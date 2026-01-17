<?php
session_start();
include 'includes/db_connection.php';

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'librarian'])) {
    header('Location: login.php');
    exit();
}

$report_type = $_GET['report_type'] ?? '';
$filename = "report_" . date('Ymd') . ".csv";

header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="' . $filename . '"');

$output = fopen('php://output', 'w');

if ($report_type == 'books') {
    fputcsv($output, ['ID', 'Title', 'Author', 'Category', 'ISBN', 'Quantity', 'Added Date']);
    
    $where_clauses = [];
    if (!empty($_GET['category_id'])) {
        $cat_id = intval($_GET['category_id']);
        $where_clauses[] = "b.category_id = $cat_id";
    }
    if (!empty($_GET['search'])) {
        $search = $conn->real_escape_string($_GET['search']);
        $where_clauses[] = "(b.title LIKE '%$search%' OR b.author LIKE '%$search%')";
    }
    
    $sql = "SELECT b.id, b.title, b.author, c.name as category, b.isbn, b.quantity, b.created_at 
            FROM books b 
            LEFT JOIN categories c ON b.category_id = c.id";
            
    if (!empty($where_clauses)) {
        $sql .= " WHERE " . implode(' AND ', $where_clauses);
    }
    
    $result = $conn->query($sql);
    while ($row = $result->fetch_assoc()) {
        fputcsv($output, $row);
    }

} elseif ($report_type == 'users') {
    fputcsv($output, ['ID', 'Name', 'Email', 'Role', 'Joined Date']);
    
    $where_clauses = [];
    if (!empty($_GET['role_filter'])) {
        $role = $conn->real_escape_string($_GET['role_filter']);
        $where_clauses[] = "role = '$role'";
    }
    
    $sql = "SELECT id, name, email, role, created_at FROM users";
    
    if (!empty($where_clauses)) {
        $sql .= " WHERE " . implode(' AND ', $where_clauses);
    }
    
    $result = $conn->query($sql);
    while ($row = $result->fetch_assoc()) {
        fputcsv($output, $row);
    }
}

fclose($output);
exit();
?>
