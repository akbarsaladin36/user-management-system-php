<?php

session_start();

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php';

$mail = new PHPMailer(true);

require_once 'auth.php';
$user = new Auth();

// Handle Ajax Register Request
if(isset($_POST['action']) && $_POST['action'] == 'register'){
    $name = $user->test_input($_POST['name']);
    $email = $user->test_input($_POST['email']);
    $pass = $user->test_input($_POST['password']);

    $hpass = password_hash($pass, PASSWORD_DEFAULT);

    //Function to check if user is already registered
    if($user->user_exist($email)){
        echo $user->showMessage('warning','This E-mail has been already registered.');
    } else {
        if($user->register($name,$email,$hpass)){
            echo 'register';
            $_SESSION['user'] = $email;

        } else {
            echo $user->showMessage('danger','Something is wrong! Try again later!');
        }
    }
}

// Handle Ajax Login Request
if(isset($_POST['action']) && $_POST['action'] == 'login'){
    $email = $user->test_input($_POST['email']);
    $pass = $user->test_input($_POST['password']);

    $LoggedInUser = $user->login($email);

    if($LoggedInUser != null){
        if(password_verify($pass, $LoggedInUser['password'])){
            if(!empty($_POST['rem'])){
                setcookie("email", $email, time()+(30*24*60*60), '/');
                setcookie("password", $pass, time()+(30*24*60*60), '/');
            } else {
                setcookie("email", "", 1, '/');
                setcookie("password", "", 1, '/');
            }
            echo 'login';
            $_SESSION['user'] = $email;
        } else {
            echo $user->showMessage('danger', 'Password is incorrect!');
        }
    } else {
        echo $user->showMessage('danger', 'User not found!');
    }
}

//Handle Ajax Forgot Password Request
if(isset($_POST['action']) && $_POST['action'] == 'forgot'){
    $email = $user->test_input($_POST['email']);

    $user_found = $user->currentUser($email);

    if($user_found != null){
        $token = uniqid();
        $token = str_shuffle($token);

        $user->forgot_password($token, $email);

        try {
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = Database::USERNAME;
            $mail->Password = Database::PASSWORD;
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = 587;

            $mail->setFrom(Database::USERNAME,'Admin ForumKita');
            $mail->addAddress($email);

            $mail->isHTML(true);
            $mail->Subject = 'Reset Password';
            $mail->Body = '<h3>Click the below link to reset your password.<br><a href="http://localhost/user_management_system/reset-pass.php?email='.$email.'&token='.$token.'">http://localhost/user_management_system/reset-pass.php?email='.$email.'&token='.$token.'</a><br>Regards,<br>Admin ForumKita</h3>';

            $mail->send();
            echo $user->showMessage('success', 'We have send a reset link to your e-mail. Please check your e-mail right now!');

        } catch (Exception $e) {
            echo $user->showMessage('danger', 'Something went wrong, please try again later!');
        }
    } else {
        echo $user->showMessage('info', 'This e-mail is not registered.');
    }
}

if(isset($_POST['action']) && $_POST['action'] == 'checkUser'){
    if(!$user->currentUser($_SESSION['user'])){
        echo 'bye';
        unset($_SESSION['user']);
    }
}


?>