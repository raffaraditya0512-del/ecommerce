<?php
session_start();
require_once '../config.php';

// Redirect to login if not logged in or not user role
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'user') {
    header("Location: ../Auth/login.php");
    exit();
}

// Get user info
$user_id = $_SESSION['user_id'];
$user = $conn->query("SELECT * FROM users WHERE id = $user_id")->fetch_assoc();

// Search functionality
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$where = $search ? "WHERE (name LIKE '%$search%' OR description LIKE '%$search%') AND stock > 0" : "WHERE stock > 0";

// Get products (dummy data if table doesn't exist yet)
$products = [];

// Check if products table exists
$table_exists = $conn->query("SHOW TABLES LIKE 'products'")->num_rows > 0;

if ($table_exists) {
    $result = $conn->query("SELECT * FROM products $where ORDER BY created_at DESC LIMIT 12");
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $products[] = $row;
        }
    }
}

// If no products in database, use dummy data
if (empty($products)) {
    $products = [
        [
            'id' => 1,
            'name' => 'Work Jacket',
            'price' => 200000,
            'description' => 'Jaket kerja berkualitas premium dengan bahan tahan lama',
            'stock' => 15,
            'category' => 'fashion',
            'image_icon' => 'fa-hard-hat'
        ],
        [
            'id' => 2,
            'name' => 'Jaket Kulit',
            'price' => 150000,
            'description' => 'Jaket kulit asli dengan desain modern dan nyaman dipakai',
            'stock' => 8,
            'category' => 'fashion',
            'image_icon' => 'fa-tshirt'
        ],
        [
            'id' => 3,
            'name' => 'T-Shirt Premium',
            'price' => 100000,
            'description' => 'Kaos premium dengan bahan katun terbaik dan nyaman dipakai',
            'stock' => 25,
            'category' => 'fashion',
            'image_icon' => 'fa-tshirt'
        ],
        [
            'id' => 4,
            'name' => 'Hoodie Hitam',
            'price' => 180000,
            'description' => 'Hoodie hangat dengan desain minimalis dan bahan berkualitas',
            'stock' => 12,
            'category' => 'fashion',
            'image_icon' => 'fa-user'
        ],
      
    ];
}

// Get cart count
$cart_count = 0;
if (isset($_SESSION['cart'])) {
    $cart_count = array_sum(array_column($_SESSION['cart'], 'quantity'));
}

// Get wishlist count (stored in session)
$wishlist_count = 0;
if (isset($_SESSION['wishlist'])) {
    $wishlist_count = count($_SESSION['wishlist']);
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lexz Store
        - Temukan Produk Terbaik</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #1c2deb;
            --primary-dark: #0a1a9a;
            --secondary-color: #002cdd;
            --text-dark: #000000;
            --text-light: #ffffff;
            --gray-bg: #d9d9d9;
            --gray-light: #f5f5f5;
            --danger-color: #fc0404;
            --success-color: #28a745;
            --fashion-color: #ff4081;
            --electronics-color: #2196f3;
            --accessories-color: #ff9800;
            --footwear-color: #4caf50;
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
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px 0;
            position: relative;
            z-index: 100;
        }
        
        .logo {
            font-family: 'Inter', sans-serif;
            font-size: 36px;
            font-weight: 900;
            color: var(--primary-color);
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .logo i {
            font-size: 45px;
        }
        
        .search-container {
            position: relative;
            width: 550px;
            max-width: 100%;
        }
        
        .search-input {
            width: 100%;
            padding: 20px 25px 20px 60px;
            border: 3px solid #000000;
            border-radius: 10px;
            font-family: 'Inter', sans-serif;
            font-size: 22px;
            font-weight: 600;
            background-color: rgba(255, 255, 255, 0.95);
            box-shadow: 0 6px 10px rgba(0, 0, 0, 0.25);
            transition: all 0.3s ease;
        }
        
        .search-input:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 8px 15px rgba(28, 45, 235, 0.4);
        }
        
        .search-icon {
            position: absolute;
            left: 22px;
            top: 50%;
            transform: translateY(-50%);
            color: #666;
            font-size: 28px;
        }
        
        .header-actions {
            display: flex;
            align-items: center;
            gap: 30px;
        }
        
        .icon-btn {
            position: relative;
            width: 65px;
            height: 65px;
            background-color: var(--secondary-color);
            border-radius: 20px;
            border: 3px solid #000000;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.35);
            transition: all 0.35s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }
        
        .icon-btn:hover {
            transform: translateY(-5px) scale(1.08);
            box-shadow: 0 12px 20px rgba(0, 0, 0, 0.45);
            background-color: var(--primary-dark);
        }
        
        .icon-btn i {
            font-size: 32px;
            color: var(--text-light);
        }
        
        .badge {
            position: absolute;
            top: -10px;
            right: -10px;
            background-color: var(--danger-color);
            color: white;
            font-family: 'Inter', sans-serif;
            font-size: 18px;
            font-weight: 800;
            width: 32px;
            height: 32px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 3px solid white;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.4);
            animation: pulse 1.5s infinite;
        }
        
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.2); }
            100% { transform: scale(1); }
        }
        
        .logout-btn {
            width: 160px;
            height: 95px;
            background: linear-gradient(135deg, var(--secondary-color), var(--primary-dark));
            border-radius: 25px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            box-shadow: 0 8px 15px rgba(0, 0, 0, 0.35);
            border: 3px solid #000000;
        }
        
        .logout-btn:hover {
            transform: translateY(-8px) rotate(2deg);
            box-shadow: 0 15px 25px rgba(0, 0, 0, 0.5);
        }
        
        .logout-btn span {
            font-family: 'Inter', sans-serif;
            font-size: 26px;
            font-weight: 700;
            color: white;
            margin-top: 10px;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
        }
        
        .logout-btn i {
            font-size: 38px;
            color: white;
        }
        
        /* Hero Section */
        .hero {
            width: 100%;
            height: 500px;
            background: linear-gradient(rgba(0, 15, 60, 0.85), rgba(0, 10, 40, 0.9)), 
                        url('https://images.unsplash.com/photo-1542291026-7eec264c27ff?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=1200&q=80');
            background-size: cover;
            background-position: center;
            border-radius: 30px;
            margin: 50px 0 60px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            text-align: center;
            color: white;
            position: relative;
            overflow: hidden;
            box-shadow: 0 25px 60px rgba(0, 0, 0, 0.45);
        }
        
        .hero::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(28, 45, 235, 0.3) 0%, transparent 70%);
            z-index: 0;
        }
        
        .hero-content {
            position: relative;
            z-index: 1;
            padding: 0 50px;
            max-width: 900px;
        }
        
        .hero h1 {
            font-family: 'Inter', sans-serif;
            font-size: 72px;
            font-weight: 900;
            margin-bottom: 30px;
            text-shadow: 0 6px 20px rgba(0, 0, 0, 0.5);
            line-height: 1.1;
            letter-spacing: -1px;
        }
        
        .hero p {
            font-family: 'Inter', sans-serif;
            font-size: 32px;
            font-weight: 600;
            margin-bottom: 45px;
            max-width: 750px;
            text-shadow: 0 3px 10px rgba(0, 0, 0, 0.4);
            line-height: 1.5;
        }
        
        .btn-primary {
            padding: 22px 60px;
            background: linear-gradient(135deg, var(--secondary-color), var(--primary-dark));
            color: white;
            border: none;
            border-radius: 60px;
            font-family: 'Inter', sans-serif;
            font-size: 30px;
            font-weight: 800;
            cursor: pointer;
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            box-shadow: 0 12px 35px rgba(28, 45, 235, 0.5);
            display: inline-flex;
            align-items: center;
            gap: 20px;
            position: relative;
            overflow: hidden;
        }
        
        .btn-primary:hover {
            transform: translateY(-10px) scale(1.07);
            box-shadow: 0 20px 50px rgba(28, 45, 235, 0.7);
        }
        
        .btn-primary:active {
            transform: translateY(3px);
        }
        
        .btn-primary::after {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.3) 0%, transparent 70%);
            opacity: 0;
            transition: opacity 0.5s;
            pointer-events: none;
        }
        
        .btn-primary:hover::after {
            opacity: 1;
        }
        
        /* Categories Section */
        .categories {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 25px;
            margin: 60px 0 80px;
        }
        
        .category-card {
            background: linear-gradient(135deg, #f8f9ff, #eef2ff);
            border: 3px solid #000000;
            border-radius: 25px;
            padding: 35px 25px;
            text-align: center;
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
            cursor: pointer;
        }
        
        .category-card:hover {
            transform: translateY(-15px) scale(1.05);
            box-shadow: 0 25px 45px rgba(0, 0, 0, 0.25);
            border-color: var(--primary-color);
        }
        
        .category-icon {
            font-size: 65px;
            margin-bottom: 25px;
            transition: all 0.5s ease;
        }
        
        .category-card:hover .category-icon {
            transform: scale(1.3) rotate(15deg);
        }
        
        .category-fashion .category-icon { color: var(--fashion-color); }
        .category-electronics .category-icon { color: var(--electronics-color); }
        .category-accessories .category-icon { color: var(--accessories-color); }
        .category-footwear .category-icon { color: var(--footwear-color); }
        
        .category-name {
            font-family: 'Inter', sans-serif;
            font-size: 34px;
            font-weight: 800;
            margin-bottom: 15px;
        }
        
        .category-fashion .category-name { color: var(--fashion-color); }
        .category-electronics .category-name { color: var(--electronics-color); }
        .category-accessories .category-name { color: var(--accessories-color); }
        .category-footwear .category-name { color: var(--footwear-color); }
        
        .category-count {
            font-family: 'Inter', sans-serif;
            font-size: 26px;
            font-weight: 600;
            color: #555;
        }
        
        /* Products Section */
        .section-title {
            font-family: 'Inter', sans-serif;
            font-size: 52px;
            font-weight: 900;
            color: var(--text-dark);
            margin: 70px 0 50px;
            text-align: center;
            position: relative;
            padding-bottom: 25px;
        }
        
        .section-title::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 150px;
            height: 6px;
            background: linear-gradient(90deg, var(--primary-color), var(--secondary-color), var(--primary-color));
            border-radius: 3px;
        }
        
        .products-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(340px, 1fr));
            gap: 45px;
            margin-bottom: 100px;
        }
        
        .product-card {
            background-color: rgba(255, 255, 255, 0.98);
            border: 3px solid #000000;
            border-radius: 25px;
            overflow: hidden;
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.2);
            transition: all 0.45s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            position: relative;
        }
        
        .product-card:hover {
            transform: translateY(-20px) scale(1.04);
            box-shadow: 0 35px 60px rgba(0, 0, 0, 0.35);
            border-color: var(--primary-color);
        }
        
        .product-image {
            width: 100%;
            height: 320px;
            background: linear-gradient(135deg, #f0f4ff, #e6eeff);
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            overflow: hidden;
        }
        
        .product-image i {
            font-size: 120px;
            transition: all 0.6s ease;
        }
        
        .product-card:hover .product-image i {
            transform: scale(1.4) rotate(10deg);
        }
        
        .category-fashion .product-image { background: linear-gradient(135deg, #fff0f5, #ffe4f0); }
        .category-fashion .product-image i { color: var(--fashion-color); }
        .category-fashion .product-card:hover .product-image i { color: #e91e63; }
        
        .category-electronics .product-image { background: linear-gradient(135deg, #e3f2fd, #bbdefb); }
        .category-electronics .product-image i { color: var(--electronics-color); }
        .category-electronics .product-card:hover .product-image i { color: #1565c0; }
        
        .category-accessories .product-image { background: linear-gradient(135deg, #fff8e1, #ffecb3); }
        .category-accessories .product-image i { color: var(--accessories-color); }
        .category-accessories .product-card:hover .product-image i { color: #ef6c00; }
        
        .category-footwear .product-image { background: linear-gradient(135deg, #e8f5e9, #c8e6c9); }
        .category-footwear .product-image i { color: var(--footwear-color); }
        .category-footwear .product-card:hover .product-image i { color: #2e7d32; }
        
        .wishlist-btn {
            position: absolute;
            top: 25px;
            right: 25px;
            width: 65px;
            height: 65px;
            background-color: rgba(255, 255, 255, 0.92);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            z-index: 10;
            transition: all 0.35s ease;
            border: 3px solid #000000;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
        }
        
        .wishlist-btn:hover {
            background-color: var(--danger-color);
            transform: scale(1.25) rotate(15deg);
            box-shadow: 0 8px 20px rgba(252, 4, 4, 0.5);
        }
        
        .wishlist-btn i {
            font-size: 28px;
            color: #666;
            transition: all 0.3s ease;
        }
        
        .wishlist-btn:hover i,
        .wishlist-btn.active i {
            color: white;
        }
        
        .product-info {
            padding: 40px 35px 35px;
            text-align: center;
        }
        
        .product-name {
            font-family: 'Inter', sans-serif;
            font-size: 38px;
            font-weight: 900;
            color: var(--text-dark);
            margin-bottom: 20px;
            min-height: 65px;
            line-height: 1.3;
        }
        
        .product-desc {
            font-family: 'Inter', sans-serif;
            font-size: 20px;
            color: #666;
            margin-bottom: 25px;
            min-height: 60px;
            line-height: 1.5;
        }
        
        .product-price {
            font-family: 'Inter', sans-serif;
            font-size: 42px;
            font-weight: 800;
            margin: 20px 0 30px;
            background: linear-gradient(90deg, var(--primary-color), var(--secondary-color));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            text-shadow: 0 3px 8px rgba(28, 45, 235, 0.3);
        }
        
        .product-stock {
            display: inline-block;
            padding: 10px 25px;
            border-radius: 50px;
            font-family: 'Inter', sans-serif;
            font-size: 22px;
            font-weight: 700;
            margin-bottom: 25px;
            background: linear-gradient(135deg, #4caf50, #2e7d32);
            color: white;
            box-shadow: 0 4px 10px rgba(76, 175, 80, 0.4);
        }
        
        .product-stock.low {
            background: linear-gradient(135deg, #f44336, #c62828);
            box-shadow: 0 4px 10px rgba(244, 67, 54, 0.4);
        }
        
        .btn-buy {
            width: 100%;
            padding: 22px 15px;
            background: linear-gradient(135deg, var(--secondary-color), var(--primary-dark));
            color: white;
            border: 3px solid #000000;
            border-radius: 25px;
            font-family: 'Inter', sans-serif;
            font-size: 28px;
            font-weight: 800;
            cursor: pointer;
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.3);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 15px;
        }
        
        .btn-buy:hover {
            transform: translateY(-8px) scale(1.05);
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.45);
            background: linear-gradient(135deg, var(--primary-dark), #07157a);
        }
        
        .btn-buy:active {
            transform: translateY(3px);
        }
        
        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 100px 30px;
            background: linear-gradient(135deg, #f8f9ff, #eef2ff);
            border-radius: 40px;
            margin: 60px 0;
            border: 4px dashed var(--primary-color);
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.15);
        }
        
        .empty-state i {
            font-size: 130px;
            color: var(--primary-color);
            margin-bottom: 40px;
            display: block;
            animation: bounce 2s infinite;
        }
        
        @keyframes bounce {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-20px); }
        }
        
        .empty-state h3 {
            font-family: 'Inter', sans-serif;
            font-size: 52px;
            font-weight: 900;
            color: var(--primary-color);
            margin-bottom: 30px;
            text-shadow: 0 3px 8px rgba(28, 45, 235, 0.2);
        }
        
        .empty-state p {
            font-family: 'Inter', sans-serif;
            font-size: 30px;
            color: #555;
            max-width: 700px;
            margin: 0 auto 45px;
            line-height: 1.6;
        }
        
        /* Footer */
        .footer {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 70px 0 50px;
            margin-top: 100px;
            text-align: center;
            border-radius: 40px 40px 0 0;
            position: relative;
            overflow: hidden;
        }
        
        .footer::before {
            content: '';
            position: absolute;
            top: -100px;
            left: -100px;
            width: 300px;
            height: 300px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
        }
        
        .footer::after {
            content: '';
            position: absolute;
            bottom: -150px;
            right: -100px;
            width: 400px;
            height: 400px;
            background: rgba(255, 255, 255, 0.08);
            border-radius: 50%;
        }
        
        .footer-logo {
            font-family: 'Inter', sans-serif;
            font-size: 56px;
            font-weight: 900;
            margin-bottom: 30px;
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 25px;
            text-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
        }
        
        .footer-logo i {
            font-size: 70px;
        }
        
        .footer-text {
            font-family: 'Inter', sans-serif;
            font-size: 28px;
            margin: 25px 0;
            max-width: 850px;
            margin-left: auto;
            margin-right: auto;
            line-height: 1.7;
            color: rgba(255, 255, 255, 0.92);
            text-shadow: 0 2px 6px rgba(0, 0, 0, 0.2);
        }
        
        .copyright {
            font-family: 'Inter', sans-serif;
            font-size: 22px;
            margin-top: 45px;
            padding-top: 30px;
            border-top: 3px solid rgba(255, 255, 255, 0.25);
            color: rgba(255, 255, 255, 0.85);
            text-shadow: 0 1px 3px rgba(0, 0, 0, 0.2);
        }
        
        /* Responsive Design */
        @media (max-width: 1400px) {
            .search-container {
                width: 480px;
            }
            
            .hero h1 {
                font-size: 64px;
            }
            
            .hero p {
                font-size: 28px;
            }
            
            .btn-primary {
                font-size: 28px;
                padding: 20px 55px;
            }
            
            .category-name {
                font-size: 30px;
            }
            
            .category-count {
                font-size: 24px;
            }
            
            .product-name {
                font-size: 34px;
            }
            
            .product-price {
                font-size: 38px;
            }
            
            .btn-buy {
                font-size: 26px;
                padding: 20px 15px;
            }
        }
        
        @media (max-width: 1200px) {
            .header {
                flex-direction: column;
                gap: 30px;
            }
            
            .search-container {
                width: 100%;
            }
            
            .header-actions {
                width: 100%;
                justify-content: space-around;
            }
            
            .logout-btn {
                width: 140px;
                height: 85px;
            }
            
            .logout-btn span {
                font-size: 24px;
            }
            
            .logout-btn i {
                font-size: 34px;
            }
            
            .hero {
                height: 450px;
            }
            
            .hero h1 {
                font-size: 56px;
            }
            
            .hero p {
                font-size: 26px;
            }
            
            .btn-primary {
                font-size: 26px;
                padding: 18px 50px;
            }
            
            .categories {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .section-title {
                font-size: 46px;
            }
            
            .products-grid {
                grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            }
            
            .product-image {
                height: 290px;
            }
            
            .product-name {
                font-size: 32px;
                min-height: 60px;
            }
            
            .product-desc {
                font-size: 19px;
                min-height: 55px;
            }
            
            .product-price {
                font-size: 36px;
            }
            
            .footer-logo {
                font-size: 50px;
            }
            
            .footer-logo i {
                font-size: 62px;
            }
            
            .footer-text {
                font-size: 26px;
            }
            
            .copyright {
                font-size: 20px;
            }
        }
        
        @media (max-width: 992px) {
            .logo {
                font-size: 32px;
            }
            
            .logo i {
                font-size: 40px;
            }
            
            .search-input {
                font-size: 20px;
                padding: 18px 25px 18px 55px;
            }
            
            .search-icon {
                font-size: 26px;
            }
            
            .icon-btn {
                width: 60px;
                height: 60px;
            }
            
            .icon-btn i {
                font-size: 30px;
            }
            
            .badge {
                width: 30px;
                height: 30px;
                font-size: 17px;
            }
            
            .logout-btn {
                width: 130px;
                height: 80px;
            }
            
            .logout-btn span {
                font-size: 22px;
            }
            
            .logout-btn i {
                font-size: 32px;
            }
            
            .hero {
                height: 400px;
                border-radius: 25px;
            }
            
            .hero h1 {
                font-size: 48px;
            }
            
            .hero p {
                font-size: 24px;
                padding: 0 20px;
            }
            
            .btn-primary {
                font-size: 24px;
                padding: 17px 45px;
            }
            
            .categories {
                grid-template-columns: repeat(2, 1fr);
                gap: 20px;
            }
            
            .category-card {
                padding: 30px 20px;
            }
            
            .category-icon {
                font-size: 60px;
            }
            
            .category-name {
                font-size: 28px;
            }
            
            .category-count {
                font-size: 22px;
            }
            
            .section-title {
                font-size: 42px;
                margin: 60px 0 45px;
            }
            
            .products-grid {
                grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
                gap: 35px;
            }
            
            .product-image {
                height: 270px;
            }
            
            .product-name {
                font-size: 30px;
                min-height: 55px;
            }
            
            .product-desc {
                font-size: 18px;
                min-height: 50px;
            }
            
            .product-price {
                font-size: 34px;
            }
            
            .product-stock {
                font-size: 20px;
                padding: 8px 22px;
            }
            
            .btn-buy {
                font-size: 24px;
                padding: 18px 15px;
            }
            
            .footer-logo {
                font-size: 46px;
            }
            
            .footer-logo i {
                font-size: 56px;
            }
            
            .footer-text {
                font-size: 24px;
                padding: 0 20px;
            }
            
            .copyright {
                font-size: 19px;
            }
        }
        
        @media (max-width: 768px) {
            .logo {
                font-size: 28px;
            }
            
            .logo i {
                font-size: 35px;
            }
            
            .search-input {
                font-size: 19px;
                padding: 17px 25px 17px 50px;
            }
            
            .search-icon {
                font-size: 24px;
            }
            
            .icon-btn {
                width: 55px;
                height: 55px;
            }
            
            .icon-btn i {
                font-size: 27px;
            }
            
            .badge {
                width: 28px;
                height: 28px;
                font-size: 16px;
            }
            
            .logout-btn {
                width: 120px;
                height: 75px;
            }
            
            .logout-btn span {
                font-size: 20px;
                display: none;
            }
            
            .logout-btn i {
                font-size: 30px;
            }
            
            .hero {
                height: 360px;
                border-radius: 20px;
            }
            
            .hero h1 {
                font-size: 42px;
            }
            
            .hero p {
                font-size: 22px;
            }
            
            .btn-primary {
                font-size: 22px;
                padding: 16px 40px;
            }
            
            .categories {
                grid-template-columns: 1fr;
            }
            
            .category-card {
                padding: 35px 25px;
            }
            
            .category-icon {
                font-size: 65px;
            }
            
            .category-name {
                font-size: 32px;
            }
            
            .category-count {
                font-size: 24px;
            }
            
            .section-title {
                font-size: 38px;
                margin: 50px 0 40px;
            }
            
            .products-grid {
                grid-template-columns: 1fr;
            }
            
            .product-image {
                height: 250px;
            }
            
            .product-name {
                font-size: 32px;
                min-height: 60px;
            }
            
            .product-desc {
                font-size: 20px;
                min-height: 55px;
            }
            
            .product-price {
                font-size: 36px;
            }
            
            .product-stock {
                font-size: 22px;
                padding: 10px 25px;
            }
            
            .btn-buy {
                font-size: 26px;
                padding: 20px 15px;
            }
            
            .empty-state {
                padding: 80px 20px;
            }
            
            .empty-state i {
                font-size: 110px;
            }
            
            .empty-state h3 {
                font-size: 46px;
            }
            
            .empty-state p {
                font-size: 26px;
            }
            
            .footer-logo {
                font-size: 42px;
            }
            
            .footer-logo i {
                font-size: 52px;
            }
            
            .footer-text {
                font-size: 22px;
            }
            
            .copyright {
                font-size: 18px;
                padding: 25px;
            }
        }
        
        @media (max-width: 480px) {
            .header {
                padding: 15px 0;
                gap: 20px;
            }
            
            .logo {
                font-size: 26px;
            }
            
            .logo i {
                font-size: 32px;
            }
            
            .search-input {
                font-size: 18px;
                padding: 16px 25px 16px 48px;
            }
            
            .search-icon {
                font-size: 22px;
            }
            
            .icon-btn {
                width: 50px;
                height: 50px;
            }
            
            .icon-btn i {
                font-size: 25px;
            }
            
            .badge {
                width: 26px;
                height: 26px;
                font-size: 15px;
            }
            
            .logout-btn {
                width: 100px;
                height: 70px;
            }
            
            .logout-btn i {
                font-size: 28px;
            }
            
            .hero {
                height: 320px;
                border-radius: 15px;
            }
            
            .hero h1 {
                font-size: 38px;
            }
            
            .hero p {
                font-size: 20px;
                padding: 0 15px;
            }
            
            .btn-primary {
                font-size: 20px;
                padding: 15px 35px;
            }
            
            .category-icon {
                font-size: 55px;
            }
            
            .category-name {
                font-size: 28px;
            }
            
            .category-count {
                font-size: 22px;
            }
            
            .section-title {
                font-size: 34px;
                margin: 45px 0 35px;
            }
            
            .product-image {
                height: 230px;
            }
            
            .product-name {
                font-size: 28px;
                min-height: 55px;
            }
            
            .product-desc {
                font-size: 18px;
                min-height: 50px;
            }
            
            .product-price {
                font-size: 32px;
            }
            
            .product-stock {
                font-size: 20px;
                padding: 9px 22px;
            }
            
            .btn-buy {
                font-size: 24px;
                padding: 18px 15px;
            }
            
            .empty-state i {
                font-size: 90px;
            }
            
            .empty-state h3 {
                font-size: 40px;
            }
            
            .empty-state p {
                font-size: 24px;
            }
            
            .footer-logo {
                font-size: 38px;
            }
            
            .footer-logo i {
                font-size: 46px;
            }
            
            .footer-text {
                font-size: 20px;
                padding: 0 15px;
            }
            
            .copyright {
                font-size: 16px;
            }
        }
    </style>
</head>
<body>
    <div class="main-container">
        <!-- Header -->
        <header class="header">
            <a href="index.php" class="logo">
                <i class="fas fa-shopping-bag"></i>
                Lexz Store
            </a>
            
            <div class="search-container">
                <i class="fas fa-search search-icon"></i>
                <form method="GET" action="">
                    <input 
                        type="text" 
                        name="search" 
                        class="search-input" 
                        placeholder="Cari produk fashion, elektronik, aksesoris..." 
                        value="<?php echo htmlspecialchars($search); ?>"
                    >
                </form>
            </div>
            
            <div class="header-actions">
                <div class="icon-btn" id="wishlistBtn" title="Wishlist">
                    <i class="fas fa-heart"></i>
                    <?php if ($wishlist_count > 0): ?>
                        <span class="badge"><?php echo $wishlist_count; ?></span>
                    <?php endif; ?>
                </div>
                
                <a href="keranjang.php" class="icon-btn" title="Keranjang">
                    <i class="fas fa-shopping-cart"></i>
                    <?php if ($cart_count > 0): ?>
                        <span class="badge"><?php echo $cart_count; ?></span>
                    <?php endif; ?>
                </a>
                
                <div class="logout-btn" onclick="confirmLogout()">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Logout</span>
                </div>
            </div>
        </header>
        
        <!-- Hero Section -->
        <section class="hero">
            <div class="hero-content">
                <h1>Halo, <?php echo htmlspecialchars($user['username']); ?>! 👋</h1>
                <p>Temukan produk berkualitas dengan harga terbaik hanya di Lexz Store. Diskon hingga 50% untuk pembelian hari ini!</p>
                <a href="#products" class="btn-primary">
                    <i class="fas fa-fire"></i> Produk Terlaris
                </a>
            </div>
        </section>
        
        <!-- Categories Section -->
        <section class="categories">
            <div class="category-card category-fashion">
                <i class="fas fa-tshirt category-icon"></i>
                <h3 class="category-name">Fashion</h3>
                <div class="category-count">4 Produk</div>
            </div>
            <div class="category-card category-electronics">
                <i class="fas fa-headphones category-icon"></i>
                <h3 class="category-name">Elektronik</h3>
                <div class="category-count">2 Produk</div>
            </div>
            <div class="category-card category-accessories">
                <i class="fas fa-shopping-bag category-icon"></i>
                <h3 class="category-name">Aksesoris</h3>
                <div class="category-count">3 Produk</div>
            </div>
            <div class="category-card category-footwear">
                <i class="fas fa-shoe-prints category-icon"></i>
                <h3 class="category-name">Footwear</h3>
                <div class="category-count">1 Produk</div>
            </div>
        </section>
        
        <!-- Products Section -->
        <section id="products">
            <h2 class="section-title">🔥 Produk Terbaru</h2>
            
            <?php if (empty($products)): ?>
                <div class="empty-state">
                    <i class="fas fa-box-open"></i>
                    <h3>Tidak Ada Produk</h3>
                    <p>
                        <?php if ($search): ?>
                            Tidak ada produk yang cocok dengan pencarian "<?php echo htmlspecialchars($search); ?>".
                            <br>Coba kata kunci lain atau reset pencarian.
                        <?php else: ?>
                            Belum ada produk yang tersedia saat ini. Silakan cek kembali nanti!
                        <?php endif; ?>
                    </p>
                    <?php if ($search): ?>
                        <a href="index.php" class="btn-primary" style="padding: 16px 40px; font-size: 22px;">
                            <i class="fas fa-redo"></i> Reset Pencarian
                        </a>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <div class="products-grid">
                    <?php foreach ($products as $product): ?>
                        <?php
                        // Determine category class for styling
                        $category_class = 'category-fashion';
                        if ($product['category'] === 'electronics') $category_class = 'category-electronics';
                        if ($product['category'] === 'accessories') $category_class = 'category-accessories';
                        if ($product['category'] === 'footwear') $category_class = 'category-footwear';
                        
                        // Determine stock status
                        $stock_class = $product['stock'] <= 5 ? 'low' : '';
                        ?>
                        <div class="product-card <?php echo $category_class; ?>">
                            <div class="product-image">
                                <i class="fas <?php echo $product['image_icon']; ?>"></i>
                                <div class="wishlist-btn" data-product-id="<?php echo $product['id']; ?>">
                                    <i class="far fa-heart"></i>
                                </div>
                            </div>
                            <div class="product-info">
                                <h3 class="product-name"><?php echo htmlspecialchars($product['name']); ?></h3>
                                <p class="product-desc"><?php echo htmlspecialchars(substr($product['description'], 0, 70)) . '...'; ?></p>
                                <div class="product-price">Rp <?php echo number_format($product['price'], 0, ',', '.'); ?></div>
                                <div class="product-stock <?php echo $stock_class; ?>">
                                    <?php echo $product['stock']; ?> tersedia
                                </div>
                                <a href="keranjang.php?add_product=<?php echo $product['id']; ?>&quantity=1" class="btn-buy">
                                    <i class="fas fa-shopping-cart"></i> Beli Sekarang
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>
        
        <!-- Footer -->
        <footer class="footer">
            <div class="footer-logo">
                <i class="fas fa-shopping-bag"></i>
               Lexz Store
            </div>
            <p class="footer-text">
                Platform e-commerce terpercaya untuk kebutuhan fashion, elektronik, dan lifestyle Anda. 
                Kualitas terjamin, harga terbaik, pengiriman cepat!
            </p>
            <p class="copyright">
                &copy; 2026 Lexz Store. All Rights Reserved.
            </p>
        </footer>
    </div>
    
    <script>
        // Wishlist functionality
        document.querySelectorAll('.wishlist-btn').forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                const productId = this.getAttribute('data-product-id');
                const icon = this.querySelector('i');
                
                // Toggle wishlist state
                if (icon.classList.contains('fa-heart')) {
                    icon.classList.remove('fa-heart');
                    icon.classList.add('far', 'fa-heart');
                } else {
                    icon.classList.remove('far', 'fa-heart');
                    icon.classList.add('fa-heart');
                    icon.style.color = 'white';
                    this.style.backgroundColor = '#e53935';
                    
                    // Show toast notification
                    showToast('Produk ditambahkan ke wishlist!', 'success');
                }
            });
        });
        
        // Logout confirmation
        function confirmLogout() {
            if (confirm('Apakah Anda yakin ingin logout?')) {
                window.location.href = '../auth/logout.php';
            }
        }
        
        // Toast notification function
        function showToast(message, type = 'info') {
            // Create toast element
            const toast = document.createElement('div');
            toast.style.position = 'fixed';
            toast.style.top = '20px';
            toast.style.right = '20px';
            toast.style.padding = '18px 30px';
            toast.style.borderRadius = '15px';
            toast.style.color = '#fff';
            toast.style.fontFamily = "'Inter', sans-serif";
            toast.style.fontSize = '20px';
            toast.style.fontWeight = '700';
            toast.style.boxShadow = '0 6px 15px rgba(0, 0, 0, 0.3)';
            toast.style.zIndex = '9999';
            toast.style.animation = 'slideIn 0.4s ease-out, fadeOut 0.4s ease-out 3s';
            toast.style.maxWidth = '450px';
            toast.style.textAlign = 'center';
            toast.style.border = '3px solid transparent';
            
            // Set colors based on type
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
            
            toast.style.background = `linear-gradient(135deg, ${bgColor}, ${darkenColor(bgColor, 15)})`;
            toast.style.borderColor = borderColor;
            toast.textContent = message;
            
            document.body.appendChild(toast);
            
            // Remove after animation
            setTimeout(() => {
                toast.style.animation = 'fadeOut 0.5s ease-out';
                setTimeout(() => {
                    toast.remove();
                }, 500);
            }, 3000);
        }
        
        // Helper function to darken color
        function darkenColor(color, percent) {
            if (color.startsWith('#')) {
                let r = parseInt(color.substring(1, 3), 16);
                let g = parseInt(color.substring(3, 5), 16);
                let b = parseInt(color.substring(5, 7), 16);
                
                r = Math.max(0, r - (255 * percent / 100));
                g = Math.max(0, g - (255 * percent / 100));
                b = Math.max(0, b - (255 * percent / 100));
                
                return `#${Math.round(r).toString(16).padStart(2, '0')}${Math.round(g).toString(16).padStart(2, '0')}${Math.round(b).toString(16).padStart(2, '0')}`;
            }
            return color;
        }
        
        // Add CSS for toast animation
        const style = document.createElement('style');
        style.textContent = `
            @keyframes slideIn {
                from {
                    transform: translateX(500px) scale(0.8);
                    opacity: 0;
                }
                to {
                    transform: translateX(0) scale(1);
                    opacity: 1;
                }
            }
            @keyframes fadeOut {
                from {
                    opacity: 1;
                    transform: translateX(0) scale(1);
                }
                to {
                    opacity: 0;
                    transform: translateX(100px) scale(0.9);
                }
            }
        `;
        document.head.appendChild(style);
        
        // Smooth scroll for anchor links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function(e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    window.scrollTo({
                        top: target.offsetTop - 120,
                        behavior: 'smooth'
                    });
                }
            });
        });
        
        // Auto-show toast if coming from product page
        window.addEventListener('load', function() {
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.get('added')) {
                showToast('✅ Produk berhasil ditambahkan ke keranjang!', 'success');
                // Remove param from URL without reloading
                window.history.replaceState({}, document.title, window.location.pathname);
            }
        });
    </script>
</body>
</html>