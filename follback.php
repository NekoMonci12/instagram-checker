<?php
// Set the content type to text/html
header('Content-Type: text/html; charset=utf-8');

/**
 * FOLLBACK CHECKER - FIXED VERSION
 * Fixes:
 * 1. Proper JSON validation
 * 2. File upload security
 * 3. Complete error handling
 * 4. More reliable username parsing
 * 5. Data structure validation
 */

// Function to validate uploaded file
function validateUploadedFile($fileInputName, $maxSizeInMB = 10) {
    // Check if the file exists in $_FILES
    if (!isset($_FILES[$fileInputName])) {
        return ['error' => "File not found"];
    }

    $file = $_FILES[$fileInputName];

    // Check upload error
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $errorMessages = [
            UPLOAD_ERR_INI_SIZE => "File exceeds upload_max_filesize",
            UPLOAD_ERR_FORM_SIZE => "File exceeds max_file_size",
            UPLOAD_ERR_PARTIAL => "File was only partially uploaded",
            UPLOAD_ERR_NO_FILE => "No file was uploaded",
            UPLOAD_ERR_NO_TMP_DIR => "Missing temporary folder",
            UPLOAD_ERR_CANT_WRITE => "Failed to write file to disk",
            UPLOAD_ERR_EXTENSION => "Upload stopped by extension"
        ];
        return ['error' => $errorMessages[$file['error']] ?? "Unknown upload error"];
    }

    // Check file extension
    $fileName = $file['name'];
    if (!preg_match('/\.json$/i', $fileName)) {
        return ['error' => "File must be in .json format (You uploaded: $fileName)"];
    }

    // Check file size
    $fileSizeInMB = $file['size'] / (1024 * 1024);
    if ($fileSizeInMB > $maxSizeInMB) {
        return ['error' => "File is too large ({$fileSizeInMB}MB, max: {$maxSizeInMB}MB)"];
    }

    // Check if file actually exists in temp folder
    if (!file_exists($file['tmp_name'])) {
        return ['error' => "Temporary file is missing"];
    }

    // Validate JSON
    $content = @file_get_contents($file['tmp_name']);
    if ($content === false) {
        return ['error' => "Cannot read file"];
    }

    $decodedData = json_decode($content, true);
    if ($decodedData === null && json_last_error() !== JSON_ERROR_NONE) {
        return ['error' => "File is not valid JSON: " . json_last_error_msg()];
    }

    return ['success' => true, 'path' => $file['tmp_name'], 'data' => $decodedData];
}

// Function to extract usernames from followers
function extractFollowers($followersData) {
    $followers = [];
    $errors = [];

    if (!is_array($followersData)) {
        return ['followers' => [], 'errors' => ["Followers data is not an array"]];
    }

    foreach ($followersData as $index => $item) {
        // Validate structure
        if (!isset($item["string_list_data"]) || !is_array($item["string_list_data"])) {
            continue;
        }

        foreach ($item["string_list_data"] as $userItem) {
            if (isset($userItem["value"]) && !empty($userItem["value"])) {
                $followers[] = strtolower($userItem["value"]);
            }
        }
    }

    return [
        'followers' => array_unique($followers),
        'errors' => $errors,
        'count' => count(array_unique($followers))
    ];
}

// Function to extract usernames from following
function extractFollowing($followingData) {
    $following = [];
    $errors = [];

    // Validate structure
    if (!isset($followingData["relationships_following"]) || !is_array($followingData["relationships_following"])) {
        return ['following' => [], 'errors' => ["Structure of following.json is incorrect"]];
    }

    foreach ($followingData["relationships_following"] as $item) {
        // Check if title exists (more reliable)
        if (isset($item["title"]) && !empty($item["title"])) {
            $following[] = strtolower($item["title"]);
        }
        // Fallback: parse from href if title is not present
        elseif (isset($item["string_list_data"]) && is_array($item["string_list_data"])) {
            foreach ($item["string_list_data"] as $userItem) {
                if (isset($userItem["href"])) {
                    // Extract username from URL
                    // Format: https://www.instagram.com/_u/username
                    if (preg_match('/_u\/([^?\/]+)/', $userItem["href"], $matches)) {
                        $following[] = strtolower($matches[1]);
                    }
                }
            }
        }
    }

    return [
        'following' => array_unique($following),
        'errors' => $errors,
        'count' => count(array_unique($following))
    ];
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Follback | Checker Result</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f9f9f9;
            margin: 0;
            padding: 20px;
        }

        h1 {
            text-align: center;
            color: #333;
        }

        h2 {
            text-align: center;
            color: #333;
            margin: 25px 0 15px 0;
            border-bottom: 2px solid #007bff;
            padding-bottom: 10px;
        }

        .container {
            max-width: 600px;
            margin: 0 auto;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            padding: 20px;
        }

        .header {
            text-align: center;
            margin-bottom: 20px;
        }

        .header h1 {
            font-size: 28px;
            margin-bottom: 5px;
        }

        .header p {
            color: #666;
            font-size: 14px;
        }

        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 4px solid;
        }

        .alert.error {
            background-color: #f8d7da;
            border-left-color: #dc3545;
            color: #721c24;
        }

        .alert.success {
            background-color: #d4edda;
            border-left-color: #28a745;
            color: #155724;
        }

        .alert.warning {
            background-color: #fff3cd;
            border-left-color: #ffc107;
            color: #856404;
        }

        .stats {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 15px;
            margin-bottom: 30px;
        }

        .stat-box {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            text-align: center;
            border-top: 3px solid;
        }

        .stat-box.followers {
            border-top-color: #007bff;
        }

        .stat-box.following {
            border-top-color: #555;
        }

        .stat-box.notfollback {
            border-top-color: #ffc107;
        }

        .stat-box h3 {
            font-size: 32px;
            color: #333;
            margin-bottom: 5px;
        }

        .stat-box p {
            font-size: 12px;
            color: #666;
            text-transform: uppercase;
        }

        ul {
            list-style: none;
            column-count: 2;
            column-gap: 20px;
            margin-bottom: 20px;
            padding: 0;
        }

        @media (max-width: 600px) {
            ul {
                column-count: 1;
            }
        }

        li {
            background: #f8f9fa;
            padding: 8px 12px;
            margin-bottom: 8px;
            border-radius: 6px;
            break-inside: avoid;
        }

        a {
            color: #007bff;
            text-decoration: none;
        }

        a:hover {
            text-decoration: underline;
        }

        .footer {
            text-align: center;
            margin-top: 20px;
            color: #555;
        }

        .empty {
            text-align: center;
            padding: 40px 20px;
            color: #999;
        }

        .empty p {
            font-size: 18px;
            margin-bottom: 10px;
        }

        .emoji {
            font-size: 40px;
        }
    </style>
</head>
<body>

<div class="container">
    <div class="header">
        <h1>Follback Checker</h1>
        <p>Analyze your Instagram follow-unfollow data</p>
    </div>

    <div class="content">
        <?php
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            // Validate followers file
            $followersValidation = validateUploadedFile('followers');
            if (isset($followersValidation['error'])) {
                echo '<div class="alert error">Error Followers: ' . htmlspecialchars($followersValidation['error']) . '</div>';
                echo '<div class="footer"><a href="follback.html">Back to Upload</a></div>';
            }
            // Validate following file
            elseif (!isset($_FILES['following'])) {
                echo '<div class="alert error">File following not found</div>';
                echo '<div class="footer"><a href="follback.html">Back to Upload</a></div>';
            }
            else {
                $followingValidation = validateUploadedFile('following');
                if (isset($followingValidation['error'])) {
                    echo '<div class="alert error">Error Following: ' . htmlspecialchars($followingValidation['error']) . '</div>';
                    echo '<div class="footer"><a href="follback.html">Back to Upload</a></div>';
                }
                else {
                    // Extract data
                    $followersResult = extractFollowers($followersValidation['data']);
                    $followingResult = extractFollowing($followingValidation['data']);

                    $followers = $followersResult['followers'];
                    $following = $followingResult['following'];

                    // Calculate not follow back
                    $notFollowBack = array_diff($following, $followers);
                    $notFollowBackCount = count($notFollowBack);

                    // Calculate mutual
                    $mutual = count(array_intersect($following, $followers));

                    // Display statistics
                    echo '<div class="stats">';
                    echo '<div class="stat-box followers">';
                    echo '<h3>' . count($followers) . '</h3>';
                    echo '<p>Followers</p>';
                    echo '</div>';
                    echo '<div class="stat-box following">';
                    echo '<h3>' . count($following) . '</h3>';
                    echo '<p>Following</p>';
                    echo '</div>';
                    echo '<div class="stat-box notfollback">';
                    echo '<h3>' . $notFollowBackCount . '</h3>';
                    echo '<p>Not Following Back</p>';
                    echo '</div>';
                    echo '</div>';

                    // Display results
                    echo '<h2>Users Who Do Not Follow Back (' . $notFollowBackCount . ')</h2>';

                    if ($notFollowBackCount > 0) {
                        sort($notFollowBack);
                        echo '<ul>';
                        foreach ($notFollowBack as $user) {
                            echo '<li><a href="https://www.instagram.com/' . htmlspecialchars($user) . '" target="_blank">' . htmlspecialchars($user) . '</a></li>';
                        }
                        echo '</ul>';

                        echo '<div class="alert warning">';
                        echo '<strong>Tip:</strong> You can unfollow the accounts above if you\'d like. Use the Instagram app to mass unfollow.';
                        echo '</div>';
                    } else {
                        echo '<div class="empty">';
                        echo '<p>Everyone you follow follows you back!</p>';
                        echo '</div>';
                    }

                    // Additional info
                    echo '<h2>Additional Information</h2>';
                    echo '<ul style="column-count: 1;">';
                    echo '<li><strong>Mutual Follow:</strong> ' . $mutual . ' users</li>';
                    echo '<li><strong>Followers You Do Not Follow Back:</strong> ' . count(array_diff($followers, $following)) . ' users</li>';
                    echo '<li><strong>Data Accuracy:</strong> Based on the latest export from Instagram</li>';
                    echo '</ul>';
                }
            }
        } else {
            echo '<div class="alert warning">';
            echo 'Invalid request or incomplete data.';
            echo '</div>';
        }
        ?>

        <div class="footer">
            <a href="follback.html">Back to Upload</a>
        </div>
    </div>
</div>

</body>
</html>
