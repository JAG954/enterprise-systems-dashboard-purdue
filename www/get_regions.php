<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
ini_set('session.save_path', __DIR__ . '/_sessions');
header('Content-Type: application/json');
include 'dbconnect.php';
$query = "SELECT DISTINCT ContinentName FROM Location ORDER BY ContinentName";
$result = $conn->query($query);
$regions = [];
while ($row = $result->fetch_assoc()) {
    $regions[] = $row['ContinentName'];
}
echo json_encode(['success' => true, 'regions' => $regions]);
$conn->close();
?>