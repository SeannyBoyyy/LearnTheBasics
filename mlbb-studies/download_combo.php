<?php
// Directory to save hero combo JSON files
$save_dir = __DIR__ . '/hero_cache/';
if (!is_dir($save_dir)) mkdir($save_dir);

for ($i = 1; $i <= 129; $i++) {
    $api_url = "https://mlbb-stats.ridwaanhall.com/api/hero-skill-combo/$i/";
    $json = @file_get_contents($api_url);
    if ($json) {
        file_put_contents($save_dir . "skill-combo-$i.json", $json);
        echo "Downloaded combo for hero $i\n";
    } else {
        echo "Failed to fetch combo for hero $i\n";
    }
    usleep(250000); // 0.25s delay to avoid hammering the API
}
echo "Done.\n";
?>