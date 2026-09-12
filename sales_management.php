<?php 
include('config/db.php');
include('config/auth.php');

// 1. බිලක් දාද්දී වැඩ කරන PHP Code එක
if (isset($_POST['create_invoice'])) {
    $customer_name = mysqli_real_escape_string($conn, $_POST['customer_name']);
    $customer_phone = mysqli_real_escape_string($conn, $_POST['customer_phone']);
    $rice_stock_id = mysqli_real_escape_string($conn, $_POST['rice_stock_id']);
    $quantity_bags = (int)$_POST['quantity_bags'];
    $invoice_date = date('Y-m-d H:i:s'); // Invoice Date එක මෙතනින් සෙට් කරනවා

    // තෝරපු හාල් වර්ගයේ විස්තර සහ stock එක database එකෙන් ගන්නවා
    $stock_res = $conn->query("SELECT * FROM rice_stock WHERE id='$rice_stock_id'");
    if ($stock_res && $stock_res->num_rows > 0) {
        $stock = $stock_res->fetch_assoc();
        
        // ඉල්ලන බෑග් ගණන stock එකේ තියෙනවද බලනවා
        if ($stock['available_bag_count'] >= $quantity_bags) {
            $price_per_bag = $stock['price_per_bag'];
            $total_amount = $price_per_bag * $quantity_bags;

            // Sales table එකට සේව් කරනවා (invoice_date එකත් එක්කම)
            $sql_sales = "INSERT INTO sales (customer_name, customer_phone, total_amount, net_amount, payment_status, invoice_date) 
                          VALUES ('$customer_name', '$customer_phone', '$total_amount', '$total_amount', 'Paid', '$invoice_date')";
            
            if ($conn->query($sql_sales) === TRUE) {
                $sales_id = $conn->insert_id; // අලුතින් හැදුණු Bill ID එක ගන්නවා

                // Sales Items table එකට සේව් කරනවා
                $conn->query("INSERT INTO sales_items (sales_id, rice_stock_id, quantity_bags, price_per_bag, sub_total) 
                              VALUES ('$sales_id', '$rice_stock_id', '$quantity_bags', '$price_per_bag', '$total_amount')");

                // Rice Stock එකෙන් විකුණපු බෑග් ගණන අඩු කරනවා (UPDATE)
                $conn->query("UPDATE rice_stock 
                              SET available_bag_count = available_bag_count - $quantity_bags, 
                                  total_weight_kg = total_weight_kg - ($quantity_bags * bag_weight_kg) 
                              WHERE id='$rice_stock_id'");

                echo "<script>alert('Invoice created successfully! Total: Rs. " . number_format($total_amount, 2) . "'); window.location='sales_management.php';</script>";
            }
        } else {
            echo "<script>alert('Error: Not enough bags available in stock! (Available: " . $stock['available_bag_count'] . " Bags)');</script>";
        }
    }
}

// 2. සජීවී විකුණුම් සාරාංශ දත්ත (Summary Dashboard Calculations)
$sales_stats = $conn->query("SELECT COUNT(id) as total_bills, SUM(net_amount) as total_rev FROM sales")->fetch_assoc();
$total_bills = $sales_stats['total_bills'] ?? 0;
$total_revenue = $sales_stats['total_rev'] ?? 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sales & Invoicing - Minsada Rice Mill</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #0f172a !important; color: #f1f5f9 !important; margin: 0; }
        .main-content { background-color: #0f172a !important; min-height: 100vh; padding: 30px; }
        
        header { padding-bottom: 20px; border-bottom: 1px solid #1e293b; margin-bottom: 30px; display: flex; justify-content: space-between; align-items: center; }
        header h2 { font-size: 24px; font-weight: 700; color: #ffffff; margin: 0; }
        header span { color: #94a3b8; font-size: 14px; }
        header strong { color: #38bdf8; }

        /* Summary Dashboard Cards */
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 20px; margin-bottom: 30px; }
        .stat-card { background: #1e293b; border: 1px solid #334155; padding: 22px; border-radius: 14px; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 4px 15px rgba(0,0,0,0.1); }
        .stat-card h4 { font-size: 12px; color: #94a3b8; text-transform: uppercase; margin: 0 0 6px 0; letter-spacing: 0.5px; }
        .stat-card .value { font-size: 24px; font-weight: 700; color: #ffffff; }

        /* Premium Box Container */
        .premium-box { background: #1e293b !important; border-radius: 16px !important; padding: 24px !important; border: 1px solid #334155 !important; box-shadow: 0 10px 25px rgba(0,0,0,0.2) !important; margin-bottom: 30px; }
        .box-title { font-size: 16px; font-weight: 600; color: #ffffff; margin-bottom: 20px; display: flex; align-items: center; gap: 8px; border-bottom: 1px solid #334155; padding-bottom: 12px; }

        /* Premium Input Elements */
        .form-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 18px; }
        .form-group { display: flex; flex-direction: column; }
        .form-group label { font-size: 12px; font-weight: 600; color: #94a3b8; margin-bottom: 8px; text-transform: uppercase; letter-spacing: 0.3px; }
        .form-group input, .form-group select {
            padding: 12px 14px; background-color: #0f172a; border: 1px solid #334155; border-radius: 8px; color: #ffffff; font-size: 14px; transition: all 0.2s;
        }
        .form-group input:focus, .form-group select:focus { outline: none; border-color: #10b981; box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.1); }
        ::placeholder { color: #475569; }

        .btn-submit { padding: 12px 28px; background: linear-gradient(135deg, #10b981 0%, #059669 100%); border: none; border-radius: 8px; color: #ffffff; font-size: 14px; font-weight: 600; cursor: pointer; transition: all 0.2s ease; margin-top: 15px; box-shadow: 0 4px 12px rgba(16, 185, 129, 0.2); }
        .btn-submit:hover { transform: translateY(-1.5px); box-shadow: 0 6px 18px rgba(16, 185, 129, 0.3); }

        /* Premium Table Style */
        .table-wrapper { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; text-align: left; }
        th { background-color: #0f172a !important; padding: 14px 16px !important; font-size: 12px !important; font-weight: 600 !important; color: #94a3b8 !important; border-bottom: 2px solid #334155 !important; text-transform: uppercase; }
        td { padding: 14px 16px !important; font-size: 13.5px !important; color: #cbd5e1 !important; border-bottom: 1px solid #334155 !important; }
        tr:hover td { background-color: #243249; color: #ffffff; }
        
        /* Accents & Print Button */
        .badge-paid { padding: 4px 8px; border-radius: 6px; font-size: 11px; font-weight: 600; text-transform: uppercase; background: rgba(16, 185, 129, 0.1); color: #10b981; border: 1px solid rgba(16, 185, 129, 0.2); }
        .text-id { color: #64748b; font-weight: 500; }
        .btn-print { background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%); color: white; padding: 8px 16px; text-decoration: none; border-radius: 6px; font-size: 12.5px; font-weight: 600; display: inline-flex; align-items: center; gap: 4px; transition: all 0.2s; box-shadow: 0 2px 6px rgba(59, 130, 246, 0.2); }
        .btn-print:hover { transform: translateY(-1px); box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3); }
    </style>
</head>
<body>

    <?php include('includes/sidebar.php'); ?>

    <div class="main-content">
        <header>
            <h2>🛍️ Sales & Invoicing</h2>
            <span>Welcome, <strong>Admin</strong></span>
        </header>

        <div class="stats-grid">
            <div class="stat-card" style="border-left: 4px solid #10b981;">
                <div>
                    <h4>Total Revenue (මුළු ආදායම)</h4>
                    <div class="value" style="color: #10b981;">Rs. <?php echo number_format($total_revenue, 2); ?></div>
                </div>
                <div style="font-size: 28px;">💰</div>
            </div>
            <div class="stat-card" style="border-left: 4px solid #38bdf8;">
                <div>
                    <h4>Total Invoices Issued</h4>
                    <div class="value"><?php echo number_format($total_bills); ?> <span style="font-size:14px; color:#94a3b8;">Bills</span></div>
                </div>
                <div style="font-size: 28px;">📄</div>
            </div>
        </div>

        <div class="content-wrapper">
            
            <div class="premium-box">
                <div class="box-title">➕ Create New Invoice (අලුත් බිලක් නිකුත් කිරීම)</div>
                <form action="sales_management.php" method="POST">
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Customer Name / Shop</label>
                            <input type="text" name="customer_name" required placeholder="E.g. Gunadasa Stores">
                        </div>
                        <div class="form-group">
                            <label>Customer Phone</label>
                            <input type="text" name="customer_phone" placeholder="E.g. 07XXXXXXXX">
                        </div>
                        <div class="form-group">
                            <label>Select Rice Item (හාල් වර්ගය)</label>
                            <select name="rice_stock_id" required>
                                <option value="">-- Select Rice Item --</option>
                                <?php 
                                $stock_res = $conn->query("SELECT * FROM rice_stock WHERE available_bag_count > 0");
                                while($st = $stock_res->fetch_assoc()) {
                                    echo "<option value='{$st['id']}'>{$st['rice_type']} - (Available: {$st['available_bag_count']} Bags) - Rs. " . number_format($st['price_per_bag'], 2) . "/Bag</option>";
                                }
                                ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Quantity (Bags Count)</label>
                            <input type="number" name="quantity_bags" min="1" required placeholder="E.g. 5">
                        </div>
                    </div>
                    <button type="submit" name="create_invoice" class="btn-submit">Generate & Print Invoice 📑</button>
                </form>
            </div>

            <div class="premium-box">
                <div class="box-title">📋 Recent Sales History (මෑතකාලීන විකුණුම් ඉතිහාසය)</div>
                <div class="table-wrapper">
                    <table>
                        <thead>
                            <tr>
                                <th>Bill ID</th>
                                <th>Date & Time</th>
                                <th>Customer Name</th>
                                <th>Total Amount</th>
                                <th>Status</th>
                                <th style="text-align: center;">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $sales_res = $conn->query("SELECT * FROM sales ORDER BY id DESC");
                            if ($sales_res && $sales_res->num_rows > 0) {
                                while($sale = $sales_res->fetch_assoc()) {
                                    // ඩේටාබේස් එකේ තියෙන වේලාව ලස්සනට පෙන්වන්න
                                    $formatted_date = date('Y-m-d h:i A', strtotime($sale['invoice_date']));
                                    echo "<tr>
                                            <td class='text-id'>#{$sale['id']}</td>
                                            <td>{$formatted_date}</td>
                                            <td style='font-weight:500; color:#fff;'>{$sale['customer_name']}</td>
                                            <td style='font-weight:600; color:#10b981;'>Rs. " . number_format($sale['net_amount'], 2) . "</td>
                                            <td><span class='badge-paid'>{$sale['payment_status']}</span></td>
                                            <td style='text-align: center;'>
                                                <a href='print_invoice.php?id={$sale['id']}' target='_blank' class='btn-print'>🖨️ Print Bill</a>
                                            </td>
                                          </tr>";
                                }
                            } else {
                                echo "<tr><td colspan='6' style='text-align:center; color:#94a3b8; padding:30px;'>No sales recorded yet.</td></tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>

</body>
</html>