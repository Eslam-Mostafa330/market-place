<!DOCTYPE html>
<html lang="en" dir="ltr">
    <head>
        <meta charset="UTF-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1.0" />
        <meta http-equiv="X-UA-Compatible" content="IE=edge" />
        <title>Login Verification Code</title>
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
                    Login Verification Code
                </h2>
                <p style="color:#666;font-size:16px;font-weight:500;max-width:700px;margin:0 0 20px;line-height:1.5;">
                    We received a login attempt for your {{ $appName }} account.
                    Use the verification code below to complete your login:
                </p>

                <div style="text-align:center;margin:0 0 24px;">
                    <div style="display:inline-block;background:#f0f6ff;border:2px dashed #2271C8;border-radius:12px;padding:20px 40px;">
                        <p style="margin:0 0 6px;color:#666;font-size:13px;font-weight:500;letter-spacing:1px;text-transform:uppercase;">
                            Your verification code
                        </p>
                        <p style="margin:0;color:#2271C8;font-size:42px;font-weight:700;letter-spacing:10px;">
                            {{ $code }}
                        </p>
                    </div>
                </div>

                <div style="background:#EFEFEF;border-left:5px solid #40A2FB;padding:15px;margin:20px 0;text-align:left;color:#444;font-size:14px;border-radius:8px;">
                    <p style="margin:0 0 18px;color:#2271C8;font-weight:600;">
                        Notes:
                    </p>
                    <ul style="margin:0;padding-left:19px;list-style:disc;">
                        <li style="margin-bottom:10px;">
                            This code will expire in <strong>10 minutes</strong>.
                        </li>
                        <li style="margin-bottom:10px;">
                            Never share this code with anyone.
                        </li>
                        <li>
                            If you didn't attempt to login, please secure your account immediately.
                        </li>
                    </ul>
                </div>
            </div>
            <div style="padding:20px;font-size:13px;color:#666;line-height:1.6;">
                <p style="margin:0 0 4px 0;">Thank you,</p>
                <strong>The {{ $appName }} Team</strong>
            </div>
        </div>
    </body>
</html>