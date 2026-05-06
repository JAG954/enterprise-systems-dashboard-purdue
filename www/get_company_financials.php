<?php
// Suppress PHP errors from being outputted (they break JSON)
ini_set('session.save_path', __DIR__ . '/_sessions');
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
    // Get parameters
    $companyName = isset($_GET['companyName']) && $_GET['companyName'] !== '' ? $_GET['companyName'] : null;
    $startDate   = isset($_GET['startDate'])   && $_GET['startDate']   !== '' ? $_GET['startDate']   : null;
    $endDate     = isset($_GET['endDate'])     && $_GET['endDate']     !== '' ? $_GET['endDate']     : null;
    $region      = isset($_GET['region'])      && $_GET['region']      !== '' ? $_GET['region']      : null;

    if (!$companyName) {
        echo json_encode([
            'success' => false,
            'error'   => 'Please provide a company name.'
        ]);
        exit;
    }

    // Escape helper
    $esc = function($str) use ($conn) {
        return $conn->real_escape_string($str);
    };

    // 1) Find the company (ID, type, region)

// try exact match first
$companySql = "
    SELECT 
        c.CompanyID,
        c.CompanyName,
        c.Type AS CompanyType,
        l.ContinentName AS Region
    FROM Company c
    LEFT JOIN Location l ON c.LocationID = l.LocationID
    WHERE c.CompanyName = '" . $esc($companyName) . "'
    LIMIT 1
";

$companyRes = $conn->query($companySql);
if (!$companyRes) {
    throw new Exception("Company lookup failed: " . $conn->error);
}

// if no exact match, fall back to LIKE search
if ($companyRes->num_rows === 0) {
    $companySql = "
        SELECT 
            c.CompanyID,
            c.CompanyName,
            c.Type AS CompanyType,
            l.ContinentName AS Region
        FROM Company c
        LEFT JOIN Location l ON c.LocationID = l.LocationID
        WHERE c.CompanyName LIKE '%" . $esc($companyName) . "%'
        ORDER BY c.CompanyName
        LIMIT 1
    ";

    $companyRes = $conn->query($companySql);
    if (!$companyRes) {
        throw new Exception("Company LIKE lookup failed: " . $conn->error);
    }
}

if ($companyRes->num_rows === 0) {
    echo json_encode([
        'success' => false,
        'error'   => 'Company not found in database.'
    ]);
    exit;
}

$companyRow = $companyRes->fetch_assoc();
$companyId  = (int)$companyRow['CompanyID'];


    // If no region filter was selected in the UI, use the company’s own region
    $regionToUse = $region ?: $companyRow['Region'];

    // Build year-based filters from date range (optional)
    $yearConditions = [];
    if ($startDate) {
        $yearConditions[] = "fr.RepYear >= YEAR('" . $esc($startDate) . "')";
    }
    if ($endDate) {
        $yearConditions[] = "fr.RepYear <= YEAR('" . $esc($endDate) . "')";
    }
    $yearWhere = '';
    if (!empty($yearConditions)) {
        $yearWhere = ' AND ' . implode(' AND ', $yearConditions);
    }

    // 2) Time series for this company
    $timelineSql = "
        SELECT 
            fr.RepYear,
            fr.Quarter,
            AVG(fr.HealthScore) AS AvgHealth,
            COUNT(*) AS RecordCount
        FROM FinancialReport fr
        WHERE fr.CompanyID = {$companyId}
        $yearWhere
        GROUP BY fr.RepYear, fr.Quarter
        ORDER BY fr.RepYear, fr.Quarter
    ";

    $timelineRes = $conn->query($timelineSql);
    if (!$timelineRes) {
        throw new Exception("Company timeline query failed: " . $conn->error);
    }

    $companyTimeline = [];
    while ($row = $timelineRes->fetch_assoc()) {
        $companyTimeline[] = $row;
    }

    // 3) Regional average time series (for comparison)
    $regionConditions = ["1=1"];
    if ($regionToUse) {
        $regionConditions[] = "l.ContinentName = '" . $esc($regionToUse) . "'";
    }
    if ($startDate) {
        $regionConditions[] = "fr.RepYear >= YEAR('" . $esc($startDate) . "')";
    }
    if ($endDate) {
        $regionConditions[] = "fr.RepYear <= YEAR('" . $esc($endDate) . "')";
    }

    $regionWhere = 'WHERE ' . implode(' AND ', $regionConditions);

    $regionSql = "
        SELECT 
            fr.RepYear,
            fr.Quarter,
            AVG(fr.HealthScore) AS AvgHealth,
            COUNT(DISTINCT c.CompanyID) AS CompanyCount
        FROM FinancialReport fr
        JOIN Company c ON fr.CompanyID = c.CompanyID
        LEFT JOIN Location l ON c.LocationID = l.LocationID
        $regionWhere
        GROUP BY fr.RepYear, fr.Quarter
        ORDER BY fr.RepYear, fr.Quarter
    ";

    $regionRes = $conn->query($regionSql);
    if (!$regionRes) {
        throw new Exception("Region timeline query failed: " . $conn->error);
    }

    $regionTimeline = [];
    while ($row = $regionRes->fetch_assoc()) {
        $regionTimeline[] = $row;
    }

    echo json_encode([
        'success' => true,
        'data'    => [
            'company'         => $companyRow,
            'companyTimeline' => $companyTimeline,
            'regionTimeline'  => $regionTimeline,
            'regionUsed'      => $regionToUse
        ]
    ]);

} catch (Exception $e) {
    error_log("Company Financials Error: " . $e->getMessage());
    error_log("SQL Error: " . $conn->error);

    echo json_encode([
        'success'   => false, // $_SESSION['success'] => false; 
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
