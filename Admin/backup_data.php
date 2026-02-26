<?php
session_start();
require_once '../config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../Auth/login.php");
    exit();
}

$backup_file = '';
$success_msg = '';
$error_msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['backup'])) {
    $backup_dir = __DIR__ . '/../backups/';
    
    // Create backups directory if not exists
    if (!is_dir($backup_dir)) {
        mkdir($backup_dir, 0777, true);
    }
    
    // Generate filename with timestamp
    $filename = 'backup_ecommerce_' . date('Y-m-d_H-i-s') . '.sql';
    $filepath = $backup_dir . $filename;
    
    // Get all table names
    $tables = [];
    $result = $conn->query("SHOW TABLES");
    while ($row = $result->fetch_array()) {
        $tables[] = $row[0];
    }
    
    // Start backup content
    $backup_content = "-- Backup Database E-Commerce Radit\n";
    $backup_content .= "-- Generated: " . date('Y-m-d H:i:s') . "\n\n";
    
    try {
        foreach ($tables as $table) {
            // Get CREATE TABLE statement
            $create_result = $conn->query("SHOW CREATE TABLE `$table`");
            $create_row = $create_result->fetch_assoc();
            $backup_content .= "-- Table: $table\n";
            $backup_content .= $create_row['Create Table'] . ";\n\n";
            
            // Get all data
            $data_result = $conn->query("SELECT * FROM `$table`");
            $num_rows = $data_result->num_rows;
            
            if ($num_rows > 0) {
                $backup_content .= "-- Data for table `$table`\n";
                while ($row = $data_result->fetch_assoc()) {
                    $values = array_map(function($val) use ($conn) {
                        return $val === null ? 'NULL' : "'" . $conn->real_escape_string($val) . "'";
                    }, $row);
                    
                    $backup_content .= "INSERT INTO `$table` (`" . implode("`, `", array_keys($row)) . "`) VALUES (" . implode(", ", $values) . ");\n";
                }
                $backup_content .= "\n";
            }
        }
        
        // Write to file
        if (file_put_contents($filepath, $backup_content)) {
            $success_msg = "✅ Backup berhasil dibuat! File: $filename";
            $backup_file = $filename;
        } else {
            $error_msg = "❌ Gagal menyimpan file backup!";
        }
    } catch (Exception $e) {
        $error_msg = "❌ Error saat backup: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Backup Data - Admin Panel</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #324dff;
            --primary-dark: #1a73e8;
            --gray-bg: #d9d9d9;
            --gray-light: #f5f5f5;
            --danger-color: #fc0404;
            --success-color: #28a745;
            --backup-color: #009688;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background: linear-gradient(135deg, #1a2a6c, #2c3e50);
            color: #000000;
            min-height: 100vh;
            padding: 20px;
        }
        .main-container {
            max-width: 1000px;
            margin: 0 auto;
            background-color: #ffffff;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            border-radius: 25px;
            overflow: hidden;
            padding: 40px;
        }
        .header {
            text-align: center;
            margin-bottom: 40px;
            padding-bottom: 30px;
            border-bottom: 3px solid var(--backup-color);
        }
        .header i { font-size: 60px; color: var(--backup-color); margin-bottom: 15px; }
        .header h1 { font-size: 42px; font-weight: 800; color: var(--backup-color); }
        .backup-info {
            background: linear-gradient(135deg, #e0f7fa, #b2dfdb);
            border: 2px solid #009688;
            border-radius: 20px;
            padding: 30px;
            margin-bottom: 30px;
            text-align: center;
        }
        .info-title { font-size: 28px; font-weight: 700; color: #00796b; margin-bottom: 15px; }
        .info-detail { font-size: 20px; color: #26a69a; margin: 8px 0; }
        .backup-btn {
            display: block;
            width: 100%;
            padding: 25px;
            background: linear-gradient(135deg, var(--backup-color), #00796b);
            color: white;
            border: none;
            border-radius: 20px;
            font-size: 28px;
            font-weight: 800;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 10px 25px rgba(0, 150, 136, 0.4);
            margin: 20px 0;
        }
        .backup-btn:hover {
            transform: translateY(-5px) scale(1.02);
            box-shadow: 0 15px 35px rgba(0, 150, 136, 0.6);
            background: linear-gradient(135deg, #00796b, #00695c);
        }
        .backup-btn i { margin-right: 15px; }
        .message {
            padding: 20px;
            border-radius: 15px;
            margin: 20px 0;
            font-size: 20px;
            font-weight: 700;
            text-align: center;
        }
        .message.success {
            background-color: #d4edda;
            color: #155724;
            border: 2px solid #c3e6cb;
        }
        .message.error {
            background-color: #f8d7da;
            color: #721c24;
            border: 2px solid #f5c6cb;
        }
        .download-link {
            display: inline-block;
            padding: 15px 40px;
            background: linear-gradient(135deg, #2196f3, #1976d2);
            color: white;
            text-decoration: none;
            border-radius: 15px;
            font-size: 22px;
            font-weight: 700;
            margin-top: 20px;
            transition: all 0.3s ease;
        }
        .download-link:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(33, 150, 243, 0.4);
        }
        .download-link i { margin-right: 10px; }
        .back-btn {
            display: inline-block;
            padding: 15px 40px;
            background: linear-gradient(135deg, #9e9e9e, #616161);
            color: white;
            text-decoration: none;
            border-radius: 15px;
            font-size: 20px;
            font-weight: 700;
            margin-top: 20px;
            transition: all 0.3s ease;
        }
        .back-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(158, 158, 158, 0.4);
        }
        .back-btn i { margin-right: 10px; }
    </style>
</head>
<body>
    <div class="main-container">
        <div class="header">
            <i class="fas fa-database"></i>
            <h1>Backup Database</h1>
        </div>
        
        <div class="backup-info">
            <div class="info-title">ℹ️ Informasi Backup</div>
            <div class="info-detail">📁 Lokasi: <strong>folder /backups/</strong></div>
            <div class="info-detail">⏰ Format Nama File: backup_ecommerce_TANGGAL_WAKTU.sql</div>
            <div class="info-detail">💾 Termasuk: Semua tabel dan data</div>
        </div>
        
        <?php if ($success_msg): ?>
            <div class="message success">
                <i class="fas fa-check-circle"></i> <?php echo $success_msg; ?>
            </div>
            <a href="../backups/<?php echo $backup_file; ?>" class="download-link" download>
                <i class="fas fa-download"></i> Download File Backup
            </a>
        <?php endif; ?>
        
        <?php if ($error_msg): ?>
            <div class="message error">
                <i class="fas fa-exclamation-triangle"></i> <?php echo $error_msg; ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <button type="submit" name="backup" class="backup-btn">
                <i class="fas fa-save"></i> Backup Sekarang
            </button>
        </form>
        
        <a href="admin_dashboard.php" class="back-btn">
            <i class="fas fa-arrow-left"></i> Kembali ke Dashboard
        </a>
    </div>
</body>
</html>