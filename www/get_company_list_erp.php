<?php
ini_set('session.save_path', __DIR__ . '/_sessions');
// get_company_list_erp.php
header('Content-Type: application/json');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// (change this line if your project uses a different file name)
require 'dbconnect.php';

if (!isset($conn)) {
    echo json_encode([
        'success' => false,
        'error'   => 'Database connection not initialized.'
    ]);
    exit;
}

$companies = [];

try {
    // Adjust table / column names to match your schema
    $sql = "SELECT CompanyID, CompanyName
            FROM Companies
            ORDER BY CompanyName ASC";

    if ($result = $conn->query($sql)) {
        while ($row = $result->fetch_assoc()) {
            $companies[] = [
                'CompanyID'   => $row['CompanyID'],
                'CompanyName' => $row['CompanyName']
            ];
        }
        $result->free();
    } else {
        echo json_encode([
            'success' => false,
            'error'   => 'Query failed: ' . $conn->error
        ]);
        exit;
    }

    echo json_encode([
        'success'   => true,
        'companies' => $companies
    ]);
} catch (Throwable $e) {
    echo json_encode([
        'success' => false,
        'error'   => 'Exception: ' . $e->getMessage()
    ]);
}
