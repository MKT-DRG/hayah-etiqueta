function checkTextOverflow() {
 const overflow = [...document.querySelectorAll('.label-text')].some(el => el.scrollHeight > el.clientHeight + 1);
 document.querySelector('#print-warning').hidden = !overflow;
 return overflow;
}
document.fonts.ready.then(checkTextOverflow);
document.querySelector('#print').addEventListener('click', () => {
 if (checkTextOverflow() && !confirm('Alguns textos não cabem na etiqueta. Deseja imprimir mesmo assim?')) return;
 window.print();
});
