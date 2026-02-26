<?php
session_start();
require_once '../config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../Auth/login.php");
    exit();
}

// Get stock filter
$stock_filter = isset($_GET['stock_filter']) ? $_GET['stock_filter'] : 'all';
$low_stock_threshold = 10; // Threshold for low stock

// Get products
$products = [];
$where = '';
if ($stock_filter === 'low') {
    $where = "WHERE stock <= $low_stock_threshold";
} elseif ($stock_filter === 'critical') {
    $where = "WHERE stock = 0";
} else {
    $where = "";
}

$query = "SELECT * FROM products $where ORDER BY stock ASC";
$result = $conn->query($query);

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $products[] = $row;
    }
}

// Calculate stock metrics
$total_products = count($products);
$low_stock_count = 0;
$critical_stock_count = 0;
$healthy_stock_count = 0;

foreach ($products as $product) {
    if ($product['stock'] <= $low_stock_threshold) $low_stock_count++;
    if ($product['stock'] == 0) $critical_stock_count++;
    if ($product['stock'] > $low_stock_threshold) $healthy_stock_count++;
}

// Get messages
$success_msg = isset($_SESSION['success_msg']) ? $_SESSION['success_msg'] : null;
$error_msg = isset($_SESSION['error_msg']) ? $_SESSION['error_msg'] : null;
unset($_SESSION['success_msg'], $_SESSION['error_msg']);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Stok - Admin Panel</title>
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
            --report-color: #673ab7;
        }
        * { 
            margin: 0; 
            padding: 0; 
            box-sizing: border-box; 
        }
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: #ffffff;
            color: #000000;
            min-height: 100vh;
            padding: 20px;
            overflow-x: hidden;
        }
        .main-container {
            max-width: 1400px;
            margin: 0 auto;
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
        .stock-filter {
            display: flex;
            gap: 15px;
            align-items: center;
        }
        .stock-filter button {
            background: var(--report-color);
            color: white;
            border: none;
            border-radius: 10px;
            padding: 12px 25px;
            font-size: 18px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }
        .stock-filter button:hover {
            background: #512da8;
            transform: translateY(-2px);
        }
        .stock-filter button.active {
            background: #512da8;
            box-shadow: 0 4px 8px rgba(81, 45, 168, 0.4);
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
            text-decoration: none;
            display: block;
            color: var(--primary-color);
        }
        .sidebar-menu:hover { transform: translateX(5px); background-color: #ffffff; }
        .sidebar-menu.active {
            background-color: #e0e0ff;
            border-color: var(--report-color);
            box-shadow: 0 4px 8px rgba(103, 58, 183, 0.3);
        }
        .sidebar-menu.stok-active {
            background-color: #f3e5f5;
            border-color: var(--report-color);
            box-shadow: 0 4px 8px rgba(103, 58, 183, 0.3);
        }
        .sidebar-menu i { margin-right: 8px; }
        .content-area {
            margin-left: 310px;
        }
        .stats-container {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
            margin-bottom: 30px;
        }
        .stat-card {
            background: linear-gradient(135deg, var(--gray-bg) 0%, #ffffff 100%);
            border-radius: 20px;
            padding: 25px;
            text-align: center;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
            border: 2px solid #000000;
        }
        .stat-card-title { 
            font-size: 20px; 
            font-weight: 700; 
            color: #666; 
            margin-bottom: 10px; 
        }
        .stat-card-value { 
            font-size: 42px; 
            font-weight: 800; 
            color: var(--report-color); 
        }
        .table-container {
            background: white;
            border-radius: 20px;
            padding: 25px;
            border: 2px solid #000000;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }
        .table-title { 
            font-size: 26px; 
            font-weight: 800; 
            color: var(--report-color); 
            margin-bottom: 20px; 
            text-align: center; 
        }
        table { 
            width: 100%; 
            border-collapse: collapse; 
        }
        th, td { 
            padding: 15px; 
            text-align: left; 
            border-bottom: 1px solid #ddd; 
            font-size: 18px; 
        }
        th { 
            background-color: var(--gray-bg); 
            font-weight: 800; 
        }
        tr:hover { 
            background-color: #f5f5f5; 
        }
        .stock-badge {
            display: inline-block;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 16px;
            font-weight: 700;
        }
        .stock-high { background: #4caf50; color: white; }
        .stock-medium { background: #ff9800; color: white; }
        .stock-low { background: #f44336; color: white; }
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
        .logout-btn i { font-size: 28px; color: var(--danger-color); }
        @media (max-width: 1200px) {
            .sidebar { width: 100%; float: none; margin-bottom: 25px; }
            .content-area { margin-left: 0; }
            .stats-container { grid-template-columns: repeat(2, 1fr); }
            .table-container { 
                padding: 20px; 
                margin-bottom: 20px; 
            }
            .table-title { font-size: 24px; }
        }
        @media (max-width: 768px) {
            .header { flex-direction: column; gap: 15px; }
            .stats-container { grid-template-columns: 1fr; }
            .stock-filter { flex-direction: column; width: 100%; }
            .stock-filter button { width: 100%; }
            .table-container { 
                padding: 15px; 
                margin-bottom: 15px; 
            }
            .table-title { font-size: 20px; }
            th, td { padding: 12px 10px; font-size: 16px; }
        }
    </style>
</head>
<body>
    <div class="main-container">
        <div class="admin-panel-title">Admin Panel</div>
        
        <div class="header">
            <div class="header-title">Laporan Stok</div>
            <div class="stock-filter">
                <button class="stock-btn <?php echo $stock_filter === 'all' ? 'active' : ''; ?>" data-filter="all">Semua</button>
                <button class="stock-btn <?php echo $stock_filter === 'low' ? 'active' : ''; ?>" data-filter="low">Stok Rendah</button>
                <button class="stock-btn <?php echo $stock_filter === 'critical' ? 'active' : ''; ?>" data-filter="critical">Stok Habis</button>
            </div>
        </div>
        
        <div class="sidebar">
            <a href="../admin_dashboard.php" class="sidebar-menu">
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
            <a href="kelola_data_transaksi.php" class="sidebar-menu">
                <i class="fas fa-shopping-cart"></i> Kelola Data Transaksi
            </a>
            <a href="laporan_transaksi.php" class="sidebar-menu">
                <i class="fas fa-file-alt"></i> Laporan Transaksi
            </a>
            <a href="laporan_penjualan.php" class="sidebar-menu">
                <i class="fas fa-chart-line"></i> Laporan Penjualan
            </a>
            <a href="laporan_stok.php" class="sidebar-menu stok-active">
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
            
            <!-- Statistics Cards -->
            <div class="stats-container">
                <div class="stat-card">
                    <div class="stat-card-title">Total Produk</div>
                    <div class="stat-card-value"><?php echo number_format($total_products); ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-card-title">Stok Sehat</div>
                    <div class="stat-card-value"><?php echo number_format($healthy_stock_count); ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-card-title">Stok Rendah</div>
                    <div class="stat-card-value"><?php echo number_format($low_stock_count); ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-card-title">Stok Habis</div>
                    <div class="stat-card-value"><?php echo number_format($critical_stock_count); ?></div>
                </div>
            </div>
            
            <!-- Products Table -->
            <div class="table-container">
                <div class="table-title">📦 Daftar Produk</div>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nama Produk</th>
                            <th>Harga</th>
                            <th>Stok</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($products)): ?>
                            <tr>
                                <td colspan="6" style="text-align: center; padding: 20px;">Tidak ada produk ditemukan</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($products as $product): ?>
                            <tr>
                                <td><?php echo str_pad($product['id'], 5, '0', STR_PAD_LEFT); ?></td>
                                <td><?php echo htmlspecialchars($product['name']); ?></td>
                                <td class="price-display">Rp <?php echo number_format($product['price'], 0, ',', '.'); ?></td>
                                <td><?php echo $product['stock']; ?></td>
                                <td>
                                    <?php 
                                    $stock_class = 'stock-high';
                                    if ($product['stock'] <= $low_stock_threshold && $product['stock'] > 0) {
                                        $stock_class = 'stock-low';
                                    } elseif ($product['stock'] == 0) {
                                        $stock_class = 'stock-low';
                                    }
                                    ?>
                                    <span class="stock-badge <?php echo $stock_class; ?>">
                                        <?php 
                                        if ($product['stock'] == 0) echo 'Habis';
                                        elseif ($product['stock'] <= $low_stock_threshold) echo 'Rendah';
                                        else echo 'Cukup';
                                        ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="kelola_data_produk.php" class="btn-edit" style="background: #a39999; color: white; padding: 8px 15px; border-radius: 8px; text-decoration: none; margin-right: 5px;">
                                        <i class="fas fa-edit"></i> Edit
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <a href="../logout.php" class="logout-btn" title="Logout">
            <i class="fas fa-sign-out-alt"></i>
        </a>
    </div>
</body>
</html>