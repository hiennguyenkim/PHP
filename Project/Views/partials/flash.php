<?php
declare(strict_types=1);

$flashSuccess = $flashSuccess ?? null;
$flashError = $flashError ?? null;
?>
<?php if (!empty($flashSuccess)): ?>
    <div class="alert alert-success">
        <?= htmlspecialchars((string) $flashSuccess, ENT_QUOTES, 'UTF-8') ?>
    </div>
<?php endif; ?>

<?php if (!empty($flashError)): ?>
    <div class="alert alert-error">
        <?= htmlspecialchars((string) $flashError, ENT_QUOTES, 'UTF-8') ?>
    </div>
<?php endif; ?>
