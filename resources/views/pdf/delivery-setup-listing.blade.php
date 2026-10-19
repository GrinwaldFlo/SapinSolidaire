<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Listing mise en place</title>
    <style>
        @page {
            margin: 10mm;
        }
        body {
            font-family: "DejaVu Sans", Arial, sans-serif;
            margin: 0;
            padding: 0;
            font-size: 16pt;
            color: #111;
        }
        .page {
            height: 277mm;
            display: flex;
            flex-direction: column;
        }
        .range {
            font-size: 120pt;
            font-weight: 800;
            line-height: 1.1;
            margin: 0 0 8mm 0;
            text-align: center;
        }
        .rows {
            flex: 1;
            display: flex;
            flex-direction: column;
        }
        .row {
            flex: 1;
            height: 20mm;
            display: flex;
            align-items: center;
            border-bottom: 1px solid #ddd;
            font-size: 30pt;
            text-align: left;
        }
        .row:first-child {
            border-top: 1px solid #ddd;
        }
        .line {
            width: 100%;
        }
        .line {
            white-space: nowrap;
        }
        .objects {
            font-size: 18pt;
        }
        .objects-md {
            font-size: 14pt;
        }
        .objects-sm {
            font-size: 11pt;
        }
        .objects-xs {
            font-size: 9pt;
        }
        .objects-xxs {
            font-size: 7pt;
        }
        .page-break {
            page-break-after: always;
        }
    </style>
</head>
<body>
    @forelse($pages as $pageIndex => $page)
        <section class="page {{ $pageIndex < $pages->count() - 1 ? 'page-break' : '' }}">
            <h1 class="range">{{ $page['first_code'] }} - {{ $page['last_code'] }}</h1>

            <div class="rows">
                @for($index = 0; $index < 10; $index++)
                    @php
                        $row = $page['rows']->get($index);
                    @endphp
                    <div class="row">
                        <div class="line">
                            @if($row)
                                # {{ $row['family_code'] }} : {{ $row['children_count'] }}
                                @if($row['large_objects']->isNotEmpty())
                                    @php
                                        $objectsText = $row['large_objects']->pluck('display')->join(' · ');
                                        $objectsLength = mb_strlen($objectsText);
                                        $objectsClass = match (true) {
                                            $objectsLength <= 35 => 'objects',
                                            $objectsLength <= 45 => 'objects-md',
                                            $objectsLength <= 58 => 'objects-sm',
                                            $objectsLength <= 75 => 'objects-xs',
                                            default => 'objects-xxs',
                                        };
                                    @endphp
                                    <span class="{{ $objectsClass }}">{{ $objectsText }}</span>
                                @endif
                            @endif
                        </div>
                    </div>
                @endfor
            </div>
        </section>
    @empty
        <section class="page">
            <h1 class="range">Aucune donnée</h1>
            <div class="rows">
                <div class="row">
                    <div class="line">Aucune famille éligible pour cette saison.</div>
                </div>
            </div>
        </section>
    @endforelse
</body>
</html>
