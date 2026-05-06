<?php
/**
 * get_companies_affected.php
 * Returns all companies affected by a specific disruption event
 * 
 * CRITICAL: This file must ONLY output JSON
 */

// Prevent any output before JSON
//ob_start();
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('session.save_path', __DIR__ . '/_sessions');
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// Set JSON header FIRST
header('Content-Type: application/json; charset=utf-8');

// Clean any previous output
//ob_clean();

try {
    // Include database connection
    require_once 'dbconnect.php';
    
    // Check database connection
    if (!isset($conn) || !($conn instanceof mysqli)) {
        throw new Exception('Database connection failed');
    }
    
    if ($conn->connect_error) {
        throw new Exception('Database connection error: ' . $conn->connect_error);
    }
    
    // Get event ID from query string
    $eventId = isset($_GET['eventId']) ? trim($_GET['eventId']) : '';
    
    if (empty($eventId) || !is_numeric($eventId)) {
        ob_end_clean();
        echo json_encode([
            'success' => false,
            'error'   => 'Valid Event ID is required'
        ]);
        exit;
    }
    
    $eventIdEsc = (int)$eventId;
    
    // First, get the event details
    $eventSql = "
        SELECT 
            e.EventID,
            e.EventDate,
            e.EventRecoveryDate,
            ec.CategoryName,
            COUNT(DISTINCT ei.CompanyID) AS AffectedCount
        FROM DisruptionEvent e
        LEFT JOIN EventCategory ec ON e.CategoryID = ec.CategoryID
        LEFT JOIN EventImpact ei ON e.EventID = ei.EventID
        WHERE e.EventID = {$eventIdEsc}
        GROUP BY e.EventID, e.EventDate, e.EventRecoveryDate, ec.CategoryName
    ";
    
    $eventResult = $conn->query($eventSql);
    
    if (!$eventResult) {
        throw new Exception('Event query failed: ' . $conn->error);
    }
    
    if ($eventResult->num_rows === 0) {
        ob_end_clean();
        echo json_encode([
            'success' => false,
            'error'   => 'Event not found in database'
        ]);
        exit;
    }
    
    $event = $eventResult->fetch_assoc();
    
    // Now get all companies affected by this event
    $companiesSql = "
        SELECT 
            c.CompanyID,
            c.CompanyName,
            c.CompanyType,
            c.City,
            co.CountryName,
            cont.ContinentName,
            ei.ImpactLevel
        FROM EventImpact ei
        INNER JOIN Company c ON ei.CompanyID = c.CompanyID
        LEFT JOIN Country co ON c.CountryID = co.CountryID
        LEFT JOIN Continent cont ON co.ContinentID = cont.ContinentID
        WHERE ei.EventID = {$eventIdEsc}
        ORDER BY 
            CASE ei.ImpactLevel
                WHEN 'high' THEN 1
                WHEN 'medium' THEN 2
                WHEN 'low' THEN 3
                ELSE 4
            END,
            c.CompanyName
    ";
    
    $companiesResult = $conn->query($companiesSql);
    
    if (!$companiesResult) {
        throw new Exception('Companies query failed: ' . $conn->error);
    }
    
    $companies = [];
    while ($row = $companiesResult->fetch_assoc()) {
        $companies[] = [
            'CompanyID'     => $row['CompanyID'],
            'CompanyName'   => $row['CompanyName'],
            'CompanyType'   => $row['CompanyType'] ?? 'Unknown',
            'City'          => $row['City'] ?? '',
            'CountryName'   => $row['CountryName'] ?? '',
            'ContinentName' => $row['ContinentName'] ?? '',
            'ImpactLevel'   => ucfirst($row['ImpactLevel'] ?? 'unknown')
        ];
    }
    
    // Clean output buffer and send JSON
    ob_end_clean();
    echo json_encode([
        'success'   => true,
        'event'     => $event,
        'companies' => $companies,
        'count'     => count($companies)
    ], JSON_PRETTY_PRINT);
    
} catch (Throwable $e) {
    // Log error for debugging
    error_log('get_companies_affected.php error: ' . $e->getMessage());
    
    // Clean output buffer
    ob_end_clean();
    
    // Return error JSON
    echo json_encode([
        'success' => false,
        'error'   => 'An error occurred while fetching affected companies',
        'details' => $e->getMessage()
    ]);
}

// Close database connection
if (isset($conn) && $conn instanceof mysqli) {
    $conn->close();
}

// Flush output
ob_end_flush();
?>