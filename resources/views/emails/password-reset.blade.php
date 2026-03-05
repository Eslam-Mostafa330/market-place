<!DOCTYPE html>
<html lang="en" dir="ltr">
    <head>
        <meta charset="UTF-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1.0" />
        <meta http-equiv="X-UA-Compatible" content="IE=edge" />
        <title>Password Reset Request</title>
    </head>
    <body style="background:#f5f5f5;padding:20px;margin:0;font-family:Arial,Helvetica,sans-serif;">
        <div style="max-width:600px;margin:0 auto;background:#ffffff;">
        <div style="text-align:center;padding:20px;">
            <img src="{{ $message->embed(public_path('assets/images/img.png')) }}" alt="{{ $appName }}" style="display:block;margin:0 auto 10px;width:400px;max-width:100%;height:auto;border:0;" />
        </div>
        <div style="padding:20px;">
            <p style="color:#888888;font-weight:500;margin:0 0 14px;font-size:20px;">
                Welcome Back {{ $userName }}
            </p>
            <h2 style="color:#323232;margin:0 0 8px;font-weight:700;font-size:27px;">
                Reset Your Password
            </h2>
            <p style="color:#666;font-size:16px;font-weight:500;max-width:700px;margin:0 0 20px;line-height:1.5;">
                We received a request to reset your password for your Elzero Academy
                account. If you made this request, please click the link below to
                create a new password:
            </p>
            <table border="0" cellpadding="0" cellspacing="0" style="margin:0 0 20px;">
                <tr>
                    <td style="border-radius:24px;background:#2271C8;">
                        <a href="{{ $resetUrl }}" target="_blank"
                            style="display:inline-block;padding:16px 32px;background:#2271C8;color:#ffffff;text-decoration:none;font-size:16px;border-radius:24px;font-weight:600;line-height:1.5;">
                            Reset Your Password
                        </a>
                    </td>
                </tr>
            </table>
            <div style="background:#EFEFEF;border-left:5px solid #40A2FB;padding:15px;margin:20px 0;text-align:left;color:#444;font-size:14px;border-radius:8px;">
                <p style="margin:0 0 18px;color:#2271C8;font-weight:600;">
                    Notes:
                </p>
                <ul style="margin:0;padding-left:19px;list-style:disc;">
                    <li style="margin-bottom:10px;">
                        For your protection, this link will expire in <strong>30 minutes</strong>.
                    </li>
                    <li>
                        If you didn't request a password reset, you can safely ignore this
                        email — your account will remain secure
                    </li>
                </ul>
            </div>
            <div style="margin-top:20px;">
                <p style="margin:0 0 12px;font-size:16px;font-weight:500;">
                    Or copy and paste this URL into your browser:
                </p>
                <a href="{{ $resetUrl }}" target="_blank"
                    style="display:inline-block;color:#2271C8;font-size:14px;font-weight:500;text-decoration:underline;word-break:break-all;">
                    {{ $resetUrl }}
                </a>
            </div>
        </div>
        <div style="padding:20px;font-size:13px;color:#666;line-height:1.6;">
            <p style="margin:0 0 4px 0;">Thank you,</p>
            <strong>The {{ $appName }} Team</strong>
        </div>
        </div>
    </body>
</html>