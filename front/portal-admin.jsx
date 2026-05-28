// Admin portal — dense, tabular, formal college admission management

const { useState: apUseState, useMemo: apUseMemo } = React;

const ADMIN_SECTIONS = [
  { id: "dashboard", lbl: "Dashboard", icon: "home" },
  { id: "applications", lbl: "Applications", icon: "doc", count: 7289 },
  { id: "verification", lbl: "Document Verification", icon: "check", count: 23 },
  { id: "merit", lbl: "Merit Lists", icon: "list" },
  { id: "seats", lbl: "Seat Allocation", icon: "users" },
  { id: "fee", lbl: "Fee Collection", icon: "money" },
  { id: "programmes", lbl: "Programmes", icon: "book" },
  { id: "reports", lbl: "Reports", icon: "chart" },
  { id: "notifications", lbl: "Notifications", icon: "bell" },
];

function AdminPortal({ section, setSection, setToast }) {
  return (
    <div className="portal">
      <AdminSidebar section={section} setSection={setSection}/>
      <div className="portal-main">
        {section === "dashboard" && <AdminDashboardPage setSection={setSection}/>}
        {section === "applications" && <AdminApplicationsPage setToast={setToast}/>}
        {section === "verification" && <AdminVerificationPage setToast={setToast}/>}
        {section === "merit" && <AdminMeritPage setToast={setToast}/>}
        {section === "seats" && <AdminSeatsPage/>}
        {section === "fee" && <AdminFeePage/>}
        {section === "programmes" && <AdminProgrammesPage/>}
        {section === "reports" && <AdminReportsPage/>}
        {section === "notifications" && <AdminNotificationsPage setToast={setToast}/>}
      </div>
    </div>
  );
}

function AdminSidebar({ section, setSection }) {
  return (
    <aside className="portal-side">
      <div className="portal-user">
        <div className="ph">
          <div className="av" style={{ background: "var(--navy)" }}>PM</div>
        </div>
        <div className="nm" style={{ color: "var(--navy)" }}>Dr. Priya Menon</div>
        <div className="id">EMP-2018-0042</div>
        <div className="meta">
          <div style={{ display: "flex", justifyContent: "space-between", marginBottom: 2 }}>
            <strong>Role</strong>
            <span>Dean (Admissions)</span>
          </div>
          <div style={{ display: "flex", justifyContent: "space-between", marginBottom: 2 }}>
            <strong>Department</strong>
            <span>Administration</span>
          </div>
          <div style={{ display: "flex", justifyContent: "space-between" }}>
            <strong>Session</strong>
            <span>2026-27</span>
          </div>
        </div>
      </div>

      <div className="portal-nav-section">Administration</div>
      {ADMIN_SECTIONS.map(s => (
        <button key={s.id} className={`portal-link ${section === s.id ? "active" : ""}`} onClick={() => setSection(s.id)}>
          <PIcon name={s.icon} size={13}/>
          <span className="lbl">{s.lbl}</span>
          {s.count && <span className="ct">{s.count.toLocaleString("en-IN")}</span>}
        </button>
      ))}

      <div className="portal-nav-section" style={{ marginTop: 12 }}>Tools</div>
      <button className="portal-link"><PIcon name="download" size={13}/><span className="lbl">Export Data</span></button>
      <button className="portal-link"><PIcon name="upload2" size={13}/><span className="lbl">Bulk Upload</span></button>
      <button className="portal-link"><PIcon name="settings" size={13}/><span className="lbl">Configuration</span></button>
      <button className="portal-link"><PIcon name="lock" size={13}/><span className="lbl">Audit Logs</span></button>
    </aside>
  );
}

function ACrumbs({ items }) {
  return (
    <div className="portal-crumbs">
      {items.map((it, i) => (
        <React.Fragment key={i}>
          {i > 0 && <span>›</span>}
          {it.href ? <a href="#" onClick={(e) => { e.preventDefault(); it.onClick?.(); }}>{it.label}</a> : <span style={{ color: "var(--ink)" }}>{it.label}</span>}
        </React.Fragment>
      ))}
    </div>
  );
}

// ===== ADMIN DASHBOARD =====
function AdminDashboardPage({ setSection }) {
  return <>
    <ACrumbs items={[{ label: "Home" }, { label: "Admin Dashboard" }]}/>
    <div className="portal-h1-row">
      <div>
        <h1 className="portal-h1">Admin Dashboard</h1>
        <p className="portal-h1-sub">Online Admission System · Session 2026-27 · Last refreshed: 27-May-2026 14:32 IST</p>
      </div>
      <div style={{ display: "flex", gap: 8 }}>
        <button className="btn"><PIcon name="refresh" size={12}/> Refresh</button>
        <button className="btn navy"><PIcon name="download" size={12}/> Export Snapshot</button>
        <button className="btn accent"><PIcon name="print" size={12}/> Print Report</button>
      </div>
    </div>

    {/* Bulletin */}
    <div style={{ background: "var(--saffron-soft)", border: "1px solid var(--saffron)", borderLeft: "4px solid var(--saffron)", padding: "10px 14px", marginBottom: 16, fontSize: 12.5, borderRadius: 2 }}>
      <strong style={{ color: "var(--saffron-dark)" }}>Round-2 Status:</strong> Online application window closes in <strong>4 days</strong>. 2,147 applications pending document verification. Provisional merit list for B.Sc. CS scheduled for publication on <strong>05-Jun-2026</strong>.
    </div>

    <div className="stat-grid">
      <StatCard label="Total Applications" value="7,289" sub="↑ 12.4% vs Round-1" color="navy" subKind="up"/>
      <StatCard label="Verified Applications" value="5,142" sub="70.5% of total" color="green"/>
      <StatCard label="Pending Verification" value="2,147" sub="Avg. review 6 min" color="saffron"/>
      <StatCard label="Total Fee Collected" value="₹ 2.84 Cr" sub="↑ 18% above target" color="maroon" subKind="up"/>
    </div>
    <div className="stat-grid">
      <StatCard label="Total Sanctioned Seats" value="650" sub="across 8 programmes" color="navy"/>
      <StatCard label="Allocated Seats" value="487" sub="74.9% capacity" color="green"/>
      <StatCard label="On Waitlist" value="284" color="saffron"/>
      <StatCard label="Vacant Seats" value="163" sub="Spot Round 20–25 Jun" color="maroon"/>
    </div>

    {/* Programme-wise summary */}
    <div className="panel">
      <div className="panel-head">
        <h3>Programme-wise Application Summary</h3>
        <span className="meta">All 8 UG programmes · Round-2 · 2026-27</span>
      </div>
      <div className="data-table-wrap" style={{ border: "none" }}>
        <table className="data-table">
          <thead>
            <tr>
              <th style={{ width: 50 }}>Sl.</th>
              <th style={{ width: 80 }}>Code</th>
              <th>Programme</th>
              <th style={{ width: 80 }}>Seats</th>
              <th style={{ width: 90 }}>Applied</th>
              <th style={{ width: 100 }}>Verified</th>
              <th style={{ width: 100 }}>Allocated</th>
              <th style={{ width: 100 }}>Waitlist</th>
              <th style={{ width: 110 }}>Fill Rate</th>
            </tr>
          </thead>
          <tbody>
            {PROGRAMMES.map((p, i) => {
              const verified = Math.round(p.applied * 0.7);
              const allocated = Math.round(p.intake * (0.6 + (i % 4) * 0.08));
              const waitlist = Math.round(allocated * 2.6);
              const fillPct = Math.min(100, Math.round((allocated / p.intake) * 100));
              return (
                <tr key={p.code} className="clickable">
                  <td style={{ textAlign: "center" }}>{i + 1}</td>
                  <td style={{ fontFamily: "var(--font-mono)", fontWeight: 600, color: "var(--navy)" }}>{p.code}</td>
                  <td style={{ fontWeight: 500 }}>{p.name}</td>
                  <td style={{ textAlign: "center", fontFamily: "var(--font-mono)" }}>{p.intake}</td>
                  <td style={{ textAlign: "center", fontFamily: "var(--font-mono)", fontWeight: 600 }}>{p.applied.toLocaleString("en-IN")}</td>
                  <td style={{ textAlign: "center", fontFamily: "var(--font-mono)" }}>{verified.toLocaleString("en-IN")}</td>
                  <td style={{ textAlign: "center", fontFamily: "var(--font-mono)", color: "var(--green-dark)", fontWeight: 600 }}>{allocated}</td>
                  <td style={{ textAlign: "center", fontFamily: "var(--font-mono)", color: "var(--saffron-dark)" }}>{waitlist}</td>
                  <td>
                    <div style={{ display: "flex", alignItems: "center", gap: 6 }}>
                      <div className="progress-track" style={{ flex: 1, height: 6 }}><span style={{ width: fillPct + "%" }}></span></div>
                      <span style={{ fontFamily: "var(--font-mono)", fontSize: 11, fontWeight: 600, color: fillPct >= 90 ? "var(--green-dark)" : "var(--saffron-dark)" }}>{fillPct}%</span>
                    </div>
                  </td>
                </tr>
              );
            })}
          </tbody>
        </table>
      </div>
    </div>

    <div style={{ display: "grid", gridTemplateColumns: "1.4fr 1fr", gap: 16 }}>
      {/* Category-wise */}
      <div className="panel">
        <div className="panel-head"><h3>Category-wise Application Distribution</h3></div>
        <div className="panel-body" style={{ padding: 0 }}>
          <table className="data-table">
            <thead>
              <tr>
                <th>Category</th>
                <th style={{ width: 100 }}>Applied</th>
                <th style={{ width: 100 }}>Verified</th>
                <th style={{ width: 100 }}>Allocated</th>
                <th style={{ width: 100 }}>Cut-off (%)</th>
                <th>Distribution</th>
              </tr>
            </thead>
            <tbody>
              {[
                { cat: "Unreserved (UR)", a: 2916, v: 2042, all: 195, cut: "91.00" },
                { cat: "OBC-NCL", a: 1968, v: 1378, all: 132, cut: "85.30" },
                { cat: "SC", a: 1093, v: 765, all: 73, cut: "78.20" },
                { cat: "ST", a: 547, v: 383, all: 37, cut: "75.40" },
                { cat: "EWS", a: 729, v: 510, all: 50, cut: "88.50" },
                { cat: "PwBD (Horizontal)", a: 36, v: 28, all: 12, cut: "—" },
              ].map((r, i) => {
                const total = 7289;
                const pct = (r.a / total * 100).toFixed(1);
                return (
                  <tr key={i}>
                    <td style={{ fontWeight: 500 }}>{r.cat}</td>
                    <td style={{ textAlign: "right", fontFamily: "var(--font-mono)", fontWeight: 600 }}>{r.a.toLocaleString("en-IN")}</td>
                    <td style={{ textAlign: "right", fontFamily: "var(--font-mono)" }}>{r.v.toLocaleString("en-IN")}</td>
                    <td style={{ textAlign: "right", fontFamily: "var(--font-mono)", color: "var(--green-dark)" }}>{r.all}</td>
                    <td style={{ textAlign: "right", fontFamily: "var(--font-mono)" }}>{r.cut}</td>
                    <td>
                      <div style={{ display: "flex", alignItems: "center", gap: 6 }}>
                        <div className="progress-track" style={{ flex: 1, height: 5 }}><span style={{ width: pct + "%", background: "var(--navy)" }}></span></div>
                        <span style={{ fontFamily: "var(--font-mono)", fontSize: 10.5 }}>{pct}%</span>
                      </div>
                    </td>
                  </tr>
                );
              })}
              <tr className="total">
                <td>Total</td>
                <td style={{ textAlign: "right", fontFamily: "var(--font-mono)" }}>7,289</td>
                <td style={{ textAlign: "right", fontFamily: "var(--font-mono)" }}>5,106</td>
                <td style={{ textAlign: "right", fontFamily: "var(--font-mono)" }}>499</td>
                <td colSpan={2}></td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      {/* Recent activity */}
      <div className="panel">
        <div className="panel-head"><h3>Recent Activity</h3><span className="meta">Live</span></div>
        <div className="panel-body" style={{ padding: 0 }}>
          {[
            { t: "14:32", who: "System", a: "Generated merit", w: "B.Sc. CS — Round-2 (1,842 applicants)" },
            { t: "14:28", who: "Dr. Priya Menon", a: "Verified document", w: "12th Marksheet · Aarav Patel" },
            { t: "14:24", who: "Sh. Rahul Vaidya", a: "Approved allocation", w: "Diya Krishnan → B.A. Eco-DS" },
            { t: "14:19", who: "System", a: "Fee received", w: "₹ 28,500 from Vivaan Iyer · CHN-22847" },
            { t: "14:14", who: "Ms. Anjali Sharma", a: "Marked verified", w: "Rohan Mehta — OBC-NCL Certificate" },
            { t: "14:08", who: "System", a: "Sent SMS notification", w: "Round-2 merit notice → 1,842 students" },
            { t: "14:02", who: "Dr. Priya Menon", a: "Rejected document", w: "Caste cert · Ishita B. — image unclear" },
          ].map((a, i) => (
            <div key={i} style={{ padding: "8px 14px", borderBottom: i < 6 ? "1px solid var(--border)" : "none", fontSize: 12, display: "flex", gap: 8, alignItems: "flex-start" }}>
              <span style={{ fontFamily: "var(--font-mono)", color: "var(--ink-mute)", fontSize: 10.5, marginTop: 2 }}>{a.t}</span>
              <div style={{ flex: 1, minWidth: 0 }}>
                <div style={{ fontSize: 11.5 }}><strong>{a.who}</strong> <span style={{ color: "var(--ink-mute)" }}>{a.a}</span></div>
                <div style={{ fontSize: 11, color: "var(--ink-mute)", marginTop: 1 }}>{a.w}</div>
              </div>
            </div>
          ))}
        </div>
      </div>
    </div>
  </>;
}

// ===== APPLICATIONS =====
function AdminApplicationsPage({ setToast }) {
  const [filter, setFilter] = apUseState("All");
  const [search, setSearch] = apUseState("");
  const [selected, setSelected] = apUseState(null);
  const [rows, setRows] = apUseState(new Set());

  const filtered = APP_TABLE.filter(a => {
    if (filter !== "All" && a.status !== filter) return false;
    if (search && !a.name.toLowerCase().includes(search.toLowerCase()) && !a.no.toLowerCase().includes(search.toLowerCase())) return false;
    return true;
  });

  const toggle = (no) => {
    const s = new Set(rows);
    if (s.has(no)) s.delete(no); else s.add(no);
    setRows(s);
  };

  return <>
    <ACrumbs items={[{ label: "Dashboard" }, { label: "Applications" }]}/>
    <div className="portal-h1-row">
      <div>
        <h1 className="portal-h1">Applications Register</h1>
        <p className="portal-h1-sub">Showing {filtered.length} of {APP_TABLE.length} applications · Session 2026-27 · Round-2</p>
      </div>
      <div style={{ display: "flex", gap: 8 }}>
        <button className="btn"><PIcon name="filter" size={12}/> Advanced Filters</button>
        <button className="btn"><PIcon name="download" size={12}/> Export to Excel</button>
        <button className="btn navy"><PIcon name="print" size={12}/> Print Register</button>
      </div>
    </div>

    {/* Filter bar */}
    <div className="panel">
      <div className="panel-body" style={{ padding: 12, display: "flex", gap: 12, flexWrap: "wrap", alignItems: "center" }}>
        <div style={{ flex: "1 1 240px", minWidth: 200 }}>
          <label style={{ fontSize: 11, color: "var(--ink-mute)", fontWeight: 600, display: "block", marginBottom: 2 }}>SEARCH</label>
          <div style={{ display: "flex", border: "1px solid var(--border-strong)", borderRadius: 2 }}>
            <span style={{ padding: "6px 10px", color: "var(--ink-mute)" }}><PIcon name="search" size={13}/></span>
            <input style={{ border: "none", outline: "none", flex: 1, padding: "6px 8px 6px 0", background: "transparent" }} placeholder="Application no., name, mobile…" value={search} onChange={e => setSearch(e.target.value)}/>
          </div>
        </div>
        <div style={{ flex: "1 1 140px" }}>
          <label style={{ fontSize: 11, color: "var(--ink-mute)", fontWeight: 600, display: "block", marginBottom: 2 }}>PROGRAMME</label>
          <select style={{ width: "100%" }}><option>All Programmes</option>{PROGRAMMES.map(p => <option key={p.code}>{p.code} — {p.name}</option>)}</select>
        </div>
        <div style={{ flex: "1 1 110px" }}>
          <label style={{ fontSize: 11, color: "var(--ink-mute)", fontWeight: 600, display: "block", marginBottom: 2 }}>CATEGORY</label>
          <select style={{ width: "100%" }}><option>All Categories</option><option>UR</option><option>OBC-NCL</option><option>SC</option><option>ST</option><option>EWS</option></select>
        </div>
        <div style={{ flex: "1 1 110px" }}>
          <label style={{ fontSize: 11, color: "var(--ink-mute)", fontWeight: 600, display: "block", marginBottom: 2 }}>STATUS</label>
          <select style={{ width: "100%" }} value={filter} onChange={e => setFilter(e.target.value)}>
            <option>All</option>
            <option>Verified</option>
            <option>Pending</option>
            <option>Submitted</option>
            <option>Rejected</option>
            <option>Draft</option>
          </select>
        </div>
        <div>
          <label style={{ fontSize: 11, color: "var(--ink-mute)", fontWeight: 600, display: "block", marginBottom: 2 }}>&nbsp;</label>
          <button className="btn navy">Apply</button>
        </div>
      </div>
    </div>

    {/* Bulk action bar */}
    {rows.size > 0 && (
      <div style={{ background: "var(--navy-dark)", color: "#fff", padding: "8px 14px", display: "flex", justifyContent: "space-between", alignItems: "center", marginBottom: -1, borderRadius: "3px 3px 0 0" }}>
        <span style={{ fontSize: 13, fontWeight: 600 }}>{rows.size} application(s) selected</span>
        <div style={{ display: "flex", gap: 6 }}>
          <button className="btn sm" style={{ background: "transparent", color: "#fff", borderColor: "rgba(255,255,255,0.3)" }}>Bulk Verify</button>
          <button className="btn sm" style={{ background: "transparent", color: "#fff", borderColor: "rgba(255,255,255,0.3)" }}>Send Email</button>
          <button className="btn sm" style={{ background: "transparent", color: "#fff", borderColor: "rgba(255,255,255,0.3)" }}>Send SMS</button>
          <button className="btn sm" style={{ background: "transparent", color: "#fff", borderColor: "rgba(255,255,255,0.3)" }}>Reject</button>
        </div>
      </div>
    )}

    <div className="data-table-wrap">
      <table className="data-table">
        <thead>
          <tr>
            <th style={{ width: 32 }}><input type="checkbox" onChange={e => setRows(e.target.checked ? new Set(filtered.map(a => a.no)) : new Set())}/></th>
            <th style={{ width: 40 }}>Sl.</th>
            <th>Application Number</th>
            <th>Applicant Name</th>
            <th>Programme</th>
            <th style={{ width: 90 }}>Category</th>
            <th style={{ width: 70 }}>Gender</th>
            <th style={{ width: 80 }}>Marks %</th>
            <th style={{ width: 60 }}>Docs</th>
            <th style={{ width: 80 }}>Fee</th>
            <th style={{ width: 90 }}>Status</th>
            <th style={{ width: 80 }}>Action</th>
          </tr>
        </thead>
        <tbody>
          {filtered.map((a) => (
            <tr key={a.no} className="clickable" onClick={() => setSelected(a)}>
              <td onClick={e => e.stopPropagation()}><input type="checkbox" checked={rows.has(a.no)} onChange={() => toggle(a.no)}/></td>
              <td style={{ textAlign: "center" }}>{a.sno}</td>
              <td style={{ fontFamily: "var(--font-mono)", fontWeight: 600, color: "var(--navy)" }}>{a.no}</td>
              <td style={{ fontWeight: 500 }}>{a.name}<div style={{ fontSize: 10.5, color: "var(--ink-mute)" }}>{a.state}</div></td>
              <td>{a.prog}</td>
              <td>{a.cat}</td>
              <td style={{ textAlign: "center" }}>{a.gender}</td>
              <td style={{ textAlign: "center", fontFamily: "var(--font-mono)", fontWeight: 600 }}>{a.pct.toFixed(2)}</td>
              <td style={{ textAlign: "center", fontFamily: "var(--font-mono)" }}>{a.docs}</td>
              <td>{a.fee === "Paid" && <Status kind="paid">Paid</Status>}{a.fee === "Unpaid" && <Status kind="unpaid">Unpaid</Status>}{a.fee === "Refund" && <Status kind="draft">Refund</Status>}</td>
              <td>
                {a.status === "Verified" && <Status kind="verified">Verified</Status>}
                {a.status === "Pending" && <Status kind="pending">Pending</Status>}
                {a.status === "Submitted" && <Status kind="submitted">Submitted</Status>}
                {a.status === "Rejected" && <Status kind="rejected">Rejected</Status>}
                {a.status === "Draft" && <Status kind="draft">Draft</Status>}
              </td>
              <td onClick={e => e.stopPropagation()}>
                <div style={{ display: "flex", gap: 4 }}>
                  <button className="btn sm" style={{ padding: "2px 6px" }}><PIcon name="eye" size={11}/></button>
                  <button className="btn sm" style={{ padding: "2px 6px" }}><PIcon name="moreH" size={11}/></button>
                </div>
              </td>
            </tr>
          ))}
        </tbody>
      </table>
    </div>

    {/* Pagination */}
    <div style={{ display: "flex", justifyContent: "space-between", padding: "12px 0", fontSize: 12, color: "var(--ink-mute)" }}>
      <span>Showing 1–{filtered.length} of {APP_TABLE.length} applications · 7,289 total in database</span>
      <div style={{ display: "flex", gap: 4 }}>
        <button className="btn sm">‹ Previous</button>
        <button className="btn sm navy">1</button>
        <button className="btn sm">2</button>
        <button className="btn sm">3</button>
        <button className="btn sm">...</button>
        <button className="btn sm">607</button>
        <button className="btn sm">Next ›</button>
      </div>
    </div>

    <ApplicationDetailModal app={selected} onClose={() => setSelected(null)} setToast={setToast}/>
  </>;
}

function ApplicationDetailModal({ app, onClose, setToast }) {
  return (
    <PortalModal open={!!app} onClose={onClose} title={app ? `Application — ${app.no}` : ""} size={820}
      footer={app && <>
        <button className="btn danger"><PIcon name="x" size={12}/> Reject</button>
        <button className="btn"><PIcon name="mail" size={12}/> Request Resubmission</button>
        <button className="btn success" onClick={() => { setToast("Application verified"); onClose(); setTimeout(() => setToast(""), 1500); }}>
          <PIcon name="check" size={12}/> Verify & Approve
        </button>
      </>}>
      {app && <>
        <div style={{ display: "grid", gridTemplateColumns: "100px 1fr", gap: 16, marginBottom: 18 }}>
          <div style={{ width: 90, height: 110, border: "1px solid var(--border-strong)", background: "var(--bg-sunken)", display: "grid", placeItems: "center" }}>
            <div style={{ width: 60, height: 60, borderRadius: "50%", background: "var(--maroon)", color: "#fff", display: "grid", placeItems: "center", fontFamily: "var(--font-serif)", fontWeight: 700, fontSize: 22 }}>
              {app.name.split(" ").map(s => s[0]).slice(0, 2).join("")}
            </div>
          </div>
          <div>
            <h3 style={{ fontFamily: "var(--font-serif)", margin: 0, color: "var(--maroon)" }}>{app.name}</h3>
            <div style={{ fontFamily: "var(--font-mono)", fontSize: 12, color: "var(--ink-mute)", marginTop: 2 }}>{app.no}</div>
            <div style={{ display: "flex", gap: 8, marginTop: 8 }}>
              {app.status === "Verified" && <Status kind="verified">Verified</Status>}
              {app.status === "Pending" && <Status kind="pending">Pending</Status>}
              {app.status === "Submitted" && <Status kind="submitted">Submitted</Status>}
              <span className="status" style={{ background: "var(--bg-sunken)", color: "var(--ink-soft)" }}>{app.cat}</span>
              <span className="status" style={{ background: "var(--bg-sunken)", color: "var(--ink-soft)" }}>Round-2</span>
            </div>
          </div>
        </div>

        <div style={{ display: "grid", gridTemplateColumns: "1fr 1fr", gap: 14 }}>
          <div style={{ background: "var(--bg-sunken)", padding: 14, border: "1px solid var(--border)" }}>
            <div style={{ fontSize: 11, color: "var(--ink-mute)", textTransform: "uppercase", letterSpacing: "0.04em", fontWeight: 700, marginBottom: 8, paddingBottom: 6, borderBottom: "1px solid var(--border)" }}>Personal</div>
            <div className="kv-list" style={{ gridTemplateColumns: "1fr 1fr" }}>
              <div className="k">Email</div><div className="v">aarav.p@gmail.com</div>
              <div className="k">Mobile</div><div className="v">+91 982xx-19987</div>
              <div className="k">Aadhaar</div><div className="v" style={{ fontFamily: "var(--font-mono)" }}>5421 •••• 3214</div>
              <div className="k">DOB</div><div className="v">12-08-2008</div>
              <div className="k">Gender</div><div className="v">{app.gender === "M" ? "Male" : "Female"}</div>
              <div className="k">Domicile</div><div className="v">{app.state}</div>
            </div>
          </div>
          <div style={{ background: "var(--bg-sunken)", padding: 14, border: "1px solid var(--border)" }}>
            <div style={{ fontSize: 11, color: "var(--ink-mute)", textTransform: "uppercase", letterSpacing: "0.04em", fontWeight: 700, marginBottom: 8, paddingBottom: 6, borderBottom: "1px solid var(--border)" }}>Academic</div>
            <div className="kv-list" style={{ gridTemplateColumns: "1fr 1fr" }}>
              <div className="k">Board</div><div className="v">GSHSEB</div>
              <div className="k">Year</div><div className="v">2026</div>
              <div className="k">Stream</div><div className="v">PCM + CS</div>
              <div className="k">Class XII %</div><div className="v" style={{ fontFamily: "var(--font-mono)", fontWeight: 700 }}>{app.pct.toFixed(2)}%</div>
              <div className="k">Class X %</div><div className="v" style={{ fontFamily: "var(--font-mono)" }}>92.60%</div>
              <div className="k">Eligibility</div><div className="v" style={{ color: "var(--green-dark)", fontWeight: 600 }}>Eligible</div>
            </div>
          </div>
        </div>

        <div style={{ marginTop: 14, padding: 14, background: "var(--bg-sunken)", border: "1px solid var(--border)" }}>
          <div style={{ fontSize: 11, color: "var(--ink-mute)", textTransform: "uppercase", letterSpacing: "0.04em", fontWeight: 700, marginBottom: 10, paddingBottom: 6, borderBottom: "1px solid var(--border)" }}>Document Checklist</div>
          <div style={{ display: "grid", gridTemplateColumns: "1fr 1fr", gap: 8 }}>
            {["Class X Marksheet", "Class XII Marksheet", "Aadhaar Card", "Photograph", "Signature", "Category Cert.", "Income Cert.", "Migration Cert."].map((d, i) => {
              const ok = i < parseInt(app.docs);
              return (
                <div key={d} style={{ display: "flex", gap: 8, fontSize: 12 }}>
                  <span style={{ width: 16, height: 16, borderRadius: 2, background: ok ? "var(--green)" : "var(--bg)", color: ok ? "#fff" : "var(--ink-mute)", display: "grid", placeItems: "center", border: ok ? "none" : "1px solid var(--border-strong)" }}>
                    {ok ? <PIcon name="check" size={10} stroke={3}/> : <PIcon name="x" size={10}/>}
                  </span>
                  <span style={{ color: ok ? "var(--ink)" : "var(--ink-mute)" }}>{d}</span>
                </div>
              );
            })}
          </div>
        </div>
      </>}
    </PortalModal>
  );
}

// ===== VERIFICATION =====
function AdminVerificationPage({ setToast }) {
  const [filter, setFilter] = apUseState("Pending");
  return <>
    <ACrumbs items={[{ label: "Dashboard" }, { label: "Document Verification" }]}/>
    <div className="portal-h1-row">
      <div>
        <h1 className="portal-h1">Document Verification Queue</h1>
        <p className="portal-h1-sub">Review and verify uploaded documents · DigiLocker-issued documents are auto-verified</p>
      </div>
      <div style={{ display: "flex", gap: 8 }}>
        <button className="btn"><PIcon name="download" size={12}/> Export Queue</button>
        <button className="btn navy">Bulk Approve DigiLocker Docs</button>
      </div>
    </div>

    <div className="stat-grid">
      <StatCard label="Pending Review" value="23" sub="this session" color="saffron"/>
      <StatCard label="Approved Today" value="89" sub="auto: 64 · manual: 25" color="green"/>
      <StatCard label="Rejected Today" value="4" color="maroon"/>
      <StatCard label="Avg. Review Time" value="6 min" sub="↓ 2 min vs Round-1" color="navy" subKind="down"/>
    </div>

    <div className="panel">
      <div className="panel-head">
        <h3>Documents Queue</h3>
        <div style={{ display: "flex", gap: 4 }}>
          {["Pending", "Approved", "Rejected", "All"].map(s => (
            <button key={s} className={`btn sm ${filter === s ? "navy" : ""}`} onClick={() => setFilter(s)}>{s}</button>
          ))}
        </div>
      </div>
      <div className="data-table-wrap" style={{ border: "none" }}>
        <table className="data-table">
          <thead>
            <tr>
              <th style={{ width: 50 }}>Sl.</th>
              <th>Document Type</th>
              <th>Application No.</th>
              <th>Applicant</th>
              <th>Programme</th>
              <th style={{ width: 90 }}>Source</th>
              <th style={{ width: 90 }}>Size</th>
              <th style={{ width: 100 }}>Uploaded</th>
              <th style={{ width: 100 }}>Status</th>
              <th style={{ width: 180 }}>Actions</th>
            </tr>
          </thead>
          <tbody>
            {[
              { d: "10th Marksheet", a: "Aarav Kumar Patel", no: "SVNC/UG/2026/039812", p: "UGCS01", src: "DigiLocker", sz: "1.2 MB", up: "12-05-2026" },
              { d: "Category Certificate (OBC-NCL)", a: "Aarav Kumar Patel", no: "SVNC/UG/2026/039812", p: "UGCS01", src: "Manual", sz: "1.1 MB", up: "13-05-2026" },
              { d: "Income Certificate", a: "Rohan Mehta", no: "SVNC/UG/2026/039810", p: "UGCM03", src: "Manual", sz: "780 KB", up: "10-05-2026" },
              { d: "Migration Certificate", a: "Sanvi Reddy", no: "SVNC/UG/2026/039809", p: "UGBT04", src: "Manual", sz: "920 KB", up: "11-05-2026" },
              { d: "Caste Certificate (SC)", a: "Ishita Banerjee", no: "SVNC/UG/2026/039803", p: "UGEC02", src: "Manual", sz: "1.0 MB", up: "12-05-2026" },
              { d: "PwBD Certificate", a: "Tara Nair", no: "SVNC/UG/2026/039801", p: "UGLS05", src: "Manual", sz: "1.4 MB", up: "13-05-2026" },
            ].map((r, i) => (
              <tr key={i}>
                <td style={{ textAlign: "center" }}>{i + 1}</td>
                <td style={{ fontWeight: 500 }}>{r.d}</td>
                <td style={{ fontFamily: "var(--font-mono)", color: "var(--navy)", fontSize: 11.5 }}>{r.no}</td>
                <td>{r.a}</td>
                <td style={{ fontFamily: "var(--font-mono)" }}>{r.p}</td>
                <td>{r.src === "DigiLocker" ? <span className="status" style={{ background: "rgba(0, 51, 102, 0.1)", color: "var(--navy)" }}>DigiLocker</span> : <span className="status draft">Manual</span>}</td>
                <td style={{ fontSize: 11.5 }}>{r.sz}</td>
                <td style={{ fontFamily: "var(--font-mono)", fontSize: 11.5 }}>{r.up}</td>
                <td><Status kind={r.src === "DigiLocker" ? "verified" : "pending"}>{r.src === "DigiLocker" ? "Auto-verified" : "Pending"}</Status></td>
                <td>
                  <div style={{ display: "flex", gap: 4 }}>
                    <button className="btn sm" title="View"><PIcon name="eye" size={11}/></button>
                    <button className="btn success sm" onClick={() => { setToast("Approved"); setTimeout(() => setToast(""), 1500); }}><PIcon name="check" size={11}/></button>
                    <button className="btn danger sm"><PIcon name="x" size={11}/></button>
                  </div>
                </td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>
    </div>
  </>;
}

// ===== MERIT =====
function AdminMeritPage({ setToast }) {
  const [generating, setGenerating] = apUseState(false);
  return <>
    <ACrumbs items={[{ label: "Dashboard" }, { label: "Merit Lists" }]}/>
    <div className="portal-h1-row">
      <div>
        <h1 className="portal-h1">Merit List Generation</h1>
        <p className="portal-h1-sub">Round-2 · Provisional · Generated as per Reservation Matrix & Tie-Breaker Rules</p>
      </div>
      <div style={{ display: "flex", gap: 8 }}>
        <button className="btn"><PIcon name="download" size={12}/> Download as PDF</button>
        <button className="btn"><PIcon name="print" size={12}/> Print</button>
        <button className="btn accent" onClick={() => { setGenerating(true); setTimeout(() => { setGenerating(false); setToast("Merit list regenerated"); setTimeout(() => setToast(""), 1500); }, 1500); }}>
          <PIcon name="refresh" size={12}/> {generating ? "Generating…" : "Regenerate"}
        </button>
      </div>
    </div>

    {/* Generation rules */}
    <div className="panel">
      <div className="panel-head"><h3>Merit Generation Rules</h3><span className="meta">Updated 27-May-2026 by Dr. Priya Menon</span></div>
      <div className="panel-body">
        <div style={{ display: "grid", gridTemplateColumns: "1fr 1fr", gap: 14 }}>
          {[
            ["Primary Merit Score", "Class XII Best 5 Aggregate %"],
            ["Entrance Exam Weight", "CUET-UG: 30% · Board: 70% (where applicable)"],
            ["Reservation Matrix", "UR 40% · OBC 27% · SC 15% · ST 7.5% · EWS 10%"],
            ["Marks Relaxation", "SC/ST: 5% · OBC-NCL: 3% · PwBD: 5%"],
            ["Tie-Breaker 1", "Higher marks in Mathematics"],
            ["Tie-Breaker 2", "Older candidate (earlier DOB)"],
            ["Tie-Breaker 3", "Earlier application submission"],
            ["Horizontal Reservation", "PwBD: 5% (across all categories)"],
          ].map(([k, v], i) => (
            <div key={i} style={{ background: "var(--bg-sunken)", padding: "8px 10px", border: "1px solid var(--border-soft)" }}>
              <div style={{ fontSize: 10.5, color: "var(--ink-mute)", textTransform: "uppercase", letterSpacing: "0.04em", fontWeight: 700 }}>{k}</div>
              <div style={{ fontSize: 12.5, fontWeight: 500, marginTop: 2 }}>{v}</div>
            </div>
          ))}
        </div>
      </div>
    </div>

    {/* Programme tabs */}
    <div style={{ display: "flex", gap: 0, borderBottom: "2px solid var(--maroon)", marginBottom: 0 }}>
      {["UGCS01 — B.Sc. CS", "UGEC02 — B.A. Eco-DS", "UGCM03 — B.Com.", "UGBT04 — B.Sc. Biotech"].map((p, i) => (
        <button key={p} className="btn" style={{
          borderRadius: i === 0 ? "3px 3px 0 0" : "3px 3px 0 0",
          background: i === 0 ? "var(--maroon)" : "var(--bg-sunken)",
          color: i === 0 ? "#fff" : "var(--ink-soft)",
          borderColor: i === 0 ? "var(--maroon)" : "var(--border)",
          borderBottom: "none", fontWeight: 600,
        }}>{p}</button>
      ))}
    </div>

    <div className="panel" style={{ marginTop: 0, borderRadius: "0 3px 3px 3px", borderTop: "none" }}>
      <div className="panel-head" style={{ borderRadius: 0 }}>
        <h3>Provisional Merit List · B.Sc. (Hons.) Computer Science · 120 Seats</h3>
        <span className="meta">1,842 applicants · 106 allocated · 284 on waitlist</span>
      </div>
      <div className="data-table-wrap" style={{ border: "none" }}>
        <table className="data-table">
          <thead>
            <tr>
              <th style={{ width: 60 }}>Rank</th>
              <th>Application Number</th>
              <th>Candidate Name</th>
              <th>Category</th>
              <th style={{ width: 90 }}>Marks %</th>
              <th>Tie-Breaker</th>
              <th style={{ width: 100 }}>Programme</th>
              <th style={{ width: 110 }}>Allotted Status</th>
              <th style={{ width: 80 }}>Action</th>
            </tr>
          </thead>
          <tbody>
            {MERIT_LIST.map(m => (
              <tr key={m.rank} style={{ background: m.rank <= 3 ? "rgba(232, 119, 34, 0.06)" : undefined }}>
                <td>
                  <div style={{
                    width: 30, height: 30,
                    background: m.rank === 1 ? "#b89249" : m.rank === 2 ? "#a8a8a8" : m.rank === 3 ? "#cd7f32" : "var(--bg-sunken)",
                    color: m.rank <= 3 ? "#fff" : "var(--ink-soft)",
                    borderRadius: 3, display: "grid", placeItems: "center",
                    fontFamily: "var(--font-serif)", fontWeight: 700, fontSize: 13,
                  }}>{m.rank}</div>
                </td>
                <td style={{ fontFamily: "var(--font-mono)", color: "var(--navy)", fontSize: 11.5, fontWeight: 600 }}>{m.no}</td>
                <td style={{ fontWeight: 500 }}>{m.name}</td>
                <td>{m.cat}</td>
                <td style={{ textAlign: "center", fontFamily: "var(--font-mono)", fontWeight: 700, color: m.marks >= 90 ? "var(--green-dark)" : "var(--ink)" }}>{m.marks.toFixed(2)}</td>
                <td style={{ fontSize: 11.5, color: "var(--ink-mute)" }}>{m.tiebreak}</td>
                <td style={{ fontFamily: "var(--font-mono)" }}>{m.prog}</td>
                <td>
                  {m.status === "Allocated" && <Status kind="allocated">Allocated</Status>}
                  {m.status === "Waitlist" && <Status kind="waitlist">Waitlist</Status>}
                  {m.status === "Withdrawn" && <Status kind="draft">Withdrawn</Status>}
                </td>
                <td><button className="btn sm">View</button></td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>
      <div className="panel-foot">
        <div style={{ fontSize: 11.5, color: "var(--ink-mute)" }}>
          Provisional merit list. Final list will be published after document verification & seat acceptance.
        </div>
        <div style={{ display: "flex", gap: 8 }}>
          <button className="btn"><PIcon name="mail" size={12}/> Notify Candidates</button>
          <button className="btn primary"><PIcon name="check" size={12}/> Publish List</button>
        </div>
      </div>
    </div>
  </>;
}

// ===== SEATS =====
function AdminSeatsPage() {
  return <>
    <ACrumbs items={[{ label: "Dashboard" }, { label: "Seat Allocation" }]}/>
    <div className="portal-h1-row">
      <div>
        <h1 className="portal-h1">Seat Allocation Matrix</h1>
        <p className="portal-h1-sub">Programme-wise and category-wise seat occupancy · As on 27-May-2026 14:32 IST</p>
      </div>
      <div style={{ display: "flex", gap: 8 }}>
        <button className="btn"><PIcon name="filter" size={12}/> Round-2</button>
        <button className="btn accent">Run Allocation Engine</button>
      </div>
    </div>

    <div className="stat-grid">
      <StatCard label="Total Sanctioned Seats" value="650" color="navy"/>
      <StatCard label="Allocated" value="487" sub="74.9%" color="green"/>
      <StatCard label="Waitlisted" value="284" color="saffron"/>
      <StatCard label="Vacant" value="163" sub="Spot Round 20–25 Jun" color="maroon"/>
    </div>

    <div className="panel">
      <div className="panel-head"><h3>Category-wise Seat Allocation by Programme</h3></div>
      <div className="data-table-wrap" style={{ border: "none" }}>
        <table className="data-table">
          <thead>
            <tr>
              <th style={{ width: 60 }}>Code</th>
              <th>Programme</th>
              <th colSpan={2} style={{ textAlign: "center", borderRight: "2px solid var(--navy-light)" }}>UR (40%)</th>
              <th colSpan={2} style={{ textAlign: "center", borderRight: "2px solid var(--navy-light)" }}>OBC (27%)</th>
              <th colSpan={2} style={{ textAlign: "center", borderRight: "2px solid var(--navy-light)" }}>SC (15%)</th>
              <th colSpan={2} style={{ textAlign: "center", borderRight: "2px solid var(--navy-light)" }}>ST (7.5%)</th>
              <th colSpan={2} style={{ textAlign: "center", borderRight: "2px solid var(--navy-light)" }}>EWS (10%)</th>
              <th style={{ width: 80 }}>Total</th>
              <th style={{ width: 100 }}>Fill %</th>
            </tr>
            <tr style={{ background: "var(--navy-dark)" }}>
              <th></th>
              <th></th>
              <th>Seats</th><th style={{ borderRight: "2px solid var(--navy-light)" }}>Filled</th>
              <th>Seats</th><th style={{ borderRight: "2px solid var(--navy-light)" }}>Filled</th>
              <th>Seats</th><th style={{ borderRight: "2px solid var(--navy-light)" }}>Filled</th>
              <th>Seats</th><th style={{ borderRight: "2px solid var(--navy-light)" }}>Filled</th>
              <th>Seats</th><th style={{ borderRight: "2px solid var(--navy-light)" }}>Filled</th>
              <th></th><th></th>
            </tr>
          </thead>
          <tbody>
            {PROGRAMMES.map((p, idx) => {
              const cats = [
                { pct: 40 }, { pct: 27 }, { pct: 15 }, { pct: 7.5 }, { pct: 10 }
              ];
              const computed = cats.map(c => {
                const seats = Math.round(p.intake * c.pct / 100);
                const filled = Math.round(seats * (0.6 + (idx % 4) * 0.08));
                return { seats, filled: Math.min(seats, filled) };
              });
              const totalFilled = computed.reduce((s, x) => s + x.filled, 0);
              const fillPct = Math.round(totalFilled / p.intake * 100);
              return (
                <tr key={p.code}>
                  <td style={{ fontFamily: "var(--font-mono)", fontWeight: 600, color: "var(--navy)" }}>{p.code}</td>
                  <td style={{ fontWeight: 500 }}>{p.name}</td>
                  {computed.map((c, j) => <React.Fragment key={j}>
                    <td style={{ textAlign: "center", fontFamily: "var(--font-mono)" }}>{c.seats}</td>
                    <td style={{ textAlign: "center", fontFamily: "var(--font-mono)", fontWeight: 600, color: c.filled === c.seats ? "var(--green-dark)" : "var(--saffron-dark)", borderRight: "2px solid var(--border)" }}>{c.filled}</td>
                  </React.Fragment>)}
                  <td style={{ textAlign: "center", fontFamily: "var(--font-mono)", fontWeight: 700 }}>{totalFilled}/{p.intake}</td>
                  <td>
                    <div style={{ display: "flex", alignItems: "center", gap: 6 }}>
                      <div className="progress-track" style={{ flex: 1, height: 6 }}><span style={{ width: fillPct + "%" }}></span></div>
                      <span style={{ fontFamily: "var(--font-mono)", fontSize: 11, fontWeight: 600 }}>{fillPct}%</span>
                    </div>
                  </td>
                </tr>
              );
            })}
          </tbody>
        </table>
      </div>
    </div>
  </>;
}

// ===== FEE =====
function AdminFeePage() {
  return <>
    <ACrumbs items={[{ label: "Dashboard" }, { label: "Fee Collection" }]}/>
    <div className="portal-h1-row">
      <div>
        <h1 className="portal-h1">Fee Collection Register</h1>
        <p className="portal-h1-sub">Online & offline fee receipts · Reconciliation as on 27-May-2026 14:32 IST</p>
      </div>
      <div style={{ display: "flex", gap: 8 }}>
        <button className="btn"><PIcon name="download" size={12}/> Export Register</button>
        <button className="btn"><PIcon name="print" size={12}/> Print</button>
      </div>
    </div>

    <div className="stat-grid">
      <StatCard label="Total Collected (FY)" value="₹ 2.84 Cr" sub="↑ 18% vs target" color="green" subKind="up"/>
      <StatCard label="Application Fee" value="₹ 1.09 Cr" sub="7,289 × ₹1,500 avg" color="navy"/>
      <StatCard label="Admission Fee" value="₹ 1.46 Cr" sub="487 admissions" color="maroon"/>
      <StatCard label="Refunds Issued" value="₹ 2.84 L" sub="32 transactions" color="saffron"/>
    </div>

    <div className="panel">
      <div className="panel-head">
        <h3>Fee Receipts Register · Last 7 Days</h3>
        <span className="meta">412 transactions · ₹ 18,42,540</span>
      </div>
      <div className="data-table-wrap" style={{ border: "none" }}>
        <table className="data-table">
          <thead>
            <tr>
              <th style={{ width: 50 }}>Sl.</th>
              <th>Challan / Txn ID</th>
              <th>Application No.</th>
              <th>Candidate</th>
              <th>Fee Head</th>
              <th style={{ width: 90 }}>Mode</th>
              <th style={{ width: 100 }}>Date</th>
              <th style={{ width: 110 }}>Amount (₹)</th>
              <th style={{ width: 90 }}>Status</th>
              <th style={{ width: 90 }}>Action</th>
            </tr>
          </thead>
          <tbody>
            {[
              { c: "CHN-2026-22847", no: "SVNC/UG/2026/039812", n: "Aarav Kumar Patel", h: "Admission Confirmation Fee", m: "UPI", d: "27-05-2026", amt: 28500, s: "Paid" },
              { c: "CHN-2026-22846", no: "SVNC/UG/2026/039811", n: "Diya Krishnan", h: "Admission Confirmation Fee", m: "Net Banking", d: "27-05-2026", amt: 32000, s: "Paid" },
              { c: "CHN-2026-22845", no: "SVNC/UG/2026/039810", n: "Rohan Mehta", h: "Application Fee", m: "Credit Card", d: "27-05-2026", amt: 1451, s: "Paid" },
              { c: "CHN-2026-22844", no: "SVNC/UG/2026/039808", n: "Vivaan Iyer", h: "Tuition Fee Sem-1", m: "UPI", d: "26-05-2026", amt: 17500, s: "Paid" },
              { c: "CHN-2026-22843", no: "SVNC/UG/2026/039804", n: "Aryan Kapoor", h: "Caution Money", m: "Debit Card", d: "26-05-2026", amt: 5000, s: "Paid" },
              { c: "CHN-2026-22842", no: "SVNC/UG/2026/039802", n: "Aditya Rao", h: "Tuition Fee Sem-1", m: "Net Banking", d: "26-05-2026", amt: 19000, s: "Paid" },
              { c: "TXN-FAIL-921", no: "SVNC/UG/2026/039807", n: "Ananya Sharma", h: "Application Fee", m: "UPI", d: "26-05-2026", amt: 1451, s: "Failed" },
              { c: "RFD-2026-0014", no: "SVNC/UG/2026/039805", n: "Meera Joshi", h: "Application Fee", m: "Refund (UPI)", d: "25-05-2026", amt: -1451, s: "Refunded" },
              { c: "CHN-2026-22835", no: "SVNC/UG/2026/039803", n: "Ishita Banerjee", h: "Application Fee", m: "UPI", d: "25-05-2026", amt: 1200, s: "Paid" },
              { c: "CHN-2026-22834", no: "SVNC/UG/2026/039806", n: "Kabir Singh", h: "Application Fee", m: "Net Banking", d: "25-05-2026", amt: 1200, s: "Paid" },
            ].map((r, i) => (
              <tr key={i}>
                <td style={{ textAlign: "center" }}>{i + 1}</td>
                <td style={{ fontFamily: "var(--font-mono)", color: "var(--navy)" }}>{r.c}</td>
                <td style={{ fontFamily: "var(--font-mono)", fontSize: 11 }}>{r.no}</td>
                <td>{r.n}</td>
                <td>{r.h}</td>
                <td>{r.m}</td>
                <td style={{ fontFamily: "var(--font-mono)" }}>{r.d}</td>
                <td style={{ textAlign: "right", fontFamily: "var(--font-mono)", fontWeight: 600, color: r.amt < 0 ? "var(--red)" : "var(--ink)" }}>{r.amt < 0 ? "−" : ""}{Math.abs(r.amt).toLocaleString("en-IN")}.00</td>
                <td>
                  {r.s === "Paid" && <Status kind="paid">Paid</Status>}
                  {r.s === "Failed" && <Status kind="rejected">Failed</Status>}
                  {r.s === "Refunded" && <Status kind="draft">Refunded</Status>}
                </td>
                <td><button className="btn sm"><PIcon name="print" size={11}/> Receipt</button></td>
              </tr>
            ))}
            <tr className="total">
              <td colSpan={7} style={{ textAlign: "right" }}>Total of {10} transactions shown</td>
              <td style={{ textAlign: "right", fontFamily: "var(--font-mono)", fontSize: 13 }}>1,03,851.00</td>
              <td colSpan={2}></td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </>;
}

// ===== PROGRAMMES =====
function AdminProgrammesPage() {
  return <>
    <ACrumbs items={[{ label: "Dashboard" }, { label: "Programmes" }]}/>
    <div className="portal-h1-row">
      <div>
        <h1 className="portal-h1">NEP 2020 Programme Configuration</h1>
        <p className="portal-h1-sub">Configure credit structures, multi-disciplinary tracks and entry/exit options</p>
      </div>
      <div style={{ display: "flex", gap: 8 }}>
        <button className="btn accent"><PIcon name="plus" size={12}/> Add New Programme</button>
      </div>
    </div>

    <div className="panel">
      <div className="panel-head"><h3>All Programmes · Session 2026-27</h3></div>
      <div className="data-table-wrap" style={{ border: "none" }}>
        <table className="data-table">
          <thead>
            <tr>
              <th style={{ width: 80 }}>Code</th>
              <th>Programme Name</th>
              <th>Department</th>
              <th style={{ width: 80 }}>Duration</th>
              <th style={{ width: 80 }}>Credits</th>
              <th style={{ width: 70 }}>Seats</th>
              <th style={{ width: 90 }}>Type</th>
              <th style={{ width: 110 }}>NEP Tags</th>
              <th style={{ width: 80 }}>Action</th>
            </tr>
          </thead>
          <tbody>
            {PROGRAMMES.map(p => (
              <tr key={p.code}>
                <td style={{ fontFamily: "var(--font-mono)", fontWeight: 600, color: "var(--navy)" }}>{p.code}</td>
                <td style={{ fontWeight: 600 }}>{p.name}</td>
                <td>{p.dept}</td>
                <td style={{ textAlign: "center" }}>{p.duration}</td>
                <td style={{ textAlign: "center", fontFamily: "var(--font-mono)" }}>{p.credits}</td>
                <td style={{ textAlign: "center", fontFamily: "var(--font-mono)" }}>{p.intake}</td>
                <td><span className="status" style={{ background: "var(--bg-sunken)", color: "var(--ink-soft)" }}>{p.duration === "4 Years" ? "Hons." : "Regular"}</span></td>
                <td><span style={{ fontSize: 10.5, color: "var(--green-dark)", fontWeight: 600 }}>NEP 2020 ✓</span><br/><span style={{ fontSize: 10, color: "var(--ink-mute)" }}>ABC · CBCS · MEME</span></td>
                <td>
                  <div style={{ display: "flex", gap: 4 }}>
                    <button className="btn sm"><PIcon name="edit" size={11}/></button>
                    <button className="btn sm"><PIcon name="eye" size={11}/></button>
                  </div>
                </td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>
    </div>

    {/* NEP Structure */}
    <div className="panel">
      <div className="panel-head"><h3>NEP 2020 Credit Structure · UGCS01 (Sample)</h3><span className="meta">Credit-based · Multi-disciplinary · ABC-ready</span></div>
      <div className="data-table-wrap" style={{ border: "none" }}>
        <table className="data-table">
          <thead>
            <tr>
              <th>Year</th>
              <th>Semester</th>
              <th style={{ width: 110 }}>Major (Core)</th>
              <th style={{ width: 90 }}>Minor</th>
              <th style={{ width: 120 }}>Multi-Disc.</th>
              <th style={{ width: 100 }}>Skill / Voc.</th>
              <th style={{ width: 100 }}>Ability / VAC</th>
              <th style={{ width: 80 }}>Total</th>
              <th>Award (on exit)</th>
            </tr>
          </thead>
          <tbody>
            {[
              { y: "Year 1", s: 1, M: 12, m: 4, MD: 4, S: 2, A: 2 },
              { y: "Year 1", s: 2, M: 12, m: 4, MD: 4, S: 2, A: 2, exit: "Certificate (40 credits)" },
              { y: "Year 2", s: 3, M: 14, m: 4, MD: 4, S: 2, A: 0 },
              { y: "Year 2", s: 4, M: 14, m: 4, MD: 4, S: 2, A: 0, exit: "Diploma (80 credits)" },
              { y: "Year 3", s: 5, M: 16, m: 4, MD: 0, S: 4, A: 0 },
              { y: "Year 3", s: 6, M: 16, m: 4, MD: 0, S: 4, A: 0, exit: "Bachelor's (120 credits)" },
              { y: "Year 4", s: 7, M: 16, m: 0, MD: 0, S: 4, A: 0 },
              { y: "Year 4", s: 8, M: 16, m: 0, MD: 0, S: 4, A: 0, exit: "Bachelor's (Hons.) (160 cr)" },
            ].map((r, i) => (
              <tr key={i} style={{ background: r.exit ? "rgba(232, 119, 34, 0.05)" : undefined }}>
                <td style={{ fontWeight: 500 }}>{r.y}</td>
                <td style={{ fontWeight: 600 }}>Sem-{r.s}</td>
                <td style={{ textAlign: "center", fontFamily: "var(--font-mono)" }}>{r.M}</td>
                <td style={{ textAlign: "center", fontFamily: "var(--font-mono)" }}>{r.m}</td>
                <td style={{ textAlign: "center", fontFamily: "var(--font-mono)" }}>{r.MD}</td>
                <td style={{ textAlign: "center", fontFamily: "var(--font-mono)" }}>{r.S}</td>
                <td style={{ textAlign: "center", fontFamily: "var(--font-mono)" }}>{r.A || "—"}</td>
                <td style={{ textAlign: "center", fontFamily: "var(--font-mono)", fontWeight: 700 }}>{r.M + r.m + r.MD + r.S + r.A}</td>
                <td style={{ fontSize: 11.5, color: r.exit ? "var(--saffron-dark)" : "var(--ink-mute)", fontWeight: r.exit ? 600 : 400 }}>{r.exit || "—"}</td>
              </tr>
            ))}
            <tr className="total">
              <td colSpan={2} style={{ textAlign: "right" }}>Programme Total</td>
              <td style={{ textAlign: "center", fontFamily: "var(--font-mono)" }}>116</td>
              <td style={{ textAlign: "center", fontFamily: "var(--font-mono)" }}>24</td>
              <td style={{ textAlign: "center", fontFamily: "var(--font-mono)" }}>16</td>
              <td style={{ textAlign: "center", fontFamily: "var(--font-mono)" }}>24</td>
              <td style={{ textAlign: "center", fontFamily: "var(--font-mono)" }}>4</td>
              <td style={{ textAlign: "center", fontFamily: "var(--font-mono)" }}>184</td>
              <td></td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </>;
}

// ===== REPORTS =====
function AdminReportsPage() {
  return <>
    <ACrumbs items={[{ label: "Dashboard" }, { label: "Reports & Analytics" }]}/>
    <div className="portal-h1-row">
      <div>
        <h1 className="portal-h1">Reports & Analytics</h1>
        <p className="portal-h1-sub">Pre-configured reports for AISHE submission and internal MIS</p>
      </div>
      <div style={{ display: "flex", gap: 8 }}>
        <button className="btn"><PIcon name="cal" size={12}/> 27-Apr to 27-May</button>
        <button className="btn navy"><PIcon name="download" size={12}/> Generate AISHE Report</button>
      </div>
    </div>

    <div className="stat-grid">
      <StatCard label="Total Applications" value="7,289" sub="↑ 12.4% YoY" color="navy" subKind="up"/>
      <StatCard label="Conversion Rate" value="42.1%" sub="↑ 3.2 pts" color="green" subKind="up"/>
      <StatCard label="Revenue (YTD)" value="₹ 2.84 Cr" sub="↑ 18%" color="maroon" subKind="up"/>
      <StatCard label="Cost / Acquisition" value="₹ 186" sub="↓ 12%" color="saffron" subKind="down"/>
    </div>

    <div className="panel">
      <div className="panel-head"><h3>Admission Funnel · Session 2026-27</h3></div>
      <div className="panel-body" style={{ padding: 0 }}>
        <table className="data-table">
          <thead>
            <tr>
              <th style={{ width: 60 }}>Sl.</th>
              <th>Funnel Stage</th>
              <th style={{ width: 120 }}>Count</th>
              <th style={{ width: 110 }}>% of Total</th>
              <th style={{ width: 110 }}>vs Round-1</th>
              <th>Distribution</th>
            </tr>
          </thead>
          <tbody>
            {[
              { s: "Total Enquiries (CRM)", v: 18420, pct: 100, yoy: "+22%", color: "var(--violet)" },
              { s: "Registered for Application", v: 12104, pct: 65.7, yoy: "+18%", color: "var(--navy)" },
              { s: "Submitted Application", v: 7289, pct: 39.6, yoy: "+12%", color: "var(--saffron)" },
              { s: "Application Fee Paid", v: 6842, pct: 37.2, yoy: "+14%", color: "var(--saffron-dark)" },
              { s: "Documents Verified", v: 5142, pct: 27.9, yoy: "+9%", color: "var(--green)" },
              { s: "Allocated Seats", v: 487, pct: 2.6, yoy: "+6%", color: "var(--maroon)" },
              { s: "Final Admissions Confirmed", v: 412, pct: 2.2, yoy: "+8%", color: "var(--maroon-dark)" },
            ].map((r, i) => (
              <tr key={i}>
                <td style={{ textAlign: "center" }}>{i + 1}</td>
                <td style={{ fontWeight: 500 }}>{r.s}</td>
                <td style={{ textAlign: "right", fontFamily: "var(--font-mono)", fontWeight: 600 }}>{r.v.toLocaleString("en-IN")}</td>
                <td style={{ textAlign: "right", fontFamily: "var(--font-mono)" }}>{r.pct.toFixed(1)}%</td>
                <td style={{ textAlign: "center", color: "var(--green-dark)", fontWeight: 600 }}>{r.yoy}</td>
                <td>
                  <div style={{ display: "flex", alignItems: "center", gap: 6 }}>
                    <div style={{ flex: 1, height: 14, background: "var(--bg-sunken)", borderRadius: 1, overflow: "hidden", border: "1px solid var(--border-soft)" }}>
                      <div style={{ height: "100%", width: r.pct + "%", background: r.color, transition: "width 400ms" }}/>
                    </div>
                  </div>
                </td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>
    </div>

    <div style={{ display: "grid", gridTemplateColumns: "1fr 1fr", gap: 16 }}>
      <div className="panel">
        <div className="panel-head"><h3>Top Source States (Domicile)</h3></div>
        <div className="panel-body" style={{ padding: 0 }}>
          <table className="data-table">
            <thead><tr><th>State</th><th style={{ width: 100 }}>Applications</th><th>%</th></tr></thead>
            <tbody>
              {[
                { s: "Gujarat", v: 3204 }, { s: "Maharashtra", v: 1840 }, { s: "Rajasthan", v: 840 },
                { s: "Delhi", v: 520 }, { s: "Karnataka", v: 412 }, { s: "Tamil Nadu", v: 318 },
                { s: "Kerala", v: 286 }, { s: "Others", v: 473 },
              ].map((g, i) => {
                const pct = (g.v / 7289 * 100).toFixed(1);
                return (
                  <tr key={i}>
                    <td style={{ fontWeight: 500 }}>{g.s}</td>
                    <td style={{ textAlign: "right", fontFamily: "var(--font-mono)" }}>{g.v.toLocaleString("en-IN")}</td>
                    <td>
                      <div style={{ display: "flex", alignItems: "center", gap: 6 }}>
                        <div style={{ flex: 1, height: 6, background: "var(--bg-sunken)" }}>
                          <div style={{ height: "100%", width: pct + "%", background: "var(--navy)" }}/>
                        </div>
                        <span style={{ fontFamily: "var(--font-mono)", fontSize: 11, width: 36 }}>{pct}%</span>
                      </div>
                    </td>
                  </tr>
                );
              })}
            </tbody>
          </table>
        </div>
      </div>

      <div className="panel">
        <div className="panel-head"><h3>Application Sources</h3></div>
        <div className="panel-body" style={{ padding: 0 }}>
          <table className="data-table">
            <thead><tr><th>Source</th><th style={{ width: 100 }}>Leads</th><th style={{ width: 90 }}>Converted</th><th>Conv. %</th></tr></thead>
            <tbody>
              {[
                { s: "College Website", l: 4204, c: 1842 },
                { s: "Education Fairs", l: 2240, c: 1104 },
                { s: "Newspaper Ads", l: 1804, c: 412 },
                { s: "School Outreach", l: 1240, c: 780 },
                { s: "Counselor Referral", l: 980, c: 612 },
                { s: "Social Media", l: 840, c: 240 },
                { s: "Walk-ins", l: 312, c: 220 },
                { s: "Others", l: 540, c: 86 },
              ].map((r, i) => {
                const conv = (r.c / r.l * 100).toFixed(1);
                return (
                  <tr key={i}>
                    <td style={{ fontWeight: 500 }}>{r.s}</td>
                    <td style={{ textAlign: "right", fontFamily: "var(--font-mono)" }}>{r.l.toLocaleString("en-IN")}</td>
                    <td style={{ textAlign: "right", fontFamily: "var(--font-mono)", color: "var(--green-dark)" }}>{r.c.toLocaleString("en-IN")}</td>
                    <td style={{ fontFamily: "var(--font-mono)", fontWeight: 600 }}>{conv}%</td>
                  </tr>
                );
              })}
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </>;
}

// ===== NOTIFICATIONS =====
function AdminNotificationsPage({ setToast }) {
  return <>
    <ACrumbs items={[{ label: "Dashboard" }, { label: "Send Notifications" }]}/>
    <div className="portal-h1-row">
      <div>
        <h1 className="portal-h1">Publish Notifications & Circulars</h1>
        <p className="portal-h1-sub">Send bulk SMS, Email and WhatsApp notifications to applicants & students</p>
      </div>
    </div>

    <div style={{ display: "grid", gridTemplateColumns: "1fr 360px", gap: 16 }}>
      <div className="panel">
        <div className="panel-head"><h3>Compose New Notification</h3></div>
        <div className="panel-body">
          <div className="fg-2">
            <div className="form-row"><label>Notification Type <span className="req">*</span></label><select><option>Admission Notice</option><option>Examination Notice</option><option>Recruitment Notice</option><option>General Circular</option><option>Reminder</option></select></div>
            <div className="form-row"><label>Priority</label><select><option>Normal</option><option>Important</option><option>Urgent</option></select></div>
          </div>
          <div className="form-row"><label>Title <span className="req">*</span></label><input placeholder="e.g. Round-2 Counselling Schedule Released"/></div>
          <div className="fg-2">
            <div className="form-row"><label>Send To <span className="req">*</span></label><select><option>All Applicants — Round 2</option><option>Verified Applicants only</option><option>Pending Document candidates</option><option>Allocated candidates</option><option>Waitlist candidates</option><option>Specific Programme</option></select></div>
            <div className="form-row"><label>Channels</label>
              <div style={{ display: "flex", gap: 6, marginTop: 4 }}>
                <label style={{ fontSize: 12, display: "flex", gap: 4, padding: "4px 8px", border: "1px solid var(--border)", borderRadius: 2 }}><input type="checkbox" defaultChecked/> SMS</label>
                <label style={{ fontSize: 12, display: "flex", gap: 4, padding: "4px 8px", border: "1px solid var(--border)", borderRadius: 2 }}><input type="checkbox" defaultChecked/> Email</label>
                <label style={{ fontSize: 12, display: "flex", gap: 4, padding: "4px 8px", border: "1px solid var(--border)", borderRadius: 2 }}><input type="checkbox" defaultChecked/> WhatsApp</label>
                <label style={{ fontSize: 12, display: "flex", gap: 4, padding: "4px 8px", border: "1px solid var(--border)", borderRadius: 2 }}><input type="checkbox"/> Portal</label>
              </div>
            </div>
          </div>
          <div className="form-row"><label>Message Body <span className="req">*</span></label><textarea rows="6" defaultValue="Dear Applicant, the provisional merit list for Round-2 will be published on 05-Jun-2026 at 4:00 PM. Please log in to your portal to check your status. — Office of the Dean (Admissions), SVNC"/></div>
          <div className="form-row"><label>Attachment (optional)</label><input type="file"/></div>
          <div className="fg-2">
            <div className="form-row"><label>Schedule Date / Time</label><input defaultValue="Send Immediately"/></div>
            <div className="form-row"><label>Approval</label><input defaultValue="Dr. Priya Menon" readOnly style={{ background: "var(--bg-sunken)" }}/></div>
          </div>
        </div>
        <div className="panel-foot">
          <button className="btn">Save Draft</button>
          <div style={{ display: "flex", gap: 8 }}>
            <button className="btn">Preview</button>
            <button className="btn accent" onClick={() => { setToast("Notification queued for 7,289 recipients"); setTimeout(() => setToast(""), 2000); }}>
              <PIcon name="bell" size={12}/> Send to 7,289 Recipients
            </button>
          </div>
        </div>
      </div>

      <div>
        <div className="panel">
          <div className="panel-head"><h3>Delivery Estimate</h3></div>
          <div className="panel-body" style={{ fontSize: 12.5 }}>
            <div style={{ marginBottom: 8 }}><strong>Recipients:</strong> 7,289 applicants</div>
            <div style={{ marginBottom: 8 }}><strong>SMS:</strong> 7,289 × ₹0.18 = ₹ 1,312.02</div>
            <div style={{ marginBottom: 8 }}><strong>Email:</strong> 7,289 × ₹0.05 = ₹ 364.45</div>
            <div style={{ marginBottom: 8 }}><strong>WhatsApp:</strong> 7,289 × ₹0.42 = ₹ 3,061.38</div>
            <div style={{ borderTop: "1px solid var(--border)", paddingTop: 8, marginTop: 8, fontWeight: 700, color: "var(--maroon)" }}>Total Cost: ₹ 4,737.85</div>
          </div>
        </div>

        <div className="panel">
          <div className="panel-head"><h3>Recent Notifications</h3></div>
          <div className="panel-body" style={{ padding: 0, fontSize: 12 }}>
            {[
              { t: "Round-2 Counselling Schedule", to: "All Applicants", on: "27-May 11:42", sent: 7289 },
              { t: "Document Verification Reminder", to: "Pending Apps", on: "26-May 18:00", sent: 2147 },
              { t: "Provisional Merit Published", to: "All Applicants", on: "26-May 16:30", sent: 7289 },
              { t: "OBC Certificate Resubmission", to: "OBC Apps", on: "25-May 14:20", sent: 1968 },
            ].map((n, i) => (
              <div key={i} style={{ padding: 10, borderBottom: i < 3 ? "1px solid var(--border)" : "none" }}>
                <div style={{ fontWeight: 600, fontSize: 12 }}>{n.t}</div>
                <div style={{ color: "var(--ink-mute)", fontSize: 11, marginTop: 2 }}>
                  To: {n.to} · Sent: {n.sent.toLocaleString("en-IN")} · {n.on}
                </div>
              </div>
            ))}
          </div>
        </div>
      </div>
    </div>
  </>;
}

Object.assign(window, { AdminPortal });
