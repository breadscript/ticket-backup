<?php
require_once('../conn/db.php');
require_once('../check_session.php');

$taskId = $_GET['taskId'] ?? 0;

// Get subtask details
$query = "SELECT subject FROM pm_projecttasktb WHERE id = '$taskId' AND type = 'subtask'";
$result = mysqli_query($mysqlconn, $query);
$subtask = mysqli_fetch_assoc($result);
?>

<div class="modal-dialog" role="document">
    <div class="modal-content">
        <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
            <h4 class="modal-title" id="deleteSubtaskModalLabel">Delete Subtask</h4>
        </div>
        <div class="modal-body">
            <div class="row">
                <div class="col-md-12">
                    <p>Are you sure you want to delete this subtask?</p>
                    <?php if ($subtask): ?>
                        <p><strong>Subject:</strong> <?php echo htmlspecialchars($subtask['subject']); ?></p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <div class="modal-footer">
        <div id="flash-message"></div>
            <button type="button" class="btn btn-danger" onclick="deleteSubtask(<?php echo $taskId; ?>)">
                 Delete
            </button>
            <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
        </div>
    </div>
</div>

<script>
function deleteSubtask(taskId) {
    $.ajax({
        url: 'delete_subtask.php',
        type: 'POST',
        data: { taskId: taskId },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                // Show flash message first
                $('#flash-message').html('<div class="alert alert-success">Subtask deleted successfully!</div>');
                
                // Remove the row with animation
                $('#task-row-' + taskId).fadeOut(400, function() {
                    $(this).remove();
                });
                
                // Wait for 1.5 seconds before closing modal and reloading
                setTimeout(function() {
                    $('#deleteSubtaskModal').modal('hide');
                    location.reload();
                }, 1500);
            } else {
                $('#flash-message').html('<div class="alert alert-danger">' + (response.message || 'Error deleting subtask') + '</div>');
            }
        },
        error: function(xhr, status, error) {
            $('#flash-message').html('<div class="alert alert-danger">An unexpected error occurred while deleting the subtask.</div>');
            setTimeout(function() {
                $('#deleteSubtaskModal').modal('hide');
            }, 1500);
        }
    });
}
</script>
