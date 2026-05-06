<?php
error_reporting(E_ALL);
ini_set('display_errors', 0);

header('Content-Type: application/json');
ini_set('session.save_path', __DIR__ . '/_sessions');
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


include 'dbconnect.php';

try {
    // Get filter parameters
    $startDate = isset($_GET['startDate']) && $_GET['startDate'] !== '' ? $_GET['startDate'] : null;
    $endDate = isset($_GET['endDate']) && $_GET['endDate'] !== '' ? $_GET['endDate'] : null;
    $company = isset($_GET['company']) && $_GET['company'] !== '' ? $_GET['company'] : null;
    $region = isset($_GET['region']) && $_GET['region'] !== '' ? $_GET['region'] : null;

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

    // 1. Disruption Frequency by Company (Top 10)
    $sqlDF = "
        SELECT 
            c.CompanyName,
            COUNT(DISTINCT de.EventID) as DisruptionCount
        FROM DisruptionEvent de
        JOIN ImpactsCompany ic ON de.EventID = ic.EventID
        JOIN Company c ON ic.AffectedCompanyID = c.CompanyID
        " . ($region || $company ? "LEFT JOIN Location l ON c.LocationID = l.LocationID" : "") . "
        $whereClause
        GROUP BY c.CompanyID, c.CompanyName
        ORDER BY DisruptionCount DESC
        LIMIT 10
    ";

    $resultDF = $conn->query($sqlDF);
    if (!$resultDF) {
        throw new Exception("DF Query failed: " . $conn->error);
    }
    
    $disruptionFrequency = [];
    while ($row = $resultDF->fetch_assoc()) {
        $disruptionFrequency[] = $row;
    }

    // 2. Average Recovery Time Distribution
    $artWhereClause = $whereClause;
    if (empty($artWhereClause)) {
        $artWhereClause = "WHERE de.EventRecoveryDate IS NOT NULL";
    } else {
        $artWhereClause .= " AND de.EventRecoveryDate IS NOT NULL";
    }
    
    $sqlART = "
        SELECT 
            DATEDIFF(de.EventRecoveryDate, de.EventDate) as RecoveryDays
        FROM DisruptionEvent de
        " . ($region || $company ? "JOIN ImpactsCompany ic ON de.EventID = ic.EventID JOIN Company c ON ic.AffectedCompanyID = c.CompanyID LEFT JOIN Location l ON c.LocationID = l.LocationID" : "") . "
        $artWhereClause
    ";

    $resultART = $conn->query($sqlART);
    if (!$resultART) {
        throw new Exception("ART Query failed: " . $conn->error);
    }
    
    $recoveryTimes = [];
    while ($row = $resultART->fetch_assoc()) {
        $recoveryTimes[] = intval($row['RecoveryDays']);
    }

    // 3. High-Impact Disruption Rate by Company
    $sqlHDR = "
        SELECT 
            c.CompanyName,
            COUNT(DISTINCT CASE WHEN ic.ImpactLevel = 'High' THEN de.EventID END) as HighImpactCount,
            COUNT(DISTINCT de.EventID) as TotalCount,
            ROUND((COUNT(DISTINCT CASE WHEN ic.ImpactLevel = 'High' THEN de.EventID END) * 100.0 / 
                   COUNT(DISTINCT de.EventID)), 1) as HighImpactRate
        FROM DisruptionEvent de
        JOIN ImpactsCompany ic ON de.EventID = ic.EventID
        JOIN Company c ON ic.AffectedCompanyID = c.CompanyID
        " . ($region || $company ? "LEFT JOIN Location l ON c.LocationID = l.LocationID" : "") . "
        $whereClause
        GROUP BY c.CompanyID, c.CompanyName
        HAVING TotalCount >= 3
        ORDER BY HighImpactRate DESC
        LIMIT 10
    ";

    $resultHDR = $conn->query($sqlHDR);
    if (!$resultHDR) {
        throw new Exception("HDR Query failed: " . $conn->error);
    }
    
    $highImpactRate = [];
    while ($row = $resultHDR->fetch_assoc()) {
        $highImpactRate[] = $row;
    }

    // 4. Total Downtime Distribution
    $sqlTD = "
        SELECT 
            c.CompanyName,
            SUM(DATEDIFF(IFNULL(de.EventRecoveryDate, CURDATE()), de.EventDate)) as TotalDowntime
        FROM DisruptionEvent de
        JOIN ImpactsCompany ic ON de.EventID = ic.EventID
        JOIN Company c ON ic.AffectedCompanyID = c.CompanyID
        " . ($region || $company ? "LEFT JOIN Location l ON c.LocationID = l.LocationID" : "") . "
        $whereClause
        GROUP BY c.CompanyID, c.CompanyName
        HAVING TotalDowntime > 0
    ";

    $resultTD = $conn->query($sqlTD);
    if (!$resultTD) {
        throw new Exception("TD Query failed: " . $conn->error);
    }
    
    $downtimes = [];
    while ($row = $resultTD->fetch_assoc()) {
        $downtimes[] = intval($row['TotalDowntime']);
    }

    // 5. Regional Risk Concentration - Using Company's Location
    $sqlRRC = "
        SELECT 
            l.ContinentName as Region,
            COUNT(DISTINCT de.EventID) as DisruptionCount,
            ROUND((COUNT(DISTINCT de.EventID) * 100.0 / 
                   (SELECT COUNT(DISTINCT EventID) FROM DisruptionEvent)), 1) as RiskPercentage
        FROM DisruptionEvent de
        JOIN ImpactsCompany ic ON de.EventID = ic.EventID
        JOIN Company c ON ic.AffectedCompanyID = c.CompanyID
        LEFT JOIN Location l ON c.LocationID = l.LocationID
        $whereClause
        GROUP BY l.ContinentName
        ORDER BY DisruptionCount DESC
    ";

    $resultRRC = $conn->query($sqlRRC);
    if (!$resultRRC) {
        throw new Exception("RRC Query failed: " . $conn->error);
    }
    
    $regionalRisk = [];
    while ($row = $resultRRC->fetch_assoc()) {
        $regionalRisk[] = $row;
    }

    // 6. Disruption Severity Distribution by Company
    $sqlDSD = "
        SELECT 
            c.CompanyName,
            COUNT(DISTINCT CASE WHEN ic.ImpactLevel = 'High' THEN de.EventID END) as HighCount,
            COUNT(DISTINCT CASE WHEN ic.ImpactLevel = 'Medium' THEN de.EventID END) as MediumCount,
            COUNT(DISTINCT CASE WHEN ic.ImpactLevel = 'Low' THEN de.EventID END) as LowCount
        FROM DisruptionEvent de
        JOIN ImpactsCompany ic ON de.EventID = ic.EventID
        JOIN Company c ON ic.AffectedCompanyID = c.CompanyID
        " . ($region || $company ? "LEFT JOIN Location l ON c.LocationID = l.LocationID" : "") . "
        $whereClause
        GROUP BY c.CompanyID, c.CompanyName
        HAVING (COUNT(DISTINCT CASE WHEN ic.ImpactLevel = 'High' THEN de.EventID END) + 
                COUNT(DISTINCT CASE WHEN ic.ImpactLevel = 'Medium' THEN de.EventID END) + 
                COUNT(DISTINCT CASE WHEN ic.ImpactLevel = 'Low' THEN de.EventID END)) > 0
        ORDER BY (COUNT(DISTINCT CASE WHEN ic.ImpactLevel = 'High' THEN de.EventID END) + 
                  COUNT(DISTINCT CASE WHEN ic.ImpactLevel = 'Medium' THEN de.EventID END) + 
                  COUNT(DISTINCT CASE WHEN ic.ImpactLevel = 'Low' THEN de.EventID END)) DESC
        LIMIT 10
    ";

    $resultDSD = $conn->query($sqlDSD);
    if (!$resultDSD) {
        throw new Exception("DSD Query failed: " . $conn->error);
    }
    
    $severityDistribution = [];
    while ($row = $resultDSD->fetch_assoc()) {
        $severityDistribution[] = $row;
    }

    // Recent disruptions alert
    $sqlRecent = "
        SELECT COUNT(DISTINCT EventID) as RecentCount
        FROM DisruptionEvent
        WHERE EventDate >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
    ";
    $resultRecent = $conn->query($sqlRecent);
    if (!$resultRecent) {
        throw new Exception("Recent Query failed: " . $conn->error);
    }
    
    $recentRow = $resultRecent->fetch_assoc();
    $recentDisruptions = intval($recentRow['RecentCount']);

    $sqlOngoing = "
        SELECT COUNT(DISTINCT EventID) as OngoingCount
        FROM DisruptionEvent
        WHERE EventRecoveryDate IS NULL OR EventRecoveryDate > CURDATE()
    ";
    $resultOngoing = $conn->query($sqlOngoing);
    if (!$resultOngoing) {
        throw new Exception("Ongoing Query failed: " . $conn->error);
    }
    
    $ongoingRow = $resultOngoing->fetch_assoc();
    $ongoingDisruptions = intval($ongoingRow['OngoingCount']);

    // Return all data
    echo json_encode([
        'success' => true,
        'data' => [
            'disruptionFrequency' => $disruptionFrequency,
            'recoveryTimes' => $recoveryTimes,
            'highImpactRate' => $highImpactRate,
            'downtimes' => $downtimes,
            'regionalRisk' => $regionalRisk,
            'severityDistribution' => $severityDistribution,
            'alert' => [
                'recent' => $recentDisruptions,
                'ongoing' => $ongoingDisruptions
            ]
        ]
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);
}

$conn->close();
?>