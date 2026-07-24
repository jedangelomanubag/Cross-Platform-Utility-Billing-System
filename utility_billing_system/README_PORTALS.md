# Utility Billing System - Reader & Consumer Portals

## 📋 Overview
This project includes two new web portals for the Utility Billing System:
1. **Reader Portal** - For utility readers to record meter readings
2. **Consumer Portal** - For consumers to view bills and usage history

## 🚀 Quick Start

### Prerequisites
- XAMPP with Apache and MySQL running
- Database: `utility_billing_system` (already setup)

### Installation
All files are already created. No additional installation needed.

### Setup Test Data
1. Open phpMyAdmin: `http://localhost/phpmyadmin`
2. Select database: `utility_billing_system`
3. Run the SQL script: `database/setup_test_data.sql`
   - This assigns Reader #1 (lucas) to Meter #1

## 🔐 Test Credentials

### Reader Portal
- **URL**: `http://localhost/utility_billing_system/reader_portal`
- **Username**: `lucas`
- **Password**: (use the existing password in your database)

### Consumer Portal
- **URL**: `http://localhost/utility_billing_system/consumer_portal`
- **Email**: `michaelacristobal1998@gmail.com`
- **Password**: (use the existing password in your database)

### Admin Portal
- **URL**: `http://localhost/utility_billing_system/portal/login.php`
- **Email**: `admin@example.com`
- **Password**: `password` (default from your existing setup)

## 📁 Project Structure

```
utility_billing_system/
├── portal_selection.php         # Role selection page
├── public/
│   └── index.php               # Your existing homepage
├── includes/
│   └── db_connect.php          # MySQLi database connection
├── assets/
│   └── css/
│       └── custom.css          # Blue & white theme
├── api/
│   ├── reader_login.php        # Reader authentication
│   ├── consumer_login.php      # Consumer authentication
│   ├── add_reading.php         # Add meter reading & generate bill
│   ├── consumer_fetch_bills.php # Fetch consumer bills
│   └── list_recent_readings.php # List reader's recent readings
├── reader_portal/
│   ├── index.php               # Reader login page
│   ├── dashboard.php           # Reader dashboard
│   ├── add_reading.php         # Add reading form
│   ├── recent_readings.php     # View recent readings
│   └── logout.php              # Logout
├── consumer_portal/
│   ├── index.php               # Consumer login page
│   ├── dashboard.php           # Consumer dashboard with chart
│   ├── billing_history.php     # Complete billing history
│   └── logout.php              # Logout
└── database/
    └── setup_test_data.sql     # Test data setup script
```

## ✨ Features

### Reader Portal Features
- ✅ Secure login with username/password
- ✅ Dashboard showing assigned meters
- ✅ Add new meter readings
- ✅ Automatic bill generation on reading submission
- ✅ View recent readings history
- ✅ Responsive design for mobile/tablet/desktop

### Consumer Portal Features
- ✅ Secure login with email/password
- ✅ Dashboard with usage statistics
- ✅ Chart.js graph showing water usage trend
- ✅ View all billing history
- ✅ Payment status indicators (Paid/Unpaid/Overdue)
- ✅ Detailed bill breakdown modal
- ✅ Fully responsive design

## 🧪 Testing Workflow

### Test Reader Portal
1. Go to: `http://localhost/utility_billing_system/reader_portal`
2. Login with reader credentials
3. View assigned meters on dashboard
4. Click "Add Reading" on a meter
5. Enter current reading (must be ≥ last reading)
6. Submit - this will:
   - Insert into `meterreadingdata` (status: approved)
   - Create new `billingstatement` (status: unpaid)
   - Update `meter.LastReading` and `LastReadingDate`
   - Calculate bill using: Rate=5.00, Fixed=50.00, Tax=12%
7. View recent readings

### Test Consumer Portal
1. Go to: `http://localhost/utility_billing_system/consumer_portal`
2. Login with consumer credentials
3. View dashboard with:
   - Total unpaid/paid amounts
   - Overdue bills count
   - Water usage chart
   - Recent bills list
4. Click "View All Billing History"
5. Click "View Details" on any bill to see breakdown

## 🎨 Design Features
- **Color Scheme**: Blue (#0d6efd) & White
- **Framework**: Bootstrap 5.3.0
- **Icons**: Unicode emojis
- **Charts**: Chart.js for usage visualization
- **Responsive**: Mobile-first design
- **Typography**: Clean, modern Segoe UI font

## 🔧 Configuration

### Database Connection
File: `includes/db_connect.php`
```php
define('DB_HOST', 'localhost');
define('DB_USERNAME', 'root');
define('DB_PASSWORD', '');
define('DB_NAME', 'utility_billing_system');
```

### Default Billing Rates
File: `api/add_reading.php`
```php
$rate_per_unit = 5.00;   // ₱5 per cubic meter
$fixed_charge = 50.00;   // ₱50 fixed charge
$tax_rate = 12.00;       // 12% tax
```

## 📱 Mobile Responsiveness
All portals are fully responsive and tested on:
- ✅ Desktop (1920x1080 and above)
- ✅ Tablet (768px - 1024px)
- ✅ Mobile (320px - 767px)
- ✅ Android browsers (Chrome, Firefox)
- ✅ iOS browsers (Safari, Chrome)

## 🔒 Security Features
- Session-based authentication
- Password verification using `password_verify()`
- SQL injection protection (prepared statements)
- XSS protection (htmlspecialchars)
- Input sanitization
- CSRF protection ready (can be enhanced)

## 🐛 Troubleshooting

### Issue: "Connection failed"
- Check if XAMPP MySQL is running
- Verify database name is correct
- Check credentials in `includes/db_connect.php`

### Issue: "No meters assigned"
- Run `database/setup_test_data.sql` to assign readers to meters
- Or manually update via phpMyAdmin: `UPDATE meter SET ReaderID = 1 WHERE MeterID = 1`

### Issue: "Invalid username or password"
- Passwords are bcrypt hashed
- Use existing passwords from your database
- Or create new users via admin panel

### Issue: Charts not showing
- Check browser console for JavaScript errors
- Ensure Chart.js CDN is accessible
- Verify bills exist in database

## 📊 Database Tables Used

### Reader Portal
- `utilityreader` - Reader authentication
- `meter` - Assigned meters
- `meterreadingdata` - Reading records
- `billingstatement` - Generated bills

### Consumer Portal
- `consumer` - Consumer authentication
- `meter` - Consumer's meters
- `billingstatement` - Billing history

## 🎯 Next Steps / Enhancements
- [ ] Add payment processing functionality
- [ ] Email notifications for new bills
- [ ] PDF bill generation
- [ ] SMS notifications
- [ ] Mobile app integration
- [ ] Advanced reporting
- [ ] Multi-language support

## 📞 Support
For issues or questions, contact your system administrator.

---

**Version**: 1.0.0  
**Last Updated**: October 20, 2025  
**Developed with**: PHP 8.2, MySQL, Bootstrap 5, Chart.js
