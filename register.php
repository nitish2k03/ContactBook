<?php
require_once 'config.php';

$error = '';

$username = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = htmlspecialchars($_POST['username']);
    $email = htmlspecialchars($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // Validate input data
    if (empty($username) || empty($email) || empty($password) || empty($confirm_password)) {
        $error = 'Please fill in all fields.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid email format.';
    } elseif ($password != $confirm_password) {
        $error = 'Passwords do not match.';
    } else {
        // Check if the username already exists
        $query = "SELECT id FROM users WHERE username = ?";
        if ($stmt = $conn->prepare($query)) {
            $stmt->bind_param('s', $username);
            $stmt->execute();
            $stmt->store_result();
            
            if ($stmt->num_rows > 0) {
                $error = 'Username already exists.';
            } else {
                // Hash password
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);

                // Insert user into database
                $query = "INSERT INTO users (username, email, password) VALUES (?, ?, ?)";
                if ($stmt = $conn->prepare($query)) {
                    $stmt->bind_param('sss', $username, $email, $hashed_password);
                    if ($stmt->execute()) {
                        // Redirect to login page
                        header('Location: login.php');
                        exit;
                    } else {
                        $error = 'Database error: ' . $stmt->error;
                    }
                } else {
                    $error = 'Database error: ' . $conn->error;
                }
            }
        } else {
            $error = 'Database error: ' . $conn->error;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Sign Up</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/bootstrap.css">
    <script>
      function validateForm() {
        var password = document.getElementById("password").value;
        var retypePassword = document.getElementById("confirm_password").value;
        if (password !== retypePassword) {
          alert("Passwords do not match!");
          return false;
        }
        return true;
      }
    </script>
</head>
<body>
    <section class="vh-75 bg-image py-2" style="background-image: url('./images/img4.webp');">
        <div class="mask d-flex align-items-center h-100 gradient-custom-3">
            <div class="container h-100">
                <div class="row d-flex justify-content-center align-items-center h-80">
                    <div class="col-12 col-md-9 col-lg-7 col-xl-6">
                        <div class="card" style="border-radius: 15px;">
                            <div class="card-body p-5">
                                <h2 class="text-uppercase text-center mb-5">Create an account</h2>

                                <?php if (!empty($error)): ?>
                                    <div class="alert alert-danger">
                                        <?php echo htmlspecialchars($error); ?>
                                    </div>
                                <?php endif; ?>

                                <form method="post" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" onsubmit="return validateForm()">

                                    <div class="form-outline mb-4">
                                        <label class="form-label" for="username">Username</label>
                                        <input type="text" id="username" class="form-control form-control-lg" name="username" value="<?php echo htmlspecialchars($username); ?>" required />
                                    </div>

                                    <div class="form-outline mb-4">
                                        <label class="form-label" for="email">Your Email</label>
                                        <input type="email" id="email" class="form-control form-control-lg" name="email" value="<?php echo htmlspecialchars($email); ?>" required />
                                    </div>

                                    <div class="form-outline mb-4">
                                        <label class="form-label" for="password">Password</label>
                                        <input type="password" id="password" class="form-control form-control-lg" name="password" required />
                                    </div>

                                    <div class="form-outline mb-4">
                                        <label class="form-label" for="confirm_password">Repeat your password</label>
                                        <input type="password" id="confirm_password" class="form-control form-control-lg" name="confirm_password" required />
                                    </div>

                                    <div class="form-check d-flex justify-content-center mb-5"></div>

                                    <div class="d-flex justify-content-center">
                                        <button type="submit" class="btn btn-success btn-block btn-lg gradient-custom-4 text-body">Register</button>
                                    </div>

                                    <p class="text-center text-muted mt-5 mb-0">Have already an account? <a href="login.php" class="fw-bold text-body"><u>Login here</u></a></p>
                                </form>

                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Bootstrap JS and dependencies -->
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.min.js"></script>
</body>
</html>
