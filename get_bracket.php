<?php
header('Content-Type: application/json');

$jsonFile = 'data.json';
$tournament_id = (string)($_GET['tournament_id'] ?? "1");

if (!file_exists($jsonFile)) {
    echo json_encode(['success' => true, 'players' => []]);
    exit;
}

$data = json_decode(file_get_contents($jsonFile), true);

if (!isset($data['tournaments'][$tournament_id])) {
    echo json_encode(['success' => false, 'message' => 'Tournament not found']);
    exit;
}

$tournament = $data['tournaments'][$tournament_id];
$registrations = $tournament['registrations'] ?? [];
$players = [];

foreach ($registrations as $slot => $player_id) {
    if (isset($data['players'][$player_id])) {
        $pInfo = $data['players'][$player_id];
        $players[] = [
            'slot' => (int)$slot,
            'name' => $pInfo['name'],
            'avatar' => $pInfo['avatar'],
            'steamid64' => $player_id
        ];
    }
}

// Sort by slot to ensure consistency
usort($players, function($a, $b) {
    return $a['slot'] <=> $b['slot'];
});

echo json_encode(['success' => true, 'players' => $players]);
?>
