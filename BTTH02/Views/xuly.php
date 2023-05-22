<?php
session_start(); // Khởi động phiên

if ($_SERVER['REQUEST_METHOD'] == 'POST') { // Kiểm tra nút đăng nhập đã được nhấn hay chưa
    include('..\includes\database-connection.php'); // Kết nối đến cơ sở dữ liệu

    $username = $_POST['username']; // Tên đăng nhập được nhập từ biểu mẫu
    $password = $_POST['password']; // Mật khẩu được nhập từ biểu mẫu

    if (!$username || !$password) { // Kiểm tra xem đã nhập đủ tên đăng nhập và mật khẩu hay chưa
        echo "Vui lòng nhập đầy đủ tên đăng nhập và mật khẩu. <a href='javascript: history.go(-1)'>Trở lại</a>";
        exit;
    }

    // Tìm tên đăng nhập trong cơ sở dữ liệu
    $sql = "SELECT * FROM users WHERE username='$username'";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(); 
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    // Kiểm tra đăng nhập
    if (!$result) {
        echo "Tên đăng nhập hoặc mật khẩu không đúng!";
    } else {
        // Lấy mật khẩu trong database ra
        $password_hash = $result['password'];
        $_SESSION['username'] = $result['username'];
        $_SESSION['id'] = $result['id'];
        // Kiểm tra xem hai mật khẩu có khớp với nhau hay không
        if ($password != $password_hash) {
            echo "Mật khẩu không đúng. Vui lòng nhập lại. <a href='javascript: history.go(-1)'>Trở lại</a>";
            exit;
        }
    }
        $role = $result['role'];

// Lưu tên đăng nhập vào biến session

// Chuyển hướng đến trang thích hợp
switch ($role) {
    case 'student':
        header("Location: students\index.php?id=$result[id]");
        exit();
        break;

    case 'instructor':
        header("Location: instructors\index.php?id=$result[id]");
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