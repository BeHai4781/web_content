<?php
require '../config/db.php';
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
// Nếu chưa đăng nhập thì chuyển hướng
if (!isset($_SESSION['user'])) {
    echo "<script>alert('Bạn chưa đăng nhập!'); window.location.href = '/auth/login.php';</script>";
    exit;
}

include '../includes/header_admin.php';

// Tổng số người dùng
$totalUsers = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();

// Tổng số bài viết
$totalPosts = $pdo->query("SELECT COUNT(*) FROM posts")->fetchColumn();

// Số người dùng mới trong tuần và tháng
$newUsersWeek = $pdo->query("SELECT COUNT(*) FROM users WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)")->fetchColumn();
$newUsersMonth = $pdo->query("SELECT COUNT(*) FROM users WHERE created_at >= DATE_SUB(NOW(), INTERVAL 1 MONTH)")->fetchColumn();

// Số bài viết mới trong ngày
$newPostsToday = $pdo->query("SELECT COUNT(*) FROM posts WHERE DATE(created_at) = CURDATE()")->fetchColumn();

// Lượt xem bài viết theo ngày trong 1 tháng 
$postViews = $pdo->query("SELECT DATE(created_at) as date, SUM(views) as total_views FROM posts WHERE created_at >= DATE_SUB(NOW(), INTERVAL 1 MONTH) GROUP BY DATE(created_at)")->fetchAll();
$postLabels = [];
$postData = [];
foreach ($postViews as $row) {
    $postLabels[] = $row['date'];
    $postData[] = $row['total_views'];
}
$postLabelsStr = json_encode($postLabels);
$postDataStr = json_encode($postData);

// Chủ đề hot nhất
$hotTopic = $pdo->query("SELECT categories.name, SUM(posts.views) as total_views FROM posts JOIN categories ON posts.category_id = categories.id WHERE posts.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) GROUP BY category_id ORDER BY total_views DESC LIMIT 1")->fetch();

// Bài viết mới nhất trong ngày
$recentPosts = $pdo->query("SELECT posts.*, users.username as author FROM posts JOIN users ON posts.user_id = users.id WHERE DATE (posts.created_at) = CURDATE() ORDER BY posts.created_at DESC LIMIT 5")->fetchAll();

// Bài viết được quan tâm nhiều nhất trong ngày
$popularPosts = $pdo->query("SELECT posts.*, users.username as author FROM posts JOIN users ON posts.user_id = users.id WHERE DATE(posts.created_at) = CURDATE() AND posts.views >= 15 ORDER BY posts.views DESC")->fetchAll();
?>

<style>
.dashboard-container {
    background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
    min-height: 100vh;
    padding: 20px 0;
}

.dashboard-title {
    text-align: center;
    color: #253342;
    font-size: 2.5rem;
    font-weight: 700;
    margin-bottom: 2rem;
    text-shadow: 2px 2px 4px rgba(0,0,0,0.1);
}

.stats-card {
    background: linear-gradient(145deg, #ffffff, #f0f0f0);
    border: none;
    border-radius: 20px;
    padding: 2rem;
    margin-bottom: 1.5rem;
    box-shadow: 0 10px 30px rgba(0,0,0,0.1);
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
}

.stats-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: linear-gradient(90deg, #0091ae, #006d77);
}

.stats-card:hover {
    transform: translateY(-10px);
    box-shadow: 0 20px 40px rgba(0,0,0,0.15);
}

.stats-card.users::before { background: linear-gradient(90deg, #4facfe, #00f2fe); }
.stats-card.posts::before { background: linear-gradient(90deg, #43e97b, #38f9d7); }
.stats-card.new-users::before { background: linear-gradient(90deg, #fa709a, #fee140); }
.stats-card.new-posts::before { background: linear-gradient(90deg, #a8edea, #fed6e3); }

.stats-icon {
    font-size: 3rem;
    margin-bottom: 1rem;
    display: block;
}

.stats-card.users .stats-icon { color: #4facfe; }
.stats-card.posts .stats-icon { color: #43e97b; }
.stats-card.new-users .stats-icon { color: #fa709a; }
.stats-card.new-posts .stats-icon { color: #a8edea; }

.stats-number {
    font-size: 2.5rem;
    font-weight: 700;
    color: #253342;
    margin: 0;
    line-height: 1;
}

.stats-label {
    font-size: 1rem;
    color: #666;
    font-weight: 500;
    margin-top: 0.5rem;
}

.chart-container {
    background: white;
    border-radius: 20px;
    padding: 2rem;
    margin-bottom: 2rem;
    box-shadow: 0 10px 30px rgba(0,0,0,0.1);
    border: 1px solid rgba(0,145,174,0.1);
}

.chart-title {
    color: #253342;
    font-size: 1.5rem;
    font-weight: 600;
    margin-bottom: 1.5rem;
    text-align: center;
}

.hot-topic-alert {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border: none;
    border-radius: 15px;
    color: white;
    padding: 1.5rem;
    margin-bottom: 2rem;
    box-shadow: 0 8px 25px rgba(102, 126, 234, 0.3);
}

.hot-topic-alert strong {
    color: #fff;
    text-shadow: 1px 1px 2px rgba(0,0,0,0.2);
}

.section-title {
    color: #253342;
    font-size: 1.8rem;
    font-weight: 600;
    margin: 2rem 0 1rem 0;
    position: relative;
    padding-left: 20px;
}

.section-title::before {
    content: '';
    position: absolute;
    left: 0;
    top: 50%;
    transform: translateY(-50%);
    width: 4px;
    height: 30px;
    background: linear-gradient(135deg, #0091ae, #006d77);
    border-radius: 2px;
}

.post-card {
    background: white;
    border: none;
    border-radius: 15px;
    margin-bottom: 1rem;
    box-shadow: 0 5px 20px rgba(0,0,0,0.08);
    transition: all 0.3s ease;
    overflow: hidden;
}

.post-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 15px 35px rgba(0,0,0,0.12);
}

.post-card .card-body {
    padding: 1.5rem;
}

.post-card .card-title {
    color: #253342;
    font-size: 1.2rem;
    font-weight: 600;
    margin-bottom: 1rem;
    line-height: 1.4;
}

.post-meta {
    color: #666;
    font-size: 0.9rem;
    margin-bottom: 1rem;
    display: flex;
    flex-wrap: wrap;
    gap: 15px;
}

.post-meta-item {
    display: flex;
    align-items: center;
    gap: 5px;
}

.post-meta-item i {
    color: #0091ae;
}

.post-link {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    background: linear-gradient(135deg, #0091ae, #006d77);
    color: white;
    padding: 10px 20px;
    border-radius: 25px;
    text-decoration: none;
    font-weight: 500;
    transition: all 0.3s ease;
    font-size: 0.9rem;
}

.post-link:hover {
    background: linear-gradient(135deg, #006d77, #004d54);
    color: white;
    transform: translateX(5px);
}

.empty-state {
    text-align: center;
    padding: 3rem;
    color: #666;
    font-style: italic;
}

.empty-state i {
    font-size: 4rem;
    color: #ddd;
    margin-bottom: 1rem;
}

@media (max-width: 768px) {
    .dashboard-title {
        font-size: 2rem;
    }
    
    .stats-card {
        padding: 1.5rem;
        margin-bottom: 1rem;
    }
    
    .stats-number {
        font-size: 2rem;
    }
    
    .post-meta {
        flex-direction: column;
        gap: 8px;
    }
}
</style>

<div class="dashboard-container">
    <div class="container">
        <div class="row">
            <div class="col-md-3 col-sm-6">
                <a href="../admin/approve_user.php?duyet=true" style="text-decoration: none;">
                    <div class="stats-card users">
                        <div class="text-center">
                            <i class="stats-icon">👥</i>
                            <div class="stats-number"><?= number_format($totalUsers) ?></div>
                            <div class="stats-label">Tổng người dùng</div>
                        </div>
                    </div>
                </a>
            </div>
            <div class="col-md-3 col-sm-6">
                <a href="../admin/approve_cate.php" style="text-decoration: none;">
                    <div class="stats-card posts">
                        <div class="text-center">
                            <i class="stats-icon">📝</i>
                            <div class="stats-number"><?= number_format($totalPosts) ?></div>
                            <div class="stats-label">Tổng bài viết</div>
                        </div>
                    </div>
                </a>
            </div>
            <div class="col-md-3 col-sm-6">
                <div class="stats-card new-users">
                    <div class="text-center">
                        <i class="stats-icon">🆕</i>
                        <div class="stats-number"><?= number_format($newUsersWeek) ?></div>
                        <div class="stats-label">Người dùng mới (7 ngày)</div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6">
                <div class="stats-card new-posts">
                    <div class="text-center">
                        <i class="stats-icon">✨</i>
                        <div class="stats-number"><?= number_format($newPostsToday) ?></div>
                        <div class="stats-label">Bài viết mới (hôm nay)</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Chart -->
        <div class="chart-container">
            <h3 class="chart-title">📈 Thống kê lượt xem bài viết 30 ngày gần nhất</h3>
            <div style="position: relative; height: 400px;">
                <canvas id="lineChart"></canvas>
            </div>
        </div>

        <!-- Hot Topic -->
        <div class="hot-topic-alert">
            <div class="d-flex align-items-center">
                <i style="font-size: 2rem; margin-right: 15px;">🔥</i>
                <div>
                    <strong>Chủ đề hot trong tuần:</strong> 
                    <span style="font-size: 1.1rem;"><?= $hotTopic['name'] ?? 'Chưa xác định' ?></span>
                    <br>
                    <small style="opacity: 0.9;">👁️ <?= number_format($hotTopic['total_views'] ?? 0) ?> lượt xem</small>
                </div>
            </div>
        </div>

        <!-- Bài viết mới nhất  -->
        <h3 class="section-title">Bài viết mới nhất hôm nay</h3>
        <?php if (empty($recentPosts)): ?>
            <div class="empty-state">
                <i>📭</i>
                <p>Chưa có bài viết mới nào hôm nay</p>
            </div>
        <?php else: ?>
            <?php foreach ($recentPosts as $post): ?>
                <div class="post-card">
                    <div class="card-body">
                        <h5 class="card-title"><?= htmlspecialchars($post['title']) ?></h5>
                        <div class="post-meta">
                            <div class="post-meta-item">
                                <i>👤</i>
                                <span><strong>Người viết:</strong> <?= htmlspecialchars($post['author']) ?></span>
                            </div>
                            <div class="post-meta-item">
                                <i>📅</i>
                                <span><strong>Ngày đăng:</strong> <?= date('d/m/Y H:i', strtotime($post['created_at'])) ?></span>
                            </div>
                            <div class="post-meta-item">
                                <i>👁️</i>
                                <span><strong>Lượt xem:</strong> <?= number_format($post['views']) ?></span>
                            </div>
                        </div>
                        <a href="/post.php?slug=<?= htmlspecialchars($post['slug']) ?>" target="_blank" class="post-link">
                            <span>Xem chi tiết</span>
                            <i>🔗</i>
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>

        <!-- Bài viết được quan tâm  -->
        <h3 class="section-title">Bài viết được quan tâm nhiều nhất hôm nay</h3>
        <?php if (empty($popularPosts)): ?>
            <div class="empty-state">
                <i>📭</i>
                <p>Chưa có bài viết phổ biến nào hôm nay</p>
            </div>
        <?php else: ?>
            <?php foreach ($popularPosts as $post): ?>
                <div class="post-card">
                    <div class="card-body">
                        <h5 class="card-title"><?= htmlspecialchars($post['title']) ?></h5>
                        <div class="post-meta">
                            <div class="post-meta-item">
                                <i>👤</i>
                                <span><strong>Người viết:</strong> <?= htmlspecialchars($post['author']) ?></span>
                            </div>
                            <div class="post-meta-item">
                                <i>📅</i>
                                <span><strong>Ngày đăng:</strong> <?= date('d/m/Y H:i', strtotime($post['created_at'])) ?></span>
                            </div>
                            <div class="post-meta-item">
                                <i>👁️</i>
                                <span><strong>Lượt xem:</strong> <?= number_format($post['views']) ?></span>
                            </div>
                        </div>
                        <a href="/post.php?slug=<?= htmlspecialchars($post['slug']) ?>" target="_blank" class="post-link">
                            <span>Xem chi tiết</span>
                            <i>🔗</i>
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    const ctx = document.getElementById('lineChart').getContext('2d');
    
    const gradient = ctx.createLinearGradient(0, 0, 0, 400);
    gradient.addColorStop(0, 'rgba(0, 145, 174, 0.8)');
    gradient.addColorStop(1, 'rgba(0, 145, 174, 0.1)');
    
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: <?= $postLabelsStr ?>,
            datasets: [{
                label: 'Lượt xem bài viết',
                data: <?= $postDataStr ?>,
                borderColor: '#0091ae',
                backgroundColor: gradient,
                borderWidth: 3,
                tension: 0.4,
                fill: true,
                pointBackgroundColor: '#0091ae',
                pointBorderColor: '#ffffff',
                pointBorderWidth: 3,
                pointRadius: 6,
                pointHoverRadius: 8,
                pointHoverBackgroundColor: '#006d77',
                pointHoverBorderColor: '#ffffff',
                pointHoverBorderWidth: 3
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { 
                    position: 'top',
                    labels: {
                        color: '#253342',
                        font: {
                            size: 14,
                            weight: 'bold'
                        }
                    }
                },
                title: { 
                    display: false
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: {
                        color: 'rgba(0, 145, 174, 0.1)'
                    },
                    ticks: {
                        color: '#666',
                        font: {
                            size: 12
                        }
                    }
                },
                x: {
                    grid: {
                        color: 'rgba(0, 145, 174, 0.1)'
                    },
                    ticks: {
                        color: '#666',
                        font: {
                            size: 12
                        }
                    }
                }
            },
            interaction: {
                intersect: false,
                mode: 'index'
            }
        }
    });
</script>

<?php include '../includes/footer.php'; ?>