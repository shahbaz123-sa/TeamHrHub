<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body {
            font-family: Arial, sans-serif;
            color: #333;
            line-height: 1.6;
        }
        .container {
            max-width: 783px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            padding: 20px;
            text-align: center;
        }
        .subcontent {
            color: #666;
            font-size: 16px;
            font-weight: 400;
            letter-spacing: -0.31px;
        }
        .content { padding: 20px; }
        .username {
            font-size: 16px;
            line-height: 24px;
            letter-spacing: -0.31px;
        }
        .url-block {
            word-break: break-all;
            background-color: #F9F9F9;
            padding: 10px;
            border-radius: 4px;
            color: #D45D36;
            font-size: 14px;
            line-height: 21px;
            letter-spacing: -0.15px;
        }
        .footer {
            padding: 30px 0 0;
            font-size: 12px;
        }
        .call-us-section {
            text-align: center;
            background-color: #D45D36;
            min-height: 53px;
            color: #fff;
        }
        .call-us-text {
            margin: 0;
            padding-top: 16px;
            font-size: 14px;
            line-height: 21px;
            letter-spacing: -0.15px;
            font-weight: 400;
        }
        .customer-care {
            display: flex;
            justify-content: space-around;
            text-align: center;
            margin: 32px 30px 20px;
        }
        .customer-care div p {
            font-size: 11px;
            font-weight: bold;
            line-height: 16.5px;
            letter-spacing: 0.08px;
        }
        .copyright p {
            color: rgba(79, 90, 104, 0.6);
            font-size: 14px;
            line-height: 20px;
            letter-spacing: 0;
            margin-top: 30px;
            padding: 0 20px;
        }
        .btn {
            display: inline-block;
            padding: 10px 20px;
            background-color: #D45D36;
            color: #fff !important;
            text-decoration: none;
            border-radius: 4px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <img src='{{ asset("/images/company-logo.png") }}' alt='logo' width='133'>
            <h2>Password Reset Request</h2>
            <p class="subcontent">
                We received a request to reset your password. This link will expire in {{ env('RESET_PASSWORD_LINK_TIMEOUT', 10) }} minutes.
            </p>
        </div>
        
        <div class="content">
            <p class="username"><strong>Hi {{ $user->name }},</strong></p>
            
            <p class="subcontent">We received a request to reset your password. Click the button below to reset it. This link will expire in {{ env('RESET_PASSWORD_LINK_TIMEOUT', 10) }} minutes.</p>
            
            <p style="text-align: center; margin: 30px 0;">
                <a href="{{ $resetUrl }}" class="btn" style="color: #ffffff;">Reset Password</a>
            </p>
            
            <p class="subcontent">Or copy and paste this URL in your browser:</p>
            <p class="url-block">
                {{ $resetUrl }}
            </p>
            
            <p class="subcontent">If you didn't request a password reset, you can ignore this email. Your password will not be changed.</p>
            
            <p class="subcontent" style="margin-bottom: 0">Best regards,</p>
            <p class="username" style="margin-top: 5px"><strong>The {{ config('app.name') }} Team</strong></p>
        </div>
        
        <div class="footer">
            <div class="customer-care">
                <div>
                    <img
                        src="{{ asset('/images/customer-care/service.png') }}"
                        alt="customer-service"
                        width="36"
                        height="36"
                        style="vertical-align: middle;">
                    <p>CUSTOMER<br>SERVICE</p>
                </div>
                <div>
                    <img
                        src="{{ asset('/images/customer-care/shipping.png') }}"
                        alt="customer-service"
                        width="36"
                        height="36"
                        style="vertical-align: middle;">
                    <p>FREE SHIPPING<br>ORDERS Rs49+</p>
                </div>
                <div>
                    <img
                        src="{{ asset('/images/customer-care/satisfaction.png') }}"
                        alt="customer-service"
                        width="36"
                        height="36"
                        style="vertical-align: middle;">
                    <p>SATISFACTION<br>GUARANTEED</p>
                </div>
                <div>
                    <img
                        src="{{ asset('/images/customer-care/return.png') }}"
                        alt="customer-service"
                        width="36"
                        height="36"
                        style="vertical-align: middle;">
                    <p>HASSLE-FREE<br>RETURNS</p>
                </div>
            </div>
            <hr/>
            <div class="copyright">
                <p>
                    If you did not sign up for this account you can ignore this email and the account will be deleted.
                </p>
                <p>
                    &copy; 2025 Zarea. All rights reserved. You received this email because you signed up for an app that helps you create your emails. To update your email subscription preferences
                </p>
            </div>
        </div>
    </div>
</body>
</html>
