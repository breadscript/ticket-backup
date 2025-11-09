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
        $adminEmail = '';

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
                        <div style='background-color: #f8f9fa; padding: 15px; margin-top: 20px; border-left: 4px solid #dc3545;'>
                    <p style='color: #dc3545; font-weight: bold; margin: 0;'>IMPORTANT: This email was sent from a notification-only address that cannot accept incoming email. Please do not reply to this message.</p>
                </div>
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
                    <a href='https://" . $_SERVER['HTTP_HOST'] . "/it-ticket/projectmanagement/threadPage.php?taskId={$taskId}' 
                       style='background-color: #4CAF50; color: white; padding: 12px 20px; text-decoration: none; border-radius: 4px; font-weight: bold;'>
                       View Thread
                    </a>
                </div>

                <hr style='border: 0; border-top: 1px solid #e9ecef; margin: 20px 0;'>
                

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
        // Get task and status data
        $taskQuery = "SELECT t.srn_id, t.statusid, t.createdbyid, s.statusname 
                     FROM pm_projecttasktb t 
                     LEFT JOIN sys_taskstatustb s ON t.statusid = s.id 
                     WHERE t.id = ?";
        $stmt = $conn->prepare($taskQuery);
        $stmt->bind_param("i", $taskId);
        $stmt->execute();
        $taskData = $stmt->get_result()->fetch_assoc();
        
        $srnId = $taskData['srn_id'] ?? $taskId;
        $statusName = $taskData['statusname'] ?? 'New';
        $creatorId = $taskData['createdbyid'];

        // Get commenter info
        $userQuery = "SELECT emailadd, CONCAT(user_firstname, ' ', user_lastname) as fullname 
                     FROM sys_usertb WHERE id = ?";
        $stmt = $conn->prepare($userQuery);
        $stmt->bind_param("s", $commenterId);
        $stmt->execute();
        $commenterInfo = $stmt->get_result()->fetch_assoc();
        $commenterName = $commenterInfo['fullname'] ?? 'Unknown User';
        $commenterEmail = $commenterInfo['emailadd'] ?? '';

        // Service team emails for when there are no assignees
        $serviceTeamEmails = [
            '',
            'kpmarasigan@carmensbest.com.ph',
            'mavillaluz@carmensbest.com.ph',
            'jffelix@carmensbest.com.ph'
        ];

        // 1. First send to creator if not the commenter
        if ($creatorId && $creatorId != $commenterId) {
            $stmt = $conn->prepare($userQuery);
            $stmt->bind_param("s", $creatorId);
            $stmt->execute();
            $creatorInfo = $stmt->get_result()->fetch_assoc();
            
            if (!empty($creatorInfo['emailadd'])) {
                $mail = new PHPMailer(true);
                configureMailer($mail);
                $mail->addAddress($creatorInfo['emailadd'], $creatorInfo['fullname']);
                
                $headers = getThreadingHeaders($taskId, $conn);
                applyThreadingHeaders($mail, $headers, $subject, "Comment Added");
                
                $emailBody = generateCommentEmailBody(
                    $srnId,
                    $subject,
                    $commenterName,
                    $statusName,
                    $taskId,
                    $message,
                    "A new comment has been added to your task"
                );

                $mail->Body = $emailBody;
                $mail->AltBody = strip_tags(str_replace("<br>", "\n", $emailBody));
                addEmailHeaders($mail);
                $mail->send();
                
                error_log("Sent comment notification to creator: " . $creatorInfo['emailadd']);
            }
        }

        // 2. Send to assignees or service team
        $assigneeQuery = "SELECT u.id, u.emailadd, CONCAT(u.user_firstname, ' ', u.user_lastname) as fullname 
                         FROM pm_taskassigneetb ta
                         JOIN sys_usertb u ON ta.assigneeid = u.id
                         WHERE ta.taskid = ?";
        $stmt = $conn->prepare($assigneeQuery);
        $stmt->bind_param("i", $taskId);
        $stmt->execute();
        $assigneeResult = $stmt->get_result();
        
        $hasAssignees = false;
        while ($assignee = $assigneeResult->fetch_assoc()) {
            if (!empty($assignee['emailadd']) && $assignee['id'] != $commenterId && $assignee['id'] != $creatorId) {
                $hasAssignees = true;
                
                $mail = new PHPMailer(true);
                configureMailer($mail);
                $mail->addAddress($assignee['emailadd'], $assignee['fullname']);
                
                $headers = getThreadingHeaders($taskId, $conn);
                applyThreadingHeaders($mail, $headers, $subject, "Comment Added");
                
                $emailBody = generateCommentEmailBody(
                    $srnId,
                    $subject,
                    $commenterName,
                    $statusName,
                    $taskId,
                    $message,
                    "A new comment has been added to a task assigned to you"
                );

                $mail->Body = $emailBody;
                $mail->AltBody = strip_tags(str_replace("<br>", "\n", $emailBody));
                addEmailHeaders($mail);
                $mail->send();
                
                error_log("Sent comment notification to assignee: " . $assignee['emailadd']);
            }
        }

        // If no assignees, send to service team
        if (!$hasAssignees) {
            foreach ($serviceTeamEmails as $email) {
                if ($email != $commenterEmail) {
                    $mail = new PHPMailer(true);
                    configureMailer($mail);
                    $mail->addAddress($email);
                    
                    $headers = getThreadingHeaders($taskId, $conn);
                    applyThreadingHeaders($mail, $headers, $subject, "Comment Added");
                    
                    $emailBody = generateCommentEmailBody(
                        $srnId,
                        $subject,
                        $commenterName,
                        $statusName,
                        $taskId,
                        $message,
                        "A new comment has been added to an unassigned task"
                    );

                    $mail->Body = $emailBody;
                    $mail->AltBody = strip_tags(str_replace("<br>", "\n", $emailBody));
                    addEmailHeaders($mail);
                    $mail->send();
                    
                    error_log("Sent comment notification to service team: " . $email);
                }
            }
        }

        // 3. Send to admin - always send to admin regardless of who commented
        $adminEmail = '';
        // $adminEmail = 'mavillaluz@carmensbest.com.ph';

        $mail = new PHPMailer(true);
        configureMailer($mail);
        $mail->addAddress($adminEmail);
        
        $headers = getThreadingHeaders($taskId, $conn);
        applyThreadingHeaders($mail, $headers, $subject, "Comment Added");
        
        $emailBody = generateCommentEmailBody(
            $srnId,
            $subject,
            $commenterName,
            $statusName,
            $taskId,
            $message,
            "New Comment Added - Admin Notification",
            true // isAdmin flag
        );

        $mail->Body = $emailBody;
        $mail->AltBody = strip_tags(str_replace("<br>", "\n", $emailBody));
        addEmailHeaders($mail);
        $mail->send();
        
        error_log("Sent comment notification to admin: " . $adminEmail);

        // 4. Process mentions and send separate notifications
        preg_match_all('/@([a-zA-Z0-9_]+)/', $message, $matches);
        if (!empty($matches[1])) {
            $mentionedUsernames = array_unique($matches[1]);
            
            foreach ($mentionedUsernames as $username) {
                $userQuery = "SELECT id, emailadd, username, CONCAT(user_firstname, ' ', user_lastname) as fullname 
                            FROM sys_usertb WHERE username = ?";
                $stmt = $conn->prepare($userQuery);
                $stmt->bind_param("s", $username);
                $stmt->execute();
                $mentionedUser = $stmt->get_result()->fetch_assoc();
                
                if ($mentionedUser && !empty($mentionedUser['emailadd']) && $mentionedUser['id'] != $commenterId) {
                    $mail = new PHPMailer(true);
                    configureMailer($mail);
                    $mail->addAddress($mentionedUser['emailadd'], $mentionedUser['fullname']);
                    
                    $headers = getThreadingHeaders($taskId, $conn);
                    applyThreadingHeaders($mail, $headers, $subject, "You were mentioned");
                    
                    // Highlight the mention in the message
                    $highlightedMessage = str_replace(
                        '@' . $username,
                        '<span style="background-color: #ffff00; font-weight: bold;">@' . $username . '</span>',
                        htmlspecialchars($message)
                    );
                    
                    $emailBody = generateMentionEmailBody(
                        $srnId,
                        $subject,
                        $commenterName,
                        $statusName,
                        $taskId,
                        $highlightedMessage,
                        "You were mentioned in a comment",
                        $mentionedUser['fullname']
                    );

                    $mail->Body = $emailBody;
                    $mail->AltBody = strip_tags(str_replace("<br>", "\n", $emailBody));
                    addEmailHeaders($mail);
                    $mail->send();
                    
                    error_log("Sent mention notification to: " . $mentionedUser['emailadd']);
                }
            }
        }

        return true;
    } catch (Exception $e) {
        error_log("Error sending notifications: " . $e->getMessage());
        return false;
    }
}

// Helper function to generate comment email body
function generateCommentEmailBody($srnId, $subject, $commenterName, $statusName, $taskId, $message, $headerMessage, $isAdmin = false) {
    $adminSection = $isAdmin ? "
        <div style='background-color: #fff3cd; border-left: 4px solid #ffc107; padding: 15px; margin: 20px 0;'>
            <h3 style='color: #856404; margin-top: 0;'>Admin Notification</h3>
            <p>This is an administrative notification for comment monitoring.</p>
        </div>" : "";

    return "
    <html>
    <body style='font-family: Arial, sans-serif; line-height: 1.6;'>
         
            <div style='background-color: #f8f9fa; padding: 15px; margin-top: 20px; border-left: 4px solid #dc3545;'>
                <p style='color: #dc3545; font-weight: bold; margin: 0;'>IMPORTANT: This email was sent from a notification-only address that cannot accept incoming email. Please do not reply to this message.</p>
            </div>
        <div style='max-width: 600px; margin: 0 auto; padding: 20px;'>
            <div style='text-align: center; margin-bottom: 20px;'>
                <div style='background-color: #3498db; color: white; padding: 20px; text-align: center; font-weight: bold; font-size: 24px; border-radius: 5px;'>TLCI</div>
            </div>
            
            {$adminSection}
            
            <div style='background-color: #f8f9fa; border-left: 4px solid #4CAF50; padding: 15px; margin-bottom: 20px;'>
                <h2 style='color: #2C3E50; margin-top: 0;'>{$headerMessage}</h2>
                <p><strong>SRN ID:</strong> {$srnId}</p>
                <p><strong>Subject:</strong> {$subject}</p>
                <p><strong>Comment By:</strong> {$commenterName}</p>
                <p><strong>Status:</strong> {$statusName}</p>
            </div>
            
            <div style='background-color: #f5f5f5; padding: 15px; border-left: 4px solid #007bff; margin-bottom: 20px;'>
                <strong>Comment:</strong><br>
                " . htmlspecialchars($message) . "
            </div>

            <div style='text-align: center; margin: 30px 0;'>
                <a href='https://" . $_SERVER['HTTP_HOST'] . "/it-ticket/projectmanagement/threadPage.php?taskId={$taskId}' 
                   style='background-color: #4CAF50; color: white; padding: 12px 20px; text-decoration: none; border-radius: 4px; font-weight: bold;'>
                   View Thread
                </a>
            </div>

            <hr style='border: 0; border-top: 1px solid #e9ecef; margin: 20px 0;'>
       
        </div>
    </body>
    </html>";
}

// Helper function to generate mention email body
function generateMentionEmailBody($srnId, $subject, $commenterName, $statusName, $taskId, $message, $headerMessage, $mentionedName) {
    return "
    <html>
    <body style='font-family: Arial, sans-serif; line-height: 1.6;'>
         
            <div style='background-color: #f8f9fa; padding: 15px; margin-top: 20px; border-left: 4px solid #dc3545;'>
                <p style='color: #dc3545; font-weight: bold; margin: 0;'>IMPORTANT: This email was sent from a notification-only address that cannot accept incoming email. Please do not reply to this message.</p>
            </div>
        <div style='max-width: 600px; margin: 0 auto; padding: 20px;'>
            <div style='text-align: center; margin-bottom: 20px;'>
                <div style='background-color: #3498db; color: white; padding: 20px; text-align: center; font-weight: bold; font-size: 24px; border-radius: 5px;'>TLCI</div>
            </div>
            
            <div style='background-color: #f8f9fa; border-left: 4px solid #4CAF50; padding: 15px; margin-bottom: 20px;'>
                <h2 style='color: #2C3E50; margin-top: 0;'>{$headerMessage}</h2>
                <p><strong>Hello {$mentionedName},</strong></p>
                <p>You were mentioned in a comment by {$commenterName}.</p>
                <p><strong>SRN ID:</strong> {$srnId}</p>
                <p><strong>Subject:</strong> {$subject}</p>
                <p><strong>Status:</strong> {$statusName}</p>
            </div>
            
            <div style='background-color: #f5f5f5; padding: 15px; border-left: 4px solid #007bff; margin-bottom: 20px;'>
                <strong>Comment:</strong><br>
                {$message}
            </div>

            <div style='text-align: center; margin: 30px 0;'>
                <a href='https://" . $_SERVER['HTTP_HOST'] . "/it-ticket/projectmanagement/threadPage.php?taskId={$taskId}' 
                   style='background-color: #4CAF50; color: white; padding: 12px 20px; text-decoration: none; border-radius: 4px; font-weight: bold;'>
                   View Thread
                </a>
            </div>

            <hr style='border: 0; border-top: 1px solid #e9ecef; margin: 20px 0;'>
       
        </div>
    </body>
    </html>";
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
        // $adminEmail = '';
        $adminEmail = '';
        // $adminEmail = 'mavillaluz@carmensbest.com.ph';

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
             
                <div style='background-color: #f8f9fa; padding: 15px; margin-top: 20px; border-left: 4px solid #dc3545;'>
                    <p style='color: #dc3545; font-weight: bold; margin: 0;'>IMPORTANT: This email was sent from a notification-only address that cannot accept incoming email. Please do not reply to this message.</p>
                </div>
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
                    <a href='https://" . $_SERVER['HTTP_HOST'] . "/it-ticket/projectmanagement/threadPage.php?taskId={$taskId}' 
                       style='background-color: #4CAF50; color: white; padding: 12px 20px; text-decoration: none; border-radius: 4px; font-weight: bold;'>
                       View Thread
                    </a>
                </div>

                <hr style='border: 0; border-top: 1px solid #e9ecef; margin: 20px 0;'>
           
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
        
        // Send a separate email to admin if admin is the replier (not included in the CC)
        if ($replierEmail === $adminEmail) {
            $adminMail = new PHPMailer(true);
            
            // Server settings
            $adminMail->isSMTP();
            $adminMail->Host = 'smtp.gmail.com';
            $adminMail->SMTPAuth = true;
            $adminMail->Username = 'tlcreameryinc@gmail.com';
            $adminMail->Password = 'kdvg bueb seul rfnw';
            $adminMail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $adminMail->Port = 587;
            
            $adminMail->setFrom('tlcreameryinc@gmail.com', 'The Laguna Creamery Inc. (No-Reply)');
            $adminMail->addReplyTo('no-reply@tlcreameryinc.com', 'TLCI IT Support (Do Not Reply)');
            $adminMail->addAddress($adminEmail);
            
            // Get and apply threading headers
            $headers = getThreadingHeaders($taskId, $conn);
            applyThreadingHeaders($adminMail, $headers, $subject, "Reply Added - Admin Copy");
            
            // Create admin-specific email body
            $adminEmailBody = "
            <html>
            <body style='font-family: Arial, sans-serif; line-height: 1.6;'>
                 
                    <div style='background-color: #f8f9fa; padding: 15px; margin-top: 20px; border-left: 4px solid #dc3545;'>
                        <p style='color: #dc3545; font-weight: bold; margin: 0;'>IMPORTANT: This email was sent from a notification-only address that cannot accept incoming email. Please do not reply to this message.</p>
                    </div>
                <div style='max-width: 600px; margin: 0 auto; padding: 20px;'>
                    <div style='text-align: center; margin-bottom: 20px;'>
                        <div style='background-color: #3498db; color: white; padding: 20px; text-align: center; font-weight: bold; font-size: 24px; border-radius: 5px;'>TLCI</div>
                    </div>
                    
                    <div style='background-color: #fff3cd; border-left: 4px solid #ffc107; padding: 15px; margin: 20px 0;'>
                        <h3 style='color: #856404; margin-top: 0;'>Admin Copy</h3>
                        <p>This is a copy of your reply for your records.</p>
                    </div>
                    
                    <div style='background-color: #f8f9fa; border-left: 4px solid #4CAF50; padding: 15px; margin-bottom: 20px;'>
                        <h2 style='color: #2C3E50; margin-top: 0;'>Your Reply to Comment</h2>
                        <p><strong>SRN ID:</strong> {$srnId}</p>
                        <p><strong>Subject:</strong> {$subject}</p>
                        " . (!empty($parentAuthorName) ? "<p><strong>In Response To:</strong> {$parentAuthorName}</p>" : "") . "
                        <p><strong>Assigned To:</strong> {$assigneeNamesStr}</p>
                        <p><strong>Status:</strong> {$statusName}</p>
                    </div>
                    
                    <div style='background-color: #f5f5f5; padding: 15px; border-left: 4px solid #007bff; margin-bottom: 20px;'>
                        <strong>Your Reply:</strong> " . htmlspecialchars($message) . "
                    </div>

                    <div style='text-align: center; margin: 30px 0;'>
                        <a href='https://" . $_SERVER['HTTP_HOST'] . "/it-ticket/projectmanagement/threadPage.php?taskId={$taskId}' 
                           style='background-color: #4CAF50; color: white; padding: 12px 20px; text-decoration: none; border-radius: 4px; font-weight: bold;'>
                           View Thread
                        </a>
                    </div>

                    <hr style='border: 0; border-top: 1px solid #e9ecef; margin: 20px 0;'>
               
                </div>
            </body>
            </html>";
            
            $adminMail->Body = $adminEmailBody;
            $adminMail->AltBody = strip_tags(str_replace("<br>", "\n", $adminEmailBody));
            
            // Add additional headers
            $adminMail->addCustomHeader('X-Priority', '1');
            $adminMail->addCustomHeader('X-MSMail-Priority', 'High');
            $adminMail->addCustomHeader('Importance', 'High');
            $adminMail->addCustomHeader('X-Auto-Response-Suppress', 'OOF, DR, RN, NRN, AutoReply');
            $adminMail->addCustomHeader('Organization', 'The Laguna Creamery Inc.');
            $adminMail->ContentType = 'text/html; charset=utf-8';
            
            $adminMail->send();
            error_log("Sent admin copy of reply to: " . $adminEmail);
        }
        
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
        $adminEmail = '';

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
              <div style='background-color: #f8f9fa; padding: 15px; margin-top: 20px; border-left: 4px solid #dc3545;'>
                    <p style='color: #dc3545; font-weight: bold; margin: 0;'>IMPORTANT: This email was sent from a notification-only address that cannot accept incoming email. Please do not reply to this message.</p>
                </div>
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
                    <a href='https://" . $_SERVER['HTTP_HOST'] . "/it-ticket/projectmanagement/threadPage.php?taskId={$taskId}' 
                       style='background-color: #4CAF50; color: white; padding: 12px 20px; text-decoration: none; border-radius: 4px; font-weight: bold;'>
                       View Thread
                    </a>
                </div>

                <hr style='border: 0; border-top: 1px solid #e9ecef; margin: 20px 0;'>
                
          
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
        $ticketQuery = "SELECT t.srn_id, t.statusid, t.resolution, s.statusname 
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
            '',
            'kpmarasigan@carmensbest.com.ph',
            'mavillaluz@carmensbest.com.ph',
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
 * Sends ticket update notification emails
 * 
 * @param object $conn Database connection
 * @param int $ticketId Ticket ID
 * @param string $subject Ticket subject
 * @param int $newStatus New status ID
 * @param string $updatedBy User ID who updated the ticket
 * @return bool Success status
 */
function sendTicketUpdateNotification($conn, $ticketId, $subject, $newStatus, $updatedBy) {
    try {
        // Get the ticket data
        $ticketQuery = "SELECT t.srn_id, t.statusid, t.createdbyid, t.resolution, s.statusname 
                       FROM pm_projecttasktb t 
                       LEFT JOIN sys_taskstatustb s ON s.id = ? 
                       WHERE t.id = ?";
        $stmt = $conn->prepare($ticketQuery);
        $stmt->bind_param("ii", $newStatus, $ticketId);
        $stmt->execute();
        $result = $stmt->get_result();
        $ticketData = $result->fetch_assoc();
        
        $srnId = $ticketData['srn_id'] ?? $ticketId;
        $statusName = $ticketData['statusname'] ?? 'Unknown Status';
        $createdBy = $ticketData['createdbyid'] ?? '';

        // Get updater's information
        $updaterQuery = "SELECT emailadd, CONCAT(user_firstname, ' ', user_lastname) as fullname 
                        FROM sys_usertb WHERE id = ?";
        $stmt = $conn->prepare($updaterQuery);
        $stmt->bind_param("s", $updatedBy);
        $stmt->execute();
        $result = $stmt->get_result();
        $updaterInfo = $result->fetch_assoc();
        $updaterName = $updaterInfo['fullname'] ?? 'Unknown User';
        $updaterEmail = $updaterInfo['emailadd'] ?? '';

        // Get creator's information if different from updater
        $creatorEmail = '';
        $creatorName = '';
        if ($createdBy && $createdBy != $updatedBy) {
            $stmt = $conn->prepare($updaterQuery);
            $stmt->bind_param("s", $createdBy);
            $stmt->execute();
            $result = $stmt->get_result();
            $creatorInfo = $result->fetch_assoc();
            $creatorName = $creatorInfo['fullname'] ?? 'Unknown User';
            $creatorEmail = $creatorInfo['emailadd'] ?? '';
        }

        // Service management team emails
        $serviceTeamEmails = [
            '',
            'kpmarasigan@carmensbest.com.ph',
            'mavillaluz@carmensbest.com.ph',
            'jffelix@carmensbest.com.ph'
        ];

        // 1. Always send notification to creator (if they have an email and aren't the updater)
        if (!empty($creatorEmail) && $creatorEmail != $updaterEmail) {
            $mail = new PHPMailer(true);
            configureMailer($mail);
            
            $mail->addAddress($creatorEmail, $creatorName);
            
            // Get and apply threading headers
            $headers = getThreadingHeaders($ticketId, $conn);
            applyThreadingHeaders($mail, $headers, $subject, "Ticket Updated");
            
            $emailBody = generateTicketEmailBody(
                $srnId,
                $subject,
                $updaterName,
                $statusName,
                $ticketId,
                "Your ticket has been updated",
                true
            );

            $mail->Body = $emailBody;
            $mail->AltBody = strip_tags(str_replace("<br>", "\n", $emailBody));
            addEmailHeaders($mail);
            
            $mail->send();
            error_log("Sent ticket update notification to creator: " . $creatorEmail);
        }

        // 2. Send notification to updater if they're not the creator
        if (!empty($updaterEmail) && $updaterEmail != $creatorEmail) {
            $mail = new PHPMailer(true);
            configureMailer($mail);
            
            $mail->addAddress($updaterEmail, $updaterName);
            
            // Get and apply threading headers
            $headers = getThreadingHeaders($ticketId, $conn);
            applyThreadingHeaders($mail, $headers, $subject, "Ticket Updated");
            
            $emailBody = generateTicketEmailBody(
                $srnId,
                $subject,
                $updaterName,
                $statusName,
                $ticketId,
                "You have updated a ticket",
                true
            );

            $mail->Body = $emailBody;
            $mail->AltBody = strip_tags(str_replace("<br>", "\n", $emailBody));
            addEmailHeaders($mail);
            
            $mail->send();
            error_log("Sent ticket update notification to updater: " . $updaterEmail);
        }

        // 3. Always send notification to service management team
        $mail = new PHPMailer(true);
        configureMailer($mail);
        
        // Add service team members who aren't already notified
        $notifiedEmails = [$creatorEmail, $updaterEmail];
        foreach ($serviceTeamEmails as $email) {
            if (!in_array(strtolower($email), array_map('strtolower', $notifiedEmails))) {
                $mail->addAddress($email);
                error_log("Adding service team member to notification: " . $email);
            }
        }
        
        // Only send if there are recipients
        if (count($mail->getToAddresses()) > 0) {
            // Get and apply threading headers
            $headers = getThreadingHeaders($ticketId, $conn);
            applyThreadingHeaders($mail, $headers, $subject, "Ticket Updated");
            
            $emailBody = generateTicketEmailBody(
                $srnId,
                $subject,
                $updaterName,
                $statusName,
                $ticketId,
                "A ticket has been updated",
                false
            );

            $mail->Body = $emailBody;
            $mail->AltBody = strip_tags(str_replace("<br>", "\n", $emailBody));
            addEmailHeaders($mail);
            
            $mail->send();
            error_log("Sent ticket update notification to service team");
        }

        return true;

    } catch (Exception $e) {
        error_log("Ticket update notification email sending failed: " . $e->getMessage());
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
    $mail->CharSet = 'UTF-8';
    $mail->Encoding = 'base64';
}

/**
 * Generates email body for ticket notifications
 */
function generateTicketEmailBody($srnId, $subject, $creatorName, $statusName, $ticketId, $headerMessage, $isCreator) {
    return "
    <html>
    <head>
        <meta http-equiv='Content-Type' content='text/html; charset=UTF-8'>
    </head>
    <body style='font-family: Arial, sans-serif; line-height: 1.6;'>
                
            <div style='background-color: #f8f9fa; padding: 15px; margin-top: 20px; border-left: 4px solid #dc3545;'>
                <p style='color: #dc3545; font-weight: bold; margin: 0;'>IMPORTANT: This email was sent from a notification-only address that cannot accept incoming email. Please do not reply to this message.</p>
            </div>
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
                <a href='https://" . $_SERVER['HTTP_HOST'] . "/it-ticket/projectmanagement/threadPage.php?taskId={$ticketId}' 
                   style='background-color: #4CAF50; color: white; padding: 12px 20px; text-decoration: none; border-radius: 4px; font-weight: bold;'>
                   View Ticket
                </a>
            </div>

            <hr style='border: 0; border-top: 1px solid #e9ecef; margin: 20px 0;'>

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

/**
 * Sends task creation notification emails with separate emails for different recipients
 * 
 * @param object $conn Database connection
 * @param int $taskId Task ID
 * @param string $subject Task subject
 * @param array|string $assignees Assignee IDs
 * @param string $createdBy User ID who created the task
 * @return bool Success status
 */
function sendTaskCreateNotification($conn, $taskId, $subject, $assignees, $createdBy) {
    try {
        // Get the task data
        $taskQuery = "SELECT t.srn_id, t.statusid, s.statusname 
                     FROM pm_projecttasktb t 
                     LEFT JOIN sys_taskstatustb s ON t.statusid = s.id 
                     WHERE t.id = ?";
        $stmt = $conn->prepare($taskQuery);
        $stmt->bind_param("i", $taskId);
        $stmt->execute();
        $result = $stmt->get_result();
        $taskData = $result->fetch_assoc();
        
        $srnId = $taskData['srn_id'] ?? $taskId;
        $statusName = $taskData['statusname'] ?? 'New';

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

        // Get assignee information
        $assigneeEmails = [];
        $assigneeNames = [];
        if (!empty($assignees) && $assignees !== 'null') {
            $assigneeArray = is_array($assignees) ? $assignees : explode(',', $assignees);
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

        // Admin email
        $adminEmail = '';

        // 1. Send special notification to admin
        $mail = new PHPMailer(true);
        configureMailer($mail);
        $mail->addAddress($adminEmail);
        
        $headers = getThreadingHeaders($taskId, $conn);
        applyThreadingHeaders($mail, $headers, $subject, "New Task Created");
        
        // Special admin email body with all details
        $emailBody = generateTaskEmailBody(
            $srnId,
            $subject,
            $creatorName,
            $statusName,
            $taskId,
            "New Task Created - Admin Notification",
            $assigneeNames,
            true // New parameter to indicate admin email
        );

        $mail->Body = $emailBody;
        $mail->AltBody = strip_tags(str_replace("<br>", "\n", $emailBody));
        addEmailHeaders($mail);
        $mail->send();

        // 2. Send notification to creator
        if (!empty($creatorEmail)) {
            $mail = new PHPMailer(true);
            configureMailer($mail);
            $mail->addAddress($creatorEmail, $creatorName);
            
            $headers = getThreadingHeaders($taskId, $conn);
            applyThreadingHeaders($mail, $headers, $subject, "Task Created");
            
            $emailBody = generateTaskEmailBody(
                $srnId,
                $subject,
                $creatorName,
                $statusName,
                $taskId,
                "Your task has been created",
                $assigneeNames
            );

            $mail->Body = $emailBody;
            $mail->AltBody = strip_tags(str_replace("<br>", "\n", $emailBody));
            addEmailHeaders($mail);
            $mail->send();
        }

        // 3. Send notification to assignees if they exist
        if (!empty($assigneeEmails)) {
            $mail = new PHPMailer(true);
            configureMailer($mail);
            
            foreach ($assigneeEmails as $index => $email) {
                if ($email !== $creatorEmail) {
                    $mail->addAddress($email, $assigneeNames[$index] ?? '');
                }
            }
            
            if (count($mail->getToAddresses()) > 0) {
                $headers = getThreadingHeaders($taskId, $conn);
                applyThreadingHeaders($mail, $headers, $subject, "Task Created");
                
                $emailBody = generateTaskEmailBody(
                    $srnId,
                    $subject,
                    $creatorName,
                    $statusName,
                    $taskId,
                    "A new task has been assigned to you",
                    $assigneeNames
                );

                $mail->Body = $emailBody;
                $mail->AltBody = strip_tags(str_replace("<br>", "\n", $emailBody));
                addEmailHeaders($mail);
                $mail->send();
            }
        }
        // 4. Send to service team if no assignees (excluding admin who already received notification)
        else {
            $mail = new PHPMailer(true);
            configureMailer($mail);
            
            $serviceTeamEmails = [
                'mavillaluz@carmensbest.com.ph',
                'kpmarasigan@carmensbest.com.ph',
                'jffelix@carmensbest.com.ph'
            ];
            
            foreach ($serviceTeamEmails as $email) {
                $mail->addAddress($email);
            }
            
            $headers = getThreadingHeaders($taskId, $conn);
            applyThreadingHeaders($mail, $headers, $subject, "Task Created");
            
            $emailBody = generateTaskEmailBody(
                $srnId,
                $subject,
                $creatorName,
                $statusName,
                $taskId,
                "A new unassigned task has been created",
                []
            );

            $mail->Body = $emailBody;
            $mail->AltBody = strip_tags(str_replace("<br>", "\n", $emailBody));
            addEmailHeaders($mail);
            $mail->send();
        }

        return true;

    } catch (Exception $e) {
        error_log("Task creation notification email sending failed: " . $e->getMessage());
        return false;
    }
}

/**
 * Sends task update notification emails with separate emails for different recipients
 * 
 * @param object $conn Database connection
 * @param int $taskId Task ID
 * @param string $subject Task subject
 * @param array|string $assignees Assignee IDs
 * @param string $updatedBy User ID who updated the task
 * @return bool Success status
 */
function sendTaskUpdateNotification($conn, $taskId, $subject, $assignees, $updatedBy) {
    try {
        // Get the task data including resolution
        $taskQuery = "SELECT t.srn_id, t.statusid, t.createdbyid, t.resolution, s.statusname 
                     FROM pm_projecttasktb t 
                     LEFT JOIN sys_taskstatustb s ON t.statusid = s.id 
                     WHERE t.id = ?";
        $stmt = $conn->prepare($taskQuery);
        $stmt->bind_param("i", $taskId);
        $stmt->execute();
        $result = $stmt->get_result();
        $taskData = $result->fetch_assoc();
        
        $srnId = $taskData['srn_id'] ?? $taskId;
        $statusName = $taskData['statusname'] ?? 'Unknown Status';
        $createdBy = $taskData['createdbyid'] ?? '';
        $resolution = $taskData['resolution'] ?? '';

        // Get updater's information
        $updaterQuery = "SELECT emailadd, CONCAT(user_firstname, ' ', user_lastname) as fullname 
                        FROM sys_usertb WHERE id = ?";
        $stmt = $conn->prepare($updaterQuery);
        $stmt->bind_param("s", $updatedBy);
        $stmt->execute();
        $result = $stmt->get_result();
        $updaterInfo = $result->fetch_assoc();
        $updaterName = $updaterInfo['fullname'] ?? 'Unknown User';
        $updaterEmail = $updaterInfo['emailadd'] ?? '';

        // Get creator's information if different from updater
        $creatorEmail = '';
        $creatorName = '';
        if ($createdBy && $createdBy != $updatedBy) {
            $stmt = $conn->prepare($updaterQuery);
            $stmt->bind_param("s", $createdBy);
            $stmt->execute();
            $result = $stmt->get_result();
            $creatorInfo = $result->fetch_assoc();
            $creatorName = $creatorInfo['fullname'] ?? 'Unknown User';
            $creatorEmail = $creatorInfo['emailadd'] ?? '';
        }

        // Get assignee information
        $assigneeEmails = [];
        $assigneeNames = [];
        if (!empty($assignees) && $assignees !== 'null') {
            $assigneeArray = is_array($assignees) ? $assignees : explode(',', $assignees);
            foreach ($assigneeArray as $assigneeId) {
                if (empty($assigneeId)) continue;
                
                $stmt = $conn->prepare($updaterQuery);
                $stmt->bind_param("s", $assigneeId);
                $stmt->execute();
                $result = $stmt->get_result();
                if ($row = $result->fetch_assoc()) {
                    $assigneeEmails[] = $row['emailadd'];
                    $assigneeNames[] = $row['fullname'];
                }
            }
        }

        // Service management team emails
        $serviceTeamEmails = [
            '',
            'mavillaluz@carmensbest.com.ph',
            'kpmarasigan@carmensbest.com.ph',
            'jffelix@carmensbest.com.ph'
        ];

        // Track all notified emails to avoid duplicates
        $notifiedEmails = [];

        // 1. Always send a dedicated notification to creator regardless of who updated it
        if (!empty($creatorEmail)) {
            $mail = new PHPMailer(true);
            configureMailer($mail);
            $mail->addAddress($creatorEmail, $creatorName);
            
            // Don't CC anyone else, this is a dedicated email for the creator
            $headers = getThreadingHeaders($taskId, $conn);
            applyThreadingHeaders($mail, $headers, $subject, "Task Updated");
            
            // Create special email body with emphasis on resolution if task is done
            $specialMessage = ($statusName == "Done") ? 
                "Your task has been marked as complete" : 
                "Your task has been updated";
                
            $emailBody = generateTaskEmailBody(
                $srnId,
                $subject,
                $updaterName,
                $statusName,
                $taskId,
                $specialMessage,
                $assigneeNames,
                false, // isAdmin flag
                $resolution
            );

            $mail->Body = $emailBody;
            $mail->AltBody = strip_tags(str_replace("<br>", "\n", $emailBody));
            addEmailHeaders($mail);
            $mail->send();
            
            $notifiedEmails[] = strtolower($creatorEmail);
            error_log("Sent dedicated task update notification to creator: " . $creatorEmail);
        }

        // 2. Send notification to updater if they're not already notified
        if (!empty($updaterEmail) && !in_array(strtolower($updaterEmail), $notifiedEmails)) {
            $mail = new PHPMailer(true);
            configureMailer($mail);
            $mail->addAddress($updaterEmail, $updaterName);
            
            $headers = getThreadingHeaders($taskId, $conn);
            applyThreadingHeaders($mail, $headers, $subject, "Task Updated");
            
            $emailBody = generateTaskEmailBody(
                $srnId,
                $subject,
                $updaterName,
                $statusName,
                $taskId,
                "You have updated a task",
                $assigneeNames,
                false, // isAdmin flag
                $resolution
            );

            $mail->Body = $emailBody;
            $mail->AltBody = strip_tags(str_replace("<br>", "\n", $emailBody));
            addEmailHeaders($mail);
            $mail->send();
            
            $notifiedEmails[] = strtolower($updaterEmail);
            error_log("Sent task update notification to updater: " . $updaterEmail);
        }

        // 3. Send notification to assignees if they exist and haven't been notified yet
        if (!empty($assigneeEmails)) {
            $mail = new PHPMailer(true);
            configureMailer($mail);
            
            $hasRecipients = false;
            foreach ($assigneeEmails as $index => $email) {
                if (!in_array(strtolower($email), $notifiedEmails)) {
                    $mail->addAddress($email, $assigneeNames[$index] ?? '');
                    $notifiedEmails[] = strtolower($email);
                    $hasRecipients = true;
                }
            }
            
            if ($hasRecipients) {
                $headers = getThreadingHeaders($taskId, $conn);
                applyThreadingHeaders($mail, $headers, $subject, "Task Updated");
                
                $emailBody = generateTaskEmailBody(
                    $srnId,
                    $subject,
                    $updaterName,
                    $statusName,
                    $taskId,
                    "A task assigned to you has been updated",
                    $assigneeNames,
                    false, // isAdmin flag
                    $resolution
                );

                $mail->Body = $emailBody;
                $mail->AltBody = strip_tags(str_replace("<br>", "\n", $emailBody));
                addEmailHeaders($mail);
                $mail->send();
                error_log("Sent task update notification to assignees");
            }
        }

        // 4. Always send to service team members who haven't been notified yet
        $mail = new PHPMailer(true);
        configureMailer($mail);
        
        $hasServiceRecipients = false;
        foreach ($serviceTeamEmails as $email) {
            if (!in_array(strtolower($email), $notifiedEmails)) {
                $mail->addAddress($email);
                $hasServiceRecipients = true;
                error_log("Adding service team member to notification: " . $email);
            }
        }
        
        if ($hasServiceRecipients) {
            $headers = getThreadingHeaders($taskId, $conn);
            applyThreadingHeaders($mail, $headers, $subject, "Task Updated");
            
            $emailBody = generateTaskEmailBody(
                $srnId,
                $subject,
                $updaterName,
                $statusName,
                $taskId,
                "A task has been updated",
                $assigneeNames,
                false, // isAdmin flag
                $resolution
            );

            $mail->Body = $emailBody;
            $mail->AltBody = strip_tags(str_replace("<br>", "\n", $emailBody));
            addEmailHeaders($mail);
            $mail->send();
            error_log("Sent task update notification to service team");
        }

        return true;

    } catch (Exception $e) {
        error_log("Task update notification email sending failed: " . $e->getMessage());
        return false;
    }
}

/**
 * Fixes double-encoded UTF-8 strings (UTF-8 bytes interpreted as ISO-8859-1)
 * 
 * @param string $text The text to fix
 * @return string Fixed text with proper UTF-8 encoding
 */
function fixDoubleEncoding($text) {
    if (empty($text)) {
        return $text;
    }
    
    // First, ensure the string is treated as UTF-8
    // If it's already valid UTF-8, this won't change anything
    if (!mb_check_encoding($text, 'UTF-8')) {
        // Try to detect encoding
        $detected = @mb_detect_encoding($text, ['UTF-8', 'ISO-8859-1', 'Windows-1252', 'ASCII'], true);
        if ($detected && $detected !== 'UTF-8') {
            $text = mb_convert_encoding($text, 'UTF-8', $detected);
        } else {
            // Force to UTF-8, replacing invalid characters
            $text = mb_convert_encoding($text, 'UTF-8', 'UTF-8');
        }
    }
    
    // Check if text contains corrupted UTF-8 patterns (common indicators of double encoding)
    // Patterns like "Ã" followed by other characters often indicate UTF-8 bytes read as ISO-8859-1
    $hasCorruption = (mb_strpos($text, 'Ã', 0, 'UTF-8') !== false || 
                     mb_strpos($text, 'Â', 0, 'UTF-8') !== false ||
                     preg_match('/Ã[^\s<]/u', $text));
    
    if (!$hasCorruption) {
        return $text; // No corruption detected, return as-is
    }
    
    // Method 1: Treat the string as if UTF-8 bytes were incorrectly interpreted as ISO-8859-1
    // This fixes cases where "Ñ" (C3 91 in UTF-8) was read as "Ã" + something
    $fixed = @mb_convert_encoding($text, 'UTF-8', 'ISO-8859-1');
    if ($fixed !== false && $fixed !== $text && mb_check_encoding($fixed, 'UTF-8')) {
        // Verify the fix worked by checking if corruption patterns are gone
        if (mb_strpos($fixed, 'Ã', 0, 'UTF-8') === false && mb_strpos($fixed, 'Â', 0, 'UTF-8') === false) {
            return $fixed;
        }
    }
    
    // Method 2: iconv approach - same concept
    $fixed = @iconv('ISO-8859-1', 'UTF-8//IGNORE', $text);
    if ($fixed !== false && $fixed !== $text && mb_check_encoding($fixed, 'UTF-8')) {
        if (mb_strpos($fixed, 'Ã', 0, 'UTF-8') === false && mb_strpos($fixed, 'Â', 0, 'UTF-8') === false) {
            return $fixed;
        }
    }
    
    // Method 3: Try utf8_decode/encode cycle (for triple-encoded strings)
    $decoded = @utf8_decode($text);
    if ($decoded !== false && $decoded !== $text) {
        $reencoded = @utf8_encode($decoded);
        if ($reencoded !== false && mb_check_encoding($reencoded, 'UTF-8')) {
            if (mb_strpos($reencoded, 'Ã', 0, 'UTF-8') === false) {
                return $reencoded;
            }
        }
    }
    
    // If all methods failed, return original (might be valid text containing those rare characters)
    return $text;
}

/**
 * Processes resolution text for email display - fixes encoding and preserves formatting
 * Allows professional HTML formatting while maintaining security
 * 
 * @param string $resolution The resolution text to process
 * @return string Processed resolution text safe for HTML display
 */
function processResolutionForEmail($resolution) {
    if (empty($resolution) || $resolution === 'NULL' || strtolower(trim($resolution)) === 'null') {
        return '';
    }
    
    // Clean and sanitize the resolution text
    $cleanResolution = trim($resolution);
    
    // Fix double-encoding issues FIRST before any other processing
    $cleanResolution = fixDoubleEncoding($cleanResolution);
    
    // Ensure valid UTF-8 encoding
    if (!mb_check_encoding($cleanResolution, 'UTF-8')) {
        // Try to detect and fix encoding
        $detected = @mb_detect_encoding($cleanResolution, ['UTF-8', 'ISO-8859-1', 'Windows-1252', 'ASCII'], true);
        if ($detected && $detected !== 'UTF-8') {
            $cleanResolution = mb_convert_encoding($cleanResolution, 'UTF-8', $detected);
        } else {
            // Force UTF-8, replacing invalid characters
            $cleanResolution = mb_convert_encoding($cleanResolution, 'UTF-8', 'UTF-8');
        }
    }
    
    // Professional formatting: Add line breaks and paragraph breaks for structured entries
    // This handles cases like "bp code:" entries that run together without breaks
    
    // Step 1: Normalize existing line breaks
    $cleanResolution = preg_replace('/\r\n|\r/', "\n", $cleanResolution);
    
    // Step 2: First, combine any "bp" and "code:" that are on separate lines
    // This handles cases where "bp" is on one line and "code:" is on the next
    // Pattern: line ending with "bp" (possibly with whitespace) followed by newline and "code:"
    $cleanResolution = preg_replace('/\bbp\s*\n\s*code:\s*/i', 'bp code: ', $cleanResolution);
    // Also handle case where "bp" is alone on a line followed by "code:" on next line
    $cleanResolution = preg_replace('/^bp\s*$\n\s*code:\s*/mi', 'bp code: ', $cleanResolution);
    // Ensure "bp code:" stays together (remove any line breaks between them)
    $cleanResolution = preg_replace('/\bbp\s+code\s*:\s*/i', 'bp code: ', $cleanResolution);
    
    // Step 3: Detect when "bp code:" appears immediately after text (no break)
    // Add line break before "bp code:" if it's not already at the start of a line
    // Normalize to lowercase "bp code:" for consistency
    $cleanResolution = preg_replace('/([^\n])(bp\s+code|BP\s+CODE):/i', "$1\nbp code:", $cleanResolution);
    
    // Step 4: Split into lines and process entries
    // Use PREG_SPLIT_DELIM_CAPTURE to preserve empty lines (paragraph breaks)
    $lines = preg_split('/\n/', $cleanResolution, -1);
    $processedLines = [];
    $previousEntrySignature = '';
    
    foreach ($lines as $line) {
        $line = trim($line);
        // Preserve empty lines as paragraph breaks
        if (empty($line)) {
            if (!empty($processedLines) && end($processedLines) !== '') {
                $processedLines[] = '';
            }
            continue;
        }
        
        // Detect if this is a "bp code:" entry (must have "bp code:" together)
        $isBpCodeEntry = preg_match('/^(bp\s+code|BP\s+CODE):\s*(.+)$/i', $line, $matches);
        
        if ($isBpCodeEntry) {
            // Extract the entry data part
            $entryData = trim($matches[2]);
            if (empty($entryData)) {
                continue; // Skip if no data
            }
            
            // Normalize signature for comparison (remove extra spaces, lowercase)
            $signature = preg_replace('/\s+/', ' ', strtolower($entryData));
            
            // Format the entry properly: "bp code: data" (keep together on one line)
            $formattedLine = 'bp code: ' . $entryData;
            
            // Check if this is a repeated entry (same as previous)
            if (!empty($signature) && $signature === $previousEntrySignature && !empty($processedLines)) {
                // Add paragraph break before repeated entry (empty line)
                if (end($processedLines) !== '') {
                    $processedLines[] = '';
                }
                $processedLines[] = $formattedLine;
            } else {
                // New entry or first entry
                // Add paragraph break between different bp code entries only if not already separated
                if (!empty($previousEntrySignature) && !empty($processedLines)) {
                    $lastLine = end($processedLines);
                    // Only add paragraph break if last line is not already empty (paragraph break)
                    if ($lastLine !== '') {
                        $processedLines[] = '';
                    }
                }
                $processedLines[] = $formattedLine;
            }
            
            // Update previous signature
            $previousEntrySignature = $signature;
        } else {
            // Check if this line might be a continuation of a previous entry
            // If previous line was a bp code entry, this might be additional data
            if (!empty($previousEntrySignature) && !empty($processedLines)) {
                $lastLine = end($processedLines);
                // If last line ends with "bp code:", append this line to it
                if (preg_match('/^bp code:/i', $lastLine)) {
                    $processedLines[count($processedLines) - 1] = $lastLine . ' ' . $line;
                    continue;
                }
            }
            
            // Regular text line - add as-is
            $processedLines[] = $line;
            // Reset signature for non-entry lines
            $previousEntrySignature = '';
        }
    }
    
    // Rejoin with proper line breaks
    // Convert empty lines (paragraph breaks) to double newlines for proper paragraph separation
    $rejoined = [];
    foreach ($processedLines as $idx => $line) {
        if ($line === '') {
            // Empty line indicates paragraph break - use double newline
            $rejoined[] = "\n\n";
        } else {
            $rejoined[] = $line;
        }
    }
    $cleanResolution = implode("\n", $rejoined);
    
    // Allow safe formatting tags for professional display
    // Preserve: paragraphs, line breaks, bold, italic, underline, lists, headings
    $allowedTags = '<p><br><br/><br /><strong><b><em><i><u><ul><ol><li><h1><h2><h3><h4><h5><h6><blockquote><pre><code><span><div>';
    $cleanResolution = strip_tags($cleanResolution, $allowedTags);
    
    // Remove any remaining dangerous tags and event handlers as extra security
    $cleanResolution = preg_replace('/<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/mi', '', $cleanResolution);
    $cleanResolution = preg_replace('/<iframe\b[^<]*(?:(?!<\/iframe>)<[^<]*)*<\/iframe>/mi', '', $cleanResolution);
    $cleanResolution = preg_replace('/<object\b[^<]*(?:(?!<\/object>)<[^<]*)*<\/object>/mi', '', $cleanResolution);
    $cleanResolution = preg_replace('/<embed\b[^<]*(?:(?!<\/embed>)<[^<]*)*<\/embed>/mi', '', $cleanResolution);
    
    // Remove event handlers and dangerous attributes
    $cleanResolution = preg_replace('/on\w+="[^"]*"/i', '', $cleanResolution);
    $cleanResolution = preg_replace('/on\w+=\'[^\']*\'/i', '', $cleanResolution);
    $cleanResolution = preg_replace('/javascript:/i', '', $cleanResolution);
    $cleanResolution = preg_replace('/vbscript:/i', '', $cleanResolution);
    $cleanResolution = preg_replace('/data:text\/html/i', '', $cleanResolution);
    
    // Preserve multiple spaces (convert to non-breaking spaces for professional formatting)
    // Limit consecutive spaces to prevent abuse
    $cleanResolution = preg_replace_callback('/ {2,}/', function($matches) {
        // Limit to max 4 consecutive spaces for professional formatting
        $spaces = substr($matches[0], 0, 4);
        return str_repeat('&nbsp;', strlen($spaces));
    }, $cleanResolution);
    
    // Convert newlines to HTML breaks
    // Double newlines (paragraph breaks) will become double <br> tags for natural spacing
    // Single newlines will become <br> tags
    if (strip_tags($cleanResolution) === $cleanResolution) {
        // First, convert double newlines to paragraph break markers
        $cleanResolution = preg_replace('/\n\n+/', '||PARAGRAPH_BREAK||', $cleanResolution);
        // Convert single newlines to <br> tags
        $cleanResolution = nl2br($cleanResolution);
        // Convert paragraph break markers to double <br> tags (more natural spacing)
        $cleanResolution = preg_replace('/\|\|PARAGRAPH_BREAK\|\|/', '<br><br>', $cleanResolution);
    }
    
    // Clean up empty HTML tags
    $cleanResolution = preg_replace('/<([^>]+)>\s*<\/\1>/i', '', $cleanResolution);
    $cleanResolution = preg_replace('/<p>\s*<\/p>/i', '', $cleanResolution);
    $cleanResolution = preg_replace('/<div>\s*<\/div>/i', '', $cleanResolution);
    // Reduce excessive line breaks (more than 2 consecutive <br> tags become just 2)
    $cleanResolution = preg_replace('/(<br\s*\/?>\s*){3,}/i', '<br><br>', $cleanResolution);
    
    // Ensure proper spacing around block elements
    $cleanResolution = preg_replace('/<\/(p|h[1-6]|blockquote|pre|ul|ol|li|div)>\s*<\1>/i', '</$1><br><$1>', $cleanResolution);
    
    // For content that already has HTML tags, we need to escape text content
    // but preserve the HTML structure
    if (strip_tags($cleanResolution) !== $cleanResolution) {
        // Has HTML tags - escape text content outside tags while preserving tags
        // Split by tags, escape text parts, preserve tag parts
        $parts = preg_split('/(<[^>]+>)/', $cleanResolution, -1, PREG_SPLIT_DELIM_CAPTURE);
        $result = '';
        foreach ($parts as $part) {
            if (preg_match('/^<[^>]+>$/', $part)) {
                // This is an HTML tag, keep it as is (already sanitized above)
                $result .= $part;
            } else {
                // This is text content, escape it
                $result .= htmlspecialchars($part, ENT_QUOTES | ENT_HTML5, 'UTF-8', false);
            }
        }
        $cleanResolution = $result;
    } else {
        // No HTML tags, just escape the whole thing
        $cleanResolution = htmlspecialchars($cleanResolution, ENT_QUOTES | ENT_HTML5, 'UTF-8', false);
    }
    
    // Final cleanup: remove any remaining problematic patterns
    $cleanResolution = preg_replace('/&nbsp;{3,}/', '&nbsp;&nbsp;', $cleanResolution);
    
    return $cleanResolution;
}

/**
 * Generates email body for task notifications
 */
function generateTaskEmailBody($srnId, $subject, $updaterName, $statusName, $taskId, $headerMessage, $assigneeNames, $isAdminEmail = false, $resolution = '') {
    $assigneeSection = '';
    if (!empty($assigneeNames)) {
        $assigneeList = implode(', ', $assigneeNames);
        $assigneeSection = "<p><strong>Assigned To:</strong> {$assigneeList}</p>";
    }
    
    // Resolution section - only display if there is meaningful content
    $resolutionSection = '';
    if (!empty($resolution) && $resolution !== 'NULL' && strtolower(trim($resolution)) !== 'null') {
        // Process resolution using helper function (fixes encoding, preserves formatting)
        $cleanResolution = processResolutionForEmail($resolution);
        
        if (!empty($cleanResolution)) {
            $resolutionSection = "
                <div style='background-color: #e9f7ef; padding: 12px; margin: 15px 0; border-left: 4px solid #27ae60; border-radius: 5px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);'>
                    <h3 style='color: #27ae60; margin-top: 0; margin-bottom: 8px; font-size: 16px; font-weight: bold;'>Resolution</h3>
                    <div style='line-height: 1.6; color: #2c3e50; font-size: 14px; word-wrap: break-word; overflow-wrap: break-word;'>
                        {$cleanResolution}
                    </div>
                </div>";
        }
    }

    // Additional information for admin emails
    $adminSection = '';
    if ($isAdminEmail) {
        $adminSection = "
            <div style='background-color: #fff3cd; border-left: 4px solid #ffc107; padding: 15px; margin: 20px 0;'>
                <h3 style='color: #856404; margin-top: 0;'>Admin Notification</h3>
                <p>This is an administrative notification for task creation monitoring.</p>
                <p>Please review the task details and ensure proper assignment and prioritization.</p>
            </div>";
    }

    return "
    <html>
    <head>
        <meta http-equiv='Content-Type' content='text/html; charset=UTF-8'>
    </head>
    <body style='font-family: Arial, sans-serif; line-height: 1.6;'>
        
            <div style='background-color: #f8f9fa; padding: 15px; margin-top: 20px; border-left: 4px solid #dc3545;'>
                <p style='color: #dc3545; font-weight: bold; margin: 0;'>IMPORTANT: This email was sent from a notification-only address that cannot accept incoming email. Please do not reply to this message.</p>
            </div>
        <div style='max-width: 600px; margin: 0 auto; padding: 20px;'>
            <div style='text-align: center; margin-bottom: 20px;'>
                <div style='background-color: #3498db; color: white; padding: 20px; text-align: center; font-weight: bold; font-size: 24px; border-radius: 5px;'>TLCI</div>
            </div>
            
            {$adminSection}
            
            <div style='background-color: #f8f9fa; border-left: 4px solid #4CAF50; padding: 15px; margin-bottom: 20px;'>
                <h2 style='color: #2C3E50; margin-top: 0;'>{$headerMessage}</h2>
                <p><strong>SRN ID:</strong> {$srnId}</p>
                <p><strong>Subject:</strong> {$subject}</p>
                <p><strong>Created By:</strong> {$updaterName}</p>
                {$assigneeSection}
                <p><strong>Status:</strong> {$statusName}</p>
            </div>
            
            {$resolutionSection}

            <div style='text-align: center; margin: 30px 0;'>
                <a href='https://" . $_SERVER['HTTP_HOST'] . "/it-ticket/projectmanagement/threadPage.php?taskId={$taskId}' 
                   style='background-color: #4CAF50; color: white; padding: 12px 20px; text-decoration: none; border-radius: 4px; font-weight: bold;'>
                   View Thread
                </a>
            </div>

            <hr style='border: 0; border-top: 1px solid #e9ecef; margin: 20px 0;'>
        
        </div>
    </body>
    </html>";
}

/**
 * Sends subtask creation notification emails
 * 
 * @param object $conn Database connection
 * @param int $subtaskId Subtask ID
 * @param string $subject Subtask subject
 * @param array|string $assignees Assignee IDs
 * @param string $createdBy User ID who created the subtask
 * @param int $parentTaskId Parent task ID
 * @return bool Success status
 */
function sendSubtaskCreateNotification($conn, $subtaskId, $subject, $assignees, $createdBy, $parentTaskId) {
    try {
        // Get the subtask and parent task data
        $taskQuery = "SELECT t.srn_id, t.statusid, s.statusname, 
                            p.subject as parent_subject, p.srn_id as parent_srn
                     FROM pm_projecttasktb t 
                     LEFT JOIN sys_taskstatustb s ON t.statusid = s.id 
                     LEFT JOIN pm_projecttasktb p ON t.parent_id = p.id
                     WHERE t.id = ?";
        $stmt = $conn->prepare($taskQuery);
        $stmt->bind_param("i", $subtaskId);
        $stmt->execute();
        $result = $stmt->get_result();
        $taskData = $result->fetch_assoc();
        
        $srnId = $taskData['srn_id'] ?? $subtaskId;
        $statusName = $taskData['statusname'] ?? 'New';
        $parentSubject = $taskData['parent_subject'] ?? '';
        $parentSRN = $taskData['parent_srn'] ?? '';

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

        // Get assignee information
        $assigneeEmails = [];
        $assigneeNames = [];
        if (!empty($assignees) && $assignees !== 'null') {
            $assigneeArray = is_array($assignees) ? $assignees : explode(',', $assignees);
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

        // Admin email
        $adminEmail = '';

        // 1. Send special notification to admin
        $mail = new PHPMailer(true);
        configureMailer($mail);
        $mail->addAddress($adminEmail);
        
        $headers = getThreadingHeaders($subtaskId, $conn);
        applyThreadingHeaders($mail, $headers, $subject, "New Subtask Created");
        
        $emailBody = generateSubtaskEmailBody(
            $srnId,
            $subject,
            $creatorName,
            $statusName,
            $subtaskId,
            "New Subtask Created - Admin Notification",
            $assigneeNames,
            $parentSubject,
            $parentSRN,
            true
        );

        $mail->Body = $emailBody;
        $mail->AltBody = strip_tags(str_replace("<br>", "\n", $emailBody));
        addEmailHeaders($mail);
        $mail->send();

        // 2. Send notification to creator
        if (!empty($creatorEmail)) {
            $mail = new PHPMailer(true);
            configureMailer($mail);
            $mail->addAddress($creatorEmail, $creatorName);
            
            $headers = getThreadingHeaders($subtaskId, $conn);
            applyThreadingHeaders($mail, $headers, $subject, "Subtask Created");
            
            $emailBody = generateSubtaskEmailBody(
                $srnId,
                $subject,
                $creatorName,
                $statusName,
                $subtaskId,
                "Your subtask has been created",
                $assigneeNames,
                $parentSubject,
                $parentSRN
            );

            $mail->Body = $emailBody;
            $mail->AltBody = strip_tags(str_replace("<br>", "\n", $emailBody));
            addEmailHeaders($mail);
            $mail->send();
        }

        // 3. Send notification to assignees
        if (!empty($assigneeEmails)) {
            $mail = new PHPMailer(true);
            configureMailer($mail);
            
            foreach ($assigneeEmails as $index => $email) {
                if ($email !== $creatorEmail) {
                    $mail->addAddress($email, $assigneeNames[$index] ?? '');
                }
            }
            
            if (count($mail->getToAddresses()) > 0) {
                $headers = getThreadingHeaders($subtaskId, $conn);
                applyThreadingHeaders($mail, $headers, $subject, "Subtask Created");
                
                $emailBody = generateSubtaskEmailBody(
                    $srnId,
                    $subject,
                    $creatorName,
                    $statusName,
                    $subtaskId,
                    "A new subtask has been assigned to you",
                    $assigneeNames,
                    $parentSubject,
                    $parentSRN
                );

                $mail->Body = $emailBody;
                $mail->AltBody = strip_tags(str_replace("<br>", "\n", $emailBody));
                addEmailHeaders($mail);
                $mail->send();
            }
        }

        return true;

    } catch (Exception $e) {
        error_log("Subtask creation notification email sending failed: " . $e->getMessage());
        return false;
    }
}

/**
 * Generates email body for subtask notifications
 */
function generateSubtaskEmailBody($srnId, $subject, $userName, $statusName, $taskId, $headerMessage, $assigneeNames, $parentSubject, $parentSRN, $isAdminEmail = false) {
    $assigneeSection = '';
    if (!empty($assigneeNames)) {
        $assigneeList = implode(', ', $assigneeNames);
        $assigneeSection = "<p><strong>Assigned To:</strong> {$assigneeList}</p>";
    }

    $adminSection = '';
    if ($isAdminEmail) {
        $adminSection = "
            <div style='background-color: #fff3cd; border-left: 4px solid #ffc107; padding: 15px; margin: 20px 0;'>
                <h3 style='color: #856404; margin-top: 0;'>Admin Notification</h3>
                <p>This is an administrative notification for subtask creation monitoring.</p>
                <p>Please review the subtask details and ensure proper assignment and prioritization.</p>
            </div>";
    }

    $parentSection = "
        <div style='background-color: #e9ecef; padding: 15px; margin: 10px 0; border-radius: 5px;'>
            <h3 style='color: #495057; margin-top: 0;'>Parent Task Information</h3>
            <p><strong>Parent SRN:</strong> {$parentSRN}</p>
            <p><strong>Parent Task:</strong> {$parentSubject}</p>
        </div>";

    return "
    <html>
    <body style='font-family: Arial, sans-serif; line-height: 1.6;'>
      
            <div style='background-color: #f8f9fa; padding: 15px; margin-top: 20px; border-left: 4px solid #dc3545;'>
                <p style='color: #dc3545; font-weight: bold; margin: 0;'>IMPORTANT: This email was sent from a notification-only address that cannot accept incoming email. Please do not reply to this message.</p>
            </div>
        <div style='max-width: 600px; margin: 0 auto; padding: 20px;'>
            <div style='text-align: center; margin-bottom: 20px;'>
                <div style='background-color: #3498db; color: white; padding: 20px; text-align: center; font-weight: bold; font-size: 24px; border-radius: 5px;'>TLCI</div>
            </div>
            
            {$adminSection}
            
            <div style='background-color: #f8f9fa; border-left: 4px solid #4CAF50; padding: 15px; margin-bottom: 20px;'>
                <h2 style='color: #2C3E50; margin-top: 0;'>{$headerMessage}</h2>
                <p><strong>SRN ID:</strong> {$srnId}</p>
                <p><strong>Subject:</strong> {$subject}</p>
                <p><strong>Created By:</strong> {$userName}</p>
                {$assigneeSection}
                <p><strong>Status:</strong> {$statusName}</p>
            </div>

            {$parentSection}

            <div style='text-align: center; margin: 30px 0;'>
                <a href='https://" . $_SERVER['HTTP_HOST'] . "/it-ticket/projectmanagement/threadPage.php?taskId={$taskId}' 
                   style='background-color: #4CAF50; color: white; padding: 12px 20px; text-decoration: none; border-radius: 4px; font-weight: bold;'>
                   View Subtask
                </a>
            </div>

            <hr style='border: 0; border-top: 1px solid #e9ecef; margin: 20px 0;'>
          
        </div>
    </body>
    </html>";
}

/**
 * Sends subtask update notification emails with separate emails for different recipients
 * 
 * @param object $conn Database connection
 * @param int $subtaskId Subtask ID
 * @param string $subject Subtask subject
 * @param array|string $assignees Assignee IDs
 * @param string $updatedBy User ID who updated the subtask
 * @param int $parentTaskId Parent task ID
 * @return bool Success status
 */
function sendSubtaskUpdateNotification($conn, $subtaskId, $subject, $assignees, $updatedBy, $parentTaskId) {
    try {
        // Get the subtask and parent task data
        $taskQuery = "SELECT t.srn_id, t.statusid, t.createdbyid, s.statusname, 
                            p.subject as parent_subject, p.srn_id as parent_srn
                     FROM pm_projecttasktb t 
                     LEFT JOIN sys_taskstatustb s ON t.statusid = s.id 
                     LEFT JOIN pm_projecttasktb p ON t.parent_id = p.id
                     WHERE t.id = ?";
        $stmt = $conn->prepare($taskQuery);
        $stmt->bind_param("i", $subtaskId);
        $stmt->execute();
        $result = $stmt->get_result();
        $taskData = $result->fetch_assoc();
        
        $srnId = $taskData['srn_id'] ?? $subtaskId;
        $statusName = $taskData['statusname'] ?? 'Unknown Status';
        $createdBy = $taskData['createdbyid'] ?? '';
        $parentSubject = $taskData['parent_subject'] ?? '';
        $parentSRN = $taskData['parent_srn'] ?? '';

        // Get updater's information
        $userQuery = "SELECT emailadd, CONCAT(user_firstname, ' ', user_lastname) as fullname 
                     FROM sys_usertb WHERE id = ?";
        $stmt = $conn->prepare($userQuery);
        $stmt->bind_param("s", $updatedBy);
        $stmt->execute();
        $result = $stmt->get_result();
        $updaterInfo = $result->fetch_assoc();
        $updaterName = $updaterInfo['fullname'] ?? 'Unknown User';
        $updaterEmail = $updaterInfo['emailadd'] ?? '';

        // Get creator's information
        $creatorEmail = '';
        $creatorName = '';
        if ($createdBy) {
            $stmt = $conn->prepare($userQuery);
            $stmt->bind_param("s", $createdBy);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($creatorInfo = $result->fetch_assoc()) {
                $creatorName = $creatorInfo['fullname'];
                $creatorEmail = $creatorInfo['emailadd'];
            }
        }

        // Get assignee information
        $assigneeEmails = [];
        $assigneeNames = [];
        if (!empty($assignees)) {
            $assigneeArray = is_array($assignees) ? $assignees : explode(',', $assignees);
            foreach ($assigneeArray as $assigneeId) {
                if (empty($assigneeId)) continue;
                
                $stmt = $conn->prepare($userQuery);
                $stmt->bind_param("s", $assigneeId);
                $stmt->execute();
                $result = $stmt->get_result();
                if ($row = $result->fetch_assoc()) {
                    $assigneeEmails[] = $row['emailadd'];
                    $assigneeNames[] = $row['fullname'];
                }
            }
        }

        // Service team emails for when there are no assignees
        $serviceTeamEmails = [
            '',
            'mavillaluz@carmensbest.com.ph',
            'kpmarasigan@carmensbest.com.ph',
            'jffelix@carmensbest.com.ph'
        ];

        // 1. Send notification to creator
        if (!empty($creatorEmail)) {
            $mail = new PHPMailer(true);
            configureMailer($mail);
            $mail->addAddress($creatorEmail, $creatorName);
            
            $headers = getThreadingHeaders($subtaskId, $conn);
            applyThreadingHeaders($mail, $headers, $subject, "Subtask Updated");
            
            $emailBody = generateSubtaskEmailBody(
                $srnId,
                $subject,
                $updaterName,
                $statusName,
                $subtaskId,
                "Your subtask has been updated",
                $assigneeNames,
                $parentSubject,
                $parentSRN
            );

            $mail->Body = $emailBody;
            $mail->AltBody = strip_tags(str_replace("<br>", "\n", $emailBody));
            addEmailHeaders($mail);
            $mail->send();
            
            error_log("Sent update notification to creator: $creatorEmail");
        }

        // 2. Send notification to assignees (if they exist and aren't the creator)
        if (!empty($assigneeEmails)) {
            foreach ($assigneeEmails as $index => $email) {
                if ($email !== $creatorEmail && $email !== $updaterEmail) {
                    $mail = new PHPMailer(true);
                    configureMailer($mail);
                    $mail->addAddress($email, $assigneeNames[$index] ?? '');
                    
                    $headers = getThreadingHeaders($subtaskId, $conn);
                    applyThreadingHeaders($mail, $headers, $subject, "Subtask Updated");
                    
                    $emailBody = generateSubtaskEmailBody(
                        $srnId,
                        $subject,
                        $updaterName,
                        $statusName,
                        $subtaskId,
                        "A subtask assigned to you has been updated",
                        $assigneeNames,
                        $parentSubject,
                        $parentSRN
                    );

                    $mail->Body = $emailBody;
                    $mail->AltBody = strip_tags(str_replace("<br>", "\n", $emailBody));
                    addEmailHeaders($mail);
                    $mail->send();
                    
                    error_log("Sent update notification to assignee: $email");
                }
            }
        }
        // If no assignees, send to service team
        else {
            foreach ($serviceTeamEmails as $email) {
                if ($email !== $creatorEmail && $email !== $updaterEmail) {
                    $mail = new PHPMailer(true);
                    configureMailer($mail);
                    $mail->addAddress($email);
                    
                    $headers = getThreadingHeaders($subtaskId, $conn);
                    applyThreadingHeaders($mail, $headers, $subject, "Subtask Updated");
                    
                    $emailBody = generateSubtaskEmailBody(
                        $srnId,
                        $subject,
                        $updaterName,
                        $statusName,
                        $subtaskId,
                        "An unassigned subtask has been updated",
                        [],
                        $parentSubject,
                        $parentSRN
                    );

                    $mail->Body = $emailBody;
                    $mail->AltBody = strip_tags(str_replace("<br>", "\n", $emailBody));
                    addEmailHeaders($mail);
                    $mail->send();
                    
                    error_log("Sent update notification to service team member: $email");
                }
            }
        }

        // 3. Send notification to admin ()
        $adminEmail = '';
        if ($adminEmail !== $creatorEmail && $adminEmail !== $updaterEmail) {
            $mail = new PHPMailer(true);
            configureMailer($mail);
            $mail->addAddress($adminEmail);
            
            $headers = getThreadingHeaders($subtaskId, $conn);
            applyThreadingHeaders($mail, $headers, $subject, "Subtask Updated");
            
            $emailBody = generateSubtaskEmailBody(
                $srnId,
                $subject,
                $updaterName,
                $statusName,
                $subtaskId,
                "Subtask Update Notification - Admin",
                $assigneeNames,
                $parentSubject,
                $parentSRN,
                true // isAdmin flag
            );

            $mail->Body = $emailBody;
            $mail->AltBody = strip_tags(str_replace("<br>", "\n", $emailBody));
            addEmailHeaders($mail);
            $mail->send();
            
            error_log("Sent update notification to admin: $adminEmail");
        }

        return true;

    } catch (Exception $e) {
        error_log("Subtask update notification email sending failed: " . $e->getMessage());
        return false;
    }
}

/**
 * Sends notification email to management committee members when a new ticket is created
 * 
 * @param object $conn Database connection
 * @param int $ticketId Ticket ID
 * @param string $subject Ticket subject
 * @param string $createdBy User ID who created the ticket
 * @param array $attachments Optional array of attachments from $_FILES
 * @return bool Success status
 */
function sendManComNotification($conn, $ticketId, $subject, $createdBy, $attachments = null) {
    try {
        // Get the ticket data
        $ticketQuery = "SELECT t.srn_id, t.company_name, t.description
                       FROM pm_projecttasktb2 t 
                       WHERE t.id = ?";
        $stmt = $conn->prepare($ticketQuery);
        $stmt->bind_param("i", $ticketId);
        $stmt->execute();
        $result = $stmt->get_result();
        $ticketData = $result->fetch_assoc();
        
        $srnId = $ticketData['srn_id'] ?? $ticketId;
        $companyName = $ticketData['company_name'] ?? '';
        $description = $ticketData['description'] ?? '';

        // Get creator's information
        $creatorQuery = "SELECT CONCAT(user_firstname, ' ', user_lastname) as fullname 
                        FROM sys_usertb WHERE id = ?";
        $stmt = $conn->prepare($creatorQuery);
        $stmt->bind_param("s", $createdBy);
        $stmt->execute();
        $result = $stmt->get_result();
        $creatorInfo = $result->fetch_assoc();
        $creatorName = $creatorInfo['fullname'] ?? 'Unknown User';

        // ManCom email recipients
        $mancomEmails = [
            // 'jihernandez@mpav.com.ph',
            // 'togatchalian@mpav.com.ph',
            // 'bdmapa@mpav.com.ph',
            // 'rsgripal@mpav.com.ph'

            // 'mavillaluz@carmensbest.com.ph',
            // 'mmredondo@carmensbest.com.ph',
            // 'kpmarasigan@carmensbest.com.ph',
            // 'jffelix@carmensbest.com.ph'
        ];

        // Email subject: subject + company_name
        $emailSubject = "{$subject} - {$companyName}";

        // Get uploaded files data once
        $fileQuery = "SELECT file_data FROM pm_threadtb WHERE taskid = ?";
        $stmt = $conn->prepare($fileQuery);
        $stmt->bind_param("i", $ticketId);
        $stmt->execute();
        $fileResult = $stmt->get_result();
        $fileData = $fileResult->fetch_all(MYSQLI_ASSOC);

        // Send individual emails to each ManCom member
        foreach ($mancomEmails as $recipientEmail) {
            if (empty($recipientEmail)) continue;

            $mail = new PHPMailer(true);
            
            // Server settings
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'tlcreameryinc@gmail.com';
            $mail->Password = 'kdvg bueb seul rfnw';
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = 587;
            
            $mail->setFrom('tlcreameryinc@gmail.com', 'Whistleblower Hotline');
            $mail->addReplyTo('no-reply@tlcreameryinc.com', 'Whistleblower Hotline(Do Not Reply)');

            // Add single recipient
            $mail->addAddress($recipientEmail);
            $mail->Subject = $emailSubject;

            // Start building email body
            $emailBody = "
            <html>
            <body>
                <p><strong>Ticket ID:</strong> {$srnId}</p>
                <p><strong>Sender:</strong> {$creatorName}</p>
                <p><strong>Company:</strong> {$companyName}</p>
                <p><strong>Description:</strong><br>" . $description . "</p>";

            // Process attachments if any exist
            if (!empty($fileData)) {
                $emailBody .= "<p><strong>Attachments:</strong></p><ul>";
                
                foreach ($fileData as $file) {
                    $relativePaths = explode(',', $file['file_data']);
                    
                    foreach ($relativePaths as $relativePath) {
                        $relativePath = trim($relativePath);
                        if (empty($relativePath)) continue;
                        
                        // Get just the filename from the path
                        $fileName = basename($relativePath);
                        
                        // Construct absolute path
                        $absolutePath = __DIR__ . '/' . $relativePath;
                        $absolutePath = str_replace('\\', '/', $absolutePath);
                        
                        error_log("Checking file for recipient {$recipientEmail}: " . $absolutePath);
                        
                        // Check if file exists and is readable
                        if (file_exists($absolutePath) && is_readable($absolutePath)) {
                            // Determine if it's an image based on extension
                            $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
                            $isImage = in_array($fileExtension, ['jpg', 'jpeg', 'png', 'gif', 'bmp']);
                            
                            if ($isImage) {
                                $cid = md5($fileName . time() . rand());
                                $mail->addEmbeddedImage($absolutePath, $cid, $fileName);
                                $emailBody .= "<li><strong>" . htmlspecialchars($fileName) . "</strong><br>
                                             <img src='cid:$cid' style='max-width: 800px; margin: 10px 0;' alt='" . htmlspecialchars($fileName) . "'></li>";
                            } else {
                                // For non-image files, just list them
                                $emailBody .= "<li><strong>Attachment:</strong> " . htmlspecialchars($fileName) . "</li>";
                            }
                            
                            // Add the file as an attachment
                            $mail->addAttachment($absolutePath, $fileName);
                            error_log("Added attachment from path for recipient {$recipientEmail}: " . $absolutePath);
                        } else {
                            error_log("File not accessible for recipient {$recipientEmail}: " . $absolutePath);
                            $emailBody .= "<li><strong>Error:</strong> Could not access file " . htmlspecialchars($fileName) . "</li>";
                        }
                    }
                }
                
                $emailBody .= "</ul>";
            }

            // Close the email body
            $emailBody .= "</body></html>";

            $mail->isHTML(true);
            $mail->Body = $emailBody;
            $mail->AltBody = strip_tags(str_replace("<br>", "\n", $emailBody));
            
            // Set email encoding
            $mail->CharSet = 'UTF-8';
            $mail->Encoding = 'base64';
            
            // Debug logging before sending
            error_log("Preparing to send email to {$recipientEmail} with " . count($mail->getAttachments()) . " attachments");
            
            $mail->send();
            error_log("Email sent successfully to {$recipientEmail}");
        }

        return true;
    } catch (Exception $e) {
        error_log("ManCom notification email sending failed: " . $e->getMessage());
        error_log("Stack trace: " . $e->getTraceAsString());
        return false;
    }
}