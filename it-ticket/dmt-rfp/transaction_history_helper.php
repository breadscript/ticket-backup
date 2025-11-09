<?php
/**
 * Transaction History Helper
 * Reusable component for displaying transaction history in disbursement views
 */

function renderTransactionHistory($transactionHistory, $sequenceNames = null, $viewType = 'table') {
    // Default sequence names
    if ($sequenceNames === null) {
        $sequenceNames = [
            1 => 'Request Submission',
            2 => 'Cost Center Approval',
            3 => 'Accounting Review',
            4 => 'Accounting Approval',
            5 => 'Controller Review',
            6 => 'Cashier Processing'
        ];
    }

    // Filter out parallel approvers who are still waiting when another has approved
    // First pass: identify sequences where someone has approved and track returns
    $sequencesWithApproval = [];
    $sequencesReturnedTo = [];
    $lastActionTime = [];
    
    foreach ($transactionHistory as $row) {
        $seqTmp = (int)($row['sequence'] ?? 0);
        $stText = strtoupper(trim((string)($row['new_status'] ?? '')));
        $createdAt = (string)($row['created_at'] ?? '');
        
        if (strpos($stText, 'APPROVED') !== false || $stText === 'DONE') {
            $sequencesWithApproval[$seqTmp] = $createdAt;
            $lastActionTime[$seqTmp] = $createdAt;
        } elseif (strpos($stText, 'RETURN TO APPROVER') !== false) {
            // When returning to previous approver, mark that the previous sequence is being returned to
            if ($seqTmp > 1) {
                $sequencesReturnedTo[$seqTmp - 1] = $createdAt;
            }
            $lastActionTime[$seqTmp] = $createdAt;
        } elseif (strpos($stText, 'RETURN TO REQUESTOR') !== false) {
            $sequencesReturnedTo[1] = $createdAt;
            $lastActionTime[$seqTmp] = $createdAt;
        } elseif (strpos($stText, 'DECLINED') !== false || strpos($stText, 'REJECTED') !== false) {
            $lastActionTime[$seqTmp] = $createdAt;
        }
    }
    
    // Second pass: filter out Waiting entries for sequences that already have approvals
    // UNLESS that sequence has been returned to after the approval
    $displayHistory = [];
    foreach ($transactionHistory as $row) {
        $seqTmp = (int)($row['sequence'] ?? 0);
        $stText = strtoupper(trim((string)($row['new_status'] ?? '')));
        $createdAt = (string)($row['created_at'] ?? '');
        
        // Skip Waiting for Approval entries if someone else has already approved in this sequence
        if ($stText === 'WAITING FOR APPROVAL' && isset($sequencesWithApproval[$seqTmp])) {
            // Allow if this sequence was returned to AFTER the approval
            $allowIt = false;
            if (isset($sequencesReturnedTo[$seqTmp])) {
                // Check if the return happened after the approval
                if (strtotime($sequencesReturnedTo[$seqTmp]) > strtotime($sequencesWithApproval[$seqTmp])) {
                    $allowIt = true;
                }
            }
            
            if (!$allowIt) {
                continue;
            }
        }
        
        $displayHistory[] = $row;
    }
    
    // Add Accounting Review entries after Return to Approver actions
    $finalDisplayHistory = [];
    $seenReturnToRequestor = false;
    foreach ($displayHistory as $row) {
        $finalDisplayHistory[] = $row;
        
        // If this is a "Return to Approver" action, add the previous sequence back
        $stText = strtoupper(trim((string)($row['new_status'] ?? '')));
        if (strpos($stText, 'RETURN TO APPROVER') !== false) {
            $seqTmp = (int)($row['sequence'] ?? 0);
            if ($seqTmp > 1) {
                // Find the previous sequence's waiting entry to add back
                $prevSeq = $seqTmp - 1;

                // Only add the waiting entry if there is NOT an approval for prevSeq AFTER this return
                $returnAtTs = isset($row['created_at']) ? strtotime((string)$row['created_at']) : 0;
                $hasApprovalAfterReturn = false;
                foreach ($transactionHistory as $chkRow) {
                    $chkSeq = (int)($chkRow['sequence'] ?? 0);
                    if ($chkSeq !== $prevSeq) { continue; }
                    $chkStatus = strtoupper(trim((string)($chkRow['new_status'] ?? '')));
                    if (strpos($chkStatus, 'APPROVED') !== false || $chkStatus === 'DONE') {
                        $chkTs = isset($chkRow['created_at']) ? strtotime((string)$chkRow['created_at']) : 0;
                        if ($chkTs > $returnAtTs) { $hasApprovalAfterReturn = true; break; }
                    }
                }
                if (!$hasApprovalAfterReturn) {
                    foreach ($transactionHistory as $prevRow) {
                        $prevSeqTmp = (int)($prevRow['sequence'] ?? 0);
                        $prevStText = strtoupper(trim((string)($prevRow['new_status'] ?? '')));
                        if ($prevSeqTmp === $prevSeq && $prevStText === 'WAITING FOR APPROVAL') {
                            // Add this waiting entry after the return action
                            $finalDisplayHistory[] = $prevRow;
                            break;
                        }
                    }
                }
            }
        }

        // If this is a "Return to Requestor", add Request Submission (seq 1) back as waiting for submission
        if (strpos($stText, 'RETURN TO REQUESTOR') !== false) {
            $seenReturnToRequestor = true;
            $synthetic = $row;
            $synthetic['sequence'] = 1;
            $synthetic['actor_id'] = 'Requestor';
            $synthetic['actor_display_name'] = 'Requestor';
            $synthetic['new_status'] = 'WAITING FOR SUBMISSION';
            $synthetic['remarks'] = ($row['remarks'] ?? '') ?: 'Waiting for submission';
            $finalDisplayHistory[] = $synthetic;
        }
    }
    
    $displayHistory = $finalDisplayHistory;

    // Collapse multiple "Waiting for Approval" rows per sequence to a single latest entry
    if (!empty($displayHistory)) {
        $lastWaitingIdxBySeq = [];
        for ($i = 0; $i < count($displayHistory); $i++) {
            $r = $displayHistory[$i];
            $seqTmp = (int)($r['sequence'] ?? 0);
            $stText = strtoupper(trim((string)($r['new_status'] ?? '')));
            if ($stText === 'WAITING FOR APPROVAL') {
                $lastWaitingIdxBySeq[$seqTmp] = $i; // keep last occurrence index per sequence
            }
        }
        if (!empty($lastWaitingIdxBySeq)) {
            $collapsed = [];
            for ($i = 0; $i < count($displayHistory); $i++) {
                $r = $displayHistory[$i];
                $seqTmp = (int)($r['sequence'] ?? 0);
                $stText = strtoupper(trim((string)($r['new_status'] ?? '')));
                if ($stText === 'WAITING FOR APPROVAL' && isset($lastWaitingIdxBySeq[$seqTmp]) && $i !== $lastWaitingIdxBySeq[$seqTmp]) {
                    // skip earlier waiting duplicates for this sequence
                    continue;
                }
                $collapsed[] = $r;
            }
            $displayHistory = $collapsed;
        }
    }

    // Render based on view type
    if ($viewType === 'timeline') {
        return renderTimelineView($displayHistory, $sequenceNames);
    } else {
        return renderTableView($displayHistory, $sequenceNames);
    }
}

function renderTableView($displayHistory, $sequenceNames) {
    $currentSequence = null;
    $output = '';
    
    foreach ($displayHistory as $txn) { 
        $sequence = (int)($txn['sequence'] ?? 0);
        $status = $txn['new_status'] ?? '';
        // Use the actor_display_name from the workflow log for all sequences
        $actor = $txn['actor_display_name'] ?? $txn['actor_id'] ?? 'Unknown';
        $remarks = $txn['remarks'] ?? '';
        $createdAt = $txn['created_at'] ?? '';
        
        // Show step name only when sequence changes
        $showStepName = ($currentSequence !== $sequence);
        $currentSequence = $sequence;
        
        // Determine status class, but keep the real status text from the log
        $statusClass = 'label-default';
        $statusText = trim((string)$status);
        $statusUpperTx = strtoupper($statusText);
        if (strpos($statusUpperTx, 'APPROVED') !== false || $statusUpperTx === 'DONE') {
            $statusClass = 'label-success';
        } elseif (strpos($statusUpperTx, 'SUBMITTED') !== false || strpos($statusUpperTx, 'SUBMIT') !== false) {
            $statusClass = 'label-primary';
        } elseif (strpos($statusUpperTx, 'DECLINED') !== false || strpos($statusUpperTx, 'REJECTED') !== false) {
            $statusClass = 'label-danger';
        } elseif (strpos($statusUpperTx, 'RETURN') !== false) {
            $statusClass = 'label-warning';
        } elseif (strpos($statusUpperTx, 'WAITING FOR APPROVAL') !== false) {
            $statusClass = 'label-info';
        } elseif (strpos($statusUpperTx, 'WAITING FOR SUBMISSION') !== false) {
            $statusClass = 'label-primary';
        } elseif (strpos($statusUpperTx, 'SKIPPED') !== false) {
            $statusClass = 'label-default';
        }
        
        // Hide approver name if status is "Waiting for Approval"
        if (strpos($statusUpperTx, 'WAITING FOR APPROVAL') !== false) {
            $actor = ''; // Hide the approver name
        }
        
        $output .= '<tr>';
        $output .= '<td>';
        if ($showStepName) {
            $output .= '<strong>' . h($sequenceNames[$sequence] ?? "Step $sequence") . '</strong>';
        }
        $output .= '</td>';
        $output .= '<td>';
        $output .= '<strong>' . h($actor) . '</strong>';
        $output .= '</td>';
        $output .= '<td>';
        $output .= '<span class="label ' . $statusClass . '">' . h($statusText) . '</span>';
        $output .= '</td>';
        $output .= '<td>';
        if ($createdAt) {
            $date = new DateTime($createdAt);
            $output .= $date->format('m/d/Y h:i A');
        } else {
            $output .= 'N/A';
        }
        $output .= '</td>';
        $output .= '<td>';
        $remarksDisplay = trim((string)$remarks);
        if ($remarksDisplay === '') {
            // Provide a friendly default description when remarks are empty
            $statusUpperTx = strtoupper(trim((string)$status));
            $eventName = isset($txn['event']) ? strtoupper(trim((string)$txn['event'])) : '';
            if (strpos($statusUpperTx, 'WAITING FOR APPROVAL') !== false) {
                $remarksDisplay = 'Waiting for approval';
            } elseif (strpos($statusUpperTx, 'SUBMITTED') !== false || strpos($statusUpperTx, 'SUBMIT') !== false) {
                $remarksDisplay = 'Submitted';
            } elseif (strpos($statusUpperTx, 'APPROVED') !== false || $statusUpperTx === 'DONE') {
                $remarksDisplay = 'Approved';
            } elseif (strpos($statusUpperTx, 'RETURN TO APPROVER') !== false) {
                $remarksDisplay = 'Returned to previous approver';
            } elseif (strpos($statusUpperTx, 'RETURN TO REQUESTOR') !== false) {
                $remarksDisplay = 'Returned to requestor';
            } elseif (strpos($statusUpperTx, 'DECLINED') !== false || strpos($statusUpperTx, 'REJECTED') !== false) {
                $remarksDisplay = 'Declined';
            } elseif (strpos($statusUpperTx, 'CANCELLED') !== false) {
                $remarksDisplay = 'Cancelled';
            } elseif (strpos($statusUpperTx, 'SKIPPED') !== false) {
                $remarksDisplay = 'Skipped due to parallel approval';
            } elseif (strpos($eventName, 'SEQUENCE_CREATION') !== false || strpos($eventName, 'SEQUENCE') !== false) {
                $remarksDisplay = 'Sequence created automatically';
            } elseif ($eventName === 'INSERT') {
                $remarksDisplay = 'Process created';
            } elseif ($eventName === 'UPDATE') {
                $remarksDisplay = 'Process updated';
            } else {
                $remarksDisplay = 'No remarks';
            }
        }
        $output .= h($remarksDisplay);
        $output .= '</td>';
        $output .= '</tr>';
    }
    
    return $output;
}

function renderTimelineView($displayHistory, $sequenceNames) {
    $currentSequence = null;
    $output = '';
    
    foreach ($displayHistory as $txn) { 
        $sequence = (int)($txn['sequence'] ?? 0);
        $status = $txn['new_status'] ?? '';
        // Use the actor_display_name from the workflow log for all sequences
        $actor = $txn['actor_display_name'] ?? $txn['actor_id'] ?? 'Unknown';
        $remarks = $txn['remarks'] ?? '';
        $createdAt = $txn['created_at'] ?? '';
        
        // Show step name only when sequence changes
        $showStepName = ($currentSequence !== $sequence);
        $currentSequence = $sequence;
        
        // Determine status class and color
        $statusClass = 'default';
        $statusIcon = 'fa-clock-o';
        $timelineColor = '#ccc';
        $statusText = trim((string)$status);
        $statusUpperTx = strtoupper($statusText);
        
        if (strpos($statusUpperTx, 'APPROVED') !== false || $statusUpperTx === 'DONE') {
            $statusClass = 'success';
            $statusIcon = 'fa-check-circle';
            $timelineColor = '#26B99A';
        } elseif (strpos($statusUpperTx, 'SUBMITTED') !== false || strpos($statusUpperTx, 'SUBMIT') !== false) {
            $statusClass = 'primary';
            $statusIcon = 'fa-paper-plane';
            $timelineColor = '#337AB7';
        } elseif (strpos($statusUpperTx, 'DECLINED') !== false || strpos($statusUpperTx, 'REJECTED') !== false) {
            $statusClass = 'danger';
            $statusIcon = 'fa-times-circle';
            $timelineColor = '#D9534F';
        } elseif (strpos($statusUpperTx, 'RETURN') !== false) {
            $statusClass = 'warning';
            $statusIcon = 'fa-undo';
            $timelineColor = '#F0AD4E';
        } elseif (strpos($statusUpperTx, 'WAITING FOR APPROVAL') !== false) {
            $statusClass = 'info';
            $statusIcon = 'fa-hourglass-half';
            $timelineColor = '#5BC0DE';
        } elseif (strpos($statusUpperTx, 'WAITING FOR SUBMISSION') !== false) {
            $statusClass = 'primary';
            $statusIcon = 'fa-upload';
            $timelineColor = '#337AB7';
        } elseif (strpos($statusUpperTx, 'CANCELLED') !== false) {
            $statusClass = 'default';
            $statusIcon = 'fa-ban';
            $timelineColor = '#999';
        }
        
        // Hide approver name if status is "Waiting for Approval"
        if (strpos($statusUpperTx, 'WAITING FOR APPROVAL') !== false) {
            $actor = ''; // Hide the approver name
        }
        
        // Format date and time
        $dateDisplay = 'N/A';
        $timeDisplay = '';
        if ($createdAt) {
            $date = new DateTime($createdAt);
            $dateDisplay = $date->format('M d, Y');
            $timeDisplay = $date->format('h:i A');
        }
        
        // Prepare remarks
        $remarksDisplay = trim((string)$remarks);
        if ($remarksDisplay === '') {
            $eventName = isset($txn['event']) ? strtoupper(trim((string)$txn['event'])) : '';
            if (strpos($statusUpperTx, 'WAITING FOR APPROVAL') !== false) {
                $remarksDisplay = 'Waiting for approval';
            } elseif (strpos($statusUpperTx, 'SUBMITTED') !== false || strpos($statusUpperTx, 'SUBMIT') !== false) {
                $remarksDisplay = 'Submitted';
            } elseif (strpos($statusUpperTx, 'APPROVED') !== false || $statusUpperTx === 'DONE') {
                $remarksDisplay = 'Approved';
            } elseif (strpos($statusUpperTx, 'RETURN TO APPROVER') !== false) {
                $remarksDisplay = 'Returned to previous approver';
            } elseif (strpos($statusUpperTx, 'RETURN TO REQUESTOR') !== false) {
                $remarksDisplay = 'Returned to requestor';
            } elseif (strpos($statusUpperTx, 'DECLINED') !== false || strpos($statusUpperTx, 'REJECTED') !== false) {
                $remarksDisplay = 'Declined';
            } elseif (strpos($statusUpperTx, 'CANCELLED') !== false) {
                $remarksDisplay = 'Cancelled';
            } elseif (strpos($eventName, 'SEQUENCE_CREATION') !== false || strpos($eventName, 'SEQUENCE') !== false) {
                $remarksDisplay = 'Sequence created automatically';
            } elseif ($eventName === 'INSERT') {
                $remarksDisplay = 'Process created';
            } elseif ($eventName === 'UPDATE') {
                $remarksDisplay = 'Process updated';
            } else {
                $remarksDisplay = 'No remarks';
            }
        }
        
        $output .= '<div class="timeline-item" style="padding-left:50px; position:relative; margin-bottom:25px;">';
        $output .= '<!-- Timeline line -->';
        $output .= '<div style="position:absolute; left:15px; top:0; bottom:-25px; width:2px; background:linear-gradient(to bottom, ' . $timelineColor . ', ' . $timelineColor . ' 80%, transparent);"></div>';
        $output .= '<!-- Timeline dot -->';
        $output .= '<div style="position:absolute; left:8px; top:15px; width:16px; height:16px; border-radius:50%; background:' . $timelineColor . '; border:3px solid #fff; box-shadow:0 2px 5px rgba(0,0,0,0.15);"></div>';
        $output .= '<!-- Content card -->';
        $output .= '<div style="background:#fff; border-left:3px solid ' . $timelineColor . '; border-radius:4px; padding:15px; box-shadow:0 1px 3px rgba(0,0,0,0.1);">';
        $output .= '<div style="display:flex; justify-content:space-between; align-items:start; margin-bottom:8px;">';
        $output .= '<div>';
        $output .= '<div style="font-weight:600; color:#2A3F54; margin-bottom:3px;">';
        $output .= h($actor);
        $output .= '</div>';
        if ($showStepName) {
            $output .= '<div style="font-size:11px; color:#73879C; text-transform:uppercase; letter-spacing:0.5px;">';
            $output .= h($sequenceNames[$sequence] ?? "Step $sequence");
            $output .= '</div>';
        }
        $output .= '</div>';
        $output .= '<div style="text-align:right;">';
        $output .= '<span class="label label-' . $statusClass . '" style="font-size:11px;">';
        $output .= '<i class="fa ' . $statusIcon . '"></i> ' . h($statusText);
        $output .= '</span>';
        $output .= '</div>';
        $output .= '</div>';
        
        if ($remarksDisplay && $remarksDisplay !== 'No remarks') {
            $output .= '<div style="padding:8px 0; color:#555; font-size:13px; line-height:1.5;">';
            $output .= '<i class="fa fa-comment" style="color:#999; margin-right:5px;"></i>';
            $output .= h($remarksDisplay);
            $output .= '</div>';
        }
        
        $output .= '<div style="display:flex; justify-content:space-between; align-items:center; margin-top:8px; padding-top:8px; border-top:1px solid #ECF0F5;">';
        $output .= '<div style="font-size:11px; color:#999;">';
        $output .= '<i class="fa fa-calendar" style="margin-right:4px;"></i>' . h($dateDisplay);
        $output .= '</div>';
        $output .= '<div style="font-size:11px; color:#999;">';
        $output .= '<i class="fa fa-clock-o" style="margin-right:4px;"></i>' . h($timeDisplay);
        $output .= '</div>';
        $output .= '</div>';
        $output .= '</div>';
        $output .= '</div>';
    }
    
    return $output;
}

if (!function_exists('h')) {
    function h($val) { 
        return htmlspecialchars((string)$val, ENT_QUOTES, 'UTF-8'); 
    }
}
?>
