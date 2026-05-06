<?php
header('Content-Type: application/json');
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'dbconnect.php';

$name = mysqli_real_escape_string($conn, $_POST['companyName']);
$type = mysqli_real_escape_string($conn, $_POST['companyType']);
$region = mysqli_real_escape_string($conn, $_POST['region']);

$sql = "SELECT LocationID FROM Location WHERE ContinentName = '$region' LIMIT 1";
$result = mysqli_query($conn, $sql);

if (mysqli_num_rows($result) > 0) {
    $row = mysqli_fetch_assoc($result);
    $locId = $row['LocationID'];
} else {
    $sql = "INSERT INTO Location (ContinentName) VALUES ('$region')";
    mysqli_query($conn, $sql);
    $locId = mysqli_insert_id($conn);
}

$sql = "INSERT INTO Company (CompanyName, LocationID, TierLevel, Type) VALUES ('$name', $locId, 3, '$type')";
mysqli_query($conn, $sql);
$newId = mysqli_insert_id($conn);

mysqli_close($conn);

echo '{"success":true,"company":{"CompanyID":' . $newId . ',"CompanyName":"' . $name . '"}}';
?>
