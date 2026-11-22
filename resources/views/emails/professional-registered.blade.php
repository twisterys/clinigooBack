<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vérification de votre adresse email — Clinigoo</title>
</head>
<body style="margin:0;padding:0;background-color:#f4f7fa;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Helvetica,Arial,sans-serif;color:#1f2937;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background-color:#f4f7fa;padding:32px 16px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:600px;background-color:#ffffff;border-radius:16px;overflow:hidden;box-shadow:0 4px 16px rgba(0,0,0,0.06);">
                    <!-- Header -->
                    <tr>
                        <td style="background:linear-gradient(135deg,#0089a8 0%,#00a8cc 60%,#1ac2e6 100%);padding:32px 36px;text-align:left;">
                            <div style="font-size:24px;font-weight:800;color:#ffffff;letter-spacing:-0.3px;">
                                Clinigoo
                            </div>
                            <div style="margin-top:6px;font-size:13px;color:#e0f7fa;">
                                Vérification de votre compte professionnel de santé
                            </div>
                        </td>
                    </tr>

                    <!-- Body -->
                    <tr>
                        <td style="padding:36px;">
                            <h1 style="margin:0 0 14px 0;font-size:20px;font-weight:700;color:#111827;">
                                Bonjour {{ $recipientName }},
                            </h1>

                            <p style="margin:0 0 22px 0;font-size:15px;line-height:1.6;color:#4b5563;">
                                Merci pour votre inscription sur <strong>Clinigoo</strong>. Pour confirmer votre adresse email et finaliser votre dossier professionnel, utilisez le code de vérification ci-dessous ou cliquez sur le bouton.
                            </p>

                            <!-- OTP Box -->
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background-color:#f0fdfa;border:1px solid #99f6e4;border-radius:12px;margin-bottom:24px;">
                                <tr>
                                    <td style="padding:22px;text-align:center;">
                                        <div style="font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:0.8px;color:#0f766e;margin-bottom:10px;">
                                            Votre code de vérification (OTP)
                                        </div>
                                        <div style="font-family:'Courier New',Courier,monospace;font-size:34px;font-weight:800;letter-spacing:10px;color:#0089a8;margin:4px 0;">
                                            {{ $otpCode }}
                                        </div>
                                        <div style="margin-top:10px;font-size:12px;color:#64748b;">
                                            Ce code expire dans <strong>15 minutes</strong>.
                                        </div>
                                    </td>
                                </tr>
                            </table>

                            <!-- Verification Button -->
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="margin-bottom:24px;">
                                <tr>
                                    <td align="center">
                                        <a href="{{ $verificationUrl }}" style="display:inline-block;background-color:#00a8cc;color:#ffffff;font-size:15px;font-weight:700;text-decoration:none;padding:14px 28px;border-radius:10px;">
                                            Vérifier mon adresse email
                                        </a>
                                    </td>
                                </tr>
                            </table>

                            <!-- Fallback URL -->
                            <p style="margin:0 0 24px 0;font-size:12px;line-height:1.6;color:#6b7280;word-break:break-all;">
                                Si le bouton ne fonctionne pas, copiez et collez ce lien dans votre navigateur :<br>
                                <a href="{{ $verificationUrl }}" style="color:#0089a8;text-decoration:underline;">{{ $verificationUrl }}</a>
                            </p>

                            <!-- Summary Card -->
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background-color:#f8fafc;border:1px solid #e2e8f0;border-radius:12px;margin-bottom:24px;">
                                <tr>
                                    <td style="padding:18px 22px;">
                                        <div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.6px;color:#0089a8;margin-bottom:10px;">
                                            Récapitulatif de votre inscription
                                        </div>
                                        <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="font-size:13px;color:#374151;">
                                            <tr>
                                                <td style="padding:4px 0;color:#6b7280;width:40%;">Email</td>
                                                <td style="padding:4px 0;font-weight:600;color:#111827;">{{ $recipientEmail }}</td>
                                            </tr>
                                            <tr>
                                                <td style="padding:4px 0;color:#6b7280;">Profession</td>
                                                <td style="padding:4px 0;font-weight:600;color:#111827;">{{ $professionName }}</td>
                                            </tr>
                                            @if(!empty($city))
                                            <tr>
                                                <td style="padding:4px 0;color:#6b7280;">Ville</td>
                                                <td style="padding:4px 0;font-weight:600;color:#111827;">{{ $city }}</td>
                                            </tr>
                                            @endif
                                        </table>
                                    </td>
                                </tr>
                            </table>

                            <p style="margin:0 0 16px 0;font-size:13px;line-height:1.6;color:#6b7280;">
                                Si vous n'êtes pas à l'origine de cette inscription, vous pouvez ignorer cet email en toute sécurité.
                            </p>

                            <p style="margin:0;font-size:14px;color:#4b5563;">
                                Cordialement,<br>
                                <strong style="color:#111827;">L'équipe Clinigoo Maroc</strong>
                            </p>
                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td style="background-color:#f8fafc;border-top:1px solid #e5e7eb;padding:20px 36px;text-align:center;font-size:12px;color:#6b7280;">
                            Besoin d'aide ? Contactez-nous à
                            <a href="mailto:support@clinigoo.ma" style="color:#0089a8;text-decoration:none;font-weight:600;">support@clinigoo.ma</a>
                            <div style="margin-top:6px;color:#9ca3af;">
                                © {{ date('Y') }} Clinigoo Santé Maroc. Tous droits réservés.
                            </div>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
