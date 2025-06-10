<?php
include_once $_SERVER['DOCUMENT_ROOT'] . '/myschedule/public/admin/logger.php';
session_start();
@include '../components/links.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/myschedule/config.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);

$teacher_id = isset($_GET['teacher_id']) ? intval($_GET['teacher_id']) : 0;
$teacher = [];
$success = false;
$error = '';
$form_values = [
    'name' => '',
    'email' => '',
    'subject' => '',
    'message' => ''
];

if ($teacher_id) {
    $stmt = $conn->prepare("
        SELECT CONCAT(u.firstname, ' ', u.lastname) AS name, o.name AS office
        FROM teachers t
        JOIN users u ON t.user_id = u.id
        JOIN offices o ON t.office_id = o.id
        WHERE t.id = ?
    ");
    $stmt->bind_param("i", $teacher_id);
    $stmt->execute();
    $teacher = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $subject = trim($_POST['subject']);
    $message = trim($_POST['message']);
    
    $form_values = [
        'name' => $name,
        'email' => $email,
        'subject' => $subject,
        'message' => $message
    ];
    
    if (empty($email)) {
        $error = "Email is required";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format";
    } elseif (empty($subject)) {
        $error = "Subject is required";
    } elseif (empty($message)) {
        $error = "Message is required";
    } else {
        $email = filter_var($email, FILTER_SANITIZE_EMAIL);
        
        $stmt = $conn->prepare("
            INSERT INTO complaints 
            (teacher_id, name, email, subject, message, created_at) 
            VALUES (?, ?, ?, ?, ?, NOW())
        ");
        $stmt->bind_param("issss", $teacher_id, $name, $email, $subject, $message);
        
        if ($stmt->execute()) {
            $success = true;
            // Clear form values on success
            $form_values = [
                'name' => '',
                'email' => '',
                'subject' => '',
                'message' => ''
            ];
            log_action('INSERT', "Complaint submitted for teacher ID $teacher_id, subject: $subject");
        } else {
            $error = "Failed to submit complaint: " . $stmt->error;
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>File Complaint | Leyte Normal University</title>
    <style>
        :root {
            --lnu-blue: #003366;
            --lnu-gold: #FFCC00;
            --lnu-light: #f8f9fa;
        }
        
        body {
            background-color: #f0f2f5;
            color: #000;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        
        .lnu-header {
            background: var(--lnu-blue);
            color: white;
            border-bottom: 4px solid var(--lnu-gold);
        }
        
        .lnu-card {
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            border: none;
            margin-bottom: 2rem;
        }
        
        .lnu-card-header {
            background: var(--lnu-blue);
            color: white;
            padding: 1.5rem;
            font-weight: 600;
            font-size: 1.5rem;
        }
        
        .lnu-alert {
            border-left: 4px solid var(--lnu-blue);
            background-color: white;
        }
        
        .btn-lnu {
            background: var(--lnu-blue);
            color: white;
            border: none;
            padding: 10px 25px;
            font-weight: 600;
            transition: all 0.3s;
        }
        
        .btn-lnu:hover {
            background: #002147;
            color: var(--lnu-gold);
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        }
        
        .form-control:focus {
            border-color: var(--lnu-gold);
            box-shadow: 0 0 0 0.2rem rgba(255, 204, 0, 0.25);
        }
        
        .lnu-divider {
            height: 4px;
            background: linear-gradient(90deg, var(--lnu-blue) 50%, var(--lnu-gold) 50%);
            margin: 1.5rem 0;
        }
        
        .lnu-footer {
            background: var(--lnu-blue);
            color: white;
            padding: 15px 0;
            text-align: center;
            margin-top: auto;
            font-size: 0.9rem;
        }
        
        .error-highlight {
            border: 1px solid #dc3545 !important;
        }
        
        .error-message {
            color: #dc3545;
            font-size: 0.875rem;
            margin-top: 0.25rem;
        }
        
        .lnu-logo-container {
            background: white;
            border-radius: 50%;
            padding: 8px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        
        .form-label.required:after {
            content: " *";
            color: #dc3545;
        }
    </style>
</head>
<body>
    <header class="lnu-header py-3">
        <div class="container">
            <div class="d-flex align-items-center">
                <div class="mr-3 lnu-logo-container">
                    <img src="../assets/img/favicon.png" alt="LNU Logo" height="60">
                </div>
                <div>
                    <h3 class="mb-0">Leyte Normal University</h3>
                    <p class="mb-0">Complaint System</p>
                </div>
            </div>
        </div>
    </header>

    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="lnu-card shadow">
                    <div class="lnu-card-header text-center">
                        <h3 class="mb-0">File Complaint</h3>
                    </div>
                    
                    <div class="card-body">
                        <?php if ($success): ?>
                            <div class="alert alert-success text-center py-4">
                                <i class="fas fa-check-circle fa-3x mb-3" style="color: #28a745;"></i>
                                <h4>Complaint Submitted Successfully!</h4>
                                <p class="lead">Your complaint has been received and will be processed shortly.</p>
                            </div>
                        <?php elseif ($teacher): ?>
                            <div class="alert lnu-alert mb-4">
                                <div class="d-flex align-items-center">
                                    <div class="mr-3">
                                        <i class="fas fa-user-circle fa-2x" style="color: var(--lnu-blue);"></i>
                                    </div>
                                    <div>
                                        <h5>Professor Information</h5>
                                        <p class="mb-1"><strong>Name:</strong> <?= htmlspecialchars($teacher['name']) ?></p>
                                        <p class="mb-0"><strong>Office:</strong> <?= htmlspecialchars($teacher['office']) ?></p>
                                    </div>
                                </div>
                            </div>
                            
                            <?php if (!empty($error)): ?>
                                <div class="alert alert-danger">
                                    <i class="fas fa-exclamation-circle mr-2"></i><?= htmlspecialchars($error) ?>
                                </div>
                            <?php endif; ?>
                            
                            <div class="lnu-divider"></div>
                            
                            <form method="POST" id="complaintForm">
                                <div class="mb-4">
                                    <label class="form-label font-weight-bold">Your Name</label>
                                    <input type="text" name="name" class="form-control form-control-lg" 
                                           value="<?= htmlspecialchars($form_values['name']) ?>">
                                    <small class="form-text text-muted">(Optional)</small>
                                </div>
                                
                                <div class="mb-4">
                                    <label class="form-label font-weight-bold required">Email Address</label>
                                    <input type="email" name="email" class="form-control form-control-lg <?= !empty($error) && (strpos($error, 'Email') !== false) ? 'error-highlight' : '' ?>" 
                                           value="<?= htmlspecialchars($form_values['email']) ?>">
                                    <small class="form-text text-muted">We'll use this to contact you about your complaint</small>
                                </div>
                                
                                <div class="mb-4">
                                    <label class="form-label font-weight-bold required">Complaint Subject</label>
                                    <input type="text" name="subject" class="form-control form-control-lg <?= !empty($error) && (strpos($error, 'Subject') !== false) ? 'error-highlight' : '' ?>" 
                                           value="<?= htmlspecialchars($form_values['subject']) ?>" required>
                                </div>
                                
                                <div class="mb-4">
                                    <label class="form-label font-weight-bold required">Complaint Details</label>
                                    <textarea name="message" class="form-control form-control-lg <?= !empty($error) && (strpos($error, 'Message') !== false) ? 'error-highlight' : '' ?>" 
                                              rows="5" required placeholder="Please describe your complaint in detail..."><?= htmlspecialchars($form_values['message']) ?></textarea>
                                </div>
                                
                                <div class="text-center mt-5">
                                    <button type="submit" class="btn btn-lnu btn-lg px-5 py-3">
                                        <i class="fas fa-paper-plane mr-2"></i>Submit Complaint
                                    </button>
                                </div>
                            </form>
                        <?php else: ?>
                            <div class="alert alert-danger text-center py-4">
                                <i class="fas fa-exclamation-triangle fa-3x mb-3" style="color: #dc3545;"></i>
                                <h3>Invalid Professor Selection</h3>
                                <p class="lead">Please scan a valid QR code to file a complaint.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <footer class="lnu-footer">
        <div class="container">
            <div class="row">
                <div class="col-12">
                    <p class="mb-0">Leyte Normal University Complaint System &copy; <?= date('Y') ?></p>
                    <p class="mb-0">Paterno Street, Tacloban City, Leyte, Philippines</p>
                </div>
            </div>
        </div>
    </footer>
    
    <script src="../../assets/js/complaints.js"></script>
</body>
</html>