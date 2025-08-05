<?php
session_start();

if (isset($_SESSION["useremail"]) && !empty($_SESSION["useremail"])) {


  ?>
  <!DOCTYPE html>
  <html lang="en">

  <head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <link rel="stylesheet" href="css/header.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
      href="https://fonts.googleapis.com/css2?family=Exo:ital,wght@0,100..900;1,100..900&family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap"
      rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Exo:ital,wght@0,100..900;1,100..900&display=swap"
      rel="stylesheet">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
  </head>

  <body>
    <div class="dashboard-1">
      <!-- <div class="background-5">
    </div> -->
      <div class="group-54">
        <div class="logo">
          <!-- <div class="icon-1">
          <img class="mask-group-1" src="../materials/arrow.png" />
        </div> -->
          <div class="icon">
            <img class="mask-group" src="../materials/maskGroup_x2.svg" />

            <span class="sc">
              CL
            </span>
          </div>
          <div class="dashboard">
            <!-- Dashboard -->
          </div>


          <!-- <div class="student-companion">
          Student Companion
        </div> -->
        </div>

        <!-- <div class="desh-text1"> -->
        <div class="hamburgermenu">
          <div class="container-2">
          </div>
          <div class="container-3">
          </div>
          <div class="container-4">
          </div>
        </div>


        <!-- </div> -->
        <form class="searchbox1">
          <input type="search" class="searchbox" placeholder="Search here">
          <div class="ic-search">
            <img class="vector-10" src="../materials/vector_x2.svg" />
          </div>
        </form>
        <div class="student-companion">
          CampusLynk
        </div>
        <!-- <div class="messages">
        <img class="ic-messages" src="../materials/icMessages_x2.svg" />
        <div class="background-2">
        </div>
        <div class="background-3">
        </div>
        <span class="container-1">
          0
        </span>
      </div> -->
        <!-- <div class="messages">
        <img class="ic-messages" src="../materials/icNotification_x2.svg" />
        <div class="background-2">
        </div>
        <div class="background-3">
        </div>
        <span class="container-1">
          0
        </span>
      </div> -->
        <!-- <div class="notif">
        <img class="ic-notification" src="icNotification_x2.svg" />
        <div class="background-2">
        </div>
        <div class="background-1">
        </div>
        <span class="container">
          7
        </span>
      </div> -->
        <!-- </div> -->
      </div>
      <div class="profile">
        <div class="profile-picture">
          <!-- <img src="arrow.png" alt=""> -->
          <div class="placeholder">
          </div>
        </div>
        <div class="container">
        <?php
                try {
                    $conn = new PDO("mysql:host=localhost:3306;dbname=student_companion;", "root", "");
                    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);


                    $projectquery = "SELECT name FROM student where email='$_SESSION[useremail]'";
                    $returnobj = $conn->query($projectquery);
                    $returnable = $returnobj->fetchAll();
                    foreach ($returnable as $row) {
                        ?><div class="student-1">
                          <?php echo $row['name'] ?>
                        <!-- kazi jaberul islam Nafi -->
                      </div>
                      <span class="cse">
                      <?php echo $_SESSION['useremail'] ?>
                        <!-- CSE -->
                      </span>
                        <?php
                    }



                } catch (PDOException $ex) {
                    ?>
                    <div class="student-1">Error loading user info</div>
                    <span class="cse">
                      <?php echo $_SESSION['useremail'] ?>
                    </span>
                    <?php
                }
                ?>
          
        </div>
        <!-- <div class="ic-chevron">
          <img class="vector" src="vector1_x2.svg" />
        </div> -->
      </div>
    </div>
  </body>
  <!-- <script>
  function my() {
    location.assign("my.html")
  }
</script> -->

  </html>
  <?php



} else {
  ?>
  <script>location.assign("login.php")</script>
  <?php
}

?>
<script src="header.js"></script>