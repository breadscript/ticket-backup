<!-- Modal for adding new withholding tax -->
<div class="modal fade" id="addWithholdingTaxModal" tabindex="-1" role="dialog" aria-labelledby="addWithholdingTaxModalLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="addWithholdingTaxModalLabel">
          <i class="fa fa-plus-circle"></i> Add New Withholding Tax
        </h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <form id="addWithholdingTaxForm" method="POST" action="save_withholding_tax.php">
        <div class="modal-body">
          <!-- CSRF Token -->
          <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
          
          <div class="form-group">
            <label for="tax_code">Tax Code <span style="color: red;">*</span></label>
            <input type="text" class="form-control" id="tax_code" name="tax_code" required
                   maxlength="50"
                   placeholder="e.g., WC161"
                   pattern="^[A-Z0-9]+$"
                   title="Tax code must contain only uppercase letters and numbers">
            <small class="form-text text-muted">Use uppercase letters and numbers only (e.g., WC161, WI012)</small>
          </div>
          
          <div class="form-group">
            <label for="tax_name">Tax Name <span style="color: red;">*</span></label>
            <input type="text" class="form-control" id="tax_name" name="tax_name" required
                   maxlength="200"
                   placeholder="e.g., PROFESSIONAL SERVICES">
            <small class="form-text text-muted">Brief description of the tax (max 200 characters)</small>
          </div>
          
          <div class="form-group">
            <label for="tax_rate">Tax Rate (%) <span style="color: red;">*</span></label>
            <input type="number" class="form-control" id="tax_rate" name="tax_rate" required
                   step="0.01"
                   min="0"
                   max="100"
                   placeholder="e.g., 15.00">
            <small class="form-text text-muted">Enter percentage value (e.g., 15 for 15%)</small>
          </div>
          
          <div class="form-group">
            <label for="tax_type">Tax Type <span style="color: red;">*</span></label>
            <select class="form-control" id="tax_type" name="tax_type" required>
              <option value="">Choose...</option>
              <option value="withholding">Withholding Tax</option>
              <option value="final">Final Tax</option>
            </select>
          </div>
          
          <div class="form-group">
            <label for="description">Description</label>
            <textarea class="form-control" id="description" name="description" rows="3"
                      maxlength="500"
                      placeholder="Optional: Add detailed description or notes"></textarea>
            <small class="form-text text-muted">Optional: Additional details about this tax (max 500 characters)</small>
          </div>
          
          <div class="form-group">
            <div class="checkbox">
              <label>
                <input type="checkbox" name="is_active" value="1" checked> Active
              </label>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-dismiss="modal">
            <i class="fa fa-times"></i> Cancel
          </button>
          <button type="submit" class="btn btn-success" id="saveWithholdingTaxBtn">
            <i class="fa fa-save"></i> Save Tax
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
// Handle form submission via AJAX
$(document).ready(function() {
  $('#addWithholdingTaxForm').on('submit', function(e) {
    e.preventDefault();
    
    var formData = $(this).serialize();
    var submitBtn = $('#saveWithholdingTaxBtn');
    var originalText = submitBtn.html();
    
    // Disable button and show loading
    submitBtn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Saving...');
    
    $.ajax({
      url: 'save_withholding_tax.php',
      type: 'POST',
      data: formData,
      dataType: 'json',
      success: function(response) {
        if (response.success) {
          // Show success message
          Swal.fire({
            icon: 'success',
            title: 'Success!',
            text: response.message || 'Withholding tax added successfully',
            timer: 2000,
            showConfirmButton: false
          });
          
          // Close modal
          $('#addWithholdingTaxModal').modal('hide');
          
          // Reset form
          $('#addWithholdingTaxForm')[0].reset();
          
          // Add new option to all withholding tax dropdowns
          if (response.tax) {
            console.log('Adding new tax to dropdowns:', response.tax);
            
            var displayText = response.tax.tax_code;
            if (parseFloat(response.tax.tax_rate) > 0) {
              displayText += ' - ' + response.tax.tax_rate + '% ' + response.tax.tax_name;
            } else {
              displayText += ' - ' + response.tax.tax_name;
            }
            
            // Add to all withholding tax dropdowns
            $('select[name="items_withholding_tax[]"]').each(function() {
              var $select = $(this);
              
              // Check if option already exists
              if ($select.find('option[value="' + response.tax.tax_code + '"]').length > 0) {
                console.log('Option already exists in dropdown, skipping');
                return; // Skip this dropdown
              }
              
              // Create new option with proper attributes
              var $newOption = $('<option></option>')
                .val(response.tax.tax_code)
                .text(displayText)
                .attr('data-rate', response.tax.tax_rate);
              
              // Add before "Other" option if it exists
              var $otherOption = $select.find('option[value="other"]');
              if ($otherOption.length > 0) {
                $newOption.insertBefore($otherOption);
                console.log('Added new option before "Other"');
              } else {
                $select.append($newOption);
                console.log('Added new option at end');
              }
            });
            
            console.log('Successfully added tax code to ' + $('select[name="items_withholding_tax[]"]').length + ' dropdown(s)');
          }
        } else {
          // Show error message
          Swal.fire({
            icon: 'error',
            title: 'Error',
            text: response.message || 'Failed to add withholding tax'
          });
        }
      },
      error: function(xhr, status, error) {
        console.error('Error:', error);
        Swal.fire({
          icon: 'error',
          title: 'Error',
          text: 'An error occurred while saving the withholding tax'
        });
      },
      complete: function() {
        // Re-enable button
        submitBtn.prop('disabled', false).html(originalText);
      }
    });
  });
  
  // Reset form when modal is closed
  $('#addWithholdingTaxModal').on('hidden.bs.modal', function() {
    $('#addWithholdingTaxForm')[0].reset();
  });
  
  // Convert tax code to uppercase as user types
  $('#tax_code').on('input', function() {
    this.value = this.value.toUpperCase();
  });
});
</script>

