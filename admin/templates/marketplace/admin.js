document.addEventListener('DOMContentLoaded', function () {
    initMarketplaceToolbars();
});

function initMarketplaceToolbars() {
    const lists = document.querySelectorAll('.market-list');
    lists.forEach(function (list) {
        const parent = list.parentElement;
        const toolbar = parent.querySelector('.market-toolbar');
        if (!toolbar) return;

        const searchInput = toolbar.querySelector('.market-search-input');
        const clearBtn = toolbar.querySelector('.market-search-clear');
        const catPills = toolbar.querySelectorAll('.market-cat-pill');
        const countNum = toolbar.querySelector('.market-count-num');
        const emptyState = parent.querySelector('.market-empty-state');
        const resetBtn = parent.querySelector('.market-reset-btn');

        const items = list.querySelectorAll('.market-item');
        if (!items.length) {
            toolbar.style.display = 'none';
            return;
        }

        // Detect if this list is for themes only
        const isThemeList = Array.from(items).every(item => item.getAttribute('data-type') === 'theme');
        const categoriesBar = toolbar.querySelector('.market-categories-bar');
        if (isThemeList && categoriesBar) {
            categoriesBar.style.display = 'none';
        }

        // Calculate counts per category
        const counts = { all: items.length, updates: 0 };
        items.forEach(function (item) {
            const cat = item.getAttribute('data-category') || 'system';
            counts[cat] = (counts[cat] || 0) + 1;
            if (item.classList.contains('has-update')) {
                counts.updates++;
            }
        });

        // Update pill counts
        catPills.forEach(function (pill) {
            const cat = pill.getAttribute('data-cat');
            const cnt = counts[cat] || 0;
            let countBadge = pill.querySelector('.pill-count');
            if (!countBadge) {
                countBadge = document.createElement('span');
                countBadge.className = 'pill-count';
                pill.appendChild(countBadge);
            }
            countBadge.textContent = cnt;
            if (cnt === 0 && cat !== 'all' && cat !== 'updates') {
                pill.style.display = 'none';
            }
            if (cat === 'updates' && counts.updates === 0) {
                pill.style.display = 'none';
            }
        });

        let currentCat = 'all';
        let currentSearch = '';

        function filterItems() {
            let visibleCount = 0;
            const q = currentSearch.toLowerCase().trim();

            items.forEach(function (item) {
                const itemCat = item.getAttribute('data-category') || '';
                const isUpdate = item.classList.contains('has-update');
                const name = (item.getAttribute('data-name') || '').toLowerCase();
                const desc = (item.getAttribute('data-desc') || '').toLowerCase();
                const slug = (item.getAttribute('data-slug') || '').toLowerCase();

                let matchesCat = true;
                if (currentCat === 'all') {
                    matchesCat = true;
                } else if (currentCat === 'updates') {
                    matchesCat = isUpdate;
                } else {
                    matchesCat = (itemCat === currentCat);
                }

                let matchesSearch = true;
                if (q !== '') {
                    matchesSearch = name.includes(q) || desc.includes(q) || slug.includes(q);
                }

                if (matchesCat && matchesSearch) {
                    item.style.display = '';
                    visibleCount++;
                } else {
                    item.style.display = 'none';
                }
            });

            if (countNum) {
                countNum.textContent = visibleCount;
            }

            if (emptyState) {
                emptyState.style.display = (visibleCount === 0) ? 'block' : 'none';
            }
        }

        // Search input event
        if (searchInput) {
            searchInput.addEventListener('input', function () {
                currentSearch = this.value;
                if (clearBtn) {
                    clearBtn.style.display = (currentSearch !== '') ? 'inline-flex' : 'none';
                }
                filterItems();
            });
        }

        // Clear button event
        if (clearBtn) {
            clearBtn.addEventListener('click', function () {
                if (searchInput) {
                    searchInput.value = '';
                    currentSearch = '';
                    searchInput.focus();
                }
                clearBtn.style.display = 'none';
                filterItems();
            });
        }

        // Category pill click event
        catPills.forEach(function (pill) {
            pill.addEventListener('click', function () {
                catPills.forEach(p => p.classList.remove('active'));
                this.classList.add('active');
                currentCat = this.getAttribute('data-cat') || 'all';
                filterItems();
            });
        });

        // Reset button event
        if (resetBtn) {
            resetBtn.addEventListener('click', function () {
                if (searchInput) {
                    searchInput.value = '';
                    currentSearch = '';
                }
                if (clearBtn) {
                    clearBtn.style.display = 'none';
                }
                catPills.forEach(p => p.classList.remove('active'));
                const allPill = toolbar.querySelector('.market-cat-pill[data-cat="all"]');
                if (allPill) allPill.classList.add('active');
                currentCat = 'all';
                filterItems();
            });
        }

        // Initial filter call
        filterItems();
    });
}
