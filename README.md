1) Create or move these files into a Laravel project at c:\xampp\htdocs
2) Install dependencies:
   composer install
3) Copy .env example:
   cp .env.example .env
   update DB_* values in .env
4) Generate app key:
   php artisan key:generate
5) Run migrations:
   php artisan migrate
6) Serve locally:
   php artisan serve
7) Visit http://127.0.0.1:8000/dashboard (login required — set up auth with Breeze/Jetstream or custom)
