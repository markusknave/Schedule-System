# 📚 Schedule Load System

<div align="center">

![PHP Version](https://img.shields.io/badge/PHP-7.4%2B-blue)
![MySQL Version](https://img.shields.io/badge/MySQL-8.0%2B-orange)
![License](https://img.shields.io/badge/License-MIT-green)

A modern web-based scheduling system for educational institutions to efficiently manage teacher schedules, class assignments, and administrative tasks.

[Features](#-features) • [Installation](#-installation) • [Documentation](#-documentation) • [Contributing](#-contributing)

</div>

## ✨ Features

### 👥 User Management
- **Role-Based Access**
  - Admin dashboard with full system control
  - Teacher portal for schedule and profile management
  - Secure authentication with password hashing
- **Profile System**
  - Profile image upload
  - Personal information management
  - Leave management tracking
- **Default Password Format for Professors**
  - For newly added professors, the default password is in the format `SURNAMEYYYYDDMM`, where `SURNAME` is the professor's last name in uppercase and `YYYYDDMM` is the date the account was originally created (Year, Day, Month).


### 📅 Schedule Management
- **Smart Scheduling**
  - Create and manage class schedules
  - Automatic conflict detection
  - Room availability checking
  - Teacher availability verification
- **Resource Allocation**
  - Room assignment
  - Section management
  - Time slot scheduling
  - Subject assignment

### 🏢 Office Management
- **Multi-Office Support**
  - Independent office configurations
  - Office-specific resources
  - Customizable settings
- **Resource Control**
  - Office-specific announcements
  - Room management
  - Subject management
  - Section management

### 📢 Communication
- **Announcement System**
  - Rich text editor
  - Image attachments
  - Priority levels
  - Scheduled posts
- **Complaint Management**
  - Teacher complaint submission
  - Status tracking
  - Email notifications
  - Resolution management

## 🚀 Installation

### Prerequisites
- PHP 7.4 or higher
- MySQL 8.0 or higher
- Web server (Apache/Nginx)
- Required PHP extensions:
  - PDO
  - MySQLi
  - GD Library
  - FileInfo
  - JSON
  - Session

### Setup Steps

1. **Clone the Repository**
   ```bash
   git clone [repository-url]
   cd myschedule
   ```

2. **Database Configuration**
   ```bash
   # Create database
   mysql -u root -p
   CREATE DATABASE sched_load_system;
   
   # Import schema
   mysql -u root -p sched_load_system < sched_load_system.sql
   ```

3. **Environment Setup**
   ```php
   // config.php
   $host = "localhost";
   $username = "your_username";
   $password = "your_password";
   $database = "sched_load_system";
   ```

4. **File Permissions**
   ```bash
   chmod 755 -R /path/to/myschedule
   chmod 777 -R /path/to/myschedule/uploads
   ```

5. **Web Server Configuration**
   - Point your web server to the project directory
   - Enable mod_rewrite (Apache)
   - Configure virtual host (recommended)

## 🔒 Security Features

- **Authentication**
  - Password hashing
  - Session management
  - CSRF protection
  - XSS prevention

- **Data Protection**
  - Input validation
  - SQL injection prevention
  - Secure file handling
  - Rate limiting

- **System Security**
  - Error logging
  - Access monitoring
  - IP-based security
  - Session timeout

## 🤝 Contributing

We welcome contributions! Please follow these steps:

1. **Fork the Repository**
   ```bash
   git clone [your-fork-url]
   cd myschedule
   git checkout -b feature/YourFeature
   ```

2. **Development Guidelines**
   - Follow PSR-12 standards
   - Write clear commit messages
   - Add comments for complex logic
   - Update documentation

3. **Testing**
   - Test thoroughly
   - Ensure compatibility
   - Check security

4. **Pull Request**
   - Detailed description
   - Reference issues
   - Include tests

## 📝 License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## 💬 Support

- **Issues**: [GitHub Issues](issues-link)

- **Contact**: [Development Team](seandavenn@gmail.com)


---

<div align="center">

Made with ❤️ by the Schedule Load System Team

</div> 
