<?php 
include('config/db.php');
include('config/auth.php'); 

$msg = "";
$error = "";

// 1. වී තොගයක් Milling එකට දාන කොට වැඩ කරන PHP Code එක
if (isset($_POST['start_milling'])) {
    $paddy_stock_id = mysqli_real_escape_string($conn, $_POST['paddy_stock_id']);
    $weight_used = floatval($_POST['paddy_weight_used']);
    $start_date = date('Y-m-d H:i:s');

    $stock_check = $conn->query("SELECT weight_kg FROM paddy_stock WHERE id='$paddy_stock_id'");
    $row = $stock_check->fetch_assoc();
    $current_stock = $row ? floatval($row['weight_kg']) : 0;

    if ($weight_used <= 0) {
        $error = "කරුණාකර වලංගු වී බරක් ඇතුළත් කරන්න!";
    } else if ($current_stock >= $weight_used) {
        $sql = "INSERT INTO milling_process (paddy_stock_id, paddy_weight_used, start_date, status) 
                VALUES ('$paddy_stock_id', '$weight_used', '$start_date', 'Processing')";

        if ($conn->query($sql) === TRUE) {
            $new_weight = $current_stock - $weight_used;
            if ($new_weight <= 0) {
                $conn->query("UPDATE paddy_stock SET weight_kg='0.00', status='Milled' WHERE id='$paddy_stock_id'");
            } else {
                $conn->query("UPDATE paddy_stock SET weight_kg='$new_weight' WHERE id='$paddy_stock_id'");
            }
            $msg = "Milling Process එක සාර්ථකව ආරම්භ කළා! වී තොගයෙන් බර අඩු කරන ලදී.";
        } else {
            $error = "MySQL Error: " . $conn->error;
        }
    } else {
        $error = "ප්‍රමාණවත් වී තොගයක් නොමැත! (දැනට ඇති තොගය: " . number_format($current_stock, 2) . " KG)";
    }
}

// 2. Milling වැඩේ ඉවර වුණාම Form එකෙන් එන දත්ත
if (isset($_POST['complete_milling'])) {
    $process_id = mysqli_real_escape_string($conn, $_POST['process_id']);
    $rice_produced = floatval($_POST['rice_produced_kg']);
    $husk_produced = floatval($_POST['husk_produced_kg']);
    $end_date = date('Y-m-d H:i:s');

    $job_info = $conn->query("SELECT p.paddy_type, m.paddy_weight_used FROM milling_process m JOIN paddy_stock p ON m.paddy_stock_id = p.id WHERE m.id='$process_id'")->fetch_assoc();
    $rice_type = $job_info['paddy_type'] ?? 'Unknown';
    $paddy_weight_used = floatval($job_info['paddy_weight_used'] ?? 0);

    if (($rice_produced + $husk_produced) > $paddy_weight_used || ($rice_produced / $paddy_weight_used) > 0.80) {
        $error = "ඇතුළත් කළ දත්ත දෝෂ සහිතයි! කරුණාකර නිවැරදි බර ඇතුළත් කරන්න.";
    } else {
        $sql = "UPDATE milling_process 
                SET rice_produced_kg='$rice_produced', husk_produced_kg='$husk_produced', end_date='$end_date', status='Completed' 
                WHERE id='$process_id'";

        if ($conn->query($sql) === TRUE) {
            $bag_weight = 50.00;
            $bags_produced = floor($rice_produced / $bag_weight);

            // Rice Stock Update
            $check_stock = $conn->query("SELECT * FROM rice_stock WHERE rice_type='$rice_type'");
            if ($check_stock && $check_stock->num_rows > 0) {
                $conn->query("UPDATE rice_stock SET available_bag_count = available_bag_count + $bags_produced, total_weight_kg = total_weight_kg + $rice_produced WHERE rice_type='$rice_type'");
            } else {
                $price_per_bag = 5000.00; 
                $conn->query("INSERT INTO rice_stock (rice_type, available_bag_count, bag_weight_kg, total_weight_kg, price_per_bag) VALUES ('$rice_type', '$bags_produced', '$bag_weight', '$rice_produced', '$price_per_bag')");
            }

            // By-product Stock Update
            $check_byproduct = $conn->query("SELECT * FROM byproduct_stock WHERE product_type='Rice Bran / Husk'");
            if ($check_byproduct && $check_byproduct->num_rows > 0) {
                $conn->query("UPDATE byproduct_stock SET total_weight_kg = total_weight_kg + $husk_produced WHERE product_type='Rice Bran / Husk'");
            } else {
                $conn->query("INSERT INTO byproduct_stock (product_type, total_weight_kg) VALUES ('Rice Bran / Husk', '$husk_produced')");
            }

            $msg = "Milling process completed! Stocks updated successfully.";
        } else {
            $error = "MySQL Error: " . $conn->error;
        }
    }
}

// 3. එකතුවන් ලබා ගැනීම
$efficiency_q = $conn->query("SELECT SUM(paddy_weight_used) as total_paddy_used, SUM(rice_produced_kg) as total_rice_out, SUM(husk_produced_kg) as total_husk_out FROM milling_process WHERE status='Completed'");
$eff_data = $efficiency_q->fetch_assoc();

$paddy_used = $eff_data['total_paddy_used'] ?? 0;
$rice_out = $eff_data['total_rice_out'] ?? 0;
$husk_out = $eff_data['total_husk_out'] ?? 0;
$recovery_rate = $paddy_used > 0 ? ($rice_out / $paddy_used) * 100 : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Premium Milling Management - Minsada Rice Mill</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Noto+Sans+Sinhala:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        :root {
            --bg-dark-main: #0b0f19;       
            --panel-dark: #131c2e;         
            --border-dark: #222f47;        
            --accent-blue: #3b82f6;        
            --accent-blue-glow: rgba(59, 130, 246, 0.3);
            --accent-green: #10b981;
            --accent-green-glow: rgba(16, 185, 129, 0.2);
            --accent-cyan: #06b6d4;
            --text-light: #f8fafc;         
            --text-muted: #64748b;         
            --text-desc: #94a3b8;
        }

        body { 
            font-family: 'Plus Jakarta Sans', 'Noto Sans Sinhala', sans-serif; 
            background-color: var(--bg-dark-main) !important; 
            color: var(--text-light) !important; 
            margin: 0; 
            display: flex;
        }

        .main-content { 
            padding: 40px; 
            margin-left: 260px; 
            min-height: 100vh; 
            box-sizing: border-box; 
            width: calc(100% - 260px);
            background-color: var(--bg-dark-main) !important;
        }
        
        header { 
            padding-bottom: 25px; 
            border-bottom: 1px solid var(--border-dark); 
            margin-bottom: 35px; 
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
        }
        header h2 { font-size: 26px; font-weight: 800; color: #ffffff; margin: 0; display: flex; align-items: center; gap: 12px; letter-spacing: -0.5px; }
        header span { color: var(--text-muted); font-size: 14px; font-weight: 500; }
        header strong { color: var(--accent-blue); font-weight: 700; }

        /* Premium Box Design */
        .premium-box { 
            background: linear-gradient(145deg, #131c2e, #18253c) !important; 
            border-radius: 20px !important; 
            padding: 30px !important; 
            border: 1px solid var(--border-dark) !important; 
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3) !important; 
            margin-bottom: 35px; 
            position: relative;
            overflow: hidden;
        }

        .box-title { 
            font-size: 17px; 
            font-weight: 700; 
            color: #ffffff; 
            margin-bottom: 25px; 
            display: flex; 
            align-items: center; 
            gap: 12px; 
            border-bottom: 1px solid rgba(255,255,255,0.05); 
            padding-bottom: 15px; 
            letter-spacing: -0.3px;
        }

        .form-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 25px; }
        .form-group { display: flex; flex-direction: column; }
        
        .form-group label { 
            font-size: 12px; 
            font-weight: 700; 
            color: var(--text-desc); 
            text-transform: uppercase; 
            margin-bottom: 8px; 
            letter-spacing: 0.7px; 
        }
        .form-group label span { font-size: 11px; color: var(--text-muted); text-transform: none; font-weight: 500; }
        
        .form-group input, .form-group select {
            width: 100%;
            padding: 14px 16px; 
            background-color: #090e17 !important; 
            border: 1px solid var(--border-dark) !important; 
            border-radius: 10px; 
            color: #ffffff !important; 
            font-family: inherit;
            font-size: 14px; 
            font-weight: 600;
            box-sizing: border-box;
            transition: all 0.3s ease;
            box-shadow: inset 0 2px 4px rgba(0,0,0,0.2);
        }
        .form-group input:focus, .form-group select:focus { 
            outline: none; 
            border-color: var(--accent-blue) !important; 
            box-shadow: 0 0 0 4px var(--accent-blue-glow);
        }
        
        .btn-submit { 
            background: linear-gradient(135deg, var(--accent-blue), #1d4ed8) !important; 
            color: #ffffff !important; 
            border: none; 
            padding: 14px 30px; 
            font-weight: 700; 
            border-radius: 10px; 
            cursor: pointer; 
            font-size: 14px; 
            transition: all 0.3s ease; 
            margin-top: 25px; 
            display: inline-flex;
            align-items: center;
            gap: 10px;
            box-shadow: 0 8px 20px var(--accent-blue-glow); 
        }
        .btn-submit:hover { 
            transform: translateY(-2px); 
            box-shadow: 0 12px 24px rgba(59, 130, 246, 0.5); 
        }
        
        /* Modern Table Design */
        table { width: 100%; border-collapse: separate; border-spacing: 0 8px; text-align: left; }
        th { 
            background-color: transparent !important; 
            padding: 12px 20px !important; 
            font-size: 12px !important; 
            font-weight: 700 !important; 
            color: var(--text-muted) !important; 
            text-transform: uppercase; 
            letter-spacing: 1px;
        }
        td { 
            padding: 18px 20px !important; 
            font-size: 14px !important; 
            color: #e2e8f0 !important; 
            background-color: #0e1524 !important;
            border-top: 1px solid var(--border-dark);
            border-bottom: 1px solid var(--border-dark);
        }
        td:first-child { border-left: 1px solid var(--border-dark); border-top-left-radius: 12px; border-bottom-left-radius: 12px; }
        td:last-child { border-right: 1px solid var(--border-dark); border-top-right-radius: 12px; border-bottom-right-radius: 12px; }
        tr:hover td { background-color: #162035 !important; color: #ffffff; border-color: #3b4f74; }
        
        /* Status & Badges */
        .badge { padding: 6px 14px; border-radius: 8px; font-size: 12px; font-weight: 700; display: inline-flex; align-items: center; gap: 6px; }
        .badge-process { background: rgba(245, 158, 11, 0.1); color: #f59e0b; border: 1px solid rgba(245, 158, 11, 0.2); }
        .badge-complete { background: rgba(16, 185, 129, 0.1); color: var(--accent-green); border: 1px solid rgba(16, 185, 129, 0.2); }
        
        .paddy-type-tag {
            padding: 4px 10px; background: rgba(6, 182, 212, 0.1); border-radius: 8px; color: var(--accent-cyan); font-size: 13px; font-weight: 700; border: 1px solid rgba(6, 182, 212, 0.2);
        }

        .btn-complete { 
            background: linear-gradient(135deg, var(--accent-green), #059669) !important; 
            color: white !important; 
            padding: 8px 16px; 
            border: none; 
            border-radius: 8px; 
            cursor: pointer; 
            font-size: 12.5px; 
            font-weight: 700; 
            transition: all 0.2s; 
            box-shadow: 0 4px 12px var(--accent-green-glow);
        }
        .btn-complete:hover { transform: translateY(-1px); box-shadow: 0 6px 16px rgba(16, 185, 129, 0.4); }

        /* Efficiency Glass Cards */
        .eff-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 25px; margin-bottom: 35px; }
        .eff-card { 
            background: linear-gradient(145deg, #0e1524, #131c2e); 
            border: 1px solid var(--border-dark); 
            padding: 24px; 
            border-radius: 16px; 
            position: relative;
            transition: all 0.3s ease;
        }
        .eff-card:hover { transform: translateY(-4px); border-color: #3b4f74; box-shadow: 0 10px 20px rgba(0,0,0,0.2); }
        .eff-card span { color: var(--text-muted); font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.8px; }
        .eff-card .val { font-size: 24px; font-weight: 800; color: #ffffff; margin-top: 10px; display: flex; align-items: baseline; gap: 5px; }
        .eff-card .icon-bg { position: absolute; right: 20px; top: 20px; font-size: 28px; opacity: 0.1; color: var(--accent-blue); }

        /* Premium Progress Bar */
        .progress-container { background: #090e17; border-radius: 99px; height: 32px; width: 100%; position: relative; overflow: hidden; border: 1px solid var(--border-dark); box-shadow: inset 0 2px 4px rgba(0,0,0,0.5); }
        .progress-bar { background: linear-gradient(90deg, #3b82f6, #06b6d4, #10b981); height: 100%; transition: width 0.8s cubic-bezier(0.4, 0, 0.2, 1); box-shadow: 0 0 10px rgba(16, 185, 129, 0.5); }
        .progress-text { position: absolute; left: 50%; top: 50%; transform: translate(-50%, -50%); font-weight: 800; font-size: 13px; color: #ffffff; letter-spacing: 0.5px; text-shadow: 0 1px 4px rgba(0,0,0,0.8); }

        /* Dynamic Complete Form Look */
        .complete-form-box {
            background: #090f1c !important;
            border: 1px solid var(--accent-green) !important;
            border-radius: 16px;
            padding: 25px;
            margin-top: 30px;
            box-shadow: 0 0 25px rgba(16, 185, 129, 0.1) !important;
            animation: slideDown 0.35s cubic-bezier(0.4, 0, 0.2, 1);
        }
        @keyframes slideDown {
            from { opacity: 0; transform: translateY(-15px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .live-feedback-panel {
            font-size: 14px; color: var(--text-light); margin-bottom: 20px; padding: 16px 20px; background: #131c2e; border: 1px solid var(--border-dark); border-radius: 10px; font-weight: 600; display: flex; justify-content: space-between; align-items: center;
        }

        .alert { padding: 14px 20px; border-radius: 10px; margin-bottom: 30px; font-size: 14px; font-weight: 600; display: flex; align-items: center; gap: 10px; }
        .alert-success { background: rgba(16, 185, 129, 0.1); border: 1px solid var(--accent-green); color: #34d399; }
        .alert-danger { background: rgba(239, 68, 68, 0.1); border: 1px solid #ef4444; color: #f87171; }
    </style>
</head>
<body>

    <!-- 🗂️ Sidebar Area -->
    <?php include('includes/sidebar.php'); ?>

    <div class="main-content">
        <header>
            <h2><i class="fa-solid fa-microchip" style="color: var(--accent-blue);"></i> Milling Automation Control</h2>
            <span>System Status: <strong style="color: var(--accent-green);"><i class="fa-solid fa-circle" style="font-size:10px;"></i> Active</strong></span>
        </header>

        <div class="content-wrapper">
            
            <?php if ($msg != ""): ?>
                <div class="alert alert-success"><i class="fa-solid fa-circle-check"></i> <?php echo $msg; ?></div>
            <?php endif; ?>
            <?php if ($error != ""): ?>
                <div class="alert alert-danger"><i class="fa-solid fa-circle-exclamation"></i> <?php echo $error; ?></div>
            <?php endif; ?>

            <!-- වී කොටන්න දාන Form එක -->
            <div class="premium-box">
                <div class="box-title"><i class="fa-solid fa-bolt" style="color: #f59e0b;"></i> Start Production Line (නව කෙටීම් ක්‍රියාවලිය)</div>
                <form action="milling_management.php" method="POST">
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Select Paddy Batch <span>(වී තොග කණ්ඩායම)</span></label>
                            <select name="paddy_stock_id" required>
                                <option value="">-- Select Available Paddy Batch --</option>
                                <?php 
                                $paddy_res = $conn->query("SELECT * FROM paddy_stock WHERE weight_kg > 0 AND (status='Available' OR status='' OR status='Stocked' OR status='Paddy Stocked')");
                                while($paddy = $paddy_res->fetch_assoc()) {
                                    echo "<option value='{$paddy['id']}'>Batch #{$paddy['id']} - {$paddy['farmer_name']} ({$paddy['paddy_type']} | " . number_format($paddy['weight_kg'], 2) . "kg)</option>";
                                }
                                ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Target Weight to Mill (KG) <span>(කොටන්න ගන්නා බර)</span></label>
                            <input type="number" step="0.01" name="paddy_weight_used" required placeholder="E.g. 1000.00">
                        </div>
                    </div>
                    <button type="submit" name="start_milling" class="btn-submit">Initiate Milling <i class="fa-solid fa-arrow-right"></i></button>
                </form>
            </div>

            <!-- වී කෙටීමේ ඉතිහාසය Table එක -->
            <div class="premium-box">
                <div class="box-title"><i class="fa-solid fa-list-check" style="color: var(--accent-blue);"></i> Modern Operations Log (මෙහෙයුම් වාර්තා ලොගය)</div>
                <div style="overflow-x: auto;">
                    <table>
                        <thead>
                            <tr>
                                <th>Job ID</th>
                                <th>Launch Time</th>
                                <th>Paddy Variety</th>
                                <th>Input Weight</th>
                                <th>Rice Output</th>
                                <th>Status</th>
                                <th style="text-align: center;">Control Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $sql_jobs = "SELECT m.*, p.paddy_type FROM milling_process m 
                                         JOIN paddy_stock p ON m.paddy_stock_id = p.id 
                                         ORDER BY m.id DESC";
                            $jobs_res = $conn->query($sql_jobs);
                            
                            if ($jobs_res && $jobs_res->num_rows > 0) {
                                while($job = $jobs_res->fetch_assoc()) {
                                    $is_processing = ($job['status'] == 'Processing');
                                    $status_badge = $is_processing ? 'badge-process' : 'badge-complete';
                                    $status_icon = $is_processing ? '<i class="fa-solid fa-spinner fa-spin"></i>' : '<i class="fa-solid fa-check-double"></i>';
                                    ?>
                                    <tr>
                                        <td style="font-weight:700; color: #38bdf8;">#JOB-<?php echo $job['id']; ?></td>
                                        <td style="color: var(--text-desc);"><?php echo date('M d, Y - H:i', strtotime($job['start_date'])); ?></td>
                                        <td><span class="paddy-type-tag"><?php echo $job['paddy_type']; ?></span></td>
                                        <td style="font-weight:700; color: #ffffff;"><?php echo number_format($job['paddy_weight_used'], 2); ?> <span style="font-size:11px; color:var(--text-muted)">KG</span></td>
                                        <td style="font-weight:700; color: var(--accent-green);">
                                            <?php echo !$is_processing ? number_format($job['rice_produced_kg'], 2) . ' <span style="font-size:11px; color:var(--text-muted)">KG</span>' : '<span style="color:var(--text-muted)">--</span>'; ?>
                                        </td>
                                        <td><span class='badge <?php echo $status_badge; ?>'><?php echo $status_icon . ' ' . $job['status']; ?></span></td>
                                        <td style="text-align: center;">
                                        <?php if ($is_processing) { ?>
                                            <button type="button" onclick="triggerCompleteForm('<?php echo $job['id']; ?>', '<?php echo $job['paddy_weight_used']; ?>')" class="btn-complete">
                                                Update Output <i class="fa-solid fa-pen-to-square"></i>
                                            </button>
                                        <?php } else { ?>
                                            <span style='font-size:12px; color: var(--text-muted); font-weight: 600;'><i class='fa-solid fa-clock'></i> <?php echo date('H:i', strtotime($job['end_date'])); ?></span>
                                        <?php } ?>
                                        </td>
                                    </tr>
                                    <?php
                                }
                            } else {
                                echo "<tr><td colspan='7' style='text-align:center; color: var(--text-muted); padding:40px;'>No production logs found.</td></tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>

                <!-- Complete කරන්න ලැබෙන Form එක -->
                <div id="complete_overlay_section" style="display: none;" class="complete-form-box">
                    <h4 style="margin: 0 0 20px 0; color: var(--accent-green); font-size: 16px; font-weight: 800;"><i class="fa-solid fa-receipt"></i> Log Output Metrics for Job #<span id="display_job_id">00</span></h4>
                    <form action="milling_management.php" method="POST">
                        <input type="hidden" name="process_id" id="hidden_process_id">

                        <div class="form-grid" style="margin-bottom: 25px;">
                            <div class="form-group">
                                <label style="color: var(--accent-green);">Net Rice Yield Produced (KG)</label>
                                <input type="number" name="rice_produced_kg" id="calc_rice_out" step="0.01" placeholder="0.00" oninput="runLiveMath()" required>
                            </div>
                            <div class="form-group">
                                <label style="color: #f59e0b;">Fine Bran / Husk Extracted (KG)</label>
                                <input type="number" name="husk_produced_kg" id="calc_husk_out" step="0.01" placeholder="0.00" oninput="runLiveMath()" required>
                            </div>
                        </div>

                        <!-- Live Math Display Area -->
                        <div class="live-feedback-panel" id="math_feedback_text">
                            <span>Ready to process math logs...</span>
                        </div>

                        <div style="display: flex; gap: 15px;">
                            <button type="submit" name="complete_milling" id="submit_btn" class="btn-submit" style="margin-top:0; background: linear-gradient(135deg, var(--accent-green), #047857) !important; box-shadow: 0 4px 14px var(--accent-green-glow);">Commit & Dispatch Stock 💾</button>
                            <button type="button" onclick="closeCompleteForm()" class="btn-submit" style="margin-top:0; background:#334155 !important; box-shadow: none;">Dismiss</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Efficiency Dashboard Widgets -->
            <div class="premium-box">
                <div class="box-title"><i class="fa-solid fa-chart-pie" style="color: var(--accent-cyan);"></i> Factory Performance Analytics</div>
                
                <div class="eff-grid">
                    <div class="eff-card">
                        <span>Total Paddy Processed</span>
                        <div class="val"><?php echo number_format($paddy_used, 2); ?> <span style="font-size:14px; color: var(--text-muted); font-weight:500;">KG</span></div>
                        <i class="fa-solid fa-wheat-awn icon-bg" style="color:#f59e0b;"></i>
                    </div>
                    <div class="eff-card">
                        <span style="color: var(--accent-green);">Total Fine Rice Output</span>
                        <div class="val" style="color: var(--accent-green);"><?php echo number_format($rice_out, 2); ?> <span style="font-size:14px; color: var(--text-muted); font-weight:500;">KG</span></div>
                        <i class="fa-solid fa-bowl-rice icon-bg" style="color:var(--accent-green)"></i>
                    </div>
                    <div class="eff-card">
                        <span style="color: #f59e0b;">Total By-Products Saved</span>
                        <div class="val" style="color: #f59e0b;"><?php echo number_format($husk_out, 2); ?> <span style="font-size:14px; color: var(--text-muted); font-weight:500;">KG</span></div>
                        <i class="fa-solid fa-recycle icon-bg"></i>
                    </div>
                </div>

                <div class="progress-container">
                    <div class="progress-bar" style="width: <?php echo min($recovery_rate, 100); ?>%;"></div>
                    <div class="progress-text">Global Rice Recovery Yield: <?php echo number_format($recovery_rate, 1); ?>%</div>
                </div>
            </div>

        </div>
    </div>

    <!-- 📜 MODERN JAVASCRIPT LIVE LOGIC -->
    <script>
        let inputPaddyWeight = 0;

        function triggerCompleteForm(id, paddyWeight) {
            inputPaddyWeight = parseFloat(paddyWeight);
            
            document.getElementById('complete_overlay_section').style.display = 'block';
            document.getElementById('display_job_id').innerText = id;
            document.getElementById('hidden_process_id').value = id;
            
            document.getElementById('calc_rice_out').value = '';
            document.getElementById('calc_husk_out').value = '';
            document.getElementById('math_feedback_text').innerHTML = `<span>Batch Weight: <b>${inputPaddyWeight.toFixed(2)} KG</b></span><span style="color:var(--text-muted);">Awaiting metrics...</span>`;
            
            const submitBtn = document.getElementById('submit_btn');
            submitBtn.disabled = false;
            submitBtn.style.opacity = "1";

            document.getElementById('complete_overlay_section').scrollIntoView({ behavior: 'smooth' });
        }

        function closeCompleteForm() {
            document.getElementById('complete_overlay_section').style.display = 'none';
        }

        function runLiveMath() {
            const riceOut = parseFloat(document.getElementById('calc_rice_out').value) || 0;
            const huskOut = parseFloat(document.getElementById('calc_husk_out').value) || 0;
            const totalOut = riceOut + huskOut;
            const diff = inputPaddyWeight - totalOut;
            const recovery = inputPaddyWeight > 0 ? (riceOut / inputPaddyWeight) * 100 : 0;
            const submitBtn = document.getElementById('submit_btn');

            let htmlContent = "";
            
            if (totalOut > inputPaddyWeight) {
                htmlContent = `<span>Total Out: ${totalOut.toFixed(2)} KG</span> <span style="color:#f87171;"><i class="fa-solid fa-triangle-exclamation"></i> Error: Exceeds Input (${inputPaddyWeight.toFixed(2)} KG)</span>`;
                submitBtn.disabled = true;
                submitBtn.style.opacity = "0.4";
                submitBtn.style.cursor = "not-allowed";
            } else if (recovery > 80) {
                htmlContent = `<span>Recovery: ${recovery.toFixed(1)}%</span> <span style="color:#fbbf24;"><i class="fa-solid fa-circle-exclamation"></i> Warning: Yield looks too high (>80%)</span>`;
                submitBtn.disabled = true;
                submitBtn.style.opacity = "0.4";
            } else {
                htmlContent = `<span>Loss/Wastage: <b style="color:#f87171;">${diff.toFixed(2)} KG</b></span> <span>Current Yield: <b style="color:var(--accent-cyan);">${recovery.toFixed(1)}%</b></span>`;
                submitBtn.disabled = false;
                submitBtn.style.opacity = "1";
                submitBtn.style.cursor = "pointer";
            }

            document.getElementById('math_feedback_text').innerHTML = htmlContent;
        }
    </script>
</body>
</html>