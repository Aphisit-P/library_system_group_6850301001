<?php
require_once __DIR__ . '/includes/functions.php';

if (isLoggedIn()) {
    redirect($_SESSION['role_name'] === 'Admin' ? '/admin/dashboard.php' : '/member/dashboard.php');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {
        $error = 'กรุณากรอก Username และ Password ให้ครบ';
    } else {
        $db = getDB();
        $stmt = $db->prepare(
            'SELECT u.UserID, u.Username, u.PasswordHash, u.FullName, u.Status, r.RoleName
             FROM Users u JOIN Roles r ON u.RoleID = r.RoleID
             WHERE u.Username = :username'
        );
        $stmt->execute(['username' => $username]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($password, $user['PasswordHash'])) {
            $error = 'Username หรือ Password ไม่ถูกต้อง';
        } elseif ($user['Status'] !== 'Active') {
            $error = 'บัญชีนี้ถูกระงับการใช้งาน กรุณาติดต่อผู้ดูแลระบบ';
        } else {
            $_SESSION['user_id']   = $user['UserID'];
            $_SESSION['username']  = $user['Username'];
            $_SESSION['full_name'] = $user['FullName'];
            $_SESSION['role_name'] = $user['RoleName'];
            redirect($user['RoleName'] === 'Admin' ? '/admin/dashboard.php' : '/member/dashboard.php');
        }
    }
}

$pageTitle = 'เข้าสู่ระบบ';
require __DIR__ . '/includes/header.php';
?>
<div class="auth-wrapper">
  <div class="card auth-card p-4">
    <div class="text-center mb-3">
      <h3 class="fw-bold">📚 เข้าสู่ระบบ</h3>
      <p class="text-muted mb-0">ระบบจัดการห้องสมุดและการยืม-คืนหนังสือ</p>
    </div>
    <?php if ($error): ?>
      <div class="alert alert-danger"><?= e($error) ?></div>
    <?php endif; ?>
    <form method="post">
      <div class="mb-3">
        <label class="form-label">Username</label>
        <input type="text" name="username" class="form-control" required autofocus value="<?= e($_POST['username'] ?? '') ?>">
      </div>
      <div class="mb-3">
        <label class="form-label">Password</label>
        <input type="password" name="password" class="form-control" required>
      </div>
      <button type="submit" class="btn btn-brand w-100">เข้าสู่ระบบ</button>
    </form>
    <p class="text-center mt-3 mb-0">ยังไม่มีบัญชี? <a href="<?= basePath() ?>/register.php">สมัครสมาชิก</a></p>
    <hr>
    <p class="text-muted small mb-0 text-center">
      ทดสอบ Admin: <code>admin</code> / <code>123456</code><br>
      ทดสอบ Member: <code>member1</code> / <code>123456</code>
    </p>
  </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
