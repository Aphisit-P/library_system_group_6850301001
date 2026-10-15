<?php
require_once __DIR__ . '/../includes/functions.php';
requireRole('Admin');
$db = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'create') {
    $userId = (int)$_POST['user_id'];
    $copyId = (int)$_POST['copy_id'];
    $days = (int)($_POST['days'] ?: 7);

    $db->beginTransaction();
    try {
        $check = $db->prepare('SELECT Status FROM BookCopies WHERE CopyID=:id FOR UPDATE');
        $check->execute(['id' => $copyId]);
        if ($check->fetchColumn() !== 'Available') {
            throw new Exception('เล่มนี้ไม่ว่างแล้ว');
        }
        $borrowDate = date('Y-m-d');
        $dueDate = date('Y-m-d', strtotime("+$days days"));
        $ins = $db->prepare('INSERT INTO Borrowings (UserID, BorrowDate, DueDate, Status) VALUES (:u,:bd,:dd,"Borrowed")');
        $ins->execute(['u'=>$userId,'bd'=>$borrowDate,'dd'=>$dueDate]);
        $borrowId = $db->lastInsertId();
        $db->prepare('INSERT INTO BorrowingDetails (BorrowID, CopyID) VALUES (:b,:c)')->execute(['b'=>$borrowId,'c'=>$copyId]);
        $db->prepare("UPDATE BookCopies SET Status='Borrowed' WHERE CopyID=:id")->execute(['id'=>$copyId]);
        $db->commit();
        flash('success', 'สร้างรายการยืมสำเร็จ');
    } catch (Exception $e) {
        $db->rollBack();
        flash('error', $e->getMessage());
    }
    redirect('/admin/borrowings.php');
}

$members = $db->query(
    "SELECT u.UserID, u.FullName, u.Username FROM Users u JOIN Roles r ON u.RoleID=r.RoleID
     WHERE r.RoleName='Member' AND u.Status='Active' ORDER BY u.FullName"
)->fetchAll();

$availableCopies = $db->query(
    "SELECT bc.CopyID, bc.Barcode, bk.Title FROM BookCopies bc
     JOIN Books bk ON bc.BookID=bk.BookID
     WHERE bc.Status='Available' ORDER BY bk.Title"
)->fetchAll();

$status = $_GET['status'] ?? '';
$sql = "SELECT b.BorrowID, b.BorrowDate, b.DueDate, b.Status, u.FullName,
               GROUP_CONCAT(bk.Title SEPARATOR ', ') AS Titles
        FROM Borrowings b
        JOIN Users u ON b.UserID = u.UserID
        JOIN BorrowingDetails bd ON b.BorrowID = bd.BorrowID
        JOIN BookCopies bc ON bd.CopyID = bc.CopyID
        JOIN Books bk ON bc.BookID = bk.BookID";
$params = [];
if ($status !== '') {
    $sql .= " WHERE b.Status = :st";
    $params['st'] = $status;
}
$sql .= " GROUP BY b.BorrowID ORDER BY b.BorrowDate DESC";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$borrowings = $stmt->fetchAll();

$pageTitle = 'จัดการการยืม';
require __DIR__ . '/../includes/header.php';
?>
<h4 class="fw-bold mb-3">📗 จัดการการยืม</h4>

<div class="card p-3 mb-3">
  <h6 class="fw-bold">สร้างรายการยืมใหม่ (ที่เคาน์เตอร์)</h6>
  <form method="post" class="row g-2">
    <input type="hidden" name="action" value="create">
    <div class="col-md-4">
      <select name="user_id" class="form-select" required>
        <option value="">-- เลือกสมาชิก --</option>
        <?php foreach ($members as $m): ?>
          <option value="<?= (int)$m['UserID'] ?>"><?= e($m['FullName']) ?> (<?= e($m['Username']) ?>)</option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-4">
      <select name="copy_id" class="form-select" required>
        <option value="">-- เลือกเล่มที่ว่าง --</option>
        <?php foreach ($availableCopies as $c): ?>
          <option value="<?= (int)$c['CopyID'] ?>"><?= e($c['Title']) ?> (<?= e($c['Barcode']) ?>)</option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-2"><input type="number" name="days" class="form-control" placeholder="กำหนดยืม (วัน)" value="7"></div>
    <div class="col-md-2"><button class="btn btn-brand w-100" type="submit">บันทึกการยืม</button></div>
  </form>
</div>

<div class="mb-2">
  <a href="?status=" class="btn btn-sm <?= $status===''?'btn-brand':'btn-outline-secondary' ?>">ทั้งหมด</a>
  <a href="?status=Borrowed" class="btn btn-sm <?= $status==='Borrowed'?'btn-brand':'btn-outline-secondary' ?>">กำลังยืม</a>
  <a href="?status=Returned" class="btn btn-sm <?= $status==='Returned'?'btn-brand':'btn-outline-secondary' ?>">คืนแล้ว</a>
</div>

<div class="card p-3">
<div class="table-responsive">
<table class="table table-hover align-middle">
  <thead><tr><th>เลขที่</th><th>สมาชิก</th><th>หนังสือ</th><th>วันที่ยืม</th><th>กำหนดคืน</th><th>สถานะ</th></tr></thead>
  <tbody>
  <?php foreach ($borrowings as $b): ?>
    <?php $overdue = $b['Status']==='Borrowed' && strtotime($b['DueDate']) < strtotime(date('Y-m-d')); ?>
    <tr>
      <td>#<?= (int)$b['BorrowID'] ?></td>
      <td><?= e($b['FullName']) ?></td>
      <td><?= e($b['Titles']) ?></td>
      <td><?= e($b['BorrowDate']) ?></td>
      <td><?= e($b['DueDate']) ?></td>
      <td>
        <?php if ($overdue): ?>
          <span class="badge badge-borrowed">เกินกำหนด</span>
        <?php else: ?>
          <span class="badge <?= $b['Status']==='Borrowed'?'bg-primary':'bg-secondary' ?>"><?= e($b['Status']) ?></span>
        <?php endif; ?>
      </td>
    </tr>
  <?php endforeach; ?>
  <?php if (!$borrowings): ?>
    <tr><td colspan="6" class="text-center text-muted">ไม่มีข้อมูล</td></tr>
  <?php endif; ?>
  </tbody>
</table>
</div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
