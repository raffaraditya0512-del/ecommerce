<?php
session_start();
require_once '../config.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../Auth/login.php");
    exit();
}

// Get statistics from database
$total_users = 0;
$total_products = 0;
$total_transactions = 0;

// Count total users
$result = $conn->query("SELECT COUNT(*) as count FROM users");
if ($result && $result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $total_users = $row['count'];
}

// Count total products - Skip if table doesn't exist
$result = $conn->query("SHOW TABLES LIKE 'products'");
if ($result && $result->num_rows > 0) {
    $result = $conn->query("SELECT COUNT(*) as count FROM products");
    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $total_products = $row['count'];
    }
} else {
    $total_products = 0; // Default jika tabel belum ada
}

// Count total transactions - Skip if table doesn't exist
$result = $conn->query("SHOW TABLES LIKE 'transactions'");
if ($result && $result->num_rows > 0) {
    $result = $conn->query("SELECT COUNT(*) as count FROM transactions");
    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $total_transactions = $row['count'];
    }
} else {
    $total_transactions = 0; // Default jika tabel belum ada
}

// Get last backup date (dummy data for now)
$last_backup = "16 Januari 2026";
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Dashboard | Admin - E-Commerce Radit</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" />
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=PoetsenOne:wght@400&display=swap" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    <style>
        :root {
            --default-font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto,
                Ubuntu, "Helvetica Neue", Helvetica, Arial, "PingFang SC",
                "Hiragino Sans GB", "Microsoft Yahei UI", "Microsoft Yahei",
                "Source Han Sans CN", sans-serif;
            --primary-color: #324dff;
            --primary-dark: #1a73e8;
            --primary-light: #e8f0fe;
            --secondary-color: #950a98;
            --text-dark: #000000;
            --text-light: #ffffff;
            --gray-bg: #d9d9d9;
            --gray-light: #f5f5f5;
            --danger-color: #fc0404;
            --danger-bg: #ffcccc;
            --shadow-sm: 0 2px 4px rgba(0, 0, 0, 0.1);
            --shadow-md: 0 4px 6px rgba(0, 0, 0, 0.15);
            --shadow-lg: 0 10px 20px rgba(0, 0, 0, 0.2);
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: var(--default-font-family);
            background-color: var(--gray-light);
            min-height: 100vh;
            padding: 0;
            margin: 0;
            overflow-x: hidden;
        }

        .main-container {
            width: 100%;
            min-height: 100vh;
            background-color: var(--gray-light);
            position: relative;
            overflow: hidden;
            padding: 0;
        }

        .header {
            width: calc(100% - 300px);
            height: 100px;
            background: linear-gradient(135deg, var(--gray-bg) 0%, #ffffff 100%);
            border: 2px solid var(--text-dark);
            position: fixed;
            top: 0;
            right: 0;
            box-shadow: var(--shadow-lg);
            margin-bottom: 30px;
            border-radius: 15px;
            padding: 0 30px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            z-index: 1000;
        }

        .header-title {
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-size: 36px;
            font-weight: 800;
            color: var(--text-dark);
            letter-spacing: -0.5px;
        }

        .btn-add-product {
            min-width: 180px;
            height: 60px;
            background: linear-gradient(135deg, var(--secondary-color) 0%, #7a087d 100%);
            border-radius: 15px;
            border: 2px solid var(--text-dark);
            cursor: pointer;
            transition: var(--transition);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 0 25px;
            box-shadow: var(--shadow-md);
        }

        .btn-add-product:hover {
            transform: translateY(-3px) scale(1.02);
            box-shadow: 0 6px 12px rgba(149, 10, 152, 0.4);
        }

        .btn-add-product:active {
            transform: translateY(0) scale(0.98);
        }

        .btn-add-product span {
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-size: 16px;
            font-weight: 800;
            color: var(--text-light);
            display: flex;
            align-items: center;
            gap: 8px;
        }

        /* Sidebar dengan background abu-abu full di kiri */
        .sidebar {
            width: 300px;
            position: fixed;
            top: 0;
            left: 0;
            bottom: 0;
            z-index: 999;
            background: linear-gradient(135deg, #666666 0%, #444444 100%);
            padding: 20px 15px;
            overflow-y: auto;
            overflow-x: hidden;
            scrollbar-width: thin;
            scrollbar-color: var(--primary-color) #555555;
        }

        /* Custom scrollbar */
        .sidebar::-webkit-scrollbar {
            width: 8px;
        }

        .sidebar::-webkit-scrollbar-track {
            background: #555555;
            border-radius: 10px;
        }

        .sidebar::-webkit-scrollbar-thumb {
            background: var(--primary-color);
            border-radius: 10px;
            border: 2px solid #555555;
        }

        .sidebar::-webkit-scrollbar-thumb:hover {
            background: var(--primary-dark);
        }

        .admin-panel-title {
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-size: 32px;
            font-weight: 800;
            color: var(--text-light);
            margin-bottom: 30px;
            text-align: center;
            letter-spacing: -0.5px;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
            flex-shrink: 0;
        }

        .sidebar-menu {
            background: linear-gradient(135deg, var(--gray-bg) 0%, #ffffff 100%);
            border-radius: 15px;
            border: 2px solid var(--primary-color);
            padding: 20px 0;
            margin-bottom: 12px;
            box-shadow: var(--shadow-md);
            cursor: pointer;
            transition: var(--transition);
            position: relative;
            overflow: hidden;
            flex-shrink: 0;
            text-decoration: none;
            display: block;
        }

        .sidebar-menu::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, rgba(50, 77, 255, 0.1) 0%, transparent 100%);
            opacity: 0;
            transition: var(--transition);
            z-index: 0;
        }

        .sidebar-menu:hover {
            transform: translateX(8px);
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.2);
            background: linear-gradient(135deg, #ffffff 0%, var(--gray-bg) 100%);
        }

        .sidebar-menu:hover::before {
            opacity: 1;
        }

        .sidebar-menu.active {
            background: linear-gradient(135deg, var(--text-light) 0%, var(--primary-light) 100%);
            border-color: var(--primary-color);
            box-shadow: 0 8px 16px rgba(50, 77, 255, 0.3);
            transform: translateX(4px);
        }

        .sidebar-menu.active::before {
            opacity: 0.3;
        }

        .sidebar-menu span {
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-size: 20px;
            font-weight: 700;
            color: var(--primary-color);
            display: block;
            text-align: center;
            position: relative;
            z-index: 1;
            transition: var(--transition);
        }

        .sidebar-menu:hover span {
            color: var(--primary-dark);
            transform: scale(1.05);
        }

        .logout-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 15px;
            width: 100%;
            padding: 20px 0;
            background: linear-gradient(135deg, var(--danger-bg) 0%, #ff9999 100%);
            border-radius: 15px;
            border: 2px solid var(--danger-color);
            margin-top: 25px;
            cursor: pointer;
            transition: var(--transition);
            text-decoration: none;
            color: var(--text-dark);
            box-shadow: var(--shadow-md);
            flex-shrink: 0;
        }

        .logout-btn:hover {
            transform: translateX(8px) scale(1.02);
            background: linear-gradient(135deg, #ff9999 0%, var(--danger-bg) 100%);
            box-shadow: 0 6px 12px rgba(252, 4, 4, 0.3);
        }

        .logout-btn:active {
            transform: translateX(4px) scale(0.98);
        }

        .logout-btn span {
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-size: 26px;
            font-weight: 800;
            color: var(--danger-color);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .logout-btn i {
            font-size: 24px;
        }

        .content-area {
            margin-left: 300px;
            padding-top: 120px;
            padding-right: 30px;
            padding-left: 30px;
            padding-bottom: 30px;
            animation: fadeIn 0.6s ease-out;
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

        .welcome-section {
            display: flex;
            align-items: center;
            margin-bottom: 40px;
            gap: 30px;
            padding: 25px 30px;
            background: linear-gradient(135deg, var(--gray-bg) 0%, #ffffff 100%);
            border-radius: 20px;
            border: 2px solid var(--text-dark);
            box-shadow: var(--shadow-md);
        }

        .welcome-text {
            font-family: 'PoetsenOne', cursive;
            font-size: 52px;
            font-weight: 400;
            color: var(--text-dark);
            line-height: 1.2;
            letter-spacing: -1px;
        }

        .welcome-text span {
            color: var(--primary-dark);
            font-weight: 700;
            text-shadow: 0 2px 4px rgba(26, 115, 232, 0.2);
        }

        .stats-container {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 30px;
            margin-bottom: 40px;
        }

        .stat-card {
            background: linear-gradient(135deg, var(--gray-bg) 0%, #ffffff 100%);
            border-radius: 20px;
            border: 2px solid var(--text-dark);
            padding: 35px 25px;
            text-align: center;
            box-shadow: var(--shadow-md);
            transition: var(--transition);
            cursor: pointer;
            position: relative;
            overflow: hidden;
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(50, 77, 255, 0.1) 0%, transparent 70%);
            opacity: 0;
            transition: var(--transition);
            z-index: 0;
        }

        .stat-card:hover {
            transform: translateY(-8px) scale(1.03);
            box-shadow: 0 12px 24px rgba(0, 0, 0, 0.25);
        }

        .stat-card:hover::before {
            opacity: 1;
        }

        .stat-card:active {
            transform: translateY(-4px) scale(1.01);
        }

        .stat-card-title {
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-size: 32px;
            font-weight: 700;
            color: var(--text-dark);
            margin-bottom: 25px;
            position: relative;
            z-index: 1;
        }

        .stat-card-value {
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-size: 60px;
            font-weight: 800;
            color: var(--primary-dark);
            position: relative;
            z-index: 1;
            line-height: 1;
            text-shadow: 0 2px 4px rgba(19, 34, 194, 0.2);
        }

        .action-buttons {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 25px;
            margin-bottom: 30px;
        }

        .action-btn {
            background: linear-gradient(135deg, var(--gray-bg) 0%, #ffffff 100%);
            border-radius: 20px;
            border: 2px solid var(--text-dark);
            padding: 35px 20px;
            text-align: center;
            box-shadow: var(--shadow-md);
            cursor: pointer;
            transition: var(--transition);
            position: relative;
            overflow: hidden;
        }

        .action-btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent 0%, rgba(50, 77, 255, 0.1) 50%, transparent 100%);
            transition: var(--transition);
            z-index: 0;
        }

        .action-btn:hover {
            transform: translateY(-6px) scale(1.02);
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.25);
            background: linear-gradient(135deg, #ffffff 0%, var(--gray-bg) 100%);
        }

        .action-btn:hover::before {
            left: 100%;
        }

        .action-btn:active {
            transform: translateY(-2px) scale(0.99);
        }

        .action-btn span {
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-size: 26px;
            font-weight: 700;
            color: var(--primary-color);
            display: block;
            position: relative;
            z-index: 1;
            transition: var(--transition);
        }

        .action-btn:hover span {
            color: var(--primary-dark);
            transform: scale(1.05);
        }

        .backup-section {
            background: linear-gradient(135deg, #a39999 0%, #c0c0c0 100%);
            border: 3px solid var(--text-dark);
            padding: 25px 30px;
            border-radius: 15px;
            margin-top: 25px;
            box-shadow: var(--shadow-md);
        }

        .backup-title {
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-size: 30px;
            font-weight: 700;
            color: var(--text-dark);
            margin-bottom: 12px;
        }

        .backup-date {
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-size: 22px;
            font-weight: 500;
            color: #444;
        }

        /* Responsive Design */
        @media (max-width: 1200px) {
            .sidebar {
                width: 250px;
            }
            
            .header {
                width: calc(100% - 250px);
            }
            
            .content-area {
                margin-left: 250px;
            }
            
            .stats-container {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .action-buttons {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 992px) {
            .sidebar {
                width: 220px;
            }
            
            .header {
                width: calc(100% - 220px);
                flex-direction: column;
                height: auto;
                padding: 25px;
                gap: 20px;
            }
            
            .content-area {
                margin-left: 220px;
            }
            
            .btn-add-product {
                width: 100%;
                max-width: 300px;
            }
        }

        @media (max-width: 768px) {
            .sidebar {
                width: 100%;
                height: auto;
                position: relative;
                top: auto;
                bottom: auto;
                max-height: none;
                margin-bottom: 25px;
            }
            
            .header {
                width: 100%;
                position: relative;
            }
            
            .content-area {
                margin-left: 0;
                padding-top: 150px;
            }
            
            .stats-container {
                grid-template-columns: 1fr;
            }
            
            .welcome-text {
                font-size: 40px;
            }
            
            .stat-card-title {
                font-size: 28px;
            }
            
            .stat-card-value {
                font-size: 48px;
            }
            
            .header-title {
                font-size: 28px;
            }
            
            .btn-add-product {
                min-width: 150px;
                height: 50px;
            }
            
            .btn-add-product span {
                font-size: 14px;
            }
            
            .action-btn span {
                font-size: 22px;
            }
            
            .admin-panel-title {
                font-size: 28px;
            }
            
            .sidebar-menu span {
                font-size: 18px;
            }
            
            .logout-btn span {
                font-size: 22px;
            }
        }

        @media (max-width: 480px) {
            .header {
                padding: 20px 15px;
            }
            
            .header-title {
                font-size: 24px;
            }
            
            .btn-add-product {
                min-width: 130px;
                height: 45px;
                padding: 0 15px;
            }
            
            .btn-add-product span {
                font-size: 12px;
                gap: 5px;
            }
            
            .welcome-text {
                font-size: 32px;
            }
            
            .stat-card {
                padding: 25px 15px;
            }
            
            .stat-card-value {
                font-size: 40px;
            }
            
            .action-btn {
                padding: 25px 15px;
            }
            
            .action-btn span {
                font-size: 20px;
            }
            
            .backup-title {
                font-size: 24px;
            }
            
            .backup-date {
                font-size: 18px;
            }
        }
    </style>
</head>
<body>
    <div class="main-container">
        <!-- Header -->
        <div class="header">
            <div class="header-title">Dashboard</div>
            <a href="kelola_data_produk.php" class="btn-add-product">
                <span><i class="fas fa-plus"></i> Tambah Produk</span>
            </a>
        </div>

        <!-- Sidebar -->
        <div class="sidebar">
            <div class="admin-panel-title">Admin Panel</div>
            
            <a href="admin_dashboard.php" class="sidebar-menu active">
                <span><i class="fas fa-tachometer-alt"></i> Dashboard</span>
            </a>
            
            <a href="kelola_data_user.php" class="sidebar-menu">
                <span><i class="fas fa-users"></i> Kelola Data User</span>
            </a>
            
            <a href="kelola_data_petugas.php" class="sidebar-menu">
                <span><i class="fas fa-user-tie"></i> Kelola Data Petugas</span>
            </a>
            
            <a href="kelola_data_produk.php" class="sidebar-menu">
                <span><i class="fas fa-box"></i> Kelola Data Produk</span>
            </a>
            
            <a href="kelola_data_transaksi.php" class="sidebar-menu">
                <span><i class="fas fa-shopping-cart"></i> Kelola Data Transaksi</span>
            </a>
            
            <a href="laporan.php" class="sidebar-menu">
                <span><i class="fas fa-file-alt"></i> Laporan</span>
            </a>
            
            <a href="backup_data.php" class="sidebar-menu">
                <span><i class="fas fa-database"></i> Backup Data</span>
            </a>
            
            <a href="restore_data.php" class="sidebar-menu">
                <span><i class="fas fa-undo"></i> Restore Data</span>
            </a>
            
            <a href="../Auth/logout.php" class="logout-btn">
                <span><i class="fas fa-sign-out-alt"></i> Logout</span>
            </a>
        </div>

        <!-- Content Area -->
        <div class="content-area">
            <!-- Welcome Section -->
            <div class="welcome-section">
                <div class="welcome-text">
                    Selamat datang, <span>Admin</span>
                </div>
            </div>

            <!-- Statistics Cards -->
            <div class="stats-container">
                <div class="stat-card">
                    <div class="stat-card-title">Total User</div>
                    <div class="stat-card-value"><?php echo number_format($total_users, 0, ',', '.'); ?></div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-card-title">Total Produk</div>
                    <div class="stat-card-value"><?php echo number_format($total_products, 0, ',', '.'); ?></div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-card-title">Total Payment</div>
                    <div class="stat-card-value"><?php echo number_format($total_transactions, 0, ',', '.'); ?></div>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="action-buttons">
                <a href="kelola_data_user.php" class="action-btn">
                    <span><i class="fas fa-users"></i> Kelola Data User</span>
                </a>
                
                <a href="kelola_data_petugas.php" class="action-btn">
                    <span><i class="fas fa-user-tie"></i> Kelola Data Petugas</span>
                </a>
                
                <a href="kelola_data_produk.php" class="action-btn">
                    <span><i class="fas fa-box"></i> Kelola Data Produk</span>
                </a>
                
                <a href="kelola_data_transaksi.php" class="action-btn">
                    <span><i class="fas fa-shopping-cart"></i> Kelola Data Transaksi</span>
                </a>
            </div>

            <!-- Backup Section -->
            <div class="backup-section">
                <div class="backup-title">Backup Terakhir: <?php echo $last_backup; ?></div>
                <div class="backup-date">Status: Semua data berhasil di-backup</div>
            </div>
        </div>
    </div>

    <script>
        // Add smooth hover effect to stat cards
        document.querySelectorAll('.stat-card').forEach(card => {
            card.addEventListener('mouseenter', function() {
                this.style.transform = 'translateY(-8px) scale(1.03)';
            });
            
            card.addEventListener('mouseleave', function() {
                this.style.transform = 'translateY(0) scale(1)';
            });
        });

        // Add smooth hover effect to action buttons
        document.querySelectorAll('.action-btn').forEach(btn => {
            btn.addEventListener('mouseenter', function() {
                this.style.transform = 'translateY(-6px) scale(1.02)';
            });
            
            btn.addEventListener('mouseleave', function() {
                this.style.transform = 'translateY(0) scale(1)';
            });
        });

        // Add click ripple effect
        document.querySelectorAll('.action-btn, .stat-card').forEach(element => {
            element.addEventListener('click', function(e) {
                const ripple = document.createElement('span');
                ripple.style.position = 'absolute';
                ripple.style.width = '0';
                ripple.style.height = '0';
                ripple.style.borderRadius = '50%';
                ripple.style.backgroundColor = 'rgba(50, 77, 255, 0.3)';
                ripple.style.transform = 'scale(0)';
                ripple.style.animation = 'ripple 0.6s ease-out';
                
                const rect = this.getBoundingClientRect();
                const x = e.clientX - rect.left;
                const y = e.clientY - rect.top;
                
                ripple.style.left = x + 'px';
                ripple.style.top = y + 'px';
                
                this.appendChild(ripple);
                
                setTimeout(() => {
                    ripple.remove();
                }, 600);
            });
        });

        // Add CSS for ripple effect
        const style = document.createElement('style');
        style.textContent = `
            @keyframes ripple {
                to {
                    width: 200px;
                    height: 200px;
                    opacity: 0;
                }
            }
        `;
        document.head.appendChild(style);

        // Add toast notification for button clicks
        document.querySelector('.btn-add-product').addEventListener('click', function(e) {
            e.preventDefault();
            showToast('Fitur Tambah Produk akan segera tersedia!', 'info');
        });

        function showToast(message, type = 'info') {
            const toast = document.createElement('div');
            toast.style.position = 'fixed';
            toast.style.top = '20px';
            toast.style.right = '20px';
            toast.style.padding = '15px 25px';
            toast.style.borderRadius = '10px';
            toast.style.color = '#fff';
            toast.style.fontFamily = "'Plus Jakarta Sans', sans-serif";
            toast.style.fontSize = '16px';
            toast.style.fontWeight = '600';
            toast.style.boxShadow = '0 4px 12px rgba(0, 0, 0, 0.2)';
            toast.style.zIndex = '9999';
            toast.style.animation = 'slideIn 0.3s ease-out, fadeOut 0.3s ease-out 2.7s';
            toast.style.maxWidth = '400px';
            toast.style.textAlign = 'center';
            
            let bgColor, borderColor;
            switch(type) {
                case 'success':
                    bgColor = '#28a745';
                    borderColor = '#218838';
                    break;
                case 'error':
                    bgColor = '#dc3545';
                    borderColor = '#c82333';
                    break;
                case 'info':
                    bgColor = '#17a2b8';
                    borderColor = '#138496';
                    break;
                default:
                    bgColor = '#ffc107';
                    borderColor = '#e0a800';
            }
            
            toast.style.background = bgColor;
            toast.style.border = `2px solid ${borderColor}`;
            toast.textContent = message;
            
            document.body.appendChild(toast);
            
            setTimeout(() => {
                toast.remove();
            }, 3000);
        }

        // Add CSS for toast animation
        const toastStyle = document.createElement('style');
        toastStyle.textContent = `
            @keyframes slideIn {
                from {
                    transform: translateX(400px);
                    opacity: 0;
                }
                to {
                    transform: translateX(0);
                    opacity: 1;
                }
            }
            @keyframes fadeOut {
                from {
                    opacity: 1;
                }
                to {
                    opacity: 0;
                    transform: translateX(50px);
                }
            }
        `;
        document.head.appendChild(toastStyle);
    </script>
</body>
</html>