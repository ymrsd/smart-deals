<?php
// ============================================
//  fetch_products.php — 5 categories x 5 trending items
//  auto ගන්නවා → products.json cache එකට save
//  Cron: 0 */6 * * * php /home/USER/public_html/fetch_products.php
// ============================================
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require __DIR__ . '/config.php';

if (APP_SECRET === 'PUT_YOUR_APP_SECRET_HERE' || TRACKING_ID === 'YOUR_TRACKING_ID') {
    fwrite(STDERR, "Configure APP_SECRET and TRACKING_ID in config.php before fetching products.\n");
    exit(1);
}

function ali_sign($params, $secret) {
    ksort($params);
    $str = $secret;
    foreach ($params as $k => $v) $str .= $k . $v;
    return strtoupper(md5($str . $secret));
}

function api_fetch($keyword) {
    $params = [
        'app_key' => APP_KEY, 'method' => 'aliexpress.affiliate.product.query',
        'timestamp' => date('Y-m-d H:i:s'), 'format' => 'json', 'v' => '2.0',
        'sign_method' => 'md5', 'partner_id' => 'apidoc',
        'keywords' => $keyword, 'page_no' => 1, 'page_size' => 5,
        'sort' => 'LAST_VOLUME_DESC',  // trending items
        'target_currency' => 'USD', 'target_language' => 'EN',
        'tracking_id' => TRACKING_ID,
    ];
    $params['sign'] = ali_sign($params, APP_SECRET);
    $ch = curl_init(GATEWAY . '?' . http_build_query($params));
    if ($ch === false) throw new RuntimeException('Could not initialize cURL.');
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 30]);
    $resp = curl_exec($ch);
    if ($resp === false) {
        $error = curl_error($ch);
        curl_close($ch);
        throw new RuntimeException('AliExpress request failed: ' . $error);
    }
    $status = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    curl_close($ch);
    if ($status < 200 || $status >= 300) throw new RuntimeException('AliExpress returned HTTP ' . $status . '.');
    $d = json_decode($resp, true);
    if (!is_array($d)) throw new RuntimeException('AliExpress returned invalid JSON.');
    if (isset($d['error_response'])) {
        throw new RuntimeException('AliExpress API error: ' . json_encode($d['error_response']));
    }
    return $d['aliexpress_affiliate_product_query_response']['resp_result']['products']['product'] ?? [];
}

$data = [];
foreach (CATEGORIES as $slug => $keywords) {
    $items = [];
    $seen = [];
    foreach (array_slice($keywords, 0, 5) as $kw) {
        if (count($items) >= 5) break;
        foreach (api_fetch($kw) as $p) {
            if (!is_array($p)) continue;
            $id = (string)($p['product_id'] ?? '');
            $url = (string)($p['promotion_link'] ?? $p['product_detail_url'] ?? '');
            $title = (string)($p['product_title'] ?? '');
            $image = (string)($p['product_main_image_url'] ?? '');
            if ($id === '' || $url === '' || $title === '' || $image === '' || isset($seen[$id])) continue;
            $seen[$id] = true;
            $items[] = [
                'id'    => $id,
                'title' => $title,
                'image' => $image,
                'price' => $p['target_sale_price'] ?? $p['sale_price'] ?? '',
                'url'   => $url,
                'desc'  => mb_substr(strip_tags($title), 0, 150),
            ];
            if (count($items) >= 5) break;
        }
        if (count($items) < 5) usleep(500000);
    }
    $data[$slug] = array_slice($items, 0, 5);
}

$json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
if ($json === false || file_put_contents(CACHE_FILE, $json, LOCK_EX) === false) {
    throw new RuntimeException('Could not save product cache to ' . CACHE_FILE . '.');
}
echo 'Saved: ' . json_encode(array_map('count', $data)) . "\n";
