<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
ini_set('session.save_path', __DIR__ . '/_sessions');

header('Content-Type: text/html; charset=UTF-8');

if (!isset($_SESSION['UserID']) || !isset($_SESSION['Role'])) {
    header("Location: index.php?error");
    exit();
}

?>

<!DOCTYPE html>
<html lang='en'>

    <?php
    // html head
    include 'head.php'; 
    ?>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="erp.js"></script>

    

    <body class='erp' data-page='erp'>
        <!-- top of page navbar -->
        <?php include 'navbar.php';?>

        <header>
            <h2>Enterprise Resource Planning - Senior Manager Dashboard</h2>

            <div class="filters">
                <label>Date Range:</label>
                <input type="date" id="startDate">
                <input type="date" id="endDate">

                <label>Company:</label>
                <div class="autocomplete-container">
                    <input type="text" id="companySearch" placeholder="Search company" autocomplete="off" style="width: 200px;">
                    <div id="companyDropdown" class="autocomplete-dropdown"></div>
                </div>

                <label>Region:</label>
                <select id="regionSelect">
                    <option value="">All Regions</option>
                </select>

                <button class="btn btn-success" onclick="applyFilters()">
                    Apply Filters
                    <span id="loadingSpinner" class="spinner" style="display: none;"></span>
                </button>
                <button onclick="clearFilters()" class="btn btn-danger">Clear</button>
            </div>
        </header>

        <!-- sidebar container-->
        <div class="d-flex flex-grow-1">

            <!-- sidebar -->
            <div class="nav flex-column nav-pills me-3 p-3 border-end" id="sidebar-tabs" role="tablist" aria-orientation="vertical" style="min-width: 200px;">

                <button class="nav-link btn-success active" id="tab-1" data-bs-toggle="pill" data-bs-target="#financial-health" type="button" role="tab">
                Financial Health
                </button>

                <button class="nav-link btn-success" id="tab-2" data-bs-toggle="pill" data-bs-target="#regional-disruptions" type="button" role="tab">
                Regional Disruptions
                </button>

                <button class="nav-link btn-success" id="tab-3" data-bs-toggle="pill" data-bs-target="#critical-companies" type="button" role="tab">
                Critical Companies
                </button>

                <button class="nav-link btn-success" id="tab-4" data-bs-toggle="pill" data-bs-target="#disruption-timeline" type="button" role="tab">
                Disruption Timeline
                </button>

                <button class="nav-link btn-success" id="tab-5" data-bs-toggle="pill" data-bs-target="#company-financials" type="button" role="tab">
                Company Financials
                </button>

                <button class="nav-link btn-success" id="tab-6" data-bs-toggle="pill" data-bs-target="#top-distributors" type="button" role="tab">
                Top Distributors
                </button>

                <button class="nav-link btn-success" id="tab-7" data-bs-toggle="pill" data-bs-target="#disruption-details" type="button" role="tab">
                Disruption Details
                </button>

                <button class="nav-link btn-success" id="tab-8" data-bs-toggle="pill" data-bs-target="#add-company" type="button" role="tab">
                Add Company
                </button>

                <button class="nav-link btn-success" id="tab-9" data-bs-toggle="pill" data-bs-target="#custom-analytics" type="button" role="tab">
                Custom Analytics
                </button>

            </div>

            <!-- main content -->
            <div class="tab-content flex-grow-1" id="sidebar-content">

                <!-- financial health -->
                <div class="tab-pane fade show active p-4" id="financial-health" role="tabpanel" aria-labelledby="tab-1">
                        <h3>Average Financial Health by Company</h3>
                        <button class="btn btn-success mb-3" onclick="loadFinancialHealth()">Load Financial Data</button>
                        
                        <!-- Financial Health Cards -->
                        <div class="data-grid">
                            <div class="data-card" style="height: 400px;">
                                <h4>Average Financial Health by Company</h4>
                                <div class="data-card-content">
                                    <canvas id="financialCompanyChart"></canvas>
                                </div>
                            </div>

                            <div class="data-card" style="height: 400px;">
                                <h4>Average Financial Health by Type</h4>
                                <div class="data-card-content">
                                    <canvas id="financialTypeChart"></canvas>
                                </div>
                            </div>
                        </div>
                        
                        <div id="financial-output"></div>
                </div>

                <!-- regional disruptions -->
                <div class="tab-pane fade p-4" id="regional-disruptions" role="tabpanel" aria-labelledby="tab-2">
                        <h3>Regional Disruption Overview</h3>
                        <button class="btn btn-success mb-3" onclick="loadRegionalDisruptions()">Load Regional Data</button>
                        
                        <!-- Regional Disruptions Card -->
                        <div class="data-card" style="height: 500px;">
                            <h4>Disruptions by Region</h4>
                            <div class="data-card-content">
                                <canvas id="regionalChart"></canvas>
                            </div>
                        </div>
                        
                        <div id="regional-output"></div>
                </div>

                <!-- critical companies -->
                <div class="tab-pane fade p-4" id="critical-companies" role="tabpanel" aria-labelledby="tab-3">
                        <h3>Most Critical Companies</h3>
                        <button class="btn btn-success mb-3" onclick="loadCriticalCompanies()">Load Critical Companies</button>
                        <p class="text-muted">Criticality = # Downstream Companies × High Impact Count</p>
                        
                        <div id="critical-output" class="placeholder">
                            Click the button above to load critical companies data.
                        </div>
                </div>

                <!-- disruption timeline -->
                <div class="tab-pane fade p-4" id="disruption-timeline" role="tabpanel" aria-labelledby="tab-4">
                        <h3>Disruption Frequency Over Time</h3>
                        <button class="btn btn-success mb-3" onclick="loadDisruptionTimeline()">Load Timeline</button>
                        
                        <!-- Disruption Timeline Card -->
                        <div class="data-card" style="height: 500px;">
                            <h4>Disruption Frequency Over Time</h4>
                            <div class="data-card-content">
                                <canvas id="timelineChart"></canvas>
                            </div>
                        </div>
                        
                        <div id="timeline-output"></div>
                </div>

                <!-- company financials -->
                <div class="tab-pane fade p-4" id="company-financials" role="tabpanel" aria-labelledby="tab-5">
                        <h3>Company Financials by Region</h3>
                        <div class="mb-3">
                            <label class="form-label">Search Company:</label>
                            <div class="input-group" style="max-width: 500px;">
                                <div class="autocomplete-container w-100">
                                    <input
                                        type="text"
                                        class="form-control"
                                        id="companyFinancialSearch"
                                        placeholder="Enter company name"
                                        autocomplete="off"
                                    >
                                    <div id="companyFinancialDropdown" class="autocomplete-dropdown"></div>
                                </div>
                                <button class="btn btn-success" onclick="loadCompanyFinancials()">Search</button>
                            </div>
                        </div>
                        
                        <div id="company-financial-output" class="placeholder">
                            Enter a company name and click Search to view detailed financial data.
                        </div>
                </div>

                <!-- top distributors -->
                <div class="tab-pane fade p-4" id="top-distributors" role="tabpanel" aria-labelledby="tab-6">
                        <h3>Top Distributors by Shipment Volume</h3>
                        <button class="btn btn-success mb-3" onclick="loadTopDistributors()">Load Distributors</button>
                        
                        <div id="distributors-output" class="placeholder">
                            Click the button above to load top distributors ranked by shipment volume.
                        </div>
                </div>


                <!-- disruption details -->
                <div class="tab-pane fade p-4" id="disruption-details" role="tabpanel" aria-labelledby="tab-7">
                        <h3>Disruption Details</h3>
                        
                        <div class="grid-2">
                            <div class="data-card">
                                <h4>Companies Affected by Disruption Event</h4>
                                <div class="mb-3">
    <label class="form-label">Disruption Event:</label>
    <div class="input-group">
        <select class="form-select" id="eventIdSelect">
            <option value="">Select an event...</option>
        </select>
        <button class="btn btn-sm btn-success" onclick="loadCompaniesAffected()">Search</button>
    </div>
    <small class="text-muted">
        Select an event (Category – Date) to view the affected companies.
    </small>
</div>

                                <div class="data-card-content" id="companies-affected-output">
                                    <div class="text-muted">Enter an event ID to search</div>
                                </div>
                            </div>
                            
                            <div class="data-card">
                                <h4>All Disruptions for Company</h4>
                                <div class="mb-3">
                                    <label class="form-label">Company:</label>
                                    <div class="input-group">
                                        <div class="autocomplete-container w-100">
                                            <input
                                                type="text"
                                                class="form-control"
                                                id="companyDisruptionSearch"
                                                placeholder="Enter company name"
                                                autocomplete="off"
                                            >
                                            <div id="companyDisruptionDropdown" class="autocomplete-dropdown"></div>
                                        </div>
                                        <button class="btn btn-sm btn-success" onclick="loadCompanyDisruptions()">Search</button>
                                    </div>
                                </div>

                                <div class="data-card-content" id="company-disruptions-output">
                                    <div class="text-muted">Enter a company name to search</div>
                                </div>
                            </div>
                        </div>

                        <!-- Distributors by Delay Section -->
                        <h3 class="mt-4">Distributors Sorted by Average Delay</h3>
                        <button class="btn btn-success mb-3" onclick="loadDistributorsByDelay()">Load Data</button>
                        <div id="delay-output" class="placeholder">
                            Click the button above to load distributors sorted by average delivery delay.
                        </div>
                </div>


                <!-- add company -->
                <div class="tab-pane fade p-4" id="add-company" role="tabpanel" aria-labelledby="tab-8">
                    <h3>Add New Company</h3>
                    <div class="row">
                        <div class="col-md-6">
                            <form id="addCompanyForm" onsubmit="return addCompany(event)">
                                <!-- Company Name: SEARCHABLE AUTOCOMPLETE -->
                                <div class="mb-3">
    <label class="form-label">Company Name:</label>
    <input
        type="text"
        class="form-control"
        id="newCompanyName"
        placeholder="Enter new company name"
        autocomplete="off"
    >
</div>

                                <!-- Company Type (unchanged) -->
                                <div class="mb-3">
                                    <label class="form-label">Company Type:</label>
                                    <select class="form-select" id="newCompanyType" required>
                                        <option value="">Select Type</option>
                                        <option value="manufacturer">Manufacturer</option>
                                        <option value="distributor">Distributor</option>
                                        <option value="supplier">Supplier</option>
                                    </select>
                                </div>

                                <!-- Region DROPDOWN (unchanged, still like Company Type) -->
                                <div class="mb-3">
                                    <label class="form-label">Region:</label>
                                    <select class="form-select" id="newCompanyRegion" required>
                                        <option value="">Select Region</option>
                                        <!-- filled by loadFilterOptions() -->
                                    </select>
                                </div>

                                <button type="submit" class="btn btn-success">Add Company</button>
                            </form>
                        </div>
                        <div class="col-md-6">
                            <div id="add-company-output"></div>
                        </div>
                    </div>
                </div>


                <!-- custom analytics -->
                <div class="tab-pane fade p-4" id="custom-analytics" role="tabpanel" aria-labelledby="tab-9">
                        <h3>Custom Analytics</h3>
                        <button class="btn btn-success mb-3" onclick="loadCustomAnalytics()">Load Custom Plots</button>
                        
                        <div class="data-grid">
                            <div class="data-card" style="height: 450px;">
                                <h4>Custom Analysis 1</h4>
                                <div class="data-card-content">
                                    <canvas id="customChart1"></canvas>
                                </div>
                            </div>

                            <div class="data-card" style="height: 450px;">
                                <h4>Custom Analysis 2</h4>
                                <div class="data-card-content">
                                    <canvas id="customChart2"></canvas>
                                </div>
                            </div>
                        </div>
                        
                        <div id="custom-analytics-output"></div>
                </div>
            </div>
        </div>

        <?php include 'footer.php';?>
        
    </body>
</html>