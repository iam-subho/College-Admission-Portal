<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <title>Application — {{ $application->application_number }}</title>
    <style>
        @page { margin: 14mm 12mm; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 10pt; color: #1a2c4e; line-height: 1.35; }
        h1, h2, h3 { font-family: "Times New Roman", serif; color: #7a1e1e; margin: 0; }

        /* Header */
        .header { border-bottom: 3px solid #7a1e1e; padding-bottom: 8px; margin-bottom: 10px; }
        .crest { width: 46px; height: 46px; border-radius: 50%; background: #7a1e1e; color: white;
                 text-align: center; line-height: 1.1; padding-top: 8px; float: left;
                 font-size: 7pt; font-weight: bold; }
        .college { margin-left: 56px; }
        .college h1 { font-size: 14pt; }
        .college .meta { font-size: 8.5pt; color: #555; margin-top: 2px; }
        .badge-status { float: right; margin-top: 4px; padding: 3px 8px; font-size: 9pt;
                        border-radius: 3px; font-weight: bold; }
        .status-submitted { background: #DBEAFE; color: #1E40AF; }
        .status-verified  { background: #D1FAE5; color: #065F46; }
        .status-rejected  { background: #FEE2E2; color: #991B1B; }
        .status-draft     { background: #F3F4F6; color: #374151; }
        .status-withdrawn { background: #FEF3C7; color: #92400E; }
        .status-awaiting  { background: #FEF3C7; color: #92400E; }

        .doc-title { background: #fdf8ec; border: 1px solid #e87722; padding: 6px 12px;
                     margin: 10px 0 12px 0; text-align: center; font-size: 12pt;
                     font-weight: bold; color: #7a1e1e; letter-spacing: 0.8pt; }

        /* Identity strip */
        .identity { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
        .identity td { padding: 4px 8px; vertical-align: top; border: 1px solid #ddd; }
        .identity td.label { font-size: 7.5pt; color: #555; text-transform: uppercase;
                              letter-spacing: 0.4pt; background: #fafafa; width: 22%; }
        .identity td.value { font-weight: bold; font-size: 10pt; }

        /* Section heading */
        .section { margin-top: 10px; margin-bottom: 8px; }
        .section h2 { background: #f5f5f5; padding: 4px 8px; font-size: 10.5pt;
                      border-left: 3px solid #e87722; color: #7a1e1e; }

        /* Key-value list (2-column layout via floats) */
        .kv2 { width: 100%; }
        .kv2 td { padding: 3px 8px; font-size: 9.5pt; vertical-align: top; width: 50%; }
        .kv2 td .label { color: #555; font-size: 8pt; text-transform: uppercase;
                         letter-spacing: 0.3pt; display: block; margin-bottom: 1px; }

        /* Data table */
        table.data { width: 100%; border-collapse: collapse; margin-bottom: 6px; }
        table.data th { background: #f0f0f0; color: #555; font-size: 7.5pt;
                        text-transform: uppercase; letter-spacing: 0.4pt;
                        padding: 4px 6px; border: 1px solid #ddd; text-align: left; }
        table.data td { padding: 4px 6px; border: 1px solid #ddd; font-size: 9pt; }
        table.data .num { text-align: right; font-family: monospace; }
        table.data .agg { background: #fdf8ec; font-weight: bold; }

        .record-header { background: #fdf8ec; padding: 4px 8px; border: 1px solid #e87722;
                          border-bottom: none; font-weight: bold; color: #7a1e1e;
                          margin-top: 8px; font-size: 10pt; }
        .record-header .meta { font-weight: normal; color: #555; font-size: 9pt; margin-left: 4px; }

        /* Declarations */
        .decl { font-size: 9.5pt; padding: 5px 0; }
        .decl .tick { font-family: monospace; font-weight: bold; font-size: 11pt; }
        .decl .yes { color: #065F46; }
        .decl .no { color: #991B1B; }

        /* Signature block */
        .sig-row { margin-top: 14px; }
        .sig-row table { width: 100%; border-collapse: collapse; }
        .sig-row td { width: 50%; padding-top: 30px; }
        .sig-row .sig-line { border-top: 1px solid #888; padding-top: 3px; font-size: 8.5pt; color: #555; }

        .footer { margin-top: 10px; padding-top: 6px; border-top: 1px solid #ccc;
                  font-size: 7pt; color: #999; text-align: center; }

        .small { font-size: 8pt; color: #666; }
        .mono { font-family: monospace; }
        .verdict-pass { color: #065F46; font-weight: bold; }
        .verdict-fail { color: #991B1B; font-weight: bold; }
    </style>
</head>
<body>

@php
    $isSubmitted = $application->status === 'submitted';
    $awaitingPayment = $isSubmitted && $application->payment_status === 'pending';
    $statusClass = $awaitingPayment ? 'status-awaiting' : 'status-'.$application->status;
    $statusLabel = $awaitingPayment ? 'Awaiting Payment' : ucfirst($application->status);
    $studentName = $student->aadhaar_full_name ?? $user?->name ?? '—';
@endphp

<div class="header">
    <div class="crest">SVNC<br/>1956</div>
    <div class="college">
        <h1>Sardar Vallabhbhai National College</h1>
        <div class="meta">Anand, Gujarat · NAAC A+ · UGC 2(f) &amp; 12(B)<br/>
            Online Admissions Portal · Session {{ $session?->code ?? '—' }}</div>
    </div>
    <span class="badge-status {{ $statusClass }}">{{ $statusLabel }}</span>
    <div style="clear: both;"></div>
</div>

<div class="doc-title">APPLICATION ACKNOWLEDGEMENT RECEIPT</div>

<table class="identity">
    <tr>
        <td class="label">Application No.</td>
        <td class="value mono">{{ $application->application_number ?? 'DRAFT' }}</td>
        <td class="label">Submitted On</td>
        <td class="value">{{ $application->submitted_at ? \Carbon\Carbon::parse($application->submitted_at)->format('d M Y, h:i A') : '—' }}</td>
    </tr>
    <tr>
        <td class="label">Programme</td>
        <td class="value">{{ $program?->code }} — {{ $program?->name }}</td>
        <td class="label">Type</td>
        <td class="value">{{ $program?->type }}</td>
    </tr>
    <tr>
        <td class="label">Eligibility Verdict</td>
        <td class="value">
            @php
                $v = $application->eligibility_verdict ?? 'pending';
                $cls = in_array($v, ['pass', 'override_pass']) ? 'verdict-pass'
                      : (in_array($v, ['fail', 'override_fail']) ? 'verdict-fail' : '');
            @endphp
            <span class="{{ $cls }}">{{ strtoupper(str_replace('_', ' ', $v)) }}</span>
        </td>
        <td class="label">Payment</td>
        <td class="value">{{ strtoupper(str_replace('_', ' ', $application->payment_status ?? 'pending')) }}</td>
    </tr>
</table>

<!-- 1. Applicant -->
<div class="section">
    <h2>1. Applicant Details</h2>
    <table class="kv2">
        <tr>
            <td><span class="label">Full Name</span>{{ $studentName }}</td>
            <td><span class="label">Date of Birth</span>{{ $student->dob ? \Carbon\Carbon::parse($student->dob)->format('d M Y') : '—' }}</td>
        </tr>
        <tr>
            <td><span class="label">Gender</span>{{ $student->gender ?? '—' }}</td>
            <td><span class="label">Nationality</span>{{ $student->nationality ?? '—' }}</td>
        </tr>
        <tr>
            <td><span class="label">Category</span>{{ $student->category?->code ?? '—' }} ({{ $student->category?->name ?? 'Unreserved' }})</td>
            <td><span class="label">Religion</span>{{ $student->religion ?? '—' }}</td>
        </tr>
        <tr>
            <td><span class="label">Aadhaar</span><span class="mono">•••• •••• {{ $student->aadhaar_last4 ?? '—' }}</span></td>
            <td><span class="label">ABC ID</span><span class="mono">{{ $student->abc_id ?? '—' }}</span></td>
        </tr>
        <tr>
            <td><span class="label">Email</span>{{ $user?->email ?? '—' }}</td>
            <td><span class="label">Mobile</span><span class="mono">{{ $user?->mobile ?? '—' }}</span></td>
        </tr>
        <tr>
            <td><span class="label">Mother Tongue</span>{{ $student->mother_tongue ?? '—' }}</td>
            <td><span class="label">Domicile State</span>{{ $student->domicile_state ?? '—' }}</td>
        </tr>
    </table>
</div>

<!-- 2. Family & Address -->
<div class="section">
    <h2>2. Family &amp; Address</h2>
    <table class="kv2">
        <tr>
            <td><span class="label">Father's Name</span>{{ $student->father_name ?? '—' }}</td>
            <td><span class="label">Father's Occupation</span>{{ $student->father_occupation ?? '—' }}</td>
        </tr>
        <tr>
            <td><span class="label">Mother's Name</span>{{ $student->mother_name ?? '—' }}</td>
            <td><span class="label">Mother's Occupation</span>{{ $student->mother_occupation ?? '—' }}</td>
        </tr>
        <tr>
            <td><span class="label">Annual Family Income</span>
                @if($student->annual_family_income)
                    ₹ {{ number_format((float) $student->annual_family_income, 0, '.', ',') }}
                @else — @endif
            </td>
            <td><span class="label">Emergency Contact</span><span class="mono">{{ $student->emergency_contact ?? '—' }}</span></td>
        </tr>
        <tr>
            <td colspan="2"><span class="label">Permanent Address</span>
                {{ collect([$student->house_no, $student->locality, $student->taluka, $student->district, $student->pincode, $student->state, $student->country])->filter()->implode(', ') ?: '—' }}
            </td>
        </tr>
    </table>
</div>

<!-- 3. Programme + Subject Combination -->
<div class="section">
    <h2>3. Subject Combination (NEP 2020)</h2>
    <table class="data">
        <thead>
            <tr>
                <th style="width: 28%;">Category</th>
                <th>Course(s)</th>
                <th class="num" style="width: 12%;">Credits</th>
            </tr>
        </thead>
        <tbody>
            @foreach($categories as $catKey => $catLabel)
                <tr>
                    <td><strong>{{ $catLabel }}</strong></td>
                    <td>
                        @php $rows = $picks[$catKey] ?? []; @endphp
                        @if(empty($rows))
                            <span class="small">— not selected —</span>
                        @else
                            @foreach($rows as $r)
                                <div>{{ $r['name'] }}
                                    @if(!empty($r['code']))<span class="small mono">({{ $r['code'] }})</span>@endif
                                </div>
                            @endforeach
                        @endif
                    </td>
                    <td class="num">
                        @php $totalCredits = collect($rows)->sum(fn ($r) => (int) ($r['credits'] ?? 0)); @endphp
                        {{ $totalCredits > 0 ? $totalCredits : '—' }}
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>

<!-- 4. Academic Records -->
<div class="section">
    <h2>4. Academic Records</h2>

    @if($academic_records->isEmpty())
        <p class="small" style="font-style: italic; text-align: center; padding: 8px 0;">No academic records on file.</p>
    @endif

    @foreach($academic_records as $r)
        <div class="record-header">
            {{ strtoupper($r->level) }} · {{ $r->board }}
            <span class="meta">({{ $r->passing_year ?? '—' }})</span>
            @if($r->stream)<span class="meta">· {{ $r->stream }}</span>@endif
            @if($r->school_name)<span class="meta">· {{ $r->school_name }}</span>@endif
            @if($r->roll_number)<span class="meta">· Roll {{ $r->roll_number }}</span>@endif
            <span style="float: right;">
                @if($r->full_marks){{ $r->obtained_marks }} / {{ $r->full_marks }}@endif
                @if($r->percentage) · {{ number_format((float) $r->percentage, 2) }}%@endif
                @if($r->cgpa) · CGPA {{ $r->cgpa }}@endif
            </span>
        </div>

        @if(is_array($r->subjects) && count($r->subjects))
            <table class="data" style="border-top: none;">
                <thead>
                    <tr>
                        <th>Subject</th>
                        <th style="width: 10%;">Code</th>
                        <th class="num" style="width: 10%;">Theory</th>
                        <th class="num" style="width: 10%;">Practical</th>
                        <th class="num" style="width: 12%;">Obtained</th>
                        <th class="num" style="width: 12%;">Full Marks</th>
                        <th class="num" style="width: 8%;">%</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($r->subjects as $sub)
                        <tr>
                            <td>{{ $sub['name'] ?? '—' }}</td>
                            <td class="mono small">{{ $sub['code'] ?? '—' }}</td>
                            <td class="num">{{ $sub['theory'] ?? '—' }}</td>
                            <td class="num">{{ $sub['practical'] ?? '—' }}</td>
                            <td class="num"><strong>{{ $sub['obtained_marks'] ?? '—' }}</strong></td>
                            <td class="num">{{ $sub['full_marks'] ?? '—' }}</td>
                            <td class="num">{{ isset($sub['percentage']) ? number_format((float) $sub['percentage'], 2) : '—' }}</td>
                        </tr>
                    @endforeach
                    @if($r->full_marks)
                        <tr class="agg">
                            <td colspan="4" style="text-align: right; font-size: 8pt; text-transform: uppercase;">Aggregate</td>
                            <td class="num">{{ $r->obtained_marks }}</td>
                            <td class="num">{{ $r->full_marks }}</td>
                            <td class="num">{{ number_format((float) $r->percentage, 2) }}</td>
                        </tr>
                    @endif
                </tbody>
            </table>
        @else
            <div style="border: 1px solid #ddd; border-top: none; padding: 6px 8px;
                        font-size: 9pt; color: #666; font-style: italic;">
                Aggregate-only record. No subject-wise breakdown.
            </div>
        @endif
    @endforeach
</div>

<!-- 5. Payment -->
@if($paid_order)
<div class="section">
    <h2>5. Payment Receipt</h2>
    <table class="kv2">
        <tr>
            <td><span class="label">Order Number</span><span class="mono">{{ $paid_order->order_number }}</span></td>
            <td><span class="label">Paid On</span>{{ $paid_order->paid_at ? \Carbon\Carbon::parse($paid_order->paid_at)->format('d M Y, h:i A') : '—' }}</td>
        </tr>
        <tr>
            <td><span class="label">Fee Amount</span>₹ {{ number_format((float) $paid_order->amount, 2) }}</td>
            <td><span class="label">Total Paid</span><strong>₹ {{ number_format((float) $paid_order->total, 2) }}</strong></td>
        </tr>
    </table>
</div>
@endif

<!-- 6. Declarations -->
<div class="section">
    <h2>{{ $paid_order ? '6' : '5' }}. Declarations</h2>
    <div class="decl">
        <span class="tick {{ $application->declaration_anti_ragging ? 'yes' : 'no' }}">
            {{ $application->declaration_anti_ragging ? '✓' : '✗' }}
        </span>
        I have read the UGC anti-ragging regulations and undertake to abide by them.
    </div>
    <div class="decl">
        <span class="tick {{ $application->declaration_information_true ? 'yes' : 'no' }}">
            {{ $application->declaration_information_true ? '✓' : '✗' }}
        </span>
        I confirm that all information provided in this application is true and correct.
    </div>
</div>

@if($application->special_request)
<div class="section">
    <h2>{{ $paid_order ? '7' : '6' }}. Special Request</h2>
    <p style="font-style: italic; padding: 4px 8px; background: #fafafa; border-left: 3px solid #e87722;">
        "{{ $application->special_request }}"
    </p>
</div>
@endif

@if(!empty($application->eligibility_reasons))
<div class="section">
    <h2>Eligibility Notes</h2>
    <ul style="margin: 4px 0 4px 20px; font-size: 9pt; color: #991B1B;">
        @foreach($application->eligibility_reasons as $reason)
            <li>{{ $reason }}</li>
        @endforeach
    </ul>
    <p class="small" style="font-style: italic;">
        Verdict is informational. Final eligibility is confirmed by the admissions committee during document verification.
    </p>
</div>
@endif

<!-- Signature block -->
<div class="sig-row">
    <table>
        <tr>
            <td>
                <div class="sig-line">Applicant's Signature</div>
                <div class="small" style="margin-top: 1px;">
                    {{ $application->submitted_at ? \Carbon\Carbon::parse($application->submitted_at)->format('d M Y') : '' }}
                </div>
            </td>
            <td style="text-align: right;">
                <div class="sig-line">Controller of Admissions</div>
                <div class="small" style="margin-top: 1px;">For office use only</div>
            </td>
        </tr>
    </table>
</div>

<div class="footer">
    System-generated acknowledgement · Issued {{ now()->format('d M Y, h:i A') }} ·
    No physical signature required from the applicant for online verification.
</div>

</body>
</html>
