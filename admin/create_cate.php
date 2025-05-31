<?php
require '../config/db.php';
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
include '../includes/header_admin.php';
// Nếu chưa đăng nhập thì chuyển hướng
if (!isset($_SESSION['user'])) {
    echo "<script>alert('Bạn chưa đăng nhập!'); window.location.href = '/auth/login.php';</script>";
    exit;
}
// Chỉ cho admin
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header("Location: ../index.php");
    exit;
}

$errors = [];
$success = "";

// Xử lý thêm danh mục
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);

    if (empty($name)) {
        $errors[] = "Tên danh mục không được để trống.";
    } else {
        $check = $pdo->prepare("SELECT id FROM categories WHERE name = ?");
        $check->execute([$name]);
        if ($check->fetch()) {
            $errors[] = "Danh mục đã tồn tại.";
        }
    }

    if (empty($errors)) {
        $stmt = $pdo->prepare("INSERT INTO categories (name) VALUES (?)");
        $stmt->execute([$name]);
        $success = "Thêm danh mục thành công.";
    }
}

// Xử lý xoá danh mục
if (isset($_GET['delete'])) {
    $catId = (int) $_GET['delete'];

    $check = $pdo->prepare("SELECT COUNT(*) FROM posts WHERE category_id = ?");
    $check->execute([$catId]);
    $count = $check->fetchColumn();

    if ($count > 0) {
        $errors[] = "Không thể xoá danh mục có bài viết.";
    } else {
        $del = $pdo->prepare("DELETE FROM categories WHERE id = ?");
        $del->execute([$catId]);
        $success = "Xoá danh mục thành công.";
        header("Location: ../admin/create_cate.php");
        exit;
    }
}

// Lấy danh sách danh mục và số lượng bài viết mỗi danh mục
$categories = $pdo->query("SELECT c.id, c.name, COUNT(p.id) AS post_count
                           FROM categories c
                           LEFT JOIN posts p ON c.id = p.category_id
                           GROUP BY c.id, c.name
                           ORDER BY c.id DESC")->fetchAll();
?>

<div class="container mt-4">
    <h1>Quản lý danh mục</h1>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <?php foreach ($errors as $e): ?>
                <div><?= htmlspecialchars($e) ?></div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="alert alert-success"><?= $success ?></div>
    <?php endif; ?>

    <form method="post" class="mb-4">
        <div class="row g-2">
            <div class="col-md-6">
                <input type="text" name="name" class="form-control" placeholder="Tên danh mục mới" required>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-secondary w-50">Thêm</button>
            </div>
        </div>
    </form>

    <table class="table table-bordered">
        <thead>
            <tr>
                <th>STT</th>
                <th>Tên danh mục</th>
                <th>Số bài viết</th>
                <th>Hành động</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($categories as $cat): ?>
            <tr>
                <td><?= $cat['id'] ?></td>
                <td><?= htmlspecialchars($cat['name']) ?></td>
                <td><?= $cat['post_count'] ?></td>
                <td>
                    <a href="?delete=<?= $cat['id'] ?>"
                       onclick="return confirm('Bạn có chắc chắn muốn xoá danh mục này?')"
                       class="btn btn-danger btn-sm">Xoá</a>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <a href="../admin/approve_cate.php" class="btn btn-primary mt-3">← Quay lại</a>
</div>

<?php include '../includes/footer.php'; ?>
