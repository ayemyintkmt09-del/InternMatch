<?php
require_once '../../config/database.php';
require_once '../../classes/User.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $database = new Database();
    $db = $database->getConnection();
    
    $user = new User($db);
    $result = $user->login($_POST['email'], $_POST['password']);

    if ($result === true) {
        // Role-based redirection mapping to correct directory paths
        switch ($_SESSION['role']) {
            case 'student':
                header("Location: ../student/stu_dashboard.php");
                break;
            case 'company':
                header("Location: ../company/com_dashboard.php");
                break;
            case 'admin':
                header("Location: ../admin/ad_dashboard.php");
                break;
        }
        exit();
    } else {
        $error = $result;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>InternMatch - Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f8f9fa; display: flex; align-items: center; justify-content: center; height: 100vh; }
        .card-auth { border: none; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); }
    </style>
</head>
<body>
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-5">
            <div class="card card-auth p-4 bg-white">
                <h3 class="text-center fw-bold text-success mb-4">InternMatch Login</h3>
                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger py-2"><?php echo $error; ?></div>
                <?php endif; ?>
                <form method="POST" action="">
                    <div class="mb-3">
                        <label class="form-label">Email Address</label>
                        <input type="email" name="email" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Password</label>
                        <input type="password" name="password" class="form-control" required>
                    </div>
                    <button type="submit" class="btn btn-success w-100 py-2">Secure Login</button>
                </form>
                <p class="text-center mt-3 text-muted small">Don't have an account? <a href="register.php" class="text-success">Register here</a></p>
            </div>
        </div>
    </div>
</div>
</body>
</html>