<?php
require '../config/db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $fullname = trim($_POST['fullname'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');

    $stmt = $pdo->prepare("INSERT INTO users (fullname, username, password, email, phone, status, role)
                           VALUES (?, ?, ?, ?, ?, 'pending', 'user')");
    $stmt->execute([$fullname, $username, $password, $email, $phone]);

    $user_id = $pdo->lastInsertId();

    // Chỉ thêm thông báo nếu chưa tồn tại
    $check = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE type = 'approval_request' AND user_id = ?");
    $check->execute([$user_id]);

    if ($check->fetchColumn() == 0) {
        $stmt = $pdo->prepare("INSERT INTO notifications (type, user_id, is_read, created_at)
                               VALUES ('approval_request', ?, 0, NOW())");
        $stmt->execute([$user_id]);
    }

    echo "Đăng ký thành công. Vui lòng chờ admin duyệt tài khoản.";
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Đăng ký Cộng tác viên</title>
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

        .register-box {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 10px 20px rgba(0,0,0,0.2);
            width: 320px;
        }

        .register-box h2 {
            text-align: center;
            margin-bottom: 20px;
            color: #1f4e79;
        }

        .register-box input {
            width: 100%;
            padding: 10px;
            margin: 8px 0;
            border: 1px solid #ccc;
            border-radius: 8px;
            box-sizing: border-box;
        }

        .register-box button {
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

        .register-box button:hover {
            background-color: #45a049;
        }
    </style>
</head>
<body>

<div class="register-box">
    <h2>Đăng ký Cộng tác viên</h2>
    <form method="post">
        <input type="text" name="fullname" placeholder="Họ tên" required>
        <input type="text" name="username" placeholder="Tên đăng nhập" required>
        <input type="password" name="password" placeholder="Mật khẩu" required>
        <input type="email" name="email" placeholder="Email" required>
        <input type="text" name="phone" placeholder="Số điện thoại" required>
        <button type="submit">Gửi yêu cầu duyệt</button>
    </form>
    <p style="text-align: center; margin-top: 10px;">
        Đã có tài khoản? <a href="login.php">Đăng nhập</a>
    </p>
</div>

</body>
</html>
