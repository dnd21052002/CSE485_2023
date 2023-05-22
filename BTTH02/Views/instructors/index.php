<?php
    session_start();
    if(!isset($_SESSION['username'])) {
        header('Location: ../login.php');
        exit();
    }
    ?>

<?php
    require_once '../../includes/database-connection.php';

        if($_SERVER['REQUEST_METHOD'] == 'GET'){
            if(isset($_GET['id'])) $user_id = $_GET['id'];
            else $user_id = $_SESSION['id'];
            try{
                $sql_instructor = "SELECT i.*
                                    FROM instructors i
                                    JOIN users u ON i.id = u.user_id
                                    where u.id = $user_id";
                $stmt_instructor = $pdo->prepare($sql_instructor);
                $stmt_instructor->execute();
                $instructor = $stmt_instructor->fetch(PDO::FETCH_ASSOC);
                $_SESSION['instructor_name'] = $instructor['name'];
                $_SESSION['instructor_id'] = $instructor['id'];
                $sql_classes = "SELECT c.title AS course_name, cl.*
                                FROM courses c
                                INNER JOIN classes cl ON c.id = cl.course_id
                                INNER JOIN instructors_classes ic ON cl.id = ic.class_id
                                WHERE ic.instructor_id = $instructor[id]";
                $stmt_classes = $pdo->prepare($sql_classes);
                $stmt_classes->execute();
                $classes = $stmt_classes->fetchAll(PDO::FETCH_ASSOC);
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
  </head>
  <body>
        <nav class="navbar shadow navbar-expand-lg bg-body-tertiary">
            <div class="container-fluid">
                <a class="navbar-brand" href="index.php">CodeError</a>
                <div class="collapse navbar-collapse">
                    <a class="nav-link active " aria-current="page" href="./">Home</a>
                </div>
                <p class="me-4 mb-0">Welcome, <?php echo $instructor['name'] ?></p>
                <a href="../logout.php" class="text-black">Đăng xuất</a>
            </div>
        </nav>
        <div class="container">
            <h2 class="text-center mt-4 mb-4">Danh sách lớp</h2>
            <div class="row card_wrapper">
                <?php
                    foreach($classes as $class){
                        echo '<div class="card col-4 course_card">';
                        echo '<div class="card-body d-flex flex-column justify-content-between">';
                        echo '<h5 class="card-title"> Khoá học: ' . $class['course_name']  . '</h5>';
                        echo '<p class="card-text">Lớp: ' . $class['name'] . '</p>';
                        echo '<a href="../classes/index.php?id=' . $class['id'] . '" class="btn btn-primary">Quản lý</a>';
                        echo '</div>';
                        echo '</div>';
                    }
                ?>
            </div>
        </div>
  
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js" integrity="sha384-ENjdO4Dr2bkBIFxQpeoTz1HIcje39Wm4jDKdf19U8gI4ddQ3GYNS7NTKfAdVQSZe" crossorigin="anonymous"></script>
  </body>
</html>