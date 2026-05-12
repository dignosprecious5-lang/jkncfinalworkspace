<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Careers | John Kelly & Company</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
        [x-cloak] { display: none !important; }
        input:-webkit-autofill,
        input:-webkit-autofill:hover, 
        input:-webkit-autofill:focus {
            -webkit-box-shadow: 0 0 0px 1000px white inset;
            transition: background-color 5000s ease-in-out 0s;
        }
    </style>
</head>
<body class="bg-gray-50 text-gray-900">
    <div class="min-h-screen py-12 px-4 sm:px-6 lg:px-8" x-data="publicApplication({{ $jobPostings->toJson() }})">
        <div class="max-w-4xl mx-auto">
            {{-- Header --}}
            <div class="text-center mb-12">
                <img src="{{ asset('images/imaglogo.png') }}" onerror="this.src='{{ asset('images/imag1logo.jpg') }}'" alt="Logo" class="h-20 w-auto mx-auto mb-6 object-contain">
                <h1 class="text-3xl font-black text-gray-900 tracking-tight uppercase">Join Our Team</h1>
                <p class="mt-2 text-gray-600 font-medium">Please fill out the form below to submit your application.</p>
            </div>

            <div class="bg-white rounded-[2.5rem] shadow-2xl overflow-hidden border border-gray-100">
                <div class="p-8 sm:p-12">
                    <form @submit.prevent="submitForm" class="space-y-10">
                        @csrf
                        {{-- Personal Information Section --}}
                        <div class="space-y-8">
                            <div class="flex items-center gap-4">
                                <div class="h-10 w-1.5 bg-blue-600 rounded-full"></div>
                                <h3 class="text-xl font-black text-gray-900 uppercase tracking-widest">Personal Information</h3>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-8">
                                {{-- Photo Upload --}}
                                <div class="sm:col-span-2 flex flex-col items-center">
                                    <label class="block text-xs font-black text-gray-400 uppercase tracking-[0.2em] mb-4">2x2 Applicant Photo</label>
                                    <div class="relative group">
                                        <div @dragover.prevent @drop.prevent="form.photo = $event.dataTransfer.files[0]"
                                            class="w-44 h-44 border-4 border-gray-100 rounded-[2rem] overflow-hidden bg-gray-50 flex items-center justify-center relative transition-all group-hover:border-blue-100 group-hover:shadow-xl group-hover:shadow-blue-50/50">
                                            <template x-if="!form.photo">
                                                <div class="flex flex-col items-center justify-center text-gray-300">
                                                    <svg class="w-12 h-12 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 4v16m8-8H4"/></svg>
                                                    <p class="text-[10px] font-black uppercase tracking-tighter">Upload Photo</p>
                                                </div>
                                            </template>
                                            <template x-if="form.photo">
                                                <img :src="URL.createObjectURL(form.photo)" class="w-full h-full object-cover">
                                            </template>
                                            <input type="file" class="absolute inset-0 opacity-0 cursor-pointer" accept="image/*" @change="form.photo = $event.target.files[0]" />
                                        </div>
                                        <template x-if="form.photo">
                                            <button type="button" @click="form.photo = null" class="absolute -top-2 -right-2 bg-red-500 text-white p-1.5 rounded-full shadow-lg hover:bg-red-600 transition">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                            </button>
                                        </template>
                                    </div>
                                </div>

                                <div class="sm:col-span-1">
                                    <label class="block text-[11px] font-black text-gray-400 uppercase tracking-widest mb-2 px-1">Full Name</label>
                                    <input type="text" x-model="form.fullName" required placeholder="Juana Dela Cruz"
                                        class="w-full px-5 py-4 border border-gray-200 rounded-2xl focus:ring-4 focus:ring-blue-50 focus:border-blue-500 outline-none transition-all bg-gray-50/30 text-sm font-medium">
                                </div>

                                <div class="sm:col-span-1">
                                    <label class="block text-[11px] font-black text-gray-400 uppercase tracking-widest mb-2 px-1">Position Applied</label>
                                    <select x-model="form.jobPostingId" @change="onJobPostingChange()" required
                                        class="w-full px-5 py-4 border border-gray-200 rounded-2xl focus:ring-4 focus:ring-blue-50 focus:border-blue-500 outline-none transition-all bg-gray-50/30 text-sm font-medium appearance-none">
                                        <option value="">Select a posted job</option>
                                        @forelse($jobPostings as $job)
                                            <option value="{{ $job->id }}">
                                                {{ $job->job_id }} - {{ $job->position }}
                                            </option>
                                        @empty
                                            <option value="" disabled>No posted jobs available</option>
                                        @endforelse
                                    </select>
                                    <input type="hidden" x-model="form.positionApplied">

                                    <div x-show="selectedJob" x-cloak class="mt-3 text-[11px] text-gray-600 bg-gray-50 border border-gray-100 rounded-2xl p-4 space-y-1">
                                        <p><strong>Position:</strong> <span x-text="selectedJob?.position || '—'"></span></p>
                                        <p><strong>Department / Unit:</strong> <span x-text="selectedJob?.department_unit || '—'"></span></p>
                                        <p><strong>Location:</strong> <span x-text="selectedJob?.location || selectedJob?.office_branch_site || '—'"></span></p>
                                        <p><strong>Employment Type:</strong> <span x-text="selectedJob?.employment_type || '—'"></span></p>
                                    </div>
                                </div>

                                <div class="sm:col-span-1">
                                    <label class="block text-[11px] font-black text-gray-400 uppercase tracking-widest mb-2 px-1">Email Address</label>
                                    <input type="email" x-model="form.email" required placeholder="juana@example.com"
                                        class="w-full px-5 py-4 border border-gray-200 rounded-2xl focus:ring-4 focus:ring-blue-50 focus:border-blue-500 outline-none transition-all bg-gray-50/30 text-sm font-medium">
                                </div>

                                <div class="sm:col-span-1">
                                    <label class="block text-[11px] font-black text-gray-400 uppercase tracking-widest mb-2 px-1">Phone Number</label>
                                    <input type="text" x-model="form.phone" required placeholder="+63 9xx xxx xxxx"
                                        class="w-full px-5 py-4 border border-gray-200 rounded-2xl focus:ring-4 focus:ring-blue-50 focus:border-blue-500 outline-none transition-all bg-gray-50/30 text-sm font-medium">
                                </div>
                            </div>
                        </div>

                        {{-- Documents Section --}}
                        <div class="space-y-8 pt-4">
                            <div class="flex items-center gap-4">
                                <div class="h-10 w-1.5 bg-blue-600 rounded-full"></div>
                                <h3 class="text-xl font-black text-gray-900 uppercase tracking-widest">Documents</h3>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-8">
                                <div>
                                    <label class="block text-[11px] font-black text-gray-400 uppercase tracking-widest mb-3 px-1">Resume / CV</label>
                                    <label @dragover.prevent @drop.prevent="form.cv = $event.dataTransfer.files[0]"
                                        class="flex flex-col items-center justify-center w-full h-44 border-2 border-gray-200 border-dashed rounded-[2rem] cursor-pointer bg-gray-50/50 hover:bg-white hover:border-blue-400 hover:shadow-xl hover:shadow-blue-50 transition-all group relative overflow-hidden">
                                        <div class="text-center px-6 transition group-hover:scale-105 duration-300">
                                            <div class="w-12 h-12 bg-white rounded-2xl shadow-sm flex items-center justify-center mx-auto mb-3 border border-gray-100 group-hover:text-blue-500 transition">
                                                <svg x-show="!form.cv" class="w-6 h-6 " fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/></svg>
                                                <svg x-show="form.cv" class="w-6 h-6 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                            </div>
                                            <p class="text-[10px] font-black text-gray-500 uppercase tracking-wider" x-text="form.cv ? form.cv.name : 'Choose File'"></p>
                                        </div>
                                        <input type="file" class="hidden" @change="form.cv = $event.target.files[0]" />
                                    </label>
                                </div>
                                <div>
                                    <label class="block text-[11px] font-black text-gray-400 uppercase tracking-widest mb-3 px-1">Cover Letter (File)</label>
                                    <label @dragover.prevent @drop.prevent="form.coverLetterFile = $event.dataTransfer.files[0]"
                                        class="flex flex-col items-center justify-center w-full h-44 border-2 border-gray-200 border-dashed rounded-[2rem] cursor-pointer bg-gray-50/50 hover:bg-white hover:border-indigo-400 hover:shadow-xl hover:shadow-indigo-50 transition-all group relative overflow-hidden">
                                        <div class="text-center px-6 transition group-hover:scale-105 duration-300">
                                            <div class="w-12 h-12 bg-white rounded-2xl shadow-sm flex items-center justify-center mx-auto mb-3 border border-gray-100 group-hover:text-indigo-500 transition">
                                                <svg x-show="!form.coverLetterFile" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                                <svg x-show="form.coverLetterFile" class="w-6 h-6 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                            </div>
                                            <p class="text-[10px] font-black text-gray-500 uppercase tracking-wider" x-text="form.coverLetterFile ? form.coverLetterFile.name : 'Choose File'"></p>
                                        </div>
                                        <input type="file" class="hidden" @change="form.coverLetterFile = $event.target.files[0]" />
                                    </label>
                                </div>
                            </div>

                            <div>
                                <label class="block text-[11px] font-black text-gray-400 uppercase tracking-widest mb-2 px-1">Or Paste Cover Letter Text</label>
                                <textarea x-model="form.coverLetter" rows="8" placeholder="Tell us about yourself and why you're a great fit..."
                                    class="w-full px-6 py-5 border border-gray-200 rounded-[2rem] focus:ring-4 focus:ring-blue-50 focus:border-blue-500 outline-none transition-all resize-none bg-gray-50/30 text-sm font-medium italic"></textarea>
                            </div>
                        </div>

                        {{-- Submit Button --}}
                        <div class="pt-8 text-center pb-4">
                            <button type="submit" :disabled="isSubmitting"
                                class="group relative px-16 py-5 bg-gray-900 border border-gray-900 text-white rounded-full font-black uppercase tracking-[0.25em] text-sm overflow-hidden transition-all hover:pr-20 active:scale-95 disabled:opacity-50">
                                <span class="relative z-10" x-text="isSubmitting ? 'Sending...' : 'Submit Application'"></span>
                                <svg x-show="!isSubmitting" class="w-5 h-5 absolute right-8 top-1/2 -translate-y-1/2 opacity-0 group-hover:opacity-100 group-hover:right-10 transition-all" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                            </button>
                        </div>
                    </form>

                    {{-- Success Message --}}
                    <div x-show="isSuccess" x-transition.opacity class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900/60 backdrop-blur-xl p-4" style="display:none;">
                        <div class="bg-white rounded-[4rem] p-16 max-w-xl w-full text-center shadow-[0_50px_100px_-20px_rgba(0,0,0,0.25)] border border-gray-100 transform transition-all scale-105">
                            <div class="w-32 h-32 bg-green-50 rounded-full flex items-center justify-center mx-auto mb-10 border border-green-100">
                                <svg class="w-16 h-16 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                            </div>
                            <h2 class="text-4xl font-black text-gray-900 uppercase tracking-tighter mb-6">Application Successful!</h2>
                            <p class="text-gray-500 font-medium text-lg mb-12 leading-relaxed">Thank you for applying. Your profile is now being reviewed by our Talent Acquisition team. We will reach out via email or phone soon.</p>
                            <button @click="resetForm()" class="px-12 py-4 bg-gray-900 text-white rounded-full font-black uppercase tracking-widest text-xs hover:shadow-2xl hover:scale-105 active:scale-95 transition-all">Understood</button>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Footer --}}
            <div class="mt-16 text-center text-gray-300 text-[11px] font-black uppercase tracking-[0.4em]">
                © {{ date('Y') }} John Kelly & Company · Built for Excellence
            </div>
        </div>
    </div>

    <script>
    function publicApplication(jobPostings = []) {
        return {
            jobPostings: jobPostings,
            form: {
                jobPostingId: '',
                fullName: '', positionApplied: '', email: '', phone: '',
                photo: null, cv: null, coverLetterFile: null, coverLetter: ''
            },
            isSubmitting: false,
            isSuccess: false,

            get selectedJob() {
                return this.jobPostings.find(job => String(job.id) === String(this.form.jobPostingId)) || null;
            },

            onJobPostingChange() {
                this.form.positionApplied = this.selectedJob ? (this.selectedJob.position || '') : '';
            },

            submitForm() {
                if (!this.form.jobPostingId) {
                    alert('Please select a posted job before submitting your application.');
                    return;
                }

                this.isSubmitting = true;
                let fd = new FormData();
                fd.append('jobPostingId', this.form.jobPostingId);
                fd.append('fullName', this.form.fullName);
                fd.append('positionApplied', this.form.positionApplied);
                fd.append('email', this.form.email);
                fd.append('phone', this.form.phone);
                fd.append('coverLetter', this.form.coverLetter);
                if (this.form.photo) fd.append('photo', this.form.photo);
                if (this.form.cv) fd.append('cv', this.form.cv);
                if (this.form.coverLetterFile) fd.append('cover_letter_file', this.form.coverLetterFile);

                fetch('{{ route("careers.apply.submit") }}', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: fd
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        this.isSuccess = true;
                    } else {
                        alert(data.message || 'Submission failed. Please check your inputs and try again.');
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
                this.form = {
                    jobPostingId: '',
                    fullName: '', positionApplied: '', email: '', phone: '',
                    photo: null, cv: null, coverLetterFile: null, coverLetter: ''
                };
            }
        }
    }
    </script>
</body>
</html>
