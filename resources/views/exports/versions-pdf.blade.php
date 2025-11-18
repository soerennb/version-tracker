<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #1f2937; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th, td { border: 1px solid #d1d5db; padding: 8px; text-align: left; }
        th { background-color: #f3f4f6; }
        h1 { font-size: 20px; margin-bottom: 10px; }
        .meta { font-size: 12px; color: #4b5563; margin-bottom: 20px; }
    </style>
</head>
<body>
    <h1>{{ __('Versions Export') }}</h1>
    <div class="meta">
        {{ __('Generated at: :date', ['date' => $generated_at->format('d.m.Y H:i')]) }}
        <br>
        {{ __('Total versions: :count', ['count' => $versions->count()]) }}
    </div>

    <table>
        <thead>
            <tr>
                <th>{{ __('Software') }}</th>
                <th>{{ __('Version') }}</th>
                <th>{{ __('Release Date') }}</th>
                <th>{{ __('Status') }}</th>
                <th>{{ __('Approval Status') }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($versions as $version)
                <tr>
                    <td>{{ $version->software->name ?? 'n/a' }}</td>
                    <td>{{ $version->version_number }}</td>
                    <td>{{ optional($version->release_date)->format('d.m.Y') }}</td>
                    <td>{{ $version->status?->value }}</td>
                    <td>{{ $version->approval_status?->value }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
