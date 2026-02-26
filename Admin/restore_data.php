<?php
session_start();
require_once '../config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../Auth/login.php");
    exit();
}

$success_msg = '';
$error_msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['sql_file'])) {
    $file = $_FILES['sql_file'];
    
    // Validate file
    $allowed_ext = ['sql'];
    $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $error_msg = "❌ Error saat upload file!";
    } elseif (!in_array($file_ext, $allowed_ext)) {
        $error_msg = "❌ Hanya file .sql yang diperbolehkan!";
    } elseif ($file['size'] > 10 * 1024 * 1024) { // 10MB max
        $error_msg = "❌ File terlalu besar! Maksimal 10MB";
    } else {
        // Read SQL file
        $sql_content = file_get_contents($file['tmp_name']);
        
        // Split into individual queries
        $queries = array_filter(array_map('trim', explode(';', $sql_content)));
        
        $error_count = 0;
        $success_count = 0;
        
        foreach ($queries as $query) {
            if (empty($query) || strpos($query, '--') === 0) continue;
            
            if (!$conn->query($query)) {
                $error_count++;
                if ($error_count === 1) {
                    $error_msg = "❌ Error pada query: " . $conn->error;
                }
            } else {
                $success_count++;
            }
        }
        
        if ($error_count === 0) {
            $success_msg = "✅ Restore berhasil! $success_count query berhasil dijalankan.";
        } else {
            $error_msg .= " ($error_count query gagal, $success_count berhasil)";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Restore Data - Admin Panel</title>
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
            --restore-color: #ff5722;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background: linear-gradient(135deg, #4a148c, #283593);
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
            border-bottom: 3px solid var(--restore-color);
        }
        .header i { font-size: 60px; color: var(--restore-color); margin-bottom: 15px; }
        .header h1 { font-size: 42px; font-weight: 800; color: var(--restore-color); }
        .warning-box {
            background: linear-gradient(135deg, #ffebee, #ffcdd2);
            border: 3px solid var(--danger-color);
            border-radius: 20px;
            padding: 30px;
            margin-bottom: 30px;
            text-align: center;
        }
        .warning-title { 
            font-size: 28px; 
            font-weight: 800; 
            color: var(--danger-color); 
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }
        .warning-title i { font-size: 32px; }
        .warning-text { 
            font-size: 18px; 
            color: #c62828; 
            line-height: 1.6;
            margin: 10px 0;
        }
        .upload-area {
            border: 3px dashed var(--restore-color);
            border-radius: 20px;
            padding: 40px;
            text-align: center;
            background: linear-gradient(135deg, #fff3e0, #ffe0b2);
            margin-bottom: 30px;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .upload-area:hover {
            background: linear-gradient(135deg, #ffe0b2, #ffcc80);
            transform: scale(1.02);
        }
        .upload-area i { font-size: 80px; color: var(--restore-color); margin-bottom: 20px; }
        .upload-area h3 { font-size: 28px; font-weight: 700; color: #e65100; margin-bottom: 15px; }
        .upload-area p { font-size: 18px; color: #5d4037; }
        .file-input {
            display: none;
        }
        .restore-btn {
            display: block;
            width: 100%;
            padding: 25px;
            background: linear-gradient(135deg, var(--restore-color), #e64a19);
            color: white;
            border: none;
            border-radius: 20px;
            font-size: 28px;
            font-weight: 800;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 10px 25px rgba(255, 87, 34, 0.4);
            margin: 20px 0;
        }
        .restore-btn:hover {
            transform: translateY(-5px) scale(1.02);
            box-shadow: 0 15px 35px rgba(255, 87, 34, 0.6);
            background: linear-gradient(135deg, #e64a19, #d84315);
        }
        .restore-btn:disabled {
            background: #bdbdbd;
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }
        .restore-btn i { margin-right: 15px; }
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
            <i class="fas fa-undo"></i>
            <h1>Restore Database</h1>
        </div>
        
        <div class="warning-box">
            <div class="warning-title">
                <i class="fas fa-exclamation-triangle"></i>
                PERINGATAN PENTING!
            </div>
            <div class="warning-text">
                ⚠️ Restore database akan <strong>MENIMPA</strong> semua data yang ada saat ini!
            </div>
            <div class="warning-text">
                ⚠️ Pastikan Anda sudah melakukan <strong>BACKUP</strong> sebelum restore!
            </div>
            <div class="warning-text">
                ⚠️ Hanya gunakan file backup yang <strong>TERPERCAYA</strong>!
            </div>
        </div>
        
        <?php if ($success_msg): ?>
            <div class="message success">
                <i class="fas fa-check-circle"></i> <?php echo $success_msg; ?>
            </div>
        <?php endif; ?>
        
        <?php if ($error_msg): ?>
            <div class="message error">
                <i class="fas fa-exclamation-triangle"></i> <?php echo $error_msg; ?>
            </div>
        <?php endif; ?>
        
        <div class="upload-area" id="dropZone">
            <i class="fas fa-file-sql"></i>
            <h3>Drag & Drop File SQL</h3>
            <p>atau klik untuk memilih file backup (.sql)</p>
            <input type="file" id="fileInput" class="file-input" name="sql_file" accept=".sql" required>
        </div>
        
        <form method="POST" action="" enctype="multipart/form-data" id="restoreForm">
            <input type="file" id="hiddenFileInput" name="sql_file" accept=".sql" style="display:none;" required>
            <button type="submit" id="restoreBtn" class="restore-btn" disabled>
                <i class="fas fa-upload"></i> Restore Database
            </button>
        </form>
        
        <a href="admin_dashboard.php" class="back-btn">
            <i class="fas fa-arrow-left"></i> Kembali ke Dashboard
        </a>
    </div>
    
    <script>
        const dropZone = document.getElementById('dropZone');
        const fileInput = document.getElementById('fileInput');
        const hiddenFileInput = document.getElementById('hiddenFileInput');
        const restoreBtn = document.getElementById('restoreBtn');
        
        // Click to select file
        dropZone.addEventListener('click', () => fileInput.click());
        
        // Handle file selection
        fileInput.addEventListener('change', (e) => {
            if (e.target.files.length > 0) {
                const file = e.target.files[0];
                hiddenFileInput.files = e.target.files;
                
                // Update UI
                dropZone.innerHTML = `
                    <i class="fas fa-check-circle" style="color: #4caf50; font-size: 80px;"></i>
                    <h3 style="color: #2e7d32;">File Terpilih:</h3>
                    <p style="font-size: 20px; color: #1b5e20; font-weight: 700;">${file.name}</p>
                    <p style="font-size: 16px; color: #555;">Ukuran: ${(file.size / 1024).toFixed(2)} KB</p>
                `;
                
                restoreBtn.disabled = false;
            }
        });
        
        // Drag and drop
        dropZone.addEventListener('dragover', (e) => {
            e.preventDefault();
            dropZone.style.borderColor = '#4caf50';
            dropZone.style.backgroundColor = '#e8f5e9';
        });
        
        dropZone.addEventListener('dragleave', () => {
            dropZone.style.borderColor = '#ff5722';
            dropZone.style.backgroundColor = '';
        });
        
        dropZone.addEventListener('drop', (e) => {
            e.preventDefault();
            dropZone.style.borderColor = '#ff5722';
            dropZone.style.backgroundColor = '';
            
            if (e.dataTransfer.files.length > 0) {
                fileInput.files = e.dataTransfer.files;
                const event = new Event('change');
                fileInput.dispatchEvent(event);
            }
        });
        
        // Form submission
        document.getElementById('restoreForm').addEventListener('submit', function(e) {
            if (!hiddenFileInput.files.length) {
                e.preventDefault();
                alert('Pilih file SQL terlebih dahulu!');
                return;
            }
            
            if (!confirm('⚠️ PERINGATAN: Restore akan menimpa semua data yang ada!\n\nApakah Anda yakin ingin melanjutkan?')) {
                e.preventDefault();
            }
        });
    </script>
</body>
</html>