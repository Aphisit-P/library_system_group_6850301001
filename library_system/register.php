<?php
require_once __DIR__ . '/includes/functions.php';

if (isLoggedIn()) {
    redirect($_SESSION['role_name'] === 'Admin' ? '/admin/dashboard.php' : '/member/dashboard.php');
}

$errors = [];
$old = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $old = $_POST;
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm'] ?? '';
    $fullName = trim($_POST['full_name'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $phone    = trim($_POST['phone'] ?? '');
    $address  = trim($_POST['address'] ?? '');

    if ($username === '' || $password === '' || $fullName === '') {
        $errors[] = 'กรุณากรอกข้อมูลที่จำเป็นให้ครบ (Username, Password, ชื่อ-นามสกุล)';
    }
    if (strlen($password) < 6) {
        $errors[] = 'รหัสผ่านต้องมีอย่างน้อย 6 ตัวอักษร';
    }
    if ($password !== $confirm) {
        $errors[] = 'ยืนยันรหัสผ่านไม่ตรงกัน';
    }

    if (!$errors) {
        $db = getDB();
        $stmt = $db->prepare('SELECT UserID FROM Users WHERE Username = :u');
        $stmt->execute(['u' => $username]);
        if ($stmt->fetch()) {
            $errors[] = 'Username นี้ถูกใช้งานแล้ว';
        }
    }

    if (!$errors) {
        $db = getDB();
        $memberRoleId = $db->query("SELECT RoleID FROM Roles WHERE RoleName = 'Member'")->fetchColumn();
        $stmt = $db->prepare(
            'INSERT INTO Users (Username, PasswordHash, FullName, Email, Phone, Address, RoleID, Status)
             VALUES (:username, :password, :full_name, :email, :phone, :address, :role_id, "Active")'
        );
        $stmt->execute([
            'username'  => $username,
            'password'  => password_hash($password, PASSWORD_DEFAULT),
            'full_name' => $fullName,
            'email'     => $email ?: null,
            'phone'     => $phone ?: null,
            'address'   => $address ?: null,
            'role_id'   => $memberRoleId,
        ]);
        flash('success', 'สมัครสมาชิกสำเร็จ กรุณาเข้าสู่ระบบ');
        redirect('/login.php');
    }
}

$pageTitle = 'สมัครสมาชิก';
require __DIR__ . '/includes/header.php';
?>
<div class="auth-wrapper">
  <div class="card auth-card p-4">
    <h3 class="fw-bold text-center mb-3">📝 สมัครสมาชิก</h3>
    <?php if ($errors): ?>
      <div class="alert alert-danger">
        <ul class="mb-0">
        <?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?>
        </ul>
      </div>
    <?php endif; ?>
    <form method="post">
      <div class="mb-2">
        <label class="form-label">Username *</label>
        <input type="text" name="username" class="form-control" required value="<?= e($old['username'] ?? '') ?>">
      </div>
      <div class="row">
        <div class="col-6 mb-2">
          <label class="form-label">Password *</label>
          <input type="password" name="password" class="form-control" required>
        </div>
        <div class="col-6 mb-2">
          <label class="form-label">ยืนยันรหัสผ่าน *</label>
          <input type="password" name="confirm" class="form-control" required>
        </div>
      </div>
      <div class="mb-2">
        <label class="form-label">ชื่อ-นามสกุล *</label>
        <input type="text" name="full_name" class="form-control" required value="<?= e($old['full_name'] ?? '') ?>">
      </div>
      <div class="mb-2">
        <label class="form-label">อีเมล</label>
        <input type="email" name="email" class="form-control" value="<?= e($old['email'] ?? '') ?>">
      </div>
      <div class="mb-2">
        <label class="form-label">เบอร์โทร</label>
        <input type="text" name="phone" class="form-control" value="<?= e($old['phone'] ?? '') ?>">
      </div>
      <div class="mb-3">
        <label class="form-label">ที่อยู่</label>
        <textarea name="address" class="form-control" rows="2"><?= e($old['address'] ?? '') ?></textarea>
      </div>
      <button type="submit" class="btn btn-brand w-100">สมัครสมาชิก</button>
    </form>
    <p class="text-center mt-3 mb-0">มีบัญชีอยู่แล้ว? <a href="<?= basePath() ?>/login.php">เข้าสู่ระบบ</a></p>
  </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
