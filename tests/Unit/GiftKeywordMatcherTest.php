<?php

use App\Services\GiftKeywordMatcher;

test('matching ignores accents and case', function () {
    expect(GiftKeywordMatcher::matches('Vélo bleu', ['velo']))->toBeTrue();
    expect(GiftKeywordMatcher::matches('GRAND VELO', ['vélo']))->toBeTrue();
});

test('keyword can be part of a word anywhere in the gift', function () {
    expect(GiftKeywordMatcher::matches('Chaussures de foot', ['chauss']))->toBeTrue();
    expect(GiftKeywordMatcher::matches('Un super vélo', ['velo']))->toBeTrue();
});

test('one matching keyword is enough', function () {
    expect(GiftKeywordMatcher::matches('Trottinette', ['velo', 'trottinette']))->toBeTrue();
});

test('no match when no keyword is contained in the gift', function () {
    expect(GiftKeywordMatcher::matches('Puzzle', ['velo', 'trottinette']))->toBeFalse();
});

test('empty gift, empty list and blank keywords never match', function () {
    expect(GiftKeywordMatcher::matches('', ['velo']))->toBeFalse();
    expect(GiftKeywordMatcher::matches(null, ['velo']))->toBeFalse();
    expect(GiftKeywordMatcher::matches('Vélo', []))->toBeFalse();
    expect(GiftKeywordMatcher::matches('Vélo', ['', '   ']))->toBeFalse();
});
