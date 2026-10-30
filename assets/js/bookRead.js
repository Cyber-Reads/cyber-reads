(function () {
    const FAVORITES_STORAGE_KEY = 'cyber_reads_favorites';
    const RECENTLY_VIEWED_STORAGE_KEY = 'cyber_reads_recently_viewed';
    const FALLBACK_COVER_PATH = 'assets/img/1.jpg';
    const memoryFavorites = new Set();

    function normalizeText(value) {
        if (typeof value !== 'string') return '';
        return value.trim().replace(/\s+/g, ' ');
    }

    function isSafeAssetPath(url) {
        if (typeof url !== 'string') return false;
        const trimmed = url.trim();
        if (!trimmed) return false;
        if (/^(javascript|data|vbscript):/i.test(trimmed)) return false;
        if (trimmed.includes('..')) return false;
        return /^[a-zA-Z0-9_\-./ ]+$/.test(trimmed);
    }

    function getFavoriteIds() {
        try {
            const raw = window.localStorage.getItem(FAVORITES_STORAGE_KEY);
            if (!raw) return Array.from(memoryFavorites);
            const parsed = JSON.parse(raw);
            if (!Array.isArray(parsed)) return Array.from(memoryFavorites);
            const clean = Array.from(
                new Set(parsed.map(id => String(id).trim()).filter(id => /^\d+$/.test(id)))
            );
            memoryFavorites.clear();
            clean.forEach(id => memoryFavorites.add(id));
            return clean;
        } catch (e) {
            return Array.from(memoryFavorites);
        }
    }

    function isBookFavorite(bookId) {
        return getFavoriteIds().includes(String(bookId).trim());
    }

    function toggleFavoriteBook(bookId) {
        const target = String(bookId).trim();
        if (!/^\d+$/.test(target)) return false;
        const current = getFavoriteIds();
        const idx = current.indexOf(target);
        let nowFav = false;
        if (idx >= 0) {
            current.splice(idx, 1);
            nowFav = false;
        } else {
            current.push(target);
            nowFav = true;
        }
        memoryFavorites.clear();
        current.forEach(id => memoryFavorites.add(id));
        try {
            window.localStorage.setItem(FAVORITES_STORAGE_KEY, JSON.stringify(current));
        } catch (e) {
            // Storage unavailable; keep in-memory state
        }
        return nowFav;
    }

    function recordRecentlyViewed(book) {
        if (!book || !book.bookid) return;
        const bookId = String(book.bookid).trim();
        if (!/^\d+$/.test(bookId)) return;

        try {
            const raw = window.localStorage.getItem(RECENTLY_VIEWED_STORAGE_KEY);
            const existing = raw ? JSON.parse(raw) : [];
            const list = Array.isArray(existing) ? existing : [];
            const filtered = list.filter(item => item && String(item.bookid).trim() !== bookId);
            filtered.unshift({
                bookid: bookId,
                title: normalizeText(book.title) || 'Untitled Book',
                authors: normalizeText(book.authors) || 'Unknown Author',
                cover: isSafeAssetPath(book.cover) ? book.cover.trim() : FALLBACK_COVER_PATH
            });
            window.localStorage.setItem(
                RECENTLY_VIEWED_STORAGE_KEY,
                JSON.stringify(filtered.slice(0, 5))
            );
        } catch (e) {
            // Ignore blocked storage
        }
    }

    function copyTextToClipboard(text) {
        let syncCopied = false;
        try {
            const ta = document.createElement('textarea');
            ta.value = text;
            ta.style.position = 'fixed';
            ta.style.left = '0';
            ta.style.top = '0';
            ta.style.opacity = '0';
            ta.style.pointerEvents = 'none';
            document.body.appendChild(ta);
            ta.focus();
            ta.select();
            ta.setSelectionRange(0, text.length);
            syncCopied = Boolean(document.execCommand && document.execCommand('copy'));
            document.body.removeChild(ta);
        } catch (err) {
            syncCopied = false;
        }

        if (navigator.clipboard && typeof navigator.clipboard.writeText === 'function') {
            return navigator.clipboard.writeText(text).catch(err => {
                if (syncCopied) return;
                throw err;
            });
        }
        return syncCopied ? Promise.resolve() : Promise.reject(new Error('Copy command failed'));
    }

    function renderErrorState(container, message) {
        if (!container) return;
        container.textContent = '';

        const wrapper = document.createElement('div');
        wrapper.className = 'flex flex-col items-center justify-center h-full py-12 text-center';
        wrapper.setAttribute('role', 'alert');

        const heading = document.createElement('h1');
        heading.className = 'text-2xl sm:text-3xl font-bold text-red-600 mb-3';
        heading.textContent = message;

        const sub = document.createElement('p');
        sub.className = 'text-gray-600 mb-6 max-w-md';
        sub.textContent = 'The requested book could not be loaded. You can return to the digital library to browse available titles.';

        const backLink = document.createElement('a');
        backLink.href = 'index.html#libraryPage';
        backLink.className = 'px-6 py-3 rounded-xl bg-black hover:bg-purple-950 text-white font-semibold transition-colors focus:outline-none focus:ring-2 focus:ring-purple-600';
        backLink.textContent = 'Return to Library';

        wrapper.appendChild(heading);
        wrapper.appendChild(sub);
        wrapper.appendChild(backLink);
        container.appendChild(wrapper);
    }

    function updateFavoriteButtonUI(btn, isFav, title) {
        if (!btn) return;
        btn.setAttribute('aria-pressed', isFav ? 'true' : 'false');
        btn.setAttribute(
            'aria-label',
            isFav ? `Remove ${title} from favorites` : `Add ${title} to favorites`
        );
        btn.className = isFav
            ? 'inline-flex items-center justify-center gap-2 px-5 py-3.5 rounded-xl font-semibold border border-red-300 bg-red-50 text-red-700 hover:bg-red-100 transition-colors focus:outline-none focus:ring-2 focus:ring-red-500'
            : 'inline-flex items-center justify-center gap-2 px-5 py-3.5 rounded-xl font-semibold border border-gray-300 bg-white text-gray-800 hover:bg-gray-100 transition-colors focus:outline-none focus:ring-2 focus:ring-purple-600';

        btn.textContent = '';
        const svgNS = 'http://www.w3.org/2000/svg';
        const svg = document.createElementNS(svgNS, 'svg');
        svg.setAttribute('viewBox', '0 0 24 24');
        svg.setAttribute('stroke', 'currentColor');
        svg.setAttribute('aria-hidden', 'true');
        svg.setAttribute('class', isFav ? 'w-5 h-5 text-red-600' : 'w-5 h-5 text-gray-600');
        svg.setAttribute('fill', isFav ? 'currentColor' : 'none');

        const path = document.createElementNS(svgNS, 'path');
        path.setAttribute('stroke-linecap', 'round');
        path.setAttribute('stroke-linejoin', 'round');
        path.setAttribute('stroke-width', '2');
        path.setAttribute(
            'd',
            'M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z'
        );
        svg.appendChild(path);

        const labelSpan = document.createElement('span');
        labelSpan.textContent = isFav ? 'Favorited' : 'Favorite';

        btn.appendChild(svg);
        btn.appendChild(labelSpan);
    }

    function configurePdfSection(book, cleanTitle) {
        const pdfIframe = document.getElementById('pdfIframe');
        const downloadLink = document.getElementById('downloadLink');
        const openPdfNewTab = document.getElementById('openPdfNewTab');
        const readingModeTitle = document.getElementById('readingModeTitle');
        const pdfStatusText = document.getElementById('pdfStatusText');
        const pdfFallbackMessage = document.getElementById('pdfFallbackMessage');
        const pdfFallbackBody = document.getElementById('pdfFallbackBody');

        if (readingModeTitle) {
            readingModeTitle.textContent = `Reading: ${cleanTitle}`;
        }

        const safePdf = isSafeAssetPath(book.pdf) ? book.pdf.trim() : '';

        if (!safePdf) {
            if (pdfStatusText) pdfStatusText.textContent = 'PDF file unavailable.';
            if (pdfFallbackMessage) {
                pdfFallbackMessage.classList.remove('hidden');
                pdfFallbackMessage.classList.add('flex');
            }
            if (pdfFallbackBody) {
                pdfFallbackBody.textContent = 'No valid PDF file is associated with this book.';
            }
            if (downloadLink) {
                downloadLink.removeAttribute('href');
                downloadLink.setAttribute('aria-disabled', 'true');
                downloadLink.classList.add('opacity-50', 'pointer-events-none');
            }
            if (openPdfNewTab) {
                openPdfNewTab.classList.add('hidden');
            }
            return;
        }

        const safeFilename = cleanTitle.replace(/[^a-zA-Z0-9_-]+/g, '_') + '.pdf';
        if (downloadLink) {
            downloadLink.href = safePdf;
            downloadLink.setAttribute('download', safeFilename);
            downloadLink.setAttribute('aria-label', `Download ${cleanTitle} as PDF`);
        }
        if (openPdfNewTab) {
            openPdfNewTab.href = safePdf;
            openPdfNewTab.setAttribute('aria-label', `Open ${cleanTitle} PDF in a new browser tab`);
        }
        if (pdfIframe) {
            pdfIframe.title = `PDF Reader for ${cleanTitle}`;
        }

        // Verify that the PDF asset actually exists via HEAD request
        fetch(safePdf, { method: 'HEAD' })
            .then(response => {
                if (!response.ok) {
                    throw new Error(`PDF HTTP status ${response.status}`);
                }
                if (pdfIframe) {
                    pdfIframe.src = safePdf;
                }
                if (pdfStatusText) {
                    pdfStatusText.textContent = 'If the PDF does not display in your browser, use "Open in New Tab" or "Download Book".';
                }
            })
            .catch(() => {
                if (pdfStatusText) {
                    pdfStatusText.textContent = 'The PDF file could not be loaded.';
                }
                if (pdfFallbackMessage) {
                    pdfFallbackMessage.classList.remove('hidden');
                    pdfFallbackMessage.classList.add('flex');
                }
                if (pdfFallbackBody) {
                    pdfFallbackBody.textContent = 'The PDF file for this book is currently unavailable on the server.';
                }
                if (downloadLink) {
                    downloadLink.removeAttribute('href');
                    downloadLink.setAttribute('aria-disabled', 'true');
                    downloadLink.classList.add('opacity-50', 'pointer-events-none');
                }
                if (openPdfNewTab) {
                    openPdfNewTab.classList.add('hidden');
                }
            });
    }

    document.addEventListener('DOMContentLoaded', function () {
        const homeButton = document.getElementById('homeButton');
        if (homeButton) {
            homeButton.addEventListener('click', function () {
                window.location.href = 'index.html';
            });
        }

        const urlParams = new URLSearchParams(window.location.search);
        const rawBookId = urlParams.get('id');
        const bookId = rawBookId && /^\d+$/.test(rawBookId.trim()) ? rawBookId.trim() : '';

        const bookCover = document.getElementById('bookCover');
        const bookDetailsContainer = document.getElementById('bookDetailsContainer');

        if (!bookId) {
            renderErrorState(bookDetailsContainer, 'Book ID is missing or invalid.');
            configurePdfSection({}, 'Unavailable Book');
            return;
        }

        fetch(`src/books/get_book_details.php?id=${encodeURIComponent(bookId)}`)
            .then(response => {
                return response.json().then(data => ({ ok: response.ok, status: response.status, data }));
            })
            .then(({ ok, data: book }) => {
                if (!ok || !book || book.error) {
                    const errMsg = book && book.error ? String(book.error) : 'Failed to load book details.';
                    renderErrorState(bookDetailsContainer, errMsg);
                    configurePdfSection({}, 'Unavailable Book');
                    return;
                }

                const cleanTitle = normalizeText(book.title) || 'Untitled Book';
                const cleanAuthors = normalizeText(book.authors)
                    ? `by ${normalizeText(book.authors)}`
                    : 'Unknown Author';

                document.title = `${cleanTitle} — Cyber Reads`;

                if (bookCover) {
                    bookCover.src = isSafeAssetPath(book.cover) ? book.cover.trim() : FALLBACK_COVER_PATH;
                    bookCover.alt = `${cleanTitle} cover`;
                    bookCover.addEventListener('error', () => {
                        if (bookCover.src.indexOf(FALLBACK_COVER_PATH) === -1) {
                            bookCover.src = FALLBACK_COVER_PATH;
                        }
                    });
                }

                recordRecentlyViewed(book);

                // Build Book Details DOM safely without innerHTML
                bookDetailsContainer.textContent = '';

                const topContent = document.createElement('div');
                topContent.className = 'flex-grow';

                const h1 = document.createElement('h1');
                h1.className = 'font-extrabold text-3xl sm:text-4xl lg:text-5xl mb-2 text-gray-900 tracking-tight leading-tight';
                h1.textContent = cleanTitle;

                const authorP = document.createElement('p');
                authorP.className = 'text-lg sm:text-xl text-gray-700 font-medium mb-6';
                authorP.textContent = cleanAuthors;

                const categoriesWrap = document.createElement('div');
                categoriesWrap.id = 'bookCategories';
                categoriesWrap.className = 'gap-2.5 mb-6 flex flex-wrap';

                if (book.categories && typeof book.categories === 'string') {
                    const cats = book.categories
                        .split(',')
                        .map(c => c.trim())
                        .filter(Boolean);
                    cats.forEach(cat => {
                        const badge = document.createElement('span');
                        badge.className = 'px-3.5 py-1.5 rounded-full bg-purple-100 border border-purple-200 text-xs sm:text-sm font-semibold text-purple-950';
                        badge.textContent = cat;
                        categoriesWrap.appendChild(badge);
                    });
                } else {
                    const fallbackBadge = document.createElement('span');
                    fallbackBadge.className = 'px-3.5 py-1.5 rounded-full bg-gray-200 text-xs sm:text-sm font-medium text-gray-700';
                    fallbackBadge.textContent = 'General Fiction';
                    categoriesWrap.appendChild(fallbackBadge);
                }

                const descCard = document.createElement('div');
                descCard.className = 'rounded-2xl mb-8 bg-white/80 border border-gray-200 shadow-sm p-5 sm:p-6';

                const descBox = document.createElement('div');
                descBox.id = 'bookDescription';
                descBox.className = 'leading-relaxed text-base sm:text-lg text-gray-800 space-y-4';

                const rawDesc = typeof book.description === 'string' && book.description.trim() !== ''
                    ? book.description.trim()
                    : 'No description available for this book.';

                const paragraphs = rawDesc.split(/\r?\n\s*\r?\n|\r?\n/).map(p => p.trim()).filter(Boolean);
                paragraphs.forEach(paraText => {
                    const p = document.createElement('p');
                    p.textContent = paraText;
                    descBox.appendChild(p);
                });

                descCard.appendChild(descBox);

                topContent.appendChild(h1);
                topContent.appendChild(authorP);
                topContent.appendChild(categoriesWrap);
                topContent.appendChild(descCard);

                // Actions Bar: Read Now, Favorite, Share + Share Feedback
                const actionsSection = document.createElement('div');
                actionsSection.className = 'mt-auto pt-4 border-t border-gray-300/80';

                const buttonsRow = document.createElement('div');
                buttonsRow.className = 'gap-3 flex flex-col sm:flex-row items-stretch sm:items-center';

                // Read Now Link
                const readNowLink = document.createElement('a');
                readNowLink.href = '#readNow';
                readNowLink.className = 'font-semibold text-white btn-primary flex-1 py-3.5 px-6 rounded-xl text-center hover-lift focus:outline-none focus:ring-2 focus:ring-purple-600 focus:ring-offset-2 inline-flex items-center justify-center gap-2';
                readNowLink.innerHTML =
                    '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>' +
                    '<span>Read Now</span>';

                // Feature 01: Favorite Control on Book Details Page
                const favBtn = document.createElement('button');
                favBtn.type = 'button';
                favBtn.id = 'detailFavoriteBtn';
                updateFavoriteButtonUI(favBtn, isBookFavorite(bookId), cleanTitle);

                // Feature 03: Share Book Button
                const shareBtn = document.createElement('button');
                shareBtn.type = 'button';
                shareBtn.id = 'shareBookBtn';
                shareBtn.setAttribute('aria-label', `Share ${cleanTitle}`);
                shareBtn.className = 'inline-flex items-center justify-center gap-2 px-5 py-3.5 rounded-xl font-semibold border border-gray-300 bg-white text-gray-800 hover:bg-gray-100 transition-colors focus:outline-none focus:ring-2 focus:ring-purple-600';
                shareBtn.innerHTML =
                    '<svg class="w-5 h-5 text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z"/></svg>' +
                    '<span>Share</span>';

                const feedbackP = document.createElement('p');
                feedbackP.id = 'shareFeedback';
                feedbackP.setAttribute('role', 'status');
                feedbackP.setAttribute('aria-live', 'polite');
                feedbackP.className = 'text-sm font-medium text-purple-900 mt-2 min-h-[1.25rem]';

                let feedbackTimer = null;
                function showFeedbackMessage(msg, isError = false) {
                    if (feedbackTimer) clearTimeout(feedbackTimer);
                    feedbackP.textContent = msg;
                    feedbackP.className = isError
                        ? 'text-sm font-medium text-red-700 mt-2 min-h-[1.25rem]'
                        : 'text-sm font-medium text-purple-900 mt-2 min-h-[1.25rem]';
                    feedbackTimer = setTimeout(() => {
                        feedbackP.textContent = '';
                    }, 3500);
                }

                favBtn.addEventListener('click', () => {
                    const nowFav = toggleFavoriteBook(bookId);
                    updateFavoriteButtonUI(favBtn, nowFav, cleanTitle);
                    showFeedbackMessage(
                        nowFav ? 'Added to your favorite books.' : 'Removed from your favorite books.'
                    );
                });

                shareBtn.addEventListener('click', async () => {
                    const canonicalUrl = new URL(`bookRead.html?id=${encodeURIComponent(bookId)}`, window.location.href).href;
                    const shareData = {
                        title: `${cleanTitle} — Cyber Reads`,
                        text: `Read "${cleanTitle}" (${cleanAuthors}) on Cyber Reads`,
                        url: canonicalUrl
                    };

                    const canUseWebShare =
                        navigator.share &&
                        typeof navigator.share === 'function' &&
                        !/HeadlessChrome/i.test(navigator.userAgent || '') &&
                        (!navigator.canShare || navigator.canShare(shareData));

                    if (canUseWebShare) {
                        try {
                            await navigator.share(shareData);
                            showFeedbackMessage('Book shared successfully!');
                            return;
                        } catch (err) {
                            if (err && err.name === 'AbortError') return;
                            // Fall through to clipboard copy fallback
                        }
                    }

                    try {
                        await copyTextToClipboard(canonicalUrl);
                        showFeedbackMessage('Book link copied to clipboard!');
                    } catch (err) {
                        showFeedbackMessage(`Could not copy automatically — Share link: ${canonicalUrl}`, true);
                    }
                });

                window.addEventListener('storage', (e) => {
                    if (!e.key || e.key === FAVORITES_STORAGE_KEY) {
                        updateFavoriteButtonUI(favBtn, isBookFavorite(bookId), cleanTitle);
                    }
                });

                buttonsRow.appendChild(readNowLink);
                buttonsRow.appendChild(favBtn);
                buttonsRow.appendChild(shareBtn);

                actionsSection.appendChild(buttonsRow);
                actionsSection.appendChild(feedbackP);

                bookDetailsContainer.appendChild(topContent);
                bookDetailsContainer.appendChild(actionsSection);

                configurePdfSection(book, cleanTitle);
            })
            .catch(error => {
                console.error('Error fetching book details:', error);
                renderErrorState(bookDetailsContainer, 'Failed to load book details.');
                configurePdfSection({}, 'Unavailable Book');
            });
    });
})();
