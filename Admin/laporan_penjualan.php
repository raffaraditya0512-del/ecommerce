<?php
session_start();
require_once '../config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../Auth/login.php");
    exit();
}

// Get date filter
$period = isset($_GET['period']) ? $_GET['period'] : 'monthly';
$from_date = isset($_GET['from_date']) ? $_GET['from_date'] : date('Y-01-01');
$to_date = isset($_GET['to_date']) ? $_GET['to_date'] : date('Y-m-d');

// Calculate period
if ($period === 'daily') {
    $from_date = date('Y-m-d', strtotime('-30 days'));
    $to_date = date('Y-m-d');
} elseif ($period === 'weekly') {
    $from_date = date('Y-m-d', strtotime('-7 days'));
    $to_date = date('Y-m-d');
} elseif ($period === 'monthly') {
    $from_date = date('Y-m-01');
    $to_date = date('Y-m-d');
} elseif ($period === 'yearly') {
    $from_date = date('Y-01-01');
    $to_date = date('Y-m-d');
}

// Get sales data
$sales_data = [];
$products_data = [];

// Daily sales data
if ($period === 'daily') {
    $query = "SELECT DATE(created_at) as date, SUM(total_price) as revenue, COUNT(*) as transactions 
              FROM transactions 
              WHERE created_at BETWEEN '$from_date 00:00:00' AND '$to_date 23:59:59'
              GROUP BY DATE(created_at)
              ORDER BY date";
    $result = $conn->query($query);
    
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $sales_data[] = $row;
        }
    }
    
    // Get top products
    $query = "SELECT p.name, SUM(t.quantity) as total_quantity, SUM(t.total_price) as total_revenue
              FROM transactions t
              LEFT JOIN products p ON t.product_id = p.id
              WHERE t.created_at BETWEEN '$from_date 00:00:00' AND '$to_date 23:59:59'
              GROUP BY p.id
              ORDER BY total_quantity DESC
              LIMIT 5";
    $result = $conn->query($query);
    
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $products_data[] = $row;
        }
    }
}

// Monthly sales data
elseif ($period === 'monthly') {
    $query = "SELECT DATE_FORMAT(created_at, '%Y-%m') as month, SUM(total_price) as revenue, COUNT(*) as transactions 
              FROM transactions 
              WHERE created_at BETWEEN '$from_date 00:00:00' AND '$to_date 23:59:59'
              GROUP BY DATE_FORMAT(created_at, '%Y-%m')
              ORDER BY month";
    $result = $conn->query($query);
    
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $sales_data[] = $row;
        }
    }
    
    // Get top products
    $query = "SELECT p.name, SUM(t.quantity) as total_quantity, SUM(t.total_price) as total_revenue
              FROM transactions t
              LEFT JOIN products p ON t.product_id = p.id
              WHERE t.created_at BETWEEN '$from_date 00:00:00' AND '$to_date 23:59:59'
              GROUP BY p.id
              ORDER BY total_quantity DESC
              LIMIT 5";
    $result = $conn->query($query);
    
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $products_data[] = $row;
        }
    }
}

// Yearly sales data
elseif ($period === 'yearly') {
    $query = "SELECT YEAR(created_at) as year, SUM(total_price) as revenue, COUNT(*) as transactions 
              FROM transactions 
              WHERE created_at BETWEEN '$from_date 00:00:00' AND '$to_date 23:59:59'
              GROUP BY YEAR(created_at)
              ORDER BY year";
    $result = $conn->query($query);
    
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $sales_data[] = $row;
        }
    }
    
    // Get top products
    $query = "SELECT p.name, SUM(t.quantity) as total_quantity, SUM(t.total_price) as total_revenue
              FROM transactions t
              LEFT JOIN products p ON t.product_id = p.id
              WHERE t.created_at BETWEEN '$from_date 00:00:00' AND '$to_date 23:59:59'
              GROUP BY p.id
              ORDER BY total_quantity DESC
              LIMIT 5";
    $result = $conn->query($query);
    
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $products_data[] = $row;
        }
    }
}

// Calculate totals
$total_revenue = 0;
$total_transactions = 0;
foreach ($sales_data as $sale) {
    $total_revenue += $sale['revenue'];
    $total_transactions += $sale['transactions'];
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
    <title>Laporan Penjualan - Admin Panel</title>
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
        .period-filter {
            display: flex;
            gap: 15px;
            align-items: center;
        }
        .period-filter button {
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
        .period-filter button:hover {
            background: #512da8;
            transform: translateY(-2px);
        }
        .period-filter button.active {
            background: #512da8;
            box-shadow: 0 4px 8px rgba(81, 45, 168, 0.4);
        }
        .date-filter {
            display: flex;
            gap: 15px;
            align-items: center;
        }
        .date-filter input {
            padding: 12px 15px;
            border: 2px solid #ddd;
            border-radius: 10px;
            font-size: 18px;
            background-color: #fff;
        }
        .date-filter button {
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
        .date-filter button:hover {
            background: #512da8;
            transform: translateY(-2px);
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
        .sidebar-menu.penjualan-active {
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
            margin-bottom: 30px;
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
        .price-display {
            color: #c62828;
            font-weight: 800;
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
            .period-filter, .date-filter { flex-direction: column; width: 100%; }
            .period-filter button, .date-filter button { width: 100%; }
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
            <div class="header-title">Laporan Penjualan</div>
            <div class="period-filter">
                <button class="period-btn <?php echo $period === 'daily' ? 'active' : ''; ?>" data-period="daily">Harian</button>
                <button class="period-btn <?php echo $period === 'weekly' ? 'active' : ''; ?>" data-period="weekly">Mingguan</button>
                <button class="period-btn <?php echo $period === 'monthly' ? 'active' : ''; ?>" data-period="monthly">Bulanan</button>
                <button class="period-btn <?php echo $period === 'yearly' ? 'active' : ''; ?>" data-period="yearly">Tahunan</button>
            </div>
            <div class="date-filter">
                <input type="date" name="from_date" value="<?php echo $from_date; ?>">
                <input type="date" name="to_date" value="<?php echo $to_date; ?>">
                <button type="submit">Filter</button>
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
            <a href="laporan.php" class="sidebar-menu">
                <i class="fas fa-file-alt"></i> Laporan Transaksi
            </a>
            <a href="laporan_penjualan.php" class="sidebar-menu penjualan-active">
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
            
            <!-- Statistics Cards -->
            <div class="stats-container">
                <div class="stat-card">
                    <div class="stat-card-title">Total Penjualan</div>
                    <div class="stat-card-value"><?php echo number_format($total_transactions); ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-card-title">Pendapatan</div>
                    <div class="stat-card-value">Rp <?php echo number_format($total_revenue, 0, ',', '.'); ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-card-title">Rata-rata</div>
                    <div class="stat-card-value">Rp <?php echo $total_transactions > 0 ? number_format($total_revenue / $total_transactions, 0, ',', '.') : '0'; ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-card-title">Produk Terlaris</div>
                    <div class="stat-card-value"><?php echo !empty($products_data) ? htmlspecialchars($products_data[0]['name']) : 'N/A'; ?></div>
                </div>
            </div>
            
            <!-- Top Products Table -->
            <div class="table-container">
                <div class="table-title">🏆 5 Produk Terlaris</div>
                <table>
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Nama Produk</th>
                            <th>Jumlah Terjual</th>
                            <th>Pendapatan</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($products_data)): ?>
                            <tr>
                                <td colspan="4" style="text-align: center; padding: 20px;">Tidak ada data penjualan</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($products_data as $index => $product): ?>
                            <tr>
                                <td><?php echo $index + 1; ?></td>
                                <td><?php echo htmlspecialchars($product['name']); ?></td>
                                <td><?php echo $product['total_quantity']; ?></td>
                                <td class="price-display">Rp <?php echo number_format($product['total_revenue'], 0, ',', '.'); ?></td>
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