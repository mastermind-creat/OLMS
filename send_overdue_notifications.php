<?php
session_start();
// Only admins or librarians can run this
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] != 'admin' && $_SESSION['role'] != 'librarian')) {
    header('Location: login.php');
    exit();
}

include 'includes/db_connection.php';
include 'includes/functions.php';

// Find overdue books: Status 'Book Issued' AND (requested_at + days) < NOW()
$sql = "SELECT br.id, br.user_id, b.title, u.email, DATEDIFF(NOW(), DATE_ADD(br.requested_at, INTERVAL br.days DAY)) as days_overdue 
        FROM borrow_requests br
        JOIN books b ON br.book_id = b.id
        JOIN users u ON br.user_id = u.id
        WHERE br.status = 'Book Issued' 
        AND DATE_ADD(br.requested_at, INTERVAL br.days DAY) < NOW()";

$result = $conn->query($sql);
$count = 0;

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $message = "Your borrowed book '{$row['title']}' is overdue by {$row['days_overdue']} days. Please return it immediately to avoid penalties.";
        
        // Prevent spamming: Check if notification already sent today?
        // For simplicity, we'll just send it. In a real app, we'd log last_notification_sent in borrow_requests.
        
        addNotification($conn, $row['user_id'], $message);
        $count++;
    }
}

if ($count > 0) {
    logAudit($conn, $_SESSION['user_id'], "System Alert", "Sent $count overdue notifications.");
    $_SESSION['success_msg'] = "Success: Sent $count overdue notifications to users.";
} else {
    $_SESSION['success_msg'] = "No overdue books found at this time.";
}

// Redirect back to the previous page or dashboard
if (isset($_SERVER['HTTP_REFERER'])) {
    header("Location: " . $_SERVER['HTTP_REFERER']);
} else {
    header("Location: admin_dashboard.php");
}
exit();
?>
