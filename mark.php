<?php
session_start();
include('../includes/auth.php');
include('../includes/db.php');
include('../includes/location_utils.php');

redirectIfNotLoggedIn();

if ($_SESSION['role'] !== 'student') {
    header("Location: ../login.php");
    exit;
}

$student_username = $_SESSION['username'];

// Fetch student details
function getStudentDetails($pdo, $username) {
    $stmt = $pdo->prepare("SELECT roll, division, name FROM users WHERE username = ?");
    $stmt->execute([$username]);
    return $stmt->fetch();
}

$student = getStudentDetails($pdo, $student_username);
$roll_number = $student['roll'];
$student_division = $student['division'];
$student_name = $student['name'];

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $code = trim($_POST['code']);

    $lat = isset($_POST['latitude']) ? floatval($_POST['latitude']) : null;
    $lon = isset($_POST['longitude']) ? floatval($_POST['longitude']) : null;

    if (!$lat || !$lon) {
        $error = "Location data missing or invalid. Please enable location and try again.";
    } else {
        // Find session by code
        $stmt = $pdo->prepare("SELECT * FROM attendance_sessions WHERE code = ?");
        $stmt->execute([$code]);
        $session = $stmt->fetch();

        if ($session) {
            $session_id = $session['id'];
            $sess_lat = $session['latitude'];
            $sess_lon = $session['longitude'];
            $session_division = $session['division'];
            $subject = $session['subject'];

            // Use DateTime for reliable expiry check
            $timezone = new DateTimeZone('Asia/Kolkata');
            $created_at = new DateTime($session['created_at'], $timezone);
            $now = new DateTime('now', $timezone);

            $interval_seconds = $now->getTimestamp() - $created_at->getTimestamp();

            // 7 minutes = 420 seconds
            if ($interval_seconds > 420) {
                $reason = 'Session expired';
                $deny = $pdo->prepare("INSERT INTO attendance_denied (session_id, student_username, roll_number, reason) VALUES (?, ?, ?, ?)");
                $deny->execute([$session_id, $student_username, $roll_number, $reason]);
                $error = "Attendance session has expired. Please contact your teacher.";
            } elseif ($student_division !== $session_division) {
                $reason = "Division mismatch: Your division ($student_division) does not match session division ($session_division).";
                $deny = $pdo->prepare("INSERT INTO attendance_denied (session_id, student_username, roll_number, reason) VALUES (?, ?, ?, ?)");
                $deny->execute([$session_id, $student_username, $roll_number, $reason]);
                $error = "You cannot mark attendance for this session because your division does not match.";
            } else {
                $distance = calculateDistance($lat, $lon, $sess_lat, $sess_lon);

                // Check if already marked
                $check = $pdo->prepare("SELECT * FROM attendance_logs WHERE session_id = ? AND student_username = ?");
                $check->execute([$session_id, $student_username]);

                if ($check->rowCount() > 0) {
                    $error = "You have already marked attendance for this session.";
                } elseif ($distance <= 100) {
                    // Mark attendance
                    $insert = $pdo->prepare("INSERT INTO attendance_logs (session_id, student_username, roll_number, subject) VALUES ( ?, ?, ?, ?)");
                    $insert->execute([$session_id, $student_username, $roll_number, $subject]);
 
                    $distance_rounded = round($distance, 2);
                    $success = "Attendance marked successfully for $subject!";
                } else {
                    $reason = "Out of range: " . round($distance, 2) . " meters away.";
                    $deny = $pdo->prepare("INSERT INTO attendance_denied (session_id, student_username, roll_number, reason) VALUES (?, ?, ?, ?)");
                    $deny->execute([$session_id, $student_username, $roll_number, $reason]);
                    $error = "You are too far from the classroom to mark attendance.";
                }
            }
        } else {
            $error = "Invalid session code.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mark Attendance | Student Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #4e73df;
            --success-color: #1cc88a;
            --danger-color: #e74a3b;
            --warning-color: #f6c23e;
            --info-color: #36b9cc;
        }
        
        body {
            background-color: #f8f9fc;
            font-family: 'Nunito', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
        }
        
        .attendance-card {
            border: none;
            border-radius: 1rem;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
            overflow: hidden;
            max-width: 500px;
        }
        
        .card-header {
            background-color: var(--primary-color);
            color: white;
            padding: 1.25rem 1.5rem;
            border-bottom: none;
        }
        
        .card-body {
            padding: 2rem;
        }
        
        .location-status {
            border-radius: 0.35rem;
            padding: 1rem;
            margin-bottom: 1.5rem;
            background-color: #f8f9fc;
            border-left: 0.25rem solid var(--info-color);
        }
        
        .location-icon {
            font-size: 1.25rem;
            margin-right: 0.75rem;
        }
        
        .location-active {
            color: var(--success-color);
            border-left-color: var(--success-color);
        }
        
        .location-error {
            color: var(--danger-color);
            border-left-color: var(--danger-color);
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
            padding: 0.75rem;
            font-weight: 600;
        }
        
        .btn-primary:hover {
            background-color: #2e59d9;
            border-color: #2653d4;
        }
        
        .btn-secondary {
            background-color: #858796;
            border-color: #858796;
        }
        
        .form-control {
            padding: 0.75rem;
            border-radius: 0.35rem;
        }
        
        .alert {
            border-radius: 0.35rem;
            padding: 1rem;
        }
        
        .student-info {
            background-color: #f8f9fc;
            border-radius: 0.35rem;
            padding: 1rem;
            margin-bottom: 1.5rem;
        }
        
        .info-label {
            font-weight: 600;
            color: #5a5c69;
        }
    </style>
</head>
<body>
    <div class="container d-flex justify-content-center align-items-center min-vh-100">
        <div class="attendance-card w-100">
            <div class="card-header text-center">
                <h4 class="mb-0"><i class="fas fa-user-check me-2"></i>Mark Attendance</h4>
            </div>
            <div class="card-body">
                <!-- Student Information -->
                <div class="student-info mb-4">
                    <div class="row">
                        <div class="col-6">
                            <div class="info-label">Name</div>
                            <div><?= htmlspecialchars($student_name) ?></div>
                        </div>
                        <div class="col-3">
                            <div class="info-label">Roll No</div>
                            <div><?= htmlspecialchars($roll_number) ?></div>
                        </div>
                        <div class="col-3">
                            <div class="info-label">Division</div>
                            <div><?= htmlspecialchars($student_division) ?></div>
                        </div>
                    </div>
                </div>
                
                <?php if ($success): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle me-2"></i> <?= htmlspecialchars($success) ?>
                    </div>
                <?php elseif ($error): ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle me-2"></i> <?= htmlspecialchars($error) ?>
                    </div>
                <?php endif; ?>
                
                <!-- Location Status -->
                <div id="locationStatus" class="location-status">
                    <div class="d-flex align-items-center">
                        <i id="locationIcon" class="fas fa-spinner fa-pulse location-icon"></i>
                        <span id="locationText">Checking your location...</span>
                    </div>
                </div>
                
                <!-- Attendance Form -->
                <form id="attendanceForm" method="POST" action="">
                    <div class="mb-4">
                        <label for="code" class="form-label fw-bold">Session Code</label>
                        <input type="text" class="form-control" name="code" id="code" required 
                               placeholder="Enter the 6-digit code provided by your teacher">
                    </div>
                    
                    <input type="hidden" name="latitude" id="latitude">
                    <input type="hidden" name="longitude" id="longitude">
                    
                    <button type="submit" id="submitBtn" class="btn btn-primary w-100 mb-3" disabled>
                        <i class="fas fa-check-circle me-2"></i> Mark Attendance
                    </button>
                    
                    <a href="../dashboard/student.php" class="btn btn-secondary w-100">
                        <i class="fas fa-arrow-left me-2"></i> Back to Dashboard
                    </a>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const submitBtn = document.getElementById('submitBtn');
        const locationStatus = document.getElementById('locationStatus');
        const locationIcon = document.getElementById('locationIcon');
        const locationText = document.getElementById('locationText');
        
        // Enhanced location handling
        function updateLocationStatus(status, message, isError = false) {
            locationIcon.className = `fas ${status} location-icon`;
            locationText.textContent = message;
            locationStatus.className = isError ? 
                'location-status location-error' : 
                'location-status location-active';
        }
        
        function fetchLocation() {
            if (!navigator.geolocation) {
                updateLocationStatus('fa-times-circle', 
                    'Geolocation is not supported by your browser.', true);
                return;
            }
            
            updateLocationStatus('fa-spinner fa-pulse', 'Checking your location...');
            
            const options = {
                enableHighAccuracy: true,
                timeout: 10000,
                maximumAge: 0
            };
            
            navigator.geolocation.getCurrentPosition(
                (position) => {
                    const lat = position.coords.latitude;
                    const lon = position.coords.longitude;
                    const accuracy = position.coords.accuracy;
                    
                    document.getElementById('latitude').value = lat;
                    document.getElementById('longitude').value = lon;
                    submitBtn.disabled = false;
                    
                    updateLocationStatus('fa-check-circle', 
                        `Location found (Accuracy: ${Math.round(accuracy)} meters)`);
                },
                (error) => {
                    submitBtn.disabled = true;
                    let errorMessage;
                    
                    switch(error.code) {
                        case error.PERMISSION_DENIED:
                            errorMessage = 'Location access was denied. Please enable location services.';
                            break;
                        case error.POSITION_UNAVAILABLE:
                            errorMessage = 'Location information is unavailable.';
                            break;
                        case error.TIMEOUT:
                            errorMessage = 'The request to get location timed out.';
                            break;
                        default:
                            errorMessage = 'An unknown error occurred while getting location.';
                    }
                    
                    updateLocationStatus('fa-times-circle', errorMessage, true);
                },
                options
            );
        }
        
        // Initialize on page load
        document.addEventListener('DOMContentLoaded', fetchLocation);
        
        // Form validation
        document.getElementById('attendanceForm').addEventListener('submit', function(e) {
            const lat = document.getElementById('latitude').value;
            const lon = document.getElementById('longitude').value;
            
            if (!lat || !lon) {
                e.preventDefault();
                updateLocationStatus('fa-exclamation-circle', 
                    'Location not available. Please wait and try again.', true);
            }
        });
    </script>
</body>
</html>