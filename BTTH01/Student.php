<?php
    class Student {
        private $id;
        private $name;
        private $age;
        private $grade;
        
        public function getId() {
          return $this->id;
        }
        
        public function setId($id) {
          $this->id = $id;
        }
        
        public function getName() {
          return $this->name;
        }
        
        public function setName($name) {
          $this->name = $name;
        }
        
        public function getAge() {
          return $this->age;
        }
        
        public function setAge($age) {
          $this->age = $age;
        }

        public function getGrade() {
          return $this->grade;
        }

        public function setGrade($grade) {
          $this->grade = $grade;
        }
      }
?>