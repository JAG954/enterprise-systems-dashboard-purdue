<?php 

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// login fail / login page defs
$loginFailed = (isset($_GET['login']) && $_GET['login'] == 'failed');
$LoginPage   = (!isset($_SESSION['UserID']) || $loginFailed);

// If not logged in, redirect
if (!isset($_SESSION['UserID'])) {
    header("Location: index.php?error=notloggedin");
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


    <body class='scm' data-page='scm'>
        <!-- top of page navbar -->
        <?php include 'navbar.php';?>


        <!-- BODY CONTENT -->
        <header>
            <h2>Supply Chain Management Dashboard</h2>

            <div class="filters" id="filters-container">
                <label id="label-daterange">Date Range:</label>
                <input type="date" id="startDate" style="padding: 8px;">
                <input type="date" id="endDate" style="padding: 8px;">

                <label>Company:</label>
                <div class="autocomplete-container">
                    <input type="text" id="companySearch" placeholder="Search company" autocomplete="off" style="width: 200px; padding: 8px;">
                    <div id="companyDropdown" class="autocomplete-dropdown"></div>
                </div>

                <label id="label-region">Region:</label>
                <select id="regionSelect" style="padding: 8px; width: 150px;">
                    <option value="">All</option>
                </select>

                <button class="btn btn-primary" onclick="applyFilters()" style="padding: 8px 16px;">
                    Apply
                    <span id="loadingSpinner" class="spinner" style="display: none;"></span>
                </button>
                <button onclick="clearFilters()" class="btn-clear">Clear</button>
            </div>
        </header>

        <div class="layout">
            <div class="d-flex flex-grow-1">

                <div class="nav flex-column nav-pills me-3 p-3 border-end" id="sidebar-tabs" role="tablist" aria-orientation="vertical" style="min-width: 200px;">
                    <button class="nav-link active" id="tab-1" data-bs-toggle="pill" data-bs-target="#company-info" type="button" role="tab">
                      Company Info
                    </button>

                    <button class="nav-link" id="tab-3" data-bs-toggle="pill" data-bs-target="#disruptions" type="button" role="tab">
                      Disruptions
                    </button>

                    <button class="nav-link" id="tab-4" data-bs-toggle="pill" data-bs-target="#transactions" type="button" role="tab">
                      Transactions
                    </button>

                </div>

                <div class="tab-content flex-grow-1" id="sidebar-content">

                    <div class="tab-pane fade show active p-4" id="company-info" role="tabpanel" aria-labelledby="tab-1">
                        <h3>Company Information</h3>

                        <!-- Three Column Layout -->
                        <div class="three-column-layout">
                            <!-- Column 1: Company Information -->
                            <div class="info-column">
                                <h4>Company Information</h4>
                                <div class="info-column-content">
                                    <div class="info-item">
                                        <div class="info-item-label">Company Name</div>
                                        <div class="info-item-value" id="company-name-display">Select a company</div>
                                    </div>
                                    <div class="info-item">
                                        <div class="info-item-label">Address</div>
                                        <div class="info-item-value" id="company-address">-</div>
                                    </div>
                                    <div class="info-item">
                                        <div class="info-item-label">Company Type</div>
                                        <div class="info-item-value" id="company-type">-</div>
                                    </div>
                                    <div class="info-item">
                                        <div class="info-item-label">Tier Level</div>
                                        <div class="info-item-value" id="tier-level">-</div>
                                    </div>
                                    <div class="info-item">
                                        <div class="info-item-label">Most Recent Financial Status</div>
                                        <div class="info-item-value" id="financial-status">-</div>
                                    </div>
                                    <div class="info-item">
                                        <div class="info-item-label">Depends On</div>
                                        <div class="info-item-value" id="depends-on" style="white-space: pre-line;">-</div>
                                    </div>
                                    <div class="info-item">
                                        <div class="info-item-label">Dependencies</div>
                                        <div class="info-item-value" id="dependencies" style="white-space: pre-line;">-</div>
                                    </div>
                                    <div class="info-item">
                                        <div class="info-item-label">Capacity</div>
                                        <div class="info-item-value" id="capacity">-</div>
                                    </div>
                                    <div class="info-item">
                                        <div class="info-item-label">Routes Operated</div>
                                        <div class="info-item-value" id="routes-operated">-</div>
                                    </div>
                                    <div class="info-item">
                                        <div class="info-item-label">Products Supplied</div>
                                        <div class="info-item-value" id="products-supplied" style="white-space: pre-line;">-</div>
                                    </div>
                                    <div class="info-item">
                                        <div class="info-item-label">Product Diversity</div>
                                        <div class="info-item-value" id="product-diversity">-</div>
                                    </div>
                                </div>
                            </div>

                            <!-- Column 2: Key Performance Indicators -->
                            <div class="info-column">
                                <h4>Key Performance Indicators</h4>
                                <div class="info-column-content">
                                    <!-- On-Time Delivery Rate -->
                                    <div style="margin-bottom: 25px;">
                                        <div style="font-size: 0.9em; color: #666; margin-bottom: 5px;">On-Time Delivery Rate</div>
                                        <div style="font-size: 2.5em; font-weight: 700; color: #007bff;" id="kpi-delivery-rate">-%</div>
                                        <div style="font-size: 0.85em; color: #888; margin-top: 5px;">Shipments where delivery date =< promised date</div>
                                    </div>

                                    <!-- Delay Statistics -->
                                    <div style="margin-bottom: 25px;">
                                        <div style="font-size: 0.9em; color: #666; margin-bottom: 10px;">Delay Statistics</div>
                                        <div style="font-size: 0.95em; color: #333;">
                                            Average Delay: <strong id="kpi-avg-delay">-</strong> days<br>
                                            Standard Deviation of Delay: <strong id="kpi-std-delay">-</strong> days
                                        </div>
                                    </div>

                                    <!-- Financial Health Score -->
                                    <div style="margin-bottom: 15px;">
                                        <div style="font-size: 0.9em; color: #666; margin-bottom: 5px;">Financial Health Over Time</div>
                                    </div>

                                    <!-- Financial Chart Placeholder -->
                                    <div style="margin-top: 20px;">
                                        <canvas id="financialChart" style="max-height: 200px;"></canvas>
                                    </div>
                                </div>
                            </div>

                            <!-- Column 3: Transactions -->
                            <div class="info-column">
                                <h4>Recent Transactions</h4>
                                <div class="info-column-content" id="recent-transactions-list">
                                    <p style="text-align: center; color: #666; padding: 20px;">Select a company to view transactions</p>
                                </div>
                            </div>
                        </div>

                        <!-- NEW: Disruption Events Section -->
                        <div style="margin-top: 30px;">
                            <div class="info-column" style="max-width: 100%;">
                                <h4>Disruption Events Affecting This Company</h4>
                                <div class="info-column-content" id="company-disruption-events-list" style="max-height: 400px; overflow-y: auto;">
                                    <p style="text-align: center; color: #666; padding: 20px;">Select a company to view disruption events</p>
                                </div>
                            </div>
                        </div>

                    </div>

                    <div class="tab-pane fade p-4" id="disruptions" role="tabpanel" aria-labelledby="tab-3">
                        <!-- Alert Banner -->
                        <div id="disruption-alert" style="background-color: #fff3cd; border-left: 4px solid #ff6b6b; padding: 15px; margin-bottom: 20px; border-radius: 4px; display: none; position: relative;">
                            <button onclick="document.getElementById('disruption-alert').style.display='none'" style="position: absolute; top: 10px; right: 10px; background: none; border: none; font-size: 20px; cursor: pointer; color: #721c24; font-weight: bold;">&times;</button>
                            <strong style="color: #721c24;"> <span id="alert-recent">0</span> new disruption(s) in the last 7 days. <span id="alert-ongoing">0</span> ongoing disruption(s) with no recovery date.</strong>
                        </div>

                        <h3>Disruption Analytics Dashboard</h3>
                        
                        <!-- Row 1: DF, ART, HDR -->
                        <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                            <!-- Disruption Frequency -->
                            <div class="data-card" style="height: 450px;">
                                <h4>DISRUPTION FREQUENCY (DF)</h4>
                                <div class="data-card-content" style="flex: 1; overflow-y: auto; display: flex; flex-direction: column;">
                                    <div style="flex: 1; min-height: 0;">
                                        <canvas id="dfChart"></canvas>
                                    </div>
                                    <button onclick="exportData('df')" style="width: 100%; background: #007bff; color: white; border: none; padding: 12px; border-radius: 4px; cursor: pointer; font-size: 14px; font-weight: 600; margin-top: 10px;">Export DF Data</button>
                                </div>
                            </div>

                            <!-- Average Recovery Time -->
                            <div class="data-card" style="height: 450px;">
                                <h4>AVERAGE RECOVERY TIME (ART)</h4>
                                <div class="data-card-content" style="flex: 1; overflow-y: auto; display: flex; flex-direction: column;">
                                    <div style="flex: 1; min-height: 0;">
                                        <canvas id="artChart"></canvas>
                                    </div>
                                    <button onclick="exportData('art')" style="width: 100%; background: #007bff; color: white; border: none; padding: 12px; border-radius: 4px; cursor: pointer; font-size: 14px; font-weight: 600; margin-top: 10px;">Export ART Data</button>
                                </div>
                            </div>

                            <!-- High-Impact Disruption Rate -->
                            <div class="data-card" style="height: 450px;">
                                <h4>HIGH-IMPACT DISRUPTION RATE (HDR)</h4>
                                <div class="data-card-content" style="flex: 1; overflow-y: auto; display: flex; flex-direction: column;">
                                    <div style="flex: 1; min-height: 0;">
                                        <canvas id="hdrChart"></canvas>
                                    </div>
                                    <button onclick="exportData('hdr')" style="width: 100%; background: #007bff; color: white; border: none; padding: 12px; border-radius: 4px; cursor: pointer; font-size: 14px; font-weight: 600; margin-top: 10px;">Export HDR Data</button>
                                </div>
                            </div>
                        </div>

                        <!-- Row 2: TD, RRC, DSD -->
                        <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                            <!-- Total Downtime -->
                            <div class="data-card" style="height: 450px;">
                                <h4>TOTAL DOWNTIME (TD)</h4>
                                <div class="data-card-content" style="flex: 1; overflow-y: auto; display: flex; flex-direction: column;">
                                    <div style="flex: 1; min-height: 0;">
                                        <canvas id="tdChart"></canvas>
                                    </div>
                                    <button onclick="exportData('td')" style="width: 100%; background: #007bff; color: white; border: none; padding: 12px; border-radius: 4px; cursor: pointer; font-size: 14px; font-weight: 600; margin-top: 10px;">Export TD Data</button>
                                </div>
                            </div>

                            <!-- Regional Risk Concentration -->
                            <div class="data-card" style="height: 450px;">
    <h4>REGIONAL RISK CONCENTRATION (RRC)</h4>
    <div class="data-card-content" style="flex: 1; overflow-y: auto; display: flex; flex-direction: column;">
        <div style="flex: 1; min-height: 0;">
            <canvas id="rrcChart"></canvas>
        </div>

        <!-- Tiny legend (added here) -->
        <div id="rrc-legend" style="font-size: 12px; margin-top: 6px; text-align: center;">
            <span style="margin-right: 10px;">
                <span style="display:inline-block;width:12px;height:12px;background:#7f1d1d;margin-right:4px;border-radius:2px;"></span> Very High
            </span>
            <span style="margin-right: 10px;">
                <span style="display:inline-block;width:12px;height:12px;background:#dc2626;margin-right:4px;border-radius:2px;"></span> High
            </span>
            <span style="margin-right: 10px;">
                <span style="display:inline-block;width:12px;height:12px;background:#f97316;margin-right:4px;border-radius:2px;"></span> Medium
            </span>
            <span>
                <span style="display:inline-block;width:12px;height:12px;background:#fef08a;margin-right:4px;border-radius:2px;"></span> Low
            </span>
        </div>

        <button onclick="exportData('rrc')" style="width: 100%; background: #007bff; color: white; border: none; padding: 12px; border-radius: 4px; cursor: pointer; font-size: 14px; font-weight: 600; margin-top: 10px;">
            Export RRC Data
        </button>
    </div>
</div>

                            <!-- Disruption Severity Distribution -->
                            <div class="data-card" style="height: 450px;">
                                <h4>DISRUPTION SEVERITY DISTRIBUTION (DSD)</h4>
                                <div class="data-card-content" style="flex: 1; overflow-y: auto; display: flex; flex-direction: column;">
                                    <div style="flex: 1; min-height: 0;">
                                        <canvas id="dsdChart"></canvas>
                                    </div>
                                    <button onclick="exportData('dsd')" style="width: 100%; background: #007bff; color: white; border: none; padding: 12px; border-radius: 4px; cursor: pointer; font-size: 14px; font-weight: 600; margin-top: 10px;">Export DSD Data</button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="tab-pane fade p-4" id="transactions" role="tabpanel" aria-labelledby="tab-4">
                        <h3>Transactions Overview</h3>
                        
                        <!-- Row 1: Metrics Cards -->
                        <div class="data-grid">
                            <div class="data-card">
                                <h4>Shipment Volume</h4>
                                <div class="data-card-content" id="shipment-volume-list">
                                    <p style="text-align: center; color: #666; padding: 20px;">Select a company and click Apply to view data</p>
                                </div>
                            </div>

                            <div class="data-card">
                                <h4>On-Time Delivery Rate</h4>
                                <div class="data-card-content" id="delivery-rate-list">
                                    <p style="text-align: center; color: #666; padding: 20px;">Select a company and click Apply to view data</p>
                                </div>
                            </div>

                            <div class="data-card">
                                <h4>Shipment Status</h4>
                                <div class="data-card-content" id="shipment-status-list">
                                    <p style="text-align: center; color: #666; padding: 20px;">Select a company and click Apply to view data</p>
                                </div>
                            </div>
                        </div>

                        <!-- Row 2: More Cards -->
                        <div class="data-grid">
                            <div class="data-card">
                                <h4>Products Handled</h4>
                                <div class="data-card-content" id="products-handled-list">
                                    <p style="text-align: center; color: #666; padding: 20px;">Select a company and click Apply to view data</p>
                                </div>
                            </div>

                            <div class="data-card">
                                <h4>Top Routes</h4>
                                <div class="data-card-content" id="top-routes-list">
                                    <p style="text-align: center; color: #666; padding: 20px;">Select a company and click Apply to view data</p>
                                </div>
                            </div>

                            <div class="data-card">
                                <h4>Disruption Exposure</h4>
                                <div class="data-card-content" id="disruption-exposure-list">
                                    <p style="text-align: center; color: #666; padding: 20px;">Select a company and click Apply to view data</p>
                                </div>
                            </div>
                        </div>

                        <!-- Transaction Details Table -->
                        <div class="data-card" style="height: 500px;">
                            <h4>Transaction Details</h4>
                            <div class="data-card-content" id="transactions-table">
                                <p style="text-align: center; color: #666; padding: 20px;">Select a company and click Apply to view data</p>
                            </div>
                        </div>

                        <!-- Row 3: 4 Visualization Charts (BELOW everything) -->
                        <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; margin-top: 20px;">
                            <!-- Chart 1: Shipment Volume Over Time -->
                            <div class="data-card" style="height: 350px;">
                                <h4>VOLUME OVER TIME</h4>
                                <div class="data-card-content" style="flex: 1; overflow-y: auto;">
                                    <canvas id="volumeTimeChart"></canvas>
                                </div>
                            </div>

                            <!-- Chart 2: On-Time vs Delayed Distribution -->
                            <div class="data-card" style="height: 350px;">
                                <h4>ON-TIME STATUS</h4>
                                <div class="data-card-content" style="flex: 1; overflow-y: auto;">
                                    <canvas id="onTimeStatusChart"></canvas>
                                </div>
                            </div>

                            <!-- Chart 3: Top Products by Quantity -->
                            <div class="data-card" style="height: 350px;">
                                <h4>TOP PRODUCTS</h4>
                                <div class="data-card-content" style="flex: 1; overflow-y: auto;">
                                    <canvas id="topProductsChart"></canvas>
                                </div>
                            </div>

                            <!-- Chart 4: Route Performance -->
                            <div class="data-card" style="height: 350px;">
                                <h4>ROUTE PERFORMANCE</h4>
                                <div class="data-card-content" style="flex: 1; overflow-y: auto;">
                                    <canvas id="routePerformanceChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>

        <script src='scm.js'></script>

        <?php include 'footer.php';?>
        
    </body>
</html>