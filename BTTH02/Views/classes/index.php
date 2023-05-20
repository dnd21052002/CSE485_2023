<?php
    require_once '../../includes/database-connection.php';

    if(isset($_GET['id'])){
        $class_id = $_GET['id'];
        try{
            $sql_students = "SELECT s.*, a.status as attendance_status, a.date as attendance_date
                            FROM students s
                            INNER JOIN students_classes sc ON s.id = sc.student_id
                            INNER JOIN attendances a ON s.id = a.student_id
                            WHERE sc.class_id = $class_id";
            $stmt_students = $pdo->prepare($sql_students);
            $stmt_students->execute();
            $students = $stmt_students->fetchAll(PDO::FETCH_ASSOC);
            $sql_class = "SELECT * FROM classes WHERE id = $class_id";
            $stmt_class = $pdo->prepare($sql_class);
            $stmt_class->execute();
            $class = $stmt_class->fetch(PDO::FETCH_ASSOC);

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
    <title>CodeError</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-KK94CHFLLe+nY2dmCWGMq91rCGa5gtU4mk92HdvYe+M/SXH301p5ILy+dN9+nJOZ" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
  </head>
  <body>
        <nav class="navbar shadow navbar-expand-lg bg-body-tertiary">
            <div class="container-fluid">
                <a class="navbar-brand" href="index.php">CodeError</a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse">
                    <a class="nav-link active fw-bold" aria-current="page" href="../instructors/index.php">Home</a>
                </div>
                <div class="d-flex text-center">
                    <p class="mb-0 me-4">Khoá học: <?= $class['name'] ;?></p>
                    <p class="me-4 mb-0">Giảng viên: <?php session_start();echo $_SESSION['instructor_name']; ?></p>
                    <a href="../../logout.php" class="text-black">Đăng xuất</a>
                </div>
            </div>
        </nav>
        <div class="container">
            <h2 class="text-center mt-4 mb-4">Trạng thái điểm danh</h2>
            <table class="table table-bordered text-center table-hover">
                <a href="add-status.php" class="btn btn-primary mb-4">Thêm mới</a>
                <thead>
                    <tr>
                        <th scope="col">#</th>
                        <th scope="col">Tên</th>
                        <th scope="col">Ngày điểm danh</th>
                        <th scope="col">Trạng thái</th>
                        <th scope="col">Hành động</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                        $i = 1;
                        foreach($students as $student){
                            $status_color = '';
                            switch ($student['attendance_status']) {
                            case 'có mặt':
                                $status_color = 'text-success';
                                break;
                            case 'muộn':
                                $status_color = 'text-warning';
                                break;
                            case 'vắng mặt':
                                $status_color = 'text-danger';
                                break;
                            case 'có phép':
                                $status_color = 'text-info';
                                break;
                            default:
                                $status_color = '';
                                break;
                            }
                            echo "<tr>";
                            echo "<td scope='row'>$i</td>";
                            echo "<td>{$student['name']}</td>";
                            echo "<td>{$student['attendance_date']}</td>";
                            echo "<td class = ".$status_color.">{$student['attendance_status']}</td>";
                            echo "<td>
                                    <a href='editStatus.php?id={$student['id']}' class='text-warning'><i class='bi bi-pencil-square'></i></a>
                                    <a href='deleteStatus.php?id={$student['id']}' class='text-danger'><i class='bi bi-trash'></i></a>
                                </td>";
                            echo "</tr>";
                            $i++;
                        }
                    ?>
                </tbody>
            </table>
        </div>
  
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js" integrity="sha384-ENjdO4Dr2bkBIFxQpeoTz1HIcje39Wm4jDKdf19U8gI4ddQ3GYNS7NTKfAdVQSZe" crossorigin="anonymous"></script>
  </body>
</html>