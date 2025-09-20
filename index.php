<?php
// index.php
session_start();
require_once 'config/db.php';
require_once 'app/controllers/AuthController.php';

$controller = new AuthController();
$controller->handleLogin();
?>