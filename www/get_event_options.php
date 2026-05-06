<?php
// get_event_options.php

ini_set('session.save_path', __DIR__ . '/_sessions');
error_reporting(E_ALL);
ini_set('display_errors', 0);

header('Content-Type: application/json');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'dbconnect.php';

$response = [
    'success' => false,
    'error'   => null,
    'events'  => []
];

try {
    // Optional: enforce login but do NOT redirect (we want JSON)
    // if (!isset($_SESSION['UserID'])) {
    //     throw new Exception('Not logged in.');
    // }

    // Pull all events with their category names
    $sql = "
        SELECT
            de.EventID,
            de.EventDate,
            dc.CategoryName
        FROM DisruptionEvent AS de
        LEFT JOIN DisruptionCategory AS dc
            ON de.CategoryID = dc.CategoryID
        ORDER BY de.EventDate DESC, de.EventID DESC
        LIMIT 200
    ";

    $result = $conn->query($sql);
    if (!$result) {
        throw new Exception('Query failed: ' . $conn->error);
    }

    while ($row = $result->fetch_assoc()) {
        $response['events'][] = $row;
    }

    $response['success'] = true;
    echo json_encode($response);
} catch (Throwable $e) {
    $response['success'] = false;
    $response['error']   = $e->getMessage();
    echo json_encode($response);
}
