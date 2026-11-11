const assert = require('node:assert/strict');
const fs = require('node:fs');

const view = fs.readFileSync('resources/views/human-capital/obf.blade.php', 'utf8');
const formPartial = fs.readFileSync('resources/views/human-capital/partials/obf-create-form.blade.php', 'utf8');

function expression(source, pattern, label) {
    const found = source.match(pattern);
    assert.ok(found, `${label} must be present in the rendered Blade template`);
    return found[1];
}

const v3Preview = expression(view, /class="obv3-preview-content" x-show="([^"]+)"/, 'V3 preview');
const legacyPreview = expression(view, /<div x-show="([^"]+)" x-cloak>\s*<div class="section-title">\s*Client & Billability Section/, 'legacy preview');
const legacyForm = expression(view, /<fieldset x-show="([^"]+)" :disabled="[^"]+" x-cloak>/, 'legacy form');
const v3Chooser = expression(formPartial, /<section class="form-section obv3-section" x-show="([^"]+)" x-cloak>/, 'V3 classification chooser');
const v3Details = expression(formPartial, /<div x-show="(form\.classification)" x-cloak>/, 'V3 detail sections');

function visible(condition, state) {
    return Boolean(Function('form', 'isView', 'isEdit', `return ${condition};`)(
        state.form, state.isView, state.isEdit
    ));
}

const cases = [
    {
        label: 'legacy Pending view',
        state: {form: {workflow_version: 1, classification: '', status: 'Pending', destination: 'Client office'}, isView: true, isEdit: false},
        expected: [false, true, false, false, false],
    },
    {
        label: 'legacy Pending edit',
        state: {form: {workflow_version: 1, classification: '', status: 'Pending', destination: 'Client office'}, isView: false, isEdit: true},
        expected: [false, true, true, false, false],
    },
    {
        label: 'legacy Approved view',
        state: {form: {workflow_version: 1, classification: null, status: 'Approved', destination: 'Approved trip'}, isView: true, isEdit: false},
        expected: [false, true, false, false, false],
    },
    {
        label: 'V3 Draft edit',
        state: {form: {workflow_version: 3, classification: 'client', status: 'Draft'}, isView: false, isEdit: true},
        expected: [true, false, false, true, true],
    },
    {
        label: 'V3 Approved view',
        state: {form: {workflow_version: 3, classification: 'internal', status: 'Approved'}, isView: true, isEdit: false},
        expected: [true, false, false, true, true],
    },
    {
        label: 'new O.B. before classification',
        state: {form: {workflow_version: 3, classification: ''}, isView: false, isEdit: false},
        expected: [false, false, false, true, false],
    },
];

for (const {label, state, expected} of cases) {
    assert.deepEqual(
        [v3Preview, legacyPreview, legacyForm, v3Chooser, v3Details].map(condition => visible(condition, state)),
        expected,
        label
    );
}

assert.match(view, /name="destination"[\s\S]*?x-model="form\.destination"/, 'legacy destination stays rendered');
assert.match(view, /x-model="form\.purpose"/, 'legacy purpose stays rendered');
assert.match(formPartial, /Related Engagement \/ No Engagement \(required to submit\)/, 'V3 engagement control stays rendered');

console.log('O.B. legacy/V3 visibility regression passed: 6 rendered-state cases and preserved form controls.');
