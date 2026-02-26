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

// Redirect jika keranjang kosong
if (!isset($_SESSION['cart']) || empty($_SESSION['cart'])) {
    $_SESSION['error_msg'] = "Keranjang belanja kosong! Silakan tambahkan produk terlebih dahulu.";
    header("Location: keranjang.php");
    exit();
}

// Calculate cart totals
$cart_items = $_SESSION['cart'];
$subtotal = 0;
$total_items = 0;

foreach ($cart_items as $item) {
    $subtotal += $item['price'] * $item['quantity'];
    $total_items += $item['quantity'];
}

$shipping = 15000; // Fixed shipping cost
$total = $subtotal + $shipping;

// Handle payment processing
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['payment_method'])) {
    $payment_method = $_POST['payment_method'];
    
    // Validate payment method
    $valid_methods = ['cod', 'ewallet', 'bank_transfer'];
    if (!in_array($payment_method, $valid_methods)) {
        $_SESSION['error_msg'] = "Metode pembayaran tidak valid!";
        header("Location: checkout.php");
        exit();
    }
    
    // Insert transactions for each item in cart
    $error = false;
    $transaction_ids = [];
    
    foreach ($cart_items as $item) {
        $product_id = $item['id'];
        $quantity = $item['quantity'];
        $total_price = $item['price'] * $quantity;
        
        // Check stock availability
        $product = $conn->query("SELECT stock FROM products WHERE id = $product_id")->fetch_assoc();
        if (!$product || $product['stock'] < $quantity) {
            $_SESSION['error_msg'] = "Stok tidak mencukupi untuk produk: " . htmlspecialchars($item['name']);
            $error = true;
            break;
        }
        
        // Insert transaction
        $stmt = $conn->prepare("INSERT INTO transactions (user_id, product_id, quantity, total_price, status, payment_method) VALUES (?, ?, ?, ?, 'pending', ?)");
        $stmt->bind_param("iiids", $user_id, $product_id, $quantity, $total_price, $payment_method);
        
        if (!$stmt->execute()) {
            $_SESSION['error_msg'] = "Gagal memproses pembayaran: " . $stmt->error;
            $error = true;
            $stmt->close();
            break;
        }
        
        $transaction_ids[] = $stmt->insert_id;
        $stmt->close();
        
        // Update product stock
        $new_stock = $product['stock'] - $quantity;
        $conn->query("UPDATE products SET stock = $new_stock WHERE id = $product_id");
    }
    
    if (!$error) {
        // Clear cart after successful payment
        unset($_SESSION['cart']);
        
        // Set success message with transaction details
        $_SESSION['checkout_success'] = true;
        $_SESSION['payment_method'] = $payment_method;
        $_SESSION['total_amount'] = $total;
        
        // Redirect to riwayat with transaction details
        header("Location: riwayat.php");
        exit();
    }
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
    <title>Pembayaran -Lexz Store</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #0828c5;
            --primary-dark: #061e9a;
            --secondary-color: #112c9b;
            --text-dark: #000000;
            --text-light: #ffffff;
            --gray-bg: #d9d9d9;
            --gray-light: #f5f5f5;
            --danger-color: #fc0404;
            --success-color: #28a745;
            --cod-color: #4caf50;
            --ewallet-color: #ff9800;
            --bank-color: #2196f3;
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
            max-width: 1600px;
            margin: 0 auto;
            padding: 0 20px;
            position: relative;
        }
        
        /* Header Styles */
        .header {
            background-color: var(--primary-color);
            padding: 25px 0;
            text-align: center;
            position: relative;
            z-index: 100;
            margin: -15px -20px 50px;
            box-shadow: 0 10px 30px rgba(8, 40, 197, 0.4);
        }
        
        .header-title {
            font-family: 'Inter', sans-serif;
            font-size: 52px;
            font-weight: 900;
            color: var(--text-light);
            text-shadow: 0 4px 10px rgba(0, 0, 0, 0.3);
        }
        
        /* Order Summary */
        .order-summary {
            background-color: rgba(255, 255, 255, 0.98);
            border: 3px solid var(--text-dark);
            border-radius: 15px;
            padding: 40px;
            margin-bottom: 50px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
        }
        
        .summary-title {
            font-family: 'Inter', sans-serif;
            font-size: 42px;
            font-weight: 800;
            color: var(--text-dark);
            margin-bottom: 35px;
            padding-bottom: 20px;
            border-bottom: 3px solid var(--primary-color);
            text-align: center;
        }
        
        .order-items {
            display: flex;
            flex-direction: column;
            gap: 35px;
            margin-bottom: 40px;
        }
        
        .order-item {
            display: flex;
            gap: 40px;
            padding: 25px;
            background: linear-gradient(135deg, #f8f9ff, #eef2ff);
            border-radius: 20px;
            border: 2px solid #ddd;
            transition: all 0.3s ease;
        }
        
        .order-item:hover {
            transform: translateX(10px);
            border-color: var(--primary-color);
            box-shadow: 0 8px 20px rgba(8, 40, 197, 0.2);
        }
        
        .item-image {
            width: 220px;
            height: 220px;
            background: linear-gradient(135deg, #e3f2fd, #bbdefb);
            border-radius: 18px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }
        
        .item-image i {
            font-size: 90px;
            color: var(--primary-color);
        }
        
        .item-details {
            flex: 1;
        }
        
        .item-name {
            font-family: 'Inter', sans-serif;
            font-size: 38px;
            font-weight: 800;
            color: var(--text-dark);
            margin-bottom: 15px;
        }
        
        .item-quantity {
            font-family: 'Inter', sans-serif;
            font-size: 28px;
            color: #555;
            margin: 10px 0;
        }
        
        .item-price {
            font-family: 'Inter', sans-serif;
            font-size: 36px;
            font-weight: 700;
            color: var(--primary-color);
            margin-top: 15px;
        }
        
        .summary-totals {
            background: linear-gradient(135deg, #e8f4ff, #d0e3ff);
            border-radius: 20px;
            padding: 30px;
            margin-top: 30px;
            border: 3px solid var(--primary-color);
        }
        
        .total-row {
            display: flex;
            justify-content: space-between;
            padding: 15px 0;
            font-family: 'Inter', sans-serif;
            font-size: 32px;
            font-weight: 700;
            border-bottom: 2px dashed #aaa;
        }
        
        .total-row:last-child {
            border-bottom: none;
            padding-top: 25px;
            margin-top: 25px;
            border-top: 4px solid var(--primary-color);
            font-size: 48px;
            color: var(--primary-color);
            font-weight: 900;
        }
        
        /* Payment Methods */
        .payment-section {
            background-color: rgba(255, 255, 255, 0.98);
            border: 3px solid var(--text-dark);
            border-radius: 15px;
            padding: 40px;
            margin-bottom: 50px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
        }
        
        .payment-title {
            font-family: 'Inter', sans-serif;
            font-size: 42px;
            font-weight: 800;
            color: var(--text-dark);
            margin-bottom: 40px;
            padding-bottom: 20px;
            border-bottom: 3px solid var(--primary-color);
            text-align: center;
        }
        
        .payment-methods {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 30px;
            margin-bottom: 40px;
        }
        
        .payment-option {
            background: linear-gradient(135deg, #f8f9ff, #eef2ff);
            border: 3px solid #ccc;
            border-radius: 25px;
            padding: 30px 25px;
            text-align: center;
            cursor: pointer;
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            position: relative;
            overflow: hidden;
        }
        
        .payment-option:hover {
            transform: translateY(-10px) scale(1.03);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.25);
        }
        
        .payment-option.active {
            border-color: var(--primary-color);
            background: linear-gradient(135deg, #e3f2fd, #bbdefb);
            transform: translateY(-5px);
            box-shadow: 0 12px 30px rgba(8, 40, 197, 0.35);
        }
        
        .payment-option::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(8, 40, 197, 0.1) 0%, transparent 70%);
            opacity: 0;
            transition: opacity 0.5s;
            z-index: 0;
        }
        
        .payment-option.active::before {
            opacity: 1;
        }
        
        .payment-icon {
            width: 100px;
            height: 100px;
            margin: 0 auto 25px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 60px;
            border-radius: 25px;
            background: white;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.2);
        }
        
        .payment-option:nth-child(1) .payment-icon { background: linear-gradient(135deg, #4caf50, #2e7d32); color: white; }
        .payment-option:nth-child(2) .payment-icon { background: linear-gradient(135deg, #ff9800, #ef6c00); color: white; }
        .payment-option:nth-child(3) .payment-icon { background: linear-gradient(135deg, #2196f3, #1565c0); color: white; }
        
        .payment-label {
            font-family: 'Inter', sans-serif;
            font-size: 34px;
            font-weight: 800;
            margin-bottom: 15px;
        }
        
        .payment-option:nth-child(1) .payment-label { color: var(--cod-color); }
        .payment-option:nth-child(2) .payment-label { color: var(--ewallet-color); }
        .payment-option:nth-child(3) .payment-label { color: var(--bank-color); }
        
        .payment-desc {
            font-family: 'Inter', sans-serif;
            font-size: 22px;
            color: #666;
            line-height: 1.5;
        }
        
        .payment-instruction {
            background: linear-gradient(135deg, #fff8e1, #ffecb3);
            border: 3px solid #ffc107;
            border-radius: 25px;
            padding: 35px;
            margin-top: 20px;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .payment-instruction:hover {
            transform: scale(1.02);
            box-shadow: 0 10px 25px rgba(255, 193, 7, 0.4);
        }
        
        .instruction-header {
            display: flex;
            align-items: center;
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .instruction-toggle {
            width: 70px;
            height: 70px;
            background: var(--primary-color);
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 40px;
            color: white;
            flex-shrink: 0;
            box-shadow: 0 6px 15px rgba(8, 40, 197, 0.4);
        }
        
        .instruction-title {
            font-family: 'Inter', sans-serif;
            font-size: 36px;
            font-weight: 800;
            color: var(--primary-color);
        }
        
        .instruction-content {
            font-family: 'Inter', sans-serif;
            font-size: 26px;
            line-height: 1.6;
            color: #555;
            padding: 20px;
            background: rgba(255, 255, 255, 0.7);
            border-radius: 15px;
            display: none;
        }
        
        .instruction-content.show {
            display: block;
            animation: fadeIn 0.5s ease;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        /* Payment Button */
        .payment-button {
            background: linear-gradient(135deg, var(--secondary-color), var(--primary-dark));
            color: white;
            border: none;
            border-radius: 30px;
            padding: 28px 80px;
            font-family: 'Inter', sans-serif;
            font-size: 42px;
            font-weight: 900;
            cursor: pointer;
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            box-shadow: 0 15px 40px rgba(17, 44, 155, 0.5);
            display: block;
            margin: 0 auto;
            position: relative;
            overflow: hidden;
        }
        
        .payment-button:hover {
            transform: translateY(-10px) scale(1.05);
            box-shadow: 0 25px 55px rgba(17, 44, 155, 0.7);
        }
        
        .payment-button:active {
            transform: translateY(5px);
        }
        
        .payment-button::after {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.3) 0%, transparent 70%);
            opacity: 0;
            transition: opacity 0.5s;
        }
        
        .payment-button:hover::after {
            opacity: 1;
        }
        
        .payment-button i {
            margin-right: 20px;
            font-size: 48px;
        }
        
        /* Messages */
        .message {
            padding: 25px 40px;
            border-radius: 20px;
            margin-bottom: 35px;
            font-family: 'Inter', sans-serif;
            font-size: 28px;
            font-weight: 700;
            text-align: center;
            animation: slideDown 0.6s ease;
        }
        
        @keyframes slideDown {
            from {
                transform: translateY(-40px);
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
            border: 4px solid #c3e6cb;
            box-shadow: 0 8px 25px rgba(40, 167, 69, 0.35);
        }
        
        .message.error {
            background-color: #f8d7da;
            color: #721c24;
            border: 4px solid #f5c6cb;
            box-shadow: 0 8px 25px rgba(220, 53, 69, 0.35);
        }
        
        /* Footer */
        .footer {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 60px 0 40px;
            margin-top: 80px;
            text-align: center;
            border-radius: 35px 35px 0 0;
            position: relative;
            overflow: hidden;
        }
        
        .footer::before {
            content: '';
            position: absolute;
            top: -120px;
            left: -120px;
            width: 350px;
            height: 350px;
            background: rgba(255, 255, 255, 0.12);
            border-radius: 50%;
        }
        
        .footer-logo {
            font-family: 'Inter', sans-serif;
            font-size: 52px;
            font-weight: 900;
            margin-bottom: 25px;
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 25px;
            text-shadow: 0 5px 15px rgba(0, 0, 0, 0.35);
        }
        
        .footer-logo i {
            font-size: 65px;
        }
        
        .footer-text {
            font-family: 'Inter', sans-serif;
            font-size: 26px;
            max-width: 800px;
            margin: 0 auto 25px;
            line-height: 1.65;
            color: rgba(255, 255, 255, 0.95);
            text-shadow: 0 2px 5px rgba(0, 0, 0, 0.25);
        }
        
        .copyright {
            font-family: 'Inter', sans-serif;
            font-size: 20px;
            margin-top: 40px;
            padding-top: 25px;
            border-top: 3px solid rgba(255, 255, 255, 0.28);
            color: rgba(255, 255, 255, 0.88);
            text-shadow: 0 1px 3px rgba(0, 0, 0, 0.25);
        }
        
        /* Responsive Design */
        @media (max-width: 1400px) {
            .header-title { font-size: 48px; }
            .summary-title { font-size: 38px; }
            .item-name { font-size: 34px; }
            .item-price { font-size: 32px; }
            .total-row { font-size: 28px; }
            .total-row:last-child { font-size: 42px; }
            .payment-title { font-size: 38px; }
            .payment-methods { grid-template-columns: repeat(3, 1fr); }
            .payment-icon { width: 90px; height: 90px; font-size: 55px; }
            .payment-label { font-size: 30px; }
            .payment-desc { font-size: 20px; }
            .instruction-title { font-size: 32px; }
            .instruction-content { font-size: 24px; }
            .payment-button { font-size: 38px; padding: 25px 70px; }
            .payment-button i { font-size: 44px; }
            .footer-logo { font-size: 48px; }
            .footer-logo i { font-size: 60px; }
            .footer-text { font-size: 24px; }
            .copyright { font-size: 19px; }
        }
        
        @media (max-width: 1200px) {
            .order-item { flex-direction: column; text-align: center; }
            .item-image { width: 180px; height: 180px; margin: 0 auto; }
            .item-image i { font-size: 75px; }
            .item-details { width: 100%; }
            .payment-methods { grid-template-columns: repeat(2, 1fr); }
            .header-title { font-size: 44px; }
            .summary-title { font-size: 36px; }
            .payment-title { font-size: 36px; }
            .payment-button { font-size: 36px; padding: 24px 65px; }
            .footer-logo { font-size: 46px; }
            .footer-logo i { font-size: 56px; }
        }
        
        @media (max-width: 992px) {
            .header { margin: -15px -15px 45px; }
            .header-title { font-size: 40px; }
            .order-summary, .payment-section { padding: 30px 25px; }
            .summary-title { font-size: 34px; }
            .item-name { font-size: 30px; }
            .item-quantity { font-size: 26px; }
            .item-price { font-size: 28px; }
            .total-row { font-size: 26px; }
            .total-row:last-child { font-size: 38px; }
            .payment-title { font-size: 34px; }
            .payment-methods { grid-template-columns: 1fr; }
            .payment-icon { width: 85px; height: 85px; font-size: 50px; }
            .payment-label { font-size: 28px; }
            .payment-desc { font-size: 19px; }
            .instruction-header { flex-direction: column; gap: 15px; }
            .instruction-toggle { width: 65px; height: 65px; font-size: 36px; }
            .instruction-title { font-size: 28px; }
            .instruction-content { font-size: 22px; }
            .payment-button { font-size: 34px; padding: 22px 60px; }
            .payment-button i { font-size: 40px; }
            .footer-logo { font-size: 42px; }
            .footer-logo i { font-size: 52px; }
            .footer-text { font-size: 22px; padding: 0 20px; }
            .copyright { font-size: 18px; }
        }
        
        @media (max-width: 768px) {
            .header { margin: -15px -10px 40px; padding: 20px 0; }
            .header-title { font-size: 36px; }
            .order-summary, .payment-section { padding: 25px 20px; }
            .summary-title { font-size: 30px; }
            .order-items { gap: 25px; }
            .order-item { padding: 20px 15px; }
            .item-image { width: 160px; height: 160px; }
            .item-image i { font-size: 65px; }
            .item-name { font-size: 28px; }
            .item-quantity { font-size: 24px; }
            .item-price { font-size: 26px; }
            .summary-totals { padding: 25px 20px; }
            .total-row { font-size: 24px; padding: 12px 0; }
            .total-row:last-child { font-size: 34px; padding-top: 20px; margin-top: 20px; }
            .payment-title { font-size: 30px; margin-bottom: 30px; }
            .payment-icon { width: 80px; height: 80px; font-size: 48px; }
            .payment-label { font-size: 26px; margin-bottom: 12px; }
            .payment-desc { font-size: 18px; }
            .payment-instruction { padding: 30px 20px; }
            .instruction-toggle { width: 60px; height: 60px; font-size: 34px; }
            .instruction-title { font-size: 26px; }
            .instruction-content { font-size: 20px; padding: 18px; }
            .payment-button { 
                font-size: 30px; 
                padding: 20px 50px; 
                width: 90%;
            }
            .payment-button i { font-size: 36px; margin-right: 15px; }
            .footer { padding: 50px 0 35px; margin-top: 70px; }
            .footer-logo { font-size: 38px; }
            .footer-logo i { font-size: 48px; }
            .footer-text { font-size: 20px; }
            .copyright { font-size: 17px; padding-top: 20px; }
        }
        
        @media (max-width: 480px) {
            .header { margin: -15px -5px 35px; padding: 18px 0; }
            .header-title { font-size: 32px; }
            .summary-title { font-size: 28px; }
            .order-item { padding: 18px 12px; }
            .item-image { width: 140px; height: 140px; }
            .item-image i { font-size: 58px; }
            .item-name { font-size: 26px; }
            .item-quantity { font-size: 22px; }
            .item-price { font-size: 24px; }
            .total-row { font-size: 22px; padding: 10px 0; }
            .total-row:last-child { font-size: 30px; padding-top: 18px; margin-top: 18px; }
            .payment-title { font-size: 28px; margin-bottom: 25px; }
            .payment-icon { width: 75px; height: 75px; font-size: 45px; }
            .payment-label { font-size: 24px; }
            .payment-desc { font-size: 17px; }
            .payment-instruction { padding: 25px 15px; }
            .instruction-toggle { width: 55px; height: 55px; font-size: 32px; }
            .instruction-title { font-size: 24px; }
            .instruction-content { font-size: 19px; padding: 16px; }
            .payment-button { 
                font-size: 28px; 
                padding: 18px 40px; 
                width: 95%;
            }
            .payment-button i { font-size: 32px; }
            .footer { padding: 45px 0 30px; margin-top: 60px; }
            .footer-logo { font-size: 34px; }
            .footer-logo i { font-size: 44px; }
            .footer-text { font-size: 19px; padding: 0 15px; }
            .copyright { font-size: 16px; }
        }
    </style>
</head>
<body>
    <div class="main-container">
        <!-- Header -->
        <header class="header">
            <h1 class="header-title">Pembayaran</h1>
        </header>
        
        <!-- Messages -->
        <?php if ($error_msg): ?>
            <div class="message error">
                <i class="fas fa-exclamation-triangle"></i> <?php echo $error_msg; ?>
            </div>
        <?php endif; ?>
        
        <!-- Order Summary -->
        <section class="order-summary">
            <h2 class="summary-title">📦 Ringkasan Pesanan</h2>
            
            <div class="order-items">
                <?php foreach ($cart_items as $item): ?>
                    <div class="order-item">
                        <div class="item-image">
                            <i class="fas fa-tshirt"></i>
                        </div>
                        <div class="item-details">
                            <h3 class="item-name"><?php echo htmlspecialchars($item['name']); ?></h3>
                            <div class="item-quantity">Jumlah: <?php echo $item['quantity']; ?> pcs</div>
                            <div class="item-price">Rp <?php echo number_format($item['price'] * $item['quantity'], 0, ',', '.'); ?></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <div class="summary-totals">
                <div class="total-row">
                    <span>Subtotal (<?php echo $total_items; ?> item)</span>
                    <span>Rp <?php echo number_format($subtotal, 0, ',', '.'); ?></span>
                </div>
                <div class="total-row">
                    <span>Ongkos Kirim</span>
                    <span>Rp <?php echo number_format($shipping, 0, ',', '.'); ?></span>
                </div>
                <div class="total-row">
                    <span>Total Pembayaran</span>
                    <span>Rp <?php echo number_format($total, 0, ',', '.'); ?></span>
                </div>
            </div>
        </section>
        
        
        <!-- Payment Methods -->
        <!-- Data Pengiriman -->
<section class="payment-section">
    <h2 class="payment-title">📮 Data Pengiriman</h2>

    <div style="display:grid; gap:25px; max-width:900px; margin:0 auto 50px;">
        <input type="text" name="receiver_name" placeholder="Nama Penerima" required
        style="padding:22px;font-size:22px;border-radius:15px;border:2px solid #ccc;">

        <input type="text" name="phone" placeholder="Nomor HP" required
        style="padding:22px;font-size:22px;border-radius:15px;border:2px solid #ccc;">

        <textarea name="address" placeholder="Alamat Lengkap" required rows="4"
        style="padding:22px;font-size:22px;border-radius:15px;border:2px solid #ccc;"></textarea>
    </div>
</section>
            <h2 class="payment-title">💳 Pilih Metode Pembayaran</h2>
            
            <form method="POST" action="checkout.php" id="paymentForm">
                <div class="payment-methods">
                    <label class="payment-option active">
                        <input type="radio" name="payment_method" value="cod" checked hidden>
                        <div class="payment-icon">
                            <i class="fas fa-money-bill-wave"></i>
                        </div>
                        <div class="payment-label">Bayar di Tempat (COD)</div>
                        <div class="payment-desc">Bayar langsung kepada kurir saat paket sampai di tujuan</div>
                    </label>
                    
                    <label class="payment-option">
                        <input type="radio" name="payment_method" value="ewallet" hidden>
                        <div class="payment-icon">
                            <i class="fas fa-wallet"></i>
                        </div>
                        <div class="payment-label">E-Wallet</div>
                        <div class="payment-desc">Bayar menggunakan GoPay, OVO, DANA, atau LinkAja</div>
                    </label>
                    
                    <label class="payment-option">
                        <input type="radio" name="payment_method" value="bank_transfer" hidden>
                        <div class="payment-icon">
                            <i class="fas fa-credit-card"></i>
                        </div>
                        <div class="payment-label">Transfer Bank</div>
                        <div class="payment-desc">Bayar melalui transfer bank BCA, Mandiri, BNI, atau BRI</div>
                    </label>
                </div>
                
                <div class="payment-instruction" id="instructionBox">
                    <div class="instruction-header">
                        <div class="instruction-toggle" id="toggleInstruction">
                            <i class="fas fa-plus"></i>
                        </div>
                        <h3 class="instruction-title">ℹ️ Instruksi Pembayaran</h3>
                    </div>
                    <div class="instruction-content" id="instructionContent">
                        <p>
                            <strong>Untuk Pembayaran COD:</strong><br>
                            • Bayar langsung kepada kurir saat paket tiba<br>
                            • Siapkan uang pas untuk mempermudah transaksi<br>
                            • Pastikan ada yang menerima paket di alamat pengiriman<br><br>
                            
                            <strong>Untuk Pembayaran E-Wallet:</strong><br>
                            • Pilih e-wallet yang Anda gunakan<br>
                            • Scan QR code yang dikirim via email/SMS<br>
                            • Konfirmasi pembayaran di aplikasi e-wallet Anda<br><br>
                            
                            <strong>Untuk Transfer Bank:</strong><br>
                            • Transfer ke rekening BCA: 123-456-7890 a/n Lexz Store<br>
                            • Gunakan nominal unik (contoh: Rp 450.789) untuk memudahkan identifikasi<br>
                            • Upload bukti transfer di halaman konfirmasi<br>
                            • Pesanan akan diproses setelah pembayaran dikonfirmasi (maks. 1x24 jam)
                        </p>
                    </div>
                </div>
                
                <button type="submit" class="payment-button">
                    <i class="fas fa-credit-card"></i> Bayar Sekarang
                </button>
            </form>
        </section>
        
        <!-- Footer -->
        <footer class="footer">
            <div class="footer-logo">
                <i class="fas fa-shopping-bag"></i>
               Lexz Store
            </div>
            <p class="footer-text">
                Terima kasih telah berbelanja di Lexz Store. 
                Pembayaran Anda akan diproses segera setelah konfirmasi diterima.
            </p>
            <p class="copyright">
                &copy; 2026 Lexz Store. All Rights Reserved. 
            </p>
        </footer>
    </div>
    
    <script>
        // Payment method selection
        document.querySelectorAll('.payment-option').forEach(option => {
            option.addEventListener('click', function() {
                // Remove active class from all options
                document.querySelectorAll('.payment-option').forEach(o => {
                    o.classList.remove('active');
                });
                
                // Add active class to selected option
                this.classList.add('active');
                
                // Set the hidden radio button value
                this.querySelector('input[type="radio"]').checked = true;
            });
        });
        
        // Toggle payment instructions
        const toggleBtn = document.getElementById('toggleInstruction');
        const instructionContent = document.getElementById('instructionContent');
        const instructionBox = document.getElementById('instructionBox');
        
        toggleBtn.addEventListener('click', function() {
            if (instructionContent.classList.contains('show')) {
                instructionContent.classList.remove('show');
                toggleBtn.innerHTML = '<i class="fas fa-plus"></i>';
                instructionBox.style.boxShadow = '0 10px 25px rgba(255, 193, 7, 0.4)';
            } else {
                instructionContent.classList.add('show');
                toggleBtn.innerHTML = '<i class="fas fa-minus"></i>';
                instructionBox.style.boxShadow = '0 15px 35px rgba(255, 193, 7, 0.6)';
            }
        });
        
        // Form validation before submit
        document.getElementById('paymentForm').addEventListener('submit', function(e) {
            // Show loading state
            const submitBtn = this.querySelector('.payment-button');
            const originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Memproses Pembayaran...';
            submitBtn.disabled = true;
            
            // Simulate processing delay (remove in production)
            setTimeout(() => {
                // Revert button state if needed (actual submit will redirect)
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
            }, 2000);
        });
        
        // Auto-scroll to payment button on load
        window.addEventListener('load', function() {
            // Scroll to payment section after 500ms
            setTimeout(() => {
                document.querySelector('.payment-section').scrollIntoView({ 
                    behavior: 'smooth',
                    block: 'start'
                });
            }, 500);
        });
    </script>
</body>
</html>