<?php
require_once __DIR__ . '/../includes/functions.php';
requireRole('Admin');
$db = getDB();

// หนังสือที่ถูกยืมมากที่สุด
$topBooks = $db->query(
    "SELECT b.Title, COUNT(*) AS BorrowCount
     FROM BorrowingDetails bd
     JOIN BookCopies bc ON bd.CopyID = bc.CopyID
     JOIN Books b ON bc.BookID = b.BookID
     GROUP BY b.Title
     ORDER BY BorrowCount DESC
     LIMIT 10"
)->fetchAll();

// สมาชิกที่ยืมมากที่สุด
$topMembers = $db->query(
    "SELECT u.FullName, COUNT(*) AS BorrowCount
     FROM Borrowings br
     JOIN Users u ON br.UserID = u.UserID
     GROUP BY u.UserID, u.FullName
     ORDER BY BorrowCount DESC
     LIMIT 10"
)->fetchAll();

// หมวดหนังสือที่ได้รับความนิยม
$topCategories = $db->query(
    "SELECT c.CategoryName, COUNT(*) AS BorrowCount
     FROM BorrowingDetails bd
     JOIN BookCopies bc ON bd.CopyID = bc.CopyID
     JOIN Books b ON bc.BookID = b.BookID
     JOIN Categories c ON b.CategoryID = c.CategoryID
     GROUP BY c.CategoryName
     ORDER BY BorrowCount DESC"
)->fetchAll();

// รายได้จากค่าปรับ
$totalFine = (float)$db->query("SELECT COALESCE(SUM(Amount),0) FROM Fines")->fetchColumn();
$paidFine = (float)$db->query("SELECT COALESCE(SUM(Amount),0) FROM Fines WHERE FineStatus='Paid'")->fetchColumn();
$unpaidFine = (float)$db->query("SELECT COALESCE(SUM(Amount),0) FROM Fines WHERE FineStatus='Unpaid'")->fetchColumn();

// หนังสือที่เสียหาย
$damagedBooks = $db->query(
    "SELECT bk.Title, bc.Barcode, r.BookCondition, r.DamageDescription, r.ReturnDate
     FROM Returns r
     JOIN BorrowingDetails bd ON r.BorrowDetailID = bd.BorrowDetailID
     JOIN BookCopies bc ON bd.CopyID = bc.CopyID
     JOIN Books bk ON bc.BookID = bk.BookID
     WHERE r.BookCondition IN ('Minor','Moderate','Severe','Lost')
     ORDER BY r.ReturnDate DESC"
)->fetchAll();

$pageTitle = 'รายงาน';
require __DIR__ . '/../includes/header.php';
?>
<h4 class="fw-bold mb-3">📊 รายงาน</h4>

<div class="row g-3 mb-4">
  <div class="col-md-4"><div class="stat-card bg-grad-1"><p>ค่าปรับทั้งหมด</p><h3><?= money($totalFine) ?> ฿</h3></div></div>
  <div class="col-md-4"><div class="stat-card bg-grad-3"><p>ชำระแล้ว</p><h3><?= money($paidFine) ?> ฿</h3></div></div>
  <div class="col-md-4"><div class="stat-card bg-grad-4"><p>ค้างชำระ</p><h3><?= money($unpaidFine) ?> ฿</h3></div></div>
</div>

<div class="row g-3">
  <div class="col-md-6">
    <div class="card p-3">
      <h6 class="fw-bold">📚 หนังสือที่ถูกยืมมากที่สุด</h6>
      <table class="table table-sm">
        <thead><tr><th>ชื่อหนังสือ</th><th class="text-end">จำนวนครั้ง</th></tr></thead>
        <tbody>
        <?php foreach ($topBooks as $r): ?>
          <tr><td><?= e($r['Title']) ?></td><td class="text-end"><?= (int)$r['BorrowCount'] ?></td></tr>
        <?php endforeach; ?>
        <?php if (!$topBooks): ?><tr><td colspan="2" class="text-muted text-center">ไม่มีข้อมูล</td></tr><?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
  <div class="col-md-6">
    <div class="card p-3">
      <h6 class="fw-bold">🏆 สมาชิกที่ยืมมากที่สุด</h6>
      <table class="table table-sm">
        <thead><tr><th>สมาชิก</th><th class="text-end">จำนวนครั้ง</th></tr></thead>
        <tbody>
        <?php foreach ($topMembers as $r): ?>
          <tr><td><?= e($r['FullName']) ?></td><td class="text-end"><?= (int)$r['BorrowCount'] ?></td></tr>
        <?php endforeach; ?>
        <?php if (!$topMembers): ?><tr><td colspan="2" class="text-muted text-center">ไม่มีข้อมูล</td></tr><?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
  <div class="col-md-6">
    <div class="card p-3">
      <h6 class="fw-bold">🏷️ หมวดหนังสือยอดนิยม</h6>
      <table class="table table-sm">
        <thead><tr><th>หมวดหมู่</th><th class="text-end">จำนวนครั้งยืม</th></tr></thead>
        <tbody>
        <?php foreach ($topCategories as $r): ?>
          <tr><td><?= e($r['CategoryName']) ?></td><td class="text-end"><?= (int)$r['BorrowCount'] ?></td></tr>
        <?php endforeach; ?>
        <?php if (!$topCategories): ?><tr><td colspan="2" class="text-muted text-center">ไม่มีข้อมูล</td></tr><?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
  <div class="col-md-6">
    <div class="card p-3">
      <h6 class="fw-bold">⚠️ หนังสือที่เสียหาย / สูญหาย</h6>
      <table class="table table-sm">
        <thead><tr><th>หนังสือ</th><th>สภาพ</th><th>วันที่คืน</th></tr></thead>
        <tbody>
        <?php foreach ($damagedBooks as $r): ?>
          <tr><td><?= e($r['Title']) ?> (<?= e($r['Barcode']) ?>)</td><td><?= e($r['BookCondition']) ?></td><td><?= e($r['ReturnDate']) ?></td></tr>
        <?php endforeach; ?>
        <?php if (!$damagedBooks): ?><tr><td colspan="3" class="text-muted text-center">ไม่มีข้อมูล</td></tr><?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
