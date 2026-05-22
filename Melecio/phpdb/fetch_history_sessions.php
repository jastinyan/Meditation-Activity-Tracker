<?php
session_start();
require 'db_connection.php';

if (!isset($_SESSION['id_no'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$id_no = $_SESSION['id_no'];

// Get pagination parameters
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
$offset = ($page - 1) * $limit;

// Get filter parameters
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$feeling = isset($_GET['feeling']) ? trim($_GET['feeling']) : '';
$location = isset($_GET['location']) ? trim($_GET['location']) : '';
$sort = isset($_GET['sort']) ? trim($_GET['sort']) : 'newest';

// Build the query
$whereConditions = ["id_no = ?"];
$params = [$id_no];
$types = "s";

// Add search condition
if (!empty($search)) {
    $whereConditions[] = "(session_name LIKE ? OR notes LIKE ?)";
    $searchTerm = "%$search%";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $types .= "ss";
}

// Add feeling filter
if (!empty($feeling)) {
    $whereConditions[] = "feeling = ?";
    $params[] = $feeling;
    $types .= "s";
}

// Add location filter
if (!empty($location)) {
    $whereConditions[] = "location = ?";
    $params[] = $location;
    $types .= "s";
}

$whereClause = implode(" AND ", $whereConditions);

// Get total count for pagination
$countQuery = "SELECT COUNT(*) as total FROM recorded_sessions WHERE $whereClause";
$countStmt = $conn->prepare($countQuery);
if (!empty($params)) {
    $countStmt->bind_param($types, ...$params);
}
$countStmt->execute();
$totalResult = $countStmt->get_result();
$totalRows = $totalResult->fetch_assoc()['total'];
$countStmt->close();

// Determine sort order
$orderBy = "created_at DESC"; // default newest
switch ($sort) {
    case 'oldest':
        $orderBy = "created_at ASC";
        break;
    case 'longest':
        $orderBy = "time_spent DESC";
        break;
    case 'shortest':
        $orderBy = "time_spent ASC";
        break;
    default: // newest
        $orderBy = "created_at DESC";
}

// Main query with pagination
$query = "SELECT id, session_name, time_spent, feeling, location, notes, created_at 
          FROM recorded_sessions 
          WHERE $whereClause 
          ORDER BY $orderBy 
          LIMIT ? OFFSET ?";

$stmt = $conn->prepare($query);

// Add limit and offset to params
$params[] = $limit;
$params[] = $offset;
$types .= "ii";

if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();

$sessions = [];
$totalTime = 0;

while ($row = $result->fetch_assoc()) {
    $sessions[] = [
        'id' => $row['id'],
        'session_name' => $row['session_name'],
        'time_spent' => (int)$row['time_spent'],
        'feeling' => $row['feeling'],
        'location' => $row['location'],
        'notes' => $row['notes'],
        'created_at' => $row['created_at']
    ];
    $totalTime += (int)$row['time_spent'];
}

$stmt->close();

// Calculate stats
$totalSessions = count($sessions);
$avgTime = $totalSessions > 0 ? round($totalTime / $totalSessions) : 0;

// Calculate total pages
$totalPages = ceil($totalRows / $limit);

echo json_encode([
    'success' => true,
    'sessions' => $sessions,
    'stats' => [
        'totalSessions' => $totalRows, // Use totalRows for total count, not just current page
        'totalTime' => $totalTime,
        'avgTime' => $avgTime
    ],
    'pagination' => [
        'page' => $page,
        'limit' => $limit,
        'total' => $totalRows,
        'totalPages' => $totalPages,
        'hasNext' => $page < $totalPages,
        'hasPrev' => $page > 1
    ]
]);

$conn->close();
?>