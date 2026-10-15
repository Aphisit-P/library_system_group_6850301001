<?php
require_once __DIR__ . '/../includes/functions.php';
requireRole('Member');
$db = getDB();
$uid = $_SESSION['user_id'];

$stmt = $db->prepare(
    "SELECT b.BorrowID, b.BorrowDate, b.DueDate, b.Status,
            bk.Title, bc.Barcode, r.ReturnDate
     FROM Borrowings b
     JOIN BorrowingDetails bd ON b.BorrowID = bd.BorrowID
     JOIN BookCopies bc ON bd.CopyID = bc.CopyID
     JOIN Books bk ON bc.BookID = bk.BookID
     LEFT JOIN Returns r ON r.BorrowDetailID = bd.BorrowDetailID
     WHERE b.UserID = :u
     ORDER BY b.BorrowDate DESC"
);
$stmt->execute(['u' => $uid]);
$rows = $stmt->fetchAll();

$pageTitle = 'ประวัติการยืม';
require __DIR__ . '/../includes/header.php';
?>
<h4 class="fw-bold mb-3">📖 ประวัติการยืมของฉัน</h4>
<div class="card p-3">
<div class="table-responsive">
<table class="table table-hover align-middle">
  <thead><tr><th>เลขที่</th><th>หนังสือ</th><th>บาร์โค้ด</th><th>วันที่ยืม</th><th>กำหนดคืน</th><th>วันที่คืนจริง</th><th>สถานะ</th></tr></thead>
  <tbody>
  <?php foreach ($rows as $r): ?>
    <?php
      $overdue = !$r['ReturnDate'] && strtotime($r['DueDate']) < strtotime(date('Y-m-d'));
    ?>
    <tr>
      <td>#<?= (int)$r['BorrowID'] ?></td>
      <td><?= e($r['Title']) ?></td>
      <td><?= e($r['Barcode']) ?></td>
      <td><?= e($r['BorrowDate']) ?></td>
      <td><?= e($r['DueDate']) ?></td>
      <td><?= e($r['ReturnDate'] ?? '-') ?></td>
      <td>
        <?php if ($r['ReturnDate']): ?>
          <span class="badge bg-secondary">คืนแล้ว</span>
        <?php elseif ($overdue): ?>
          <span class="badge badge-borrowed">เกินกำหนด</span>
        <?php else: ?>
          <span class="badge bg-primary">กำลังยืม</span>
        <?php endif; ?>
      </td>
    </tr>
  <?php endforeach; ?>
  <?php if (!$rows): ?>
    <tr><td colspan="7" class="text-center text-muted">ยังไม่มีประวัติการยืม</td></tr>
  <?php endif; ?>
  </tbody>
</table>
</div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
