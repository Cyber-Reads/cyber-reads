const FAVORITES_STORAGE_KEY = 'cyber_reads_favorites';
const RECENTLY_VIEWED_STORAGE_KEY = 'cyber_reads_recently_viewed';
const FALLBACK_COVER_PATH = 'assets/img/1.jpg';

const memoryFavorites = new Set();
let currentBooksData = [];
let currentSortOrder = 'default';
let showFavoritesOnly = false;
let latestLoadRequestId = 0;
let activeFetchController = null;
let loadedCategoriesList = [];

function normalizeBookTitle(title) {
    if (typeof title !== 'string') return '';
    return title.trim().replace(/\s+/g, ' ');
}

function isSafeAssetPath(url) {
    if (typeof url !== 'string') return false;
    const trimmed = url.trim();
    if (!trimmed) return false;
    if (/^(javascript|data|vbscript):/i.test(trimmed)) return false;
    if (trimmed.includes('..')) return false;
    return /^[a-zA-Z0-9_\-./ ]+$/.test(trimmed);
}

function getSafeCoverUrl(cover) {
    if (isSafeAssetPath(cover)) {
        return cover.trim();
    }
    return FALLBACK_COVER_PATH;
}

function getFavoriteIds() {
    try {
        const raw = window.localStorage.getItem(FAVORITES_STORAGE_KEY);
        if (!raw) return Array.from(memoryFavorites);
        const parsed = JSON.parse(raw);
        if (!Array.isArray(parsed)) return Array.from(memoryFavorites);
        const normalized = parsed
            .map(id => String(id).trim())
            .filter(id => /^\d+$/.test(id));
        const unique = Array.from(new Set(normalized));
        memoryFavorites.clear();
        unique.forEach(id => memoryFavorites.add(id));
        return unique;
    } catch (e) {
        return Array.from(memoryFavorites);
    }
}

function saveFavoriteIds(ids) {
    const clean = Array.from(new Set(ids.map(id => String(id).trim()).filter(id => /^\d+$/.test(id))));
    memoryFavorites.clear();
    clean.forEach(id => memoryFavorites.add(id));
    try {
        window.localStorage.setItem(FAVORITES_STORAGE_KEY, JSON.stringify(clean));
    } catch (e) {
        // Storage unavailable or blocked; in-memory fallback remains active
    }
    return clean;
}

function isBookFavorite(bookId) {
    const target = String(bookId).trim();
    return getFavoriteIds().includes(target);
}

function toggleFavoriteId(bookId) {
    const target = String(bookId).trim();
    if (!/^\d+$/.test(target)) return false;
    const current = getFavoriteIds();
    const index = current.indexOf(target);
    let nowFavorite = false;
    if (index >= 0) {
        current.splice(index, 1);
        nowFavorite = false;
    } else {
        current.push(target);
        nowFavorite = true;
    }
    saveFavoriteIds(current);
    return nowFavorite;
}

function sortBooksList(books, sortOrder) {
    if (!Array.isArray(books)) return [];
    const copy = books.slice();
    if (sortOrder === 'title-asc') {
        copy.sort((a, b) =>
            normalizeBookTitle(a.title).localeCompare(normalizeBookTitle(b.title), undefined, {
                sensitivity: 'base',
                numeric: true
            })
        );
    } else if (sortOrder === 'title-desc') {
        copy.sort((a, b) =>
            normalizeBookTitle(b.title).localeCompare(normalizeBookTitle(a.title), undefined, {
                sensitivity: 'base',
                numeric: true
            })
        );
    }
    return copy;
}

function filterAndSortBooks(books, options = {}) {
    const {
        favoriteIds = [],
        favoritesOnly = false,
        sortOrder = 'default'
    } = options;

    if (!Array.isArray(books)) return [];
    let filtered = books.slice();

    if (favoritesOnly) {
        const favSet = new Set(favoriteIds.map(id => String(id).trim()));
        filtered = filtered.filter(b => b && favSet.has(String(b.bookid).trim()));
    }

    return sortBooksList(filtered, sortOrder);
}

function getRecentlyViewedBooks() {
    try {
        const raw = window.localStorage.getItem(RECENTLY_VIEWED_STORAGE_KEY);
        if (!raw) return [];
        const parsed = JSON.parse(raw);
        if (!Array.isArray(parsed)) return [];
        return parsed.filter(item => item && /^\d+$/.test(String(item.bookid).trim())).slice(0, 5);
    } catch (e) {
        return [];
    }
}

function clearRecentlyViewedBooks() {
    try {
        window.localStorage.removeItem(RECENTLY_VIEWED_STORAGE_KEY);
    } catch (e) {
        // Ignore storage error
    }
    renderRecentlyViewed();
}

function announceStatus(message) {
    const statusEl = document.getElementById('libraryStatus');
    if (statusEl) {
        statusEl.textContent = message;
    }
}

function updateFavoritesToolbarUI() {
    const favIds = getFavoriteIds();
    const badge = document.getElementById('favoritesCountBadge');
    const button = document.getElementById('favoritesFilterButton');
    const icon = document.getElementById('favoritesFilterIcon');

    if (badge) {
        badge.textContent = String(favIds.length);
    }
    if (button) {
        button.setAttribute('aria-pressed', showFavoritesOnly ? 'true' : 'false');
        if (showFavoritesOnly) {
            button.className = 'inline-flex items-center gap-2 px-3.5 py-2.5 h-11 rounded-xl border border-purple-700 bg-purple-950 text-white text-sm font-semibold shadow-sm hover:bg-purple-900 focus:outline-none focus:ring-2 focus:ring-purple-600 transition-colors';
            if (badge) badge.className = 'inline-flex items-center justify-center px-2 py-0.5 text-xs font-bold rounded-full bg-purple-700 text-white';
            if (icon) {
                icon.setAttribute('fill', 'currentColor');
                icon.className = 'w-4 h-4 text-pink-400 transition-colors';
            }
        } else {
            button.className = 'inline-flex items-center gap-2 px-3.5 py-2.5 h-11 rounded-xl border border-gray-300 bg-white text-gray-800 text-sm font-semibold shadow-sm hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-purple-600 transition-colors';
            if (badge) badge.className = 'inline-flex items-center justify-center px-2 py-0.5 text-xs font-bold rounded-full bg-gray-200 text-gray-800';
            if (icon) {
                icon.setAttribute('fill', 'none');
                icon.className = 'w-4 h-4 text-gray-500 transition-colors';
            }
        }
    }
}

function updateActiveFiltersUI() {
    const selectedCategoryBoxes = Array.from(
        document.querySelectorAll('#categoryCheckboxes input[type="checkbox"]:checked')
    );
    const activeGenreBadge = document.getElementById('activeGenreBadge');
    const activeFilterBar = document.getElementById('activeFilterBar');
    const summaryText = document.getElementById('activeFilterSummaryText');

    if (activeGenreBadge) {
        if (selectedCategoryBoxes.length > 0) {
            activeGenreBadge.textContent = String(selectedCategoryBoxes.length);
            activeGenreBadge.classList.remove('hidden');
            activeGenreBadge.classList.add('inline-flex');
        } else {
            activeGenreBadge.classList.add('hidden');
            activeGenreBadge.classList.remove('inline-flex');
        }
    }

    if (!activeFilterBar || !summaryText) return;

    const parts = [];
    if (selectedCategoryBoxes.length > 0) {
        const names = selectedCategoryBoxes.map(cb => cb.getAttribute('data-category-label') || 'Genre');
        parts.push(`Genres (${names.join(', ')})`);
    }
    if (showFavoritesOnly) {
        parts.push('Favorites only');
    }
    if (currentSortOrder === 'title-asc') {
        parts.push('Sorted A–Z');
    } else if (currentSortOrder === 'title-desc') {
        parts.push('Sorted Z–A');
    }

    if (parts.length > 0) {
        summaryText.textContent = parts.join(' • ');
        activeFilterBar.classList.remove('hidden');
        activeFilterBar.classList.add('flex');
    } else {
        summaryText.textContent = '';
        activeFilterBar.classList.add('hidden');
        activeFilterBar.classList.remove('flex');
    }
}

function createHeartSvg(isFavorited) {
    const svgNS = 'http://www.w3.org/2000/svg';
    const svg = document.createElementNS(svgNS, 'svg');
    svg.setAttribute('viewBox', '0 0 24 24');
    svg.setAttribute('stroke', 'currentColor');
    svg.setAttribute('aria-hidden', 'true');
    svg.setAttribute('class', isFavorited ? 'w-5 h-5 text-red-500' : 'w-5 h-5 text-gray-700');
    svg.setAttribute('fill', isFavorited ? 'currentColor' : 'none');

    const path = document.createElementNS(svgNS, 'path');
    path.setAttribute('stroke-linecap', 'round');
    path.setAttribute('stroke-linejoin', 'round');
    path.setAttribute('stroke-width', '2');
    path.setAttribute(
        'd',
        'M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z'
    );
    svg.appendChild(path);
    return svg;
}

function buildBookCardElement(book) {
    const bookId = String(book.bookid || '').trim();
    const cleanTitle = normalizeBookTitle(book.title) || 'Untitled Book';
    const cleanAuthors = book.authors && String(book.authors).trim() !== ''
        ? `by ${String(book.authors).trim()}`
        : 'Unknown Author';
    const isFav = isBookFavorite(bookId);

    const card = document.createElement('article');
    card.className = 'group relative w-full max-w-[17rem] shadow-sm hover:shadow-xl hover:shadow-purple-200/50 hover:-translate-y-1 transition-all duration-200 ease-in-out bg-white rounded-2xl border border-slate-200 p-3.5 flex flex-col justify-between gap-3 mx-auto';
    card.setAttribute('data-book-id', bookId);

    // Cover wrapper
    const coverWrapper = document.createElement('div');
    coverWrapper.className = 'relative w-full aspect-[2/3] overflow-hidden bg-slate-100 rounded-xl shadow-inner';

    const coverLink = document.createElement('a');
    coverLink.href = `bookRead.html?id=${encodeURIComponent(bookId)}`;
    coverLink.className = 'block w-full h-full focus:outline-none focus:ring-2 focus:ring-purple-600 rounded-xl';
    coverLink.setAttribute('tabindex', '-1');
    coverLink.setAttribute('aria-hidden', 'true');

    const img = document.createElement('img');
    img.src = getSafeCoverUrl(book.cover);
    img.alt = `${cleanTitle} cover`;
    img.width = 240;
    img.height = 360;
    img.loading = 'lazy';
    img.className = 'w-full h-full object-cover group-hover:scale-[1.03] transition-transform duration-200';
    img.addEventListener('error', () => {
        if (img.src.indexOf(FALLBACK_COVER_PATH) === -1) {
            img.src = FALLBACK_COVER_PATH;
        }
    });

    coverLink.appendChild(img);
    coverWrapper.appendChild(coverLink);

    // Favorite button overlay
    const favBtn = document.createElement('button');
    favBtn.type = 'button';
    favBtn.setAttribute('data-favorite-btn', bookId);
    favBtn.setAttribute('aria-pressed', isFav ? 'true' : 'false');
    favBtn.setAttribute(
        'aria-label',
        isFav ? `Remove ${cleanTitle} from favorites` : `Add ${cleanTitle} to favorites`
    );
    favBtn.title = isFav ? 'Remove from favorites' : 'Add to favorites';
    favBtn.className = 'absolute top-2.5 right-2.5 z-10 w-9 h-9 rounded-full bg-white/90 hover:bg-white shadow-md border border-gray-200/80 flex items-center justify-center transition-colors focus:outline-none focus:ring-2 focus:ring-purple-600';
    favBtn.appendChild(createHeartSvg(isFav));

    favBtn.addEventListener('click', (e) => {
        e.preventDefault();
        e.stopPropagation();
        const nowFav = toggleFavoriteId(bookId);
        updateFavoritesToolbarUI();
        updateActiveFiltersUI();
        announceStatus(
            nowFav
                ? `${cleanTitle} added to favorites.`
                : `${cleanTitle} removed from favorites.`
        );
        if (showFavoritesOnly) {
            renderBooksGrid();
        } else {
            syncFavoriteButtonsInDOM();
        }
    });

    coverWrapper.appendChild(favBtn);
    card.appendChild(coverWrapper);

    // Book info
    const infoDiv = document.createElement('div');
    infoDiv.className = 'flex flex-col flex-grow text-center mt-1 px-1';

    const titleEl = document.createElement('h3');
    titleEl.className = 'text-slate-900 line-clamp-2 min-h-[3.25rem] font-bold text-base sm:text-lg leading-snug';
    titleEl.title = cleanTitle;
    titleEl.textContent = cleanTitle;

    const authorEl = document.createElement('p');
    authorEl.className = 'mt-1 line-clamp-1 text-sm text-slate-600 font-medium';
    authorEl.textContent = cleanAuthors;

    infoDiv.appendChild(titleEl);
    infoDiv.appendChild(authorEl);
    card.appendChild(infoDiv);

    // Accessible Read Now action link (no nested button inside anchor)
    const readLink = document.createElement('a');
    readLink.href = `bookRead.html?id=${encodeURIComponent(bookId)}`;
    readLink.className = 'mt-1 py-2.5 px-4 w-full bg-neutral-950 text-white text-center font-bold text-sm sm:text-base rounded-xl shadow-sm transition-colors duration-200 hover:bg-purple-950 focus:outline-none focus:ring-2 focus:ring-purple-600 focus:ring-offset-2 block';
    readLink.textContent = 'Read Now';
    readLink.setAttribute('aria-label', `Read ${cleanTitle}`);

    card.appendChild(readLink);

    return card;
}

function syncFavoriteButtonsInDOM() {
    const buttons = document.querySelectorAll('[data-favorite-btn]');
    buttons.forEach(btn => {
        const bookId = btn.getAttribute('data-favorite-btn');
        const card = btn.closest('[data-book-id]');
        const titleEl = card ? card.querySelector('h3') : null;
        const cleanTitle = titleEl ? titleEl.textContent : 'book';
        const isFav = isBookFavorite(bookId);

        btn.setAttribute('aria-pressed', isFav ? 'true' : 'false');
        btn.setAttribute(
            'aria-label',
            isFav ? `Remove ${cleanTitle} from favorites` : `Add ${cleanTitle} to favorites`
        );
        btn.title = isFav ? 'Remove from favorites' : 'Add to favorites';
        btn.textContent = '';
        btn.appendChild(createHeartSvg(isFav));
    });
}

function renderBooksGrid() {
    const bookGrid = document.getElementById('bookCardGrid');
    if (!bookGrid) return;

    const displayedBooks = filterAndSortBooks(currentBooksData, {
        favoriteIds: getFavoriteIds(),
        favoritesOnly: showFavoritesOnly,
        sortOrder: currentSortOrder
    });

    bookGrid.textContent = '';

    if (displayedBooks.length > 0) {
        const fragment = document.createDocumentFragment();
        displayedBooks.forEach(book => {
            if (book && book.bookid !== undefined) {
                fragment.appendChild(buildBookCardElement(book));
            }
        });
        bookGrid.appendChild(fragment);
        announceStatus(`Showing ${displayedBooks.length} ${displayedBooks.length === 1 ? 'book' : 'books'}.`);
    } else {
        const emptyBox = document.createElement('div');
        emptyBox.className = 'col-span-full bg-white rounded-2xl border border-gray-200 p-8 sm:p-12 text-center max-w-lg mx-auto my-4 shadow-sm';

        const heading = document.createElement('h3');
        heading.className = 'text-lg sm:text-xl font-bold text-gray-900 mb-2';
        heading.textContent = showFavoritesOnly
            ? 'No favorite books found'
            : 'No books match the selected genres';

        const desc = document.createElement('p');
        desc.className = 'text-sm sm:text-base text-gray-600 mb-6';
        desc.textContent = showFavoritesOnly
            ? 'Tap the heart icon on any book card or book details page to save your favorite reads here.'
            : 'Try clearing some genre filters to explore more books in the collection.';

        const resetBtn = document.createElement('button');
        resetBtn.type = 'button';
        resetBtn.className = 'px-5 py-2.5 rounded-xl bg-neutral-900 hover:bg-purple-950 text-white text-sm font-semibold transition-colors focus:outline-none focus:ring-2 focus:ring-purple-600';
        resetBtn.textContent = showFavoritesOnly ? 'Show All Books' : 'Clear Filters';
        resetBtn.addEventListener('click', () => {
            resetAllFilters();
        });

        emptyBox.appendChild(heading);
        emptyBox.appendChild(desc);
        emptyBox.appendChild(resetBtn);
        bookGrid.appendChild(emptyBox);
        announceStatus(heading.textContent);
    }
}

function renderRecentlyViewed() {
    const section = document.getElementById('recentlyViewedSection');
    const grid = document.getElementById('recentlyViewedGrid');
    if (!section || !grid) return;

    const recent = getRecentlyViewedBooks();
    grid.textContent = '';

    if (recent.length === 0) {
        section.classList.add('hidden');
        return;
    }

    section.classList.remove('hidden');
    const fragment = document.createDocumentFragment();

    recent.forEach(item => {
        const bookId = String(item.bookid).trim();
        const cleanTitle = normalizeBookTitle(item.title) || 'Untitled Book';

        const link = document.createElement('a');
        link.href = `bookRead.html?id=${encodeURIComponent(bookId)}`;
        link.className = 'group flex items-center gap-3 p-2.5 bg-white rounded-xl border border-gray-200 shadow-sm hover:border-purple-400 hover:shadow transition-all focus:outline-none focus:ring-2 focus:ring-purple-600';

        const img = document.createElement('img');
        img.src = getSafeCoverUrl(item.cover);
        img.alt = `${cleanTitle} cover`;
        img.width = 48;
        img.height = 72;
        img.loading = 'lazy';
        img.className = 'w-12 h-16 object-cover rounded-lg flex-shrink-0 bg-gray-100';
        img.addEventListener('error', () => {
            if (img.src.indexOf(FALLBACK_COVER_PATH) === -1) {
                img.src = FALLBACK_COVER_PATH;
            }
        });

        const meta = document.createElement('div');
        meta.className = 'min-w-0 flex-1';

        const titleP = document.createElement('p');
        titleP.className = 'text-xs sm:text-sm font-bold text-gray-900 line-clamp-2 group-hover:text-purple-800';
        titleP.textContent = cleanTitle;

        meta.appendChild(titleP);
        if (item.authors) {
            const authorP = document.createElement('p');
            authorP.className = 'text-xs text-gray-500 truncate mt-0.5';
            authorP.textContent = String(item.authors).trim();
            meta.appendChild(authorP);
        }

        link.appendChild(img);
        link.appendChild(meta);
        fragment.appendChild(link);
    });

    grid.appendChild(fragment);
}

function loadCategories() {
    const categoryContainer = document.getElementById('categoryCheckboxes');
    if (!categoryContainer) return;

    fetch('src/books/get_categories.php')
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error ${response.status}`);
            }
            return response.json();
        })
        .then(categories => {
            if (!Array.isArray(categories)) {
                throw new Error('Unexpected categories response format');
            }
            loadedCategoriesList = categories;
            categoryContainer.textContent = '';

            if (categories.length === 0) {
                const emptyMsg = document.createElement('p');
                emptyMsg.className = 'text-sm text-gray-500';
                emptyMsg.textContent = 'No genres available.';
                categoryContainer.appendChild(emptyMsg);
                return;
            }

            const fragment = document.createDocumentFragment();
            categories.forEach(category => {
                if (!category || category.categoryid === undefined) return;
                const catId = String(category.categoryid).trim();
                const catName = String(category.category || '').trim();
                if (!catName) return;

                const label = document.createElement('label');
                label.className = 'flex items-center p-1.5 rounded-lg hover:bg-gray-50 cursor-pointer transition-colors';

                const input = document.createElement('input');
                input.type = 'checkbox';
                input.className = 'h-4 w-4 text-purple-600 bg-gray-100 border-gray-300 rounded focus:ring-purple-500 focus:ring-2 cursor-pointer';
                input.value = catId;
                input.setAttribute('data-category-label', catName);

                const span = document.createElement('span');
                span.className = 'ml-3 text-sm font-medium text-gray-800 select-none';
                span.textContent = catName;

                label.appendChild(input);
                label.appendChild(span);
                fragment.appendChild(label);
            });

            categoryContainer.appendChild(fragment);
        })
        .catch(error => {
            console.error('Error loading categories:', error);
            categoryContainer.textContent = '';
            const errP = document.createElement('p');
            errP.className = 'text-sm text-red-600';
            errP.textContent = 'Unable to load genres.';
            categoryContainer.appendChild(errP);
        });
}

function getSelectedCategories() {
    const selected = [];
    document.querySelectorAll('#categoryCheckboxes input[type="checkbox"]:checked').forEach(checkbox => {
        selected.push(checkbox.value);
    });
    return selected;
}

function loadBooks(categoryIds = []) {
    const bookGrid = document.getElementById('bookCardGrid');
    if (!bookGrid) return;

    const requestId = ++latestLoadRequestId;
    if (activeFetchController && typeof activeFetchController.abort === 'function') {
        activeFetchController.abort();
    }
    if (typeof AbortController !== 'undefined') {
        activeFetchController = new AbortController();
    }

    bookGrid.textContent = '';
    const loadingP = document.createElement('p');
    loadingP.className = 'text-purple-900 font-medium text-center col-span-full py-12';
    loadingP.textContent = 'Loading books...';
    bookGrid.appendChild(loadingP);
    announceStatus('Loading books...');

    const fetchOptions = {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ category_ids: categoryIds })
    };
    if (activeFetchController) {
        fetchOptions.signal = activeFetchController.signal;
    }

    fetch('src/books/filter_books.php', fetchOptions)
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP status ${response.status}`);
            }
            return response.json();
        })
        .then(books => {
            if (requestId !== latestLoadRequestId) return;
            if (!Array.isArray(books)) {
                throw new Error(books && books.error ? books.error : 'Invalid books data received');
            }
            currentBooksData = books;
            updateFavoritesToolbarUI();
            updateActiveFiltersUI();
            renderBooksGrid();
        })
        .catch(error => {
            if (error && error.name === 'AbortError') return;
            if (requestId !== latestLoadRequestId) return;

            console.error('Error loading books:', error);
            bookGrid.textContent = '';

            const errorBox = document.createElement('div');
            errorBox.className = 'col-span-full bg-white rounded-2xl border border-red-200 p-8 text-center max-w-md mx-auto my-4 shadow-sm';

            const errHeading = document.createElement('p');
            errHeading.className = 'text-red-700 font-bold text-lg mb-2';
            errHeading.textContent = 'Failed to load books.';

            const errSub = document.createElement('p');
            errSub.className = 'text-gray-600 text-sm mb-4';
            errSub.textContent = 'Please check your connection and try again.';

            const retryBtn = document.createElement('button');
            retryBtn.type = 'button';
            retryBtn.className = 'px-5 py-2 rounded-xl bg-neutral-900 hover:bg-purple-950 text-white text-sm font-semibold transition-colors focus:outline-none focus:ring-2 focus:ring-purple-600';
            retryBtn.textContent = 'Try Again';
            retryBtn.addEventListener('click', () => loadBooks(getSelectedCategories()));

            errorBox.appendChild(errHeading);
            errorBox.appendChild(errSub);
            errorBox.appendChild(retryBtn);
            bookGrid.appendChild(errorBox);
            announceStatus('Failed to load books.');
        });
}

function resetAllFilters() {
    document.querySelectorAll('#categoryCheckboxes input[type="checkbox"]').forEach(cb => {
        cb.checked = false;
    });
    showFavoritesOnly = false;
    currentSortOrder = 'default';
    const sortSelect = document.getElementById('sortSelect');
    if (sortSelect) {
        sortSelect.value = 'default';
    }
    updateFavoritesToolbarUI();
    updateActiveFiltersUI();
    loadBooks([]);
}

function setupDropdownAccessibility() {
    const filterBtn = document.getElementById('filterMenuButton');
    const dropdown = document.getElementById('filterCategoriesDropdown');
    if (!filterBtn || !dropdown) return;

    function openDropdown() {
        dropdown.classList.remove('hidden');
        filterBtn.setAttribute('aria-expanded', 'true');
        const firstCheckbox = dropdown.querySelector('input[type="checkbox"]');
        if (firstCheckbox) {
            firstCheckbox.focus();
        }
    }

    function closeDropdown(restoreFocus = false) {
        if (!dropdown.classList.contains('hidden')) {
            dropdown.classList.add('hidden');
            filterBtn.setAttribute('aria-expanded', 'false');
            if (restoreFocus) {
                filterBtn.focus();
            }
        }
    }

    filterBtn.addEventListener('click', (e) => {
        e.stopPropagation();
        const isHidden = dropdown.classList.contains('hidden');
        if (isHidden) {
            openDropdown();
        } else {
            closeDropdown(false);
        }
    });

    document.addEventListener('click', (e) => {
        if (!dropdown.classList.contains('hidden')) {
            if (!dropdown.contains(e.target) && !filterBtn.contains(e.target)) {
                closeDropdown(false);
            }
        }
    });

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && !dropdown.classList.contains('hidden')) {
            e.preventDefault();
            closeDropdown(true);
        }
    });

    return { closeDropdown };
}

function setupHeroCategoryCards() {
    const heroCards = document.querySelectorAll('[data-category-filter]');
    heroCards.forEach(card => {
        function activateCategoryCard() {
            const targetGenre = (card.getAttribute('data-category-filter') || '').trim().toLowerCase();
            const librarySection = document.getElementById('libraryPage');
            if (librarySection) {
                librarySection.scrollIntoView({ behavior: 'smooth' });
            }

            const checkboxes = document.querySelectorAll('#categoryCheckboxes input[type="checkbox"]');
            if (checkboxes.length > 0 && targetGenre) {
                let matched = false;
                checkboxes.forEach(cb => {
                    const label = (cb.getAttribute('data-category-label') || '').trim().toLowerCase();
                    if (label === targetGenre || label.includes(targetGenre)) {
                        cb.checked = true;
                        matched = true;
                    } else {
                        cb.checked = false;
                    }
                });
                if (matched) {
                    updateActiveFiltersUI();
                    loadBooks(getSelectedCategories());
                }
            }
        }

        card.addEventListener('click', activateCategoryCard);
        card.addEventListener('keydown', (e) => {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                activateCategoryCard();
            }
        });
    });
}

if (typeof document !== 'undefined') {
    document.addEventListener('DOMContentLoaded', () => {
        const dropdownCtrl = setupDropdownAccessibility();
        setupHeroCategoryCards();
        updateFavoritesToolbarUI();
        renderRecentlyViewed();

        loadCategories();
        loadBooks();

        const applyFilterButton = document.getElementById('applyFilterButton');
        const clearFilterButton = document.getElementById('clearFilterButton');
        const favoritesFilterButton = document.getElementById('favoritesFilterButton');
        const sortSelect = document.getElementById('sortSelect');
        const resetAllFiltersBtn = document.getElementById('resetAllFiltersBtn');
        const clearRecentlyViewedBtn = document.getElementById('clearRecentlyViewedBtn');

        if (applyFilterButton) {
            applyFilterButton.addEventListener('click', () => {
                const selectedCategories = getSelectedCategories();
                if (dropdownCtrl) dropdownCtrl.closeDropdown(true);
                updateActiveFiltersUI();
                loadBooks(selectedCategories);
            });
        }

        if (clearFilterButton) {
            clearFilterButton.addEventListener('click', () => {
                document.querySelectorAll('#categoryCheckboxes input[type="checkbox"]').forEach(checkbox => {
                    checkbox.checked = false;
                });
                if (dropdownCtrl) dropdownCtrl.closeDropdown(true);
                updateActiveFiltersUI();
                loadBooks([]);
            });
        }

        if (favoritesFilterButton) {
            favoritesFilterButton.addEventListener('click', () => {
                showFavoritesOnly = !showFavoritesOnly;
                updateFavoritesToolbarUI();
                updateActiveFiltersUI();
                renderBooksGrid();
            });
        }

        if (sortSelect) {
            sortSelect.addEventListener('change', (e) => {
                currentSortOrder = e.target.value || 'default';
                updateActiveFiltersUI();
                renderBooksGrid();
            });
        }

        if (resetAllFiltersBtn) {
            resetAllFiltersBtn.addEventListener('click', () => {
                resetAllFilters();
            });
        }

        if (clearRecentlyViewedBtn) {
            clearRecentlyViewedBtn.addEventListener('click', () => {
                clearRecentlyViewedBooks();
                announceStatus('Recently viewed history cleared.');
            });
        }

        window.addEventListener('storage', (e) => {
            if (!e.key || e.key === FAVORITES_STORAGE_KEY) {
                updateFavoritesToolbarUI();
                if (showFavoritesOnly) {
                    renderBooksGrid();
                } else {
                    syncFavoriteButtonsInDOM();
                }
            }
            if (!e.key || e.key === RECENTLY_VIEWED_STORAGE_KEY) {
                renderRecentlyViewed();
            }
        });

        window.addEventListener('pageshow', () => {
            updateFavoritesToolbarUI();
            syncFavoriteButtonsInDOM();
            renderRecentlyViewed();
        });
    });
}

if (typeof module !== 'undefined' && module.exports) {
    module.exports = {
        FAVORITES_STORAGE_KEY,
        RECENTLY_VIEWED_STORAGE_KEY,
        normalizeBookTitle,
        isSafeAssetPath,
        getSafeCoverUrl,
        getFavoriteIds,
        saveFavoriteIds,
        isBookFavorite,
        toggleFavoriteId,
        sortBooksList,
        filterAndSortBooks
    };
}
