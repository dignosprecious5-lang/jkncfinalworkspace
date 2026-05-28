<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Candidate Application | John Kelly &amp; Company</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        body { font-family: Inter, sans-serif; }
        [x-cloak] { display: none !important; }
        .field { width: 100%; border: 1px solid #d1d5db; border-radius: 0.75rem; padding: 0.75rem 0.875rem; font-size: 0.875rem; outline: none; background: #fff; }
        .field:focus { border-color: #2563eb; box-shadow: 0 0 0 3px rgba(37, 99, 235, .12); }
        .label { display: block; margin-bottom: .4rem; font-size: .68rem; font-weight: 900; text-transform: uppercase; letter-spacing: .12em; color: #4b5563; }
        .section-title { font-size: 1rem; font-weight: 900; text-transform: uppercase; letter-spacing: .12em; color: #111827; }
    </style>
</head>
<body class="bg-slate-50 text-slate-900">
<div class="min-h-screen px-4 py-8" x-data='publicApplication(@json($jobPostings ?? []))'>
    <div class="mx-auto max-w-5xl">
        <header class="mb-8 flex flex-col gap-4 border-b border-slate-200 pb-6 sm:flex-row sm:items-center sm:justify-between">
            <a href="{{ route('homepage.public') }}" class="flex items-center gap-4">
                <img src="{{ asset('images/FINAL_LOGO.jpg') }}" alt="John Kelly &amp; Company" class="h-16 w-auto object-contain">
                <div>
                    <h1 class="text-xl font-black uppercase tracking-tight">Career Portal</h1>
                    <p class="text-sm font-semibold text-slate-500">Candidate Application Form</p>
                </div>
            </a>
            <a href="{{ route('homepage.public') }}" class="inline-flex items-center justify-center rounded-lg border border-blue-200 px-4 py-2 text-sm font-bold text-blue-700 hover:bg-blue-50">Back to Careers</a>
        </header>

        <form @submit.prevent="submitForm" class="space-y-6">
            @csrf

            <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="mb-5">
                    <h2 class="section-title">I. Personal Information</h2>
                    <p class="mt-1 text-sm text-slate-500">Your applicant ID will be generated after submission.</p>
                </div>
                <div class="grid gap-4 sm:grid-cols-3">
                    <div><label class="label">First Name</label><input class="field" x-model="form.firstName" required></div>
                    <div><label class="label">Middle Name</label><input class="field" x-model="form.middleName"></div>
                    <div><label class="label">Last Name</label><input class="field" x-model="form.lastName" required></div>
                    <div><label class="label">Nickname / Preferred Name</label><input class="field" x-model="form.nickname"></div>
                    <div><label class="label">Date of Birth</label><input type="date" class="field" x-model="form.dateOfBirth" @change="computeAge"></div>
                    <div><label class="label">Age</label><input class="field bg-slate-100" x-model="form.age" readonly></div>
                    <div><label class="label">Gender</label><select class="field" x-model="form.gender"><option value="">Prefer not to say</option><option>Female</option><option>Male</option><option>Other</option></select></div>
                    <div><label class="label">Civil Status</label><select class="field" x-model="form.civilStatus"><option value="">Select...</option><option>Single</option><option>Married</option><option>Widowed</option><option>Separated</option></select></div>
                    <div><label class="label">Nationality</label><input class="field" x-model="form.nationality"></div>
                    <div><label class="label">Religion</label><input class="field" x-model="form.religion"></div>
                    <div><label class="label">PWD?</label><select class="field" x-model="form.pwd"><option>No</option><option>Yes</option></select></div>
                    <div><label class="label">Solo Parent?</label><select class="field" x-model="form.soloParent"><option>No</option><option>Yes</option></select></div>
                    <div><label class="label">Senior Citizen?</label><select class="field" x-model="form.seniorCitizen"><option>No</option><option>Yes</option></select></div>
                    <div class="sm:col-span-3"><label class="label">Current Address</label><textarea class="field" rows="2" x-model="form.currentAddress" required></textarea></div>
                    <div class="sm:col-span-3"><label class="label">Permanent Address</label><textarea class="field" rows="2" x-model="form.permanentAddress" :placeholder="form.sameAddress ? 'Same as current address' : ''" :disabled="form.sameAddress"></textarea><label class="mt-2 flex items-center gap-2 text-sm"><input type="checkbox" x-model="form.sameAddress"> Same as current address</label></div>
                    <div><label class="label">Contact Number</label><input class="field" x-model="form.phone" required></div>
                    <div class="sm:col-span-2"><label class="label">Email Address</label><input type="email" class="field" x-model="form.email" required></div>
                </div>
            </section>

            <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                <h2 class="section-title mb-5">II. Position Applied For</h2>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="sm:col-span-2">
                        <label class="label">Position Title</label>
                        <select class="field" x-model="form.jobPostingId" @change="onJobPostingChange()" required>
                            <option value="">Select active published job...</option>
                            @foreach($jobPostings as $job)
                                <option value="{{ $job->id }}">{{ $job->job_id }} - {{ $job->position }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div><label class="label">Department / Team</label><input class="field bg-slate-100" x-model="form.departmentTeam" readonly></div>
                    <div><label class="label">Employment Type</label><input class="field bg-slate-100" x-model="form.employmentType" readonly></div>
                    <div><label class="label">Job ID</label><input class="field bg-slate-100" x-model="form.jobId" readonly></div>
                    <div><label class="label">Preferred Work Arrangement</label><select class="field" x-model="form.preferredWorkArrangement" required><template x-for="arrangement in allowedWorkArrangements" :key="arrangement"><option x-text="arrangement"></option></template></select></div>
                    <div><label class="label">Date Available to Start</label><input type="date" class="field" x-model="form.dateAvailable" :min="today" required></div>
                </div>
            </section>

            <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="mb-5 flex items-center justify-between gap-3">
                    <h2 class="section-title">III. Educational Background</h2>
                    <button type="button" @click="addEducation('Others')" class="rounded-lg bg-slate-900 px-3 py-2 text-xs font-bold text-white">+ Add Education</button>
                </div>
                <div class="space-y-4">
                    <template x-for="(edu, index) in form.education" :key="index">
                        <div class="rounded-lg border border-slate-200 p-4">
                            <div class="mb-3 flex items-center justify-between">
                                <strong class="text-sm" x-text="edu.level"></strong>
                                <button type="button" x-show="edu.added" @click="form.education.splice(index, 1)" class="text-xs font-bold text-red-600">Remove</button>
                            </div>
                            <div class="grid gap-3 sm:grid-cols-5">
                                <input class="field" placeholder="School Name" x-model="edu.school" :required="edu.required">
                                <input class="field" placeholder="Degree / Level Completed" x-model="edu.degree" :required="edu.required">
                                <input class="field" placeholder="Course / Major" x-model="edu.course">
                                <input class="field" placeholder="Year Graduated" x-model="edu.year" :required="edu.required">
                                <input class="field" placeholder="Honors / Awards" x-model="edu.honors">
                            </div>
                            <label x-show="edu.level === 'Senior High School'" class="mt-3 flex items-center gap-2 text-sm"><input type="checkbox" x-model="edu.oldCurriculum"> Not Applicable / Old Curriculum</label>
                        </div>
                    </template>
                </div>
            </section>

            <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="mb-5 flex items-center justify-between gap-3">
                    <div>
                        <h2 class="section-title">IV. Employment History</h2>
                        <p class="mt-1 text-sm text-slate-500">Add employment history entries as applicable. Applicants may add as many employment records as needed.</p>
                    </div>
                    <button type="button" @click="addEmployment" class="rounded-lg bg-slate-900 px-3 py-2 text-xs font-bold text-white">+ Add Employment</button>
                </div>
                <label class="mb-4 flex items-center gap-2 text-sm"><input type="checkbox" x-model="form.noPreviousEmployment"> No Previous Employment / First-Time Job Applicant</label>
                <div class="space-y-4" x-show="!form.noPreviousEmployment">
                    <template x-for="(job, index) in form.employmentHistory" :key="index">
                        <div class="rounded-lg border border-slate-200 p-4">
                            <div class="mb-3 flex justify-end"><button type="button" @click="form.employmentHistory.splice(index, 1)" class="text-xs font-bold text-red-600">Remove</button></div>
                            <div class="grid gap-3 sm:grid-cols-2">
                                <input class="field" placeholder="Company Name" x-model="job.company">
                                <input class="field" placeholder="Company Address" x-model="job.address">
                                <input class="field" placeholder="Employer / HR Contact Person" x-model="job.contactPerson">
                                <input type="email" class="field" placeholder="Employer Email Address" x-model="job.contactEmail">
                                <input class="field" placeholder="Employer Contact Number" x-model="job.contactNumber">
                                <input class="field" placeholder="Position / Title" x-model="job.position">
                                <input class="field" placeholder="Inclusive Dates From - To" x-model="job.inclusiveDates">
                                <input class="field" placeholder="Reason for Leaving" x-model="job.reasonForLeaving">
                                <textarea class="field sm:col-span-2" rows="2" placeholder="Key Responsibilities" x-model="job.responsibilities"></textarea>
                            </div>
                        </div>
                    </template>
                </div>
            </section>

            <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="mb-5 flex items-center justify-between gap-3">
                    <h2 class="section-title">V. Certifications &amp; Trainings</h2>
                    <button type="button" @click="addCertification" class="rounded-lg bg-slate-900 px-3 py-2 text-xs font-bold text-white">+ Add Certification / Training</button>
                </div>
                <div class="space-y-4">
                    <template x-for="(cert, index) in form.certifications" :key="index">
                        <div class="rounded-lg border border-slate-200 p-4">
                            <div class="mb-3 flex justify-end"><button type="button" @click="form.certifications.splice(index, 1)" class="text-xs font-bold text-red-600">Remove</button></div>
                            <div class="grid gap-3 sm:grid-cols-2">
                                <input class="field" placeholder="Certification / Training / Course Name" x-model="cert.name">
                                <input class="field" placeholder="Provider / Issuing Organization" x-model="cert.provider">
                                <select class="field" x-model="cert.status"><option>Completed / Taken</option><option>Planned / Scheduled</option></select>
                                <input type="date" class="field" x-model="cert.dateTaken" placeholder="Date Taken">
                                <input type="date" class="field" x-model="cert.datePlanned" placeholder="Date Planned">
                                <input class="field" placeholder="Validity Period" x-model="cert.validity">
                                <input type="date" class="field" x-model="cert.expirationDate" :disabled="cert.noExpiration">
                                <input class="field" placeholder="Certificate / License / Unique Code" x-model="cert.code">
                                <label class="flex items-center gap-2 text-sm"><input type="checkbox" x-model="cert.noExpiration"> No Expiration / Lifetime Validity</label>
                                <textarea class="field sm:col-span-2" rows="2" placeholder="Notes" x-model="cert.notes"></textarea>
                            </div>
                        </div>
                    </template>
                </div>
            </section>

            <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                <h2 class="section-title mb-5">VI-XI. Skills, Preferences, Compliance, Emergency, Attachments &amp; Consent</h2>
                <div class="grid gap-4 sm:grid-cols-2">
                    <textarea class="field" rows="4" placeholder="Technical Skills" x-model="form.technicalSkills"></textarea>
                    <textarea class="field" rows="4" placeholder="Software / Tools Familiar With" x-model="form.softwareTools"></textarea>
                    <textarea class="field" rows="4" placeholder="Certifications / Licenses (if any)" x-model="form.certificationsLicenses"></textarea>
                    <textarea class="field" rows="4" placeholder="Languages Spoken (with proficiency level)" x-model="form.languages"></textarea>
                    <input class="field" placeholder="Current Salary (optional)" x-model="form.currentSalary">
                    <input class="field" placeholder="Expected Salary" x-model="form.expectedSalary">
                    <select class="field" x-model="form.willingOvertime"><option value="">Willing to work overtime?</option><option>Yes</option><option>No</option></select>
                    <select class="field" x-model="form.willingSmallTeam"><option value="">Willing to work alone / small team?</option><option>Yes</option><option>No</option></select>
                    <select class="field" x-model="form.willingResidentialOffice"><option value="">Willing to work in a residential office?</option><option>Yes</option><option>No</option></select>
                    <select class="field" x-model="form.authorizedToWork"><option value="">Authorized to work in the Philippines?</option><option>Yes</option><option>No</option></select>
                    <select class="field" x-model="form.pendingObligations"><option value="">Pending employment obligations?</option><option>Yes</option><option>No</option></select>
                    <select class="field" x-model="form.crimeConviction"><option value="">Convicted of any crime?</option><option>Yes</option><option>No</option></select>
                    <textarea class="field sm:col-span-2" rows="2" placeholder="If yes, explain pending obligations or conviction." x-model="form.legalExplanation"></textarea>
                    <select class="field" x-model="form.backgroundCheck"><option value="">Willing to undergo background check?</option><option>Yes</option><option>No</option></select>
                    <input class="field" placeholder="Emergency Contact Full Name" x-model="form.emergencyName">
                    <input class="field" placeholder="Emergency Contact Relationship" x-model="form.emergencyRelationship">
                    <input class="field" placeholder="Emergency Contact Number" x-model="form.emergencyNumber">
                    <textarea class="field sm:col-span-2" rows="2" placeholder="Emergency Contact Address" x-model="form.emergencyAddress"></textarea>
                    <select class="field" x-model="form.applicationSource"><option value="">How did you learn about this job opening?</option><option>Facebook</option><option>LinkedIn</option><option>Mynimo</option><option>Referral</option><option>Company Website</option><option>Others</option></select>
                    <input class="field" x-show="form.applicationSource === 'Others'" placeholder="If other, specify" x-model="form.applicationSourceOther">
                    <div><label class="label">Resume / CV (required, max 4MB)</label><input type="file" class="field" accept=".pdf,.doc,.docx" @change="form.cv = $event.target.files[0]" required></div>
                    <div><label class="label">Portfolio (if applicable)</label><input type="file" class="field" @change="form.portfolioFile = $event.target.files[0]"></div>
                    <div><label class="label">Valid Government ID (optional)</label><input type="file" class="field" @change="form.governmentId = $event.target.files[0]"></div>
                    <div><label class="label">2x2 Applicant Photo</label><input type="file" class="field" accept="image/*" @change="form.photo = $event.target.files[0]"></div>
                    <textarea class="field sm:col-span-2" rows="2" placeholder="Attachment Notes (optional)" x-model="form.attachmentNotes"></textarea>
                    <div class="sm:col-span-2 rounded-lg border border-blue-100 bg-blue-50 p-4 text-sm leading-6 text-slate-700">
                        <label class="flex items-start gap-3">
                            <input type="checkbox" class="mt-1" x-model="form.consentAccepted" required>
                            <span>I certify, consent, authorize, acknowledge, and agree that all information and documents submitted are true, correct, complete, authentic, and updated; and that John Kelly &amp; Company, JK&amp;C Inc., and authorized representatives may lawfully process my personal and sensitive personal information for recruitment, verification, onboarding, compliance, legal, audit, and employment-related purposes. I understand that false statements or omissions may result in rejection, withdrawal of offer, termination if hired, and/or legal action where applicable.</span>
                        </label>
                    </div>
                </div>
            </section>

            <div class="pb-8 text-center">
                <button type="submit" :disabled="isSubmitting" class="rounded-xl bg-blue-700 px-8 py-4 text-sm font-black uppercase tracking-widest text-white shadow-lg hover:bg-blue-800 disabled:opacity-50" x-text="isSubmitting ? 'Submitting...' : 'Submit Application'"></button>
            </div>
        </form>

        <div x-show="isSuccess" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/70 p-4">
            <div class="max-w-lg rounded-2xl bg-white p-8 text-center shadow-2xl">
                <h2 class="text-2xl font-black">Application Submitted</h2>
                <p class="mt-3 text-slate-600">Your application was received. Your Candidate Application ID is:</p>
                <p class="mt-4 rounded-lg bg-blue-50 px-4 py-3 text-xl font-black text-blue-800" x-text="submittedApplicantId || 'Generated'"></p>
                <a href="{{ route('homepage.public') }}" class="mt-6 inline-flex rounded-lg bg-slate-900 px-5 py-3 text-sm font-bold text-white">Back to Careers</a>
            </div>
        </div>
    </div>
</div>

<script>
function publicApplication(jobPostings = []) {
    return {
        jobPostings,
        today: new Date().toISOString().slice(0, 10),
        isSubmitting: false,
        isSuccess: false,
        submittedApplicantId: '',
        form: {
            jobPostingId: new URLSearchParams(window.location.search).get('job_id') || '',
            firstName: '', middleName: '', lastName: '', nickname: '', dateOfBirth: '', age: '',
            gender: '', civilStatus: '', nationality: 'Filipino', religion: '', pwd: 'No', soloParent: 'No', seniorCitizen: 'No',
            currentAddress: '', permanentAddress: '', sameAddress: false, email: '', phone: '',
            positionApplied: '', departmentTeam: '', employmentType: '', jobId: '', preferredWorkArrangement: 'On-site', dateAvailable: '',
            education: [
                { level: 'Elementary', required: true, added: false, school: '', degree: '', course: '', year: '', honors: '' },
                { level: 'High School / Junior High School', required: true, added: false, school: '', degree: '', course: '', year: '', honors: '' },
                { level: 'Senior High School', required: true, added: false, school: '', degree: '', course: '', year: '', honors: '', oldCurriculum: false },
                { level: 'College / Bachelor\\'s Degree', required: false, added: false, school: '', degree: '', course: '', year: '', honors: '' }
            ],
            noPreviousEmployment: false,
            employmentHistory: [],
            certifications: [],
            consentAccepted: false,
            photo: null, cv: null, portfolioFile: null, governmentId: null,
        },
        get selectedJob() {
            return this.jobPostings.find(job => String(job.id) === String(this.form.jobPostingId)) || null;
        },
        get allowedWorkArrangements() {
            const selected = this.selectedJob;
            const raw = selected?.work_arrangement || selected?.workSchedule || selected?.work_schedule || null;
            if (Array.isArray(raw) && raw.length) return raw;
            return ['On-site', 'Hybrid', 'Work-from-Home', 'Field Work', 'Others'];
        },
        init() { this.onJobPostingChange(); },
        computeAge() {
            if (!this.form.dateOfBirth) return;
            const birth = new Date(this.form.dateOfBirth);
            const today = new Date();
            let age = today.getFullYear() - birth.getFullYear();
            const monthDiff = today.getMonth() - birth.getMonth();
            if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birth.getDate())) age--;
            this.form.age = age >= 0 ? age : '';
        },
        onJobPostingChange() {
            const job = this.selectedJob;
            this.form.positionApplied = job?.position || '';
            this.form.departmentTeam = job?.department_unit || job?.department || '';
            this.form.employmentType = job?.employment_type || '';
            this.form.jobId = job?.job_id || '';
            if (!this.allowedWorkArrangements.includes(this.form.preferredWorkArrangement)) {
                this.form.preferredWorkArrangement = this.allowedWorkArrangements[0] || '';
            }
        },
        addEducation(level = 'Others') {
            this.form.education.push({ level, required: false, added: true, school: '', degree: '', course: '', year: '', honors: '', otherLevel: '' });
        },
        addEmployment() {
            this.form.employmentHistory.push({ company: '', address: '', contactPerson: '', contactEmail: '', contactNumber: '', position: '', inclusiveDates: '', responsibilities: '', reasonForLeaving: '' });
        },
        addCertification() {
            this.form.certifications.push({ name: '', provider: '', status: 'Completed / Taken', dateTaken: '', datePlanned: '', validity: '', expirationDate: '', noExpiration: false, code: '', notes: '' });
        },
        appendData(fd, key, value) {
            fd.append(key, typeof value === 'object' && value !== null ? JSON.stringify(value) : (value ?? ''));
        },
        submitForm() {
            if (!this.form.jobPostingId) return alert('Please select an active job posting.');
            if (!this.form.consentAccepted) return alert('Declaration and consent is required.');
            this.isSubmitting = true;
            const fd = new FormData();
            Object.entries(this.form).forEach(([key, value]) => {
                if (['photo', 'cv', 'portfolioFile', 'governmentId'].includes(key)) return;
                this.appendData(fd, key, value);
            });
            fd.append('fullName', [this.form.firstName, this.form.middleName, this.form.lastName].filter(Boolean).join(' '));
            fd.append('positionApplied', this.form.positionApplied);
            if (this.form.photo) fd.append('photo', this.form.photo);
            if (this.form.cv) fd.append('cv', this.form.cv);
            if (this.form.portfolioFile) fd.append('portfolio_file', this.form.portfolioFile);
            if (this.form.governmentId) fd.append('government_id', this.form.governmentId);

            fetch('{{ route("careers.apply.submit") }}', {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                body: fd
            })
                .then(res => res.json())
                .then(data => {
                    if (!data.success) throw new Error(data.message || 'Submission failed.');
                    this.submittedApplicantId = data.data?.applicant_id || '';
                    this.isSuccess = true;
                })
                .catch(error => alert(error.message || 'Unable to submit application.'))
                .finally(() => { this.isSubmitting = false; });
        }
    };
}
</script>
</body>
</html>
