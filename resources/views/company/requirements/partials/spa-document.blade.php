<div class="spa-document" style="font-family: Georgia, 'Times New Roman', serif; font-size: 14px; line-height: 1.72; color: #111827;">
    @php
        $line = '______________________________';
        $lineShort = '________';
        $spanStyle = 'display:inline-block; border-bottom:1px solid #111; text-align:center; line-height:1.15; padding:0 4px 1px 4px; vertical-align:baseline;';
        $fieldSpan = static function ($value, $field, $width = '180px') use ($spanStyle) {
            $text = trim((string) $value);
            return '<span data-field="' . e($field) . '" style="min-width:' . $width . ';' . $spanStyle . '">' . e($text) . '</span>';
        };
    @endphp
    <div style="text-align: center; font-size: 24px; font-weight: 700; text-transform: uppercase; text-decoration: underline; letter-spacing: 0.03em;">SPECIAL POWER OF ATTORNEY</div>

    <div style="margin-top: 36px; text-align: center; font-size: 15px; font-weight: 700; text-transform: uppercase;">KNOW ALL MEN BY THESE PRESENTS:</div>

    <div style="margin-top: 36px; text-align: justify;">
        <p style="margin: 0 0 18px;">
            I,{!! $fieldSpan($doc['principal_name'], 'principal_name', '170px') !!},
            {!! $fieldSpan($doc['principal_nationality'] ?: 'Filipino', 'principal_nationality', '90px') !!},
            of legal age,
            {!! $fieldSpan($doc['principal_civil_status'] ?: 'Married/single', 'principal_civil_status', '110px') !!}
            and resident of
            {!! $fieldSpan($doc['principal_address'], 'principal_address', '290px') !!}
            do hereby name, constitute and appoint
            {!! $fieldSpan($doc['attorney_name'], 'attorney_name', '140px') !!},
            of legal age,
            {!! $fieldSpan($doc['attorney_nationality'] ?: 'Filipino', 'attorney_nationality', '90px') !!}
            and resident of
            {!! $fieldSpan($doc['attorney_address'], 'attorney_address', '250px') !!}.
            (whose signature appears herein below and which signature I now hereby attest to be true, genuine and authentic).
            To be my true and lawful Attorney-in-fact for me and in my name, place and stead, to do and perform the following special powers, to wit:
        </p>

        <ul style="margin: 0 0 18px 28px; padding-left: 10px; list-style-type: disc;">
            <li style="margin-bottom: 8px;">To negotiate, sign, execute, and deliver contracts, engagement agreements, memoranda, and all related documents in connection with my engagement with John Kelly &amp; Company (JK&amp;C Inc.);</li>
            <li style="margin-bottom: 8px;">To submit, receive, process, and manage all documents, records, applications, and communications required in relation to such engagement;</li>
            <li style="margin-bottom: 8px;">To act as my sole and exclusive representative in all dealings, coordination, and communications with John Kelly &amp; Company (JK&amp;C Inc.);</li>
            <li style="margin-bottom: 8px;">To receive copies of documents, reports, notices, and deliverables, whether physical or electronic, on my behalf;</li>
            <li style="margin-bottom: 8px;">To approve, acknowledge, and accept services rendered, outputs, and deliverables;</li>
            <li style="margin-bottom: 8px;">To sign letters, certifications, authorizations, affidavits, and other documents necessary for processing and compliance;</li>
            <li style="margin-bottom: 8px;">To represent me before government agencies, financial institutions, and third parties, including but not limited to the SEC, BIR, LGUs, and banks, in relation to services facilitated by John Kelly &amp; Company (JK&amp;C Inc.);</li>
            <li style="margin-bottom: 8px;">To perform any and all acts necessary or incidental to carry out the foregoing authority.</li>
        </ul>

        <p style="margin: 0 0 18px;">
            I hereby expressly declare that the above-named Attorney-in-Fact is my sole and exclusive authorized representative in all dealings with John Kelly &amp; Company (JK&amp;C Inc.), and that said firm is authorized to rely solely on the acts, instructions, and representations of my Attorney-in-Fact, without obligation to recognize any other person unless I issue a written revocation.
        </p>

        <p style="margin: 0 0 12px; text-align: justify;">
            <span style="font-weight: 700;">HEREBY GIVING AND GRANTING</span>
            unto my said Attorney-in-Fact full power and authority to do and perform any and every acts and thing whatsoever requisite, necessary or proper to be done in and about the premises, as I might or could do if personally and acting in person and
        </p>

        <p style="margin: 0 0 18px; text-align: justify;">
            <span style="font-weight: 700;">HEREBY RATIFYING AND CONFIRMING</span>
            all that my said Attorney-in-Fact shall lawfully do and cause to be done under and by virtue of these presents.
            <span style="font-weight: 700;">IN WITNESS WHEREOF,</span>
            I have hereunto set my hands at
            {!! $fieldSpan($doc['signed_place'], 'signed_place', '170px') !!}
            Philippines on this
            {!! $fieldSpan($doc['signed_day'], 'signed_day', '70px') !!}
            day of
            {!! $fieldSpan($doc['signed_month'], 'signed_month', '90px') !!}.
        </p>
    </div>

    <div class="spa-signature-section" style="margin-top: 54px;">
        <div style="width: 260px; margin: 0 auto; text-align: center;">
            <div style="text-align: center;">____________________________</div>
            <div style="text-align: center;">Guarantor</div>
            <div style="text-align: center; font-weight: 700;">ID No. {!! $fieldSpan($doc['principal_id_no'], 'principal_id_no', '120px') !!}</div>
        </div>

        <div style="height: 28px;"></div>

        <div style="width: 260px; margin: 0 auto; text-align: center;">With Conformity:</div>
        <div style="height: 18px;"></div>
        <div style="width: 260px; margin: 0 auto; text-align: center;">
            <div style="text-align: center;">____________________</div>
            <div style="text-align: center;">Attorney-in-fact</div>
            <div style="text-align: center; font-weight: 700;">ID No. {!! $fieldSpan($doc['attorney_id_no'], 'attorney_id_no', '120px') !!}</div>
        </div>

        <div style="height: 28px;"></div>

        <div style="text-align: center; text-transform: uppercase;">SIGNED IN THE PRESENCE OF</div>
        <div style="height: 18px;"></div>
        <div style="display: flex; justify-content: space-between; gap: 48px;">
            <div style="flex: 1;">_________________________</div>
            <div style="flex: 1; text-align: right;">___________________</div>
        </div>
    </div>

    <div class="spa-acknowledgement-section" style="margin-top: 56px; padding-top: 24px; font-size: 13px;">
        <p style="margin: 0 0 2px; text-transform: uppercase;">REPUBLIC OF THE PHILIPPINES)</p>
        <p style="margin: 0 0 26px; text-align: right;">) S.S.</p>

        <div style="margin-bottom: 16px; text-align: center; font-size: 18px; font-weight: 700; text-transform: uppercase;">ACKNOWLEDGEMENT</div>

        <p style="margin: 0 0 12px; text-align: justify; text-transform: uppercase;">
            BEFORE ME, a Notary Public for and in the {{ $line }}, Philippines, this {{ $lineShort }} day of {{ $lineShort }}, 202__, personally appeared the above-named person/s, who has satisfactorily proven to me his/her/their identity through his/her/their identifying documents written below his/her/their name and signature, that they are the same person/s who executed and voluntarily signed the foregoing Special Power of Attorney, duly signed by his/her/their instrumental witnesses at the spaces herein provided which he/she/they acknowledged to me as his/her/their free and voluntary act and deed. The foregoing instrument relates to a Special Power of Attorney consisting of ___ pages including the page on which this Acknowledgement is written, has been signed on the left margin of each and every page by the parties and the witnesses. WITNESS
        </p>

        <p style="margin: 0 0 26px; text-align: justify; text-transform: uppercase;">
            MY HAND AND NOTARIAL SEAL, this {{ $lineShort }} day of {{ $lineShort }}, 2024, at {{ $line }}.
        </p>

        <div style="margin: 24px 0 24px; text-align: right; font-weight: 700; text-transform: uppercase;">NOTARY PUBLIC</div>

        <div style="font-size: 13px; line-height: 1.7;">
            <div>Doc No. {{ $lineShort }};</div>
            <div>Page No.{{ $lineShort }};</div>
            <div>Book No.{{ $lineShort }};</div>
            <div>Series of 202__.</div>
        </div>
    </div>
</div>
