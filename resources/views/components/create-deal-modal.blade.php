<div x-data="{ open: false }" 
     x-show="open" 
     @open-create-deal-modal.window="open = true" 
     @keydown.escape.window="open = false"
     class="relative z-50" 
     style="display: none;">

    <!-- Modal Backdrop -->
    <div x-show="open" 
         x-transition:enter="ease-in-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="ease-in-out duration-300"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 bg-gray-900/50 backdrop-blur-sm transition-opacity"></div>

    <div class="fixed inset-0 overflow-hidden">
        <div class="absolute inset-0 overflow-hidden">
            <div class="pointer-events-none fixed inset-y-0 right-0 flex max-w-full pl-10">
                
                <!-- Slide-over Drawer Panel -->
                <div x-show="open" 
                     x-transition:enter="transform transition ease-in-out duration-300"
                     x-transition:enter-start="translate-x-full"
                     x-transition:enter-end="translate-x-0"
                     x-transition:leave="transform transition ease-in-out duration-300"
                     x-transition:leave-start="translate-x-0"
                     x-transition:leave-end="translate-x-full"
                     class="pointer-events-auto w-screen max-w-3xl bg-white shadow-2xl flex flex-col justify-between">
                    
                    <form
                        action="{{ route('deals.store') }}"
                        method="POST"
                        class="h-full flex flex-col"
                        x-data="{ isSubmitting: false }"
                        @submit="if (isSubmitting) { $event.preventDefault(); return false; } isSubmitting = true;"
                    >
                        @csrf

                        <!-- Sticky Top Header -->
                        <div class="p-6 border-b border-gray-200 bg-white flex justify-between items-start sticky top-0 z-10">
                            <div>
                                <h2 class="text-xl font-bold text-gray-900">Create Deal</h2>
                                <p class="text-sm text-gray-500 mt-0.5">Select an existing client, then complete the consulting and deal form.</p>
                            </div>
                            <button type="button" @click="open = false" class="text-gray-400 hover:text-gray-600 p-1 text-2xl font-semibold leading-none rounded-lg focus:outline-none">&times;</button>
                        </div>

                        <!-- Form Context Banner (Fixed Layout & Compact Dropdown) -->
                        <div class="bg-gray-50/80 px-6 py-3 border-b border-gray-200 flex items-center justify-between gap-4">
                            
                            <!-- Title Text: Forced single-line horizontal layout -->
                            <span class="font-bold text-gray-700 text-xs uppercase tracking-wider whitespace-nowrap shrink-0">
                                Consulting &amp; Deal Form
                            </span>

                            <!-- Owner Dropdown Container: Fixed Compact Width -->
                            <div class="relative w-48 shrink-0">
                                <span class="absolute left-3.5 top-1/2 -translate-y-1/2 w-2 h-2 rounded-full bg-blue-600 pointer-events-none z-10"></span>
                                <select name="owner_id" class="w-full bg-white border border-gray-200 rounded-full pl-7 pr-8 py-1.5 text-xs font-medium text-gray-700 shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 appearance-none cursor-pointer">
                                    <option value="">Owner</option>
                                    @php
                                        $allUsers = \App\Models\User::orderBy('name')->get();
                                    @endphp
                                    @foreach($allUsers as $u)
                                        <option value="{{ $u->id }}" {{ (auth()->id() == $u->id) ? 'selected' : '' }}>{{ $u->name }}</option>
                                    @endforeach
                                </select>
                                <div class="absolute right-3 top-1/2 -translate-y-1/2 pointer-events-none text-gray-400">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                    </svg>
                                </div>
                            </div>

                        </div>

                        <!-- Scrollable Form Body -->
                        <div class="p-6 overflow-y-auto space-y-6 flex-1 text-sm text-gray-700">

                            <!-- Card 1: Customer & Account -->
                            <div class="bg-white border border-gray-200 rounded-xl p-5 space-y-3 shadow-sm">
                                <h3 class="font-bold text-gray-900 text-base">Customer &amp; Account</h3>
                                <div class="grid grid-cols-2 gap-4">
                                    <label class="flex items-center p-3 border border-gray-200 rounded-lg cursor-pointer hover:bg-gray-50 transition-colors">
                                        <input type="radio" name="customer_type" value="business" class="mr-3 text-blue-600 focus:ring-blue-500">
                                        <span class="font-medium text-gray-800">Business</span>
                                    </label>
                                    <label class="flex items-center p-3 border border-gray-200 rounded-lg cursor-pointer hover:bg-gray-50 transition-colors">
                                        <input type="radio" name="customer_type" value="individual" class="mr-3 text-blue-600 focus:ring-blue-500">
                                        <span class="font-medium text-gray-800">Individual</span>
                                    </label>
                                </div>
                            </div>

                            <!-- Card 2: Deal Metadata Information -->
                            <div class="bg-gray-50/50 border border-gray-200 rounded-xl p-5 space-y-3">
                                <div>
                                    <h3 class="font-bold text-gray-900 text-base">Deal Information</h3>
                                    <p class="text-xs text-gray-500">Auto-generated metadata appears here after the deal is saved.</p>
                                </div>
                                <div class="grid grid-cols-3 gap-4 text-xs pt-2">
                                    <div>
                                        <span class="block text-gray-400 uppercase font-bold text-[10px] tracking-wider mb-0.5">Deal Code</span>
                                        <span class="text-gray-500 font-medium italic">Auto-generated after save</span>
                                    </div>
                                    <div>
                                        <span class="block text-gray-400 uppercase font-bold text-[10px] tracking-wider mb-0.5">Created By</span>
                                        <span class="text-gray-800 font-semibold">{{ auth()->user()?->name ?? 'Administrator' }}</span>
                                    </div>
                                    <div>
                                        <span class="block text-gray-400 uppercase font-bold text-[10px] tracking-wider mb-0.5">Created At</span>
                                        <span
                                            class="text-gray-800 font-semibold"
                                            x-data="{
                                                now: '',
                                                formatDate() {
                                                    const d = new Date();
                                                    const months = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
                                                    const month = months[d.getMonth()];
                                                    const day = String(d.getDate()).padStart(2, '0');
                                                    const year = d.getFullYear();
                                                    let hours = d.getHours();
                                                    const minutes = String(d.getMinutes()).padStart(2, '0');
                                                    const seconds = String(d.getSeconds()).padStart(2, '0');
                                                    const ampm = hours >= 12 ? 'PM' : 'AM';
                                                    hours = hours % 12;
                                                    hours = hours ? String(hours).padStart(2, '0') : '12';
                                                    this.now = `${month} ${day} • ${year} at ${hours}:${minutes}:${seconds} ${ampm}`;
                                                }
                                            }"
                                            x-init="formatDate(); setInterval(() => formatDate(), 1000)"
                                            x-text="now"
                                        >{{ now()->timezone('Asia/Manila')->format('F d • Y \a\t h:i:s A') }}</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Card 3: Select Existing Contact / Client -->
                            <div class="bg-white border border-gray-200 rounded-xl p-5 space-y-4 shadow-sm">
                                <div>
                                    <h3 class="font-bold text-gray-900 text-base">Select Existing Contact / Client</h3>
                                    <p class="text-xs text-gray-500">Search by contact name, company, email, or mobile number.</p>
                                </div>
                                <div>
                                    <label class="block font-semibold text-gray-700 mb-1.5">Search Existing Client</label>
                                    <div class="relative">
                                        <input type="text" name="client_search" placeholder="Type name, company, email, or mobile..." class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 focus:outline-none">
                                        <svg class="w-5 h-5 absolute left-3 top-2.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                                    </div>
                                </div>
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label class="block font-semibold text-gray-700 mb-1.5">Deal Code / Title</label>
                                        <input type="text" name="deal_title" value="" placeholder="Auto-generated (e.g. CONDEAL-{{ date('Y') }}-###)" readonly class="w-full bg-gray-100 border border-gray-200 rounded-lg p-2.5 text-sm text-gray-500 cursor-not-allowed">
                                        <p class="text-[11px] text-gray-400 mt-1">Auto-generated CONDEAL code assigned when saved.</p>
                                    </div>
                                    <div>
                                        <label class="block font-semibold text-gray-700 mb-1.5">Pipeline Stage</label>
                                        <select name="pipeline_stage" class="w-full border border-gray-300 rounded-lg p-2.5 text-sm bg-white focus:ring-2 focus:ring-blue-500 focus:outline-none">
                                            <option value="inquiry">Inquiry</option>
                                            <option value="qualification">Qualification</option>
                                            <option value="consultation">Consultation</option>
                                            <option value="proposal">Proposal</option>
                                            <option value="negotiation">Negotiation</option>
                                            <option value="payment">Payment</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="bg-amber-50 border border-amber-200 text-amber-800 text-xs p-3 rounded-lg flex items-center gap-2">
                                    <svg class="w-4 h-4 shrink-0 text-amber-600" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path></svg>
                                    <span>Contact selection is required before completing the rest of the deal form.</span>
                                </div>
                            </div>

                            <!-- Card 4: Contact Information -->
                            <div class="bg-white border border-gray-200 rounded-xl p-5 space-y-4 shadow-sm">
                                <div>
                                    <h3 class="font-bold text-gray-900 text-base">Contact Information</h3>
                                    <p class="text-xs text-gray-500">Fields auto-fill from selected contact and remain editable.</p>
                                </div>
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label class="block font-semibold text-gray-700 mb-1">Salutation</label>
                                        <select name="salutation" class="w-full border border-gray-300 rounded-lg p-2 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none">
                                            <option value="">Select salutation</option>
                                            <option value="Mr.">Mr.</option>
                                            <option value="Ms.">Ms.</option>
                                            <option value="Mrs.">Mrs.</option>
                                            <option value="Dr.">Dr.</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block font-semibold text-gray-700 mb-1">Sex</label>
                                        <select name="sex" class="w-full border border-gray-300 rounded-lg p-2 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none">
                                            <option value="">Select sex</option>
                                            <option value="male">Male</option>
                                            <option value="female">Female</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block font-semibold text-gray-700 mb-1">First Name</label>
                                        <input type="text" name="first_name" class="w-full border border-gray-300 rounded-lg p-2 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none">
                                    </div>
                                    <div>
                                        <label class="block font-semibold text-gray-700 mb-1">Middle Initial</label>
                                        <input type="text" name="middle_initial" class="w-full border border-gray-300 rounded-lg p-2 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none">
                                    </div>
                                    <div>
                                        <label class="block font-semibold text-gray-700 mb-1">Last Name</label>
                                        <input type="text" name="last_name" class="w-full border border-gray-300 rounded-lg p-2 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none">
                                    </div>
                                    <div>
                                        <label class="block font-semibold text-gray-700 mb-1">Name Extension</label>
                                        <input type="text" name="name_extension" class="w-full border border-gray-300 rounded-lg p-2 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none">
                                    </div>
                                    <div>
                                        <label class="block font-semibold text-gray-700 mb-1">Date of Birth</label>
                                        <input type="date" name="date_of_birth" class="w-full border border-gray-300 rounded-lg p-2 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none">
                                    </div>
                                    <div>
                                        <label class="block font-semibold text-gray-700 mb-1">Email Address</label>
                                        <input type="email" name="email" class="w-full border border-gray-300 rounded-lg p-2 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none">
                                    </div>
                                </div>
                                <div>
                                    <label class="block font-semibold text-gray-700 mb-1">Mobile Number</label>
                                    <input type="text" name="mobile_number" class="w-full border border-gray-300 rounded-lg p-2 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none">
                                </div>
                                <div>
                                    <label class="block font-semibold text-gray-700 mb-1">Address</label>
                                    <textarea name="address" rows="2" class="w-full border border-gray-300 rounded-lg p-2 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none"></textarea>
                                </div>
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label class="block font-semibold text-gray-700 mb-1">Company</label>
                                        <input type="text" name="company" class="w-full border border-gray-300 rounded-lg p-2 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none">
                                    </div>
                                    <div>
                                        <label class="block font-semibold text-gray-700 mb-1">Position / Designation</label>
                                        <input type="text" name="position" class="w-full border border-gray-300 rounded-lg p-2 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none">
                                    </div>
                                </div>
                                <div>
                                    <label class="block font-semibold text-gray-700 mb-1">Company Address</label>
                                    <textarea name="company_address" rows="2" class="w-full border border-gray-300 rounded-lg p-2 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none"></textarea>
                                </div>
                            </div>

                            <!-- Card 5: Services & Products -->
                            <div class="bg-white border border-gray-200 rounded-xl p-5 space-y-4 shadow-sm">
                                <h3 class="font-bold text-gray-900 text-base">Services</h3>
                                <div class="border border-dashed border-gray-300 bg-gray-50/50 rounded-lg p-3 text-xs text-gray-500">
                                    Select a service area first to show matching services.
                                </div>
                                <label class="flex items-center p-3 border border-gray-200 rounded-lg text-sm cursor-pointer hover:bg-gray-50">
                                    <input type="checkbox" name="services[]" value="others" class="mr-2.5 text-blue-600 focus:ring-blue-500 rounded">
                                    <span class="font-medium">Others</span>
                                </label>

                                <h3 class="font-bold text-gray-900 text-base pt-3 border-t border-gray-100">Products</h3>
                                <p class="text-xs text-gray-500">Select a service area to narrow matching products. Products without a linked service remain available below.</p>
                                <div class="space-y-2">
                                    <span class="block text-xs uppercase font-bold text-gray-400 tracking-wider">Unlinked Products</span>
                                    <label class="flex items-center p-3 border border-gray-200 rounded-lg text-sm cursor-pointer hover:bg-gray-50">
                                        <input type="checkbox" name="products[]" value="Stock Certificate Printing" class="mr-2.5 text-blue-600 focus:ring-blue-500 rounded">
                                        <span class="font-medium">Stock Certificate Printing</span>
                                    </label>
                                    <label class="flex items-center p-3 border border-gray-200 rounded-lg text-sm cursor-pointer hover:bg-gray-50">
                                        <input type="checkbox" name="products[]" value="Others" class="mr-2.5 text-blue-600 focus:ring-blue-500 rounded">
                                        <span class="font-medium">Others</span>
                                    </label>
                                </div>
                            </div>

                            <!-- Card 6: Scope of Work -->
                            <div class="bg-white border border-gray-200 rounded-xl p-5 space-y-2 shadow-sm">
                                <h3 class="font-bold text-gray-900 text-base">Scope of Work</h3>
                                <p class="text-xs text-gray-500">Describe the detailed scope of the engagement.</p>
                                <textarea name="scope_of_work" rows="3" class="w-full border border-gray-300 rounded-lg p-2.5 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none"></textarea>
                            </div>

                            <!-- Card 7: Engagement Type -->
                            <div class="bg-white border border-gray-200 rounded-xl p-5 space-y-3 shadow-sm">
                                <h3 class="font-bold text-gray-900 text-base">Engagement Type</h3>
                                <div class="grid grid-cols-3 gap-3">
                                    <label class="flex items-center p-3 border border-gray-200 rounded-lg text-xs cursor-pointer hover:bg-gray-50">
                                        <input type="radio" name="engagement_type" value="project" class="mr-2 text-blue-600 focus:ring-blue-500">
                                        <span class="font-medium">Project Engagement</span>
                                    </label>
                                    <label class="flex items-center p-3 border border-gray-200 rounded-lg text-xs cursor-pointer hover:bg-gray-50">
                                        <input type="radio" name="engagement_type" value="retainer" class="mr-2 text-blue-600 focus:ring-blue-500">
                                        <span class="font-medium">Regular (Retainer)</span>
                                    </label>
                                    <label class="flex items-center p-3 border border-gray-200 rounded-lg text-xs cursor-pointer hover:bg-gray-50">
                                        <input type="radio" name="engagement_type" value="hybrid" class="mr-2 text-blue-600 focus:ring-blue-500">
                                        <span class="font-medium">Hybrid Engagement</span>
                                    </label>
                                </div>
                            </div>

                            <!-- Card 8: Client Requirements -->
                            <div class="bg-white border border-gray-200 rounded-xl p-5 space-y-3 shadow-sm">
                                <h3 class="font-bold text-gray-900 text-base">Client Requirements</h3>
                                <div class="overflow-x-auto">
                                    <table class="w-full text-xs text-left border-collapse">
                                        <thead>
                                            <tr class="border-b border-gray-200 text-gray-400 uppercase tracking-wider text-[10px]">
                                                <th class="py-2.5 font-bold">Requirement</th>
                                                <th class="py-2.5 text-center w-24 font-bold">Provided</th>
                                                <th class="py-2.5 text-center w-24 font-bold">Pending</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-gray-100 text-gray-700">
                                            <tr><td class="py-2.5 font-medium">Client Contact Form</td><td class="text-center"><input type="radio" name="req[client_contact_form]" value="provided" class="text-blue-600"></td><td class="text-center"><input type="radio" name="req[client_contact_form]" value="pending" class="text-blue-600"></td></tr>
                                            <tr><td class="py-2.5 font-medium">Deal Form</td><td class="text-center"><input type="radio" name="req[deal_form]" value="provided" class="text-blue-600"></td><td class="text-center"><input type="radio" name="req[deal_form]" value="pending" class="text-blue-600"></td></tr>
                                            <tr><td class="py-2.5 font-medium">Business Information Form</td><td class="text-center"><input type="radio" name="req[business_info_form]" value="provided" class="text-blue-600"></td><td class="text-center"><input type="radio" name="req[business_info_form]" value="pending" class="text-blue-600"></td></tr>
                                            <tr><td class="py-2.5 font-medium">Client Information Form</td><td class="text-center"><input type="radio" name="req[client_info_form]" value="provided" class="text-blue-600"></td><td class="text-center"><input type="radio" name="req[client_info_form]" value="pending" class="text-blue-600"></td></tr>
                                            <tr><td class="py-2.5 font-medium">Service Task Activation & Routing Tracker (START)</td><td class="text-center"><input type="radio" name="req[start_tracker]" value="provided" class="text-blue-600"></td><td class="text-center"><input type="radio" name="req[start_tracker]" value="pending" class="text-blue-600"></td></tr>
                                            <tr><td class="py-2.5 font-medium">Others</td><td class="text-center"><input type="radio" name="req[others]" value="provided" class="text-blue-600"></td><td class="text-center"><input type="radio" name="req[others]" value="pending" class="text-blue-600"></td></tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <!-- Card 9: Required Actions -->
                            <div class="bg-white border border-gray-200 rounded-xl p-5 space-y-3 shadow-sm">
                                <h3 class="font-bold text-gray-900 text-base">Required Actions</h3>
                                <div class="grid grid-cols-2 gap-3 text-xs">
                                    <label class="flex items-center p-2.5 border border-gray-200 rounded-lg cursor-pointer hover:bg-gray-50"><input type="checkbox" name="actions[]" value="Document Review" class="mr-2.5 text-blue-600 rounded"> Document Review</label>
                                    <label class="flex items-center p-2.5 border border-gray-200 rounded-lg cursor-pointer hover:bg-gray-50"><input type="checkbox" name="actions[]" value="Regulatory Research" class="mr-2.5 text-blue-600 rounded"> Regulatory Research</label>
                                    <label class="flex items-center p-2.5 border border-gray-200 rounded-lg cursor-pointer hover:bg-gray-50"><input type="checkbox" name="actions[]" value="Drafting of Documents" class="mr-2.5 text-blue-600 rounded"> Drafting of Documents</label>
                                    <label class="flex items-center p-2.5 border border-gray-200 rounded-lg cursor-pointer hover:bg-gray-50"><input type="checkbox" name="actions[]" value="Client Consultation" class="mr-2.5 text-blue-600 rounded"> Client Consultation</label>
                                    <label class="flex items-center p-2.5 border border-gray-200 rounded-lg cursor-pointer hover:bg-gray-50"><input type="checkbox" name="actions[]" value="Compliance Check" class="mr-2.5 text-blue-600 rounded"> Compliance Check</label>
                                    <label class="flex items-center p-2.5 border border-gray-200 rounded-lg cursor-pointer hover:bg-gray-50"><input type="checkbox" name="actions[]" value="Financial Analysis" class="mr-2.5 text-blue-600 rounded"> Financial Analysis</label>
                                    <label class="flex items-center p-2.5 border border-gray-200 rounded-lg cursor-pointer hover:bg-gray-50"><input type="checkbox" name="actions[]" value="Government Filing / Processing" class="mr-2.5 text-blue-600 rounded"> Government Filing / Processing</label>
                                    <label class="flex items-center p-2.5 border border-gray-200 rounded-lg cursor-pointer hover:bg-gray-50"><input type="checkbox" name="actions[]" value="Internal Approval" class="mr-2.5 text-blue-600 rounded"> Internal Approval</label>
                                    <label class="flex items-center p-2.5 border border-gray-200 rounded-lg cursor-pointer hover:bg-gray-50 col-span-2"><input type="checkbox" name="actions[]" value="Others" class="mr-2.5 text-blue-600 rounded"> Others</label>
                                </div>
                            </div>

                            <!-- Card 10: Fees Structure -->
                            <div class="bg-white border border-gray-200 rounded-xl p-5 space-y-4 shadow-sm">
                                <h3 class="font-bold text-gray-900 text-base">Fees</h3>
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label class="block font-semibold text-gray-700 mb-1">Estimated Professional Fee</label>
                                        <input type="number" step="0.01" name="est_professional_fee" placeholder="₱ 0.00" class="w-full border border-gray-300 rounded-lg p-2 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none">
                                    </div>
                                    <div>
                                        <label class="block font-semibold text-gray-700 mb-1">Estimated Government Fees</label>
                                        <input type="number" step="0.01" name="est_government_fee" placeholder="₱ 0.00" class="w-full border border-gray-300 rounded-lg p-2 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none">
                                    </div>
                                    <div>
                                        <label class="block font-semibold text-gray-700 mb-1">Estimated Service Support Fee</label>
                                        <input type="number" step="0.01" name="est_service_support_fee" placeholder="₱ 0.00" class="w-full border border-gray-300 rounded-lg p-2 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none">
                                    </div>
                                    <div>
                                        <label class="block font-semibold text-gray-700 mb-1">Total Service Fee</label>
                                        <input type="number" step="0.01" name="total_service_fee" placeholder="₱ 0.00" class="w-full border border-gray-300 rounded-lg p-2 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none">
                                    </div>
                                    <div>
                                        <label class="block font-semibold text-gray-700 mb-1">Total Product Fee</label>
                                        <input type="number" step="0.01" name="total_product_fee" placeholder="₱ 0.00" class="w-full border border-gray-300 rounded-lg p-2 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none">
                                    </div>
                                    <div>
                                        <label class="block font-semibold text-gray-700 mb-1">Discount</label>
                                        <input type="number" step="0.01" name="discount" placeholder="₱ 0.00" class="w-full border border-gray-300 rounded-lg p-2 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none">
                                    </div>
                                </div>
                                <div class="pt-2 border-t border-gray-100">
                                    <label class="block font-semibold text-gray-800 mb-1">Total Estimated Engagement Value</label>
                                    <input type="number" step="0.01" name="total_estimated_engagement_value" placeholder="₱ 0.00" class="w-full border border-gray-300 bg-gray-50 rounded-lg p-2.5 text-base font-bold text-blue-600 focus:ring-2 focus:ring-blue-500 focus:outline-none">
                                </div>
                            </div>

                            <!-- Card 11: Pricing Guides & Payment Terms -->
                            <div class="bg-white border border-gray-200 rounded-xl p-5 space-y-4 shadow-sm">
                                <div class="grid grid-cols-2 gap-4 border-b border-gray-100 pb-4">
                                    <div>
                                        <h4 class="font-bold text-gray-700 text-xs uppercase tracking-wider mb-2">Services Pricing Guide</h4>
                                        <div class="text-xs text-gray-400 bg-gray-50 p-3 rounded-lg border border-gray-100">Select a service area to show service prices.</div>
                                        <button type="button" class="mt-3 text-xs border border-gray-300 px-3 py-1.5 rounded-lg hover:bg-gray-50 font-medium text-gray-700">+ Add Other Fee</button>
                                    </div>
                                    <div>
                                        <h4 class="font-bold text-gray-700 text-xs uppercase tracking-wider mb-2">Products Pricing Guide</h4>
                                        <div class="flex justify-between text-xs py-2 px-3 bg-gray-50 rounded-lg border border-gray-100 text-gray-700">
                                            <span>Stock Certificate Printing</span>
                                            <span class="font-bold text-gray-900">₱300.00</span>
                                        </div>
                                    </div>
                                </div>

                                <div>
                                    <h3 class="font-bold text-gray-900 text-base mb-3">Payment Terms</h3>
                                    <div class="grid grid-cols-2 gap-3">
                                        <label class="flex items-center p-3 border border-gray-200 rounded-lg text-xs cursor-pointer hover:bg-gray-50"><input type="radio" name="payment_term" value="full_payment" class="mr-2 text-blue-600 focus:ring-blue-500"> Full Payment Before Service</label>
                                        <label class="flex items-center p-3 border border-gray-200 rounded-lg text-xs cursor-pointer hover:bg-gray-50"><input type="radio" name="payment_term" value="50_50" class="mr-2 text-blue-600 focus:ring-blue-500"> 50% Downpayment / 50% Completion</label>
                                        <label class="flex items-center p-3 border border-gray-200 rounded-lg text-xs cursor-pointer hover:bg-gray-50"><input type="radio" name="payment_term" value="milestone" class="mr-2 text-blue-600 focus:ring-blue-500"> Milestone-Based Payment</label>
                                        <label class="flex items-center p-3 border border-gray-200 rounded-lg text-xs cursor-pointer hover:bg-gray-50"><input type="radio" name="payment_term" value="monthly_retainer" class="mr-2 text-blue-600 focus:ring-blue-500"> Monthly Retainer</label>
                                        <label class="flex items-center p-3 border border-gray-200 rounded-lg text-xs cursor-pointer hover:bg-gray-50 col-span-2"><input type="radio" name="payment_term" value="others" class="mr-2 text-blue-600 focus:ring-blue-500"> Others</label>
                                    </div>
                                </div>
                            </div>

                            <!-- Card 12: Estimated Timeline -->
                            <div class="bg-white border border-gray-200 rounded-xl p-5 space-y-4 shadow-sm">
                                <h3 class="font-bold text-gray-900 text-base">Estimated Timeline</h3>
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label class="block font-semibold text-gray-700 mb-1">Planned Start Date</label>
                                        <input type="date" name="planned_start_date" class="w-full border border-gray-300 rounded-lg p-2 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none">
                                    </div>
                                    <div>
                                        <label class="block font-semibold text-gray-700 mb-1">Estimated Duration (Days)</label>
                                        <input type="number" name="estimated_duration_days" class="w-full border border-gray-300 rounded-lg p-2 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none">
                                    </div>
                                    <div>
                                        <label class="block font-semibold text-gray-700 mb-1">Estimated Completion Date</label>
                                        <input type="date" name="estimated_completion_date" class="w-full border border-gray-300 rounded-lg p-2 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none">
                                    </div>
                                    <div>
                                        <label class="block font-semibold text-gray-700 mb-1">Client Preferred Completion Date</label>
                                        <input type="date" name="client_preferred_completion_date" class="w-full border border-gray-300 rounded-lg p-2 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none">
                                    </div>
                                    <div class="col-span-2">
                                        <label class="block font-semibold text-gray-700 mb-1">Confirmed Delivery Date</label>
                                        <input type="date" name="confirmed_delivery_date" class="w-full border border-gray-300 rounded-lg p-2 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none">
                                    </div>
                                </div>
                                <div>
                                    <label class="block font-semibold text-gray-700 mb-1">Timeline Notes</label>
                                    <textarea name="timeline_notes" rows="2" class="w-full border border-gray-300 rounded-lg p-2 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none"></textarea>
                                </div>
                            </div>

                        </div>

                        <!-- Sticky Footer Actions -->
                        <div class="p-4 border-t border-gray-200 bg-gray-50 flex items-center justify-end gap-3 sticky bottom-0 z-10">
                            <button type="button" @click="open = false" class="px-5 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-100 transition-colors focus:outline-none">
                                Cancel
                            </button>
                            <button
                                type="submit"
                                :disabled="isSubmitting"
                                class="px-5 py-2 text-sm font-semibold text-white bg-blue-600 rounded-lg hover:bg-blue-700 transition-colors shadow-sm focus:outline-none flex items-center gap-2"
                                :class="isSubmitting ? 'opacity-70 cursor-not-allowed' : ''"
                            >
                                <svg x-show="isSubmitting" class="w-4 h-4 animate-spin text-white" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                <span x-text="isSubmitting ? 'Creating Deal...' : 'Create Deal'"></span>
                            </button>
                        </div>

                    </form>
                </div>
            </div>
        </div>
    </div>
</div>