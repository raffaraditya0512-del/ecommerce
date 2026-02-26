<?php
session_start();
require_once '../config.php';

// Redirect ke login jika belum login atau bukan user
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'user') {
    header("Location: ../Auth/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$user = $conn->query("SELECT * FROM users WHERE id = $user_id")->fetch_assoc();

// Handle add to cart from product page (redirect dari detail_produk.php)
if (isset($_GET['add_product'])) {
    $product_id = intval($_GET['add_product']);
    $quantity = isset($_GET['quantity']) ? intval($_GET['quantity']) : 1;
    
    // Get product info
    $product = $conn->query("SELECT * FROM products WHERE id = $product_id AND stock > 0")->fetch_assoc();
    
    if ($product) {
        // Initialize cart if not exists
        if (!isset($_SESSION['cart'])) {
            $_SESSION['cart'] = [];
        }
        
        // Add or update cart
        if (isset($_SESSION['cart'][$product_id])) {
            $_SESSION['cart'][$product_id]['quantity'] += $quantity;
        } else {
            $_SESSION['cart'][$product_id] = [
                'id' => $product_id,
                'name' => $product['name'],
                'price' => $product['price'],
                'quantity' => $quantity,
                'image' => $product['image']
            ];
        }
        
        $_SESSION['success_msg'] = "✅ " . htmlspecialchars($product['name']) . " berhasil ditambahkan ke keranjang!";
        header("Location: keranjang.php");
        exit();
    }
}

// Handle update quantity
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_quantity'])) {
    $product_id = intval($_POST['product_id']);
    $action = $_POST['action'];
    
    if (isset($_SESSION['cart'][$product_id])) {
        if ($action === 'increase') {
            $_SESSION['cart'][$product_id]['quantity']++;
        } elseif ($action === 'decrease') {
            $_SESSION['cart'][$product_id]['quantity']--;
            if ($_SESSION['cart'][$product_id]['quantity'] <= 0) {
                unset($_SESSION['cart'][$product_id]);
                $_SESSION['success_msg'] = "Produk dihapus dari keranjang";
            }
        }
    }
    
    header("Location: keranjang.php");
    exit();
}

// Handle remove item
if (isset($_GET['remove'])) {
    $product_id = intval($_GET['remove']);
    if (isset($_SESSION['cart'][$product_id])) {
        $product_name = $_SESSION['cart'][$product_id]['name'];
        unset($_SESSION['cart'][$product_id]);
        $_SESSION['success_msg'] = "✅ $product_name berhasil dihapus dari keranjang";
    }
    header("Location: keranjang.php");
    exit();
}

// Handle checkout
if (isset($_POST['checkout'])) {
    if (!isset($_SESSION['cart']) || empty($_SESSION['cart'])) {
        $_SESSION['error_msg'] = "Keranjang belanja kosong!";
        header("Location: keranjang.php");
        exit();
    }
    
    // Redirect to checkout page
    header("Location: checkout.php");
    exit();
}

// Calculate cart totals
$cart_items = isset($_SESSION['cart']) ? $_SESSION['cart'] : [];
$subtotal = 0;
$total_items = 0;

foreach ($cart_items as $item) {
    $subtotal += $item['price'] * $item['quantity'];
    $total_items += $item['quantity'];
}

$shipping = 15000; // Fixed shipping cost
$total = $subtotal + $shipping;

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
    <title>Keranjang Belanja - E-Commerce Radit</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #042eff;
            --primary-dark: #0321cc;
            --secondary-color: #112c9b;
            --text-dark: #000000;
            --text-light: #ffffff;
            --gray-bg: #d9d9d9;
            --gray-light: #f5f5f5;
            --danger-color: #fc0404;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', sans-serif;
            background-color: #ffffff;
            color: var(--text-dark);
            min-height: 100vh;
            overflow-x: hidden;
        }
        
        .main-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 20px;
            position: relative;
        }
        
        /* Header Styles */
        .header {
            background-color: var(--primary-color);
            padding: 25px 60px;
            text-align: center;
            position: relative;
            z-index: 28;
            margin: -15px -20px 40px;
            border-radius: 0 0 20px 20px;
            box-shadow: 0 10px 30px rgba(4, 46, 255, 0.3);
        }
        
        .header-title {
            font-family: 'Inter', sans-serif;
            font-size: 48px;
            font-weight: 900;
            color: var(--text-light);
            margin-bottom: 15px;
        }
        
        .header-subtitle {
            font-family: 'Inter', sans-serif;
            font-size: 36px;
            font-weight: 800;
            color: var(--text-dark);
        }
        
        /* Cart Items */
        .cart-container {
            display: grid;
            grid-template-columns: 1fr 400px;
            gap: 40px;
            margin-bottom: 60px;
        }
        
        .cart-items {
            display: flex;
            flex-direction: column;
            gap: 30px;
        }
        
        .cart-item {
            background-color: rgba(255, 255, 255, 0.95);
            border: 5px solid var(--text-dark);
            border-radius: 10px;
            padding: 25px;
            display: grid;
            grid-template-columns: 320px 1fr 220px;
            align-items: center;
            position: relative;
            box-shadow: 0 4px 4px 0 rgba(0, 0, 0, 0.3);
        }
        
        .item-image {
            width: 280px;
            height: 240px;
            background: linear-gradient(135deg, #f0f4ff, #e6eeff);
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }
        
        .item-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .item-image i {
            font-size: 90px;
            color: var(--gray-bg);
        }
        
        .item-info {
            padding: 0 30px;
        }
        
        .item-name {
            font-family: 'Inter', sans-serif;
            font-size: 36px;
            font-weight: 800;
            color: var(--text-dark);
            margin-bottom: 20px;
        }
        
        .item-price {
            font-family: 'Inter', sans-serif;
            font-size: 28px;
            font-weight: 700;
            color: var(--primary-color);
        }
        
        .quantity-control {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-top: 25px;
        }
        
        .quantity-btn {
            width: 65px;
            height: 60px;
            background-color: #474141;
            border-radius: 5px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            box-shadow: 0 4px 4px 0 rgba(0, 0, 0, 0.3);
            transition: all 0.3s ease;
        }
        
        .quantity-btn:hover {
            background-color: #2c2828;
            transform: scale(1.1);
        }
        
        .quantity-btn span {
            font-family: 'Inter', sans-serif;
            font-size: 40px;
            font-weight: 800;
            color: var(--text-light);
            line-height: 1;
        }
        
        .quantity-display {
            width: 70px;
            height: 60px;
            background-color: rgba(255, 255, 255, 0.9);
            border: 5px solid var(--text-dark);
            border-radius: 5px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Inter', sans-serif;
            font-size: 36px;
            font-weight: 600;
            color: var(--text-dark);
        }
        
        .remove-btn {
            position: absolute;
            top: 15px;
            right: 15px;
            width: 50px;
            height: 50px;
            background-color: var(--danger-color);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s ease;
            z-index: 10;
        }
        
        .remove-btn:hover {
            transform: scale(1.2) rotate(90deg);
            box-shadow: 0 0 15px rgba(252, 4, 4, 0.5);
        }
        
        .remove-btn i {
            font-size: 28px;
            color: white;
        }
        
        /* Cart Summary */
        .cart-summary {
            background-color: rgba(255, 255, 255, 0.95);
            border: 5px solid var(--text-dark);
            border-radius: 10px;
            padding: 40px;
            position: sticky;
            top: 100px;
            height: fit-content;
            box-shadow: 0 4px 4px 0 rgba(0, 0, 0, 0.3);
        }
        
        .summary-title {
            font-family: 'Inter', sans-serif;
            font-size: 42px;
            font-weight: 900;
            color: var(--text-dark);
            margin-bottom: 30px;
            text-align: center;
            padding-bottom: 20px;
            border-bottom: 3px solid var(--primary-color);
        }
        
        .summary-row {
            display: flex;
            justify-content: space-between;
            padding: 15px 0;
            font-family: 'Inter', sans-serif;
            font-size: 32px;
            font-weight: 800;
            color: var(--text-dark);
            border-bottom: 2px dashed #ccc;
        }
        
        .summary-row:last-child {
            border-bottom: none;
            padding-top: 25px;
            margin-top: 25px;
            border-top: 3px solid var(--primary-color);
            font-size: 42px;
            color: var(--primary-color);
        }
        
        .checkout-btn {
            width: 100%;
            padding: 25px;
            background-color: var(--secondary-color);
            border-radius: 15px;
            border: none;
            font-family: 'Inter', sans-serif;
            font-size: 36px;
            font-weight: 800;
            color: var(--text-light);
            cursor: pointer;
            margin-top: 30px;
            box-shadow: 0 4px 4px 0 rgba(0, 0, 0, 0.3);
            transition: all 0.3s ease;
        }
        
        .checkout-btn:hover {
            background-color: #0d237a;
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(17, 44, 155, 0.4);
        }
        
        .checkout-btn:disabled {
            background-color: #999;
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }
        
        .checkout-btn i {
            margin-right: 15px;
        }
        
        /* Empty Cart */
        .empty-cart {
            text-align: center;
            padding: 100px 20px;
            background: linear-gradient(135deg, #f8f9ff, #eef2ff);
            border-radius: 25px;
            border: 5px dashed var(--primary-color);
            margin: 40px 0;
        }
        
        .empty-cart i {
            font-size: 120px;
            color: var(--primary-color);
            margin-bottom: 30px;
        }
        
        .empty-cart h2 {
            font-family: 'Inter', sans-serif;
            font-size: 48px;
            font-weight: 900;
            color: var(--primary-color);
            margin-bottom: 25px;
        }
        
        .empty-cart p {
            font-family: 'Inter', sans-serif;
            font-size: 28px;
            color: #666;
            max-width: 600px;
            margin: 0 auto 40px;
            line-height: 1.6;
        }
        
        .btn-continue {
            display: inline-block;
            padding: 20px 50px;
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            color: white;
            text-decoration: none;
            border-radius: 50px;
            font-family: 'Inter', sans-serif;
            font-size: 32px;
            font-weight: 700;
            transition: all 0.4s ease;
            box-shadow: 0 10px 30px rgba(4, 46, 255, 0.4);
        }
        
        .btn-continue:hover {
            transform: translateY(-8px) scale(1.05);
            box-shadow: 0 15px 40px rgba(4, 46, 255, 0.6);
        }
        
        .btn-continue i {
            margin-right: 15px;
        }
        
        /* Messages */
        .message {
            padding: 20px 30px;
            border-radius: 15px;
            margin-bottom: 30px;
            font-family: 'Inter', sans-serif;
            font-size: 24px;
            font-weight: 700;
            text-align: center;
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
        
        .message.success {
            background-color: #d4edda;
            color: #155724;
            border: 3px solid #c3e6cb;
            box-shadow: 0 5px 15px rgba(40, 167, 69, 0.3);
        }
        
        .message.error {
            background-color: #f8d7da;
            color: #721c24;
            border: 3px solid #f5c6cb;
            box-shadow: 0 5px 15px rgba(220, 53, 69, 0.3);
        }
        
        /* Footer */
        .footer {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 40px 0 30px;
            margin-top: 60px;
            text-align: center;
            border-radius: 20px 20px 0 0;
        }
        
        .footer p {
            font-family: 'Inter', sans-serif;
            font-size: 22px;
            margin-top: 15px;
            max-width: 700px;
            margin-left: auto;
            margin-right: auto;
            line-height: 1.6;
        }
        
        .copyright {
            font-family: 'Inter', sans-serif;
            font-size: 18px;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 2px solid rgba(255, 255, 255, 0.2);
            color: rgba(255, 255, 255, 0.8);
        }
        
        /* Responsive Design */
        @media (max-width: 1200px) {
            .cart-container {
                grid-template-columns: 1fr;
            }
            
            .cart-summary {
                position: static;
                width: 100%;
            }
            
            .cart-item {
                grid-template-columns: 280px 1fr 200px;
            }
            
            .item-name {
                font-size: 32px;
            }
            
            .item-price {
                font-size: 26px;
            }
            
            .quantity-btn span {
                font-size: 36px;
            }
            
            .quantity-display {
                font-size: 32px;
            }
            
            .summary-row {
                font-size: 28px;
            }
            
            .summary-row:last-child {
                font-size: 36px;
            }
            
            .checkout-btn {
                font-size: 32px;
            }
            
            .btn-continue {
                font-size: 28px;
                padding: 18px 45px;
            }
        }
        
        @media (max-width: 992px) {
            .header {
                padding: 20px 30px;
            }
            
            .header-title {
                font-size: 40px;
            }
            
            .header-subtitle {
                font-size: 30px;
            }
            
            .cart-item {
                grid-template-columns: 240px 1fr 180px;
            }
            
            .item-image {
                width: 240px;
                height: 200px;
            }
            
            .item-name {
                font-size: 28px;
                margin-bottom: 15px;
            }
            
            .item-price {
                font-size: 24px;
            }
            
            .quantity-control {
                gap: 10px;
            }
            
            .quantity-btn {
                width: 55px;
                height: 50px;
            }
            
            .quantity-btn span {
                font-size: 32px;
            }
            
            .quantity-display {
                width: 60px;
                height: 50px;
                font-size: 28px;
            }
            
            .remove-btn {
                width: 45px;
                height: 45px;
            }
            
            .remove-btn i {
                font-size: 24px;
            }
            
            .summary-title {
                font-size: 36px;
            }
            
            .summary-row {
                font-size: 26px;
            }
            
            .summary-row:last-child {
                font-size: 32px;
            }
            
            .checkout-btn {
                font-size: 28px;
                padding: 22px;
            }
            
            .empty-cart i {
                font-size: 100px;
            }
            
            .empty-cart h2 {
                font-size: 40px;
            }
            
            .empty-cart p {
                font-size: 24px;
            }
            
            .btn-continue {
                font-size: 26px;
                padding: 16px 40px;
            }
        }
        
        @media (max-width: 768px) {
            .header {
                padding: 15px 20px;
                margin: -15px -15px 30px;
                border-radius: 0 0 15px 15px;
            }
            
            .header-title {
                font-size: 36px;
            }
            
            .header-subtitle {
                font-size: 26px;
            }
            
            .cart-item {
                grid-template-columns: 1fr;
                text-align: center;
            }
            
            .item-image {
                width: 100%;
                height: 220px;
                margin-bottom: 20px;
            }
            
            .item-info {
                padding: 0;
                margin-bottom: 25px;
            }
            
            .item-name {
                font-size: 32px;
                margin-bottom: 10px;
            }
            
            .item-price {
                font-size: 28px;
                margin-bottom: 15px;
            }
            
            .quantity-control {
                justify-content: center;
                margin-top: 20px;
            }
            
            .quantity-btn {
                width: 60px;
                height: 55px;
            }
            
            .quantity-btn span {
                font-size: 36px;
            }
            
            .quantity-display {
                width: 65px;
                height: 55px;
                font-size: 32px;
            }
            
            .remove-btn {
                position: absolute;
                top: 15px;
                right: 15px;
                width: 50px;
                height: 50px;
            }
            
            .summary-title {
                font-size: 32px;
            }
            
            .summary-row {
                font-size: 24px;
                flex-direction: column;
                gap: 10px;
            }
            
            .summary-row div:first-child {
                font-weight: 600;
                color: #666;
            }
            
            .summary-row:last-child {
                font-size: 36px;
                flex-direction: row;
                justify-content: space-between;
            }
            
            .checkout-btn {
                font-size: 32px;
                padding: 25px;
            }
            
            .empty-cart {
                padding: 80px 15px;
            }
            
            .empty-cart i {
                font-size: 80px;
            }
            
            .empty-cart h2 {
                font-size: 36px;
            }
            
            .empty-cart p {
                font-size: 22px;
            }
            
            .btn-continue {
                font-size: 28px;
                padding: 20px 45px;
            }
            
            .footer p {
                font-size: 20px;
            }
            
            .copyright {
                font-size: 16px;
            }
        }
        
        @media (max-width: 480px) {
            .header {
                padding: 12px 15px;
                margin: -15px -10px 25px;
            }
            
            .header-title {
                font-size: 32px;
            }
            
            .header-subtitle {
                font-size: 24px;
            }
            
            .item-image {
                height: 200px;
            }
            
            .item-name {
                font-size: 28px;
            }
            
            .item-price {
                font-size: 26px;
            }
            
            .quantity-btn {
                width: 55px;
                height: 50px;
            }
            
            .quantity-btn span {
                font-size: 32px;
            }
            
            .quantity-display {
                width: 60px;
                height: 50px;
                font-size: 28px;
            }
            
            .remove-btn {
                width: 45px;
                height: 45px;
            }
            
            .remove-btn i {
                font-size: 22px;
            }
            
            .summary-title {
                font-size: 28px;
                padding-bottom: 15px;
            }
            
            .summary-row {
                font-size: 22px;
            }
            
            .summary-row:last-child {
                font-size: 32px;
                padding-top: 20px;
                margin-top: 20px;
            }
            
            .checkout-btn {
                font-size: 28px;
                padding: 22px;
            }
            
            .empty-cart i {
                font-size: 70px;
            }
            
            .empty-cart h2 {
                font-size: 32px;
            }
            
            .empty-cart p {
                font-size: 20px;
            }
            
            .btn-continue {
                font-size: 24px;
                padding: 18px 40px;
            }
            
            .footer p {
                font-size: 18px;
                padding: 0 15px;
            }
            
            .copyright {
                font-size: 14px;
                padding: 15px;
            }
        }
    </style>
</head>
<body>
    <div class="main-container">
        <!-- Header -->
        <header class="header">
            <h1 class="header-title">Keranjang Belanja</h1>
            <div class="header-subtitle"></div>
        </header>
        
        <!-- Messages -->
        <?php if ($success_msg): ?>
            <div class="message success"><?php echo $success_msg; ?></div>
        <?php endif; ?>
        
        <?php if ($error_msg): ?>
            <div class="message error"><?php echo $error_msg; ?></div>
        <?php endif; ?>
        
        <!-- Cart Content -->
        <?php if (empty($cart_items)): ?>
            <div class="empty-cart">
                <i class="fas fa-shopping-cart"></i>
                <h2>Keranjang Belanja Kosong</h2>
                <p>Tambahkan produk favorit Anda ke keranjang untuk memulai belanja!</p>
                <a href="produk.php" class="btn-continue">
                    <i class="fas fa-shopping-bag"></i> Lihat Produk
                </a>
            </div>
        <?php else: ?>
            <div class="cart-container">
                <div class="cart-items">
                    <?php foreach ($cart_items as $item): ?>
                        <div class="cart-item">
                            <div class="item-image">
                                <i class="fas fa-tshirt"></i>
                            </div>
                            <div class="item-info">
                                <h3 class="item-name"><?php echo htmlspecialchars($item['name']); ?></h3>
                                <div class="item-price">Rp <?php echo number_format($item['price'], 0, ',', '.'); ?></div>
                            </div>
                            <div class="quantity-control">
                                <form method="POST" action="keranjang.php" style="display: inline;">
                                    <input type="hidden" name="product_id" value="<?php echo $item['id']; ?>">
                                    <input type="hidden" name="action" value="decrease">
                                    <button type="submit" class="quantity-btn" <?php echo $item['quantity'] <= 1 ? 'disabled' : ''; ?>>
                                        <span>-</span>
                                    </button>
                                </form>
                                
                                <div class="quantity-display"><?php echo $item['quantity']; ?></div>
                                
                                <form method="POST" action="keranjang.php" style="display: inline;">
                                    <input type="hidden" name="product_id" value="<?php echo $item['id']; ?>">
                                    <input type="hidden" name="action" value="increase">
                                    <button type="submit" class="quantity-btn">
                                        <span>+</span>
                                    </button>
                                </form>
                            </div>
                            <a href="keranjang.php?remove=<?php echo $item['id']; ?>" class="remove-btn" title="Hapus produk">
                                <i class="fas fa-trash-alt"></i>
                            </a>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <div class="cart-summary">
                    <h2 class="summary-title">Ringkasan Belanja</h2>
                    <div class="summary-row">
                        <div>Subtotal (<?php echo $total_items; ?> item)</div>
                        <div>Rp <?php echo number_format($subtotal, 0, ',', '.'); ?></div>
                    </div>
                    <div class="summary-row">
                        <div>Ongkos Kirim</div>
                        <div>Rp <?php echo number_format($shipping, 0, ',', '.'); ?></div>
                    </div>
                    <div class="summary-row">
                        <div>Total</div>
                        <div>Rp <?php echo number_format($total, 0, ',', '.'); ?></div>
                    </div>
                    
                    <form method="POST" action="keranjang.php">
                        <button type="submit" name="checkout" class="checkout-btn" <?php echo empty($cart_items) ? 'disabled' : ''; ?>>
                            <i class="fas fa-credit-card"></i> Checkout
                        </button>
                    </form>
                </div>
            </div>
        <?php endif; ?>
        
        <!-- Footer -->
        <footer class="footer">
            <p>
                <i class="fas fa-shopping-bag"></i> Lexz Store
            </p>
            <p>
                Platform e-commerce terpercaya untuk kebutuhan fashion dan lifestyle Anda
            </p>
            <p class="copyright">
                &copy; 2026 Lexz Store. All Rights Reserved.
            </p>
        </footer>
    </div>
    
    <script>
        // Smooth scroll untuk anchor links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function(e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    window.scrollTo({
                        top: target.offsetTop - 100,
                        behavior: 'smooth'
                    });
                }
            });
        });
        
        // Konfirmasi hapus produk
        document.querySelectorAll('.remove-btn').forEach(btn => {
            btn.addEventListener('click', function(e) {
                const productName = this.closest('.cart-item').querySelector('.item-name').textContent;
                if (!confirm(`Hapus "${productName}" dari keranjang?`)) {
                    e.preventDefault();
                }
            });
        });
    </script>
</body>
</html>