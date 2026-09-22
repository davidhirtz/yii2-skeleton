// @ts-ignore
import Sortable, {AutoScroll} from 'sortablejs/modular/sortable.core.esm.js';

// Mounting a plugin twice throws, and every entry point shares this chunk: a page loading two of them — the
// custom attribute form and then a grid — would otherwise mount it once per script.
Sortable.mount(new AutoScroll());

export default Sortable;
