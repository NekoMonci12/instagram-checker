<?php
// Set the content type to text/html
header('Content-Type: text/html; charset=utf-8');

/**
 * FOLLBACK CHECKER - FIXED VERSION
 * Perbaikan:
 * 1. JSON validation yang proper
 * 2. File upload security
 * 3. Error handling yang lengkap
 * 4. Parsing username yang lebih reliable
 * 5. Struktur data validation
 */

// Fungsi untuk validasi uploaded file
function validateUploadedFile($fileInputName, $maxSizeInMB = 10) {
    // Cek apakah file ada di $_FILES
    if (!isset($_FILES[$fileInputName])) {
        return ['error' => "File tidak ditemukan"];
    }

    $file = $_FILES[$fileInputName];

    // Cek upload error
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $errorMessages = [
            UPLOAD_ERR_INI_SIZE => "File melebihi upload_max_filesize",
            UPLOAD_ERR_FORM_SIZE => "File melebihi max_file_size",
            UPLOAD_ERR_PARTIAL => "File hanya terupload sebagian",
            UPLOAD_ERR_NO_FILE => "Tidak ada file yang diupload",
            UPLOAD_ERR_NO_TMP_DIR => "Temporary folder hilang",
            UPLOAD_ERR_CANT_WRITE => "Tidak bisa write ke disk",
            UPLOAD_ERR_EXTENSION => "Upload dihentikan oleh extension"
        ];
        return ['error' => $errorMessages[$file['error']] ?? "Upload error tidak diketahui"];
    }

    // Cek extension file
    $fileName = $file['name'];
    if (!preg_match('/\.json$/i', $fileName)) {
        return ['error' => "File harus berformat .json (Anda upload: $fileName)"];
    }

    // Cek file size
    $fileSizeInMB = $file['size'] / (1024 * 1024);
    if ($fileSizeInMB > $maxSizeInMB) {
        return ['error' => "File terlalu besar ({$fileSizeInMB}MB, max: {$maxSizeInMB}MB)"];
    }

    // Cek apakah file benar-benar ada di temp
    if (!file_exists($file['tmp_name'])) {
        return ['error' => "File temporary hilang"];
    }

    // Validasi JSON
    $content = @file_get_contents($file['tmp_name']);
    if ($content === false) {
        return ['error' => "Tidak bisa membaca file"];
    }

    $decodedData = json_decode($content, true);
    if ($decodedData === null && json_last_error() !== JSON_ERROR_NONE) {
        return ['error' => "File bukan JSON valid: " . json_last_error_msg()];
    }

    return ['success' => true, 'path' => $file['tmp_name'], 'data' => $decodedData];
}

// Fungsi untuk extract username dari followers
function extractFollowers($followersData) {
    $followers = [];
    $errors = [];

    if (!is_array($followersData)) {
        return ['followers' => [], 'errors' => ["Followers data bukan array"]];
    }

    foreach ($followersData as $index => $item) {
        // Validasi struktur
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

// Fungsi untuk extract username dari following
function extractFollowing($followingData) {
    $following = [];
    $errors = [];

    // Validasi struktur
    if (!isset($followingData["relationships_following"]) || !is_array($followingData["relationships_following"])) {
        return ['following' => [], 'errors' => ["Struktur following.json tidak sesuai"]];
    }

    foreach ($followingData["relationships_following"] as $item) {
        // Cek apakah ada title (lebih reliable)
        if (isset($item["title"]) && !empty($item["title"])) {
            $following[] = strtolower($item["title"]);
        }
        // Fallback: parse dari href jika title tidak ada
        elseif (isset($item["string_list_data"]) && is_array($item["string_list_data"])) {
            foreach ($item["string_list_data"] as $userItem) {
                if (isset($userItem["href"])) {
                    // Extract username dari URL
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
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }

        .container {
            max-width: 700px;
            margin: 0 auto;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
            overflow: hidden;
        }

        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px 20px;
            text-align: center;
        }

        .header h1 {
            font-size: 28px;
            margin-bottom: 5px;
        }

        .header p {
            opacity: 0.9;
            font-size: 14px;
        }

        .content {
            padding: 30px 20px;
        }

        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 4px solid;
        }

        .alert.error {
            background-color: #fee;
            border-left-color: #c33;
            color: #c33;
        }

        .alert.success {
            background-color: #efe;
            border-left-color: #3c3;
            color: #3c3;
        }

        .alert.warning {
            background-color: #ffe;
            border-left-color: #cc3;
            color: #884400;
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
            border-top-color: #667eea;
        }

        .stat-box.following {
            border-top-color: #764ba2;
        }

        .stat-box.notfollback {
            border-top-color: #f59e0b;
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

        h2 {
            font-size: 20px;
            margin: 25px 0 15px 0;
            color: #333;
            border-bottom: 2px solid #667eea;
            padding-bottom: 10px;
        }

        ul {
            list-style: none;
            column-count: 2;
            column-gap: 20px;
            margin-bottom: 20px;
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
            color: #667eea;
            text-decoration: none;
            font-weight: 500;
        }

        a:hover {
            text-decoration: underline;
            color: #764ba2;
        }

        .footer {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #eee;
            color: #666;
            font-size: 13px;
        }

        .footer a {
            display: inline-block;
            margin-top: 10px;
            padding: 10px 20px;
            background: #667eea;
            color: white;
            border-radius: 6px;
            text-decoration: none;
        }

        .footer a:hover {
            background: #764ba2;
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
        <h1>📊 Follback Checker</h1>
        <p>Analisis follow-unfollow Instagram Anda</p>
    </div>

    <div class="content">
        <?php
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            // Validasi file followers
            $followersValidation = validateUploadedFile('followers');
            if (isset($followersValidation['error'])) {
                echo '<div class="alert error">❌ Error Followers: ' . htmlspecialchars($followersValidation['error']) . '</div>';
                echo '<div class="footer"><a href="follback.html">← Kembali ke Upload</a></div>';
            }
            // Validasi file following
            elseif (!isset($_FILES['following'])) {
                echo '<div class="alert error">❌ File following tidak ditemukan</div>';
                echo '<div class="footer"><a href="follback.html">← Kembali ke Upload</a></div>';
            }
            else {
                $followingValidation = validateUploadedFile('following');
                if (isset($followingValidation['error'])) {
                    echo '<div class="alert error">❌ Error Following: ' . htmlspecialchars($followingValidation['error']) . '</div>';
                    echo '<div class="footer"><a href="follback.html">← Kembali ke Upload</a></div>';
                }
                else {
                    // Ekstrak data
                    $followersResult = extractFollowers($followersValidation['data']);
                    $followingResult = extractFollowing($followingValidation['data']);

                    $followers = $followersResult['followers'];
                    $following = $followingResult['following'];

                    // Hitung not follow back
                    $notFollowBack = array_diff($following, $followers);
                    $notFollowBackCount = count($notFollowBack);

                    // Hitung mutual
                    $mutual = count(array_intersect($following, $followers));

                    // Tampilkan statistik
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
                    echo '<p>Tidak Follow Back</p>';
                    echo '</div>';
                    echo '</div>';

                    // Tampilkan hasil
                    echo '<h2>👤 Pengguna Yang Tidak Follow Back (' . $notFollowBackCount . ')</h2>';

                    if ($notFollowBackCount > 0) {
                        echo '<ul>';
                        foreach (array_sort($notFollowBack) as $user) {
                            echo '<li><a href="https://www.instagram.com/' . htmlspecialchars($user) . '" target="_blank">' . htmlspecialchars($user) . '</a></li>';
                        }
                        echo '</ul>';

                        echo '<div class="alert warning">';
                        echo '💡 <strong>Tips:</strong> Anda bisa unfollow akun-akun di atas jika ingin. Gunakan Instagram app untuk mass unfollow.';
                        echo '</div>';
                    } else {
                        echo '<div class="empty">';
                        echo '<div class="emoji">🎉</div>';
                        echo '<p>Semua orang yang Anda follow juga follow Anda kembali!</p>';
                        echo '</div>';
                    }

                    // Info tambahan
                    echo '<h2>📈 Informasi Tambahan</h2>';
                    echo '<ul style="column-count: 1;">';
                    echo '<li><strong>Mutual Follow:</strong> ' . $mutual . ' orang</li>';
                    echo '<li><strong>Followers Yang Tidak Kamu Follow:</strong> ' . count(array_diff($followers, $following)) . ' orang</li>';
                    echo '<li><strong>Akurasi Data:</strong> Berdasarkan export terbaru dari Instagram</li>';
                    echo '</ul>';
                }
            }
        } else {
            echo '<div class="alert warning">';
            echo '⚠️ Permintaan tidak valid atau data tidak lengkap.';
            echo '</div>';
        }
        ?>

        <div class="footer">
            <a href="follback.html">← Kembali ke Upload</a>
        </div>
    </div>
</div>

</body>
</html>
