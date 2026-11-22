<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email vérifié — Clinigoo</title>
</head>
<body style="margin:0;padding:0;min-height:100vh;background-color:#f4f7fa;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Helvetica,Arial,sans-serif;display:flex;align-items:center;justify-content:center;">
    <div style="max-width:460px;width:90%;background:#ffffff;border-radius:16px;padding:36px;text-align:center;box-shadow:0 8px 30px rgba(0,0,0,0.08);">
        <div style="width:64px;height:64px;border-radius:50%;background:{{ $verified ? '#d1fae5' : '#fee2e2' }};color:{{ $verified ? '#059669' : '#dc2626' }};font-size:32px;line-height:64px;margin:0 auto 20px;">
            {{ $verified ? '✓' : '✕' }}
        </div>
        <h1 style="margin:0 0 12px;font-size:22px;color:#111827;">
            {{ $verified ? 'Adresse email vérifiée !' : 'Lien de vérification invalide' }}
        </h1>
        <p style="margin:0 0 24px;font-size:14px;line-height:1.6;color:#4b5563;">
            {{ $message }}
        </p>
        @if(!empty($loginUrl))
            <a href="{{ $loginUrl }}" style="display:inline-block;background:#00a8cc;color:#ffffff;font-weight:700;font-size:14px;text-decoration:none;padding:12px 24px;border-radius:10px;">
                Accéder à mon espace Clinigoo
            </a>
        @endif
    </div>
</body>
</html>
