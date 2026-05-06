<?php
header('Content-Type: application/json');
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
ini_set('session.save_path', __DIR__ . '/_sessions');
require_once 'dbconnect.php';

// Get filters
$company = isset($_GET['company']) ? $_GET['company'] : '';
$region = isset($_GET['region']) ? $_GET['region'] : '';

// Query to get products handled with total units
$query = "
    SELECT 
        p.ProductName as Product,
        p.Category,
        SUM(s.Quantity) as Units
    FROM Shipping s
    JOIN Product p ON s.ProductID = p.ProductID
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

$query .= " GROUP BY p.ProductID, p.ProductName, p.Category ORDER BY Units DESC LIMIT 15";

$result = $conn->query($query);

$products = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $products[] = $row;
    }
}

echo json_encode(['success' => true, 'data' => $products]);
$conn->close();
?>
