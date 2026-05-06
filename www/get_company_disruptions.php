<?php
// get_company_disruptions.php

ini_set('session.save_path', __DIR__ . '/_sessions');
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
error_reporting(E_ALL);
ini_set('display_errors', 0);

header('Content-Type: application/json');



require_once 'dbconnect.php';

$response = [
    'success'     => false,
    'error'       => null,
    'company'     => null,
    'disruptions' => []
];

try {
    if (!isset($_SESSION['UserID'])) {
        throw new Exception('Not logged in.');
    }

    if (!isset($_GET['companyName']) || trim($_GET['companyName']) === '') {
        throw new Exception('Company name is required.');
    }

    $companyName = trim($_GET['companyName']);

    // ---------- 1) Find the company ----------
    // Try exact match first
    $sqlCompanyExact = "
        SELECT
            CompanyID,
            CompanyName,
            Type AS CompanyType,
            TierLevel,
            LocationID,
            NULL AS City,
            NULL AS CountryName,
            NULL AS ContinentName
        FROM Company
        WHERE CompanyName = ?
        LIMIT 1
    ";

    $stmt = $conn->prepare($sqlCompanyExact);
    if (!$stmt) {
        throw new Exception('Prepare failed (company exact): ' . $conn->error);
    }
    $stmt->bind_param('s', $companyName);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($res->num_rows === 0) {
        // Fallback: partial match
        $like = '%' . $companyName . '%';
        $sqlCompanyLike = "
            SELECT
                CompanyID,
                CompanyName,
                Type AS CompanyType,
                TierLevel,
                LocationID,
                NULL AS City,
                NULL AS CountryName,
                NULL AS ContinentName
            FROM Company
            WHERE CompanyName LIKE ?
            ORDER BY CompanyName
            LIMIT 1
        ";
        $stmt2 = $conn->prepare($sqlCompanyLike);
        if (!$stmt2) {
            throw new Exception('Prepare failed (company like): ' . $conn->error);
        }
        $stmt2->bind_param('s', $like);
        $stmt2->execute();
        $res = $stmt2->get_result();
    }

    if ($res->num_rows === 0) {
        throw new Exception('Company not found in database. Please check the spelling or try searching for a partial name.');
    }

    $company = $res->fetch_assoc();

    // ---------- 2) Get disruptions for that company ----------
    $sqlDisruptions = "
        SELECT
            de.EventID,
            dc.CategoryName,
            de.EventDate,
            de.EventRecoveryDate,
            DATEDIFF(de.EventRecoveryDate, de.EventDate) AS DurationDays,
            ic.ImpactLevel
        FROM ImpactsCompany AS ic
        JOIN DisruptionEvent AS de
            ON ic.EventID = de.EventID
        LEFT JOIN DisruptionCategory AS dc
            ON de.CategoryID = dc.CategoryID
        WHERE ic.AffectedCompanyID = ?
        ORDER BY de.EventDate
    ";

    $stmt3 = $conn->prepare($sqlDisruptions);
    if (!$stmt3) {
        throw new Exception('Prepare failed (disruptions query): ' . $conn->error);
    }
    $stmt3->bind_param('i', $company['CompanyID']);
    $stmt3->execute();
    $res2 = $stmt3->get_result();

    $rows = [];
    while ($row = $res2->fetch_assoc()) {
        $rows[] = $row;
    }

    $response['success']     = true;
    $response['company']     = $company;
    $response['disruptions'] = $rows;

    echo json_encode($response);
} catch (Throwable $e) {
    $response['success'] = false;
    $response['error']   = $e->getMessage();
    echo json_encode($response);
}
