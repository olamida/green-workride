# SPRINT 1 PROMPT: Foundation — Branding Upgrade + Admin Grouping + Role Switcher + Map Libs
## AI Coder Prompt — Copy-Paste Ready

> **Sprint:** 1 of 4 — Foundation
> **Goal:** Single design system everywhere, admin sidebar grouped into packages and mobile-friendly, role switcher, install map libs
> **Base:** WORKRIDE-APP-GUIDE.md v1.0 + WORKRIDE-DEV-GUIDE.md + input_section.txt §4,5,6
> **DoD:** Rider + Admin use same tokens, admin usable on phone, tests green, pint clean

### ROLE
You are Senior Laravel + Filament + Tailwind Engineer. You have shipped Linear.app-level design systems. You know DEV-GUIDE non-negotiables: money decimal(15,2), NIN hash only, optimistic locking, idempotency.

### TASKS:

#### 1.1 Design System Tokens — `resources/css/design-system.css` (CREATE/UPDATE)
```css
@theme {
  --color-primary: #0F5132;
  --color-primary-light: #2E7D32;
  --color-accent: #FFC300;
  --color-surface: #F6F9F6;
  --color-ink: #0F172A;
  --color-danger: #DC2626;
  --font-heading: 'Sora', sans-serif;
  --font-body: 'Inter', sans-serif;
  --font-mono: 'JetBrains Mono', monospace;
  --radius-card: 16px;
  --radius-pill: 9999px;
  --shadow-card: 0 1px 3px rgba(0,0,0,0.08);
  --shadow-live: 0 0 0 4px rgba(46,125,50,0.15);
  --spacing-8: 8px;
}
```
- Ensure `resources/css/app.css` imports this file as first import
- Replace all hardcoded `bg-green-600` with `bg-[var(--color-primary)]`, `text-green-700` with `text-[var(--color-primary-light)]` — use regex replace
- Update `tailwind.config.js` to extend colors from CSS variables
- Update `public/manifest.json`: `theme_color: #0F5132`, `background_color: #F6F9F6`, add icons 192, 512 maskable
- Create `resources/views/components/ui/card.blade.php`, `button.blade.php`, `badge.blade.php` using tokens — glassmorphism: `backdrop-blur-xl bg-white/80 border border-white/50 shadow-sm rounded-[var(--radius-card)]`

#### 1.2 Rider Layout Mobile-First — `resources/views/layouts/app.blade.php` + `resources/views/layouts/public.blade.php`
- Rider container: `max-w-[480px] mx-auto` on mobile, centered, like WhatsApp — on desktop `max-w-[1280px]`
- Bottom navigation for rider (5 items): Home, Search, My Rides, Wallet, More — `resources/views/components/navigation/bottom-nav.blade.php`
- Touch targets min 44px, buttons min 48px height
- Ensure guest-safe: `layouts/public` does NOT call `auth()->user()` unconditionally (per DEV-GUIDE §3.2)

#### 1.3 Admin Sidebar Grouping — `config/admin_nav.php` (CREATE) + `app/Providers/Filament/AdminPanelProvider.php` or `resources/views/layouts/admin.blade.php`

Create config:
```php
return [
 'groups' => [
   'operations' => ['label'=>'Operations','icon'=>'heroicon-o-truck','items'=>['trips','bookings','live-map','demand','fleet','verifications','sos']],
   'people' => ['label'=>'People','icon'=>'heroicon-o-users','items'=>['users','drivers','employers','workplaces','junctions']],
   'intelligence' => ['label'=>'Intelligence','icon'=>'heroicon-o-map','items'=>['road-map','road-events','gtfs','impact','reports','forecasts']],
   'business' => ['label'=>'Business','icon'=>'heroicon-o-wallet','items'=>['wallets','transactions','subsidies','business','stakeholders','payouts','time-bank']],
   'system' => ['label'=>'System','icon'=>'heroicon-o-cog-6-tooth','items'=>['activity-logs','api-costs','imports','flags','settings']],
 ]
];
```

- Implement as collapsible NavigationGroups — if Filament: use `NavigationGroup::make()->collapsible()` — if custom Blade: create Alpine accordion `x-data="{open: true}"`
- Badge counts: e.g., Verifications pending count via `Verification::whereStatus('pending')->count()`
- Topnav: Reduce to 4 links: Dashboard | Operations | Finance | Intelligence + user menu + Role Switcher
- Mobile behavior: Sidebar becomes hamburger slide-over (Tailwind `translate-x` drawer) + bottom nav for 4 most used: Live Trips, Demand, Fleet, Verifications, More
- Tables responsive: Use Filament Split/Stack or Tailwind `overflow-x-auto` + stack on <640px

#### 1.4 Role Switcher — Admin can View as Passenger/Driver
- Create `app/Services/RoleSwitcherService.php`:
```php
public function switch(User $admin, string $targetRole): void // targetRole in ['passenger','driver','both','admin']
public function getEffectiveRole(User $user): UserRole // checks session('view_as_role') if admin
```
- Middleware `App\Http\Middleware\EffectiveRoleMiddleware` — set in session, override `auth()->user()->role` effective
- UI in admin topnav dropdown: "View as: Passenger / Driver / Admin" — stores in session `view_as_role`
- Ensure DEV-GUIDE §2.2 middleware gates use effective role, but actual admin privileges remain for admin routes

#### 1.5 Install Map Libs
```bash
npm install leaflet-polylinedecorator leaflet-arrowheads maplibre-gl --save
```
- Add to `resources/js/app.js`: import polylineDecorator
- Create `resources/js/map/common.js` with helper `createLabeledTileLayer()` returning CartoDB Positron with labels

#### 1.6 Branding Consistency
- Apply design-system.css tokens to EVERY admin page — verify no hardcoded colors remain
- Update logo component `resources/views/components/brand/logo.blade.php`

### ACCEPTANCE:
- `vendor/bin/pint && npm run build && php artisan test` green
- Rider layout mobile-first, max 480px centered on desktop, bottom nav
- Admin sidebar grouped into 5 collapsible packages, usable on phone (hamburger + bottom nav), touch targets >=44px
- Role switcher works: admin can view as passenger and see rider home
- Design tokens used everywhere, no hardcoded green

### COMMIT:
`feat(nav): sprint 1 foundation branding + admin grouping + role switcher + map libs`
