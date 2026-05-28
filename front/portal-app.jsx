// Main app router

const { useState: paUseState, useEffect: paUseEffect } = React;

const PORTAL_TWEAK_DEFAULTS = /*EDITMODE-BEGIN*/{
  "view": "public",
  "role": "student",
  "dark": false
}/*EDITMODE-END*/;

function PortalApp() {
  const [t, setTweak] = useTweaks(PORTAL_TWEAK_DEFAULTS);
  const [section, setSection] = paUseState("dashboard");
  const [adminSection, setAdminSection] = paUseState("dashboard");
  const [navItem, setNavItem] = paUseState("home");
  const [toast, setToast] = paUseState("");

  // Dark mode
  paUseEffect(() => {
    document.documentElement.dataset.theme = t.dark ? "dark" : "light";
  }, [t.dark]);

  // Reset to first section when role changes
  paUseEffect(() => {
    if (t.role === "student") setSection("dashboard");
    if (t.role === "admin") setAdminSection("dashboard");
  }, [t.role]);

  const handleLogin = () => setTweak("view", "portal");
  const handleLogout = () => setTweak("view", "public");

  const isPortal = t.view === "portal";

  return <>
    <TopUtilityBar/>
    <MainHeader/>
    <PortalTopNav
      isPortal={isPortal}
      navItem={navItem}
      setNavItem={setNavItem}
      onApplyClick={handleLogin}
      role={t.role}
      onRoleChange={(r) => setTweak("role", r)}
      onLogout={handleLogout}
    />

    {isPortal ? (
      t.role === "student"
        ? <StudentPortal section={section} setSection={setSection} setToast={setToast}/>
        : t.role === "admin"
          ? <AdminPortal section={adminSection} setSection={setAdminSection} setToast={setToast}/>
          : <PlaceholderPortal/>
    ) : (
      <PublicLanding onLogin={handleLogin} setToast={setToast}/>
    )}

    {/* Tweaks */}
    <TweaksPanel title="Tweaks">
      <TweakSection label="View Mode">
        <TweakRadio value={t.view} onChange={v => setTweak("view", v)} options={[
          { value: "public", label: "Public" },
          { value: "portal", label: "Logged In" },
        ]}/>
      </TweakSection>
      <TweakSection label="View as Role">
        <TweakRadio value={t.role} onChange={v => setTweak("role", v)} options={[
          { value: "student", label: "Student" },
          { value: "admin", label: "Admin" },
        ]}/>
        <div style={{ fontSize: 11, color: "var(--ink-mute)", marginTop: 8 }}>
          Switch between the student portal (Samarth-style application form) and the admin dashboard.
        </div>
      </TweakSection>
      <TweakSection label="Appearance">
        <TweakToggle label="Dark mode" value={t.dark} onChange={v => setTweak("dark", v)}/>
      </TweakSection>
      <TweakSection label="Quick Jump">
        {isPortal && t.role === "student" && (
          <div style={{ display: "flex", flexDirection: "column", gap: 4 }}>
            {STUDENT_SECTIONS.map(s => (
              <button key={s.id} onClick={() => setSection(s.id)} style={{
                textAlign: "left", padding: "6px 10px", fontSize: 12,
                border: "1px solid var(--border)", borderRadius: 2,
                background: section === s.id ? "var(--saffron-soft)" : "var(--bg)",
                color: section === s.id ? "var(--maroon)" : "var(--ink)",
                fontWeight: section === s.id ? 600 : 400, fontFamily: "inherit", cursor: "pointer",
              }}>{s.lbl}</button>
            ))}
          </div>
        )}
        {isPortal && t.role === "admin" && (
          <div style={{ display: "flex", flexDirection: "column", gap: 4 }}>
            {ADMIN_SECTIONS.map(s => (
              <button key={s.id} onClick={() => setAdminSection(s.id)} style={{
                textAlign: "left", padding: "6px 10px", fontSize: 12,
                border: "1px solid var(--border)", borderRadius: 2,
                background: adminSection === s.id ? "var(--saffron-soft)" : "var(--bg)",
                color: adminSection === s.id ? "var(--maroon)" : "var(--ink)",
                fontWeight: adminSection === s.id ? 600 : 400, fontFamily: "inherit", cursor: "pointer",
              }}>{s.lbl}</button>
            ))}
          </div>
        )}
        {!isPortal && (
          <div style={{ fontSize: 12, color: "var(--ink-mute)" }}>Sections will appear once you switch to "Logged In" view or click "Apply Online".</div>
        )}
      </TweakSection>
    </TweaksPanel>

    <PortalToast msg={toast}/>
  </>;
}

function PortalTopNav({ isPortal, navItem, setNavItem, onApplyClick, role, onRoleChange, onLogout }) {
  if (!isPortal) {
    return <MainNav active={navItem} setActive={setNavItem} onApplyClick={onApplyClick}/>;
  }
  // Portal navigation bar — shows role switcher and logout
  return (
    <nav className="main-nav">
      <div className="container" style={{ display: "flex", alignItems: "center" }}>
        <button className={`nav-link ${navItem === "home" ? "active" : ""}`} onClick={onLogout}>
          <PIcon name="home" size={13}/> Home
        </button>
        <button className={`nav-link active`}>
          {role === "student" ? "Student Portal" : "Admin Portal"}
        </button>
        <button className="nav-link"><PIcon name="bell" size={13}/> Notifications</button>
        <button className="nav-link"><PIcon name="phone" size={13}/> Helpdesk</button>

        <div style={{ marginLeft: "auto", display: "flex", alignItems: "center", gap: 8, paddingRight: 16 }}>
          <div style={{ display: "flex", background: "rgba(255,255,255,0.08)", borderRadius: 3, padding: 2 }}>
            <button onClick={() => onRoleChange("student")} style={{
              padding: "4px 10px", border: "none", background: role === "student" ? "var(--saffron)" : "transparent",
              color: "#fff", fontSize: 11.5, fontWeight: 600, cursor: "pointer", borderRadius: 2, fontFamily: "inherit"
            }}>Student</button>
            <button onClick={() => onRoleChange("admin")} style={{
              padding: "4px 10px", border: "none", background: role === "admin" ? "var(--saffron)" : "transparent",
              color: "#fff", fontSize: 11.5, fontWeight: 600, cursor: "pointer", borderRadius: 2, fontFamily: "inherit"
            }}>Admin</button>
          </div>
          <button onClick={onLogout} style={{
            background: "transparent", border: "1px solid rgba(255,255,255,0.3)",
            color: "#fff", fontSize: 11.5, fontWeight: 600, padding: "5px 12px",
            borderRadius: 3, cursor: "pointer", fontFamily: "inherit", display: "flex", alignItems: "center", gap: 6,
          }}>
            <PIcon name="arrowR" size={12}/> Logout
          </button>
        </div>
      </div>
    </nav>
  );
}

function PlaceholderPortal() {
  return (
    <div style={{ padding: 60, textAlign: "center" }}>
      <div style={{ fontFamily: "var(--font-serif)", fontSize: 22, color: "var(--maroon)" }}>Coming soon</div>
    </div>
  );
}

ReactDOM.createRoot(document.getElementById("root")).render(<PortalApp/>);
