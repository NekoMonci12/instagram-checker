<?php
// Set the content type to text/html
header('Content-Type: text/html; charset=utf-8');

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
        }

        .container {
            max-width: 600px;
            margin: 0 auto;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            padding: 20px;
        }

        ul {
            list-style-type: disc;
            margin: 20px 0;
            padding-left: 20px;
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
    </style>
</head>
<body>

<div class="container">
    <?php
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        // Check if files were uploaded
        if (isset($_FILES['followers']) && isset($_FILES['following'])) {
            
            // Get followers file
            $followersFile = $_FILES['followers']['tmp_name'];
            $followingFile = $_FILES['following']['tmp_name'];
            
            // Check if the files are valid JSON files
            if (!file_exists($followersFile) || !file_exists($followingFile)) {
                die("<h2>Error uploading files.</h2>");
            }
            
            // Read and decode followers JSON
            $followersData = json_decode(file_get_contents($followersFile), true);
            $followers = [];

            foreach ($followersData as $item) {
                foreach ($item["string_list_data"] as $j) {
                    $followers[] = $j["value"];
                }
            }
            
            // Read and decode following JSON
            $followingData = json_decode(file_get_contents($followingFile), true);
            $following = [];

            foreach ($followingData["relationships_following"] as $item) {
                foreach ($item["string_list_data"] as $j) {

                    $path = parse_url($j["href"], PHP_URL_PATH); 
                    $parts = explode("/", trim($path, "/"));
                    $username = end($parts);

                    $following[] = $username;
                }
            }
            
            $followers = array_unique(array_map('strtolower', $followers));
            $following = array_unique(array_map('strtolower', $following));

            // Users you follow but they don't follow you back
            $unfollowers = array_diff($following, $followers);

            // Count unfollowers
            $unfollowersCount = count($unfollowers);

            // Display unfollowers with count
            echo "<h2>$unfollowersCount Users That Not Follback</h2>";
            if ($unfollowersCount > 0) {
                echo "<ul>";
                foreach ($unfollowers as $user) {
                    echo "<li><a href='https://www.instagram.com/$user' target='_blank'>$user</a></li>";
                }
                echo "</ul>";
            } else {
                echo "<p>You're not being unfollowed by anyone!</p>";
            }
        } else {
            echo "<h2>Please upload both files.</h2>";
        }
    } else {
        echo "<h2>Invalid request.</h2>";
    }
    ?>
    <div class="footer">
        <a href="follback.html">Back to Upload</a>
    </div>
</div>

</body>
</html>
