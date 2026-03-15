<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Cartes - Grille</title>
    <style>
        @page {
            margin: 10mm;
        }
        body {
            font-family: "DejaVu Sans", Arial, sans-serif;
            margin: 0;
            padding: 0;
            font-size: 10pt;
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
    <table>
        <thead>
            <tr>
                <th>Prénom</th>
                <th>Genre</th>
                <th>Âge</th>
                <th>Souhait du cadeau</th>
                <th>Taille et pointure</th>
                <th>Code</th>
            </tr>
        </thead>
        <tbody>
            @foreach($children as $child)
                <tr>
                    <td>{{ $child->anonymous ? '***' : $child->first_name }}</td>
                    <td>{{ $child->gender !== 'unspecified' ? $child->gender_label : '—' }}</td>
                    <td>{{ $child->age }} ans</td>
                    <td>{{ $child->gift }}</td>
                    <td>
                        @if($child->height){{ $child->height }} cm @endif
                        @if($child->height && $child->shoe_size)/ @endif
                        @if($child->shoe_size)P. {{ $child->shoe_size }}@endif
                        @if(!$child->height && !$child->shoe_size)—@endif
                    </td>
                    <td>{{ $child->code ?? '—' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
