<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'librarian') {
  header('Location: login.php');
  exit();
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Librarian Dashboard</title>
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
      <h2>Welcome, Librarian <?= $_SESSION['name']; ?></h2>
      <p>Manage your library system efficiently.</p>
    </div>

    <div class="row mt-5">
      <!-- Issue Books -->
      <div class="col-md-4">
        <div class="card text-center shadow-sm">
          <div class="card-body">
            <i class="bi bi-journal-arrow-up display-4 text-primary"></i>
            <h5 class="card-title mt-3">Issue Books</h5>
            <p class="card-text">Issue books to library members.</p>
            <a href="issued_books.php" class="btn btn-primary">Issued Books</a>
          </div>
        </div>
      </div>

      <!-- Return Books -->
      <div class="col-md-4">
        <div class="card text-center shadow-sm">
          <div class="card-body">
            <i class="bi bi-journal-arrow-down display-4 text-success"></i>
            <h5 class="card-title mt-3">Returned Books</h5>
            <p class="card-text">Manage book returns from members.</p>
            <a href="returned_books.php" class="btn btn-success">Return Books</a>
          </div>
        </div>
      </div>

      <!-- Borrow Requests Notifications -->
      <div class="col-md-4">
        <div class="card text-center shadow-sm">
          <div class="card-body">
            <i class="bi bi-bell display-4 text-warning"></i>
            <h5 class="card-title mt-3">Borrow Requests</h5>
            <p class="card-text">View and manage borrow requests from members.</p>
            <a href="manage_borrow_requests.php" class="btn btn-warning">Manage Borrow Requests</a>
          </div>
        </div>
      </div>
    </div>

    <div class="row mt-4">
      <!-- Add Books -->
      <div class="col-md-4">
        <div class="card text-center shadow-sm">
          <div class="card-body">
            <i class="bi bi-book-fill display-4 text-primary"></i>
            <h5 class="card-title mt-3">Add Books</h5>
            <p class="card-text">Add new books to the library system.</p>
            <a href="add_book.php" class="btn btn-primary">Add Books</a>
            <a href="manage_books.php" class="btn btn-primary">Manage Books</a>
          </div>
        </div>
      </div>

      <!-- Create Book Categories -->
      <div class="col-md-6">
        <div class="card text-center shadow-sm">
          <div class="card-body">
            <i class="bi bi-folder-plus display-4 text-info"></i>
            <h5 class="card-title mt-3">Create Categories</h5>
            <p class="card-text">Organize books into categories.</p>
            <a href="create_category.php" class="btn btn-info">Create Categories</a>
            <a href="manage_categories.php" class="btn btn-info">Manage Categories</a>
          </div>
        </div>
      </div>

      <!-- Logout -->
      <div class="col-md-6">
        <div class="card text-center shadow-sm">
          <div class="card-body">
            <i class="bi bi-box-arrow-right display-4 text-danger"></i>
            <h5 class="card-title mt-3">Logout</h5>
            <p class="card-text">Sign out of your librarian account.</p>
            <a href="logout.php" class="btn btn-danger">Logout</a>
          </div>
        </div>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>