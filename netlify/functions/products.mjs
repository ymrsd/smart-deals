import { createHash } from "node:crypto";

const categories = {
  electronics: ["wireless earbuds", "bluetooth speaker", "smart watch", "fast charger", "power bank"],
  gadgets: ["led strip lights", "mini projector", "wifi camera", "usb hub", "phone holder"],
  fashion: ["men sneakers", "women handbag", "sunglasses", "smart ring", "leather wallet"],
  home: ["kitchen organizer", "led night light", "vacuum cleaner", "storage box", "air purifier"],
  beauty: ["skincare tools", "hair dryer", "makeup brush set", "facial massager", "nail kit"],
};

function response(statusCode, body, cache = false) {
  return {
    statusCode,
    headers: {
      "Content-Type": "application/json; charset=utf-8",
      "Cache-Control": cache ? "public, max-age=0, s-maxage=21600, stale-while-revalidate=3600" : "no-store",
    },
    body: JSON.stringify(body),
  };
}

function sign(params, secret) {
  const canonical = Object.keys(params).sort().map((key) => `${key}${params[key]}`).join("");
  return createHash("md5").update(`${secret}${canonical}${secret}`).digest("hex").toUpperCase();
}

async function fetchKeyword(keyword, env) {
  const params = {
    app_key: env.ALIEXPRESS_APP_KEY,
    method: "aliexpress.affiliate.product.query",
    timestamp: new Date().toISOString().slice(0, 19).replace("T", " "),
    format: "json",
    v: "2.0",
    sign_method: "md5",
    partner_id: "apidoc",
    keywords: keyword,
    page_no: "1",
    page_size: "5",
    sort: "LAST_VOLUME_DESC",
    target_currency: "USD",
    target_language: "EN",
    tracking_id: env.ALIEXPRESS_TRACKING_ID,
  };
  params.sign = sign(params, env.ALIEXPRESS_APP_SECRET);
  const endpoint = env.ALIEXPRESS_GATEWAY || "https://eco.taobao.com/router/rest";
  const url = new URL(endpoint);
  for (const [key, value] of Object.entries(params)) url.searchParams.set(key, value);

  const abort = new AbortController();
  const timeout = setTimeout(() => abort.abort(), 12000);
  let upstream;
  try {
    upstream = await fetch(url, { signal: abort.signal });
  } finally {
    clearTimeout(timeout);
  }
  if (!upstream.ok) throw new Error(`AliExpress returned HTTP ${upstream.status}.`);
  const data = await upstream.json();
  if (data.error_response) throw new Error("AliExpress rejected the product request.");
  const products = data?.aliexpress_affiliate_product_query_response?.resp_result?.products?.product;
  return Array.isArray(products) ? products : [];
}

function normalize(product) {
  if (!product || typeof product !== "object") return null;
  const id = String(product.product_id ?? "");
  const title = String(product.product_title ?? "");
  const image = String(product.product_main_image_url ?? "");
  const url = String(product.promotion_link ?? product.product_detail_url ?? "");
  if (!/^[A-Za-z0-9]+$/.test(id) || !title || !isWebUrl(image) || !isWebUrl(url)) return null;
  return {
    id,
    title,
    image,
    url,
    price: String(product.target_sale_price ?? product.sale_price ?? ""),
    desc: title.replace(/<[^>]*>/g, "").slice(0, 150),
  };
}

function isWebUrl(value) {
  try {
    return ["https:", "http:"].includes(new URL(value).protocol);
  } catch {
    return false;
  }
}

export async function handler(event) {
  if (event.httpMethod !== "GET") return response(405, { error: "Method not allowed." });
  const slug = event.queryStringParameters?.category;
  if (!Object.hasOwn(categories, slug)) return response(400, { error: "Unknown product category." });
  const env = process.env;
  if (!env.ALIEXPRESS_APP_KEY || !env.ALIEXPRESS_APP_SECRET || !env.ALIEXPRESS_TRACKING_ID) {
    console.error("AliExpress environment variables are not configured.");
    return response(503, { error: "Product service is not configured yet. Please try again later." });
  }

  try {
    const results = await Promise.allSettled(categories[slug].map((keyword) => fetchKeyword(keyword, env)));
    const failedRequests = results.filter((result) => result.status === "rejected");
    for (const failure of failedRequests) console.error("AliExpress keyword lookup failed:", failure.reason);
    const successfulResults = results.filter((result) => result.status === "fulfilled");
    if (successfulResults.length === 0) throw failedRequests[0].reason;

    const unique = new Map();
    for (const product of successfulResults.flatMap((result) => result.value)) {
      const normalized = normalize(product);
      if (normalized && !unique.has(normalized.id)) unique.set(normalized.id, normalized);
      if (unique.size >= 5) break;
    }
    return response(200, { category: slug, products: [...unique.values()] }, true);
  } catch (error) {
    console.error("AliExpress product lookup failed:", error);
    return response(502, { error: "Could not load deals right now. Please try again later." });
  }
}
