# Laravel Setup Instructions (Windows)

Here's a quick guide to setting up a Laravel project on Windows.

## 1.  Install PHP

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

## 2.  Install Composer

* Download and run the Composer installer from <https://getcomposer.org/>.
* Ensure Composer is added to your PATH during installation.
* Restart your terminal.
* Check installation: `composer -v`
