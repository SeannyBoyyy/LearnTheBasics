<?php
$save_dir = __DIR__ . '/hero_cache/';
if (!is_dir($save_dir)) mkdir($save_dir);

$url = "https://mlbb-stats.ridwaanhall.com/api/hero-list/";
$json = @file_get_contents($url);
if ($json) {
    file_put_contents($save_dir . "hero-list.json", $json);
    echo "Downloaded hero-list.json\n";
} else {
    echo "Failed to fetch hero list.\n";
}
?>