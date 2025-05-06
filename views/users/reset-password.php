<?php include 'views/layouts/header.php'; ?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Reset Password</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <a href="index.php?controller=user&action=list" class="btn btn-sm btn-secondary">
            <i class="fas fa-arrow-left me-1"></i> Back to Users
        </a>
    </div>
</div>

<div class="card mb-4">
    <div class="card-header">
        <h5 class="card-title mb-0">Reset Password for <?php echo $user->name; ?></h5>
    </div>
    <div class="card-body">
        <form method="POST" action="index.php?controller=user&action=resetPassword&id=<?php echo $user->id; ?>" class="needs-validation" novalidate>
            <div class="mb-3">
                <label for="password" class="form-label">New Password <span class="text-danger">*</span></label>
                <input type="password" class="form-control" id="password" name="password" required minlength="6">
                <div class="invalid-feedback">
                    Password must be at least 6 characters.
                </div>
            </div>
            
            <div class="mb-3">
                <label for="confirm_password" class="form-label">Confirm New Password <span class="text-danger">*</span></label>
                <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                <div class="invalid-feedback">
                    Passwords must match.
                </div>
            </div>
            
            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                <button type="submit" class="btn btn-primary">Reset Password</button>
            </div>
        </form>
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

<?php include 'views/layouts/footer.php'; ?>
