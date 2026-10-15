<?php
require_once __DIR__ . '/../includes/functions.php';
requireRole('Admin');
$db = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'add') {
        $stmt = $db->prepare('INSERT INTO Authors (AuthorName, Biography) VALUES (:n, :b)');
        $stmt->execute(['n' => trim($_POST['name']), 'b' => trim($_POST['bio']) ?: null]);
        flash('success', 'เพิ่มผู้แต่งสำเร็จ');
    } elseif ($action === 'edit') {
        $stmt = $db->prepare('UPDATE Authors SET AuthorName=:n, Biography=:b WHERE AuthorID=:id');
        $stmt->execute(['n' => trim($_POST['name']), 'b' => trim($_POST['bio']) ?: null, 'id' => (int)$_POST['id']]);
        flash('success', 'แก้ไขผู้แต่งสำเร็จ');
    } elseif ($action === 'delete') {
        try {
            $stmt = $db->prepare('DELETE FROM Authors WHERE AuthorID=:id');
            $stmt->execute(['id' => (int)$_POST['id']]);
            flash('success', 'ลบผู้แต่งสำเร็จ');
        } catch (PDOException $e) {
            flash('error', 'ไม่สามารถลบได้ เนื่องจากมีหนังสือของผู้แต่งนี้อยู่');
        }
    }
    redirect('/admin/authors.php');
}

$authors = $db->query(
    "SELECT a.*, COUNT(ba.BookID) AS BookCount FROM Authors a
     LEFT JOIN BookAuthors ba ON ba.AuthorID = a.AuthorID
     GROUP BY a.AuthorID ORDER BY a.AuthorName"
)->fetchAll();

$pageTitle = 'จัดการผู้แต่ง';
require __DIR__ . '/../includes/header.php';
?>
<h4 class="fw-bold mb-3">✍️ จัดการผู้แต่ง</h4>

<div class="card p-3 mb-3">
  <h6 class="fw-bold">เพิ่มผู้แต่งใหม่</h6>
  <form method="post" class="row g-2">
    <input type="hidden" name="action" value="add">
    <div class="col-md-4"><input name="name" class="form-control" placeholder="ชื่อผู้แต่ง" required></div>
    <div class="col-md-6"><input name="bio" class="form-control" placeholder="ประวัติโดยย่อ"></div>
    <div class="col-md-2"><button class="btn btn-brand w-100" type="submit">เพิ่ม</button></div>
  </form>
</div>

<div class="card p-3">
<table class="table table-hover align-middle">
  <thead><tr><th>ชื่อผู้แต่ง</th><th>ประวัติ</th><th>จำนวนหนังสือ</th><th style="width:220px;"></th></tr></thead>
  <tbody>
  <?php foreach ($authors as $a): ?>
    <tr>
      <form method="post">
      <input type="hidden" name="action" value="edit">
      <input type="hidden" name="id" value="<?= (int)$a['AuthorID'] ?>">
      <td><input name="name" class="form-control form-control-sm" value="<?= e($a['AuthorName']) ?>"></td>
      <td><input name="bio" class="form-control form-control-sm" value="<?= e($a['Biography']) ?>"></td>
      <td><?= (int)$a['BookCount'] ?></td>
      <td>
        <button class="btn btn-sm btn-outline-primary" type="submit">บันทึก</button>
      </form>
        <form method="post" class="d-inline" onsubmit="return confirm('ยืนยันการลบ?');">
          <input type="hidden" name="action" value="delete">
          <input type="hidden" name="id" value="<?= (int)$a['AuthorID'] ?>">
          <button class="btn btn-sm btn-outline-danger" type="submit">ลบ</button>
        </form>
      </td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
