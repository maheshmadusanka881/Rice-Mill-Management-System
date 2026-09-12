<?php 
include('config/db.php');   
include('config/auth.php'); 

// 1. Total Paddy Stock (KG) ගණනය කිරීම
$paddy_q = $conn->query("SELECT SUM(weight_kg) as total_paddy FROM paddy_stock WHERE status='Stocked'");
$paddy_data = $paddy_q->fetch_assoc();
$total_paddy = $paddy_data['total_paddy'] ?? 0;

// වී වර්ග අනුව තොග විස්තර
$paddy_breakdown_q = $conn->query("SELECT paddy_type, SUM(weight_kg) as total_weight 
                                    FROM paddy_stock 
                                    WHERE status='Stocked' 
                                    GROUP BY paddy_type");

// Chart එක සඳහා Arrays සකස් කිරීම
$chart_labels = [];
$chart_data = [];
while($b_row = $paddy_breakdown_q->fetch_assoc()) {
    $chart_labels[] = $b_row['paddy_type'];
    $chart_data[] = floatval($b_row['total_weight']);
}

// 2. Active Milling Process Count
$milling_q = $conn->query("SELECT COUNT(id) as active_jobs FROM milling_process WHERE status='Processing'");
$milling_data = $milling_q->fetch_assoc();
$active_milling = $milling_data['active_jobs'] ?? 0;

// 3. Rice Bags Available
$rice_q = $conn->query("SELECT SUM(available_bag_count) as total_bags FROM rice_stock");
$rice_data = $rice_q->fetch_assoc();
$total_bags = $rice_data['total_bags'] ?? 0;

// 4. Today's Total Sales (Rs.)
$today_date = date('Y-m-d');
$sales_q = $conn->query("SELECT SUM(net_amount) as today_sales FROM sales WHERE DATE(invoice_date) = '$today_date'");
$sales_data = $sales_q->fetch_assoc();
$today_sales = $sales_data['today_sales'] ?? 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Minsada Rice Mill</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=Noto+Sans+Sinhala:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- 📊 Chart.js Library එක ඇතුළත් කිරීම -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <style>
        :root {
            --bg-dark-main: #0f172a;       
            --panel-dark: #1e293b;         
            --border-dark: #334155;        
            --accent-blue: #2563eb;        
            --text-light: #f1f5f9;         
            --text-muted: #94a3b8;         
            --inner-input-bg: #070d1e;
            --accent-orange: #f59e0b;
            
            --paddy-blue: #1e3a8a;
            --paddy-blue-txt: #38bdf8;
            --rice-green-txt: #10b981;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body { 
            font-family: 'Plus Jakarta Sans', 'Noto Sans Sinhala', sans-serif; 
            background-color: var(--bg-dark-main) !important;
            color: var(--text-light) !important;
            display: flex;
            min-height: 100vh;
            overflow-x: hidden;
        }

        .main-container {
            flex-grow: 1;
            margin-left: 260px; 
            padding: 40px;
            display: flex;
            flex-direction: column;
            gap: 30px;
            width: calc(100% - 260px);
            background-color: var(--bg-dark-main);
        }

        .dashboard-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid var(--border-dark);
            padding-bottom: 20px;
        }
        .dashboard-header h1 {
            font-size: 24px;
            font-weight: 800;
            color: #ffffff;
        }

        /* 🎴 Cards Row */
        .cards-row {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 18px;
        }
        
        .stat-card {
            background: var(--panel-dark);
            border-radius: 14px;
            padding: 18px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
            border: 1px solid var(--border-dark);
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            min-height: 120px;
            height: auto;
            transition: transform 0.2s;
        }
        .stat-card:hover { transform: translateY(-3px); }
        
        .stat-card.highlighted {
            background: linear-gradient(135deg, #1e293b, var(--paddy-blue));
            border-color: #2563eb;
            color: #ffffff;
        }
        .stat-card-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            color: var(--text-muted);
            letter-spacing: 0.5px;
            line-height: 1.4;
            gap: 10px;
        }
        .stat-card-header span { flex-grow: 1; }
        .stat-card.highlighted .stat-card-header { color: var(--paddy-blue-txt); }
        
        .stat-card-value {
            font-size: 22px;
            font-weight: 800;
            margin-top: 15px;
            color: #ffffff;
            word-break: break-all;
        }
        .stat-card-icon {
            font-size: 16px;
            color: var(--text-muted);
            flex-shrink: 0;
            margin-top: 2px;
        }
        .stat-card.highlighted .stat-card-icon { color: var(--paddy-blue-txt); }

        /* 📊 Stock Breakdown Panel */
        .chart-panel-box {
            background: var(--panel-dark);
            border-radius: 14px;
            padding: 30px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
            border: 1px solid var(--border-dark);
            display: flex;
            flex-direction: column;
            gap: 25px;
        }

        .box-title {
            font-size: 15px;
            font-weight: 700;
            color: #ffffff;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-bottom: 1px solid var(--border-dark);
            padding-bottom: 12px;
        }

        .chart-layout-flex {
            display: flex;
            align-items: center;
            justify-content: space-around;
            flex-wrap: wrap;
            gap: 40px;
            padding: 10px 0;
        }

        /* Chart වැසීමට වඩාත් සුදුසු ප්‍රමාණය */
        .chart-container {
            position: relative;
            width: 220px;
            height: 220px;
        }

        .mini-details-list {
            flex-grow: 1;
            max-width: 500px;
            display: flex;
            flex-direction: column;
            gap: 16px;
        }
        .mini-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 14px 18px;
            background-color: var(--inner-input-bg);
            border: 1px solid var(--border-dark);
            border-radius: 8px;
            font-size: 14px;
        }
        .mini-label {
            color: var(--text-light);
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        /* 🎨 වී වර්ග අනුව ලිස්ට් එකේ බුලට් වලට පාට දීම */
        .dot-nadu::before { content: ''; display: inline-block; width: 12px; height: 12px; background-color: #38bdf8; border-radius: 50%; }
        .dot-samba::before { content: ''; display: inline-block; width: 12px; height: 12px; background-color: #10b981; border-radius: 50%; }
        .dot-kiri::before { content: ''; display: inline-block; width: 12px; height: 12px; background-color: #f59e0b; border-radius: 50%; }
        .dot-default::before { content: ''; display: inline-block; width: 12px; height: 12px; background-color: #8b5cf6; border-radius: 50%; }

        .mini-val {
            font-weight: 700;
            color: #ffffff;
        }
    </style>
</head>
<body>

    <?php include('includes/sidebar.php'); ?>

    <div class="main-container">
        <div class="dashboard-header">
            <h1><i class="fa-solid fa-chart-pie" style="color: var(--accent-blue); margin-right: 10px;"></i> MINSADA RICE MILL DASHBOARD</h1>
        </div>
        
        <!-- 🎴 TOP CARDS ROW -->
        <div class="cards-row">
            <!-- 1. Total Paddy Card -->
            <div class="stat-card highlighted">
                <div class="stat-card-header">
                    <span>Total Paddy Stock</span>
                    <i class="fa-solid fa-wheat-awn stat-card-icon"></i>
                </div>
                <div class="stat-card-value"><?php echo number_format($total_paddy, 2); ?> <span style="font-size: 12px;">KG</span></div>
            </div>

            <!-- 2. Active Milling Card -->
            <div class="stat-card">
                <div class="stat-card-header">
                    <span>Milling Active</span>
                    <i class="fa-solid fa-gear stat-card-icon" style="color: var(--accent-orange);"></i>
                </div>
                <div class="stat-card-value"><?php echo $active_milling; ?> <span style="font-size: 12px;">Jobs</span></div>
            </div>

            <!-- 3. Rice Stock Card -->
            <div class="stat-card">
                <div class="stat-card-header">
                    <span>Rice Stock</span>
                    <i class="fa-solid fa-cubes stat-card-icon" style="color: var(--rice-green-txt);"></i>
                </div>
                <div class="stat-card-value"><?php echo number_format($total_bags); ?> <span style="font-size: 12px;">Bags</span></div>
            </div>

            <!-- 4. Today Sales Card -->
            <div class="stat-card">
                <div class="stat-card-header">
                    <span>Today Sales</span>
                    <i class="fa-solid fa-wallet stat-card-icon" style="color: #a855f7;"></i>
                </div>
                <div class="stat-card-value"><span style="font-size: 14px; font-weight: 600;">Rs.</span> <?php echo number_format($today_sales, 2); ?></div>
            </div>
        </div>

        <!-- 📊 PADDY STOCK BREAKDOWN PANEL (WITH COLORFUL DONUT CHART) -->
        <div class="chart-panel-box">
            <div class="box-title"><i class="fa-solid fa-chart-pie" style="color: var(--accent-blue); margin-right: 6px;"></i> Current Paddy Stock Breakdown</div>
            
            <div class="chart-layout-flex">
                <!-- Colorful Chart.js Canvas -->
                <div class="chart-container">
                    <canvas id="paddyDonutChart"></canvas>
                </div>

                <!-- Dynamic Breakdown List Right -->
                <div class="mini-details-list">
                    <?php 
                    if(count($chart_labels) > 0) {
                        for($i = 0; $i < count($chart_labels); $i++) {
                            $type = $chart_labels[$i];
                            $weight = $chart_data[$i];
                            
                            // වී වර්ගයට ගැලපෙන Class එක තේරීම
                            $dot_class = 'dot-default';
                            if(strtolower($type) == 'nadu') $dot_class = 'dot-nadu';
                            elseif(strtolower($type) == 'samba') $dot_class = 'dot-samba';
                            elseif(strtolower($type) == 'kiri samba' || strtolower($type) == 'keeri samba') $dot_class = 'dot-kiri';

                            echo '<div class="mini-row">';
                            echo '  <span class="mini-label '.$dot_class.'">'.$type.'</span>';
                            echo '  <span class="mini-val">'.number_format($weight, 2).' KG</span>';
                            echo '</div>';
                        }
                    } else {
                        echo '<div class="mini-row"><span class="mini-label" style="text-align:center; width:100%;">No varieties found in stock.</span></div>';
                    }
                    ?>
                </div>
            </div>
        </div>

    </div>

    <!-- 📊 Chart Rendering Script -->
    <script>
        const ctx = document.getElementById('paddyDonutChart').getContext('2d');
        
        // PHP වලින් එන දත්ත JavaScript Arrays වලට පරිවර්තනය
        const labelsArray = <?php echo json_encode($chart_labels); ?>;
        const dataArray = <?php echo json_encode($chart_data); ?>;

        // වී වර්ගවලට ගැලපෙන පාටවල් Assign කිරීම
        const colorMap = {
            'nadu': '#38bdf8',       // Light Blue
            'samba': '#10b981',      // Emerald Green
            'kiri samba': '#f59e0b', // Warm Amber/Yellow
            'keeri samba': '#f59e0b' 
        };

        const bgColors = labelsArray.map(label => colorMap[label.toLowerCase()] || '#8b5cf6');

        new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: labelsArray,
                datasets: [{
                    data: dataArray,
                    backgroundColor: bgColors,
                    borderWidth: 2,
                    borderColor: '#1e293b', // Panel Background Color එකට මැච් වෙන Border එකක්
                    hoverOffset: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false // අපි වෙනම ලිස්ට් එකක් හදපු නිසා Chart legend එක hide කරනවා
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                let value = context.raw || 0;
                                return ' ' + context.label + ': ' + value.toLocaleString() + ' KG';
                            }
                        }
                    }
                },
                cutout: '70%' // මැද රවුමේ ප්‍රමාණය (Donut look)
            }
        });
    </script>
</body>
</html>