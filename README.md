# Grocery-Chain-Store
سامانه مدیریت فروشگاه زنجیره ای

معرفی

سامانه فروشگاه زنجیره‌ای یک سیستم مدیریت جامع برای فروشگاه‌های زنجیره‌ای کوچک است که با استفاده از PHP و MySQL توسعه یافته است. این سیستم امکان مدیریت کاربران، شعب، کالاها، فروش، مرجوعی و گزارشات را فراهم می‌کند. 

ویژگی‌ها 


     مدیریت کاربران: ثبت و مدیریت کاربران با نقش‌های مختلف (مدیر، حسابدار، فروشنده)
     مدیریت شعب: مدیریت سه شعبه فعال
     مدیریت کالاها: ثبت و مدیریت کالاهای با جزئیات کامل (کد، نام، رنگ، قیمت، موجودی و ...)
     سیستم فروش: امکان فروش چندین کالا به مشتریان با روش‌های مختلف پرداخت
     سیستم مرجوعی: مدیریت مرجوعی کالاها با ثبت دلیل و توضیحات
     گزارشات: گزارشات فروش، مرجوعی و سایر گزارشات مورد نیاز
     داشبورد: نمایش آماری و نموداری فروش
     

نصب و راه‌اندازی 
**
پیش‌نیازها

    PHP 7.4 یا بالاتر
    MySQL 5.7 یا بالاتر
     وب سرور (Apache, Nginx, etc.)
    Composer (برای مدیریت وابستگی‌ها)
     استفاده از مرورگرهایی همچون  Chrome, Firefox, Edge (آخرین نسخه) 
     پرینتر حرارتی ۵۸ یا ۸۰ میلی‌متری (اختیاری) 

مراحل نصب

کلون کردن ریپازیتوری:

git clone (https://github.com/spg2182/grocery-chain-store.git)
cd grocery-chain-store

نصب وابستگی‌ها:

composer install

ایجاد دیتابیس:

    یک دیتابیس جدید در MySQL ایجاد کنید
    فایل grocery_chain.sql را در دیتابیس خود ایمپورت کنید

تنظیمات اتصال به دیتابیس:

    فایل includes/db.php را باز کرده و اطلاعات اتصال به دیتابیس را وارد کنید:

$host = 'localhost';
$db   = 'grocery_chain';
$user = 'root';
$pass = '';

دسترسی به سیستم:

    پروژه را در مرورگر خود باز کنید
    برای ورود به بخش مدیریت، از حساب کاربری با سطح دسترسی "admin" استفاده کنید با رمز 123456
    برای ورود با سایر کابران از نام کاربری موجود در سامانه و رمز 123 استفاده کنید

سطوح دسترسی:

    مدیر برای کل شعب، حسابدار و فروشنده برای هر شعبه
    
نکات امنیتی 

     استفاده از Prepared Statements برای جلوگیری از SQL Injection
     رمزنگاری پسوردها با استفاده از password_hash() و password_verify()
     مدیریت sessions با استفاده از session_start()
     اعتبارسنجی ورودی‌ها قبل از پردازش
     

مشارکت در توسعه

ما از مشارکت‌های شما استقبال می‌کنیم! اگر می‌خواهید در توسعه این پروژه مشارکت کنید:

    ریپازیتوری را فورک کنید
    یک شاخه (branch) جدید برای ویژگی خود ایجاد کنید
    تغییرات خود را اعمال کنید
    کامیت کنید (git commit -m 'Add some feature')
    به شاخه اصلی خود push کنید (git push origin feature-branch)
    یک Pull Request ایجاد کنید

لایسنس

این پروژه تحت لایسنس GPLv3 منتشر شده است. برای اطلاعات بیشتر، فایل LICENSE را مطالعه کنید.


# Grocery-Chain-Store
**Chain Store Management System**

Introduction

The Chain Store Management System is a comprehensive management solution for small retail chains, developed using **PHP** and **MySQL**. The system supports user, branch, product, sales, returns, and reporting management.

**Features**

- User management: register and manage users with different roles (admin, accountant, salesperson)  
- Branch management: manage three active branches  
- Product management: create and manage products with full details (code, name, color, price, stock, etc.)  
- Sales system: sell multiple products to customers with various payment methods  
- Returns system: handle product returns with reason and notes  
- Reports: sales, returns, and other required reports  
- Dashboard: statistical and graphical sales overview

**Installation and Setup**

Prerequisites

- PHP 7.4 or higher  
- MySQL 5.7 or higher  
- Web server (Apache, Nginx, etc.)  
- Composer (for dependency management)  
- Recommended browsers: Chrome, Firefox, Edge (latest versions)  
- Thermal printer 58mm or 80mm (optional)

Installation steps

Clone the repository:

```bash
git clone https://github.com/spg2182/grocery-chain-store.git
cd grocery-chain-store
```

Install dependencies:

```bash
composer install
```

Create the database:

- Create a new MySQL database  
- Import the file grocery_chain.sql into your database

Database connection settings:

- Open includes/db.php and enter your database connection details:

```php
$host = 'localhost';
$db   = 'grocery_chain';
$user = 'root';
$pass = '';
```

Accessing the system:

- Open the project in your browser  
- To access the admin area, use the admin account with password **123456**  
- To log in as other users, use existing usernames in the system with password **123**

Access levels:

- Admin for all branches; accountant and salesperson per branch

Security notes

- Use prepared statements to prevent SQL injection  
- Hash passwords using password_hash() and verify with password_verify()  
- Manage sessions using session_start()  
- Validate inputs before processing

Contributing

Contributions are welcome! To contribute:

1. Fork the repository  
2. Create a new branch for your feature  
3. Make your changes  
4. Commit (git commit -m "Add some feature")  
5. Push to your branch (git push origin feature-branch)  
6. Open a Pull Request

License
This project is released under **GPLv3**. See the LICENSE file for details.
