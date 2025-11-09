<?php
require_once __DIR__ . '/../phpmailer/src/PHPMailer.php';
require_once __DIR__ . '/../phpmailer/src/SMTP.php';
require_once __DIR__ . '/../phpmailer/src/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Debug helpers: enable by adding frn_debug=1 to the request or setting env FRN_DEBUG=1
function frn_debug_enabled() {
    static $enabled = null;
    if ($enabled === null) {
        $enabled = (isset($_GET['frn_debug']) && $_GET['frn_debug'] == '1')
            || (isset($_POST['frn_debug']) && $_POST['frn_debug'] == '1')
            || getenv('FRN_DEBUG') === '1';
    }
    return $enabled;
}

function frn_debug($message) {
    if (frn_debug_enabled()) {
        echo (string)$message . "\n";
    }
}

// Financial Request Notifications
// Events: SUBMIT, APPROVE, RETURN_REQUESTOR, RETURN_APPROVER, DECLINE, REQUESTOR_EDIT, APPROVER_EDIT

function frn_is_numeric_string($value) {
    if (!isset($value)) return false;
    $s = trim((string)$value);
    return $s !== '' && ctype_digit($s);
}

function frn_get_user_by_id($conn, $id) {
    $info = null;
    if (!$id) return null;
    if ($stmt = mysqli_prepare($conn, "SELECT id, emailadd, CONCAT(user_firstname,' ',user_lastname) AS fullname, user_firstname, user_lastname FROM sys_usertb WHERE id = ? LIMIT 1")) {
        mysqli_stmt_bind_param($stmt, 'i', $id);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        $info = mysqli_fetch_assoc($res) ?: null;
        mysqli_stmt_close($stmt);
    }
    return $info;
}

function frn_get_user_by_email($conn, $email) {
    $info = null;
    if (!$email) return null;
    if ($stmt = mysqli_prepare($conn, "SELECT id, emailadd, CONCAT(user_firstname,' ',user_lastname) AS fullname, user_firstname, user_lastname FROM sys_usertb WHERE LOWER(emailadd) = LOWER(?) LIMIT 1")) {
        mysqli_stmt_bind_param($stmt, 's', $email);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        $info = mysqli_fetch_assoc($res) ?: null;
        mysqli_stmt_close($stmt);
    }
    return $info;
}

function frn_get_user_by_name($conn, $name) {
    $info = null;
    if (!$name) return null;
    $name = trim($name);
    // Try exact full name
    if ($stmt = mysqli_prepare($conn, "SELECT id, emailadd, CONCAT(user_firstname,' ',user_lastname) AS fullname, user_firstname, user_lastname FROM sys_usertb WHERE CONCAT(LOWER(user_firstname),' ',LOWER(user_lastname)) = LOWER(?) LIMIT 1")) {
        mysqli_stmt_bind_param($stmt, 's', $name);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        $info = mysqli_fetch_assoc($res) ?: null;
        mysqli_stmt_close($stmt);
    }
    // Fallback: match by first name only
    if (!$info) {
        if ($stmt = mysqli_prepare($conn, "SELECT id, emailadd, CONCAT(user_firstname,' ',user_lastname) AS fullname, user_firstname, user_lastname FROM sys_usertb WHERE LOWER(user_firstname) = LOWER(?) LIMIT 1")) {
            mysqli_stmt_bind_param($stmt, 's', $name);
            mysqli_stmt_execute($stmt);
            $res = mysqli_stmt_get_result($stmt);
            $info = mysqli_fetch_assoc($res) ?: null;
            mysqli_stmt_close($stmt);
        }
    }
    return $info;
}

function frn_get_user_by_username($conn, $username) {
    $info = null;
    if (!$username) return null;
    if ($stmt = mysqli_prepare($conn, "SELECT id, emailadd, CONCAT(user_firstname,' ',user_lastname) AS fullname, user_firstname, user_lastname FROM sys_usertb WHERE username = ? LIMIT 1")) {
        mysqli_stmt_bind_param($stmt, 's', $username);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        $info = mysqli_fetch_assoc($res) ?: null;
        mysqli_stmt_close($stmt);
    }
    return $info;
}

function frn_resolve_identifier_to_user($conn, $identifier) {
    if (!isset($identifier) || trim((string)$identifier) === '') return null;
    $idStr = trim((string)$identifier);
    frn_debug("FRN: Resolving identifier: '$idStr'");
    
    if (frn_is_numeric_string($idStr)) {
        frn_debug("FRN: Treating as numeric ID");
        return frn_get_user_by_id($conn, (int)$idStr);
    }
    if (strpos($idStr, '@') !== false) {
        frn_debug("FRN: Treating as email");
        return frn_get_user_by_email($conn, $idStr);
    }
    // Try username lookup first (since workflow template uses usernames)
    frn_debug("FRN: Trying username lookup");
    $user = frn_get_user_by_username($conn, $idStr);
    if ($user) {
        frn_debug("FRN: Found user by username: " . ($user['emailadd'] ?? 'no email'));
        return $user;
    }
    // Fallback to name lookup
    frn_debug("FRN: Trying name lookup");
    $user = frn_get_user_by_name($conn, $idStr);
    if ($user) {
        frn_debug("FRN: Found user by name: " . ($user['emailadd'] ?? 'no email'));
    } else {
        frn_debug("FRN: No user found for identifier: '$idStr'");
    }
    return $user;
}

function frn_load_header($conn, $docType, $docNumber) {
    $row = null;
    if ($stmt = mysqli_prepare($conn, "SELECT id, request_type, doc_number, company, cost_center, payee, created_by_user_id, doc_date, currency, amount_figures, amount_in_words, payment_for, special_instructions, expenditure_type, balance, budget, status, from_company, to_company, credit_to_payroll, issue_check, supporting_document_path FROM financial_requests WHERE doc_number = ? AND request_type = ? LIMIT 1")) {
        mysqli_stmt_bind_param($stmt, 'ss', $docNumber, $docType);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        $row = mysqli_fetch_assoc($res) ?: null;
        mysqli_stmt_close($stmt);
    }
    return $row;
}

function frn_decode_paths_field($value) {
    if (!isset($value) || $value === '') return [];
    if (is_array($value)) return $value;
    $s = (string)$value;
    // Try JSON decode
    $decoded = json_decode($s, true);
    if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
        return array_values(array_filter(array_map('strval', $decoded)));
    }
    return [$s];
}

function frn_collect_attachments($conn, $headerRow) {
    $paths = [];
    if (!$headerRow) return [];
    $headerId = isset($headerRow['id']) ? (int)$headerRow['id'] : 0;

    // Header supporting documents (may be single path or JSON array string)
    $paths = array_merge($paths, frn_decode_paths_field($headerRow['supporting_document_path'] ?? null));

    // Item attachments
    if ($headerId > 0) {
        if ($stmt = mysqli_prepare($conn, 'SELECT attachment_path FROM financial_request_items WHERE doc_number = ?')) {
            mysqli_stmt_bind_param($stmt, 'i', $headerId);
            mysqli_stmt_execute($stmt);
            $res = mysqli_stmt_get_result($stmt);
            while ($row = mysqli_fetch_assoc($res)) {
                $paths = array_merge($paths, frn_decode_paths_field($row['attachment_path'] ?? null));
            }
            mysqli_stmt_close($stmt);
        }
    }

    // Normalize, filter duplicates, and ensure the files exist
    $unique = [];
    $out = [];
    foreach ($paths as $p) {
        if (!is_string($p) || trim($p) === '') continue;
        $path = $p;
        // If path is relative, try to resolve from project root
        if (!@file_exists($path)) {
            $alt = realpath(__DIR__ . '/../' . ltrim($path, '/\\'));
            if ($alt && @file_exists($alt)) { $path = $alt; }
        }
        if (@is_file($path) && @is_readable($path)) {
            $key = strtolower(str_replace('\\', '/', $path));
            if (!isset($unique[$key])) {
                $unique[$key] = true;
                $out[] = $path;
            }
        }
    }

    return $out;
}

function frn_get_template_actor_ids($conn, $docType, $company, $department, $sequence) {
    $actors = [];
    if ($stmt = mysqli_prepare($conn, "SELECT actor_id FROM work_flow_template WHERE work_flow_id = ? AND company = ? AND sequence = ? AND (department = ? OR department = '' OR department IS NULL) ORDER BY CASE WHEN department = ? THEN 0 ELSE 1 END, id")) {
        mysqli_stmt_bind_param($stmt, 'ssiss', $docType, $company, $sequence, $department, $department);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        while ($row = mysqli_fetch_assoc($res)) {
            $actors[] = (string)$row['actor_id'];
        }
        mysqli_stmt_close($stmt);
        
        // Debug logging
        frn_debug("FRN: Template lookup for docType=$docType, company=$company, department=$department, sequence=$sequence found " . count($actors) . " actors: " . implode(', ', $actors));
    } else {
        frn_debug("FRN: Failed to prepare template query for docType=$docType, company=$company, department=$department, sequence=$sequence");
    }
    return $actors;
}

function frn_get_process_actor_ids($conn, $docType, $docNumber, $sequence, $statusesFilter = null) {
    $actors = [];
    $sql = "SELECT actor_id FROM work_flow_process WHERE doc_type = ? AND doc_number = ? AND sequence = ?";
    if ($statusesFilter) {
        $sql .= " AND status IN (" . implode(',', array_fill(0, count($statusesFilter), '?')) . ")";
    }
    $types = 'ssi' . ($statusesFilter ? str_repeat('s', count($statusesFilter)) : '');
    if ($stmt = mysqli_prepare($conn, $sql)) {
        if ($statusesFilter) {
            $params = array_merge([$stmt, $types, $docType, $docNumber, $sequence], $statusesFilter);
            // php 7 doesn't support splat into bind_param via array easily; bind manually
            mysqli_stmt_bind_param($stmt, $types, $docType, $docNumber, $sequence, ...$statusesFilter);
        } else {
            mysqli_stmt_bind_param($stmt, $types, $docType, $docNumber, $sequence);
        }
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        while ($row = mysqli_fetch_assoc($res)) {
            $actors[] = (string)$row['actor_id'];
        }
        mysqli_stmt_close($stmt);
    }
    return $actors;
}

function frn_resolve_actors_to_users($conn, $actorIds) {
    $users = [];
    foreach ($actorIds as $ident) {
        $u = frn_resolve_identifier_to_user($conn, $ident);
        if ($u && !empty($u['emailadd'])) {
            $users[$u['emailadd']] = $u; // de-dupe by email
        }
    }
    return array_values($users);
}

function frn_send_email($toUsers, $ccUsers, $subject, $htmlBody, $attachments = []) {
    if ((count($toUsers) + count($ccUsers)) === 0) {
        echo "FRN: No recipients to send email to\n";
        return false;
    }
    
    echo "FRN: Attempting to send email to " . count($toUsers) . " TO recipients and " . count($ccUsers) . " CC recipients\n";
    echo "FRN: Subject: $subject\n";
    
    $mail = new PHPMailer(true);
    try {
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
        $mail->Subject = $subject;
        $mail->Body = $htmlBody;
        $mail->AltBody = strip_tags(str_replace('<br>', "\n", $htmlBody));

        foreach ($toUsers as $u) { 
            $mail->addAddress($u['emailadd'], $u['fullname'] ?? ''); 
            echo "FRN: Adding TO: " . $u['emailadd'] . "\n";
        }
        foreach ($ccUsers as $u) { 
            $mail->addCC($u['emailadd'], $u['fullname'] ?? ''); 
            echo "FRN: Adding CC: " . $u['emailadd'] . "\n";
        }

        $mail->addCustomHeader('X-Priority', '1');
        $mail->addCustomHeader('X-MSMail-Priority', 'High');
        $mail->addCustomHeader('Importance', 'High');
        $mail->addCustomHeader('X-Auto-Response-Suppress', 'OOF, DR, RN, NRN, AutoReply');
        $mail->addCustomHeader('Organization', 'The Laguna Creamery Inc.');
        $mail->ContentType = 'text/html; charset=utf-8';

        // Add attachments (limit for safety)
        $attached = 0;
        foreach ($attachments as $p) {
            if ($attached >= 15) break; // safety cap
            if (@is_file($p) && @is_readable($p)) {
                $name = basename($p);
                try { $mail->addAttachment($p, $name); $attached++; } catch (Exception $e) { /* skip */ }
            }
        }

        echo "FRN: Sending email...\n";
        $result = $mail->send();
        echo "FRN: Email send result: " . ($result ? "SUCCESS" : "FAILED") . "\n";
        return $result;
    } catch (Exception $e) {
        echo "FRN: Email send failed: " . $e->getMessage() . "\n";
        return false;
    }
}

function frn_build_email_body($title, $lines, $ctaUrl = null, $ctaText = 'Open Request', $leadNote = null) {
    $rowHtml = '';
    $i = 0;
    foreach ($lines as $label => $value) {
        $safeLabel = htmlspecialchars($label, ENT_QUOTES, 'UTF-8');
        $safeValue = htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
        $bg = ($i % 2 === 0) ? '#ffffff' : '#f7f9fc';
        $rowHtml .= "<tr style='background:{$bg}'><td style='padding:10px 12px;color:#6b7280;width:35%;border:1px solid #e5e7eb'>{$safeLabel}</td><td style='padding:10px 12px;color:#111827;border:1px solid #e5e7eb'>{$safeValue}</td></tr>";
        $i++;
    }

    $cta = '';
    if ($ctaUrl) {
        $safeUrl = htmlspecialchars($ctaUrl, ENT_QUOTES, 'UTF-8');
        $cta = "<div style='text-align:center;margin:28px 0 12px'>
                    <a href='{$safeUrl}' style='display:inline-block;background-color:#1e40af;color:#ffffff;padding:14px 28px;text-decoration:none;border-radius:6px;font-weight:600;font-size:15px;letter-spacing:0.01em;box-shadow:0 2px 4px rgba(30, 64, 175, 0.2);transition:all 0.2s ease'>
                        " . htmlspecialchars($ctaText, ENT_QUOTES, 'UTF-8') . "
                    </a>
                </div>";
    }

    $leadHtml = '';
    if ($leadNote) {
        $leadHtml = "<div style='background:#f0f9ff;border:1px solid #bae6fd;color:#0c4a6e;padding:16px 18px;border-radius:6px;margin-bottom:20px;border-left:4px solid #0284c7'>
                        <p style='margin:0;font-size:14px;line-height:1.5;font-weight:500'>" . htmlspecialchars($leadNote, ENT_QUOTES, 'UTF-8') . "</p>
                    </div>";
    }

    $html = "<html><body style='margin:0;padding:0;background:#f3f4f6;font-family:Segoe UI, Roboto, Helvetica, Arial, sans-serif;line-height:1.6;color:#111827'>
                <div style='max-width:720px;margin:24px auto;padding:0 12px;'>
                <div style='background:#ffffff;border:1px solid #e5e7eb;border-radius:8px;overflow:hidden;box-shadow:0 2px 4px rgba(0,0,0,0.08)'>
                    <div style='background:#1f2937;color:#ffffff;padding:20px 24px;'>
                    <div style='font-size:14px;letter-spacing:0.05em;text-transform:uppercase;opacity:0.9;font-weight:500'>The Laguna Creamery Inc.</div>
                    <div style='font-size:22px;font-weight:700;margin-top:6px;letter-spacing:-0.01em'>" . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . "</div>
                    </div>

                    <div style='padding:24px'>
                    <div style='background:#fef3c7;border-left:4px solid #f59e0b;padding:12px 16px;margin-bottom:20px;border-radius:0 4px 4px 0'>
                        <p style='margin:0;color:#92400e;font-size:13px;font-weight:500'><strong>System Notification:</strong> This is an automated message from the TLCI Disbursement Management System. Please do not reply to this email.</p>
                    </div>

                    <p style='margin:0 0 18px;color:#374151;font-size:16px;line-height:1.6'>Dear Valued Colleague,</p>
                    <p style='margin:0 0 20px;color:#374151;font-size:15px;line-height:1.6'>
                        We are writing to inform you that a financial request has been processed in our disbursement management system. Please review the comprehensive details provided below and take the appropriate action as required by your role in the approval workflow.
                    </p>

                    {$leadHtml}

                    <div style='background:#f8fafc;border:1px solid #e2e8f0;border-radius:6px;padding:16px;margin:20px 0'>
                        <h3 style='margin:0 0 12px;color:#1e293b;font-size:16px;font-weight:600'>Request Details</h3>
                        <table style='width:100%;border-collapse:collapse;border-spacing:0;background:#ffffff;border-radius:4px;overflow:hidden'>
                            <tbody>{$rowHtml}</tbody>
                        </table>
                    </div>

                    {$cta}

                    <div style='margin:20px 0 0;padding:16px;background:#f1f5f9;border-radius:6px;border-left:4px solid #3b82f6'>
                        <p style='margin:0 0 8px;font-size:13px;color:#475569;font-weight:500'><strong>Important:</strong></p>
                        <p style='margin:0;font-size:12px;color:#64748b;line-height:1.5'>
                            If the action button above does not function properly, please copy and paste the following URL into your web browser to access the request directly.
                        </p>
                    </div>
                    </div>

                    <div style='background:#f8fafc;padding:18px 24px;border-top:1px solid #e2e8f0'>
                    <div style='font-size:12px;color:#64748b;line-height:1.5'>
                        <p style='margin:0 0 8px'><strong>System Information:</strong></p>
                        <p style='margin:0 0 4px'>• This message was automatically generated by the TLCI Disbursement Management System</p>
                        <p style='margin:0 0 4px'>• Email replies to this address are not monitored or processed</p>
                        <p style='margin:0'>• For technical assistance, please contact the IT Service Desk or your designated Finance representative</p>
                    </div>
                    </div>
                </div>

                <div style='text-align:center;margin-top:16px;font-size:11px;color:#94a3b8;line-height:1.4'>
                    <p style='margin:0'>© " . date('Y') . " The Laguna Creamery Inc. All rights reserved.</p>
                    <p style='margin:4px 0 0'>This communication is confidential and intended solely for the use of the individual or entity to whom it is addressed.</p>
                </div>
                </div>
                    </body></html>";

    return $html;
}


// Main entry: send notification for Financial Request workflow events
function sendFinancialRequestNotification($conn, $docType, $docNumber, $event, $sequence = null, $deciderIdentifier = null, $options = []) {
    frn_debug("FRN: Starting notification for docType=$docType, docNumber=$docNumber, event=$event, sequence=$sequence");
    
    $header = frn_load_header($conn, $docType, $docNumber);
    if (!$header) {
        frn_debug("FRN: Failed to load header for docType=$docType, docNumber=$docNumber");
        return false;
    }

    $company = (string)($header['company'] ?? '');
    $department = (string)($header['cost_center'] ?? '');
    frn_debug("FRN: Company=$company, Department=$department");

    // Requestor: prefer created_by_user_id, fallback to payee
    $requestor = null;
    $createdBy = isset($header['created_by_user_id']) ? (int)$header['created_by_user_id'] : 0;
    if ($createdBy > 0) {
        $requestor = frn_get_user_by_id($conn, $createdBy);
    }
    if (!$requestor) {
        $payee = $header['payee'] ?? null; // may be user id or name
        if ($payee !== null && $payee !== '') {
            $requestor = frn_resolve_identifier_to_user($conn, $payee);
        }
    }
    
    if ($requestor) { frn_debug("FRN: Requestor found: " . ($requestor['emailadd'] ?? 'no email')); } else { frn_debug("FRN: No requestor found"); }

    // Decider (current actor performing action), optional
    $decider = $deciderIdentifier ? frn_resolve_identifier_to_user($conn, $deciderIdentifier) : null;
    if ($decider) { frn_debug("FRN: Decider found: " . ($decider['emailadd'] ?? 'no email')); }

    $to = [];
    $cc = [];

    $subjectPrefix = '[FR] ' . $docType . ' ' . $docNumber . ' - ';
    $title = '';

    // URL to view request (re-use existing view page)
    $host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'localhost';
    $requestorUrl = 'http://' . $host . '/it-ticket/dmt-rfp/disbursement_view.php?id=' . urlencode((string)($header['id'] ?? ''));
    $approverUrl = 'http://' . $host . '/it-ticket/dmt-rfp/disbursement_approver_view.php?id=' . urlencode((string)($header['id'] ?? ''));

    $extraInfoLabel = '';
    $approverNames = [];

    if (strcasecmp($event, 'SUBMIT') === 0) {
        $title = 'Submission Notification';
        $subject = $subjectPrefix . 'Submitted';
        // Notify sequence 2 approvers only
        $seq = $sequence ?: 2;
        frn_debug("FRN: SUBMIT event - looking for sequence $seq approvers");
        $actors = frn_get_template_actor_ids($conn, $docType, $company, $department, $seq);
        $approvers = frn_resolve_actors_to_users($conn, $actors);
        frn_debug("FRN: Found " . count($approvers) . " approvers for sequence $seq");
        $to = $approvers;
        // No CC to requestor
        $approverNames = array_map(function($u){ return $u['fullname'] ?? ($u['emailadd'] ?? ''); }, $approvers);
        $extraInfoLabel = 'Approver(s) Notified';
    } elseif (strcasecmp($event, 'APPROVE') === 0) {
        $title = 'Approved';
        $subject = $subjectPrefix . 'Approved';
        // Notify Requestor only
        if ($requestor) $to[] = $requestor;
        $nextSeq = ($sequence ?: 1) + 1;
        frn_debug("FRN: APPROVE event - looking for sequence $nextSeq approvers");
        $actors = frn_get_template_actor_ids($conn, $docType, $company, $department, $nextSeq);
        $approvers = frn_resolve_actors_to_users($conn, $actors);
        frn_debug("FRN: Found " . count($approvers) . " approvers for sequence $nextSeq");
        // No CC to next approvers
        $approverNames = array_map(function($u){ return $u['fullname'] ?? ($u['emailadd'] ?? ''); }, $approvers);
        $extraInfoLabel = 'Next Approver(s)';
    } elseif (strcasecmp($event, 'RETURN_REQUESTOR') === 0) {
        $title = 'Returned to Requestor';
        $subject = $subjectPrefix . 'Returned to Requestor';
        // Notify Requestor and previous approver
        if ($requestor) $to[] = $requestor;
        $prevSeq = max(1, ($sequence ?: 2) - 1);
        frn_debug("FRN: RETURN_REQUESTOR event - looking for sequence $prevSeq approvers");
        $actors = frn_get_template_actor_ids($conn, $docType, $company, $department, $prevSeq);
        $prevApprovers = frn_resolve_actors_to_users($conn, $actors);
        frn_debug("FRN: Found " . count($prevApprovers) . " previous approvers for sequence $prevSeq");
        foreach ($prevApprovers as $u) { $to[] = $u; }
        $approverNames = array_map(function($u){ return $u['fullname'] ?? ($u['emailadd'] ?? ''); }, $prevApprovers);
        $extraInfoLabel = 'Previous Approver(s)';
    } elseif (strcasecmp($event, 'RETURN_APPROVER') === 0) {
        $title = 'Returned to Approver';
        $subject = $subjectPrefix . 'Returned to Approver';
        // Notify Requestor and previous approver
        if ($requestor) $to[] = $requestor;
        $prevSeq = max(1, ($sequence ?: 2) - 1);
        frn_debug("FRN: RETURN_APPROVER event - looking for sequence $prevSeq approvers");
        $actors = frn_get_template_actor_ids($conn, $docType, $company, $department, $prevSeq);
        $prevApprovers = frn_resolve_actors_to_users($conn, $actors);
        frn_debug("FRN: Found " . count($prevApprovers) . " previous approvers for sequence $prevSeq");
        foreach ($prevApprovers as $u) { $to[] = $u; }
        $approverNames = array_map(function($u){ return $u['fullname'] ?? ($u['emailadd'] ?? ''); }, $prevApprovers);
        $extraInfoLabel = 'Previous Approver(s)';
    } elseif (strcasecmp($event, 'DECLINE') === 0) {
        $title = 'Declined';
        $subject = $subjectPrefix . 'Declined';
        // Notify Requestor and previous approver
        if ($requestor) $to[] = $requestor;
        $prevSeq = max(1, ($sequence ?: 2) - 1);
        frn_debug("FRN: DECLINE event - looking for sequence $prevSeq approvers");
        $actors = frn_get_template_actor_ids($conn, $docType, $company, $department, $prevSeq);
        $prevApprovers = frn_resolve_actors_to_users($conn, $actors);
        frn_debug("FRN: Found " . count($prevApprovers) . " previous approvers for sequence $prevSeq");
        foreach ($prevApprovers as $u) { $to[] = $u; }
        $approverNames = array_map(function($u){ return $u['fullname'] ?? ($u['emailadd'] ?? ''); }, $prevApprovers);
        $extraInfoLabel = 'Previous Approver(s)';
    } elseif (strcasecmp($event, 'REQUESTOR_EDIT') === 0) {
        $title = 'Request Edited by Requestor';
        $subject = $subjectPrefix . 'Edited by Requestor';
        // Notify sequence 3, 4, 5 approvers if they exist
        $approvers = [];
        for ($seq = 3; $seq <= 5; $seq++) {
            frn_debug("FRN: REQUESTOR_EDIT event - looking for sequence $seq approvers");
            $actors = frn_get_template_actor_ids($conn, $docType, $company, $department, $seq);
            $seqApprovers = frn_resolve_actors_to_users($conn, $actors);
            frn_debug("FRN: Found " . count($seqApprovers) . " approvers for sequence $seq");
            foreach ($seqApprovers as $u) { $approvers[] = $u; }
        }
        $to = $approvers;
        $approverNames = array_map(function($u){ return $u['fullname'] ?? ($u['emailadd'] ?? ''); }, $approvers);
        $extraInfoLabel = 'Higher Level Approver(s) Notified';
    } elseif (strcasecmp($event, 'APPROVER_EDIT') === 0) {
        $title = 'Request Edited by Approver';
        $subject = $subjectPrefix . 'Edited by Approver';
        // Notify requestor and other approvers at same level
        if ($requestor) $to[] = $requestor;
        $currentSeq = $sequence ?: 3;
        frn_debug("FRN: APPROVER_EDIT event - looking for sequence $currentSeq approvers");
        $actors = frn_get_template_actor_ids($conn, $docType, $company, $department, $currentSeq);
        $sameLevelApprovers = frn_resolve_actors_to_users($conn, $actors);
        frn_debug("FRN: Found " . count($sameLevelApprovers) . " same level approvers for sequence $currentSeq");
        foreach ($sameLevelApprovers as $u) { $to[] = $u; }
        $approverNames = array_map(function($u){ return $u['fullname'] ?? ($u['emailadd'] ?? ''); }, $sameLevelApprovers);
        $extraInfoLabel = 'Same Level Approver(s)';
    } else {
        frn_debug("FRN: Unknown event: $event");
        return false;
    }

    frn_debug("FRN: Final recipients - TO: " . count($to) . ", CC: " . count($cc));
    foreach ($to as $u) { frn_debug("FRN: TO: " . ($u['emailadd'] ?? 'no email') . " (" . ($u['fullname'] ?? 'no name') . ")"); }
    foreach ($cc as $u) { frn_debug("FRN: CC: " . ($u['emailadd'] ?? 'no email') . " (" . ($u['fullname'] ?? 'no name') . ")"); }

    // Build details
    $docDate = isset($header['doc_date']) ? (string)$header['doc_date'] : '';
    $currency = isset($header['currency']) ? (string)$header['currency'] : '';
    $amountFiguresRaw = isset($header['amount_figures']) ? (float)$header['amount_figures'] : null;
    $amountFiguresStr = ($amountFiguresRaw !== null) ? number_format($amountFiguresRaw, 2, '.', ',') : '';
    $amountInWords = isset($header['amount_in_words']) ? (string)$header['amount_in_words'] : '';
    $paymentFor = isset($header['payment_for']) ? (string)$header['payment_for'] : '';
    $specialInstructions = isset($header['special_instructions']) ? (string)$header['special_instructions'] : '';
    $expenditureType = isset($header['expenditure_type']) ? (string)$header['expenditure_type'] : '';
    $budgetRaw = isset($header['budget']) ? (float)$header['budget'] : null;
    $budgetStr = ($budgetRaw !== null) ? number_format($budgetRaw, 2, '.', ',') : '';
    $balanceRaw = isset($header['balance']) ? (float)$header['balance'] : null;
    $balanceStr = ($balanceRaw !== null) ? number_format($balanceRaw, 2, '.', ',') : '';
    $fromCompany = isset($header['from_company']) ? (string)$header['from_company'] : '';
    $toCompany = isset($header['to_company']) ? (string)$header['to_company'] : '';
    $creditToPayroll = isset($header['credit_to_payroll']) ? ((int)$header['credit_to_payroll'] === 1 ? 'Yes' : 'No') : '';
    $issueCheck = isset($header['issue_check']) ? ((int)$header['issue_check'] === 1 ? 'Yes' : 'No') : '';
    $requestorName = $requestor['fullname'] ?? (string)$payee;
    $deciderName = $decider['fullname'] ?? '';
    $approverList = isset($approverNames) && is_array($approverNames) && count($approverNames) > 0 ? implode(', ', array_filter($approverNames)) : '';

    $lines = [
        'Document' => $docType . ' / ' . $docNumber,
        'Event' => strtoupper($event),
        'Requestor' => $requestorName,
        'Company' => $company,
        'Cost Center' => $department,
        'Doc Date' => $docDate,
        'Currency' => $currency,
        'Amount (Figures)' => ($currency ? ($currency . ' ') : '') . $amountFiguresStr,
        'Amount (Words)' => $amountInWords,
        'Payment For' => $paymentFor,
        'Special Instructions' => $specialInstructions,
        'Expenditure Type' => $expenditureType,
        'Budget' => ($budgetStr !== '' ? (($currency ? $currency . ' ' : '') . $budgetStr) : ''),
        'Balance' => ($balanceStr !== '' ? (($currency ? $currency . ' ' : '') . $balanceStr) : ''),
        'Credit to Payroll' => $creditToPayroll,
        'Issue Check' => $issueCheck,
    ];

    // Only add From Company and To Company for ERL document type
    if (strtoupper($docType) === 'ERL') {
        $lines['From Company'] = $fromCompany;
        $lines['To Company'] = $toCompany;
    }

    if (!empty($deciderName)) { $lines['Actioned By'] = $deciderName; }
    if (!empty($approverList) && !empty($extraInfoLabel)) { $lines[$extraInfoLabel] = $approverList; }
    if (!empty($options['remarks'] ?? '')) { $lines['Approver Remarks'] = (string)$options['remarks']; }

    // Prepare audiences per event with tailored lead notes
    $sent = [];
    $attachments = frn_collect_attachments($conn, $header);
    $attachmentCount = count($attachments);
    
    // Add attachment information to email details
    if ($attachmentCount > 0) {
        $fileNames = array_map('basename', $attachments);
        $lines['Supporting Documents'] = $attachmentCount . ' file(s): ' . implode(', ', $fileNames);
        
        // For edit events, highlight new or updated attachments
        if (strcasecmp($event, 'REQUESTOR_EDIT') === 0 || strcasecmp($event, 'APPROVER_EDIT') === 0) {
            $lines['Document Updates'] = 'Please review all attached supporting documents as some may have been updated.';
        }
    } else {
        $lines['Supporting Documents'] = 'No supporting documents attached';
    }
    $sendAudience = function($primary, $copies, $lead, $ctaUrl) use (&$sent, $subject, $lines, $attachments) {
        $toUsers = [];
        $ccUsers = [];
        foreach ($primary as $u) {
            if (!$u || empty($u['emailadd'])) continue;
            $key = strtolower($u['emailadd']);
            if (isset($sent[$key])) continue;
            $toUsers[] = $u;
            $sent[$key] = true;
        }
        foreach ($copies as $u) {
            if (!$u || empty($u['emailadd'])) continue;
            $key = strtolower($u['emailadd']);
            if (isset($sent[$key])) continue;
            $ccUsers[] = $u;
            $sent[$key] = true;
        }
        if (!empty($toUsers) || !empty($ccUsers)) {
            $body = frn_build_email_body($subject, $lines, $ctaUrl, 'Access Financial Request', $lead);
            frn_send_email($toUsers, $ccUsers, $subject, $body, $attachments);
        }
    };

    // Build groups for each event
    if (strcasecmp($event, 'SUBMIT') === 0) {
        // Send to approvers
        $lead = 'This financial request requires your review and approval. Please examine the details carefully and take appropriate action within the designated timeframe.';
        $sendAudience($to, [], $lead, $approverUrl);
        
        // Send confirmation to requestor
        if ($requestor) {
            $leadReq = 'Your financial request has been successfully submitted and is now pending approval. You will be notified of any updates regarding this request.';
            $sendAudience([$requestor], [], $leadReq, $requestorUrl);
        }
    } elseif (strcasecmp($event, 'APPROVE') === 0) {
        // Notify Requestor only
        $leadReq = 'We are pleased to inform you that your financial request has been approved by ' . ($deciderName ?: 'the designated approver') . '. The request will now proceed to the next stage of the approval workflow.';
        $sendAudience($requestor ? [$requestor] : [], [], $leadReq, $requestorUrl);
    } elseif (strcasecmp($event, 'RETURN_REQUESTOR') === 0) {
        // Send separate emails to requestor and previous approver
        if ($requestor) {
            $leadReq = 'Your financial request has been returned by ' . ($deciderName ?: 'the designated approver') . ' for further review and revision. Please address any concerns raised and resubmit the request when ready.';
            $sendAudience([$requestor], [], $leadReq, $requestorUrl);
        }
        // Send to previous approvers
        $prevSeq = max(1, ($sequence ?: 2) - 1);
        $actors = frn_get_template_actor_ids($conn, $docType, $company, $department, $prevSeq);
        $prevApprovers = frn_resolve_actors_to_users($conn, $actors);
        if (!empty($prevApprovers)) {
            $leadApp = 'For your information: The financial request you previously approved has been returned to the requestor for revision. You will be notified when it is resubmitted.';
            $sendAudience($prevApprovers, [], $leadApp, $approverUrl);
        }
    } elseif (strcasecmp($event, 'RETURN_APPROVER') === 0) {
        // Send separate emails to requestor and previous approver
        if ($requestor) {
            $leadReq = 'For your information: Your financial request has been returned to the previous approver for additional review. You will be notified of any further developments.';
            $sendAudience([$requestor], [], $leadReq, $requestorUrl);
        }
        // Send to previous approvers
        $prevSeq = max(1, ($sequence ?: 2) - 1);
        $actors = frn_get_template_actor_ids($conn, $docType, $company, $department, $prevSeq);
        $prevApprovers = frn_resolve_actors_to_users($conn, $actors);
        if (!empty($prevApprovers)) {
            $leadApp = 'This financial request has been returned to you for additional review. Please re-examine the details and take the necessary action.';
            $sendAudience($prevApprovers, [], $leadApp, $approverUrl);
        }
    } elseif (strcasecmp($event, 'DECLINE') === 0) {
        // Send separate emails to requestor and previous approver
        if ($requestor) {
            $leadReq = 'We regret to inform you that your financial request has been declined by ' . ($deciderName ?: 'the designated approver') . '. Please review the decision and contact the approver if you require clarification.';
            $sendAudience([$requestor], [], $leadReq, $requestorUrl);
        }
        // Send to previous approvers
        $prevSeq = max(1, ($sequence ?: 2) - 1);
        $actors = frn_get_template_actor_ids($conn, $docType, $company, $department, $prevSeq);
        $prevApprovers = frn_resolve_actors_to_users($conn, $actors);
        if (!empty($prevApprovers)) {
            $leadApp = 'For your information: The financial request you previously approved has been declined and will not proceed further in the approval workflow.';
            $sendAudience($prevApprovers, [], $leadApp, $approverUrl);
        }
    } elseif (strcasecmp($event, 'REQUESTOR_EDIT') === 0) {
        // Send to sequence 3, 4, 5 approvers
        $approvers = [];
        for ($seq = 3; $seq <= 5; $seq++) {
            $actors = frn_get_template_actor_ids($conn, $docType, $company, $department, $seq);
            $seqApprovers = frn_resolve_actors_to_users($conn, $actors);
            foreach ($seqApprovers as $u) { $approvers[] = $u; }
        }
        if (!empty($approvers)) {
            $leadEdit = 'For your information: A financial request has been edited by the requestor. Please review the updated details as this may affect your approval decision when the request reaches your level.';
            $sendAudience($approvers, [], $leadEdit, $approverUrl);
        }
    } elseif (strcasecmp($event, 'APPROVER_EDIT') === 0) {
        // Send to requestor and same level approvers
        $sameLevelApprovers = [];
        $currentSeq = $sequence ?: 3;
        $actors = frn_get_template_actor_ids($conn, $docType, $company, $department, $currentSeq);
        $sameLevelApprovers = frn_resolve_actors_to_users($conn, $actors);
        
        // Send to requestor
        if ($requestor) {
            $leadReq = 'For your information: Your financial request has been edited by ' . ($deciderName ?: 'a designated approver') . '. The changes have been noted in the system.';
            $sendAudience([$requestor], [], $leadReq, $requestorUrl);
        }
        
        // Send to same level approvers
        if (!empty($sameLevelApprovers)) {
            $leadApp = 'For your information: A financial request has been edited by a colleague at your approval level. Please review the changes as this may affect your approval decision.';
            $sendAudience($sameLevelApprovers, [], $leadApp, $approverUrl);
        }
    } else {
        // Fallback single audience
        $sendAudience($to, [], null, $approverUrl);
    }

    return true;
}

// Fire-and-forget trigger to process notifications asynchronously
function frn_trigger_async_notification($docType, $docNumber, $event, $sequence = null, $deciderIdentifier = null, $remarks = null) {
    frn_debug("FRN: Triggering async notification for docType=$docType, docNumber=$docNumber, event=$event, sequence=$sequence");
    
    $payload = [
        'doc_type' => (string)$docType,
        'doc_number' => (string)$docNumber,
        'event' => (string)$event,
        'sequence' => is_null($sequence) ? null : (int)$sequence,
        'decider' => isset($deciderIdentifier) ? (string)$deciderIdentifier : null,
        'remarks' => isset($remarks) ? (string)$remarks : null
    ];
    $json = json_encode($payload);
    frn_debug("FRN: Payload: $json");

    $scheme = isset($_SERVER['REQUEST_SCHEME']) ? $_SERVER['REQUEST_SCHEME'] : ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'http');
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $base = rtrim(dirname($_SERVER['PHP_SELF'] ?? '/it-ticket/dmt-rfp/workflow_action.php'), '/\\');
    $url = $scheme . '://' . $host . $base . '/process_fr_email_queue.php';
    frn_debug("FRN: Target URL: $url");

    if (function_exists('curl_init')) {
        frn_debug("FRN: Using cURL to send async notification");
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($ch, CURLOPT_POSTFIELDS, $json);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Content-Length: ' . strlen($json)
        ]);
        $timeoutSeconds = getenv('FRN_CURL_TIMEOUT') !== false ? (int)getenv('FRN_CURL_TIMEOUT') : 1;
        curl_setopt($ch, CURLOPT_TIMEOUT, max(1, $timeoutSeconds));
        $result = @curl_exec($ch);
        $error = curl_error($ch);
        @curl_close($ch);
        if ($error) {
            frn_debug("FRN: cURL error: $error");
        } else {
            frn_debug("FRN: cURL result: $result");
        }
        return true;
    }

    // Fallback: non-blocking stream
    frn_debug("FRN: Using fallback stream method");
    $parts = parse_url($url);
    if (!$parts || empty($parts['host'])) {
        frn_debug("FRN: Failed to parse URL: $url");
        return false;
    }
    $port = ($parts['scheme'] === 'https') ? 443 : 80;
    $path = ($parts['path'] ?? '/') . (!empty($parts['query']) ? ('?' . $parts['query']) : '');
    $fp = @fsockopen(($parts['scheme'] === 'https' ? 'ssl://' : '') . $parts['host'], $port, $errno, $errstr, 1);
    if ($fp) {
        $out = "POST " . $path . " HTTP/1.1\r\n" .
               "Host: " . $parts['host'] . "\r\n" .
               "Content-Type: application/json\r\n" .
               "Content-Length: " . strlen($json) . "\r\n" .
               "Connection: Close\r\n\r\n" .
               $json;
        @fwrite($fp, $out);
        @fclose($fp);
        frn_debug("FRN: Stream method completed");
        return true;
    } else {
        frn_debug("FRN: Stream method failed: $errstr ($errno)");
    }

    return false;
}
