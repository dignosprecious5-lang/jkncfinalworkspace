{{-- ORDO UNIFIED TOOLTIP SYSTEM --}}
<style>
    .ordo-floating-tooltip {
        position: fixed;
        z-index: 99999999;
        max-width: 290px;
        min-width: 80px;
        padding: 7px 11px;
        font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
        font-size: 12px;
        font-weight: 500;
        line-height: 1.45;
        color: #ffffff;
        background: #0f172a;
        border: 1px solid rgba(255, 255, 255, 0.12);
        border-radius: 7px;
        box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.4), 0 8px 10px -6px rgba(0, 0, 0, 0.25);
        pointer-events: none;
        opacity: 0;
        transform: translateY(3px) scale(0.97);
        transition: opacity 0.16s cubic-bezier(0.16, 1, 0.3, 1), transform 0.16s cubic-bezier(0.16, 1, 0.3, 1);
        word-wrap: break-word;
        text-align: left;
        letter-spacing: 0.01em;
        box-sizing: border-box;
    }
    .ordo-floating-tooltip.visible {
        opacity: 1;
        transform: translateY(0) scale(1);
    }
    .ordo-floating-tooltip .tooltip-arrow {
        position: absolute;
        width: 8px;
        height: 8px;
        background: #0f172a;
        border: 1px solid rgba(255, 255, 255, 0.12);
        transform: rotate(45deg);
        pointer-events: none;
    }
    .ordo-floating-tooltip[data-placement="top"] .tooltip-arrow {
        bottom: -5px;
        left: 50%;
        margin-left: -4px;
        border-top: none;
        border-left: none;
    }
    .ordo-floating-tooltip[data-placement="bottom"] .tooltip-arrow {
        top: -5px;
        left: 50%;
        margin-left: -4px;
        border-right: none;
        border-bottom: none;
    }
    .ordo-floating-tooltip[data-placement="left"] .tooltip-arrow {
        right: -5px;
        top: 50%;
        margin-top: -4px;
        border-bottom: none;
        border-left: none;
    }
    .ordo-floating-tooltip[data-placement="right"] .tooltip-arrow {
        left: -5px;
        top: 50%;
        margin-top: -4px;
        border-top: none;
        border-right: none;
    }
    .ordo-tooltip-icon {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 15px;
        height: 15px;
        margin-left: 5px;
        font-size: 10px;
        font-weight: 700;
        font-family: serif, 'Times New Roman', Georgia, -apple-system, BlinkMacSystemFont, sans-serif;
        font-style: italic;
        color: #64748b;
        background: #f1f5f9;
        border: 1px solid #cbd5e1;
        border-radius: 50%;
        cursor: pointer;
        vertical-align: middle;
        line-height: 1;
        transition: all 0.15s ease;
        text-decoration: none !important;
        user-select: none;
    }
    .ordo-tooltip-icon:hover, .ordo-tooltip-icon:focus {
        color: #2563eb;
        background: #eff6ff;
        border-color: #93c5fd;
        outline: none;
    }
</style>

<script>
(function() {
    if (window.__ORDO_TOOLTIP_INITIALIZED__) return;
    window.__ORDO_TOOLTIP_INITIALIZED__ = true;

    let tooltipEl = null;
    let arrowEl = null;
    let currentTarget = null;
    let hideTimeout = null;

    function createTooltipElement() {
        if (tooltipEl) return tooltipEl;
        tooltipEl = document.createElement('div');
        tooltipEl.className = 'ordo-floating-tooltip';
        tooltipEl.setAttribute('role', 'tooltip');
        tooltipEl.setAttribute('aria-hidden', 'true');

        arrowEl = document.createElement('div');
        arrowEl.className = 'tooltip-arrow';
        tooltipEl.appendChild(arrowEl);

        document.body.appendChild(tooltipEl);
        return tooltipEl;
    }

    function showTooltip(el) {
        const text = el.getAttribute('data-tooltip') || el.getAttribute('data-ordo-tooltip');
        if (!text || !text.trim()) return;

        createTooltipElement();
        clearTimeout(hideTimeout);
        currentTarget = el;

        // Set text
        tooltipEl.childNodes[0]?.nodeType === 3 
            ? tooltipEl.childNodes[0].textContent = text 
            : tooltipEl.insertBefore(document.createTextNode(text), arrowEl);

        tooltipEl.setAttribute('aria-hidden', 'false');
        tooltipEl.style.display = 'block';

        // Position calculation
        const preferredPlacement = el.getAttribute('data-tooltip-pos') || 'top';
        positionTooltip(el, preferredPlacement);

        requestAnimationFrame(() => {
            if (currentTarget === el) {
                tooltipEl.classList.add('visible');
            }
        });
    }

    function positionTooltip(target, preferredPlacement) {
        const rect = target.getBoundingClientRect();
        const tooltipRect = tooltipEl.getBoundingClientRect();
        const gap = 8;
        const pad = 12;

        let placement = preferredPlacement;
        let top = 0;
        let left = 0;

        // Determine if flipping is necessary
        if (placement === 'top' && rect.top - tooltipRect.height - gap < pad) {
            placement = 'bottom';
        } else if (placement === 'bottom' && rect.bottom + tooltipRect.height + gap > window.innerHeight - pad) {
            placement = 'top';
        } else if (placement === 'left' && rect.left - tooltipRect.width - gap < pad) {
            placement = 'right';
        } else if (placement === 'right' && rect.right + tooltipRect.width + gap > window.innerWidth - pad) {
            placement = 'left';
        }

        tooltipEl.setAttribute('data-placement', placement);

        if (placement === 'top') {
            top = rect.top - tooltipRect.height - gap;
            left = rect.left + (rect.width / 2) - (tooltipRect.width / 2);
        } else if (placement === 'bottom') {
            top = rect.bottom + gap;
            left = rect.left + (rect.width / 2) - (tooltipRect.width / 2);
        } else if (placement === 'left') {
            top = rect.top + (rect.height / 2) - (tooltipRect.height / 2);
            left = rect.left - tooltipRect.width - gap;
        } else if (placement === 'right') {
            top = rect.top + (rect.height / 2) - (tooltipRect.height / 2);
            left = rect.right + gap;
        }

        // Keep within viewport horizontally
        if (left < pad) {
            left = pad;
        } else if (left + tooltipRect.width > window.innerWidth - pad) {
            left = window.innerWidth - tooltipRect.width - pad;
        }

        // Keep within viewport vertically
        if (top < pad) {
            top = pad;
        } else if (top + tooltipRect.height > window.innerHeight - pad) {
            top = window.innerHeight - tooltipRect.height - pad;
        }

        tooltipEl.style.top = `${Math.round(top)}px`;
        tooltipEl.style.left = `${Math.round(left)}px`;

        // Adjust arrow position to center with trigger
        if (placement === 'top' || placement === 'bottom') {
            const targetCenter = rect.left + (rect.width / 2);
            const arrowLeft = Math.max(10, Math.min(tooltipRect.width - 10, targetCenter - left));
            arrowEl.style.left = `${Math.round(arrowLeft)}px`;
            arrowEl.style.top = '';
        } else {
            const targetCenter = rect.top + (rect.height / 2);
            const arrowTop = Math.max(8, Math.min(tooltipRect.height - 8, targetCenter - top));
            arrowEl.style.top = `${Math.round(arrowTop)}px`;
            arrowEl.style.left = '';
        }
    }

    function hideTooltip() {
        if (!tooltipEl) return;
        currentTarget = null;
        tooltipEl.classList.remove('visible');
        tooltipEl.setAttribute('aria-hidden', 'true');
        hideTimeout = setTimeout(() => {
            if (!currentTarget && tooltipEl) {
                tooltipEl.style.display = 'none';
            }
        }, 180);
    }

    // Event delegation
    document.addEventListener('pointerenter', function(e) {
        const trigger = e.target.closest && e.target.closest('[data-tooltip], [data-ordo-tooltip]');
        if (trigger) {
            showTooltip(trigger);
        }
    }, true);

    document.addEventListener('pointerleave', function(e) {
        const trigger = e.target.closest && e.target.closest('[data-tooltip], [data-ordo-tooltip]');
        if (trigger && currentTarget === trigger) {
            hideTooltip();
        }
    }, true);

    document.addEventListener('focusin', function(e) {
        const trigger = e.target.closest && e.target.closest('[data-tooltip], [data-ordo-tooltip]');
        if (trigger) {
            showTooltip(trigger);
        }
    }, true);

    document.addEventListener('focusout', function(e) {
        const trigger = e.target.closest && e.target.closest('[data-tooltip], [data-ordo-tooltip]');
        if (trigger && currentTarget === trigger) {
            hideTooltip();
        }
    }, true);

    document.addEventListener('scroll', function() {
        if (currentTarget) {
            positionTooltip(currentTarget, currentTarget.getAttribute('data-tooltip-pos') || 'top');
        }
    }, { passive: true, capture: true });

    window.addEventListener('resize', function() {
        if (currentTarget) {
            positionTooltip(currentTarget, currentTarget.getAttribute('data-tooltip-pos') || 'top');
        }
    }, { passive: true });
})();
</script>
