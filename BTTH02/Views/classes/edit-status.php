<?php 
    session_start();
    $instructor_name = $_SESSION['instructor_name'];
    if(isset($_GET['id'])){
        $id = $_GET['id'];

        //Bước 1: Kết nối DB
        try{
            require_once '../../includes/database-connection.php';

            $sql_attendances = "SELECT a.*, s.name as student_name, c.title as course_name
                                FROM attendances a
                                INNER JOIN students s ON a.student_id = s.id
                                INNER JOIN courses c ON a.course_id = c.id
                                WHERE a.id = $id";
            $stmt_attendances = $pdo->prepare($sql_attendances);
            $stmt_attendances->execute();
            $attendances = $stmt_attendances->fetch(PDO::FETCH_ASSOC);
            $_SESSION['course_id'] = $attendances['course_id'];
            $_SESSION['student_id'] = $attendances['student_id'];
        }
        catch(PDOException $e){
            echo $e->getMessage();
        }
    }
?>


<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Sửa trạng thái đăng nhập</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-KK94CHFLLe+nY2dmCWGMq91rCGa5gtU4mk92HdvYe+M/SXH301p5ILy+dN9+nJOZ" crossorigin="anonymous">
  </head>
  <body>
        <nav class="navbar shadow navbar-expand-lg bg-body-tertiary">
            <div class="container-fluid">
                <a class="navbar-brand" href="index.php">CodeError</a>
                <div class="collapse navbar-collapse">
                    <a class="nav-link active fw-bold" aria-current="page" href="../instructors/index.php">Home</a>
                </div>
                <div class="d-flex text-center">
                    <p class="mb-0 me-4">Khoá học: <?= $attendances['course_name'] ;?></p>
                    <p class="me-4 mb-0">Giảng viên: <?php echo $instructor_name ?></p>
                    <a href="../../logout.php" class="text-black">Đăng xuất</a>
                </div>
            </div>
        </nav>
        <div class="container">
            <h2 class="text-center text-primary mt-3 mb-5" >Sửa trạng thái đăng nhập</h2>
            <form method="POST" action="edit-status-process.php">
                <div class="row mb-3">
                    <label for="txtAttendanceId" class="col-sm-2 col-form-label">Mã trạng thái</label>
                    <div class="col-sm-10">
                        <input readonly type="text" class="form-control bg-secondary" id="txtAttendanceId" name="txtAttendanceId" value="<?= $attendances['id'] ?>">
                    </div>
                </div>
                <div class="row mb-3">
                    <label for="txtName" class="col-sm-2 col-form-label">Tên</label>
                    <div class="col-sm-10">
                        <input readonly type="text" class="form-control" id="txtName" name="txtName" value="<?= $attendances['student_name'] ?>">
                    </div>
                </div>
                <div class="row mb-3">
                    <label for="txtDate" class="col-sm-2 col-form-label">Ngày điểm danh</label>
                    <div class="col-sm-10">
                        <input type="date" class="form-control" id="txtDate" name="txtDate" value="<?= $attendances['date'] ?>">
                    </div>
                </div>
                <div class="row mb-3">
                    <label for="inputEmail3" class="col-sm-2 col-form-label">Trạng thái</label>
                    <div class="col-sm-10">
                        <div>
                            <input type="radio" id="option1" name="selection" value="có mặt">
                            <label for="option1">Có mặt</label>
                        </div>
                        <div>
                            <input type="radio" id="option2" name="selection" value="vắng mặt">
                            <label for="option2">Vắng mặt</label>
                        </div>
                        <div>
                            <input type="radio" id="option3" name="selection" value="muộn">
                            <label for="option3">Muộn</label>
                        </div>
                        <div>
                            <input type="radio" id="option3" name="selection" value="có phép">
                            <label for="option3">Có phép</label>
                        </div>
                    </div>
                </div>
                
                <button type="submit" class="btn btn-primary">Sửa</button>
            </form>
        </div>




    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js" integrity="sha384-ENjdO4Dr2bkBIFxQpeoTz1HIcje39Wm4jDKdf19U8gI4ddQ3GYNS7NTKfAdVQSZe" crossorigin="anonymous"></script>
  </body>
</html>