<?php
ini_set('session.save_path', __DIR__ . '/_sessions');
// Suppress PHP errors from being outputted (they break JSON)
error_reporting(E_ALL);
ini_set('display_errors', 0);

header('Content-Type: application/json');
ini_set('session.save_path', __DIR__ . '/_sessions');
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include database connection
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
    // Get filter parameters
    $startDate = isset($_GET['startDate']) && $_GET['startDate'] !== '' ? $_GET['startDate'] : null;
    $endDate   = isset($_GET['endDate'])   && $_GET['endDate']   !== '' ? $_GET['endDate']   : null;
    $company   = isset($_GET['company'])   && $_GET['company']   !== '' ? $_GET['company']   : null;
    $region    = isset($_GET['region'])    && $_GET['region']    !== '' ? $_GET['region']    : null;

    // --- Build WHERE clause for company / region filters ---
    $whereConditions = [];

    // Company filter (focal company)
    if ($company) {
        $whereConditions[] = "c.CompanyName = '" . $conn->real_escape_string($company) . "'";
    }

    // Region filter (focal company location)
    if ($region) {
        $whereConditions[] = "l.ContinentName = '" . $conn->real_escape_string($region) . "'";
    }

    $whereClause = '';
    if (!empty($whereConditions)) {
        $whereClause = 'WHERE ' . implode(' AND ', $whereConditions);
    }

    // --- Build ON-clause conditions for disruption date range ---
    $eventConditions = [];
    if ($startDate) {
        $eventConditions[] = "de.EventDate >= '" . $conn->real_escape_string($startDate) . "'";
    }
    if ($endDate) {
        $eventConditions[] = "de.EventDate <= '" . $conn->real_escape_string($endDate) . "'";
    }

    $eventDateClause = '';
    if (!empty($eventConditions)) {
        $eventDateClause = ' AND ' . implode(' AND ', $eventConditions);
    }

    /*
     * Criticality definition:
     *  - DownstreamCount: # of distinct companies that depend on this company
     *      DependsOn.UpstreamCompanyID = focal company
     *      DependsOn.DownstreamCompanyID = customer company
     *  - HighImpactCount: # of HIGH impact disruption events that hit this company
     *  - CriticalityScore = DownstreamCount * HighImpactCount
     *
     * Tables:
     *  - Company c
     *  - DependsOn d         (d.UpstreamCompanyID, d.DownstreamCompanyID)
     *  - ImpactsCompany ic   (ic.EventID, ic.AffectedCompanyID, ic.ImpactLevel)
     *  - DisruptionEvent de  (de.EventID, de.EventDate, ...)
     *  - Location l          (c.LocationID -> l.LocationID, l.ContinentName)
     */

    $sql = "
        SELECT
            c.CompanyID,
            c.CompanyName,
            c.Type AS CompanyType,
            COALESCE(COUNT(DISTINCT d.DownstreamCompanyID), 0) AS DownstreamCount,
            COALESCE(
                SUM(
                    CASE WHEN ic.ImpactLevel = 'High' THEN 1 ELSE 0 END
                ), 0
            ) AS HighImpactCount,
            COALESCE(COUNT(DISTINCT de.EventID), 0) AS TotalEvents,
            (
                COALESCE(COUNT(DISTINCT d.DownstreamCompanyID), 0) *
                COALESCE(
                    SUM(
                        CASE WHEN ic.ImpactLevel = 'High' THEN 1 ELSE 0 END
                    ), 0
                )
            ) AS CriticalityScore
        FROM Company c
        LEFT JOIN DependsOn d
            ON d.UpstreamCompanyID = c.CompanyID
        LEFT JOIN ImpactsCompany ic
            ON ic.AffectedCompanyID = c.CompanyID
        LEFT JOIN DisruptionEvent de
            ON de.EventID = ic.EventID
            $eventDateClause
        LEFT JOIN Location l
            ON c.LocationID = l.LocationID
        $whereClause
        GROUP BY
            c.CompanyID,
            c.CompanyName,
            c.Type
        HAVING
            DownstreamCount > 0
            OR HighImpactCount > 0
        ORDER BY
            CriticalityScore DESC,
            DownstreamCount DESC,
            HighImpactCount DESC
        LIMIT 10
    ";

    $result = $conn->query($sql);
    if (!$result) {
        throw new Exception("Query failed: " . $conn->error);
    }

    $companies = [];
    while ($row = $result->fetch_assoc()) {
        $companies[] = $row;
    }

    echo json_encode([
        'success' => true,
        'data'    => $companies
    ]);

} catch (Exception $e) {
    error_log("Critical Companies Error: " . $e->getMessage());
    error_log("SQL Error: " . $conn->error);

    echo json_encode([
        'success'   => false,
        'error'     => $e->getMessage(),
        'sql_error' => $conn->error,
        'debug'     => [
            'file' => __FILE__,
            'line' => $e->getLine()
        ]
    ]);
}

if (isset($conn)) {
    $conn->close();
}
?>
