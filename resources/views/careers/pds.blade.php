<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Personal Data Sheet | John Kelly & Company</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
        [x-cloak] { display: none !important; }
    </style>
</head>
<body class="bg-gray-50 text-gray-900">
    <div class="min-h-screen py-12 px-4 sm:px-6 lg:px-8" x-data="publicPDS()">
        <div class="max-w-4xl mx-auto">
            {{-- Header --}}
            <div class="text-center mb-12">
                <img src="{{ asset('images/imaglogo.png') }}" onerror="this.src='{{ asset('images/imag1logo.jpg') }}'" alt="Logo" class="h-20 w-auto mx-auto mb-6 object-contain">
                <h1 class="text-3xl font-black text-gray-900 tracking-tight uppercase">Personal Data Sheet</h1>
                <p class="mt-2 text-gray-600 font-medium">Please accurately fill out the form below for onboarding.</p>
                @if(!empty($jobOffer))
                    <div class="mt-6 max-w-2xl mx-auto bg-blue-50 border border-blue-200 rounded-2xl p-4 text-left">
                        <p class="text-xs font-black text-blue-600 uppercase tracking-widest mb-2">Accepted Job Offer Details</p>
                        <div class="grid sm:grid-cols-3 gap-3 text-sm">
                            <div>
                                <p class="text-gray-500 text-xs font-bold uppercase">Name</p>
                                <p class="font-bold text-gray-900">{{ $jobOffer->name }}</p>
                            </div>
                            <div>
                                <p class="text-gray-500 text-xs font-bold uppercase">Position</p>
                                <p class="font-bold text-gray-900">{{ $jobOffer->position }}</p>
                            </div>
                            <div>
                                <p class="text-gray-500 text-xs font-bold uppercase">Email</p>
                                <p class="font-bold text-gray-900">{{ $jobOffer->candidate_email }}</p>
                            </div>
                        </div>
                        <div class="mt-3 bg-white/70 border border-blue-100 rounded-xl p-3">
                            <p class="text-[11px] text-blue-900 font-semibold">
                                Please manually enter your legal Surname, First Name, and Middle Name below. The job offer name is shown only for reference to avoid incorrect name splitting.
                            </p>
                        </div>
                    </div>
                @endif

            </div>

            <div class="bg-white rounded-3xl shadow-xl border border-gray-100 mb-12">
                <div class="p-8 sm:p-12">
                    <form @submit.prevent="submitPds()" class="flex-1 overflow-y-auto px-5 py-4 space-y-5">

        <div>
            <p class="text-[10px] font-black text-blue-600 uppercase tracking-widest mb-3 pb-1 border-b border-blue-100">I. Personal Information</p>
            <div class="grid grid-cols-2 gap-3 mb-3">
                <div><label class="block text-xs font-semibold text-gray-600 mb-1">Surname</label><input type="text" x-model="pdsForm.surname" required @input="pdsForm.fullName = pdsForm.surname + ', ' + pdsForm.firstName" class="w-full text-sm px-3 py-1.5 border border-gray-300 rounded focus:ring-1 focus:ring-blue-500 outline-none"></div>
                <div><label class="block text-xs font-semibold text-gray-600 mb-1">First Name</label><input type="text" x-model="pdsForm.firstName" required @input="pdsForm.fullName = pdsForm.surname + ', ' + pdsForm.firstName" class="w-full text-sm px-3 py-1.5 border border-gray-300 rounded focus:ring-1 focus:ring-blue-500 outline-none"></div>
                <div><label class="block text-xs font-semibold text-gray-600 mb-1">Middle Name</label><input type="text" x-model="pdsForm.middleName" class="w-full text-sm px-3 py-1.5 border border-gray-300 rounded focus:ring-1 focus:ring-blue-500 outline-none"></div>
                <div><label class="block text-xs font-semibold text-gray-600 mb-1">Name Extension</label><input type="text" x-model="pdsForm.nameExt" placeholder="Jr., Sr., III, etc." class="w-full text-sm px-3 py-1.5 border border-gray-300 rounded focus:ring-1 focus:ring-blue-500 outline-none"></div>
            </div>
            <div class="grid grid-cols-3 gap-3 mb-3">
                <div><label class="block text-xs font-semibold text-gray-600 mb-1">Date of Birth</label><input type="date" x-model="pdsForm.dob" class="w-full text-sm px-3 py-1.5 border border-gray-300 rounded focus:ring-1 focus:ring-blue-500 outline-none bg-white"></div>
                <div><label class="block text-xs font-semibold text-gray-600 mb-1">Place of Birth</label><input type="text" x-model="pdsForm.pob" class="w-full text-sm px-3 py-1.5 border border-gray-300 rounded focus:ring-1 focus:ring-blue-500 outline-none"></div>
                <div><label class="block text-xs font-semibold text-gray-600 mb-1">Citizenship</label><input type="text" x-model="pdsForm.citizenship" class="w-full text-sm px-3 py-1.5 border border-gray-300 rounded focus:ring-1 focus:ring-blue-500 outline-none"></div>
            </div>
            <div class="grid grid-cols-2 gap-3 mb-3">
                <div><label class="block text-xs font-semibold text-gray-600 mb-1">Sex</label><select x-model="pdsForm.sex" class="w-full text-sm px-3 py-1.5 border border-gray-300 rounded focus:ring-1 focus:ring-blue-500 outline-none bg-white"><option value="">Select</option><option>Male</option><option>Female</option></select></div>
                <div><label class="block text-xs font-semibold text-gray-600 mb-1">Civil Status</label><select x-model="pdsForm.civilStatus" class="w-full text-sm px-3 py-1.5 border border-gray-300 rounded focus:ring-1 focus:ring-blue-500 outline-none bg-white"><option value="">Select</option><option>Single</option><option>Married</option><option>Widowed</option><option>Legally Separated</option></select></div>
                <div><label class="block text-xs font-semibold text-gray-600 mb-1">Height (meters)</label><input type="text" x-model="pdsForm.height" class="w-full text-sm px-3 py-1.5 border border-gray-300 rounded focus:ring-1 focus:ring-blue-500 outline-none"></div>
                <div><label class="block text-xs font-semibold text-gray-600 mb-1">Weight (kilograms)</label><input type="text" x-model="pdsForm.weight" class="w-full text-sm px-3 py-1.5 border border-gray-300 rounded focus:ring-1 focus:ring-blue-500 outline-none"></div>
                <div class="col-span-2"><label class="block text-xs font-semibold text-gray-600 mb-1">Blood Type</label><input type="text" x-model="pdsForm.bloodType" class="w-full text-sm px-3 py-1.5 border border-gray-300 rounded focus:ring-1 focus:ring-blue-500 outline-none"></div>
            </div>
            <div class="grid grid-cols-2 gap-3 mb-3">
                <div><label class="block text-xs font-semibold text-gray-600 mb-1">SSS Number</label><input type="text" x-model="pdsForm.sss" class="w-full text-sm px-3 py-1.5 border border-gray-300 rounded focus:ring-1 focus:ring-blue-500 outline-none"></div>
                <div><label class="block text-xs font-semibold text-gray-600 mb-1">PhilHealth Number</label><input type="text" x-model="pdsForm.philhealth" class="w-full text-sm px-3 py-1.5 border border-gray-300 rounded focus:ring-1 focus:ring-blue-500 outline-none"></div>
                <div><label class="block text-xs font-semibold text-gray-600 mb-1">Pag-IBIG ID Number</label><input type="text" x-model="pdsForm.pagibig" class="w-full text-sm px-3 py-1.5 border border-gray-300 rounded focus:ring-1 focus:ring-blue-500 outline-none"></div>
                <div><label class="block text-xs font-semibold text-gray-600 mb-1">TIN Number</label><input type="text" x-model="pdsForm.tin" class="w-full text-sm px-3 py-1.5 border border-gray-300 rounded focus:ring-1 focus:ring-blue-500 outline-none"></div>
            </div>
            <div class="grid grid-cols-2 gap-3 mb-3">
                <div class="col-span-2"><label class="block text-xs font-bold text-gray-600">Residential Address</label></div>
                <div><label class="block text-xs font-semibold text-gray-600 mb-1">House / Block / Lot No.</label><input type="text" x-model="pdsForm.resHouse" class="w-full text-sm px-3 py-1.5 border border-gray-300 rounded focus:ring-1 focus:ring-blue-500 outline-none"></div>
                <div><label class="block text-xs font-semibold text-gray-600 mb-1">Street</label><input type="text" x-model="pdsForm.resStreet" class="w-full text-sm px-3 py-1.5 border border-gray-300 rounded focus:ring-1 focus:ring-blue-500 outline-none"></div>
                <div><label class="block text-xs font-semibold text-gray-600 mb-1">Subdivision / Village</label><input type="text" x-model="pdsForm.resSubdiv" class="w-full text-sm px-3 py-1.5 border border-gray-300 rounded focus:ring-1 focus:ring-blue-500 outline-none"></div>
                <div><label class="block text-xs font-semibold text-gray-600 mb-1">Barangay</label><input type="text" x-model="pdsForm.resBrgy" class="w-full text-sm px-3 py-1.5 border border-gray-300 rounded focus:ring-1 focus:ring-blue-500 outline-none"></div>
                <div><label class="block text-xs font-semibold text-gray-600 mb-1">City / Municipality</label><input type="text" x-model="pdsForm.resCity" class="w-full text-sm px-3 py-1.5 border border-gray-300 rounded focus:ring-1 focus:ring-blue-500 outline-none"></div>
                <div><label class="block text-xs font-semibold text-gray-600 mb-1">Province</label><input type="text" x-model="pdsForm.resProv" class="w-full text-sm px-3 py-1.5 border border-gray-300 rounded focus:ring-1 focus:ring-blue-500 outline-none"></div>
                <div class="col-span-2"><label class="block text-xs font-semibold text-gray-600 mb-1">ZIP Code</label><input type="text" x-model="pdsForm.resZip" class="w-full text-sm px-3 py-1.5 border border-gray-300 rounded focus:ring-1 focus:ring-blue-500 outline-none"></div>
            </div>
            <div class="grid grid-cols-2 gap-3 mb-3">
                <div class="col-span-2 flex items-center justify-between">
                    <label class="block text-xs font-bold text-gray-600">Permanent Address</label>
                    <label class="flex items-center gap-1 cursor-pointer"><input type="checkbox" x-model="pdsForm.permSameAsRes" @change="if(pdsForm.permSameAsRes){ pdsForm.permHouse=pdsForm.resHouse; pdsForm.permStreet=pdsForm.resStreet; pdsForm.permSubdiv=pdsForm.resSubdiv; pdsForm.permBrgy=pdsForm.resBrgy; pdsForm.permCity=pdsForm.resCity; pdsForm.permProv=pdsForm.resProv; pdsForm.permZip=pdsForm.resZip; }"><span class="text-[10px] text-gray-500">Same as residential</span></label>
                </div>
                <div><label class="block text-xs font-semibold text-gray-600 mb-1">House / Block / Lot No.</label><input type="text" x-model="pdsForm.permHouse" class="w-full text-sm px-3 py-1.5 border border-gray-300 rounded focus:ring-1 focus:ring-blue-500 outline-none"></div>
                <div><label class="block text-xs font-semibold text-gray-600 mb-1">Street</label><input type="text" x-model="pdsForm.permStreet" class="w-full text-sm px-3 py-1.5 border border-gray-300 rounded focus:ring-1 focus:ring-blue-500 outline-none"></div>
                <div><label class="block text-xs font-semibold text-gray-600 mb-1">Subdivision / Village</label><input type="text" x-model="pdsForm.permSubdiv" class="w-full text-sm px-3 py-1.5 border border-gray-300 rounded focus:ring-1 focus:ring-blue-500 outline-none"></div>
                <div><label class="block text-xs font-semibold text-gray-600 mb-1">Barangay</label><input type="text" x-model="pdsForm.permBrgy" class="w-full text-sm px-3 py-1.5 border border-gray-300 rounded focus:ring-1 focus:ring-blue-500 outline-none"></div>
                <div><label class="block text-xs font-semibold text-gray-600 mb-1">City / Municipality</label><input type="text" x-model="pdsForm.permCity" class="w-full text-sm px-3 py-1.5 border border-gray-300 rounded focus:ring-1 focus:ring-blue-500 outline-none"></div>
                <div><label class="block text-xs font-semibold text-gray-600 mb-1">Province</label><input type="text" x-model="pdsForm.permProv" class="w-full text-sm px-3 py-1.5 border border-gray-300 rounded focus:ring-1 focus:ring-blue-500 outline-none"></div>
                <div class="col-span-2"><label class="block text-xs font-semibold text-gray-600 mb-1">ZIP Code</label><input type="text" x-model="pdsForm.permZip" class="w-full text-sm px-3 py-1.5 border border-gray-300 rounded focus:ring-1 focus:ring-blue-500 outline-none"></div>
            </div>
            <div class="grid grid-cols-2 gap-3 mb-3">
                <div class="col-span-2"><label class="block text-xs font-bold text-gray-600">Contact Information</label></div>
                <div><label class="block text-xs font-semibold text-gray-600 mb-1">Telephone Number</label><input type="text" x-model="pdsForm.telNo" class="w-full text-sm px-3 py-1.5 border border-gray-300 rounded focus:ring-1 focus:ring-blue-500 outline-none"></div>
                <div><label class="block text-xs font-semibold text-gray-600 mb-1">Mobile Number</label><input type="text" x-model="pdsForm.mobileNo" @input="pdsForm.phone = pdsForm.mobileNo" class="w-full text-sm px-3 py-1.5 border border-gray-300 rounded focus:ring-1 focus:ring-blue-500 outline-none"></div>
                <div class="col-span-2"><label class="block text-xs font-semibold text-gray-600 mb-1">Email</label><input type="email" x-model="pdsForm.email" class="w-full text-sm px-3 py-1.5 border border-gray-300 rounded focus:ring-1 focus:ring-blue-500 outline-none"></div>
            </div>
        </div>

        <div>
            <p class="text-[10px] font-black text-blue-600 uppercase tracking-widest mb-3 pb-1 border-b border-blue-100">II. Family Background</p>
            <div class="grid grid-cols-2 gap-3 mb-3">
                <div><label class="block text-xs font-semibold text-gray-600 mb-1">Spouse's Surname</label><input type="text" x-model="pdsForm.spouseSurname" class="w-full text-sm px-3 py-1.5 border border-gray-300 rounded"></div>
                <div><label class="block text-xs font-semibold text-gray-600 mb-1">Spouse's First Name</label><input type="text" x-model="pdsForm.spouseFirstName" class="w-full text-sm px-3 py-1.5 border border-gray-300 rounded"></div>
                <div><label class="block text-xs font-semibold text-gray-600 mb-1">Spouse's Middle Name</label><input type="text" x-model="pdsForm.spouseMiddleName" class="w-full text-sm px-3 py-1.5 border border-gray-300 rounded"></div>
                <div><label class="block text-xs font-semibold text-gray-600 mb-1">Spouse's Name Ext.</label><input type="text" x-model="pdsForm.spouseNameExt" class="w-full text-sm px-3 py-1.5 border border-gray-300 rounded"></div>
                <div><label class="block text-xs font-semibold text-gray-600 mb-1">Spouse's Occupation</label><input type="text" x-model="pdsForm.spouseOccupation" class="w-full text-sm px-3 py-1.5 border border-gray-300 rounded"></div>
                <div><label class="block text-xs font-semibold text-gray-600 mb-1">Employer / Business</label><input type="text" x-model="pdsForm.spouseEmployer" class="w-full text-sm px-3 py-1.5 border border-gray-300 rounded"></div>
                <div><label class="block text-xs font-semibold text-gray-600 mb-1">Business Address</label><input type="text" x-model="pdsForm.spouseBusinessAddress" class="w-full text-sm px-3 py-1.5 border border-gray-300 rounded"></div>
                <div><label class="block text-xs font-semibold text-gray-600 mb-1">Telephone Number</label><input type="text" x-model="pdsForm.spouseTelNo" class="w-full text-sm px-3 py-1.5 border border-gray-300 rounded"></div>
            </div>
            <div class="mb-3">
                <label class="block text-xs font-bold text-gray-600 mb-2">Children</label>
                <template x-for="(child, idx) in pdsForm.children" :key="idx">
                    <div class="grid grid-cols-3 gap-2 mb-2">
                        <input type="text" x-model="child.name" placeholder="Full Name" class="w-full text-sm px-3 py-1.5 border border-gray-300 rounded">
                        <select x-model="child.gender" class="w-full text-sm px-3 py-1.5 border border-gray-300 rounded bg-white"><option value="">Gender</option><option>Male</option><option>Female</option></select>
                        <input type="date" x-model="child.dob" class="w-full text-sm px-3 py-1.5 border border-gray-300 rounded bg-white">
                    </div>
                </template>
            </div>
            <div class="grid grid-cols-2 gap-3 mb-3">
                <div class="col-span-2"><label class="block text-xs font-bold text-gray-600">Father's Name</label></div>
                <div><label class="block text-xs font-semibold text-gray-600 mb-1">Surname</label><input type="text" x-model="pdsForm.fatherSurname" class="w-full text-sm px-3 py-1.5 border border-gray-300 rounded"></div>
                <div><label class="block text-xs font-semibold text-gray-600 mb-1">First Name</label><input type="text" x-model="pdsForm.fatherFirstName" class="w-full text-sm px-3 py-1.5 border border-gray-300 rounded"></div>
                <div><label class="block text-xs font-semibold text-gray-600 mb-1">Middle Name</label><input type="text" x-model="pdsForm.fatherMiddleName" class="w-full text-sm px-3 py-1.5 border border-gray-300 rounded"></div>
                <div><label class="block text-xs font-semibold text-gray-600 mb-1">Name Extension</label><input type="text" x-model="pdsForm.fatherNameExt" class="w-full text-sm px-3 py-1.5 border border-gray-300 rounded"></div>
                
                <div class="col-span-2"><label class="block text-xs font-bold text-gray-600 mt-2">Mother's Maiden Name</label></div>
                <div><label class="block text-xs font-semibold text-gray-600 mb-1">Surname</label><input type="text" x-model="pdsForm.motherMaidenSurname" class="w-full text-sm px-3 py-1.5 border border-gray-300 rounded"></div>
                <div><label class="block text-xs font-semibold text-gray-600 mb-1">First Name</label><input type="text" x-model="pdsForm.motherFirstName" class="w-full text-sm px-3 py-1.5 border border-gray-300 rounded"></div>
                <div class="col-span-2"><label class="block text-xs font-semibold text-gray-600 mb-1">Middle Name</label><input type="text" x-model="pdsForm.motherMiddleName" class="w-full text-sm px-3 py-1.5 border border-gray-300 rounded"></div>
            </div>
        </div>

        <div>
            <p class="text-[10px] font-black text-blue-600 uppercase tracking-widest mb-3 pb-1 border-b border-blue-100">III. Educational Background</p>
            <div class="mb-3">
                <label class="block text-xs font-bold text-gray-600 mb-2">A. Formal Education</label>
                <div class="grid grid-cols-4 gap-2 mb-2">
                    <div class="text-xs pt-2 font-bold text-gray-500">Elementary</div>
                    <input type="text" x-model="pdsForm.educElemSchool" placeholder="School" class="w-full text-sm px-2 py-1.5 border border-gray-300 rounded">
                    <input type="text" x-model="pdsForm.educElemFrom" placeholder="From" class="w-full text-sm px-2 py-1.5 border border-gray-300 rounded">
                    <input type="text" x-model="pdsForm.educElemTo" placeholder="To" class="w-full text-sm px-2 py-1.5 border border-gray-300 rounded">
                </div>
                <div class="grid grid-cols-4 gap-2 mb-2">
                    <div class="text-xs pt-2 font-bold text-gray-500">Secondary</div>
                    <input type="text" x-model="pdsForm.educSecSchool" placeholder="School" class="w-full text-sm px-2 py-1.5 border border-gray-300 rounded">
                    <input type="text" x-model="pdsForm.educSecFrom" placeholder="From" class="w-full text-sm px-2 py-1.5 border border-gray-300 rounded">
                    <input type="text" x-model="pdsForm.educSecTo" placeholder="To" class="w-full text-sm px-2 py-1.5 border border-gray-300 rounded">
                </div>
                <div class="grid grid-cols-4 gap-2 mb-2">
                    <div class="text-xs pt-2 font-bold text-gray-500">College</div>
                    <input type="text" x-model="pdsForm.educCollSchool" placeholder="School" class="w-full text-sm px-2 py-1.5 border border-gray-300 rounded">
                    <input type="text" x-model="pdsForm.educCollDegree" placeholder="Degree" class="w-full text-sm px-2 py-1.5 border border-gray-300 rounded">
                    <div class="flex gap-1">
                        <input type="text" x-model="pdsForm.educCollFrom" placeholder="Fr" class="w-1/2 text-sm px-1 py-1.5 border border-gray-300 rounded">
                        <input type="text" x-model="pdsForm.educCollTo" placeholder="To" class="w-1/2 text-sm px-1 py-1.5 border border-gray-300 rounded">
                    </div>
                </div>
                <div class="grid grid-cols-4 gap-2 mb-2">
                    <div class="text-xs pt-2 font-bold text-gray-500">Masters</div>
                    <input type="text" x-model="pdsForm.educMastSchool" placeholder="School" class="w-full text-sm px-2 py-1.5 border border-gray-300 rounded">
                    <input type="text" x-model="pdsForm.educMastDegree" placeholder="Degree" class="w-full text-sm px-2 py-1.5 border border-gray-300 rounded">
                    <div class="flex gap-1">
                        <input type="text" x-model="pdsForm.educMastFrom" placeholder="Fr" class="w-1/2 text-sm px-1 py-1.5 border border-gray-300 rounded">
                        <input type="text" x-model="pdsForm.educMastTo" placeholder="To" class="w-1/2 text-sm px-1 py-1.5 border border-gray-300 rounded">
                    </div>
                </div>
                <div class="grid grid-cols-4 gap-2 mb-2">
                    <div class="text-xs pt-2 font-bold text-gray-500">Doctorate</div>
                    <input type="text" x-model="pdsForm.educDoctSchool" placeholder="School" class="w-full text-sm px-2 py-1.5 border border-gray-300 rounded">
                    <input type="text" x-model="pdsForm.educDoctDegree" placeholder="Degree" class="w-full text-sm px-2 py-1.5 border border-gray-300 rounded">
                    <div class="flex gap-1">
                        <input type="text" x-model="pdsForm.educDoctFrom" placeholder="Fr" class="w-1/2 text-sm px-1 py-1.5 border border-gray-300 rounded">
                        <input type="text" x-model="pdsForm.educDoctTo" placeholder="To" class="w-1/2 text-sm px-1 py-1.5 border border-gray-300 rounded">
                    </div>
                </div>
            </div>
            <div class="mb-3 mt-4">
                <label class="block text-xs font-bold text-gray-600 mb-2">B. Learning & Development</label>
                <template x-for="(l, idx) in pdsForm.lnd" :key="idx">
                    <div class="grid grid-cols-4 gap-2 mb-2">
                        <input type="text" x-model="l.title" placeholder="Title" class="w-full text-sm px-2 py-1.5 border border-gray-300 rounded">
                        <input type="text" x-model="l.conductedBy" placeholder="Conducted By" class="w-full text-sm px-2 py-1.5 border border-gray-300 rounded">
                        <input type="text" x-model="l.date" placeholder="Date" class="w-full text-sm px-2 py-1.5 border border-gray-300 rounded bg-white">
                        <select x-model="l.cert" class="w-full text-sm px-2 py-1.5 border border-gray-300 rounded bg-white"><option value="">Cert?</option><option>Yes</option><option>No</option></select>
                    </div>
                </template>
            </div>
        </div>

        <div>
            <p class="text-[10px] font-black text-blue-600 uppercase tracking-widest mb-3 pb-1 border-b border-blue-100">IV. Declaration</p>
            <label class="flex items-start gap-2 cursor-pointer mt-2 border rounded p-3 bg-gray-50 border-gray-200">
                <input type="checkbox" x-model="pdsForm.consent" required class="mt-1 w-4 h-4 text-blue-600 rounded cursor-pointer">
                <span class="text-xs text-gray-700 leading-tight">I certify that the information provided herein is true, complete, and accurate. I understand and consent to the data privacy policy. *</span>
            </label>
        </div>

        <div class="flex justify-end gap-3 pt-2 pb-1">
            
            <button type="submit" :disabled="isSubmitting"
                class="group relative px-16 py-5 bg-blue-600 text-white rounded-full font-black uppercase tracking-[0.25em] text-sm overflow-hidden transition-all hover:bg-blue-700 active:scale-95 disabled:opacity-50 mx-auto block mt-8 mb-4">
                <span class="relative z-10" x-text="isSubmitting ? 'Submitting...' : 'Submit Data Sheet'"></span>
            </button>
        </div>
    </form>
                </div>
            </div>

            {{-- Success Message --}}
            <div x-show="isSuccess" x-transition.opacity class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900/60 backdrop-blur-xl p-4" style="display:none;">
                <div class="bg-white rounded-3xl p-16 max-w-xl w-full text-center shadow-2xl">
                    <div class="w-24 h-24 bg-green-50 rounded-full flex items-center justify-center mx-auto mb-8 border border-green-100">
                        <svg class="w-12 h-12 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                    </div>
                    <h2 class="text-3xl font-black text-gray-900 uppercase tracking-tighter mb-4">Submission Successful!</h2>
                    <p class="text-gray-500 font-medium text-lg mb-8 leading-relaxed">Thank you. Your Personal Data Sheet has been submitted securely to the Human Capital department.</p>
                    <button @click="resetForm()" class="px-10 py-3 bg-gray-900 text-white rounded-full font-black uppercase tracking-widest text-xs hover:scale-105 active:scale-95 transition-all">Close</button>
                </div>
            </div>
            
            {{-- Footer --}}
            <div class="text-center text-gray-300 text-[11px] font-black uppercase tracking-[0.4em]">
                © {{ date('Y') }} John Kelly & Company
            </div>
        </div>
    </div>

    <script>
    function publicPDS() {
        return {
            pdsForm: {
                jobOfferToken: @json($token ?? null),
                fullName: @json(optional($jobOffer ?? null)->name ?? ''),
                position: @json(optional($jobOffer ?? null)->position ?? ''),
                email: @json(optional($jobOffer ?? null)->candidate_email ?? ''),
                phone: '',
                surname: '',
                firstName: '',
                middleName: '', nameExt: '',
                dob: '', pob: '', citizenship: '',
                sex: '', civilStatus: '', height: '', weight: '', bloodType: '',
                sss: '', philhealth: '', pagibig: '', tin: '',
                resHouse: '', resStreet: '', resSubdiv: '', resBrgy: '', resCity: '', resProv: '', resZip: '',
                permSameAsRes: false,
                permHouse: '', permStreet: '', permSubdiv: '', permBrgy: '', permCity: '', permProv: '', permZip: '',
                telNo: '', mobileNo: '',
                spouseSurname: '', spouseFirstName: '', spouseMiddleName: '', spouseNameExt: '',
                spouseOccupation: '', spouseEmployer: '', spouseBusinessAddress: '', spouseTelNo: '',
                children: [ {name: '', gender: '', dob: ''}, {name: '', gender: '', dob: ''}, {name: '', gender: '', dob: ''} ],
                fatherSurname: '', fatherFirstName: '', fatherMiddleName: '', fatherNameExt: '',
                motherMaidenSurname: '', motherFirstName: '', motherMiddleName: '',
                educElemSchool: '', educElemDegree: '', educElemFrom: '', educElemTo: '',
                educSecSchool: '', educSecDegree: '', educSecFrom: '', educSecTo: '',
                educCollSchool: '', educCollDegree: '', educCollFrom: '', educCollTo: '',
                educMastSchool: '', educMastDegree: '', educMastFrom: '', educMastTo: '',
                educDoctSchool: '', educDoctDegree: '', educDoctFrom: '', educDoctTo: '',
                lnd: [ {title: '', conductedBy: '', date: '', cert: ''}, {title: '', conductedBy: '', date: '', cert: ''}, {title: '', conductedBy: '', date: '', cert: ''} ],
                consent: false,
                signaturePreview: '', signatureName: '', submittedDate: ''
            },
            isSubmitting: false,
            isSuccess: false,
            acceptedName: @json(optional($jobOffer ?? null)->name ?? ''),

            init() {
                // Do not auto-split the job offer name into surname/first/middle name.
                // The applicant must manually enter their legal name to avoid wrong middle-name parsing.
            },
            submitPds() {
                const first = String(this.pdsForm.firstName || '').trim();
                const middle = String(this.pdsForm.middleName || '').trim();
                const surname = String(this.pdsForm.surname || '').trim();

                if (surname || first || middle) {
                    const givenNames = [first, middle].filter(Boolean).join(' ');
                    this.pdsForm.fullName = surname ? `${surname}, ${givenNames}`.trim() : givenNames;
                }

                if (!this.pdsForm.phone) {
                    this.pdsForm.phone = this.pdsForm.mobileNo || '';
                }

                this.isSubmitting = true;
                
                fetch('{{ route("careers.pds.submit") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify(this.pdsForm)
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        this.isSuccess = true;
                    } else {
                        alert('Submission failed. Please check your inputs and try again.');
                    }
                })
                .catch(err => {
                    console.error(err);
                    alert('An error occurred. Please try again later.');
                })
                .finally(() => {
                    this.isSubmitting = false;
                });
            },
            resetForm() {
                this.isSuccess = false;
                window.location.reload();
            }
        }
    }
    </script>
</body>
</html>
