<?php
require_once 'config.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$error = '';

// Fetch user information
$query = "SELECT username FROM users WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param('i', $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$username = htmlspecialchars($user['username']);

// Fetch user contacts
$query = "SELECT id, name, phone FROM contacts WHERE user_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param('i', $user_id);
$stmt->execute();
$result = $stmt->get_result();
$contacts = $result->fetch_all(MYSQLI_ASSOC);

// Handle form submissions for creating, updating, and deleting contacts
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['create'])) {
        $name = htmlspecialchars($_POST['name']);
        $phone = htmlspecialchars($_POST['phone']);
        if (!empty($name) && !empty($phone)) {
            // Check if phone number already exists for this user
            $query = "SELECT id FROM contacts WHERE phone = ? AND user_id = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param('si', $phone, $user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result->num_rows > 0) {
                $error = 'This phone number already exists in your contacts.';
            } else {
                $query = "INSERT INTO contacts (user_id, name, phone) VALUES (?, ?, ?)";
                $stmt = $conn->prepare($query);
                $stmt->bind_param('iss', $user_id, $name, $phone);
                if ($stmt->execute()) {
                    header('Location: dashboard.php');
                    exit;
                } else {
                    $error = 'Database error: ' . $stmt->error;
                }
            }
        } else {
            $error = 'Please fill in all fields.';
        }
    } elseif (isset($_POST['update'])) {
        $contact_id = $_POST['contact_id'];
        $name = htmlspecialchars($_POST['name']);
        $phone = htmlspecialchars($_POST['phone']);
        if (!empty($name) && !empty($phone)) {
            $query = "UPDATE contacts SET name = ?, phone = ? WHERE id = ? AND user_id = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param('ssii', $name, $phone, $contact_id, $user_id);
            if ($stmt->execute()) {
                header('Location: dashboard.php');
                exit;
            } else {
                $error = 'Database error: ' . $stmt->error;
            }
        } else {
            $error = 'Please fill in all fields.';
        }
    } elseif (isset($_POST['delete'])) {
        $contact_id = $_POST['contact_id'];
        $query = "DELETE FROM contacts WHERE id = ? AND user_id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param('ii', $contact_id, $user_id);
        if ($stmt->execute()) {
            header('Location: dashboard.php');
            exit;
        } else {
            $error = 'Database error: ' . $stmt->error;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="container mt-5">
        <b><p class="fs-1">Welcome, <?php echo $username; ?>!</p></b>
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h2 class="text-center">Dashboard</h2>
            <a href="logout.php" class="btn btn-danger">Logout</a>
        </div>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <h3>Your Contacts</h3>
        <table class="table">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Phone</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($contacts as $contact): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($contact['name']); ?></td>
                        <td><?php echo htmlspecialchars($contact['phone']); ?></td>
                        <td>
                            <form method="post" style="display:inline;">
                                <input type="hidden" name="contact_id" value="<?php echo $contact['id']; ?>">
                                <input type="hidden" name="name" value="<?php echo htmlspecialchars($contact['name']); ?>">
                                <input type="hidden" name="phone" value="<?php echo htmlspecialchars($contact['phone']); ?>">
                                <button type="submit" name="edit" class="btn btn-primary btn-sm">Edit</button>
                            </form>
                            <form method="post" style="display:inline;">
                                <input type="hidden" name="contact_id" value="<?php echo $contact['id']; ?>">
                                <button type="submit" name="delete" class="btn btn-danger btn-sm">Delete</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <h3>Add New Contact</h3>
        <form method="post">
            <div class="form-outline mb-4">
                <label class="form-label" for="name">Name</label>
                <input type="text" id="name" class="form-control" name="name" required />
            </div>
            <div class="form-outline mb-4">
                <label class="form-label" for="phone">Phone</label>
                <input type="text" id="phone" class="form-control" name="phone" required />
            </div>
            <button type="submit" name="create" class="btn btn-success mb-4">Add Contact</button>
        </form>

        <!-- Edit contact modal -->
        <?php if (isset($_POST['edit'])): ?>
            <div class="modal fade show" id="editModal" tabindex="-1" style="display:block;">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Edit Contact</h5>
                        </div>
                        <div class="modal-body">
                            <form method="post">
                                <input type="hidden" name="contact_id" value="<?php echo $_POST['contact_id']; ?>">
                                <div class="form-outline mb-4">
                                    <label class="form-label" for="name">Name</label>
                                    <input type="text" id="name" class="form-control" name="name" value="<?php echo htmlspecialchars($_POST['name']); ?>" required />
                                </div>
                                <div class="form-outline mb-4">
                                    <label class="form-label" for="phone">Phone</label>
                                    <input type="text" id="phone" class="form-control" name="phone" value="<?php echo htmlspecialchars($_POST['phone']); ?>" required />
                                </div>
                                <button type="submit" name="update" class="btn btn-primary">Update Contact</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.min.js"></script>
</body>
</html>
