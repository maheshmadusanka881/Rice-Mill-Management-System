<?php 
include('config/db.php');
include('config/auth.php');

$msg = "";
$error = "";

// 1. හාල් මල්ලක මිල වෙනස් කිරීම (Price Update Logic)
if (isset($_POST['update_price'])) {
    $stock_id = mysqli_real_escape_string($conn, $_POST['stock_id']);
    $new_price = floatval($_POST['new_price']);
    
    if ($new_price > 0) {
        $update_q = "UPDATE rice_stock SET price_per_bag='$new_price' WHERE id='$stock_id'";
        if ($conn->query($update_q) === TRUE) {
            $msg = "මිල ගණන් සාර්ථකව යාවත්කාලීන කරන ලදී!";
        } else {
            $error = "MySQL Error: " . $conn->error;
        }
    } else {
        $error = "කරුණාකර වලංගු මිලක් ඇතුළත් කරන්න!";
    }
}

// 2. සාරාංශ දත්ත (Summary Stats) ගණනය කිරීම්
$stats_bags = $conn->query("SELECT SUM(available_bag_count) as total_b FROM rice_stock")->fetch_assoc();
$total_bags_available = $stats_bags['total_b'] ?? 0;

$stats_weight = $conn->query("SELECT SUM(total_weight_kg) as total_w FROM rice_stock")->fetch_assoc();
$total_weight_available = $stats_weight['total_w'] ?? 0;

$stats_types = $conn->query("SELECT COUNT(id) as total_t FROM rice_stock WHERE available_bag_count > 0")->fetch_assoc();
$total_types = $stats_types['total_t'] ?? 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rice Stock Control - Minsada Rice Mill</title>
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

        /* Stats Grid */
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 25px; margin-bottom: 35px; }
        .stat-card { 
            background: linear-gradient(145deg, #0e1524, #131c2e); 
            border: 1px solid var(--border-dark); 
            padding: 24px; 
            border-radius: 16px; 
            position: relative;
            transition: all 0.3s ease;
        }
        .stat-card:hover { transform: translateY(-4px); border-color: #3b4f74; box-shadow: 0 10px 20px rgba(0,0,0,0.2); }
        .stat-card span { color: var(--text-muted); font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.8px; }
        .stat-card .val { font-size: 26px; font-weight: 800; color: #ffffff; margin-top: 10px; display: flex; align-items: baseline; gap: 6px; }
        .stat-card .icon-bg { position: absolute; right: 20px; top: 20px; font-size: 28px; opacity: 0.15; color: var(--accent-blue); }

        /* Premium Box Design */
        .premium-box { 
            background: linear-gradient(145deg, #131c2e, #18253c) !important; 
            border-radius: 20px !important; 
            padding: 30px !important; 
            border: 1px solid var(--border-dark) !important; 
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3) !important; 
            margin-bottom: 35px; 
            position: relative;
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

        .rice-type-tag {
            padding: 6px 12px; background: rgba(59, 130, 246, 0.1); border-radius: 8px; color: var(--accent-blue); font-size: 13px; font-weight: 700; border: 1px solid rgba(59, 130, 246, 0.2);
            display: inline-flex; align-items: center; gap: 6px;
        }

        .price-form { display: flex; align-items: center; gap: 8px; }
        .price-input { 
            width: 110px; 
            padding: 8px 12px; 
            background: #090e17; 
            border: 1px solid var(--border-dark); 
            border-radius: 8px; 
            color: #ffffff; 
            font-weight: 700; 
            font-size: 13px;
        }
        .price-input:focus { outline: none; border-color: var(--accent-blue); }

        .btn-update { 
            background: linear-gradient(135deg, var(--accent-blue), #1d4ed8); 
            color: white; 
            border: none; 
            padding: 8px 14px; 
            border-radius: 8px; 
            cursor: pointer; 
            font-size: 12px; 
            font-weight: 700; 
            transition: all 0.2s; 
        }
        .btn-update:hover { transform: translateY(-1px); box-shadow: 0 4px 12px var(--accent-blue-glow); }

        .badge { padding: 6px 12px; border-radius: 8px; font-size: 12px; font-weight: 700; display: inline-flex; align-items: center; gap: 6px; }
        .badge-success { background: rgba(16, 185, 129, 0.1); color: var(--accent-green); border: 1px solid rgba(16, 185, 129, 0.2); }
        .badge-warning { background: rgba(245, 158, 11, 0.1); color: #f59e0b; border: 1px solid rgba(245, 158, 11, 0.2); }
        .badge-danger { background: rgba(239, 68, 68, 0.1); color: #f87171; border: 1px solid rgba(239, 68, 68, 0.2); }

        .alert { padding: 14px 20px; border-radius: 10px; margin-bottom: 30px; font-size: 14px; font-weight: 600; display: flex; align-items: center; gap: 10px; }
        .alert-success { background: rgba(16, 185, 129, 0.1); border: 1px solid var(--accent-green); color: #34d399; }
        .alert-danger { background: rgba(239, 68, 68, 0.1); border: 1px solid #ef4444; color: #f87171; }
    </style>
</head>
<body>

    <!-- Sidebar Area -->
    <?php include('includes/sidebar.php'); ?>

    <div class="main-content">
        <header>
            <h2><i class="fa-solid fa-boxes-stacked" style="color: var(--accent-blue);"></i> Rice Stock Inventory Control</h2>
            <span>System Status: <strong style="color: var(--accent-green);"><i class="fa-solid fa-circle" style="font-size:10px;"></i> Inventory Live</strong></span>
        </header>

        <div class="content-wrapper">
            
            <?php if ($msg != ""): ?>
                <div class="alert alert-success"><i class="fa-solid fa-circle-check"></i> <?php echo $msg; ?></div>
            <?php endif; ?>
            <?php if ($error != ""): ?>
                <div class="alert alert-danger"><i class="fa-solid fa-circle-exclamation"></i> <?php echo $error; ?></div>
            <?php endif; ?>

            <!-- Inventory Summary Cards -->
            <div class="stats-grid">
                <div class="stat-card">
                    <span>Available Rice Varieties</span>
                    <div class="val"><?php echo number_format($total_types); ?> <span style="font-size:14px; color: var(--text-muted); font-weight:500;">Types</span></div>
                    <i class="fa-solid fa-layer-group icon-bg" style="color: var(--accent-cyan);"></i>
                </div>
                <div class="stat-card">
                    <span>Total Bags In Stock</span>
                    <div class="val" style="color: var(--accent-green);"><?php echo number_format($total_bags_available); ?> <span style="font-size:14px; color: var(--text-muted); font-weight:500;">Bags</span></div>
                    <i class="fa-solid fa-box icon-bg" style="color: var(--accent-green);"></i>
                </div>
                <div class="stat-card">
                    <span>Total Weight Volume</span>
                    <div class="val" style="color: #f59e0b;"><?php echo number_format($total_weight_available, 2); ?> <span style="font-size:14px; color: var(--text-muted); font-weight:500;">KG</span></div>
                    <i class="fa-solid fa-weight-hanging icon-bg" style="color: #f59e0b;"></i>
                </div>
            </div>

            <!-- Stock Details Table -->
            <div class="premium-box">
                <div class="box-title"><i class="fa-solid fa-list" style="color: var(--accent-blue);"></i> Available Rice Stocks & Pricing</div>
                <div style="overflow-x: auto;">
                    <table>
                        <thead>
                            <tr>
                                <th>#ID</th>
                                <th>Rice Variety</th>
                                <th>Bag Size</th>
                                <th>Available Bags</th>
                                <th>Total Net Weight</th>
                                <th>Stock Status</th>
                                <th>Price Per Bag (LKR)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $rice_res = $conn->query("SELECT * FROM rice_stock ORDER BY id DESC");
                            
                            if ($rice_res && $rice_res->num_rows > 0) {
                                while($row = $rice_res->fetch_assoc()) {
                                    $bags = intval($row['available_bag_count']);
                                    
                                    // Stock Level Status
                                    if ($bags == 0) {
                                        $badge = "<span class='badge badge-danger'><i class='fa-solid fa-triangle-exclamation'></i> Out of Stock</span>";
                                    } else if ($bags < 20) {
                                        $badge = "<span class='badge badge-warning'><i class='fa-solid fa-circle-exclamation'></i> Low Stock</span>";
                                    } else {
                                        $badge = "<span class='badge badge-success'><i class='fa-solid fa-check'></i> In Stock</span>";
                                    }
                                    ?>
                                    <tr>
                                        <td style="font-weight:700; color: #38bdf8;">#RICE-<?php echo $row['id']; ?></td>
                                        <td><span class="rice-type-tag"><i class="fa-solid fa-bowl-rice"></i> <?php echo htmlspecialchars($row['rice_type']); ?></span></td>
                                        <td style="font-weight:600; color: var(--text-desc);"><?php echo number_format($row['bag_weight_kg'], 2); ?> KG</td>
                                        <td style="font-weight:800; font-size:16px; color: #ffffff;"><?php echo number_format($bags); ?> <span style="font-size:11px; color:var(--text-muted)">Bags</span></td>
                                        <td style="font-weight:700; color: var(--accent-green);"><?php echo number_format($row['total_weight_kg'], 2); ?> <span style="font-size:11px; color:var(--text-muted)">KG</span></td>
                                        <td><?php echo $badge; ?></td>
                                        <td>
                                            <form action="rice_stock.php" method="POST" class="price-form">
                                                <input type="hidden" name="stock_id" value="<?php echo $row['id']; ?>">
                                                <input type="number" step="0.01" name="new_price" class="price-input" value="<?php echo number_format($row['price_per_bag'], 2, '.', ''); ?>" required>
                                                <button type="submit" name="update_price" class="btn-update"><i class="fa-solid fa-floppy-disk"></i></button>
                                            </form>
                                        </td>
                                    </tr>
                                    <?php
                                }
                            } else {
                                echo "<tr><td colspan='7' style='text-align:center; color: var(--text-muted); padding:40px;'>No rice stock entries found in database. Complete milling jobs to generate stock.</td></tr>";
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