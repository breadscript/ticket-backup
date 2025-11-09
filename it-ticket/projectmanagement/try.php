<div class="col-lg-4">     
    <div class="form-group">
        <label for="date_from">Date From</label>
        <div class="input-group">
            <input class="form-control input-xs datepicker" type="text" id="date_from" placeholder="YYYY-MM-DD" name="date_from" value="" required>
            <span class="input-group-addon" style="cursor: pointer;" onclick="$('#date_from').datepicker('show');">
                <i class="fa fa-calendar"></i>
            </span>
        </div>
    </div>  
</div>

<div class="col-lg-4">     
    <div class="form-group">
        <label for="date_to">Date To</label>
        <div class="input-group">
            <input class="form-control input-xs datepicker" type="text" id="date_to" placeholder="YYYY-MM-DD" name="date_to" value="" required>
            <span class="input-group-addon" style="cursor: pointer;" onclick="$('#date_to').datepicker('show');">
                <i class="fa fa-calendar"></i>
            </span>
        </div>
    </div>  
</div>

<script>
    // Automatically add an asterisk inside the required input fields
    document.querySelectorAll('input[required]').forEach(function(input) {
        input.addEventListener('focus', function() {
            if (input.value === '') {
                input.value = '* ' + input.placeholder; // Add asterisk if empty
            }
        });
        input.addEventListener('blur', function() {
            if (input.value === '* ' + input.placeholder) {
                input.value = ''; // Remove asterisk on blur if the field is still empty
            }
        });
    });
</script>
