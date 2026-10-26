<?php
/**
 * CarDeal Database Setup & Auto-Seeder
 * Initializes database `cardeal_db` with tables and realistic seed data.
 */

require_once __DIR__ . '/autoload.php';
$config = require __DIR__ . '/config/database.php';

$isCli = (php_sapi_name() === 'cli');

function outputMsg(string $msg, bool $isCli) {
    if ($isCli) {
        echo $msg . PHP_EOL;
    } else {
        echo "<p style='margin:4px 0; font-family: monospace;'>{$msg}</p>";
    }
}

try {
    outputMsg("==> Connecting to MySQL server on {$config['host']}:{$config['port']}...", $isCli);

    $initDsn = sprintf("mysql:host=%s;port=%d;charset=%s", $config['host'], $config['port'], $config['charset'] ?? 'utf8mb4');
    $pdo = new PDO($initDsn, $config['username'], $config['password'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);

    // 1. Create database
    $dbName = $config['dbname'];
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$dbName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;");
    outputMsg("==> Database `{$dbName}` created or already exists.", $isCli);

    // Switch to database
    $pdo->exec("USE `{$dbName}`;");

    // 2. Create Users Table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `users` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `username` VARCHAR(50) NOT NULL UNIQUE,
            `password_hash` VARCHAR(255) NOT NULL,
            `full_name` VARCHAR(100) NOT NULL,
            `email` VARCHAR(100) NULL,
            `role` ENUM('admin', 'staff') NOT NULL DEFAULT 'staff',
            `status` ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
            `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
            `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
    outputMsg("==> Table `users` ready.", $isCli);

    // 3. Create Vehicles Table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `vehicles` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `brand` VARCHAR(50) NOT NULL,
            `model` VARCHAR(75) NOT NULL,
            `year` INT NOT NULL,
            `color` VARCHAR(40) NOT NULL,
            `transmission` VARCHAR(30) NOT NULL DEFAULT 'Automatic',
            `fuel_type` VARCHAR(30) NOT NULL DEFAULT 'Gasoline',
            `price` DECIMAL(12, 2) NOT NULL,
            `mileage` INT NOT NULL DEFAULT 0,
            `vin` VARCHAR(30) NOT NULL UNIQUE,
            `status` ENUM('Available', 'Sold') NOT NULL DEFAULT 'Available',
            `image_url` TEXT NULL,
            `description` TEXT NULL,
            `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
            `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_brand (`brand`),
            INDEX idx_model (`model`),
            INDEX idx_status (`status`),
            INDEX idx_price (`price`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
    outputMsg("==> Table `vehicles` ready.", $isCli);

    // 4. Create Inquiries Table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `inquiries` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `customer_name` VARCHAR(100) NOT NULL,
            `contact_number` VARCHAR(30) NOT NULL,
            `email` VARCHAR(100) NULL,
            `vehicle_id` INT NULL,
            `question` TEXT NOT NULL,
            `inquiry_date` DATE NOT NULL,
            `status` ENUM('Pending', 'Contacted', 'Resolved', 'Closed') NOT NULL DEFAULT 'Pending',
            `staff_notes` TEXT NULL,
            `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
            `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (`vehicle_id`) REFERENCES `vehicles`(`id`) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
    outputMsg("==> Table `inquiries` ready.", $isCli);

    // 5. Create Test Drives Table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `test_drives` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `customer_name` VARCHAR(100) NOT NULL,
            `contact_number` VARCHAR(30) NOT NULL,
            `email` VARCHAR(100) NULL,
            `vehicle_id` INT NOT NULL,
            `scheduled_date` DATE NOT NULL,
            `scheduled_time` TIME NOT NULL DEFAULT '10:00:00',
            `status` ENUM('Pending', 'Approved', 'In-Progress', 'Completed', 'Cancelled') NOT NULL DEFAULT 'Pending',
            `license_number` VARCHAR(50) NULL,
            `notes` TEXT NULL,
            `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
            `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (`vehicle_id`) REFERENCES `vehicles`(`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
    outputMsg("==> Table `test_drives` ready.", $isCli);

    // 6. Seed Default Users if empty
    $checkUsers = $pdo->query("SELECT COUNT(*) FROM `users`")->fetchColumn();
    if ($checkUsers == 0) {
        $stmtUser = $pdo->prepare("
            INSERT INTO `users` (username, password_hash, full_name, email, role, status)
            VALUES (:username, :password_hash, :full_name, :email, :role, :status)
        ");

        $defaultUsers = [
            [
                'username' => 'admin',
                'password' => 'admin123',
                'full_name' => 'Alexander Vance',
                'email' => 'admin@cardeal.com',
                'role' => 'admin',
                'status' => 'active'
            ],
            [
                'username' => 'staff1',
                'password' => 'staff123',
                'full_name' => 'Sarah Jenkins',
                'email' => 'sarah.j@cardeal.com',
                'role' => 'staff',
                'status' => 'active'
            ],
            [
                'username' => 'staff2',
                'password' => 'staff123',
                'full_name' => 'Marcus Chen',
                'email' => 'marcus.c@cardeal.com',
                'role' => 'staff',
                'status' => 'active'
            ]
        ];

        foreach ($defaultUsers as $u) {
            $stmtUser->execute([
                'username' => $u['username'],
                'password_hash' => password_hash($u['password'], PASSWORD_DEFAULT),
                'full_name' => $u['full_name'],
                'email' => $u['email'],
                'role' => $u['role'],
                'status' => $u['status']
            ]);
        }
        outputMsg("==> Seeded 3 default users (admin / admin123, staff1 / staff123, staff2 / staff123).", $isCli);
    }

    // 7. Seed Vehicles if empty
    $checkVehicles = $pdo->query("SELECT COUNT(*) FROM `vehicles`")->fetchColumn();
    if ($checkVehicles == 0) {
        $stmtVeh = $pdo->prepare("
            INSERT INTO `vehicles` (
                brand, model, year, color, transmission, fuel_type, price, mileage, vin, status, image_url, description
            ) VALUES (
                :brand, :model, :year, :color, :transmission, :fuel_type, :price, :mileage, :vin, :status, :image_url, :description
            )
        ");

        $seedVehicles = [
            [
                'brand' => 'Toyota',
                'model' => 'Camry XSE Hybrid',
                'year' => 2024,
                'color' => 'Wind Chill Pearl / Black Roof',
                'transmission' => 'CVT',
                'fuel_type' => 'Hybrid',
                'price' => 34295.00,
                'mileage' => 15,
                'vin' => '4T1B11HK5RU102938',
                'status' => 'Available',
                'image_url' => 'https://images.unsplash.com/photo-1621007947382-bb3c3994e3fb?auto=format&fit=crop&w=800&q=80',
                'description' => 'Dynamic hybrid powertrain offering exceptional fuel efficiency paired with sporty styling and leather-trimmed seats.'
            ],
            [
                'brand' => 'Honda',
                'model' => 'Civic Type R',
                'year' => 2024,
                'color' => 'Championship White',
                'transmission' => 'Manual',
                'fuel_type' => 'Gasoline',
                'price' => 45890.00,
                'mileage' => 500,
                'vin' => 'JHMFL5G42RC001928',
                'status' => 'Available',
                'image_url' => 'https://images.unsplash.com/photo-1605559424843-9e4c228bf1c2?auto=format&fit=crop&w=800&q=80',
                'description' => 'Track-ready performance hatchback with turbocharged VTEC power, active dampening, and high-bolster sport seats.'
            ],
            [
                'brand' => 'Ford',
                'model' => 'Mustang Dark Horse',
                'year' => 2024,
                'color' => 'Blue Ember Metallic',
                'transmission' => 'Manual',
                'fuel_type' => 'Gasoline',
                'price' => 61280.00,
                'mileage' => 45,
                'vin' => '1FA6P8CF9R5102941',
                'status' => 'Available',
                'image_url' => 'https://images.unsplash.com/photo-1584345604476-8ec5e12e42dd?auto=format&fit=crop&w=800&q=80',
                'description' => 'The pinnacle of 5.0L Coyote V8 performance with bespoke chassis tuning and aggressive aerodynamic design.'
            ],
            [
                'brand' => 'BMW',
                'model' => 'M4 Competition Coupe',
                'year' => 2023,
                'color' => 'Isle of Man Green',
                'transmission' => 'Automatic',
                'fuel_type' => 'Gasoline',
                'price' => 82500.00,
                'mileage' => 6200,
                'vin' => 'WBA43AZ00PCL83921',
                'status' => 'Available',
                'image_url' => 'https://images.unsplash.com/photo-1555215695-3004980ad54e?auto=format&fit=crop&w=800&q=80',
                'description' => '503 hp twin-turbocharged inline 6 with M xDrive all-wheel drive and carbon bucket seating.'
            ],
            [
                'brand' => 'Porsche',
                'model' => '911 Carrera GTS',
                'year' => 2024,
                'color' => 'Guards Red',
                'transmission' => 'Dual-Clutch',
                'fuel_type' => 'Gasoline',
                'price' => 152000.00,
                'mileage' => 1200,
                'vin' => 'WP0AB2A92RS192837',
                'status' => 'Available',
                'image_url' => 'https://images.unsplash.com/photo-1614162692292-7ac56d7f7f1e?auto=format&fit=crop&w=800&q=80',
                'description' => 'Iconic rear-engine rear-wheel-drive sports car with Sport Chrono package and active suspension management.'
            ],
            [
                'brand' => 'Tesla',
                'model' => 'Model Y Performance',
                'year' => 2024,
                'color' => 'Stealth Grey',
                'transmission' => 'Automatic',
                'fuel_type' => 'Electric',
                'price' => 52490.00,
                'mileage' => 110,
                'vin' => '7SAYGDEE8RF192830',
                'status' => 'Sold',
                'image_url' => 'https://images.unsplash.com/photo-1560958089-b8a1929cea89?auto=format&fit=crop&w=800&q=80',
                'description' => 'Dual Motor All-Wheel Drive, 0-60 in 3.5 seconds with 279 miles range and Autopilot capability. Retained in records as sold.'
            ],
            [
                'brand' => 'Mercedes-Benz',
                'model' => 'AMG C 43 4MATIC',
                'year' => 2023,
                'color' => 'Obsidian Black Metallic',
                'transmission' => 'Automatic',
                'fuel_type' => 'Hybrid',
                'price' => 64900.00,
                'mileage' => 8400,
                'vin' => 'W1KZF8HB3PN918274',
                'status' => 'Available',
                'image_url' => 'https://images.unsplash.com/photo-1618843479313-40f8afb4b4d8?auto=format&fit=crop&w=800&q=80',
                'description' => 'Electrified turbocharger technology derived directly from Formula 1 paired with Burmester surround sound.'
            ],
            [
                'brand' => 'Audi',
                'model' => 'RS6 Avant',
                'year' => 2023,
                'color' => 'Nardo Gray',
                'transmission' => 'Automatic',
                'fuel_type' => 'Gasoline',
                'price' => 125000.00,
                'mileage' => 9500,
                'vin' => 'WAUZZZF27PN019283',
                'status' => 'Sold',
                'image_url' => 'https://images.unsplash.com/photo-1603584173870-7f23fdae1b7a?auto=format&fit=crop&w=800&q=80',
                'description' => 'Bi-turbo 4.0L V8 high performance super wagon with Quattro permanent AWD. Retained record for reference.'
            ],
            [
                'brand' => 'Hyundai',
                'model' => 'Ioniq 5 N',
                'year' => 2025,
                'color' => 'Performance Blue Matte',
                'transmission' => 'Automatic',
                'fuel_type' => 'Electric',
                'price' => 66100.00,
                'mileage' => 80,
                'vin' => 'KMHL84AE9SU928374',
                'status' => 'Available',
                'image_url' => 'https://images.unsplash.com/photo-1533473359331-0135ef1b58bf?auto=format&fit=crop&w=800&q=80',
                'description' => 'High-performance all-electric crossover featuring N e-shift simulation and corner-carving differential.'
            ],
            [
                'brand' => 'Toyota',
                'model' => 'GR Supra 3.0 Premium',
                'year' => 2024,
                'color' => 'Renaissance Red 2.0',
                'transmission' => 'Manual',
                'fuel_type' => 'Gasoline',
                'price' => 58550.00,
                'mileage' => 230,
                'vin' => 'WZ1DB4C08RW019284',
                'status' => 'Available',
                'image_url' => 'https://images.unsplash.com/photo-1542282088-72c9c27ed0cd?auto=format&fit=crop&w=800&q=80',
                'description' => 'Pure driving engagement with a 6-speed intelligent manual transmission and 382 hp inline 6 engine.'
            ]
        ];

        foreach ($seedVehicles as $v) {
            $stmtVeh->execute($v);
        }
        outputMsg("==> Seeded 10 realistic vehicles (8 Available, 2 Sold).", $isCli);
    }

    // 8. Seed Inquiries if empty
    $checkInquiries = $pdo->query("SELECT COUNT(*) FROM `inquiries`")->fetchColumn();
    if ($checkInquiries == 0) {
        $stmtInq = $pdo->prepare("
            INSERT INTO `inquiries` (customer_name, contact_number, email, vehicle_id, question, inquiry_date, status, staff_notes)
            VALUES (:customer_name, :contact_number, :email, :vehicle_id, :question, :inquiry_date, :status, :staff_notes)
        ");

        $seedInquiries = [
            [
                'customer_name' => 'David Martinez',
                'contact_number' => '+1 (555) 234-5678',
                'email' => 'david.m@gmail.com',
                'vehicle_id' => 1,
                'question' => 'Interested in financing terms and trade-in value for my 2018 Corolla on the Camry Hybrid.',
                'inquiry_date' => date('Y-m-d', strtotime('-2 days')),
                'status' => 'Pending',
                'staff_notes' => 'Awaiting credit pre-qualification form from customer.'
            ],
            [
                'customer_name' => 'Elena Rostova',
                'contact_number' => '+1 (555) 876-5432',
                'email' => 'elena.rostova@outlook.com',
                'vehicle_id' => 4,
                'question' => 'Is the M4 Competition equipped with the M Carbon Ceramic Brakes package?',
                'inquiry_date' => date('Y-m-d', strtotime('-4 days')),
                'status' => 'Contacted',
                'staff_notes' => 'Staff Marcus Chen emailed full window sticker specification sheet.'
            ],
            [
                'customer_name' => 'Robert Taylor',
                'contact_number' => '+1 (555) 345-6789',
                'email' => 'robert.taylor@techcorp.com',
                'vehicle_id' => 3,
                'question' => 'What is the current dealer allocation timeline for custom Dark Horse order?',
                'inquiry_date' => date('Y-m-d', strtotime('-1 day')),
                'status' => 'Pending',
                'staff_notes' => 'Needs follow up call on Monday morning.'
            ],
            [
                'customer_name' => 'Jessica Alba-Wong',
                'contact_number' => '+1 (555) 987-6543',
                'email' => 'j.albawong@yahoo.com',
                'vehicle_id' => 2,
                'question' => 'Can we schedule a pre-purchase mechanical inspection on the Civic Type R?',
                'inquiry_date' => date('Y-m-d', strtotime('-5 days')),
                'status' => 'Resolved',
                'staff_notes' => 'Customer confirmed appointment and inspection completed.'
            ]
        ];

        foreach ($seedInquiries as $inq) {
            $stmtInq->execute($inq);
        }
        outputMsg("==> Seeded 4 customer inquiries.", $isCli);
    }

    // 9. Seed Test Drives with Pending items to populate the FIFO Queue
    $checkTestDrives = $pdo->query("SELECT COUNT(*) FROM `test_drives`")->fetchColumn();
    if ($checkTestDrives == 0) {
        $stmtTd = $pdo->prepare("
            INSERT INTO `test_drives` (
                customer_name, contact_number, email, vehicle_id, scheduled_date, scheduled_time, status, license_number, notes, created_at
            ) VALUES (
                :customer_name, :contact_number, :email, :vehicle_id, :scheduled_date, :scheduled_time, :status, :license_number, :notes, :created_at
            )
        ");

        $tomorrow = date('Y-m-d', strtotime('+1 day'));
        $dayAfter = date('Y-m-d', strtotime('+2 days'));

        $seedDrives = [
            [
                'customer_name' => 'Lucas Sterling',
                'contact_number' => '+1 (555) 111-2233',
                'email' => 'lucas.s@gmail.com',
                'vehicle_id' => 5, // Porsche 911
                'scheduled_date' => $tomorrow,
                'scheduled_time' => '10:30:00',
                'status' => 'Pending',
                'license_number' => 'DL-908123-CA',
                'notes' => 'Customer has validated sports car driving experience. Queue Position #1.',
                'created_at' => date('Y-m-d H:i:s', strtotime('-3 hours'))
            ],
            [
                'customer_name' => 'Sophia Ramirez',
                'contact_number' => '+1 (555) 444-5566',
                'email' => 'sophia.r@agency.com',
                'vehicle_id' => 1, // Toyota Camry
                'scheduled_date' => $tomorrow,
                'scheduled_time' => '13:00:00',
                'status' => 'Pending',
                'license_number' => 'DL-448291-NV',
                'notes' => 'Requested highway test drive route. Queue Position #2.',
                'created_at' => date('Y-m-d H:i:s', strtotime('-2 hours'))
            ],
            [
                'customer_name' => 'Jonathan Reynolds',
                'contact_number' => '+1 (555) 777-8899',
                'email' => 'j.reynolds@finance.org',
                'vehicle_id' => 7, // Mercedes AMG
                'scheduled_date' => $dayAfter,
                'scheduled_time' => '15:00:00',
                'status' => 'Pending',
                'license_number' => 'DL-772819-AZ',
                'notes' => 'Interested in executive lease option. Queue Position #3.',
                'created_at' => date('Y-m-d H:i:s', strtotime('-1 hour'))
            ],
            [
                'customer_name' => 'Chloe Bennett',
                'contact_number' => '+1 (555) 999-0011',
                'email' => 'chloe.b@design.io',
                'vehicle_id' => 2, // Civic Type R
                'scheduled_date' => $tomorrow,
                'scheduled_time' => '11:00:00',
                'status' => 'Approved',
                'license_number' => 'DL-192837-WA',
                'notes' => 'Approved by Staff Marcus Chen. Pre-drive briefing scheduled.',
                'created_at' => date('Y-m-d H:i:s', strtotime('-1 day'))
            ],
            [
                'customer_name' => 'Michael Thorne',
                'contact_number' => '+1 (555) 222-3344',
                'email' => 'm.thorne@enterprise.com',
                'vehicle_id' => 3, // Mustang
                'scheduled_date' => date('Y-m-d', strtotime('-2 days')),
                'scheduled_time' => '14:00:00',
                'status' => 'Completed',
                'license_number' => 'DL-554433-OR',
                'notes' => 'Drive finished satisfactorily; customer considering trade-in.',
                'created_at' => date('Y-m-d H:i:s', strtotime('-3 days'))
            ]
        ];

        foreach ($seedDrives as $td) {
            $stmtTd->execute($td);
        }
        outputMsg("==> Seeded 5 test-drives (3 Pending in FIFO queue, 1 Approved, 1 Completed).", $isCli);
    }

    outputMsg("==================================================", $isCli);
    outputMsg("SUCCESS: CarDeal Database Setup Completed Successfully!", $isCli);
    outputMsg("Default Login Credentials:", $isCli);
    outputMsg("  Administrator: username = 'admin', password = 'admin123'", $isCli);
    outputMsg("  Staff Member:  username = 'staff1', password = 'staff123'", $isCli);
    outputMsg("==================================================", $isCli);

    if (!$isCli) {
        echo "<div style='margin-top:20px;'><a href='login.php' style='padding:10px 20px; background:#d62839; color:#fff; text-decoration:none; border-radius:6px; font-weight:bold;'>Go to Login Page</a></div>";
    }

} catch (Exception $e) {
    outputMsg("ERROR: " . $e->getMessage(), $isCli);
    if (!$isCli) {
        http_response_code(500);
    }
}
