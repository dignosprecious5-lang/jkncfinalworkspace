<?php

use App\Http\Controllers\ActivityController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\ChangePasswordController;
use App\Http\Controllers\BylawController;
use App\Http\Controllers\CapitalStructureController;
use App\Http\Controllers\CatalogChangeRequestController;
use App\Http\Controllers\CompanyActivityController;
use App\Http\Controllers\CompanyConsultationNoteController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\CompanyCorporateFormationController;
use App\Http\Controllers\CompanyCorporateRecordController;
use App\Http\Controllers\CompanyDealController;
use App\Http\Controllers\CompanyAccountingController;
use App\Http\Controllers\CompanyBankingController;
use App\Http\Controllers\CompanyBirTaxController;
use App\Http\Controllers\CompanyBifController;
use App\Http\Controllers\CompanyCorrespondenceController;
use App\Http\Controllers\CompanyKycController;
use App\Http\Controllers\CompanyLguController;
use App\Http\Controllers\CompanyOperationsController;
use App\Http\Controllers\CompanyProductController;
use App\Http\Controllers\CompanyServiceController;
use App\Http\Controllers\ContactsController;
use App\Http\Controllers\ContactConsultationNoteController;
use App\Http\Controllers\CorporateApprovalController;
use App\Http\Controllers\CorporateFormationController;
use App\Http\Controllers\DealController;
use App\Http\Controllers\DealProposalController;
use App\Http\Controllers\DirectorOfficerController;
use App\Http\Controllers\GisController;
use App\Http\Controllers\AdminDashboardController;
use App\Http\Controllers\AdminHumanCapitalDashboardController;
use App\Http\Controllers\AdminUserController;
use App\Http\Controllers\AdminUserPermissionController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\RegularController;
use App\Http\Controllers\RolePermissionController;
use App\Http\Controllers\SecAoiController;
use App\Http\Controllers\StockholderController;
use App\Http\Controllers\StockTransferCertificateController;
use App\Http\Controllers\StockTransferInstallmentController;
use App\Http\Controllers\StockTransferJournalController;
use App\Http\Controllers\StockTransferLedgerController;
use App\Http\Controllers\StockTransferLookupController;
use App\Http\Controllers\TownHallController;
use App\Http\Controllers\UploadedFileController;
use App\Http\Controllers\BirTaxController;
use App\Http\Controllers\NatGovController;
use App\Http\Controllers\UltimateBeneficialOwnerController;
use App\Http\Controllers\PermitController;
use App\Http\Controllers\PolicyController;
use App\Http\Controllers\CorrespondenceController;
use App\Http\Controllers\AccountingController;
use App\Http\Controllers\BankingController;
use App\Http\Controllers\FinanceController;
use App\Http\Controllers\CorporateDocumentDefaultsController;
use App\Http\Controllers\MinuteController;
use App\Http\Controllers\NoticeController;
use App\Http\Controllers\OperationController;
use App\Http\Controllers\LegalController;
use App\Http\Controllers\ResolutionController;
use App\Http\Controllers\SecretaryCertificateController;
use App\Http\Controllers\TransmittalController;
use App\Http\Controllers\TransmittalReceiptController;
use App\Http\Controllers\SalesMarketingController;
use App\Http\Controllers\SalesMarketingEarnerController;
use App\Http\Controllers\SalesMarketingIdaController;
use App\Http\Controllers\OrganizationalController;
use App\Http\Controllers\RecruitmentController;
use App\Http\Controllers\OnboardingRecordController;
use App\Http\Controllers\PayrollController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\EmployeeRequestController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PhilippineLocationController;
use App\Http\Controllers\SalesMarketingPayoutController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\TrainingController;
use App\Http\Controllers\DeploymentController;
use App\Http\Controllers\OfficialBusinessTripController;
use App\Http\Controllers\EmployeeRelationController;
use App\Http\Controllers\AwardController;
use App\Http\Controllers\PerformanceController;
use App\Http\Controllers\OffboardingController;
use App\Http\Controllers\AssessmentQuestionController;

/*
|--------------------------------------------------------------------------
| PUBLIC / AUTH-ENTRY ROUTES
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    if (Auth::check()) {
        $user = Auth::user();

        if ($user->isSuperAdmin() || $user->isAdmin()) {
            return redirect()->route('admin.users');
        }

        if ($user->isClient()) {
            return redirect()->route('contacts.index');
        }

        return redirect()->route('townhall');
    }

    return redirect()->route('login');
});

Route::get('/employee-verification/{employee?}', [EmployeeController::class, 'verificationForm'])->name('employee.verify.form');
Route::post('/employee-verification', [EmployeeController::class, 'verify'])->name('employee.verify.submit');

/*
|--------------------------------------------------------------------------
| CLIENT RESPONSE ROUTES
|--------------------------------------------------------------------------
*/

Route::get('/bif/respond/{token}', [CompanyBifController::class, 'clientForm'])->name('company.bif.client.show');
Route::post('/bif/respond/{token}', [CompanyBifController::class, 'submitClientForm'])->name('company.bif.client.submit');
Route::get('/bif/respond/{token}/preview', [CompanyBifController::class, 'previewClientBif'])->name('company.bif.client.preview');
Route::get('/bif/respond/{token}/download', [CompanyBifController::class, 'downloadClientBif'])->name('company.bif.client.download');

Route::get('/cif/respond/{token}', [ContactsController::class, 'clientCifForm'])->name('contacts.cif.client.show');
Route::post('/cif/respond/{token}', [ContactsController::class, 'submitClientCifForm'])->name('contacts.cif.client.submit');
Route::get('/cif/respond/{token}/preview', [ContactsController::class, 'previewClientCif'])->name('contacts.cif.client.preview');
Route::get('/cif/respond/{token}/download', [ContactsController::class, 'downloadClientCif'])->name('contacts.cif.client.download');

Route::get('/specimen/respond/{token}', [ContactsController::class, 'clientSpecimenForm'])->name('contacts.specimen.client.show');
Route::post('/specimen/respond/{token}', [ContactsController::class, 'submitClientSpecimenForm'])->name('contacts.specimen.client.submit');
Route::get('/specimen/respond/{token}/preview', [ContactsController::class, 'previewClientSpecimenForm'])->name('contacts.specimen.client.preview');
Route::get('/specimen/respond/{token}/download', [ContactsController::class, 'downloadClientSpecimenForm'])->name('contacts.specimen.client.download');

Route::get('/project/report/respond/{token}', [ProjectController::class, 'clientSowReport'])->name('project.report.client.show');
Route::post('/project/report/respond/{token}', [ProjectController::class, 'submitClientSowReport'])->name('project.report.client.submit');
Route::get('/project/report/respond/{token}/download', [ProjectController::class, 'downloadClientSowReport'])->name('project.report.client.download');
Route::get('/project/ntp/respond/{token}', [ProjectController::class, 'clientNtp'])->name('project.ntp.client.show');
Route::post('/project/ntp/respond/{token}', [ProjectController::class, 'submitClientNtp'])->name('project.ntp.client.submit');
Route::get('/project/ntp/respond/{token}/download', [ProjectController::class, 'downloadClientNtp'])->name('project.ntp.client.download');
Route::get('/regular/report/respond/{token}', [RegularController::class, 'clientRsatReport'])->name('regular.report.client.show');
Route::post('/regular/report/respond/{token}', [RegularController::class, 'submitClientRsatReport'])->name('regular.report.client.submit');
Route::get('/regular/report/respond/{token}/download', [RegularController::class, 'downloadClientRsatReport'])->name('regular.report.client.download');
Route::get('/regular/ntp/respond/{token}', [RegularController::class, 'clientNtp'])->name('regular.ntp.client.show');
Route::post('/regular/ntp/respond/{token}', [RegularController::class, 'submitClientNtp'])->name('regular.ntp.client.submit');
Route::get('/regular/ntp/respond/{token}/download', [RegularController::class, 'downloadClientNtp'])->name('regular.ntp.client.download');
Route::get('/proposal/respond/{token}', [DealProposalController::class, 'clientProposal'])->name('deals.proposal.client.show');
Route::post('/proposal/respond/{token}/approve', [DealProposalController::class, 'approveClientProposal'])->name('deals.proposal.client.approve');
Route::post('/proposal/respond/{token}/quotation-upload', [DealProposalController::class, 'uploadClientQuotation'])->name('deals.proposal.client.quotation-upload');
Route::get('/proposal/respond/{token}/download', [DealProposalController::class, 'downloadClientProposal'])->name('deals.proposal.client.download');

/*
|--------------------------------------------------------------------------
| ACTIVITIES PAGE + API
|--------------------------------------------------------------------------
*/

Route::get('/activities', function () {
    return view('activities.index');
})->name('activities');

Route::prefix('api')->group(function () {
    Route::get('/activities', [ActivityController::class, 'index']);
    Route::post('/tasks', [ActivityController::class, 'storeTask']);
    Route::post('/events', [ActivityController::class, 'storeEvent']);
    Route::post('/calls', [ActivityController::class, 'storeCall']);
    Route::post('/meetings', [ActivityController::class, 'storeMeeting']);

    Route::put('/tasks/{id}', [ActivityController::class, 'updateTask']);
    Route::put('/events/{id}', [ActivityController::class, 'updateEvent']);
    Route::put('/calls/{id}', [ActivityController::class, 'updateCall']);
    Route::put('/meetings/{id}', [ActivityController::class, 'updateMeeting']);

    Route::delete('/tasks/{id}', [ActivityController::class, 'destroyTask']);
    Route::delete('/events/{id}', [ActivityController::class, 'destroyEvent']);
    Route::delete('/calls/{id}', [ActivityController::class, 'destroyCall']);
    Route::delete('/meetings/{id}', [ActivityController::class, 'destroyMeeting']);

    Route::post('/notes', [ActivityController::class, 'storeNote']);
    Route::put('/notes/{id}', [ActivityController::class, 'updateNote']);
    Route::delete('/notes/{id}', [ActivityController::class, 'destroyNote']);
    Route::post('/meetings/{id}/analyze', [ActivityController::class, 'analyzeMeeting']);
    Route::post('/meetings/{id}/upload-video', [ActivityController::class, 'uploadVideo']);
    Route::post('/meetings/{id}/upload-transcript', [ActivityController::class, 'uploadTranscript']);
    Route::post('/meetings/{id}/upload-minutes', [ActivityController::class, 'uploadMinutes']);
    Route::post('/calls/{id}/upload-audio', [ActivityController::class, 'uploadCallAudio']);
    Route::delete('/calls/{id}/audio', [ActivityController::class, 'destroyCallAudio']);
});

/*
|--------------------------------------------------------------------------
| GUEST AUTH ROUTES
|--------------------------------------------------------------------------
*/

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [LoginController::class, 'login'])->name('login.post');
});

Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

$adminOrSuperAdmin = \App\Http\Middleware\AdminOrSuperAdmin::class;

/*
|--------------------------------------------------------------------------
| HUMAN CAPITAL PUBLIC ROUTES
|--------------------------------------------------------------------------
*/

Route::get('/careers', [RecruitmentController::class, 'showPublicCareersPage'])->name('homepage.public');
Route::get('/careers/job/{id}', [RecruitmentController::class, 'showJobDetail'])->name('careers.job-detail');
Route::get('/careers/apply', [RecruitmentController::class, 'showPublicApplicationForm'])->name('careers.apply');
Route::post('/careers/apply', [RecruitmentController::class, 'storeCAF'])->name('careers.apply.submit');
Route::get('/careers/pds/{token?}', [RecruitmentController::class, 'showPublicPDSForm'])->name('careers.pds');
Route::post('/careers/pds', [RecruitmentController::class, 'storePDS'])->name('careers.pds.submit');

Route::get('/careers/checklist/{token}', [OnboardingRecordController::class, 'showPublicChecklistUpload'])
    ->name('careers.checklist.show');
Route::post('/careers/checklist/{token}', [OnboardingRecordController::class, 'submitPublicChecklistUpload'])
    ->name('careers.checklist.submit');


Route::get('/assessment/start/{uuid}', [RecruitmentController::class, 'startAssessment'])
    ->name('recruitment.assessment.start');

Route::post('/assessment/start/{uuid}/submit', [RecruitmentController::class, 'submitAssessmentTest'])
    ->name('recruitment.assessment.submit');

Route::get('/job-offer/{token}/accept', [RecruitmentController::class, 'acceptJobOffer'])
    ->name('job-offer.accept');

Route::get('/job-offer/{token}/decline', [RecruitmentController::class, 'declineJobOffer'])
    ->name('job-offer.decline');


/*
|--------------------------------------------------------------------------
| AUTHENTICATED ROUTES
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'prevent-back-history'])->group(function () use ($adminOrSuperAdmin) {
    /*
    |--------------------------------------------------------------------------
    | ACCOUNT SETTINGS
    |--------------------------------------------------------------------------
    */
    Route::get('/change-password', [ChangePasswordController::class, 'edit'])->name('password.change');
    Route::post('/change-password', [ChangePasswordController::class, 'update'])->name('password.change.update');

    /*
    |--------------------------------------------------------------------------
    | FILES / UPLOADS
    |--------------------------------------------------------------------------
    */
    Route::get('/uploads/{path}', [UploadedFileController::class, 'show'])
        ->where('path', '.*')
        ->name('uploads.show');


    /*
    |--------------------------------------------------------------------------
    | REAL-TIME NOTIFICATIONS
    |--------------------------------------------------------------------------
    */
    Route::post('/notifications/{notification}/read', [NotificationController::class, 'markAsRead'])
        ->name('notifications.read');

    Route::post('/notifications/read-all', [NotificationController::class, 'markAllAsRead'])
        ->name('notifications.read-all');

    /*
    |--------------------------------------------------------------------------
    | ADMIN MODULE
    |--------------------------------------------------------------------------
    */
    Route::get('/admin-dashboard', [AdminDashboardController::class, 'index'])->name('admin.dashboard');
    Route::get('/admin-dashboard/{section}', [AdminDashboardController::class, 'index'])
        ->where('section', 'town-hall|contacts|company|deals|project|regular|services|products')
        ->name('admin.dashboard.section');
    Route::get('/admin/users', [AdminUserController::class, 'index'])->name('admin.users');
    Route::post('/admin/users', [AdminUserController::class, 'store'])->name('admin.users.store');
    Route::post('/admin/users/{id}', [AdminUserController::class, 'update'])->name('admin.users.update');
    Route::delete('/admin/users/{id}', [AdminUserController::class, 'destroy'])->name('admin.users.destroy');

    Route::get('/admin/role-permissions', [RolePermissionController::class, 'index'])->name('admin.role-permissions');
    Route::post('/admin/role-permissions/{id}', [RolePermissionController::class, 'update'])->name('admin.role-permissions.update');

    Route::get('/admin/user-permissions', [AdminUserPermissionController::class, 'index'])->name('admin.user-permissions');
    Route::get('/admin/user-permissions/{id}', [AdminUserPermissionController::class, 'edit'])->name('admin.user-permissions.edit');
    Route::post('/admin/user-permissions/{id}', [AdminUserPermissionController::class, 'update'])->name('admin.user-permissions.update');

    Route::get('/admin/corporate-dashboard', [CorporateApprovalController::class, 'dashboard'])->name('admin.corporate.dashboard');

    Route::get('/admin/human-capital-dashboard', [AdminHumanCapitalDashboardController::class, 'index'])->name('admin.human-capital.dashboard');
    Route::post('/admin/human-capital/employee-requests/{employeeRequest}/approve', [AdminHumanCapitalDashboardController::class, 'approveEmployeeRequest'])->name('admin.human-capital.employee-requests.approve');
    Route::post('/admin/human-capital/employee-requests/{employeeRequest}/reject', [AdminHumanCapitalDashboardController::class, 'rejectEmployeeRequest'])->name('admin.human-capital.employee-requests.reject');
    Route::post('/admin/human-capital/employee-requests/{employeeRequest}/revise', [AdminHumanCapitalDashboardController::class, 'reviseEmployeeRequest'])->name('admin.human-capital.employee-requests.revise');
    Route::post('/admin/human-capital/obf/{officialBusinessTrip}/approve', [AdminHumanCapitalDashboardController::class, 'approveObf'])->name('admin.human-capital.obf.approve');
    Route::post('/admin/human-capital/obf/{officialBusinessTrip}/reject', [AdminHumanCapitalDashboardController::class, 'rejectObf'])->name('admin.human-capital.obf.reject');
    Route::post('/admin/human-capital/employee-relations/{employeeRelation}/approve', [AdminHumanCapitalDashboardController::class, 'approveEmployeeRelation'])->name('admin.human-capital.employee-relations.approve');
    Route::post('/admin/human-capital/employee-relations/{employeeRelation}/reject', [AdminHumanCapitalDashboardController::class, 'rejectEmployeeRelation'])->name('admin.human-capital.employee-relations.reject');
    Route::post('/admin/human-capital/training-assignments/{trainingAssignment}/complete', [AdminHumanCapitalDashboardController::class, 'completeTrainingAssignment'])->name('admin.human-capital.training-assignments.complete');
    Route::post('/admin/human-capital/training-assignments/{trainingAssignment}/certificate', [AdminHumanCapitalDashboardController::class, 'issueTrainingCertificate'])->name('admin.human-capital.training-assignments.certificate');
    Route::post('/admin/corporate-approvals/{module}/{id}/approve', [CorporateApprovalController::class, 'approve'])->name('corporate.approvals.approve');
    Route::post('/admin/corporate-approvals/{module}/{id}/reject', [CorporateApprovalController::class, 'reject'])->name('corporate.approvals.reject');
    Route::post('/admin/corporate-approvals/{module}/{id}/revise', [CorporateApprovalController::class, 'revise'])->name('corporate.approvals.revise');
    Route::post('/admin/corporate-approvals/{module}/{id}/archive', [CorporateApprovalController::class, 'archive'])->name('corporate.approvals.archive');

    Route::get('/admin/finance-dashboard', [FinanceController::class, 'adminDashboard'])->name('admin.finance.dashboard');
    Route::post('/admin/finance/{financeRecord}/approve-delete', [FinanceController::class, 'approveDelete'])->name('admin.finance.delete.approve');
    Route::post('/admin/finance/{financeRecord}/reject-delete', [FinanceController::class, 'rejectDelete'])->name('admin.finance.delete.reject');
    Route::post('/admin/finance/{financeRecord}/unarchive', [FinanceController::class, 'unarchive'])->name('admin.finance.unarchive');

    Route::post('/admin/catalog-change-requests/{catalogChangeRequest}/approve', [CatalogChangeRequestController::class, 'approve'])->name('catalog-change-requests.approve');
    Route::post('/admin/catalog-change-requests/{catalogChangeRequest}/reject', [CatalogChangeRequestController::class, 'reject'])->name('catalog-change-requests.reject');
    Route::get('/admin/deal-proposal-templates', [DealProposalController::class, 'proposalTemplatesIndex'])->name('admin.deal-proposal-templates.index');
    Route::post('/admin/deal-proposal-templates/{formTemplate}/approve', [DealProposalController::class, 'approveProposalTemplate'])->name('admin.deal-proposal-templates.approve');
    Route::post('/admin/deal-proposal-templates/{formTemplate}/reject', [DealProposalController::class, 'rejectProposalTemplate'])->name('admin.deal-proposal-templates.reject');

    /*
    |--------------------------------------------------------------------------
    | TOWN HALL MODULE
    |--------------------------------------------------------------------------
    */
    Route::get('/townhall', [TownHallController::class, 'index'])->name('townhall');
    Route::get('/townhall/department', [TownHallController::class, 'department'])->name('townhall.department');
    Route::get('/townhall/attachments', [TownHallController::class, 'attachments'])->name('townhall.attachments');
    Route::post('/townhall/attendance/clock', [AttendanceController::class, 'clock'])->name('townhall.attendance.clock');
    Route::post('/townhall', [TownHallController::class, 'store'])->name('townhall.store');
    Route::get('/townhall/{id}/edit', [TownHallController::class, 'edit'])->name('townhall.edit');
    Route::put('/townhall/{id}', [TownHallController::class, 'update'])->name('townhall.update');
    Route::get('/townhall/{id}', [TownHallController::class, 'show'])->name('townhall.show');
    Route::get('/townhall/{id}/download-pdf', [TownHallController::class, 'downloadPdf'])->name('townhall.download.pdf');
    Route::post('/townhall/{id}/approve', [TownHallController::class, 'approve'])->name('townhall.approve');
    Route::post('/townhall/{id}/reject', [TownHallController::class, 'reject'])->name('townhall.reject');
    Route::post('/townhall/{id}/revise', [TownHallController::class, 'revise'])->name('townhall.revise');
    Route::post('/townhall/{id}/archive', [TownHallController::class, 'archive'])->name('townhall.archive');
    Route::post('/townhall/{id}/unarchive', [TownHallController::class, 'unarchive'])->name('townhall.unarchive');
    Route::post('/townhall/{id}/acknowledge', [TownHallController::class, 'acknowledge'])->name('townhall.acknowledge');
    Route::get('/townhall/recipients/search', [TownHallController::class, 'searchRecipients'])
        ->name('townhall.recipients.search');
    Route::get('/townhall/{id}/email-approve', [TownHallController::class, 'approveFromEmail'])
        ->name('townhall.email.approve')
        ->middleware('signed');
    Route::get('/townhall/{id}/email-reject', [TownHallController::class, 'rejectFromEmail'])
        ->name('townhall.email.reject')
        ->middleware('signed');
    Route::get('/admin/town-hall/audit-trail', [TownHallController::class, 'auditTrail'])
        ->name('admin.townhall.audit-trail');
    Route::get('/admin/town-hall/acknowledgement-report', [TownHallController::class, 'acknowledgementReport'])
        ->name('admin.townhall.acknowledgement-report');

    /*
    |--------------------------------------------------------------------------
    | HUMAN CAPITAL MEMOS
    |--------------------------------------------------------------------------
    */
    Route::get('/human-capital/memos', [TownHallController::class, 'humanCapitalMemos'])
        ->name('human-capital.memos');


    /*
    |--------------------------------------------------------------------------
    | CONTACTS MODULE
    |--------------------------------------------------------------------------
    */
    Route::get('/contacts', [ContactsController::class, 'index'])->name('contacts.index');
    Route::post('/contacts', [ContactsController::class, 'store'])->name('contacts.store');
    Route::delete('/contacts/bulk-delete', [ContactsController::class, 'bulkDelete'])->name('contacts.bulk-delete');
    Route::match(['put', 'patch'], '/contacts/{contact}', [ContactsController::class, 'update'])->name('contacts.update');
    Route::post('/contacts/assign-owner', [ContactsController::class, 'assignOwner'])->name('contacts.assign-owner');
    Route::post('/contacts/custom-fields', [ContactsController::class, 'storeCustomField'])->name('contacts.custom-fields.store');
    Route::post('/contacts/{contact}/cif', [ContactsController::class, 'saveCif'])->name('contacts.cif.save');
    Route::post('/contacts/{contact}/cif/documents', [ContactsController::class, 'uploadCifDocument'])->name('contacts.cif.documents.upload');
    Route::post('/contacts/{contact}/kyc/requirements/upload', [ContactsController::class, 'uploadKycRequirementDocument'])->name('contacts.kyc.requirements.upload');
    Route::delete('/contacts/{contact}/kyc/requirements/{requirement}', [ContactsController::class, 'removeKycRequirementDocument'])->name('contacts.kyc.requirements.remove');
    Route::post('/contacts/{contact}/kyc/submit', [ContactsController::class, 'submitKycForVerification'])->name('contacts.kyc.submit');
    Route::post('/contacts/{contact}/kyc/approve', [ContactsController::class, 'approveKyc'])->name('contacts.kyc.approve');
    Route::post('/contacts/{contact}/kyc/reject', [ContactsController::class, 'rejectKyc'])->name('contacts.kyc.reject');
    Route::post('/contacts/{contact}/kyc/change-request', [ContactsController::class, 'requestKycChange'])->name('contacts.kyc.change-request');
    Route::post('/contacts/{contact}/kyc/change-request/approve', [ContactsController::class, 'approveKycChange'])->name('contacts.kyc.change-request.approve');
    Route::post('/contacts/{contact}/kyc/change-request/reject', [ContactsController::class, 'rejectKycChange'])->name('contacts.kyc.change-request.reject');
    Route::post('/contacts/{contact}/kyc/cif/send', [ContactsController::class, 'sendCifClientForm'])->name('contacts.cif.send');
    Route::post('/contacts/{contact}/kyc/specimen/send', [ContactsController::class, 'sendSpecimenClientForm'])->name('contacts.specimen.send');
    Route::post('/contacts/{contact}/consultation-notes', [ContactConsultationNoteController::class, 'store'])->name('contacts.consultation-notes.store');
    Route::match(['put', 'patch'], '/contacts/{contact}/consultation-notes/{note}', [ContactConsultationNoteController::class, 'update'])->name('contacts.consultation-notes.update');
    Route::delete('/contacts/{contact}/consultation-notes/{note}', [ContactConsultationNoteController::class, 'destroy'])->name('contacts.consultation-notes.destroy');
    Route::get('/contacts/{contact}/cif/preview', [ContactsController::class, 'previewCif'])->name('contacts.cif.preview');
    Route::get('/contacts/{contact}/cif/download', [ContactsController::class, 'downloadCif'])->name('contacts.cif.download');
    Route::delete('/contacts/{contact}/companies/{company}', [ContactsController::class, 'unlinkCompany'])->name('contacts.companies.unlink');
    Route::get('/contacts/{id}/kyc/specimen-signature', [ContactsController::class, 'specimenSignature'])->name('contacts.specimen-signature');
    Route::post('/contacts/{id}/kyc/specimen-signature', [ContactsController::class, 'saveSpecimenSignature'])->name('contacts.specimen-signature.save');
    Route::get('/contacts/{id}/kyc/specimen-signature/download', [ContactsController::class, 'downloadSpecimenSignature'])->name('contacts.specimen-signature.download');
    Route::get('/contacts/{contact}', [ContactsController::class, 'show'])->name('contacts.show');

    /*
    |--------------------------------------------------------------------------
    | DEALS MODULE
    |--------------------------------------------------------------------------
    */
    Route::get('/deals', [DealController::class, 'index'])->name('deals.index');
    Route::post('/deals/preview', [DealController::class, 'preview'])->name('deals.preview');
    Route::get('/deals/preview', [DealController::class, 'previewPage'])->name('deals.preview.show');
    Route::post('/deals/draft', [DealController::class, 'saveDraft'])->name('deals.draft');
    Route::post('/deals', [DealController::class, 'store'])->name('deals.store');
    Route::post('/deals/{id}/approve', [DealController::class, 'approve'])->name('deals.approve');
    Route::post('/deals/{id}/reject', [DealController::class, 'reject'])->name('deals.reject');
    Route::post('/deals/stages', [DealController::class, 'storeStage'])->name('deals.stages.store');
    Route::patch('/deals/stages/{stage}', [DealController::class, 'updateStage'])->name('deals.stages.update');
    Route::patch('/deals/stages/{stage}/move', [DealController::class, 'moveStage'])->name('deals.stages.move');
    Route::delete('/deals/stages/{stage}', [DealController::class, 'destroyStage'])->name('deals.stages.destroy');
    Route::patch('/deals/{id}/stage', [DealController::class, 'updateDealStage'])->name('deals.stage.update');
    Route::post('/deals/{id}/update-stage', [DealController::class, 'updateDealStage'])->name('deals.stage.update.post');
    Route::put('/deals/{id}', [DealController::class, 'update'])->name('deals.update');
    Route::get('/deals/{deal}/proposal', [DealProposalController::class, 'show'])->name('deals.proposal.show');
    Route::get('/deals/{deal}/proposal/preview-view', [DealProposalController::class, 'previewPage'])->name('deals.proposal.preview-page');
    Route::post('/deals/{deal}/proposal/preview', [DealProposalController::class, 'preview'])->name('deals.proposal.preview');
    Route::post('/deals/{deal}/proposal/send', [DealProposalController::class, 'sendClientProposal'])->name('deals.proposal.send');
    Route::get('/deals/{deal}/quotation/finance', [DealProposalController::class, 'financeQuotation'])->name('deals.quotation.finance');
    Route::post('/deals/{deal}/quotation/send-client', [DealProposalController::class, 'sendClientQuotation'])->name('deals.quotation.send-client');
    Route::post('/deals/{deal}/quotation/send-finance', [DealProposalController::class, 'sendFinanceQuotation'])->name('deals.quotation.send-finance');
    Route::post('/deals/{deal}/quotation/approve', [DealProposalController::class, 'approveQuotation'])->name('deals.quotation.approve');
    Route::post('/deals/{deal}/quotation/upload-finance', [DealProposalController::class, 'uploadFinanceQuotation'])->name('deals.quotation.upload-finance');
    Route::get('/deals/{deal}/invoice/payment', [DealProposalController::class, 'paymentInvoice'])->name('deals.invoice.payment');
    Route::post('/deals/{deal}/invoice/upload', [DealProposalController::class, 'uploadInvoice'])->name('deals.invoice.upload');
    Route::post('/deals/{deal}/payment/confirm', [DealProposalController::class, 'confirmPayment'])->name('deals.payment.confirm');
    Route::get('/deals/{deal}/proposal/download', [DealProposalController::class, 'download'])->name('deals.proposal.download');
    Route::match(['put', 'patch'], '/deals/{deal}/proposal', [DealProposalController::class, 'update'])->name('deals.proposal.update');
    Route::get('/deals/{id}/download', [DealController::class, 'downloadPdf'])->name('deals.download');
    Route::get('/deals/{id}/download-pdf', [DealController::class, 'downloadPdf'])->name('deals.download-pdf');
    Route::get('/deals/{id}', [DealController::class, 'show'])->name('deals.show');

    /*
    |--------------------------------------------------------------------------
    | PROJECT / REGULAR / PRODUCTS / SERVICES
    |--------------------------------------------------------------------------
    */
    Route::get('/project', [ProjectController::class, 'index'])->name('project.index');
    Route::post('/project/manual', [ProjectController::class, 'storeManual'])->name('project.manual.store');
    Route::get('/project/{project}', [ProjectController::class, 'show'])->name('project.show');
    Route::get('/project/{project}/start/download', [ProjectController::class, 'downloadStartPdf'])->name('project.start.download');
    Route::get('/project/{project}/service-memo/download', [ProjectController::class, 'downloadServiceMemoPdf'])->name('project.service-memo.download');
    Route::post('/project/{project}/start', [ProjectController::class, 'updateStart'])->name('project.start.update');
    Route::post('/project/{project}/start/submit', [ProjectController::class, 'submitStartForApproval'])->name('project.start.submit');
    Route::post('/project/{project}/start/approve', [ProjectController::class, 'approveStart'])->name('project.start.approve');
    Route::post('/project/{project}/start/reject', [ProjectController::class, 'rejectStart'])->name('project.start.reject');
    Route::post('/project/{project}/sow', [ProjectController::class, 'updateSow'])->name('project.sow.update');
    Route::post('/project/{project}/sow/manual-approve', [ProjectController::class, 'manualApproveSow'])->name('project.sow.manual-approve');
    Route::post('/project/{project}/sow/auto-report-settings', [ProjectController::class, 'updateSowAutoReportSettings'])->name('project.sow.auto-settings');
    Route::post('/project/{project}/sow/templates', [ProjectController::class, 'storeSowTemplate'])->name('project.sow.templates.store');
    Route::get('/project/{project}/sow/download', [ProjectController::class, 'downloadSowPdf'])->name('project.sow.download');
    Route::get('/project/{project}/coc/preview', [ProjectController::class, 'showCocPreview'])->name('project.coc.preview');
    Route::get('/project/{project}/coc/download', [ProjectController::class, 'downloadCocPdf'])->name('project.coc.download');
    Route::post('/project/{project}/coc/approve', [ProjectController::class, 'approveCoc'])->name('project.coc.approve');
    Route::get('/project/{project}/ntp/download', [ProjectController::class, 'downloadNtpPdf'])->name('project.ntp.download');
    Route::post('/project/{project}/ntp/manual-approve', [ProjectController::class, 'manualApproveNtp'])->name('project.ntp.manual-approve');
    Route::get('/project/{project}/ntp/status', [ProjectController::class, 'ntpStatus'])->name('project.ntp.status');
    Route::get('/project/{project}/ntp/submission', [ProjectController::class, 'showNtpSubmission'])->name('project.ntp.submission');
    Route::post('/project/{project}/sow/generate-report', [ProjectController::class, 'generateSowReport'])->name('project.sow.generate');
    Route::get('/project/{project}/report/{report}', [ProjectController::class, 'showGeneratedReport'])->name('project.report.preview');
    Route::post('/project/{project}/report/{report}/send', [ProjectController::class, 'sendGeneratedReport'])->name('project.report.send');
    Route::post('/project/{project}/report/{report}/manual-approve', [ProjectController::class, 'manualApproveReport'])->name('project.report.manual-approve');
    Route::delete('/project/{project}/report/bulk-delete', [ProjectController::class, 'bulkDestroyGeneratedReports'])->name('project.report.bulk-delete');
    Route::post('/project/{project}/report', [ProjectController::class, 'updateReport'])->name('project.report.update');

    Route::get('/regular', [RegularController::class, 'index'])->name('regular.index');
    Route::post('/regular/manual', [RegularController::class, 'storeManual'])->name('regular.manual.store');
    Route::get('/regular/{regular}', [RegularController::class, 'show'])->name('regular.show');
    Route::post('/regular/{regular}/rsat', [RegularController::class, 'updateRsat'])->name('regular.rsat.update');
    Route::post('/regular/{regular}/rsat/manual-approve', [RegularController::class, 'manualApproveRsat'])->name('regular.rsat.manual-approve');
    Route::post('/regular/{regular}/rsat/auto-report-settings', [RegularController::class, 'updateRsatAutoReportSettings'])->name('regular.rsat.auto-settings');
    Route::post('/regular/{regular}/rsat/templates', [RegularController::class, 'storeRsatTemplate'])->name('regular.rsat.templates.store');
    Route::post('/regular/{regular}/report/generate', [RegularController::class, 'generateReport'])->name('regular.report.generate');
    Route::get('/regular/{regular}/report/{report}', [RegularController::class, 'showGeneratedReport'])->name('regular.report.preview');
    Route::post('/regular/{regular}/report/{report}/send', [RegularController::class, 'sendGeneratedReport'])->name('regular.report.send');
    Route::post('/regular/{regular}/report/{report}/manual-approve', [RegularController::class, 'manualApproveReport'])->name('regular.report.manual-approve');
    Route::delete('/regular/{regular}/report/bulk-delete', [RegularController::class, 'bulkDestroyGeneratedReports'])->name('regular.report.bulk-delete');
    Route::post('/regular/{regular}/report', [RegularController::class, 'updateReport'])->name('regular.report.update');
    Route::get('/regular/{regular}/rsat/download', [RegularController::class, 'downloadRsatPdf'])->name('regular.rsat.download');
    Route::get('/regular/{regular}/ntp/download', [RegularController::class, 'downloadNtpPdf'])->name('regular.ntp.download');
    Route::get('/regular/{regular}/ntp/submission', [RegularController::class, 'showNtpSubmission'])->name('regular.ntp.submission');
    Route::post('/regular/{regular}/ntp/manual-approve', [RegularController::class, 'manualApproveNtp'])->name('regular.ntp.manual-approve');

    Route::get('/products', [ProductController::class, 'index'])->name('products.index');
    Route::post('/products', [ProductController::class, 'store'])->name('products.store');
    Route::match(['put', 'patch'], '/products/{id}', [ProductController::class, 'update'])->name('products.update');
    Route::delete('/products/{id}', [ProductController::class, 'destroy'])->name('products.destroy');
    Route::post('/products/{id}/approve', [ProductController::class, 'approve'])->name('products.approve');
    Route::post('/products/{id}/reject', [ProductController::class, 'reject'])->name('products.reject');
    Route::post('/products/change-owner', [ProductController::class, 'changeOwner'])->name('products.change-owner');
    Route::post('/products/custom-fields', [ProductController::class, 'storeCustomField'])->name('products.custom-fields.store');
    Route::get('/products/{id}', [ProductController::class, 'show'])->name('products.show');

    Route::get('/services', [CompanyServiceController::class, 'globalIndex'])->name('services.index');
    Route::post('/services', [CompanyServiceController::class, 'storeGlobal'])->name('services.store');
    Route::post('/services/custom-fields', [CompanyServiceController::class, 'storeCustomField'])->name('services.custom-fields.store');
    Route::post('/services/{service}/approve', [CompanyServiceController::class, 'approveGlobal'])->name('services.approve');
    Route::post('/services/{service}/reject', [CompanyServiceController::class, 'rejectGlobal'])->name('services.reject');
    Route::get('/services/{service}', [CompanyServiceController::class, 'showGlobal'])->name('services.show');
    Route::match(['put', 'patch'], '/services/{service}', [CompanyServiceController::class, 'updateGlobal'])->name('services.update');
    Route::delete('/services/{service}', [CompanyServiceController::class, 'destroyGlobal'])->name('services.destroy');

    /*
    |--------------------------------------------------------------------------
    | POLICIES MODULE
    |--------------------------------------------------------------------------
    */
    Route::get('/policies', [PolicyController::class, 'index'])->name('policies.index');
    Route::post('/policies', [PolicyController::class, 'store'])->name('policies.store');
    Route::get('/policies/preview-pdf', [PolicyController::class, 'previewPdf'])->name('policies.preview');
    Route::get('/policies/{id}', [PolicyController::class, 'show'])->name('policies.show');
    Route::get('/policies/{id}/edit', [PolicyController::class, 'edit'])->name('policies.edit');

    Route::get('/admin/policies', [PolicyController::class, 'submitted'])->name('admin.policies.index');
    Route::post('/admin/policies/{id}/approve', [PolicyController::class, 'approve'])->name('admin.policies.approve');
    Route::post('/admin/policies/{id}/reject', [PolicyController::class, 'reject'])->name('admin.policies.reject');
    Route::post('/admin/policies/{id}/revise', [PolicyController::class, 'revise'])->name('admin.policies.revise');
    Route::get('/admin/policies/{id}', [PolicyController::class, 'showAdmin'])->name('admin.policies.show');
    Route::post('/admin/policies/{id}/archive', [PolicyController::class, 'archive'])->name('admin.policies.archive');
    Route::post('/admin/policies/{id}/unarchive', [PolicyController::class, 'unarchive'])->name('admin.policies.unarchive');

    /*
    |--------------------------------------------------------------------------
    | COMPANY MODULE
    |--------------------------------------------------------------------------
    */
    Route::get('/company', [CompanyController::class, 'index'])->name('company.index');
    Route::get('/company/contacts/search', [CompanyController::class, 'searchRoleContacts'])->name('company.contacts.search');
    Route::post('/company', [CompanyController::class, 'store'])->name('company.store');
    Route::post('/company/custom-fields', [CompanyController::class, 'storeCustomField'])->name('company.custom-fields.store');
    Route::delete('/company/bulk-delete', [CompanyController::class, 'bulkDelete'])->name('company.bulk-delete');
    Route::match(['put', 'patch'], '/company/{company}', [CompanyController::class, 'update'])->name('company.update');
    Route::delete('/company/{company}', [CompanyController::class, 'destroy'])->name('company.destroy');
    Route::get('/company/{company}', [CompanyController::class, 'show'])->name('company.show');

    Route::get('/company/{company}/kyc', [CompanyKycController::class, 'index'])->name('company.kyc');
    Route::post('/company/{company}/kyc/submit', [CompanyKycController::class, 'submitKycForVerification'])->name('company.kyc.submit');
    Route::post('/company/{company}/kyc/approve', [CompanyKycController::class, 'approveKyc'])->name('company.kyc.approve');
    Route::post('/company/{company}/kyc/reject', [CompanyKycController::class, 'rejectKyc'])->name('company.kyc.reject');
    Route::get('/company/{company}/kyc/requirements/{requirement}/view', [CompanyKycController::class, 'viewRequirementDocument'])->name('company.kyc.requirements.view');
    Route::get('/company/{company}/kyc/requirements/{requirement}/template', [CompanyKycController::class, 'previewRequirementTemplate'])->name('company.kyc.requirements.template');
    Route::get('/company/{company}/kyc/requirements/{requirement}/template/download', [CompanyKycController::class, 'downloadRequirementTemplatePdf'])->name('company.kyc.requirements.template.download');
    Route::post('/company/{company}/kyc/requirements/{requirement}/upload', [CompanyKycController::class, 'uploadRequirementDocument'])->name('company.kyc.requirements.upload');
    Route::delete('/company/{company}/kyc/requirements/{requirement}', [CompanyKycController::class, 'removeRequirementDocument'])->name('company.kyc.requirements.remove');

    Route::get('/company/{company}/history', [CompanyController::class, 'history'])->name('company.history');

    Route::get('/company/{company}/consultation-notes', [CompanyController::class, 'consultationNotes'])->name('company.consultation-notes');
    Route::post('/company/{company}/consultation-notes', [CompanyConsultationNoteController::class, 'store'])->name('company.consultation-notes.store');
    Route::match(['put', 'patch'], '/company/{company}/consultation-notes/{note}', [CompanyConsultationNoteController::class, 'update'])->name('company.consultation-notes.update');
    Route::delete('/company/{company}/consultation-notes/{note}', [CompanyConsultationNoteController::class, 'destroy'])->name('company.consultation-notes.destroy');

    Route::get('/company/{company}/activities', [CompanyController::class, 'activities'])->name('company.activities');
    Route::post('/company/{company}/activities', [CompanyActivityController::class, 'store'])->name('company.activities.store');
    Route::match(['put', 'patch'], '/company/{company}/activities/{activity}', [CompanyActivityController::class, 'update'])->name('company.activities.update');
    Route::patch('/company/{company}/activities/{activity}/complete', [CompanyActivityController::class, 'complete'])->name('company.activities.complete');
    Route::delete('/company/{company}/activities/{activity}', [CompanyActivityController::class, 'destroy'])->name('company.activities.destroy');

    Route::get('/company/{company}/deals', [CompanyDealController::class, 'index'])->name('company.deals');
    Route::post('/company/{company}/deals', [CompanyDealController::class, 'store'])->name('company.deals.store');
    Route::get('/company/{company}/deals/{deal}', [CompanyDealController::class, 'show'])->name('company.deals.show');
    Route::match(['put', 'patch'], '/company/{company}/deals/{deal}', [CompanyDealController::class, 'update'])->name('company.deals.update');
    Route::delete('/company/{company}/deals/{deal}', [CompanyDealController::class, 'destroy'])->name('company.deals.destroy');

    Route::get('/company/{company}/contacts', [CompanyController::class, 'contacts'])->name('company.contacts');
    Route::post('/company/{company}/contacts', [CompanyController::class, 'storeContact'])->name('company.contacts.store');
    Route::post('/company/{company}/contacts/custom-fields', [CompanyController::class, 'storeContactCustomField'])->name('company.contacts.custom-fields.store');
    Route::match(['put', 'patch'], '/company/{company}/contacts/{contact}', [CompanyController::class, 'updateContact'])->name('company.contacts.update');
    Route::delete('/company/{company}/contacts/{contact}', [CompanyController::class, 'destroyContact'])->name('company.contacts.destroy');

    Route::get('/company/{company}/projects', [CompanyController::class, 'projects'])->name('company.projects');
    Route::get('/company/{company}/regular', [CompanyController::class, 'regular'])->name('company.regular');

    Route::get('/company/{company}/products', [CompanyProductController::class, 'index'])->name('company.products');
    Route::post('/company/{company}/products/link', [CompanyProductController::class, 'link'])->name('company.products.link');
    Route::post('/company/{company}/products', [CompanyProductController::class, 'store'])->name('company.products.store');
    Route::get('/company/{company}/products/{product}', [CompanyProductController::class, 'show'])->name('company.products.show');
    Route::match(['put', 'patch'], '/company/{company}/products/{product}', [CompanyProductController::class, 'update'])->name('company.products.update');
    Route::delete('/company/{company}/products/{product}', [CompanyProductController::class, 'unlink'])->name('company.products.unlink');

    Route::get('/company/{company}/lgu', [CompanyLguController::class, 'index'])->name('company.lgu');
    Route::post('/company/{company}/lgu', [CompanyLguController::class, 'store'])->name('company.lgu.store');
    Route::match(['put', 'patch'], '/company/{company}/lgu/{record}', [CompanyLguController::class, 'update'])->name('company.lgu.update');
    Route::delete('/company/{company}/lgu/{record}', [CompanyLguController::class, 'destroy'])->name('company.lgu.destroy');

    Route::get('/company/{company}/accounting', [CompanyAccountingController::class, 'index'])->name('company.accounting');
    Route::post('/company/{company}/accounting', [CompanyAccountingController::class, 'store'])->name('company.accounting.store');
    Route::match(['put', 'patch'], '/company/{company}/accounting/{record}', [CompanyAccountingController::class, 'update'])->name('company.accounting.update');
    Route::delete('/company/{company}/accounting/{record}', [CompanyAccountingController::class, 'destroy'])->name('company.accounting.destroy');

    Route::get('/company/{company}/banking', [CompanyBankingController::class, 'index'])->name('company.banking');
    Route::post('/company/{company}/banking', [CompanyBankingController::class, 'store'])->name('company.banking.store');
    Route::match(['put', 'patch'], '/company/{company}/banking/{record}', [CompanyBankingController::class, 'update'])->name('company.banking.update');
    Route::delete('/company/{company}/banking/{record}', [CompanyBankingController::class, 'destroy'])->name('company.banking.destroy');

    Route::get('/company/{company}/operations', [CompanyOperationsController::class, 'index'])->name('company.operations');
    Route::post('/company/{company}/operations', [CompanyOperationsController::class, 'store'])->name('company.operations.store');
    Route::match(['put', 'patch'], '/company/{company}/operations/{record}', [CompanyOperationsController::class, 'update'])->name('company.operations.update');
    Route::delete('/company/{company}/operations/{record}', [CompanyOperationsController::class, 'destroy'])->name('company.operations.destroy');

    Route::get('/company/{company}/correspondence', [CompanyCorrespondenceController::class, 'index'])->name('company.correspondence');
    Route::post('/company/{company}/correspondence', [CompanyCorrespondenceController::class, 'store'])->name('company.correspondence.store');
    Route::match(['put', 'patch'], '/company/{company}/correspondence/{record}', [CompanyCorrespondenceController::class, 'update'])->name('company.correspondence.update');
    Route::delete('/company/{company}/correspondence/{record}', [CompanyCorrespondenceController::class, 'destroy'])->name('company.correspondence.destroy');

    Route::get('/company/{company}/bir-tax', [CompanyBirTaxController::class, 'index'])->name('company.bir-tax');
    Route::post('/company/{company}/bir-tax', [CompanyBirTaxController::class, 'store'])->name('company.bir-tax.store');
    Route::match(['put', 'patch'], '/company/{company}/bir-tax/{record}', [CompanyBirTaxController::class, 'update'])->name('company.bir-tax.update');
    Route::delete('/company/{company}/bir-tax/{record}', [CompanyBirTaxController::class, 'destroy'])->name('company.bir-tax.destroy');

    Route::get('/company/{company}/kyc/bif/create', [CompanyBifController::class, 'create'])->name('company.bif.create');
    Route::post('/company/{company}/kyc/bif', [CompanyBifController::class, 'store'])->name('company.bif.store');
    Route::post('/company/{company}/kyc/bif/send', [CompanyBifController::class, 'sendClientForm'])->name('company.bif.send');
    Route::get('/company/{company}/kyc/bif/{bif}', [CompanyBifController::class, 'show'])->name('company.bif.show');
    Route::get('/company/{company}/kyc/bif/{bif}/edit', [CompanyBifController::class, 'edit'])->name('company.bif.edit');
    Route::match(['put', 'patch'], '/company/{company}/kyc/bif/{bif}', [CompanyBifController::class, 'update'])->name('company.bif.update');
    Route::post('/company/{company}/kyc/bif/{bif}/change-request/approve', [CompanyBifController::class, 'approveChangeRequest'])->name('company.bif.change-request.approve');
    Route::post('/company/{company}/kyc/bif/{bif}/change-request/reject', [CompanyBifController::class, 'rejectChangeRequest'])->name('company.bif.change-request.reject');
    Route::get('/company/{company}/kyc/bif/{bif}/print', [CompanyBifController::class, 'print'])->name('company.bif.print');

    Route::get('/company/{company}/services', [CompanyServiceController::class, 'companyIndex'])->name('company.services.index');
    Route::post('/company/{company}/services', [CompanyServiceController::class, 'storeForCompany'])->name('company.services.store');
    Route::get('/company/{company}/services/{service}', [CompanyServiceController::class, 'showForCompany'])->name('company.services.show');
    Route::match(['put', 'patch'], '/company/{company}/services/{service}', [CompanyServiceController::class, 'updateForCompany'])->name('company.services.update');
    Route::delete('/company/{company}/services/{service}', [CompanyServiceController::class, 'destroyForCompany'])->name('company.services.destroy');

    Route::get('/company/{company}/corporate-formation', [CompanyCorporateFormationController::class, 'index'])->name('company.corporate-formation');
    Route::get('/company/{company}/corporate-formation/company-general-information', [CompanyCorporateFormationController::class, 'companyGeneralInformation'])->name('company.corporate-formation.company-info');
    Route::get('/company/{company}/corporate-formation/sec-coi', [CompanyCorporateFormationController::class, 'secCoi'])->name('company.corporate-formation.sec-coi');
    Route::post('/company/{company}/corporate-formation/sec-coi', [CompanyCorporateFormationController::class, 'storeSecCoi'])->name('company.corporate-formation.sec-coi.store');
    Route::match(['put', 'patch'], '/company/{company}/corporate-formation/sec-coi/{record}', [CompanyCorporateFormationController::class, 'updateSecCoi'])->name('company.corporate-formation.sec-coi.update');
    Route::get('/company/{company}/corporate-formation/sec-aoi', [CompanyCorporateFormationController::class, 'secAoi'])->name('company.corporate-formation.sec-aoi');
    Route::post('/company/{company}/corporate-formation/sec-aoi', [CompanyCorporateFormationController::class, 'storeSecAoi'])->name('company.corporate-formation.sec-aoi.store');
    Route::match(['put', 'patch'], '/company/{company}/corporate-formation/sec-aoi/{record}', [CompanyCorporateFormationController::class, 'updateSecAoi'])->name('company.corporate-formation.sec-aoi.update');
    Route::get('/company/{company}/corporate-formation/bylaws', [CompanyCorporateFormationController::class, 'bylaws'])->name('company.corporate-formation.bylaws');
    Route::post('/company/{company}/corporate-formation/bylaws', [CompanyCorporateFormationController::class, 'storeBylaw'])->name('company.corporate-formation.bylaws.store');
    Route::match(['put', 'patch'], '/company/{company}/corporate-formation/bylaws/{record}', [CompanyCorporateFormationController::class, 'updateBylaw'])->name('company.corporate-formation.bylaws.update');
    Route::get('/company/{company}/corporate-formation/gis', [CompanyCorporateFormationController::class, 'gis'])->name('company.corporate-formation.gis');
    Route::get('/company/{company}/corporate-formation/notices', [CompanyCorporateRecordController::class, 'notices'])->name('company.corporate-formation.notices');
    Route::post('/company/{company}/corporate-formation/notices', [CompanyCorporateRecordController::class, 'storeNotice'])->name('company.corporate-formation.notices.store');
    Route::get('/company/{company}/corporate-formation/notices/{notice}', [CompanyCorporateRecordController::class, 'showNotice'])->name('company.corporate-formation.notices.preview');
    Route::match(['put', 'patch'], '/company/{company}/corporate-formation/notices/{notice}', [CompanyCorporateRecordController::class, 'updateNotice'])->name('company.corporate-formation.notices.update');
    Route::delete('/company/{company}/corporate-formation/notices/{notice}', [CompanyCorporateRecordController::class, 'destroyNotice'])->name('company.corporate-formation.notices.destroy');
    Route::post('/company/{company}/corporate-formation/notices/{notice}/send', [CompanyCorporateRecordController::class, 'sendNotice'])->name('company.corporate-formation.notices.send');
    Route::get('/company/{company}/corporate-formation/minutes', [CompanyCorporateRecordController::class, 'minutes'])->name('company.corporate-formation.minutes');
    Route::post('/company/{company}/corporate-formation/minutes', [CompanyCorporateRecordController::class, 'storeMinute'])->name('company.corporate-formation.minutes.store');
    Route::get('/company/{company}/corporate-formation/minutes/{minute}', [CompanyCorporateRecordController::class, 'showMinute'])->name('company.corporate-formation.minutes.preview');
    Route::match(['put', 'patch'], '/company/{company}/corporate-formation/minutes/{minute}', [CompanyCorporateRecordController::class, 'updateMinute'])->name('company.corporate-formation.minutes.update');
    Route::post('/company/{company}/corporate-formation/minutes/{minute}/approve', [CompanyCorporateRecordController::class, 'approveMinute'])->name('company.corporate-formation.minutes.approve');
    Route::post('/company/{company}/corporate-formation/minutes/{minute}/workspace-save', [CompanyCorporateRecordController::class, 'saveMinuteWorkspace'])->name('company.corporate-formation.minutes.workspace-save');
    Route::post('/company/{company}/corporate-formation/minutes/{minute}/final-audio', [CompanyCorporateRecordController::class, 'saveMinuteFinalRecording'])->name('company.corporate-formation.minutes.final-audio');
    Route::post('/company/{company}/corporate-formation/minutes/{minute}/final-save', [CompanyCorporateRecordController::class, 'saveMinuteFinalPreview'])->name('company.corporate-formation.minutes.final-save');
    Route::delete('/company/{company}/corporate-formation/minutes/{minute}', [CompanyCorporateRecordController::class, 'destroyMinute'])->name('company.corporate-formation.minutes.destroy');
    Route::get('/company/{company}/corporate-formation/resolutions', [CompanyCorporateRecordController::class, 'resolutions'])->name('company.corporate-formation.resolutions');
    Route::post('/company/{company}/corporate-formation/resolutions', [CompanyCorporateRecordController::class, 'storeResolution'])->name('company.corporate-formation.resolutions.store');
    Route::get('/company/{company}/corporate-formation/resolutions/{resolution}', [CompanyCorporateRecordController::class, 'showResolution'])->name('company.corporate-formation.resolutions.preview');
    Route::match(['put', 'patch'], '/company/{company}/corporate-formation/resolutions/{resolution}', [CompanyCorporateRecordController::class, 'updateResolution'])->name('company.corporate-formation.resolutions.update');
    Route::delete('/company/{company}/corporate-formation/resolutions/{resolution}', [CompanyCorporateRecordController::class, 'destroyResolution'])->name('company.corporate-formation.resolutions.destroy');
    Route::get('/company/{company}/corporate-formation/secretary-certificates', [CompanyCorporateRecordController::class, 'secretaryCertificates'])->name('company.corporate-formation.secretary-certificates');
    Route::post('/company/{company}/corporate-formation/secretary-certificates', [CompanyCorporateRecordController::class, 'storeSecretaryCertificate'])->name('company.corporate-formation.secretary-certificates.store');
    Route::get('/company/{company}/corporate-formation/secretary-certificates/{certificate}', [CompanyCorporateRecordController::class, 'showSecretaryCertificate'])->name('company.corporate-formation.secretary-certificates.preview');
    Route::match(['put', 'patch'], '/company/{company}/corporate-formation/secretary-certificates/{certificate}', [CompanyCorporateRecordController::class, 'updateSecretaryCertificate'])->name('company.corporate-formation.secretary-certificates.update');
    Route::delete('/company/{company}/corporate-formation/secretary-certificates/{certificate}', [CompanyCorporateRecordController::class, 'destroySecretaryCertificate'])->name('company.corporate-formation.secretary-certificates.destroy');
    Route::post('/company/{company}/corporate-formation/gis', [CompanyCorporateFormationController::class, 'storeGis'])->name('company.corporate-formation.gis.store');
    Route::match(['put', 'patch'], '/company/{company}/corporate-formation/gis/{record}', [CompanyCorporateFormationController::class, 'updateGis'])->name('company.corporate-formation.gis.update');

    // Company corporate-formation â€” show / upload-draft / upload-notary / submit (all 4 document types)
    Route::get('/company/{company}/corporate-formation/sec-coi/{record}', [CompanyCorporateFormationController::class, 'showSecCoi'])->name('company.corporate-formation.sec-coi.show');
    Route::post('/company/{company}/corporate-formation/sec-coi/{record}/upload-draft', [CompanyCorporateFormationController::class, 'uploadDraftSecCoi'])->name('company.corporate-formation.sec-coi.upload-draft');
    Route::post('/company/{company}/corporate-formation/sec-coi/{record}/upload-notary', [CompanyCorporateFormationController::class, 'uploadNotarySecCoi'])->name('company.corporate-formation.sec-coi.upload-notary');
    Route::post('/company/{company}/corporate-formation/sec-coi/{record}/submit', [CompanyCorporateFormationController::class, 'submitSecCoi'])->name('company.corporate-formation.sec-coi.submit');

    Route::get('/company/{company}/corporate-formation/sec-aoi/{record}', [CompanyCorporateFormationController::class, 'showSecAoi'])->name('company.corporate-formation.sec-aoi.show');
    Route::post('/company/{company}/corporate-formation/sec-aoi/{record}/upload-draft', [CompanyCorporateFormationController::class, 'uploadDraftSecAoi'])->name('company.corporate-formation.sec-aoi.upload-draft');
    Route::post('/company/{company}/corporate-formation/sec-aoi/{record}/upload-notary', [CompanyCorporateFormationController::class, 'uploadNotarySecAoi'])->name('company.corporate-formation.sec-aoi.upload-notary');
    Route::post('/company/{company}/corporate-formation/sec-aoi/{record}/submit', [CompanyCorporateFormationController::class, 'submitSecAoi'])->name('company.corporate-formation.sec-aoi.submit');

    Route::get('/company/{company}/corporate-formation/bylaws/{record}', [CompanyCorporateFormationController::class, 'showBylaw'])->name('company.corporate-formation.bylaws.show');
    Route::post('/company/{company}/corporate-formation/bylaws/{record}/upload-draft', [CompanyCorporateFormationController::class, 'uploadDraftBylaw'])->name('company.corporate-formation.bylaws.upload-draft');
    Route::post('/company/{company}/corporate-formation/bylaws/{record}/upload-notary', [CompanyCorporateFormationController::class, 'uploadNotaryBylaw'])->name('company.corporate-formation.bylaws.upload-notary');
    Route::post('/company/{company}/corporate-formation/bylaws/{record}/submit', [CompanyCorporateFormationController::class, 'submitBylaw'])->name('company.corporate-formation.bylaws.submit');

    Route::get('/company/{company}/corporate-formation/gis/{record}', [CompanyCorporateFormationController::class, 'showGis'])->name('company.corporate-formation.gis.show');
    Route::post('/company/{company}/corporate-formation/gis/{record}/upload-draft', [CompanyCorporateFormationController::class, 'uploadDraftGis'])->name('company.corporate-formation.gis.upload-draft');
    Route::post('/company/{company}/corporate-formation/gis/{record}/upload-notary', [CompanyCorporateFormationController::class, 'uploadNotaryGis'])->name('company.corporate-formation.gis.upload-notary');
    Route::post('/company/{company}/corporate-formation/gis/{record}/submit', [CompanyCorporateFormationController::class, 'submitGis'])->name('company.corporate-formation.gis.submit');

    /*
    |--------------------------------------------------------------------------
    | CORPORATE CORE MODULE
    |--------------------------------------------------------------------------
    */
    Route::get('/corporate', [GisController::class, 'companyInfo'])->name('corporate');
    Route::get('/corporate/company-general-information', [GisController::class, 'companyInfo'])->name('corporate.companyinfo');

    Route::get('/corporate/gis', [GisController::class, 'index'])->name('corporate.gis');
    Route::post('/corporate/gis/store', [GisController::class, 'store'])->name('gis.store');
    Route::get('/corporate/gis/{id}/show', [GisController::class, 'show'])->name('gis.show');
    Route::get('/corporate/gis/{id}/company-info', [GisController::class, 'companyInfoById'])->name('gis.company.info');
    Route::put('/gis/company-info/{id}', [GisController::class, 'updateCompanyInfo'])->name('gis.company.update');
    Route::get('/corporate/gis/capital-structure', [GisController::class, 'capitalStructure'])->name('gis.capital');
    Route::get('/corporate/gis/directors-officers', [GisController::class, 'directorsOfficers'])->name('gis.directors');
    Route::get('/corporate/gis/stockholders', [GisController::class, 'stockholders'])->name('gis.stockholders');

    Route::post('/gis/authorized/store', [CapitalStructureController::class, 'storeAuthorized'])->name('authorized.store');
    Route::post('/gis/subscribed/store', [CapitalStructureController::class, 'storeSubscribed'])->name('subscribed.store');
    Route::post('/gis/paidup/store', [CapitalStructureController::class, 'storePaidup'])->name('paidup.store');
    Route::post('/gis/director/store', [DirectorOfficerController::class, 'store'])->name('director.store');
    Route::post('/gis/stockholder/store', [StockholderController::class, 'store'])->name('stockholder.store');
    Route::post('/gis/ubo/store', [UltimateBeneficialOwnerController::class, 'store'])->name('ubo.store');

    Route::put('/gis/authorized/{record}', [CapitalStructureController::class, 'updateAuthorized'])->name('authorized.update');
    Route::delete('/gis/authorized/{record}', [CapitalStructureController::class, 'destroyAuthorized'])->name('authorized.destroy');

    Route::put('/gis/subscribed/{record}', [CapitalStructureController::class, 'updateSubscribed'])->name('subscribed.update');
    Route::delete('/gis/subscribed/{record}', [CapitalStructureController::class, 'destroySubscribed'])->name('subscribed.destroy');

    Route::put('/gis/paidup/{record}', [CapitalStructureController::class, 'updatePaidup'])->name('paidup.update');
    Route::delete('/gis/paidup/{record}', [CapitalStructureController::class, 'destroyPaidup'])->name('paidup.destroy');

    Route::put('/gis/director/{record}', [DirectorOfficerController::class, 'update'])->name('director.update');
    Route::delete('/gis/director/{record}', [DirectorOfficerController::class, 'destroy'])->name('director.destroy');

    Route::put('/gis/stockholder/{record}', [StockholderController::class, 'update'])->name('stockholder.update');
    Route::delete('/gis/stockholder/{record}', [StockholderController::class, 'destroy'])->name('stockholder.destroy');

    Route::put('/gis/ubo/{record}', [UltimateBeneficialOwnerController::class, 'update'])->name('ubo.update');
    Route::delete('/gis/ubo/{record}', [UltimateBeneficialOwnerController::class, 'destroy'])->name('ubo.destroy');


    Route::post('/corporate/gis/{id}/upload-draft-file', [GisController::class, 'uploadDraftFile'])->name('corporate.gis.upload.draft');
    Route::post('/corporate/gis/{id}/upload-notary-file', [GisController::class, 'uploadNotaryFile'])->name('corporate.gis.upload.notary');
    Route::post('/corporate/gis/{id}/submit', [GisController::class, 'submit'])->name('corporate.gis.submit');

    Route::get('/corporate/formation', [CorporateFormationController::class, 'index'])->name('corporate.formation');
    Route::post('/corporate/formation/store', [CorporateFormationController::class, 'store'])->name('corporate.formation.store');
    Route::get('/corporate/formation/{id}', [CorporateFormationController::class, 'show'])->name('corporate.formation.show');
    Route::post('/corporate/formation/{id}/upload-draft-file', [CorporateFormationController::class, 'uploadDraftFile'])->name('corporate.formation.upload.draft');
    Route::post('/corporate/formation/{id}/upload-notary-file', [CorporateFormationController::class, 'uploadNotaryFile'])->name('corporate.formation.upload.notary');
    Route::post('/corporate/formation/{id}/submit', [CorporateFormationController::class, 'submit'])->name('corporate.formation.submit');
    Route::put('/corporate/formation/{id}/update', [CorporateFormationController::class, 'update'])->name('corporate.formation.update');

    Route::get('/corporate/sec-aoi', [SecAoiController::class, 'index'])->name('corporate.sec_aoi');
    Route::post('/corporate/sec-aoi/store', [SecAoiController::class, 'store'])->name('corporate.sec_aoi.store');
    Route::get('/corporate/sec-aoi/{id}', [SecAoiController::class, 'show'])->name('corporate.sec_aoi.show');
    Route::post('/corporate/sec-aoi/{id}/upload-draft-file', [SecAoiController::class, 'uploadDraftFile'])->name('corporate.sec_aoi.upload.draft');
    Route::post('/corporate/sec-aoi/{id}/upload-notary-file', [SecAoiController::class, 'uploadNotaryFile'])->name('corporate.sec_aoi.upload.notary');
    Route::post('/corporate/sec-aoi/{id}/submit', [SecAoiController::class, 'submit'])->name('corporate.sec_aoi.submit');

    Route::get('/corporate/bylaws', [BylawController::class, 'index'])->name('corporate.bylaws');
    Route::post('/corporate/bylaws/store', [BylawController::class, 'store'])->name('corporate.bylaws.store');
    Route::get('/corporate/bylaws/{id}', [BylawController::class, 'show'])->name('corporate.bylaws.show');
    Route::post('/corporate/bylaws/{id}/upload-draft-file', [BylawController::class, 'uploadDraftFile'])->name('corporate.bylaws.upload.draft');
    Route::post('/corporate/bylaws/{id}/upload-notary-file', [BylawController::class, 'uploadNotaryFile'])->name('corporate.bylaws.upload.notary');
    Route::post('/corporate/bylaws/{id}/submit', [BylawController::class, 'submit'])->name('corporate.bylaws.submit');

    Route::view('/corporate/lgu', 'corporate.lgu')->name('corporate.lgu');
    Route::view('/corporate/accounting', 'corporate.accounting')->name('corporate.accounting');
    Route::view('/corporate/banking', 'corporate.banking')->name('corporate.banking');
    Route::view('/corporate/legal', 'corporate.legal')->name('corporate.legal');
    Route::view('/corporate/operations', 'corporate.operations')->name('corporate.operations');
    Route::view('/corporate/correspondence', 'corporate.correspondence')->name('corporate.correspondence');
    Route::view('/corporate/ubo', 'corporate.ubo-form')->name('corporate.ubo');

    /*
    |--------------------------------------------------------------------------
    | CORPORATE NAV FALLBACK / REDIRECT ROUTES
    |--------------------------------------------------------------------------
    */
    Route::redirect('/accounting', '/corporate/accounting')->name('accounting');
    Route::redirect('/banking', '/corporate/banking')->name('banking');
    Route::redirect('/legal', '/corporate/legal')->name('legal');
    Route::redirect('/operations', '/corporate/operations')->name('operations');
    Route::redirect('/correspondence', '/corporate/correspondence')->name('correspondence');

    /*
    |--------------------------------------------------------------------------
    | STOCK TRANSFER BOOK MODULE
    |--------------------------------------------------------------------------
    */
    Route::redirect('/stock-transfer-book', '/stock-transfer-book/index')->name('stock-transfer-book');
    Route::get('/stock-transfer-book/index', [StockTransferLedgerController::class, 'indexPage'])->name('stock-transfer-book.index');

    Route::get('/stock-transfer-book/ledger', [StockTransferLedgerController::class, 'index'])->name('stock-transfer-book.ledger');
    Route::get('/stock-transfer-book/ledger/create', [StockTransferLedgerController::class, 'create'])->name('stock-transfer-book.ledger.create');
    Route::post('/stock-transfer-book/ledger', [StockTransferLedgerController::class, 'store'])->name('stock-transfer-book.ledger.store');
    Route::get('/stock-transfer-book/ledger/{stockTransferLedger}', [StockTransferLedgerController::class, 'show'])->name('stock-transfer-book.ledger.show');
    Route::get('/stock-transfer-book/ledger/{stockTransferLedger}/edit', [StockTransferLedgerController::class, 'edit'])->name('stock-transfer-book.ledger.edit');
    Route::put('/stock-transfer-book/ledger/{stockTransferLedger}', [StockTransferLedgerController::class, 'update'])->name('stock-transfer-book.ledger.update');
    Route::delete('/stock-transfer-book/ledger/{stockTransferLedger}', [StockTransferLedgerController::class, 'destroy'])->name('stock-transfer-book.ledger.destroy');

    Route::get('/stock-transfer-book/journal', [StockTransferJournalController::class, 'index'])->name('stock-transfer-book.journal');
    Route::get('/stock-transfer-book/journal/create', [StockTransferJournalController::class, 'create'])->name('stock-transfer-book.journal.create');
    Route::post('/stock-transfer-book/journal', [StockTransferJournalController::class, 'store'])->name('stock-transfer-book.journal.store');
    Route::get('/stock-transfer-book/journal/{stockTransferJournal}', [StockTransferJournalController::class, 'show'])->name('stock-transfer-book.journal.show');
    Route::get('/stock-transfer-book/journal/{stockTransferJournal}/edit', [StockTransferJournalController::class, 'edit'])->name('stock-transfer-book.journal.edit');
    Route::put('/stock-transfer-book/journal/{stockTransferJournal}', [StockTransferJournalController::class, 'update'])->name('stock-transfer-book.journal.update');
    Route::delete('/stock-transfer-book/journal/{stockTransferJournal}', [StockTransferJournalController::class, 'destroy'])->name('stock-transfer-book.journal.destroy');

    Route::get('/stock-transfer-book/installment', [StockTransferInstallmentController::class, 'index'])->name('stock-transfer-book.installment');
    Route::get('/stock-transfer-book/installment/create', [StockTransferInstallmentController::class, 'create'])->name('stock-transfer-book.installment.create');
    Route::post('/stock-transfer-book/installment', [StockTransferInstallmentController::class, 'store'])->name('stock-transfer-book.installment.store');
    Route::get('/stock-transfer-book/installment/{stockTransferInstallment}', [StockTransferInstallmentController::class, 'show'])->name('stock-transfer-book.installment.show');
    Route::post('/stock-transfer-book/installment/{stockTransferInstallment}/payments', [StockTransferInstallmentController::class, 'recordPreviewPayment'])->name('stock-transfer-book.installment.payments.store');
    Route::get('/stock-transfer-book/installment/{stockTransferInstallment}/edit', [StockTransferInstallmentController::class, 'edit'])->name('stock-transfer-book.installment.edit');
    Route::put('/stock-transfer-book/installment/{stockTransferInstallment}', [StockTransferInstallmentController::class, 'update'])->name('stock-transfer-book.installment.update');
    Route::post('/stock-transfer-book/installment/{stockTransferInstallment}/cancel', [StockTransferInstallmentController::class, 'cancelInstallment'])->name('stock-transfer-book.installment.cancel');
    Route::delete('/stock-transfer-book/installment/{stockTransferInstallment}', [StockTransferInstallmentController::class, 'destroy'])->name('stock-transfer-book.installment.destroy');

    Route::get('/stock-transfer-book/certificates', [StockTransferCertificateController::class, 'index'])->name('stock-transfer-book.certificates');
    Route::get('/stock-transfer-book/certificates/create', [StockTransferCertificateController::class, 'create'])->name('stock-transfer-book.certificates.create');
    Route::post('/stock-transfer-book/certificates', [StockTransferCertificateController::class, 'store'])->name('stock-transfer-book.certificates.store');
    Route::get('/stock-transfer-book/certificates/{stockTransferCertificate}/template', [StockTransferCertificateController::class, 'templatePreview'])->name('stock-transfer-book.certificates.template');
    Route::get('/stock-transfer-book/certificates/{stockTransferCertificate}', [StockTransferCertificateController::class, 'show'])->name('stock-transfer-book.certificates.show');
    Route::post('/stock-transfer-book/certificates/{stockTransferCertificate}/issue', [StockTransferCertificateController::class, 'issue'])->name('stock-transfer-book.certificates.issue');
    Route::get('/stock-transfer-book/certificates/{stockTransferCertificate}/edit', [StockTransferCertificateController::class, 'edit'])->name('stock-transfer-book.certificates.edit');
    Route::put('/stock-transfer-book/certificates/{stockTransferCertificate}', [StockTransferCertificateController::class, 'update'])->name('stock-transfer-book.certificates.update');
    Route::delete('/stock-transfer-book/certificates/{stockTransferCertificate}', [StockTransferCertificateController::class, 'destroy'])->name('stock-transfer-book.certificates.destroy');
    Route::post('/stock-transfer-book/certificates/requests', [StockTransferCertificateController::class, 'storeRequest'])->name('stock-transfer-book.certificates.requests.store');
    Route::get('/stock-transfer-book/certificates/requests/{stockTransferIssuanceRequest}', [StockTransferCertificateController::class, 'showRequest'])->name('stock-transfer-book.certificates.requests.show');
    Route::post('/stock-transfer-book/certificates/requests/{stockTransferIssuanceRequest}/approve', [StockTransferCertificateController::class, 'approveRequest'])->name('stock-transfer-book.certificates.requests.approve');

    Route::get('/stock-transfer-book/lookup', [StockTransferLookupController::class, 'lookup'])->name('stock-transfer-book.lookup');
    Route::get('/stock-transfer-book/defaults', [StockTransferLookupController::class, 'defaults'])->name('stock-transfer-book.defaults');

    /*
    |--------------------------------------------------------------------------
    | CORPORATE DOCUMENT DEFAULTS
    |--------------------------------------------------------------------------
    */
    Route::get('/corporate-document-defaults', CorporateDocumentDefaultsController::class)->name('corporate-document-defaults');

    /*
    |--------------------------------------------------------------------------
    | BIR TAX / NATGOV MODULES
    |--------------------------------------------------------------------------
    */
    Route::get('/bir-tax', [BirTaxController::class, 'index'])->name('bir-tax');
    Route::get('/bir-tax/create', [BirTaxController::class, 'create'])->name('bir-tax.create');
    Route::post('/bir-tax', [BirTaxController::class, 'store'])->name('bir-tax.store');
    Route::get('/bir-tax/{birTax}', [BirTaxController::class, 'show'])->name('bir-tax.preview');
    Route::get('/bir-tax/{birTax}/edit', [BirTaxController::class, 'edit'])->name('bir-tax.edit');
    Route::put('/bir-tax/{birTax}', [BirTaxController::class, 'update'])->name('bir-tax.update');
    Route::post('/bir-tax/{birTax}/authority-notes', [BirTaxController::class, 'storeAuthorityNote'])->name('bir-tax.notes.store');
    Route::delete('/bir-tax/{birTax}', [BirTaxController::class, 'destroy'])->name('bir-tax.destroy');

    Route::get('/natgov', [NatGovController::class, 'index'])->name('natgov');
    Route::get('/natgov/create', [NatGovController::class, 'create'])->name('natgov.create');
    Route::post('/natgov', [NatGovController::class, 'store'])->name('natgov.store');
    Route::get('/natgov/{natgov}', [NatGovController::class, 'show'])->name('natgov.preview');
    Route::get('/natgov/{natgov}/edit', [NatGovController::class, 'edit'])->name('natgov.edit');
    Route::put('/natgov/{natgov}', [NatGovController::class, 'update'])->name('natgov.update');
    Route::post('/natgov/{natgov}/authority-notes', [NatGovController::class, 'storeAuthorityNote'])->name('natgov.notes.store');
    Route::delete('/natgov/{natgov}', [NatGovController::class, 'destroy'])->name('natgov.destroy');

    /*
    |--------------------------------------------------------------------------
    | CORPORATE DOCUMENT MODULES
    |--------------------------------------------------------------------------
    */
    Route::get('/corporate/notices', [NoticeController::class, 'index'])->name('notices');
    Route::get('/corporate/notices/create', [NoticeController::class, 'create'])->name('notices.create');
    Route::post('/corporate/notices', [NoticeController::class, 'store'])->name('notices.store');
    Route::get('/corporate/notices/{notice}/download', [NoticeController::class, 'downloadPdf'])->name('notices.download');
    Route::get('/corporate/notices/{notice}', [NoticeController::class, 'show'])->name('notices.preview');
    Route::get('/corporate/notices/{notice}/edit', [NoticeController::class, 'edit'])->name('notices.edit');
    Route::put('/corporate/notices/{notice}', [NoticeController::class, 'update'])->name('notices.update');
    Route::delete('/corporate/notices/{notice}', [NoticeController::class, 'destroy'])->name('notices.destroy');
    Route::post('/corporate/notices/{notice}/send', [NoticeController::class, 'sendNotice'])->name('notices.send');

    Route::get('/corporate/minutes', [MinuteController::class, 'index'])->name('minutes');
    Route::get('/corporate/minutes/create', [MinuteController::class, 'create'])->name('minutes.create');
    Route::post('/corporate/minutes', [MinuteController::class, 'store'])->name('minutes.store');
    Route::get('/corporate/minutes/{minute}', [MinuteController::class, 'show'])->name('minutes.preview');
    Route::get('/corporate/minutes/{minute}/edit', [MinuteController::class, 'edit'])->name('minutes.edit');
    Route::put('/corporate/minutes/{minute}', [MinuteController::class, 'update'])->name('minutes.update');
    Route::post('/corporate/minutes/{minute}/approve', [MinuteController::class, 'approve'])->name('minutes.approve');
    Route::post('/corporate/minutes/{minute}/workspace-save', [MinuteController::class, 'saveWorkspace'])->name('minutes.workspace-save');
    Route::post('/corporate/minutes/{minute}/final-audio', [MinuteController::class, 'saveFinalRecording'])->name('minutes.final-audio');
    Route::post('/corporate/minutes/{minute}/final-save', [MinuteController::class, 'saveFinalPreview'])->name('minutes.final-save');
    Route::delete('/corporate/minutes/{minute}', [MinuteController::class, 'destroy'])->name('minutes.destroy');

    Route::get('/corporate/resolutions', [ResolutionController::class, 'index'])->name('resolutions');
    Route::get('/corporate/resolutions/create', [ResolutionController::class, 'create'])->name('resolutions.create');
    Route::post('/corporate/resolutions', [ResolutionController::class, 'store'])->name('resolutions.store');
    Route::get('/corporate/resolutions/{resolution}/download', [ResolutionController::class, 'downloadPdf'])->name('resolutions.download');
    Route::get('/corporate/resolutions/{resolution}', [ResolutionController::class, 'show'])->name('resolutions.preview');
    Route::get('/corporate/resolutions/{resolution}/edit', [ResolutionController::class, 'edit'])->name('resolutions.edit');
    Route::put('/corporate/resolutions/{resolution}', [ResolutionController::class, 'update'])->name('resolutions.update');
    Route::delete('/corporate/resolutions/{resolution}', [ResolutionController::class, 'destroy'])->name('resolutions.destroy');

    Route::get('/corporate/secretary-certificates', [SecretaryCertificateController::class, 'index'])->name('secretary-certificates');
    Route::get('/corporate/secretary-certificates/create', [SecretaryCertificateController::class, 'create'])->name('secretary-certificates.create');
    Route::post('/corporate/secretary-certificates', [SecretaryCertificateController::class, 'store'])->name('secretary-certificates.store');
    Route::get('/corporate/secretary-certificates/{secretaryCertificate}', [SecretaryCertificateController::class, 'show'])->name('secretary-certificates.preview');
    Route::get('/corporate/secretary-certificates/{secretaryCertificate}/edit', [SecretaryCertificateController::class, 'edit'])->name('secretary-certificates.edit');
    Route::put('/corporate/secretary-certificates/{secretaryCertificate}', [SecretaryCertificateController::class, 'update'])->name('secretary-certificates.update');
    Route::delete('/corporate/secretary-certificates/{secretaryCertificate}', [SecretaryCertificateController::class, 'destroy'])->name('secretary-certificates.destroy');

    /*
    |--------------------------------------------------------------------------
    | PERMITS / LGU MODULE
    |--------------------------------------------------------------------------
    */
    Route::get('/permits/template/mayors-permit/{id}', [PermitController::class, 'showMayorPermitTemplate'])->name('permits.template.mayors-permit');
    Route::get('/permits/template/barangay-business-permit/{id}', [PermitController::class, 'showBarangayBusinessPermitTemplate'])->name('permits.template.barangay-business-permit');
    Route::get('/permits/template/fire-permit/{id}', [PermitController::class, 'showFirePermitTemplate'])->name('permits.template.fire-permit');
    Route::get('/permits/template/sanitary-permit/{id}', [PermitController::class, 'showSanitaryPermitTemplate'])->name('permits.template.sanitary-permit');
    Route::get('/permits/template/obo-permit/{id}', [PermitController::class, 'showOboPermitTemplate'])->name('permits.template.obo-permit');

    Route::get('/corporate/lgu', [PermitController::class, 'page'])->name('corporate.lgu');
    Route::get('/permits', [PermitController::class, 'index'])->name('permits.index');
    Route::post('/permits', [PermitController::class, 'store'])->name('permits.store');
    Route::get('/permits/{id}', [PermitController::class, 'show'])->name('permits.show');
    Route::put('/permits/{id}/update', [PermitController::class, 'update'])->name('permits.update');
    Route::post('/permits/{id}/upload-document', [PermitController::class, 'uploadDocument'])->name('permits.upload.document');
    Route::post('/permits/{id}/submit', [PermitController::class, 'submit'])->name('permits.submit');

    /*
    |--------------------------------------------------------------------------
    | CORRESPONDENCE / LEGAL / ACCOUNTING / BANKING / OPERATIONS
    |--------------------------------------------------------------------------
    */
    Route::get('/correspondence/data', [CorrespondenceController::class, 'index'])->name('correspondence.data');
    Route::post('/correspondence', [CorrespondenceController::class, 'store'])->name('correspondence.store');
    Route::get('/correspondence/{id}', [CorrespondenceController::class, 'show'])->name('correspondence.show');
    Route::put('/correspondence/{id}/update', [CorrespondenceController::class, 'update'])->name('correspondence.update');
    Route::post('/correspondence/{id}/submit', [CorrespondenceController::class, 'submit'])->name('correspondence.submit');
    Route::get('/correspondence/draft-preview/{slug}', [CorrespondenceController::class, 'showDraftPreview'])->name('correspondence.draft-preview');
    Route::get('/correspondence/template/{slug}/{id}', [CorrespondenceController::class, 'showTemplate'])->name('correspondence.template');

    Route::get('/legal/data', [LegalController::class, 'index'])->name('legal.index');
    Route::post('/legal/store', [LegalController::class, 'store'])->name('legal.store');
    Route::get('/legal/{id}', [LegalController::class, 'show'])->name('legal.show');
    Route::put('/legal/{id}/update', [LegalController::class, 'update'])->name('legal.update');
    Route::post('/legal/{id}/submit', [LegalController::class, 'submit'])->name('legal.submit');

    Route::prefix('corporate')->name('corporate.')->group(function () {
        Route::get('/accounting', [AccountingController::class, 'page'])->name('accounting.index');
        Route::get('/accounting/data', [AccountingController::class, 'index'])->name('accounting.data');
        Route::post('/accounting', [AccountingController::class, 'store'])->name('accounting.store');
        Route::get('/accounting/{id}', [AccountingController::class, 'show'])->name('accounting.show');
        Route::put('/accounting/{id}/update', [AccountingController::class, 'update'])->name('accounting.update');
        Route::post('/accounting/{id}/submit', [AccountingController::class, 'submit'])->name('accounting.submit');
    });

    Route::get('/finance', [FinanceController::class, 'index'])->name('finance');
    Route::post('/finance/dropdown-settings', [FinanceController::class, 'updateDropdownSettings'])->name('finance.dropdown-settings.update');
    Route::get('/finance/{financeRecord}', [FinanceController::class, 'show'])->name('finance.show');
    Route::get('/finance/{financeRecord}/preview-html', [FinanceController::class, 'previewHtml'])->name('finance.preview.html');
    Route::get('/finance/{financeRecord}/preview-pdf', [FinanceController::class, 'previewPdf'])->name('finance.preview.pdf');
    Route::post('/finance', [FinanceController::class, 'store'])->name('finance.store');
    Route::put('/finance/{financeRecord}', [FinanceController::class, 'update'])->name('finance.update');
    Route::post('/finance/{financeRecord}/submit', [FinanceController::class, 'submit'])->name('finance.submit');
    Route::post('/finance/{financeRecord}/approve', [FinanceController::class, 'approve'])->name('finance.approve');
    Route::post('/finance/{financeRecord}/revert', [FinanceController::class, 'revert'])->name('finance.revert');
    Route::post('/finance/{financeRecord}/archive', [FinanceController::class, 'archive'])->name('finance.archive');
    Route::post('/finance/{financeRecord}/request-delete', [FinanceController::class, 'requestDelete'])->name('finance.delete.request');
    Route::post('/finance/{financeRecord}/share-supplier-link', [FinanceController::class, 'shareSupplierLink'])->name('finance.supplier.share');
    Route::post('/finance/{financeRecord}/supplier-email', [FinanceController::class, 'updateSupplierEmailAndResend'])->name('finance.supplier.email');

    Route::get('/finance/supplier/completion/{token}', [FinanceController::class, 'supplierCompletionForm'])
        ->name('finance.supplier.completion');
    Route::post('/finance/supplier/completion/{token}', [FinanceController::class, 'submitSupplierCompletion'])
        ->name('finance.supplier.completion.submit');

    Route::get('/banking/data', [BankingController::class, 'index'])->name('banking.index');
    Route::post('/banking/store', [BankingController::class, 'store'])->name('banking.store');
    Route::get('/banking/{id}', [BankingController::class, 'show'])->name('banking.show');
    Route::put('/banking/{id}/update', [BankingController::class, 'update'])->name('banking.update');
    Route::post('/banking/{id}/submit', [BankingController::class, 'submit'])->name('banking.submit');

    Route::get('/operations/data', [OperationController::class, 'index'])->name('operations.index');
    Route::post('/operations/store', [OperationController::class, 'store'])->name('operations.store');
    Route::get('/operations/{id}', [OperationController::class, 'show'])->name('operations.show');
    Route::put('/operations/{id}/update', [OperationController::class, 'update'])->name('operations.update');
    Route::post('/operations/{id}/submit', [OperationController::class, 'submit'])->name('operations.submit');

    /*
    |--------------------------------------------------------------------------
    | MISC CORPORATE FALLBACK VIEWS
    |--------------------------------------------------------------------------
    */
    Route::get('/lgu', function () {
        return view('corporate.lgu');
    })->name('lgu');

    /*
    |--------------------------------------------------------------------------
    | TRANSMITTAL MODULE
    |--------------------------------------------------------------------------
    */
    Route::get('/transmittal', [TransmittalController::class, 'index'])->name('transmittal.index');
    Route::get('/transmittal/create/project/{project}', [TransmittalController::class, 'createFromProject'])->name('transmittal.create.project');
    Route::get('/transmittal/create/regular/{regular}', [TransmittalController::class, 'createFromRegular'])->name('transmittal.create.regular');
    Route::get('/transmittal/data', [TransmittalController::class, 'data'])->name('transmittal.data');
    Route::post('/transmittal', [TransmittalController::class, 'store'])->name('transmittal.store');
    Route::post('/transmittal/{id}/submit', [TransmittalController::class, 'submit'])->name('transmittal.submit');
    Route::get('/transmittal-receipts/{id}', [TransmittalReceiptController::class, 'show'])->name('transmittal.receipts.show');
    Route::get('/transmittal/{transmittal}/preview', [TransmittalController::class, 'preview'])->name('transmittal.preview');
    Route::get('/transmittal/{transmittal}/preview-pdf', [TransmittalController::class, 'previewPdf'])->name('transmittal.preview.pdf');
    Route::get('/transmittal/{transmittal}/receipt-pdf', [TransmittalController::class, 'receiptPdf'])->name('transmittal.receipt.pdf');

    /*
    |--------------------------------------------------------------------------
    | SALES & MARKETING MODULE
    |--------------------------------------------------------------------------
    */
    Route::get('/sales-marketing', [SalesMarketingController::class, 'index'])->name('sales-marketing.index');

    Route::get('/sales-marketing/earners', [SalesMarketingEarnerController::class, 'index'])->name('sales-marketing.earners.index');
    Route::post('/sales-marketing/earners', [SalesMarketingEarnerController::class, 'store'])->name('sales-marketing.earners.store');
    Route::get('/sales-marketing/earners/{earner}', [SalesMarketingEarnerController::class, 'show'])->name('sales-marketing.earners.show');
    Route::put('/sales-marketing/earners/{earner}', [SalesMarketingEarnerController::class, 'update'])->name('sales-marketing.earners.update');
    Route::delete('/sales-marketing/earners/{earner}', [SalesMarketingEarnerController::class, 'destroy'])->name('sales-marketing.earners.destroy');

    Route::get('/sales-marketing/ida', [SalesMarketingIdaController::class, 'index'])->name('sales-marketing.ida.index');
    Route::post('/sales-marketing/ida', [SalesMarketingIdaController::class, 'store'])->name('sales-marketing.ida.store');
    Route::get('/sales-marketing/ida/{ida}', [SalesMarketingIdaController::class, 'show'])->name('sales-marketing.ida.show');
    Route::put('/sales-marketing/ida/{ida}', [SalesMarketingIdaController::class, 'update'])
        ->name('sales-marketing.ida.update');

    Route::delete('/sales-marketing/ida/{ida}', [SalesMarketingIdaController::class, 'destroy'])
        ->name('sales-marketing.ida.destroy');
    Route::patch('/sales-marketing/ida/{ida}/submit', [SalesMarketingIdaController::class, 'submit'])
        ->name('sales-marketing.ida.submit');

    Route::patch('/sales-marketing/ida/{ida}/accept', [SalesMarketingIdaController::class, 'accept'])
        ->name('sales-marketing.ida.accept');

    Route::patch('/sales-marketing/ida/{ida}/revert', [SalesMarketingIdaController::class, 'revert'])
        ->name('sales-marketing.ida.revert');
    Route::post('/sales-marketing/earners/{earner}/request-payout', [SalesMarketingPayoutController::class, 'requestPayout'])
        ->name('sales-marketing.earners.request-payout');
    Route::get('/sales-marketing/payouts', [SalesMarketingPayoutController::class, 'index'])
        ->name('sales-marketing.payouts.index');
    Route::patch('/sales-marketing/payouts/{allocation}/mark-paid', [SalesMarketingPayoutController::class, 'markPaid'])
        ->name('sales-marketing.payouts.mark-paid');


    /*
    |--------------------------------------------------------------------------
    | ASSESSMENT QUESTIONNAIRE EDITOR
    |--------------------------------------------------------------------------
    */
    Route::middleware($adminOrSuperAdmin)
        ->prefix('human-capital/recruitment/assessment-questionnaire-editor')
        ->group(function () {
            Route::get('/', [AssessmentQuestionController::class, 'index'])
                ->name('assessment-questions.index');

            Route::post('/types', [AssessmentQuestionController::class, 'storeType'])
                ->name('assessment-types.store');

            Route::put('/types/{type}', [AssessmentQuestionController::class, 'updateType'])
                ->name('assessment-types.update');

            Route::delete('/types/{type}', [AssessmentQuestionController::class, 'destroyType'])
                ->name('assessment-types.destroy');

            Route::post('/questions', [AssessmentQuestionController::class, 'store'])
                ->name('assessment-questions.store');

            Route::post('/questions/reorder', [AssessmentQuestionController::class, 'reorder'])
                ->name('assessment-questions.reorder');

            Route::post('/questions/{question}/move-up', [AssessmentQuestionController::class, 'moveUp'])
                ->name('assessment-questions.move-up');

            Route::post('/questions/{question}/move-down', [AssessmentQuestionController::class, 'moveDown'])
                ->name('assessment-questions.move-down');

            Route::put('/questions/{question}', [AssessmentQuestionController::class, 'update'])
                ->name('assessment-questions.update');

            Route::delete('/questions/{question}', [AssessmentQuestionController::class, 'destroy'])
                ->name('assessment-questions.destroy');
        });

    /*
    |--------------------------------------------------------------------------
    | HUMAN CAPITAL MODULE
    |--------------------------------------------------------------------------
    */
    Route::prefix('human-capital')->name('human-capital.')->group(function () use ($adminOrSuperAdmin) {
        Route::get('/', function () {
            $user = Auth::user();

            return redirect()->route(
                ($user->isAdmin() || $user->isSuperAdmin())
                    ? 'human-capital.organizational'
                    : 'human-capital.attendance'
            );
        })->name('dashboard');

        Route::middleware($adminOrSuperAdmin)->group(function () {
            Route::get('/organizational', [OrganizationalController::class, 'index'])->name('organizational');
            Route::post('/organizational', [OrganizationalController::class, 'store'])->name('organizational.store');
            Route::put('/organizational/{type}/{id}', [OrganizationalController::class, 'update'])->name('organizational.update');
            Route::delete('/organizational/{type}/{id}', [OrganizationalController::class, 'destroy'])->name('organizational.destroy');
        });

        Route::prefix('/organizational/locations')->name('organizational.locations.')->middleware($adminOrSuperAdmin)->group(function () {
            Route::get('/regions', [PhilippineLocationController::class, 'regions'])->name('regions');
            Route::get('/provinces-or-districts/{regionCode}', [PhilippineLocationController::class, 'provincesOrDistricts'])->name('provinces-or-districts');
            Route::get('/cities-municipalities/{type}/{code}', [PhilippineLocationController::class, 'citiesMunicipalities'])->name('cities-municipalities');
            Route::get('/barangays/{cityCode}', [PhilippineLocationController::class, 'barangays'])->name('barangays');
        });



        /*
        |--------------------------------------------------------------------------
        | PAYROLL
        |--------------------------------------------------------------------------
        */
        Route::middleware($adminOrSuperAdmin)->group(function () {
            Route::get('/payroll', [PayrollController::class, 'index'])->name('payroll');
            Route::post('/payroll/salary-grades', [PayrollController::class, 'storeSalaryGrade'])->name('payroll.salary-grades.store');
            Route::post('/payroll/levels', [PayrollController::class, 'storePayrollLevel'])->name('payroll.levels.store');
            Route::post('/payroll/benefits', [PayrollController::class, 'storeBenefit'])->name('payroll.benefits.store');
            Route::post('/payroll/allowances', [PayrollController::class, 'storeAllowance'])->name('payroll.allowances.store');
            Route::post('/payroll/deductions', [PayrollController::class, 'storeDeduction'])->name('payroll.deductions.store');
            Route::post('/payroll/holidays', [PayrollController::class, 'storeHoliday'])->name('payroll.holidays.store');
            Route::post('/payroll/periods', [PayrollController::class, 'storePayrollPeriod'])->name('payroll.periods.store');
            Route::post('/payroll/profiles', [PayrollController::class, 'storeEmployeeProfile'])->name('payroll.profiles.store');
            Route::post('/payroll/generate-summary', [PayrollController::class, 'generateSummary'])->name('payroll.generate-summary');
            Route::get('/payroll/payslip/{summary}', [PayrollController::class, 'showPayslip'])->name('payroll.payslip.show');
        });

        /*
        |--------------------------------------------------------------------------
        | EMPLOYEE PROFILE
        |--------------------------------------------------------------------------
        */
        Route::middleware($adminOrSuperAdmin)->group(function () {
            Route::get('/employee-profile', [EmployeeController::class, 'index'])->name('employee-profile');
            Route::post('/employee-profile', [EmployeeController::class, 'store'])->name('employee-profile.store');
            Route::put('/employee-profile/{employee}', [EmployeeController::class, 'update'])->name('employee-profile.update');
        });

        /*
        |--------------------------------------------------------------------------
        | RECRUITMENT
        |--------------------------------------------------------------------------
        */
        Route::middleware($adminOrSuperAdmin)->group(function () {
            Route::get('/recruitment', [RecruitmentController::class, 'index'])->name('recruitment');

            Route::post('/recruitment/mrf', [RecruitmentController::class, 'storeMRF'])->name('recruitment.store_mrf');
            Route::put('/recruitment/mrf/{id}', [RecruitmentController::class, 'updateMRF'])->name('recruitment.update_mrf');
            Route::post('/recruitment/mrf/{id}/approve', [RecruitmentController::class, 'approveMRF'])->name('recruitment.approve_mrf');
            Route::post('/recruitment/mrf/{id}/cancel', [RecruitmentController::class, 'cancelMRF'])->name('recruitment.cancel_mrf');
            Route::delete('/recruitment/mrf/{id}', [RecruitmentController::class, 'deleteMRF'])->name('recruitment.delete_mrf');

            Route::post('/recruitment/jpf', [RecruitmentController::class, 'storeJPF'])->name('recruitment.store_jpf');
            Route::put('/recruitment/jpf/{id}', [RecruitmentController::class, 'updateJPF'])->name('recruitment.update_jpf');
            Route::delete('/recruitment/jpf/{id}', [RecruitmentController::class, 'deleteJPF'])->name('recruitment.delete_jpf');
            Route::post('/recruitment/jpf/{id}/approval/{level}', [RecruitmentController::class, 'actOnJPFApproval'])->name('recruitment.jpf.approval');

            Route::post('/recruitment/caf', [RecruitmentController::class, 'storeCAF'])->name('recruitment.store_caf');
            Route::put('/recruitment/caf/{id}', [RecruitmentController::class, 'updateCAF'])->name('recruitment.update_caf');
            Route::post('/recruitment/caf/{id}/proceed', [RecruitmentController::class, 'proceedToAssessment'])->name('recruitment.proceed_to_assessment');
            Route::delete('/recruitment/caf/{id}', [RecruitmentController::class, 'deleteCAF'])->name('recruitment.delete_caf');

            Route::post('/recruitment/assessment', [RecruitmentController::class, 'storeAssessment'])->name('recruitment.store_assessment');
            Route::post('/recruitment/assessment/{id}/status', [RecruitmentController::class, 'updateAssessmentStatus'])->name('recruitment.update_assessment_status');
            Route::get('/recruitment/assessment/latest', [RecruitmentController::class, 'latestAssessments'])->name('recruitment.assessment.latest');
            Route::post('/recruitment/assessment/{id}/send-test', [RecruitmentController::class, 'sendAssessmentTest'])->name('recruitment.send_assessment_test');
            Route::post('/recruitment/assessment/{id}/result', [RecruitmentController::class, 'updateAssessmentResult'])->name('recruitment.update_assessment_result');
            Route::delete('/recruitment/assessment/{id}', [RecruitmentController::class, 'deleteAssessment'])->name('recruitment.delete_assessment');

            Route::post('/recruitment/interview', [RecruitmentController::class, 'storeInterview'])->name('recruitment.store_interview');
            Route::delete('/recruitment/interview/{id}', [RecruitmentController::class, 'deleteInterview'])->name('recruitment.delete_interview');
            Route::post('/recruitment/interview/{id}/status', [RecruitmentController::class, 'updateInterviewStatus'])
                ->name('recruitment.interview_status');

            Route::get('/recruitment/job-offer/latest', [RecruitmentController::class, 'latestJobOffers'])->name('recruitment.job_offer.latest');
            Route::post('/recruitment/job-offer', [RecruitmentController::class, 'storeJobOffer'])->name('recruitment.store_job_offer');
            Route::post('/recruitment/job-offer/{id}/resend-email', [RecruitmentController::class, 'resendJobOfferEmail'])->name('recruitment.resend_job_offer_email');
            Route::delete('/recruitment/job-offer/{id}', [RecruitmentController::class, 'deleteJobOffer'])->name('recruitment.delete_job_offer');
        });

        /*
        |--------------------------------------------------------------------------
        | ONBOARDING
        |--------------------------------------------------------------------------
        */
        Route::middleware($adminOrSuperAdmin)->group(function () {
            Route::get('/onboarding', [RecruitmentController::class, 'onboarding'])->name('onboarding');
            Route::delete('/onboarding/pds/{id}', [RecruitmentController::class, 'deletePDS'])->name('onboarding.pds.delete');

            Route::get('/onboarding/records', [OnboardingRecordController::class, 'records'])->name('onboarding.records');
            Route::post('/onboarding/checklists', [OnboardingRecordController::class, 'storeChecklist'])->name('onboarding.checklists.store');
            Route::patch('/onboarding/checklists/{checklist}/review-document', [OnboardingRecordController::class, 'reviewChecklistDocument'])->name('onboarding.checklists.review-document');
            Route::delete('/onboarding/checklists/{checklist}', [OnboardingRecordController::class, 'destroyChecklist'])->name('onboarding.checklists.destroy');
            Route::post('/onboarding/employees', [OnboardingRecordController::class, 'storeEmployee'])->name('onboarding.employees.store');
            Route::delete('/onboarding/employees/{employee}', [OnboardingRecordController::class, 'destroyEmployee'])->name('onboarding.employees.destroy');
            Route::post('/onboarding/trainings', [OnboardingRecordController::class, 'storeTraining'])->name('onboarding.trainings.store');
            Route::patch('/onboarding/trainings/{training}/status', [OnboardingRecordController::class, 'updateTrainingStatus'])->name('onboarding.trainings.status');
            Route::delete('/onboarding/trainings/{training}', [OnboardingRecordController::class, 'destroyTraining'])->name('onboarding.trainings.destroy');

            Route::get('/deployment', [DeploymentController::class, 'index'])->name('deployment');
            Route::post('/deployment', [DeploymentController::class, 'store'])->name('deployment.store');
            Route::put('/deployment/{deployment}', [DeploymentController::class, 'update'])->name('deployment.update');
            Route::delete('/deployment/{deployment}', [DeploymentController::class, 'destroy'])->name('deployment.destroy');
        });
        Route::get('/attendance', [AttendanceController::class, 'index'])->name('attendance');
        Route::get('/attendance/export-pdf', [AttendanceController::class, 'exportPdf'])->name('attendance.export-pdf');
        Route::post('/attendance/clock', [AttendanceController::class, 'clock'])->name('attendance.clock');
        Route::put('/attendance/{attendance}', [AttendanceController::class, 'update'])->name('attendance.update');
        Route::patch('/attendance/{attendance}/approve', [AttendanceController::class, 'approve'])->name('attendance.approve');
        Route::patch('/attendance/{attendance}/reject', [AttendanceController::class, 'reject'])->name('attendance.reject');
        Route::get('/employee-relations', [EmployeeRelationController::class, 'index'])->name('employee-relations');
        Route::post('/employee-relations', [EmployeeRelationController::class, 'store'])->name('employee-relations.store');
        Route::put('/employee-relations/{employeeRelation}', [EmployeeRelationController::class, 'update'])->name('employee-relations.update');
        Route::post('/employee-relations/{employeeRelation}/approve', [EmployeeRelationController::class, 'approve'])->name('employee-relations.approve');
        Route::post('/employee-relations/{employeeRelation}/reject', [EmployeeRelationController::class, 'reject'])->name('employee-relations.reject');
        Route::delete('/employee-relations/{employeeRelation}', [EmployeeRelationController::class, 'destroy'])->name('employee-relations.destroy');


        // Performance Management
        Route::get('/performance', [PerformanceController::class, 'index'])->name('performance');
        Route::get('/performance/employee/{id}', [PerformanceController::class, 'getEmployee'])->name('performance.get-employee');
        Route::post('/performance/evaluation', [PerformanceController::class, 'storeEvaluation'])->name('performance.evaluation.store');
        Route::put('/performance/evaluation/{id}', [PerformanceController::class, 'updateEvaluation'])->name('performance.evaluation.update');
        Route::delete('/performance/evaluation/{id}', [PerformanceController::class, 'destroyEvaluation'])->name('performance.evaluation.destroy');
        Route::post('/performance/pip', [PerformanceController::class, 'storePIP'])->name('performance.pip.store');
        Route::put('/performance/pip/{id}', [PerformanceController::class, 'updatePIP'])->name('performance.pip.update');
        Route::delete('/performance/pip/{id}', [PerformanceController::class, 'destroyPIP'])->name('performance.pip.destroy');

        Route::middleware($adminOrSuperAdmin)->group(function () {
            Route::get('/offboarding', [OffboardingController::class, 'index'])->name('offboarding');
            Route::post('/offboarding', [OffboardingController::class, 'store'])->name('offboarding.store');
            Route::put('/offboarding/{offboardingRecord}', [OffboardingController::class, 'update'])->name('offboarding.update');
            Route::delete('/offboarding/{offboardingRecord}', [OffboardingController::class, 'destroy'])->name('offboarding.destroy');
        });

        Route::get('/obf', [OfficialBusinessTripController::class, 'index'])->name('obf');
        Route::post('/obf', [OfficialBusinessTripController::class, 'store'])->name('obf.store');
        Route::put('/obf/{officialBusinessTrip}', [OfficialBusinessTripController::class, 'update'])->name('obf.update');
        Route::delete('/obf/{officialBusinessTrip}', [OfficialBusinessTripController::class, 'destroy'])->name('obf.destroy');
        Route::post('/obf/{officialBusinessTrip}/approve', [OfficialBusinessTripController::class, 'approve'])->name('obf.approve');
        Route::post('/obf/{officialBusinessTrip}/reject', [OfficialBusinessTripController::class, 'reject'])->name('obf.reject');



        // Training
        Route::get('/training', [TrainingController::class, 'index'])->name('training');
        Route::middleware($adminOrSuperAdmin)->group(function () {
            Route::post('/training', [TrainingController::class, 'store'])->name('training.store');
            Route::put('/training/{training}', [TrainingController::class, 'update'])->name('training.update');
            Route::delete('/training/{training}', [TrainingController::class, 'destroy'])->name('training.destroy');

            // Assignment actions
            Route::post('/training/assignment/{id}/complete', [TrainingController::class, 'markCompleted'])
                ->name('training.complete');

            Route::post('/training/assignment/{id}/certificate', [TrainingController::class, 'issueCertificate'])
                ->name('training.certificate');
        });

        // Awards page
        Route::get('/awards', [AwardController::class, 'index'])->name('awards');

        /*
        |--------------------------------------------------------------------------
        | HUMAN RESOURCE EMPLOYEE REQUESTS
        |--------------------------------------------------------------------------
        */

        Route::post('/employee-requests/{employeeRequest}/approve', [EmployeeRequestController::class, 'approve'])
            ->name('employee-requests.approve');

        Route::post('/employee-requests/{employeeRequest}/reject', [EmployeeRequestController::class, 'reject'])
            ->name('employee-requests.reject');

        Route::post('/employee-requests/{employeeRequest}/revise', [EmployeeRequestController::class, 'revise'])
            ->name('employee-requests.revise');

        Route::post('/employee-requests/{employeeRequest}/update-revision', [EmployeeRequestController::class, 'updateRevision'])
            ->name('employee-requests.update-revision');

        Route::get('/employee-requests', [EmployeeRequestController::class, 'index'])
            ->name('employee-requests.index');

        Route::post('/employee-requests', [EmployeeRequestController::class, 'store'])
            ->name('employee-requests.store');
    });
});
