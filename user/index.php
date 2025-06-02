<?php
session_start();

// Nếu chưa đăng nhập thì chuyển hướng
if (!isset($_SESSION['user'])) {
    echo "<script>alert('Bạn chưa đăng nhập!'); window.location.href = '/auth/login.php';</script>";
    exit;
}

require '../config/db.php';

$user_id = $_SESSION['user']['id'] ?? null;
$category_id=$_GET['category_id'] ?? null;

$search = trim($_GET['search'] ?? '');
// Xử lý yêu cầu xóa qua POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_post'])) {
    $id = intval($_POST['delete_id']);

    // Chỉ xóa nếu bài viết thuộc về người dùng hiện tại
    $check = $pdo->prepare("SELECT id FROM posts WHERE id = ? AND user_id = ?");
    $check->execute([$id, $user_id]);

    if ($check->fetch()) {
        $deleteStmt = $pdo->prepare("DELETE FROM posts WHERE id = ?");
        $deleteStmt->execute([$id]);

        // Tránh resubmit form sau khi xóa
        header("Location: index.php");
        exit;
    } else {
        echo "<script>alert('Bạn không có quyền xóa bài viết này.');</script>";
    }
}


if ($category_id && $search) {
    $stmt = $pdo->prepare("SELECT posts.*, categories.name AS category_name 
        FROM posts 
        JOIN categories ON posts.category_id = categories.id 
        WHERE posts.user_id = ? 
        AND posts.category_id = ?
        AND (posts.title LIKE ? OR posts.keywords LIKE ?)
        ORDER BY posts.created_at DESC");
    $stmt->execute([$user_id, $category_id, "%$search%", "%$search%"]);
} elseif ($category_id) {
    $stmt = $pdo->prepare("SELECT posts.*, categories.name AS category_name 
        FROM posts 
        JOIN categories ON posts.category_id = categories.id 
        WHERE posts.user_id = ? 
        AND posts.category_id = ?
        ORDER BY posts.created_at DESC");
    $stmt->execute([$user_id, $category_id]);
} elseif ($search) {
    $stmt = $pdo->prepare("SELECT posts.*, categories.name AS category_name 
        FROM posts 
        JOIN categories ON posts.category_id = categories.id 
        WHERE posts.user_id = ?
        AND (posts.title LIKE ? OR posts.keywords LIKE ?)
        ORDER BY posts.created_at DESC");
    $stmt->execute([$user_id, "%$search%", "%$search%"]);
} else {
    $stmt = $pdo->prepare("SELECT posts.*, categories.name AS category_name 
        FROM posts 
        JOIN categories ON posts.category_id = categories.id 
        WHERE posts.user_id = ? 
        ORDER BY posts.created_at DESC");
    $stmt->execute([$user_id]);
}
$posts = $stmt->fetchAll();

include('header.php');
?>
 
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <title>Trang cá nhân</title>
  <link rel="stylesheet" href="create.css"> 
  <link rel="stylesheet" href="../includes/style.css?v=<?= time(); ?>">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <style>
    .container {
      max-width: 1250px;
      margin: 0 auto;
      padding: 20px;
      box-sizing: border-box;
    }

    .post-card {
      display: flex;
      background-color: #fff;
      border: 1px solid #dee2e6;
      border-radius: 8px;
      padding: 15px;
      margin-bottom: 15px;
      box-shadow: 0 2px 4px rgba(0,0,0,0.05);
      transition: box-shadow 0.3s ease;
    }

    .post-card:hover {
      box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    }

    .thumbnail {
      flex: 0 0 150px;
      height: 100px;
      margin-right: 20px;
    }

    .thumbnail img {
      width: 150px;
      height: 100px;
      object-fit: cover;
      border-radius: 6px;
    }

    .post-content {
      flex-grow: 1;
    }

    .post-content h3 {
      margin-top: 0;
      font-size: 20px;
    }

    .post-content h3 a {
      color:#000000;
      text-decoration: none;
    }

    .post-content h3 a:hover {
      color: #0056b3;
    }

    .post-content p {
      margin: 8px 0 12px;
      color: #333;
      font-size: 13px;
    }
    .post-content small {
      font-size: 13px;
    }

    .post-actions {
      margin-top: 10px;
    }

    .post-actions .btn {
      padding: 6px 12px;
      border-radius: 4px;
      margin-right: 8px;
    }

    .add-post {
      flex-shrink: 0;
      width: 150px;
      display: inline-block;
      margin: 20px 0;
      padding: 8px 14px;
      font-size: 13px;
      background-color: #0091ae;
      color: white;
      text-decoration: none;
      border-radius: 25px;
      box-shadow: 0 2px 5px rgba(0, 123, 255, 0.3);
      transition: all 0.2s ease-in-out;
    }

    .add-post:hover {
      background-color: #1c627c;
      box-shadow: 0 4px 10px rgba(0, 86, 179, 0.4);
    }

    .btn {
      transition: all 0.2s ease-in-out;
      box-shadow: 0 2px 4px rgba(0,0,0,0.1);
      font-size: 13px;
    }

    .btn:hover {
      transform: scale(1.02);
    }
  </style>
</head>
<body>
  <div class="container">
      <a href="create.php" class="add-post">Thêm bài viết mới</a>
      <h2>Bài viết của tôi<?= $category_id ? ' - ' . htmlspecialchars($posts[0]['category_name'] ?? '') : '' ?></h2>
      <?php if (count($posts) === 0): ?>
          <p>Không có bài viết nào.</p>
      <?php else: ?>
          <div class="list-group">
              <?php foreach ($posts as $post): ?>
                  <div class="post-card">
                    <div class="thumbnail">
                      <?php if (!empty($post['thumbnail'])): ?>
                        <img src="../uploads/<?= htmlspecialchars($post['thumbnail']) ?>" alt="Thumbnail">
                      <?php else: ?>
                        <img src="../uploads/default.jpg" alt="No Thumbnail">
                      <?php endif; ?>
                    </div>
                    <div class="post-content">
                      <h3><a href="/post.php?slug=<?= htmlspecialchars($post['slug']) ?>" target="_blank"><?= htmlspecialchars($post['title']) ?></a></h3>
                      <p><?= mb_substr(html_entity_decode(strip_tags($post['description'] ?? $post['content'])), 0, 150) ?>...</p>
                      <small>Đăng ngày <?= date('d/m/Y H:i', strtotime($post['created_at'] ?? $post['updated_at'])) ?> | Danh mục: <?= htmlspecialchars($post['category_name']) ?> | Lượt xem: <?= $post['views'] ?></small>
                      <div class="post-actions d-flex gap-2 mt-3">
                        <a href="edit_post.php?id=<?= $post['id'] ?>" class="btn text-white" style="background-color: #007bff;">
                          <i class="fas fa-edit me-1"></i> Chỉnh sửa
                        </a>
                        <form method="post" action="index.php" style="display: inline;" onsubmit="return confirm('Bạn có chắc muốn xóa?');">
                            <input type="hidden" name="delete_post" value="1">
                            <input type="hidden" name="delete_id" value="<?= $post['id'] ?>">
                            <button type="submit" class="btn text-white" style="background-color: #ff4d4d; border: none;">
                                <i class="fas fa-trash-alt me-1"></i> Xóa
                            </button>
                        </form>
                      </div>
                    </div>
                  </div>
              <?php endforeach; ?>
          </div>
      <?php endif; ?>
  </div>
</body>
</html>
<?php include('../includes/footer.php'); ?>