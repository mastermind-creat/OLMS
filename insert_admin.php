<?php
// Database connection
$conn = new mysqli("localhost", "root", "", "library_db");

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Insert admin user
$hashedPassword = password_hash("admin123", PASSWORD_DEFAULT);
$sql = "INSERT INTO users (name, email, password, role)
        VALUES ('Admin User', 'admin@library.com', '$hashedPassword', 'admin')";

if ($conn->query($sql) === TRUE) {
    echo "Admin user inserted successfully!";
} else {
    echo "Error: " . $conn->error;
}

$conn->close();
?>