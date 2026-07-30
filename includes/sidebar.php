<?php
/**
 * Renders the app sidebar + topbar opening markup.
 * Expects $activeNav (string key) and $pageTitle / $pageSubtitle to be set
 * by the including page before requiring this file.
 */
$user = currentUser();
$role = $user['role'];

$navItems = [
    'admin' => [
        ['key' => 'dashboard',   'label' => 'Dashboard',        'href' => 'dashboard.php',           'ico' => '📊'],
        ['key' => 'users',       'label' => 'Manage Users',     'href' => 'manage_users.php',        'ico' => '👥'],
        ['key' => 'instruments', 'label' => 'Manage Instruments','href' => 'manage_instruments.php',  'ico' => '🎻'],
        ['key' => 'analytics',   'label' => 'Analytics',        'href' => 'analytics.php',           'ico' => '📈'],
        ['key' => 'reports',     'label' => 'Reports',          'href' => 'reports.php',             'ico' => '📄'],
    ],
    'instructor' => [
        ['key' => 'dashboard',   'label' => 'Dashboard',        'href' => 'dashboard.php',    'ico' => '📊'],
        ['key' => 'students',    'label' => 'My Students',      'href' => 'students.php',     'ico' => '🎓'],
        ['key' => 'logs',        'label' => 'Practice Logs',    'href' => 'logs.php',         'ico' => '🎼'],
        ['key' => 'leaderboard', 'label' => 'Leaderboard',      'href' => 'leaderboard.php',  'ico' => '🏆'],
        ['key' => 'assignments', 'label' => 'Assignments',      'href' => 'assignments.php',  'ico' => '📝'],
        ['key' => 'feedback',    'label' => 'Feedback Sent',    'href' => 'feedback.php',     'ico' => '💬'],
    ],
    'student' => [
        ['key' => 'dashboard',    'label' => 'Dashboard',        'href' => 'dashboard.php',     'ico' => '📊'],
        ['key' => 'sessions',     'label' => 'Practice Sessions','href' => 'sessions.php',      'ico' => '🎹'],
        ['key' => 'goals',        'label' => 'Goals & Streaks',  'href' => 'goals.php',         'ico' => '🎯'],
        ['key' => 'achievements', 'label' => 'Achievements',     'href' => 'achievements.php',  'ico' => '🏆'],
        ['key' => 'feedback',     'label' => 'Feedback & Tasks', 'href' => 'feedback.php',      'ico' => '💬'],
    ],
];

$roleLabels = ['admin' => 'Administrator', 'instructor' => 'Instructor', 'student' => 'Student'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>RiyazHub</title>
<link rel="icon" type="image/png"
    href="<?= basePath() ?>/assets/images/favicon.png">

    <link rel="stylesheet"
          href="<?= basePath() ?>/assets/css/style.css">
</head>
<body>
<div class="app-shell">
  <aside class="sidebar" id="sidebar">
    <div class="brand">
      <img src="../assets/images/RiyazHubLogo.png" alt="RiyazHub Logo" class="logo-mark">
      <span>RiyazHub</span>
    </div>
    <div class="role-badge role-<?= h($role) ?>"><?= h($roleLabels[$role]) ?></div>
    <nav class="nav-group">
      <?php foreach ($navItems[$role] as $item): ?>
        <a class="nav-link <?= ($activeNav ?? '') === $item['key'] ? 'active' : '' ?>" href="<?= h($item['href']) ?>">
          <span class="nav-ico"><?= $item['ico'] ?></span> <?= h($item['label']) ?>
        </a>
      <?php endforeach; ?>
    </nav>
    <div class="sidebar-footer">
      <div class="user-chip">
        <div class="avatar" style="background: <?= h($user['avatar_color']) ?>"><?= h(strtoupper(substr($user['full_name'],0,1))) ?></div>
        <div>
          <div class="name"><?= h($user['full_name']) ?></div>
          <div class="role"><?= h($user['email']) ?></div>
        </div>
      </div>
      <a class="logout-link" href="<?= basePath() ?>/auth/logout.php">↩ Log out</a>
    </div>
  </aside>

  <div class="main-col">
    <header class="topbar">
      <div class="flex items-center gap-12">
        <button class="menu-toggle" onclick="document.getElementById('sidebar').classList.toggle('open')">☰</button>
        <div>
          <h1><?= h($pageTitle ?? '') ?></h1>
          <?php if (!empty($pageSubtitle)): ?><p class="subtitle"><?= h($pageSubtitle) ?></p><?php endif; ?>
        </div>
      </div>
      <div class="eq-bars"><span></span><span></span><span></span><span></span><span></span></div>
    </header>
    <main class="content">
    <?php $flash = getFlash(); if ($flash): ?>
      <div class="alert alert-<?= h($flash['type']) ?>"><?= h($flash['message']) ?></div>
    <?php endif; ?>
