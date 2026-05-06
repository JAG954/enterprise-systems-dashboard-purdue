<?php
header('Content-Type: application/json');
ini_set('session.save_path', __DIR__ . '/_sessions');
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'dbconnect.php';
ini_set('session.save_path', __DIR__ . '/_sessions');

$startDate = isset($_GET['startDate']) ? $_GET['startDate'] : null;
$endDate = isset($_GET['endDate']) ? $_GET['endDate'] : null;
$company = isset($_GET['company']) ? $_GET['company'] : '';
$region = isset($_GET['region']) ? $_GET['region'] : '';

// Build WHERE clause for filters
$whereClause = "WHERE 1=1";

if ($startDate) {
    $whereClause .= " AND s.PromisedDate >= '" . $conn->real_escape_string($startDate) . "'";
}

if ($endDate) {
    $whereClause .= " AND s.PromisedDate <= '" . $conn->real_escape_string($endDate) . "'";
}

if ($company) {
    $comp = $conn->real_escape_string($company);
    $whereClause .= " AND (src.CompanyName LIKE '%{$comp}%' OR dest.CompanyName LIKE '%{$comp}%' OR dist.CompanyName LIKE '%{$comp}%')";
}

if ($region) {
    $reg = $conn->real_escape_string($region);
    $whereClause .= " AND (l_src.ContinentName = '{$reg}' OR l_dest.ContinentName = '{$reg}')";
}

// 1. Get Shipment Volume by Distributor
$shipmentVolumeQuery = "
    SELECT 
        dist.CompanyName as Distributor,
        SUM(s.Quantity) as TotalQuantity
    FROM Shipping s
    LEFT JOIN Company src ON s.SourceCompanyID = src.CompanyID
    LEFT JOIN Company dest ON s.DestinationCompanyID = dest.CompanyID
    LEFT JOIN Company dist ON s.DistributorID = dist.CompanyID
    LEFT JOIN Location l_src ON src.LocationID = l_src.LocationID
    LEFT JOIN Location l_dest ON dest.LocationID = l_dest.LocationID
    {$whereClause} AND dist.CompanyName IS NOT NULL
    GROUP BY dist.CompanyName
    ORDER BY TotalQuantity DESC
    LIMIT 10
";

$shipmentVolume = [];
$result = $conn->query($shipmentVolumeQuery);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $shipmentVolume[] = $row;
    }
}

// 2. Get On-Time Delivery Rate by Distributor
$deliveryRateQuery = "
    SELECT 
        dist.CompanyName as Distributor,
        COUNT(*) as TotalShipments,
        SUM(CASE WHEN s.ActualDate IS NOT NULL AND s.ActualDate <= s.PromisedDate THEN 1 ELSE 0 END) as OnTimeShipments,
        ROUND(100.0 * SUM(CASE WHEN s.ActualDate IS NOT NULL AND s.ActualDate <= s.PromisedDate THEN 1 ELSE 0 END) / COUNT(*), 1) as OnTimeRate
    FROM Shipping s
    LEFT JOIN Company src ON s.SourceCompanyID = src.CompanyID
    LEFT JOIN Company dest ON s.DestinationCompanyID = dest.CompanyID
    LEFT JOIN Company dist ON s.DistributorID = dist.CompanyID
    LEFT JOIN Location l_src ON src.LocationID = l_src.LocationID
    LEFT JOIN Location l_dest ON dest.LocationID = l_dest.LocationID
    {$whereClause} AND dist.CompanyName IS NOT NULL AND s.ActualDate IS NOT NULL
    GROUP BY dist.CompanyName
    HAVING COUNT(*) >= 5
    ORDER BY OnTimeRate DESC
    LIMIT 10
";

$deliveryRate = [];
$result = $conn->query($deliveryRateQuery);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $deliveryRate[] = $row;
    }
}

// 3. Get Shipment Status Distribution
$shipmentStatusQuery = "
    SELECT 
        CASE 
            WHEN s.ActualDate IS NOT NULL AND s.ActualDate <= s.PromisedDate THEN 'Delivered On Time'
            WHEN s.ActualDate IS NOT NULL AND s.ActualDate > s.PromisedDate THEN 'Delivered Late'
            WHEN s.ActualDate IS NULL AND s.PromisedDate < CURDATE() THEN 'Overdue'
            ELSE 'In Transit'
        END as Status,
        COUNT(*) as Count
    FROM Shipping s
    LEFT JOIN Company src ON s.SourceCompanyID = src.CompanyID
    LEFT JOIN Company dest ON s.DestinationCompanyID = dest.CompanyID
    LEFT JOIN Company dist ON s.DistributorID = dist.CompanyID
    LEFT JOIN Location l_src ON src.LocationID = l_src.LocationID
    LEFT JOIN Location l_dest ON dest.LocationID = l_dest.LocationID
    {$whereClause}
    GROUP BY Status
    ORDER BY Count DESC
";

$shipmentStatus = [];
$result = $conn->query($shipmentStatusQuery);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $shipmentStatus[] = $row;
    }
}

// 4. Get Products Handled
$productsQuery = "
    SELECT 
        p.ProductName,
        p.Category,
        SUM(s.Quantity) as TotalQuantity
    FROM Shipping s
    LEFT JOIN Product p ON s.ProductID = p.ProductID
    LEFT JOIN Company src ON s.SourceCompanyID = src.CompanyID
    LEFT JOIN Company dest ON s.DestinationCompanyID = dest.CompanyID
    LEFT JOIN Company dist ON s.DistributorID = dist.CompanyID
    LEFT JOIN Location l_src ON src.LocationID = l_src.LocationID
    LEFT JOIN Location l_dest ON dest.LocationID = l_dest.LocationID
    {$whereClause} AND p.ProductName IS NOT NULL
    GROUP BY p.ProductName, p.Category
    ORDER BY TotalQuantity DESC
    LIMIT 10
";

$productsHandled = [];
$result = $conn->query($productsQuery);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $productsHandled[] = $row;
    }
}

// 5. Get Top Routes
$routesQuery = "
    SELECT 
        CONCAT(l_src.ContinentName, ' → ', l_dest.ContinentName) as Route,
        COUNT(*) as ShipmentCount,
        SUM(s.Quantity) as TotalQuantity
    FROM Shipping s
    LEFT JOIN Company src ON s.SourceCompanyID = src.CompanyID
    LEFT JOIN Company dest ON s.DestinationCompanyID = dest.CompanyID
    LEFT JOIN Company dist ON s.DistributorID = dist.CompanyID
    LEFT JOIN Location l_src ON src.LocationID = l_src.LocationID
    LEFT JOIN Location l_dest ON dest.LocationID = l_dest.LocationID
    {$whereClause} AND l_src.ContinentName IS NOT NULL AND l_dest.ContinentName IS NOT NULL
    GROUP BY l_src.ContinentName, l_dest.ContinentName
    ORDER BY ShipmentCount DESC
    LIMIT 10
";

$topRoutes = [];
$result = $conn->query($routesQuery);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $topRoutes[] = $row;
    }
}

// 6. Get Disruption Exposure
$disruptionQuery = "
    SELECT 
        de.EventID as DisruptionEventID,
        dc.CategoryName as DisruptionType,
        COUNT(DISTINCT ic.AffectedCompanyID) as AffectedCompanies,
        CASE 
            WHEN SUM(CASE WHEN ic.ImpactLevel = 'High' THEN 1 ELSE 0 END) > 0 THEN 'High'
            WHEN SUM(CASE WHEN ic.ImpactLevel = 'Medium' THEN 1 ELSE 0 END) > 0 THEN 'Medium'
            ELSE 'Low'
        END as ImpactLevel,
        de.EventDate as StartDate
    FROM DisruptionEvent de
    LEFT JOIN DisruptionCategory dc ON de.CategoryID = dc.CategoryID
    LEFT JOIN ImpactsCompany ic ON de.EventID = ic.EventID
    WHERE 1=1
";

if ($startDate) {
    $disruptionQuery .= " AND de.EventDate >= '" . $conn->real_escape_string($startDate) . "'";
}

if ($endDate) {
    $disruptionQuery .= " AND de.EventDate <= '" . $conn->real_escape_string($endDate) . "'";
}

$disruptionQuery .= " GROUP BY de.EventID, dc.CategoryName, de.EventDate 
                      HAVING COUNT(DISTINCT ic.AffectedCompanyID) > 0
                      ORDER BY AffectedCompanies DESC 
                      LIMIT 10";

$disruptionExposure = [];
$result = $conn->query($disruptionQuery);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $disruptionExposure[] = $row;
    }
}

// 7. Get Transaction Details
$transactionQuery = "
    SELECT 
        t.TransactionID,
        t.Type as TransactionType,
        s.ShipmentID,
        s.Quantity,
        s.PromisedDate,
        s.ActualDate,
        p.ProductName,
        p.Category as ProductCategory,
        src.CompanyName as SourceCompany,
        dest.CompanyName as DestinationCompany,
        dist.CompanyName as DistributorName,
        l_src.ContinentName as SourceContinent,
        l_dest.ContinentName as DestinationContinent
    FROM InventoryTransaction t
    LEFT JOIN Shipping s ON t.TransactionID = s.TransactionID
    LEFT JOIN Product p ON s.ProductID = p.ProductID
    LEFT JOIN Company src ON s.SourceCompanyID = src.CompanyID
    LEFT JOIN Company dest ON s.DestinationCompanyID = dest.CompanyID
    LEFT JOIN Company dist ON s.DistributorID = dist.CompanyID
    LEFT JOIN Location l_src ON src.LocationID = l_src.LocationID
    LEFT JOIN Location l_dest ON dest.LocationID = l_dest.LocationID
    {$whereClause}
    ORDER BY s.PromisedDate DESC
    LIMIT 100
";

$transactions = [];
$result = $conn->query($transactionQuery);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $status = 'In Transit';
        if ($row['ActualDate']) {
            if ($row['ActualDate'] <= $row['PromisedDate']) {
                $status = 'Delivered On-Time';
            } else {
                $status = 'Delayed';
            }
        } else if ($row['PromisedDate'] < date('Y-m-d')) {
            $status = 'Overdue';
        }
        $row['Status'] = $status;
        $transactions[] = $row;
    }
}

echo json_encode([
    'success' => true, 
    'data' => [
        'shipmentVolume' => $shipmentVolume,
        'deliveryRate' => $deliveryRate,
        'shipmentStatus' => $shipmentStatus,
        'productsHandled' => $productsHandled,
        'topRoutes' => $topRoutes,
        'disruptionExposure' => $disruptionExposure,
        'transactions' => $transactions
    ],
    'count' => count($transactions)
]);

$conn->close();
?>
