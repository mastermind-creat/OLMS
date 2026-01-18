<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

include 'includes/db_connection.php';
include 'includes/functions.php';

$user_id = $_SESSION['user_id'];
$action = $_GET['action'] ?? '';
$notif_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($action === 'mark_all_read') {
    markNotificationsAsRead($conn, $user_id);
    $_SESSION['success_msg'] = "All notifications marked as read.";
} elseif ($notif_id > 0) {
    // Only allow marking own notifications
    $sql = "UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?";
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param("ii", $notif_id, $user_id);
        $stmt->execute();
        $stmt->close();
        $_SESSION['success_msg'] = "Notification marked as read.";
    }
}

// Redirect back
if (isset($_SERVER['HTTP_REFERER'])) {
    header("Location: " . $_SERVER['HTTP_REFERER']);
} else {
    header("Location: notifications.php");
}
exit();
?>
