<?php 
include('config/db.php');
include('config/auth.php');

if (!isset($_GET['id'])) {
    die("Error: Invoice ID is missing.");
}

$sales_id = mysqli_real_escape_string($conn, $_GET['id']);

// 1. බිලේ ප්‍රධාන විස්තර ටේබල් එකෙන් ගන්නවා
$sales_query = $conn->query("SELECT * FROM sales WHERE id='$sales_id'");
if ($sales_query->num_rows == 0) {
    die("Error: Invoice not found.");
}
$sale = $sales_query->fetch_assoc();

// 2. බිලට අයිති හාල් වර්ග සහ ප්‍රමාණයන් (Items) ගන්නවා
$items_query = $conn->query("SELECT si.*, rs.rice_type, rs.bag_weight_kg 
                             FROM sales_items si 
                             JOIN rice_stock rs ON si.rice_stock_id = rs.id 
                             WHERE si.sales_id = '$sales_id'");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice #<?php echo $sale['id']; ?> - Minsada Rice Mill</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; color: #1e293b; background: #ffffff; margin: 0; padding: 20px; font-size: 14px; }
        .invoice-box { max-width: 800px; margin: auto; padding: 30px; border: 1px solid #e2e8f0; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.02); }
        
        /* බිලේ ඉහළ කොටස - මෝලේ විස්තර */
        .invoice-header { display: flex; justify-content: space-between; align-items: flex-start; border-bottom: 2px solid #f1f5f9; padding-bottom: 20px; margin-bottom: 20px; }
        .mill-details h1 { margin: 0 0 6px 0; font-size: 26px; font-weight: 700; color: #0f172a; text-transform: uppercase; letter-spacing: 0.5px; }
        .mill-details p { margin: 3px 0; color: #64748b; font-size: 13.5px; }
        
        .invoice-title { text-align: right; }
        .invoice-title h2 { margin: 0 0 8px 0; font-size: 22px; font-weight: 700; color: #10b981; text-transform: uppercase; }
        .invoice-title p { margin: 4px 0; color: #334155; font-weight: 500; }
        .invoice-title span { color: #64748b; font-weight: 400; }

        /* පාරිභෝගික විස්තර */
        .customer-section { margin-bottom: 25px; background: #f8fafc; padding: 15px; border-radius: 6px; border-left: 4px solid #10b981; }
        .customer-section h4 { margin: 0 0 6px 0; font-size: 12px; text-transform: uppercase; color: #64748b; letter-spacing: 0.5px; }
        .customer-section p { margin: 3px 0; font-size: 14px; font-weight: 600; color: #1e293b; }
        .customer-section span { font-weight: 400; color: #475569; }

        /* බිල්පත් ටේබලය */
        table { width: 100%; border-collapse: collapse; text-align: left; margin-bottom: 25px; }
        th { background: #f1f5f9; padding: 12px; font-weight: 600; font-size: 12.5px; color: #475569; text-transform: uppercase; border-bottom: 2px solid #cbd5e1; }
        td { padding: 12px; border-bottom: 1px solid #f1f5f9; color: #334155; font-size: 13.5px; }
        tr:last-child td { border-bottom: none; }

        /* මුදල් එකතුව පෙන්වන කොටස */
        .totals-section { display: flex; justify-content: flex-end; margin-top: 15px; }
        .totals-table { width: 250px; }
        .totals-table td { padding: 8px 0; border-bottom: 1px solid #f1f5f9; font-size: 14px; }
        .totals-table tr.grand-total td { font-size: 16px; font-weight: 700; color: #10b981; border-bottom: none; padding-top: 12px; }

        /* බිලේ යට කොටස */
        .invoice-footer { text-align: center; margin-top: 40px; padding-top: 20px; border-top: 1px solid #f1f5f9; color: #94a3b8; font-size: 12px; }
        
        /* ප්‍රින්ට් බටන් එක (ස්ක්‍රීන් එකේ විතරක් පේන) */
        .print-btn-container { max-width: 800px; margin: 0 auto 15px auto; text-align: right; }
        .btn-print { background: #10b981; color: white; padding: 10px 20px; border: none; border-radius: 6px; font-size: 14px; font-weight: 600; cursor: pointer; transition: all 0.2s; box-shadow: 0 4px 6px rgba(16,185,129,0.15); }
        .btn-print:hover { background: #059669; }

        /* ප්‍රින්ට් කරද්දී බටන් එක හංගන්න */
        @media print {
            body { padding: 0; }
            .invoice-box { border: none; box-shadow: none; padding: 0; }
            .print-btn-container { display: none; }
        }
    </style>
</head>
<body>

    <div class="print-btn-container">
        <button onclick="window.print();" class="btn-print">🖨️ Print Invoice</button>
    </div>

    <div class="invoice-box">
        
        <div class="invoice-header">
            <div class="mill-details">
                <h1>Minsada Rice Mill</h1>
                <p>📍 No. 123, Rice Mill Road, Polonnaruwa , Sri Lanka.</p>
                <p>📞 Phone: 077 123 4567 / 025 222 3344</p>
                <p>✉️ Email: info@minsadaricemill.com</p>
            </div>
            <div class="invoice-title">
                <h2>INVOICE</h2>
                <p>Invoice No: <span>#<?php echo $sale['id']; ?></span></p>
                <p>Date: <span><?php echo date('Y-m-d h:i A', strtotime($sale['invoice_date'])); ?></span></p>
            </div>
        </div>

        <div class="customer-section">
            <h4>Invoiced To (බිල ලබන්නා):</h4>
            <p><?php echo $sale['customer_name']; ?></p>
            <?php if(!empty($sale['customer_phone'])): ?>
                <p style="font-weight: 400; margin-top: 4px;">📱 Phone: <span><?php echo $sale['customer_phone']; ?></span></p>
            <?php endif; ?>
        </div>

        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Rice Type (හාල් වර්ගය)</th>
                    <th>Bag Weight</th>
                    <th style="text-align: center;">Qty (මලු ගණන)</th>
                    <th style="text-align: right;">Price Per Bag</th>
                    <th style="text-align: right;">Total Amount</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $count = 1;
                while($item = $items_query->fetch_assoc()) {
                    echo "<tr>
                            <td>{$count}</td>
                            <td style='font-weight: 600;'>{$item['rice_type']}</td>
                            <td>{$item['bag_weight_kg']} kg</td>
                            <td style='text-align: center;'>{$item['quantity_bags']} Bags</td>
                            <td style='text-align: right;'>Rs. " . number_format($item['price_per_bag'], 2) . "</td>
                            <td style='text-align: right; font-weight: 500;'>Rs. " . number_format($item['sub_total'], 2) . "</td>
                          </tr>";
                    $count++;
                }
                ?>
            </tbody>
        </table>

        <div class="totals-section">
            <table class="totals-table">
                <tr>
                    <td>Sub Total:</td>
                    <td style="text-align: right;">Rs. <?php echo number_format($sale['total_amount'], 2); ?></td>
                </tr>
                <tr class="grand-total">
                    <td>Net Amount:</td>
                    <td style="text-align: right;">Rs. <?php echo number_format($sale['net_amount'], 2); ?></td>
                </tr>
            </table>
        </div>

        <div class="invoice-footer">
            <p>Thank you for your business! Come again.</p>
            <p style="font-size: 11px; margin-top: 5px; color: #cbd5e1;">Software Powered by R.M.M.M.B Group</p>
        </div>

    </div>

</body>
</html>