<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$mysqli = get_db_connection();

$team_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if (!$team_id) {
    echo "<div class='alert alert-danger'>No team selected.</div>";
    exit;
}

$alert = '';
$match_alert = '';
$last_saved_match_id = 0;

// Handle team edit (name/image)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_team'])) {
    $new_team_name = trim($_POST['edit_team_name']);
    $new_image_path = null;
    $update_image = false;

    // Handle image upload if provided
    if (isset($_FILES['edit_team_image']) && $_FILES['edit_team_image']['error'] === UPLOAD_ERR_OK) {
        $ext = strtolower(pathinfo($_FILES['edit_team_image']['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        if (in_array($ext, $allowed)) {
            $new_image_path = 'uploads/team_' . uniqid() . '.' . $ext;
            move_uploaded_file($_FILES['edit_team_image']['tmp_name'], $new_image_path);
            $update_image = true;
        } else {
            $alert = "Invalid image format.";
        }
    }

    if (!$alert && $new_team_name) {
        if ($update_image) {
            $stmt = $mysqli->prepare("UPDATE teams SET team_name=?, image=? WHERE id=? AND user_id=?");
            $stmt->bind_param('ssii', $new_team_name, $new_image_path, $team_id, $user_id);
        } else {
            $stmt = $mysqli->prepare("UPDATE teams SET team_name=? WHERE id=? AND user_id=?");
            $stmt->bind_param('sii', $new_team_name, $team_id, $user_id);
        }
        if ($stmt->execute()) {
            $alert = "Team updated successfully!";
        } else {
            $alert = "Failed to update team.";
        }
        $stmt->close();
    } elseif (!$alert) {
        $alert = "Team name is required.";
    }
}

// Handle team note submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['team_note'])) {
    $note = trim($_POST['team_note']);
    if ($note !== '') {
        $stmt = $mysqli->prepare("SELECT id FROM team_notes WHERE team_id=? AND user_id=?");
        $stmt->bind_param('ii', $team_id, $user_id);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            $stmt->close();
            $stmt = $mysqli->prepare("UPDATE team_notes SET note=?, updated_at=NOW() WHERE team_id=? AND user_id=?");
            $stmt->bind_param('sii', $note, $team_id, $user_id);
            $stmt->execute();
            $alert = "Team note saved!";
        } else {
            $stmt->close();
            $stmt = $mysqli->prepare("INSERT INTO team_notes (team_id, user_id, note) VALUES (?, ?, ?)");
            $stmt->bind_param('iis', $team_id, $user_id, $note);
            $stmt->execute();
            $alert = "Team note saved!";
        }
        $stmt->close();
    }
}

// Handle match note submission (with team_id in WHERE clause)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['match_note'], $_POST['match_id'])) {
    $match_id = intval($_POST['match_id']);
    $note = trim($_POST['match_note']);
    $note_team_id = isset($_POST['note_team_id']) ? intval($_POST['note_team_id']) : $team_id;
    if ($note !== '') {
        $stmt = $mysqli->prepare("SELECT id FROM match_notes WHERE match_id=? AND user_id=? AND team_id=?");
        $stmt->bind_param('iii', $match_id, $user_id, $note_team_id);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            $stmt->close();
            $stmt = $mysqli->prepare("UPDATE match_notes SET note=?, updated_at=NOW() WHERE match_id=? AND user_id=? AND team_id=?");
            $stmt->bind_param('siii', $note, $match_id, $user_id, $note_team_id);
            $stmt->execute();
            $match_alert = "Match note saved!";
        } else {
            $stmt->close();
            $stmt = $mysqli->prepare("INSERT INTO match_notes (match_id, user_id, note, team_id) VALUES (?, ?, ?, ?)");
            $stmt->bind_param('iisi', $match_id, $user_id, $note, $note_team_id);
            $stmt->execute();
            $match_alert = "Match note saved!";
        }
        $stmt->close();
        $last_saved_match_id = $match_id;
    }
}

// Fetch team info
$stmt = $mysqli->prepare("SELECT team_name, image, created_at, user_id FROM teams WHERE id=?");
$stmt->bind_param('i', $team_id);
$stmt->execute();
$stmt->bind_result($team_name, $team_image, $created_at, $team_owner_id);
if (!$stmt->fetch()) {
    echo "<div class='alert alert-danger'>Team not found.</div>";
    exit;
}
$stmt->close();

// Prevent access if not the owner
if ($team_owner_id != $user_id) {
    echo "<div class='alert alert-danger'>You do not have permission to view this team.</div>";
    exit;
}

// Fetch user's note for this team
$stmt = $mysqli->prepare("SELECT note FROM team_notes WHERE team_id=? AND user_id=?");
$stmt->bind_param('ii', $team_id, $user_id);
$stmt->execute();
$stmt->bind_result($team_note);
$stmt->fetch();
$stmt->close();

// Fetch all matches (drafts) for this team (as your_team or enemy_team), now also fetch team images
$stmt = $mysqli->prepare("
    SELECT d.*, 
        t1.team_name as your_team_name, 
        t1.image as your_team_image, 
        t2.team_name as enemy_team_name, 
        t2.image as enemy_team_image
    FROM drafts d
    LEFT JOIN teams t1 ON d.your_team_id = t1.id
    LEFT JOIN teams t2 ON d.enemy_team_id = t2.id
    WHERE d.your_team_id=? OR d.enemy_team_id=?
    ORDER BY d.created_at DESC
");
$stmt->bind_param('ii', $team_id, $team_id);
$stmt->execute();
$matches = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Calculate total wins, total matches, and most played heroes for this team
$total_matches = count($matches);
$total_wins = 0;

// Calculate hero stats: played count and win count for each hero
$hero_stats = []; // hero_key => ['name'=>..., 'played'=>..., 'win'=>...,'matches'=>[]]
foreach ($matches as $match) {
    $is_our_team = ($match['your_team_id'] == $team_id);
    $picks = $is_our_team ? json_decode($match['your_picks'], true) : json_decode($match['enemy_picks'], true);

    // Determine if this match is a win for this team
    $is_win = (
        ($match['winner'] == 'your_team' && $match['your_team_id'] == $team_id) ||
        ($match['winner'] == 'enemy_team' && $match['enemy_team_id'] == $team_id)
    );
    if ($is_win) $total_wins++;

    if (is_array($picks)) {
        foreach ($picks as $hero) {
            $hero_key = strtolower($hero);
            if (!isset($hero_stats[$hero_key])) {
                $hero_stats[$hero_key] = ['name' => $hero, 'played' => 0, 'win' => 0, 'matches' => []];
            }
            $hero_stats[$hero_key]['played']++;
            $hero_stats[$hero_key]['matches'][] = [
                'match' => $match,
                'is_win' => $is_win
            ];
            if ($is_win) {
                $hero_stats[$hero_key]['win']++;
            }
        }
    }
}

// Sort by most played (main heroes)
usort($hero_stats, function($a, $b) {
    return $b['played'] <=> $a['played'];
});
$top_main_heroes = array_slice($hero_stats, 0, 5);

// Calculate win percentage
$win_percentage = $total_matches > 0 ? round(($total_wins / $total_matches) * 100, 1) : 0;

// Fetch all match notes for this user for these matches, but only for this team
$match_notes = [];
if ($matches) {
    $match_ids = array_column($matches, 'id');
    if (count($match_ids)) {
        $in = implode(',', array_fill(0, count($match_ids), '?'));
        $types = str_repeat('i', count($match_ids));
        $sql = "SELECT match_id, note FROM match_notes WHERE user_id=? AND team_id=? AND match_id IN ($in)";
        $stmt = $mysqli->prepare($sql);
        $params = array_merge([$user_id, $team_id], $match_ids);
        $stmt->bind_param('ii' . $types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $match_notes[$row['match_id']] = $row['note'];
        }
        $stmt->close();
    }
}

$mysqli->close();

// Fetch all hero images and names from API (once)
$hero_api_url = "https://mlbb-stats.ridwaanhall.com/api/hero-position/?role=all&lane=all&size=128&index=1";
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $hero_api_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$hero_api_response = curl_exec($ch);
curl_close($ch);

$hero_images = [];
if ($hero_api_response) {
    $data = json_decode($hero_api_response, true);
    if (isset($data['code']) && $data['code'] == 0) {
        foreach ($data['data']['records'] as $record) {
            $hero = $record['data']['hero']['data'] ?? null;
            if ($hero && isset($hero['name'], $hero['smallmap'])) {
                $hero_images[strtolower($hero['name'])] = $hero['smallmap'];
            }
        }
    }
}
function hero_img_tag($hero_name, $hero_images) {
    $key = strtolower($hero_name);
    $img = isset($hero_images[$key]) ? $hero_images[$key] : 'https://via.placeholder.com/32x32?text=?';
    return '<img src="' . htmlspecialchars($img) . '" alt="' . htmlspecialchars($hero_name) . '" style="width:32px;height:32px;border-radius:6px;object-fit:cover;margin-right:4px;background:#232837;border:1.5px solid #6ea8fe44;">';
}

// --- ANALYSIS CALCULATION ---

// Most played heroes (top 5)
$most_played_heroes = $hero_stats;
usort($most_played_heroes, function($a, $b) {
    return $b['played'] <=> $a['played'];
});
$most_played_heroes = array_slice($most_played_heroes, 0, 5);

// Most win heroes (top 5)
$most_win_heroes = $hero_stats;
usort($most_win_heroes, function($a, $b) {
    return $b['win'] <=> $a['win'];
});
$most_win_heroes = array_slice($most_win_heroes, 0, 5);

// Most banned heroes (top 5)
$ban_stats = [];
foreach ($matches as $match) {
    $is_our_team = ($match['your_team_id'] == $team_id);
    $bans = $is_our_team ? json_decode($match['your_bans'], true) : json_decode($match['enemy_bans'], true);
    if (is_array($bans)) {
        foreach ($bans as $ban) {
            $ban_key = strtolower($ban);
            if (!isset($ban_stats[$ban_key])) {
                $ban_stats[$ban_key] = ['name' => $ban, 'banned' => 0];
            }
            $ban_stats[$ban_key]['banned']++;
        }
    }
}
usort($ban_stats, function($a, $b) {
    return $b['banned'] <=> $a['banned'];
});
$most_banned_heroes = array_slice($ban_stats, 0, 5);

// Lowest winrate heroes (top 3, at least 2 matches, sorted by winrate asc)
$lose_winrate_heroes = [];
foreach ($hero_stats as $hero) {
    if ($hero['played'] >= 2) {
        $wr = $hero['played'] > 0 ? ($hero['win'] / $hero['played']) * 100 : 0;
        $lose_winrate_heroes[] = [
            'name' => $hero['name'],
            'played' => $hero['played'],
            'win' => $hero['win'],
            'winrate' => $wr
        ];
    }
}
usort($lose_winrate_heroes, function($a, $b) {
    return $a['winrate'] <=> $b['winrate'];
});
$lose_winrate_heroes = array_slice($lose_winrate_heroes, 0, 3);

// Pagination for matches
$matches_per_page = 5;
$total_pages = max(1, ceil($total_matches / $matches_per_page));
$current_page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$start_index = ($current_page - 1) * $matches_per_page;
$matches_to_show = array_slice($matches, $start_index, $matches_per_page);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Team Profile - <?= htmlspecialchars($team_name) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        body { background: #181c24; color: #fff; }
        .card { background: #232837; }
        .team-img { width: 120px; height: 120px; border-radius: 50%; object-fit: cover; border: 3px solid #0d6efd; }
        .match-card { background: #232837; border-radius: 10px; margin-bottom: 18px; }
        .badge-win { background: #198754; }
        .badge-lose { background: #dc3545; }
        textarea.form-control { background: #232837; color: #fff; }
        .match-team-img { width:60px; height:60px; border-radius:50%; object-fit:cover; border:2px solid #6ea8fe33; }
        .match-team-img.enemy { border-color: #dc354544; }
        .hero-img { width:48px; height:48px; border-radius:8px; object-fit:cover; margin-right:6px; background:#232837; border:1.5px solid #6ea8fe44; }
    </style>
</head>
<body>
<?php include 'navbar/navbar.php'; ?>
<div class="container py-5">
    <?php if ($alert): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?= htmlspecialchars($alert) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
    <!-- Analyze This Team Button -->
        <div class="mb-4">
            <button class="btn btn-outline-info" type="button" id="analyzeTeamBtn">
                <i class="bi bi-bar-chart-fill me-2"></i>Analyze This Team
            </button>
        </div>
        <!-- Analyze This Team Section (hidden by default) -->
        <div class="card p-4 mb-4" id="analyzeTeamSection" style="background: #1a2332; display: none;">
            <h4 class="text-info mb-3"><i class="bi bi-bar-chart-fill me-2"></i>Analyze This Team</h4>
            <div class="row g-3">
                <div class="col-md-6 col-lg-3">
                    <div class="bg-dark rounded-3 p-3 text-center shadow-sm">
                        <div class="fw-bold text-primary mb-2">Most Played Heroes</div>
                        <?php if ($most_played_heroes): ?>
                            <?php foreach ($most_played_heroes as $hero): ?>
                                <div class="mb-2 text-white">
                                    <?= hero_img_tag($hero['name'], $hero_images) ?>
                                    <span class="fw-semibold"><?= htmlspecialchars($hero['name']) ?></span>
                                    <span class="small text-secondary">(<?= $hero['played'] ?> matches)</span>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="text-secondary">No data</div>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="col-md-6 col-lg-3">
                    <div class="bg-dark rounded-3 p-3 text-center shadow-sm">
                        <div class="fw-bold text-success mb-2">Most Win Heroes</div>
                        <?php if ($most_win_heroes): ?>
                            <?php foreach ($most_win_heroes as $hero): ?>
                                <div class="mb-2 text-white">
                                    <?= hero_img_tag($hero['name'], $hero_images) ?>
                                    <span class="fw-semibold"><?= htmlspecialchars($hero['name']) ?></span>
                                    <span class="small text-secondary">(<?= $hero['win'] ?> wins)</span>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="text-secondary">No data</div>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="col-md-6 col-lg-3">
                    <div class="bg-dark rounded-3 p-3 text-center shadow-sm">
                        <div class="fw-bold text-warning mb-2">Most Banned Heroes</div>
                        <?php if ($most_banned_heroes): ?>
                            <?php foreach ($most_banned_heroes as $hero): ?>
                                <div class="mb-2 text-white">
                                    <?= hero_img_tag($hero['name'], $hero_images) ?>
                                    <span class="fw-semibold"><?= htmlspecialchars($hero['name']) ?></span>
                                    <span class="small text-secondary">(<?= $hero['banned'] ?> bans)</span>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="text-secondary">No data</div>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="col-md-6 col-lg-3">
                    <div class="bg-dark rounded-3 p-3 text-center shadow-sm">
                        <div class="fw-bold text-danger mb-2">Lowest Winrate Heroes</div>
                        <?php if ($lose_winrate_heroes): ?>
                            <?php foreach ($lose_winrate_heroes as $hero): ?>
                                <div class="mb-2 text-white">
                                    <?= hero_img_tag($hero['name'], $hero_images) ?>
                                    <span class="fw-semibold"><?= htmlspecialchars($hero['name']) ?></span>
                                    <span class="small text-secondary">(<?= $hero['played'] ?> matches, <?= $hero['win'] ?> wins)</span>
                                    <span class="small text-danger"><?= round($hero['winrate'], 1) ?>% winrate</span>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="text-secondary">No data (need at least 2 matches)</div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <div class="row g-3 mt-4">
                <!-- New: Heroes that beat this team most often -->
                <div class="col-md-6 col-lg-3">
                    <div class="bg-dark rounded-3 p-3 text-center shadow-sm">
                        <div class="fw-bold text-danger mb-2">Heroes That Beat Us Most</div>
                        <?php
                        // Calculate heroes that beat this team most often
                        $heroes_beat_us = [];
                        foreach ($matches as $match) {
                            $is_loss = !(
                                ($match['winner'] == 'your_team' && $match['your_team_id'] == $team_id) ||
                                ($match['winner'] == 'enemy_team' && $match['enemy_team_id'] == $team_id
                            ));
                            
                            if ($is_loss) {
                                $enemy_picks = ($match['your_team_id'] == $team_id) ? 
                                    json_decode($match['enemy_picks'], true) : 
                                    json_decode($match['your_picks'], true);
                                
                                if (is_array($enemy_picks)) {
                                    foreach ($enemy_picks as $hero) {
                                        $hero_key = strtolower($hero);
                                        if (!isset($heroes_beat_us[$hero_key])) {
                                            $heroes_beat_us[$hero_key] = ['name' => $hero, 'count' => 0];
                                        }
                                        $heroes_beat_us[$hero_key]['count']++;
                                    }
                                }
                            }
                        }
                        
                        // Sort by most losses
                        usort($heroes_beat_us, function($a, $b) {
                            return $b['count'] <=> $a['count'];
                        });
                        $top_heroes_beat_us = array_slice($heroes_beat_us, 0, 5);
                        ?>
                        
                        <?php if ($top_heroes_beat_us): ?>
                            <?php foreach ($top_heroes_beat_us as $hero): ?>
                                <div class="mb-2 text-white">
                                    <?= hero_img_tag($hero['name'], $hero_images) ?>
                                    <span class="fw-semibold"><?= htmlspecialchars($hero['name']) ?></span>
                                    <span class="small text-secondary">(<?= $hero['count'] ?> loss<?= $hero['count'] > 1 ? 'es' : '' ?>)</span>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="text-secondary">No data</div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- New: Enemy Bans Against Us (All Matches) -->
                <div class="col-md-6 col-lg-3">
                    <div class="bg-dark rounded-3 p-3 text-center shadow-sm">
                        <div class="fw-bold text-warning mb-2">Enemy Bans Against Us</div>
                        <?php
                        // Calculate enemy bans against this team
                        $enemy_bans_against_us = [];
                        foreach ($matches as $match) {
                            $enemy_bans = ($match['your_team_id'] == $team_id) ? 
                                json_decode($match['enemy_bans'], true) : 
                                json_decode($match['your_bans'], true);
                            
                            if (is_array($enemy_bans)) {
                                foreach ($enemy_bans as $ban) {
                                    $ban_key = strtolower($ban);
                                    if (!isset($enemy_bans_against_us[$ban_key])) {
                                        $enemy_bans_against_us[$ban_key] = ['name' => $ban, 'count' => 0];
                                    }
                                    $enemy_bans_against_us[$ban_key]['count']++;
                                }
                            }
                        }
                        
                        // Sort by most banned against us
                        usort($enemy_bans_against_us, function($a, $b) {
                            return $b['count'] <=> $a['count'];
                        });
                        $top_enemy_bans = array_slice($enemy_bans_against_us, 0, 5);
                        ?>
                        
                        <?php if ($top_enemy_bans): ?>
                            <?php foreach ($top_enemy_bans as $ban): ?>
                                <div class="mb-2 text-white">
                                    <?= hero_img_tag($ban['name'], $hero_images) ?>
                                    <span class="fw-semibold"><?= htmlspecialchars($ban['name']) ?></span>
                                    <span class="small text-secondary">(<?= $ban['count'] ?> ban<?= $ban['count'] > 1 ? 's' : '' ?>)</span>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="text-secondary">No data</div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- New: Enemy Bans When We Lost -->
                <div class="col-md-6 col-lg-3">
                    <div class="bg-dark rounded-3 p-3 text-center shadow-sm">
                        <div class="fw-bold text-danger mb-2">Enemy Bans When We Lost</div>
                        <?php
                        // Calculate enemy bans in matches we lost
                        $enemy_bans_when_we_lost = [];
                        foreach ($matches as $match) {
                            $is_loss = !(
                                ($match['winner'] == 'your_team' && $match['your_team_id'] == $team_id) ||
                                ($match['winner'] == 'enemy_team' && $match['enemy_team_id'] == $team_id
                            ));
                            
                            if ($is_loss) {
                                $enemy_bans = ($match['your_team_id'] == $team_id) ? 
                                    json_decode($match['enemy_bans'], true) : 
                                    json_decode($match['your_bans'], true);
                                
                                if (is_array($enemy_bans)) {
                                    foreach ($enemy_bans as $ban) {
                                        $ban_key = strtolower($ban);
                                        if (!isset($enemy_bans_when_we_lost[$ban_key])) {
                                            $enemy_bans_when_we_lost[$ban_key] = ['name' => $ban, 'count' => 0];
                                        }
                                        $enemy_bans_when_we_lost[$ban_key]['count']++;
                                    }
                                }
                            }
                        }
                        
                        // Sort by most banned when we lost
                        usort($enemy_bans_when_we_lost, function($a, $b) {
                            return $b['count'] <=> $a['count'];
                        });
                        $top_enemy_bans_loss = array_slice($enemy_bans_when_we_lost, 0, 5);
                        ?>
                        
                        <?php if ($top_enemy_bans_loss): ?>
                            <?php foreach ($top_enemy_bans_loss as $ban): ?>
                                <div class="mb-2 text-white">
                                    <?= hero_img_tag($ban['name'], $hero_images) ?>
                                    <span class="fw-semibold"><?= htmlspecialchars($ban['name']) ?></span>
                                    <span class="small text-secondary">(<?= $ban['count'] ?> loss<?= $ban['count'] > 1 ? 'es' : '' ?>)</span>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="text-secondary">No data</div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <!-- ...you can keep the rest of your analysis section here... -->
        </div>
        <!-- End Analyze This Team Section -->
         
    <div class="card p-4 mb-4">
        <div class="d-flex align-items-start gap-4 p-4 rounded shadow" style="background: linear-gradient(90deg, #232837 70%, #0d6efd22 100%);">
            <div class="d-flex flex-column align-items-center justify-content-start" style="min-width:140px; max-width:180px;">
                <?php if ($team_image): ?>
                    <img src="<?= htmlspecialchars($team_image) ?>" class="team-img shadow-lg border border-3 border-primary mb-2" alt="Team Logo" style="box-shadow:0 4px 24px #0d6efd44;">
                <?php endif; ?>
                <h2 class="mb-0 text-white fw-bold text-center mt-2" style="word-break:break-word; white-space:normal; max-width:170px;">
                    <?= htmlspecialchars($team_name) ?>
                </h2>
                <span class="badge bg-secondary m-3" style="font-size:1em;">
                    Created: <?= date('M j, Y', strtotime($created_at)) ?>
                </span>
            </div>
            <div class="flex-grow-1">
                <div class="d-flex align-items-center gap-3 mb-2">
                    <span class="fs-5 text-success fw-semibold">
                        <i class="bi bi-trophy-fill me-1"></i>
                        <?= $total_wins ?> <span class="text-white-50">/ <?= $total_matches ?> Wins</span>
                    </span>
                    <span class="badge bg-info text-dark fs-6 px-3 py-2">
                        Winrate: <?= $win_percentage ?>%
                    </span>
                </div>
                <?php if (!empty($top_main_heroes)): ?>
                <div class="mt-3">
                    <strong class="text-secondary">Top 5 Main Heroes:</strong>
                    <div class="d-flex flex-wrap align-items-center mt-2 gap-3">
                        <?php foreach ($top_main_heroes as $hero): ?>
                            <?php $winrate = $hero['played'] > 0 ? round(($hero['win'] / $hero['played']) * 100, 1) : 0; ?>
                            <div class="bg-dark rounded-4 p-2 px-3 d-flex align-items-center shadow-sm" style="min-width:220px;">
                                <?= hero_img_tag($hero['name'], $hero_images) ?>
                                <div>
                                    <div class="fw-semibold text-white"><?= htmlspecialchars($hero['name']) ?></div>
                                    <div class="d-flex align-items-center gap-2 mt-1">
                                        <span class="badge bg-primary"><?= $hero['played'] ?> match<?= $hero['played'] > 1 ? 'es' : '' ?></span>
                                        <span class="badge bg-success"><?= $hero['win'] ?> win<?= $hero['win'] > 1 ? 's' : '' ?></span>
                                        <span class="badge bg-info text-dark"><?= $winrate ?>%</span>
                                        <button class="btn btn-sm btn-outline-light" type="button" data-bs-toggle="collapse" data-bs-target="#matches-<?= md5($hero['name']) ?>">
                                            <i class="bi bi-list-ul"></i> 
                                        </button> 
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <?php foreach ($top_main_heroes as $hero): ?>
                        <div class="collapse mt-2" id="matches-<?= md5($hero['name']) ?>">
                            <div class="card card-body bg-dark text-light border border-primary">
                                <strong>Matches with <?= htmlspecialchars($hero['name']) ?>:</strong>
                                <ul class="mb-0 list-unstyled">
                                <?php foreach ($hero['matches'] as $idx => $info): ?>
                                    <li class="mb-1">
                                        <span class="badge <?= $info['is_win'] ? 'bg-success' : 'bg-danger' ?> me-1"><?= $info['is_win'] ? 'WIN' : 'LOSE' ?></span>
                                        <span class="badge bg-light text-dark me-1"><?= $idx + 1 ?></span>
                                        <a href="view-draft.php?id=<?= $info['match']['id'] ?>&team_id=<?= $team_id ?>" class="link-info" target="_blank">
                                            <?= date('M j, Y H:i', strtotime($info['match']['created_at'])) ?> vs
                                            <?= htmlspecialchars(($info['match']['your_team_id'] == $team_id) ? $info['match']['enemy_team_name'] : $info['match']['your_team_name']) ?>
                                        </a>
                                    </li>
                                <?php endforeach; ?>
                                </ul>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <form method="post" enctype="multipart/form-data" class="mt-3 mb-2">
            <input type="hidden" name="edit_team" value="1">
            <div class="row g-2 align-items-center">
                <div class="col-md-5">
                    <label class="form-label text-info mb-1">Edit Team Name:</label>
                    <input type="text" name="edit_team_name" class="form-control mb-2" maxlength="100"
                        value="<?= isset($_POST['edit_team_name']) ? htmlspecialchars($_POST['edit_team_name']) : htmlspecialchars($team_name) ?>" required>
                </div>
                <div class="col-md-5">
                    <label class="form-label text-info mb-1">Edit Team Image:</label>
                    <input type="file" name="edit_team_image" class="form-control mb-2" accept="image/*">
                </div>
                <div class="col-md-2 d-grid">
                    <button type="submit" class="btn btn-warning mt-3">Save Changes</button>
                </div>
            </div>
        </form>
        <form method="post" class="mt-2">
            <label class="form-label text-info">Your Notes for this Team:</label>
            <textarea name="team_note" class="form-control mb-2 bg-white text-secondary" rows="2" placeholder="Add notes about this team..."><?= htmlspecialchars($team_note ?? '') ?></textarea>
            <button type="submit" class="btn btn-primary btn-sm">Save Team Note</button>
        </form>
    </div>
    <h4 class="mb-3 text-primary">Matches</h4>
    <?php if (empty($matches)): ?>
    <div class="alert alert-info">No matches found for this team.</div>
    <?php else: ?>
        <?php foreach ($matches_to_show as $match): ?>
            <?php
            $show_match_alert = ($match_alert && $last_saved_match_id == $match['id']);
            $is_win = ($match['winner'] == 'your_team' && $match['your_team_id'] == $team_id) ||
                    ($match['winner'] == 'enemy_team' && $match['enemy_team_id'] == $team_id);
            ?>
            <?php if ($show_match_alert): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?= htmlspecialchars($match_alert) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
            <div class="match-card p-3">
                <div class="d-flex justify-content-between align-items-center">
                    <div class="d-flex align-items-center gap-2">
                        <img src="<?= $match['your_team_image'] ? htmlspecialchars($match['your_team_image']) : 'https://via.placeholder.com/40x40?text=Team' ?>" alt="Team Image" class="match-team-img me-1">
                        <strong><?= htmlspecialchars($match['your_team_name']) ?></strong>
                        <span class="text-secondary mx-2">vs</span>
                        <img src="<?= $match['enemy_team_image'] ? htmlspecialchars($match['enemy_team_image']) : 'https://via.placeholder.com/40x40?text=Team' ?>" alt="Team Image" class="match-team-img enemy me-1">
                        <strong><?= htmlspecialchars($match['enemy_team_name']) ?></strong>
                        <?php
                        // Show pick order using new values
                        if ($match['pick_order'] == 'your_team') {
                            echo '<span class="badge bg-info ms-2">First Pick: ' . htmlspecialchars($match['your_team_name']) . '</span>';
                        } elseif ($match['pick_order'] == 'enemy_team') {
                            echo '<span class="badge bg-info ms-2">First Pick: ' . htmlspecialchars($match['enemy_team_name']) . '</span>';
                        }
                        ?>
                    </div>
                    <div>
                        <span class="badge <?= $is_win ? 'badge-win' : 'badge-lose' ?>">
                            <?= $is_win ? 'WIN' : 'LOSE' ?>
                        </span>
                        <span class="text-secondary ms-2"><?= date('F j, Y \a\t g:i A', strtotime($match['created_at'])) ?></span>
                        <a href="view-draft.php?id=<?= $match['id'] ?>&team_id=<?= $team_id ?>" class="btn btn-outline-info btn-sm ms-2">View Draft</a>
                    </div>
                </div>
                <div class="mt-2">
                    <strong>Picks:</strong>
                    <div class="d-flex flex-wrap align-items-center mt-1 mb-2">
                    <?php
                    $picks = ($match['your_team_id'] == $team_id) ? json_decode($match['your_picks'], true) : json_decode($match['enemy_picks'], true);
                    foreach ($picks as $pick) {
                        echo hero_img_tag($pick, $hero_images) . '<span class="me-2">' . htmlspecialchars($pick) . '</span>';
                    }
                    ?>
                    </div>
                </div>
                <div>
                    <strong>Bans:</strong>
                    <div class="d-flex flex-wrap align-items-center mt-1 mb-2">
                    <?php
                    $bans = ($match['your_team_id'] == $team_id) ? json_decode($match['your_bans'], true) : json_decode($match['enemy_bans'], true);
                    foreach ($bans as $ban) {
                        echo hero_img_tag($ban, $hero_images) . '<span class="me-2">' . htmlspecialchars($ban) . '</span>';
                    }
                    ?>
                    </div>
                </div>
                <form method="post" class="mt-2">
                    <input type="hidden" name="match_id" value="<?= $match['id'] ?>">
                    <input type="hidden" name="note_team_id" value="<?= $team_id ?>">
                    <label class="form-label text-info">Your Notes for this Match:</label>
                    <textarea name="match_note" class="form-control mb-2 bg-white text-secondary" rows="2" placeholder="Add notes about this match..."><?= htmlspecialchars($match_notes[$match['id']] ?? '') ?></textarea>
                    <button type="submit" class="btn btn-primary btn-sm">Save Match Note</button>
                </form>
            </div>
        <?php endforeach; ?>

        <a href="dashboard.php" class="btn btn-secondary my-3">Back to Teams</a>
        
        <!-- Pagination Bar -->
        <?php if ($total_pages > 1): ?>
            <nav aria-label="Match pagination">
                <ul class="pagination justify-content-center mt-3">
                    <?php
                    $base_url = strtok($_SERVER["REQUEST_URI"], '?');
                    $query = $_GET;
                    for ($i = 1; $i <= $total_pages; $i++):
                        $query['page'] = $i;
                        $active = $i == $current_page ? ' active' : '';
                    ?>
                        <li class="page-item<?= $active ?>">
                            <a class="page-link" href="<?= htmlspecialchars($base_url . '?' . http_build_query($query)) ?>"><?= $i ?></a>
                        </li>
                    <?php endfor; ?>
                </ul>
            </nav>
        <?php endif; ?>
    <?php endif; ?>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Toggle Analyze Section
    document.getElementById('analyzeTeamBtn').addEventListener('click', function() {
        var section = document.getElementById('analyzeTeamSection');
        if (section.style.display === 'none') {
            section.style.display = '';
            this.classList.remove('btn-outline-info');
            this.classList.add('btn-info');
            this.innerHTML = '<i class="bi bi-bar-chart-fill me-2"></i>Hide Analysis';
        } else {
            section.style.display = 'none';
            this.classList.remove('btn-info');
            this.classList.add('btn-outline-info');
            this.innerHTML = '<i class="bi bi-bar-chart-fill me-2"></i>Analyze This Team';
        }
    });
</script>
</body>
</html>