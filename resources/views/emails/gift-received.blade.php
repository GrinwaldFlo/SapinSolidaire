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
        .info-box {
            background-color: #d4edda;
            border: 1px solid #c3e6cb;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
        }
        .code {
            font-size: 1.5em;
            font-weight: bold;
            color: #2d5a27;
            background-color: #fff;
            padding: 10px 20px;
            border-radius: 5px;
            display: inline-block;
            margin: 10px 0;
        }
        .address-box {
            background-color: #fff;
            border: 1px solid #ddd;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
        }
        .footer {
            text-align: center;
            margin-top: 20px;
            color: #666;
            font-size: 0.9em;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>🎄 {{ $siteName }}</h1>
    </div>
    <div class="content">
        <p>Bonjour,</p>
        
        <p>Nous avons le plaisir de vous informer que le cadeau pour <strong>{{ $childName }}</strong> est arrivé !</p>
        
        <div class="info-box">
            <strong>Détails :</strong><br>
            <ul>
                <li><strong>Prénom :</strong> {{ $childName }}</li>
                <li><strong>Cadeau :</strong> {{ $gift }}</li>
                <li><strong>Code de retrait :</strong> <span class="code">{{ $code }}</span></li>
            </ul>
        </div>
        
        @if($pickupDate || $pickupAddress)
        <p>Vous pouvez venir chercher le cadeau @if($pickupDate)à partir du <strong>{{ $pickupDate }}</strong>@endif à l'adresse suivante :</p>
        
        @if($pickupAddress)
        <div class="address-box">
            {!! nl2br(e($pickupAddress)) !!}
        </div>
        @endif
        @endif
        
        <p><strong>Merci de vous munir du code de retrait lors de votre venue.</strong></p>
        
        <p>À bientôt !</p>
        
        <p>Cordialement,<br>L'équipe {{ $siteName }}</p>
    </div>
    <div class="footer">
        <p>Cet e-mail a été envoyé automatiquement. Merci de ne pas y répondre directement.</p>
    </div>
</body>
</html>
