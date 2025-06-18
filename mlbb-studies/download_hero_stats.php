<?php
$save_dir = __DIR__ . '/hero_cache/';
if (!is_dir($save_dir)) mkdir($save_dir);

for ($i = 1; $i <= 129; $i++) {
    // Hero stats
    $url = "https://mlbb-stats.ridwaanhall.com/api/hero-detail-stats/$i/";
    $json = @file_get_contents($url);
    if ($json) {
        file_put_contents($save_dir . "hero-detail-stats-$i.json", $json);
        echo "Downloaded stats for hero $i\n";
    } else {
        echo "Failed to fetch stats for hero $i\n";
    }
    // Hero counter
    $url = "https://mlbb-stats.ridwaanhall.com/api/hero-counter/$i/";
    $json = @file_get_contents($url);
    if ($json) {
        file_put_contents($save_dir . "hero-counter-$i.json", $json);
        echo "Downloaded counter for hero $i\n";
    } else {
        echo "Failed to fetch counter for hero $i\n";
    }
    // Hero compatibility
    $url = "https://mlbb-stats.ridwaanhall.com/api/hero-compatibility/$i/";
    $json = @file_get_contents($url);
    if ($json) {
        file_put_contents($save_dir . "hero-compatibility-$i.json", $json);
        echo "Downloaded compatibility for hero $i\n";
    } else {
        echo "Failed to fetch compatibility for hero $i\n";
    }
    usleep(250000); // 0.25s delay between each hero to avoid hammering the API
}
echo "Done.\n";
?>