<?php
// File: create_admin.php - Jalankan sekali saja untuk membuat admin
require_once 'config.php';

$username = 'admin';
$email = 'admin@ecommerce.com';
$password = 'admin123'; // Ganti password ini sesuai keinginan
$role = 'admin';

// Hash password
$hashed_password = password_hash($password, PASSWORD_DEFAULT);

// Insert admin user
$stmt = $conn->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, ?)");
$stmt->bind_param("ssss", $username, $email, $hashed_password, $role);

if ($stmt->execute()) {
    echo "✅ Admin berhasil dibuat!<br>";
    echo "Username: admin<br>";
    echo "Password: admin123<br>";
    echo "Silakan login sekarang!";
} else {
    echo "❌ Gagal membuat admin: " . $conn->error;
}

$stmt->close();
$conn->close();
?>