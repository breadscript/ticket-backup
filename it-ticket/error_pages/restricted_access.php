<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Access Restricted - IT Ticket System</title>
    <link rel="stylesheet" href="../assets/css/bootstrap.min.css" />
    <link rel="stylesheet" href="../assets/font-awesome/4.5.0/css/font-awesome.min.css" />
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            padding: 20px;
        }
        
        .container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            padding: 50px;
            text-align: center;
            max-width: 550px;
            width: 100%;
        }
        
        .icon {
            font-size: 80px;
            color: #e74c3c;
            margin-bottom: 25px;
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }
        
        .title {
            color: #2c3e50;
            font-size: 32px;
            font-weight: bold;
            margin-bottom: 10px;
        }
        
        .subtitle {
            color: #7f8c8d;
            font-size: 18px;
            margin-bottom: 30px;
        }
        
        .message {
            color: #5a6c7d;
            font-size: 16px;
            margin-bottom: 25px;
            line-height: 1.6;
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            border-left: 4px solid #3498db;
        }
        
        .warning-box {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            color: #856404;
            padding: 15px;
            border-radius: 8px;
            margin: 20px 0;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .info-box {
            background: #e8f4fd;
            border: 1px solid #bee5eb;
            color: #0c5460;
            padding: 15px;
            border-radius: 8px;
            margin: 20px 0;
            text-align: left;
        }
        
        .buttons {
            display: flex;
            gap: 15px;
            justify-content: center;
            margin: 30px 0;
            flex-wrap: wrap;
        }
        
        .btn {
            padding: 12px 25px;
            font-size: 16px;
            border-radius: 25px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
            font-weight: 500;
            min-width: 140px;
            justify-content: center;
        }
        
        .btn-primary {
            background: linear-gradient(45deg, #3498db, #2980b9);
            color: white;
        }
        
        .btn-primary:hover {
            background: linear-gradient(45deg, #2980b9, #1f618d);
            color: white;
            text-decoration: none;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(52, 152, 219, 0.3);
        }
        
        .btn-secondary {
            background: linear-gradient(45deg, #95a5a6, #7f8c8d);
            color: white;
        }
        
        .btn-secondary:hover {
            background: linear-gradient(45deg, #7f8c8d, #6c7b7d);
            color: white;
            text-decoration: none;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(149, 165, 166, 0.3);
        }
        
        .footer {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #e9ecef;
            color: #95a5a6;
            font-size: 14px;
        }
        
        .countdown {
            background: #f8f9fa;
            padding: 10px;
            border-radius: 5px;
            margin-top: 15px;
            display: inline-block;
        }
        
        @media (max-width: 768px) {
            .container {
                padding: 40px 30px;
            }
            
            .title {
                font-size: 28px;
            }
            
            .buttons {
                flex-direction: column;
                align-items: center;
            }
            
            .btn {
                width: 100%;
                max-width: 250px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="icon">
            <i class="fa fa-shield"></i>
        </div>
        
        <h1 class="title">Access Restricted</h1>
        
        <div class="message">
            <strong>What happened?</strong><br>
            You manually changed the URL in your browser, which triggered our security system. 
            This type of direct access is not permitted to protect sensitive data.
        </div>
        
        <div class="warning-box">
            <i class="fa fa-exclamation-triangle"></i>
            <span><strong>Security Notice:</strong> This unauthorized access attempt has been logged and reported.</span>
        </div>
        
        <div class="info-box">
            <strong><i class="fa fa-info-circle"></i> How to access properly:</strong><br>
            • Use the navigation menu or buttons within the application<br>
            • Click on links instead of typing URLs manually<br>
            • If you need help, contact your system administrator
        </div>
        
        <div class="buttons">
            <a href="../index.php" class="btn btn-primary">
                <i class="fa fa-home"></i> Return to Home
            </a>
            <a href="../login.php" class="btn btn-secondary">
                <i class="fa fa-sign-in"></i> Login Again
            </a>
        </div>
        
        <div class="footer">
            <p><strong>Access Time:</strong> <?php echo date('F j, Y \a\t g:i A'); ?></p>
            <p><strong>IP Address:</strong> <?php echo $_SERVER['REMOTE_ADDR'] ?? 'Unknown'; ?></p>
            
            <div class="countdown">
                <i class="fa fa-clock-o"></i> Auto-redirect in <span id="timer">30</span> seconds
            </div>
        </div>
    </div>

    <script>
        // Disable back button and navigation
        window.history.replaceState(null, null, window.location.href);
        window.onpopstate = function() {
            window.history.pushState(null, null, window.location.href);
            alert('⚠️ Navigation disabled for security reasons.\n\nPlease use the provided buttons to continue.');
        };
        
        // Disable keyboard shortcuts
        document.addEventListener('keydown', function(e) {
            if (e.keyCode === 8 || e.keyCode === 116 || e.keyCode === 123 || 
                (e.altKey && e.keyCode === 37) || (e.ctrlKey && e.keyCode === 85)) {
                e.preventDefault();
                alert('⚠️ This action is blocked for security reasons.');
            }
        });
        
        // Disable right-click
        document.addEventListener('contextmenu', function(e) {
            e.preventDefault();
        });
        
        // Countdown timer
        let timeLeft = 30;
        const timerElement = document.getElementById('timer');
        
        const countdown = setInterval(() => {
            timeLeft--;
            timerElement.textContent = timeLeft;
            
            if (timeLeft <= 0) {
                clearInterval(countdown);
                window.location.href = '../index.php';
            }
        }, 1000);
        
        // Smooth page load animation
        document.addEventListener('DOMContentLoaded', function() {
            const container = document.querySelector('.container');
            container.style.opacity = '0';
            container.style.transform = 'translateY(20px)';
            
            setTimeout(() => {
                container.style.transition = 'all 0.5s ease';
                container.style.opacity = '1';
                container.style.transform = 'translateY(0)';
            }, 100);
        });
        
        // Log security event
        console.log('Security: Restricted access page loaded at ' + new Date().toISOString());
    </script>
</body>
</html>