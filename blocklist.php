<?php
// Set the content type to text/html
header('Content-Type: text/html; charset=utf-8');

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Blocklist | Checker Result</title>
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
        // Check if the blocklist file was uploaded
        if (isset($_FILES['blocklist'])) {
            
            // Get blocklist file
            $blocksFile = $_FILES['blocklist']['tmp_name'];
            
            // Check if the file is valid
            if (!file_exists($blocksFile)) {
                die("<h2>Error uploading file.</h2>");
            }
            
            // Read and decode the blocklist JSON
            $blocksData = json_decode(file_get_contents($blocksFile), true);
            
            // Initialize the blocked users array
            $blockeds = [];
            
            // Loop through the data to extract usernames
            foreach ($blocksData['relationships_blocked_users'] as $item) {
                if (isset($item['title'])) {
                    $blockeds[] = $item['title'];
                }
            }
            
            // Count the number of blocked users
            $blockedsCount = count($blockeds);

            // Display the count and list of blocked users
            echo "<h2>$blockedsCount Users That You Blocked</h2>";
            if ($blockedsCount > 0) {
                echo "<ul>";
                foreach ($blockeds as $user) {
                    echo "<li><a href='https://www.instagram.com/_u/$user' target='_blank'>$user</a></li>";
                }
                echo "</ul>";
            } else {
                echo "<p>You're not blocking anyone yet!</p>";
            }
        } else {
            echo "<h2>Please upload the correct file.</h2>";
        }
    } else {
        echo "<h2>Invalid request.</h2>";
    }
    ?>
    <div class="footer">
        <a href="blocklist.html">Back to Upload</a>
    </div>
</div>

</body>
</html>
