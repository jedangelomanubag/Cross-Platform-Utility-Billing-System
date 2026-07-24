<?php
// personnel/input_meter.php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../utils/response.php';
require_once __DIR__ . '/../utils/auth_guard.php';

$reader = require_reader_auth();
$input = json_input();

$meterId = isset($input['MeterID']) ? (int)$input['MeterID'] : null;
$currentReading = isset($input['CurrentReading']) ? (float)$input['CurrentReading'] : null;
$notes = isset($input['Notes']) ? $input['Notes'] : null;

if (!$meterId || $currentReading === null) res_error('MeterID and CurrentReading required', 400);

// Fetch last reading from meter table
$stmt = $mysqli->prepare('SELECT LastReading FROM meter WHERE MeterID = ? LIMIT 1');
$stmt->bind_param('i', $meterId);
$stmt->execute();
$result = $stmt->get_result();
$meter = $result->fetch_assoc();
$stmt->close();

if (!$meter) res_error('Meter not found', 404);
$previousReading = (float)$meter['LastReading'];
$consumption = round($currentReading - $previousReading, 2);
if ($consumption < 0) res_error('Current reading cannot be less than previous reading', 400);

// Insert into meterreadingdata (pending)
$readingDate = date('Y-m-d H:i:s');
$status = 'pending';
$stmt = $mysqli->prepare('INSERT INTO meterreadingdata (MeterID, ReaderID, PreviousReading, CurrentReading, Consumption, ReadingDate, Notes, Status) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
if (!$stmt) res_error('Prepare failed: ' . $mysqli->error, 500);
$stmt->bind_param('iidddsss', $meterId, $reader['ReaderID'], $previousReading, $currentReading, $consumption, $readingDate, $notes, $status);
$ok = $stmt->execute();
if (!$ok) res_error('Insert failed: ' . $stmt->error, 500);
$readingId = $stmt->insert_id;
$stmt->close();

// We keep meter.LastReading unchanged until billing/storing is approved/stored.

res_ok(['ReadingID' => $readingId, 'Consumption' => $consumption], 'Reading stored (pending approval)');
?>
