<?php
require_once '../../includes/database-connection.php';

if ($_SERVER['REQUEST_METHOD'] == 'GET') {
    try {
        $sql_courses = "SELECT name FROM students WHERE id = 1";
        $stmt_courses = $pdo->prepare($sql_courses);
        // $stmt_courses->bindParam(':id', $_GET['id']);
        $stmt_courses->execute();
        $courses = $stmt_courses->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        echo $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.3.1/dist/css/bootstrap.min.css" integrity="sha384-ggOyR0iXCbMQv3Xipma34MD+dH/1fQ784/j6cY/iJTQUOhcWr7x9JvoRxT2MZw1T" crossorigin="anonymous">
    <link href="//maxcdn.bootstrapcdn.com/bootstrap/3.3.0/css/bootstrap.min.css" rel="stylesheet" id="bootstrap-css">

    <title>Document</title>
</head>

<body>
    <div>
        <nav class="navbar navbar-expand-lg navbar-light bg-light">
            <a class="navbar-brand" href="#">VietCodeDi</a>
            <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="navbarSupportedContent">
                <ul class="navbar-nav mr-auto">
                    <li class="nav-item active fw-bold">
                        <a class="nav-link " href="../index.php">Home <span class="sr-only">(current)</span></a>
                    </li>
                    <li class="nav-item active">
                        <a class="nav-link" href="./dashbord.php">Dashboard</a>
                    </li>
                    <li class="nav-item active">
                        <a class="nav-link" href="#">Các khoá học của tôi</a>
                    </li>

                </ul>

                <div>
                    <?php
                    foreach ($students as $student) {
                        echo '<h5 class="text-primary">' . "wellcom" . $name . '</h5>';
                    }
                    ?>
                </div>

        </nav>


    </div>

    <div class="container">
        <header>
            <h1 class="text-uppercase">Khu vực điểm danh</h1>
        </header>
        <div class="main">
            <ul class="nav nav-tabs mb-3">
                <li class="nav-item"><a class="nav-link active" href="#" class="">
                        <h3>This course</h3>
                    </a></li>
                <li class="nav-item"><a class="nav-link" href="#">
                        <h3>All courses</h3>
                    </a></li>
                <li class="nav-item"><a class="nav-link" href="#">
                        <h3>All sessions</h3>
                    </a></li>
            </ul>


            <table class="table">
                <thead>
                    <tr>
                        <th scope="col">Date</th>
                        <th scope="col">description</th>
                        <th scope="col">Status</th>
                        <th scope="col">Points</th>
                        <th scope="col">Remarks</th>
                    </tr>
                </thead>
                <?php
                if ($_SERVER['REQUEST_METHOD'] == 'GET') {
                    try {
                        $sql_courses = "SELECT description FROM courses";
                        $stmt_courses = $pdo->prepare($sql_courses);
                        $stmt_courses->execute();
                        $courses = $stmt_courses->fetchAll(PDO::FETCH_ASSOC);
                    } catch (PDOException $e) {
                        echo $e->getMessage();
                    }
                }

                foreach ($courses as $course) {
                    echo '<tbody>';
                    echo '<tr>';
                    echo '<th scope="row">1</th>';
                    echo '<th scope="row">' . $course['description'] . '</th>';
                    echo '<th scope="row">1</th>';
                    echo '<th scope="row">1</th>';
                    echo '<th scope="row">1</th>';
                    echo '</tr>';
                    echo '</tbody>';
                }
                ?>
            </table>

        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.min.js" integrity="sha384-cuYeSxntonz0PPNlHhBs68uyIAVpIIOZZ5JqeqvYYIcEL727kskC66kF92t6Xl2V" crossorigin="anonymous"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <script src="//maxcdn.bootstrapcdn.com/bootstrap/3.3.0/js/bootstrap.min.js"></script>
    <script src="//code.jquery.com/jquery-1.11.1.min.js"></script>
</body>

</html>