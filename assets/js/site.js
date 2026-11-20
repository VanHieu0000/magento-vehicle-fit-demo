// VehicleFit showcase - story: parts in pieces -> the right vehicle -> everything locks into place.
const gsap = window.gsap;
const ST = window.ScrollTrigger;
const reduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
if (gsap && ST) gsap.registerPlugin(ST);

/* ---------- sample data (same as sample-fitment.csv) ---------- */
const VEHICLES = [
  { id: 1, make: 'Toyota', model: 'Corolla', years: '2018-2022', engine: '1.8 Hybrid' },
  { id: 2, make: 'Toyota', model: 'Hilux', years: '2016-2023', engine: '2.8 D-4D' },
  { id: 3, make: 'Nissan', model: 'Patrol', years: '2010-2019', engine: '5.6 V8' },
  { id: 4, make: 'Hyundai', model: 'Tucson', years: '2021-2024', engine: '2.0' },
];
const PARTS = [
  { sku: 'BRK-DSC-280', name: 'Front brake disc 280 mm', art: 'disc', price: 189, fits: [1] },
  { sku: 'BRK-PAD-280', name: 'Front brake pad set', art: 'pads', price: 145, fits: [1] },
  { sku: 'FLT-OIL-04', name: 'Oil filter', art: 'oil', price: 39, fits: [1] },
  { sku: 'BRK-DSC-319', name: 'Front brake disc 319 mm', art: 'disc', price: 265, fits: [2] },
  { sku: 'FLT-AIR-HLX', name: 'Engine air filter', art: 'air', price: 72, fits: [2] },
  { sku: 'BRK-DSC-355', name: 'Vented brake disc 355 mm', art: 'disc', price: 410, fits: [3] },
  { sku: 'PLG-IRD-6', name: 'Iridium spark plug', art: 'plug', price: 48, fits: [3] },
  { sku: 'BRK-PAD-305', name: 'Ceramic brake pad set', art: 'pads', price: 160, fits: [4] },
  { sku: 'HUB-ASM-T21', name: 'Wheel hub assembly', art: 'hub', price: 330, fits: [4] },
];
const ART = {
  disc: '<circle cx="50" cy="50" r="40"/><circle cx="50" cy="50" r="17"/><circle cx="50" cy="50" r="24" stroke-dasharray="2 5"/>' +
    Array.from({ length: 12 }, (_, i) => { const a = i / 12 * Math.PI * 2, r = i % 2 ? 30 : 34; return `<circle cx="${(50 + Math.cos(a) * r).toFixed(1)}" cy="${(50 + Math.sin(a) * r).toFixed(1)}" r="1.6"/>`; }).join(''),
  pads: '<path d="M20 30 Q50 12 80 30 L74 44 Q50 30 26 44 Z"/><path d="M20 70 Q50 88 80 70 L74 56 Q50 70 26 56 Z"/><path d="M30 37 Q50 26 70 37" stroke-dasharray="3 3"/><path d="M30 63 Q50 74 70 63" stroke-dasharray="3 3"/>',
  oil: '<rect x="30" y="22" width="40" height="58" rx="6"/><path d="M30 34 H70 M30 68 H70"/><path d="M38 34 V68 M46 34 V68 M54 34 V68 M62 34 V68" stroke-dasharray="2 4"/><rect x="40" y="14" width="20" height="8" rx="2"/>',
  air: '<rect x="16" y="28" width="68" height="44" rx="4"/><path d="M22 34 L28 66 L34 34 L40 66 L46 34 L52 66 L58 34 L64 66 L70 34 L76 66"/>',
  plug: '<path d="M44 10 H56 V20 H44 Z"/><path d="M40 20 H60 L58 46 H42 Z"/><path d="M38 46 H62 V56 H38 Z"/><path d="M42 56 H58 V76 H42 Z"/><path d="M42 60 H58 M42 65 H58 M42 70 H58"/><path d="M50 76 V86 H56"/>',
  hub: '<circle cx="50" cy="50" r="34"/><circle cx="50" cy="50" r="13"/><circle cx="50" cy="50" r="22" stroke-dasharray="1 4"/>' +
    Array.from({ length: 5 }, (_, i) => { const a = i / 5 * Math.PI * 2 - Math.PI / 2; return `<circle cx="${(50 + Math.cos(a) * 24).toFixed(1)}" cy="${(50 + Math.sin(a) * 24).toFixed(1)}" r="3.4"/>`; }).join(''),
};
const vLabel = v => `${v.years} ${v.engine}`;
const vFull = v => `${v.make} ${v.model} ${v.years} ${v.engine}`;

/* ---------- shared vehicle state ---------- */
const listeners = [];
let current = null;
function setVehicle(id, source) {
  current = VEHICLES.find(v => v.id === Number(id)) || null;
  listeners.forEach(fn => fn(current, source));
}

/* ---------- pickers ---------- */
function opt(select, items, placeholder) {
  select.innerHTML = '';
  select.append(new Option(placeholder, ''));
  items.forEach(([value, label]) => select.append(new Option(label, value)));
  select.disabled = items.length === 0;
}
function picker(form) {
  const make = form.querySelector('[name=make]');
  const model = form.querySelector('[name=model]');
  const vehicle = form.querySelector('[name=vehicle]');
  const makes = [...new Set(VEHICLES.map(v => v.make))];
  opt(make, makes.map(m => [m, m]), 'Make');
  opt(model, [], 'Model');
  opt(vehicle, [], 'Year / engine');

  const fillModels = () => opt(model, [...new Set(VEHICLES.filter(v => v.make === make.value).map(v => v.model))].map(m => [m, m]), 'Model');
  const fillVehicles = () => opt(vehicle, VEHICLES.filter(v => v.make === make.value && v.model === model.value).map(v => [v.id, vLabel(v)]), 'Year / engine');
  const mark = () => [make, model, vehicle].forEach(s => s.classList.toggle('is-set', !!s.value));

  make.addEventListener('change', () => { fillModels(); opt(vehicle, [], 'Year / engine'); mark(); if (current) setVehicle(null, form); });
  model.addEventListener('change', () => {
    fillVehicles(); mark();
    // one engine only -> pick it straight away, fewer clicks
    if (vehicle.options.length === 2) { vehicle.selectedIndex = 1; mark(); setVehicle(vehicle.value, form); }
    else if (current) setVehicle(null, form);
  });
  vehicle.addEventListener('change', () => { mark(); setVehicle(vehicle.value || null, form); });

  listeners.push((v, source) => {
    if (source === form) return;
    if (!v) { make.value = ''; opt(model, [], 'Model'); opt(vehicle, [], 'Year / engine'); mark(); return; }
    make.value = v.make; fillModels(); model.value = v.model; fillVehicles(); vehicle.value = String(v.id); mark();
  });
}
document.querySelectorAll('.picker').forEach(picker);
document.getElementById('storeClear').addEventListener('click', () => setVehicle(null, 'clear'));

/* ---------- hero 3D ---------- */
const heroStatus = document.getElementById('heroStatus');
const stamp = document.getElementById('fitStamp');
let brake = null;
import('./brake3d.js')
  .then(({ createBrake }) => {
    brake = createBrake(document.getElementById('brake3d'), {
      reduced,
      onFit() {
        if (!gsap) return;
        gsap.fromTo(stamp, { opacity: 0, scale: 1.8, rotate: -10 }, { opacity: 1, scale: 1, rotate: -4, duration: 0.45, ease: 'back.out(2.5)' });
        gsap.to(stamp, { opacity: 0, duration: 0.5, delay: 1.6 });
      },
    });
    if (current) brake.assemble();
    if (gsap && ST) {
      ST.create({ trigger: '#hero', start: 'top top', end: 'bottom top', onUpdate: s => brake.setScroll(s.progress) });
    }
  })
  .catch(() => document.getElementById('brake3d').remove());

listeners.push(v => {
  const n = v ? PARTS.filter(p => p.fits.includes(v.id)).length : 0;
  heroStatus.innerHTML = v
    ? `<b>${n} parts</b> fit your ${vFull(v)}. URL: <b>?vehicle=${v.id}</b>`
    : 'Pick a vehicle and watch the parts lock in.';
  if (!brake) return;
  if (v) { if (brake.assembled) { brake.explode(); setTimeout(() => brake.assemble(), 450); } else brake.assemble(); }
  else brake.explode();
});

/* ---------- store demo ---------- */
const grid = document.getElementById('storeGrid');
grid.innerHTML = PARTS.map(p => `
  <article class="part" data-sku="${p.sku}">
    <span class="part__stamp">FITS</span>
    <div class="part__art"><svg viewBox="0 0 100 100" aria-hidden="true">${ART[p.art]}</svg></div>
    <span class="part__sku">${p.sku}</span>
    <h3 class="part__name">${p.name}</h3>
    <span class="part__price">AED ${p.price}</span>
  </article>`).join('');
const cards = [...grid.children];
const url = document.getElementById('storeUrl');
const cacheEl = document.getElementById('storeCache');
const countEl = document.getElementById('storeCount');
const seen = new Set();

listeners.push(v => {
  const fitting = [];
  cards.forEach((card, i) => {
    const fits = v ? PARTS[i].fits.includes(v.id) : false;
    card.classList.toggle('is-fit', fits);
    card.classList.toggle('is-out', !!v && !fits);
    if (fits) fitting.push(card);
  });
  if (gsap && !reduced && fitting.length) {
    gsap.fromTo(fitting, { y: -14 }, { y: 0, duration: 0.6, ease: 'back.out(3)', stagger: 0.07 });
  }
  countEl.innerHTML = `<b>${v ? fitting.length : PARTS.length}</b>${v ? 'parts fit' : 'parts'}`;
  url.innerHTML = v ? `yourstore.com/brakes-filters.html<b>?vehicle=${v.id}</b>` : 'yourstore.com/brakes-filters.html';
  const key = v ? v.id : 0;
  const hit = seen.has(key);
  seen.add(key);
  cacheEl.classList.toggle('is-hit', hit);
  cacheEl.innerHTML = `X-Magento-Cache: <b>${hit ? 'HIT' : 'MISS'}</b>`;
});

/* ---------- admin mock ---------- */
const adminRows = document.getElementById('adminRows');
function fillAdmin() {
  adminRows.innerHTML = VEHICLES.map(v => {
    const [from, to] = v.years.split('-');
    return `<tr class="is-new"><td>${v.id}</td><td>${v.make}</td><td>${v.model}</td><td>${from}</td><td>${to}</td><td>${v.engine}</td></tr>`;
  }).join('');
  [...adminRows.children].forEach((tr, i) => { tr.style.animationDelay = `${0.25 + i * 0.12}s`; });
}
function typeText(el) {
  const text = el.dataset.text;
  if (reduced) { el.textContent = text; el.classList.add('is-done'); return; }
  let i = 0;
  const step = () => { el.textContent = text.slice(0, ++i); if (i < text.length) setTimeout(step, 18); else el.classList.add('is-done'); };
  step();
}
function once(el, fn, threshold = 0.35) {
  const io = new IntersectionObserver(([e]) => { if (e.isIntersecting) { fn(); io.disconnect(); } }, { threshold });
  io.observe(el);
}
once(document.querySelector('.admin__wrap'), () => { fillAdmin(); typeText(document.querySelector('.typed')); }, 0.25);
once(document.getElementById('term'), () => document.getElementById('term').classList.add('is-play'), 0.5);

/* ---------- code viewer ---------- */
const tabs = [...document.querySelectorAll('#codeTabs button')];
const codeView = document.getElementById('codeView');
const codePath = document.getElementById('codePath');
const codeRaw = document.getElementById('codeRaw');
const cache = new Map();
async function show(btn) {
  tabs.forEach(t => t.setAttribute('aria-selected', String(t === btn)));
  const file = btn.dataset.file;
  codePath.textContent = file.replace('module/app/code/Hieu/VehicleFit/', '');
  codeRaw.href = file;
  try {
    if (!cache.has(file)) {
      const res = await fetch(file);
      if (!res.ok) throw new Error(res.status);
      cache.set(file, await res.text());
    }
    codeView.textContent = cache.get(file);
  } catch (e) {
    codeView.textContent = `Could not load ${file} here. Open it from the repo.`;
  }
  codeView.className = `language-${btn.dataset.lang}`;
  if (window.Prism) window.Prism.highlightElement(codeView);
}
tabs.forEach(t => t.addEventListener('click', () => show(t)));
once(document.getElementById('code'), () => show(tabs[0]), 0.05);

/* ---------- film: play only when on screen ---------- */
const video = document.querySelector('.film__video');
new IntersectionObserver(([e]) => {
  if (e.isIntersecting && !reduced) { video.preload = 'auto'; video.play().catch(() => {}); } else video.pause();
}, { threshold: 0.15 }).observe(video);

/* ---------- scroll story (GSAP) ---------- */
function splitWords(el) {
  const words = el.textContent.trim().split(/\s+/);
  el.textContent = '';
  words.forEach((w, i) => {
    const outer = document.createElement('span');
    outer.className = 'w';
    const inner = document.createElement('span');
    inner.textContent = w;
    outer.append(inner);
    el.append(outer, i < words.length - 1 ? ' ' : '');
  });
  return el.querySelectorAll('.w > span');
}

if (gsap && ST && !reduced) {
  // hero entrance
  gsap.from('.hero__title .line > span', { yPercent: 110, duration: 1.1, ease: 'expo.out', stagger: 0.1, delay: 0.15 });
  gsap.from('.hero .eyebrow, .hero__sub, .hero .picker, .hero__status', { y: 24, opacity: 0, duration: 0.9, ease: 'expo.out', stagger: 0.08, delay: 0.45 });

  // headings: each word snaps up into its slot
  document.querySelectorAll('.split').forEach(el => {
    gsap.from(splitWords(el), {
      yPercent: 105, duration: 0.9, ease: 'expo.out', stagger: 0.04,
      scrollTrigger: { trigger: el, start: 'top 85%' },
    });
  });

  // photos open like a shutter
  document.querySelectorAll('.reveal-clip').forEach(el => {
    gsap.fromTo(el, { clipPath: 'inset(0 0 100% 0)' }, {
      clipPath: 'inset(0 0 0% 0)', duration: 1.3, ease: 'expo.inOut',
      scrollTrigger: { trigger: el, start: 'top 80%' },
    });
  });
  gsap.from('.problem__inset', { y: 80, rotate: 6, opacity: 0, duration: 1.1, ease: 'back.out(1.6)', scrollTrigger: { trigger: '.problem__inset', start: 'top 95%' } });
  gsap.from('.costs li', { x: -30, opacity: 0, duration: 0.7, ease: 'expo.out', stagger: 0.1, scrollTrigger: { trigger: '.costs', start: 'top 85%' } });

  // demo store slides in like a drawer
  gsap.from('.store', { y: 80, opacity: 0, duration: 1.1, ease: 'expo.out', scrollTrigger: { trigger: '.store', start: 'top 85%' } });

  // flow: the line connects the four steps as you scroll
  const steps = [...document.querySelectorAll('.step')];
  ST.create({
    trigger: '.flow__rail', start: 'top 75%', end: 'bottom 55%', scrub: 0.6,
    onUpdate: s => {
      document.querySelector('.flow__line').style.setProperty('--p', s.progress.toFixed(3));
      steps.forEach((st, i) => st.classList.toggle('is-on', s.progress >= i / (steps.length - 1) - 0.02));
    },
  });

  // admin mock
  gsap.from('.mock', { y: 60, opacity: 0, duration: 1, ease: 'expo.out', stagger: 0.15, scrollTrigger: { trigger: '.admin__wrap', start: 'top 85%' } });

  // cache section parallax + cards
  gsap.to('.cache__bg img', { yPercent: -12, ease: 'none', scrollTrigger: { trigger: '.cache', start: 'top bottom', end: 'bottom top', scrub: true } });
  gsap.from('.card', { y: 50, opacity: 0, duration: 0.9, ease: 'expo.out', stagger: 0.12, scrollTrigger: { trigger: '.cache__grid', start: 'top 85%' } });

  // AI fixes: old idea struck out, new one locks in
  document.querySelectorAll('.fixes li').forEach(li => {
    const tl = gsap.timeline({ scrollTrigger: { trigger: li, start: 'top 88%' } });
    tl.from(li, { opacity: 0, x: -40, duration: 0.6, ease: 'expo.out' })
      .from(li.querySelector('b'), { x: 30, opacity: 0, duration: 0.5, ease: 'back.out(3)' }, '-=0.2')
      .from(li.querySelector('em'), { opacity: 0, duration: 0.4 }, '-=0.2');
  });

  // film band: camera pulls back, words land one by one
  gsap.to('.film__video', { scale: 1, ease: 'none', scrollTrigger: { trigger: '.film', start: 'top bottom', end: 'bottom top', scrub: true } });
  gsap.from('.film__words span', { yPercent: 60, opacity: 0, duration: 0.9, ease: 'expo.out', stagger: 0.25, scrollTrigger: { trigger: '.film', start: 'top 55%' } });

  // handover + end
  gsap.from('.checks li', { x: 30, opacity: 0, duration: 0.6, ease: 'expo.out', stagger: 0.08, scrollTrigger: { trigger: '.checks', start: 'top 85%' } });
  gsap.fromTo('.end__bg img', { scale: 1.15 }, { scale: 1, ease: 'none', scrollTrigger: { trigger: '.end', start: 'top bottom', end: 'bottom bottom', scrub: true } });
}

if (!gsap || !ST || reduced) {
  // no scroll story: show the finished state
  document.querySelector(".flow__line").style.setProperty("--p", "1");
  document.querySelectorAll(".step").forEach(st => st.classList.add("is-on"));
}

// first paint of store + status
setVehicle(null, 'init');
