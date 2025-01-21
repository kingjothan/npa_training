<?php
// generate_password.php

// Replace 'admin123' with the password you want to hash.
$password = 'admin123';
$hashed_password = password_hash($password, PASSWORD_DEFAULT);

echo $hashed_password;

?>
