<?php

require 'PHPMailer/PHPMailerAutoload.php' ;
$mail = new PHPMailer;
$mail->isSMTP();

$mail->Host='smtp.gmail.com';
$mail->Port=587;
$mail->SMTPAuth=true;
$mail->SMTPSecure='tls';
$mail->Username='booklibseat@gmail.com';
$mail->Password='Bls114514';

$mail->setFrom('confirm@booklibseat.org','Book Lib Seat');
$mail->addCC('1155109349@link.cuhk.edu.hk');

$mail->isHTML(true);
$mail->Subject='Book Lib Seat Confirmation';
$mail->Body='<h1 align=center>Hello<h1>';

// if(!$mail->send()){
//     echo "Email is not sent!";
// }
// else{
//     echo "Email is sent";
// }
