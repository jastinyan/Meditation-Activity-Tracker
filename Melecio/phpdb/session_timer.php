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
<title>Session Timer</title>
<link rel="stylesheet" href="../css/session_timer.css">
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
    <a href="session_timer.php" style="background: rgb(181,65,7);">Session Timer</a>
    <a href="historysession.php">Past Sessions</a>
    <a href="progress.php">Session Progress</a>
    <a href="profile.php">Profile</a>
    <a href="homepage.php" class="btn-logout">Logout</a>
</div>

<!-- MAIN CONTENT -->
<div class="main">
    <h2>Meditation Session Timer</h2>

    <div class="timer-container">

        <!-- CIRCLE TIMER -->
        <div class="timer-circle" id="timerCircle">
            <div class="timer-inner">
                <div class="timer-display" id="timerDisplay">01:00</div>
                <div class="timer-label">Countdown</div>
            </div>
        </div>

        <!-- SET MINUTES -->
        <div class="timer-settings">
            <button id="minusBtn">−</button>
            <span id="minuteText">1 Minutes</span>
            <button id="plusBtn">+</button>
        </div>

        <!-- PLAY / PAUSE -->
        <button class="play-btn" id="playPauseBtn">▶ Play</button>

        <!-- DISCARD / SAVE -->
        <div class="timer-actions">
            <button class="btn-discard" id="discardBtn">Discard</button>
            <button class="btn-save" id="saveBtn">Save</button>
        </div>

        <!-- SESSION HISTORY TABLE -->
        <div class="saved-session" id="historyBox">
            <h3>Recent Sessions</h3>  <!-- Added "Last 5" to title -->
            <table id="historyTable">
                <thead>
                    <tr>
                        <th>Session</th>
                        <th>Time</th>
                        <th>Date & Time</th>
                        <th>Feeling</th>
                        <th>Location</th>
                        <th>Notes</th>  <!-- Added Notes column -->
                    </tr>
                </thead>
                <tbody id="historyList"></tbody>
            </table>
        </div>

    </div>
</div>

<!-- MODAL -->
<div class="modal" id="detailsModal">
    <div class="modal-content">
        <span class="close-btn" id="closeDetailsBtn">&times;</span>
        <h3>Session Saved</h3>

        <div class="session-details">
            <label>Session Name:</label>
            <select id="modalSavedName">
                <option value="Mindfulness Meditation">Mindfulness Meditation</option>
                <option value="Breathing Meditation">Breathing Meditation</option>
                <option value="Zen Meditation">Zen Meditation</option>
                <option value="Guided Meditation">Guided Meditation</option>
                <option value="Body Scan Meditation">Body Scan Meditation</option>
                <option value="Loving-Kindness Meditation">Loving-Kindness Meditation</option>
                <option value="Custom">Custom (Type your own)</option>
            </select>
            <input type="text" id="customSessionName" class="modern-input" placeholder="Enter custom session name" style="display:none;">

            <input type="text" id="customSessionName" placeholder="Enter custom session name" style="display:none; margin-top: 10px;">

            <label>Time Spent:</label>
            <input type="text" id="modalSavedTime" readonly>

            <label>Date & Time:</label>
            <input type="text" id="modalSavedDateTime" readonly>

            <label>Feeling:</label>
            <div class="emoji-group" id="modalFeelingGroup">
                <div class="emoji-btn" data-value="Happy"><span>😊</span>Happy</div>
                <div class="emoji-btn" data-value="Distracted"><span>😅</span>Distracted</div>
                <div class="emoji-btn" data-value="Boring"><span>😐</span>Boring</div>
                <div class="emoji-btn" data-value="Normal"><span>🙂</span>Normal</div>
            </div>

            <label>Location:</label>
            <div class="emoji-group" id="modalLocationGroup">
                <div class="emoji-btn" data-value="Home"><span>🏠</span>Home</div>
                <div class="emoji-btn" data-value="Work"><span>🏢</span>Work</div>
                <div class="emoji-btn" data-value="Center"><span>🧘</span>Center</div>
                <div class="emoji-btn" data-value="Outside"><span>🌿</span>Outside</div>
            </div>

            <label>Notes:</label>
            <textarea id="modalSavedNotes" rows="3"></textarea>
        </div>

        <button class="btn-save" id="saveModalChangesBtn">Save Changes</button>
    </div>
</div>

<div class="success-toast" id="successToast">
    ✔ Session Saved Successfully
</div>

<!-- FOOTER -->
<div class="footer">
    <p class="all">&copy; 2026 Meditation Activity Tracker. All rights reserved.</p>
</div>

<script src="../script/session_timer.js"></script>
</body>
</html>
