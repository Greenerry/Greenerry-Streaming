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
  { name: 'The Hellp', wiki: 'The Hellp', genre: 'Alternative', bio: 'Projeto alternativo com energia electronica, moda urbana e som directo de clube underground.', releases: ['LL'], singles: ['Tu Tu Neurotic'] },
  { name: 'Rihanna', wiki: 'Rihanna', genre: 'Pop', bio: 'Artista global de Barbados, reconhecida pela versatilidade entre pop, R&B, dancehall e moda.', releases: ['ANTI', 'LOUD'], singles: ['Work'] },
  { name: 'Young Thug', wiki: 'Young Thug', genre: 'Hip hop', bio: 'Rapper norte-americano com voz elastica, flow experimental e enorme influencia no trap moderno.', releases: ['So Much Fun', 'JEFFERY'], singles: ['Hot'] },
  { name: 'Dean Blunt', wiki: 'Dean Blunt', genre: 'Experimental', bio: 'Musico britanico de linguagem minimalista, ambigua e experimental, entre pop, dub e art music.', releases: ['Black Metal', 'The Redeemer'], singles: ['100'] },
  { name: '2hollis', wiki: '2hollis', genre: 'Electronic', bio: 'Artista de som digital e energico, cruzando rap, electronica e estetica hiper-online.', releases: ['boy', '2'], singles: ['jeans'] },
  { name: 'Cities Aviv', wiki: 'Cities Aviv', genre: 'Experimental hip hop', bio: 'Projeto de hip hop experimental com colagens densas, texturas lo-fi e narrativa introspectiva.', releases: ['Man Plays the Horn', 'Working Title For The Album Secret Waters'], singles: ['URL IRL'] },
  { name: 'Fakemink', wiki: 'Fakemink', genre: 'Alternative rap', bio: 'Artista de cena digital underground, com som rapido, visual cru e energia de internet club.', releases: ['London Savant'], singles: ['Fashion Week'], image: 'https://commons.wikimedia.org/wiki/Special:Redirect/file/Fakemink%20performing%20in%20London%2C%20photographed%20by%20Teddy%20Westside.jpg' },
  { name: 'Miguel', wiki: 'Miguel (singer)', genre: 'R&B', bio: 'Cantor e compositor norte-americano que une R&B moderno, soul, rock e sensualidade futurista.', releases: ['Kaleidoscope Dream', 'Wildheart'], singles: ['Sure Thing'] },
  { name: 'Childish Gambino', wiki: 'Donald Glover', genre: 'Funk', bio: 'Projeto musical de Donald Glover, com fases entre rap, soul, funk psicadelico e pop conceptual.', releases: ['Awaken, My Love!', 'Because the Internet'], singles: ['Redbone'] },
  { name: 'Kanye West', wiki: 'Kanye West', genre: 'Hip hop', bio: 'Produtor e rapper norte-americano, importante pela producao, conceito visual e impacto cultural.', releases: ['Graduation', 'My Beautiful Dark Twisted Fantasy'], singles: ['Stronger'] },
  { name: 'Justin Bieber', wiki: 'Justin Bieber', genre: 'Pop', bio: 'Artista canadiano de pop e R&B, conhecido por grandes singles globais e varias fases visuais.', releases: ['Purpose', 'Justice'], singles: ['Sorry'] },
  { name: 'Lil Uzi Vert', wiki: 'Lil Uzi Vert', genre: 'Rap', bio: 'Rapper norte-americano de trap melodico, visual colorido e forte ligacao a moda e cultura digital.', releases: ['Luv Is Rage 2', 'Pink Tape'], singles: ['XO Tour Llif3'] },
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
    let releaseIndex = 0;
    for (const rel of wanted) {
      const cover = await downloadFirst([rel.art, firstRelease.art, artist.image, wiki.main, wiki.thumb, banner ? `file://${join(imgDir, banner).replace(/\\/g, '/')}` : null], `pap_final_release_${slug(artist.name)}_${releaseIndex + 1}`, imgDir);
      const idRelease = mysqlId(`
        INSERT INTO release_musical (idCliente, titulo, tipo, descricao, capa, data_lancamento, estado, ativo, bloqueado_admin, idAdminAprovacao, aprovado_em, criado_em, atualizado_em)
        VALUES (${idCliente}, ${sql(rel.title)}, ${sql(rel.type)}, ${sql(`Lancamento de ${artist.name} preparado para a biblioteca musical da Greenerry, com capa real e previews legais para demonstracao.`)}, ${sql(cover)}, ${sql(rel.date)}, 'aprovado', 1, 0, 1, NOW(), NOW(), NOW())
      `);
      let trackNo = 1;
      for (const track of rel.tracks.slice(0, rel.type === 'Album' ? 6 : 3)) {
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
      const idProduto = mysqlId(`
        INSERT INTO produto (idCliente, idCategoria, nomeProduto, descricaoProduto, marca, precoAtual, iva_percentual, comissao_percentual, stock_total, usa_tamanhos, estado, ativo, bloqueado_admin, idAdminAprovacao, aprovado_em, criado_em, atualizado_em)
        VALUES (${idCliente}, ${type.category}, ${sql(`${artist.name} ${mainTitle} ${type.suffix}`)}, ${sql(`Artigo de merchandising inspirado em ${mainTitle}, com imagem real do universo visual de ${artist.name}. Produto preparado para uma demonstracao profissional da loja Greenerry.`)}, ${sql(artist.name)}, ${price}, 23.00, 5.00, ${stock}, ${type.sizes ? 1 : 0}, ${sql(pending ? 'pendente' : 'aprovado')}, 1, 0, ${pending ? 'NULL' : '1'}, ${pending ? 'NULL' : 'NOW()'}, NOW(), NOW())
      `);
      const savedProductImages = [];
      for (let order = 0; order < 3; order++) {
        const imageUrl = artPool[(i + order) % artPool.length] || firstRelease.art || artist.image || wiki.main || wiki.thumb;
        const productImage = await downloadFirst([imageUrl, firstRelease.art, artist.image, wiki.main, wiki.thumb], `pap_final_product_${slug(artist.name)}_${idProduto}_${order + 1}`, imgDir);
        if (productImage) {
          mysqlExec(`INSERT INTO produto_imagem (idProduto, ficheiro, ordem, criado_em) VALUES (${idProduto}, ${sql(productImage)}, ${order}, NOW());`);
          savedProductImages.push(productImage);
        }
      }
      if (savedProductImages.length === 0) {
        for (const [order, fallback] of [avatar, banner].filter(Boolean).entries()) {
          mysqlExec(`INSERT INTO produto_imagem (idProduto, ficheiro, ordem, criado_em) VALUES (${idProduto}, ${sql(fallback)}, ${order}, NOW());`);
        }
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
