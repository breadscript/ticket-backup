// Financial Request Form - Consolidated JavaScript
// Handles all form functionality including dynamic behavior, validation, and UI interactions
// Compatible with RFP, ERL, ERGR form types

(function() {
  'use strict';

  // Global namespace for the financial form
  var FinancialForm = {
    config: {
      submissionCooldown: 5000, // 5 seconds between submissions
        maxFileSize: 200 * 1024 * 1024, // 200MB max file size
      allowedFileTypes: ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'jpg', 'jpeg', 'png', 'gif']
    },
    
    // Field visibility configuration
    fieldVisibility: {
      hideWhenRfp: [
        'field_category_col',
        'field_attachment_col', 
        'field_description_col',
        'field_reference_number_2_col',
        'field_date_col',
        'field_amount2_col',
        'field_from_company_col',
        'field_to_company_col',
        'field_credit_to_payroll_col',
        'field_issue_check_col',
        'field_carf_no_col',
        'field_pcv_no_col',
        'field_gross_amount_col',
        'field_vatable_col',
        'field_vat_amount_col',
        'field_withholding_tax_col',
        'field_amount_withhold_col',
        'field_net_payable_col'
      ],

      hideWhenErl: [
        'field_reference_number_col',
        'field_po_number_col',
        'field_due_date_col', 
        'field_cash_advance_col',
        'field_form_of_payment_col',
        'field_form_of_payment_extra_col',
        'field_budget_consumption_col'
      ],

      hideWhenErgr: [
        'field_reference_number_2_col',
        'field_po_number_col',
        'field_due_date_col',
        'field_cash_advance_col', 
        'field_form_of_payment_col',
        'field_form_of_payment_extra_col',
        'field_budget_consumption_col',
        'field_reference_number_col',
        'field_date_col',
        'field_amount2_col',
        'field_from_company_col',
        'field_to_company_col',
        'field_credit_to_payroll_col',
        'field_issue_check_col'
      ],

      hideInitially: [
        'field_category_col',
        'field_attachment_col',
        'field_description_col',
        'field_amount_col',
        'field_reference_number_col',
        'field_reference_number_2_col',
        'field_po_number_col',
        'field_due_date_col',
        'field_cash_advance_col',
        'field_form_of_payment_col',
        'field_form_of_payment_extra_col', 
        'field_budget_consumption_col',
        'field_date_col',
        'field_amount2_col',
        'field_payee_col',
        'field_amount_figures_col',
        'field_amount_words_col',
        'field_payment_for_col',
        'field_special_instruction_col',
        'field_supporting_document_col',
        'field_from_company_col',
        'field_to_company_col',
        'field_credit_to_payroll_col',
        'field_issue_check_col'
      ]
    },

    // Number to words conversion data removed - no longer needed
  };

  // Global utility: strip currency symbols/commas so numeric parsing works even before
  // currency formatting is initialized
  function stripCurrency(val) {
    return (val || '').toString().replace(/[^0-9.]/g, '');
  }

  // Global utility: detect if this is a view-only page
  function isViewOnlyPage() {
    return !!document.querySelector('#view-form');
  }

  // Global utility: protect view-only fields from modification
  function protectViewOnlyFields() {
    if (!isViewOnlyPage()) return;
    
    // Protect amount fields from any modification
    var amountFigures = document.getElementById('amount_figures');
    var amountWords = document.getElementById('amount_words');
    
    if (amountFigures) {
      // Store original value
      if (!amountFigures.dataset.originalValue) {
        amountFigures.dataset.originalValue = amountFigures.value;
      }
      // Restore original value if it was changed
      if (amountFigures.value !== amountFigures.dataset.originalValue) {
        amountFigures.value = amountFigures.dataset.originalValue;
      }
    }
    
    if (amountWords) {
      // Store original value
      if (!amountWords.dataset.originalValue) {
        amountWords.dataset.originalValue = amountWords.value;
      }
      // Restore original value if it was changed
      if (amountWords.value !== amountWords.dataset.originalValue) {
        amountWords.value = amountWords.dataset.originalValue;
      }
    }
  }

  // Override currency formatting functions for view-only pages
  function overrideCurrencyFunctions() {
    if (!isViewOnlyPage()) return;
    
    // Override the applyPrefix function to do nothing on view-only pages
    window.applyPrefix = function(input) {
      // Do nothing - preserve original values
      return;
    };
    
    // Override the normalizeToRaw function to do nothing on view-only pages
    window.normalizeToRaw = function(input) {
      // Do nothing - preserve original values
      return;
    };
    
    // Override the stripCurrency function to return original value on view-only pages
    window.stripCurrency = function(val) {
      return val || '';
    };
    
    // Override the formatValue function to return original value on view-only pages
    window.formatValue = function(raw) {
      return raw || '';
    };
    
    // Override the numberWithCommas function to return original value on view-only pages
    window.numberWithCommas = function(x) {
      return x || '';
    };
  }

  // Lock amount fields on view-only pages to prevent any modification
  function lockAmountFields() {
    if (!isViewOnlyPage()) return;
    
    var amountFigures = document.getElementById('amount_figures');
    var amountWords = document.getElementById('amount_words');
    
    if (amountFigures) {
      // Store original value
      if (!amountFigures.dataset.originalValue) {
        amountFigures.dataset.originalValue = amountFigures.value;
      }
      
      // Make field completely read-only
      amountFigures.setAttribute('readonly', 'readonly');
      amountFigures.style.backgroundColor = '#f5f5f5';
      amountFigures.style.color = '#333';
      
      // Override any setter attempts
      Object.defineProperty(amountFigures, 'value', {
        get: function() {
          return this.dataset.originalValue || '';
        },
        set: function(val) {
          // Ignore any attempts to change the value
        }
      });
    }
    
    if (amountWords) {
      // Store original value
      if (!amountWords.dataset.originalValue) {
        amountWords.dataset.originalValue = amountWords.value;
      }
      
      // Make field completely read-only
      amountWords.setAttribute('readonly', 'readonly');
      amountWords.style.backgroundColor = '#f5f5f5';
      amountWords.style.color = '#333';
      
      // Override any setter attempts
      Object.defineProperty(amountWords, 'value', {
        get: function() {
          return this.dataset.originalValue || '';
        },
        set: function(val) {
          // Ignore any attempts to change the value
        }
      });
    }
  }

    // Initialize form when DOM is loaded
  function initializeForm() {
    var isEditMode = !!document.querySelector('input[name="__edit_id"]');
    var isViewOnly = isViewOnlyPage();
    
    // On view-only pages, apply field visibility once and exit
    if (isViewOnly) {
      try { applyFieldVisibilityForCurrentType(); } catch (e) {}
      return;
    }
    
    // Don't hide sections initially - let them be visible
    // hideSectionsInitially(); // REMOVED THIS LINE
    
    initializeRequestTypeHandler();
    initializeItemManagement();
    initializeFormValidation();
    initializeTamperPrevention();
    // Ensure default currency is PHP at startup
    try {
      var selCur = document.getElementById('currency');
      if (selCur && (!selCur.value || selCur.value === '')) {
        selCur.value = 'PHP';
      }
    } catch (e) {}
    
    // Add one initial item row for better UX (skip on edit to preserve existing values)
    if (!isEditMode) {
      setTimeout(function() {
        addNewItem();
      }, 500);
    }
    
    // Apply initial field visibility based on current request type (empty initially)
    applyFieldVisibilityForCurrentType();
    
    // Ensure buttons always add a new row on every click
    var addItemBtn = document.getElementById('addItemBtn');
    if (addItemBtn) {
      addItemBtn.removeAttribute('disabled');
      addItemBtn.addEventListener('click', function(e){ e.preventDefault(); addNewItem(); });
    }
    
    initializeCurrencyFormatting();
  }

  // Apply field visibility for current request type
  function applyFieldVisibilityForCurrentType() {
    var select = document.getElementById('requestType');
    var current = (select && select.value) || '';
    var isViewOnly = isViewOnlyPage();
    
    // Apply even on view-only pages
    try { applyFieldVisibility(current); } catch (e) {}
  }

  // Handle request type changes and field visibility
  function initializeRequestTypeHandler() {
    var select = document.getElementById('requestType');
    var label = document.getElementById('doc_number_label');
    var input = document.getElementById('doc_number');
    var isResettingOnTypeChange = false;

    function resetFormLikeResetButton() {
      var form = document.getElementById('demo-form2');
      if (!form) return;

      var currentType = select ? select.value : '';
      var currentCompany = document.getElementById('company') ? document.getElementById('company').value : '';
      var currentDepartment = document.getElementById('cost_center') ? document.getElementById('cost_center').value : '';

      // Perform a native reset to initial defaults
      form.reset();

      // Preserve the newly selected request type
      if (select) select.value = currentType;

      // Explicitly clear header/footer values to ensure a full reset
      try {
        var fields = form.querySelectorAll('input, select, textarea');
        for (var i = 0; i < fields.length; i++) {
          var el = fields[i];
          if (el.name === 'request_type') continue; // keep selection
          if (el.name === 'company') continue; // preserve company selection
          if (el.name === 'cost_center') continue; // preserve department selection
          if (el.type === 'hidden') continue; // keep hidden tokens/timestamps
          // Do not clear the payee field
          if (el.name === 'payee' || el.id === 'payee') continue;
          // Do not clear the doc_date field - preserve today's date
          if (el.name === 'doc_date' || el.id === 'doc_date') continue;
          if (el.tagName === 'SELECT') {
            el.value = '';
          } else if (el.type === 'checkbox' || el.type === 'radio') {
            el.checked = false;
          } else if (el.type === 'file') {
            try { el.value = ''; } catch (e) {}
          } else {
            el.value = '';
          }
        }
      } catch (e) {}

      // Restore company and department selections
      if (currentCompany && document.getElementById('company')) {
        document.getElementById('company').value = currentCompany;
      }
      if (currentDepartment && document.getElementById('cost_center')) {
        document.getElementById('cost_center').value = currentDepartment;
      }
      
      // Restore doc_date to today's date
      var docDateField = document.getElementById('doc_date');
      if (docDateField) {
        var today = new Date().toISOString().split('T')[0]; // YYYY-MM-DD format
        docDateField.value = today;
      }

      // Re-apply department filtering if the global function exists
      if (typeof window.filterDepartments === 'function') {
        window.filterDepartments();
      }

      // Clear dynamic sections and add a fresh item row
      var itemsContainer = document.getElementById('itemsContainer');
      if (itemsContainer) itemsContainer.innerHTML = '';

      addNewItem();

      // Trigger currency adorners/symbol refresh if available
      try {
        var currencySelect = document.getElementById('currency');
        if (currencySelect) {
          if (!currencySelect.value) { currencySelect.value = 'PHP'; }
          var evt = document.createEvent('HTMLEvents');
          evt.initEvent('change', true, false);
          currencySelect.dispatchEvent(evt);
        }
      } catch (e) {}

      // Re-apply visibility based on current type after reset
      try {
        var evt2 = document.createEvent('HTMLEvents');
        evt2.initEvent('change', true, false);
        select.dispatchEvent(evt2);
      } catch (e) {}
    }

    function updateDocNumberFields() {
      var isEditMode = !!document.querySelector('input[name="__edit_id"]');
      if (!select || !label || !input) return;
      
      var value = select.value;

      // Reset the form contents when request type changes (like pressing Reset)
      if (!isResettingOnTypeChange) {
        if (!isEditMode) {
          isResettingOnTypeChange = true;
          resetFormLikeResetButton();
          isResettingOnTypeChange = false;
        }
      }

      // Update label and placeholder based on request type
      switch (value) {
        case 'ERGR':
          label.textContent = 'Doc Number (ERGR)';
          input.placeholder = 'Enter ERGR document number';
          break;
        case 'ERL':
          label.textContent = 'Doc Number (ERL)';
          input.placeholder = 'Enter ERL document number';
          break;
        case 'RFP':
          label.textContent = 'Doc Number (RFP)';
          input.placeholder = 'Enter RFP document number';
          break;
        default:
          label.textContent = 'Doc Number';
          input.placeholder = 'Enter document number';
      }

      // Auto-generate Doc Type from requestType selection
      try {
        var docTypeInput = document.getElementById('doc_type');
        if (docTypeInput) {
          docTypeInput.value = value || '';
          docTypeInput.setAttribute('readonly', 'readonly');
        }
      } catch (e) {}

      // Client-side doc number pattern: RFP-<companyId><departmentId><yymmddHHMM>
      try {
        var isEdit = !!document.querySelector('input[name="__edit_id"]');
        if (!isEdit) {
          var companySel = document.getElementById('company');
          var deptSel = document.getElementById('cost_center');
          var docNumInput = document.getElementById('doc_number');
          function pad2(n){ return (n<10?'0':'')+n; }
          function formatYYMMDDHHmm(d){ var yy=(''+d.getFullYear()).slice(-2); return yy+pad2(d.getMonth()+1)+pad2(d.getDate())+pad2(d.getHours())+pad2(d.getMinutes()); }
          if (companySel && docNumInput) {
            var compOpt = (companySel.selectedIndex >= 0) ? companySel.options[companySel.selectedIndex] : null;
            var deptOpt = (deptSel && deptSel.selectedIndex >= 0) ? deptSel.options[deptSel.selectedIndex] : null;
            var cc = compOpt ? (compOpt.getAttribute('data-company-id') || '') : '';
            var dc = deptOpt ? (deptOpt.getAttribute('data-department-id') || '') : '';
            if (value && cc) {
              docNumInput.value = value + '-' + cc + dc + formatYYMMDDHHmm(new Date());
            }
          }
        }
      } catch (e) {}

      // Show/hide Advanced section based on request type (only ERL shows Advanced)
      // Skip this logic for view-only pages
      var isViewOnly = !!document.querySelector('#view-form');
      if (!isViewOnly) {
        var advancedSection = document.getElementById('advanced_section');
        if (advancedSection) {
          advancedSection.style.display = (value === 'ERL') ? 'block' : 'none';
        }
      }
        
      // Ensure there's at least one item row when form is active
      var itemsContainer = document.getElementById('itemsContainer');
      if (itemsContainer && itemsContainer.children.length === 0) {
        addNewItem();
      }

      // Apply field visibility rules
      applyFieldVisibility(value);
    }

    function applyFieldVisibility(requestType) {
      var allFields = getAllFieldIds();
      var isViewOnly = isViewOnlyPage();
      
      // Apply even on view-only pages
      allFields.forEach(function(fieldId) {
        var shouldHide = computeShouldHideField(fieldId, requestType);
        applyVisibilityToField(fieldId, shouldHide);
      });
    }

    function getAllFieldIds() {
      var allIds = new Set();
      Object.keys(FinancialForm.fieldVisibility).forEach(function(key) {
        FinancialForm.fieldVisibility[key].forEach(function(id) {
          allIds.add(id);
        });
      });
      return Array.from(allIds);
    }

    function computeShouldHideField(fieldId, requestType) {
      if (requestType === '') {
        return FinancialForm.fieldVisibility.hideInitially.indexOf(fieldId) !== -1;
      }
      if (requestType === 'RFP') {
        return FinancialForm.fieldVisibility.hideWhenRfp.indexOf(fieldId) !== -1;
      }
      if (requestType === 'ERL') {
        return FinancialForm.fieldVisibility.hideWhenErl.indexOf(fieldId) !== -1;
      }
      if (requestType === 'ERGR') {
        return FinancialForm.fieldVisibility.hideWhenErgr.indexOf(fieldId) !== -1;
      }
      return false;
    }

    function applyVisibilityToField(fieldId, shouldHide) {
      var elements = getElementsForField(fieldId);
      elements.forEach(function(element) {
        element.style.display = shouldHide ? 'none' : '';
        disableFormElements(element, shouldHide);
      });
    }

    function getElementsForField(fieldId) {
      var elements = [];
      
      // By ID
      var elById = document.getElementById(fieldId);
      if (elById) elements.push(elById);
      
      // By class
      var elsByClass = document.querySelectorAll('.' + fieldId);
      elsByClass.forEach(function(el) {
        elements.push(el);
      });
      
      return elements;
    }

    function disableFormElements(container, disable) {
      var inputs = container.querySelectorAll('input, select, textarea');
      inputs.forEach(function(input) {
        input.disabled = disable;
        if (disable) {
          if (input.type === 'checkbox' || input.type === 'radio') {
            input.checked = false;
          }
          if (input.type === 'file') {
            try {
              input.value = '';
            } catch (e) {
              // Some browsers don't allow clearing file inputs
            }
          }
        }
      });
    }

    // Global function for newly added rows
    window.applyVisibilityForCurrentRequestType = function(scopeElement) {
      var select = document.getElementById('requestType');
      var currentValue = (select && select.value) || '';
      var allFields = getAllFieldIds();
      var isViewOnly = !!document.querySelector('#view-form');
      
      // Skip field visibility logic for view-only pages
      if (isViewOnly) return;
      
      allFields.forEach(function(fieldId) {
        var scopedElements = [];
        var byClass = scopeElement.querySelectorAll('.' + fieldId);
        var byId = scopeElement.querySelectorAll('#' + fieldId);
        
        byClass.forEach(function(el) { scopedElements.push(el); });
        byId.forEach(function(el) { scopedElements.push(el); });
        
        if (scopedElements.length > 0) {
          var shouldHide = computeShouldHideField(fieldId, currentValue);
          scopedElements.forEach(function(element) {
            element.style.display = shouldHide ? 'none' : '';
            disableFormElements(element, shouldHide);
          });
        }
      });
    };

    if (select) {
      select.addEventListener('change', updateDocNumberFields);
      // Also update when company or department changes
      var companySel2 = document.getElementById('company');
      var deptSel2 = document.getElementById('cost_center');
      if (companySel2) companySel2.addEventListener('change', updateDocNumberFields);
      if (deptSel2) deptSel2.addEventListener('change', updateDocNumberFields);
      
      // Only initialize field visibility for edit forms, not view-only pages
      var isViewOnly = !!document.querySelector('#view-form');
      if (!isViewOnly) {
        updateDocNumberFields(); // Initialize
      }
    }
  }

  // Amount to words conversion removed - no longer needed
  function initializeAmountToWords() {
    // Functionality removed as requested
  }

  function initializeCurrencyFormatting() {
    var currencySelect = document.getElementById('currency');
    var currencySymbols = {
      'USD': '$',
      'EUR': '€',
      'JPY': '¥',
      'GBP': '£',
      'AUD': 'A$',
      'CAD': 'C$',
      'CHF': 'CHF',
      'CNY': '¥',
      'PHP': '₱'
    };

    function getSymbol() {
      var val = currencySelect ? currencySelect.value : '';
      return currencySymbols[val] || '';
    }

    function formatValue(raw) {
      if (raw == null) return '';
      var num = parseFloat(stripCurrency(String(raw)));
      if (isNaN(num)) return '';
      return numberWithCommas(num.toFixed(2));
    }

    function numberWithCommas(x) {
      var parts = x.split('.');
      parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, ',');
      return parts.join('.');
    }

    function localStripCurrency(val) {
      return (val || '').toString().replace(/[^0-9.]/g, '');
    }

    window.stripCurrency = localStripCurrency;

    function applyPrefix(input) {
      var isViewOnly = !!document.querySelector('#view-form');
      // Skip currency formatting for view-only pages to preserve field values
      if (isViewOnly) return;
      
      var sym = getSymbol();
      var clean = localStripCurrency(input.value);
      input.value = (sym ? (sym + ' ') : '') + formatValue(clean);
    }

    function normalizeToRaw(input) {
      var isViewOnly = !!document.querySelector('#view-form');
      // Skip currency normalization for view-only pages to preserve field values
      if (isViewOnly) return;
      
      input.value = localStripCurrency(input.value);
    }

    function handleInput(e) {
      var isViewOnly = !!document.querySelector('#view-form');
      // Skip input handling for view-only pages to preserve field values
      if (isViewOnly) return;
      
      var input = e.target;
      var start = input.selectionStart;
      normalizeToRaw(input);
      var raw = input.value;
      if (raw === '') return;
      // allow typing decimal
      if (!/^\d*(?:\.\d{0,2})?$/.test(raw)) {
        raw = raw.replace(/[^\d.]/g, '');
        var parts = raw.split('.');
        raw = parts[0];
        if (parts.length > 1) raw += '.' + parts[1].slice(0, 2);
      }
      input.value = raw;
      applyPrefix(input);
      try { input.setSelectionRange(start, start); } catch (e) {}
    }

    function handleBlur(e) {
      var isViewOnly = !!document.querySelector('#view-form');
      // Skip blur handling for view-only pages to preserve field values
      if (isViewOnly) return;
      
      var input = e.target;
      if (input.value === '') return;
      applyPrefix(input);
    }

    function handleFocus(e) {
      var isViewOnly = !!document.querySelector('#view-form');
      // Skip focus handling for view-only pages to preserve field values
      if (isViewOnly) return;
      
      var input = e.target;
      normalizeToRaw(input);
    }

    function bindCurrencyInputs(scope) {
      var inputs = (scope || document).querySelectorAll('.currency-field');
      for (var i = 0; i < inputs.length; i++) {
        var el = inputs[i];
        el.addEventListener('input', handleInput);
        el.addEventListener('blur', handleBlur);
        el.addEventListener('focus', handleFocus);
        // initialize display
        if (el.value) applyPrefix(el);
      }
    }

    // Only bind currency inputs for edit forms, not view-only pages
    var isViewOnly = !!document.querySelector('#view-form');
    if (!isViewOnly) {
      bindCurrencyInputs(document);
    }

    // Add adorners for numeric inputs that cannot contain symbols/commas
    var adornerStyleId = 'currency-adornment-style';
    if (!document.getElementById(adornerStyleId)) {
      var style = document.createElement('style');
      style.id = adornerStyleId;
      style.textContent = '.currency-adornment-wrap{position:relative;} .currency-adornment{position:absolute; left:8px; top:50%; transform:translateY(-50%); pointer-events:none; color:#555;} .currency-adornment-input{padding-left:28px !important;}';
      document.head.appendChild(style);
    }

    function ensureAdorner(input) {
      if (!input || input.dataset.currencyAdorned === '1') return;
      var wrap = document.createElement('div');
      wrap.className = 'currency-adornment-wrap';
      input.parentNode.insertBefore(wrap, input);
      wrap.appendChild(input);
      input.classList.add('currency-adornment-input');
      var span = document.createElement('span');
      span.className = 'currency-adornment';
      span.textContent = getSymbol() ? (getSymbol() + ' ') : '';
      wrap.appendChild(span);
      input.dataset.currencyAdorned = '1';
    }

    function updateAdorners(scope) {
      var selectorList = ['#balance', '#budget', '#amount_figures', 'input[name="items_amount[]"]', 'input[name="items_budget_consumption[]"]', 'input[name="break_amount2[]"]'];
      var root = scope || document;
      var isViewOnly = !!document.querySelector('#view-form');
      
      // Skip currency adornments for view-only pages to preserve field values
      if (isViewOnly) return;
      
      for (var s = 0; s < selectorList.length; s++) {
        var nodes = root.querySelectorAll(selectorList[s]);
        for (var i = 0; i < nodes.length; i++) {
          ensureAdorner(nodes[i]);
          var parent = nodes[i].parentNode;
          if (parent && parent.classList && parent.classList.contains('currency-adornment-wrap')) {
            var span = parent.querySelector('.currency-adornment');
            if (span) span.textContent = getSymbol() ? (getSymbol() + ' ') : '';
          }
        }
      }
    }

    // Only apply currency adornments for edit forms, not view-only pages
    var isViewOnly = !!document.querySelector('#view-form');
    if (!isViewOnly) {
      updateAdorners(document);
    }

    if (currencySelect) {
      currencySelect.addEventListener('change', function() {
        var isViewOnly = !!document.querySelector('#view-form');
        // Skip currency formatting for view-only pages
        if (isViewOnly) return;
        
        // re-apply symbol to all currency fields when currency changes
        var all = document.querySelectorAll('.currency-field');
        for (var i = 0; i < all.length; i++) {
          applyPrefix(all[i]);
        }
        // update adorners for numeric inputs
        updateAdorners(document);
      });
    }

    // Re-bind for dynamically added rows
    var itemsContainer = document.getElementById('itemsContainer');
    if (itemsContainer) {
      itemsContainer.addEventListener('DOMNodeInserted', function(e) {
        var isViewOnly = !!document.querySelector('#view-form');
        // Skip currency formatting for view-only pages
        if (isViewOnly) return;
        
        if (e.target && e.target.querySelectorAll) {
          bindCurrencyInputs(e.target);
          updateAdorners(e.target);
        }
      });
    }
  }

  // Utility: find nearest ancestor by class without relying on Element.closest
  function getClosestByClass(element, className) {
    var current = element;
    while (current && current !== document) {
      if (current.classList && current.classList.contains(className)) {
        return current;
      }
      current = current.parentElement;
    }
    return null;
  }

  // Handle dynamic item management
  function initializeItemManagement() {
    var addBtn = document.getElementById('addItemBtn');
    if (addBtn && !addBtn.dataset.ffBound) {
      addBtn.addEventListener('click', function(e){ e.preventDefault(); addNewItem(); });
      try { addBtn.dataset.ffBound = '1'; } catch (e) {}
    }

    // Event delegation for remove buttons (covers all current and future rows)
    var itemsContainer = document.getElementById('itemsContainer');
    if (itemsContainer) {
      itemsContainer.addEventListener('click', function(event) {
        var target = event.target;
        if (!target) return;
        // Traverse up to the remove button if an inner icon was clicked
        var removeButton = target.classList && target.classList.contains('remove-item')
          ? target
          : getClosestByClass(target, 'remove-item');
        if (removeButton) {
          event.preventDefault();
          removeItemRow(removeButton);
        }
      });
      try { itemsContainer.dataset.ffBound = '1'; } catch (e) {}
    }
  }

  function addNewItem() {
    var template = document.getElementById('itemRowTemplate');
    var container = document.getElementById('itemsContainer');
    
    if (!template || !container) {
      return;
    }

    var prevScrollY = window.pageYOffset || document.documentElement.scrollTop || 0;

    var clone = document.importNode(template.content, true);
    var itemRow = clone.querySelector('.item-row');
    var removeBtn = clone.querySelector('.remove-item');

    if (removeBtn) {
      removeBtn.addEventListener('click', function() {
        removeItemRow(this);
      });
    }

    container.appendChild(clone);

    // Apply visibility rules to new row and enforce current request type
    var isViewOnly = !!document.querySelector('#view-form');
    if (!isViewOnly) {
      if (window.applyVisibilityForCurrentRequestType && itemRow) {
        window.applyVisibilityForCurrentRequestType(itemRow);
      }
      try {
        var currentType = (document.getElementById('requestType') || {}).value || '';
        if (currentType) { applyFieldVisibility(currentType); }
      } catch (e) {}
    }
    
    // Preserve scroll position
    try { window.scrollTo(0, prevScrollY); } catch (e) {}
    
    // REMOVED: computeTotalAmount() call - no longer auto-computing
  }

  function removeItemRow(button) {
    var row = getClosestByClass(button, 'item-row');
    if (row && row.parentNode) {
      row.parentNode.removeChild(row);
    }
    // REMOVED: computeTotalAmount() call - no longer auto-computing
  }


  // REMOVED: computeTotalAmount function entirely - amounts now come from database only

  // Form validation and submission
  function initializeFormValidation() {
    var form = document.getElementById('demo-form2');
    if (!form) return;
    form.addEventListener('submit', function(e) {
      e.preventDefault();
      submitViaAjax();
    });
  }

  function submitViaAjax() {
    var submitBtn = document.getElementById('submitBtn');
    if (submitBtn && submitBtn.disabled) return;

    // Basic and custom validations
    var form = document.getElementById('demo-form2');
    if (!form) return;

    if (submitBtn) {
      submitBtn.disabled = true;
      submitBtn.innerHTML = 'Submitting...';
    }

    if (form && !form.checkValidity()) {
      resetSubmitButton();
      return;
    }
    if (!validateFormData()) {
      resetSubmitButton();
      return;
    }
    if (!checkSubmissionRateLimit()) {
      resetSubmitButton();
      return;
    }

    try { localStorage.setItem('lastFormSubmission', Date.now().toString()); } catch (e) {}

    var formData = new FormData(form);
    // Flag to expect JSON
    formData.append('__expect_json', '1');
    var submitUrl = (form.getAttribute('action') || 'save_financial_request.php');
    fetch(submitUrl, {
      method: 'POST',
      body: formData,
      credentials: 'same-origin'
    }).then(function(resp) {
      var contentType = resp.headers.get('content-type') || '';
      if (contentType.indexOf('application/json') !== -1) {
        return resp.json().then(function(data){ return { ok: resp.ok, status: resp.status, data: data }; });
      }
      return resp.text().then(function(text){ return { ok: resp.ok, status: resp.status, text: text }; });
    }).then(function(res) {
      if (res.ok && res.data && res.data.success) {
        var newId = res.data.id;
        Swal.fire({
          icon: 'success',
          title: 'Saved',
          text: 'Financial request has been saved successfully.',
          confirmButtonText: 'View'
        }).then(function(){
          window.location.href = 'disbursement_view.php?id=' + encodeURIComponent(String(newId));
        });
      } else {
        var message = (res && res.data && res.data.message) || (res && res.text) || 'Save failed';
        Swal.fire({ icon: 'error', title: 'Error', text: message });
        resetSubmitButton();
      }
    }).catch(function(err) {
      Swal.fire({ icon: 'error', title: 'Network Error', text: 'Please try again.' });
      resetSubmitButton();
    });
  }

  // Global validation function (called by form submit button)
  window.validateAndSubmitForm = function() {
    var submitBtn = document.getElementById('submitBtn');
    
    if (submitBtn && submitBtn.disabled) {
      return false;
    }

    // Disable submit button to prevent double submission
    if (submitBtn) {
      submitBtn.disabled = true;
      submitBtn.innerHTML = 'Submitting...';
    }

    var form = document.getElementById('demo-form2');
    
    // Check basic form validity
    if (form && !form.checkValidity()) {
      resetSubmitButton();
      return false;
    }

    // Custom validation
    if (!validateFormData()) {
      resetSubmitButton();
      return false;
    }

    // Rate limiting check
    if (!checkSubmissionRateLimit()) {
      resetSubmitButton();
      return false;
    }

    // Store last submission time
    try {
      localStorage.setItem('lastFormSubmission', Date.now().toString());
    } catch (e) {
      // localStorage might not be available
    }

    return true;
  };

  function resetSubmitButton() {
    var submitBtn = document.getElementById('submitBtn');
    if (submitBtn) {
      submitBtn.disabled = false;
      submitBtn.innerHTML = 'Submit';
    }
  }

  function validateFormData() {
    var requestType = document.getElementById('requestType');
    if (!requestType || !requestType.value) {
      alert('Please select a request type.');
      if (requestType) requestType.focus();
      return false;
    }

    var requestTypeValue = requestType.value;
    var requiredFields = getRequiredFieldsForType(requestTypeValue);

    // Check required fields
    for (var i = 0; i < requiredFields.length; i++) {
      var fieldId = requiredFields[i];
      var field = document.getElementById(fieldId);
      
      if (field && field.style.display !== 'none' && !field.disabled) {
        if (!field.value || !field.value.trim()) {
          alert('Please fill in all required fields: ' + getFieldLabel(fieldId));
          field.focus();
          return false;
        }
      }
    }

    // Amount validation removed - no longer needed

    return true;
  }

  function getRequiredFieldsForType(requestType) {
    // 'doc_number' is generated server-side; do NOT require it on the client
    var commonFields = ['company', 'doc_type', 'doc_date', 'expenditure_type', 'currency', 'cost_center'];
    
    switch (requestType) {
      case 'RFP':
        return commonFields.concat(['payee', 'payment_for']); // reference_number removed from required
      case 'ERL':
        return commonFields; // ERL uses item-level fields
      case 'ERGR':
        return commonFields.concat(['payee', 'payment_for']);
      default:
        return commonFields;
    }
  }

  function getFieldLabel(fieldId) {
    var field = document.getElementById(fieldId);
    if (field) {
      var label = field.parentNode.querySelector('label');
      if (label) {
        return label.textContent || label.innerText || fieldId;
      }
    }
    return fieldId;
  }

  function checkSubmissionRateLimit() {
    try {
      var lastSubmission = localStorage.getItem('lastFormSubmission');
      var currentTime = Date.now();
      
      if (lastSubmission && (currentTime - parseInt(lastSubmission)) < FinancialForm.config.submissionCooldown) {
        alert('Please wait ' + (FinancialForm.config.submissionCooldown / 1000) + ' seconds before submitting another request.');
        return false;
      }
    } catch (e) {
      // localStorage might not be available, allow submission
    }
    
    return true;
  }

  // Light tamper prevention - COMMENTED OUT FOR DEVELOPMENT
  // function initializeTamperPrevention() {
  //   var form = document.getElementById('demo-form2');
    
  //   // Disable right-click on form
  //   if (form) {
  //     form.addEventListener('contextmenu', function(e) {
  //       e.preventDefault();
  //       return false;
  //     });
  //   }

  //   // Disable certain keyboard shortcuts
  //   document.addEventListener('keydown', function(e) {
  //     // F12, Ctrl+Shift+I, Ctrl+U
  //     if (e.key === 'F12' || 
  //         (e.ctrlKey && e.shiftKey && e.key === 'I') || 
  //         (e.ctrlKey && e.key === 'u')) {
  //       e.preventDefault();
  //       return false;
  //     }
  //   });
  // }

  // Initialize everything when DOM is ready
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initializeForm);
  } else {
    initializeForm();
  }

})();