<?php
header('Content-Type: application/json');
ini_set('session.save_path', __DIR__ . '/_sessions');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'dbconnect.php';


// Get unique regions
$regionsQuery = "SELECT DISTINCT ContinentName FROM Location WHERE ContinentName IS NOT NULL AND ContinentName != '' ORDER BY ContinentName";

$regions = [];
$result = $conn->query($regionsQuery);
if ($result) {
    $seen = []; // Track which regions we've already added
    while ($row = $result->fetch_assoc()) {
        $region = $row['ContinentName'];
        if (!in_array($region, $seen)) {
            $regions[] = $region;
            $seen[] = $region;
        }
    }
}

// Get unique company types
$typesQuery = "SELECT DISTINCT CompanyType FROM Company WHERE CompanyType IS NOT NULL AND CompanyType != '' ORDER BY CompanyType";
$companyTypes = [];
$result = $conn->query($typesQuery);
if ($result) {
    $seen = []; // Track which types we've already added
    while ($row = $result->fetch_assoc()) {
        $type = $row['CompanyType'];
        if (!in_array($type, $seen)) {
            $companyTypes[] = $type;
            $seen[] = $type;
        }
    }
}

echo json_encode([
    'success' => true,
    'regions' => $regions,
    'companyTypes' => $companyTypes
]);

$conn->close();
?>