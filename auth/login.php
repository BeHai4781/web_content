<?php
session_start();
require '../config/db.php';
require '../config/mailer.php';

$action = $_GET['action'] ?? '';

if ($action === 'forgot') {
    // Xử lý quên mật khẩu
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $username = trim($_POST['username'] ?? '');
        $email = trim($_POST['email'] ?? '');

        if (empty($username) || empty($email)) {
            $error = "Vui lòng nhập tên đăng nhập và email.";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = "Email không hợp lệ!";
        } else {
            // Tìm người dùng theo cả tên đăng nhập và email
            $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? AND email = ?");
            $stmt->execute([$username, $email]);
            $user = $stmt->fetch();
            
            if (!$user) {
                $error = "Tên đăng nhập và email không tồn tại trong hệ thống.";
            } else {
                // Sinh mật khẩu tạm thời (8 ký tự)
                $temp_password = substr(str_shuffle("0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ"), 0, 8);
                $hashed_temp = password_hash($temp_password, PASSWORD_DEFAULT);
                
                // Cập nhật mật khẩu mới và đặt is_first_login = 1
                $stmt = $pdo->prepare("UPDATE users SET password = ?, is_first_login = 1 WHERE id = ?");
                $stmt->execute([$hashed_temp, $user['id']]);
                
                try {
                    $mail = getMailer();
                    $mail->CharSet = 'UTF-8';
                    $mail->isHTML(true);
                    $mail->addAddress($user['email'], $user['username']);
                    $mail->Subject = "Mật khẩu tạm thời của bạn";
                    $mail->Body = "Chào {$user['username']},\n\nMật khẩu tạm thời của bạn là: {$temp_password}\nVui lòng sử dụng mật khẩu này để đăng nhập và tiến hành đặt lại mật khẩu.\n\nTrân trọng,\nBan quản trị PNT group";
                    $mail->send();
                    
                    $_SESSION['success'] = "Mật khẩu tạm thời đã được gửi đến email của bạn.";
                    header("Location: ../auth/login.php");
                    exit();
                } catch (Exception $e) {
                    $error = "Không thể gửi email. Lỗi: " . $mail->ErrorInfo;
                }
            }
        }
    }
} else {
    // Xử lý đăng nhập
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $username = trim($_POST['username']);
        $password = $_POST['password'];
        $role     = $_POST['role'];
    
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? AND role = ? AND status = 'active'");
        $stmt->execute([$username, $role]);
        $user = $stmt->fetch();
    
        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user'] = $user;
    
            // Nếu là lần đăng nhập đầu tiên, chuyển về trang đặt lại mật khẩu
            if ($user['is_first_login']) {
                $_SESSION['user_id'] = $user['id']; 
                header("Location: ../auth/change_password.php?first=1");
                exit();
            }
    
            // Chuyển hướng theo vai trò
            if ($user['role'] === 'admin') {
                header("Location: ../admin/index.php");
            } elseif ($user['role'] === 'user') {
                header("Location: ../user/index.php");
            } else {
                header("Location: ../index.php");
            }
            exit();
        } else {
            $error = "Sai thông tin đăng nhập, vai trò hoặc tài khoản chưa được duyệt.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Đăng nhập - PNT website</title>
  <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500&display=swap" rel="stylesheet">
  <style>
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
    .container {
      background: #fff;
      width: 800px;
      max-width: 100%;
      display: flex;
      border-radius: 12px;
      overflow: hidden;
      box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
    }
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
      font-size: 22px;
      margin-bottom: 20px;
      font-weight: 300;
    }
    .left-panel p {
      font-size: 16px;
      line-height: 1.6;
      margin-bottom: 15px;
    }
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
    .form-group input,
    .form-group select {
      width: 100%;
      padding: 15px;
      border: 1px solid #ddd;
      border-radius: 5px;
      font-size: 16px;
      outline: none;
      transition: border 0.2s ease;
    }
    .form-group input:focus,
    .form-group select:focus {
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
    @media (max-width: 768px) {
      .container { flex-direction: column; }
      .left-panel, .right-panel { width: 100%; }
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
    <!-- Phần bên phải: hiển thị form đăng nhập hoặc quên mật khẩu -->
    <div class="right-panel">
      <?php if ($action === 'forgot'): ?>
        <h2>Quên mật khẩu</h2>
        <?php if (!empty($error)) echo "<div class='error-message'>$error</div>"; ?>
        <form method="post" action="login.php?action=forgot">
          <div class="form-group">
            <input type="text" id="username" name="username" placeholder=" " required>
            <label for="username">Tên đăng nhập</label>
          </div>
          <div class="form-group">
            <input type="email" id="email" name="email" placeholder=" " required>
            <label for="email">Email</label>
          </div>
          <button type="submit">Gửi mật khẩu tạm thời</button>
        </form>
        <div class="helper-text">
          <a href="login.php">Quay lại đăng nhập</a>
        </div>
      <?php else: ?>
        <h2>Đăng nhập</h2>
        <?php if (!empty($error)) echo "<div class='error-message'>$error</div>"; ?>
        <form method="post" action="login.php">
          <div class="form-group">
            <input type="text" id="username" name="username" placeholder=" " required>
            <label for="username">Tên đăng nhập</label>
          </div>
          <div class="form-group">
            <input type="password" id="password" name="password" placeholder=" " required>
            <label for="password">Mật khẩu</label>
          </div>
          <div class="form-group">
            <select name="role" required>
              <option value="user">Cộng tác viên</option>
              <option value="admin">Quản trị viên</option>
            </select>
          </div>
          <button type="submit">Đăng nhập</button>
        </form>
        <div class="helper-text">
          <a href="login.php?action=forgot">Quên mật khẩu?</a><br>
          Chưa có tài khoản? <a href="register.php">Đăng ký</a>
        </div>
      <?php endif; ?>
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
