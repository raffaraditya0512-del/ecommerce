<?php
session_start();
require_once '../config.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../Auth/login.php");
    exit();
}

// Create transactions table if not exists
$create_table = "CREATE TABLE IF NOT EXISTS transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL DEFAULT 1,
    total_price DECIMAL(10, 2) NOT NULL,
    status ENUM('pending', 'processing', 'completed', 'cancelled') DEFAULT 'pending',
    payment_method VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (product_id) REFERENCES products(id)
)";
$conn->query($create_table);

// Handle Add Transaction
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_transaction') {
    $user_id = intval($_POST['user_id']);
    $product_id = intval($_POST['product_id']);
    $quantity = intval($_POST['quantity']);
    $total_price = floatval($_POST['total_price']);
    $status = $_POST['status'];
    $payment_method = $_POST['payment_method'];
    
    // Validation
    $errors = [];
    if ($user_id <= 0) $errors[] = "User tidak valid";
    if ($product_id <= 0) $errors[] = "Produk tidak valid";
    if ($quantity <= 0) $errors[] = "Jumlah harus lebih dari 0";
    if ($total_price <= 0) $errors[] = "Total harga harus lebih dari 0";
    
    if (empty($errors)) {
        $stmt = $conn->prepare("INSERT INTO transactions (user_id, product_id, quantity, total_price, status, payment_method) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("iiidss", $user_id, $product_id, $quantity, $total_price, $status, $payment_method);
        if ($stmt->execute()) {
            $_SESSION['success_msg'] = "Transaksi berhasil ditambahkan!";
        } else {
            $errors[] = "Gagal menambah transaksi: " . $stmt->error;
        }
        $stmt->close();
    }
    
    if (!empty($errors)) {
        $_SESSION['error_msg'] = implode("<br>", $errors);
    }
    
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

// Handle Edit Transaction
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit_transaction') {
    $id = intval($_POST['id']);
    $status = $_POST['status'];
    
    // Validation
    $errors = [];
    if (!in_array($status, ['pending', 'processing', 'completed', 'cancelled'])) {
        $errors[] = "Status tidak valid";
    }
    
    if (empty($errors)) {
        $stmt = $conn->prepare("UPDATE transactions SET status = ? WHERE id = ?");
        $stmt->bind_param("si", $status, $id);
        if ($stmt->execute()) {
            $_SESSION['success_msg'] = "Status transaksi berhasil diupdate!";
        } else {
            $errors[] = "Gagal mengupdate transaksi: " . $stmt->error;
        }
        $stmt->close();
    }
    
    if (!empty($errors)) {
        $_SESSION['error_msg'] = implode("<br>", $errors);
    }
    
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

// Handle Delete Transaction
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_transaction') {
    $id = intval($_POST['id']);
    
    $stmt = $conn->prepare("DELETE FROM transactions WHERE id = ?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        $_SESSION['success_msg'] = "Transaksi berhasil dihapus!";
    } else {
        $_SESSION['error_msg'] = "Gagal menghapus transaksi: " . $stmt->error;
    }
    $stmt->close();
    
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

// Get messages from session
$success_msg = isset($_SESSION['success_msg']) ? $_SESSION['success_msg'] : null;
$error_msg = isset($_SESSION['error_msg']) ? $_SESSION['error_msg'] : null;
unset($_SESSION['success_msg'], $_SESSION['error_msg']);

// Get all transactions from database with user and product info
$transactions = [];
$query = "SELECT t.*, u.username as user_name, p.name as product_name 
          FROM transactions t
          LEFT JOIN users u ON t.user_id = u.id
          LEFT JOIN products p ON t.product_id = p.id
          ORDER BY t.created_at DESC";
$result = $conn->query($query);
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $transactions[] = $row;
    }
}

// Get all users and products for dropdown
$users = [];
$result = $conn->query("SELECT id, username FROM users WHERE role = 'user' ORDER BY username");
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $users[] = $row;
    }
}

$products = [];
$result = $conn->query("SELECT id, name, price FROM products ORDER BY name");
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $products[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Kelola Data Transaksi - Admin Panel</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    <style>
        :root {
            --default-font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto,
                Ubuntu, "Helvetica Neue", Helvetica, Arial, "PingFang SC",
                "Hiragino Sans GB", "Microsoft Yahei UI", "Microsoft Yahei",
                "Source Han Sans CN", sans-serif;
            --primary-color: #324dff;
            --primary-dark: #1a73e8;
            --gray-bg: #d9d9d9;
            --gray-light: #f5f5f5;
            --danger-color: #fc0404;
            --success-color: #28a745;
            --transaction-color: #ff9800;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: var(--default-font-family);
            background-color: #ffffff;
            color: #000000;
            min-height: 100vh;
            padding: 20px;
            overflow-x: hidden;
            position: relative;
        }

        .main-container {
            max-width: 1400px;
            margin: 0 auto;
            position: relative;
            background-color: #ffffff;
            box-shadow: 0 0 30px rgba(0, 0, 0, 0.1);
            border-radius: 15px;
            overflow: hidden;
            padding: 20px;
        }

        .admin-panel-title {
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-size: 32px;
            font-weight: 800;
            color: var(--primary-color);
            margin-bottom: 25px;
            text-align: center;
            letter-spacing: -0.5px;
            text-shadow: 0 2px 4px rgba(50, 77, 255, 0.2);
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background-color: var(--gray-bg);
            border: 2px solid #000000;
            border-radius: 15px;
            padding: 20px 30px;
            margin-bottom: 30px;
            box-shadow: 0 4px 4px 0 rgba(0, 0, 0, 0.3);
            position: relative;
        }

        .header-title {
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-size: 36px;
            font-weight: 800;
            color: #000000;
        }

        .btn-add-transaction {
            background-color: var(--transaction-color);
            color: #ffffff;
            border: 2px solid #000000;
            border-radius: 15px;
            padding: 12px 25px;
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-size: 18px;
            font-weight: 800;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            box-shadow: 0 4px 4px 0 rgba(0, 0, 0, 0.3);
            transition: all 0.3s ease;
        }

        .btn-add-transaction:hover {
            background-color: #f57c00;
            transform: translateY(-2px);
            box-shadow: 0 6px 8px rgba(0, 0, 0, 0.4);
        }

        .sidebar {
            width: 280px;
            float: left;
            background-color: #f8f9fa;
            border-radius: 15px;
            padding: 15px;
            margin-right: 25px;
            box-shadow: 0 2px 3px 0 rgba(0, 0, 0, 0.3);
        }

        .sidebar-menu {
            background-color: var(--gray-bg);
            border-radius: 15px;
            border: 2px solid #0e68dc;
            padding: 15px;
            margin-bottom: 12px;
            text-align: center;
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-size: 20px;
            font-weight: 800;
            color: var(--primary-color);
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            text-decoration: none;
            display: block;
            color: var(--primary-color);
        }

        .sidebar-menu:hover {
            transform: translateX(5px);
            background-color: #ffffff;
        }

        .sidebar-menu.active {
            background-color: #ffffff;
            border-color: var(--primary-color);
            box-shadow: 0 4px 8px rgba(50, 77, 255, 0.3);
        }

        .sidebar-menu.transaksi-active {
            background-color: #fff3e0;
            border-color: var(--transaction-color);
            box-shadow: 0 4px 8px rgba(255, 152, 0, 0.3);
        }

        .sidebar-menu i {
            margin-right: 8px;
        }

        .content-area {
            margin-left: 310px;
        }

        .table-header {
            display: grid;
            grid-template-columns: 80px 150px 200px 100px 150px 150px 180px;
            background-color: #a39999;
            border-radius: 10px;
            padding: 15px;
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-size: 18px;
            font-weight: 800;
            color: #000000;
            margin-bottom: 20px;
            text-align: center;
        }

        .transaction-row {
            display: grid;
            grid-template-columns: 80px 150px 200px 100px 150px 150px 180px;
            align-items: center;
            padding: 15px;
            background-color: var(--gray-bg);
            border-radius: 10px;
            margin-bottom: 15px;
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-size: 18px;
            font-weight: 800;
            color: #000000;
            position: relative;
            transition: all 0.3s ease;
        }

        .transaction-row:hover {
            transform: translateX(5px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
            background-color: #ffffff;
        }

        .price-display {
            color: #c62828;
            font-weight: 800;
        }

        .status-badge {
            display: inline-block;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 16px;
            font-weight: 700;
            text-align: center;
            min-width: 100px;
        }

        .status-pending {
            background-color: #ff9800;
            color: white;
        }

        .status-processing {
            background-color: #2196f3;
            color: white;
        }

        .status-completed {
            background-color: #4caf50;
            color: white;
        }

        .status-cancelled {
            background-color: #f44336;
            color: white;
        }

        .btn-action {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 8px 15px;
            border-radius: 8px;
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-size: 16px;
            font-weight: 800;
            cursor: pointer;
            transition: all 0.3s ease;
            border: 2px solid #000000;
            min-width: 85px;
        }

        .btn-edit {
            background-color: #a39999;
            color: #000000;
            margin-right: 10px;
        }

        .btn-edit:hover {
            background-color: #8a8080;
        }

        .btn-delete {
            background-color: #c90c0f;
            color: #ffffff;
        }

        .btn-delete:hover {
            background-color: #a50a0c;
            transform: scale(1.05);
        }

        .logout-btn {
            position: fixed;
            bottom: 30px;
            left: 40px;
            width: 60px;
            height: 60px;
            background-color: #ffcccc;
            border: 2px solid var(--danger-color);
            border-radius: 50%;
            display: flex;
            justify-content: center;
            align-items: center;
            cursor: pointer;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.3);
            transition: all 0.3s ease;
            z-index: 1000;
        }

        .logout-btn:hover {
            transform: scale(1.1) rotate(10deg);
            background-color: #ff9999;
        }

        .logout-btn i {
            font-size: 28px;
            color: var(--danger-color);
        }

        /* MODAL STYLING - Responsive & Tidak Melebihi Layar */
        .modal-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.85);
            z-index: 9999;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }

        .modal-overlay.active {
            display: flex;
        }

        .modal-content {
            background-color: #ffffff;
            border-radius: 25px;
            width: 100%;
            max-width: 650px; /* Dibatasi agar tidak melebihi layar */
            padding: 30px;
            box-shadow: 0 15px 50px rgba(0, 0, 0, 0.4);
            position: relative;
            transform: scale(0.95);
            opacity: 0;
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }

        .modal-overlay.active .modal-content {
            transform: scale(1);
            opacity: 1;
        }

        .close-modal {
            position: absolute;
            top: 20px;
            right: 25px;
            font-size: 36px;
            cursor: pointer;
            color: #888;
            transition: all 0.3s ease;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
        }

        .close-modal:hover {
            color: var(--danger-color);
            background-color: rgba(252, 4, 4, 0.1);
            transform: rotate(90deg);
        }

        .modal-title {
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-size: 36px;
            font-weight: 800;
            color: var(--transaction-color);
            margin-bottom: 30px;
            text-align: center;
            position: relative;
            padding-bottom: 15px;
        }

        .modal-title:after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 80px;
            height: 4px;
            background: linear-gradient(90deg, var(--transaction-color), #f57c00);
            border-radius: 2px;
        }

        .form-group {
            margin-bottom: 25px;
        }

        .form-group label {
            display: block;
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-size: 20px;
            font-weight: 700;
            margin-bottom: 12px;
            color: #222;
        }

        .form-control {
            width: 100%;
            padding: 15px 20px;
            border: 2px solid #e0e0e0;
            border-radius: 15px;
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-size: 19px;
            transition: all 0.3s ease;
            background-color: #f9f9f9;
        }

        .form-control:focus {
            outline: none;
            border-color: var(--transaction-color);
            box-shadow: 0 0 0 4px rgba(255, 152, 0, 0.2);
            background-color: #ffffff;
        }

        .form-control-number {
            -moz-appearance: textfield;
        }

        .form-control-number::-webkit-outer-spin-button,
        .form-control-number::-webkit-inner-spin-button {
            -webkit-appearance: none;
            margin: 0;
        }

        .btn-submit {
            width: 100%;
            padding: 18px;
            background: linear-gradient(135deg, var(--transaction-color) 0%, #f57c00 100%);
            border: none;
            border-radius: 18px;
            color: white;
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-size: 24px;
            font-weight: 800;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 6px 15px rgba(255, 152, 0, 0.45);
            position: relative;
            overflow: hidden;
        }

        .btn-submit:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(255, 152, 0, 0.6);
        }

        .alert {
            padding: 20px;
            border-radius: 15px;
            margin-bottom: 25px;
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-size: 20px;
            text-align: center;
            font-weight: 700;
            animation: slideDown 0.5s ease;
        }

        @keyframes slideDown {
            from {
                transform: translateY(-30px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 2px solid #c3e6cb;
            box-shadow: 0 4px 10px rgba(40, 167, 69, 0.2);
        }

        .alert-error {
            background-color: #f8d7da;
            color: #721c24;
            border: 2px solid #f5c6cb;
            box-shadow: 0 4px 10px rgba(220, 53, 69, 0.2);
        }

        .empty-state {
            text-align: center;
            padding: 60px 30px;
            background: linear-gradient(135deg, #fff3e0 0%, #ffe0b2 100%);
            border-radius: 25px;
            margin-top: 30px;
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-size: 28px;
            color: #e65100;
            border: 2px dashed #ffb74d;
        }

        .empty-state i {
            font-size: 80px;
            color: var(--transaction-color);
            margin-bottom: 25px;
            display: block;
        }

        @media (max-width: 1200px) {
            .sidebar {
                width: 100%;
                float: none;
                margin-right: 0;
                margin-bottom: 25px;
            }
            
            .content-area {
                margin-left: 0;
            }
            
            .table-header,
            .transaction-row {
                grid-template-columns: 80px 150px 150px 100px 120px;
                font-size: 16px;
            }
            
            .transaction-row div:nth-child(6),
            .transaction-row div:nth-child(7) {
                display: none;
            }
            
            .transaction-row div:last-child {
                grid-column: span 5;
                display: flex;
                justify-content: center;
                gap: 12px;
                margin-top: 15px;
            }
        }

        @media (max-width: 768px) {
            .header {
                flex-direction: column;
                gap: 20px;
                padding: 25px;
            }
            
            .header-title {
                font-size: 32px;
            }
            
            .btn-add-transaction {
                width: 100%;
                max-width: 300px;
                justify-content: center;
            }
            
            .table-header,
            .transaction-row {
                grid-template-columns: 1fr;
                text-align: left;
                padding: 20px;
                gap: 15px;
                font-size: 17px;
            }
            
            .transaction-row div {
                margin-bottom: 12px;
                padding-left: 15px;
            }
            
            .transaction-row div:first-child {
                font-weight: 800;
                color: var(--transaction-color);
                font-size: 20px;
            }
            
            .transaction-row div:last-child {
                display: flex;
                justify-content: flex-start;
                gap: 12px;
                margin-top: 15px;
                padding-left: 0;
                flex-wrap: wrap;
            }
            
            .sidebar-menu {
                font-size: 20px;
                padding: 18px;
                margin-bottom: 15px;
            }
            
            .price-display {
                font-size: 24px;
            }
            
            .status-badge {
                padding: 8px 20px;
                font-size: 18px;
                min-width: 120px;
            }
            
            /* Modal lebih kecil di mobile */
            .modal-content {
                max-width: 95%;
                padding: 20px 15px;
            }
            
            .modal-title {
                font-size: 28px;
            }
            
            .form-control {
                font-size: 17px;
                padding: 12px 15px;
            }
            
            .btn-submit {
                font-size: 20px;
                padding: 15px;
            }
        }
    </style>
</head>
<body>
    <div class="main-container">
        <div class="admin-panel-title">Admin Panel</div>
        
        <div class="header">
            <div class="header-title">Kelola Data Transaksi</div>
            <button class="btn-add-transaction" id="openAddModal">
                <i class="fas fa-shopping-cart"></i> Tambah Transaksi
            </button>
        </div>
        
        <div class="sidebar">
            <a href="admin_dashboard.php" class="sidebar-menu">
                <i class="fas fa-tachometer-alt"></i> Dashboard
            </a>
            <a href="kelola_data_user.php" class="sidebar-menu">
                <i class="fas fa-users"></i> Kelola Data User
            </a>
            <a href="kelola_data_petugas.php" class="sidebar-menu">
                <i class="fas fa-user-tie"></i> Kelola Data Petugas
            </a>
            <a href="kelola_data_produk.php" class="sidebar-menu">
                <i class="fas fa-box"></i> Kelola Data Produk
            </a>
            <a href="kelola_data_transaksi.php" class="sidebar-menu transaksi-active">
                <i class="fas fa-shopping-cart"></i> Kelola Data Transaksi
            </a>
            <a href="laporan_transaksi.php" class="sidebar-menu">
                <i class="fas fa-file-alt"></i> Laporan Transaksi
            </a>
            <a href="laporan_penjualan.php" class="sidebar-menu">
                <i class="fas fa-chart-line"></i> Laporan Penjualan
            </a>
            <a href="laporan_stok.php" class="sidebar-menu">
                <i class="fas fa-boxes"></i> Laporan Stok
            </a>
            <a href="backup_data.php" class="sidebar-menu">
                <i class="fas fa-database"></i> Backup Data
            </a>
            <a href="restore_data.php" class="sidebar-menu">
                <i class="fas fa-undo"></i> Restore Data
            </a>
        </div>
        
        <div class="content-area">
            <?php if ($success_msg): ?>
                <div class="alert alert-success"><?php echo $success_msg; ?></div>
            <?php endif; ?>
            
            <?php if ($error_msg): ?>
                <div class="alert alert-error"><?php echo $error_msg; ?></div>
            <?php endif; ?>
            
            <div class="table-header">
                <div>ID</div>
                <div>User</div>
                <div>Produk</div>
                <div>Jumlah</div>
                <div>Total Harga</div>
                <div>Status</div>
                <div>Aksi</div>
            </div>
            
            <?php if (empty($transactions)): ?>
                <div class="empty-state">
                    <i class="fas fa-shopping-cart"></i>
                    <div>Belum ada transaksi dalam sistem</div>
                    <div style="margin-top: 15px; font-size: 20px; color: #e65100;">
                        Klik "Tambah Transaksi" untuk membuat transaksi baru
                    </div>
                </div>
            <?php else: ?>
                <?php foreach ($transactions as $transaction): ?>
                    <div class="transaction-row">
                        <div><?php echo str_pad($transaction['id'], 5, '0', STR_PAD_LEFT); ?></div>
                        <div><?php echo htmlspecialchars($transaction['user_name'] ?? 'User ID: ' . $transaction['user_id']); ?></div>
                        <div><?php echo htmlspecialchars($transaction['product_name'] ?? 'Produk ID: ' . $transaction['product_id']); ?></div>
                        <div><?php echo $transaction['quantity']; ?></div>
                        <div class="price-display">Rp <?php echo number_format($transaction['total_price'], 0, ',', '.'); ?></div>
                        <div>
                            <?php 
                                $status_class = 'status-pending';
                                $status_text = 'Pending';
                                if ($transaction['status'] === 'processing') {
                                    $status_class = 'status-processing';
                                    $status_text = 'Processing';
                                } elseif ($transaction['status'] === 'completed') {
                                    $status_class = 'status-completed';
                                    $status_text = 'Completed';
                                } elseif ($transaction['status'] === 'cancelled') {
                                    $status_class = 'status-cancelled';
                                    $status_text = 'Cancelled';
                                }
                            ?>
                            <span class="status-badge <?php echo $status_class; ?>"><?php echo $status_text; ?></span>
                        </div>
                        <div>
                            <button class="btn-action btn-edit" 
                                    data-id="<?php echo $transaction['id']; ?>" 
                                    data-status="<?php echo $transaction['status']; ?>">
                                <i class="fas fa-edit"></i> Edit
                            </button>
                            <button class="btn-action btn-delete" 
                                    data-id="<?php echo $transaction['id']; ?>" 
                                    data-user="<?php echo htmlspecialchars($transaction['user_name'] ?? 'User'); ?>">
                                <i class="fas fa-trash"></i> Hapus
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        
        <a href="../logout.php" class="logout-btn" title="Logout">
            <i class="fas fa-sign-out-alt"></i>
        </a>
    </div>
    
    <!-- Add Transaction Modal -->
    <div class="modal-overlay" id="addModal">
        <div class="modal-content">
            <span class="close-modal" id="closeAddModal">&times;</span>
            <h2 class="modal-title">🛒 Tambah Transaksi Baru</h2>
            <form method="POST" action="">
                <input type="hidden" name="action" value="add_transaction">
                <div class="form-group">
                    <label for="addUserId">Pilih User</label>
                    <select id="addUserId" name="user_id" class="form-control" required>
                        <option value="">-- Pilih User --</option>
                        <?php foreach ($users as $user): ?>
                            <option value="<?php echo $user['id']; ?>"><?php echo htmlspecialchars($user['username']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="addProductId">Pilih Produk</label>
                    <select id="addProductId" name="product_id" class="form-control" required onchange="calculateTotal()">
                        <option value="">-- Pilih Produk --</option>
                        <?php foreach ($products as $product): ?>
                            <option value="<?php echo $product['id']; ?>" data-price="<?php echo $product['price']; ?>"><?php echo htmlspecialchars($product['name']); ?> - Rp <?php echo number_format($product['price'], 0, ',', '.'); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="addQuantity">Jumlah</label>
                    <input type="number" id="addQuantity" name="quantity" class="form-control form-control-number" required min="1" value="1" onchange="calculateTotal()">
                </div>
                <div class="form-group">
                    <label for="addTotalPrice">Total Harga (Rp)</label>
                    <input type="number" id="addTotalPrice" name="total_price" class="form-control form-control-number" required min="1000" step="100">
                </div>
                <div class="form-group">
                    <label for="addStatus">Status</label>
                    <select id="addStatus" name="status" class="form-control" required>
                        <option value="pending">Pending</option>
                        <option value="processing">Processing</option>
                        <option value="completed">Completed</option>
                        <option value="cancelled">Cancelled</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="addPaymentMethod">Metode Pembayaran</label>
                    <select id="addPaymentMethod" name="payment_method" class="form-control" required>
                        <option value="cash">Cash</option>
                        <option value="transfer">Transfer Bank</option>
                        <option value="ewallet">E-Wallet</option>
                        <option value="credit_card">Credit Card</option>
                    </select>
                </div>
                <button type="submit" class="btn-submit">
                    <i class="fas fa-shopping-cart" style="margin-right: 10px;"></i> Tambah Transaksi
                </button>
            </form>
        </div>
    </div>
    
    <!-- Edit Transaction Modal -->
    <div class="modal-overlay" id="editModal">
        <div class="modal-content">
            <span class="close-modal" id="closeEditModal">&times;</span>
            <h2 class="modal-title">✏️ Edit Status Transaksi</h2>
            <form method="POST" action="">
                <input type="hidden" name="action" value="edit_transaction">
                <input type="hidden" id="editId" name="id">
                <div class="form-group">
                    <label for="editStatus">Status Transaksi</label>
                    <select id="editStatus" name="status" class="form-control" required>
                        <option value="pending">Pending</option>
                        <option value="processing">Processing</option>
                        <option value="completed">Completed</option>
                        <option value="cancelled">Cancelled</option>
                    </select>
                </div>
                <div class="form-group" style="background: linear-gradient(135deg, #fff3e0 0%, #ffe0b2 100%); padding: 20px; border-radius: 15px; border: 2px solid #ffb74d; margin-top: 10px;">
                    <p style="font-family: 'Plus Jakarta Sans', sans-serif; color: #e65100; margin: 0; font-size: 17px; line-height: 1.5;">
                        <i class="fas fa-info-circle" style="margin-right: 10px; color: #e65100;"></i>
                        <strong>Catatan:</strong> Hanya status transaksi yang bisa diubah. Data lain seperti user, produk, dan jumlah tidak bisa diubah setelah transaksi dibuat.
                    </p>
                </div>
                <button type="submit" class="btn-submit" style="background: linear-gradient(135deg, #ff9800 0%, #f57c00 100%);">
                    <i class="fas fa-save" style="margin-right: 10px;"></i> Simpan Perubahan
                </button>
            </form>
        </div>
    </div>
    
    <!-- Delete Confirmation Modal -->
    <div class="modal-overlay" id="deleteModal">
        <div class="modal-content">
            <span class="close-modal" id="closeDeleteModal">&times;</span>
            <h2 class="modal-title" style="color: var(--danger-color);">🗑️ Konfirmasi Penghapusan Transaksi</h2>
            <p style="font-family: 'Plus Jakarta Sans', sans-serif; font-size: 24px; text-align: center; margin: 30px 0; color: #333; line-height: 1.6;">
                Anda yakin ingin menghapus transaksi berikut?<br>
                <strong id="deleteUser" style="color: var(--danger-color); font-size: 32px; display: block; margin-top: 10px;"></strong>
                <span style="display: block; color: #6c757d; font-size: 18px; margin-top: 15px; font-weight: 500;">
                    Tindakan ini tidak dapat dibatalkan!
                </span>
            </p>
            <form method="POST" action="">
                <input type="hidden" name="action" value="delete_transaction">
                <input type="hidden" id="deleteId" name="id">
                <button type="submit" class="btn-submit" style="background: linear-gradient(135deg, var(--danger-color) 0%, #a50a0c 100%); box-shadow: 0 6px 15px rgba(252, 4, 4, 0.4);">
                    <i class="fas fa-trash-alt" style="margin-right: 10px;"></i> Ya, Hapus Transaksi
                </button>
            </form>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Get modal elements
            const addModal = document.getElementById('addModal');
            const editModal = document.getElementById('editModal');
            const deleteModal = document.getElementById('deleteModal');
            const openAddModal = document.getElementById('openAddModal');
            const closeAddModal = document.getElementById('closeAddModal');
            const closeEditModal = document.getElementById('closeEditModal');
            const closeDeleteModal = document.getElementById('closeDeleteModal');
            
            // Open Add Modal
            openAddModal.addEventListener('click', function() {
                addModal.classList.add('active');
                document.body.style.overflow = 'hidden';
                document.getElementById('addUserId').focus();
                document.querySelector('#addModal form').reset();
                // Set default values
                document.getElementById('addQuantity').value = '1';
                document.getElementById('addStatus').value = 'pending';
                document.getElementById('addPaymentMethod').value = 'cash';
                document.getElementById('addTotalPrice').value = '100000';
            });
            
            // Close modals
            closeAddModal.addEventListener('click', function() {
                addModal.classList.remove('active');
                document.body.style.overflow = 'auto';
            });
            
            closeEditModal.addEventListener('click', function() {
                editModal.classList.remove('active');
                document.body.style.overflow = 'auto';
            });
            
            closeDeleteModal.addEventListener('click', function() {
                deleteModal.classList.remove('active');
                document.body.style.overflow = 'auto';
            });
            
            // Close modals when clicking outside content
            [addModal, editModal, deleteModal].forEach(modal => {
                modal.addEventListener('click', function(e) {
                    if (e.target === modal) {
                        modal.classList.remove('active');
                        document.body.style.overflow = 'auto';
                    }
                });
            });
            
            // Edit button functionality
            document.querySelectorAll('.btn-edit').forEach(button => {
                button.addEventListener('click', function(e) {
                    e.preventDefault();
                    
                    const id = this.getAttribute('data-id');
                    const status = this.getAttribute('data-status');
                    
                    document.getElementById('editId').value = id;
                    document.getElementById('editStatus').value = status;
                    
                    editModal.classList.add('active');
                    document.body.style.overflow = 'hidden';
                });
            });
            
            // Delete button functionality
            document.querySelectorAll('.btn-delete').forEach(button => {
                button.addEventListener('click', function(e) {
                    e.preventDefault();
                    
                    const id = this.getAttribute('data-id');
                    const user = this.getAttribute('data-user');
                    
                    document.getElementById('deleteId').value = id;
                    document.getElementById('deleteUser').textContent = user;
                    
                    deleteModal.classList.add('active');
                    document.body.style.overflow = 'hidden';
                });
            });
            
            // ESC key to close modals
            document.addEventListener('keydown', function(event) {
                if (event.key === 'Escape') {
                    [addModal, editModal, deleteModal].forEach(modal => {
                        modal.classList.remove('active');
                    });
                    document.body.style.overflow = 'auto';
                }
            });
            
            // Auto-close alerts after 6 seconds
            document.querySelectorAll('.alert').forEach(alert => {
                setTimeout(() => {
                    alert.style.transition = 'opacity 0.4s, transform 0.4s';
                    alert.style.opacity = '0';
                    alert.style.transform = 'translateY(-25px)';
                    setTimeout(() => alert.remove(), 400);
                }, 6000);
            });
            
            // Prevent form resubmission
            if (window.history.replaceState) {
                window.history.replaceState(null, null, window.location.href);
            }
        });
        
        // Calculate total price automatically
        function calculateTotal() {
            const productId = document.getElementById('addProductId').value;
            const quantity = parseInt(document.getElementById('addQuantity').value) || 1;
            
            if (productId) {
                const selectedOption = document.querySelector(`#addProductId option[value="${productId}"]`);
                const price = parseFloat(selectedOption.getAttribute('data-price')) || 0;
                const totalPrice = price * quantity;
                
                document.getElementById('addTotalPrice').value = totalPrice;
            }
        }
    </script>
</body>
</html>