<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Instagram Checker</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f9f9f9;
            margin: 0;
            padding: 20px;
            text-align: center;
        }

        h1 {
            color: #333;
        }

        .container {
            max-width: 400px;
            margin: 0 auto;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            padding: 20px;
        }

        .button-container {
            display: flex;
            justify-content: center;
            gap: 10px; /* space between buttons */
        }

        .button {
            padding: 10px 20px;
            color: #fff;
            background-color: #007bff;
            text-decoration: none;
            border-radius: 5px;
            transition: background-color 0.3s ease;
            flex: 1; /* makes buttons flexible */
            text-align: center;
        }

        .button:hover {
            background-color: #0056b3;
        }

        .button-separator {
            display: inline-block;
            width: 10px; /* space between buttons */
        }
    </style>
</head>
<body>

<div class="container">
    <h1>Select Available Features</h1>
    <div class="button-container">
        <a href="follback.html" class="button">Follback</a>
        <span class="button-separator"></span>
        <a href="blocklist.html" class="button">Blocklist</a>
    </div>
</div>

</body>
</html>
