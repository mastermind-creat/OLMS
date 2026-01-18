<?php
// includes/functions.php

function addNotification($conn, $user_id, $message) {
    if (!$conn) return;
    $sql = "INSERT INTO notifications (user_id, message) VALUES (?, ?)";
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param("is", $user_id, $message);
        $stmt->execute();
        $stmt->close();
    }
}

function getUnreadNotifications($conn, $user_id) {
    if (!$conn) return [];
    $sql = "SELECT * FROM notifications WHERE user_id = ? AND is_read = 0 ORDER BY created_at DESC LIMIT 5";
    $stmt = $conn->prepare($sql);
    $notifications = [];
    if ($stmt) {
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $notifications[] = $row;
        }
        $stmt->close();
    }
    return $notifications;
}

function getUnreadCount($conn, $user_id) {
    if (!$conn) return 0;
    $sql = "SELECT COUNT(*) as count FROM notifications WHERE user_id = ? AND is_read = 0";
    $stmt = $conn->prepare($sql);
    $count = 0;
    if ($stmt) {
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $count = $row['count'];
        $stmt->close();
    }
    return $count;
}

function markNotificationsAsRead($conn, $user_id) {
    if (!$conn) return;
    $sql = "UPDATE notifications SET is_read = 1 WHERE user_id = ?";
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $stmt->close();
    }
}
function logAudit($conn, $user_id, $action, $details = "") {
    if (!$conn) return;
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN';
    $sql = "INSERT INTO audit_trail (user_id, action, details, ip_address) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param("isss", $user_id, $action, $details, $ip_address);
        $stmt->execute();
        $stmt->close();
    }
}
?>
