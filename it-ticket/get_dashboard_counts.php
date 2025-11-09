<?php
// Prevent any output before our JSON
ob_clean();
header('Content-Type: application/json');

include "conn/db.php";
$conn = connectionDB();

// Get assignee ID from request
$assigneeId = isset($_GET['assignee']) ? $_GET['assignee'] : 'all';

// Base condition for filtering by assignee using JOIN with pm_taskassigneetb
$assigneeCondition = $assigneeId !== 'all' ? 
    "AND EXISTS (SELECT 1 FROM pm_taskassigneetb ta WHERE ta.taskid = pm_projecttasktb.id AND ta.assigneeid = '" . mysqli_real_escape_string($conn, $assigneeId) . "')" 
    : "";

$response = [
    'tickets' => [],
    'assignments' => [],
    'classifications' => [
        'tickets' => [],
        'assignments' => []
    ]
];

// Helper function to get count
function getCount($conn, $query) {
    $result = mysqli_query($conn, $query);
    if ($result && mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_array($result);
        return (int)$row[0];
    }
    return 0;
}

// Get ticket counts
$response['tickets'] = [
    'total' => getCount($conn, "SELECT COUNT(*) FROM pm_projecttasktb WHERE istask = 2 $assigneeCondition"),
    'new' => getCount($conn, "SELECT COUNT(*) FROM pm_projecttasktb WHERE istask = 2 AND statusid = 1 $assigneeCondition"),
    'in_progress' => getCount($conn, "SELECT COUNT(*) FROM pm_projecttasktb WHERE istask = 2 AND statusid = 3 $assigneeCondition"),
    'pending' => getCount($conn, "SELECT COUNT(*) FROM pm_projecttasktb WHERE istask = 2 AND statusid = 4 $assigneeCondition"),
    'done' => getCount($conn, "SELECT COUNT(*) FROM pm_projecttasktb WHERE istask = 2 AND statusid = 6 $assigneeCondition"),
    'rejected' => getCount($conn, "SELECT COUNT(*) FROM pm_projecttasktb WHERE istask = 2 AND statusid = 2 $assigneeCondition"),
    'cancelled' => getCount($conn, "SELECT COUNT(*) FROM pm_projecttasktb WHERE istask = 2 AND statusid = 5 $assigneeCondition")
];

// Get assignment counts
$response['assignments'] = [
    'total' => getCount($conn, "SELECT COUNT(*) FROM pm_projecttasktb WHERE istask = 1 $assigneeCondition"),
    'new' => getCount($conn, "SELECT COUNT(*) FROM pm_projecttasktb WHERE istask = 1 AND statusid = 1 $assigneeCondition"),
    'in_progress' => getCount($conn, "SELECT COUNT(*) FROM pm_projecttasktb WHERE istask = 1 AND statusid = 3 $assigneeCondition"),
    'pending' => getCount($conn, "SELECT COUNT(*) FROM pm_projecttasktb WHERE istask = 1 AND statusid = 4 $assigneeCondition"),
    'done' => getCount($conn, "SELECT COUNT(*) FROM pm_projecttasktb WHERE istask = 1 AND statusid = 6 $assigneeCondition"),
    'rejected' => getCount($conn, "SELECT COUNT(*) FROM pm_projecttasktb WHERE istask = 1 AND statusid = 2 $assigneeCondition"),
    'cancelled' => getCount($conn, "SELECT COUNT(*) FROM pm_projecttasktb WHERE istask = 1 AND statusid = 5 $assigneeCondition")
];

// Get classification counts for tickets
$classifications = [
    1 => 'Feature',
    2 => 'Enhancement',
    3 => 'Bug/Error',
    4 => 'Support/Maintenance',
    5 => 'Others'
];

foreach ($classifications as $classId => $className) {
    $response['classifications']['tickets'][] = [
        'name' => $className,
        'total' => getCount($conn, "SELECT COUNT(*) FROM pm_projecttasktb WHERE istask = 2 AND classificationid = $classId $assigneeCondition"),
        'ongoing' => getCount($conn, "SELECT COUNT(*) FROM pm_projecttasktb WHERE istask = 2 AND classificationid = $classId AND statusid IN (1,2,3,4,5) $assigneeCondition"),
        'closed' => getCount($conn, "SELECT COUNT(*) FROM pm_projecttasktb WHERE istask = 2 AND classificationid = $classId AND statusid = 6 $assigneeCondition")
    ];
}

// Get classification counts for assignments
$classificationQuery = "SELECT id, classification FROM sys_taskclassificationtb";
$classificationResult = mysqli_query($conn, $classificationQuery);
while ($classification = mysqli_fetch_assoc($classificationResult)) {
    $classId = $classification['id'];
    $response['classifications']['assignments'][] = [
        'name' => $classification['classification'],
        'total' => getCount($conn, "SELECT COUNT(*) FROM pm_projecttasktb WHERE istask = 1 AND classificationid = $classId $assigneeCondition"),
        'ongoing' => getCount($conn, "SELECT COUNT(*) FROM pm_projecttasktb WHERE istask = 1 AND classificationid = $classId AND statusid IN (1,2,3,4,5) $assigneeCondition"),
        'closed' => getCount($conn, "SELECT COUNT(*) FROM pm_projecttasktb WHERE istask = 1 AND classificationid = $classId AND statusid = 6 $assigneeCondition")
    ];
}

// Debug information (comment out in production)
$response['debug'] = [
    'assignee_id' => $assigneeId,
    'assignee_condition' => $assigneeCondition
];

echo json_encode($response);
?>