<?php
require_once __DIR__ . '/includes/functions.php';
$_SESSION = [];
session_destroy();
session_start();
redirect('/login.php');
