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
        .important {
            background-color: #fff3cd;
            border: 1px solid #ffc107;
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
        .signature {
            margin-top: 30px;
            padding-top: 15px;
            border-top: 1px solid #ddd;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>🎄 {{ $siteName }}</h1>
    </div>
    <div class="content">
        <p>Chère famille {{ $familyName }},</p>

        <p>@multiline($introText)</p>

        <div class="info-box">
            <strong>Les cadeaux sont prêts !</strong>
        </div>

        @if($slotDate && $slotStartTime && $slotEndTime)
        <p>Merci de venir chercher vos cadeaux le <strong>{{ $slotDate }}</strong> :</p>
        <p style="text-align: center;">🕐 <strong>{{ $slotStartTime }}</strong> - <strong>{{ $slotEndTime }}</strong></p>
        <a href="https://www.google.com/maps/search/?api=1&query={{ urlencode($pickupAddress) }}" target="_blank" style="color:#2d5a27!important;">{{ $pickupAddress }}</a>
        @if($googleCalendarUrl)
        <p style="text-align: center; margin-top: 15px;">
            📅 <a href="{{ $googleCalendarUrl }}" target="_blank" style="color:#2d5a27!important;">Ajouter à mon agenda Google</a>
        </p>
        @endif
        @endif

        <div class="important">
            <p>@multiline($remindersText)</p>
        </div>

        <p>@multiline($closingText)</p>

        @if($responsibleName || $responsiblePhone || $responsibleEmail)
        <div class="signature">
            @if($responsibleName)
                <p><strong>{{ $responsibleName }}</strong></p>
            @endif
            @if($responsiblePhone)
                <p>Téléphone : {{ $responsiblePhone }}</p>
            @endif
            @if($responsibleEmail)
                <p>{{ $responsibleEmail }}</p>
            @endif
        </div>
        @endif
    </div>
    <div class="footer">
        <p>@multiline($footerText)</p>
    </div>
</body>
</html>
