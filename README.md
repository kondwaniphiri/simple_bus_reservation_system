# Hard Work Logistics - Bus Reservation System

A comprehensive web-based bus reservation system for managing bus schedules, reservations, and customer bookings across multiple locations in Zambia.

## 🚌 Features

### Customer Features

- **User Registration & Authentication**: Secure account creation with password hashing
- **Browse Available Trips**: View all scheduled bus trips with detailed information
- **Advanced Search & Filters**:
  - Search by date, origin, destination
  - Real-time filtering of available trips
  - View available seats for each trip
- **Seat Reservation**:
  - Visual seat selection interface
  - Real-time seat availability updates
  - Instant reservation confirmation
- **My Reservations**:
  - View all current reservations
  - Print/Download tickets
  - Reservation details with trip information
- **Responsive Design**: Fully mobile-friendly interface

### Admin Features

- **Dashboard Management**: Comprehensive admin panel
- **Bus Fleet Management**:
  - Add, edit, and delete buses
  - Track bus details (name, number plate, capacity, location)
- **Schedule Management**:
  - Create and manage trip schedules
  - Set departure/arrival times
  - Manage available seats
- **Reservation Oversight**:
  - View all customer reservations
  - Edit or cancel reservations
  - Track booking analytics

## 📋 Prerequisites

Before installation, ensure you have:

- **Web Server**: Apache (XAMPP, WAMP, or LAMP)
- **PHP**: Version 7.4 or higher
- **MySQL**: Version 5.7 or higher
- **Browser**: Modern browser (Chrome, Firefox, Safari, Edge)

## 🛠️ Installation

### 1. Clone or Download the Repository

```bash
git clone https://github.com/yourusername/bus-reservation.git
```

Or download and extract the ZIP file to your web server directory.

### 2. Set Up the Database

1. Start your MySQL server (via XAMPP/WAMP/LAMP)
2. Open phpMyAdmin (usually at `http://localhost/phpmyadmin`)
3. Create a new database named `bus_ticket`
4. Import the database schema:
   - Click on the `bus_ticket` database
   - Go to the "Import" tab
   - Choose the file `Backend/bus_ticket.sql`
   - Click "Go" to import

### 3. Configure Database Connection

Edit the file `Backend/BusReserve/Connection.php`:

```php
<?php
class Connection {
    private $host = "localhost";
    private $user = "root";          // Your MySQL username
    private $password = "";          // Your MySQL password
    private $database = "bus_ticket";

    public $conn;

    function connect() {
        $this->conn = new mysqli($this->host, $this->user, $this->password, $this->database);

        if ($this->conn->connect_error) {
            die("Connection failed: " . $this->conn->connect_error);
        }

        return $this->conn;
    }
}
?>
```

### 4. Move Backend Files

1. Copy the `BusReserve` folder from `Backend/BusReserve/`
2. Paste it into your web server's root directory:
   - XAMPP: `C:\xampp\htdocs\BusReserve\`
   - WAMP: `C:\wamp64\www\BusReserve\`
   - LAMP: `/var/www/html/BusReserve/`

### 5. Start the Application

1. Start Apache and MySQL services
2. Open your browser
3. Navigate to: `http://localhost/Bus%20Reservation/index.html`

## 👤 Test Accounts

### Admin Account

```
Email: admin@admin
Password: admin123
Role: Administrator
```

**Important**: Change the admin password after first login for security!

### Customer Test Accounts

The database comes pre-loaded with test customer accounts. All customer passwords are hashed in the database for security. To test the customer features, you can:

1. **Register a new account** through the sign-up form, OR
2. **Use these pre-configured accounts** (passwords need to be set by you):

```
Username: peter    | Email: peter@gmail.com   | Location: Chingola
Username: jacob    | Email: jacob@gmail.com   | Location: Kitwe
Username: john     | Email: john@gmail.com    | Location: Lusaka
Username: paul     | Email: paul@gmail.com    | Location: Kitwe
Username: jane     | Email: jane@gmail.com    | Location: Chingola
Username: nancy    | Email: nancy@gmail.com   | Location: Lusaka
Username: Mary     | Email: mary@gmail.com    | Location: Chingola
```

**Note**: These accounts have hashed passwords in the database. To use them:

- Either register new accounts with your own passwords
- Or modify the database passwords manually via phpMyAdmin
- The system uses bcrypt password hashing for security

**Recommendation**: Create your own test account through the registration page for the best experience.

## 📁 Project Structure

```
Bus Reservation/
├── Backend/
│   ├── bus_ticket.sql          # Database schema
│   └── BusReserve/
│       ├── Connection.php      # Database connection
│       ├── Constants.php       # Configuration constants
│       ├── DbOperations.php    # Database operations
│       ├── login.php          # Login handler
│       └── transactions.php    # API endpoints
├── js/
│   ├── admin.script.js        # Admin panel functionality
│   ├── script.js              # Reservation page logic
│   ├── script2.js             # Home page logic
│   ├── utils.js               # Utility functions
│   └── jquery-3.6.0.min.js    # jQuery library
├── style/
│   ├── admin.style.css        # Admin panel styles
│   ├── index.css              # Login page styles
│   └── style.css              # Main stylesheet
├── fontawesome/               # Icon library
├── img/                       # Images
├── index.html                 # Login/Registration page
├── home.html                  # Customer home page
├── reserve.html               # Trip reservation page
├── admin.dashboard.html       # Admin dashboard
└── README.md                  # This file
```

## 🔧 Configuration

### Updating API Endpoints

If your backend is hosted elsewhere, update the API URL in JavaScript files:

```javascript
// In js/script.js, js/script2.js, js/admin.script.js
url: "http://your-domain.com/BusReserve/transactions.php";
```

### Customizing Locations

Update locations in:

1. `index.html` - Registration form datalist
2. `reserve.html` - Filter dropdowns
3. Database - Update existing records as needed

## 🎨 Customization

### Branding

1. **Logo & Company Name**: Edit `home.html`, `reserve.html`, and `admin.dashboard.html`
2. **Colors**: Modify CSS variables in `style/style.css`
3. **Contact Information**: Update in `home.html` contact section

### Adding New Routes

1. Go to Admin Dashboard
2. Select "Schedule List"
3. Click "Add"
4. Enter trip details (ensure bus exists first)

## 📱 Features in Detail

### Ticket Printing

Customers can print their reservation tickets with:

- QR-style barcode
- Trip details
- Passenger information
- Professional ticket layout

### Search & Filtering

Advanced filtering options:

- **Date Filter**: Select specific travel dates
- **Origin Filter**: Filter by departure location
- **Destination Filter**: Filter by arrival location
- **Text Search**: Search across all trip details

### Seat Selection

Interactive seat selection:

- Visual representation of available seats
- Real-time updates
- Clear indication of reserved seats

## 🔐 Security Features

- Password hashing using PHP's `password_hash()`
- SQL injection prevention via prepared statements
- Input validation on both client and server side
- Session management for user authentication

## 🐛 Troubleshooting

### Database Connection Issues

**Problem**: "Connection failed" error

**Solution**:

1. Verify MySQL is running
2. Check database credentials in `Connection.php`
3. Ensure database `bus_ticket` exists

### 404 Errors on API Calls

**Problem**: AJAX requests failing

**Solution**:

1. Verify `BusReserve` folder is in correct location
2. Check Apache is running
3. Update API URLs in JavaScript files

### Seats Not Updating

**Problem**: Seat availability not reflecting reservations

**Solution**:

1. Clear browser cache
2. Check database for seat updates
3. Verify AJAX calls are successful in browser console

## 📊 Database Schema

### Tables

1. **login** - User accounts
2. **buses** - Bus fleet information
3. **schedule** - Trip schedules
4. **reservations** - Customer bookings

## 🚀 Future Enhancements

Potential improvements:

- [ ] Email notifications for bookings
- [ ] Payment gateway integration
- [ ] SMS notifications
- [ ] Customer reviews and ratings
- [ ] Loyalty program
- [ ] Multi-language support
- [ ] Real-time bus tracking

<Question ID="1" Shortcut="Q1" Order="" ElementType="question" QuestionType="numeric" Anonymity="1" AllowDK="1" Decimals="0" Translated="0" >
<LongCaption>**Version**: 0</LongCaption>
<Routings>
</Routings>
</Question>
