<style>
    .notification-wrap {
        position: relative;
    }

    .notification-button {
        width: 34px;
        height: 34px;
        border: 0;
        background: transparent;
        border-radius: 8px;
        color: #64748b;
        display: flex;
        align-items: center;
        justify-content: center;
        position: relative;
        cursor: pointer;
    }

    .notification-button:hover {
        background: #f8fafc;
    }

    .notification-button svg {
        width: 18px;
        height: 18px;
    }

    .notif-count {
        position: absolute;
        top: -1px;
        right: -3px;
        min-width: 17px;
        height: 17px;
        padding: 0 4px;
        border-radius: 20px;
        background: #dc2626;
        color: #ffffff;
        font-size: 8px;
        font-weight: 700;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .notification-panel {
        position: absolute;
        top: 43px;
        right: 0;
        width: 330px;
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        box-shadow: 0 15px 40px rgba(15, 23, 42, .14);
        display: none;
        overflow: hidden;
        z-index: 500;
    }

    .notification-panel.show {
        display: block;
    }

    .notification-header {
        padding: 13px 15px;
        border-bottom: 1px solid #eef2f7;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }

    .notification-header strong {
        font-size: 12px;
    }

    .notification-header button {
        border: 0;
        background: transparent;
        color: #2458d7;
        font-size: 10px;
        cursor: pointer;
    }

    .notification-list {
        max-height: 330px;
        overflow-y: auto;
    }

    .notification-item {
        padding: 12px 15px;
        border-bottom: 1px solid #f1f5f9;
        font-size: 10px;
        color: #475569;
        cursor: pointer;
    }

    .notification-item:hover {
        background: #f8fafc;
    }

    .notification-item.unread {
        background: #f8fbff;
    }

    .notification-empty {
        padding: 25px;
        text-align: center;
        color: #94a3b8;
        font-size: 10px;
    }
</style>

<div class="notification-wrap" data-global-notifications>
    <button type="button" class="notification-button" data-notification-button aria-label="Notifications">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
            <path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9"></path>
            <path d="M10 21h4"></path>
        </svg>

        <?php
            $unreadNotifications = 0;
            if (class_exists(\App\Models\Notification::class)) {
                try {
                    $unreadNotifications = \App\Models\Notification::whereNull('read_at')->count();
                } catch (\Throwable $e) {
                    $unreadNotifications = 0;
                }
            }
        ?>

        <?php if($unreadNotifications > 0): ?>
            <span class="notif-count"><?php echo e($unreadNotifications > 9 ? '9+' : $unreadNotifications); ?></span>
        <?php endif; ?>
    </button>

    <div class="notification-panel" data-notification-panel>
        <div class="notification-header">
            <strong>Notifications</strong>
            <?php if(Route::has('notifications.read-all')): ?>
                <form method="POST" action="<?php echo e(route('notifications.read-all')); ?>">
                    <?php echo csrf_field(); ?>
                    <button type="submit">Mark all as read</button>
                </form>
            <?php endif; ?>
        </div>

        <div class="notification-list">
            <?php if(class_exists(\App\Models\Notification::class)): ?>
                <?php
                    try {
                        $notifications = \App\Models\Notification::latest()->take(20)->get();
                    } catch (\Throwable $e) {
                        $notifications = collect();
                    }
                ?>

                <?php $__empty_1 = true; $__currentLoopData = $notifications; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $notification): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <?php if(Route::has('notifications.read')): ?>
                        <form method="POST" action="<?php echo e(route('notifications.read', $notification->id)); ?>" class="notification-item <?php echo e(empty($notification->read_at) ? 'unread' : ''); ?>">
                            <?php echo csrf_field(); ?>
                            <button type="submit" style="width:100%;border:0;background:transparent;text-align:left;padding:0;color:inherit;">
                                <?php echo e($notification->message ?? $notification->title ?? 'New notification'); ?>

                            </button>
                        </form>
                    <?php else: ?>
                        <div class="notification-item <?php echo e(empty($notification->read_at) ? 'unread' : ''); ?>">
                            <?php echo e($notification->message ?? $notification->title ?? 'New notification'); ?>

                        </div>
                    <?php endif; ?>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <div class="notification-empty">No notifications.</div>
                <?php endif; ?>
            <?php else: ?>
                <div class="notification-empty">No notifications.</div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
(function () {
    const root = document.querySelector('[data-global-notifications]');
    if (!root) return;

    const button = root.querySelector('[data-notification-button]');
    const panel = root.querySelector('[data-notification-panel]');

    button.addEventListener('click', function (event) {
        event.stopPropagation();
        panel.classList.toggle('show');
    });

    panel.addEventListener('click', function (event) {
        event.stopPropagation();
    });

    document.addEventListener('click', function () {
        panel.classList.remove('show');
    });
})();
</script>
<?php /**PATH C:\JK&C\ordodeals\resources\views/components/notifications.blade.php ENDPATH**/ ?>