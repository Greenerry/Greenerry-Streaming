import { execFileSync } from 'node:child_process';
import { existsSync, mkdirSync, writeFileSync } from 'node:fs';
import { join } from 'node:path';

const root = process.cwd();
const imgDir = join(root, 'assets', 'img');
const mysql = 'C:\\xampp\\mysql\\bin\\mysql.exe';
const imageCount = Number(process.argv[2] || 3);
const onlyMissing = process.argv.includes('--missing');

if (!existsSync(imgDir)) mkdirSync(imgDir, { recursive: true });

function runMysql(args, input = '') {
  return execFileSync(mysql, ['-u', 'root', 'greenerry', ...args], {
    input,
    encoding: 'utf8',
    maxBuffer: 1024 * 1024 * 64,
  });
}

function query(sql) {
  const out = runMysql(['--batch', '--raw', '--skip-column-names', '-e', sql]);
  return out.trim() === ''
    ? []
    : out.trim().split(/\r?\n/).map((line) => line.split('\t'));
}

function exec(sql) {
  runMysql(['-e', sql]);
}

function sql(value) {
  if (value === null || value === undefined) return 'NULL';
  return `'${String(value).replace(/\\/g, '\\\\').replace(/'/g, "''")}'`;
}

function slug(value) {
  return String(value || '')
    .normalize('NFD')
    .replace(/[\u0300-\u036f]/g, '')
    .toLowerCase()
    .replace(/[^a-z0-9]+/g, '-')
    .replace(/^-+|-+$/g, '')
    .slice(0, 70) || 'produto';
}

function categoryTerms(category) {
  const normalized = String(category || '').normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase();
  if (normalized.includes('vinil')) return 'official vinyl LP record merch product photo';
  if (normalized.includes('cd')) return 'official CD jewel case album merch product photo';
  if (normalized.includes('hoodie')) return 'official hoodie merch product photo';
  if (normalized.includes('poster')) return 'official poster print merch product photo';
  if (normalized.includes('shirt')) return 'official t-shirt merch product photo';
  return 'official accessory merch pin keychain product photo';
}

function extensionFromType(contentType, url) {
  const type = String(contentType || '').toLowerCase();
  if (type.includes('png')) return 'png';
  if (type.includes('webp')) return 'webp';
  if (type.includes('gif')) return 'gif';
  if (type.includes('jpeg') || type.includes('jpg')) return 'jpg';
  const match = String(url || '').toLowerCase().match(/\.(jpg|jpeg|png|webp|gif)(?:[?#]|$)/);
  return match ? (match[1] === 'jpeg' ? 'jpg' : match[1]) : 'jpg';
}

async function ddgImages(search) {
  const landing = await fetch(`https://duckduckgo.com/?q=${encodeURIComponent(search)}&iax=images&ia=images`, {
    headers: { 'user-agent': 'Mozilla/5.0 Greenerry PAP catalog refresh' },
  });
  const html = await landing.text();
  const vqd = html.match(/vqd=['"]([^'"]+)['"]/)?.[1];
  if (!vqd) return [];

  const response = await fetch(
    `https://duckduckgo.com/i.js?l=us-en&o=json&q=${encodeURIComponent(search)}&vqd=${encodeURIComponent(vqd)}&f=,,,,,&p=1`,
    {
      headers: {
        referer: 'https://duckduckgo.com/',
        'user-agent': 'Mozilla/5.0 Greenerry PAP catalog refresh',
      },
    }
  );
  if (!response.ok) return [];
  const data = await response.json();
  return Array.isArray(data.results) ? data.results : [];
}

async function downloadCandidate(url, fileBase) {
  const controller = new AbortController();
  const timer = setTimeout(() => controller.abort(), 12000);
  try {
    const response = await fetch(url, {
      signal: controller.signal,
      headers: { 'user-agent': 'Mozilla/5.0 Greenerry PAP catalog refresh' },
    });
    if (!response.ok) return null;
    const contentType = response.headers.get('content-type') || '';
    if (!contentType.toLowerCase().includes('image')) return null;
    const bytes = Buffer.from(await response.arrayBuffer());
    if (bytes.length < 5000 || bytes.length > 4_500_000) return null;
    const ext = extensionFromType(contentType, url);
    const filename = `${fileBase}.${ext}`;
    writeFileSync(join(imgDir, filename), bytes);
    return filename;
  } catch {
    return null;
  } finally {
    clearTimeout(timer);
  }
}

function productSearches(product) {
  const baseName = product.name.replace(new RegExp(product.artist, 'i'), '').replace(/\s+/g, ' ').trim();
  const terms = categoryTerms(product.category);
  const artist = product.artist;
  return [
    `${artist} ${baseName} ${terms}`,
    `${artist} ${product.category} official merch product photo`,
    `${artist} merch ${terms}`,
  ];
}

const products = query(`
  SELECT p.idProduto, p.nomeProduto, cat.nomeCategoria, c.nome
  FROM produto p
  JOIN categoria cat ON cat.idCategoria = p.idCategoria
  JOIN cliente c ON c.idCliente = p.idCliente
  ${onlyMissing ? "WHERE NOT EXISTS (SELECT 1 FROM produto_imagem pi WHERE pi.idProduto = p.idProduto AND pi.ficheiro LIKE 'pap_real_product_%')" : ''}
  ORDER BY p.idProduto ASC
`).map(([id, name, category, artist]) => ({
  id: Number(id),
  name,
  category,
  artist,
}));

console.log(`Refreshing ${products.length} products with ${imageCount} real images each...`);

for (const [index, product] of products.entries()) {
  const found = [];
  const seen = new Set();
  const searches = productSearches(product);

  for (const search of searches) {
    if (found.length >= imageCount) break;
    const results = await ddgImages(search);
    for (const result of results) {
      if (found.length >= imageCount) break;
      const urls = [result.image, result.thumbnail].filter(Boolean);
      for (const url of urls) {
        if (found.length >= imageCount) break;
        if (seen.has(url)) continue;
        seen.add(url);
        const fileBase = `pap_real_product_${product.id}_${found.length + 1}_${slug(product.artist)}_${slug(product.category)}`;
        const filename = await downloadCandidate(url, fileBase);
        if (filename) found.push(filename);
      }
    }
  }

  if (found.length === 0) {
    console.warn(`No image found for #${product.id} ${product.name}`);
    continue;
  }

  exec(`DELETE FROM produto_imagem WHERE idProduto = ${product.id}`);
  found.forEach((filename, order) => {
    exec(`INSERT INTO produto_imagem (idProduto, ficheiro, ordem) VALUES (${product.id}, ${sql(filename)}, ${order + 1})`);
  });

  console.log(`${index + 1}/${products.length} #${product.id} ${product.artist} ${product.category}: ${found.join(', ')}`);
  await new Promise((resolve) => setTimeout(resolve, 260));
}

console.log('Done.');
