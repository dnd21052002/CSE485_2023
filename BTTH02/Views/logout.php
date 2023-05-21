<?php
session_start();

// Xóa các biến phiên được lưu trữ
unset($_SESSION['id']);
unset($_SESSION['username']);

// Xóa tất cả các biến phiên
session_destroy();

// Chuyển hướng người dùng đến trang đăng nhập
header("Location: login.php");
exit();
?>