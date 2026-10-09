<?php
declare(strict_types=1);

header('Content-Type: application/xml; charset=UTF-8');

$base = 'https://cryptoshit.info';
$urls = [
    '/',
    '/lore',
    '/oracle',
    '/draw',
    '/press',
    '/legal',
];

for ($id = 1; $id <= 128; $id++) {
    $urls[] = '/crtsht/' . $id;
}

echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
echo "<urlset xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\">\n";
foreach ($urls as $path) {
    echo "  <url><loc>" . htmlspecialchars($base . $path, ENT_XML1 | ENT_QUOTES, 'UTF-8') . "</loc></url>\n";
}
echo "</urlset>\n";
