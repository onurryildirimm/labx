<!DOCTYPE html>
<html>
<head>
        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <!-- Basic Meta Tags -->
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="description" content="Labx - Laboratory Reporting and Management System. Upload, manage, and view your lab reports easily.">
<meta name="keywords" content="Labx, laboratory, reporting, analysis, test, water, food, environmental analysis">
<meta name="author" content="Vektraweb">

<!-- Page Title -->
<title>Labx - Laboratory Management Panel</title>

<!-- Open Graph Meta (for Facebook, LinkedIn, etc.) -->
<meta property="og:title" content="Labx - Laboratory Management Panel">
<meta property="og:description" content="Manage your lab analysis reports efficiently with Labx.">
<meta property="og:image" content="https://www.vektraweb.com.tr/labx/imgs/labx-preview.png">
<meta property="og:url" content="https://www.vektraweb.com.tr/labx/">
<meta property="og:type" content="website">

<!-- Twitter Card Meta -->
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="Labx - Laboratory Management Panel">
<meta name="twitter:description" content="Manage your lab analysis reports efficiently with Labx.">
<meta name="twitter:image" content="https://www.vektraweb.com.tr/labx/imgs/labx-preview.png">
        <!-- Favicon -->
        <link rel="icon" type="image/png" sizes="32x32" href="/imgs/favicon.png">
        <link rel="icon" type="image/png" sizes="16x16" href="/imgs/favicon.png">
        <link rel="shortcut icon" href="imgs/favicon.ico">
        

        <!-- inject:css -->
        <link rel="stylesheet" href="bower_components/bootstrap/dist/css/bootstrap.min.css">
        <link rel="stylesheet" href="bower_components/font-awesome/css/font-awesome.min.css">
        <link rel="stylesheet" href="bower_components/simple-line-icons/css/simple-line-icons.css">
        <link rel="stylesheet" href="bower_components/weather-icons/css/weather-icons.min.css">
        <link rel="stylesheet" href="bower_components/themify-icons/css/themify-icons.css">
        
        <!-- endinject -->

        <!-- Main Style  -->
        <link rel="stylesheet" href="dist/css/main.css">

        <script src="assets/js/modernizr-custom.js"></script>
        <script src="https://www.google.com/recaptcha/api.js" async defer></script>

    </head>
    <style>
    body, html {
    width: 100%;
    height: 100%;
    overflow-x: hidden;
}
@media (max-width: 576px) {
    .g-recaptcha {
        transform: scale(0.85);
        transform-origin: 0 0;
    }
}

#bg-video {
    position: fixed;
    right: 0;
    bottom: 0;
    min-width: 100%;
    min-height: 100%;
    z-index: -1; /* arka plana göndermek için */
    object-fit: cover; /* Videonun oranlarını koru */
}

.sign-container {
    max-width: 400px;
    margin: auto;
    padding: 30px;
    background: white;
    border-radius: 10px;
    box-shadow: 0 0 10px rgba(0,0,0,0.1);
}
.sign-in-wrapper {
    background-color: rgba(255,255,255,10%); /* Sayfanın okunurluğunu artırmak için */
    height: 100%;
    position: relative;
    z-index: 1; /* Videonun önüne getirmek için */
}
</style>
        <!-- Video Arka Planı -->
<video autoplay muted loop id="bg-video">
    <source src="video/bg.mp4" type="video/mp4">
</video>
    <body>
        
<?php if (isset($_GET['error'])): ?>
    <?php if ($_GET['error'] === 'invalid'): ?>
        <div class="alert alert-danger text-center">Invalid Username or Password!</div>
    <?php elseif ($_GET['error'] === 'captcha'): ?>
        <div class="alert alert-danger text-center">Please verify that you are not a robot!</div>
    <?php endif; ?>
<?php endif; ?>
        <div class="sign-in-wrapper">
            <div class="sign-container">
                <div class="text-center">
                    <h2 class="logo"><img src="https://www.vektraweb.com.tr/labx/imgs/pro-logo.png" width="280px" alt=""/></h2><hr>
                    <h4>Login Form</h4>
                </div>

                <form class="sign-in-form" role="form" action="login_process.php" method="POST">
    <div class="form-group">
        <input type="text" class="form-control" name="username" placeholder="User Mail" required>
    </div>
    <div class="form-group">
        <input type="password" class="form-control" name="password" placeholder="Password" required>
    </div>
    <div class="g-recaptcha" data-sitekey="6LeZNKMUAAAAACLAMiRB7qQgYkEquxSh6liMNNc4"></div>
    <br>
    <button type="submit" class="btn btn-info btn-block">Login</button><br>
    <small>Vektraweb - Copyright © 2025</small>
</form>

               
            </div>
        </div>

        <!-- inject:js -->
        <script src="bower_components/jquery/dist/jquery.min.js"></script>
        <script src="bower_components/bootstrap/dist/js/bootstrap.min.js"></script>
        <script src="bower_components/jquery.nicescroll/dist/jquery.nicescroll.min.js"></script>
        <script src="bower_components/autosize/dist/autosize.min.js"></script>
        <!-- endinject -->

        <!-- Common Script   -->
        <script src="dist/js/main.js"></script>

    </body>

</html>
