<?php
require_once __DIR__ . '/../includes/functions.php';
requireRole('Admin');
$db = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'process_return') {
    $borrowDetailId = (int)$_POST['borrow_detail_id'];
    $condition = $_POST['condition']; // Good / Minor / Moderate / Severe / Lost
    $damageDesc = trim($_POST['damage_desc']) ?: null;

    $info = $db->prepare(
        "SELECT bd.BorrowDetailID, bd.CopyID, b.BorrowID, b.DueDate, b.UserID, bk.Price, bk.Title
         FROM BorrowingDetails bd
         JOIN Borrowings b ON bd.BorrowID = b.BorrowID
         JOIN BookCopies bc ON bd.CopyID = bc.CopyID
         JOIN Books bk ON bc.BookID = bk.BookID
         WHERE bd.BorrowDetailID = :id"
    );
    $info->execute(['id' => $borrowDetailId]);
    $row = $info->fetch();

    if (!$row) {
        flash('error', 'ไม่พบรายการยืมนี้');
        redirect('/admin/returns.php');
    }

    $db->beginTransaction();
    try {
        $returnDate = date('Y-m-d');
        $returnCondition = $condition === 'Lost' ? 'Lost' : $condition; // store as-is
        $ins = $db->prepare(
            'INSERT INTO Returns (BorrowDetailID, ReturnDate, BookCondition, DamageDescription)
             VALUES (:bd, :rd, :cond, :dmg)'
        );
        $ins->execute(['bd' => $borrowDetailId, 'rd' => $returnDate, 'cond' => $condition, 'dmg' => $damageDesc]);
        $returnId = $db->lastInsertId();

        // ---- Late fine ----
        $lateDays = max(0, (int)((strtotime($returnDate) - strtotime($row['DueDate'])) / 86400));
        if ($lateDays > 0) {
            $lateFine = calcLateFine($lateDays);
            $f = $db->prepare(
                'INSERT INTO Fines (ReturnID, FineType, Amount, Description, FineStatus)
                 VALUES (:r, "Late", :amt, :desc, "Unpaid")'
            );
            $f->execute(['r' => $returnId, 'amt' => $lateFine, 'desc' => "คืนช้า $lateDays วัน (" . money($lateFine) . " บาท)"]);
        }

        // ---- Damage / Lost fine ----
        if (in_array($condition, ['Minor', 'Moderate', 'Severe'])) {
            $dmgFine = calcDamageFine($condition);
            $f = $db->prepare(
                'INSERT INTO Fines (ReturnID, FineType, Amount, Description, FineStatus)
                 VALUES (:r, "Damage", :amt, :desc, "Unpaid")'
            );
            $f->execute(['r' => $returnId, 'amt' => $dmgFine, 'desc' => "หนังสือเสียหายระดับ $condition" . ($damageDesc ? ": $damageDesc" : '')]);
        } elseif ($condition === 'Lost') {
            $lostFine = (float)$row['Price'] + FINE_LOST_EXTRA;
            $f = $db->prepare(
                'INSERT INTO Fines (ReturnID, FineType, Amount, Description, FineStatus)
                 VALUES (:r, "Lost", :amt, :desc, "Unpaid")'
            );
            $f->execute(['r' => $returnId, 'amt' => $lostFine, 'desc' => "หนังสือหาย: ราคาหนังสือ + ค่าปรับ"]);
        }

        // ---- Update copy status ----
        $newCopyStatus = $condition === 'Lost' ? 'Lost' : ($condition === 'Severe' ? 'Damaged' : 'Available');
        $db->prepare('UPDATE BookCopies SET Status=:s, BookCondition=:c WHERE CopyID=:id')
           ->execute(['s' => $newCopyStatus, 'c' => $condition === 'Lost' ? 'Good' : $condition, 'id' => $row['CopyID']]);

        // ---- Update Borrowing status if all details returned ----
        $remain = $db->prepare(
            "SELECT COUNT(*) FROM BorrowingDetails bd
             LEFT JOIN Returns r ON r.BorrowDetailID = bd.BorrowDetailID
             WHERE bd.BorrowID = :b AND r.ReturnID IS NULL"
        );
        $remain->execute(['b' => $row['BorrowID']]);
        if ((int)$remain->fetchColumn() === 0) {
            $db->prepare("UPDATE Borrowings SET Status='Returned' WHERE BorrowID=:id")->execute(['id' => $row['BorrowID']]);
        }

        // ---- Notify next reservation if copy is available again ----
        if ($newCopyStatus === 'Available') {
            // handled informationally in reservations page (no email system) — status stays 'Waiting' until admin fulfills
        }

        $db->commit();
        flash('success', 'บันทึกการคืนหนังสือสำเร็จ' . ($lateDays > 0 || $condition !== 'Good' ? ' และสร้างรายการค่าปรับแล้ว' : ''));
    } catch (Exception $e) {
        $db->rollBack();
        flash('error', 'เกิดข้อผิดพลาด: ' . $e->getMessage());
    }
    redirect('/admin/returns.php');
}

$pending = $db->query(
    "SELECT bd.BorrowDetailID, b.BorrowID, b.DueDate, u.FullName, bk.Title, bc.Barcode
     FROM BorrowingDetails bd
     JOIN Borrowings b ON bd.BorrowID = b.BorrowID
     JOIN Users u ON b.UserID = u.UserID
     JOIN BookCopies bc ON bd.CopyID = bc.CopyID
     JOIN Books bk ON bc.BookID = bk.BookID
     LEFT JOIN Returns r ON r.BorrowDetailID = bd.BorrowDetailID
     WHERE r.ReturnID IS NULL
     ORDER BY b.DueDate ASC"
)->fetchAll();

$pageTitle = 'จัดการการคืนหนังสือ';
require __DIR__ . '/../includes/header.php';
?>
<h4 class="fw-bold mb-3">📘 จัดการการคืนหนังสือ</h4>
<p class="text-muted">ค่าปรับ: คืนช้า <?= FINE_LATE_PER_DAY ?> บาท/วัน | เสียหาย Minor <?= FINE_DAMAGE_MINOR ?> / Moderate <?= FINE_DAMAGE_MODERATE ?> / Severe <?= FINE_DAMAGE_SEVERE ?> บาท | หนังสือหาย = ราคา + <?= FINE_LOST_EXTRA ?> บาท</p>

<div class="card p-3">
<div class="table-responsive">
<table class="table table-hover align-middle">
  <thead><tr><th>เลขที่ยืม</th><th>สมาชิก</th><th>หนังสือ</th><th>บาร์โค้ด</th><th>กำหนดคืน</th><th style="width:420px;">บันทึกการคืน</th></tr></thead>
  <tbody>
  <?php foreach ($pending as $p): ?>
    <?php $overdue = strtotime($p['DueDate']) < strtotime(date('Y-m-d')); ?>
    <tr>
      <td>#<?= (int)$p['BorrowID'] ?></td>
      <td><?= e($p['FullName']) ?></td>
      <td><?= e($p['Title']) ?></td>
      <td><?= e($p['Barcode']) ?></td>
      <td class="<?= $overdue ? 'text-danger fw-bold' : '' ?>"><?= e($p['DueDate']) ?></td>
      <td>
        <form method="post" class="d-flex gap-1">
          <input type="hidden" name="action" value="process_return">
          <input type="hidden" name="borrow_detail_id" value="<?= (int)$p['BorrowDetailID'] ?>">
          <select name="condition" class="form-select form-select-sm" required>
            <option value="Good">สภาพดี</option>
            <option value="Minor">เสียหายเล็กน้อย (Minor)</option>
            <option value="Moderate">เสียหายปานกลาง (Moderate)</option>
            <option value="Severe">เสียหายมาก (Severe)</option>
            <option value="Lost">หนังสือหาย (Lost)</option>
          </select>
          <input name="damage_desc" class="form-control form-control-sm" placeholder="รายละเอียด (ถ้ามี)">
          <button class="btn btn-sm btn-brand" type="submit">คืน</button>
        </form>
      </td>
    </tr>
  <?php endforeach; ?>
  <?php if (!$pending): ?>
    <tr><td colspan="6" class="text-center text-muted">ไม่มีรายการรอคืน</td></tr>
  <?php endif; ?>
  </tbody>
</table>
</div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
