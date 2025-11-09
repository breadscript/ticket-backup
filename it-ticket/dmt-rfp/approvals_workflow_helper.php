<?php
/**
 * Approvals (Workflow) Helper
 * Reusable renderer for the Approvals section used in approver views.
 *
 * Dependencies expected in caller scope:
 * - h(string): string
 * - wf_resolve_actor_fullname(mysqli $conn, string $actorId): string
 * - wf_status_badge(string $status): string (HTML badge)
 * - __matches_actor(string $actorId, array $sessionUser): bool
 */

if (!function_exists('renderApprovalsWorkflow')) {
    function renderApprovalsWorkflow($approverSteps, $processStatus, $activeSequence, $earliestWaitingSeq, $__SESSION_USER, $sequence2Approved, $mysqlconn) {
        if (!is_array($approverSteps) || empty($approverSteps)) {
            return '<div class="alert alert-info">No approvers configured.</div>';
        }

        ksort($approverSteps);
        $colClass = 'col-md-2 col-sm-4 col-xs-6';
        $html = '';

        foreach ($approverSteps as $seq => $actors) {
            $html .= '<div class="'. $colClass .'" style="text-align:center; margin-bottom:15px;">';

            $isParallelGroup = (count($actors) > 1 && !empty($actors[0]['is_parallel']));

            // For parallel-one-approval steps, when any approver already acted,
            // only show the approver who acted (hide peers that are skipped/pending).
            $actorsToRender = $actors;
            if ($isParallelGroup) {
                $note = isset($actors[0]['note']) ? (string)$actors[0]['note'] : '';
                $oneApprovalOnly = (stripos($note, 'one approval only') !== false || stripos($note, 'one approval') !== false);
                if ($oneApprovalOnly) {
                    $strongActed = false;
                    $actedKeys = [];
                    foreach ($actors as $a0) {
                        $k0 = $seq.'|'.trim((string)$a0['actor_id']).'|'.trim((string)$a0['action']);
                        $st0 = isset($processStatus[$k0]) ? strtoupper(trim((string)$processStatus[$k0])) : '';
                        if ($st0 && $st0 !== 'WAITING FOR APPROVAL' && $st0 !== 'SUBMITTED' && $st0 !== 'SUBMIT') {
                            // Ignore SKIPPED peers entirely
                            if (strpos($st0, 'SKIPPED') !== false) { continue; }
                            if (strpos($st0, 'APPROVED') !== false || strpos($st0, 'DECLINED') !== false || strpos($st0, 'RETURN') !== false || strpos($st0, 'CANCEL') !== false) {
                                $strongActed = true;
                                $actedKeys[$k0] = true;
                            }
                        }
                    }
                    if ($strongActed) {
                        $filtered = [];
                        foreach ($actors as $a1) {
                            $k1 = $seq.'|'.trim((string)$a1['actor_id']).'|'.trim((string)$a1['action']);
                            if (isset($actedKeys[$k1])) { $filtered[] = $a1; }
                        }
                        if (!empty($filtered)) { $actorsToRender = $filtered; }
                    }
                }
            }

            foreach ($actorsToRender as $a) {
                $key = $seq.'|'.trim((string)$a['actor_id']).'|'.trim((string)$a['action']);
                $status = isset($processStatus[$key]) ? $processStatus[$key] : '';

                $html .= '<div style="margin-bottom:12px;">';
                $actorLabel = ($seq >= 2) ? wf_resolve_actor_fullname($mysqlconn, (string)$a['actor_id']) : (string)$a['actor_id'];
                if ($seq >= 2) { $actorLabel = strtoupper($actorLabel); }
                $html .= '<div style="height:16px; font-size:12px; color:#777; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">'.h($actorLabel).'</div>';
                $html .= '<div style="border-bottom:1px solid #ddd; width:70%; margin:6px auto 6px;"></div>';
                $html .= '<div style="font-size:11px; color:#888;">'.h(str_replace('_',' ',(string)$a['action'])).'</div>';
                $html .= '<div style="margin-top:6px;">'. wf_status_badge($status) .'</div>';

                // Action buttons logic
                $isActiveSeq = ($seq === $activeSequence);
                $statusUpper = strtoupper(trim((string)$status));
                $isPending = empty($status) || $statusUpper === 'WAITING FOR APPROVAL' || $statusUpper === 'SUBMITTED' || $statusUpper === 'SUBMIT';
                $isMyTurn = ($earliestWaitingSeq !== null && $seq === $earliestWaitingSeq)
                    && ($statusUpper === 'WAITING FOR APPROVAL')
                    && __matches_actor($a['actor_id'], $__SESSION_USER);

                // Check if this is the current user's step (regardless of status)
                $isMyStep = __matches_actor($a['actor_id'], $__SESSION_USER);

                // Check if user has already acted on this step
                $hasActedOnThisStep = $isMyStep && $statusUpper !== 'WAITING FOR APPROVAL' && $statusUpper !== 'SUBMITTED' && $statusUpper !== 'SUBMIT';

                if ($seq === 1 && $isMyTurn) {
                    // Sequence 1 (Requestor) logic
                    if (($statusUpper === 'SUBMITTED' || $statusUpper === 'SUBMIT') && !$sequence2Approved) {
                        // Show cancel button when submitted and sequence 2 hasn't approved yet
                        $html .= '<div style="margin-top:8px;">'
                            .'<button type="button" class="btn btn-xs btn-danger wf-act" data-act="CANCEL" data-seq="'.h($seq).'" data-actor="'.h($a['actor_id']).'" data-role="'.h($a['action']).'">Cancel</button>'
                            .'</div>';
                    } elseif ($statusUpper === 'WAITING FOR APPROVAL') {
                        // Show submit and cancel buttons when status is "Waiting for Approval" (returned from sequence 2 or initial state)
                        $html .= '<div style="margin-top:8px;">'
                            .'<button type="button" class="btn btn-xs btn-primary wf-act" data-act="SUBMIT" data-seq="'.h($seq).'" data-actor="'.h($a['actor_id']).'" data-role="'.h($a['action']).'">Submit</button> '
                            .'<button type="button" class="btn btn-xs btn-danger wf-act" data-act="CANCEL" data-seq="'.h($seq).'" data-actor="'.h($a['actor_id']).'" data-role="'.h($a['action']).'">Cancel</button>'
                            .'</div>';
                    } elseif ($isPending && $statusUpper !== 'SUBMITTED' && $statusUpper !== 'SUBMIT' && $statusUpper !== 'WAITING FOR APPROVAL') {
                        // Show initial submit button for first time (fallback)
                        $html .= '<div style="margin-top:8px;">'
                            .'<button type="button" class="btn btn-xs btn-primary wf-act" data-act="SUBMIT" data-seq="'.h($seq).'" data-actor="'.h($a['actor_id']).'" data-role="'.h($a['action']).'">Submit</button>'
                            .'</div>';
                    }
                } elseif ($seq === 2 && $isPending && $isMyTurn) {
                    // Sequence 2 logic - show approve, return to requestor, decline when pending
                    $html .= '<div style="margin-top:8px;">'
                        .'<button type="button" class="btn btn-xs btn-success wf-act" data-act="APPROVE" data-seq="'.h($seq).'" data-actor="'.h($a['actor_id']).'" data-role="'.h($a['action']).'">Approve</button> '
                        .'<button type="button" class="btn btn-xs btn-info wf-act" data-act="RETURN_REQUESTOR" data-seq="'.h($seq).'" data-actor="'.h($a['actor_id']).'" data-role="'.h($a['action']).'">Return to Requestor</button> '
                        .'<button type="button" class="btn btn-xs btn-danger wf-act" data-act="DECLINE" data-seq="'.h($seq).'" data-actor="'.h($a['actor_id']).'" data-role="'.h($a['action']).'">Decline</button>'
                        .'</div>';
                } elseif ($seq > 2 && $isActiveSeq && $isPending && $isMyTurn) {
                    // Other sequences (3+) logic
                    $html .= '<div style="margin-top:8px;">'
                        .'<button type="button" class="btn btn-xs btn-success wf-act" data-act="APPROVE" data-seq="'.h($seq).'" data-actor="'.h($a['actor_id']).'" data-role="'.h($a['action']).'">Approve</button> '
                        .'<button type="button" class="btn btn-xs btn-warning wf-act" data-act="RETURN_APPROVER" data-seq="'.h($seq).'" data-actor="'.h($a['actor_id']).'" data-role="'.h($a['action']).'">Return to Approver</button> '
                        .'<button type="button" class="btn btn-xs btn-info wf-act" data-act="RETURN_REQUESTOR" data-seq="'.h($seq).'" data-actor="'.h($a['actor_id']).'" data-role="'.h($a['action']).'">Return to Requestor</button> '
                        .'<button type="button" class="btn btn-xs btn-danger wf-act" data-act="DECLINE" data-seq="'.h($seq).'" data-actor="'.h($a['actor_id']).'" data-role="'.h($a['action']).'">Decline</button>'
                        .'</div>';
                }

                // Show action buttons for users who have already acted (for viewing/modifying their actions)
                if ($hasActedOnThisStep && $seq > 1) {
                    $html .= '<div style="margin-top:8px;">';
                    $html .= '<small class="text-muted">You have already acted on this step</small>';
                    $html .= '</div>';
                }

                $html .= '</div>';
            }

            $html .= '</div>';
        }

        return $html;
    }
}

?>


