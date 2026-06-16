<?php

use App\Support\SearchSummaryText;

test('search summary limiter ends at sentence boundary when possible', function () {
    $text = 'This project focuses on bioinspired and nature-inspired materials for experimental mechanics research. '
        .str_repeat('Additional context about methods and tools. ', 20);

    $limited = SearchSummaryText::limit($text, 400);

    expect(mb_strlen($limited))->toBeLessThanOrEqual(400);
    expect($limited)->toEndWith('.');
    expect($limited)->toContain('bioinspired');
});

test('search summary limiter falls back to word boundary without mid-word cut', function () {
    $text = str_repeat('experimental mechanics ', 30).'bioinspired nature-inspired';

    $limited = SearchSummaryText::limit($text, 200);
    $len = mb_strlen($limited);

    expect($len)->toBeLessThanOrEqual(200);
    if ($len < mb_strlen($text)) {
        expect(mb_substr($text, $len, 1))->toBe(' ');
    }
    expect($limited)->not->toContain('bioinspired');
});

test('search summary limiter leaves short text unchanged', function () {
    $text = 'Short complete summary.';

    expect(SearchSummaryText::limit($text, 600))->toBe($text);
});

test('search summary limiter handles real cropped-style text', function () {
    $text = 'This project focuses on the design and development of advanced robotic skins using metamaterials for enhanced tactile sensing and impact absorption. Utilizing advanced additive manufacturing techniques, the research aims to create soft robotics and materials science applications. Key tools include finite element analysis and experimental validation methods for nature-inspired architected materials.';

    $limited = SearchSummaryText::limit($text, 400);

    expect(mb_strlen($limited))->toBeLessThanOrEqual(400);
    expect($limited)->toMatch('/[.!?]$/u');
    expect($limited)->not->toBe(mb_substr($text, 0, 400));
});
