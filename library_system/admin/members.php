<?php
require_once __DIR__ . '/../includes/functions.php';
requireRole('Admin');
$db = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_id'])) {
    $id = (int)$_POST['toggle_id'];
    $stmt = $db->prepare("UPDATE Users SET Status = IF(Status='Active','Suspended','Active') WHERE UserID = :id");
    $stmt->execute(['id' => $id]);
    flash('success', 'อัปเดตสถานะสมาชิกสำเร็จ');
    redirect('/admin/members.php');
}

$q = trim($_GET['q'] ?? '');
$sql = "SELECT u.*, r.RoleName,
        (SELECT COUNT(*) FROM Borrowings b WHERE b.UserID=u.UserID) AS BorrowCount
        FROM Users u JOIN Roles r ON u.RoleID = r.RoleID
        WHERE r.RoleName = 'Member'";
$params = [];
if ($q !== '') {
    $sql .= " AND (u.FullName LIKE :q OR u.Username LIKE :q OR u.Email LIKE :q)";
    $params['q'] = "%$q%";
}
$sql .= " ORDER BY u.RegisterDate DESC";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$members = $stmt->fetchAll();

$pageTitle = 'จัดการสมาชิก';
require __DIR__ . '/../includes/header.php';
?>
<h4 class="fw-bold mb-3">👥 จัดการสมาชิก</h4>
<form method="get" class="mb-3">
  <input type="text" name="q" class="form-control" placeholder="ค้นหาชื่อ/username/email" value="<?= e($q) ?>">
</form>
<div class="card p-3">
<div class="table-responsive">
<table class="table table-hover align-middle">
  <thead><tr><th>Username</th><th>ชื่อ-นามสกุล</th><th>อีเมล</th><th>เบอร์โทร</th><th>สมัครเมื่อ</th><th>ยืมสะสม</th><th>สถานะ</th><th></th></tr></thead>
  <tbody>
  <?php foreach ($members as $m): ?>
    <tr>
      <td><?= e($m['Username']) ?></td>
      <td><?= e($m['FullName']) ?></td>
      <td><?= e($m['Email']) ?></td>
      <td><?= e($m['Phone']) ?></td>
      <td><?= e(substr($m['RegisterDate'],0,10)) ?></td>
      <td><?= (int)$m['BorrowCount'] ?></td>
      <td>
        <span class="badge <?= $m['Status']==='Active' ? 'bg-success' : 'bg-danger' ?>"><?= e($m['Status']) ?></span>
      </td>
      <td>
        <form method="post" onsubmit="return confirm('ยืนยันเปลี่ยนสถานะสมาชิก?');">
          <input type="hidden" name="toggle_id" value="<?= (int)$m['UserID'] ?>">
          <button class="btn btn-sm btn-outline-secondary" type="submit">
            <?= $m['Status']==='Active' ? 'ระงับ' : 'เปิดใช้งาน' ?>
          </button>
        </form>
      </td>
    </tr>
  <?php endforeach; ?>
  <?php if (!$members): ?>
    <tr><td colspan="8" class="text-center text-muted">ไม่พบสมาชิก</td></tr>
  <?php endif; ?>
  </tbody>
</table>
</div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
