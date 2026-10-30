<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $siteName }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            background-color: #2d5a27;
            color: white;
            padding: 20px;
            text-align: center;
            border-radius: 8px 8px 0 0;
        }
        .content {
            background-color: #f9f9f9;
            padding: 30px;
            border-radius: 0 0 8px 8px;
        }
        .alert-box {
            background-color: #f8d7da;
            border: 1px solid #f5c6cb;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>🎄 {{ $siteName }}</h1>
    </div>
    <div class="content">
        <p>Bonjour,</p>

        <div class="alert-box">
            <strong>{{ $requestCount }} demandes de liens d'accès</strong> ont été reçues en moins de {{ $windowMinutes }} minutes,
            ce qui ressemble à une attaque automatisée (robots depuis plusieurs adresses IP).
        </div>

        <p>Par sécurité, l'envoi de liens d'accès depuis la page d'accueil est suspendu jusqu'au <strong>{{ $blockedUntil }}</strong>.
            Les familles ayant déjà reçu un lien peuvent continuer à l'utiliser.</p>

        <p>Le blocage est levé automatiquement. Si l'attaque continue, il sera relancé et vous recevrez une nouvelle alerte.</p>
    </div>
</body>
</html>
