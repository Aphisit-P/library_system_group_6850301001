<?php
require_once __DIR__ . '/../includes/functions.php';
requireRole('Admin');
$db = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
    try {
        $db->prepare('DELETE FROM Books WHERE BookID=:id')->execute(['id' => (int)$_POST['id']]);
        flash('success', 'ลบหนังสือสำเร็จ');
    } catch (PDOException $e) {
        flash('error', 'ไม่สามารถลบได้ เนื่องจากยังมีเล่ม (copies) หรือประวัติการยืมของหนังสือนี้อยู่');
    }
    redirect('/admin/books.php');
}

$q = trim($_GET['q'] ?? '');
$sql = "SELECT b.*, c.CategoryName,
               GROUP_CONCAT(DISTINCT a.AuthorName SEPARATOR ', ') AS Authors,
               COUNT(DISTINCT bc.CopyID) AS CopyCount
        FROM Books b
        JOIN Categories c ON b.CategoryID = c.CategoryID
        LEFT JOIN BookAuthors ba ON b.BookID = ba.BookID
        LEFT JOIN Authors a ON ba.AuthorID = a.AuthorID
        LEFT JOIN BookCopies bc ON b.BookID = bc.BookID";
$params = [];
if ($q !== '') {
    $sql .= " WHERE b.Title LIKE :q OR b.ISBN LIKE :q";
    $params['q'] = "%$q%";
}
$sql .= " GROUP BY b.BookID ORDER BY b.Title";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$books = $stmt->fetchAll();

$pageTitle = 'จัดการหนังสือ';
require __DIR__ . '/../includes/header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h4 class="fw-bold mb-0">📚 จัดการหนังสือ</h4>
  <a href="<?= basePath() ?>/admin/book_form.php" class="btn btn-brand">+ เพิ่มหนังสือใหม่</a>
</div>

<form method="get" class="mb-3">
  <input type="text" name="q" class="form-control" placeholder="ค้นหาชื่อ/ISBN" value="<?= e($q) ?>">
</form>

<div class="card p-3">
<div class="table-responsive">
<table class="table table-hover align-middle">
  <thead><tr><th>Title</th><th>ISBN</th><th>ผู้แต่ง</th><th>หมวดหมู่</th><th>ราคา</th><th>จำนวนเล่ม</th><th style="width:160px;"></th></tr></thead>
  <tbody>
  <?php foreach ($books as $b): ?>
    <tr>
      <td><?= e($b['Title']) ?></td>
      <td><?= e($b['ISBN']) ?></td>
      <td><?= e($b['Authors']) ?></td>
      <td><?= e($b['CategoryName']) ?></td>
      <td><?= money($b['Price']) ?></td>
      <td><?= (int)$b['CopyCount'] ?></td>
      <td>
        <a href="<?= basePath() ?>/admin/book_form.php?id=<?= (int)$b['BookID'] ?>" class="btn btn-sm btn-outline-primary">แก้ไข</a>
        <form method="post" class="d-inline" onsubmit="return confirm('ยืนยันการลบหนังสือนี้?');">
          <input type="hidden" name="action" value="delete">
          <input type="hidden" name="id" value="<?= (int)$b['BookID'] ?>">
          <button class="btn btn-sm btn-outline-danger" type="submit">ลบ</button>
        </form>
      </td>
    </tr>
  <?php endforeach; ?>
  <?php if (!$books): ?>
    <tr><td colspan="7" class="text-center text-muted">ไม่มีข้อมูลหนังสือ</td></tr>
  <?php endif; ?>
  </tbody>
</table>
</div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
