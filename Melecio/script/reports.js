// Global variables
let currentStartDate = moment().subtract(30, 'days').format('YYYY-MM-DD');
let currentEndDate = moment().format('YYYY-MM-DD');

// Initialize page
document.addEventListener('DOMContentLoaded', () => {
    loadReportData();
    setupEventListeners();
    setupTableFilters();
});

// Setup event listeners
function setupEventListeners() {
    // Quick filter buttons
    document.querySelectorAll('.quick-filter-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            document.querySelectorAll('.quick-filter-btn').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            
            const days = btn.dataset.days;
            if (days === 'all') {
                currentStartDate = '2000-01-01';
                currentEndDate = moment().format('YYYY-MM-DD');
            } else {
                currentStartDate = moment().subtract(parseInt(days), 'days').format('YYYY-MM-DD');
                currentEndDate = moment().format('YYYY-MM-DD');
            }
            
            document.getElementById('startDate').value = currentStartDate;
            document.getElementById('endDate').value = currentEndDate;
            loadReportData();
        });
    });

    // Apply custom range
    document.getElementById('applyCustomRange').addEventListener('click', () => {
        const start = document.getElementById('startDate').value;
        const end = document.getElementById('endDate').value;
        
        if (start && end) {
            currentStartDate = start;
            currentEndDate = end;
            loadReportData();
        }
    });

    // Export buttons
    document.querySelectorAll('.btn-export').forEach(btn => {
        btn.addEventListener('click', () => {
            document.getElementById('exportModal').style.display = 'flex';
        });
    });

    // Modal close buttons
    document.getElementById('closeExportModal').addEventListener('click', () => {
        document.getElementById('exportModal').style.display = 'none';
    });

    document.getElementById('cancelExport').addEventListener('click', () => {
        document.getElementById('exportModal').style.display = 'none';
    });

    document.getElementById('confirmExport').addEventListener('click', exportData);
}

// Setup table filters
function setupTableFilters() {
    const roleFilter = document.getElementById('userRoleFilter');
    const statusFilter = document.getElementById('userStatusFilter');
    const searchFilter = document.getElementById('userSearchFilter');
    
    if (roleFilter) {
        roleFilter.addEventListener('change', applyTableFilters);
    }
    
    if (statusFilter) {
        statusFilter.addEventListener('change', applyTableFilters);
    }
    
    if (searchFilter) {
        searchFilter.addEventListener('input', debounce(applyTableFilters, 300));
    }
}

// Debounce function for search input
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

// Apply filters to user summary table
function applyTableFilters() {
    const roleFilter = document.getElementById('userRoleFilter')?.value || 'all';
    const statusFilter = document.getElementById('userStatusFilter')?.value || 'all';
    const searchFilter = document.getElementById('userSearchFilter')?.value?.toLowerCase() || '';
    
    const tbody = document.getElementById('userSummaryBody');
    const rows = tbody.querySelectorAll('tr');
    
    rows.forEach(row => {
        if (row.classList.contains('no-data') || row.classList.contains('loading-message') || row.classList.contains('error-message')) {
            return;
        }
        
        let showRow = true;
        
        // Role filter
        if (roleFilter !== 'all') {
            const roleCell = row.querySelector('td:nth-child(2) span');
            if (roleCell) {
                const roleText = roleCell.textContent.toLowerCase().replace(/\s+/g, '_');
                if (roleText !== roleFilter) {
                    showRow = false;
                }
            }
        }
        
        // Status filter
        if (showRow && statusFilter !== 'all') {
            const statusCell = row.querySelector('td:nth-child(7) span');
            if (statusCell) {
                const statusText = statusCell.textContent.toLowerCase();
                if (statusFilter === 'active' && !statusText.includes('active')) {
                    showRow = false;
                } else if (statusFilter === 'inactive' && !statusText.includes('inactive')) {
                    showRow = false;
                }
            }
        }
        
        // Search filter
        if (showRow && searchFilter) {
            const usernameCell = row.querySelector('td:first-child');
            if (usernameCell) {
                const username = usernameCell.textContent.toLowerCase();
                if (!username.includes(searchFilter)) {
                    showRow = false;
                }
            }
        }
        
        row.style.display = showRow ? '' : 'none';
    });
}

// Load all report data
async function loadReportData() {
    showLoading();
    
    try {
        const params = new URLSearchParams({
            start_date: currentStartDate,
            end_date: currentEndDate
        });

        console.log('Fetching data from:', `get_report_data.php?${params}`);
        
        const response = await fetch(`get_report_data.php?${params}`);
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const data = await response.json();
        console.log('Data received:', data);

        if (data.success) {
            updateKPIs(data.kpis);
            updateTopUsers(data.topUsers);
            updateUserSummary(data.userSummary);
            updateSessionsTable(data.recentSessions);
            updateHeatmap(data.peakHours);
            
            // Apply any existing filters after data is loaded
            setTimeout(applyTableFilters, 100);
        } else {
            console.error('Server error:', data.message);
            showError('Failed to load report data: ' + (data.message || 'Unknown error'));
            
            // Show error in tables
            document.getElementById('userSummaryBody').innerHTML = 
                '<tr><td colspan="7" class="error-message"><i class="fas fa-exclamation-circle"></i> Error loading data</td></tr>';
            document.getElementById('sessionsTableBody').innerHTML = 
                '<tr><td colspan="6" class="error-message"><i class="fas fa-exclamation-circle"></i> Error loading data</td></tr>';
        }
    } catch (error) {
        console.error('Fetch error:', error);
        showError('Error loading report data: ' + error.message);
        
        // Show error in tables
        document.getElementById('userSummaryBody').innerHTML = 
            '<tr><td colspan="7" class="error-message"><i class="fas fa-exclamation-circle"></i> Connection error</td></tr>';
        document.getElementById('sessionsTableBody').innerHTML = 
            '<tr><td colspan="6" class="error-message"><i class="fas fa-exclamation-circle"></i> Connection error</td></tr>';
    }
}

// Show error message
function showError(message) {
    console.error(message);
    
    // Create toast if it doesn't exist
    let errorToast = document.getElementById('errorToast');
    if (!errorToast) {
        errorToast = document.createElement('div');
        errorToast.id = 'errorToast';
        errorToast.className = 'error-toast';
        document.body.appendChild(errorToast);
    }
    
    errorToast.innerHTML = `<i class="fas fa-exclamation-circle"></i> ${message}`;
    errorToast.classList.add('show');
    
    setTimeout(() => {
        errorToast.classList.remove('show');
    }, 5000);
}

// Update KPI cards
function updateKPIs(kpis) {
    document.getElementById('activeUsers').textContent = kpis.activeUsers || 0;
    document.getElementById('totalSessions').textContent = kpis.totalSessions || 0;
    document.getElementById('totalMinutes').textContent = formatMinutes(kpis.totalMinutes || 0);
    document.getElementById('avgSession').textContent = formatMinutes(kpis.avgSession || 0);

    // Update trends
    updateTrend('userTrend', kpis.userTrend);
    updateTrend('sessionTrend', kpis.sessionTrend);
    updateTrend('minutesTrend', kpis.minutesTrend);
    updateTrend('avgTrend', kpis.avgTrend);
}

function updateTrend(elementId, trend) {
    const element = document.getElementById(elementId);
    if (!element) return;

    if (trend > 0) {
        element.innerHTML = `<i class="fas fa-arrow-up"></i> +${trend}% vs previous`;
        element.className = 'kpi-trend trend-up';
    } else if (trend < 0) {
        element.innerHTML = `<i class="fas fa-arrow-down"></i> ${trend}% vs previous`;
        element.className = 'kpi-trend trend-down';
    } else {
        element.innerHTML = '<i class="fas fa-minus"></i> No change vs previous';
        element.className = 'kpi-trend';
    }
}

// Update heatmap
function updateHeatmap(peakHours) {
    const heatmap = document.getElementById('peakHoursHeatmap');
    if (!heatmap) return;

    let html = '<div class="heatmap-container">';
    
    // Add hour labels
    for (let hour = 0; hour < 24; hour++) {
        const intensity = peakHours[hour] || 0;
        const color = getHeatmapColor(intensity);
        
        html += `
            <div class="heatmap-hour">
                <div class="heatmap-cell" style="background: ${color};" 
                     title="${hour}:00 - ${hour+1}:00: ${intensity} sessions"></div>
                <span class="heatmap-label">${hour}:00</span>
            </div>
        `;
    }
    
    html += '</div>';
    heatmap.innerHTML = html;
}

// Update top users list
function updateTopUsers(users) {
    const container = document.getElementById('topUsersList');
    if (!container || !users.length) {
        container.innerHTML = '<div class="no-data">No data available</div>';
        return;
    }

    let html = '';
    users.slice(0, 5).forEach((user, index) => {
        const medal = index === 0 ? '🥇' : index === 1 ? '🥈' : index === 2 ? '🥉' : '';
        html += `
            <div class="top-user-item">
                <div class="user-rank">${medal || index + 1}</div>
                <div class="user-info">
                    <span class="user-name">${escapeHtml(user.username)}</span>
                    <div class="user-stats">
                        <span><i class="fas fa-clock"></i> ${formatMinutes(user.total_time)}</span>
                        <span><i class="fas fa-calendar"></i> ${user.sessions} sessions</span>
                    </div>
                </div>
                <div class="user-progress">
                    <span class="progress-value">${user.avg_time} min</span>
                    <small>avg</small>
                </div>
            </div>
        `;
    });

    container.innerHTML = html;
}

// Update user summary table
function updateUserSummary(users) {
    const tbody = document.getElementById('userSummaryBody');
    if (!users.length) {
        tbody.innerHTML = '<tr><td colspan="7" class="no-data">No users found</td></tr>';
        return;
    }

    let html = '';
    users.forEach(user => {
        const status = getStatusClass(user.last_active);
        const roleClass = getRoleClass(user.role);
        
        html += `
            <tr>
                <td><i class="fas fa-user-circle" style="color: var(--primary-light);"></i> ${escapeHtml(user.username)}</td>
                <td><span class="role-badge-sm ${roleClass}">${user.role}</span></td>
                <td>${user.sessions}</td>
                <td>${formatMinutes(user.total_time)}</td>
                <td>${formatMinutes(user.avg_time)}</td>
                <td>${user.last_active}</td>
                <td><span class="status-badge ${status.class}"><i class="fas fa-circle"></i> ${status.text}</span></td>
            </tr>
        `;
    });

    tbody.innerHTML = html;
}

// Update recent sessions table
function updateSessionsTable(sessions) {
    const tbody = document.getElementById('sessionsTableBody');
    if (!sessions.length) {
        tbody.innerHTML = '<tr><td colspan="6" class="no-data">No sessions found</td></tr>';
        return;
    }

    let html = '';
    sessions.forEach(session => {
        html += `
            <tr>
                <td><strong>${escapeHtml(session.username)}</strong></td>
                <td>${escapeHtml(session.session_name)}</td>
                <td>${formatMinutes(session.time_spent)}</td>
                <td>${session.created_at}</td>
                <td><span class="feeling-badge">${getFeelingEmoji(session.feeling)} ${escapeHtml(session.feeling)}</span></td>
                <td><span class="location-badge">${getLocationEmoji(session.location)} ${escapeHtml(session.location)}</span></td>
            </tr>
        `;
    });

    tbody.innerHTML = html;
}

// Export data
function exportData() {
    const selectedData = [];
    document.querySelectorAll('.export-option input:checked').forEach(cb => {
        selectedData.push(cb.value);
    });

    const format = document.querySelector('input[name="exportFormat"]:checked').value;

    // Build export URL
    const params = new URLSearchParams({
        start_date: currentStartDate,
        end_date: currentEndDate,
        data: selectedData.join(','),
        format: format
    });

    window.location.href = `export_report.php?${params}`;
    document.getElementById('exportModal').style.display = 'none';
}

// Helper functions
function formatMinutes(seconds) {
    if (!seconds) return '0 min';
    const mins = Math.floor(seconds / 60);
    const secs = seconds % 60;
    if (mins === 0) return `${secs} sec`;
    if (secs === 0) return `${mins} min`;
    return `${mins} min ${secs} sec`;
}

function getStatusClass(lastActive) {
    if (!lastActive || lastActive === 'Never') {
        return { class: 'status-inactive', text: 'Inactive' };
    }
    
    const last = new Date(lastActive).getTime();
    const now = new Date().getTime();
    const diff = now - last;
    
    if (diff < 5 * 60 * 1000) { // 5 minutes
        return { class: 'status-active', text: 'Active Now' };
    } else if (diff < 24 * 60 * 60 * 1000) { // 24 hours
        return { class: 'status-active', text: 'Active Today' };
    } else {
        return { class: 'status-inactive', text: 'Inactive' };
    }
}

function getRoleClass(role) {
    switch(role?.toLowerCase()) {
        case 'super_admin': return 'super-admin';
        case 'admin': return 'admin';
        default: return 'user';
    }
}

function getHeatmapColor(intensity) {
    const max = 100;
    const percentage = Math.min(intensity / max, 1);
    
    if (percentage === 0) return 'rgba(255, 255, 255, 0.05)';
    if (percentage < 0.2) return 'rgba(76, 175, 80, 0.2)';
    if (percentage < 0.4) return 'rgba(76, 175, 80, 0.4)';
    if (percentage < 0.6) return 'rgba(255, 152, 0, 0.4)';
    if (percentage < 0.8) return 'rgba(255, 87, 34, 0.6)';
    return 'rgba(244, 67, 54, 0.8)';
}

function getFeelingEmoji(feeling) {
    const emojis = {
        'Happy': '😊',
        'Distracted': '😅',
        'Boring': '😐',
        'Normal': '🙂'
    };
    return emojis[feeling] || '😊';
}

function getLocationEmoji(location) {
    const emojis = {
        'Home': '🏠',
        'Work': '🏢',
        'Center': '🧘',
        'Outside': '🌿'
    };
    return emojis[location] || '📍';
}

function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function showLoading() {
    // Show loading indicators if needed
}

// Set initial dates
document.getElementById('startDate').value = currentStartDate;
document.getElementById('endDate').value = currentEndDate;