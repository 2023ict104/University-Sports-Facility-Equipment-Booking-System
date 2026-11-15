# University Sports Equipment Booking Management System

**Group No:** 13  
**Department:** Department of Physical Science  
**Faculty:** Faculty of Applied Science  
**University:** University of Vavuniya  

## Project Overview

The **University Sports Equipment Booking Management System** is a centralized, role-based web application developed using **PHP** and **MySQL** to digitize and streamline the process of borrowing, issuing, and returning sports equipment within the university.

In a traditional paper-based workflow, students and university staff must manually contact sports officers and record borrowings in physical logbooks—leading to misplaced records, inaccurate stock counts, and difficulty tracking overdue returns. This system provides a unified web platform where students and staff can check real-time equipment availability and submit borrowing requests, while administrators manage inventory, review requests, hand over equipment, and record partial or full returns.

## Team Members

| Registration No. | Student Name | Role |
| :--- | :--- | :--- |
| `2023/ICT/104` | Mr. Muhammad A.J | Team Leader (TL) |
| `2023/ICT/22` | Ms. Ishara D.L.T. | Member |
| `2023/ICT/58` | Ms. Perera B.B.A.K.K | Member |
| `2023/ICT/60` | Ms. Diwyangie J.A.M. | Member |
| `2023/ICT/74` | Ms. Mallawaarachchi T.M.G.S.V. | Member |
| `2023/ICT/91` | Ms. Infa S.M.F. | Member |

## Key Features

### Student & University Staff Portal
- **Role-Based Authentication:** Dedicated login portal (`login.php`) restricted strictly to active Student and Staff accounts.
- **Interactive Dashboard:** Real-time summary of personal equipment requests (`Pending`, `Approved`, `Completed`) alongside live stock availability.
- **Equipment Directory:** Browse active sports equipment across categories with condition statuses (`New`, `Good`, `Fair`, `Needs Repair`) and available vs. total stock counts.
- **Borrowing Request Submission:** Request available equipment with client-side and server-side quantity validation and expected return date selection.
- **Request & Borrowing Tracker:** Track administrator review notes, approval statuses, issued quantities, partial returns, and due dates (`Overdue` alerts).
- **Password Management:** Self-service password update (`change_password.php`) with current password verification and bcrypt hashing.

### Administrator Portal (Sports Unit)
- **Dedicated Admin Authentication:** Role-enforced administrator sign-in (`admin/login.php`).
- **Executive Dashboard:** Live metrics for available/total inventory, pending approvals, issued units, and overdue borrowings.
- **User Account Management:** Create Student (`add_student.php`) and Staff (`add_staff.php`) accounts, edit user details/roles/passwords (`edit_user.php`), toggle `active`/`inactive` status, and safely delete users who hold no unreturned gear.
- **Category & Inventory Management:** Full CRUD (`Create`, `Read`, `Update`, `Delete`, and `Activate`/`Deactivate`) for equipment categories (`manage_categories.php`, `edit_category.php`) and sports equipment (`manage_equipment.php`, `edit_equipment.php`) with foreign-key safety checks.
- **Request Review Workflow:** Approve or reject pending student/staff requests with custom review notes (`manage_requests.php`).
- **Equipment Issuing (`equipment_issues`):** Hand over approved equipment (`issue_equipment.php`), automatically deducting from `available_quantity` and preventing over-issuing beyond available stock.
- **Full & Partial Return Recording (`equipment_returns`):** Record returned items in portions or in full (`record_returns.php`), automatically restoring `available_quantity`, updating equipment condition, and marking the request as `completed` once all issued units are returned.
- **Complete Borrowing & Overdue Ledger:** Filter and audit all historical, active, and overdue borrowings (`borrowing_records.php`).

- ## Technology Stack

| Layer | Technology | Purpose |
| :--- | :--- | :--- |
| **Frontend** | HTML5, CSS3, JavaScript (ES6) | Responsive UI layout, modern athletic styling, and form validation |
| **Backend** | PHP 8.x (PDO) | Modular server-side business logic, session management, and prepared statements |
| **Database** | MySQL / MariaDB (`InnoDB`) | Relational storage (`sports_equipment_db`) with foreign keys and ACID transactions |
| **Environment** | XAMPP | Local Apache + MySQL development server |

## Installation & Setup Guide (XAMPP)

1. **Clone or Extract the Project**
   - Copy the `sports_equipment_system` folder into your XAMPP `htdocs` directory:
     ```text
     C:\xampp\htdocs\sports_equipment_system\
     ```
   - *(Note: The application uses dynamic base-path detection in `includes/auth.php`, so it also works if your folder is named differently inside `htdocs`).*

2. **Start Apache & MySQL**
   - Open the **XAMPP Control Panel** and click **Start** next to both **Apache** and **MySQL**.

3. **Import the Database**
   - Open `http://localhost/phpmyadmin` in your browser.
   - Click the **Import** tab at the top, choose `database/sports_equipment_db.sql`, and click **Import** (or **Go**).
   - This automatically creates the `sports_equipment_db` database, all 6 tables, foreign keys, and sample records.

4. **Configure Database Credentials (Optional)**
   - By default, `config/db.php` connects using standard XAMPP settings (`host: localhost`, `user: root`, `password: ""`). Update `config/db.php` only if your local MySQL root user has a custom password.

5. **Launch the Application**
   - **Student & Staff Portal:** `http://localhost/sports_equipment_system/login.php`
   - **Administrator Portal:** `http://localhost/sports_equipment_system/admin/login.php`


## Demo Login Credentials

All pre-seeded accounts use the default password: **`password123`**

| Role | Name / Registration No. | Email Address | Password | Portal |
| :--- | :--- | :--- | :--- | :--- |
| **Administrator** | Navaratnam (Sports Officer) | `admin@gmail.com` | `password123` | `admin/login.php` |
| **Student** | Kamal (`2023/ICT/001`) | `kamal@gmail.com` | `password123` | `login.php` |
| **Staff** | Sivakumar (Senior Lecturer) | `sivakumar@gmail.com` | `password123` | `login.php` |
