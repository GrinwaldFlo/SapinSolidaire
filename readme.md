composer run dev

# How to start

1. Run `composer install`
2. Copy .env.example to .env and configure your database
3. Run `php artisan key:generate`
4. Run `php artisan migrate --seed`
5. Run `composer run dev` (starts Laravel server, queue worker, and asset bundler)
5. Register the first user at http://localhost:8000/admin to become Admin

## Alternative (if you want to run services separately)

Instead of `composer run dev`, you can run these in separate terminals:
- `php artisan serve` - Laravel development server
- `php artisan queue:work` - Process email queue
- `npm run dev` - Vite asset bundler


## Use PHP 8.4

echo 'export PATH=/opt/php84/bin:$PATH' >> ~/.profile
source ~/.profile
