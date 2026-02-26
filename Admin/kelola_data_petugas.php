<?php
session_start();
require_once '../config.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../Auth/login.php");
    exit();
}

// Handle Add Staff
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_staff') {
    $username = trim($_POST['username']);
    $nama = trim($_POST['nama']);
    $password = $_POST['password'];
    
    // Validation
    $errors = [];
    if (strlen($username) < 4) $errors[] = "Username minimal 4 karakter";
    if (strlen($nama) < 2) $errors[] = "Nama minimal 2 karakter";
    if (strlen($password) < 6) $errors[] = "Password minimal 6 karakter";
    
    // Check username exists
    $stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        $errors[] = "Username sudah digunakan";
    }
    $stmt->close();
    
    if (empty($errors)) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, ?)");
        // Use username as email for staff (or generate dummy email)
        $email = $username . '@petugas.ecommerce.com';
        $role = 'petugas';
        $stmt->bind_param("ssss", $username, $email, $hashed_password, $role);
        if ($stmt->execute()) {
            $_SESSION['success_msg'] = "Petugas berhasil ditambahkan!";
        } else {
            $errors[] = "Gagal menambah petugas: " . $stmt->error;
        }
        $stmt->close();
    }
    
    if (!empty($errors)) {
        $_SESSION['error_msg'] = implode("<br>", $errors);
    }
    
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

// Handle Edit Staff
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit_staff') {
    $id = intval($_POST['id']);
    $username = trim($_POST['username']);
    $nama = trim($_POST['nama']);
    
    // Validation
    $errors = [];
    if (strlen($username) < 4) $errors[] = "Username minimal 4 karakter";
    if (strlen($nama) < 2) $errors[] = "Nama minimal 2 karakter";
    
    // Check username exists (excluding current staff)
    $stmt = $conn->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
    $stmt->bind_param("si", $username, $id);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        $errors[] = "Username sudah digunakan";
    }
    $stmt->close();
    
    if (empty($errors)) {
        $stmt = $conn->prepare("UPDATE users SET username = ? WHERE id = ?");
        $stmt->bind_param("si", $username, $id);
        if ($stmt->execute()) {
            $_SESSION['success_msg'] = "Data petugas berhasil diupdate!";
        } else {
            $errors[] = "Gagal mengupdate petugas: " . $stmt->error;
        }
        $stmt->close();
    }
    
    if (!empty($errors)) {
        $_SESSION['error_msg'] = implode("<br>", $errors);
    }
    
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

// Handle Delete Staff
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_staff') {
    $id = intval($_POST['id']);
    
    $stmt = $conn->prepare("DELETE FROM users WHERE id = ? AND role = 'petugas'");
    $stmt->bind_param("i", $id);
    if ($stmt->execute() && $stmt->affected_rows > 0) {
        $_SESSION['success_msg'] = "Petugas berhasil dihapus!";
    } else {
        $_SESSION['error_msg'] = "Gagal menghapus petugas atau petugas tidak ditemukan";
    }
    $stmt->close();
    
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

// Get messages from session
$success_msg = isset($_SESSION['success_msg']) ? $_SESSION['success_msg'] : null;
$error_msg = isset($_SESSION['error_msg']) ? $_SESSION['error_msg'] : null;
unset($_SESSION['success_msg'], $_SESSION['error_msg']);

// Get all staff (petugas) from database
$staff = [];
$result = $conn->query("SELECT id, username, role, created_at FROM users WHERE role = 'petugas' ORDER BY created_at DESC");
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        // Extract nama from username (for demo purposes)
        // In real app, you should have a separate 'nama' field in database
        $parts = explode('_', $row['username']);
        $nama = count($parts) > 1 ? ucfirst($parts[0]) . ' ' . ucfirst($parts[1]) : ucfirst($row['username']);
        
        $staff[] = [
            'id' => $row['id'],
            'username' => $row['username'],
            'nama' => $nama,
            'role' => $row['role']
        ];
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Kelola Data Petugas - Admin Panel</title>
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
            --staff-color: #10db1e;
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

        .btn-add-staff {
            background-color: #950a98;
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

        .btn-add-staff:hover {
            background-color: #7a087d;
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

        .sidebar-menu.petugas-active {
            background-color: #e8f5e9;
            border-color: var(--staff-color);
            box-shadow: 0 4px 8px rgba(16, 219, 30, 0.3);
        }

        .sidebar-menu i {
            margin-right: 8px;
        }

        .content-area {
            margin-left: 310px;
        }

        .table-header {
            display: grid;
            grid-template-columns: 100px 180px 250px 180px;
            background-color: #a39999;
            border-radius: 10px;
            padding: 15px;
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-size: 24px;
            font-weight: 800;
            color: #000000;
            margin-bottom: 20px;
            text-align: center;
        }

        .staff-row {
            display: grid;
            grid-template-columns: 100px 180px 250px 180px;
            align-items: center;
            padding: 15px;
            background-color: var(--gray-bg);
            border-radius: 10px;
            margin-bottom: 15px;
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-size: 20px;
            font-weight: 800;
            color: #000000;
            position: relative;
            transition: all 0.3s ease;
        }

        .staff-row:hover {
            transform: translateX(5px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
            background-color: #ffffff;
        }

        .btn-action {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 8px 15px;
            border-radius: 8px;
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-size: 18px;
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

        /* MODAL STYLING */
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
            max-width: 650px;
            padding: 40px;
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
            color: var(--staff-color);
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
            background: linear-gradient(90deg, var(--staff-color), #0ea616);
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
            padding: 18px 20px;
            border: 2px solid #e0e0e0;
            border-radius: 15px;
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-size: 19px;
            transition: all 0.3s ease;
            background-color: #f9f9f9;
        }

        .form-control:focus {
            outline: none;
            border-color: var(--staff-color);
            box-shadow: 0 0 0 4px rgba(16, 219, 30, 0.2);
            background-color: #ffffff;
        }

        .btn-submit {
            width: 100%;
            padding: 18px;
            background: linear-gradient(135deg, var(--staff-color) 0%, #0ea616 100%);
            border: none;
            border-radius: 18px;
            color: white;
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-size: 24px;
            font-weight: 800;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 6px 15px rgba(16, 219, 30, 0.45);
            position: relative;
            overflow: hidden;
        }

        .btn-submit:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(16, 219, 30, 0.6);
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
            background: linear-gradient(135deg, #e8f5e9 0%, #c8e6c9 100%);
            border-radius: 25px;
            margin-top: 30px;
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-size: 28px;
            color: #2e7d32;
            border: 2px dashed #81c784;
        }

        .empty-state i {
            font-size: 80px;
            color: #4caf50;
            margin-bottom: 25px;
            display: block;
        }

        @media (max-width: 1100px) {
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
            .staff-row {
                grid-template-columns: 90px 160px 1fr;
            }
            
            .staff-row div:last-child {
                grid-column: span 3;
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
            
            .btn-add-staff {
                width: 100%;
                max-width: 300px;
                justify-content: center;
            }
            
            .table-header,
            .staff-row {
                grid-template-columns: 1fr;
                text-align: left;
                padding: 20px;
                gap: 15px;
            }
            
            .staff-row div {
                margin-bottom: 12px;
                padding-left: 15px;
                font-size: 19px;
            }
            
            .staff-row div:first-child {
                font-weight: 800;
                color: var(--staff-color);
                font-size: 22px;
            }
            
            .staff-row div:last-child {
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
        }
    </style>
</head>
<body>
    <div class="main-container">
        <div class="admin-panel-title">Admin Panel</div>
        
        <div class="header">
            <div class="header-title">Kelola Data Petugas</div>
            <button class="btn-add-staff" id="openAddModal">
                <i class="fas fa-user-plus"></i> Tambah Petugas
            </button>
        </div>
        
        <div class="sidebar">
            <a href="admin_dashboard.php" class="sidebar-menu">
                <i class="fas fa-tachometer-alt"></i> Dashboard
            </a>
            <a href="kelola_data_user.php" class="sidebar-menu">
                <i class="fas fa-users"></i> Kelola Data User
            </a>
            <a href="kelola_data_petugas.php" class="sidebar-menu petugas-active">
                <i class="fas fa-user-tie"></i> Kelola Data Petugas
            </a>
            <a href="kelola_data_produk.php" class="sidebar-menu">
                <i class="fas fa-box"></i> Kelola Data Produk
            </a>
            <a href="kelola_data_transaksi.php" class="sidebar-menu">
                <i class="fas fa-shopping-cart"></i> Kelola Data Transaksi
            </a>
            <a href="laporan.php" class="sidebar-menu">
                <i class="fas fa-file-alt"></i> Laporan
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
                <div>Nama</div>
                <div>Username</div>
                <div>Aksi</div>
            </div>
            
            <?php if (empty($staff)): ?>
                <div class="empty-state">
                    <i class="fas fa-user-friends"></i>
                    <div>Belum ada data petugas dalam sistem</div>
                    <div style="margin-top: 15px; font-size: 20px; color: #43a047;">
                        Klik "Tambah Petugas" untuk menambahkan staff baru
                    </div>
                </div>
            <?php else: ?>
                <?php foreach ($staff as $petugas): ?>
                    <div class="staff-row">
                        <div><?php echo str_pad($petugas['id'], 5, '0', STR_PAD_LEFT); ?></div>
                        <div><?php echo htmlspecialchars($petugas['nama']); ?></div>
                        <div><?php echo htmlspecialchars($petugas['username']); ?></div>
                        <div>
                            <button class="btn-action btn-edit" 
                                    data-id="<?php echo $petugas['id']; ?>" 
                                    data-username="<?php echo htmlspecialchars($petugas['username']); ?>" 
                                    data-nama="<?php echo htmlspecialchars($petugas['nama']); ?>">
                                <i class="fas fa-edit"></i> Edit
                            </button>
                            <button class="btn-action btn-delete" 
                                    data-id="<?php echo $petugas['id']; ?>" 
                                    data-nama="<?php echo htmlspecialchars($petugas['nama']); ?>">
                                <i class="fas fa-trash"></i> Hapus
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        
        <a href="../Auth/logout.php" class="logout-btn" title="Logout">
            <i class="fas fa-sign-out-alt"></i>
        </a>
    </div>
    
    <!-- Add Staff Modal -->
    <div class="modal-overlay" id="addModal">
        <div class="modal-content">
            <span class="close-modal" id="closeAddModal">&times;</span>
            <h2 class="modal-title">👷 Tambah Petugas Baru</h2>
            <form method="POST" action="">
                <input type="hidden" name="action" value="add_staff">
                <div class="form-group">
                    <label for="addNama"><i class="fas fa-user" style="margin-right: 10px;"></i>Nama Lengkap</label>
                    <input type="text" id="addNama" name="nama" class="form-control" required minlength="2" placeholder="Contoh: Budi Santoso" autocomplete="off">
                </div>
                <div class="form-group">
                    <label for="addUsername"><i class="fas fa-user-tag" style="margin-right: 10px;"></i>Username</label>
                    <input type="text" id="addUsername" name="username" class="form-control" required minlength="4" placeholder="Contoh: budi_santoso" autocomplete="off">
                </div>
                <div class="form-group">
                    <label for="addPassword"><i class="fas fa-lock" style="margin-right: 10px;"></i>Password</label>
                    <input type="password" id="addPassword" name="password" class="form-control" required minlength="6" placeholder="Minimal 6 karakter" autocomplete="new-password">
                </div>
                <div class="form-group" style="background: linear-gradient(135deg, #e8f5e9 0%, #c8e6c9 100%); padding: 20px; border-radius: 15px; border: 2px solid #81c784; margin-top: 10px;">
                    <p style="font-family: 'Plus Jakarta Sans', sans-serif; color: #1b5e20; margin: 0; font-size: 17px; line-height: 1.5;">
                        <i class="fas fa-info-circle" style="margin-right: 10px; color: #1b5e20;"></i>
                        <strong>Catatan:</strong> Sistem akan otomatis membuat email dengan format: 
                        <span style="font-family: monospace; background: rgba(255,255,255,0.5); padding: 3px 6px; border-radius: 4px;">
                            username@petugas.ecommerce.com
                        </span>
                    </p>
                </div>
                <button type="submit" class="btn-submit">
                    <i class="fas fa-user-plus" style="margin-right: 10px;"></i> Tambah Petugas
                </button>
            </form>
        </div>
    </div>
    
    <!-- Edit Staff Modal -->
    <div class="modal-overlay" id="editModal">
        <div class="modal-content">
            <span class="close-modal" id="closeEditModal">&times;</span>
            <h2 class="modal-title">✏️ Edit Data Petugas</h2>
            <form method="POST" action="">
                <input type="hidden" name="action" value="edit_staff">
                <input type="hidden" id="editId" name="id">
                <div class="form-group">
                    <label for="editNama"><i class="fas fa-user" style="margin-right: 10px;"></i>Nama Lengkap</label>
                    <input type="text" id="editNama" name="nama" class="form-control" required minlength="2" placeholder="Contoh: Budi Santoso" autocomplete="off">
                </div>
                <div class="form-group">
                    <label for="editUsername"><i class="fas fa-user-tag" style="margin-right: 10px;"></i>Username</label>
                    <input type="text" id="editUsername" name="username" class="form-control" required minlength="4" placeholder="Contoh: budi_santoso" autocomplete="off">
                </div>
                <div class="form-group" style="background: linear-gradient(135deg, #fff3cd 0%, #ffeaa7 100%); padding: 20px; border-radius: 15px; border: 2px solid #ffc107; margin-top: 10px;">
                    <p style="font-family: 'Plus Jakarta Sans', sans-serif; color: #856404; margin: 0; font-size: 17px; line-height: 1.5;">
                        <i class="fas fa-lock" style="margin-right: 10px; color: #856404;"></i>
                        <strong>Password:</strong> Untuk keamanan, password tidak bisa diubah di halaman ini. 
                        Petugas harus mengganti password melalui halaman profil mereka sendiri.
                    </p>
                </div>
                <button type="submit" class="btn-submit" style="background: linear-gradient(135deg, #10db1e 0%, #0ea616 100%);">
                    <i class="fas fa-save" style="margin-right: 10px;"></i> Simpan Perubahan
                </button>
            </form>
        </div>
    </div>
    
    <!-- Delete Confirmation Modal -->
    <div class="modal-overlay" id="deleteModal">
        <div class="modal-content">
            <span class="close-modal" id="closeDeleteModal">&times;</span>
            <h2 class="modal-title" style="color: var(--danger-color);">🗑️ Konfirmasi Penghapusan Petugas</h2>
            <p style="font-family: 'Plus Jakarta Sans', sans-serif; font-size: 24px; text-align: center; margin: 30px 0; color: #333; line-height: 1.6;">
                Anda yakin ingin menghapus petugas berikut?<br>
                <strong id="deleteNama" style="color: var(--danger-color); font-size: 32px; display: block; margin-top: 10px;"></strong>
                <span style="display: block; color: #6c757d; font-size: 18px; margin-top: 15px; font-weight: 500;">
                    Tindakan ini tidak dapat dibatalkan!
                </span>
            </p>
            <form method="POST" action="">
                <input type="hidden" name="action" value="delete_staff">
                <input type="hidden" id="deleteId" name="id">
                <button type="submit" class="btn-submit" style="background: linear-gradient(135deg, var(--danger-color) 0%, #a50a0c 100%); box-shadow: 0 6px 15px rgba(252, 4, 4, 0.4);">
                    <i class="fas fa-trash-alt" style="margin-right: 10px;"></i> Ya, Hapus Petugas
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
                document.getElementById('addNama').focus();
                document.querySelector('#addModal form').reset();
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
                    const username = this.getAttribute('data-username');
                    const nama = this.getAttribute('data-nama');
                    
                    document.getElementById('editId').value = id;
                    document.getElementById('editUsername').value = username;
                    document.getElementById('editNama').value = nama;
                    
                    editModal.classList.add('active');
                    document.body.style.overflow = 'hidden';
                    document.getElementById('editNama').focus();
                });
            });
            
            // Delete button functionality
            document.querySelectorAll('.btn-delete').forEach(button => {
                button.addEventListener('click', function(e) {
                    e.preventDefault();
                    
                    const id = this.getAttribute('data-id');
                    const nama = this.getAttribute('data-nama');
                    
                    document.getElementById('deleteId').value = id;
                    document.getElementById('deleteNama').textContent = nama;
                    
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
    </script>
</body>
</html>