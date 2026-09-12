<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// User session ekak nathnam kelinma login page ekata elවනවා
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}
?>