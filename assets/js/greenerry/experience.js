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
      const mediaLimit = isHeroVisual ? 14 : (isCatalogCloud ? 12 : (isSectionCloud ? 8 : 12));

      media = media.filter((item) => item?.src).slice(0, mediaLimit);
      if (media.length < 1) {
        cloud.classList.add('is-empty');
        return;
      }

      const items = media.map((item, index) => {
        const tile = document.createElement('span');
        tile.className = 'media-cloud-tile';
        tile.dataset.mediaType = item.type || 'all';
        const baseSize = isCatalogCloud ? 58 : (isSectionCloud ? 46 : (isHeroVisual ? 76 : 58));
        const rangeSize = isCatalogCloud ? 58 : (isSectionCloud ? 46 : (isHeroVisual ? 84 : 76));
        tile.style.setProperty('--tile-size', `${baseSize + ((index * 17) % rangeSize)}px`);
        tile.style.setProperty('--tile-radius', index % 4 === 0 ? '50%' : `${14 + (index % 3) * 8}px`);
        tile.style.backgroundImage = `url("${item.src}")`;
        tile.setAttribute('aria-label', item.label || '');
        cloud.appendChild(tile);

        return {
          el: tile,
          type: item.type || 'all',
          x: isSectionCloud ? (-54 + ((index * 37) % 108)) : (-42 + ((index * 31) % 84)),
          y: isSectionCloud ? (-38 + ((index * 53) % 76)) : (-32 + ((index * 43) % 64)),
          z: -260 + ((index * 97) % 520),
          rx: -14 + ((index * 11) % 28),
          ry: -22 + ((index * 13) % 44),
          speed: 0.00012 + (index % 5) * 0.000032,
          phase: index * 0.72
        };
      });

      let raf = 0;
      let activeFilter = 'all';
      const scope = cloud.closest('.home-hero') || cloud.closest('.content-shell') || document;

      function setFilter(filter) {
        activeFilter = filter || 'all';
        items.forEach((item) => {
          const visible = activeFilter === 'all' || item.type === activeFilter;
          item.el.classList.toggle('is-hidden', !visible);
        });
        scope.querySelectorAll('[data-media-cloud-filter]').forEach((button) => {
          button.classList.toggle('on', button.dataset.mediaCloudFilter === activeFilter);
        });
      }

      scope.querySelectorAll('[data-media-cloud-filter]').forEach((button) => {
        button.addEventListener('click', () => setFilter(button.dataset.mediaCloudFilter));
      });

      function render(time) {
        items.forEach((item, index) => {
          if (activeFilter !== 'all' && item.type !== activeFilter) return;
          const drift = Math.sin(time * item.speed + item.phase);
          const float = Math.cos(time * item.speed * 1.4 + item.phase);
          const x = item.x + drift * 4;
          const y = item.y + float * 5;
          const z = item.z + drift * 16;
          item.el.style.transform = `translate3d(${x}vw, ${y}vh, ${z}px) rotateX(${item.rx}deg) rotateY(${item.ry}deg) rotateZ(${drift * 3}deg)`;
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
