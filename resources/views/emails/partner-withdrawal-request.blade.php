<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Partner Withdrawal Request</title>
</head>
<body style="margin:0; padding:0; background-color:#0b1020; font-family:Arial, Helvetica, sans-serif; color:#e5e7eb;">
<table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="background-color:#0b1020; margin:0; padding:30px 0;">
    <tr>
        <td align="center">
            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="max-width:680px; background:linear-gradient(180deg, #161b33 0%, #10162b 100%); border:1px solid #2a3158; border-radius:20px; overflow:hidden; box-shadow:0 10px 35px rgba(0,0,0,0.35);">

                <tr>
                    <td style="padding:28px 32px; background:linear-gradient(90deg, #6d4aff 0%, #8b5cf6 100%);">
                        <h1 style="margin:0; font-size:24px; line-height:32px; color:#ffffff; font-weight:700;">
                            Partner Withdrawal Request
                        </h1>
                        <p style="margin:8px 0 0; font-size:14px; line-height:22px; color:#efeaff;">
                            A partner has submitted a manual withdrawal request.
                        </p>
                    </td>
                </tr>

                <tr>
                    <td style="padding:32px;">
                        <p style="margin:0 0 24px; font-size:15px; line-height:24px; color:#cbd5e1;">
                            Please review the request details below and process the payout manually.
                        </p>

                        <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="border-collapse:collapse; background-color:#0f172a; border:1px solid #2a3158; border-radius:14px; overflow:hidden;">
                            <tr>
                                <td colspan="2" style="padding:16px 20px; background-color:#151c36; font-size:16px; font-weight:700; color:#ffffff; border-bottom:1px solid #2a3158;">
                                    Partner Information
                                </td>
                            </tr>
                            <tr>
                                <td style="padding:14px 20px; width:220px; font-size:14px; color:#94a3b8; border-bottom:1px solid #24304f;">
                                    Partner Name
                                </td>
                                <td style="padding:14px 20px; font-size:14px; color:#ffffff; border-bottom:1px solid #24304f;">
                                    {{ $partner->name }}
                                </td>
                            </tr>
                            <tr>
                                <td style="padding:14px 20px; font-size:14px; color:#94a3b8; border-bottom:1px solid #24304f;">
                                    Partner ID
                                </td>
                                <td style="padding:14px 20px; font-size:14px; color:#ffffff; border-bottom:1px solid #24304f;">
                                    #{{ $partner->id }}
                                </td>
                            </tr>
                            <tr>
                                <td style="padding:14px 20px; font-size:14px; color:#94a3b8;">
                                    Email
                                </td>
                                <td style="padding:14px 20px; font-size:14px; color:#ffffff;">
                                    {{ $partner->email }}
                                </td>
                            </tr>
                        </table>

                        <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="margin-top:20px; border-collapse:collapse; background-color:#0f172a; border:1px solid #2a3158; border-radius:14px; overflow:hidden;">
                            <tr>
                                <td colspan="2" style="padding:16px 20px; background-color:#151c36; font-size:16px; font-weight:700; color:#ffffff; border-bottom:1px solid #2a3158;">
                                    Withdrawal Details
                                </td>
                            </tr>
                            <tr>
                                <td style="padding:14px 20px; width:220px; font-size:14px; color:#94a3b8; border-bottom:1px solid #24304f;">
                                    Available Commission
                                </td>
                                <td style="padding:14px 20px; font-size:14px; color:#c4b5fd; font-weight:700; border-bottom:1px solid #24304f;">
                                    {{ $availableAmount }} USDT
                                </td>
                            </tr>
                            <tr>
                                <td style="padding:14px 20px; font-size:14px; color:#94a3b8; border-bottom:1px solid #24304f;">
                                    Requested Amount
                                </td>
                                <td style="padding:14px 20px; font-size:14px; color:#ffffff; border-bottom:1px solid #24304f;">
                                    {{ $availableAmount }} USDT
                                </td>
                            </tr>
                            <tr>
                                <td style="padding:14px 20px; font-size:14px; color:#94a3b8;">
                                    Wallet Address
                                </td>
                                <td style="padding:14px 20px; font-size:14px; color:#ffffff; word-break:break-all;">
                                    {{ $walletAddress }}
                                </td>
                            </tr>
                        </table>

                        @if($comment)
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="margin-top:20px; border-collapse:collapse; background-color:#0f172a; border:1px solid #2a3158; border-radius:14px; overflow:hidden;">
                                <tr>
                                    <td style="padding:16px 20px; background-color:#151c36; font-size:16px; font-weight:700; color:#ffffff; border-bottom:1px solid #2a3158;">
                                        Partner Comment
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding:18px 20px; font-size:14px; line-height:24px; color:#e2e8f0;">
                                        {{ $comment }}
                                    </td>
                                </tr>
                            </table>
                        @endif

                        <div style="margin-top:24px; padding:16px 18px; background-color:rgba(139,92,246,0.08); border:1px solid rgba(139,92,246,0.25); border-radius:12px;">
                            <p style="margin:0; font-size:13px; line-height:22px; color:#cbd5e1;">
                                This is an automated message. Partner payouts are currently processed manually by the manager.
                            </p>
                        </div>
                    </td>
                </tr>

                <tr>
                    <td style="padding:18px 32px; border-top:1px solid #2a3158; background-color:#0d1327;">
                        <p style="margin:0; font-size:12px; line-height:20px; color:#94a3b8; text-align:center;">
                            Trastly Partner System
                        </p>
                    </td>
                </tr>

            </table>
        </td>
    </tr>
</table>
</body>
</html>
