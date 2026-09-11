<!DOCTYPE html> 
<html lang="en"> 
 
<head> 
    <meta charset="UTF-8"> 
    <meta name="viewport" content="width=device-width, initial-scale=1.0"> 
 
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
           ADD DEAL BUTTON 
        ========================================================= */ 
 
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
 
        .search-box { 
            width: 337px; 
            height: 34px; 
 
            border: 1px solid #dce2ea; 
            border-radius: 7px; 
 
            padding: 0 11px; 
 
            outline: none; 
 
            color: #334155; 
            font-size: 11px; 
 
            background: #ffffff; 
        } 
 
        .search-box:focus { 
            border-color: #93b4f5; 
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
            width: 202px; 
            min-width: 202px; 
 
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
            background: #1e2b3f; 
 
            border-top: 3px solid #2458d7; 
            border-radius: 8px 8px 0 0; 
 
            padding: 8px 9px 8px; 
 
            color: #ffffff; 
        } 
 
        .column-title-row { 
            display: flex; 
            align-items: center; 
            justify-content: space-between; 
 
            gap: 5px; 
        } 
 
        .column-title { 
            font-size: 11px; 
            font-weight: 700; 
            color: #ffffff; 
        } 
 
        .column-meta { 
            margin-top: 2px; 
 
            color: #ffffff; 
 
            font-size: 9px; 
            opacity: .95; 
        } 
 
 
        /* ========================================================= 
           STAGE MENU 
        ========================================================= */ 
 
        .stage-menu-wrap { 
            position: relative; 
        } 
 
        .dots-button { 
            width: 24px; 
            height: 24px; 
 
            border: 0; 
            background: transparent; 
 
            border-radius: 5px; 
 
            color: #ffffff; 
 
            font-size: 13px; 
            letter-spacing: 1px; 
 
            display: flex; 
            align-items: center; 
            justify-content: center; 
        } 
 
        .dots-button:hover { 
            background: rgba(255,255,255,.12); 
        } 
 
        .stage-menu { 
            position: absolute; 
 
            right: 0; 
            top: 27px; 
 
            width: 145px; 
 
            background: #ffffff; 
 
            border: 1px solid #e2e8f0; 
            border-radius: 7px; 
 
            box-shadow: 0 10px 28px rgba(15, 23, 42, .13); 
 
            padding: 4px; 
 
            display: none; 
 
            z-index: 100; 
        } 
 
        .stage-menu.show { 
            display: block; 
        } 
 
        .stage-menu button { 
            width: 100%; 
 
            border: 0; 
            background: transparent; 
 
            text-align: left; 
 
            border-radius: 5px; 
 
            padding: 8px; 
 
            font-size: 10px; 
            color: #475569; 
        } 
 
        .stage-menu button:hover { 
            background: #f8fafc; 
        } 
 
 
        /* ========================================================= 
           COLUMN CONTENT 
        ========================================================= */ 
 
        .column-content { 
            padding: 7px; 
 
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
 
            font-size: 9px; 
 
            background: #ffffff; 
 
            border: 1px dashed #d6dde7; 
 
            border-radius: 7px; 
        } 
 
        .stage-description { 
            padding: 0 9px 10px; 
 
            color: #a0a9b5; 
 
            font-size: 8px; 
        } 
 
 
        /* ========================================================= 
           DEAL CARD 
        ========================================================= */ 
 
        .deal-card { 
            position: relative; 
 
            background: #ffffff; 
 
            border: 1px solid #e2e8f0; 
            border-radius: 7px; 
 
            padding: 11px; 
 
            margin-bottom: 7px; 
 
            cursor: pointer; 
 
            transition: 
                box-shadow .15s ease, 
                border-color .15s ease; 
        } 
 
        .deal-card:hover { 
            border-color: #cbd5e1; 
 
            box-shadow: 
                0 5px 14px rgba(15, 23, 42, .07); 
        } 
 
        .deal-code { 
            padding-right: 25px; 
 
            color: #1455d9; 
 
            font-size: 10px; 
            font-weight: 700; 
 
            line-height: 1.35; 
 
            word-break: break-word; 
        } 
 
        .deal-title { 
            margin-top: 7px; 
 
            padding-right: 20px; 
 
            color: #334155; 
 
            font-size: 10px; 
            font-weight: 600; 
 
            line-height: 1.35; 
        } 
 
        .deal-company { 
            margin-top: 4px; 
 
            color: #64748b; 
 
            font-size: 9px; 
 
            min-height: 13px; 
        } 
 
        .deal-divider { 
            border: 0; 
            border-top: 1px solid #eef2f7; 
 
            margin: 10px 0; 
        } 
 
        .deal-price-label { 
            font-size: 8px; 
            color: #94a3b8; 
            text-transform: uppercase; 
 
            display: flex; 
            align-items: center; 
            gap: 5px; 
        } 
 
        .deal-price-label svg { 
            width: 11px; 
            height: 11px; 
        } 
 
        .deal-price { 
            margin-top: 4px; 
 
            color: #1455d9; 
 
            font-size: 10px; 
            font-weight: 700; 
        } 
 
        .deal-expected { 
            margin-top: 11px; 
        } 
 
        .deal-expected-label { 
            font-size: 8px; 
            color: #94a3b8; 
        } 
 
        .deal-expected-value { 
            margin-top: 3px; 
 
            font-size: 9px; 
            color: #475569; 
        } 
 
        .deal-owner { 
            margin-top: 9px; 
            padding-top: 8px; 
 
            border-top: 1px solid #eef2f7; 
 
            color: #64748b; 
 
            font-size: 8px; 
 
            display: flex; 
            align-items: center; 
            gap: 5px; 
        } 
 
        .owner-avatar { 
            width: 16px; 
            height: 16px; 
 
            border-radius: 50%; 
 
            background: #edf4ff; 
            color: #2458d7; 
 
            display: inline-flex; 
            align-items: center; 
            justify-content: center; 
 
            font-size: 8px; 
            font-weight: 700; 
 
            flex-shrink: 0; 
        } 

        .start-card-action {
    margin-top: 10px;
    padding-top: 8px;
    border-top: 1px solid #eef2f7;
}

.start-card-button {
    width: 100%;
    padding: 7px 10px;

    border: 1px solid #d8e7ff;
    border-radius: 6px;

    background: #edf4ff;
    color: #2458d7;

    font-size: 9px;
    font-weight: 700;

    cursor: pointer;

    transition:
        background .15s ease,
        border-color .15s ease;
}

.start-card-button:hover {
    background: #e4efff;
    border-color: #c7dcff;
}
 
 
        /* ========================================================= 
           DEAL MENU 
        ========================================================= */ 
 
        .deal-menu-wrap { 
            position: absolute; 
 
            right: 6px; 
            top: 6px; 
        } 
 
        .deal-dots { 
            width: 24px; 
            height: 24px; 
 
            border: 0; 
            background: transparent; 
 
            color: #64748b; 
 
            border-radius: 5px; 
 
            font-size: 14px; 
            letter-spacing: 1px; 
 
            display: flex; 
            align-items: center; 
            justify-content: center; 
        } 
 
        .deal-dots:hover { 
            background: #f1f5f9; 
        } 
 
        .deal-menu { 
            position: absolute; 
 
            right: 0; 
            top: 27px; 
 
            width: 130px; 
 
            background: #ffffff; 
 
            border: 1px solid #e2e8f0; 
            border-radius: 7px; 
 
            box-shadow: 0 10px 28px rgba(15, 23, 42, .13); 
 
            padding: 4px; 
 
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
 
            border-radius: 5px; 
 
            padding: 8px; 
 
            font-size: 10px; 
 
            color: #475569; 
        } 
 
        .deal-menu a:hover, 
        .deal-menu button:hover { 
            background: #f8fafc; 
        } 
 
        .deal-menu .delete-action { 
            color: #dc2626; 
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
 
            .search-box { 
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
 
                {{ strtoupper(substr(auth()->user()->name ?? 'P', 0, 1)) }} 
 
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
 
 
                    {{-- ADD DEAL --}} 
 
                    <button 
                        type="button" 
                        class="primary-button" 
                        id="addDealButton" 
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
 
 
                {{-- ================================================= 
                     FILTERS 
                ================================================== --}} 
 
                <form 
                    method="GET" 
                    action="{{ route('deals.index') }}" 
                    class="filters" 
                > 
 
                    <input 
                        type="text" 
                        name="search" 
                        class="search-box" 
                        value="{{ request('search') }}" 
                        placeholder=" Search code, deal, or contact" 
                    > 
 
 
                    <select 
                        name="deal_filter" 
                        class="filter-select" 
                    > 
 
                        <option value=""> 
                            All Deals 
                        </option> 
 
                        <option 
                            value="my_deals" 
                            {{ request('deal_filter') === 'my_deals' ? 'selected' : '' }} 
                        > 
                            My Deals 
                        </option> 
 
                    </select> 
 
 
                    <select 
                        name="date_filter" 
                        class="filter-select" 
                    > 
 
                        <option value="created_date"> 
                            Created Date 
                        </option> 
 
                        <option 
                            value="updated_date" 
                            {{ request('date_filter') === 'updated_date' ? 'selected' : '' }} 
                        > 
                            Last Updated 
                        </option> 
 
                    </select> 
 
 
                    <button 
                        type="submit" 
                        class="filter-button" 
                    > 
                        Search 
                    </button> 
 
 
                    <span class="deal-count"> 
 
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
 
 
                            <div class="kanban-column"> 
 
 
                                {{-- STAGE HEADER --}} 
 
                                <div 
                                    class="column-header" 
                                    style=" 
                                        border-top-color: 
                                        {{ $stageColors[$stage] ?? '#2458d7' }}; 
                                    " 
                                > 
 
                                    <div class="column-title-row"> 
 
                                        <div class="column-title"> 
                                            {{ $stage }} 
                                        </div> 
 
 
                                        <div class="stage-menu-wrap"> 
 
                                            <button 
                                                type="button" 
                                                class="dots-button stage-dots" 
                                                aria-label="Stage actions" 
                                            > 
                
                                            </button> 
 
 
                                            <div class="stage-menu"> 
 
                                                <button 
                                                    type="button" 
                                                    onclick="closeAllMenus()" 
                                                > 
                                                    View Stage 
                                                </button> 
 
                                                <button 
                                                    type="button" 
                                                    onclick="filterStage(@js($stage))" 
                                                > 
                                                    Show Deals 
                                                </button> 
 
                                                <button 
                                                    type="button" 
                                                    onclick="closeAllMenus()" 
                                                > 
                                                    Close Menu 
                                                </button> 
 
                                            </div> 
 
                                        </div> 
 
                                    </div> 
 
 
                                    <div class="column-meta"> 
 
                                        {{ number_format($stageTotal, 2) }} 
 
                                
 
                                        {{ $stageDeals->count() }} 
 
                                        {{ $stageDeals->count() === 1 ? 'Deal' : 'Deals' }} 
 
                                    </div> 
 
                                </div> 
 
 
                                {{-- STAGE CONTENT --}} 
 
                                <div class="column-content"> 
 
                                    @forelse($stageDeals as $deal) 
 
                                        <div 
                                            class="deal-card" 
                                            data-deal-card 
                                            data-stage="{{ $stage }}" 
                                            data-search="{{ strtolower( 
                                                ($deal->deal_code ?? '') . ' ' . 
                                                ($deal->deal_title ?? '') . ' ' . 
                                                ($deal->first_name ?? '') . ' ' . 
                                                ($deal->last_name ?? '') . ' ' . 
                                                ($deal->company ?? '') . ' ' . 
                                                ($deal->company_name ?? '') 
                                            ) }}" 
                                            onclick="openDeal('{{ route('deals.show', $deal->id) }}', event)" 
                                        > 
 
 
                                            {{-- CARD MENU --}} 
 
                                            <div class="deal-menu-wrap"> 
 
                                                <button 
                                                    type="button" 
                                                    class="deal-dots" 
                                                    onclick="toggleDealMenu(event, this)" 
                                                > 
                                                   
                                                </button> 
 
 
                                                <div class="deal-menu"> 
 
                                                    <a 
                                                        href="{{ route('deals.show', $deal->id) }}" 
                                                        onclick="event.stopPropagation();" 
                                                    > 
                                                        View Deal 
                                                    </a> 
 
 
                                                    <button 
                                                        type="button" 
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
 
 
                                            {{-- CODE --}} 
 
                                            <div class="deal-code"> 
 
                                                {{ $deal->deal_code ?: 'CONDEAL-' . date('Y') . '-' . str_pad($deal->id, 3, '0', STR_PAD_LEFT) }} 
 
                                            </div> 
 
 
                                            {{-- TITLE --}} 
 
                                            <div class="deal-title"> 
 
                                                {{ $deal->deal_title ?: 'Untitled Deal' }} 
 
                                            </div> 
 
 
                                            {{-- COMPANY --}} 
 
                                            <div class="deal-company"> 
 
                                                @if($deal->company_name) 
 
                                                    {{ $deal->company_name }} 
 
                                                @elseif($deal->company) 
 
                                                    {{ $deal->company }} 
 
                                                @else 
 
                                                
 
                                                @endif 
 
                                            </div> 
 
 
                                            <hr class="deal-divider"> 
 
 
                                            {{-- PRICE --}} 
 
                                            <div class="deal-price-label"> 
 
                                                <span> 
                                                    PRICE 
                                                </span> 
 
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
 
                                            </div> 
 
 
                                            <div class="deal-price"> 
 
                                                {{ number_format( 
                                                    (float) ( 
                                                        $deal->total_estimated_engagement_value 
                                                        ?? $deal->amount 
                                                        ?? 0 
                                                    ), 
                                                    2 
                                                ) }} 
 
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
 
                                                    {{ strtoupper(substr($deal->owner_name ?: 'U', 0, 2)) }} 
 
                                                </span> 
 
                                                <span> 
 
                                                    {{ $deal->owner_name ?: 'Unassigned' }} 
 
                                                </span> 
 
                                            </div> 

                                              {{-- START --}}

                                                <div class="start-card-action">

                                                <a
                                                    href="{{ route('deals.start', ['id' => $deal->id]) }}"
                                                    class="start-card-button"
                                                    onclick="event.stopPropagation();"
                                                >
                                                    GO TO START
                                                </a>

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
                <div class="drawer-subtitle">Select an existing client, then complete the consulting and deal form.</div> 
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
       OPEN ADD DEAL DRAWER 
    ========================================================= */ 
 
    function openAddDealDrawer() { 
 
        addDealFrame.src = 
            "{{ route('deals.create') }}?drawer=1"; 
 
        document.getElementById('drawerTitle').textContent = 'Create Deal'; 
 
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
                        !currentUrl.includes('/deals/create') 
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
 
        if ( 
            event.target.closest('.deal-menu-wrap') 
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
 
 
                    const menu = 
                        button.parentElement.querySelector('.stage-menu'); 
 
 
                    closeAllMenus(); 
 
 
                    menu.classList.toggle('show'); 
 
                } 
            ); 
 
        }); 
 
 
    /* ========================================================= 
       CLOSE MENUS 
    ========================================================= */ 
 
    function closeAllMenus() { 
 
        document 
            .querySelectorAll('.deal-menu.show') 
            .forEach(function(menu) { 
 
                menu.classList.remove('show'); 
 
            }); 
 
 
        document 
            .querySelectorAll('.stage-menu.show') 
            .forEach(function(menu) { 
 
                menu.classList.remove('show'); 
 
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
 
    const searchInput = 
        document.querySelector('.search-box'); 
 
 
    if (searchInput) { 
 
        searchInput.addEventListener( 
            'input', 
            function() { 
 
                const search = 
                    this.value.toLowerCase().trim(); 
 
 
                document 
                    .querySelectorAll('[data-deal-card]') 
                    .forEach(function(card) { 
 
                        const text = 
                            card.dataset.search || ''; 
 
 
                        card.style.display = 
                            !search || 
                            text.includes(search) 
                                ? '' 
                                : 'none'; 
 
                    }); 
 
            } 
        ); 
 
    } 
 
</script> 
 
 
 
{{-- ============================================================= 
     FLASH MESSAGE 
============================================================= --}} 
 
@if(session('success')) 
 
    <div 
        class="flash-message" 
        id="flashMessage" 
    > 
 
        {{ session('success') }} 
 
    </div> 
 
 
    <script> 
 
        setTimeout(function() { 
 
            const flash = 
                document.getElementById('flashMessage'); 
 
            if (flash) { 
 
                flash.style.display = 'none'; 
 
            } 
 
        }, 3500); 
 
    </script> 
 
@endif 
 
 
</body> 
 
</html> dito 
