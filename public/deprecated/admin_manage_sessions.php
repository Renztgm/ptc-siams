<?php
session_start();
include('../config/db_config.php');

// Check if admin is logged in (basic check - you may need stronger authentication)
// For now, we'll allow access if they're authenticated
$is_admin = isset($_SESSION['admin_user']);

if (!$is_admin) {
    // Simple hardcoded check - you should implement proper admin authentication
    if (isset($_POST['login_password']) && $_POST['login_password'] === 'admin123') {
        $_SESSION['admin_user'] = true;
        $is_admin = true;
    } elseif (!isset($_POST['login_password'])) {
        // Show login form
        ?>
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Admin Login</title>
            <style>
                body {
                    font-family: Arial, sans-serif;
                    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                    height: 100vh;
                    display: flex;
                    justify-content: center;
                    align-items: center;
                }
                .login-box {
                    background: white;
                    padding: 40px;
                    border-radius: 10px;
                    box-shadow: 0 10px 40px rgba(0,0,0,0.2);
                    width: 300px;
                }
                h1 {
                    text-align: center;
                    color: #333;
                }
                input {
                    width: 100%;
                    padding: 10px;
                    margin: 10px 0;
                    border: 1px solid #ccc;
                    border-radius: 5px;
                }
                button {
                    width: 100%;
                    padding: 10px;
                    background: #667eea;
                    color: white;
                    border: none;
                    border-radius: 5px;
                    cursor: pointer;
                }
            </style>
        </head>
        <body>
            <div class="login-box">
                <h1>Admin Login</h1>
                <form method="POST">
                    <input type="password" name="login_password" placeholder="Enter admin password" required autofocus>
                    <button type="submit">Login</button>
                </form>
            </div>
        </body>
        </html>
        <?php
        exit;
    } else {
        ?>
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <title>Invalid Login</title>
            <style>
                body {
                    font-family: Arial, sans-serif;
                    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                    height: 100vh;
                    display: flex;
                    justify-content: center;
                    align-items: center;
                }
                .message-box {
                    background: white;
                    padding: 40px;
                    border-radius: 10px;
                    text-align: center;
                    box-shadow: 0 10px 40px rgba(0,0,0,0.2);
                }
                button {
                    padding: 10px 20px;
                    background: #667eea;
                    color: white;
                    border: none;
                    border-radius: 5px;
                    cursor: pointer;
                    margin-top: 20px;
                }
            </style>
        </head>
        <body>
            <div class="message-box">
                <h2>Invalid Password</h2>
                <button onclick="history.back()">Try Again</button>
            </div>
        </body>
        </html>
        <?php
        exit;
    }
}

$message = '';
$error = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] == 'add_session') {
            $session_name = trim($_POST['session_name']);
            $exam_date = $_POST['exam_date'];
            $exam_start_time = $_POST['exam_start_time'];
            $exam_end_time = $_POST['exam_end_time'];
            $exam_location = trim($_POST['exam_location']);
            $exam_link = trim($_POST['exam_link']);
            $capacity = intval($_POST['capacity']);

            if (empty($session_name) || empty($exam_date) || empty($exam_start_time)) {
                $error = 'Please fill in all required fields';
            } else {
                $insert = "INSERT INTO exam_sessions (session_name, exam_date, exam_start_time, exam_end_time, exam_location, exam_link, capacity, status) 
                          VALUES (?, ?, ?, ?, ?, ?, ?, 'scheduled')";
                
                $stmt = $conn->prepare($insert);
                $stmt->bind_param('ssssssI', $session_name, $exam_date, $exam_start_time, $exam_end_time, $exam_location, $exam_link, $capacity);
                
                if ($stmt->execute()) {
                    $message = 'Exam session created successfully!';
                } else {
                    $error = 'Failed to create session: ' . $stmt->error;
                }
                $stmt->close();
            }
        } elseif ($_POST['action'] == 'delete_session') {
            $session_id = intval($_POST['session_id']);
            $delete = "DELETE FROM exam_sessions WHERE id = ?";
            
            $stmt = $conn->prepare($delete);
            $stmt->bind_param('i', $session_id);
            
            if ($stmt->execute()) {
                $message = 'Exam session deleted successfully!';
            } else {
                $error = 'Failed to delete session';
            }
            $stmt->close();
        } elseif ($_POST['action'] == 'update_session') {
            $session_id = intval($_POST['session_id']);
            $session_name = trim($_POST['session_name']);
            $exam_date = $_POST['exam_date'];
            $exam_start_time = $_POST['exam_start_time'];
            $exam_end_time = $_POST['exam_end_time'];
            $exam_location = trim($_POST['exam_location']);
            $exam_link = trim($_POST['exam_link']);
            $capacity = intval($_POST['capacity']);
            $status = $_POST['status'];

            $update = "UPDATE exam_sessions SET session_name = ?, exam_date = ?, exam_start_time = ?, exam_end_time = ?, exam_location = ?, exam_link = ?, capacity = ?, status = ? WHERE id = ?";
            
            $stmt = $conn->prepare($update);
            $stmt->bind_param('ssssssssi', $session_name, $exam_date, $exam_start_time, $exam_end_time, $exam_location, $exam_link, $capacity, $status, $session_id);
            
            if ($stmt->execute()) {
                $message = 'Exam session updated successfully!';
            } else {
                $error = 'Failed to update session';
            }
            $stmt->close();
        }
    }
}

// Get all exam sessions
$result = $conn->query("SELECT * FROM exam_sessions ORDER BY exam_date DESC");
$sessions = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $sessions[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Exam Session Management</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f5f5;
            padding: 20px;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            border-radius: 10px;
            margin-bottom: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .header h1 {
            font-size: 28px;
        }

        .logout-btn {
            background: rgba(255,255,255,0.2);
            border: 2px solid white;
            color: white;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            transition: 0.3s;
        }

        .logout-btn:hover {
            background: white;
            color: #667eea;
        }

        .message {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
            display: none;
        }

        .message.show {
            display: block;
        }

        .success {
            background: #e8f5e9;
            color: #2e7d32;
            border-left: 4px solid #2e7d32;
        }

        .error {
            background: #ffebee;
            color: #c62828;
            border-left: 4px solid #c62828;
        }

        .form-section {
            background: white;
            padding: 30px;
            border-radius: 10px;
            margin-bottom: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .form-section h2 {
            color: #333;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #667eea;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 15px;
            margin-bottom: 15px;
        }

        input, select, textarea {
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
        }

        input:focus, select:focus, textarea:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 5px rgba(102, 126, 234, 0.3);
        }

        button {
            background: #667eea;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: 600;
            transition: 0.3s;
        }

        button:hover {
            background: #764ba2;
        }

        .sessions-table {
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th {
            background: #667eea;
            color: white;
            padding: 15px;
            text-align: left;
            font-weight: 600;
        }

        td {
            padding: 12px 15px;
            border-bottom: 1px solid #eee;
        }

        tr:hover {
            background: #f9f9f9;
        }

        .status-badge {
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }

        .status-scheduled {
            background: #e3f2fd;
            color: #1565c0;
        }

        .status-completed {
            background: #e8f5e9;
            color: #2e7d32;
        }

        .status-cancelled {
            background: #ffebee;
            color: #c62828;
        }

        .action-buttons {
            display: flex;
            gap: 10px;
        }

        .edit-btn, .delete-btn {
            padding: 6px 12px;
            font-size: 12px;
            border: none;
            border-radius: 3px;
            cursor: pointer;
            transition: 0.3s;
        }

        .edit-btn {
            background: #2196f3;
        }

        .edit-btn:hover {
            background: #1976d2;
        }

        .delete-btn {
            background: #f44336;
        }

        .delete-btn:hover {
            background: #d32f2f;
        }

        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 1000;
            justify-content: center;
            align-items: center;
        }

        .modal.show {
            display: flex;
        }

        .modal-content {
            background: white;
            padding: 30px;
            border-radius: 10px;
            width: 90%;
            max-width: 500px;
            max-height: 80vh;
            overflow-y: auto;
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #667eea;
        }

        .close-btn {
            background: none;
            border: none;
            font-size: 24px;
            cursor: pointer;
            color: #999;
        }

        .close-btn:hover {
            color: #333;
        }

        .modal-form-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 15px;
            margin-bottom: 15px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📋 Exam Session Management</h1>
            <form method="POST" style="margin: 0;">
                <button type="submit" name="logout" value="1" class="logout-btn">Logout</button>
            </form>
        </div>

        <?php if ($message): ?>
            <div class="message success show"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="message error show"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <div class="form-section">
            <h2>Create New Exam Session</h2>
            <form method="POST">
                <input type="hidden" name="action" value="add_session">
                <div class="form-grid">
                    <input type="text" name="session_name" placeholder="Session Name (e.g., Morning Session 1)" required>
                    <input type="date" name="exam_date" required>
                    <input type="time" name="exam_start_time" required>
                    <input type="time" name="exam_end_time" required>
                    <input type="text" name="exam_location" placeholder="Location (e.g., Online, Room 101)">
                    <input type="url" name="exam_link" placeholder="Exam Link (optional)">
                    <input type="number" name="capacity" placeholder="Capacity (0 for unlimited)" min="0">
                </div>
                <button type="submit">Create Session</button>
            </form>
        </div>

        <div class="sessions-table">
            <h2 style="padding: 20px 15px; background: #f9f9f9; margin: 0;">Exam Sessions</h2>
            <?php if (count($sessions) > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Session Name</th>
                            <th>Date</th>
                            <th>Time</th>
                            <th>Location</th>
                            <th>Capacity</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($sessions as $session): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($session['session_name']); ?></td>
                                <td><?php echo (new DateTime($session['exam_date']))->format('M d, Y'); ?></td>
                                <td><?php echo (new DateTime($session['exam_start_time']))->format('h:i A'); ?> - <?php echo (new DateTime($session['exam_end_time']))->format('h:i A'); ?></td>
                                <td><?php echo htmlspecialchars($session['exam_location'] ?: 'Online'); ?></td>
                                <td><?php echo $session['capacity'] > 0 ? $session['capacity'] : 'Unlimited'; ?></td>
                                <td><span class="status-badge status-<?php echo $session['status']; ?>"><?php echo ucfirst($session['status']); ?></span></td>
                                <td>
                                    <div class="action-buttons">
                                        <button class="edit-btn" onclick="editSession(<?php echo htmlspecialchars(json_encode($session)); ?>)">Edit</button>
                                        <form method="POST" style="display: inline;" onsubmit="return confirm('Delete this session?');">
                                            <input type="hidden" name="action" value="delete_session">
                                            <input type="hidden" name="session_id" value="<?php echo $session['id']; ?>">
                                            <button type="submit" class="delete-btn">Delete</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p style="padding: 20px 15px; text-align: center; color: #999;">No exam sessions created yet.</p>
            <?php endif; ?>
        </div>
    </div>

    <div id="editModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Edit Exam Session</h3>
                <button class="close-btn" onclick="closeModal()">&times;</button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="update_session">
                <input type="hidden" name="session_id" id="edit_session_id">
                <div class="modal-form-grid">
                    <input type="text" name="session_name" id="edit_session_name" placeholder="Session Name" required>
                    <input type="date" name="exam_date" id="edit_exam_date" required>
                    <input type="time" name="exam_start_time" id="edit_exam_start_time" required>
                    <input type="time" name="exam_end_time" id="edit_exam_end_time" required>
                    <input type="text" name="exam_location" id="edit_exam_location" placeholder="Location">
                    <input type="url" name="exam_link" id="edit_exam_link" placeholder="Exam Link">
                    <input type="number" name="capacity" id="edit_capacity" placeholder="Capacity" min="0">
                    <select name="status" id="edit_status" required>
                        <option value="scheduled">Scheduled</option>
                        <option value="ongoing">Ongoing</option>
                        <option value="completed">Completed</option>
                        <option value="cancelled">Cancelled</option>
                    </select>
                </div>
                <div style="display: flex; gap: 10px;">
                    <button type="submit">Save Changes</button>
                    <button type="button" onclick="closeModal()" style="background: #999;">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function editSession(session) {
            document.getElementById('edit_session_id').value = session.id;
            document.getElementById('edit_session_name').value = session.session_name;
            document.getElementById('edit_exam_date').value = session.exam_date;
            document.getElementById('edit_exam_start_time').value = session.exam_start_time;
            document.getElementById('edit_exam_end_time').value = session.exam_end_time;
            document.getElementById('edit_exam_location').value = session.exam_location || '';
            document.getElementById('edit_exam_link').value = session.exam_link || '';
            document.getElementById('edit_capacity').value = session.capacity || '0';
            document.getElementById('edit_status').value = session.status;
            document.getElementById('editModal').classList.add('show');
        }

        function closeModal() {
            document.getElementById('editModal').classList.remove('show');
        }

        window.addEventListener('click', function(e) {
            const modal = document.getElementById('editModal');
            if (e.target == modal) {
                closeModal();
            }
        });
    </script>
</body>
</html>
