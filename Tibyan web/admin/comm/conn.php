<?php
$conn = new mysqli(
    'blbwriu20pssviklccht-mysql.services.clever-cloud.com',
    'ugiow0myriby1vii',
    '9Z0ByFMaWIYJtLin7Plu',
    'blbwriu20pssviklccht',
    3306
);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>