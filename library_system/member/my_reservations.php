<?php
require_once __DIR__ . '/../includes/functions.php';
requireRole('Member');
$db = getDB();
$uid = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_id'])) {
    $stmt = $db->prepare("UPDATE Reservations SET Status = 'Cancelled' WHERE ReservationID = :id AND UserID = :u AND Status = 'Waiting'");
    $stmt->execute(['id' => (int)$_POST['cancel_id'], 'u' => $uid]);
    flash('success', 'ยกเลิกการจองแล้ว');
    redirect('/member/my_reservations.php');
}

$stmt = $db->prepare(
    "SELECT res.ReservationID, res.ReservationDate, res.Status, bk.Title
     FROM Reservations res
     JOIN Books bk ON res.BookID = bk.BookID
     WHERE res.UserID = :u
     ORDER BY res.ReservationDate DESC"
);
$stmt->execute(['u' => $uid]);
$rows = $stmt->fetchAll();

$pageTitle = 'รายการจอง';
require __DIR__ . '/../includes/header.php';
?>
<h4 class="fw-bold mb-3">🔔 รายการจองของฉัน</h4>
<div class="card p-3">
<div class="table-responsive">
<table class="table table-hover align-middle">
  <thead><tr><th>วันที่จอง</th><th>หนังสือ</th><th>สถานะ</th><th></th></tr></thead>
  <tbody>
  <?php foreach ($rows as $r): ?>
    <tr>
      <td><?= e(substr($r['ReservationDate'],0,10)) ?></td>
      <td><?= e($r['Title']) ?></td>
      <td>
        <?php $badges=['Waiting'=>'bg-warning text-dark','Fulfilled'=>'bg-success','Cancelled'=>'bg-secondary']; ?>
        <span class="badge <?= $badges[$r['Status']] ?? 'bg-secondary' ?>"><?= e($r['Status']) ?></span>
      </td>
      <td>
        <?php if ($r['Status'] === 'Waiting'): ?>
        <form method="post">
          <input type="hidden" name="cancel_id" value="<?= (int)$r['ReservationID'] ?>">
          <button class="btn btn-sm btn-outline-danger" type="submit">ยกเลิก</button>
        </form>
        <?php endif; ?>
      </td>
    </tr>
  <?php endforeach; ?>
  <?php if (!$rows): ?>
    <tr><td colspan="4" class="text-center text-muted">ยังไม่มีรายการจอง</td></tr>
  <?php endif; ?>
  </tbody>
</table>
</div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
