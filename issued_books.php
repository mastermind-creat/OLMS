<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'librarian') {
    header('Location: login.php');
    exit();
}

include 'includes/db_connection.php';
include 'includes/header.php';

// Fetch all issued books
$issued_books_sql = "
    SELECT borrow_requests.id, books.title, users.name AS member_name, borrow_requests.days, borrow_requests.total_cost, borrow_requests.requested_at 
    FROM borrow_requests
    INNER JOIN books ON borrow_requests.book_id = books.id
    INNER JOIN users ON borrow_requests.user_id = users.id
    WHERE borrow_requests.status = 'Book Issued'
    ORDER BY borrow_requests.requested_at ASC";
$issued_books_result = $conn->query($issued_books_sql);
?>

<div class="container-fluid fade-in">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold text-primary">Issued Books</h2>
            <p class="text-muted">Track books currently borrowed by members.</p>
        </div>
        <div>
             <a href="manage_borrow_requests.php" class="btn btn-outline-primary"><i class="bi bi-clock-history"></i> Manage Requests</a>
        </div>
    </div>

    <!-- Stats or Filters could go here in future -->

    <div class="card p-4 shadow-sm border-0">
        <!-- Search -->
        <div class="row mb-3">
             <div class="col-md-4">
                <input type="text" id="searchInput" class="form-control" placeholder="Search by member or book title...">
            </div>
        </div>

        <div class="table-responsive">
             <?php if ($issued_books_result->num_rows > 0): ?>
                <table class="table table-hover align-middle" id="issuedBooksTable">
                    <thead class="table-light">
                        <tr>
                            <th>Book Title</th>
                            <th>Member Name</th>
                            <th>Duration</th>
                            <th>Issued Date</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($book = $issued_books_result->fetch_assoc()): ?>
                            <tr>
                                <td class="fw-bold text-primary book-title"><?= htmlspecialchars($book['title']); ?></td>
                                <td class="member-name">
                                    <div class="d-flex align-items-center">
                                         <div class="bg-light-primary rounded-circle d-flex align-items-center justify-content-center text-primary fw-bold me-2" style="width: 30px; height: 30px;">
                                            <?= substr(htmlspecialchars($book['member_name']), 0, 1) ?>
                                        </div>
                                        <?= htmlspecialchars($book['member_name']); ?>
                                    </div>
                                </td>
                                <td><?= $book['days']; ?> days</td>
                                <td><?= date('d M Y, h:i A', strtotime($book['requested_at'])); ?></td>
                                <td><span class="badge bg-warning text-dark">Issued</span></td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="text-center py-5">
                    <i class="bi bi-journals display-4 text-muted"></i>
                    <p class="mt-3 text-muted">No books are currently issued.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
    // Live Search Functionality
    document.addEventListener('DOMContentLoaded', function() {
        const searchInput = document.getElementById('searchInput');
        const table = document.getElementById('issuedBooksTable');
        
        if(searchInput && table) {
            searchInput.addEventListener('keyup', function() {
                const searchValue = this.value.toLowerCase();
                const rows = table.querySelectorAll('tbody tr');
                
                rows.forEach(row => {
                    const bookTitle = row.querySelector('.book-title').textContent.toLowerCase();
                    const memberName = row.querySelector('.member-name').textContent.toLowerCase();
                    
                    if (bookTitle.includes(searchValue) || memberName.includes(searchValue)) {
                        row.style.display = '';
                    } else {
                        row.style.display = 'none';
                    }
                });
            });
        }
    });
</script>

<?php include 'includes/footer.php'; ?>