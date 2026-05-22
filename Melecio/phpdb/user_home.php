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
    <title>User Dashboard</title>
    <link rel="stylesheet" href="../css/user_home.css">
</head>
<body>

<div class="navbar">
    <p class="system-title container">Meditation Activity Tracker</p>
</div>

<div class="sidebar">
    <div class="sidebar-header">Welcome <?= htmlspecialchars($username) ?></div>
    <a href="user_home.php" style="background: rgb(181,65,7);">Your Calendar</a>
    <a href="session_timer.php">Session Timer</a>
    <a href="historysession.php">Past Sessions</a>
    <a href="progress.php">Session Progress</a>
    <a href="profile.php">Profile</a>
    <a href="homepage.php" class="btn-logout">Logout</a>
</div>

<div class="main">
    <h2>Your Calendar</h2>

    <div class="calendar-container">
        <div class="calendar" id="calendar"></div>

        <button class="btn-add-session" id="openSessionFormBtn">+ Add a Session</button>
    </div>
</div>

<!-- ================= ADD SESSION MODAL ================= -->
<div class="modal" id="sessionModal">
    <div class="modal-content">
        <span class="close-btn" id="closeModalBtn">&times;</span>

        <h3>Add Meditation Session</h3>

        <form id="sessionForm">
            <label>Session Name:</label>
            <select id="session_name">
                <option value="Mindfulness Meditation">Mindfulness Meditation</option>
                <option value="Breathing Meditation">Breathing Meditation</option>
                <option value="Zen Meditation">Zen Meditation</option>
                <option value="Guided Meditation">Guided Meditation</option>
                <option value="Body Scan Meditation">Body Scan Meditation</option>
                <option value="Loving-Kindness Meditation">Loving-Kindness Meditation</option>
                <option value="Custom">Custom (Type your own)</option>
            </select>

            <input type="text" id="custom_session" placeholder="Enter custom session name" style="display:none;">

            <label>Date:</label>
            <input type="date" id="session_date" required>

            <label>Time:</label>
            <input type="time" id="session_time" required>

            <label>Color:</label>
            <input type="color" id="color" value="#ff6600" required>

            <button type="submit" class="btn-save">Save Session</button>
        </form>
    </div>
</div>

<!-- ================= VIEW DAY MODAL ================= -->
<div class="modal" id="viewModal">
    <div class="modal-content">
        <span class="close-btn" id="closeViewBtn">&times;</span>

        <h3>Scheduled Sessions</h3>
        <div id="viewSessionDetails"></div>
    </div>
</div>

<!-- ================= EDIT MODAL ================= -->
<div class="modal" id="editModal">
    <div class="modal-content">
        <span class="close-btn" id="closeEditBtn">&times;</span>

        <h3>Edit Session</h3>

        <form id="editForm">
            <input type="hidden" id="edit_id">

            <label>Session Name:</label>
            <input type="text" id="edit_session_name" required>

            <label>Date:</label>
            <input type="date" id="edit_session_date" required>

            <label>Time:</label>
            <input type="time" id="edit_session_time" required>

            <label>Color:</label>
            <input type="color" id="edit_color" required>

            <button type="submit" class="btn-save">Update Session</button>
        </form>
    </div>
</div>

<script src="../script/calendar.js"></script>

<div class="footer">
    <p class="all">&copy; 2026 Meditation Activity Tracker. All rights reserved.</p>
</div>

</body>
</html>
