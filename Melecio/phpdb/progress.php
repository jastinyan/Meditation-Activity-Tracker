<?php
session_start();
require 'db_connection.php';

if (!isset($_SESSION['id_no'])) {
    header("Location: login.php");
    exit;
}

$username = $_SESSION['username'];
$id_no = $_SESSION['id_no'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Progress Dashboard - Meditation Activity Tracker</title>
    <link rel="stylesheet" href="../css/progress.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-adapter-date-fns/dist/chartjs-adapter-date-fns.bundle.min.js"></script>
</head>
<body>

<!-- NAVBAR -->
<div class="navbar">
    <p class="system-title container">Meditation Activity Tracker</p>
</div>

<!-- SIDEBAR -->
<div class="sidebar">
    <div class="sidebar-header">Welcome <?= htmlspecialchars($username) ?></div>
    <a href="user_home.php">Your Calendar</a>
    <a href="session_timer.php">Session Timer</a>
    <a href="historysession.php">Past Sessions</a>
    <a href="progress.php" style="background: rgb(181,65,7);">Session Progress</a>
    <a href="profile.php">Profile</a>
    <a href="homepage.php" class="btn-logout">Logout</a>
</div>

<!-- MAIN CONTENT -->
<div class="main">
    <h2><i class="fas fa-chart-line"></i> Your Meditation Progress</h2>
    
    <!-- Date Range Filter -->
    <div class="filter-section">
        <div class="date-range">
            <div class="date-input">
                <label>From:</label>
                <input type="date" id="startDate" class="date-picker">
            </div>
            <div class="date-input">
                <label>To:</label>
                <input type="date" id="endDate" class="date-picker">
            </div>
            <button id="applyDateFilter" class="btn-filter">
                <i class="fas fa-filter"></i> Apply
            </button>
            <button id="resetDateFilter" class="btn-reset">
                <i class="fas fa-undo"></i> Reset
            </button>
        </div>
        
        <div class="quick-filters">
            <button class="quick-filter-btn active" data-days="7">Last 7 Days</button>
            <button class="quick-filter-btn" data-days="30">Last 30 Days</button>
            <button class="quick-filter-btn" data-days="90">Last 3 Months</button>
            <button class="quick-filter-btn" data-days="365">Last Year</button>
            <button class="quick-filter-btn" data-days="all">All Time</button>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="stats-grid">
        <div class="stat-card primary">
            <div class="stat-icon">
                <i class="fas fa-clock"></i>
            </div>
            <div class="stat-content">
                <span class="stat-label">Total Time</span>
                <span class="stat-value" id="totalTime">0</span>
                <span class="stat-trend" id="totalTimeTrend">
                    <i class="fas fa-minus"></i>
                </span>
            </div>
        </div>
        
        <div class="stat-card success">
            <div class="stat-icon">
                <i class="fas fa-calendar-check"></i>
            </div>
            <div class="stat-content">
                <span class="stat-label">Total Sessions</span>
                <span class="stat-value" id="totalSessions">0</span>
                <span class="stat-trend" id="totalSessionsTrend">
                    <i class="fas fa-minus"></i>
                </span>
            </div>
        </div>
        
        <div class="stat-card warning">
            <div class="stat-icon">
                <i class="fas fa-hourglass-half"></i>
            </div>
            <div class="stat-content">
                <span class="stat-label">Average Session</span>
                <span class="stat-value" id="avgSession">0</span>
                <span class="stat-trend" id="avgSessionTrend">
                    <i class="fas fa-minus"></i>
                </span>
            </div>
        </div>
        
        <div class="stat-card info">
            <div class="stat-icon">
                <i class="fas fa-fire"></i>
            </div>
            <div class="stat-content">
                <span class="stat-label">Current Streak</span>
                <span class="stat-value" id="currentStreak">0</span>
                <span class="stat-trend">days</span>
            </div>
        </div>
        
        <div class="stat-card purple">
            <div class="stat-icon">
                <i class="fas fa-trophy"></i>
            </div>
            <div class="stat-content">
                <span class="stat-label">Longest Streak</span>
                <span class="stat-value" id="longestStreak">0</span>
                <span class="stat-trend">days</span>
            </div>
        </div>
        
        <div class="stat-card orange">
            <div class="stat-icon">
                <i class="fas fa-star"></i>
            </div>
            <div class="stat-content">
                <span class="stat-label">Consistency</span>
                <span class="stat-value" id="consistencyScore">0%</span>
                <span class="stat-trend" id="consistencyTrend">
                    <i class="fas fa-minus"></i>
                </span>
            </div>
        </div>
    </div>

    <!-- Charts Row 1 -->
    <div class="charts-row">
        <div class="chart-container">
            <div class="chart-header">
                <h3><i class="fas fa-calendar-alt"></i> Daily Meditation Time</h3>
                <div class="chart-legend" id="dailyLegend"></div>
            </div>
            <div class="chart-wrapper">
                <canvas id="dailyTimeChart"></canvas>
            </div>
        </div>
        
        <div class="chart-container">
            <div class="chart-header">
                <h3><i class="fas fa-chart-pie"></i> Feelings Distribution</h3>
                <div class="chart-legend" id="feelingsLegend"></div>
            </div>
            <div class="chart-wrapper">
                <canvas id="feelingsChart"></canvas>
            </div>
        </div>
    </div>

    <!-- Charts Row 2 -->
    <div class="charts-row">
        <div class="chart-container full-width">
            <div class="chart-header">
                <h3><i class="fas fa-chart-bar"></i> Weekly Comparison</h3>
                <div class="chart-legend" id="weeklyLegend"></div>
            </div>
            <div class="chart-wrapper">
                <canvas id="weeklyChart"></canvas>
            </div>
        </div>
    </div>

    <!-- Charts Row 3 -->
    <div class="charts-row">
        <div class="chart-container full-width">
            <div class="chart-header">
                <h3><i class="fas fa-heart"></i> Mood Timeline</h3>
                <div class="chart-legend" id="moodLegend"></div>
            </div>
            <div class="chart-wrapper">
                <canvas id="moodChart"></canvas>
            </div>
        </div>
    </div>

    <!-- Insights Section -->
    <div class="insights-section">
        <h3><i class="fas fa-lightbulb"></i> Insights & Recommendations</h3>
        <div class="insights-grid" id="insightsContainer">
            <!-- Insights will be loaded here -->
        </div>
    </div>

    <!-- Achievements Section -->
    <div class="achievements-section">
        <h3><i class="fas fa-medal"></i> Achievements</h3>
        <div class="achievements-grid" id="achievementsContainer">
            <!-- Achievements will be loaded here -->
        </div>
    </div>
</div>

<!-- FOOTER -->
<div class="footer">
    <p class="all">&copy; 2026 Meditation Activity Tracker. All rights reserved.</p>
</div>

<script src="../script/progress.js"></script>
</body>
</html>