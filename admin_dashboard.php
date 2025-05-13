<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
  header('Location: login.php');
  exit();
}

// Include the database connection file
include 'db_connection.php';

// Fetch statistics
$total_librarians_sql = "SELECT COUNT(*) AS total FROM users WHERE role = 'librarian'";
$total_librarians_result = $conn->query($total_librarians_sql);
$total_librarians = $total_librarians_result->fetch_assoc()['total'];

$total_members_sql = "SELECT COUNT(*) AS total FROM users WHERE role = 'member'";
$total_members_result = $conn->query($total_members_sql);
$total_members = $total_members_result->fetch_assoc()['total'];

$total_books_sql = "SELECT COUNT(*) AS total FROM books";
$total_books_result = $conn->query($total_books_sql);
$total_books = $total_books_result->fetch_assoc()['total'];

$total_borrowed_books_sql = "SELECT COUNT(*) AS total FROM borrowed_books";
$total_borrowed_books_result = $conn->query($total_borrowed_books_sql);
$total_borrowed_books = $total_borrowed_books_result->fetch_assoc()['total'];
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Dashboard</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
  <style>
    body {
      background-color: #f8f9fa;
    }

    .dashboard-header {
      margin-top: 50px;
      text-align: center;
      animation: fadeIn 1.5s ease-in-out;
    }

    .dashboard-header h2 {
      font-size: 2.5rem;
      font-weight: bold;
    }

    .dashboard-header p {
      font-size: 1.2rem;
      color: #6c757d;
    }

    .card {
      transition: transform 0.3s ease, box-shadow 0.3s ease;
    }

    .card:hover {
      transform: translateY(-5px);
      box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
    }

    @keyframes fadeIn {
      from {
        opacity: 0;
        transform: translateY(20px);
      }

      to {
        opacity: 1;
        transform: translateY(0);
      }
    }
  </style>
</head>

<body>
  <div class="container">
    <div class="dashboard-header">
      <h2>Welcome, Admin <?= $_SESSION['name']; ?></h2>
      <p>Manage your library system efficiently.</p>
    </div>

    <!-- Statistics Section -->
    <div class="row mt-5">
      <div class="col-md-3">
        <div class="card text-center shadow-sm">
          <div class="card-body">
            <i class="bi bi-person-fill display-4 text-primary"></i>
            <h5 class="card-title mt-3">Total Librarians</h5>
            <p class="card-text display-6"><?= $total_librarians; ?></p>
          </div>
        </div>
      </div>
      <div class="col-md-3">
        <div class="card text-center shadow-sm">
          <div class="card-body">
            <i class="bi bi-people-fill display-4 text-success"></i>
            <h5 class="card-title mt-3">Total Members</h5>
            <p class="card-text display-6"><?= $total_members; ?></p>
          </div>
        </div>
      </div>
      <div class="col-md-3">
        <div class="card text-center shadow-sm">
          <div class="card-body">
            <i class="bi bi-book-fill display-4 text-info"></i>
            <h5 class="card-title mt-3">Total Books</h5>
            <p class="card-text display-6"><?= $total_books; ?></p>
          </div>
        </div>
      </div>
      <div class="col-md-3">
        <div class="card text-center shadow-sm">
          <div class="card-body">
            <i class="bi bi-journal-arrow-down display-4 text-warning"></i>
            <h5 class="card-title mt-3">Books Borrowed</h5>
            <p class="card-text display-6"><?= $total_borrowed_books; ?></p>
          </div>
        </div>
      </div>
    </div>

    <!-- Other Dashboard Sections -->
    <div class="row mt-5">
      <!-- Manage Users -->
      <div class="col-md-4">
        <div class="card text-center shadow-sm">
          <div class="card-body">
            <i class="bi bi-people-fill display-4 text-primary"></i>
            <h5 class="card-title mt-3">Manage Users</h5>
            <p class="card-text">View, edit, and manage all users in the system.</p>
            <a href="#" class="btn btn-primary">Go to Users</a>
          </div>
        </div>
      </div>

      <!-- Manage Books -->
      <div class="col-md-4">
        <div class="card text-center shadow-sm">
          <div class="card-body">
            <i class="bi bi-book-fill display-4 text-success"></i>
            <h5 class="card-title mt-3">Manage Books</h5>
            <p class="card-text">Add, edit, or remove books from the library.</p>
            <a href="manage_books.php" class="btn btn-success">Go to Books</a>
          </div>
        </div>
      </div>

      <!-- View Reports -->
      <div class="col-md-4">
        <div class="card text-center shadow-sm">
          <div class="card-body">
            <i class="bi bi-bar-chart-fill display-4 text-warning"></i>
            <h5 class="card-title mt-3">View Reports</h5>
            <p class="card-text">Analyze system performance and user activity.</p>
            <a href="reports.php" class="btn btn-warning">View Reports</a>
          </div>
        </div>
      </div>
    </div>

    <div class="row mt-4">
      <!-- Add Librarian -->
      <div class="col-md-6">
        <div class="card text-center shadow-sm">
          <div class="card-body">
            <i class="bi bi-person-plus-fill display-4 text-info"></i>
            <h5 class="card-title mt-3">Add Librarian</h5>
            <p class="card-text">Add new librarians to manage the library system.</p>
            <a href="add_librarian.php" class="btn btn-info">Add Librarian</a>
          </div>
        </div>
      </div>

      <!-- Logout -->
      <div class="col-md-6">
        <div class="card text-center shadow-sm">
          <div class="card-body">
            <i class="bi bi-box-arrow-right display-4 text-danger"></i>
            <h5 class="card-title mt-3">Logout</h5>
            <p class="card-text">Sign out of your admin account securely.</p>
            <a href="logout.php" class="btn btn-danger">Logout</a>
          </div>
        </div>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>