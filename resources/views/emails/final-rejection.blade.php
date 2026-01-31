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
        .comment-box {
            background-color: #f8d7da;
            border: 1px solid #f5c6cb;
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
        
        <p>Nous avons examiné votre demande de cadeau et nous sommes dans le regret de vous informer que nous ne pouvons pas y donner suite.</p>
        
        <div class="comment-box">
            <strong>Motif :</strong><br>
            {{ $comment }}
        </div>
        
        <p>Nous vous remercions de votre compréhension.</p>
        
        <p>Si vous pensez qu'il s'agit d'une erreur, vous pouvez nous contacter.</p>
        
        <p>Cordialement,<br>L'équipe {{ $siteName }}</p>
    </div>
    <div class="footer">
        <p>Cet e-mail a été envoyé automatiquement. Merci de ne pas y répondre directement.</p>
    </div>
</body>
</html>
