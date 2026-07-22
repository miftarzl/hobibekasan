<?php
// Include file controller
include('notif_controller.php');
?>

<!-- Tombol Notifikasi -->
<button id="notifButton" class="btn-notif">
    <i class="fas fa-bell"></i>
    <span class="badge" id="notifCount"><?= $totalPending ?></span>
</button>

<!-- Popup Notifikasi -->
<div class="notif-popup" id="notifPopup">
    <h4>Notifikasi Pembelian</h4>
    <ul id="notifList">
        <?php if (!empty($notifications)): ?>
            <?php foreach ($notifications as $notif): ?>
                <li class="notif-item">
                    <i class="fas fa-shopping-cart"></i>
                    <p><strong><?= $notif['customer_name'] ?></strong> membeli dengan total Rp<?= number_format($notif['total_price'], 0, ',', '.') ?> pada <?= date("d M Y, H:i", strtotime($notif['created_at'])) ?></p>
                </li>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="notif-empty">Tidak ada transaksi baru</div>
        <?php endif; ?>
    </ul>
</div>
