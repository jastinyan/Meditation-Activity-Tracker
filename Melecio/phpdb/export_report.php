<?php
session_start();
require 'db_connection.php';

// Check authentication
if (!isset($_SESSION['id_no']) || $_SESSION['role'] !== 'super_admin') {
    die('Unauthorized');
}

// Get parameters
$start_date = $_GET['start_date'] ?? date('Y-m-d', strtotime('-30 days'));
$end_date = $_GET['end_date'] ?? date('Y-m-d');
$format = $_GET['format'] ?? 'csv';
$data_types = explode(',', $_GET['data'] ?? 'userSummary,sessions');

// Set headers based on format
if ($format === 'csv') {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="report_' . date('Y-m-d') . '.csv"');
    
    $output = fopen('php://output', 'w');
    
    // Export each selected data type
    foreach ($data_types as $type) {
        switch ($type) {
            case 'userSummary':
                fputcsv($output, ['USER SUMMARY REPORT']);
                fputcsv($output, ['Username', 'Role', 'Sessions', 'Total Time (min)', 'Avg Time (min)', 'Last Active']);
                
                $query = "SELECT 
                            ra.username,
                            ra.role,
                            COUNT(rs.id) as sessions,
                            COALESCE(SUM(rs.time_spent)/60, 0) as total_minutes,
                            COALESCE(AVG(rs.time_spent)/60, 0) as avg_minutes,
                            MAX(rs.created_at) as last_active
                          FROM registeredacc ra
                          LEFT JOIN recorded_sessions rs ON ra.id_no = rs.id_no 
                              AND DATE(rs.created_at) BETWEEN ? AND ?
                          WHERE ra.role IN ('user', 'admin')
                          GROUP BY ra.id_no";
                
                $stmt = $conn->prepare($query);
                $stmt->bind_param("ss", $start_date, $end_date);
                $stmt->execute();
                $result = $stmt->get_result();
                
                while ($row = $result->fetch_assoc()) {
                    fputcsv($output, [
                        $row['username'],
                        $row['role'],
                        $row['sessions'],
                        round($row['total_minutes'], 1),
                        round($row['avg_minutes'], 1),
                        $row['last_active'] ?? 'Never'
                    ]);
                }
                fputcsv($output, []);
                break;
                
            case 'sessions':
                fputcsv($output, ['SESSION DETAILS REPORT']);
                fputcsv($output, ['Username', 'Session Name', 'Duration (min)', 'Feeling', 'Location', 'Date & Time']);
                
                $query = "SELECT 
                            ra.username,
                            rs.session_name,
                            rs.time_spent/60 as minutes,
                            rs.feeling,
                            rs.location,
                            rs.created_at
                          FROM recorded_sessions rs
                          JOIN registeredacc ra ON rs.id_no = ra.id_no
                          WHERE DATE(rs.created_at) BETWEEN ? AND ?
                          ORDER BY rs.created_at DESC";
                
                $stmt = $conn->prepare($query);
                $stmt->bind_param("ss", $start_date, $end_date);
                $stmt->execute();
                $result = $stmt->get_result();
                
                while ($row = $result->fetch_assoc()) {
                    fputcsv($output, [
                        $row['username'],
                        $row['session_name'],
                        round($row['minutes'], 1),
                        $row['feeling'],
                        $row['location'],
                        $row['created_at']
                    ]);
                }
                fputcsv($output, []);
                break;
        }
    }
    
    fclose($output);
}

$conn->close();
?>