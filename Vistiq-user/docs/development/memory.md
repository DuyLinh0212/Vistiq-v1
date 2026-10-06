# Vistiq Project Memory

Compact, cross-session context for coding agents working in this repository. Treat this file as an orientation aid; current source code and the repository's `AGENTS.md` remain authoritative.

## Project snapshot

Last checked: 2026-10-02.

- `composer.json` identifies a Laravel application (`laravel/laravel`) with a Laravel framework constraint of `^13.17`, PHP constraint `^8.3`, and Laravel Tinker `^3.0`. The root `AGENTS.md` describes the runtime as PHP 8.5; check the current environment when exact runtime details matter.
- `package.json` configures ES modules, Vite, Tailwind CSS 4, and the Laravel Vite plugin. Available scripts are `npm run dev` and `npm run build`.
- Vistiq is an English-learning app prototype with home, dictionary, grammar, practice, knowledge, profile, and Admin Studio screens. Most of those screens are demos dispatched through `/demo/{screen}`; inspect each feature before treating a demo flow as production behavior.
- Session-based account flows are available at `/login`, `/register`, `/forgot-password`, and `/reset-password/{token}`. `AuthController` handles registration, login/logout, rate limits, and Laravel's password broker. User passwords are hashed by the `User` model cast; the initial migration already includes reset tokens and sessions.
- The auth pages reuse `resources/views/pages/auth/shell.blade.php` and screen partials. The Vistiq book-and-star logo is `public/images/vistiq-mark.svg`. On login, a small animated owl sits beside the heading; its flashlight rotates 360° in place while the password is visible. The password visibility control in `resources/js/app.js` turns the flashlight scene on (night) and off (day); the lamp beam originates at the owl and sweeps around its fixed position. Styling and reduced-motion behavior live in `resources/css/app.css`.
- As of 2026-10-02, the local `.env` targets the imported Laragon MySQL database `vistiq`, uses database-backed sessions, and uses the `log` mailer. All eight migrations were applied. Reset notifications need a configured SMTP/API mailer for inbox delivery; with the log mailer, inspect Laravel's application log for the reset URL during local development.
- On 2026-10-02, an HTTP smoke flow registered a temporary user, confirmed registration auto-login, logged out, and successfully logged in again. The exact temporary row was removed afterward; `users` was empty at the end, so no ready-to-use account is currently present.

## Repository guidance

- Read the root `AGENTS.md` before making code or documentation changes and follow existing sibling patterns.
- For new PHP files, the project guidance prefers Laravel `php artisan make:` commands. Confirm dependency versions from `composer.json` or `package.json` before relying on version-specific behavior.
- For changes depending on Laravel or package APIs, the project guidance asks agents to search the installed-version documentation with Laravel Boost first.
- Before future edits, check whether `.ai/rules/index.md` and matching rule files exist. `.ai/rules` was absent when this memory was first written, so recheck instead of assuming it remains absent.
- The project uses PHPUnit. Follow the repository guidance for focused tests when changing application behavior.

## Memory maintenance

- Keep this file short and limited to durable facts that help future work; update the checked date when versions or project structure change.
- Mark uncertain or time-sensitive details as needing verification, and preserve dated context when an earlier fact is useful to understand a change.
- Prefer just-in-time retrieval: read this file when starting project work, then inspect the specific source and instructions relevant to the task.
