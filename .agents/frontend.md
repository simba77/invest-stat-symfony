# Frontend

Vue 3 SPA in `assets/`, built by Vite (`pentatrion/vite-bundle`). Entry: `assets/app.ts`,
routes: `assets/router.ts`.

## Layout

* `assets/pages/` — route pages (single-word names are allowed here).
* `assets/components/` — reusable UI, grouped by feature.
* `assets/composable/use*.ts` — API calls and page state: `axios` requests plus
  `@/utils/use-async` for loading flags; state is held in module-level `ref`s.
* `assets/stores/` — Pinia, only for global app state (e.g. `authStore`).
* `assets/types/` — TypeScript types for API payloads; keep them in sync with backend DTOs.

## Rules

* Strict TypeScript (`tsconfig.json`); type API responses instead of using `any` in new code.
* Import via the `@/` alias (`@/types/...`, `@/utils/...`).
* 2-space indentation for TS/Vue/JSON/YAML.
* ESLint rules in `.eslintrc.js` (`vue3-recommended`, max 4 attributes on a single line,
  one per line when multiline).

## Checks

`make verify-js` runs `npm run lint` and `npm run build`; fix lint errors with `npm run lint-fix`.
