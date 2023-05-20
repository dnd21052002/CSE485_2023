<?php
    require_once '../includes/database-connection.php';

    if($_SERVER['REQUEST_METHOD'] == 'GET'){
        try{
            $sql_courses = "SELECT * FROM courses";
            $stmt_courses = $pdo->prepare($sql_courses);
            $stmt_courses->execute();
            $courses = $stmt_courses->fetchAll(PDO::FETCH_ASSOC);
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
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse">
                    <a class="nav-link active " aria-current="page" href="index.php">Home</a>
                </div>
                <a class="nav-link text-primary text-decoration-underline" href="login.php">Log in</a>
            </div>
        </nav>
        <div class="container">
            <h2 class="text-center mt-4 mb-4">Các khoá học có sẵn</h2>
            <div class="row card_wrapper">
                <?php
                    foreach($courses as $course){
                        echo '<div class="card col-4 course_card">';
                        echo '<div class="card-body d-flex flex-column justify-content-between">';
                        echo '<h5 class="card-title">' . $course['course_code'] . '.' . $course['title']   . '</h5>';
                        echo '<p class="card-text">' . $course['description'] . '</p>';
                        echo '<a href="course.php?id=' . $course['id'] . '" class="btn btn-primary">Xem chi tiết</a>';
                        echo '</div>';
                        echo '</div>';
                    }
                ?>
            </div>
                
        </div>
  
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js" integrity="sha384-ENjdO4Dr2bkBIFxQpeoTz1HIcje39Wm4jDKdf19U8gI4ddQ3GYNS7NTKfAdVQSZe" crossorigin="anonymous"></script>
  </body>
</html>