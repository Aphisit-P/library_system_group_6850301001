<?php
require_once __DIR__ . '/functions.php';
$user = currentUser();
$bp = basePath();
$pageTitle = $pageTitle ?? 'ระบบห้องสมุด';
?>
<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($pageTitle) ?> | Library System</title>
<link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.3/css/bootstrap.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Thai:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= $bp ?>/assets/style.css">
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark app-navbar">
  <div class="container">
    <a class="navbar-brand fw-bold" href="<?= $bp ?>/index.php">📚 Library System</a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#nav">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="nav">
      <?php if ($user): ?>
        <ul class="navbar-nav me-auto mb-2 mb-lg-0">
        <?php if ($user['RoleName'] === 'Member'): ?>
          <li class="nav-item"><a class="nav-link" href="<?= $bp ?>/member/dashboard.php">หน้าแรก</a></li>
          <li class="nav-item"><a class="nav-link" href="<?= $bp ?>/member/books.php">ค้นหาหนังสือ</a></li>
          <li class="nav-item"><a class="nav-link" href="<?= $bp ?>/member/my_borrowings.php">ประวัติการยืม</a></li>
          <li class="nav-item"><a class="nav-link" href="<?= $bp ?>/member/my_fines.php">ค่าปรับ</a></li>
          <li class="nav-item"><a class="nav-link" href="<?= $bp ?>/member/my_reservations.php">รายการจอง</a></li>
          <li class="nav-item"><a class="nav-link" href="<?= $bp ?>/member/profile.php">ข้อมูลส่วนตัว</a></li>
        <?php else: ?>
          <li class="nav-item"><a class="nav-link" href="<?= $bp ?>/admin/dashboard.php">แดชบอร์ด</a></li>
          <li class="nav-item"><a class="nav-link" href="<?= $bp ?>/admin/books.php">หนังสือ</a></li>
          <li class="nav-item"><a class="nav-link" href="<?= $bp ?>/admin/copies.php">เล่ม (Copies)</a></li>
          <li class="nav-item"><a class="nav-link" href="<?= $bp ?>/admin/authors.php">ผู้แต่ง</a></li>
          <li class="nav-item"><a class="nav-link" href="<?= $bp ?>/admin/categories.php">หมวดหมู่</a></li>
          <li class="nav-item"><a class="nav-link" href="<?= $bp ?>/admin/members.php">สมาชิก</a></li>
          <li class="nav-item"><a class="nav-link" href="<?= $bp ?>/admin/borrowings.php">การยืม</a></li>
          <li class="nav-item"><a class="nav-link" href="<?= $bp ?>/admin/returns.php">การคืน</a></li>
          <li class="nav-item"><a class="nav-link" href="<?= $bp ?>/admin/fines.php">ค่าปรับ</a></li>
          <li class="nav-item"><a class="nav-link" href="<?= $bp ?>/admin/reports.php">รายงาน</a></li>
        <?php endif; ?>
        </ul>
        <span class="navbar-text me-3">👤 <?= e($user['FullName']) ?> (<?= e($user['RoleName']) ?>)</span>
        <a href="<?= $bp ?>/logout.php" class="btn btn-outline-light btn-sm">ออกจากระบบ</a>
      <?php endif; ?>
    </div>
  </div>
</nav>
<div class="container py-4">
<?php foreach (['success', 'error', 'info'] as $ftype): ?>
  <?php if ($msg = flash($ftype)): ?>
    <div class="alert alert-<?= $ftype === 'error' ? 'danger' : $ftype ?> alert-dismissible fade show">
      <?= e($msg) ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  <?php endif; ?>
<?php endforeach; ?>
