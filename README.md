# CommServe - Laravel + Supabase

Barangay Community Service Platform built with Laravel and Supabase.

## 🚀 Quick Start

### Deploy Free (No Credit Card - 5 Minutes)

**[📖 Read Deployment Guide →](./docs/DEPLOYMENT_GUIDE.md)**

1. **Create Supabase account** - [supabase.com](https://supabase.com)
2. **Import database** - Run `database/supabase-schema.sql`
3. **Push to GitHub** - Your repository
4. **Deploy on Vercel** - [vercel.com](https://vercel.com)
5. **Done!** - App is live

---

## 💻 Local Development

```bash
# Install dependencies
composer install

# Configure environment
cp .env.example .env
php artisan key:generate

# Add Supabase credentials to .env
SUPABASE_URL=https://your-project.supabase.co
SUPABASE_KEY=your-anon-key
SUPABASE_SERVICE_KEY=your-service-role-key

# Start server
php artisan serve
```

Visit: `http://localhost:8000`

---

## 📁 Project Structure

```
commserve-laravel/
├── app/
│   ├── Services/
│   │   └── SupabaseService.php    # Database wrapper
│   └── Http/Controllers/          # Your controllers here
├── database/
│   └── supabase-schema.sql        # PostgreSQL schema
├── docs/                          # Documentation
│   ├── DEPLOYMENT_GUIDE.md        # Complete deployment steps
│   ├── DEPLOYMENT_CHECKLIST.md    # Quick checklist
│   └── MIGRATION_SUMMARY.md       # Migration details
├── resources/views/               # Blade templates
├── routes/web.php                 # Define routes
└── vercel.json                    # Deployment config
```

---

## 🗃️ Using Supabase Service

```php
use App\Services\SupabaseService;

$supabase = new SupabaseService();

// Query users
$users = $supabase->from('users')
    ->eq('barangay', 'Poblacion')
    ->orderBy('createdAt', 'desc')
    ->limit(10)
    ->get();

// Insert data
$supabase->insert('announcements', [
    'title' => 'New Announcement',
    'content' => 'Important information...',
    'barangay' => 'Poblacion'
]);

// Update data
$supabase->update('users', 
    ['phoneNumber' => '09171234567'],
    ['id' => $userId]
);

// Delete data
$supabase->delete('announcements', ['id' => 1]);
```

---

## 🌐 Free Tier Limits

**Vercel (Hosting):**
- ✅ 100 GB bandwidth/month
- ✅ Unlimited deployments
- ✅ Custom domains
- ✅ Automatic HTTPS

**Supabase (Database):**
- ✅ 500 MB database
- ✅ 2 GB bandwidth/month
- ✅ Unlimited API requests
- ✅ PostgreSQL database

**Total Cost: $0.00/month**

---

## 📖 Documentation

- **[Deployment Guide](./docs/DEPLOYMENT_GUIDE.md)** - Complete step-by-step
- **[Deployment Checklist](./docs/DEPLOYMENT_CHECKLIST.md)** - Quick checklist
- **[Migration Summary](./docs/MIGRATION_SUMMARY.md)** - Technical details
- [Laravel Docs](https://laravel.com/docs) - Framework documentation
- [Supabase Docs](https://supabase.com/docs) - Database documentation

---

## 🔧 Available Commands

```bash
# Generate application key
php artisan key:generate

# Clear cache
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Optimize for production
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Run development server
php artisan serve
```

---

## 🆘 Troubleshooting

**"APP_KEY is missing"**
```bash
php artisan key:generate
```

**"Database connection failed"**
- Verify Supabase credentials in `.env`
- Ensure schema was imported in Supabase

**"404 on routes"**
```bash
php artisan route:clear
php artisan config:clear
```

See [Deployment Guide](./docs/DEPLOYMENT_GUIDE.md) for more troubleshooting.

---

## 📧 Support

- GitHub Issues: [Create an issue](https://github.com/JcKintanar/LaravelPHP_CommServe/issues)
- Repository: [LaravelPHP_CommServe](https://github.com/JcKintanar/LaravelPHP_CommServe)

---

## 📄 License

Open-source software. Free to use and modify.

---

**Ready to deploy?** Start here: **[docs/DEPLOYMENT_GUIDE.md](./docs/DEPLOYMENT_GUIDE.md)** 🚀
