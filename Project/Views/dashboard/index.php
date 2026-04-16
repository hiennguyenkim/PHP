<?php
declare(strict_types=1);

$isAdmin = $isAdmin ?? false;
$stats = $stats ?? [];
$loanSummary = $loanSummary ?? [];
$bookSummary = $bookSummary ?? [];
$userSummary = $userSummary ?? [];
$recentLoans = $recentLoans ?? [];
$lowStockBooks = $lowStockBooks ?? [];
$recentMembers = $recentMembers ?? [];
$availableBooks = $availableBooks ?? [];
$memberName = $memberName ?? 'Ban doc';
$statusLabels = [
    'pending' => 'Cho duyet',
    'borrowed' => 'Dang muon',
    'overdue' => 'Qua han',
    'returned' => 'Da tra',
    'cancelled' => 'Da huy',
];
?>
<?php if ($isAdmin): ?>
    <section class="hero-surface">
        <div>
            <p class="eyebrow">Thu vien tong quan</p>
            <h1>Bang dieu khien quan ly thu vien</h1>
            <p class="section-copy">
                Theo doi nhanh tinh trang kho sach, thanh vien moi va cac luot muon tra dang dien ra trong ngay.
            </p>
        </div>
        <div class="hero-actions">
            <a class="button button-primary" href="index.php?url=book/create">Them sach</a>
            <a class="button button-secondary" href="index.php?url=loan/create">Lap phieu muon</a>
        </div>
    </section>

    <section class="metric-grid">
        <?php foreach ($stats as $stat): ?>
            <article class="metric-card metric-card-<?= htmlspecialchars((string) $stat['tone'], ENT_QUOTES, 'UTF-8') ?>">
                <span><?= htmlspecialchars((string) $stat['label'], ENT_QUOTES, 'UTF-8') ?></span>
                <strong><?= (int) $stat['value'] ?></strong>
                <small><?= htmlspecialchars((string) $stat['hint'], ENT_QUOTES, 'UTF-8') ?></small>
            </article>
        <?php endforeach; ?>
    </section>

    <section class="panel-grid panel-grid-wide">
        <article class="panel">
            <div class="section-head">
                <div>
                    <p class="eyebrow">Phieu muon moi nhat</p>
                    <h2>Hoat dong muon tra gan day</h2>
                </div>
                <a class="text-link" href="index.php?url=loan/index">Xem tat ca</a>
            </div>

            <div class="table-wrapper">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Thanh vien</th>
                            <th>Sach</th>
                            <th>Ngay muon</th>
                            <th>Han tra</th>
                            <th>Trang thai</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($recentLoans === []): ?>
                            <tr>
                                <td colspan="5" class="table-empty">Chua co giao dich muon tra nao.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($recentLoans as $loan): ?>
                                <?php $displayStatus = (string) ($loan['display_status'] ?? $loan['status'] ?? 'pending'); ?>
                                <tr>
                                    <td>
                                        <div class="cell-title"><?= htmlspecialchars((string) $loan['full_name'], ENT_QUOTES, 'UTF-8') ?></div>
                                    </td>
                                    <td><?= htmlspecialchars((string) $loan['book_title'], ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><?= htmlspecialchars(date('d/m/Y', strtotime((string) $loan['borrow_date'])), ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><?= htmlspecialchars(date('d/m/Y', strtotime((string) $loan['due_date'])), ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><span class="status-pill status-pill-<?= $displayStatus ?>"><?= htmlspecialchars($statusLabels[$displayStatus] ?? $displayStatus, ENT_QUOTES, 'UTF-8') ?></span></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </article>

        <div class="stack-panels">
            <article class="panel compact-panel">
                <div class="section-head">
                    <div>
                        <p class="eyebrow">Tong hop muon tra</p>
                        <h2>Chi so nhanh</h2>
                    </div>
                </div>
                <div class="list-stack">
                    <div class="mini-stat"><span>Cho duyet</span><strong><?= (int) ($loanSummary['pending'] ?? 0) ?></strong></div>
                    <div class="mini-stat"><span>Dang muon</span><strong><?= (int) ($loanSummary['borrowed'] ?? 0) ?></strong></div>
                    <div class="mini-stat"><span>Qua han</span><strong><?= (int) ($loanSummary['overdue'] ?? 0) ?></strong></div>
                    <div class="mini-stat"><span>Da tra hom nay</span><strong><?= (int) ($loanSummary['returned_today'] ?? 0) ?></strong></div>
                </div>
            </article>

            <article class="panel compact-panel">
                <div class="section-head">
                    <div>
                        <p class="eyebrow">Can chu y</p>
                        <h2>Sach sap het</h2>
                    </div>
                </div>
                <div class="list-stack">
                    <?php if ($lowStockBooks === []): ?>
                        <p class="empty-copy">Tat ca sach hien dang on dinh ton kho.</p>
                    <?php else: ?>
                        <?php foreach ($lowStockBooks as $book): ?>
                            <div class="list-row">
                                <div>
                                    <strong><?= htmlspecialchars((string) $book['title'], ENT_QUOTES, 'UTF-8') ?></strong>
                                    <small><?= htmlspecialchars((string) $book['author'], ENT_QUOTES, 'UTF-8') ?></small>
                                </div>
                                <span class="status-pill status-pill-<?= htmlspecialchars((string) $book['status'], ENT_QUOTES, 'UTF-8') ?>">
                                    <?= (int) $book['available_quantity'] ?> con
                                </span>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </article>

            <article class="panel compact-panel">
                <div class="section-head">
                    <div>
                        <p class="eyebrow">Thanh vien moi</p>
                        <h2>Hoat dong ban doc</h2>
                    </div>
                    <a class="text-link" href="index.php?url=user/index">Quan ly</a>
                </div>
                <div class="list-stack">
                    <?php foreach ($recentMembers as $member): ?>
                        <div class="list-row">
                            <div>
                                <strong><?= htmlspecialchars((string) $member['full_name'], ENT_QUOTES, 'UTF-8') ?></strong>
                                <small><?= htmlspecialchars((string) $member['username'], ENT_QUOTES, 'UTF-8') ?></small>
                            </div>
                            <span class="badge"><?= htmlspecialchars((string) strtoupper((string) $member['role']), ENT_QUOTES, 'UTF-8') ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </article>
        </div>
    </section>
<?php else: ?>
    <section class="hero-surface">
        <div>
            <p class="eyebrow">Khong gian ban doc</p>
            <h1>Xin chao, <?= htmlspecialchars((string) $memberName, ENT_QUOTES, 'UTF-8') ?></h1>
            <p class="section-copy">
                Theo doi cac yeu cau muon, sach dang giu va lich su tra sach cua rieng ban trong thu vien.
            </p>
        </div>
        <div class="hero-actions">
            <a class="button button-primary" href="index.php?url=loan/create">Gui yeu cau muon sach</a>
            <a class="button button-secondary" href="index.php?url=loan/index">Xem tat ca phieu muon</a>
        </div>
    </section>

    <section class="metric-grid">
        <?php foreach ($stats as $stat): ?>
            <article class="metric-card metric-card-<?= htmlspecialchars((string) $stat['tone'], ENT_QUOTES, 'UTF-8') ?>">
                <span><?= htmlspecialchars((string) $stat['label'], ENT_QUOTES, 'UTF-8') ?></span>
                <strong><?= (int) $stat['value'] ?></strong>
                <small><?= htmlspecialchars((string) $stat['hint'], ENT_QUOTES, 'UTF-8') ?></small>
            </article>
        <?php endforeach; ?>
    </section>

    <section class="panel-grid panel-grid-wide">
        <article class="panel">
            <div class="section-head">
                <div>
                    <p class="eyebrow">Phieu muon cua toi</p>
                    <h2>Lich su gan day</h2>
                </div>
                <a class="text-link" href="index.php?url=loan/index">Xem chi tiet</a>
            </div>

            <div class="table-wrapper">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Sach</th>
                            <th>Ngay muon</th>
                            <th>Han tra</th>
                            <th>Trang thai</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($recentLoans === []): ?>
                            <tr>
                                <td colspan="4" class="table-empty">Ban chua co phieu muon nao.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($recentLoans as $loan): ?>
                                <?php $displayStatus = (string) ($loan['display_status'] ?? $loan['status'] ?? 'pending'); ?>
                                <tr>
                                    <td>
                                        <div class="cell-title"><?= htmlspecialchars((string) $loan['book_title'], ENT_QUOTES, 'UTF-8') ?></div>
                                        <small class="cell-subtitle"><?= htmlspecialchars((string) ($loan['book_author'] ?? ''), ENT_QUOTES, 'UTF-8') ?></small>
                                    </td>
                                    <td><?= htmlspecialchars(date('d/m/Y', strtotime((string) $loan['borrow_date'])), ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><?= htmlspecialchars(date('d/m/Y', strtotime((string) $loan['due_date'])), ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><span class="status-pill status-pill-<?= $displayStatus ?>"><?= htmlspecialchars($statusLabels[$displayStatus] ?? $displayStatus, ENT_QUOTES, 'UTF-8') ?></span></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </article>

        <div class="stack-panels">
            <article class="panel compact-panel">
                <div class="section-head">
                    <div>
                        <p class="eyebrow">Tong hop ca nhan</p>
                        <h2>Chi so muon sach</h2>
                    </div>
                </div>
                <div class="list-stack">
                    <div class="mini-stat"><span>Tong phieu</span><strong><?= (int) ($loanSummary['total_loans'] ?? 0) ?></strong></div>
                    <div class="mini-stat"><span>Cho duyet</span><strong><?= (int) ($loanSummary['pending'] ?? 0) ?></strong></div>
                    <div class="mini-stat"><span>Da tra</span><strong><?= (int) ($loanSummary['returned_total'] ?? 0) ?></strong></div>
                    <div class="mini-stat"><span>Da huy</span><strong><?= (int) ($loanSummary['cancelled'] ?? 0) ?></strong></div>
                </div>
            </article>

            <article class="panel compact-panel">
                <div class="section-head">
                    <div>
                        <p class="eyebrow">Sach san sang</p>
                        <h2>Co the gui yeu cau ngay</h2>
                    </div>
                    <a class="text-link" href="index.php?url=loan/create">Muon sach</a>
                </div>
                <div class="list-stack">
                    <?php if ($availableBooks === []): ?>
                        <p class="empty-copy">Hien chua co dau sach kha dung de gui yeu cau.</p>
                    <?php else: ?>
                        <?php foreach ($availableBooks as $book): ?>
                            <div class="list-row">
                                <div>
                                    <strong><?= htmlspecialchars((string) $book['title'], ENT_QUOTES, 'UTF-8') ?></strong>
                                    <small><?= htmlspecialchars((string) $book['author'], ENT_QUOTES, 'UTF-8') ?></small>
                                </div>
                                <span class="status-pill status-pill-<?= htmlspecialchars((string) $book['status'], ENT_QUOTES, 'UTF-8') ?>">
                                    <?= (int) $book['available_quantity'] ?> con
                                </span>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </article>
        </div>
    </section>
<?php endif; ?>
