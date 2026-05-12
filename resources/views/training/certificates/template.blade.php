{{-- resources/views/training/certificates/template.blade.php --}}

<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Certificate</title>

    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            text-align: center;
            padding: 0;
            margin: 0;
        }

        .certificate {
            width: 100%;
            height: 100%;
            border: 12px solid #c9a646;
            padding: 50px;
            box-sizing: border-box;
            position: relative;
        }

        .inner-border {
            border: 2px solid #c9a646;
            padding: 40px;
            height: 100%;
        }

        .logo {
            width: 80px;
            margin-bottom: 10px;
        }

        h1 {
            font-size: 42px;
            margin-bottom: 10px;
        }

        .subtitle {
            font-size: 18px;
            margin-bottom: 20px;
        }

        .name {
            font-size: 32px;
            font-weight: bold;
            margin: 20px 0;
        }

        .course {
            font-size: 24px;
            margin: 10px 0;
        }

        .footer {
            position: absolute;
            bottom: 50px;
            left: 50px;
            right: 50px;
            display: flex;
            justify-content: space-between;
            font-size: 14px;
        }

        .signature {
            text-align: center;
        }

        .signature-line {
            border-top: 1px solid #000;
            width: 200px;
            margin: 10px auto 5px;
        }

        .cert-number {
            position: absolute;
            bottom: 20px;
            right: 40px;
            font-size: 12px;
            color: gray;
        }
    </style>
</head>

<body>

<div class="certificate">
    <div class="inner-border">

        <!-- LOGO -->
        <img src="{{ public_path('logo.png') }}" class="logo">

        <h1>Certificate of Completion</h1>

        <p class="subtitle">
            This is to certify that
        </p>

        <!-- ✅ NAME -->
        <div class="name">
            {{ $certificate->enrollment->trainee->profile->first_name ?? '' }}
            {{ $certificate->enrollment->trainee->profile->last_name ?? '' }}
        </div>

        <p>has successfully completed the training</p>

        <!-- ✅ COURSE -->
        <div class="course">
            {{ $certificate->enrollment->course->course_name ?? 'Training Course' }}
        </div>

        <p class="subtitle">
            Given this {{ \Carbon\Carbon::parse($certificate->date_issued)->format('jS') }}
            day of {{ \Carbon\Carbon::parse($certificate->date_issued)->format('F Y') }}
        </p>

        <!-- FOOTER -->
        <div class="footer">

            <div class="signature">
                <div class="signature-line"></div>
                <strong>Program Director</strong>
            </div>

            <div class="signature">
                <div class="signature-line"></div>
                <strong>Authorized Signature</strong>
            </div>

        </div>

        <!-- ✅ CERT NUMBER -->
        <div class="cert-number">
            Certificate No: {{ $certificate->certificate_number }}
        </div>

    </div>
</div>

</body>
</html>