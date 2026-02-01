// ==============================================
// SEARCH - JavaScript cho tìm kiếm
// ==============================================

document.addEventListener('DOMContentLoaded', function() {
    initSearch();
});

function initSearch() {
    const searchBox = document.querySelector('.search-box');
    const searchInput = document.getElementById('headerSearchInput') || searchBox?.querySelector('input');
    const suggestions = document.getElementById('searchSuggestions') || searchBox?.querySelector('.search-suggestions');

    if (!searchInput || !searchBox || !suggestions) return;
    if (searchInput.dataset.searchReady === 'true') return;
    searchInput.dataset.searchReady = 'true';

    const endpoint = searchInput.dataset.searchEndpoint || '/steamweb/app/Controllers/SearchController.php';
    const searchPage = searchInput.dataset.searchPage || '/steamweb/public/index.php?page=search';
    const productPage = searchInput.dataset.productPage || '/steamweb/public/index.php?page=product&id=';

    let debounceTimer = null;
    let controller = null;
    const cache = new Map();

    const hideSuggestions = () => {
        suggestions.classList.remove('visible');
        suggestions.setAttribute('aria-hidden', 'true');
        suggestions.innerHTML = '';
    };

    const showSuggestions = () => {
        suggestions.classList.add('visible');
        suggestions.setAttribute('aria-hidden', 'false');
    };

    const renderItems = (items, query) => {
        if (!items || items.length === 0) {
            suggestions.innerHTML = `<div class="search-suggestion empty">Không tìm thấy kết quả cho "${escapeHtml(query)}"</div>`;
            showSuggestions();
            return;
        }

        const fragment = document.createDocumentFragment();
        items.forEach(item => {
            const price = item.sale_price && Number(item.sale_price) > 0 ? item.sale_price : item.price;
            const div = document.createElement('a');
            div.className = 'search-suggestion';
            div.href = `${productPage}${item.id}`;
            div.innerHTML = `
                <div class="suggestion-image">
                    <img src="${escapeAttr(item.image || '')}" alt="${escapeAttr(item.name || '')}">
                </div>
                <div class="suggestion-info">
                    <div class="suggestion-name">${escapeHtml(item.name || '')}</div>
                    <div class="suggestion-price">${formatCurrency(price)}</div>
                </div>
            `;
            fragment.appendChild(div);
        });
        suggestions.innerHTML = '';
        suggestions.appendChild(fragment);
        showSuggestions();
    };

    const fetchSuggestions = (query) => {
        if (cache.has(query)) {
            renderItems(cache.get(query), query);
            return;
        }

        if (controller) controller.abort();
        controller = new AbortController();

        fetch(`${endpoint}?q=${encodeURIComponent(query)}`, { signal: controller.signal })
            .then(res => res.ok ? res.json() : { success: false, items: [] })
            .then(data => {
                if (!data || !data.success) {
                    hideSuggestions();
                    return;
                }
                cache.set(query, data.items || []);
                renderItems(data.items || [], query);
            })
            .catch(err => {
                if (err.name !== 'AbortError') {
                    hideSuggestions();
                }
            });
    };

    const handleInput = () => {
        const query = searchInput.value.trim();
        if (query.length < 2) {
            hideSuggestions();
            return;
        }

        if (debounceTimer) clearTimeout(debounceTimer);
        debounceTimer = setTimeout(() => {
            fetchSuggestions(query);
        }, 250);
    };

    searchInput.addEventListener('input', handleInput);

    searchInput.addEventListener('keydown', function(e) {
        if (e.key === 'Enter') {
            const searchTerm = this.value.trim();
            if (searchTerm) {
                window.location.href = `${searchPage}&q=${encodeURIComponent(searchTerm)}`;
            }
        }
        if (e.key === 'Escape') {
            hideSuggestions();
        }
    });

    document.addEventListener('click', (e) => {
        if (!searchBox.contains(e.target)) {
            hideSuggestions();
        }
    });
}

function formatCurrency(amount) {
    return new Intl.NumberFormat('vi-VN').format(amount) + 'đ';
}

function escapeHtml(str) {
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');
}

function escapeAttr(str) {
    return escapeHtml(str);
}
