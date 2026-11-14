// Presentation only. Launcher commands, account handling and state rendering stay in shared/interface.js.
document.addEventListener('DOMContentLoaded', () => {
  const details = document.getElementById('details-panel');
  const toggle = document.getElementById('details-panel-toggle');
  const setDetails = (open) => { details.hidden = !open; toggle.setAttribute('aria-expanded', String(open)); };
  toggle.addEventListener('click', () => setDetails(details.hidden));
  document.getElementById('details-close').addEventListener('click', () => { setDetails(false); toggle.focus(); });
  let lastStatus = '';
  window.Launcher?.onStateChange((state) => {
    if (!state) return;
    const etaRow = document.getElementById('download-eta-stat');
    etaRow.hidden = state.status !== 'downloading';
    const remaining = Math.max(0, (state.progress?.bytesTotal || 0) - (state.progress?.bytesCompleted || 0));
    const speed = state.timing?.currentTotalSpeed || 0;
    const seconds = Math.ceil(remaining / speed);
    document.getElementById('download-eta').textContent = speed > 0 && state.progress?.bytesTotal > 0 && Number.isFinite(seconds)
      ? `≈ ${Math.floor(seconds / 3600).toString().padStart(2, '0')}:${Math.floor(seconds / 60 % 60).toString().padStart(2, '0')}:${(seconds % 60).toString().padStart(2, '0')}`
      : (window.i18n?.t('estimatingTime') || 'Расчёт…');
    const working = ['checking', 'downloading', 'extracting'].includes(state.status);
    document.getElementById('full-update-btn').hidden = working;
    if (state.status === 'error' && lastStatus !== 'error') setDetails(true);
    lastStatus = state.status;
    document.querySelector('.launcher-shell').dataset.state = state.status;
  });
  // The shared renderer creates clickable news as divs. Add keyboard semantics without replacing its handlers.
  const news = document.querySelector('.news-container');
  const makeNewsAccessible = () => news.querySelectorAll('.news-item').forEach((item) => {
    if (item.style.cursor !== 'pointer' || item.hasAttribute('tabindex')) return;
    item.tabIndex = 0; item.setAttribute('role', 'link');
    item.addEventListener('keydown', (event) => { if (event.key === 'Enter' || event.key === ' ') { event.preventDefault(); item.click(); } });
  });
  new MutationObserver(makeNewsAccessible).observe(news, { childList:true });
  makeNewsAccessible();
  const shell = document.querySelector('.launcher-shell');
  document.querySelectorAll('.modal').forEach((modal) => {
    const content = modal.querySelector('.modal-content');
    content.setAttribute('role', 'dialog'); content.setAttribute('aria-modal', 'true'); content.tabIndex = -1;
    const heading = content.querySelector('h2'); heading.id = `${modal.id}-title`; content.setAttribute('aria-labelledby', heading.id);
    let previousFocus;
    let wasOpen = false;
    new MutationObserver(() => {
      const open = modal.style.display === 'flex';
      if (open === wasOpen) return;
      wasOpen = open;
      if (open) { previousFocus = document.activeElement; shell.inert = true; content.querySelector('button,input,select')?.focus(); }
      else { shell.inert = [...document.querySelectorAll('.modal')].some((item) => item.style.display === 'flex'); previousFocus?.focus(); }
    }).observe(modal, { attributes:true, attributeFilter:['style'] });
    modal.querySelector('.modal-overlay').addEventListener('click', () => modal.querySelector('.close-modal-btn').click());
    content.addEventListener('keydown', (event) => {
      if (event.key !== 'Tab') return;
      const items = [...content.querySelectorAll('button:not(:disabled),input:not(:disabled),select:not(:disabled),[tabindex="0"]')].filter((item) => item.getClientRects().length);
      if (!items.length) { event.preventDefault(); content.focus(); return; }
      if (event.shiftKey && document.activeElement === items[0]) { event.preventDefault(); items.at(-1).focus(); }
      else if (!event.shiftKey && document.activeElement === items.at(-1)) { event.preventDefault(); items[0].focus(); }
    });
  });
});
