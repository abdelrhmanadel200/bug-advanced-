<?php include 'views/layouts/header.php'; ?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Edit User</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <a href="index.php?controller=user&action=list" class="btn btn-sm btn-secondary">
            <i class="fas fa-arrow-left me-1"></i> Back to Users
        </a>
    </div>
</div>

<div class="card mb-4">
    <div class="card-header">
        <h5 class="card-title mb-0">User Information</h5>
    </div>
    <div class="card-body">
        <form method="POST" action="index.php?controller=user&action=edit&id=<?php echo $user->id; ?>" class="needs-validation" novalidate>
            <div class="mb-3">
                <label for="name" class="form-label">Full Name <span class="text-danger">*</span></label>
                <input type="text" class="form-control" id="name" name="name" value="<?php echo $user->name; ?>" required>
                <div class="invalid-feedback">
                    Please provide a full name.
                </div>
            </div>
            
            <div class="mb-3">
                <label for="email" class="form-label">Email Address <span class="text-danger">*</span></label>
                <input type="email" class="form-control" id="email" name="email" value="<?php echo $user->email; ?>" required>
                <div class="invalid-feedback">
                    Please provide a valid email address.
                </div>
            </div>
            
            <div class="mb-3">
                <label for="role" class="form-label">Role <span class="text-danger">*</span></label>
                <select class="form-select" id="role" name="role" required>
                    <option value="administrator" <?php echo $user->role === 'administrator' ? 'selected' : ''; ?>>Administrator</option>
                    <option value="staff" <?php echo $user->role === 'staff' ? 'selected' : ''; ?>>Staff</option>
                    <option value="customer" <?php echo $user->role === 'customer' ? 'selected' : ''; ?>>Customer</option>
                </select>
                <div class="invalid-feedback">
                    Please select a role.
                </div>
            </div>
            
            <div class="mb-3">
                <label for="status" class="form-label">Status <span class="text-danger">*</span></label>
                <select class="form-select" id="status" name="status" required>
                    <option value="active" <?php echo $user->status === 'active' ? 'selected' : ''; ?>>Active</option>
                    <option value="inactive" <?php echo $user->status === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                </select>
                <div class="invalid-feedback">
                    Please select a status.
                </div>
            </div>
            
            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                <button type="submit" class="btn btn-primary">Update User</button>
            </div>
        </form>
    </div>
</div>

<?php include 'views/layouts/footer.php'; ?>
