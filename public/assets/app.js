const checkboxes = [...document.querySelectorAll('input[name="ids[]"]')];
const selectAll = document.querySelector('#select-all');
const copies = document.querySelector('#copies');
function updateSelection() {
 const count = checkboxes.filter(el => el.checked).length;
 const total = count * Number(copies?.value || 1);
 if (selectAll) { selectAll.checked = count > 0 && count === checkboxes.length; selectAll.indeterminate = count > 0 && count < checkboxes.length; }
 const counter = document.querySelector('#selected-count');
 if (counter) counter.textContent = count;
 const summary = document.querySelector('#label-total');
 if (summary) summary.textContent = `· ${total} etiquetas${total > 500 ? ' (máximo 500)' : ''}`;
 const generate = document.querySelector('#generate');
 if (generate) generate.disabled = !count || total > 500;
}
selectAll?.addEventListener('change', () => { checkboxes.forEach(el => { el.checked = selectAll.checked; }); updateSelection(); });
checkboxes.forEach(el => el.addEventListener('change', updateSelection));
copies?.addEventListener('input', updateSelection);
document.querySelectorAll('[data-confirm]').forEach(form => form.addEventListener('submit', event => { if (!confirm(form.dataset.confirm)) event.preventDefault(); }));
updateSelection();

