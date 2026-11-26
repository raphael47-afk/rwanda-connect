RWANDA CONNECT - STARTER PACKAGE
================================

WHAT IS INCLUDED
----------------
- index.php: the first project homepage
- config/database.php: reusable PDO database connection
- database/rwanda_connect.sql: starter database and tables
- assets/css/style.css: responsive page styling
- assets/js/script.js: JavaScript starter file

REQUIREMENTS
------------
- XAMPP with Apache, PHP and MySQL
- A web browser
- A code editor (Visual Studio Code is recommended)

INSTALLATION
------------
1. Extract the ZIP archive.
2. Copy the folder named "rwanda_connect" into:
   C:\xampp\htdocs\
3. Open XAMPP Control Panel.
4. Start Apache and MySQL.
5. Open phpMyAdmin:
   http://localhost/phpmyadmin
6. Select the Import tab.
7. Choose database/rwanda_connect.sql from this project folder.
8. Click Import / Go.
9. Open the homepage:
   http://localhost/rwanda_connect/

DATABASE CONNECTION
-------------------
The default config in config/database.php uses:
- Host: 127.0.0.1
- Database: rwanda_connect_db
- Username: root
- Password: blank

These are common XAMPP defaults. If your MySQL account uses a password
or a different username, edit config/database.php.

IMPORTANT
---------
The homepage is a starter demonstration. Registration, login, admin tools,
service listings and reporting workflows have not been implemented yet.
Do not publish this starter online or use it to collect real citizens'
personal information. We will add and test security features before
implementing accounts or collecting data.

This is an independent educational student project, not an official
Government of Rwanda website or service.
