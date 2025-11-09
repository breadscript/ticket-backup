<?php
// Create a new file: email_utils.php

/**
 * Gets threading headers for a task
 */
function getThreadingHeaders($taskId, $conn) {
    // Get domain from server or use default
    $domain = $_SERVER['HTTP_HOST'] ?? 'tlcreameryinc.com';
    
    // Create a consistent base for Message-ID and Thread-ID using the SRN ID
    $srnQuery = "SELECT srn_id FROM pm_projecttasktb WHERE id = ?";
    $stmt = $conn->prepare($srnQuery);
    $stmt->bind_param("i", $taskId);
    $stmt->execute();
    $result = $stmt->get_result();
    $srnId = $result->fetch_assoc()['srn_id'] ?? $taskId;
    
    // Generate consistent message ID based on SRN
    $messageId = "<{$srnId}@{$domain}>";
    
    // Use the same thread ID for all messages in the thread
    $threadId = "<thread-{$srnId}@{$domain}>";
    
    // Generate a consistent thread index base
    $baseTimestamp = strtotime('2024-01-01'); // Use a fixed base date
    $threadIndexBase = pack('H*', '01');  // Start marker
    $threadIndexBase .= pack('V', crc32($srnId));  // Hash of SRN
    $threadIndexBase .= pack('V', $baseTimestamp); // Base timestamp
    
    // Add current timestamp for this specific message
    $currentTime = pack('V', time() - $baseTimestamp);
    $threadIndex = base64_encode($threadIndexBase . $currentTime);
    
    return [
        'messageId' => $messageId,
        'threadId' => $threadId,
        'threadIndex' => $threadIndex,
        'srnId' => $srnId
    ];
}

/**
 * Applies threading headers to PHPMailer instance
 */
function applyThreadingHeaders($mail, $headers, $subject, $activity) {
    // Set consistent subject format
    $mail->Subject = "The Laguna Creamery Inc. (No-Reply) | {$headers['srnId']} | {$subject}";
    
    // Set Message-ID
    $mail->MessageID = $headers['messageId'];
    
    // Set References and In-Reply-To for threading
    $mail->addCustomHeader('References', $headers['threadId']);
    $mail->addCustomHeader('In-Reply-To', $headers['threadId']);
    
    // Add Outlook-specific threading headers
    $mail->addCustomHeader('Thread-Topic', $subject);
    $mail->addCustomHeader('Thread-Index', $headers['threadIndex']);
    
    // Add conversation index for better Outlook threading
    $mail->addCustomHeader('Conversation-ID', $headers['threadId']);
    $mail->addCustomHeader('X-MS-Conversation-ID', $headers['threadId']);
    $mail->addCustomHeader('X-MS-Exchange-CrossTenant-Thread-Topic', $subject);
} 