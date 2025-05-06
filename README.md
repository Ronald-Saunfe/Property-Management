# Property Management System

A comprehensive property management application built with Laravel, designed to help property managers and landlords efficiently manage their properties, tenants, leases, and payments.

## Setup Instructions

### Traditional Setup (Windows)

#### 1. Install PHP

* Download the latest PHP non-thread-safe (NTS) zip package from <https://windows.php.net/download/>.
* Extract to a directory (e.g., `C:\php`).
* **Install 7-Zip:** Download and install 7-Zip from <https://www.7-zip.org/>.
* Set up Environment Variables:
    * Create a `PHP_HOME` system variable with your PHP path (e.g., `C:\php`).
    * Add `%PHP_HOME%` to the `Path` system variable.
    * Add `C:\php\ext` to the `Path` variable
    * Add the 7-Zip installation directory (e.g., `C:\Program Files\7-Zip`) to the `Path` variable.
* Enable the zip extension by removing the semicolon (`;`) from `;extension=zip` in your `php.ini` file.
* **Restart your computer.**

#### 2. Install Composer

* Download and run the Composer installer from <https://getcomposer.org/>.
* Ensure Composer is added to your PATH during installation.
* Restart your terminal.
* Check installation: `composer -v`

### Docker Setup (Recommended)

For a simpler setup using Docker, please refer to our [Docker Setup Guide](DOCKER-SETUP.md).

## Documentation

### Architecture & Design

* [RBAC Documentation](RBAC-DOCUMENTATION.md) - Role-Based Access Control implementation
* [API Documentation](SWAGGER-DOCUMENTATION.md) - API endpoints and usage
* [Scaling Strategies](SCALING-STRATEGIES.md) - Horizontal scaling approaches

### Performance & Optimization

* [Performance Profiling Report](PERFORMANCE-PROFILING-REPORT.md) - Analysis of application performance
* [Profiling Documentation](PROFILING-DOCUMENTATION.md) - How to profile the application
* [Debugbar Usage Guide](DEBUGBAR-USAGE-GUIDE.md) - Using Laravel Debugbar for profiling
* [Performance Monitoring Documentation](PERFORMANCE-MONITORING-DOCUMENTATION.md) - Monitoring system

### Security

* [Rate Limiting Documentation](RATE-LIMITING-DOCUMENTATION.md) - API rate limiting implementation

## Project Submission

* [Submission Checklist](SUBMISSION-CHECKLIST.md) - Verify all requirements before submission
