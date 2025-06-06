<?php
// filepath: c:\xampp\htdocs\mlbb-studies\view-draft.php

session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$mysqli = get_db_connection();

$draft_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if (!$draft_id) {
    echo "<div class='alert alert-danger'>No draft selected.</div>";
    exit;
}

// Get team_id from query string if present
$team_id = isset($_GET['team_id']) ? intval($_GET['team_id']) : 0;

// Fetch draft info
$stmt = $mysqli->prepare("
    SELECT d.*, 
        t1.team_name as your_team_name, t1.image as your_team_image,
        t2.team_name as enemy_team_name, t2.image as enemy_team_image
    FROM drafts d
    LEFT JOIN teams t1 ON d.your_team_id = t1.id
    LEFT JOIN teams t2 ON d.enemy_team_id = t2.id
    WHERE d.id=?
");
$stmt->bind_param('i', $draft_id);
$stmt->execute();
$draft = $stmt->get_result()->fetch_assoc();
$stmt->close();
$mysqli->close();

if (!$draft) {
    echo "<div class='alert alert-danger'>Draft not found.</div>";
    exit;
}

// Prevent access if not the owner
if ($draft['user_id'] != $user_id) {
    echo "<div class='alert alert-danger'>You do not have permission to view this draft.</div>";
    exit;
}

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
    $img = isset($hero_images[$key]) ? $hero_images[$key] : 'https://via.placeholder.com/48x48?text=?';
    return '<img src="' . htmlspecialchars($img) . '" alt="' . htmlspecialchars($hero_name) . '" style="width:48px;height:48px;border-radius:8px;object-fit:cover;margin-right:6px;background:#232837;border:1.5px solid #6ea8fe44;">';
}

// Determine if the viewed team won or lost
$viewed_team_id = $team_id ? $team_id : $draft['your_team_id'];
$is_win = (
    ($draft['winner'] == 'your_team' && $draft['your_team_id'] == $viewed_team_id) ||
    ($draft['winner'] == 'enemy_team' && $draft['enemy_team_id'] == $viewed_team_id)
);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>View Draft - <?= htmlspecialchars($draft['your_team_name']) ?> vs <?= htmlspecialchars($draft['enemy_team_name']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #181c24; color: #fff; }
        .card { background: #232837; }
        .team-img { width: 80px; height: 80px; border-radius: 50%; object-fit: cover; border: 3px solid #0d6efd; }
        .match-team-img { width:60px; height:60px; border-radius:50%; object-fit:cover; border:2px solid #6ea8fe33; }
        .match-team-img.enemy { border-color: #dc354544; }
        .hero-img { width:48px; height:48px; border-radius:8px; object-fit:cover; margin-right:6px; background:#232837; border:1.5px solid #6ea8fe44; }
        .badge-win { background: #198754; }
        .badge-lose { background: #dc3545; }
    </style>
</head>
<body>
<?php include 'navbar/navbar.php'; ?>
<div class="container py-5">
    <div class="card p-4 mb-4">
        <div class="d-flex align-items-center mb-3">
            <img src="<?= $draft['your_team_image'] ? htmlspecialchars($draft['your_team_image']) : 'https://via.placeholder.com/80x80?text=Team' ?>" class="team-img me-3" alt="Your Team">
            <h3 class="mb-0 text-white"><?= htmlspecialchars($draft['your_team_name']) ?></h3>
            <span class="text-secondary mx-3 fs-4">vs</span>
            <img src="<?= $draft['enemy_team_image'] ? htmlspecialchars($draft['enemy_team_image']) : 'https://via.placeholder.com/80x80?text=Team' ?>" class="team-img me-3" alt="Enemy Team">
            <h3 class="mb-0 text-white"><?= htmlspecialchars($draft['enemy_team_name']) ?></h3>
        </div>
        <div class="mb-2">
            <?php
                // Show pick order with team name for new values
                if ($draft['pick_order'] == 'your_team') {
                    $pick_order_text = 'First Pick (' . htmlspecialchars($draft['your_team_name']) . ')';
                } elseif ($draft['pick_order'] == 'enemy_team') {
                    $pick_order_text = 'First Pick (' . htmlspecialchars($draft['enemy_team_name']) . ')';
                } else {
                    $pick_order_text = ucfirst($draft['pick_order']) . ' Pick';
                }
            ?>
            <span class="badge bg-info"><?= $pick_order_text ?></span>
            <span class="badge <?= $is_win ? 'badge-win' : 'badge-lose' ?>">
                <?= $is_win ? 'WIN' : 'LOSE' ?>
            </span>
            <span class="text-secondary ms-2">
                <?= date('F j, Y \a\t g:i A', strtotime($draft['created_at'] ?? '')) ?>
            </span>
        </div>
        <?php
        // Determine which team is first pick (blue/left) and which is second pick (red/right)
        if ($draft['pick_order'] == 'your_team') {
            $left_team_name = $draft['your_team_name'];
            $left_team_img = $draft['your_team_image'];
            $left_picks = json_decode($draft['your_picks'], true);
            $left_bans = json_decode($draft['your_bans'], true);

            $right_team_name = $draft['enemy_team_name'];
            $right_team_img = $draft['enemy_team_image'];
            $right_picks = json_decode($draft['enemy_picks'], true);
            $right_bans = json_decode($draft['enemy_bans'], true);
        } else {
            $left_team_name = $draft['enemy_team_name'];
            $left_team_img = $draft['enemy_team_image'];
            $left_picks = json_decode($draft['enemy_picks'], true);
            $left_bans = json_decode($draft['enemy_bans'], true);

            $right_team_name = $draft['your_team_name'];
            $right_team_img = $draft['your_team_image'];
            $right_picks = json_decode($draft['your_picks'], true);
            $right_bans = json_decode($draft['your_bans'], true);
        }
        ?>
        <div class="row mt-4">
            <div class="col-md-6">
                <div class="d-flex align-items-center mb-2">
                    <img src="<?= $left_team_img ? htmlspecialchars($left_team_img) : 'https://via.placeholder.com/48x48?text=Team' ?>" class="me-2 rounded-circle" style="width:48px;height:48px;">
                    <h5 class="mb-0 text-primary"><?= htmlspecialchars($left_team_name) ?> (First Pick / Blue)</h5>
                </div>
                <div class="fw-bold text-info mb-1">Picks</div>
                <div class="d-flex flex-wrap align-items-center mb-3">
                    <?php foreach ($left_picks as $pick) {
                        echo hero_img_tag($pick, $hero_images) . '<span class="me-2 text-white">' . htmlspecialchars($pick) . '</span>';
                    } ?>
                </div>
                <div class="fw-bold text-warning mb-1">Bans</div>
                <div class="d-flex flex-wrap align-items-center mb-3">
                    <?php foreach ($left_bans as $ban) {
                        echo hero_img_tag($ban, $hero_images) . '<span class="me-2 text-white">' . htmlspecialchars($ban) . '</span>';
                    } ?>
                </div>
            </div>
            <div class="col-md-6">
                <div class="d-flex align-items-center mb-2">
                    <img src="<?= $right_team_img ? htmlspecialchars($right_team_img) : 'https://via.placeholder.com/48x48?text=Team' ?>" class="me-2 rounded-circle" style="width:48px;height:48px;">
                    <h5 class="mb-0 text-danger"><?= htmlspecialchars($right_team_name) ?> (Second Pick / Red)</h5>
                </div>
                <div class="fw-bold text-info mb-1">Picks</div>
                <div class="d-flex flex-wrap align-items-center mb-3">
                    <?php foreach ($right_picks as $pick) {
                        echo hero_img_tag($pick, $hero_images) . '<span class="me-2 text-white">' . htmlspecialchars($pick) . '</span>';
                    } ?>
                </div>
                <div class="fw-bold text-warning mb-1">Bans</div>
                <div class="d-flex flex-wrap align-items-center mb-3">
                    <?php foreach ($right_bans as $ban) {
                        echo hero_img_tag($ban, $hero_images) . '<span class="me-2 text-white">' . htmlspecialchars($ban) . '</span>';
                    } ?>
                </div>
            </div>
        </div>
    </div>
    <a href="view-team.php?id=<?= $team_id ? $team_id : $draft['your_team_id'] ?>" class="btn btn-secondary mt-3">Back to Matches</a>
   
    <div id="analysis-result" class="mt-4"></div>

    <form id="analyze-draft-form" class="d-inline">
        <input type="hidden" name="your_team_id" value="<?= htmlspecialchars($draft['your_team_id']) ?>">
        <input type="hidden" name="enemy_team_id" value="<?= htmlspecialchars($draft['enemy_team_id']) ?>">
        <input type="hidden" name="pick_order" value="<?= htmlspecialchars($draft['pick_order']) ?>">
        <input type="hidden" name="winner" value="<?= htmlspecialchars($draft['winner']) ?>">
        <?php
        foreach (json_decode($draft['your_picks'], true) as $pick) {
            echo '<input type="hidden" name="your_team[]" value="' . htmlspecialchars($pick) . '">';
        }
        foreach (json_decode($draft['enemy_picks'], true) as $pick) {
            echo '<input type="hidden" name="enemy_team[]" value="' . htmlspecialchars($pick) . '">';
        }
        foreach (json_decode($draft['your_bans'], true) as $ban) {
            echo '<input type="hidden" name="your_bans[]" value="' . htmlspecialchars($ban) . '">';
        }
        foreach (json_decode($draft['enemy_bans'], true) as $ban) {
            echo '<input type="hidden" name="enemy_bans[]" value="' . htmlspecialchars($ban) . '">';
        }
        ?>
        <button type="submit" class="btn btn-warning mt-3" id="analyze-btn">
            <i class="bi bi-graph-up-arrow me-1"></i> Analyze Draft
        </button>
    </form>

</div>
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script>
$('#analyze-draft-form').on('submit', function(e) {
    e.preventDefault();
    $('#analyze-btn').prop('disabled', true).text('Analyzing...');
    $('#analysis-result').html('<div class="text-center my-4"><div class="spinner-border text-info"></div><div>Analyzing draft...</div></div>');
    $.post('analyze-draft.php', $(this).serialize(), function(data) {
        $('#analysis-result').html(data);
        $('#analyze-btn').prop('disabled', false).text('Analyze Draft');
        window.scrollTo({ top: $('#analysis-result').offset().top - 60, behavior: 'smooth' });
    }).fail(function() {
        $('#analysis-result').html('<div class="alert alert-danger">Failed to analyze draft. Please try again.</div>');
        $('#analyze-btn').prop('disabled', false).text('Analyze Draft');
    });
});
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>