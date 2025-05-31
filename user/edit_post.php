<?php
session_start();
require '../config/db.php';

if (!isset($_SESSION['user'])) {
    header('Location: /auth/login.php');
    exit;
}

$user_id = $_SESSION['user']['id'];
$post_id = $_GET['id'] ?? null;

if (!$post_id) {
    die("Không tìm thấy bài viết.");
}

$stmt = $pdo->prepare("SELECT * FROM posts WHERE id = ? AND user_id = ?");
$stmt->execute([$post_id, $user_id]);
$post = $stmt->fetch();

if (!$post) {
    die("Bài viết không tồn tại hoặc bạn không có quyền chỉnh sửa.");
}

$categories = $pdo->query("SELECT * FROM categories")->fetchAll();
include('header.php');
?>

<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <title>Chỉnh sửa bài viết</title>
  <link href="https://fonts.googleapis.com/css2?family=Roboto&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="create.css?v=<?= time(); ?>">
</head>
<body>
  <div class="container">
    <form action="update_post.php" method="POST" enctype="multipart/form-data" target="_blank">
        <input type="hidden" name="id" value="<?= $post['id'] ?>">
        <div class="form-group">
        <div class="slug-group">
            <input type="text" name="title" id="title" value="<?= htmlspecialchars($post['title']) ?>" required>
            <button type="button" onclick="generateSlug()">Tạo link</button>
        </div>
        <input type="text" name="slug" id="slug" value="<?= htmlspecialchars($post['slug']) ?>" readonly>
        </div>

        <div class="editor-section">
        <div class="editor">
            <textarea id="editor" name="content"><?= htmlspecialchars($post['content']) ?></textarea>
        </div>

        <div class="sidebar">
            <div class="form-group">
            <label for="category">Thể loại:</label>
            <select name="category" id="category" class="custom-input">
                <?php foreach ($categories as $cat): ?>
                <option value="<?= $cat['id'] ?>" <?= $cat['id'] == $post['category_id'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($cat['name']) ?>
                </option>
                <?php endforeach; ?>
            </select>
            </div>

            <div class="form-group">
            <label for="keywords" placeholder="Nhập từ khóa">Từ khóa:</label>
            <textarea name="keywords" id="keywords" class="custom-input"><?= htmlspecialchars($post['keywords']) ?></textarea>
            </div>
            <div id="tag-container" class="tag-container"></div>

            <div class="image">
            <label for="thumbnail">Ảnh đại diện hiện tại:</label>
            <?php if ($post['thumbnail']): ?>
                <img src="/uploads/<?= htmlspecialchars($post['thumbnail']) ?>" style="max-height: 150px;"><br>
            <?php endif; ?>
            <input type="file" name="thumbnail" id="thumbnail" accept="image/*">
            </div>

            <div class="descript">
            <label for="excerpt">Mô tả ngắn</label>
            <textarea id="excerpt" name="excerpt" rows="3" placeholder="Nhập mô tả ngắn ..."><?= htmlspecialchars($post['description']) ?></textarea>
            <small id="excerpt-status" style="display: none;">✔ Mô tả đã được thêm</small>
            </div>

            <div class="action-buttons">
            <button type="submit" class="btnsave">Lưu bài viết</button>
            <button type="reset" class="btndelete">Xóa</button>
            </div>
        </div>
        </div>
    </form>
    </div>

  <script src="tinymce_7.9.0/tinymce/js/tinymce/tinymce.min.js"></script>
  <script>
    tinymce.init({
        selector: '#editor',
        plugins: 'link image code lists table textcolor image_caption',
        toolbar: 'undo redo | styles | bold italic underline | forecolor backcolor | alignleft aligncenter alignright | bullist numlist outdent indent | link image | code',
        menubar: false,
        height: 800,
        width: '100%',
        image_caption: true, // Bật chú thích ảnh
        automatic_uploads: false, // Không tự upload
        images_upload_url: '',    // Không dùng nếu không upload

        file_picker_types: 'image',

    file_picker_callback: function (cb, value, meta) {
        if (meta.filetype === 'image') {
        const input = document.createElement('input');
        input.setAttribute('type', 'file');
        input.setAttribute('accept', 'image/*');

        input.onchange = function () {
            const file = this.files[0];

            const reader = new FileReader();
            reader.onload = function () {
            const id = 'blobid' + (new Date()).getTime();
            const blobCache = tinymce.activeEditor.editorUpload.blobCache;
            const base64 = reader.result.split(',')[1];
            const blobInfo = blobCache.create(id, file, base64);
            blobCache.add(blobInfo);

            cb(blobInfo.blobUri(), { title: file.name });
            };
            reader.readAsDataURL(file);
        };

        input.click();
        }
    }
    });

    const keywordsInput = document.getElementById("keywords");
  const tagContainer = document.getElementById("tag-container");

  let tags = [];

  keywordsInput.addEventListener("keyup", function (e) {
    if (e.key === "," || e.key === "Enter") {
      const raw = keywordsInput.value.trim();
      const split = raw.split(",");

      split.forEach(token => {
        const keyword = token.trim();
        if (keyword && !tags.includes(keyword)) {
          tags.push(keyword);
          addTagElement(keyword);
        }
      });

      keywordsInput.value = "";
    }
  });

  function addTagElement(keyword) {
    const tag = document.createElement("div");
    tag.className = "tag";
    tag.innerHTML = `${keyword} <span class="remove-tag" onclick="removeTag('${keyword}')">×</span>`;
    tagContainer.appendChild(tag);
  }

  function removeTag(keyword) {
    tags = tags.filter(tag => tag !== keyword);
    renderTags();
  }

  function renderTags() {
    tagContainer.innerHTML = "";
    tags.forEach(addTagElement);
  }

  document.getElementById("excerpt").addEventListener("keydown", function(event) {
    if (event.key === "Enter") {
      event.preventDefault(); // Ngăn xuống dòng 
      const status = document.getElementById("excerpt-status");
      status.style.display = "block";
      status.style.color = "#28a745"; 
      // vài giây sau ẩn lại 
      setTimeout(() => {
        status.style.display = "none";
      }, 3000);
    }
  });

  document.querySelector('form').addEventListener('submit', function () {
    document.getElementById('keywords').value = tags.join(', ');
  });
  </script>
</body>
</html>
<?php include('../includes/footer.php'); ?>