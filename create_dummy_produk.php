<?php
require_once '../config.php';

// Daftar data dummy produk
$dummy_produk = [
    ['name' => 'Work Jacket', 'price' => 200000, 'stock' => 15, 'description' => 'Jaket kerja berkualitas premium'],
    ['name' => 'Jaket Kulit', 'price' => 150000, 'stock' => 8, 'description' => 'Jaket kulit asli dengan desain modern'],
    ['name' => 'T-Shirt Premium', 'price' => 100000, 'stock' => 25, 'description' => 'Kaos premium dengan bahan katun terbaik'],
    ['name' => 'Hoodie Hitam', 'price' => 180000, 'stock' => 12, 'description' => 'Hoodie hangat dengan desain minimalis'],
    ['name' => 'Celana Jeans', 'price' => 250000, 'stock' => 6, 'description' => 'Celana jeans stretch dengan kualitas terbaik'],
    ['name' => 'Kemeja Formal', 'price' => 120000, 'stock' => 20, 'description' => 'Kemeja formal untuk acara resmi'],
    ['name' => 'Sweater Rajut', 'price' => 160000, 'stock' => 10, 'description' => 'Sweater rajut hangat untuk musim dingin'],
    ['name' => 'Topi Baseball', 'price' => 50000, 'stock' => 30, 'description' => 'Topi baseball dengan logo eksklusif'],
    ['name' => 'Tas Ransel', 'price' => 300000, 'stock' => 5, 'description' => 'Tas ransel multifungsi dengan banyak kantong'],
    ['name' => 'Sepatu Sneakers', 'price' => 450000, 'stock' => 3, 'description' => 'Sepatu sneakers trendi dengan bahan berkualitas']
];

$success_count = 0;
$error_messages = [];

// Create products table if not exists
$create_table = "CREATE TABLE IF NOT EXISTS products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    price DECIMAL(10, 2) NOT NULL,
    description TEXT,
    image VARCHAR(255) DEFAULT 'default-product.jpg',
    stock INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)";
$conn->query($create_table);

foreach ($dummy_produk as $produk) {
    // Cek apakah produk sudah ada
    $stmt = $conn->prepare("SELECT id FROM products WHERE name = ?");
    $stmt->bind_param("s", $produk['name']);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $error_messages[] = "Produk '{$produk['name']}' sudah ada, dilewati.";
        $stmt->close();
        continue;
    }
    $stmt->close();
    
    // Insert data produk
    $stmt = $conn->prepare("INSERT INTO products (name, price, description, stock) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("sdss", $produk['name'], $produk['price'], $produk['description'], $produk['stock']);
    
    if ($stmt->execute()) {
        $success_count++;
    } else {
        $error_messages[] = "Gagal menambahkan {$produk['name']}: " . $stmt->error;
    }
    $stmt->close();
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>✅ Data Dummy Produk Dibuat</title>
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
            max-width: 700px;
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
            background: radial-gradient(circle, rgba(149, 10, 152, 0.15) 0%, transparent 70%);
            z-index: 0;
        }
        .header {
            margin-bottom: 30px;
            position: relative;
            z-index: 1;
        }
        .header i {
            font-size: 60px;
            color: #950a98;
            margin-bottom: 15px;
            display: block;
        }
        h1 {
            font-size: 36px;
            font-weight: 800;
            margin-bottom: 10px;
            background: linear-gradient(45deg, #950a98, #7a087d);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            text-shadow: 0 2px 10px rgba(149, 10, 152, 0.3);
        }
        .success-count {
            font-size: 72px;
            font-weight: 800;
            color: #950a98;
            margin: 20px 0;
            text-shadow: 0 0 20px rgba(149, 10, 152, 0.5);
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
            background: linear-gradient(135deg, #950a98 0%, #7a087d 100%);
            color: white;
            box-shadow: 0 6px 15px rgba(149, 10, 152, 0.4);
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
        .products-list {
            background: #f8e9ff;
            border: 2px dashed #ce93d8;
            border-radius: 15px;
            padding: 25px;
            margin-top: 25px;
            text-align: left;
            max-height: 300px;
            overflow-y: auto;
        }
        .products-list h3 {
            color: #4a148c;
            margin-bottom: 15px;
            font-size: 22px;
            text-align: center;
        }
        .products-list table {
            width: 100%;
            border-collapse: collapse;
            font-size: 16px;
        }
        .products-list th {
            background: #950a98;
            color: white;
            padding: 10px;
            text-align: left;
        }
        .products-list td {
            padding: 8px;
            border-bottom: 1px solid #e0e0e0;
        }
        .products-list tr:hover {
            background: rgba(149, 10, 152, 0.1);
        }
        .price {
            color: #c62828;
            font-weight: 700;
        }
        .stock {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 15px;
            font-size: 14px;
            font-weight: 700;
        }
        .stock-high {
            background-color: #4caf50;
            color: white;
        }
        .stock-medium {
            background-color: #ff9800;
            color: white;
        }
        .stock-low {
            background-color: #f44336;
            color: white;
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
            <i class="fas fa-box-open"></i>
            <h1>Data Dummy Produk</h1>
        </div>
        
        <div class="success-count">
            <?php echo $success_count; ?>
        </div>
        
        <div class="message">
            <?php if ($success_count > 0): ?>
                ✅ Berhasil membuat <strong><?php echo $success_count; ?></strong> produk dummy!
            <?php else: ?>
                ⚠️ Tidak ada data baru yang dibuat (semua produk sudah ada)
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
        
        <div class="products-list">
            <h3><i class="fas fa-list"></i> Daftar Produk yang Dibuat</h3>
            <table>
                <thead>
                    <tr>
                        <th>Nama Produk</th>
                        <th>Harga</th>
                        <th>Stok</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($dummy_produk as $index => $produk): ?>
                        <?php 
                            $stock_class = 'stock-low';
                            if ($produk['stock'] > 20) $stock_class = 'stock-high';
                            elseif ($produk['stock'] > 5) $stock_class = 'stock-medium';
                        ?>
                        <tr>
                            <td><?php echo htmlspecialchars($produk['name']); ?></td>
                            <td class="price">Rp <?php echo number_format($produk['price'], 0, ',', '.'); ?></td>
                            <td><span class="stock <?php echo $stock_class; ?>"><?php echo $produk['stock']; ?></span></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <div class="btn-container">
            <a href="kelola_data_produk.php" class="btn btn-primary">
                <i class="fas fa-box"></i> Lihat Data Produk
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
        // Auto-redirect ke halaman produk setelah 10 detik
        setTimeout(function() {
            window.location.href = 'kelola_data_produk.php';
        }, 10000);
    </script>
</body>
</html>