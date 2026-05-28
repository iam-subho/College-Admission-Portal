// Portal shared UI primitives

const { useState: pUseState, useEffect: pUseEffect } = React;

// Icons
const PIcon = ({ name, size = 16, stroke = 1.8, ...rest }) => {
  const paths = {
    home: <><path d="M3 11.5L12 4l9 7.5"/><path d="M5 10v10h14V10"/></>,
    user: <><circle cx="12" cy="8" r="4"/><path d="M4 21c0-4.4 3.6-8 8-8s8 3.6 8 8"/></>,
    users: <><circle cx="9" cy="8" r="3.5"/><path d="M2 21c0-3.9 3.1-7 7-7s7 3.1 7 7"/><circle cx="17" cy="9" r="2.5"/><path d="M22 19c0-2.5-2-4.5-4.5-4.5"/></>,
    doc: <><path d="M14 3H6v18h12V7l-4-4z"/><path d="M14 3v4h4"/></>,
    edit: <><path d="M14.5 4.5l5 5L7 22H2v-5L14.5 4.5z"/></>,
    money: <><rect x="2" y="6" width="20" height="12" rx="2"/><circle cx="12" cy="12" r="3"/></>,
    chart: <><path d="M4 20V10M10 20V4M16 20v-7M22 20H2"/></>,
    bell: <><path d="M18 16v-5a6 6 0 10-12 0v5l-2 2h16l-2-2z"/><path d="M10 21a2 2 0 004 0"/></>,
    search: <><circle cx="11" cy="11" r="7"/><path d="M21 21l-4.5-4.5"/></>,
    check: <><path d="M5 12l5 5L20 7"/></>,
    x: <><path d="M6 6l12 12M18 6L6 18"/></>,
    chevR: <><path d="M9 6l6 6-6 6"/></>,
    chevL: <><path d="M15 6l-6 6 6 6"/></>,
    chevD: <><path d="M6 9l6 6 6-6"/></>,
    download: <><path d="M12 4v12M6 12l6 6 6-6"/><path d="M4 20h16"/></>,
    upload: <><path d="M12 20V8M6 12l6-6 6 6"/><path d="M4 20h16"/></>,
    filter: <><path d="M3 5h18l-7 9v6l-4-2v-4L3 5z"/></>,
    book: <><path d="M4 4h6a4 4 0 014 4v12a3 3 0 00-3-3H4z"/><path d="M20 4h-6a4 4 0 00-4 4v12a3 3 0 013-3h7z"/></>,
    cap: <><path d="M2 9l10-4 10 4-10 4L2 9z"/><path d="M6 11v4c0 1.7 2.7 3 6 3s6-1.3 6-3v-4"/><path d="M22 9v6"/></>,
    flag: <><path d="M5 21V4M5 4h12l-2 4 2 4H5"/></>,
    list: <><path d="M8 6h13M8 12h13M8 18h13M3 6h.01M3 12h.01M3 18h.01"/></>,
    phone: <><path d="M5 4h4l2 5-3 2a13 13 0 006 6l2-3 5 2v4a2 2 0 01-2 2A17 17 0 013 6a2 2 0 012-2z"/></>,
    mail: <><rect x="3" y="5" width="18" height="14" rx="2"/><path d="M3 7l9 6 9-6"/></>,
    lock: <><rect x="4" y="11" width="16" height="10" rx="2"/><path d="M8 11V7a4 4 0 018 0v4"/></>,
    id: <><rect x="3" y="5" width="18" height="14" rx="2"/><circle cx="9" cy="12" r="2.5"/><path d="M14 10h5M14 13h5M5 18.5c.5-2 2.5-3 4-3s3.5 1 4 3"/></>,
    print: <><path d="M6 9V3h12v6M6 18h12v3H6zM4 9h16a2 2 0 012 2v5a2 2 0 01-2 2h-2v-3H6v3H4a2 2 0 01-2-2v-5a2 2 0 012-2z"/></>,
    settings: <><circle cx="12" cy="12" r="3"/><path d="M12 1v6m0 10v6M4.22 4.22l4.24 4.24m7.07 7.07l4.24 4.24M1 12h6m10 0h6M4.22 19.78l4.24-4.24m7.07-7.07l4.24-4.24"/></>,
    info: <><circle cx="12" cy="12" r="9"/><path d="M12 8v0.01M12 11v6"/></>,
    star: <><path d="M12 3l2.7 5.6 6.1.9-4.4 4.3 1 6.1L12 17l-5.4 2.9 1-6.1L3.3 9.5l6.1-.9L12 3z"/></>,
    file: <><path d="M14 3H6v18h12V7l-4-4z"/><path d="M14 3v4h4M8 12h8M8 16h8M8 8h2"/></>,
    upload2: <><path d="M4 17v3h16v-3M12 3v12M7 8l5-5 5 5"/></>,
    eye: <><path d="M2 12s4-7 10-7 10 7 10 7-4 7-10 7S2 12 2 12z"/><circle cx="12" cy="12" r="3"/></>,
    cal: <><rect x="3" y="5" width="18" height="16" rx="2"/><path d="M16 3v4M8 3v4M3 11h18"/></>,
    moreH: <><circle cx="6" cy="12" r="1.5"/><circle cx="12" cy="12" r="1.5"/><circle cx="18" cy="12" r="1.5"/></>,
    refresh: <><path d="M3 12a9 9 0 0115-6.7L21 8"/><path d="M21 3v5h-5"/><path d="M21 12a9 9 0 01-15 6.7L3 16"/><path d="M3 21v-5h5"/></>,
    plus: <><path d="M12 5v14M5 12h14"/></>,
    arrowR: <><path d="M5 12h14M13 6l6 6-6 6"/></>,
    arrowL: <><path d="M19 12H5M11 18l-6-6 6-6"/></>,
  };
  return (
    <svg className="ic" width={size} height={size} viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth={stroke} strokeLinecap="round" strokeLinejoin="round" {...rest}>
      {paths[name] || null}
    </svg>
  );
};

const Status = ({ kind, children }) => <span className={`status ${kind}`}>{children}</span>;

const NewBadge = () => <span className="notice-pill new">New</span>;

// Format INR
const inr = (n) => "₹" + n.toLocaleString("en-IN");

// Header components
function TopUtilityBar({ onLoginClick }) {
  const [lang, setLang] = pUseState("en");
  return (
    <div className="util-bar">
      <div className="container">
        <div className="util-links">
          <a href="#main">Skip to main content</a>
          <span>|</span>
          <a href="#">Screen Reader Access</a>
          <span>|</span>
          <a href="#">Sitemap</a>
          <span>|</span>
          <a href="#">Web Mail</a>
          <span>|</span>
          <a href="#" style={{ color: "#ffc070" }}>RTI</a>
        </div>
        <div className="util-right">
          <span style={{ color: "#8aa0bc" }}>Text Size:</span>
          <div className="size-toggles">
            <button title="Decrease">A-</button>
            <button title="Default">A</button>
            <button title="Increase">A+</button>
          </div>
          <div className="lang-toggle">
            <button className={lang === "en" ? "active" : ""} onClick={() => setLang("en")}>EN</button>
            <button className={lang === "hi" ? "active" : ""} onClick={() => setLang("hi")}>हिंदी</button>
          </div>
        </div>
      </div>
    </div>
  );
}

function MainHeader() {
  return <>
    <div className="main-header">
      <div className="container">
        <div className="crest">
          <span style={{ position: "relative", zIndex: 1 }}>SVNC<br/>1956</span>
        </div>
        <div className="header-text">
          <div className="uni-name">{UNIVERSITY.name}</div>
          <div className="uni-name-hi">{UNIVERSITY.nameHi}</div>
          <div className="uni-sub"><strong>{UNIVERSITY.location}</strong> · {UNIVERSITY.ugc}</div>
        </div>
        <div className="accreditation">
          <div className="badge-row">
            <div className="accred-badge gold"><strong>NAAC</strong> A++</div>
            <div className="accred-badge"><strong>NIRF</strong> #42</div>
          </div>
          <div className="badge-row">
            <div className="accred-badge">UGC 2(f) & 12B</div>
            <div className="accred-badge">NEP 2020 Aligned</div>
          </div>
          <div className="badge-row">
            <div className="accred-badge" style={{ fontSize: 10 }}>ISO 9001:2015 Certified</div>
          </div>
        </div>
      </div>
    </div>
    <div className="tricolor"></div>
  </>;
}

function MainNav({ active, setActive, onApplyClick }) {
  const items = [
    { id: "home", label: "Home" },
    { id: "about", label: "About Us" },
    { id: "admissions", label: "Admissions ▾" },
    { id: "academics", label: "Academics ▾" },
    { id: "departments", label: "Departments" },
    { id: "exam", label: "Examination" },
    { id: "research", label: "Research" },
    { id: "notifications", label: "Notifications" },
    { id: "contact", label: "Contact" },
  ];
  return (
    <nav className="main-nav">
      <div className="container">
        {items.map(it => (
          <button key={it.id} className={`nav-link ${active === it.id ? "active" : ""}`} onClick={() => setActive(it.id)}>{it.label}</button>
        ))}
        <button className="nav-link cta" onClick={onApplyClick}>
          <PIcon name="edit" size={13} stroke={2.2}/> Apply Online 2026-27
        </button>
      </div>
    </nav>
  );
}

function Marquee() {
  return (
    <div className="ticker">
      <div className="ticker-label"><PIcon name="bell" size={11} stroke={2.4}/> Latest Updates</div>
      <div className="ticker-content">
        <div className="ticker-track">
          {TICKER.concat(TICKER).map((t, i) => (
            <span key={i}>{t.new && <span className="new">NEW</span>}{t.text}</span>
          ))}
        </div>
      </div>
    </div>
  );
}

// Footer
function SiteFooter() {
  return <>
    <footer className="site-footer">
      <div className="container">
        <div className="footer-grid">
          <div className="footer-col">
            <h4>About SVNC</h4>
            <p>
              Established in 1956, Sardar Vallabhbhai National College is a state-aided autonomous institution offering NEP 2020-aligned undergraduate, postgraduate and doctoral programmes across seven schools of study.
            </p>
            <p style={{ marginTop: 10 }}>
              <strong style={{ color: "#fff" }}>Address:</strong> Plot 1, Education Park, Vallabh Vidyanagar Road, Anand, Gujarat 388120
            </p>
            <p>
              <strong style={{ color: "#fff" }}>Helpline:</strong> 1800-555-0142 (Toll Free)<br/>
              <strong style={{ color: "#fff" }}>Admissions:</strong> admissions@svnc.ac.in<br/>
              <strong style={{ color: "#fff" }}>Office Hours:</strong> 9:30 AM – 5:30 PM (Mon-Sat)
            </p>
          </div>
          <div className="footer-col">
            <h4>Quick Links</h4>
            <ul>
              <li><a href="#">→ Admissions 2026-27</a></li>
              <li><a href="#">→ Academic Calendar</a></li>
              <li><a href="#">→ Programmes Offered</a></li>
              <li><a href="#">→ Fee Structure</a></li>
              <li><a href="#">→ Examination Results</a></li>
              <li><a href="#">→ Convocation</a></li>
              <li><a href="#">→ Student Portal</a></li>
              <li><a href="#">→ Faculty Portal</a></li>
            </ul>
          </div>
          <div className="footer-col">
            <h4>Mandatory Disclosures</h4>
            <ul>
              <li><a href="#">→ NAAC Accreditation</a></li>
              <li><a href="#">→ NIRF Data</a></li>
              <li><a href="#">→ AISHE Report</a></li>
              <li><a href="#">→ NEP Implementation</a></li>
              <li><a href="#">→ IQAC Reports</a></li>
              <li><a href="#">→ Annual Report</a></li>
              <li><a href="#">→ Audit Report</a></li>
              <li><a href="#">→ Right to Information</a></li>
            </ul>
          </div>
          <div className="footer-col">
            <h4>Student Services</h4>
            <ul>
              <li><a href="#">→ Library Catalogue</a></li>
              <li><a href="#">→ Anti-Ragging Cell</a></li>
              <li><a href="#">→ Grievance Redressal</a></li>
              <li><a href="#">→ Internal Complaints Committee</a></li>
              <li><a href="#">→ SC/ST Cell</a></li>
              <li><a href="#">→ Placement Cell</a></li>
              <li><a href="#">→ Sports & NCC</a></li>
              <li><a href="#">→ Alumni Network</a></li>
            </ul>
          </div>
        </div>
      </div>
      <div className="compliance-strip">
        <div className="container">
          <a href="#">UGC</a>
          <a href="#">AICTE</a>
          <a href="#">MoE</a>
          <a href="#">NCTE</a>
          <a href="#">DigiLocker</a>
          <a href="#">Academic Bank of Credits</a>
          <a href="#">SWAYAM</a>
          <a href="#">NPTEL</a>
          <a href="#">National Scholarship Portal</a>
          <a href="#">MyGov</a>
        </div>
      </div>
      <div className="footer-bottom">
        <div className="container">
          <div>© 1956–2026 Sardar Vallabhbhai National College. All rights reserved. | Last updated: 27 May 2026</div>
          <div>Site visit count: <strong style={{ color: "#fff", fontFamily: "var(--font-mono)" }}>12,84,729</strong></div>
        </div>
      </div>
    </footer>
  </>;
}

// Modal
function PortalModal({ open, onClose, title, children, footer, size = 760 }) {
  pUseEffect(() => {
    if (!open) return;
    const h = (e) => { if (e.key === "Escape") onClose?.(); };
    window.addEventListener("keydown", h);
    document.body.style.overflow = "hidden";
    return () => { window.removeEventListener("keydown", h); document.body.style.overflow = ""; };
  }, [open, onClose]);
  if (!open) return null;
  return (
    <div className="modal-bg" onClick={onClose}>
      <div className="modal" style={{ maxWidth: size }} onClick={e => e.stopPropagation()}>
        <div className="modal-head">
          <h3>{title}</h3>
          <button onClick={onClose}>×</button>
        </div>
        <div className="modal-body">{children}</div>
        {footer && <div className="modal-foot">{footer}</div>}
      </div>
    </div>
  );
}

// Toast
function PortalToast({ msg }) { return msg ? <div className="toast">{msg}</div> : null; }

// Stat card
function StatCard({ label, value, sub, color = "navy", subKind }) {
  return (
    <div className={`stat-card ${color}`}>
      <div className="lbl">{label}</div>
      <div className="val">{value}</div>
      {sub && <div className="sub">{subKind ? <span className={subKind}>{sub}</span> : sub}</div>}
    </div>
  );
}

Object.assign(window, {
  PIcon, Status, NewBadge, inr, TopUtilityBar, MainHeader, MainNav, Marquee, SiteFooter,
  PortalModal, PortalToast, StatCard,
});
