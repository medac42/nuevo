<?php
header('Content-Type: application/json');

$jsonFile = 'data.json';

// Get POST data
$data = json_decode(file_get_contents('php://input'), true);

if (!$data) {
    echo json_encode(['success' => false, 'message' => 'No data received']);
    exit;
}

$steamid = $data['steamid'] ?? '';
$name = $data['name'] ?? '';
$avatar = $data['avatar'] ?? '';
$tournament_id = (string)($data['tournament_id'] ?? "1");

if (empty($steamid) || empty($name)) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

// Load current data with file locking
$fileHandle = fopen($jsonFile, 'c+');
if (!$fileHandle) {
    echo json_encode(['success' => false, 'message' => 'Could not open data file']);
    exit;
}

flock($fileHandle, LOCK_EX);
$filesize = filesize($jsonFile);
$currentData = $filesize > 0 ? json_decode(fread($fileHandle, $filesize), true) : ['players' => [], 'tournaments' => []];

// 1. Save/Update player
$currentData['players'][$steamid] = [
    'name' => $name,
    'avatar' => $avatar,
    'last_seen' => date('Y-m-d H:i:s')
];

// 2. Check tournament existence
if (!isset($currentData['tournaments'][$tournament_id])) {
    echo json_encode(['success' => false, 'message' => 'Tournament not found']);
    flock($fileHandle, LOCK_UN);
    fclose($fileHandle);
    exit;
}

$tournament = &$currentData['tournaments'][$tournament_id];
if (!isset($tournament['registrations'])) {
    $tournament['registrations'] = [];
}

// 3. Check if already registered
$existingSlot = null;
foreach ($tournament['registrations'] as $slot => $registeredId) {
    if ($registeredId === $steamid) {
        $existingSlot = $slot;
        break;
    }
}

if ($existingSlot !== null) {
    echo json_encode(['success' => true, 'message' => 'Already registered', 'slot' => (int)$existingSlot]);
    flock($fileHandle, LOCK_UN);
    fclose($fileHandle);
    exit;
}

// 4. Find available slots
$max_players = $tournament['max_players'] ?? 32;
$taken_slots = array_keys($tournament['registrations']);
$all_slots = range(0, $max_players - 1);
$available_slots = array_diff($all_slots, $taken_slots);

if (empty($available_slots)) {
    echo json_encode(['success' => false, 'message' => 'Tournament is full']);
    flock($fileHandle, LOCK_UN);
    fclose($fileHandle);
    exit;
}

// 5. Randomly assign to a slot
shuffle($available_slots);
$assigned_slot = array_pop($available_slots);

// 6. Register
$tournament['registrations'][(string)$assigned_slot] = $steamid;

// Save back to file
ftruncate($fileHandle, 0);
rewind($fileHandle);
fwrite($fileHandle, json_encode($currentData, JSON_PRETTY_PRINT));
fflush($fileHandle);
flock($fileHandle, LOCK_UN);
fclose($fileHandle);

echo json_encode(['success' => true, 'message' => 'Registration successful', 'slot' => $assigned_slot]);
?>
