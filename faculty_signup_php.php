<?php
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['myemail']) && isset($_POST['mypass']) && isset($_POST['myname']) && isset($_POST['myid']) && isset($_POST['mydepartment'])) {
        
        $name = $_POST['myname'];
        $profession = $_POST['myprofession'];
        $email = $_POST['myemail'];
        $pass = $_POST['mypass'];
        try {
            $conn = new PDO("mysql:host=localhost:3306;dbname=student_companion;", "root", "");
            // $conn->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION);
            $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $enr_pass=md5($pass);
            $signupquery = "insert into faculty values('$name','$profession','$email','$enr_pass')";
            $conn->exec($signupquery);
            ?>
            <script>location.assign("login.php");</script>
            <?php
        } catch (PDOException $ex) {
            ?>
            <script>location.assign("signup.php");</script>
            <?php
        }
    } else {
        ?>
        <script>location.assign("Homepage.php");</script>
        <?php
    }
} else {
    ?>
    <script>location.assign("homepage.php");</script>
    <?php
}
?>