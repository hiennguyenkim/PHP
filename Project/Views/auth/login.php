<?php
declare(strict_types=1);

$errors = $errors ?? [];
$old = $old ?? [];
?>
<section class="auth-layout">
    <article class="hero-card">
        <p class="eyebrow">Thu vien MVC</p>
        <h1>Dang nhap vao khu quan tri thu vien</h1>
        <p>
            Dang nhap de quan ly dau sach, thanh vien va cac phieu muon tra trong cung mot he thong MVC.
        </p>
        <ul class="feature-list">
            <li>Dashboard tong hop sach, thanh vien, luot muon va qua han.</li>
            <li>Module sach va phieu muon su dung prepared statement.</li>
            <li>Session bao ve toan bo khu quan tri sau khi dang nhap.</li>
        </ul>
    </article>

    <article class="panel auth-panel">
        <div class="section-head compact">
            <div>
                <p class="eyebrow">Authentication</p>
                <h2>Dang nhap thu thu / thanh vien</h2>
            </div>
            <a class="text-link" href="index.php?url=auth/register">Dang ky thanh vien</a>
        </div>

        <form method="post" action="index.php?url=auth/login" class="stack-form">
            <?php if (!empty($errors['general'])): ?>
                <div class="inline-error">
                    <?= htmlspecialchars((string) $errors['general'], ENT_QUOTES, 'UTF-8') ?>
                </div>
            <?php endif; ?>

            <label class="field">
                <span>Ten dang nhap</span>
                <input
                    type="text"
                    name="username"
                    value="<?= htmlspecialchars((string) ($old['username'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                    placeholder="nhap username"
                >
                <?php if (!empty($errors['username'])): ?>
                    <small class="field-error"><?= htmlspecialchars((string) $errors['username'], ENT_QUOTES, 'UTF-8') ?></small>
                <?php endif; ?>
            </label>

            <label class="field">
                <span>Mat khau</span>
                <input type="password" name="password" placeholder="nhap mat khau">
                <?php if (!empty($errors['password'])): ?>
                    <small class="field-error"><?= htmlspecialchars((string) $errors['password'], ENT_QUOTES, 'UTF-8') ?></small>
                <?php endif; ?>
            </label>

            <button type="submit" class="button button-primary full-width">Dang nhap</button>
        </form>

        <p class="helper-copy">Tai khoan mau sau khi nap schema: <code>admin / admin123</code></p>
    </article>
</section>
