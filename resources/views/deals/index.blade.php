<!DOCTYPE html> 
<html lang="en"> 
 
<head> 
    <meta charset="UTF-8"> 
    <meta name="viewport" content="width=device-width, initial-scale=1.0"> 
    <meta name="csrf-token" content="{{ csrf_token() }}"> 

    <title>Deals</title> 
 
    <style> 
 
        * { 
            box-sizing: border-box; 
        } 
 
        html, 
        body { 
            margin: 0; 
            padding: 0; 
            width: 100%; 
            height: 100%; 
            font-family: Arial, Helvetica, sans-serif; 
            background: #f8fafc; 
            color: #172033; 
        } 
 
        body { 
            overflow: hidden; 
        } 
 
        button, 
        input, 
        select, 
        textarea { 
            font-family: inherit; 
        } 
 
        button { 
            cursor: pointer; 
        } 
 
 
        /* ========================================================= 
           APPLICATION 
        ========================================================= */ 
 
        .app { 
            width: 100%; 
            height: 100vh; 
            display: flex; 
            flex-direction: column; 
            overflow: hidden; 
        } 
 
 
        /* ========================================================= 
           TOP HEADER 
        ========================================================= */ 
 
        .top-header { 
            height: 58px; 
            min-height: 58px; 
 
            background: #ffffff; 
            border-bottom: 1px solid #e5e7eb; 
 
            display: flex; 
            align-items: flex-start; 
            justify-content: space-between; 
 
            padding: 0 22px; 
 
            position: relative; 
            z-index: 100; 
        } 
 
        .brand { 
            display: flex; 
            align-items: center; 
            min-width: 220px; 
        } 
 
        .brand-logo { 
            width: 88px; 
            height: 45px; 
 
            display: flex; 
            align-items: center; 
            justify-content: flex-start; 
        } 
 
        .brand-logo img { 
            max-width: 100%; 
            max-height: 100%; 
            object-fit: contain; 
        } 
 
        .brand-text { 
            line-height: 1; 
        } 
 
        .brand-name { 
            font-family: Georgia, "Times New Roman", serif; 
            font-size: 16px; 
            font-weight: 600; 
            color: #172033; 
        } 
 
        .brand-subtitle { 
            margin-top: 3px; 
            font-size: 9px; 
            color: #2458d7; 
            font-weight: 600; 
        } 
 
 
        /* ========================================================= 
           HEADER SEARCH 
        ========================================================= */ 
 
        .header-search { 
            position: absolute; 
            left: 50%; 
            transform: translateX(-50%); 
 
            width: 510px; 
            max-width: 40vw; 
            height: 34px; 
 
            background: #f1f3f6; 
            border-radius: 18px; 
 
            display: flex; 
            align-items: center; 
 
            padding: 0 15px; 
 
            color: #94a3b8; 
        } 
 
        .header-search svg { 
            width: 15px; 
            height: 15px; 
            margin-right: 10px; 
        } 
 
        .header-search input { 
            width: 100%; 
            border: 0; 
            outline: 0; 
            background: transparent; 
 
            font-size: 11px; 
            color: #475569; 
        } 
 
        .header-search input::placeholder { 
            color: #94a3b8; 
        } 
 
 
        /* ========================================================= 
           HEADER ACTIONS 
        ========================================================= */ 
 
        .header-actions { 
            display: flex; 
            align-items: center; 
            gap: 14px; 
        } 
 
 
        /* ========================================================= 
           NOTIFICATION 
        ========================================================= */ 
 
        .notification-wrap { 
            position: relative; 
        } 
 
        .notification-button { 
            width: 34px; 
            height: 34px; 
 
            border: 0; 
            background: transparent; 
            border-radius: 8px; 
 
            color: #64748b; 
 
            display: flex; 
            align-items: center; 
            justify-content: center; 
 
            position: relative; 
        } 
 
        .notification-button:hover { 
            background: #f8fafc; 
        } 
 
        .notification-button svg { 
            width: 18px; 
            height: 18px; 
        } 
 
        .notif-count { 
            position: absolute; 
            top: -1px; 
            right: -3px; 
 
            min-width: 17px; 
            height: 17px; 
 
            padding: 0 4px; 
 
            border-radius: 20px; 
 
            background: #dc2626; 
            color: #ffffff; 
 
            font-size: 8px; 
            font-weight: 700; 
 
            display: flex; 
            align-items: center; 
            justify-content: center; 
        } 
 
        .notification-panel { 
            position: absolute; 
            top: 43px; 
            right: 0; 
 
            width: 330px; 
 
            background: #ffffff; 
            border: 1px solid #e2e8f0; 
            border-radius: 10px; 
 
            box-shadow: 0 15px 40px rgba(15, 23, 42, .14); 
 
            display: none; 
            overflow: hidden; 
 
            z-index: 500; 
        } 
 
        .notification-panel.show { 
            display: block; 
        } 
 
        .notification-header { 
            padding: 13px 15px; 
 
            border-bottom: 1px solid #eef2f7; 
 
            display: flex; 
            align-items: center; 
            justify-content: space-between; 
        } 
 
        .notification-header strong { 
            font-size: 12px; 
        } 
 
        .notification-header button { 
            border: 0; 
            background: transparent; 
            color: #2458d7; 
            font-size: 10px; 
        } 
 
        .notification-list { 
            max-height: 330px; 
            overflow-y: auto; 
        } 
 
        .notification-item { 
            padding: 12px 15px; 
 
            border-bottom: 1px solid #f1f5f9; 
 
            font-size: 10px; 
            color: #475569; 
 
            cursor: pointer; 
        } 
 
        .notification-item:hover { 
            background: #f8fafc; 
        } 
 
        .notification-item.unread { 
            background: #f8fbff; 
        } 
 
        .notification-empty { 
            padding: 25px; 
            text-align: center; 
            color: #94a3b8; 
            font-size: 10px; 
        } 
 
 
        /* ========================================================= 
           PROFILE 
        ========================================================= */ 
 
        .avatar { 
            width: 32px; 
            height: 32px; 
 
            border-radius: 50%; 
 
            background: #eef1f5; 
            color: #475569; 
 
            display: flex; 
            align-items: center; 
            justify-content: center; 
 
            font-size: 11px; 
            font-weight: 600; 
        } 
 
 
        /* ========================================================= 
           WORKSPACE 
        ========================================================= */ 
 
        .workspace { 
            flex: 1; 
            min-height: 0; 
 
            display: flex; 
        } 
 
 
        /* ========================================================= 
           ICON SIDEBAR 
        ========================================================= */ 
 
        .rail { 
            width: 70px; 
            min-width: 70px; 
 
            background: #ffffff; 
            border-right: 1px solid #e5e7eb; 
 
            padding-top: 14px; 
 
            display: flex; 
            flex-direction: column; 
            align-items: center; 
 
            gap: 8px; 
        } 
 
        .rail-icon { 
            width: 36px; 
            height: 36px; 
 
            border-radius: 9px; 
 
            color: #718096; 
 
            display: flex; 
            align-items: center; 
            justify-content: center; 
 
            cursor: pointer; 
        } 
 
        .rail-icon:hover { 
            background: #f8fafc; 
            color: #2458d7; 
        } 
 
        .rail-icon.active { 
            background: #edf4ff; 
            color: #2458d7; 
 
            border: 1px solid #d8e7ff; 
        } 
 
        .rail-icon svg { 
            width: 17px; 
            height: 17px; 
        } 
 
 
        /* ========================================================= 
           MAIN 
        ========================================================= */ 
 
        .main { 
            flex: 1; 
            min-width: 0; 
            min-height: 0; 
 
            overflow: hidden; 
 
            background: #ffffff; 
        } 
 
        .page { 
            height: 100%; 
 
            display: flex; 
            flex-direction: column; 
 
            min-height: 0; 
        } 
 
 
        /* ========================================================= 
           PAGE HEADER 
        ========================================================= */ 
 
        .page-header { 
            padding: 24px 22px 12px; 
 
            background: #ffffff; 
 
            display: flex; 
            align-items: center; 
            justify-content: space-between; 
        } 
 
        .page-title { 
            margin: 0; 
 
            font-size: 26px; 
            font-weight: 600; 
 
            color: #111827; 
        } 
 
        .page-subtitle { 
            margin-top: 3px; 
 
            color: #64748b; 
 
            font-size: 11px; 
        } 
 
 
        /* ========================================================= 
           ADD DEAL BUTTON & QUICK PURCHASE SHORTCUT
        ========================================================= */ 

        .quick-purchase-button { 
            height: 36px; 
            padding: 0 14px; 
            border: 1px solid #d1d5db; 
            border-radius: 7px; 
            background: #ffffff; 
            color: #1e293b; 
            font-size: 11px; 
            font-weight: 600; 
            display: inline-flex; 
            align-items: center; 
            gap: 6px; 
            box-shadow: 0 1px 2px rgba(15, 23, 42, .04); 
            cursor: pointer;
            transition: all 0.15s ease;
        } 

        .quick-purchase-button:hover { 
            background: #f8fafc; 
            border-color: #94a3b8;
            color: #0f172a;
        } 

        .quick-purchase-button svg { 
            width: 14px; 
            height: 14px; 
            color: #2563eb;
        } 

        .primary-button { 
            height: 36px; 
 
            padding: 0 14px; 
 
            border: 0; 
            border-radius: 7px; 
 
            background: #2864df; 
            color: #ffffff; 
 
            font-size: 11px; 
            font-weight: 600; 
 
            display: inline-flex; 
            align-items: center; 
            gap: 6px; 
 
            box-shadow: 0 1px 2px rgba(15, 23, 42, .08); 
        } 
 
        .primary-button:hover { 
            background: #1f55c7; 
        } 
 
        .primary-button svg { 
            width: 14px; 
            height: 14px; 
        } 
 
 
        /* ========================================================= 
           FILTERS 
        ========================================================= */ 
 
        .filters { 
            padding: 5px 22px 14px; 
 
            background: #ffffff; 
 
            display: flex; 
            align-items: center; 
 
            gap: 7px; 
 
            border-bottom: 1px solid #e5e7eb; 
        } 
 
        .search-box-wrapper { 
            position: relative; 
            width: 337px; 
        } 

        .search-input-inner { 
            position: relative; 
            display: flex; 
            align-items: center; 
            width: 100%; 
        } 

        .search-input-inner .search-icon { 
            position: absolute; 
            left: 10px; 
            width: 13px; 
            height: 13px; 
            color: #94a3b8; 
            pointer-events: none; 
            z-index: 2; 
        } 

        .search-box { 
            width: 100%; 
            height: 34px; 
            border: 1px solid #dce2ea; 
            border-radius: 7px; 
            padding: 0 30px 0 28px; 
            outline: none; 
            color: #334155; 
            font-size: 11px; 
            background: #ffffff; 
            transition: border-color 0.15s ease, box-shadow 0.15s ease; 
        } 

        .search-box:focus { 
            border-color: #3b82f6; 
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.12); 
        } 

        .search-clear-btn { 
            position: absolute; 
            right: 8px; 
            width: 17px; 
            height: 17px; 
            border: none; 
            background: #e2e8f0; 
            color: #64748b; 
            border-radius: 50%; 
            display: flex; 
            align-items: center; 
            justify-content: center; 
            font-size: 13px; 
            cursor: pointer; 
            padding: 0; 
            line-height: 1; 
            transition: all 0.15s ease; 
            z-index: 2; 
        } 

        .search-clear-btn:hover { 
            background: #cbd5e1; 
            color: #0f172a; 
        } 

        .search-spinner { 
            position: absolute; 
            right: 9px; 
            width: 13px; 
            height: 13px; 
            border: 2px solid #e2e8f0; 
            border-top-color: #3b82f6; 
            border-radius: 50%; 
            animation: searchSpin 0.6s linear infinite; 
            z-index: 2; 
        } 

        @keyframes searchSpin { 
            to { transform: rotate(360deg); } 
        } 

        /* Typeahead Dropdown - Clean Minimalist Design */ 
        .search-typeahead-dropdown { 
            position: absolute; 
            top: calc(100% + 5px); 
            left: 0; 
            width: 440px; 
            max-height: 420px; 
            background: #ffffff; 
            border: 1px solid #dce2ea; 
            border-radius: 8px; 
            box-shadow: 0 10px 25px -4px rgba(15, 23, 42, 0.12), 0 4px 10px -2px rgba(15, 23, 42, 0.04); 
            z-index: 1050; 
            overflow: hidden; 
            display: flex; 
            flex-direction: column; 
            animation: typeaheadFadeIn 0.12s ease; 
        } 

        @keyframes typeaheadFadeIn { 
            from { opacity: 0; transform: translateY(-3px); } 
            to { opacity: 1; transform: translateY(0); } 
        } 

        .typeahead-header { 
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
            padding: 8px 14px; 
            background: #f8fafc; 
            border-bottom: 1px solid #edf2f7; 
            font-size: 10px; 
            font-weight: 700; 
            text-transform: uppercase; 
            letter-spacing: 0.05em; 
            color: #64748b; 
        } 

        .typeahead-hint { 
            font-size: 9.5px; 
            font-weight: 400; 
            text-transform: none; 
            color: #94a3b8; 
            letter-spacing: normal; 
        } 

        .typeahead-results { 
            max-height: 330px; 
            overflow-y: auto; 
            padding: 0; 
        } 

        /* Result Row - Clean, simple, no bordered card wrappers */ 
        .typeahead-item { 
            display: flex; 
            flex-direction: column; 
            padding: 9px 14px; 
            cursor: pointer; 
            text-decoration: none; 
            color: inherit; 
            transition: background 0.1s ease; 
            border-bottom: 1px solid #f1f5f9; 
        } 

        .typeahead-item:last-child { 
            border-bottom: none; 
        } 

        .typeahead-item:hover, 
        .typeahead-item.active { 
            background: #f8fafc; 
        } 

        /* 1. Primary Result: Customer / Client Name */ 
        .typeahead-client-primary { 
            font-size: 13px; 
            color: #0f172a; 
            font-weight: 600; 
            line-height: 1.35; 
            white-space: nowrap; 
            overflow: hidden; 
            text-overflow: ellipsis; 
            margin-bottom: 3px; 
        } 

        /* 2. Secondary identification line: Code, Deal Title / Service */ 
        .typeahead-secondary-info { 
            font-size: 11px; 
            color: #64748b; 
            font-weight: 400; 
            white-space: nowrap; 
            overflow: hidden; 
            text-overflow: ellipsis; 
            flex: 1; 
        } 

        /* Backward-compatibility classes if needed */
        .typeahead-code { 
            font-size: 10.5px; 
            font-weight: 600; 
            color: #64748b; 
            letter-spacing: 0.02em; 
            margin-bottom: 2px; 
            line-height: 1.2; 
        } 

        .typeahead-title { 
            font-size: 12.5px; 
            color: #0f172a; 
            font-weight: 600; 
            line-height: 1.35; 
            white-space: nowrap; 
            overflow: hidden; 
            text-overflow: ellipsis; 
            margin-bottom: 4px; 
        } 

        /* 3. Bottom Row: Secondary info on left, Stage Badge on right */ 
        .typeahead-meta { 
            display: flex; 
            align-items: center; 
            justify-content: space-between; 
            gap: 8px; 
            margin-top: 1px; 
        } 

        .typeahead-client { 
            font-size: 11px; 
            color: #64748b; 
            font-weight: 400; 
            white-space: nowrap; 
            overflow: hidden; 
            text-overflow: ellipsis; 
            flex: 1; 
        } 

        /* 4. Stage Badge - Subtle, not oversized */ 
        .typeahead-stage-badge { 
            font-size: 9.5px; 
            font-weight: 600; 
            padding: 2px 7px; 
            border-radius: 9999px; 
            color: #ffffff; 
            white-space: nowrap; 
            flex-shrink: 0; 
            line-height: 1.2; 
        } 

        .typeahead-highlight { 
            background-color: #fef08a; 
            color: #713f12; 
            font-weight: 600; 
            border-radius: 2px; 
            padding: 0 1px; 
        } 

        .typeahead-empty { 
            padding: 22px 14px; 
            text-align: center; 
            color: #64748b; 
            font-size: 11px; 
        } 

        .typeahead-footer { 
            padding: 8px 14px; 
            background: #fafbfc; 
            border-top: 1px solid #edf2f7; 
            font-size: 10.5px; 
        } 

        .typeahead-footer-action { 
            display: block; 
            color: #2563eb; 
            text-decoration: none; 
            font-weight: 500; 
            cursor: pointer; 
            line-height: 1.4; 
        } 

        .typeahead-footer-action:hover { 
            color: #1d4ed8; 
            text-decoration: underline; 
        } 

        .typeahead-footer kbd { 
            background: #e2e8f0; 
            color: #334155; 
            padding: 1px 4px; 
            border-radius: 4px; 
            font-size: 9.5px; 
            font-family: inherit; 
            border: 1px solid #cbd5e1; 
        } 
 
        .filter-select { 
            height: 34px; 
 
            border: 1px solid #dce2ea; 
            border-radius: 7px; 
 
            background: #ffffff; 
            color: #475569; 
 
            padding: 0 10px; 
 
            font-size: 11px; 
 
            outline: none; 
        } 
 
        .filter-button { 
            height: 34px; 
 
            padding: 0 13px; 
 
            border: 1px solid #dce2ea; 
            border-radius: 7px; 
 
            background: #ffffff; 
            color: #475569; 
 
            font-size: 11px; 
        } 
 
        .filter-button:hover { 
            background: #f8fafc; 
        } 
 
        .deal-count { 
            font-size: 10px; 
            color: #64748b; 
            margin-left: 2px; 
        } 
 
 
        /* ========================================================= 
           KANBAN 
        ========================================================= */ 
 
        .kanban-scroll { 
            flex: 1; 
            min-height: 0; 
 
            overflow-x: auto; 
            overflow-y: auto; 
 
            padding: 14px 22px 24px; 
 
            background: #ffffff; 
        } 
 
        .kanban { 
            display: flex; 
            align-items: flex-start; 
 
            gap: 10px; 
 
            min-width: max-content; 
            height: 100%; 
        } 
 
        .kanban-column { 
            width: 215px; 
            min-width: 215px; 
 
            min-height: 100%; 
 
            background: #f8fafc; 
 
            border: 1px solid #e1e6ed; 
            border-radius: 8px; 
 
            overflow: visible; 
 
            position: relative; 
        } 
 
 
        /* ========================================================= 
           COLUMN HEADER 
        ========================================================= */ 
 
        .column-header { 
            background: #1e293b; 
 
            border-top: 3px solid #2458d7; 
            border-radius: 8px 8px 0 0; 
 
            padding: 8px 10px; 
 
            color: #ffffff; 
        } 
 
        .column-title-row { 
            display: flex; 
            align-items: center; 
            justify-content: space-between; 
 
            gap: 6px; 
        } 
 
        .column-title { 
            font-size: 12px; 
            font-weight: 700; 
            color: #ffffff; 
            line-height: 1.2;
        } 

        .stage-header-actions {
            display: flex;
            align-items: center;
            gap: 4px;
        }

        .stage-checkbox {
            width: 14px;
            height: 14px;
            border: 1px solid #64748b;
            border-radius: 3px;
            background: rgba(255, 255, 255, 0.08);
            cursor: pointer;
            margin: 0;
            accent-color: #2563eb;
        }
 
        .column-meta { 
            margin-top: 3px; 
 
            color: #cbd5e1; 
 
            font-size: 10px; 
            font-weight: 500;
        } 
 
 
        /* ========================================================= 
           STAGE MENU 
        ========================================================= */ 
 
        .stage-menu-wrap { 
            position: relative; 
        } 
 
        .dots-button { 
            width: 22px; 
            height: 22px; 
 
            border: 0; 
            background: transparent; 
 
            border-radius: 4px; 
 
            color: #94a3b8; 
 
            display: flex; 
            align-items: center; 
            justify-content: center; 
            cursor: pointer; 
            padding: 0; 
            transition: background 0.15s ease, color 0.15s ease;
        } 
 
        .dots-button:hover { 
            background: rgba(255, 255, 255, 0.15); 
            color: #ffffff; 
        } 
 
        .stage-menu { 
            position: absolute; 
            right: 0; 
            top: calc(100% + 8px); 
 
            width: 200px; 
            min-width: 200px; 
            max-width: 200px; 
 
            background: #ffffff; 
 
            border: 1px solid #e2e8f0; 
            border-radius: 12px; 
 
            box-shadow: 0 10px 25px -5px rgba(15, 23, 42, 0.12), 0 8px 10px -6px rgba(15, 23, 42, 0.08); 
 
            padding: 8px; 
 
            display: none; 
 
            z-index: 250; 
            box-sizing: border-box;
        } 

        .stage-menu.position-above {
            top: auto;
            bottom: calc(100% + 8px);
        }
 
        .stage-menu.show { 
            display: block; 
        } 
 
        .stage-menu button { 
            width: 100%; 
            height: 32px; 
 
            border: 0; 
            background: transparent; 
 
            text-align: left; 
 
            padding: 0 10px; 
            border-radius: 6px; 
 
            font-size: 12px; 
            font-weight: 500; 
            color: #1e293b; 
            cursor: pointer; 
            box-sizing: border-box; 
            display: flex; 
            align-items: center; 
            transition: background 0.12s ease, color 0.12s ease;
        } 
 
        .stage-menu button:hover { 
            background: #f1f5f9; 
            color: #0f172a; 
        } 
 
        .stage-menu-section-label { 
            padding: 8px 10px 6px; 
            font-size: 10px; 
            font-weight: 700; 
            color: #94a3b8; 
            text-transform: uppercase; 
            letter-spacing: 0.05em; 
            line-height: 1;
        } 
 
        .stage-color-palette { 
            display: flex; 
            align-items: center; 
            justify-content: space-between; 
            gap: 4px; 
            padding: 2px 6px 8px; 
        } 
 
        .color-dot { 
            width: 17px !important; 
            height: 17px !important; 
            min-width: 17px; 
            min-height: 17px; 
            border-radius: 50% !important; 
            border: 2px solid #ffffff !important; 
            box-shadow: 0 0 0 1px #cbd5e1; 
            cursor: pointer; 
            padding: 0 !important; 
            margin: 0 !important;
            flex-shrink: 0; 
            transition: transform 0.15s ease, box-shadow 0.15s ease; 
        } 
 
        .color-dot:hover { 
            transform: scale(1.22); 
            box-shadow: 0 0 0 2px #64748b; 
        } 

        .color-dot:focus {
            outline: none;
            box-shadow: 0 0 0 2px #2563eb;
        }
 
        .stage-menu-divider { 
            height: 1px; 
            background: #f1f5f9; 
            margin: 4px 0 4px; 
        }

        .stage-menu .delete-action { 
            color: #dc2626 !important; 
        } 
 
        .stage-menu .delete-action:hover { 
            background: #fef2f2 !important; 
            color: #b91c1c !important;
        }
 
 
        /* ========================================================= 
           COLUMN CONTENT 
        ========================================================= */ 
 
        .column-content { 
            padding: 8px; 
 
            min-height: 100px; 
        } 
 
 
        /* ========================================================= 
           EMPTY STAGE 
        ========================================================= */ 
 
        .empty-stage { 
            padding: 22px 8px; 
 
            margin: 0; 
 
            text-align: center; 
 
            color: #94a3b8; 
 
            font-size: 10px; 
 
            background: #ffffff; 
 
            border: 1px dashed #d6dde7; 
 
            border-radius: 7px; 
        } 
 
        .stage-description { 
            padding: 0 10px 10px; 
 
            color: #64748b; 
 
            font-size: 10px; 
        } 
 
 
        /* ========================================================= 
           DEAL CARD 
        ========================================================= */ 
 
        .deal-card { 
            position: relative; 
 
            background: #ffffff; 
 
            border: 1px solid #e2e8f0; 
            border-radius: 8px; 
 
            padding: 12px 12px 10px; 
 
            margin-bottom: 8px; 
 
            cursor: pointer; 
 
            transition: 
                box-shadow .18s ease, 
                border-color .18s ease,
                background-color .18s ease,
                transform .18s ease; 
        } 
 
        .deal-card:hover { 
            border-color: #2563eb; 
            background-color: #f8faff;
            transform: translateY(-2px);
            box-shadow: 
                0 6px 16px rgba(37, 99, 235, 0.14),
                0 2px 6px rgba(37, 99, 235, 0.08); 
        } 

        .deal-card-top-row {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 6px;
            margin-bottom: 6px;
        }
 
        .deal-code { 
            color: #2563eb; 
 
            font-size: 12px; 
            font-weight: 700; 
 
            line-height: 1.3; 
 
            word-break: break-word; 
        } 

        .deal-card-actions {
            display: flex;
            align-items: center;
            gap: 5px;
            flex-shrink: 0;
        }

        .deal-card-checkbox {
            width: 14px;
            height: 14px;
            border: 1px solid #cbd5e1;
            border-radius: 3px;
            accent-color: #2563eb;
            cursor: pointer;
            margin: 0;
        }
 
        .deal-contact-name { 
            color: #1e293b; 
 
            font-size: 11px; 
            font-weight: 600; 
 
            line-height: 1.35; 
            margin-top: 4px;
        } 
 
        .deal-company { 
            margin-top: 2px; 
 
            color: #64748b; 
 
            font-size: 9px; 
            font-weight: 500;
            text-transform: uppercase;
            line-height: 1.3;
        } 
 
        .deal-price-section {
            margin-top: 10px;
        }

        .deal-price-label { 
            font-size: 9px; 
            font-weight: 700;
            color: #94a3b8; 
            text-transform: uppercase; 
            letter-spacing: 0.05em;
 
            display: flex; 
            align-items: center; 
            gap: 5px; 
        } 
 
        .price-eye-btn {
            background: transparent;
            border: 0;
            padding: 0;
            color: #94a3b8;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 14px;
            height: 14px;
        }

        .price-eye-btn:hover {
            color: #2563eb;
        }

        .price-eye-btn svg { 
            width: 12px; 
            height: 12px; 
        } 
 
        .deal-price-value {
            margin-top: 3px;
            min-height: 16px;
            display: flex;
            align-items: center;
        }

        .price-masked { 
            color: #2563eb; 
            font-size: 14px; 
            font-weight: 800; 
            letter-spacing: 2px;
            line-height: 1;
        } 

        .price-numeric {
            color: #2563eb;
            font-size: 11px;
            font-weight: 700;
            line-height: 1.2;
        }
 
        .deal-expected { 
            margin-top: 10px; 
        } 
 
        .deal-expected-label { 
            font-size: 9px; 
            color: #94a3b8; 
            font-weight: 500;
        } 
 
        .deal-expected-value { 
            margin-top: 2px; 
 
            font-size: 10px; 
            font-weight: 500;
            color: #1e293b; 
        } 
 
        .deal-owner { 
            margin-top: 10px; 
            padding-top: 8px; 
 
            border-top: 1px solid #f1f5f9; 
 
            color: #475569; 
 
            font-size: 10px; 
            font-weight: 500; 
 
            display: flex; 
            align-items: center; 
            gap: 6px; 
        } 
 
        .owner-avatar { 
            width: 18px; 
            height: 18px; 
 
            border-radius: 50%; 
 
            background: #eff6ff; 
            color: #2563eb; 
 
            display: inline-flex; 
            align-items: center; 
            justify-content: center; 
 
            font-size: 8px; 
            font-weight: 700; 
 
            flex-shrink: 0; 
        } 

        .owner-name {
            font-size: 10px;
            color: #475569;
            font-weight: 500;
        }
 
 
        /* ========================================================= 
           DEAL MENU 
        ========================================================= */ 
 
        .deal-menu-wrap { 
            position: relative; 
        } 
 
        .deal-dots { 
            width: 20px; 
            height: 16px; 
 
            border: 1px solid #e2e8f0; 
            background: #ffffff; 
 
            color: #64748b; 
 
            border-radius: 4px; 
 
            font-size: 11px; 
            letter-spacing: 0.5px; 
 
            display: flex; 
            align-items: center; 
            justify-content: center; 
            cursor: pointer;
            padding: 0;
            line-height: 1;
        } 
 
        .deal-dots:hover { 
            background: #f8fafc; 
            border-color: #cbd5e1;
            color: #1e293b;
        } 
 
        .deal-menu { 
            position: absolute; 
 
            right: 0; 
            top: 22px; 
 
            width: 130px; 
 
            background: #ffffff; 
 
            border: 1px solid #e2e8f0; 
            border-radius: 7px; 
 
            box-shadow: 0 10px 25px -5px rgba(15, 23, 42, .13); 
 
            padding: 4px 0; 
 
            display: none; 
 
            z-index: 150; 
        } 
 
        .deal-menu.show { 
            display: block; 
        } 
 
        .deal-menu a, 
        .deal-menu button { 
            width: 100%; 
 
            display: block; 
 
            border: 0; 
            background: transparent; 
 
            text-decoration: none; 
            text-align: left; 
 
            padding: 7px 12px; 
 
            font-size: 11px; 
            font-weight: 500;
 
            color: #1e293b; 
            cursor: pointer;
            box-sizing: border-box;
        } 
 
        .deal-menu a:hover, 
        .deal-menu button:hover { 
            background: #f8fafc; 
            color: #0f172a;
        } 
 
        .deal-menu .delete-action { 
            color: #dc2626 !important; 
        } 

        .deal-menu .delete-action:hover { 
            background: #fef2f2 !important; 
        } 
 
 
        /* ========================================================= 
           ADD DEAL DRAWER 
        ========================================================= */ 
 
        .drawer-overlay { 
            position: fixed; 
            inset: 0; 
 
            background: rgba(15, 23, 42, .25); 
 
            opacity: 0; 
            visibility: hidden; 
 
            transition: 
                opacity .2s ease, 
                visibility .2s ease; 
 
            z-index: 900; 
        } 
 
        .drawer-overlay.show { 
            opacity: 1; 
            visibility: visible; 
        } 
 
        .add-deal-drawer { 
            position: fixed; 
 
            top: 0; 
            right: 0; 
            bottom: 0; 
 
            width: 750px; 
            max-width: 92vw; 
 
            background: #ffffff; 
 
            box-shadow: 
                -10px 0 35px rgba(15, 23, 42, .15); 
 
            transform: translateX(100%); 
 
            transition: 
                transform .25s ease; 
 
            z-index: 1000; 
 
            display: flex; 
            flex-direction: column; 
        } 
 
        .add-deal-drawer.show { 
            transform: translateX(0); 
        } 
 
 
        /* ========================================================= 
           DRAWER HEADER 
        ========================================================= */ 
 
        .drawer-header { 
            height: 94px; 
            min-height: 94px; 
 
            padding: 22px 30px; 
 
            border-top: 3px solid #3f3f46; 
            border-bottom: 1px solid #e5e7eb; 
 
            display: flex; 
            align-items: flex-start; 
            justify-content: space-between; 
 
            background: #ffffff; 
        } 
 
        .drawer-header-left { 
            display: flex; 
            align-items: center; 
            gap: 10px; 
        } 
 
        .drawer-title { 
            margin: 0; 
 
            font-size: 23px; 
            font-weight: 600; 
 
            color: #172033; 
        } 
 
        .drawer-subtitle { 
            margin-top: 4px; 
            color: #64748b; 
            font-size: 12px; 
        } 
 
        .drawer-close { 
            width: 32px; 
            height: 32px; 
 
            border: 0; 
            background: transparent; 
 
            border-radius: 7px; 
 
            color: #64748b; 
 
            display: flex; 
            align-items: center; 
            justify-content: center; 
        } 
 
        .drawer-close:hover { 
            background: #f1f5f9; 
            color: #172033; 
        } 
 
        .drawer-close svg { 
            width: 18px; 
            height: 18px; 
        } 
 
 
        /* ========================================================= 
           DRAWER BODY 
        ========================================================= */ 
 
        .drawer-body { 
            flex: 1; 
            min-height: 0; 
 
            overflow: hidden; 
 
            background: #ffffff; 
        } 
 
        .drawer-frame { 
            width: 100%; 
            height: 100%; 
 
            border: 0; 
 
            display: block; 
 
            background: #ffffff; 
        } 
 
 
        /* ========================================================= 
           DELETE MODAL 
        ========================================================= */ 
 
        .modal-backdrop { 
            position: fixed; 
            inset: 0; 
 
            background: rgba(15, 23, 42, .25); 
 
            display: none; 
            align-items: center; 
            justify-content: center; 
 
            z-index: 1200; 
        } 
 
        .modal-backdrop.show { 
            display: flex; 
        } 
 

        .confirm-modal { 
            width: 360px; 
            max-width: calc(100% - 30px); 
 
            background: #ffffff; 
 
            border-radius: 10px; 
 
            border: 1px solid #e2e8f0; 
 
            box-shadow: 
                0 20px 50px rgba(15, 23, 42, .2); 
 
            padding: 20px; 
        } 
 
        .confirm-title { 
            font-size: 15px; 
            font-weight: 600; 
 
            color: #172033; 
        } 
 
        .confirm-text { 
            margin-top: 8px; 
 
            font-size: 11px; 
            line-height: 1.5; 
 
            color: #64748b; 
        } 
 
        .confirm-actions { 
            display: flex; 
            justify-content: flex-end; 
 
            gap: 8px; 
 
            margin-top: 18px; 
        } 
 
        .confirm-cancel { 
            height: 34px; 
 
            padding: 0 13px; 
 
            border: 1px solid #dce2ea; 
 
            background: #ffffff; 
 
            border-radius: 6px; 
 
            color: #475569; 
 
            font-size: 11px; 
        } 
 
        .confirm-delete { 
            height: 34px; 
 
            padding: 0 13px; 
 
            border: 0; 
 
            background: #dc2626; 
 
            border-radius: 6px; 
 
            color: #ffffff; 
 
            font-size: 11px; 
        } 
 
 
        /* ========================================================= 
           FLASH 
        ========================================================= */ 
 
        .flash-message { 
            position: fixed; 
 
            top: 72px; 
            right: 24px; 
 
            background: #ffffff; 
 
            border: 1px solid #dbeafe; 
            border-left: 3px solid #2458d7; 
 
            box-shadow: 
                0 10px 25px rgba(15, 23, 42, .1); 
 
            border-radius: 8px; 
 
            padding: 11px 14px; 
 
            font-size: 11px; 
 
            color: #475569; 
 
            z-index: 1300; 
        } 
 
 
        /* ========================================================= 
           RESPONSIVE 
        ========================================================= */ 
 
        .mobile-stage-tabs {
            display: none;
        }

        @media (max-width: 1000px) { 
             .mobile-stage-tabs {
               display: none;
             }
            .header-search { 
                width: 350px; 
            } 
 
            .kanban-column { 
                width: 220px; 
                min-width: 220px; 
            } 
 
            .add-deal-drawer { 
                width: 600px; 
            } 
 
        } 
 
        @media (max-width: 700px) { 

            .mobile-stage-tabs {
                display: flex;
                gap: 8px;
                overflow-x: auto;
                padding: 10px 14px;
                background: #ffffff;
                border-top: 1px solid #e5e7eb;
                border-bottom: 1px solid #e5e7eb;
                scrollbar-width: none;
            }

            .mobile-stage-tabs::-webkit-scrollbar {
                display: none;
            }

            .mobile-stage-tab {
                flex: 0 0 auto;
                border: 1px solid #d8dee8;
                background: #ffffff;
                color: #475569;
                border-radius: 999px;
                padding: 7px 12px;
                font-size: 10px;
                font-weight: 600;
                cursor: pointer;
                white-space: nowrap;
            }

            .mobile-stage-tab.active {
                background: #1e2b3f;
                color: #ffffff;
                border-color: #1e2b3f;
            }

            .kanban-scroll {
                overflow-x: hidden;
                overflow-y: auto;
                padding: 14px;
            }

            .kanban {
                display: block;
                min-width: 0;
                height: auto;
            }

            .kanban-column {
                display: none;
                width: 100%;
                min-width: 0;
            }

            .kanban-column.mobile-active {
                display: block;
            }

            .deal-card {
                width: 100%;
            }
 
            .top-header { 
                padding: 0 12px; 
            } 
 
            .brand { 
                min-width: auto; 
            } 
 
            .brand-name { 
                font-size: 13px; 
            } 
 
            .brand-subtitle { 
                display: none; 
            } 
 
            .header-search { 
                display: none; 
            } 
 
            .rail { 
                width: 54px; 
                min-width: 54px; 
            } 
 
            .page-header { 
                padding: 18px 14px 10px; 
            } 
 
            .filters { 
                padding-left: 14px; 
                padding-right: 14px; 
 
                flex-wrap: wrap; 
            } 
 
            .search-box-wrapper { 
                width: 100%; 
            } 
            .search-typeahead-dropdown { 
                width: 100%; 
            } 
 
            .kanban-scroll { 
                padding-left: 14px; 
                padding-right: 14px; 
            } 
 
            .add-deal-drawer { 
                width: 100%; 
                max-width: 100%; 
            } 
 
        } 

        /* ========================================================= 
           DRAG AND DROP KANBAN STYLES 
        ========================================================= */ 
        .deal-card[draggable="true"] { 
            cursor: grab; 
            user-select: none; 
            -webkit-user-drag: element; 
        } 

        .deal-card.is-dragging { 
            opacity: 0.4; 
            cursor: grabbing !important; 
            transform: scale(0.98); 
            box-shadow: 0 8px 24px rgba(15, 23, 42, 0.14); 
        } 

        .column-content.drag-over { 
            background: rgba(37, 99, 235, 0.04); 
            border-radius: 6px; 
            outline: 2px dashed #93c5fd; 
            outline-offset: -2px; 
        } 

        .kanban-drop-placeholder { 
            background: #eff6ff; 
            border: 1.5px dashed #3b82f6; 
            border-radius: 8px; 
            min-height: 64px; 
            margin-bottom: 8px; 
            display: flex; 
            align-items: center; 
            justify-content: center; 
            color: #2563eb; 
            font-size: 11px; 
            font-weight: 600; 
            letter-spacing: 0.2px; 
            pointer-events: none; 
            transition: all 0.15s ease; 
        } 

        .deal-card.is-updating { 
            pointer-events: none; 
            opacity: 0.6; 
            position: relative; 
        } 

        .deal-card.is-updating::after { 
            content: ''; 
            position: absolute; 
            top: 0; 
            left: 0; 
            right: 0; 
            bottom: 0; 
            background: rgba(255, 255, 255, 0.65) url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='20' height='20' viewBox='0 0 24 24' fill='none' stroke='%232563eb' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='M21 12a9 9 0 1 1-6.219-8.56'%3E%3C/path%3E%3C/svg%3E") no-repeat center center; 
            border-radius: 8px; 
            animation: spinUpdating 0.8s linear infinite; 
        } 

        @keyframes spinUpdating { 
            from { transform: rotate(0deg); } 
            to { transform: rotate(360deg); } 
        } 

        .kanban-toast { 
            position: fixed; 
            top: 24px; 
            right: 24px; 
            z-index: 99999; 
            min-width: 320px; 
            max-width: 440px; 
            padding: 14px 16px; 
            border-radius: 12px; 
            background: #ffffff;
            color: #0f172a;
            border: 1px solid #e2e8f0;
            font-size: 12.5px; 
            font-weight: 500; 
            box-shadow: 0 12px 30px -4px rgba(15, 23, 42, 0.15), 0 4px 12px -2px rgba(15, 23, 42, 0.08); 
            display: flex; 
            align-items: center; 
            gap: 12px; 
            transition: opacity 0.25s cubic-bezier(0.16, 1, 0.3, 1), transform 0.25s cubic-bezier(0.16, 1, 0.3, 1); 
            opacity: 0; 
            transform: translateY(-12px) scale(0.96); 
            pointer-events: none; 
            line-height: 1.4; 
        } 

        .kanban-toast.show { 
            opacity: 1; 
            transform: translateY(0) scale(1); 
            pointer-events: auto; 
        } 

        .kanban-toast.toast-error { 
            border-color: #fecaca;
        } 

        .kanban-toast.toast-success { 
            border-color: #bbf7d0;
        } 

        .kanban-toast.toast-info { 
            border-color: #bfdbfe;
        } 

    </style> 
 
</head> 
 
 
<body> 
 
 
<div class="app"> 
 
 
    {{-- ========================================================= 
         TOP HEADER 
    ========================================================== --}} 
 
    <header class="top-header"> 
 
 
        <div class="brand"> 
 
            <div class="brand-logo"> 
 
                @if(file_exists(public_path('images/logo.png'))) 
 
                    <img 
                        src="{{ asset('images/logo.png') }}" 
                        alt="John Kelly & Company" 
                    > 
 
                @else 
 
                    <div class="brand-text"> 
 
                        <div class="brand-name"> 
                            John Kelly 
                        </div> 
 
                        <div class="brand-name"> 
                            Company 
                        </div> 
 
                        <div class="brand-subtitle"> 
                            JK&C 
                        </div> 
 
                    </div> 
 
                @endif 
 
            </div> 
 
        </div> 
 
 
        {{-- HEADER SEARCH --}} 
 
        <div class="header-search"> 
 
            <svg 
                viewBox="0 0 24 24" 
                fill="none" 
                stroke="currentColor" 
                stroke-width="2" 
            > 
 
                <circle 
                    cx="11" 
                    cy="11" 
                    r="7" 
                ></circle> 
 
                <path 
                    d="m20 20-3.5-3.5" 
                ></path> 
 
            </svg> 
 
            <input 
                type="text" 
                placeholder="Search" 
            > 
 
        </div> 
 
 
        <div class="header-actions"> 
 
 
            {{-- ================================================= 
                 NOTIFICATIONS 
            ================================================== --}} 
 
            <div class="notification-wrap"> 
 
 
                <button 
                    type="button" 
                    class="notification-button" 
                    id="notificationButton" 
                    aria-label="Notifications" 
                > 
 
                    <svg 
                        viewBox="0 0 24 24" 
                        fill="none" 
                        stroke="currentColor" 
                        stroke-width="1.8" 
                    > 
 
                        <path 
                            d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9" 
                        ></path> 
 
                        <path 
                            d="M10 21h4" 
                        ></path> 
 
                    </svg> 
 
 
                    @php 
 
                        $unreadNotifications = 0; 
 
                        if (class_exists(\App\Models\Notification::class)) { 
 
                            try { 
 
                                $unreadNotifications = 
                                    \App\Models\Notification::whereNull('read_at')->count(); 
 
                            } catch (\Throwable $e) { 
 
                                $unreadNotifications = 0; 
 
                            } 
 
                        } 
 
                    @endphp 
 
 
                    @if($unreadNotifications > 0) 
 
                        <span class="notif-count"> 
 
                            {{ $unreadNotifications > 9 ? '9+' : $unreadNotifications }} 
 
                        </span> 
 
                    @endif 
 
                </button> 
 
 
                <div 
                    class="notification-panel" 
                    id="notificationPanel" 
                > 
 
                    <div class="notification-header"> 
 
                        <strong> 
                            Notifications 
                        </strong> 
 
 
                        @if(Route::has('notifications.read-all')) 
 
                            <form 
                                method="POST" 
                                action="{{ route('notifications.read-all') }}" 
                            > 
 
                                @csrf 
 
                                <button type="submit"> 
                                    Mark all as read 
                                </button> 
 
                            </form> 
 
                        @endif 
 
                    </div> 
 
 
                    <div class="notification-list"> 
 
                        @if(class_exists(\App\Models\Notification::class)) 
 
                            @php 
 
                                try { 
 
                                    $notifications = 
                                        \App\Models\Notification::latest() 
                                            ->take(20) 
                                            ->get(); 
 
                                } catch (\Throwable $e) { 
 
                                    $notifications = collect(); 
 
                                } 
 
                            @endphp 
 
 
                            @forelse($notifications as $notification) 
 
                                @if(Route::has('notifications.read')) 
 
                                    <form 
                                        method="POST" 
                                        action="{{ route('notifications.read', $notification->id) }}" 
                                        class="notification-item {{ empty($notification->read_at) ? 'unread' : '' }}" 
                                    > 
 
                                        @csrf 
 
                                        <button 
                                            type="submit" 
                                            style=" 
                                                width:100%; 
                                                border:0; 
                                                background:transparent; 
                                                text-align:left; 
                                                padding:0; 
                                                color:inherit; 
                                            " 
                                        > 
 
                                            {{ $notification->message ?? $notification->title ?? 'New notification' }} 
 
                                        </button> 
 
                                    </form> 
 
                                @else 
 
                                    <div 
                                        class="notification-item {{ empty($notification->read_at) ? 'unread' : '' }}" 
                                    > 
 
                                        {{ $notification->message ?? $notification->title ?? 'New notification' }} 
 
                                    </div> 
 
                                @endif 
 
                            @empty 
 
                                <div class="notification-empty"> 
                                    No notifications. 
                                </div> 
 
                            @endforelse 
 
                        @else 
 
                            <div class="notification-empty"> 
                                No notifications. 
                            </div> 
 
                        @endif 
 
                    </div> 
 
                </div> 
 
            </div> 
 
 
            {{-- PROFILE --}} 
 
            <div class="avatar"> 
 
                {{ strtoupper(substr(auth()->user()?->name ?? 'A', 0, 1)) }} 
 
            </div> 
 
        </div> 
 
    </header> 
 
 
    {{-- ========================================================= 
         WORKSPACE 
    ========================================================== --}} 
 
    <div class="workspace"> 
 
 
        {{-- ===================================================== 
             LEFT ICON RAIL 
        ====================================================== --}} 
 
        <aside class="rail"> 
 
 
            {{-- Dashboard --}} 
 
            <div class="rail-icon"> 
 
                <svg 
                    viewBox="0 0 24 24" 
                    fill="none" 
                    stroke="currentColor" 
                    stroke-width="1.8" 
                > 
 
                    <rect 
                        x="4" 
                        y="4" 
                        width="6" 
                        height="6" 
                        rx="1" 
                    ></rect> 
 
                    <rect 
                        x="14" 
                        y="4" 
                        width="6" 
                        height="6" 
                        rx="1" 
                    ></rect> 
 
                    <rect 
                        x="4" 
                        y="14" 
                        width="6" 
                        height="6" 
                        rx="1" 
                    ></rect> 
 
                    <rect 
                        x="14" 
                        y="14" 
                        width="6" 
                        height="6" 
                        rx="1" 
                    ></rect> 
 
                </svg> 
 
            </div> 
 
 
            {{-- Contacts --}} 
 
            <div class="rail-icon"> 
 
                <svg 
                    viewBox="0 0 24 24" 
                    fill="none" 
                    stroke="currentColor" 
                    stroke-width="1.8" 
                > 
 
                    <circle 
                        cx="9" 
                        cy="8" 
                        r="3" 
                    ></circle> 
 
                    <path 
                        d="M3 19c0-3 2.5-5 6-5s6 2 6 5" 
                    ></path> 
 
                    <path 
                        d="M16 5.5c2.2.3 3.5 1.5 3.5 3.5" 
                    ></path> 
 
                    <path 
                        d="M17 14c2.5.4 4 2 4 4" 
                    ></path> 
 
                </svg> 
 
            </div> 
 
 
            {{-- Marketing --}} 
 
            <div class="rail-icon"> 
 
                <svg 
                    viewBox="0 0 24 24" 
                    fill="none" 
                    stroke="currentColor" 
                    stroke-width="1.8" 
                > 
 
                    <path d="M4 11v2"></path> 
 
                    <path d="M7 9l10-4v14L7 15z"></path> 
 
                    <path d="M7 15v5"></path> 
 
                    <path d="M17 9h3v6h-3"></path> 
 
                </svg> 
 
            </div> 
 
 
            {{-- Records --}} 
 
            <div class="rail-icon"> 
 
                <svg 
                    viewBox="0 0 24 24" 
                    fill="none" 
                    stroke="currentColor" 
                    stroke-width="1.8" 
                > 
 
                    <rect 
                        x="5" 
                        y="3" 
                        width="14" 
                        height="18" 
                        rx="2" 
                    ></rect> 
 
                    <path d="M8 7h8"></path> 
 
                    <path d="M8 11h8"></path> 
 
                    <path d="M8 15h5"></path> 
 
                </svg> 
 
            </div> 
 
 
            {{-- Documents --}} 
 
            <div class="rail-icon"> 
 
                <svg 
                    viewBox="0 0 24 24" 
                    fill="none" 
                    stroke="currentColor" 
                    stroke-width="1.8" 
                > 
 
                    <path d="M6 3h9l4 4v14H6z"></path> 
 
                    <path d="M15 3v5h4"></path> 
 
                    <path d="M9 12h6"></path> 
 
                    <path d="M9 16h6"></path> 
 
                </svg> 
 
            </div> 
 
 
            {{-- Finance --}} 
 
            <div class="rail-icon"> 
 
                <svg 
                    viewBox="0 0 24 24" 
                    fill="none" 
                    stroke="currentColor" 
                    stroke-width="1.8" 
                > 
 
                    <rect 
                        x="4" 
                        y="5" 
                        width="16" 
                        height="14" 
                        rx="2" 
                    ></rect> 
 
                    <path d="M4 9h16"></path> 
 
                    <path d="M8 14h4"></path> 
 
                </svg> 
 
            </div> 
 
 
            {{-- Users --}} 
 
            <div class="rail-icon"> 
 
                <svg 
                    viewBox="0 0 24 24" 
                    fill="none" 
                    stroke="currentColor" 
                    stroke-width="1.8" 
                > 
 
                    <circle 
                        cx="12" 
                        cy="8" 
                        r="3" 
                    ></circle> 
 
                    <path 
                        d="M5 20c0-4 3-6 7-6s7 2 7 6" 
                    ></path> 
 
                </svg> 
 
            </div> 
 
 
            {{-- Settings --}} 
 
            <div class="rail-icon"> 
 
                <svg 
                    viewBox="0 0 24 24" 
                    fill="none" 
                    stroke="currentColor" 
                    stroke-width="1.8" 
                > 
 
                    <circle 
                        cx="12" 
                        cy="12" 
                        r="3" 
                    ></circle> 
 
                    <path 
                        d="M19.4 15a1.8 1.8 0 0 0 .3 2l.1.1-1.8 1.8-.1-.1a1.8 1.8 0 0 0-2-.3 1.8 1.8 0 0 0-1.1 1.7v.2h-2.6v-.2a1.8 1.8 0 0 0-1.1-1.7 1.8 1.8 0 0 0-2 .3l-.1.1-1.8-1.8.1-.1a1.8 1.8 0 0 0 .3-2A1.8 1.8 0 0 0 6 14H5.8v-2.6H6a1.8 1.8 0 0 0 1.7-1.1 1.8 1.8 0 0 0-.3-2l-.1-.1 1.8-1.8.1.1a1.8 1.8 0 0 0 2 .3A1.8 1.8 0 0 0 12.3 5v-.2h2.6V5a1.8 1.8 0 0 0 1.1 1.7 1.8 1.8 0 0 0 2-.3l.1-.1 1.8 1.8-.1.1a1.8 1.8 0 0 0-.3 2 1.8 1.8 0 0 0 1.7 1.1h.2V14h-.2a1.8 1.8 0 0 0-1.8 1z" 
                    ></path> 
 
                </svg> 
 
            </div> 
 
 
            {{-- DEALS ACTIVE --}} 
 
            <div class="rail-icon active"> 
 
                <svg 
                    viewBox="0 0 24 24" 
                    fill="none" 
                    stroke="currentColor" 
                    stroke-width="1.8" 
                > 
 
                    <path d="M4 18V6"></path> 
 
                    <path d="M4 18h16"></path> 
 
                    <path d="M7 14l3-3 3 2 5-6"></path> 
 
                </svg> 
 
            </div> 
 
 
            {{-- More --}} 
 
            <div class="rail-icon"> 
 
                <svg 
                    viewBox="0 0 24 24" 
                    fill="none" 
                    stroke="currentColor" 
                    stroke-width="1.8" 
                > 
 
                    <circle 
                        cx="5" 
                        cy="12" 
                        r="1" 
                    ></circle> 
 
                    <circle 
                        cx="12" 
                        cy="12" 
                        r="1" 
                    ></circle> 
 
                    <circle 
                        cx="19" 
                        cy="12" 
                        r="1" 
                    ></circle> 
 
                </svg> 
 
            </div> 
 
        </aside> 
 
 
        {{-- ===================================================== 
             MAIN PAGE 
        ====================================================== --}} 
 
        <main class="main"> 
 
            <section class="page"> 
 
 
                {{-- ================================================= 
                     PAGE HEADER 
                ================================================== --}} 
 
                <div class="page-header"> 
 
                    <div> 
 
                        <h1 class="page-title"> 
                            Deals 
                        </h1> 
 
                        <div class="page-subtitle"> 
                            Track and manage your sales pipeline 
                        </div> 
 
                    </div> 
 
 
                    <div style="display: flex; align-items: center; gap: 8px;">
                        {{-- ADD INQUIRY --}}
                        <button 
                            type="button" 
                            class="quick-purchase-button" 
                            id="quickPurchaseButton"
                            data-tooltip="Create a new inquiry for a client."
                        >
                            Add Inquiry
                        </button>

                        {{-- ADD DEAL --}} 
                        <button 
                            type="button" 
                            class="primary-button" 
                            id="addDealButton" 
                            data-tooltip="Create a new deal record."
                        > 
                            <svg 
                                viewBox="0 0 24 24" 
                                fill="none" 
                                stroke="currentColor" 
                                stroke-width="2" 
                            > 
                                <path d="M12 5v14"></path> 
                                <path d="M5 12h14"></path> 
                            </svg> 
                            Add Deal 
                        </button> 
                    </div>

                </div> 
 
 
                {{-- ================================================= 
                     FILTERS 
                ================================================== --}} 
 
                <form 
                    method="GET" 
                    action="{{ route('deals.index') }}" 
                    class="filters" 
                    id="dealsFilterForm"
                > 

                    <div class="search-box-wrapper" id="dealSearchWrapper">
                        <div class="search-input-inner">
                            <svg class="search-icon" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M9 3.5a5.5 5.5 0 100 11 5.5 5.5 0 000-11zM2 9a7 7 0 1112.452 4.391l3.328 3.329a.75.75 0 11-1.06 1.06l-3.329-3.328A7 7 0 012 9z" clip-rule="evenodd" />
                            </svg>
                            <input 
                                type="text" 
                                name="search" 
                                id="dealSearchInput"
                                class="search-box" 
                                value="{{ request('search') }}" 
                                placeholder="Search code, deal, or contact" 
                                autocomplete="off"
                            >
                            <button type="button" class="search-clear-btn" id="searchClearBtn" style="display: {{ request('search') ? 'flex' : 'none' }};" title="Clear search" aria-label="Clear search">&times;</button>
                            <span class="search-spinner" id="searchSpinner" style="display: none;"></span>
                        </div>

                        <!-- Typeahead Dropdown -->
                        <div class="search-typeahead-dropdown" id="searchTypeaheadDropdown" style="display: none;">
                            <div class="typeahead-header">
                                <span>SUGGESTED CLIENTS</span>
                                <span class="typeahead-hint">↑↓ to navigate, Enter to view</span>
                            </div>
                            <div class="typeahead-results" id="typeaheadResultsList">
                                <!-- Dynamic Items rendered via JS -->
                            </div>
                            <div class="typeahead-footer" id="typeaheadFooter">
                                <a href="javascript:void(0)" class="typeahead-footer-action" id="typeaheadSearchAllBtn">
                                    <span>Press <kbd>Enter</kbd> to search all deals for "<strong id="typeaheadQueryLabel"></strong>"</span>
                                </a>
                            </div>
                        </div>
                    </div> 


                    <select 
                        name="deal_filter" 
                        class="filter-select" 
                        onchange="this.form.submit()"
                    > 

                        <option value=""> 
                            All Stage Deals 
                        </option> 

                        <option 
                            value="my_deals" 
                            {{ request('deal_filter') === 'my_deals' ? 'selected' : '' }} 
                        > 
                            My Deals 
                        </option> 

                        @foreach($stages as $stage)
                            <option 
                                value="{{ $stage }}" 
                                {{ request('deal_filter') === $stage ? 'selected' : '' }}
                            >
                                {{ $stage }}
                            </option>
                        @endforeach

                    </select> 


                    <select 
                        name="date_filter" 
                        class="filter-select" 
                        onchange="this.form.submit()"
                    > 

                        <option value="created_date" {{ request('date_filter', 'created_date') === 'created_date' ? 'selected' : '' }}> 
                            Created Date 
                        </option> 

                        <option 
                            value="updated_date" 
                            {{ request('date_filter') === 'updated_date' ? 'selected' : '' }} 
                        > 
                            Last Updated 
                        </option> 

                        <option 
                            value="today" 
                            {{ request('date_filter') === 'today' ? 'selected' : '' }} 
                        > 
                            Today 
                        </option> 

                        <option 
                            value="this_week" 
                            {{ request('date_filter') === 'this_week' ? 'selected' : '' }} 
                        > 
                            This Week 
                        </option> 

                        <option 
                            value="this_month" 
                            {{ request('date_filter') === 'this_month' ? 'selected' : '' }} 
                        > 
                            This Month 
                        </option> 

                        <option 
                            value="this_year" 
                            {{ request('date_filter') === 'this_year' ? 'selected' : '' }} 
                        > 
                            This Year 
                        </option> 

                    </select> 


                    <button 
                        type="submit" 
                        class="filter-button" 
                    > 
                        Search 
                    </button> 


                    <span class="deal-count" id="totalDealsCount"> 

                        {{ $deals->count() }} 

                        {{ $deals->count() === 1 ? 'deal' : 'deals' }} 

                    </span> 

                </form> 
 
 
                {{-- ================================================= 
                     KANBAN 
                ================================================== --}} 
 
                <div class="kanban-scroll">
                 {{-- MOBILE STAGE TABS --}}
                        <div class="mobile-stage-tabs">

                            @foreach($stages as $stage)
                                <button
                                    type="button"
                                    class="mobile-stage-tab"
                                    onclick="showMobileStage(@js($stage), this)"
                                >
                                    {{ $stage }}
                                </button>
                            @endforeach

                        </div>
 
                    <div class="kanban"> 
 
                        @php 
 
                            $stageColors = [ 
 
                                'Inquiry'       => '#2458d7', 
                                'Qualification' => '#5d43d7', 
                                'Consultation'  => '#0d8797', 
                                'Proposal'      => '#d87b10', 
                                'Negotiation'   => '#dd5b21', 
                                'Payment'       => '#c43d71', 
                                'Activation'    => '#23834b', 
                                'Closed Won'    => '#16805b', 
                                'Closed Lost'   => '#64748b', 
 
                            ]; 

                            $stageTooltipsIndex = [
                                'Inquiry'       => "Initial stage where the client's request or opportunity is recorded.",
                                'Qualification' => "Confirm whether the client, need, and opportunity meet the applicable qualification requirements.",
                                'Consultation'  => "Stage for discussing the client's requirements and proposed approach.",
                                'Proposal'      => "Stage where the applicable services and commercial proposal are prepared or reviewed.",
                                'Negotiation'   => "Stage where commercial terms or requested changes are being discussed.",
                                'Payment'       => "Stage for completing the applicable payment requirements.",
                                'Activation'    => "Stage where the approved service moves into activation.",
                                'Closed Won'    => "Deal has been successfully completed and won.",
                                'Closed Lost'   => "Deal has been closed without proceeding.",
                            ];
 
                        @endphp 
 
 
                        @foreach($stages as $stage) 
 
                            @php 
 
                                $stageDeals = 
                                    $deals->filter(function ($deal) use ($stage) { 
 
                                        return $deal->pipeline_stage === $stage; 
 
                                    }); 
 
 
                                $stageTotal = 
                                    $stageDeals->sum(function ($deal) { 
 
                                        return (float) ( 
 
                                            $deal->total_estimated_engagement_value 
                                            ?? $deal->amount 
                                            ?? 0 
 
                                        ); 
 
                                    }); 
 
                            @endphp 
 
 
                            <div class="kanban-column" data-stage="{{ $stage }}"> 
 
 
                                {{-- STAGE HEADER --}} 
 
                                <div 
                                    class="column-header" 
                                    data-tooltip="{{ $stageTooltipsIndex[$stage] ?? '' }}"
                                    style=" 
                                        border-top-color: 
                                        {{ $stageColors[$stage] ?? '#2458d7' }}; 
                                    " 
                                > 
 
                                    <div class="column-title-row"> 
 
                                        <div class="column-title"> 
                                            {{ $stage }} 
                                        </div> 
 
                                        <div class="stage-header-actions"> 
 
                                            <input 
                                                type="checkbox" 
                                                class="stage-checkbox" 
                                                onclick="event.stopPropagation();" 
                                                aria-label="Select stage" 
                                            > 
 
                                            <div class="stage-menu-wrap"> 
 
                                                <button 
                                                    type="button" 
                                                    class="dots-button stage-dots" 
                                                    aria-label="Stage actions" 
                                                > 
                                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"> 
                                                        <circle cx="12" cy="5" r="2.2"></circle> 
                                                        <circle cx="12" cy="12" r="2.2"></circle> 
                                                        <circle cx="12" cy="19" r="2.2"></circle> 
                                                    </svg> 
                                                </button> 
 
 
                                                <div class="stage-menu"> 
 
                                                    <button 
                                                        type="button" 
                                                        onclick="closeAllMenus()" 
                                                    > 
                                                        New Stage 
                                                    </button> 
 
                                                    <button 
                                                        type="button" 
                                                        onclick="moveStageLeft(this); closeAllMenus()" 
                                                    > 
                                                        Move Left 
                                                    </button> 
 
                                                    <button 
                                                        type="button" 
                                                        onclick="moveStageRight(this); closeAllMenus()" 
                                                    > 
                                                        Move Right 
                                                    </button> 
 
                                                    <button 
                                                        type="button" 
                                                        onclick="renameStage(this); closeAllMenus()" 
                                                    > 
                                                        Rename Stage 
                                                    </button> 
 
                                                    <div class="stage-menu-section-label"> 
                                                        CHANGE STAGE COLOR 
                                                    </div> 
 
                                                    <div class="stage-color-palette"> 
                                                        <button type="button" class="color-dot" style="background: #2563eb;" onclick="setStageColor(this, '#2563eb')" title="Blue" aria-label="Blue"></button> 
                                                        <button type="button" class="color-dot" style="background: #5d43d7;" onclick="setStageColor(this, '#5d43d7')" title="Indigo" aria-label="Indigo"></button> 
                                                        <button type="button" class="color-dot" style="background: #0d8797;" onclick="setStageColor(this, '#0d8797')" title="Cyan" aria-label="Cyan"></button> 
                                                        <button type="button" class="color-dot" style="background: #d87b10;" onclick="setStageColor(this, '#d87b10')" title="Amber" aria-label="Amber"></button> 
                                                        <button type="button" class="color-dot" style="background: #dd5b21;" onclick="setStageColor(this, '#dd5b21')" title="Orange" aria-label="Orange"></button> 
                                                        <button type="button" class="color-dot" style="background: #23834b;" onclick="setStageColor(this, '#23834b')" title="Green" aria-label="Green"></button> 
                                                        <button type="button" class="color-dot" style="background: #8b5cf6;" onclick="setStageColor(this, '#8b5cf6')" title="Purple" aria-label="Purple"></button> 
                                                        <button type="button" class="color-dot" style="background: #dc2626;" onclick="setStageColor(this, '#dc2626')" title="Red" aria-label="Red"></button> 
                                                    </div> 
 
                                                    <div class="stage-menu-divider"></div> 
 
                                                    <button 
                                                        type="button" 
                                                        class="delete-action" 
                                                        onclick="closeAllMenus()" 
                                                    > 
                                                        Delete Stage 
                                                    </button> 
 
                                                </div> 
 
                                            </div> 
 
                                        </div> 
 
                                    </div> 
 
 
                                    <div class="column-meta" data-stage-meta="{{ $stage }}"> 
                                        P{{ number_format($stageTotal, 0) }} • {{ $stageDeals->count() }} {{ $stageDeals->count() === 1 ? 'Deal' : 'Deals' }} 
                                    </div> 
 
                                </div> 
 
 
                                {{-- STAGE CONTENT --}} 
 
                                <div class="column-content" data-stage="{{ $stage }}"> 
 
                                    @forelse($stageDeals as $deal) 
                                        @php
                                            $cardCompany = trim((string)($deal->company_name ?: $deal->company));
                                            $cardContact = trim(($deal->first_name ?? '') . ' ' . ($deal->last_name ?? ''));
                                            if (!$cardContact) {
                                                $cardContact = trim((string)($deal->primary_contact_name ?: ($deal->client_search ?: '')));
                                            }
                                            $cardMainClient = $cardCompany ?: ($cardContact ?: ($deal->account?->account_name ?: ''));
                                            $cardSearchTerms = array_unique(array_filter([
                                                $cardMainClient,
                                                $cardCompany,
                                                $cardContact,
                                                $deal->account?->account_name,
                                            ]));
                                        @endphp

                                        <div 
                                            class="deal-card" 
                                            draggable="true" 
                                            data-deal-card 
                                            data-deal-id="{{ $deal->id }}" 
                                            data-stage="{{ $stage }}" 
                                            data-deal-amount="{{ (float)($deal->total_estimated_engagement_value ?? $deal->amount ?? 0) }}" 
                                            data-customer-name="{{ strtolower($cardMainClient) }}"
                                            data-customer-search="{{ strtolower(implode(' | ', $cardSearchTerms)) }}"
                                            data-search="{{ strtolower(implode(' | ', $cardSearchTerms)) }}" 
                                            onclick="openDeal('{{ route('deals.show', $deal->id) }}', event)" 
                                        > 
 
 
                                            {{-- TOP ROW: CODE + RIGHT ACTIONS --}} 
                                            <div class="deal-card-top-row"> 
                                                <div class="deal-code"> 
                                                    {{ $deal->deal_code ?: 'CONDEAL-' . date('Y') . '-' . str_pad($deal->id, 3, '0', STR_PAD_LEFT) }} 
                                                </div> 
 
                                                <div class="deal-card-actions"> 
                                                    <input 
                                                        type="checkbox" 
                                                        class="deal-card-checkbox" 
                                                        onclick="event.stopPropagation();" 
                                                        aria-label="Select deal" 
                                                    > 
 
                                                    <div class="deal-menu-wrap"> 
                                                        <button 
                                                            type="button" 
                                                            class="deal-dots" 
                                                            onclick="toggleDealMenu(event, this)" 
                                                            aria-label="Deal actions" 
                                                        > 
                                                            ... 
                                                        </button> 
 
                                                        <div class="deal-menu"> 
                                                            <a 
                                                                href="{{ route('deals.show', $deal->id) }}" 
                                                                data-tooltip="Open the complete deal record and its current status."
                                                                onclick="event.stopPropagation();" 
                                                            > 
                                                                View Deal 
                                                            </a> 

                                                            <button 
                                                                type="button" 
                                                                data-tooltip="Start a new inquiry for an existing client using verified client data."
                                                                onclick="openQuickPurchaseDrawer({{ $deal->account_id ?? 'null' }}); event.stopPropagation();"
                                                                style="color: #2563eb; font-weight: 600;"
                                                            > 
                                                                Add Inquiry 
                                                            </button> 

                                                            <button 
                                                                type="button" 
                                                                data-tooltip="Update the deal information that is still editable."
                                                                onclick="openEditDealDrawer({{ $deal->id }}, event);" 
                                                            > 
                                                                Edit Deal 
                                                            </button> 

                                                            <button 
                                                                type="button" 
                                                                class="delete-action" 
                                                                onclick=" 
                                                                    event.stopPropagation(); 
                                                                    openDeleteModal( 
                                                                        @js($deal->id), 
                                                                        @js($deal->deal_title ?: $deal->deal_code ?: 'this deal') 
                                                                    ); 
                                                                " 
                                                            > 
                                                                Delete Deal 
                                                            </button> 
                                                        </div> 
                                                    </div> 
                                                </div> 
                                            </div> 
 
 
                                            {{-- CONTACT NAME --}} 
                                            <div class="deal-contact-name"> 
                                                @if($deal->primary_contact_name) 
                                                    {{ $deal->primary_contact_name }} 
                                                @elseif($deal->first_name || $deal->last_name) 
                                                    {{ trim(($deal->first_name ?? '') . ' ' . ($deal->last_name ?? '')) }} 
                                                @elseif($deal->deal_title) 
                                                    {{ $deal->deal_title }} 
                                                @else 
                                                    Untitled Deal 
                                                @endif 
                                            </div> 
 
 
                                            {{-- COMPANY --}} 
                                            <div class="deal-company"> 
                                                @if($deal->company_name) 
                                                    {{ $deal->company_name }} 
                                                @elseif($deal->company) 
                                                    {{ $deal->company }} 
                                                @endif 
                                            </div> 
 
 
                                            {{-- PRICE --}} 
                                            <div class="deal-price-section"> 
                                                <div class="deal-price-label"> 
                                                    <span> 
                                                        PRICE 
                                                    </span> 
 
                                                    <button 
                                                        type="button" 
                                                        class="price-eye-btn" 
                                                        onclick="togglePriceMask(event, this)" 
                                                        title="Show/Hide Price" 
                                                        aria-label="Toggle price visibility" 
                                                    > 
                                                        <svg 
                                                            viewBox="0 0 24 24" 
                                                            fill="none" 
                                                            stroke="currentColor" 
                                                            stroke-width="1.8" 
                                                        > 
                                                            <path 
                                                                d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6S2 12 2 12z" 
                                                            ></path> 
                                                            <circle 
                                                                cx="12" 
                                                                cy="12" 
                                                                r="2" 
                                                            ></circle> 
                                                        </svg> 
                                                    </button> 
                                                </div> 
 
                                                <div class="deal-price-value"> 
                                                    <div class="price-masked">••••••</div> 
                                                    <div class="price-numeric" style="display: none;"> 
                                                        P{{ number_format( 
                                                            (float) ( 
                                                                $deal->total_estimated_engagement_value 
                                                                ?? $deal->amount 
                                                                ?? 0 
                                                            ), 
                                                            0 
                                                        ) }} 
                                                    </div> 
                                                </div> 
                                            </div> 
 
 
                                            {{-- EXPECTED CLOSE --}} 
                                            <div class="deal-expected"> 
                                                <div class="deal-expected-label"> 
                                                    Expected Close 
                                                </div> 
 
                                                <div class="deal-expected-value"> 
                                                    @if($deal->expected_close) 
                                                        {{ \Carbon\Carbon::parse($deal->expected_close)->format('M d, Y') }} 
                                                    @else 
                                                        TBD 
                                                    @endif 
                                                </div> 
                                            </div> 
 
 
                                            {{-- OWNER --}} 
                                            <div class="deal-owner"> 
                                                <span class="owner-avatar"> 
                                                    {{ strtoupper(substr($deal->owner_name ?: ($deal->user?->name ?: 'UN'), 0, 2)) }} 
                                                </span> 
 
                                                <span class="owner-name"> 
                                                    {{ $deal->owner_name ?: ($deal->user?->name ?: 'Unassigned') }} 
                                                </span> 
                                            </div> 
 
                                        </div> 
 
                                    @empty 
 
                                        <div class="empty-stage"> 
                                            No deals in this stage. 
                                        </div> 
 
                                    @endforelse 
 
                                </div> 
 
 
                                <div class="stage-description"> 
                                    No description yet. 
                                </div> 
 
                            </div> 
 
                        @endforeach 
 
                    </div> 
 
                </div> 
 
            </section> 
 
        </main> 
 
    </div> 
 
</div> 
 
 
 
{{-- ============================================================= 
     ADD DEAL DRAWER OVERLAY 
============================================================= --}} 
 
<div 
    class="drawer-overlay" 
    id="drawerOverlay" 
></div> 
 
 
 
{{-- ============================================================= 
     ADD DEAL DRAWER 
============================================================= --}} 
 
<aside 
    class="add-deal-drawer" 
    id="addDealDrawer" 
> 
 
    <div class="drawer-header"> 
        <div class="drawer-header-left"> 
            <div> 
                <h2 class="drawer-title" id="drawerTitle">Create Deal</h2> 
                <div class="drawer-subtitle" id="drawerSubtitle">Select an existing client, then complete the consulting and deal form.</div> 
            </div> 
        </div> 
 
        <button 
                type="button" 
                class="drawer-close" 
                id="closeDrawerButton" 
                aria-label="Close drawer" 
            > 
 
                <svg 
                    viewBox="0 0 24 24" 
                    fill="none" 
                    stroke="currentColor" 
                    stroke-width="1.8" 
                > 
 
                <path d="M6 6l12 12"></path> 
 
                <path d="M18 6L6 18"></path> 
 
            </svg> 
 
        </button> 
 
    </div> 
 
 
    {{-- ========================================================= 
         DRAWER BODY 
    ========================================================== --}} 
 
    <div class="drawer-body"> 
 
        <iframe 
            id="addDealFrame" 
            class="drawer-frame" 
            src="about:blank" 
            title="Create Deal Form" 
        ></iframe> 
 
    </div> 
 
</aside> 
 
 
 
 



{{-- ============================================================= 
     DELETE MODAL 
============================================================= --}} 

<div 
    class="modal-backdrop" 
    id="deleteModal" 
> 
 
    <div class="confirm-modal"> 
 
        <div class="confirm-title"> 
            Delete Deal 
        </div> 
 
 
        <div class="confirm-text"> 
 
            Are you sure you want to delete 
 
            <strong id="deleteDealName"></strong>? 
 
            This action cannot be undone. 
 
        </div> 
 
 
        <div class="confirm-actions"> 
 
            <button 
                type="button" 
                class="confirm-cancel" 
                onclick="closeDeleteModal()" 
            > 
                Cancel 
            </button> 
 
 
            <form 
                method="POST" 
                id="deleteDealForm" 
            > 
 
                @csrf 
 
                @method('DELETE') 
 
                <button 
                    type="submit" 
                    class="confirm-delete" 
                > 
                    Delete Deal 
                </button> 
 
            </form> 
 
        </div> 
 
    </div> 
 
</div> 
 
 
 
{{-- ============================================================= 
     JAVASCRIPT 
============================================================= --}} 
 
<script> 
 
 
    /* ========================================================= 
       ELEMENTS 
    ========================================================= */ 
 
    const addDealButton = 
        document.getElementById('addDealButton'); 
 
    const addDealDrawer = 
        document.getElementById('addDealDrawer'); 
 
    const drawerOverlay = 
        document.getElementById('drawerOverlay'); 
 
    const closeDrawerButton = 
        document.getElementById('closeDrawerButton'); 
 
    const addDealFrame = 
        document.getElementById('addDealFrame'); 
 
    const notificationButton = 
        document.getElementById('notificationButton'); 
 
    const notificationPanel = 
        document.getElementById('notificationPanel'); 
 
 
    /* ========================================================= 
       QUICK PURCHASE DRAWER (EXISTING CLIENTS)
    ========================================================= */ 
    const quickPurchaseButton = document.getElementById('quickPurchaseButton'); 


    function openQuickPurchaseDrawer(accountId) { 
        closeAllMenus(); 
        let url = "{{ route('deals.quick-purchase') }}?drawer=1"; 
        if (accountId) { 
            url += '&account_id=' + encodeURIComponent(accountId); 
        } 
        addDealFrame.src = url; 
        document.getElementById('drawerTitle').textContent = 'Add Inquiry'; 
        const drawerSub = document.getElementById('drawerSubtitle');
        if (drawerSub) drawerSub.textContent = 'Create a new inquiry for a client.';
        addDealDrawer.classList.add('show'); 
        drawerOverlay.classList.add('show'); 
        document.body.style.overflow = 'hidden'; 
    } 

    if (quickPurchaseButton) { 
        quickPurchaseButton.addEventListener('click', function(e) { 
            e.preventDefault(); 
            openQuickPurchaseDrawer(); 
        }); 
    } 
 


    /* ========================================================= 
       OPEN ADD DEAL DRAWER 
    ========================================================= */ 

    function openAddDealDrawer() { 

        addDealFrame.src = 
            "{{ route('deals.create') }}?drawer=1"; 

        document.getElementById('drawerTitle').textContent = 'Create Deal'; 
        const drawerSub = document.getElementById('drawerSubtitle');
        if (drawerSub) drawerSub.textContent = 'Select an existing client, then complete the consulting and deal form.'; 

        addDealDrawer.classList.add('show'); 

        drawerOverlay.classList.add('show'); 

        document.body.style.overflow = 'hidden'; 

        closeAllMenus(); 

    } 
 
 
    function openEditDealDrawer(dealId, event) { 
 
        event.preventDefault(); 
        event.stopPropagation(); 
 
        addDealFrame.src = 
            "{{ route('deals.create') }}?deal=" + encodeURIComponent(dealId) + '&drawer=1'; 
 
        document.getElementById('drawerTitle').textContent = 'Edit Deal'; 
 
        addDealDrawer.classList.add('show'); 
        drawerOverlay.classList.add('show'); 
        document.body.style.overflow = 'hidden'; 
 
        closeAllMenus(); 
 
    } 
 
 
    const editHash = 
        window.location.hash.match(/^#edit-(\d+)$/); 
 
    if (editHash) { 
 
        window.history.replaceState( 
            null, 
            '', 
            window.location.pathname + window.location.search 
        ); 
 
        openEditDealDrawer(editHash[1], { 
            preventDefault: function() {}, 
            stopPropagation: function() {} 
        }); 
 
    } 
 
 
    /* ========================================================= 
       CLOSE ADD DEAL DRAWER 
    ========================================================= */ 
 
    function closeAddDealDrawer() { 
 
        addDealDrawer.classList.remove('show'); 
 
        drawerOverlay.classList.remove('show'); 
 
        document.body.style.overflow = ''; 
 
        setTimeout(function () { 
 
            if (!addDealDrawer.classList.contains('show')) { 
 
                addDealFrame.src = 'about:blank'; 
 
            } 
 
        }, 250); 
 
    } 
 
 
    /* ========================================================= 
       ADD DEAL BUTTON 
    ========================================================= */ 
 
    if (addDealButton) { 
 
        addDealButton.addEventListener( 
            'click', 
            openAddDealDrawer 
        ); 
 
    } 
 
 
    /* ========================================================= 
       CLOSE X BUTTON 
    ========================================================= */ 
 
    if (closeDrawerButton) { 
 
        closeDrawerButton.addEventListener( 
            'click', 
            function(event) { 
 
                event.preventDefault(); 
 
                event.stopPropagation(); 
 
                closeAddDealDrawer(); 
 
            } 
        ); 
 
    } 
 
 
    /* ========================================================= 
       CLOSE WHEN CLICKING OVERLAY 
    ========================================================= */ 
 
    if (drawerOverlay) { 
 
        drawerOverlay.addEventListener( 
            'click', 
            function() { 
 
                closeAddDealDrawer(); 
 
            } 
        ); 
 
    } 
 
 
    /* ========================================================= 
       ESC CLOSES DRAWER 
    ========================================================= */ 
 
    document.addEventListener( 
        'keydown', 
        function(event) { 
 
            if (event.key === 'Escape') { 
 
                if ( 
                    addDealDrawer && 
                    addDealDrawer.classList.contains('show') 
                ) { 
 
                    closeAddDealDrawer(); 
 
                } 
 
                closeAllMenus(); 
 
            } 
 
        } 
    ); 
 
 
    /* ========================================================= 
       CREATE FORM INSIDE DRAWER 
    ========================================================= */ 
 
    if (addDealFrame) { 
 
        addDealFrame.addEventListener( 
            'load', 
            function() { 
 
                if ( 
                    !addDealDrawer.classList.contains('show') 
                ) { 
 
                    return; 
 
                } 
 
 
                try { 
 
                    const currentUrl = 
                        addDealFrame.contentWindow.location.href; 
 
 
                    if ( 
                        currentUrl !== 'about:blank' && 
                        !currentUrl.includes('/deals/create') &&
                        !currentUrl.includes('/deals/quick-purchase')
                    ) { 
 
                        closeAddDealDrawer(); 
 
                        setTimeout(function() { 
 
                            window.location.reload(); 
 
                        }, 200); 
 
                    } 
 
                } catch (error) { 
 
                    /* 
                     * Ignore cross-frame access errors. 
                     */ 
 
                } 
 
            } 
        ); 
 
    } 
 
 
    /* ========================================================= 
       NOTIFICATIONS 
    ========================================================= */ 
 
    if (notificationButton) { 
 
        notificationButton.addEventListener( 
            'click', 
            function(event) { 
 
                event.stopPropagation(); 
 
                closeAllMenus(); 
 
                notificationPanel.classList.toggle('show'); 
 
            } 
        ); 
 
    } 
 
 
    if (notificationPanel) { 
 
        notificationPanel.addEventListener( 
            'click', 
            function(event) { 
 
                event.stopPropagation(); 
 
            } 
        ); 
 
    } 
 
 
    /* ========================================================= 
       DEAL CARD 
    ========================================================= */ 
 
    function openDeal(url, event) { 
        if (typeof isDraggingDeal !== 'undefined' && isDraggingDeal) { 
            return; 
        } 

        if ( 
            event.target.closest('.deal-menu-wrap') || 
            event.target.closest('.deal-card-checkbox') || 
            event.target.closest('.price-eye-btn') || 
            event.target.closest('.deal-dots') || 
            event.target.closest('.deal-menu') 
        ) { 
            return; 
        } 

        window.location.href = url; 
    } 
 
 
    /* ========================================================= 
       DEAL MENU 
    ========================================================= */ 
 
    function toggleDealMenu(event, button) { 
 
        event.preventDefault(); 
 
        event.stopPropagation(); 
 
 
        const menu = 
            button.parentElement.querySelector('.deal-menu'); 
 
 
        closeAllMenus(); 
 
 
        menu.classList.toggle('show'); 
 
    } 
 
 
    /* ========================================================= 
       STAGE MENUS 
    ========================================================= */ 
    document 
        .querySelectorAll('.stage-dots') 
        .forEach(function(button) { 
            button.addEventListener( 
                'click', 
                function(event) { 
                    event.preventDefault(); 
                    event.stopPropagation(); 
 
                    const menu = button.parentElement.querySelector('.stage-menu'); 
                    if (!menu) return; 
 
                    const isShown = menu.classList.contains('show'); 
                    closeAllMenus(); 
 
                    if (!isShown) { 
                        menu.classList.add('show'); 
                        menu.classList.remove('position-above'); 
 
                        const rect = menu.getBoundingClientRect(); 
                        if (rect.bottom > window.innerHeight - 12) { 
                            menu.classList.add('position-above'); 
                        } 
 
                        const col = button.closest('.kanban-column'); 
                        if (col) { 
                            col.style.zIndex = '50'; 
                        } 
                    } 
                } 
            ); 
        }); 
 
 
    /* ========================================================= 
       CLOSE MENUS 
    ========================================================= */ 
    function closeAllMenus() { 
        document 
            .querySelectorAll('.deal-menu.show, .stage-menu.show') 
            .forEach(function(menu) { 
                menu.classList.remove('show'); 
                menu.classList.remove('position-above'); 
            }); 
 
        document 
            .querySelectorAll('.kanban-column') 
            .forEach(function(col) { 
                col.style.zIndex = ''; 
            }); 
 
        if (notificationPanel) { 
            notificationPanel.classList.remove('show'); 
        } 
    } 
 
 
    document.addEventListener( 
        'click', 
        function() { 
            closeAllMenus(); 
        } 
    ); 
 
    document.addEventListener( 
        'keydown', 
        function(event) { 
            if (event.key === 'Escape') { 
                closeAllMenus(); 
            } 
        } 
    ); 
 
 
    /* ========================================================= 
       DELETE MODAL 
    ========================================================= */ 
 
    function openDeleteModal(id, name) { 
 
        const modal = 
            document.getElementById('deleteModal'); 
 
        const form = 
            document.getElementById('deleteDealForm'); 
 
        const dealName = 
            document.getElementById('deleteDealName'); 
 
 
        form.action = 
            "{{ url('/deals') }}/" + id; 
 
 
        dealName.textContent = 
            name; 
 
 
        modal.classList.add('show'); 
 
 
        closeAllMenus(); 
 
    } 
 
 
    function closeDeleteModal() { 
 
        document 
            .getElementById('deleteModal') 
            .classList.remove('show'); 
 
    } 
 
 
    document 
        .getElementById('deleteModal') 
        .addEventListener( 
            'click', 
            function(event) { 
 
                if (event.target === this) { 
 
                    closeDeleteModal(); 
 
                } 
 
            } 
        ); 
    /* =========================================================
       STAGE FILTER
    ========================================================= */

    function filterStage(stage) {

        const cards =
            document.querySelectorAll('[data-deal-card]');

        cards.forEach(function(card) {

            if (card.dataset.stage === stage) {
                card.style.display = '';
            } else {
                card.style.display = 'none';
            }

        });

        closeAllMenus();

    }



    /* =========================================================
       MOBILE STAGE TABS
    ========================================================= */

    function showMobileStage(stage, button) {

        const columns =
            document.querySelectorAll('.kanban-column');

        columns.forEach(function(column) {

            const title =
                column.querySelector('.column-title');

            if (title && title.textContent.trim() === stage) {
                column.classList.add('mobile-active');
            } else {
                column.classList.remove('mobile-active');
            }

        });

        document.querySelectorAll('.mobile-stage-tab').forEach(function(tab) {
            tab.classList.remove('active');
        });

        if (button) {
            button.classList.add('active');
        }

        closeAllMenus();

    }



    /* =========================================================
       DEFAULT MOBILE STAGE
    ========================================================= */

    document.addEventListener('DOMContentLoaded', function() {

        const firstTab =
            document.querySelector('.mobile-stage-tab');

        if (firstTab) {
            showMobileStage(
                firstTab.textContent.trim(),
                firstTab
            );
        }

    });


         /* =========================================================
                  LIVE SEARCH
        ========================================================= */
 
    function updateKanbanLiveCounts() {
        let totalVisible = 0;
        document.querySelectorAll('.column-content').forEach(function(col) {
            const stage = col.dataset.stage;
            const meta = document.querySelector(`[data-stage-meta="${stage}"]`);
            const cards = col.querySelectorAll('[data-deal-card]');
            let visibleCount = 0;
            let visibleTotal = 0;

            cards.forEach(function(card) {
                if (card.style.display !== 'none') {
                    visibleCount++;
                    totalVisible++;
                    const amt = parseFloat(card.dataset.dealAmount) || 0;
                    visibleTotal += amt;
                }
            });

            if (meta) {
                meta.textContent = `₱${visibleTotal.toLocaleString('en-US', { minimumFractionDigits: 0, maximumFractionDigits: 0 })} • ${visibleCount} ${visibleCount === 1 ? 'Deal' : 'Deals'}`;
            }
        });

        const totalBadge = document.getElementById('totalDealsCount') || document.querySelector('.deal-count');
        if (totalBadge) {
            totalBadge.textContent = `${totalVisible} ${totalVisible === 1 ? 'deal' : 'deals'}`;
        }
    }

    /* ========================================================= 
       SEARCH AUTOCOMPLETE & TYPEAHEAD
    ========================================================= */ 
    const dealSearchWrapper = document.getElementById('dealSearchWrapper');
    const searchInput = document.getElementById('dealSearchInput') || document.querySelector('.search-box');
    const searchClearBtn = document.getElementById('searchClearBtn');
    const searchSpinner = document.getElementById('searchSpinner');
    const typeaheadDropdown = document.getElementById('searchTypeaheadDropdown');
    const typeaheadResultsList = document.getElementById('typeaheadResultsList');
    const typeaheadFooter = document.getElementById('typeaheadFooter');
    const typeaheadQueryLabel = document.getElementById('typeaheadQueryLabel');
    const typeaheadSearchAllBtn = document.getElementById('typeaheadSearchAllBtn');
    const dealsFilterForm = document.getElementById('dealsFilterForm');

    let typeaheadDebounceTimer = null;
    let typeaheadAbortController = null;
    let currentTypeaheadResults = [];
    let activeResultIndex = -1;

    function escapeHtml(str) {
        if (!str) return '';
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function highlightMatch(text, query) {
        if (!text || !query) return escapeHtml(text || '');
        const qTrim = query.trim();
        if (!qTrim) return escapeHtml(text);

        const tokens = qTrim.split(/\s+/).filter(t => t.length > 0);
        if (tokens.length === 0) return escapeHtml(text);

        const safeText = String(text);
        // Sort tokens longest first
        tokens.sort((a, b) => b.length - a.length);

        const escapedTokens = tokens.map(t => t.replace(/[.*+?^${}()|[\]\\]/g, '\\$&'));
        // Match word-start boundary (start of string or preceded by non-alphanumeric character)
        const pattern = new RegExp('(^|[^a-zA-Z0-9])(' + escapedTokens.join('|') + ')', 'gi');

        let lastIndex = 0;
        let result = '';
        let match;

        while ((match = pattern.exec(safeText)) !== null) {
            const prefixBoundary = match[1];
            const matchedText = match[2];
            const matchStart = match.index + prefixBoundary.length;
            const matchEnd = matchStart + matchedText.length;

            result += escapeHtml(safeText.substring(lastIndex, matchStart));
            result += '<span class="typeahead-highlight">' + escapeHtml(matchedText) + '</span>';
            lastIndex = matchEnd;
            pattern.lastIndex = matchEnd;
        }

        result += escapeHtml(safeText.substring(lastIndex));
        return result || escapeHtml(safeText);
    }

    function hideTypeahead() {
        if (typeaheadDropdown) {
            typeaheadDropdown.style.display = 'none';
        }
        activeResultIndex = -1;
    }

    function showTypeahead() {
        if (typeaheadDropdown && searchInput && searchInput.value.trim().length >= 1) {
            typeaheadDropdown.style.display = 'flex';
        }
    }

    function renderTypeaheadResults(results, query) {
        if (!typeaheadResultsList) return;
        currentTypeaheadResults = results || [];
        activeResultIndex = -1;

        if (typeaheadQueryLabel) {
            typeaheadQueryLabel.textContent = query;
        }

        if (!results || results.length === 0) {
            typeaheadResultsList.innerHTML = `
                <div class="typeahead-empty">
                    <div>No deals found for "<strong>${escapeHtml(query)}</strong>"</div>
                </div>
            `;
            if (typeaheadFooter) typeaheadFooter.style.display = 'block';
            showTypeahead();
            return;
        }

        let html = '';
        results.forEach((deal, idx) => {
            const stageColor = deal.stage_color || '#2458d7';
            // Highlight ONLY the customer/client name match
            const highlightedClient = highlightMatch(deal.client_name, query);
            const dealCode = escapeHtml(deal.deal_code || '');
            const dealTitle = escapeHtml(deal.deal_title || '');

            html += `
                <div 
                    class="typeahead-item" 
                    data-index="${idx}" 
                    data-url="${deal.url}"
                    onclick="selectTypeaheadItem('${deal.url}')"
                >
                    <div class="typeahead-client-primary" title="${escapeHtml(deal.client_name)}">${highlightedClient}</div>
                    <div class="typeahead-meta">
                        <span class="typeahead-secondary-info">${dealCode}${dealTitle ? ' · ' + dealTitle : ''}</span>
                        <span class="typeahead-stage-badge" style="background-color: ${stageColor};">
                            ${escapeHtml(deal.stage)}
                        </span>
                    </div>
                </div>
            `;
        });

        typeaheadResultsList.innerHTML = html;
        if (typeaheadFooter) typeaheadFooter.style.display = 'block';
        showTypeahead();
    }

    function selectTypeaheadItem(url) {
        if (url) {
            window.location.href = url;
        }
    }

    function updateActiveTypeaheadItem() {
        if (!typeaheadResultsList) return;
        const items = typeaheadResultsList.querySelectorAll('.typeahead-item');
        items.forEach((item, idx) => {
            if (idx === activeResultIndex) {
                item.classList.add('active');
                item.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
            } else {
                item.classList.remove('active');
            }
        });
    }

    async function fetchTypeahead(query) {
        if (typeaheadAbortController) {
            typeaheadAbortController.abort();
        }
        typeaheadAbortController = new AbortController();

        if (searchSpinner) searchSpinner.style.display = 'block';
        if (searchClearBtn) searchClearBtn.style.display = 'none';

        try {
            const url = "{{ route('deals.autocomplete') }}?q=" + encodeURIComponent(query);
            const response = await fetch(url, {
                signal: typeaheadAbortController.signal,
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            if (!response.ok) throw new Error('Search failed');
            const data = await response.json();
            renderTypeaheadResults(data, query);
        } catch (err) {
            if (err.name !== 'AbortError') {
                console.error('Autocomplete error:', err);
            }
        } finally {
            if (searchSpinner) searchSpinner.style.display = 'none';
            if (searchClearBtn && searchInput.value.length > 0) searchClearBtn.style.display = 'flex';
        }
    }

    if (searchInput) {
        searchInput.addEventListener('input', function() {
            const rawSearch = this.value.trim().toLowerCase();
            const searchTokens = rawSearch.split(/\s+/).filter(t => t.length > 0);

            // 1. Live filter the Kanban cards instantly based on CUSTOMER / CLIENT NAME prefix ONLY
            document.querySelectorAll('[data-deal-card]').forEach(function(card) {
                if (searchTokens.length === 0) {
                    card.style.display = '';
                    return;
                }

                const rawTargets = (card.dataset.customerSearch || card.dataset.customerName || card.dataset.search || '').split('|').map(s => s.trim()).filter(Boolean);

                let matches = false;

                for (let target of rawTargets) {
                    // Check if customer/client name starts with full search query
                    if (target.startsWith(rawSearch)) {
                        matches = true;
                        break;
                    }

                    // Multi-token: check if customer/client name starts with first token and subsequent tokens match words in target
                    if (searchTokens.length > 1 && target.startsWith(searchTokens[0])) {
                        const words = target.split(/[\s\-_,.:;|\/\\()\[\]@]+/).filter(w => w.length > 0);
                        const allTokensMatch = searchTokens.every(function(token) {
                            return words.some(function(word) {
                                return word.startsWith(token);
                            });
                        });
                        if (allTokensMatch) {
                            matches = true;
                            break;
                        }
                    }
                }

                card.style.display = matches ? '' : 'none';
            });
            updateKanbanLiveCounts();

            // 2. Toggle clear button
            if (searchClearBtn) {
                searchClearBtn.style.display = rawSearch.length > 0 ? 'flex' : 'none';
            }

            // 3. Typeahead query
            clearTimeout(typeaheadDebounceTimer);
            if (rawSearch.length >= 1) {
                typeaheadDebounceTimer = setTimeout(() => {
                    fetchTypeahead(rawSearch);
                }, 180);
            } else {
                hideTypeahead();
            }
        });

        searchInput.addEventListener('focus', function() {
            if (this.value.trim().length >= 1 && currentTypeaheadResults.length > 0) {
                showTypeahead();
            }
        });

        searchInput.addEventListener('keydown', function(e) {
            const items = typeaheadResultsList ? typeaheadResultsList.querySelectorAll('.typeahead-item') : [];

            if (e.key === 'ArrowDown') {
                if (typeaheadDropdown.style.display !== 'none' && items.length > 0) {
                    e.preventDefault();
                    activeResultIndex = (activeResultIndex + 1) % items.length;
                    updateActiveTypeaheadItem();
                }
            } else if (e.key === 'ArrowUp') {
                if (typeaheadDropdown.style.display !== 'none' && items.length > 0) {
                    e.preventDefault();
                    activeResultIndex = (activeResultIndex - 1 + items.length) % items.length;
                    updateActiveTypeaheadItem();
                }
            } else if (e.key === 'Enter') {
                if (typeaheadDropdown.style.display !== 'none' && activeResultIndex >= 0 && items[activeResultIndex]) {
                    e.preventDefault();
                    const url = items[activeResultIndex].dataset.url;
                    selectTypeaheadItem(url);
                } else {
                    hideTypeahead();
                }
            } else if (e.key === 'Escape') {
                hideTypeahead();
            }
        });
    }

    if (searchClearBtn) {
        searchClearBtn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            if (searchInput) {
                searchInput.value = '';
                searchInput.focus();
                // Reset live filter
                document.querySelectorAll('[data-deal-card]').forEach(function(card) {
                    card.style.display = '';
                });
                updateKanbanLiveCounts();
            }
            searchClearBtn.style.display = 'none';
            hideTypeahead();
        });
    }

    if (typeaheadSearchAllBtn) {
        typeaheadSearchAllBtn.addEventListener('click', function(e) {
            e.preventDefault();
            hideTypeahead();
            if (dealsFilterForm) {
                dealsFilterForm.submit();
            }
        });
    }

    // Dismiss dropdown on outside click
    document.addEventListener('click', function(e) {
        if (dealSearchWrapper && !dealSearchWrapper.contains(e.target)) {
            hideTypeahead();
        }
    }); 
 
 
    /* ========================================================= 
       PRICE MASK TOGGLE 
    ========================================================= */ 
    function togglePriceMask(event, button) { 
        event.preventDefault(); 
        event.stopPropagation(); 
 
        const section = button.closest('.deal-price-section'); 
        if (!section) return; 
 
        const masked = section.querySelector('.price-masked'); 
        const numeric = section.querySelector('.price-numeric'); 
 
        if (masked && numeric) { 
            if (masked.style.display === 'none') { 
                masked.style.display = 'block'; 
                numeric.style.display = 'none'; 
            } else { 
                masked.style.display = 'none'; 
                numeric.style.display = 'block'; 
            } 
        } 
    } 
 
 
    /* ========================================================= 
       STAGE COLOR & ACTIONS 
    ========================================================= */ 
    function setStageColor(button, color) { 
        const column = button.closest('.kanban-column'); 
        if (column) { 
            const header = column.querySelector('.column-header'); 
            if (header) { 
                header.style.borderTopColor = color; 
            } 
        } 
        closeAllMenus(); 
    } 
 
    function moveStageLeft(button) { 
        const column = button.closest('.kanban-column'); 
        if (column && column.previousElementSibling && column.previousElementSibling.classList.contains('kanban-column')) { 
            column.parentNode.insertBefore(column, column.previousElementSibling); 
        } 
    } 
 
    function moveStageRight(button) { 
        const column = button.closest('.kanban-column'); 
        if (column && column.nextElementSibling && column.nextElementSibling.classList.contains('kanban-column')) { 
            column.parentNode.insertBefore(column.nextElementSibling, column); 
        } 
    } 
 
    function renameStage(button) { 
        const column = button.closest('.kanban-column'); 
        if (column) { 
            const titleEl = column.querySelector('.column-title'); 
            const currentName = titleEl ? titleEl.textContent.trim() : ''; 
            const newName = prompt('Enter new stage name:', currentName); 
            if (newName && newName.trim() && titleEl) { 
                titleEl.textContent = newName.trim(); 
            } 
        } 
    } 

    /* ========================================================= 
       DRAG AND DROP KANBAN ENGINE 
    ========================================================= */ 
    var draggedDealCard = null; 
    var dragSourceColumn = null; 
    var dragSourceStage = null; 
    var isDraggingDeal = false; 
    var dropPlaceholder = null; 

    const kanbanScrollEl = document.querySelector('.kanban-scroll'); 

    function showToast(message, type = 'info', duration = 4000) { 
        let toast = document.getElementById('kanbanToast'); 
        if (!toast) { 
            toast = document.createElement('div'); 
            toast.id = 'kanbanToast'; 
            toast.className = 'kanban-toast'; 
            document.body.appendChild(toast); 
        } 
        
        const configs = {
            success: {
                title: 'Success',
                iconBg: '#f0fdf4',
                iconColor: '#16a34a',
                border: '#bbf7d0',
                svg: '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>'
            },
            error: {
                title: 'Error',
                iconBg: '#fef2f2',
                iconColor: '#dc2626',
                border: '#fecaca',
                svg: '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="15" y1="9" x2="9" y2="15"></line><line x1="9" y1="9" x2="15" y2="15"></line></svg>'
            },
            warning: {
                title: 'Warning',
                iconBg: '#fffbeb',
                iconColor: '#d97706',
                border: '#fde68a',
                svg: '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path><line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>'
            },
            info: {
                title: 'Notice',
                iconBg: '#eff6ff',
                iconColor: '#2563eb',
                border: '#bfdbfe',
                svg: '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="16" x2="12" y2="12"></line><line x1="12" y1="8" x2="12.01" y2="8"></line></svg>'
            }
        };

        const cfg = configs[type] || configs.info;
        toast.className = 'kanban-toast toast-' + type;
        toast.style.borderColor = cfg.border;
        toast.innerHTML = `
            <div style="flex-shrink: 0; width: 32px; height: 32px; border-radius: 50%; background: ${cfg.iconBg}; color: ${cfg.iconColor}; display: flex; align-items: center; justify-content: center;">
                ${cfg.svg}
            </div>
            <div style="flex: 1; min-width: 0;">
                <div style="font-size: 13px; font-weight: 600; color: #0f172a; line-height: 1.3;">${cfg.title}</div>
                <div style="font-size: 12.5px; font-weight: 400; color: #475569; margin-top: 2px; line-height: 1.4; word-break: break-word;">${message}</div>
            </div>
            <button type="button" onclick="this.parentElement.classList.remove('show')" style="flex-shrink: 0; background: transparent; border: none; padding: 4px; cursor: pointer; color: #94a3b8; border-radius: 6px; display: flex; align-items: center; justify-content: center;" onmouseenter="this.style.color='#475569'" onmouseleave="this.style.color='#94a3b8'">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
            </button>
        `;
        toast.classList.add('show'); 

        clearTimeout(toast._timeout); 
        toast._timeout = setTimeout(function() { 
            toast.classList.remove('show'); 
        }, duration); 
    } 

    function updateColumnMeta(stage, metaData) { 
        const metaEl = document.querySelector(`.column-meta[data-stage-meta="${stage}"]`); 
        if (metaEl && metaData) { 
            metaEl.textContent = metaData.label || `${metaData.formatted_total} • ${metaData.count} ${metaData.count === 1 ? 'Deal' : 'Deals'}`; 
        } 
    } 

    function syncEmptyState(columnContent) { 
        if (!columnContent) return; 
        const cards = columnContent.querySelectorAll('.deal-card'); 
        let emptyEl = columnContent.querySelector('.empty-stage'); 
        if (cards.length === 0) { 
            if (!emptyEl) { 
                emptyEl = document.createElement('div'); 
                emptyEl.className = 'empty-stage'; 
                emptyEl.textContent = 'No deals in this stage.'; 
                columnContent.appendChild(emptyEl); 
            } else { 
                emptyEl.style.display = ''; 
            } 
        } else { 
            if (emptyEl) { 
                emptyEl.remove(); 
            } 
        } 
    } 

    function initKanbanDragDrop() { 
        const cards = document.querySelectorAll('.deal-card[draggable="true"]'); 
        const columns = document.querySelectorAll('.kanban-column'); 

        cards.forEach(function(card) { 
            card.removeEventListener('dragstart', handleCardDragStart); 
            card.removeEventListener('dragend', handleCardDragEnd); 
            card.addEventListener('dragstart', handleCardDragStart); 
            card.addEventListener('dragend', handleCardDragEnd); 
        }); 

        columns.forEach(function(column) { 
            column.removeEventListener('dragover', handleColumnDragOver); 
            column.removeEventListener('dragenter', handleColumnDragEnter); 
            column.removeEventListener('dragleave', handleColumnDragLeave); 
            column.removeEventListener('drop', handleColumnDrop); 

            column.addEventListener('dragover', handleColumnDragOver); 
            column.addEventListener('dragenter', handleColumnDragEnter); 
            column.addEventListener('dragleave', handleColumnDragLeave); 
            column.addEventListener('drop', handleColumnDrop); 
        }); 

        if (kanbanScrollEl) { 
            kanbanScrollEl.removeEventListener('dragover', handleKanbanAutoScroll); 
            kanbanScrollEl.addEventListener('dragover', handleKanbanAutoScroll); 
        } 
    } 

    function handleCardDragStart(e) { 
        if ( 
            e.target.closest('.deal-card-checkbox') || 
            e.target.closest('.deal-menu-wrap') || 
            e.target.closest('.price-eye-btn') || 
            e.target.closest('.deal-dots') || 
            e.target.closest('.deal-menu') 
        ) { 
            e.preventDefault(); 
            return false; 
        } 

        draggedDealCard = this; 
        dragSourceColumn = this.closest('.column-content'); 
        dragSourceStage = this.getAttribute('data-stage') || (dragSourceColumn ? dragSourceColumn.getAttribute('data-stage') : null); 
        isDraggingDeal = true; 

        e.dataTransfer.effectAllowed = 'move'; 
        e.dataTransfer.setData('text/plain', this.getAttribute('data-deal-id') || ''); 

        if (!dropPlaceholder) { 
            dropPlaceholder = document.createElement('div'); 
            dropPlaceholder.className = 'kanban-drop-placeholder'; 
            dropPlaceholder.textContent = 'Drop deal here'; 
        } 

        setTimeout(() => { 
            if (draggedDealCard) { 
                draggedDealCard.classList.add('is-dragging'); 
            } 
        }, 0); 
    } 

    function handleCardDragEnd(e) { 
        if (draggedDealCard) { 
            draggedDealCard.classList.remove('is-dragging'); 
        } 
        document.querySelectorAll('.column-content.drag-over').forEach(function(col) { 
            col.classList.remove('drag-over'); 
        }); 
        if (dropPlaceholder && dropPlaceholder.parentNode) { 
            dropPlaceholder.parentNode.removeChild(dropPlaceholder); 
        } 

        draggedDealCard = null; 
        dragSourceColumn = null; 
        dragSourceStage = null; 

        setTimeout(function() { 
            isDraggingDeal = false; 
        }, 150); 
    } 

    function handleColumnDragOver(e) { 
        e.preventDefault(); 
        e.dataTransfer.dropEffect = 'move'; 

        const content = this.querySelector('.column-content'); 
        if (!content || !draggedDealCard) return; 

        if (!content.classList.contains('drag-over')) { 
            content.classList.add('drag-over'); 
        } 

        if (dropPlaceholder && dropPlaceholder.parentNode !== content) { 
            const afterElement = getDragAfterElement(content, e.clientY); 
            if (afterElement == null) { 
                content.appendChild(dropPlaceholder); 
            } else { 
                content.insertBefore(dropPlaceholder, afterElement); 
            } 
        } 
    } 

    function handleColumnDragEnter(e) { 
        e.preventDefault(); 
        const content = this.querySelector('.column-content'); 
        if (content && draggedDealCard) { 
            content.classList.add('drag-over'); 
        } 
    } 

    function handleColumnDragLeave(e) { 
        const content = this.querySelector('.column-content'); 
        if (content && !this.contains(e.relatedTarget)) { 
            content.classList.remove('drag-over'); 
            if (dropPlaceholder && dropPlaceholder.parentNode === content) { 
                dropPlaceholder.remove(); 
            } 
        } 
    } 

    function getDragAfterElement(container, y) { 
        const draggableElements = [...container.querySelectorAll('.deal-card:not(.is-dragging)')]; 

        return draggableElements.reduce((closest, child) => { 
            const box = child.getBoundingClientRect(); 
            const offset = y - box.top - box.height / 2; 
            if (offset < 0 && offset > closest.offset) { 
                return { offset: offset, element: child }; 
            } else { 
                return closest; 
            } 
        }, { offset: Number.NEGATIVE_INFINITY }).element; 
    } 

    function handleKanbanAutoScroll(e) { 
        if (!isDraggingDeal || !kanbanScrollEl) return; 

        const rect = kanbanScrollEl.getBoundingClientRect(); 
        const mouseX = e.clientX; 
        const edgeThreshold = 80; 
        const scrollStep = 18; 

        if (mouseX > rect.right - edgeThreshold) { 
            kanbanScrollEl.scrollLeft += scrollStep; 
        } else if (mouseX < rect.left + edgeThreshold) { 
            kanbanScrollEl.scrollLeft -= scrollStep; 
        } 
    } 

    async function handleColumnDrop(e) { 
        e.preventDefault(); 
        e.stopPropagation(); 

        const targetColumn = this; 
        const targetContent = targetColumn.querySelector('.column-content'); 
        const targetStage = targetColumn.getAttribute('data-stage') || (targetContent ? targetContent.getAttribute('data-stage') : null); 
        const card = draggedDealCard; 
        const sourceCol = dragSourceColumn; 
        const sourceStage = dragSourceStage; 

        document.querySelectorAll('.column-content.drag-over').forEach(c => c.classList.remove('drag-over')); 
        if (dropPlaceholder && dropPlaceholder.parentNode) { 
            dropPlaceholder.parentNode.removeChild(dropPlaceholder); 
        } 

        if (!card || !targetContent || !targetStage) return; 

        const dealId = card.getAttribute('data-deal-id'); 
        if (!dealId) return; 

        // Dropped in the same column
        if (targetStage === sourceStage) { 
            const afterElement = getDragAfterElement(targetContent, e.clientY); 
            if (afterElement == null) { 
                targetContent.appendChild(card); 
            } else { 
                targetContent.insertBefore(card, afterElement); 
            } 
            return; 
        } 

        // Dropped in a different stage column -> AJAX Backend stage change
        if (card.classList.contains('is-updating')) { 
            return; 
        } 

        card.classList.add('is-updating'); 

        try { 
            const csrfMeta = document.querySelector('meta[name="csrf-token"]'); 
            const csrfToken = csrfMeta ? csrfMeta.getAttribute('content') : '{{ csrf_token() }}'; 

            const response = await fetch(`{{ url('/deals') }}/${dealId}/stage`, { 
                method: 'PATCH', 
                headers: { 
                    'Content-Type': 'application/json', 
                    'Accept': 'application/json', 
                    'X-CSRF-TOKEN': csrfToken 
                }, 
                body: JSON.stringify({ 
                    pipeline_stage: targetStage 
                }) 
            }); 

            const data = await response.json(); 

            if (!response.ok || !data.success) { 
                const errorMsg = (data && data.message) ? data.message : 'Unable to update deal stage. Please try again.'; 
                showToast(errorMsg, 'error', 4500); 
                card.classList.remove('is-updating'); 
                return; 
            } 

            // SUCCESS: Move card element into destination column
            const afterElement = getDragAfterElement(targetContent, e.clientY); 
            if (afterElement == null) { 
                targetContent.appendChild(card); 
            } else { 
                targetContent.insertBefore(card, afterElement); 
            } 

            card.setAttribute('data-stage', targetStage); 
            card.classList.remove('is-updating'); 

            // Update empty states
            syncEmptyState(sourceCol); 
            syncEmptyState(targetContent); 

            // Update column aggregates & totals dynamically
            if (data.stages) { 
                Object.keys(data.stages).forEach(function(st) { 
                    updateColumnMeta(st, data.stages[st]); 
                }); 
            } 

            showToast(data.message || `Deal moved to ${targetStage}.`, 'success', 3000); 

        } catch (err) { 
            console.error('Drag and drop stage update error:', err); 
            showToast('Unable to update the deal stage. Please try again.', 'error', 4500); 
            card.classList.remove('is-updating'); 
        } 
    } 

    document.addEventListener('DOMContentLoaded', function() { 
        initKanbanDragDrop(); 
    }); 

</script> 



@php
    $flashType = session('success') ? 'success' : (session('info') ? 'info' : (session('warning') ? 'warning' : (session('error') ? 'error' : null)));
    $flashMsg = session('success') ?? session('info') ?? session('warning') ?? session('error');
    
    $flashConfig = match($flashType) {
        'error' => [
            'iconBg' => '#fef2f2',
            'iconColor' => '#dc2626',
            'border' => '#fecaca',
            'title' => 'Error',
            'icon' => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="15" y1="9" x2="9" y2="15"></line><line x1="9" y1="9" x2="15" y2="15"></line></svg>',
            'barColor' => '#ef4444',
        ],
        'warning' => [
            'iconBg' => '#fffbeb',
            'iconColor' => '#d97706',
            'border' => '#fde68a',
            'title' => 'Warning',
            'icon' => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path><line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>',
            'barColor' => '#f59e0b',
        ],
        'info' => [
            'iconBg' => '#eff6ff',
            'iconColor' => '#2563eb',
            'border' => '#bfdbfe',
            'title' => 'Notice',
            'icon' => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="16" x2="12" y2="12"></line><line x1="12" y1="8" x2="12.01" y2="8"></line></svg>',
            'barColor' => '#3b82f6',
        ],
        default => [
            'iconBg' => '#f0fdf4',
            'iconColor' => '#16a34a',
            'border' => '#bbf7d0',
            'title' => 'Success',
            'icon' => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>',
            'barColor' => '#22c55e',
        ],
    };
@endphp

@if($flashMsg)
    <div
        id="flashMessage"
        class="ordo-toast-notification"
        style="position: fixed; top: 24px; right: 24px; z-index: 99999; background: #ffffff; color: #0f172a; border: 1px solid {{ $flashConfig['border'] }}; border-radius: 12px; box-shadow: 0 12px 30px -4px rgba(15, 23, 42, 0.15), 0 4px 12px -2px rgba(15, 23, 42, 0.08); display: flex; align-items: flex-start; gap: 12px; padding: 14px 16px; min-width: 320px; max-width: 440px; animation: ordoToastSlideIn 0.35s cubic-bezier(0.16, 1, 0.3, 1); overflow: hidden;"
    >
        <div style="flex-shrink: 0; width: 34px; height: 34px; border-radius: 50%; background: {{ $flashConfig['iconBg'] }}; color: {{ $flashConfig['iconColor'] }}; display: flex; align-items: center; justify-content: center;">
            {!! $flashConfig['icon'] !!}
        </div>
        <div style="flex: 1; min-width: 0; padding-top: 1px;">
            <div style="font-size: 13px; font-weight: 600; color: #0f172a; line-height: 1.3;">{{ $flashConfig['title'] }}</div>
            <div style="font-size: 12.5px; font-weight: 400; color: #475569; margin-top: 2px; line-height: 1.4; word-break: break-word;">{{ $flashMsg }}</div>
        </div>
        <button
            type="button"
            onclick="dismissOrdoToast(document.getElementById('flashMessage'))"
            style="flex-shrink: 0; background: transparent; border: none; padding: 4px; cursor: pointer; color: #94a3b8; border-radius: 6px; display: flex; align-items: center; justify-content: center; margin-top: -2px; margin-right: -4px;"
            onmouseenter="this.style.color='#475569'; this.style.background='#f1f5f9';"
            onmouseleave="this.style.color='#94a3b8'; this.style.background='transparent';"
            aria-label="Close"
        >
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
        </button>
        <div style="position: absolute; bottom: 0; left: 0; right: 0; height: 3px; background: #f1f5f9;">
            <div id="flashProgressBar" style="height: 100%; background: {{ $flashConfig['barColor'] }}; width: 100%; transition: width 4s linear;"></div>
        </div>
    </div>

    <style>
        @keyframes ordoToastSlideIn {
            from {
                opacity: 0;
                transform: translateY(-16px) scale(0.96);
            }
            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }
        @keyframes ordoToastFadeOut {
            from {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
            to {
                opacity: 0;
                transform: translateY(-10px) scale(0.96);
            }
        }
    </style>

    <script>
        function dismissOrdoToast(toast) {
            if (!toast) return;
            toast.style.animation = 'ordoToastFadeOut 0.25s cubic-bezier(0.16, 1, 0.3, 1) forwards';
            setTimeout(() => toast.remove(), 250);
        }

        setTimeout(function() {
            const bar = document.getElementById('flashProgressBar');
            if (bar) bar.style.width = '0%';
        }, 50);

        setTimeout(function() {
            const flash = document.getElementById('flashMessage');
            dismissOrdoToast(flash);
        }, 4000);
    </script>
@endif

<div id="kanbanToast" class="kanban-toast"></div>

<x-ordo-tooltip />

</body> 

</html>
