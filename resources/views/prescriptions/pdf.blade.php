<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Ordonnance Médicale</title>
    <style>
        body {
            font-family: 'Times New Roman', Times, serif; /* Often preferred for official documents */
            line-height: 1.6;
            margin: 40px; /* Slightly larger margins for better print appearance */
            color: #333; /* Darker text for readability */
            font-size: 11pt; /* Standard professional font size */
        }
        .header-image {
            width: 100%;
            max-height: 150px; /* Slightly more room for the header */
            margin-bottom: 30px;
            object-fit: contain;
            display: block; /* Ensures it takes up full width */
            margin-left: auto;
            margin-right: auto;
        }
        .header {
            text-align: right;
            margin-bottom: 40px;
            font-size: 10pt;
            color: #555;
        }
        .subject {
            font-weight: bold;
            font-size: 22px; /* Prominent title */
            margin: 35px 0;
            text-transform: uppercase;
            text-align: center; /* Center the title */
            color: #1a4e95; /* A professional blue */
            border-bottom: 3px solid #1a4e95; /* Thicker, matching border */
            padding-bottom: 12px;
            letter-spacing: 1px; /* Spacing for emphasis */
        }
        .patient-info {
            margin: 25px 0;
            padding: 20px;
            background-color: #eaf3ff; /* Lighter blue background */
            border-left: 5px solid #1a4e95; /* Thicker, matching border */
            border-radius: 5px; /* Slightly rounded corners */
            font-size: 11pt;
        }
        .patient-info strong {
            color: #1a4e95;
        }
        .medications {
            margin: 30px 0;
        }
        .medication-item {
            margin: 20px 0; /* More spacing between medications */
            padding: 15px 0;
            border-bottom: 1px dashed #ccc; /* Dashed line for subtle separation */
        }
        .medication-item:last-child {
            border-bottom: none; /* No border for the last item */
        }
        .medication-item strong {
            font-size: 13pt; /* Larger font for active substance */
            color: #000;
            display: block; /* Each main medication on its own line */
            margin-bottom: 5px;
        }
        .medication-item div {
            margin-left: 15px; /* Indent dosage details */
            font-size: 10.5pt;
            color: #444;
        }
        .medication-item .brand-name {
            font-style: italic;
            color: #666;
            font-size: 10pt;
        }
        .doctor-info {
            margin-top: 60px; /* More space before signature */
            text-align: right;
            font-size: 11pt;
        }
        .doctor-info p {
            margin: 5px 0;
        }
        .signature-line {
            width: 250px; /* Line for physical signature */
            border-bottom: 1px solid #000;
            margin-left: auto; /* Align to the right */
            margin-top: 30px;
            padding-bottom: 5px;
        }
    </style>
</head>
<body>
    <div>
        {{-- Use public_path or ensure the Base64 image is correctly rendered by dompdf --}}
        {{-- If the image is in public/images/ENTETE.png, pass public_path('images/ENTETE.png') --}}
        {{-- and in your controller, make sure 'enable_remote' => true in dompdf config --}}
        {{-- If you're using Base64, ensure it's `data:image/png;base64,...` --}}
            <img src="{{ storage_path('app/public/ENTETE.png') }}" class="header-image" alt="En-tête">
    </div>

    <div class="header">
        Date: {{ $current_date }}
    </div>

    <div class="patient-info">
        <strong>Patient:</strong> {{ $patient_first_name }} {{ $patient_last_name }}<br>
        @if($prescription->patient_age)
            <strong>Âge:</strong> {{ $prescription->patient_age }} ans<br>
        @endif
        @if($prescription->patient_weight)
            <strong>Poids:</strong> {{ $prescription->patient_weight }} kg
        @endif
    </div>

    <div class="subject">Ordonnance Médicale</div>

    <div class="medications">
        @forelse($medications as $medication)
            <div class="medication-item">
                <strong>{{ $medication->cd_active_substance }}</strong>
                @if($medication->brand_name)
                    <span class="brand-name">({{ $medication->brand_name }})</span><br>
                @endif
                <div>Forme: {{ $medication->pharmaceutical_form }}</div>
                <div>Posologie: {{ $medication->dose_per_intake }} - {{ $medication->num_intakes_per_day }}</div>
                <div>Durée/Quantité: {{ $medication->duration_or_boxes }}</div>
            </div>
        @empty
            <p style="text-align: center; color: #777;">Aucun médicament n'a été prescrit pour cette ordonnance.</p>
        @endforelse
    </div>

    <div class="doctor-info">
        <p>Signature du Médecin:</p>
        <div class="signature-line"></div>
        <p>Yahia Cherif</p>
    </div>
</body>
</html>