<?php
// සෙෂන් එක ස්ටාර්ට් වෙලා නැත්නම් විතරක් ස්ටාර්ට් කරනවා
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 🛠️ User role සහ නම session එකෙන් ගන්නවා (නැත්නම් default අගයන් වැටෙනවා)
$user_role = isset($_SESSION['user_role']) ? strtolower(trim($_SESSION['user_role'])) : 'manager';
$username = isset($_SESSION['username']) ? $_SESSION['username'] : 'MINSADA ADMIN';

// දැනට ලෝඩ් වෙලා තියෙන පිටුවේ නම ඉබේම ගන්නවා
$current_page = basename($_SERVER['PHP_SELF']);
?>

<!-- FontAwesome Icons & Font -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

<style>
    /* 🎨 Dashboard Dark Navy Sidebar Main Container */
    .sidebar {
        width: 260px;
        height: 100vh;
        background-color: #112d4e !important; /* Dashboard එකේ තියෙන තද නිල් පාට */
        position: fixed;
        top: 0;
        left: 0;
        z-index: 1000;
        box-shadow: 4px 0 25px rgba(0, 0, 0, 0.3);
        display: flex;
        flex-direction: column;
        font-family: 'Plus Jakarta Sans', sans-serif;
    }

    /* 🧑‍💻 User Profile Section (වමේ තියෙන රවුම් icon එක සහ නම) */
    .sidebar-user-profile {
        padding: 35px 20px 25px 20px;
        display: flex;
        flex-direction: column;
        align-items: center;
        text-align: center;
        border-bottom: 1px solid rgba(255, 255, 255, 0.05);
    }

    .profile-avatar-box {
        width: 90px;
        height: 90px;
        border-radius: 50%;
        border: 2px solid rgba(255, 255, 255, 0.2);
        display: flex;
        align-items: center;
        justify-content: center;
        background-color: rgba(255, 255, 255, 0.05);
        margin-bottom: 18px;
    }

    .profile-avatar-box i {
        font-size: 40px;
        color: #ffffff;
    }

    .user-name {
        font-size: 16px;
        font-weight: 800;
        color: #ffffff;
        margin: 0;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .user-role-badge {
        font-size: 12px;
        color: #94a3b8;
        font-weight: 500;
        margin-top: 6px;
        text-transform: capitalize;
    }

    /* Menu Navigation Area */
    .sidebar-menu-wrapper {
        flex: 1;
        overflow-y: auto;
        padding-top: 15px;
    }
    .sidebar-menu-wrapper::-webkit-scrollbar {
        width: 0px; 
    }

    .sidebar-menu {
        list-style: none;
        padding: 0;
        margin: 0;
        display: flex;
        flex-direction: column;
        height: calc(100% - 20px); 
    }

    .sidebar-menu li {
        padding: 4px 18px; /* Dashboard එකේ තිබ්බ පරතරය */
    }

    .sidebar-menu a {
        display: flex;
        align-items: center;
        gap: 15px;
        padding: 12px 18px;
        color: #94a3b8 !important; /* Dashboard එකේ තිබ්බ අළු පැහැති අකුරු */
        text-decoration: none;
        font-weight: 600;
        font-size: 14px;
        border-radius: 12px; /* Dashboard එකේ Rounded Button Style එක */
        transition: all 0.2s ease;
    }

    /* Hover Effect */
    .sidebar-menu a:hover {
        background-color: rgba(255, 255, 255, 0.03) !important; 
        color: #ffffff !important;
    }

    /* 🎯 Active Page Style (Dashboard එකේ Home බටන් එක වගේමයි) */
    .sidebar-menu li.active-link a {
        background-color: rgba(255, 255, 255, 0.08) !important; /* Rounded background highlight */
        color: #ffffff !important; 
        font-weight: 700;
    }
    .sidebar-menu li.active-link a i {
        color: #38bdf8 !important; /* Active icon එකට ලස්සන Sky Blue එලියක් */
    }

    /* Logout Section */
    .logout-item {
        margin-top: auto; 
        padding-bottom: 25px !important; 
        border-top: 1px solid rgba(255, 255, 255, 0.05);
        padding-top: 15px !important;
    }

    .sidebar-menu .logout-link {
        color: #f87171 !important; /* Soft Red */
    }

    .sidebar-menu .logout-link:hover {
        background-color: rgba(239, 68, 68, 0.08) !important; 
        color: #ef4444 !important;
    }
</style>

<div class="sidebar">
    <!-- 🧑‍💻 Dashboard Profile Header (අයිකන් එක සහ විස්තර) -->
    <div class="sidebar-user-profile">
        <div class="profile-avatar-box">
            <i class="fa-regular fa-user"></i>
        </div>
        <h3 class="user-name"><?php echo htmlspecialchars($username); ?></h3>
        <span class="user-role-badge"><?php echo htmlspecialchars($user_role); ?></span>
    </div>
    
    <div class="sidebar-menu-wrapper">
        <ul class="sidebar-menu">
            <!-- 👥 හැමෝටම පොදු මෙනු (Dashboard, Paddy Stock, Milling, Rice Stock, Expenses) -->
            <li class="<?php echo ($current_page == 'dashboard.php') ? 'active-link' : ''; ?>">
                <a href="dashboard.php">
                    <i class="fa-solid fa-house"></i> <span>Home</span>
                </a>
            </li>
            <li class="<?php echo ($current_page == 'paddy_management.php' || $current_page == 'paddy_stock.php') ? 'active-link' : ''; ?>">
                <a href="paddy_management.php">
                    <i class="fa-solid fa-seedling"></i> <span>Paddy Stock</span>
                </a>
            </li>
            <li class="<?php echo ($current_page == 'milling_management.php' || $current_page == 'milling.php') ? 'active-link' : ''; ?>">
                <a href="milling_management.php">
                    <i class="fa-solid fa-gears"></i> <span>Milling Process</span>
                </a>
            </li>
            <li class="<?php echo ($current_page == 'rice_stock.php') ? 'active-link' : ''; ?>">
                <a href="rice_stock.php">
                    <i class="fa-solid fa-boxes-stacked"></i> <span>Rice Stock</span>
                </a>
            </li>
            <li class="<?php echo ($current_page == 'expenses.php') ? 'active-link' : ''; ?>">
                <a href="expenses.php">
                    <i class="fa-solid fa-wallet"></i> <span>Expense Tracker</span>
                </a>
            </li>

            <!-- 🔒 ADMIN සහ MANAGER ට පමණක් පෙනෙන මෙනු -->
            <?php if ($user_role === 'admin' || $user_role === 'manager'): ?>
                <li class="<?php echo ($current_page == 'sales_management.php' || $current_page == 'print_invoice.php') ? 'active-link' : ''; ?>">
                    <a href="sales_management.php">
                        <i class="fa-solid fa-cart-shopping"></i> <span>Sales</span>
                    </a>
                </li>
                <li class="<?php echo ($current_page == 'employees.php') ? 'active-link' : ''; ?>">
                    <a href="employees.php">
                        <i class="fa-solid fa-users"></i> <span>Employees</span>
                    </a>
                </li>
            <?php endif; ?>

            <!-- 🔒 ADMIN ට පමණක් පෙනෙන Reports මෙනුව -->
            <?php if ($user_role === 'admin'): ?>
                <li class="<?php echo ($current_page == 'reports.php') ? 'active-link' : ''; ?>">
                    <a href="reports.php">
                        <i class="fa-solid fa-file-invoice-dollar"></i> <span>Reports</span>
                    </a>
                </li>
            <?php endif; ?>
            
            <!-- 🚪 Logout -->
            <li class="logout-item">
                <a href="logout.php" class="logout-link">
                    <i class="fa-solid fa-right-from-bracket"></i> <span>Logout System</span>
                </a>
            </li>
        </ul>
    </div>
</div>