<?php
$host = "localhost";
$user = "root";
$password = ""; // XAMPP default password eka blank
$dbname = "mill_rice_db";

// Connection eka create kirima
$conn = new mysqli($host, $user, $password, $dbname);

// Connection eka check kirima
if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

// Unicode (Sinhala) data thiyenam hariyata weda karanna meka ona
$conn->set_charset("utf8mb4"); 
?>