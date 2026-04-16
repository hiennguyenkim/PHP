<?php
declare(strict_types=1);

$pageTitle = $title ?? 'Ung dung MVC';
$currentUser = $currentUser ?? null;
$currentRoute = trim((string) ($_GET['url'] ?? ($currentUser ? 'dashboard/index' : 'auth/login')), '/');
$activeSection = explode('/', $currentRoute)[0] ?: 'dashboard';
$isAdmin = (string) ($currentUser['role'] ?? '') === 'admin';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') ?></title>
    <link rel="stylesheet" href="Public/css/app.css">
</head>
<body>
    <div class="page-shell">
        <header class="topbar">
            <div class="topbar-inner">
                <a class="brand" href="index.php?url=home/index">
                    <span class="brand-mark">LIB</span>
                    <span class="brand-copy">
                        <strong>HCMUE Library</strong>
                        <small>He thong quan ly thu vien theo mo hinh MVC</small>
                    </span>
                </a>

                <nav class="nav-links<?= $currentUser !== null ? ' nav-links-authenticated' : '' ?>">
                    <?php if ($currentUser !== null): ?>
                        <a class="<?= $activeSection === 'dashboard' ? 'active' : '' ?>" href="index.php?url=dashboard/index">Tong quan</a>
                        <?php if ($isAdmin): ?>
                            <a class="<?= $activeSection === 'book' ? 'active' : '' ?>" href="index.php?url=book/index">Sach</a>
                            <a class="<?= $activeSection === 'loan' ? 'active' : '' ?>" href="index.php?url=loan/index">Muon tra</a>
                            <a class="<?= $activeSection === 'user' ? 'active' : '' ?>" href="index.php?url=user/index">Thanh vien</a>
                        <?php else: ?>
                            <a class="<?= $activeSection === 'loan' ? 'active' : '' ?>" href="index.php?url=loan/index">Phieu muon cua toi</a>
                        <?php endif; ?>
                        <div class="user-chip">
                            <strong><?= htmlspecialchars((string) ($currentUser['full_name'] ?? $currentUser['username'] ?? 'Nguoi dung'), ENT_QUOTES, 'UTF-8') ?></strong>
                            <small><?= htmlspecialchars(strtoupper((string) ($currentUser['role'] ?? 'member')), ENT_QUOTES, 'UTF-8') ?></small>
                        </div>
                        <a href="index.php?url=auth/logout">Dang xuat</a>
                    <?php else: ?>
                        <a class="<?= $activeSection === 'auth' && str_contains($currentRoute, 'login') ? 'active' : '' ?>" href="index.php?url=auth/login">Dang nhap</a>
                        <a class="<?= $activeSection === 'auth' && str_contains($currentRoute, 'register') ? 'active' : '' ?>" href="index.php?url=auth/register">Dang ky</a>
                    <?php endif; ?>
                </nav>
            </div>
        </header>

        <main class="app-container">
            <?php include BASE_PATH . '/Views/partials/flash.php'; ?>
            <?= $content ?>
        </main>

        <footer class="site-footer">
            <p>He thong quan ly thu vien tach biet Controller, Model va View. Toan bo nghiep vu sach va muon tra duoc xu ly trong lop Model.</p>
        </footer>
    </div>

    <script src="Public/js/app.js"></script>
</body>
</html>
