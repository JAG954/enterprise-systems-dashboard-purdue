<?php
header('Content-Type: application/json');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'dbconnect.php';

$term = isset($_GET['term']) ? $_GET['term'] : '';
$search = "%{$term}%";

$stmt = $conn->prepare("SELECT DISTINCT CompanyName, Type FROM Company WHERE CompanyName LIKE ? ORDER BY CompanyName LIMIT 20");
$stmt->bind_param('s', $search);
$stmt->execute();

// Use bind_result instead of get_result
$stmt->bind_result($companyName, $companyType);

$companies = [];
while ($stmt->fetch()) {
    $companies[] = ['name' => $companyName, 'type' => $companyType];
}

echo json_encode(['success' => true, 'companies' => $companies]);
$stmt->close();
$conn->close();
?>
