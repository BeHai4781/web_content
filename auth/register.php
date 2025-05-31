<?php
session_start();
require '../config/db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $fullname     = trim($_POST['fullname'] ?? '');
    $username     = trim($_POST['username'] ?? '');
    $raw_password = $_POST['password'] ?? '';
    $email        = trim($_POST['email'] ?? '');
    $phone        = trim($_POST['phone'] ?? '');

    // Kiểm tra các thông tin bắt buộc
    if (empty($fullname) || empty($username) || empty($raw_password) || empty($email) || empty($phone)) {
        $_SESSION['error'] = "Vui lòng nhập đầy đủ các thông tin.";
        header("Location: register.php");
        exit;
    }
    
    // Kiểm tra định dạng email hợp lệ
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['error'] = "Email không hợp lệ!";
        header("Location: register.php");
        exit;
    }
    
    // Kiểm tra số điện thoại: chỉ chứa chữ số, 10 hoặc 11 số
    if (!preg_match('/^\d{10,11}$/', $phone)) {
        $_SESSION['error'] = "Số điện thoại không hợp lệ. Vui lòng nhập số điện thoại gồm 10 hoặc 11 chữ số.";
        header("Location: register.php");
        exit;
    }
    
    // Yêu cầu mật khẩu phải có tối thiểu 6 ký tự
    if (strlen($raw_password) < 6) {
        $_SESSION['error'] = "Mật khẩu phải có ít nhất 6 ký tự.";
        header("Location: register.php");
        exit;
    }

    // Băm mật khẩu
    $password = password_hash($raw_password, PASSWORD_DEFAULT);
    
    try {
        $stmt = $pdo->prepare("INSERT INTO users (fullname, username, password, email, phone, status, role)
                               VALUES (?, ?, ?, ?, ?, 'pending', 'user')");
        $stmt->execute([$fullname, $username, $password, $email, $phone]);

        $user_id = $pdo->lastInsertId();
        
        // Kiểm tra nếu chưa có thông báo thì thêm thông báo yêu cầu duyệt
        $check = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE type = 'approval_request' AND user_id = ?");
        $check->execute([$user_id]);

        if ($check->fetchColumn() == 0) {
            $stmt = $pdo->prepare("INSERT INTO notifications (type, user_id, is_read, created_at)
                                   VALUES ('approval_request', ?, 0, NOW())");
            $stmt->execute([$user_id]);
        }
        
        $_SESSION['success'] = "Đã đăng ký tài khoản cộng tác viên, vui lòng chờ quản trị viên duyệt! Chúng tôi sẽ thông báo cho bạn qua email đăng ký.";
        header("Location: register.php");
        exit;
    } catch (PDOException $e) {
        $_SESSION['error'] = "Đã xảy ra lỗi trong quá trình đăng ký. Vui lòng thử lại sau.";
        header("Location: register.php");
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng ký Cộng tác viên - PNT website</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500&display=swap" rel="stylesheet">
    <style>
        /* Global reset và font */
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        body {
            font-family: 'Roboto', sans-serif;
            background: linear-gradient(135deg, #253342, #0091ae);
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 20px;
        }
        /* Container chính chia 2 cột */
        .container {
            background: #fff;
            width: 800px;
            max-width: 100%;
            display: flex;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
        }
        /* Phía bên trái: thông tin giới thiệu */
        .left-panel {
            background: #0091ae;
            color: #fff;
            padding: 40px;
            width: 50%;
            display: flex;
            flex-direction: column;
            justify-content: center;
            text-align: center;
        }
        .left-panel h1 {
            font-size: 36px;
            margin-bottom: 10px;
        }
        .left-panel h2 {
            font-size: 20px;
            margin-bottom: 20px;
            font-weight: normal;
        }
        .left-panel p {
            font-size: 16px;
            line-height: 1.6;
            margin-bottom: 15px;
        }
        /* Phía bên phải: form đăng ký */
        .right-panel {
            padding: 40px;
            width: 50%;
        }
        .right-panel h2 {
            text-align: center;
            margin-bottom: 20px;
            color: #253342;
        }
        .form-group {
            position: relative;
            margin-bottom: 20px;
        }
        .form-group input {
            width: 100%;
            padding: 15px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 16px;
            outline: none;
            transition: border 0.2s ease;
        }
        .form-group input:focus {
            border-color: #0091ae;
        }
        .form-group input:focus + label,
        .form-group input:not(:placeholder-shown) + label {
            top: -10px;
            left: 10px;
            font-size: 12px;
            background: #fff;
            padding: 0 5px;
            color: #0091ae;
        }
        .form-group label {
            position: absolute;
            left: 15px;
            top: 15px;
            color: #aaa;
            pointer-events: none;
            transition: all 0.2s ease;
        }
        button {
            width: 100%;
            padding: 15px;
            background: #253342;
            color: #fff;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            cursor: pointer;
            transition: background 0.3s;
        }
        button:hover {
            background: #1f2b34;
        }
        .helper-text {
            text-align: center;
            font-size: 14px;
            margin-top: 15px;
        }
        .helper-text a {
            color: #0091ae;
            text-decoration: none;
        }
        .helper-text a:hover {
            text-decoration: underline;
        }
        .error-message {
            background: #ffdddd;
            border: 1px solid #ffa5a5;
            color: #cc0000;
            padding: 10px;
            text-align: center;
            border-radius: 5px;
            margin-bottom: 15px;
            font-size: 14px;
        }
        /* Responsive cho màn hình nhỏ */
        @media (max-width: 768px) {
            .container {
                flex-direction: column;
            }
            .left-panel, .right-panel {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Phần bên trái: thông tin giới thiệu -->
        <div class="left-panel">
            <h1>PNT website</h1>
            <h2>The future of content</h2>
            <p>Nơi bạn thỏa sức đam mê viết lách, nơi cập nhật những tin tức hot nhất.</p>
            <p>Hãy gia nhập và trở thành một thành viên của PNT!</p>
        </div>
        <!-- Phần bên phải: form đăng ký -->
        <div class="right-panel">
            <h2>Đăng ký Cộng tác viên</h2>
            <?php
            if (isset($_SESSION['error'])) {
                echo '<div class="error-message">' . $_SESSION['error'] . '</div>';
                unset($_SESSION['error']);
            }
            ?>
            <form action="register.php" method="post">
                <div class="form-group">
                    <input type="text" id="fullname" name="fullname" placeholder=" " required>
                    <label for="fullname">Họ tên</label>
                </div>
                <div class="form-group">
                    <input type="text" id="username" name="username" placeholder=" " required>
                    <label for="username">Tên đăng nhập</label>
                </div>
                <div class="form-group">
                    <input type="password" id="password" name="password" placeholder=" " required minlength="6">
                    <label for="password">Mật khẩu (tối thiểu 6 ký tự)</label>
                </div>
                <div class="form-group">
                    <input type="email" id="email" name="email" placeholder=" " required>
                    <label for="email">Email</label>
                </div>
                <div class="form-group">
                    <input type="text" id="phone" name="phone" placeholder=" " required>
                    <label for="phone">Số điện thoại</label>
                </div>
                <button type="submit">Gửi yêu cầu duyệt</button>
            </form>
            <div class="helper-text">
                Đã có tài khoản? <a href="login.php">Đăng nhập</a>
            </div>
        </div>
    </div>
    <?php
    if (isset($_SESSION['success'])) {
        echo "<script>alert('" . $_SESSION['success'] . "');</script>";
        unset($_SESSION['success']);
    }
    ?>
</body>
</html>
