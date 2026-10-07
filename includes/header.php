<?php
if (!isset($page_title)) $page_title = 'Academic Clearance System';
$flash = get_flash();
$user = current_user();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($page_title) ?> | Academic Clearance</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
</head>
<body>
<div class="app-wrapper">
    <?php if (is_logged_in()): ?>
    <aside class="sidebar">
        <div class="sidebar-brand">
            <h2>ACS</h2>
            <p>Academic Clearance</p>
        </div>
        <nav class="sidebar-nav">
            <?php include __DIR__ . '/sidebar.php'; ?>
        </nav>
        <div class="sidebar-footer">
            <p><?= e($user['name']) ?></p>
            <small><?= e(ucfirst($user['role'])) ?></small>
        </div>
    </aside>
    <?php endif; ?>

    <main class="main-content <?= !is_logged_in() ? 'full-width' : '' ?>">
        <?php if (is_logged_in()): ?>
        <header class="topbar">
            <button class="menu-toggle" id="menuToggle">☰</button>
            <h1><?= e($page_title) ?></h1>
            <div class="topbar-right">
                <a href="<?= BASE_URL ?>/auth/logout.php" class="btn btn-sm btn-outline">Logout</a>
            </div>
        </header>
        <?php endif; ?>

        <div class="content-area">
            <?php if ($flash): ?>
            <div class="alert alert-<?= e($flash['type']) ?>">
                <?= e($flash['message']) ?>
                <button class="alert-close">&times;</button>
            </div>
            <?php endif; ?>
