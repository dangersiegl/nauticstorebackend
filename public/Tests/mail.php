<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

require '../../scripts/PHPMailer/PHPMailer.php';
require '../../scripts/PHPMailer/SMTP.php';
require '../../scripts/PHPMailer/Exception.php';

$mail = new PHPMailer(true);

try {
    // SMTP Konfiguration
    $mail->isSMTP();
    $mail->Host = 'w01bfc76.kasserver.com'; // SMTP-Server
    $mail->SMTPAuth = true;
    $mail->Username = 'nauticstore24@jackydoo.at'; // SMTP-Benutzername
    $mail->Password = 'drFxrPgDYyeJcEt9z8d7'; // SMTP-Passwort
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = 587;

    // Absender und Empfänger
    $mail->setFrom('nauticstore24@jackydoo.at', 'Nauticstore24.at');
    $mail->addAddress('michael.siegl@gmail.com', 'Michael Siegl');
    $mail->addReplyTo('nauticstore24@jackydoo.at', 'Nauticstore24.at');

    // Anhang hinzufügen
    $file = 'datei.pdf'; // Pfad zur Datei
    if (file_exists($file)) {
        $mail->addAttachment($file);
    }

    // Inhalt der E-Mail
    $mail->isHTML(true);
    $mail->Subject = 'Test-E-Mail mit Anhang';
    $mail->Body = '<html><body><h1>Hallo!</h1><p>Dies ist eine Test-E-Mail mit Anhang.</p></body></html>';
    $mail->AltBody = 'Hallo! Dies ist eine Test-E-Mail mit Anhang.';

    // E-Mail senden
    $mail->send();
    echo 'E-Mail erfolgreich gesendet.';
} catch (Exception $e) {
    echo 'Fehler beim Senden der E-Mail: ' . $mail->ErrorInfo;
}

?>
