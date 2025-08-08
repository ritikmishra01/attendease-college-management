# 📚 AttendEase

**AttendEase** is a full-stack web application designed to simplify **attendance tracking**, **marks management**, and **course material sharing** for colleges.  
Built using **PHP**, **MySQL**, **JavaScript**, and **HTML/CSS**, the system provides **role-based dashboards** for **Admin**, **Teacher**, and **Student**.

---

## 🚀 Features

### 🔐 Role-Based Dashboards

#### 👤 Admin
- Add and manage **students** and **teachers**
- Assign usernames, passwords,class, divisions, and subjects
- View, edit, and delete user accounts

#### 👨‍🏫 Teacher
- Generate **attendance session codes** and **QR links**
- Mark student attendance with **location validation**
- Upload, edit, and delete **study materials** (notes, assignments) by subject and division
- View and manage **student marks** by subject and exam type

#### 👨‍🎓 Student
- Mark attendance via shared **QR/code link** with real-time **location check**
- View **attendance reports** and **exam marks**
- Access and download **course materials** by subject and division

---

## 📍 Location-Based Attendance
- Uses **HTML5 Geolocation API** to fetch student's latitude & longitude
- Applies **Haversine formula** in PHP to calculate distance between student and teacher
- Attendance allowed **only** if the student is within the specified range (e.g., 100 meters)
- Blocks attendance after multiple failed attempts from different devices or outside the location

---

## 📁 Course Material Sharing
- Teachers can upload files (PDFs, notes, assignments, etc.)
- Materials are tagged by **subject** and **division**
- Students can view/download only materials for their division and subject
- Secure file uploads with **type & size validation**

---

## 🔐 Security Features
- Passwords hashed using `password_hash()` (**Bcrypt**)
- Role-based access control via **PHP sessions**
- File uploads validated to prevent abuse
- Session validation using:
  - `redirectIfNotLoggedIn()`
  - `redirectIfNotRole()`

---

## 🛠️ Tech Stack

| Frontend      | Backend | Database | APIs                 |
|---------------|---------|----------|----------------------|
| HTML, CSS, JS | PHP     | MySQL    | HTML5 Geolocation API|

---

👨‍💻 Author
Ritik Mishra
B.E. Student | Web Developer
📧 ritikskmishra01@gmail.com

📄 License
This project is for educational use.
You are free to modify and use it with proper credits.
