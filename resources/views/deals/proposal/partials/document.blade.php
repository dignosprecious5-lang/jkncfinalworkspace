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
            >{{ $d['service_type'] ?? 'BIR Compliance Services' }}</div>
            <div class="proposal-cover-date">{{ $d['date'] ?? now()->format('F d, Y') }}</div>

            <div class="proposal-presented-label">Presented For:</div>
            <div class="proposal-presented-name">{{ $d['client_name'] ?? 'Client Name' }}</div>
            <div class="proposal-presented-name">{{ $d['business_name'] ?? 'Business Name' }}</div>
            <div
                class="proposal-presented-location @if($editableMode) {{ $textEditorClasses }} @endif"
                @if($editableMode) contenteditable="true" data-proposal-field="location" data-proposal-editor="text" @endif
            >{{ $d['location'] ?? 'Philippines' }}</div>
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

        <h3 class="proposal-subheading proposal-subheading-blue proposal-block-spaced">Services Availed</h3>
        <table class="proposal-data-table proposal-availed-table">
            <thead>
                <tr>
                    <th>Item #</th>
                    <th>Name</th>
                    <th>Description</th>
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
                        <td>{!! nl2br(e($item['description'] ?? '')) !!}</td>
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
                        <td>TBD</td>
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
                    <th>Description</th>
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
                        <td>{!! nl2br(e($item['description'] ?? '')) !!}</td>
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
                        <td>TBD</td>
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
                    <th>Price</th>
                </tr>
            </thead>
            <tbody>
                @forelse (($d['service_fee_rows'] ?? []) as $row)
                    <tr>
                        <td>{{ $row['item_no'] ?? '' }}</td>
                        <td>{{ $row['name'] ?? '' }}</td>
                        <td>{{ $row['service_id'] ?? '' }}</td>
                        <td>{{ $money($row['price'] ?? 0) }}</td>
                    </tr>
                @empty
                    <tr><td>1</td><td>No service selected</td><td></td><td>{{ $money(0) }}</td></tr>
                @endforelse
                <tr>
                    <td></td>
                    <td></td>
                    <td>Total</td>
                    <td
                        @if($editableMode) contenteditable="true" class="{{ $numberEditorClasses }}" data-proposal-field="price_regular" data-proposal-editor="number" @endif
                    >{{ $money($d['price_regular'] ?? 0) }}</td>
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
                    <th>Price</th>
                </tr>
            </thead>
            <tbody>
                @forelse (($d['product_fee_rows'] ?? []) as $row)
                    <tr>
                        <td>{{ $row['item_no'] ?? '' }}</td>
                        <td>{{ $row['name'] ?? '' }}</td>
                        <td>{{ $row['service_id'] ?? '' }}</td>
                        <td>{{ $money($row['price'] ?? 0) }}</td>
                    </tr>
                @empty
                    <tr><td>1</td><td>No product selected</td><td></td><td>{{ $money(0) }}</td></tr>
                @endforelse
                <tr><td></td><td></td><td>Total</td><td data-proposal-display="price_products">{{ $money($d['price_products'] ?? 0) }}</td></tr>
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
                <tr><td>Total Services</td><td data-proposal-display="price_regular">{{ $money($d['price_regular'] ?? 0) }}</td></tr>
                <tr><td>Total Product</td><td data-proposal-display="price_products">{{ $money($d['price_products'] ?? 0) }}</td></tr>
                <tr>
                    <td>Discount</td>
                    <td
                        @if($editableMode) contenteditable="true" class="{{ $numberEditorClasses }}" data-proposal-field="price_discount" data-proposal-editor="number" @endif
                    >{{ $money($d['price_discount'] ?? 0) }}</td>
                </tr>
                <tr><td>Subtotal (After Discount)</td><td data-proposal-display="price_subtotal">{{ $money($d['price_subtotal'] ?? 0) }}</td></tr>
                <tr>
                    <td>Tax (if applicable)</td>
                    <td
                        @if($editableMode) contenteditable="true" class="{{ $numberEditorClasses }}" data-proposal-field="price_tax" data-proposal-editor="number" @endif
                    >{{ $money($d['price_tax'] ?? 0) }}</td>
                </tr>
                <tr class="is-total"><td>Total Fees</td><td data-proposal-display="price_total">{{ $money($d['price_total'] ?? 0) }}</td></tr>
                <tr><td>Down Payment (50%)</td><td data-proposal-display="price_down">{{ $money($d['price_down'] ?? 0) }}</td></tr>
                <tr><td>Balance Payable Upon Completion (50%)</td><td data-proposal-display="price_balance">{{ $money($d['price_balance'] ?? 0) }}</td></tr>
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
</div>
