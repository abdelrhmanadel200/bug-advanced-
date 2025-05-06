<?php include 'views/layouts/header.php'; ?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">My Profile</h1>
</div>

<div class="row">
    <div class="col-md-8">
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="card-title mb-0">Update Profile</h5>
            </div>
            <div class="card-body">
                <form method="POST" action="index.php?controller=user&action=profile" class="needs-validation" novalidate>
                    <div class="mb-3">
                        <label for="name" class="form-label">Full Name</label>
                        <input type="text" class="form-control" id="name" name="name" value="<?php echo $user['name']; ?>" required>
                        <div class="invalid-feedback">
                            Please provide your full name.
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="email" class="form-label">Email Address</label>
                        <input type="email" class="form-control" id="email" name="email" value="<?php echo $user['email']; ?>" required>
                        <div class="invalid-feedback">
                            Please provide a valid email address.
                        </div>
                    </div>
                    
                    <hr class="my-4">
                    <h6 class="mb-3">Change Password (Optional)</h6>
                    
                    <div class="mb-3">
                        <label for="current_password" class="form-label">Current Password</label>
                        <input type="password" class="form-control" id="current_password" name="current_password">
                        <div class="form-text">Leave blank if you don't want to change your password.</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="new_password" class="form-label">New Password</label>
                        <input type="password" class="form-control" id="new_password" name="new_password" minlength="6">
                        <div class="invalid-feedback">
                            Password must be at least 6 characters.
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="confirm_password" class="form-label">Confirm New Password</label>
                        <input type="password" class="form-control" id="confirm_password" name="confirm_password">
                        <div class="invalid-feedback">
                            Passwords must match.
                        </div>
                    </div>
                    
                    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                        <button type="submit" class="btn btn-primary">Update Profile</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="card-title mb-0">Account Information</h5>
            </div>
            <div class="card-body">
                <ul class="list-group list-group-flush">
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <span>User ID:</span>
                        <span class="fw-bold"><?php echo $user['id']; ?></span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <span>Role:</span>
                        <span class="badge bg-<?php 
                            echo $user['role'] === 'administrator' ? 'danger' : 
                                ($user['role'] === 'staff' ? 'primary' : 'info'); 
                        ?>">
                            <?php echo ucfirst($user['role']); ?>
                        </span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <span>Status:</span>
                        <span class="badge bg-<?php echo $user['status'] === 'active' ? 'success' : 'secondary'; ?>">
                            <?php echo ucfirst($user['status']); ?>
                        </span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <span>Created:</span>
                        <span><?php echo date('M d, Y', strtotime($user['created_at'])); ?></span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <span>Last Login:</span>
                        <span><?php echo $user['last_login'] ? date('M d, Y H:i', strtotime($user['last_login'])) : 'Never'; ?></span>
                    </li>
                </ul>
            </div>
        </div>
        
        <?php if (isset($_SESSION['github_token']) && ($_SESSION['user_role'] === 'administrator' || $_SESSION['user_role'] === 'staff')): ?>
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="card-title mb-0">GitHub Integration</h5>
            </div>
            <div class="card-body">
                <p class="github-connected">
                    <i class="fas fa-check-circle me-1"></i> Connected as <?php echo $_SESSION['github_username']; ?>
                </p>
                <div class="d-grid gap-2">
                    <a href="index.php?controller=github&action=repositories" class="btn btn-dark">
                        <i class="fab fa-github me-1"></i> View Repositories
                    </a>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

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
                
                // Check if passwords match if changing password
                var currentPassword = document.getElementById('current_password');
                var newPassword = document.getElementById('new_password');
                var confirmPassword = document.getElementById('confirm_password');
                
                if (currentPassword.value && (newPassword.value !== confirmPassword.value)) {
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

<?php include 'views/layouts/footer.php'; ?>
