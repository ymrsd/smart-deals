<?php
require __DIR__ . '/lib.php'; auto_refresh();
$rawId = $_GET['id'] ?? '';
$id = is_string($rawId) ? preg_replace('/[^A-Za-z0-9]/', '', $rawId) : '';
[$slug, $p] = find_product($id);
if (!$p) {
    http_response_code(404);
    $title = 'Product not found – SmartDeals.lk';
    ?><!DOCTYPE html><html lang="si"><head><?php base_head($title, 'මෙම product එක සොයාගත නොහැක.'); ?><?= STYLES ?></head><body>
    <?php header_html(); ?><main class="container"><div class="empty-state"><h1>Product එක සොයාගත නොහැක</h1><p class="not-found-copy"><a class="section-link" href="/">මුල් පිටුවට යන්න →</a></p></div></main><?php footer_html(); ?></body></html><?php
    exit;
}
$name = CAT_NAMES[$slug] ?? 'Deals';
$title = $p['title'] . ' – Best Price | SmartDeals.lk';
$desc  = mb_substr($p['desc'] ?: $p['title'], 0, 155);
?><!DOCTYPE html><html lang="si"><head><?php base_head($title, $desc); ?>
<meta property="og:image" content="<?= esc($p['image']) ?>">
<meta property="og:type" content="product">
<script type="application/ld+json"><?= json_encode([
  "@context"=>"https://schema.org","@type"=>"Product","name"=>$p['title'],"image"=>$p['image'],
  "description"=>$p['desc'],"category"=>$name,
  "offers"=>["@type"=>"Offer","price"=>$p['price'],"priceCurrency"=>"USD","availability"=>"https://schema.org/InStock","url"=>$p['url'],"itemCondition"=>"https://schema.org/NewCondition"]],
  JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?></script>
<?= STYLES ?></head><body>
<?php header_html(); ?>
<div class="container product-page">
<div class="breadcrumb"><a href="/">Home</a> / <a href="/category/<?= $slug ?>"><?= esc($name) ?></a> / <?= esc($p['title']) ?></div>
<div class="product-detail">
  <img src="<?= esc($p['image']) ?>" alt="<?= esc($p['title']) ?>">
  <div>
    <h1 class="product-heading"><?= esc($p['title']) ?></h1>
    <p class="product-meta">Category: <?= esc($name) ?> · Shipping details බලන්න AliExpress වෙත යන්න</p>
    <div class="price product-price">$<?= esc($p['price']) ?></div>
    <a class="btn product-buy" href="<?= esc($p['url']) ?>" rel="nofollow sponsored noopener" target="_blank">AliExpress එකෙන් මිලදී ගන්න →</a>
    <div class="product-note">
      <strong>Product Review:</strong> <?= esc($p['desc']) ?> — AliExpress trending item. ඉහළම quality sellers ලගින්, buyer protection එකත් එක්ක මිලදී ගන්න.
    </div>
  </div>
</div>
</div>
<?php footer_html(); ?></body></html>
