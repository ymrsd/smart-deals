<?php
require __DIR__ . '/lib.php';
header('Content-Type: application/xml; charset=utf-8');
$products = load_products();
$urls = [[SITE_URL.'/', date('Y-m-d'), 'daily', '1.0']];
foreach (CAT_NAMES as $slug=>$n) $urls[] = [SITE_URL."/category/$slug", date('Y-m-d'), 'daily', '0.8'];
foreach ($products as $slug=>$items) foreach ($items as $p) $urls[] = [SITE_URL.'/product/'.$p['id'], date('Y-m-d'), 'weekly', '0.6'];
echo '<?xml version="1.0" encoding="UTF-8"?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
';
foreach ($urls as [$loc,$lastmod,$freq,$pri])
    echo "<url><loc>".htmlspecialchars($loc)."</loc><lastmod>$lastmod</lastmod><changefreq>$freq</changefreq><priority>$pri</priority></url>
";
echo '</urlset>';
