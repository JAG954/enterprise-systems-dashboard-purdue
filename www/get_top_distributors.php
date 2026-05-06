<?php
// get_top_distributors.php

error_reporting(E_ALL);
ini_set('display_errors', 0);

header('Content-Type: application/json');

ini_set('session.save_path', __DIR__ . '/_sessions');
// session_start();
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


// DB connection
try {
    include 'dbconnect.php';
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error'   => 'Database connection failed',
        'details' => $e->getMessage()
    ]);
    exit;
}

try {
    // Filters from query string
    $startDate = isset($_GET['startDate']) && $_GET['startDate'] !== '' ? $_GET['startDate'] : null;
    $endDate   = isset($_GET['endDate'])   && $_GET['endDate']   !== '' ? $_GET['endDate']   : null;
    $company   = isset($_GET['company'])   && $_GET['company']   !== '' ? $_GET['company']   : null;
    $region    = isset($_GET['region'])    && $_GET['region']    !== '' ? $_GET['region']    : null;

    $where = [];

    // Use COALESCE(ActualDate, PromisedDate) for date filtering
    if ($startDate) {
        $where[] = "COALESCE(s.ActualDate, s.PromisedDate) >= '" . $conn->real_escape_string($startDate) . "'";
    }
    if ($endDate) {
        $where[] = "COALESCE(s.ActualDate, s.PromisedDate) <= '" . $conn->real_escape_string($endDate) . "'";
    }

    // Company filter → distributor’s company name
    if ($company) {
        $where[] = "c.CompanyName = '" . $conn->real_escape_string($company) . "'";
    }

    // Region filter → distributor’s region (continent)
    if ($region) {
        $where[] = "l.ContinentName = '" . $conn->real_escape_string($region) . "'";
    }

    $whereClause = '';
    if (!empty($where)) {
        $whereClause = 'WHERE ' . implode(' AND ', $where);
    }

    /*
     * Schema used:
     *   Shipping s   (ShipmentID, DistributorID, Quantity, PromisedDate, ActualDate, ...)
     *   Company c    (CompanyID, CompanyName, LocationID, ...)
     *   Location l   (LocationID, ContinentName, ...)
     *
     * Here, Shipping.DistributorID references Company.CompanyID.
     */

    $sql = "
        SELECT
            c.CompanyID AS DistributorCompanyID,
            c.CompanyName,
            COALESCE(l.ContinentName, 'Unknown') AS Region,
            SUM(s.Quantity) AS TotalQuantity,
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
            TotalQuantity IS NOT NULL
        ORDER BY
            TotalQuantity DESC
        LIMIT 10
    ";

    $result = $conn->query($sql);
    if (!$result) {
        throw new Exception("Query failed: " . $conn->error);
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
    error_log("Top Distributors Error: " . $e->getMessage());
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
