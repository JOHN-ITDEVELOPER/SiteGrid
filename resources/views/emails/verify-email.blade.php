<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Verify Your Email Address</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Roboto', 'Helvetica', 'Arial', sans-serif;
            color: #333;
            line-height: 1.6;
            background-color: #f5f5f5;
        }
        
        .container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
            padding: 40px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #f97316;
            padding-bottom: 20px;
        }
        
        .logo {
            font-size: 28px;
            font-weight: 700;
            color: #1e1b4b;
        }
        
        .logo-subtitle {
            color: #f97316;
            font-size: 14px;
            margin-top: 5px;
        }
        
        .content {
            margin: 30px 0;
        }
        
        .greeting {
            font-size: 16px;
            color: #1e1b4b;
            margin-bottom: 15px;
            font-weight: 600;
        }
        
        .message {
            color: #666;
            margin-bottom: 20px;
            font-size: 14px;
        }
        
        .verify-button {
            display: inline-block;
            background-color: #f97316;
            color: #ffffff;
            padding: 12px 30px;
            text-decoration: none;
            border-radius: 6px;
            font-weight: 600;
            margin: 20px 0;
            text-align: center;
            transition: background-color 0.3s;
        }
        
        .verify-button:hover {
            background-color: #ea580c;
        }
        
        .alt-link {
            color: #666;
            font-size: 13px;
            margin-top: 15px;
        }
        
        .alt-link a {
            color: #f97316;
            text-decoration: none;
            word-break: break-all;
        }
        
        .footer {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #e5e7eb;
            text-align: center;
            color: #999;
            font-size: 12px;
        }
        
        .footer-text {
            margin-bottom: 10px;
        }
        
        .warning {
            background-color: #fef3c7;
            border-left: 4px solid #f59e0b;
            padding: 12px;
            margin: 20px 0;
            border-radius: 4px;
            font-size: 13px;
            color: #92400e;
        }
        
        .info-box {
            background-color: #dbeafe;
            border-left: 4px solid #0c4a6e;
            padding: 12px;
            margin: 20px 0;
            border-radius: 4px;
            font-size: 13px;
            color: #0c4a6e;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <img src="{{ asset('images/logo.png', true) }}" alt="SiteGrid Logo" style="height: 50px; width: auto; margin-bottom: 10px;">
            <div class="logo-subtitle">Workforce & Site Operations Management</div>
        </div>

        <!-- Content -->
        <div class="content">
            <p class="greeting">Welcome to SiteGrid!</p>
            
            <p class="message">
                Thank you for creating your account! To get started with managing your workforce and site operations, please verify your email address by clicking the button below.
            </p>

            <!-- Verify Button -->
            <p style="text-align: center;">
                <a href="{{ $verificationUrl }}" class="verify-button">Verify Your Email</a>
            </p>

            <!-- Alternative Link -->
            <div class="alt-link">
                <p>Or copy and paste this link in your browser:</p>
                <p>{{ $verificationUrl }}</p>
            </div>

            <!-- Info Box -->
            <div class="info-box">
                ℹ️ This verification link will expire in 60 minutes.
            </div>

            <!-- Security Warning -->
            <div class="warning">
                ⚠️ If you didn't create a SiteGrid account, you can safely ignore this email.
            </div>

            <!-- Next Steps -->
            <p class="message" style="margin-top: 30px;">
                <strong>What's next:</strong><br>
                1. Verify your email address to unlock your account<br>
                2. Complete your profile with your details<br>
                3. Create and manage your sites<br>
                4. Onboard your workforce and start tracking operations<br>
            </p>
        </div>

        <!-- Footer -->
        <div class="footer">
            <p class="footer-text">
                © {{ now()->year }} SiteGrid. All rights reserved.
            </p>
            <p class="footer-text">
                <a href="https://sitegrid.io" style="color: #f97316; text-decoration: none;">www.sitegrid.io</a>
            </p>
            <p class="footer-text">
                This is an automated email. Please do not reply directly to this message.
            </p>
        </div>
    </div>
</body>
</html>
