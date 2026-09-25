<?php
session_start();
include '../db.php';

if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: index.php");
    exit();
}

$id = (int)$_GET['id'];
$error = "";

// อัปเดตข้อมูล
if (isset($_POST['update'])) {
    $title  = trim($_POST['title'] ?? '');
    $detail = trim($_POST['detail'] ?? '');
    $link   = trim($_POST['link'] ?? '');

    // ดึงข้อมูลเดิมไว้ก่อน
    $sql_old = "SELECT * FROM news WHERE id='$id'";
    $result_old = $conn->query($sql_old);
    $old_row = $result_old ? $result_old->fetch_assoc() : null;

    if (!$old_row) {
        $error = "ไม่พบข้อมูลข่าวที่ต้องการแก้ไข";
    } else {
        $image = $old_row['image'];

        // ถ้ามีการอัปโหลดรูปใหม่
        if (isset($_FILES['image']) && !empty($_FILES['image']['name'])) {
            $filename = time() . "_" . basename($_FILES['image']['name']);
            $tmp = $_FILES['image']['tmp_name'];
            $target = "../uploads/" . $filename;

            if (move_uploaded_file($tmp, $target)) {
                $image = $filename;
            } else {
                $error = "อัปโหลดรูปไม่สำเร็จ";
            }
        }

        if ($error === "") {
            $title_safe  = $conn->real_escape_string($title);
            $detail_safe = $conn->real_escape_string($detail);
            $link_safe   = $conn->real_escape_string($link);
            $image_safe  = $conn->real_escape_string($image);

            $sql_update = "UPDATE news SET
                title='$title_safe',
                detail='$detail_safe',
                link='$link_safe',
                image='$image_safe'
                WHERE id='$id'";

            if ($conn->query($sql_update)) {
                $_SESSION['update_success'] = true;
                header("Location: edit.php?id=$id");
                exit();
            } else {
                $error = "เกิดข้อผิดพลาด: " . $conn->error;
            }
        }
    }
}

// ดึงข้อมูลล่าสุดมาแสดงในฟอร์ม
$sql = "SELECT * FROM news WHERE id='$id'";
$result = $conn->query($sql);
$row = $result ? $result->fetch_assoc() : null;

if (!$row) {
    echo "ไม่พบข้อมูลข่าว";
    exit();
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>แก้ไขข่าว</title>
    <link rel="stylesheet" href="style.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>

<div class="topbar">
    <div class="logo">Admin Dashboard</div>
    <div class="menu">
        <a href="index.php">จัดการข่าว</a>
        <a class="logout" href="logout.php">Logout</a>
    </div>
</div>

<div class="container">
    <div class="form-card">
        <h2>แก้ไขข่าว</h2>

        <?php if (!empty($error)) : ?>
            <div class="alert"><?php echo $error; ?></div>
        <?php endif; ?>

        <form method="post" enctype="multipart/form-data">
            <label>หัวข้อข่าว</label>
            <input type="text" name="title" value="<?php echo htmlspecialchars($row['title']); ?>" required>

            <label>รายละเอียด</label>
            <textarea name="detail" required><?php echo htmlspecialchars($row['detail']); ?></textarea>

            <label>รูปข่าวปัจจุบัน</label>
            <img
                src="../uploads/<?php echo htmlspecialchars($row['image']); ?>"
                alt="รูปข่าว"
                id="current-preview"
                style="width:150px; margin-bottom:10px; border-radius:8px; display:block;"
            >

            <label>เลือกรูปใหม่</label>
            <input type="file" name="image" accept="image/*" onchange="previewImage(event)">

            <img
                id="preview"
                style="width:150px; margin-top:10px; border-radius:8px; display:none;"
                alt="ตัวอย่างรูปใหม่"
            >

            <label>ลิงก์ Facebook</label>
            <input type="text" name="link" value="<?php echo htmlspecialchars($row['link'] ?? ''); ?>">

            <button class="btn-save" name="update" type="submit">อัปเดตข่าว</button>
        </form>
    </div>
</div>

<script>
function previewImage(event) {
    const file = event.target.files[0];
    if (!file) return;

    const reader = new FileReader();
    reader.onload = function(e) {
        const preview = document.getElementById('preview');
        preview.src = e.target.result;
        preview.style.display = 'block';
    };
    reader.readAsDataURL(file);
}
</script>

<?php if (isset($_SESSION['update_success'])): ?>
<script>
Swal.fire({
    icon: 'success',
    title: 'บันทึกสำเร็จ',
    text: 'อัปเดตข่าวเรียบร้อยแล้ว',
    confirmButtonColor: '#27ae60'
}).then(() => {
    window.location = 'index.php';
});
</script>
<?php unset($_SESSION['update_success']); endif; ?>

</body>
</html>