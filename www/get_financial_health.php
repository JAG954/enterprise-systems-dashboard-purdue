<?php
// Suppress PHP errors from being outputted (they break JSON)
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('session.save_path', __DIR__ . '/_sessions');
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
        'error' => 'Database connection failed',
        'details' => $e->getMessage()
    ]);
    exit;
}

try {
    // Get filter parameters
    $startDate = isset($_GET['startDate']) && $_GET['startDate'] !== '' ? $_GET['startDate'] : null;
    $endDate = isset($_GET['endDate']) && $_GET['endDate'] !== '' ? $_GET['endDate'] : null;
    $company = isset($_GET['company']) && $_GET['company'] !== '' ? $_GET['company'] : null;
    $region = isset($_GET['region']) && $_GET['region'] !== '' ? $_GET['region'] : null;

    // Build WHERE clause
    $whereConditions = [];
    
    if ($startDate) {
        $startYear = date('Y', strtotime($startDate));
        $whereConditions[] = "fr.RepYear >= " . intval($startYear);
    }
    if ($endDate) {
        $endYear = date('Y', strtotime($endDate));
        $whereConditions[] = "fr.RepYear <= " . intval($endYear);
    }
    if ($company) {
        $whereConditions[] = "c.CompanyName = '" . $conn->real_escape_string($company) . "'";
    }
    if ($region) {
        $whereConditions[] = "l.ContinentName = '" . $conn->real_escape_string($region) . "'";
    }

    $whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';

    // Query 1: Average Financial Health by Company
    $sqlByCompany = "
        SELECT 
            c.CompanyName,
            c.Type AS CompanyType,
            AVG(fr.HealthScore) AS AvgHealth,
            COUNT(*) AS RecordCount
        FROM FinancialReport fr
        JOIN Company c ON fr.CompanyID = c.CompanyID
        LEFT JOIN Location l ON c.LocationID = l.LocationID
        $whereClause
        GROUP BY c.CompanyID, c.CompanyName, c.Type
        ORDER BY AvgHealth DESC
        LIMIT 10
    ";

    $resultByCompany = $conn->query($sqlByCompany);
    if (!$resultByCompany) {
        throw new Exception("Query 1 failed: " . $conn->error);
    }
    
    $byCompany = [];
    while ($row = $resultByCompany->fetch_assoc()) {
        $byCompany[] = $row;
    }

    // Query 2: Average Financial Health by Company Type
    $sqlByType = "
        SELECT 
            c.Type AS CompanyType,
            AVG(fr.HealthScore) AS AvgHealth,
            COUNT(DISTINCT c.CompanyID) AS CompanyCount
        FROM FinancialReport fr
        JOIN Company c ON fr.CompanyID = c.CompanyID
        LEFT JOIN Location l ON c.LocationID = l.LocationID
        $whereClause
        GROUP BY c.Type
        ORDER BY AvgHealth DESC
    ";

    $resultByType = $conn->query($sqlByType);
    if (!$resultByType) {
        throw new Exception("Query 2 failed: " . $conn->error);
    }
    
    $byType = [];
    while ($row = $resultByType->fetch_assoc()) {
        $byType[] = $row;
    }

    // Query 3: Financial Health Trend by Quarter
    $sqlTrend = "
        SELECT 
            CONCAT(fr.RepYear, '-', fr.Quarter) AS Period,
            fr.RepYear,
            fr.Quarter,
            AVG(fr.HealthScore) AS AvgHealth
        FROM FinancialReport fr
        JOIN Company c ON fr.CompanyID = c.CompanyID
        LEFT JOIN Location l ON c.LocationID = l.LocationID
        $whereClause
        GROUP BY fr.RepYear, fr.Quarter
        ORDER BY fr.RepYear DESC, 
                 FIELD(fr.Quarter, 'Q1', 'Q2', 'Q3', 'Q4') DESC
        LIMIT 12
    ";

    $resultTrend = $conn->query($sqlTrend);
    if (!$resultTrend) {
        throw new Exception("Query 3 failed: " . $conn->error);
    }
    
    $trend = [];
    while ($row = $resultTrend->fetch_assoc()) {
        $trend[] = $row;
    }

    // Return success response
    echo json_encode([
        'success' => true,
        'data' => [
            'byCompany' => $byCompany,
            'byType' => $byType,
            'trend' => array_reverse($trend)
        ]
    ]);

} catch (Exception $e) {
    // Log the full error for debugging
    error_log("Financial Health Error: " . $e->getMessage());
    error_log("SQL Error: " . $conn->error);
    
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'sql_error' => $conn->error,
        'debug' => [
            'file' => __FILE__,
            'line' => $e->getLine()
        ]
    ]);
}

if (isset($conn)) {
    $conn->close();
}
?>