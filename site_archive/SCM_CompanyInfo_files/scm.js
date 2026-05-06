/* ---------------- GLOBAL STATE ---------------- */
let selectedCompanyName = '';
let disruptionData = {};

document.addEventListener('DOMContentLoaded', function() {
    loadRegions();
    setupAutocomplete('companySearch', 'companyDropdown');
    
    // Initialize filter visibility for Company Info tab (default active)
    updateFiltersForTab('#company-info');
    
    // Add event listeners to sidebar tabs for filter switching
    document.querySelectorAll('.nav-link').forEach(tab => {
        tab.addEventListener('click', function() {
            const target = this.getAttribute('data-bs-target');
            updateFiltersForTab(target);
        });
    });
});

/* ---------------- FILTER VISIBILITY CONTROL ---------------- */
function updateFiltersForTab(tabId) {
    const dateRangeLabel = document.getElementById('label-daterange');
    const startDate = document.getElementById('startDate');
    const endDate = document.getElementById('endDate');
    const regionLabel = document.getElementById('label-region');
    const regionSelect = document.getElementById('regionSelect');
    
    if (tabId === '#company-info') {
        // Company Info: Hide date range and region, show only company search
        if (dateRangeLabel) dateRangeLabel.style.display = 'none';
        if (startDate) startDate.style.display = 'none';
        if (endDate) endDate.style.display = 'none';
        if (regionLabel) regionLabel.style.display = 'none';
        if (regionSelect) regionSelect.style.display = 'none';
    } else {
        // Transactions/Disruptions: Show all filters
        if (dateRangeLabel) dateRangeLabel.style.display = 'inline';
        if (startDate) startDate.style.display = 'inline-block';
        if (endDate) endDate.style.display = 'inline-block';
        if (regionLabel) regionLabel.style.display = 'inline';
        if (regionSelect) regionSelect.style.display = 'inline-block';
    }
}

/* ---------------- LOAD REGIONS ---------------- */
function loadRegions() {
    fetch('get_filter_options.php')
        .then(response => response.json())
        .then(data => {
            console.log('Filter options response:', data);
            if (data.regions && Array.isArray(data.regions)) {
                const regionSelect = document.getElementById('regionSelect');
                data.regions.forEach(region => {
                    const option = document.createElement('option');
                    option.value = region;
                    option.textContent = region;
                    regionSelect.appendChild(option);
                });
                console.log('Loaded regions:', data.regions.length);
            }
        })
        .catch(error => console.error('Error loading regions:', error));
}

/* ---------------- AUTOCOMPLETE ---------------- */
function setupAutocomplete(inputId, dropdownId) {
    const input = document.getElementById(inputId);
    const dropdown = document.getElementById(dropdownId);
    if (!input || !dropdown) return;

    input.addEventListener('input', function () {
        const query = this.value.trim();
        
        console.log('Input event fired. Query:', query);

        if (query.length < 1) {
            dropdown.classList.remove('show');
            dropdown.innerHTML = '';
            return;
        }

        // Dynamic search using the same endpoint as ERP
        fetch(`search_companies_erp.php?q=${encodeURIComponent(query)}`)
            .then(response => response.json())
            .then(data => {
                console.log('Company search response:', data);
                
                if (data.success && data.companies && data.companies.length > 0) {
                    dropdown.innerHTML = '';
                    data.companies.forEach(company => {
                        const item = document.createElement('div');
                        item.className = 'autocomplete-item';
                        item.setAttribute('data-name', company.CompanyName);
                        item.innerHTML = `
                            <span class="company-name">${company.CompanyName}</span>
                            <span class="company-type">(${company.CompanyType})</span>
                        `;
                        item.addEventListener('click', function() {
                            selectCompany(company.CompanyName, inputId, dropdownId);
                        });
                        dropdown.appendChild(item);
                    });
                    dropdown.classList.add('show');
                } else {
                    dropdown.innerHTML = '<div class="autocomplete-item" style="color: #999;">No companies found</div>';
                    dropdown.classList.add('show');
                }
            })
            .catch(error => {
                console.error('Error fetching companies:', error);
                dropdown.innerHTML = '<div class="autocomplete-item" style="color: #f00;">Error loading companies</div>';
                dropdown.classList.add('show');
            });
    });
    
    // Close dropdown when clicking outside
    document.addEventListener('click', function (e) {
        if (!input.contains(e.target) && !dropdown.contains(e.target)) {
            dropdown.classList.remove('show');
        }
    });
}

function selectCompany(companyName, inputId, dropdownId) {
    selectedCompanyName = companyName;

    const input = document.getElementById(inputId);
    const dropdown = document.getElementById(dropdownId);

    if (input) input.value = companyName;
    if (dropdown) dropdown.classList.remove('show');
}

/* ---------------- APPLY FILTERS ---------------- */
function applyFilters() {
    const activeTab = document.querySelector('.nav-link.active').getAttribute('data-bs-target');
    
    if (activeTab === '#company-info') {
        const company = document.getElementById('companySearch').value.trim();
        if (!company) {
            alert('Please select a company first');
            return;
        }
        loadCompanyInfo();
    } else if (activeTab === '#transactions') {
        loadTransactionData();
    } else if (activeTab === '#disruptions') {
        loadDisruptions();
    }
}

/* ---------------- COMPANY INFO LOAD ---------------- */
function loadCompanyInfo() {
    const company = document.getElementById('companySearch').value.trim();
    
    if (!company) {
        alert('Please select a company first');
        return;
    }
    
    const spinner = document.getElementById('loadingSpinner');
    if (spinner) spinner.style.display = 'inline-block';
    
    fetch(`get_company_info.php?company=${encodeURIComponent(company)}`)
        .then(response => response.text())
        .then(text => {
            let data;
            try {
                data = JSON.parse(text);
            } catch (e) {
                if (spinner) spinner.style.display = 'none';
                console.error('JSON parse error:', e);
                alert('Error loading company information (invalid JSON from server)');
                return;
            }

            if (spinner) spinner.style.display = 'none';

            if (data.success) {
                populateCompanyInfo(data.data);
            } else {
                alert(data.message || 'Error loading company information');
            }
        })
        .catch(error => {
            if (spinner) spinner.style.display = 'none';
            console.error('Fetch error:', error);
            alert('Error loading company information (request failed)');
        });
    
    loadCompanyKPIs(company);
    loadRecentTransactions(company);
    loadCompanyDisruptionEvents(company);
}

function populateCompanyInfo(data) {
    document.getElementById('company-name-display').textContent = data.CompanyName;
    document.getElementById('company-address').textContent = data.Address;
    document.getElementById('company-type').textContent = data.CompanyType;
    document.getElementById('tier-level').textContent = data.TierLevel;
    document.getElementById('financial-status').textContent = data.FinancialStatus || '-';
    document.getElementById('depends-on').textContent = data.DependsOn || '-';
    document.getElementById('dependencies').textContent = data.Dependencies || '-';
    document.getElementById('capacity').textContent = data.Capacity;
    document.getElementById('routes-operated').textContent = data.RoutesOperated;
    document.getElementById('products-supplied').textContent = data.ProductsSupplied || '-';
    document.getElementById('product-diversity').textContent = data.ProductDiversity;
}

/* ---------------- KPIs + CHART ---------------- */
function loadCompanyKPIs(company) {
    fetch(`get_company_kpis.php?company=${encodeURIComponent(company)}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('kpi-delivery-rate').textContent = data.data.onTimeRate + '%';
                document.getElementById('kpi-avg-delay').textContent = data.data.avgDelay;
                document.getElementById('kpi-std-delay').textContent = data.data.stdDevDelay;
                document.getElementById('financial-status').textContent = data.data.financialStatus;
                
                if (data.data.financialHistory && data.data.financialHistory.length > 0) {
                    drawFinancialChart(data.data.financialHistory);
                }
            }
        })
        .catch(error => console.error('Error loading KPIs:', error));
}

function drawFinancialChart(historyData) {
    const ctx = document.getElementById('financialChart');
    
    if (window.financialChartInstance) {
        window.financialChartInstance.destroy();
    }
    
    const labels = historyData.map(item => item.Quarter);
    const scores = historyData.map(item => parseFloat(item.FinancialScore));
    
    window.financialChartInstance = new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [{
                label: 'Health Score',
                data: scores,
                borderColor: '#007bff',
                backgroundColor: 'transparent',
                borderWidth: 2,
                tension: 0.4,
                pointBackgroundColor: '#007bff',
                pointRadius: 4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: {
                    display: false
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    max: 100,
                    grid: {
                        color: '#f0f0f0'
                    }
                },
                x: {
                    grid: {
                        display: false
                    }
                }
            }
        }
    });
}

/* ---------------- RECENT TRANSACTIONS ---------------- */
function loadRecentTransactions(company) {
    fetch(`get_recent_transactions.php?company=${encodeURIComponent(company)}&limit=10`)
        .then(response => response.json())
        .then(data => {
            if (data.success && data.transactions && data.transactions.length > 0) {
                let html = '';
                data.transactions.forEach(trans => {
                    html += `
                        <div class="transaction-item">
                            <div class="transaction-item-header">${trans.ShipmentID || 'N/A'}</div>
                            <div class="transaction-item-details">
                                ${trans.ProductName || 'N/A'} - Qty: ${trans.Quantity || 0}<br>
                                ${trans.SourceCompany || 'N/A'} => ${trans.DestinationCompany || 'N/A'}<br>
                                Status: ${trans.Status || 'N/A'} | ${trans.PromisedDate || 'N/A'}
                            </div>
                        </div>
                    `;
                });
                document.getElementById('recent-transactions-list').innerHTML = html;
            } else {
                document.getElementById('recent-transactions-list').innerHTML = '<p style="text-align: center; color: #666; padding: 20px;">No recent transactions</p>';
            }
        })
        .catch(error => {
            console.error('Error loading transactions:', error);
        });
}

/* ---------------- COMPANY DISRUPTION EVENTS ---------------- */
function loadCompanyDisruptionEvents(company) {
    console.log('Loading disruption events for:', company);
    
    fetch(`get_company_disruption_events.php?company=${encodeURIComponent(company)}`)
        .then(response => response.json())
        .then(data => {
            console.log('Disruption events response:', data);
            const container = document.getElementById('company-disruption-events-list');
            
            if (data.success && data.events && data.events.length > 0) {
                let html = '';
                data.events.forEach(event => {
                    const impactColor = event.ImpactLevel === 'High' ? '#dc3545' : 
                                       event.ImpactLevel === 'Medium' ? '#ffc107' : '#28a745';
                    const isOngoing = !event.RecoveryDate;
                    
                    html += `
                        <div style="padding: 12px; border-bottom: 1px solid #eee; border-left: 3px solid ${impactColor};">
                            <div style="font-weight: 600; color: #333; margin-bottom: 4px;">${event.DisruptionType}</div>
                            <div style="font-size: 0.85em; color: #666;">
                                <span style="color: ${impactColor}; font-weight: 600;">● ${event.ImpactLevel} Impact</span>
                                <span style="margin-left: 15px;">📅 ${event.EventDate}</span>
                            </div>
                            <div style="font-size: 0.85em; color: #666; margin-top: 4px;">
                                ${isOngoing 
                                    ? '<span style="color: #dc3545; font-weight: 600;">⚠️ ONGOING</span>' 
                                    : `✓ Recovered: ${event.RecoveryDate} (${event.DurationDays} days)`
                                }
                            </div>
                        </div>
                    `;
                });
                container.innerHTML = html;
                console.log('Disruption events loaded successfully');
            } else {
                container.innerHTML = '<p style="text-align: center; color: #666; padding: 20px;">No disruption events found</p>';
                console.log('No disruption events found');
            }
        })
        .catch(error => {
            console.error('Error loading disruption events:', error);
            document.getElementById('company-disruption-events-list').innerHTML = 
                '<p style="text-align: center; color: #dc3545; padding: 20px;">Error loading disruption events</p>';
        });
}

/* ---------------- DISRUPTIONS ---------------- */
function loadDisruptions() {
    const spinner = document.getElementById('loadingSpinner');
    if (spinner) spinner.style.display = 'inline-block';
    
    const startDate = document.getElementById('startDate')?.value || '';
    const endDate = document.getElementById('endDate')?.value || '';
    const company = document.getElementById('companySearch')?.value.trim() || '';
    const region = document.getElementById('regionSelect')?.value || '';
    
    const params = new URLSearchParams();
    if (startDate) params.append('startDate', startDate);
    if (endDate) params.append('endDate', endDate);
    if (company) params.append('company', company);
    if (region) params.append('region', region);
    
    fetch(`get_disruptions.php?${params.toString()}`)
        .then(response => response.json())
        .then(data => {
            if (spinner) spinner.style.display = 'none';
            
            if (data.success) {
                disruptionData = data.data;
                
                showDisruptionAlert(data.data.alert);
                createDFChart(data.data.disruptionFrequency);
                createARTChart(data.data.recoveryTimes);
                createHDRChart(data.data.highImpactRate);
                createTDChart(data.data.downtimes);
                createRRCChart(data.data.regionalRisk);
                createDSDChart(data.data.severityDistribution);
            } else {
                alert('Error loading disruption data: ' + data.error);
            }
        })
        .catch(error => {
            if (spinner) spinner.style.display = 'none';
            console.error('Error loading disruptions:', error);
            alert('Error loading disruption data');
        });
}

function showDisruptionAlert(alert) {
    const alertDiv = document.getElementById('disruption-alert');
    if (!alertDiv) return;
    
    document.getElementById('alert-recent').textContent = alert.recent;
    document.getElementById('alert-ongoing').textContent = alert.ongoing;
    alertDiv.style.display = 'block';
}

function createDFChart(data) {
    const canvas = document.getElementById('dfChart');
    if (!canvas) return;
    
    if (window.dfChart && typeof window.dfChart.destroy === 'function') {
        window.dfChart.destroy();
    }
    
    const ctx = canvas.getContext('2d');
    window.dfChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: data.map(d => d.CompanyName),
            datasets: [{
                label: 'Number of Disruptions',
                data: data.map(d => parseInt(d.DisruptionCount)),
                backgroundColor: '#3b82f6',
                borderWidth: 0
            }]
        },
        options: {
            indexAxis: 'y',
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false }
            },
            scales: {
                x: {
                    beginAtZero: true,
                    title: { display: true, text: 'Number of Disruptions' }
                }
            }
        }
    });
}

function createARTChart(recoveryTimes) {
    const canvas = document.getElementById('artChart');
    if (!canvas) return;
    
    if (window.artChart && typeof window.artChart.destroy === 'function') {
        window.artChart.destroy();
    }
    
    const bins = [0, 5, 10, 15, 20, 25, 30];
    const counts = new Array(bins.length - 1).fill(0);
    
    recoveryTimes.forEach(days => {
        for (let i = 0; i < bins.length - 1; i++) {
            if (days >= bins[i] && days < bins[i + 1]) {
                counts[i]++;
                break;
            }
        }
    });
    
    const labels = bins.slice(0, -1).map((bin, i) => `${bin}-${bins[i + 1]}`);
    
    const ctx = canvas.getContext('2d');
    window.artChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [{
                label: 'Count of Occurrences',
                data: counts,
                backgroundColor: '#007bff',
                borderWidth: 0
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false }
            },
            scales: {
                x: {
                    title: { display: true, text: 'Number of Days' }
                },
                y: {
                    beginAtZero: true,
                    title: { display: true, text: 'Count of Occurrences' }
                }
            }
        }
    });
}

function createHDRChart(data) {
    const canvas = document.getElementById('hdrChart');
    if (!canvas) return;
    
    if (window.hdrChart && typeof window.hdrChart.destroy === 'function') {
        window.hdrChart.destroy();
    }
    
    const ctx = canvas.getContext('2d');
    window.hdrChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: data.map(d => d.CompanyName),
            datasets: [{
                label: 'Percentage (%)',
                data: data.map(d => parseFloat(d.HighImpactRate)),
                backgroundColor: '#fbbf24',
                borderWidth: 0
            }]
        },
        options: {
            indexAxis: 'y',
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false }
            },
            scales: {
                x: {
                    beginAtZero: true,
                    title: { display: true, text: 'Percentage (%)' }
                }
            }
        }
    });
}

function createTDChart(downtimes) {
    const canvas = document.getElementById('tdChart');
    if (!canvas) return;
    
    if (window.tdChart && typeof window.tdChart.destroy === 'function') {
        window.tdChart.destroy();
    }
    
    // If no data, show message
    if (!downtimes || downtimes.length === 0) {
        const ctx = canvas.getContext('2d');
        ctx.clearRect(0, 0, canvas.width, canvas.height);
        ctx.font = '14px Arial';
        ctx.fillStyle = '#666';
        ctx.textAlign = 'center';
        ctx.fillText('No downtime data available', canvas.width / 2, canvas.height / 2);
        return;
    }
    
    // Find min and max to create appropriate bins
    const minDowntime = Math.min(...downtimes);
    const maxDowntime = Math.max(...downtimes);
    
    // Create dynamic bins based on data range
    let binSize = 100;
    if (maxDowntime <= 50) {
        binSize = 10;
    } else if (maxDowntime <= 200) {
        binSize = 50;
    }
    
    const binStart = Math.floor(minDowntime / binSize) * binSize;
    const binEnd = Math.ceil(maxDowntime / binSize) * binSize + binSize;
    
    const bins = [];
    for (let i = binStart; i < binEnd; i += binSize) {
        bins.push(i);
    }
    
    const counts = new Array(bins.length - 1).fill(0);
    
    downtimes.forEach(days => {
        for (let i = 0; i < bins.length - 1; i++) {
            if (days >= bins[i] && days < bins[i + 1]) {
                counts[i]++;
                break;
            }
        }
    });
    
    const labels = bins.slice(0, -1).map((bin, i) => `${bin}-${bins[i + 1]}`);
    
    const ctx = canvas.getContext('2d');
    window.tdChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [{
                label: 'Count of Occurrences',
                data: counts,
                backgroundColor: '#ef4444',
                borderWidth: 0
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false }
            },
            scales: {
                x: {
                    title: { display: true, text: 'Number of Days' }
                },
                y: {
                    beginAtZero: true,
                    title: { display: true, text: 'Count of Occurrences' },
                    ticks: {
                        stepSize: 1
                    }
                }
            }
        }
    });
}

function createRRCChart(data) {
    const canvas = document.getElementById('rrcChart');
    if (!canvas) return;

    // Destroy existing chart if it exists
    if (window.rrcChart && typeof window.rrcChart.destroy === 'function') {
        window.rrcChart.destroy();
    }

    // If no data, show a simple message
    if (!data || data.length === 0) {
        const ctx = canvas.getContext('2d');
        ctx.clearRect(0, 0, canvas.width, canvas.height);
        ctx.font = '14px Arial';
        ctx.fillStyle = '#666';
        ctx.textAlign = 'center';
        ctx.fillText('No data available. Apply filters to view regional risk.', canvas.width / 2, canvas.height / 2);
        return;
    }

    const ctx = canvas.getContext('2d');
    const labels = data.map(d => d.Region);
    const values = data.map(d => parseFloat(d.RiskPercentage));

    // Color scale based on risk % (heatmap style)
    const colors = values.map(v => {
        if (v >= 40) return 'rgba(127, 29, 29, 0.9)';   // very high risk (dark red)
        if (v >= 30) return 'rgba(220, 38, 38, 0.9)';   // high risk (red)
        if (v >= 20) return 'rgba(249, 115, 22, 0.9)';  // medium risk (orange)
        if (v > 0)   return 'rgba(254, 240, 138, 0.9)'; // low risk (yellow)
        return 'rgba(229, 231, 235, 0.9)';              // no risk (grey)
    });

    window.rrcChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [{
                label: 'Risk %',
                data: values,
                backgroundColor: colors,
                borderColor: '#ffffff',
                borderWidth: 1
            }]
        },
        options: {
            indexAxis: 'y',  // horizontal bars (feels more like a heat strip)
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: context => `Risk: ${context.parsed.x.toFixed(1)}%`
                    }
                }
            },
            scales: {
                x: {
                    beginAtZero: true,
                    max: 100,
                    title: { display: true, text: 'Risk %' },
                    grid: {
                        color: '#f0f0f0'
                    }
                },
                y: {
                    grid: { display: false }
                }
            }
        }
    });
}

function createDSDChart(data) {
    const canvas = document.getElementById('dsdChart');
    if (!canvas) return;
    
    if (window.dsdChart && typeof window.dsdChart.destroy === 'function') {
        window.dsdChart.destroy();
    }
    
    const ctx = canvas.getContext('2d');
    window.dsdChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: data.map(d => d.CompanyName),
            datasets: [
                {
                    label: 'High',
                    data: data.map(d => parseInt(d.HighCount)),
                    backgroundColor: '#ef4444',
                    borderWidth: 0
                },
                {
                    label: 'Medium',
                    data: data.map(d => parseInt(d.MediumCount)),
                    backgroundColor: '#fbbf24',
                    borderWidth: 0
                },
                {
                    label: 'Low',
                    data: data.map(d => parseInt(d.LowCount)),
                    backgroundColor: '#10b981',
                    borderWidth: 0
                }
            ]
        },
        options: {
            indexAxis: 'y',
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { 
                    display: true,
                    position: 'bottom'
                }
            },
            scales: {
                x: {
                    stacked: true,
                    beginAtZero: true,
                    title: { display: true, text: 'Number of Disruptions' }
                },
                y: {
                    stacked: true
                }
            }
        }
    });
}

function exportData(chartType) {
    let dataToExport, filename;
    
    switch(chartType) {
        case 'df':
            dataToExport = disruptionData.disruptionFrequency;
            filename = 'disruption_frequency.csv';
            break;
        case 'art':
            dataToExport = disruptionData.recoveryTimes.map(days => ({ RecoveryDays: days }));
            filename = 'average_recovery_time.csv';
            break;
        case 'hdr':
            dataToExport = disruptionData.highImpactRate;
            filename = 'high_impact_rate.csv';
            break;
        case 'td':
            dataToExport = disruptionData.downtimes.map(days => ({ TotalDowntime: days }));
            filename = 'total_downtime.csv';
            break;
        case 'rrc':
            dataToExport = disruptionData.regionalRisk;
            filename = 'regional_risk.csv';
            break;
        case 'dsd':
            dataToExport = disruptionData.severityDistribution;
            filename = 'severity_distribution.csv';
            break;
        default:
            alert('Unknown chart type');
            return;
    }
    
    if (!dataToExport || dataToExport.length === 0) {
        alert('No data to export');
        return;
    }
    
    const headers = Object.keys(dataToExport[0]).join(',');
    const rows = dataToExport.map(row => 
        Object.values(row).join(',')
    );
    const csv = [headers, ...rows].join('\n');
    
    const blob = new Blob([csv], { type: 'text/csv' });
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = filename;
    a.click();
    window.URL.revokeObjectURL(url);
}

/* ---------------- TRANSACTIONS TAB ---------------- */
function loadTransactionData() {
    const spinner = document.getElementById('loadingSpinner');
    if (spinner) spinner.style.display = 'inline-block';
    
    const startDate = document.getElementById('startDate')?.value || '';
    const endDate = document.getElementById('endDate')?.value || '';
    const company = document.getElementById('companySearch').value.trim();
    const region = document.getElementById('regionSelect')?.value || '';
    
    const params = new URLSearchParams();
    if (startDate) params.append('startDate', startDate);
    if (endDate) params.append('endDate', endDate);
    if (company) params.append('company', company);
    if (region) params.append('region', region);
    
    // Load existing transaction data
    fetch(`get_transactions.php?${params.toString()}`)
        .then(response => response.json())
        .then(data => {
            if (spinner) spinner.style.display = 'none';
            
            if (data.success) {
                populateShipmentVolume(data.data.shipmentVolume);
                populateDeliveryRate(data.data.deliveryRate);
                populateShipmentStatus(data.data.shipmentStatus);
                populateProductsHandled(data.data.productsHandled);
                populateTopRoutes(data.data.topRoutes);
                populateDisruptionExposure(data.data.disruptionExposure);
                populateTransactionDetails(data.data.transactions);
            }
        })
        .catch(error => {
            if (spinner) spinner.style.display = 'none';
            console.error('Error loading transaction data:', error);
        });
    
    // Load chart data
    fetch(`get_transaction_charts.php?${params.toString()}`)
        .then(response => response.json())
        .then(data => {
            if (data.success && data.charts) {
                createVolumeTimeChart(data.charts.volumeOverTime);
                createOnTimeStatusChart(data.charts.statusDistribution);
                createTopProductsChart(data.charts.topProducts);
                createRoutePerformanceChart(data.charts.routePerformance);
            }
        })
        .catch(error => {
            console.error('Error loading transaction charts:', error);
        });
}

/* ---------------- CLEAR FILTERS ---------------- */
function clearFilters() {
    // Clear all filter inputs
    if (document.getElementById('startDate')) document.getElementById('startDate').value = '';
    if (document.getElementById('endDate')) document.getElementById('endDate').value = '';
    if (document.getElementById('companySearch')) document.getElementById('companySearch').value = '';
    selectedCompanyName = '';
    if (document.getElementById('regionSelect')) document.getElementById('regionSelect').value = '';
    
    const emptyMessage = '<p style="text-align: center; color: #666; padding: 20px;">Select a company and click Apply to view data</p>';
    
    // Clear transaction data
    if (document.getElementById('shipment-volume-list')) {
        document.getElementById('shipment-volume-list').innerHTML = emptyMessage;
    }
    if (document.getElementById('delivery-rate-list')) {
        document.getElementById('delivery-rate-list').innerHTML = emptyMessage;
    }
    if (document.getElementById('shipment-status-list')) {
        document.getElementById('shipment-status-list').innerHTML = emptyMessage;
    }
    if (document.getElementById('products-handled-list')) {
        document.getElementById('products-handled-list').innerHTML = emptyMessage;
    }
    if (document.getElementById('top-routes-list')) {
        document.getElementById('top-routes-list').innerHTML = emptyMessage;
    }
    if (document.getElementById('disruption-exposure-list')) {
        document.getElementById('disruption-exposure-list').innerHTML = emptyMessage;
    }
    if (document.getElementById('transactions-table')) {
        document.getElementById('transactions-table').innerHTML = emptyMessage;
    }
    
    // Clear company info data
    document.getElementById('company-name-display').textContent = 'Select a company';
    document.getElementById('company-address').textContent = '-';
    document.getElementById('company-type').textContent = '-';
    document.getElementById('tier-level').textContent = '-';
    document.getElementById('financial-status').textContent = '-';
    document.getElementById('depends-on').textContent = '-';
    document.getElementById('dependencies').textContent = '-';
    document.getElementById('capacity').textContent = '-';
    document.getElementById('routes-operated').textContent = '-';
    document.getElementById('products-supplied').textContent = '-';
    document.getElementById('product-diversity').textContent = '-';
    document.getElementById('kpi-delivery-rate').textContent = '-%';
    document.getElementById('kpi-avg-delay').textContent = '-';
    document.getElementById('kpi-std-delay').textContent = '-';
    document.getElementById('recent-transactions-list').innerHTML = '<p style="text-align: center; color: #666; padding: 20px;">Select a company to view transactions</p>';
    
    // Clear disruption events list
    const disruptionEvents = document.getElementById('company-disruption-events-list');
    if (disruptionEvents) {
        disruptionEvents.innerHTML = '<p style="text-align: center; color: #666; padding: 20px;">Select a company to view disruption events</p>';
    }
    
    // Destroy financial chart
    if (window.financialChartInstance) {
        window.financialChartInstance.destroy();
    }
    
    // Clear disruption alert
    const disruptionAlert = document.getElementById('disruption-alert');
    if (disruptionAlert) {
        disruptionAlert.style.display = 'none';
    }
    
    // Clear and reset all disruption charts with empty state
    clearDisruptionChart('dfChart', 'No data available. Apply filters to view disruption frequency.');
    clearDisruptionChart('artChart', 'No data available. Apply filters to view recovery times.');
    clearDisruptionChart('hdrChart', 'No data available. Apply filters to view high-impact rates.');
    clearDisruptionChart('tdChart', 'No data available. Apply filters to view downtime distribution.');
    clearDisruptionChart('rrcChart', 'No data available. Apply filters to view regional risk.');
    clearDisruptionChart('dsdChart', 'No data available. Apply filters to view severity distribution.');
    
    // Clear transaction charts
    clearDisruptionChart('volumeTimeChart', 'No data available.');
    clearDisruptionChart('onTimeStatusChart', 'No data available.');
    clearDisruptionChart('topProductsChart', 'No data available.');
    clearDisruptionChart('routePerformanceChart', 'No data available.');
    
    // Clear disruption data
    disruptionData = {};
}

function clearDisruptionChart(canvasId, message) {
    const canvas = document.getElementById(canvasId);
    if (!canvas) return;
    
    // Destroy existing chart
    const chartVarName = canvasId.replace('Chart', 'Chart');
    if (window[chartVarName] && typeof window[chartVarName].destroy === 'function') {
        window[chartVarName].destroy();
    }
    
    // Draw empty state message
    const ctx = canvas.getContext('2d');
    ctx.clearRect(0, 0, canvas.width, canvas.height);
    ctx.font = '14px Arial';
    ctx.fillStyle = '#999';
    ctx.textAlign = 'center';
    ctx.textBaseline = 'middle';
    
    // Word wrap the message
    const maxWidth = canvas.width - 40;
    const words = message.split(' ');
    const lines = [];
    let currentLine = words[0];
    
    for (let i = 1; i < words.length; i++) {
        const testLine = currentLine + ' ' + words[i];
        const metrics = ctx.measureText(testLine);
        if (metrics.width > maxWidth) {
            lines.push(currentLine);
            currentLine = words[i];
        } else {
            currentLine = testLine;
        }
    }
    lines.push(currentLine);
    
    // Draw lines centered
    const lineHeight = 20;
    const startY = (canvas.height / 2) - ((lines.length - 1) * lineHeight / 2);
    lines.forEach((line, index) => {
        ctx.fillText(line, canvas.width / 2, startY + (index * lineHeight));
    });
}

/* ---------------- TRANSACTION CHARTS ---------------- */
function createVolumeTimeChart(data) {
    const canvas = document.getElementById('volumeTimeChart');
    if (!canvas) return;
    
    if (window.volumeTimeChart && typeof window.volumeTimeChart.destroy === 'function') {
        window.volumeTimeChart.destroy();
    }
    
    if (!data || data.length === 0) {
        const ctx = canvas.getContext('2d');
        ctx.clearRect(0, 0, canvas.width, canvas.height);
        ctx.font = '14px Arial';
        ctx.fillStyle = '#666';
        ctx.textAlign = 'center';
        ctx.fillText('No data available', canvas.width / 2, canvas.height / 2);
        return;
    }
    
    const ctx = canvas.getContext('2d');
    window.volumeTimeChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: data.map(d => d.Month),
            datasets: [{
                label: 'Shipment Count',
                data: data.map(d => parseInt(d.ShipmentCount)),
                borderColor: '#007bff',
                backgroundColor: 'rgba(0, 123, 255, 0.1)',
                borderWidth: 2,
                tension: 0.4,
                fill: true
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    title: { display: true, text: 'Number of Shipments' }
                },
                x: {
                    title: { display: true, text: 'Month' }
                }
            }
        }
    });
}

function createOnTimeStatusChart(data) {
    const canvas = document.getElementById('onTimeStatusChart');
    if (!canvas) return;
    
    if (window.onTimeStatusChart && typeof window.onTimeStatusChart.destroy === 'function') {
        window.onTimeStatusChart.destroy();
    }
    
    if (!data || data.length === 0) {
        const ctx = canvas.getContext('2d');
        ctx.clearRect(0, 0, canvas.width, canvas.height);
        ctx.font = '14px Arial';
        ctx.fillStyle = '#666';
        ctx.textAlign = 'center';
        ctx.fillText('No data available', canvas.width / 2, canvas.height / 2);
        return;
    }
    
    const colors = {
        'On-Time': '#28a745',
        'Delayed': '#dc3545',
        'Pending': '#ffc107'
    };
    
    const ctx = canvas.getContext('2d');
    window.onTimeStatusChart = new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: data.map(d => d.Status),
            datasets: [{
                data: data.map(d => parseInt(d.Count)),
                backgroundColor: data.map(d => colors[d.Status] || '#6c757d'),
                borderWidth: 2,
                borderColor: '#fff'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom'
                }
            }
        }
    });
}

function createTopProductsChart(data) {
    const canvas = document.getElementById('topProductsChart');
    if (!canvas) return;
    
    if (window.topProductsChart && typeof window.topProductsChart.destroy === 'function') {
        window.topProductsChart.destroy();
    }
    
    if (!data || data.length === 0) {
        const ctx = canvas.getContext('2d');
        ctx.clearRect(0, 0, canvas.width, canvas.height);
        ctx.font = '14px Arial';
        ctx.fillStyle = '#666';
        ctx.textAlign = 'center';
        ctx.fillText('No data available', canvas.width / 2, canvas.height / 2);
        return;
    }
    
    const ctx = canvas.getContext('2d');
    window.topProductsChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: data.map(d => d.ProductName),
            datasets: [{
                label: 'Total Quantity',
                data: data.map(d => parseInt(d.TotalQuantity)),
                backgroundColor: '#17a2b8',
                borderWidth: 0
            }]
        },
        options: {
            indexAxis: 'y',
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false }
            },
            scales: {
                x: {
                    beginAtZero: true,
                    title: { display: true, text: 'Quantity' }
                }
            }
        }
    });
}

function createRoutePerformanceChart(data) {
    const canvas = document.getElementById('routePerformanceChart');
    if (!canvas) return;
    
    if (window.routePerformanceChart && typeof window.routePerformanceChart.destroy === 'function') {
        window.routePerformanceChart.destroy();
    }
    
    if (!data || data.length === 0) {
        const ctx = canvas.getContext('2d');
        ctx.clearRect(0, 0, canvas.width, canvas.height);
        ctx.font = '14px Arial';
        ctx.fillStyle = '#666';
        ctx.textAlign = 'center';
        ctx.fillText('No data available', canvas.width / 2, canvas.height / 2);
        return;
    }
    
    // Color code by performance
    const colors = data.map(d => {
        const percent = parseFloat(d.OnTimePercent);
        if (percent >= 80) return '#28a745';
        if (percent >= 60) return '#ffc107';
        return '#dc3545';
    });
    
    const ctx = canvas.getContext('2d');
    window.routePerformanceChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: data.map(d => d.Route),
            datasets: [{
                label: 'On-Time %',
                data: data.map(d => parseFloat(d.OnTimePercent)),
                backgroundColor: colors,
                borderWidth: 0
            }]
        },
        options: {
            indexAxis: 'y',
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false }
            },
            scales: {
                x: {
                    beginAtZero: true,
                    max: 100,
                    title: { display: true, text: 'On-Time Percentage (%)' }
                }
            }
        }
    });
}

/* ---------------- TRANSACTION HELPERS ---------------- */
function populateShipmentVolume(data) {
    const container = document.getElementById('shipment-volume-list');
    if (!data || data.length === 0) {
        container.innerHTML = '<p style="text-align: center; color: #666; padding: 20px;">No data available</p>';
        return;
    }
    
    let html = '';
    data.forEach(item => {
        html += `
            <div class="shipment-item">
                <span class="distributor">${item.Distributor}</span>
                <span class="quantity">${parseInt(item.TotalQuantity).toLocaleString()}</span>
            </div>
        `;
    });
    container.innerHTML = html;
}

function populateDeliveryRate(data) {
    const container = document.getElementById('delivery-rate-list');
    if (!data || data.length === 0) {
        container.innerHTML = '<p style="text-align: center; color: #666; padding: 20px;">No data available</p>';
        return;
    }
    
    let html = '';
    data.forEach(item => {
        const rate = parseFloat(item.OnTimeRate);
        let rateClass = 'good';
        if (rate < 15) rateClass = 'low';
        else if (rate < 17) rateClass = 'medium';
        
        html += `
            <div class="delivery-rate-item">
                <span class="distributor-name">${item.Distributor}</span>
                <span class="rate ${rateClass}">${rate}%</span>
            </div>
        `;
    });
    container.innerHTML = html;
}

function populateShipmentStatus(data) {
    const container = document.getElementById('shipment-status-list');
    if (!data || data.length === 0) {
        container.innerHTML = '<p style="text-align: center; color: #666; padding: 20px;">No data available</p>';
        return;
    }
    
    let html = '';
    data.forEach(item => {
        html += `
            <div class="shipment-item">
                <span class="distributor">${item.Status}</span>
                <span class="quantity">${parseInt(item.Count).toLocaleString()}</span>
            </div>
        `;
    });
    container.innerHTML = html;
}

function populateProductsHandled(data) {
    const container = document.getElementById('products-handled-list');
    if (!data || data.length === 0) {
        container.innerHTML = '<p style="text-align: center; color: #666; padding: 20px;">No data available</p>';
        return;
    }
    
    let html = '';
    data.forEach(item => {
        html += `
            <div class="shipment-item">
                <div>
                    <div class="distributor">${item.ProductName}</div>
                    <div style="font-size: 0.85em; color: #666;">${item.Category}</div>
                </div>
                <span class="quantity">${parseInt(item.TotalQuantity).toLocaleString()}</span>
            </div>
        `;
    });
    container.innerHTML = html;
}

function populateTopRoutes(data) {
    const container = document.getElementById('top-routes-list');
    if (!data || data.length === 0) {
        container.innerHTML = '<p style="text-align: center; color: #666; padding: 20px;">No data available</p>';
        return;
    }
    
    let html = '';
    data.forEach(item => {
        html += `
            <div class="shipment-item">
                <span class="distributor">${item.Route}</span>
                <span class="quantity">${parseInt(item.ShipmentCount).toLocaleString()}</span>
            </div>
        `;
    });
    container.innerHTML = html;
}

function populateDisruptionExposure(data) {
    const container = document.getElementById('disruption-exposure-list');
    if (!data || data.length === 0) {
        container.innerHTML = '<p style="text-align: center; color: #666; padding: 20px;">No data available</p>';
        return;
    }
    
    const totalDisruptions = data.length;
    const highImpactCount = data.filter(item => item.ImpactLevel === 'High').length;
    const score = totalDisruptions + (highImpactCount * 2);
    
    let html = `
        <div style="background-color: #fff3cd; border-radius: 8px; padding: 20px; margin-bottom: 15px;">
            <div style="margin-bottom: 10px;">
                <strong>Total:</strong> <span style="color: #17a2b8; font-size: 1.5em; font-weight: bold;">${totalDisruptions}</span>
                <strong style="margin-left: 20px;">High Impact:</strong> <span style="color: #fd7e14; font-size: 1.5em; font-weight: bold;">${highImpactCount}</span>
                <strong style="margin-left: 20px;">Score:</strong> <span style="color: #dc3545; font-size: 1.5em; font-weight: bold;">${score}</span>
            </div>
            <div style="font-size: 0.9em; color: #856404;">
                ${totalDisruptions} + (${highImpactCount} * 2) = ${score}
            </div>
        </div>
    `;
    
    data.forEach(item => {
        const impactColor = item.ImpactLevel === 'High' ? '#dc3545' : 
                           item.ImpactLevel === 'Medium' ? '#ffc107' : '#28a745';
        
        html += `
            <div class="shipment-item">
                <div style="flex: 1;">
                    <div class="distributor">${item.DisruptionType}</div>
                    <div style="font-size: 0.85em; color: #666;">
                        <span style="color: ${impactColor}; font-weight: 600;">Impact: ${item.ImpactLevel}</span>
                        <span style="margin-left: 10px;">Date: ${item.StartDate || 'N/A'}</span>
                    </div>
                </div>
                <span class="quantity">${parseInt(item.AffectedCompanies)} companies</span>
            </div>
        `;
    });
    
    container.innerHTML = html;
}

function populateTransactionDetails(data) {
    const container = document.getElementById('transactions-table');
    if (!data || data.length === 0) {
        container.innerHTML = '<p style="text-align: center; color: #666; padding: 20px;">No transactions found</p>';
        return;
    }
    
    let html = `
        <table style="width: 100%; border-collapse: collapse; font-size: 0.9em;">
            <thead>
                <tr style="border-bottom: 2px solid #ddd; text-align: left;">
                    <th style="padding: 10px;">Shipment ID</th>
                    <th style="padding: 10px;">Type</th>
                    <th style="padding: 10px;">From</th>
                    <th style="padding: 10px;">To</th>
                    <th style="padding: 10px;">Product</th>
                    <th style="padding: 10px;">Qty</th>
                    <th style="padding: 10px;">Status</th>
                    <th style="padding: 10px;">Date</th>
                </tr>
            </thead>
            <tbody>
    `;
    
    data.forEach(item => {
        html += `
            <tr style="border-bottom: 1px solid #eee;">
                <td style="padding: 10px;">${item.ShipmentID || 'N/A'}</td>
                <td style="padding: 10px;">${item.TransactionType || 'N/A'}</td>
                <td style="padding: 10px;">${item.SourceCompany || 'N/A'}</td>
                <td style="padding: 10px;">${item.DestinationCompany || 'N/A'}</td>
                <td style="padding: 10px;">${item.ProductName || 'N/A'}</td>
                <td style="padding: 10px;">${item.Quantity || 0}</td>
                <td style="padding: 10px;">${item.Status || 'N/A'}</td>
                <td style="padding: 10px;">${item.PromisedDate || 'N/A'}</td>
            </tr>
        `;
    });
    
    html += `
            </tbody>
        </table>
    `;
    
    container.innerHTML = html;
}