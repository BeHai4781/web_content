<?php
session_start();
require '../config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $_POST['title'] ?? '';
    $slug = $_POST['slug'] ?? '';
    $content = $_POST['content'] ?? '';
    $user_id = $_SESSION['user']['id'] ?? null;
    $created_at = date('Y-m-d H:i:s');
    $category_id = $_POST['category'] ?? null;
    $keywords = $_POST['keywords'] ?? '';
    $description = $_POST['excerpt'] ?? '';

    // Xử lý thumbnail
     $thumbnail = null;

    // Kiểm tra thumbnail
    if (isset($_FILES['thumbnail']) && $_FILES['thumbnail']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = '../uploads/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true); // Tạo thư mục nếu chưa có
        }

        $fileTmpPath = $_FILES['thumbnail']['tmp_name'];
        $originalName = basename($_FILES['thumbnail']['name']);
        $extension = pathinfo($originalName, PATHINFO_EXTENSION);
        $fileName = time() . '_' . bin2hex(random_bytes(4)) . '.' . $extension;
        $uploadPath = $uploadDir . $fileName;

        if (move_uploaded_file($fileTmpPath, $uploadPath)) {
            $thumbnail = $fileName; // Lưu tên file (không cần đường dẫn)
        }
    }

    // Kiểm tra slug rỗng
    if (empty($slug)) {
        die("Lỗi: Slug rỗng. Vui lòng tạo link trước khi lưu.");
    }

    // Kiểm tra slug đã tồn tại
    $checkSlug = $pdo->prepare("SELECT COUNT(*) FROM posts WHERE slug = ?");
    $checkSlug->execute([$slug]);
    if ($checkSlug->fetchColumn() > 0) {
        die("Lỗi: Slug đã tồn tại. Vui lòng đổi tiêu đề hoặc chỉnh sửa slug.");
    }
    try {
        // Thêm bài viết
        $stmt = $pdo->prepare("INSERT INTO posts (title, slug, content, user_id, created_at, category_id, keywords, thumbnail, description) 
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$title, $slug, $content, $user_id, $created_at, $category_id, $keywords, $thumbnail, $description]);

        // Lưu ID bài viết mới thêm (nếu cần sử dụng sau này)
        $post_id = $pdo->lastInsertId();
        
        // Kiểm tra nếu bai viet chua co thong bao thi them thong bao
        $check = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE type = 'new_post' AND post_id = ?");
        $check->execute([$post_id]);

        if ($check->fetchColumn() == 0) {
            $stmt = $pdo->prepare("INSERT INTO notifications (type, user_id, post_id, is_read, created_at)
                       VALUES ('new_post', ?, ?, 0, NOW())");
            $stmt->execute([$user_id, $post_id]);
        }
        header("Location: /post.php?slug=$slug");
        exit;
    } catch (PDOException $e) {
        $_SESSION['error'] = "Đã xảy ra lỗi trong quá trình đăng bài. Vui lòng thử lại sau.";
        header("Location: /post.php?slug=$slug");
        exit;
    }

}
?>
