<?php
session_start();
require '../config/db.php';

if (!isset($_SESSION['user'])) {
    header('Location: /auth/login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'] ?? null;
    $title = $_POST['title'] ?? '';
    $slug = $_POST['slug'] ?? '';
    $content = $_POST['content'] ?? '';
    $category_id = $_POST['category'] ?? null;
    $keywords = $_POST['keywords'] ?? '';
    $excerpt = $_POST['excerpt'] ?? '';
    $user_id = $_SESSION['user']['id'];

    // Lấy dữ liệu bài viết hiện tại
    $stmt = $pdo->prepare("SELECT title, slug, thumbnail FROM posts WHERE id = ? AND user_id = ?");
    $stmt->execute([$id, $user_id]);
    $existingPost = $stmt->fetch();
    if (!$existingPost) {
        die("Bài viết không tồn tại hoặc bạn không có quyền chỉnh sửa.");
    }

    // Giữ nguyên slug nếu tiêu đề không thay đổi
    if (trim($title) === trim($existingPost['title'])) {
        $slug = $existingPost['slug'];
    }

    $thumbnail = $existingPost['thumbnail'];

    // Nếu có ảnh mới
    if (isset($_FILES['thumbnail']) && $_FILES['thumbnail']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = '../uploads/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        $fileName = time() . '_' . basename($_FILES['thumbnail']['name']);
        $uploadPath = $uploadDir . $fileName;
        if (move_uploaded_file($_FILES['thumbnail']['tmp_name'], $uploadPath)) {
            $thumbnail = $fileName;
        }
    }

    $stmt = $pdo->prepare("UPDATE posts SET title = ?, slug = ?, content = ?, category_id = ?, keywords = ?, description = ?, thumbnail = ?, updated_at = NOW() WHERE id = ? AND user_id = ?");
    $stmt->execute([$title, $slug, $content, $category_id, $keywords, $excerpt, $thumbnail, $id, $user_id]);

    header("Location: /post.php?slug=$slug");
    exit;
}
?>
