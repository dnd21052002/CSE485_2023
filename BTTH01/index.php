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
    <form action="index.php" method="post">
        <div class="mb-3">
            <label class="form-label" for="id">ID:</label>
            <input class="form-control" type="text" id="id" name="id"><br>
        </div>
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

        <button type="submit" class="btn btn-primary">Submit</button>
    </form>
    <?php
        if ($_SERVER["REQUEST_METHOD"] == "POST") {
            if (!empty($_POST["id"]) && !empty($_POST["name"]) && !empty($_POST["age"]) && !empty($_POST["grade"])) {
                // toàn bộ các trường dữ liệu đã được nhập
                // xử lý dữ liệu
                $id = $_POST["id"];
                //Kiểm tra trùng lặp ID
                foreach ($students as $student) {
                    if ($student->getId() == $id) {
                        echo '<p class="text-danger">ID đã tồn tại</p>';
                        exit;
                    }
                }
                $name = $_POST["name"];
                $age = $_POST["age"];
                $grade = $_POST["grade"];

                $student = "$id, $name, $age, $grade\n";

                $file = fopen("students.csv", "a");
                fwrite($file, $student);
                fclose($file);

                header("Location: index.php"); // chuyển hướng về trang chủ
                exit;
            } else {
            // một hoặc nhiều trường dữ liệu chưa được nhập
            // hiển thị thông báo lỗi hoặc chuyển hướng về trang trước đó
                echo '<p class="text-danger">Vui lòng nhập đầy đủ thông tin</p>';
            }
        } else {
            // không phải là phương thức POST
            // hiển thị form
            $id = "";   
            $name = "";
            $age = "";
            $grade = "";

            unset($_POST);
        }
    ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js" integrity="sha384-ENjdO4Dr2bkBIFxQpeoTz1HIcje39Wm4jDKdf19U8gI4ddQ3GYNS7NTKfAdVQSZe" crossorigin="anonymous"></script>
</body>
</html>