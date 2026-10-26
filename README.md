# CarDeal: Web-Based Car Dealership Management System

> Built with **PHP 8 (OOP with Namespaces)**, **MySQL**, and modern **Vanilla CSS**.  
> Primary Palette: `#d62839` (Automotive Crimson) & `#0f1b2d` (Midnight Slate).

---

## 🚀 Quick Start Guide

### 1. Requirements
- **Web Server:** XAMPP (Apache + MySQL / MariaDB)
- **PHP Version:** PHP 8.0 or newer (tested on PHP 8.2)
- **Location:** `C:\xampp\htdocs\SampleFinalProject`
- **Access URL:** [http://localhost/SampleFinalProject/](http://localhost/SampleFinalProject/)

### 2. Database Initialization
The database configuration is located in `config/database.php`.
To automatically create the database `cardeal_db`, construct all tables, and seed realistic demo records:
- Open your browser to: [http://localhost/SampleFinalProject/setup.php](http://localhost/SampleFinalProject/setup.php)  
  *Or run via command line:* `php setup.php`

### 3. Default Login Credentials

| Role | Username | Password | Access Level |
|---|---|---|---|
| **Administrator** | `admin` | `admin123` | Full access, user accounts, record deletion |
| **Dealership Staff** | `staff1` | `staff123` | Inventory CRUD, Inquiries, Test-Drive Queue |
| **Dealership Staff** | `staff2` | `staff123` | Inventory CRUD, Inquiries, Test-Drive Queue |

---

## 🏛️ System Architecture & Object-Oriented Design

The system adheres strictly to Object-Oriented Programming (OOP) and PSR-4 compliant namespaces:

```
SampleFinalProject/
├── config/
│   └── database.php                # Database host, port, credentials configuration
├── src/
│   ├── Config/
│   │   └── Database.php            # Singleton PDO database connector
│   ├── Models/
│   │   ├── User.php                # User entity (Admin / Staff)
│   │   ├── Vehicle.php             # Vehicle inventory entity
│   │   ├── Inquiry.php             # Customer inquiry entity
│   │   └── TestDrive.php           # Test-drive request & appointment entity
│   ├── Algorithms/
│   │   ├── LinearSearch.php        # 5.1 Linear Search algorithm with metrics
│   │   └── MergeSort.php           # 5.2 Merge Sort divide-and-conquer implementation
│   ├── DataStructures/
│   │   ├── BSTNode.php             # Node for Binary Search Tree
│   │   ├── BinarySearchTree.php    # 5.3 Binary Search Tree (BST) indexed by Vehicle ID
│   │   ├── QueueNode.php           # Node for Linked-List Queue
│   │   └── Queue.php               # 5.4 First-In, First-Out (FIFO) Linked List Queue
│   ├── Services/
│   │   ├── AuthService.php         # Authentication & session authorization
│   │   ├── VehicleService.php      # Inventory management & DSA orchestration
│   │   ├── InquiryService.php      # Customer inquiries processing & statuses
│   │   ├── TestDriveService.php    # Test-drive queue workflow & FIFO dispatcher
│   │   └── UserService.php         # Administrator staff accounts management
│   └── Testing/
│       └── DSATestSuite.php        # Normal, Empty, Missing, Invalid automated test runner
├── public/
│   ├── assets/
│   │   ├── css/
│   │   │   └── style.css           # Custom design system (#d62839 & #0f1b2d)
│   │   └── js/
│   │       └── app.js              # Modals, clock widget, dynamic form population
├── templates/
│   ├── header.php                  # Global HTML head, fonts, stylesheet
│   ├── sidebar.php                 # Responsive navigation with live status badges
│   ├── alerts.php                  # Dismissible flash alert notifications
│   └── footer.php                  # Closing tags and scripts
├── index.php                       # Main Dealership Dashboard
├── login.php                       # Staff authentication portal
├── logout.php                      # Session termination
├── vehicles.php                    # Inventory Management (CRUD, Search, Sort, BST)
├── vehicle-details.php             # Vehicle specifications & BST path inspector
├── inquiries.php                   # Customer Inquiry Management
├── test-drives.php                 # Test Drive Scheduling & FIFO Queue Dispatcher
├── dsa-showcase.php                # DSA Engine verification, test suite & flowchart
├── users.php                       # Administrator staff accounts management
├── setup.php                       # Database setup & seeding migration
└── autoload.php                    # PSR-4 Autoloader mapping App\ to src/
```

---

## ⚡ Integration of the 4 Selected Data Structures & Algorithms

Each component is directly connected to actual dealership workflows and evaluated against **Normal**, **Empty**, **Missing**, and **Invalid** test cases:

### 1. Linear Search
- **Class / Method:** [`App\Algorithms\LinearSearch::search()`](src/Algorithms/LinearSearch.php)
- **Workflow:** Vehicle Inventory query search (Brand, Model, VIN, or ID).
- **Time Complexity:** Best: $O(1)$ | Average: $O(n)$ | Worst: $O(n)$
- **Space Complexity:** $O(1)$ auxiliary space
- **Justification:** Can find vehicles across unsorted lists without requiring index pre-computation.

### 2. Merge Sort
- **Class / Method:** [`App\Algorithms\MergeSort::sort()`](src/Algorithms/MergeSort.php)
- **Workflow:** Sorting vehicle listings by `price`, `year`, `brand`, or `model` (Ascending / Descending).
- **Time Complexity:** Best: $O(n \log n)$ | Average: $O(n \log n)$ | Worst: $O(n \log n)$
- **Space Complexity:** $O(n)$ auxiliary space
- **Justification:** Provides guaranteed $O(n \log n)$ performance and stable ordering regardless of initial input order.

### 3. Binary Search Tree (BST)
- **Class / Method:** [`App\DataStructures\BinarySearchTree::search()`](src/DataStructures/BinarySearchTree.php)
- **Workflow:** In-memory indexing of all vehicles by unique `Vehicle ID`. Supports fast $O(\log n)$ average ID lookups and sorted in-order traversal.
- **Time Complexity:** Best: $O(1)$ | Average: $O(\log n)$ | Worst: $O(n)$
- **Space Complexity:** $O(n)$ tree node storage
- **Justification:** Provides hierarchical search and in-order traversal directly on vehicle IDs.

### 4. Linked List FIFO Queue
- **Class / Method:** [`App\DataStructures\Queue::enqueue()`](src/DataStructures/Queue.php) / `dequeue()` / `peek()`
- **Workflow:** Test-Drive Processing & Dispatch Queue. Earliest bookings enter at the rear and wait in First-In, First-Out order. Staff process requests strictly from the front pointer.
- **Time Complexity:** Enqueue: $O(1)$ | Dequeue: $O(1)$ | Peek: $O(1)$
- **Space Complexity:** $O(k)$ for $k$ pending requests
- **Justification:** Eliminates scheduling favoritism by serving pending customer test-drives in chronological order with constant-time queue operations.

---

## 🧪 DSA Verification & Test Suite Summary

Accessible directly within the system at **[http://localhost/SampleFinalProject/dsa-showcase.php](http://localhost/SampleFinalProject/dsa-showcase.php)**:

| Component | Normal Case | Empty Case | Missing Case | Invalid Case |
|---|---|---|---|---|
| **Linear Search** | ✅ PASS (Finds brand) | ✅ PASS (Returns all) | ✅ PASS (Empty result on missing) | ✅ PASS (Safe rejection on negative ID) |
| **Merge Sort** | ✅ PASS (Sorted ASC) | ✅ PASS (Empty array `[]`) | ✅ PASS (Fallback on missing field) | ✅ PASS (Sanitized invalid direction) |
| **Binary Search Tree** | ✅ PASS (Found ID node) | ✅ PASS (Root is null check) | ✅ PASS (Null on non-existent ID) | ✅ PASS (Guarded on negative ID) |
| **FIFO Queue** | ✅ PASS (A → B → C FIFO) | ✅ PASS (Null on empty queue) | ✅ PASS (Underflow safe) | ✅ PASS (Clear and boundary validation) |

---

## 🔒 Security & Data Integrity
- Passwords securely hashed with `password_hash()` using `PASSWORD_DEFAULT` (Bcrypt).
- Prepared statements (`PDO::prepare()`) utilized on all database queries to prevent SQL Injection.
- Full server-side and client-side validation on normal, empty, missing, and invalid data.
- Sold vehicles are retained in inventory for sales reference and accounting history.
