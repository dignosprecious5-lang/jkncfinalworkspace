<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Controlled Document - {{ $action->action_code }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f8fafc;
            color: #0f172a;
            margin: 0;
            padding: 30px;
        }

        .controlled-paper {
            max-width: 800px;
            margin: 0 auto;
            background: #ffffff;
            border: 1px solid #cbd5e1;
            padding: 48px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
            position: relative;
        }

        .watermark-banner {
            border: 2px dashed #94a3b8;
            padding: 8px 14px;
            font-size: 11px;
            font-weight: 700;
            color: #475569;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 24px;
            display: flex;
            justify-content: space-between;
        }

        .doc-header {
            border-bottom: 2px solid #1e4f95;
            padding-bottom: 16px;
            margin-bottom: 24px;
        }

        .doc-title {
            font-size: 24px;
            font-weight: 800;
            color: #0f172a;
            margin: 0 0 6px;
        }

        .meta-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
            margin-bottom: 24px;
            font-size: 13px;
        }

        .meta-item strong {
            color: #64748b;
        }

        .content-box {
            font-size: 13.5px;
            line-height: 1.6;
            margin-bottom: 40px;
            padding: 16px;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
        }

        .signature-section {
            margin-top: 60px;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 40px;
        }

        .sig-block {
            border-top: 1.5px solid #0f172a;
            padding-top: 8px;
        }

        .sig-label {
            font-size: 12px;
            color: #64748b;
            font-weight: 600;
        }

        .sig-name {
            font-size: 14px;
            font-weight: 700;
            color: #0f172a;
            margin-top: 2px;
        }

        @media print {
            body { background: transparent; padding: 0; }
            .controlled-paper { box-shadow: none; border: none; padding: 20px; }
            .no-print { display: none; }
        }
    </style>
</head>
<body>

    <div class="no-print" style="max-width:800px; margin:0 auto 16px; text-align:right;">
        <button onclick="window.print()" style="padding:8px 18px; font-weight:700; background:#1e4f95; color:#fff; border:none; border-radius:6px; cursor:pointer;">
            🖨️ Print Controlled Document
        </button>
    </div>

    <div class="controlled-paper">
        <div class="watermark-banner">
            <span>ORDO CONTROLLED CLIENT DOCUMENT</span>
            <span>ACTION REF: {{ $action->action_code }}</span>
        </div>

        <div class="doc-header">
            <h1 class="doc-title">{{ $action->document_title }}</h1>
            <p style="color:#64748b; font-size:13px;">Document Version: <strong>{{ $action->document_version }}</strong> · Type: {{ $action->document_type }}</p>
        </div>

        <div class="meta-grid">
            <div class="meta-item">
                <strong>Client / Company:</strong> {{ $action->client_name }} {{ $action->client_company ? "({$action->client_company})" : '' }}
            </div>
            <div class="meta-item">
                <strong>Action Requested:</strong> {{ $action->action_type }}
            </div>
            <div class="meta-item">
                <strong>Issue Date:</strong> {{ $action->created_at->format('M d, Y') }}
            </div>
            <div class="meta-item">
                <strong>Deal Code:</strong> {{ $action->deal?->deal_code ?: 'N/A' }}
            </div>
        </div>

        <div class="content-box">
            <strong>Instructions:</strong><br>
            {{ $action->instructions ?: "Please physically sign and date this document in the signature section below. Once signed, scan or photograph the signed copy and return it to your ORDO account representative for verification." }}
        </div>

        <div class="signature-section">
            <div>
                <p style="font-size:12px; font-weight:700; color:#64748b; margin-bottom:50px;">FOR CLIENT / SIGNATORY:</p>
                <div class="sig-block">
                    <div class="sig-name">{{ $action->client_name }}</div>
                    <div class="sig-label">Authorized Signatory Name &amp; Title</div>
                    <div class="sig-label" style="margin-top:8px;">Date: ________________________</div>
                </div>
            </div>

            <div>
                <p style="font-size:12px; font-weight:700; color:#64748b; margin-bottom:50px;">FOR ORDO REPRESENTATIVE:</p>
                <div class="sig-block">
                    <div class="sig-name">{{ $action->created_by_name ?: 'ORDO Partner / Lead' }}</div>
                    <div class="sig-label">ORDO Custodian / Lead</div>
                    <div class="sig-label" style="margin-top:8px;">Date: ________________________</div>
                </div>
            </div>
        </div>

        <div style="margin-top:50px; font-size:10px; color:#94a3b8; text-align:center; border-top:1px solid #e2e8f0; padding-top:10px;">
            ORDO Universal Client Action Layer · Security Scoped Document · Action Ref: {{ $action->action_code }}
        </div>
    </div>

</body>
</html>
