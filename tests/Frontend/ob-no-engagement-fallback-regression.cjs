const assert = require('node:assert/strict');
const fs = require('node:fs');

const view = fs.readFileSync(
    'resources/views/human-capital/obf.blade.php',
    'utf8'
);

const partial = fs.readFileSync(
    'resources/views/human-capital/partials/obf-create-form.blade.php',
    'utf8'
);

const service = fs.readFileSync(
    'app/Services/OfficialBusinessCreateDetailService.php',
    'utf8'
);

const engagementService = fs.readFileSync(
    'app/Services/OfficialBusinessEngagementService.php',
    'utf8'
);

const detailService = fs.readFileSync(
    'app/Services/OfficialBusinessDetailService.php',
    'utf8'
);

function check(name, condition) {
    assert.ok(condition, name);
    console.log('[PASS] ' + name);
}

check(
    'Fallback option is selectable',
    partial.includes('value="no-engagement"')
);

check(
    'Fallback appears only after loading finishes',
    partial.includes('!engagementsLoading')
);

check(
    'Fallback requires an empty engagement list',
    partial.includes('engagements.length === 0')
);

check(
    'Fallback requires a selected company or contact',
    partial.includes('detailDraft.company_id || detailDraft.contact_id')
);

check(
    'Fallback is saved in frontend state',
    view.includes('this.detailDraft.no_engagement_selected = true')
);

check(
    'Fallback is cleared when selecting an engagement',
    view.includes('this.detailDraft.no_engagement_selected = false')
);

check(
    'Fallback is cleared when switching client or type',
    (view.match(/this\.detailDraft\.no_engagement_selected = false/g) || []).length >= 4
);

check(
    'Loading begins before fetching engagements',
    view.includes('this.engagementsLoading = true;')
);

check(
    'Successful lookup finishes loading',
    view.includes('this.engagementsLoading = false;')
);

check(
    'Backend accepts boolean fallback flag',
    service.includes("'rows.*.no_engagement_selected' => ['nullable', 'boolean']")
);

check(
    'Backend rejects fallback when eligible engagements exist',
    service.includes('$eligible->isNotEmpty()')
);

check(
    'Backend uses company eligibility',
    service.includes('$engagements->eligible($companyId, $engagementType)')
);

check(
    'Backend uses contact eligibility',
    service.includes('$engagements->eligibleForContact($contactId, $engagementType)')
);

check(
    'Backend rejects fallback combined with engagement IDs',
    service.includes('filled($projectId)') &&
    service.includes('filled($legacyDealId)')
);

check(
    'Backend retains validated fallback flag',
    service.includes("$row['no_engagement_selected'] = $fallback")
);

check(
    'Engagement service excludes shell workspaces',
    engagementService.includes('|| $workspace->isShell()')
);

check(
    'Detail persistence includes fallback flag',
    detailService.includes("'no_engagement_selected'")
);

console.log(
    '\nO.B. No Engagement fallback source regression passed.'
);
