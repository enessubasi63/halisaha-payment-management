```markdown
# Halisaha Payment Management System


## Table of Contents

- [Introduction](#introduction)
- [Features](#features)
- [Live Demo](#live-demo)
- [Technology Stack](#technology-stack)
- [Installation](#installation)
- [Configuration](#configuration)
- [Database Setup](#database-setup)
- [Usage](#usage)
- [Screenshots](#screenshots)
- [API Documentation](#api-documentation)
- [Contributing](#contributing)
- [License](#license)
- [Contact](#contact)
- [Acknowledgements](#acknowledgements)

## Introduction

The **Halisaha Payment Management System** is a sophisticated web-based platform meticulously crafted to streamline the management of reservations and payments for sports facilities, specifically catering to soccer fields (Halisaha). This system integrates advanced functionalities to facilitate efficient handling of bookings, precise tracking of financial transactions, and an intuitive interface designed for both administrators and end-users. Leveraging robust technologies such as PHP, MySQL, and Bootstrap, the application ensures reliability, scalability, and a seamless user experience across a multitude of devices.

## Features

- **User Authentication & Authorization**
  - Secure login and registration for administrators and users.
  - Role-based access control to maintain data integrity and security.

- **Reservation Management**
  - Dynamic booking system allowing users to reserve fields based on real-time availability.
  - Comprehensive calendar view for effortless scheduling and management.
  - Detailed reservation information including date, time slots, and user details.

- **Payment Processing**
  - Support for multiple payment methods, including cash and bank transfers (IBAN).
  - Automated calculation of total fees, deposits (Kapora), and outstanding balances.
  - Detailed payment history with capabilities to add, edit, and review payments.

- **Responsive Design**
  - Mobile-optimized interface ensuring accessibility across smartphones, tablets, and desktops.
  - Enhanced table views tailored for both desktop and mobile users, augmenting readability and usability.

- **Reporting & Analytics**
  - In-depth reports on reservations and payments.
  - Export functionality for comprehensive data analysis and record-keeping.

- **Print Functionality**
  - Printable views of reservations and payment records for offline usage.

- **Error Handling & Validation**
  - Robust validation mechanisms ensuring data accuracy.
  - User-friendly error messages guiding users through corrective actions.

- **Live Demo Access**
  - A fully functional demo environment for prospective users to explore and interact with the system.

## Live Demo

Experience the **Halisaha Payment Management System** firsthand through our live demo. This environment allows you to navigate the application's features, manage reservations, and process payments without the need for a local setup.

- **Demo URL:** [https://halisaha.enessubasi.com.tr/](https://halisaha.enessubasi.com.tr/)
- **Username:** `admin`
- **Password:** `123456`


## Technology Stack

- **Frontend:**
  - [HTML5](https://developer.mozilla.org/en-US/docs/Web/HTML)
  - [CSS3](https://developer.mozilla.org/en-US/docs/Web/CSS)
  - [Bootstrap 5](https://getbootstrap.com/) for responsive design and styling
  - [JavaScript](https://developer.mozilla.org/en-US/docs/Web/JavaScript) for interactive elements

- **Backend:**
  - [PHP 8](https://www.php.net/) for server-side scripting
  - [MySQL](https://www.mysql.com/) for database management
  - [PDO](https://www.php.net/manual/en/book.pdo.php) for secure database interactions

- **Version Control:**
  - [Git](https://git-scm.com/) for tracking changes and collaboration
  - [GitHub](https://github.com/) for repository hosting and project management

## Installation

### Prerequisites

- **Web Server:** Apache, Nginx, or any compatible server with PHP support.
- **PHP:** Version 8.0 or higher.
- **MySQL:** Version 5.7 or higher.
- **Composer:** For managing PHP dependencies (optional but recommended).

### Steps

1. **Clone the Repository:**

   ```bash
   git clone https://github.com/yourusername/halisaha-payment-management.git

```
### Steps

1. **Clone the Repository:**

   ```bash
   git clone https://github.com/enessubasi63/halisaha-payment-management.git
   ```

2. **Navigate to the Project Directory:**

   ```bash
   cd halisaha-payment-management
   ```

3. **Install Dependencies:**

   If your project uses Composer for dependency management, run:

   ```bash
   composer install
   ```

4. **Configure the Environment:**

   - Duplicate the `.env.example` file and rename it to `.env`.
   - Update the environment variables with your database credentials and other necessary configurations.

5. **Set Up Permissions:**

   Ensure that the web server has write permissions to the `storage` and `bootstrap/cache` directories.

   ```bash
   chmod -R 775 storage
   chmod -R 775 bootstrap/cache
   ```

6. **Generate Application Key:**

   If applicable, generate an application key to secure sessions and other encrypted data.

   ```bash
   php artisan key:generate
   ```

## Configuration

### Database Configuration

1. **Create a Database:**

   ```sql
   CREATE DATABASE halisaha_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   ```

2. **Import the Database Schema:**

   ```bash
   mysql -u yourusername -p halisaha_db < database/schema.sql
   ```

3. **Update Configuration Files:**

   Ensure that your `config/db.php` (or equivalent) file has the correct database credentials.

   ```php
   <?php
   // config/db.php

   return [
       'host' => 'localhost',
       'dbname' => 'halisaha_db',
       'user' => 'yourusername',
       'password' => 'yourpassword',
       'charset' => 'utf8mb4',
   ];
   ```
   
### Email Configuration (Optional)

If your application sends emails (e.g., reservation confirmations), configure the mail settings in your `.env` file.

```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=your_username
MAIL_PASSWORD=your_password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=example@halisaha.com
MAIL_FROM_NAME="${APP_NAME}"
```

## Database Setup

### Schema Overview

The database consists of the following primary tables:

- **users:** Stores user information including authentication details and roles.
- **sahalar (fields):** Contains details about each sports field, such as name and pricing.
- **seanslar (sessions):** Defines available time slots for each day.
- **rezervasyonlar (reservations):** Records all reservations made by users.
- **odeme (payments):** Tracks all payments associated with reservations.

### SQL Scripts

Ensure you have the SQL scripts ready to set up the necessary tables and relationships. Execute the following command to import the schema:

```bash
mysql -u yourusername -p halisaha_db < database/schema.sql
```

### Sample Data

Populate the database with sample data for testing purposes.

```bash
mysql -u yourusername -p halisaha_db < database/sample_data.sql
```

## Usage

### Accessing the Application

1. **Start the Web Server:**

   Ensure your web server is running and points to the project's `public` directory.

2. **Navigate to the Application URL:**

   Open your browser and go to `http://localhost/halisaha-payment-management`.

### User Workflow

1. **Registration & Login:**

   - Users can register and log in to manage their reservations.
   - Administrators have elevated privileges to manage fields, sessions, and oversee payments.

2. **Making a Reservation:**

   - Select the desired date and view available fields.
   - Choose a time slot and provide necessary details to book a field.

3. **Processing Payments:**

   - Navigate to the Payments section to view all reservations.
   - Select a reservation to add payments, view payment history, and track outstanding balances.

4. **Viewing Reports:**

   - Access various reports to analyze reservations and payment trends.

### Administrative Functions

- **Manage Fields:**
  - Add, edit, or deactivate sports fields.
  
- **Manage Sessions:**
  - Define available time slots for each day and field.
  
- **Oversee Payments:**
  - Monitor all payment transactions and ensure financial accuracy.

## Screenshots

### Dashboard

![Dashboard](https://halisaha.enessubasi.com.tr/ss/dashboard.png)

### Reservation Form

![Reservation Form](https://halisaha.enessubasi.com.tr/ss/path-to-reservation-form-screenshot.png)


## API Documentation

*(If applicable, include API endpoints and usage instructions)*

### Endpoints

- **GET** `/api/reservations`
  - Retrieves a list of all reservations.

- **POST** `/api/reservations`
  - Creates a new reservation.

- **GET** `/api/payments/{reservation_id}`
  - Retrieves payment history for a specific reservation.

- **POST** `/api/payments`
  - Processes a new payment for a reservation.

### Authentication

- **Bearer Token Authentication** is used to secure API endpoints.
- Obtain a token by logging in through the authentication endpoint.

### Request & Response Formats

All API requests and responses are in **JSON** format.

#### Example: Creating a Reservation

**Request:**

```json
POST /api/reservations
Content-Type: application/json

{
    "user_id": 1,
    "field_id": 2,
    "date": "2024-12-20",
    "session_id": 3,
    "ad_soyad": "John Doe",
    "telefon": "1234567890",
    "kapora": 100.00
}
```

**Response:**

```json
{
    "success": true,
    "message": "Reservation created successfully.",
    "reservation_id": 15
}
```

## Contributing

Contributions are welcome! To contribute to this project, please follow the guidelines below:

### **1. Fork the Repository**

Click the "Fork" button at the top-right corner of the repository page to create a personal copy.

### **2. Clone the Forked Repository**

```bash
git clone https://github.com/enessubasi63/halisaha-payment-management.git
```

### **3. Create a Feature Branch**

```bash
git checkout -b feature/your-feature-name
```

### **4. Commit Your Changes**

Ensure your commits are descriptive and follow the commit message conventions.

```bash
git commit -m "Add feature: Detailed reservation view"
```

### **5. Push to the Branch**

```bash
git push origin feature/your-feature-name
```

### **6. Open a Pull Request**

Navigate to the original repository and click "Compare & pull request." Provide a clear description of your changes.

### **Code of Conduct**

Please adhere to the [Code of Conduct](CODE_OF_CONDUCT.md) in all your interactions with the project.

## License

This project is licensed under the [MIT License](LICENSE.md). You are free to use, modify, and distribute this software as long as you include the original license and copyright.

## Contact

For any inquiries, suggestions, or support, please reach out to us:

- **Project Lead:** John Doe
- **Email:** johndoe@example.com
- **LinkedIn:** [linkedin.com/in/johndoe](https://www.linkedin.com/in/johndoe/)
- **Twitter:** [@johndoe](https://twitter.com/johndoe)

## Acknowledgements

- **Bootstrap:** For providing a powerful and flexible frontend framework.
- **PHP Community:** For the robust server-side scripting support.
- **MySQL:** For reliable and efficient database management.
- **All Contributors:** Special thanks to everyone who has contributed to the development and improvement of this project.

---
