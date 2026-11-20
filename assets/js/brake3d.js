// 3D brake assembly: exploded until a vehicle is picked, then every part locks into its one right place.
import * as THREE from 'three';
import { RoomEnvironment } from 'three/addons/environments/RoomEnvironment.js';

const TAU = Math.PI * 2;

function annularSector(inner, outer, a0, a1) {
  const s = new THREE.Shape();
  s.moveTo(Math.cos(a0) * inner, Math.sin(a0) * inner);
  s.absarc(0, 0, outer, a0, a1, false);
  s.lineTo(Math.cos(a1) * inner, Math.sin(a1) * inner);
  s.absarc(0, 0, inner, a1, a0, true);
  return s;
}

function extrude(shape, depth, bevel = 0.02, curveSegments = 64) {
  const g = new THREE.ExtrudeGeometry(shape, {
    depth, curveSegments, bevelEnabled: true,
    bevelThickness: bevel, bevelSize: bevel, bevelSegments: 3,
  });
  g.translate(0, 0, -depth / 2);
  return g;
}

export function createBrake(canvas, { reduced = false, onFit = () => {} } = {}) {
  const gsap = window.gsap;
  const renderer = new THREE.WebGLRenderer({ canvas, antialias: true, alpha: true, powerPreference: 'high-performance' });
  renderer.setPixelRatio(Math.min(window.devicePixelRatio || 1, 2));
  renderer.toneMapping = THREE.ACESFilmicToneMapping;
  renderer.toneMappingExposure = 1.05;
  renderer.outputColorSpace = THREE.SRGBColorSpace;

  const scene = new THREE.Scene();
  const pmrem = new THREE.PMREMGenerator(renderer);
  scene.environment = pmrem.fromScene(new RoomEnvironment(renderer), 0.04).texture;

  const camera = new THREE.PerspectiveCamera(30, 1, 0.1, 100);
  camera.position.set(0, 0, 10);

  const key = new THREE.DirectionalLight(0xffffff, 1.6);
  key.position.set(4, 5, 6);
  scene.add(key);
  const rim = new THREE.PointLight(0xff6a1a, 18, 12, 2);
  rim.position.set(-3, -1, -3);
  scene.add(rim);

  // ---------- materials ----------
  const steel = new THREE.MeshStandardMaterial({ color: 0xa3a9b2, metalness: 1, roughness: 0.3 });
  const darkSteel = new THREE.MeshStandardMaterial({ color: 0x50555d, metalness: 1, roughness: 0.42 });
  const signal = new THREE.MeshStandardMaterial({ color: 0xff6a1a, metalness: 0.35, roughness: 0.32 });
  const friction = new THREE.MeshStandardMaterial({ color: 0x26282c, metalness: 0.15, roughness: 0.9 });

  // ---------- parts ----------
  const rig = new THREE.Group();          // whole assembly (layout + scroll + pointer)
  const spin = new THREE.Group();         // things that rotate with the wheel
  rig.add(spin);
  scene.add(rig);

  // disc with drilled holes
  const discShape = new THREE.Shape();
  discShape.absarc(0, 0, 1.45, 0, TAU, false);
  const bore = new THREE.Path();
  bore.absarc(0, 0, 0.64, 0, TAU, true);
  discShape.holes.push(bore);
  for (let i = 0; i < 30; i++) {
    const r = i % 2 ? 1.0 : 1.22;
    const a = (i / 30) * TAU;
    const h = new THREE.Path();
    h.absarc(Math.cos(a) * r, Math.sin(a) * r, 0.045, 0, TAU, true);
    discShape.holes.push(h);
  }
  const disc = new THREE.Mesh(extrude(discShape, 0.16, 0.012, 96), steel);

  // hub / hat with 5 studs
  const hub = new THREE.Group();
  const hat = new THREE.Mesh(new THREE.CylinderGeometry(0.68, 0.72, 0.34, 64), darkSteel);
  hat.rotation.x = Math.PI / 2;
  hub.add(hat);
  const cap = new THREE.Mesh(new THREE.CylinderGeometry(0.26, 0.26, 0.46, 48), steel);
  cap.rotation.x = Math.PI / 2;
  cap.position.z = 0.08;
  hub.add(cap);
  for (let i = 0; i < 5; i++) {
    const a = (i / 5) * TAU + Math.PI / 2;
    const stud = new THREE.Mesh(new THREE.CylinderGeometry(0.05, 0.05, 0.62, 16), steel);
    stud.rotation.x = Math.PI / 2;
    stud.position.set(Math.cos(a) * 0.46, Math.sin(a) * 0.46, 0.26);
    hub.add(stud);
  }

  // caliper (signal orange) and pads, on the right side of the disc
  const caliper = new THREE.Mesh(extrude(annularSector(0.98, 1.64, -0.58, 0.58), 0.64, 0.06), signal);
  const bridge = new THREE.Mesh(extrude(annularSector(1.5, 1.66, -0.4, 0.4), 0.7, 0.02), darkSteel);
  caliper.add(bridge);
  const padGeo = extrude(annularSector(1.02, 1.42, -0.44, 0.44), 0.07, 0.01);
  const padA = new THREE.Mesh(padGeo, friction);
  const padB = new THREE.Mesh(padGeo, friction);

  spin.add(disc, hub);
  rig.add(caliper, padA, padB);

  // glow ring that pulses on "fit"
  const ring = new THREE.Mesh(
    new THREE.TorusGeometry(1.5, 0.012, 8, 180),
    new THREE.MeshBasicMaterial({ color: 0xff6a1a, transparent: true, opacity: 0, toneMapped: false })
  );
  rig.add(ring);

  // ---------- states ----------
  const parts = [
    { obj: hub,     fit: [0, 0, 0.26, 0],   apart: [0, 0, 1.6, 0] },
    { obj: padA,    fit: [0, 0, 0.125, 0],  apart: [0.3, 0.1, 0.9, 0.15] },
    { obj: disc,    fit: [0, 0, 0, 0],      apart: [0, 0, 0, 0] },
    { obj: padB,    fit: [0, 0, -0.125, 0], apart: [0.3, -0.1, -0.9, -0.15] },
    { obj: caliper, fit: [0, 0, 0, 0],      apart: [0.3, 0.2, -1.5, 0.3] },
  ];
  const apply = (p, s) => { p.obj.position.set(s[0], s[1], s[2]); p.obj.rotation.z = s[3]; };
  parts.forEach(p => apply(p, p.apart));

  let assembled = false;
  let spinSpeed = 0.06;

  function tweenTo(stateKey, done) {
    if (!gsap || reduced) {
      parts.forEach(p => apply(p, p[stateKey]));
      done && done();
      return;
    }
    const tl = gsap.timeline({ onComplete: done });
    parts.forEach((p, i) => {
      const s = p[stateKey];
      tl.to(p.obj.position, { x: s[0], y: s[1], z: s[2], duration: 1.05, ease: 'expo.inOut' }, i * 0.07);
      tl.to(p.obj.rotation, { z: s[3], duration: 1.05, ease: 'expo.inOut' }, i * 0.07);
    });
    return tl;
  }

  function pulse() {
    if (!gsap || reduced) return;
    ring.scale.setScalar(1);
    ring.material.opacity = 0.95;
    gsap.to(ring.scale, { x: 1.38, y: 1.38, z: 1.38, duration: 1.1, ease: 'expo.out' });
    gsap.to(ring.material, { opacity: 0, duration: 1.1, ease: 'power2.out' });
    gsap.fromTo(rim, { intensity: 60 }, { intensity: 18, duration: 1.4, ease: 'power2.out' });
    gsap.fromTo(rig.position, { z: -0.25 }, { z: 0, duration: 0.6, ease: 'back.out(3)' });
  }

  function assemble() {
    assembled = true;
    tweenTo('fit', () => { pulse(); onFit(); });
    if (gsap) gsap.to({ v: spinSpeed }, { v: 0.9, duration: 2.2, ease: 'power2.inOut', onUpdate() { spinSpeed = this.targets()[0].v; } });
  }

  function explode() {
    assembled = false;
    tweenTo('apart');
    if (gsap) gsap.to({ v: spinSpeed }, { v: 0.06, duration: 1.2, onUpdate() { spinSpeed = this.targets()[0].v; } });
  }

  // ---------- layout, pointer, scroll ----------
  const base = { x: 0, y: 0, s: 1 };
  function resize() {
    const w = canvas.clientWidth, h = canvas.clientHeight;
    if (!w || !h) return;
    renderer.setSize(w, h, false);
    camera.aspect = w / h;
    camera.updateProjectionMatrix();
    const halfH = Math.tan(THREE.MathUtils.degToRad(camera.fov / 2)) * camera.position.z;
    const halfW = halfH * camera.aspect;
    if (camera.aspect > 1.15) {
      base.x = halfW * 0.4; base.y = 0; base.s = Math.min(0.9, halfH / 3);
    } else {
      base.s = Math.min(1, halfW / 3.3); base.x = -0.45 * base.s; base.y = 0.1;
    }
  }
  resize();
  window.addEventListener('resize', resize);

  const pointer = { x: 0, y: 0, tx: 0, ty: 0 };
  window.addEventListener('pointermove', e => {
    pointer.tx = (e.clientX / window.innerWidth - 0.5) * 2;
    pointer.ty = (e.clientY / window.innerHeight - 0.5) * 2;
  }, { passive: true });

  let scrollP = 0;
  function setScroll(p) { scrollP = Math.max(0, Math.min(1, p)); }

  // ---------- loop (only when visible) ----------
  let visible = true, raf = 0, last = performance.now(), t = 0;
  function frame(now) {
    raf = 0;
    const dt = Math.min(0.05, (now - last) / 1000);
    last = now;
    t += dt;
    pointer.x += (pointer.tx - pointer.x) * 0.05;
    pointer.y += (pointer.ty - pointer.y) * 0.05;

    if (!reduced) spin.rotation.z -= spinSpeed * dt * (assembled ? 1 : 0.5);
    rig.position.x = base.x;
    rig.position.y = base.y + scrollP * 1.2 + (reduced ? 0 : Math.sin(t * 0.8) * 0.05);
    rig.scale.setScalar(base.s * (1 - scrollP * 0.12));
    rig.rotation.y = -0.72 + pointer.x * 0.18 + (reduced ? 0 : Math.sin(t * 0.35) * 0.08) + scrollP * 0.5;
    rig.rotation.x = 0.16 + pointer.y * 0.1 + scrollP * 0.35;

    renderer.render(scene, camera);
    if (visible && !document.hidden) raf = requestAnimationFrame(frame);
  }
  const kick = () => { if (!raf && visible && !document.hidden) { last = performance.now(); raf = requestAnimationFrame(frame); } };
  new IntersectionObserver(([e]) => { visible = e.isIntersecting; kick(); }).observe(canvas);
  document.addEventListener('visibilitychange', kick);
  kick();

  return { assemble, explode, setScroll, get assembled() { return assembled; } };
}
