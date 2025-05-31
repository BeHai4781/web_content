<?php
session_start();
require 'config/db.php';

$search = trim($_GET['search'] ?? '');
$categoryId = $_GET['category_id'] ?? null;

$searchResults = [];
$latestPosts = [];
$popularPosts = [];
$recentPosts = [];

if ($search !== '') {
    // Ưu tiên tìm kiếm
    $stmt = $pdo->prepare("
        SELECT posts.*, users.username as author
        FROM posts
        JOIN users ON posts.user_id = users.id
        WHERE posts.title LIKE ? OR posts.keywords LIKE ?
        ORDER BY posts.created_at DESC
    ");
    $stmt->execute(["%$search%", "%$search%"]);
    $searchResults = $stmt->fetchAll();
} elseif ($categoryId) {
    // lọc theo danh mục
    $stmt = $pdo->prepare("
        SELECT posts.*, users.username as author
        FROM posts
        JOIN users ON posts.user_id = users.id
        WHERE posts.category_id = ?
        ORDER BY posts.created_at DESC
    ");
    $stmt->execute([$categoryId]);
    $latestPosts = $stmt->fetchAll();
} else {
    // Mặc định
    $latestPosts = $pdo->query("
        SELECT posts.*, users.username as author
        FROM posts
        JOIN users ON posts.user_id = users.id
        WHERE posts.created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
        ORDER BY RAND()
        LIMIT 20 
    ")->fetchAll();
}

$popularPosts = $pdo->query("
    SELECT posts.*, users.username as author
    FROM posts
    JOIN users ON posts.user_id = users.id
    WHERE DATE(posts.created_at) = CURDATE()
      AND posts.views >= 30
    ORDER BY posts.views DESC
")->fetchAll();

$recentPosts = $pdo->query("
    SELECT posts.*, users.username as author
    FROM posts
    JOIN users ON posts.user_id = users.id
    WHERE DATE(posts.created_at) = CURDATE()
    ORDER BY posts.created_at DESC
    LIMIT 5
")->fetchAll();

// Kiểm tra request POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Lấy dữ liệu từ form
    $email = filter_var(trim($_POST['email']), FILTER_VALIDATE_EMAIL); 
    $comment = trim($_POST['comment']);

    // Kiểm tra dữ liệu nhập
    if (!$email) {
        $_SESSION['contact_error'] = "Email không hợp lệ.";
        header("Location: " . $_SERVER['HTTP_REFERER']);
        exit;
    }

    if (empty($comment)) {
        $_SESSION['contact_error'] = "Vui lòng nhập nội dung liên hệ.";
        header("Location: " . $_SERVER['HTTP_REFERER']);
        exit;
    }

    try {
        // Chèn dữ liệu vào bảng ratings
        $stmt = $pdo->prepare("INSERT INTO ratings (email_rate, type, comment, is_read, submitted_at) VALUES (?, 'user_contact', ?, 0, NOW())");
        $stmt->execute([$email, $comment]);

        $_SESSION['contact_success'] = "Liên hệ của bạn đã được gửi thành công!";
    } catch (PDOException $e) {
        $_SESSION['contact_error'] = "Đã xảy ra lỗi, vui lòng thử lại sau.";
    }

    header("Location: " . $_SERVER['HTTP_REFERER']);
    exit;
}

include 'includes/header.php';
?>

<div class="container mt-4">
    <div class="row">
         <!-- MẢNG TRÁI -->
        <div class="col-md-9">
            <?php if (!empty($search)): ?>
                <?php if (!empty($searchResults)): ?>
                    <?php foreach ($searchResults as $post): ?>
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
                                <p><?= mb_substr(strip_tags($post['description'] ?? $post['content']), 0, 150) ?>...</p>
                                <small>Đăng ngày <?= date('d/m/Y H:i', strtotime($post['created_at'])) ?></small>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p>Không tìm thấy kết quả phù hợp.</p>
                <?php endif; ?>

            <?php else: ?>
                <!-- BÀI VIẾT ĐƯỢC QUAN TÂM NHIỀU NHẤT trong ngày -->
                <h2 class="mt-4">PNT <strong>Spotlight</strong></h2>
                <div class="post-grid">
                    <?php foreach ($popularPosts as $post): ?>
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
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <!-- BÀI VIẾT MỚI NHẤT TRONG 7 NGÀY-->
                <h2>Bài viết gần đây</h2>
                <?php foreach ($latestPosts as $post): ?>
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
                            <p><?= mb_substr(strip_tags($post['description'] ?? $post['content']), 0, 150) ?>...</p>
                            <small>Đăng ngày <?= date('d/m/Y H:i', strtotime($post['created_at'])) ?></small>
                        </div>
                    </div>
                <?php endforeach; ?>

            <?php endif; ?>
        </div>

        <!-- MẢNG PHẢI -->
        <?php if (!$search  && !$categoryId): ?>
            <div class="col-md-3">
                <h5 class="mb-3 sidebar-title">Bài viết mới nhất</h5>
                <?php foreach ($recentPosts as $post): ?>
                    <div class="mb-3 text-center d-flex flex-column align-items-center sidebar-post-item">
                        <div class="thumbnail">
                            <?php if (!empty($post['thumbnail'])): ?>
                                <img src="../uploads/<?= htmlspecialchars($post['thumbnail']) ?>" alt="Thumbnail">
                            <?php else: ?>
                                <img src="../uploads/default.jpg" alt="No Thumbnail">
                            <?php endif; ?>
                        </div>
                        <div class="sidebar-post-info">
                            <a href="/post.php?slug=<?= htmlspecialchars($post['slug']) ?>" target="_blank">
                                <?= htmlspecialchars($post['title']) ?>
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Nút mở form liên hệ -->
    <button class="contact-btn" id="openContact" title="Liên hệ"><i class="fa-solid fa-headset" style="color: #ffffff;"></i></button>

    <!-- Form liên hệ dạng popup -->
    <div class="contact-form" id="contactForm">
    <h4>Liên hệ</h4>

    <!-- Thông báo từ session (nếu có) -->
    <?php if (isset($_SESSION['contact_success'])): ?>
        <div class="alert alert-success">
        <?= $_SESSION['contact_success'] ?>
        <?php unset($_SESSION['contact_success']); ?>
        </div>
    <?php endif; ?>
    <?php if (isset($_SESSION['contact_error'])): ?>
        <div class="alert alert-danger">
        <?= $_SESSION['contact_error'] ?>
        <?php unset($_SESSION['contact_error']); ?>
        </div>
    <?php endif; ?>

    <!-- Form liên hệ: chỉ gồm email và comment -->
    <form method="post" action="index.php">
        <!-- Email input -->
        <div class="form-group">
        <label for="email">Email của bạn:</label>
        <input type="email" id="email" name="email" placeholder="example@domain.com" required>
        </div>

        <!-- Comment -->
        <div class="form-group">
        <label for="comment">Nội dung liên hệ:</label>
        <textarea id="comment" name="comment" rows="3" placeholder="Nhập nội dung của bạn..." required></textarea>
        </div>

        <!-- Submit button -->
        <button type="submit" class="submit-btn">Gửi</button>
    </form>
    </div>

    <!-- JavaScript để toggle hiển thị form liên hệ -->
    <script>
    const contactBtn = document.getElementById('openContact');
    const contactForm = document.getElementById('contactForm');

    contactBtn.addEventListener('click', function () {
        // Toggle hiển thị form
        if (contactForm.style.display === 'none' || contactForm.style.display === '') {
        contactForm.style.display = 'block';
        } else {
        contactForm.style.display = 'none';
        }
    });
    </script>
</div>
</div>

<?php include 'includes/footer.php'; ?>
