/**
 * 299koEVO - Plugins Manager JavaScript
 * Live Instant Search, Filter Pills, Sorting, and State Persistence
 */
document.addEventListener('DOMContentLoaded', function () {
    initPluginsManager();
});

function initPluginsManager() {
    const container = document.getElementById('pmContainer');
    if (!container) return;

    const searchInput = document.getElementById('pmSearchInput');
    const searchClear = document.getElementById('pmSearchClear');
    const filterPills = container.querySelectorAll('.pm-filter-pill');
    const sortSelect = document.getElementById('pmSortSelect');
    const countIndicator = document.getElementById('pmCountIndicator');
    const tableBody = document.getElementById('pmTableBody');
    const emptyState = document.getElementById('pmEmptyState');
    const resetBtn = document.getElementById('pmResetBtn');
    const rows = Array.from(container.querySelectorAll('.pm-row'));

    if (!rows.length) return;

    let currentFilter = sessionStorage.getItem('pm_active_filter') || 'all';
    let currentSearch = sessionStorage.getItem('pm_search_query') || '';
    let currentSort = sessionStorage.getItem('pm_sort') || 'name-asc';

    // Set initial values from sessionStorage
    if (searchInput && currentSearch) {
        searchInput.value = currentSearch;
        if (searchClear) searchClear.style.display = 'flex';
    }
    if (sortSelect && currentSort) {
        sortSelect.value = currentSort;
    }

    // Set active pill
    filterPills.forEach(function (pill) {
        const f = pill.getAttribute('data-filter');
        if (f === currentFilter) {
            pill.classList.add('is-active');
        } else {
            pill.classList.remove('is-active');
        }
    });

    function applyFilterAndSearch() {
        const query = (searchInput ? searchInput.value : '').trim().toLowerCase();
        let visibleCount = 0;

        rows.forEach(function (row) {
            const name = (row.getAttribute('data-name') || '').toLowerCase();
            const slug = (row.getAttribute('data-slug') || '').toLowerCase();
            const desc = (row.getAttribute('data-desc') || '').toLowerCase();
            const cat = (row.getAttribute('data-cat') || '').toLowerCase();
            const isActive = row.getAttribute('data-active') === '1';
            const hasSettings = row.getAttribute('data-settings') === '1';
            const isMaintenance = row.getAttribute('data-maintenance') === '1';

            // Filter check
            let matchesFilter = true;
            if (currentFilter === 'active') {
                matchesFilter = isActive;
            } else if (currentFilter === 'inactive') {
                matchesFilter = !isActive;
            } else if (currentFilter === 'settings') {
                matchesFilter = hasSettings;
            } else if (currentFilter === 'maintenance') {
                matchesFilter = isMaintenance;
            }

            // Search query check
            let matchesSearch = true;
            if (query.length > 0) {
                matchesSearch = name.includes(query) || slug.includes(query) || desc.includes(query) || cat.includes(query);
            }

            if (matchesFilter && matchesSearch) {
                row.style.display = '';
                visibleCount++;
            } else {
                row.style.display = 'none';
            }
        });

        // Update count indicator
        if (countIndicator) {
            countIndicator.textContent = visibleCount + ' / ' + rows.length + ' extension(s)';
        }

        // Toggle Empty State
        if (emptyState && tableBody) {
            if (visibleCount === 0) {
                emptyState.style.display = 'flex';
                tableBody.parentElement.style.display = 'none';
            } else {
                emptyState.style.display = 'none';
                tableBody.parentElement.style.display = '';
            }
        }

        // Toggle search clear button
        if (searchClear) {
            searchClear.style.display = query.length > 0 ? 'flex' : 'none';
        }

        // Persist in sessionStorage
        sessionStorage.setItem('pm_active_filter', currentFilter);
        sessionStorage.setItem('pm_search_query', query);
        sessionStorage.setItem('pm_sort', currentSort);
    }

    function sortRows() {
        if (!tableBody) return;
        const sortVal = sortSelect ? sortSelect.value : 'name-asc';
        currentSort = sortVal;

        const sortedRows = rows.slice().sort(function (a, b) {
            if (sortVal === 'name-asc') {
                return (a.getAttribute('data-name') || '').localeCompare(b.getAttribute('data-name') || '', 'fr', { sensitivity: 'base' });
            } else if (sortVal === 'name-desc') {
                return (b.getAttribute('data-name') || '').localeCompare(a.getAttribute('data-name') || '', 'fr', { sensitivity: 'base' });
            } else if (sortVal === 'priority-asc') {
                const pa = parseInt(a.getAttribute('data-priority') || '5', 10);
                const pb = parseInt(b.getAttribute('data-priority') || '5', 10);
                return pa - pb;
            } else if (sortVal === 'priority-desc') {
                const pa = parseInt(a.getAttribute('data-priority') || '5', 10);
                const pb = parseInt(b.getAttribute('data-priority') || '5', 10);
                return pb - pa;
            }
            return 0;
        });

        sortedRows.forEach(function (row) {
            tableBody.appendChild(row);
        });
    }

    // Event Listeners: Search input
    if (searchInput) {
        searchInput.addEventListener('input', applyFilterAndSearch);
    }

    // Event Listeners: Clear button
    if (searchClear) {
        searchClear.addEventListener('click', function () {
            if (searchInput) {
                searchInput.value = '';
                searchInput.focus();
            }
            applyFilterAndSearch();
        });
    }

    // Event Listeners: Filter pills
    filterPills.forEach(function (pill) {
        pill.addEventListener('click', function () {
            filterPills.forEach(function (p) { p.classList.remove('is-active'); });
            pill.classList.add('is-active');
            currentFilter = pill.getAttribute('data-filter') || 'all';
            applyFilterAndSearch();
        });
    });

    // Event Listeners: Sort select
    if (sortSelect) {
        sortSelect.addEventListener('change', function () {
            sortRows();
            applyFilterAndSearch();
        });
    }

    // Event Listeners: Reset button in empty state
    if (resetBtn) {
        resetBtn.addEventListener('click', function () {
            if (searchInput) searchInput.value = '';
            currentFilter = 'all';
            filterPills.forEach(function (p) {
                if (p.getAttribute('data-filter') === 'all') p.classList.add('is-active');
                else p.classList.remove('is-active');
            });
            applyFilterAndSearch();
        });
    }

    // Run initial sorting and filtering
    sortRows();
    applyFilterAndSearch();
}
