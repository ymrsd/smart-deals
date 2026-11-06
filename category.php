<?php
require __DIR__ . '/lib.php'; auto_refresh();
$rawSlug = $_GET['slug'] ?? '';
$slug = is_string($rawSlug) ? preg_replace('/[^a-z0-9-]/', '', $rawSlug) : '';
if (!isset(CAT_NAMES[$slug])) {
    http_response_code(404);
    $title = 'Category not found – SmartDeals.lk';
    ?><!DOCTYPE html><html lang="si"><head><?php base_head($title, 'මෙම category එක සොයාගත නොහැක.'); ?><?= STYLES ?></head><body>
    <?php header_html(); ?><main class="container"><div class="empty-state"><h1>Category එක සොයාගත නොහැක</h1><p class="not-found-copy"><a class="section-link" href="/">මුල් පිටුවට යන්න →</a></p></div></main><?php footer_html(); ?></body></html><?php
    exit;
}
$products = load_products();
$items = $products[$slug] ?? [];
$name = CAT_NAMES[$slug];
$title = "$name Deals – SmartDeals.lk | AliExpress Best Prices";
$desc  = "AliExpress trending $name items — best prices, daily updated. අඩුම මිලට හොඳම items.";
?><!DOCTYPE html><html lang="si"><head><?php base_head($title, $desc); ?><?= STYLES ?>
<script type="application/ld+json"><?= json_encode([
  "@context"=>"https://schema.org","@type"=>"ItemList","name"=>"$name Deals",
  "itemListElement"=>array_map(fn($p,$i)=>["@type"=>"ListItem","position"=>$i+1,
    "item"=>["@type"=>"Product","name"=>$p['title'],"image"=>$p['image'],
      "offers"=>["@type"=>"Offer","price"=>$p['price'],"priceCurrency"=>"USD","availability"=>"https://schema.org/InStock"],
      "url"=>SITE_URL."/product/".$p['id']]], $items, array_keys($items))],
  JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?></script>
</head><body>
<?php header_html(); ?>
<section class="hero"><h1><?= esc($name) ?> <span>Deals</span></h1><p>ඔබට ගැළපෙන ජනප්‍රිය තේරීම් සොයාගන්න.</p></section>
<div class="container">
<div class="breadcrumb"><a href="/">Home</a> / <?= esc($name) ?></div>
<div class="grid">
<?php foreach ($items as $p): ?>
  <div class="card">
    <a href="/product/<?= esc($p['id']) ?>"><img src="<?= esc($p['image']) ?>" alt="<?= esc($p['title']) ?>" loading="lazy"></a>
    <div class="card-body">
      <h3><a href="/product/<?= esc($p['id']) ?>"><?= esc($p['title']) ?></a></h3>
      <div class="price">$<?= esc($p['price']) ?></div>
      <a class="btn" href="<?= esc($p['url']) ?>" rel="nofollow sponsored noopener" target="_blank">View Deal →</a>
    </div>
  </div>
<?php endforeach; ?>
<?php if (empty($items)): ?><p class="empty-state">මෙම category එකට දැනට deals නැහැ. කරුණාකර පසුව නැවත බලන්න.</p><?php endif; ?>
</div></div>
<?php footer_html(); ?></body></html>
