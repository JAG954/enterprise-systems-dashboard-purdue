<?php
// search_events_erp.php
// Autocomplete for event IDs in ERP dashboard
// Do NOT echo anything except JSON.
error_reporting(E_ALL);
ini_set('display_errors', 0);
header('Content-Type: application/json');
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Try to connect to database
try {
    // Use the SAME connection file you use in get_critical_companies.php, etc.
    if (!file_exists('dbconnect.php')) {
        throw new Exception('dbconnect.php file not found');
    }
    include 'dbconnect.php';
    
    if (!isset($conn) || !($conn instanceof mysqli)) {
        throw new Exception('Database connection not established');
    }
    
    if ($conn->connect_error) {
        throw new Exception('Connection failed: ' . $conn->connect_error);
    }
    
} catch (Throwable $e) {
    echo json_encode([
        'success' => false,
        'error'   => 'Database connection failed',
        'details' => $e->getMessage()
    ]);
    exit;
}

try {
    $q = isset($_GET['q']) ? trim($_GET['q']) : '';
    
    if ($q === '') {
        echo json_encode([
            'success' => true,
            'events'  => []
        ]);
        exit;
    }
    
    // Escape once for safety
    $qEsc = $conn->real_escape_string($q);
    
    /*  Adjust table/column names here if needed to match your schema:
        Assumes:
          DisruptionEvent (e)
            - EventID
            - EventDate
            - EventRecoveryDate
            - CategoryID  (FK)
          EventCategory (c)
            - CategoryID
            - CategoryName
    */
    $sql = "
        SELECT
            e.EventID,
            DATE_FORMAT(e.EventDate, '%Y-%m-%d')         AS EventDate,
            DATE_FORMAT(e.EventRecoveryDate, '%Y-%m-%d') AS EventRecoveryDate,
            c.CategoryName
        FROM DisruptionEvent e
        LEFT JOIN EventCategory c
            ON e.CategoryID = c.CategoryID
        WHERE
            CAST(e.EventID AS CHAR) LIKE '{$qEsc}%'              -- starts with ID
            OR c.CategoryName           LIKE '%{$qEsc}%'         -- category contains text
            OR DATE_FORMAT(e.EventDate, '%Y-%m-%d') LIKE '{$qEsc}%'
        ORDER BY e.EventDate DESC, e.EventID DESC
        LIMIT 20
    ";
    
    $result = $conn->query($sql);
    
    if (!$result) {
        throw new Exception('Query failed: ' . $conn->error);
    }
    
    $events = [];
    while ($row = $result->fetch_assoc()) {
        $events[] = [
            'EventID'          => $row['EventID'],
            'EventDate'        => $row['EventDate'],
            'EventRecoveryDate'=> $row['EventRecoveryDate'],
            'CategoryName'     => $row['CategoryName'],
        ];
    }
    
    echo json_encode([
        'success' => true,
        'events'  => $events
    ]);
    
} catch (Throwable $e) {
    echo json_encode([
        'success' => false,
        'error'   => $e->getMessage()
    ]);
}

if (isset($conn) && $conn instanceof mysqli) {
    $conn->close();
}
?>