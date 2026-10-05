# FishWarehouse - Freezing Fish Factory Warehouse Management System

## Tech Stack
- **Backend:** Laravel 12.69.3 (PHP 8.2) with Sanctum SPA auth
- **Frontend:** Vue 3 + Vuetify 4 + Pinia + VCalendar@next + ApexCharts + MDI 7.4.47
- **Database:** MySQL/MariaDB 10.4.32
- **Build:** Vite 7.3.6 with PWA support
- **Auth:** Cookie-based Sanctum SPA authentication (no API tokens)

## Project Structure
```
warehouse/
├── app/
│   ├── Http/Controllers/Api/     # All API controllers
│   ├── Models/                    # 16 Eloquent models
│   └── Services/StockService.php # Business logic for stock operations
├── database/
│   ├── migrations/               # 19 migration files
│   └── seeders/                  # Test data seeder
├── resources/
│   ├── css/app.css               # Global styles
│   ├── js/
│   │   ├── app.js                # Vue entry point
│   │   ├── App.vue               # Root component
│   │   ├── router/index.js       # Vue Router
│   │   ├── stores/app.js         # Pinia store
│   │   ├── plugins/              # Vuetify, i18n
│   │   ├── utils/api.js          # Axios instance
│   │   ├── layouts/              # MainLayout.vue
│   │   ├── components/           # CrudPage.vue (reusable)
│   │   └── pages/                # All page components
│   ├── locales/                  # en, fr, es, ar JSON
│   └── views/app.blade.php       # SPA Blade template
├── routes/
│   ├── api.php                   # API routes
│   └── web.php                   # SPA catch-all
└── vite.config.js                # Vite + Vue + PWA
```

## Key Architecture Decisions
1. **No WebSocket** - all operations are synchronous as requested
2. **Cookie-based auth** via Sanctum stateful API
3. **StockService** handles transactional stock logic (reception/sale)
4. **CrudPage.vue** is a reusable component for all settings pages
5. **Cascading filters** on Voucher Operations and Fish Stock pages
6. **VCalendar** used for all date pickers and workforce calendar
7. **i18n** supports EN, FR, ES, AR (with RTL)
8. **Dark/Light theme** toggle via Vuetify

## API Endpoints
- `POST /api/login` - Login (needs web middleware for session)
- `POST /api/logout` - Logout
- `GET /api/user` - Current user
- `GET /api/dashboard` - Dashboard stats
- `CRUD /api/providers|clients|fish-types|consumable-types|containers|warehouses|voucher-types`
- `GET|POST /api/vouchers` - Voucher CRUD
- `GET /api/fish-stock` - Active fish stock
- `GET /api/fish-stock/available` - Available stock for sales
- `GET /api/fish-stock/archives` - Archived stock
- `GET /api/consumable-stock` - Consumable stock
- `GET|POST /api/consumable-vouchers` - Consumable vouchers
- `CRUD /api/workers` - Workers
- `GET|POST|DELETE /api/workforce-entries` - Workforce entries
- `POST /api/workforce-entries/bulk` - Bulk assign entries

## Test Credentials
- Email: test@test.com
- Password: password

## Important Notes
- Stock operations use database transactions with `lockForUpdate()`
- When fish stock quantity reaches 0, it's archived automatically
- Consumable stock is subtracted during reception vouchers
- The `date` field on vouchers is separate from `created_at`
