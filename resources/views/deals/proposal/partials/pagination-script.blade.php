<script>
(() => {
    const PAGE_SELECTOR = '.proposal-page.proposal-inner-page';
    const BODY_SELECTOR = '.proposal-page-body';
    const CONTINUATION_CLASS = 'proposal-js-continuation';

    const isOverflowing = (body) => body.scrollHeight > body.clientHeight + 2;

    const continuedHeading = (heading) => heading ? heading.cloneNode(true) : null;

    const previousContextHeading = (table) => {
        let sibling = table.previousElementSibling;

        while (sibling) {
            if (/^H[3-6]$/i.test(sibling.tagName)) {
                return sibling.cloneNode(true);
            }

            if (/^H2$/i.test(sibling.tagName)) {
                return null;
            }

            sibling = sibling.previousElementSibling;
        }

        return null;
    };

    const buildContinuationPage = (sourcePage, sourceTable) => {
        const page = sourcePage.cloneNode(false);
        page.classList.add(CONTINUATION_CLASS);

        const pageNumber = sourcePage.querySelector('.proposal-page-number');
        if (pageNumber) {
            page.appendChild(pageNumber.cloneNode(true));
        }

        const body = document.createElement('div');
        body.className = 'proposal-page-body';

        const pageHeading = continuedHeading(sourcePage.querySelector(`${BODY_SELECTOR} > .proposal-section-heading`));
        if (pageHeading) {
            body.appendChild(pageHeading);
        }

        const contextHeading = previousContextHeading(sourceTable);
        if (contextHeading) {
            body.appendChild(contextHeading);
        }

        const table = sourceTable.cloneNode(false);
        const thead = sourceTable.querySelector('thead');
        if (thead) {
            table.appendChild(thead.cloneNode(true));
        }

        const tbody = document.createElement('tbody');
        table.appendChild(tbody);
        body.appendChild(table);
        page.appendChild(body);

        const footer = sourcePage.querySelector('.proposal-page-footer');
        if (footer) {
            page.appendChild(footer.cloneNode(true));
        }

        return { page, tbody };
    };

    const buildBlockContinuationPage = (sourcePage) => {
        const page = sourcePage.cloneNode(false);
        page.classList.add(CONTINUATION_CLASS);

        const pageNumber = sourcePage.querySelector('.proposal-page-number');
        if (pageNumber) {
            page.appendChild(pageNumber.cloneNode(true));
        }

        const body = document.createElement('div');
        body.className = 'proposal-page-body';

        const pageHeading = continuedHeading(sourcePage.querySelector(`${BODY_SELECTOR} > .proposal-section-heading`));
        if (pageHeading) {
            body.appendChild(pageHeading);
        }

        page.appendChild(body);

        const footer = sourcePage.querySelector('.proposal-page-footer');
        if (footer) {
            page.appendChild(footer.cloneNode(true));
        }

        return { page, body };
    };

    const escapeHtml = (value) => String(value)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');

    const lineParts = (cell) => (cell.innerText || '')
        .replace(/\r/g, '')
        .split('\n')
        .map((line) => line.trim())
        .filter((line) => line !== '');

    const renderLines = (cell, lines) => {
        cell.innerHTML = lines.map(escapeHtml).join('<br>');
    };

    const splitSingleTallRow = (body, sourceTable, continuationBody) => {
        const sourceRow = sourceTable.querySelector('tbody tr');
        if (!sourceRow) {
            return 0;
        }

        const sourceCells = Array.from(sourceRow.children);
        const sourceLines = sourceCells.map(lineParts);

        if (!sourceLines.some((lines) => lines.length > 1)) {
            return 0;
        }

        const continuationRow = sourceRow.cloneNode(false);
        const continuationLines = sourceCells.map(() => []);

        sourceCells.forEach((cell) => {
            const clone = cell.cloneNode(false);
            clone.innerHTML = '';
            continuationRow.appendChild(clone);
        });

        continuationBody.insertBefore(continuationRow, continuationBody.firstChild);

        let moved = 0;
        while (isOverflowing(body)) {
            let splitIndex = -1;
            let splitLength = 1;

            sourceLines.forEach((lines, index) => {
                if (lines.length > splitLength) {
                    splitIndex = index;
                    splitLength = lines.length;
                }
            });

            if (splitIndex === -1) {
                break;
            }

            continuationLines[splitIndex].unshift(sourceLines[splitIndex].pop());
            renderLines(sourceCells[splitIndex], sourceLines[splitIndex]);
            renderLines(continuationRow.children[splitIndex], continuationLines[splitIndex]);
            moved += 1;
        }

        if (moved === 0) {
            continuationRow.remove();
        }

        return moved;
    };

    const renumberPages = (root) => {
        const pages = Array.from(root.querySelectorAll(PAGE_SELECTOR));
        const total = pages.length;

        pages.forEach((page, index) => {
            const number = page.querySelector('.proposal-page-number');
            if (number) {
                number.textContent = `Page ${index + 1} of ${total}`;
            }
        });
    };

    const paginateTables = (root = document) => {
        const doc = root.querySelector ? (root.querySelector('.proposal-doc') || root) : document;
        doc.querySelectorAll(`.${CONTINUATION_CLASS}`).forEach((page) => {
            const sourcePage = page.previousElementSibling;
            const sourceTable = page.querySelector(`${BODY_SELECTOR} table`);
            const sourceRows = sourceTable ? Array.from(sourceTable.querySelectorAll('tbody tr')) : [];
            const sourceBlocks = sourceTable ? [] : Array.from(page.querySelectorAll(`${BODY_SELECTOR} .proposal-term-block`));

            if (sourcePage && sourceRows.length) {
                const targetTables = Array.from(sourcePage.querySelectorAll(`${BODY_SELECTOR} table`));
                const targetTable = targetTables.reverse().find((table) => table.className === sourceTable.className) || targetTables[0];
                const targetBody = targetTable?.querySelector('tbody');

                if (targetBody) {
                    sourceRows.forEach((row) => targetBody.appendChild(row));
                }
            }

            if (sourcePage && sourceBlocks.length) {
                const targetBody = sourcePage.querySelector(BODY_SELECTOR);
                if (targetBody) {
                    sourceBlocks.forEach((block) => targetBody.appendChild(block));
                }
            }

            page.remove();
        });

        const maxPasses = 80;
        let pass = 0;

        while (pass < maxPasses) {
            pass += 1;
            const overflowingPage = Array.from(doc.querySelectorAll(PAGE_SELECTOR)).find((page) => {
                const body = page.querySelector(BODY_SELECTOR);
                return body && isOverflowing(body);
            });

            if (!overflowingPage) {
                break;
            }

            const body = overflowingPage.querySelector(BODY_SELECTOR);
            const table = Array.from(body.querySelectorAll('table')).reverse().find((candidate) => {
                return candidate.querySelectorAll('tbody tr').length > 0;
            });
            const termBlocks = Array.from(body.querySelectorAll('.proposal-term-block'));

            if (!table && termBlocks.length <= 1) {
                overflowingPage.classList.add('proposal-unresolved-overflow');
                break;
            }

            let moved = 0;

            if (table) {
                const sourceBody = table.querySelector('tbody');
                const { page: continuationPage, tbody: continuationBody } = buildContinuationPage(overflowingPage, table);
                overflowingPage.insertAdjacentElement('afterend', continuationPage);

                if (sourceBody.rows.length > 1) {
                    while (sourceBody.rows.length > 1 && isOverflowing(body)) {
                        continuationBody.insertBefore(sourceBody.rows[sourceBody.rows.length - 1], continuationBody.firstChild);
                        moved += 1;
                    }
                } else {
                    moved = splitSingleTallRow(body, table, continuationBody);
                }

                if (moved === 0) {
                    continuationPage.remove();
                    overflowingPage.classList.add('proposal-unresolved-overflow');
                    break;
                }
            } else {
                const { page: continuationPage, body: continuationBody } = buildBlockContinuationPage(overflowingPage);
                overflowingPage.insertAdjacentElement('afterend', continuationPage);

                while (body.querySelectorAll('.proposal-term-block').length > 1 && isOverflowing(body)) {
                    const blocks = body.querySelectorAll('.proposal-term-block');
                    continuationBody.insertBefore(blocks[blocks.length - 1], continuationBody.querySelector('.proposal-term-block'));
                    moved += 1;
                }

                if (moved === 0) {
                    continuationPage.remove();
                    overflowingPage.classList.add('proposal-unresolved-overflow');
                    break;
                }
            }
        }

        renumberPages(doc);
    };

    window.paginateProposalTables = paginateTables;

    const run = () => paginateTables(document);

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', run, { once: true });
    } else {
        run();
    }

    window.addEventListener('load', run, { once: true });
    window.addEventListener('beforeprint', run);
})();
</script>
