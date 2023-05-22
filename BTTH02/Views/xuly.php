<?php
session_start(); // Khởi động phiên

if (isset($_POST['dangnhap'])) { // Kiểm tra nút đăng nhập đã được nhấn hay chưa
    include('..\includes\database-connection.php'); // Kết nối đến cơ sở dữ liệu

    $username = addslashes($_POST['username']); // Tên đăng nhập được nhập từ biểu mẫu
    $password = addslashes($_POST['password']); // Mật khẩu được nhập từ biểu mẫu

    if (!$username || !$password) { // Kiểm tra xem đã nhập đủ tên đăng nhập và mật khẩu hay chưa
        echo "Vui lòng nhập đầy đủ tên đăng nhập và mật khẩu. <a href='javascript: history.go(-1)'>Trở lại</a>";
        exit;
    }

    $password = md5($password); // Mã hóa mật khẩu bằng md5

    // Tìm tên đăng nhập trong cơ sở dữ liệu
    $query = "SELECT username, password FROM users WHERE username='$username'";
    $result = mysqli_query($connect, $query) or die( mysqli_error($connect));

    // Kiểm tra đăng nhập
    if (!$result) {
        echo "Tên đăng nhập hoặc mật khẩu không đúng!";
    } else {
        // Lấy mật khẩu trong database ra
        $row = mysqli_fetch_array($result);

        // Kiểm tra xem hai mật khẩu có khớp với nhau hay không
        if ($password != $row['password']) {
            echo "Mật khẩu không đúng. Vui lòng nhập lại. <a href='javascript: history.go(-1)'>Trở lại</a>";
            exit;
        }
        $role = $row['role'];

// Lưu tên đăng nhập vào biến session
$_SESSION['username'] = $username;

// Chuyển hướng đến trang thích hợp
switch ($role) {
    case 'student':
        header("Location: students\index.php");
        exit();
        break;

    case 'instructor':
        header("Location: instructors\index.php");
        exit();
        break;

    case 'admin':
        header("Location: index.php");
        exit();
        break;

    default:
        echo "Không xác định được quyền hạn.";
        exit;
}
        // Lưu tên đăng nhập vào biến session
        $_SESSION['username'] = $username;
        echo "Xin chào <b>" .$username . "</b>. Bạn đã đăng nhập thành công. <a href=''>Thoát</a>";
        die(); // Kết thúc chương trình
    }

    $connect->close(); // Đóng kết nối
}