<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header('Location: login.php');
    exit();
}

include 'includes/db_connection.php';
include 'includes/header.php';

// Pagination
$limit = 20;
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$offset = ($page - 1) * $limit;

// Search/Filter
$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';
$where = "";
if ($search) {
    $where = " WHERE a.action LIKE '%$search%' OR a.details LIKE '%$search%' OR u.name LIKE '%$search%' OR a.ip_address LIKE '%$search%'";
}

// Fetch Audit Logs
$sql = "SELECT a.*, u.name as user_name, u.role as user_role 
        FROM audit_trail a 
        LEFT JOIN users u ON a.user_id = u.id 
        $where 
        ORDER BY a.created_at DESC 
        LIMIT $limit OFFSET $offset";
$result = $conn->query($sql);

// Total for pagination
$total_sql = "SELECT COUNT(*) as count FROM audit_trail a LEFT JOIN users u ON a.user_id = u.id $where";
$total_result = $conn->query($total_sql);
$total_rows = $total_result->fetch_assoc()['count'];
$total_pages = ceil($total_rows / $limit);
?>

<style>
    @media print {
        .top-navbar, .sidebar, .btn, .input-group, .pagination, .card-footer, .breadcrumb-item.active {
            display: none !important;
        }
        .main-content {
            margin-left: 0 !important;
            padding: 0 !important;
        }
        .container-fluid {
            width: 100% !important;
            padding: 0 !important;
        }
        .card {
            border: none !important;
            box-shadow: none !important;
        }
        .table {
            width: 100% !important;
            border-collapse: collapse !important;
        }
        .table th, .table td {
            border: 1px solid #dee2e6 !important;
            padding: 8px !important;
        }
        .badge {
            border: 1px solid #000 !important;
            color: #000 !important;
            background: transparent !important;
        }
    }
</style>

<div class="container-fluid fade-in">
    <div class="row mb-4">
        <div class="col-md-12 d-flex justify-content-between align-items-center">
            <div>
                <h2 class="fw-bold text-primary"><i class="bi bi-clock-history"></i> System Audit Trail</h2>
                <p class="text-muted">Monitor all critical system activities and user actions.</p>
            </div>
            <div class="d-flex gap-2">
                <button onclick="window.print()" class="btn btn-outline-dark">
                    <i class="bi bi-printer"></i> Print Report
                </button>
                <div style="width: 300px;">
                    <form method="GET" class="input-group">
                        <input type="text" name="search" class="form-control" placeholder="Search logs..." value="<?= htmlspecialchars($search) ?>">
                        <button class="btn btn-primary" type="submit"><i class="bi bi-search"></i></button>
                        <?php if($search): ?>
                            <a href="audit_trail.php" class="btn btn-outline-secondary"><i class="bi bi-x-lg"></i></a>
                        <?php endif; ?>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-4">Timestamp</th>
                            <th>User</th>
                            <th>Action</th>
                            <th>Details</th>
                            <th>IP Address</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($result->num_rows > 0): ?>
                            <?php while ($log = $result->fetch_assoc()): ?>
                                <tr>
                                    <td class="ps-4">
                                        <div class="small fw-bold"><?= date('M d, Y', strtotime($log['created_at'])) ?></div>
                                        <div class="text-muted smaller"><?= date('H:i:s', strtotime($log['created_at'])) ?></div>
                                    </td>
                                    <td>
                                        <?php if ($log['user_id']): ?>
                                            <div class="d-flex align-items-center">
                                                <div class="bg-light-primary rounded-circle d-flex align-items-center justify-content-center text-primary fw-bold me-2" style="width: 32px; height: 32px; font-size: 0.8rem;">
                                                    <?= strtoupper(substr($log['user_name'], 0, 1)) ?>
                                                </div>
                                                <div>
                                                    <div class="fw-bold"><?= htmlspecialchars($log['user_name']) ?></div>
                                                    <div class="badge bg-light text-dark smaller"><?= ucfirst($log['user_role']) ?></div>
                                                </div>
                                            </div>
                                        <?php else: ?>
                                            <span class="text-muted italic"><i class="bi bi-robot"></i> System / Guest</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php 
                                        $badgeClass = 'bg-info';
                                        if (stripos($log['action'], 'login') !== false) $badgeClass = 'bg-success';
                                        if (stripos($log['action'], 'failed') !== false) $badgeClass = 'bg-danger';
                                        if (stripos($log['action'], 'delete') !== false) $badgeClass = 'bg-danger';
                                        if (stripos($log['action'], 'edit') !== false) $badgeClass = 'bg-warning text-dark';
                                        ?>
                                        <span class="badge <?= $badgeClass ?>"><?= htmlspecialchars($log['action']) ?></span>
                                    </td>
                                    <td class="text-wrap" style="max-width: 300px;">
                                        <span class="small"><?= htmlspecialchars($log['details']) ?></span>
                                    </td>
                                    <td>
                                        <code><?= htmlspecialchars($log['ip_address']) ?></code>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="text-center py-5 text-muted">
                                    <i class="bi bi-file-earmark-text display-4"></i>
                                    <p class="mt-3">No audit logs found matching your criteria.</p>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <?php if ($total_pages > 1): ?>
            <div class="card-footer bg-white border-0 py-3">
                <nav aria-label="Page navigation">
                    <ul class="pagination justify-content-center mb-0">
                        <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
                            <a class="page-link" href="?page=<?= $page - 1 ?>&search=<?= urlencode($search) ?>">Previous</a>
                        </li>
                        <?php for($i = 1; $i <= $total_pages; $i++): ?>
                            <li class="page-item <?= ($page == $i) ? 'active' : '' ?>">
                                <a class="page-link" href="?page=<?= $i ?>&search=<?= urlencode($search) ?>"><?= $i ?></a>
                            </li>
                        <?php endfor; ?>
                        <li class="page-item <?= ($page >= $total_pages) ? 'disabled' : '' ?>">
                            <a class="page-link" href="?page=<?= $page + 1 ?>&search=<?= urlencode($search) ?>">Next</a>
                        </li>
                    </ul>
                </nav>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
