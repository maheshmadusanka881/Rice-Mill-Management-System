<?php 
include('config/db.php'); 
include('config/auth.php'); // සිස්ටම් එකේ ආරක්ෂාව වෙනුවෙන් මේක ඇතුලත් කළා

// සේවකයෙක් ඇතුලත් කරද්දී වැඩ කරන PHP Code එක
if (isset($_POST['add_employee'])) {
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $nic = mysqli_real_escape_string($conn, $_POST['nic']);
    $phone = mysqli_real_escape_string($conn, $_POST['phone']);
    $role = mysqli_real_escape_string($conn, $_POST['role']);
    $username = !empty($_POST['username']) ? mysqli_real_escape_string($conn, $_POST['username']) : null;
    $password = !empty($_POST['password']) ? $_POST['password'] : null; 

    $sql = "INSERT INTO employees (name, nic, phone, role, username, password) 
            VALUES ('$name', '$nic', '$phone', '$role', '$username', '$password')";
    
    if ($conn->query($sql) === TRUE) {
        echo "<script>alert('Employee added successfully!'); window.location='employees.php';</script>";
    } else {
        die("MySQL Error: " . $conn->error);
    }
}

// සජීවී සේවක සංඛ්‍යාලේඛන ගණනය කිරීම්
$count_all = $conn->query("SELECT COUNT(id) as total FROM employees")->fetch_assoc();
$total_employees = $count_all['total'] ?? 0;

$count_admin = $conn->query("SELECT COUNT(id) as total FROM employees WHERE role IN ('Admin', 'Manager', 'Cashier')")->fetch_assoc();
$total_management = $count_admin['total'] ?? 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employee Management - Minsada Rice Mill</title>
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
        .form-group input:focus, .form-group select:focus { outline: none; border-color: #38bdf8; box-shadow: 0 0 0 3px rgba(56, 189, 248, 0.1); }
        ::placeholder { color: #475569; }

        .btn-submit { padding: 12px 28px; background: linear-gradient(135deg, #38bdf8 0%, #0284c7 100%); border: none; border-radius: 8px; color: #ffffff; font-size: 14px; font-weight: 600; cursor: pointer; transition: all 0.2s ease; margin-top: 20px; box-shadow: 0 4px 12px rgba(56, 189, 248, 0.2); }
        .btn-submit:hover { transform: translateY(-1.5px); box-shadow: 0 6px 18px rgba(56, 189, 248, 0.3); }

        /* Premium Table Style */
        .table-wrapper { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; text-align: left; }
        th { background-color: #0f172a !important; padding: 14px 16px !important; font-size: 12px !important; font-weight: 600 !important; color: #94a3b8 !important; border-bottom: 2px solid #334155 !important; text-transform: uppercase; }
        td { padding: 14px 16px !important; font-size: 13.5px !important; color: #cbd5e1 !important; border-bottom: 1px solid #334155 !important; }
        tr:hover td { background-color: #243249; color: #ffffff; }
        
        /* Badges */
        .badge-role { padding: 5px 10px; border-radius: 6px; font-size: 12px; font-weight: 600; text-transform: uppercase; border: 1px solid; display: inline-block; }
        .role-admin { background: rgba(239, 68, 68, 0.1); color: #ef4444; border-color: rgba(239, 68, 68, 0.2); }
        .role-manager { background: rgba(245, 158, 11, 0.1); color: #f59e0b; border-color: rgba(245, 158, 11, 0.2); }
        .role-cashier { background: rgba(16, 185, 129, 0.1); color: #10b981; border-color: rgba(16, 185, 129, 0.2); }
        .role-worker { background: rgba(148, 163, 184, 0.1); color: #94a3b8; border-color: rgba(148, 163, 184, 0.2); }
        .text-id { color: #64748b; font-weight: 500; }
    </style>
</head>
<body>

    <?php include('includes/sidebar.php'); ?>

    <div class="main-content">
        <header>
            <h2> Employee Management</h2>
            <span>Welcome, <strong>Admin</strong></span>
        </header>

        <!-- 📊 සේවක සංඛ්‍යාලේඛන කාඩ්පත් -->
        <div class="stats-grid">
            <div class="stat-card" style="border-left: 4px solid #38bdf8;">
                <div>
                    <h4>Total Staff Count</h4>
                    <div class="value" style="color: #38bdf8;"><?php echo $total_employees; ?> <span style="font-size:14px; color:#94a3b8;">Workers</span></div>
                </div>
                <div style="font-size: 28px;">👷</div>
            </div>
            <div class="stat-card" style="border-left: 4px solid #f59e0b;">
                <div>
                    <h4>Management Staff</h4>
                    <div class="value"><?php echo $total_management; ?> <span style="font-size:14px; color:#94a3b8;">Users</span></div>
                </div>
                <div style="font-size: 28px;">💼</div>
            </div>
        </div>

        <div class="content-wrapper">
            
            <!-- ➕ අලුත් සේවකයෙක් දාන PREMIUM FORM එක -->
            <div class="premium-box">
                <div class="box-title">➕ Add New Employee / Worker (අලුත් සේවකයෙකු ඇතුලත් කිරීම)</div>
                <form action="employees.php" method="POST">
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Full Name</label>
                            <input type="text" name="name" required placeholder="E.g. Kamal Silva">
                        </div>
                        <div class="form-group">
                            <label>NIC Number</label>
                            <input type="text" name="nic" required placeholder="E.g. 1995XXXXXX">
                        </div>
                        <div class="form-group">
                            <label>Phone Number</label>
                            <input type="text" name="phone" required placeholder="E.g. 077XXXXXXXX">
                        </div>
                        <div class="form-group">
                            <label>Role (තනතුර)</label>
                            <select name="role" required>
                                <option value="Worker">Worker (මෝලේ සේවක)</option>
                                <option value="Cashier">Cashier (කැෂියර්)</option>
                                <option value="Manager">Manager (මැනේජර්)</option>
                                <option value="Admin">Admin (ප්‍රධාන පරිපාලක)</option>
                            </select>
                        </div>
                    </div>
                    
                    <p style="margin-top: 25px; margin-bottom: 10px; font-size: 13px; color: #38bdf8; font-weight: 500;">🔒 System Login එකක් දෙනවා නම් පමණක් පහත කොටස් පුරවන්න (Optional):</p>
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Username</label>
                            <input type="text" name="username" placeholder="Login Username">
                        </div>
                        <div class="form-group">
                            <label>Password</label>
                            <input type="password" name="password" placeholder="Login Password">
                        </div>
                    </div>

                    <button type="submit" name="add_employee" class="btn-submit">Save Employee Details 👤</button>
                </form>
            </div>

            <!-- 📋 EMPLOYEE LIST TABLE -->
            <div class="premium-box">
                <div class="box-title">📋 Employee List (සේවක ලැයිස්තුව)</div>
                <div class="table-wrapper">
                    <table>
                        <thead>
                            <tr>
                                <th>Emp ID</th>
                                <th>Name</th>
                                <th>NIC</th>
                                <th>Phone</th>
                                <th>Role (තනතුර)</th>
                                <th>Username</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $emp_res = $conn->query("SELECT * FROM employees ORDER BY id DESC");
                            if ($emp_res && $emp_res->num_rows > 0) {
                                while($emp = $emp_res->fetch_assoc()) {
                                    $user_display = !empty($emp['username']) ? $emp['username'] : '<span style="color:#475569; font-style: italic;">No Login</span>';
                                    
                                    // Role එක අනුව Badge එකේ පාට වෙනස් කරන කේතය
                                    $role_class = 'role-worker';
                                    if($emp['role'] == 'Admin') $role_class = 'role-admin';
                                    elseif($emp['role'] == 'Manager') $role_class = 'role-manager';
                                    elseif($emp['role'] == 'Cashier') $role_class = 'role-cashier';

                                    echo "<tr>
                                            <td class='text-id'>#{$emp['id']}</td>
                                            <td style='font-weight:600; color:#fff;'>{$emp['name']}</td>
                                            <td>{$emp['nic']}</td>
                                            <td>{$emp['phone']}</td>
                                            <td><span class='badge-role {$role_class}'>{$emp['role']}</span></td>
                                            <td style='font-weight: 500;'>{$user_display}</td>
                                          </tr>";
                                }
                            } else {
                                echo "<tr><td colspan='6' style='text-align:center; color:#94a3b8; padding:30px;'>No employees found.</td></tr>";
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