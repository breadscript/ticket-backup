    <script>
      (function(){
        function numberWithCommas(x){
          if(x===null||x===undefined||x==='') return '';
          var num = parseFloat(String(x).replace(/[^\d.\-]/g,''));
          if (isNaN(num)) return '';
          var s = num.toFixed(2);
          var parts = s.split('.');
          parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, ',');
          return parts.join('.');
        }
        function applyCurrency(){
          var curSel = document.getElementById('currency');
          var cur = curSel ? curSel.value : '';
          var symbol = cur ? (cur + ' ') : '';
          var fields = document.querySelectorAll('.currency-view');
          for (var i=0;i<fields.length;i++){
            var el = fields[i];
            var raw = el.value || el.textContent;
            var formatted = numberWithCommas(raw);
            if (el.tagName==='INPUT') el.value = (symbol + formatted);
            else el.textContent = (symbol + formatted);
          }
        }
        document.addEventListener('DOMContentLoaded', applyCurrency);
      })();
    </script>
    <!-- Force read-only and basic dev-tools blocking -->
    <script>
      (function() {
        // Force all inputs to be non-editable
        function lockForm() {
          var inputs = document.querySelectorAll('#view-form input, #view-form select, #view-form textarea, #view-form button');
          for (var i = 0; i < inputs.length; i++) {
            var el = inputs[i];
            // Skip workflow action buttons so they remain clickable
            if (el.tagName === 'BUTTON' && el.classList && el.classList.contains('wf-act')) {
              continue;
            }
            if (el.tagName === 'TEXTAREA' || el.type === 'text' || el.type === 'number' || el.type === 'date') {
              el.setAttribute('readonly', 'readonly');
            }
            el.setAttribute('disabled', 'disabled');
          }
          // Re-enable workflow action buttons explicitly (safety)
          var wfBtns = document.querySelectorAll('button.wf-act');
          for (var j = 0; j < wfBtns.length; j++) {
            wfBtns[j].removeAttribute('disabled');
          }
          // But enable the Back and Edit anchor buttons when request is returned to requestor (active seq 1)
          var backBtn = document.querySelector('a.btn.btn-default');
          if (backBtn) backBtn.removeAttribute('disabled');
          var editBtn = document.querySelector('a.btn-edit');
          if (editBtn) editBtn.removeAttribute('disabled');
        }

        // Prevent context menu and common devtools shortcuts
        function hardenUI() {
          document.addEventListener('contextmenu', function(e) { e.preventDefault(); return false; });
          document.addEventListener('keydown', function(e) {
            if (e.key === 'F12') { e.preventDefault(); return false; }
            if (e.ctrlKey && e.shiftKey && (e.key === 'I' || e.key === 'i')) { e.preventDefault(); return false; }
            if (e.ctrlKey && e.shiftKey && (e.key === 'J' || e.key === 'j')) { e.preventDefault(); return false; }
            if (e.ctrlKey && (e.key === 'U' || e.key === 'u')) { e.preventDefault(); return false; }
          });
        }

        // Field visibility configuration based on request type
        var fieldVisibility = {
          hideWhenRfp: [
            'field_category_col',
            'field_attachment_col',
            'field_ap_attachment_col',
            'field_cv_attachment_col', 
            'field_description_col',
            'field_amount_col',
            'field_reference_number_2_col',
            'field_date_col',
            'field_amount2_col',
            'field_from_company_col',
            'field_to_company_col',
            'field_credit_to_payroll_col',
            'field_issue_check_col'
          ],

          hideWhenErl: [
            'field_reference_number_col',
            'field_po_number_col',
            'field_due_date_col', 
            'field_cash_advance_col',
            'field_form_of_payment_col',
          ],

          hideWhenErgr: [
            'field_reference_number_2_col',
            'field_po_number_col',
            'field_due_date_col',
            'field_cash_advance_col', 
            'field_form_of_payment_col',
            'field_reference_number_col',
            'field_date_col',
            'field_amount2_col',
            'field_from_company_col',
            'field_to_company_col',
            'field_credit_to_payroll_col',
            'field_issue_check_col'
          ]
        };

        // Apply field visibility based on request type
        function applyFieldVisibility() {
          var requestType = '<?php echo h($requestType); ?>';
          var allFields = new Set();
          
          // Collect all field IDs from the configuration
          Object.keys(fieldVisibility).forEach(function(key) {
            fieldVisibility[key].forEach(function(id) {
              allFields.add(id);
            });
          });
          
          // Apply visibility rules
          allFields.forEach(function(fieldId) {
            var shouldHide = false;
            
            if (requestType === 'RFP') {
              shouldHide = fieldVisibility.hideWhenRfp.indexOf(fieldId) !== -1;
            } else if (requestType === 'ERL') {
              shouldHide = fieldVisibility.hideWhenErl.indexOf(fieldId) !== -1;
            } else if (requestType === 'ERGR') {
              shouldHide = fieldVisibility.hideWhenErgr.indexOf(fieldId) !== -1;
            }
            
            if (shouldHide) {
              var elements = document.querySelectorAll('#' + fieldId);
              elements.forEach(function(element) {
                element.style.display = 'none';
              });
            }
          });
        }

        document.addEventListener('DOMContentLoaded', function() {
          // Trigger existing logic to show/hide sections based on requestType
          var sel = document.getElementById('requestType');
          if (sel) {
            try { sel.dispatchEvent(new Event('change')); } catch (e) {}
          }
          
          // Apply field visibility based on request type
          applyFieldVisibility();
          
          lockForm();
          hardenUI();

          // WF buttons
          document.body.addEventListener('click', function(e){
            var t = e.target;
            if (t && t.classList && t.classList.contains('wf-act')) {
              e.preventDefault();
              var act = t.getAttribute('data-act');
              var seq = t.getAttribute('data-seq');
              var actor = t.getAttribute('data-actor');
              var role = t.getAttribute('data-role');
              
              // Customize confirmation message based on action
              var title = 'Confirm action';
              var confirmText = 'Proceed';
              
              switch(act) {
                case 'SUBMIT':
                  title = 'Submit Request';
                  confirmText = 'Submit';
                  break;
                case 'CANCEL':
                  title = 'Cancel Request';
                  confirmText = 'Cancel Request';
                  break;
                case 'APPROVE':
                  title = 'Approve Request';
                  confirmText = 'Approve';
                  break;
                case 'DECLINE':
                  title = 'Decline Request';
                  confirmText = 'Decline';
                  break;
                case 'RETURN_REQUESTOR':
                  title = 'Return to Requestor';
                  confirmText = 'Return';
                  break;
                case 'RETURN_APPROVER':
                  title = 'Return to Previous Approver';
                  confirmText = 'Return';
                  break;
              }
              
              Swal.fire({
                title: title,
                input: 'textarea',
                inputLabel: 'Remarks ' + (act === 'CANCEL' || act === 'DECLINE' || act.includes('RETURN') ? '(required)' : '(optional)'),
                inputPlaceholder: 'Enter remarks...',
                showCancelButton: true,
                confirmButtonText: confirmText,
                inputValidator: function(value) {
                  // Require remarks for certain actions
                  if ((act === 'CANCEL' || act === 'DECLINE' || act.includes('RETURN')) && !value.trim()) {
                    return 'Remarks are required for this action';
                  }
                }
              }).then(function(result){
                if (!result.isConfirmed) return;
                
                var fd = new FormData();
                fd.append('doc_type', '<?php echo h($requestType); ?>');
                fd.append('doc_number', '<?php echo h($docNumber); ?>');
                fd.append('sequence', seq);
                fd.append('actor_id', actor);
                fd.append('action', role);
                fd.append('decision', act);
                fd.append('remarks', result.value || '');
                fd.append('__expect_json', '1');
                
                // Show loading
                Swal.fire({
                  title: 'Processing...',
                  allowOutsideClick: false,
                  didOpen: function() {
                    Swal.showLoading();
                  }
                });
                
                fetch('workflow_action.php', { 
                  method: 'POST', 
                  body: fd, 
                  credentials: 'same-origin' 
                })
                .then(function(r){ 
                  return r.json().catch(function(){ 
                    return { success: false, message: 'Invalid server response' }; 
                  }); 
                })
                .then(function(res){
                  if (res && res.success) {
                    Swal.fire({ 
                      icon: 'success', 
                      title: 'Success', 
                      text: res.message || 'Workflow updated successfully.' 
                    }).then(function(){ 
                      location.reload(); 
                    });
                  } else {
                    Swal.fire({ 
                      icon: 'error', 
                      title: 'Error', 
                      text: (res && res.message) || 'Failed to update workflow.' 
                    });
                  }
                })
                .catch(function(err){ 
                  console.error('Workflow action error:', err);
                  Swal.fire({ 
                    icon: 'error', 
                    title: 'Network Error', 
                    text: 'Unable to connect to server. Please try again.' 
                  }); 
                });
              });
            }
          });
        });
      })();
    </script>

