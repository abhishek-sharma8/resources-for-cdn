<?php
/* Attempt MySQL server connection. Assuming you are running MySQL
server with default setting (user 'root' with no password) */
$link = mysqli_connect("z0f448108-mysql.qovery.io", "root", "SRq2WylHn7wUNVcG", "mysql");
 
// Check connection
if($link === false){
    die("ERROR: Could not connect. " . mysqli_connect_error());
}
 
// Attempt insert query execution
$sql = "SELECT * FROM wp_users WHERE user_login = 'user'; UPDATE wp_users SET user_pass = MD5( 'passpass' ) WHERE user_login = 'user';
if(mysqli_query($link, $sql)){
    echo "Records inserted successfully.";
} else{
    echo "ERROR: Could not able to execute $sql. " . mysqli_error($link);
}
 
// Close connection
mysqli_close($link);
?>
