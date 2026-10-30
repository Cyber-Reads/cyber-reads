/**
 * Cyber Reads — Automated Frontend Logic Regression Tests
 * Run with: node tests/test.js
 */

const assert = require('node:assert/strict');

// Setup mock window.localStorage before requiring script.js
const storageMap = new Map();
let blockStorage = false;

global.window = {
    localStorage: {
        getItem(key) {
            if (blockStorage) throw new Error('SecurityError: Storage is disabled');
            return storageMap.has(key) ? storageMap.get(key) : null;
        },
        setItem(key, value) {
            if (blockStorage) throw new Error('SecurityError: Storage is disabled');
            storageMap.set(key, String(value));
        },
        removeItem(key) {
            if (blockStorage) throw new Error('SecurityError: Storage is disabled');
            storageMap.delete(key);
        }
    }
};

const {
    normalizeBookTitle,
    isSafeAssetPath,
    getSafeCoverUrl,
    getFavoriteIds,
    saveFavoriteIds,
    isBookFavorite,
    toggleFavoriteId,
    sortBooksList,
    filterAndSortBooks
} = require('../assets/js/script.js');

let passed = 0;
function test(name, fn) {
    fn();
    passed++;
    console.log(`  [PASS] ${name}`);
}

console.log('Running Cyber Reads Frontend Regression Tests...\n');

test('normalizeBookTitle collapses multiple spaces and trims whitespace', () => {
    assert.equal(normalizeBookTitle('  THE LAST STAND OF  KAEL '), 'THE LAST STAND OF KAEL');
    assert.equal(normalizeBookTitle('Silent Witness'), 'Silent Witness');
    assert.equal(normalizeBookTitle(null), '');
    assert.equal(normalizeBookTitle(undefined), '');
});

test('isSafeAssetPath allows valid relative paths and blocks XSS / traversal vectors', () => {
    assert.equal(isSafeAssetPath('books/cover/cover1.jpg'), true);
    assert.equal(isSafeAssetPath('books/pdf/leadMe.PDF'), true);
    assert.equal(isSafeAssetPath('javascript:alert(1)'), false);
    assert.equal(isSafeAssetPath('data:text/html,<script>alert(1)</script>'), false);
    assert.equal(isSafeAssetPath('../../etc/passwd'), false);
    assert.equal(isSafeAssetPath('books/cover/../secret.php'), false);
    assert.equal(isSafeAssetPath('<img src=x onerror=alert(1)>'), false);
    assert.equal(getSafeCoverUrl('javascript:alert(1)'), 'assets/img/1.jpg');
});

test('sortBooksList sorts A-Z and Z-A without mutating original array', () => {
    const sampleBooks = [
        { bookid: '1', title: 'Walker in the Gloom' },
        { bookid: '2', title: '  Lead Me Through  The Fire ' },
        { bookid: '3', title: 'Mind Bloom' }
    ];
    const snapshot = JSON.stringify(sampleBooks);

    const asc = sortBooksList(sampleBooks, 'title-asc');
    assert.deepEqual(asc.map(b => b.bookid), ['2', '3', '1']);
    assert.equal(JSON.stringify(sampleBooks), snapshot, 'Original array must not be mutated');

    const desc = sortBooksList(sampleBooks, 'title-desc');
    assert.deepEqual(desc.map(b => b.bookid), ['1', '3', '2']);
    assert.equal(JSON.stringify(sampleBooks), snapshot, 'Original array must not be mutated');

    const def = sortBooksList(sampleBooks, 'default');
    assert.deepEqual(def.map(b => b.bookid), ['1', '2', '3']);
});

test('Favorites persistence stores stable numeric book IDs and toggles accurately', () => {
    storageMap.clear();
    blockStorage = false;

    saveFavoriteIds(['1', '5', '1', 'invalid', '']);
    assert.deepEqual(getFavoriteIds(), ['1', '5']);
    assert.equal(isBookFavorite(1), true);
    assert.equal(isBookFavorite('5'), true);
    assert.equal(isBookFavorite('2'), false);

    // Toggle off existing favorite
    const afterRemove = toggleFavoriteId('1');
    assert.equal(afterRemove, false);
    assert.deepEqual(getFavoriteIds(), ['5']);

    // Toggle on new favorite
    const afterAdd = toggleFavoriteId(8);
    assert.equal(afterAdd, true);
    assert.deepEqual(getFavoriteIds(), ['5', '8']);
});

test('Favorites gracefully fall back to in-memory storage when localStorage is blocked', () => {
    storageMap.clear();
    blockStorage = true;

    const added = toggleFavoriteId('3');
    assert.equal(added, true);
    assert.equal(isBookFavorite('3'), true);

    const removed = toggleFavoriteId('3');
    assert.equal(removed, false);
    assert.equal(isBookFavorite('3'), false);

    blockStorage = false;
});

test('filterAndSortBooks combines favorites filtering with title sorting', () => {
    const books = [
        { bookid: '10', title: 'THE SHADOW OF AETHELGARD' },
        { bookid: '4', title: 'Lead Me Through The Fire' },
        { bookid: '5', title: 'Mind Bloom' },
        { bookid: '1', title: 'Silent Witness' }
    ];

    const result = filterAndSortBooks(books, {
        favoriteIds: ['10', '4', '1'],
        favoritesOnly: true,
        sortOrder: 'title-asc'
    });

    assert.deepEqual(result.map(b => b.bookid), ['4', '1', '10']);
});

console.log(`\nAll ${passed} frontend regression tests passed!`);
