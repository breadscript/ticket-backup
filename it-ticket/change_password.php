<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Only process POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once('conn/db.php');
    $conn = connectionDB();
    
    // Get user ID from session
    $user_id = $_SESSION['userid'] ?? 0;
    
    if (!$user_id) {
        echo "User not logged in";
        exit();
    }
    
    // Get and sanitize input
    $current_password = trim($_POST['current_password'] ?? '');
    $new_password = trim($_POST['new_password'] ?? '');
    $captcha_answer = trim($_POST['captcha_answer'] ?? '');
    
    // Server-side validation
    if (empty($current_password)) {
        echo "Current password is required";
        exit();
    }
    
    if (empty($new_password)) {
        echo "New password is required";
        exit();
    }
    
    if (empty($captcha_answer)) {
        echo "Security check answer is required";
        exit();
    }
    
    // Validate security check answer
    $correctAnswer = $_SESSION['captcha_answer'] ?? '';
    
    // For division results, round to 2 decimal places
    $userAnswer = round(floatval($captcha_answer), 2);
    $correctAnswer = round(floatval($correctAnswer), 2);
    
    if ($userAnswer != $correctAnswer) {
        echo "Incorrect arithmetic answer. Please try again.";
        exit();
    }
    
    try {
        // Get current password from database
        $stmt = $conn->prepare("SELECT password FROM sys_usertb WHERE id = ?");
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $conn->error);
        }
        
        $stmt->bind_param("i", $user_id);
        if (!$stmt->execute()) {
            throw new Exception("Execute failed: " . $stmt->error);
        }
        
        $result = $stmt->get_result();
        if (!$result) {
            throw new Exception("Get result failed: " . $stmt->error);
        }
        
        $user = $result->fetch_assoc();
        if (!$user) {
            echo "User not found";
            exit();
        }
        
        // Verify current password
        if (!password_verify($current_password, $user['password'])) {
            echo "Current password is incorrect";
            exit();
        }
        
        // Verify current and new passwords are different
        if (password_verify($new_password, $user['password'])) {
            echo "New password must be different from current password";
            exit();
        }
        
        // Hash new password
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        
        // Update password
        $update_stmt = $conn->prepare("UPDATE sys_usertb SET password = ? WHERE id = ?");
        if (!$update_stmt) {
            throw new Exception("Prepare update failed: " . $conn->error);
        }
        
        $update_stmt->bind_param("si", $hashed_password, $user_id);
        if (!$update_stmt->execute()) {
            throw new Exception("Update failed: " . $update_stmt->error);
        }
        
        if ($update_stmt->affected_rows > 0) {
            echo "success";
        } else {
            echo "No changes made to password";
        }
        
        $update_stmt->close();
        
    } catch (Exception $e) {
        error_log("Password change error: " . $e->getMessage());
        echo "An error occurred while changing the password";
    } finally {
        if (isset($stmt)) {
            $stmt->close();
        }
        if (isset($conn)) {
            $conn->close();
        }
    }
    exit();
}

// Generate random arithmetic problem
$num1 = rand(1, 10);
$num2 = rand(1, 10);
$operator = rand(0, 1) ? '+' : '-';

switch($operator) {
    case '+':
        $answer = $num1 + $num2;
        break;
    case '-':
        $answer = $num1 - $num2;
        break;
}

// Store answer in session
$_SESSION['captcha_answer'] = $answer;
?>

<div class="password-container">
    <div class="password-header">
        <h2><i class="ace-icon fa fa-key"></i> Change Password</h2>
    </div>

    <div class="password-content">
        <form id="change-password-form">
            <div class="form-group">
                <label class="control-label">Current Password</label>
                <div class="input-wrapper">
                    <i class="fa fa-lock input-icon"></i>
                    <input type="password" id="current_password" name="current_password" required>
                </div>
            </div>

            <div class="form-group">
                <label class="control-label">New Password</label>
                <div class="input-wrapper">
                    <i class="fa fa-key input-icon"></i>
                    <input type="password" id="new_password" name="new_password" required>
                </div>
            </div>

            <div class="form-group">
                <label class="control-label">Confirm New Password</label>
                <div class="input-wrapper">
                    <i class="fa fa-key input-icon"></i>
                    <input type="password" id="confirm_new_password" name="confirm_new_password" required>
                </div>
            </div>

            <div class="form-group">
                <label class="control-label">Security Check</label>
                <div class="captcha-container">
                    <div class="captcha-question">
                        <?php echo $num1 . ' ' . $operator . ' ' . $num2 . ' = '; ?>
                    </div>
                    <div class="input-wrapper captcha-input">
                        <input type="number" id="captcha_answer" name="captcha_answer" required>
                    </div>
                </div>
            </div>

            <div class="alert error" id="password-error" style="display: none;">
                <i class="fa fa-exclamation-circle alert-icon"></i>
                <span id="error-message"></span>
                <button type="button" class="close-alert">&times;</button>
            </div>

            <div class="alert success" id="password-success" style="display: none;">
                <i class="fa fa-check-circle alert-icon"></i>
                <span>Password successfully updated!</span>
                <button type="button" class="close-alert">&times;</button>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary">
                    <i class="fa fa-check"></i> Update Password
                </button>
            </div>
        </form>
    </div>
</div>

<style>
.password-container {
    max-width: 600px;
    margin: 1rem auto;
    background: #fff;
    border: 1px solid #ddd;
    border-radius: 4px;
}

.password-header {
    padding: 1rem;
    border-bottom: 1px solid #ddd;
    background: #f8f9fa;
}

.password-header h2 {
    margin: 0;
    font-size: 1.2rem;
    color: #333;
}

.password-content {
    padding: 1.5rem;
}

.form-group {
    margin-bottom: 1rem;
}

.control-label {
    display: inline-block;
    width: 180px;
    margin-bottom: 0.5rem;
    color: #555;
    font-weight: normal;
}

.input-wrapper {
    display: inline-block;
    position: relative;
    width: 250px;
}

.input-icon {
    position: absolute;
    left: 10px;
    top: 50%;
    transform: translateY(-50%);
    color: #999;
}

.form-group input {
    width: 100%;
    padding: 0.5rem 0.75rem 0.5rem 2rem;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 0.9rem;
}

.form-group input:focus {
    border-color: #4A90E2;
    outline: none;
    box-shadow: 0 0 0 2px rgba(74, 144, 226, 0.1);
}

.captcha-container {
    display: inline-flex;
    align-items: center;
    gap: 1rem;
}

.captcha-question {
    background: #f5f5f5;
    padding: 0.5rem 1rem;
    border-radius: 4px;
    font-weight: normal;
}

.captcha-input {
    width: 100px;
}

.captcha-input input {
    padding-left: 0.75rem;
}

.form-actions {
    margin-top: 1.5rem;
    padding-top: 1rem;
    border-top: 1px solid #eee;
}

.btn {
    padding: 0.5rem 1rem;
    border: none;
    border-radius: 4px;
    font-size: 0.9rem;
    cursor: pointer;
}

.btn-primary {
    background: #4A90E2;
    color: white;
}

.btn-primary:hover {
    background: #357ABD;
}

.alert {
    padding: 0.75rem 1rem;
    border-radius: 4px;
    margin-bottom: 1rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.9rem;
}

.alert.error {
    background: #FEE2E2;
    color: #DC2626;
    border: 1px solid #FECACA;
}

.alert.success {
    background: #DCFCE7;
    color: #16A34A;
    border: 1px solid #BBF7D0;
}

.close-alert {
    background: none;
    border: none;
    color: inherit;
    font-size: 1.1rem;
    cursor: pointer;
    padding: 0;
    margin-left: auto;
}

@media (max-width: 576px) {
    .control-label {
        display: block;
        width: 100%;
        margin-bottom: 0.5rem;
    }

    .input-wrapper {
        display: block;
        width: 100%;
    }

    .captcha-container {
        flex-direction: column;
        align-items: stretch;
    }

    .captcha-input {
        width: 100%;
    }
}
</style>

<script>
$(document).ready(function() {
    $('#change-password-form').on('submit', function(e) {
        e.preventDefault();
        
        // Hide any existing alerts
        $('#password-error, #password-success').hide();
        
        // Validate passwords match
        var newPassword = $('#new_password').val();
        var confirmPassword = $('#confirm_new_password').val();
        
        if (newPassword !== confirmPassword) {
            $('#error-message').text('New passwords do not match!');
            $('#password-error').show();
            return;
        }
        
        // Submit form via AJAX with captcha answer
        $.ajax({
            url: 'change_password.php',
            type: 'POST',
            data: {
                current_password: $('#current_password').val(),
                new_password: newPassword,
                captcha_answer: $('#captcha_answer').val()
            },
            success: function(response) {
                if (response === 'success') {
                    $('#password-success').show();
                    $('#change-password-form')[0].reset();
                    // Reload the page to get a new arithmetic problem
                    setTimeout(function() {
                        location.reload();
                    }, 2000);
                } else {
                    $('#error-message').text(response);
                    $('#password-error').show();
                }
            },
            error: function() {
                $('#error-message').text('An error occurred. Please try again.');
                $('#password-error').show();
            }
        });
    });
});
</script> 