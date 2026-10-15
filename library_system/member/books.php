<?php
require_once __DIR__ . '/../includes/functions.php';
requireRole('Member');
$db = getDB();

$q = trim($_GET['q'] ?? '');
$categoryId = $_GET['category'] ?? '';

$categories = $db->query('SELECT * FROM Categories ORDER BY CategoryName')->fetchAll();

$sql = "SELECT b.BookID, b.Title, b.ISBN, b.Price, c.CategoryName,
               GROUP_CONCAT(DISTINCT a.AuthorName SEPARATOR ', ') AS Authors,
               SUM(CASE WHEN bc.Status = 'Available' THEN 1 ELSE 0 END) AS AvailableCopies,
               COUNT(bc.CopyID) AS TotalCopies
        FROM Books b
        JOIN Categories c ON b.CategoryID = c.CategoryID
        LEFT JOIN BookAuthors ba ON b.BookID = ba.BookID
        LEFT JOIN Authors a ON ba.AuthorID = a.AuthorID
        LEFT JOIN BookCopies bc ON b.BookID = bc.BookID
        WHERE 1=1";
$params = [];

if ($q !== '') {
    $sql .= " AND (b.Title LIKE :q OR b.ISBN LIKE :q OR a.AuthorName LIKE :q OR c.CategoryName LIKE :q)";
    $params['q'] = "%$q%";
}
if ($categoryId !== '') {
    $sql .= " AND b.CategoryID = :cat";
    $params['cat'] = $categoryId;
}
$sql .= " GROUP BY b.BookID, b.Title, b.ISBN, b.Price, c.CategoryName ORDER BY b.Title";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$books = $stmt->fetchAll();

$pageTitle = 'ค้นหาหนังสือ';
require __DIR__ . '/../includes/header.php';
?>
<h4 class="fw-bold mb-3">🔍 ค้นหาหนังสือ</h4>

<form method="get" class="row g-2 mb-4">
  <div class="col-md-6">
    <input type="text" name="q" class="form-control" placeholder="ชื่อหนังสือ / ISBN / ผู้แต่ง / สำนักพิมพ์" value="<?= e($q) ?>">
  </div>
  <div class="col-md-3">
    <select name="category" class="form-select">
      <option value="">ทุกหมวดหมู่</option>
      <?php foreach ($categories as $c): ?>
        <option value="<?= (int)$c['CategoryID'] ?>" <?= $categoryId == $c['CategoryID'] ? 'selected' : '' ?>>
          <?= e($c['CategoryName']) ?>
        </option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="col-md-3">
    <button class="btn btn-brand w-100" type="submit">ค้นหา</button>
  </div>
</form>

<div class="row g-3">
<?php if (!$books): ?>
  <p class="text-muted">ไม่พบหนังสือที่ค้นหา</p>
<?php endif; ?>
<?php foreach ($books as $b): ?>
  <div class="col-md-4">
    <div class="card p-3 h-100">
      <div class="d-flex gap-3">
        <div class="book-cover-placeholder">📖</div>
        <div>
          <h6 class="fw-bold mb-1"><?= e($b['Title']) ?></h6>
          <div class="small text-muted mb-1"><?= e($b['Authors'] ?: 'ไม่ระบุผู้แต่ง') ?></div>
          <span class="badge bg-light text-dark border"><?= e($b['CategoryName']) ?></span>
        </div>
      </div>
      <hr>
      <div class="small mb-2">
        ISBN: <?= e($b['ISBN']) ?><br>
        ราคา: <?= money($b['Price']) ?> ฿<br>
        พร้อมให้ยืม: <b><?= (int)$b['AvailableCopies'] ?></b> / <?= (int)$b['TotalCopies'] ?> เล่ม
      </div>
      <a href="<?= basePath() ?>/member/book_detail.php?id=<?= (int)$b['BookID'] ?>" class="btn btn-brand btn-sm mt-auto">ดูรายละเอียด</a>
    </div>
  </div>
<?php endforeach; ?>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
