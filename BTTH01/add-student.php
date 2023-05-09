<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Student</title>
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

    <h1>Thêm sinh viên</h1>
    <form action="add-student.php" method="post">
        <div class="mb-3">
            <label class="form-label" for="name">Tên:</label>
            <input class="form-control col-md-2" type="text" id="name" name="name"><br>
        </div>
        <div class="mb-3">
            <label class="form-label" for="age">Tuổi:</label>
            <input class="form-control" type="text" id="age" name="age"><br>
        </div>
        <div class="mb-3">
            <label class="form-label" for="grade">Lớp:</label>
            <input class="form-control" type="text" id="grade" name="grade"><br>
        </div>

        <button type="submit" class="btn btn-primary">Thêm</button>
    </form>
    <?php
        require_once "StudentDAO.php";
        require_once "Student.php";
        $studentDao = new StudentDAO();
        $students = $studentDao->readFile("students.csv");

        if ($_SERVER["REQUEST_METHOD"] == "POST") {
            if (!empty($_POST["name"]) && !empty($_POST["age"]) && !empty($_POST["grade"])) {
                // toàn bộ các trường dữ liệu đã được nhập
                // xử lý dữ liệu
                //Kiểm tra trùng lặp dữ liệu
                $name = $_POST["name"];
                $age = $_POST["age"];
                $grade = $_POST["grade"];

                foreach ($students as $student) {
                    if ($student->getName() == $name && $student->getAge() == $age && $student->getGrade() == $grade) {
                        echo '<p class="text-danger">Sinh viên đã tồn tại</p>';
                        exit();
                    }
                }
                $id = count($students) + 1;
                $name = $_POST["name"];
                $age = $_POST["age"];
                $grade = $_POST["grade"];

                $student = new Student($id, $name, $age, $grade);
                $studentDao->create($student);
                $studentDao->saveFile("students.csv");
                header("Location: index.php"); // chuyển hướng về trang chủ
                exit();
            } else {
            // một hoặc nhiều trường dữ liệu chưa được nhập
            // hiển thị thông báo lỗi hoặc chuyển hướng về trang trước đó
                echo '<p class="text-danger">Vui lòng nhập đầy đủ thông tin</p>';
            }
        }
    ?>
</body>
</html>