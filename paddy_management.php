<?php 
include('config/db.php'); // Database Connection
include('config/auth.php'); // User Authentication

$show_bill = false;
$bill_data = [];
$sms_status = "";

// 📳 SMS Gateway Function (Notify.lk සඳහා)
function sendSMS($phone, $message) {
    $formatted_phone = preg_replace('/[^0-9]/', '', $phone);
    if (strlen($formatted_phone) == 10 && substr($formatted_phone, 0, 1) == '0') {
        $formatted_phone = '94' . substr($formatted_phone, 1);
    }

    $api_url = "https://app.notify.lk/api/v1/send";
    $user_id = "YOUR_USER_ID"; 
    $api_key = "YOUR_API_KEY"; 
    $sender_id = "Demo";       

    if($user_id == "YOUR_USER_ID" || $api_key == "YOUR_API_KEY") {
        return false;
    }

    $post_fields = [
        'user_id' => $user_id,
        'api_key' => $api_key,
        'sender_id' => $sender_id,
        'to' => $formatted_phone,
        'message' => $message
    ];

    $ch = curl_init();
    $ch::setopt($ch, CURLOPT_URL, $api_url);
    $ch::setopt($ch, CURLOPT_POST, true);
    $ch::setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post_fields));
    $ch::setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $ch::setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    $response = curl_exec($ch);
    curl_close($ch);

    return $response;
}

// 💾 Form එක Submit කළ පසු Data Save කිරීම
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['save_purchase'])) {
    $farmer_name = mysqli_real_escape_string($conn, $_POST['farmer_name']);
    $farmer_phone = mysqli_real_escape_string($conn, $_POST['farmer_phone']);
    $rice_type = mysqli_real_escape_string($conn, $_POST['rice_type']);
    $weight_kg = floatval($_POST['weight_kg']);
    $moisture = floatval($_POST['moisture']);
    $price_per_kg = floatval($_POST['price_per_kg']);
    
    $total_amount = $weight_kg * $price_per_kg;

    // 1. paddy_procurement table එකට data ඇතුළත් කිරීම
    $sql = "INSERT INTO paddy_procurement (farmer_name, farmer_phone, rice_type, weight_kg, moisture_percentage, price_per_kg, total_amount) 
            VALUES ('$farmer_name', '$farmer_phone', '$rice_type', '$weight_kg', '$moisture', '$price_per_kg', '$total_amount')";
    
    if ($conn->query($sql)) {
        $last_id = $conn->insert_id;
        $bill_no = str_pad($last_id, 5, '0', STR_PAD_LEFT);

        // 2. Dashboard Stock එක වැඩි වීමට paddy_stock වගුවට දත්ත Insert කිරීම (Status = Stocked)
        $conn->query("INSERT INTO paddy_stock (paddy_type, weight_kg, status) VALUES ('$rice_type', '$weight_kg', 'Stocked')");

        // SMS එකක් යැවීම (අංකයක් තියෙනවා නම් පමණක්)
        if (!empty($farmer_phone)) {
            $sms_msg = "Minsada Rice Mill:\nHi $farmer_name, your paddy purchase (Bill #$bill_no) is processed.\nQty: $weight_kg KG ($rice_type)\nTotal Paid: LKR " . number_format($total_amount, 2) . "\nThank you!";
            $sms_res = sendSMS($farmer_phone, $sms_msg);
            
            if($sms_res) {
                $sms_status = "SMS Sent Successfully!";
            } else {
                $sms_status = "Ready for WhatsApp dispatch!";
            }
        }

        $show_bill = true;
        $bill_data = [
            'bill_no' => $bill_no,
            'name' => $farmer_name,
            'phone' => $farmer_phone,
            'type' => $rice_type,
            'weight' => $weight_kg,
            'moisture' => $moisture,
            'price' => $price_per_kg,
            'total' => $total_amount,
            'date' => date('Y-m-d H:i')
        ];
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Paddy Procurement - Minsada Rice Mill</title>
    <!-- Fonts & Icons -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Noto+Sans+Sinhala:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        :root {
            --bg-dark-main: #0f172a; 
            --panel-dark: #1e293b; 
            --border-dark: #334155; 
            
            --text-light: #f1f5f9; 
            --text-muted: #94a3b8; 
            --text-sinhala: #64748b; 
            
            --accent-blue: #38bdf8; 
            --accent-green: #10b981; 
            --accent-green-hover: #059669;
        }

        body { 
            font-family: 'Inter', 'Noto Sans Sinhala', sans-serif; 
            background-color: var(--bg-dark-main) !important; 
            color: var(--text-light) !important; 
            margin: 0; 
            display: flex;
        }

        .main-content { 
            padding: 30px; 
            margin-left: 260px; 
            min-height: 100vh; 
            box-sizing: border-box; 
            width: calc(100% - 260px);
            background-color: var(--bg-dark-main) !important;
        }
        
        header { 
            padding-bottom: 20px; 
            border-bottom: 1px solid var(--border-dark); 
            margin-bottom: 30px; 
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
        }
        header h2 { font-size: 24px; font-weight: 700; color: #ffffff; margin: 0; display: flex; align-items: center; gap: 10px; }
        header span { color: var(--text-muted); font-size: 14px; }
        header strong { color: var(--accent-blue); }

        .panel-box {
            background: var(--panel-dark) !important;
            border: 1px solid var(--border-dark) !important;
            border-radius: 16px !important;
            padding: 20px 24px !important;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2) !important;
        }

        .panel-box h3 { 
            font-size: 15px; 
            font-weight: 600; 
            color: #ffffff; 
            margin: 0 0 20px 0; 
            display: flex; 
            align-items: center; 
            gap: 10px;
            border-bottom: 1px solid var(--border-dark);
            padding-bottom: 12px;
        }

        .form-grid-2 {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 16px;
            margin-bottom: 16px;
        }
        .form-group { display: flex; flex-direction: column; }
        
        .form-group label { 
            font-size: 11px; 
            font-weight: 600; 
            color: var(--text-muted); 
            text-transform: uppercase; 
            margin-bottom: 6px; 
            letter-spacing: 0.5px;
        }
        .form-group label span {
            font-size: 11px;
            color: var(--text-sinhala);
        }
        
        .form-control {
            width: 100%;
            padding: 10px 14px; 
            background: var(--bg-dark-main) !important; 
            border: 1px solid var(--border-dark) !important;
            border-radius: 6px;
            color: #ffffff;
            font-family: 'Inter', 'Noto Sans Sinhala', sans-serif;
            font-size: 13px; 
            font-weight: 500;
            box-sizing: border-box;
        }
        .form-control:focus { 
            outline: none; 
            border-color: var(--accent-blue) !important; 
        }

        select.form-control {
            color: #ffffff;
            cursor: pointer;
        }

        .form-footer {
            display: flex;
            flex-direction: column;
            align-items: flex-end;
            gap: 10px;
            border-top: 1px solid var(--border-dark);
            padding-top: 15px;
            margin-top: 5px;
        }
        .live-total-box {
            font-size: 13px;
            color: var(--text-light);
            font-weight: 600;
        }
        .live-total-box span {
            font-size: 18px;
            font-weight: 700;
            color: var(--accent-green); 
        }

        .btn-submit {
            background: var(--accent-green);
            color: #ffffff; 
            border: none;
            padding: 10px 24px;
            font-weight: 700;
            border-radius: 6px;
            cursor: pointer;
            font-size: 13px;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.2s;
        }
        .btn-submit:hover { background: var(--accent-green-hover); }

        .bill-wrapper {
            display: grid;
            grid-template-columns: 1.25fr 0.75fr;
            gap: 20px;
            align-items: start;
        }

        .bill-box {
            background: #ffffff !important; 
            color: #1e293b !important; 
            border-radius: 10px;
            padding: 20px 24px; 
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }
        .bill-header { 
            text-align: center; 
            border-bottom: 2px dashed #e2e8f0; 
            padding-bottom: 12px; 
            margin-bottom: 15px; 
        }
        .bill-header h2 { 
            margin: 0; 
            font-size: 17px; 
            font-weight: 800; 
            color: #0f172a !important; 
        }
        .bill-header p { 
            margin: 4px 0 0 0; 
            font-size: 10px; 
            color: #64748b !important; 
        }
        
        .bill-details { 
            font-size: 12px; 
            color: #475569 !important; 
            margin-bottom: 15px; 
            line-height: 1.6; 
        }
        .bill-details div { display: flex; justify-content: space-between; }
        .bill-details b { color: #0f172a !important; }

        .bill-table { width: 100%; border-collapse: collapse; margin-bottom: 15px; }
        .bill-table th { 
            text-align: left; 
            font-size: 10px; 
            color: #64748b !important; 
            border-bottom: 1px solid #e2e8f0; 
            padding-bottom: 6px; 
        }
        .bill-table td { 
            padding: 8px 0; 
            font-size: 12px; 
            border-bottom: 1px solid #f1f5f9; 
            color: #1e293b !important; 
        }
        
        .total-row-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-top: 2px dashed #e2e8f0;
            padding-top: 12px;
            margin-bottom: 15px;
        }
        .total-row-label { font-size: 13px; font-weight: 700; color: #0f172a !important; }
        .total-row-value { font-size: 17px; font-weight: 800; color: var(--accent-green) !important; }
        
        .print-btn {
            background: #111a2e !important; 
            color: #ffffff !important; 
            border: none; 
            padding: 10px; 
            width: 100%; 
            border-radius: 6px; 
            margin-top: 10px; 
            font-weight: 700; 
            cursor: pointer; 
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            font-size: 12.5px;
        }
        
        .whatsapp-btn {
            background: var(--accent-green) !important; 
            color: white !important; 
            border: none; 
            padding: 10px; 
            width: 100%; 
            border-radius: 6px; 
            margin-top: 6px; 
            font-weight: 700; 
            cursor: pointer; 
            display: flex; 
            align-items: center; 
            justify-content: center; 
            gap: 6px; 
            font-size: 12.5px; 
        }

        .alert-success {
            background: #0f1d18;
            border: 1px solid #10b981;
            color: #a7f3d0;
            padding: 12px;
            border-radius: 6px;
            margin-bottom: 20px;
            font-size: 13px;
        }/* 🖨️ PRINT-ONLY STYLES (CORRECTED) */
        @media print {
            /* 1. ප්‍රධාන Layout එකෙන් බිල්පත හැර අනිත් හැම විශාල කොටසක්ම හංගන්න */
            .sidebar, 
            .main-content > header, 
            .alert-success, 
            .panel-box, 
            .print-btn, 
            .whatsapp-btn,
            #wa_btn {
                display: none !important;
            }

            /* 2. මුළු පිටුවම (Body) සහ Main Content එක Print එකට සකසන්න */
            body, html {
                background: #ffffff !important;
                color: #000000 !important;
                margin: 0 !important;
                padding: 0 !important;
                width: 100% !important;
            }

            .main-content {
                margin-left: 0 !important;
                width: 100% !important;
                padding: 0 !important;
                background: #ffffff !important;
            }

            /* 3. Grid එක අයින් කරලා බිල්පත විතරක් පේන්න සලස්වන්න */
            .bill-wrapper {
                display: block !important;
                width: 100% !important;
            }

            /* 4. බිල් කොටස ඇතුළත දේවල් පෙනීම තහවුරු කර, එය මැදට ගන්න */
            .bill-box {
                display: block !important;
                visibility: visible !important;
                width: 80mm !important; /* POS / A4 ඕනෑම එකකට ගැළපේ */
                margin: 0 auto !important;
                padding: 10px !important;
                box-shadow: none !important;
                border: none !important;
                background: #ffffff !important;
                color: #000000 !important;
            }

            /* 5. අකුරු සහ ඉරි තද කළු පාටින් පෙන්වීම */
            .bill-box * {
                color: #000000 !important;
            }
            .bill-header h2, .bill-details b, .total-row-label, .total-row-value {
                color: #000000 !important;
            }
            .bill-table th, .bill-table td {
                border-bottom: 1px solid #000000 !important;
            }
            .bill-header {
                border-bottom: 2px dashed #000000 !important;
            }
            .total-row-container {
                border-top: 2px dashed #000000 !important;
            }
        }
    </style>
</head>
<body>

    <?php include('includes/sidebar.php'); ?>

    <div class="main-content">
        <header>
            <h2><i class="fa-solid fa-wheat-awn" style="color: var(--accent-blue);"></i> Paddy Procurement</h2>
            <span>Welcome, <strong>Admin</strong></span>
        </header>
        
        <?php if ($show_bill): ?>
            <div class="alert-success">
                <i class="fa-solid fa-circle-check" style="color: var(--accent-green);"></i> Procurement Saved! Farmer: <strong><?php echo $bill_data['name']; ?></strong>
            </div>
        <?php endif; ?>

        <div class="bill-wrapper">
            
            <div class="panel-box">
                <h3>📋 Buy Paddy from Farmer (වී මිලදී ගැනීමේ පෝරමය)</h3>
                
                <form action="" method="POST">
                    <div class="form-grid-2">
                        <div class="form-group">
                            <label>Farmer Name <span>(ගොවියාගේ නම)</span></label>
                            <input type="text" name="farmer_name" class="form-control" placeholder="E.g. H.P. Perera" required>
                        </div>
                        <div class="form-group">
                            <label>Farmer Phone <span>(දුරකථන අංකය)</span></label>
                            <input type="text" name="farmer_phone" class="form-control" placeholder="E.g. 0771234567" required>
                        </div>
                    </div>

                    <div class="form-grid-2">
                        <div class="form-group">
                            <label>Paddy Type <span>(වී වර්ගය)</span></label>
                            <select name="rice_type" class="form-control">
                                <option value="Nadu">Nadu</option>
                                <option value="Samba">Samba</option>
                                <option value="Kiri Samba">Kiri Samba</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Weight (KG) <span>(බර කිලෝග්‍රෑම්)</span></label>
                            <input type="number" id="weight" name="weight_kg" step="0.01" class="form-control" placeholder="0.00" required oninput="calculateTotal()">
                        </div>
                    </div>

                    <div class="form-grid-2">
                        <div class="form-group">
                            <label>Moisture % <span>(තෙතමනය)</span></label>
                            <input type="number" name="moisture" step="0.1" class="form-control" placeholder="E.g. 14">
                        </div>
                        <div class="form-group">
                            <label>Price Per KG <span>(1KG මිල - Rs.)</span></label>
                            <input type="number" id="price" name="price_per_kg" step="0.01" class="form-control" placeholder="0.00" required oninput="calculateTotal()">
                        </div>
                    </div>

                    <div class="form-footer">
                        <div class="live-total-box">
                            Estimated Cost: <span>රු. <span id="live_total">0.00</span></span>
                        </div>
                        <button type="submit" name="save_purchase" class="btn-submit">
                            Save Purchase <i class="fa-solid fa-floppy-disk"></i>
                        </button>
                    </div>
                </form>
            </div>

            <div>
                <?php if ($show_bill): 
                    $wa_msg = "🌾 *MINSADA RICE MILL* 🌾\n\n"
                            . "Hi *" . $bill_data['name'] . "*,\n"
                            . "Your paddy purchase has been processed successfully!\n\n"
                            . "📄 *Bill No:* #" . $bill_data['bill_no'] . "\n"
                            . "🌾 *Paddy:* " . $bill_data['type'] . "\n"
                            . "⚖️ *Weight:* " . number_format($bill_data['weight'], 2) . " KG\n"
                            . "💧 *Moisture:* " . $bill_data['moisture'] . "%\n"
                            . "💵 *Rate:* Rs." . number_format($bill_data['price'], 2) . "\n\n"
                            . "💰 *Total Paid:* LKR *" . number_format($bill_data['total'], 2) . "*\n\n"
                            . "Thank you for doing business with us! 🙏";
                ?>
                    <div class="bill-box">
                        <div class="bill-header">
                            <h2>MINSADA RICE MILL</h2>
                            <p>Official Purchase Invoice (GRN)</p>
                        </div>
                        <div class="bill-details">
                            <div><span>Invoice No:</span> <b>#<?php echo $bill_data['bill_no']; ?></b></div>
                            <div><span>Date:</span> <b><?php echo $bill_data['date']; ?></b></div>
                            <div><span>Farmer Name:</span> <b><?php echo $bill_data['name']; ?></b></div>
                            <div><span>Phone No:</span> <b id="display_phone"><?php echo $bill_data['phone']; ?></b></div>
                        </div>
                        <table class="bill-table">
                            <thead>
                                <tr>
                                    <th>Paddy Type</th>
                                    <th>Qty (KG)</th>
                                    <th>Moisture</th>
                                    <th style="text-align: right;">Rate (KG)</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><b><?php echo $bill_data['type']; ?></b></td>
                                    <td><?php echo number_format($bill_data['weight'], 2); ?></td>
                                    <td><?php echo $bill_data['moisture']; ?>%</td>
                                    <td style="text-align: right; font-weight: 600;">රු. <?php echo number_format($bill_data['price'], 2); ?></td>
                                </tr>
                            </tbody>
                        </table>
                        
                        <div class="total-row-container">
                            <span class="total-row-label">Total:</span>
                            <span class="total-row-value">රු. <?php echo number_format($bill_data['total'], 2); ?></span>
                        </div>
                        
                        <button class="print-btn" onclick="window.print()"><i class="fa-solid fa-print"></i> Print Receipt</button>
                        <button id="wa_btn" onclick="sendWhatsAppMessage()" class="whatsapp-btn">
                            <i class="fa-brands fa-whatsapp"></i> Send via WhatsApp
                        </button>
                    </div>
                <?php else: ?>
                    <div class="bill-box" style="text-align: center; color: #64748b; padding: 45px 20px; font-weight: 500; font-size: 13px;">
                        <i class="fa-solid fa-circle-info" style="font-size: 24px; color: var(--accent-green); margin-bottom: 12px; display: block;"></i>
                        Farmer's printed invoice will load here once saved.
                    </div>
                <?php endif; ?>
            </div>

        </div> <!-- .bill-wrapper end -->

        <!-- 🌾 PADDY PROCUREMENT HISTORY TABLE START -->
        <br><br>
        <div class="panel-box" style="margin-top: 10px;">
            <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--border-dark); padding-bottom: 12px; margin-bottom: 20px;">
                <h3 style="border-bottom: none; margin: 0; padding: 0;">
                    <i class="fa-solid fa-clock-rotate-left" style="color: var(--accent-blue);"></i> Procurement History (මිලදී ගැනීමේ ඉතිහාසය)
                </h3>
                
                <!-- Live Search Control -->
                <div style="position: relative; width: 300px;">
                    <i class="fa-solid fa-magnifying-glass" style="position: absolute; left: 12px; top: 11px; color: var(--text-muted); font-size: 13px;"></i>
                    <input type="text" id="tableSearch" onkeyup="searchTable()" class="form-control" placeholder="Search farmer, phone or type..." style="padding-left: 35px; height: 36px;">
                </div>
            </div>

            <div style="overflow-x: auto;">
                <table style="width: 100%; border-collapse: collapse; text-align: left; font-size: 13px;">
                    <thead>
                        <tr style="border-bottom: 2px solid var(--border-dark); color: var(--text-muted); text-transform: uppercase; font-size: 11px; letter-spacing: 0.5px;">
                            <th style="padding: 12px 8px;">Bill No</th>
                            <th style="padding: 12px 8px;">Date & Time</th>
                            <th style="padding: 12px 8px;">Farmer Name</th>
                            <th style="padding: 12px 8px;">Phone No</th>
                            <th style="padding: 12px 8px;">Paddy Type</th>
                            <th style="padding: 12px 8px; text-align: right;">Weight (KG)</th>
                            <th style="padding: 12px 8px; text-align: center;">Moisture</th>
                            <th style="padding: 12px 8px; text-align: right;">Rate (Rs)</th>
                            <th style="padding: 12px 8px; text-align: right; color: var(--accent-green);">Total Amount</th>
                        </tr>
                    </thead>
                    <tbody id="historyTableBody">
                        <?php
                        $history_query = "SELECT * FROM paddy_procurement ORDER BY id DESC";
                        $history_result = $conn->query($history_query);

                        if ($history_result && $history_result->num_rows > 0) {
                            while ($row = $history_result->fetch_assoc()) {
                                $formatted_bill_no = str_pad($row['id'], 5, '0', STR_PAD_LEFT);
                                $date_field = isset($row['created_at']) ? $row['created_at'] : (isset($row['date']) ? $row['date'] : date('Y-m-d H:i:s'));
                                $db_date = date('Y-m-d h:i A', strtotime($date_field)); 
                                ?>
                                <tr style="border-bottom: 1px solid var(--border-dark); color: var(--text-light); transition: background 0.2s;">
                                    <td style="padding: 14px 8px; font-weight: 700; color: var(--accent-blue);">#<?php echo $formatted_bill_no; ?></td>
                                    <td style="padding: 14px 8px; color: var(--text-muted); font-size: 12px;"><?php echo $db_date; ?></td>
                                    <td style="padding: 14px 8px; font-weight: 600;"><?php echo htmlspecialchars($row['farmer_name']); ?></td>
                                    <td style="padding: 14px 8px; color: var(--text-muted);"><?php echo htmlspecialchars($row['farmer_phone']); ?></td>
                                    <td style="padding: 14px 8px;"><span style="background: rgba(56, 189, 248, 0.1); color: var(--accent-blue); padding: 4px 8px; border-radius: 4px; font-size: 11px; font-weight: 600;"><?php echo $row['rice_type']; ?></span></td>
                                    <td style="padding: 14px 8px; text-align: right; font-weight: 600;"><?php echo number_format($row['weight_kg'], 2); ?> KG</td>
                                    <td style="padding: 14px 8px; text-align: center; color: #fbbf24; font-weight: 600;"><?php echo $row['moisture_percentage']; ?>%</td>
                                    <td style="padding: 14px 8px; text-align: right;">Rs. <?php echo number_format($row['price_per_kg'], 2); ?></td>
                                    <td style="padding: 14px 8px; text-align: right; font-weight: 700; color: var(--accent-green);">Rs. <?php echo number_format($row['total_amount'], 2); ?></td>
                                </tr>
                                <?php
                            }
                        } else {
                            ?>
                            <tr>
                                <td colspan="9" style="text-align: center; padding: 40px; color: var(--text-muted);">
                                    <i class="fa-solid fa-folder-open" style="font-size: 24px; display: block; margin-bottom: 10px;"></i>
                                    තවමත් මිලදී ගැනීම් සිදු කර නොමැත. (No procurement history found)
                                </td>
                            </tr>
                            <?php
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
        <!-- 🌾 PADDY PROCUREMENT HISTORY TABLE END -->

    </div> <!-- .main-content end -->

    <script>
        function calculateTotal() {
            const weight = parseFloat(document.getElementById('weight').value) || 0;
            const price = parseFloat(document.getElementById('price').value) || 0;
            const total = weight * price;
            document.getElementById('live_total').innerText = total.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        }

        function sendWhatsAppMessage() {
            let phone = "<?php echo isset($bill_data['phone']) ? $bill_data['phone'] : ''; ?>";
            let rawMessage = <?php echo isset($wa_msg) ? json_encode($wa_msg) : '""'; ?>;
            const waBtn = document.getElementById('wa_btn');

            phone = phone.replace(/[^0-9]/g, '');
            if (phone.length === 10 && phone.startsWith('0')) { phone = '94' + phone.substring(1); }
            if (phone === '') { alert("දුරකථන අංකයක් සොයාගත නොහැක!"); return; }

            waBtn.disabled = true;
            waBtn.innerHTML = "⏳ Sending...";

            fetch('http://localhost:3000/send', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ phone: phone, message: rawMessage })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert("✅ WhatsApp Message Send!");
                    waBtn.innerHTML = "✅ Message Sent!";
                } else {
                    alert("❌ Error: " + data.error);
                    waBtn.disabled = false;
                    waBtn.innerHTML = "<i class='fa-brands fa-whatsapp'></i> Send via WhatsApp";
                }
            })
            .catch(error => {
                alert("❌ Gateway Error! CMD Server එක පරීක්ෂා කරන්න.");
                waBtn.disabled = false;
                waBtn.innerHTML = "<i class='fa-brands fa-whatsapp'></i> Send via WhatsApp";
            });
        }

        // 🔍 Table එක ඇතුළේ Live Search කිරීමට
        function searchTable() {
            const input = document.getElementById("tableSearch");
            const filter = input.value.toLowerCase();
            const tableBody = document.getElementById("historyTableBody");
            const rows = tableBody.getElementsByTagName("tr");

            for (let i = 0; i < rows.length; i++) {
                const nameTd = rows[i].getElementsByTagName("td")[2];
                const phoneTd = rows[i].getElementsByTagName("td")[3];
                const typeTd = rows[i].getElementsByTagName("td")[4];

                if (nameTd || phoneTd || typeTd) {
                    const nameText = nameTd.textContent || nameTd.innerText;
                    const phoneText = phoneTd.textContent || phoneTd.innerText;
                    const typeText = typeTd.textContent || typeTd.innerText;

                    if (
                        nameText.toLowerCase().indexOf(filter) > -1 || 
                        phoneText.toLowerCase().indexOf(filter) > -1 ||
                        typeText.toLowerCase().indexOf(filter) > -1
                    ) {
                        rows[i].style.display = "";
                    } else {
                        rows[i].style.display = "none";
                    }
                }
            }
        }
    </script>
</body>
</html>