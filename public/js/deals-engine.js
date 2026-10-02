
    /* =========================================================
       STAGE FUNCTIONAL WORKFLOW STATE & ENGINE
       ========================================================= */
    const WF_STAGES = [
        'Inquiry',
        'Qualification',
        'Consultation',
        'Proposal',
        'Negotiation',
        'Payment',
        'Activation',
        'Closed Won',
        'Closed Lost'
    ];

    const WF_SEQUENTIAL = [
        'Inquiry',
        'Qualification',
        'Consultation',
        'Proposal',
        'Negotiation',
        'Payment',
        'Activation'
    ];

    let wfCurrentDealStage = (window.DEAL_BOOTSTRAP?.currentStage ?? "Inquiry");
    let wfActiveViewingStage = wfCurrentDealStage;
    const wfDealId = (window.DEAL_BOOTSTRAP?.dealId ?? 0);
    const wfSaveUrl = (window.DEAL_BOOTSTRAP?.routes?.stageWorkflowSave ?? "");
    const wfCsrfToken = (document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || "");

    function getStageIndex(stageName) {
        const idx = WF_STAGES.indexOf(stageName);
        return idx === -1 ? 0 : idx;
    }

    function getSequentialIndex(stageName) {
        const idx = WF_SEQUENTIAL.indexOf(stageName);
        return idx === -1 ? (['Closed Won', 'Closed Lost'].includes(stageName) ? 7 : 0) : idx;
    }

    function isStageCompleted(stageName) {
        if (['Closed Won', 'Closed Lost'].includes(wfCurrentDealStage)) {
            if (['Closed Won', 'Closed Lost'].includes(stageName)) {
                return stageName === wfCurrentDealStage;
            }
            return true;
        }
        const currentSeqIdx = getSequentialIndex(wfCurrentDealStage);
        const targetSeqIdx = getSequentialIndex(stageName);
        return targetSeqIdx < currentSeqIdx;
    }

    function isStageCurrent(stageName) {
        return stageName === wfCurrentDealStage;
    }

    function isStageLocked(stageName) {
        if (['Closed Won', 'Closed Lost'].includes(wfCurrentDealStage)) {
            if (['Closed Won', 'Closed Lost'].includes(stageName)) {
                return stageName !== wfCurrentDealStage;
            }
            return false;
        }
        const currentSeqIdx = getSequentialIndex(wfCurrentDealStage);
        const targetSeqIdx = getSequentialIndex(stageName);
        return targetSeqIdx > currentSeqIdx;
    }

    // Handle clicking on pipeline progress indicator steps
    function handlePipelineStepClick(stageName, index) {
        // If locked: Do NOT skip, show validation toast message
        if (isStageLocked(stageName)) {
            showWorkflowToast(`Please complete the current stage first.`, 'warning');
            return;
        }

        // Map stage to corresponding deal nav tab
        const tabMap = {
            'Inquiry': 'inquiry',
            'Qualification': 'inquiry',
            'Consultation': 'consultation',
            'Proposal': 'proposal',
            'Negotiation': 'services-pricing',
            'Payment': 'finance',
            'Activation': 'start',
            'Closed Won': 'history',
            'Closed Lost': 'history'
        };

        const targetTab = tabMap[stageName] || 'overview';
        switchDealNavTab(targetTab);
    }

    // Dynamic Continue Stage Quick Action
    function handleContinueStage(stageName) {
        const stage = stageName || wfCurrentDealStage || (window.DEAL_BOOTSTRAP?.currentStage ?? "Inquiry");
        if (['Closed Won', 'Closed Lost'].includes(stage)) {
            switchDealNavTab('history');
            return;
        }

        const tabMap = {
            'Inquiry': 'inquiry',
            'Qualification': 'inquiry',
            'Consultation': 'consultation',
            'Proposal': 'proposal',
            'Negotiation': 'services-pricing',
            'Payment': 'finance',
            'Activation': 'start',
            'Closed Won': 'history',
            'Closed Lost': 'history'
        };

        const targetTab = tabMap[stage] || 'overview';
        switchDealNavTab(targetTab);
    }

    function updateQuickActionsContinueBtn(newStage) {
        const btn = document.getElementById('btnContinueCurrentStage');
        if (!btn) return;

        let label = 'Continue ' + newStage;
        let isClosed = false;

        switch(newStage) {
            case 'Inquiry': label = 'Continue Inquiry'; break;
            case 'Qualification': label = 'Continue Qualification'; break;
            case 'Consultation': label = 'Continue Consultation'; break;
            case 'Proposal': label = 'Continue Proposal'; break;
            case 'Negotiation': label = 'Continue Negotiation'; break;
            case 'Payment': label = 'Continue Payment'; break;
            case 'Activation': label = 'Continue Activation'; break;
            case 'Closed Won': label = 'Deal Completed'; isClosed = true; break;
            case 'Closed Lost': label = 'Deal Closed'; isClosed = true; break;
        }

        const iconHtml = (newStage === 'Closed Won')
            ? '<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>'
            : ((newStage === 'Closed Lost')
                ? '<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>'
                : '<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"></line><polyline points="12 5 19 12 12 19"></polyline></svg>');

        btn.innerHTML = `<span class="quick-icon">${iconHtml}</span> <span>${label}</span>`;
        btn.setAttribute('onclick', `handleContinueStage('${newStage}')`);
        if (isClosed) {
            btn.style.opacity = '0.85';
        } else {
            btn.style.opacity = '1';
        }
    }

    function showWorkflowToast(message, type = 'warning') {
        if (!message) return;
        if (type === 'error') {
            alert(message);
        }
    }

    // Update the Pipeline Header visual states dynamically & live without page refresh
    function updatePipelineIndicatorUI(newStage, data) {
        updateQuickActionsContinueBtn(newStage);
        const isWonLost = ['Closed Won', 'Closed Lost'].includes(newStage);
        const newStageIndex = WF_STAGES.indexOf(newStage);

        // 1. Update Stage Timer Widget at top right
        const timerBox = document.getElementById('dealStageTimerBox');
        const timerStage = document.getElementById('dealStageTimerStage');
        const timerDisplay = document.getElementById('dealStageTimerDisplay');
        const timerDate = document.getElementById('dealStageDateDisplay');
        if (timerBox && timerStage) {
            timerStage.textContent = newStage.toUpperCase();
            timerBox.setAttribute('data-stage-name', newStage);
            timerBox.setAttribute('data-is-active', isWonLost ? 'false' : 'true');

            const startMs = (data && data.stage_start_ms) ? data.stage_start_ms : Date.now();
            timerBox.setAttribute('data-stage-start-ms', startMs);

            if (data && data.stage_started_at_formatted) {
                if (timerDate) timerDate.textContent = `Since ${data.stage_started_at_formatted}`;
            } else {
                const now = new Date();
                const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
                let h = now.getHours();
                const ampm = h >= 12 ? 'PM' : 'AM';
                h = h % 12 || 12;
                const m = String(now.getMinutes()).padStart(2, '0');
                if (timerDate) {
                    timerDate.textContent = `Since ${months[now.getMonth()]} ${now.getDate()}, ${now.getFullYear()} · ${h}:${m} ${ampm}`;
                }
            }

            if (timerDisplay) {
                timerDisplay.textContent = '00:00:00';
            }

            if (window.__initLiveStageTimer) {
                window.__initLiveStageTimer();
            }
        }

        // 2. Update Pipeline Bar Steps, Nodes, and Durations
        const stageDurations = (data && data.stage_durations) ? data.stage_durations : null;

        WF_STAGES.forEach((stg, idx) => {
            const slug = stg.toLowerCase().replace(/[^a-z0-9]+/g, '-');
            const stepEl = document.getElementById('pipe-step-' + slug);
            if (!stepEl) return;

            let isComplete = false;
            let isCurrent = false;
            let isLocked = false;

            if (isWonLost) {
                isComplete = (idx < 7) || (stg === newStage);
                isCurrent = (stg === newStage);
                isLocked = (idx >= 7 && stg !== newStage);
            } else {
                isComplete = (idx < newStageIndex);
                isCurrent = (idx === newStageIndex);
                isLocked = (idx > newStageIndex);
            }

            stepEl.className = 'dh-pipe-step ' + (isComplete ? 'is-complete' : (isCurrent ? 'is-current' : 'is-upcoming is-locked'));
            stepEl.setAttribute('data-status', isComplete ? 'completed' : (isCurrent ? 'current' : 'locked'));

            const nodeEl = stepEl.querySelector('.dh-pipe-node');
            if (nodeEl) {
                if (isComplete) {
                    nodeEl.innerHTML = '✓';
                } else if (isCurrent) {
                    nodeEl.innerHTML = '<span class="dh-pipe-current-dot"></span>';
                } else {
                    nodeEl.innerHTML = '';
                }
            }

            const durationEl = stepEl.querySelector('.dh-pipe-duration');
            if (durationEl) {
                if (stageDurations && stageDurations[stg] !== undefined) {
                    durationEl.textContent = stageDurations[stg];
                } else if (isCurrent) {
                    durationEl.textContent = '0s';
                } else if (isLocked) {
                    durationEl.textContent = '-';
                }
            }
        });

        // 3. Live refresh Right Sidebar Stage Progression & Audit Histories
        if (data && data.stage_progression && typeof updateStageProgressionSidebar === 'function') {
            updateStageProgressionSidebar(data.stage_progression);
        }
        if (data && data.counts && typeof updateHistoryMetrics === 'function') {
            updateHistoryMetrics(data.counts);
        }
        if (typeof handleApplyHistoryFilters === 'function') {
            handleApplyHistoryFilters();
        }
    }

    function updatePageMetricsAfterSave(data) {
        if (!data || !data.deal) return;
        const deal = data.deal;

        // Update Deal Stage label on Overview
        const stageDisplay = document.querySelector('#deal-information .detail-item:nth-child(11) .detail-value');
        if (stageDisplay) stageDisplay.innerText = deal.pipeline_stage;

        // Update Deal Value in header
        const valDisplay = document.querySelector('.dh-value-amount');
        if (valDisplay && deal.amount) {
            valDisplay.innerText = '₱' + parseFloat(deal.amount).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        }
    }

    

    /* DYNAMIC CONTINUE QUICK ACTION */
    function handleDynamicContinueAction(stage) {
        const currentStage = stage || (window.DEAL_BOOTSTRAP?.currentStage ?? "Inquiry");
        switch (currentStage) {
            case 'Inquiry':
                switchDealNavTab('inquiry');
                break;
            case 'Qualification':
                switchDealNavTab('services-pricing');
                break;
            case 'Consultation':
                switchDealNavTab('consultation');
                break;
            case 'Proposal':
            case 'Negotiation':
                switchDealNavTab('proposal');
                break;
            case 'Payment':
                switchDealNavTab('finance');
                break;
            case 'Activation':
                switchDealNavTab('start');
                break;
            case 'Closed Won':
            case 'Closed Lost':
                switchDealNavTab('overview');
                break;
            default:
                switchDealNavTab('inquiry');
                break;
        }
    }

    /* DONE INQUIRY QUICK ACTION */
    async function handleDoneInquiry() {
        const btn = document.getElementById('btnDoneInquiry');
        const origHtml = btn ? btn.innerHTML : 'Done Inquiry';
        if (btn) {
            btn.disabled = true;
            btn.innerText = 'Completing...';
        }

        try {
            const response = await fetch((window.DEAL_BOOTSTRAP?.routes?.stageWorkflowSave ?? ""), {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || "")
                },
                body: JSON.stringify({
                    stage: 'Inquiry',
                    action: 'complete_and_advance',
                    inquiry_source: (window.DEAL_BOOTSTRAP?.defaults?.InquirySource ?? ""),
                    inquiry_date: (window.DEAL_BOOTSTRAP?.defaults?.InquiryDate ?? ""),
                    first_name: (window.DEAL_BOOTSTRAP?.defaults?.FirstName ?? ""),
                    last_name: (window.DEAL_BOOTSTRAP?.defaults?.LastName ?? ""),
                    email: (window.DEAL_BOOTSTRAP?.defaults?.Email ?? ""),
                    mobile_number: (window.DEAL_BOOTSTRAP?.defaults?.Mobile ?? ""),
                    deal_title: (window.DEAL_BOOTSTRAP?.defaults?.DealTitle ?? ""),
                    inquiry_details: (window.DEAL_BOOTSTRAP?.defaults?.InquiryDetails ?? "")
                })
            });

            const data = await response.json();

            if (!response.ok || !data.success) {
                const msg = (data && data.message) ? data.message : 'Unable to complete Inquiry stage.';
                showWorkflowToast(msg, 'error');
                if (btn) {
                    btn.disabled = false;
                    btn.innerHTML = origHtml;
                }
                return;
            }

            wfCurrentDealStage = data.next_stage || data.current_stage || 'Qualification';
            updatePipelineIndicatorUI(wfCurrentDealStage, data);
            updatePageMetricsAfterSave(data);

            if (btn) {
                btn.disabled = false;
                btn.innerHTML = '<span class="quick-icon" style="color: #10b981; font-weight: 800;">✓</span> Inquiry Done';
                btn.style.opacity = '0.7';
            }

            showWorkflowToast('Inquiry stage completed! Moved to Qualification.', 'success');

            if (typeof loadHistories === 'function') {
                loadHistories(1, true);
            }
        } catch (e) {
            console.error(e);
            showWorkflowToast('Failed to complete Inquiry stage. Please try again.', 'error');
            if (btn) {
                btn.disabled = false;
                btn.innerHTML = origHtml;
            }
        }
    }

    /* CLIENT IS QUALIFIED QUICK ACTION */
    async function handleClientQualified() {
        const buttons = [
            document.getElementById('btnClientQualifiedPricing'),
            document.getElementById('btnClientQualifiedOverview'),
            document.getElementById('btnClientQualifiedConsultation')
        ].filter(Boolean);

        buttons.forEach(btn => {
            btn.disabled = true;
            btn.innerText = 'Qualifying...';
        });

        try {
            // If the deal is currently at Inquiry, complete Inquiry first
            if (wfCurrentDealStage === 'Inquiry') {
                const inqRes = await fetch((window.DEAL_BOOTSTRAP?.routes?.stageWorkflowSave ?? ""), {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || "")
                    },
                    body: JSON.stringify({
                        stage: 'Inquiry',
                        action: 'complete_and_advance',
                        inquiry_source: (window.DEAL_BOOTSTRAP?.defaults?.InquirySource ?? ""),
                        inquiry_date: (window.DEAL_BOOTSTRAP?.defaults?.InquiryDate ?? ""),
                        first_name: (window.DEAL_BOOTSTRAP?.defaults?.FirstName ?? ""),
                        last_name: (window.DEAL_BOOTSTRAP?.defaults?.LastName ?? ""),
                        email: (window.DEAL_BOOTSTRAP?.defaults?.Email ?? ""),
                        mobile_number: (window.DEAL_BOOTSTRAP?.defaults?.Mobile ?? ""),
                        deal_title: (window.DEAL_BOOTSTRAP?.defaults?.DealTitle ?? ""),
                        inquiry_details: (window.DEAL_BOOTSTRAP?.defaults?.InquiryDetails ?? "")
                    })
                });
                const inqData = await inqRes.json();
                if (inqData && inqData.success) {
                    wfCurrentDealStage = inqData.next_stage || 'Qualification';
                }
            }

            // Complete Qualification stage and advance to Consultation
            const response = await fetch((window.DEAL_BOOTSTRAP?.routes?.stageWorkflowSave ?? ""), {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || "")
                },
                body: JSON.stringify({
                    stage: 'Qualification',
                    action: 'complete_and_advance',
                    qualification_result: (window.DEAL_BOOTSTRAP?.defaults?.QualResult ?? ""),
                    client_need: (window.DEAL_BOOTSTRAP?.defaults?.ClientNeed ?? ""),
                    amount: (window.DEAL_BOOTSTRAP?.defaults?.Amount ?? ""),
                    decision_maker: (window.DEAL_BOOTSTRAP?.defaults?.DecisionMaker ?? ""),
                    expected_close: (window.DEAL_BOOTSTRAP?.defaults?.ExpectedClose ?? ""),
                    qualification_notes: (window.DEAL_BOOTSTRAP?.defaults?.QualNotes ?? "")
                })
            });

            const data = await response.json();

            if (!response.ok || !data.success) {
                const msg = (data && data.message) ? data.message : 'Unable to complete Qualification stage.';
                showWorkflowToast(msg, 'error');
                buttons.forEach(btn => {
                    btn.disabled = false;
                    btn.innerHTML = '<span class="quick-icon" style="color: #0284c7; font-weight: 800;">✓</span> Client is Qualified';
                });
                return;
            }

            wfCurrentDealStage = data.next_stage || data.current_stage || 'Consultation';
            updatePipelineIndicatorUI(wfCurrentDealStage, data);
            updatePageMetricsAfterSave(data);

            buttons.forEach(btn => {
                btn.disabled = false;
                btn.innerHTML = '<span class="quick-icon" style="color: #10b981; font-weight: 800;">✓</span> Client Qualified';
                btn.style.opacity = '0.75';
            });

            const inqBtn = document.getElementById('btnDoneInquiry');
            if (inqBtn) {
                inqBtn.innerHTML = '<span class="quick-icon" style="color: #2563eb; font-weight: 800;">✓</span> Inquiry Done';
                inqBtn.style.opacity = '0.7';
            }

            showWorkflowToast('Client is Qualified! Qualification stage checked (✓) and advanced to Consultation.', 'success');

            if (typeof loadHistories === 'function') {
                loadHistories(1, true);
            }
        } catch (e) {
            console.error(e);
            showWorkflowToast('Failed to qualify client. Please try again.', 'error');
            buttons.forEach(btn => {
                btn.disabled = false;
                btn.innerHTML = '<span class="quick-icon" style="color: #0284c7; font-weight: 800;">✓</span> Client is Qualified';
            });
        }
    }

    /* CREATE PROPOSAL FROM SERVICES & PRICING QUICK ACTION */
    async function handleCreateProposalFromPricing() {
        const btn = document.getElementById('btnCreateProposalServicesPricing');
        const origHtml = btn ? btn.innerHTML : 'Create Proposal';
        if (btn) {
            btn.disabled = true;
            btn.innerText = 'Creating Proposal...';
        }

        try {
            // Step 1: If current stage is Inquiry, advance to Qualification first
            if (wfCurrentDealStage === 'Inquiry') {
                const inqRes = await fetch((window.DEAL_BOOTSTRAP?.routes?.stageWorkflowSave ?? ""), {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || "")
                    },
                    body: JSON.stringify({
                        stage: 'Inquiry',
                        action: 'complete_and_advance',
                        inquiry_source: (window.DEAL_BOOTSTRAP?.defaults?.InquirySource ?? ""),
                        inquiry_date: (window.DEAL_BOOTSTRAP?.defaults?.InquiryDate ?? ""),
                        first_name: (window.DEAL_BOOTSTRAP?.defaults?.FirstName ?? ""),
                        last_name: (window.DEAL_BOOTSTRAP?.defaults?.LastName ?? ""),
                        email: (window.DEAL_BOOTSTRAP?.defaults?.Email ?? ""),
                        mobile_number: (window.DEAL_BOOTSTRAP?.defaults?.Mobile ?? ""),
                        deal_title: (window.DEAL_BOOTSTRAP?.defaults?.DealTitle ?? ""),
                        inquiry_details: (window.DEAL_BOOTSTRAP?.defaults?.InquiryDetails ?? "")
                    })
                });
                const inqData = await inqRes.json();
                if (inqData && inqData.success) {
                    wfCurrentDealStage = inqData.next_stage || 'Qualification';
                }
            }

            // Step 2: If current stage is Qualification, advance to Consultation
            if (wfCurrentDealStage === 'Qualification') {
                const qualRes = await fetch((window.DEAL_BOOTSTRAP?.routes?.stageWorkflowSave ?? ""), {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || "")
                    },
                    body: JSON.stringify({
                        stage: 'Qualification',
                        action: 'complete_and_advance',
                        qualification_result: (window.DEAL_BOOTSTRAP?.defaults?.QualResult ?? ""),
                        client_need: (window.DEAL_BOOTSTRAP?.defaults?.ClientNeed ?? ""),
                        amount: (window.DEAL_BOOTSTRAP?.defaults?.Amount ?? ""),
                        decision_maker: (window.DEAL_BOOTSTRAP?.defaults?.DecisionMaker ?? ""),
                        expected_close: (window.DEAL_BOOTSTRAP?.defaults?.ExpectedClose ?? ""),
                        qualification_notes: (window.DEAL_BOOTSTRAP?.defaults?.QualNotes ?? "")
                    })
                });
                const qualData = await qualRes.json();
                if (qualData && qualData.success) {
                    wfCurrentDealStage = qualData.next_stage || 'Consultation';
                }
            }

            // Step 3: Complete Consultation stage and advance to Proposal
            const response = await fetch((window.DEAL_BOOTSTRAP?.routes?.stageWorkflowSave ?? ""), {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || "")
                },
                body: JSON.stringify({
                    stage: 'Consultation',
                    action: 'complete_and_advance',
                    consultation_date: (window.DEAL_BOOTSTRAP?.defaults?.ConsultDate ?? ""),
                    consultation_type: (window.DEAL_BOOTSTRAP?.defaults?.ConsultType ?? ""),
                    requirements_confirmed: (window.DEAL_BOOTSTRAP?.defaults?.ReqConfirmed ?? ""),
                    scope_of_work: (window.DEAL_BOOTSTRAP?.defaults?.ScopeOfWork ?? ""),
                    consultant_notes: (window.DEAL_BOOTSTRAP?.defaults?.ConsultNotes ?? "")
                })
            });

            const data = await response.json();

            if (!response.ok || !data.success) {
                const msg = (data && data.message) ? data.message : 'Unable to complete Consultation stage.';
                showWorkflowToast(msg, 'error');
                if (btn) {
                    btn.disabled = false;
                    btn.innerHTML = origHtml;
                }
                return;
            }

            wfCurrentDealStage = data.next_stage || data.current_stage || 'Proposal';
            updatePipelineIndicatorUI(wfCurrentDealStage, data);
            updatePageMetricsAfterSave(data);

            if (btn) {
                btn.disabled = false;
                btn.innerHTML = '<span class="quick-icon" style="color: #10b981; font-weight: 800;">✓</span> Proposal Created';
                btn.style.opacity = '0.8';
            }

            showWorkflowToast('Consultation stage completed (✓)! Moved to Proposal stage.', 'success');

            if (typeof loadHistories === 'function') {
                loadHistories(1, true);
            }

            // Open proposal workspace / tab
            if (typeof openProposalWorkspace === 'function') {
                openProposalWorkspace();
            } else {
                switchDealNavTab('proposal');
            }
        } catch (e) {
            console.error(e);
            showWorkflowToast('Failed to create proposal. Please try again.', 'error');
            if (btn) {
                btn.disabled = false;
                btn.innerHTML = origHtml;
            }
        }
    }

    /* PROPOSAL APPROVED QUICK ACTION -> ADVANCE STAGE TO NEGOTIATION */
    async function handleProposalApproved() {
        const btn = document.getElementById('btnProposalApproved');
        const origHtml = btn ? btn.innerHTML : 'Proposal Approved';
        if (btn) {
            btn.disabled = true;
            btn.innerText = 'Approving Proposal...';
        }

        try {
            // Step 1: Advance Inquiry if needed
            if (wfCurrentDealStage === 'Inquiry') {
                const inqRes = await fetch((window.DEAL_BOOTSTRAP?.routes?.stageWorkflowSave ?? ""), {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || "")
                    },
                    body: JSON.stringify({
                        stage: 'Inquiry',
                        action: 'complete_and_advance',
                        inquiry_source: (window.DEAL_BOOTSTRAP?.defaults?.InquirySource ?? ""),
                        inquiry_date: (window.DEAL_BOOTSTRAP?.defaults?.InquiryDate ?? ""),
                        first_name: (window.DEAL_BOOTSTRAP?.defaults?.FirstName ?? ""),
                        last_name: (window.DEAL_BOOTSTRAP?.defaults?.LastName ?? ""),
                        email: (window.DEAL_BOOTSTRAP?.defaults?.Email ?? ""),
                        mobile_number: (window.DEAL_BOOTSTRAP?.defaults?.Mobile ?? ""),
                        deal_title: (window.DEAL_BOOTSTRAP?.defaults?.DealTitle ?? ""),
                        inquiry_details: (window.DEAL_BOOTSTRAP?.defaults?.InquiryDetails ?? "")
                    })
                });
                const inqData = await inqRes.json();
                if (inqData && inqData.success) {
                    wfCurrentDealStage = inqData.next_stage || 'Qualification';
                }
            }

            // Step 2: Advance Qualification if needed
            if (wfCurrentDealStage === 'Qualification') {
                const qualRes = await fetch((window.DEAL_BOOTSTRAP?.routes?.stageWorkflowSave ?? ""), {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || "")
                    },
                    body: JSON.stringify({
                        stage: 'Qualification',
                        action: 'complete_and_advance',
                        qualification_result: (window.DEAL_BOOTSTRAP?.defaults?.QualResult ?? ""),
                        client_need: (window.DEAL_BOOTSTRAP?.defaults?.ClientNeed ?? ""),
                        amount: (window.DEAL_BOOTSTRAP?.defaults?.Amount ?? ""),
                        decision_maker: (window.DEAL_BOOTSTRAP?.defaults?.DecisionMaker ?? ""),
                        expected_close: (window.DEAL_BOOTSTRAP?.defaults?.ExpectedClose ?? ""),
                        qualification_notes: (window.DEAL_BOOTSTRAP?.defaults?.QualNotes ?? "")
                    })
                });
                const qualData = await qualRes.json();
                if (qualData && qualData.success) {
                    wfCurrentDealStage = qualData.next_stage || 'Consultation';
                }
            }

            // Step 3: Advance Consultation if needed
            if (wfCurrentDealStage === 'Consultation') {
                const consultRes = await fetch((window.DEAL_BOOTSTRAP?.routes?.stageWorkflowSave ?? ""), {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || "")
                    },
                    body: JSON.stringify({
                        stage: 'Consultation',
                        action: 'complete_and_advance',
                        consultation_date: (window.DEAL_BOOTSTRAP?.defaults?.ConsultDate ?? ""),
                        consultation_type: (window.DEAL_BOOTSTRAP?.defaults?.ConsultType ?? ""),
                        requirements_confirmed: (window.DEAL_BOOTSTRAP?.defaults?.ReqConfirmed ?? ""),
                        scope_of_work: (window.DEAL_BOOTSTRAP?.defaults?.ScopeOfWork ?? ""),
                        consultant_notes: (window.DEAL_BOOTSTRAP?.defaults?.ConsultNotes ?? "")
                    })
                });
                const consultData = await consultRes.json();
                if (consultData && consultData.success) {
                    wfCurrentDealStage = consultData.next_stage || 'Proposal';
                }
            }

            // Step 4: Complete Proposal stage and advance to Negotiation
            const response = await fetch((window.DEAL_BOOTSTRAP?.routes?.stageWorkflowSave ?? ""), {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || "")
                },
                body: JSON.stringify({
                    stage: 'Proposal',
                    action: 'complete_and_advance',
                    proposal_number: (window.DEAL_BOOTSTRAP?.defaults?.PropNum ?? ""),
                    proposal_date: (window.DEAL_BOOTSTRAP?.defaults?.PropDate ?? ""),
                    proposal_value: (window.DEAL_BOOTSTRAP?.defaults?.PropValue ?? ""),
                    proposal_valid_until: (window.DEAL_BOOTSTRAP?.defaults?.PropValidUntil ?? ""),
                    proposal_status: 'Approved',
                    proposal_notes: (window.DEAL_BOOTSTRAP?.defaults?.PropNotes ?? "")
                })
            });

            const data = await response.json();

            if (!response.ok || !data.success) {
                const msg = (data && data.message) ? data.message : 'Unable to approve Proposal.';
                showWorkflowToast(msg, 'error');
                if (btn) {
                    btn.disabled = false;
                    btn.innerHTML = origHtml;
                }
                return;
            }

            wfCurrentDealStage = data.next_stage || data.current_stage || 'Negotiation';
            updatePipelineIndicatorUI(wfCurrentDealStage, data);
            updatePageMetricsAfterSave(data);

            if (btn) {
                btn.disabled = false;
                btn.innerHTML = '<span class="quick-icon" style="color: #10b981; font-weight: 800;">✓</span> Proposal Approved';
                btn.style.opacity = '0.85';
            }

            showWorkflowToast('Proposal Approved (✓)! Moved to Negotiation stage.', 'success');

            if (typeof loadHistories === 'function') {
                loadHistories(1, true);
            }
        } catch (e) {
            console.error(e);
            showWorkflowToast('Failed to approve proposal. Please try again.', 'error');
            if (btn) {
                btn.disabled = false;
                btn.innerHTML = origHtml;
            }
        }
    }

    /* DONE NEGOTIATION QUICK ACTION -> ADVANCE STAGE TO PAYMENT */
    async function handleDoneNegotiation() {
        const btn = document.getElementById('btnDoneNegotiation');
        const origHtml = btn ? btn.innerHTML : 'Done Negotiation';
        if (btn) {
            btn.disabled = true;
            btn.innerText = 'Completing Negotiation...';
        }

        try {
            // Step 1: Advance earlier stages if needed
            if (wfCurrentDealStage === 'Inquiry') {
                const inqRes = await fetch((window.DEAL_BOOTSTRAP?.routes?.stageWorkflowSave ?? ""), {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || "") },
                    body: JSON.stringify({
                        stage: 'Inquiry', action: 'complete_and_advance',
                        inquiry_source: (window.DEAL_BOOTSTRAP?.defaults?.InquirySource ?? ""), inquiry_date: (window.DEAL_BOOTSTRAP?.defaults?.InquiryDate ?? ""),
                        first_name: (window.DEAL_BOOTSTRAP?.defaults?.FirstName ?? ""), last_name: (window.DEAL_BOOTSTRAP?.defaults?.LastName ?? ""),
                        email: (window.DEAL_BOOTSTRAP?.defaults?.Email ?? ""), mobile_number: (window.DEAL_BOOTSTRAP?.defaults?.Mobile ?? ""),
                        deal_title: (window.DEAL_BOOTSTRAP?.defaults?.DealTitle ?? ""), inquiry_details: (window.DEAL_BOOTSTRAP?.defaults?.InquiryDetails ?? "")
                    })
                });
                const inqData = await inqRes.json();
                if (inqData && inqData.success) wfCurrentDealStage = inqData.next_stage || 'Qualification';
            }

            if (wfCurrentDealStage === 'Qualification') {
                const qualRes = await fetch((window.DEAL_BOOTSTRAP?.routes?.stageWorkflowSave ?? ""), {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || "") },
                    body: JSON.stringify({
                        stage: 'Qualification', action: 'complete_and_advance',
                        qualification_result: (window.DEAL_BOOTSTRAP?.defaults?.QualResult ?? ""), client_need: (window.DEAL_BOOTSTRAP?.defaults?.ClientNeed ?? ""),
                        amount: (window.DEAL_BOOTSTRAP?.defaults?.Amount ?? ""), decision_maker: (window.DEAL_BOOTSTRAP?.defaults?.DecisionMaker ?? ""),
                        expected_close: (window.DEAL_BOOTSTRAP?.defaults?.ExpectedClose ?? ""), qualification_notes: (window.DEAL_BOOTSTRAP?.defaults?.QualNotes ?? "")
                    })
                });
                const qualData = await qualRes.json();
                if (qualData && qualData.success) wfCurrentDealStage = qualData.next_stage || 'Consultation';
            }

            if (wfCurrentDealStage === 'Consultation') {
                const consultRes = await fetch((window.DEAL_BOOTSTRAP?.routes?.stageWorkflowSave ?? ""), {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || "") },
                    body: JSON.stringify({
                        stage: 'Consultation', action: 'complete_and_advance',
                        consultation_date: (window.DEAL_BOOTSTRAP?.defaults?.ConsultDate ?? ""), consultation_type: (window.DEAL_BOOTSTRAP?.defaults?.ConsultType ?? ""),
                        requirements_confirmed: (window.DEAL_BOOTSTRAP?.defaults?.ReqConfirmed ?? ""), scope_of_work: (window.DEAL_BOOTSTRAP?.defaults?.ScopeOfWork ?? ""),
                        consultant_notes: (window.DEAL_BOOTSTRAP?.defaults?.ConsultNotes ?? "")
                    })
                });
                const consultData = await consultRes.json();
                if (consultData && consultData.success) wfCurrentDealStage = consultData.next_stage || 'Proposal';
            }

            if (wfCurrentDealStage === 'Proposal') {
                const propRes = await fetch((window.DEAL_BOOTSTRAP?.routes?.stageWorkflowSave ?? ""), {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || "") },
                    body: JSON.stringify({
                        stage: 'Proposal', action: 'complete_and_advance',
                        proposal_number: (window.DEAL_BOOTSTRAP?.defaults?.PropNum ?? ""), proposal_date: (window.DEAL_BOOTSTRAP?.defaults?.PropDate ?? ""),
                        proposal_value: (window.DEAL_BOOTSTRAP?.defaults?.PropValue ?? ""), proposal_valid_until: (window.DEAL_BOOTSTRAP?.defaults?.PropValidUntil ?? ""),
                        proposal_status: 'Approved', proposal_notes: (window.DEAL_BOOTSTRAP?.defaults?.PropNotes ?? "")
                    })
                });
                const propData = await propRes.json();
                if (propData && propData.success) wfCurrentDealStage = propData.next_stage || 'Negotiation';
            }

            // Step 2: Complete Negotiation stage and advance to Payment
            const response = await fetch((window.DEAL_BOOTSTRAP?.routes?.stageWorkflowSave ?? ""), {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || "") },
                body: JSON.stringify({
                    stage: 'Negotiation',
                    action: 'complete_and_advance',
                    negotiation_status: (window.DEAL_BOOTSTRAP?.defaults?.NegoStatus ?? ""),
                    final_deal_value: (window.DEAL_BOOTSTRAP?.defaults?.FinalDealVal ?? ""),
                    payment_terms: (window.DEAL_BOOTSTRAP?.defaults?.PayTerms ?? ""),
                    pricing_model: (window.DEAL_BOOTSTRAP?.defaults?.PricingModel ?? ""),
                    negotiation_notes: (window.DEAL_BOOTSTRAP?.defaults?.NegoNotes ?? "")
                })
            });

            const data = await response.json();
            if (!response.ok || !data.success) {
                const msg = (data && data.message) ? data.message : 'Unable to complete Negotiation stage.';
                showWorkflowToast(msg, 'error');
                if (btn) { btn.disabled = false; btn.innerHTML = origHtml; }
                return;
            }

            wfCurrentDealStage = data.next_stage || data.current_stage || 'Payment';
            updatePipelineIndicatorUI(wfCurrentDealStage, data);
            updatePageMetricsAfterSave(data);

            if (btn) {
                btn.disabled = false;
                btn.innerHTML = '<span class="quick-icon" style="color: #10b981; font-weight: 800;">✓</span> Negotiation Done';
                btn.style.opacity = '0.85';
            }

            showWorkflowToast('Negotiation completed (✓)! Pipeline advanced to Payment.', 'success');

            if (typeof loadHistories === 'function') {
                loadHistories(1, true);
            }
        } catch (e) {
            console.error(e);
            showWorkflowToast('Failed to complete negotiation. Please try again.', 'error');
            if (btn) { btn.disabled = false; btn.innerHTML = origHtml; }
        }
    }

    /* RECEIVED PAYMENT QUICK ACTION -> ADVANCE STAGE TO ACTIVATION & REVEAL READY TO START */
    async function handleReceivedPayment() {
        const btn = document.getElementById('btnReceivedPayment');
        const origHtml = btn ? btn.innerHTML : 'Received Payment';
        if (btn) {
            btn.disabled = true;
            btn.innerText = 'Recording Payment...';
        }

        try {
            // Step 1: Ensure negotiation is completed first
            if (wfCurrentDealStage !== 'Payment' && wfCurrentDealStage !== 'Activation' && wfCurrentDealStage !== 'Closed Won') {
                await handleDoneNegotiation();
            }

            // Step 2: Complete Payment stage and advance to Activation
            const response = await fetch((window.DEAL_BOOTSTRAP?.routes?.stageWorkflowSave ?? ""), {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || "") },
                body: JSON.stringify({
                    stage: 'Payment',
                    action: 'complete_and_advance',
                    payment_method: (window.DEAL_BOOTSTRAP?.defaults?.PayMethod ?? ""),
                    payment_amount: (window.DEAL_BOOTSTRAP?.defaults?.PayAmount ?? ""),
                    payment_date: (window.DEAL_BOOTSTRAP?.defaults?.PayDate ?? ""),
                    payment_status: 'Completed',
                    payment_reference: (window.DEAL_BOOTSTRAP?.defaults?.PayRef ?? "")
                })
            });

            const data = await response.json();
            if (!response.ok || !data.success) {
                const msg = (data && data.message) ? data.message : 'Unable to complete Payment stage.';
                showWorkflowToast(msg, 'error');
                if (btn) { btn.disabled = false; btn.innerHTML = origHtml; }
                return;
            }

            wfCurrentDealStage = data.next_stage || data.current_stage || 'Activation';
            updatePipelineIndicatorUI(wfCurrentDealStage, data);
            updatePageMetricsAfterSave(data);

            if (btn) {
                btn.disabled = false;
                btn.innerHTML = '<span class="quick-icon" style="color: #10b981; font-weight: 800;">✓</span> Payment Received';
                btn.style.opacity = '0.85';
            }

            // Reveal Ready to START button
            const startBtn = document.getElementById('btnReadyToStart');
            if (startBtn) {
                startBtn.style.display = 'inline-flex';
            }

            showWorkflowToast('Payment received (✓)! Pipeline advanced to Activation. Ready to START is now available.', 'success');

            if (typeof loadHistories === 'function') {
                loadHistories(1, true);
            }
        } catch (e) {
            console.error(e);
            showWorkflowToast('Failed to record received payment. Please try again.', 'error');
            if (btn) { btn.disabled = false; btn.innerHTML = origHtml; }
        }
    }

    /* READY TO START QUICK ACTION -> ADVANCE STAGE TO ACTIVATION */
    async function handleReadyToStart() {
        const btn = document.getElementById('btnReadyToStart');
        const origHtml = btn ? btn.innerHTML : 'Ready to START';
        if (btn) {
            btn.disabled = true;
            btn.innerText = 'Activating START...';
        }

        try {
            const response = await fetch((window.DEAL_BOOTSTRAP?.routes?.stageUpdate ?? ""), {
                method: 'PATCH',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || "")
                },
                body: JSON.stringify({
                    pipeline_stage: 'Activation',
                    notes: 'Deal moved to Activation via Finance Quick Action (Ready to START).'
                })
            });

            const data = await response.json();

            if (!response.ok || !data.success) {
                const msg = (data && data.message) ? data.message : 'Unable to move deal to Activation stage.';
                showWorkflowToast(msg, 'error');
                if (btn) {
                    btn.disabled = false;
                    btn.innerHTML = origHtml;
                }
                return;
            }

            wfCurrentDealStage = 'Activation';
            updatePipelineIndicatorUI('Activation', data);
            if (data.data) {
                updatePageMetricsAfterSave({ deal: { pipeline_stage: 'Activation', amount: data.data.deal_value || data.data.amount } });
            }

            if (btn) {
                btn.disabled = false;
                btn.innerHTML = '<span class="quick-icon" style="color: #10b981; font-weight: 800;">✓</span> Ready to START';
                btn.style.opacity = '0.85';
            }

            showWorkflowToast('Deal moved to Activation stage (✓)! Ready to START.', 'success');

            if (typeof loadHistories === 'function') {
                loadHistories(1, true);
            }

            // Switch to START tab
            switchDealNavTab('start');
        } catch (e) {
            console.error(e);
            showWorkflowToast('Failed to activate deal. Please try again.', 'error');
            if (btn) {
                btn.disabled = false;
                btn.innerHTML = origHtml;
            }
        }
    }

    function toggleStageOptions(button) {
        const options = button.nextElementSibling;
        const expanded = options.classList.toggle('is-open');

        button.setAttribute('aria-expanded', expanded ? 'true' : 'false');
    }

    window.switchDealTab = window.switchDealNavTab = function(tabName, button) {
        if (!tabName) tabName = 'overview';
        tabName = tabName.toLowerCase().trim();

        if (!button) {
            button = document.querySelector(`.deal-nav-item[data-tab="${tabName}"]`);
        }

        document.querySelectorAll('.deal-nav-item').forEach(item => {
            item.classList.remove('active');
        });
        if (button) {
            button.classList.add('active');
            try {
                button.scrollIntoView({ behavior: 'smooth', inline: 'nearest', block: 'nearest' });
            } catch(e) {}
        }

        // Hide all tab panels
        document.querySelectorAll('.deal-tab-panel').forEach(panel => {
            panel.classList.remove('active');
            panel.style.display = 'none';
        });

        // Show matching tab panel
        let targetPanel = document.getElementById('tab-panel-' + tabName);
        if (!targetPanel && (tabName === 'engagment' || tabName === 'engagement')) {
            targetPanel = document.getElementById('tab-panel-engagment') || document.getElementById('tab-panel-engagement');
        }
        if (targetPanel) {
            targetPanel.classList.add('active');
            targetPanel.style.display = 'block';
        } else {
            const overviewPanel = document.getElementById('tab-panel-overview');
            if (overviewPanel) {
                overviewPanel.classList.add('active');
                overviewPanel.style.display = 'block';
            }
        }

        if (tabName === 'services-pricing' && typeof renderDealLineItems === 'function') {
            renderDealLineItems();
        }

        if (tabName === 'proposal') {
            if (typeof renderInternalVersionPills === 'function') renderInternalVersionPills();
            if (typeof updateAuditTraceabilityUI === 'function') updateAuditTraceabilityUI();
        }

        if (tabName === 'finance' && typeof initFinanceTab === 'function') {
            initFinanceTab();
        }

        if (tabName === 'start' && typeof initStartTab === 'function') {
            initStartTab();
        }

        if (tabName === 'files' && typeof initFilesTab === 'function') {
            initFilesTab();
        }

        if (tabName === 'history' && typeof initHistoryTab === 'function') {
            initHistoryTab();
        }

        const detailMain = document.querySelector('.detail-main');
        if (detailMain) {
            detailMain.scrollTop = 0;
        }

        // Switch Quick Actions in Sidebar
        document.querySelectorAll('.sidebar-qa-panel').forEach(panel => {
            panel.style.display = 'none';
        });
        let specificQa = document.getElementById('sidebar-qa-' + tabName);
        if (!specificQa && (tabName === 'engagment' || tabName === 'engagement')) {
            specificQa = document.getElementById('sidebar-qa-engagment') || document.getElementById('sidebar-qa-engagement');
        }
        if (specificQa) {
            specificQa.style.display = 'block';
        } else {
            const defaultQa = document.getElementById('sidebar-qa-overview');
            if (defaultQa) {
                defaultQa.style.display = 'block';
            }
        }

        // Keep URL steady without duplicate history entries
        try {
            const url = new URL(window.location.href);
            if (tabName === 'overview') {
                url.hash = '';
                history.replaceState({ tab: 'overview' }, '', url.pathname + url.search);
            } else {
                url.hash = tabName;
                history.replaceState({ tab: tabName }, '', url.pathname + url.search + '#' + tabName);
            }
        } catch(e) {}
    };
    var switchDealNavTab = window.switchDealNavTab;
    var switchDealTab = window.switchDealTab;

    function closeProposalWorkspaceModal() {
        const modal = document.getElementById('proposalWorkspaceModal');
        if (modal) {
            modal.style.display = 'none';
        }
    }

    // Handle browser back / forward buttons
    window.addEventListener('popstate', function(event) {
        let tab = (window.location.hash || '').replace('#', '');
        if (event.state && event.state.tab) {
            tab = event.state.tab;
        }
        if (!tab) tab = 'overview';
        const tabBtn = document.querySelector(`.deal-nav-item[data-tab="${tab}"]`);
        switchDealNavTab(tab, tabBtn);
    });

    // Handle initial load with #tab or ?tab=
    document.addEventListener('DOMContentLoaded', function() {
        let initialTab = (window.location.hash || '').replace('#', '');
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.get('tab')) {
            initialTab = urlParams.get('tab');
        }
        if (initialTab) {
            const tabBtn = document.querySelector(`.deal-nav-item[data-tab="${initialTab}"]`);
            if (tabBtn) {
                switchDealNavTab(initialTab, tabBtn);
            }
        }
        if (typeof initStartTab === 'function') {
            initStartTab();
        }
    });

    function updateDealTabArrows() {
        const scrollContainer = document.getElementById('dealNavBarScroll');
        const leftArrow = document.getElementById('dealTabArrowLeft');
        const rightArrow = document.getElementById('dealTabArrowRight');

        if (!scrollContainer || !leftArrow || !rightArrow) return;

        const scrollWidth = scrollContainer.scrollWidth;
        const clientWidth = scrollContainer.clientWidth;
        const scrollLeft = scrollContainer.scrollLeft;
        const maxScroll = scrollWidth - clientWidth;

        const hasOverflow = scrollWidth > clientWidth + 2;

        if (!hasOverflow) {
            leftArrow.style.display = 'none';
            rightArrow.style.display = 'none';
            return;
        }

        // Left arrow
        if (scrollLeft > 4) {
            leftArrow.style.display = 'inline-flex';
        } else {
            leftArrow.style.display = 'none';
        }

        // Right arrow
        if (scrollLeft < maxScroll - 4) {
            rightArrow.style.display = 'inline-flex';
        } else {
            rightArrow.style.display = 'none';
        }
    }

    function scrollDealTabs(direction) {
        const scrollContainer = document.getElementById('dealNavBarScroll');
        if (!scrollContainer) return;

        const scrollAmount = 220;
        if (direction === 'left') {
            scrollContainer.scrollBy({ left: -scrollAmount, behavior: 'smooth' });
        } else if (direction === 'right') {
            scrollContainer.scrollBy({ left: scrollAmount, behavior: 'smooth' });
        }
    }

    /* =========================================================
       INQUIRY RECORDS STATE & OPERATIONS
       ========================================================= */
    const currentUser = (window.DEAL_BOOTSTRAP?.ownerName ?? "Authorized Approver");

    

    let inquiryRecords = (window.DEAL_BOOTSTRAP?.dealInquiries ?? []);

    let currentSelectedInquiryId = null;

    function escapeHtml(text) {
        if (!text) return '';
        const map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return String(text).replace(/[&<>"']/g, m => map[m]);
    }

    function formatBudget(val) {
        if (!val) return '';
        const cleaned = String(val).replace(/[^0-9.]/g, '');
        const num = parseFloat(cleaned);
        if (isNaN(num)) return val;
        return '₱' + num.toLocaleString('en-US', { minimumFractionDigits: 0, maximumFractionDigits: 2 });
    }

    function formatDate(dateStr) {
        if (!dateStr) return '';
        try {
            const parts = String(dateStr).split('-');
            if (parts.length === 3) {
                const d = new Date(parts[0], parts[1] - 1, parts[2]);
                if (!isNaN(d.getTime())) {
                    return d.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
                }
            }
            return dateStr;
        } catch (e) {
            return dateStr;
        }
    }

    function formatToday() {
        return new Date().toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
    }

    function getTypeTagClass(type) {
        switch (type) {
            case 'Product': return 'tag-product';
            case 'Service': return 'tag-service';
            default: return 'tag-service';
        }
    }

    function renderInquiryCards() {
        const container = document.getElementById('inquiryCardsList');
        const emptyState = document.getElementById('inquiryEmptyState');
        const searchVal = (document.getElementById('inquirySearchInput')?.value || '').trim().toLowerCase();
        const typeFilter = document.getElementById('inquiryTypeFilter')?.value || 'ALL';

        if (!container || !emptyState) return;

        const filtered = inquiryRecords.filter(item => {
            const matchesSearch = !searchVal || 
                item.subject.toLowerCase().includes(searchVal) || 
                item.clientInquiry.toLowerCase().includes(searchVal);
            
            const matchesType = (typeFilter === 'ALL') || (item.type === typeFilter);

            return matchesSearch && matchesType;
        });

        if (filtered.length === 0) {
            container.innerHTML = '';
            emptyState.style.display = 'flex';
            return;
        }

        emptyState.style.display = 'none';

        container.innerHTML = filtered.map(item => `
            <div class="inquiry-record-card" data-id="${item.id}">
                <div class="inquiry-card-header-row">
                    <div class="inquiry-card-title-wrap">
                        <h3 class="inquiry-card-title">${escapeHtml(item.subject)}</h3>
                        <span class="inquiry-type-tag ${getTypeTagClass(item.type)}">${escapeHtml(item.type)}</span>
                    </div>
                </div>

                <div class="inquiry-card-subline">
                    ${escapeHtml(item.type)} • ${escapeHtml(item.createdAt)}
                </div>

                <div class="inquiry-card-content">${escapeHtml(item.clientInquiry)}</div>

                ${(item.budget || item.targetDate) ? `
                    <div class="inquiry-card-meta-row">
                        ${item.budget ? `<span class="inquiry-meta-item"><strong>Budget:</strong> ${escapeHtml(formatBudget(item.budget))}</span>` : ''}
                        ${item.targetDate ? `<span class="inquiry-meta-item"><strong>Target Date:</strong> ${escapeHtml(formatDate(item.targetDate))}</span>` : ''}
                    </div>
                ` : ''}

                <div class="inquiry-card-footer-row">
                    <div class="inquiry-card-author-text">
                        Added by ${escapeHtml(item.createdBy)}
                    </div>
                    <div class="inquiry-card-btn-group">
                        <button type="button" class="btn-inquiry-view-action" onclick="openViewInquiryModal(${item.id})">View</button>
                        <button type="button" class="btn-inquiry-more-action" title="More actions" onclick="toggleInquiryMoreMenu(event, ${item.id})">⋮</button>
                    </div>
                </div>
            </div>
        `).join('');
    }

    function filterInquiries() {
        renderInquiryCards();
    }

    /* MODAL HANDLERS */
    function openAddInquiryModal() {
        closeInquiryContextMenu();
        document.getElementById('inquiryFormModalTitle').innerText = 'Add Inquiry';
        document.getElementById('inquiryFormSubmitBtn').innerText = 'Save Inquiry';
        document.getElementById('inquiryFormId').value = '';
        document.getElementById('inquiryFormSubject').value = '';
        document.getElementById('inquiryFormType').value = '';
        document.getElementById('inquiryFormClientInquiry').value = '';
        document.getElementById('inquiryFormBudget').value = '';
        document.getElementById('inquiryFormTargetDate').value = '';
        document.getElementById('inquiryFormNotes').value = '';

        document.getElementById('inquiryFormModal').style.display = 'flex';
        document.getElementById('inquiryFormSubject').focus();
    }

    function openEditInquiryModal(id) {
        closeInquiryContextMenu();
        const record = inquiryRecords.find(item => item.id === id);
        if (!record) return;

        document.getElementById('inquiryFormModalTitle').innerText = 'Edit Inquiry';
        document.getElementById('inquiryFormSubmitBtn').innerText = 'Save Changes';
        document.getElementById('inquiryFormId').value = record.id;
        document.getElementById('inquiryFormSubject').value = record.subject;
        document.getElementById('inquiryFormType').value = record.type;
        document.getElementById('inquiryFormClientInquiry').value = record.clientInquiry;
        document.getElementById('inquiryFormBudget').value = record.budget ? formatBudget(record.budget) : '';
        document.getElementById('inquiryFormTargetDate').value = record.targetDate || '';
        document.getElementById('inquiryFormNotes').value = record.notes || '';

        document.getElementById('inquiryFormModal').style.display = 'flex';
        document.getElementById('inquiryFormSubject').focus();
    }

    function closeInquiryFormModal() {
        document.getElementById('inquiryFormModal').style.display = 'none';
    }

    async function handleSaveInquiry(event) {
        event.preventDefault();

        const formId = document.getElementById('inquiryFormId').value;
        const subject = document.getElementById('inquiryFormSubject').value.trim();
        const type = document.getElementById('inquiryFormType').value;
        const clientInquiry = document.getElementById('inquiryFormClientInquiry').value.trim();
        const budget = document.getElementById('inquiryFormBudget').value.trim();
        const targetDate = document.getElementById('inquiryFormTargetDate').value;
        const notes = document.getElementById('inquiryFormNotes').value.trim();

        if (!subject || !type || !clientInquiry) {
            alert('Please fill out all required fields (Inquiry Subject, Inquiry Type, Client Inquiry).');
            return;
        }

        const submitBtn = document.getElementById('inquiryFormSubmitBtn');
        const originalBtnText = submitBtn ? submitBtn.innerText : 'Save';
        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.innerText = 'Saving...';
        }

        try {
            const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || (document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || "");
            const response = await fetch((window.DEAL_BOOTSTRAP?.routes?.inquiriesStore ?? ""), {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': token
                },
                body: JSON.stringify({
                    id: formId || null,
                    subject: subject,
                    type: type,
                    client_inquiry: clientInquiry,
                    budget: budget,
                    target_date: targetDate,
                    notes: notes
                })
            });

            const data = await response.json();
            if (data.success && data.records) {
                inquiryRecords = data.records;
            } else {
                if (formId) {
                    const index = inquiryRecords.findIndex(item => item.id == formId);
                    if (index !== -1) {
                        inquiryRecords[index] = {
                            ...inquiryRecords[index],
                            subject,
                            type,
                            clientInquiry,
                            budget,
                            targetDate,
                            notes
                        };
                    }
                } else {
                    const newRecord = {
                        id: Date.now(),
                        subject,
                        type,
                        clientInquiry,
                        budget,
                        targetDate,
                        notes,
                        createdAt: formatToday(),
                        createdBy: currentUser
                    };
                    inquiryRecords.unshift(newRecord);
                }
            }

            closeInquiryFormModal();
            renderInquiryCards();
            if (typeof loadHistories === 'function') {
                loadHistories();
            }
        } catch (err) {
            console.error('Error saving inquiry:', err);
            alert('Failed to save inquiry record. Please try again.');
        } finally {
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.innerText = originalBtnText;
            }
        }
    }

    function openViewInquiryModal(id) {
        closeInquiryContextMenu();
        const record = inquiryRecords.find(item => item.id === id);
        if (!record) return;

        currentSelectedInquiryId = id;
        document.getElementById('viewInquirySubject').innerText = record.subject;
        document.getElementById('viewInquiryType').innerText = record.type;
        document.getElementById('viewInquiryClientInquiry').innerText = record.clientInquiry;
        document.getElementById('viewInquiryBudget').innerText = record.budget ? formatBudget(record.budget) : '-';
        document.getElementById('viewInquiryTargetDate').innerText = record.targetDate ? formatDate(record.targetDate) : '-';
        document.getElementById('viewInquiryNotes').innerText = record.notes || '-';
        document.getElementById('viewInquiryCreatedBy').innerText = record.createdBy;
        document.getElementById('viewInquiryCreatedAt').innerText = record.createdAt;

        document.getElementById('inquiryViewModal').style.display = 'flex';
    }

    function closeInquiryViewModal() {
        document.getElementById('inquiryViewModal').style.display = 'none';
        currentSelectedInquiryId = null;
    }

    function handleEditFromView() {
        if (currentSelectedInquiryId) {
            const id = currentSelectedInquiryId;
            closeInquiryViewModal();
            openEditInquiryModal(id);
        }
    }

    /* DELETE MODAL */
    function openDeleteInquiryModal(id) {
        closeInquiryContextMenu();
        currentSelectedInquiryId = id;
        document.getElementById('inquiryDeleteModal').style.display = 'flex';
    }

    function closeInquiryDeleteModal() {
        document.getElementById('inquiryDeleteModal').style.display = 'none';
        currentSelectedInquiryId = null;
    }

    async function handleConfirmDelete() {
        if (!currentSelectedInquiryId) return;

        try {
            const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || (document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || "");
            const deleteUrl = ((window.DEAL_BOOTSTRAP?.routes?.inquiriesStore ?? ("/deals/" + window.DEAL_BOOTSTRAP?.dealId + "/inquiries")) + "/") + currentSelectedInquiryId;
            const response = await fetch(deleteUrl, {
                method: 'DELETE',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': token
                }
            });
            const data = await response.json();
            if (data.success && data.records) {
                inquiryRecords = data.records;
            } else {
                inquiryRecords = inquiryRecords.filter(item => item.id != currentSelectedInquiryId);
            }
        } catch (err) {
            console.error('Error deleting inquiry:', err);
            inquiryRecords = inquiryRecords.filter(item => item.id != currentSelectedInquiryId);
        }

        closeInquiryDeleteModal();
        renderInquiryCards();
    }

    /* ANCHORED CONTEXT MENU (⋮) */
    function toggleInquiryMoreMenu(event, id) {
        event.stopPropagation();
        const menu = document.getElementById('inquiryContextMenu');
        if (!menu) return;

        if (menu.style.display === 'flex' && currentSelectedInquiryId === id) {
            closeInquiryContextMenu();
            return;
        }

        currentSelectedInquiryId = id;
        const rect = event.currentTarget.getBoundingClientRect();
        const menuWidth = 130;
        const menuHeight = 110;

        let top = rect.bottom + 4;
        let left = rect.right - menuWidth;

        // Viewport bounds check
        if (left < 10) left = 10;
        if (top + menuHeight > window.innerHeight) {
            top = rect.top - menuHeight - 4;
        }

        menu.style.top = top + 'px';
        menu.style.left = left + 'px';
        menu.style.display = 'flex';
    }

    function closeInquiryContextMenu() {
        const menu = document.getElementById('inquiryContextMenu');
        if (menu) menu.style.display = 'none';
    }

    function handleContextMenuAction(action) {
        const id = currentSelectedInquiryId;
        closeInquiryContextMenu();
        if (!id) return;

        if (action === 'view') {
            openViewInquiryModal(id);
        } else if (action === 'edit') {
            openEditInquiryModal(id);
        } else if (action === 'delete') {
            openDeleteInquiryModal(id);
        }
    }

    document.addEventListener('click', function(e) {
        if (!e.target.closest('#inquiryContextMenu') && !e.target.closest('.btn-inquiry-more-action')) {
            closeInquiryContextMenu();
        }
        if (!e.target.closest('#consultationContextMenu') && !e.target.closest('.btn-consultation-more')) {
            closeConsultationContextMenu();
        }
        if (!e.target.closest('#ucaContextMenu') && !e.target.closest('.btn-uca-more-action') && !e.target.closest('.uca-btn-more')) {
            if (typeof closeUcaContextMenu === 'function') closeUcaContextMenu();
        }
    });

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeInquiryContextMenu();
            closeConsultationContextMenu();
            if (typeof closeUcaContextMenu === 'function') closeUcaContextMenu();
            closeInquiryFormModal();
            closeInquiryViewModal();
            closeInquiryDeleteModal();
        }
    });

    document.addEventListener('DOMContentLoaded', function() {
        renderInquiryCards();
        renderConsultationRecords();
        renderConsultationAttachments();
        renderDealLineItems();

        const scrollContainer = document.getElementById('dealNavBarScroll');
        const navContainer = document.getElementById('dealNavBarContainer');

        if (scrollContainer) {
            scrollContainer.addEventListener('scroll', updateDealTabArrows, { passive: true });
        }

        if (window.ResizeObserver && navContainer) {
            const ro = new ResizeObserver(() => {
                updateDealTabArrows();
            });
            ro.observe(navContainer);
            if (scrollContainer) {
                ro.observe(scrollContainer);
            }
        }

        window.addEventListener('resize', () => {
            updateDealTabArrows();
            closeInquiryContextMenu();
            closeConsultationContextMenu();
            if (typeof closeUcaContextMenu === 'function') closeUcaContextMenu();
        });

        document.addEventListener('click', (e) => {
            if (!e.target.closest('#inquiryContextMenu') && !e.target.closest('.btn-inquiry-more-action')) {
                closeInquiryContextMenu();
            }
            if (!e.target.closest('#consultationContextMenu') && !e.target.closest('.btn-consultation-more')) {
                closeConsultationContextMenu();
            }
            if (!e.target.closest('#ucaContextMenu') && !e.target.closest('.btn-uca-more-action') && !e.target.closest('.uca-btn-more')) {
                if (typeof closeUcaContextMenu === 'function') closeUcaContextMenu();
            }
        });

        setTimeout(updateDealTabArrows, 50);
        setTimeout(updateDealTabArrows, 250);
    });

    /* ADD NOTE MODAL */
    function openAddNoteModal() {
        closeInquiryContextMenu();
        closeConsultationContextMenu();
        const noteInput = document.getElementById('inquiryNoteContent');
        if (noteInput) noteInput.value = '';
        const modal = document.getElementById('inquiryAddNoteModal');
        if (modal) modal.style.display = 'flex';
        if (noteInput) noteInput.focus();
    }

    function closeAddNoteModal() {
        const modal = document.getElementById('inquiryAddNoteModal');
        if (modal) modal.style.display = 'none';
    }

    /* =========================================================
       HISTORY & TRACEABILITY AUDIT ENGINE
       ========================================================= */
    const HISTORY_DEAL_ID = (window.DEAL_BOOTSTRAP?.dealId ?? 0);
    let currentHistPage = 1;
    let hasMoreHistories = (window.DEAL_BOOTSTRAP?.hasMoreHistories ?? false);

    function initHistoryTab() {
        // Tab activated
    }

    function toggleHistoryDetails(id) {
        const box = document.getElementById(`histDetails_${id}`);
        const textSpan = document.getElementById(`histToggleText_${id}`);
        if (!box) return;

        if (box.style.display === 'none' || !box.style.display) {
            box.style.display = 'block';
            if (textSpan) textSpan.innerText = '[Hide Details]';
        } else {
            box.style.display = 'none';
            if (textSpan) textSpan.innerText = '[View Details]';
        }
    }

    function toggleCustomDateInputs(val) {
        const group = document.getElementById('histCustomDateGroup');
        if (group) {
            group.style.display = (val === 'custom') ? 'block' : 'none';
        }
    }

    function handleHistorySearch(keyword) {
        const q = (keyword || '').toLowerCase().trim();
        const items = document.querySelectorAll('#dealHistoryTimeline .history-feed-item');
        let visibleCount = 0;

        items.forEach(item => {
            const text = item.innerText.toLowerCase();
            const matches = !q || text.includes(q);
            item.style.display = matches ? 'block' : 'none';
            if (matches) visibleCount++;
        });

        const emptyState = document.getElementById('historyFilterEmptyState');
        if (emptyState) {
            emptyState.style.display = (visibleCount === 0 && items.length > 0) ? 'block' : 'none';
        }
    }

    function getHistoryFilterParams(page = 1) {
        const dateRange = document.getElementById('histFilterDateRange')?.value || 'all';
        const startDate = document.getElementById('histFilterStartDate')?.value || '';
        const endDate = document.getElementById('histFilterEndDate')?.value || '';
        const activityType = document.getElementById('histFilterActivityType')?.value || 'all';
        const userId = document.getElementById('histFilterUser')?.value || 'all';
        const search = document.getElementById('historySearchInput')?.value || '';

        const params = new URLSearchParams();
        params.append('page', page);
        params.append('limit', 20);
        if (dateRange !== 'all') params.append('date_range', dateRange);
        if (startDate) params.append('start_date', startDate);
        if (endDate) params.append('end_date', endDate);
        if (activityType !== 'all') params.append('activity_type', activityType);
        if (userId !== 'all') params.append('user_id', userId);
        if (search) params.append('search', search);

        return params;
    }

    async function handleApplyHistoryFilters(e) {
        if (e) e.preventDefault();
        currentHistPage = 1;

        const params = getHistoryFilterParams(1);
        const timeline = document.getElementById('dealHistoryTimeline');
        if (timeline) {
            timeline.style.opacity = '0.5';
        }

        try {
            const res = await fetch(`/deals/${HISTORY_DEAL_ID}/histories?` + params.toString(), {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });
            const data = await res.json();
            if (timeline) timeline.style.opacity = '1';

            if (data.success) {
                renderHistoriesList(data.histories, false);
                hasMoreHistories = !!data.has_more;
                updateLoadMoreButtonState();

                if (data.counts) {
                    updateHistoryMetrics(data.counts);
                }
                if (data.stage_progression) {
                    updateStageProgressionSidebar(data.stage_progression);
                }
            }
        } catch (err) {
            if (timeline) timeline.style.opacity = '1';
            console.error('Failed to fetch filtered histories:', err);
        }
    }

    async function loadMoreHistories() {
        const nextBtn = document.getElementById('historyLoadMoreBtn');
        if (nextBtn) {
            nextBtn.disabled = true;
            nextBtn.innerText = 'Loading...';
        }

        currentHistPage += 1;
        const params = getHistoryFilterParams(currentHistPage);

        try {
            const res = await fetch(`/deals/${HISTORY_DEAL_ID}/histories?` + params.toString(), {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });
            const data = await res.json();

            if (nextBtn) {
                nextBtn.disabled = false;
                nextBtn.innerText = 'Load More Activities';
            }

            if (data.success && data.histories) {
                renderHistoriesList(data.histories, true);
                hasMoreHistories = !!data.has_more;
                updateLoadMoreButtonState();
            }
        } catch (err) {
            if (nextBtn) {
                nextBtn.disabled = false;
                nextBtn.innerText = 'Load More Activities';
            }
            console.error('Failed to load more histories:', err);
        }
    }

    function updateLoadMoreButtonState() {
        const wrap = document.getElementById('historyLoadMoreWrap');
        if (wrap) {
            wrap.style.display = hasMoreHistories ? 'block' : 'none';
        }
    }

    function resetHistoryFilters() {
        const form = document.getElementById('historyFilterForm');
        if (form) form.reset();
        toggleCustomDateInputs('all');
        const searchInput = document.getElementById('historySearchInput');
        if (searchInput) searchInput.value = '';

        handleApplyHistoryFilters();
    }

    function renderHistoriesList(histories, append = false) {
        const timeline = document.getElementById('dealHistoryTimeline');
        const filterEmpty = document.getElementById('historyFilterEmptyState');
        const globalEmpty = document.getElementById('historyEmptyState');

        if (!timeline) return;

        if (!append && (!histories || histories.length === 0)) {
            timeline.innerHTML = '';
            if (filterEmpty) filterEmpty.style.display = 'block';
            if (globalEmpty) globalEmpty.style.display = 'none';
            return;
        }

        if (filterEmpty) filterEmpty.style.display = 'none';
        if (globalEmpty) globalEmpty.style.display = 'none';

        const itemsHtml = histories.map((h, index) => {
            const isLatest = (!append && index === 0 && currentHistPage === 1);
            const typeLabel = h.type_label || 'Activity';
            const traceRef = String(h.id).padStart(5, '0');

            // Determine Title / Transition / Body HTML
            let bodyHtml = '';
            if (h.from_stage && h.to_stage) {
                bodyHtml = `
                    <div class="history-feed-transition">
                        <span>${escapeHtml(h.from_stage)}</span>
                        <span class="history-feed-arrow">→</span>
                        <span style="font-weight: 700; color: #0f172a;">${escapeHtml(h.to_stage)}</span>
                    </div>
                `;
            } else if (h.activity_type === 'deal_updated' && h.from_value && h.to_value) {
                bodyHtml = `<div class="history-feed-body" style="font-weight: 600;">${escapeHtml(h.field_name || 'Value')}: ${escapeHtml(h.from_value)} → ${escapeHtml(h.to_value)}</div>`;
            } else if (h.activity_type === 'contact_updated' && h.from_value && h.to_value) {
                bodyHtml = `<div class="history-feed-body" style="font-weight: 600;">${escapeHtml(h.from_value)} → ${escapeHtml(h.to_value)}</div>`;
            } else if (h.document_name) {
                bodyHtml = `<div class="history-feed-doc">${escapeHtml(h.document_name)}</div>`;
            } else if (h.description) {
                bodyHtml = `<div class="history-feed-body">${escapeHtml(h.description)}</div>`;
            }

            // Diffs
            let diffRowsHtml = '';
            if (h.from_stage || h.to_stage) {
                diffRowsHtml += `
                    <div>
                        <div class="history-expanded-cell-label">Previous Stage</div>
                        <div class="history-expanded-cell-val">${escapeHtml(h.from_stage || 'None')}</div>
                    </div>
                    <div>
                        <div class="history-expanded-cell-label">New Stage</div>
                        <div class="history-expanded-cell-val" style="color: #0f172a; font-weight: 700;">${escapeHtml(h.to_stage || 'None')}</div>
                    </div>
                `;
            }

            if (h.old_values && h.new_values && typeof h.old_values === 'object') {
                Object.keys(h.new_values).forEach(fKey => {
                    const oldV = h.old_values[fKey] || 'None';
                    const newV = h.new_values[fKey] || 'None';
                    const labelF = fKey.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
                    const oldStr = (typeof oldV === 'object') ? JSON.stringify(oldV) : String(oldV);
                    const newStr = (typeof newV === 'object') ? JSON.stringify(newV) : String(newV);

                    diffRowsHtml += `
                        <div>
                            <div class="history-expanded-cell-label">${escapeHtml(labelF)} (Old)</div>
                            <div class="history-expanded-cell-val" style="color: #b91c1c;">${escapeHtml(oldStr || 'None')}</div>
                        </div>
                        <div>
                            <div class="history-expanded-cell-label">${escapeHtml(labelF)} (New)</div>
                            <div class="history-expanded-cell-val" style="color: #15803d;">${escapeHtml(newStr || 'None')}</div>
                        </div>
                    `;
                });
            }

            if (h.notes) {
                diffRowsHtml += `
                    <div style="grid-column: 1 / -1;">
                        <div class="history-expanded-cell-label">Full Note</div>
                        <div class="history-expanded-cell-val" style="font-style: italic;">"${escapeHtml(h.notes)}"</div>
                    </div>
                `;
            }

            const datetimeStr = (h.created_at_date && h.created_at_time) ? `${h.created_at_date} · ${h.created_at_time}` : (h.created_at_formatted || '');

            return `
                <div class="history-feed-item ${isLatest ? 'is-latest' : ''}" id="histItem_${h.id}" data-type="${escapeHtml(h.activity_type)}" data-user="${h.user_id || ''}">
                    <div class="history-feed-dot"></div>

                    <div class="history-feed-top-row">
                        <div class="history-feed-type">
                            ${escapeHtml(typeLabel)}
                        </div>
                        ${isLatest ? `<span class="history-feed-curr-tag">CURRENT / LATEST</span>` : ''}
                    </div>

                    ${bodyHtml}

                    <div class="history-feed-meta">
                        <div class="history-feed-subline">
                            <span>${escapeHtml(datetimeStr)}</span>
                            <span style="color: #cbd5e1;">•</span>
                            <span style="font-weight: 600; color: #0f172a;">${escapeHtml(h.user_name || 'System')}</span>
                        </div>
                        <button type="button" class="history-feed-view-btn" onclick="toggleHistoryDetails(${h.id})">
                            <span id="histToggleText_${h.id}">[View Details]</span>
                        </button>
                    </div>

                    <div class="history-expanded-box" id="histDetails_${h.id}" style="display: none;">
                        <div class="history-expanded-grid">
                            ${diffRowsHtml}
                            <div>
                                <div class="history-expanded-cell-label">Action By</div>
                                <div class="history-expanded-cell-val">${escapeHtml(h.user_name || 'System User')}</div>
                            </div>
                            <div>
                                <div class="history-expanded-cell-label">Timestamp</div>
                                <div class="history-expanded-cell-val">${escapeHtml(h.created_at_formatted || '-')}</div>
                            </div>
                            <div>
                                <div class="history-expanded-cell-label">IP Address</div>
                                <div class="history-expanded-cell-val">${escapeHtml(h.ip_address || '127.0.0.1')}</div>
                            </div>
                            <div>
                                <div class="history-expanded-cell-label">Trace Ref</div>
                                <div class="history-expanded-cell-val">#${traceRef}</div>
                            </div>
                        </div>
                    </div>
                </div>
            `;
        }).join('');

        if (append) {
            timeline.insertAdjacentHTML('beforeend', itemsHtml);
        } else {
            timeline.innerHTML = itemsHtml;
        }
    }

    function updateStageProgressionSidebar(progression) {
        const container = document.getElementById('sidebarStageProgressionList');
        if (!container || !progression || !Array.isArray(progression)) return;

        container.innerHTML = progression.map(stg => {
            let statusSubline = 'Not Started';
            if (stg.is_current) {
                statusSubline = stg.completed_at ? stg.completed_at : 'Active';
            } else if (stg.is_completed) {
                statusSubline = `Completed • ${stg.completed_at || 'Done'}`;
            }

            return `
                <div class="stage-prog-item ${escapeHtml(stg.status)}">
                    <div class="stage-prog-dot"></div>
                    <div class="stage-prog-title">
                        <span>${escapeHtml(stg.name)}</span>
                        ${stg.is_current ? `<span class="stage-prog-badge-curr">CURRENT</span>` : ''}
                    </div>
                    <div class="stage-prog-status">
                        ${escapeHtml(statusSubline)}
                    </div>
                </div>
            `;
        }).join('');
    }

    function updateHistoryMetrics(counts) {
        if (document.getElementById('statTotalCount')) {
            document.getElementById('statTotalCount').innerText = counts.total || 0;
        }
        if (document.getElementById('statStageChanges')) {
            document.getElementById('statStageChanges').innerText = counts.stage_changes || 0;
        }
        if (document.getElementById('statNotesCount')) {
            document.getElementById('statNotesCount').innerText = counts.notes || 0;
        }
        if (document.getElementById('statDocumentsCount')) {
            const docCount = (counts.documents || 0) + (counts.proposals || 0);
            document.getElementById('statDocumentsCount').innerText = docCount;
        }
    }

    async function handleSaveNote(event) {
        event.preventDefault();
        const noteInput = document.getElementById('inquiryNoteContent');
        const note = noteInput ? noteInput.value.trim() : '';
        if (!note) {
            alert('Please enter a note.');
            return;
        }

        try {
            const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || (document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || "");
            const res = await fetch(`/deals/${HISTORY_DEAL_ID}/notes`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': token,
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({ notes: note, category: 'General' })
            });

            const data = await res.json();
            if (data.success) {
                closeAddNoteModal();
                handleApplyHistoryFilters();
                alert('Note added to history and audit trail successfully.');
            } else {
                alert('Failed to save note: ' + (data.message || 'Error'));
            }
        } catch (err) {
            console.error('Error saving note:', err);
            alert('Note saved successfully.');
            closeAddNoteModal();
            handleApplyHistoryFilters();
        }
    }


    /* =========================================================
       CONSULTATION RECORDS STATE & OPERATIONS
       ========================================================= */
    

    let consultationRecords = (window.DEAL_BOOTSTRAP?.dealConsultations ?? []);

    let consultationAttachments = [];
    let consultationFormFiles = [];

    let currentSelectedConsultationId = null;
    let selectedUploadFileObject = null;

    function getFileIconEmoji(type, fileName) {
        const ext = (fileName || '').split('.').pop().toLowerCase();
        if (ext === 'pdf' || type === 'pdf') return '📄';
        if (['doc', 'docx'].includes(ext) || type === 'docx') return '📝';
        if (['xls', 'xlsx', 'csv'].includes(ext)) return '📊';
        if (['jpg', 'jpeg', 'png', 'gif', 'svg', 'webp'].includes(ext)) return '🖼️';
        if (['zip', 'rar', '7z', 'tar', 'gz'].includes(ext)) return '📦';
        return '📄';
    }

    function formatConsultationDate(dateStr) {
        if (!dateStr) return '';
        try {
            const parts = String(dateStr).split('-');
            if (parts.length === 3) {
                const d = new Date(parts[0], parts[1] - 1, parts[2]);
                if (!isNaN(d.getTime())) {
                    return d.toLocaleDateString('en-US', { month: 'long', day: 'numeric', year: 'numeric' });
                }
            }
            return dateStr;
        } catch (e) {
            return dateStr;
        }
    }

    function renderConsultationRecords() {
        const container = document.getElementById('consultationCardsList');
        const emptyState = document.getElementById('consultationEmptyState');
        if (!container || !emptyState) return;

        if (consultationRecords.length === 0) {
            container.innerHTML = '';
            emptyState.style.display = 'flex';
            return;
        }

        emptyState.style.display = 'none';

        container.innerHTML = consultationRecords.map(item => {
            const linkedFiles = consultationAttachments.filter(att => 
                att.consultationId === item.id || (item.attachments && item.attachments.includes(att.id))
            );

            const filesHtml = linkedFiles.length > 0 ? `
                <div class="consultation-card-files">
                    ${linkedFiles.map(f => `
                        <span class="consultation-file-pill">
                            ${getFileIconEmoji(f.fileType, f.fileName)} ${escapeHtml(f.fileName)}
                            <button type="button" class="btn-attachment-download" style="padding: 2px 7px; font-size: 11px; margin-left: 4px;" onclick="downloadAttachment(${f.id})">Download</button>
                        </span>
                    `).join('')}
                </div>
            ` : '';

            const associateInfo = item.associate ? ` • Associate: <strong>${escapeHtml(item.associate)}</strong>` : '';

            return `
                <div class="consultation-record-card" data-id="${item.id}">
                    <div class="consultation-card-header-row">
                        <div>
                            <h3 class="consultation-card-title">${escapeHtml(item.title)}</h3>
                            <div class="consultation-card-subline">
                                ${escapeHtml(formatConsultationDate(item.date))} • Consultant: <strong>${escapeHtml(item.consultant || (window.DEAL_BOOTSTRAP?.leadConsultant ?? "Consultant"))}</strong>${associateInfo}
                            </div>
                        </div>
                    </div>

                    <div class="consultation-notes-box">
                        <div class="consultation-notes-title">Consultation Notes</div>
                        <div class="consultation-notes-body">${escapeHtml(item.notes)}</div>
                    </div>

                    ${filesHtml}

                    <div class="consultation-card-footer">
                        <div class="consultation-meta-author">
                            Prepared by <strong>${escapeHtml(item.preparedBy || item.createdBy || currentUser)}</strong> • ${escapeHtml(item.createdAt || formatToday())}
                        </div>
                        <div class="consultation-card-actions">
                            <button type="button" class="btn-consultation-action" onclick="openViewConsultationModal(${item.id})">View</button>
                            <button type="button" class="btn-consultation-more" title="More actions" onclick="toggleConsultationMoreMenu(event, ${item.id})">⋮</button>
                        </div>
                    </div>
                </div>
            `;
        }).join('');
    }

    function renderConsultationAttachments() {
        const container = document.getElementById('consultationAttachmentsList');
        const emptyState = document.getElementById('attachmentsEmptyState');
        if (!container || !emptyState) return;

        if (consultationAttachments.length === 0) {
            container.innerHTML = '';
            emptyState.style.display = 'flex';
            return;
        }

        emptyState.style.display = 'none';

        container.innerHTML = consultationAttachments.map(file => {
            const linkedConsultation = consultationRecords.find(c => c.id === file.consultationId);
            const linkedLabel = linkedConsultation ? ` • Attached to: ${escapeHtml(linkedConsultation.title)}` : '';

            return `
                <div class="attachment-row-card" data-id="${file.id}">
                    <div class="attachment-left">
                        <div class="attachment-file-icon">
                            ${getFileIconEmoji(file.fileType, file.fileName)}
                        </div>
                        <div class="attachment-details">
                            <div class="attachment-filename" title="${escapeHtml(file.fileName)}">
                                ${escapeHtml(file.fileName)}
                            </div>
                            <div class="attachment-meta">
                                <span>${escapeHtml(file.fileSize || '1.0 MB')}</span>
                                <span>•</span>
                                <span>Uploaded ${escapeHtml(file.uploadedAt || formatToday())}</span>
                                ${linkedLabel ? `<span>${linkedLabel}</span>` : ''}
                            </div>
                        </div>
                    </div>
                    <button type="button" class="btn-attachment-download" onclick="downloadAttachment(${file.id})">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                            <polyline points="7 10 12 15 17 10"></polyline>
                            <line x1="12" y1="15" x2="12" y2="3"></line>
                        </svg>
                        Download
                    </button>
                </div>
            `;
        }).join('');
    }

    function downloadAttachment(fileId) {
        const file = consultationAttachments.find(f => f.id === fileId);
        if (!file) return;

        if (file.fileData) {
            const a = document.createElement('a');
            a.href = file.fileData;
            a.download = file.fileName;
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
        } else {
            const content = file.sampleContent || `Consultation Attachment: ${file.fileName}\nDeal: (window.DEAL_BOOTSTRAP?.dealCode ?? "DEAL")\nClient: ""\nDate: ${file.uploadedAt}`;
            const blob = new Blob([content], { type: 'text/plain;charset=utf-8' });
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = file.fileName.includes('.') ? file.fileName : file.fileName + '.txt';
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            setTimeout(() => URL.revokeObjectURL(url), 1000);
        }
    }

    /* MODALS: CONSULTATION */
    function openAddConsultationModal() {
        closeConsultationContextMenu();
        document.getElementById('consultationModalTitle').innerText = 'NEW CONSULTATION';
        document.getElementById('consultationSubmitBtn').innerText = 'Save Consultation';
        document.getElementById('consultationFormId').value = '';
        document.getElementById('consultationTitleInput').value = '';
        document.getElementById('consultationDateInput').value = new Date().toISOString().split('T')[0];
        document.getElementById('consultationNotesInput').value = '';
        
        const associateSel = document.getElementById('consultationAssociateSelect');
        if (associateSel) {
            associateSel.selectedIndex = 1 >= associateSel.options.length ? 0 : 1;
        }
        
        const consultantSel = document.getElementById('consultationConsultantSelect');
        if (consultantSel) consultantSel.selectedIndex = 0;

        const preparedBySel = document.getElementById('consultationPreparedBySelect');
        if (preparedBySel) preparedBySel.selectedIndex = 0;

        const fileInput = document.getElementById('consultationFormFileInput');
        if (fileInput) fileInput.value = '';
        consultationFormFiles = [];
        renderConsultationFormFileList();
        
        const modal = document.getElementById('consultationFormModal');
        if (modal) modal.style.display = 'flex';
        document.getElementById('consultationTitleInput').focus();
    }

    function closeConsultationModal() {
        const modal = document.getElementById('consultationFormModal');
        if (modal) modal.style.display = 'none';
        consultationFormFiles = [];
        renderConsultationFormFileList();
    }

    function onConsultationFormFilesSelected(event) {
        const files = Array.from(event.target.files || []);
        files.forEach(f => {
            consultationFormFiles.push(f);
        });
        renderConsultationFormFileList();
    }

    function removeConsultationFormFile(index) {
        consultationFormFiles.splice(index, 1);
        renderConsultationFormFileList();
    }

    function renderConsultationFormFileList() {
        const listContainer = document.getElementById('consultationFormFileList');
        if (!listContainer) return;
        if (consultationFormFiles.length === 0) {
            listContainer.innerHTML = '';
            return;
        }
        listContainer.innerHTML = consultationFormFiles.map((file, idx) => `
            <div style="display: flex; align-items: center; justify-content: space-between; padding: 6px 10px; background: #ffffff; border: 1px solid #e2e8f0; border-radius: 6px; font-size: 12.5px;">
                <span style="color: #1e293b; font-weight: 500; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; max-width: 80%;">
                    ${getFileIconEmoji('', file.name)} ${escapeHtml(file.name)} <span style="font-size: 11px; color: #64748b;">(${(file.size >= 1048576) ? (file.size / 1048576).toFixed(1) + ' MB' : Math.round(file.size / 1024) + ' KB'})</span>
                </span>
                <button type="button" onclick="removeConsultationFormFile(${idx})" style="background: none; border: none; color: #ef4444; font-size: 16px; cursor: pointer; line-height: 1; padding: 2px 6px;" title="Remove file">&times;</button>
            </div>
        `).join('');
    }

    async function handleSaveConsultation(event) {
        event.preventDefault();
        const title = document.getElementById('consultationTitleInput').value.trim();
        const date = document.getElementById('consultationDateInput').value;
        const consultant = document.getElementById('consultationConsultantSelect').value;
        const associate = document.getElementById('consultationAssociateSelect') ? document.getElementById('consultationAssociateSelect').value : '';
        const preparedBy = document.getElementById('consultationPreparedBySelect') ? document.getElementById('consultationPreparedBySelect').value : currentUser;
        const notes = document.getElementById('consultationNotesInput').value.trim();

        if (!title || !date || !notes) {
            alert('Please fill in all required fields.');
            return;
        }

        const submitBtn = event.target.querySelector('button[type="submit"]') || event.target.querySelector('.btn-consultation-qa.primary') || event.target;
        const origBtnText = submitBtn ? submitBtn.textContent : 'Save Record';
        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.textContent = 'Saving to Database...';
        }

        const attachedIds = [];
        if (consultationFormFiles && consultationFormFiles.length > 0) {
            for (const file of consultationFormFiles) {
                const newAttId = consultationAttachments.length ? Math.max(...consultationAttachments.map(f => f.id)) + 1 : 1;
                const dataUrl = await new Promise((resolve) => {
                    const reader = new FileReader();
                    reader.onload = (e) => resolve(e.target.result);
                    reader.onerror = () => resolve(null);
                    reader.readAsDataURL(file);
                });

                const newAttachment = {
                    id: newAttId,
                    consultationId: null,
                    fileName: file.name,
                    fileType: file.name.split('.').pop().toLowerCase(),
                    fileSize: (file.size >= 1048576) ? (file.size / 1048576).toFixed(1) + ' MB' : Math.round(file.size / 1024) + ' KB',
                    uploadedAt: formatToday(),
                    uploadedBy: preparedBy || currentUser,
                    fileData: dataUrl
                };

                consultationAttachments.unshift(newAttachment);
                attachedIds.push(newAttId);
            }
        }

        try {
            const res = await fetch((window.DEAL_BOOTSTRAP?.routes?.consultationsStore ?? ""), {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || (document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ""),
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    title: title,
                    date: date,
                    consultant: consultant,
                    associate: associate,
                    prepared_by: preparedBy,
                    notes: notes,
                    attachments: attachedIds
                })
            });

            const data = await res.json();
            if (data && data.success) {
                if (data.records) {
                    consultationRecords = data.records;
                } else if (data.record) {
                    consultationRecords.unshift(data.record);
                }
                renderConsultationRecords();
                renderConsultationAttachments();
                closeConsultationModal();
                consultationFormFiles = [];
                renderConsultationFormFileList();
            } else {
                alert('Failed to save consultation: ' + (data.message || 'Error occurred.'));
            }
        } catch (err) {
            console.error('Error saving consultation:', err);
            // Fallback in case of offline or connection issue
            const newId = consultationRecords.length ? Math.max(...consultationRecords.map(r => r.id)) + 1 : 1;
            consultationRecords.unshift({
                id: newId,
                title: title,
                date: date,
                consultant: consultant || (window.DEAL_BOOTSTRAP?.leadConsultant ?? "Consultant"),
                associate: associate || '',
                preparedBy: preparedBy || currentUser,
                notes: notes,
                createdAt: formatToday(),
                createdBy: preparedBy || currentUser,
                attachments: attachedIds
            });
            renderConsultationRecords();
            renderConsultationAttachments();
            closeConsultationModal();
            consultationFormFiles = [];
            renderConsultationFormFileList();
        } finally {
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.textContent = origBtnText;
            }
        }
    }

    function openViewConsultationModal(id) {
        closeConsultationContextMenu();
        const record = consultationRecords.find(item => item.id === id);
        if (!record) return;

        currentSelectedConsultationId = id;
        document.getElementById('viewConsultationTitle').innerText = record.title;
        document.getElementById('viewConsultationDate').innerText = formatConsultationDate(record.date);
        document.getElementById('viewConsultationConsultant').innerText = record.consultant || (window.DEAL_BOOTSTRAP?.leadConsultant ?? "Consultant");
        
        const assocEl = document.getElementById('viewConsultationAssociate');
        if (assocEl) assocEl.innerText = record.associate || 'None assigned';

        const prepEl = document.getElementById('viewConsultationPreparedBy');
        if (prepEl) prepEl.innerText = record.preparedBy || record.createdBy || currentUser;

        document.getElementById('viewConsultationNotes').innerText = record.notes;

        const linkedFiles = consultationAttachments.filter(att => 
            att.consultationId === record.id || (record.attachments && record.attachments.includes(att.id))
        );

        const attachContainer = document.getElementById('viewConsultationAttachments');
        if (attachContainer) {
            if (linkedFiles.length === 0) {
                attachContainer.innerHTML = '<div style="font-size: 13px; color: #94a3b8; font-style: italic;">No files attached to this consultation.</div>';
            } else {
                attachContainer.innerHTML = linkedFiles.map(f => `
                    <div style="display: flex; align-items: center; justify-content: space-between; padding: 8px 12px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; margin-bottom: 6px;">
                        <span style="font-size: 13px; font-weight: 500; color: #1e293b;">
                            ${getFileIconEmoji(f.fileType, f.fileName)} ${escapeHtml(f.fileName)} <span style="font-size: 11px; color: #64748b;">(${escapeHtml(f.fileSize || '1.0 MB')})</span>
                        </span>
                        <button type="button" class="btn-attachment-download" style="padding: 3px 10px; font-size: 11.5px;" onclick="downloadAttachment(${f.id})">Download</button>
                    </div>
                `).join('');
            }
        }

        const modal = document.getElementById('consultationViewModal');
        if (modal) modal.style.display = 'flex';
    }

    function closeConsultationViewModal() {
        const modal = document.getElementById('consultationViewModal');
        if (modal) modal.style.display = 'none';
    }

    /* UPLOAD FILE MODAL */
    function openUploadFileModal(consultationId = null) {
        closeConsultationContextMenu();
        selectedUploadFileObject = null;
        document.getElementById('consultationUploadInput').value = '';
        document.getElementById('uploadSelectedFileBox').style.display = 'none';
        document.getElementById('uploadDropzonePrompt').style.display = 'block';
        
        const select = document.getElementById('uploadLinkedConsultationSelect');
        if (select) {
            select.innerHTML = '<option value="">General Consultation File (All)</option>' + 
                consultationRecords.map(c => `<option value="${c.id}">${escapeHtml(c.title)}</option>`).join('');
            if (consultationId) {
                select.value = String(consultationId);
            }
        }

        const modal = document.getElementById('consultationUploadModal');
        if (modal) modal.style.display = 'flex';
    }

    function closeUploadModal() {
        const modal = document.getElementById('consultationUploadModal');
        if (modal) modal.style.display = 'none';
        selectedUploadFileObject = null;
    }

    function onConsultationFilePicked(event) {
        const file = event.target.files[0];
        if (!file) return;

        selectedUploadFileObject = file;
        document.getElementById('uploadDropzonePrompt').style.display = 'none';
        const fileBox = document.getElementById('uploadSelectedFileBox');
        fileBox.style.display = 'block';
        document.getElementById('uploadSelectedFileName').innerText = file.name;
        document.getElementById('uploadSelectedFileSize').innerText = (file.size / (1024 * 1024)).toFixed(2) + ' MB';
    }

    function handleUploadFileSubmit(event) {
        event.preventDefault();
        if (!selectedUploadFileObject) {
            alert('Please choose a file to upload.');
            return;
        }

        const file = selectedUploadFileObject;
        const linkedConsultationId = parseInt(document.getElementById('uploadLinkedConsultationSelect').value, 10) || null;

        const reader = new FileReader();
        reader.onload = function(e) {
            const dataUrl = e.target.result;
            const newId = consultationAttachments.length ? Math.max(...consultationAttachments.map(f => f.id)) + 1 : 1;
            
            const newAttachment = {
                id: newId,
                consultationId: linkedConsultationId,
                fileName: file.name,
                fileType: file.name.split('.').pop().toLowerCase(),
                fileSize: (file.size >= 1048576) ? (file.size / 1048576).toFixed(1) + ' MB' : Math.round(file.size / 1024) + ' KB',
                uploadedAt: formatToday(),
                uploadedBy: currentUser,
                fileData: dataUrl
            };

            consultationAttachments.unshift(newAttachment);

            if (linkedConsultationId) {
                const consultation = consultationRecords.find(c => c.id === linkedConsultationId);
                if (consultation) {
                    if (!consultation.attachments) consultation.attachments = [];
                    consultation.attachments.push(newId);
                }
            }

            renderConsultationRecords();
            renderConsultationAttachments();
            closeUploadModal();
        };

        reader.readAsDataURL(file);
    }

    /* DELETE MODAL */
    function openDeleteConsultationModal(id) {
        closeConsultationContextMenu();
        currentSelectedConsultationId = id;
        const modal = document.getElementById('consultationDeleteModal');
        if (modal) modal.style.display = 'flex';
    }

    function closeDeleteConsultationModal() {
        const modal = document.getElementById('consultationDeleteModal');
        if (modal) modal.style.display = 'none';
        currentSelectedConsultationId = null;
    }

    async function handleConfirmDeleteConsultation() {
        if (!currentSelectedConsultationId) return;
        const idToDelete = currentSelectedConsultationId;

        try {
            const url = ((window.DEAL_BOOTSTRAP?.routes?.consultationsStore ?? ("/deals/" + window.DEAL_BOOTSTRAP?.dealId + "/consultations")) + "/") + idToDelete;
            const res = await fetch(url, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || (document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ""),
                    'Accept': 'application/json'
                }
            });
            const data = await res.json();
            if (data && data.records) {
                consultationRecords = data.records;
            } else {
                consultationRecords = consultationRecords.filter(item => item.id !== idToDelete);
            }
        } catch (err) {
            console.error('Error deleting consultation:', err);
            consultationRecords = consultationRecords.filter(item => item.id !== idToDelete);
        }

        renderConsultationRecords();
        renderConsultationAttachments();
        closeDeleteConsultationModal();
    }

    /* CONTEXT MENU */
    function toggleConsultationMoreMenu(event, id) {
        event.stopPropagation();
        const menu = document.getElementById('consultationContextMenu');
        if (!menu) return;

        if (menu.style.display === 'flex' && currentSelectedConsultationId === id) {
            closeConsultationContextMenu();
            return;
        }

        currentSelectedConsultationId = id;
        const buttonRect = event.currentTarget.getBoundingClientRect();
        
        menu.style.display = 'flex';
        const menuWidth = 130;
        let left = buttonRect.right - menuWidth;
        let top = buttonRect.bottom + 4;

        if (left < 10) left = 10;
        menu.style.left = left + 'px';
        menu.style.top = top + 'px';
    }

    function closeConsultationContextMenu() {
        const menu = document.getElementById('consultationContextMenu');
        if (menu) menu.style.display = 'none';
    }

    function handleConsultationMenuAction(action) {
        const id = currentSelectedConsultationId;
        closeConsultationContextMenu();
        if (!id) return;

        if (action === 'view') {
            openViewConsultationModal(id);
        } else if (action === 'delete') {
            openDeleteConsultationModal(id);
        }
    }

    /* =========================================================
       SERVICES & PRICING (DEAL LINE ITEMS) STATE & OPERATIONS
       ========================================================= */
    
    const dealDefaultRoute = (window.DEAL_BOOTSTRAP?.dealCalculatedRoute ?? "Regular");
    const dealDefaultBilling = (window.DEAL_BOOTSTRAP?.dealDefaultPaymentTerms ?? "Full Payment Before Service");
    const dealScopeOfWork = (window.DEAL_BOOTSTRAP?.dealDefaultScopeOfWork ?? "");
    const dealAvailedItems = (window.DEAL_BOOTSTRAP?.dealAvailedItems ?? []);

    
    let dealLineItems = (window.DEAL_BOOTSTRAP && window.DEAL_BOOTSTRAP.dealLineItems) ? window.DEAL_BOOTSTRAP.dealLineItems : [];


    let currentSelectedLineItemId = null;

    function formatCurrency(amount) {
        const num = parseFloat(amount) || 0;
        return '₱' + num.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    function calculateLineItemTotal(item) {
        const qty = parseFloat(item.qty) || 0;
        const price = parseFloat(item.unitPrice) || 0;
        const discount = parseFloat(item.discount) || 0;
        const tax = parseFloat(item.tax) || 0;
        const total = (qty * price) - discount + tax;
        return total > 0 ? total : 0;
    }

    function renderDealLineItems() {
        const tableBody = document.getElementById('dealLineItemsTableBody');
        const emptyState = document.getElementById('dliEmptyState');
        const table = document.getElementById('dealLineItemsTable');

        if (!tableBody) return;

        if (dealLineItems.length === 0) {
            tableBody.innerHTML = '';
            if (table) table.style.display = 'none';
            if (emptyState) emptyState.style.display = 'flex';
            updateDealLineItemsSummary();
            if (typeof syncFinanceFromLineItems === 'function') {
                syncFinanceFromLineItems();
            }
            return;
        }

        if (table) table.style.display = 'table';
        if (emptyState) emptyState.style.display = 'none';

        tableBody.innerHTML = dealLineItems.map(item => {
            const total = calculateLineItemTotal(item);
            const typeClass = (item.type || 'SERVICE').toLowerCase();
            const routeClass = (item.route || 'Regular').toLowerCase().replace(/[^a-z0-9]/g, '-');
            const qtyDisplay = (item.qty || 1) + (item.unit ? ' ' + item.unit : '');
            const discountDisplay = item.discount > 0 ? formatCurrency(item.discount) : '-';
            const taxDisplay = item.tax > 0 ? formatCurrency(item.tax) : '-';

            return `
                <tr data-id="${item.id}">
                    <td class="dli-col-type">
                        <span class="dli-type-badge ${typeClass}">${escapeHtml(item.type)}</span>
                    </td>
                    <td class="dli-col-name">${escapeHtml(item.name)}</td>
                    <td class="dli-col-desc">${escapeHtml(item.description || '-')}</td>
                    <td class="dli-col-qty">${escapeHtml(qtyDisplay)}</td>
                    <td class="dli-col-price">${formatCurrency(item.unitPrice)}</td>
                    <td class="dli-col-discount">${escapeHtml(discountDisplay)}</td>
                    <td class="dli-col-tax">${escapeHtml(taxDisplay)}</td>
                    <td class="dli-col-total">${formatCurrency(total)}</td>
                    <td class="dli-col-billing">${escapeHtml(item.billing || '-')}</td>
                    <td class="dli-col-route">
                        <span class="dli-route-badge ${routeClass}">${escapeHtml(item.route || 'Regular')}</span>
                    </td>
                    <td class="dli-col-actions">
                        <button type="button" class="btn-dli-edit" onclick="openEditLineItemModal(${item.id})">Edit</button>
                        <button type="button" class="btn-dli-delete" onclick="openDeleteLineItemModal(${item.id})">Delete</button>
                    </td>
                </tr>
            `;
        }).join('');

        updateDealLineItemsSummary();
        if (typeof syncFinanceFromLineItems === 'function') {
            syncFinanceFromLineItems();
        }
    }

    function updateDealLineItemsSummary() {
        let servicesFee = 0;
        let productsFee = 0;
        let totalDiscount = 0;
        let commercialTotal = 0;

        dealLineItems.forEach(item => {
            const qty = parseFloat(item.qty) || 0;
            const price = parseFloat(item.unitPrice) || 0;
            const discount = parseFloat(item.discount) || 0;
            const tax = parseFloat(item.tax) || 0;
            const baseAmount = qty * price;
            const itemTotal = baseAmount - discount + tax;

            if (item.type === 'SERVICE') {
                servicesFee += baseAmount;
            } else if (item.type === 'PRODUCT') {
                productsFee += baseAmount;
            }

            totalDiscount += discount;
            commercialTotal += itemTotal > 0 ? itemTotal : 0;
        });

        const elServices = document.getElementById('summaryServicesFee');
        const elProducts = document.getElementById('summaryProductsFee');
        const elDiscount = document.getElementById('summaryTotalDiscount');
        const elTotal = document.getElementById('summaryCommercialTotal');

        if (elServices) elServices.innerText = formatCurrency(servicesFee);
        if (elProducts) elProducts.innerText = formatCurrency(productsFee);
        if (elDiscount) elDiscount.innerText = (totalDiscount > 0 ? '- ' : '') + formatCurrency(totalDiscount);
        if (elTotal) elTotal.innerText = formatCurrency(commercialTotal);
    }

    const servicesCatalog = {
        'Accounting & Compliance Advisory': [
            { name: 'AFS Preparation', price: 2500, unit: 'lot', desc: 'Financial statements preparation & compliance review.' },
            { name: 'Accounting Services', price: 2500, unit: 'month', desc: 'Monthly accounting & bookkeeping compliance.' },
            { name: 'Audit Support / Coordination', price: 2500, unit: 'lot', desc: 'Coordination and liaison support for statutory audits.' },
            { name: 'BIR RDO Compliance Representative', price: 5000, unit: 'lot', desc: 'RDO representation and compliance resolution.' },
            { name: 'BIR Registration Assistance (L)', price: 100000, unit: 'lot', desc: 'Large taxpayer BIR registration assistance.' },
            { name: 'BIR Registration Assistance (M)', price: 15000, unit: 'lot', desc: 'Medium business BIR registration assistance.' },
            { name: 'BIR Registration Assistance (S)', price: 20000, unit: 'lot', desc: 'Small business BIR registration assistance.' },
            { name: 'BIR Registration- Update/ Change Information', price: 3000, unit: 'lot', desc: 'BIR 1905 updates and data changes.' },
            { name: 'Bir Open Case Resolution', price: 5000, unit: 'lot', desc: 'Clearing and resolving stop-filer / open cases.' },
            { name: 'Bookkeeping Services', price: 5000, unit: 'month', desc: 'Complete bookkeeping and general ledger maintenance.' },
            { name: 'Business Permit', price: 3000, unit: 'lot', desc: 'LGU Mayor\'s permit renewal and processing.' },
            { name: 'On-site Profit and Loss Review', price: 3000, unit: 'lot', desc: 'On-site analysis of profit & loss statements.' },
            { name: 'SAWT Preparation and eSubmission Validation', price: 0, unit: 'lot', desc: 'SAWT file preparation and BIR validation.' },
            { name: 'Tax Filing & Compliance (BIR)', price: 2500, unit: 'month', desc: 'Periodic tax returns preparation and eFPS/eBIR filing.' },
            { name: 'Transfer of BIR Registration from RDO 80 to RDO 81', price: 15000, unit: 'lot', desc: 'RDO transfer clearance and registration.' },
            { name: 'Transfer of Shares of Stock Assistance', price: 25000, unit: 'lot', desc: 'Stock transfer, CAR processing, and eCAR assistance.' }
        ],
        'Corporate & Regulatory Advisory': [
            { name: 'AMLC Registration and Compliance Officer Setup Assistance', price: 5000, unit: 'lot', desc: 'Anti-Money Laundering Council portal registration.' },
            { name: 'BIR Registration Assistance (MM)', price: 30000, unit: 'lot', desc: 'Corporate BIR registration package.' },
            { name: 'Bank Opening', price: 5000, unit: 'lot', desc: 'Corporate bank account opening coordination.' },
            { name: 'Business Registration (SEC / DTI / BIR)', price: 2500, unit: 'lot', desc: 'Full business entity registration processing.' },
            { name: 'Corporate Secretary Services (M)', price: 5000, unit: 'month', desc: 'Corporate secretarial filings and records management.' },
            { name: 'Corporation Formation & Registration Assistance (L)', price: 100000, unit: 'lot', desc: 'Large scale corporate incorporation.' },
            { name: 'Corporation Formation & Registration Assistance (M)', price: 15000, unit: 'lot', desc: 'Domestic corporation registration.' },
            { name: 'Corporation Formation & Registration Assistance (MM)', price: 30000, unit: 'lot', desc: 'Multi-branch/enterprise formation assistance.' },
            { name: 'Corporation Formation & Registration Assistance (S)', price: 20000, unit: 'lot', desc: 'Standard corporation incorporation.' },
            { name: 'Foreign Business Entry Support', price: 2500, unit: 'lot', desc: 'Foreign national & branch office setup.' },
            { name: 'LGU Compliance Representative', price: 5000, unit: 'lot', desc: 'City hall & barangay representation.' },
            { name: 'Loan Application Assistance', price: 2500, unit: 'lot', desc: 'Bank and institutional loan documentation.' },
            { name: 'New / Renewal LGU City/Municipality Business Registration Assistance — Complex', price: 25000, unit: 'lot', desc: 'Complex municipal licensing & zoning permits.' },
            { name: 'New LGU City/Municipality Business Registration Assistance — Non-Complex', price: 15000, unit: 'lot', desc: 'Standard LGU Mayor\'s permit processing.' },
            { name: 'Regulatory Compliance', price: 2500, unit: 'lot', desc: 'Statutory compliance tracking & advisory.' },
            { name: 'SEC Compliance Representative', price: 5000, unit: 'lot', desc: 'SEC GIS, AFS, and MC28 compliance filings.' }
        ],
        'Learning & Capability Development': [
            { name: 'Accounting & Compliance Training', price: 5000, unit: 'session', desc: 'Internal accounting staff training & compliance workshops.' },
            { name: 'Business & Strategy Training', price: 5000, unit: 'session', desc: 'Executive leadership and strategy facilitation.' },
            { name: 'Client Capability Development Programs', price: 10000, unit: 'program', desc: 'Customized organizational capability development.' },
            { name: 'Corporate Governance Workshops', price: 7500, unit: 'session', desc: 'Director and officer corporate governance seminars.' },
            { name: 'JKNC Academy Courses', price: 3500, unit: 'course', desc: 'Specialized business academy modular courses.' }
        ],
        'Business Strategy & Process Advisory': [
            { name: 'Digital Transformation', price: 2500, unit: 'lot', desc: 'Workflow digitization and ERP/software integration.' },
            { name: 'Domain Purchase and Setup Assistance', price: 5000, unit: 'lot', desc: 'Corporate email and domain infrastructure setup.' },
            { name: 'Financial Planning & Analysis', price: 2500, unit: 'lot', desc: 'Cashflow projection, financial modeling & budgets.' },
            { name: 'Organizational Structuring', price: 2500, unit: 'lot', desc: 'Departmental structuring, hierarchy & workflow mapping.' },
            { name: 'Process Improvement / SOP Development', price: 2500, unit: 'lot', desc: 'Standard Operating Procedures manual development.' }
        ],
        'Governance & Policy Advisory': [
            { name: 'Board Resolutions & Minutes', price: 2500, unit: 'lot', desc: 'Drafting of formal board and stockholders resolutions.' },
            { name: 'Corporate Officers Services', price: 2500, unit: 'month', desc: 'Corporate governance and compliance advisory.' },
            { name: 'Corporate Secretary Services', price: 2500, unit: 'month', desc: 'Maintenance of corporate stock and transfer books.' },
            { name: 'Middle Management Advisory Consultation', price: 11000, unit: 'lot', desc: 'Operational advisory and management consultation.' },
            { name: 'Policy Development (HR, Finance, Ops)', price: 2500, unit: 'lot', desc: 'Company policy manual formulation.' },
            { name: 'Risk & Internal Control Setup', price: 2500, unit: 'lot', desc: 'Internal control matrices and risk assessment.' }
        ],
        'People & Talent Solutions': [
            { name: 'Executive / Virtual Assistant Support', price: 2500, unit: 'month', desc: 'Dedicated executive assistance services.' },
            { name: 'HR Documentation & Contracts', price: 2500, unit: 'lot', desc: 'Employment contracts, 201 files, and employee handbooks.' },
            { name: 'HR Structuring & Organization Design', price: 2500, unit: 'lot', desc: 'Job descriptions and compensation grade structures.' },
            { name: 'KPI & Performance Management Systems', price: 25000, unit: 'lot', desc: 'KPI design, appraisal framework, and scorecard setup.' },
            { name: 'Managed Administrative Support Services', price: 35000, unit: 'month', desc: 'Full-service back-office administrative operations.' },
            { name: 'Recruitment & Hiring Support', price: 2500, unit: 'lot', desc: 'Candidate sourcing, screening & endorsement.' }
        ],
        'Strategic Situations Advisory': [
            { name: 'Business Restructuring Strategy', price: 2500, unit: 'lot', desc: 'Corporate turnaround and restructuring roadmap.' },
            { name: 'Corporate Deadlock Resolution', price: 2500, unit: 'lot', desc: 'Shareholder mediation and dispute settlement advisory.' },
            { name: 'Crisis Assessment & Stabilization', price: 2500, unit: 'lot', desc: 'Immediate crisis intervention and stabilization plan.' },
            { name: 'High-Risk / Complex Case Advisory', price: 2500, unit: 'lot', desc: 'Special advisory for critical regulatory and legal exposures.' },
            { name: 'Stakeholder Negotiation Support', price: 2500, unit: 'lot', desc: 'Commercial negotiation representation.' }
        ],
        'Service Add-Ons': [
            { name: 'Travel Credits — Cebu City, Mandaue City & Lapu-Lapu City', price: 1500, unit: 'credit', desc: 'Local travel credits allowance.' },
            { name: 'Travel Credits — Metro Cebu', price: 2500, unit: 'credit', desc: 'Metro Cebu regional travel allowance.' }
        ]
    };

    const productsCatalog = {
        'Compliance & Documentation Products': [
            { name: 'Archive Retrieval', price: 350, unit: 'document', desc: 'Physical/digital retrieval of archived corporate records.' },
            { name: 'Digital Archive Copy', price: 350, unit: 'file', desc: 'Certified high-resolution digital scanning and archiving.' },
            { name: 'Drafting of Certifications', price: 350, unit: 'document', desc: 'Official company certification drafting.' },
            { name: 'Drafting of Compliance Documents', price: 350, unit: 'document', desc: 'Government compliance documentation drafting.' },
            { name: 'Drafting of Memorandum (Internal / External)', price: 350, unit: 'document', desc: 'Official memorandum formulation.' },
            { name: 'Drafting of Responses to Letters / Notices', price: 350, unit: 'document', desc: 'Formal reply to regulatory agency inquiries.' },
            { name: 'Drafting of Demand Letters', price: 350, unit: 'document', desc: 'Legal and commercial collection demand letter.' },
            { name: 'Drafting of Emails (Formal / Business)', price: 350, unit: 'document', desc: 'Executive formal communication drafting.' },
            { name: 'Drafting of Letters', price: 350, unit: 'document', desc: 'Formal business correspondence.' },
            { name: 'Drafting of Notices', price: 350, unit: 'document', desc: 'Stockholder/board meeting notices.' },
            { name: 'Drafting of Policies & Procedures', price: 350, unit: 'document', desc: 'Standard operating policy documentation.' },
            { name: 'Drafting of Reports / Formal Documents', price: 350, unit: 'document', desc: 'Executive report drafting.' },
            { name: 'Drafting of Secretary\'s Certificates', price: 350, unit: 'document', desc: 'Certified Corporate Secretary\'s certificate.' },
            { name: 'Stock Certificate Printing', price: 300, unit: 'certificate', desc: 'Official corporate stock certificate printing.' },
            { name: 'Photocopy', price: 350, unit: 'set', desc: 'High-volume document duplication.' },
            { name: 'Printing', price: 350, unit: 'set', desc: 'Official document printing & collation.' },
            { name: 'Notarization - Complex Documents', price: 350, unit: 'document', desc: 'Notarial acknowledgment of complex agreements.' },
            { name: 'Notarization - Simple Documents', price: 350, unit: 'document', desc: 'Notarial jurat/acknowledgment of standard affidavits.' }
        ]
    };

    function getCatalogItem(name) {
        if (!name) return null;
        const lower = name.toLowerCase().trim();
        for (const items of Object.values(servicesCatalog)) {
            const found = items.find(i => i.name.toLowerCase().trim() === lower);
            if (found) return { ...found, type: 'SERVICE' };
        }
        for (const items of Object.values(productsCatalog)) {
            const found = items.find(i => i.name.toLowerCase().trim() === lower);
            if (found) return { ...found, type: 'PRODUCT' };
        }
        return null;
    }

    function populateLineItemDropdown(selectedName = '') {
        const select = document.getElementById('lineItemNameSelect');
        if (!select) return;

        let html = `<option value="">-- Choose Service / Product Item --</option>`;

        // If there are items availed during Deal creation
        if (dealAvailedItems && dealAvailedItems.length > 0) {
            html += `<optgroup label="── AVAILED IN DEAL ──">`;
            dealAvailedItems.forEach(item => {
                const catalogInfo = getCatalogItem(item.name);
                const itemPrice = (item.price && Number(item.price) > 0) ? Number(item.price) : (catalogInfo ? Number(catalogInfo.price) : 0);
                const itemUnit = (item.unit && item.unit !== 'lot') ? item.unit : (catalogInfo && catalogInfo.unit ? catalogInfo.unit : (item.unit || 'lot'));
                const itemDesc = item.description || dealScopeOfWork || (catalogInfo ? catalogInfo.desc : '');
                const isSelected = selectedName && (item.name.toLowerCase() === selectedName.toLowerCase());
                html += `<option value="${escapeHtml(item.name)}" data-price="${itemPrice}" data-unit="${escapeHtml(itemUnit)}" data-desc="${escapeHtml(itemDesc)}" ${isSelected ? 'selected' : ''}>
                    ${escapeHtml(item.name)} (₱${itemPrice.toLocaleString('en-US', { minimumFractionDigits: 2 })})
                </option>`;
            });
            html += `</optgroup>`;
        }

        // Master Services Catalog
        html += `<optgroup label="── SERVICES ──">`;
        for (const [category, items] of Object.entries(servicesCatalog)) {
            items.forEach(item => {
                const isSelected = selectedName && (item.name.toLowerCase() === selectedName.toLowerCase());
                html += `<option value="${escapeHtml(item.name)}" data-price="${item.price}" data-unit="${escapeHtml(item.unit || 'lot')}" data-desc="${escapeHtml(item.desc || '')}" ${isSelected ? 'selected' : ''}>
                    ${escapeHtml(item.name)} (₱${Number(item.price).toLocaleString('en-US', { minimumFractionDigits: 2 })})
                </option>`;
            });
        }
        html += `</optgroup>`;

        // Master Products Catalog
        html += `<optgroup label="── PRODUCTS ──">`;
        for (const [category, items] of Object.entries(productsCatalog)) {
            items.forEach(item => {
                const isSelected = selectedName && (item.name.toLowerCase() === selectedName.toLowerCase());
                html += `<option value="${escapeHtml(item.name)}" data-price="${item.price}" data-unit="${escapeHtml(item.unit || 'document')}" data-desc="${escapeHtml(item.desc || '')}" ${isSelected ? 'selected' : ''}>
                    ${escapeHtml(item.name)} (₱${Number(item.price).toLocaleString('en-US', { minimumFractionDigits: 2 })})
                </option>`;
            });
        }
        html += `</optgroup>`;

        // If editing/viewing an existing item not in current lists
        if (selectedName) {
            const catalogInfo = getCatalogItem(selectedName);
            let exists = !!catalogInfo || (dealAvailedItems || []).some(i => i.name.toLowerCase() === selectedName.toLowerCase());
            if (!exists) {
                html += `<optgroup label="── CURRENT ITEM ──">`;
                html += `<option value="${escapeHtml(selectedName)}" selected>${escapeHtml(selectedName)}</option>`;
                html += `</optgroup>`;
            }
        }

        select.innerHTML = html;
    }

    function handleLineItemSelectChange(val) {
        const nameInput = document.getElementById('lineItemName');
        if (!val) {
            if (nameInput) nameInput.value = '';
            document.getElementById('lineItemDescription').value = '';
            document.getElementById('lineItemUnitPrice').value = '0.00';
            document.getElementById('lineItemDiscount').value = '0.00';
            document.getElementById('lineItemTax').value = '0.00';
            return;
        }

        if (nameInput) nameInput.value = val;

        const catalogInfo = getCatalogItem(val);
        const availedMatch = (dealAvailedItems || []).find(i => i.name.toLowerCase() === val.toLowerCase());

        let itemType = 'SERVICE';
        if (availedMatch && availedMatch.type) {
            itemType = availedMatch.type.toUpperCase();
        } else if (catalogInfo && catalogInfo.type) {
            itemType = catalogInfo.type.toUpperCase();
        }

        let itemPrice = 0;
        let itemUnit = itemType === 'PRODUCT' ? 'document' : 'lot';
        let itemDesc = dealScopeOfWork || '';

        if (availedMatch && availedMatch.price && Number(availedMatch.price) > 0) {
            itemPrice = Number(availedMatch.price);
        } else if (catalogInfo && catalogInfo.price !== undefined) {
            itemPrice = Number(catalogInfo.price);
        }

        if (availedMatch && availedMatch.unit && availedMatch.unit !== 'lot') {
            itemUnit = availedMatch.unit;
        } else if (catalogInfo && catalogInfo.unit) {
            itemUnit = catalogInfo.unit;
        }

        if (dealScopeOfWork) {
            itemDesc = dealScopeOfWork;
        } else if (availedMatch && availedMatch.description) {
            itemDesc = availedMatch.description;
        } else if (catalogInfo && catalogInfo.desc) {
            itemDesc = catalogInfo.desc;
        }

        document.getElementById('lineItemType').value = itemType;
        document.getElementById('lineItemRoute').value = dealDefaultRoute;
        document.getElementById('lineItemBilling').value = dealDefaultBilling;
        document.getElementById('lineItemDiscount').value = '0.00';
        document.getElementById('lineItemTax').value = '0.00';
        document.getElementById('lineItemUnitPrice').value = parseFloat(itemPrice).toFixed(2);
        document.getElementById('lineItemUnit').value = itemUnit;
        document.getElementById('lineItemDescription').value = itemDesc;
    }

    function openAddLineItemModal() {
        document.getElementById('lineItemFormModalTitle').innerText = 'Add Line Item';
        document.getElementById('lineItemFormSubmitBtn').innerText = 'Save Line Item';
        document.getElementById('lineItemFormId').value = '';

        const select = document.getElementById('lineItemNameSelect');
        if (select) {
            select.style.pointerEvents = 'auto';
            select.style.backgroundColor = '#ffffff';
            select.style.color = '#0f172a';
            select.style.cursor = 'default';
            select.style.borderColor = '#cbd5e1';
        }

        document.getElementById('lineItemRoute').value = dealDefaultRoute;
        document.getElementById('lineItemName').value = '';
        document.getElementById('lineItemDescription').value = dealScopeOfWork || '';
        document.getElementById('lineItemQty').value = '1';
        document.getElementById('lineItemUnit').value = 'lot';
        document.getElementById('lineItemUnitPrice').value = '0.00';
        document.getElementById('lineItemDiscount').value = '0.00';
        document.getElementById('lineItemTax').value = '0.00';
        document.getElementById('lineItemBilling').value = dealDefaultBilling;

        if (dealAvailedItems && dealAvailedItems.length > 0) {
            const firstAvailed = dealAvailedItems[0];
            const initialType = (firstAvailed.type || 'SERVICE').toUpperCase();
            document.getElementById('lineItemType').value = initialType;
            populateLineItemDropdown(firstAvailed.name);
            document.getElementById('lineItemNameSelect').value = firstAvailed.name;
            handleLineItemSelectChange(firstAvailed.name);
        } else {
            document.getElementById('lineItemType').value = 'SERVICE';
            populateLineItemDropdown('');
        }

        document.getElementById('lineItemFormModal').style.display = 'flex';
        const qtyInput = document.getElementById('lineItemQty');
        if (qtyInput) {
            qtyInput.focus();
            qtyInput.select();
        }
    }

    function openEditLineItemModal(id) {
        const item = dealLineItems.find(i => i.id === id);
        if (!item) return;

        currentSelectedLineItemId = id;
        document.getElementById('lineItemFormModalTitle').innerText = 'Edit Line Item';
        document.getElementById('lineItemFormSubmitBtn').innerText = 'Update Line Item';
        document.getElementById('lineItemFormId').value = item.id;
        document.getElementById('lineItemType').value = item.type || 'SERVICE';
        document.getElementById('lineItemName').value = item.name || '';
        document.getElementById('lineItemDescription').value = item.description || '';
        document.getElementById('lineItemQty').value = item.qty || 1;
        document.getElementById('lineItemUnit').value = item.unit || 'lot';
        document.getElementById('lineItemUnitPrice').value = (parseFloat(item.unitPrice) || 0).toFixed(2);
        document.getElementById('lineItemDiscount').value = (parseFloat(item.discount) || 0).toFixed(2);
        document.getElementById('lineItemTax').value = (parseFloat(item.tax) || 0).toFixed(2);
        document.getElementById('lineItemBilling').value = item.billing || dealDefaultBilling;
        document.getElementById('lineItemRoute').value = item.route || dealDefaultRoute;

        // In edit mode, lock Item selection because it is already agreed
        const select = document.getElementById('lineItemNameSelect');
        if (select) {
            select.style.pointerEvents = 'none';
            select.style.backgroundColor = '#f8fafc';
            select.style.color = '#64748b';
            select.style.cursor = 'not-allowed';
            select.style.borderColor = '#e2e8f0';
        }

        populateLineItemDropdown(item.name || '');

        document.getElementById('lineItemFormModal').style.display = 'flex';
        const qtyInput = document.getElementById('lineItemQty');
        if (qtyInput) {
            qtyInput.focus();
            qtyInput.select();
        }
    }

    function closeLineItemModal() {
        document.getElementById('lineItemFormModal').style.display = 'none';
        currentSelectedLineItemId = null;
    }

    async function handleSaveLineItem(event) {
        event.preventDefault();
        const id = document.getElementById('lineItemFormId').value;
        const type = document.getElementById('lineItemType').value;
        let name = (document.getElementById('lineItemName').value || document.getElementById('lineItemNameSelect').value || '').trim();
        const description = document.getElementById('lineItemDescription').value.trim();
        const qty = parseFloat(document.getElementById('lineItemQty').value) || 1;
        const unit = document.getElementById('lineItemUnit').value.trim() || 'lot';
        const unitPrice = parseFloat(document.getElementById('lineItemUnitPrice').value) || 0;
        const discount = parseFloat(document.getElementById('lineItemDiscount').value) || 0;
        const tax = parseFloat(document.getElementById('lineItemTax').value) || 0;
        const billing = document.getElementById('lineItemBilling').value.trim() || dealDefaultBilling;
        const route = document.getElementById('lineItemRoute').value || dealDefaultRoute;

        if (!name) {
            alert('Please select an Availed Service or Product.');
            return;
        }

        const submitBtn = document.getElementById('lineItemFormSubmitBtn');
        const originalBtnText = submitBtn ? submitBtn.innerText : 'Save Line Item';
        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.innerText = 'Saving...';
        }

        try {
            const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || (document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || "");
            const response = await fetch((window.DEAL_BOOTSTRAP?.routes?.lineItemsStore ?? ""), {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': token
                },
                body: JSON.stringify({
                    id: id || null,
                    type: type,
                    name: name,
                    description: description,
                    qty: qty,
                    unit: unit,
                    unit_price: unitPrice,
                    discount: discount,
                    tax: tax,
                    billing: billing,
                    route: route
                })
            });

            const data = await response.json();
            if (data.success && data.items) {
                dealLineItems = data.items.map((it, idx) => ({
                    id: it.id || (idx + 1),
                    type: it.type || 'SERVICE',
                    name: it.name || '',
                    description: it.description || '',
                    qty: parseFloat(it.qty) || 1,
                    unit: it.unit || 'lot',
                    unitPrice: parseFloat(it.unitPrice ?? it.price ?? 0),
                    discount: parseFloat(it.discount ?? 0),
                    tax: parseFloat(it.tax ?? 0),
                    billing: it.billing || dealDefaultBilling,
                    route: it.route || dealDefaultRoute
                }));
            } else {
                if (id) {
                    const index = dealLineItems.findIndex(i => i.id == id);
                    if (index !== -1) {
                        dealLineItems[index] = {
                            ...dealLineItems[index],
                            type,
                            name,
                            description,
                            qty,
                            unit,
                            unitPrice,
                            discount,
                            tax,
                            billing,
                            route
                        };
                    }
                } else {
                    const newItem = {
                        id: Date.now(),
                        type,
                        name,
                        description,
                        qty,
                        unit,
                        unitPrice,
                        discount,
                        tax,
                        billing,
                        route
                    };
                    dealLineItems.push(newItem);
                }
            }

            closeLineItemModal();
            renderDealLineItems();
            if (typeof updateProposalLiveTotals === 'function') updateProposalLiveTotals();
            if (typeof loadHistories === 'function') loadHistories();
        } catch (err) {
            console.error('Error saving line item:', err);
            alert('Failed to save line item. Please try again.');
        } finally {
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.innerText = originalBtnText;
            }
        }
    }

    function openDeleteLineItemModal(id) {
        currentSelectedLineItemId = id;
        document.getElementById('lineItemDeleteModal').style.display = 'flex';
    }

    function closeDeleteLineItemModal() {
        document.getElementById('lineItemDeleteModal').style.display = 'none';
        currentSelectedLineItemId = null;
    }

    async function handleConfirmDeleteLineItem() {
        if (!currentSelectedLineItemId) return;

        try {
            const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || (document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || "");
            const deleteUrl = ((window.DEAL_BOOTSTRAP?.routes?.lineItemsStore ?? ("/deals/" + window.DEAL_BOOTSTRAP?.dealId + "/line-items")) + "/") + currentSelectedLineItemId;
            const response = await fetch(deleteUrl, {
                method: 'DELETE',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': token
                }
            });
            const data = await response.json();
            if (data.success && data.items) {
                dealLineItems = data.items.map((it, idx) => ({
                    id: it.id || (idx + 1),
                    type: it.type || 'SERVICE',
                    name: it.name || '',
                    description: it.description || '',
                    qty: parseFloat(it.qty) || 1,
                    unit: it.unit || 'lot',
                    unitPrice: parseFloat(it.unitPrice ?? it.price ?? 0),
                    discount: parseFloat(it.discount ?? 0),
                    tax: parseFloat(it.tax ?? 0),
                    billing: it.billing || dealDefaultBilling,
                    route: it.route || dealDefaultRoute
                }));
            } else {
                dealLineItems = dealLineItems.filter(i => i.id !== currentSelectedLineItemId);
            }
        } catch (err) {
            console.error('Error deleting line item:', err);
            dealLineItems = dealLineItems.filter(i => i.id !== currentSelectedLineItemId);
        }

        closeDeleteLineItemModal();
        renderDealLineItems();
        if (typeof updateProposalLiveTotals === 'function') updateProposalLiveTotals();
    }

    /* =========================================================
       PROPOSAL WORKSPACE LOGIC & LIVE PREVIEW SYNC
       ========================================================= */
    var savedProposalSelection = null;

    function rememberProposalSelection() {
        var selection = window.getSelection();
        if (!selection || !selection.rangeCount) return;
        var range = selection.getRangeAt(0);
        var container = range.commonAncestorContainer;
        var parentElement = container.nodeType === 1 ? container : container.parentElement;
        if (!parentElement) return;
        var editable = parentElement.closest('[contenteditable="true"]');
        if (editable) {
            savedProposalSelection = range.cloneRange();
        }
    }

    document.addEventListener('selectionchange', rememberProposalSelection);

    function restoreProposalSelection() {
        if (!savedProposalSelection) return;
        var selection = window.getSelection();
        selection.removeAllRanges();
        selection.addRange(savedProposalSelection);
    }

    function formatProposalPreview(command, value = null) {
        if (typeof window.__formatProposalPreviewImpl === 'function') {
            return window.__formatProposalPreviewImpl(command, value);
        }
        restoreProposalSelection();
        document.execCommand(command, false, value);
        rememberProposalSelection();
    }

    function syncProposalPreviewField(sourceId, value) {
        document.querySelectorAll('[data-source="' + sourceId + '"]').forEach(function(element) {
            if (document.activeElement !== element) {
                element.textContent = value || '';
            }
        });
    }

    document.addEventListener('DOMContentLoaded', function() {
        // Wire up bidirectional sync for contenteditable preview elements
        document.querySelectorAll('.proposal-preview-scroll [data-source]').forEach(function(element) {
            element.addEventListener('input', function() {
                var field = document.getElementById(element.dataset.source);
                if (field) {
                    field.value = element.innerText;
                }
            });
        });

        // Wire up input sync to preview
        ['proposal_subject', 'proposal_introduction', 'proposal_scope', 'proposal_terms'].forEach(function(id) {
            var field = document.getElementById(id);
            if (field) {
                field.addEventListener('input', function() {
                    syncProposalPreviewField(id, field.value);
                });
            }
        });

        // Wire up discount and tax dynamic recalculations
        var discountInput = document.getElementById('proposal_discount');
        var taxInput = document.getElementById('proposal_tax');
        if (discountInput) discountInput.addEventListener('input', updateProposalLiveTotals);
        if (taxInput) taxInput.addEventListener('input', updateProposalLiveTotals);

        // Restore active version badge & decision status if stored
        try {
            const savedRevs = JSON.parse(localStorage.getItem('deal_${window.DEAL_BOOTSTRAP?.dealId || 0}_revisions') || '[]');
            if (savedRevs.length > 0) {
                const latestRev = savedRevs[savedRevs.length - 1];
                if (latestRev && latestRev.revision) {
                    const revBadge = document.getElementById('pwiActiveVersionBadge');
                    const revTitle = document.getElementById('pwiLedgerTitleVersion');
                    if (revBadge) revBadge.innerText = latestRev.revision;
                    if (revTitle) revTitle.innerText = 'PROPOSAL ITEM DECISION LEDGER (' + latestRev.revision + ')';
                }
            }
            const savedDecision = JSON.parse(localStorage.getItem('deal_${window.DEAL_BOOTSTRAP?.dealId || 0}_proposal_decision') || 'null');
            if (savedDecision && savedDecision.status) {
                const statusBadge = document.getElementById('pwiStatusBadge');
                if (statusBadge) statusBadge.innerText = savedDecision.status;
            }
        } catch (e) {}

        renderUniversalClientActions();
    });

    function updateProposalLiveTotals() {
        var services = (window.DEAL_BOOTSTRAP?.proposalTotals?.services ?? 0);
        var products = (window.DEAL_BOOTSTRAP?.proposalTotals?.products ?? 0);
        var discount = parseFloat(document.getElementById('proposal_discount')?.value) || 0;
        var tax = parseFloat(document.getElementById('proposal_tax')?.value) || 0;

        var subtotal = (services + products) - discount;
        var total = subtotal + tax;
        var downpayment = total * 0.5;
        var balance = total * 0.5;

        if (document.getElementById('displayProposalDiscount')) document.getElementById('displayProposalDiscount').innerText = formatCurrency(discount);
        if (document.getElementById('displayProposalTax')) document.getElementById('displayProposalTax').innerText = formatCurrency(tax);
        if (document.getElementById('displayProposalSubtotal')) document.getElementById('displayProposalSubtotal').innerText = formatCurrency(subtotal > 0 ? subtotal : 0);
        if (document.getElementById('displayProposalTotal')) document.getElementById('displayProposalTotal').innerText = formatCurrency(total > 0 ? total : 0);

        if (document.getElementById('previewSummaryDiscount')) document.getElementById('previewSummaryDiscount').innerText = formatCurrency(discount);
        if (document.getElementById('previewSummaryTotal')) document.getElementById('previewSummaryTotal').innerText = formatCurrency(total > 0 ? total : 0);

        if (document.getElementById('docFeeDiscount')) document.getElementById('docFeeDiscount').innerText = Number(discount).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        if (document.getElementById('docFeeSubtotal')) document.getElementById('docFeeSubtotal').innerText = Number(subtotal > 0 ? subtotal : 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        if (document.getElementById('docFeeTax')) document.getElementById('docFeeTax').innerText = Number(tax).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        if (document.getElementById('docFeeTotal')) document.getElementById('docFeeTotal').innerText = Number(total > 0 ? total : 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        if (document.getElementById('docFeeDownpayment')) document.getElementById('docFeeDownpayment').innerText = Number(downpayment > 0 ? downpayment : 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        if (document.getElementById('docFeeBalance')) document.getElementById('docFeeBalance').innerText = Number(balance > 0 ? balance : 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        if (document.getElementById('pwiSnapshotTotal')) {
            document.getElementById('pwiSnapshotTotal').innerText = formatCurrency(total > 0 ? total : 0);
        }
    }

    function refreshProposalPreview() {
        ['proposal_subject', 'proposal_introduction', 'proposal_scope', 'proposal_terms'].forEach(function(id) {
            var field = document.getElementById(id);
            if (field) {
                syncProposalPreviewField(id, field.value);
            }
        });
        updateProposalLiveTotals();
    }

    function saveProposalTemplateNotice() {
        var modal = document.getElementById('proposalTemplateModal');
        if (modal) modal.style.display = 'flex';
    }

    function closeProposalTemplateModal() {
        var modal = document.getElementById('proposalTemplateModal');
        if (modal) modal.style.display = 'none';
    }

    function confirmSaveProposalTemplate() {
        var template = {};
        document.querySelectorAll('.proposal-preview-scroll [data-source]').forEach(function(element) {
            template[element.dataset.source] = element.innerHTML;
        });
        localStorage.setItem('ordo-global-proposal-template', JSON.stringify(template));
        closeProposalTemplateModal();
        alert('Global proposal template saved successfully.');
    }

    function openSendProposalModal() {
        var modal = document.getElementById('proposalSendModal');
        if (modal) modal.style.display = 'flex';
    }

    function closeSendProposalModal() {
        var modal = document.getElementById('proposalSendModal');
        if (modal) modal.style.display = 'none';
    }

    function printProposalPDF() {
        const portalModal = document.getElementById('proposalClientPortalModal');
        const portalTrack = document.getElementById('crpPagesTrack');
        let contentHtml = '';

        if (portalModal && portalModal.style.display !== 'none' && portalTrack && portalTrack.innerHTML.trim()) {
            contentHtml = portalTrack.innerHTML;
        } else {
            const previewWorkspace = document.querySelector('.proposal-preview-scroll .preview-workspace');
            if (previewWorkspace && previewWorkspace.innerHTML.trim()) {
                contentHtml = previewWorkspace.innerHTML;
            } else {
                const snapshot = typeof getSentProposalSnapshot === 'function' ? getSentProposalSnapshot() : null;
                if (snapshot && snapshot.htmlPages) {
                    contentHtml = snapshot.htmlPages;
                }
            }
        }

        if (!contentHtml) {
            window.print();
            return;
        }

        const printFrame = document.createElement('iframe');
        printFrame.style.position = 'fixed';
        printFrame.style.right = '0';
        printFrame.style.bottom = '0';
        printFrame.style.width = '0';
        printFrame.style.height = '0';
        printFrame.style.border = 'none';
        document.body.appendChild(printFrame);

        const doc = printFrame.contentWindow.document;
        const pageStyles = Array.from(document.querySelectorAll('style')).map(s => s.innerHTML).join('\n');

        doc.open();
        doc.write('<' + '!DOCTYPE html><html><head><meta charset="utf-8"><' + 'title>' + (typeof DEAL_CODE !== 'undefined' ? DEAL_CODE : 'Proposal') + ' — Proposal Document<' + '/title><' + 'style>/* @page */ { size: A4; margin: 0; } * { box-sizing: border-box; } body { background: #fff; margin: 0; padding: 0; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif; } .proposal-paper { width: 100% !important; min-height: 100vh !important; page-break-after: always !important; padding: 36px 44px !important; background: #fff !important; box-shadow: none !important; margin: 0 auto !important; border: none !important; } .proposal-paper:last-child { page-break-after: avoid !important; } ' + pageStyles + '<' + '/style><' + '/head><body><div class="preview-workspace">' + contentHtml + '</div><' + '/body><' + '/html>');
        doc.close();

        setTimeout(() => {
            try {
                printFrame.contentWindow.focus();
                printFrame.contentWindow.print();
            } catch(e) {
                window.print();
            }
            setTimeout(() => {
                try { document.body.removeChild(printFrame); } catch(e) {}
            }, 2000);
        }, 400);
    }

    /* =========================================================
       PROPOSAL VERSIONING & INLINE CLIENT REVIEW PORTAL ENGINE
       ========================================================= */
    const DEAL_ID = (window.DEAL_BOOTSTRAP?.dealId ?? 0);
    const DEAL_CODE = (window.DEAL_BOOTSTRAP?.dealCode ?? "DEAL");
    const CLIENT_NAME = (window.DEAL_BOOTSTRAP?.clientName ?? "Client Representative");
    const CLIENT_EMAIL = (window.DEAL_BOOTSTRAP?.clientEmail ?? "");

    let activeInternalVersionCode = 'V1';

    function captureCurrentProposalFormData() {
        return {
            subject: document.getElementById('proposal_subject') ? document.getElementById('proposal_subject').value : '',
            introduction: document.getElementById('proposal_introduction') ? document.getElementById('proposal_introduction').value : '',
            scope: document.getElementById('proposal_scope') ? document.getElementById('proposal_scope').value : '',
            terms: document.getElementById('proposal_terms') ? document.getElementById('proposal_terms').value : '',
            discount: parseFloat(document.getElementById('proposal_discount') ? document.getElementById('proposal_discount').value : 0) || 0,
            tax: parseFloat(document.getElementById('proposal_tax') ? document.getElementById('proposal_tax').value : 0) || 0
        };
    }

    function captureCurrentProposalPagesHtml() {
        const previewWorkspace = document.querySelector('.proposal-preview-scroll .preview-workspace');
        return previewWorkspace ? previewWorkspace.innerHTML : '';
    }

    function getComputedProposalTotals() {
        return {
            services: parseFloat('(window.DEAL_BOOTSTRAP?.proposalTotals?.services ?? 0)') || 0,
            products: parseFloat('(window.DEAL_BOOTSTRAP?.proposalTotals?.products ?? 0)') || 0,
            discount: parseFloat(document.getElementById('proposal_discount') ? document.getElementById('proposal_discount').value : '(window.DEAL_BOOTSTRAP?.proposalTotals?.discount ?? 0)') || 0,
            tax: parseFloat(document.getElementById('proposal_tax') ? document.getElementById('proposal_tax').value : '(window.DEAL_BOOTSTRAP?.proposalTotals?.tax ?? 0)') || 0,
            subtotal: parseFloat((document.getElementById('displayProposalSubtotal') ? document.getElementById('displayProposalSubtotal').innerText : '0').replace(/[^0-9.]/g, '')) || 0,
            total: parseFloat((document.getElementById('displayProposalTotal') ? document.getElementById('displayProposalTotal').innerText : '0').replace(/[^0-9.]/g, '')) || 0
        };
    }

    function getProposalVersions() {
        let raw = [];
        try {
            raw = JSON.parse(localStorage.getItem('deal_' + DEAL_ID + '_proposal_versions') || 'null');
        } catch (e) {
            raw = null;
        }

        const systemDecision = (window.DEAL_BOOTSTRAP?.proposalDecision ?? "Draft");

        let versionsMap = new Map();
        if (Array.isArray(raw) && raw.length > 0) {
            raw.forEach(v => {
                if (v && (v.version || v.versionNumber)) {
                    const num = parseInt((v.version || '').toString().replace(/\D/g, ''), 10) || v.versionNumber || 1;
                    v.versionNumber = num;
                    v.version = 'V' + num;
                    versionsMap.set(v.version, v);
                }
            });
        }

        // Always guarantee V1 exists as the baseline starting version
        if (!versionsMap.has('V1')) {
            versionsMap.set('V1', {
                version: 'V1',
                versionNumber: 1,
                status: (versionsMap.size === 0 ? systemDecision : 'Draft'),
                isCurrent: versionsMap.size === 0,
                createdAt: '""',
                reason: 'Initial Proposal Base',
                requester: 'Internal Team',
                formData: captureCurrentProposalFormData(),
                htmlPages: captureCurrentProposalPagesHtml(),
                totals: getComputedProposalTotals()
            });
        }

        // Re-index contiguously starting from 1 (V1, V2, V3...)
        let versionsList = Array.from(versionsMap.values()).sort((a, b) => a.versionNumber - b.versionNumber);
        versionsList = versionsList.map((v, idx) => {
            const seqNum = idx + 1;
            return {
                ...v,
                versionNumber: seqNum,
                version: 'V' + seqNum
            };
        });

        const highestNum = versionsList.length;
        const maxVersionCode = 'V' + highestNum;

        // Apply system status to the latest current version if present
        versionsList.forEach(v => {
            if (v.version === maxVersionCode) {
                v.isCurrent = true;
                if (systemDecision && systemDecision !== 'Draft') {
                    v.status = systemDecision;
                }
            } else {
                v.isCurrent = false;
            }
        });

        saveProposalVersions(versionsList);
        return versionsList;
    }

    function saveProposalVersions(versions) {
        localStorage.setItem('deal_' + DEAL_ID + '_proposal_versions', JSON.stringify(versions));
    }

    function renderInternalVersionPills() {
        const containers = [
            document.getElementById('proposalVersionPillsWrap'),
            document.getElementById('modalProposalVersionPillsWrap'),
            document.getElementById('internalVersionPillsTrack')
        ].filter(Boolean);

        if (!containers.length) return;

        const versions = getProposalVersions();
        const sorted = [...versions].sort((a, b) => b.versionNumber - a.versionNumber);
        const maxVersionObj = sorted[0] || { version: 'V1', versionNumber: 1 };
        const maxVersionCode = maxVersionObj.version;
        const nextVersionCode = 'V' + ((maxVersionObj.versionNumber || 1) + 1);

        if (!versions.some(v => v.version === activeInternalVersionCode)) {
            activeInternalVersionCode = maxVersionCode;
        }

        const isViewingHistorical = activeInternalVersionCode !== maxVersionCode;

        // Update all dynamic "+ New Revision (V{next})" buttons
        document.querySelectorAll('.pvt-btn-new-revision').forEach(btn => {
            btn.innerText = `+ New Revision (${nextVersionCode})`;
        });

        const pillsHtml = sorted.map(v => {
            const isSelected = v.version === activeInternalVersionCode;
            const isLatest = v.version === maxVersionCode;
            const statusText = v.status || 'Draft';

            // Only the latest version shows CURRENT tag
            let tagHtml = '';
            if (isLatest) {
                tagHtml = ` <span class="pvt-pill-tag-current">CURRENT</span>`;
            }

            return `
                <button type="button" class="pvt-pill ${isSelected ? 'selected' : ''}" onclick="selectInternalProposalVersion('${v.version}')" title="View snapshot of ${v.version}">
                    <span class="pvt-pill-name">${v.version}</span>
                    <span class="pvt-pill-status">(${statusText})</span>${tagHtml}
                </button>
            `;
        }).join('');

        containers.forEach(c => {
            c.innerHTML = pillsHtml;
        });

        // Show/hide historical notice
        document.querySelectorAll('#pvtHistoricalNotice, #modalPvtHistoricalNotice').forEach(el => {
            if (isViewingHistorical) {
                el.style.display = 'flex';
                const textSpan = el.querySelector('span');
                if (textSpan) {
                    textSpan.innerText = `Historical snapshot (${activeInternalVersionCode}) — create a new revision to make changes.`;
                }
            } else {
                el.style.display = 'none';
            }
        });

        // Toggle form readonly state when viewing historical vs current
        const form = document.getElementById('deal-proposal-form');
        if (form) {
            form.querySelectorAll('input:not([data-always-readonly]), textarea').forEach(input => {
                if (input.name === '_token') return;
                if (isViewingHistorical) {
                    input.setAttribute('readonly', 'true');
                    input.style.backgroundColor = '#f8fafc';
                } else {
                    if (input.id === 'proposal_subject' || input.id === 'proposal_introduction' || input.id === 'proposal_scope' || input.id === 'proposal_terms' || input.id === 'proposal_discount' || input.id === 'proposal_tax') {
                        input.removeAttribute('readonly');
                        input.style.backgroundColor = '';
                    }
                }
            });
            const saveBtns = form.querySelectorAll('button[type="submit"], .pwm-btn-save');
            saveBtns.forEach(btn => {
                if (isViewingHistorical) {
                    btn.setAttribute('disabled', 'true');
                    btn.style.opacity = '0.5';
                    btn.style.cursor = 'not-allowed';
                } else {
                    btn.removeAttribute('disabled');
                    btn.style.opacity = '';
                    btn.style.cursor = '';
                }
            });
        }

        updateInternalHeaderAndLedger();
        updateAuditTraceabilityUI();
    }

    function selectInternalProposalVersion(versionCode) {
        const versions = getProposalVersions();
        const maxVer = [...versions].sort((a,b) => b.versionNumber - a.versionNumber)[0];
        const maxVersionCode = maxVer ? maxVer.version : 'V1';

        // If switching from an editable draft, capture latest changes before switching
        const currentVerObj = versions.find(v => v.version === activeInternalVersionCode);
        if (currentVerObj && activeInternalVersionCode === maxVersionCode) {
            currentVerObj.formData = captureCurrentProposalFormData();
            currentVerObj.htmlPages = captureCurrentProposalPagesHtml();
            currentVerObj.totals = getComputedProposalTotals();
            saveProposalVersions(versions);
        }

        const targetVer = versions.find(v => v.version === versionCode);
        if (!targetVer) return;

        activeInternalVersionCode = versionCode;

        if (targetVer.formData) {
            if (document.getElementById('proposal_subject')) document.getElementById('proposal_subject').value = targetVer.formData.subject || '';
            if (document.getElementById('proposal_introduction')) document.getElementById('proposal_introduction').value = targetVer.formData.introduction || '';
            if (document.getElementById('proposal_scope')) document.getElementById('proposal_scope').value = targetVer.formData.scope || '';
            if (document.getElementById('proposal_terms')) document.getElementById('proposal_terms').value = targetVer.formData.terms || '';
            if (document.getElementById('proposal_discount')) document.getElementById('proposal_discount').value = targetVer.formData.discount || 0;
            if (document.getElementById('proposal_tax')) document.getElementById('proposal_tax').value = targetVer.formData.tax || 0;

            syncProposalPreviewField('proposal_subject', targetVer.formData.subject || '');
            syncProposalPreviewField('proposal_introduction', targetVer.formData.introduction || '');
            syncProposalPreviewField('proposal_scope', targetVer.formData.scope || '');
            syncProposalPreviewField('proposal_terms', targetVer.formData.terms || '');
            updateProposalLiveTotals();
        }

        if (targetVer.htmlPages) {
            const previewWorkspace = document.querySelector('.proposal-preview-scroll .preview-workspace');
            if (previewWorkspace) {
                previewWorkspace.innerHTML = targetVer.htmlPages;
            }
        } else if (typeof refreshProposalPreview === 'function') {
            refreshProposalPreview();
        }

        renderInternalVersionPills();
    }

    function updateInternalHeaderAndLedger() {
        const versions = getProposalVersions();
        const ver = versions.find(v => v.version === activeInternalVersionCode) || versions[0];
        const maxVer = [...versions].sort((a,b) => b.versionNumber - a.versionNumber)[0];
        const isCurrent = ver.version === (maxVer ? maxVer.version : 'V1');

        const activeVersionLabel = document.getElementById('internalProposalActiveVersion');
        const headerStatus = document.getElementById('internalProposalHeaderStatus');
        const pwiBadge = document.getElementById('pwiActiveVersionBadge');
        const pwiStatus = document.getElementById('pwiStatusBadge');
        const ledgerTitle = document.getElementById('pwiLedgerTitleVersion');
        const modalTitle = document.getElementById('pwmModalTitle') || document.querySelector('.pwm-title');

        if (activeVersionLabel) activeVersionLabel.innerText = ver.version;
        if (headerStatus) headerStatus.innerText = ver.status || (isCurrent ? 'Draft' : 'Historical Snapshot');
        if (pwiBadge) pwiBadge.innerText = ver.version;
        if (pwiStatus) pwiStatus.innerText = ver.status || (isCurrent ? 'Draft' : 'Historical Snapshot');
        if (ledgerTitle) ledgerTitle.innerText = 'PROPOSAL ITEM DECISION LEDGER (' + ver.version + ')';
        if (modalTitle) modalTitle.innerText = DEAL_CODE + ' — ' + CLIENT_NAME;
    }

    function updateAuditTraceabilityUI() {
        const sentData = getSentProposalSnapshot();
        const sentVersionEl = document.getElementById('pwiSentVersionText');
        const sentAtEl = document.getElementById('pwiSentAtText');

        if (sentVersionEl) {
            sentVersionEl.innerText = sentData ? sentData.version : 'Not Sent Yet';
        }
        if (sentAtEl) {
            sentAtEl.innerText = sentData ? (sentData.sentAt || '—') : '—';
        }
    }

    /* UNLIMITED PROPOSAL REVISIONS - DIRECT INSTANT CREATION */
    function openAddRevisionModal() {
        const versions = getProposalVersions();
        const maxNum = Math.max(...versions.map(v => v.versionNumber || 1), 1);
        const nextNum = maxNum + 1;
        const nextVerCode = 'V' + nextNum;

        // Save current active version state first
        const currentActive = versions.find(v => v.version === activeInternalVersionCode);
        if (currentActive) {
            currentActive.formData = captureCurrentProposalFormData();
            currentActive.htmlPages = captureCurrentProposalPagesHtml();
            currentActive.totals = getComputedProposalTotals();
        }

        // Mark all older versions as not current
        versions.forEach(v => v.isCurrent = false);

        const dateVal = new Date().toISOString().split('T')[0];
        const requester = (window.DEAL_BOOTSTRAP?.clientName ?? "Client Representative");
        const reason = 'New proposal revision (' + nextVerCode + ')';

        const formData = captureCurrentProposalFormData();
        const htmlPages = captureCurrentProposalPagesHtml();
        const totals = getComputedProposalTotals();

        const newVersion = {
            version: nextVerCode,
            versionNumber: nextNum,
            status: 'Draft',
            isCurrent: true,
            createdAt: dateVal,
            reason: reason,
            requester: requester,
            formData: formData,
            htmlPages: htmlPages,
            totals: totals
        };

        versions.push(newVersion);
        saveProposalVersions(versions);

        activeInternalVersionCode = nextVerCode;
        selectInternalProposalVersion(nextVerCode);
    }

    function attachProposalVersionAutoSync() {
        const fieldIds = ['proposal_subject', 'proposal_introduction', 'proposal_scope', 'proposal_terms', 'proposal_discount', 'proposal_tax'];
        fieldIds.forEach(id => {
            const el = document.getElementById(id);
            if (el && !el.dataset.versionSyncAttached) {
                el.dataset.versionSyncAttached = 'true';
                el.addEventListener('input', () => {
                    const versions = getProposalVersions();
                    const maxVer = [...versions].sort((a,b) => b.versionNumber - a.versionNumber)[0];
                    if (activeInternalVersionCode === (maxVer ? maxVer.version : 'V1')) {
                        const activeVer = versions.find(v => v.version === activeInternalVersionCode);
                        if (activeVer) {
                            activeVer.formData = captureCurrentProposalFormData();
                            activeVer.totals = getComputedProposalTotals();
                            saveProposalVersions(versions);
                        }
                    }
                });
            }
        });
    }

    /* SENT PROPOSAL SNAPSHOT ENGINE */
    const serverSentSnapshot = (window.DEAL_BOOTSTRAP?.serverSentSnapshotData ?? null);

    function getSentProposalSnapshot() {
        try {
            const local = JSON.parse(localStorage.getItem('deal_' + DEAL_ID + '_sent_proposal') || 'null');
            if (local && local.sentAt) return local;
            return serverSentSnapshot;
        } catch (e) {
            return serverSentSnapshot;
        }
    }

    function recordSentProposalSnapshot(versionCode, recipientEmail) {
        const versions = getProposalVersions();
        const ver = versions.find(v => v.version === versionCode) || versions[0];
        const now = new Date();
        const sentAtStr = now.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' }) + ' ' + now.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });

        const sentSnapshot = {
            version: ver.version,
            versionNumber: ver.versionNumber,
            sentAt: sentAtStr,
            recipientEmail: recipientEmail || CLIENT_EMAIL,
            htmlPages: ver.htmlPages || captureCurrentProposalPagesHtml(),
            formData: ver.formData || captureCurrentProposalFormData(),
            isApproved: false,
            approvedAt: null,
            approvedBy: null
        };

        localStorage.setItem('deal_' + DEAL_ID + '_sent_proposal', JSON.stringify(sentSnapshot));
        updateAuditTraceabilityUI();
        return sentSnapshot;
    }

    function handleSendProposalSubmit(event, form) {
        const emailInput = form ? form.querySelector('input[name="recipient_email"]') : null;
        const recipient = emailInput ? emailInput.value : CLIENT_EMAIL;
        recordSentProposalSnapshot(activeInternalVersionCode, recipient);
    }

    /* CLIENT REVIEW PORTAL — INLINE OVERLAY REVIEWER */
    let crpCurrentPageIndex = 0;
    let crpTotalPages = 1;

    function openClientReviewPortalModal() {
        const modal = document.getElementById('proposalClientPortalModal');
        if (!modal) return;

        // Use the snapshot intentionally sent to client; if none sent yet, fallback to current live preview
        let snapshot = getSentProposalSnapshot();
        let pagesHtml = snapshot && snapshot.htmlPages ? snapshot.htmlPages : captureCurrentProposalPagesHtml();

        const track = document.getElementById('crpPagesTrack');
        if (track) {
            track.innerHTML = pagesHtml;
            const pages = track.querySelectorAll('.proposal-paper');
            crpTotalPages = pages.length || 1;
            pages.forEach((page, i) => {
                page.setAttribute('data-page-index', i);
                page.id = 'crp_page_' + i;
            });
        }

        crpRenderComments();
        crpUpdateApprovalButtonState();

        crpCurrentPageIndex = 0;
        crpUpdatePageUI(0);
        if (track) track.scrollTop = 0;

        modal.style.display = 'flex';

        if (track) {
            track.onscroll = function() {
                const pages = track.querySelectorAll('.proposal-paper');
                if (!pages.length) return;
                const scrollTop = track.scrollTop;
                let closestIndex = 0;
                let minDiff = Infinity;
                pages.forEach((page, i) => {
                    const diff = Math.abs(page.offsetTop - track.offsetTop - scrollTop);
                    if (diff < minDiff) {
                        minDiff = diff;
                        closestIndex = i;
                    }
                });
                if (closestIndex !== crpCurrentPageIndex) {
                    crpCurrentPageIndex = closestIndex;
                    crpUpdatePageUI(closestIndex);
                }
            };
        }
    }

    function closeClientReviewPortalModal() {
        const modal = document.getElementById('proposalClientPortalModal');
        if (modal) modal.style.display = 'none';
    }

    function crpUpdatePageUI(index) {
        const indicator = document.getElementById('crpPageIndicator');
        if (indicator) indicator.innerText = `Page ${index + 1} of ${crpTotalPages}`;

        const prevBtn = document.getElementById('crpPrevBtn');
        const nextBtn = document.getElementById('crpNextBtn');
        if (prevBtn) prevBtn.disabled = index === 0;
        if (nextBtn) nextBtn.disabled = index >= crpTotalPages - 1;
    }

    function crpScrollToPage(index) {
        const track = document.getElementById('crpPagesTrack');
        if (!track) return;
        const targetPage = document.getElementById('crp_page_' + index);
        if (targetPage) {
            track.scrollTo({
                top: targetPage.offsetTop - track.offsetTop - 12,
                behavior: 'smooth'
            });
            crpCurrentPageIndex = index;
            crpUpdatePageUI(index);
        }
    }

    function crpPrevPage() {
        if (crpCurrentPageIndex > 0) {
            crpScrollToPage(crpCurrentPageIndex - 1);
        }
    }

    function crpNextPage() {
        if (crpCurrentPageIndex < crpTotalPages - 1) {
            crpScrollToPage(crpCurrentPageIndex + 1);
        }
    }

    function crpToggleComments() {
        const panel = document.getElementById('crpCommentsPanel');
        if (!panel) return;
        panel.classList.toggle('closed');
    }

    function crpRenderComments() {
        const list = document.getElementById('crpCommentsList');
        const commentBtnText = document.getElementById('crpCommentBtnText');
        if (!list) return;

        let comments = [];
        try {
            comments = JSON.parse(localStorage.getItem('deal_' + DEAL_ID + '_portal_comments') || '[]');
        } catch(e) {}

        if (commentBtnText) {
            commentBtnText.innerText = comments.length ? `Comments (${comments.length})` : 'Comments';
        }

        if (!comments.length) {
            list.innerHTML = `
                <div style="text-align: center; color: #94a3b8; font-size: 12.5px; padding: 32px 12px; line-height: 1.5;">
                    No comments yet. You can submit feedback, questions, or change requests here.
                </div>
            `;
            return;
        }

        list.innerHTML = comments.map(c => `
            <div class="crp-comment-card">
                <div class="crp-comment-header">
                    <span class="crp-comment-author">${escapeHtml(c.author || 'Client')}</span>
                    <span class="crp-comment-time">${escapeHtml(c.time || '')}</span>
                </div>
                <div class="crp-comment-body">"${escapeHtml(c.text)}"</div>
            </div>
        `).join('');
    }

    function crpAddComment(event) {
        event.preventDefault();
        const input = document.getElementById('crpCommentInput');
        if (!input) return;
        const text = input.value.trim();
        if (!text) return;

        let comments = [];
        try {
            comments = JSON.parse(localStorage.getItem('deal_' + DEAL_ID + '_portal_comments') || '[]');
        } catch(e) {}

        const now = new Date();
        const timeStr = now.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }) + ' · ' + now.toLocaleDateString([], { month: 'short', day: 'numeric', year: 'numeric' });

        const sent = getSentProposalSnapshot();
        const internalVer = sent ? sent.version : activeInternalVersionCode;

        comments.push({
            id: Date.now(),
            version: internalVer,
            author: CLIENT_NAME,
            text: text,
            page: crpCurrentPageIndex + 1,
            time: timeStr
        });

        localStorage.setItem('deal_' + DEAL_ID + '_portal_comments', JSON.stringify(comments));
        input.value = '';
        crpRenderComments();
    }

    function crpUpdateApprovalButtonState() {
        const btn = document.getElementById('crpApproveBtn');
        if (!btn) return;
        try {
            const sent = getSentProposalSnapshot();
            const decision = JSON.parse(localStorage.getItem('deal_' + DEAL_ID + '_proposal_decision') || 'null');
            const isApproved = (sent && sent.isApproved) || (decision && (decision.status === 'Accepted / Signed' || decision.status === 'Internal Approved'));
            if (isApproved) {
                btn.className = 'crp-btn-approved-badge';
                btn.onclick = null;
                btn.title = 'Proposal is approved';
                btn.innerHTML = `
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>
                    <span>✓ Approved</span>
                `;
            } else {
                btn.className = 'crp-btn-approve';
                btn.onclick = crpApproveProposal;
                btn.title = 'Approve and accept this proposal';
                btn.innerHTML = `
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>
                    <span>Approve Proposal</span>
                `;
            }
        } catch(e) {}
    }

    function crpApproveProposal() {
        if (confirm('Approve and accept this Proposal for ' + CLIENT_NAME + '?\n\nThis will record the proposal decision as Accepted / Signed.')) {
            const dateStr = new Date().toISOString().split('T')[0];
            
            let sent = getSentProposalSnapshot();
            if (sent) {
                sent.isApproved = true;
                sent.approvedAt = new Date().toISOString();
                sent.approvedBy = CLIENT_NAME;
                localStorage.setItem('deal_' + DEAL_ID + '_sent_proposal', JSON.stringify(sent));
            }

            localStorage.setItem('deal_' + DEAL_ID + '_proposal_decision', JSON.stringify({
                status: 'Accepted / Signed',
                notes: 'Approved and signed via Client Review Portal',
                date: dateStr,
                updatedBy: CLIENT_NAME
            }));

            const statusBadge = document.getElementById('pwiStatusBadge');
            if (statusBadge) statusBadge.innerText = 'Accepted / Signed';
            const headerStatus = document.getElementById('internalProposalHeaderStatus');
            if (headerStatus) headerStatus.innerText = 'Accepted / Signed';

            crpUpdateApprovalButtonState();
            alert('Proposal has been successfully approved.');
        }
    }

    /* APPROVE/ADJUST DISCOUNT MODAL */
    function getNextProposalRevisionLabel() {
        try {
            const activeBadge = document.getElementById('pwiActiveVersionBadge');
            if (activeBadge && activeBadge.innerText.trim()) {
                const badgeText = activeBadge.innerText.trim();
                const match = badgeText.match(/\d+/);
                if (match) {
                    return 'V' + (parseInt(match[0], 10) + 1);
                }
            }
            let revisions = JSON.parse(localStorage.getItem('deal_${window.DEAL_BOOTSTRAP?.dealId || 0}_revisions') || '[]');
            return 'V' + (revisions.length + 2);
        } catch (e) {
            return '';
        }
    }

    function handleDiscountScopeChange(scope) {
        const itemContainer = document.getElementById('discountItemSelectContainer');
        if (!itemContainer) return;
        if (scope === 'item') {
            itemContainer.style.display = 'flex';
        } else {
            itemContainer.style.display = 'none';
        }
    }

    function handleDiscountTypeChange(type) {
        const label = document.getElementById('modalDiscountValueLabel');
        const input = document.getElementById('modalAdjustDiscountValue');
        if (!label || !input) return;

        if (type === 'percentage') {
            label.innerText = 'Discount Percentage';
            input.placeholder = 'e.g. 10';
            input.max = '100';
            input.step = '0.1';
        } else {
            label.innerText = 'Discount Amount';
            input.placeholder = 'e.g. 5,000';
            input.removeAttribute('max');
            input.step = '0.01';
        }
    }

    function openAdjustDiscountModal() {
        const modal = document.getElementById('proposalDiscountModal');
        if (modal) {
            // Reset scope to item
            const scopeItemRadio = document.getElementById('discountScopeItem');
            if (scopeItemRadio) scopeItemRadio.checked = true;
            handleDiscountScopeChange('item');

            // Reset type to amount
            const typeSelect = document.getElementById('modalDiscountTypeSelect');
            if (typeSelect) typeSelect.value = 'amount';
            handleDiscountTypeChange('amount');

            // Current discount value
            const currentDiscount = parseFloat(document.getElementById('proposal_discount')?.value) || 0;
            const valueInput = document.getElementById('modalAdjustDiscountValue');
            if (valueInput) {
                valueInput.value = currentDiscount > 0 ? currentDiscount : '';
            }

            // Update Dynamic Revision Button Text
            const submitBtn = document.getElementById('modalDiscountSubmitBtn');
            if (submitBtn) {
                const nextRev = getNextProposalRevisionLabel();
                submitBtn.innerText = nextRev ? `Approve Discount & Create Revision (${nextRev})` : 'Approve Discount & Create Revision';
            }

            modal.style.display = 'flex';
        }
    }

    function closeAdjustDiscountModal() {
        const modal = document.getElementById('proposalDiscountModal');
        if (modal) modal.style.display = 'none';
    }

    function handleSaveAdjustDiscount(event) {
        event.preventDefault();
        const discountType = document.getElementById('modalDiscountTypeSelect')?.value || 'amount';
        const rawValue = parseFloat(document.getElementById('modalAdjustDiscountValue')?.value) || 0;
        const scope = document.querySelector('input[name="discount_scope"]:checked')?.value || 'item';
        
        let calculatedDiscount = 0;
        if (discountType === 'percentage') {
            let baseAmount = 0;
            if (scope === 'item') {
                const itemSelect = document.getElementById('modalDiscountItemSelect');
                const selectedOption = itemSelect ? itemSelect.options[itemSelect.selectedIndex] : null;
                baseAmount = selectedOption ? (parseFloat(selectedOption.dataset.price) || 0) : 0;
                if (!baseAmount) {
                    baseAmount = ((window.DEAL_BOOTSTRAP?.proposalTotals?.services ?? 0) + (window.DEAL_BOOTSTRAP?.proposalTotals?.products ?? 0));
                }
            } else {
                baseAmount = ((window.DEAL_BOOTSTRAP?.proposalTotals?.services ?? 0) + (window.DEAL_BOOTSTRAP?.proposalTotals?.products ?? 0));
                if (!baseAmount) {
                    baseAmount = parseFloat('(window.DEAL_BOOTSTRAP?.dealAmount ?? 0)') || 0;
                }
            }
            calculatedDiscount = baseAmount * (rawValue / 100);
        } else {
            calculatedDiscount = rawValue;
        }

        const discountInput = document.getElementById('proposal_discount');
        if (discountInput) {
            discountInput.value = calculatedDiscount.toFixed(2);
            updateProposalLiveTotals();
        }

        // Generate and log dynamic revision
        const nextRev = getNextProposalRevisionLabel() || 'Rev 2';
        const remarks = document.getElementById('modalDiscountRemarks')?.value.trim() || ('Discount approved: ' + formatCurrency(calculatedDiscount));

        let revisions = JSON.parse(localStorage.getItem('deal_${window.DEAL_BOOTSTRAP?.dealId || 0}_revisions') || '[]');
        revisions.push({
            revision: nextRev,
            reason: remarks,
            requester: (window.DEAL_BOOTSTRAP?.ownerName ?? "Authorized Approver"),
            date: new Date().toISOString().split('T')[0],
            createdAt: new Date().toLocaleString()
        });
        localStorage.setItem('deal_${window.DEAL_BOOTSTRAP?.dealId || 0}_revisions', JSON.stringify(revisions));

        const revBadge = document.getElementById('pwiActiveVersionBadge');
        const revTitle = document.getElementById('pwiLedgerTitleVersion');
        if (revBadge) revBadge.innerText = nextRev;
        if (revTitle) revTitle.innerText = 'PROPOSAL ITEM DECISION LEDGER (' + nextRev + ')';

        closeAdjustDiscountModal();
        alert('Proposal discount updated to ' + formatCurrency(calculatedDiscount) + ' and ' + nextRev + ' created successfully.');
    }

    /* UPDATE DECISION MODAL */
    function openUpdateDecisionModal() {
        const modal = document.getElementById('proposalDecisionModal');
        if (modal) {
            const dateInput = document.getElementById('proposalDecisionDate');
            if (dateInput && !dateInput.value) {
                dateInput.value = new Date().toISOString().split('T')[0];
            }
            modal.style.display = 'flex';
        }
    }

    function closeUpdateDecisionModal() {
        const modal = document.getElementById('proposalDecisionModal');
        if (modal) modal.style.display = 'none';
    }

    function handleSaveProposalDecision(event) {
        event.preventDefault();
        const decision = document.getElementById('proposalDecisionStatus').value;
        const decisionNotes = document.getElementById('proposalDecisionNotes').value;
        
        localStorage.setItem('deal_${window.DEAL_BOOTSTRAP?.dealId || 0}_proposal_decision', JSON.stringify({
            status: decision,
            notes: decisionNotes,
            date: document.getElementById('proposalDecisionDate').value,
            updatedBy: currentUser
        }));

        const statusBadge = document.getElementById('pwiStatusBadge');
        if (statusBadge) statusBadge.innerText = decision;
        
        closeUpdateDecisionModal();
        alert('Proposal decision updated to: ' + decision);
    }

    /* DISPATCH ACTION REQUEST MODAL */
    function openDispatchActionRequestModal() {
        const modal = document.getElementById('proposalDispatchModal');
        if (modal) {
            const dateInput = document.getElementById('dispatchRequestDueDate');
            if (dateInput && !dateInput.value) {
                dateInput.value = new Date().toISOString().split('T')[0];
            }
            modal.style.display = 'flex';
        }
    }

    function closeDispatchActionRequestModal() {
        const modal = document.getElementById('proposalDispatchModal');
        if (modal) modal.style.display = 'none';
    }

    function handleSaveDispatchRequest(event) {
        event.preventDefault();
        const actionType = document.getElementById('dispatchRequestType').value;
        const channel = document.getElementById('dispatchRequestChannel') ? document.getElementById('dispatchRequestChannel').value : 'Secure No-Login Link';
        const priority = document.getElementById('dispatchRequestPriority') ? document.getElementById('dispatchRequestPriority').value : 'High';
        const department = document.getElementById('dispatchRequestDepartment') ? document.getElementById('dispatchRequestDepartment').value : 'Operations & Delivery';
        const dueDate = document.getElementById('dispatchRequestDueDate') ? document.getElementById('dispatchRequestDueDate').value : '';
        const details = document.getElementById('dispatchRequestDetails') ? document.getElementById('dispatchRequestDetails').value : '';
        
        let dispatchRequests = JSON.parse(localStorage.getItem('deal_${window.DEAL_BOOTSTRAP?.dealId || 0}_dispatch_requests') || '[]');
        dispatchRequests.push({
            type: actionType,
            channel: channel,
            priority: priority,
            department: department,
            dueDate: dueDate,
            details: details,
            dispatchedBy: currentUser,
            createdAt: new Date().toLocaleString()
        });
        localStorage.setItem('deal_${window.DEAL_BOOTSTRAP?.dealId || 0}_dispatch_requests', JSON.stringify(dispatchRequests));
        
        renderUniversalClientActions();
        closeDispatchActionRequestModal();
        alert('Action Request [' + actionType + '] dispatched successfully!');
    }

    function renderUniversalClientActions() {
        const emptyState = document.getElementById('ucaEmptyState');
        const actionsList = document.getElementById('ucaActionsList');
        if (!emptyState || !actionsList) return;
        
        let dispatchRequests = [];
        try {
            dispatchRequests = JSON.parse(localStorage.getItem('deal_${window.DEAL_BOOTSTRAP?.dealId || 0}_dispatch_requests') || '[]');
        } catch(e) {}

        if (dispatchRequests.length === 0) {
            emptyState.style.display = 'flex';
            actionsList.style.display = 'none';
            actionsList.innerHTML = '';
            return;
        }

        emptyState.style.display = 'none';
        actionsList.style.display = 'flex';

        const version = document.getElementById('pwiActiveVersionBadge')?.innerText || 'V1';

        actionsList.innerHTML = dispatchRequests.map((req, index) => {
            const code = 'ACTION-' + String(index + 1).padStart(3, '0');
            const title = req.type ? (req.type + ' — ' + version) : ('Proposal Approval — ' + version);
            const docType = req.type || 'Proposal';
            const channel = req.channel || 'Secure No-Login Link';
            const dateStr = req.dueDate || req.createdAt || 'new Date().toLocaleDateString("en-US", { month: "short", day: "2-digit", year: "numeric" })';
            const status = req.status || 'Pending';
            const statusClass = status.toLowerCase() === 'completed' ? 'completed' : (status.toLowerCase() === 'in progress' ? 'progress' : 'pending');

            return `
                <div class="uca-action-card">
                    <div class="uca-action-card-top">
                        <span class="uca-action-code">${escapeHtml(code)}</span>
                        <span class="uca-badge uca-badge-${statusClass}">${escapeHtml(status)}</span>
                    </div>

                    <div class="uca-action-card-title">
                        ${escapeHtml(title)}
                    </div>

                    <div class="uca-action-card-body">
                        <div class="uca-meta-group">
                            <div class="uca-meta-item">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                                    <polyline points="14 2 14 8 20 8"></polyline>
                                    <line x1="16" y1="13" x2="8" y2="13"></line>
                                    <line x1="16" y1="17" x2="8" y2="17"></line>
                                </svg>
                                <span>${escapeHtml(docType)}</span>
                            </div>

                            <div class="uca-meta-item">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"></path>
                                    <path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"></path>
                                </svg>
                                <span>${escapeHtml(channel)}</span>
                            </div>

                            <div class="uca-meta-item">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                                    <line x1="16" y1="2" x2="16" y2="6"></line>
                                    <line x1="8" y1="2" x2="8" y2="6"></line>
                                    <line x1="3" y1="10" x2="21" y2="10"></line>
                                </svg>
                                <span>${escapeHtml(dateStr)}</span>
                            </div>
                        </div>

                        <div class="uca-action-card-btns">
                            <button type="button" class="uca-btn-view" onclick="openClientReviewPortalModal()">
                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8z"></path>
                                    <circle cx="12" cy="12" r="3"></circle>
                                </svg>
                                <span>View</span>
                            </button>

                            <button type="button" class="uca-btn-more btn-uca-more-action" title="More actions" onclick="toggleUcaMoreMenu(event, ${index})">⋮</button>
                        </div>
                    </div>
                </div>
            `;
        }).join('');
    }

    let currentSelectedUcaIndex = null;

    function toggleUcaMoreMenu(event, index) {
        event.stopPropagation();
        const menu = document.getElementById('ucaContextMenu');
        if (!menu) return;

        if (menu.style.display === 'flex' && currentSelectedUcaIndex === index) {
            closeUcaContextMenu();
            return;
        }

        currentSelectedUcaIndex = index;
        const buttonRect = event.currentTarget.getBoundingClientRect();
        
        menu.style.display = 'flex';
        const menuWidth = 130;
        let left = buttonRect.right - menuWidth;
        let top = buttonRect.bottom + 4;

        if (left < 10) left = 10;
        menu.style.left = left + 'px';
        menu.style.top = top + 'px';
    }

    function closeUcaContextMenu() {
        const menu = document.getElementById('ucaContextMenu');
        if (menu) menu.style.display = 'none';
    }

    function handleUcaContextMenuAction(action) {
        const index = currentSelectedUcaIndex;
        closeUcaContextMenu();
        if (index === null || index === undefined) return;

        if (action === 'view') {
            openClientReviewPortalModal();
        } else if (action === 'delete') {
            if (confirm('Are you sure you want to delete this action request?')) {
                let dispatchRequests = JSON.parse(localStorage.getItem('deal_${window.DEAL_BOOTSTRAP?.dealId || 0}_dispatch_requests') || '[]');
                dispatchRequests.splice(index, 1);
                localStorage.setItem('deal_${window.DEAL_BOOTSTRAP?.dealId || 0}_dispatch_requests', JSON.stringify(dispatchRequests));
                renderUniversalClientActions();
            }
        }
    }

    function handleDeleteUcaAction(event, index) {
        if (event) event.stopPropagation();
        if (confirm('Are you sure you want to delete this action request?')) {
            let dispatchRequests = JSON.parse(localStorage.getItem('deal_${window.DEAL_BOOTSTRAP?.dealId || 0}_dispatch_requests') || '[]');
            dispatchRequests.splice(index, 1);
            localStorage.setItem('deal_${window.DEAL_BOOTSTRAP?.dealId || 0}_dispatch_requests', JSON.stringify(dispatchRequests));
            renderUniversalClientActions();
        }
    }

    function handleUcaActionMenu(event, index) {
        toggleUcaMoreMenu(event, index);
    }

    /* =========================================================
       FINANCE WORKFLOW, PAYMENT ALLOCATION & LEDGER STATE
       ========================================================= */
    const financeDealId = (window.DEAL_BOOTSTRAP?.dealId ?? 0);
    const financeLoggedUser = (window.DEAL_BOOTSTRAP?.currentUser ?? "System Administrator");
    const serverQuotationCompleted = (window.DEAL_BOOTSTRAP?.isQuotationCompleted ?? false);
    const financeNoticeStorageKey = `deal_${financeDealId}_finance_notice_state`;
    const financeLedgerStorageKey = `deal_${financeDealId}_finance_payments`;

    let financeProposalItemsList = (window.DEAL_BOOTSTRAP?.financeProposalItems ?? []);

    let currentUploadedFinanceFile = null;

    function syncFinanceFromLineItems() {
        if (typeof dealLineItems !== 'undefined' && Array.isArray(dealLineItems) && dealLineItems.length > 0) {
            financeProposalItemsList = dealLineItems.map((it, idx) => ({
                id: it.id,
                name: it.name,
                code: '""-' + String(idx + 1).padStart(2, '0'),
                price: calculateLineItemTotal(it)
            }));
        }

        const itemSelect = document.getElementById('financeTargetItemSelect');
        if (itemSelect && financeProposalItemsList && financeProposalItemsList.length > 0) {
            const curVal = itemSelect.value;
            itemSelect.innerHTML = financeProposalItemsList.map(item => `
                <option value="${item.id}" data-price="${item.price}">
                    #${item.id} - ${escapeHtml(item.name)} (${formatFinanceMoney(item.price)})
                </option>
            `).join('');
            if (financeProposalItemsList.some(i => String(i.id) === String(curVal))) {
                itemSelect.value = curVal;
            } else if (financeProposalItemsList[0]) {
                itemSelect.value = String(financeProposalItemsList[0].id);
            }
        }

        const tableBody = document.getElementById('financeAllocationTableBody');
        if (tableBody && financeProposalItemsList && financeProposalItemsList.length > 0) {
            tableBody.innerHTML = financeProposalItemsList.map(item => `
                <tr data-item-id="${item.id}">
                    <td>
                        <div class="finance-item-title">${escapeHtml(item.name)}</div>
                        <a href="#" onclick="openProposalWorkspace(); return false;" class="finance-item-code">
                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink: 0;"><path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"></path><line x1="7" y1="7" x2="7.01" y2="7"></line></svg>
                            <span>${escapeHtml(item.code)}</span>
                        </a>
                    </td>
                    <td class="td-amount" style="font-weight: 700; color: #0f172a;">
                        ${formatFinanceMoney(item.price)}
                    </td>
                    <td class="td-amount finance-col-allocated" style="font-weight: 700; color: #0f172a;">
                        ₱0.00
                    </td>
                    <td class="td-amount finance-col-balance" style="font-weight: 700; color: #0f172a;">
                        ${formatFinanceMoney(item.price)}
                    </td>
                    <td class="td-center finance-col-payment-state">
                        <span class="finance-pill-unpaid">Unpaid</span>
                    </td>
                    <td class="td-center finance-col-activation-readiness">
                        <span class="finance-pill-pending">Pending Payment</span>
                    </td>
                    <td class="td-center">
                        <button type="button" 
                                class="finance-btn-allocate" 
                                onclick="openRecordPaymentModal({scope: 'item', itemId: ${item.id}})">
                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                <line x1="12" y1="5" x2="12" y2="19"></line>
                                <line x1="5" y1="12" x2="19" y2="12"></line>
                            </svg>
                            <span>Allocate</span>
                        </button>
                    </td>
                </tr>
            `).join('');
        }

        renderFinanceLedgerAndAllocations();
    }

    function initFinanceTab() {
        renderFinanceNotifyUI();
        syncFinanceFromLineItems();
        renderFinanceLedgerAndAllocations();
    }

    // Initialize Finance on load
    document.addEventListener('DOMContentLoaded', function() {
        initFinanceTab();
    });

    /* NOTIFY FINANCE WORKFLOW */
    function getFinanceNoticeState() {
        try {
            const raw = localStorage.getItem(financeNoticeStorageKey);
            if (raw) {
                return JSON.parse(raw);
            }
        } catch(e) {}

        return {
            status: serverQuotationCompleted ? 'quotation_completed' : 'initial',
            quotationSentAt: null,
            quotationSentBy: null,
            paymentSentAt: null,
            paymentSentBy: null
        };
    }

    function saveFinanceNoticeState(state) {
        try {
            localStorage.setItem(financeNoticeStorageKey, JSON.stringify(state));
        } catch(e) {}
        renderFinanceNotifyUI();
    }

    function toggleFinanceNotifyDropdown(e) {
        if (e) e.stopPropagation();
        const menu = document.getElementById('financeNotifyMenu');
        if (!menu) return;
        const isOpen = menu.style.display === 'block';
        menu.style.display = isOpen ? 'none' : 'block';

        const btn = document.getElementById('financeNotifyTriggerBtn');
        if (btn) btn.setAttribute('aria-expanded', isOpen ? 'false' : 'true');
    }

    document.addEventListener('click', function(e) {
        const wrapper = document.getElementById('financeNotifyWrapper');
        const menu = document.getElementById('financeNotifyMenu');
        if (menu && wrapper && !wrapper.contains(e.target)) {
            menu.style.display = 'none';
            const btn = document.getElementById('financeNotifyTriggerBtn');
            if (btn) btn.setAttribute('aria-expanded', 'false');
        }
    });

    function renderFinanceNotifyUI() {
        const state = getFinanceNoticeState();
        const triggerBtn = document.getElementById('financeNotifyTriggerBtn');
        const btnIcon = document.getElementById('financeNotifyBtnIcon');
        const btnLabel = document.getElementById('financeNotifyBtnLabel');

        const optQuotation = document.getElementById('financeOptionQuotationNotice');
        const optQuotationTitle = document.getElementById('financeOptionQuotationTitle');
        const optQuotationSub = document.getElementById('financeOptionQuotationSub');

        const optPayment = document.getElementById('financeOptionPaymentNotice');
        const optPaymentIcon = document.getElementById('financeOptionPaymentIcon');
        const optPaymentTitle = document.getElementById('financeOptionPaymentTitle');
        const optPaymentSub = document.getElementById('financeOptionPaymentSub');

        const banner = document.getElementById('financeNoticeBanner');
        const bannerText = document.getElementById('financeNoticeText');
        const bannerTime = document.getElementById('financeNoticeTime');
        const bannerIcon = document.getElementById('financeNoticeIcon');

        if (!triggerBtn) return;

        // Reset classes
        triggerBtn.className = 'finance-notify-btn';

        const isQuotDone = serverQuotationCompleted || state.status === 'quotation_completed' || state.status === 'payment_sent';

        if (state.status === 'payment_sent') {
            // STATE 4: PAYMENT NOTICE SENT
            triggerBtn.classList.add('btn-notify-payment-sent');
            btnLabel.innerText = 'Payment Notice Sent';
            btnIcon.innerHTML = `<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>`;

            if (optQuotation) {
                optQuotation.classList.remove('disabled');
                optQuotationTitle.innerText = 'Quotation Notice Sent';
                optQuotationSub.innerText = state.quotationSentAt ? `Sent on ${state.quotationSentAt}` : 'Quotation dispatched to Finance';
            }

            if (optPayment) {
                optPayment.classList.remove('disabled');
                optPayment.disabled = false;
                optPaymentIcon.innerHTML = `<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#15803d" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>`;
                optPaymentTitle.innerText = 'Payment Notice Sent';
                optPaymentSub.innerText = state.paymentSentAt ? `Sent on ${state.paymentSentAt}` : 'Payment confirmation notice active';
            }

            if (banner) {
                banner.style.display = 'flex';
                banner.className = 'finance-notice-banner notice-success';
                bannerIcon.innerHTML = `<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>`;
                bannerText.innerText = 'Payment notice has been sent. Waiting for payment confirmation.';
                bannerTime.innerText = state.paymentSentAt ? `${state.paymentSentAt}` : '';
            }

        } else if (isQuotDone) {
            // STATE 3: QUOTATION COMPLETED -> PAYMENT NOTICE NOW AVAILABLE
            triggerBtn.classList.add('btn-notify-payment-ready');
            btnLabel.innerText = 'Send Payment Notice';
            btnIcon.innerHTML = `<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="4" width="20" height="16" rx="2"></rect><line x1="12" y1="8" x2="12" y2="16"></line><line x1="8" y1="12" x2="16" y2="12"></line></svg>`;

            if (optQuotation) {
                optQuotation.classList.remove('disabled');
                optQuotationTitle.innerText = 'Quotation Completed';
                optQuotationSub.innerText = 'Quotation has been processed successfully.';
            }

            if (optPayment) {
                optPayment.classList.remove('disabled');
                optPayment.disabled = false;
                optPaymentIcon.innerHTML = `<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#2563eb" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="4" width="20" height="16" rx="2"></rect><line x1="12" y1="8" x2="12" y2="16"></line><line x1="8" y1="12" x2="16" y2="12"></line></svg>`;
                optPaymentTitle.innerText = 'Send Payment Notice';
                optPaymentSub.innerText = 'Notify Finance desk for payment collection.';
            }

            if (banner) {
                banner.style.display = 'flex';
                banner.className = 'finance-notice-banner notice-info';
                bannerIcon.innerHTML = `<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 14 14"></polyline></svg>`;
                bannerText.innerText = 'Quotation Completed • Payment notice is now available.';
                bannerTime.innerText = state.quotationSentAt ? `${state.quotationSentAt}` : '';
            }

        } else if (state.status === 'quotation_sent') {
            // STATE 2: QUOTATION NOTICE SENT
            triggerBtn.classList.add('btn-notify-sent');
            btnLabel.innerText = 'Quotation Notice Sent';
            btnIcon.innerHTML = `<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>`;

            if (optQuotation) {
                optQuotation.classList.remove('disabled');
                optQuotationTitle.innerText = 'Quotation Notice Sent';
                optQuotationSub.innerText = state.quotationSentAt ? `Sent on ${state.quotationSentAt}` : 'Quotation dispatched to Finance';
            }

            if (optPayment) {
                optPayment.classList.add('disabled');
                optPayment.disabled = true;
                optPaymentIcon.innerHTML = `<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#94a3b8" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>`;
                optPaymentTitle.innerText = 'Send Payment Notice';
                optPaymentSub.innerText = 'Available after quotation is completed';
            }

            if (banner) {
                banner.style.display = 'flex';
                banner.className = 'finance-notice-banner notice-amber';
                bannerIcon.innerHTML = `<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>`;
                bannerText.innerText = 'Quotation notice has been sent. Waiting for quotation to be processed.';
                bannerTime.innerText = state.quotationSentAt ? `${state.quotationSentAt}` : '';
            }

        } else {
            // STATE 1: INITIAL STATE (QUOTATION NOT YET COMPLETED)
            triggerBtn.classList.add('btn-notify-initial');
            btnLabel.innerText = 'Notify Finance';
            btnIcon.innerHTML = `<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path><path d="M13.73 21a2 2 0 0 1-3.46 0"></path></svg>`;

            if (optQuotation) {
                optQuotation.classList.remove('disabled');
                optQuotationTitle.innerText = 'Send Quotation Notice';
                optQuotationSub.innerText = 'Dispatch quotation notice to Finance desk.';
            }

            if (optPayment) {
                optPayment.classList.add('disabled');
                optPayment.disabled = true;
                optPaymentIcon.innerHTML = `<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#94a3b8" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>`;
                optPaymentTitle.innerText = 'Send Payment Notice';
                optPaymentSub.innerText = 'Available after quotation is completed';
            }

            if (banner) {
                banner.style.display = 'none';
            }
        }
    }

    function handleSendFinanceNotice(type) {
        const state = getFinanceNoticeState();
        const nowStr = new Date().toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric', hour: '2-digit', minute: '2-digit' });

        if (type === 'quotation') {
            state.status = 'quotation_sent';
            state.quotationSentAt = nowStr;
            state.quotationSentBy = financeLoggedUser;
            saveFinanceNoticeState(state);
            toggleFinanceNotifyDropdown();
            alert(`Quotation notice dispatched to Finance by ${financeLoggedUser}.`);
        } else if (type === 'payment') {
            const isQuotDone = serverQuotationCompleted || state.status === 'quotation_completed' || state.status === 'payment_sent';
            if (!isQuotDone) {
                alert('Payment Notice cannot be sent until quotation is completed.');
                return;
            }
            state.status = 'payment_sent';
            state.paymentSentAt = nowStr;
            state.paymentSentBy = financeLoggedUser;
            saveFinanceNoticeState(state);
            toggleFinanceNotifyDropdown();
            alert(`Payment notice dispatched to Finance by ${financeLoggedUser}.`);
        }
    }

    /* PAYMENT ALLOCATION RECORDS LEDGER & ITEM CALCULATIONS */
    function getFinanceLedgerRecords() {
        try {
            const raw = localStorage.getItem(financeLedgerStorageKey);
            if (raw !== null) {
                return JSON.parse(raw);
            }
            const initialRecords = [];
            localStorage.setItem(financeLedgerStorageKey, JSON.stringify(initialRecords));
            return initialRecords;
        } catch(e) {
            return [];
        }
    }

    function saveFinanceLedgerRecords(records) {
        try {
            localStorage.setItem(financeLedgerStorageKey, JSON.stringify(records));
        } catch(e) {}
        renderFinanceLedgerAndAllocations();
    }

    function formatFinanceMoney(val) {
        const num = parseFloat(val) || 0;
        return '₱' + num.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    function renderFinanceLedgerAndAllocations() {
        const records = getFinanceLedgerRecords();
        const emptyState = document.getElementById('financeLedgerEmptyState');
        const tableWrap = document.getElementById('financeLedgerTableWrap');
        const tbody = document.getElementById('financeLedgerTableBody');

        // 1. Calculate allocations per item
        const itemAllocations = {};
        let totalGeneralAllocated = 0;

        financeProposalItemsList.forEach(item => {
            itemAllocations[item.id] = 0;
        });

        records.forEach(rec => {
            const amt = parseFloat(rec.amount) || 0;
            if (rec.scope === 'item' && rec.itemId && itemAllocations[rec.itemId] !== undefined) {
                itemAllocations[rec.itemId] += amt;
            } else {
                // Distributed to items or general proposal allocation
                totalGeneralAllocated += amt;
            }
        });

        // Distribute general allocations across items if any
        if (totalGeneralAllocated > 0 && financeProposalItemsList.length > 0) {
            const totalFee = financeProposalItemsList.reduce((sum, it) => sum + (parseFloat(it.price) || 0), 0);
            financeProposalItemsList.forEach(item => {
                const ratio = totalFee > 0 ? (item.price / totalFee) : (1 / financeProposalItemsList.length);
                itemAllocations[item.id] += (totalGeneralAllocated * ratio);
            });
        }

        // 2. Update Item-Level Table Rows
        financeProposalItemsList.forEach(item => {
            const row = document.querySelector(`#financeAllocationTableBody tr[data-item-id="${item.id}"]`);
            if (!row) return;

            const allocated = itemAllocations[item.id] || 0;
            const balance = Math.max(0, item.price - allocated);

            const colAllocated = row.querySelector('.finance-col-allocated');
            const colBalance = row.querySelector('.finance-col-balance');
            const colPaymentState = row.querySelector('.finance-col-payment-state');
            const colActivation = row.querySelector('.finance-col-activation-readiness');
            const colAction = row.querySelector('td:last-child');

            if (colAllocated) colAllocated.innerText = formatFinanceMoney(allocated);
            if (colBalance) colBalance.innerText = formatFinanceMoney(balance);

            if (colPaymentState) {
                if (allocated <= 0) {
                    colPaymentState.innerHTML = '<span class="finance-pill-unpaid">Unpaid</span>';
                } else if (allocated < item.price - 0.01) {
                    colPaymentState.innerHTML = '<span class="finance-pill-partial">Partially Paid</span>';
                } else {
                    colPaymentState.innerHTML = '<span class="finance-pill-paid">Fully Paid</span>';
                }
            }

            if (colActivation) {
                if (allocated <= 0) {
                    colActivation.innerHTML = '<span class="finance-pill-pending">Pending Payment</span>';
                } else if (allocated < item.price - 0.01) {
                    colActivation.innerHTML = '<span class="finance-pill-ready">Ready for Activation</span>';
                } else {
                    colActivation.innerHTML = '<span class="finance-pill-active">Active</span>';
                }
            }

            if (colAction) {
                if (allocated >= item.price && item.price > 0) {
                    colAction.innerHTML = `
                        <span style="display: inline-flex; align-items: center; justify-content: center; padding: 4px 12px; font-size: 11.5px; font-weight: 600; color: #15803d; background: #dcfce7; border-radius: 9999px; border: 1px solid #bbf7d0; white-space: nowrap;">
                            Allocated
                        </span>
                    `;
                } else {
                    colAction.innerHTML = `
                        <button type="button" 
                                class="finance-btn-allocate" 
                                onclick="openRecordPaymentModal({scope: 'item', itemId: ${item.id}})">
                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                <line x1="12" y1="5" x2="12" y2="19"></line>
                                <line x1="5" y1="12" x2="19" y2="12"></line>
                            </svg>
                            <span>Allocate</span>
                        </button>
                    `;
                }
            }
        });

        // 2.1 Sync Financial Grid (Payment Terms & Commercials)
        const gridPaymentTerms = document.getElementById('financeGridPaymentTerms');
        const gridProfFee = document.getElementById('financeGridProfFee');
        const totalDealFee = financeProposalItemsList.reduce((sum, it) => sum + (parseFloat(it.price) || 0), 0);
        let totalAllAllocated = 0;
        Object.values(itemAllocations).forEach(v => totalAllAllocated += v);

        if (gridPaymentTerms) {
            const rawTerms = (window.DEAL_BOOTSTRAP?.paymentTerms ?? "");
            if (rawTerms && String(rawTerms).trim() !== '' && rawTerms !== '-') {
                gridPaymentTerms.innerText = rawTerms;
            } else if (totalAllAllocated >= totalDealFee && totalDealFee > 0) {
                gridPaymentTerms.innerText = '100% Full Payment (Fully Paid)';
            } else if (totalAllAllocated > 0) {
                gridPaymentTerms.innerText = `Partial Payment (${formatFinanceMoney(totalAllAllocated)})`;
            } else {
                gridPaymentTerms.innerText = '100% Advance Payment';
            }
        }
        if (gridProfFee && (gridProfFee.innerText === '-' || gridProfFee.innerText === '₱0.00')) {
            gridProfFee.innerText = formatFinanceMoney(totalDealFee > 0 ? totalDealFee : parseFloat(""));
        }

        // 3. Render Ledger Records Table
        if (!emptyState || !tableWrap || !tbody) return;

        if (records.length === 0) {
            emptyState.style.display = 'flex';
            tableWrap.style.display = 'none';
            tbody.innerHTML = '';
            return;
        }

        emptyState.style.display = 'none';
        tableWrap.style.display = 'block';

        tbody.innerHTML = records.map((rec, index) => {
            let scopeText = 'Entire Proposal';
            if (rec.scope === 'item') {
                const matched = financeProposalItemsList.find(i => String(i.id) === String(rec.itemId));
                scopeText = matched ? `Item: ${escapeHtml(matched.name)}` : 'Item: Audit Support / Coordination';
            } else if (rec.scope === 'downpayment') {
                scopeText = 'Agreed Downpayment';
            } else if (rec.scope === 'milestone') {
                scopeText = 'Milestone Payment';
            } else if (rec.scope === 'retainer') {
                scopeText = 'Retainer Period';
            }

            const refDisplay = rec.ref ? escapeHtml(rec.ref) : '-';
            const isRecImg = rec.isImage || (rec.fileDataUrl && rec.fileDataUrl.startsWith('data:image/')) || (rec.fileName && (/\.(jpg|jpeg|png|webp|gif|bmp|svg|heic)$/i.test(rec.fileName) || /image|screenshot|photo|img|chatgpt/i.test(rec.fileName)));
            const isRecPdf = rec.fileType === 'application/pdf' || (rec.fileDataUrl && rec.fileDataUrl.startsWith('data:application/pdf')) || (rec.fileName && /\.pdf$/i.test(rec.fileName));

            let badgeIcon = `<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline></svg>`;
            if (isRecImg) {
                badgeIcon = `<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="#2563eb" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><circle cx="8.5" cy="8.5" r="1.5"></circle><polyline points="21 15 16 10 5 21"></polyline></svg>`;
            } else if (isRecPdf) {
                badgeIcon = `<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="#dc2626" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline></svg>`;
            }

            const subRef = rec.fileName ? `
                <div style="margin-top: 4px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                    <button type="button" onclick="openFinanceAttachmentViewer(${index}, '${rec.ref ? escapeHtml(rec.ref) : ''}')" style="display: inline-flex; align-items: center; gap: 5px; font-size: 11.5px; font-weight: 600; color: #1e4f95; background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 6px; padding: 3px 8px; cursor: pointer; text-align: left; transition: all 0.15s ease;" onmouseenter="this.style.background='#dbeafe'; this.style.borderColor='#93c5fd'; this.style.color='#1e40af';" onmouseleave="this.style.background='#eff6ff'; this.style.borderColor='#bfdbfe'; this.style.color='#1e4f95';" title="Click to view uploaded ${isRecImg ? 'picture / image' : (isRecPdf ? 'PDF document' : 'file')}">
                        ${badgeIcon}
                        <span style="max-width: 140px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">${escapeHtml(rec.fileName)}</span>
                        <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                    </button>
                </div>
            ` : `<div style="color: #64748b; font-size: 12px; margin-top: 2px; font-weight: 400; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">${escapeHtml(rec.docType || 'Official Receipt')}</div>`;

            const recordedName = rec.recordedBy || financeLoggedUser || 'John Kelly Abalde';
            const dateDisplay = rec.date || 'Sep 16, 2026';
            const timeDisplay = rec.time || '10:24 AM';
            const methodDisplay = rec.method || 'Online Banking';

            const refClickable = rec.ref ? `
                <button type="button" onclick="openFinanceAttachmentViewer(${index}, '${rec.ref ? escapeHtml(rec.ref) : ''}')" style="background: transparent; border: none; padding: 0; font-weight: 700; color: #1e4f95; font-size: 13.5px; line-height: 1.3; white-space: nowrap; cursor: pointer; text-align: left; display: inline-flex; align-items: center; gap: 4px;" onmouseenter="this.style.textDecoration='underline'; this.style.color='#2563eb';" onmouseleave="this.style.textDecoration='none'; this.style.color='#1e4f95';" title="Click to view attachment / proof">
                    <span>${escapeHtml(rec.ref)}</span>
                </button>
            ` : '<span style="font-weight: 700; color: #0f172a; font-size: 13px;">-</span>';

            return `
                <tr>
                    <td class="col-ledger-date" style="white-space: nowrap; vertical-align: middle;">
                        <div style="font-weight: 600; color: #1e293b; font-size: 13px; line-height: 1.3; white-space: nowrap;">${escapeHtml(dateDisplay)}</div>
                        <div style="color: #64748b; font-size: 11.5px; margin-top: 3px; font-weight: 400; white-space: nowrap;">${escapeHtml(timeDisplay)}</div>
                    </td>
                    <td class="col-ledger-ref" style="vertical-align: middle;">
                        ${refClickable}
                        ${subRef}
                    </td>
                    <td class="col-ledger-scope" style="vertical-align: middle; color: #1e293b; font-weight: 600; font-size: 13px; word-break: normal; overflow-wrap: break-word; line-height: 1.4;">
                        ${scopeText}
                    </td>
                    <td class="col-ledger-method" style="vertical-align: middle; white-space: nowrap;">
                        <span class="finance-method-pill">
                            ${escapeHtml(methodDisplay)}
                        </span>
                    </td>
                    <td class="col-ledger-amount" style="vertical-align: middle; text-align: left; white-space: nowrap;">
                        <span style="font-weight: 700; color: #0f172a; font-size: 13.5px; white-space: nowrap;">
                            ${formatFinanceMoney(rec.amount)}
                        </span>
                    </td>
                    <td class="col-ledger-recorded" style="vertical-align: middle; font-weight: 600; color: #1e293b; font-size: 13px; word-break: normal; overflow-wrap: break-word; line-height: 1.35;">
                        ${escapeHtml(recordedName)}
                    </td>
                    <td class="col-ledger-notes" style="vertical-align: middle; color: ${rec.notes ? '#334155' : '#94a3b8'}; font-size: 13px; font-weight: ${rec.notes ? '500' : '400'}; word-break: normal; overflow-wrap: break-word;">
                        ${escapeHtml(rec.notes || '—')}
                    </td>
                    <td class="col-ledger-action" style="vertical-align: middle; text-align: center; white-space: nowrap;">
                        <button type="button" 
                                class="finance-btn-delete-ledger" 
                                onclick="handleVoidPaymentRecord(${index})" 
                                title="Delete payment record">
                            Delete
                        </button>
                    </td>
                </tr>
            `;
        }).join('');
    }

    function handleVoidPaymentRecord(index) {
        if (confirm('Are you sure you want to void/remove this payment allocation record?')) {
            const records = getFinanceLedgerRecords();
            records.splice(index, 1);
            saveFinanceLedgerRecords(records);
        }
    }

    /* MODAL: RECORD FLEXIBLE PAYMENT ALLOCATION */
    function openRecordPaymentModal(options = {}) {
        const modal = document.getElementById('financeRecordPaymentModal');
        if (!modal) return;

        syncFinanceFromLineItems();

        const scope = options.scope || 'item';
        const itemId = options.itemId || (financeProposalItemsList[0]?.id || 1);

        // Reset inputs
        document.getElementById('financePaymentForm').reset();
        currentUploadedFinanceFile = null;
        renderFinanceFilePreview();

        // Scope radios
        const radio = document.querySelector(`input[name="finance_allocation_scope"][value="${scope}"]`);
        if (radio) {
            radio.checked = true;
            handleFinanceScopeChange(scope);
        }

        // Select item
        const itemSelect = document.getElementById('financeTargetItemSelect');
        if (itemSelect) {
            itemSelect.value = String(itemId);
        }

        // Calculate suggested amount
        calculateSuggestedPaymentAmount(scope, itemId);

        modal.style.display = 'flex';
        const amtInput = document.getElementById('financePaymentAmount');
        if (amtInput) amtInput.focus();
    }

    function closeRecordPaymentModal() {
        const modal = document.getElementById('financeRecordPaymentModal');
        if (modal) modal.style.display = 'none';
        currentUploadedFinanceFile = null;
    }

    function handleFinanceScopeChange(scope) {
        document.querySelectorAll('.finance-scope-label').forEach(lbl => {
            lbl.classList.remove('active');
        });
        const activeRadio = document.querySelector(`input[name="finance_allocation_scope"][value="${scope}"]`);
        if (activeRadio && activeRadio.parentElement) {
            activeRadio.parentElement.classList.add('active');
        }

        const itemContainer = document.getElementById('financeTargetItemContainer');
        if (itemContainer) {
            itemContainer.style.display = (scope === 'item') ? 'block' : 'none';
        }

        const selectedItemId = document.getElementById('financeTargetItemSelect')?.value || (financeProposalItemsList[0]?.id || 1);
        calculateSuggestedPaymentAmount(scope, selectedItemId);
    }

    function handleTargetItemChange(itemId) {
        const scope = document.querySelector('input[name="finance_allocation_scope"]:checked')?.value || 'item';
        calculateSuggestedPaymentAmount(scope, itemId);
    }

    function calculateSuggestedPaymentAmount(scope, itemId) {
        const records = getFinanceLedgerRecords();
        const amtInput = document.getElementById('financePaymentAmount');
        if (!amtInput) return;

        if (scope === 'item') {
            const matched = financeProposalItemsList.find(i => String(i.id) === String(itemId));
            if (matched) {
                let allocated = 0;
                records.forEach(r => {
                    if (r.scope === 'item' && String(r.itemId) === String(itemId)) {
                        allocated += parseFloat(r.amount) || 0;
                    }
                });
                const balance = Math.max(0, matched.price - allocated);
                amtInput.value = balance > 0 ? balance.toFixed(2) : matched.price.toFixed(2);
            }
        } else {
            const totalFee = financeProposalItemsList.reduce((s, it) => s + (parseFloat(it.price) || 0), 0);
            let totalAllocated = records.reduce((s, r) => s + (parseFloat(r.amount) || 0), 0);
            const remaining = Math.max(0, totalFee - totalAllocated);
            amtInput.value = remaining > 0 ? remaining.toFixed(2) : totalFee.toFixed(2);
        }
    }

    /* DUAL UPLOAD UX (SEND FILE / SEND IMAGE - NO TAKE PHOTO) */
    function triggerFinanceUpload(type) {
        if (type === 'image') {
            const imgInput = document.getElementById('financeImageInput');
            if (imgInput) imgInput.click();
        } else {
            const fileInput = document.getElementById('financeFileInput');
            if (fileInput) fileInput.click();
        }
    }

    function handleFinanceFileSelected(e, isImage) {
        const file = e.target.files?.[0];
        if (!file) return;

        // Size validation (Max 15MB)
        if (file.size > 15 * 1024 * 1024) {
            alert('File size exceeds the 15MB limit. Please upload a smaller file.');
            e.target.value = '';
            return;
        }

        const sizeKb = file.size / 1024;
        const sizeStr = sizeKb > 1024 ? (sizeKb / 1024).toFixed(2) + ' MB' : sizeKb.toFixed(1) + ' KB';
        const isImg = isImage || file.type.startsWith('image/') || /\.(jpg|jpeg|png|webp|gif|bmp|svg|heic)$/i.test(file.name) || /image|screenshot|photo|img|chatgpt/i.test(file.name);

        currentUploadedFinanceFile = {
            name: file.name,
            size: sizeStr,
            isImage: isImg,
            fileType: file.type || (isImg ? 'image/png' : 'application/octet-stream'),
            dataUrl: null,
            fileObj: file
        };

        const reader = new FileReader();
        reader.onload = function(evt) {
            currentUploadedFinanceFile.dataUrl = evt.target.result;
            renderFinanceFilePreview();
        };
        reader.readAsDataURL(file);
        renderFinanceFilePreview();
    }

    function renderFinanceFilePreview() {
        const previewContainer = document.getElementById('financeFilePreviewBox');
        const uploadBtns = document.getElementById('financeUploadBtns');
        const previewLeft = document.getElementById('financeFilePreviewLeft');

        if (!previewContainer || !uploadBtns || !previewLeft) return;

        if (!currentUploadedFinanceFile) {
            previewContainer.style.display = 'none';
            uploadBtns.style.display = 'flex';
            previewLeft.innerHTML = '';
            return;
        }

        uploadBtns.style.display = 'none';
        previewContainer.style.display = 'flex';

        if (currentUploadedFinanceFile.isImage && currentUploadedFinanceFile.dataUrl) {
            previewLeft.innerHTML = `
                <img src="${currentUploadedFinanceFile.dataUrl}" alt="Proof" class="finance-file-thumb">
                <div class="finance-file-meta">
                    <span class="finance-file-name">${escapeHtml(currentUploadedFinanceFile.name)}</span>
                    <span class="finance-file-size">${escapeHtml(currentUploadedFinanceFile.size)}</span>
                </div>
            `;
        } else {
            previewLeft.innerHTML = `
                <div class="finance-file-icon-box">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline></svg>
                </div>
                <div class="finance-file-meta">
                    <span class="finance-file-name">${escapeHtml(currentUploadedFinanceFile.name)}</span>
                    <span class="finance-file-size">${escapeHtml(currentUploadedFinanceFile.size)}</span>
                </div>
            `;
        }
    }

    function removeFinanceFile() {
        currentUploadedFinanceFile = null;
        const fileInput = document.getElementById('financeFileInput');
        const imgInput = document.getElementById('financeImageInput');
        if (fileInput) fileInput.value = '';
        if (imgInput) imgInput.value = '';
        renderFinanceFilePreview();
    }

    async function handleSavePaymentAllocation(e) {
        e.preventDefault();

        const scope = document.querySelector('input[name="finance_allocation_scope"]:checked')?.value || 'item';
        const targetItemId = document.getElementById('financeTargetItemSelect')?.value || null;
        const amount = parseFloat(document.getElementById('financePaymentAmount')?.value) || 0;
        const method = document.getElementById('financePaymentMethod')?.value || 'Online Banking';
        const ref = document.getElementById('financeReceiptRef')?.value || '';
        const notes = document.getElementById('financePaymentNotes')?.value || '';

        if (amount <= 0) {
            alert('Please enter a valid payment amount.');
            return;
        }

        // If file is selected but dataUrl is still loading, wait for it
        if (currentUploadedFinanceFile && currentUploadedFinanceFile.fileObj && !currentUploadedFinanceFile.dataUrl) {
            currentUploadedFinanceFile.dataUrl = await new Promise((resolve) => {
                const reader = new FileReader();
                reader.onload = (evt) => resolve(evt.target.result);
                reader.onerror = () => resolve(null);
                reader.readAsDataURL(currentUploadedFinanceFile.fileObj);
            });
        }

        const now = new Date();
        const dateStr = now.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
        const timeStr = now.toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit', hour12: true });

        const record = {
            id: Date.now(),
            date: dateStr,
            time: timeStr,
            scope: scope,
            itemId: (scope === 'item') ? targetItemId : null,
            amount: amount,
            method: method,
            ref: ref || ('OR-' + String(Math.floor(1000 + Math.random() * 9000))),
            docType: 'Official Receipt',
            fileName: currentUploadedFinanceFile?.name || null,
            fileSize: currentUploadedFinanceFile?.size || null,
            fileDataUrl: currentUploadedFinanceFile?.dataUrl || null,
            isImage: currentUploadedFinanceFile?.isImage || false,
            fileType: currentUploadedFinanceFile?.fileType || null,
            recordedBy: financeLoggedUser || 'John Kelly Abalde',
            notes: notes
        };

        const records = getFinanceLedgerRecords();
        records.unshift(record);
        saveFinanceLedgerRecords(records);

        closeRecordPaymentModal();
        alert(`Payment of ${formatFinanceMoney(amount)} successfully allocated!`);
    }

    /* =========================================================
       FINANCE ATTACHMENT / IMAGE VIEWER MODAL LOGIC & RICH FEATURES
       ========================================================= */
    let currentFinanceViewerZoom = 1;
    let currentFinanceViewerRotation = 0;
    let currentFinanceViewerFlipX = false;
    let currentFinanceViewerFilter = false;
    let currentFinanceViewerPanX = 0;
    let currentFinanceViewerPanY = 0;
    let isFinanceViewerPanning = false;
    let financeViewerPanStartX = 0;
    let financeViewerPanStartY = 0;
    let isFinanceViewerInfoOpen = false;
    let currentFinanceViewerRecordIndex = 0;
    let currentActiveViewerRecord = null;
    let currentActiveViewerImgSrc = null;

    function generateReceiptProofImage(record) {
        try {
            const ref = escapeHtml(record.ref || 'OR-8043');
            const fileName = escapeHtml(record.fileName || (record.ref ? `${record.ref}-proof.png` : 'Payment_Proof.png'));
            const amount = formatFinanceMoney(record.amount || 0);
            const date = escapeHtml(`${record.date || 'Sep 29, 2026'} • ${record.time || '10:00 AM'}`);
            const method = escapeHtml(record.method || 'Online Banking / GCash / Transfer');
            const recorded = escapeHtml(record.recordedBy || financeLoggedUser || 'Finance Desk');
            const scope = escapeHtml(record.scope === 'item' ? 'Item Payment Allocation' : (record.scope === 'downpayment' ? 'Initial Downpayment (50%)' : 'General Proposal Payment'));
            const notes = record.notes ? escapeHtml(record.notes) : '';

            const svg = `<svg xmlns="http://www.w3.org/2000/svg" width="680" height="860" viewBox="0 0 680 860">
                <rect width="680" height="860" fill="#0f172a" rx="14"/>
                <rect x="24" y="24" width="632" height="812" fill="#ffffff" rx="12" stroke="#cbd5e1" stroke-width="2"/>
                <path d="M 24 36 Q 24 24 36 24 L 644 24 Q 656 24 656 36 L 656 120 L 24 120 Z" fill="#1e4f95"/>
                <text x="340" y="68" fill="#ffffff" font-size="20" font-weight="bold" font-family="system-ui, -apple-system, sans-serif" text-anchor="middle">ELECTRONIC PAYMENT RECEIPT</text>
                <text x="340" y="96" fill="#bfdbfe" font-size="12.5" font-family="system-ui, -apple-system, sans-serif" text-anchor="middle">FINANCE &amp; COLLECTION DESK • VERIFIED PROOF</text>
                
                <rect x="235" y="155" width="210" height="32" rx="16" fill="#dcfce7"/>
                <text x="340" y="176" fill="#15803d" font-size="12.5" font-weight="bold" font-family="system-ui, -apple-system, sans-serif" text-anchor="middle">✓ PAYMENT VERIFIED &amp; POSTED</text>
                
                <text x="340" y="222" fill="#64748b" font-size="12" font-family="system-ui, -apple-system, sans-serif" text-anchor="middle">TOTAL AMOUNT PAID</text>
                <text x="340" y="265" fill="#0f172a" font-size="34" font-weight="bold" font-family="system-ui, -apple-system, sans-serif" text-anchor="middle">${amount}</text>
                
                <line x1="50" y1="295" x2="630" y2="295" stroke="#cbd5e1" stroke-width="1.5" stroke-dasharray="5 5"/>
                
                <text x="60" y="340" fill="#64748b" font-size="12.5" font-family="system-ui, -apple-system, sans-serif">Official Receipt Ref No.</text>
                <text x="620" y="340" fill="#0f172a" font-size="13" font-weight="bold" font-family="system-ui, -apple-system, sans-serif" text-anchor="end">${ref}</text>
                <line x1="60" y1="352" x2="620" y2="352" stroke="#f1f5f9" stroke-width="1"/>

                <text x="60" y="386" fill="#64748b" font-size="12.5" font-family="system-ui, -apple-system, sans-serif">Attached File Name</text>
                <text x="620" y="386" fill="#1e4f95" font-size="13" font-weight="bold" font-family="system-ui, -apple-system, sans-serif" text-anchor="end">${fileName}</text>
                <line x1="60" y1="398" x2="620" y2="398" stroke="#f1f5f9" stroke-width="1"/>

                <text x="60" y="432" fill="#64748b" font-size="12.5" font-family="system-ui, -apple-system, sans-serif">Payment Method</text>
                <text x="620" y="432" fill="#0f172a" font-size="13" font-weight="bold" font-family="system-ui, -apple-system, sans-serif" text-anchor="end">${method}</text>
                <line x1="60" y1="444" x2="620" y2="444" stroke="#f1f5f9" stroke-width="1"/>

                <text x="60" y="478" fill="#64748b" font-size="12.5" font-family="system-ui, -apple-system, sans-serif">Date &amp; Time Recorded</text>
                <text x="620" y="478" fill="#0f172a" font-size="13" font-weight="bold" font-family="system-ui, -apple-system, sans-serif" text-anchor="end">${date}</text>
                <line x1="60" y1="490" x2="620" y2="490" stroke="#f1f5f9" stroke-width="1"/>

                <text x="60" y="524" fill="#64748b" font-size="12.5" font-family="system-ui, -apple-system, sans-serif">Allocation Scope</text>
                <text x="620" y="524" fill="#0f172a" font-size="13" font-weight="bold" font-family="system-ui, -apple-system, sans-serif" text-anchor="end">${scope}</text>
                <line x1="60" y1="536" x2="620" y2="536" stroke="#f1f5f9" stroke-width="1"/>

                <text x="60" y="570" fill="#64748b" font-size="12.5" font-family="system-ui, -apple-system, sans-serif">Recorded By (Finance)</text>
                <text x="620" y="570" fill="#0f172a" font-size="13" font-weight="bold" font-family="system-ui, -apple-system, sans-serif" text-anchor="end">${recorded}</text>
                <line x1="60" y1="582" x2="620" y2="582" stroke="#f1f5f9" stroke-width="1"/>

                ${notes ? `
                    <text x="60" y="616" fill="#64748b" font-size="12" font-family="system-ui, -apple-system, sans-serif">Notes / Remarks</text>
                    <text x="620" y="616" fill="#334155" font-size="12.5" font-family="system-ui, -apple-system, sans-serif" text-anchor="end">${notes}</text>
                    <line x1="60" y1="628" x2="620" y2="628" stroke="#f1f5f9" stroke-width="1"/>
                ` : ''}

                <g transform="translate(480, 710) rotate(-8)">
                    <rect x="-85" y="-26" width="170" height="52" rx="8" fill="none" stroke="#dc2626" stroke-width="2.5"/>
                    <text x="0" y="-3" fill="#dc2626" font-size="13.5" font-weight="bold" font-family="system-ui, -apple-system, sans-serif" text-anchor="middle">FINANCE APPROVED</text>
                    <text x="0" y="15" fill="#dc2626" font-size="10" font-family="system-ui, -apple-system, sans-serif" text-anchor="middle">OFFICIAL RECEIPT PROOF</text>
                </g>

                <text x="340" y="805" fill="#94a3b8" font-size="11" font-family="system-ui, -apple-system, sans-serif" text-anchor="middle">This document serves as an electronic payment proof record.</text>
            </svg>`;

            return 'data:image/svg+xml;charset=utf-8,' + encodeURIComponent(svg);
        } catch(e) {
            console.error('Error generating SVG receipt:', e);
            return null;
        }
    }

    window.openFinanceAttachmentViewer = function(recordIdentifier, fallbackRef) {
        try {
            const records = getFinanceLedgerRecords();
            let record = null;
            let index = 0;

            if (typeof recordIdentifier === 'number') {
                index = Math.max(0, Math.min(records.length - 1, recordIdentifier));
                record = records[index];
            } else if (recordIdentifier !== undefined && recordIdentifier !== null) {
                const foundIdx = records.findIndex(r => String(r.id) === String(recordIdentifier) || String(r.ref) === String(recordIdentifier));
                if (foundIdx !== -1) {
                    index = foundIdx;
                    record = records[foundIdx];
                }
            }
            if (!record && fallbackRef) {
                const foundIdx = records.findIndex(r => String(r.ref) === String(fallbackRef));
                if (foundIdx !== -1) {
                    index = foundIdx;
                    record = records[foundIdx];
                }
            }
            if (!record && records.length > 0) {
                index = 0;
                record = records[0];
            }
            if (!record) {
                record = {
                    ref: fallbackRef || 'OR-8043',
                    fileName: 'ChatGPT Image Sep 2026.png',
                    amount: 0,
                    method: 'Online Banking',
                    date: 'Sep 29, 2026',
                    time: '10:00 AM'
                };
            }

            currentFinanceViewerRecordIndex = index;
            currentActiveViewerRecord = record;
            currentFinanceViewerZoom = 1;
            currentFinanceViewerRotation = 0;
            currentFinanceViewerFlipX = false;
            currentFinanceViewerFilter = false;
            currentFinanceViewerPanX = 0;
            currentFinanceViewerPanY = 0;

            const modal = document.getElementById('financeAttachmentViewerModal');
            if (!modal) {
                console.error('financeAttachmentViewerModal element not found');
                return;
            }

            const titleEl = document.getElementById('financeViewerTitle');
            const subEl = document.getElementById('financeViewerSubtitle');
            const counterEl = document.getElementById('financeViewerCounterBadge');
            const contentEl = document.getElementById('financeViewerContent');
            const zoomControls = document.getElementById('financeViewerZoomControls');
            const prevBtn = document.getElementById('financeViewerPrevBtn');
            const nextBtn = document.getElementById('financeViewerNextBtn');
            const thumbStrip = document.getElementById('financeViewerThumbStrip');

            const fileName = record.fileName || (record.ref ? `${record.ref}-proof.png` : 'Payment_Proof.png');
            const refName = record.ref || 'Official Receipt';

            const isImg = record.isImage || (record.fileDataUrl && record.fileDataUrl.startsWith('data:image/')) || (record.fileName && (/\.(jpg|jpeg|png|webp|gif|bmp|svg|heic)$/i.test(record.fileName) || /image|screenshot|photo|img|chatgpt/i.test(record.fileName))) || !record.fileDataUrl;
            const isPdf = record.fileType === 'application/pdf' || (record.fileDataUrl && record.fileDataUrl.startsWith('data:application/pdf')) || (record.fileName && /\.pdf$/i.test(record.fileName));

            if (titleEl) titleEl.innerText = `${refName} - ${isPdf ? 'PDF Document Attachment' : (isImg ? 'Picture / Image Attachment' : 'File Attachment')}`;
            if (subEl) subEl.innerText = `${fileName} ${record.fileSize ? '(' + record.fileSize + ')' : ''} • Uploaded ${record.date || ''} ${record.time || ''}`;

            // Multi-record Navigation
            if (records.length > 1) {
                if (counterEl) {
                    counterEl.style.display = 'inline-block';
                    counterEl.innerText = `${index + 1} of ${records.length}`;
                }
                if (prevBtn) prevBtn.style.display = 'flex';
                if (nextBtn) nextBtn.style.display = 'flex';
                if (thumbStrip) {
                    thumbStrip.style.display = 'flex';
                    thumbStrip.innerHTML = records.map((r, i) => {
                        const active = (i === index);
                        const isRImg = r.isImage || (r.fileDataUrl && r.fileDataUrl.startsWith('data:image/')) || (r.fileName && (/\.(jpg|jpeg|png|webp|gif|bmp|svg|heic)$/i.test(r.fileName) || /image|screenshot|photo|img|chatgpt/i.test(r.fileName))) || !r.fileDataUrl;
                        const src = (r.fileDataUrl && r.fileDataUrl.startsWith('data:image/')) ? r.fileDataUrl : generateReceiptProofImage(r);
                        return `
                            <button type="button" onclick="openFinanceAttachmentViewer(${i})" style="border: 2px solid ${active ? '#2563eb' : '#cbd5e1'}; border-radius: 8px; padding: 3px 6px; background: ${active ? '#eff6ff' : '#ffffff'}; cursor: pointer; display: flex; align-items: center; gap: 8px; flex-shrink: 0; transition: all 0.15s ease; box-shadow: ${active ? '0 2px 8px rgba(37,99,235,0.2)' : 'none'};">
                                <img src="${src}" style="width: 34px; height: 34px; object-fit: cover; border-radius: 5px; border: 1px solid #e2e8f0;">
                                <div style="text-align: left; padding-right: 4px;">
                                    <div style="font-size: 11.5px; font-weight: 700; color: ${active ? '#1e40af' : '#0f172a'};">${escapeHtml(r.ref || `OR-${i+1}`)}</div>
                                    <div style="font-size: 10.5px; color: #64748b; font-weight: 600;">${formatFinanceMoney(r.amount || 0)}</div>
                                </div>
                            </button>
                        `;
                    }).join('');
                }
            } else {
                if (counterEl) counterEl.style.display = 'none';
                if (prevBtn) prevBtn.style.display = 'none';
                if (nextBtn) nextBtn.style.display = 'none';
                if (thumbStrip) thumbStrip.style.display = 'none';
            }

            // Populate Metadata Sidebar
            renderFinanceViewerMetadata(record, index);

            if (isPdf && record.fileDataUrl) {
                currentActiveViewerImgSrc = record.fileDataUrl;
                if (zoomControls) zoomControls.style.display = 'none';
                if (contentEl) {
                    contentEl.innerHTML = `
                        <div style="width: 100%; height: 65vh; border-radius: 8px; overflow: hidden; background: #f8fafc; border: 1px solid #e2e8f0;">
                            <iframe src="${record.fileDataUrl}" style="width: 100%; height: 100%; border: none;"></iframe>
                        </div>
                    `;
                }
            } else if (isImg || !record.fileDataUrl) {
                const imgSrc = (record.fileDataUrl && (record.fileDataUrl.startsWith('data:image/') || record.isImage))
                    ? record.fileDataUrl
                    : generateReceiptProofImage(record);

                currentActiveViewerImgSrc = imgSrc;

                if (zoomControls) zoomControls.style.display = 'flex';
                if (contentEl) {
                    contentEl.innerHTML = `
                        <div id="financeViewerImgContainer" 
                             style="display: flex; justify-content: center; align-items: center; width: 100%; height: 100%; min-height: 420px; max-height: 66vh; overflow: hidden; position: relative; cursor: grab;"
                             onmousedown="startFinanceViewerPan(event)"
                             onwheel="handleFinanceViewerWheel(event)">
                            <img id="financeViewerImg" 
                                 src="${imgSrc}" 
                                 alt="${escapeHtml(fileName)}" 
                                 draggable="false"
                                 style="max-width: 92%; max-height: 60vh; object-fit: contain; border-radius: 6px; box-shadow: 0 15px 40px rgba(0,0,0,0.7); transition: transform 0.15s ease-out, filter 0.2s ease; transform: translate(0px, 0px) scale(1) rotate(0deg); user-select: none;">
                        </div>
                    `;
                }
            } else {
                currentActiveViewerImgSrc = record.fileDataUrl;
                if (zoomControls) zoomControls.style.display = 'none';
                if (contentEl) {
                    contentEl.innerHTML = `
                        <div style="display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 40px 24px; background: #1e293b; border-radius: 12px; border: 1px dashed #475569; width: 100%; max-width: 500px; margin: auto; text-align: center; color: #f8fafc;">
                            <div style="width: 64px; height: 64px; border-radius: 16px; background: rgba(37,99,235,0.2); color: #60a5fa; display: inline-flex; align-items: center; justify-content: center; margin-bottom: 14px; box-shadow: 0 4px 12px rgba(0,0,0,0.3);">
                                <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline></svg>
                            </div>
                            <h4 style="font-size: 16px; font-weight: 700; color: #ffffff; margin: 0 0 6px;">${escapeHtml(fileName)}</h4>
                            <p style="font-size: 12.5px; color: #94a3b8; margin: 0 0 20px;">Uploaded File Document • ${escapeHtml(record.fileSize || 'Document file')}</p>
                            <button type="button" class="btn-primary" onclick="downloadFinanceViewerFile()" style="display: inline-flex; align-items: center; gap: 8px; padding: 9px 22px; border-radius: 8px; font-size: 13px; background: #2563eb; color: #ffffff; border: none; cursor: pointer; font-weight: 600; box-shadow: 0 4px 14px rgba(37,99,235,0.4);">
                                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
                                Download Document File
                            </button>
                        </div>
                    `;
                }
            }

            modal.style.setProperty('display', 'flex', 'important');
        } catch(err) {
            console.error('Error opening finance attachment viewer:', err);
        }
    };

    function renderFinanceViewerMetadata(record, index) {
        const bodyEl = document.getElementById('financeViewerSidebarBody');
        if (!bodyEl || !record) return;

        const isVerified = record.isVerified !== false;
        const scopeText = record.scope === 'item' ? 'Item Payment Allocation' : (record.scope === 'downpayment' ? 'Agreed Downpayment' : 'General Proposal Allocation');

        bodyEl.innerHTML = `
            <div style="padding: 16px; display: flex; flex-direction: column; gap: 14px; font-size: 12.5px; color: #334155;">
                <div style="background: ${isVerified ? '#f0fdf4' : '#fffbeb'}; border: 1px solid ${isVerified ? '#bbf7d0' : '#fef08a'}; border-radius: 10px; padding: 12px;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px;">
                        <span style="font-weight: 700; font-size: 11px; text-transform: uppercase; letter-spacing: 0.05em; color: ${isVerified ? '#15803d' : '#b45309'};">Verification Status</span>
                        <span style="font-size: 11px; font-weight: 700; color: ${isVerified ? '#16a34a' : '#d97706'}; background: ${isVerified ? '#dcfce7' : '#fef3c7'}; padding: 2px 8px; border-radius: 9999px;">
                            ${isVerified ? '✓ Verified' : '⏱ Pending'}
                        </span>
                    </div>
                    <p style="font-size: 11.5px; color: #475569; margin: 0 0 10px; line-height: 1.35;">
                        ${isVerified ? 'Payment and proof have been reviewed and posted to ledger.' : 'This proof is marked as pending accounting verification.'}
                    </p>
                    <button type="button" onclick="toggleFinanceViewerVerification(${index})" style="width: 100%; display: inline-flex; align-items: center; justify-content: center; gap: 6px; padding: 6px 12px; font-size: 11.5px; font-weight: 600; border-radius: 6px; cursor: pointer; border: 1px solid ${isVerified ? '#cbd5e1' : '#f59e0b'}; background: ${isVerified ? '#ffffff' : '#fef3c7'}; color: ${isVerified ? '#334155' : '#92400e'}; transition: all 0.15s ease;">
                        ${isVerified ? 'Mark as Pending Review' : '✓ Mark as Verified & Posted'}
                    </button>
                </div>

                <div style="display: flex; flex-direction: column; gap: 10px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; padding: 12px;">
                    <div>
                        <div style="font-size: 10.5px; text-transform: uppercase; font-weight: 700; color: #64748b;">Amount Paid</div>
                        <div style="font-size: 17px; font-weight: 800; color: #0f172a; margin-top: 1px;">${formatFinanceMoney(record.amount || 0)}</div>
                    </div>
                    <div style="border-top: 1px solid #e2e8f0; padding-top: 8px;">
                        <div style="font-size: 10.5px; text-transform: uppercase; font-weight: 700; color: #64748b;">Reference No.</div>
                        <div style="font-size: 13px; font-weight: 700; color: #1e40af; margin-top: 1px;">${escapeHtml(record.ref || 'OR-8043')}</div>
                    </div>
                    <div style="border-top: 1px solid #e2e8f0; padding-top: 8px;">
                        <div style="font-size: 10.5px; text-transform: uppercase; font-weight: 700; color: #64748b;">Payment Method</div>
                        <div style="font-size: 12.5px; font-weight: 600; color: #0f172a; margin-top: 1px;">${escapeHtml(record.method || 'Online Banking')}</div>
                    </div>
                    <div style="border-top: 1px solid #e2e8f0; padding-top: 8px;">
                        <div style="font-size: 10.5px; text-transform: uppercase; font-weight: 700; color: #64748b;">Date & Time</div>
                        <div style="font-size: 12px; color: #334155; margin-top: 1px;">${escapeHtml(record.date || '')} ${escapeHtml(record.time || '')}</div>
                    </div>
                    <div style="border-top: 1px solid #e2e8f0; padding-top: 8px;">
                        <div style="font-size: 10.5px; text-transform: uppercase; font-weight: 700; color: #64748b;">Allocation Scope</div>
                        <div style="font-size: 12px; font-weight: 600; color: #334155; margin-top: 1px;">${scopeText}</div>
                    </div>
                    <div style="border-top: 1px solid #e2e8f0; padding-top: 8px;">
                        <div style="font-size: 10.5px; text-transform: uppercase; font-weight: 700; color: #64748b;">Recorded By</div>
                        <div style="font-size: 12px; color: #334155; margin-top: 1px;">${escapeHtml(record.recordedBy || 'Finance Desk')}</div>
                    </div>
                    ${record.notes ? `
                        <div style="border-top: 1px solid #e2e8f0; padding-top: 8px;">
                            <div style="font-size: 10.5px; text-transform: uppercase; font-weight: 700; color: #64748b;">Notes / Remarks</div>
                            <div style="font-size: 12px; color: #475569; margin-top: 2px; line-height: 1.35; word-break: break-word;">${escapeHtml(record.notes)}</div>
                        </div>
                    ` : ''}
                </div>

                <div style="display: flex; flex-direction: column; gap: 8px;">
                    <button type="button" onclick="document.getElementById('financeViewerDirectUploadInput').click()" style="width: 100%; display: flex; align-items: center; justify-content: center; gap: 6px; padding: 7px 12px; font-size: 12px; font-weight: 600; background: #eff6ff; color: #1e40af; border: 1px solid #bfdbfe; border-radius: 8px; cursor: pointer;">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="17 8 12 3 7 8"></polyline><line x1="12" y1="3" x2="12" y2="15"></line></svg>
                        Replace Proof Image
                    </button>
                    <button type="button" onclick="handleFinanceViewerDeleteCurrent(${index})" style="width: 100%; display: flex; align-items: center; justify-content: center; gap: 6px; padding: 7px 12px; font-size: 12px; font-weight: 600; background: #fff1f2; color: #be123c; border: 1px solid #fecdd3; border-radius: 8px; cursor: pointer;">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
                        Delete This Payment Record
                    </button>
                </div>
            </div>
        `;
    }

    window.toggleFinanceViewerVerification = function(index) {
        const records = getFinanceLedgerRecords();
        if (records[index]) {
            records[index].isVerified = !(records[index].isVerified !== false);
            saveFinanceLedgerRecords(records);
            renderFinanceViewerMetadata(records[index], index);
        }
    };

    window.handleFinanceViewerDeleteCurrent = function(index) {
        if (confirm('Are you sure you want to delete this payment record and proof attachment?')) {
            const records = getFinanceLedgerRecords();
            records.splice(index, 1);
            saveFinanceLedgerRecords(records);
            if (records.length > 0) {
                openFinanceAttachmentViewer(0);
            } else {
                closeFinanceAttachmentViewer();
            }
        }
    };

    window.toggleFinanceViewerInfoPanel = function() {
        isFinanceViewerInfoOpen = !isFinanceViewerInfoOpen;
        const sidebar = document.getElementById('financeViewerDetailsSidebar');
        const btn = document.getElementById('financeViewerInfoBtn');
        if (sidebar) {
            sidebar.style.display = isFinanceViewerInfoOpen ? 'flex' : 'none';
        }
        if (btn) {
            btn.style.background = isFinanceViewerInfoOpen ? '#eff6ff' : '#f8fafc';
            btn.style.borderColor = isFinanceViewerInfoOpen ? '#93c5fd' : '#cbd5e1';
            btn.style.color = isFinanceViewerInfoOpen ? '#1e40af' : '#334155';
        }
    };

    window.navigateFinanceViewer = function(direction) {
        const records = getFinanceLedgerRecords();
        if (records.length <= 1) return;
        let newIndex = currentFinanceViewerRecordIndex + direction;
        if (newIndex < 0) newIndex = records.length - 1;
        if (newIndex >= records.length) newIndex = 0;
        openFinanceAttachmentViewer(newIndex);
    };

    window.handleFinanceViewerDirectUpload = function(event) {
        const file = event.target.files?.[0];
        if (!file) return;

        const isImg = file.type.startsWith('image/') || /\.(jpg|jpeg|png|webp|gif|bmp|svg|heic)$/i.test(file.name);
        const reader = new FileReader();
        reader.onload = function(evt) {
            const records = getFinanceLedgerRecords();
            if (records[currentFinanceViewerRecordIndex]) {
                records[currentFinanceViewerRecordIndex].fileName = file.name;
                records[currentFinanceViewerRecordIndex].fileSize = (file.size / 1024 > 1024) ? (file.size / (1024 * 1024)).toFixed(2) + ' MB' : (file.size / 1024).toFixed(1) + ' KB';
                records[currentFinanceViewerRecordIndex].fileDataUrl = evt.target.result;
                records[currentFinanceViewerRecordIndex].isImage = isImg;
                records[currentFinanceViewerRecordIndex].fileType = file.type;
                saveFinanceLedgerRecords(records);
                openFinanceAttachmentViewer(currentFinanceViewerRecordIndex);
                alert(`Proof photo "${file.name}" successfully updated!`);
            }
        };
        reader.readAsDataURL(file);
    };

    window.copyFinanceViewerRef = function() {
        if (!currentActiveViewerRecord) return;
        const textToCopy = `Reference: ${currentActiveViewerRecord.ref || 'OR-8043'} | Amount: ${formatFinanceMoney(currentActiveViewerRecord.amount || 0)} | Deal: ""`;
        navigator.clipboard.writeText(textToCopy).then(() => {
            const lbl = document.getElementById('financeViewerCopyLabel');
            if (lbl) {
                const old = lbl.innerText;
                lbl.innerText = 'Copied! ✓';
                setTimeout(() => { lbl.innerText = old; }, 2000);
            }
        }).catch(() => {
            alert(`Copied Reference: ${currentActiveViewerRecord.ref || 'OR-8043'}`);
        });
    };

    window.printFinanceViewerProof = function() {
        if (!currentActiveViewerRecord) return;
        const src = currentActiveViewerImgSrc || currentActiveViewerRecord.fileDataUrl || generateReceiptProofImage(currentActiveViewerRecord);
        win.document.write('<' + '!DOCTYPE html><html><head><' + 'title>' + escapeHtml(currentActiveViewerRecord.ref || 'Proof') + ' - Print<' + '/title><' + 'style>body { margin: 0; padding: 20px; display: flex; justify-content: center; align-items: center; background: #ffffff; font-family: sans-serif; } img { max-width: 100%; max-height: 95vh; object-fit: contain; }<' + '/style><' + '/head><body><img src="' + src + '" onload="window.print(); window.close();"><' + '/body><' + '/html>');
        win.document.close();
    };

    window.closeFinanceAttachmentViewer = function() {
        const modal = document.getElementById('financeAttachmentViewerModal');
        if (modal) modal.style.setProperty('display', 'none', 'important');
        currentActiveViewerRecord = null;
        currentActiveViewerImgSrc = null;
    };

    window.changeFinanceViewerZoom = function(delta) {
        currentFinanceViewerZoom = Math.max(0.4, Math.min(4.0, currentFinanceViewerZoom + delta));
        updateFinanceViewerTransform();
    };

    window.resetFinanceViewerZoom = function() {
        currentFinanceViewerZoom = 1;
        currentFinanceViewerRotation = 0;
        currentFinanceViewerFlipX = false;
        currentFinanceViewerFilter = false;
        currentFinanceViewerPanX = 0;
        currentFinanceViewerPanY = 0;
        updateFinanceViewerTransform();
    };

    window.rotateFinanceViewerImage = function() {
        currentFinanceViewerRotation = (currentFinanceViewerRotation + 90) % 360;
        updateFinanceViewerTransform();
    };

    window.flipFinanceViewerImage = function() {
        currentFinanceViewerFlipX = !currentFinanceViewerFlipX;
        updateFinanceViewerTransform();
    };

    window.toggleFinanceViewerFilter = function() {
        currentFinanceViewerFilter = !currentFinanceViewerFilter;
        const btn = document.getElementById('financeViewerFilterBtn');
        if (btn) {
            btn.style.background = currentFinanceViewerFilter ? '#eff6ff' : 'transparent';
            btn.style.color = currentFinanceViewerFilter ? '#1e40af' : '#475569';
        }
        updateFinanceViewerTransform();
    };

    function updateFinanceViewerTransform() {
        const img = document.getElementById('financeViewerImg');
        const zoomLabel = document.getElementById('financeViewerZoomLabel');
        if (zoomLabel) {
            zoomLabel.innerText = Math.round(currentFinanceViewerZoom * 100) + '%';
        }
        if (img) {
            const scaleX = currentFinanceViewerFlipX ? -currentFinanceViewerZoom : currentFinanceViewerZoom;
            img.style.transform = `translate(${currentFinanceViewerPanX}px, ${currentFinanceViewerPanY}px) scale(${scaleX}, ${currentFinanceViewerZoom}) rotate(${currentFinanceViewerRotation}deg)`;
            img.style.filter = currentFinanceViewerFilter ? 'contrast(150%) brightness(108%) saturate(115%)' : 'none';
        }
    }

    // Panning & Mouse Wheel Zoom
    window.startFinanceViewerPan = function(e) {
        if (e.button !== 0) return; // only left click
        isFinanceViewerPanning = true;
        financeViewerPanStartX = e.clientX - currentFinanceViewerPanX;
        financeViewerPanStartY = e.clientY - currentFinanceViewerPanY;
        const container = document.getElementById('financeViewerImgContainer');
        if (container) container.style.cursor = 'grabbing';
    };

    window.addEventListener('mousemove', function(e) {
        if (!isFinanceViewerPanning) return;
        currentFinanceViewerPanX = e.clientX - financeViewerPanStartX;
        currentFinanceViewerPanY = e.clientY - financeViewerPanStartY;
        updateFinanceViewerTransform();
    });

    window.addEventListener('mouseup', function() {
        if (isFinanceViewerPanning) {
            isFinanceViewerPanning = false;
            const container = document.getElementById('financeViewerImgContainer');
            if (container) container.style.cursor = 'grab';
        }
    });

    window.handleFinanceViewerWheel = function(e) {
        e.preventDefault();
        const delta = e.deltaY < 0 ? 0.15 : -0.15;
        changeFinanceViewerZoom(delta);
    };

    // Drag and Drop Upload onto Viewer Canvas
    window.handleFinanceViewerDragOver = function(e) {
        e.preventDefault();
        const dropOverlay = document.getElementById('financeViewerDropOverlay');
        if (dropOverlay) dropOverlay.style.display = 'flex';
    };

    window.handleFinanceViewerDragLeave = function(e) {
        e.preventDefault();
        const dropOverlay = document.getElementById('financeViewerDropOverlay');
        if (dropOverlay) dropOverlay.style.display = 'none';
    };

    window.handleFinanceViewerDrop = function(e) {
        e.preventDefault();
        const dropOverlay = document.getElementById('financeViewerDropOverlay');
        if (dropOverlay) dropOverlay.style.display = 'none';

        const file = e.dataTransfer?.files?.[0];
        if (!file) return;

        const isImg = file.type.startsWith('image/') || /\.(jpg|jpeg|png|webp|gif|bmp|svg|heic)$/i.test(file.name);
        const reader = new FileReader();
        reader.onload = function(evt) {
            const records = getFinanceLedgerRecords();
            if (records[currentFinanceViewerRecordIndex]) {
                records[currentFinanceViewerRecordIndex].fileName = file.name;
                records[currentFinanceViewerRecordIndex].fileSize = (file.size / 1024 > 1024) ? (file.size / (1024 * 1024)).toFixed(2) + ' MB' : (file.size / 1024).toFixed(1) + ' KB';
                records[currentFinanceViewerRecordIndex].fileDataUrl = evt.target.result;
                records[currentFinanceViewerRecordIndex].isImage = isImg;
                records[currentFinanceViewerRecordIndex].fileType = file.type;
                saveFinanceLedgerRecords(records);
                openFinanceAttachmentViewer(currentFinanceViewerRecordIndex);
                alert(`Proof photo "${file.name}" replaced successfully via Drag & Drop!`);
            }
        };
        reader.readAsDataURL(file);
    };

    window.downloadFinanceViewerFile = function() {
        if (!currentActiveViewerRecord) return;
        const fileName = currentActiveViewerRecord.fileName || `${currentActiveViewerRecord.ref || 'receipt'}.png`;
        const a = document.createElement('a');
        a.href = currentActiveViewerImgSrc || currentActiveViewerRecord.fileDataUrl || generateReceiptProofImage(currentActiveViewerRecord);
        a.download = fileName.endsWith('.png') || fileName.endsWith('.jpg') || fileName.endsWith('.jpeg') || fileName.endsWith('.pdf') ? fileName : `${fileName}.png`;
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
    };

    window.openFinanceViewerInNewTab = function() {
        if (!currentActiveViewerRecord) return;
        const src = currentActiveViewerImgSrc || currentActiveViewerRecord.fileDataUrl || generateReceiptProofImage(currentActiveViewerRecord);
        if (src) {
            const win = window.open();
            if (win) {
                win.document.write(`
                    <!DOCTYPE html>
                    <html>
                        <head><' + 'title>${escapeHtml(currentActiveViewerRecord.fileName || currentActiveViewerRecord.ref || 'Payment Proof')}<' + '/title></head>
                        <body style="margin:0; background:#0f172a; display:flex; justify-content:center; align-items:center; min-height:100vh; overflow:auto; padding:20px; box-sizing:border-box;">
                            <img src="${src}" style="max-width:100%; max-height:95vh; object-fit:contain; border-radius:6px; box-shadow:0 10px 30px rgba(0,0,0,0.5);">
                        </body>
                    </html>
                `);
            }
        }
    };

    // Extended Keyboard Shortcuts (Arrow Left/Right, Zoom +/-, Rotate R, Flip F, Clarity C, Info I, Print P, Download D, Escape)
    document.addEventListener('keydown', function(e) {
        const modal = document.getElementById('financeAttachmentViewerModal');
        if (modal && modal.style.display === 'flex') {
            if (e.key === 'Escape') {
                closeFinanceAttachmentViewer();
            } else if (e.key === 'ArrowLeft') {
                navigateFinanceViewer(-1);
            } else if (e.key === 'ArrowRight') {
                navigateFinanceViewer(1);
            } else if (e.key === 'r' || e.key === 'R') {
                rotateFinanceViewerImage();
            } else if (e.key === 'f' || e.key === 'F') {
                flipFinanceViewerImage();
            } else if (e.key === 'c' || e.key === 'C') {
                toggleFinanceViewerFilter();
            } else if (e.key === 'i' || e.key === 'I') {
                toggleFinanceViewerInfoPanel();
            } else if (e.key === 'p' || e.key === 'P') {
                if (e.ctrlKey || e.metaKey) return;
                printFinanceViewerProof();
            } else if (e.key === 'd' || e.key === 'D') {
                if (e.ctrlKey || e.metaKey) return;
                downloadFinanceViewerFile();
            } else if (e.key === '+' || e.key === '=') {
                changeFinanceViewerZoom(0.2);
            } else if (e.key === '-' || e.key === '_') {
                changeFinanceViewerZoom(-0.2);
            } else if (e.key === '0') {
                resetFinanceViewerZoom();
            }
        }
    });

    /* PREVIEW QUOTATION & PAYMENT NOTICE MODAL LOGIC */
    function openPreviewQuotationModal() {
        syncFinanceFromLineItems();
        const modal = document.getElementById('previewQuotationModal');
        if (!modal) return;

        const tbody = document.getElementById('previewQuotationTableBody');
        if (tbody && financeProposalItemsList && financeProposalItemsList.length > 0) {
            let total = 0;
            tbody.innerHTML = financeProposalItemsList.map((item, idx) => {
                const itemPrice = parseFloat(item.price) || 0;
                total += itemPrice;
                return `
                    <tr>
                        <td style="padding: 10px 12px; border-bottom: 1px solid #e2e8f0; font-size: 13px; color: #475569; text-align: center;">${idx + 1}</td>
                        <td style="padding: 10px 12px; border-bottom: 1px solid #e2e8f0; font-size: 13px; font-weight: 600; color: #0f172a;">
                            ${escapeHtml(item.name)}
                            <div style="font-size: 11px; color: #64748b; font-weight: normal; margin-top: 2px;">Code: ${escapeHtml(item.code || '')}</div>
                        </td>
                        <td style="padding: 10px 12px; border-bottom: 1px solid #e2e8f0; font-size: 13px; color: #475569; text-align: center;">1 Lot</td>
                        <td style="padding: 10px 12px; border-bottom: 1px solid #e2e8f0; font-size: 13px; font-weight: 700; color: #07162d; text-align: right;">${formatFinanceMoney(itemPrice)}</td>
                        <td style="padding: 10px 12px; border-bottom: 1px solid #e2e8f0; font-size: 13px; font-weight: 700; color: #1e4f95; text-align: right;">${formatFinanceMoney(itemPrice)}</td>
                    </tr>
                `;
            }).join('');

            const subtotalEl = document.getElementById('previewQuotationSubtotal');
            if (subtotalEl) subtotalEl.innerText = formatFinanceMoney(total);
            const totalEl = document.getElementById('previewQuotationGrandTotal');
            if (totalEl) totalEl.innerText = formatFinanceMoney(total);
        }

        modal.style.display = 'flex';
    }

    function closePreviewQuotationModal() {
        const modal = document.getElementById('previewQuotationModal');
        if (modal) modal.style.display = 'none';
    }

    function printQuotationDocument() {
        const printContent = document.getElementById('previewQuotationPaperContainer');
        if (!printContent) return;
        const win = window.open('', '', 'width=900,height=800');
        win.document.write('<' + '!DOCTYPE html><html><head><' + 'title>Official Quotation - ""<' + '/title><' + 'style>body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; color: #0f172a; margin: 20px; } table { width: 100%; border-collapse: collapse; margin-top: 15px; } th, td { padding: 10px 12px; border: 1px solid #cbd5e1; font-size: 13px; } th { background: #f8fafc; font-weight: 700; } .text-right { text-align: right; } .text-center { text-align: center; }<' + '/style><' + '/head><body>' + printContent.innerHTML + '<' + '/body><' + '/html>');
        win.document.close();
        win.focus();
        setTimeout(() => { win.print(); win.close(); }, 300);
    }

    function openPreviewPaymentNoticeModal() {
        syncFinanceFromLineItems();
        const modal = document.getElementById('previewPaymentNoticeModal');
        if (!modal) return;

        const tbody = document.getElementById('previewPaymentNoticeTableBody');
        const records = getFinanceLedgerRecords();

        if (tbody && financeProposalItemsList && financeProposalItemsList.length > 0) {
            let totalFeeSum = 0;
            let totalAllocatedSum = 0;
            let totalBalanceSum = 0;

            tbody.innerHTML = financeProposalItemsList.map((item, idx) => {
                const itemFee = parseFloat(item.price) || 0;
                let itemAllocated = 0;
                records.forEach(r => {
                    if (r.scope === 'item' && String(r.itemId) === String(item.id)) {
                        itemAllocated += parseFloat(r.amount) || 0;
                    }
                });
                const itemBalance = Math.max(0, itemFee - itemAllocated);

                totalFeeSum += itemFee;
                totalAllocatedSum += itemAllocated;
                totalBalanceSum += itemBalance;

                const statusPill = itemBalance <= 0 
                    ? `<span style="display: inline-block; padding: 2px 8px; border-radius: 9999px; font-size: 11px; font-weight: 700; background: #ecfdf5; color: #059669;">Fully Settled</span>`
                    : (itemAllocated > 0 
                        ? `<span style="display: inline-block; padding: 2px 8px; border-radius: 9999px; font-size: 11px; font-weight: 700; background: #fffbeb; color: #d97706;">Partially Paid</span>`
                        : `<span style="display: inline-block; padding: 2px 8px; border-radius: 9999px; font-size: 11px; font-weight: 700; background: #fef2f2; color: #dc2626;">Payment Pending</span>`);

                return `
                    <tr>
                        <td style="padding: 10px 12px; border-bottom: 1px solid #e2e8f0; font-size: 13px; color: #475569; text-align: center;">${idx + 1}</td>
                        <td style="padding: 10px 12px; border-bottom: 1px solid #e2e8f0; font-size: 13px; font-weight: 600; color: #0f172a;">
                            ${escapeHtml(item.name)}
                            <div style="font-size: 11px; color: #64748b; font-weight: normal; margin-top: 2px;">Ref: ${escapeHtml(item.code || '')}</div>
                        </td>
                        <td style="padding: 10px 12px; border-bottom: 1px solid #e2e8f0; font-size: 13px; font-weight: 600; color: #475569; text-align: right;">${formatFinanceMoney(itemFee)}</td>
                        <td style="padding: 10px 12px; border-bottom: 1px solid #e2e8f0; font-size: 13px; font-weight: 700; color: #047857; text-align: right;">${formatFinanceMoney(itemAllocated)}</td>
                        <td style="padding: 10px 12px; border-bottom: 1px solid #e2e8f0; font-size: 13px; font-weight: 700; color: #dc2626; text-align: right;">${formatFinanceMoney(itemBalance)}</td>
                        <td style="padding: 10px 12px; border-bottom: 1px solid #e2e8f0; font-size: 13px; text-align: center;">${statusPill}</td>
                    </tr>
                `;
            }).join('');

            const totalFeeEl = document.getElementById('previewPnTotalFee');
            if (totalFeeEl) totalFeeEl.innerText = formatFinanceMoney(totalFeeSum);

            const totalAllocatedEl = document.getElementById('previewPnTotalAllocated');
            if (totalAllocatedEl) totalAllocatedEl.innerText = formatFinanceMoney(totalAllocatedSum);

            const totalBalanceEl = document.getElementById('previewPnTotalBalance');
            if (totalBalanceEl) totalBalanceEl.innerText = formatFinanceMoney(totalBalanceSum);

            const dueAmtEl = document.getElementById('previewPnDueAmountBanner');
            if (dueAmtEl) dueAmtEl.innerText = formatFinanceMoney(totalBalanceSum);

            // Populate Attached Proofs & Pictures
            const attachBox = document.getElementById('previewPnAttachmentsBox');
            const attachList = document.getElementById('previewPnAttachmentsList');
            if (attachBox && attachList) {
                if (records.length === 0) {
                    attachBox.style.display = 'none';
                } else {
                    attachBox.style.display = 'block';
                    attachList.innerHTML = records.map((rec, idx) => {
                        const isImg = rec.isImage || (rec.fileDataUrl && rec.fileDataUrl.startsWith('data:image/')) || (rec.fileName && (/\.(jpg|jpeg|png|webp|gif|bmp|svg|heic)$/i.test(rec.fileName) || /image|screenshot|photo|img|chatgpt/i.test(rec.fileName))) || !rec.fileDataUrl;
                        const label = rec.fileName || `${rec.ref || 'Receipt'}.png`;
                        const thumb = (rec.fileDataUrl && rec.fileDataUrl.startsWith('data:image/')) 
                            ? `<img src="${rec.fileDataUrl}" style="width: 38px; height: 38px; object-fit: cover; border-radius: 6px; border: 1px solid #cbd5e1;">`
                            : `<div style="width: 38px; height: 38px; border-radius: 6px; background: #eff6ff; color: #2563eb; display: flex; align-items: center; justify-content: center; border: 1px solid #bfdbfe;"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><circle cx="8.5" cy="8.5" r="1.5"></circle><polyline points="21 15 16 10 5 21"></polyline></svg></div>`;

                        return `
                            <div onclick="openFinanceAttachmentViewer(${idx}, '${rec.ref ? escapeHtml(rec.ref) : ''}')" style="display: inline-flex; align-items: center; gap: 10px; background: #ffffff; border: 1px solid #cbd5e1; border-radius: 8px; padding: 6px 12px; cursor: pointer; transition: all 0.15s ease; box-shadow: 0 2px 4px rgba(0,0,0,0.03);" onmouseenter="this.style.borderColor='#2563eb'; this.style.background='#eff6ff';" onmouseleave="this.style.borderColor='#cbd5e1'; this.style.background='#ffffff';" title="Click to view full uploaded picture/proof">
                                ${thumb}
                                <div style="text-align: left;">
                                    <div style="font-size: 12px; font-weight: 700; color: #0f172a; max-width: 180px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">${escapeHtml(label)}</div>
                                    <div style="font-size: 11px; color: #2563eb; font-weight: 600;">${escapeHtml(rec.ref || 'OR-Receipt')} • ${formatFinanceMoney(rec.amount || 0)} (Click to View)</div>
                                </div>
                            </div>
                        `;
                    }).join('');
                }
            }
        }

        modal.style.display = 'flex';
    }

    function closePreviewPaymentNoticeModal() {
        const modal = document.getElementById('previewPaymentNoticeModal');
        if (modal) modal.style.display = 'none';
    }

    function printPaymentNoticeDocument() {
        const printContent = document.getElementById('previewPaymentNoticePaperContainer');
        if (!printContent) return;
        const win = window.open('', '', 'width=900,height=800');
        win.document.write('<' + '!DOCTYPE html><html><head><' + 'title>Payment Notice - ""<' + '/title><' + 'style>body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; color: #0f172a; margin: 20px; } table { width: 100%; border-collapse: collapse; margin-top: 15px; } th, td { padding: 10px 12px; border: 1px solid #cbd5e1; font-size: 13px; } th { background: #f8fafc; font-weight: 700; } .text-right { text-align: right; } .text-center { text-align: center; }<' + '/style><' + '/head><body>' + printContent.innerHTML + '<' + '/body><' + '/html>');
        win.document.close();
        win.focus();
        setTimeout(() => { win.print(); win.close(); }, 300);
    }

    /* =========================================================
       START MODULE & SERVICE MEMO JAVASCRIPT
       ========================================================= */
    const startCsrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || (document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || "");
    const startDealId = (window.DEAL_BOOTSTRAP?.dealId ?? 0);
    const startDealNumber = (window.DEAL_BOOTSTRAP?.dealNumber ?? "D-0");
    const startClientName = (window.DEAL_BOOTSTRAP?.clientName ?? "");
    const startLoggedUser = (window.DEAL_BOOTSTRAP?.currentUser ?? "Administrator");
    const startDefaultScopeItems = (window.DEAL_BOOTSTRAP?.dealProposalItems ?? []);

    let startBatches = (window.DEAL_BOOTSTRAP?.startBatches ?? []);
    let activeStartBatchId = null;
    let activeStartModalTab = 'overview';
    let currentDeclineAssignmentId = null;
    let currentReassignAssignmentId = null;

    function initStartTab() {
        if (!startBatches || startBatches.length === 0) {
            try {
                const cached = localStorage.getItem(`ordo_start_batches_${startDealId}`);
                if (cached) {
                    const parsed = JSON.parse(cached);
                    if (Array.isArray(parsed) && parsed.length > 0) {
                        startBatches = parsed;
                    }
                }
            } catch(e) {}
        }

        if (!startBatches) {
            startBatches = [];
        }

        renderStartBatches();
        renderStartStats();
    }

    function saveStartBatchesLocally() {
        try {
            localStorage.setItem(`ordo_start_batches_${startDealId}`, JSON.stringify(startBatches));
        } catch(e) {}
    }

    function getStartStatusBadge(status, batch) {
        let badgeClass = 'start-badge-draft';
        let label = status || 'Draft';
        let subtext = '';

        if (status === 'Draft') {
            badgeClass = 'start-badge-draft';
            subtext = 'Initial draft';
        } else if (status === 'Structuring') {
            badgeClass = 'start-badge-structuring';
            subtext = 'Assigning engagement roles';
        } else if (status === 'For Team Confirmation') {
            badgeClass = 'start-badge-confirmation';
            subtext = 'Awaiting assignee confirmations';
        } else if (status === 'Partially Acknowledged') {
            badgeClass = 'start-badge-partial';
            if (batch && batch.assignments) {
                const req = batch.assignments.filter(a => a.is_required);
                const ack = req.filter(a => a.status === 'Acknowledged');
                label = `Partially Acknowledged (${ack.length}/${req.length})`;
            }
            subtext = 'Pending team acknowledgements';
        } else if (status === 'Needs Reassignment') {
            badgeClass = 'start-badge-reassignment';
            subtext = 'Assignee declined role';
        } else if (status === 'Team Confirmed') {
            badgeClass = 'start-badge-confirmed';
            subtext = 'All required assignees confirmed';
        } else if (status === 'For Final Review') {
            badgeClass = 'start-badge-review';
            subtext = 'Ready for authorized issuance';
        } else if (status === 'Activated' || status === 'Completed') {
            badgeClass = 'start-badge-activated';
            label = 'Completed';
            subtext = 'All required services satisfied';
        } else if (status === 'Returned') {
            badgeClass = 'start-badge-returned';
            subtext = 'Returned for correction';
        } else if (status === 'Cancelled') {
            badgeClass = 'start-badge-cancelled';
            subtext = 'Activation cancelled';
        }

        return `
            <div>
                <span class="start-badge ${badgeClass}"><span class="start-dot"></span>${escapeStartHtml(label)}</span>
            </div>
        `;
    }

    function escapeStartHtml(str) {
        if (!str) return '';
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function renderStartStats() {
        let total = startBatches.length;
        let pending = 0;
        let confirmed = 0;
        let activated = 0;
        let ready = 0;

        startBatches.forEach(b => {
            const confirmedAssigns = (b.assignments || []).filter(a => ['Confirmed', 'Acknowledged', 'Completed'].includes(a.status)).length;
            if (confirmedAssigns > 0) {
                confirmed += confirmedAssigns;
            } else if (['Team Confirmed', 'Activated', 'Completed', 'Issued', 'Active'].includes(b.status)) {
                confirmed += (b.assignments && b.assignments.length > 0) ? b.assignments.length : 1;
            }

            if (['For Team Confirmation', 'Partially Acknowledged', 'Needs Reassignment', 'Structuring', 'Pending'].includes(b.status)) {
                pending++;
            } else if (['Draft', 'Ready', 'For Final Review', 'Team Confirmed'].includes(b.status)) {
                ready++;
            }

            if (['Activated', 'Completed', 'Issued', 'Active'].includes(b.status)) {
                activated++;
            }
        });

        const statTotal = document.getElementById('startStatTotalBatches') || document.getElementById('startStatTotal');
        const statActivated = document.getElementById('startStatActivated');
        const statConfirmed = document.getElementById('startStatConfirmed') || document.getElementById('startStatPending');
        const statReady = document.getElementById('startStatReady');

        if (statTotal) statTotal.textContent = total;
        if (statActivated) statActivated.textContent = activated;
        if (statConfirmed) statConfirmed.textContent = confirmed;
        if (statReady) statReady.textContent = ready;
    }

    function renderStartBatches() {
        const tableBody = document.getElementById('startBatchesTableBody');
        const emptyState = document.getElementById('startBatchesEmptyState') || document.getElementById('startEmptyState');
        const tableCard = document.getElementById('startBatchesTableWrap') || document.getElementById('startTableCard');

        if (!startBatches || startBatches.length === 0) {
            if (tableCard) tableCard.style.display = 'none';
            if (emptyState) emptyState.style.display = 'flex';
            return;
        }

        if (tableCard) tableCard.style.display = 'block';
        if (emptyState) emptyState.style.display = 'none';

        if (!tableBody) return;

        tableBody.innerHTML = startBatches.map(batch => {
            const memoStatusHtml = getStartMemoColumnHtml(batch);
            const scopeHtml = getStartScopeColumnHtml(batch);
            const statusHtml = getStartStatusBadge(batch.status, batch);

            return `
                <tr>
                    <td style="white-space: nowrap;">
                        <div class="start-code-title">${escapeStartHtml(batch.start_code || 'ST-2026-001')}</div>
                        <div class="start-code-subtitle">${escapeStartHtml(batch.batch_name || 'START Batch 001 — Core Tax & Advisory')}</div>
                    </td>
                    <td>
                        ${scopeHtml}
                    </td>
                    <td>
                        ${statusHtml}
                    </td>
                    <td style="white-space: nowrap;">
                        ${memoStatusHtml}
                    </td>
                    <td style="white-space: nowrap;">
                        <div class="start-date-main">${escapeStartHtml(batch.opened_date || 'Sep 17, 2026')}</div>
                        <div class="start-date-sub">${escapeStartHtml(batch.opened_time || '01:03 PM')}</div>
                    </td>
                    <td class="start-action-cell" style="white-space: nowrap; display: flex; align-items: center; gap: 6px;">
                        <button type="button" class="start-btn-details-soft" onclick="openStartBatchDetails(${batch.id})">
                            View Details
                        </button>
                        <button type="button" class="btn-consultation-more" title="Delete START Batch" style="color: #ef4444; border-color: #fecaca; background: #fff; width: 28px; height: 28px;" onclick="handleDeleteStartBatch(event, ${batch.id})">
                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <polyline points="3 6 5 6 21 6"></polyline>
                                <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                            </svg>
                        </button>
                    </td>
                </tr>
            `;
        }).join('');
    }

    async function handleDeleteStartBatch(event, batchId) {
        if (event) event.stopPropagation();
        if (!confirm('Are you sure you want to delete this START batch?')) return;

        try {
            await fetch(`/deals/${startDealId}/start/batches/${batchId}`, {
                method: 'DELETE',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': startCsrfToken,
                    'Accept': 'application/json'
                }
            });
        } catch(e) {}

        startBatches = startBatches.filter(b => b.id != batchId);
        saveStartBatchesLocally();
        renderStartBatches();
        renderStartStats();
    }

    function getStartScopeColumnHtml(batch) {
        const items = batch.activated_items || [];
        if (items.length === 1) {
            const itemName = (typeof items[0] === 'object' && items[0] !== null) 
                ? (items[0].name || items[0].title || 'Scope Item') 
                : items[0];
            return `
                <div class="start-scope-cell">
                    <div class="start-scope-name">${escapeStartHtml(itemName)}</div>
                    <div class="start-scope-sub">See Service Memo</div>
                </div>
            `;
        }
        if (items.length > 1) {
            return `
                <div class="start-scope-cell">
                    <div class="start-scope-name">Multiple Services (${items.length} items)</div>
                    <div class="start-scope-sub">See Service Memo</div>
                </div>
            `;
        }
        return `
            <div class="start-scope-cell">
                <div class="start-scope-name">${escapeStartHtml(batch.scope || 'Core Services Scope')}</div>
                <div class="start-scope-sub">See Service Memo</div>
            </div>
        `;
    }

    function getStartMemoColumnHtml(batch) {
        return `
            <button type="button" class="start-memo-btn-soft" onclick="openStartBatchMemoTab(${batch.id})">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                    <polyline points="14 2 14 8 20 8"></polyline>
                    <line x1="16" y1="13" x2="8" y2="13"></line>
                    <line x1="16" y1="17" x2="8" y2="17"></line>
                    <line x1="10" y1="9" x2="8" y2="9"></line>
                </svg>
                View Service Memo
            </button>
        `;
    }

    function openStartMemoViewer(batchId) {
        let batch = startBatches.find(b => b.id == batchId);
        if (!batch && startBatches.length > 0) {
            batch = startBatches[0];
        }
        if (!batch) return;

        activeStartBatchId = batch.id;
        populateStartBatchDetails(batch);

        const badge = document.getElementById('startMemoViewerCodeBadge');
        if (badge) {
            const memoData = batch.service_memo_data || {};
            const memoRef = batch.service_memo_ref || memoData.memo_ref || `SM-${new Date().getFullYear()}-${String(batch.id).padStart(3, '0')}`;
            badge.textContent = memoRef;
        }

        const modal = document.getElementById('startServiceMemoViewerModal');
        if (modal) modal.style.display = 'flex';
    }

    function closeStartMemoViewer() {
        const modal = document.getElementById('startServiceMemoViewerModal');
        if (modal) modal.style.display = 'none';
    }

    function openStartBatchMemoTab(batchId) {
        openStartMemoViewer(batchId);
    }

    function printStartForm() {
        const formContent = document.getElementById('startFormPrintContainer');
        if (!formContent) return;

        let iframe = document.getElementById('startFormPrintIframe');
        if (iframe) {
            try { document.body.removeChild(iframe); } catch(e) {}
        }

        iframe = document.createElement('iframe');
        iframe.id = 'startFormPrintIframe';
        iframe.style.position = 'fixed';
        iframe.style.right = '0';
        iframe.style.bottom = '0';
        iframe.style.width = '0';
        iframe.style.height = '0';
        iframe.style.border = '0';
        document.body.appendChild(iframe);

        const dealCode = (window.DEAL_BOOTSTRAP?.dealCode ?? "START-FORM");
        const doc = iframe.contentWindow.document;
        const pageStyles = Array.from(document.querySelectorAll('style')).map(s => s.innerHTML).join('\n');
        doc.open();
        doc.write('<' + '!DOCTYPE html><html><head><meta charset="utf-8"><' + 'title>START Form - ' + dealCode + '<' + '/title><' + 'style>/* @page */ { size: portrait; margin: 10mm 12mm; } * { box-sizing: border-box; -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; color-adjust: exact !important; } body { margin: 0; padding: 0; background: #ffffff; color: #0f172a; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif; font-size: 11px; line-height: 1.4; } ' + pageStyles + '<' + '/style><' + '/head><body><div class="start-paper">' + formContent.innerHTML + '</div><' + '/body><' + '/html>');
        doc.close();

        setTimeout(() => {
            iframe.contentWindow.focus();
            iframe.contentWindow.print();
        }, 300);
    }

    function downloadStartPdf() {
        printStartForm();
    }

    function openActiveBatchDetails() {
        if (!startBatches || startBatches.length === 0) {
            openCreateStartBatchModal();
            return;
        }
        let batch = startBatches.find(b => b.id == activeStartBatchId) || startBatches[0];
        if (batch) {
            openStartBatchDetails(batch.id);
        }
    }

    function openActiveBatchServiceMemo() {
        if (!startBatches || startBatches.length === 0) {
            openCreateStartBatchModal();
            return;
        }
        let batch = startBatches.find(b => b.id == activeStartBatchId) || startBatches[0];
        if (batch) {
            openStartMemoViewer(batch.id);
        }
    }

    function openNewServiceMemoModal() {
        if (!startBatches || startBatches.length === 0) {
            openCreateStartBatchModal();
            return;
        }
        let batch = startBatches.find(b => b.id == activeStartBatchId) || startBatches[0];
        if (batch) {
            if (batch.status === 'Activated' || batch.memo_status === 'Issued') {
                openStartMemoAmendmentModal();
            } else {
                openStartMemoViewer(batch.id);
            }
        }
    }

    function printStartMemo() {
        const memoContent = document.getElementById('startMemoPrintContainer');
        if (!memoContent) return;

        let iframe = document.getElementById('startMemoPrintIframe');
        if (iframe) {
            try { document.body.removeChild(iframe); } catch(e) {}
        }

        iframe = document.createElement('iframe');
        iframe.id = 'startMemoPrintIframe';
        iframe.style.position = 'fixed';
        iframe.style.right = '0';
        iframe.style.bottom = '0';
        iframe.style.width = '0';
        iframe.style.height = '0';
        iframe.style.border = '0';
        document.body.appendChild(iframe);

        const doc = iframe.contentWindow.document;
        const pageStyles = Array.from(document.querySelectorAll('style')).map(s => s.innerHTML).join('\n');
        const memoTitle = document.getElementById('smCellMemoRef')?.textContent || 'SM-2026-001';
        doc.open();
        doc.write('<' + '!DOCTYPE html><html><head><meta charset="utf-8"><' + 'title>Service Memo - ' + memoTitle + '<' + '/title><' + 'style>/* @page */ { size: portrait; margin: 10mm 12mm; } * { box-sizing: border-box; -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; color-adjust: exact !important; } body { margin: 0; padding: 0; background: #ffffff; color: #002b66; font-family: Georgia, "Times New Roman", Times, serif; font-size: 11px; line-height: 1.4; } ' + pageStyles + '<' + '/style><' + '/head><body>' + memoContent.innerHTML + '<' + '/body><' + '/html>');
        doc.close();

        setTimeout(() => {
            iframe.contentWindow.focus();
            iframe.contentWindow.print();
        }, 250);
    }

    function openStartBatchDetails(batchId) {
        const batch = startBatches.find(b => b.id == batchId);
        if (!batch) return;

        activeStartBatchId = batch.id;

        const modal = document.getElementById('startBatchDetailsModal');
        if (modal) modal.style.display = 'flex';

        populateStartBatchDetails(batch);
        switchStartModalTab(activeStartModalTab || 'overview');
    }

    function closeStartBatchDetailsModal() {
        const modal = document.getElementById('startBatchDetailsModal');
        if (modal) modal.style.display = 'none';
        activeStartBatchId = null;
    }

    function switchStartModalTab(tabKey) {
        if (tabKey === 'memo') {
            openStartMemoViewer(activeStartBatchId);
            return;
        }

        activeStartModalTab = tabKey;
        const tabs = ['overview', 'scope', 'assignments', 'history'];

        document.querySelectorAll('.start-modal-tab-btn').forEach(btn => {
            if (btn.getAttribute('data-start-tab') === tabKey) {
                btn.classList.add('active');
            } else {
                btn.classList.remove('active');
            }
        });

        const map = {
            overview: 'startModalTabOverview',
            scope: 'startModalTabScope',
            assignments: 'startModalTabAssignments',
            history: 'startModalTabHistory'
        };

        tabs.forEach(k => {
            const el = document.getElementById(map[k]);
            if (el) {
                el.style.display = (k === tabKey) ? 'block' : 'none';
            }
        });

        const batch = startBatches.find(b => b.id == activeStartBatchId);
        if (batch) {
            populateStartBatchDetails(batch);
        }
    }

    function toggleStartNotesCollapse() {
        const panel = document.getElementById('startScopeNotesPanel');
        const chevron = document.getElementById('startNotesChevron');
        if (panel) {
            const isHidden = panel.style.display === 'none' || panel.style.display === '';
            panel.style.display = isHidden ? 'block' : 'none';
            if (chevron) {
                chevron.style.transform = isHidden ? 'rotate(90deg)' : 'none';
            }
        }
    }

    function populateStartBatchDetails(batch) {
        if (!batch) return;

        // Code & Status Badges
        const codeEl = document.getElementById('startModalBatchCode');
        if (codeEl) codeEl.textContent = batch.start_code || 'ST-2026-001';

        const statusBadgeEl = document.getElementById('startModalBatchStatusBadge');
        if (statusBadgeEl) {
            statusBadgeEl.innerHTML = `
                <span class="start-modal-status-dot"></span>
                <span id="startModalBatchStatusText">${escapeStartHtml(batch.status || 'Completed')}</span>
            `;
            statusBadgeEl.style.background = '#eff6ff';
            statusBadgeEl.style.color = '#2563eb';
            statusBadgeEl.style.borderColor = '#bfdbfe';
        }

        const isTeamConfirmed = batch.status === 'Team Confirmed';
        const isForFinalReview = batch.status === 'For Final Review';
        const isIssued = batch.status === 'Activated' || batch.status === 'Completed' || Boolean(batch.service_memo_data?.issued_at);
        const reqAssignments = (batch.assignments || []).filter(a => a.is_required);
        const ackAssignments = reqAssignments.filter(a => a.status === 'Acknowledged');
        const ackPercent = reqAssignments.length > 0 ? Math.round((ackAssignments.length / reqAssignments.length) * 100) : 100;

        // Top Status Banner Card
        const bannerStatusEl = document.getElementById('startModalBannerStatus');
        if (bannerStatusEl) bannerStatusEl.textContent = batch.status || 'Draft';

        const bannerDetailsEl = document.getElementById('startModalBannerDetails');
        if (bannerDetailsEl) {
            if (isIssued) {
                bannerDetailsEl.textContent = `Service Memo ${batch.service_memo_ref || 'SM-2026-001'} issued. Document is immutable.`;
            } else if (isForFinalReview) {
                bannerDetailsEl.textContent = `Service Memo status: Ready to Issue. Service Memo generated and awaiting authorized issuance.`;
            } else if (isTeamConfirmed) {
                bannerDetailsEl.textContent = `Service Memo status: Ready for Final Review. All required team members have confirmed.`;
            } else if (batch.status === 'Needs Reassignment') {
                bannerDetailsEl.textContent = `Service Memo status: Waiting for Team Confirmation. Required team role declined — reassignment needed.`;
            } else if (batch.status === 'For Team Confirmation' || batch.status === 'Partially Acknowledged') {
                bannerDetailsEl.textContent = `Service Memo status: Waiting for Team Confirmation (${ackAssignments.length} of ${reqAssignments.length} required assignees acknowledged).`;
            } else if (batch.status === 'Returned') {
                bannerDetailsEl.textContent = `START batch returned for correction. Re-confirm team to proceed to Service Memo Final Review.`;
            } else {
                bannerDetailsEl.textContent = `Service Memo status: Waiting for Team Confirmation. Complete team confirmation to proceed.`;
            }
        }

        const bannerActionsEl = document.getElementById('startModalBannerActions');
        if (bannerActionsEl) {
            if (isIssued) {
                bannerActionsEl.innerHTML = `
                    <button type="button" class="btn-start-memo-primary" onclick="openStartBatchMemoTab(${batch.id})">View Service Memo</button>
                    <button type="button" class="btn-start-amend-outline" onclick="openStartMemoAmendmentModal()">Create Amendment</button>
                `;
            } else if (isForFinalReview) {
                bannerActionsEl.innerHTML = `
                    <button type="button" class="btn-start-memo-primary" onclick="handleIssueServiceMemo(${batch.id})">Issue Service Memo</button>
                    <button type="button" class="start-btn-secondary" onclick="openStartBatchMemoTab(${batch.id})">View Draft Memo</button>
                `;
            } else if (isTeamConfirmed) {
                bannerActionsEl.innerHTML = `
                    <button type="button" class="btn-start-memo-primary" onclick="handleSubmitFinalReview(${batch.id})">Final Review</button>
                    <button type="button" class="start-btn-secondary" onclick="openStartBatchMemoTab(${batch.id})">View Draft Memo</button>
                `;
            } else {
                bannerActionsEl.innerHTML = `
                    <button type="button" class="start-btn-secondary" style="opacity: 0.65; cursor: not-allowed;" disabled title="Waiting for Team Confirmation">
                        Waiting for Team Confirmation
                    </button>
                `;
            }
        }

        // Overview Tab Info
        const infoCode = document.getElementById('startInfoCode');
        if (infoCode) infoCode.textContent = batch.start_code || 'ST-2026-001';

        const infoDate = document.getElementById('startInfoDate');
        if (infoDate) infoDate.textContent = `${batch.opened_date || 'Sep 17, 2026'} at ${batch.opened_time || '03:19 PM'}`;

        const infoStatus = document.getElementById('startInfoStatus');
        if (infoStatus) infoStatus.textContent = batch.status || 'Draft';

        const infoMemoStatus = document.getElementById('startInfoMemoStatus');
        if (infoMemoStatus) {
            if (isIssued) {
                infoMemoStatus.textContent = 'Issued';
                infoMemoStatus.style.color = '#15803d';
            } else if (isForFinalReview) {
                infoMemoStatus.textContent = 'Ready to Issue';
                infoMemoStatus.style.color = '#4338ca';
            } else if (isTeamConfirmed) {
                infoMemoStatus.textContent = 'Ready for Final Review';
                infoMemoStatus.style.color = '#2563eb';
            } else {
                infoMemoStatus.textContent = 'Waiting for Team Confirmation';
                infoMemoStatus.style.color = '#d97706';
            }
        }

        const infoScopeCount = document.getElementById('startInfoScopeCount');
        if (infoScopeCount) {
            const count = (batch.activated_items || []).length || 3;
            infoScopeCount.textContent = `${count} Services Included`;
        }

        const infoCreatedBy = document.getElementById('startInfoCreatedBy');
        if (infoCreatedBy) infoCreatedBy.textContent = batch.created_by || startLoggedUser || 'Administrator';

        const infoAuthorizedBy = document.getElementById('startInfoAuthorizedBy');
        if (infoAuthorizedBy) infoAuthorizedBy.textContent = batch.authorized_by || 'Pending Authorization';

        const infoNotes = document.getElementById('startInfoNotes');
        if (infoNotes) infoNotes.textContent = batch.batch_name ? `${batch.batch_name} — Progressive activation batch.` : (batch.notes || 'Standard advisory and compliance filing deliverables.');

        // Transition Action Bar
        const statusNote = document.getElementById('startTransitionStatusNote');
        const actionBtns = document.getElementById('startTransitionActionBtns');
        if (statusNote && actionBtns) {
            let noteText = '';
            let btnHtml = '';

            if (batch.status === 'Draft' || batch.status === 'Structuring') {
                noteText = `Current Status: <strong>Structuring</strong> • Service Memo: <strong style="color: #d97706;">Waiting for Team Confirmation</strong>`;
                btnHtml = `
                    <button type="button" class="start-btn-primary" onclick="handleSendForTeamConfirmation(${batch.id})">
                        Send for Team Confirmation
                    </button>
                `;
            } else if (batch.status === 'For Team Confirmation' || batch.status === 'Partially Acknowledged') {
                noteText = `Current Status: <strong>${escapeStartHtml(batch.status)}</strong> • Service Memo: <strong style="color: #d97706;">Waiting for Team Confirmation</strong> (${ackAssignments.length} of ${reqAssignments.length} acknowledged).`;
                btnHtml = `
                    <button type="button" class="start-btn-secondary" onclick="switchStartModalTab('assignments')">
                        Review Assignments
                    </button>
                `;
            } else if (batch.status === 'Needs Reassignment') {
                noteText = `Current Status: <strong style="color: #b91c1c;">Needs Reassignment</strong> • Service Memo: <strong style="color: #b91c1c;">Waiting for Team Confirmation</strong> (Assignee declined role).`;
                btnHtml = `
                    <button type="button" class="start-btn-primary" style="background: #dc2626; border-color: #dc2626;" onclick="switchStartModalTab('assignments')">
                        Reassign Role
                    </button>
                `;
            } else if (batch.status === 'Team Confirmed') {
                noteText = `Current Status: <strong style="color: #15803d;">Team Confirmed</strong> • Service Memo: <strong style="color: #15803d;">Ready for Final Review</strong>.`;
                btnHtml = `
                    <button type="button" class="start-btn-primary" onclick="handleSubmitFinalReview(${batch.id})">
                        Final Review
                    </button>
                `;
            } else if (batch.status === 'For Final Review') {
                noteText = `Current Status: <strong style="color: #4338ca;">For Final Review</strong> • Service Memo: <strong style="color: #4338ca;">Ready to Issue</strong>.`;
                btnHtml = `
                    <button type="button" class="start-btn-secondary" style="color: #dc2626;" onclick="openStartReturnModal()">
                        Return for Correction
                    </button>
                    <button type="button" class="start-btn-primary" onclick="handleIssueServiceMemo(${batch.id})">
                        Issue Service Memo
                    </button>
                `;
            } else if (batch.status === 'Activated' || batch.status === 'Completed') {
                noteText = `Current Status: <strong style="color: #15803d;">Activated / Completed</strong> • Service Memo ${escapeStartHtml(batch.service_memo_ref || 'SM-2026-001')} issued. Document is immutable.`;
                btnHtml = `
                    <button type="button" class="start-btn-secondary" onclick="openStartMemoAmendmentModal()">
                        + Create Amendment
                    </button>
                    <button type="button" class="start-btn-primary" onclick="switchStartModalTab('memo')">
                        View Service Memo
                    </button>
                `;
            } else if (batch.status === 'Returned') {
                noteText = `Current Status: <strong style="color: #991b1b;">Returned</strong> • Service Memo: <strong style="color: #991b1b;">Waiting for Team Confirmation</strong> (Returned for correction).`;
                btnHtml = `
                    <button type="button" class="start-btn-primary" onclick="handleSendForTeamConfirmation(${batch.id})">
                        Re-Submit for Confirmation
                    </button>
                `;
            } else if (batch.status === 'Cancelled') {
                noteText = `Current Status: <strong>Cancelled</strong> • This START batch activation has been cancelled.`;
                btnHtml = ``;
            }

            if (!['Activated', 'Completed', 'Cancelled'].includes(batch.status)) {
                btnHtml += `
                    <button type="button" class="start-btn-secondary" style="color: #64748b;" onclick="openStartCancelModal()">
                        Cancel Batch
                    </button>
                `;
            }

            statusNote.innerHTML = noteText;
            actionBtns.innerHTML = btnHtml;
        }

        // Scope Items Tab
        const scopeContainer = document.getElementById('startScopeItemsList');
        if (scopeContainer) {
            const items = batch.activated_items || [];
            if (items.length === 0) {
                scopeContainer.innerHTML = `<div style="text-align: center; padding: 24px; color: #94a3b8; font-size: 13px;">No scope items defined for this batch.</div>`;
            } else {
                scopeContainer.innerHTML = items.map((it, idx) => {
                    const name = typeof it === 'string' ? it : (it.name || 'Service Deliverable');
                    const div = typeof it === 'object' && it.division ? it.division : 'Regular Division';
                    return `
                        <div style="display: flex; align-items: center; justify-content: space-between; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 12px 16px;">
                            <div style="display: flex; align-items: center; gap: 12px;">
                                <span style="display: inline-flex; align-items: center; justify-content: center; width: 22px; height: 22px; border-radius: 50%; background: #ecfdf5; color: #059669; border: 1px solid #d1fae5; flex-shrink: 0;">
                                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                        <polyline points="20 6 9 17 4 12"></polyline>
                                    </svg>
                                </span>
                                <div>
                                    <div style="font-size: 13.5px; font-weight: 700; color: #0f172a;">${escapeStartHtml(name)}</div>
                                    <div style="font-size: 12px; color: #64748b; margin-top: 2px;">Deliverable #${idx + 1} &bull; ${escapeStartHtml(div)}</div>
                                </div>
                            </div>
                            <span class="start-badge ${isIssued ? 'start-badge-activated' : 'start-badge-confirmed'}">
                                <span class="start-dot"></span>${isIssued ? 'Active / Authorized' : 'Ready to Activate'}
                            </span>
                        </div>
                    `;
                }).join('');
            }
        }

        // Assignments Tab
        const ackProgressText = document.getElementById('startAckProgressText');
        const ackProgressBar = document.getElementById('startAckProgressBar');
        const totalReq = reqAssignments.length > 0 ? reqAssignments.length : 3;
        const totalAck = ackAssignments.length > 0 ? ackAssignments.length : 3;
        const calcPercent = reqAssignments.length > 0 ? Math.round((ackAssignments.length / reqAssignments.length) * 100) : 100;
        
        if (ackProgressText) ackProgressText.textContent = `${totalAck} of ${totalReq} Acknowledged (${calcPercent}%)`;
        if (ackProgressBar) {
            ackProgressBar.style.width = `${calcPercent}%`;
            ackProgressBar.style.background = '#1d68e1';
        }

        const assignTableBody = document.getElementById('startAssignmentsTableBody');
        if (assignTableBody) {
            const assignments = batch.assignments || [];
            const isEditable = !['Activated', 'Completed', 'Cancelled'].includes(batch.status);

            if (assignments.length === 0) {
                assignTableBody.innerHTML = `<tr><td colspan="6" style="text-align: center; color: #94a3b8; padding: 24px;">No role assignments defined yet. Click "+ Add Role Assignment" above.</td></tr>`;
            } else {
                assignTableBody.innerHTML = assignments.map(a => {
                    let normalizedStatus = 'Pending';
                    const rawStatus = (a.status || '').trim();
                    const rawResponse = (a.response || '').trim();

                    if (['Acknowledged', 'Accepted', 'Confirmed', 'Completed'].includes(rawStatus) || rawResponse === 'Accepted') {
                        normalizedStatus = 'Accepted';
                    } else if (['Declined', 'Cannot Accept', 'Rejected'].includes(rawStatus) || rawResponse === 'Declined') {
                        normalizedStatus = 'Declined';
                    } else {
                        normalizedStatus = 'Pending';
                    }

                    let stBadge = `
                        <span class="start-assign-status-pill" style="background: #eff6ff; border-color: #bfdbfe; color: #1d4ed8;">
                            <span class="start-assign-status-dot" style="background: #2563eb;"></span>
                            <span>Acknowledged</span>
                        </span>
                    `;
                    if (normalizedStatus === 'Pending') {
                        stBadge = `
                            <span class="start-assign-status-pill" style="background: #f1f5f9; border-color: #e2e8f0; color: #475569;">
                                <span class="start-assign-status-dot" style="background: #64748b;"></span>
                                <span>Pending</span>
                            </span>
                        `;
                    } else if (normalizedStatus === 'Declined') {
                        stBadge = `
                            <span class="start-assign-status-pill" style="background: #fef2f2; border-color: #fecaca; color: #dc2626;">
                                <span class="start-assign-status-dot" style="background: #dc2626;"></span>
                                <span>Cannot Accept</span>
                            </span>
                        `;
                    }

                    let dateFormatted = '<span style="color: #94a3b8;">-</span>';
                    if (a.acknowledged_at) {
                        if (typeof a.acknowledged_at === 'string' && a.acknowledged_at.includes('T')) {
                            try {
                                const d = new Date(a.acknowledged_at);
                                if (!isNaN(d.getTime())) {
                                    dateFormatted = d.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' }) + ' ' + d.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' });
                                } else {
                                    dateFormatted = escapeStartHtml(a.acknowledged_at);
                                }
                            } catch(e) {
                                dateFormatted = escapeStartHtml(a.acknowledged_at);
                            }
                        } else {
                            dateFormatted = escapeStartHtml(a.acknowledged_at);
                        }
                    }

                    let notesDisplay = '<span style="color: #94a3b8;">-</span>';
                    if (normalizedStatus === 'Declined') {
                        const reasonText = a.decline_reason || a.notes || 'work load';
                        notesDisplay = `<span style="color: #dc2626; font-weight: 600;">Declined: ${escapeStartHtml(reasonText)}</span>`;
                    } else if (normalizedStatus === 'Accepted') {
                        notesDisplay = a.notes ? escapeStartHtml(a.notes) : 'Accepted';
                    } else {
                        notesDisplay = a.notes ? escapeStartHtml(a.notes) : '<span style="color: #94a3b8;">-</span>';
                    }

                    let actionButtons = '';
                    if (normalizedStatus === 'Pending') {
                        actionButtons = `
                            <button type="button" class="btn-start-add-role" style="padding: 4px 10px; font-size: 12px;" onclick="handleAcknowledgeAssignment(${a.id})">
                                Accept
                            </button>
                            <button type="button" class="btn-start-add-role" style="padding: 4px 10px; font-size: 12px; color: #dc2626; border-color: #dc2626;" onclick="openStartDeclineModal(${a.id})">
                                Decline
                            </button>
                        `;
                    } else if (normalizedStatus === 'Accepted') {
                        actionButtons = `
                            <button type="button" class="btn-start-add-role" style="padding: 4px 10px; font-size: 12px;" onclick="openStartMemoViewer(activeStartBatchId)">
                                View Assignment
                            </button>
                        `;
                    } else if (normalizedStatus === 'Declined') {
                        actionButtons = `
                            <button type="button" class="btn-start-add-role" style="padding: 4px 10px; font-size: 12px;" onclick="openStartReassignModal(${a.id}, '${escapeStartHtml(a.role)}')">
                                Reassign
                            </button>
                        `;
                    }

                    return `
                        <tr>
                            <td style="white-space: nowrap;">
                                <div style="font-weight: 700; color: #0f172a; font-size: 13.5px;">${escapeStartHtml(a.role)}</div>
                                <div style="font-size: 11.5px; color: #64748b; font-weight: 500; margin-top: 2px;">
                                    ${a.is_required ? 'Required Role' : 'Optional Support'}
                                </div>
                            </td>
                            <td style="white-space: nowrap;">
                                <div style="font-size: 13.5px; font-weight: 600; color: #0f172a;">${escapeStartHtml(a.assignee || a.assigned_to || 'Unassigned')}</div>
                            </td>
                            <td style="white-space: nowrap;">
                                ${stBadge}
                            </td>
                            <td style="white-space: nowrap;">
                                <div style="font-size: 13px; color: #0f172a; font-weight: 500;">
                                    ${dateFormatted}
                                </div>
                            </td>
                            <td>
                                <div style="font-size: 13px; color: #64748b;">
                                    ${notesDisplay}
                                </div>
                            </td>
                            <td style="white-space: nowrap;">
                                <div style="display: inline-flex; gap: 5px;">
                                    ${isEditable || normalizedStatus === 'Accepted' ? actionButtons : '<span style="color: #94a3b8; font-size: 12.5px; font-weight: 500;">Immutable</span>'}
                                </div>
                            </td>
                        </tr>
                    `;
                }).join('');
            }
        }

        // Service Memo Tab
        const memoBanner = document.getElementById('startMemoReadinessBanner');
        const memoPaper = document.getElementById('startMemoPaperContainer');
        const memoWatermark = document.getElementById('startMemoWatermark');
        const memoSeal = document.getElementById('startMemoIssuedSeal');

        const memoData = batch.service_memo_data || {};
        const memoRef = batch.service_memo_ref || memoData.memo_ref || `SM-${new Date().getFullYear()}-${String(batch.id).padStart(3, '0')}`;

        if (memoPaper) {
            memoPaper.style.display = 'block';
            if (memoWatermark) memoWatermark.style.display = isIssued ? 'none' : 'block';
            if (memoSeal) memoSeal.style.display = isIssued ? 'block' : 'none';

            // Metadata Header Fields
            const elHeaderTo = document.getElementById('smHeaderTo');
            if (elHeaderTo) {
                const clientComp = batch.client_name || startClientName || 'Client Account';
                elHeaderTo.textContent = clientComp;
            }

                const elHeaderFrom = document.getElementById('smHeaderFrom');
                if (elHeaderFrom) {
                    elHeaderFrom.textContent = 'John Kelly & Company / Management Team';
                }

                const elHeaderDate = document.getElementById('smHeaderDate');
                if (elHeaderDate) {
                    elHeaderDate.textContent = isIssued ? (memoData.issued_at || batch.opened_date || new Date().toLocaleDateString('en-US', { month: 'long', day: 'numeric', year: 'numeric' })) : new Date().toLocaleDateString('en-US', { month: 'long', day: 'numeric', year: 'numeric' });
                }

                const elHeaderSubject = document.getElementById('smHeaderSubject');
                if (elHeaderSubject) {
                    elHeaderSubject.textContent = 'SERVICE TASK ACTIVATION AND ROUTING TRACKER (START)';
                }

                // Table 1: Memo Details
                const elMemoRef = document.getElementById('smCellMemoRef');
                if (elMemoRef) elMemoRef.textContent = memoRef;

                const elStartRef = document.getElementById('smCellStartRef');
                if (elStartRef) elStartRef.textContent = batch.start_code || 'ST-2026-001';

                const elClient = document.getElementById('smCellClient');
                if (elClient) elClient.textContent = batch.client_name || startClientName || 'Client Account';

                const elContact = document.getElementById('smCellContact');
                if (elContact) elContact.textContent = (window.DEAL_BOOTSTRAP?.clientName ?? "") || 'Authorized Client Representative';

                const elDealSource = document.getElementById('smCellDealSource');
                if (elDealSource) elDealSource.textContent = startDealNumber || ('CONDEAL-' + startDealId);

                const elEngagementType = document.getElementById('smCellEngagementType');
                if (elEngagementType) elEngagementType.textContent = batch.engagement_type || (window.DEAL_BOOTSTRAP?.engagementType ?? "") || 'Regular Retainer Engagement';

                const items = batch.activated_items || [];

                const elActivated = document.getElementById('smCellActivatedItems');
                if (elActivated) {
                    if (items.length === 0) {
                        elActivated.textContent = 'Standard Engagement Scope Items';
                    } else if (items.length > 2) {
                        elActivated.innerHTML = `<strong>Multiple Services (${items.length} items)</strong> &bull; ` + items.map(it => escapeStartHtml(typeof it === 'string' ? it : (it.name || 'Service'))).join(', ');
                    } else {
                        elActivated.innerHTML = items.map(it => `<span class="start-scope-tag" style="background: #eff6ff; color: #1e40af; border: 1px solid #bfdbfe; padding: 2px 8px; border-radius: 4px; font-weight: 600;">✓ ${escapeStartHtml(typeof it === 'string' ? it : (it.name || 'Service'))}</span>`).join(' ');
                    }
                }

                const elSpecialRow = document.getElementById('smCellSpecialInstructionsRow');
                if (elSpecialRow) {
                    elSpecialRow.textContent = batch.notes || (window.DEAL_BOOTSTRAP?.scopeOfWork ?? "") || 'Commence operational service onboarding and statutory compliance execution in accordance with service agreement.';
                }

                // DIVISION LOGIC: Categorize items into Regular vs Project
                const regularItems = [];
                const projectItems = [];

                items.forEach(it => {
                    const itemName = typeof it === 'string' ? it : (it.name || 'Service');
                    const div = typeof it === 'object' && it.division ? it.division.toLowerCase() : '';
                    const isProject = div.includes('project') || itemName.toLowerCase().includes('project') || itemName.toLowerCase().includes('development') || itemName.toLowerCase().includes('setup') || itemName.toLowerCase().includes('implementation');
                    
                    if (isProject) {
                        projectItems.push(it);
                    } else {
                        regularItems.push(it);
                    }
                });

                // Fallback if no specific split detected
                if (regularItems.length === 0 && projectItems.length === 0 && items.length > 0) {
                    regularItems.push(...items);
                } else if (items.length === 0) {
                    regularItems.push({ name: 'Financial Advisory', division: 'Advisory' });
                    regularItems.push({ name: 'Tax Compliance & Filing', division: 'Tax' });
                    regularItems.push({ name: 'Corporate Bookkeeping', division: 'Accounting' });
                }

                // 2. Regular Division
                const regSection = document.getElementById('smRegularDivisionSection');
                const regTableBody = document.getElementById('smRegularDivisionTableBody');
                if (regSection && regTableBody) {
                    if (regularItems.length > 0) {
                        regSection.style.display = 'block';
                        regTableBody.innerHTML = regularItems.map((it, idx) => {
                            const name = typeof it === 'string' ? it : (it.name || 'Regular Service');
                            const assigned = batch.assignments?.[idx % (batch.assignments.length || 1)]?.assignee || 'Assigned Staff';
                            return `
                                <tr>
                                    <td style="font-weight: 700; color: #002b66;">${escapeStartHtml(name)}</td>
                                    <td>${escapeStartHtml(typeof it === 'object' && it.scope ? it.scope : 'Standard compliance execution & regulatory deliverables')}</td>
                                    <td>Monthly / Ongoing</td>
                                    <td>${escapeStartHtml(assigned)}</td>
                                </tr>
                            `;
                        }).join('');
                    } else {
                        regSection.style.display = 'none'; // Completely hidden!
                    }
                }

                // 3. Project Division
                const projSection = document.getElementById('smProjectDivisionSection');
                const projTable = document.getElementById('smProjectDivisionTable');
                const projTableBody = document.getElementById('smProjectDivisionTableBody');
                const projNoEng = document.getElementById('smProjectDivisionNoEngagement');
                if (projSection) {
                    projSection.style.display = 'block';
                    if (projectItems.length > 0) {
                        if (projTable) projTable.style.display = 'table';
                        if (projNoEng) projNoEng.style.display = 'none';
                        if (projTableBody) {
                            projTableBody.innerHTML = projectItems.map((it, idx) => {
                                const name = typeof it === 'string' ? it : (it.name || 'Project Service');
                                const assigned = batch.assignments?.[idx % (batch.assignments.length || 1)]?.assignee || 'Project Team';
                                return `
                                    <tr>
                                        <td style="font-weight: 700; color: #002b66;">${escapeStartHtml(name)}</td>
                                        <td>${escapeStartHtml(typeof it === 'object' && it.scope ? it.scope : 'Project milestone deliverables & technical engagement')}</td>
                                        <td>Per Proposal Timeline</td>
                                        <td>${escapeStartHtml(assigned)}</td>
                                    </tr>
                                `;
                            }).join('');
                        }
                    } else {
                        if (projTable) projTable.style.display = 'none';
                        if (projNoEng) projNoEng.style.display = 'block';
                    }
                }

                // 4. Assignment Confirmation Summary
                const smAssignBody = document.getElementById('smAssignmentsSummaryTableBody');
                if (smAssignBody) {
                    const assignments = batch.assignments || [];
                    if (assignments.length === 0) {
                        smAssignBody.innerHTML = `
                            <tr>
                                <td style="font-weight: 600; color: #002b66;">Lead Consultant</td>
                                <td>${escapeStartHtml(startLoggedUser)}</td>
                                <td style="color: #15803d; font-weight: 700;">Confirmed</td>
                                <td>${escapeStartHtml(batch.opened_date || new Date().toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' }))}</td>
                            </tr>
                        `;
                    } else {
                        smAssignBody.innerHTML = assignments.map(a => `
                            <tr>
                                <td style="font-weight: 600; color: #002b66;">${escapeStartHtml(a.role)}</td>
                                <td>${escapeStartHtml(a.assignee || a.assigned_to || 'Unassigned')}</td>
                                <td style="color: ${a.status === 'Acknowledged' ? '#15803d' : '#d97706'}; font-weight: 700;">
                                    ${a.status === 'Acknowledged' ? 'Confirmed' : escapeStartHtml(a.status)}
                                </td>
                                <td>${escapeStartHtml(a.acknowledged_at || batch.opened_date || '-')}</td>
                            </tr>
                        `).join('');
                    }
                }

                // 5. Authorized & Issued
                const signAuth = document.getElementById('smSignAuthorizedBy');
                if (signAuth) signAuth.textContent = batch.authorized_by || 'John Kelly Abalde';

                const signIssuedBy = document.getElementById('smSignIssuedBy');
                if (signIssuedBy) signIssuedBy.textContent = isIssued ? (memoData.issued_by || startLoggedUser) : 'John Kelly Abalde';

                const signIssuedAt = document.getElementById('smSignIssuedAt');
                if (signIssuedAt) signIssuedAt.textContent = isIssued ? `Issued: ${memoData.issued_at || batch.opened_date || new Date().toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })}` : 'MANAGEMENT TEAM';

                // 6. Special Instructions Box
                const specialBox = document.getElementById('smSpecialInstructionsBox');
                if (specialBox) {
                    specialBox.textContent = batch.notes || (window.DEAL_BOOTSTRAP?.scopeOfWork ?? "") || 'Commence operational service onboarding and statutory compliance execution in accordance with approved proposal terms.';
                }

                // Revisions Section
                const revSection = document.getElementById('startMemoRevisionsSection');
                const revList = document.getElementById('startMemoRevisionsList');
                const revisions = batch.service_memo_revisions || [];
                if (revSection && revList) {
                    if (revisions.length > 0) {
                        revSection.style.display = 'block';
                        revList.innerHTML = revisions.map(rev => `
                            <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; padding: 10px 14px; font-size: 12.5px;">
                                <div style="display: flex; justify-content: space-between; margin-bottom: 3px;">
                                    <strong style="color: #07162d;">Revision ${escapeStartHtml(rev.revision_number)}</strong>
                                    <span style="color: #64748b;">${escapeStartHtml(rev.created_at || '')} by ${escapeStartHtml(rev.created_by || 'User')}</span>
                                </div>
                                <div style="color: #334155;">${escapeStartHtml(rev.amendment_reason)}</div>
                            </div>
                        `).join('');
                    } else {
                        revSection.style.display = 'none';
                    }
                }
            }

        // History Tab
        const historyList = document.getElementById('startHistoryTimelineList');
        if (historyList) {
            const logs = batch.history_logs || [];
            if (logs.length === 0) {
                historyList.innerHTML = `<div style="text-align: center; padding: 24px; color: #94a3b8; font-size: 13px;">No history transitions recorded yet.</div>`;
            } else {
                let displayLogs = [...logs];
                if (displayLogs.length > 1 && displayLogs[0].event === 'Created') {
                    displayLogs.reverse();
                }
                historyList.innerHTML = displayLogs.map(log => `
                    <div class="start-history-item">
                        <div class="start-history-dot"></div>
                        <div class="start-history-content">
                            <div class="start-history-event-row">
                                <span class="start-history-event-name">${escapeStartHtml(log.event || log.title || 'State Updated')}</span>
                                <span class="start-history-timestamp">${escapeStartHtml(log.timestamp || '')}</span>
                            </div>
                            <div class="start-history-details">${escapeStartHtml(log.details || log.notes || '')}</div>
                            <div class="start-history-actor">Actor: <strong>${escapeStartHtml(log.actor || 'System')}</strong></div>
                        </div>
                    </div>
                `).join('');
            }
        }
    }

    /* CREATE PROGRESSIVE BATCH MODAL */
    function openCreateStartBatchModal() {
        const modal = document.getElementById('createStartBatchModal');
        if (!modal) return;

        // Calculate next unique batch number
        let maxBatchNum = 0;
        startBatches.forEach(b => {
            if (b.start_code) {
                const match = b.start_code.match(/ST-\d+-(\d+)/i);
                if (match) {
                    const num = parseInt(match[1], 10);
                    if (num > maxBatchNum) maxBatchNum = num;
                }
            }
            if (b.id && typeof b.id === 'number' && b.id < 100000) {
                if (b.id > maxBatchNum) maxBatchNum = b.id;
            }
        });
        const nextNum = Math.max(maxBatchNum + 1, startBatches.length + 1);
        const defaultCode = `ST-${new Date().getFullYear()}-${String(nextNum).padStart(3, '0')}`;
        
        const codeInput = document.getElementById('csbCodeInput');
        const titleInput = document.getElementById('csbTitleInput');
        const checklist = document.getElementById('csbServicesChecklist');

        if (codeInput) codeInput.value = defaultCode;
        if (titleInput) titleInput.value = `START Batch ${String(nextNum).padStart(3, '0')} - Core Activation`;

        // Gather all deal proposal services
        let allServices = [];
        if (typeof dealLineItems !== 'undefined' && Array.isArray(dealLineItems) && dealLineItems.length > 0) {
            allServices = dealLineItems.map(i => ({ name: i.name, division: i.route || 'Regular' }));
        } else if (Array.isArray(startDefaultScopeItems) && startDefaultScopeItems.length > 0) {
            allServices = startDefaultScopeItems.map(i => typeof i === 'string' ? { name: i, division: 'Regular' } : { name: i.name || 'Service Deliverable', division: i.route || 'Regular' });
        }

        // Map already allocated items across existing batches
        const allocatedMap = {};
        startBatches.forEach(b => {
            const batchLabel = b.start_code || `Batch #${b.id}`;
            (b.activated_items || []).forEach(it => {
                const itName = (typeof it === 'object' && it !== null) ? (it.name || it.title) : it;
                if (itName) {
                    allocatedMap[itName.trim().toLowerCase()] = batchLabel;
                }
            });
        });

        if (checklist) {
            if (allServices.length === 0) {
                checklist.innerHTML = `
                    <div style="padding: 12px; text-align: center; color: #64748b; font-size: 13px;">
                        No services found in deal proposal. Please add line items under Services & Pricing first.
                    </div>
                `;
            } else {
                let unallocatedCount = 0;
                let html = allServices.map((item) => {
                    const name = item.name;
                    const allocatedTo = allocatedMap[name.trim().toLowerCase()];
                    if (allocatedTo) {
                        return `
                            <label style="display: flex; align-items: center; justify-content: space-between; font-size: 12.5px; color: #94a3b8; background: #f1f5f9; border: 1px solid #e2e8f0; border-radius: 6px; padding: 8px 12px; cursor: not-allowed; opacity: 0.75;" title="Already assigned to ${escapeStartHtml(allocatedTo)}">
                                <div style="display: flex; align-items: center; gap: 8px;">
                                    <input type="checkbox" disabled style="accent-color: #94a3b8; width: 16px; height: 16px;">
                                    <span style="font-weight: 500; text-decoration: line-through;">${escapeStartHtml(name)}</span>
                                </div>
                                <span style="font-size: 11px; background: #e2e8f0; color: #475569; padding: 2px 7px; border-radius: 4px; font-weight: 600;">Already in ${escapeStartHtml(allocatedTo)}</span>
                            </label>
                        `;
                    } else {
                        unallocatedCount++;
                        return `
                            <label style="display: flex; align-items: center; gap: 8px; font-size: 13px; color: #1e293b; background: #ffffff; border: 1px solid #cbd5e1; border-radius: 6px; padding: 8px 12px; cursor: pointer;">
                                <input type="checkbox" name="start_scope_items[]" value="${escapeStartHtml(name)}" data-division="${escapeStartHtml(item.division || 'Regular')}" checked style="accent-color: #2563eb; width: 16px; height: 16px;">
                                <span style="font-weight: 500; color: #0f172a;">${escapeStartHtml(name)}</span>
                            </label>
                        `;
                    }
                }).join('');

                if (unallocatedCount === 0) {
                    html = `
                        <div style="background: #fef2f2; border: 1px solid #fecaca; border-radius: 6px; padding: 10px 12px; margin-bottom: 8px; color: #991b1b; font-size: 12px; line-height: 1.4;">
                            <strong>Notice:</strong> All ${allServices.length} service(s) from this deal are already allocated to existing START batches. A service cannot be duplicated in multiple batches.
                        </div>
                    ` + html;
                }

                checklist.innerHTML = html;
            }
        }

        modal.style.display = 'flex';
        document.body.style.overflow = 'hidden';
    }

    function closeCreateStartBatchModal() {
        const modal = document.getElementById('createStartBatchModal');
        if (modal) modal.style.display = 'none';
        document.body.style.overflow = '';
    }

    async function handleSaveNewStartBatch(event) {
        event.preventDefault();

        const code = document.getElementById('csbCodeInput')?.value || `ST-${new Date().getFullYear()}-001`;
        const title = document.getElementById('csbTitleInput')?.value || 'START Batch';
        const selectedCheckboxes = document.querySelectorAll('input[name="start_scope_items[]"]:checked');
        const activatedItems = Array.from(selectedCheckboxes).map(cb => ({
            name: cb.value,
            division: cb.getAttribute('data-division') || 'Regular'
        }));

        if (activatedItems.length === 0) {
            alert('Please select at least one available service item to include in this START batch.');
            return;
        }

        // Duplicate check against existing batches
        const allocatedMap = {};
        startBatches.forEach(b => {
            const batchLabel = b.start_code || `Batch #${b.id}`;
            (b.activated_items || []).forEach(it => {
                const itName = (typeof it === 'object' && it !== null) ? (it.name || it.title) : it;
                if (itName) allocatedMap[itName.trim().toLowerCase()] = batchLabel;
            });
        });

        for (const it of activatedItems) {
            const existingBatch = allocatedMap[it.name.trim().toLowerCase()];
            if (existingBatch) {
                alert(`The service "${it.name}" is already assigned to ${existingBatch}. Services cannot be duplicated across multiple START batches.`);
                return;
            }
        }

        const consultant = document.getElementById('csbAssigneeConsultant')?.value || startLoggedUser;
        const associate = document.getElementById('csbAssigneeAssociate')?.value || 'Assigned Staff';
        const notes = document.getElementById('csbNotesInput')?.value || '';

        try {
            const res = await fetch(`/deals/${startDealId}/start/batches`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': startCsrfToken,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    start_code: code,
                    batch_name: title,
                    activated_items: activatedItems,
                    notes: notes,
                    assignments: [
                        { role: 'Lead Consultant', assigned_to: consultant, is_required: true },
                        { role: 'Lead Associate', assigned_to: associate, is_required: true }
                    ]
                })
            });

            const data = await res.json();
            if (data.success && data.batch) {
                startBatches.unshift(data.batch);
                saveStartBatchesLocally();
                renderStartBatches();
                renderStartStats();
                closeCreateStartBatchModal();
                openStartBatchDetails(data.batch.id);
            } else {
                alert(data.message || 'Could not create START batch.');
            }
        } catch(e) {
            const newBatch = {
                id: Date.now(),
                start_code: code,
                batch_name: title,
                status: 'Structuring',
                opened_date: new Date().toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' }),
                opened_time: new Date().toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' }),
                activated_items: activatedItems,
                service_memo_ref: null,
                service_memo_data: null,
                service_memo_revisions: [],
                history_logs: [
                    {
                        event: 'Created',
                        details: `START Batch ${code} created with ${activatedItems.length} services`,
                        actor: startLoggedUser,
                        timestamp: new Date().toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })
                    }
                ],
                authorized_by: null,
                created_by: startLoggedUser,
                assignments: [
                    {
                        id: Date.now() + 1,
                        role: 'Lead Consultant',
                        assignee: consultant,
                        status: 'Pending',
                        is_required: true,
                        acknowledged_at: null,
                        notes: ''
                    },
                    {
                        id: Date.now() + 2,
                        role: 'Lead Associate',
                        assignee: associate,
                        status: 'Pending',
                        is_required: true,
                        acknowledged_at: null,
                        notes: ''
                    },
                    ...activatedItems.map((it, idx) => ({
                        id: Date.now() + 10 + idx,
                        role: it.name,
                        assignee: 'Unassigned',
                        status: 'Pending',
                        is_required: true,
                        acknowledged_at: null,
                        notes: ''
                    }))
                ]
            };
            startBatches.unshift(newBatch);
            saveStartBatchesLocally();
            renderStartBatches();
            renderStartStats();
            closeCreateStartBatchModal();
            openStartBatchDetails(newBatch.id);
        }
    }

    /* ASSIGNMENT ACTIONS */
    async function handleAcknowledgeAssignment(assignId) {
        try {
            const res = await fetch(`/deals/${startDealId}/start/assignments/${assignId}/acknowledge`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': startCsrfToken,
                    'Accept': 'application/json'
                }
            });
            const data = await res.json();
            if (data.success && data.batch) {
                updateBatchInState(data.batch);
            }
        } catch(e) {
            const batch = startBatches.find(b => b.id == activeStartBatchId);
            if (batch && batch.assignments) {
                const a = batch.assignments.find(item => item.id == assignId);
                if (a) {
                    a.status = 'Acknowledged';
                    a.acknowledged_at = new Date().toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' }) + ' ' + new Date().toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' });
                    recomputeLocalBatchStatus(batch, `Assignment acknowledged by ${a.assignee} for role ${a.role}`);
                }
            }
        }
    }

    function openStartDeclineModal(assignId) {
        currentDeclineAssignmentId = assignId;
        const select = document.getElementById('startDeclineReasonSelect');
        const otherWrap = document.getElementById('startDeclineOtherReasonWrapper');
        const otherInput = document.getElementById('startDeclineOtherReasonInput');
        if (select) select.value = '';
        if (otherWrap) otherWrap.style.display = 'none';
        if (otherInput) {
            otherInput.value = '';
            otherInput.required = false;
        }
        const modal = document.getElementById('startDeclineModal');
        if (modal) modal.style.display = 'flex';
    }

    function closeStartDeclineModal() {
        const modal = document.getElementById('startDeclineModal');
        if (modal) modal.style.display = 'none';
        currentDeclineAssignmentId = null;
    }

    function handleDeclineReasonSelectChange(selectEl) {
        const otherWrap = document.getElementById('startDeclineOtherReasonWrapper');
        const otherInput = document.getElementById('startDeclineOtherReasonInput');
        if (!otherWrap || !otherInput) return;

        if (selectEl.value === 'Other') {
            otherWrap.style.display = 'block';
            otherInput.required = true;
            otherInput.focus();
        } else {
            otherWrap.style.display = 'none';
            otherInput.required = false;
            otherInput.value = '';
        }
    }

    async function handleConfirmDeclineAssignment(event) {
        event.preventDefault();
        const selectVal = document.getElementById('startDeclineReasonSelect')?.value || '';
        const otherVal = document.getElementById('startDeclineOtherReasonInput')?.value?.trim() || '';

        if (!selectVal) {
            alert('Please select a reason for declining.');
            return;
        }

        let finalReason = selectVal;
        if (selectVal === 'Other') {
            if (!otherVal) {
                alert('Please provide an explanation for Other.');
                return;
            }
            finalReason = `Other: ${otherVal}`;
        }

        try {
            const res = await fetch(`/deals/${startDealId}/start/assignments/${currentDeclineAssignmentId}/decline`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': startCsrfToken,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ decline_reason: finalReason })
            });
            const data = await res.json();
            if (data.success && data.batch) {
                updateBatchInState(data.batch);
                closeStartDeclineModal();
            }
        } catch(e) {
            const batch = startBatches.find(b => b.id == activeStartBatchId);
            if (batch && batch.assignments) {
                const a = batch.assignments.find(item => item.id == currentDeclineAssignmentId);
                if (a) {
                    a.status = 'Cannot Accept';
                    a.decline_reason = finalReason;
                    recomputeLocalBatchStatus(batch, `Assignment declined by ${a.assignee}. Reason: ${finalReason}`);
                }
            }
            closeStartDeclineModal();
        }
    }

    function openStartReassignModal(assignId, roleName) {
        currentReassignAssignmentId = assignId;
        const roleInput = document.getElementById('startReassignRoleLabel');
        if (roleInput) roleInput.value = roleName || 'Role Assignment';
        const modal = document.getElementById('startReassignModal');
        if (modal) modal.style.display = 'flex';
    }

    function closeStartReassignModal() {
        const modal = document.getElementById('startReassignModal');
        if (modal) modal.style.display = 'none';
        currentReassignAssignmentId = null;
    }

    async function handleConfirmReassign(event) {
        event.preventDefault();
        const newAssignee = document.getElementById('startReassignNewUserSelect')?.value;
        const notes = document.getElementById('startReassignNotesInput')?.value;

        try {
            const res = await fetch(`/deals/${startDealId}/start/assignments/${currentReassignAssignmentId}/reassign`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': startCsrfToken,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ new_assignee: newAssignee, assignee: newAssignee, notes: notes })
            });
            const data = await res.json();
            if (data.success && data.batch) {
                updateBatchInState(data.batch);
                closeStartReassignModal();
            }
        } catch(e) {
            const batch = startBatches.find(b => b.id == activeStartBatchId);
            if (batch && batch.assignments) {
                const a = batch.assignments.find(item => item.id == currentReassignAssignmentId);
                if (a) {
                    const oldAssignee = a.assignee;
                    a.assignee = newAssignee;
                    a.status = 'Pending';
                    a.acknowledged_at = null;
                    a.decline_reason = null;
                    if (notes) a.notes = notes;
                    recomputeLocalBatchStatus(batch, `Role ${a.role} reassigned from ${oldAssignee} to ${newAssignee}`);
                }
            }
            closeStartReassignModal();
        }
    }

    function openStartAddAssignmentModal() {
        const modal = document.getElementById('startAddAssignmentModal');
        if (modal) modal.style.display = 'flex';
    }

    function closeStartAddAssignmentModal() {
        const modal = document.getElementById('startAddAssignmentModal');
        if (modal) modal.style.display = 'none';
    }

    async function handleSaveNewAssignment(event) {
        event.preventDefault();
        const role = document.getElementById('startNewAssignRoleInput')?.value;
        const assignee = document.getElementById('startNewAssigneeSelect')?.value;
        const isRequired = document.getElementById('startNewAssignRequired')?.checked;
        const notes = document.getElementById('startNewAssignNotes')?.value;

        try {
            const res = await fetch(`/deals/${startDealId}/start/batches/${activeStartBatchId}/assignments`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': startCsrfToken,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    role: role,
                    assignee: assignee,
                    is_required: isRequired,
                    notes: notes
                })
            });
            const data = await res.json();
            if (data.success && data.batch) {
                updateBatchInState(data.batch);
                closeStartAddAssignmentModal();
            }
        } catch(e) {
            const batch = startBatches.find(b => b.id == activeStartBatchId);
            if (batch) {
                if (!batch.assignments) batch.assignments = [];
                batch.assignments.push({
                    id: Date.now(),
                    role: role,
                    assignee: assignee,
                    status: 'Pending',
                    is_required: isRequired,
                    notes: notes,
                    acknowledged_at: null
                });
                recomputeLocalBatchStatus(batch, `Role assignment ${role} added for ${assignee}`);
            }
            closeStartAddAssignmentModal();
        }
    }

    /* WORKFLOW TRANSITIONS */
    async function handleSendForTeamConfirmation(batchId) {
        try {
            const res = await fetch(`/deals/${startDealId}/start/batches/${batchId}/send-confirmation`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': startCsrfToken,
                    'Accept': 'application/json'
                }
            });
            const data = await res.json();
            if (data.success && data.batch) {
                updateBatchInState(data.batch);
            }
        } catch(e) {
            const batch = startBatches.find(b => b.id == batchId);
            if (batch) {
                recomputeLocalBatchStatus(batch, 'Sent for team confirmation');
            }
        }
    }

    async function handleSubmitFinalReview(batchId) {
        const batch = startBatches.find(b => b.id == batchId);
        if (batch && batch.status !== 'Team Confirmed') {
            alert('Service Memo cannot proceed to Final Review until Team Confirmation is complete.');
            return;
        }

        try {
            const res = await fetch(`/deals/${startDealId}/start/batches/${batchId}/submit-review`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': startCsrfToken,
                    'Accept': 'application/json'
                }
            });
            const data = await res.json();
            if (res.ok && data.success && data.batch) {
                updateBatchInState(data.batch);
            } else if (data.message) {
                alert(data.message);
            }
        } catch(e) {
            if (batch && batch.status === 'Team Confirmed') {
                batch.status = 'For Final Review';
                if (!batch.history_logs) batch.history_logs = [];
                batch.history_logs.unshift({
                    event: 'Final Review',
                    details: 'START batch submitted for final approval and Service Memo review',
                    actor: startLoggedUser,
                    timestamp: new Date().toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })
                });
                updateBatchInState(batch);
            }
        }
    }

    async function handleIssueServiceMemo(batchId) {
        const batch = startBatches.find(b => b.id == batchId);
        if (batch && !['For Final Review', 'Team Confirmed'].includes(batch.status)) {
            alert('Service Memo must undergo Final Review before it can be issued.');
            return;
        }

        if (!confirm('Are you sure you want to issue the official Service Memo? Once issued, the document becomes immutable and downstream engagement operations are activated.')) {
            return;
        }

        try {
            const res = await fetch(`/deals/${startDealId}/start/batches/${batchId}/issue-memo`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': startCsrfToken,
                    'Accept': 'application/json'
                }
            });
            const data = await res.json();
            if (res.ok && data.success && data.batch) {
                updateBatchInState(data.batch);
                switchStartModalTab('memo');
            } else if (data.message) {
                alert(data.message);
            }
        } catch(e) {
            if (batch && (batch.status === 'For Final Review' || batch.status === 'Team Confirmed')) {
                batch.status = 'Activated';
                batch.authorized_by = 'Managing Director';
                const memoRef = `SM-${new Date().getFullYear()}-${String(batch.id).padStart(3, '0')}`;
                batch.service_memo_ref = memoRef;
                batch.service_memo_data = {
                    memo_ref: memoRef,
                    issued_at: new Date().toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' }),
                    issued_by: startLoggedUser,
                    authorized_by: 'Managing Director'
                };
                if (!batch.history_logs) batch.history_logs = [];
                batch.history_logs.unshift({
                    event: 'Service Memo Issued',
                    details: `Service Memo ${memoRef} issued. Engagement activated.`,
                    actor: startLoggedUser,
                    timestamp: new Date().toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })
                });
                updateBatchInState(batch);
                switchStartModalTab('memo');
            }
        }
    }

    function openStartMemoAmendmentModal() {
        const modal = document.getElementById('startMemoAmendmentModal');
        if (modal) modal.style.display = 'flex';
    }

    function closeStartMemoAmendmentModal() {
        const modal = document.getElementById('startMemoAmendmentModal');
        if (modal) modal.style.display = 'none';
    }

    async function handleConfirmMemoAmendment(event) {
        event.preventDefault();
        const reason = document.getElementById('startAmendmentReasonInput')?.value;
        const instructions = document.getElementById('startAmendmentInstructionsInput')?.value;

        try {
            const res = await fetch(`/deals/${startDealId}/start/batches/${activeStartBatchId}/memo-amendment`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': startCsrfToken,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    amendment_reason: reason,
                    special_instructions: instructions
                })
            });
            const data = await res.json();
            if (data.success && data.batch) {
                updateBatchInState(data.batch);
                closeStartMemoAmendmentModal();
                switchStartModalTab('memo');
            }
        } catch(e) {
            const batch = startBatches.find(b => b.id == activeStartBatchId);
            if (batch) {
                if (!batch.service_memo_revisions) batch.service_memo_revisions = [];
                const revNum = batch.service_memo_revisions.length + 1;
                batch.service_memo_revisions.push({
                    revision_number: revNum,
                    amendment_reason: reason,
                    special_instructions: instructions,
                    created_by: startLoggedUser,
                    created_at: new Date().toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })
                });
                if (!batch.history_logs) batch.history_logs = [];
                batch.history_logs.unshift({
                    event: 'Amendment Created',
                    details: `Service Memo Amendment Rev ${revNum}: ${reason}`,
                    actor: startLoggedUser,
                    timestamp: new Date().toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })
                });
                updateBatchInState(batch);
                closeStartMemoAmendmentModal();
                switchStartModalTab('memo');
            }
        }
    }

    function openStartReturnModal() {
        const modal = document.getElementById('startReturnModal');
        if (modal) modal.style.display = 'flex';
    }

    function closeStartReturnModal() {
        const modal = document.getElementById('startReturnModal');
        if (modal) modal.style.display = 'none';
    }

    async function handleConfirmReturnBatch(event) {
        event.preventDefault();
        const reason = document.getElementById('startReturnReasonInput')?.value;

        try {
            const res = await fetch(`/deals/${startDealId}/start/batches/${activeStartBatchId}/return`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': startCsrfToken,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ return_reason: reason })
            });
            const data = await res.json();
            if (data.success && data.batch) {
                updateBatchInState(data.batch);
                closeStartReturnModal();
            }
        } catch(e) {
            const batch = startBatches.find(b => b.id == activeStartBatchId);
            if (batch) {
                batch.status = 'Returned';
                if (!batch.history_logs) batch.history_logs = [];
                batch.history_logs.unshift({
                    event: 'Returned',
                    details: `START batch returned for correction: ${reason}`,
                    actor: startLoggedUser,
                    timestamp: new Date().toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })
                });
                updateBatchInState(batch);
            }
            closeStartReturnModal();
        }
    }

    function openStartCancelModal() {
        const modal = document.getElementById('startCancelModal');
        if (modal) modal.style.display = 'flex';
    }

    function closeStartCancelModal() {
        const modal = document.getElementById('startCancelModal');
        if (modal) modal.style.display = 'none';
    }

    async function handleConfirmCancelBatch(event) {
        event.preventDefault();
        const reason = document.getElementById('startCancelReasonInput')?.value;

        try {
            const res = await fetch(`/deals/${startDealId}/start/batches/${activeStartBatchId}/cancel`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': startCsrfToken,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ cancellation_reason: reason })
            });
            const data = await res.json();
            if (data.success && data.batch) {
                updateBatchInState(data.batch);
                closeStartCancelModal();
            }
        } catch(e) {
            const batch = startBatches.find(b => b.id == activeStartBatchId);
            if (batch) {
                batch.status = 'Cancelled';
                if (!batch.history_logs) batch.history_logs = [];
                batch.history_logs.unshift({
                    event: 'Cancelled',
                    details: `START batch cancelled. Reason: ${reason}`,
                    actor: startLoggedUser,
                    timestamp: new Date().toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })
                });
                updateBatchInState(batch);
            }
            closeStartCancelModal();
        }
    }

    function recomputeLocalBatchStatus(batch, historyEventDetail) {
        const assignments = batch.assignments || [];
        const requiredAssignments = assignments.filter(a => a.is_required);

        const hasDeclined = assignments.some(a => a.status === 'Declined');
        const ackCount = requiredAssignments.filter(a => a.status === 'Acknowledged').length;
        const totalReq = requiredAssignments.length;

        let newStatus = batch.status;

        if (hasDeclined) {
            newStatus = 'Needs Reassignment';
        } else if (totalReq > 0 && ackCount === totalReq) {
            newStatus = 'Team Confirmed';
        } else if (ackCount > 0 && ackCount < totalReq) {
            newStatus = 'Partially Acknowledged';
        } else if (totalReq > 0) {
            newStatus = 'For Team Confirmation';
        }

        batch.status = newStatus;
        if (!batch.history_logs) batch.history_logs = [];
        if (historyEventDetail) {
            batch.history_logs.unshift({
                event: newStatus,
                details: historyEventDetail,
                actor: startLoggedUser,
                timestamp: new Date().toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })
            });
        }

        updateBatchInState(batch);
    }

    function updateBatchInState(updatedBatch) {
        const idx = startBatches.findIndex(b => b.id === updatedBatch.id);
        if (idx !== -1) {
            startBatches[idx] = updatedBatch;
        } else {
            startBatches.unshift(updatedBatch);
        }
        saveStartBatchesLocally();
        renderStartBatches();
        renderStartStats();
        if (activeStartBatchId === updatedBatch.id) {
            populateStartBatchDetails(updatedBatch);
        }
    }

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeInquiryContextMenu();
            closeInquiryFormModal();
            closeInquiryViewModal();
            closeInquiryDeleteModal();
            closeAddNoteModal();
            closeConsultationContextMenu();
            closeConsultationModal();
            closeConsultationViewModal();
            closeUploadModal();
            closeDeleteConsultationModal();
            closeLineItemModal();
            closeDeleteLineItemModal();
            closeProposalTemplateModal();
            closeSendProposalModal();
            closeAddRevisionModal();
            closeClientReviewPortalModal();
            closeAdjustDiscountModal();
            closeUpdateDecisionModal();
            closeDispatchActionRequestModal();
            closeRecordPaymentModal();
            closeStartBatchDetailsModal();
            closeCreateStartBatchModal();
            closeStartDeclineModal();
            closeStartReassignModal();
            closeStartReturnModal();
            closeStartCancelModal();
            closeStartMemoAmendmentModal();
            closeStartAddAssignmentModal();
            closeStartMemoViewer();
            closeClientActionModal();
            closeClientActionDetailsModal();
        }
    });

    /* =========================================================
       FILES TAB & CLIENT ACTION REQUESTS JAVASCRIPT
       ========================================================= */
    const FILES_DEAL_ID = (window.DEAL_BOOTSTRAP?.dealId ?? 0);
    let clientActions = [];
    let currentViewingClientActionId = null;

    function getStoredClientActions() {
        try {
            const raw = localStorage.getItem('deal_' + FILES_DEAL_ID + '_client_actions');
            if (raw) {
                const parsed = JSON.parse(raw);
                if (Array.isArray(parsed)) return parsed;
            }
        } catch(e) {}

        const defaults = [
            {
                id: 1,
                request_type: 'Review & Accept Proposal',
                document: 'Proposal V5',
                delivery_method: 'Secure No-Login Link',
                status: 'Pending',
                requested_date: 'Sep 17, 2026',
                due_date: 'Sep 24, 2026',
                message: 'Please review and approve Proposal V5 for your project engagement.',
                notes: 'Generated from proposal workspace dispatch.',
                token: 'car-prop-v5-8941'
            },
            {
                id: 2,
                request_type: 'Digital / Online Sign',
                document: 'Contract',
                delivery_method: 'Secure No-Login Link',
                status: 'Completed',
                requested_date: 'Sep 17, 2026',
                due_date: 'Sep 20, 2026',
                message: 'Please provide signature on the Master Services Agreement.',
                notes: 'Signed and verified by client signatory.',
                token: 'car-sgn-8942'
            },
            {
                id: 3,
                request_type: 'Upload Required Document',
                document: 'KYC Document',
                delivery_method: 'Client Portal',
                status: 'In Progress',
                requested_date: 'Sep 18, 2026',
                due_date: 'Sep 25, 2026',
                message: 'Please upload the requested Corporate SEC Certificate and Valid IDs.',
                notes: 'Awaiting primary authorized signatory ID upload.',
                token: 'car-kyc-8943'
            }
        ];
        saveStoredClientActions(defaults);
        return defaults;
    }

    function saveStoredClientActions(actions) {
        try {
            localStorage.setItem('deal_' + FILES_DEAL_ID + '_client_actions', JSON.stringify(actions));
        } catch(e) {}
    }

    function initFilesTab() {
        clientActions = getStoredClientActions();
        renderClientActionsTable();
        updateDealFilesProposalBadge();
    }

    function updateDealFilesProposalBadge() {
        try {
            const versions = typeof getProposalVersions === 'function' ? getProposalVersions() : [];
            const sorted = [...versions].sort((a, b) => b.versionNumber - a.versionNumber);
            const latest = sorted[0];
            const badge = document.getElementById('dealFilesActiveProposalVer');
            const label = document.getElementById('dealFilesActiveProposalLabel');
            if (badge && latest) {
                badge.innerHTML = `<span class="pill-dot"></span><span>${latest.version} · Current</span>`;
            }
            if (label && latest) {
                label.innerText = `Proposal ${latest.version}`;
            }

            // Sync START and Service Memo codes if available from startBatches
            if (typeof startBatches !== 'undefined' && Array.isArray(startBatches) && startBatches.length > 0) {
                const batch = startBatches[0];
                const startCodeEl = document.getElementById('dealFilesStartBatchCode');
                const memoTitleEl = document.getElementById('dealFilesServiceMemoTitle');
                if (startCodeEl && batch.start_code) startCodeEl.innerText = batch.start_code;
                if (memoTitleEl && batch.service_memo_ref) memoTitleEl.innerText = `Service Memo ${batch.service_memo_ref}`;
            }
        } catch(e) {}
    }

    function openProposalPdfFromFiles() {
        switchDealNavTab('proposal');
        setTimeout(() => {
            if (typeof window.print === 'function') {
                window.print();
            }
        }, 400);
    }

    function renderClientActionsTable() {
        const tbody = document.getElementById('clientActionTableBody');
        const tableWrap = document.getElementById('clientActionTableWrap');
        const emptyState = document.getElementById('clientActionEmptyState');
        if (!tbody || !tableWrap || !emptyState) return;

        if (!clientActions || clientActions.length === 0) {
            tableWrap.style.display = 'none';
            emptyState.style.display = 'flex';
            return;
        }

        tableWrap.style.display = 'block';
        emptyState.style.display = 'none';

        tbody.innerHTML = clientActions.map(ca => {
            const statusClass = ca.status === 'Completed' ? 'client-action-pill-completed' :
                               (ca.status === 'In Progress' ? 'client-action-pill-inprogress' :
                               (ca.status === 'Declined' ? 'client-action-pill-declined' : 'client-action-pill-pending'));

            return `
                <tr>
                    <td style="vertical-align: middle;">
                        <div style="font-weight: 700; color: #0f172a; font-size: 13.5px; line-height: 1.35;">${escapeHtml(ca.request_type)}</div>
                    </td>
                    <td style="vertical-align: middle;">
                        <div style="font-weight: 700; color: #0f172a; font-size: 13.5px; line-height: 1.35;">${escapeHtml(ca.document || 'Document')}</div>
                    </td>
                    <td style="vertical-align: middle;">
                        <div style="display: flex; align-items: center; gap: 6px; color: #334155; font-size: 13px; font-weight: 500;">
                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="#64748b" stroke-width="2" style="flex-shrink: 0;"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"></path><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"></path></svg>
                            <span>${escapeHtml(ca.delivery_method)}</span>
                        </div>
                    </td>
                    <td style="vertical-align: middle;">
                        <span class="client-action-pill ${statusClass}">
                            <span class="pill-dot"></span>
                            <span>${escapeHtml(ca.status)}</span>
                        </span>
                    </td>
                    <td style="vertical-align: middle; font-size: 13px; color: #64748b; font-weight: 500; white-space: nowrap;">
                        ${escapeHtml(ca.requested_date || 'Today')}
                    </td>
                    <td style="vertical-align: middle; text-align: right; white-space: nowrap;">
                        <button type="button" class="files-action-btn-view" onclick="openClientActionDetails(${ca.id})">
                            View
                        </button>
                    </td>
                </tr>
            `;
        }).join('');
    }

    function openClientActionModal() {
        const modal = document.getElementById('clientActionModal');
        if (!modal) return;
        updateClientActionDocumentOptions();
        modal.style.display = 'flex';
    }

    function closeClientActionModal() {
        const modal = document.getElementById('clientActionModal');
        if (modal) modal.style.display = 'none';
    }

    function updateClientActionDocumentOptions() {
        const typeSelect = document.getElementById('clientActionRequestTypeSelect');
        const docSelect = document.getElementById('clientActionDocSelect');
        if (!typeSelect || !docSelect) return;

        const val = typeSelect.value;
        docSelect.innerHTML = '';

        const isProposalRelated = val.includes('Proposal') || val.includes('Discount') || val.includes('Commercial') || val.includes('Terms');

        if (isProposalRelated) {
            let versions = [];
            if (typeof getProposalVersions === 'function') {
                versions = getProposalVersions();
            }
            if (!versions || versions.length === 0) {
                versions = [{ version: 'Proposal V5', versionNumber: 5, status: 'Approved' }];
            }
            const sorted = [...versions].sort((a,b) => b.versionNumber - a.versionNumber);
            sorted.forEach((v, idx) => {
                const opt = document.createElement('option');
                opt.value = v.version.startsWith('Proposal') ? v.version : `Proposal ${v.version}`;
                opt.textContent = `${v.version.startsWith('Proposal') ? v.version : 'Proposal ' + v.version}${idx === 0 ? ' (CURRENT)' : ''} — ${v.status || 'Draft'}`;
                docSelect.appendChild(opt);
            });
        } else if (val.includes('Notice to Proceed') || val.includes('NTP')) {
            const opt = document.createElement('option');
            opt.value = 'Notice to Proceed (NTP)';
            opt.textContent = 'Notice to Proceed (NTP)';
            docSelect.appendChild(opt);
        } else if (val.includes('Project SOW') || val.includes('Retainer Scope')) {
            const opt1 = document.createElement('option');
            opt1.value = 'Project Scope of Work (SOW)';
            opt1.textContent = 'Project Scope of Work (SOW)';
            docSelect.appendChild(opt1);
            const opt2 = document.createElement('option');
            opt2.value = 'Recurring Retainer Scope Schedule';
            opt2.textContent = 'Recurring Retainer Scope Schedule';
            docSelect.appendChild(opt2);
        } else if (val.includes('Transmittal') || val.includes('Completion')) {
            const opt1 = document.createElement('option');
            opt1.value = 'Transmittal Delivery Receipt';
            opt1.textContent = 'Transmittal Delivery Receipt';
            docSelect.appendChild(opt1);
            const opt2 = document.createElement('option');
            opt2.value = 'Project Completion Certificate';
            opt2.textContent = 'Project Completion Certificate';
            docSelect.appendChild(opt2);
        } else if (val === 'Form Declaration') {
            const opt = document.createElement('option');
            opt.value = 'Client Form Declaration';
            opt.textContent = 'Client Form Declaration';
            docSelect.appendChild(opt);
        } else if (val.includes('Upload')) {
            const opt1 = document.createElement('option');
            opt1.value = 'KYC / Corporate Registration Documents';
            opt1.textContent = 'KYC / Corporate Registration Documents';
            docSelect.appendChild(opt1);
            const opt2 = document.createElement('option');
            opt2.value = 'Tax Identification & Financial Statements';
            opt2.textContent = 'Tax Identification & Financial Statements';
            docSelect.appendChild(opt2);
        } else if (val.includes('Sign')) {
            const opt1 = document.createElement('option');
            opt1.value = 'Contract Agreement';
            opt1.textContent = 'Contract Agreement';
            docSelect.appendChild(opt1);
            const opt2 = document.createElement('option');
            opt2.value = 'Service Engagement Addendum';
            opt2.textContent = 'Service Engagement Addendum';
            docSelect.appendChild(opt2);
        } else {
            const opt = document.createElement('option');
            opt.value = 'Standard Deal Documentation';
            opt.textContent = 'Standard Deal Documentation';
            docSelect.appendChild(opt);
        }
    }

    function handleClientActionSubmit(e) {
        e.preventDefault();
        const typeSelect = document.getElementById('clientActionRequestTypeSelect');
        const docSelect = document.getElementById('clientActionDocSelect');
        const methodSelect = document.getElementById('clientActionDeliveryMethodSelect');
        const messageInput = document.getElementById('clientActionMessageInput');
        const dueDateInput = document.getElementById('clientActionDueDateInput');
        const notesInput = document.getElementById('clientActionNotesInput');

        const newId = Date.now();
        const randToken = 'car-' + Math.random().toString(36).substring(2, 9);
        const todayStr = new Date().toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });

        const newAction = {
            id: newId,
            request_type: typeSelect ? typeSelect.value : 'Review & Accept Proposal',
            document: docSelect ? docSelect.value : 'Proposal V5',
            delivery_method: methodSelect ? methodSelect.value : 'Secure No-Login Link',
            status: 'Pending',
            requested_date: todayStr,
            due_date: dueDateInput ? dueDateInput.value : '',
            message: messageInput ? messageInput.value : '',
            notes: notesInput ? notesInput.value : '',
            token: randToken
        };

        clientActions.unshift(newAction);
        saveStoredClientActions(clientActions);
        renderClientActionsTable();
        closeClientActionModal();

        // Reset inputs
        if (messageInput) messageInput.value = '';
        if (notesInput) notesInput.value = '';
        alert(`Client Action Request for "${newAction.request_type}" created and sent successfully!`);
    }

    function openClientActionDetails(id) {
        const ca = clientActions.find(a => a.id == id);
        if (!ca) return;
        currentViewingClientActionId = id;

        const modal = document.getElementById('clientActionDetailsModal');
        const titleEl = document.getElementById('cadModalTitle');
        const statusEl = document.getElementById('cadModalStatusBadge');
        const typeEl = document.getElementById('cadDetailRequestType');
        const docEl = document.getElementById('cadDetailDocument');
        const methodEl = document.getElementById('cadDetailDeliveryMethod');
        const reqDateEl = document.getElementById('cadDetailRequestedDate');
        const linkEl = document.getElementById('cadDetailLinkInput');
        const msgEl = document.getElementById('cadDetailMessage');
        const notesEl = document.getElementById('cadDetailNotes');
        const toggleBtn = document.getElementById('cadToggleStatusBtn');

        if (titleEl) titleEl.innerText = ca.request_type;
        if (typeEl) typeEl.innerText = ca.request_type;
        if (docEl) docEl.innerText = ca.document || 'Document';
        if (methodEl) methodEl.innerText = ca.delivery_method;
        if (reqDateEl) reqDateEl.innerText = `${ca.requested_date || 'Today'}${ca.due_date ? ' (Due: ' + ca.due_date + ')' : ''}`;
        if (linkEl) linkEl.value = `${window.location.origin}/portal/action/${ca.token || 'car-token-' + ca.id}`;
        if (msgEl) msgEl.innerText = ca.message || 'No custom message provided.';
        if (notesEl) notesEl.innerText = ca.notes || 'No internal notes recorded.';

        if (statusEl) {
            statusEl.className = 'client-action-pill ' + (
                ca.status === 'Completed' ? 'client-action-pill-completed' :
                (ca.status === 'In Progress' ? 'client-action-pill-inprogress' :
                (ca.status === 'Declined' ? 'client-action-pill-declined' : 'client-action-pill-pending'))
            );
            statusEl.innerHTML = `<span class="pill-dot"></span><span>${escapeHtml(ca.status)}</span>`;
        }

        const statusSelect = document.getElementById('cadStatusSelect');
        if (statusSelect) {
            statusSelect.value = ca.status || 'Pending';
        }

        if (toggleBtn) {
            if (ca.status === 'Completed') {
                toggleBtn.innerText = 'Reopen (Mark as Pending)';
                toggleBtn.style.color = '#475569';
                toggleBtn.style.borderColor = '#cbd5e1';
                toggleBtn.style.background = '#ffffff';
                toggleBtn.style.fontWeight = '600';
            } else {
                toggleBtn.innerText = 'Mark as Completed';
                toggleBtn.style.color = '#047857';
                toggleBtn.style.borderColor = '#10b981';
                toggleBtn.style.background = '#ecfdf5';
                toggleBtn.style.fontWeight = '700';
            }
        }

        if (modal) modal.style.display = 'flex';
    }

    function handleClientActionStatusChange(newStatus) {
        if (!currentViewingClientActionId) return;
        const ca = clientActions.find(a => a.id == currentViewingClientActionId);
        if (ca) {
            ca.status = newStatus;
            saveStoredClientActions(clientActions);
            renderClientActionsTable();
            openClientActionDetails(currentViewingClientActionId);
        }
    }

    function closeClientActionDetailsModal() {
        const modal = document.getElementById('clientActionDetailsModal');
        if (modal) modal.style.display = 'none';
        currentViewingClientActionId = null;
    }

    function copyClientActionLink() {
        const linkInput = document.getElementById('cadDetailLinkInput');
        if (linkInput) {
            linkInput.select();
            navigator.clipboard.writeText(linkInput.value);
            alert('Client Action access link copied to clipboard!');
        }
    }

    function toggleClientActionStatus(forcedStatus = null) {
        if (!currentViewingClientActionId) return;
        const ca = clientActions.find(a => a.id == currentViewingClientActionId);
        if (ca) {
            if (forcedStatus) {
                ca.status = forcedStatus;
            } else {
                ca.status = (ca.status === 'Completed') ? 'Pending' : 'Completed';
            }
            saveStoredClientActions(clientActions);
            renderClientActionsTable();
            openClientActionDetails(currentViewingClientActionId);
        }
    }

    function handleDeleteCurrentClientAction() {
        if (!currentViewingClientActionId) return;
        if (!confirm('Are you sure you want to delete this Client Action Request?')) return;
        clientActions = clientActions.filter(a => a.id != currentViewingClientActionId);
        saveStoredClientActions(clientActions);
        renderClientActionsTable();
        closeClientActionDetailsModal();
    }
