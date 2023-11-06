import katex from 'katex';

$(() => {
  const appEl = document.getElementById('app');
  if (appEl === null) {
    return;
  }
  const mathEls = appEl.querySelectorAll('[data-tex]');
  mathEls.forEach((mathEl) => {
    const display = mathEl.getAttribute('data-display');
    const isDisplay = display !== undefined && display !== null;
    mathEl.style.display = isDisplay ? '' : 'inline-block';

    katex.render(mathEl.getAttribute('data-tex'), mathEl, {
      output: 'html',
      displayMode: true,
    });
  });
});
