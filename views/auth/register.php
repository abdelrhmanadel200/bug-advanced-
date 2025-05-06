<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Bug Tracking System</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    
    <!-- Custom CSS -->
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body class="text-center bg-light">
    <main class="form-register mt-5">
        <form method="POST" action="index.php?controller=auth&action=register" class="needs-validation" novalidate>
            <img class="mb-4" src="assets/images/logo.png" alt="Logo" width="72" height="72">
            <h1 class="h3 mb-3 fw-normal">Bug Tracking System</h1>
            <h2 class="h5 mb-3 fw-normal">Create an account</h2>
            
            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?php echo $_SESSION['success']; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php unset($_SESSION['success']); ?>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['errors'])): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <ul class="mb-0">
                        <?php foreach ($_SESSION['errors'] as $error): ?>
                            <li><?php echo $error; ?></li>
                        <?php endforeach; ?>
                    </ul>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php unset($_SESSION['errors']); ?>
            <?php endif; ?>
            
            <div class="form-floating mb-3">
                <input type="text" class="form-control" id="name" name="name" placeholder="Full Name" required>
                <label for="name">Full Name</label>
                <div class="invalid-feedback">
                    Please provide your full name.
                </div>
            </div>
            
            <div class="form-floating mb-3">
                <input type="email" class="form-control" id="email" name="email" placeholder="name@example.com" required>
                <label for="email">Email address</label>
                <div class="invalid-feedback">
                    Please provide a valid email address.
                </div>
            </div>
            
            <div class="form-floating mb-3">
                <input type="password" class="form-control" id="password" name="password" placeholder="Password" required minlength="6">
                <label for="password">Password</label>
                <div class="invalid-feedback">
                    Password must be at least 6 characters.
                </div>
            </div>
            
            <div class="form-floating mb-3">
                <input type="password" class="form-control" id="confirm_password" name="confirm_password" placeholder="Confirm Password" required>
                <label for="confirm_password">Confirm Password</label>
                <div class="invalid-feedback">
                    Passwords must match.
                </div>
            </div>
            
            <button class="w-100 btn btn-lg btn-primary mb-3" type="submit">Register</button>
            
            <div class="mt-3">
                <span>Already have an account?</span>
                <a href="index.php?controller=auth&action=login" class="text-decoration-none">Sign in</a>
            </div>
            
            <p class="mt-5 mb-3 text-muted">&copy; <?php echo date('Y'); ?> Bug Tracking System</p>
        </form>
    </main>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Form validation
        (function() {
            'use strict';
            
            // Fetch all the forms we want to apply custom Bootstrap validation styles to
            var forms = document.querySelectorAll('.needs-validation');
            
            // Loop over them and prevent submission
            Array.prototype.slice.call(forms).forEach(function(form) {
                form.addEventListener('submit', function(event) {
                    if (!form.checkValidity()) {
                        event.preventDefault();
                        event.stopPropagation();
                    }
                    
                    // Check if passwords match
                    var password = document.getElementById('password');
                    var confirmPassword = document.getElementById('confirm_password');
                    
                    if (password.value !== confirmPassword.value) {
                        confirmPassword.setCustomValidity('Passwords do not match');
                        event.preventDefault();
                        event.stopPropagation();
                    } else {
                        confirmPassword.setCustomValidity('');
                    }
                    
                    form.classList.add('was-validated');
                }, false);
            });
        })();
    </script>
</body>
</html>
