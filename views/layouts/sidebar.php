<nav id="sidebarMenu" class="col-md-3 col-lg-2 d-md-block bg-light sidebar collapse">
    <div class="position-sticky pt-3">
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link <?php echo ($_GET['controller'] === 'dashboard' && $_GET['action'] === 'index') ? 'active' : ''; ?>" href="index.php?controller=dashboard&action=index">
                    <i class="fas fa-tachometer-alt me-1"></i> Dashboard
                </a>
            </li>
            
            <li class="nav-item">
                <a class="nav-link <?php echo ($_GET['controller'] === 'bug' && $_GET['action'] === 'list') ? 'active' : ''; ?>" href="index.php?controller=bug&action=list">
                    <i class="fas fa-bug me-1"></i> Bugs
                </a>
            </li>
            
            <?php if ($_SESSION['user_role'] === 'customer'): ?>
                <li class="nav-item">
                    <a class="nav-link <?php echo ($_GET['controller'] === 'bug' && $_GET['action'] === 'report') ? 'active' : ''; ?>" href="index.php?controller=bug&action=report">
                        <i class="fas fa-plus-circle me-1"></i> Report Bug
                    </a>
                </li>
            <?php endif; ?>
            
            <li class="nav-item">
                <a class="nav-link <?php echo ($_GET['controller'] === 'project' && $_GET['action'] === 'list') ? 'active' : ''; ?>" href="index.php?controller=project&action=list">
                    <i class="fas fa-project-diagram me-1"></i> Projects
                </a>
            </li>
            
            <?php if ($_SESSION['user_role'] === 'administrator'): ?>
                <li class="nav-item">
                    <a class="nav-link <?php echo ($_GET['controller'] === 'user' && $_GET['action'] === 'list') ? 'active' : ''; ?>" href="index.php?controller=user&action=list">
                        <i class="fas fa-users me-1"></i> Users
                    </a>
                </li>
                
                <li class="nav-item">
                    <a class="nav-link <?php echo ($_GET['controller'] === 'dashboard' && $_GET['action'] === 'reports') ? 'active' : ''; ?>" href="index.php?controller=dashboard&action=reports">
                        <i class="fas fa-chart-bar me-1"></i> Reports
                    </a>
                </li>
            <?php endif; ?>
            
            <li class="nav-item">
                <a class="nav-link <?php echo ($_GET['controller'] === 'user' && $_GET['action'] === 'profile') ? 'active' : ''; ?>" href="index.php?controller=user&action=profile">
                    <i class="fas fa-user-cog me-1"></i> My Profile
                </a>
            </li>
            
            <?php if ($_SESSION['user_role'] === 'administrator' || $_SESSION['user_role'] === 'staff'): ?>
                <li class="nav-item">
                    <a class="nav-link <?php echo ($_GET['controller'] === 'bug' && $_GET['action'] === 'track') ? 'active' : ''; ?>" href="index.php?controller=bug&action=track">
                        <i class="fas fa-search me-1"></i> Track Bug
                    </a>
                </li>
            <?php endif; ?>
        </ul>
        
        <?php if ($_SESSION['user_role'] === 'administrator' || $_SESSION['user_role'] === 'staff'): ?>
            <h6 class="sidebar-heading d-flex justify-content-between align-items-center px-3 mt-4 mb-1 text-muted">
                <span>GitHub Integration</span>
            </h6>
            <ul class="nav flex-column mb-2">
                <?php if (isset($_SESSION['github_token'])): ?>
                    <li class="nav-item">
                        <a class="nav-link <?php echo ($_GET['controller'] === 'github' && $_GET['action'] === 'repositories') ? 'active' : ''; ?>" href="index.php?controller=github&action=repositories">
                            <i class="fab fa-github me-1"></i> Repositories
                        </a>
                    </li>
                    <li class="nav-item">
                        <span class="nav-link text-success">
                            <i class="fas fa-check-circle me-1"></i> Connected as <?php echo $_SESSION['github_username']; ?>
                        </span>
                    </li>
                <?php else: ?>
                    <li class="nav-item">
                        <a class="nav-link" href="index.php?controller=github&action=connect">
                            <i class="fab fa-github me-1"></i> Connect to GitHub
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
        <?php endif; ?>
    </div>
</nav>
