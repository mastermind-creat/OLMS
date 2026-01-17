<?php
session_start();
include 'db_connection.php';

// Fetch all categories for the filter
$categories_result = $conn->query("SELECT * FROM categories ORDER BY name ASC");

// Handle Search and Category Filter
$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';
$category_id = isset($_GET['category']) ? intval($_GET['category']) : 0;

$where_clauses = [];
if ($search) {
    $where_clauses[] = "(title LIKE '%$search%' OR author LIKE '%$search%' OR isbn LIKE '%$search%')";
}
if ($category_id) {
    $where_clauses[] = "category_id = $category_id";
}

$where_sql = "";
if (count($where_clauses) > 0) {
    $where_sql = "WHERE " . implode(' AND ', $where_clauses);
}

// Fetch books based on search/filter or just popular books if no search
if ($search || $category_id) {
    $books_sql = "SELECT books.*, categories.name as category_name 
                  FROM books 
                  LEFT JOIN categories ON books.category_id = categories.id 
                  $where_sql 
                  ORDER BY books.created_at DESC";
    $section_title = "Search Results";
} else {
    // Default: Fetch popular books
    $books_sql = "
        SELECT books.*, categories.name as category_name, COUNT(borrow_requests.book_id) AS borrow_count 
        FROM books
        LEFT JOIN categories ON books.category_id = categories.id
        LEFT JOIN borrow_requests ON books.id = borrow_requests.book_id AND borrow_requests.status = 'Book Issued'
        GROUP BY books.id
        ORDER BY borrow_count DESC
        LIMIT 6";
    $section_title = "Popular Books";
}

$books_result = $conn->query($books_sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>MyLibrary - Online Library Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
    <style>
        :root {
            --primary: #4361ee;
            --secondary: #3f37c9;
            --accent: #4cc9f0;
            --dark: #1e1e2d;
            --light: #f8fafc;
        }
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: var(--light);
            color: var(--dark);
        }
        .navbar {
            padding: 20px 0;
            transition: all 0.3s;
        }
        .navbar-brand {
            font-weight: 800;
            font-size: 1.5rem;
            color: var(--primary) !important;
        }
        .hero {
            background: linear-gradient(rgba(0,0,0,0.6), rgba(0,0,0,0.6)), url('assets/images/b2182435-28b5-47ee-b893-c7772220b7b7.jpg') no-repeat center center/cover;
            padding: 120px 0;
            color: white;
            text-align: center;
            border-radius: 0 0 50px 50px;
            margin-bottom: -50px;
        }
        .hero h1 {
            font-weight: 800;
            font-size: 3.5rem;
            margin-bottom: 20px;
            letter-spacing: -1px;
        }
        .search-container {
            max-width: 800px;
            margin: 40px auto 0;
            background: white;
            padding: 10px;
            border-radius: 20px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.2);
        }
        .search-container input {
            border: none;
            padding: 15px 25px;
            font-size: 1.1rem;
            width: 100%;
            border-radius: 15px;
        }
        .search-container input:focus {
            outline: none;
        }
        .search-container .btn-search {
            padding: 15px 35px;
            border-radius: 15px;
            font-weight: 700;
        }
        .category-pill {
            padding: 10px 25px;
            border-radius: 50px;
            background: white;
            color: var(--dark);
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s;
            border: 2px solid #eee;
            white-space: nowrap;
            display: inline-block;
            margin: 5px;
        }
        .category-pill:hover, .category-pill.active {
            background: var(--primary);
            color: white;
            border-color: var(--primary);
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(67, 97, 238, 0.3);
        }
        .book-card {
            border: none;
            border-radius: 20px;
            overflow: hidden;
            transition: all 0.3s;
            background: white;
            height: 100%;
        }
        .book-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
        }
        .book-card img {
            height: 280px;
            object-fit: cover;
            width: 100%;
        }
        .book-info {
            padding: 20px;
        }
        .book-title {
            font-weight: 700;
            font-size: 1.1rem;
            margin-bottom: 5px;
            color: var(--dark);
        }
        .book-author {
            color: #6c757d;
            font-size: 0.9rem;
            margin-bottom: 15px;
        }
        .badge-cat {
            background: rgba(67, 97, 238, 0.1);
            color: var(--primary);
            padding: 5px 12px;
            border-radius: 50px;
            font-size: 0.75rem;
            font-weight: 700;
        }
        footer {
            background: var(--dark);
            color: white;
            padding: 80px 0 40px;
            margin-top: 100px;
        }
        .footer-logo {
            font-weight: 800;
            font-size: 1.5rem;
            margin-bottom: 20px;
            display: block;
        }
    </style>
</head>
<body>

    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-light bg-light sticky-top shadow-sm">
        <div class="container">
            <a class="navbar-brand" href="index.php">📚 MyLibrary</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto align-items-center">
                    <li class="nav-item"><a class="nav-link fw-semibold px-3" href="index.php">Home</a></li>
                    <li class="nav-item"><a class="nav-link fw-semibold px-3" href="#">Browse</a></li>
                    <li class="nav-item"><a class="nav-link fw-semibold px-3" href="#">About</a></li>
                    <li class="nav-item ms-lg-3">
                        <a href="login.php" class="btn btn-outline-primary fw-bold px-4 rounded-pill">Login</a>
                    </li>
                    <li class="nav-item ms-lg-2">
                        <a href="register.php" class="btn btn-primary fw-bold px-4 rounded-pill shadow-sm">Register</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero">
        <div class="container">
            <h1 class="display-3">Your Gateway to Knowledge</h1>
            <p class="lead mb-5 opacity-75">Discover thousands of books, resources, and learning materials in one place.</p>
            
            <div class="search-container">
                <form action="index.php" method="GET" class="d-flex">
                    <input type="text" name="search" placeholder="Search for books, authors, or ISBN..." value="<?= htmlspecialchars($search) ?>">
                    <button type="submit" class="btn btn-primary btn-search shadow-sm">Search</button>
                </form>
            </div>
        </div>
    </section>

    <!-- Categories Section -->
    <div class="container" style="margin-top: 100px;">
        <div class="text-center mb-5">
            <h2 class="fw-bold">Browse by Category</h2>
            <p class="text-muted">Explore our diverse collection of genres</p>
        </div>
        
        <div class="d-flex flex-wrap justify-content-center mb-5">
            <a href="index.php" class="category-pill <?= !$category_id ? 'active' : '' ?>">All Books</a>
            <?php while($cat = $categories_result->fetch_assoc()): ?>
                <a href="index.php?category=<?= $cat['id'] ?>" class="category-pill <?= ($category_id == $cat['id']) ? 'active' : '' ?>">
                    <?= htmlspecialchars($cat['name']) ?>
                </a>
            <?php endwhile; ?>
        </div>

        <!-- Books Grid -->
        <h3 class="fw-bold mb-4"><?= $section_title ?></h3>
        <div class="row g-4">
            <?php if ($books_result->num_rows > 0): ?>
                <?php while ($book = $books_result->fetch_assoc()): ?>
                <div class="col-lg-3 col-md-4 col-sm-6">
                    <div class="card book-card shadow-sm">
                        <img src="<?= $book['cover_photo'] ?: 'https://via.placeholder.com/300x450?text=No+Cover'; ?>"
                            class="card-img-top" alt="<?= htmlspecialchars($book['title']); ?>">
                        <div class="book-info">
                            <span class="badge-cat mb-2 d-inline-block"><?= htmlspecialchars($book['category_name'] ?: 'Uncategorized'); ?></span>
                            <h5 class="book-title text-truncate" title="<?= htmlspecialchars($book['title']); ?>"><?= htmlspecialchars($book['title']); ?></h5>
                            <p class="book-author">by <?= htmlspecialchars($book['author']); ?></p>
                            <a href="login.php" class="btn btn-sm btn-outline-primary w-100 rounded-pill fw-bold">View Details</a>
                        </div>
                    </div>
                </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="col-12 text-center py-5">
                    <i class="bi bi-search display-1 text-muted opacity-25"></i>
                    <h4 class="mt-3 text-muted">No books found matching your criteria.</h4>
                    <a href="index.php" class="btn btn-primary mt-3 rounded-pill">View All Books</a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Footer -->
    <footer>
        <div class="container">
            <div class="row g-4">
                <div class="col-lg-4">
                    <a class="footer-logo text-white text-decoration-none" href="#">📚 MyLibrary</a>
                    <p class="opacity-50">Empowering readers worldwide with access to knowledge, anytime and anywhere. Join our community today.</p>
                </div>
                <div class="col-lg-2 offset-lg-1">
                    <h5 class="fw-bold mb-4">Quick Links</h5>
                    <ul class="list-unstyled opacity-75">
                        <li class="mb-2"><a href="#" class="text-white text-decoration-none">Privacy Policy</a></li>
                        <li class="mb-2"><a href="#" class="text-white text-decoration-none">Terms of Service</a></li>
                        <li class="mb-2"><a href="#" class="text-white text-decoration-none">Contact Us</a></li>
                    </ul>
                </div>
                <div class="col-lg-2">
                    <h5 class="fw-bold mb-4">Support</h5>
                    <ul class="list-unstyled opacity-75">
                        <li class="mb-2"><a href="#" class="text-white text-decoration-none">FAQ</a></li>
                        <li class="mb-2"><a href="#" class="text-white text-decoration-none">Help Center</a></li>
                        <li class="mb-2"><a href="#" class="text-white text-decoration-none">Knowledge Base</a></li>
                    </ul>
                </div>
                <div class="col-lg-3">
                    <h5 class="fw-bold mb-4">Contact Info</h5>
                    <ul class="list-unstyled opacity-75">
                        <li class="mb-2"><i class="bi bi-envelope me-2"></i> support@library.com</li>
                        <li class="mb-2"><i class="bi bi-telephone me-2"></i> +254 700 000 000</li>
                        <li><i class="bi bi-geo-alt me-2"></i> Nairobi, Kenya</li>
                    </ul>
                </div>
            </div>
            <hr class="my-5 opacity-25">
            <div class="text-center opacity-50">
                <p class="mb-0">&copy; <?= date("Y"); ?> Online Library Management System. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
