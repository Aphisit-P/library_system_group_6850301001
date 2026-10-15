<?php
require_once __DIR__ . '/../includes/functions.php';
requireRole('Member');
$db = getDB();
$uid = $_SESSION['user_id'];

// ---- Pay action ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['fine_id'])) {
    $fineId = (int)$_POST['fine_id'];
    $method = $_POST['method'] ?? 'Cash';

    // verify fine belongs to this member and is unpaid
    $chk = $db->prepare(
        "SELECT f.FineID, f.Amount FROM Fines f
         JOIN Returns r ON f.ReturnID = r.ReturnID
         JOIN BorrowingDetails bd ON r.BorrowDetailID = bd.BorrowDetailID
         JOIN Borrowings b ON bd.BorrowID = b.BorrowID
         WHERE f.FineID = :f AND b.UserID = :u AND f.FineStatus = 'Unpaid'"
    );
    $chk->execute(['f' => $fineId, 'u' => $uid]);
    $fine = $chk->fetch();

    if ($fine) {
        $db->beginTransaction();
        $ins = $db->prepare(
            'INSERT INTO Payments (FineID, UserID, Amount, PaymentMethod, Status)
             VALUES (:f, :u, :amt, :method, "Completed")'
        );
        $ins->execute(['f' => $fineId, 'u' => $uid, 'amt' => $fine['Amount'], 'method' => $method]);
        $upd = $db->prepare("UPDATE Fines SET FineStatus = 'Paid' WHERE FineID = :f");
        $upd->execute(['f' => $fineId]);
        $db->commit();
        flash('success', 'ชำระค่าปรับสำเร็จ');
    } else {
        flash('error', 'ไม่พบรายการค่าปรับนี้ หรือชำระไปแล้ว');
    }
    redirect('/member/my_fines.php');
}

$stmt = $db->prepare(
    "SELECT f.FineID, f.FineType, f.Amount, f.Description, f.FineStatus, f.CreatedDate, bk.Title
     FROM Fines f
     JOIN Returns r ON f.ReturnID = r.ReturnID
     JOIN BorrowingDetails bd ON r.BorrowDetailID = bd.BorrowDetailID
     JOIN Borrowings b ON bd.BorrowID = b.BorrowID
     JOIN BookCopies bc ON bd.CopyID = bc.CopyID
     JOIN Books bk ON bc.BookID = bk.BookID
     WHERE b.UserID = :u
     ORDER BY f.CreatedDate DESC"
);
$stmt->execute(['u' => $uid]);
$fines = $stmt->fetchAll();

$totalUnpaid = array_sum(array_map(fn($f) => $f['FineStatus'] === 'Unpaid' ? $f['Amount'] : 0, $fines));

$pageTitle = 'ค่าปรับของฉัน';
require __DIR__ . '/../includes/header.php';
?>
<h4 class="fw-bold mb-3">💰 ค่าปรับของฉัน</h4>

<div class="stat-card bg-grad-4 mb-4" style="max-width:300px;">
  <p>ยอดค้างชำระทั้งหมด</p><h3><?= money($totalUnpaid) ?> ฿</h3>
</div>

<div class="card p-3">
<div class="table-responsive">
<table class="table table-hover align-middle">
  <thead><tr><th>วันที่</th><th>หนังสือ</th><th>ประเภท</th><th>รายละเอียด</th><th>จำนวนเงิน</th><th>สถานะ</th><th></th></tr></thead>
  <tbody>
  <?php foreach ($fines as $f): ?>
    <tr>
      <td><?= e(substr($f['CreatedDate'],0,10)) ?></td>
      <td><?= e($f['Title']) ?></td>
      <td><?= e($f['FineType']) ?></td>
      <td><?= e($f['Description']) ?></td>
      <td><?= money($f['Amount']) ?> ฿</td>
      <td>
        <?php if ($f['FineStatus'] === 'Paid'): ?>
          <span class="badge bg-success">ชำระแล้ว</span>
        <?php else: ?>
          <span class="badge bg-danger">ค้างชำระ</span>
        <?php endif; ?>
      </td>
      <td>
        <?php if ($f['FineStatus'] === 'Unpaid'): ?>
        <form method="post" class="d-flex gap-1">
          <input type="hidden" name="fine_id" value="<?= (int)$f['FineID'] ?>">
          <select name="method" class="form-select form-select-sm">
            <option value="Cash">เงินสด</option>
            <option value="Transfer">โอนเงิน</option>
            <option value="Card">บัตร</option>
          </select>
          <button class="btn btn-sm btn-brand" type="submit">ชำระเงิน</button>
        </form>
        <?php endif; ?>
      </td>
    </tr>
  <?php endforeach; ?>
  <?php if (!$fines): ?>
    <tr><td colspan="7" class="text-center text-muted">ไม่มีค่าปรับ 🎉</td></tr>
  <?php endif; ?>
  </tbody>
</table>
</div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
