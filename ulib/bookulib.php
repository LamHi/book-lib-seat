<?php
    session_start();
    if(!isset($_SESSION['sid'])){
        //If there is no sid on url, return to the login page.
        header("Location: ../index.php");
        exit();
    }
    // Get sid from SESSION;
     $gsid = $_SESSION['sid'];

     if(!isset($_SESSION['lib'])){  //If there exist no library id in SESSION
         $_SESSION['lib'] = "ulib"; //We set SESSION['lib'] to be ulib;
         $lib = $_SESSION['lib'];   
     }
     else{  // If we have library id in SESSION
         $lib = $_SESSION['lib'];   // We get library id into $lib
     }
    
     if($lib=="uclib"){
         // If library id is uclib, we pass the full name of the library into $libmessage for further use
        $libmessage="United College Library";
    }
    else if($lib=="cclib"){
        // If library id is cclib, we pass the full name of the library into $libmessage for further use
        $libmessage="Chung Chi College Library";
    }
    else{
        // If library id is ulib, we pass the full name of the library into $libmessage for further use
        $libmessage="University Library";
    }
     date_default_timezone_set("Asia/Hong_Kong");   //Set timezone to Hong Kong

    require 'bls.dbh.php';
    if(isset($_GET['submit'])){ //If the user submited the date and time of booking
        // Get date, starttime, endtime, and seat area from the url
        $date = $_GET['date'];
        $starttime = $_GET['starttime'];
        $endtime = $_GET['endtime'];
        $area = $_GET['area'];
        $multiseatsql = "SELECT * FROM bookrecord WHERE sid='".$gsid."' AND bookdate='".$date."' AND ((starttime<='".$starttime."' AND endtime>='".$starttime."') OR (starttime<='".$endtime."' AND endtime>='".$endtime."') OR (starttime>='".$starttime."' AND endtime<='".$endtime."'))";
        $multiseatresult = mysqli_query($conn, $multiseatsql);
        if(mysqli_num_rows($multiseatresult)){
            //If the user has already reserved a seat at the time slot, the webpage is refreshed, an error message is shown to ask the user to select another timeslot.
            header("Location: ../ulib/bookulib.php?area=".$area."&error=2");
            exit();
        }
        if (empty($date)||empty($starttime)||empty($endtime)||empty($area)) {
            // If any of the information is missing, print error message, and ask the user to enter all of them.
            header("Location: ../ulib/bookulib.php?error=empty&area=".$area);
            exit();
        }

        if($date <  date("Y-m-d")){
            // If the date chosen by user is already passed, reload the page and print error message.
            header("Location: ../ulib/bookulib.php?area=".$area."&error=date");
        }
        else if(($date == date("Y-m-d"))&&$starttime<date("H:i")){
            // If the time chosen by user is already passed, reload the page and print error message.
            header("Location: ../ulib/bookulib.php?area=".$area."&error=time");
        }
        else if($starttime > $endtime){
            // If start time is greater than end time, the input is invalid, reload the page, and print error message.
            header("Location: ../ulib/bookulib.php?area=".$area."&error=time");
        }
    }
    if (isset($_POST['login-submit'])) {

        // Include the database connection script file
        require 'bls.dbh.php';
      
        // Get sid and password for chekcing.
        $usid = $_POST['sid'];
        $password = $_POST['pwd'];
        // Get seat id, date, start time, end time and seat area inputted by user
        $seat = $_POST['seat'];
        $date = $_GET['date'];
        $starttime = $_GET['starttime'];
        $endtime = $_GET['endtime'];
        $area = $_GET['area'];
      
        // Check whether there exist empty input or not.
        if (empty($usid) || empty($password)||empty($seat)||empty($date)||empty($starttime)||empty($endtime)||empty($area)) {
            // IF any field is not inputted by user, the page is refreshed, and error message is printed.
            header("Location: ../ulib/bookulib.php?area=".$area."&date=".$date."&starttime=".$starttime."&endtime=".$endtime."&error=empty&submit=");
          exit();
        }
        else {
          // Select the data from the account database which has the same sid as the user input
          $sql = "SELECT * FROM accounts WHERE sid=?;";
          // Initialize a new statement using the connection from the dbh.inc.php file.
          $stmt = mysqli_stmt_init($conn);
          // Then we prepare our SQL statement AND check if there are any errors with it.
          if (!mysqli_stmt_prepare($stmt, $sql)) {
            // If there is an error we send the user back to the signup page.
            header("Location: ../ulib/bookulib.php?error=sqlerror");
            exit();
          }
          else {
            // Bind the type of parameters we expect to pass into the statement, and bind the data from the user.
            mysqli_stmt_bind_param($stmt, "s", $usid);
            // Execute the prepared statement and send it to the database
            mysqli_stmt_execute($stmt);
            // Get the result from the statement.
            $result = mysqli_stmt_get_result($stmt);
            // Store the result into a variable.
            if ($row = mysqli_fetch_assoc($result)) {
              // Match the password from the database with the password submitted by user. The result is returned to variable $pwdCheck
              $pwdCheck = password_verify($password, $row['pwd']);
              
              if ($pwdCheck == false){
                // If the password is incorrect, the webpage is refreshed, an error message will be shown.
                // header("Location: ../ulib/bookulib.php?error=wrongpwd&sid=".$usid."&area=".$area);
                header("Location: ../ulib/bookulib.php?area=".$area."&date=".$date."&starttime=".$starttime."&endtime=".$endtime."&error=wrongpwd&submit=");
                exit();
              }
                else if ($pwdCheck == true){    //if Password is correct
                    // Select the bookrecord from database to check whether the seat selected by user is occupied at the timeslot
                    $checkseatsql = "SELECT * FROM bookrecord WHERE seatid='".$seat."' AND lib='".$lib."' AND bookdate='".$date."' AND ((starttime<='".$starttime."' AND endtime>='".$starttime."') OR (starttime<='".$endtime."' AND endtime>='".$endtime."') OR (starttime>='".$starttime."' AND endtime<='".$endtime."'))";
                    $checkseatresult = mysqli_query($conn, $checkseatsql);
                    if(mysqli_num_rows($checkseatresult)){
                        // Check whether the user has selected a seat which is already reserved. If yes, the webpage is refreshed, an error message will be shown.
                        header("Location: ../ulib/bookulib.php?area=".$area."&date=".$date."&starttime=".$starttime."&endtime=".$endtime."&error=1&submit=");
                        exit();
                    }
                    // Get the number of seat in the seat area selected by user
                    $areaseatsql = "SELECT `seatnum` FROM `areainfo` WHERE `area`='".$area."' AND `lib`='".$lib."'";
                    $seatresult = mysqli_query($conn, $areaseatsql);
                    $seatrow = mysqli_fetch_array($seatresult);
                    $seatnum = (int) filter_var($seat, FILTER_SANITIZE_NUMBER_INT); //Get the numerical part in seat id inputted by user for comparison
                    $seatchar = substr($seat,0,1);  //Get the first character of the seat id inputted by user into $seatchar
                    if($seatnum>$seatrow['seatnum']){
                        // If the seat id number inputed by user is greater the number of seats in that area, it is an invalid input, and the webpage is refreshed, and show error message.s
                        header("Location: ../ulib/bookulib.php?area=".$area."&date=".$date."&starttime=".$starttime."&endtime=".$endtime."&error=3&submit=");
                    }
                    else if($seatnum<1){
                        // If the seat id number inputed by user is less than 1, it is also identified as an invalid input.
                        header("Location: ../ulib/bookulib.php?area=".$area."&date=".$date."&starttime=".$starttime."&endtime=".$endtime."&error=3&submit=");
                    }
                    else if(strcmp($seatchar,$area)!=0){
                        // If the character part of the seat id inputed by user is not the id of the seat area, it is an invalid input.
                        header("Location: ../ulib/bookulib.php?area=".$area."&date=".$date."&starttime=".$starttime."&endtime=".$endtime."&error=4&submit=");
                    }
                    else if((substr($seat,1,1)>9)||(substr($seat,1,1)<1)){
                        // If the second character is not an number from 1 to 9, it is also identified as an invalid input.
                        header("Location: ../ulib/bookulib.php?area=".$area."&date=".$date."&starttime=".$starttime."&endtime=".$endtime."&error=4&submit=");
                    }
                    else {
                        //we start to reserve the seat for user and update the book record.
                        $_SESSION['sid'] = $row['sid'];
                        $updsql = "INSERT INTO `bookrecord`(`sid`, `bookdate`, `starttime`, `endtime`, `seatid`, `area`, `lib`) VALUES ('".$usid."','".$date."','".$starttime."','".$endtime."','".$seat."','".$area."','".$lib."')";
                        $stmt = mysqli_stmt_init($conn);
                        if (mysqli_stmt_prepare($stmt, $updsql)){
                            // mysqli_stmt_bind_param($stmt, "sssssss", $usid, $date, $starttime, $endtime, $seat);
                            mysqli_stmt_execute($stmt);
                        }
                        $getfloorsql = "SELECT `floor` FROM `areainfo` WHERE `area`='".$area."' AND `lib`='".$lib."'";
                        $getfloorresult = mysqli_query($conn, $getfloorsql);
                        $getfloorrow = mysqli_fetch_array($getfloorresult);
                        
                        //Send email to user
                        $getmailsql = "SELECT `linkmail` FROM `accounts` WHERE `sid`='".$usid."'";  //Get the link email address from account database
                        $getmailresult = mysqli_query($conn, $getmailsql);
                        $getmailrow = mysqli_fetch_array($getmailresult);
                        require_once('mail.include.php');
                        $mail->addAddress($getmailrow['linkmail']);
                        $mail->isHTML(true);
                        $mail->Subject = 'Book Lib Seat Confirmation Email';
                        $mail->Body = '
                        <head>
                            <style>
                                table, th, td {
                                    border: 1px solid black;
                                    border-collapse: collapse;
                                    margin-left:auto;
                                    margin-right:auto;
                                    
                                }
                                th, td {
                                    padding: 5px;
                                    text-align: center;   
                                }
                            </style>
                        </head>
                        <body>
                        <h1 style="text-align:center;font-size:28px;color:purple;">Book Lib Seat</h1>
                        <p style="text-align:center;color:green;">You have successfully booked a seat</p>
                        <table width="280">
                        <tr><th>Library</th>
                            <td>'.$libmessage.'</td></tr>
                        <tr><th>Date</th>
                            <td>'.$date.'</td></tr>
                        <tr><th>Time</th>
                            <td>From '.$starttime.' to '.$endtime.'</td></tr>
                        <tr><th>Area</th>
                            <td>'.$area.',  '.$getfloorrow['floor'].'/F</td></tr>
                        <tr><th>Seat id</th>
                            <td>'.$seat.'</td></tr>
                        </table>
                        <br><br>
                        </body>
                        ';
                        
                        $mail->send();

                        header("Location: ../ulib/successful.php");
                        exit();
                    }
                        
                    // }
              }
            }
            else {
                // If the sid inputted by user is invalid, booking failed, refresh the page, and show error message
                header("Location: ../ulib/bookulib.php?area=".$area."&date=".$date."&starttime=".$starttime."&endtime=".$endtime."&error=sidinvalid&submit=");
                exit();
            }
          }
        }
        // Close prepared statement after finish booking
         mysqli_stmt_close($stmt);
      }
?>
<!DOCTYPE html>
<html>
    <head>
        <meta charset="utf-8">
        <title>Book Lib Seat</title>
        <link rel="stylesheet" href="stylesheets/home.style.css">   <!-- Include the stylesheet -->
        <meta name="viewport" content="width=device-width, initial-scale=0.8">
        <style>
            .ques{
                text-align: center;
                align-items: center;
                font-size: 20px;
                font-family: arial;
            }
            .bs{
                display: block;
                width: 404px;
                height: 45px;
                margin: auto;
                border-color: purple;
                border-width: 1px;
                border-radius: 4px;
                font-family: Arial;
                color: black;
                font-weight: bold;
                font-size: 16px;
                text-align: left;
                padding-left: 10px;
            }
            .info{
                text-align: center;
                align-items: center;
                font-size: 16px;
                font-family: arial;
            }
            table, th, td {
                border: 1px solid black;
                border-collapse: collapse;
                margin-left:auto;
                margin-right:auto;
                
            }
            th, td {
                padding: 5px;
                text-align: center;   
            }
        </style>
    </head>

    <body>
        <!-- The layout of login page -->
        <header><h1 class="title">Book Lib Seat</h1></header>
        <br>
        <?php
            if(isset($_GET['error'])){
                $errortype = $_GET['error'];
                if($errortype == 1){
                    echo '<h1 style="text-align:center;font-family:arial;font-size:24px;color:red;">The seat you selected is NOT AVAILABLE!</h1>
                    <h2 style="color:red;text-align:center;font-size:16px;font-family:arial;">Please select another seat</h2>';
                }
                else if($errortype == 2){
                    echo '<h1 style="text-align:center;font-family:arial;font-size:24px;color:red;">You have already reserved a seat in this timeslot!</h1>
                    <h2 style="color:red;text-align:center;font-size:16px;font-family:arial;">Please select another timeslot</h2>';
                }
                else if($errortype == 3){
                    echo '<h1 style="text-align:center;font-family:arial;font-size:24px;color:red;">The seat id you inserted is invalid!</h1>
                    <h2 style="color:red;text-align:center;font-size:16px;font-family:arial;">Please select a valid seat</h2>';
                }
                else if($errortype == 4){
                    echo '<h1 style="text-align:center;font-family:arial;font-size:24px;color:red;">The seat id you inserted is invalid!</h1>
                    <h2 style="color:red;text-align:center;font-size:16px;font-family:arial;">Please select a valid seat</h2>';
                }
                else if($errortype == "date"){
                    echo '<h1 style="text-align:center;font-family:arial;font-size:24px;color:red;">The date you selected is invalid!</h1>
                    <h2 style="color:red;text-align:center;font-size:16px;font-family:arial;">Please choose a valid date</h2>';
                }
                else if($errortype == "time"){
                    echo '<h1 style="text-align:center;font-family:arial;font-size:24px;color:red;">The time you selected is invalid!</h1>
                    <h2 style="color:red;text-align:center;font-size:16px;font-family:arial;">Please choose a valid time</h2>';
                }
                else if($errortype == "empty"){
                    echo '<h1 style="text-align:center;font-family:arial;font-size:24px;color:red;">Please fill in all the fields!</h1>';
                    //<h2 style="color:red;text-align:center;font-size:16px;font-family:arial;">Please choose a valid time</h2>';
                }
                else if($errortype == "sqlerror"){
                    echo '<h1 style="text-align:center;font-family:arial;font-size:24px;color:red;">Connection failed!</h1>';
                }
                else if($errortype =="wrongpwd"){
                    echo '<h1 style="text-align:center;font-family:arial;font-size:24px;color:red;">The password is wrong!</h1>';
                }
                else if($errortype == "sidinvalid"){
                    echo '<h1 style="text-align:center;font-family:arial;font-size:24px;color:red;">Student/staff id invalid!</h1>';
                }
            }
            if(isset($_GET['area'])){
                $area = $_GET['area'];
            }
            if(!isset($_GET['submit'])){
                echo '<form method="GET">
                    <p align="center" class="ques">Seat Area</p>
                    <input type="text" class="bs" id="area" name="area" value="'.$area.'">
                    <p align="center" class="ques">Which day do you want to have the seat?</p>
                    <input type="date" class="bs" id="date" name="date" placeholder="Date: YYYY-MM-DD">
                    <p align="center" class="ques">When will you start using the seat?</p>
                    <input type="time" class="bs" id="starttime" name="starttime">
                    <p align="center" class="ques">When will you leave the seat?</p>
                    <input type="time" class="bs" id="endtime" name="endtime">
                    <br><button type="submit" name="submit">Submit</button>
                </form>
                ';
                echo '<form method="POST"><button name="return">Return to previous page</button></form>';
                if(isset($_POST['return'])){
                        header("Location: ../ulib.php");
                        exit();
                    
                }
            }
        ?>
        <?php
            if(isset($_GET['submit'])){
                echo '<table style="font-size:22px;margin-left:auto;margin-right:auto;border-collapse: collapse;text-align:left;width:auto;">';
                echo '<tr><th>Library</th><td>'.$libmessage.'</td></tr>';
                echo '<tr><th>Area</th><td>'.$area.'</td></tr>';
                echo '<tr><th>Date</th><td>'.$date.'</td></tr>';
                echo '<tr><th>Time</th><td>'.$starttime.'  to  '.$endtime.'</td></tr>';
                // echo '<tr><th style="padding-right: 15px;">end time:</th><td>'.$endtime.'</td></tr>';
                echo '</table>';
                
                
                $areaseatsql = "SELECT `seatnum` FROM `areainfo` WHERE `area`='".$area."' AND `lib`='".$lib."'";
                $seatresult = mysqli_query($conn, $areaseatsql);
                $seatrow = mysqli_fetch_array($seatresult);

                $selsql = "SELECT seatid FROM bookrecord WHERE lib='".$lib."'AND area='".$area."' AND bookdate='".$date."' AND ((starttime<='".$starttime."' AND endtime>='".$starttime."') OR (starttime<='".$endtime."' AND endtime>='".$endtime."') OR (starttime>='".$starttime."' AND endtime<='".$endtime."')) ORDER BY length(seatid) ASC, seatid ASC;";
                $result = mysqli_query($conn, $selsql);

                if(!mysqli_num_rows($result)) {
                    echo "<p align='center' style='font-size:18px;font-family:arial;'>There is no seat booked<br>
                    You can choose <font color='red' style='font-size:24px;'>ANY</font> seat in this area with seat id 
                    <font color='red' style='font-size:24px;'>".$area."1-".$area."".$seatrow['seatnum'];
                    echo "</font>";
                }
                else {
                    // output data of each row
                    echo "<p align='center' style='font-size:24px'>Seat(s) reserved:<br>";
                    echo "<font align='center' style='font-size:28px;color:indigo;font-family:calibri;font-weight:bold;'>" ;
                    while($row = mysqli_fetch_assoc($result)) {
                        echo $row['seatid'].", ";
                    }
                    echo "</font></p>";
                    echo "<p align='center' style='font-size:20px'>
                    You can choose <font color='red'>OTHER</font> seats in this area with seat id:<br>
                    <font align='center' style='font-size:28px;color:indigo;font-family:calibri;font-weight:bold;'>
                    ".$area."1 - ".$area."".$seatrow['seatnum'];
                    echo "</font></p>";
                }
                
            }

        ?>

        <?php
            if(isset($_GET['submit'])){
                echo '<form action="" method="POST">';
                echo '<p align="center" style="font-size:20px;">Which seat do you want to choose?</p>
                <input type="text" id="seat" name="seat" class="bs" placeholder="'.$area.'1-'.$area.$seatrow['seatnum'].'"><br>';
                echo '<p align="center" style="font-size:20px;">Please login again to confirm the booking.</p><br>';
                        if(isset($_SESSION['sid'])){
                            echo '<input type="text" name="sid" placeholder="Student/Staff ID" value="'.$_SESSION['sid'].'" class="bs"><br>';
                        }
                        else{
                            echo '<input type="text" name="sid" placeholder="Student/Staff ID" class="bs"><br>';
                        }
                    

                echo '<input type="password" name="pwd" placeholder="Password" class="bs">
                    <br>
                    <button type="submit" name="login-submit" style="width:416px">Confirm</button>
                </form>';
                echo '<form method="POST"><button name="return" style="width:416px">Return to previous page</button></form>';
                if(isset($_POST['return'])){
                    header("Location: ../ulib/bookulib.php?area=".$area);
                    exit();
                }
            }
        ?>
    </body>
</html>