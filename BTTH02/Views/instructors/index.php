<?php
    require_once '../../includes/database-connection.php';
    if($_SERVER['REQUEST_METHOD'] == 'GET'){

        //Bước 1: Kết nối DB
        try{
            $sql_students = "SELECT * FROM students";
            $stmt_students = $pdo->prepare($sql_courses);
            $stmt_students->execute();
            $students = $sql_students->fetchAll(PDO::FETCH_ASSOC);

        }catch(PDOException $e){
            echo "Lỗi: " . $e->getMessage();
        }
    }
?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Bootstrap demo</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-KK94CHFLLe+nY2dmCWGMq91rCGa5gtU4mk92HdvYe+M/SXH301p5ILy+dN9+nJOZ" crossorigin="anonymous">
  </head>
  <body>
    <div class="container">
        <!-- Thêm bảng hiển thị dữ liệu -->
        <h1 class="text-center text-primary text-uppercase mt-3 mb-5" >Danh sách sinh viên</h1>
        <table class="table table-hover">
            <a href="#" class="btn btn-primary">Thêm mới</a>
            <thead>
                <tr>
                    <th scope="col">#</th>
                    <th scope="col">Tiêu đề</th>
                    <th scope="col">Danh mục</th>
                    <th scope="col">Tác giả</th>
                    <th scope="col">Hành động</th>
                </tr>
            </thead>
            <tbody>
                <?php
                    $i = 1;
                    foreach($articles as $article){
                        echo "<tr>";
                        echo "<td scope='row'>$i</td>";
                        echo "<td>{$students['name']}</td>";
                        echo "<td>{$article['category_name']}</td>";
                        echo "<td>{$article['member_forename']} {$article['member_surname']}</td>";
                        echo "<td>
                                <a href='editArticle.php?id={$article['id']}' class='btn btn-warning'>Sửa</a>
                                <a href='deleteArticle.php?id={$article['id']}' class='btn btn-danger'>Xóa</a>
                            </td>";
                        echo "</tr>";
                        $i++;
                    }
                ?>
            </tbody>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js" integrity="sha384-ENjdO4Dr2bkBIFxQpeoTz1HIcje39Wm4jDKdf19U8gI4ddQ3GYNS7NTKfAdVQSZe" crossorigin="anonymous"></script>
  </body>
</html>
0