<?php
header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 0);

ini_set('session.save_path', __DIR__ . '/_sessions');
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'dbconnect.php';
try {
    include 'dbconnect.php';
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error'   => 'Database connection failed',
        'details' => $e->getMessage()
    ]);
    exit();
}

try {
    $company = isset($_GET['company']) && $_GET['company'] !== '' ? trim($_GET['company']) : null;
    
    if (!$company) {
        echo json_encode(['success' => false, 'error' => 'Company name required']);
        exit;
    }
    
    $companyEscaped = $conn->real_escape_string($company);
    
    // First, find the CompanyID
    $sqlCompanyID = "SELECT CompanyID FROM Company WHERE CompanyName = '$companyEscaped' LIMIT 1";
    $resultCompanyID = $conn->query($sqlCompanyID);
    
    if (!$resultCompanyID || $resultCompanyID->num_rows === 0) {
        echo json_encode([
            'success' => false,
            'error' => 'Company not found'
        ]);
        exit;
    }
    
    $companyRow = $resultCompanyID->fetch_assoc();
    $companyId = (int) $companyRow['CompanyID'];
    
    // Get disruption events for this company
    $sql = "
        SELECT 
            de.EventID,
            dc.CategoryName as DisruptionType,
            ic.ImpactLevel,
            de.EventDate,
            de.EventRecoveryDate as RecoveryDate,
            TIMESTAMPDIFF(
                DAY,
                de.EventDate,
                COALESCE(de.EventRecoveryDate, CURDATE())
            ) AS DurationDays
        FROM DisruptionEvent de
        JOIN DisruptionCategory dc ON de.CategoryID = dc.CategoryID
        JOIN ImpactsCompany ic ON de.EventID = ic.EventID
        WHERE ic.AffectedCompanyID = $companyId
        ORDER BY de.EventDate DESC
        LIMIT 20
    ";
    
    $result = $conn->query($sql);
    
    if (!$result) {
        throw new Exception("Query failed: " . $conn->error);
    }
    
    $events = [];
    while ($row = $result->fetch_assoc()) {
        $events[] = $row;
    }
    
    echo json_encode([
        'success' => true,
        'events' => $events
    ]);
    
} catch (Exception $e) {
    error_log("Company Disruption Events Error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

if (isset($conn)) {
    $conn->close();
}
?>