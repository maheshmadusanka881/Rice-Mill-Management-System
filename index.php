<?php
session_start();
include('config/db.php');

// FIXED LOGIC: User log wela nam kelinma dashboard yවනවා (ඔයාගේ ඔරිජිනල් ලොජික් එක)
if(isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit();
}

$error = "";

if (isset($_POST['login'])) {
    $username = mysqli_real_escape_string($conn, $_POST['username']); 
    $password = mysqli_real_escape_string($conn, $_POST['password']);

    $sql = "SELECT * FROM employees WHERE username='$username' AND password='$password'";
    $result = $conn->query($sql);

    if ($result && $result->num_rows > 0) {
        $user = $result->fetch_assoc();
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['user_role'] = $user['role'];

        header("Location: dashboard.php");
        exit();
    } else {
        $error = "❌ Invalid Username or Password!";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Minsada Rice Mill</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #0f172a; 
            margin: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            color: #f1f5f9;
        }

        /* RE-DESIGN (COMPACT SIZE): පළල සහ Padding අඩු කර බොක්ස් එක compact කරන ලදී */
        .login-container {
            width: 90%;
            max-width: 350px; /* 400px සිට 350px දක්වා අඩු කලා */
            background: #1e293b; 
            padding: 28px 24px; /* Padding එක ගොඩක් අඩු කරා */
            border-radius: 14px; /* Soft Corners */
            box-shadow: 0 15px 35px -10px rgba(0, 0, 0, 0.6);
            border: 1px solid #334155;
            transition: border-color 0.3s ease;
        }
        .login-container:focus-within {
            border-color: #38bdf8; 
        }

        /* Heading එකේ හිස්තැන් (Margins) අඩු කිරීම */
        .login-header {
            text-align: center;
            margin-bottom: 22px;
        }
        .login-header h2 {
            font-size: 20px; /* 24px සිට 20px දක්වා කුඩා කලා */
            font-weight: 700;
            color: #ffffff;
            margin: 0 0 4px 0;
        }
        .login-header h2 span {
            color: #38bdf8; 
        }
        .login-header p {
            font-size: 11.5px; /* තවත් කුඩා කලා */
            color: #94a3b8;
            margin: 0;
            letter-spacing: 0.2px;
        }

        /* Inputs සහ Labels වල සයිස් එක සකස් කිරීම */
        .input-group {
            margin-bottom: 16px; /* පරතරය අඩු කලා */
        }
        .input-group label {
            display: block;
            font-size: 11px; /* Label එක සිහින් සහ කුඩා කලා */
            font-weight: 600;
            color: #94a3b8;
            margin-bottom: 6px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .input-group input {
            width: 100%;
            padding: 10px 14px; /* Input එක ඇතුලේ ඉඩ (Height) අඩු කලා */
            background-color: #0f172a; 
            border: 1px solid #334155;
            border-radius: 8px;
            color: #ffffff;
            font-size: 14px; /* Font size එක 14px කලා */
            font-family: 'Inter', sans-serif;
            box-sizing: border-box;
            transition: all 0.2s ease;
        }
        .input-group input:focus {
            outline: none;
            border-color: #38bdf8;
            box-shadow: 0 0 0 3px rgba(56, 189, 248, 0.1);
        }

        /* Button එකත් බොක්ස් එකට මැච් වෙන්න Sleek කිරීම */
        .login-btn {
            width: 100%;
            padding: 11px; /* බටන් එකේ උස ලස්සන ගාණකට අඩු කලා */
            background: linear-gradient(135deg, #0284c7 0%, #38bdf8 100%); 
            border: none;
            border-radius: 8px;
            color: #ffffff;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
            box-shadow: 0 4px 10px rgba(56, 189, 248, 0.15);
            margin-top: 5px;
        }
        .login-btn:hover {
            transform: translateY(-1.5px);
            box-shadow: 0 6px 15px rgba(56, 189, 248, 0.3);
        }

        .error-msg {
            background-color: rgba(239, 68, 68, 0.1);
            color: #fca5a5;
            border: 1px solid rgba(239, 68, 68, 0.15);
            padding: 10px;
            border-radius: 6px;
            font-size: 12px;
            margin-bottom: 16px;
            text-align: center;
        }
    </style>
</head>
<body>

    <div class="login-container">
        <div class="login-header">
            <h2>🌾 Minsada <span>Rice Mill</span></h2>
            <p>Dashboard Login</p>
        </div>

        <?php if(!empty($error)): ?>
            <div class="error-msg"><?php echo $error; ?></div>
        <?php endif; ?>

        <form action="index.php" method="POST">
            <div class="input-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" placeholder="Enter Username" required autocomplete="off">
            </div>

            <div class="input-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" placeholder="Enter Password" required>
            </div>

            <button type="submit" name="login" class="login-btn">Secure Login 🔒</button>
        </form>
    </div>

</body>
</html>