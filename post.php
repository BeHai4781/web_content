<?php
require 'config/db.php';
if (session_status() == PHP_SESSION_NONE) session_start();

// Lấy slug từ URL
$slug = $_GET['slug'] ?? '';
if (!$slug) {
  http_response_code(400);
  echo "Thiếu đường dẫn bài viết.";
  exit;
}

// Lấy thông tin bài viết
$stmt = $pdo->prepare("SELECT posts.*, users.fullname FROM posts JOIN users ON posts.user_id = users.id WHERE slug = ?");
$stmt->execute([$slug]);
$post = $stmt->fetch();

if (!$post) {
  http_response_code(404);
  echo "Bài viết không tồn tại.";
  exit;
}

$postId = $post['id'];
try {
  // Đảm bảo views luôn là số, nếu NULL thì set về 0 trước khi tăng
  $updateStmt = $pdo->prepare("
        UPDATE posts 
        SET views = COALESCE(views, 0) + 1 
        WHERE id = ?
    ");
  $updateStmt->execute([$postId]);
} catch (PDOException $e) {
  error_log("Error updating views: " . $e->getMessage());
}

//TRUY VẤN BÀI VIẾT LIÊN QUAN (DỰA TRÊN KEYWORDS) 
$current_keywords = array_filter(array_map('trim', explode(',', $post['keywords'] ?? '')));
$keyword_conditions = [];
$params = [];

foreach ($current_keywords as $keyword) {
  $keyword_conditions[] = "keywords LIKE ?";
  $params[] = "%$keyword%";
}

$relatedPosts = [];
$totalPages = 1;
$currentPage = isset($_GET['page_related']) ? max(1, (int)$_GET['page_related']) : 1;
$relatedPerPage = 3;
$offset = ($currentPage - 1) * $relatedPerPage;

if (!empty($keyword_conditions)) {
  // Tổng số bài viết liên quan
  $countSql = "SELECT COUNT(*) FROM posts WHERE id != ? AND (" . implode(' OR ', $keyword_conditions) . ")";
  $countStmt = $pdo->prepare($countSql);
  $countStmt->execute([$post['id'], ...$params]);
  $totalRelated = $countStmt->fetchColumn();
  $totalPages = max(1, ceil($totalRelated / $relatedPerPage));

  // Truy vấn bài viết liên quan
  $sql = "SELECT id, title, slug, thumbnail, description 
            FROM posts 
            WHERE id != ? AND (" . implode(' OR ', $keyword_conditions) . ") 
            ORDER BY updated_at DESC 
            LIMIT $offset, $relatedPerPage";
  $stmt = $pdo->prepare($sql);
  $stmt->execute([$post['id'], ...$params]);
  $relatedPosts = $stmt->fetchAll();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['comment'])) {
    // Lấy dữ liệu từ form
    $post_id = $_POST['post_id'] ?? null;
    $slug    = $_POST['slug'] ?? null;
    $email   = filter_var(trim($_POST['email']), FILTER_VALIDATE_EMAIL);
    $comment = trim($_POST['comment']);

    // Kiểm tra dữ liệu đầu vào
    if (!$post_id || !$email || empty($comment) || !$slug) {
        $_SESSION['error'] = "Thông tin không hợp lệ, vui lòng kiểm tra lại.";
        header("Location: post.php?slug=" . urlencode($slug));
        exit;
    }

    try {
        // Chèn dữ liệu bình luận vào bảng ratings
        $stmt = $pdo->prepare("INSERT INTO ratings (post_id, email_rate, comment, type, submitted_at, is_read)
                               VALUES (?, ?, ?, 'user_rate', NOW(), 0)");
        $stmt->execute([$post_id, $email, $comment]);

        $_SESSION['success'] = "Bình luận đã được gửi thành công!";
    } catch (PDOException $e) {
        $_SESSION['error'] = "Có lỗi xảy ra, vui lòng thử lại.";
    }

    // Chuyển hướng về lại bài viết sau khi xử lý thành công hoặc thất bại
    header("Location: post.php?slug=" . urlencode($slug));
    exit;
}

$post_id = $post['id']; 
$comments = [];

$stmt = $pdo->prepare("SELECT email_rate, comment, submitted_at FROM ratings WHERE post_id = ? AND type = 'user_rate' ORDER BY submitted_at DESC");
$stmt->execute([$post_id]);
$comments = $stmt->fetchAll(PDO::FETCH_ASSOC);
$totalComments = count($comments);

//xac dinh so luong binh luan tren moi trang
$commentsPerPage = 5;
$currentPage = isset($_GET['page_comments']) ? max(1, (int)$_GET['page_comments']) : 1;
$offset = ($currentPage - 1) * $commentsPerPage;
//tinh tong so binh luan va tong so trang
$countStmt = $pdo->prepare("SELECT COUNT(*) FROM ratings WHERE post_id = ? AND type = 'user_rate'");
$countStmt->execute([$post_id]);
$totalComments = $countStmt->fetchColumn();
$totalPages = max(1, ceil($totalComments / $commentsPerPage));
//truy van danh sach binh luan voi phan trang
$sql = "SELECT email_rate, comment, submitted_at 
        FROM ratings 
        WHERE post_id = ? AND type = 'user_rate' 
        ORDER BY submitted_at DESC 
        LIMIT " . intval($commentsPerPage) . " OFFSET " . intval($offset);

$stmt = $pdo->prepare($sql);
$stmt->execute([$post_id]);
$comments = $stmt->fetchAll(PDO::FETCH_ASSOC);
include 'includes/header.php';
?>

  <style>
    bbody {
    font-family: 'Merriweather', serif;
    background-color: #fdfaff;
    margin: 0;
    padding: 0;
}

.container {
    max-width: 1000px;
    margin: 40px auto;
    padding: 0 20px;
}

h1 {
    font-size: 60px; 
    font-weight: 700;
    color: #1a1a1a;
    margin-bottom: 25px;
    line-height: 1.3;
    letter-spacing: -0.5px;
}

.content h1 {
    font-size: 36px; 
    margin: 40px 0 25px;
}

/* Style cho phần meta */
.meta {
    color: #666;
    font-size: 14px;
    margin-bottom: 30px;
    padding-bottom: 20px;
    border-bottom: 1px solid #eee;
}

.meta strong {
    color: #333;
    font-weight: 600;
}

/* Style cho nội dung bài viết */
.content {
    line-height: 1.8;
    font-size: 17px;
    color: #2c3e50;
    margin-bottom: 40px;
}

.content p {
    margin-bottom: 20px;
}

.content img {
    max-width: 100%;
    height: auto;
    border-radius: 8px;
    margin: 25px 0;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.content h2 {
    font-size: 26px;
    color: #2c3e50;
    margin: 35px 0 20px;
    font-weight: 600;
}

.content h3 {
    font-size: 22px;
    color: #34495e;
    margin: 30px 0 18px;
    font-weight: 600;
}

@media (max-width: 768px) {
    h1 {
        font-size: 28px;
    }

    .content {
        font-size: 16px;
    }
}
    h1 {
      font-size: 28px;
      margin-bottom: 10px;
    }

    .related-posts {
      margin-top: 50px;
      padding: 20px;
      background-color: #f7f4ed;
      border-top: 1px solid #ddd;
    }

    .related-posts h3 {
      color: #5a32a3;
      margin-bottom: 20px;
    }

    .related-item {
      display: flex;
      gap: 15px;
      margin-bottom: 20px;
      border-bottom: 1px solid #ddd;
      padding-bottom: 10px;
    }

    .related-item img {
      width: 120px;
      height: 80px;
      object-fit: cover;
      border-radius: 5px;
      margin-right: 15px;
    }

    .related-item .related-info {
      flex: 1;
    }

    .related-item h4 {
      font-size: 16px;
      margin: 0 0 6px;
      color: #000;
    }

    .related-item p {
      font-size: 14px;
      color: #444;
      margin: 0;
    }

    .related-item a {
      display: flex;
      text-decoration: none;
    }

/* Email */
.form-group {
  margin-bottom: 15px;
}

.form-group label {
  display: block;
  margin-bottom: 8px;
  color: #555;
}

/* Style chung cho cả input email và textarea */
.form-group input[type="email"],
textarea {
  width: 100%;
  padding: 8px 12px;
  border: 1px solid #ddd;
  border-radius: 4px;
  font-size: 14px;
  color: #333;
  background: #fff;
}

/* Focus style đồng bộ */
.form-group input[type="email"]:focus,
textarea:focus {
  outline: none;
  border-color: #0066cc;
  box-shadow: 0 0 0 1px #0066cc;
}

/* Placeholder style đồng bộ */
.form-group input[type="email"]::placeholder,
textarea::placeholder {
  color: #999;
}

/* Button style giữ nguyên */
.btn-primary {
  background: #0066cc;
  color: white;
  border: none;
  padding: 8px 20px;
  border-radius: 4px;
  cursor: pointer;
}
    /* Phần bình luận */
.post-comments {
  margin-top: 30px;
}

.post-comments h3 {
  margin-bottom: 20px;
  font-size: 18px;
}

.post-comments form {
  margin-bottom: 20px;
}

.post-comments textarea {
  width: 100%;
  min-height: 100px;
  padding: 10px;
  border: 1px solid #ddd;
  border-radius: 4px;
  margin-bottom: 10px;
  font-size: 14px;
}

.post-comments .btn-primary {
  background: #0066cc;
  color: white;
  border: none;
  padding: 8px 20px;
  border-radius: 4px;
  cursor: pointer;
}

.post-comments p {
  color: #666;
  font-style: italic;
  text-align: center;
  padding: 20px 0;
}
/* Phần hiển thị danh sách bình luận */
.comments-list {
  max-width: 700px;
  margin: 10px auto;
  padding: 0;
  list-style: none;
}

.comment-item {
  border-left: 2px solid #333;
  padding-left: 10px;
  margin-bottom: 10px;
}

.comment-item p {
  margin: 5px 0;
  font-size: 15px;
  color: #222;
  text-align: left;
}

.comment-item strong {
  font-weight: bold;
}

.comment-date {
  font-size: 13px;
  color: #666;
}
  </style>

  <div class="container">
    <h1><?= htmlspecialchars($post['title']) ?></h1>

    <div class="meta">
      Đăng bởi <strong><?= htmlspecialchars($post['fullname']) ?></strong>
      vào lúc
      <?= date('d/m/Y H:i', strtotime($post['updated_at'] ?? $post['created_at'])) ?>
    </div>
    <br><br>
    <div class="content">
      <?= $post['content'] ?>
    </div>
    
    <div class="related-posts">
      <h3>Tin liên quan</h3>
      <?php if (!empty($relatedPosts)): ?>
        <?php foreach ($relatedPosts as $r): ?>
          <div class="related-item">
            <a href="/post.php?slug=<?= htmlspecialchars($r['slug']) ?>" target="_blank">
              <img src="/uploads/<?= htmlspecialchars($r['thumbnail']) ?>" alt="thumbnail">
              <div class="related-info">
                <h4><?= htmlspecialchars($r['title']) ?></h4>
                <p><?= htmlspecialchars(mb_strimwidth($r['description'], 0, 150, '...')) ?></p>
              </div>
            </a>
          </div>
        <?php endforeach; ?>
        <!-- Phân trang -->
        <?php if ($totalPages > 1): ?>
          <div style="text-align: center; margin-top: 15px;">
            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
              <a href="?slug=<?= urlencode($slug) ?>&page_related=<?= $i ?>"
                style="margin: 0 5px; padding: 6px 10px; border-radius: 4px; background: <?= $i == $currentPage ? '#0056b3' : '#ccc' ?>; color: #fff; text-decoration: none;">
                <?= $i ?>
              </a>
            <?php endfor; ?>
          </div>
        <?php endif; ?>
      <?php else: ?>
        <p>Không có bài viết liên quan.</p>
      <?php endif; ?>
    </div>
  
<br><br> 

    <div class="post-comments">

      <form method="post" action="/post.php?slug=<?= htmlspecialchars($post['slug']) ?>" id="comment-form">
        <div class="form-group">
          <label for="email">Email của bạn:</label>
          <input type="email"
                id="email"
                name="email" 
                placeholder="example@domain.com"
                required>
        </div>

        <input type="hidden" name="post_id" value="<?= $post['id'] ?>">
        <input type="hidden" name="slug" value="<?= htmlspecialchars($post['slug']) ?>">
        
        <textarea name="comment"
                  placeholder="Bạn nghĩ gì về bài viết này?"
                  required></textarea>
        <button type="submit" class="btn btn-primary">Gửi bình luận</button>
      </form>


      <?php if (empty($comments)): ?>
        <p>Hiện chưa có bình luận nào, hãy trở thành người đầu tiên bình luận cho bài viết!</p>
      <?php endif; ?>

      <h3>Bình luận (<?= $totalComments ?? 0 ?>)</h3>
      <div class="comments-list">
        <?php foreach ($comments as $comment): ?>
          <div class="comment-item">
            <p>Đăng bởi <strong><?= htmlspecialchars($comment['email_rate']) ?></strong>:</p>
            <p class="comment-text"><?= nl2br(htmlspecialchars($comment['comment'])) ?></p>
            <span class="comment-date"><?= date('d/m/Y H:i', strtotime($comment['submitted_at'])) ?></span>
          </div>
        <?php endforeach; ?>
      </div>
      <?php if ($totalPages > 1): ?>
        <div style="text-align: center; margin-top: 15px;">
          <?php for ($i = 1; $i <= $totalPages; $i++): ?>
            <a href="?slug=<?= urlencode($slug) ?>&page_comments=<?= $i ?>"
              style="margin: 0 5px; padding: 6px 10px; border-radius: 4px; background: <?= $i == $currentPage ? '#0056b3' : '#ccc' ?>; color: #fff; text-decoration: none;">
              <?= $i ?>
            </a>
          <?php endfor; ?>
        </div>
      <?php endif; ?>
    </div>
  </div>
<?php include('includes/footer.php'); ?>