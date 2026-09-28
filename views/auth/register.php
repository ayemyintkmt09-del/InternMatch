<?php
require_once '../../config/database.php';
require_once '../../classes/User.php';

$message = '';
$isError = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $database = new Database();
    $db = $database->getConnection();
    
    $user = new User($db);
    $result = $user->register($_POST['name'], $_POST['email'], $_POST['password'], $_POST['role']);

    if ($result === true) {
        $message = "Registration successful! You can now log in.";
    } else {
        $isError = true;
        $message = $result;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>InternMatch - Register</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f8f9fa; padding-top: 40px; }
        .card-auth { border: none; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); }
    </style>
</head>
<body>
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card card-auth p-4 bg-white">
                <h3 class="text-center fw-bold text-success mb-4">Create an Account</h3>
                <?php if (!empty($message)): ?>
                    <div class="alert <?php echo $isError ? 'alert-danger' : 'alert-success'; ?> py-2"><?php echo $message; ?></div>
                <?php endif; ?>
                <form method="POST" action="">
                    <div class="mb-3">
                        <label class="form-label">Full Name / Company Name</label>
                        <input type="text" name="name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email Address</label>
                        <input type="email" name="email" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Password</label>
                        <input type="password" name="password" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Register As</label>
                        <select name="role" class="form-select" required>
                            <option value="student">Student</option>
                            <option value="company">Company / Organization</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-success w-100 py-2">Register Account</button>
                </form>
                <p class="text-center mt-3 text-muted small">Already registered? <a href="login.php" class="text-success">Login here</a></p>
            </div>
        </div>
    </div>
</div>
</body>
</html>