# Withholding Taxes Feature

## Overview
This feature adds dynamic withholding tax management to the disbursement system. Users can now select from predefined tax codes or add new ones on the fly.

## Features
- ✅ Dynamic dropdown with tax codes loaded from database
- ✅ Pre-configured tax codes (WI011, WC100, WC158, WC160, WC516, etc.)
- ✅ Ability to add new tax codes via modal
- ✅ Automatic tax calculation based on configured rates
- ✅ Support for both Withholding Tax and Final Tax types

## Installation

### Step 1: Create the Database Table
Run the SQL script to create the `withholding_taxes` table:

```sql
-- Open your MySQL client (phpMyAdmin, MySQL Workbench, or command line)
-- Select your database (e.g., techsources)
-- Run the following script:

SOURCE /path/to/dmt-rfp/db/withholding_taxes.sql;

-- OR copy and paste the contents of withholding_taxes.sql and execute
```

**Alternative Method (via phpMyAdmin):**
1. Open phpMyAdmin
2. Select your database (`techsources` or your project database)
3. Click on "Import" tab
4. Choose file: `dmt-rfp/db/withholding_taxes.sql`
5. Click "Go" to execute

### Step 2: Verify Installation
After running the SQL script, verify the table was created:

```sql
-- Check if table exists
SHOW TABLES LIKE 'withholding_taxes';

-- View the data
SELECT * FROM withholding_taxes;
```

You should see 13 records including:
- NONE (0%)
- WI011 - 15% PROFESSIONALS/TALENT FEES
- WC100 - 5% RENTALS
- WC158 - 1% GOODS
- WC160 - 2% SERVICES
- WC516 - 15% COMM, REBATES, DISCOUNTS
- And other tax codes

## Usage

### For End Users

#### Selecting a Tax Code
1. Navigate to the disbursement form (create or edit)
2. In the "Item Details" section, find the "Withholding Tax/Final Tax" dropdown
3. Select from the available tax codes
4. The tax amount will be calculated automatically based on the gross amount

#### Adding a New Tax Code
1. Click the **"+ Add"** button next to the Withholding Tax dropdown
2. Fill in the modal form:
   - **Tax Code**: Use uppercase letters and numbers (e.g., WC161, WI012)
   - **Tax Name**: Brief description (e.g., "CONSULTING SERVICES")
   - **Tax Rate**: Enter percentage (e.g., 10 for 10%)
   - **Tax Type**: Select "Withholding Tax" or "Final Tax"
   - **Description**: Optional detailed description
3. Click **"Save Tax"**
4. The new tax code will be immediately available in the dropdown

### For Administrators

#### Managing Tax Codes
To manage existing tax codes, you can:

1. **View all tax codes:**
```sql
SELECT tax_code, tax_name, tax_rate, tax_type, is_active 
FROM withholding_taxes 
ORDER BY tax_code;
```

2. **Deactivate a tax code:**
```sql
UPDATE withholding_taxes 
SET is_active = 0 
WHERE tax_code = 'WC100';
```

3. **Update tax rate:**
```sql
UPDATE withholding_taxes 
SET tax_rate = 12.00 
WHERE tax_code = 'WI011';
```

4. **Add tax code manually:**
```sql
INSERT INTO withholding_taxes 
(tax_code, tax_name, tax_rate, tax_type, description, is_active) 
VALUES 
('WC170', 'ADVERTISING SERVICES', 3.00, 'withholding', '3% Withholding Tax on Advertising', 1);
```

## Database Schema

### Table: `withholding_taxes`

| Column | Type | Description |
|--------|------|-------------|
| id | INT UNSIGNED | Primary key, auto-increment |
| tax_code | VARCHAR(50) | Unique tax code (e.g., WI011) |
| tax_name | VARCHAR(200) | Tax name/description |
| tax_rate | DECIMAL(5,2) | Tax rate in percentage (e.g., 15.00) |
| tax_type | ENUM | 'withholding' or 'final' |
| description | TEXT | Optional detailed description |
| is_active | TINYINT(1) | 1 = active, 0 = inactive |
| created_at | DATETIME | Record creation timestamp |
| updated_at | DATETIME | Last update timestamp |
| created_by | INT | User ID who created the record |

## Files Modified/Created

### New Files:
- `dmt-rfp/db/withholding_taxes.sql` - Database schema and seed data
- `dmt-rfp/modal_add_withholding_tax.php` - Modal for adding new tax codes
- `dmt-rfp/save_withholding_tax.php` - Backend handler for saving new taxes
- `dmt-rfp/db/README_WITHHOLDING_TAXES.md` - This documentation

### Modified Files:
- `dmt-rfp/disbursement_approver_edit_form.php` - Updated to load and display dynamic taxes
- `dmt-rfp/financial-form.js` - Updated tax calculation logic to use data-rate attributes

## Predefined Tax Codes

| Tax Code | Rate | Description |
|----------|------|-------------|
| NONE | 0% | No withholding tax applied |
| WI011 | 15% | Professionals/Talent Fees |
| WC100 | 5% | Rentals |
| WC158 | 1% | Goods |
| WC160 | 2% | Services |
| WC516 | 15% | Commission, Rebates, Discounts |
| EWT02 | 2% | Expanded Withholding Tax |
| FT05 | 5% | Final Tax |
| FT10 | 10% | Final Tax |
| FT15 | 15% | Final Tax |
| FT20 | 20% | Final Tax |
| FT25 | 25% | Final Tax |
| FT30 | 30% | Final Tax |

## Tax Calculation Logic

The system calculates withholding tax as follows:

```
1. Get Gross Amount (entered by user)
2. If VAT is applicable:
   - VAT-exclusive Amount = Gross Amount / 1.12
   - VAT Amount = VAT-exclusive Amount × 0.12
3. Calculate Withholding Amount:
   - Withholding Amount = VAT-exclusive Amount × (Tax Rate / 100)
4. Calculate Net Payable:
   - Net Payable = Gross Amount - Withholding Amount
```

## Troubleshooting

### Issue: Dropdown shows empty or "Choose.."
**Solution:** 
- Verify the `withholding_taxes` table exists
- Check if there are active records: `SELECT * FROM withholding_taxes WHERE is_active = 1;`
- Verify database connection in `conn/db.php`

### Issue: "Add" button doesn't open modal
**Solution:**
- Check browser console for JavaScript errors
- Verify jQuery and Bootstrap are loaded
- Ensure `modal_add_withholding_tax.php` is in the correct path

### Issue: Tax calculation not working
**Solution:**
- Verify `financial-form.js` has been updated
- Check browser console for errors
- Ensure selected option has `data-rate` attribute

### Issue: Cannot save new tax code
**Solution:**
- Check if `save_withholding_tax.php` exists and is accessible
- Verify CSRF token is being generated in the session
- Check MySQL error logs for database issues
- Ensure user has INSERT permissions on the table

## Security Notes

- ✅ CSRF token protection on form submission
- ✅ Input validation (both client-side and server-side)
- ✅ SQL injection prevention using prepared statements
- ✅ XSS protection with htmlspecialchars()
- ✅ User authentication required
- ✅ Unique tax code constraint in database

## Support

For issues or questions:
1. Check this README
2. Review browser console for JavaScript errors
3. Check PHP error logs
4. Verify database connection and permissions

## Future Enhancements

Potential features for future development:
- [ ] Edit existing tax codes via UI
- [ ] Delete/deactivate tax codes via UI
- [ ] Tax code search/filter in dropdown
- [ ] Import/export tax codes
- [ ] Audit trail for tax code changes
- [ ] Tax code grouping/categorization

