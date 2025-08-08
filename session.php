<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../includes/auth.php';
require_once '../includes/db.php';

// Only allow logged in teachers
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'teacher') {
    header("Location: ../login.php");
    exit;
}

$message = '';
$message_type = ''; // For styling success/error messages

// On form submit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $teacher = $_SESSION['username'];
    $subject = trim($_POST['subject']);
    $class = trim($_POST['class']);
    $code = str_pad(random_int(0, 9999), 4, '0', STR_PAD_LEFT); // Auto-generated 4-digit code
    $latitude = floatval($_POST['latitude']);
    $longitude = floatval($_POST['longitude']);
    $location_name = trim($_POST['location_name']);
    $division = isset($_POST['division']) ? trim($_POST['division']) : '';

    if (empty($subject) || empty($class) || !$latitude || !$longitude || empty($division)) {
        $message = "All fields including location and division are required.";
        $message_type = 'error';
    } else {
        // Check if code already exists for this teacher (optional)
        $stmt = $pdo->prepare("SELECT * FROM attendance_sessions WHERE teacher = ? AND code = ?");
        $stmt->execute([$teacher, $code]);
        if ($stmt->rowCount() > 0) {
            $message = "You already have a session with this code. Please try again.";
            $message_type = 'error';
        } else {
            // Insert session with division (created_at auto set by DB)
            $stmt = $pdo->prepare("INSERT INTO attendance_sessions (teacher, subject, class, code, latitude, longitude, location_name, division) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            if ($stmt->execute([$teacher, $subject, $class, $code, $latitude, $longitude, $location_name, $division])) {
                $message = "Attendance session created successfully! Session Code: $code";
                $message_type = 'success';
            } else {
                $message = "Error creating session. Please try again.";
                $message_type = 'error';
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Attendance Session | AttendEase</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #2a5298;
            --primary-light: #3b6bc5;
            --primary-dark: #1e3c72;
            --light: #ffffff;
            --dark: #1a202c;
            --gray-100: #f8fafc;
            --gray-200: #f1f5f9;
            --gray-300: #e2e8f0;
            --success: #10b981;
            --danger: #ef4444;
            --warning: #f59e0b;
            --info: #3b82f6;
            --card-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            --shadow-lg: 0 10px 25px rgba(0, 0, 0, 0.1);
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', -apple-system, BlinkMacSystemFont, sans-serif;
        }
        
        body {
            background-color: var(--gray-100);
            color: var(--dark);
            line-height: 1.5;
            -webkit-font-smoothing: antialiased;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 1.5rem;
        }
        
        /* Impressive Header Design */
        .header-wrapper {
            position: relative;
            margin-bottom: 3rem;
            height: 220px;
            overflow: hidden;
            background: linear-gradient(135deg, var(--primary-dark) 0%, var(--primary) 100%);
            clip-path: polygon(0 0, 100% 0, 100% 85%, 0 100%);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }
        
        .header-content {
            position: relative;
            z-index: 2;
            padding: 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            height: 100%;
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .brand {
            display: flex;
            align-items: center;
            gap: 1rem;
            text-decoration: none;
        }
        
        .logo {
            width: 60px;
            height: 60px;
            object-fit: contain;
            background-color: var(--light);
            border-radius: 15px;
            padding: 0.5rem;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }
        
        .site-name {
            font-weight: 700;
            font-size: 1.8rem;
            color: var(--light);
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        
        .user-info {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            background-color: rgba(255, 255, 255, 0.2);
            padding: 0.75rem 1.25rem;
            border-radius: 30px;
            backdrop-filter: blur(5px);
            -webkit-backdrop-filter: blur(5px);
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        
        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: rgba(255, 255, 255, 0.3);
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .user-name {
            font-weight: 500;
            color: var(--light);
            font-size: 1rem;
        }
        
        /* Main Content */
        .main-content {
            position: relative;
            z-index: 3;
            margin-top: -50px;
        }
        
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }
        
        .page-title {
            font-size: 1.8rem;
            color: var(--primary);
            font-weight: 600;
        }
        
        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.75rem 1.5rem;
            background-color: var(--primary);
            color: white;
            text-decoration: none;
            border-radius: 30px;
            transition: var(--transition);
            font-weight: 500;
            box-shadow: var(--card-shadow);
        }
        
        .back-link:hover {
            background-color: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.15);
        }
        
        /* Card */
        .card {
            background: var(--light);
            border-radius: 15px;
            box-shadow: var(--shadow-lg);
            padding: 2rem;
            margin-bottom: 2rem;
            transition: var(--transition);
            border: none;
        }
        
        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.1);
        }
        
        /* Alert Messages */
        .alert {
            padding: 1rem 1.5rem;
            margin-bottom: 2rem;
            border-radius: 10px;
            display: flex;
            align-items: center;
            gap: 1rem;
            font-size: 1rem;
        }
        
        .alert-success {
            background-color: rgba(16, 185, 129, 0.1);
            color: var(--success);
            border: 1px solid rgba(16, 185, 129, 0.2);
        }
        
        .alert-error {
            background-color: rgba(239, 68, 68, 0.1);
            color: var(--danger);
            border: 1px solid rgba(239, 68, 68, 0.2);
        }
        
        /* Form Styles */
        .form-group {
            margin-bottom: 1.75rem;
        }
        
        .form-label {
            display: block;
            margin-bottom: 0.75rem;
            font-weight: 500;
            color: var(--dark);
            font-size: 1rem;
        }
        
        .form-control {
            width: 100%;
            padding: 1rem;
            border: 1px solid var(--gray-300);
            border-radius: 8px;
            font-size: 1rem;
            transition: var(--transition);
            background-color: #fcfcfc;
        }
        
        .form-control:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(42, 82, 152, 0.2);
            background-color: #fff;
        }
        
        .btn {
            padding: 1rem 2rem;
            background-color: var(--primary);
            color: white;
            border: none;
            border-radius: 8px;
            font-weight: 500;
            font-size: 1rem;
            cursor: pointer;
            transition: var(--transition);
            display: inline-flex;
            align-items: center;
            gap: 0.75rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            width: 100%;
            justify-content: center;
        }
        
        .btn:hover {
            background-color: var(--primary-dark);
            transform: translateY(-3px);
            box-shadow: 0 8px 15px rgba(0, 0, 0, 0.15);
        }
        
        .location-status {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-top: 0.5rem;
            font-size: 0.875rem;
            color: #64748b;
            padding: 0.75rem;
            background-color: var(--gray-200);
            border-radius: 8px;
        }
        
        .location-icon {
            width: 16px;
            height: 16px;
            color: var(--primary);
        }
        
        .location-error {
            color: var(--danger);
        }
        
        /* Responsive adjustments */
        @media (max-width: 768px) {
            .header-wrapper {
                height: 180px;
                clip-path: polygon(0 0, 100% 0, 100% 90%, 0 100%);
            }
            
            .header-content {
                padding: 1.5rem;
            }
            
            .logo {
                width: 50px;
                height: 50px;
            }
            
            .site-name {
                font-size: 1.5rem;
            }
            
            .page-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 1.5rem;
            }
            
            .back-link {
                width: 100%;
                justify-content: center;
            }
        }
        
        @media (max-width: 480px) {
            .header-wrapper {
                height: 160px;
            }
            
            .logo {
                width: 45px;
                height: 45px;
            }
            
            .site-name {
                font-size: 1.3rem;
            }
            
            .user-info {
                padding: 0.5rem 1rem;
            }
            
            .user-avatar {
                width: 35px;
                height: 35px;
            }
            
            .user-name {
                font-size: 0.9rem;
            }
            
            .card {
                padding: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <!-- Impressive Header Section -->
    <div class="header-wrapper">
        <div class="header-content">
            <a href="../index.php" class="brand">
                <img src="../assets/images/logo.png" alt="AttendEase Logo" class="logo">
                <span class="site-name">AttendEase</span>
            </a>
            <div class="user-info">
                <div class="user-avatar">
                    <i class="fas fa-chalkboard-teacher" style="color: white; font-size: 1rem;"></i>
                </div>
                <span class="user-name"><?= htmlspecialchars($_SESSION['name'] ?? 'Teacher') ?></span>
            </div>
        </div>
    </div>

    <div class="container">
        <div class="main-content">
            <div class="page-header">
                <h1 class="page-title">Create Attendance Session</h1>
                  <a href="../dashboard/teacher.php" class="back-link">                            
                    <i class="fas fa-arrow-left"></i> Back to Dashboard
                </a>
            </div>

            <div class="card">
                <?php if ($message): ?>
                    <div class="alert alert-<?= $message_type === 'success' ? 'success' : 'error' ?>">
                        <i class="fas <?= $message_type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle' ?>"></i>
                        <?= htmlspecialchars($message) ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="" id="sessionForm">
                    <div class="form-group">
                        <label for="subject" class="form-label">Subject</label>
                        <input type="text" id="subject" name="subject" class="form-control" 
                               value="<?= isset($subject) ? htmlspecialchars($subject) : '' ?>" 
                               placeholder="Enter subject name" required>
                    </div>

                    <div class="form-group">
                        <label for="class" class="form-label">Class/Grade</label>
                        <input type="text" id="class" name="class" class="form-control" 
                               value="<?= isset($class) ? htmlspecialchars($class) : '' ?>" 
                               placeholder="Enter class/grade (e.g., 10th, 12th, B.Tech)" required>
                    </div>

                    <div class="form-group">
                        <label for="division" class="form-label">Division</label>
                        <select name="division" id="division" class="form-control" required>
                            <option value="">Select Division</option>
                            <option value="A" <?= isset($division) && $division === 'A' ? 'selected' : '' ?>>Division A</option>
                            <option value="B" <?= isset($division) && $division === 'B' ? 'selected' : '' ?>>Division B</option>
                            <option value="C" <?= isset($division) && $division === 'C' ? 'selected' : '' ?>>Division C</option>
                        </select>
                    </div>

                    <input type="hidden" id="latitude" name="latitude">
                    <input type="hidden" id="longitude" name="longitude">
                    <input type="hidden" id="location_name" name="location_name">

                    <div class="form-group">
                        <label class="form-label">Location</label>
                        <div class="location-status" id="locationStatus">
                            <svg class="location-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                 stroke-linecap="round" stroke-linejoin="round">
                                <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path>
                                <circle cx="12" cy="10" r="3"></circle>
                            </svg>
                            <span>Getting your current location...</span>
                        </div>
                    </div>

                    <button type="submit" class="btn">
                        <i class="fas fa-plus-circle"></i> Create Attendance Session
                    </button>
                </form>
            </div>
        </div>
    </div>

    <script>
        function updateLocationStatus(text, isError = false) {
            const statusElement = document.getElementById('locationStatus');
            const iconElement = statusElement.querySelector('svg');

            if (isError) {
                statusElement.innerHTML = `
                    <svg class="location-icon location-error" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="10"></circle>
                        <line x1="12" y1="8" x2="12" y2="12"></line>
                        <line x1="12" y1="16" x2="12.01" y2="16"></line>
                    </svg>
                    <span class="location-error">${text}</span>
                `;
            } else {
                iconElement.style.color = '#2a5298';
                statusElement.querySelector('span').textContent = text;
            }
        }

        function reverseGeocode(latitude, longitude) {
            return new Promise((resolve, reject) => {
                fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${latitude}&lon=${longitude}`)
                    .then(response => response.json())
                    .then(data => {
                        let address = '';
                        if (data.address) {
                            // Build a readable address from available components
                            if (data.address.building || data.address.house_number) {
                                address += (data.address.house_number ? data.address.house_number + ' ' : '') + 
                                          (data.address.building ? data.address.building : '') + ', ';
                            }
                            if (data.address.road) {
                                address += data.address.road + ', ';
                            }
                            if (data.address.suburb) {
                                address += data.address.suburb + ', ';
                            }
                            if (data.address.city || data.address.town || data.address.village) {
                                address += data.address.city || data.address.town || data.address.village;
                            }
                            
                            // If we have a very short address, try to include more details
                            if (address.length < 20 && data.address.state) {
                                address += (address ? ', ' : '') + data.address.state;
                            }
                            
                            // Fallback to display name if we don't have enough components
                            if (!address || address.length < 10) {
                                address = data.display_name.split(',')[0];
                            }
                        } else {
                            address = data.display_name.split(',')[0];
                        }
                        resolve(address);
                    })
                    .catch(error => {
                        console.error('Geocoding error:', error);
                        resolve(`Near ${latitude.toFixed(4)}, ${longitude.toFixed(4)}`);
                    });
            });
        }

        function getLocation() {
            if (navigator.geolocation) {
                updateLocationStatus("Getting your current location...");

                navigator.geolocation.getCurrentPosition(
                    async function (position) {
                        const lat = position.coords.latitude;
                        const lng = position.coords.longitude;
                        
                        document.getElementById("latitude").value = lat;
                        document.getElementById("longitude").value = lng;
                        
                        try {
                            const address = await reverseGeocode(lat, lng);
                            document.getElementById("location_name").value = address;
                            updateLocationStatus(`Location: ${address}`);
                        } catch (error) {
                            document.getElementById("location_name").value = `Near ${lat.toFixed(4)}, ${lng.toFixed(4)}`;
                            updateLocationStatus(`Location: Near ${lat.toFixed(4)}, ${lng.toFixed(4)}`);
                        }
                    },
                    function (error) {
                        let errorMessage;
                        switch (error.code) {
                            case error.PERMISSION_DENIED:
                                errorMessage = "Location access denied. Please enable location services.";
                                break;
                            case error.POSITION_UNAVAILABLE:
                                errorMessage = "Location information unavailable.";
                                break;
                            case error.TIMEOUT:
                                errorMessage = "Location request timed out.";
                                break;
                            default:
                                errorMessage = "Unknown error occurred while getting location.";
                        }
                        updateLocationStatus(errorMessage, true);
                    },
                    {enableHighAccuracy: true, timeout: 10000}
                );
            } else {
                updateLocationStatus("Geolocation is not supported by this browser.", true);
            }
        }

        document.getElementById('sessionForm').addEventListener('submit', function (e) {
            const latitude = document.getElementById('latitude').value;
            const longitude = document.getElementById('longitude').value;

            if (!latitude || !longitude) {
                alert('Please wait while we get your location. If this persists, check your location permissions.');
                e.preventDefault();
                return false;
            }

            return true;
        });

        window.onload = getLocation;
    </script>
</body>
</html>