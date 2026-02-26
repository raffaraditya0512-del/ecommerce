<?php
// File: create_dummy_petugas.php
// Jalankan file ini SEKALI saja untuk membuat data dummy petugas
// Akses via: http://localhost/ecommerce_radit/Admin/create_dummy_petugas.php

require_once 'config.php';

// Daftar data dummy petugas
$dummy_petugas = [
    ['username' => 'budi_santoso', 'nama' => 'Budi Santoso', 'password' => 'budi123'],
    ['username' => 'siti_nurhaliza', 'nama' => 'Siti Nurhaliza', 'password' => 'siti123'],
    ['username' => 'agus_wijaya', 'nama' => 'Agus Wijaya', 'password' => 'agus123'],
    ['username' => 'dewi_permata', 'nama' => 'Dewi Permata', 'password' => 'dewi123'],
    ['username' => 'joko_widodo', 'nama' => 'Joko Widodo', 'password' => 'joko123']
];

$success_count = 0;
$error_messages = [];

foreach ($dummy_petugas as $petugas) {
    // Hash password
    $hashed_password = password_hash($petugas['password'], PASSWORD_DEFAULT);
    
    // Generate email otomatis
    $email = $petugas['username'] . '@petugas.ecommerce.com';
    
    // Cek apakah username sudah ada
    $stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
    $stmt->bind_param("s", $petugas['username']);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $error_messages[] = "Username '{$petugas['username']}' sudah ada, dilewati.";
        $stmt->close();
        continue;
    }
    $stmt->close();
    
    // Insert data petugas
    $stmt = $conn->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, 'petugas')");
    $stmt->bind_param("sss", $petugas['username'], $email, $hashed_password);
    
    if ($stmt->execute()) {
        $success_count++;
    } else {
        $error_messages[] = "Gagal menambahkan {$petugas['nama']}: " . $stmt->error;
    }
    $stmt->close();
}

$conn->close();

// Tampilkan hasil
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>✅ Data Dummy Petugas Dibuat</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        body {
            background: linear-gradient(135deg, #1a2a6c, #2c3e50);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
            color: #fff;
        }
        .container {
            background: rgba(255, 255, 255, 0.92);
            color: #2c3e50;
            border-radius: 25px;
            padding: 40px;
            max-width: 600px;
            text-align: center;
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.4);
            position: relative;
            overflow: hidden;
        }
        .container::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(16, 219, 30, 0.15) 0%, transparent 70%);
            z-index: 0;
        }
        .header {
            margin-bottom: 30px;
            position: relative;
            z-index: 1;
        }
        .header i {
            font-size: 60px;
            color: #10db1e;
            margin-bottom: 15px;
            display: block;
        }
        h1 {
            font-size: 36px;
            font-weight: 800;
            margin-bottom: 10px;
            background: linear-gradient(45deg, #10db1e, #0ea616);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            text-shadow: 0 2px 10px rgba(16, 219, 30, 0.3);
        }
        .success-count {
            font-size: 72px;
            font-weight: 800;
            color: #10db1e;
            margin: 20px 0;
            text-shadow: 0 0 20px rgba(16, 219, 30, 0.5);
        }
        .message {
            font-size: 20px;
            margin-bottom: 25px;
            line-height: 1.5;
            color: #2c3e50;
        }
        .errors {
            background: #ffebee;
            border-left: 4px solid #f44336;
            padding: 15px;
            border-radius: 0 10px 10px 0;
            margin: 20px 0;
            text-align: left;
            font-size: 16px;
            color: #c62828;
            max-height: 200px;
            overflow-y: auto;
        }
        .btn-container {
            display: flex;
            gap: 15px;
            justify-content: center;
            margin-top: 25px;
        }
        .btn {
            padding: 15px 35px;
            border-radius: 15px;
            font-size: 18px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
            border: none;
            position: relative;
            overflow: hidden;
        }
        .btn-primary {
            background: linear-gradient(135deg, #10db1e 0%, #0ea616 100%);
            color: white;
            box-shadow: 0 6px 15px rgba(16, 219, 30, 0.4);
        }
        .btn-secondary {
            background: linear-gradient(135deg, #324dff 0%, #1a73e8 100%);
            color: white;
            box-shadow: 0 6px 15px rgba(50, 77, 255, 0.4);
        }
        .btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.3);
        }
        .btn:active {
            transform: translateY(1px);
        }
        .btn i {
            margin-right: 8px;
        }
        .credentials {
            background: #e8f5e9;
            border: 2px dashed #4caf50;
            border-radius: 15px;
            padding: 20px;
            margin-top: 25px;
            text-align: left;
        }
        .credentials h3 {
            color: #1b5e20;
            margin-bottom: 15px;
            font-size: 22px;
        }
        .credentials ul {
            list-style: none;
            font-size: 18px;
            line-height: 1.8;
        }
        .credentials li {
            padding: 5px 0;
            display: flex;
        }
        .credentials li span:first-child {
            font-weight: 700;
            color: #2e7d32;
            min-width: 120px;
        }
        .credentials li span:last-child {
            color: #1565c0;
            font-family: monospace;
        }
        .footer {
            margin-top: 30px;
            font-size: 16px;
            color: #666;
            font-style: italic;
        }
        @media (max-width: 600px) {
            .container {
                padding: 30px 20px;
            }
            h1 {
                font-size: 28px;
            }
            .success-count {
                font-size: 56px;
            }
            .btn-container {
                flex-direction: column;
            }
            .btn {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <i class="fas fa-user-friends"></i>
            <h1>Data Dummy Petugas</h1>
        </div>
        
        <div class="success-count">
            <?php echo $success_count; ?>
        </div>
        
        <div class="message">
            <?php if ($success_count > 0): ?>
                ✅ Berhasil membuat <strong><?php echo $success_count; ?></strong> akun petugas dummy!
            <?php else: ?>
                ⚠️ Tidak ada data baru yang dibuat (semua username sudah ada)
            <?php endif; ?>
        </div>
        
        <?php if (!empty($error_messages)): ?>
            <div class="errors">
                <strong>Peringatan:</strong>
                <ul>
                    <?php foreach ($error_messages as $error): ?>
                        <li><?php echo htmlspecialchars($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        
        <div class="credentials">
            <h3><i class="fas fa-key"></i> Detail Login Petugas</h3>
            <ul>
                <li><span>Username:</span> <span>budi_santoso</span></li>
                <li><span>Password:</span> <span>budi123</span></li>
                <li><span>Username:</span> <span>siti_nurhaliza</span></li>
                <li><span>Password:</span> <span>siti123</span></li>
                <li><span>Username:</span> <span>agus_wijaya</span></li>
                <li><span>Password:</span> <span>agus123</span></li>
                <li><span>Username:</span> <span>dewi_permata</span></li>
                <li><span>Password:</span> <span>dewi123</span></li>
                <li><span>Username:</span> <span>joko_widodo</span></li>
                <li><span>Password:</span> <span>joko123</span></li>
            </ul>
        </div>
        
        <div class="btn-container">
            <a href="kelola_data_petugas.php" class="btn btn-primary">
                <i class="fas fa-users"></i> Lihat Data Petugas
            </a>
            <a href="../admin_dashboard.php" class="btn btn-secondary">
                <i class="fas fa-tachometer-alt"></i> Dashboard
            </a>
        </div>
        
        <div class="footer">
            ℹ️ File ini bisa dihapus setelah testing selesai
        </div>
    </div>
    
    <script>
        // Auto-redirect ke halaman petugas setelah 10 detik
        setTimeout(function() {
            window.location.href = 'kelola_data_petugas.php';
        }, 10000);
    </script>
</body>
</html>