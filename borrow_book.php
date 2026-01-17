<?php
session_start();
include 'includes/db_connection.php';
include 'includes/header.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'member') {
    echo "<script>window.location.href='login.php';</script>";
    exit();
}

$user_id = $_SESSION['user_id'];
$book_id = isset($_GET['book_id']) ? intval($_GET['book_id']) : 0;
$message = "";
$error = "";
$book = null;

// Fetch book details
if ($book_id > 0) {
    $book_sql = "SELECT * FROM books WHERE id = $book_id";
    $book_result = $conn->query($book_sql);
    if ($book_result->num_rows > 0) {
        $book = $book_result->fetch_assoc();
    }
}

// Handle Borrow Request
if ($_SERVER['REQUEST_METHOD'] == 'POST' && $book) {
    $days = intval($_POST['days']);
    $total_cost = $days * 50; // Pricing logic: 50 per day

    // NEW: Check if user already has 2 or more books issued
    $issued_books_check = $conn->query("SELECT COUNT(*) as count FROM borrow_requests WHERE user_id = $user_id AND status = 'Book Issued'")->fetch_assoc()['count'];
    
    if ($issued_books_check >= 2) {
        $error = "You cannot borrow more than 2 books. Please return your current books first.";
    } elseif ($book['quantity'] > 0) {
        // Start Transaction
        $conn->begin_transaction();
        try {
            // Create Borrow Request
            $stmt = $conn->prepare("INSERT INTO borrow_requests (book_id, user_id, days, total_cost, status, requested_at) VALUES (?, ?, ?, ?, 'Pending', NOW())");
            $stmt->bind_param("iiid", $book_id, $user_id, $days, $total_cost);
            $stmt->execute();

            // Decrease Quantity
            $update_stmt = $conn->prepare("UPDATE books SET quantity = quantity - 1 WHERE id = ?");
            $update_stmt->bind_param("i", $book_id);
            $update_stmt->execute();

            $conn->commit();
            $message = "Borrow request submitted successfully! Pending approval.";
            
            // Refresh book data to show updated quantity
            $book['quantity'] -= 1; 

        } catch (Exception $e) {
            $conn->rollback();
            $error = "Transaction failed: " . $e->getMessage();
        }
    } else {
        $error = "Sorry, this book is currently out of stock.";
    }
}
?>

<div class="container fade-in" style="max-width: 800px; margin-top: 50px;">
    <?php if ($book): ?>
        <div class="card shadow-lg border-0 overflow-hidden">
            <div class="row g-0">
                <div class="col-md-5 bg-light d-flex align-items-center justify-content-center p-4">
                     <!-- Book Cover Placeholder or Image -->
                    <?php if (!empty($book['cover_photo'])): ?>
                        <img src="<?= htmlspecialchars($book['cover_photo']) ?>" class="img-fluid shadow rounded" alt="<?= htmlspecialchars($book['title']) ?>" style="max-height: 300px;">
                    <?php else: ?>
                        <div class="text-center text-muted">
                            <i class="bi bi-book display-1"></i>
                            <p class="mt-2">No Cover Image</p>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="col-md-7">
                    <div class="card-body p-5">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <div>
                                <h3 class="card-title fw-bold text-primary mb-1"><?= htmlspecialchars($book['title']) ?></h3>
                                <p class="text-muted mb-0">by <span class="fw-semibold text-dark"><?= htmlspecialchars($book['author']) ?></span></p>
                            </div>
                            <span class="badge bg-light-info text-info rounded-pill px-3 py-2">
                                <?= $book['quantity'] > 0 ? 'In Library' : 'Out of Stock' ?>
                            </span>
                        </div>
                        
                        <p class="small text-muted mb-4"><?= htmlspecialchars($book['description'] ?? 'No description available.') ?></p>

                        <?php if ($message): ?>
                            <div class="alert alert-success d-flex align-items-center" role="alert">
                                <i class="bi bi-check-circle-fill me-2"></i> 
                                <div>
                                    <?= $message ?>
                                    <div class="mt-2">
                                        <a href="member_dashboard.php" class="btn btn-sm btn-success">View My Books</a>
                                    </div>
                                </div>
                            </div>
                        <?php elseif ($error): ?>
                            <div class="alert alert-danger d-flex align-items-center" role="alert">
                                <i class="bi bi-exclamation-triangle-fill me-2"></i> <?= $error ?>
                            </div>
                        <?php endif; ?>

                        <?php if (!$message && $book['quantity'] > 0): ?>
                            <form method="POST">
                                <div class="mb-4">
                                    <label for="days" class="form-label fw-bold small text-uppercase">Borrow Duration (Days)</label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-white"><i class="bi bi-calendar-event"></i></span>
                                        <input type="number" name="days" id="days" class="form-control" min="1" max="30" value="7" required>
                                        <span class="input-group-text bg-light">Days</span>
                                    </div>
                                </div>

                                <div class="d-grid gap-2">
                                    <button type="submit" class="btn btn-primary btn-lg shadow-sm">Confirm Request</button>
                                    <a href="member_dashboard.php" class="btn btn-outline-secondary">Cancel</a>
                                </div>
                            </form>
                        <?php elseif ($book['quantity'] <= 0): ?>
                             <div class="d-grid gap-2">
                                <button class="btn btn-secondary btn-lg" disabled>Unavailable</button>
                                <a href="member_dashboard.php" class="btn btn-outline-secondary">Browse Other Books</a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    <?php else: ?>
        <div class="text-center py-5">
            <i class="bi bi-exclamation-circle display-1 text-muted"></i>
            <h3 class="mt-3">Book Not Found</h3>
            <p class="text-muted">The book you are looking for does not exist or has been removed.</p>
            <a href="member_dashboard.php" class="btn btn-primary mt-3">Browse Library</a>
        </div>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>