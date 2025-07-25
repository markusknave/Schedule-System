<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" type="image/png" href="./assets/img/favicon.png">
    <script src="/myschedule/assets/js/jquery.min.js"></script>
    <link rel="stylesheet" href="./assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="./assets/css/all.min.css">
    <link rel="stylesheet" href="./assets/css/uf-style.css">
    <title>Login</title>
    <style>
        body {
            background-color: #1a1a2e;
            overflow: hidden;
        }
        
        .fade-out {
            animation: slideOut 0.5s forwards;
        }

        @keyframes slideOut {
            from {
                transform: translateX(0);
                opacity: 1;
            }
            to {
                transform: translateX(-100%);
                opacity: 0;
            }
        }
        
        .error-message {
            color: #ff6b6b;
            text-align: center;
            margin-bottom: 15px;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="uf-form-signin" id="loginForm">
        <div class="text-center">
            <img src="./assets/img/favicon.png" alt="" width="200" height="200">
            <h1 class="text-white h3">Account Login</h1>
        </div>
        <form class="mt-4" action="./components/login.php" method="POST">
            <div id="errorMessage" class="error-message">
                <?php 
                if (isset($_GET['error']) && $_GET['error'] == '1') {
                    echo 'Invalid email or password!';
                }
                ?>
            </div>
            <div class="input-group uf-input-group mb-3">
                <span class="input-group-text fa fa-user" style="color: #000ed3;"></span>
                <input type="text" class="form-control" name="email" placeholder="Email address" required>
            </div>
            <div class="input-group uf-input-group mb-3">
                <span class="input-group-text fa fa-lock" style="color: #000ed3;"></span>
                <input type="password" class="form-control" name="passwords" placeholder="Password" id="password" required>
                <button class="btn btn-light toggle-password" type="button" data-target="#password">
                    <i class="fas fa-eye" style="color: #000ed3;"></i>
                </button>
            </div>
            <div class="d-grid mb-4">
                <button type="submit" class="btn uf-btn-primary btn-lg">Login</button>
            </div>
            <div class="mt-4 text-center">
                <span class="text-white">Don't have an account?</span>
                <a href="register.php" class="text-white text-decoration-underline" id="registerLink">Sign Up</a>
            </div>
        </form>
    </div>

    <script>
        document.getElementById("registerLink").addEventListener("click", function(event) {
            event.preventDefault(); 
            document.getElementById("loginForm").classList.add("fade-out");
            setTimeout(() => {
                window.location.href = "register.php"; 
            }, 500);
        });

        $(document).ready(function() {
            $('.toggle-password').click(function() {
                var input = $(this).data('target');
                var type = $(input).attr('type') === 'password' ? 'text' : 'password';
                $(input).attr('type', type);
                $(this).find('i').toggleClass('fa-eye fa-eye-slash');
            });
        });
    </script>
</body>
</html>