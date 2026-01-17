<?php
include 'includes/db_connection.php';
$result = $conn->query("SELECT id, title, cover_photo FROM books ORDER BY id DESC LIMIT 5");
while($row = $result->fetch_assoc()) {
    echo "ID: " . $row['id'] . " | Title: " . $row['title'] . " | Photo: " . $row['cover_photo'] . "\n";
}
?>
