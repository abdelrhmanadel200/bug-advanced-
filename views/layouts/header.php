<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bug Tracking System</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <header>
        <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
            <div class="container">
                <a class="navbar-brand" href="index.php">Bug Tracking System</a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse" id="navbarNav">
                    <ul class="navbar-nav me-auto">
                        <?php if (isset($_SESSION['user_id'])): ?>
                            <li class="nav-item">
                                <a class="nav-link" href="index.php?controller=dashboard&action=index">Dashboard</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="index.php?controller=bug&action=list">Bugs</a>
                            </li>
                            <?php if ($_SESSION['user_role'] !== 'customer'): ?>
                                <li class="nav-item">
                                    <a class="nav-link" href="index.php?controller=project&action=list">Projects</a>
                                </li>
                            <?php endif; ?>
                            <?php if ($_SESSION['user_role'] === 'administrator'): ?>
                                <li class="nav-item">
                                    <a class="nav-link" href="index.php?controller=user&action=list">Users</a>
                                </li>
                            <?php endif; ?>
                        <?php endif; ?>
                    </ul>
                    <ul class="navbar-nav">
                        <?php if (isset($_SESSION['user_id'])): ?>
                            <!-- Notification Dropdown -->
                             
                            <li class="nav-item dropdown">
                                <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                    <?php echo $_SESSION['user_name']; ?>
                                </a>
                                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown">
                                    <li><a class="dropdown-item" href="index.php?controller=user&action=profile">Profile</a></li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item" href="index.php?controller=auth&action=logout">Logout</a></li>
                                </ul>
                            </li>
                        <?php else: ?>
                            <li class="nav-item">
                                <a class="nav-link" href="index.php?controller=auth&action=login">Login</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="index.php?controller=auth&action=register">Register</a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
        </nav>
    </header>
    
    <div class="container mt-4">
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
    </div>
