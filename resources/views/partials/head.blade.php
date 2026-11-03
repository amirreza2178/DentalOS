<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
@php
    $identity = $clinicIdentity ?? app(\App\Support\ClinicIdentity::class);
    // Public titles lead with the approved doctor name once; avoid stacking the full clinic phrase everywhere.
    $pageTitle = ($title ?? 'DentalOS').' — '.$identity->doctorDisplayName();
@endphp
<title>{{ $pageTitle }}</title>
<meta name="description" content="{{ $identity->introduction() }}">
@if (! empty($autoRefreshSeconds))
    <meta http-equiv="refresh" content="{{ (int) $autoRefreshSeconds }}">
@endif
@vite(['resources/css/app.css', 'resources/js/app.js'])
