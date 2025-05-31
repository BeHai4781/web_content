<?php 
session_start();
require '../config/db.php';
include('header.php');?>
<!-- File: create.php -->
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Viết bài mới</title>
  <link href="https://fonts.googleapis.com/css2?family=Roboto&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../user/create.css">
</head>
<body>
<div class="container">

  <!-- Form viết bài -->
    <form action="save_post.php" method="POST" enctype="multipart/form-data" target="_blank">
      <div class="form-group">
    <div class="slug-group">
      <input type="text" name="title" id="title" placeholder="Nhập tiêu đề bài viết" required>
      <button type="button" onclick="generateSlug()">Tạo link</button>
    </div>
    <input type="text" name="slug" class="showlink" id="slug" placeholder="Link bài viết sẽ tự động tạo" readonly>
    </div>

    <!-- Vùng nhập văn bản và sidebar -->
    <div class="editor-section">
      <div class="editor">
        <textarea id="editor" name="content"></textarea>
      </div>

      <div class="sidebar">
        <div class="form-group">
          <label for="category">Thể loại:</label>
          <select name="category" id="category" class="custom-input">
            <?php
              require '../config/db.php';
              $stmt = $pdo->query("SELECT * FROM categories");
              while ($row = $stmt->fetch()) {
                echo "<option value='" . htmlspecialchars($row['id']) . "'>" . htmlspecialchars($row['name']) . "</option>";
              }
            ?>
          </select>
        </div>

        <div class="form-group">
          <label for="keywords">Từ khóa:</label>
          <textarea name="keywords" id="keywords" placeholder="Nhập từ khóa" class="custom-input"></textarea>
        </div>
        <div id="tag-container" class="tag-container"></div>

        <div class="image">
          <label for="thumbnail">Ảnh đại diện (thumbnail)</label>
          <div style="position: relative;">
            <input type="file" id="thumbnail" name="thumbnail" accept="image/*" style="padding-right: 30px;">
            <span id="remove-thumbnail" title="Xoá ảnh" style="
              display: none;
              position: absolute;
              right: 10px;
              top: 50%;
              transform: translateY(-50%);
              cursor: pointer;
              font-weight: bold;
              color: #c00;
              font-size: 18px;
            ">×</span>
          </div>
        </div>

        <div class="descript">
          <label for="excerpt">Mô tả ngắn</label>
          <textarea id="excerpt" name="excerpt" rows="3" placeholder="Nhập mô tả ngắn ..."></textarea>
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

  function generateSlug() {
    const title = document.getElementById("title").value;

    const slug = title.toLowerCase()
      .normalize("NFD") // bỏ dấu tiếng Việt
      .replace(/[\u0300-\u036f]/g, "")
      .replace(/[^a-z0-9 -]/g, "") // chỉ giữ chữ thường, số, dấu gạch
      .replace(/\s+/g, '-')       // khoảng trắng thành dấu -
      .replace(/-+/g, '-');       // loại bỏ dấu - thừa

    document.getElementById("slug").value = slug;
  }

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
      event.preventDefault(); // Ngăn xuống dòng nếu cần
      const status = document.getElementById("excerpt-status");
      status.style.display = "block";
      status.style.color = "#28a745"; // màu xanh lá báo thành công

      // Nếu muốn sau vài giây sẽ ẩn lại:
      setTimeout(() => {
        status.style.display = "none";
      }, 3000);
    }
  });

  const thumbnailInput = document.getElementById("thumbnail");
  const removeBtn = document.getElementById("remove-thumbnail");

  thumbnailInput.addEventListener("change", function () {
    if (thumbnailInput.files.length > 0) {
      removeBtn.style.display = "inline";
    } else {
      removeBtn.style.display = "none";
    }
  });

  removeBtn.addEventListener("click", function () {
    thumbnailInput.value = ""; // Xoá ảnh
    removeBtn.style.display = "none";
  });

  // ✅ Cập nhật: đưa từ khóa đã chọn vào ô textarea khi submit
  document.querySelector('form').addEventListener('submit', function () {
    document.getElementById('keywords').value = tags.join(', ');
  });

</script>
</body>
</html>
<?php include('../includes/footer.php'); ?>