<style>
    .global-deal-drawer-overlay { position: fixed; inset: 0; background: rgba(15, 23, 42, .25); opacity: 0; visibility: hidden; transition: opacity .2s ease, visibility .2s ease; z-index: 900; }
    .global-deal-drawer-overlay.show { opacity: 1; visibility: visible; }
    .global-deal-drawer { position: fixed; top: 0; right: 0; bottom: 0; width: 650px; max-width: 92vw; background: #ffffff; box-shadow: -10px 0 35px rgba(15, 23, 42, .15); transform: translateX(100%); transition: transform .25s ease; z-index: 1000; display: flex; flex-direction: column; }
    .global-deal-drawer.show { transform: translateX(0); }
    .global-deal-drawer-header { height: 94px; min-height: 94px; padding: 22px 30px; border-top: 3px solid #3f3f46; border-bottom: 1px solid #e5e7eb; display: flex; align-items: flex-start; justify-content: space-between; background: #ffffff; position: relative; }
    .global-deal-drawer-heading { min-width: 0; }
    .global-deal-drawer-title { margin: 0; font-size: 23px; font-weight: 600; color: #172033; }
    .global-deal-drawer-subtitle { margin-top: 4px; color: #64748b; font-size: 12px; }
    .global-deal-drawer-close { width: 32px; height: 32px; border: 0; background: transparent; border-radius: 7px; color: #64748b; display: flex; align-items: center; justify-content: center; cursor: pointer; }
    .global-deal-drawer-close:hover { background: #f1f5f9; color: #172033; }
    .global-deal-drawer-close svg { width: 18px; height: 18px; }
    .global-deal-drawer-body { flex: 1; min-height: 0; overflow: hidden; background: #ffffff; }
    .global-deal-drawer-frame { width: 100%; height: 100%; border: 0; display: block; background: #ffffff; }
</style>

<div class="global-deal-drawer-overlay" id="globalDealDrawerOverlay"></div>
<aside class="global-deal-drawer" id="globalDealDrawer">
    <div class="global-deal-drawer-header">
        <div class="global-deal-drawer-heading">
            <h2 class="global-deal-drawer-title" data-deal-drawer-title>Create Deal</h2>
            <div class="global-deal-drawer-subtitle">Select an existing client, then complete the consulting and deal form.</div>
        </div>
        <button type="button" class="global-deal-drawer-close" id="globalDealDrawerClose" aria-label="Close drawer">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                <path d="M6 6l12 12"></path>
                <path d="M18 6L6 18"></path>
            </svg>
        </button>
    </div>
    <div class="global-deal-drawer-body">
        <iframe id="globalDealDrawerFrame" class="global-deal-drawer-frame" src="about:blank" title="Deal Form"></iframe>
    </div>
</aside>

<script>
(function () {
    const drawer = document.getElementById('globalDealDrawer');
    const overlay = document.getElementById('globalDealDrawerOverlay');
    const frame = document.getElementById('globalDealDrawerFrame');
    const closeButton = document.getElementById('globalDealDrawerClose');
    const title = document.querySelector('[data-deal-drawer-title]');
    const returnTo = <?php echo json_encode($returnTo ?? url()->current(), 15, 512) ?>;

    function closeDealDrawer() {
        drawer.classList.remove('show');
        overlay.classList.remove('show');
        document.body.style.overflow = '';
        window.setTimeout(function () {
            if (!drawer.classList.contains('show')) frame.src = 'about:blank';
        }, 250);
    }

    window.openDealDrawer = function (mode, dealId) {
        const query = mode === 'edit'
            ? '?deal=' + encodeURIComponent(dealId) + '&drawer=1'
            : '?drawer=1';
        frame.src = '<?php echo e(route('deals.create')); ?>' + query + '&return_to=' + encodeURIComponent(returnTo);
        title.textContent = mode === 'edit' ? 'Edit Deal' : 'Create Deal';
        drawer.classList.add('show');
        overlay.classList.add('show');
        document.body.style.overflow = 'hidden';
    };

    closeButton.addEventListener('click', closeDealDrawer);
    overlay.addEventListener('click', closeDealDrawer);
    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && drawer.classList.contains('show')) closeDealDrawer();
    });

    frame.addEventListener('load', function () {
        if (!drawer.classList.contains('show')) return;
        try {
            if (frame.contentWindow.location.href !== 'about:blank' && !frame.contentWindow.location.href.includes('/deals/create')) {
                closeDealDrawer();
                window.setTimeout(function () { window.location.reload(); }, 200);
            }
        } catch (error) {}
    });
})();
</script>
<?php /**PATH C:\JKC\ordodeals\resources\views/components/deal-drawer.blade.php ENDPATH**/ ?>