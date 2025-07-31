# Interior Design & Contractor Project Management System

[![PHP](https://img.shields.io/badge/PHP-8.x-777BB4?style=flat&logo=php&logoColor=white)](https://php.net/)
[![MySQL](https://img.shields.io/badge/MySQL-8.0+-4479A1?style=flat&logo=mysql&logoColor=white)](https://mysql.com/)
[![Bootstrap](https://img.shields.io/badge/Bootstrap-5.3-7952B3?style=flat&logo=bootstrap&logoColor=white)](https://getbootstrap.com/)
[![License](https://img.shields.io/badge/License-MIT-green.svg)](LICENSE)

## 🏗️ Project Overview

A comprehensive role-based project management system specifically designed for interior designers and contractors. This web application provides different access levels and functionalities for various roles in the organization.

## 👥 User Roles

### 🔴 Admin

- **Full System Access**
- User management (create, edit, delete users)
- Complete project oversight
- System settings and configuration
- Advanced reporting and analytics
- Role and permission management

### 🔵 Manager

- **Project Management**
- Team coordination and task assignment
- Budget and timeline oversight
- Client communication
- Progress monitoring
- Resource allocation

### 🟢 Designer

- **Creative Control**
- Design creation and modification
- Project visualization
- Material selection
- Design documentation
- Client presentation materials

### 🟡 Site Manager

- **On-site Operations**
- Inventory management
- Quality control
- Site safety compliance
- Vendor coordination
- Progress reporting

### 🟠 Site Coordinator

- **Scheduling & Coordination**
- Task scheduling
- Resource coordination
- Team communication
- Progress tracking
- Status updates

### 🟣 Site Supervisor

- **Field Operations**
- Daily attendance tracking
- Task completion monitoring
- Field reporting
- Quality checks
- Safety compliance

## ✨ Key Features

### 🔐 Authentication & Security

- **Secure Login System** with password hashing
- **Role-Based Access Control (RBAC)**
- **Session Management** with timeout
- **Permission-based Navigation**
- **Password Recovery System**

### 📊 Dashboard Analytics

- **Role-specific Dashboards**
- **Real-time Project Statistics**
- **Progress Tracking Charts**
- **Budget Analysis**
- **Task Management Overview**

### 🏢 Project Management

- **Complete Project Lifecycle**
- **Client Information Management**
- **Budget and Timeline Tracking**
- **Status and Progress Monitoring**
- **Team Assignment and Coordination**

### 📋 Task Management

- **Task Creation and Assignment**
- **Priority-based Organization**
- **Deadline Tracking**
- **Progress Updates**
- **Completion Monitoring**

### 📈 Reporting System

- **Daily, Weekly, Monthly Reports**
- **Project Progress Reports**
- **Budget Analysis Reports**
- **Team Performance Reports**
- **Custom Report Generation**

### 💼 Specialized Modules

#### For Designers:

- Design portfolio management
- Material library
- Client presentation tools
- Design version control

#### For Site Managers:

- Inventory tracking
- Vendor management
- Quality control checklists
- Safety compliance monitoring

#### For Coordinators:

- Scheduling calendar
- Resource allocation
- Communication hub
- Status dashboard

#### For Supervisors:

- Attendance management
- Daily work reports
- Quality inspection forms
- Safety incident reporting

## 🛠️ Technical Stack

- **Backend**: PHP 8.x with PDO
- **Database**: MySQL 8.0+
- **Frontend**: HTML5, CSS3, JavaScript
- **Framework**: Bootstrap 5.3
- **Icons**: Font Awesome 6.0
- **Authentication**: Session-based with password hashing
- **Security**: SQL injection prevention, XSS protection

## 📁 Project Structure

interior-project-management/
├── config/ # Configuration files
│ ├── database.php # Database connection
│ ├── auth.php # Authentication system
│ └── roles.php # Role permissions
├── includes/ # Common includes
│ ├── header.php # Common header
│ ├── sidebar.php # Navigation sidebar
│ ├── footer.php # Common footer
│ └── functions.php # Utility functions
├── auth/ # Authentication pages
│ ├── login.php # Login form
│ ├── register.php # User registration
│ ├── logout.php # Logout handler
│ └── forgot-password.php # Password recovery
├── dashboard/ # Role-specific dashboards
│ ├── admin.php # Admin dashboard
│ ├── manager.php # Manager dashboard
│ ├── designer.php # Designer dashboard
│ ├── site-manager.php # Site manager dashboard
│ ├── site-coordinator.php # Coordinator dashboard
│ └── site-supervisor.php # Supervisor dashboard
├── modules/ # Feature modules
│ ├── projects/ # Project management
│ ├── users/ # User management
│ ├── tasks/ # Task management
│ ├── reports/ # Reporting system
│ ├── designs/ # Design management
│ ├── inventory/ # Inventory tracking
│ ├── schedule/ # Scheduling system
│ └── attendance/ # Attendance tracking
├── assets/ # Static assets
│ ├── css/ # Stylesheets
│ ├── js/ # JavaScript files
│ └── img/ # Images
├── sql/ # Database files
│ └── database.sql # Database schema
└── README.md # Documentation

## 🚀 Installation Guide

### Prerequisites

- **Web Server** (Apache/Nginx)
- **PHP 8.0+** with PDO extension
- **MySQL 8.0+** or MariaDB
- **Modern Web Browser**

### Step-by-Step Installation

1. **Clone/Download the Repository**

git clone https://github.com/yourusername/interior-project-management.git
cd interior-project-management

2. **Create Database**

CREATE DATABASE interior_project_management;

3. **Import Database Schema**

mysql -u root -p interior_project_management < sql/database.sql

4. **Configure Database Connection**

// config/database.php
private $host = 'localhost';
private $db_name = 'interior_project_management';
private $username = 'your_username';
private $password = 'your_password';

5. **Set File Permissions**

chmod 755 assets/
chmod 644 config/database.php

6. **Access the Application**

- Navigate to `http://your-domain/interior-project-management/auth/login.php`
- Default admin credentials:
  - Email: `admin@interior.com`
  - Password: `password` (change immediately)

## 🔧 Configuration

### Role Permissions

Modify `config/roles.php` to customize permissions for each role:

$role_permissions = [
'admin' => ['users' => ['create', 'read', 'update', 'delete']],
'manager' => ['projects' => ['create', 'read', 'update']],
// ... more roles
];

### Theme Customization

Update `assets/css/custom.css` for visual customization:

:root {
--primary-color: #007bff;
--secondary-color: #6c757d;
/_ ... more variables _/
}

## 📱 Usage Guide

### Getting Started

1. **Login** with your assigned credentials
2. **Navigate** using the role-specific sidebar
3. **Access** features based on your permissions
4. **Update Profile** from the user menu

### For Admins

1. **Manage Users**: Create accounts for team members
2. **System Settings**: Configure roles and permissions
3. **Monitor Projects**: Oversee all project activities
4. **Generate Reports**: Access comprehensive analytics

### For Managers

1. **Create Projects**: Set up new interior design projects
2. **Assign Teams**: Allocate resources and team members
3. **Track Progress**: Monitor project timelines and budgets
4. **Client Communication**: Manage client relationships

### For Designers

1. **Access Projects**: View assigned design projects
2. **Upload Designs**: Share design concepts and materials
3. **Track Feedback**: Manage client and team feedback
4. **Version Control**: Maintain design iterations

### For Site Staff

1. **Daily Updates**: Submit progress reports
2. **Resource Management**: Track materials and tools
3. **Quality Control**: Document quality checks
4. **Safety Compliance**: Report safety incidents

## 🔒 Security Features

- **Password Hashing** using PHP's password_hash()
- **SQL Injection Prevention** via prepared statements
- **XSS Protection** through input sanitization
- **Session Security** with timeout and validation
- **Role-based Access Control** for feature protection
- **CSRF Protection** on form submissions

## 📊 Database Schema

### Core Tables

- **users**: User accounts and roles
- **projects**: Project information and details
- **tasks**: Task management and assignments
- **project_materials**: Material tracking
- **project_reports**: Progress and status reports

### Relationships

- Users can be assigned to multiple projects
- Projects contain multiple tasks and materials
- Reports are linked to specific projects and users
- Role-based access controls data visibility

## 🤝 Contributing

1. **Fork** the repository
2. **Create** feature branch (`git checkout -b feature/AmazingFeature`)
3. **Commit** changes (`git commit -m 'Add AmazingFeature'`)
4. **Push** to branch (`git push origin feature/AmazingFeature`)
5. **Open** Pull Request

### Development Guidelines

- Follow PSR-12 coding standards
- Write meaningful commit messages
- Test all role-based permissions
- Update documentation for new features
- Ensure responsive design compatibility

## 📝 License

This project is licensed under the **MIT License** - see the [LICENSE](LICENSE) file for details.

## 👨💻 Support

For support and queries:

- **Create Issues** on GitHub
- **Check Documentation** in the wiki
- **Join Discussions** in the community section

## 🗺️ Roadmap

### Version 2.0 (Planned)

- [ ] Mobile application (React Native)
- [ ] Advanced reporting with charts
- [ ] Integration with accounting software
- [ ] Document management system
- [ ] Real-time notifications

### Version 1.5 (In Development)

- [ ] Enhanced file upload system
- [ ] Advanced search and filtering
- [ ] Email notification system
- [ ] Calendar integration
- [ ] Backup and restore functionality

## 🏆 Acknowledgments

- **Bootstrap Team** for the responsive framework
- **Font Awesome** for the icon library
- **PHP Community** for excellent documentation
- **MySQL Team** for the robust database system
- **Interior Design Industry** for valuable feedback

---

**Built with ❤️ for Interior Designers and Contractors**

# _Last Updated: January 2025_
