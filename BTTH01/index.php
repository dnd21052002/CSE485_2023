<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-KK94CHFLLe+nY2dmCWGMq91rCGa5gtU4mk92HdvYe+M/SXH301p5ILy+dN9+nJOZ" crossorigin="anonymous">
</head>

<style>
    body {
        margin: 40px;
    }

    input {
        width: 50% !important;
        border: 1px solid #000 !important;
    }
</style>
<body>
    <?php
        require_once "StudentDAO.php";

        $studentDao = new StudentDAO();
        $students = $studentDao->readFile("students.csv");
    ?>
    <h1>Student Management</h1>
    <table class="table">
        <thead>
            <tr>
                <th scope="col">ID</th>
                <th scope="col">Họ tên</th>
                <th scope="col">Tuổi</th>
                <th scope="col">Lớp</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($students as $student): ?>
            <tr>
                <td scope="row"><?php echo $student->getId(); ?></td>
                <td><?php echo $student->getName(); ?></td>
                <td><?php echo $student->getAge(); ?></td>
                <td><?php echo $student->getGrade(); ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <a href="add-student.php" class="btn btn-primary">Thêm mới</a>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js" integrity="sha384-ENjdO4Dr2bkBIFxQpeoTz1HIcje39Wm4jDKdf19U8gI4ddQ3GYNS7NTKfAdVQSZe" crossorigin="anonymous"></script>
</body>
</html>