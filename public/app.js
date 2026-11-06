const categories = {
  electronics: "Electronics",
  gadgets: "Gadgets",
  fashion: "Fashion",
  home: "Home & Garden",
  beauty: "Beauty",
};

const app = document.querySelector("#app");
const nav = document.querySelector("#navigation");
const productCache = new Map();
const categoryFromPath = (slug) => Object.hasOwn(categories, slug);

function escapeHtml(value) {
  return String(value ?? "").replace(/[&<>"']/g, (char) => ({
    "&": "&amp;",
    "<": "&lt;",
    ">": "&gt;",
    '"': "&quot;",
    "'": "&#39;",
  })[char]);
}

function safeUrl(value) {
  try {
    const url = new URL(value);
    return url.protocol === "https:" || url.protocol === "http:" ? url.href : "";
  } catch {
    return "";
  }
}

function updateHead(title, description) {
  document.title = title;
  document.querySelector('meta[name="description"]').content = description;
  document.querySelector('meta[property="og:title"]').content = title;
  document.querySelector('meta[property="og:description"]').content = description;
  document.querySelector('link[rel="canonical"]')?.remove();
  const canonical = document.createElement("link");
  canonical.rel = "canonical";
  canonical.href = location.origin + location.pathname;
  document.head.append(canonical);
}

function productCard(product, slug) {
  const image = safeUrl(product.image);
  const deal = safeUrl(product.url);
  const productPath = `/product/${encodeURIComponent(product.id)}?category=${encodeURIComponent(slug)}`;
  if (!image || !deal || !product.id || !product.title) return "";
  return `<article class="card">
    <a href="${productPath}"><img src="${escapeHtml(image)}" alt="${escapeHtml(product.title)}" loading="lazy"></a>
    <div class="card-body">
      <h3><a href="${productPath}">${escapeHtml(product.title)}</a></h3>
      <div class="price">$${escapeHtml(product.price || "—")}</div>
      <a class="btn" href="${escapeHtml(deal)}" rel="nofollow sponsored noopener" target="_blank">View Deal →</a>
    </div>
  </article>`;
}

function renderNavigation() {
  nav.innerHTML = Object.entries(categories)
    .map(([slug, name]) => `<a href="/category/${slug}">${escapeHtml(name)}</a>`)
    .join("");
}

async function loadCategory(slug) {
  if (productCache.has(slug)) return productCache.get(slug);
  const response = await fetch(`/api/products?category=${encodeURIComponent(slug)}`);
  const data = await response.json().catch(() => ({}));
  if (!response.ok) throw new Error(data.error || "Products could not be loaded.");
  if (!Array.isArray(data.products)) throw new Error("The product service returned invalid data.");
  productCache.set(slug, data.products);
  return data.products;
}

async function renderHome() {
  updateHead("SmartDeals.lk – AliExpress Best Deals & Trending Items in Sri Lanka",
    "AliExpress trending items සිංහලෙන් — electronics, gadgets, fashion, home, beauty. Daily updated deals.");
  app.innerHTML = `<section class="hero">
    <h1>හොඳම <span>Trending Deals</span><br>දැන් එකම තැනකින්</h1>
    <p>AliExpress හි ජනප්‍රිය gadgets, fashion සහ තවත් දේවල් — ශ්‍රී ලංකාවට ගැළපෙන තේරීම් සිංහලෙන්.</p>
    <a class="hero-cta" href="#deals">අද deals බලන්න <span aria-hidden="true">&nbsp;↓</span></a>
  </section><div class="container" id="deals"><p class="loading">Deals ලබාගනිමින්...</p></div>`;
  const container = document.querySelector("#deals");
  const sections = await Promise.all(Object.entries(categories).map(async ([slug, name]) => {
    try {
      const products = await loadCategory(slug);
      return `<section class="category-section">
        <div class="section-heading"><h2 class="section-title">${escapeHtml(name)}</h2>
        <a class="section-link" href="/category/${slug}">සියල්ල බලන්න →</a></div>
        <div class="grid">${products.map((product) => productCard(product, slug)).join("") || '<p class="empty-state">දැනට deals නැහැ. කරුණාකර පසුව නැවත බලන්න.</p>'}</div>
      </section>`;
    } catch (error) {
      return `<section class="category-section"><div class="section-heading"><h2 class="section-title">${escapeHtml(name)}</h2></div>
        <p class="error-state">${escapeHtml(error.message)}</p></section>`;
    }
  }));
  container.innerHTML = sections.join("");
}

async function renderCategory(slug) {
  if (!categoryFromPath(slug)) return renderNotFound("Category එක සොයාගත නොහැක");
  const name = categories[slug];
  updateHead(`${name} Deals – SmartDeals.lk | AliExpress Best Prices`,
    `AliExpress trending ${name} items — best prices, daily updated.`);
  app.innerHTML = `<section class="hero"><h1>${escapeHtml(name)} <span>Deals</span></h1>
    <p>ඔබට ගැළපෙන ජනප්‍රිය තේරීම් සොයාගන්න.</p></section>
    <div class="container"><div class="breadcrumb"><a href="/">Home</a> / ${escapeHtml(name)}</div>
    <div class="grid"><p class="loading">Deals ලබාගනිමින්...</p></div></div>`;
  const grid = app.querySelector(".grid");
  try {
    const products = await loadCategory(slug);
    grid.innerHTML = products.map((product) => productCard(product, slug)).join("") ||
      '<p class="empty-state">මෙම category එකට දැනට deals නැහැ. කරුණාකර පසුව නැවත බලන්න.</p>';
  } catch (error) {
    grid.innerHTML = `<p class="error-state">${escapeHtml(error.message)}</p>`;
  }
}

async function renderProduct(id) {
  const slug = new URLSearchParams(location.search).get("category");
  if (!id || !slug || !categoryFromPath(slug)) return renderNotFound("Product එක සොයාගත නොහැක");
  const name = categories[slug];
  updateHead("Product – SmartDeals.lk", "AliExpress trending item — SmartDeals.lk");
  app.innerHTML = '<div class="container product-page"><p class="loading">Product එක ලබාගනිමින්...</p></div>';
  try {
    const products = await loadCategory(slug);
    const product = products.find((item) => String(item.id) === id);
    if (!product) return renderNotFound("Product එක සොයාගත නොහැක");
    const deal = safeUrl(product.url);
    const image = safeUrl(product.image);
    updateHead(`${product.title} – Best Price | SmartDeals.lk`, product.desc || product.title);
    app.innerHTML = `<div class="container product-page">
      <div class="breadcrumb"><a href="/">Home</a> / <a href="/category/${slug}">${escapeHtml(name)}</a> / ${escapeHtml(product.title)}</div>
      <div class="product-detail">
        <img src="${escapeHtml(image)}" alt="${escapeHtml(product.title)}">
        <div><h1 class="product-heading">${escapeHtml(product.title)}</h1>
          <p class="product-meta">Category: ${escapeHtml(name)} · Shipping details බලන්න AliExpress වෙත යන්න</p>
          <div class="price product-price">$${escapeHtml(product.price || "—")}</div>
          <a class="btn product-buy" href="${escapeHtml(deal)}" rel="nofollow sponsored noopener" target="_blank">AliExpress එකෙන් මිලදී ගන්න →</a>
          <div class="product-note"><strong>Product Review:</strong> ${escapeHtml(product.desc || product.title)} — AliExpress trending item.</div>
        </div>
      </div>
    </div>`;
  } catch (error) {
    app.innerHTML = `<div class="container"><p class="error-state">${escapeHtml(error.message)}</p></div>`;
  }
}

function renderNotFound(message) {
  updateHead("Page not found – SmartDeals.lk", message);
  app.innerHTML = `<div class="container"><div class="empty-state"><h1>${escapeHtml(message)}</h1>
    <p class="not-found-copy"><a class="section-link" href="/">මුල් පිටුවට යන්න →</a></p></div></div>`;
}

async function render() {
  const path = location.pathname.replace(/\/+$/, "") || "/";
  const categoryMatch = path.match(/^\/category\/([a-z0-9-]+)$/);
  const productMatch = path.match(/^\/product\/([A-Za-z0-9]+)$/);
  if (categoryMatch) return renderCategory(categoryMatch[1]);
  if (productMatch) return renderProduct(productMatch[1]);
  if (path === "/") return renderHome();
  return renderNotFound("මෙම පිටුව සොයාගත නොහැක");
}

renderNavigation();
document.querySelector("#year").textContent = new Date().getFullYear();
render();
