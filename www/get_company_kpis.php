<?php
header('Content-Type: application/json');
ini_set('display_errors', 0);
error_reporting(E_ALL);

try {
    require_once 'dbconnect.php';

    $company = isset($_GET['company']) && $_GET['company'] !== '' ? $_GET['company'] : null;
    
    if (!$company) {
        echo json_encode([
            'success' => false,
            'message' => 'Company name is required'
        ]);
        exit;
    }

    // Get CompanyID
    $sqlID = "SELECT CompanyID FROM Company WHERE CompanyName = ? LIMIT 1";
    $stmtID = $conn->prepare($sqlID);
    $stmtID->bind_param('s', $company);
    $stmtID->execute();
    $stmtID->bind_result($companyID);
    
    if (!$stmtID->fetch()) {
        echo json_encode([
            'success' => false,
            'message' => 'Company not found'
        ]);
        exit;
    }
    $stmtID->close();
    
    // Calculate On-Time Delivery Rate
    $sqlOnTime = "
        SELECT 
            COUNT(*) as TotalShipments,
            SUM(CASE WHEN s.ActualDate <= s.PromisedDate THEN 1 ELSE 0 END) as OnTimeShipments
        FROM Shipping s
        WHERE (s.SourceCompanyID = ? OR s.DestinationCompanyID = ? OR s.DistributorID = ?)
        AND s.ActualDate IS NOT NULL
    ";
    $stmtOnTime = $conn->prepare($sqlOnTime);
    $stmtOnTime->bind_param('iii', $companyID, $companyID, $companyID);
    $stmtOnTime->execute();
    $stmtOnTime->bind_result($totalShipments, $onTimeShipments);
    $stmtOnTime->fetch();
    $stmtOnTime->close();
    
    $onTimeRate = 0;
    if ($totalShipments > 0) {
        $onTimeRate = round(($onTimeShipments / $totalShipments) * 100, 1);
    }
    
    // Calculate Delay Statistics
    $sqlDelay = "
        SELECT 
            AVG(DATEDIFF(s.ActualDate, s.PromisedDate)) as AvgDelay,
            STDDEV(DATEDIFF(s.ActualDate, s.PromisedDate)) as StdDevDelay
        FROM Shipping s
        WHERE (s.SourceCompanyID = ? OR s.DestinationCompanyID = ? OR s.DistributorID = ?)
        AND s.ActualDate IS NOT NULL
        AND s.ActualDate > s.PromisedDate
    ";
    $stmtDelay = $conn->prepare($sqlDelay);
    $stmtDelay->bind_param('iii', $companyID, $companyID, $companyID);
    $stmtDelay->execute();
    $stmtDelay->bind_result($avgDelay, $stdDevDelay);
    $stmtDelay->fetch();
    $stmtDelay->close();
    
    $avgDelay = $avgDelay ? round($avgDelay, 1) : 0;
    $stdDevDelay = $stdDevDelay ? round($stdDevDelay, 1) : 0;
    
    // Get Financial Health Status
    $sqlFinancial = "
        SELECT HealthScore
        FROM FinancialReport
        WHERE CompanyID = ?
        ORDER BY RepYear DESC, FIELD(Quarter, 'Q4', 'Q3', 'Q2', 'Q1')
        LIMIT 1
    ";
    $stmtFinancial = $conn->prepare($sqlFinancial);
    $stmtFinancial->bind_param('i', $companyID);
    $stmtFinancial->execute();
    $stmtFinancial->bind_result($healthScore);
    $stmtFinancial->fetch();
    $stmtFinancial->close();
    
    $financialStatus = $healthScore ? round($healthScore, 2) : 'N/A';
    
    // Get Financial History (last 8 quarters) - FIXED: using bind_result instead of get_result
    $sqlHistory = "
        SELECT 
            CONCAT(RepYear, ' ', Quarter) as Quarter,
            HealthScore
        FROM FinancialReport
        WHERE CompanyID = ?
        ORDER BY RepYear ASC, FIELD(Quarter, 'Q1', 'Q2', 'Q3', 'Q4') ASC
        LIMIT 8
    ";
    $stmtHistory = $conn->prepare($sqlHistory);
    $stmtHistory->bind_param('i', $companyID);
    $stmtHistory->execute();
    $stmtHistory->bind_result($quarterLabel, $score);
    
    $financialHistory = [];
    while ($stmtHistory->fetch()) {
        $financialHistory[] = [
            'Quarter' => $quarterLabel,
            'FinancialScore' => $score
        ];
    }
    $stmtHistory->close();
    
    echo json_encode([
        'success' => true,
        'data' => [
            'onTimeRate' => $onTimeRate,
            'avgDelay' => $avgDelay,
            'stdDevDelay' => $stdDevDelay,
            'financialStatus' => $financialStatus,
            'financialHistory' => $financialHistory
        ]
    ]);

} catch (Exception $e) {
    error_log('Company KPI endpoint error: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Unable to load company KPIs'
    ]);
}

if (isset($conn)) {
    $conn->close();
}
?>
