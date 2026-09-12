<?php 
session_start();
include('config/db.php');
include('config/auth.php');

// ඇඩ්මින් හැර වෙනත් කෙනෙක් ආවොත් කෙලින්ම ඩෑෂ්බෝඩ් එකට හරවා යැවීම (ඔයාගේ ලොජික් එක)
if ($_SESSION['user_role'] !== 'Admin') {
    echo "<script>alert('Access Denied! Only Admin can view reports.'); window.location='dashboard.php';</script>";
    exit();
}

// 1. [ORIGINAL LOGIC] මුළු ව්‍යාපාරයේම සාරාංශය ලබාගැනීම
$total_farmers_paddy = $conn->query("SELECT SUM(total_amount) as total_spent FROM paddy_stock")->fetch_assoc()['total_spent'] ?? 0;
$total_earned_sales = $conn->query("SELECT SUM(net_amount) as total_income FROM sales")->fetch_assoc()['total_income'] ?? 0;
$total_employees_count = $conn->query("SELECT COUNT(id) as total_emp FROM employees")->fetch_assoc()['total_emp'] ?? 0;

// 2. [EXPENSES LOGIC] වියදම් දත්ත ලබා ගැනීම
$current_month = date('Y-m');
$month_exp_q = $conn->query("SELECT SUM(amount) as total FROM expenses WHERE expense_date LIKE '$current_month%'");
$month_exp = $month_exp_q->fetch_assoc()['total'] ?? 0;

// Category අනුව වියදම් (Category Breakdown)
$cat_summary_q = $conn->query("SELECT category, SUM(amount) as total_amount, COUNT(id) as count 
                               FROM expenses 
                               GROUP BY category 
                               ORDER BY total_amount DESC");

// මාසික වියදම් ඉතිහාසය
$monthly_trend_q = $conn->query("SELECT DATE_FORMAT(expense_date, '%Y-%m') as month, SUM(amount) as total_amount 
                                 FROM expenses 
                                 GROUP BY month 
                                 ORDER BY month DESC 
                                 LIMIT 6");

// 3. [ORIGINAL LOGIC] පසුගිය දින 30 සඳහා දිනපතා විකිණුම් ආදායම (ප්‍රස්තාරය සඳහා)
$chart_q = $conn->query("SELECT DATE(invoice_date) as sales_date, SUM(net_amount) as daily_total 
                         FROM sales 
                         WHERE invoice_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
                         GROUP BY DATE(invoice_date)
                         ORDER BY sales_date ASC");

$chart_dates = [];
$chart_sales = [];

while($row = $chart_q->fetch_assoc()) {
    $chart_dates[] = date('M d', strtotime($row['sales_date'])); 
    $chart_sales[] = $row['daily_total'];
}

$js_dates = json_encode($chart_dates);
$js_sales = json_encode($chart_sales);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports & Analytics - Minsada Rice Mill</title>
    <link rel="stylesheet" href="assets/css/style.css">
    
    <!-- Chart.js Library -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <!-- Inter Premium Font -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    <style>
        body { font-family: 'Inter', sans-serif; background-color: #0f172a !important; color: #f1f5f9 !important; margin: 0; }
        .main-content { background-color: #0f172a !important; min-height: 100vh; padding: 30px; }
        
        header { padding-bottom: 20px; border-bottom: 1px solid #1e293b; margin-bottom: 30px; display: flex; justify-content: space-between; align-items: center; }
        header h2 { font-size: 24px; font-weight: 700; color: #ffffff; margin: 0; }
        .user-profile { font-size: 14px; color: #94a3b8; }
        .user-profile strong { color: #38bdf8; }

        h3 { font-size: 18px; font-weight: 600; color: #ffffff; margin: 30px 0 15px 0; }

        /* Report Info Box Grid */
        .report-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 20px; margin-bottom: 30px; }
        .report-box { background: #1e293b; padding: 22px; border-radius: 14px; text-align: center; border: 1px solid #334155; box-shadow: 0 4px 15px rgba(0,0,0,0.2); }
        .report-box h4 { color: #94a3b8; font-size: 12px; text-transform: uppercase; margin: 0 0 10px 0; font-weight: 600; letter-spacing: 0.5px; }
        .report-box .amount { font-size: 24px; font-weight: 700; }

        /* Layout Grid for Sub Reports */
        .sub-report-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 25px; margin-top: 25px; }
        @media (max-width: 900px) { .sub-report-grid { grid-template-columns: 1fr; } }

        /* Premium Card Container */
        .premium-card { background: #1e293b !important; border-radius: 16px !important; padding: 24px !important; border: 1px solid #334155 !important; box-shadow: 0 10px 25px rgba(0,0,0,0.2) !important; }
        .card-title { font-size: 15px; font-weight: 600; color: #ffffff; margin-bottom: 18px; display: flex; align-items: center; gap: 8px; border-bottom: 1px solid #334155; padding-bottom: 12px; }

        /* Progress Bar (Category Expenses) */
        .progress-wrapper { margin-bottom: 16px; }
        .progress-info { display: flex; justify-content: space-between; font-size: 13px; margin-bottom: 6px; color: #cbd5e1; }
        .progress-bar-bg { background: #0f172a; height: 8px; border-radius: 99px; overflow: hidden; }
        .progress-bar-fill { background: linear-gradient(90deg, #f43f5e, #fda4af); height: 100%; border-radius: 99px; }

        /* Table Styles */
        table { width: 100%; border-collapse: collapse; }
        th { background-color: #0f172a !important; padding: 12px 14px !important; font-size: 12px !important; font-weight: 600 !important; color: #94a3b8 !important; border-bottom: 2px solid #334155 !important; text-transform: uppercase; text-align: left; }
        td { padding: 12px 14px !important; font-size: 13.5px !important; color: #cbd5e1 !important; border-bottom: 1px solid #334155 !important; }
        tr:last-child td { border-bottom: none; }

        .badge-month { padding: 4px 10px; border-radius: 6px; font-size: 12px; font-weight: 600; background-color: rgba(56, 189, 248, 0.1); color: #38bdf8; border: 1px solid rgba(56, 189, 248, 0.15); }
    </style>
</head>
<body>

    <?php include('includes/sidebar.php'); ?>

    <div class="main-content">
        <header>
            <h2>📊 Reports & Analytics</h2>
            <div class="user-profile">
                <span>Welcome, <strong>Admin</strong></span>
            </div>
        </header>

        <div class="content-wrapper">
            <h3>Business Summary (ව්‍යාපාරික වාර්තා සාරාංශය)</h3>

            <!-- 4-Column Grid (ඔයාගේ පරණ 3 + අලුත් Expense Box එක) -->
            <div class="report-grid">
                <div class="report-box" style="border-top: 4px solid #ef4444;">
                    <h4>Total Investment on Paddy (වී මිලදී ගැනීමේ වියදම)</h4>
                    <div class="amount" style="color: #ef4444;">Rs. <?php echo number_format($total_farmers_paddy, 2); ?></div>
                </div>

                <div class="report-box" style="border-top: 4px solid #10b981;">
                    <h4>Total Income from Sales (විකිණුම් මුළු ආදායම)</h4>
                    <div class="amount" style="color: #10b981;">Rs. <?php echo number_format($total_earned_sales, 2); ?></div>
                </div>

                <!-- අලුතින් එකතු කල මේ මාසයේ දෛනික වියදම් එකතුව -->
                <div class="report-box" style="border-top: 4px solid #f43f5e;">
                    <h4>Total Expenses This Month (මේ මාසයේ මුළු වියදම්)</h4>
                    <div class="amount" style="color: #f43f5e;">Rs. <?php echo number_format($month_exp, 2); ?></div>
                </div>

                <div class="report-box" style="border-top: 4px solid #3b82f6;">
                    <h4>Total Registered Employees (සේවකයින් සංඛ්‍යාව)</h4>
                    <div class="amount" style="color: #3b82f6;"><?php echo $total_employees_count; ?></div>
                </div>
            </div>

            <!-- 30-Day Sales Line Chart Container (Premium Dark ස්ටයිල් කර ඇත) -->
            <div class="premium-card" style="margin-top: 30px;">
                <div class="card-title">
                    <span style="font-size: 16px;">📈</span> 30-Day Sales Performance Analytics (පසුගිය දින 30 විකිණුම් ප්‍රස්තාරය)
                </div>
                <div style="width: 100%; height: 320px;">
                    <canvas id="salesAnalyticsChart"></canvas>
                </div>
            </div>

            <!-- අලුත් කෑල්ල: Category Breakdown සහ Monthly Expense Trend දෙපැත්තට වැටෙන Grid එක -->
            <div class="sub-report-grid">
                
                <!-- වම් පැත්ත: Category Expenses -->
                <div class="premium-card">
                    <div class="card-title">🔍 Expense Breakdown by Category (වියදම් වර්ගීකරණය)</div>
                    <?php 
                    if ($cat_summary_q && $cat_summary_q->num_rows > 0) {
                        $max_amount = 1; $cat_data = [];
                        while($row = $cat_summary_q->fetch_assoc()) { $cat_data[] = $row; }
                        if(!empty($cat_data)) { $max_amount = $cat_data[0]['total_amount']; }

                        foreach($cat_data as $cat) {
                            $percentage = ($max_amount > 0) ? ($cat['total_amount'] / $max_amount) * 100 : 0;
                            ?>
                            <div class="progress-wrapper">
                                <div class="progress-info">
                                    <strong>📂 <?php echo $cat['category']; ?> <span style="font-weight:400; color:#94a3b8;">(<?php echo $cat['count']; ?>)</span></strong>
                                    <span style="font-weight: 600; color: #f43f5e;">Rs. <?php echo number_format($cat['total_amount'], 2); ?></span>
                                </div>
                                <div class="progress-bar-bg">
                                    <div class="progress-bar-fill" style="width: <?php echo $percentage; ?>%;"></div>
                                </div>
                            </div>
                            <?php
                        }
                    } else {
                        echo "<p style='color:#94a3b8; text-align:center; padding:20px;'>No expense records found.</p>";
                    }
                    ?>
                </div>

                <!-- දකුණු පැත්ත: Monthly History Table -->
                <div class="premium-card">
                    <div class="card-title">📅 Monthly Expense History (මාසික වියදම් ඉතිහාසය)</div>
                    <div style="overflow-x: auto;">
                        <table>
                            <thead>
                                <tr>
                                    <th>Month</th>
                                    <th>Total Expenses</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                if ($monthly_trend_q && $monthly_trend_q->num_rows > 0) {
                                    while($trend = $monthly_trend_q->fetch_assoc()) {
                                        echo "<tr>
                                                <td><span class='badge-month'>" . date('F Y', strtotime($trend['month'] . "-01")) . "</span></td>
                                                <td style='font-weight:600; color:#f43f5e;'>Rs. " . number_format($trend['total_amount'], 2) . "</td>
                                              </tr>";
                                    }
                                } else {
                                    echo "<tr><td colspan='2' style='text-align:center; color:#94a3b8; padding:20px;'>No monthly data available.</td></tr>";
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>

        </div>
    </div>

    <!-- Chart JavaScript (Premium Dark colors සෙට් කර ඇත) -->
    <script>
    document.addEventListener("DOMContentLoaded", function() {
        const ctx = document.getElementById('salesAnalyticsChart').getContext('2d');
        
        const dates = <?php echo $js_dates; ?>;
        const salesData = <?php echo $js_sales; ?>;

        new Chart(ctx, {
            type: 'line',
            data: {
                labels: dates,
                datasets: [{
                    label: 'Daily Sales (Rs.)',
                    data: salesData,
                    borderColor: '#38bdf8', /* Neon Soft Blue Line */
                    backgroundColor: 'rgba(56, 189, 248, 0.06)', /* Very light glow fill */
                    borderWidth: 3,
                    pointBackgroundColor: '#38bdf8',
                    pointRadius: 4,
                    pointHoverRadius: 6,
                    tension: 0.35,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: '#1e293b',
                        titleColor: '#ffffff',
                        bodyColor: '#38bdf8',
                        borderColor: '#334155',
                        borderWidth: 1,
                        callbacks: {
                            label: function(context) {
                                return ' Sales: Rs. ' + context.raw.toLocaleString();
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        grid: { display: false },
                        ticks: { color: '#94a3b8', font: { family: 'Inter, sans-serif' } }
                    },
                    y: {
                        grid: { color: '#334155' }, /* Dark grid lines */
                        ticks: {
                            color: '#94a3b8',
                            callback: function(value) { return 'Rs. ' + value.toLocaleString(); }
                        }
                    }
                }
            }
        });
    });
    </script>

</body>
</html>