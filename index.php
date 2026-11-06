<?php
require __DIR__ . '/lib.php'; auto_refresh();
$products = load_products();
$title = 'SmartDeals.lk – AliExpress Best Deals & Trending Items in Sri Lanka';
$desc  = 'AliExpress trending items සිංහලෙන් — electronics, gadgets, fashion, home, beauty. Daily updated deals.';
?><!DOCTYPE html><html lang="si"><head><?php base_head($title, $desc); ?><?= STYLES ?>
<script type="application/ld+json"><?= json_encode([
  "@context"=>"https://schema.org","@type"=>"WebSite","name"=>"SmartDeals.lk","url"=>SITE_URL."/","description"=>$desc],
  JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?></script>
</head><body>
<?php header_html(); ?>
<section class="hero"><h1>හොඳම <span>Trending Deals</span><br>දැන් එකම තැනකින්</h1>
<p>AliExpress හි ජනප්‍රිය gadgets, fashion සහ තවත් දේවල් — ශ්‍රී ලංකාවට ගැළපෙන තේරීම් සිංහලෙන්.</p>
<a class="hero-cta" href="#deals">අද deals බලන්න <span aria-hidden="true">&nbsp;↓</span></a></section>
<div class="container">
<div id="deals">
<?php foreach (CAT_NAMES as $slug=>$name):
    $items = $products[$slug] ?? []; ?>
  <div class="section-heading"><h2 class="section-title"><?= esc($name) ?></h2><a class="section-link" href="/category/<?= esc($slug) ?>">සියල්ල බලන්න →</a></div>
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
  <?php endforeach; if (empty($items)) echo '<p class="empty-state">දැනට deals නැහැ. කරුණාකර පසුව නැවත බලන්න.</p>'; ?>
  </div>
<?php endforeach; ?>
</div>
</div>
<?php footer_html(); ?></body></html>
