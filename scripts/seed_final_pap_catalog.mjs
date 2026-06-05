import { execFileSync } from 'node:child_process';
import { existsSync, mkdirSync, writeFileSync } from 'node:fs';
import { extname, join } from 'node:path';

const root = 'C:/xampp/htdocs/dashboard/greenerry';
const mysql = 'C:/xampp/mysql/bin/mysql.exe';
const imgDir = join(root, 'assets/img');
const audioDir = join(root, 'assets/audio');
const passwordHash = '$2y$10$SubBHsrEhXQ0MR043figbeDT7QQ6aUj4lQ4bugcNVfWSp3nZQhhfO';

for (const dir of [imgDir, audioDir]) {
  if (!existsSync(dir)) mkdirSync(dir, { recursive: true });
}

const artists = [
  { name: 'Bladee', wiki: 'Bladee', genre: 'Cloud rap', bio: 'Artista sueco ligado ao universo Drain Gang, com uma identidade visual fria, digital e muito reconhecivel.', releases: ['Crest', 'Icedancer'], singles: ['Be Nice 2 Me'] },
  { name: 'The Weeknd', wiki: 'The Weeknd', genre: 'R&B', bio: 'Artista canadiano conhecido por misturar R&B, pop e uma estetica cinematografica nocturna.', releases: ['After Hours', 'Dawn FM'], singles: ['Blinding Lights'] },
  { name: 'Cocteau Twins', wiki: 'Cocteau Twins', genre: 'Dream pop', bio: 'Banda escocesa essencial do dream pop, marcada por guitarras atmosfericas e vozes etereas.', releases: ['Heaven or Las Vegas', 'Treasure'], singles: ['Cherry-coloured Funk'] },
  { name: 'Rihanna', wiki: 'Rihanna', genre: 'Pop', bio: 'Artista global de Barbados, reconhecida pela versatilidade entre pop, R&B, dancehall e moda.', releases: ['ANTI', 'LOUD'], singles: ['Work'] },
  { name: 'Young Thug', wiki: 'Young Thug', genre: 'Hip hop', bio: 'Rapper norte-americano com voz elastica, flow experimental e enorme influencia no trap moderno.', releases: ['So Much Fun', 'JEFFERY'], singles: ['Hot'] },
  { name: 'Dean Blunt', wiki: 'Dean Blunt', genre: 'Experimental', bio: 'Musico britanico de linguagem minimalista, ambigua e experimental, entre pop, dub e art music.', releases: ['Black Metal', 'The Redeemer'], singles: ['100'] },
  { name: 'Miguel', wiki: 'Miguel (singer)', genre: 'R&B', bio: 'Cantor e compositor norte-americano que une R&B moderno, soul, rock e sensualidade futurista.', releases: ['Kaleidoscope Dream', 'Wildheart'], singles: ['Sure Thing'] },
  { name: 'Childish Gambino', wiki: 'Donald Glover', genre: 'Funk', bio: 'Projeto musical de Donald Glover, com fases entre rap, soul, funk psicadelico e pop conceptual.', releases: ['Awaken, My Love!', 'Because the Internet'], singles: ['Redbone'] },
  { name: 'Kanye West', wiki: 'Kanye West', genre: 'Hip hop', bio: 'Produtor e rapper norte-americano, importante pela producao, conceito visual e impacto cultural.', releases: ['Graduation', 'My Beautiful Dark Twisted Fantasy'], singles: ['Stronger'] },
  { name: 'Justin Bieber', wiki: 'Justin Bieber', genre: 'Pop', bio: 'Artista canadiano de pop e R&B, conhecido por grandes singles globais e varias fases visuais.', releases: ['Purpose', 'Justice'], singles: ['Sorry'] },
  { name: 'Lil Uzi Vert', wiki: 'Lil Uzi Vert', genre: 'Rap', bio: 'Rapper norte-americano de trap melodico, visual colorido e forte ligacao a moda e cultura digital.', releases: ['Luv Is Rage 2', 'Pink Tape'], singles: ['XO Tour Llif3'] },
  { name: 'Frank Ocean', wiki: 'Frank Ocean', genre: 'Alternative R&B', bio: 'Artista norte-americano de R&B alternativo, conhecido por escrita emotiva, minimalismo e direcao visual muito forte.', releases: ['channel ORANGE', 'Blonde'], singles: ['Pink + White'] },
  { name: 'Tyler, The Creator', wiki: 'Tyler, the Creator', genre: 'Hip hop', bio: 'Rapper, produtor e diretor criativo com mundos visuais muito marcados, entre rap, soul e pop alternativo.', releases: ['IGOR', 'CALL ME IF YOU GET LOST'], singles: ['EARFQUAKE'] },
  { name: 'SZA', wiki: 'SZA', genre: 'R&B', bio: 'Cantora e compositora de R&B contemporaneo, com escrita intima, melodias fluidas e estetica visual suave.', releases: ['SOS', 'Ctrl'], singles: ['Kill Bill'] },
  { name: 'Travis Scott', wiki: 'Travis Scott', genre: 'Hip hop', bio: 'Artista e produtor conhecido por concertos imersivos, trap atmosferico e uma identidade visual de grande escala.', releases: ['ASTROWORLD', 'UTOPIA'], singles: ['SICKO MODE'] },
  { name: 'Playboi Carti', wiki: 'Playboi Carti', genre: 'Rap', bio: 'Rapper norte-americano com som minimalista, energia punk e estetica muito forte ligada a moda e performance.', releases: ['Whole Lotta Red', 'Die Lit'], singles: ['Magnolia'] },
  { name: 'A$AP Rocky', wiki: 'ASAP Rocky', genre: 'Hip hop', bio: 'Rapper de Harlem ligado a moda, videos cinematograficos e uma mistura entre rap, luxo e cultura alternativa.', releases: ['LONG.LIVE.A$AP', 'AT.LONG.LAST.A$AP'], singles: ['Praise The Lord'] },
  { name: 'ZAYN', wiki: 'Zayn Malik', genre: 'Pop', bio: 'Cantor britanico com som entre pop e R&B, marcado por vocais suaves, atmosfera escura e imagem editorial.', releases: ['Mind Of Mine', 'Nobody Is Listening'], singles: ['PILLOWTALK'] },
  { name: 'Lana Del Rey', wiki: 'Lana Del Rey', genre: 'Alternative', bio: 'Artista norte-americana com universo cinematografico, melancolia pop e estetica vintage muito reconhecivel.', releases: ['Born To Die', 'Ultraviolence'], singles: ['Summertime Sadness'] },
  { name: 'Charli XCX', wiki: 'Charli XCX', genre: 'Pop', bio: 'Artista britanica ligada ao pop futurista, club music e cultura digital, com identidade visual muito atual.', releases: ['BRAT', 'how i am feeling now'], singles: ['360'] },
  { name: 'FKA twigs', wiki: 'FKA twigs', genre: 'Alternative R&B', bio: 'Artista britanica de musica, performance e visual art, cruzando R&B, electronica, coreografia e moda.', releases: ['MAGDALENE', 'LP1'], singles: ['cellophane'] },
  { name: 'Bjork', wiki: 'Bjork', genre: 'Art pop', bio: 'Artista islandesa de art pop e electronica experimental, conhecida por mundos visuais organicos e futuristas.', releases: ['Homogenic', 'Vespertine'], singles: ['Joga'] },
  { name: 'Beyonce', wiki: 'Beyoncé', genre: 'Pop', bio: 'Artista global com espetaculos visuais de grande producao, pop, R&B, dance e conceitos culturais fortes.', releases: ['RENAISSANCE', 'COWBOY CARTER'], singles: ['CUFF IT'] },
];

const demoBuyers = [
  ['Mafalda Silva', 'mafalda.silva.demo@gmail.com'],
  ['Tiago Ferreira', 'tiago.ferreira.demo@gmail.com'],
  ['Ines Costa', 'ines.costa.demo@gmail.com'],
  ['Rafael Martins', 'rafael.martins.demo@gmail.com'],
  ['Sofia Almeida', 'sofia.almeida.demo@gmail.com'],
];

const productTypes = [
  { category: 3, suffix: 'vinil de colecao', price: 34.99, sizes: false },
  { category: 4, suffix: 'CD deluxe', price: 18.99, sizes: false },
  { category: 1, suffix: 't-shirt grafica', price: 29.99, sizes: true },
  { category: 2, suffix: 'hoodie oficial', price: 64.99, sizes: true },
  { category: 5, suffix: 'poster de parede', price: 14.99, sizes: true },
  { category: 6, suffix: 'pack de acessorios', price: 12.99, sizes: false },
];

const orderStates = ['pendente', 'em_preparacao', 'enviada', 'entregue', 'cancelada'];

function slug(value) {
  return value.normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase().replace(/[^a-z0-9]+/g, '-') .replace(/^-|-$/g, '') || 'item';
}

function emailFor(name) {
  return `${slug(name).replace(/-/g, '')}@gmail.com`;
}

function sql(value) {
  if (value === null || value === undefined) return 'NULL';
  return `'${String(value).replace(/\\/g, '\\\\').replace(/'/g, "''")}'`;
}

function xml(value) {
  return String(value || '')
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;');
}

function colorPair(seed) {
  const pairs = [
    ['#111827', '#f8fafc'],
    ['#1f2937', '#d9b66f'],
    ['#141414', '#a8b9ad'],
    ['#22243a', '#d97f9a'],
    ['#0f172a', '#8fa1b5'],
    ['#2d1f2f', '#e7d28a'],
    ['#101820', '#60a5fa'],
  ];
  let sum = 0;
  for (const char of seed) sum += char.charCodeAt(0);
  return pairs[sum % pairs.length];
}

function writeProductMockup({ artist, title, categoryId, categoryName, productName, fileBase }) {
  const [dark, accent] = colorPair(`${artist}-${title}-${categoryId}`);
  const safeArtist = xml(artist);
  const safeTitle = xml(title);
  const safeProduct = xml(productName);
  const safeCategory = xml(categoryName);
  const file = `${fileBase}.svg`;
  const common = `
    <defs>
      <filter id="shadow" x="-20%" y="-20%" width="140%" height="140%">
        <feDropShadow dx="0" dy="26" stdDeviation="20" flood-color="#000" flood-opacity=".28"/>
      </filter>
      <linearGradient id="bg" x1="0" x2="1" y1="0" y2="1">
        <stop stop-color="#f8f8f5"/>
        <stop offset="1" stop-color="#ddd8cf"/>
      </linearGradient>
      <linearGradient id="accent" x1="0" x2="1" y1="0" y2="1">
        <stop stop-color="${accent}"/>
        <stop offset="1" stop-color="${dark}"/>
      </linearGradient>
    </defs>
    <rect width="1200" height="1200" rx="0" fill="url(#bg)"/>
    <circle cx="1020" cy="220" r="170" fill="${accent}" opacity=".18"/>
    <circle cx="200" cy="1020" r="220" fill="${dark}" opacity=".08"/>`;
  let body = '';
  if (categoryId === 1) {
    body = `
      <path d="M365 250 500 182h200l135 68 120 170-112 78-74-88v525H431V410l-74 88-112-78 120-170Z" fill="${dark}" filter="url(#shadow)"/>
      <rect x="475" y="392" width="250" height="300" rx="28" fill="${accent}" opacity=".96"/>
      <text x="600" y="510" text-anchor="middle" font-family="Arial, sans-serif" font-size="56" font-weight="800" fill="#fff">${safeArtist}</text>
      <text x="600" y="575" text-anchor="middle" font-family="Arial, sans-serif" font-size="34" font-weight="700" fill="#fff">${safeTitle}</text>
      <text x="600" y="650" text-anchor="middle" font-family="Arial, sans-serif" font-size="25" letter-spacing="5" fill="#fff">GREENERRY</text>`;
  } else if (categoryId === 2) {
    body = `
      <path d="M392 240c58-46 116-68 174-68h68c58 0 116 22 174 68l84 100-88 94-50-56v536H446V378l-50 56-88-94 84-100Z" fill="${dark}" filter="url(#shadow)"/>
      <path d="M524 176c24 54 128 54 152 0l46 42c-18 88-226 88-244 0l46-42Z" fill="#0b0d12" opacity=".34"/>
      <rect x="480" y="420" width="240" height="250" rx="34" fill="${accent}" opacity=".95"/>
      <text x="600" y="530" text-anchor="middle" font-family="Arial, sans-serif" font-size="48" font-weight="800" fill="#fff">${safeArtist}</text>
      <text x="600" y="590" text-anchor="middle" font-family="Arial, sans-serif" font-size="30" font-weight="700" fill="#fff">${safeTitle}</text>`;
  } else if (categoryId === 3) {
    body = `
      <circle cx="590" cy="595" r="330" fill="#111" filter="url(#shadow)"/>
      <circle cx="590" cy="595" r="250" fill="#1f1f1f"/>
      <circle cx="590" cy="595" r="80" fill="${accent}"/>
      <rect x="690" y="286" width="310" height="310" rx="18" fill="url(#accent)" filter="url(#shadow)"/>
      <text x="845" y="420" text-anchor="middle" font-family="Arial, sans-serif" font-size="42" font-weight="800" fill="#fff">${safeArtist}</text>
      <text x="845" y="480" text-anchor="middle" font-family="Arial, sans-serif" font-size="28" font-weight="700" fill="#fff">${safeTitle}</text>`;
  } else if (categoryId === 4) {
    body = `
      <rect x="335" y="300" width="530" height="530" rx="30" fill="rgba(255,255,255,.58)" stroke="#cbd5e1" stroke-width="4" filter="url(#shadow)"/>
      <rect x="388" y="354" width="310" height="422" rx="18" fill="url(#accent)"/>
      <circle cx="750" cy="565" r="92" fill="none" stroke="#b7bcc5" stroke-width="22"/>
      <circle cx="750" cy="565" r="18" fill="#b7bcc5"/>
      <text x="543" y="535" text-anchor="middle" font-family="Arial, sans-serif" font-size="42" font-weight="800" fill="#fff">${safeArtist}</text>
      <text x="543" y="594" text-anchor="middle" font-family="Arial, sans-serif" font-size="27" font-weight="700" fill="#fff">${safeTitle}</text>`;
  } else if (categoryId === 5) {
    body = `
      <rect x="350" y="150" width="500" height="780" rx="8" fill="#fdfbf7" filter="url(#shadow)"/>
      <rect x="390" y="190" width="420" height="620" rx="4" fill="url(#accent)"/>
      <text x="600" y="440" text-anchor="middle" font-family="Arial, sans-serif" font-size="64" font-weight="900" fill="#fff">${safeArtist}</text>
      <text x="600" y="520" text-anchor="middle" font-family="Arial, sans-serif" font-size="38" font-weight="700" fill="#fff">${safeTitle}</text>
      <text x="600" y="770" text-anchor="middle" font-family="Arial, sans-serif" font-size="24" letter-spacing="7" fill="#fff">POSTER</text>`;
  } else {
    body = `
      <rect x="326" y="355" width="548" height="360" rx="46" fill="#f9fafb" filter="url(#shadow)"/>
      <circle cx="475" cy="535" r="88" fill="${accent}"/>
      <circle cx="600" cy="535" r="88" fill="${dark}"/>
      <rect x="700" y="450" width="120" height="170" rx="28" fill="url(#accent)"/>
      <text x="600" y="775" text-anchor="middle" font-family="Arial, sans-serif" font-size="48" font-weight="800" fill="${dark}">${safeArtist}</text>
      <text x="600" y="830" text-anchor="middle" font-family="Arial, sans-serif" font-size="28" font-weight="700" fill="#606a78">${safeCategory}</text>`;
  }
  const svg = `<svg xmlns="http://www.w3.org/2000/svg" width="1200" height="1200" viewBox="0 0 1200 1200">${common}${body}<text x="64" y="1120" font-family="Arial, sans-serif" font-size="30" font-weight="800" fill="#111827">${safeProduct}</text></svg>`;
  writeFileSync(join(imgDir, file), svg, 'utf8');
  return file;
}

function ensureSilencePreview() {
  const file = 'pap_preview_silence.wav';
  const target = join(audioDir, file);
  if (existsSync(target)) return file;
  const sampleRate = 44100;
  const seconds = 2;
  const dataSize = sampleRate * seconds * 2;
  const buffer = Buffer.alloc(44 + dataSize);
  buffer.write('RIFF', 0);
  buffer.writeUInt32LE(36 + dataSize, 4);
  buffer.write('WAVE', 8);
  buffer.write('fmt ', 12);
  buffer.writeUInt32LE(16, 16);
  buffer.writeUInt16LE(1, 20);
  buffer.writeUInt16LE(1, 22);
  buffer.writeUInt32LE(sampleRate, 24);
  buffer.writeUInt32LE(sampleRate * 2, 28);
  buffer.writeUInt16LE(2, 32);
  buffer.writeUInt16LE(16, 34);
  buffer.write('data', 36);
  buffer.writeUInt32LE(dataSize, 40);
  writeFileSync(target, buffer);
  return file;
}

function mysqlOut(args, input = null) {
  return execFileSync(mysql, args, { input, encoding: 'utf8', maxBuffer: 1024 * 1024 * 20 });
}

function mysqlRows(query) {
  const out = mysqlOut(['-u', 'root', '--default-character-set=utf8mb4', '-N', '-B', 'greenerry', '-e', query]);
  return out.trim() === '' ? [] : out.trim().split(/\r?\n/).map(line => line.split('\t'));
}

function mysqlExec(query) {
  mysqlOut(['-u', 'root', '--default-character-set=utf8mb4', 'greenerry'], query);
}

function mysqlId(query) {
  const out = mysqlOut(['-u', 'root', '--default-character-set=utf8mb4', '-N', '-B', 'greenerry', '-e', `${query}; SELECT LAST_INSERT_ID();`]);
  return Number(out.trim().split(/\r?\n/).pop());
}

async function fetchJson(url) {
  try {
    const res = await fetch(url, { headers: { 'User-Agent': 'Greenerry PAP final seed' } });
    if (!res.ok) return null;
    return await res.json();
  } catch {
    return null;
  }
}

async function download(url, fileBase, dir) {
  if (!url) return null;
  try {
    const res = await fetch(url, { headers: { 'User-Agent': 'Greenerry PAP final seed' } });
    if (!res.ok) return null;
    const type = res.headers.get('content-type') || '';
    let ext = extname(new URL(url).pathname).split('?')[0].toLowerCase();
    if (!ext || ext.length > 6) {
      ext = type.includes('png') ? '.png' : type.includes('audio') ? '.m4a' : '.jpg';
    }
    if (ext === '.jpeg') ext = '.jpg';
    const file = `${fileBase}${ext}`;
    writeFileSync(join(dir, file), Buffer.from(await res.arrayBuffer()));
    return file;
  } catch {
    return null;
  }
}

async function downloadFirst(urls, fileBase, dir) {
  for (const url of urls.filter(Boolean)) {
    const file = await download(url, fileBase, dir);
    if (file) return file;
  }
  return null;
}

async function wikiImages(title) {
  const data = await fetchJson(`https://en.wikipedia.org/api/rest_v1/page/summary/${encodeURIComponent(title)}`);
  return {
    main: data?.originalimage?.source || data?.thumbnail?.source || null,
    thumb: data?.thumbnail?.source || data?.originalimage?.source || null,
  };
}

async function itunesSearch(term, entity, limit = 8) {
  const data = await fetchJson(`https://itunes.apple.com/search?term=${encodeURIComponent(term)}&entity=${entity}&limit=${limit}&country=US`);
  return data?.results || [];
}

async function itunesAlbum(artist, album) {
  const rows = await itunesSearch(`${artist} ${album}`, 'album', 10);
  const lowerArtist = artist.toLowerCase();
  return rows.find(row => String(row.artistName || '').toLowerCase().includes(lowerArtist) && row.artworkUrl100) || rows.find(row => row.artworkUrl100) || null;
}

async function albumTracks(collectionId) {
  if (!collectionId) return [];
  const data = await fetchJson(`https://itunes.apple.com/lookup?id=${collectionId}&entity=song&country=US`);
  return (data?.results || []).filter(row => row.wrapperType === 'track' && row.kind === 'song');
}

function bigArt(url) {
  return url ? url.replace(/100x100bb\.(jpg|png)$/i, '1200x1200bb.$1').replace(/100x100-999\.(jpg|png)$/i, '1200x1200-999.$1') : null;
}

async function releaseData(artist, title, fallbackGenre) {
  const album = await itunesAlbum(artist, title);
  const tracks = album ? await albumTracks(album.collectionId) : [];
  if (tracks.length > 0) {
    return {
      title: album.collectionName || title,
      type: tracks.length > 3 ? 'Album' : tracks.length > 1 ? 'EP' : 'Single',
      date: (album.releaseDate || tracks[0].releaseDate || '').slice(0, 10) || null,
      art: bigArt(album.artworkUrl100 || tracks[0].artworkUrl100),
      tracks: tracks.slice(0, 6).map((track, index) => ({
        title: track.trackName || `${title} ${index + 1}`,
        genre: track.primaryGenreName || fallbackGenre,
        duration: Math.round((track.trackTimeMillis || 30000) / 1000),
        preview: track.previewUrl || null,
        art: bigArt(track.artworkUrl100 || album.artworkUrl100),
      })),
    };
  }

  const songs = await itunesSearch(`${artist} ${title}`, 'song', 5);
  const song = songs.find(row => row.previewUrl) || songs[0];
  return {
    title: song?.trackName || title,
    type: 'Single',
    date: (song?.releaseDate || '').slice(0, 10) || null,
    art: bigArt(song?.artworkUrl100 || album?.artworkUrl100),
    tracks: [{
      title: song?.trackName || title,
      genre: song?.primaryGenreName || fallbackGenre,
      duration: Math.round((song?.trackTimeMillis || 30000) / 1000),
      preview: song?.previewUrl || null,
      art: bigArt(song?.artworkUrl100 || album?.artworkUrl100),
    }],
  };
}

function insertCliente(name, email, bio, foto, banner, active = 1) {
  return mysqlId(`
    INSERT INTO cliente (nome, email, palavra_passe, foto, banner, bio, slug, estado, criado_em, atualizado_em)
    VALUES (${sql(name)}, ${sql(email)}, ${sql(passwordHash)}, ${sql(foto)}, ${sql(banner)}, ${sql(bio)}, ${sql(slug(name))}, ${active ? sql('ativo') : sql('inativo')}, NOW(), NOW())
  `);
}

function resetDatabase() {
  mysqlExec(`
    SET FOREIGN_KEY_CHECKS=0;
    DELETE FROM produto_review;
    DELETE FROM encomenda_mensagem;
    DELETE FROM morada_encomenda;
    DELETE FROM encomenda_item;
    DELETE FROM encomenda;
    DELETE FROM pagamento;
    DELETE FROM produto_tamanho_stock;
    DELETE FROM produto_imagem;
    DELETE FROM produto;
    DELETE FROM faixa_listen;
    DELETE FROM favorito_musica;
    DELETE FROM seguir_artista;
    DELETE FROM playlist_faixa;
    DELETE FROM playlist;
    DELETE FROM mensagem_admin;
    DELETE FROM notificacao;
    DELETE f FROM faixa f JOIN release_musical r ON r.idRelease=f.idRelease WHERE r.idCliente <> 5;
    DELETE FROM release_musical WHERE idCliente <> 5;
    DELETE FROM cliente WHERE idCliente <> 5;
    UPDATE cliente SET nome='Green', slug='green', estado='ativo', bio='Projetos pessoais, demos e lancamentos originais dentro da Greenerry.' WHERE idCliente=5;
    UPDATE release_musical SET estado='aprovado', ativo=1, bloqueado_admin=0, aprovado_em=NOW() WHERE idCliente=5;
    UPDATE faixa f JOIN release_musical r ON r.idRelease=f.idRelease SET f.estado='aprovada', f.ativo=1 WHERE r.idCliente=5;
    SET FOREIGN_KEY_CHECKS=1;
  `);
}

async function main() {
  resetDatabase();

  const releaseImagesByArtist = new Map();
  const artistIds = new Map([['Green', 5]]);
const allTrackIds = [];
const allProducts = [];

  for (const artist of artists) {
    console.log(`Preparing ${artist.name}`);
    const wiki = await wikiImages(artist.wiki);
    const firstRelease = await releaseData(artist.name, artist.releases[0], artist.genre);
    const avatar = await downloadFirst([artist.image, wiki.main, wiki.thumb, firstRelease.art], `pap_final_avatar_${slug(artist.name)}`, imgDir);
    const banner = await downloadFirst([firstRelease.art, artist.image, wiki.main, wiki.thumb], `pap_final_banner_${slug(artist.name)}`, imgDir);
    const idCliente = insertCliente(artist.name, emailFor(artist.name), artist.bio, avatar, banner, true);
    artistIds.set(artist.name, idCliente);
    releaseImagesByArtist.set(artist.name, [firstRelease.art, wiki.main].filter(Boolean));

    const wanted = [firstRelease, ...(await Promise.all([...artist.releases.slice(1), ...artist.singles].map(title => releaseData(artist.name, title, artist.genre))))];
    const artistTrackTitles = new Set();
    let releaseIndex = 0;
    for (const rel of wanted) {
      const cover = await downloadFirst([rel.art, firstRelease.art, artist.image, wiki.main, wiki.thumb, banner ? `file://${join(imgDir, banner).replace(/\\/g, '/')}` : null], `pap_final_release_${slug(artist.name)}_${releaseIndex + 1}`, imgDir);
      const idRelease = mysqlId(`
        INSERT INTO release_musical (idCliente, titulo, tipo, descricao, capa, data_lancamento, estado, ativo, bloqueado_admin, idAdminAprovacao, aprovado_em, criado_em, atualizado_em)
        VALUES (${idCliente}, ${sql(rel.title)}, ${sql(rel.type)}, ${sql(`Lancamento de ${artist.name} preparado para a biblioteca musical da Greenerry, com capa real e previews legais para demonstracao.`)}, ${sql(cover)}, ${sql(rel.date)}, 'aprovado', 1, 0, 1, NOW(), NOW(), NOW())
      `);
      let trackNo = 1;
      for (const track of rel.tracks.slice(0, rel.type === 'Album' ? 6 : 3)) {
        const trackKey = String(track.title || '').trim().toLowerCase();
        if (trackKey && artistTrackTitles.has(trackKey)) continue;
        if (trackKey) artistTrackTitles.add(trackKey);
        const audio = await download(track.preview, `pap_preview_${slug(artist.name)}_${slug(track.title)}`, audioDir) || ensureSilencePreview();
        const idFaixa = mysqlId(`
          INSERT INTO faixa (idRelease, numero_faixa, titulo, genero, ficheiro_audio, duracao_segundos, estado, ativo, criado_em, atualizado_em)
          VALUES (${idRelease}, ${trackNo}, ${sql(track.title)}, ${sql(track.genre || artist.genre)}, ${sql(audio || '')}, ${track.duration || 30}, 'aprovada', 1, NOW(), NOW())
        `);
        allTrackIds.push(idFaixa);
        trackNo += 1;
      }
      releaseIndex += 1;
    }

    const artPool = releaseImagesByArtist.get(artist.name);
    const productCount = artist.name === 'Fakemink' || artist.name === 'The Hellp' ? 4 : 5;
    for (let i = 0; i < productCount; i++) {
      const type = productTypes[i % productTypes.length];
      const mainTitle = wanted[i % wanted.length]?.title || artist.releases[0];
      const pending = (allProducts.length + i) % 11 === 0;
      const stock = 18 + ((allProducts.length + i) % 27);
      const price = Number(type.price + ((allProducts.length + i) % 5) * 3).toFixed(2);
      const productName = `${artist.name} ${mainTitle} ${type.suffix}`;
      const categoryName = productTypes.find((item) => item.category === type.category)?.suffix || 'merch';
      const productDescription = type.category === 1
        ? `T-shirt de algodao com grafismo frontal inspirado em ${mainTitle}. Corte regular, ideal para um look de concerto ou uso diario.`
        : type.category === 2
          ? `Hoodie macio com bolso frontal, capuz ajustavel e grafismo de ${artist.name} inspirado em ${mainTitle}.`
          : type.category === 3
            ? `Edicao em vinil para colecao, com embalagem visual de ${mainTitle} e acabamento premium para exposicao.`
            : type.category === 4
              ? `CD deluxe em caixa fisica com arte de ${mainTitle}, pensado para colecionadores e apresentacao de loja.`
              : type.category === 5
                ? `Poster decorativo de parede com direcao visual de ${artist.name}, preparado para quartos, estudio ou espaco criativo.`
                : `Pack de acessorios inspirado em ${mainTitle}, com pins, pouch e detalhes visuais do universo de ${artist.name}.`;
      const idProduto = mysqlId(`
        INSERT INTO produto (idCliente, idCategoria, nomeProduto, descricaoProduto, marca, precoAtual, iva_percentual, comissao_percentual, stock_total, usa_tamanhos, estado, ativo, bloqueado_admin, idAdminAprovacao, aprovado_em, criado_em, atualizado_em)
        VALUES (${idCliente}, ${type.category}, ${sql(productName)}, ${sql(productDescription)}, ${sql(artist.name)}, ${price}, 23.00, 5.00, ${stock}, ${type.sizes ? 1 : 0}, ${sql(pending ? 'pendente' : 'aprovado')}, 1, 0, ${pending ? 'NULL' : '1'}, ${pending ? 'NULL' : 'NOW()'}, NOW(), NOW())
      `);
      for (let order = 0; order < 3; order++) {
        const productImage = writeProductMockup({
          artist: artist.name,
          title: order === 0 ? mainTitle : wanted[(i + order) % wanted.length]?.title || mainTitle,
          categoryId: type.category,
          categoryName,
          productName,
          fileBase: `pap_final_product_${slug(artist.name)}_${idProduto}_${order + 1}`,
        });
        mysqlExec(`INSERT INTO produto_imagem (idProduto, ficheiro, ordem, criado_em) VALUES (${idProduto}, ${sql(productImage)}, ${order}, NOW());`);
      }
      if (type.sizes) {
        for (const sizeId of [1, 2, 3, 4]) {
          mysqlExec(`INSERT INTO produto_tamanho_stock (idProduto, idTamanho, stock) VALUES (${idProduto}, ${sizeId}, ${Math.max(2, Math.floor(stock / 4))});`);
        }
      }
      allProducts.push({ idProduto, artist: artist.name, price: Number(price), idCliente });
    }
  }

  const buyerIds = [];
  for (const [name, email] of demoBuyers) {
    buyerIds.push(insertCliente(name, email, 'Conta de cliente usada para testar compras, avaliacoes, favoritos e suporte.', null, null, true));
  }

  const trackArtists = mysqlRows(`
    SELECT f.idFaixa, r.idCliente
    FROM faixa f
    JOIN release_musical r ON r.idRelease=f.idRelease
  `).reduce((map, row) => map.set(Number(row[0]), Number(row[1])), new Map());

  for (let i = 0; i < allTrackIds.length; i++) {
    const plays = 4 + (i % 18);
    for (let n = 0; n < plays; n++) {
      const buyer = buyerIds[(i + n) % buyerIds.length];
      mysqlExec(`INSERT INTO faixa_listen (idFaixa, idCliente, idArtista, segundos_ouvidos, criado_em) VALUES (${allTrackIds[i]}, ${buyer}, ${trackArtists.get(allTrackIds[i]) || 5}, ${20 + (n % 45)}, DATE_SUB(NOW(), INTERVAL ${(i + n) % 60} DAY));`);
    }
  }

  const reviewTargets = [];
  for (let i = 0; i < 36; i++) {
    const buyer = buyerIds[i % buyerIds.length];
    const productA = allProducts[i % allProducts.length];
    const productB = allProducts[(i * 3 + 7) % allProducts.length];
    const state = orderStates[i % orderStates.length];
    const total = Number((productA.price + (i % 3 === 0 ? productB.price : 0)).toFixed(2));
    const subtotal = total;
    const ivaTotal = Number((subtotal * 0.23).toFixed(2));
    const commission = Number((subtotal * 0.05).toFixed(2));
    const payMethod = i % 4 === 0 ? 'mbway' : i % 3 === 0 ? 'transferencia' : 'cartao';
    const idEnc = mysqlId(`
      INSERT INTO encomenda (idCliente, subtotal, iva_total, comissao_total, total_final, estado_encomenda, estado_pagamento, metodo_pagamento, nif, observacoes, pago_em, enviado_em, entregue_em, criado_em, atualizado_em)
      VALUES (${buyer}, ${subtotal}, ${ivaTotal}, ${commission}, ${total}, ${sql(state)}, ${sql(state === 'cancelada' ? 'reembolsado' : 'pago')}, ${sql(payMethod)}, ${sql(`2${String(10000000 + i).slice(1)}`)}, ${sql(state === 'cancelada' ? 'Encomenda cancelada para demonstrar o fluxo de reembolso.' : 'Encomenda criada para demonstracao da PAP.')}, DATE_SUB(NOW(), INTERVAL ${i} DAY), ${state === 'enviada' || state === 'entregue' ? `DATE_SUB(NOW(), INTERVAL ${Math.max(0, i - 2)} DAY)` : 'NULL'}, ${state === 'entregue' ? `DATE_SUB(NOW(), INTERVAL ${Math.max(0, i - 4)} DAY)` : 'NULL'}, DATE_SUB(NOW(), INTERVAL ${i} DAY), DATE_SUB(NOW(), INTERVAL ${Math.max(0, i - 1)} DAY))
    `);
    const itemState = state === 'enviada' ? 'enviado' : state === 'cancelada' ? 'cancelado' : state;
    const insertItem = (product, sizeId = null) => {
      const iva = Number((product.price * 0.23).toFixed(2));
      const com = Number((product.price * 0.05).toFixed(2));
      const totalLine = Number((product.price).toFixed(2));
      mysqlExec(`INSERT INTO encomenda_item (idEncomenda, idProduto, idArtista, idTamanho, nome_produto, categoria_nome, quantidade, preco_unitario, iva_percentual, iva_valor, comissao_percentual, comissao_valor, subtotal_linha, total_linha, valor_artista, estado_item) VALUES (${idEnc}, ${product.idProduto}, ${product.idCliente}, ${sizeId || 'NULL'}, ${sql(product.artist)}, ${sql('Merchandising')}, 1, ${product.price}, 23.00, ${iva}, 5.00, ${com}, ${product.price}, ${totalLine}, ${Number((product.price - com).toFixed(2))}, ${sql(itemState)});`);
    };
    insertItem(productA, i % 2 === 0 ? 2 : null);
    reviewTargets.push({ idEncomenda: idEnc, idProduto: productA.idProduto, idCliente: buyer });
    if (i % 3 === 0) {
      insertItem(productB);
    }
    mysqlExec(`INSERT INTO morada_encomenda (idEncomenda, nome_destinatario, morada, cidade, codigo_postal, pais, telefone) VALUES (${idEnc}, ${sql(demoBuyers[i % demoBuyers.length][0])}, ${sql(`Rua das Artes ${20 + i}, ${i % 3 === 0 ? '1 Esq.' : '2 Dto.'}`)}, ${sql(i % 2 === 0 ? 'Porto' : 'Lisboa')}, ${sql(`4${String(100 + i).padStart(3, '0')}-0${String(10 + i).padStart(2, '0')}`)}, 'Portugal', ${sql(`9${String(10000000 + i).slice(1)}`)});`);
    if (i % 5 === 0) {
      mysqlExec(`INSERT INTO encomenda_mensagem (idEncomenda, idProduto, idComprador, idArtista, remetente, mensagem, lida, criado_em) VALUES (${idEnc}, ${productA.idProduto}, ${buyer}, ${productA.idCliente}, 'comprador', ${sql('Ola, queria confirmar se a encomenda segue esta semana.')}, 0, NOW());`);
      mysqlExec(`INSERT INTO encomenda_mensagem (idEncomenda, idProduto, idComprador, idArtista, remetente, mensagem, lida, criado_em) VALUES (${idEnc}, ${productA.idProduto}, ${buyer}, ${productA.idCliente}, 'artista', ${sql('Sim, estamos a preparar tudo e enviamos a atualizacao em breve.')}, 0, NOW());`);
    }
  }

  for (let i = 0; i < reviewTargets.length; i++) {
    const target = reviewTargets[i];
    mysqlExec(`
      INSERT INTO produto_review (idProduto, idCliente, idEncomenda, rating, comentario, criado_em)
      VALUES (${target.idProduto}, ${target.idCliente}, ${target.idEncomenda}, ${4 + (i % 2)}, ${sql(i % 3 === 0 ? 'Qualidade muito boa e imagem fiel ao artista.' : i % 3 === 1 ? 'Entrega rapida, produto bem apresentado.' : 'Perfeito para a colecao e para oferecer.')}, DATE_SUB(NOW(), INTERVAL ${i} DAY))
    `);
  }

  const artistIdList = [...artistIds.values()].filter(id => id !== 5);
  for (let i = 0; i < buyerIds.length; i++) {
    for (let n = 0; n < 6; n++) {
      mysqlExec(`INSERT IGNORE INTO seguir_artista (idSeguidor, idArtista, criado_em) VALUES (${buyerIds[i]}, ${artistIdList[(i + n) % artistIdList.length]}, DATE_SUB(NOW(), INTERVAL ${n} DAY));`);
    }
    mysqlExec(`INSERT INTO mensagem_admin (idCliente, assunto, mensagem, resposta_admin, estado, idAdminResposta, criado_em, respondido_em) VALUES (${buyerIds[i]}, ${sql('Ajuda com encomenda')}, ${sql('Preciso de ajuda para acompanhar uma compra feita na loja.')}, ${i % 2 === 0 ? 'NULL' : sql('Ola, ja confirmamos a encomenda e fica tudo atualizado na area de compras.')}, ${sql(i % 2 === 0 ? 'aberta' : 'respondida')}, ${i % 2 === 0 ? 'NULL' : '1'}, DATE_SUB(NOW(), INTERVAL ${i} DAY), ${i % 2 === 0 ? 'NULL' : 'NOW()'});`);
  }

  mysqlExec(`
    UPDATE produto p SET stock_total = GREATEST(stock_total, COALESCE((SELECT SUM(stock) FROM produto_tamanho_stock pts WHERE pts.idProduto=p.idProduto), stock_total));
    INSERT INTO notificacao (idCliente, titulo, mensagem, tipo, lida, criado_em)
    SELECT idCliente, 'Bem-vindo a Greenerry', 'O teu perfil artistico esta pronto para a demonstracao final.', 'sistema', 0, NOW()
    FROM cliente
    WHERE idCliente <> 5 AND EXISTS (SELECT 1 FROM release_musical r WHERE r.idCliente=cliente.idCliente);
  `);

  const counts = mysqlRows(`
    SELECT 'artistas', COUNT(*) FROM cliente WHERE EXISTS (SELECT 1 FROM release_musical r WHERE r.idCliente=cliente.idCliente)
    UNION ALL SELECT 'releases', COUNT(*) FROM release_musical
    UNION ALL SELECT 'faixas', COUNT(*) FROM faixa
    UNION ALL SELECT 'produtos', COUNT(*) FROM produto
    UNION ALL SELECT 'encomendas', COUNT(*) FROM encomenda
    UNION ALL SELECT 'avaliacoes', COUNT(*) FROM produto_review
  `);
  console.log(counts.map(row => `${row[0]}=${row[1]}`).join('\n'));
}

main().catch(error => {
  console.error(error);
  process.exit(1);
});
