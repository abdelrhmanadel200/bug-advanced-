<?php
// Include configuration
require_once 'config/config.php';

// Check if user is logged in
$is_logged_in = isset($_SESSION['user_id']);

// Include header
include 'includes/header.php';
?>

<!-- Hero Section -->
<section class="hero-section">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-6 animate-fadeIn">
                <h1 class="display-4 fw-bold mb-4">Track, Manage, and Resolve Bugs Efficiently</h1>
                <p class="lead mb-4">A comprehensive bug tracking system designed to streamline your development workflow and improve team collaboration.</p>
                <div class="d-flex flex-wrap gap-3">
                    <?php if (!$is_logged_in): ?>
                        <a href="register.php" class="btn btn-primary btn-lg">
                            <i class="fas fa-user-plus me-2"></i>Get Started
                        </a>
                        <a href="login.php" class="btn btn-outline-primary btn-lg">
                            <i class="fas fa-sign-in-alt me-2"></i>Login
                        </a>
                    <?php else: ?>
                        <a href="dashboard.php" class="btn btn-primary btn-lg">
                            <i class="fas fa-tachometer-alt me-2"></i>Go to Dashboard
                        </a>
                    <?php endif; ?>
                </div>
            </div>
            <div class="col-lg-6 mt-5 mt-lg-0 animate-slideInRight">
                <img src="assets/images/hero-illustration.svg" alt="Bug Tracking Illustration" class="img-fluid">
            </div>
        </div>
    </div>
    
    <!-- Animated Bubbles -->
    <div class="bubble"></div>
    <div class="bubble"></div>
    <div class="bubble"></div>
    <div class="bubble"></div>
    <div class="bubble"></div>
</section>

<!-- Features Section -->
<section class="features-section py-5">
    <div class="container">
        <div class="text-center mb-5 animate-fadeIn">
            <h2 class="fw-bold">Powerful Features</h2>
            <p class="lead text-muted">Everything you need to manage bugs effectively</p>
        </div>
        
        <div class="row g-4">
            <div class="col-md-4 animate-slideInUp">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-body text-center p-4">
                        <div class="feature-icon bg-primary text-white rounded-circle mb-3">
                            <i class="fas fa-bug"></i>
                        </div>
                        <h3 class="h4 mb-3">Bug Tracking</h3>
                        <p class="text-muted mb-0">Track bugs from submission to resolution with detailed history and status updates.</p>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4 animate-slideInUp delay-1">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-body text-center p-4">
                        <div class="feature-icon bg-success text-white rounded-circle mb-3">
                            <i class="fas fa-project-diagram"></i>
                        </div>
                        <h3 class="h4 mb-3">Project Management</h3>
                        <p class="text-muted mb-0">Organize bugs by projects, assign team members, and monitor progress.</p>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4 animate-slideInUp delay-2">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-body text-center p-4">
                        <div class="feature-icon bg-info text-white rounded-circle mb-3">
                            <i class="fas fa-chart-bar"></i>
                        </div>
                        <h3 class="h4 mb-3">Reporting & Analytics</h3>
                        <p class="text-muted mb-0">Generate detailed reports and gain insights into bug trends and team performance.</p>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4 animate-slideInUp delay-3">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-body text-center p-4">
                        <div class="feature-icon bg-warning text-white rounded-circle mb-3">
                            <i class="fas fa-bell"></i>
                        </div>
                        <h3 class="h4 mb-3">Notifications</h3>
                        <p class="text-muted mb-0">Stay updated with real-time notifications for bug assignments and status changes.</p>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4 animate-slideInUp delay-4">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-body text-center p-4">
                        <div class="feature-icon bg-danger text-white rounded-circle mb-3">
                            <i class="fas fa-users"></i>
                        </div>
                        <h3 class="h4 mb-3">Team Collaboration</h3>
                        <p class="text-muted mb-0">Facilitate team communication with comments, mentions, and file sharing.</p>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4 animate-slideInUp delay-5">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-body text-center p-4">
                        <div class="feature-icon bg-accent text-white rounded-circle mb-3">
                            <i class="fas fa-mobile-alt"></i>
                        </div>
                        <h3 class="h4 mb-3">Mobile Friendly</h3>
                        <p class="text-muted mb-0">Access your bug tracking system from any device with our responsive design.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- How It Works Section -->
<section class="how-it-works-section py-5 bg-light">
    <div class="container">
        <div class="text-center mb-5 animate-fadeIn">
            <h2 class="fw-bold">How It Works</h2>
            <p class="lead text-muted">Simple and effective bug tracking process</p>
        </div>
        
        <div class="row">
            <div class="col-lg-10 mx-auto">
                <div class="timeline">
                    <div class="timeline-item animate-slideInLeft">
                        <div class="timeline-number bg-primary text-white">1</div>
                        <div class="timeline-content">
                            <h3 class="h5 mb-2">Report a Bug</h3>
                            <p class="text-muted mb-0">Users can easily report bugs with detailed descriptions, screenshots, and environment information.</p>
                        </div>
                    </div>
                    
                    <div class="timeline-item animate-slideInRight">
                        <div class="timeline-number bg-primary text-white">2</div>
                        <div class="timeline-content">
                            <h3 class="h5 mb-2">Assign to Team Member</h3>
                            <p class="text-muted mb-0">Bugs are assigned to the appropriate team members based on expertise and workload.</p>
                        </div>
                    </div>
                    
                    <div class="timeline-item animate-slideInLeft">
                        <div class="timeline-number bg-primary text-white">3</div>
                        <div class="timeline-content">
                            <h3 class="h5 mb-2">Track Progress</h3>
                            <p class="text-muted mb-0">Monitor bug status, updates, and comments throughout the resolution process.</p>
                        </div>
                    </div>
                    
                    <div class="timeline-item animate-slideInRight">
                        <div class="timeline-number bg-primary text-white">4</div>
                        <div class="timeline-content">
                            <h3 class="h5 mb-2">Resolve and Close</h3>
                            <p class="text-muted mb-0">Mark bugs as resolved, verify fixes, and close them when completed.</p>
                        </div>
                    </div>
                    
                    <div class="timeline-item animate-slideInLeft">
                        <div class="timeline-number bg-primary text-white">5</div>
                        <div class="timeline-content">
                            <h3 class="h5 mb-2">Analyze and Improve</h3>
                            <p class="text-muted mb-0">Generate reports to identify patterns and improve your development process.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Testimonials Section -->
<section class="testimonials-section py-5">
    <div class="container">
        <div class="text-center mb-5 animate-fadeIn">
            <h2 class="fw-bold">What Our Users Say</h2>
            <p class="lead text-muted">Trusted by developers and teams worldwide</p>
        </div>
        
        <div class="row">
            <div class="col-lg-4 mb-4 animate-slideInUp">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-body p-4">
                        <div class="d-flex align-items-center mb-3">
                            <img src="assets/images/testimonial-1.jpg" alt="User" class="rounded-circle me-3" width="60" height="60">
                            <div>
                                <h4 class="h5 mb-1">Sarah Johnson</h4>
                                <p class="text-muted mb-0">Lead Developer, TechCorp</p>
                            </div>
                        </div>
                        <div class="mb-3">
                            <i class="fas fa-star text-warning"></i>
                            <i class="fas fa-star text-warning"></i>
                            <i class="fas fa-star text-warning"></i>
                            <i class="fas fa-star text-warning"></i>
                            <i class="fas fa-star text-warning"></i>
                        </div>
                        <p class="mb-0">"This bug tracking system has transformed our development workflow. It's intuitive, feature-rich, and has significantly improved our team's productivity."</p>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-4 mb-4 animate-slideInUp delay-1">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-body p-4">
                        <div class="d-flex align-items-center mb-3">
                            <img src="assets/images/testimonial-2.jpg" alt="User" class="rounded-circle me-3" width="60" height="60">
                            <div>
                                <h4 class="h5 mb-1">Michael Chen</h4>
                                <p class="text-muted mb-0">Project Manager, InnovateSoft</p>
                            </div>
                        </div>
                        <div class="mb-3">
                            <i class="fas fa-star text-warning"></i>
                            <i class="fas fa-star text-warning"></i>
                            <i class="fas fa-star text-warning"></i>
                            <i class="fas fa-star text-warning"></i>
                            <i class="fas fa-star text-warning"></i>
                        </div>
                        <p class="mb-0">"The reporting features are exceptional. I can now track team performance, identify bottlenecks, and make data-driven decisions to improve our processes."</p>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-4 mb-4 animate-slideInUp delay-2">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-body p-4">
                        <div class="d-flex align-items-center mb-3">
                            <img src="assets/images/testimonial-3.jpg" alt="User" class="rounded-circle me-3" width="60" height="60">
                            <div>
                                <h4 class="h5 mb-1">Emily Rodriguez</h4>
                                <p class="text-muted mb-0">QA Specialist, DevSecure</p>
                            </div>
                        </div>
                        <div class="mb-3">
                            <i class="fas fa-star text-warning"></i>
                            <i class="fas fa-star text-warning"></i>
                            <i class="fas fa-star text-warning"></i>
                            <i class="fas fa-star text-warning"></i>
                            <i class="fas fa-star-half-alt text-warning"></i>
                        </div>
                        <p class="mb-0">"As a QA specialist, I love how easy it is to report bugs with detailed information. The screenshot feature and environment tracking are game-changers."</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Pricing Section -->
<section class="pricing-section py-5 bg-light">
    <div class="container">
        <div class="text-center mb-5 animate-fadeIn">
            <h2 class="fw-bold">Simple, Transparent Pricing</h2>
            <p class="lead text-muted">Choose the plan that fits your needs</p>
        </div>
        
        <div class="row g-4">
            <div class="col-lg-4 col-md-6 animate-slideInUp">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-header bg-white text-center border-0 pt-4">
                        <h3 class="h4 mb-0">Free</h3>
                    </div>
                    <div class="card-body text-center">
                        <div class="display-5 fw-bold mb-3">$0</div>
                        <p class="text-muted mb-4">Perfect for individuals and small teams</p>
                        <ul class="list-unstyled mb-4">
                            <li class="mb-2"><i class="fas fa-check text-success me-2"></i> Up to 3 users</li>
                            <li class="mb-2"><i class="fas fa-check text-success me-2"></i> 1 project</li>
                            <li class="mb-2"><i class="fas fa-check text-success me-2"></i> Basic bug tracking</li>
                            <li class="mb-2"><i class="fas fa-check text-success me-2"></i> Email notifications</li>
                            <li class="mb-2 text-muted"><i class="fas fa-times text-danger me-2"></i> Advanced reporting</li>
                            <li class="mb-2 text-muted"><i class="fas fa-times text-danger me-2"></i> API access</li>
                        </ul>
                        <div class="d-grid">
                            <a href="register.php" class="btn btn-outline-primary">Get Started</a>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-4 col-md-6 animate-slideInUp delay-1">
                <div class="card h-100 border-0 shadow-sm border-primary">
                    <div class="card-header bg-primary text-white text-center border-0 pt-4">
                        <h3 class="h4 mb-0">Professional</h3>
                        <span class="badge bg-warning position-absolute top-0 end-0 translate-middle-y me-3">Popular</span>
                    </div>
                    <div class="card-body text-center">
                        <div class="display-5 fw-bold mb-3">$29</div>
                        <p class="text-muted mb-4">Ideal for growing teams and businesses</p>
                        <ul class="list-unstyled mb-4">
                            <li class="mb-2"><i class="fas fa-check text-success me-2"></i> Up to 15 users</li>
                            <li class="mb-2"><i class="fas fa-check text-success me-2"></i> Unlimited projects</li>
                            <li class="mb-2"><i class="fas fa-check text-success me-2"></i> Advanced bug tracking</li>
                            <li class="mb-2"><i class="fas fa-check text-success me-2"></i> Email & in-app notifications</li>
                            <li class="mb-2"><i class="fas fa-check text-success me-2"></i> Basic reporting</li>
                            <li class="mb-2 text-muted"><i class="fas fa-times text-danger me-2"></i> API access</li>
                        </ul>
                        <div class="d-grid">
                            <a href="register.php?plan=professional" class="btn btn-primary">Choose Plan</a>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-4 col-md-6 mx-auto animate-slideInUp delay-2">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-header bg-white text-center border-0 pt-4">
                        <h3 class="h4 mb-0">Enterprise</h3>
                    </div>
                    <div class="card-body text-center">
                        <div class="display-5 fw-bold mb-3">$99</div>
                        <p class="text-muted mb-4">For large teams and organizations</p>
                        <ul class="list-unstyled mb-4">
                            <li class="mb-2"><i class="fas fa-check text-success me-2"></i> Unlimited users</li>
                            <li class="mb-2"><i class="fas fa-check text-success me-2"></i> Unlimited projects</li>
                            <li class="mb-2"><i class="fas fa-check text-success me-2"></i> Advanced bug tracking</li>
                            <li class="mb-2"><i class="fas fa-check text-success me-2"></i> All notification channels</li>
                            <li class="mb-2"><i class="fas fa-check text-success me-2"></i> Advanced reporting & analytics</li>
                            <li class="mb-2"><i class="fas fa-check text-success me-2"></i> API access</li>
                        </ul>
                        <div class="d-grid">
                            <a href="register.php?plan=enterprise" class="btn btn-outline-primary">Choose Plan</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- CTA Section -->
<section class="cta-section py-5">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-8 mx-auto text-center animate-fadeIn">
                <h2 class="fw-bold mb-4">Ready to Streamline Your Bug Tracking?</h2>
                <p class="lead mb-4">Join thousands of developers and teams who trust our platform for efficient bug management.</p>
                <div class="d-flex flex-wrap justify-content-center gap-3">
                    <a href="register.php" class="btn btn-primary btn-lg">
                        <i class="fas fa-user-plus me-2"></i>Sign Up Free
                    </a>
                    <a href="contact.php" class="btn btn-outline-primary btn-lg">
                        <i class="fas fa-headset me-2"></i>Contact Sales
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>

<?php
// Include footer
include 'includes/footer-home.php';
?>
