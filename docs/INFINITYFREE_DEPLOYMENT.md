# InfinityFree Deployment Guide for CommServe

## Prerequisites
- InfinityFree account (sign up at infinityfree.net)
- FTP client (FileZilla recommended)
- Your project files ready

## Step 1: Sign Up for InfinityFree
1. Go to https://infinityfree.net
2. Click "Sign Up Now"
3. Create your account
4. Create a new hosting account
5. Choose a subdomain (e.g., commserve.infinityfreeapp.com)

## Step 2: Get Database Credentials
After account creation:
1. Go to Control Panel
2. Click "MySQL Databases"
3. Create a new database
4. Note down:
   - Database name (e.g., `epiz_12345678_commserve`)
   - Username (e.g., `epiz_12345678`)
   - Password (what you set)
   - Host (e.g., `sql123.infinityfree.com`)

## Step 3: Import Database Schema
1. Go to Control Panel → phpMyAdmin
2. Login with your database credentials
3. Select your database
4. Click "Import" tab
5. Upload `database/mysql-schema.sql`
6. Click "Go"

## Step 4: Update .env File
Edit `.env` in your project:
```env
APP_NAME="CommServe"
APP_ENV=production
APP_KEY=base64:uA9nh/UadOV526aQdEU5he76X3KUlAEGQCpEHo3xcRQ=
APP_DEBUG=false
APP_URL=http://commserve.infinityfreeapp.com

LOG_CHANNEL=daily
LOG_LEVEL=error

DB_CONNECTION=mysql
DB_HOST=sql123.infinityfree.com
DB_PORT=3306
DB_DATABASE=epiz_12345678_commserve
DB_USERNAME=epiz_12345678
DB_PASSWORD=your_password

SESSION_DRIVER=file
CACHE_STORE=file
QUEUE_CONNECTION=sync
```

## Step 5: Prepare Files for Upload
1. Make sure vendor folder exists (run `composer install` locally)
2. Create folder structure:
   ```
   htdocs/
   ├── .htaccess (from public folder)
   ├── index.php (from public folder)
   ├── app/
   ├── bootstrap/
   ├── config/
   ├── database/
   ├── resources/
   ├── routes/
   ├── storage/
   ├── vendor/
   ├── .env
   └── artisan
   ```

## Step 6: Upload via FTP
1. Open FileZilla
2. Connect using FTP credentials from InfinityFree:
   - Host: `ftpupload.net`
   - Username: From control panel
   - Password: From control panel
   - Port: 21
3. Navigate to `/htdocs` folder on server
4. Upload all files EXCEPT:
   - .git/
   - node_modules/
   - tests/
   - .env.example

## Step 7: Set Permissions
After upload, set these folder permissions to 755:
- storage/
- storage/framework/
- storage/framework/cache/
- storage/framework/sessions/
- storage/framework/views/
- storage/logs/
- bootstrap/cache/

## Step 8: Create .htaccess in Root
Create or edit `.htaccess` in `/htdocs`:
```apache
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteRule ^(.*)$ public/$1 [L]
</IfModule>
```

## Step 9: Test Your Site
Visit: http://commserve.infinityfreeapp.com
- Should see: `{"message":"CommServe API - Laravel + MySQL",...}`

Test database: http://commserve.infinityfreeapp.com/test-db
- Should show: `{"status":"success","message":"Database connection working!"}`

## Troubleshooting

### 500 Error
- Check `.env` file is uploaded
- Check APP_KEY is set
- Check storage folder permissions
- Check database credentials

### Database Connection Error
- Verify database credentials in .env
- Check if database was created
- Check if schema was imported
- Try accessing phpMyAdmin directly

### File Permission Issues
- All folders should be 755
- All files should be 644
- Never use 777 permissions

## Important Notes

1. **InfinityFree Limitations:**
   - No command-line access
   - No composer commands on server
   - Must upload vendor folder
   - 5GB storage limit
   - Hit counter (10,000 hits/day)

2. **Security:**
   - Set `APP_DEBUG=false` in production
   - Use strong `APP_KEY`
   - Don't expose `.env` file

3. **Performance:**
   - Use file-based cache/sessions
   - Optimize images before upload
   - Minimize database queries

## Next Steps
After successful deployment:
1. Create admin user via phpMyAdmin
2. Test all features
3. Set up CORS if needed for API
4. Consider custom domain

## Support
- InfinityFree Forum: https://forum.infinityfree.net
- Laravel Docs: https://laravel.com/docs
