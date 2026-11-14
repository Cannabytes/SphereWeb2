// Decorative only: all game commands and progress still use the shared bridge.
document.addEventListener('DOMContentLoaded', () => {
  const images = [...document.querySelectorAll('.scene-image')];
  const buttons = [...document.querySelectorAll('.scene-button')];
  const toggle = document.getElementById('effects-toggle');
  const canvas = document.getElementById('embers');
  const context = canvas.getContext('2d');
  const reducedMotion = matchMedia('(prefers-reduced-motion: reduce)');
  let paused = reducedMotion.matches;
  let active = 0;
  let timer;
  let frame;
  let previousTime = 0;
  let width = 0;
  let height = 0;
  const particles = Array.from({ length: 44 }, () => ({
    x: Math.random(), y: Math.random(), radius: .5 + Math.random() * 1.2,
    speed: .012 + Math.random() * .018, phase: Math.random() * Math.PI * 2,
  }));

  function select(index) {
    if (!images[index].complete || !images[index].naturalWidth) return;
    active = index;
    images.forEach((image, i) => image.classList.toggle('is-active', i === index));
    buttons.forEach((button, i) => {
      button.classList.toggle('is-active', i === index);
      button.setAttribute('aria-pressed', String(i === index));
    });
  }

  function schedule() {
    clearTimeout(timer);
    if (paused || document.hidden) return;
    timer = setTimeout(() => {
      for (let offset = 1; offset < images.length; offset++) {
        const next = (active + offset) % images.length;
        if (images[next].complete && images[next].naturalWidth) { select(next); break; }
      }
      schedule();
    }, 10000);
  }

  function resize() {
    const box = canvas.getBoundingClientRect();
    width = box.width; height = box.height;
    const ratio = Math.min(devicePixelRatio || 1, 1.5);
    canvas.width = Math.round(width * ratio); canvas.height = Math.round(height * ratio);
    context?.setTransform(ratio, 0, 0, ratio, 0, 0);
  }

  function draw(time) {
    if (paused || document.hidden || !context) return;
    const elapsed = previousTime ? time - previousTime : 34;
    if (elapsed >= 33) {
      previousTime = time;
      const delta = Math.min(elapsed / 1000, .1);
      context.clearRect(0, 0, width, height);
      particles.forEach((particle, index) => {
        particle.y -= particle.speed * delta;
        if (particle.y < -.02) { particle.y = 1.02; particle.x = Math.random(); }
        const x = particle.x * width + Math.sin(time / 4000 + particle.phase) * 18;
        const alpha = .2 + .35 * (1 + Math.sin(time / 1800 + particle.phase)) / 2;
        context.fillStyle = index % 3 !== 0 ? `rgba(244,131,64,${alpha})` : `rgba(216,219,225,${alpha})`;
        context.beginPath(); context.arc(x, particle.y * height, particle.radius, 0, Math.PI * 2); context.fill();
      });
    }
    frame = requestAnimationFrame(draw);
  }

  function sync() {
    cancelAnimationFrame(frame);
    previousTime = 0;
    document.documentElement.dataset.effects = paused || document.hidden ? 'paused' : 'playing';
    toggle.setAttribute('aria-pressed', String(paused));
    toggle.querySelector('.effects-symbol').textContent = paused ? '▶' : 'Ⅱ';
    if (paused) context?.clearRect(0, 0, width, height);
    else if (!document.hidden && context) frame = requestAnimationFrame(draw);
    schedule();
  }

  buttons.forEach((button, index) => button.addEventListener('click', () => { select(index); schedule(); }));
  images.forEach((image, index) => {
    const ready = () => {
      buttons[index].disabled = !image.naturalWidth;
      if (!images[active].naturalWidth) {
        const available = images.findIndex(item => item.complete && item.naturalWidth);
        if (available >= 0) select(available);
      }
    };
    image.addEventListener('load', ready);
    image.addEventListener('error', ready);
    if (image.complete) ready();
  });
  toggle.addEventListener('click', () => { paused = !paused; sync(); });
  reducedMotion.addEventListener('change', () => { paused = reducedMotion.matches; sync(); });
  document.addEventListener('visibilitychange', sync);
  new ResizeObserver(resize).observe(canvas);
  window.addEventListener('pagehide', () => { clearTimeout(timer); cancelAnimationFrame(frame); });
  window.addEventListener('pageshow', sync);
  resize(); sync();
});
