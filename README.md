# SmartDeals.lk

SmartDeals is an AliExpress affiliate-deals website. The Netlify deployment uses a static frontend and Netlify Functions; the original PHP implementation remains available in the repository but is not part of the Netlify publish directory.

## Deploy to Netlify

1. Push this repository to GitHub and import it in Netlify using **Add new site → Import an existing project**.
2. Leave the build command empty. `netlify.toml` configures `public` as the publish directory and `netlify/functions` as the Functions directory.
3. In **Site configuration → Environment variables**, add:
   - `ALIEXPRESS_APP_KEY`
   - `ALIEXPRESS_APP_SECRET`
   - `ALIEXPRESS_TRACKING_ID`
4. Trigger a deploy. Keep the AliExpress secret in Netlify environment variables; never add credentials to Git or the public directory.

The site provides `/`, `/category/{slug}`, `/product/{id}?category={slug}`, `/api/products?category={slug}`, and `/sitemap.xml`. Product results are cached at Netlify's CDN for up to six hours. The product API uses the first five keyword searches for each category and returns up to five unique items.

## Local checks

Run `npm test` with Node.js 18 or later. To preview through Netlify's local Functions runtime, install the Netlify CLI and run `netlify dev` after setting the same environment variables locally.

## Original PHP deployment

The root-level PHP pages and `fetch_products.php` are retained for PHP hosting. Copy `config.example.php` to `config.php`, configure its values, use PHP with cURL and Apache `mod_rewrite`, and keep `config.php` and `products.json` private. Netlify serves only the `public` directory and does not execute those PHP files.
