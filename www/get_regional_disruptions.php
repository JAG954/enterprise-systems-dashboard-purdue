<?php
// Suppress PHP errors from being outputted (they break JSON)
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('session.save_path', __DIR__ . '/_sessions');
header('Content-Type: application/json');
// 
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

    // Build WHERE clause
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

    $whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';

    // Query: Regional Disruption Overview
    $sql = "
        SELECT 
            l.ContinentName AS Region,
            COUNT(DISTINCT de.EventID) AS TotalDisruptions,
            SUM(CASE WHEN ic.ImpactLevel = 'High'   THEN 1 ELSE 0 END) AS HighImpact,
            SUM(CASE WHEN ic.ImpactLevel = 'Medium' THEN 1 ELSE 0 END) AS MediumImpact,
            SUM(CASE WHEN ic.ImpactLevel = 'Low'    THEN 1 ELSE 0 END) AS LowImpact,
            COUNT(DISTINCT c.CompanyID) AS AffectedCompanies
        FROM DisruptionEvent de
        JOIN ImpactsCompany ic    ON de.EventID = ic.EventID
        JOIN Company        c     ON ic.AffectedCompanyID = c.CompanyID
        LEFT JOIN Location  l     ON c.LocationID = l.LocationID
        $whereClause
        GROUP BY l.ContinentName
        ORDER BY TotalDisruptions DESC
    ";

    $result = $conn->query($sql);
    if (!$result) {
        throw new Exception("Query failed: " . $conn->error);
    }

    $data = [];
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }

    echo json_encode([
        'success' => true,
        'data'    => $data
    ]);

} catch (Exception $e) {
    error_log("Regional Disruptions Error: " . $e->getMessage());
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
