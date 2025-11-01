<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Meeting {{ $meeting->id }} - Attendees</title>
    <style>
        /* Page setup for PDF printing */
        @page {
            margin: 20mm 15mm;
        }

        html, body { height: 100%; }
        body { font-family: Arial, Helvetica, sans-serif; color: #111; margin: 0; }

        /* Header repeated on each page */
        .page-header {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            height: 60px;
            padding: 10px 15px;
            border-bottom: 1px solid #e2e2e2;
            background: #fff;
        }

        /* Footer with page numbers */
        .page-footer {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            height: 28px;
            padding: 6px 15px;
            border-top: 1px solid #e2e2e2;
            font-size: 12px;
            color: #444;
            background: #fff;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        /* Counters for page numbers (works with Dompdf and browsers that support CSS counters for printing) */
        .page-number:before { content: counter(page); }
        .page-count:before { content: counter(pages); }

        /* Main content area: give space for header/footer */
        .container { max-width: 800px; margin: 80px auto 50px; padding: 0 5px; }

        h1 { font-size: 18px; margin: 0 0 6px 0; }
        .meta { margin-bottom: 12px; color: #444; }

        table { width: 100%; border-collapse: collapse; margin-top: 8px; font-size: 13px; }
        thead { display: table-header-group; }
        tfoot { display: table-footer-group; }
        th, td { padding: 8px 6px; border: 1px solid #ddd; text-align: left; vertical-align: top; }
        th { background: #f4f4f4; }

        /* Avoid splitting a row across pages. If a single row is too big, it may still split. */
        tr { page-break-inside: avoid; }

        /* Small adjustments so long tables break nicely */
        tbody { page-break-inside: auto; }

        .footer-small { font-size: 11px; color: #666; }

        /* When printing from browser, ensure colors and backgrounds print cleanly */
        @media print {
            .page-header, .page-footer { background: #fff; }
        }
    </style>
</head>
<body>

    <div class="page-header">
        <div style="display:flex; justify-content:space-between; align-items:center;">
            <div>
                <strong>Meeting #{{ $meeting->id }}</strong>
                <div style="font-size:12px; color:#666;">{{ $meeting->info ?? '' }}</div>
            </div>
            <div style="text-align:right; font-size:12px; color:#666;">
                <div>Attendees: {{ $attendees_count }}</div>
                <div>PIN: {{ $meeting->pin ?? '' }}</div>
            </div>
        </div>
    </div>

    <div class="page-footer">
        <div class="footer-small">Generated: {{ now()->toDateTimeString() }}</div>
        <div class="footer-small">Page <span class="page-number"></span> of <span class="page-count"></span></div>
    </div>

    <div class="container">
        <h1>Meeting attendees</h1>
        <div class="meta">
            <div><strong>Day / Time:</strong> {{ $day ?? 'n/a' }}</div>
        </div>

        <table>
            <thead>
                <tr>
                    <th style="width:6%">#</th>
                    <th style="width:62%">Name</th>
                    <th style="width:32%">Number</th>
                </tr>
            </thead>
            <tbody>
                @forelse($attendees as $i => $a)
                    <tr>
                        <td>{{ $i + 1 }}</td>
                        <td>{{ $a['name'] }}</td>
                        <td>{{ $a['number'] }}</td>
                    </tr>
                @empty
                    <tr><td colspan="3" style="text-align:center; color:#666">No attendees</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

</body>
</html>
