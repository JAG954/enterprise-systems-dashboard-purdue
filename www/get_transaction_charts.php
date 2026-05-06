<?php
header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 0);

try {
    require_once 'dbconnect.php';
    
    $startDate = isset($_GET['startDate']) && $_GET['startDate'] !== '' ? $_GET['startDate'] : null;
    $endDate = isset($_GET['endDate']) && $_GET['endDate'] !== '' ? $_GET['endDate'] : null;
    $company = isset($_GET['company']) && $_GET['company'] !== '' ? trim($_GET['company']) : null;
    $region = isset($_GET['region']) && $_GET['region'] !== '' ? $_GET['region'] : null;
    
    // Build WHERE clause
    $whereConditions = [];
    
    if ($startDate) {
        $whereConditions[] = "s.PromisedDate >= '" . $conn->real_escape_string($startDate) . "'";
    }
    if ($endDate) {
        $whereConditions[] = "s.PromisedDate <= '" . $conn->real_escape_string($endDate) . "'";
    }
    if ($company) {
        $companyEsc = $conn->real_escape_string($company);
        $whereConditions[] = "(src.CompanyName = '$companyEsc' OR dest.CompanyName = '$companyEsc')";
    }
    if ($region) {
        $regionEsc = $conn->real_escape_string($region);
        $whereConditions[] = "(srcLoc.ContinentName = '$regionEsc' OR destLoc.ContinentName = '$regionEsc')";
    }
    
    $whereClause = count($whereConditions) > 0 ? 'WHERE ' . implode(' AND ', $whereConditions) : '';
    
    // Chart 1: Volume Over Time (monthly)
    $volumeTimeSql = "
        SELECT 
            DATE_FORMAT(s.PromisedDate, '%Y-%m') as Month,
            COUNT(*) as ShipmentCount,
            SUM(s.Quantity) as TotalQuantity
        FROM Shipping s
        JOIN Company src ON s.SourceCompanyID = src.CompanyID
        JOIN Company dest ON s.DestinationCompanyID = dest.CompanyID
        LEFT JOIN Location srcLoc ON src.LocationID = srcLoc.LocationID
        LEFT JOIN Location destLoc ON dest.LocationID = destLoc.LocationID
        $whereClause
        GROUP BY Month
        ORDER BY Month DESC
        LIMIT 12
    ";
    
    $result = $conn->query($volumeTimeSql);
    $volumeTime = [];
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $volumeTime[] = $row;
        }
    }
    $volumeTime = array_reverse($volumeTime); // Oldest to newest
    
    // Chart 2: On-Time vs Delayed Status
    $statusSql = "
        SELECT 
            CASE 
                WHEN s.ActualDate IS NULL THEN 'Pending'
                WHEN s.ActualDate <= s.PromisedDate THEN 'On-Time'
                ELSE 'Delayed'
            END as Status,
            COUNT(*) as Count
        FROM Shipping s
        JOIN Company src ON s.SourceCompanyID = src.CompanyID
        JOIN Company dest ON s.DestinationCompanyID = dest.CompanyID
        LEFT JOIN Location srcLoc ON src.LocationID = srcLoc.LocationID
        LEFT JOIN Location destLoc ON dest.LocationID = destLoc.LocationID
        $whereClause
        GROUP BY Status
    ";
    
    $result = $conn->query($statusSql);
    $statusDist = [];
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $statusDist[] = $row;
        }
    }
    
    // Chart 3: Top Products by Quantity
    $productsSql = "
        SELECT 
            p.ProductName,
            SUM(s.Quantity) as TotalQuantity
        FROM Shipping s
        JOIN Product p ON s.ProductID = p.ProductID
        JOIN Company src ON s.SourceCompanyID = src.CompanyID
        JOIN Company dest ON s.DestinationCompanyID = dest.CompanyID
        LEFT JOIN Location srcLoc ON src.LocationID = srcLoc.LocationID
        LEFT JOIN Location destLoc ON dest.LocationID = destLoc.LocationID
        $whereClause
        GROUP BY p.ProductName
        ORDER BY TotalQuantity DESC
        LIMIT 10
    ";
    
    $result = $conn->query($productsSql);
    $topProducts = [];
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $topProducts[] = $row;
        }
    }
    
    // Chart 4: Route Performance (On-Time %)
    $routePerfSql = "
        SELECT 
            CONCAT(src.CompanyName, ' → ', dest.CompanyName) as Route,
            COUNT(*) as TotalShipments,
            SUM(CASE WHEN s.ActualDate IS NOT NULL AND s.ActualDate <= s.PromisedDate THEN 1 ELSE 0 END) as OnTimeCount,
            ROUND(SUM(CASE WHEN s.ActualDate IS NOT NULL AND s.ActualDate <= s.PromisedDate THEN 1 ELSE 0 END) * 100.0 / COUNT(*), 2) as OnTimePercent
        FROM Shipping s
        JOIN Company src ON s.SourceCompanyID = src.CompanyID
        JOIN Company dest ON s.DestinationCompanyID = dest.CompanyID
        LEFT JOIN Location srcLoc ON src.LocationID = srcLoc.LocationID
        LEFT JOIN Location destLoc ON dest.LocationID = destLoc.LocationID
        $whereClause
        GROUP BY Route
        HAVING TotalShipments >= 5
        ORDER BY OnTimePercent DESC
        LIMIT 10
    ";
    
    $result = $conn->query($routePerfSql);
    $routePerformance = [];
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $routePerformance[] = $row;
        }
    }
    
    echo json_encode([
        'success' => true,
        'charts' => [
            'volumeOverTime' => $volumeTime,
            'statusDistribution' => $statusDist,
            'topProducts' => $topProducts,
            'routePerformance' => $routePerformance
        ]
    ]);
    
} catch (Exception $e) {
    error_log("Transaction Charts Error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'Unable to load transaction charts'
    ]);
}

if (isset($conn)) {
    $conn->close();
}
?>
