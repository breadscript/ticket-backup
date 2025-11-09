<?php
// Include your database connection
include 'connection.php';

// Get the task ID from the request
$taskId = $_GET['taskId'] ?? null;

// Your existing query to get subtasks
$query = "SELECT * FROM your_subtasks_table WHERE parent_task_id = ?";
$stmt = $mysqlconn->prepare($query);
$stmt->bind_param('i', $taskId);
$stmt->execute();
$result = $stmt->get_result();

// Generate HTML for tasks
while ($row = $result->fetch_assoc()) {
    ?>
    <div class="task-item">
        <!-- Your existing task item HTML structure -->
        <div class="task-title"><?php echo htmlspecialchars($row['title']); ?></div>
        <!-- ... other task details ... -->
    </div>
    <?php
}
?>