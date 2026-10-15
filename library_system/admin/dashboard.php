<?php
require_once __DIR__ . '/../includes/functions.php';
requireRole('Admin');
$db = getDB();

$totalBooks   = (int)$db->query("SELECT COUNT(*) FROM Books")->fetchColumn();
$totalCopies  = (int)$db->query("SELECT COUNT(*) FROM BookCopies")->fetchColumn();
$borrowed     = (int)$db->query("SELECT COUNT(*) FROM BookCopies WHERE Status='Borrowed'")->fetchColumn();
$available    = (int)$db->query("SELECT COUNT(*) FROM BookCopies WHERE Status='Available'")->fetchColumn();
$totalMembers = (int)$db->query("SELECT COUNT(*) FROM Users u JOIN Roles r ON u.RoleID=r.RoleID WHERE r.RoleName='Member'")->fetchColumn();
$totalBorrows = (int)$db->query("SELECT COUNT(*) FROM Borrowings")->fetchColumn();
$totalReturns = (int)$db->query("SELECT COUNT(*) FROM Returns")->fetchColumn();
$totalFine    = (float)$db->query("SELECT COALESCE(SUM(Amount),0) FROM Fines")->fetchColumn();
$paidFine     = (float)$db->query("SELECT COALESCE(SUM(Amount),0) FROM Fines WHERE FineStatus='Paid'")->fetchColumn();
$unpaidFine   = (float)$db->query("SELECT COALESCE(SUM(Amount),0) FROM Fines WHERE FineStatus='Unpaid'")->fetchColumn();

$pendingReturns = $db->query(
    "SELECT b.BorrowID, u.FullName, bk.Title, b.DueDate
     FROM Borrowings b
     JOIN Users u ON b.UserID = u.UserID
     JOIN BorrowingDetails bd ON b.BorrowID = bd.BorrowID
     JOIN BookCopies bc ON bd.CopyID = bc.CopyID
     JOIN Books bk ON bc.BookID = bk.BookID
     WHERE b.Status = 'Borrowed' AND b.DueDate < CURDATE()
     ORDER BY b.DueDate ASC LIMIT 5"
)->fetchAll();

$pageTitle = 'แดชบอร์ดผู้ดูแลระบบ';
require __DIR__ . '/../includes/header.php';
?>
<h4 class="fw-bold mb-3">แดชบอร์ดผู้ดูแลระบบ</h4>

<div class="row g-3 mb-3">
  <div class="col-md-3 col-6"><div class="stat-card bg-grad-1"><p>หนังสือทั้งหมด</p><h3><?= $totalBooks ?></h3></div></div>
  <div class="col-md-3 col-6"><div class="stat-card bg-grad-3"><p>เล่มว่างให้ยืม</p><h3><?= $available ?></h3></div></div>
  <div class="col-md-3 col-6"><div class="stat-card bg-grad-4"><p>เล่มถูกยืมอยู่</p><h3><?= $borrowed ?></h3></div></div>
  <div class="col-md-3 col-6"><div class="stat-card bg-grad-2"><p>สมาชิกทั้งหมด</p><h3><?= $totalMembers ?></h3></div></div>
</div>
<div class="row g-3 mb-4">
  <div class="col-md-3 col-6"><div class="stat-card bg-grad-1"><p>การยืมทั้งหมด</p><h3><?= $totalBorrows ?></h3></div></div>
  <div class="col-md-3 col-6"><div class="stat-card bg-grad-3"><p>การคืนทั้งหมด</p><h3><?= $totalReturns ?></h3></div></div>
  <div class="col-md-3 col-6"><div class="stat-card bg-grad-2"><p>ค่าปรับที่ชำระแล้ว</p><h3><?= money($paidFine) ?> ฿</h3></div></div>
  <div class="col-md-3 col-6"><div class="stat-card bg-grad-4"><p>ค่าปรับค้างชำระ</p><h3><?= money($unpaidFine) ?> ฿</h3></div></div>
</div>

<div class="card p-3">
  <h6 class="fw-bold mb-3">⚠️ รายการเกินกำหนดคืน (ล่าสุด 5 รายการ)</h6>
  <?php if (!$pendingReturns): ?>
    <p class="text-muted mb-0">ไม่มีรายการเกินกำหนด</p>
  <?php else: ?>
  <table class="table table-sm">
    <thead><tr><th>เลขที่ยืม</th><th>สมาชิก</th><th>หนังสือ</th><th>กำหนดคืน</th></tr></thead>
    <tbody>
    <?php foreach ($pendingReturns as $p): ?>
      <tr>
        <td>#<?= (int)$p['BorrowID'] ?></td>
        <td><?= e($p['FullName']) ?></td>
        <td><?= e($p['Title']) ?></td>
        <td class="text-danger"><?= e($p['DueDate']) ?></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  <a href="<?= basePath() ?>/admin/returns.php" class="btn btn-sm btn-outline-danger">ไปหน้าจัดการคืนหนังสือ</a>
  <?php endif; ?>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
