<?php
require_once __DIR__ . '/../includes/functions.php';
requireRole('Admin');
$db = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['fine_id'])) {
    // Admin บันทึกว่าชำระด้วยเงินสดที่เคาน์เตอร์
    $fineId = (int)$_POST['fine_id'];
    $f = $db->prepare(
        "SELECT f.Amount, b.UserID FROM Fines f
         JOIN Returns r ON f.ReturnID=r.ReturnID
         JOIN BorrowingDetails bd ON r.BorrowDetailID=bd.BorrowDetailID
         JOIN Borrowings b ON bd.BorrowID=b.BorrowID
         WHERE f.FineID=:id AND f.FineStatus='Unpaid'"
    );
    $f->execute(['id' => $fineId]);
    $row = $f->fetch();
    if ($row) {
        $db->beginTransaction();
        $db->prepare('INSERT INTO Payments (FineID, UserID, Amount, PaymentMethod, Status) VALUES (:f,:u,:a,"Cash","Completed")')
           ->execute(['f' => $fineId, 'u' => $row['UserID'], 'a' => $row['Amount']]);
        $db->prepare("UPDATE Fines SET FineStatus='Paid' WHERE FineID=:id")->execute(['id' => $fineId]);
        $db->commit();
        flash('success', 'บันทึกการชำระค่าปรับสำเร็จ');
    } else {
        flash('error', 'ไม่พบรายการ หรือชำระไปแล้ว');
    }
    redirect('/admin/fines.php');
}

$status = $_GET['status'] ?? '';
$sql = "SELECT f.*, u.FullName, bk.Title
        FROM Fines f
        JOIN Returns r ON f.ReturnID = r.ReturnID
        JOIN BorrowingDetails bd ON r.BorrowDetailID = bd.BorrowDetailID
        JOIN Borrowings b ON bd.BorrowID = b.BorrowID
        JOIN Users u ON b.UserID = u.UserID
        JOIN BookCopies bc ON bd.CopyID = bc.CopyID
        JOIN Books bk ON bc.BookID = bk.BookID";
$params = [];
if ($status !== '') {
    $sql .= " WHERE f.FineStatus = :s";
    $params['s'] = $status;
}
$sql .= " ORDER BY f.CreatedDate DESC";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$fines = $stmt->fetchAll();

$pageTitle = 'จัดการค่าปรับ';
require __DIR__ . '/../includes/header.php';
?>
<h4 class="fw-bold mb-3">💰 จัดการค่าปรับ</h4>
<div class="mb-2">
  <a href="?status=" class="btn btn-sm <?= $status===''?'btn-brand':'btn-outline-secondary' ?>">ทั้งหมด</a>
  <a href="?status=Unpaid" class="btn btn-sm <?= $status==='Unpaid'?'btn-brand':'btn-outline-secondary' ?>">ค้างชำระ</a>
  <a href="?status=Paid" class="btn btn-sm <?= $status==='Paid'?'btn-brand':'btn-outline-secondary' ?>">ชำระแล้ว</a>
</div>
<div class="card p-3">
<div class="table-responsive">
<table class="table table-hover align-middle">
  <thead><tr><th>วันที่</th><th>สมาชิก</th><th>หนังสือ</th><th>ประเภท</th><th>รายละเอียด</th><th>จำนวนเงิน</th><th>สถานะ</th><th></th></tr></thead>
  <tbody>
  <?php foreach ($fines as $f): ?>
    <tr>
      <td><?= e(substr($f['CreatedDate'],0,10)) ?></td>
      <td><?= e($f['FullName']) ?></td>
      <td><?= e($f['Title']) ?></td>
      <td><?= e($f['FineType']) ?></td>
      <td><?= e($f['Description']) ?></td>
      <td><?= money($f['Amount']) ?> ฿</td>
      <td>
        <span class="badge <?= $f['FineStatus']==='Paid'?'bg-success':'bg-danger' ?>"><?= e($f['FineStatus']) ?></span>
      </td>
      <td>
        <?php if ($f['FineStatus']==='Unpaid'): ?>
        <form method="post" onsubmit="return confirm('ยืนยันบันทึกว่าชำระเป็นเงินสดแล้ว?');">
          <input type="hidden" name="fine_id" value="<?= (int)$f['FineID'] ?>">
          <button class="btn btn-sm btn-outline-success" type="submit">รับชำระเงินสด</button>
        </form>
        <?php endif; ?>
      </td>
    </tr>
  <?php endforeach; ?>
  <?php if (!$fines): ?>
    <tr><td colspan="8" class="text-center text-muted">ไม่มีข้อมูล</td></tr>
  <?php endif; ?>
  </tbody>
</table>
</div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
