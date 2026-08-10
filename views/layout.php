<?php
/** @var string $page */
/** @var array $pageMeta */
/** @var array|null $flash */
$role = user()['role'];
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#175c45">
    <title><?= e($pageMeta['title']) ?> — Rumah Tahfidz As-Sakinah</title>
    <link rel="icon" href="assets/images/logo.jpeg" type="image/jpeg">
    <link rel="stylesheet" href="assets/app.css?v=20260811f">
    <link rel="stylesheet" href="assets/components.css?v=20260811zf">
    <link rel="stylesheet" href="assets/halaqoh-tools.css?v=20260811e">
    <link rel="stylesheet" href="assets/guardian-dashboard.css?v=20260811a">
</head>
<body>
    <div class="app-shell">
        <?php include __DIR__ . '/partials/sidebar.php'; ?>

        <section class="workspace">
            <?php include __DIR__ . '/partials/topbar.php'; ?>

            <main class="content">
                <?php if ($flash): ?>
                    <div class="alert <?= e($flash['type']) ?>" role="alert">
                        <?= e($flash['message']) ?>
                    </div>
                <?php endif; ?>

                <?php include __DIR__ . '/pages/' . $pageMeta['template'] . '.php'; ?>
            </main>
        </section>
    </div>

    <?php include __DIR__ . '/partials/detail_modal.php'; ?>
    <?php include __DIR__ . '/partials/confirm_modal.php'; ?>
    <div class="sidebar-overlay" onclick="document.body.classList.remove('menu-open')"></div>
    <script src="assets/app.js?v=20260811x"></script>
</body>
</html>
