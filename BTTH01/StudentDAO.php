<?php
    require_once 'Student.php';
    class StudentDAO {
        private $students = array();
        
        public function create(Student $student) {
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
        
        public function readFile($filename) {
            $file = fopen($filename, "r");
            if ($file) {
              while (($line = fgets($file)) !== false) {
                $data = explode(",", $line);
                $student = new Student();
                $student->setId(trim($data[0]));
                $student->setName(trim($data[1]));
                $student->setAge(trim($data[2]));
                $student->setGrade(trim($data[3]));
                array_push($this->students, $student);
              }
              fclose($file);
            } else {
              echo "Không thể mở file " . $filename;
            }
            return $this->students;
          }
      }
?>