<?php
require_once __DIR__ . '/../includes/auth.php';
requireRole('admin');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verifyCsrf($_POST['csrf_token'] ?? '')) {
    redirect('manage_instruments.php');
}

$db = getDB();
$action = $_POST['action'] ?? '';

if ($action === 'create') {
    $name = trim($_POST['name'] ?? '');
    $icon = trim($_POST['icon'] ?? '🎵') ?: '🎵';
    $desc = trim($_POST['description'] ?? '');
    if ($name === '') {
        setFlash('error', 'Instrument name is required.');
        redirect('manage_instruments.php');
    }
    $db->prepare('INSERT INTO instruments (name, icon, description) VALUES (?, ?, ?)')->execute([$name, $icon, $desc]);
    setFlash('success', 'Instrument added.');
}

if ($action === 'update') {
    $id = (int) ($_POST['id'] ?? 0);
    $name = trim($_POST['name'] ?? '');
    $icon = trim($_POST['icon'] ?? '🎵') ?: '🎵';
    $desc = trim($_POST['description'] ?? '');
    $db->prepare('UPDATE instruments SET name=?, icon=?, description=? WHERE id=?')->execute([$name, $icon, $desc, $id]);
    setFlash('success', 'Instrument updated.');
}

if ($action === 'delete') {
    $id = (int) ($_POST['id'] ?? 0);
    $db->prepare('UPDATE users SET instrument_id = NULL WHERE instrument_id = ?')->execute([$id]);
    $db->prepare('UPDATE practice_sessions SET instrument_id = NULL WHERE instrument_id = ?')->execute([$id]);
    $db->prepare('DELETE FROM instruments WHERE id = ?')->execute([$id]);
    setFlash('success', 'Instrument deleted.');
}

redirect('manage_instruments.php');
