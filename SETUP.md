# Quick Setup Guide - Hard Work Logistics Bus Reservation System

## ⚡ Quick Start (5 Minutes)

### Step 1: Setup Web Server

1. Download and install [XAMPP](https://www.apachefriends.org/)
2. Start Apache and MySQL from XAMPP Control Panel

### Step 2: Setup Database

1. Open browser and go to: `http://localhost/phpmyadmin`
2. Click "New" to create a database
3. Name it: `bus_ticket`
4. Click on the database name
5. Click "Import" tab
6. Choose file: `Backend/bus_ticket.sql`
7. Click "Go"

### Step 3: Configure Database Connection

1. Open file: `Backend/BusReserve/Constants.php`
2. Update if needed (default works for XAMPP):
   ```php
   <?php
   define('DB_NAME','bus_ticket');
   define('DB_HOST','localhost');
   define('DB_USER','root');
   define('DB_PASSWORD','');  // Leave empty for default XAMPP
   ?>
   ```

### Step 4: Copy Backend Files

1. Copy folder: `Backend/BusReserve/`
2. Paste to: `C:\xampp\htdocs\` (Windows) or `/Applications/XAMPP/htdocs/` (Mac)

### Step 5: Run the Application

1. Open browser
2. Go to: `http://localhost/Bus%20Reservation/index.html`
3. Or navigate to where you placed the files

## 🔑 Test Accounts

### Admin Account

```
Email: admin@admin
Password: admin123
Role: Administrator
```

### Customer Test Accounts

The database includes several test customer accounts. For security, all passwords are hashed.

**Option 1: Register New Account (Recommended)**

- Use the Sign Up form to create your own account
- Choose any email and password

**Option 2: Use Existing Test Users**
Pre-loaded test usernames (you'll need to set passwords yourself):

```
peter@gmail.com   - Location: Chingola
jacob@gmail.com   - Location: Kitwe
john@gmail.com    - Location: Lusaka
paul@gmail.com    - Location: Kitwe
jane@gmail.com    - Location: Chingola
nancy@gmail.com   - Location: Lusaka
mary@gmail.com    - Location: Chingola
```

**Note**: These accounts have hashed passwords. To use them, you can:

1. Reset passwords via phpMyAdmin (login table)
2. Or simply create a new account through registration

**Best Practice**: Register a fresh account for testing!

## ✅ Verify Installation

1. Can you see the login page? ✓
2. Can you register a new account? ✓
3. Can you login? ✓
4. Can you see the schedule? ✓
5. Can you make a reservation? ✓

## 🆘 Common Issues

### Issue: "Connection failed" error

**Fix**:

- Make sure MySQL is running in XAMPP
- Check database name is `bus_ticket`
- Verify `Constants.php` settings

### Issue: "404 Not Found" on login

**Fix**:

- Make sure `BusReserve` folder is in `htdocs`
- Check the URL: `http://localhost/BusReserve/transactions.php`

### Issue: Reservations not working

**Fix**:

- Check browser console for errors (F12)
- Verify database has data (check phpMyAdmin)
- Clear browser cache and reload

## 📝 Next Steps

1. **Change Admin Password**

   - Login as admin
   - Go to database and update password

2. **Add Your Buses**

   - Admin Dashboard → Bus List → Add

3. **Create Schedules**

   - Admin Dashboard → Schedule List → Add

4. **Customize Branding**
   - Edit contact info in `home.html`
   - Update company name and colors

## 🎯 Features to Try

1. **As Customer:**

   - Register an account
   - Browse available trips
   - Filter by date/location
   - Reserve a seat
   - View notifications
   - Print ticket

2. **As Admin:**
   - Add new buses
   - Create schedules
   - View reservations
   - Edit/Delete entries

## 📱 Mobile Testing

Open on your phone:
`http://[YOUR-IP-ADDRESS]/Bus%20Reservation/index.html`

To find your IP:

- Windows: Run `ipconfig` in cmd
- Mac/Linux: Run `ifconfig` in terminal

## 🔒 Security Reminder

1. Change default admin password
2. Update database password in production
3. Use HTTPS in production
4. Regular backups of database
