<?php
require_once('../conn/db.php');

// Get task ID from AJAX request
$task_id = $_GET['taskId'] ?? null;

if (!$task_id) {
    echo '<div class="alert alert-danger">Task ID not provided</div>';
    exit;
}

if (!$mysqlconn) {
    echo '<div class="alert alert-danger">Database connection failed</div>';
    exit;
}

// Query to get files
$query66 = "SELECT * FROM pm_threadtb WHERE taskid = ? AND file_data IS NOT NULL AND file_data != '' ORDER BY id DESC";
$stmt = mysqli_prepare($mysqlconn, $query66);

if (!$stmt) {
    echo '<div class="alert alert-danger">Query preparation failed</div>';
    exit;
}

mysqli_stmt_bind_param($stmt, 's', $task_id);
mysqli_stmt_execute($stmt);
$result66 = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result66) === 0) {
    echo '<div class="alert alert-info">No files found</div>';
    mysqli_stmt_close($stmt);
    exit;
}
?>

<div style="max-height: 400px; overflow-y: auto;">
    <div class="form-group">
        <ul class="list-unstyled">
            <?php
            while($row66 = mysqli_fetch_assoc($result66)) {
                // Check if file_data contains multiple files (comma-separated)
                $fileContents = explode(',', $row66['file_data']);
                
                foreach($fileContents as $fileContent) {
                    $fileContent = trim($fileContent);
                    if (empty($fileContent)) continue;
                    
                    $fileid = $row66['id'];
                    $filename = basename($fileContent);
                    $displayName = $filename ? $filename : "File ID: $fileid";
                    $fileExtension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
                    
                    // Determine file icon based on extension
                    $iconClass = 'fa-file-o';
                    switch($fileExtension) {
                        case 'pdf': $iconClass = 'fa-file-pdf-o'; break;
                        case 'doc':
                        case 'docx': $iconClass = 'fa-file-word-o'; break;
                        case 'xls':
                        case 'xlsx': $iconClass = 'fa-file-excel-o'; break;
                        case 'ppt':
                        case 'pptx': $iconClass = 'fa-file-powerpoint-o'; break;
                        case 'jpg':
                        case 'jpeg':
                        case 'png':
                        case 'gif': $iconClass = 'fa-file-image-o'; break;
                        case 'zip':
                        case 'rar': $iconClass = 'fa-file-archive-o'; break;
                        case 'txt': $iconClass = 'fa-file-text-o'; break;
                    }
                    ?>
                    <li class="file-item" style="padding: 12px; border-bottom: 1px solid #eee; display: flex; align-items: center; gap: 10px;">
                        <i class="ace-icon fa <?php echo $iconClass; ?> bigger-125"></i>
                        <div style="flex-grow: 1;">
                            <a href="<?php echo htmlspecialchars($fileContent); ?>" 
                               class="file-info" 
                               download="<?php echo htmlspecialchars($filename); ?>"
                               style="text-decoration: none; color: inherit;">
                                <span class="filename"><?php echo htmlspecialchars($displayName); ?></span>
                            </a>
                            <div class="file-meta" style="font-size: 12px; color: #666;">
                                <span>Uploaded: <?php echo date('Y-m-d H:i', strtotime($row66['datetimecreated'])); ?></span>
                                <?php if (!empty($row66['username'])): ?>
                                    <span style="margin-left: 10px;">By: <?php echo htmlspecialchars($row66['username']); ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="file-actions" style="display: flex; gap: 5px;">
                            <?php if (in_array($fileExtension, ['jpg', 'jpeg', 'png', 'gif'])): ?>
                                <a href="<?php echo htmlspecialchars($fileContent); ?>" 
                                   onclick="previewImage('<?php echo htmlspecialchars($fileContent); ?>'); return false;"
                                   class="btn btn-xs btn-info" 
                                   title="Preview">
                                    <i class="ace-icon fa fa-eye"></i>
                                </a>
                            <?php endif; ?>
                            <a href="<?php echo htmlspecialchars($fileContent); ?>" 
                               download="<?php echo htmlspecialchars($filename); ?>" 
                               class="btn btn-xs btn-success"
                               title="Download">
                                <i class="ace-icon fa fa-download"></i>
                            </a>
                        </div>
                    </li>
                    <?php
                }
            }
            ?>
        </ul>
    </div>
</div>

<!-- Image Preview Modal -->
<div class="modal fade" id="imagePreviewModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
                <h4 class="modal-title"></h4>
            </div>
            <div class="modal-body text-center">
                <img id="previewImage" src="" alt="Preview" style="max-width: 100%; max-height: 80vh;">
            </div>
        </div>
    </div>
</div>

<script>
function previewImage(url) {
    document.getElementById('previewImage').src = url;
    $('.modal-title').text(url.split('/').pop());
    $('#imagePreviewModal').modal('show');
}

// Initialize tooltips
$(document).ready(function(){
    $('[title]').tooltip();
});
</script>

<?php
mysqli_stmt_close($stmt);
?>