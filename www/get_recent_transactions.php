<?php
ini_set('display_errors', 0);
error_reporting(E_ALL);
ini_set('session.save_path', __DIR__ . '/_sessions');
header('Content-Type: application/json');
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'dbconnect.php';

try {
    $company = isset($_GET['company']) ? trim($_GET['company']) : '';
    $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 10;

    if (empty($company)) {
        echo json_encode(['success' => false, 'message' => 'No company specified']);
        exit;
    }

    $comp = $conn->real_escape_string($company);

    // Get recent shipments involving this company
    // Status is calculated: if ActualDate is NULL, it's "Pending", otherwise compare dates
    $transactionsQuery = "
        SELECT 
            s.ShipmentID,
            p.ProductName,
            s.Quantity,
            src.CompanyName as SourceCompany,
            dest.CompanyName as DestinationCompany,
            CASE 
                WHEN s.ActualDate IS NULL THEN 'Pending'
                WHEN s.ActualDate <= s.PromisedDate THEN 'On Time'
                ELSE 'Delayed'
            END as Status,
            s.PromisedDate,
            s.ActualDate
        FROM Shipping s
        JOIN Company src ON s.SourceCompanyID = src.CompanyID
        JOIN Company dest ON s.DestinationCompanyID = dest.CompanyID
        LEFT JOIN Company dist ON s.DistributorID = dist.CompanyID
        LEFT JOIN Product p ON s.ProductID = p.ProductID
        WHERE src.CompanyName = '{$comp}' 
           OR dest.CompanyName = '{$comp}' 
           OR dist.CompanyName = '{$comp}'
        ORDER BY s.PromisedDate DESC
        LIMIT {$limit}
    ";

    $transactions = [];
    $result = @$conn->query($transactionsQuery);
    
    if (!$result) {
        throw new Exception('Query failed: ' . $conn->error);
    }
    
    while ($row = $result->fetch_assoc()) {
        $transactions[] = $row;
    }

    echo json_encode([
        'success' => true,
        'transactions' => $transactions
    ]);

    $conn->close();

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
