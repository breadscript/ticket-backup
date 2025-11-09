<?php
require_once '../phpmailer/src/PHPMailer.php';
require_once '../phpmailer/src/SMTP.php';
require_once '../phpmailer/src/Exception.php';
require_once('email_utils.php');

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

/**
 * Sends a notification email for task activities
 * 
 * @param object $conn Database connection
 * @param int $taskId Task ID
 * @param string $subject Task subject
 * @param string $activity Activity type (Created, Updated, etc.)
 * @param array|string $assignees Assignee IDs (array or comma-separated string)
 * @param string $createdBy User ID who created/updated the task
 * @return bool Success status
 */
function sendTaskNotification($conn, $taskId, $subject, $activity, $assignees, $createdBy) {
    try {
        // Get the SRN ID for the task and status information - combine queries for efficiency
        $srnQuery = "SELECT t.srn_id, t.statusid, s.statusname 
                     FROM pm_projecttasktb t 
                     LEFT JOIN sys_taskstatustb s ON t.statusid = s.id 
                     WHERE t.id = ?";
        $stmt = $conn->prepare($srnQuery);
        $stmt->bind_param("i", $taskId);
        $stmt->execute();
        $result = $stmt->get_result();
        $taskData = $result->fetch_assoc();
        
        $srnId = $taskData['srn_id'] ?? $taskId;
        $statusId = $taskData['statusid'] ?? 1; // Default to status 1 if not set
        $statusName = $taskData['statusname'] ?? 'New'; // Get status name directly
        
        // Admin email for CC
        $adminEmail = 'mmredondo@carmensbest.com.ph';

        // Get creator's email
        $creatorQuery = "SELECT emailadd, CONCAT(user_firstname, ' ', user_lastname) as fullname 
                        FROM sys_usertb WHERE id = ?";
        $stmt = $conn->prepare($creatorQuery);
        if (!$stmt) {
            error_log("Failed to prepare creator query");
            return false;
        }
        
        $stmt->bind_param("s", $createdBy);
        $stmt->execute();
        $result = $stmt->get_result();
        $creatorInfo = $result->fetch_assoc();
        $creatorName = $creatorInfo['fullname'] ?? 'Unknown User';
        $creatorEmail = $creatorInfo['emailadd'] ?? '';

        // Get assignee emails and names - optimize with a single query if possible
        $assigneeEmails = [];
        $assigneeNames = [];
        if (!empty($assignees) && $assignees !== 'null') {
            // Handle both string and array input
            $assigneeArray = is_array($assignees) ? $assignees : explode(',', $assignees);
            
            // Use a single query with IN clause if there are multiple assignees
            if (count($assigneeArray) > 1) {
                $placeholders = str_repeat('?,', count($assigneeArray) - 1) . '?';
                $assigneeQuery = "SELECT id, emailadd, CONCAT(user_firstname, ' ', user_lastname) as fullname 
                                 FROM sys_usertb WHERE id IN ($placeholders)";
                $stmt = $conn->prepare($assigneeQuery);
                
                if ($stmt) {
                    $types = str_repeat('s', count($assigneeArray));
                    $stmt->bind_param($types, ...$assigneeArray);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    
                    while ($row = $result->fetch_assoc()) {
                        $assigneeEmails[] = $row['emailadd'];
                        $assigneeNames[] = $row['fullname'];
                    }
                }
            } else {
                // Use the existing query for a single assignee
                foreach ($assigneeArray as $assigneeId) {
                    if (empty($assigneeId)) continue;
                    
                    $stmt = $conn->prepare($creatorQuery);
                    $stmt->bind_param("s", $assigneeId);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    if ($row = $result->fetch_assoc()) {
                        $assigneeEmails[] = $row['emailadd'];
                        $assigneeNames[] = $row['fullname'];
                    }
                }
            }
        }

        $mail = new PHPMailer(true);
        
        // Server settings
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'tlcreameryinc@gmail.com';
        $mail->Password = 'kdvg bueb seul rfnw';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;
        
        $mail->setFrom('tlcreameryinc@gmail.com', 'The Laguna Creamery Inc. (No-Reply)');
        $mail->addReplyTo('no-reply@tlcreameryinc.com', 'TLCI IT Support (Do Not Reply)');

        // Add creator as primary recipient if available
        if (!empty($creatorEmail)) {
            $mail->addAddress($creatorEmail);
            error_log("Added creator as recipient: " . $creatorEmail);
        }

        // Add assignees as primary recipients
        foreach ($assigneeEmails as $assigneeEmail) {
            if (!empty($assigneeEmail) && $assigneeEmail !== $creatorEmail) {
                $mail->addAddress($assigneeEmail);
                error_log("Added assignee as recipient: " . $assigneeEmail);
            }
        }

        // Add admin as CC
        if (!empty($adminEmail) && $adminEmail !== $creatorEmail && !in_array($adminEmail, $assigneeEmails)) {
            $mail->addCC($adminEmail);
            error_log("Added admin as CC: " . $adminEmail);
        }

        // If no recipients, log and return
        if (count($mail->getToAddresses()) === 0) {
            error_log("No recipients found for task notification");
            return false;
        }

        // Email content
        $mail->isHTML(true);
        
        // Get and apply threading headers
        $headers = getThreadingHeaders($taskId, $conn);
        applyThreadingHeaders($mail, $headers, $subject, $activity);

        $assigneeNamesStr = !empty($assigneeNames) ? implode(', ', $assigneeNames) : 'No assignees';

        $emailBody = "
        <html>
        <body style='font-family: Arial, sans-serif; line-height: 1.6;'>
            <div style='max-width: 600px; margin: 0 auto; padding: 20px;'>
                <div style='text-align: center; margin-bottom: 20px;'>
                    <div style='background-color: #3498db; color: white; padding: 20px; text-align: center; font-weight: bold; font-size: 24px; border-radius: 5px;'>TLCI</div>
                </div>
                
                <div style='background-color: #f8f9fa; border-left: 4px solid #4CAF50; padding: 15px; margin-bottom: 20px;'>
                    <h2 style='color: #2C3E50; margin-top: 0;'>Task {$activity}</h2>
                    <p><strong>SRN ID:</strong> {$srnId}</p>
                    <p><strong>Subject:</strong> {$subject}</p>
                    <p><strong>Updated By:</strong> {$creatorName}</p>
                    <p><strong>Assigned To:</strong> {$assigneeNamesStr}</p>
                    <p><strong>Status:</strong> {$statusName}</p>
                </div>

                <div style='text-align: center; margin: 30px 0;'>
                    <a href='http://" . $_SERVER['HTTP_HOST'] . "/it-ticket/projectmanagement/threadPage.php?taskId={$taskId}' 
                       style='background-color: #4CAF50; color: white; padding: 12px 20px; text-decoration: none; border-radius: 4px; font-weight: bold;'>
                       View Task
                    </a>
                </div>

                <hr style='border: 0; border-top: 1px solid #e9ecef; margin: 20px 0;'>
                
                <div style='background-color: #f8f9fa; padding: 15px; margin-top: 20px; border-left: 4px solid #dc3545;'>
                    <p style='color: #dc3545; font-weight: bold; margin: 0;'>IMPORTANT: This email was sent from a notification-only address that cannot accept incoming email. Please do not reply to this message.</p>
                </div>
            </div>
        </body>
        </html>";

        $mail->Body = $emailBody;
        $mail->AltBody = strip_tags(str_replace("<br>", "\n", $emailBody));

        // Add additional headers for better Outlook compatibility
        $mail->addCustomHeader('X-Priority', '1');
        $mail->addCustomHeader('X-MSMail-Priority', 'High');
        $mail->addCustomHeader('Importance', 'High');
        $mail->addCustomHeader('X-Auto-Response-Suppress', 'OOF, DR, RN, NRN, AutoReply');
        $mail->addCustomHeader('Organization', 'The Laguna Creamery Inc.');
        $mail->ContentType = 'text/html; charset=utf-8';

        // Debug logging
        error_log("Sending email to recipients: " . print_r($mail->getToAddresses(), true));
        
        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Email sending failed: " . $e->getMessage());
        return false;
    }
}

/**
 * Sends a notification email for comments on tasks
 * 
 * @param object $conn Database connection
 * @param int $taskId Task ID
 * @param string $subject Task subject
 * @param string $message Comment message
 * @param int $commenterId User ID who added the comment
 * @return bool Success status
 */
function sendCommentNotification($conn, $taskId, $subject, $message, $commenterId) {
    try {
        // Get the SRN ID for the task and status information
        $srnQuery = "SELECT srn_id, statusid, createdbyid FROM pm_projecttasktb WHERE id = ?";
        $stmt = $conn->prepare($srnQuery);
        $stmt->bind_param("i", $taskId);
        $stmt->execute();
        $result = $stmt->get_result();
        $taskData = $result->fetch_assoc();
        $srnId = $taskData['srn_id'] ?? $taskId;
        $statusId = $taskData['statusid'] ?? 1; // Default to status 1 if not set
        $taskCreatorId = $taskData['createdbyid'] ?? 0;

        // Get status name
        $statusName = 'New';
        $statusQuery = "SELECT statusname FROM sys_taskstatustb WHERE id = ?";
        try {
            $statusStmt = $conn->prepare($statusQuery);
            if ($statusStmt) {
                $statusStmt->bind_param("s", $statusId);
                $statusStmt->execute();
                $statusResult = $statusStmt->get_result();
                if ($statusRow = $statusResult->fetch_assoc()) {
                    $statusName = $statusRow['statusname'];
                }
                $statusStmt->close();
            }
        } catch (Exception $e) {
            error_log("Error getting status name: " . $e->getMessage());
            // Continue with default status name
        }

        // Admin email for CC
        $adminEmail = 'mmredondo@carmensbest.com.ph';

        // Get commenter's information
        $commenterQuery = "SELECT emailadd, CONCAT(user_firstname, ' ', user_lastname) as fullname 
                        FROM sys_usertb WHERE id = ?";
        $stmt = $conn->prepare($commenterQuery);
        if (!$stmt) {
            error_log("Failed to prepare commenter query");
            return false;
        }
        
        $stmt->bind_param("s", $commenterId);
        $stmt->execute();
        $result = $stmt->get_result();
        $commenterInfo = $result->fetch_assoc();
        $commenterName = $commenterInfo['fullname'] ?? 'Unknown User';
        $commenterEmail = $commenterInfo['emailadd'] ?? '';

        // Get task creator's information
        $creatorEmail = '';
        $creatorName = '';
        if ($taskCreatorId) {
            $creatorStmt = $conn->prepare($commenterQuery);
            $creatorStmt->bind_param("s", $taskCreatorId);
            $creatorStmt->execute();
            $creatorResult = $creatorStmt->get_result();
            if ($creatorRow = $creatorResult->fetch_assoc()) {
                $creatorEmail = $creatorRow['emailadd'];
                $creatorName = $creatorRow['fullname'];
            }
        }

        // Get assignee emails and names
        $assigneeEmails = [];
        $assigneeNames = [];
        $assigneeQuery = "SELECT u.emailadd, CONCAT(u.user_firstname, ' ', u.user_lastname) as fullname 
                         FROM pm_taskassigneetb ta
                         JOIN sys_usertb u ON ta.assigneeid = u.id
                         WHERE ta.taskid = ?";
        
        $assigneeStmt = $conn->prepare($assigneeQuery);
        if ($assigneeStmt) {
            $assigneeStmt->bind_param("i", $taskId);
            $assigneeStmt->execute();
            $assigneeResult = $assigneeStmt->get_result();
            
            while ($assigneeRow = $assigneeResult->fetch_assoc()) {
                if (!empty($assigneeRow['emailadd'])) {
                    $assigneeEmails[] = $assigneeRow['emailadd'];
                    $assigneeNames[] = $assigneeRow['fullname'];
                }
            }
        }

        $mail = new PHPMailer(true);
        
        // Server settings
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'tlcreameryinc@gmail.com';
        $mail->Password = 'kdvg bueb seul rfnw';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;
        
        $mail->setFrom('tlcreameryinc@gmail.com', 'The Laguna Creamery Inc. (No-Reply)');
        $mail->addReplyTo('no-reply@tlcreameryinc.com', 'TLCI IT Support (Do Not Reply)');

        // If there are assignees, add them as primary recipients
        $hasAssignees = false;
        foreach ($assigneeEmails as $assigneeEmail) {
            if (!empty($assigneeEmail) && $assigneeEmail !== $commenterEmail) {
                $mail->addAddress($assigneeEmail);
                error_log("Added assignee as recipient: " . $assigneeEmail);
                $hasAssignees = true;
            }
        }

        // If no assignees, add task creator as primary recipient (if not the commenter)
        if (!$hasAssignees && !empty($creatorEmail) && $creatorEmail !== $commenterEmail) {
            $mail->addAddress($creatorEmail);
            error_log("Added task creator as primary recipient (no assignees): " . $creatorEmail);
        }

        // Add commenter as CC
        if (!empty($commenterEmail)) {
            $mail->addCC($commenterEmail);
            error_log("Added commenter as CC: " . $commenterEmail);
        }

        // Add task creator as CC if not already a primary recipient and not the commenter
        if (!empty($creatorEmail) && $creatorEmail !== $commenterEmail && 
            !in_array($creatorEmail, $assigneeEmails) && 
            !in_array($creatorEmail, $mail->getToAddresses())) {
            $mail->addCC($creatorEmail);
            error_log("Added task creator as CC: " . $creatorEmail);
        }

        // Add admin as CC
        if (!empty($adminEmail) && $adminEmail !== $commenterEmail && 
            !in_array($adminEmail, $assigneeEmails) && $adminEmail !== $creatorEmail) {
            $mail->addCC($adminEmail);
            error_log("Added admin as CC: " . $adminEmail);
        }

        // If no recipients, log and return
        if (count($mail->getToAddresses()) === 0) {
            error_log("No recipients found for comment notification");
            return false;
        }

        // Email content
        $mail->isHTML(true);
        
        // Get and apply threading headers
        $headers = getThreadingHeaders($taskId, $conn);
        applyThreadingHeaders($mail, $headers, $subject, "Comment Added");

        $assigneeNamesStr = !empty($assigneeNames) ? implode(', ', $assigneeNames) : 'No assignees';

        $emailBody = "
        <html>
        <body style='font-family: Arial, sans-serif; line-height: 1.6;'>
            <div style='max-width: 600px; margin: 0 auto; padding: 20px;'>
                <div style='text-align: center; margin-bottom: 20px;'>
                    <div style='background-color: #3498db; color: white; padding: 20px; text-align: center; font-weight: bold; font-size: 24px; border-radius: 5px;'>TLCI</div>
                </div>
                
                <div style='background-color: #f8f9fa; border-left: 4px solid #4CAF50; padding: 15px; margin-bottom: 20px;'>
                    <h2 style='color: #2C3E50; margin-top: 0;'>New Comment on Task</h2>
                    <p><strong>SRN ID:</strong> {$srnId}</p>
                    <p><strong>Subject:</strong> {$subject}</p>
                    <p><strong>Comment By:</strong> {$commenterName}</p>
                    <p><strong>Assigned To:</strong> {$assigneeNamesStr}</p>
                    <p><strong>Status:</strong> {$statusName}</p>
                </div>
                
                <div style='background-color: #f5f5f5; padding: 15px; border-left: 4px solid #007bff; margin-bottom: 20px;'>
                    <strong>Comment:</strong> " . htmlspecialchars($message) . "
                </div>

                <div style='text-align: center; margin: 30px 0;'>
                    <a href='http://" . $_SERVER['HTTP_HOST'] . "/it-ticket/projectmanagement/threadPage.php?taskId={$taskId}' 
                       style='background-color: #4CAF50; color: white; padding: 12px 20px; text-decoration: none; border-radius: 4px; font-weight: bold;'>
                       View Task
                    </a>
                </div>

                <hr style='border: 0; border-top: 1px solid #e9ecef; margin: 20px 0;'>
                
                <div style='background-color: #f8f9fa; padding: 15px; margin-top: 20px; border-left: 4px solid #dc3545;'>
                    <p style='color: #dc3545; font-weight: bold; margin: 0;'>IMPORTANT: This email was sent from a notification-only address that cannot accept incoming email. Please do not reply to this message.</p>
                </div>
            </div>
        </body>
        </html>";

        $mail->Body = $emailBody;
        $mail->AltBody = strip_tags(str_replace("<br>", "\n", $emailBody));

        // Add additional headers for better Outlook compatibility
        $mail->addCustomHeader('X-Priority', '1');
        $mail->addCustomHeader('X-MSMail-Priority', 'High');
        $mail->addCustomHeader('Importance', 'High');
        $mail->addCustomHeader('X-Auto-Response-Suppress', 'OOF, DR, RN, NRN, AutoReply');
        $mail->addCustomHeader('Organization', 'The Laguna Creamery Inc.');
        $mail->ContentType = 'text/html; charset=utf-8';

        // Debug logging
        error_log("Sending comment notification email to recipients: " . print_r($mail->getToAddresses(), true));
        
        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Comment notification email sending failed: " . $e->getMessage());
        return false;
    }
}

/**
 * Sends a notification email for replies to comments on tasks
 * 
 * @param object $conn Database connection
 * @param int $taskId Task ID
 * @param string $subject Task subject
 * @param string $message Reply message
 * @param int $replierId User ID who added the reply
 * @param int $parentId Parent comment ID
 * @return bool Success status
 */
function sendReplyNotification($conn, $taskId, $subject, $message, $replierId, $parentId) {
    try {
        // Get the SRN ID for the task and status information
        $srnQuery = "SELECT srn_id, statusid, createdbyid FROM pm_projecttasktb WHERE id = ?";
        $stmt = $conn->prepare($srnQuery);
        $stmt->bind_param("i", $taskId);
        $stmt->execute();
        $result = $stmt->get_result();
        $taskData = $result->fetch_assoc();
        $srnId = $taskData['srn_id'] ?? $taskId;
        $statusId = $taskData['statusid'] ?? 1; // Default to status 1 if not set
        $taskCreatorId = $taskData['createdbyid'] ?? 0;

        // Get status name
        $statusName = 'New';
        $statusQuery = "SELECT statusname FROM sys_taskstatustb WHERE id = ?";
        try {
            $statusStmt = $conn->prepare($statusQuery);
            if ($statusStmt) {
                $statusStmt->bind_param("s", $statusId);
                $statusStmt->execute();
                $statusResult = $statusStmt->get_result();
                if ($statusRow = $statusResult->fetch_assoc()) {
                    $statusName = $statusRow['statusname'];
                }
                $statusStmt->close();
            }
        } catch (Exception $e) {
            error_log("Error getting status name: " . $e->getMessage());
            // Continue with default status name
        }

        // Admin email for CC
        $adminEmail = 'mmredondo@carmensbest.com.ph';

        // Get replier's information
        $replierQuery = "SELECT emailadd, CONCAT(user_firstname, ' ', user_lastname) as fullname 
                        FROM sys_usertb WHERE id = ?";
        $stmt = $conn->prepare($replierQuery);
        if (!$stmt) {
            error_log("Failed to prepare replier query");
            return false;
        }
        
        $stmt->bind_param("s", $replierId);
        $stmt->execute();
        $result = $stmt->get_result();
        $replierInfo = $result->fetch_assoc();
        $replierName = $replierInfo['fullname'] ?? 'Unknown User';
        $replierEmail = $replierInfo['emailadd'] ?? '';

        // Get task creator's information
        $creatorEmail = '';
        $creatorName = '';
        if ($taskCreatorId) {
            $creatorStmt = $conn->prepare($replierQuery);
            $creatorStmt->bind_param("s", $taskCreatorId);
            $creatorStmt->execute();
            $creatorResult = $creatorStmt->get_result();
            if ($creatorRow = $creatorResult->fetch_assoc()) {
                $creatorEmail = $creatorRow['emailadd'];
                $creatorName = $creatorRow['fullname'];
            }
        }

        // Get parent comment author's information
        $parentAuthorEmail = '';
        $parentAuthorName = '';
        $parentAuthorId = 0;
        if ($parentId) {
            $parentQuery = "SELECT u.id, u.emailadd, CONCAT(u.user_firstname, ' ', u.user_lastname) as fullname 
                           FROM pm_threadtb t
                           JOIN sys_usertb u ON t.createdbyid = u.id
                           WHERE t.id = ?";
            $parentStmt = $conn->prepare($parentQuery);
            if ($parentStmt) {
                $parentStmt->bind_param("i", $parentId);
                $parentStmt->execute();
                $parentResult = $parentStmt->get_result();
                if ($parentRow = $parentResult->fetch_assoc()) {
                    $parentAuthorId = $parentRow['id'];
                    $parentAuthorEmail = $parentRow['emailadd'];
                    $parentAuthorName = $parentRow['fullname'];
                }
                $parentStmt->close();
            }
        }

        // Debug logging to verify parent comment author information
        error_log("Parent comment ID: " . $parentId);
        error_log("Parent author ID: " . $parentAuthorId);
        error_log("Parent author email: " . $parentAuthorEmail);

        // Get assignee info for display purposes only
        $assigneeNames = [];
        $assigneeQuery = "SELECT CONCAT(u.user_firstname, ' ', u.user_lastname) as fullname 
                         FROM pm_taskassigneetb ta
                         JOIN sys_usertb u ON ta.assigneeid = u.id
                         WHERE ta.taskid = ?";
        
        $assigneeStmt = $conn->prepare($assigneeQuery);
        if ($assigneeStmt) {
            $assigneeStmt->bind_param("i", $taskId);
            $assigneeStmt->execute();
            $assigneeResult = $assigneeStmt->get_result();
            
            while ($assigneeRow = $assigneeResult->fetch_assoc()) {
                $assigneeNames[] = $assigneeRow['fullname'];
            }
        }

        $mail = new PHPMailer(true);
        
        // Server settings
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'tlcreameryinc@gmail.com';
        $mail->Password = 'kdvg bueb seul rfnw';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;
        
        $mail->setFrom('tlcreameryinc@gmail.com', 'The Laguna Creamery Inc. (No-Reply)');
        $mail->addReplyTo('no-reply@tlcreameryinc.com', 'TLCI IT Support (Do Not Reply)');

        $hasRecipient = false;
        
        // Primary recipient: The parent comment author if available and not the replier
        if (!empty($parentAuthorEmail) && $parentAuthorEmail !== $replierEmail) {
            $mail->addAddress($parentAuthorEmail);
            error_log("Added parent comment author as primary recipient: " . $parentAuthorEmail);
            $hasRecipient = true;
        }
        
        // If no parent author available or parent author is the replier, use task creator as primary recipient
        if (!$hasRecipient && !empty($creatorEmail) && $creatorEmail !== $replierEmail) {
            $mail->addAddress($creatorEmail);
            error_log("Added task creator as primary recipient: " . $creatorEmail);
            $hasRecipient = true;
        }
        
        // CC: Always include sender (replier), creator, and admin
        
        // Add replier as CC
        if (!empty($replierEmail)) {
            $mail->addCC($replierEmail);
            error_log("Added replier as CC: " . $replierEmail);
        }
        
        // Add task creator as CC if not already primary recipient
        if (!empty($creatorEmail) && !in_array($creatorEmail, array_column($mail->getToAddresses(), 0))) {
            $mail->addCC($creatorEmail);
            error_log("Added task creator as CC: " . $creatorEmail);
        }
        
        // Add admin as CC
        if (!empty($adminEmail) && !in_array($adminEmail, array_column($mail->getToAddresses(), 0))) {
            $mail->addCC($adminEmail);
            error_log("Added admin as CC: " . $adminEmail);
        }

        // If no recipients, log and return
        if (count($mail->getToAddresses()) === 0) {
            error_log("No recipients found for reply notification");
            return false;
        }

        // Email content
        $mail->isHTML(true);
        
        // Get and apply threading headers
        $headers = getThreadingHeaders($taskId, $conn);
        applyThreadingHeaders($mail, $headers, $subject, "Reply Added");

        $assigneeNamesStr = !empty($assigneeNames) ? implode(', ', $assigneeNames) : 'No assignees';

        $emailBody = "
        <html>
        <body style='font-family: Arial, sans-serif; line-height: 1.6;'>
            <div style='max-width: 600px; margin: 0 auto; padding: 20px;'>
                <div style='text-align: center; margin-bottom: 20px;'>
                    <div style='background-color: #3498db; color: white; padding: 20px; text-align: center; font-weight: bold; font-size: 24px; border-radius: 5px;'>TLCI</div>
                </div>
                
                <div style='background-color: #f8f9fa; border-left: 4px solid #4CAF50; padding: 15px; margin-bottom: 20px;'>
                    <h2 style='color: #2C3E50; margin-top: 0;'>New Reply to Comment</h2>
                    <p><strong>SRN ID:</strong> {$srnId}</p>
                    <p><strong>Subject:</strong> {$subject}</p>
                    <p><strong>Reply By:</strong> {$replierName}</p>
                    " . (!empty($parentAuthorName) ? "<p><strong>In Response To:</strong> {$parentAuthorName}</p>" : "") . "
                    <p><strong>Assigned To:</strong> {$assigneeNamesStr}</p>
                    <p><strong>Status:</strong> {$statusName}</p>
                </div>
                
                <div style='background-color: #f5f5f5; padding: 15px; border-left: 4px solid #007bff; margin-bottom: 20px;'>
                    <strong>Reply:</strong> " . htmlspecialchars($message) . "
                </div>

                <div style='text-align: center; margin: 30px 0;'>
                    <a href='http://" . $_SERVER['HTTP_HOST'] . "/it-ticket/projectmanagement/threadPage.php?taskId={$taskId}' 
                       style='background-color: #4CAF50; color: white; padding: 12px 20px; text-decoration: none; border-radius: 4px; font-weight: bold;'>
                       View Task
                    </a>
                </div>

                <hr style='border: 0; border-top: 1px solid #e9ecef; margin: 20px 0;'>
                
                <div style='background-color: #f8f9fa; padding: 15px; margin-top: 20px; border-left: 4px solid #dc3545;'>
                    <p style='color: #dc3545; font-weight: bold; margin: 0;'>IMPORTANT: This email was sent from a notification-only address that cannot accept incoming email. Please do not reply to this message.</p>
                </div>
            </div>
        </body>
        </html>";

        $mail->Body = $emailBody;
        $mail->AltBody = strip_tags(str_replace("<br>", "\n", $emailBody));

        // Add additional headers for better Outlook compatibility
        $mail->addCustomHeader('X-Priority', '1');
        $mail->addCustomHeader('X-MSMail-Priority', 'High');
        $mail->addCustomHeader('Importance', 'High');
        $mail->addCustomHeader('X-Auto-Response-Suppress', 'OOF, DR, RN, NRN, AutoReply');
        $mail->addCustomHeader('Organization', 'The Laguna Creamery Inc.');
        $mail->ContentType = 'text/html; charset=utf-8';

        // Debug logging
        error_log("Sending reply notification email to recipients: " . print_r($mail->getToAddresses(), true));
        
        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Reply notification email sending failed: " . $e->getMessage());
        return false;
    }
}

/**
 * Detects @mentions in a message and sends notifications to mentioned users
 * 
 * @param object $conn Database connection
 * @param string $message The message text to scan for mentions
 * @param int $taskId Task ID
 * @param string $subject Task subject
 * @param int $commenterId User ID who added the comment/reply
 * @return array Array of mentioned user IDs
 */
function processMentions($conn, $message, $taskId, $subject, $commenterId) {
    // Regular expression to find @username mentions
    preg_match_all('/@([a-zA-Z0-9_]+)/', $message, $matches);
    
    if (empty($matches[1])) {
        return [];
    }
    
    $mentionedUsernames = array_unique($matches[1]);
    $mentionedUserIds = [];
    $mentionedUsers = [];
    
    // Get commenter's information
    $commenterQuery = "SELECT CONCAT(user_firstname, ' ', user_lastname) as fullname 
                      FROM sys_usertb WHERE id = ?";
    $stmt = $conn->prepare($commenterQuery);
    $stmt->bind_param("s", $commenterId);
    $stmt->execute();
    $result = $stmt->get_result();
    $commenterInfo = $result->fetch_assoc();
    $commenterName = $commenterInfo['fullname'] ?? 'Unknown User';
    
    // Get the SRN ID for the task
    $srnQuery = "SELECT srn_id FROM pm_projecttasktb WHERE id = ?";
    $stmt = $conn->prepare($srnQuery);
    $stmt->bind_param("i", $taskId);
    $stmt->execute();
    $result = $stmt->get_result();
    $taskData = $result->fetch_assoc();
    $srnId = $taskData['srn_id'] ?? $taskId;
    
    foreach ($mentionedUsernames as $username) {
        // Find user by username
        $userQuery = "SELECT id, emailadd, username, CONCAT(user_firstname, ' ', user_lastname) as fullname 
                     FROM sys_usertb WHERE username = ?";
        $stmt = $conn->prepare($userQuery);
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($user = $result->fetch_assoc()) {
            // Skip if the mentioned user is the commenter
            if ($user['id'] == $commenterId) {
                continue;
            }
            
            $mentionedUserIds[] = $user['id'];
            
            // Add to the mentioned users array if they have an email
            if (!empty($user['emailadd'])) {
                $mentionedUsers[] = [
                    'id' => $user['id'],
                    'email' => $user['emailadd'],
                    'name' => $user['fullname'],
                    'username' => $user['username']
                ];
            }
        }
    }
    
    // If we have mentioned users, send a single notification to all of them
    if (!empty($mentionedUsers)) {
        sendMultipleMentionNotification(
            $conn,
            $taskId,
            $subject,
            $message,
            $commenterId,
            $commenterName,
            $mentionedUsers,
            $srnId
        );
    }
    
    return $mentionedUserIds;
}

/**
 * Sends a notification email to multiple users who were mentioned in a comment
 * 
 * @param object $conn Database connection
 * @param int $taskId Task ID
 * @param string $subject Task subject
 * @param string $message Comment message
 * @param int $commenterId User ID who added the comment
 * @param string $commenterName Name of the user who added the comment
 * @param array $mentionedUsers Array of mentioned user data
 * @param string $srnId SRN ID of the task
 * @return bool Success status
 */
function sendMultipleMentionNotification($conn, $taskId, $subject, $message, $commenterId, $commenterName, $mentionedUsers, $srnId) {
    try {
        // Get status information and task creator
        $statusQuery = "SELECT s.statusname, t.createdbyid 
                       FROM pm_projecttasktb t 
                       JOIN sys_taskstatustb s ON t.statusid = s.id 
                       WHERE t.id = ?";
        $stmt = $conn->prepare($statusQuery);
        $stmt->bind_param("i", $taskId);
        $stmt->execute();
        $result = $stmt->get_result();
        $taskData = $result->fetch_assoc();
        $statusName = $taskData['statusname'] ?? 'New';
        $taskCreatorId = $taskData['createdbyid'] ?? 0;

        // Admin email for CC
        $adminEmail = 'mmredondo@carmensbest.com.ph';

        $mail = new PHPMailer(true);
        
        // Server settings
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'tlcreameryinc@gmail.com';
        $mail->Password = 'kdvg bueb seul rfnw';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;
        
        $mail->setFrom('tlcreameryinc@gmail.com', 'The Laguna Creamery Inc. (No-Reply)');
        $mail->addReplyTo('no-reply@tlcreameryinc.com', 'TLCI IT Support (Do Not Reply)');

        // Add all mentioned users as TO recipients
        foreach ($mentionedUsers as $user) {
            $mail->addAddress($user['email'], $user['name']);
        }
        
        // Get task creator's email if not the commenter
        if ($taskCreatorId && $taskCreatorId != $commenterId) {
            $creatorQuery = "SELECT emailadd, CONCAT(user_firstname, ' ', user_lastname) as fullname 
                            FROM sys_usertb WHERE id = ?";
            $stmt = $conn->prepare($creatorQuery);
            $stmt->bind_param("s", $taskCreatorId);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($creatorData = $result->fetch_assoc()) {
                // Check if creator is not already in the recipients list
                $creatorAlreadyMentioned = false;
                foreach ($mentionedUsers as $user) {
                    if ($user['id'] == $taskCreatorId) {
                        $creatorAlreadyMentioned = true;
                        break;
                    }
                }
                
                if (!$creatorAlreadyMentioned && !empty($creatorData['emailadd'])) {
                    $mail->addCC($creatorData['emailadd']);
                    error_log("Added task creator as CC: " . $creatorData['emailadd']);
                }
            }
        }
        
        // Add commenter as CC
        $commenterQuery = "SELECT emailadd FROM sys_usertb WHERE id = ?";
        $stmt = $conn->prepare($commenterQuery);
        $stmt->bind_param("s", $commenterId);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($commenterData = $result->fetch_assoc()) {
            if (!empty($commenterData['emailadd'])) {
                $mail->addCC($commenterData['emailadd']);
                error_log("Added commenter as CC: " . $commenterData['emailadd']);
            }
        }
        
        // Add admin as CC
        if (!empty($adminEmail)) {
            // Check if admin is not already in the recipients list
            $adminAlreadyMentioned = false;
            foreach ($mentionedUsers as $user) {
                if ($user['email'] == $adminEmail) {
                    $adminAlreadyMentioned = true;
                    break;
                }
            }
            
            if (!$adminAlreadyMentioned) {
                $mail->addCC($adminEmail);
                error_log("Added admin as CC: " . $adminEmail);
            }
        }
        
        // Email content
        $mail->isHTML(true);
        
        // Get and apply threading headers
        $headers = getThreadingHeaders($taskId, $conn);
        applyThreadingHeaders($mail, $headers, $subject, "Mention Notification");

        // Highlight all mentions in the message
        $highlightedMessage = htmlspecialchars($message);
        foreach ($mentionedUsers as $user) {
            $highlightedMessage = preg_replace('/@' . preg_quote($user['username'], '/') . '/', 
                                              '<span style="background-color: #ffff00; font-weight: bold;">@' . $user['username'] . '</span>', 
                                              $highlightedMessage);
        }

        // Create a list of mentioned users for the email
        $mentionedList = '';
        foreach ($mentionedUsers as $user) {
            $mentionedList .= '<li>' . htmlspecialchars($user['name']) . ' (@' . htmlspecialchars($user['username']) . ')</li>';
        }

        $emailBody = "
        <html>
        <body style='font-family: Arial, sans-serif; line-height: 1.6;'>
            <div style='max-width: 600px; margin: 0 auto; padding: 20px;'>
                <div style='text-align: center; margin-bottom: 20px;'>
                    <div style='background-color: #3498db; color: white; padding: 20px; text-align: center; font-weight: bold; font-size: 24px; border-radius: 5px;'>TLCI</div>
                </div>
                
                <div style='background-color: #f8f9fa; border-left: 4px solid #4CAF50; padding: 15px; margin-bottom: 20px;'>
                    <h2 style='color: #2C3E50; margin-top: 0;'>You were mentioned in a task</h2>
                    <p><strong>SRN ID:</strong> {$srnId}</p>
                    <p><strong>Subject:</strong> {$subject}</p>
                    <p><strong>Mentioned by:</strong> {$commenterName}</p>
                    <p><strong>Status:</strong> {$statusName}</p>
                    <p><strong>Users Mentioned:</strong></p>
                    <ul>{$mentionedList}</ul>
                </div>
                
                <div style='background-color: #f5f5f5; padding: 15px; border-left: 4px solid #007bff; margin-bottom: 20px;'>
                    <strong>Message:</strong> {$highlightedMessage}
                </div>

                <div style='text-align: center; margin: 30px 0;'>
                    <a href='http://" . $_SERVER['HTTP_HOST'] . "/it-ticket/projectmanagement/threadPage.php?taskId={$taskId}' 
                       style='background-color: #4CAF50; color: white; padding: 12px 20px; text-decoration: none; border-radius: 4px; font-weight: bold;'>
                       View Task
                    </a>
                </div>

                <hr style='border: 0; border-top: 1px solid #e9ecef; margin: 20px 0;'>
                
                <div style='background-color: #f8f9fa; padding: 15px; margin-top: 20px; border-left: 4px solid #dc3545;'>
                    <p style='color: #dc3545; font-weight: bold; margin: 0;'>IMPORTANT: This email was sent from a notification-only address that cannot accept incoming email. Please do not reply to this message.</p>
                </div>
            </div>
        </body>
        </html>";

        $mail->Body = $emailBody;
        $mail->AltBody = strip_tags(str_replace("<br>", "\n", $emailBody));

        // Add additional headers for better Outlook compatibility
        $mail->addCustomHeader('X-Priority', '1');
        $mail->addCustomHeader('X-MSMail-Priority', 'High');
        $mail->addCustomHeader('Importance', 'High');
        $mail->addCustomHeader('X-Auto-Response-Suppress', 'OOF, DR, RN, NRN, AutoReply');
        $mail->addCustomHeader('Organization', 'The Laguna Creamery Inc.');
        $mail->ContentType = 'text/html; charset=utf-8';
        
        // Debug logging
        error_log("Sending mention notification email to multiple recipients: " . print_r($mail->getToAddresses(), true));
        error_log("With CCs: " . print_r($mail->getCcAddresses(), true));
        
        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Multiple mention notification email sending failed: " . $e->getMessage());
        return false;
    }
}

/**
 * Sends ticket creation notifications - one to creator and one to service team
 * 
 * @param object $conn Database connection
 * @param int $ticketId Ticket ID
 * @param string $subject Ticket subject
 * @param string $createdBy User ID who created the ticket
 * @return bool Success status
 */
function sendTicketNotification($conn, $ticketId, $subject, $createdBy) {
    try {
        // Get the ticket data
        $ticketQuery = "SELECT t.srn_id, t.statusid, s.statusname 
                       FROM pm_projecttasktb t 
                       LEFT JOIN sys_taskstatustb s ON t.statusid = s.id 
                       WHERE t.id = ?";
        $stmt = $conn->prepare($ticketQuery);
        $stmt->bind_param("i", $ticketId);
        $stmt->execute();
        $result = $stmt->get_result();
        $ticketData = $result->fetch_assoc();
        
        $srnId = $ticketData['srn_id'] ?? $ticketId;
        $statusName = $ticketData['statusname'] ?? 'New';

        // Get creator's information
        $creatorQuery = "SELECT emailadd, CONCAT(user_firstname, ' ', user_lastname) as fullname 
                        FROM sys_usertb WHERE id = ?";
        $stmt = $conn->prepare($creatorQuery);
        $stmt->bind_param("s", $createdBy);
        $stmt->execute();
        $result = $stmt->get_result();
        $creatorInfo = $result->fetch_assoc();
        $creatorName = $creatorInfo['fullname'] ?? 'Unknown User';
        $creatorEmail = $creatorInfo['emailadd'] ?? '';

        // Service management team emails
        $serviceTeamEmails = [
            'MMREDONDO@CARMENSBEST.COM.PH',
            'mavillaluz@carmensbest.com.ph',
            // 'kpmarasigan@carmensbest.com.ph',
            'jffelix@carmensbest.com.ph'
        ];

        // 1. Send notification to creator
        if (!empty($creatorEmail)) {
            $mail = new PHPMailer(true);
            configureMailer($mail);
            
            $mail->addAddress($creatorEmail, $creatorName);
            
            // Get and apply threading headers
            $headers = getThreadingHeaders($ticketId, $conn);
            applyThreadingHeaders($mail, $headers, $subject, "Ticket Created");
            
            $emailBody = generateTicketEmailBody(
                $srnId,
                $subject,
                $creatorName,
                $statusName,
                $ticketId,
                "Your ticket has been successfully created",
                true
            );

            $mail->Body = $emailBody;
            $mail->AltBody = strip_tags(str_replace("<br>", "\n", $emailBody));
            addEmailHeaders($mail);
            
            $mail->send();
        }

        // 2. Send notification to service management team
        $mail = new PHPMailer(true);
        configureMailer($mail);
        
        foreach ($serviceTeamEmails as $email) {
            $mail->addAddress($email);
        }
        
        // Get and apply threading headers
        $headers = getThreadingHeaders($ticketId, $conn);
        applyThreadingHeaders($mail, $headers, $subject, "New Ticket");
        
        $emailBody = generateTicketEmailBody(
            $srnId,
            $subject,
            $creatorName,
            $statusName,
            $ticketId,
            "A new ticket has been created",
            false
        );

        $mail->Body = $emailBody;
        $mail->AltBody = strip_tags(str_replace("<br>", "\n", $emailBody));
        addEmailHeaders($mail);
        
        $mail->send();
        return true;

    } catch (Exception $e) {
        error_log("Ticket notification email sending failed: " . $e->getMessage());
        return false;
    }
}

/**
 * Configures PHPMailer instance with common settings
 */
function configureMailer($mail) {
    $mail->isSMTP();
    $mail->Host = 'smtp.gmail.com';
    $mail->SMTPAuth = true;
    $mail->Username = 'tlcreameryinc@gmail.com';
    $mail->Password = 'kdvg bueb seul rfnw';
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = 587;
    
    $mail->setFrom('tlcreameryinc@gmail.com', 'The Laguna Creamery Inc. (No-Reply)');
    $mail->addReplyTo('no-reply@tlcreameryinc.com', 'TLCI IT Support (Do Not Reply)');
    $mail->isHTML(true);
}

/**
 * Generates email body for ticket notifications
 */
function generateTicketEmailBody($srnId, $subject, $creatorName, $statusName, $ticketId, $headerMessage, $isCreator) {
    return "
    <html>
    <body style='font-family: Arial, sans-serif; line-height: 1.6;'>
        <div style='max-width: 600px; margin: 0 auto; padding: 20px;'>
            <div style='text-align: center; margin-bottom: 20px;'>
                <div style='background-color: #3498db; color: white; padding: 20px; text-align: center; font-weight: bold; font-size: 24px; border-radius: 5px;'>TLCI</div>
            </div>
            
            <div style='background-color: #f8f9fa; border-left: 4px solid #4CAF50; padding: 15px; margin-bottom: 20px;'>
                <h2 style='color: #2C3E50; margin-top: 0;'>{$headerMessage}</h2>
                <p><strong>SRN ID:</strong> {$srnId}</p>
                <p><strong>Subject:</strong> {$subject}</p>
                <p><strong>Created By:</strong> {$creatorName}</p>
                <p><strong>Status:</strong> {$statusName}</p>
            </div>

            <div style='text-align: center; margin: 30px 0;'>
                <a href='http://" . $_SERVER['HTTP_HOST'] . "/it-ticket/projectmanagement/threadPage.php?taskId={$ticketId}' 
                   style='background-color: #4CAF50; color: white; padding: 12px 20px; text-decoration: none; border-radius: 4px; font-weight: bold;'>
                   View Ticket
                </a>
            </div>

            <hr style='border: 0; border-top: 1px solid #e9ecef; margin: 20px 0;'>
            
            <div style='background-color: #f8f9fa; padding: 15px; margin-top: 20px; border-left: 4px solid #dc3545;'>
                <p style='color: #dc3545; font-weight: bold; margin: 0;'>IMPORTANT: This email was sent from a notification-only address that cannot accept incoming email. Please do not reply to this message.</p>
            </div>
        </div>
    </body>
    </html>";
}

/**
 * Adds common email headers
 */
function addEmailHeaders($mail) {
    $mail->addCustomHeader('X-Priority', '1');
    $mail->addCustomHeader('X-MSMail-Priority', 'High');
    $mail->addCustomHeader('Importance', 'High');
    $mail->addCustomHeader('X-Auto-Response-Suppress', 'OOF, DR, RN, NRN, AutoReply');
    $mail->addCustomHeader('Organization', 'The Laguna Creamery Inc.');
    $mail->ContentType = 'text/html; charset=utf-8';
}