<?php
// Reusable workflow data provider for Disbursement pages

class DisbursementClass {
    /**
     * Fetch workflow approver steps, process statuses, and existing sequences.
     *
     * @param mysqli $mysqlconn
     * @param string $requestType
     * @param string $company
     * @param string $costCenter
     * @param string $docNumber
     * @param string $payee Display name for sequence 1 actor (requestor)
     * @return array{approverSteps: array<int,array>, processStatus: array<string,string>, existingSequences: int[]}
     */
    public static function getWorkflowData($mysqlconn, $requestType, $company, $costCenter, $docNumber, $payee) {
        $approverSteps = [];
        $processStatus = [];
        $existingSequences = [];

        if (isset($mysqlconn) && $mysqlconn) {
            // Check table presence
            $hasTemplate = false;
            $hasProcess = false;
            $resTpl = @mysqli_query($mysqlconn, "SHOW TABLES LIKE 'work_flow_template'");
            if ($resTpl) { $hasTemplate = mysqli_num_rows($resTpl) > 0; }
            $resProc = @mysqli_query($mysqlconn, "SHOW TABLES LIKE 'work_flow_process'");
            if ($resProc) { $hasProcess = mysqli_num_rows($resProc) > 0; }

            // Load template rows -> approver steps
            if ($hasTemplate) {
                $sqlTpl = "SELECT id, work_flow_id, department, company, sequence, actor_id, action, is_parellel, `global`, amount_from, amount_to, Note
                           FROM work_flow_template
                           WHERE work_flow_id = ? AND company = ? AND (department = ? OR department = '' OR department IS NULL)
                           ORDER BY sequence, id";
                if ($stmtTpl = mysqli_prepare($mysqlconn, $sqlTpl)) {
                    $dept = (string)$costCenter;
                    mysqli_stmt_bind_param($stmtTpl, 'sss', $requestType, $company, $dept);
                    mysqli_stmt_execute($stmtTpl);
                    $rs = mysqli_stmt_get_result($stmtTpl);
                    while ($row = mysqli_fetch_assoc($rs)) {
                        $seq = (int)$row['sequence'];
                        if (!isset($approverSteps[$seq])) $approverSteps[$seq] = [];
                        $val = isset($row['is_parellel']) ? $row['is_parellel'] : '';
                        $valStr = is_string($val) ? $val : (string)$val;
                        $isParallel = (
                            stripos($valStr, 'YES') !== false ||
                            stripos($valStr, 'Y') !== false ||
                            stripos($valStr, 'TRUE') !== false ||
                            $val === 1 || $val === '1'
                        );
                        $note = isset($row['Note']) ? (string)$row['Note'] : '';
                        // Display payee on Requestor step
                        $actorDisplay = $row['actor_id'];
                        if ($seq === 1 && strcasecmp((string)$row['action'], 'Requestor') === 0) {
                            $actorDisplay = $payee ?: $row['actor_id'];
                        }
                        $approverSteps[$seq][] = [
                            'actor_id' => $actorDisplay,
                            'action' => $row['action'],
                            'is_parallel' => $isParallel,
                            'note' => $note,
                        ];
                    }
                    mysqli_stmt_close($stmtTpl);
                }
            }

            // Load process statuses
            if ($hasProcess) {
                $sqlP = "SELECT sequence, actor_id, action, status
                         FROM work_flow_process
                         WHERE doc_type = ? AND doc_number = ?";
                if ($stmtP = mysqli_prepare($mysqlconn, $sqlP)) {
                    mysqli_stmt_bind_param($stmtP, 'ss', $requestType, $docNumber);
                    mysqli_stmt_execute($stmtP);
                    $rp = mysqli_stmt_get_result($stmtP);
                    while ($row = mysqli_fetch_assoc($rp)) {
                        $key = trim((string)$row['sequence']).'|'.trim((string)$row['actor_id']).'|'.trim((string)$row['action']);
                        $processStatus[$key] = $row['status'];
                    }
                    mysqli_stmt_close($stmtP);
                }
            }

            // Distinct sequences present in process
            $stmtSeq = mysqli_prepare($mysqlconn, "SELECT DISTINCT sequence FROM work_flow_process WHERE doc_type = ? AND doc_number = ? ORDER BY sequence");
            if ($stmtSeq) {
                mysqli_stmt_bind_param($stmtSeq, 'ss', $requestType, $docNumber);
                mysqli_stmt_execute($stmtSeq);
                $resSeq = mysqli_stmt_get_result($stmtSeq);
                while ($row = mysqli_fetch_assoc($resSeq)) {
                    $existingSequences[] = (int)$row['sequence'];
                }
                mysqli_stmt_close($stmtSeq);
            }
        }

        // Fallback flow if no template rows
        if (empty($approverSteps)) {
            $approverSteps = [
                1 => [ ['actor_id' => $payee ?: 'Requestor', 'action' => 'Requestor', 'is_parallel' => false] ],
                2 => [ ['actor_id' => 'Cost Center Head', 'action' => 'Cost_Center_Head', 'is_parallel' => false] ],
                3 => [
                    ['actor_id' => 'Accounting 1', 'action' => 'Accounting_Approver_1', 'is_parallel' => true],
                    ['actor_id' => 'Accounting 2', 'action' => 'Accounting_Approver_1_Sub', 'is_parallel' => true]
                ],
                4 => [ ['actor_id' => 'Accounting 3', 'action' => 'Accounting_Approver_2', 'is_parallel' => false] ],
                5 => [ ['actor_id' => 'Controller', 'action' => 'Accounting_Controller_1', 'is_parallel' => false] ],
                6 => [ ['actor_id' => 'Cashier', 'action' => 'Accounting_Cashier', 'is_parallel' => false] ],
            ];
        }

        return [
            'approverSteps' => $approverSteps,
            'processStatus' => $processStatus,
            'existingSequences' => $existingSequences,
        ];
    }
}

?>