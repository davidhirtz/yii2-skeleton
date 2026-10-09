# Admin frontend

The skeleton's TypeScript and Sass. Read with the root `CLAUDE.md`, whose htmx section holds the server side.

## Build

Built per bundle with esbuild (TypeScript + Sass), `cd bundles/yii2-skeleton && npm run dev` / `build`; output in
`resources/assets/dist/` is **committed** (leave the churning CSS source maps out when only scripts changed).
`esbuild.config.js` in the skeleton is shared; a bundle's `esbuild.js` passes entry points and resolves
everything from the skeleton's `node_modules` (`npm install` there once; a bundle needs its own only for what it
imports). JS tests are Vitest in the skeleton's `resources/assets/tests/`, mirroring `src/`, (happy-dom), `npm test` there; no CI runs them. The build runs `tsc --noEmit` first and exits before writing; restart `npm run dev` after touching the
config. Styles are always `.scss`. Anything npm-level is maintained in the skeleton (`npx update-browserslist-db`,
`npm update && npm run build` for a Dependabot alert — a lockfile bump alone ships the vulnerable TinyMCE on;
TinyMCE is the one npm dependency a browser sees). esbuild's `0.x` minor is not an API break. Sass deprecations
are only fully visible through the JS API (`sass.compile(…, {verbose: true})`); verify a Sass migration by
diffing `dist/css/*.css`. jQuery appears only in the Yii debugger (`Modules\Debug\Module`); never elsewhere.

Admin colours: every colour is a hex `--color-*` in the palette at the top of `_theme.scss` (plain CSS variables,
never a Sass map — the IDE navigates them). A module token takes the palette, a state derives from its own module's
base (`--btn-primary-border-color: var(--btn-primary-bg)`), and only the globals (`body-*`, `border-color`, `link-*`)
are shared; never one module from another. `_theme-dark.scss` holds only what differs; `[data-theme]` or, without
one, `prefers-color-scheme`. An SVG in `_images.scss` cannot read a variable: its call takes the hex, updated by hand
when the palette changes. Never merge two colours that tell a rest, hover, active or selected state apart.

## htmx in the scripts

The admin runs **htmx 4**. What shapes everything: nothing inherits an attribute unless the ancestor says
`:inherited` (missing it is silent and swaps the whole page); an empty attribute cancels an inherited one; events
are renamed (`htmx:after:process`, `htmx:config:request`, `htmx:before:swap`), fire on the issuing element, and
carry `detail.ctx`; a container listener must check `event.target`; no history cache (back/forward re-fetch).
- `htmx.onLoad()` goes through `includes/onLoad.ts`; a script an asset bundle introduces after a swap
  initialises the current document itself (`crop.ts`, `data-crop-ready`). Bundle scripts go in `<head>`; the
  `hx-head` extension (imported into `admin.ts` **after** the htmx import) carries them across swaps.
- Only the skeleton's scripts may `import htmx` (a bundle would ship a second copy); a bundle listens for its
  events or does the DOM work itself. `components/*.ts` wire only what their custom element contains
  (`ColorPicker`, `FileUpload`, `RelativeTime`, `tinymce-editor`, `flash-alert`).
- An error response (`>= 400`) never swaps: `admin.ts` cancels `htmx:after:request`, the last event before the swap
  and the history push, and shows the response in a modal instead.
- `htmx.ajax()`'s `values` appends each value once: an array arrives as one comma-joined string, so a list is sent
  with indexed keys (`entry[0]`, `entry[1]`; `sortable.ts`).
- `hx-vars` is gone (`hx-vals`, read at request time); an `hx-vals` `js:` value sees neither element nor event
  (`autocomplete.ts` rewrites `ctx.request.body` on `htmx:config:request`).
- `tooltips.ts` moves `title` into its own element. Every id outside `#wrap` is a **literal** (`Html::getId()`
  counts per request). The navbar contains no `<form>`. A popover removed while open fires no `toggle`
  (`teardown.ts`). `FileUpload.ts` swaps its `data-target` with the same id in the response (a missing target
  swaps the body), looked up per swap: a script holding an element a swap replaces keeps writing into a detached node; `busy.ts` defers the overlay 200 ms. `UPLOAD_ERR_PARTIAL` means "browser aborted" from PHP and
  "chunk landed" from `ChunkedUploadedFile` (`isPartial()`).
- The aside: `data-aside-collapsed` and `data-aside-open` on `<html>` with a host-only `_aside` cookie; the collapsed
  rail is the part `.main` does not cover (negative margin, `--aside-open`, both widths on `:root`); the mobile
  drawer translates `.main` and `.aside`, never `.wrap`; `includes/aside.ts` holds one document-level click
  listener; the pin and the drawer toggle share a navbar slot and extend `AbstractAsideButton`. The colour scheme
  is `user.color_scheme` on `<html>`, no cookie. A nav link is `.nav-link-icon` + `.nav-link-label`.
- Every swap runs through the View Transitions API: `.aside`, `.breadcrumbs`, `.tabs`, `.header-content` and each
  `.header-subtitle-item` (named after the record) carry a `view-transition-name`; each must render at most once
  and none may be painted over. Direction comes from `Web\View::$depth` on `#wrap`; a swap narrower than `#wrap` is
  `none`. Chrome skips transitions in a hidden tab; slow them down to see artefacts.
