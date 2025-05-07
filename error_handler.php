<?php
// Custom error handler
function customErrorHandler($errno, $errstr, $errfile, $errline) {
    // Log the error
    error_log("Error [$errno] $errstr in $errfile on line $errline");
    
    // Check if the error is related to missing files
    if (strpos($errstr, 'Failed to open stream') !== false && strpos($errstr, 'No such file or directory') !== false) {
        // Extract the file path from the error message
        preg_match('/\'([^\']+)\'/', $errstr, $matches);
        $missingFile = $matches[1] ?? '';
        
        if (!empty($missingFile)) {
            // Create directory if it doesn't exist
            $directory = dirname($missingFile);
            if (!file_exists($directory) && !is_dir($directory)) {
                mkdir($directory, 0777, true);
            }
            
            // Create a basic template file
            $fileExtension = pathinfo($missingFile, PATHINFO_EXTENSION);
            $content = '';
            
            if ($fileExtension === 'php') {
                $content = "<?php\n// Auto-generated file\n// This file was automatically created by the error handler\n?>\n";
                $content .= "<div class=\"alert alert-warning\">\n";
                $content .= "    <h4>Template Not Found</h4>\n";
                $content .= "    <p>The requested template file was not found and has been automatically generated.</p>\n";
                $content .= "    <p>Please create the proper content for this file: <strong>" . htmlspecialchars($missingFile) . "</strong></p>\n";
                $content .= "</div>\n";
            }
            
            // Write the content to the file
            file_put_contents($missingFile, $content);
            
            // Log the action
            error_log("Created missing file: $missingFile");
        }
    }
    
    // Don't execute PHP's internal error handler
    return true;
}

// Set the custom error handler
set_error_handler("customErrorHandler");

// Function to check and create missing view files
function checkAndCreateMissingViewFiles() {
    $viewDirectories = [
        'views/projects',
        'views/bugs',
        'views/users',
        'views/auth',
        'views/layouts',
        'views/components',
        'views/notifications'
    ];
    
    // Create directories if they don't exist
    foreach ($viewDirectories as $dir) {
        if (!file_exists($dir) && !is_dir($dir)) {
            mkdir($dir, 0777, true);
            error_log("Created missing directory: $dir");
        }
    }
}

// Run the check
checkAndCreateMissingViewFiles();
