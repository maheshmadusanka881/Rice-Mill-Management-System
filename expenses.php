<?php 
include('config/db.php');   
include('config/auth.php'); // ආරක්ෂාව (Session) check කිරීම

$success_msg = "";
$error_msg = "";

// 1. අලුත් වියදමක් සිස්ටම් එකට එකතු කිරීම (Insert)
if (isset($_POST['add_expense'])) {
    $expense_date = $_POST['expense_date'];
    $category = mysqli_real_escape_string($conn, $_POST['category']);
    $amount = $_POST['amount'];
    $description = mysqli_real_escape_string($conn, $_POST['description']);

    $insert_sql = "INSERT INTO expenses (expense_date, category, amount, description) VALUES ('$expense_date', '$category', '$amount', '$description')";
    
    if ($conn->query($insert_sql)) {
        $success_msg = "✅ වියදම සාර්ථකව ඇතුළත් කරන ලදී!";
    } else {
        $error_msg = "❌ දත්ත ඇතුළත් කිරීමේදී දෝෂයක් සිදුවිය: " . $conn->error;
    }
}

// 2. දැනට තියෙන වියදම් ලැයිස්තුව ලබා ගැනීම (Select)
$expenses_q = $conn->query("SELECT * FROM expenses ORDER BY expense_date DESC");

// 3. මේ මාසයේ මුළු වියဒီම ගණනය කිරීම
$current_month = date('Y-m');
$total_exp_q = $conn->query("SELECT SUM(amount) as total_exp FROM expenses WHERE expense_date LIKE '$current_month%'");
$total_exp_data = $total_exp_q->fetch_assoc();
$total_month_expense = $total_exp_data['total_exp'] ?? 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Expense Tracker - Minsada Rice Mill</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    <style>
        body { font-family: 'Inter', sans-serif; background-color: #0f172a !important; color: #f1f5f9 !important; margin: 0; }
        .main-content { background-color: #0f172a !important; min-height: 100vh; padding: 30px; }
        header { padding-bottom: 20px; border-bottom: 1px solid #1e293b; margin-bottom: 30px; }
        header h2 { font-size: 24px; font-weight: 700; color: #ffffff; margin: 0; }

        /* Form සහ Table එක දෙපැත්තට බෙදීමට Grid එකක් */
        .expense-grid { display: grid; grid-template-columns: 1fr 2fr; gap: 30px; align-items: start; }
        @media (max-width: 900px) { .expense-grid { grid-template-columns: 1fr; } }

        /* Premium Box Styles */
        .premium-box { background: #1e293b !important; border-radius: 16px !important; padding: 24px !important; border: 1px solid #334155 !important; box-shadow: 0 10px 25px rgba(0,0,0,0.3) !important; }
        .box-title { font-size: 16px; font-weight: 600; color: #ffffff; margin-bottom: 20px; display: flex; align-items: center; gap: 8px; }

        /* Inputs Styles */
        .form-group { margin-bottom: 16px; }
        .form-group label { display: block; font-size: 12px; font-weight: 600; color: #94a3b8; margin-bottom: 6px; text-transform: uppercase; }
        .form-group input, .form-group select, .form-group textarea {
            width: 100%; padding: 11px 14px; background-color: #0f172a; border: 1px solid #334155; border-radius: 8px; color: #ffffff; font-size: 14px; font-family: 'Inter', sans-serif; box-sizing: border-box;
        }
        .form-group input:focus, .form-group select:focus, .form-group textarea:focus { outline: none; border-color: #38bdf8; box-shadow: 0 0 0 3px rgba(56, 189, 248, 0.1); }

        /* Button Style */
        .btn-submit { width: 100%; padding: 12px; background: linear-gradient(135deg, #0284c7 0%, #38bdf8 100%); border: none; border-radius: 8px; color: #ffffff; font-size: 14px; font-weight: 600; cursor: pointer; transition: all 0.2s ease; box-shadow: 0 4px 10px rgba(56, 189, 248, 0.15); }
        .btn-submit:hover { transform: translateY(-1.5px); box-shadow: 0 6px 15px rgba(56, 189, 248, 0.3); }

        /* Table Styles */
        table { width: 100%; border-collapse: collapse; }
        th { background-color: #0f172a !important; padding: 14px 16px !important; font-size: 13px !important; font-weight: 600 !important; color: #94a3b8 !important; border-bottom: 2px solid #334155 !important; text-transform: uppercase; text-align: left; }
        td { padding: 14px 16px !important; font-size: 14px !important; color: #cbd5e1 !important; border-bottom: 1px solid #334155 !important; }
        tr:hover td { background-color: #243249; color: #ffffff; }

        /* Badges */
        .badge-cat { padding: 4px 10px; border-radius: 99px; font-size: 12px; font-weight: 600; background-color: rgba(56, 189, 248, 0.1); color: #38bdf8; border: 1px solid rgba(56, 189, 248, 0.2); }
        
        /* Summary Box */
        .summary-card { background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%); border: 1px solid #334155; padding: 20px; border-radius: 14px; margin-bottom: 25px; display: flex; justify-content: space-between; align-items: center; }

        /* Messages */
        .msg { padding: 12px; border-radius: 8px; font-size: 13px; margin-bottom: 15px; text-align: center; }
        .msg-success { background: rgba(52, 211, 153, 0.1); color: #34d399; border: 1px solid rgba(52, 211, 153, 0.2); }
        .msg-error { background: rgba(239, 68, 68, 0.1); color: #fca5a5; border: 1px solid rgba(239, 68, 68, 0.2); }
    </style>
</head>
<body>

    <?php include('includes/sidebar.php'); ?>

    <div class="main-content">
        <header>
            <h2>Expense Tracker (වියදම් කළමනාකරණය)</h2>
        </header>

        <div class="summary-card">
            <div>
                <div style="font-size: 13px; color: #94a3b8; text-transform: uppercase; font-weight: 600; letter-spacing: 0.5px;">Total Expenses This Month</div>
                <div style="font-size: 26px; font-weight: 700; color: #f43f5e; margin-top: 5px;">Rs. <?php echo number_format($total_month_expense, 2); ?></div>
            </div>
            <div style="font-size: 32px;">💸</div>
        </div>

        <div class="expense-grid">
            
            <div class="premium-box">
                <div class="box-title">➕ Add New Expense</div>
                
                <?php if(!empty($success_msg)): ?> <div class="msg msg-success"><?php echo $success_msg; ?></div> <?php endif; ?>
                <?php if(!empty($error_msg)): ?> <div class="msg msg-error"><?php echo $error_msg; ?></div> <?php endif; ?>

                <form action="expenses.php" method="POST">
                    <div class="form-group">
                        <label>Date (දිනය)</label>
                        <input type="date" name="expense_date" value="<?php echo date('Y-m-d'); ?>" required>
                    </div>

                    <div class="form-group">
                        <label>Category (වියදම් වර්ගය)</label>
                        <select name="category" required>
                            <option value="Electricity">Electricity (විදුලි බිල්)</option>
                            <option value="Diesel">Diesel (මැෂින්/ලොරි ඩීසල්)</option>
                            <option value="Wages">Wages (සේවක කුලී)</option>
                            <option value="Repairs">Repairs (මැෂින් අලුත්වැඩියාව)</option>
                            <option value="Transport">Transport (ප්‍රවාහන වියදම්)</option>
                            <option value="Other">Other (වෙනත් වියදම්)</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Amount (මුදල - Rs.)</label>
                        <input type="number" step="0.01" name="amount" placeholder="0.00" required>
                    </div>

                    <div class="form-group">
                        <label>Description (විස්තරය)</label>
                        <textarea name="description" rows="3" placeholder="වියදම පිළිබඳ කෙටි සටහනක්..."></textarea>
                    </div>

                    <button type="submit" name="add_expense" class="btn-submit">Save Expense 💾</button>
                </form>
            </div>

            <div class="premium-box">
                <div class="box-title">📋 Recent Expenses List</div>
                <div style="overflow-x: auto;">
                    <table>
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Category</th>
                                <th>Description</th>
                                <th>Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            if ($expenses_q && $expenses_q->num_rows > 0) {
                                while($row = $expenses_q->fetch_assoc()) {
                                    echo "<tr>
                                            <td>" . date('Y-m-d', strtotime($row['expense_date'])) . "</td>
                                            <td><span class='badge-cat'>" . $row['category'] . "</span></td>
                                            <td>" . (!empty($row['description']) ? $row['description'] : '-') . "</td>
                                            <td style='font-weight:600; color:#f43f5e;'>Rs. " . number_format($row['amount'], 2) . "</td>
                                          </tr>";
                                }
                            } else {
                                echo "<tr><td colspan='4' style='text-align:center; color:#94a3b8; padding:20px;'>No expenses recorded yet.</td></tr>";
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