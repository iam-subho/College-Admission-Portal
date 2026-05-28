// Public landing — Online Admission Portal homepage (transactional, not marketing)

const { useState: ppUseState, useEffect: ppUseEffect } = React;

function PublicLanding({ onLogin, setToast }) {
  return <>
    <AdmissionStatusBar/>
    <Marquee/>
    <LoginRegisterHero onLogin={onLogin}/>
    <LiveCountersStrip/>
    <AdmissionProcessBlock/>
    <KeyDatesAndLinks onLogin={onLogin}/>
    <DocumentsRequiredBlock/>
    <FAQBlock/>
    <HelpBlock/>
    <SiteFooter/>
  </>;
}

// ===== Compact status bar — title + round status + countdown =====
function AdmissionStatusBar() {
  // Static countdown — Application closes 31 May 2026 23:59 IST
  const days = 4, hours = 9, mins = 27;
  return (
    <section className="adm-status-bar" id="main">
      <div className="container">
        <div className="adm-status-row">
          <div>
            <h2 className="adm-status-title">Online Admission Portal · Session 2026-27</h2>
            <div className="adm-status-sub">Undergraduate Programmes · NEP 2020 aligned · DigiLocker + ABC integrated</div>
          </div>
          <div className="round-pill"><span className="dot"></span> Round-2 Open</div>
          <div className="countdown">
            <div className="cd-box"><div className="cdv">{String(days).padStart(2, "0")}</div><div className="cdl">Days</div></div>
            <div className="cd-box"><div className="cdv">{String(hours).padStart(2, "0")}</div><div className="cdl">Hours</div></div>
            <div className="cd-box"><div className="cdv">{String(mins).padStart(2, "0")}</div><div className="cdl">Mins</div></div>
            <div className="cd-box" style={{ background: "rgba(232,119,34,0.15)" }}><div className="cdv" style={{ fontSize: 13, lineHeight: 1.15 }}>31 May<br/><span style={{ fontSize: 10, color: "rgba(255,255,255,0.7)" }}>23:59 IST</span></div></div>
          </div>
        </div>
      </div>
    </section>
  );
}

// ===== Login + Register hero =====
function LoginRegisterHero({ onLogin }) {
  const [tab, setTab] = ppUseState("student");
  return (
    <section className="adm-hero">
      <div className="container">
        <div className="adm-hero-grid">
          {/* NEW APPLICANT — REGISTER */}
          <div className="adm-action-card register">
            <div className="aac-eyebrow"><span className="num">1</span> New Applicant</div>
            <h3>Register for Admission</h3>
            <p className="aac-sub">Create your account to apply for any undergraduate programme. Registration takes 2 minutes — you'll need your Aadhaar, email and mobile number.</p>

            <div className="fg-2" style={{ marginBottom: 8 }}>
              <div className="form-row"><label>Full Name (as per Aadhaar) <span className="req">*</span></label><input placeholder="Enter your full name"/></div>
              <div className="form-row"><label>Aadhaar Number <span className="req">*</span></label><input placeholder="XXXX XXXX XXXX"/></div>
            </div>
            <div className="fg-2" style={{ marginBottom: 8 }}>
              <div className="form-row"><label>Email Address <span className="req">*</span></label><input placeholder="you@example.com" type="email"/></div>
              <div className="form-row"><label>Mobile Number <span className="req">*</span></label><input placeholder="10-digit mobile"/></div>
            </div>
            <div className="fg-2" style={{ marginBottom: 8 }}>
              <div className="form-row"><label>Date of Birth <span className="req">*</span></label><input type="date"/></div>
              <div className="form-row"><label>Programme Interest</label>
                <select><option>Choose programme…</option>{PROGRAMMES.map(p => <option key={p.code}>{p.code} — {p.name}</option>)}</select>
              </div>
            </div>
            <label style={{ display: "flex", gap: 6, alignItems: "flex-start", fontSize: 11.5, color: "var(--ink-soft)", marginBottom: 10, marginTop: 6 }}>
              <input type="checkbox" defaultChecked style={{ marginTop: 2 }}/>
              I agree to the <a href="#">Terms & Conditions</a> and <a href="#">Privacy Policy</a>. I authorise SVNC to verify my Aadhaar and academic records via DigiLocker.
            </label>
            <button className="btn accent lg" style={{ width: "100%", justifyContent: "center", marginTop: "auto" }} onClick={onLogin}>
              <PIcon name="arrowR" size={14}/> Register & Verify Mobile via OTP
            </button>
            <div style={{ textAlign: "center", marginTop: 10, fontSize: 11.5, color: "var(--ink-mute)" }}>
              Already registered? <a href="#" onClick={(e) => { e.preventDefault(); onLogin(); }}>Sign in instead</a>
            </div>
          </div>

          {/* RETURNING APPLICANT — LOGIN */}
          <div className="adm-action-card">
            <div className="aac-eyebrow"><span className="num">2</span> Returning Applicant</div>
            <h3>Sign in to Continue Application</h3>
            <p className="aac-sub">Resume your application, check status, upload pending documents, pay fees, or download admit / fee receipt.</p>

            <div className="login-tabs">
              <button className={tab === "student" ? "active" : ""} onClick={() => setTab("student")}>Student</button>
              <button className={tab === "admin" ? "active" : ""} onClick={() => setTab("admin")}>Admin / Faculty</button>
            </div>
            <div className="form-row"><label>Application Number / Email <span className="req">*</span></label>
              <input defaultValue={tab === "student" ? "SVNC/UG/2026/039812" : "EMP-2018-0042"}/></div>
            <div className="form-row"><label>Password <span className="req">*</span></label>
              <input type="password" defaultValue="••••••••••"/></div>
            <div className="form-row captcha-row">
              <div style={{ flex: 1 }}>
                <label>Enter Captcha <span className="req">*</span></label>
                <input placeholder="Type the text shown"/>
              </div>
              <div>
                <label style={{ visibility: "hidden" }}>.</label>
                <div className="captcha-img">7K9X4P</div>
              </div>
              <div>
                <label style={{ visibility: "hidden" }}>.</label>
                <button className="btn sm" style={{ padding: "6px 8px" }}><PIcon name="refresh" size={12}/></button>
              </div>
            </div>
            <div style={{ display: "flex", justifyContent: "space-between", fontSize: 11.5, margin: "4px 0 8px" }}>
              <label style={{ display: "flex", gap: 4, alignItems: "center" }}>
                <input type="checkbox" defaultChecked/> Keep me signed in
              </label>
              <a href="#">Forgot password?</a>
            </div>
            <button className="btn-primary" onClick={onLogin}>
              <PIcon name="lock" size={13}/> Sign In Securely
            </button>

            <div className="alt-login">
              <div className="alt-label">— Alternative sign-in methods —</div>
              <div className="alt-buttons" style={{ gridTemplateColumns: "1fr 1fr 1fr" }}>
                <button className="alt-btn" onClick={onLogin}><span className="ic">DL</span> DigiLocker</button>
                <button className="alt-btn" onClick={onLogin}><span className="ic green">A</span> Aadhaar OTP</button>
                <button className="alt-btn" onClick={onLogin}><span className="ic saffron">ABC</span> ABC ID</button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>
  );
}

// ===== Live counters strip =====
function LiveCountersStrip() {
  return (
    <div className="counter-strip">
      <div className="container">
        <div className="counter-grid">
          <div className="counter-tile live"><div className="cnt-v">7,289</div><div className="cnt-l">Applications Received</div></div>
          <div className="counter-tile"><div className="cnt-v">5,142</div><div className="cnt-l">Verified</div></div>
          <div className="counter-tile"><div className="cnt-v">2,147</div><div className="cnt-l">Under Review</div></div>
          <div className="counter-tile"><div className="cnt-v">487</div><div className="cnt-l">Allotted Round-1</div></div>
          <div className="counter-tile"><div className="cnt-v">163</div><div className="cnt-l">Vacant Seats</div></div>
          <div className="counter-tile"><div className="cnt-v" style={{ fontFamily: "var(--font-mono)", fontSize: 22 }}>₹2.84 Cr</div><div className="cnt-l">Fees Collected</div></div>
        </div>
      </div>
    </div>
  );
}

// ===== Admission process block =====
function AdmissionProcessBlock() {
  const steps = [
    { lbl: "Register", sub: "Account + OTP", done: true },
    { lbl: "Verify Email", sub: "Mobile OTP", done: true },
    { lbl: "Profile Info", sub: "Personal + Family", done: true },
    { lbl: "Link DigiLocker", sub: "Auto-fetch docs", done: false, active: true },
    { lbl: "Programme", sub: "Choose & rank", done: false },
    { lbl: "Upload Docs", sub: "Photo, signature", done: false },
    { lbl: "Pay Fee", sub: "UPI / Net Banking", done: false },
    { lbl: "Submit", sub: "Final preview", done: false },
  ];
  return (
    <section className="section">
      <div className="container">
        <div className="section-head-row">
          <div>
            <h2 className="section-title">Admission Process — Eight Simple Steps</h2>
            <p className="section-sub">The complete online application flow · Average completion time: 22 minutes if all documents are ready</p>
          </div>
          <a href="#" style={{ fontSize: 12.5, fontWeight: 600 }}>Detailed Guide (PDF) →</a>
        </div>
        <div className="process-steps">
          {steps.map((s, i) => (
            <div key={i} className={`process-step ${s.done ? "done" : ""} ${s.active ? "active" : ""}`}>
              <div className="ps-num">{s.done ? <PIcon name="check" size={14} stroke={3}/> : i + 1}</div>
              <div className="ps-label">{s.lbl}</div>
              <div className="ps-sub">{s.sub}</div>
            </div>
          ))}
        </div>
        <div style={{ marginTop: 12, fontSize: 11.5, color: "var(--ink-mute)", display: "flex", gap: 18, justifyContent: "center", flexWrap: "wrap" }}>
          <span><span style={{ display: "inline-block", width: 10, height: 10, background: "var(--green-dark)", borderRadius: 50, marginRight: 4, verticalAlign: "middle" }}/>Completed</span>
          <span><span style={{ display: "inline-block", width: 10, height: 10, background: "var(--saffron)", borderRadius: 50, marginRight: 4, verticalAlign: "middle" }}/>In Progress (sample state for a registered applicant)</span>
          <span><span style={{ display: "inline-block", width: 10, height: 10, background: "var(--bg-sunken)", border: "2px solid var(--maroon)", borderRadius: 50, marginRight: 4, verticalAlign: "middle" }}/>Pending</span>
        </div>
      </div>
    </section>
  );
}

// ===== Key Dates Table + Quick Access Tiles =====
function KeyDatesAndLinks({ onLogin }) {
  return (
    <section className="section alt">
      <div className="container">
        <div style={{ display: "grid", gridTemplateColumns: "1.5fr 1fr", gap: 24 }}>
          {/* Schedule Table */}
          <div>
            <div className="section-head-row">
              <div>
                <h2 className="section-title">Admission Calendar — Round 2</h2>
                <p className="section-sub">Important dates · Failure to meet deadlines may result in cancellation</p>
              </div>
              <button className="btn"><PIcon name="download" size={12}/> PDF</button>
            </div>
            <div className="data-table-wrap">
              <table className="data-table">
                <thead>
                  <tr>
                    <th style={{ width: 45 }}>Sl.</th>
                    <th>Activity</th>
                    <th style={{ width: 120 }}>Start</th>
                    <th style={{ width: 120 }}>End</th>
                    <th style={{ width: 110 }}>Status</th>
                  </tr>
                </thead>
                <tbody>
                  {ADMISSION_SCHEDULE.slice(0, 8).map((s, i) => (
                    <tr key={i} style={{ background: s.status === "Ongoing" ? "rgba(232, 119, 34, 0.06)" : undefined }}>
                      <td style={{ textAlign: "center" }}>{i + 1}</td>
                      <td style={{ fontWeight: s.status === "Ongoing" ? 700 : 500 }}>{s.event}</td>
                      <td style={{ fontFamily: "var(--font-mono)" }}>{s.from}</td>
                      <td style={{ fontFamily: "var(--font-mono)" }}>{s.to}</td>
                      <td>
                        {s.status === "Ongoing" && <Status kind="pending">Ongoing</Status>}
                        {s.status === "Completed" && <Status kind="verified">Completed</Status>}
                        {s.status === "Upcoming" && <Status kind="draft">Upcoming</Status>}
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
            <div style={{ marginTop: 10, fontSize: 11.5, color: "var(--ink-mute)" }}>
              All dates are tentative and may be revised by the competent authority. Notice will be published on this portal.
            </div>
          </div>

          {/* Quick access */}
          <div>
            <div className="section-head-row">
              <div>
                <h2 className="section-title">Quick Access</h2>
                <p className="section-sub">Resources & utilities</p>
              </div>
            </div>
            <div style={{ display: "grid", gridTemplateColumns: "1fr 1fr", gap: 8 }}>
              {[
                { ic: "book", t: "Information Brochure", m: "PDF · 4.8 MB" },
                { ic: "money", t: "Fee Structure", m: "PDF · 320 KB" },
                { ic: "list", t: "Reservation Policy", m: "PDF · 280 KB" },
                { ic: "check", t: "Eligibility Criteria", m: "by Programme" },
                { ic: "id", t: "DigiLocker Setup", m: "Step-by-step" },
                { ic: "id", t: "ABC ID Creation", m: "abc.gov.in →" },
                { ic: "edit", t: "Sample Application", m: "PDF · 1.1 MB" },
                { ic: "chart", t: "Past Year Cut-offs", m: "2023, 2024, 2025" },
                { ic: "print", t: "Application Print", m: "Re-print copy" },
                { ic: "phone", t: "Helpdesk", m: "1800-555-0142" },
              ].map((q, i) => (
                <a key={i} href="#" className="quick-tile" onClick={(e) => { e.preventDefault(); onLogin(); }}>
                  <div className="qtic"><PIcon name={q.ic} size={16}/></div>
                  <div className="qttxt">
                    <div className="qttitle">{q.t}</div>
                    <div className="qtmeta">{q.m}</div>
                  </div>
                </a>
              ))}
            </div>
          </div>
        </div>
      </div>
    </section>
  );
}

// ===== Documents Required =====
function DocumentsRequiredBlock() {
  return (
    <section className="section">
      <div className="container">
        <div style={{ display: "grid", gridTemplateColumns: "1fr 1fr", gap: 24 }}>
          <div>
            <div className="section-head-row">
              <div>
                <h2 className="section-title">Documents Required</h2>
                <p className="section-sub">Keep these ready before starting your application</p>
              </div>
            </div>
            <div className="data-table-wrap">
              <table className="data-table">
                <thead>
                  <tr>
                    <th style={{ width: 45 }}>Sl.</th>
                    <th>Document</th>
                    <th style={{ width: 90 }}>Format</th>
                    <th style={{ width: 80 }}>Max Size</th>
                    <th style={{ width: 110 }}>Required</th>
                  </tr>
                </thead>
                <tbody>
                  {[
                    { d: "Class X Marksheet", f: "PDF/JPG", s: "2 MB", r: "Required", dl: true },
                    { d: "Class XII Marksheet", f: "PDF/JPG", s: "2 MB", r: "Required", dl: true },
                    { d: "Aadhaar Card", f: "PDF/JPG", s: "2 MB", r: "Required", dl: true },
                    { d: "Recent Passport Photo", f: "JPG/PNG", s: "500 KB", r: "Required" },
                    { d: "Scanned Signature", f: "JPG/PNG", s: "200 KB", r: "Required" },
                    { d: "Category Certificate (OBC/SC/ST)", f: "PDF/JPG", s: "2 MB", r: "If applicable" },
                    { d: "Income Certificate (EWS / Scholarship)", f: "PDF/JPG", s: "2 MB", r: "If applicable" },
                    { d: "Migration Certificate (Non-Gujarat boards)", f: "PDF/JPG", s: "2 MB", r: "If applicable" },
                    { d: "Domicile Certificate", f: "PDF/JPG", s: "2 MB", r: "If applicable" },
                    { d: "PwBD Certificate (if disabled)", f: "PDF/JPG", s: "2 MB", r: "If applicable" },
                  ].map((d, i) => (
                    <tr key={i}>
                      <td style={{ textAlign: "center" }}>{i + 1}</td>
                      <td style={{ fontWeight: 500 }}>{d.d} {d.dl && <span style={{ marginLeft: 6, fontSize: 10, color: "var(--navy)", fontWeight: 700, background: "rgba(0, 51, 102, 0.08)", padding: "1px 5px", borderRadius: 2 }}>DigiLocker</span>}</td>
                      <td style={{ fontFamily: "var(--font-mono)", fontSize: 11 }}>{d.f}</td>
                      <td style={{ fontFamily: "var(--font-mono)", fontSize: 11 }}>{d.s}</td>
                      <td>{d.r === "Required" ? <Status kind="rejected">Required</Status> : <Status kind="draft">Conditional</Status>}</td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          </div>

          <div>
            <div className="section-head-row">
              <div>
                <h2 className="section-title">Eligibility Snapshot</h2>
                <p className="section-sub">Programme-wise minimum requirements</p>
              </div>
            </div>
            <div className="data-table-wrap">
              <table className="data-table">
                <thead>
                  <tr>
                    <th style={{ width: 80 }}>Code</th>
                    <th>Programme</th>
                    <th>Eligibility (Minimum)</th>
                  </tr>
                </thead>
                <tbody>
                  {PROGRAMMES.map(p => (
                    <tr key={p.code}>
                      <td style={{ fontFamily: "var(--font-mono)", fontWeight: 600, color: "var(--navy)" }}>{p.code}</td>
                      <td style={{ fontWeight: 500 }}>{p.name}</td>
                      <td style={{ fontSize: 11.5 }}>{p.eligibility}</td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
            <div style={{ marginTop: 12, padding: 12, background: "var(--saffron-soft)", border: "1px solid var(--saffron)", borderRadius: 2, fontSize: 12, color: "var(--saffron-dark)" }}>
              <strong>Note:</strong> SC/ST candidates: 5% relaxation in minimum marks. OBC-NCL: 3% relaxation. PwBD: 5% relaxation (applies horizontally across categories).
            </div>
          </div>
        </div>
      </div>
    </section>
  );
}

// ===== FAQ =====
function FAQBlock() {
  const [open, setOpen] = ppUseState(0);
  const faqs = [
    { q: "I forgot my application number. How do I retrieve it?", a: "Click on 'Forgot password?' below the login form and enter your registered email or mobile number. You will receive your application number and a password reset link within 5 minutes. If not received, please contact the helpdesk." },
    { q: "Can I edit my application after submission?", a: "Critical fields (Name, DOB, Aadhaar, Programme selection) cannot be edited after final submission. Non-critical fields like correspondence address and contact details can be edited until the document verification deadline. For corrections beyond this, submit a written request to admissions@svnc.ac.in." },
    { q: "Is the application fee refundable?", a: "The Application Processing Fee of ₹1,200 (₹500 for SC/ST/PwBD) plus convenience charges is non-refundable, except in cases of duplicate payment or system error. Refunds, when applicable, will be credited within 7–10 working days." },
    { q: "What if I don't have a DigiLocker account?", a: "DigiLocker is optional but recommended. It auto-verifies your 10th, 12th and Aadhaar instantly. You can still upload scanned copies manually; however manual verification takes 2–3 working days. To create a DigiLocker account, visit digilocker.gov.in." },
    { q: "Can I apply for multiple programmes?", a: "Yes. You can select up to 5 programmes in order of preference in a single application. There is no additional fee for multiple choices. Seat allocation follows your preference order based on merit." },
    { q: "When will the merit list be published?", a: "The provisional merit list for Round-2 will be published on 05-Jun-2026 at 4:00 PM. The final merit list will be published after document verification on 08-Jun-2026. You will receive an SMS and email notification once published." },
    { q: "How is the merit calculated?", a: "Merit is computed using your Class XII aggregate (Best 5 subjects), with category-wise relaxation as per GoI norms. Tie-breaker rules: (1) Higher marks in Mathematics, (2) Older date of birth, (3) Earlier application submission. Detailed rules are in the Information Brochure." },
    { q: "Where can I see live seat availability?", a: "Once logged in, the Programme Selection page shows real-time seat availability per programme and category. The Admin publishes Round-wise vacancy after each counselling round on this portal." },
  ];
  return (
    <section className="section alt">
      <div className="container">
        <div style={{ display: "grid", gridTemplateColumns: "1fr 1fr", gap: 24 }}>
          <div>
            <div className="section-head-row">
              <div>
                <h2 className="section-title">Frequently Asked Questions</h2>
                <p className="section-sub">Common queries about the admission process</p>
              </div>
            </div>
            <div className="data-table-wrap" style={{ background: "var(--bg)" }}>
              {faqs.map((f, i) => (
                <div key={i} className={`faq-item ${open === i ? "open" : ""}`}>
                  <div className="faq-q" onClick={() => setOpen(open === i ? -1 : i)}>
                    <span className="qnum">{String(i + 1).padStart(2, "0")}</span>
                    <span className="qtext">{f.q}</span>
                    <span className="qchev"><PIcon name="chevD" size={14}/></span>
                  </div>
                  {open === i && <div className="faq-a">{f.a}</div>}
                </div>
              ))}
            </div>
            <div style={{ marginTop: 12, textAlign: "center", fontSize: 12.5 }}>
              Couldn't find your answer? <a href="#">View all FAQs →</a> or <a href="#">contact helpdesk</a>
            </div>
          </div>

          <div>
            <div className="section-head-row">
              <div>
                <h2 className="section-title">System Status</h2>
                <p className="section-sub">Real-time portal health & uptime</p>
              </div>
            </div>
            <div className="data-table-wrap">
              <table className="data-table">
                <thead>
                  <tr>
                    <th>Service</th>
                    <th style={{ width: 100 }}>Status</th>
                    <th style={{ width: 120 }}>Uptime (30d)</th>
                  </tr>
                </thead>
                <tbody>
                  {[
                    { s: "Online Application Portal", st: "Operational", up: "99.98%" },
                    { s: "Payment Gateway (Razorpay)", st: "Operational", up: "99.95%" },
                    { s: "DigiLocker Integration", st: "Operational", up: "99.78%" },
                    { s: "Aadhaar OTP Service", st: "Operational", up: "99.82%" },
                    { s: "SMS Gateway", st: "Operational", up: "99.91%" },
                    { s: "Email Service", st: "Operational", up: "99.96%" },
                    { s: "Document Upload Service", st: "Degraded", up: "98.42%" },
                    { s: "Academic Bank of Credits", st: "Operational", up: "99.74%" },
                  ].map((r, i) => (
                    <tr key={i}>
                      <td style={{ fontWeight: 500 }}>{r.s}</td>
                      <td>
                        {r.st === "Operational" && <Status kind="verified">Operational</Status>}
                        {r.st === "Degraded" && <Status kind="pending">Degraded</Status>}
                      </td>
                      <td style={{ textAlign: "center", fontFamily: "var(--font-mono)", color: r.up >= "99" ? "var(--green-dark)" : "var(--saffron-dark)" }}>{r.up}</td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
            <div style={{ marginTop: 10, padding: 10, background: "var(--bg-cream)", border: "1px solid var(--border)", fontSize: 11.5, color: "var(--ink-mute)" }}>
              <strong style={{ color: "var(--ink-soft)" }}>Last checked:</strong> 27-May-2026 14:32 IST · <a href="#">Status history →</a>
            </div>

            <div style={{ marginTop: 16, padding: 16, background: "var(--bg)", border: "1px solid var(--border)", borderLeft: "4px solid var(--navy)" }}>
              <div style={{ fontFamily: "var(--font-serif)", fontSize: 14, fontWeight: 700, color: "var(--maroon)", marginBottom: 6 }}><PIcon name="info" size={14}/> Browser Compatibility</div>
              <div style={{ fontSize: 12, color: "var(--ink-soft)", lineHeight: 1.55 }}>
                This portal is best viewed on Chrome 110+, Firefox 110+, Edge 110+, or Safari 16+. Internet Explorer is not supported. For technical support during application, please use a desktop or laptop with stable internet connection.
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>
  );
}

// ===== Help & Support block =====
function HelpBlock() {
  return <>
    <section className="section">
      <div className="container">
        <div className="section-head-row">
          <div>
            <h2 className="section-title">Help & Support</h2>
            <p className="section-sub">Multiple channels to assist you through the admission process</p>
          </div>
        </div>
        <div style={{ display: "grid", gridTemplateColumns: "1fr 1fr 1fr", gap: 14 }}>
          <div className="panel">
            <div className="panel-head"><h3>Admission Helpdesk</h3><span className="meta">Mon–Sat · 9:30 AM – 5:30 PM</span></div>
            <div className="panel-body" style={{ fontSize: 12.5 }}>
              <p style={{ margin: "0 0 6px" }}><PIcon name="phone" size={13}/> Toll Free: <strong>1800-555-0142</strong></p>
              <p style={{ margin: "0 0 6px" }}><PIcon name="phone" size={13}/> Direct: <strong>+91 2692 234567 / 234568</strong></p>
              <p style={{ margin: "0 0 6px" }}><PIcon name="mail" size={13}/> <a href="#">admissions@svnc.ac.in</a></p>
              <p style={{ margin: 0 }}><PIcon name="home" size={13}/> Office of the Dean (Admissions), Admin Block</p>
            </div>
          </div>

          <div className="panel">
            <div className="panel-head"><h3>Technical Support</h3><span className="meta">Portal issues / payment / docs</span></div>
            <div className="panel-body" style={{ fontSize: 12.5 }}>
              <p style={{ margin: "0 0 6px" }}><PIcon name="phone" size={13}/> Helpline: <strong>+91 2692 234600</strong></p>
              <p style={{ margin: "0 0 6px" }}><PIcon name="mail" size={13}/> <a href="#">techsupport@svnc.ac.in</a></p>
              <p style={{ margin: "0 0 6px" }}><PIcon name="inbox" size={13} stroke={2}/> Live chat (in-portal) · 24×7 automated</p>
              <p style={{ margin: 0 }}><PIcon name="info" size={13}/> Response SLA: under 4 working hours</p>
            </div>
          </div>

          <div className="panel">
            <div className="panel-head"><h3>Grievance Redressal</h3><span className="meta">For complaints / disputes</span></div>
            <div className="panel-body" style={{ fontSize: 12.5 }}>
              <p style={{ margin: "0 0 6px" }}><strong>Prof. (Dr.) Anil R. Desai</strong> · Dean (Student Welfare)</p>
              <p style={{ margin: "0 0 6px" }}><PIcon name="phone" size={13}/> +91 2692 234580</p>
              <p style={{ margin: "0 0 6px" }}><PIcon name="mail" size={13}/> <a href="#">grievance@svnc.ac.in</a></p>
              <p style={{ margin: 0, fontSize: 11, color: "var(--ink-mute)" }}>Resolution within 7 working days as per UGC norms</p>
            </div>
          </div>
        </div>

        <div style={{ marginTop: 14, padding: 14, background: "var(--saffron-soft)", border: "1px solid var(--saffron)", borderRadius: 2, display: "flex", justifyContent: "space-between", alignItems: "center", flexWrap: "wrap", gap: 12 }}>
          <div style={{ fontSize: 12.5 }}>
            <div style={{ fontWeight: 700, color: "var(--saffron-dark)", marginBottom: 2 }}><PIcon name="info" size={13}/> Anti-Ragging Helpline (National)</div>
            <span style={{ color: "var(--ink-soft)" }}>1800-180-5522 · <a href="#">helpline@antiragging.in</a> · Available 24×7 · As per UGC Regulations, 2009</span>
          </div>
          <a href="#" className="btn primary sm">File a Complaint</a>
        </div>
      </div>
    </section>

    {/* Final CTA */}
    <section className="cta-strip">
      <div className="container" style={{ display: "flex", justifyContent: "space-between", alignItems: "center", flexWrap: "wrap", gap: 16, position: "relative" }}>
        <div>
          <div style={{ fontFamily: "var(--font-serif)", fontSize: 22, fontWeight: 600 }}>Ready to apply for Session 2026-27?</div>
          <div style={{ fontSize: 13, opacity: 0.85, marginTop: 4 }}>Online applications close on <strong>31 May 2026 · 23:59 IST</strong>. No late entries will be accepted.</div>
        </div>
        <div style={{ display: "flex", gap: 10 }}>
          <a href="#main" className="btn accent lg"><PIcon name="edit" size={13}/> Apply Online</a>
          <a href="#" className="btn lg" style={{ background: "rgba(255,255,255,0.1)", color: "#fff", borderColor: "rgba(255,255,255,0.3)" }}>
            <PIcon name="download" size={13}/> Information Brochure
          </a>
          <a href="#" className="btn lg" style={{ background: "rgba(255,255,255,0.1)", color: "#fff", borderColor: "rgba(255,255,255,0.3)" }}>
            <PIcon name="phone" size={13}/> 1800-555-0142
          </a>
        </div>
      </div>
    </section>
  </>;
}

Object.assign(window, { PublicLanding });
