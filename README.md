<div align="center">
  <img src="public/logo.png" alt="Laracheck Logo" width="120">
  
  # Laracheck
  
  **Modern Exception Tracking & Uptime Monitoring for Laravel**
  
  Track exceptions, monitor uptime, and keep your Laravel applications healthy with real-time alerts and beautiful analytics.
</div>

---

## ✨ Features

-   🎯 **Exception Tracking** - Catch and monitor all exceptions in real-time
-   📡 **Uptime Monitoring** - Automatic site availability checks every minute with instant alerts
-   🔔 **Smart Notifications** - Exceptions Batched alerts via Email, Slack, and Discord
-   📊 **Beautiful Dashboard** - Powered by Filament 4 with rich analytics and insights
-   👥 **Multi-Site Management** - Track unlimited sites from a single dashboard
-   🔐 **Secure Access** - User roles, site permissions, and 2FA (App + Email)
-   🌍 **Environment Detection** - Separate tracking for local, staging, and production
-   📈 **Advanced Filtering** - Filter by status, site, code, date range, and more
-   📥 **Export to Markdown** - Copy exception details for tickets and sharing

## 🚀 Quick Start

### Install Laracheck Server

```bash
# Clone the repository
git clone https://github.com/officineweb/laracheck.git
cd laracheck

# Install dependencies
composer install

# Setup environment
cp .env.example .env
php artisan key:generate

# Configure database in .env, then migrate
php artisan migrate

# Create admin user (admin@laracheck.test / Password123!)
php artisan db:seed --class=AdminUserSeeder
```

Customize Url and Database into your .env file if neeeded.

Configure SMTP in your `.env`:

```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=your_username
MAIL_PASSWORD=your_password
```

### Configure Your First Site

1. **Create a site** in the Laracheck dashboard
2. **Copy your API key** from the "Setup" button and follow the steps
3. **Configure your app** installing the Client via Composer and configuring:

```env
LARACHECK_KEY=your-api-key-here
LARACHECK_URL=http://localhost:8000
```

4. **Register exception tracking** in `bootstrap/app.php`:

```php
->withExceptions(function (Exceptions $exceptions): void {
    app('laracheck')->track($exceptions);
})
```

That's it! Exceptions will now be tracked automatically. 🎉

## 📖 Key Concepts

### Exception Management

-   **4xx errors** (404, 403, etc.) are automatically marked as fixed with no notifications
-   **5xx errors** (500, 503, etc.) trigger immediate alerts and require manual resolution
-   **Batch notifications** every 5 minutes prevent alert fatigue
-   **Markdown export** for easy sharing

### Uptime Monitoring

-   Checks run **every minute** for enabled sites
-   **Outage detection** with automatic resolution tracking
-   **Multi-channel alerts** (Email, Slack, Discord)
-   **Downtime statistics** with duration tracking

### User & Permission Management

-   **Admins** have full access to all sites and settings
-   **Regular users** only see assigned sites
-   **Site owners** can manage their own sites
-   **2FA support** with app and email authentication

## 🛠️ Requirements

-   PHP 8.2 or higher
-   MySQL, PostgreSQL, or SQLite
-   Composer 2

## 🔧 Configuration

### Email Notifications

Configure SMTP in your `.env`:

```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=your_username
MAIL_PASSWORD=your_password
```

### Slack & Discord Webhooks

Add webhook URLs directly in site settings through the admin panel.

### Scheduled Tasks

Laracheck runs the following scheduled tasks:

-   **Uptime Checks**: Every minute - monitors site availability
-   **Exception Notifications**: Every 5 minutes - sends batched alerts
-   **Data Cleanup**: Daily at midnight - removes old data based on retention policy

Make sure the Laravel scheduler is running:

```bash
* * * * * cd /path-to-laracheck && php artisan schedule:run >> /dev/null 2>&1
```

Or use Laravel's built-in scheduler:

```bash
php artisan schedule:work
```

### Data Retention

By default, Laracheck keeps exceptions and outages for **365 days**. Configure in `.env`:

```env
DATA_RETENTION_DAYS=365
```

See [Data Retention Documentation](DATA-RETENTION.md) for more details.

## 🤝 Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

1. Fork the repository
2. Create your feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add some amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

## 📄 License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## 💙 Credits

Built with ❤️ by [Officine Web](https://officineweb.it)

---

<div align="center">
  <strong>Keep your Laravel applications bug-free and online!</strong>
  <br>
  <a href="https://github.com/officineweb/laracheck">⭐ Star on GitHub</a>
</div>
