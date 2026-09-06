# BAUST Exchange

A complete web application for buying, selling, sharing, exchanging, and renting items within the BAUST campus community.

## Features

- **Google OAuth 2.0 Authentication** - Any Google account can log in
- **User Roles** - Teachers and Students (Senior/Junior) can use the platform
- **Item Listings** - Post items for sale, exchange, share, rent, or buying interest
- **Rentals System** - Rent items with customizable periods (hour/day/week/month/semester)
- **Sharing System** - Share items with other campus members
- **Exchange Requests** - Request to exchange items with other users
- **Wanted Items** - Post items you're looking for
- **Messaging** - Chat with other users about items
- **Notifications** - Stay updated on requests and messages
- **Admin Panel** - Manage users, listings, categories, and reports
- **Responsive Design** - Works on desktop, tablet, and mobile

## Tech Stack

- **Frontend**: HTML5, CSS3, JavaScript, Bootstrap 5, Font Awesome
- **Backend**: PHP 8.3+
- **Database**: MySQL 8+
- **Server**: Apache (XAMPP)

## Installation

### Local Development (XAMPP)

1. **Clone/Download** the project to `C:\xampp\htdocs\BaustExchange\`

2. **Start XAMPP** (Apache and MySQL)

3. **Create Database**:
   - Open phpMyAdmin (http://localhost/phpmyadmin)
   - Create a new database named `baust_exchange`
   - Import `database/baust_exchange.sql`

4. **Configure Google OAuth**:
   - Go to [Google Cloud Console](https://console.cloud.google.com)
   - Create a new project
   - Enable Google+ API
   - Go to Credentials > Create OAuth Client ID
   - Set Authorized redirect URI to: `http://localhost/BaustExchange/google-callback.php`
   - Copy Client ID and Client Secret to `config/google-config.php`

5. **Access the Application**:
   - Open http://localhost/BaustExchange/
   - Login with your Google account

### Production Deployment (cPanel)

1. **Upload Files**: Upload all files to `public_html/baust-exchange/`

2. **Create Database**: Create a MySQL database via cPanel

3. **Import Database**: Import `database/baust_exchange.sql`

4. **Update Configuration**: Edit `config/config.php` with your database credentials

5. **Update Google OAuth**: 
   - Update redirect URI in Google Cloud Console to your domain
   - Update `GOOGLE_REDIRECT_URI` in `config/google-config.php`

6. **Set Admin User**: After first login, run this SQL query:
   ```sql
   UPDATE users SET role='admin' WHERE email='your-admin-email@gmail.com';
   ```

## Configuration

### Database Settings (`config/config.php`)

```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'baust_exchange');
define('DB_USER', 'root');
define('DB_PASSWORD', '');
```

### Google OAuth (`config/google-config.php`)

```php
define('GOOGLE_CLIENT_ID', 'your_client_id');
define('GOOGLE_CLIENT_SECRET', 'your_client_secret');
define('GOOGLE_REDIRECT_URI', 'http://localhost/BaustExchange/google-callback.php');
```

## Project Structure

```
baust-exchange/
├── index.php                 # Landing page
├── login.php                 # Google OAuth login
├── google-callback.php       # OAuth callback handler
├── logout.php                # Session logout
├── dashboard.php             # User dashboard
├── marketplace.php           # Browse all items
├── item.php                  # Item details
├── post-item.php             # Create new listing
├── edit-item.php             # Edit listing
├── my-listings.php           # User's listings
├── requests.php              # Exchange requests
├── wanted.php                # Wanted items
├── post-wanted.php           # Post wanted item
├── messages.php              # Message list
├── chat.php                  # Chat with user
├── notifications.php         # Notifications
├── profile.php               # User profile
├── settings.php              # Account settings
│
├── admin/                    # Admin panel
│   ├── dashboard.php
│   ├── users.php
│   ├── listings.php
│   ├── categories.php
│   ├── requests.php
│   ├── reports.php
│   └── activities.php
│
├── components/               # Reusable components
│   ├── header.php
│   ├── sidebar.php
│   ├── sidebar-menu.php
│   ├── right-sidebar.php
│   ├── footer.php
│   ├── cookie-banner.php
│   └── auth-check.php
│
├── config/                   # Configuration files
│   ├── config.php
│   ├── database.php
│   └── google-config.php
│
├── api/                      # AJAX endpoints
│   ├── search.php
│   ├── messages.php
│   ├── notifications.php
│   ├── exchange-request.php
│   └── reports.php
│
├── assets/                   # Static assets
│   ├── css/style.css
│   ├── js/app.js
│   └── images/
│
├── uploads/                  # User uploads
│   ├── items/
│   └── profiles/
│
└── database/                 # Database schema
    └── baust_exchange.sql
```

## Default Categories

- Books
- Furniture
- Electronics
- Clothing
- Stationery
- Academic Materials
- Sports
- Accessories
- Others

## Admin Setup

After creating your first user account via Google login, promote it to admin:

```sql
UPDATE users SET role='admin' WHERE email='your-email@gmail.com';
```

## Security Features

- PDO prepared statements (SQL injection protection)
- CSRF token protection
- XSS protection via htmlspecialchars
- Session regeneration after login
- Role-based authorization
- Ownership checks for modifications
- Secure file upload validation
- Server-side input validation

## Browser Support

- Chrome (latest)
- Firefox (latest)
- Safari (latest)
- Edge (latest)

## License

MIT License
