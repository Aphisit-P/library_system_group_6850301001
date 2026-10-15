<?php
require_once __DIR__ . '/../includes/functions.php';
requireRole('Member');
$db = getDB();
$uid = $_SESSION['user_id'];

$bookId = (int)($_GET['id'] ?? 0);

$stmt = $db->prepare(
    "SELECT b.*, c.CategoryName,
            GROUP_CONCAT(DISTINCT a.AuthorName SEPARATOR ', ') AS Authors
     FROM Books b
     JOIN Categories c ON b.CategoryID = c.CategoryID
     LEFT JOIN BookAuthors ba ON b.BookID = ba.BookID
     LEFT JOIN Authors a ON ba.AuthorID = a.AuthorID
     WHERE b.BookID = :id
     GROUP BY b.BookID"
);
$stmt->execute(['id' => $bookId]);
$book = $stmt->fetch();
if (!$book) { flash('error', 'ไม่พบหนังสือ'); redirect('/member/books.php'); }

$copiesStmt = $db->prepare('SELECT * FROM BookCopies WHERE BookID = :id ORDER BY CopyID');
$copiesStmt->execute(['id' => $bookId]);
$copies = $copiesStmt->fetchAll();
$availableCopies = array_filter($copies, fn($c) => $c['Status'] === 'Available');

// ---- Borrow action ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'borrow') {
    $copyId = (int)$_POST['copy_id'];
    $db->beginTransaction();
    try {
        $check = $db->prepare("SELECT Status FROM BookCopies WHERE CopyID = :id FOR UPDATE");
        $check->execute(['id' => $copyId]);
        $status = $check->fetchColumn();
        if ($status !== 'Available') {
            throw new Exception('เล่มนี้ถูกยืมไปแล้ว กรุณาเลือกเล่มอื่น');
        }
        $borrowDate = date('Y-m-d');
        $dueDate = date('Y-m-d', strtotime('+7 days'));
        $ins = $db->prepare('INSERT INTO Borrowings (UserID, BorrowDate, DueDate, Status) VALUES (:u, :bd, :dd, "Borrowed")');
        $ins->execute(['u' => $uid, 'bd' => $borrowDate, 'dd' => $dueDate]);
        $borrowId = $db->lastInsertId();

        $ins2 = $db->prepare('INSERT INTO BorrowingDetails (BorrowID, CopyID) VALUES (:b, :c)');
        $ins2->execute(['b' => $borrowId, 'c' => $copyId]);

        $upd = $db->prepare("UPDATE BookCopies SET Status = 'Borrowed' WHERE CopyID = :id");
        $upd->execute(['id' => $copyId]);

        $db->commit();
        flash('success', "ยืมหนังสือสำเร็จ กำหนดคืนวันที่ $dueDate");
    } catch (Exception $e) {
        $db->rollBack();
        flash('error', $e->getMessage());
    }
    redirect('/member/book_detail.php?id=' . $bookId);
}

// ---- Reserve action ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'reserve') {
    $ins = $db->prepare('INSERT INTO Reservations (UserID, BookID, Status) VALUES (:u, :b, "Waiting")');
    $ins->execute(['u' => $uid, 'b' => $bookId]);
    flash('success', 'จองหนังสือสำเร็จ ระบบจะแจ้งเตือนเมื่อมีเล่มว่าง');
    redirect('/member/book_detail.php?id=' . $bookId);
}

$pageTitle = $book['Title'];
require __DIR__ . '/../includes/header.php';
?>
<a href="<?= basePath() ?>/member/books.php" class="btn btn-sm btn-outline-secondary mb-3">&larr; กลับไปค้นหา</a>

<div class="card p-4">
  <div class="row">
    <div class="col-md-2 mb-3">
      <div class="book-cover-placeholder" style="height:150px;width:100%;font-size:2.5rem;">📖</div>
    </div>
    <div class="col-md-7">
      <h4 class="fw-bold"><?= e($book['Title']) ?></h4>
      <p class="text-muted mb-1">ผู้แต่ง: <?= e($book['Authors'] ?: 'ไม่ระบุ') ?></p>
      <p class="mb-1"><span class="badge bg-light text-dark border"><?= e($book['CategoryName']) ?></span></p>
      <p class="mb-1">ISBN: <?= e($book['ISBN']) ?></p>
      <p class="mb-1">สำนักพิมพ์: <?= e($book['Publisher']) ?> (<?= e((string)$book['PublishYear']) ?>)</p>
      <p class="mb-1">ราคา: <?= money($book['Price']) ?> ฿</p>
      <p class="mt-2"><?= nl2br(e($book['Description'])) ?></p>
    </div>
    <div class="col-md-3 text-center">
      <div class="stat-card <?= $availableCopies ? 'bg-grad-3' : 'bg-grad-4' ?>">
        <p>พร้อมให้ยืม</p>
        <h3><?= count($availableCopies) ?> / <?= count($copies) ?></h3>
      </div>
      <?php if ($availableCopies): ?>
        <form method="post" class="mt-3">
          <input type="hidden" name="action" value="borrow">
          <select name="copy_id" class="form-select mb-2" required>
            <?php foreach ($availableCopies as $c): ?>
              <option value="<?= (int)$c['CopyID'] ?>"><?= e($c['Barcode']) ?> (<?= e($c['ShelfLocation']) ?>)</option>
            <?php endforeach; ?>
          </select>
          <button class="btn btn-brand w-100" type="submit">ยืมหนังสือเล่มนี้ (7 วัน)</button>
        </form>
      <?php else: ?>
        <form method="post" class="mt-3">
          <input type="hidden" name="action" value="reserve">
          <button class="btn btn-outline-warning w-100" type="submit">🔔 จองหนังสือ (รอเล่มว่าง)</button>
        </form>
      <?php endif; ?>
    </div>
  </div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
