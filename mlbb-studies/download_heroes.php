<?php
// Directory to save hero JSON files
$save_dir = __DIR__ . '/hero_cache/';
if (!is_dir($save_dir)) mkdir($save_dir);

for ($i = 1; $i <= 129; $i++) {
    $api_url = "https://mlbb-stats.ridwaanhall.com/api/hero-detail/$i/";
    $json = @file_get_contents($api_url);
    if ($json) {
        file_put_contents($save_dir . "$i.json", $json);
        echo "Downloaded hero $i\n";
    } else {
        echo "Failed to fetch hero $i\n";
    }
    usleep(250000); // 0.25s delay to avoid hammering the API
}
echo "Done.\n";
?>