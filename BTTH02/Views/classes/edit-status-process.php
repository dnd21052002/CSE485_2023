<?php
session_start();
$course_id = $_SESSION['course_id'];
$student_id = $_SESSION['student_id'];
session_abort();
    if(isset($_POST['txtDate']) && isset($_POST['selection']) && isset($_POST['txtAttendanceId'])){
        $date = $_POST['txtDate'];
        $status = $_POST['selection'];
        $attendanceId = $_POST['txtAttendanceId'];
        //Bước 1: Kết nối DB
        try{
            require_once '../../includes/database-connection.php';

            $sql = "UPDATE attendances SET course_id = ?, student_id = ?, status = ?, date = ?  WHERE id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(1, $course_id, PDO::PARAM_INT);
            $stmt->bindParam(2, $student_id, PDO::PARAM_INT);
            $stmt->bindParam(3, $status, PDO::PARAM_STR);
            $stmt->bindParam(4, $date, PDO::PARAM_STR);
            $stmt->bindParam(5, $attendanceId, PDO::PARAM_INT);
            $stmt->execute();

            //chuyển về trang index.php
            header("Location: index.php?id=$_SESSION[class_id]");


        }catch(PDOException $e){
            echo "Lỗi: " . $e->getMessage();
        }
    }
    else{
        echo "Không thể cập nhật dữ liệu";
    }
?>