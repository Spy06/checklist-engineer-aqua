<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
</head>
<body>
    <table border="1">
        <thead>
            <tr>
                <th colspan="6" style="text-align: center; font-weight: bold; font-size: 16px;">
                    Data Checklist Export ({{ $startDate }} to {{ $endDate }})
                </th>
            </tr>
            <tr>
                <th>No</th>
                <th>Tanggal</th>
                <th>Nama Form Checklist</th>
                <th>Pemeriksa</th>
                <th>Status</th>
                <th>Catatan / Failures</th>
            </tr>
        </thead>
        <tbody>
            @foreach($submissions as $index => $sub)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $sub['date'] ?? '' }}</td>
                    <td>{{ $forms[$sub['form_id']] ?? 'Unknown Form' }}</td>
                    <td>{{ $sub['user_name'] ?? '' }}</td>
                    
                    @php
                        // To determine if good, check if failures array is empty or check notes
                        $hasFailures = false;
                        if (!empty($sub['notes']) && (str_contains($sub['notes'], '[IMPORTED FAILURES]') || str_contains($sub['notes'], '[API FAILURES]'))) {
                            $hasFailures = true;
                        }
                    @endphp
                    <td>{{ !$hasFailures ? 'Good / Sesuai' : 'Not Good / Tidak Sesuai' }}</td>
                    <td>
                        {{ $sub['notes'] ?? '' }}
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
