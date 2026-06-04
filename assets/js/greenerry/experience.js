/* GREENERRY - visual experience layer */
(() => {
  const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');

  function initMediaCloud() {
    const clouds = Array.from(document.querySelectorAll('[data-media-cloud]'));
    if (!clouds.length || reducedMotion.matches) return;

    clouds.forEach((cloud) => {
      if (cloud.dataset.mediaCloudInitialized === '1') return;
      cloud.dataset.mediaCloudInitialized = '1';

      let media = [];
      try {
        media = JSON.parse(cloud.dataset.mediaCloud || '[]');
      } catch (error) {
        media = [];
      }

      const isHeroVisual = cloud.classList.contains('hero-visual-cloud');
      const isSectionCloud = cloud.classList.contains('section-media-cloud');
      const isCatalogCloud = cloud.classList.contains('section-media-cloud--catalog');
      const mediaLimit = isHeroVisual ? 42 : (isCatalogCloud ? 12 : (isSectionCloud ? 8 : 12));

      media = media.filter((item) => item?.src).slice(0, mediaLimit);
      if (media.length < 1) {
        cloud.classList.add('is-empty');
        return;
      }

      const items = media.map((item, index) => {
        const tile = document.createElement('span');
        tile.className = 'media-cloud-tile';
        tile.dataset.mediaType = item.type || 'all';
        const baseSize = isCatalogCloud ? 58 : (isSectionCloud ? 46 : (isHeroVisual ? 104 : 58));
        const rangeSize = isCatalogCloud ? 58 : (isSectionCloud ? 46 : (isHeroVisual ? 72 : 76));
        tile.style.setProperty('--tile-size', `${baseSize + ((index * 17) % rangeSize)}px`);
        tile.style.setProperty('--tile-radius', isHeroVisual ? `${12 + (index % 3) * 6}px` : (index % 4 === 0 ? '50%' : `${14 + (index % 3) * 8}px`));
        tile.style.backgroundImage = `url("${item.src}")`;
        tile.setAttribute('aria-label', item.label || '');
        cloud.appendChild(tile);

        const boardX = [-42, -31, -20, -9, 3, 15, 27, 39, -36, -24, -11, 1, 13, 25, 37, 45, -43, -30, -17, -4, 8, 21, 34, 43, -38, -26, -14, -2, 10, 22, 35, 44, -32, -18, -6, 6, 18, 30, 41, -40, -12, 24];
        const boardY = [-24, -15, -23, -12, -20, -10, -22, -13, -2, 7, -4, 9, -1, 8, -5, 4, 17, 26, 15, 29, 18, 31, 20, 28, 39, 34, 44, 36, 47, 38, 43, 32, 3, 13, 25, 35, 7, 17, 27, 45, -6, 41];
        const focusX = [-18, -3, 13, 28, -24, -8, 8, 24, -16, 0, 16, 32, -26, -10, 6, 22, 38];
        const focusY = [-14, -22, -12, -20, 4, 12, 2, 10, 26, 34, 24, 32, 45, 42, 50, 44, 38];

        return {
          el: tile,
          type: item.type || 'all',
          x: isHeroVisual ? boardX[index % boardX.length] : (isSectionCloud ? (-54 + ((index * 37) % 108)) : (-42 + ((index * 31) % 84))),
          y: isHeroVisual ? boardY[index % boardY.length] : (isSectionCloud ? (-38 + ((index * 53) % 76)) : (-32 + ((index * 43) % 64))),
          focusX: isHeroVisual ? focusX[index % focusX.length] : 0,
          focusY: isHeroVisual ? focusY[index % focusY.length] : 0,
          z: isHeroVisual ? (index % 6) * 22 : (-260 + ((index * 97) % 520)),
          rx: isHeroVisual ? 0 : (-14 + ((index * 11) % 28)),
          ry: isHeroVisual ? 0 : (-22 + ((index * 13) % 44)),
          speed: isHeroVisual ? (0.000052 + (index % 5) * 0.000014) : (0.00012 + (index % 5) * 0.000032),
          phase: index * 0.72
        };
      });

      let raf = 0;
      let activeFilter = 'all';
      const scope = cloud.closest('.home-hero') || cloud.closest('.content-shell') || document;

      function setFilter(filter) {
        activeFilter = filter || 'all';
        let visibleIndex = 0;
        items.forEach((item) => {
          const visible = activeFilter === 'all' || item.type === activeFilter;
          item.el.classList.toggle('is-hidden', !visible);
          item.el.classList.toggle('is-entering', visible);
          if (visible) {
            item.visibleIndex = visibleIndex;
            if (isHeroVisual && activeFilter !== 'all') {
              item.focusX = [-20, -4, 13, 30, -26, -10, 8, 25, -16, 1, 18, 34, -24, -6, 12, 28][visibleIndex % 16];
              item.focusY = [-18, -24, -15, -22, 2, 10, 0, 8, 22, 30, 20, 28, 43, 38, 46, 40][visibleIndex % 16];
              item.el.style.setProperty('--tile-size', `${118 + ((visibleIndex * 19) % 76)}px`);
            } else if (isHeroVisual) {
              item.el.style.setProperty('--tile-size', `${104 + ((Number(item.el.style.getPropertyValue('--tile-seed')) || item.visibleIndex || visibleIndex) * 17 % 72)}px`);
            }
            visibleIndex += 1;
          }
          if (!visible) {
            item.el.style.transform = 'translate3d(-9999px, -9999px, 0)';
          }
        });
        if (isHeroVisual) {
          window.setTimeout(() => items.forEach((item) => item.el.classList.remove('is-entering')), 560);
        }
        scope.querySelectorAll('[data-media-cloud-filter]').forEach((button) => {
          button.classList.toggle('on', button.dataset.mediaCloudFilter === activeFilter);
        });
      }

      scope.querySelectorAll('[data-media-cloud-filter]').forEach((button) => {
        button.addEventListener('click', () => setFilter(button.dataset.mediaCloudFilter));
      });

      function render(time) {
        items.forEach((item, index) => {
          if (activeFilter !== 'all' && item.type !== activeFilter) {
            item.el.style.transform = 'translate3d(-9999px, -9999px, 0)';
            return;
          }
          const drift = Math.sin(time * item.speed + item.phase);
          const float = Math.cos(time * item.speed * 1.4 + item.phase);
          const baseX = isHeroVisual && activeFilter !== 'all' ? item.focusX : item.x;
          const baseY = isHeroVisual && activeFilter !== 'all' ? item.focusY : item.y;
          const x = baseX + drift * (isHeroVisual ? 1.6 : 4);
          const y = baseY + float * (isHeroVisual ? 2.2 : 5);
          const z = item.z + drift * (isHeroVisual ? 4 : 16);
          const rotate = isHeroVisual ? ((index % 7) - 3) * 3.6 + drift * 1.2 : drift * 3;
          item.el.style.transform = `translate3d(${x}vw, ${y}vh, ${z}px) rotateX(${item.rx}deg) rotateY(${item.ry}deg) rotateZ(${rotate}deg)`;
        });
        raf = window.requestAnimationFrame(render);
      }

      setFilter('all');
      cloud.classList.add('is-ready');
      raf = window.requestAnimationFrame(render);
    });
  }

  function initHeroFlip() {
    const heroPanel = document.querySelector('.home-hero-panel');
    if (!heroPanel) return;
    if (heroPanel.dataset.heroFlipInitialized === '1') return;
    heroPanel.dataset.heroFlipInitialized = '1';

    const flipButtons = Array.from(heroPanel.querySelectorAll('[data-hero-flip]'));
    const unflipButtons = Array.from(heroPanel.querySelectorAll('[data-hero-unflip]'));

    function setFlipped(flipped) {
      heroPanel.classList.toggle('is-flipped', flipped);
      flipButtons.forEach((button) => button.setAttribute('aria-expanded', String(flipped)));
    }

    flipButtons.forEach((button) => {
      button.addEventListener('click', () => setFlipped(true));
    });

    unflipButtons.forEach((button) => {
      button.addEventListener('click', () => setFlipped(false));
    });

    document.addEventListener('keydown', (event) => {
      if (event.key === 'Escape' && heroPanel.classList.contains('is-flipped')) {
        setFlipped(false);
      }
    });
  }

  function initExperience() {
    initMediaCloud();
    initHeroFlip();
  }

  function resetHeroFlip() {
    const heroPanel = document.querySelector('.home-hero-panel');
    if (!heroPanel) return;
    heroPanel.classList.remove('is-flipped');
    heroPanel.querySelectorAll('[data-hero-flip]').forEach((button) => {
      button.setAttribute('aria-expanded', 'false');
    });
  }

  document.addEventListener('DOMContentLoaded', initExperience);
  window.GreenerryExperience = { init: initExperience, resetHeroFlip };
  window.addEventListener('greenerry:page-ready', () => {
    initExperience();
    resetHeroFlip();
  });
  window.addEventListener('pageshow', () => {
    initExperience();
    resetHeroFlip();
  });
})();
