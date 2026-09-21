<style>
    :root {
        --primarna-zelena: #0f6b64;
        --primarna-tamna: #08403c;
        --svijetlo-zelena: #eef8f7;
        --bordo-crvena: #8b1414;
        --zlatna-tradicija: #7fd8ce;
        --tekst-tamni: #1a3d3a;
        --tema: var(--primarna-zelena);
        --tema-svijetla: var(--svijetlo-zelena);
        --tema-sjena-fokus: rgba(15, 107, 100, 0.15);
        --tema-rub-tablica: rgba(15, 107, 100, 0.18);
        --tema-greska-svijetla: #fff5f5;
        --org-sloj-poslovna: #0f6b64;
        --org-sloj-funkcijska: #1a4a6b;
        --org-sloj-mjesto: #e8a317;
    }

    body { background-color: var(--svijetlo-zelena); font-family: 'Segoe UI', -apple-system, sans-serif; font-size: 13px; color: var(--tekst-tamni); }
    .small, small { font-size: 12px !important; }

    .app-shell { display: flex; min-height: 100vh; align-items: stretch; }
    .app-nav-sprite { position: absolute; width: 0; height: 0; overflow: hidden; }
    .app-sidebar {
        width: 260px;
        flex-shrink: 0;
        background: #fff;
        color: var(--tekst-tamni);
        display: flex;
        flex-direction: column;
        position: sticky;
        top: 0;
        height: 100vh;
        overflow: hidden;
        z-index: 1040;
        border-right: 1px solid color-mix(in srgb, var(--primarna-zelena) 12%, #d7e4e2);
        box-shadow: 4px 0 18px rgba(8, 64, 60, 0.04);
    }
    .app-sidebar-brand {
        display: flex;
        align-items: center;
        gap: .65rem;
        padding: .7rem .9rem;
        background: var(--primarna-tamna);
        text-decoration: none;
        color: #fff;
        min-height: 56px;
        flex-shrink: 0;
    }
    .app-sidebar-brand:hover { color: #fff; }
    .app-sidebar-mark {
        width: 34px;
        height: 34px;
        object-fit: contain;
        flex-shrink: 0;
        display: block;
    }
    .app-sidebar-brand-name {
        font-size: 15px;
        font-weight: 700;
        letter-spacing: .01em;
        line-height: 1.15;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
        text-transform: none;
    }
    .app-sidebar-brand-crew { color: #97bb4c; }
    .app-sidebar-nav {
        flex: 1;
        overflow-y: auto;
        padding: .35rem 0 .5rem;
        background: #fff;
    }
    .app-sidebar-icon {
        width: 1.05rem;
        height: 1.05rem;
        margin-top: .12rem;
        flex-shrink: 0;
        display: block;
    }
    .app-sidebar-link,
    .app-sidebar-group-link {
        display: flex;
        align-items: flex-start;
        gap: .55rem;
        color: var(--tekst-tamni);
        text-decoration: none;
        font-size: 13px;
        font-weight: 600;
        padding: .48rem .9rem;
        line-height: 1.3;
        border: 0;
        background: transparent;
    }
    .app-sidebar-link:hover,
    .app-sidebar-group-link:hover {
        color: var(--primarna-tamna);
        background: var(--svijetlo-zelena);
    }
    .app-sidebar-link.active {
        color: #fff;
        background: var(--primarna-tamna);
    }
    .app-sidebar-group-head { display: flex; align-items: stretch; }
    .app-sidebar-group-head .app-sidebar-group-link { flex: 1; min-width: 0; }
    .app-sidebar-group-head:has(.active) {
        background: var(--primarna-tamna);
        color: #fff;
    }
    .app-sidebar-group-head:has(.active) .app-sidebar-group-link,
    .app-sidebar-group-head:has(.active) .app-sidebar-group-link:hover,
    .app-sidebar-group-head:has(.active) .app-sidebar-group-toggle,
    .app-sidebar-group-head:has(.active) .app-sidebar-group-toggle:hover {
        color: #fff;
        background: transparent;
    }
    .app-sidebar-group-toggle {
        flex: 0 0 2rem;
        border: 0;
        background: transparent;
        color: #7a8a88;
    }
    .app-sidebar-group-toggle:hover { color: var(--primarna-tamna); background: var(--svijetlo-zelena); }
    .app-sidebar-chevron {
        display: inline-block;
        width: 0.42rem;
        height: 0.42rem;
        border-right: 2px solid currentColor;
        border-bottom: 2px solid currentColor;
        transform: rotate(45deg);
        margin-top: -2px;
    }
    .app-sidebar-group.is-open > .app-sidebar-group-head .app-sidebar-chevron {
        transform: rotate(-135deg);
        margin-top: 3px;
    }
    .app-sidebar-submenu { display: none; padding: .1rem 0 .35rem; background: #fbfefe; }
    .app-sidebar-group.is-open > .app-sidebar-submenu { display: block; }
    .app-sidebar-submenu .app-sidebar-link {
        font-size: 12px;
        font-weight: 500;
        padding: .38rem .9rem .38rem 1.15rem;
        color: #3d5552;
    }
    .app-sidebar-submenu .app-sidebar-link.active,
    .app-sidebar-submenu .app-sidebar-link.active:hover {
        color: var(--primarna-tamna);
        background: color-mix(in srgb, var(--primarna-zelena) 12%, #fff);
        font-weight: 600;
    }
    .app-sidebar-submenu .app-sidebar-submenu .app-sidebar-link {
        padding-left: 1.85rem;
        font-size: 11.5px;
    }
    .app-sidebar-label {
        padding: .55rem .9rem .15rem 1.15rem;
        font-size: 10px;
        font-weight: 700;
        letter-spacing: .08em;
        text-transform: uppercase;
        color: #8aa3a0;
    }
    .app-sidebar-footer {
        flex-shrink: 0;
        padding: .7rem .9rem .9rem;
        font-size: 11px;
        color: #8aa3a0;
        border-top: 1px solid #e6eeee;
        background: #fff;
    }
    .app-main { flex: 1; min-width: 0; display: flex; flex-direction: column; }
    .app-topbar {
        position: sticky;
        top: 0;
        z-index: 1030;
        min-height: 52px;
        background: #fff;
        border-bottom: 2px solid var(--zlatna-tradicija);
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: .75rem;
        padding: .45rem 1.25rem;
    }
    .app-topbar-title {
        font-size: 15px;
        font-weight: 700;
        color: var(--primarna-zelena);
        margin: 0;
        min-width: 0;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
    .app-topbar-title-prefix { text-transform: uppercase; }
    .app-topbar-user {
        display: flex;
        align-items: center;
        gap: .55rem;
        flex-shrink: 0;
    }
    .navbar-hamburger-btn {
        display: none;
        align-items: center;
        justify-content: center;
        width: 2.3rem;
        height: 2.3rem;
        padding: 0;
        border: 1px solid color-mix(in srgb, var(--primarna-zelena) 28%, transparent);
        border-radius: 8px;
        background: #fff;
        color: var(--primarna-zelena);
        flex-shrink: 0;
    }
    .navbar-hamburger-btn:hover,
    .navbar-hamburger-btn:focus,
    .navbar-hamburger-btn[aria-expanded="true"] {
        background: var(--svijetlo-zelena);
        border-color: var(--zlatna-tradicija);
    }
    .navbar-hamburger-icon {
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        width: 1.05rem;
        height: .72rem;
    }
    .navbar-hamburger-icon span {
        display: block;
        height: 2px;
        width: 100%;
        background: currentColor;
        border-radius: 2px;
    }
    .navbar-hamburger-btn[aria-expanded="true"] .navbar-hamburger-icon span:nth-child(1) { transform: translateY(4.5px) rotate(45deg); }
    .navbar-hamburger-btn[aria-expanded="true"] .navbar-hamburger-icon span:nth-child(2) { opacity: 0; }
    .navbar-hamburger-btn[aria-expanded="true"] .navbar-hamburger-icon span:nth-child(3) { transform: translateY(-4.5px) rotate(-45deg); }
    .app-sidebar-backdrop {
        display: none;
        position: fixed;
        inset: 0;
        background: rgba(8, 64, 60, 0.35);
        z-index: 1035;
    }
    .navbar-modules-user-avatar {
        flex-shrink: 0;
        width: 1.85rem;
        height: 1.85rem;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 50%;
        background: linear-gradient(135deg, var(--primarna-zelena), var(--primarna-tamna));
        color: #fff;
        font-size: 11px;
        font-weight: 700;
        border: 2px solid color-mix(in srgb, var(--zlatna-tradicija) 55%, transparent);
    }
    .navbar-user-bar-label {
        font-size: 9px;
        font-weight: 700;
        letter-spacing: .06em;
        text-transform: uppercase;
        color: #7a8a7a;
        line-height: 1.2;
    }
    .navbar-user-bar-email { font-size: 12px; font-weight: 600; color: var(--tekst-tamni); max-width: 12rem; }
    .btn-navbar-logout {
        border-radius: 9px !important;
        border: 1px solid color-mix(in srgb, var(--primarna-zelena) 22%, transparent) !important;
        background: #fff !important;
        color: var(--primarna-zelena) !important;
        font-size: 12px !important;
        font-weight: 600 !important;
        padding: .4rem .75rem !important;
    }
    .btn-navbar-logout:hover { background: var(--primarna-zelena) !important; color: #fff !important; }
    @media (max-width: 991px) {
        .navbar-hamburger-btn { display: inline-flex; }
        .app-sidebar {
            position: fixed;
            left: 0;
            transform: translateX(-100%);
            transition: transform .2s ease;
            box-shadow: 8px 0 24px rgba(8, 64, 60, 0.18);
        }
        .app-shell.sidebar-open .app-sidebar { transform: translateX(0); }
        .app-shell.sidebar-open .app-sidebar-backdrop { display: block; }
    }

    .bg-siletici { background: linear-gradient(135deg, var(--primarna-zelena) 0%, var(--primarna-tamna) 100%) !important; border-bottom: 3px solid var(--zlatna-tradicija); }
    .navbar-brand-logo {
        max-height: 38px;
        width: auto;
        background: white;
        border-radius: 4px;
        padding: 2px;
        display: block;
    }

    .nav-tabs .nav-link { color: #555; font-size: 12px; font-weight: 500; padding: 8px 14px; }
    .nav-tabs .nav-link.active { color: var(--primarna-zelena); font-weight: 700; border-bottom-color: var(--primarna-zelena); }
    .settings-subnav .nav-link { font-size: 12px; padding: 6px 12px; border-radius: 20px; color: #555; }
    .settings-subnav .nav-link.active { background: var(--primarna-zelena); color: #fff; }

    .btn { font-size: 13px; font-weight: 500; border-radius: 9px; }
    .btn-sm:not(.btn-akcija-tablica) { padding: 4px 10px !important; font-size: 12px !important; border-radius: 9px !important; }
    .btn-success, .btn-primary {
        background-color: var(--primarna-zelena) !important;
        border-color: var(--primarna-zelena) !important;
        color: #fff !important;
    }
    .btn-success:hover, .btn-primary:hover, .btn-primary:focus {
        background-color: var(--primarna-tamna) !important;
        border-color: var(--primarna-tamna) !important;
        color: #fff !important;
    }
    .btn-outline-primary, .btn-outline-success {
        color: var(--primarna-zelena) !important;
        border-color: color-mix(in srgb, var(--primarna-zelena) 45%, transparent) !important;
        background: #fff !important;
    }
    .btn-outline-primary:hover, .btn-outline-success:hover {
        background-color: var(--primarna-zelena) !important;
        border-color: var(--primarna-zelena) !important;
        color: #fff !important;
    }
    .btn-link { color: var(--primarna-zelena) !important; }
    .text-bg-primary, .text-bg-success { background-color: var(--primarna-zelena) !important; color: #fff !important; }
    .text-tema { color: var(--primarna-zelena) !important; }
    .nedjelja, .table th.nedjelja { color: var(--bordo-crvena) !important; }
    .clock-btn { background-color: var(--primarna-zelena) !important; border-color: var(--primarna-zelena) !important; color: #fff !important; }
    .clock-btn.btn-light { background: #fff !important; color: var(--primarna-tamna) !important; border-color: #fff !important; }
    a { color: var(--primarna-zelena); }
    a:hover { color: var(--primarna-tamna); }

    main .container-fluid { max-width: 100%; overflow-x: clip; }
    .form-label, .forma-label { font-size: 12px; font-weight: 600; margin-bottom: 6px; color: var(--tekst-tamni); }
    .forma-polje { margin-bottom: 0; }
    .form-control, .form-select { border-radius: 9px; min-width: 0; max-width: 100%; }
    .row > * { min-width: 0; }
    .form-control:focus, .form-select:focus {
        border-color: var(--primarna-zelena) !important;
        box-shadow: 0 0 0 3px var(--tema-sjena-fokus) !important;
    }
    .form-check-input:checked { background-color: var(--primarna-zelena); border-color: var(--primarna-zelena); }
    .forma-sekcija { margin: 1.15rem 0 0.75rem; padding-top: .65rem; border-top: 1px solid color-mix(in srgb, var(--primarna-zelena) 8%, transparent); }
    .forma-sekcija:first-child { margin-top: 0; padding-top: 0; border-top: 0; }
    .forma-sekcija h2 { font-size: 13px; font-weight: 700; color: var(--primarna-zelena); margin: 0 0 .2rem; }
    .forma-sekcija p { font-size: 12px; color: #6a7a6a; margin: 0; }
    .forma-podnozje {
        display: flex;
        justify-content: flex-end;
        gap: .5rem;
        flex-wrap: wrap;
        padding-top: 1rem;
        margin-top: 1rem;
        border-top: 1px solid color-mix(in srgb, var(--primarna-zelena) 8%, transparent);
        background: #fff;
        max-width: 100%;
    }
    .forma-modal .modal-content { border-radius: 16px; border: 1px solid rgba(0,0,0,0.06); overflow: visible; }
    .forma-modal .modal-header { border-bottom: 1px solid color-mix(in srgb, var(--primarna-zelena) 8%, transparent); padding: 1rem 1.15rem; }
    .forma-modal .modal-title { font-size: 1rem; font-weight: 700; color: var(--primarna-zelena); }
    .forma-modal .modal-body { padding: 1.15rem; overflow: visible; }
    .forma-modal .modal-footer { border-top: 1px solid color-mix(in srgb, var(--primarna-zelena) 8%, transparent); background: #fff; border-radius: 0 0 16px 16px; }

    .ustroj-toolbar { display: flex; gap: .5rem; flex-wrap: wrap; align-items: end; max-width: 100%; }
    .ustroj-toolbar .form-control { width: auto; min-width: 0; }
    .ustroj-split { display: grid; grid-template-columns: minmax(0, 1.2fr) minmax(0, .8fr); gap: 1rem; align-items: start; }
    @media (max-width: 991px) { .ustroj-split { grid-template-columns: 1fr; } }
    .ustroj-red-aktivan { background: var(--svijetlo-zelena); }
    .tablica-osoba-ime a { color: inherit; text-decoration: none; font-weight: 600; }
    .tablica-osoba-ime a:hover { color: var(--primarna-zelena); }
    .table-akcije {
        position: sticky;
        right: 0;
        background: #fff;
        white-space: nowrap;
        box-shadow: -8px 0 8px -8px var(--tema-rub-tablica);
    }
    thead .table-akcije { background-color: var(--svijetlo-zelena) !important; z-index: 5; }
    .ustroj-kontekst {
        display: flex;
        justify-content: space-between;
        align-items: flex-end;
        gap: .75rem;
        flex-wrap: wrap;
        padding: .85rem 1rem;
        border-bottom: 1px solid var(--tema-rub-tablica);
        background: #fff;
        position: sticky;
        top: 0;
        z-index: 6;
    }
    .ustroj-kontekst h2 { font-size: 1.05rem; font-weight: 700; color: var(--primarna-zelena); margin: 0 0 .15rem; }
    .ustroj-kontekst p { margin: 0; color: #6a7a6a; font-size: 12px; }
    .org-platno {
        background: #fff;
        border-radius: 12px;
        min-height: 28rem;
        overflow: auto;
    }
    .kartica-kontejner--platno { padding: 0; overflow: hidden; }
    .org-shema { overflow-x: auto; padding: 1.25rem .75rem 2rem; }
    .org-shema ul { display: flex; justify-content: center; padding-top: 22px; position: relative; gap: 12px; margin: 0; }
    .org-shema > ul { padding-top: 0; }
    .org-shema li {
        list-style: none;
        display: flex;
        flex-direction: column;
        align-items: center;
        position: relative;
        padding: 22px 6px 0;
    }
    .org-shema li::before,
    .org-shema li::after {
        content: "";
        position: absolute;
        top: 0;
        border-color: color-mix(in srgb, var(--primarna-zelena) 28%, transparent);
    }
    .org-shema li::before {
        left: 50%;
        border-left: 1px solid color-mix(in srgb, var(--primarna-zelena) 28%, transparent);
        height: 22px;
    }
    .org-shema li::after {
        width: 100%;
        left: 0;
        border-top: 1px solid color-mix(in srgb, var(--primarna-zelena) 28%, transparent);
    }
    .org-shema li:only-child::after,
    .org-shema > ul > li::before,
    .org-shema > ul > li::after { display: none; }
    .org-shema li:first-child::after { left: 50%; width: 50%; }
    .org-shema li:last-child::after { width: 50%; }
    .org-kutija-grupa { display: flex; gap: 8px; flex-wrap: wrap; justify-content: center; }
    .org-kutija {
        width: 200px;
        background: #fff;
        border: 1px solid color-mix(in srgb, var(--primarna-zelena) 12%, transparent);
        border-radius: 10px;
        overflow: hidden;
        text-align: left;
        cursor: pointer;
        color: inherit;
        padding: 0;
        position: relative;
        box-shadow: 0 2px 8px rgba(0,0,0,0.04);
    }
    .org-kutija:hover { border-color: var(--primarna-zelena); }
    .org-kutija-kapa {
        background: var(--org-sloj-poslovna);
        color: #fff;
        font-weight: 700;
        font-size: 12px;
        padding: 9px 12px;
        line-height: 1.3;
        padding-right: 2.4rem;
    }
    .org-kutija-poslovna .org-kutija-kapa { background: var(--org-sloj-poslovna); }
    .org-kutija-root .org-kutija-kapa { background: var(--primarna-tamna); border-bottom: 3px solid var(--zlatna-tradicija); }
    .org-kutija-funkcijska .org-kutija-kapa { background: var(--org-sloj-funkcijska); }
    .org-kutija-mjesto .org-kutija-kapa { background: var(--org-sloj-mjesto); color: #3a2a10; }
    .org-kutija-osoba { width: 240px; cursor: pointer; border-radius: 12px; }
    .org-kutija-osoba .org-kutija-kapa { display: none; }
    .org-kutija-tijelo { padding: 8px 12px 12px; font-size: 12px; color: #5a6a5a; }
    .org-kutija-tijelo strong { display: block; color: var(--tekst-tamni); font-weight: 600; }
    .org-kutija-akcije {
        display: none;
        position: absolute;
        top: 6px;
        right: 6px;
        gap: 2px;
        background: #fff;
        border-radius: 8px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.14);
        padding: 2px;
        z-index: 2;
    }
    .org-kutija:hover .org-kutija-akcije { display: flex; }
    .org-kutija-akcija {
        width: 26px;
        height: 26px;
        border: 0;
        background: transparent;
        border-radius: 6px;
        font-size: 13px;
        line-height: 1;
        color: var(--tekst-tamni);
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 0;
    }
    .org-kutija-akcija:hover { background: var(--svijetlo-zelena); color: var(--primarna-zelena); }
    .org-kutija-osoba-head {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 12px 12px 8px;
    }
    .org-kutija-osoba-head strong {
        font-size: 13px;
        color: var(--tekst-tamni);
        line-height: 1.25;
    }
    .org-kutija-osoba-redovi {
        padding: 0 12px 12px;
        font-size: 12px;
        color: #5a6a5a;
        line-height: 1.55;
    }
    .org-inicijali {
        flex-shrink: 0;
        width: 48px; height: 48px;
        border-radius: 50%;
        display: inline-flex; align-items: center; justify-content: center;
        background: var(--svijetlo-zelena);
        color: var(--primarna-zelena);
        font-weight: 700;
        font-size: 14px;
        border: 1px solid color-mix(in srgb, var(--zlatna-tradicija) 45%, transparent);
    }

    .profil-osobe { display: grid; grid-template-columns: minmax(0, 260px) minmax(0, 1fr); gap: 1rem; align-items: start; }
    @media (max-width: 991px) { .profil-osobe { grid-template-columns: 1fr; } }
    .profil-identitet { padding: 1.1rem; }
    .profil-avatar {
        width: 72px; height: 72px; border-radius: 50%;
        display: flex; align-items: center; justify-content: center;
        background: var(--primarna-zelena); color: #fff;
        font-weight: 700; font-size: 1.2rem;
        border: 3px solid rgba(212, 175, 55, 0.55);
        margin: 0 auto 12px;
    }
    .profil-nav { list-style: none; padding: 0; margin: 1rem 0 0; }
    .profil-nav a {
        display: block; padding: 8px 12px; border-radius: 8px;
        color: var(--tekst-tamni); text-decoration: none; font-weight: 600;
        border-left: 3px solid transparent;
    }
    .profil-nav a:hover { background: #eef5ee; color: var(--primarna-zelena); }
    .profil-nav a.active {
        background: var(--svijetlo-zelena);
        color: var(--primarna-zelena);
        border-left-color: var(--zlatna-tradicija);
    }
    .profil-plocica {
        display: block; background: var(--svijetlo-zelena); border: 1px solid color-mix(in srgb, var(--primarna-zelena) 8%, transparent);
        border-radius: 12px; padding: 12px; text-decoration: none; color: inherit; height: 100%;
    }
    .profil-plocica:hover { border-color: var(--primarna-zelena); color: inherit; }
    .profil-plocica .kpi-broj { font-size: 1.25rem; }

    .alert-success { background: var(--svijetlo-zelena); border-color: var(--primarna-zelena); color: var(--primarna-tamna); }
    .alert-danger { background: var(--tema-greska-svijetla); border-color: var(--bordo-crvena); color: var(--bordo-crvena); }
    .alert-warning { background: #fff8e8; border-color: var(--zlatna-tradicija); color: #6b5200; }

    .kartica-kontejner {
        background: white;
        border-radius: 16px;
        padding: 24px;
        box-shadow: 0 4px 16px rgba(0,0,0,0.02);
        border: 1px solid rgba(0,0,0,0.04);
        max-width: 100%;
        min-width: 0;
    }
    .table th, .table thead.table-light th {
        font-weight: 600;
        font-size: 12px;
        color: var(--primarna-zelena) !important;
        background-color: var(--svijetlo-zelena) !important;
        padding: 8px 6px !important;
        vertical-align: middle;
    }
    .table td { padding: 6px !important; vertical-align: middle; font-size: 12px; }
    .table-responsive:not(.table-responsive-no-sticky) {
        max-height: min(70vh, calc(100vh - 11rem));
        overflow: auto;
    }
    .table-responsive:not(.table-responsive-no-sticky) > .table > thead > tr > th {
        position: sticky;
        top: 0;
        z-index: 4;
        background-color: var(--svijetlo-zelena) !important;
        box-shadow: 0 1px 0 var(--tema-rub-tablica);
    }

    .kpi-kartica {
        background: #fff;
        border: 1px solid color-mix(in srgb, var(--primarna-zelena) 8%, transparent);
        border-radius: 12px;
        padding: 14px 12px;
        text-align: center;
        text-decoration: none;
        color: inherit;
        display: block;
        height: 100%;
    }
    .kpi-kartica:hover { border-color: var(--primarna-zelena); color: inherit; }
    .kpi-kartica .kpi-broj { font-size: 1.55rem; font-weight: 700; color: var(--primarna-zelena); line-height: 1.1; }
    .kpi-kartica .kpi-label { font-size: 10px; font-weight: 700; letter-spacing: .04em; text-transform: uppercase; color: #7a8a7a; }

    .tema-svatch {
        width: 28px;
        height: 28px;
        border-radius: 50%;
        border: 2px solid #fff;
        box-shadow: 0 0 0 1px color-mix(in srgb, var(--primarna-zelena) 20%, transparent);
        padding: 0;
        cursor: pointer;
    }
    .tema-svatch.aktivna { box-shadow: 0 0 0 3px var(--zlatna-tradicija); }

    .guest-shell { min-height: 100vh; display: flex; align-items: center; }
    .app-guest-lockup { display: block; max-width: 210px; width: 100%; height: auto; margin: 0 auto 1.15rem; }
    .plan-kartica {
        border: 1px solid color-mix(in srgb, var(--primarna-zelena) 18%, #dfe8e6);
        border-radius: 12px;
        padding: 1rem;
        cursor: pointer;
        height: 100%;
        display: block;
        background: #fff;
        position: relative;
    }
    .plan-kartica .plan-kartica-radio { position: absolute; top: .75rem; right: .75rem; }
    .plan-kartica.aktivna {
        border-color: var(--primarna-zelena);
        background: var(--svijetlo-zelena);
        box-shadow: 0 0 0 1px var(--primarna-zelena);
    }
    .trial-notice {
        background: linear-gradient(90deg, color-mix(in srgb, var(--zlatna-tradicija) 28%, #fff) 0%, var(--svijetlo-zelena) 40%, #eef4ee 100%);
        border-bottom: 1px solid color-mix(in srgb, var(--primarna-zelena) 14%, transparent);
        color: var(--tekst-tamni);
        font-size: 12px;
        line-height: 1.35;
    }
    .trial-notice--expired {
        background: linear-gradient(90deg, rgba(139, 20, 20, 0.08) 0%, #f7f2f2 40%, #f5f5f5 100%);
        border-bottom-color: rgba(139, 20, 20, 0.14);
    }
    .trial-notice__inner {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: .45rem .85rem;
        padding: .5rem 1.25rem;
    }
    .trial-notice__label {
        display: inline-flex;
        align-items: center;
        font-size: 10px;
        font-weight: 700;
        letter-spacing: .04em;
        text-transform: uppercase;
        color: var(--primarna-zelena);
        background: color-mix(in srgb, var(--primarna-zelena) 8%, #fff);
        border: 1px solid color-mix(in srgb, var(--zlatna-tradicija) 45%, transparent);
        border-radius: 999px;
        padding: .2rem .55rem;
        white-space: nowrap;
    }
    .trial-notice--expired .trial-notice__label {
        color: var(--bordo-crvena);
        background: rgba(139, 20, 20, 0.07);
        border-color: rgba(139, 20, 20, 0.28);
    }
    .trial-notice__text { flex: 1 1 12rem; min-width: 0; color: #3d4f3d; }
    .trial-notice--expired .trial-notice__text { color: #5a4545; }
    .trial-notice__text strong { color: var(--primarna-zelena); font-weight: 700; }
    .trial-notice--expired .trial-notice__text strong { color: var(--bordo-crvena); }
    .trial-notice__action {
        display: inline-flex;
        align-items: center;
        font-size: 11px;
        font-weight: 700;
        color: var(--primarna-zelena);
        text-decoration: none;
        border: 1px solid color-mix(in srgb, var(--primarna-zelena) 28%, transparent);
        border-radius: 8px;
        padding: .25rem .65rem;
        background: #fff;
        white-space: nowrap;
    }
    .trial-notice__action:hover {
        background: var(--primarna-zelena);
        border-color: var(--primarna-zelena);
        color: #fff;
    }
    .trial-notice--expired .trial-notice__action {
        color: var(--bordo-crvena);
        border-color: rgba(139, 20, 20, 0.3);
    }
    .trial-notice--expired .trial-notice__action:hover {
        background: var(--bordo-crvena);
        border-color: var(--bordo-crvena);
        color: #fff;
    }
    .page-heading { margin-bottom: 1rem; }
    .page-heading h1 { font-size: 1.15rem; font-weight: 700; color: var(--primarna-zelena); margin-bottom: .2rem; }
    .page-heading p { color: #6a7a6a; margin-bottom: 0; }
    .nkz-odabir { position: relative; }
    .nkz-odabir__lista {
        position: absolute;
        z-index: 30;
        left: 0;
        right: 0;
        top: calc(100% + 4px);
        max-height: 220px;
        overflow: auto;
        margin: 0;
        padding: .25rem 0;
        list-style: none;
        background: #fff;
        border: 1px solid color-mix(in srgb, var(--primarna-zelena) 22%, transparent);
        border-radius: 9px;
        box-shadow: 0 8px 24px color-mix(in srgb, var(--primarna-tamna) 12%, transparent);
    }
    .nkz-odabir__stavka {
        padding: .4rem .75rem;
        cursor: pointer;
        font-size: 13px;
        color: var(--tekst-tamni);
    }
    .nkz-odabir__stavka.is-active,
    .nkz-odabir__stavka:hover {
        background: var(--svijetlo-zelena);
    }
    .nkz-odabir__sifra {
        font-weight: 700;
        color: var(--primarna-zelena);
        margin-right: .4rem;
    }
    .nkz-odabir__prazno {
        padding: .5rem .75rem;
        color: #6a7a6a;
        font-size: 12px;
    }
</style>
