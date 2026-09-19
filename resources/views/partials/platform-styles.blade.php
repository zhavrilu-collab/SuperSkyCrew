<style>
    :root {
        --primarna-zelena: #1b431c;
        --primarna-tamna: #112b12;
        --svijetlo-zelena: #f4f8f4;
        --bordo-crvena: #8b1414;
        --zlatna-tradicija: #d4af37;
        --tekst-tamni: #2b3a2b;
        --tema: var(--primarna-zelena);
        --tema-svijetla: var(--svijetlo-zelena);
        --tema-sjena-fokus: rgba(27, 67, 28, 0.15);
        --tema-rub-tablica: rgba(27, 67, 28, 0.18);
        --tema-greska-svijetla: #fff5f5;
    }

    body { background-color: #f5f7f5; font-family: 'Segoe UI', -apple-system, sans-serif; font-size: 13px; color: var(--tekst-tamni); }
    .small, small { font-size: 12px !important; }

    .app-header { position: sticky; top: 0; z-index: 1030; }
    .app-header > .navbar { position: relative; top: auto; z-index: auto; margin-bottom: 0 !important; }

    .bg-siletici { background: linear-gradient(135deg, var(--primarna-zelena) 0%, var(--primarna-tamna) 100%) !important; border-bottom: 3px solid var(--zlatna-tradicija); }
    nav.navbar { position: sticky; top: 0; z-index: 1030; }
    .app-header nav.navbar { position: relative; top: auto; }
    .navbar-brand.navbar-brand-org {
        font-size: 20px;
        font-weight: 600;
        font-family: 'Segoe UI', -apple-system, BlinkMacSystemFont, Roboto, sans-serif;
        line-height: 1.2;
    }
    .navbar-brand.navbar-brand-org .navbar-brand-prefix { text-transform: uppercase; }
    .navbar-brand.navbar-brand-org .navbar-brand-suffix { text-transform: none; font-weight: 600; }
    .navbar-brand-logo {
        max-height: 38px;
        width: auto;
        background: white;
        border-radius: 4px;
        padding: 2px;
        display: block;
    }
    .navbar-hamburger-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 2.4rem;
        height: 2.4rem;
        padding: 0;
        border: 1px solid rgba(212, 175, 55, 0.45);
        border-radius: 8px;
        background: rgba(255, 255, 255, 0.08);
        color: #fff;
    }
    .navbar-hamburger-btn:hover,
    .navbar-hamburger-btn:focus,
    .navbar-hamburger-btn[aria-expanded="true"] {
        background: rgba(212, 175, 55, 0.22);
        border-color: var(--zlatna-tradicija);
        color: #fff;
        box-shadow: 0 0 0 2px rgba(212, 175, 55, 0.28);
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
    .navbar-modules-menu {
        min-width: 260px;
        max-height: min(80vh, 640px);
        overflow-y: auto;
        margin-top: .55rem !important;
        padding: .5rem 0 .4rem;
        border: 1px solid rgba(27, 67, 28, 0.12);
        border-radius: 10px;
        border-top: 3px solid var(--zlatna-tradicija);
        box-shadow: 0 10px 28px rgba(17, 43, 18, 0.14) !important;
    }
    .navbar-modules-menu-header {
        padding: .35rem 1rem .55rem;
        font-size: 10px;
        font-weight: 700;
        letter-spacing: .08em;
        text-transform: uppercase;
        color: #7a8a7a;
    }
    .navbar-modules-group-head { display: flex; align-items: stretch; }
    .navbar-modules-group-head .navbar-modules-group-link { flex: 1; min-width: 0; }
    .navbar-modules-group-toggle {
        flex: 0 0 2.25rem;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border: 0;
        background: transparent;
        color: #6a7a6a;
        border-left: 1px solid rgba(27, 67, 28, 0.08);
    }
    .navbar-modules-group-toggle:hover { background: #eef5ee; color: var(--primarna-zelena); }
    .navbar-modules-chevron {
        display: inline-block;
        width: 0.45rem;
        height: 0.45rem;
        border-right: 2px solid currentColor;
        border-bottom: 2px solid currentColor;
        transform: rotate(45deg);
        margin-top: -2px;
    }
    .navbar-modules-group.is-open > .navbar-modules-group-head .navbar-modules-chevron {
        transform: rotate(-135deg);
        margin-top: 3px;
    }
    .navbar-modules-submenu {
        display: none;
        padding: .15rem 0 .35rem;
        background: #f7faf7;
        border-top: 1px solid rgba(27, 67, 28, 0.06);
        border-bottom: 1px solid rgba(27, 67, 28, 0.06);
    }
    .navbar-modules-group.is-open > .navbar-modules-submenu { display: block; }
    .navbar-modules-submenu .dropdown-item {
        font-size: 12px;
        padding: .38rem 1rem .38rem 1.65rem;
        font-weight: 500;
        color: #445044;
    }
    .navbar-modules-group--nested > .navbar-modules-group-head .navbar-modules-group-link {
        padding-left: 1.65rem;
        font-size: 12px;
        color: #445044;
    }
    .navbar-modules-group--nested > .navbar-modules-submenu {
        background: #eef4ee;
    }
    .navbar-modules-group--nested > .navbar-modules-submenu .dropdown-item {
        padding-left: 2.45rem;
        font-size: 11.5px;
    }
    .navbar-modules-user { padding: .25rem .75rem .55rem; }
    .navbar-modules-user-card {
        display: flex;
        align-items: center;
        gap: .65rem;
        padding: .55rem .7rem;
        margin-bottom: .55rem;
        border-radius: 10px;
        background: linear-gradient(135deg, #f4f8f4 0%, #eef4ee 100%);
        border: 1px solid rgba(27, 67, 28, 0.08);
    }
    .navbar-modules-user-avatar {
        flex-shrink: 0;
        width: 2rem;
        height: 2rem;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 50%;
        background: linear-gradient(135deg, var(--primarna-zelena), var(--primarna-tamna));
        color: #fff;
        font-size: 12px;
        font-weight: 700;
        border: 2px solid rgba(212, 175, 55, 0.55);
    }
    .navbar-modules-user-label, .navbar-user-bar-label {
        font-size: 10px;
        font-weight: 700;
        letter-spacing: .06em;
        text-transform: uppercase;
        color: #7a8a7a;
    }
    .navbar-user-bar-label { color: rgba(255, 255, 255, 0.55); font-size: 9px; margin-bottom: .05rem; }
    .navbar-modules-user-email { font-size: 12px; font-weight: 600; color: var(--tekst-tamni); }
    .navbar-user-bar-card {
        display: flex;
        align-items: center;
        gap: .55rem;
        max-width: 16rem;
        padding: .3rem .55rem .3rem .35rem;
        border-radius: 10px;
        background: rgba(255, 255, 255, 0.1);
        border: 1px solid rgba(255, 255, 255, 0.18);
    }
    .navbar-user-bar .navbar-modules-user-avatar { width: 1.85rem; height: 1.85rem; font-size: 11px; }
    .navbar-user-bar-email { font-size: 12px; font-weight: 600; color: #fff; max-width: 12rem; }
    .btn-navbar-logout {
        border-radius: 9px !important;
        border: 1px solid rgba(27, 67, 28, 0.22) !important;
        background: #fff !important;
        color: var(--primarna-zelena) !important;
        font-size: 12px !important;
        font-weight: 600 !important;
        padding: .4rem .75rem !important;
    }
    .btn-navbar-logout:hover { background: var(--primarna-zelena) !important; color: #fff !important; }
    .btn-navbar-logout--header {
        border-color: rgba(255, 255, 255, 0.35) !important;
        background: rgba(255, 255, 255, 0.08) !important;
        color: #fff !important;
    }
    .btn-navbar-logout--header:hover {
        background: rgba(212, 175, 55, 0.28) !important;
        border-color: var(--zlatna-tradicija) !important;
        color: #fff !important;
    }
    .navbar-modules-menu .dropdown-divider { margin: .35rem .75rem; border-top-color: rgba(27, 67, 28, 0.1); }
    .navbar-modules-menu .dropdown-item {
        font-size: 13px;
        padding: .5rem 1rem;
        color: var(--tekst-tamni);
        border-left: 3px solid transparent;
    }
    .navbar-modules-menu .dropdown-item:hover,
    .navbar-modules-menu .dropdown-item:focus {
        background: #eef5ee;
        color: var(--primarna-zelena);
        border-left-color: rgba(212, 175, 55, 0.55);
    }
    .navbar-modules-menu .dropdown-item.active {
        background: var(--svijetlo-zelena) !important;
        color: var(--primarna-zelena) !important;
        font-weight: 700;
        border-left-color: var(--zlatna-tradicija);
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
        border-color: rgba(27, 67, 28, 0.45) !important;
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
    .forma-sekcija { margin: 1.15rem 0 0.75rem; padding-top: .65rem; border-top: 1px solid rgba(27, 67, 28, 0.08); }
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
        border-top: 1px solid rgba(27, 67, 28, 0.08);
        background: #fff;
        max-width: 100%;
    }
    .forma-modal .modal-content { border-radius: 16px; border: 1px solid rgba(0,0,0,0.06); }
    .forma-modal .modal-header { border-bottom: 1px solid rgba(27, 67, 28, 0.08); padding: 1rem 1.15rem; }
    .forma-modal .modal-title { font-size: 1rem; font-weight: 700; color: var(--primarna-zelena); }
    .forma-modal .modal-body { padding: 1.15rem; }
    .forma-modal .modal-footer { border-top: 1px solid rgba(27, 67, 28, 0.08); background: #fff; border-radius: 0 0 16px 16px; }

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
        box-shadow: -8px 0 8px -8px rgba(27, 67, 28, 0.18);
    }
    thead .table-akcije { background-color: var(--svijetlo-zelena) !important; z-index: 5; }
    .org-shema { overflow-x: auto; padding: .5rem 0 1.5rem; }
    .org-shema ul { display: flex; justify-content: center; padding-top: 22px; position: relative; gap: 8px; margin: 0; }
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
        border-color: rgba(27, 67, 28, 0.28);
    }
    .org-shema li::before {
        left: 50%;
        border-left: 1px solid rgba(27, 67, 28, 0.28);
        height: 22px;
    }
    .org-shema li::after {
        width: 100%;
        left: 0;
        border-top: 1px solid rgba(27, 67, 28, 0.28);
    }
    .org-shema li:only-child::after,
    .org-shema > ul > li::before,
    .org-shema > ul > li::after { display: none; }
    .org-shema li:first-child::after { left: 50%; width: 50%; }
    .org-shema li:last-child::after { width: 50%; }
    .org-kutija {
        width: 180px;
        background: #fff;
        border: 1px solid rgba(27, 67, 28, 0.12);
        border-radius: 12px;
        overflow: hidden;
        text-align: left;
        cursor: pointer;
        color: inherit;
        padding: 0;
        position: relative;
    }
    .org-kutija:hover { border-color: var(--primarna-zelena); }
    .org-kutija-kapa {
        background: var(--primarna-zelena);
        color: #fff;
        font-weight: 700;
        font-size: 12px;
        padding: 8px 10px;
        line-height: 1.3;
    }
    .org-kutija-root .org-kutija-kapa { background: var(--primarna-tamna); border-bottom: 3px solid var(--zlatna-tradicija); }
    .org-kutija-funkcijska .org-kutija-kapa { background: var(--primarna-tamna); }
    .org-kutija-mjesto .org-kutija-kapa { background: var(--zlatna-tradicija); color: var(--tekst-tamni); }
    .org-kutija-osoba { width: 210px; cursor: pointer; }
    .org-kutija-tijelo { padding: 8px 10px 10px; font-size: 12px; color: #5a6a5a; }
    .org-kutija-tijelo strong { display: block; color: var(--tekst-tamni); font-weight: 600; }
    .org-kutija-akcije {
        display: none;
        position: absolute;
        inset: auto 6px 6px 6px;
        gap: 4px;
        flex-wrap: wrap;
        justify-content: center;
    }
    .org-kutija:hover .org-kutija-akcije { display: flex; }
    .org-kutija:hover .org-kutija-tijelo { padding-bottom: 2.4rem; }
    .org-kutija-akcije .btn { font-size: 11px; padding: .15rem .4rem; }
    .org-kutija-osoba-tijelo { display: flex; gap: 8px; align-items: flex-start; }
    .org-inicijali {
        flex-shrink: 0;
        width: 36px; height: 36px;
        border-radius: 50%;
        display: inline-flex; align-items: center; justify-content: center;
        background: var(--svijetlo-zelena);
        color: var(--primarna-zelena);
        font-weight: 700;
        font-size: 12px;
        border: 1px solid rgba(212, 175, 55, 0.45);
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
        display: block; background: #f7faf7; border: 1px solid rgba(27, 67, 28, 0.08);
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
        border: 1px solid rgba(27, 67, 28, 0.08);
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

    .kartica-modula {
        background: white;
        border-radius: 14px;
        padding: 22px 14px;
        text-align: center;
        box-shadow: 0 5px 18px rgba(0,0,0,0.07);
        border: 2px solid transparent;
        text-decoration: none;
        color: inherit;
        display: block;
        height: 100%;
    }
    .kartica-modula:hover { transform: translateY(-3px); border-color: var(--primarna-zelena); color: inherit; }
    .kartica-modula .ikona { font-size: 2.15rem; margin-bottom: 10px; display: block; line-height: 1; }
    .kartica-modula h3 { font-size: 1.05rem; font-weight: 700; color: var(--primarna-zelena); margin-bottom: 6px; }
    .kartica-modula p { color: #666; font-size: 0.78rem; margin: 0; line-height: 1.4; }
    .badge-modul { display: inline-block; background: #d4edda; color: #155724; border-radius: 20px; padding: 2px 9px; font-size: 10px; font-weight: 600; margin-top: 9px; }
    .moduli { max-width: 960px; }
    .kartica-modula h3 { font-size: 1.05rem; font-weight: 700; color: var(--primarna-zelena); margin-bottom: 6px; }
    .kartica-modula p { color: #666; font-size: 0.78rem; margin: 0; line-height: 1.4; }
    .badge-modul { display: inline-block; background: #d4edda; color: #155724; border-radius: 20px; padding: 2px 9px; font-size: 10px; font-weight: 600; margin-top: 9px; }

    .landing-hero {
        background: linear-gradient(135deg, var(--primarna-zelena) 0%, var(--primarna-tamna) 100%);
        border-bottom: 3px solid var(--zlatna-tradicija);
        padding: 22px 14px 24px;
        text-align: center;
        color: white;
    }
    .landing-hero img { max-height: 64px; background: white; border-radius: 6px; padding: 4px; margin-bottom: 10px; }
    .landing-hero h1 { font-size: 1.4rem; font-weight: 700; margin: 0; }
    .landing-hero p { color: rgba(255,255,255,0.75); font-size: 0.88rem; margin-top: 5px; }

    .tema-svatch {
        width: 28px;
        height: 28px;
        border-radius: 50%;
        border: 2px solid #fff;
        box-shadow: 0 0 0 1px rgba(27, 67, 28, 0.2);
        padding: 0;
        cursor: pointer;
    }
    .tema-svatch.aktivna { box-shadow: 0 0 0 3px var(--zlatna-tradicija); }

    .guest-shell { min-height: 100vh; display: flex; align-items: center; }
    .page-heading { margin-bottom: 1rem; }
    .page-heading h1 { font-size: 1.15rem; font-weight: 700; color: var(--primarna-zelena); margin-bottom: .2rem; }
    .page-heading p { color: #6a7a6a; margin-bottom: 0; }
</style>
