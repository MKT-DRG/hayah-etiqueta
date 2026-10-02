(() => {
 const form = document.querySelector('#design-form');
 if (!form) return;
 const preview = document.querySelector('#design-preview');
 const summary = document.querySelector('#design-summary');
 const warning = document.querySelector('#design-warning');
 const fonts = {arial:'Arial, sans-serif',verdana:'Verdana, sans-serif',georgia:'Georgia, serif',courier:'"Courier New", monospace'};
 const sample = {'{nome}':'Mariana Costa','{empresa}':'Estúdio Horizonte','{telefone}':'+5511999999999','{email}':'mariana@exemplo.com'};
 let dirty = false;
 function update() {
  const custom = form.elements.paper.value === 'custom';
  const customPaper = document.querySelector('#custom-paper');
  customPaper.hidden = !custom;
  customPaper.disabled = !custom;
  const data = Object.fromEntries(new FormData(form));
  const num = key => Number(data[key]);
  let [pw, ph] = {a4:[210,297],a5:[148,210],letter:[215.9,279.4],custom:[num('paper_width'),num('paper_height')]}[data.paper];
  if (data.orientation === 'landscape') [pw,ph] = [ph,pw];
  const format = value => value.toLocaleString('pt-BR', {maximumFractionDigits:1});
  document.querySelector('#paper-dimensions').textContent = Number.isFinite(pw) && Number.isFinite(ph) ? `${custom ? 'Folha personalizada' : 'Medidas automáticas'}: ${format(pw)} × ${format(ph)} mm · ${data.orientation === 'landscape' ? 'Paisagem' : 'Retrato'}` : 'Preencha as medidas da folha personalizada.';
  const columns = Math.floor((pw-2*num('margin_x')+num('gap_x')+.00001)/(num('width')+num('gap_x'));
  const rows = Math.floor((ph-2*num('margin_y')+num('gap_y')+.00001)/(num('height')+num('gap_y'));
  const valid = form.checkValidity() && columns > 0 && rows > 0;
  document.querySelector('#save-design').disabled = !valid;
  summary.textContent = valid ? `${columns*rows} etiquetas por folha · ${columns} ${columns === 1 ? 'coluna' : 'colunas'} × ${rows} ${rows === 1 ? 'linha' : 'linhas'}.` : 'Confira os campos. A etiqueta precisa caber na folha com as margens escolhidas.';
  preview.style.setProperty('--label-width', `${num('width')}mm`);
  preview.style.setProperty('--label-height', `${num('height')}mm`);
  preview.style.setProperty('--qr-size', `${Math.round(Math.min(num('height')-6,num('width')*.4)*10)/10}mm`);
  preview.style.setProperty('--label-font', fonts[data.font]);
  for (const field of ['name','company','caption']) {
   preview.style.setProperty(`--${field}-size`, `${num(`${field}_size`)}pt`);
   preview.querySelector(`[data-label-text="${field}_text"]`).textContent = data[`${field}_text`].replace(/\{[^{}]*\}/g, token => sample[token] ?? token);
  }
  requestAnimationFrame(() => {
   const text = preview.querySelector('.label-text');
   warning.textContent = text.scrollHeight > text.clientHeight + 1 ? 'O texto está ultrapassando a etiqueta. Diminua a fonte, encurte os textos ou aumente a etiqueta.' : '';
  });
  document.querySelector('#design-dirty').textContent = dirty ? 'Alterações na prévia ainda não salvas.' : '';
 }
 form.addEventListener('input', () => {dirty = true; update();});
 form.addEventListener('change', () => {dirty = true; update();});
 form.addEventListener('submit', () => { dirty = false; });
 document.querySelector('#reset-design-form')?.addEventListener('submit', event => {
  if (!confirm('Restaurar e salvar todos os valores padrão da etiqueta? As personalizações atuais serão substituídas.')) {
   event.preventDefault();
   return;
  }
  dirty = false;
 });
 window.addEventListener('beforeunload', event => {if (dirty) {event.preventDefault();event.returnValue='';}});
 update();
})();
