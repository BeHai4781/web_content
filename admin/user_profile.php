<?php
require '../config/db.php';
session_start();

$action = $_GET['action'] ?? null;
$id = $_GET['id'] ?? null;

if (!$action || !$id) {
    echo "Thiếu thông tin yêu cầu.";
    exit();
}

// Lấy thông tin user để hiển thị
if ($action === 'edit') {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$id]);
    $user = $stmt->fetch();

    if (!$user) {
        echo "Không tìm thấy người dùng.";
        exit();
    }
}

// Cập nhật thông tin khi gửi form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'];
    $fullname = $_POST['fullname'];
    $username = $_POST['username'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $new_password = $_POST['new_password'];

    if (!empty($new_password)) {
        $hashedPassword = password_hash($new_password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE users SET fullname = ?, username = ?, email = ?, phone = ?, password = ?, created_at = NOW() WHERE id = ?");
        $stmt->execute([$fullname, $username, $email, $phone, $hashedPassword, $id]);
    } else {
        $stmt = $pdo->prepare("UPDATE users SET fullname = ?, username = ?, email = ?, phone = ?, created_at = NOW() WHERE id = ?");
        $stmt->execute([$fullname, $username, $email, $phone, $id]);
    }

    header("Location: ../admin/approve_user.php?duyet=true");
    exit();
}

require '../includes/header_admin.php';
?>

<?php if ($action === 'edit' && $user): ?>
<div class="container d-flex justify-content-center align-items-center" style="min-height: 100vh;">
    <div class="card shadow p-4" style="width: 100%; max-width: 500px;">
        <h4 class="mb-4 text-center">Cập nhật người dùng</h4>
        <form method="POST">
            <input type="hidden" name="id" value="<?php echo $user['id']; ?>">

            <div class="mb-3">
                <label class="form-label">Họ tên</label>
                <input type="text" name="fullname" class="form-control" value="<?php echo htmlspecialchars($user['fullname']); ?>">
            </div>

            <div class="mb-3">
                <label class="form-label">Tên đăng nhập</label>
                <input type="text" name="username" class="form-control" value="<?php echo htmlspecialchars($user['username']); ?>">
            </div>

            <div class="mb-3">
                <label class="form-label">Email</label>
                <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($user['email']); ?>">
            </div>

            <div class="mb-3">
                <label class="form-label">Số điện thoại</label>
                <input type="text" name="phone" class="form-control" value="<?php echo htmlspecialchars($user['phone']); ?>">
            </div>

            <div class="mb-3">
                <label class="form-label">Mật khẩu mới</label>
                <input type="password" name="new_password" class="form-control" placeholder="Để trống nếu không đổi">
            </div>

            <div class="mb-3">
                <label class="form-label">Ngày tạo</label>
                <div class="form-control-plaintext">
                    <?php echo !empty($user['created_at']) ? htmlspecialchars($user['created_at']) : ''; ?>
                </div>
            </div>

            <div class="d-flex justify-content-between">
                <div>
                    <button type="submit" name="update" class="btn btn-secondary">Cập nhật</button>
                    <a href="../admin/approve_user.php?duyet=true" class="btn btn-secondary ms-2">Quay lại</a>
                </div>
                <a href="../admin/approve_user.php?action=delete&id=<?php echo $user['id']; ?>" 
                onclick="return confirm('Bạn có chắc chắn muốn xóa người dùng này?');" 
                class="btn btn-danger">Xóa</a>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<?php require '../includes/footer.php'; ?>
