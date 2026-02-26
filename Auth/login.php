<?php
session_start();
require_once '../config.php';

// Check if user is already logged in
if (isset($_SESSION['user_id'])) {
    header("Location: ../Admin/admin_dashboard.php");
    exit();
}

$error = '';
$success = '';

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    
    if (empty($username) || empty($password)) {
        $error = "Username dan password harus diisi!";
    } else {
        // Prepare statement to prevent SQL injection
        $stmt = $conn->prepare("SELECT id, username, email, password, role FROM users WHERE username = ? OR email = ?");
        $stmt->bind_param("ss", $username, $username);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows == 1) {
            $user = $result->fetch_assoc();
            
            // Verify password
            if (password_verify($password, $user['password'])) {
                // Set session variables
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['role'] = $user['role'];
                
                // Redirect based on role
                switch ($user['role']) {
                    case 'admin':
                        header("Location: ../Admin/admin_dashboard.php");
                        break;
                    case 'petugas':
                        header("Location: petugas_dashboard.php");
                        break;
                    case 'user':
                        header("Location: ../User/index.php");
                        break;
                    default:
                        header("Location: dashboard.php");
                }
                exit();
            } else {
                $error = "Username atau password salah!";
            }
        } else {
            $error = "Username atau password salah!";
        }
        
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Login - E-Commerce Radit</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" />
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Nico+Moji:wght@400&display=swap" />
    <style>
        :root {
            --default-font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto,
                Ubuntu, "Helvetica Neue", Helvetica, Arial, "PingFang SC",
                "Hiragino Sans GB", "Microsoft Yahei UI", "Microsoft Yahei",
                "Source Han Sans CN", sans-serif;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: var(--default-font-family);
            background: linear-gradient(180deg, #172cb9, #3447c1);
            min-height: 100vh;
            padding: 0;
            margin: 0;
            overflow-x: hidden;
        }

        .main-container {
            width: 100%;
            min-height: 100vh;
            background: linear-gradient(180deg, #172cb9, #3447c1);
            position: relative;
            overflow: hidden;
            display: flex;
        }

        .left-panel {
            width: 50%;
            height: 100%;
            background: linear-gradient(135deg, #f0f4ff 0%, #e6eeff 100%);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 1;
            padding: 20px;
            position: relative;
            overflow: hidden;
        }

        .left-panel::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, rgba(255,255,255,0) 70%);
            z-index: 0;
        }

        .left-content {
            text-align: center;
            max-width: 500px;
            z-index: 1;
            position: relative;
        }

        .left-content h1 {
            font-family: 'Nico Moji', cursive;
            font-size: 64px;
            font-weight: 700;
            color: #1a237e;
            margin-bottom: 30px;
            line-height: 1.1;
            text-shadow: 0 2px 4px rgba(0,0,0,0.1);
            position: relative;
        }

        .left-content h1 span {
            color: #1a73e8;
            font-weight: 800;
            text-shadow: 0 1px 2px rgba(26, 115, 232, 0.2);
        }

        .left-content h1 span:nth-child(2) {
            color: #0d47a1;
            font-weight: 800;
            text-shadow: 0 1px 2px rgba(13, 71, 161, 0.2);
        }

        .left-content p {
            font-family: 'Inter', sans-serif;
            font-size: 24px;
            color: #283593;
            line-height: 1.5;
            margin-top: 20px;
            font-weight: 500;
        }

        .left-content .tagline {
            font-size: 18px;
            color: #3949ab;
            margin-top: 30px;
            font-weight: 600;
            letter-spacing: 0.5px;
        }

        .right-panel {
            width: 50%;
            height: 100%;
            background-color: #1729f3;
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 2;
            padding: 20px;
            position: relative;
        }

        .right-panel::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" width="100" height="100" viewBox="0 0 100 100"><rect width="100" height="100" fill="none" stroke="%231729f3" stroke-width="1" opacity="0.05"/></svg>');
            z-index: 0;
        }

        .login-form {
            width: 100%;
            max-width: 500px;
            padding: 40px;
            background-color: rgba(255, 255, 255, 0.1);
            border-radius: 25px;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.15);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            z-index: 1;
        }

        .login-form h2 {
            font-family: 'Inter', sans-serif;
            font-size: 48px;
            font-weight: 700;
            color: #ffffff;
            text-align: center;
            margin-bottom: 40px;
        }

        .form-group {
            margin-bottom: 30px;
        }

        .form-group label {
            display: block;
            font-family: 'Inter', sans-serif;
            font-size: 24px;
            font-weight: 400;
            color: #ffffff;
            margin-bottom: 15px;
            text-align: left;
        }

        .form-group input {
            width: 100%;
            padding: 20px;
            background-color: rgba(255, 255, 255, 0.15);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 19px;
            font-family: 'Inter', sans-serif;
            font-size: 20px;
            color: #ffffff;
            transition: all 0.3s ease;
        }

        .form-group input:focus {
            outline: none;
            border-color: #ffffff;
            background-color: rgba(255, 255, 255, 0.25);
            box-shadow: 0 0 0 3px rgba(255, 255, 255, 0.2);
        }

        .btn-login {
            width: 100%;
            padding: 20px;
            background: linear-gradient(to right, #1a73e8, #0d47a1);
            border: none;
            border-radius: 19px;
            font-family: 'Inter', sans-serif;
            font-size: 24px;
            font-weight: 600;
            color: #ffffff;
            cursor: pointer;
            box-shadow: 0 4px 15px rgba(13, 71, 161, 0.3);
            transition: all 0.3s ease;
            margin-top: 10px;
        }

        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(13, 71, 161, 0.4);
            background: linear-gradient(to right, #1a73e8, #0d47a1);
        }

        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 10px;
            font-family: 'Inter', sans-serif;
            font-size: 18px;
        }

        .alert-error {
            background-color: #ffebee;
            color: #c62828;
            border: 1px solid #ef9a9a;
        }

        .alert-success {
            background-color: #e8f5e9;
            color: #2e7d32;
            border: 1px solid #a5d6a7;
        }

        .register-link {
            text-align: center;
            margin-top: 20px;
            font-family: 'Inter', sans-serif;
            font-size: 18px;
            color: #ffffff;
        }

        .register-link a {
            color: #ffd700;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .register-link a:hover {
            text-decoration: underline;
            color: #ffecb3;
            text-shadow: 0 0 5px rgba(255, 224, 179, 0.5);
        }

        @media (max-width: 1024px) {
            .left-panel, .right-panel {
                width: 100%;
                height: auto;
                min-height: 50vh;
            }
            
            .left-content h1 {
                font-size: 48px;
            }
            
            .left-content p {
                font-size: 20px;
            }
        }

        @media (max-width: 768px) {
            .left-content h1 {
                font-size: 40px;
                line-height: 1.2;
            }
            
            .left-content p {
                font-size: 18px;
            }
            
            .login-form h2 {
                font-size: 36px;
            }
            
            .form-group label {
                font-size: 20px;
            }
            
            .form-group input {
                padding: 15px;
                font-size: 18px;
            }
            
            .btn-login {
                padding: 15px;
                font-size: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="main-container">
        <div class="left-panel">
            <div class="left-content">
                <h1>LOGIN FOR <span>ALL</span> <span>ROLES</span></h1>
                <p>
                    Selamat datang di E-Commerce Radit
                    <br>Masuk dengan akun Anda untuk melanjutkan
                </p>
                <div class="tagline">Sistem Manajemen E-Commerce Profesional</div>
            </div>
        </div>
        
        <div class="right-panel">
            <div class="login-form">
                <h2>Login</h2>
                
                <?php if ($error): ?>
                    <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                    <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
                <?php endif; ?>
                
                <form method="POST" action="">
                    <div class="form-group">
                        <label for="username">Username atau Email</label>
                        <input 
                            type="text" 
                            id="username" 
                            name="username" 
                            placeholder="Masukkan username atau email"
                            required
                            value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>"
                        >
                    </div>
                    
                    <div class="form-group">
                        <label for="password">Password</label>
                        <input 
                            type="password" 
                            id="password" 
                            name="password" 
                            placeholder="Masukkan password"
                            required
                        >
                    </div>
                    
                    <button type="submit" class="btn-login">Login</button>
                </form>
                
                <div class="register-link">
                    Belum punya akun? <a href="register.php">Daftar sekarang</a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>