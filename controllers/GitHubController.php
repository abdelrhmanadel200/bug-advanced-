?php
require_once 'models/BugTrackingSystem.php';
require_once 'models/Bug.php';

class GitHubController {
    private $system;
    private $client_id;
    private $client_secret;
    private $redirect_uri;
    
    public function __construct() {
        $this->system = BugTrackingSystem::getInstance();
        $this->client_id = 'YOUR_GITHUB_CLIENT_ID';
        $this->client_secret = 'YOUR_GITHUB_CLIENT_SECRET';
        $this->redirect_uri = 'http://' . $_SERVER['HTTP_HOST'] . '/index.php?controller=github&action=callback';
    }
    
    public function connect() {
        // Check if user is logged in
        if (!isset($_SESSION['user_id'])) {
            header('Location: index.php?controller=auth&action=login');
            exit;
        }
        
        // Check if user has permission to connect to GitHub
        if ($_SESSION['user_role'] !== 'administrator' && $_SESSION['user_role'] !== 'staff') {
            $_SESSION['errors'] = ['You do not have permission to connect to GitHub'];
            header('Location: index.php?controller=dashboard&action=index');
            exit;
        }
        
        // Redirect to GitHub for authorization
        $url = 'https://github.com/login/oauth/authorize';
        $params = [
            'client_id' => $this->client_id,
            'redirect_uri' => $this->redirect_uri,
            'scope' => 'repo',
            'state' => bin2hex(random_bytes(16))
        ];
        
        // Store state in session to prevent CSRF
        $_SESSION['github_state'] = $params['state'];
        
        header('Location: ' . $url . '?' . http_build_query($params));
        exit;
    }
    
    public function callback() {
        // Check if user is logged in
        if (!isset($_SESSION['user_id'])) {
            header('Location: index.php?controller=auth&action=login');
            exit;
        }
        
        // Check if user has permission to connect to GitHub
        if ($_SESSION['user_role'] !== 'administrator' && $_SESSION['user_role'] !== 'staff') {
            $_SESSION['errors'] = ['You  !== 'administrator' && $_SESSION['user_role'] !== 'staff') {
            $_SESSION['errors'] = ['You do not have permission to connect to GitHub'];
            header('Location: index.php?controller=dashboard&action=index');
            exit;
        }
        
        // Verify state to prevent CSRF
        $state = $_GET['state'] ?? '';
        if (empty($state) || $state !== $_SESSION['github_state']) {
            $_SESSION['errors'] = ['Invalid state parameter'];
            header('Location: index.php?controller=dashboard&action=index');
            exit;
        }
        
        // Get code from GitHub
        $code = $_GET['code'] ?? '';
        if (empty($code)) {
            $_SESSION['errors'] = ['Authorization code not received'];
            header('Location: index.php?controller=dashboard&action=index');
            exit;
        }
        
        // Exchange code for access token
        $url = 'https://github.com/login/oauth/access_token';
        $params = [
            'client_id' => $this->client_id,
            'client_secret' => $this->client_secret,
            'code' => $code,
            'redirect_uri' => $this->redirect_uri
        ];
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Accept: application/json']);
        
        $response = curl_exec($ch);
        curl_close($ch);
        
        $data = json_decode($response, true);
        
        if (isset($data['error'])) {
            $_SESSION['errors'] = ['GitHub authentication failed: ' . $data['error_description']];
            header('Location: index.php?controller=dashboard&action=index');
            exit;
        }
        
        $access_token = $data['access_token'] ?? '';
        
        if (empty($access_token)) {
            $_SESSION['errors'] = ['Failed to get access token'];
            header('Location: index.php?controller=dashboard&action=index');
            exit;
        }
        
        // Get user info from GitHub
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://api.github.com/user');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: token ' . $access_token,
            'User-Agent: Bug Tracking System'
        ]);
        
        $response = curl_exec($ch);
        curl_close($ch);
        
        $user_data = json_decode($response, true);
        
        if (!isset($user_data['login'])) {
            $_SESSION['errors'] = ['Failed to get GitHub user info'];
            header('Location: index.php?controller=dashboard&action=index');
            exit;
        }
        
        // Store GitHub info in session
        $_SESSION['github_token'] = $access_token;
        $_SESSION['github_username'] = $user_data['login'];
        
        // Log activity
        $this->system->logActivity($_SESSION['user_id'], 'github_connect', "Connected to GitHub as {$user_data['login']}");
        
        $_SESSION['success'] = 'Successfully connected to GitHub';
        header('Location: index.php?controller=user&action=profile');
        exit;
    }
    
    public function repositories() {
        // Check if user is logged in
        if (!isset($_SESSION['user_id'])) {
            header('Location: index.php?controller=auth&action=login');
            exit;
        }
        
        // Check if user has permission to view repositories
        if ($_SESSION['user_role'] !== 'administrator' && $_SESSION['user_role'] !== 'staff') {
            $_SESSION['errors'] = ['You do not have permission to view repositories'];
            header('Location: index.php?controller=dashboard&action=index');
            exit;
        }
        
        // Check if user is connected to GitHub
        if (!isset($_SESSION['github_token'])) {
            $_SESSION['errors'] = ['You need to connect to GitHub first'];
            header('Location: index.php?controller=github&action=connect');
            exit;
        }
        
        // Get repositories from GitHub
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://api.github.com/user/repos?sort=updated');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: token ' . $_SESSION['github_token'],
            'User-Agent: Bug Tracking System'
        ]);
        
        $response = curl_exec($ch);
        curl_close($ch);
        
        $repositories = json_decode($response, true);
        
        if (!is_array($repositories)) {
            $_SESSION['errors'] = ['Failed to get repositories'];
            header('Location: index.php?controller=dashboard&action=index');
            exit;
        }
        
        // Display repositories
        include 'views/github/repositories.php';
    }
    
    public function createIssue() {
        // Check if user is logged in
        if (!isset($_SESSION['user_id'])) {
            header('Location: index.php?controller=auth&action=login');
            exit;
        }
        
        // Check if user has permission to create issues
        if ($_SESSION['user_role'] !== 'administrator' && $_SESSION['user_role'] !== 'staff') {
            $_SESSION['errors'] = ['You do not have permission to create issues'];
            header('Location: index.php?controller=dashboard&action=index');
            exit;
        }
        
        // Check if user is connected to GitHub
        if (!isset($_SESSION['github_token'])) {
            $_SESSION['errors'] = ['You need to connect to GitHub first'];
            header('Location: index.php?controller=github&action=connect');
            exit;
        }
        
        $bug_id = $_GET['bug_id'] ?? 0;
        
        if (!$bug_id) {
            $_SESSION['errors'] = ['Bug not found'];
            header('Location: index.php?controller=bug&action=list');
            exit;
        }
        
        $bug = new Bug($bug_id);
        $bugDetails = $bug->getDetails();
        
        if (!$bugDetails) {
            $_SESSION['errors'] = ['Bug not found'];
            header('Location: index.php?controller=bug&action=list');
            exit;
        }
        
        // Check if bug already has a GitHub issue
        global $db;
        $stmt = $db->prepare("SELECT * FROM github_issues WHERE bug_id = ?");
        $stmt->execute([$bug_id]);
        $github_issue = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($github_issue) {
            $_SESSION['errors'] = ['Bug already has a GitHub issue'];
            header('Location: index.php?controller=bug&action=view&id=' . $bug_id);
            exit;
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $repo_owner = $_POST['repo_owner'] ?? '';
            $repo_name = $_POST['repo_name'] ?? '';
            
            $errors = [];
            
            if (empty($repo_owner)) {
                $errors[] = 'Repository owner is required';
            }
            
            if (empty($repo_name)) {
                $errors[] = 'Repository name is required';
            }
            
            if (empty($errors)) {
                // Create issue on GitHub
                $issue_data = [
                    'title' => $bugDetails['title'],
                    'body' => "## Bug Report\n\n" .
                             "**Description:** " . $bugDetails['description'] . "\n\n" .
                             "**Severity:** " . ucfirst($bugDetails['severity']) . "\n" .
                             "**Priority:** " . ucfirst($bugDetails['priority']) . "\n\n" .
                             "**Steps to Reproduce:** " . ($bugDetails['steps'] ?? 'N/A') . "\n\n" .
                             "**Expected Result:** " . ($bugDetails['expected_result'] ?? 'N/A') . "\n\n" .
                             "**Actual Result:** " . ($bugDetails['actual_result'] ?? 'N/A') . "\n\n" .
                             "**Ticket Number:** " . $bugDetails['ticket_number'] . "\n\n" .
                             "This issue was created automatically from the Bug Tracking System."
                ];
                
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, "https://api.github.com/repos/{$repo_owner}/{$repo_name}/issues");
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($issue_data));
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_HTTPHEADER, [
                    'Authorization: token ' . $_SESSION['github_token'],
                    'User-Agent: Bug Tracking System',
                    'Content-Type: application/json'
                ]);
                
                $response = curl_exec($ch);
                $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);
                
                $issue = json_decode($response, true);
                
                if ($http_code !== 201 || !isset($issue['number'])) {
                    $error_message = isset($issue['message']) ? $issue['message'] : 'Unknown error';
                    $_SESSION['errors'] = ['Failed to create GitHub issue: ' . $error_message];
                    header('Location: index.php?controller=bug&action=view&id=' . $bug_id);
                    exit;
                }
                
                // Save GitHub issue info
                $stmt = $db->prepare("INSERT INTO github_issues (bug_id, repo_owner, repo_name, issue_number, created_at) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$bug_id, $repo_owner, $repo_name, $issue['number'], date('Y-m-d H:i:s')]);
                
                // Log activity
                $this->system->logActivity($_SESSION['user_id'], 'create_github_issue', "Created GitHub issue for bug #{$bugDetails['ticket_number']}");
                
                $_SESSION['success'] = 'GitHub issue created successfully';
                header('Location: index.php?controller=bug&action=view&id=' . $bug_id);
                exit;
            }
            
            $_SESSION['errors'] = $errors;
        }
        
        // Get repositories from GitHub
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://api.github.com/user/repos?sort=updated');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: token ' . $_SESSION['github_token'],
            'User-Agent: Bug Tracking System'
        ]);
        
        $response = curl_exec($ch);
        curl_close($ch);
        
        $repositories = json_decode($response, true);
        
        if (!is_array($repositories)) {
            $_SESSION['errors'] = ['Failed to get repositories'];
            header('Location: index.php?controller=bug&action=view&id=' . $bug_id);
            exit;
        }
        
        // Display create issue form
        include 'views/github/create-issue.php';
    }
    
    public function viewIssue() {
        // Check if user is logged in
        if (!isset($_SESSION['user_id'])) {
            header('Location: index.php?controller=auth&action=login');
            exit;
        }
        
        // Check if user is connected to GitHub
        if (!isset($_SESSION['github_token'])) {
            $_SESSION['errors'] = ['You need to connect to GitHub first'];
            header('Location: index.php?controller=github&action=connect');
            exit;
        }
        
        $bug_id = $_GET['bug_id'] ?? 0;
        
        if (!$bug_id) {
            $_SESSION['errors'] = ['Bug not found'];
            header('Location: index.php?controller=bug&action=list');
            exit;
        }
        
        // Get GitHub issue info
        global $db;
        $stmt = $db->prepare("SELECT * FROM github_issues WHERE bug_id = ?");
        $stmt->execute([$bug_id]);
        $github_issue = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$github_issue) {
            $_SESSION['errors'] = ['GitHub issue not found'];
            header('Location: index.php?controller=bug&action=view&id=' . $bug_id);
            exit;
        }
        
        // Get issue from GitHub
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://api.github.com/repos/{$github_issue['repo_owner']}/{$github_issue['repo_name']}/issues/{$github_issue['issue_number']}");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: token ' . $_SESSION['github_token'],
            'User-Agent: Bug Tracking System'
        ]);
        
        $response = curl_exec($ch);
        curl_close($ch);
        
        $issue = json_decode($response, true);
        
        if (!isset($issue['number'])) {
            $_SESSION['errors'] = ['Failed to get GitHub issue'];
            header('Location: index.php?controller=bug&action=view&id=' . $bug_id);
            exit;
        }
        
        // Get bug details
        $bug = new Bug($bug_id);
        $bugDetails = $bug->getDetails();
        
        // Display issue details
        include 'views/github/view-issue.php';
    }
    
    public function syncIssue() {
        // Check if user is logged in
        if (!isset($_SESSION['user_id'])) {
            header('Location: index.php?controller=auth&action=login');
            exit;
        }
        
        // Check if user has permission to sync issues
        if ($_SESSION['user_role'] !== 'administrator' && $_SESSION['user_role'] !== 'staff') {
            $_SESSION['errors'] = ['You do not have permission to sync issues'];
            header('Location: index.php?controller=dashboard&action=index');
            exit;
        }
        
        // Check if user is connected to GitHub
        if (!isset($_SESSION['github_token'])) {
            $_SESSION['errors'] = ['You need to connect to GitHub first'];
            header('Location: index.php?controller=github&action=connect');
            exit;
        }
        
        $bug_id = $_GET['bug_id'] ?? 0;
        
        if (!$bug_id) {
            $_SESSION['errors'] = ['Bug not found'];
            header('Location: index.php?controller=bug&action=list');
            exit;
        }
        
        // Get GitHub issue info
        global $db;
        $stmt = $db->prepare("SELECT * FROM github_issues WHERE bug_id = ?");
        $stmt->execute([$bug_id]);
        $github_issue = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$github_issue) {
            $_SESSION['errors'] = ['GitHub issue not found'];
            header('Location: index.php?controller=bug&action=view&id=' . $bug_id);
            exit;
        }
        
        // Get issue from GitHub
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://api.github.com/repos/{$github_issue['repo_owner']}/{$github_issue['repo_name']}/issues/{$github_issue['issue_number']}");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: token ' . $_SESSION['github_token'],
            'User-Agent: Bug Tracking System'
        ]);
        
        $response = curl_exec($ch);
        curl_close($ch);
        
        $issue = json_decode($response, true);
        
        if (!isset($issue['number'])) {
            $_SESSION['errors'] = ['Failed to get GitHub issue'];
            header('Location: index.php?controller=bug&action=view&id=' . $bug_id);
            exit;
        }
        
        // Update bug status based on GitHub issue state
        $bug = new Bug($bug_id);
        
        if ($issue['state'] === 'closed') {
            $bug->setStatus('resolved');
        } elseif ($issue['state'] === 'open') {
            $bug->setStatus('in-progress');
        }
        
        // Add comment with GitHub issue update
        $bug->addComment($_SESSION['user_id'], "Synced with GitHub issue: {$issue['html_url']}\nIssue state: {$issue['state']}\nLast updated: {$issue['updated_at']}");
        
        // Log activity
        $this->system->logActivity($_SESSION['user_id'], 'sync_github_issue', "Synced GitHub issue for bug #{$bug->getTicketNumber()}");
        
        $_SESSION['success'] = 'GitHub issue synced successfully';
        header('Location: index.php?controller=bug&action=view&id=' . $bug_id);
        exit;
    }
}
