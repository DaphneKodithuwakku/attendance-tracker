#  Attendance Tracker - University of Deakin

A simple Laravel-based web tool to help teachers easily track and report student attendance at the University of Deakin.

---

##  What It Does

* Mark attendance using student IDs.
* View attendance reports with filters (date, subject, student).
* Fast performance, even with large datasets.
* Built to be extendable and customizable.

---

## Key Features

* Daily attendance marking form.
* Dashboard with search, and filters.
* Date filters (Today, Last 7 Days).
* Student-wise attendance percentage calculation.
* Built using Laravel and MySQL.

---

## How to Set It Up

### Requirements

* **XAMPP** (with PHP 8.0+)
* **Composer**
* **Browser** (Chrome, Firefox, Edge)

---

### Installation Steps

#### 1. Setup XAMPP

* Install [XAMPP](https://www.apachefriends.org/index.html).
* Start **Apache** and **MySQL** from the XAMPP Control Panel.

#### 2. Set Up Laravel Project

* Download or clone the Laravel project into:

  ```
  C:\xampp\htdocs\attendance-tracker
  ```

* Open terminal (Command Prompt or PowerShell), navigate to the project:

  ```bash
  cd C:\xampp\htdocs\attendance-tracker
  ```

* Install dependencies and generate app key:

  ```bash
  composer install
  php artisan key:generate
  ```

#### 3. Configure Environment File

* Open the `.env` file and update database settings:

  ```env
  DB_CONNECTION=mysql
  DB_HOST=127.0.0.1
  DB_PORT=3306
  DB_DATABASE=attendance_tracker
  DB_USERNAME=root
  DB_PASSWORD=
  ```

#### 4. Create Database

* Go to [http://localhost/phpmyadmin](http://localhost/phpmyadmin).
* Create a new database named:

  ```
  attendance_tracker
  ```

#### 5. Run Migrations

* In terminal, run:

  ```bash
  php artisan migrate
  ```

#### 6. Seed Sample Data

* Add subjects:

  ```bash
  php artisan db:seed --class=SubjectSeeder
  ```

* Add students:

  ```bash
  php artisan db:seed --class=StudentSeeder
  ```

* Add attendance records (optional):

  ```bash
  php artisan db:seed --class=AttendanceSeeder
  ```

---

## How to Use the App

###  Mark Attendance

* Visit: [http://localhost:8000/attendance/form](http://localhost:8000/attendance/form)
* Select:

  * **Subject**
  * **Teacher Name**
  * Click **"Load Students"**
* Mark students as:

  * ✅ Present
  * ❌ Absent
* Click **Save** to record attendance

### View Attendance Dashboard

* Visit: [http://localhost:8000/attendance/dashboard](http://localhost:8000/attendance/dashboard)
* Features:

  * Filter by date range or subject
  * Search by student name or ID
  * View attendance percentages
  * Export data (CSV or print view)

---

##  Sample Data Included

| Category   | Count   | Notes                           |
| ---------- | ------- | ------------------------------- |
| Students   | 18      | 6 each in Business, IT, Science |
| Subjects   | 15      | 5 per department                |
| Attendance | 2 weeks | Generated via seeder            |

---


## Technical Stack

* **Framework:** Laravel 10
* **Language:** PHP 8.1+
* **Database:** MySQL
* **Frontend:** js

---

##  Assumptions

* Focused on **first-year students**
* Each student is enrolled in **3 or more subjects**
* Built and tested on **XAMPP + Windows environment**
* Designed to be extended for future features**
*Departments are divided into 3 different cateogaries - IT, Business and Science**

---

##  Need Help?

If you get stuck:

* Double-check `.env` database values
* Use `php artisan migrate:fresh` to reset the database
* Restart Apache/MySQL from XAMPP if issues persist

---