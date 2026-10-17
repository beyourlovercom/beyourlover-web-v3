/* BeYourLover Business B2 frontend: strictly local-only preview; no fetch, XHR, email or persistent storage. */
(() => {
  'use strict';
  const root = document.querySelector('[data-byl-business="dev-preview-v1"]');
  if (!root) return;
  const $ = (s) => root.querySelector(s);
  const cards = [...root.querySelectorAll('[data-business-program]')];
  const filter = $('#byl-business-filter');
  const search = $('#byl-business-search');
  const dialog = $('#byl-business-dialog');
  const form = $('#byl-business-form');
  const details = $('#byl-business-dialog-details');
  const submit = form.querySelector('[type=submit]');
  const success = $('#byl-business-success');
  const intro = $('#byl-business-dialog-intro');
  const title = $('#byl-business-dialog-title');
  const programError = $('#byl-business-program-error');
  const matches = {
    store: {id:'retail', label:'Retail Partners', text:'A practical starting point for shop displays, samples and regular replenishment.'},
    online: {id:'dropshipping',label:'Dropshipping',text:'An option for expanding your catalog while limiting held inventory.'},
    network: {id:'wholesale',label:'Wholesale',text:'Ideal for product demonstrations, team sample kits and volume ordering.'},
    brand: {id:'oem',label:'OEM / Private Label',text:'Start with a customization brief, feasibility check, samples and MOQ.'}
  };

  const showDialog = () => {
    if (typeof dialog.showModal === 'function') dialog.showModal();
    else dialog.setAttribute('open','');
  };
  const hideDialog = () => {
    if (typeof dialog.close === 'function') dialog.close();
    else dialog.removeAttribute('open');
  };
  const showApplication = (preselect = '') => {
    form.reset();
    form.hidden = false;
    success.hidden = true;
    details.hidden = true;
    submit.disabled = false;
    programError.hidden = true;
    title.textContent = 'Become a Partner';
    intro.textContent = 'Dev preview only — your information will not be sent or saved.';
    if (preselect) {
      const target = [...form.querySelectorAll('input[name="program[]"]')].find(i => i.value === preselect);
      if (target) target.checked = true;
    }
    showDialog();
  };
  const showProgram = (id) => {
    const card = cards.find(el => el.dataset.businessProgram === id);
    if (!card) return;
    form.hidden = true;
    success.hidden = true;
    details.hidden = false;
    title.textContent = card.dataset.label || 'Business Program';
    intro.textContent = 'Explore eligibility and cooperation options.';
    details.replaceChildren();
    const h3 = document.createElement('h3');
    h3.textContent = card.dataset.label;
    const p = document.createElement('p');
    p.textContent = card.dataset.description;
    const button = document.createElement('button');
    button.type = 'button';
    button.className = 'byl-business__button';
    button.textContent = 'Apply for this program →';
    button.addEventListener('click', () => {
      hideDialog();
      showApplication(id);
    });
    details.append(h3,p,button);
    showDialog();
  };
  const applyFilters = () => {
    const term = search.value.toLocaleLowerCase().trim();
    const category = filter.value;
    let visible = 0;
    cards.forEach(card => {
      const matchesCategory = category === 'all' || card.dataset.category === category;
      const matchesText = !term || (card.dataset.search || '').includes(term);
      card.hidden = !(matchesCategory && matchesText);
      if (!card.hidden) visible += 1;
    });
    $('#byl-business-noresults').hidden = visible !== 0;
  };
  search.addEventListener('input', applyFilters);
  filter.addEventListener('change', applyFilters);
  root.querySelectorAll('[data-business-match]').forEach(button => {
    button.addEventListener('click', () => {
      const match = matches[button.dataset.businessMatch];
      if (!match) return;
      root.querySelectorAll('[data-business-match]').forEach(b => b.setAttribute('aria-pressed', String(b === button)));
      $('#byl-business-recommend-title').textContent = match.label;
      $('#byl-business-recommend-copy').textContent = match.text;
      $('#byl-business-recommend-apply').dataset.preselect = match.id;
    });
  });
  root.querySelectorAll('[data-business-details]').forEach(button => button.addEventListener('click', () => showProgram(button.dataset.businessDetails)));
  root.querySelectorAll('[data-business-apply]').forEach(button => button.addEventListener('click', () => showApplication(button.dataset.preselect || '')));
  root.querySelectorAll('[data-business-close]').forEach(button => button.addEventListener('click', hideDialog));
  dialog.addEventListener('click', e => { if (e.target === dialog) hideDialog(); });
  form.addEventListener('submit', event => {
    event.preventDefault(); // NEVER submit a network request while B3 intake is not authorized.
    const chosen = [...form.querySelectorAll('input[name="program[]"]:checked')];
    programError.hidden = chosen.length > 0;
    if (!chosen.length) { form.querySelector('input[name="program[]"]').focus(); return; }
    if (!form.reportValidity()) return;
    form.hidden = true;
    details.hidden = true;
    title.textContent = 'Application preview complete';
    intro.textContent = 'This is a simulation — no data was transmitted.';
    success.hidden = false;
  });
})();
