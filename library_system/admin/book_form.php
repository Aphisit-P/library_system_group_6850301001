<?php
require_once __DIR__ . '/../includes/functions.php';
requireRole('Admin');
$db = getDB();

$bookId = (int)($_GET['id'] ?? 0);
$isEdit = $bookId > 0;
$book = ['Title'=>'','ISBN'=>'','Publisher'=>'','PublishYear'=>'','Description'=>'','Price'=>'','CategoryID'=>''];
$selectedAuthorIds = [];

if ($isEdit) {
    $stmt = $db->prepare('SELECT * FROM Books WHERE BookID = :id');
    $stmt->execute(['id' => $bookId]);
    $book = $stmt->fetch();
    if (!$book) { flash('error', 'ไม่พบหนังสือ'); redirect('/admin/books.php'); }

    $aStmt = $db->prepare('SELECT AuthorID FROM BookAuthors WHERE BookID = :id');
    $aStmt->execute(['id' => $bookId]);
    $selectedAuthorIds = array_column($aStmt->fetchAll(), 'AuthorID');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title']);
    $isbn = trim($_POST['isbn']) ?: null;
    $publisher = trim($_POST['publisher']) ?: null;
    $publishYear = $_POST['publish_year'] ?: null;
    $description = trim($_POST['description']) ?: null;
    $price = $_POST['price'];
    $categoryId = (int)$_POST['category_id'];
    $authorIds = $_POST['author_ids'] ?? [];

    if ($isEdit) {
        $stmt = $db->prepare(
            'UPDATE Books SET ISBN=:isbn, Title=:t, Publisher=:pub, PublishYear=:py,
             Description=:d, Price=:pr, CategoryID=:c WHERE BookID=:id'
        );
        $stmt->execute(['isbn'=>$isbn,'t'=>$title,'pub'=>$publisher,'py'=>$publishYear,
                         'd'=>$description,'pr'=>$price,'c'=>$categoryId,'id'=>$bookId]);
        $db->prepare('DELETE FROM BookAuthors WHERE BookID=:id')->execute(['id' => $bookId]);
    } else {
        $stmt = $db->prepare(
            'INSERT INTO Books (ISBN, Title, Publisher, PublishYear, Description, Price, CategoryID)
             VALUES (:isbn, :t, :pub, :py, :d, :pr, :c)'
        );
        $stmt->execute(['isbn'=>$isbn,'t'=>$title,'pub'=>$publisher,'py'=>$publishYear,
                         'd'=>$description,'pr'=>$price,'c'=>$categoryId]);
        $bookId = $db->lastInsertId();
    }

    if ($authorIds) {
        $insA = $db->prepare('INSERT INTO BookAuthors (BookID, AuthorID) VALUES (:b, :a)');
        foreach ($authorIds as $aid) {
            $insA->execute(['b' => $bookId, 'a' => (int)$aid]);
        }
    }

    flash('success', $isEdit ? 'แก้ไขหนังสือสำเร็จ' : 'เพิ่มหนังสือสำเร็จ กรุณาเพิ่มจำนวนเล่ม (Copies) ต่อไป');
    redirect('/admin/books.php');
}

$categories = $db->query('SELECT * FROM Categories ORDER BY CategoryName')->fetchAll();
$authors = $db->query('SELECT * FROM Authors ORDER BY AuthorName')->fetchAll();

$pageTitle = $isEdit ? 'แก้ไขหนังสือ' : 'เพิ่มหนังสือ';
require __DIR__ . '/../includes/header.php';
?>
<h4 class="fw-bold mb-3"><?= $isEdit ? '✏️ แก้ไขหนังสือ' : '+ เพิ่มหนังสือใหม่' ?></h4>

<div class="card p-4">
<form method="post">
  <div class="row">
    <div class="col-md-8 mb-2">
      <label class="form-label">ชื่อหนังสือ *</label>
      <input name="title" class="form-control" required value="<?= e($book['Title']) ?>">
    </div>
    <div class="col-md-4 mb-2">
      <label class="form-label">ISBN</label>
      <input name="isbn" class="form-control" value="<?= e($book['ISBN']) ?>">
    </div>
  </div>
  <div class="row">
    <div class="col-md-4 mb-2">
      <label class="form-label">สำนักพิมพ์</label>
      <input name="publisher" class="form-control" value="<?= e($book['Publisher']) ?>">
    </div>
    <div class="col-md-4 mb-2">
      <label class="form-label">ปีที่พิมพ์</label>
      <input type="number" name="publish_year" class="form-control" value="<?= e((string)$book['PublishYear']) ?>">
    </div>
    <div class="col-md-4 mb-2">
      <label class="form-label">ราคา *</label>
      <input type="number" step="0.01" name="price" class="form-control" required value="<?= e((string)$book['Price']) ?>">
    </div>
  </div>
  <div class="mb-2">
    <label class="form-label">หมวดหมู่ *</label>
    <select name="category_id" class="form-select" required>
      <option value="">-- เลือกหมวดหมู่ --</option>
      <?php foreach ($categories as $c): ?>
        <option value="<?= (int)$c['CategoryID'] ?>" <?= $book['CategoryID'] == $c['CategoryID'] ? 'selected' : '' ?>>
          <?= e($c['CategoryName']) ?>
        </option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="mb-2">
    <label class="form-label">ผู้แต่ง (เลือกได้หลายคน)</label>
    <select name="author_ids[]" class="form-select" multiple size="5">
      <?php foreach ($authors as $a): ?>
        <option value="<?= (int)$a['AuthorID'] ?>" <?= in_array($a['AuthorID'], $selectedAuthorIds) ? 'selected' : '' ?>>
          <?= e($a['AuthorName']) ?>
        </option>
      <?php endforeach; ?>
    </select>
    <div class="form-text">ยังไม่มีผู้แต่งที่ต้องการ? <a href="<?= basePath() ?>/admin/authors.php">เพิ่มผู้แต่งใหม่</a></div>
  </div>
  <div class="mb-3">
    <label class="form-label">รายละเอียด</label>
    <textarea name="description" class="form-control" rows="4"><?= e($book['Description']) ?></textarea>
  </div>
  <button type="submit" class="btn btn-brand">บันทึก</button>
  <a href="<?= basePath() ?>/admin/books.php" class="btn btn-outline-secondary">ยกเลิก</a>
</form>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
