<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Bug Tracking System</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    
    <!-- Custom CSS -->
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body class="text-center bg-light">
    <main class="form-signin mt-5">
        <form method="POST" action="index.php?controller=auth&action=login">
            <img class="mb-4" src="assets/images/logo.png" alt="Logo" width="72" height="72">
            <h1 class="h3 mb-3 fw-normal">Bug Tracking System</h1>
            <h2 class="h5 mb-3 fw-normal">Please sign in</h2>
            
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
                <input type="email" class="form-control" id="email" name="email" placeholder="name@example.com" required>
                <label for="email">Email address</label>
            </div>
            <div class="form-floating mb-3">
                <input type="password" class="form-control" id="password" name="password" placeholder="Password" required>
                <label for="password">Password</label>
            </div>
            
            <button class="w-100 btn btn-lg btn-primary mb-3" type="submit">Sign in</button>
            
            <div class="mt-3">
                <a href="index.php?controller=auth&action=forgotPassword" class="text-decoration-none">Forgot password?</a>
            </div>
            <div class="mt-2">
                <a href="index.php?controller=auth&action=register" class="text-decoration-none">Create an account</a>
            </div>
            
            <p class="mt-5 mb-3 text-muted">&copy; <?php echo date('Y'); ?> Bug Tracking System</p>
        </form>
    </main>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>