<?php
require_once 'includes/functions.php';
startSession();
session_destroy();
setcookie(SESSION_NAME, '', time() - 3600, '/');
header('Location: ' . SITE_URL . '/');
exit();
