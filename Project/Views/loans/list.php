<?php
declare(strict_types=1);

$isAdmin = $isAdmin ?? false;
$summary = $summary ?? [];
$loans = $loans ?? [];
$filters = $filters ?? [];
$users = $users ?? [];
$books = $books ?? [];
$statusLabels = [
    'pending' => 'Cho duyet',
    'borrowed' => 'Dang muon',
    'overdue' => 'Qua han',
    'returned' => 'Da tra',
    'cancelled' => 'Da huy',
];
?>
<section class="hero-surface">
    <div>
        <p class="eyebrow"><?= $isAdmin ? 'Muon tra' : 'Ban doc' ?></p>
        <h1><?= $isAdmin ? 'Quan ly phieu muon sach' : 'Phieu muon cua toi' ?></h1>
        <p class="section-copy">
            <?= $isAdmin
                ? 'Duyet yeu cau muon, ghi nhan tra sach va theo doi toan bo vong doi muon tra trong thu vien.'
                : 'Theo doi yeu cau muon sach, han tra va lich su giao dich muon tra cua rieng ban.' ?>
        </p>
    </div>
    <div class="hero-actions">
        <a class="button button-primary" href="index.php?url=loan/create">
            <?= $isAdmin ? 'Lap phieu muon' : 'Gui yeu cau muon sach' ?>
        </a>
    </div>
</section>

<section class="metric-grid metric-grid-compact">
    <article class="metric-card metric-card-blue"><span>Cho duyet</span><strong><?= (int) ($summary['pending'] ?? 0) ?></strong></article>
    <article class="metric-card metric-card-amber"><span>Dang muon</span><strong><?= (int) ($summary['borrowed'] ?? 0) ?></strong></article>
    <article class="metric-card metric-card-red"><span>Qua han</span><strong><?= (int) ($summary['overdue'] ?? 0) ?></strong></article>
    <article class="metric-card metric-card-teal">
        <span><?= $isAdmin ? 'Da tra hom nay' : 'Da tra' ?></span>
        <strong><?= (int) ($isAdmin ? ($summary['returned_today'] ?? 0) : ($summary['returned_total'] ?? 0)) ?></strong>
    </article>
</section>

<section class="panel">
    <form method="get" action="index.php" class="filter-grid filter-grid-wide">
        <input type="hidden" name="url" value="loan/index">

        <label class="field">
            <span>Tim kiem</span>
            <input type="text" name="search" value="<?= htmlspecialchars((string) ($filters['search'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" placeholder="<?= $isAdmin ? 'Thanh vien hoac ten sach' : 'Ten sach can tim' ?>">
        </label>

        <label class="field">
            <span>Trang thai</span>
            <select name="status">
                <option value="">Tat ca trang thai</option>
                <?php foreach ($statusLabels as $status => $label): ?>
                    <option value="<?= htmlspecialchars($status, ENT_QUOTES, 'UTF-8') ?>"<?= ($filters['status'] ?? '') === $status ? ' selected' : '' ?>>
                        <?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label>

        <?php if ($isAdmin): ?>
            <label class="field">
                <span>Thanh vien</span>
                <select name="user_id">
                    <option value="">Tat ca thanh vien</option>
                    <?php foreach ($users as $user): ?>
                        <option value="<?= (int) $user['id'] ?>"<?= (string) ($filters['user_id'] ?? '') === (string) $user['id'] ? ' selected' : '' ?>>
                            <?= htmlspecialchars((string) $user['full_name'], ENT_QUOTES, 'UTF-8') ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>
        <?php endif; ?>

        <label class="field">
            <span>Sach</span>
            <select name="book_id">
                <option value="">Tat ca sach</option>
                <?php foreach ($books as $book): ?>
                    <option value="<?= (int) $book['id'] ?>"<?= (string) ($filters['book_id'] ?? '') === (string) $book['id'] ? ' selected' : '' ?>>
                        <?= htmlspecialchars((string) $book['title'], ENT_QUOTES, 'UTF-8') ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label>

        <div class="filter-actions">
            <button type="submit" class="button button-primary">Loc du lieu</button>
            <a class="button button-secondary" href="index.php?url=loan/index">Dat lai</a>
        </div>
    </form>
</section>

<section class="panel">
    <div class="table-wrapper">
        <table class="data-table">
            <thead>
                <tr>
                    <?php if ($isAdmin): ?>
                        <th>Thanh vien</th>
                    <?php endif; ?>
                    <th>Sach</th>
                    <th>Ngay muon</th>
                    <th>Han tra</th>
                    <th>Ngay tra</th>
                    <th>Trang thai</th>
                    <th>Thao tac</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($loans === []): ?>
                    <tr>
                        <td colspan="<?= $isAdmin ? '7' : '6' ?>" class="table-empty">Chua co phieu muon nao phu hop.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($loans as $loan): ?>
                        <?php $status = (string) ($loan['display_status'] ?? $loan['status']); ?>
                        <tr>
                            <?php if ($isAdmin): ?>
                                <td>
                                    <div class="cell-title"><?= htmlspecialchars((string) $loan['full_name'], ENT_QUOTES, 'UTF-8') ?></div>
                                    <small class="cell-subtitle">@<?= htmlspecialchars((string) $loan['username'], ENT_QUOTES, 'UTF-8') ?></small>
                                </td>
                            <?php endif; ?>
                            <td>
                                <div class="cell-title"><?= htmlspecialchars((string) $loan['book_title'], ENT_QUOTES, 'UTF-8') ?></div>
                                <small class="cell-subtitle"><?= htmlspecialchars((string) ($loan['book_author'] ?? ''), ENT_QUOTES, 'UTF-8') ?></small>
                            </td>
                            <td><?= htmlspecialchars(date('d/m/Y', strtotime((string) $loan['borrow_date'])), ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars(date('d/m/Y', strtotime((string) $loan['due_date'])), ENT_QUOTES, 'UTF-8') ?></td>
                            <td>
                                <?= !empty($loan['returned_date'])
                                    ? htmlspecialchars(date('d/m/Y', strtotime((string) $loan['returned_date'])), ENT_QUOTES, 'UTF-8')
                                    : '<span class="cell-subtitle">Chua tra</span>' ?>
                            </td>
                            <td><span class="status-pill status-pill-<?= htmlspecialchars($status, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($statusLabels[$status] ?? $status, ENT_QUOTES, 'UTF-8') ?></span></td>
                            <td class="actions">
                                <?php if ($isAdmin): ?>
                                    <a class="button button-secondary button-small" href="index.php?url=loan/edit/<?= (int) $loan['id'] ?>">Sua</a>
                                    <?php if ($status === 'pending'): ?>
                                        <form method="post" action="index.php?url=loan/approve/<?= (int) $loan['id'] ?>" class="inline-form" data-confirm="Duyet yeu cau muon sach nay?">
                                            <button type="submit" class="button button-primary button-small">Duyet</button>
                                        </form>
                                        <form method="post" action="index.php?url=loan/cancel/<?= (int) $loan['id'] ?>" class="inline-form" data-confirm="Huy yeu cau muon sach nay?">
                                            <button type="submit" class="button button-danger button-small">Huy</button>
                                        </form>
                                    <?php endif; ?>
                                    <?php if (in_array($status, ['borrowed', 'overdue'], true)): ?>
                                        <form method="post" action="index.php?url=loan/mark-return/<?= (int) $loan['id'] ?>" class="inline-form" data-confirm="Xac nhan sach da duoc tra?">
                                            <button type="submit" class="button button-primary button-small">Tra sach</button>
                                        </form>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <?php if ($status === 'pending'): ?>
                                        <form method="post" action="index.php?url=loan/cancel/<?= (int) $loan['id'] ?>" class="inline-form" data-confirm="Huy yeu cau muon sach nay?">
                                            <button type="submit" class="button button-danger button-small">Huy yeu cau</button>
                                        </form>
                                    <?php else: ?>
                                        <span class="cell-subtitle">Khong co</span>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
