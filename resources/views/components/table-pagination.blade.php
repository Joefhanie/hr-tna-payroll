@props([
    'target',
    'itemsPerPage' => 10,
    'maxPages' => 5
])

<div id="pagination-{{ $target }}" class="js-table-pagination flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between border-t border-slate-200/80 bg-white px-4 py-3 hidden" data-target="{{ $target }}" data-items-per-page="{{ $itemsPerPage }}" data-max-pages="{{ $maxPages }}">
    <nav class="flex items-center self-end sm:self-auto select-none" aria-label="Pagination">
        <button type="button" class="btn-prev inline-flex h-9 w-9 items-center justify-center rounded-lg border border-slate-100 bg-white text-slate-500 transition hover:bg-slate-100 hover:text-slate-900 disabled:opacity-50 disabled:pointer-events-none" title="Previous Page">
            <i class="ti ti-chevron-left text-base font-semibold"></i>
        </button>
        <div class="page-buttons flex items-center">
            <!-- Dynamic page buttons -->
        </div>
        <button type="button" class="btn-next inline-flex h-9 w-9 items-center justify-center rounded-lg border border-slate-100 bg-white text-slate-500 transition hover:bg-slate-100 hover:text-slate-900 disabled:opacity-50 disabled:pointer-events-none" title="Next Page">
            <i class="ti ti-chevron-right text-base font-semibold"></i>
        </button>
    </nav>
    <div class="text-xs text-slate-500 font-medium select-none">
        Showing <span class="font-semibold text-slate-900 pagination-start">0</span> out of <span class="font-semibold text-slate-900 pagination-total">0</span> entries
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const paginatorEl = document.getElementById('pagination-{{ $target }}');
    if (!paginatorEl) return;

    const targetId = paginatorEl.getAttribute('data-target');
    const itemsPerPage = parseInt(paginatorEl.getAttribute('data-items-per-page')) || 10;
    const maxPages = parseInt(paginatorEl.getAttribute('data-max-pages')) || 5;

    const table = document.getElementById(targetId);
    if (!table) {
        console.error('Pagination target not found: ' + targetId);
        return;
    }

    const tbody = table.querySelector('tbody');
    if (!tbody) return;

    const btnPrev = paginatorEl.querySelector('.btn-prev');
    const btnNext = paginatorEl.querySelector('.btn-next');
    const pageButtonsContainer = paginatorEl.querySelector('.page-buttons');
    const txtStart = paginatorEl.querySelector('.pagination-start');
    const txtTotal = paginatorEl.querySelector('.pagination-total');

    let currentPage = 1;
    let isUpdating = false;

    function getFilteredRows() {
        return Array.from(tbody.querySelectorAll(':scope > tr')).filter(row => {
            if (row.id === 'noResultsRow' || row.id === 'emptyRow') return false;
            if (row.querySelector('td[colspan]')) return false;
            return row.getAttribute('data-filter-hidden') !== 'true';
        });
    }

    function update() {
        if (isUpdating) return;
        isUpdating = true;

        const filteredRows = getFilteredRows();
        const totalItems = filteredRows.length;
        const totalPages = Math.ceil(totalItems / itemsPerPage) || 1;

        if (currentPage > totalPages) {
            currentPage = totalPages;
        }
        if (currentPage < 1) {
            currentPage = 1;
        }

        // Show/hide pagination container
        if (totalItems <= itemsPerPage) {
            paginatorEl.classList.add('hidden');
        } else {
            paginatorEl.classList.remove('hidden');
            paginatorEl.classList.add('flex');
        }

        const startIdx = (currentPage - 1) * itemsPerPage;
        const endIdx = startIdx + itemsPerPage;

        // Disconnect observer during DOM updates to avoid infinite loop
        if (observer) observer.disconnect();

        const allRows = Array.from(tbody.querySelectorAll(':scope > tr')).filter(row => {
            return row.id !== 'noResultsRow' && row.id !== 'emptyRow' && !row.querySelector('td[colspan]');
        });

        allRows.forEach(row => {
            const isVisibleByFilter = filteredRows.includes(row);
            if (isVisibleByFilter) {
                const idx = filteredRows.indexOf(row);
                if (idx >= startIdx && idx < endIdx) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            } else {
                row.style.display = 'none';
            }
        });

        // Reconnect observer
        if (observer) observer.observe(tbody, observerConfig);

        // Update statistics
        txtStart.textContent = totalItems === 0 ? 0 : Math.min(endIdx, totalItems);
        txtTotal.textContent = totalItems;

        // Update navigation buttons status
        btnPrev.disabled = (currentPage === 1);
        btnNext.disabled = (currentPage === totalPages);

        // Render page links
        pageButtonsContainer.innerHTML = '';

        let startPage = Math.max(1, currentPage - Math.floor(maxPages / 2));
        let endPage = Math.min(totalPages, startPage + maxPages - 1);

        if (endPage - startPage + 1 < maxPages) {
            startPage = Math.max(1, endPage - maxPages + 1);
        }

        for (let p = startPage; p <= endPage; p++) {
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.textContent = p;

            if (p === currentPage) {
                btn.className = 'pagination-page-btn pagination-page-btn-active inline-flex h-9 w-9 items-center justify-center rounded-lg text-sm font-bold select-none';
            } else {
                btn.className = 'pagination-page-btn pagination-page-btn-inactive inline-flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-600 text-sm font-medium transition hover:bg-slate-50 hover:text-slate-900 select-none';
            }

            btn.addEventListener('click', function () {
                currentPage = p;
                update();
            });
            pageButtonsContainer.appendChild(btn);
        }

        isUpdating = false;
    }

    const observerConfig = { attributes: true, childList: true, subtree: true, attributeFilter: ['style', 'class', 'data-filter-hidden'] };
    const observer = new MutationObserver(function (mutations) {
        let externalChange = false;
        for (let mutation of mutations) {
            if (mutation.target.tagName === 'TR' && !isUpdating) {
                externalChange = true;
                break;
            }
        }
        if (externalChange) {
            update();
        }
    });

    observer.observe(tbody, observerConfig);

    btnPrev.addEventListener('click', function () {
        if (currentPage > 1) {
            currentPage--;
            update();
        }
    });

    btnNext.addEventListener('click', function () {
        const filteredRows = getFilteredRows();
        const totalPages = Math.ceil(filteredRows.length / itemsPerPage);
        if (currentPage < totalPages) {
            currentPage++;
            update();
        }
    });

    update();
});
</script>
