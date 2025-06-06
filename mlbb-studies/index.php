<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>MLBB Hero List</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: #181c24;
            color: #e9ecef;
            font-family: 'Segoe UI', 'Roboto', Arial, sans-serif;
        }
        .hero-card {
            margin-bottom: 15px;
        }
    </style>
</head>
<body>

<?php include 'navbar/navbar.php'; ?>

<div class="container">
    <h1 class="mb-4 text-primary">Mobile Legends: Bang Bang - Hero List</h1>

    <div class="row">
        <?php
        $url = "https://api-mobilelegends.vercel.app/api/hero-list/";
        $response = file_get_contents($url);
        $heroes = json_decode($response, true);

        if ($heroes) {
            foreach ($heroes as $id => $name) {
                echo "
                <div class='col-md-3'>
                    <div class='card hero-card shadow-sm'>
                        <div class='card-body'>
                            <h5 class='card-title text-center'>$name</h5>
                            <p class='card-text text-center'>Hero ID: $id</p>
                        </div>
                    </div>
                </div>
                ";
            }
        } else {
            echo "<p class='text-danger'>Failed to fetch hero data.</p>";
        }
        ?>
    </div>
</div>
</body>
</html>
