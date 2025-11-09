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
  
      // Number to words conversion data
      numberToWords: {
        'USD': {
          ones: ['', 'One', 'Two', 'Three', 'Four', 'Five', 'Six', 'Seven', 'Eight', 'Nine'],
          teens: ['Ten', 'Eleven', 'Twelve', 'Thirteen', 'Fourteen', 'Fifteen', 'Sixteen', 'Seventeen', 'Eighteen', 'Nineteen'],
          tens: ['', '', 'Twenty', 'Thirty', 'Forty', 'Fifty', 'Sixty', 'Seventy', 'Eighty', 'Ninety'],
          hundreds: 'Hundred',
          thousands: 'Thousand', 
          millions: 'Million',
          billions: 'Billion',
          currency: 'Dollars',
          cents: 'Cents'
        },
        'PHP': {
          ones: ['', 'One', 'Two', 'Three', 'Four', 'Five', 'Six', 'Seven', 'Eight', 'Nine'],
          teens: ['Ten', 'Eleven', 'Twelve', 'Thirteen', 'Fourteen', 'Fifteen', 'Sixteen', 'Seventeen', 'Eighteen', 'Nineteen'],
          tens: ['', '', 'Twenty', 'Thirty', 'Forty', 'Fifty', 'Sixty', 'Seventy', 'Eighty', 'Ninety'],
          hundreds: 'Hundred',
          thousands: 'Thousand',
          millions: 'Million', 
          billions: 'Billion',
          currency: 'Pesos',
          cents: 'Centavos'
        },
        'EUR': {
          ones: ['', 'One', 'Two', 'Three', 'Four', 'Five', 'Six', 'Seven', 'Eight', 'Nine'],
          teens: ['Ten', 'Eleven', 'Twelve', 'Thirteen', 'Fourteen', 'Fifteen', 'Sixteen', 'Seventeen', 'Eighteen', 'Nineteen'],
          tens: ['', '', 'Twenty', 'Thirty', 'Forty', 'Fifty', 'Sixty', 'Seventy', 'Eighty', 'Ninety'],
          hundreds: 'Hundred',
          thousands: 'Thousand',
          millions: 'Million',
          billions: 'Billion', 
          currency: 'Euros',
          cents: 'Cents'
        },
        'GBP': {
          ones: ['', 'One', 'Two', 'Three', 'Four', 'Five', 'Six', 'Seven', 'Eight', 'Nine'],
          teens: ['Ten', 'Eleven', 'Twelve', 'Thirteen', 'Fourteen', 'Fifteen', 'Sixteen', 'Seventeen', 'Eighteen', 'Nineteen'],
          tens: ['', '', 'Twenty', 'Thirty', 'Forty', 'Fifty', 'Sixty', 'Seventy', 'Eighty', 'Ninety'],
          hundreds: 'Hundred',
          thousands: 'Thousand',
          millions: 'Million',
          billions: 'Billion',
          currency: 'Pounds',
          cents: 'Pence'
        },
        'JPY': {
          ones: ['', 'One', 'Two', 'Three', 'Four', 'Five', 'Six', 'Seven', 'Eight', 'Nine'],
          teens: ['Ten', 'Eleven', 'Twelve', 'Thirteen', 'Fourteen', 'Fifteen', 'Sixteen', 'Seventeen', 'Eighteen', 'Nineteen'],
          tens: ['', '', 'Twenty', 'Thirty', 'Forty', 'Fifty', 'Sixty', 'Seventy', 'Eighty', 'Ninety'],
          hundreds: 'Hundred',
          thousands: 'Thousand',
          millions: 'Million',
          billions: 'Billion',
          currency: 'Yen',
          cents: 'Sen'
        }
      }
    };
  
    // Global utility: strip currency symbols/commas so numeric parsing works even before
    // currency formatting is initialized
    function stripCurrency(val) {
      return (val || '').toString().replace(/[^0-9.]/g, '');
    }

    // Initialize form when DOM is loaded
    function initializeForm() {
      var isEditMode = !!document.querySelector('input[name="__edit_id"]');
      // Don't hide sections initially - let them be visible
      // hideSectionsInitially(); // REMOVED THIS LINE
      
      initializeRequestTypeHandler();
      initializeAmountToWords();
      initializeItemManagement();
      initializeFormValidation();
      initializeTamperPrevention();
      initializeTaxCalculations();
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

      initializeCurrencyFormatting();
    }
  
    // Apply field visibility for current request type
    function applyFieldVisibilityForCurrentType() {
      var select = document.getElementById('requestType');
      if (select) {
        var evt = document.createEvent('HTMLEvents');
        evt.initEvent('change', true, false);
        select.dispatchEvent(evt);
      }
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
            if (el.type === 'hidden') continue; // keep hidden tokens/timestamps
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

        // Client-side doc number pattern: RFP-<company><dept><yy-mm-dd hh:ii>
        try {
          var isEdit = !!document.querySelector('input[name="__edit_id"]');
          if (!isEdit) {
            var companySel = document.getElementById('company');
            var deptSel = document.getElementById('cost_center');
            var docNumInput = document.getElementById('doc_number');
            var companyCodeMap = { 'TLCI':'20','MPAV':'10','MPFF':'30','MPDF':'40','UHDFI':'50','MPNAT':'60' };
            var departmentCodeMap = {
              'TLCI-IT':'10','TLCI-FIN':'20','TLCI-ACC':'30','TLCI-LOG':'40','TLCI-PR':'50','TLCI-SALES':'60','TLCI-ENGR':'70',
              'MPAV-IT':'11','MPAV-FIN':'21','MPAV-ACC':'31','MPAV-LOG':'41','MPAV-PR':'51','MPAV-SALES':'61','MPAV-ENGR':'71',
              'MPFF-IT':'12','MPFF-FIN':'22','MPFF-ACC':'32','MPFF-LOG':'42','MPFF-PR':'52','MPFF-SALES':'62','MPFF-ENGR':'72',
              'MPDF-IT':'13','MPDF-FIN':'23','MPDF-ACC':'33','MPDF-LOG':'43','MPDF-PR':'53','MPDF-SALES':'63','MPDF-ENGR':'73',
              'MPNAT-IT':'14','MPNAT-FIN':'24','MPNAT-ACC':'34','MPNAT-LOG':'44','MPNAT-PR':'54','MPNAT-SALES':'64','MPNAT-ENGR':'74',
              'UHDFI-IT':'15','UHDFI-FIN':'25','UHDFI-ACC':'35','UHDFI-LOG':'45','UHDFI-PR':'55','UHDFI-SALES':'65','UHDFI-ENGR':'75'
            };
            function pad2(n){ return (n<10?'0':'')+n; }
            function formatYYMMDDHHmm(d){ var yy=(''+d.getFullYear()).slice(-2); return yy+pad2(d.getMonth()+1)+pad2(d.getDate())+pad2(d.getHours())+pad2(d.getMinutes()); }
            if (companySel && docNumInput) {
              var c = (companySel.value||'').trim();
              var d = deptSel ? (deptSel.value||'').trim() : '';
              var cc = companyCodeMap[c] || '';
              var dc = departmentCodeMap[d] || '';
              if (value && cc) {
                docNumInput.value = value + '-' + cc + dc + formatYYMMDDHHmm(new Date());
              }
            }
          }
        } catch (e) {}
  
        // Show/hide Advanced section based on request type (only ERL shows Advanced)
        var advancedSection = document.getElementById('advanced_section');
        if (advancedSection) {
          advancedSection.style.display = (value === 'ERL') ? 'block' : 'none';
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
        updateDocNumberFields(); // Initialize
      }
    }
  
    // Handle amount to words conversion
    function initializeAmountToWords() {
      var amountFiguresInput = document.getElementById('amount_figures');
      var amountWordsInput = document.getElementById('amount_words');
      var currencySelect = document.getElementById('currency');
  
      function convertNumberToWords(num, currency) {
        if (num === 0) return 'Zero';
        
        var currencyData = FinancialForm.numberToWords[currency] || FinancialForm.numberToWords['USD'];
        var words = '';
  
        // Process billions
        if (Math.floor(num / 1000000000) > 0) {
          words += convertNumberToWords(Math.floor(num / 1000000000), currency) + ' ' + currencyData.billions + ' ';
          num %= 1000000000;
        }
  
        // Process millions
        if (Math.floor(num / 1000000) > 0) {
          words += convertNumberToWords(Math.floor(num / 1000000), currency) + ' ' + currencyData.millions + ' ';
          num %= 1000000;
        }
  
        // Process thousands
        if (Math.floor(num / 1000) > 0) {
          words += convertNumberToWords(Math.floor(num / 1000), currency) + ' ' + currencyData.thousands + ' ';
          num %= 1000;
        }
  
        // Process hundreds
        if (Math.floor(num / 100) > 0) {
          words += convertNumberToWords(Math.floor(num / 100), currency) + ' ' + currencyData.hundreds + ' ';
          num %= 100;
        }
  
        // Process remaining number
        if (num > 0) {
          if (num < 10) {
            words += currencyData.ones[num];
          } else if (num < 20) {
            words += currencyData.teens[num - 10];
          } else {
            words += currencyData.tens[Math.floor(num / 10)];
            if (num % 10 > 0) {
              words += ' ' + currencyData.ones[num % 10];
            }
          }
        }
  
        return words.trim();
      }
  
      function updateAmountWords() {
        if (!amountFiguresInput || !amountWordsInput || !currencySelect) return;
  
        var _strip = (typeof stripCurrency === 'function') ? stripCurrency : (window.stripCurrency || function(v){ return (v||'').toString().replace(/[^0-9.]/g,''); });
        var amount = parseFloat(_strip(amountFiguresInput.value));
        var currency = currencySelect.value;
  
        if (isNaN(amount) || amount <= 0) {
          amountWordsInput.value = '';
          return;
        }
  
        var wholePart = Math.floor(amount);
        var decimalPart = Math.round((amount - wholePart) * 100);
        var currencyData = FinancialForm.numberToWords[currency] || FinancialForm.numberToWords['USD'];
  
        var words = convertNumberToWords(wholePart, currency) + ' ' + currencyData.currency;
  
        if (decimalPart > 0) {
          words += ' and ' + convertNumberToWords(decimalPart, currency) + ' ' + currencyData.cents;
        }
  
        amountWordsInput.value = words;
      }
  
      if (amountFiguresInput) {
        amountFiguresInput.addEventListener('input', updateAmountWords);
        amountFiguresInput.addEventListener('change', updateAmountWords);
      }
      if (currencySelect) {
        currencySelect.addEventListener('change', updateAmountWords);
      }
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
        var sym = getSymbol();
        var clean = localStripCurrency(input.value);
        input.value = (sym ? (sym + ' ') : '') + formatValue(clean);
      }

      function normalizeToRaw(input) {
        input.value = localStripCurrency(input.value);
      }

      function handleInput(e) {
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
        var input = e.target;
        if (input.value === '') return;
        applyPrefix(input);
      }

      function handleFocus(e) {
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

      bindCurrencyInputs(document);

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



      

      if (currencySelect) {
        currencySelect.addEventListener('change', function() {
          // re-apply symbol to all currency fields when currency changes
          var all = document.querySelectorAll('.currency-field');
          for (var i = 0; i < all.length; i++) {
            applyPrefix(all[i]);
          }
          // update adorners for numeric inputs
          
        });
      }

      // Re-bind for dynamically added rows
      var itemsContainer = document.getElementById('itemsContainer');
      if (itemsContainer) {
        itemsContainer.addEventListener('DOMNodeInserted', function(e) {
          if (e.target && e.target.querySelectorAll) {
            bindCurrencyInputs(e.target);
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
      if (addBtn) {
        addBtn.addEventListener('click', addNewItem);
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
      }
    }
  
    function addNewItem() {
      var template = document.getElementById('itemRowTemplate');
      var container = document.getElementById('itemsContainer');
      
      if (!template || !container) {
        return;
      }
  
      var clone = document.importNode(template.content, true);
      var itemRow = clone.querySelector('.item-row');
      var removeBtn = clone.querySelector('.remove-item');
  
      if (removeBtn) {
        removeBtn.addEventListener('click', function() {
          removeItemRow(this);
        });
      }
  
      container.appendChild(clone);
  
      // Apply visibility rules to new row
      if (window.applyVisibilityForCurrentRequestType && itemRow) {
        window.applyVisibilityForCurrentRequestType(itemRow);
      }
  
      // Bind tax calculation events to the new item row
      if (itemRow) {
        bindTaxCalculationEvents(itemRow);
      }
  
      // Trigger change event to update visibility
      var requestTypeSelect = document.getElementById('requestType');
      if (requestTypeSelect) {
        requestTypeSelect.dispatchEvent(new Event('change'));
      }
      
      // Update total amount in figures
      try { updateTotalAmountInFigures(); } catch (e) {}
      
    }
  
    function removeItemRow(button) {
      var row = getClosestByClass(button, 'item-row');
      if (row && row.parentNode) {
        row.parentNode.removeChild(row);
      }
      try { updateTotalAmountInFigures(); } catch (e) {}
    }
  
  
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
  
      // Validate amount if present
      var amountFigures = document.getElementById('amount_figures');
      if (amountFigures && amountFigures.value && amountFigures.style.display !== 'none') {
        var amount = parseFloat(amountFigures.value);
        if (isNaN(amount) || amount <= 0) {
          alert('Please enter a valid amount greater than 0.');
          amountFigures.focus();
          return false;
        }
      }
  
      return true;
    }
  
    function getRequiredFieldsForType(requestType) {
      // 'doc_number' is generated server-side; do NOT require it on the client
      var commonFields = ['company', 'doc_type', 'doc_date', 'expenditure_type', 'currency', 'cost_center'];
      
      switch (requestType) {
        case 'RFP':
          return commonFields.concat(['payee', 'amount_figures', 'payment_for']); // reference_number removed from required
        case 'ERL':
          return commonFields; // ERL uses item-level fields
        case 'ERGR':
          return commonFields.concat(['payee', 'amount_figures', 'payment_for']);
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
    function initializeTamperPrevention() {
      var form = document.getElementById('demo-form2');
      
      // Disable right-click on form
      if (form) {
        form.addEventListener('contextmenu', function(e) {
          e.preventDefault();
          return false;
        });
      }
  
      // Disable certain keyboard shortcuts
      document.addEventListener('keydown', function(e) {
        // F12, Ctrl+Shift+I, Ctrl+U
        if (e.key === 'F12' || 
            (e.ctrlKey && e.shiftKey && e.key === 'I') || 
            (e.ctrlKey && e.key === 'u')) {
          e.preventDefault();
          return false;
        }
      });
    }

    // Tax calculation functions
    function initializeTaxCalculations() {
      // Bind tax calculation events to all existing and future tax fields
      bindTaxCalculationEvents(document.body);
      
      // Trigger calculations for existing items on page load
      setTimeout(function() {
        var existingItems = document.querySelectorAll('.item-row');
        existingItems.forEach(function(itemRow) {
          var grossAmountInput = itemRow.querySelector('[name="items_gross_amount[]"]');
          if (grossAmountInput && grossAmountInput.value) {
            calculateTaxForItem(grossAmountInput);
          }
        });
        // Update total amount in figures after loading existing items
        updateTotalAmountInFigures();
      }, 100);
    }

    function bindTaxCalculationEvents(container) {
      if (!container) return;
      
      // Bind to gross amount, vatable, and withholding tax fields
      container.addEventListener('input', function(e) {
        if (e.target.classList && e.target.classList.contains('tax-calc-field')) {
          calculateTaxForItem(e.target);
        }
      });
      
      container.addEventListener('change', function(e) {
        if (e.target.classList && e.target.classList.contains('tax-calc-field')) {
          calculateTaxForItem(e.target);
        }
      });
    }

    function calculateTaxForItem(triggerElement) {
      // Find the parent item row
      var itemRow = triggerElement.closest('.item-row');
      if (!itemRow) return;
      
      var grossAmountInput = itemRow.querySelector('[name="items_gross_amount[]"]');
      var vatableSelect = itemRow.querySelector('[name="items_vatable[]"]');
      var vatAmountInput = itemRow.querySelector('[name="items_vat_amount[]"]');
      var withholdingTaxSelect = itemRow.querySelector('[name="items_withholding_tax[]"]');
      var amountWithholdInput = itemRow.querySelector('[name="items_amount_withhold[]"]');
      var netPayableInput = itemRow.querySelector('[name="items_net_payable[]"]');
      
      if (!grossAmountInput || !vatableSelect || !vatAmountInput || 
          !withholdingTaxSelect || !amountWithholdInput || !netPayableInput) {
        return;
      }
      
      var grossAmount = parseFloat(grossAmountInput.value) || 0;
      var vatable = vatableSelect.value;
      var withholdingTax = withholdingTaxSelect.value;
      
      // Calculate VAT (12% if vatable)
      var vatAmount = 0;
      if (vatable === 'yes') {
        // VAT is calculated on the VAT-exclusive amount
        // If gross amount is VAT-inclusive, we need to extract the VAT-exclusive amount first
        var vatExclusiveAmount = grossAmount / 1.12;
        vatAmount = vatExclusiveAmount * 0.12;
      }
      vatAmountInput.value = vatAmount.toFixed(2);
      
      // Calculate withholding tax amount
      var withholdingAmount = 0;
      var withholdingRate = 0;
      
      switch (withholdingTax) {
        case 'expanded_withholding_tax':
          withholdingRate = 0.02; // 2%
          break;
        case 'final_tax_5':
          withholdingRate = 0.05; // 5%
          break;
        case 'final_tax_10':
          withholdingRate = 0.10; // 10%
          break;
        case 'final_tax_15':
          withholdingRate = 0.15; // 15%
          break;
        case 'final_tax_20':
          withholdingRate = 0.20; // 20%
          break;
        case 'final_tax_25':
          withholdingRate = 0.25; // 25%
          break;
        case 'final_tax_30':
          withholdingRate = 0.30; // 30%
          break;
        default:
          withholdingRate = 0;
      }
      
      // Withholding tax is calculated on the VAT-exclusive amount
      var vatExclusiveAmount = vatable === 'yes' ? grossAmount / 1.12 : grossAmount;
      withholdingAmount = vatExclusiveAmount * withholdingRate;
      amountWithholdInput.value = withholdingAmount.toFixed(2);
      
      // Calculate net payable amount
      // Net Payable = Gross Amount - Amount Withhold
      var netPayable = grossAmount - withholdingAmount;
      netPayableInput.value = netPayable.toFixed(2);
      
      // Update the total amount in figures
      updateTotalAmountInFigures();
    }

    // Function to update the total amount in figures based on net payable amounts
    function updateTotalAmountInFigures() {
      var totalNetPayable = 0;
      var netPayableInputs = document.querySelectorAll('[name="items_net_payable[]"]');
      
      netPayableInputs.forEach(function(input) {
        var value = parseFloat(input.value) || 0;
        totalNetPayable += value;
      });
      
      var amountFiguresInput = document.getElementById('amount_figures');
      if (amountFiguresInput) {
        amountFiguresInput.value = totalNetPayable.toFixed(2);
        console.log('Total amount in figures updated:', totalNetPayable);
        
        // Trigger amount in words update by dispatching events
        try {
          var ev1 = document.createEvent('HTMLEvents');
          ev1.initEvent('input', true, false);
          amountFiguresInput.dispatchEvent(ev1);
          
          var ev2 = document.createEvent('HTMLEvents');
          ev2.initEvent('change', true, false);
          amountFiguresInput.dispatchEvent(ev2);
        } catch (e) {
          // Fallback: try to call the function directly if it exists
          if (typeof updateAmountWords === 'function') {
            updateAmountWords();
          }
        }
      }
    }

    // Manual trigger function for testing
    window.triggerTaxCalculations = function() {
      console.log('Manually triggering tax calculations...');
      var existingItems = document.querySelectorAll('.item-row');
      existingItems.forEach(function(itemRow) {
        var grossAmountInput = itemRow.querySelector('[name="items_gross_amount[]"]');
        if (grossAmountInput) {
          calculateTaxForItem(grossAmountInput);
        }
      });
    };
  
    // Initialize everything when DOM is ready
    if (document.readyState === 'loading') {
      document.addEventListener('DOMContentLoaded', initializeForm);
    } else {
      initializeForm();
    }
  
  })();