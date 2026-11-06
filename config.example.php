<?php
// Copy this file to config.php and enter your private AliExpress credentials.
define('APP_KEY',     'YOUR_APP_KEY');
define('APP_SECRET',  'PUT_YOUR_APP_SECRET_HERE');
define('TRACKING_ID', 'YOUR_TRACKING_ID');
define('GATEWAY',     'https://eco.taobao.com/router/rest');
define('CACHE_FILE',  __DIR__ . '/products.json');
define('CACHE_TTL',   6 * 3600);
define('SITE_URL',    'https://example.com');

const CATEGORIES = [
    'electronics' => ['wireless earbuds', 'bluetooth speaker', 'smart watch', 'fast charger', 'power bank'],
    'gadgets'     => ['led strip lights', 'mini projector', 'wifi camera', 'usb hub', 'phone holder'],
    'fashion'     => ['men sneakers', 'women handbag', 'sunglasses', 'smart ring', 'leather wallet'],
    'home'        => ['kitchen organizer', 'led night light', 'vacuum cleaner', 'storage box', 'air purifier'],
    'beauty'      => ['skincare tools', 'hair dryer', 'makeup brush set', 'facial massager', 'nail kit'],
];
const CAT_NAMES = [
    'electronics' => 'Electronics', 'gadgets' => 'Gadgets', 'fashion' => 'Fashion',
    'home' => 'Home & Garden', 'beauty' => 'Beauty',
];
