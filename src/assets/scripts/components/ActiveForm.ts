// window.customElements.get('active-form') || window.customElements.define('active-form', class extends HTMLElement {
//     // noinspection JSUnusedGlobalSymbols
//     connectedCallback() {
//         const $form = this.querySelector('form');
//
//         if ($form) {
//             $form.addEventListener('reflow', () => {
//                 $form.querySelectorAll('hr').forEach(($hr: HTMLElement) => {
//                     let visible = false;
//                     let $el = $hr.previousElementSibling;
//
//                     while ($el && $el.tagName !== 'HR') {
//                         if ($el.checkVisibility()) {
//                             visible = true;
//                             break;
//                         }
//
//                         $el = $el.previousElementSibling;
//                     }
//
//                     if (visible) {
//                         $el = $hr.nextElementSibling;
//                         visible = false;
//
//                         while ($el && !$el.classList.contains('form-buttons')) {
//                             if ($el.classList.contains('form-row') && $el.checkVisibility()) {
//                                 visible = true;
//                                 break;
//                             }
//
//                             $el = $el.nextElementSibling;
//                         }
//                     }
//
//                     $hr.style.display = visible ? '' : 'none';
//                 });
//             });
//         }
//     }
// });