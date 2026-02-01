<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Étiquettes</title>
    <style>
        @page {
            margin: 10mm;
        }
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
        }
        .labels-grid {
            width: 100%;
        }
        .label-row {
            display: table;
            width: 100%;
            table-layout: fixed;
        }
        .label {
            display: table-cell;
            width: 50%;
            height: 67mm;
            padding: 5mm;
            vertical-align: top;
            border: 1px dashed #ccc;
            box-sizing: border-box;
        }
        .label-content {
            height: 100%;
            position: relative;
        }
        .label-code {
            text-align: center;
            font-size: 28pt;
            font-weight: bold;
            font-family: monospace;
            color: #2d5a27;
            margin-bottom: 3mm;
            letter-spacing: 3mm;
            margin: 0;
        }
        .label-name {
            text-align: center;
            font-size: 16pt;
            font-weight: bold;
            margin: 0 0 2mm 0;
        }
        .label-age {
            text-align: center;
            font-size: 12pt;
            color: #666;
            margin: 0 0 3mm 0;
        }
        .label-gift {
            text-align: center;
            font-size: 14pt;
            background-color: #f5f5f5;
            padding: 2mm;
            border-radius: 2mm;
            margin: 0 0 2mm 0;
        }
        .label-details {
            text-align: center;
            font-size: 10pt;
            color: #666;
            margin: 0;
        }
        .label-detail-item {
            display: inline-block;
            margin: 0 2mm;
        }
    </style>
</head>
<body>
    <div class="labels-grid">
        @foreach($children->chunk(2) as $rowChildren)
            <div class="label-row">
                @foreach($rowChildren as $child)
                    <div class="label">
                        <div class="label-content">
                            <div class="label-code">{{ $child->code }}</div>
                            <div class="label-name">{{ $child->anonymous ? '***' : $child->first_name }}</div>
                            <div class="label-age">{{ $child->age }} ans @if($child->gender !== 'unspecified') ({{ $child->gender_label }}) @endif</div>
                            <div class="label-gift">🎁 {{ $child->gift }}</div>
                            <div class="label-details">
                                @if($child->height)
                                    <span class="label-detail-item">📏 {{ $child->height }} cm</span>
                                @endif
                                @if($child->shoe_size)
                                    <span class="label-detail-item">👟 {{ $child->shoe_size }}</span>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
                @if($rowChildren->count() === 1)
                    <div class="label"></div>
                @endif
            </div>
        @endforeach
    </div>
</body>
</html>
