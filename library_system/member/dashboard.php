<?php
require_once __DIR__ . '/../includes/functions.php';
requireRole('Member');
$db = getDB();
$uid = $_SESSION['user_id'];

$activeBorrows = $db->prepare(
    "SELECT COUNT(*) FROM Borrowings WHERE UserID = :u AND Status = 'Borrowed'"
);
$activeBorrows->execute(['u' => $uid]);
$activeBorrows = (int)$activeBorrows->fetchColumn();

$unpaidFine = $db->prepare(
    "SELECT COALESCE(SUM(f.Amount),0)
     FROM Fines f
     JOIN Returns r ON f.ReturnID = r.ReturnID
     JOIN BorrowingDetails bd ON r.BorrowDetailID = bd.BorrowDetailID
     JOIN Borrowings b ON bd.BorrowID = b.BorrowID
     WHERE b.UserID = :u AND f.FineStatus = 'Unpaid'"
);
$unpaidFine->execute(['u' => $uid]);
$unpaidFine = (float)$unpaidFine->fetchColumn();

$reservations = $db->prepare(
    "SELECT COUNT(*) FROM Reservations WHERE UserID = :u AND Status = 'Waiting'"
);
$reservations->execute(['u' => $uid]);
$reservations = (int)$reservations->fetchColumn();

$totalBorrowedEver = $db->prepare("SELECT COUNT(*) FROM Borrowings WHERE UserID = :u");
$totalBorrowedEver->execute(['u' => $uid]);
$totalBorrowedEver = (int)$totalBorrowedEver->fetchColumn();

$recent = $db->prepare(
    "SELECT b.BorrowID, b.BorrowDate, b.DueDate, b.Status, bk.Title
     FROM Borrowings b
     JOIN BorrowingDetails bd ON b.BorrowID = bd.BorrowID
     JOIN BookCopies bc ON bd.CopyID = bc.CopyID
     JOIN Books bk ON bc.BookID = bk.BookID
     WHERE b.UserID = :u
     ORDER BY b.BorrowDate DESC
     LIMIT 5"
);
$recent->execute(['u' => $uid]);
$recent = $recent->fetchAll();

$pageTitle = 'หน้าแรกสมาชิก';
require __DIR__ . '/../includes/header.php';
?>
<h4 class="fw-bold mb-3">สวัสดี, <?= e($_SESSION['full_name']) ?> 👋</h4>

<div class="row g-3 mb-4">
  <div class="col-md-3 col-6">
    <div class="stat-card bg-grad-1">
      <p>กำลังยืมอยู่</p><h3><?= $activeBorrows ?></h3>
    </div>
  </div>
  <div class="col-md-3 col-6">
    <div class="stat-card bg-grad-4">
      <p>ค่าปรับค้างชำระ</p><h3><?= money($unpaidFine) ?> ฿</h3>
    </div>
  </div>
  <div class="col-md-3 col-6">
    <div class="stat-card bg-grad-2">
      <p>รายการจองที่รอ</p><h3><?= $reservations ?></h3>
    </div>
  </div>
  <div class="col-md-3 col-6">
    <div class="stat-card bg-grad-3">
      <p>ยืมทั้งหมด (สะสม)</p><h3><?= $totalBorrowedEver ?></h3>
    </div>
  </div>
</div>

<div class="d-flex gap-2 mb-4">
  <a href="<?= basePath() ?>/member/books.php" class="btn btn-brand">🔍 ค้นหาหนังสือ</a>
  <a href="<?= basePath() ?>/member/my_fines.php" class="btn btn-outline-danger">💰 ดูค่าปรับ</a>
</div>

<div class="card p-3">
  <h6 class="fw-bold mb-3">การยืมล่าสุด</h6>
  <?php if (!$recent): ?>
    <p class="text-muted mb-0">ยังไม่มีประวัติการยืม</p>
  <?php else: ?>
  <div class="table-responsive">
  <table class="table table-hover align-middle">
    <thead><tr><th>เลขที่ยืม</th><th>ชื่อหนังสือ</th><th>วันที่ยืม</th><th>กำหนดคืน</th><th>สถานะ</th></tr></thead>
    <tbody>
      <?php foreach ($recent as $r): ?>
      <tr>
        <td>#<?= (int)$r['BorrowID'] ?></td>
        <td><?= e($r['Title']) ?></td>
        <td><?= e($r['BorrowDate']) ?></td>
        <td><?= e($r['DueDate']) ?></td>
        <td><span class="badge <?= $r['Status']==='Borrowed' ? 'badge-borrowed' : 'bg-secondary' ?>"><?= e($r['Status']) ?></span></td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
  </div>
  <?php endif; ?>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
