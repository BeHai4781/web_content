<?php
session_start();
require '../config/db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $role = $_POST['role'];

    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? AND role = ? AND status = 'active'");
    $stmt->execute([$username, $role]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user'] = $user;

        // ✅ Kiểm tra nếu là lần đầu đăng nhập
        if ($user['is_first_login']) {
            $_SESSION['user_id'] = $user['id']; 
            header("Location: ../auth/change_password.php?first=1");
            exit();
        }

        // ✅ Chuyển hướng theo vai trò
        if ($user['role'] === 'admin') {
            header("Location: ../admin/index.php");
        } elseif ($user['role'] === 'user') {
            header("Location: ../user/index.php");
        } else {
            header("Location: ../index.php");
        }
        exit();
    } else {
        $error = "Sai thông tin đăng nhập, vai trò hoặc chưa được duyệt.";
    }
}

?>


<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Đăng nhập</title>
    <link href="https://fonts.googleapis.com/css2?family=Merriweather&display=swap" rel="stylesheet">
    <style>
        body {
            margin: 0;
            height: 100vh;
            font-family: 'Merriweather', Georgia, serif;
            background-color: #f0f8ff;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .login-box {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 10px 20px rgba(0,0,0,0.2);
            width: 300px;
        }

        .login-box h2 {
            text-align: center;
            margin-bottom: 20px;
            color: #1f4e79;
        }

        .login-box input,
        .login-box select {
            width: 100%;
            padding: 10px;
            margin: 8px 0;
            border: 1px solid #ccc;
            border-radius: 8px;
            box-sizing: border-box;
        }

        .login-box button {
            width: 100%;
            padding: 10px;
            background-color: #4CAF50;
            color: white;
            font-weight: bold;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            margin-top: 10px;
        }

        .login-box button:hover {
            background-color: #45a049;
        }

        .error {
            color: red;
            text-align: center;
            margin-bottom: 10px;
        }
    </style>
</head>
<body>

<div class="login-box">
    <h2>Đăng nhập</h2>
    <?php if (!empty($error)) echo "<div class='error'>$error</div>"; ?>
    <form method="post">
        <input type="text" name="username" placeholder="Tên đăng nhập" required>
        <input type="password" name="password" placeholder="Mật khẩu" required>
        <select name="role" required>
            <option value="user">Cộng tác viên</option>
            <option value="admin">Quản trị viên</option>
        </select>
        <button type="submit">Đăng nhập</button>
    </form>
    <p style="text-align: center; margin-top: 10px;">
        Chưa có tài khoản? <a href="register.php">Đăng ký</a>
    </p>
</div>

</body>
</html>
