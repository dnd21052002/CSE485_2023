<?php session_start();
if ($row['role'] == 'students') {
    $_SESSION['role'] = 'students';
  } else if ($row['role'] == 'instructors') {
    $_SESSION['role'] = 'instructors';
  } else if ($row['role'] == 'admin') {
    $_SESSION['role'] = 'admin';
  }
  if ($_SESSION['role'] == 'students') {
    // Nếu vai trò của người dùng là sinh viên, chuyển hướng đến trang sinh viên
    header('Location: students/index.php');
  } else if ($_SESSION['role'] == 'instructors') {
    // Nếu vai trò của người dùng là giáo viên, chuyển hướng đến trang giáo viên
    header('Location: instructors/index.php');
  } else if ($_SESSION['role'] == 'admin') {
    // Nếu vai trò của người dùng là admin, chuyển hướng đến trang admin
    header('Location: HomeController.php');
  } else {
    // Nếu không có vai trò được xác định, chuyển hướng người dùng đến trang đăng nhập
    header('Location: login.php');
  }