# SmartDeals.lk

PHP affiliate-deals website with product categories, product pages, and an XML sitemap.

## Setup

1. Use PHP 8+ with cURL and Apache `mod_rewrite` enabled.
2. Copy `config.example.php` to `config.php`.
3. Set your AliExpress `APP_KEY`, `APP_SECRET`, `TRACKING_ID`, and production `SITE_URL` in `config.php`. Keep this file private; it is excluded from Git.
4. Ensure the web server can write `products.json` in this directory.
5. Run `php fetch_products.php` from the command line to retrieve the first product cache. Schedule it to run periodically (the script comments recommend every six hours).
6. Point the domain's document root at this directory and verify `.htaccess` is enabled.

The public pages are `/`, `/category/{slug}`, `/product/{id}`, and `/sitemap.xml`.

Do not commit `config.php` or `products.json`.
