<?php
require_once 'header.php';

$page_title = 'Import Members (Excel)';
$success = '';
$error = '';
$organisation_id = (int) $_SESSION['organisation_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['excel_data'])) {
    
    // Decode the JSON data sent from our JavaScript reader
    $excel_rows = json_decode($_POST['excel_data'], true);
    
    if (is_array($excel_rows) && count($excel_rows) > 0) {
        
        $imported_count = 0;
        $skipped_count = 0;

        // Prepare queries
        $check_email_stmt = $conn->prepare("SELECT id FROM members WHERE email = ?");
        $insert_query = "INSERT INTO members (organisation_id, member_id, first_name, last_name, email, phone_no, joining_date, original_rotary_date, address, city_state, postal_code, online_account_with_rotary, age_data_available, satellite_member) 
                         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $insert_stmt = $conn->prepare($insert_query);

        // Skip the first row (Header row)
        array_shift($excel_rows);

        foreach ($excel_rows as $row) {
            
            // Map Excel columns to variables based on the template
            $member_id = isset($row[0]) ? trim($row[0]) : '';
            $first_name = isset($row[1]) ? trim($row[1]) : '';
            $last_name = isset($row[2]) ? trim($row[2]) : '';
            $email = isset($row[3]) ? trim($row[3]) : '';
            $phone_no = isset($row[4]) ? trim($row[4]) : '';
            
            // Handle Dates
            $joining_date = !empty(trim($row[5] ?? '')) ? date('Y-m-d', strtotime(trim($row[5]))) : null;
            $original_rotary_date = !empty(trim($row[6] ?? '')) ? date('Y-m-d', strtotime(trim($row[6]))) : null;
            
            $address = isset($row[7]) ? trim($row[7]) : '';
            $city_state = isset($row[8]) ? trim($row[8]) : '';
            $postal_code = isset($row[9]) ? trim($row[9]) : '';
            
            // Normalize Y/N fields
            $online = (strtoupper(trim($row[10] ?? '')) === 'Y') ? 'Y' : 'N';
            $age_data = (strtoupper(trim($row[11] ?? '')) === 'Y') ? 'Y' : 'N';
            $satellite = (strtoupper(trim($row[12] ?? '')) === 'Y') ? 'Y' : 'N';

            // Skip row if required fields are missing
            if (empty($member_id) || empty($first_name) || empty($last_name)) {
                $skipped_count++;
                continue; 
            }

            $email_exists = false;
            
            // Check duplicate email
            if (!empty($email) && $check_email_stmt) {
                $check_email_stmt->bind_param("s", $email);
                $check_email_stmt->execute();
                if ($check_email_stmt->get_result()->num_rows > 0) {
                    $email_exists = true;
                }
            }

            // Insert if valid
            if (!$email_exists && $insert_stmt) {
                $insert_stmt->bind_param("isssssssssssss", 
                    $organisation_id, $member_id, $first_name, $last_name, 
                    $email, $phone_no, $joining_date, $original_rotary_date, 
                    $address, $city_state, $postal_code, 
                    $online, $age_data, $satellite
                );
                
                try {
                    if ($insert_stmt->execute()) {
                        $imported_count++;
                    }
                } catch (mysqli_sql_exception $e) {
                    // Skip if duplicate Member ID
                    $skipped_count++;
                }
            } else {
                $skipped_count++;
            }
        }
        
        // Redirect back with a summary message
        $final_msg = urlencode("Excel Import complete! $imported_count members added. $skipped_count rows skipped (duplicates or missing data).");
        header("Location: members.php?msg=" . $final_msg);
        exit();
        
    } else {
        $error = "The Excel file appears to be empty or invalid.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Import Excel - Rotary Membership</title>
    <?php render_client_shared_styles(); ?>
<script src="https://cdn.sheetjs.com/xlsx-latest/package/dist/xlsx.full.min.js"></script>
    
    <style>
.dashboard-content { max-width: 900px; margin: 0 auto; }
        
        .card { background: var(--white); border-radius: 16px; box-shadow: var(--card-shadow); overflow: hidden; }
        .card-header { padding: 25px 30px; border-bottom: 1px solid #e2e8f0; }
        .card-header h1 { font-size: 22px; font-weight: 700; color: var(--text-dark); margin-bottom: 5px; }
        .card-header p { font-size: 14px; color: var(--text-light); }
        
        .instructions-box { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; padding: 20px; margin: 30px; }
        .instructions-box h3 { font-size: 16px; margin-bottom: 10px; color: var(--text-dark); }
        .instructions-box ol { padding-left: 20px; margin-bottom: 15px; font-size: 14px; color: var(--text-light); line-height: 1.6; }
        .instructions-box code { background: #e2e8f0; padding: 2px 6px; border-radius: 4px; color: #0f4c81; font-weight: 600; font-size: 13px; }
        .column-list { background: var(--white); border: 1px solid #e2e8f0; border-radius: 8px; padding: 15px; font-size: 13px; font-family: monospace; color: var(--text-dark); overflow-x: auto; white-space: nowrap; }

        form { padding: 0 30px 30px; }
        
        .file-upload-wrapper { border: 2px dashed #cbd5e0; border-radius: 12px; padding: 40px 20px; text-align: center; background: #f7fafc; transition: all 0.3s ease; margin-bottom: 20px; cursor: pointer; }
        .file-upload-wrapper:hover { border-color: var(--success-color); background: rgba(16, 185, 129, 0.05); }
        .file-upload-wrapper i { font-size: 48px; color: #10b981; margin-bottom: 15px; }
        .file-upload-wrapper h4 { font-size: 16px; color: var(--text-dark); margin-bottom: 5px; }
        .file-upload-wrapper p { font-size: 13px; color: var(--text-light); margin-bottom: 15px; }
        
        .file-input { display: none; }
        
        .custom-file-btn { display: inline-block; padding: 10px 20px; background: var(--white); border: 1px solid #cbd5e0; border-radius: 8px; color: var(--text-dark); font-weight: 600; font-size: 14px; cursor: pointer; transition: all 0.2s ease; pointer-events: none; }
        
        #file-name-display { display: block; margin-top: 15px; font-size: 14px; font-weight: 600; color: var(--success-color); }
        #processing-msg { display: none; margin-top: 10px; font-size: 14px; color: #0f4c81; font-weight: 600; }

        .form-actions { display: flex; gap: 15px; margin-top: 10px; padding-top: 20px; border-top: 1px solid #e2e8f0; }
        .btn { display: inline-flex; align-items: center; gap: 8px; padding: 12px 25px; border-radius: 10px; font-size: 14px; font-weight: 600; text-decoration: none; cursor: pointer; border: none; transition: all 0.3s ease; }
        .btn-success { background: var(--success-color); color: var(--white); opacity: 0.5; cursor: not-allowed; }
        .btn-success.active { opacity: 1; cursor: pointer; }
        .btn-success.active:hover { background: #059669; transform: translateY(-2px); box-shadow: 0 5px 15px rgba(16, 185, 129, 0.3); }
        .btn-secondary { background: #e2e8f0; color: var(--text-dark); }
        .btn-secondary:hover { background: #cbd5e0; transform: translateY(-2px); }
        
        .alert { padding: 15px 20px; border-radius: 10px; margin-bottom: 20px; display: flex; align-items: center; gap: 10px; font-size: 14px; font-weight: 500; }
        .alert-error { background: rgba(229, 62, 62, 0.15); color: #c53030; border: 1px solid rgba(229, 62, 62, 0.3); }

        @media (max-width: 768px) {
.form-actions { flex-direction: column; }
            .btn { justify-content: center; }
        }
    </style>
</head>
<body>

<?php 
$active_page = 'members';
include 'sidebar.php'; 
?>

<div class="main-content">
<?php render_client_header(); ?>

    <div class="dashboard-content">
        
        <?php if ($error): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <div class="card">
            <div class="card-header">
                <h1><i class="fas fa-file-excel"></i> Import Excel File</h1>
                <p>Upload a standard <b>.xlsx</b> or <b>.xls</b> file to import members.</p>
            </div>
            
            <div class="instructions-box">
                <h3><i class="fas fa-info-circle" style="color: var(--primary-color);"></i> Formatting Instructions</h3>
                <ol>
                    <li>You can upload standard Excel files (<code>.xlsx</code>, <code>.xls</code>).</li>
                    <!-- <li>Ensure the <strong>first row is a header</strong> (it will be skipped).</li> -->
                    <!-- <li>Ensure dates are formatted nicely in Excel (e.g. <code>YYYY-MM-DD</code>).</li> -->
                </ol>
                <p style="font-size: 13px; font-weight: 600; margin-bottom: 8px;">Your columns MUST be in this exact order:</p>
                <!-- <div class="column-list">
                    1. Member ID * | 2. First Name * | 3. Last Name * | 4. Email | 5. Phone | 6. Joining Date | 7. Original Date | 8. Address | 9. City/State | 10. Postal Code | 11. Online Acct (Y/N) | 12. Age Data (Y/N) | 13. Satellite (Y/N)
                </div> -->
            </div>
            
            <form id="excelForm" action="" method="POST">
                <input type="hidden" name="excel_data" id="excel_data">
                
                <div class="file-upload-wrapper" onclick="document.getElementById('excel_file').click();">
                    <i class="fas fa-file-excel"></i>
                    <h4>Click to browse or drag your Excel file here</h4>
                    <p>Supports .xlsx and .xls</p>
                    
                    <input type="file" id="excel_file" class="file-input" accept=".xlsx, .xls, .csv" required>
                    <span class="custom-file-btn">Browse Files</span>
                    
                    <span id="file-name-display"></span>
                    <div id="processing-msg"><i class="fas fa-spinner fa-spin"></i> Processing Excel File...</div>
                </div>

                <div class="form-actions">
                    <button type="submit" id="submitBtn" class="btn btn-success" disabled>
                        <i class="fas fa-upload"></i> Start Import
                    </button>
                    <a href="members.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Cancel
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>

    <script>
        // Sidebar logic
        function toggleSidebar() { document.querySelector('.sidebar').classList.toggle('active'); }
        
        // Handle Excel File Reading via SheetJS
        document.getElementById('excel_file').addEventListener('change', function(e) {
            var file = e.target.files[0];
            if (!file) return;
            
            // UI Updates
            document.getElementById('file-name-display').innerHTML = '<i class="fas fa-check-circle"></i> Selected: ' + file.name;
            document.getElementById('processing-msg').style.display = 'block';
            document.getElementById('submitBtn').disabled = true;
            document.getElementById('submitBtn').classList.remove('active');

            var reader = new FileReader();
            
            reader.onload = function(e) {
                var data = e.target.result;
                // Parse the Excel file
                var workbook = XLSX.read(data, {
                    type: 'binary',
                    cellDates: true, 
                    dateNF: 'yyyy-mm-dd' // Standardize dates
                });
                
                // Grab the first sheet
                var firstSheetName = workbook.SheetNames[0];
                var worksheet = workbook.Sheets[firstSheetName];
                
                // Convert sheet to array of arrays
                var json_data = XLSX.utils.sheet_to_json(worksheet, {
                    header: 1,
                    raw: false // Forces dates to format as strings based on dateNF
                });
                
                // Put JSON into hidden input
                document.getElementById('excel_data').value = JSON.stringify(json_data);
                
                // UI Updates
                document.getElementById('processing-msg').innerHTML = '<i class="fas fa-check"></i> File ready for import! (' + (json_data.length - 1) + ' rows found)';
                document.getElementById('processing-msg').style.color = '#10b981';
                document.getElementById('submitBtn').disabled = false;
                document.getElementById('submitBtn').classList.add('active');
            };
            
            reader.onerror = function(ex) {
                alert("Error reading file!");
                document.getElementById('processing-msg').style.display = 'none';
            };
            
            reader.readAsBinaryString(file);
        });
    </script>
</body>
</html>
