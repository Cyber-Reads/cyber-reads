<?php

session_start();


require '../src/config/db_connection.php';

if ( $_SERVER["REQUEST_METHOD"] == "POST" ) {
    
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $password = $_POST['password'] ;

    
    $sql = "SELECT id, email, password FROM admins WHERE email = '$email'";
    
    $result = mysqli_query($conn, $sql);
    
    if ( $result && mysqli_num_rows($result) == 1 ) {

        $user = mysqli_fetch_assoc($result);

        $hashed_password = $user['password'];

        if (password_verify($password, $hashed_password)) {
            
            session_regenerate_id();

            $_SESSION["admin_loggedin"] = true ;
            $_SESSION["admin_id"] =  $user['id'];
            $_SESSION["admin_email"]  = $user['email'];
            
            header("location: dashboard.php" );

            exit();
        } 
        
        else {
            
            header("location: admin.php?error=1");
            exit();

        }
    } 
    
    else {
        
        header("location: admin.php?error=1");
        exit();
    }
    
    mysqli_close($conn);
}

?>