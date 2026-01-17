<?php
session_start();
include 'includes/db_connection.php';
include 'includes/functions.php';

if (isset($_SESSION['user_id'])) {
    markNotificationsAsRead($conn, $_SESSION['user_id']);
}

header('Location: ' . $_SERVER['HTTP_REFERER']);
exit();
?>
