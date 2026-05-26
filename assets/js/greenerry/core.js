/* GREENERRY - core browser helpers */

/* Path helpers */
function _imgPath(file) {
  if (!file) return '';
  if (file.startsWith('http') || file.startsWith('/')) return file;
  if (file.startsWith('../assets/')) return window.SITE_BASE + '/' + file.substring(3);
  return window.SITE_BASE + '/assets/img/' + file;
}

function _audioPath(file) {
  if (!file) return '';
  if (file.startsWith('http') || file.startsWith('/')) return file;
  if (file.startsWith('../assets/')) return window.SITE_BASE + '/' + file.substring(3);
  return window.SITE_BASE + '/assets/audio/' + file;
}

function _norm(track) {
  if (!track) return track;
  if (track.audio && !track.audioSrc) track.audioSrc = track.audio;
  if (track.audioSrc && !track.audio) track.audio = track.audioSrc;
  return track;
}

function _csrfBody(params = {}) {
  const body = new URLSearchParams(params);
  if (window.CSRF_TOKEN) body.set('csrf_token', window.CSRF_TOKEN);
  return body.toString();
}

function initInputValidation(root = document) {
  root.querySelectorAll('[data-name-only]').forEach((input) => {
    if (input.dataset.nameOnlyReady === '1') return;
    input.dataset.nameOnlyReady = '1';
    input.addEventListener('input', () => {
      const clean = input.value.replace(new RegExp('[0-9_+=@#$%^&*()\\[\\]{}<>/\\\\|~`:;"!?]', 'g'), '');
      if (input.value !== clean) input.value = clean;
    });
  });
}

/* Translations */
let T = { pt: {}, en: {} };
let lang = localStorage.getItem('g_lang') || 'pt';
const PLAYER_STATE_KEY = 'g_track';
const THEME_KEY = 'g_theme';

function animateThemeSwitch(button) {
  const rect = button?.getBoundingClientRect();
  const wipe = document.getElementById('theme-wipe');
  if (wipe && rect) {
    wipe.style.setProperty('--theme-x', `${rect.left + rect.width / 2}px`);
    wipe.style.setProperty('--theme-y', `${rect.top + rect.height / 2}px`);
  }

  document.documentElement.classList.add('theme-switching');
  document.querySelectorAll('#theme-toggle, [data-theme-toggle]').forEach((item) => {
    item.classList.add('theme-toggle--animate');
  });

  window.setTimeout(() => {
    document.documentElement.classList.remove('theme-switching');
    document.querySelectorAll('#theme-toggle, [data-theme-toggle]').forEach((item) => {
      item.classList.remove('theme-toggle--animate');
    });
  }, 360);
}

function initThemeToggle() {
  const current = localStorage.getItem(THEME_KEY) || document.documentElement.dataset.theme || 'dark';
  document.documentElement.dataset.theme = current;

  document.querySelectorAll('#theme-toggle, [data-theme-toggle]').forEach((button) => {
    button.setAttribute('aria-pressed', current === 'light' ? 'true' : 'false');
    button.addEventListener('click', () => {
      const next = document.documentElement.dataset.theme === 'light' ? 'dark' : 'light';
      animateThemeSwitch(button);
      document.documentElement.dataset.theme = next;
      localStorage.setItem(THEME_KEY, next);
      document.cookie = `g_theme=${encodeURIComponent(next)}; path=/; max-age=31536000; samesite=lax`;
      document.querySelectorAll('#theme-toggle, [data-theme-toggle]').forEach((item) => {
        item.setAttribute('aria-pressed', next === 'light' ? 'true' : 'false');
      });
    });
  });
}

function _readEmbeddedTranslations() {
  const el = document.getElementById('greenerry-translations');
  if (!el) return null;

  try {
    return JSON.parse(el.textContent || '{}');
  } catch (error) {
    console.warn('Embedded translations failed:', error);
    return null;
  }
}

async function loadTranslations() {
  const embedded = _readEmbeddedTranslations();
  if (embedded?.pt || embedded?.en) {
    T = embedded;
    return;
  }

  try {
    const response = await fetch(window.SITE_BASE + '/assets/js/translations.json');
    T = await response.json();
  } catch (error) {
    console.warn('Translations failed:', error);
  }
}

function setLang(nextLang) {
  if (!Object.keys(T.pt || {}).length && !Object.keys(T.en || {}).length) {
    const embedded = _readEmbeddedTranslations();
    if (embedded?.pt || embedded?.en) T = embedded;
  }

  lang = nextLang;
  localStorage.setItem('g_lang', nextLang);
  document.cookie = `g_lang=${encodeURIComponent(nextLang)}; path=/; max-age=31536000; samesite=lax`;
  document.documentElement.lang = nextLang;

  document.querySelectorAll('[data-t]').forEach((el) => {
    if (!T[nextLang]?.[el.dataset.t]) return;
    const value = T[nextLang][el.dataset.t];
    if (el.tagName === 'OPTION') {
      el.textContent = value;
      return;
    }
    el.textContent = value;
  });

  document.querySelectorAll('[data-tp]').forEach((el) => {
    if (T[nextLang]?.[el.dataset.tp]) el.placeholder = T[nextLang][el.dataset.tp];
  });

  document.querySelectorAll('[data-lang-pt][data-lang-en]').forEach((el) => {
    el.textContent = nextLang === 'en' ? el.dataset.langEn : el.dataset.langPt;
  });

  applyDynamicLabels(nextLang);

  document.querySelectorAll('.lang button').forEach((button) => {
    button.classList.toggle('on', button.dataset.l === nextLang);
  });

  window.dispatchEvent(new CustomEvent('greenerry:langchange', {
    detail: { lang: nextLang }
  }));
}

function applyDynamicLabels(activeLang = lang) {
  const statusMap = {
    aprovado: 'status_approved',
    aprovada: 'status_approved',
    rejeitado: 'status_rejected',
    rejeitada: 'status_rejected',
    inativo: 'status_inactive',
    inativa: 'status_inactive',
    ativo: 'status_active',
    ativa: 'status_active',
    pendente: 'status_pending',
    em_preparacao: 'status_preparing',
    enviado: 'status_sent',
    enviada: 'status_sent',
    entregue: 'status_delivered',
    cancelado: 'status_cancelled',
    cancelada: 'status_cancelled',
    pago: 'payment_paid',
    falhado: 'payment_failed',
    reembolsado: 'payment_refunded'
  };
  const typeMap = {
    Single: 'release_type_single',
    EP: 'release_type_ep',
    Album: 'release_type_album'
  };

  document.querySelectorAll('[data-status-label]').forEach((element) => {
    const key = statusMap[element.dataset.statusLabel || ''];
    if (key && T[activeLang]?.[key]) element.textContent = T[activeLang][key];
  });

  document.querySelectorAll('[data-release-type]').forEach((element) => {
    const key = typeMap[element.dataset.releaseType || ''];
    if (key && T[activeLang]?.[key]) element.textContent = T[activeLang][key];
  });

  const categoryMap = {
    'T-Shirt': 'category_t_shirt',
    Hoodie: 'category_hoodie',
    Vinil: 'category_vinyl',
    CD: 'category_cd',
    Poster: 'category_poster',
    Acessorio: 'category_accessory'
  };

  document.querySelectorAll('[data-product-category]').forEach((element) => {
    const key = categoryMap[element.dataset.productCategory || ''];
    if (key && T[activeLang]?.[key]) element.textContent = T[activeLang][key];
  });

  const countLabels = {
    release: {
      pt: ['lançamento', 'lançamentos'],
      en: ['release', 'releases']
    },
    track: {
      pt: ['faixa', 'faixas'],
      en: ['track', 'tracks']
    },
    product: {
      pt: ['produto', 'produtos'],
      en: ['product', 'products']
    }
  };

  document.querySelectorAll('[data-count-type]').forEach((element) => {
    const type = element.dataset.countType || '';
    const count = parseInt(element.dataset.countValue || '0', 10);
    const pair = countLabels[type]?.[activeLang];
    if (!pair || Number.isNaN(count)) return;
    element.textContent = `${count} ${count === 1 ? pair[0] : pair[1]}`;
  });
}

window.GreenerrySetLang = setLang;
window.addEventListener('greenerry:langchange', () => {
  if (document.getElementById('favs-grid')) renderFavs();
  if (document.getElementById('following-grid')) renderFollowing();
});

/* Motion */
const _prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');
let _motionObserver = null;

function _motionGroups(root = document) {
  return [
    { selector: '.home-hero-copy', type: 'hero-left', step: 0 },
    { selector: '.home-hero-side', type: 'hero-right', step: 0 },
    { selector: '.catalog-hero', type: 'hero-left', step: 0 },
    { selector: '.artist-hero-content > *', type: 'soft', step: 90 },
    { selector: '.auth-card', type: 'zoom', step: 0 },
    { selector: '.hero-card--single', type: 'hero-left', step: 0 },
    { selector: '.product-hero > *', type: 'soft', step: 110 },
    { selector: '.admin-summary-grid > *', type: 'soft', step: 90 },
    { selector: '.stats-grid > *', type: 'soft', step: 70 },
    { selector: '.admin-status-grid > *', type: 'soft', step: 60 },
    { selector: '.admin-chart-col', type: 'soft', step: 45 },
    { selector: '.grid > *', type: 'soft', step: 60 },
    { selector: '.grid-art > *', type: 'soft', step: 60 },
    { selector: '.artist-grid-panels > *', type: 'soft', step: 85 },
    { selector: '.admin-card-list > *', type: 'soft', step: 65 },
    { selector: '.cart-list > *', type: 'soft', step: 65 },
    { selector: '.orders-list > *', type: 'soft', step: 70 },
    { selector: '.surface-card', type: 'soft', step: 0 },
    { selector: '.acard-box', type: 'soft', step: 0 },
    { selector: '.tbl-wrap', type: 'soft', step: 0 }
  ];
}

function _ensureMotionObserver() {
  if (_motionObserver || _prefersReducedMotion.matches) return;

  _motionObserver = new IntersectionObserver((entries) => {
    entries.forEach((entry) => {
      if (!entry.isIntersecting) return;
      entry.target.classList.add('motion-in');
      _motionObserver.unobserve(entry.target);
    });
  }, {
    threshold: 0.14,
    rootMargin: '0px 0px -8% 0px'
  });
}

function _registerMotion(root = document) {
  if (_prefersReducedMotion.matches) return;

  document.body?.classList.add('motion-enabled');
  _ensureMotionObserver();

  _motionGroups(root).forEach((group) => {
    root.querySelectorAll(group.selector).forEach((element, index) => {
      if (element.hasAttribute('data-motion') || element.classList.contains('no-motion')) return;

      element.setAttribute('data-motion', group.type);
      element.style.setProperty('--motion-delay', `${index * group.step}ms`);
      _motionObserver?.observe(element);
    });
  });
}

/* Login check */
function _isLoggedIn() {
  return (document.body?.dataset?.userId || '0') !== '0';
}

function _labelForField(field) {
  const id = field.id;
  if (id) {
    const label = document.querySelector(`label[for="${id}"]`);
    if (label) return label.textContent.trim().replace(/\s+/g, ' ');
  }

  return field.getAttribute('aria-label')
    || field.getAttribute('placeholder')
    || field.name
    || (lang === 'pt' ? 'Este campo' : 'This field');
}

function _validationMessage(field) {
  const fieldName = _labelForField(field);
  const value = (field.value || '').trim();

  if (field.validity.valueMissing) {
    if (field.tagName === 'SELECT') {
      return lang === 'pt'
        ? `Seleciona ${fieldName.toLowerCase()}.`
        : `Select ${fieldName.toLowerCase()}.`;
    }

    if (field.type === 'file') {
      return lang === 'pt'
        ? `Escolhe ${fieldName.toLowerCase()}.`
        : `Choose ${fieldName.toLowerCase()}.`;
    }

    return lang === 'pt'
      ? `${fieldName} é obrigatório.`
      : `${fieldName} is required.`;
  }

  if (field.validity.typeMismatch) {
    if (field.type === 'email') {
      return lang === 'pt'
        ? 'Indica um email válido.'
        : 'Enter a valid email.';
    }

    return lang === 'pt'
      ? `${fieldName} não é válido.`
      : `${fieldName} is not valid.`;
  }

  if (field.validity.tooShort) {
    return lang === 'pt'
      ? `${fieldName} precisa de pelo menos ${field.minLength} caracteres.`
      : `${fieldName} needs at least ${field.minLength} characters.`;
  }

  if (field.validity.tooLong) {
    return lang === 'pt'
      ? `${fieldName} não pode ter mais de ${field.maxLength} caracteres.`
      : `${fieldName} cannot have more than ${field.maxLength} characters.`;
  }

  if (field.validity.rangeUnderflow) {
    if (field.type === 'number' && field.min) {
      return lang === 'pt'
        ? `${fieldName} tem de ser pelo menos ${field.min}.`
        : `${fieldName} must be at least ${field.min}.`;
    }

    return lang === 'pt'
      ? `${fieldName} está abaixo do mínimo.`
      : `${fieldName} is below the minimum.`;
  }

  if (field.validity.rangeOverflow) {
    if (field.type === 'number' && field.max) {
      return lang === 'pt'
        ? `${fieldName} não pode ser maior que ${field.max}.`
        : `${fieldName} cannot be greater than ${field.max}.`;
    }

    return lang === 'pt'
      ? `${fieldName} está acima do máximo.`
      : `${fieldName} is above the maximum.`;
  }

  if (field.validity.patternMismatch) {
    return lang === 'pt'
      ? `${fieldName} não tem o formato certo.`
      : `${fieldName} has the wrong format.`;
  }

  if (field.validity.badInput || (field.type === 'number' && value && Number.isNaN(Number(value)))) {
    return lang === 'pt'
      ? `Indica um valor válido para ${fieldName.toLowerCase()}.`
      : `Enter a valid value for ${fieldName.toLowerCase()}.`;
  }

  return field.validationMessage || (lang === 'pt' ? 'Verifica este campo.' : 'Check this field.');
}

function _bindValidationToasts() {
  document.addEventListener('submit', (event) => {
    const form = event.target;
    if (!(form instanceof HTMLFormElement)) return;

    if (form.checkValidity()) return;

    event.preventDefault();
    const invalidField = form.querySelector(':invalid');
    if (!invalidField) return;

    toast(_validationMessage(invalidField));
    invalidField.focus({ preventScroll: false });
  }, true);

  const pageAlert = document.querySelector('.alert-err, .alert-ok');
  if (pageAlert?.textContent?.trim()) {
    setTimeout(() => toast(pageAlert.textContent.trim()), 120);
  }
}

/* Favourites */
