<?php
require_once __DIR__ . '/includes/functions.php';

if (isLoggedIn()) {
    if ($_SESSION['role_name'] === 'Admin') {
        redirect('/admin/dashboard.php');
    } else {
        redirect('/member/dashboard.php');
    }
} else {
    redirect('/login.php');
}
