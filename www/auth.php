<?php 
session_start();
require_once 'dbconnect.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: index.php?error");
    exit();
}

$sql_stmt = "SELECT FullName, Password, Role, UserID, Username
             FROM User
             WHERE Username = ? AND Password = MD5(?)
             LIMIT 1;";
$stmt = $conn->prepare($sql_stmt);

if (!$stmt) {
    header("Location: index.php?error");
    exit();
}


$username = trim($_POST['username']);
$password = $_POST['password'];

$stmt->bind_param('ss', $username, $password);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows == 0) {
    $stmt->close();
    $conn->close();
    header("Location: index.php?error");
    exit();
}

$stmt->bind_result($FullName, $Password, $Role, $UserID, $Username);
$stmt->fetch();


session_regenerate_id(true);

$_SESSION['UserID'] = $UserID;
$_SESSION['FullName'] = $FullName;
$_SESSION['Role'] = $Role;
$_SESSION['Username'] = $Username;

$stmt->close();
$conn->close();

if ($Role == 'SupplyChainManager') {
    header("Location: scm.php");
    exit();
} elseif ($Role == 'SeniorManager') {
    header("Location: erp.php");
    exit();
} else {
    header("Location:index.php?error");
    exit();
}

?>
