<?php
require_once __DIR__ . '/../includes/functions.php';
requireRole('Admin');
$db = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'add') {
        $stmt = $db->prepare('INSERT INTO Categories (CategoryName, Description) VALUES (:n, :d)');
        $stmt->execute(['n' => trim($_POST['name']), 'd' => trim($_POST['description']) ?: null]);
        flash('success', 'เพิ่มหมวดหมู่สำเร็จ');
    } elseif ($action === 'edit') {
        $stmt = $db->prepare('UPDATE Categories SET CategoryName=:n, Description=:d WHERE CategoryID=:id');
        $stmt->execute(['n' => trim($_POST['name']), 'd' => trim($_POST['description']) ?: null, 'id' => (int)$_POST['id']]);
        flash('success', 'แก้ไขหมวดหมู่สำเร็จ');
    } elseif ($action === 'delete') {
        try {
            $stmt = $db->prepare('DELETE FROM Categories WHERE CategoryID=:id');
            $stmt->execute(['id' => (int)$_POST['id']]);
            flash('success', 'ลบหมวดหมู่สำเร็จ');
        } catch (PDOException $e) {
            flash('error', 'ไม่สามารถลบได้ เนื่องจากมีหนังสือใช้หมวดหมู่นี้อยู่');
        }
    }
    redirect('/admin/categories.php');
}

$categories = $db->query(
    "SELECT c.*, COUNT(b.BookID) AS BookCount FROM Categories c
     LEFT JOIN Books b ON b.CategoryID = c.CategoryID
     GROUP BY c.CategoryID ORDER BY c.CategoryName"
)->fetchAll();

$pageTitle = 'จัดการหมวดหมู่';
require __DIR__ . '/../includes/header.php';
?>
<h4 class="fw-bold mb-3">🏷️ จัดการหมวดหมู่</h4>

<div class="card p-3 mb-3">
  <h6 class="fw-bold">เพิ่มหมวดหมู่ใหม่</h6>
  <form method="post" class="row g-2">
    <input type="hidden" name="action" value="add">
    <div class="col-md-4"><input name="name" class="form-control" placeholder="ชื่อหมวดหมู่" required></div>
    <div class="col-md-6"><input name="description" class="form-control" placeholder="คำอธิบาย"></div>
    <div class="col-md-2"><button class="btn btn-brand w-100" type="submit">เพิ่ม</button></div>
  </form>
</div>

<div class="card p-3">
<table class="table table-hover align-middle">
  <thead><tr><th>ชื่อหมวดหมู่</th><th>คำอธิบาย</th><th>จำนวนหนังสือ</th><th style="width:220px;"></th></tr></thead>
  <tbody>
  <?php foreach ($categories as $c): ?>
    <tr>
      <form method="post">
      <input type="hidden" name="action" value="edit">
      <input type="hidden" name="id" value="<?= (int)$c['CategoryID'] ?>">
      <td><input name="name" class="form-control form-control-sm" value="<?= e($c['CategoryName']) ?>"></td>
      <td><input name="description" class="form-control form-control-sm" value="<?= e($c['Description']) ?>"></td>
      <td><?= (int)$c['BookCount'] ?></td>
      <td>
        <button class="btn btn-sm btn-outline-primary" type="submit">บันทึก</button>
      </form>
        <form method="post" class="d-inline" onsubmit="return confirm('ยืนยันการลบ?');">
          <input type="hidden" name="action" value="delete">
          <input type="hidden" name="id" value="<?= (int)$c['CategoryID'] ?>">
          <button class="btn btn-sm btn-outline-danger" type="submit">ลบ</button>
        </form>
      </td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
