<?php

use App\Services\TextLinkifier;

test('text without url is only escaped', function () {
    expect(TextLinkifier::toHtml('Dossier <b>incomplet</b> & refusé'))
        ->toBe('Dossier &lt;b&gt;incomplet&lt;/b&gt; &amp; refusé');
});

test('http and https urls become links', function () {
    expect(TextLinkifier::toHtml('Voir https://www.e-f-y.ch pour aide'))
        ->toBe('Voir <a href="https://www.e-f-y.ch" target="_blank" rel="noopener noreferrer">https://www.e-f-y.ch</a> pour aide');

    expect(TextLinkifier::toHtml('http://example.com/page?a=1&b=2'))
        ->toBe('<a href="http://example.com/page?a=1&amp;b=2" target="_blank" rel="noopener noreferrer">http://example.com/page?a=1&amp;b=2</a>');
});

test('www urls get an https link', function () {
    expect(TextLinkifier::toHtml('Allez sur www.e-f-y.ch'))
        ->toBe('Allez sur <a href="https://www.e-f-y.ch" target="_blank" rel="noopener noreferrer">www.e-f-y.ch</a>');
});

test('trailing punctuation stays out of the link', function () {
    expect(TextLinkifier::toHtml('Voir https://e-f-y.ch. Merci'))
        ->toBe('Voir <a href="https://e-f-y.ch" target="_blank" rel="noopener noreferrer">https://e-f-y.ch</a>. Merci');

    expect(TextLinkifier::toHtml('(https://e-f-y.ch/aide)'))
        ->toBe('(<a href="https://e-f-y.ch/aide" target="_blank" rel="noopener noreferrer">https://e-f-y.ch/aide</a>)');
});

test('html around the url cannot be injected', function () {
    expect(TextLinkifier::toHtml('<https://e-f-y.ch>"onclick="x'))
        ->toBe('&lt;<a href="https://e-f-y.ch" target="_blank" rel="noopener noreferrer">https://e-f-y.ch</a>&gt;&quot;onclick=&quot;x');
});

test('a css class can be added to links', function () {
    expect(TextLinkifier::toHtml('https://e-f-y.ch', 'link'))
        ->toBe('<a href="https://e-f-y.ch" class="link" target="_blank" rel="noopener noreferrer">https://e-f-y.ch</a>');
});

test('multiline html keeps line breaks, links and readable quotes', function () {
    expect(TextLinkifier::toMultilineHtml("L'équipe\nhttps://e-f-y.ch"))
        ->toBe("L'équipe<br />\n<a href=\"https://e-f-y.ch\" target=\"_blank\" rel=\"noopener noreferrer\">https://e-f-y.ch</a>");
});

test('invalid utf-8 is scrubbed instead of failing', function () {
    expect(TextLinkifier::toHtml("Texte \xC3( https://e-f-y.ch"))
        ->toBe('Texte ?( <a href="https://e-f-y.ch" target="_blank" rel="noopener noreferrer">https://e-f-y.ch</a>')
        ->and(TextLinkifier::toMultilineHtml("Texte \xC3("))->toBe('Texte ?(');
});
