let dailyChart, feelingsChart, weeklyChart, moodChart;
let currentData = null;

// Date range
const startDateInput = document.getElementById('startDate');
const endDateInput = document.getElementById('endDate');
const applyFilterBtn = document.getElementById('applyDateFilter');
const resetFilterBtn = document.getElementById('resetDateFilter');
const quickFilterBtns = document.querySelectorAll('.quick-filter-btn');

// Set default dates
const today = new Date();
const thirtyDaysAgo = new Date();
thirtyDaysAgo.setDate(today.getDate() - 30);

startDateInput.value = formatDate(thirtyDaysAgo);
endDateInput.value = formatDate(today);

// Initialize
document.addEventListener('DOMContentLoaded', () => {
    loadProgressData();
    setupEventListeners();
});

function setupEventListeners() {
    applyFilterBtn.addEventListener('click', () => {
        loadProgressData();
    });
    
    resetFilterBtn.addEventListener('click', () => {
        startDateInput.value = formatDate(thirtyDaysAgo);
        endDateInput.value = formatDate(today);
        loadProgressData();
    });
    
    quickFilterBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            quickFilterBtns.forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            
            const days = btn.dataset.days;
            if (days === 'all') {
                startDateInput.value = '';
                endDateInput.value = formatDate(today);
            } else {
                const start = new Date();
                start.setDate(today.getDate() - parseInt(days));
                startDateInput.value = formatDate(start);
                endDateInput.value = formatDate(today);
            }
            loadProgressData();
        });
    });
    
    // Chart resize handler
    window.addEventListener('resize', () => {
        if (dailyChart) dailyChart.resize();
        if (feelingsChart) feelingsChart.resize();
        if (weeklyChart) weeklyChart.resize();
        if (moodChart) moodChart.resize();
    });
}

async function loadProgressData() {
    try {
        showLoading();
        
        const params = new URLSearchParams({
            start_date: startDateInput.value,
            end_date: endDateInput.value
        });
        
        const response = await fetch(`fetch_progress_data.php?${params}`);
        const data = await response.json();
        
        if (data.success) {
            currentData = data;
            updateStats(data.stats);
            updateCharts(data.charts);
            updateInsights(data.insights);
            updateAchievements(data.achievements);
        } else {
            showError('Failed to load progress data');
        }
    } catch (error) {
        console.error('Error:', error);
        showError('Error loading progress data');
    } finally {
        hideLoading();
    }
}

function updateStats(stats) {
    document.getElementById('totalTime').textContent = stats.totalTime;
    document.getElementById('totalSessions').textContent = stats.totalSessions;
    document.getElementById('avgSession').textContent = stats.avgSession;
    document.getElementById('currentStreak').textContent = stats.currentStreak;
    document.getElementById('longestStreak').textContent = stats.longestStreak;
    document.getElementById('consistencyScore').textContent = stats.consistencyScore + '%';
    
    // Update trends
    updateTrend('totalTimeTrend', stats.trends.totalTime);
    updateTrend('totalSessionsTrend', stats.trends.totalSessions);
    updateTrend('avgSessionTrend', stats.trends.avgSession);
    updateTrend('consistencyTrend', stats.trends.consistency);
}

function updateTrend(elementId, trend) {
    const element = document.getElementById(elementId);
    if (!element) return;
    
    element.innerHTML = '';
    element.className = 'stat-trend';
    
    if (trend.direction === 'up') {
        element.innerHTML = `<i class="fas fa-arrow-up"></i> +${trend.percentage}%`;
        element.classList.add('trend-up');
    } else if (trend.direction === 'down') {
        element.innerHTML = `<i class="fas fa-arrow-down"></i> -${trend.percentage}%`;
        element.classList.add('trend-down');
    } else {
        element.innerHTML = `<i class="fas fa-minus"></i> 0%`;
        element.classList.add('trend-flat');
    }
}

function updateCharts(charts) {
    // Daily Time Chart
    const dailyCtx = document.getElementById('dailyTimeChart').getContext('2d');
    if (dailyChart) dailyChart.destroy();
    
    dailyChart = new Chart(dailyCtx, {
        type: 'line',
        data: {
            labels: charts.daily.labels,
            datasets: [{
                label: 'Meditation Time (minutes)',
                data: charts.daily.values,
                borderColor: 'rgb(181, 65, 7)',
                backgroundColor: 'rgba(181, 65, 7, 0.1)',
                borderWidth: 3,
                pointBackgroundColor: 'rgb(255, 102, 0)',
                pointBorderColor: '#fff',
                pointRadius: 4,
                pointHoverRadius: 6,
                fill: true,
                tension: 0.4
            }]
        },
        options: getChartOptions('Daily Meditation Time', 'minutes')
    });

    // Feelings Distribution Chart
    const feelingsCtx = document.getElementById('feelingsChart').getContext('2d');
    if (feelingsChart) feelingsChart.destroy();
    
    feelingsChart = new Chart(feelingsCtx, {
        type: 'doughnut',
        data: {
            labels: charts.feelings.labels.map(l => getFeelingEmoji(l) + ' ' + l),
            datasets: [{
                data: charts.feelings.values,
                backgroundColor: [
                    '#4CAF50', // Happy
                    '#FF9800', // Distracted
                    '#9E9E9E', // Boring
                    '#2196F3'  // Normal
                ],
                borderWidth: 0
            }]
        },
        options: {
            ...getPieChartOptions('Feelings Distribution'),
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: { color: '#fff' }
                }
            }
        }
    });

    // Weekly Comparison Chart
    const weeklyCtx = document.getElementById('weeklyChart').getContext('2d');
    if (weeklyChart) weeklyChart.destroy();
    
    weeklyChart = new Chart(weeklyCtx, {
        type: 'bar',
        data: {
            labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
            datasets: [
                {
                    label: 'This Week',
                    data: charts.weekly.thisWeek,
                    backgroundColor: 'rgba(76, 175, 80, 0.7)',
                    borderColor: '#4CAF50',
                    borderWidth: 2,
                    borderRadius: 5
                },
                {
                    label: 'Last Week',
                    data: charts.weekly.lastWeek,
                    backgroundColor: 'rgba(255, 152, 0, 0.7)',
                    borderColor: '#FF9800',
                    borderWidth: 2,
                    borderRadius: 5
                }
            ]
        },
        options: {
            ...getChartOptions('Weekly Comparison', 'minutes'),
            responsive: true,
            maintainAspectRatio: false
        }
    });

    // Mood Timeline Chart
    if (charts.mood && charts.mood.length > 0) {
        const moodCtx = document.getElementById('moodChart').getContext('2d');
        if (moodChart) moodChart.destroy();
        
        const moodValues = charts.mood.map(m => {
            switch(m.feeling) {
                case 'Happy': return 4;
                case 'Normal': return 3;
                case 'Distracted': return 2;
                case 'Boring': return 1;
                default: return 0;
            }
        });
        
        moodChart = new Chart(moodCtx, {
            type: 'line',
            data: {
                labels: charts.mood.map(m => new Date(m.date).toLocaleDateString()),
                datasets: [{
                    label: 'Mood Score',
                    data: moodValues,
                    borderColor: 'rgb(33, 150, 243)',
                    backgroundColor: 'rgba(33, 150, 243, 0.1)',
                    borderWidth: 3,
                    pointBackgroundColor: ctx => {
                        const feeling = charts.mood[ctx.dataIndex].feeling;
                        switch(feeling) {
                            case 'Happy': return '#4CAF50';
                            case 'Normal': return '#2196F3';
                            case 'Distracted': return '#FF9800';
                            case 'Boring': return '#9E9E9E';
                            default: return '#fff';
                        }
                    },
                    pointRadius: 6,
                    fill: true,
                    tension: 0.4,
                    stepped: false
                }]
            },
            options: {
                ...getChartOptions('Mood Timeline', 'score'),
                scales: {
                    y: {
                        beginAtZero: true,
                        max: 5,
                        ticks: {
                            callback: function(value) {
                                const moods = ['', 'Boring', 'Distracted', 'Normal', 'Happy'];
                                return moods[value] || '';
                            },
                            color: '#fff'
                        },
                        grid: { color: 'rgba(255,255,255,0.1)' }
                    },
                    x: {
                        ticks: { color: '#fff' },
                        grid: { color: 'rgba(255,255,255,0.1)' }
                    }
                }
            }
        });
    }
}

function updateInsights(insights) {
    const container = document.getElementById('insightsContainer');
    if (!container) return;
    
    let html = '';
    insights.forEach(insight => {
        html += `
            <div class="insight-card ${insight.color}">
                <div class="insight-icon">
                    <i class="fas ${insight.icon}"></i>
                </div>
                <div class="insight-content">
                    <h4>${insight.title}</h4>
                    <p>${insight.description}</p>
                </div>
            </div>
        `;
    });
    
    container.innerHTML = html;
}

function updateAchievements(achievements) {
    const container = document.getElementById('achievementsContainer');
    if (!container) return;
    
    let html = '';
    achievements.sort((a, b) => (a.unlocked === b.unlocked) ? 0 : a.unlocked ? -1 : 1);
    
    achievements.forEach(achievement => {
        html += `
            <div class="achievement-card ${achievement.unlocked ? 'unlocked' : 'locked'}">
                <div class="achievement-icon">
                    <i class="fas ${achievement.icon}"></i>
                </div>
                <div class="achievement-info">
                    <h4>${achievement.name}</h4>
                    <p>${achievement.description}</p>
                    <div class="progress-bar">
                        <div class="progress-fill" style="width: ${achievement.progress}%"></div>
                    </div>
                    <span class="progress-text">${achievement.progress}%</span>
                </div>
            </div>
        `;
    });
    
    container.innerHTML = html;
}

function getChartOptions(title, yLabel) {
    return {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                display: false
            },
            tooltip: {
                mode: 'index',
                intersect: false,
                backgroundColor: 'rgba(12, 19, 25, 0.95)',
                titleColor: '#fff',
                bodyColor: '#fff',
                borderColor: 'rgb(181, 65, 7)',
                borderWidth: 2
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                title: {
                    display: true,
                    text: yLabel,
                    color: '#fff'
                },
                ticks: { color: '#fff' },
                grid: { color: 'rgba(255,255,255,0.1)' }
            },
            x: {
                ticks: { 
                    color: '#fff',
                    maxRotation: 45,
                    minRotation: 45
                },
                grid: { color: 'rgba(255,255,255,0.1)' }
            }
        }
    };
}

function getPieChartOptions(title) {
    return {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                labels: { color: '#fff' }
            },
            tooltip: {
                backgroundColor: 'rgba(12, 19, 25, 0.95)',
                titleColor: '#fff',
                bodyColor: '#fff',
                borderColor: 'rgb(181, 65, 7)',
                borderWidth: 2
            }
        }
    };
}

function getFeelingEmoji(feeling) {
    const emojis = {
        'Happy': '😊',
        'Distracted': '😅',
        'Boring': '😐',
        'Normal': '🙂'
    };
    return emojis[feeling] || '';
}

function formatDate(date) {
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');
    return `${year}-${month}-${day}`;
}

function showLoading() {
    document.body.style.cursor = 'wait';
}

function hideLoading() {
    document.body.style.cursor = 'default';
}

function showError(message) {
    console.error(message);
    // You can implement a toast notification here
}