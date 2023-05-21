<?php
session_start();
header('Content-Type: text/html; charset=UTF-8');
if (isset($_POST['dangnhap']))
{
include('database-connection.php');
  
$username = addslashes($_POST['username']);
$password = addslashes($_POST['password']);
  
if (!$username || !$password) {
echo "Vui lòng nhập đầy đủ tên đăng nhập và mật khẩu. <a href='javascript: history.go(-1)'>Trở lại</a>";
exit;
}
  
$password = md5($password);
  
//Kiểm tra tên đăng nhập có tồn tại không
$query = "SELECT username, password FROM users WHERE username='$username'";

$result = mysqli_query($connect, $query) or die( mysqli_error($connect));

if (!$result) {
echo "Tên đăng nhập hoặc mật khẩu không đúng!";
} else {
echo "Đăng nhập thành công!";
}
  
//Lấy mật khẩu trong database ra
$row = mysqli_fetch_array($result);
  
//So sánh 2 mật khẩu có trùng khớp hay không
if ($password != $row['password']) {
echo "Mật khẩu không đúng. Vui lòng nhập lại. <a href='javascript: history.go(-1)'>Trở lại</a>";
exit;
}
  
//Lưu tên đăng nhập
$_SESSION['username'] = $username;
echo "Xin chào <b>" .$username . "</b>. Bạn đã đăng nhập thành công. <a href=''>Thoát</a>";
die();
$connect->close();
}
?>