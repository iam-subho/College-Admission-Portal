<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <title>Admit Card — {{ $candidate->roll_number }}</title>
    <style>
        @page { margin: 18mm 15mm; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 11pt; color: #1a2c4e; }
        h1, h2, h3 { font-family: "Times New Roman", serif; color: #7a1e1e; margin: 0; }
        .header { border-bottom: 3px solid #7a1e1e; padding-bottom: 8px; margin-bottom: 14px; }
        .crest { width: 50px; height: 50px; border-radius: 50%; background: #7a1e1e; color: white;
                 text-align: center; line-height: 1.1; padding-top: 9px; float: left; font-size: 8pt; font-weight: bold; }
        .college { margin-left: 64px; }
        .college h1 { font-size: 16pt; }
        .college .meta { font-size: 9pt; color: #555; margin-top: 2px; }
        .card-title { background: #fdf8ec; border: 1px solid #e87722; padding: 8px 14px; margin: 14px 0;
                      text-align: center; font-size: 14pt; font-weight: bold; color: #7a1e1e; letter-spacing: 1px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 12px; }
        table.kv td { padding: 5px 8px; font-size: 10pt; vertical-align: top; }
        table.kv td.label { color: #555; width: 28%; font-size: 9pt; text-transform: uppercase; letter-spacing: 0.4pt; }
        table.kv td.value { color: #1a2c4e; font-weight: bold; }
        .section-title { background: #f5f5f5; padding: 5px 10px; font-size: 10pt; font-weight: bold;
                         color: #7a1e1e; border-left: 3px solid #e87722; margin: 10px 0 4px 0; }
        .roll { font-size: 18pt; font-family: monospace; color: #7a1e1e; text-align: right; font-weight: bold; }
        .signature-row { margin-top: 22px; }
        .sig-box { display: inline-block; width: 40%; text-align: center; }
        .sig-line { border-top: 1px solid #888; margin-top: 36px; padding-top: 4px; font-size: 9pt; color: #555; }
        .instructions { background: #fffaf0; border: 1px solid #f0d4a8; padding: 8px 14px; font-size: 9pt; line-height: 1.5; }
        .instructions h3 { font-size: 10pt; margin-bottom: 4px; }
        .footer { margin-top: 14px; padding-top: 8px; border-top: 1px solid #ccc; font-size: 7.5pt; color: #999; text-align: center; }
        .photo-box { float: right; width: 90px; height: 110px; border: 1px dashed #aaa; text-align: center; font-size: 8pt;
                     color: #999; padding-top: 44px; margin-left: 10px; }
    </style>
</head>
<body>

<div class="header">
    <div class="crest">
        SVNC<br/>1956
    </div>
    <div class="college">
        <h1>Sardar Vallabhbhai National College</h1>
        <div class="meta">Anand, Gujarat · NAAC A+ · UGC 2(f) &amp; 12(B)<br/>
            Online Admissions Portal · Session {{ $session?->code ?? '—' }}</div>
    </div>
    <div style="clear: both;"></div>
</div>

<div class="card-title">ADMISSION TEST · ADMIT CARD</div>

<div class="photo-box">[ PHOTO ]</div>

<table class="kv">
    <tr>
        <td class="label">Roll Number</td>
        <td class="value roll">{{ $candidate->roll_number }}</td>
    </tr>
    <tr>
        <td class="label">Application No.</td>
        <td class="value">{{ $application?->application_number }}</td>
    </tr>
    <tr>
        <td class="label">Candidate Name</td>
        <td class="value">{{ $user?->name ?? $student?->aadhaar_full_name }}</td>
    </tr>
    <tr>
        <td class="label">Date of Birth</td>
        <td class="value">{{ $student?->dob ? \Carbon\Carbon::parse($student->dob)->format('d M Y') : '—' }}</td>
    </tr>
    <tr>
        <td class="label">Programme</td>
        <td class="value">{{ $program?->code }} · {{ $program?->name }}</td>
    </tr>
</table>

<div class="section-title">Test Schedule</div>
<table class="kv">
    <tr>
        <td class="label">Date of Examination</td>
        <td class="value">{{ \Carbon\Carbon::parse($schedule->test_date)->format('l, d F Y') }}</td>
    </tr>
    <tr>
        <td class="label">Reporting Time</td>
        <td class="value">{{ $schedule->reporting_time ? \Carbon\Carbon::parse($schedule->reporting_time)->format('h:i A') : '—' }}</td>
    </tr>
    <tr>
        <td class="label">Test Duration</td>
        <td class="value">
            @if($schedule->start_time && $schedule->end_time)
                {{ \Carbon\Carbon::parse($schedule->start_time)->format('h:i A') }}
                to {{ \Carbon\Carbon::parse($schedule->end_time)->format('h:i A') }}
            @else
                —
            @endif
        </td>
    </tr>
    <tr>
        <td class="label">Max Marks</td>
        <td class="value">{{ $config?->max_marks ?? '—' }}
            @if($config?->qualifying_marks)
                <span style="font-weight: normal; color: #555;">(qualifying: {{ $config->qualifying_marks }})</span>
            @endif
        </td>
    </tr>
    <tr>
        <td class="label">Venue</td>
        <td class="value">{{ $schedule->venue }}</td>
    </tr>
    @if($schedule->venue_address)
        <tr>
            <td class="label">Address</td>
            <td class="value" style="font-weight: normal; font-size: 9.5pt;">{{ $schedule->venue_address }}</td>
        </tr>
    @endif
</table>

<div class="section-title">Instructions for Candidates</div>
<div class="instructions">
    @if($config?->instructions)
        {!! nl2br(e($config->instructions)) !!}
    @else
        <ul style="margin: 0; padding-left: 18px;">
            <li>Report at the venue at least 30 minutes before the reporting time.</li>
            <li>Bring this admit card (printed) and a valid photo ID proof (Aadhaar / PAN / Driving License).</li>
            <li>Affix a recent passport-size photograph in the box above (same as the one uploaded in your application).</li>
            <li>Electronic devices (mobile phones, smart watches, calculators except where explicitly permitted) are prohibited inside the hall.</li>
            <li>Candidates without a valid admit card or photo ID will not be allowed to appear.</li>
            <li>Follow all instructions given by the invigilator.</li>
        </ul>
    @endif
</div>

<div class="signature-row">
    <div class="sig-box" style="float: left;">
        <div class="sig-line">Candidate's Signature</div>
    </div>
    <div class="sig-box" style="float: right;">
        <div class="sig-line">Controller of Admissions</div>
    </div>
    <div style="clear: both;"></div>
</div>

<div class="footer">
    System-generated admit card · Issued on {{ now()->format('d M Y, h:i A') }}<br/>
    For queries, contact admissions@svnc.edu.in
</div>

</body>
</html>
