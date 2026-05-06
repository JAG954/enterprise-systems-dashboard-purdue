<?php
ini_set('session.save_path', __DIR__ . '/_sessions');
// get_custom_analytics.php
error_reporting(E_ALL);
ini_set('display_errors', 0);

header('Content-Type: application/json');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// -----------------------------------------------------------------------------
// 1) DB connection (same as other get_*.php files)
// -----------------------------------------------------------------------------
try {
    require_once 'dbconnect.php';   // gives you $conn
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error'   => 'DB connection failed',
        'details' => $e->getMessage()
    ]);
    exit;
}

// -----------------------------------------------------------------------------
// 2) Read filters from query string
// -----------------------------------------------------------------------------
$startDate = isset($_GET['startDate']) ? trim($_GET['startDate']) : '';
$endDate   = isset($_GET['endDate'])   ? trim($_GET['endDate'])   : '';
$company   = isset($_GET['company'])   ? trim($_GET['company'])   : '';
$region    = isset($_GET['region'])    ? trim($_GET['region'])    : '';

// helper
function esc($conn, $v) {
    return $conn->real_escape_string($v);
}

// -----------------------------------------------------------------------------
// 3) QUERY 1 – Average delivery delay by region
//     Uses: Shipping, Company, Location (same as get_distributors_by_delay.php)
// -----------------------------------------------------------------------------
$delayByRegion = [];

$shipWhere = [];
// only consider rows where we can compute a delay
$shipWhere[] = "s.ActualDate IS NOT NULL AND s.PromisedDate IS NOT NULL";

if ($startDate !== '') {
    $shipWhere[] = "COALESCE(s.ActualDate, s.PromisedDate) >= '" . esc($conn, $startDate) . "'";
}
if ($endDate !== '') {
    $shipWhere[] = "COALESCE(s.ActualDate, s.PromisedDate) <= '" . esc($conn, $endDate) . "'";
}
if ($company !== '') {
    $shipWhere[] = "c.CompanyName = '" . esc($conn, $company) . "'";
}
if ($region !== '') {
    // region = continent name in your other code
    $shipWhere[] = "l.ContinentName = '" . esc($conn, $region) . "'";
}

$shipWhereSql = '';
if (!empty($shipWhere)) {
    $shipWhereSql = 'WHERE ' . implode(' AND ', $shipWhere);
}

$sql1 = "
    SELECT
        COALESCE(l.ContinentName, 'Unknown') AS Region,
        COUNT(*) AS ShipmentCount,
        AVG(DATEDIFF(s.ActualDate, s.PromisedDate)) AS AvgDelayDays
    FROM Shipping s
    JOIN Company c
        ON s.DistributorID = c.CompanyID
    LEFT JOIN Location l
        ON c.LocationID = l.LocationID
    $shipWhereSql
    GROUP BY COALESCE(l.ContinentName, 'Unknown')
    HAVING ShipmentCount > 0 AND AvgDelayDays IS NOT NULL
    ORDER BY AvgDelayDays DESC
";

$res1 = $conn->query($sql1);
if ($res1) {
    while ($row = $res1->fetch_assoc()) {
        $delayByRegion[] = $row;
    }
} else {
    echo json_encode([
        'success' => false,
        'error'   => 'Error in delayByRegion query: ' . $conn->error
    ]);
    $conn->close();
    exit;
}

// -----------------------------------------------------------------------------
// 4) QUERY 2 – Disruptions by category & impact level
//     Uses: ImpactsCompany, DisruptionEvent, DisruptionCategory, Company, Location
//     (aligned with get_company_disruptions.php)
// -----------------------------------------------------------------------------
$disruptionsByCategory = [];

$eventWhere = [];

if ($startDate !== '') {
    $eventWhere[] = "de.EventDate >= '" . esc($conn, $startDate) . "'";
}
if ($endDate !== '') {
    $eventWhere[] = "de.EventDate <= '" . esc($conn, $endDate) . "'";
}
if ($company !== '') {
    $eventWhere[] = "c.CompanyName = '" . esc($conn, $company) . "'";
}
if ($region !== '') {
    $eventWhere[] = "l.ContinentName = '" . esc($conn, $region) . "'";
}

$eventWhereSql = '';
if (!empty($eventWhere)) {
    $eventWhereSql = 'WHERE ' . implode(' AND ', $eventWhere);
}

$sql2 = "
    SELECT
        dc.CategoryName,
        SUM(CASE WHEN ic.ImpactLevel = 'High'   THEN 1 ELSE 0 END) AS HighImpact,
        SUM(CASE WHEN ic.ImpactLevel = 'Medium' THEN 1 ELSE 0 END) AS MediumImpact,
        SUM(CASE WHEN ic.ImpactLevel = 'Low'    THEN 1 ELSE 0 END) AS LowImpact,
        COUNT(*) AS TotalEvents
    FROM ImpactsCompany ic
    JOIN DisruptionEvent de
        ON ic.EventID = de.EventID
    JOIN Company c
        ON ic.AffectedCompanyID = c.CompanyID
    LEFT JOIN Location l
        ON c.LocationID = l.LocationID
    LEFT JOIN DisruptionCategory dc
        ON de.CategoryID = dc.CategoryID
    $eventWhereSql
    GROUP BY dc.CategoryName
    HAVING TotalEvents > 0
    ORDER BY TotalEvents DESC
    LIMIT 15
";

$res2 = $conn->query($sql2);
if ($res2) {
    while ($row = $res2->fetch_assoc()) {
        $disruptionsByCategory[] = $row;
    }
} else {
    echo json_encode([
        'success' => false,
        'error'   => 'Error in disruptionsByCategory query: ' . $conn->error
    ]);
    $conn->close();
    exit;
}

$conn->close();

echo json_encode([
    'success' => true,
    'data'    => [
        'delayByRegion'         => $delayByRegion,
        'disruptionsByCategory' => $disruptionsByCategory
    ]
]);
