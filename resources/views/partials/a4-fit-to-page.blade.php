<style>
    @media print {
        @page {
            size: A4 portrait;
            margin: 8mm;
        }

        html,
        body {
            margin: 0 !important;
            width: auto;
            height: auto;
            overflow: hidden;
            background: #fff !important;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        .no-print {
            display: none !important;
        }

        .a4-fit-shell {
            margin: 0 !important;
            padding: 0 !important;
            width: 194mm !important;
            height: 281mm !important;
            max-width: none !important;
            overflow: hidden !important;
        }

        .a4-fit-page {
            position: relative !important;
            width: 194mm !important;
            height: 281mm !important;
            overflow: hidden !important;
            box-shadow: none !important;
            background: #fff !important;
        }

        .a4-fit-content {
            position: absolute !important;
            top: 0;
            left: 0;
            margin: 0 !important;
            max-width: none !important;
            transform-origin: top left;
        }
    }
</style>

<script>
    (function () {
        const printableWidthMm = 194;
        const printableHeightMm = 281;
        const originalStyles = new WeakMap();

        function pxPerMillimeter() {
            const probe = document.createElement('div');
            probe.style.position = 'absolute';
            probe.style.left = '-9999px';
            probe.style.top = '0';
            probe.style.width = '100mm';
            probe.style.height = '1mm';
            document.body.appendChild(probe);
            const value = probe.getBoundingClientRect().width / 100;
            probe.remove();

            return value || 3.7795275591;
        }

        function rememberStyle(element) {
            if (originalStyles.has(element)) {
                return;
            }

            originalStyles.set(element, {
                left: element.style.left,
                top: element.style.top,
                width: element.style.width,
                height: element.style.height,
                maxWidth: element.style.maxWidth,
                minHeight: element.style.minHeight,
                transform: element.style.transform,
                transformOrigin: element.style.transformOrigin,
            });
        }

        function restoreStyle(element) {
            const original = originalStyles.get(element);

            if (!original) {
                return;
            }

            element.style.left = original.left;
            element.style.top = original.top;
            element.style.width = original.width;
            element.style.height = original.height;
            element.style.maxWidth = original.maxWidth;
            element.style.minHeight = original.minHeight;
            element.style.transform = original.transform;
            element.style.transformOrigin = original.transformOrigin;
            originalStyles.delete(element);
        }

        window.prepareA4FitToPage = function () {
            const page = document.querySelector('.a4-fit-page');
            const content = document.querySelector('.a4-fit-content');

            if (!page || !content) {
                return 1;
            }

            const pxPerMm = pxPerMillimeter();
            const pageWidth = printableWidthMm * pxPerMm;
            const pageHeight = printableHeightMm * pxPerMm;
            const configuredInset = Number(page.dataset.a4FitInsetMm || content.dataset.a4FitInsetMm || 1.5);
            const fitInset = Math.max(configuredInset, 0) * pxPerMm;
            const fitWidth = pageWidth - (fitInset * 2);
            const fitHeight = pageHeight - (fitInset * 2);
            const safetyScale = Number(page.dataset.a4FitSafetyScale || content.dataset.a4FitSafetyScale || 1);
            const shouldFillPage = page.dataset.a4FitFillPage === 'true' || content.dataset.a4FitFillPage === 'true';

            rememberStyle(page);
            rememberStyle(content);

            page.style.width = `${pageWidth}px`;
            page.style.height = `${pageHeight}px`;

            content.style.width = `${pageWidth}px`;
            content.style.height = 'auto';
            content.style.left = '0';
            content.style.top = '0';
            content.style.maxWidth = 'none';
            content.style.transform = 'none';
            content.style.transformOrigin = 'top left';

            const rect = content.getBoundingClientRect();
            const contentWidth = Math.max(content.scrollWidth, rect.width, 1);
            const contentHeight = Math.max(content.scrollHeight, rect.height, 1);
            const scale = Math.min(fitWidth / contentWidth, fitHeight / contentHeight) * safetyScale;
            const resolvedScale = Number.isFinite(scale) && scale > 0 ? scale : 1;
            const resolvedContentHeight = shouldFillPage
                ? Math.max(contentHeight, fitHeight / resolvedScale)
                : contentHeight;
            const offsetX = Math.max((pageWidth - (contentWidth * resolvedScale)) / 2, 0);
            const offsetY = shouldFillPage
                ? fitInset
                : Math.max((pageHeight - (resolvedContentHeight * resolvedScale)) / 2, 0);

            content.style.left = `${offsetX}px`;
            content.style.top = `${offsetY}px`;
            content.style.transform = `scale(${resolvedScale})`;
            content.style.height = `${resolvedContentHeight}px`;
            content.style.minHeight = `${resolvedContentHeight}px`;
            content.dataset.a4FitScale = resolvedScale.toFixed(5);

            return resolvedScale;
        };

        window.resetA4FitToPage = function () {
            const page = document.querySelector('.a4-fit-page');
            const content = document.querySelector('.a4-fit-content');

            if (content) {
                delete content.dataset.a4FitScale;
                restoreStyle(content);
            }

            if (page) {
                restoreStyle(page);
            }
        };

        window.printA4FitPage = function () {
            requestAnimationFrame(function () {
                window.prepareA4FitToPage();
                requestAnimationFrame(function () {
                    window.print();
                });
            });
        };

        window.addEventListener('beforeprint', window.prepareA4FitToPage);
        window.addEventListener('afterprint', window.resetA4FitToPage);
    })();
</script>
