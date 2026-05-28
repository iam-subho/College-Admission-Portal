// Student portal — Samarth-style dashboard with sectioned application form

const { useState: spUseState } = React;

const STUDENT_SECTIONS = [
  { id: "dashboard", lbl: "Dashboard", icon: "home" },
  { id: "personal", lbl: "Personal Details", icon: "user", status: "done" },
  { id: "family", lbl: "Family Details", icon: "users", status: "done" },
  { id: "address", lbl: "Address & Contact", icon: "home", status: "done" },
  { id: "academic", lbl: "Academic Records", icon: "cap", status: "done" },
  { id: "other", lbl: "Other Details", icon: "list", status: "pending" },
  { id: "uploads", lbl: "Uploads", icon: "upload", status: "pending" },
  { id: "programme", lbl: "Programme Selection", icon: "book", status: "pending" },
  { id: "payment", lbl: "Fee Payment", icon: "money", status: "pending" },
  { id: "preview", lbl: "Preview & Submit", icon: "eye", status: "pending" },
];

function StudentPortal({ section, setSection, setToast }) {
  return (
    <div className="portal">
      <StudentSidebar section={section} setSection={setSection}/>
      <div className="portal-main">
        {section === "dashboard" && <StudentDashboardPage setSection={setSection} setToast={setToast}/>}
        {section === "personal" && <PersonalDetailsPage setSection={setSection} setToast={setToast}/>}
        {section === "family" && <FamilyDetailsPage setSection={setSection} setToast={setToast}/>}
        {section === "address" && <AddressPage setSection={setSection} setToast={setToast}/>}
        {section === "academic" && <AcademicPage setSection={setSection} setToast={setToast}/>}
        {section === "uploads" && <UploadsPage setSection={setSection} setToast={setToast}/>}
        {section === "programme" && <ProgrammeSelectionPage setSection={setSection} setToast={setToast}/>}
        {section === "payment" && <PaymentPage setSection={setSection} setToast={setToast}/>}
        {section === "preview" && <PreviewPage setSection={setSection} setToast={setToast}/>}
        {section === "other" && <OtherDetailsPage setSection={setSection} setToast={setToast}/>}
      </div>
    </div>
  );
}

function StudentSidebar({ section, setSection }) {
  const p = STUDENT_PROFILE;
  const done = STUDENT_SECTIONS.filter(s => s.status === "done").length;
  const pending = STUDENT_SECTIONS.filter(s => s.status === "pending").length;
  const totalForm = done + pending;
  const pct = Math.round((done / totalForm) * 100);
  return (
    <aside className="portal-side">
      <div className="portal-user">
        <div className="ph">
          <div className="av">{p.name.split(" ").map(s => s[0]).slice(0, 2).join("")}</div>
        </div>
        <div className="nm">{p.name}</div>
        <div className="id">{p.applicationNo}</div>
        <div className="meta">
          <div style={{ display: "flex", justifyContent: "space-between", marginBottom: 2 }}>
            <strong>ABC ID</strong>
            <span style={{ fontFamily: "var(--font-mono)", fontSize: 10 }}>{p.abcId}</span>
          </div>
          <div style={{ display: "flex", justifyContent: "space-between", marginBottom: 2 }}>
            <strong>DigiLocker</strong>
            <span style={{ color: "var(--green-dark)", fontWeight: 600, fontSize: 10.5 }}><PIcon name="check" size={10} stroke={2.5}/> Linked</span>
          </div>
          <div style={{ display: "flex", justifyContent: "space-between" }}>
            <strong>Category</strong>
            <span>{p.category}</span>
          </div>
        </div>
        <div style={{ marginTop: 8, paddingTop: 8, borderTop: "1px dashed var(--border)" }}>
          <div style={{ fontSize: 10.5, color: "var(--ink-mute)", marginBottom: 4, display: "flex", justifyContent: "space-between" }}>
            <span>Form Completion</span>
            <strong style={{ color: "var(--saffron-dark)" }}>{pct}%</strong>
          </div>
          <div className="progress-track"><span style={{ width: pct + "%" }}></span></div>
        </div>
      </div>

      <div className="portal-nav-section">Application Sections</div>
      {STUDENT_SECTIONS.map(s => (
        <button key={s.id} className={`portal-link ${section === s.id ? "active" : ""} ${s.status === "done" ? "done" : ""}`} onClick={() => setSection(s.id)}>
          {s.status === "done"
            ? <PIcon name="check" size={13} stroke={2.5} className="icon-tick"/>
            : <PIcon name={s.icon} size={13}/>}
          <span className="lbl">{s.lbl}</span>
          {s.status === "pending" && section !== s.id && <span className="ct">!</span>}
        </button>
      ))}

      <div className="portal-nav-section" style={{ marginTop: 12 }}>Quick Actions</div>
      <button className="portal-link"><PIcon name="print" size={13}/><span className="lbl">Print Application</span></button>
      <button className="portal-link"><PIcon name="download" size={13}/><span className="lbl">Download Receipt</span></button>
      <button className="portal-link"><PIcon name="phone" size={13}/><span className="lbl">Contact Helpdesk</span></button>
      <button className="portal-link"><PIcon name="lock" size={13}/><span className="lbl">Change Password</span></button>
    </aside>
  );
}

function Crumbs({ items }) {
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

// ===== DASHBOARD =====
function StudentDashboardPage({ setSection, setToast }) {
  const p = STUDENT_PROFILE;
  return <>
    <Crumbs items={[{ label: "Home", href: "#" }, { label: "Student Dashboard" }]}/>
    <div className="portal-h1-row">
      <div>
        <h1 className="portal-h1">Welcome, {p.name.split(" ")[0]}</h1>
        <p className="portal-h1-sub">Application No.: <strong style={{ fontFamily: "var(--font-mono)" }}>{p.applicationNo}</strong> · Session 2026-27 · Round-2</p>
      </div>
      <div style={{ display: "flex", gap: 8 }}>
        <button className="btn"><PIcon name="refresh" size={12}/> Refresh Status</button>
        <button className="btn navy"><PIcon name="print" size={12}/> Print Application</button>
      </div>
    </div>

    {/* Alert banner */}
    <div style={{ background: "var(--saffron-soft)", border: "1px solid var(--saffron)", borderLeft: "4px solid var(--saffron)", padding: "10px 14px", marginBottom: 16, fontSize: 12.5, display: "flex", alignItems: "center", gap: 10, borderRadius: 2 }}>
      <PIcon name="info" size={16} style={{ color: "var(--saffron-dark)" }}/>
      <div style={{ flex: 1 }}>
        <strong style={{ color: "var(--saffron-dark)" }}>Important:</strong> Your application is <strong>72% complete</strong>. Please complete the pending sections (Uploads, Programme Selection, Payment) and submit by <strong>31 May 2026</strong> to be considered for Round-2.
      </div>
      <button className="btn accent sm" onClick={() => setSection("uploads")}>Continue →</button>
    </div>

    <div className="stat-grid">
      <StatCard label="Form Completion" value="72%" sub="3 sections pending" color="saffron"/>
      <StatCard label="Documents Verified" value="5 / 9" sub="OBC certificate pending" color="navy"/>
      <StatCard label="Fee Paid" value="₹ 6,500" sub="₹ 27,050 due" color="maroon"/>
      <StatCard label="Application Status" value="In Progress" sub="Not Submitted" color="saffron"/>
    </div>

    <div style={{ display: "grid", gridTemplateColumns: "1.5fr 1fr", gap: 16 }}>
      {/* Application sections grid */}
      <div className="panel">
        <div className="panel-head">
          <h3>Application Sections</h3>
          <span className="meta">5 of 9 sections completed</span>
        </div>
        <div className="panel-body">
          <div style={{ display: "grid", gridTemplateColumns: "1fr 1fr 1fr", gap: 10 }}>
            {STUDENT_SECTIONS.filter(s => s.id !== "dashboard").map(s => (
              <button key={s.id} onClick={() => setSection(s.id)} style={{
                background: "var(--bg)", border: "1px solid var(--border)",
                borderTop: `3px solid ${s.status === "done" ? "var(--green)" : "var(--saffron)"}`,
                padding: 12, borderRadius: 3, textAlign: "left", cursor: "pointer", fontFamily: "inherit",
                display: "flex", flexDirection: "column", gap: 4,
              }}>
                <div style={{ display: "flex", alignItems: "center", gap: 6 }}>
                  <PIcon name={s.icon} size={14} style={{ color: s.status === "done" ? "var(--green)" : "var(--saffron-dark)" }}/>
                  <span style={{ fontWeight: 700, fontSize: 12.5 }}>{s.lbl}</span>
                </div>
                <div style={{ fontSize: 11, color: "var(--ink-mute)" }}>
                  {s.status === "done" ? "Completed" : "Pending — Click to fill"}
                </div>
                <Status kind={s.status === "done" ? "verified" : "pending"}>
                  {s.status === "done" ? "Completed" : "Pending"}
                </Status>
              </button>
            ))}
          </div>
        </div>
      </div>

      <div className="panel">
        <div className="panel-head"><h3>Important Notifications</h3></div>
        <div className="panel-body" style={{ padding: 0 }}>
          <div style={{ padding: "12px 14px", borderBottom: "1px solid var(--border)", fontSize: 12.5 }}>
            <div style={{ display: "flex", justifyContent: "space-between", marginBottom: 4 }}>
              <NewBadge/>
              <span style={{ color: "var(--ink-mute)", fontSize: 11 }}>27 May 2026</span>
            </div>
            <div style={{ fontWeight: 600 }}>Round-2 Counselling Schedule Released</div>
            <div style={{ fontSize: 11.5, color: "var(--ink-mute)" }}>Document verification: 13 May – 02 Jun · Merit list: 05 Jun</div>
          </div>
          <div style={{ padding: "12px 14px", borderBottom: "1px solid var(--border)", fontSize: 12.5 }}>
            <div style={{ display: "flex", justifyContent: "space-between", marginBottom: 4 }}>
              <span className="notice-pill imp">Important</span>
              <span style={{ color: "var(--ink-mute)", fontSize: 11 }}>25 May 2026</span>
            </div>
            <div style={{ fontWeight: 600 }}>OBC-NCL Certificate verification pending</div>
            <div style={{ fontSize: 11.5, color: "var(--ink-mute)" }}>Please upload a clear scanned copy issued after 01 Apr 2023</div>
          </div>
          <div style={{ padding: "12px 14px", borderBottom: "1px solid var(--border)", fontSize: 12.5 }}>
            <div style={{ display: "flex", justifyContent: "space-between", marginBottom: 4 }}>
              <span className="notice-pill admis" style={{ background: "var(--green)" }}>Verified</span>
              <span style={{ color: "var(--ink-mute)", fontSize: 11 }}>22 May 2026</span>
            </div>
            <div style={{ fontWeight: 600 }}>12th Marksheet verified via DigiLocker</div>
            <div style={{ fontSize: 11.5, color: "var(--ink-mute)" }}>Auto-verified — GSHSEB 2026 · 88.4%</div>
          </div>
          <div style={{ padding: "10px 14px" }}>
            <a href="#" style={{ fontSize: 12 }}>View all notifications →</a>
          </div>
        </div>
      </div>
    </div>

    {/* Helpful information */}
    <div className="panel" style={{ marginTop: 16 }}>
      <div className="panel-head"><h3>Programme of Interest — Quick Summary</h3><a href="#" style={{ fontSize: 12 }}>View Details →</a></div>
      <div className="panel-body">
        <div style={{ display: "grid", gridTemplateColumns: "1fr 1fr 1fr 1fr", gap: 14 }}>
          <div><div style={{ fontSize: 10.5, color: "var(--ink-mute)", textTransform: "uppercase", letterSpacing: "0.04em", fontWeight: 600 }}>Programme Code</div><div style={{ fontFamily: "var(--font-mono)", fontWeight: 700, fontSize: 14, color: "var(--navy)", marginTop: 2 }}>{p.programmeCode}</div></div>
          <div><div style={{ fontSize: 10.5, color: "var(--ink-mute)", textTransform: "uppercase", letterSpacing: "0.04em", fontWeight: 600 }}>Programme</div><div style={{ fontWeight: 700, fontSize: 13, marginTop: 2 }}>{p.programmeApplied}</div></div>
          <div><div style={{ fontSize: 10.5, color: "var(--ink-mute)", textTransform: "uppercase", letterSpacing: "0.04em", fontWeight: 600 }}>Preference</div><div style={{ fontWeight: 700, fontSize: 13, marginTop: 2 }}>{p.programmePreference} Choice</div></div>
          <div><div style={{ fontSize: 10.5, color: "var(--ink-mute)", textTransform: "uppercase", letterSpacing: "0.04em", fontWeight: 600 }}>Minor</div><div style={{ fontWeight: 700, fontSize: 13, marginTop: 2 }}>{p.minorPreference}</div></div>
        </div>
      </div>
    </div>
  </>;
}

// ===== PERSONAL =====
function PersonalDetailsPage({ setSection, setToast }) {
  const p = STUDENT_PROFILE;
  return <>
    <Crumbs items={[{ label: "Dashboard", href: "#", onClick: () => setSection("dashboard") }, { label: "Personal Details" }]}/>
    <div className="portal-h1-row">
      <div>
        <h1 className="portal-h1">Personal Details</h1>
        <p className="portal-h1-sub">As per your Aadhaar / DigiLocker records — please verify carefully before saving</p>
      </div>
      <Status kind="verified">Completed</Status>
    </div>

    <div className="panel">
      <div className="panel-head"><h3>Identity Information</h3><span className="meta">Auto-filled from DigiLocker · verified</span></div>
      <div className="panel-body">
        <div className="fg-3">
          <div className="form-row"><label>Full Name (as per Aadhaar) <span className="req">*</span></label><input defaultValue={p.name} readOnly style={{ background: "var(--bg-sunken)" }}/></div>
          <div className="form-row"><label>Father's / Guardian's Name <span className="req">*</span></label><input defaultValue={p.fatherName}/></div>
          <div className="form-row"><label>Mother's Name <span className="req">*</span></label><input defaultValue={p.motherName}/></div>
          <div className="form-row"><label>Date of Birth (DD-MM-YYYY) <span className="req">*</span></label><input defaultValue="12-08-2008" readOnly style={{ background: "var(--bg-sunken)" }}/></div>
          <div className="form-row"><label>Gender <span className="req">*</span></label><select><option>Male</option><option>Female</option><option>Other</option><option>Prefer not to say</option></select></div>
          <div className="form-row"><label>Nationality <span className="req">*</span></label><select><option>Indian</option><option>Non-Resident Indian (NRI)</option><option>Foreign National</option></select></div>
          <div className="form-row"><label>Aadhaar Number <span className="req">*</span></label><input defaultValue={p.aadhaar} readOnly style={{ background: "var(--bg-sunken)" }}/></div>
          <div className="form-row"><label>ABC ID</label><input defaultValue={p.abcId} readOnly style={{ background: "var(--bg-sunken)" }}/></div>
          <div className="form-row"><label>Blood Group</label><select><option>B+</option><option>A+</option><option>O+</option><option>AB+</option><option>B-</option><option>A-</option><option>O-</option><option>AB-</option></select></div>
        </div>
      </div>
    </div>

    <div className="panel">
      <div className="panel-head"><h3>Category & Reservation</h3></div>
      <div className="panel-body">
        <div className="fg-3">
          <div className="form-row"><label>Category <span className="req">*</span></label><select defaultValue="OBC-NCL"><option>Unreserved (UR)</option><option>OBC-NCL</option><option>SC</option><option>ST</option><option>EWS</option></select></div>
          <div className="form-row"><label>Sub-Category / PwBD</label><select><option>None</option><option>PwBD (40-80%)</option><option>PwBD (≥80%)</option><option>Kashmiri Migrant</option><option>Ward of Defence Personnel</option></select></div>
          <div className="form-row"><label>Religion</label><select><option>Hindu</option><option>Muslim</option><option>Christian</option><option>Sikh</option><option>Jain</option><option>Buddhist</option><option>Other</option></select></div>
          <div className="form-row"><label>Mother Tongue <span className="req">*</span></label><select><option>Gujarati</option><option>Hindi</option><option>English</option><option>Marathi</option><option>Tamil</option></select></div>
          <div className="form-row"><label>Domicile State <span className="req">*</span></label><select><option>Gujarat</option><option>Maharashtra</option><option>Delhi</option><option>Other</option></select></div>
          <div className="form-row"><label>Are you a foreign national?</label><select><option>No</option><option>Yes</option></select></div>
        </div>
        <div style={{ fontSize: 11.5, color: "var(--ink-mute)", marginTop: 10, padding: 10, background: "var(--bg-sunken)", borderRadius: 2 }}>
          <strong>Note:</strong> Category certificate must be issued after 01 April 2023 by the competent authority. Upload the certificate in the "Uploads" section.
        </div>
      </div>
      <div className="panel-foot">
        <button className="btn-link">← Back to Dashboard</button>
        <div style={{ display: "flex", gap: 8 }}>
          <button className="btn">Save Draft</button>
          <button className="btn primary" onClick={() => { setToast("Personal details saved"); setSection("family"); setTimeout(() => setToast(""), 1800); }}>Save & Next →</button>
        </div>
      </div>
    </div>
  </>;
}

// ===== FAMILY =====
function FamilyDetailsPage({ setSection, setToast }) {
  const p = STUDENT_PROFILE;
  return <>
    <Crumbs items={[{ label: "Dashboard", href: "#", onClick: () => setSection("dashboard") }, { label: "Family Details" }]}/>
    <div className="portal-h1-row">
      <div>
        <h1 className="portal-h1">Family Details</h1>
        <p className="portal-h1-sub">Information about parents / guardian and family</p>
      </div>
      <Status kind="verified">Completed</Status>
    </div>

    <div className="panel">
      <div className="panel-head"><h3>Father's Details</h3></div>
      <div className="panel-body">
        <div className="fg-3">
          <div className="form-row"><label>Name <span className="req">*</span></label><input defaultValue={p.fatherName}/></div>
          <div className="form-row"><label>Occupation <span className="req">*</span></label><input defaultValue={p.fatherOccupation}/></div>
          <div className="form-row"><label>Annual Income (INR) <span className="req">*</span></label><input defaultValue="680000" type="number"/></div>
          <div className="form-row"><label>Mobile <span className="req">*</span></label><input defaultValue={p.guardianMobile}/></div>
          <div className="form-row"><label>Email</label><input defaultValue="rajesh.patel@gmail.com"/></div>
          <div className="form-row"><label>Qualification</label><select><option>Graduate</option><option>Post-Graduate</option><option>Doctorate</option><option>12th</option><option>10th</option></select></div>
        </div>
      </div>
    </div>

    <div className="panel">
      <div className="panel-head"><h3>Mother's Details</h3></div>
      <div className="panel-body">
        <div className="fg-3">
          <div className="form-row"><label>Name <span className="req">*</span></label><input defaultValue={p.motherName}/></div>
          <div className="form-row"><label>Occupation <span className="req">*</span></label><input defaultValue={p.motherOccupation}/></div>
          <div className="form-row"><label>Annual Income (INR)</label><input defaultValue="0" type="number"/></div>
          <div className="form-row"><label>Mobile</label><input defaultValue="+91 98244-19989"/></div>
          <div className="form-row"><label>Email</label><input defaultValue="meena.patel@gmail.com"/></div>
          <div className="form-row"><label>Qualification</label><select><option>Graduate</option><option>Post-Graduate</option><option>12th</option><option>10th</option></select></div>
        </div>
      </div>
    </div>

    <div className="panel">
      <div className="panel-head"><h3>Other Information</h3></div>
      <div className="panel-body">
        <div className="fg-3">
          <div className="form-row"><label>Total Annual Family Income (INR) <span className="req">*</span></label><input defaultValue="680000" type="number"/></div>
          <div className="form-row"><label>Number of Siblings</label><input defaultValue="1" type="number"/></div>
          <div className="form-row"><label>Family in Government Service?</label><select><option>No</option><option>Yes</option></select></div>
          <div className="form-row"><label>Single Parent?</label><select><option>No</option><option>Yes</option></select></div>
          <div className="form-row"><label>Are you the first-generation graduate in family?</label><select><option>No</option><option>Yes</option></select></div>
          <div className="form-row"><label>Emergency Contact Number <span className="req">*</span></label><input defaultValue={p.emergencyContact}/></div>
        </div>
      </div>
      <div className="panel-foot">
        <button className="btn" onClick={() => setSection("personal")}>← Previous</button>
        <div style={{ display: "flex", gap: 8 }}>
          <button className="btn">Save Draft</button>
          <button className="btn primary" onClick={() => { setToast("Family details saved"); setSection("address"); setTimeout(() => setToast(""), 1800); }}>Save & Next →</button>
        </div>
      </div>
    </div>
  </>;
}

// ===== ADDRESS =====
function AddressPage({ setSection, setToast }) {
  const p = STUDENT_PROFILE;
  const [sameAddress, setSameAddress] = spUseState(true);
  return <>
    <Crumbs items={[{ label: "Dashboard", href: "#", onClick: () => setSection("dashboard") }, { label: "Address & Contact" }]}/>
    <div className="portal-h1-row">
      <div>
        <h1 className="portal-h1">Address & Contact</h1>
        <p className="portal-h1-sub">Permanent and correspondence addresses</p>
      </div>
      <Status kind="verified">Completed</Status>
    </div>

    <div className="panel">
      <div className="panel-head"><h3>Contact Details</h3></div>
      <div className="panel-body">
        <div className="fg-2">
          <div className="form-row"><label>Email Address <span className="req">*</span></label><input defaultValue={p.email}/><span style={{ fontSize: 11, color: "var(--green-dark)" }}>✓ Verified via OTP</span></div>
          <div className="form-row"><label>Mobile Number <span className="req">*</span></label><input defaultValue={p.mobile}/><span style={{ fontSize: 11, color: "var(--green-dark)" }}>✓ Verified via OTP</span></div>
        </div>
      </div>
    </div>

    <div className="panel">
      <div className="panel-head"><h3>Permanent Address</h3></div>
      <div className="panel-body">
        <div className="form-row"><label>House / Building / Street <span className="req">*</span></label><input defaultValue="B-204, Sapphire Heights, Off SG Road"/></div>
        <div className="fg-3">
          <div className="form-row"><label>Locality / Area <span className="req">*</span></label><input defaultValue="Bodakdev"/></div>
          <div className="form-row"><label>Country <span className="req">*</span></label><select><option>India</option></select></div>
          <div className="form-row"><label>Pincode <span className="req">*</span></label><input defaultValue={p.pincode}/></div>
          <div className="form-row"><label>State / UT <span className="req">*</span></label><select><option>Gujarat</option><option>Maharashtra</option><option>Delhi</option></select></div>
          <div className="form-row"><label>District <span className="req">*</span></label><input defaultValue={p.district}/></div>
          <div className="form-row"><label>Taluka / Tehsil</label><input defaultValue="Ahmedabad City"/></div>
        </div>
      </div>
    </div>

    <div className="panel">
      <div className="panel-head">
        <h3>Correspondence Address</h3>
        <label style={{ fontSize: 12, fontWeight: 500, display: "flex", gap: 6, alignItems: "center", cursor: "pointer" }}>
          <input type="checkbox" checked={sameAddress} onChange={e => setSameAddress(e.target.checked)}/>
          Same as Permanent Address
        </label>
      </div>
      {!sameAddress && (
        <div className="panel-body">
          <div className="form-row"><label>House / Building / Street <span className="req">*</span></label><input/></div>
          <div className="fg-3">
            <div className="form-row"><label>Locality / Area <span className="req">*</span></label><input/></div>
            <div className="form-row"><label>Pincode <span className="req">*</span></label><input/></div>
            <div className="form-row"><label>State <span className="req">*</span></label><select><option>Gujarat</option></select></div>
          </div>
        </div>
      )}
      <div className="panel-foot">
        <button className="btn" onClick={() => setSection("family")}>← Previous</button>
        <div style={{ display: "flex", gap: 8 }}>
          <button className="btn">Save Draft</button>
          <button className="btn primary" onClick={() => { setToast("Address saved"); setSection("academic"); setTimeout(() => setToast(""), 1800); }}>Save & Next →</button>
        </div>
      </div>
    </div>
  </>;
}

// ===== ACADEMIC =====
function AcademicPage({ setSection, setToast }) {
  const p = STUDENT_PROFILE;
  return <>
    <Crumbs items={[{ label: "Dashboard", href: "#", onClick: () => setSection("dashboard") }, { label: "Academic Records" }]}/>
    <div className="portal-h1-row">
      <div>
        <h1 className="portal-h1">Academic Records</h1>
        <p className="portal-h1-sub">Educational qualifications — verified via DigiLocker where available</p>
      </div>
      <Status kind="verified">Completed</Status>
    </div>

    <div className="panel">
      <div className="panel-head"><h3>Class X (Secondary / SSC)</h3><span className="meta" style={{ color: "var(--green-dark)", fontWeight: 600 }}>✓ Verified via DigiLocker</span></div>
      <div className="panel-body">
        <div className="fg-3">
          <div className="form-row"><label>Board / Council <span className="req">*</span></label><input defaultValue={p.board10}/></div>
          <div className="form-row"><label>Year of Passing <span className="req">*</span></label><input defaultValue={p.board10Year}/></div>
          <div className="form-row"><label>Roll / Seat Number <span className="req">*</span></label><input defaultValue="C24-7741-12"/></div>
          <div className="form-row"><label>School / Institution Name <span className="req">*</span></label><input defaultValue="Delhi Public School, Bopal"/></div>
          <div className="form-row"><label>Marks Obtained <span className="req">*</span></label><input defaultValue="463"/></div>
          <div className="form-row"><label>Maximum Marks <span className="req">*</span></label><input defaultValue="500"/></div>
          <div className="form-row"><label>Percentage <span className="req">*</span></label><input defaultValue={p.board10Pct + "%"} readOnly style={{ background: "var(--bg-sunken)" }}/></div>
          <div className="form-row"><label>CGPA</label><input defaultValue="9.7"/></div>
          <div className="form-row"><label>Medium of Instruction</label><select><option>English</option><option>Gujarati</option><option>Hindi</option></select></div>
        </div>
      </div>
    </div>

    <div className="panel">
      <div className="panel-head"><h3>Class XII (Higher Secondary / HSC)</h3><span className="meta" style={{ color: "var(--green-dark)", fontWeight: 600 }}>✓ Verified via DigiLocker</span></div>
      <div className="panel-body">
        <div className="fg-3">
          <div className="form-row"><label>Board / Council <span className="req">*</span></label><input defaultValue={p.board12}/></div>
          <div className="form-row"><label>Year of Passing <span className="req">*</span></label><input defaultValue={p.board12Year}/></div>
          <div className="form-row"><label>Roll Number <span className="req">*</span></label><input defaultValue={p.board12Roll}/></div>
          <div className="form-row"><label>Stream / Subject Combination <span className="req">*</span></label><input defaultValue={p.board12Stream}/></div>
          <div className="form-row"><label>Aggregate (Best 5) <span className="req">*</span></label><input defaultValue={p.board12Pct + "%"} readOnly style={{ background: "var(--bg-sunken)" }}/></div>
          <div className="form-row"><label>School Code</label><input defaultValue="GJ-AH-1024"/></div>
        </div>

        <div style={{ marginTop: 12 }}>
          <div style={{ fontWeight: 700, fontSize: 12.5, marginBottom: 8, color: "var(--maroon)" }}>Subject-wise Marks (Class XII)</div>
          <div className="data-table-wrap">
            <table className="data-table">
              <thead>
                <tr>
                  <th style={{ width: 60 }}>Sl.</th>
                  <th>Subject</th>
                  <th style={{ width: 100 }}>Subject Code</th>
                  <th style={{ width: 100 }}>Theory</th>
                  <th style={{ width: 100 }}>Practical</th>
                  <th style={{ width: 100 }}>Total</th>
                  <th style={{ width: 80 }}>Max</th>
                  <th style={{ width: 80 }}>%</th>
                </tr>
              </thead>
              <tbody>
                {[
                  { s: "English", c: "ENG-101", t: 70, pr: 20, m: 100 },
                  { s: "Physics", c: "PHY-301", t: 64, pr: 22, m: 100 },
                  { s: "Chemistry", c: "CHE-302", t: 62, pr: 22, m: 100 },
                  { s: "Mathematics", c: "MAT-304", t: 92, pr: 0, m: 100 },
                  { s: "Computer Science", c: "CSC-305", t: 73, pr: 22, m: 100 },
                ].map((r, i) => {
                  const tot = r.t + r.pr;
                  const pct = ((tot / r.m) * 100).toFixed(1);
                  return (
                    <tr key={i}>
                      <td style={{ textAlign: "center" }}>{i + 1}</td>
                      <td style={{ fontWeight: 500 }}>{r.s}</td>
                      <td style={{ fontFamily: "var(--font-mono)" }}>{r.c}</td>
                      <td style={{ textAlign: "center", fontFamily: "var(--font-mono)" }}>{r.t}</td>
                      <td style={{ textAlign: "center", fontFamily: "var(--font-mono)" }}>{r.pr || "—"}</td>
                      <td style={{ textAlign: "center", fontFamily: "var(--font-mono)", fontWeight: 700 }}>{tot}</td>
                      <td style={{ textAlign: "center", fontFamily: "var(--font-mono)" }}>{r.m}</td>
                      <td style={{ textAlign: "center", fontWeight: 600, color: pct >= 90 ? "var(--green-dark)" : pct >= 80 ? "var(--navy)" : "var(--saffron-dark)" }}>{pct}%</td>
                    </tr>
                  );
                })}
                <tr className="total">
                  <td colSpan={3} style={{ textAlign: "right" }}>Aggregate</td>
                  <td style={{ textAlign: "center", fontFamily: "var(--font-mono)" }}>361</td>
                  <td style={{ textAlign: "center", fontFamily: "var(--font-mono)" }}>86</td>
                  <td style={{ textAlign: "center", fontFamily: "var(--font-mono)" }}>447</td>
                  <td style={{ textAlign: "center", fontFamily: "var(--font-mono)" }}>500</td>
                  <td style={{ textAlign: "center", color: "var(--green-dark)" }}>89.4%</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>

    <div className="panel">
      <div className="panel-head"><h3>Entrance / Competitive Examinations (Optional)</h3></div>
      <div className="panel-body">
        <div className="fg-4">
          <div className="form-row"><label>Examination</label><select><option>CUET-UG 2026</option><option>JEE Main 2026</option><option>NEET-UG 2026</option><option>State CET</option><option>Not Applicable</option></select></div>
          <div className="form-row"><label>Roll Number</label><input placeholder="CUET24XXXX"/></div>
          <div className="form-row"><label>Score / Percentile</label><input placeholder="94.2"/></div>
          <div className="form-row"><label>Year</label><input defaultValue="2026"/></div>
        </div>
      </div>
      <div className="panel-foot">
        <button className="btn" onClick={() => setSection("address")}>← Previous</button>
        <div style={{ display: "flex", gap: 8 }}>
          <button className="btn">Save Draft</button>
          <button className="btn primary" onClick={() => { setToast("Academic records saved"); setSection("other"); setTimeout(() => setToast(""), 1800); }}>Save & Next →</button>
        </div>
      </div>
    </div>
  </>;
}

// ===== OTHER =====
function OtherDetailsPage({ setSection, setToast }) {
  return <>
    <Crumbs items={[{ label: "Dashboard", href: "#", onClick: () => setSection("dashboard") }, { label: "Other Details" }]}/>
    <div className="portal-h1-row">
      <div>
        <h1 className="portal-h1">Other Details</h1>
        <p className="portal-h1-sub">Achievements, sports, NCC, extracurricular activities</p>
      </div>
      <Status kind="pending">Pending</Status>
    </div>

    <div className="panel">
      <div className="panel-head"><h3>Co-curricular & Extracurricular</h3></div>
      <div className="panel-body">
        <div className="fg-3">
          <div className="form-row"><label>NCC Certificate</label><select><option>None</option><option>A Certificate</option><option>B Certificate</option><option>C Certificate</option></select></div>
          <div className="form-row"><label>NSS Volunteer</label><select><option>No</option><option>Yes</option></select></div>
          <div className="form-row"><label>Sports Achievement</label><select><option>None</option><option>School Level</option><option>District Level</option><option>State Level</option><option>National Level</option><option>International</option></select></div>
        </div>
        <div className="form-row" style={{ marginTop: 14 }}>
          <label>Awards / Achievements (if any)</label>
          <textarea rows="3" placeholder="e.g. 1st prize in National Maths Olympiad 2024; Selected for State Tennis team 2025…"></textarea>
        </div>
      </div>
    </div>

    <div className="panel">
      <div className="panel-head"><h3>Additional Information</h3></div>
      <div className="panel-body">
        <div className="fg-3">
          <div className="form-row"><label>Hosteller / Day Scholar?</label><select><option>Day Scholar</option><option>Hosteller</option></select></div>
          <div className="form-row"><label>Transport Required?</label><select><option>No</option><option>Yes — College Bus</option></select></div>
          <div className="form-row"><label>Mode of Communication Preferred</label><select><option>Email + SMS</option><option>Email only</option><option>SMS only</option><option>WhatsApp</option></select></div>
        </div>
      </div>
      <div className="panel-foot">
        <button className="btn" onClick={() => setSection("academic")}>← Previous</button>
        <div style={{ display: "flex", gap: 8 }}>
          <button className="btn">Save Draft</button>
          <button className="btn primary" onClick={() => { setToast("Other details saved"); setSection("uploads"); setTimeout(() => setToast(""), 1800); }}>Save & Next →</button>
        </div>
      </div>
    </div>
  </>;
}

// ===== UPLOADS =====
function UploadsPage({ setSection, setToast }) {
  const [docs, setDocs] = spUseState(DOC_LIST);
  const upload = (i) => {
    setDocs(d => d.map((x, j) => j === i ? { ...x, status: "Uploading" } : x));
    setTimeout(() => {
      setDocs(d => d.map((x, j) => j === i ? { ...x, status: "Submitted", file: "uploaded.pdf", size: "1.0 MB" } : x));
      setToast("Document uploaded"); setTimeout(() => setToast(""), 1500);
    }, 900);
  };
  return <>
    <Crumbs items={[{ label: "Dashboard", href: "#", onClick: () => setSection("dashboard") }, { label: "Uploads" }]}/>
    <div className="portal-h1-row">
      <div>
        <h1 className="portal-h1">Documents — Uploads</h1>
        <p className="portal-h1-sub">Accepted formats: PDF, JPG, PNG · Maximum 2 MB per file · Photo & Signature only JPG/PNG</p>
      </div>
      <Status kind="pending">Pending</Status>
    </div>

    <div className="panel">
      <div className="panel-head">
        <h3>Required & Optional Documents</h3>
        <span className="meta">{docs.filter(d => d.status === "Verified" || d.status === "Submitted").length} of {docs.length} uploaded</span>
      </div>
      <div className="panel-body">
        <div style={{ display: "flex", flexDirection: "column", gap: 10 }}>
          {docs.map((d, i) => (
            <div key={i} className={`upload-box ${d.status === "Verified" || d.status === "Submitted" ? "uploaded" : ""}`}>
              <div className="uthumb"><PIcon name="file" size={20}/></div>
              <div style={{ flex: 1, minWidth: 0 }}>
                <div style={{ display: "flex", alignItems: "center", gap: 8, marginBottom: 2 }}>
                  <span style={{ fontWeight: 600, fontSize: 13 }}>{d.name}</span>
                  {d.required && <span style={{ fontSize: 10, color: "var(--red)", fontWeight: 700 }}>* REQUIRED</span>}
                  {d.digilocker && <span style={{ fontSize: 10, color: "var(--navy)", fontWeight: 700, background: "rgba(0, 51, 102, 0.08)", padding: "1px 6px", borderRadius: 2 }}>via DigiLocker</span>}
                </div>
                <div style={{ fontSize: 11.5, color: "var(--ink-mute)" }}>
                  {d.file ? <><strong>{d.file}</strong> · {d.size}</> : <span>PDF/JPG · Max 2 MB</span>}
                </div>
              </div>
              <div style={{ display: "flex", gap: 6, alignItems: "center" }}>
                {d.status === "Verified" && <Status kind="verified">Verified</Status>}
                {d.status === "Submitted" && <Status kind="submitted">Submitted</Status>}
                {d.status === "Pending" && <Status kind="pending">Under Review</Status>}
                {d.status === "Uploading" && <Status kind="pending">Uploading…</Status>}
                {d.status === "Not Uploaded" && <button className="btn sm" onClick={() => upload(i)}><PIcon name="upload" size={11}/> Upload</button>}
                {(d.status === "Verified" || d.status === "Submitted") && <button className="btn sm"><PIcon name="eye" size={11}/> View</button>}
              </div>
            </div>
          ))}
        </div>
      </div>
      <div className="panel-foot">
        <button className="btn" onClick={() => setSection("other")}>← Previous</button>
        <button className="btn primary" onClick={() => setSection("programme")}>Save & Next →</button>
      </div>
    </div>
  </>;
}

// ===== PROGRAMME SELECTION =====
function ProgrammeSelectionPage({ setSection, setToast }) {
  const [selected, setSelected] = spUseState(["UGCS01", "UGEC02", "UGCM03"]);
  const toggle = (code) => {
    if (selected.includes(code)) {
      setSelected(selected.filter(c => c !== code));
    } else if (selected.length < 5) {
      setSelected([...selected, code]);
    }
  };
  return <>
    <Crumbs items={[{ label: "Dashboard", href: "#", onClick: () => setSection("dashboard") }, { label: "Programme Selection" }]}/>
    <div className="portal-h1-row">
      <div>
        <h1 className="portal-h1">Programme Selection</h1>
        <p className="portal-h1-sub">Choose up to 5 programmes in order of preference. Seat allocation will follow your preference order.</p>
      </div>
      <Status kind="pending">Pending</Status>
    </div>

    {selected.length > 0 && (
      <div className="panel">
        <div className="panel-head"><h3>Your Preferences ({selected.length} / 5)</h3></div>
        <div className="panel-body">
          <div className="data-table-wrap">
            <table className="data-table">
              <thead>
                <tr>
                  <th style={{ width: 80 }}>Preference</th>
                  <th style={{ width: 90 }}>Code</th>
                  <th>Programme</th>
                  <th style={{ width: 90 }}>Eligibility</th>
                  <th style={{ width: 100 }}>Action</th>
                </tr>
              </thead>
              <tbody>
                {selected.map((code, i) => {
                  const p = PROGRAMMES.find(p => p.code === code);
                  return (
                    <tr key={code}>
                      <td><div style={{ width: 32, height: 32, background: "var(--maroon)", color: "#fff", borderRadius: 3, display: "grid", placeItems: "center", fontWeight: 700, fontFamily: "var(--font-serif)" }}>{i + 1}</div></td>
                      <td style={{ fontFamily: "var(--font-mono)", fontWeight: 600 }}>{p.code}</td>
                      <td style={{ fontWeight: 500 }}>{p.name} <span style={{ color: "var(--ink-mute)" }}>({p.dept})</span></td>
                      <td><Status kind="verified">Eligible</Status></td>
                      <td>
                        <button className="btn sm" onClick={() => toggle(code)}><PIcon name="x" size={10}/> Remove</button>
                      </td>
                    </tr>
                  );
                })}
              </tbody>
            </table>
          </div>
        </div>
      </div>
    )}

    <div className="panel">
      <div className="panel-head"><h3>All Available Programmes</h3></div>
      <div className="panel-body">
        <div className="data-table-wrap">
          <table className="data-table">
            <thead>
              <tr>
                <th style={{ width: 60 }}>Select</th>
                <th style={{ width: 90 }}>Code</th>
                <th>Programme</th>
                <th>Eligibility</th>
                <th style={{ width: 100 }}>Annual Fee</th>
              </tr>
            </thead>
            <tbody>
              {PROGRAMMES.map(p => (
                <tr key={p.code}>
                  <td style={{ textAlign: "center" }}>
                    <input type="checkbox" checked={selected.includes(p.code)} onChange={() => toggle(p.code)} disabled={!selected.includes(p.code) && selected.length >= 5}/>
                  </td>
                  <td style={{ fontFamily: "var(--font-mono)", fontWeight: 600, color: "var(--navy)" }}>{p.code}</td>
                  <td style={{ fontWeight: 500 }}>{p.name} <span style={{ color: "var(--ink-mute)", fontSize: 11 }}>{p.dept}</span></td>
                  <td style={{ fontSize: 11.5 }}>{p.eligibility}</td>
                  <td style={{ fontFamily: "var(--font-mono)" }}>{inr(p.fee)}</td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
        <div style={{ marginTop: 12, padding: 10, background: "var(--saffron-soft)", border: "1px solid var(--saffron)", fontSize: 11.5, borderRadius: 2 }}>
          <strong>Note:</strong> Eligibility is auto-calculated based on your academic records. You may select programmes for which you are eligible. Allocation is subject to merit and seat availability.
        </div>
      </div>
      <div className="panel-foot">
        <button className="btn" onClick={() => setSection("uploads")}>← Previous</button>
        <button className="btn primary" onClick={() => { setToast("Preferences saved"); setSection("payment"); setTimeout(() => setToast(""), 1800); }}>Save & Next →</button>
      </div>
    </div>
  </>;
}

// ===== PAYMENT =====
function PaymentPage({ setSection, setToast }) {
  const [method, setMethod] = spUseState("upi");
  const [paid, setPaid] = spUseState(false);
  const [processing, setProcessing] = spUseState(false);
  const pay = () => {
    setProcessing(true);
    setTimeout(() => { setProcessing(false); setPaid(true); setToast("Payment successful — receipt generated"); setTimeout(() => setToast(""), 2000); }, 1400);
  };
  return <>
    <Crumbs items={[{ label: "Dashboard", href: "#", onClick: () => setSection("dashboard") }, { label: "Fee Payment" }]}/>
    <div className="portal-h1-row">
      <div>
        <h1 className="portal-h1">Application Fee Payment</h1>
        <p className="portal-h1-sub">Pay online via Net Banking / UPI / Debit Card / Credit Card · Refunds as per refund policy</p>
      </div>
      <Status kind={paid ? "paid" : "unpaid"}>{paid ? "Paid" : "Unpaid"}</Status>
    </div>

    <div style={{ display: "grid", gridTemplateColumns: "1.4fr 1fr", gap: 16 }}>
      <div className="panel">
        <div className="panel-head"><h3>Fee Particulars</h3></div>
        <div className="panel-body" style={{ padding: 0 }}>
          <table className="data-table">
            <thead>
              <tr>
                <th style={{ width: 50 }}>Sl.</th>
                <th>Fee Head</th>
                <th style={{ width: 100 }}>Category</th>
                <th style={{ width: 120 }}>Amount (INR)</th>
              </tr>
            </thead>
            <tbody>
              <tr><td style={{ textAlign: "center" }}>1</td><td>Application Processing Fee</td><td>OBC-NCL</td><td style={{ textAlign: "right", fontFamily: "var(--font-mono)" }}>1,200.00</td></tr>
              <tr><td style={{ textAlign: "center" }}>2</td><td>Online Convenience Charges</td><td>—</td><td style={{ textAlign: "right", fontFamily: "var(--font-mono)" }}>30.00</td></tr>
              <tr><td style={{ textAlign: "center" }}>3</td><td>GST @ 18%</td><td>—</td><td style={{ textAlign: "right", fontFamily: "var(--font-mono)" }}>221.40</td></tr>
              <tr className="total">
                <td colSpan={3} style={{ textAlign: "right" }}>Total Payable</td>
                <td style={{ textAlign: "right", fontFamily: "var(--font-mono)", fontSize: 14 }}>₹ 1,451.40</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <div>
        <div className="panel">
          <div className="panel-head"><h3>Payment Method</h3></div>
          <div className="panel-body">
            <div style={{ display: "flex", flexDirection: "column", gap: 6 }}>
              {[
                { id: "upi", label: "UPI (PhonePe / GPay / Paytm / BHIM)" },
                { id: "net", label: "Net Banking (All major banks)" },
                { id: "dc", label: "Debit Card (Visa / Mastercard / RuPay)" },
                { id: "cc", label: "Credit Card" },
              ].map(m => (
                <label key={m.id} style={{
                  padding: "8px 10px", borderRadius: 2,
                  border: `1px solid ${method === m.id ? "var(--maroon)" : "var(--border)"}`,
                  background: method === m.id ? "var(--saffron-soft)" : "var(--bg)",
                  display: "flex", gap: 8, alignItems: "center", cursor: "pointer", fontSize: 12.5,
                }}>
                  <input type="radio" name="m" checked={method === m.id} onChange={() => setMethod(m.id)}/>
                  {m.label}
                </label>
              ))}
            </div>
            {!paid && <button className="btn-primary" style={{ marginTop: 14, width: "100%" }} onClick={pay}>
              {processing ? "Processing… do not refresh" : "Pay ₹ 1,451.40 Securely"}
            </button>}
            {paid && (
              <div style={{ marginTop: 14, padding: 12, background: "rgba(19, 136, 8, 0.08)", border: "1px solid var(--green)", fontSize: 12 }}>
                <div style={{ fontWeight: 700, color: "var(--green-dark)" }}><PIcon name="check" size={13} stroke={2.6}/> Payment Successful</div>
                <div style={{ marginTop: 4 }}>Transaction ID: <strong style={{ fontFamily: "var(--font-mono)" }}>SVNC-22847-2026</strong></div>
                <div>Date: 27-May-2026 14:32:18 IST</div>
              </div>
            )}
          </div>
        </div>
        <div className="panel" style={{ background: "var(--bg-cream)" }}>
          <div className="panel-body" style={{ fontSize: 11.5, color: "var(--ink-soft)" }}>
            <strong>Refund Policy:</strong> Application fee is non-refundable except in cases of duplicate payment. Please review your details carefully before paying.
          </div>
        </div>
      </div>
    </div>
    <div className="panel" style={{ marginTop: 16 }}>
      <div className="panel-foot">
        <button className="btn" onClick={() => setSection("programme")}>← Previous</button>
        <div style={{ display: "flex", gap: 8 }}>
          {paid && <button className="btn"><PIcon name="download" size={12}/> Download Receipt</button>}
          <button className="btn primary" disabled={!paid} onClick={() => setSection("preview")}>Proceed to Preview →</button>
        </div>
      </div>
    </div>
  </>;
}

// ===== PREVIEW =====
function PreviewPage({ setSection, setToast }) {
  const p = STUDENT_PROFILE;
  const [submitted, setSubmitted] = spUseState(false);
  if (submitted) {
    return (
      <div style={{ background: "var(--bg)", border: "1px solid var(--border)", padding: "60px 40px", textAlign: "center", borderRadius: 3 }}>
        <div style={{ width: 80, height: 80, borderRadius: 50, background: "var(--green)", color: "#fff", display: "grid", placeItems: "center", margin: "0 auto 18px" }}>
          <PIcon name="check" size={42} stroke={3}/>
        </div>
        <h1 style={{ fontFamily: "var(--font-serif)", color: "var(--maroon)", fontSize: 28, margin: "0 0 6px" }}>Application Submitted Successfully!</h1>
        <p style={{ color: "var(--ink-mute)", maxWidth: 560, margin: "0 auto 24px" }}>Your application for <strong>{p.programmeApplied}</strong> has been submitted to the Office of the Dean (Admissions). You will receive a confirmation email at <strong>{p.email}</strong>.</p>
        <div style={{ display: "inline-block", background: "var(--bg-cream)", border: "1px solid var(--border)", padding: 16, marginBottom: 24, textAlign: "left" }}>
          <div style={{ fontSize: 11, color: "var(--ink-mute)", textTransform: "uppercase", letterSpacing: "0.06em", fontWeight: 700 }}>Application Number</div>
          <div style={{ fontFamily: "var(--font-mono)", fontSize: 18, fontWeight: 700, color: "var(--maroon)" }}>{p.applicationNo}</div>
          <div style={{ fontSize: 11, color: "var(--ink-mute)", marginTop: 8 }}>Date of Submission: 27-May-2026 14:35:22 IST</div>
        </div>
        <div style={{ display: "flex", gap: 8, justifyContent: "center" }}>
          <button className="btn"><PIcon name="print" size={12}/> Print Application</button>
          <button className="btn navy"><PIcon name="download" size={12}/> Download as PDF</button>
          <button className="btn primary" onClick={() => setSection("dashboard")}>Back to Dashboard</button>
        </div>
      </div>
    );
  }
  return <>
    <Crumbs items={[{ label: "Dashboard", href: "#", onClick: () => setSection("dashboard") }, { label: "Preview & Submit" }]}/>
    <div className="portal-h1-row">
      <div>
        <h1 className="portal-h1">Preview Application Form</h1>
        <p className="portal-h1-sub">Review all sections carefully. After submission, critical fields cannot be edited.</p>
      </div>
      <button className="btn"><PIcon name="print" size={12}/> Print Preview</button>
    </div>

    <div className="panel">
      <div className="panel-head"><h3>1. Personal Details</h3><a href="#" onClick={(e) => { e.preventDefault(); setSection("personal"); }}><PIcon name="edit" size={11}/> Edit</a></div>
      <div className="panel-body">
        <div className="kv-list" style={{ gridTemplateColumns: "1fr 1fr 1fr 1fr" }}>
          <div className="k">Application No.</div><div className="v" style={{ fontFamily: "var(--font-mono)" }}>{p.applicationNo}</div>
          <div className="k">ABC ID</div><div className="v" style={{ fontFamily: "var(--font-mono)" }}>{p.abcId}</div>
          <div className="k">Full Name</div><div className="v">{p.name}</div>
          <div className="k">Date of Birth</div><div className="v">{p.dob}</div>
          <div className="k">Gender</div><div className="v">{p.gender}</div>
          <div className="k">Category</div><div className="v">{p.category}</div>
          <div className="k">Aadhaar</div><div className="v" style={{ fontFamily: "var(--font-mono)" }}>{p.aadhaar}</div>
          <div className="k">Nationality</div><div className="v">{p.nationality}</div>
        </div>
      </div>
    </div>

    <div className="panel">
      <div className="panel-head"><h3>2. Family & Address</h3><a href="#" onClick={(e) => { e.preventDefault(); setSection("family"); }}><PIcon name="edit" size={11}/> Edit</a></div>
      <div className="panel-body">
        <div className="kv-list" style={{ gridTemplateColumns: "1fr 1fr 1fr 1fr" }}>
          <div className="k">Father's Name</div><div className="v">{p.fatherName}</div>
          <div className="k">Mother's Name</div><div className="v">{p.motherName}</div>
          <div className="k">Annual Family Income</div><div className="v">{p.fatherIncome}</div>
          <div className="k">Guardian Mobile</div><div className="v">{p.guardianMobile}</div>
          <div className="k">Email</div><div className="v">{p.email}</div>
          <div className="k">Mobile</div><div className="v">{p.mobile}</div>
        </div>
        <div className="kv-list" style={{ gridTemplateColumns: "1fr 3fr", marginTop: 10 }}>
          <div className="k">Permanent Address</div>
          <div className="v" style={{ textAlign: "left" }}>{p.address}</div>
        </div>
      </div>
    </div>

    <div className="panel">
      <div className="panel-head"><h3>3. Academic Records</h3><a href="#" onClick={(e) => { e.preventDefault(); setSection("academic"); }}><PIcon name="edit" size={11}/> Edit</a></div>
      <div className="panel-body">
        <table className="data-table" style={{ border: "1px solid var(--border)" }}>
          <thead><tr><th>Exam</th><th>Board</th><th>Year</th><th>Stream</th><th>Marks %</th><th>Verification</th></tr></thead>
          <tbody>
            <tr><td>Class X</td><td>{p.board10}</td><td>{p.board10Year}</td><td>—</td><td style={{ fontFamily: "var(--font-mono)", fontWeight: 600 }}>{p.board10Pct}%</td><td><Status kind="verified">DigiLocker</Status></td></tr>
            <tr><td>Class XII</td><td>{p.board12}</td><td>{p.board12Year}</td><td>{p.board12Stream}</td><td style={{ fontFamily: "var(--font-mono)", fontWeight: 600 }}>{p.board12Pct}%</td><td><Status kind="verified">DigiLocker</Status></td></tr>
          </tbody>
        </table>
      </div>
    </div>

    <div className="panel">
      <div className="panel-head"><h3>4. Programme & Payment</h3></div>
      <div className="panel-body">
        <div className="kv-list" style={{ gridTemplateColumns: "1fr 1fr 1fr 1fr" }}>
          <div className="k">Programme Applied</div><div className="v">{p.programmeApplied}</div>
          <div className="k">Code</div><div className="v" style={{ fontFamily: "var(--font-mono)" }}>{p.programmeCode}</div>
          <div className="k">Minor</div><div className="v">{p.minorPreference}</div>
          <div className="k">Fee Status</div><div className="v"><Status kind="paid">Paid · ₹ 1,451.40</Status></div>
        </div>
      </div>
    </div>

    <div className="panel" style={{ background: "var(--saffron-soft)", borderColor: "var(--saffron)" }}>
      <div className="panel-body">
        <label style={{ display: "flex", gap: 10, alignItems: "flex-start", fontSize: 12.5, lineHeight: 1.5 }}>
          <input type="checkbox" defaultChecked style={{ marginTop: 3 }}/>
          <div>
            <strong>Declaration:</strong> I hereby declare that all the information furnished above is true, complete and correct to the best of my knowledge and belief. I understand that in the event of any information being found false or incorrect, my candidature / admission is liable to be cancelled. I undertake to abide by the rules and regulations of Sardar Vallabhbhai National College and the affiliated bodies.
          </div>
        </label>
      </div>
      <div className="panel-foot" style={{ background: "rgba(232, 119, 34, 0.08)" }}>
        <button className="btn" onClick={() => setSection("payment")}>← Previous</button>
        <div style={{ display: "flex", gap: 8 }}>
          <button className="btn"><PIcon name="print" size={12}/> Print Preview</button>
          <button className="btn primary" onClick={() => { setSubmitted(true); window.scrollTo(0, 0); }}>
            <PIcon name="check" size={13}/> Final Submit Application
          </button>
        </div>
      </div>
    </div>
  </>;
}

Object.assign(window, { StudentPortal });
