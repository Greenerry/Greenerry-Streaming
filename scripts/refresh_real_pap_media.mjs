import { execFileSync } from 'node:child_process';
import { writeFileSync, unlinkSync, existsSync } from 'node:fs';
import { join } from 'node:path';

const root = 'C:/xampp/htdocs/dashboard/greenerry';
const imgDir = join(root, 'assets/img');
const mysql = 'C:/xampp/mysql/bin/mysql.exe';

const catalog = {
  'The Weeknd': { wiki: 'The Weeknd', albums: ['After Hours', 'Dawn FM', 'Starboy', 'Beauty Behind the Madness'] },
  Rihanna: { wiki: 'Rihanna', albums: ['Anti', 'Loud', 'Unapologetic', 'Good Girl Gone Bad'] },
  'Justin Bieber': { wiki: 'Justin Bieber', albums: ['Purpose', 'Justice', 'Believe', 'Changes'] },
  ZAYN: { wiki: 'Zayn Malik', albums: ['Mind of Mine', 'Nobody Is Listening', 'Room Under the Stairs', 'Icarus Falls'] },
  'Childish Gambino': { wiki: 'Donald Glover', albums: ['Awaken, My Love!', 'Because the Internet', 'Atavista', 'Camp'] },
  'A$AP Rocky': { wiki: 'ASAP Rocky', albums: ['Long.Live.A$AP', 'At.Long.Last.A$AP', 'Testing', 'Live. Love. ASAP'] },
  'Cocteau Twins': { wiki: 'Cocteau Twins', albums: ['Heaven or Las Vegas', 'Treasure', 'Victorialand', 'Blue Bell Knoll'] },
  PARTYNEXTDOOR: { wiki: 'PartyNextDoor', albums: ['PARTYNEXTDOOR', 'PARTYMOBILE', 'P3', 'PARTYNEXTDOOR TWO'] },
  'Björk': { wiki: 'Björk', albums: ['Homogenic', 'Vespertine', 'Debut', 'Fossora'] },
  Bladee: { wiki: 'Bladee', albums: ['Crest', 'Icedancer', 'Spiderr', 'Exeter'] },
  'Dean Blunt': { wiki: 'Dean Blunt', albums: ['Black Metal', 'Zushi', 'The Redeemer', 'Black Metal 2'] },
  JPEGMAFIA: { wiki: 'JPEGMAFIA', albums: ['LP!', 'Veteran', 'All My Heroes Are Cornballs', 'I Lay Down My Life for You'] },
  'Frank Ocean': { wiki: 'Frank Ocean', albums: ['Blonde', 'Channel Orange', 'Endless', 'Nostalgia Ultra'] },
  'Tyler, The Creator': { wiki: 'Tyler, the Creator', albums: ['IGOR', 'CALL ME IF YOU GET LOST', 'CHROMAKOPIA', 'Flower Boy'] },
  SZA: { wiki: 'SZA', albums: ['SOS', 'Ctrl', 'Lana', 'Z'] },
  'Lana Del Rey': { wiki: 'Lana Del Rey', albums: ['Ultraviolence', 'Born To Die', 'Norman Fucking Rockwell!', 'Did you know that there is a tunnel under Ocean Blvd'] },
  'Charli XCX': { wiki: 'Charli XCX', albums: ['BRAT', 'Crash', 'how i am feeling now', 'Pop 2'] },
  'FKA twigs': { wiki: 'FKA twigs', albums: ['MAGDALENE', 'EUSEXUA', 'CAPRISONGS', 'LP1'] },
  'Travis Scott': { wiki: 'Travis Scott', albums: ['UTOPIA', 'ASTROWORLD', 'Rodeo', 'Birds in the Trap Sing McKnight'] },
  'Beyoncé': { wiki: 'Beyoncé', albums: ['RENAISSANCE', 'COWBOY CARTER', 'Lemonade', 'BEYONCÉ'] },
};

const productCopy = {
  1: '%s graphic T-shirt',
  2: '%s tour hoodie',
  3: '%s vinyl edition',
  4: '%s CD edition',
  5: '%s wall poster',
  6: '%s collector accessory',
};

const sleep = (ms) => new Promise(resolve => setTimeout(resolve, ms));

function slug(value) {
  return value.normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase().replace(/[^a-z0-9]+/g, '') || 'item';
}

function sql(value) {
  if (value === null || value === undefined) return 'NULL';
  return `'${String(value).replace(/\\/g, '\\\\').replace(/'/g, "''")}'`;
}

function mysqlRows(query) {
  const out = execFileSync(mysql, ['-u', 'root', '--default-character-set=utf8mb4', '-N', '-B', 'greenerry', '-e', query], { encoding: 'utf8' });
  return out.trim() === '' ? [] : out.trim().split(/\r?\n/).map(line => line.split('\t'));
}

function mysqlExec(query) {
  execFileSync(mysql, ['-u', 'root', '--default-character-set=utf8mb4', 'greenerry', '-e', query], { stdio: 'inherit' });
}

async function json(url) {
  const res = await fetch(url, { headers: { 'User-Agent': 'Greenerry PAP media refresh' } });
  if (!res.ok) return null;
  return res.json();
}

async function wikiImage(title) {
  const data = await json(`https://en.wikipedia.org/api/rest_v1/page/summary/${encodeURIComponent(title)}`);
  return data?.originalimage?.source || data?.thumbnail?.source || null;
}

async function albumArt(artist, album) {
  const data = await json(`https://itunes.apple.com/search?term=${encodeURIComponent(`${artist} ${album}`)}&entity=album&limit=5`);
  const art = data?.results?.find(result => result.artworkUrl100)?.artworkUrl100;
  return art ? art.replace(/100x100bb\.(jpg|png)$/i, '1200x1200bb.$1') : null;
}

async function saveImage(url, file) {
  if (!url) return false;
  try {
    const res = await fetch(url, { headers: { 'User-Agent': 'Greenerry PAP media refresh' } });
    if (!res.ok) return false;
    const input = Buffer.from(await res.arrayBuffer());
    writeFileSync(join(imgDir, file), input);
    return true;
  } catch {
    return false;
  }
}

const artistRows = mysqlRows(`
  SELECT c.idCliente, c.nome
  FROM cliente c
  WHERE c.estado='ativo'
    AND EXISTS (
      SELECT 1 FROM release_musical r
      WHERE r.idCliente=c.idCliente AND r.estado='aprovado' AND r.ativo=1
    )
  ORDER BY c.idCliente
`);

const sqlUpdates = [];
const wikiCache = new Map();

for (const [id, name] of artistRows) {
  const info = catalog[name];
  if (!info) continue;
  const source = await wikiImage(info.wiki);
  wikiCache.set(name, source);
  const artistSlug = slug(name);
  const avatar = `pap_avatar_${artistSlug}.jpg`;
  const banner = `pap_banner_${artistSlug}.jpg`;
  await saveImage(source, avatar);
  await saveImage(source, banner);
  sqlUpdates.push(`UPDATE cliente SET foto=${sql(avatar)}, banner=${sql(banner)} WHERE idCliente=${Number(id)};`);
  await sleep(80);
}

const productRows = mysqlRows(`
  SELECT p.idProduto, p.idCategoria, c.nome
  FROM produto p
  JOIN cliente c ON c.idCliente=p.idCliente
  WHERE c.nome <> 'Green'
  ORDER BY c.idCliente, p.idProduto
`);

const perArtist = new Map();
for (const [productId, categoryIdRaw, artist] of productRows) {
  const info = catalog[artist];
  if (!info) continue;
  const current = perArtist.get(artist) || 0;
  perArtist.set(artist, current + 1);
  const album = info.albums[current % info.albums.length];
  const categoryId = Number(categoryIdRaw);
  const name = (productCopy[categoryId] || '%s official item').replace('%s', album);
  const description = `Produto oficial inspirado em ${album}, de ${artist}. Artigo preparado para a loja Greenerry com imagens reais de catálogo musical e apresentação pronta para a demonstração final da PAP.`;
  const art = await albumArt(artist, album) || wikiCache.get(artist) || await wikiImage(info.wiki);

  sqlUpdates.push(`UPDATE produto SET nomeProduto=${sql(name)}, descricaoProduto=${sql(description)}, marca=${sql(artist)} WHERE idProduto=${Number(productId)};`);
  sqlUpdates.push(`DELETE FROM produto_imagem WHERE idProduto=${Number(productId)};`);

  for (let order = 0; order < 3; order++) {
    const file = `pap_real_product_${slug(artist)}_${Number(productId)}_${order + 1}.jpg`;
    const source = order === 0 ? art : (wikiCache.get(artist) || art);
    const ok = await saveImage(source, file);
    if (!ok && art) await saveImage(art, file);
    sqlUpdates.push(`INSERT INTO produto_imagem (idProduto, ficheiro, ordem) VALUES (${Number(productId)}, ${sql(file)}, ${order});`);
  }
  await sleep(80);
}

const updateFile = join(root, 'scripts/.refresh_real_pap_media.sql');
writeFileSync(updateFile, sqlUpdates.join('\n'), 'utf8');
execFileSync(mysql, ['-u', 'root', '--default-character-set=utf8mb4', 'greenerry'], {
  input: sqlUpdates.join('\n'),
  stdio: ['pipe', 'inherit', 'inherit'],
});
if (existsSync(updateFile)) unlinkSync(updateFile);

mysqlExec(`
  UPDATE release_musical r
  JOIN cliente c ON c.idCliente=r.idCliente
  JOIN produto p ON p.idCliente=c.idCliente
  JOIN produto_imagem pi ON pi.idProduto=p.idProduto AND pi.ordem=0
  SET r.capa=pi.ficheiro
  WHERE r.capa LIKE 'pap_release_%'
    AND r.estado='aprovado'
    AND r.ativo=1;
`);

console.log('Real PAP media refreshed.');
