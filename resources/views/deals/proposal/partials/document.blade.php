@php
    $d = $documentData;
    $editableMode = (bool) ($editable ?? false);

    $paragraphs = function ($value) {
        return collect(preg_split("/\r\n|\n|\r/", (string) $value) ?: [])
            ->map(fn ($line) => trim($line))
            ->filter()
            ->values();
    };

    $money = fn ($value) => number_format((float) $value, 2);
    $moneyOrConfirm = fn ($value) => is_numeric($value) ? $money($value) : 'Needs confirmation';
    $textEditorClasses = 'proposal-inline-editor proposal-inline-editor-text';
    $blockEditorClasses = 'proposal-inline-editor proposal-inline-editor-block';
    $numberEditorClasses = 'proposal-inline-editor proposal-inline-editor-number';

    $requirements = collect([
        ['label' => 'For Sole Proprietor / Professional / Individual', 'items' => $paragraphs($d['requirements_sole'] ?? '')],
        ['label' => 'For Juridical / Corporation / Partnership', 'items' => $paragraphs($d['requirements_juridical'] ?? '')],
        ['label' => 'Optional / As Applicable', 'items' => $paragraphs($d['requirements_optional'] ?? '')],
    ])->filter(fn ($group) => $group['items']->isNotEmpty())->values();

    $proposalLogo = $logoSrc ?? asset('images/deal-proposal-template-logo.png');

    $toRoman = function (int $number): string {
        $map = [
            1000 => 'M',
            900 => 'CM',
            500 => 'D',
            400 => 'CD',
            100 => 'C',
            90 => 'XC',
            50 => 'L',
            40 => 'XL',
            10 => 'X',
            9 => 'IX',
            5 => 'V',
            4 => 'IV',
            1 => 'I',
        ];

        $result = '';
        foreach ($map as $value => $roman) {
            while ($number >= $value) {
                $result .= $roman;
                $number -= $value;
            }
        }

        return $result;
    };

    $sections = [
        ['title' => 'Executive Summary', 'content' => 'executive_summary'],
        ['title' => 'Our Role and Value to You', 'content' => 'role_and_value'],
        ['title' => 'Why John Kelly & Company is the Right Partner', 'content' => 'why_partner'],
    ];

    $serviceAreaRows = collect($d['service_areas'] ?? [])->values();
    $serviceAreaChunks = collect();
    if ($serviceAreaRows->isEmpty()) {
        $serviceAreaChunks->push(collect());
    } else {
        $serviceAreaChunks->push($serviceAreaRows->take(3)->values());
        $serviceAreaRows->slice(3)->chunk(4)->each(fn ($chunk) => $serviceAreaChunks->push($chunk->values()));
    }

    $productAreaRows = collect($d['product_areas'] ?? [])->values();
    $productAreaChunks = $productAreaRows->isEmpty()
        ? collect([collect()])
        : $productAreaRows->chunk(4)->map(fn ($chunk) => $chunk->values())->values();

    $serviceCatalogTerms = collect($d['product_catalog']['services'] ?? [])->flatMap(fn ($service) => $service['terms'] ?? [])
        ->unique(fn ($term) => strtolower(trim($term['title'])).'|'.trim($term['content']));

    $termsChunks = collect([collect($d['terms_and_conditions'] ?? [])->values()]);
    $totalPages = 13
        + max($serviceAreaChunks->count() - 1, 0)
        + max($productAreaChunks->count() - 1, 0)
        + max($termsChunks->count() - 1, 0);
    $pageNumber = 0;
    $renderPageFooter = function () use ($d) {
        return '
            <div class="proposal-page-footer">
                <div>John Kelly &amp; Company</div>
                <div>'.e($d['company_address'] ?? '').'</div>
                <div>Email: '.e($d['company_email'] ?? '').' &bull; Website: '.e($d['company_website'] ?? '').' &bull; Phone: '.e($d['company_phone'] ?? '').'</div>
            </div>
        ';
    };

    $renderPageNumber = function (int $pageNumber) use ($totalPages) {
        return '<div class="proposal-page-number">Page '.$pageNumber.' of '.$totalPages.'</div>';
    };
@endphp

<div class="proposal-doc">
    <section class="proposal-page proposal-cover">
        <div class="proposal-cover-logo-wrap">
            <img src="{{ $proposalLogo }}" alt="John Kelly & Company" class="proposal-brand-logo">
        </div>

        <div class="proposal-cover-body">
            <div class="proposal-cover-year">{{ $d['year'] ?? now()->format('Y') }}</div>
            <div
                class="proposal-cover-title @if($editableMode) {{ $textEditorClasses }} @endif"
                @if($editableMode) contenteditable="true" data-proposal-field="service_type" data-proposal-editor="text" @endif
            >{{ filled($d['service_type'] ?? null) ? $d['service_type'] : 'Service area/title needs confirmation' }}</div>
            <div class="proposal-cover-date">{{ $d['date'] ?? now()->format('F d, Y') }}</div>

            <div class="proposal-presented-label">Presented For:</div>
            <div class="proposal-presented-name">{{ $d['client_name'] ?? '' }}</div>
            <div class="proposal-presented-name">{{ $d['business_name'] ?? '' }}</div>
            <div
                class="proposal-presented-location @if($editableMode) {{ $textEditorClasses }} @endif"
                @if($editableMode) contenteditable="true" data-proposal-field="location" data-proposal-editor="text" @endif
            >{{ $d['location'] ?? '' }}</div>
        </div>

        <div class="proposal-cover-footer">
            <div class="proposal-contact-strip">
                <div class="proposal-contact-inline">
                    <span>{{ $d['company_phone'] ?? '' }}</span>
                    <span>{{ $d['company_email'] ?? '' }}</span>
                    <span>{{ $d['company_website'] ?? '' }}</span>
                    <span>{{ $d['reference_id'] ?? '' }}</span>
                    <span>Confidential</span>
                </div>
                <div class="proposal-contact-address">{{ $d['company_address'] ?? '' }}</div>
            </div>
        </div>
    </section>

    <section class="proposal-page proposal-inner-page">
        {!! $renderPageNumber(++$pageNumber) !!}
        <div class="proposal-page-body">
        @foreach (collect($sections)->take(2) as $index => $section)
            <h2 class="proposal-section-heading">
                <span class="proposal-section-number">{{ $toRoman($index + 1) }}.</span>
                <span>{{ $section['title'] }}</span>
            </h2>

            @foreach (($d[$section['content']] ?? []) as $item)
                <p class="proposal-paragraph">{!! nl2br(e($item)) !!}</p>
            @endforeach
        @endforeach
        </div>
        {!! $renderPageFooter() !!}
    </section>

    @foreach ($serviceAreaChunks as $serviceChunkIndex => $serviceAreaChunk)
        <section class="proposal-page proposal-inner-page">
            {!! $renderPageNumber(++$pageNumber) !!}
            <div class="proposal-page-body">
            @php($thirdSection = $sections[2])
            <h2 class="proposal-section-heading">
                <span class="proposal-section-number">{{ $toRoman(3) }}.</span>
                <span>{{ $thirdSection['title'] }}</span>
            </h2>

            @if ($serviceChunkIndex === 0)
                @foreach (($d[$thirdSection['content']] ?? []) as $item)
                    <p class="proposal-paragraph">{!! nl2br(e($item)) !!}</p>
                @endforeach
            @endif

            <table class="proposal-service-table">
                <thead>
                    <tr>
                        <th class="proposal-service-no">No.</th>
                        <th class="proposal-service-area">Service Area</th>
                        <th class="proposal-service-scope">Scope of Support</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($serviceAreaChunk as $serviceArea)
                        <tr>
                            <td class="proposal-service-no">{{ $serviceArea['no'] ?? '' }}</td>
                            <td class="proposal-service-area-title">{{ $serviceArea['service_area'] ?? '' }}</td>
                            <td class="proposal-service-scope-list">
                                <ol type="a">
                                    @foreach (($serviceArea['scope'] ?? []) as $scope)
                                        <li>{{ $scope }}</li>
                                    @endforeach
                                </ol>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            </div>
            {!! $renderPageFooter() !!}
        </section>
    @endforeach

    @foreach ($productAreaChunks as $productChunkIndex => $productAreaChunk)
        <section class="proposal-page proposal-inner-page">
            {!! $renderPageNumber(++$pageNumber) !!}
            <div class="proposal-page-body">
            <h2 class="proposal-section-heading">
                <span class="proposal-section-number">{{ $toRoman(3) }}.</span>
                <span>Products Offered</span>
            </h2>
            <table class="proposal-service-table proposal-product-table">
                <thead>
                    <tr>
                        <th class="proposal-service-no">No.</th>
                        <th class="proposal-service-area">Product Area</th>
                        <th class="proposal-service-scope">Products Offered</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($productAreaChunk as $productArea)
                        <tr>
                            <td class="proposal-service-no">{{ $productArea['no'] ?? '' }}</td>
                            <td class="proposal-service-area-title">{{ $productArea['product_area'] ?? '' }}</td>
                            <td class="proposal-service-scope-list">
                                <ol type="a">
                                    @foreach (($productArea['products'] ?? []) as $product)
                                        <li>{{ $product }}</li>
                                    @endforeach
                                </ol>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td class="proposal-service-no">1</td>
                            <td class="proposal-service-area-title">Products</td>
                            <td class="proposal-service-scope-list">No active product records found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
            </div>
            {!! $renderPageFooter() !!}
        </section>
    @endforeach

    <section class="proposal-page proposal-inner-page">
        {!! $renderPageNumber(++$pageNumber) !!}
        <div class="proposal-page-body">
        <h2 class="proposal-section-heading">
            <span class="proposal-section-number">{{ $toRoman(4) }}.</span>
            <span>Highlights from Our Proposal and Approach</span>
        </h2>
        <p class="proposal-paragraph proposal-highlights-intro">At <strong>John Kelly &amp; Company</strong>, we believe that real progress begins with clear communication, teamwork, and trust. Our goal is to make every step of the process simple, organized, and meaningful for your business.</p>

        <div class="proposal-highlights-grid">
            @foreach (($d['proposal_highlights'] ?? []) as $highlight)
                <div class="proposal-highlight-item">
                    <h3>{{ $highlight['title'] ?? '' }}</h3>
                    <p>{{ $highlight['body'] ?? '' }}</p>
                </div>
            @endforeach
        </div>
        </div>
        {!! $renderPageFooter() !!}
    </section>

    <section class="proposal-page proposal-inner-page">
        {!! $renderPageNumber(++$pageNumber) !!}
        <div class="proposal-page-body">
        <h2 class="proposal-section-heading">
            <span class="proposal-section-number">{{ $toRoman(5) }}.</span>
            <span>Our Commitment</span>
        </h2>
        @foreach (($d['commitment'] ?? []) as $item)
            <p class="proposal-paragraph">{{ $item }}</p>
        @endforeach
        </div>
        {!! $renderPageFooter() !!}
    </section>

    <section class="proposal-page proposal-inner-page">
        {!! $renderPageNumber(++$pageNumber) !!}
        <div class="proposal-page-body">
        <h2 class="proposal-section-heading">
            <span class="proposal-section-number">{{ $toRoman(6) }}.</span>
            <span>{{ $d['proposal_intro'] ?? 'Our Proposal' }}</span>
        </h2>
        <div
            class="proposal-paragraph @if($editableMode) {{ $blockEditorClasses }} @endif"
            @if($editableMode) contenteditable="true" data-proposal-field="our_proposal_text" data-proposal-editor="multiline" @endif
        >{!! nl2br(e($d['our_proposal_text'] ?? '')) !!}</div>

        <h3 class="proposal-subheading proposal-subheading-blue">Scope of Work</h3>
        <div
            class="proposal-paragraph proposal-scope-of-service @if($editableMode) {{ $blockEditorClasses }} @endif"
            @if($editableMode) contenteditable="true" data-proposal-field="scope_of_service" data-proposal-editor="multiline" @endif
        >{!! nl2br(e(filled($d['scope_of_service'] ?? null) ? $d['scope_of_service'] : 'Scope of work needs confirmation.')) !!}</div>

        <h3 class="proposal-subheading proposal-subheading-blue">Deliverables</h3>
        <div
            class="proposal-paragraph proposal-deliverables @if($editableMode) {{ $blockEditorClasses }} @endif"
            @if($editableMode) contenteditable="true" data-proposal-field="what_you_will_receive" data-proposal-editor="multiline" @endif
        >{!! nl2br(e(filled($d['what_you_will_receive'] ?? null) ? $d['what_you_will_receive'] : 'Deliverables need confirmation.')) !!}</div>

        <h3 class="proposal-subheading proposal-subheading-blue proposal-block-spaced">Services Availed</h3>
        <table class="proposal-data-table proposal-availed-table">
            <thead>
                <tr>
                    <th>Item #</th>
                    <th>Name</th>
                    <th>Service Area</th>
                    <th>Description</th>
                    <th>Qty</th>
                    <th>Unit</th>
                    <th>Activity/Output</th>
                    <th>Frequency</th>
                    <th>Deadline</th>
                </tr>
            </thead>
            <tbody>
                @forelse (($d['service_items'] ?? []) as $item)
                    <tr>
                        <td>{{ $item['item_no'] ?? '' }}</td>
                        <td>{{ $item['name'] ?? '' }}</td>
                        <td>{{ $item['service_area'] ?? '' }}</td>
                        <td>{!! nl2br(e($item['description'] ?? '')) !!}</td>
                        <td>{{ $item['quantity'] ?? '' }}</td>
                        <td>{{ $item['unit'] ?? '' }}</td>
                        <td>{!! nl2br(e($item['activity_output'] ?? '')) !!}</td>
                        <td>{{ $item['frequency'] ?? '' }}</td>
                        <td>{{ $item['deadline'] ?? '' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td>1</td>
                        <td>No service selected</td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <h3 class="proposal-subheading proposal-subheading-blue proposal-block-spaced">Products Availed</h3>
        <table class="proposal-data-table proposal-availed-table">
            <thead>
                <tr>
                    <th>Item #</th>
                    <th>Name</th>
                    <th>Product Area</th>
                    <th>Description</th>
                    <th>Qty</th>
                    <th>Unit</th>
                    <th>Activity/Output</th>
                    <th>Frequency</th>
                    <th>Deadline</th>
                </tr>
            </thead>
            <tbody>
                @forelse (($d['product_items'] ?? []) as $item)
                    <tr>
                        <td>{{ $item['item_no'] ?? '' }}</td>
                        <td>{{ $item['name'] ?? '' }}</td>
                        <td>{{ $item['service_area'] ?? '' }}</td>
                        <td>{!! nl2br(e($item['description'] ?? '')) !!}</td>
                        <td>{{ $item['quantity'] ?? '' }}</td>
                        <td>{{ $item['unit'] ?? '' }}</td>
                        <td>{!! nl2br(e($item['activity_output'] ?? '')) !!}</td>
                        <td>{{ $item['frequency'] ?? '' }}</td>
                        <td>{{ $item['deadline'] ?? '' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td>1</td>
                        <td>No product selected</td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        </div>
        {!! $renderPageFooter() !!}
    </section>

    <section class="proposal-page proposal-inner-page">
        {!! $renderPageNumber(++$pageNumber) !!}
        <div class="proposal-page-body">
        <h2 class="proposal-section-heading">
            <span class="proposal-section-number">{{ $toRoman(6) }}.</span>
            <span>{{ $d['proposal_intro'] ?? 'Our Proposal' }} - Requirements</span>
        </h2>
        <h3 class="proposal-subheading proposal-subheading-blue">What We Need From You</h3>
            <p class="proposal-paragraph">{{ $d['requirements_intro'] ?? '' }}</p>

        <table class="proposal-data-table proposal-requirements-table">
            <thead>
                <tr>
                    <th>Item #</th>
                    <th>Name</th>
                    <th>For Sole Proprietor / Professional / Individual;</th>
                    <th>For Juridical / Corporation / Partnership;</th>
                    <th>Optional / If Applicable;</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse (($d['requirement_rows'] ?? []) as $row)
                    <tr>
                        <td>{{ $row['item_no'] ?? '' }}</td>
                        <td>{{ $row['name'] ?? '' }}</td>
                        <td
                            @if($editableMode) contenteditable="true" class="{{ $blockEditorClasses }}" data-proposal-field="requirements_sole" data-proposal-editor="multiline" @endif
                        >{!! $paragraphs($row['sole'] ?? '')->map(fn ($line) => e($line))->implode('<br>') !!}</td>
                        <td
                            @if($editableMode) contenteditable="true" class="{{ $blockEditorClasses }}" data-proposal-field="requirements_juridical" data-proposal-editor="multiline" @endif
                        >{!! $paragraphs($row['juridical'] ?? '')->map(fn ($line) => e($line))->implode('<br>') !!}</td>
                        <td
                            @if($editableMode) contenteditable="true" class="{{ $blockEditorClasses }}" data-proposal-field="requirements_optional" data-proposal-editor="multiline" @endif
                        >{!! $paragraphs($row['optional'] ?? '')->map(fn ($line) => e($line))->implode('<br>') !!}</td>
                        <td></td>
                    </tr>
                @empty
                    <tr>
                        <td>1</td>
                        <td>Client documentary requirements</td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                    </tr>
                @endforelse
            </tbody>
        </table>

            <p class="proposal-note">{{ $d['requirements_note'] ?? '' }}</p>

        </div>
        {!! $renderPageFooter() !!}
    </section>

    <section class="proposal-page proposal-inner-page">
        {!! $renderPageNumber(++$pageNumber) !!}
        <div class="proposal-page-body">
        <h2 class="proposal-section-heading">
            <span class="proposal-section-number">{{ $toRoman(6) }}.</span>
            <span>{{ $d['proposal_intro'] ?? 'Our Proposal' }} - Fees</span>
        </h2>
        <h3 class="proposal-subheading proposal-subheading-blue">Fees</h3>
        <h3 class="proposal-subheading proposal-subheading-blue proposal-subheading-tight">Services</h3>
        <table class="proposal-data-table proposal-fee-detail-table">
            <thead>
                <tr>
                    <th>Item #</th>
                    <th>Name</th>
                    <th>Service ID</th>
                    <th>Qty</th>
                    <th>Unit</th>
                    <th>Unit Price</th>
                    <th>Discount</th>
                    <th>Tax</th>
                    <th>Line Total</th>
                </tr>
            </thead>
            <tbody>
                @forelse (($d['service_fee_rows'] ?? []) as $row)
                    <tr>
                        <td>{{ $row['item_no'] ?? '' }}</td>
                        <td>{{ $row['name'] ?? '' }}</td>
                        <td>{{ $row['service_id'] ?? '' }}</td>
                        <td>{{ ($row['aggregate'] ?? false) ? '—' : ($row['quantity'] ?? '') }}</td>
                        <td>{{ ($row['aggregate'] ?? false) ? '—' : ($row['unit'] ?? '') }}</td>
                        <td>{{ ($row['aggregate'] ?? false) ? 'Included in saved fee' : $moneyOrConfirm($row['unit_price'] ?? null) }}</td>
                        <td>{{ $money($row['discount'] ?? 0) }}</td>
                        <td>{{ $money($row['tax'] ?? 0) }}</td>
                        <td>{{ $moneyOrConfirm($row['line_total'] ?? null) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="9">No service selected</td></tr>
                @endforelse
                <tr>
                    <td colspan="8">Total</td>
                    <td
                        @if($editableMode) contenteditable="true" class="{{ $numberEditorClasses }}" data-proposal-field="price_regular" data-proposal-editor="number" @endif
                    >{{ $moneyOrConfirm($d['price_regular'] ?? null) }}</td>
                </tr>
            </tbody>
        </table>

        <h3 class="proposal-subheading proposal-subheading-blue proposal-subheading-tight">Products</h3>
        <table class="proposal-data-table proposal-fee-detail-table">
            <thead>
                <tr>
                    <th>Item #</th>
                    <th>Name</th>
                    <th>Service ID</th>
                    <th>Qty</th>
                    <th>Unit</th>
                    <th>Unit Price</th>
                    <th>Discount</th>
                    <th>Tax</th>
                    <th>Line Total</th>
                </tr>
            </thead>
            <tbody>
                @forelse (($d['product_fee_rows'] ?? []) as $row)
                    <tr>
                        <td>{{ $row['item_no'] ?? '' }}</td>
                        <td>{{ $row['name'] ?? '' }}</td>
                        <td>{{ $row['service_id'] ?? '' }}</td>
                        <td>{{ ($row['aggregate'] ?? false) ? '—' : ($row['quantity'] ?? '') }}</td>
                        <td>{{ ($row['aggregate'] ?? false) ? '—' : ($row['unit'] ?? '') }}</td>
                        <td>{{ ($row['aggregate'] ?? false) ? 'Included in saved fee' : $moneyOrConfirm($row['unit_price'] ?? null) }}</td>
                        <td>{{ $money($row['discount'] ?? 0) }}</td>
                        <td>{{ $money($row['tax'] ?? 0) }}</td>
                        <td>{{ $moneyOrConfirm($row['line_total'] ?? null) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="9">No product selected</td></tr>
                @endforelse
                <tr><td colspan="8">Total</td><td data-proposal-display="price_products">{{ $moneyOrConfirm($d['price_products'] ?? null) }}</td></tr>
            </tbody>
        </table>

        </div>
        {!! $renderPageFooter() !!}
    </section>

    <section class="proposal-page proposal-inner-page">
        {!! $renderPageNumber(++$pageNumber) !!}
        <div class="proposal-page-body">
        <h2 class="proposal-section-heading">
            <span class="proposal-section-number">{{ $toRoman(6) }}.</span>
            <span>{{ $d['proposal_intro'] ?? 'Our Proposal' }} - Fee Summary</span>
        </h2>
        <table class="proposal-pricing-table">
            <thead>
                <tr>
                    <th>Item</th>
                    <th>Amount (P)</th>
                </tr>
            </thead>
            <tbody>
                <tr><td>Total Services</td><td data-proposal-display="price_regular">{{ $moneyOrConfirm($d['price_regular'] ?? null) }}</td></tr>
                <tr><td>Total Product</td><td data-proposal-display="price_products">{{ $moneyOrConfirm($d['price_products'] ?? null) }}</td></tr>
                <tr>
                    <td>Discount</td>
                    <td
                        @if($editableMode) contenteditable="true" class="{{ $numberEditorClasses }}" data-proposal-field="price_discount" data-proposal-editor="number" @endif
                    >{{ $moneyOrConfirm($d['price_discount'] ?? null) }}</td>
                </tr>
                <tr><td>Subtotal (After Discount)</td><td data-proposal-display="price_subtotal">{{ $moneyOrConfirm($d['price_subtotal'] ?? null) }}</td></tr>
                <tr>
                    <td>Tax (if applicable)</td>
                    <td
                        @if($editableMode) contenteditable="true" class="{{ $numberEditorClasses }}" data-proposal-field="price_tax" data-proposal-editor="number" @endif
                    >{{ $moneyOrConfirm($d['price_tax'] ?? null) }}</td>
                </tr>
                <tr class="is-total"><td>Total Fees</td><td data-proposal-display="price_total">{{ $moneyOrConfirm($d['price_total'] ?? null) }}</td></tr>
                <tr><td>Payment Terms</td><td>{{ filled($d['payment_terms'] ?? null) ? $d['payment_terms'] : 'Payment terms need confirmation.' }}</td></tr>
                <tr><td>Down Payment</td><td data-proposal-display="price_down">{{ $moneyOrConfirm($d['price_down'] ?? null) }}</td></tr>
                <tr><td>Balance Payable</td><td data-proposal-display="price_balance">{{ $moneyOrConfirm($d['price_balance'] ?? null) }}</td></tr>
            </tbody>
        </table>
        <p class="proposal-note">{{ $d['supplemental_fee_note'] ?? '' }}</p>
        </div>
        {!! $renderPageFooter() !!}
    </section>

    <section class="proposal-page proposal-inner-page">
        {!! $renderPageNumber(++$pageNumber) !!}
        <div class="proposal-page-body">
        <h2 class="proposal-section-heading">
            <span class="proposal-section-number">{{ $toRoman(7) }}.</span>
            <span>Agreement Inclusions and Exclusions</span>
        </h2>
        <h3 class="proposal-subheading">Agreement Inclusions</h3>
        <ul class="proposal-bullet-list">
            @foreach (($d['agreement_inclusions'] ?? []) as $item)
                <li>{{ $item }}</li>
            @endforeach
        </ul>

        <h3 class="proposal-subheading">Agreement Exclusions</h3>
        <ul class="proposal-bullet-list">
            @foreach (($d['agreement_exclusions'] ?? []) as $item)
                <li>{{ $item }}</li>
            @endforeach
        </ul>

        <h3 class="proposal-subheading">Supplemental Fees</h3>
        <table class="proposal-data-table">
            <thead>
                <tr>
                    <th>Service</th>
                    <th>Description</th>
                    <th>Fee</th>
                </tr>
            </thead>
            <tbody>
                @foreach (($d['supplemental_fees'] ?? []) as $fee)
                    <tr>
                        <td>{{ $fee['service'] ?? '' }}</td>
                        <td>{{ $fee['description'] ?? '' }}</td>
                        <td>{{ $fee['fee'] ?? '' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        </div>
        {!! $renderPageFooter() !!}
    </section>

    @foreach ($termsChunks as $termsChunkIndex => $termsChunk)
    <section class="proposal-page proposal-inner-page">
        {!! $renderPageNumber(++$pageNumber) !!}
        <div class="proposal-page-body">
        <h2 class="proposal-section-heading">
            <span class="proposal-section-number">{{ $toRoman(8) }}.</span>
            <span>Terms and Conditions</span>
        </h2>
        @foreach ($termsChunk as $termIndex => $term)
            <div class="proposal-term-block">
                <h3 class="proposal-subheading proposal-subheading-tight">
                    <span class="proposal-term-number">{{ $termsChunkIndex + $termIndex + 1 }}.</span>
                    <span>{{ $term['title'] ?? '' }}</span>
                </h3>

                @if (!empty($term['intro']))
                    <p class="proposal-paragraph">{{ $term['intro'] }}</p>
                @endif

                @foreach (($term['paragraphs'] ?? []) as $paragraph)
                    <p class="proposal-paragraph">{{ $paragraph }}</p>
                @endforeach

                @if (!empty($term['items']))
                    <ol class="proposal-numbered-list">
                        @foreach ($term['items'] as $item)
                            <li>{{ $item }}</li>
                        @endforeach
                    </ol>
                @endif

                @if (!empty($term['outro']))
                    <p class="proposal-paragraph">{{ $term['outro'] }}</p>
                @endif
            </div>
        @endforeach
        </div>
        {!! $renderPageFooter() !!}
    </section>
    @endforeach

    <section class="proposal-page proposal-inner-page">
        {!! $renderPageNumber(++$pageNumber) !!}
        <div class="proposal-page-body">
        <h2 class="proposal-section-heading">
            <span class="proposal-section-number">{{ $toRoman(9) }}.</span>
            <span>Client Engagement Team</span>
        </h2>
        <p class="proposal-paragraph">{{ $d['engagement_team_intro'] ?? '' }}</p>
        <table class="proposal-data-table">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Designation</th>
                    <th>Branch</th>
                    <th>Email</th>
                </tr>
            </thead>
            <tbody>
                @foreach (($d['engagement_team'] ?? []) as $member)
                    <tr>
                        <td>{{ $member['name'] ?? '' }}</td>
                        <td>{{ $member['designation'] ?? '' }}</td>
                        <td>{{ $member['branch'] ?? '' }}</td>
                        <td>{{ $member['email'] ?? '' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <p class="proposal-end-note">-End-</p>
        <p class="proposal-system-note">{{ $d['system_note'] ?? '' }}</p>
        </div>
        {!! $renderPageFooter() !!}
    </section>

    <section class="proposal-page proposal-inner-page">
        {!! $renderPageNumber(++$pageNumber) !!}
        <div class="proposal-page-body">
        <h2 class="proposal-section-heading">
            <span class="proposal-section-number">{{ $toRoman(10) }}.</span>
            <span>Conforme and Acceptance</span>
        </h2>
        <p class="proposal-paragraph">By signing below, the parties acknowledge and accept the terms and conditions outlined in this proposal, which shall constitute a binding agreement.</p>

        <div class="proposal-signature-grid">
            <div class="proposal-signature-block">
                <div class="proposal-signature-label">For the Client</div>
                <div class="proposal-signature-line">{{ $d['client_name'] ?? '' }}</div>
                <div class="proposal-signature-subline">{{ $d['business_name'] ?? '' }}</div>
            </div>
            <div class="proposal-signature-block">
                <div class="proposal-signature-label">For John Kelly &amp; Company</div>
                <div
                    class="proposal-signature-line @if($editableMode) {{ $textEditorClasses }} @endif"
                    @if($editableMode) contenteditable="true" data-proposal-field="prepared_by_name" data-proposal-editor="text" @endif
                >{{ $d['prepared_by_name'] ?? '' }}</div>
                <div class="proposal-signature-subline">ID Number: {{ $d['prepared_by_id'] ?? '' }}</div>
            </div>
        </div>

        </div>
        {!! $renderPageFooter() !!}
    </section>

    @if (!empty($d['product_catalog']['products']) || !empty($d['product_catalog']['services']))
    <section class="proposal-page proposal-inner-page" data-product-catalog="true">
        <div class="proposal-page-body">
        <h2 class="proposal-section-heading">Catalog Configuration and Applicable Terms</h2>
        @foreach (array_merge($d['product_catalog']['services'] ?? [], $d['product_catalog']['products'] ?? []) as $product)
            <h3 class="proposal-subheading">{{ $product['name'] }} ({{ $product['sku'] }})</h3>
            @foreach (['scope_of_work' => isset($product['product_id']) ? 'Scope of Product' : 'Scope of Service', 'deliverables' => 'Deliverables / What You Will Receive', 'client_responsibilities' => 'Client Responsibilities', 'exclusions' => 'Exclusions / Out of Scope'] as $field => $label)
                <h4 class="proposal-subheading">{{ $label }}</h4>
                <div style="white-space: pre-line; overflow-wrap: anywhere">{{ filled($product[$field] ?? '') ? $product[$field] : 'No catalog configuration provided.' }}</div>
            @endforeach
            @if (!empty($product['requirements']))
                <h4 class="proposal-subheading">{{ isset($product['product_id']) ? 'Product Requirements' : 'Service Requirements' }}</h4>
                <table class="proposal-data-table"><thead><tr><th>Requirement</th><th>Evidence Needed</th><th>Validity</th></tr></thead><tbody>
                @foreach ($product['requirements'] as $requirement)
                    <tr><td>{{ $requirement['name'] }}{{ $requirement['is_mandatory'] ? ' (Required)' : ' (Optional)' }}</td><td>{{ $requirement['evidence_type'] }}</td><td>{{ $requirement['validity_expiration'] }}</td></tr>
                @endforeach
                </tbody></table>
            @endif
        @endforeach
        @if (!empty($d['product_catalog']['conflicts']))
            <p class="proposal-note">Resolve conflicting product term headings before issuing: {{ implode(', ', $d['product_catalog']['conflicts']) }}</p>
        @endif
        @foreach ($serviceCatalogTerms as $term)
            <h3 class="proposal-subheading">{{ $term['title'] }}</h3>
            <div style="white-space: pre-line; overflow-wrap: anywhere">{{ $term['content'] }}</div>
        @endforeach
        @foreach ($d['product_catalog']['terms'] as $term)
            <h3 class="proposal-subheading">{{ $term['title'] }}</h3>
            <div style="white-space: pre-line; overflow-wrap: anywhere">{{ $term['content'] }}</div>
        @endforeach
        </div>
        {!! $renderPageFooter() !!}
    </section>
    @endif
</div>
