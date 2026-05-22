<?php
session_start();
require 'db_connection.php';

header("Content-Type: application/json");

if (!isset($_SESSION['id_no'])) {
    echo json_encode(["success" => false, "message" => "Unauthorized"]);
    exit;
}

$id_no = $_SESSION['id_no'];

// Get date range from request
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d', strtotime('-30 days'));
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');

// Fetch all sessions within date range
$stmt = $conn->prepare("SELECT id, session_name, time_spent, feeling, location, notes, created_at 
                        FROM recorded_sessions 
                        WHERE id_no = ? AND DATE(created_at) BETWEEN ? AND ?
                        ORDER BY created_at ASC");
$stmt->bind_param("sss", $id_no, $start_date, $end_date);
$stmt->execute();
$result = $stmt->get_result();

$sessions = [];
$totalTime = 0;
$feelings = ['Happy' => 0, 'Distracted' => 0, 'Boring' => 0, 'Normal' => 0];
$dailyData = [];
$weeklyData = [];
$moodData = [];

while ($row = $result->fetch_assoc()) {
    $sessions[] = $row;
    $totalTime += $row['time_spent'];
    
    // Count feelings
    if (isset($feelings[$row['feeling']])) {
        $feelings[$row['feeling']]++;
    }
    
    // Daily data
    $date = date('Y-m-d', strtotime($row['created_at']));
    if (!isset($dailyData[$date])) {
        $dailyData[$date] = 0;
    }
    $dailyData[$date] += $row['time_spent'];
    
    // Mood data for timeline
    $moodData[] = [
        'date' => $row['created_at'],
        'feeling' => $row['feeling'],
        'duration' => $row['time_spent']
    ];
}

// Calculate streaks
$streakData = calculateStreaks($id_no, $conn);

// Calculate weekly comparison
$weeklyComparison = calculateWeeklyComparison($sessions, $start_date, $end_date);

// Generate insights
$insights = generateInsights($sessions, $totalTime, $feelings, $streakData);

// Get achievements
$achievements = getAchievements($sessions, $totalTime, $streakData, $feelings);

// Format daily data for chart
$dailyLabels = [];
$dailyValues = [];
foreach ($dailyData as $date => $time) {
    $dailyLabels[] = $date;
    $dailyValues[] = round($time / 60, 1); // Convert to minutes
}

// Calculate consistency score
$totalDays = count($dailyData);
$daysInRange = date_diff(date_create($start_date), date_create($end_date))->days + 1;
$consistencyScore = $daysInRange > 0 ? round(($totalDays / $daysInRange) * 100) : 0;

// Calculate previous period for trends
$daysInRange = date_diff(date_create($start_date), date_create($end_date))->days + 1;
$previousStart = date('Y-m-d', strtotime($start_date . ' - ' . $daysInRange . ' days'));
$previousEnd = date('Y-m-d', strtotime($start_date . ' - 1 day'));

$trends = calculateTrends($id_no, $conn, $start_date, $end_date, $previousStart, $previousEnd);

echo json_encode([
    "success" => true,
    "stats" => [
        "totalTime" => formatTime($totalTime),
        "totalTimeMinutes" => round($totalTime / 60, 1),
        "totalSessions" => count($sessions),
        "avgSession" => count($sessions) > 0 ? formatTime(floor($totalTime / count($sessions))) : "00:00",
        "avgSessionMinutes" => count($sessions) > 0 ? round(($totalTime / count($sessions)) / 60, 1) : 0,
        "currentStreak" => $streakData['current'],
        "longestStreak" => $streakData['longest'],
        "consistencyScore" => $consistencyScore,
        "trends" => $trends
    ],
    "charts" => [
        "daily" => [
            "labels" => $dailyLabels,
            "values" => $dailyValues
        ],
        "feelings" => [
            "labels" => array_keys($feelings),
            "values" => array_values($feelings)
        ],
        "weekly" => $weeklyComparison,
        "mood" => $moodData
    ],
    "insights" => $insights,
    "achievements" => $achievements
]);

function formatTime($seconds) {
    $hours = floor($seconds / 3600);
    $minutes = floor(($seconds % 3600) / 60);
    
    if ($hours > 0) {
        return $hours . "h " . $minutes . "m";
    }
    return $minutes . " min";
}

function calculateStreaks($id_no, $conn) {
    // Get all session dates
    $stmt = $conn->prepare("SELECT DISTINCT DATE(created_at) as session_date 
                            FROM recorded_sessions 
                            WHERE id_no = ? 
                            ORDER BY session_date DESC");
    $stmt->bind_param("s", $id_no);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $dates = [];
    while ($row = $result->fetch_assoc()) {
        $dates[] = $row['session_date'];
    }
    
    if (empty($dates)) {
        return ['current' => 0, 'longest' => 0];
    }
    
    $currentStreak = 0;
    $longestStreak = 0;
    $tempStreak = 1;
    
    // Calculate current streak
    $today = date('Y-m-d');
    $yesterday = date('Y-m-d', strtotime('-1 day'));
    
    if (in_array($today, $dates)) {
        $currentStreak = 1;
        $checkDate = $yesterday;
        while (in_array($checkDate, $dates)) {
            $currentStreak++;
            $checkDate = date('Y-m-d', strtotime($checkDate . ' -1 day'));
        }
    } elseif (in_array($yesterday, $dates)) {
        $currentStreak = 1;
        $checkDate = date('Y-m-d', strtotime('-2 days'));
        while (in_array($checkDate, $dates)) {
            $currentStreak++;
            $checkDate = date('Y-m-d', strtotime($checkDate . ' -1 day'));
        }
    }
    
    // Calculate longest streak
    for ($i = 0; $i < count($dates) - 1; $i++) {
        $current = strtotime($dates[$i]);
        $next = strtotime($dates[$i + 1]);
        $diff = ($current - $next) / (60 * 60 * 24);
        
        if ($diff == 1) {
            $tempStreak++;
        } else {
            $longestStreak = max($longestStreak, $tempStreak);
            $tempStreak = 1;
        }
    }
    $longestStreak = max($longestStreak, $tempStreak);
    
    return ['current' => $currentStreak, 'longest' => $longestStreak];
}

function calculateWeeklyComparison($sessions, $start_date, $end_date) {
    $weeklyData = [
        'thisWeek' => array_fill(0, 7, 0),
        'lastWeek' => array_fill(0, 7, 0)
    ];
    
    $thisWeekStart = date('Y-m-d', strtotime('monday this week'));
    $lastWeekStart = date('Y-m-d', strtotime('monday last week'));
    
    foreach ($sessions as $session) {
        $sessionDate = date('Y-m-d', strtotime($session['created_at']));
        $dayOfWeek = date('N', strtotime($sessionDate)) - 1; // 0 = Monday, 6 = Sunday
        
        if ($sessionDate >= $thisWeekStart) {
            $weeklyData['thisWeek'][$dayOfWeek] += $session['time_spent'];
        } elseif ($sessionDate >= $lastWeekStart && $sessionDate < $thisWeekStart) {
            $weeklyData['lastWeek'][$dayOfWeek] += $session['time_spent'];
        }
    }
    
    // Convert to minutes
    foreach ($weeklyData as &$week) {
        foreach ($week as &$value) {
            $value = round($value / 60, 1);
        }
    }
    
    return $weeklyData;
}

function calculateTrends($id_no, $conn, $currentStart, $currentEnd, $previousStart, $previousEnd) {
    // Get current period stats
    $stmt = $conn->prepare("SELECT COUNT(*) as count, SUM(time_spent) as total 
                            FROM recorded_sessions 
                            WHERE id_no = ? AND DATE(created_at) BETWEEN ? AND ?");
    $stmt->bind_param("sss", $id_no, $currentStart, $currentEnd);
    $stmt->execute();
    $current = $stmt->get_result()->fetch_assoc();
    
    // Get previous period stats
    $stmt = $conn->prepare("SELECT COUNT(*) as count, SUM(time_spent) as total 
                            FROM recorded_sessions 
                            WHERE id_no = ? AND DATE(created_at) BETWEEN ? AND ?");
    $stmt->bind_param("sss", $id_no, $previousStart, $previousEnd);
    $stmt->execute();
    $previous = $stmt->get_result()->fetch_assoc();
    
    $trends = [
        'totalTime' => calculateTrend($current['total'] ?? 0, $previous['total'] ?? 0),
        'totalSessions' => calculateTrend($current['count'] ?? 0, $previous['count'] ?? 0),
        'avgSession' => calculateTrend(
            ($current['count'] > 0 ? ($current['total'] / $current['count']) : 0),
            ($previous['count'] > 0 ? ($previous['total'] / $previous['count']) : 0)
        ),
        'consistency' => calculateTrend($current['count'] ?? 0, $previous['count'] ?? 0)
    ];
    
    return $trends;
}

function calculateTrend($current, $previous) {
    if ($previous == 0) {
        return ['direction' => 'up', 'percentage' => 100];
    }
    
    $change = (($current - $previous) / $previous) * 100;
    
    if ($change > 0) {
        return ['direction' => 'up', 'percentage' => round($change)];
    } elseif ($change < 0) {
        return ['direction' => 'down', 'percentage' => round(abs($change))];
    } else {
        return ['direction' => 'flat', 'percentage' => 0];
    }
}

function generateInsights($sessions, $totalTime, $feelings, $streakData) {
    $insights = [];
    
    // Most productive time
    $timeCounts = ['Morning' => 0, 'Afternoon' => 0, 'Evening' => 0, 'Night' => 0];
    foreach ($sessions as $session) {
        $hour = (int)date('H', strtotime($session['created_at']));
        if ($hour >= 5 && $hour < 12) $timeCounts['Morning']++;
        elseif ($hour >= 12 && $hour < 17) $timeCounts['Afternoon']++;
        elseif ($hour >= 17 && $hour < 21) $timeCounts['Evening']++;
        else $timeCounts['Night']++;
    }
    arsort($timeCounts);
    $bestTime = key($timeCounts);
    $insights[] = [
        'icon' => 'fa-clock',
        'title' => 'Best Time to Meditate',
        'description' => "You're most active during $bestTime. Try to schedule sessions during this time for better consistency.",
        'color' => 'primary'
    ];
    
    // Feeling trend
    $positiveFeelings = $feelings['Happy'] + $feelings['Normal'];
    $totalFeelings = array_sum($feelings);
    if ($totalFeelings > 0) {
        $positivePercentage = round(($positiveFeelings / $totalFeelings) * 100);
        $insights[] = [
            'icon' => 'fa-smile',
            'title' => 'Mood Improvement',
            'description' => "$positivePercentage% of your sessions end with positive feelings. Keep up the good work!",
            'color' => 'success'
        ];
    }
    
    // Streak encouragement
    if ($streakData['current'] > 0) {
        $insights[] = [
            'icon' => 'fa-fire',
            'title' => 'Current Streak',
            'description' => "You're on a {$streakData['current']}-day streak! " . 
                            ($streakData['current'] >= 7 ? "Amazing consistency!" : "Try to make it to 7 days!"),
            'color' => 'warning'
        ];
    }
    
    // Total time milestone
    $totalMinutes = round($totalTime / 60);
    if ($totalMinutes > 0) {
        $milestone = ceil($totalMinutes / 100) * 100;
        $progress = ($totalMinutes / $milestone) * 100;
        $insights[] = [
            'icon' => 'fa-trophy',
            'title' => 'Time Milestone',
            'description' => "You've meditated for $totalMinutes minutes. " .
                            ($progress < 100 ? "$milestone minutes is your next milestone!" : "You've reached a milestone!"),
            'color' => 'purple'
        ];
    }
    
    return $insights;
}

function getAchievements($sessions, $totalTime, $streakData, $feelings) {
    $achievements = [];
    $totalMinutes = round($totalTime / 60);
    $totalSessions = count($sessions);
    
    // Time-based achievements
    $timeAchievements = [
        100 => ['name' => 'Century Club', 'desc' => 'Meditated for 100 minutes total', 'icon' => 'fa-clock'],
        500 => ['name' => 'Dedicated Meditator', 'desc' => 'Meditated for 500 minutes total', 'icon' => 'fa-hourglass-half'],
        1000 => ['name' => 'Meditation Master', 'desc' => 'Meditated for 1000 minutes total', 'icon' => 'fa-crown'],
        5000 => ['name' => 'Enlightened', 'desc' => 'Meditated for 5000 minutes total', 'icon' => 'fa-infinity']
    ];
    
    foreach ($timeAchievements as $minutes => $achievement) {
        $achievements[] = [
            'name' => $achievement['name'],
            'description' => $achievement['desc'],
            'icon' => $achievement['icon'],
            'progress' => min(100, round(($totalMinutes / $minutes) * 100)),
            'unlocked' => $totalMinutes >= $minutes,
            'category' => 'time'
        ];
    }
    
    // Streak achievements
    $streakAchievements = [
        7 => ['name' => 'Weekly Warrior', 'desc' => '7-day meditation streak', 'icon' => 'fa-calendar-week'],
        30 => ['name' => 'Monthly Master', 'desc' => '30-day meditation streak', 'icon' => 'fa-calendar-alt'],
        100 => ['name' => 'Century Streak', 'desc' => '100-day meditation streak', 'icon' => 'fa-calendar-check'],
        365 => ['name' => 'Year of Zen', 'desc' => '365-day meditation streak', 'icon' => 'fa-calendar']
    ];
    
    foreach ($streakAchievements as $days => $achievement) {
        $achievements[] = [
            'name' => $achievement['name'],
            'description' => $achievement['desc'],
            'icon' => $achievement['icon'],
            'progress' => min(100, round(($streakData['longest'] / $days) * 100)),
            'unlocked' => $streakData['longest'] >= $days,
            'category' => 'streak'
        ];
    }
    
    // Session count achievements
    $sessionAchievements = [
        10 => ['name' => 'Getting Started', 'desc' => 'Completed 10 sessions', 'icon' => 'fa-play'],
        50 => ['name' => 'Regular Meditator', 'desc' => 'Completed 50 sessions', 'icon' => 'fa-repeat'],
        100 => ['name' => 'Century Sessions', 'desc' => 'Completed 100 sessions', 'icon' => 'fa-100'],
        500 => ['name' => 'Meditation Guru', 'desc' => 'Completed 500 sessions', 'icon' => 'fa-star']
    ];
    
    foreach ($sessionAchievements as $count => $achievement) {
        $achievements[] = [
            'name' => $achievement['name'],
            'description' => $achievement['desc'],
            'icon' => $achievement['icon'],
            'progress' => min(100, round(($totalSessions / $count) * 100)),
            'unlocked' => $totalSessions >= $count,
            'category' => 'sessions'
        ];
    }
    
    // Mood achievements
    if (isset($feelings['Happy']) && $feelings['Happy'] >= 10) {
        $achievements[] = [
            'name' => 'Happiness Seeker',
            'description' => 'Reported feeling Happy 10 times',
            'icon' => 'fa-smile',
            'progress' => min(100, round(($feelings['Happy'] / 10) * 100)),
            'unlocked' => $feelings['Happy'] >= 10,
            'category' => 'mood'
        ];
    }
    
    return $achievements;
}
?>