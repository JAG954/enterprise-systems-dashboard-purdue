<?php
ini_set('display_errors', 0);
error_reporting(E_ALL);

header('Content-Type: application/json');

$response = ['success' => false, 'message' => 'Unable to load company information'];

try {
    require_once 'dbconnect.php';

    $company = isset($_GET['company']) ? trim($_GET['company']) : '';

    if (empty($company)) {
        $response['message'] = 'No company specified';
        echo json_encode($response);
        exit;
    }

    $comp = $conn->real_escape_string($company);

    $companyQuery = "
        SELECT 
            c.CompanyID,
            c.CompanyName,
            c.Type as CompanyType,
            c.TierLevel,
            CONCAT(COALESCE(l.City, ''), ', ', COALESCE(l.CountryName, ''), ', ', COALESCE(l.ContinentName, '')) as Address
        FROM Company c
        LEFT JOIN Location l ON c.LocationID = l.LocationID
        WHERE c.CompanyName = '{$comp}'
        LIMIT 1
    ";

    $result = @$conn->query($companyQuery);
    
    if (!$result) {
        $response['message'] = 'Company lookup failed';
        echo json_encode($response);
        exit;
    }
    
    $companyData = $result->fetch_assoc();

    if (!$companyData) {
        $response['message'] = 'Company not found: ' . $company;
        echo json_encode($response);
        exit;
    }

    $companyID = $companyData['CompanyID'];

    // Get capacity (for manufacturers)
    $capacity = 'N/A';
    if ($companyData['CompanyType'] === 'Manufacturer') {
        $capacityQuery = "SELECT m.FactoryCapacity FROM Manufacturer m WHERE m.CompanyID = {$companyID}";
        $capResult = @$conn->query($capacityQuery);
        if ($capResult) {
            $capRow = $capResult->fetch_assoc();
            if ($capRow && !empty($capRow['FactoryCapacity'])) {
                $capacity = number_format($capRow['FactoryCapacity']) . ' units/day';
            }
        }
    }

    // Get routes operated (for distributors)
    $routesOperated = 'N/A';
    if ($companyData['CompanyType'] === 'Distributor') {
        $routesQuery = "SELECT COUNT(DISTINCT CONCAT(s.SourceCompanyID, '-', s.DestinationCompanyID)) as RouteCount FROM Shipping s WHERE s.DistributorID = {$companyID}";
        $routesResult = @$conn->query($routesQuery);
        if ($routesResult) {
            $routesRow = $routesResult->fetch_assoc();
            if ($routesRow && $routesRow['RouteCount'] > 0) {
                $routesOperated = $routesRow['RouteCount'] . ' routes';
            }
        }
    }

    // Get product diversity
    $productDiversity = 'N/A';
    $productsQuery = "SELECT GROUP_CONCAT(DISTINCT p.Category SEPARATOR ', ') as Categories FROM SuppliesProduct sp JOIN Product p ON sp.ProductID = p.ProductID WHERE sp.SupplierID = {$companyID}";
    $prodResult = @$conn->query($productsQuery);
    if ($prodResult) {
        $prodRow = $prodResult->fetch_assoc();
        if ($prodRow && !empty($prodRow['Categories'])) {
            $productDiversity = $prodRow['Categories'];
        }
    }

    // ============ NEW FIELDS START HERE ============
    
    // Get suppliers (companies that ship TO this company) - "Depends On"
    $dependsOn = '-';
    $suppliersQuery = "
        SELECT DISTINCT c.CompanyName
        FROM Shipping s
        JOIN Company c ON s.SourceCompanyID = c.CompanyID
        WHERE s.DestinationCompanyID = {$companyID}
        ORDER BY c.CompanyName
        LIMIT 10
    ";
    $suppliersResult = @$conn->query($suppliersQuery);
    if ($suppliersResult && $suppliersResult->num_rows > 0) {
        $suppliers = [];
        while ($row = $suppliersResult->fetch_assoc()) {
            $suppliers[] = $row['CompanyName'];
        }
        $dependsOn = implode("\n", $suppliers);
    }
    
    // Get customers (companies that this company ships TO) - "Dependencies"
    $dependencies = '-';
    $customersQuery = "
        SELECT DISTINCT c.CompanyName
        FROM Shipping s
        JOIN Company c ON s.DestinationCompanyID = c.CompanyID
        WHERE s.SourceCompanyID = {$companyID}
        ORDER BY c.CompanyName
        LIMIT 10
    ";
    $customersResult = @$conn->query($customersQuery);
    if ($customersResult && $customersResult->num_rows > 0) {
        $customers = [];
        while ($row = $customersResult->fetch_assoc()) {
            $customers[] = $row['CompanyName'];
        }
        $dependencies = implode("\n", $customers);
    }
    
    // Get products supplied (products this company ships)
    $productsSupplied = '-';
    $productsSuppQuery = "
        SELECT DISTINCT p.ProductName
        FROM Shipping s
        JOIN Product p ON s.ProductID = p.ProductID
        WHERE s.SourceCompanyID = {$companyID}
        ORDER BY p.ProductName
        LIMIT 10
    ";
    $productsSuppResult = @$conn->query($productsSuppQuery);
    if ($productsSuppResult && $productsSuppResult->num_rows > 0) {
        $products = [];
        while ($row = $productsSuppResult->fetch_assoc()) {
            $products[] = $row['ProductName'];
        }
        $productsSupplied = implode("\n", $products);
    }
    
    // Get most recent financial status
    $financialStatus = '-';
    $financialQuery = "
        SELECT 
            HealthScore,
            Quarter,
            RepYear
        FROM FinancialReport
        WHERE CompanyID = {$companyID}
        ORDER BY RepYear DESC, 
                 FIELD(Quarter, 'Q4', 'Q3', 'Q2', 'Q1') ASC
        LIMIT 1
    ";
    $financialResult = @$conn->query($financialQuery);
    if ($financialResult && $financialResult->num_rows > 0) {
        $financial = $financialResult->fetch_assoc();
        $financialStatus = number_format($financial['HealthScore'], 2) . ' (' . $financial['Quarter'] . ' ' . $financial['RepYear'] . ')';
    }
    
    // ============ NEW FIELDS END HERE ============

    // Clean up the address
    $address = isset($companyData['Address']) ? $companyData['Address'] : 'N/A';
    $address = preg_replace('/,\s*,/', ',', $address);
    $address = trim($address, ', ');
    if (empty($address)) {
        $address = 'N/A';
    }

    // Success - now includes NEW fields
    $response = [
        'success' => true,
        'data' => [
            'CompanyName' => $companyData['CompanyName'],
            'Address' => $address,
            'CompanyType' => $companyData['CompanyType'],
            'TierLevel' => isset($companyData['TierLevel']) ? $companyData['TierLevel'] : 'N/A',
            'Capacity' => $capacity,
            'RoutesOperated' => $routesOperated,
            'ProductDiversity' => $productDiversity,
            // NEW FIELDS
            'FinancialStatus' => $financialStatus,
            'DependsOn' => $dependsOn,
            'Dependencies' => $dependencies,
            'ProductsSupplied' => $productsSupplied
        ]
    ];

    echo json_encode($response);
    $conn->close();

} catch (Exception $e) {
    error_log('Company info endpoint error: ' . $e->getMessage());
    $response['success'] = false;
    $response['message'] = 'Unexpected error loading company information';
    echo json_encode($response);
}
?>
