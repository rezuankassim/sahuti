# WARP.md

This file provides guidance to WARP (warp.dev) when working with code in this repository.

## Project Overview

This is a Laravel 12 + React 19 application using Inertia.js for seamless SPA-like frontend-backend integration. The stack includes TypeScript, Tailwind CSS 4, shadcn/ui components (radix-ui style), and Laravel Fortify for authentication.

## Architecture

### Backend (Laravel)
- **Actions Pattern**: Business logic for user operations lives in `app/Actions/Fortify/` (e.g., user creation, password updates)
- **Controllers**: Located in `app/Http/Controllers/`, organized by feature (e.g., `Settings/ProfileController`)
- **Routes**: Backend routes are split between `routes/web.php` (main app routes) and `routes/settings.php` (settings-related routes)
- **Auth**: Laravel Fortify handles authentication with two-factor support

### Frontend (React + Inertia)
- **Pages**: React page components in `resources/js/pages/` map to Inertia route responses
- **Layouts**: Reusable layout components in `resources/js/layouts/` (e.g., `app-layout.tsx`, `auth-layout.tsx`)
- **Components**: 
  - Custom components in `resources/js/components/`
  - shadcn/ui components in `resources/js/components/ui/`
- **Hooks**: Custom React hooks in `resources/js/hooks/` (e.g., `use-appearance.tsx`, `use-two-factor-auth.ts`)
- **Type-safe Routing**: Laravel Wayfinder generates type-safe route definitions in `resources/js/routes/index.ts` from Laravel routes
- **Actions**: Frontend form actions in `resources/js/actions/` directories

### Key Integration Points
- **Inertia Bridge**: `app.tsx` sets up Inertia app with page component resolution from `resources/js/pages/`
- **SSR Support**: `ssr.tsx` enables server-side rendering (optional)
- **Route Resolution**: Pages are automatically resolved from Laravel controller responses via Inertia

### Database
- Default: SQLite (`database/database.sqlite`)
- Migrations: `database/migrations/`
- Factories: `database/factories/`
- Seeders: `database/seeders/`

## Development Commands

### Setup
```bash
composer setup
```
Installs dependencies, copies .env, generates key, runs migrations, builds frontend.

### Development Server
```bash
composer dev
```
Runs all services concurrently (Laravel server, queue worker, logs via Pail, Vite dev server).

```bash
composer dev:ssr
```
Development mode with server-side rendering enabled.

### Frontend Development
```bash
npm run dev            # Vite dev server with HMR
npm run build          # Production build
npm run build:ssr      # Build with SSR support
```

### Code Quality
```bash
npm run lint           # ESLint (auto-fix enabled)
npm run format         # Prettier (auto-format)
npm run format:check   # Prettier (check only)
npm run types          # TypeScript type checking
```

```bash
./vendor/bin/pint      # Laravel Pint (PHP code formatting)
```

### Testing
```bash
composer test          # Run all Pest tests
php artisan test       # Alternative way to run tests
```

```bash
php artisan test --filter=ProfileTest    # Run specific test file
```

The project uses Pest PHP for testing with `RefreshDatabase` trait automatically applied to Feature tests.

### Database
```bash
php artisan migrate              # Run migrations
php artisan migrate:fresh        # Drop all tables and re-run migrations
php artisan migrate:fresh --seed # Fresh migration with seeders
php artisan db:seed              # Run seeders
```

### shadcn/ui Components
```bash
npx shadcn@latest add <component-name>
```
Installs shadcn/ui components with the radix-lyra style preset. Components are added to `resources/js/components/ui/`.

### Route Generation (Wayfinder)
```bash
php artisan wayfinder:generate
```
Regenerates TypeScript route definitions in `resources/js/routes/` when Laravel routes change.

### Other Useful Commands
```bash
php artisan pail              # Real-time log viewer
php artisan queue:listen      # Run queue worker
php artisan inertia:start-ssr # Start Inertia SSR server
```

## Code Style & Conventions

### TypeScript/React
- **Imports**: Auto-organized via Prettier plugin
- **Path Aliases**: Use `@/` prefix for imports (e.g., `@/components/ui/button`)
- **Formatting**: 4-space indentation for TS/TSX, single quotes, semicolons
- **Components**: Functional components with TypeScript, prefer named exports for pages
- **React Compiler**: Enabled via babel plugin for automatic optimization

### PHP
- **Laravel Conventions**: Follow Laravel best practices and naming conventions
- **Code Style**: PSR-12 via Laravel Pint
- **Type Safety**: Use strict types where possible

### Styling
- **Tailwind CSS 4**: Utility-first approach with Vite plugin
- **Class Organization**: Use `cn()` helper (clsx + tailwind-merge) for conditional classes
- **Design System**: Leverage shadcn/ui components; customize via `resources/css/app.css` CSS variables

## Important Files & Patterns

### Adding New Pages
1. Create page component in `resources/js/pages/`
2. Add route in `routes/web.php` returning `Inertia::render('page-name')`
3. Run `php artisan wayfinder:generate` to update route types
4. Use type-safe routes in frontend: `import { pageName } from '@/routes'`

### Form Handling
- Use Inertia's form helpers with Wayfinder's route definitions
- Form validation errors are automatically passed as props
- Example: `router.post(profile.update.url(), data)`

### Authentication
- Laravel Fortify handles auth flows
- Pages: `resources/js/pages/auth/` (login, register, etc.)
- Actions: `app/Actions/Fortify/` (CreateNewUser, etc.)
- Two-factor auth is available via Fortify features

### Settings Pages
- Routes in `routes/settings.php`
- Controllers in `app/Http/Controllers/Settings/`
- Pages in `resources/js/pages/settings/`
- Layout in `resources/js/layouts/settings/`

## Environment Configuration

Default environment uses SQLite, database queue, and file-based sessions. For production or different setups, update `.env` accordingly (see `.env.example` for all options).
