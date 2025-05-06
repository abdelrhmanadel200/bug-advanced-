<?php
/**
 * GitHub Integration Helper Functions
 */

// GitHub API URL
define('GITHUB_API_URL', 'https://api.github.com');

/**
 * Initialize GitHub integration
 * 
 * @param string $client_id GitHub OAuth client ID
 * @param string $client_secret GitHub OAuth client secret
 * @param string $redirect_uri Redirect URI after GitHub authentication
 * @return array Configuration array
 */
function initGitHub($client_id, $client_secret, $redirect_uri) {
    return [
        'client_id' => $client_id,
        'client_secret' => $client_secret,
        'redirect_uri' => $redirect_uri
    ];
}

/**
 * Get GitHub authorization URL
 * 
 * @param array $config GitHub configuration
 * @param array $scopes GitHub API scopes
 * @return string Authorization URL
 */
function getGitHubAuthUrl($config, $scopes = ['repo']) {
    $scope = implode(' ', $scopes);
    return "https://github.com/login/oauth/authorize?client_id={$config['client_id']}&redirect_uri={$config['redirect_uri']}&scope={$scope}";
}

/**
 * Get GitHub access token
 * 
 * @param array $config GitHub configuration
 * @param string $code Authorization code from GitHub
 * @return string|false Access token or false on failure
 */
function getGitHubAccessToken($config, $code) {
    $url = "https://github.com/login/oauth/access_token";
    $data = [
        'client_id' => $config['client_id'],
        'client_secret' => $config['client_secret'],
        'code' => $code
    ];
    
    $options = [
        'http' => [
            'header' => "Content-type: application/x-www-form-urlencoded\r\n",
            'method' => 'POST',
            'content' => http_build_query($data)
        ]
    ];
    
    $context = stream_context_create($options);
    $result = file_get_contents($url, false, $context);
    
    if ($result === FALSE) {
        return false;
    }
    
    parse_str($result, $response);
    
    if (isset($response['access_token'])) {
        return $response['access_token'];
    }
    
    return false;
}

/**
 * Make GitHub API request
 * 
 * @param string $endpoint API endpoint
 * @param string $access_token GitHub access token
 * @param string $method HTTP method (GET, POST, etc.)
 * @param array $data Request data
 * @return array|false Response data or false on failure
 */
function gitHubApiRequest($endpoint, $access_token, $method = 'GET', $data = null) {
    $url = GITHUB_API_URL . $endpoint;
    
    $options = [
        'http' => [
            'header' => "Authorization: token {$access_token}\r\n" .
                        "User-Agent: Bug-Tracking-System\r\n" .
                        "Accept: application/vnd.github.v3+json\r\n",
            'method' => $method
        ]
    ];
    
    if ($data !== null && ($method === 'POST' || $method === 'PATCH')) {
        $options['http']['header'] .= "Content-Type: application/json\r\n";
        $options['http']['content'] = json_encode($data);
    }
    
    $context = stream_context_create($options);
    $result = file_get_contents($url, false, $context);
    
    if ($result === FALSE) {
        return false;
    }
    
    return json_decode($result, true);
}

/**
 * Get GitHub user information
 * 
 * @param string $access_token GitHub access token
 * @return array|false User data or false on failure
 */
function getGitHubUser($access_token) {
    return gitHubApiRequest('/user', $access_token);
}

/**
 * Get GitHub repositories for the authenticated user
 * 
 * @param string $access_token GitHub access token
 * @return array|false Repositories or false on failure
 */
function getGitHubRepositories($access_token) {
    return gitHubApiRequest('/user/repos', $access_token);
}

/**
 * Create GitHub issue from bug
 * 
 * @param string $access_token GitHub access token
 * @param string $repo_owner Repository owner
 * @param string $repo_name Repository name
 * @param array $bug Bug data
 * @return array|false Issue data or false on failure
 */
function createGitHubIssue($access_token, $repo_owner, $repo_name, $bug) {
    $endpoint = "/repos/{$repo_owner}/{$repo_name}/issues";
    
    $data = [
        'title' => $bug['title'],
        'body' => "## Bug Report\n\n" .
                  "**Ticket Number:** {$bug['ticket_number']}\n\n" .
                  "**Severity:** {$bug['severity']}\n\n" .
                  "**Description:**\n{$bug['description']}\n\n" .
                  "**Reported By:** {$bug['reporter_name']}\n\n" .
                  "**Created At:** {$bug['created_at']}",
        'labels' => ['bug', $bug['severity']]
    ];
    
    return gitHubApiRequest($endpoint, $access_token, 'POST', $data);
}

/**
 * Update GitHub issue from bug
 * 
 * @param string $access_token GitHub access token
 * @param string $repo_owner Repository owner
 * @param string $repo_name Repository name
 * @param int $issue_number Issue number
 * @param array $bug Bug data
 * @return array|false Issue data or false on failure
 */
function updateGitHubIssue($access_token, $repo_owner, $repo_name, $issue_number, $bug) {
    $endpoint = "/repos/{$repo_owner}/{$repo_name}/issues/{$issue_number}";
    
    $data = [
        'title' => $bug['title'],
        'body' => "## Bug Report\n\n" .
                  "**Ticket Number:** {$bug['ticket_number']}\n\n" .
                  "**Severity:** {$bug['severity']}\n\n" .
                  "**Description:**\n{$bug['description']}\n\n" .
                  "**Reported By:** {$bug['reporter_name']}\n\n" .
                  "**Created At:** {$bug['created_at']}\n\n" .
                  "**Updated At:** {$bug['updated_at']}",
        'state' => ($bug['status'] === 'closed' || $bug['status'] === 'resolved') ? 'closed' : 'open'
    ];
    
    return gitHubApiRequest($endpoint, $access_token, 'PATCH', $data);
}

/**
 * Add comment to GitHub issue
 * 
 * @param string $access_token GitHub access token
 * @param string $repo_owner Repository owner
 * @param string $repo_name Repository name
 * @param int $issue_number Issue number
 * @param string $comment Comment text
 * @return array|false Comment data or false on failure
 */
function addGitHubIssueComment($access_token, $repo_owner, $repo_name, $issue_number, $comment) {
    $endpoint = "/repos/{$repo_owner}/{$repo_name}/issues/{$issue_number}/comments";
    
    $data = [
        'body' => $comment
    ];
    
    return gitHubApiRequest($endpoint, $access_token, 'POST', $data);
}

/**
 * Link bug to GitHub issue
 * 
 * @param PDO $db Database connection
 * @param int $bug_id Bug ID
 * @param string $repo_owner Repository owner
 * @param string $repo_name Repository name
 * @param int $issue_number Issue number
 * @return bool Success status
 */
function linkBugToGitHubIssue($db, $bug_id, $repo_owner, $repo_name, $issue_number) {
    // Check if github_issues table exists, create if not
    $query = "CREATE TABLE IF NOT EXISTS github_issues (
                id INT AUTO_INCREMENT PRIMARY KEY,
                bug_id INT NOT NULL,
                repo_owner VARCHAR(100) NOT NULL,
                repo_name VARCHAR(100) NOT NULL,
                issue_number INT NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (bug_id) REFERENCES bugs(id) ON DELETE CASCADE
              )";
    $db->exec($query);
    
    // Check if link already exists
    $query = "SELECT id FROM github_issues WHERE bug_id = :bug_id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':bug_id', $bug_id);
    $stmt->execute();
    
    if ($stmt->rowCount() > 0) {
        // Update existing link
        $query = "UPDATE github_issues 
                  SET repo_owner = :repo_owner, repo_name = :repo_name, issue_number = :issue_number 
                  WHERE bug_id = :bug_id";
    } else {
        // Create new link
        $query = "INSERT INTO github_issues (bug_id, repo_owner, repo_name, issue_number) 
                  VALUES (:bug_id, :repo_owner, :repo_name, :issue_number)";
    }
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(':bug_id', $bug_id);
    $stmt->bindParam(':repo_owner', $repo_owner);
    $stmt->bindParam(':repo_name', $repo_name);
    $stmt->bindParam(':issue_number', $issue_number);
    
    return $stmt->execute();
}

/**
 * Get GitHub issue linked to bug
 * 
 * @param PDO $db Database connection
 * @param int $bug_id Bug ID
 * @return array|false Issue data or false if not found
 */
function getGitHubIssueForBug($db, $bug_id) {
    $query = "SELECT * FROM github_issues WHERE bug_id = :bug_id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':bug_id', $bug_id);
    $stmt->execute();
    
    if ($stmt->rowCount() > 0) {
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    return false;
}