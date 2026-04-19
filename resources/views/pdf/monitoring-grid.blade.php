<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Suivi enfants - {{ $seasonName }}</title>
    <style>
        @page {
            margin: 15mm 10mm 20mm 10mm;
        }
        body {
            font-family: "DejaVu Sans", Arial, sans-serif;
            margin: 0;
            padding: 0;
            font-size: 10pt;
        }
        .header {
            margin-bottom: 6mm;
            border-bottom: 2px solid #2d5a27;
            padding-bottom: 3mm;
        }
        .header h1 {
            font-size: 13pt;
            color: #2d5a27;
            margin: 0 0 2mm 0;
        }
        .header .filters {
            font-size: 8pt;
            color: #555;
        }
        .header .filters span {
            margin-right: 6mm;
        }
        .header .filters strong {
            color: #333;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th {
            background-color: #2d5a27;
            color: #ffffff;
            text-align: left;
            padding: 3mm 2mm;
            font-size: 9pt;
        }
        td {
            padding: 2mm;
            border-bottom: 1px solid #ddd;
            vertical-align: top;
            font-size: 9pt;
        }
        tr:nth-child(even) {
            background-color: #f9f9f9;
        }
    </style>
</head>
<body>

    <div class="header">
        <h1>Suivi des enfants — {{ $seasonName }}</h1>
        <div class="filters">
            <span><strong>Statut :</strong> {{ $statusLabel }}</span>
            @if($search)
                <span><strong>Recherche :</strong> {{ $search }}</span>
            @endif
            <span><strong>Nombre d'enfants :</strong> {{ $children->count() }}</span>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th>Code</th>
                <th>Prénom</th>
                <th>Genre</th>
                <th>Âge</th>
                <th>Souhait du cadeau</th>
                <th>Taille et pointure</th>
                <th>Famille</th>
                <th>Statut</th>
            </tr>
        </thead>
        <tbody>
            @foreach($children as $child)
                <tr>
                    <td>{{ $child->code ?? '—' }}</td>
                    <td>{{ $child->anonymous ? '***' : $child->first_name }}</td>
                    <td>{{ $child->gender !== 'unspecified' ? $child->gender_label : '—' }}</td>
                    <td>{{ $child->formatted_age }}</td>
                    <td>{{ $child->gift }}</td>
                    <td>
                        @if($child->height){{ $child->height }} cm @endif
                        @if($child->height && $child->shoe_size)/ @endif
                        @if($child->shoe_size)P. {{ $child->shoe_size }}@endif
                        @if(!$child->height && !$child->shoe_size)—@endif
                    </td>
                    <td>{{ $child->giftRequest->family->last_name }}</td>
                    <td>{{ $child->status_label }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <script type="text/php">
        if (isset($pdf)) {
            $text = "Page {PAGE_NUM} / {PAGE_COUNT}";
            $font = $fontMetrics->get_font("DejaVu Sans", "normal");
            $size = 7;
            $color = [0.4, 0.4, 0.4];
            $width = $pdf->get_width();
            $height = $pdf->get_height();
            $pdf->page_text($width - 65, $height - 12, $text, $font, $size, $color);
        }
    </script>

</body>
</html>
