<?php
declare(strict_types=1);

$mode = $mode ?? 'create';
$form = $form ?? [];
$errors = $errors ?? [];
$loanHistory = $loanHistory ?? [];
$isEdit = $mode === 'edit';
$action = $isEdit
    ? 'user/update/' . (int) ($form['id'] ?? 0)
    : 'user/store';
?>
<section class="panel form-panel">
    <div class="section-head">
        <div>
            <p class="eyebrow"><?= $isEdit ? 'Update' : 'Create' ?></p>
            <h1><?= $isEdit ? 'Cap nhat thong tin nguoi dung' : 'Them nguoi dung moi' ?></h1>
        </div>
        <a class="text-link" href="index.php?url=user/index">Quay lai danh sach</a>
    </div>

    <form method="post" action="index.php?url=<?= htmlspecialchars($action, ENT_QUOTES, 'UTF-8') ?>" class="form-grid">
        <label class="field">
            <span>Ho va ten</span>
            <input
                type="text"
                name="full_name"
                value="<?= htmlspecialchars((string) ($form['full_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                placeholder="Nguyen Van An"
            >
            <?php if (!empty($errors['full_name'])): ?>
                <small class="field-error"><?= htmlspecialchars((string) $errors['full_name'], ENT_QUOTES, 'UTF-8') ?></small>
            <?php endif; ?>
        </label>

        <label class="field">
            <span>Ten dang nhap</span>
            <input
                type="text"
                name="username"
                value="<?= htmlspecialchars((string) ($form['username'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                placeholder="vi du: nguyenvana"
            >
            <?php if (!empty($errors['username'])): ?>
                <small class="field-error"><?= htmlspecialchars((string) $errors['username'], ENT_QUOTES, 'UTF-8') ?></small>
            <?php endif; ?>
        </label>

        <label class="field">
            <span>Email</span>
            <input
                type="email"
                name="email"
                value="<?= htmlspecialchars((string) ($form['email'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                placeholder="vi du: nguyenvana@example.com"
            >
            <?php if (!empty($errors['email'])): ?>
                <small class="field-error"><?= htmlspecialchars((string) $errors['email'], ENT_QUOTES, 'UTF-8') ?></small>
            <?php endif; ?>
        </label>

        <label class="field">
            <span>Vai tro</span>
            <select name="role">
                <option value="member"<?= ($form['role'] ?? 'member') === 'member' ? ' selected' : '' ?>>Member</option>
                <option value="admin"<?= ($form['role'] ?? '') === 'admin' ? ' selected' : '' ?>>Admin</option>
            </select>
            <?php if (!empty($errors['role'])): ?>
                <small class="field-error"><?= htmlspecialchars((string) $errors['role'], ENT_QUOTES, 'UTF-8') ?></small>
            <?php endif; ?>
        </label>

        <label class="field">
            <span>Trang thai</span>
            <select name="status">
                <option value="active"<?= ($form['status'] ?? 'active') === 'active' ? ' selected' : '' ?>>Hoat dong</option>
                <option value="inactive"<?= ($form['status'] ?? '') === 'inactive' ? ' selected' : '' ?>>Tam khoa</option>
            </select>
            <?php if (!empty($errors['status'])): ?>
                <small class="field-error"><?= htmlspecialchars((string) $errors['status'], ENT_QUOTES, 'UTF-8') ?></small>
            <?php endif; ?>
        </label>

        <label class="field">
            <span><?= $isEdit ? 'Mat khau moi' : 'Mat khau' ?></span>
            <input type="password" name="password" placeholder="<?= $isEdit ? 'de trong neu khong doi' : 'toi thieu 6 ky tu' ?>">
            <?php if (!empty($errors['password'])): ?>
                <small class="field-error"><?= htmlspecialchars((string) $errors['password'], ENT_QUOTES, 'UTF-8') ?></small>
            <?php endif; ?>
        </label>

        <label class="field">
            <span>Nhap lai mat khau</span>
            <input type="password" name="confirm_password" placeholder="nhap lai mat khau">
            <?php if (!empty($errors['confirm_password'])): ?>
                <small class="field-error"><?= htmlspecialchars((string) $errors['confirm_password'], ENT_QUOTES, 'UTF-8') ?></small>
            <?php endif; ?>
        </label>

        <div class="form-note">
            <?= $isEdit
                ? 'Neu khong muon thay doi mat khau, hay de trong hai truong mat khau.'
                : 'Tai khoan moi co the duoc gan vai tro admin hoac member ngay khi tao.' ?>
        </div>

        <?php if ($isEdit): ?>
            <div class="info-card">
                <strong>Lich su muon gan day</strong>
                <?php if ($loanHistory === []): ?>
                    <p>Thanh vien nay chua co phieu muon nao.</p>
                <?php else: ?>
                    <?php
                    $statusLabels = [
                        'pending' => 'Cho duyet',
                        'borrowed' => 'Dang muon',
                        'overdue' => 'Qua han',
                        'returned' => 'Da tra',
                        'cancelled' => 'Da huy',
                    ];
                    ?>
                    <div class="list-stack">
                        <?php foreach ($loanHistory as $loan): ?>
                            <?php $status = (string) ($loan['display_status'] ?? $loan['status']); ?>
                            <div class="list-row">
                                <div>
                                    <strong><?= htmlspecialchars((string) $loan['book_title'], ENT_QUOTES, 'UTF-8') ?></strong>
                                    <small><?= htmlspecialchars(date('d/m/Y', strtotime((string) $loan['borrow_date'])), ENT_QUOTES, 'UTF-8') ?></small>
                                </div>
                                <span class="status-pill status-pill-<?= htmlspecialchars($status, ENT_QUOTES, 'UTF-8') ?>">
                                    <?= htmlspecialchars($statusLabels[$status] ?? $status, ENT_QUOTES, 'UTF-8') ?>
                                </span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <div class="form-actions">
            <button type="submit" class="button button-primary"><?= $isEdit ? 'Luu thay doi' : 'Tao tai khoan' ?></button>
            <a class="button button-secondary" href="index.php?url=user/index">Huy</a>
        </div>
    </form>
</section>
