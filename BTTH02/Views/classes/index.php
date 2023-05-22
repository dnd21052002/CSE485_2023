<?php
    session_start();
    if(!isset($_SESSION['username'])) {
        header('Location: ../login.php');
        exit();
    }
    ?>


<?php
    require_once '../../includes/database-connection.php';

    if(isset($_GET['id'])){
        $class_id = $_GET['id'];
        $_SESSION['class_id'] = $class_id;
        try{
            $sql_scs = "SELECT attendances.id, attendances.status, attendances.date, classes.name AS class_name, students.name AS student_name
                        FROM attendances
                        JOIN students_classes ON attendances.student_id = students_classes.student_id
                        JOIN classes ON attendances.course_id = classes.course_id AND students_classes.class_id = classes.id
                        JOIN students ON attendances.student_id = students.id
                        WHERE classes.id = $class_id";
            $stmt_scs = $pdo->prepare($sql_scs);
            $stmt_scs->execute();
            $scs = $stmt_scs->fetchAll(PDO::FETCH_ASSOC);

            $sql_cl = "SELECT classes.name from classes WHERE classes.id = $class_id";
            $stmt_cl = $pdo->prepare($sql_cl);
            $stmt_cl->execute();
            $cl = $stmt_cl->fetch(PDO::FETCH_ASSOC);
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
                <div class="collapse navbar-collapse">
                    <a class="nav-link active fw-bold" aria-current="page" href="../instructors/">Home</a>
                </div>
                <div class="d-flex text-center">
                    <p class="mb-0 me-4">Lớp học: <?= $cl['name'] ;?></p> 
                    <p class="me-4 mb-0">Giảng viên: <?php echo $_SESSION['instructor_name']; ?></p>
                    <a href="../logout.php" class="text-black">Đăng xuất</a>
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
                        foreach($scs as $sc){
                            $status_color = '';
                            switch ($sc['status']) {
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
                            echo "<td>{$sc['student_name']}</td>";
                            echo "<td>{$sc['date']}</td>";
                            echo "<td class ='fw-bold $status_color'>{$sc['status']}</td>";
                            echo "<td>
                                    <a href='edit-status.php?id={$sc['id']}' class='text-warning'><i class='bi bi-pencil-square'></i></a>
                                    <a href='delete-status.php?id={$sc['id']}' class='text-danger'><i class='bi bi-trash'></i></a>
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