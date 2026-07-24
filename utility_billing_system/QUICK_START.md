# 🚀 Quick Start Guide

## Step 1: Setup Test Users (One-Time Setup)

Visit this URL in your browser:
```
http://localhost/utility_billing_system/database/create_test_users.php
```

This will:
- Set password `password123` for all test users
- Assign reader to meter for testing
- Display all test credentials

**⚠️ Delete this file after running it!**

---

## Step 2: Access the Portals

### 🏠 Main Homepage
```
http://localhost/utility_billing_system/
```
Choose your role: Admin | Reader | Consumer

---

### 👷‍♂️ Reader Portal

**URL**: `http://localhost/utility_billing_system/reader_portal`

**Login Credentials**:
- Username: `lucas`
- Password: `password123`

**What you can do**:
1. View assigned meters
2. Add new meter readings
3. View recent readings history

**Test Flow**:
1. Login → Dashboard
2. Click "Add Reading" on meter #2273444242
3. Enter current reading (e.g., 100.00)
4. Submit → Bill will be auto-generated
5. Check "Recent Readings" to verify

---

### 👩‍🔧 Consumer Portal

**URL**: `http://localhost/utility_billing_system/consumer_portal`

**Login Credentials**:
- Email: `michaelacristobal1998@gmail.com`
- Password: `password123`

**What you can do**:
1. View water usage chart
2. See billing summary
3. View complete billing history
4. Check payment status

**Test Flow**:
1. Login → Dashboard
2. View usage statistics
3. Click "View All Billing History"
4. Click "View Details" on any bill

---

### 👨‍💼 Admin Portal

**URL**: `http://localhost/utility_billing_system/portal/login.php`

**Login Credentials**:
- Email: `admin@example.com`
- Password: `password` (your existing admin password)

---

## Step 3: Test Complete Workflow

### Scenario: Reader adds a reading, Consumer views the bill

1. **As Reader** (lucas):
   - Login to reader portal
   - Go to "Add Reading"
   - Select meter #2273444242
   - Enter current reading: `150.00`
   - Add notes: "Regular monthly reading"
   - Submit

2. **System automatically**:
   - Creates reading record (status: approved)
   - Calculates consumption (150 - previous reading)
   - Generates bill with:
     - Rate: ₱5.00 per m³
     - Fixed charge: ₱50.00
     - Tax: 12%
   - Updates meter's last reading

3. **As Consumer** (michaela):
   - Login to consumer portal
   - View dashboard → See new bill in "Recent Bills"
   - Click "View All Billing History"
   - Click "View Details" to see breakdown
   - Check usage chart (updates with new data)

---

## 📱 Mobile Testing

Test on mobile devices:
1. Find your computer's local IP (e.g., 192.168.1.100)
2. Access from mobile: `http://192.168.1.100/utility_billing_system/`
3. All portals are fully responsive!

---

## 🎨 Features Showcase

### Reader Portal
- ✅ Clean blue/white dashboard
- ✅ Real-time meter information display
- ✅ Validation (current ≥ previous reading)
- ✅ Success notifications
- ✅ Mobile-friendly forms

### Consumer Portal
- ✅ Interactive Chart.js usage graph
- ✅ Color-coded payment status badges
- ✅ Detailed bill breakdown modal
- ✅ Responsive card layout
- ✅ Real-time data loading

---

## 🔧 Customization

### Change Billing Rates
Edit: `api/add_reading.php` (lines 47-49)
```php
$rate_per_unit = 5.00;   // Change rate per cubic meter
$fixed_charge = 50.00;   // Change fixed charge
$tax_rate = 12.00;       // Change tax percentage
```

### Change Theme Colors
Edit: `assets/css/custom.css` (lines 2-8)
```css
:root {
    --primary-blue: #0d6efd;  /* Main blue color */
    --dark-blue: #0a58ca;     /* Darker blue */
    --light-blue: #cfe2ff;    /* Light blue */
}
```

---

## ❓ Troubleshooting

### "No meters assigned"
Run: `http://localhost/utility_billing_system/database/create_test_users.php`

### "Invalid credentials"
1. Check if you ran the create_test_users.php script
2. Verify XAMPP MySQL is running
3. Check database exists: `utility_billing_system`

### Charts not showing
1. Check browser console (F12)
2. Ensure internet connection (Chart.js CDN)
3. Verify bills exist in database

### Session issues
1. Clear browser cookies
2. Restart Apache in XAMPP
3. Check session.save_path in php.ini

---

## 📊 Database Check

Verify data in phpMyAdmin:

```sql
-- Check readers
SELECT * FROM utilityreader;

-- Check consumers
SELECT * FROM consumer;

-- Check meter assignments
SELECT m.*, c.FirstName, r.Username 
FROM meter m
LEFT JOIN consumer c ON m.ConsumerID = c.ConsumerID
LEFT JOIN utilityreader r ON m.ReaderID = r.ReaderID;

-- Check readings
SELECT * FROM meterreadingdata ORDER BY ReadingDate DESC;

-- Check bills
SELECT * FROM billingstatement ORDER BY BillingDate DESC;
```

---

## ✅ Success Checklist

- [ ] XAMPP Apache & MySQL running
- [ ] Database `utility_billing_system` exists
- [ ] Ran `create_test_users.php` script
- [ ] Can access main page: `http://localhost/utility_billing_system/`
- [ ] Reader can login and add readings
- [ ] Consumer can login and view bills
- [ ] Bills auto-generate when readings added
- [ ] Charts display correctly
- [ ] Mobile responsive works

---

## 🎉 You're All Set!

The system is ready to use. Enjoy testing the portals!

**Need help?** Check `README_PORTALS.md` for detailed documentation.
