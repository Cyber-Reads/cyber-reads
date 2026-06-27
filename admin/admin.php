<?php

session_start();


if (isset($_SESSION['admin_loggedin']) && $_SESSION['admin_loggedin'] === true) {
    header("location: dashboard.php");
    exit;
}

?>



<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - Cyber Reads</title>
    
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="icon" type="image/png" href="img/icon.png">
    <link rel="stylesheet" href="../assets/css/admin.css"/>

</head>
<body class="items-center justify-center p-4 relative min-h-screen flex ">

    
    
<div class="bubbles">

    <div class="bubble"></div>
    <div class="bubble"></div>
    <div class="bubble"></div>
    <div class="bubble"></div>
    <div class="bubble"></div>
    <div class="bubble"></div>
    <div class="bubble"></div>
    <div class="bubble"></div>
    <div class="bubble"></div>
    <div class="bubble"></div>
    <div class="bubble"></div>
    <div class="bubble"></div>
    <div class="bubble"></div>
    <div class="bubble"></div>
    <div class="bubble"></div>
    <div class="bubble"></div>
    <div class="bubble"></div>
    <div class="bubble"></div>
    <div class="bubble"></div>
    <div class="bubble"></div>

</div>

   


 
    <!-- Main Container -->
    <div class="relative z-10 w-full max-w-md">

        <!-- Floating Card with Animation -->
        <div class="float-animation">

            <!-- Main Card -->
            <div class="card-container slide-in pulse-glow rounded-3xl overflow-hidden shadow-2xl">

                <!-- Blue Accent Line -->
                <div class="accent-line h-0.5"></div>
                
                <!-- Card Content -->
                <div class="p-10">


                    <!-- Animated Logo -->
                    <div class="text-center mb-8">
                        <div class="inline-block hover-lift cursor-pointer">
                            <div class="logo-box w-20 h-20 rounded-2xl flex items-center justify-center mx-auto mb-4">
                                <img src="../assets/img/logo1.jpg" alt="">
                            </div>
                            <h1 class="title text-3xl font-bold mb-2">
                                Welcome Back
                            </h1>
                            <p class="subtitle text-sm">
                                Sign in to Cyber Reads Admin
                            </p>
                        </div>
                    </div>



                    <?php 

                    if(isset($_GET['error'])){

                        echo '<div class="error-container rounded-xl p-4 mb-6 border">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0">
                                        <svg class="error-text w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                                        </svg>
                                    </div>
                                    <p class="error-text ml-3 text-sm">
                                        Authentication failed. Please check your credentials.
                                    </p>
                                </div>
                              </div>';

                    }


                    ?>

                    <!-- Form -->
                    <form class="space-y-5" action="auth.php" method="POST">

                        <!-- Email Input -->
                        <div class="group">

                            <label class="label-text block text-sm font-medium mb-2 transition-colors">
                                Email Address
                            </label>


                            <div class="relative">
                                <input type="email" name="email" required class="input-field input-focus w-full  px-4 py-3.5 rounded-xl border-2 transition-all  outline-none"  placeholder="admin@cyberreads.com" >
                                
                                <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                                    <svg class="input-icon w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.207"></path>
                                    </svg>
                                </div>

                            </div>

                        </div>

                        <!-- Password Input -->
                        <div class="group">
                            
                            <label class="label-text block text-sm font-medium mb-2 transition-colors">
                                Password
                            
                            </label>
                            
                            <div class="relative">
                                <input type="password" name="password"  required class = "input-field input-focus w-full px-4 py-3.5 rounded-xl border-2 transition-all  outline-none"  placeholder="••••••••" >
                                <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                                    
                                    <svg class="input-icon w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                                    
                                    </svg>
                                
                                </div>
                            </div>
                        
                        
                        </div>

                        <!-- Remember & Forgot -->
                        <div class="flex items-center justify-between">
                            
                            <label class="flex items-center cursor-pointer group">
                                <input type="checkbox" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                <span class="remember-text ml-2 text-sm transition-colors">
                                    Remember me
                                </span>
                            
                            </label>
                            
                            <a href="#" class="forgot-link text-sm transition-all hover:translate-x-1">
                                Forgot password?
                            </a>


                        </div>

                        <!-- Submit Button -->
                        <button type="submit"  class="submit-btn hover-lift w-full py-3.5 rounded-xl font-medium transition-all flex items-center justify-center group shadow-md hover:shadow-lg" >
                            
                            <span>Sign In</span>
                            
                            <svg class="w-5 h-5 ml-2 transition-transform group-hover:translate-x-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"></path>
                            
                            </svg>
                        </button>

                    </form>

                    <!-- Footer -->
                    
                    <div class="footer-border mt-8 pt-6 border-t">
                        
                        <p class="footer-text text-center text-xs">
                            Cyber Reads • © 2025
                        
                        </p>

                    </div>

                </div>
            </div>
        </div>
    
    
    </div>


</body>

</html>
