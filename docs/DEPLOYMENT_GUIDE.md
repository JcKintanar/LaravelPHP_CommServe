# CommServe Laravel + Supabase Deployment Guide
## Free Deployment (No Credit Card Required)

This guide will help you deploy your CommServe application using **Vercel** (free hosting) and **Supabase** (free PostgreSQL database).

---

## 📋 Prerequisites

- GitHub account (free)
- Vercel account (free, sign up with GitHub)
- Supabase account (free, sign up with GitHub)

---

## Step 1: Set Up Supabase (Free Database)

### 1.1 Create Supabase Project

1. Go to [https://supabase.com](https://supabase.com)
2. Click **"Start your project"**
3. Sign in with GitHub (no credit card needed)
4. Click **"New Project"**
5. Fill in:
   - **Name**: `commserve`
   - **Database Password**: Choose a strong password (save this!)
   - **Region**: Choose closest to your users
   - **Pricing Plan**: Free (selected by default)
6. Click **"Create new project"** (takes ~2 minutes)

### 1.2 Get Supabase Credentials

1. Once project is created, go to **Project Settings** (gear icon)
2. Click **"API"** in the left menu
3. Copy these values (you'll need them later):
   - **Project URL**: `https://your-project.supabase.co`
   - **anon/public key**: Long string starting with `eyJ...`
   - **service_role key**: Another long string (click "Reveal" to see it)

### 1.3 Import Database Schema

1. In Supabase dashboard, click **"SQL Editor"** (left sidebar)
2. Click **"New query"**
3. Copy and paste the contents of `database-schema.sql` (from this folder)
4. Click **"Run"** or press `Ctrl+Enter`
5. Wait for success message

---

## Step 2: Prepare Your Laravel Project

### 2.1 Update Environment Variables

1. Open the Laravel project folder: `commserve-laravel`
2. Open `.env` file
3. Update these values with your Supabase credentials:

```env
APP_NAME=CommServe
APP_ENV=production
APP_KEY=base64:... (keep existing value)
APP_DEBUG=false
APP_URL=https://your-app.vercel.app

# Supabase Configuration
SUPABASE_URL=https://your-project.supabase.co
SUPABASE_KEY=your-anon-key-here
SUPABASE_SERVICE_KEY=your-service-role-key-here

# Session & Cache (use file driver for Vercel)
SESSION_DRIVER=cookie
CACHE_DRIVER=array
QUEUE_CONNECTION=sync
```

### 2.2 Install Dependencies

```bash
cd commserve-laravel
composer install --no-dev --optimize-autoloader
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

---

## Step 3: Push to GitHub

### 3.1 Initialize Git (if not already done)

```bash
cd commserve-laravel
git init
git add .
git commit -m "Initial commit - Laravel + Supabase"
```

### 3.2 Create GitHub Repository

1. Go to [https://github.com/new](https://github.com/new)
2. **Repository name**: `commserve-laravel`
3. **Visibility**: Public or Private (your choice)
4. **Do NOT** check "Add README" (we already have files)
5. Click **"Create repository"**

### 3.3 Push Code to GitHub

```bash
git remote add origin https://github.com/YOUR-USERNAME/commserve-laravel.git
git branch -M main
git push -u origin main
```

---

## Step 4: Deploy to Vercel (Free Hosting)

### 4.1 Sign Up for Vercel

1. Go to [https://vercel.com](https://vercel.com)
2. Click **"Sign Up"**
3. Choose **"Continue with GitHub"**
4. Authorize Vercel to access your GitHub account

### 4.2 Import Project

1. On Vercel dashboard, click **"Add New..."** → **"Project"**
2. Find your `commserve-laravel` repository
3. Click **"Import"**

### 4.3 Configure Project Settings

1. **Framework Preset**: Select **"Other"** (not Laravel, we'll configure manually)
2. **Root Directory**: Leave as `./` (default)
3. **Build Command**: Leave empty
4. **Output Directory**: `public`
5. Click **"Environment Variables"** section
6. Add these variables one by one:

| Name | Value |
|------|-------|
| `APP_NAME` | `CommServe` |
| `APP_ENV` | `production` |
| `APP_KEY` | Copy from your `.env` file |
| `APP_DEBUG` | `false` |
| `SUPABASE_URL` | Your Supabase project URL |
| `SUPABASE_KEY` | Your Supabase anon key |
| `SUPABASE_SERVICE_KEY` | Your Supabase service key |
| `SESSION_DRIVER` | `cookie` |
| `CACHE_DRIVER` | `array` |

7. Click **"Deploy"**

### 4.4 Add Vercel Configuration

Create `vercel.json` in your project root:

```json
{
  "version": 2,
  "builds": [
    {
      "src": "public/index.php",
      "use": "@vercel/php"
    }
  ],
  "routes": [
    {
      "src": "/(css|js|images)/(.*)",
      "dest": "public/$1/$2"
    },
    {
      "src": "/(.*)",
      "dest": "public/index.php"
    }
  ],
  "env": {
    "APP_ENV": "production",
    "APP_DEBUG": "false",
    "APP_URL": "https://your-app.vercel.app",
    "APP_KEY": "@app_key",
    "SUPABASE_URL": "@supabase_url",
    "SUPABASE_KEY": "@supabase_key",
    "SUPABASE_SERVICE_KEY": "@supabase_service_key"
  }
}
```

Push this change:
```bash
git add vercel.json
git commit -m "Add Vercel configuration"
git push
```

Vercel will automatically redeploy.

---

## Step 5: Test Your Application

1. Once deployment completes, Vercel will show you a URL like: `https://commserve-laravel.vercel.app`
2. Click **"Visit"** to open your application
3. Test the signup and login features
4. Check if data is saving to Supabase

---

## 🎉 Deployment Complete!

Your application is now live at:
- **Frontend**: `https://your-app.vercel.app`
- **Database**: Supabase Dashboard (view data anytime)

---

## Free Tier Limits

### Vercel Free Plan:
- ✅ 100 GB bandwidth/month
- ✅ Unlimited deployments
- ✅ Automatic HTTPS
- ✅ Custom domains (free)

### Supabase Free Plan:
- ✅ 500 MB database space
- ✅ 2 GB bandwidth/month
- ✅ 50 MB file storage
- ✅ Unlimited API requests

---

## Troubleshooting

### Issue: "APP_KEY is missing"
**Solution**: Generate a new key:
```bash
php artisan key:generate
```
Copy the key from `.env` and add it to Vercel environment variables.

### Issue: "Database connection failed"
**Solution**: 
1. Check Supabase URL and keys are correct in Vercel environment variables
2. Make sure you ran the SQL schema in Supabase SQL Editor
3. Check Supabase project is active (not paused)

### Issue: "404 Not Found" on routes
**Solution**: Clear Laravel cache and redeploy:
```bash
php artisan config:clear
php artisan route:clear
php artisan view:clear
git add .
git commit -m "Clear cache"
git push
```

### Issue: "Permission denied" errors
**Solution**: Make sure `storage` and `bootstrap/cache` are writable:
```bash
chmod -R 775 storage bootstrap/cache
```

---

## Custom Domain (Optional, Still Free!)

1. In Vercel dashboard, go to your project
2. Click **"Settings"** → **"Domains"**
3. Add your custom domain (e.g., `commserve.com`)
4. Update DNS records as instructed by Vercel
5. Vercel provides free SSL certificate automatically

---

## Next Steps

- [ ] Set up Supabase Auth for better user management
- [ ] Add email verification (Supabase Email Auth)
- [ ] Configure file uploads to Supabase Storage
- [ ] Set up monitoring with Vercel Analytics (free)
- [ ] Add custom domain

---

## Support

- **Vercel Docs**: https://vercel.com/docs
- **Supabase Docs**: https://supabase.com/docs
- **Laravel Docs**: https://laravel.com/docs

Happy deploying! 🚀
