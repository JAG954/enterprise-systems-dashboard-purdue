// Global variables for filters
let currentFilters = {
    startDate: null,
    endDate: null,
    company: null,
    region: null
};

let filtersLoaded = false; // Flag to prevent duplicate loading

document.addEventListener('DOMContentLoaded', function() {
    if (!filtersLoaded) {
        loadFilterOptions();
        filtersLoaded = true;
    }
    setupCompanyAutocomplete();           // top filter autocomplete
    setupCompanyFinancialAutocomplete();  // company financials tab
    loadEventDropdown();           // disruption details: event
    setupCompanyDisruptionAutocomplete(); // disruption details: company
});

// Setup company autocomplete (top filters)
function setupCompanyAutocomplete() {
    const searchInput = document.getElementById('companySearch');
    const dropdown = document.getElementById('companyDropdown');
    
    if (!searchInput || !dropdown) return;
    
    searchInput.addEventListener('input', function() {
        const query = this.value.trim();
        
        if (query.length < 1) {
            dropdown.classList.remove('show');
            return;
        }
        
        fetch(`search_companies_erp.php?q=${encodeURIComponent(query)}`)
            .then(response => response.json())
            .then(data => {
                console.log('Company search response:', data); // Debug log
                
                if (data.success && data.companies && data.companies.length > 0) {
                    dropdown.innerHTML = '';
                    data.companies.forEach(company => {
                        const item = document.createElement('div');
                        item.className = 'autocomplete-item';
                        item.innerHTML = `
                            <span class="company-name">${company.CompanyName}</span>
                            <span class="company-type">(${company.CompanyType})</span>
                        `;
                        item.addEventListener('click', function() {
                            searchInput.value = company.CompanyName;
                            dropdown.classList.remove('show');
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
    document.addEventListener('click', function(e) {
        if (!searchInput.contains(e.target) && !dropdown.contains(e.target)) {
            dropdown.classList.remove('show');
        }
    });
}

// Setup autocomplete for Company Financials search bar
function setupCompanyFinancialAutocomplete() {
    const searchInput = document.getElementById('companyFinancialSearch');
    const dropdown    = document.getElementById('companyFinancialDropdown');

    if (!searchInput || !dropdown) return;

    searchInput.addEventListener('input', function () {
        const query = this.value.trim();

        if (query.length < 1) {
            dropdown.classList.remove('show');
            return;
        }

        fetch(`search_companies_erp.php?q=${encodeURIComponent(query)}`)
            .then(response => response.json())
            .then(data => {
                console.log('Company financial search response:', data); // Debug log

                if (data.success && data.companies && data.companies.length > 0) {
                    dropdown.innerHTML = '';
                    data.companies.forEach(company => {
                        const item = document.createElement('div');
                        item.className = 'autocomplete-item';
                        item.innerHTML = `
                            <span class="company-name">${company.CompanyName}</span>
                            <span class="company-type">(${company.CompanyType})</span>
                        `;
                        item.addEventListener('click', function () {
                            searchInput.value = company.CompanyName;
                            dropdown.classList.remove('show');
                        });
                        dropdown.appendChild(item);
                    });
                    dropdown.classList.add('show');
                } else {
                    dropdown.innerHTML =
                        '<div class="autocomplete-item" style="color:#999;">No companies found</div>';
                    dropdown.classList.add('show');
                }
            })
            .catch(error => {
                console.error('Error fetching companies (financials):', error);
                dropdown.innerHTML =
                    '<div class="autocomplete-item" style="color:#f00;">Error loading companies</div>';
                dropdown.classList.add('show');
            });
    });

    // Close dropdown when clicking outside
    document.addEventListener('click', function (e) {
        if (!searchInput.contains(e.target) && !dropdown.contains(e.target)) {
            dropdown.classList.remove('show');
        }
    });
}

// Setup autocomplete for Company Disruption (right card in Disruption Details)
function setupCompanyDisruptionAutocomplete() {
    const searchInput = document.getElementById('companyDisruptionSearch');
    const dropdown = document.getElementById('companyDisruptionDropdown');
    
    if (!searchInput || !dropdown) return;
    
    searchInput.addEventListener('input', function() {
        const query = this.value.trim();
        
        if (query.length < 1) {
            dropdown.classList.remove('show');
            return;
        }
        
        fetch(`search_companies_erp.php?q=${encodeURIComponent(query)}`)
            .then(response => response.json())
            .then(data => {
                console.log('Company disruption search response:', data);
                
                if (data.success && data.companies && data.companies.length > 0) {
                    dropdown.innerHTML = '';
                    data.companies.forEach(company => {
                        const item = document.createElement('div');
                        item.className = 'autocomplete-item';
                        item.innerHTML = `
                            <span class="company-name">${company.CompanyName}</span>
                            <span class="company-type">(${company.CompanyType})</span>
                        `;
                        item.addEventListener('click', function() {
                            searchInput.value = company.CompanyName;
                            dropdown.classList.remove('show');
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
    document.addEventListener('click', function(e) {
        if (!searchInput.contains(e.target) && !dropdown.contains(e.target)) {
            dropdown.classList.remove('show');
        }
    });
}

// Load filter dropdown options
function loadFilterOptions() {
    fetch('get_filter_options.php')
        .then(response => {
            if (!response.ok) throw new Error('Network response was not ok');
            return response.json();
        })
        .then(data => {
            console.log('Filter options response:', data); // Debug log
            
            // Populate region select
            const regionSelect = document.getElementById('regionSelect');
            const newCompanyRegion = document.getElementById('newCompanyRegion');

            if (regionSelect && data.regions) {
                // Clear existing options except "All Regions"
                while (regionSelect.options.length > 1) {
                    regionSelect.remove(1);
                }
            }
            if (newCompanyRegion && data.regions) {
                // Clear existing options except "Select Region"
                while (newCompanyRegion.options.length > 1) {
                    newCompanyRegion.remove(1);
                }
            }

            if (data.regions) {
                data.regions.forEach(region => {
                    if (regionSelect) {
                        const option = document.createElement('option');
                        option.value = region;
                        option.textContent = region;
                        regionSelect.appendChild(option);
                    }
                    if (newCompanyRegion) {
                        const opt2 = document.createElement('option');
                        opt2.value = region;
                        opt2.textContent = region;
                        newCompanyRegion.appendChild(opt2);
                    }
                });
            }
        })
        .catch(error => {
            console.error('Error loading filter options:', error);
        });
}

// Setup autocomplete for Company Name in "Add Company" tab
function setupAddCompanyNameAutocomplete() {
    const searchInput = document.getElementById('newCompanyName');
    const dropdown    = document.getElementById('newCompanyNameDropdown');

    if (!searchInput || !dropdown) return;

    searchInput.addEventListener('input', function () {
        const query = this.value.trim();

        if (query.length < 1) {
            dropdown.classList.remove('show');
            return;
        }

        fetch(`search_companies_erp.php?q=${encodeURIComponent(query)}`)
            .then(response => response.json())
            .then(data => {
                console.log('Add Company search response:', data);

                if (data.success && data.companies && data.companies.length > 0) {
                    dropdown.innerHTML = '';
                    data.companies.forEach(company => {
                        const item = document.createElement('div');
                        item.className = 'autocomplete-item';
                        item.innerHTML = `
                            <span class="company-name">${company.CompanyName}</span>
                            <span class="company-type">(${company.CompanyType})</span>
                        `;
                        item.addEventListener('click', function () {
                            // Fill the chosen company name into the input
                            searchInput.value = company.CompanyName;
                            dropdown.classList.remove('show');
                        });
                        dropdown.appendChild(item);
                    });
                    dropdown.classList.add('show');
                } else {
                    dropdown.innerHTML =
                        '<div class="autocomplete-item" style="color:#999;">No companies found</div>';
                    dropdown.classList.add('show');
                }
            })
            .catch(error => {
                console.error('Error fetching companies for Add Company:', error);
                dropdown.innerHTML =
                    '<div class="autocomplete-item" style="color:#f00;">Error loading companies</div>';
                dropdown.classList.add('show');
            });
    });

    // Close dropdown when clicking outside
    document.addEventListener('click', function (e) {
        if (!searchInput.contains(e.target) && !dropdown.contains(e.target)) {
            dropdown.classList.remove('show');
        }
    });
}

// Apply filters
function applyFilters() {
    const spinner = document.getElementById('loadingSpinner');
    if (spinner) spinner.style.display = 'inline-block';
    
    currentFilters.startDate = document.getElementById('startDate')?.value || '';
    currentFilters.endDate = document.getElementById('endDate')?.value || '';
    currentFilters.company = document.getElementById('companySearch')?.value || '';
    currentFilters.region = document.getElementById('regionSelect')?.value || '';
    
    // Reload active tab data
    const activeTab = document.querySelector('.nav-link.active');
    if (!activeTab) {
        if (spinner) spinner.style.display = 'none';
        return;
    }
    
    const tabId = activeTab.id;
    if (tabId === 'tab-1') {
        loadFinancialHealth();
    } else if (tabId === 'tab-2') {
        loadRegionalDisruptions();
    } else if (tabId === 'tab-3') {
        loadCriticalCompanies();
    } else if (tabId === 'tab-4') {
        loadDisruptionTimeline();
    } else if (tabId === 'tab-6') {
        loadTopDistributors();
    } else if (tabId === 'tab-7') {
        loadDistributorsByDelay();
    } else if (tabId === 'tab-9') {
        loadCustomAnalytics();
    }
    
    if (spinner) spinner.style.display = 'none';
}

// Clear filters
function clearFilters() {
    document.getElementById('startDate').value = '';
    document.getElementById('endDate').value = '';
    document.getElementById('companySearch').value = '';
    document.getElementById('regionSelect').value = '';
    
    currentFilters = {
        startDate: null,
        endDate: null,
        company: null,
        region: null
    };
}

// Financial Health
function loadFinancialHealth() {
    const output = document.getElementById('financial-output');
    if (!output) return;
    
    output.innerHTML = '<div class="spinner-border" role="status"><span class="visually-hidden">Loading...</span></div>';
    
    fetch('get_financial_health.php?' + new URLSearchParams(currentFilters))
        .then(response => {
            if (!response.ok) throw new Error('Network response was not ok');
            return response.json();
        })
        .then(data => {
            console.log('Financial health data:', data);
            
            if (data.success && data.data) {
                // Clear the loading message
                output.innerHTML = '';
                
                // Create charts
                createFinancialCharts(data.data);
            } else {
                output.innerHTML = `<div class="alert alert-danger">${data.error || 'Error loading data'}</div>`;
            }
        })
        .catch(error => {
            output.innerHTML = `<div class="alert alert-danger">Error loading data: ${error.message}</div>`;
        });
}

function createFinancialCharts(data) {
    const companyCanvas = document.getElementById('financialCompanyChart');
    const typeCanvas = document.getElementById('financialTypeChart');
    
    if (!companyCanvas || !typeCanvas) return;
    
    console.log('Creating charts with data:', data); // Debug
    
    // Check if we have data
    if (!data.byCompany || data.byCompany.length === 0) {
        companyCanvas.getContext('2d').fillText('No data available', 50, 50);
    }
    if (!data.byType || data.byType.length === 0) {
        typeCanvas.getContext('2d').fillText('No data available', 50, 50);
    }
    
    // Destroy existing chart instances if they exist
    if (window.companyChart) window.companyChart.destroy();
    if (window.typeChart) window.typeChart.destroy();
    
    // Company chart
    const companyCtx = companyCanvas.getContext('2d');
    window.companyChart = new Chart(companyCtx, {
        type: 'bar',
        data: {
            labels: data.byCompany.map(d => d.CompanyName),
            datasets: [{
                label: 'Avg Financial Health',
                data: data.byCompany.map(d => parseFloat(d.AvgHealth)),
                backgroundColor: 'rgba(54, 162, 235, 0.6)',
                borderColor: 'rgba(54, 162, 235, 1)',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: { 
                    beginAtZero: true, 
                    max: 100,
                    title: {
                        display: true,
                        text: 'Health Score'
                    }
                }
            },
            plugins: {
                legend: {
                    display: true,
                    position: 'top'
                },
                tooltip: {
                    callbacks: {
                        afterLabel: function(context) {
                            const index = context.dataIndex;
                            return 'Records: ' + data.byCompany[index].RecordCount;
                        }
                    }
                }
            }
        }
    });
    
    // Type chart
    const typeCtx = typeCanvas.getContext('2d');
    window.typeChart = new Chart(typeCtx, {
        type: 'bar',
        data: {
            labels: data.byType.map(d => d.CompanyType),
            datasets: [{
                label: 'Avg Financial Health',
                data: data.byType.map(d => parseFloat(d.AvgHealth)),
                backgroundColor: 'rgba(255, 99, 132, 0.6)',
                borderColor: 'rgba(255, 99, 132, 1)',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: { 
                    beginAtZero: true, 
                    max: 100,
                    title: {
                        display: true,
                        text: 'Health Score'
                    }
                }
            },
            plugins: {
                legend: {
                    display: true,
                    position: 'top'
                },
                tooltip: {
                    callbacks: {
                        afterLabel: function(context) {
                            const index = context.dataIndex;
                            return 'Companies: ' + data.byType[index].CompanyCount;
                        }
                    }
                }
            }
        }
    });
    
    console.log('Charts created successfully');
}

// Regional Disruptions
function loadRegionalDisruptions() {
    const output = document.getElementById('regional-output');
    if (!output) return;
    
    output.innerHTML = '<div class="spinner-border" role="status"><span class="visually-hidden">Loading...</span></div>';
    
    fetch('get_regional_disruptions.php?' + new URLSearchParams(currentFilters))
        .then(response => {
            if (!response.ok) throw new Error('Network response was not ok');
            return response.json();
        })
        .then(data => {
            console.log('Regional disruptions data:', data);
            
            if (data.success && data.data) {
                // Clear the loading message
                output.innerHTML = '';
                
                // Create chart
                createRegionalChart(data.data);
            } else {
                output.innerHTML = `<div class="alert alert-danger">${data.error || 'Error loading data'}</div>`;
            }
        })
        .catch(error => {
            output.innerHTML = `<div class="alert alert-danger">Error loading data: ${error.message}</div>`;
        });
}

function createRegionalChart(data) {
    const canvas = document.getElementById('regionalChart');
    if (!canvas) return;

    console.log('Creating regional chart with data:', data);

    // If no data, show a simple message instead of trying to build a chart
    if (!data || data.length === 0) {
        const ctx = canvas.getContext('2d');
        ctx.clearRect(0, 0, canvas.width, canvas.height);
        ctx.font = '14px Arial';
        ctx.fillText('No data available', 50, 50);
        return;
    }

    const ctx = canvas.getContext('2d');

    // Safely destroy previous chart instance
    if (window.regionalChart && typeof window.regionalChart.destroy === 'function') {
        window.regionalChart.destroy();
    }

    // Create stacked bar chart
    window.regionalChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: data.map(d => d.Region),
            datasets: [
                {
                    label: 'High Impact',
                    data: data.map(d => parseInt(d.HighImpact)),
                    backgroundColor: 'rgba(255, 99, 132, 0.6)',
                    borderColor: 'rgba(255, 99, 132, 1)',
                    borderWidth: 1
                },
                {
                    label: 'Medium Impact',
                    data: data.map(d => parseInt(d.MediumImpact)),
                    backgroundColor: 'rgba(255, 206, 86, 0.6)',
                    borderColor: 'rgba(255, 206, 86, 1)',
                    borderWidth: 1
                },
                {
                    label: 'Low Impact',
                    data: data.map(d => parseInt(d.LowImpact)),
                    backgroundColor: 'rgba(75, 192, 192, 0.6)',
                    borderColor: 'rgba(75, 192, 192, 1)',
                    borderWidth: 1
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                x: {
                    stacked: true,
                    title: {
                        display: true,
                        text: 'Region'
                    }
                },
                y: {
                    stacked: true,
                    beginAtZero: true,
                    title: {
                        display: true,
                        text: 'Number of Disruptions'
                    }
                }
            },
            plugins: {
                legend: {
                    display: true,
                    position: 'top'
                },
                tooltip: {
                    callbacks: {
                        footer: function (context) {
                            const index = context[0].dataIndex;
                            return (
                                'Total: ' + data[index].TotalDisruptions +
                                '\nAffected Companies: ' + data[index].AffectedCompanies
                            );
                        }
                    }
                }
            }
        }
    });

    console.log('Regional chart created successfully');
}

// Critical Companies
function loadCriticalCompanies() {
    const output = document.getElementById('critical-output');
    if (!output) return;

    output.innerHTML = '<div class="spinner-border" role="status"><span class="visually-hidden">Loading...</span></div>';

    fetch('get_critical_companies.php?' + new URLSearchParams(currentFilters))
        .then(response => {
            if (!response.ok) throw new Error('Network response was not ok');
            return response.json();
        })
        .then(data => {
            console.log('Critical companies data:', data);

            if (!data.success) {
                output.innerHTML = `<div class="alert alert-danger">${data.error || 'Error loading data'}</div>`;
                return;
            }

            const rows = data.data || [];
            if (rows.length === 0) {
                output.innerHTML = '<div class="placeholder">No critical companies found for the selected filters.</div>';
                return;
            }

            let html = `
                <div class="data-card" style="height:auto;">
                    <h4>Top Critical Companies</h4>
                    <div class="data-card-content" style="max-height:500px;">
                        <table class="table table-sm table-striped">
                            <thead>
                                <tr>
                                    <th>Company</th>
                                    <th>Type</th>
                                    <th>Downstream Companies</th>
                                    <th>High Impact Events</th>
                                    <th>Total Events</th>
                                    <th>Criticality Score</th>
                                </tr>
                            </thead>
                            <tbody>
            `;

            rows.forEach(row => {
                html += `
                    <tr>
                        <td>${row.CompanyName}</td>
                        <td>${row.CompanyType || 'N/A'}</td>
                        <td>${row.DownstreamCount}</td>
                        <td>${row.HighImpactCount}</td>
                        <td>${row.TotalEvents}</td>
                        <td><strong>${row.CriticalityScore}</strong></td>
                    </tr>
                `;
            });

            html += `
                            </tbody>
                        </table>
                        <p class="text-muted" style="font-size:0.85rem;">
                            Criticality Score = Downstream Companies × High Impact Events
                        </p>
                    </div>
                </div>
            `;

            output.innerHTML = html;
        })
        .catch(error => {
            console.error('Error loading critical companies:', error);
            output.innerHTML = `<div class="alert alert-danger">Error loading data: ${error.message}</div>`;
        });
}

// Disruption Timeline
function loadDisruptionTimeline() {
    const output = document.getElementById('timeline-output');
    if (!output) return;

    output.innerHTML =
        '<div class="spinner-border" role="status"><span class="visually-hidden">Loading...</span></div>';

    fetch('get_disruption_timeline.php?' + new URLSearchParams(currentFilters))
        .then(response => {
            if (!response.ok) throw new Error('Network response was not ok');
            return response.json();
        })
        .then(data => {
            console.log('Disruption timeline data:', data);

            if (data.success && data.data) {
                output.innerHTML = '';
                createTimelineChart(data.data);
            } else {
                output.innerHTML =
                    `<div class="alert alert-danger">${data.error || 'Error loading data'}</div>`;
            }
        })
        .catch(error => {
            output.innerHTML =
                `<div class="alert alert-danger">Error loading data: ${error.message}</div>`;
        });
}

function createTimelineChart(data) {
    const canvas = document.getElementById('timelineChart');
    if (!canvas) return;

    console.log('Creating timeline chart with data:', data);

    const ctx = canvas.getContext('2d');

    // If no data, show simple message
    if (!data || data.length === 0) {
        ctx.clearRect(0, 0, canvas.width, canvas.height);
        ctx.fillText('No data available for the selected filters', 40, 40);
        return;
    }

    // Destroy existing chart if it exists
    if (window.timelineChart && typeof window.timelineChart.destroy === 'function') {
        window.timelineChart.destroy();
    }

    const labels = data.map(d => d.PeriodLabel); // YYYY-MM

    const totalData  = data.map(d => parseInt(d.TotalImpacts || 0));
    const highData   = data.map(d => parseInt(d.HighImpact || 0));
    const mediumData = data.map(d => parseInt(d.MediumImpact || 0));
    const lowData    = data.map(d => parseInt(d.LowImpact || 0));

    window.timelineChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [
                {
                    label: 'Total Impacts',
                    data: totalData,
                    borderColor: 'rgba(54, 162, 235, 1)',
                    backgroundColor: 'rgba(54, 162, 235, 0.2)',
                    borderWidth: 2,
                    tension: 0.2,
                    fill: false
                },
                {
                    label: 'High Impact',
                    data: highData,
                    borderColor: 'rgba(255, 99, 132, 1)',
                    backgroundColor: 'rgba(255, 99, 132, 0.2)',
                    borderWidth: 2,
                    tension: 0.2,
                    fill: false
                },
                {
                    label: 'Medium Impact',
                    data: mediumData,
                    borderColor: 'rgba(255, 206, 86, 1)',
                    backgroundColor: 'rgba(255, 206, 86, 0.2)',
                    borderWidth: 2,
                    tension: 0.2,
                    fill: false
                },
                {
                    label: 'Low Impact',
                    data: lowData,
                    borderColor: 'rgba(75, 192, 192, 1)',
                    backgroundColor: 'rgba(75, 192, 192, 0.2)',
                    borderWidth: 2,
                    tension: 0.2,
                    fill: false
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: {
                mode: 'index',
                intersect: false
            },
            scales: {
                x: {
                    title: {
                        display: true,
                        text: 'Period (YYYY-MM)'
                    }
                },
                y: {
                    beginAtZero: true,
                    title: {
                        display: true,
                        text: 'Number of Impacts'
                    }
                }
            },
            plugins: {
                legend: {
                    display: true,
                    position: 'top'
                },
                tooltip: {
                    callbacks: {
                        footer: function (context) {
                            const index = context[0].dataIndex;
                            return `Total impacts: ${totalData[index]}`;
                        }
                    }
                }
            }
        }
    });

    console.log('Timeline chart created successfully');
}

let companyFinancialChart = null;

// Company Financials
function loadCompanyFinancials() {
    const output = document.getElementById('company-financial-output');
    const nameInput = document.getElementById('companyFinancialSearch');
    if (!output || !nameInput) return;

    const companyName = nameInput.value.trim();
    if (!companyName) {
        output.innerHTML = '<div class="alert alert-warning">Please enter a company name.</div>';
        return;
    }

    // Show loading spinner
    output.innerHTML = '<div class="spinner-border" role="status"><span class="visually-hidden">Loading...</span></div>';

    // Build query parameters (use global filters for date + region)
    const params = {
        companyName: companyName,
        startDate: currentFilters.startDate || '',
        endDate: currentFilters.endDate || '',
        region: currentFilters.region || ''
    };

    fetch('get_company_financials.php?' + new URLSearchParams(params))
        .then(response => {
            if (!response.ok) throw new Error('Network response was not ok');
            return response.json();
        })
        .then(data => {
            console.log('Company financials data:', data);

            if (!data.success) {
                output.innerHTML = `<div class="alert alert-danger">${data.error || 'Error loading data'}</div>`;
                return;
            }

            const company        = data.data.company;
            const companyTimeline = data.data.companyTimeline || [];
            const regionTimeline  = data.data.regionTimeline || [];
            const regionUsed      = data.data.regionUsed || company.Region || 'All Regions';

            if (companyTimeline.length === 0) {
                output.innerHTML = '<div class="alert alert-info">No financial data found for this company (with current filters).</div>';
                return;
            }

            // Build the HTML container with a canvas + summary table
            let tableRows = '';
            companyTimeline.forEach(row => {
                tableRows += `
                    <tr>
                        <td>${row.RepYear}</td>
                        <td>${row.Quarter}</td>
                        <td>${parseFloat(row.AvgHealth).toFixed(2)}</td>
                        <td>${row.RecordCount}</td>
                    </tr>
                `;
            });

            output.innerHTML = `
                <div class="data-card" style="height: 450px;">
                    <h4>Financial Health Over Time - ${company.CompanyName} (${company.CompanyType}, ${company.Region || 'Unknown Region'})</h4>
                    <div class="data-card-content">
                        <canvas id="companyFinancialChart"></canvas>
                    </div>
                </div>

                <div class="mt-3">
                    <h5>Summary</h5>
                    <p class="text-muted">
                        Region comparison: <strong>${regionUsed || 'All Regions'}</strong>.
                    </p>
                    <table class="table table-sm table-striped">
                        <thead>
                            <tr>
                                <th>Year</th>
                                <th>Quarter</th>
                                <th>Avg Health Score</th>
                                <th>Records</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${tableRows}
                        </tbody>
                    </table>
                </div>
            `;

            // Now create the chart
            const canvas = document.getElementById('companyFinancialChart');
            if (!canvas) return;

            const ctx = canvas.getContext('2d');

            // Labels like "2024 Q1"
            const labels = companyTimeline.map(row => `${row.RepYear} ${row.Quarter}`);
            const companySeries = companyTimeline.map(row => parseFloat(row.AvgHealth));

            // Map region timeline to same labels
            const regionMap = {};
            regionTimeline.forEach(row => {
                const key = `${row.RepYear} ${row.Quarter}`;
                regionMap[key] = parseFloat(row.AvgHealth);
            });
            const regionSeries = labels.map(label => regionMap[label] ?? null);

            // Destroy old chart safely
            if (window.companyFinancialChart && typeof window.companyFinancialChart.destroy === 'function') {
                window.companyFinancialChart.destroy();
            }

            window.companyFinancialChart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [
                        {
                            label: company.CompanyName,
                            data: companySeries,
                            fill: false,
                            borderColor: 'rgba(54, 162, 235, 1)',
                            backgroundColor: 'rgba(54, 162, 235, 0.2)',
                            tension: 0.2
                        },
                        {
                            label: `Region Average (${regionUsed})`,
                            data: regionSeries,
                            fill: false,
                            borderColor: 'rgba(255, 99, 132, 1)',
                            backgroundColor: 'rgba(255, 99, 132, 0.2)',
                            tension: 0.2
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            max: 100,
                            title: {
                                display: true,
                                text: 'Health Score'
                            }
                        },
                        x: {
                            title: {
                                display: true,
                                text: 'Year / Quarter'
                            }
                        }
                    },
                    plugins: {
                        legend: {
                            display: true,
                            position: 'top'
                        },
                        tooltip: {
                            mode: 'index',
                            intersect: false
                        }
                    }
                }
            });
        })
        .catch(error => {
            console.error('Error loading company financials:', error);
            output.innerHTML = `<div class="alert alert-danger">Error loading data: ${error.message}</div>`;
        });
}

let distributorsChart = null;

function loadTopDistributors() {
    const output = document.getElementById('distributors-output');
    if (!output) return;

    // Show loading state
    output.innerHTML =
        '<div class="spinner-border" role="status"><span class="visually-hidden">Loading...</span></div>';

    fetch('get_top_distributors.php?' + new URLSearchParams(currentFilters))
        .then(response => {
            if (!response.ok) throw new Error('Network response was not ok');
            return response.json();
        })
        .then(data => {
            console.log('Top distributors data:', data);

            if (!data.success) {
                output.innerHTML = `<div class="alert alert-danger">${data.error || 'Error loading data'}</div>`;
                return;
            }

            const rows = data.data || [];
            if (rows.length === 0) {
                output.innerHTML =
                    '<div class="placeholder">No distributor data found for the selected filters.</div>';
                return;
            }

            // Build summary table rows
            let tableRows = '';
            rows.forEach((row, idx) => {
                const qty   = row.TotalQuantity !== null ? parseInt(row.TotalQuantity) : 0;
                const count = row.ShipmentCount !== null ? parseInt(row.ShipmentCount) : 0;
                const delay = row.AvgDelayDays !== null ? parseFloat(row.AvgDelayDays).toFixed(2) : 'N/A';

                tableRows += `
                    <tr>
                        <td>${idx + 1}</td>
                        <td>${row.CompanyName}</td>
                        <td>${row.Region}</td>
                        <td>${qty}</td>
                        <td>${count}</td>
                        <td>${delay}</td>
                    </tr>
                `;
            });

            // Card with chart + table
            output.innerHTML = `
                <div class="data-card" style="height: 450px;">
                    <h4>Top Distributors by Shipment Volume</h4>
                    <div class="data-card-content">
                        <canvas id="distributorsChart"></canvas>
                    </div>
                </div>

                <div class="mt-3">
                    <h5>Detail Table</h5>
                    <table class="table table-sm table-striped">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Distributor</th>
                                <th>Region</th>
                                <th>Total Quantity Shipped</th>
                                <th># Shipments</th>
                                <th>Avg Delay (days)</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${tableRows}
                        </tbody>
                    </table>
                </div>
            `;

            // Create / update chart
            const canvas = document.getElementById('distributorsChart');
            if (!canvas) return;

            const ctx = canvas.getContext('2d');

            const labels      = rows.map(r => r.CompanyName);
            const quantities  = rows.map(r => parseInt(r.TotalQuantity || 0));
            const shipments   = rows.map(r => parseInt(r.ShipmentCount || 0));

            // Destroy old chart safely
            if (window.distributorsChart && typeof window.distributorsChart.destroy === 'function') {
                window.distributorsChart.destroy();
            }

            window.distributorsChart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Total Quantity Shipped',
                        data: quantities,
                        backgroundColor: 'rgba(54, 162, 235, 0.6)',
                        borderColor: 'rgba(54, 162, 235, 1)',
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            title: {
                                display: true,
                                text: 'Quantity'
                            }
                        },
                        x: {
                            title: {
                                display: true,
                                text: 'Distributor'
                            }
                        }
                    },
                    plugins: {
                        legend: {
                            display: true,
                            position: 'top'
                        },
                        tooltip: {
                            callbacks: {
                                footer: function (context) {
                                    const index = context[0].dataIndex;
                                    return 'Shipments: ' + shipments[index];
                                }
                            }
                        }
                    }
                }
            });
        })
        .catch(error => {
            console.error('Error loading top distributors:', error);
            output.innerHTML =
                `<div class="alert alert-danger">Error loading data: ${error.message}</div>`;
        });
}

// ADD THIS FUNCTION before loadCompanyDisruptions in your erp.js file:

function loadCompaniesAffected() {
    const output = document.getElementById('companies-affected-output');
    const eventSelect = document.getElementById('eventIdSelect');
    if (!output || !eventSelect) return;

    const eventId = eventSelect.value.trim();
    if (!eventId) {
        output.innerHTML = '<div class="text-muted">Please select an event.</div>';
        return;
    }

    output.innerHTML =
        '<div class="spinner-border" role="status"><span class="visually-hidden">Loading...</span></div>';

    fetch('get_companies_affected.php?' + new URLSearchParams({ eventId }))
        .then(r => r.text())
        .then(text => {
            console.log('Raw companies affected response:', text);
            let data;
            try {
                data = JSON.parse(text);
            } catch (e) {
                throw new Error('Server did not return valid JSON. Response was: ' + text);
            }
            return data;
        })
        .then(data => {
            console.log('Companies affected data:', data);

            if (!data.success) {
                output.innerHTML =
                    `<div class="alert alert-danger">${data.error || 'Error loading data'}</div>`;
                return;
            }

            const event = data.event;
            const companies = data.companies || [];

            if (!companies.length) {
                output.innerHTML =
                    '<div class="alert alert-info">No companies recorded as affected by this event.</div>';
                return;
            }

            let rows = '';
            companies.forEach(row => {
                rows += `
                    <tr>
                        <td>${row.CompanyName}</td>
                        <td>${row.CompanyType || 'N/A'}</td>
                        <td>${row.ImpactLevel}</td>
                        <td>${row.ContinentName || ''}</td>
                        <td>${row.CountryName || ''}</td>
                        <td>${row.City || ''}</td>
                    </tr>
                `;
            });

            output.innerHTML = `
                <h5>Event #${event.EventID} – ${event.CategoryName || 'Uncategorized'}</h5>
                <p class="text-muted">
                    Date: <strong>${event.EventDate || 'N/A'}</strong>
                    &nbsp;|&nbsp;
                    Recovery: <strong>${event.EventRecoveryDate || 'N/A'}</strong>
                    &nbsp;|&nbsp;
                    Affected companies: <strong>${event.AffectedCount}</strong>
                </p>
                <table class="table table-sm table-striped">
                    <thead>
                        <tr>
                            <th>Company</th>
                            <th>Type</th>
                            <th>Impact Level</th>
                            <th>Continent</th>
                            <th>Country</th>
                            <th>City</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${rows}
                    </tbody>
                </table>
            `;
        })
        .catch(err => {
            console.error('Error loading companies affected:', err);
            output.innerHTML =
                `<div class="alert alert-danger">Error loading data: ${err.message}</div>`;
        });
}

// ALSO UPDATE loadCompanyDisruptions to remove the extra div wrapper:

function loadCompanyDisruptions() {
    const output = document.getElementById('company-disruptions-output');
    const nameInput = document.getElementById('companyDisruptionSearch');
    if (!output || !nameInput) {
        return;
    }

    const companyName = nameInput.value.trim();
    if (!companyName) {
        output.innerHTML = '<div class="text-muted">Please enter a company name.</div>';
        return;
    }

    output.innerHTML =
        '<div class="spinner-border" role="status"><span class="visually-hidden">Loading...</span></div>';

    fetch('get_company_disruptions.php?' + new URLSearchParams({ companyName }))
        .then(r => r.text())
        .then(text => {
            console.log('Raw company disruptions response:', text);
            let data;
            try {
                data = JSON.parse(text);
            } catch (e) {
                throw new Error('Server did not return valid JSON. Response was: ' + text);
            }
            return data;
        })
        .then(data => {
            console.log('Company disruptions data:', data);

            if (!data.success) {
                output.innerHTML =
                    `<div class="alert alert-danger">${data.error || 'Error loading data'}</div>`;
                return;
            }

            const company = data.company;
            const rowsData = data.disruptions || [];

            if (!rowsData.length) {
                output.innerHTML =
                    `<div class="alert alert-info">No recorded disruptions for ${company.CompanyName}.</div>`;
                return;
            }

            let rows = '';
            rowsData.forEach(row => {
                rows += `
                    <tr>
                        <td>${row.EventID}</td>
                        <td>${row.CategoryName || 'N/A'}</td>
                        <td>${row.EventDate || ''}</td>
                        <td>${row.EventRecoveryDate || ''}</td>
                        <td>${row.DurationDays !== null ? row.DurationDays : ''}</td>
                        <td>${row.ImpactLevel}</td>
                    </tr>
                `;
            });

            // Note: No extra data-card wrapper - output is directly in the data-card-content div
            output.innerHTML = `
                <h5>Disruptions affecting ${company.CompanyName}</h5>
                <p class="text-muted">
                    Type: <strong>${company.CompanyType || 'N/A'}</strong>
                    &nbsp;|&nbsp;
                    Location:
                    <strong>${[
                        company.City,
                        company.CountryName,
                        company.ContinentName
                    ].filter(Boolean).join(', ') || 'Unknown'}</strong>
                </p>
                <table class="table table-sm table-striped">
                    <thead>
                        <tr>
                            <th>Event ID</th>
                            <th>Category</th>
                            <th>Start Date</th>
                            <th>Recovery Date</th>
                            <th>Duration (days)</th>
                            <th>Impact Level</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${rows}
                    </tbody>
                </table>
            `;
        })
        .catch(err => {
            console.error('Error loading company disruptions:', err);
            output.innerHTML =
                `<div class="alert alert-danger">Error loading data: ${err.message}</div>`;
        });
}

function loadDistributorsByDelay() {
    const output = document.getElementById('delay-output');
    if (!output) return;

    output.innerHTML =
        '<div class="spinner-border" role="status"><span class="visually-hidden">Loading...</span></div>';

    fetch('get_distributors_by_delay.php?' + new URLSearchParams(currentFilters))
        .then(r => {
            if (!r.ok) throw new Error('Network response was not ok');
            return r.json();
        })
        .then(data => {
            console.log('Distributors by delay data:', data);

            if (!data.success) {
                output.innerHTML = `<div class="alert alert-danger">${data.error || 'Error loading data'}</div>`;
                return;
            }

            const rowsData = data.data || [];

            if (!rowsData.length) {
                output.innerHTML =
                    '<div class="alert alert-info">No shipment delay data found for the selected filters.</div>';
                return;
            }

            let rows = '';
            rowsData.forEach(row => {
                rows += `
                    <tr>
                        <td>${row.CompanyName}</td>
                        <td>${row.Region}</td>
                        <td>${row.ShipmentCount}</td>
                        <td>${row.AvgDelayDays !== null ? Number(row.AvgDelayDays).toFixed(2) : ''}</td>
                    </tr>
                `;
            });

            output.innerHTML = `
                <div class="data-card" style="height:auto;">
                    <h4>Distributors Sorted by Average Delivery Delay</h4>
                    <div class="data-card-content" style="max-height:500px;">
                        <table class="table table-sm table-striped">
                            <thead>
                                <tr>
                                    <th>Distributor</th>
                                    <th>Region</th>
                                    <th>Shipment Count</th>
                                    <th>Avg Delay (days)</th>
                                </tr>
                            </thead>
                            <tbody>
                                ${rows}
                            </tbody>
                        </table>
                        <p class="text-muted" style="font-size:0.85rem;">
                            Positive delay means deliveries are later than promised dates.
                        </p>
                    </div>
                </div>
            `;
        })
        .catch(err => {
            console.error('Error loading distributors by delay:', err);
            output.innerHTML =
                `<div class="alert alert-danger">Error loading data: ${err.message}</div>`;
        });
}
// Populate the Disruption Event dropdown (right look like screenshot)
function loadEventDropdown() {
    const select = document.getElementById('eventIdSelect');
    if (!select) return;

    // Keep the first "Select an event..." option, remove the rest
    while (select.options.length > 1) {
        select.remove(1);
    }

    fetch('get_event_options.php')
        .then(r => {
            if (!r.ok) throw new Error('Network response was not ok');
            return r.json();
        })
        .then(data => {
            console.log('Event options:', data);

            if (!data.success) {
                // Optionally you could add a disabled option with the error
                const opt = document.createElement('option');
                opt.value = '';
                opt.textContent = 'Error loading events';
                opt.disabled = true;
                select.appendChild(opt);
                return;
            }

            const events = data.events || [];
            events.forEach(ev => {
                const opt = document.createElement('option');
                // value is the ID we send to PHP
                opt.value = ev.EventID;
                // label shown in dropdown (Category - Date), just like your screenshot
                const labelParts = [];
                if (ev.CategoryName) labelParts.push(ev.CategoryName);
                if (ev.EventDate) labelParts.push(ev.EventDate);
                opt.textContent = labelParts.join(' - ');
                select.appendChild(opt);
            });
        })
        .catch(err => {
            console.error('Error loading event options:', err);
            const opt = document.createElement('option');
            opt.value = '';
            opt.textContent = 'Error loading events';
            opt.disabled = true;
            select.appendChild(opt);
        });
}

// Add Company - real implementation
function addCompany(event) {
    event.preventDefault();

    const nameEl   = document.getElementById('newCompanyName');
    const typeEl   = document.getElementById('newCompanyType');
    const regionEl = document.getElementById('newCompanyRegion');
    const output   = document.getElementById('add-company-output');

    if (!output) return false;

    const companyName = nameEl.value.trim();
    const companyType = typeEl.value.trim();
    const region      = regionEl.value.trim();

    // Basic validation
    if (!companyName || !companyType || !region) {
        output.innerHTML =
            '<div class="alert alert-warning">Please fill in all fields before submitting.</div>';
        return false;
    }

    // Show loading spinner
    output.innerHTML =
        '<div class="spinner-border" role="status"><span class="visually-hidden">Saving...</span></div>';

    const formData = new FormData();
    formData.append('companyName', companyName);
    formData.append('companyType', companyType);
    formData.append('region', region);

    fetch('add_company_erp.php', {
    method: 'POST',
    body: formData
})
    .then(r => r.text())
    .then(text => {
        console.log('Raw add_company_erp.php response:', text);
        let data;
        try {
            data = JSON.parse(text);
        } catch (e) {
            throw new Error('Server did not return valid JSON. Response was: ' + text);
        }
        return data;
    })
    .then(data => {
        console.log('Add company response:', data);

        if (!data.success) {
            output.innerHTML =
                `<div class="alert alert-danger">${data.error || 'Error adding company.'}</div>`;
            return;
        }

        const c = data.company || {};
        output.innerHTML = `
            <div class="alert alert-success">
                Company <strong>${c.CompanyName || companyName}</strong> added successfully
                ${c.CompanyID ? '(ID ' + c.CompanyID + ')' : ''}.<br>
                It will now appear in the company search dropdowns.
            </div>
        `;

        nameEl.value = '';
        typeEl.selectedIndex = 0;
        regionEl.selectedIndex = 0;
    })
    .catch(err => {
        console.error('Add company error:', err);
        output.innerHTML =
            `<div class="alert alert-danger">Error adding company: ${err.message}</div>`;
    });

    return false;
}

// Global handles for custom analytics charts
let customChart1 = null;
let customChart2 = null;

// Custom Analytics
function loadCustomAnalytics() {
    const output = document.getElementById('custom-analytics-output');
    const canvas1 = document.getElementById('customChart1');
    const canvas2 = document.getElementById('customChart2');

    if (!output || !canvas1 || !canvas2) return;

    // Loading state
    output.innerHTML =
        '<div class="spinner-border" role="status"><span class="visually-hidden">Loading...</span></div>';

    // Use same global filters: startDate, endDate, company, region
    fetch('get_custom_analytics.php?' + new URLSearchParams(currentFilters))
        .then(r => {
            if (!r.ok) throw new Error('Network response was not ok');
            return r.json();
        })
        .then(data => {
            console.log('Custom analytics data:', data);

            if (!data.success) {
                output.innerHTML =
                    `<div class="alert alert-danger">${data.error || 'Error loading analytics'}</div>`;
                return;
            }

            const delayByRegion       = data.data.delayByRegion || [];
            const disruptionsByCat    = data.data.disruptionsByCategory || [];

            // Clear text area; we'll put a small summary there
            output.innerHTML = '';

            const ctx1 = canvas1.getContext('2d');
            const ctx2 = canvas2.getContext('2d');

            // Destroy existing charts if they exist
            if (window.customChart1 && typeof window.customChart1.destroy === 'function') {
                window.customChart1.destroy();
            }
            if (window.customChart2 && typeof window.customChart2.destroy === 'function') {
                window.customChart2.destroy();
            }

            // -------------------------------
            // Chart 1: Avg delay by region
            // -------------------------------
            if (!delayByRegion.length) {
                ctx1.clearRect(0, 0, canvas1.width, canvas1.height);
                ctx1.fillText('No shipment delay data for selected filters', 30, 40);
            } else {
                const labels1   = delayByRegion.map(r => r.Region || 'Unknown');
                const avgDelay  = delayByRegion.map(r => parseFloat(r.AvgDelayDays || 0));
                const counts    = delayByRegion.map(r => parseInt(r.ShipmentCount || 0));

                window.customChart1 = new Chart(ctx1, {
                    type: 'bar',
                    data: {
                        labels: labels1,
                        datasets: [{
                            label: 'Average Delivery Delay (days)',
                            data: avgDelay,
                            backgroundColor: 'rgba(54, 162, 235, 0.6)',
                            borderColor: 'rgba(54, 162, 235, 1)',
                            borderWidth: 1
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        scales: {
                            y: {
                                beginAtZero: true,
                                title: { display: true, text: 'Avg Delay (days)' }
                            },
                            x: {
                                title: { display: true, text: 'Region' }
                            }
                        },
                        plugins: {
                            legend: { display: true, position: 'top' },
                            tooltip: {
                                callbacks: {
                                    footer: function (ctx) {
                                        const idx = ctx[0].dataIndex;
                                        return 'Shipments: ' + counts[idx];
                                    }
                                }
                            }
                        }
                    }
                });
            }

            // ----------------------------------------
            // Chart 2: Disruptions by category & impact
            // ----------------------------------------
            if (!disruptionsByCat.length) {
                ctx2.clearRect(0, 0, canvas2.width, canvas2.height);
                ctx2.fillText('No disruption data for selected filters', 30, 40);
            } else {
                const labels2  = disruptionsByCat.map(r => r.CategoryName || 'Unknown');
                const high     = disruptionsByCat.map(r => parseInt(r.HighImpact   || 0));
                const medium   = disruptionsByCat.map(r => parseInt(r.MediumImpact || 0));
                const low      = disruptionsByCat.map(r => parseInt(r.LowImpact    || 0));
                const totals   = disruptionsByCat.map(r => parseInt(r.TotalEvents  || 0));

                window.customChart2 = new Chart(ctx2, {
                    type: 'bar',
                    data: {
                        labels: labels2,
                        datasets: [
                            {
                                label: 'High Impact',
                                data: high,
                                backgroundColor: 'rgba(255, 99, 132, 0.7)',
                                borderColor: 'rgba(255, 99, 132, 1)',
                                borderWidth: 1
                            },
                            {
                                label: 'Medium Impact',
                                data: medium,
                                backgroundColor: 'rgba(255, 206, 86, 0.7)',
                                borderColor: 'rgba(255, 206, 86, 1)',
                                borderWidth: 1
                            },
                            {
                                label: 'Low Impact',
                                data: low,
                                backgroundColor: 'rgba(75, 192, 192, 0.7)',
                                borderColor: 'rgba(75, 192, 192, 1)',
                                borderWidth: 1
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        scales: {
                            x: {
                                stacked: true,
                                title: { display: true, text: 'Disruption Category' }
                            },
                            y: {
                                stacked: true,
                                beginAtZero: true,
                                title: { display: true, text: 'Number of Events' }
                            }
                        },
                        plugins: {
                            legend: { display: true, position: 'top' },
                            tooltip: {
                                callbacks: {
                                    footer: function (ctx) {
                                        const idx = ctx[0].dataIndex;
                                        return 'Total events: ' + totals[idx];
                                    }
                                }
                            }
                        }
                    }
                });
            }

            // Small text summary below the charts
            let summaryHtml = '<h5>Summary</h5><ul>';

            if (delayByRegion.length) {
                const worst = delayByRegion[0]; // sorted in PHP desc by delay
                summaryHtml += `<li><strong>Highest average delay</strong> in <strong>${worst.Region}</strong>: ${parseFloat(worst.AvgDelayDays).toFixed(2)} days.</li>`;
            }
            if (disruptionsByCat.length) {
                const topCat = disruptionsByCat[0]; // sorted in PHP desc by TotalEvents
                summaryHtml += `<li><strong>Most frequent disruption category</strong>: <strong>${topCat.CategoryName}</strong> (${topCat.TotalEvents} events).</li>`;
            }

            summaryHtml += '</ul>';
            output.innerHTML = summaryHtml;
        })
        .catch(err => {
            console.error('Error loading custom analytics:', err);
            output.innerHTML =
                `<div class="alert alert-danger">Error loading analytics: ${err.message}</div>`;
        });
}
