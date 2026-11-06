<?php
// Shared helpers — හැම page එකකටම
require_once __DIR__ . '/config.php';
function load_products() {
    if (!is_file(CACHE_FILE)) return [];

    $json = file_get_contents(CACHE_FILE);
    if ($json === false) {
        error_log('SmartDeals: could not read product cache.');
        return [];
    }

    $products = json_decode($json, true);
    if (!is_array($products)) {
        error_log('SmartDeals: product cache contains invalid JSON.');
        return [];
    }
    return $products;
}
function auto_refresh() {
    if (APP_SECRET === 'PUT_YOUR_APP_SECRET_HERE' || TRACKING_ID === 'YOUR_TRACKING_ID') return;
    $lastRefresh = is_file(CACHE_FILE) ? filemtime(CACHE_FILE) : false;
    if ($lastRefresh !== false && time() - $lastRefresh <= CACHE_TTL) return;
    if (!function_exists('exec') || PHP_OS_FAMILY === 'Windows') return;

    $command = escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg(__DIR__ . '/fetch_products.php') . ' > /dev/null 2>&1 &';
    exec($command);
}
function find_product($id) {
    foreach (load_products() as $slug => $items)
        foreach ($items as $p) if (($p['id'] ?? null) === $id) return [$slug, $p];
    return [null, null];
}
function esc($s){ return htmlspecialchars((string)($s ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
function base_head($title, $desc){ ?>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= esc($title) ?></title>
<meta name="description" content="<?= esc($desc) ?>"><meta name="robots" content="index, follow">
<link rel="canonical" href="<?= esc(rtrim(SITE_URL, '/') . (parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/')) ?>">
<meta property="og:type" content="website"><meta property="og:title" content="<?= esc($title) ?>">
<meta property="og:description" content="<?= esc($desc) ?>">
<meta property="og:url" content="<?= esc(rtrim(SITE_URL, '/') . (parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/')) ?>">
<meta name="twitter:card" content="summary_large_image">
<?php }
function header_html(){ ?>
<header class="site-header"><div class="header-inner"><a class="logo" href="/" aria-label="SmartDeals.lk home">Smart<span>Deals</span><small>.lk</small></a>
<nav aria-label="Main navigation"><?php foreach (CAT_NAMES as $slug=>$n): ?><a href="/category/<?= esc($slug) ?>"><?= esc($n) ?></a><?php endforeach; ?></nav></div></header>
<?php }
function footer_html(){ ?>
<footer><div class="footer-inner"><p class="footer-brand">SmartDeals.lk <span>— හොඳම deals, එකම තැනක.</span></p>
<p><strong>Affiliate Disclosure:</strong> මෙම links හරහා AliExpress products මිලදී ගත්තොත් අපට commission එකක් ලැබිය හැකියි. ඔබට අමතර වියදමක් නැහැ.</p>
<p class="copyright">© <?= date('Y') ?> SmartDeals.lk</p></div></footer>
<?php }
const STYLES = "<style>
:root{color-scheme:light;--primary:#f04b31;--primary-dark:#d93820;--ink:#17212b;--muted:#66727d;--line:#e8edf1;--paper:#fff;--canvas:#f5f7f8;--dark:#152330}
*{margin:0;padding:0;box-sizing:border-box}
html{scroll-behavior:smooth}
body{background:var(--canvas);color:var(--ink);font-family:Inter,'Segoe UI',Arial,sans-serif;line-height:1.6;-webkit-font-smoothing:antialiased}
a{color:inherit}
.site-header{position:sticky;top:0;z-index:100;background:rgba(21,35,48,.97);box-shadow:0 4px 18px rgba(15,27,38,.12);backdrop-filter:blur(12px)}
.header-inner{max-width:1240px;min-height:72px;margin:auto;padding:12px 24px;display:flex;align-items:center;justify-content:space-between;gap:24px}
.logo{color:#fff;font-size:1.38rem;font-weight:800;letter-spacing:-.7px;text-decoration:none;white-space:nowrap}.logo span{color:#ff735d}.logo small{font-size:.78rem;color:#bdc7cf;font-weight:600}
nav{display:flex;align-items:center;gap:6px;overflow-x:auto;scrollbar-width:none}nav::-webkit-scrollbar{display:none}
nav a{color:#d1d9df;text-decoration:none;padding:9px 12px;border-radius:8px;font-size:.88rem;white-space:nowrap;transition:background .2s,color .2s}
nav a:hover,nav a:focus-visible{background:rgba(255,255,255,.1);color:#fff}
.hero{position:relative;overflow:hidden;background:radial-gradient(ellipse at 82% 10%,rgba(240,75,49,.24),transparent 34%),linear-gradient(125deg,#152330,#223b4a 62%,#274455);color:#fff;text-align:center;padding:76px 24px 80px}
.hero:after{content:'';position:absolute;width:360px;height:360px;border:1px solid rgba(255,255,255,.08);border-radius:50%;right:-110px;bottom:-260px}
.hero h1{position:relative;font-size:clamp(2rem,4vw,3.35rem);line-height:1.2;letter-spacing:-1.3px;margin:0 auto 15px;max-width:850px}
.hero h1 span{color:#ff8069}.hero p{position:relative;color:#d0dbe1;font-size:1.08rem;max-width:680px;margin:0 auto}
.hero-cta{position:relative;display:inline-flex;align-items:center;justify-content:center;margin-top:25px;padding:12px 20px;border:1px solid rgba(255,255,255,.3);border-radius:9px;color:#fff;text-decoration:none;font-weight:700;transition:background .2s,border-color .2s}
.hero-cta:hover{background:rgba(255,255,255,.12);border-color:#fff}
.container{max-width:1240px;margin:0 auto;padding:42px 24px}
.section-heading{display:flex;align-items:end;justify-content:space-between;gap:16px;margin:0 0 20px}
.section-title{text-align:left;font-size:1.55rem;letter-spacing:-.4px}
.section-link{color:var(--primary-dark);text-decoration:none;font-weight:700;font-size:.92rem;white-space:nowrap}
.section-link:hover{text-decoration:underline}
.grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:20px}
.card{min-width:0;background:var(--paper);border:1px solid var(--line);border-radius:14px;overflow:hidden;box-shadow:0 5px 18px rgba(26,42,55,.045);transition:transform .22s,box-shadow .22s,border-color .22s}
.card:hover{transform:translateY(-4px);border-color:#dbe2e7;box-shadow:0 12px 28px rgba(26,42,55,.1)}
.card>a:first-child{display:block;background:#edf1f3;overflow:hidden}
.card img{width:100%;height:220px;object-fit:cover;display:block;transition:transform .35s}.card:hover img{transform:scale(1.035)}
.card-body{padding:16px 17px 18px}.card-body h3{font-size:.98rem;line-height:1.45;margin-bottom:9px;height:2.9em;overflow:hidden;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical}
.card-body h3 a{text-decoration:none}.card-body h3 a:hover{color:var(--primary-dark)}
.price{color:var(--primary-dark);font-size:1.25rem;font-weight:800;letter-spacing:-.3px}
.btn{display:flex;align-items:center;justify-content:center;text-align:center;background:var(--primary);color:#fff;padding:11px 14px;border-radius:8px;text-decoration:none;font-weight:700;margin-top:12px;transition:background .2s,transform .2s}
.btn:hover{background:var(--primary-dark);transform:translateY(-1px)}
.breadcrumb{font-size:.9rem;color:var(--muted);margin-bottom:20px}.breadcrumb a{color:var(--primary-dark);text-decoration:none}.breadcrumb a:hover{text-decoration:underline}
.product-detail{background:var(--paper);border:1px solid var(--line);border-radius:16px;padding:32px;display:grid;grid-template-columns:minmax(0,1fr) minmax(0,1fr);align-items:start;gap:36px;box-shadow:0 8px 26px rgba(26,42,55,.06)}
.product-detail img{width:100%;max-height:520px;object-fit:contain;background:#f4f6f7;border-radius:12px}
.product-detail h1{font-size:clamp(1.35rem,2.4vw,1.9rem);line-height:1.35;letter-spacing:-.5px}
.product-note{margin-top:24px;background:#f3f6f8;border:1px solid var(--line);border-radius:10px;padding:16px;color:#47545e;font-size:.93rem}
.empty-state{grid-column:1/-1;background:var(--paper);border:1px dashed #cbd4da;border-radius:12px;padding:28px;text-align:center;color:var(--muted)}
.not-found-copy{margin-top:8px}.product-page{padding-top:30px}.product-heading{font-size:1.5rem;margin-bottom:12px}
.product-meta{color:var(--muted);margin-bottom:8px}.product-price{font-size:2rem}
.product-buy{font-size:1.1rem}
footer{background:var(--dark);color:#b8c2c9;margin-top:55px;font-size:.88rem}
.footer-inner{max-width:1240px;margin:auto;padding:30px 24px;text-align:center}
.footer-brand{color:#fff;font-weight:800;font-size:1rem;margin-bottom:7px}.footer-brand span{color:#b8c2c9;font-weight:400;font-size:.9rem}
.copyright{border-top:1px solid rgba(255,255,255,.12);padding-top:14px;margin-top:18px;color:#8998a2;font-size:.82rem}
:focus-visible{outline:3px solid #ff9a87;outline-offset:3px}
@media(max-width:960px){.grid{grid-template-columns:repeat(3,minmax(0,1fr))}.header-inner{align-items:flex-start;flex-direction:column;gap:8px}.site-header{position:relative}nav{width:100%;margin:0 -4px;padding-bottom:2px}}
@media(max-width:700px){.hero{padding:56px 20px 60px}.hero p{font-size:1rem}.container{padding:32px 18px}.grid{grid-template-columns:repeat(2,minmax(0,1fr));gap:13px}.card img{height:180px}.card-body{padding:13px}.card-body h3{font-size:.9rem}.product-detail{grid-template-columns:1fr;padding:20px;gap:22px}.product-detail img{max-height:400px}}
@media(max-width:430px){.header-inner{padding:11px 16px}.grid{grid-template-columns:1fr}.card{display:grid;grid-template-columns:115px minmax(0,1fr);align-items:center}.card>a:first-child{height:100%;min-height:140px}.card img{height:100%;min-height:140px;object-fit:cover}.card-body h3{height:auto;max-height:2.9em}.btn{padding:9px;font-size:.9rem}.section-heading{align-items:flex-start;flex-direction:column;gap:4px}.section-title{font-size:1.35rem}}
@media(prefers-reduced-motion:reduce){html{scroll-behavior:auto}*,*:before,*:after{transition:none!important}}
</style>";
