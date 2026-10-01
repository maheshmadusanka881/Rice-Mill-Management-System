<?php
include('config/db.php');

require 'PHPMailer/Exception.php';
require 'PHPMailer/PHPMailer.php';
require 'PHPMailer/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$sql = "SELECT * FROM rice_stock WHERE available_bag_count < 10";
$result = $conn->query($sql);

while($row = $result->fetch_assoc()) {
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = '@gmail.com'; 
        $mail->Password = ''; 
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        // SSL ගැටලුව මඟහරවා ගැනීමට අදාළ සැකසුම
        $mail->SMTPOptions = array(
            'ssl' => array(
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            )
        );

        $mail->setFrom('minsada@system.com', 'Minsada Rice Mill');
        $mail->addAddress('@gmail.com'); // මෙතනට ඔයාට Alert එක එන්න ඕන ඊමේල් එක දාන්න

        $mail->isHTML(true);
        $mail->Subject = 'Stock Alert: ' . $row['rice_type'];
        $mail->Body    = "හාල් තොගය අවසන් වීගෙන යයි!<br><b>හාල් වර්ගය:</b> " . $row['rice_type'] . "<br><b>ඉතිරි මලු:</b> " . $row['available_bag_count'];
        
        $mail->send();
    } catch (Exception $e) {
        echo "Error: {$mail->ErrorInfo}";
    }
}
echo "Alert Check කළා.";
?>
