<?php
require_once __DIR__ . '/../includes/functions.php';
requireRole('Member');
$db = getDB();
$uid = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullName = trim($_POST['full_name'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $phone    = trim($_POST['phone'] ?? '');
    $address  = trim($_POST['address'] ?? '');
    $newPass  = $_POST['new_password'] ?? '';

    $stmt = $db->prepare(
        'UPDATE Users SET FullName = :fn, Email = :em, Phone = :ph, Address = :ad WHERE UserID = :id'
    );
    $stmt->execute(['fn' => $fullName, 'em' => $email ?: null, 'ph' => $phone ?: null, 'ad' => $address ?: null, 'id' => $uid]);

    if ($newPass !== '') {
        if (strlen($newPass) < 6) {
            flash('error', 'บันทึกข้อมูลแล้ว แต่รหัสผ่านใหม่ต้องมีอย่างน้อย 6 ตัวอักษร (ไม่ถูกเปลี่ยน)');
            redirect('/member/profile.php');
        }
        $stmt2 = $db->prepare('UPDATE Users SET PasswordHash = :p WHERE UserID = :id');
        $stmt2->execute(['p' => password_hash($newPass, PASSWORD_DEFAULT), 'id' => $uid]);
    }

    $_SESSION['full_name'] = $fullName;
    flash('success', 'บันทึกข้อมูลส่วนตัวสำเร็จ');
    redirect('/member/profile.php');
}

$stmt = $db->prepare('SELECT * FROM Users WHERE UserID = :id');
$stmt->execute(['id' => $uid]);
$user = $stmt->fetch();

$pageTitle = 'ข้อมูลส่วนตัว';
require __DIR__ . '/../includes/header.php';
?>
<h4 class="fw-bold mb-3">👤 ข้อมูลส่วนตัว</h4>
<div class="card p-4" style="max-width:500px;">
  <form method="post">
    <div class="mb-2">
      <label class="form-label">Username</label>
      <input type="text" class="form-control" value="<?= e($user['Username']) ?>" disabled>
    </div>
    <div class="mb-2">
      <label class="form-label">ชื่อ-นามสกุล</label>
      <input type="text" name="full_name" class="form-control" required value="<?= e($user['FullName']) ?>">
    </div>
    <div class="mb-2">
      <label class="form-label">อีเมล</label>
      <input type="email" name="email" class="form-control" value="<?= e($user['Email']) ?>">
    </div>
    <div class="mb-2">
      <label class="form-label">เบอร์โทร</label>
      <input type="text" name="phone" class="form-control" value="<?= e($user['Phone']) ?>">
    </div>
    <div class="mb-2">
      <label class="form-label">ที่อยู่</label>
      <textarea name="address" class="form-control" rows="2"><?= e($user['Address']) ?></textarea>
    </div>
    <div class="mb-3">
      <label class="form-label">เปลี่ยนรหัสผ่าน (เว้นว่างถ้าไม่ต้องการเปลี่ยน)</label>
      <input type="password" name="new_password" class="form-control">
    </div>
    <button type="submit" class="btn btn-brand w-100">บันทึกข้อมูล</button>
  </form>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
