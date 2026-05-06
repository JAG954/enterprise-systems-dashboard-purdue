<?php
// get_distributors_by_delay.php

error_reporting(EALL);
ini_set('display_errors', 0);

header('Content-Type: application/json');

ini_set('session.save_path', __DIR__ . '/_sessions');
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

try {
    require_once 'dbconnect.php';
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error'   => 'Database connection failed',
        'details' => $e->getMessage()
    ]);
    exit;
}

try {
    // Optional filters (same as other endpoints)
    $startDate = isset($_GET['startDate']) && $_GET['startDate'] !== '' ? $_GET['startDate'] : null;
    $endDate   = isset($_GET['endDate'])   && $_GET['endDate']   !== '' ? $_GET['endDate']   : null;
    $company   = isset($_GET['company'])   && $_GET['company']   !== '' ? $_GET['company']   : null;
    $region    = isset($_GET['region'])    && $_GET['region']    !== '' ? $_GET['region']    : null;

    $where = [];

    if ($startDate) {
        $where[] = "COALESCE(s.ActualDate, s.PromisedDate) >= '" . $conn->real_escape_string($startDate) . "'";
    }
    if ($endDate) {
        $where[] = "COALESCE(s.ActualDate, s.PromisedDate) <= '" . $conn->real_escape_string($endDate) . "'";
    }

    // Filter on distributor company (by name)
    if ($company) {
        $where[] = "c.CompanyName = '" . $conn->real_escape_string($company) . "'";
    }

    // Region filter on distributor location
    if ($region) {
        $where[] = "l.ContinentName = '" . $conn->real_escape_string($region) . "'";
    }

    $whereClause = '';
    if (!empty($where)) {
        $whereClause = 'WHERE ' . implode(' AND ', $where);
    }

    /*
     * Shipping s (DistributorID, PromisedDate, ActualDate, Quantity, ...)
     * Company  c (CompanyID, CompanyName, LocationID, ...)
     * Location l (LocationID, ContinentName, ...)
     */

    $sql = "
        SELECT
            c.CompanyID AS DistributorCompanyID,
            c.CompanyName,
            COALESCE(l.ContinentName, 'Unknown') AS Region,
            COUNT(*) AS ShipmentCount,
            AVG(
                CASE
                    WHEN s.ActualDate IS NOT NULL AND s.PromisedDate IS NOT NULL
                    THEN DATEDIFF(s.ActualDate, s.PromisedDate)
                    ELSE NULL
                END
            ) AS AvgDelayDays
        FROM Shipping s
        JOIN Company c
            ON s.DistributorID = c.CompanyID
        LEFT JOIN Location l
            ON c.LocationID = l.LocationID
        $whereClause
        GROUP BY
            c.CompanyID,
            c.CompanyName,
            Region
        HAVING
            ShipmentCount > 0
            AND AvgDelayDays IS NOT NULL
        ORDER BY
            AvgDelayDays DESC
        LIMIT 10
    ";

    $result = $conn->query($sql);
    if (!$result) {
        throw new Exception('Query failed: ' . $conn->error);
    }

    $rows = [];
    while ($row = $result->fetch_assoc()) {
        $rows[] = $row;
    }

    echo json_encode([
        'success' => true,
        'data'    => $rows
    ]);

} catch (Exception $e) {
    error_log("Distributors by Delay Error: " . $e->getMessage());
    error_log("SQL Error: " . $conn->error);

    echo json_encode([
        'success'   => false,
        'error'     => $e->getMessage(),
        'sql_error' => $conn->error
    ]);
}

if (isset($conn)) {
    $conn->close();
}
