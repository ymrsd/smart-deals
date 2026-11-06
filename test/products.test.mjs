import assert from "node:assert/strict";
import { createHash } from "node:crypto";
import test from "node:test";
import { handler } from "../netlify/functions/products.mjs";
import { handler as sitemapHandler } from "../netlify/functions/sitemap.mjs";

const envKeys = ["ALIEXPRESS_APP_KEY", "ALIEXPRESS_APP_SECRET", "ALIEXPRESS_TRACKING_ID", "ALIEXPRESS_GATEWAY"];
const originalEnv = Object.fromEntries(envKeys.map((key) => [key, process.env[key]]));

function setCredentials() {
  process.env.ALIEXPRESS_APP_KEY = "test-app";
  process.env.ALIEXPRESS_APP_SECRET = "test-secret";
  process.env.ALIEXPRESS_TRACKING_ID = "test-tracking";
  process.env.ALIEXPRESS_GATEWAY = "https://gateway.example.test/router";
}

function makeProduct(id, overrides = {}) {
  return {
    product_id: String(id),
    product_title: `Product ${id}`,
    product_main_image_url: `https://images.example.test/${id}.jpg`,
    promotion_link: `https://shop.example.test/${id}`,
    target_sale_price: "10.50",
    ...overrides,
  };
}

test.afterEach(() => {
  for (const key of envKeys) {
    if (originalEnv[key] === undefined) delete process.env[key];
    else process.env[key] = originalEnv[key];
  }
  globalThis.fetch = originalFetch;
});

const originalFetch = globalThis.fetch;

test("rejects unknown categories without making upstream requests", async () => {
  let requested = false;
  globalThis.fetch = async () => { requested = true; };
  const result = await handler({ httpMethod: "GET", queryStringParameters: { category: "unknown" } });
  assert.equal(result.statusCode, 400);
  assert.equal(requested, false);
});

test("reports missing AliExpress credentials", async () => {
  for (const key of envKeys) delete process.env[key];
  const originalError = console.error;
  console.error = () => {};
  try {
    const result = await handler({ httpMethod: "GET", queryStringParameters: { category: "electronics" } });
    assert.equal(result.statusCode, 503);
    assert.match(JSON.parse(result.body).error, /not configured/i);
  } finally {
    console.error = originalError;
  }
});

test("signs upstream requests and returns up to five unique safe products", async () => {
  setCredentials();
  const requestUrls = [];
  globalThis.fetch = async (input) => {
    const url = new URL(input);
    requestUrls.push(url);
    const methodParams = Object.fromEntries(url.searchParams);
    const signature = methodParams.sign;
    delete methodParams.sign;
    const canonical = Object.keys(methodParams).sort().map((key) => `${key}${methodParams[key]}`).join("");
    const expected = createHash("md5").update(`test-secret${canonical}test-secret`).digest("hex").toUpperCase();
    assert.equal(signature, expected);
    assert.equal(methodParams.method, "aliexpress.affiliate.product.query");
    const index = requestUrls.length;
    const products = index === 1
      ? [makeProduct(1), makeProduct(2), makeProduct(3), makeProduct(4), makeProduct(5), makeProduct(6)]
      : [makeProduct(1)];
    return { ok: true, status: 200, json: async () => ({
      aliexpress_affiliate_product_query_response: { resp_result: { products: { product: products } } },
    }) };
  };

  const result = await handler({ httpMethod: "GET", queryStringParameters: { category: "electronics" } });
  const data = JSON.parse(result.body);
  assert.equal(result.statusCode, 200);
  assert.equal(requestUrls.length, 5);
  assert.equal(data.products.length, 5);
  assert.deepEqual(data.products.map((product) => product.id), ["1", "2", "3", "4", "5"]);
  assert.match(result.headers["Cache-Control"], /s-maxage=21600/);
});

test("returns a service error when all upstream calls fail", async () => {
  setCredentials();
  const originalError = console.error;
  console.error = () => {};
  globalThis.fetch = async () => { throw new Error("upstream unavailable"); };
  try {
    const result = await handler({ httpMethod: "GET", queryStringParameters: { category: "gadgets" } });
    assert.equal(result.statusCode, 502);
    assert.match(JSON.parse(result.body).error, /could not load deals/i);
  } finally {
    console.error = originalError;
  }
});

test("generates an absolute sitemap for the requested Netlify host", async () => {
  for (const key of envKeys) delete process.env[key];
  const originalError = console.error;
  console.error = () => {};
  try {
    const result = await sitemapHandler({
      httpMethod: "GET",
      rawUrl: "https://smartdeals.example/sitemap.xml",
    });
    assert.equal(result.statusCode, 200);
    assert.equal(result.headers["Content-Type"], "application/xml; charset=utf-8");
    assert.match(result.body, /https:\/\/smartdeals\.example\/category\/electronics/);
    assert.match(result.body, /https:\/\/smartdeals\.example\/category\/beauty/);
  } finally {
    console.error = originalError;
  }
});
