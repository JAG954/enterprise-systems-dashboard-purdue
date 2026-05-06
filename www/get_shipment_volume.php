<?php
header('Content-Type: application/json');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'dbconnect.php';


// Get filters
$company = isset($_GET['company']) ? $_GET['company'] : '';
$region = isset($_GET['region']) ? $_GET['region'] : '';

// Query to get shipment volume by distributor
$query = "
    SELECT 
        dist.CompanyName as Distributor,
        SUM(s.Quantity) as Quantity
    FROM Shipping s
    JOIN Company dist ON s.DistributorID = dist.CompanyID
    LEFT JOIN Company src ON s.SourceCompanyID = src.CompanyID
    LEFT JOIN Company dest ON s.DestinationCompanyID = dest.CompanyID
    LEFT JOIN Location l_dist ON dist.LocationID = l_dist.LocationID
    WHERE 1=1
";

if ($company) {
    $comp = $conn->real_escape_string($company);
    $query .= " AND (dist.CompanyName LIKE '%{$comp}%' OR src.CompanyName LIKE '%{$comp}%' OR dest.CompanyName LIKE '%{$comp}%')";
}

if ($region) {
    $reg = $conn->real_escape_string($region);
    $query .= " AND l_dist.ContinentName = '{$reg}'";
}

$query .= " GROUP BY dist.CompanyName ORDER BY Quantity DESC LIMIT 10";

$result = $conn->query($query);

$shipments = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $shipments[] = $row;
    }
}

echo json_encode(['success' => true, 'data' => $shipments]);
$conn->close();
?>
