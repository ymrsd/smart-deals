import { handler as productsHandler } from "./products.mjs";

const categories = ["electronics", "gadgets", "fashion", "home", "beauty"];

function xmlEscape(value) {
  return String(value).replace(/[&<>"']/g, (char) => ({
    "&": "&amp;",
    "<": "&lt;",
    ">": "&gt;",
    '"': "&quot;",
    "'": "&apos;",
  })[char]);
}

export async function handler(event) {
  if (event.httpMethod !== "GET") {
    return { statusCode: 405, headers: { Allow: "GET" }, body: "Method not allowed." };
  }

  let origin;
  try {
    origin = new URL(event.rawUrl).origin;
  } catch {
    return { statusCode: 400, body: "Invalid request URL." };
  }

  const urls = new Map([
    [`${origin}/`, "daily"],
    ...categories.map((slug) => [`${origin}/category/${slug}`, "daily"]),
  ]);
  const results = await Promise.all(categories.map((slug) => productsHandler({
    httpMethod: "GET",
    queryStringParameters: { category: slug },
  })));
  for (const result of results) {
    if (result.statusCode !== 200) {
      console.error("Could not load products while generating sitemap:", result.statusCode);
      continue;
    }
    try {
      const { products } = JSON.parse(result.body);
      for (const product of products) urls.set(`${origin}/product/${product.id}`, "weekly");
    } catch (error) {
      console.error("Invalid product response while generating sitemap:", error);
    }
  }

  const lastmod = new Date().toISOString().slice(0, 10);
  const entries = [...urls].map(([loc, changefreq]) =>
    `<url><loc>${xmlEscape(loc)}</loc><lastmod>${lastmod}</lastmod><changefreq>${changefreq}</changefreq></url>`,
  ).join("");
  return {
    statusCode: 200,
    headers: {
      "Content-Type": "application/xml; charset=utf-8",
      "Cache-Control": "public, max-age=0, s-maxage=21600, stale-while-revalidate=3600",
    },
    body: `<?xml version="1.0" encoding="UTF-8"?><urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">${entries}</urlset>`,
  };
}
