<?php

require_once __DIR__ . "/../../PHPMailer/PHPMailer/src/PHPMailer.php";
require_once __DIR__ . "/../../PHPMailer/PHPMailer/src/SMTP.php";
require_once __DIR__ . "/../../PHPMailer/PHPMailer/src/Exception.php";

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

function getMailer() {

    $mail = new PHPMailer(true);

    $mail->isSMTP();
    $mail->Host       = 'smtp.gmail.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = 'vanhanguyen2k4@gmail.com';
    $mail->Password   = 'wbqr kojt zezb yuxl';
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = 587;

    $mail->setFrom('vanhanguyen2k4@gmail.com', 'Phòng khám Clinic');
    $mail->isHTML(true);

    return $mail;
}