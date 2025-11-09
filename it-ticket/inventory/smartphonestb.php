<?php
include('../check_session.php');
$moduleid=4;
include('proxy.php');
date_default_timezone_set('Asia/Manila');
$nowdateunix = date("Y-m-d h:i:s",time());  
$conn = connectionDB();
// $myusergroupid;

?>
<style>
/* Compact table styling for Smartphones */
#dataTables-smartphones {
    font-size: 12px;
}

#dataTables-smartphones td {
    padding: 6px 4px;
    vertical-align: middle;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    max-width: 120px;
}

#dataTables-smartphones th {
    padding: 8px 4px;
    font-size: 11px;
    font-weight: bold;
}

.btn-group-xs .btn {
    padding: 2px 6px;
    font-size: 10px;
    line-height: 1.2;
}

.btn-group-xs .btn i {
    font-size: 10px;
}
</style>
<div class="table-responsive">
    <div style="text-align:right;">
        <a href="export_smartphones_excel.php" class="btn btn-success" style="margin-bottom:10px;">
            <i class="fa fa-file-excel-o"></i> Export
        </a>
    </div>
<table class="table table-striped table-bordered table-hover" id="dataTables-smartphones">
    <thead>
        <tr>
            <th>Asset Reference Number</th>     
            <th>SIM Number</th>                                         
            <th>Asset Tag</th>  
            <th>Serial Number</th>
            <!-- <th>Assignee</th>                                                                                               -->
            <th>Checked Out To</th>                                                                             
            <th>Company</th>                                                
            <th>Asset Type</th>                                                                                                                                                                                                                                                             
            <th>Status</th>         
            <th>Warranty End</th>                                                                                                            
            <th>Action</th>                       
        </tr>
    </thead>
    <tbody>
    <?php 
    // Establish database connection
    $conn = connectionDB();

    // Query to fetch Smartphone inventory items
    $query = "SELECT *
    FROM inv_inventorylisttb
    WHERE statusid = 0 
    AND asset_type = 'Smartphone'
    ORDER BY asset_id DESC";

    $result = mysqli_query($conn, $query);

    // Check if we have results
    if($result && mysqli_num_rows($result) > 0) {
        while($row = mysqli_fetch_assoc($result)) {
            ?>
            <tr>
                <td><?php echo $row['asset_id'] ?: 'N/A'; ?></td>   
                <td><?php echo $row['sim_number'] ?: 'N/A'; ?></td>                                                 
                <td><?php echo $row['asset_tag'] ?: 'N/A'; ?></td>
                <td><?php echo $row['serial_number'] ?: 'N/A'; ?></td>
                <!-- <td><?php 
                    if (empty($row['assignee'])) {
                        echo 'N/A';
                    } else {
                        $assigneeIds = explode(',', $row['assignee']);
                        $assigneeNames = [];
                        foreach ($assigneeIds as $assigneeId) {
                            $assigneeQuery = "SELECT user_firstname, user_lastname FROM sys_usertb WHERE id = '$assigneeId'";
                            $assigneeResult = mysqli_query($conn, $assigneeQuery);
                            if ($assigneeRow = mysqli_fetch_assoc($assigneeResult)) {
                                $assigneeNames[] = $assigneeRow['user_firstname'] . ' ' . $assigneeRow['user_lastname'];
                            } else {
                                $assigneeNames[] = "Unknown";
                            }
                        }
                        echo implode(', ', $assigneeNames);
                    }
                ?></td> -->
                <td><?php 
                    if (empty($row['current_owner'])) {
                        echo 'N/A';
                    } else {
                        $currentOwnerIds = explode(',', $row['current_owner']);
                        $currentOwnerNames = [];
                        foreach ($currentOwnerIds as $ownerId) {
                            $ownerId = trim($ownerId);
                            if ($ownerId !== '') {
                                $ownerQuery = "SELECT user_firstname, user_lastname FROM sys_usertb WHERE id = '$ownerId'";
                                $ownerResult = mysqli_query($conn, $ownerQuery);
                                if ($ownerRow = mysqli_fetch_assoc($ownerResult)) {
                                    $currentOwnerNames[] = $ownerRow['user_firstname'] . ' ' . $ownerRow['user_lastname'];
                                } else {
                                    $currentOwnerNames[] = "Unknown";
                                }
                            }
                        }
                        echo implode(', ', $currentOwnerNames);
                    }
                ?></td>
                <td><?php echo $row['company'] ?: 'N/A'; ?></td>
                <td><?php echo $row['asset_type'] ?: 'N/A'; ?></td>                                                                                                                                                                                                                                     
                <td style="color: <?php 
                    $colors = ['Available'=>'#28a745','In-repair'=>'#ffc107','In-use'=>'#007bff','Lost'=>'#dc3545','Reserved'=>'#6f42c1','Retired'=>'#6c757d','S.U Available'=>'#20c997','S.U Deployed'=>'#fd7e14','Upcoming'=>'#17a2b8'];
                    echo $colors[$row['status']] ?? '#000000';
                ?>"><strong><?php echo $row['status'] ?: 'Unknown'; ?></strong></td>                                                                                                    
                <td><?php 
                    if (empty($row['warranty_end'])) {
                        echo '<div style="color: #000000">N/A</div>';
                        echo '<div style="color: #dc3545"><i><strong>No Warranty Info</strong></i></div>';
                    } else {
                        $warranty_end = strtotime($row['warranty_end']);
                        $current_date = time();
                        if ($warranty_end > $current_date) {
                            echo '<div style="color: #000000">' . date('F d, Y', $warranty_end) . '</div>';
                            echo '<div style="color: #28a745"><i><strong>Under Warranty</strong></i></div>';
                        } else {
                            echo '<div style="color: #000000">' . date('F d, Y', $warranty_end) . '</div>';
                            echo '<div style="color: #dc3545"><i><strong>Warranty Expired</strong></i></div>';
                        }
                    }
                ?></td>
                <td>
                <div class="btn-group btn-group-xs" role="group">
                <button type="button" class="btn btn-success btn-xs" onclick="showCheckout('<?php echo $row['asset_tag']; ?>');" data-toggle="modal" data-target="#myModal_checkout">
                                                                <i class="ace-icon fa fa-pencil"></i> Check out
                                                            </button>
                        <button type="button" class="btn btn-info btn-xs" onclick="showUpdateAsset('<?php echo $row['asset_tag']; ?>');" data-toggle="modal" data-target="#myModal_updateTask">
                            <i class="ace-icon fa fa-pencil"></i> Edit
                        </button>
                        <a href="assetInfoPage.php?asset_id=<?php echo $row['asset_id']; ?>" class="btn btn-xs btn-danger">
                            <i class="ace-icon fa fa-eye"></i> View
                        </a> 
                        <!-- <button type="button" class="btn btn-danger" onclick="deleteAsset('<?php echo $row['asset_id']; ?>');">
                            <i class="ace-icon fa fa-trash bigger-120"></i> Delete
                        </button>       -->
                                                                                                                                                                
                    </div>
                </td>
            </tr>
            <?php
        }
    } else {
        // No data found
        echo '<tr><td colspan="10" class="text-center"><span style="color: red; font-weight: bold;">No Smartphone inventory items found.</span></td></tr>';
    }
    ?>
    </tbody>
</table>
</div>

<script type="text/javascript">
 $(document).ready(function() {
       $('#dataTables-smartphones').DataTable({
            "aaSorting": [],
            "scrollCollapse": true, 
            "autoWidth": false,
            "responsive": true,
            "bSort" : true,
            "lengthMenu": [ [10, 25, 50, 100, 200, 300, 400, 500], [10, 25, 50, 100, 200, 300, 400, 500] ]
        });
    });
</script>