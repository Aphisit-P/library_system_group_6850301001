<?php
require_once __DIR__ . '/../includes/functions.php';
requireRole('Admin');
$db = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'add') {
        $stmt = $db->prepare(
            'INSERT INTO BookCopies (BookID, Barcode, BookCondition, Status, ShelfLocation)
             VALUES (:b, :barcode, :cond, "Available", :shelf)'
        );
        $stmt->execute([
            'b' => (int)$_POST['book_id'],
            'barcode' => trim($_POST['barcode']),
            'cond' => $_POST['condition'],
            'shelf' => trim($_POST['shelf']) ?: null,
        ]);
        flash('success', 'เพิ่มเล่มหนังสือสำเร็จ');
    } elseif ($action === 'update_status') {
        $stmt = $db->prepare('UPDATE BookCopies SET Status=:s, BookCondition=:c, ShelfLocation=:sh WHERE CopyID=:id');
        $stmt->execute([
            's' => $_POST['status'], 'c' => $_POST['condition'],
            'sh' => trim($_POST['shelf']) ?: null, 'id' => (int)$_POST['id']
        ]);
        flash('success', 'อัปเดตสถานะเล่มสำเร็จ');
    } elseif ($action === 'delete') {
        try {
            $db->prepare('DELETE FROM BookCopies WHERE CopyID=:id')->execute(['id' => (int)$_POST['id']]);
            flash('success', 'ลบเล่มหนังสือสำเร็จ');
        } catch (PDOException $e) {
            flash('error', 'ไม่สามารถลบได้ เนื่องจากมีประวัติการยืมของเล่มนี้อยู่');
        }
    }
    redirect('/admin/copies.php');
}

$books = $db->query('SELECT BookID, Title FROM Books ORDER BY Title')->fetchAll();
$copies = $db->query(
    "SELECT bc.*, bk.Title FROM BookCopies bc
     JOIN Books bk ON bc.BookID = bk.BookID
     ORDER BY bk.Title, bc.Barcode"
)->fetchAll();

$pageTitle = 'จัดการเล่มหนังสือ';
require __DIR__ . '/../includes/header.php';
?>
<h4 class="fw-bold mb-3">📕 จัดการเล่มหนังสือ (BookCopies)</h4>

<div class="card p-3 mb-3">
  <h6 class="fw-bold">เพิ่มเล่มใหม่</h6>
  <form method="post" class="row g-2">
    <input type="hidden" name="action" value="add">
    <div class="col-md-4">
      <select name="book_id" class="form-select" required>
        <option value="">-- เลือกหนังสือ --</option>
        <?php foreach ($books as $b): ?>
          <option value="<?= (int)$b['BookID'] ?>"><?= e($b['Title']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-3"><input name="barcode" class="form-control" placeholder="Barcode" required></div>
    <div class="col-md-2">
      <select name="condition" class="form-select">
        <option value="Good">Good</option>
        <option value="Minor">Minor damage</option>
        <option value="Moderate">Moderate damage</option>
        <option value="Severe">Severe damage</option>
      </select>
    </div>
    <div class="col-md-2"><input name="shelf" class="form-control" placeholder="ตำแหน่งชั้น"></div>
    <div class="col-md-1"><button class="btn btn-brand w-100" type="submit">เพิ่ม</button></div>
  </form>
</div>

<div class="card p-3">
<div class="table-responsive">
<table class="table table-hover align-middle">
  <thead><tr><th>หนังสือ</th><th>Barcode</th><th>สภาพ</th><th>สถานะ</th><th>ชั้นวาง</th><th style="width:280px;"></th></tr></thead>
  <tbody>
  <?php foreach ($copies as $c): ?>
    <tr>
      <form method="post">
      <input type="hidden" name="action" value="update_status">
      <input type="hidden" name="id" value="<?= (int)$c['CopyID'] ?>">
      <td><?= e($c['Title']) ?> <span class="text-muted">(<?= e($c['Barcode']) ?>)</span></td>
      <td><?= e($c['Barcode']) ?></td>
      <td>
        <select name="condition" class="form-select form-select-sm">
          <?php foreach (['Good','Minor','Moderate','Severe'] as $opt): ?>
            <option value="<?= $opt ?>" <?= $c['BookCondition']===$opt?'selected':'' ?>><?= $opt ?></option>
          <?php endforeach; ?>
        </select>
      </td>
      <td>
        <select name="status" class="form-select form-select-sm">
          <?php foreach (['Available','Borrowed','Lost','Damaged'] as $opt): ?>
            <option value="<?= $opt ?>" <?= $c['Status']===$opt?'selected':'' ?>><?= $opt ?></option>
          <?php endforeach; ?>
        </select>
      </td>
      <td><input name="shelf" class="form-control form-control-sm" value="<?= e($c['ShelfLocation']) ?>"></td>
      <td>
        <button class="btn btn-sm btn-outline-primary" type="submit">บันทึก</button>
      </form>
        <form method="post" class="d-inline" onsubmit="return confirm('ยืนยันลบเล่มนี้?');">
          <input type="hidden" name="action" value="delete">
          <input type="hidden" name="id" value="<?= (int)$c['CopyID'] ?>">
          <button class="btn btn-sm btn-outline-danger" type="submit">ลบ</button>
        </form>
      </td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table>
</div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
