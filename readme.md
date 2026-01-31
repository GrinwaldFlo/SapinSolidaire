composer run dev

# How to start

1. Copy .env.example to .env and configure your database
2. Run `php artisan key:generate`
3. Run `php artisan migrate --seed`
4. Run `composer run dev` (starts Laravel server, queue worker, and asset bundler)
5. Register the first user at http://localhost:8000/admin to become Admin

## Alternative (if you want to run services separately)

Instead of `composer run dev`, you can run these in separate terminals:
- `php artisan serve` - Laravel development server
- `php artisan queue:work` - Process email queue
- `npm run dev` - Vite asset bundler
