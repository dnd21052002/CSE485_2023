<?php
    require_once 'Student.php';
    class StudentDAO {
        private $students = array();
        
        public function create(Student $student) {
            $newID = count($this->students) + 1;
            $student->setId($newID);
            array_push($this->students, $student);
        }
        
        public function read($id) {
          foreach ($this->students as $student) {
            if ($student->getId() == $id) {
              return $student;
            }
          }
          return null;
        }
        
        public function update(Student $student) {
          foreach ($this->students as $key => $value) {
            if ($value->getId() == $student->getId()) {
              $this->students[$key] = $student;
            }
          }
        }
        
        public function delete($id) {
          foreach ($this->students as $key => $value) {
            if ($value->getId() == $id) {
              unset($this->students[$key]);
            }
          }
        }
        
        public function getAll() {
          return $this->students;
        }
        
        //đọc file csv và lưu dữ liệu vào mảng students nhưng bỏ dòng đầu tiên
        public function readFile($fileName) {
          $file = fopen($fileName, "r");
          $i = 0;
          while (($line = fgetcsv($file)) !== false) {
            if ($i > 0) {
              $student = new Student($line[0], $line[1], $line[2], $line[3]);
              array_push($this->students, $student);
            }
            $i++;
          }
          fclose($file);
          return $this->students;
        }

        //lưu dữ liệu từ mảng students vào file csv
        public function saveFile($fileName) {
          $file = fopen($fileName, "w");
          $data = array();
          foreach ($this->students as $student) {
            array_push($data, array($student->getId(), $student->getName(), $student->getAge(), $student->getGrade()));
          }
          fputcsv($file, array("ID", "Name", "Age", "Grade"));
          foreach ($data as $row) {
            fputcsv($file, $row);
          }
          fclose($file);
        }
      }

?>