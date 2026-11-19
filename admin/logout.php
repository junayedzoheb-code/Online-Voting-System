<?php
require_once __DIR__ . '/../includes/functions.php';
start_session('ADMINSESSID');
$_SESSION = [];
session_destroy();
redirect('index.php');
