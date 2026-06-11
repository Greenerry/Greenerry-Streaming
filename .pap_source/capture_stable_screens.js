const { spawn } = require('node:child_process');
const fs = require('node:fs/promises');
const path = require('node:path');

const root = 'C:/xampp/htdocs/dashboard/greenerry';
const outDir = path.join(root, '.pap_source/report_assets/figures');
const edge = 'C:/Program Files (x86)/Microsoft/Edge/Application/msedge.exe';
const port = 9333 + Math.floor(Math.random() * 300);
const profile = path.join(process.env.TEMP || 'C:/Windows/Temp', `greenerry-capture-${Date.now()}`);
const base = 'http://localhost/dashboard/greenerry';

const cartItem = {
  key: '759:2',
  id: 759,
  name: 'The Weeknd After Hours official graphic t-shirt',
  price: 34.99,
  img: 'pap_real_product_759_1_the-weeknd_t-shirt.jpg',
  qty: 2,
  stock: 5,
  sizeId: 2,
  sizeName: 'M'
};

function delay(ms) {
  return new Promise((resolve) => setTimeout(resolve, ms));
}

async function fetchJson(url) {
  const res = await fetch(url);
  if (!res.ok) throw new Error(`${res.status} ${res.statusText}: ${url}`);
  return res.json();
}

function connect(wsUrl) {
  const ws = new WebSocket(wsUrl);
  let id = 0;
  const pending = new Map();
  const listeners = new Map();

  ws.addEventListener('message', (event) => {
    const msg = JSON.parse(event.data);
    if (msg.id && pending.has(msg.id)) {
      const { resolve, reject } = pending.get(msg.id);
      pending.delete(msg.id);
      if (msg.error) reject(new Error(msg.error.message || JSON.stringify(msg.error)));
      else resolve(msg.result || {});
      return;
    }
    const callbacks = listeners.get(msg.method) || [];
    callbacks.forEach((cb) => cb(msg.params || {}));
  });

  return new Promise((resolve, reject) => {
    ws.addEventListener('open', () => {
      resolve({
        send(method, params = {}) {
          const msgId = ++id;
          ws.send(JSON.stringify({ id: msgId, method, params }));
          return new Promise((res, rej) => pending.set(msgId, { resolve: res, reject: rej }));
        },
        on(method, cb) {
          if (!listeners.has(method)) listeners.set(method, []);
          listeners.get(method).push(cb);
        },
        close() {
          ws.close();
        }
      });
    });
    ws.addEventListener('error', reject);
  });
}

async function waitForDebug() {
  const url = `http://127.0.0.1:${port}/json/version`;
  for (let i = 0; i < 80; i++) {
    try {
      return await fetchJson(url);
    } catch {
      await delay(250);
    }
  }
  throw new Error('Browser debug port did not become available.');
}

async function main() {
  await fs.mkdir(outDir, { recursive: true });
  await fs.rm(profile, { recursive: true, force: true });
  await fs.mkdir(profile, { recursive: true });

  const browser = spawn(edge, [
    '--headless=new',
    '--disable-gpu',
    '--no-first-run',
    '--disable-background-networking',
    `--remote-debugging-port=${port}`,
    `--user-data-dir=${profile}`,
    'about:blank'
  ], { stdio: 'ignore' });

  try {
    const version = await waitForDebug();
    const cdp = await connect(version.webSocketDebuggerUrl);
    const target = await cdp.send('Target.createTarget', { url: 'about:blank' });
    const session = await cdp.send('Target.attachToTarget', { targetId: target.targetId, flatten: true });
    const sessionId = session.sessionId;
    const send = (method, params = {}) => cdp.send('Target.sendMessageToTarget', {
      sessionId,
      message: JSON.stringify({ id: Math.floor(Math.random() * 1e9), method, params })
    });

    // Use page-level WebSocket too, because captureScreenshot result is easier to read there.
    const pages = await fetchJson(`http://127.0.0.1:${port}/json/list`);
    const pageTarget = pages.find((p) => p.id === target.targetId) || pages.find((p) => p.type === 'page');
    const page = await connect(pageTarget.webSocketDebuggerUrl);
    await page.send('Page.enable');
    await page.send('Runtime.enable');
    await page.send('Emulation.setDeviceMetricsOverride', {
      width: 1440,
      height: 1050,
      deviceScaleFactor: 1,
      mobile: false
    });

    const stableCss = `
      *,*::before,*::after{animation-duration:0s!important;animation-delay:0s!important;transition-duration:0s!important;transition-delay:0s!important;scroll-behavior:auto!important}
      .reveal,.is-revealing,.media-hidden{opacity:1!important;filter:none!important;transform:none!important}
      .section-media-cloud,.home-hero-side,.content-shell--cloud::before,.content-shell--cloud::after{filter:none!important;opacity:.92!important}
      body{caret-color:transparent!important}
    `;

    async function nav(url, opts = {}) {
      let loaded = false;
      const loadedPromise = new Promise((resolve) => {
        const done = () => {
          if (!loaded) {
            loaded = true;
            resolve();
          }
        };
        page.on('Page.loadEventFired', done);
        setTimeout(done, 9000);
      });
      await page.send('Page.navigate', { url });
      await loadedPromise;
      await delay(opts.wait || 3500);
      await page.send('Runtime.evaluate', {
        expression: `(() => {
          const style = document.createElement('style');
          style.textContent = ${JSON.stringify(stableCss)};
          document.head.appendChild(style);
          localStorage.setItem('g_lang','pt');
          localStorage.setItem('g_theme','dark');
          localStorage.setItem('g_stream_sidebar_expanded_v6','0');
          window.scrollTo(0, ${opts.scrollY || 0});
          const adminScroll = document.querySelector('.admin-main-scroll');
          if (adminScroll) adminScroll.scrollTop = ${opts.adminScrollY || 0};
        })();`,
        awaitPromise: true
      });
      await delay(opts.afterStyle || 700);
    }

    async function screenshot(name) {
      const result = await page.send('Page.captureScreenshot', {
        format: 'png',
        captureBeyondViewport: false,
        fromSurface: true
      });
      await fs.writeFile(path.join(outDir, name), Buffer.from(result.data, 'base64'));
      console.log(name);
    }

    async function setCartAndPlayer() {
      await page.send('Runtime.evaluate', {
        expression: `(() => {
          localStorage.setItem('g_cart', ${JSON.stringify(JSON.stringify([cartItem]))});
          const track = {
            id: 899,
            title: 'Hardest To Love',
            artist: 'The Weeknd',
            cover: '${base}/assets/img/pap_final_release_the-weeknd_1.jpg',
            audio: '${base}/assets/audio/pap_silence.mp3',
            artistId: 214,
            artistFoto: '${base}/assets/img/pap_final_avatar_the-weeknd.jpg',
            playing: false,
            currentTime: 42,
            duration: 219,
            volume: 0.7,
            srOpen: true,
            queue: [
              {id:903,title:'Dawn FM',artist:'The Weeknd',cover:'${base}/assets/img/pap_final_release_the-weeknd_2.jpg',audio:'${base}/assets/audio/pap_silence.mp3',artistId:214,artistFoto:'${base}/assets/img/pap_final_avatar_the-weeknd.jpg'},
              {id:915,title:'Blinding Lights',artist:'The Weeknd',cover:'${base}/assets/img/pap_final_release_the-weeknd_4.jpg',audio:'${base}/assets/audio/pap_silence.mp3',artistId:214,artistFoto:'${base}/assets/img/pap_final_avatar_the-weeknd.jpg'}
            ],
            history: [],
            loop: false,
            contextTracks: [],
            contextIndex: -1,
            contextMode: 'global'
          };
          localStorage.setItem('g_track', JSON.stringify(track));
          sessionStorage.setItem('g_track', JSON.stringify(track));
        })();`,
        awaitPromise: true
      });
    }

    async function forceOpenBothSidebars() {
      await page.send('Runtime.evaluate', {
        expression: `(() => {
          document.body.classList.add('sidebar-expanded','player-open');
          document.documentElement.classList.add('player-open');
          document.querySelector('.main')?.classList.add('pb-open','sr-open');
          document.getElementById('main-nav')?.classList.add('sr-open');
          document.getElementById('player-bar')?.classList.add('sr-open');
          const sr = document.getElementById('sr');
          if (sr) sr.classList.add('open');
          if (typeof openSr === 'function') openSr();
          const cover = '${base}/assets/img/pap_final_release_the-weeknd_1.jpg';
          const avatar = '${base}/assets/img/pap_final_avatar_the-weeknd.jpg';
          const setText = (id, value) => { const el = document.getElementById(id); if (el) el.textContent = value; };
          setText('np-track', 'Hardest To Love');
          setText('np-artist', 'The Weeknd');
          setText('pb-title', 'Hardest To Love');
          setText('pb-artist', 'The Weeknd');
          setText('sr-artist-name', 'The Weeknd');
          const np = document.getElementById('np-img');
          const pb = document.getElementById('pb-img');
          const av = document.querySelector('#sr-artist-avatar');
          if (np) { np.src = cover; np.classList.remove('media-hidden'); }
          if (pb) { pb.src = cover; pb.classList.remove('media-hidden'); }
          if (av) av.innerHTML = '<img src="' + avatar + '" alt="The Weeknd">';
          const srBody = document.getElementById('sr-body');
          const srEmpty = document.getElementById('sr-empty');
          if (srBody) srBody.style.display = 'flex';
          if (srEmpty) srEmpty.style.display = 'none';
          const q = document.getElementById('queue-list');
          if (q) q.innerHTML = '<div class="queue-item"><img src="${base}/assets/img/pap_final_release_the-weeknd_2.jpg" alt=""><div><strong>Dawn FM</strong><span>The Weeknd</span></div><button type="button">▶</button></div><div class="queue-item"><img src="${base}/assets/img/pap_final_release_the-weeknd_4.jpg" alt=""><div><strong>Blinding Lights</strong><span>The Weeknd</span></div><button type="button">▶</button></div>';
          if (typeof updateCartBadgeGlobal === 'function') updateCartBadgeGlobal();
        })();`,
        awaitPromise: true
      });
      await delay(900);
    }

    async function setAdminScroll(y) {
      await page.send('Runtime.evaluate', {
        expression: `(() => {
          const adminScroll = document.querySelector('.admin-main-scroll');
          if (adminScroll) adminScroll.scrollTop = ${Number(y) || 0};
          else window.scrollTo(0, ${Number(y) || 0});
        })();`,
        awaitPromise: true
      });
      await delay(900);
    }

    async function openAccountMenu() {
      await page.send('Runtime.evaluate', {
        expression: `(() => {
          const menu = document.querySelector('[data-account-menu]');
          const button = document.querySelector('[data-account-toggle]');
          if (button) button.click();
          if (menu) {
            const popover = menu.querySelector('.account-popover');
            if (popover) popover.hidden = false;
          }
        })();`,
        awaitPromise: true
      });
      await delay(700);
    }

    await nav(`${base}/pages/login.php`, { wait: 3200 });
    await screenshot('fig_login_user_only.png');
    await nav(`${base}/admin/login.php`, { wait: 3200 });
    await screenshot('fig_admin_login_reserved.png');

    await nav(`${base}/docs/PAP_ENTREGA/_capture_session.php?mode=user&target=home`, { wait: 4200 });
    await setCartAndPlayer();
    await nav(`${base}/pages/music.php`, { wait: 5200 });
    await screenshot('fig_music_stable.png');
    await openAccountMenu();
    await screenshot('fig_profile_dropdown.png');
    await nav(`${base}/pages/artists.php`, { wait: 5200 });
    await screenshot('fig_artists_stable.png');
    await nav(`${base}/pages/shop.php`, { wait: 5200 });
    await screenshot('fig_shop_stable.png');
    await nav(`${base}/pages/produto.php?id=759`, { wait: 5200 });
    await page.send('Runtime.evaluate', { expression: "document.querySelector('#product-size') && (document.querySelector('#product-size').value='2', document.querySelector('#product-size').dispatchEvent(new Event('change')));" });
    await delay(800);
    await screenshot('fig_product_detail_cart.png');

    await nav(`${base}/pages/cart.php`, { wait: 4200 });
    await screenshot('fig_cart_filled.png');
    await nav(`${base}/pages/checkout.php`, { wait: 4200 });
    await screenshot('fig_checkout_filled.png');

    await nav(`${base}/pages/music.php`, { wait: 4200 });
    await forceOpenBothSidebars();
    await screenshot('fig_sidebars_open_player.png');

    await nav(`${base}/docs/PAP_ENTREGA/_capture_session.php?mode=user&target=profile`, { wait: 4200 });
    await screenshot('fig_user_profile.png');
    await nav(`${base}/docs/PAP_ENTREGA/_capture_session.php?mode=user&target=library`, { wait: 4200 });
    await screenshot('fig_user_library.png');
    await nav(`${base}/docs/PAP_ENTREGA/_capture_session.php?mode=user&target=notifications`, { wait: 4200 });
    await screenshot('fig_user_notifications.png');
    await nav(`${base}/docs/PAP_ENTREGA/_capture_session.php?mode=user&target=support`, { wait: 4200 });
    await screenshot('fig_user_support.png');

    await nav(`${base}/docs/PAP_ENTREGA/_capture_session.php?mode=artist&target=artist_products`, { wait: 4200 });
    await screenshot('fig_artist_products.png');
    await nav(`${base}/docs/PAP_ENTREGA/_capture_session.php?mode=artist&target=upload_music`, { wait: 4200 });
    await screenshot('fig_artist_upload_music.png');
    await nav(`${base}/docs/PAP_ENTREGA/_capture_session.php?mode=artist&target=upload_merch`, { wait: 4200 });
    await screenshot('fig_artist_upload_merch.png');

    await nav(`${base}/docs/PAP_ENTREGA/_capture_session.php?mode=admin&target=products`, { wait: 4200 });
    await screenshot('fig_admin_products_review.png');
    await setAdminScroll(760);
    await screenshot('fig_admin_products_images.png');

    await nav(`${base}/docs/PAP_ENTREGA/_capture_session.php?mode=admin&target=orders`, { wait: 4200 });
    await screenshot('fig_admin_orders.png');
    await nav(`${base}/docs/PAP_ENTREGA/_capture_session.php?mode=admin&target=home_curator`, { wait: 4200 });
    await screenshot('fig_admin_home_curator.png');
    await nav(`${base}/docs/PAP_ENTREGA/_capture_session.php?mode=admin&target=categories`, { wait: 4200 });
    await screenshot('fig_admin_categories.png');
    await nav(`${base}/docs/PAP_ENTREGA/_capture_session.php?mode=admin&target=releases`, { wait: 4200 });
    await screenshot('fig_admin_releases.png');
    await nav(`${base}/docs/PAP_ENTREGA/_capture_session.php?mode=admin&target=users`, { wait: 4200 });
    await screenshot('fig_admin_users.png');
    await nav(`${base}/docs/PAP_ENTREGA/_capture_session.php?mode=admin&target=messages`, { wait: 4200 });
    await screenshot('fig_admin_messages.png');
    await nav(`${base}/docs/PAP_ENTREGA/_capture_session.php?mode=admin&target=music`, { wait: 4200 });
    await screenshot('fig_admin_music_report.png');
    await nav(`${base}/docs/PAP_ENTREGA/_capture_session.php?mode=admin&target=maintenance`, { wait: 4200 });
    await screenshot('fig_admin_maintenance.png');
    await nav(`${base}/docs/PAP_ENTREGA/_capture_session.php?mode=admin&target=admins`, { wait: 4200 });
    await screenshot('fig_admin_admins.png');
    await nav(`${base}/docs/PAP_ENTREGA/_capture_session.php?mode=admin&target=settings`, { wait: 4200 });
    await screenshot('fig_admin_settings.png');
    await page.send('Runtime.evaluate', {
      expression: `(() => {
        localStorage.setItem('g_theme','light');
        localStorage.setItem('g_lang','en');
        document.documentElement.dataset.theme = 'light';
        document.querySelectorAll('[data-l="en"]').forEach((button) => button.classList.add('on'));
        document.querySelectorAll('[data-l="pt"]').forEach((button) => button.classList.remove('on'));
      })();`,
      awaitPromise: true
    });
    await delay(1200);
    await screenshot('fig_admin_settings_light_en.png');

    await page.close();
    cdp.close();
  } finally {
    browser.kill();
  }
}

main().catch((error) => {
  console.error(error);
  process.exit(1);
});
