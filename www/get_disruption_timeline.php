<?php
ini_set('session.save_path', __DIR__ . '/_sessions');
// Suppress PHP errors from being outputted (they break JSON)
error_reporting(E_ALL);
ini_set('display_errors', 0);

header('Content-Type: application/json');
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include database connection
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
    // Get filter parameters
    $startDate = isset($_GET['startDate']) && $_GET['startDate'] !== '' ? $_GET['startDate'] : null;
    $endDate   = isset($_GET['endDate'])   && $_GET['endDate']   !== '' ? $_GET['endDate']   : null;
    $company   = isset($_GET['company'])   && $_GET['company']   !== '' ? $_GET['company']   : null;
    $region    = isset($_GET['region'])    && $_GET['region']    !== '' ? $_GET['region']    : null;

    /*
     * We aggregate disruption *impacts* over time:
     *  - PeriodLabel: YYYY-MM
     *  - TotalImpacts: total rows in ImpactsCompany in that period
     *  - HighImpact / MediumImpact / LowImpact: counts by ImpactLevel
     *
     * Tables:
     *  - DisruptionEvent de (EventID, EventDate, ...)
     *  - ImpactsCompany ic (EventID, AffectedCompanyID, ImpactLevel)
     *  - Company c (CompanyID, CompanyName, LocationID, Type, ...)
     *  - Location l (LocationID, ContinentName, ...)
     */

    $whereConditions = [];

    if ($startDate) {
        $whereConditions[] = "de.EventDate >= '" . $conn->real_escape_string($startDate) . "'";
    }
    if ($endDate) {
        $whereConditions[] = "de.EventDate <= '" . $conn->real_escape_string($endDate) . "'";
    }

    if ($company) {
        $whereConditions[] = "c.CompanyName = '" . $conn->real_escape_string($company) . "'";
    }

    if ($region) {
        $whereConditions[] = "l.ContinentName = '" . $conn->real_escape_string($region) . "'";
    }

    $whereClause = '';
    if (!empty($whereConditions)) {
        $whereClause = 'WHERE ' . implode(' AND ', $whereConditions);
    }

    $sql = "
        SELECT
            DATE_FORMAT(de.EventDate, '%Y-%m') AS PeriodLabel,
            YEAR(de.EventDate) AS YearNum,
            MONTH(de.EventDate) AS MonthNum,
            COUNT(*) AS TotalImpacts,
            SUM(CASE WHEN ic.ImpactLevel = 'High'   THEN 1 ELSE 0 END) AS HighImpact,
            SUM(CASE WHEN ic.ImpactLevel = 'Medium' THEN 1 ELSE 0 END) AS MediumImpact,
            SUM(CASE WHEN ic.ImpactLevel = 'Low'    THEN 1 ELSE 0 END) AS LowImpact
        FROM DisruptionEvent de
        LEFT JOIN ImpactsCompany ic
            ON ic.EventID = de.EventID
        LEFT JOIN Company c
            ON ic.AffectedCompanyID = c.CompanyID
        LEFT JOIN Location l
            ON c.LocationID = l.LocationID
        $whereClause
        GROUP BY
            YEAR(de.EventDate),
            MONTH(de.EventDate),
            DATE_FORMAT(de.EventDate, '%Y-%m')
        ORDER BY
            YearNum ASC,
            MonthNum ASC
    ";

    $result = $conn->query($sql);
    if (!$result) {
        throw new Exception("Query failed: " . $conn->error);
    }

    $timeline = [];
    while ($row = $result->fetch_assoc()) {
        $timeline[] = $row;
    }

    echo json_encode([
        'success' => true,
        'data'    => $timeline
    ]);

} catch (Exception $e) {
    error_log("Disruption Timeline Error: " . $e->getMessage());
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
