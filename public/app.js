const categories = [
  ["Road Damage", "road", "Broken asphalt, cracks, and unsafe road surfaces."],
  ["Potholes", "pothole", "Report potholes for quick repair and safer travel."],
  ["Garbage", "trash", "Uncollected waste and garbage accumulation."],
  ["Water Leakage", "drop", "Pipeline leaks and water supply concerns."],
  ["Drainage", "drain", "Blocked drains, flooding, and sewage issues."],
  ["Street Light", "bulb", "Faulty or damaged public street lighting."],
  ["Traffic Signal", "signal", "Signal failures and traffic-control issues."],
  ["Public Sanitation", "sparkle", "Public toilet and sanitation concerns."]
];

const iconPaths = {
  moon: '<path d="M20.5 14.7A7.8 7.8 0 0 1 9.3 3.5 8.4 8.4 0 1 0 20.5 14.7Z"/>',
  sun: '<circle cx="12" cy="12" r="3.5"/><path d="M12 2v2m0 16v2M4.9 4.9l1.4 1.4m11.4 11.4 1.4 1.4M2 12h2m16 0h2M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4"/>',
  shield: '<path d="M12 3 5 6v5c0 4.5 3 8.5 7 10 4-1.5 7-5.5 7-10V6l-7-3Z"/><path d="m9.5 12 1.7 1.7 3.7-4"/>',
  check: '<path d="m5 12 4.3 4.3L19 7"/>',
  arrow: '<path d="M5 12h14m-5-5 5 5-5 5"/>',
  login: '<path d="M10 17l5-5-5-5"/><path d="M15 12H3m11-9h5a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-5"/>',
  user: '<circle cx="12" cy="8" r="3.5"/><path d="M4.5 20a7.5 7.5 0 0 1 15 0"/>',
  users: '<path d="M16 20a4 4 0 0 0-8 0"/><circle cx="12" cy="10" r="3"/><path d="M20 19a3 3 0 0 0-3-3m-10 0a3 3 0 0 0-3 3"/>',
  plus: '<path d="M12 5v14m-7-7h14"/>',
  search: '<circle cx="11" cy="11" r="6"/><path d="m16 16 4 4"/>',
  history: '<path d="M3 12a9 9 0 1 0 3-6.7"/><path d="M3 4v5h5m4-3v6l4 2"/>',
  edit: '<path d="M14 4 20 10 8 22H2v-6L14 4Z"/><path d="m12 6 6 6"/>',
  file: '<path d="M6 3h8l4 4v14H6z"/><path d="M14 3v5h5M9 13h6m-6 4h6"/>',
  road: '<path d="M8 3 5 21m11-18 3 18M12 5v4m0 4v4m0 4v-1"/>',
  pothole: '<path d="M3 16c3-3 5-3 8-1 3 2 5 2 10-1"/><path d="M4 11h3m10-2h3m-7 1h1"/>',
  trash: '<path d="M5 7h14m-9-3h4l1 3m2 0-1 14H8L7 7m4 4v6m3-6v6"/>',
  drop: '<path d="M12 3s6 6 6 11a6 6 0 0 1-12 0c0-5 6-11 6-11Z"/><path d="M9 15a3 3 0 0 0 3 2"/>',
  drain: '<path d="M3 6h18M5 10h14M7 14h10M9 18h6"/>',
  bulb: '<path d="M9 18h6m-5 3h4m3-10a5 5 0 1 0-10 0c0 2 1 3 2 4v1h6v-1c1-1 2-2 2-4Z"/>',
  signal: '<rect x="7" y="2" width="10" height="19" rx="3"/><circle cx="12" cy="7" r="1.7"/><circle cx="12" cy="12" r="1.7"/><circle cx="12" cy="17" r="1.7"/>',
  sparkle: '<path d="m12 3 1.8 5.2L19 10l-5.2 1.8L12 17l-1.8-5.2L5 10l5.2-1.8L12 3Zm7 14 .7 2.3L22 20l-2.3.7L19 23l-.7-2.3L16 20l2.3-.7L19 17ZM5 17l.7 2.3L8 20l-2.3.7L5 23l-.7-2.3L2 20l2.3-.7L5 17Z"/>',
  map: '<path d="M12 21s7-5.2 7-11a7 7 0 1 0-14 0c0 5.8 7 11 7 11Z"/><circle cx="12" cy="10" r="2"/>',
  phone: '<path d="M6.5 3h3l1.4 4-2 1.4a15 15 0 0 0 6.7 6.7l1.4-2 4 1.4v3c0 1.6-1.4 2.8-3 2.5C10.9 19 5 13.1 4 6c-.3-1.6.9-3 2.5-3Z"/>',
  mail: '<rect x="3" y="5" width="18" height="14" rx="2"/><path d="m4 7 8 6 8-6"/>',
  clock: '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
  pending: '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
  assigned: '<path d="M4 20v-2a4 4 0 0 1 4-4h4m4-10v6m-3-3h6"/><circle cx="10" cy="7" r="3"/>',
  progress: '<path d="M12 3a9 9 0 1 0 9 9"/><path d="M12 7v5l3 2m1-10 3 1-1 3"/>',
  resolved: '<circle cx="12" cy="12" r="9"/><path d="m8 12 2.5 2.5L16 9"/>',
  chart: '<path d="M4 20V10m5 10V4m5 16v-7m5 7V7"/>',
  download: '<path d="M12 3v12m-4-4 4 4 4-4"/><path d="M4 17v3h16v-3"/>',
  pdf: '<path d="M6 3h8l4 4v14H6z"/><path d="M14 3v5h5M9 13h6m-6 4h4"/>',
  filter: '<path d="M4 5h16l-6 7v6l-4 2v-8Z"/>',
  close: '<path d="m6 6 12 12M18 6 6 18"/>',
  building: '<path d="M4 21h16M6 21V8h5v13m2 0V3h5v18M8 11h1m-1 3h1m6-7h1m-1 4h1m-1 4h1"/>',
  lock: '<rect x="5" y="10" width="14" height="11" rx="2"/><path d="M8 10V7a4 4 0 0 1 8 0v3m-4 4v3"/>',
  logout: '<path d="M14 8V4H4v16h10v-4m-3-4h10m-3-3 3 3-3 3"/>',
  menu: '<path d="M4 6h16M4 12h16M4 18h16"/>',
  database: '<ellipse cx="12" cy="5" rx="8" ry="3"/><path d="M4 5v7c0 1.7 3.6 3 8 3s8-1.3 8-3V5m-16 7v7c0 1.7 3.6 3 8 3s8-1.3 8-3v-7"/>'
};

const state = {
  citizen: JSON.parse(localStorage.getItem("smartcity_citizen") || "null"),
  citizenToken: localStorage.getItem("smartcity_citizen_token") || "",
  admin: JSON.parse(localStorage.getItem("smartcity_admin") || "null"),
  adminToken: localStorage.getItem("smartcity_admin_token") || ""
};

const main = document.querySelector("#main-content");
const nav = document.querySelector("#site-nav");
const modalRoot = document.querySelector("#modal-root");
const toastRegion = document.querySelector("#toast-region");

function icon(name, className = "") {
  return `<svg class="${className}" aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round">${iconPaths[name] || iconPaths.file}</svg>`;
}

function esc(value = "") {
  return String(value)
    .replaceAll("&", "&amp;")
    .replaceAll("<", "&lt;")
    .replaceAll(">", "&gt;")
    .replaceAll('"', "&quot;")
    .replaceAll("'", "&#039;");
}

function formatDate(value, withTime = false) {
  if (!value) return "Not available";
  return new Date(value).toLocaleDateString("en-IN", withTime
    ? { day: "2-digit", month: "short", year: "numeric", hour: "2-digit", minute: "2-digit" }
    : { day: "2-digit", month: "short", year: "numeric" });
}

function statusClass(status) {
  return `status-${String(status).toLowerCase().replaceAll(" ", "-")}`;
}

function initials(name = "") {
  return name.split(" ").slice(0, 2).map(part => part[0]).join("").toUpperCase();
}

function routeName() {
  return (location.hash || "#home").slice(1).split("?")[0];
}

function toast(message, type = "") {
  const item = document.createElement("div");
  item.className = `toast ${type}`;
  item.textContent = message;
  toastRegion.append(item);
  setTimeout(() => item.remove(), 3600);
}

async function api(path, options = {}, role = "") {
  const headers = { "Content-Type": "application/json", ...(options.headers || {}) };
  const token = role === "admin" ? state.adminToken : role === "citizen" ? state.citizenToken : "";
  if (token) headers.Authorization = `Bearer ${token}`;
  const response = await fetch(path, { ...options, headers });
  const type = response.headers.get("content-type") || "";
  const payload = type.includes("application/json") ? await response.json() : await response.text();
  if (!response.ok) throw new Error(payload.error || "The request could not be completed.");
  return payload;
}

function setCitizen(payload) {
  state.citizen = payload.user;
  state.citizenToken = payload.token;
  localStorage.setItem("smartcity_citizen", JSON.stringify(payload.user));
  localStorage.setItem("smartcity_citizen_token", payload.token);
  state.admin = null;
  state.adminToken = "";
  localStorage.removeItem("smartcity_admin");
  localStorage.removeItem("smartcity_admin_token");
}

function setAdmin(payload) {
  state.admin = payload.admin;
  state.adminToken = payload.token;
  localStorage.setItem("smartcity_admin", JSON.stringify(payload.admin));
  localStorage.setItem("smartcity_admin_token", payload.token);
  state.citizen = null;
  state.citizenToken = "";
  localStorage.removeItem("smartcity_citizen");
  localStorage.removeItem("smartcity_citizen_token");
}

function signOut() {
  state.citizen = null;
  state.citizenToken = "";
  state.admin = null;
  state.adminToken = "";
  localStorage.removeItem("smartcity_citizen");
  localStorage.removeItem("smartcity_citizen_token");
  localStorage.removeItem("smartcity_admin");
  localStorage.removeItem("smartcity_admin_token");
  toast("You have been signed out.");
  location.hash = "home";
}

function renderNav() {
  const route = routeName();
  if (state.admin) {
    nav.innerHTML = `
      <div class="site-nav-links">
        <a class="nav-link ${route === "admin-dashboard" ? "active" : ""}" href="#admin-dashboard">Dashboard</a>
        <a class="nav-link ${route === "admin-complaints" ? "active" : ""}" href="#admin-complaints">Manage Complaints</a>
        <a class="nav-link ${route === "admin-reports" ? "active" : ""}" href="#admin-reports">Reports</a>
      </div>
      <div class="site-nav-actions">
        <span class="admin-badge">${icon("shield")} Admin Portal</span>
        <button class="btn btn-danger-soft btn-small" data-signout type="button">${icon("logout")} Sign out</button>
      </div>`;
  } else if (state.citizen) {
    nav.innerHTML = `
      <div class="site-nav-links">
        <a class="nav-link ${route === "dashboard" ? "active" : ""}" href="#dashboard">Dashboard</a>
        <a class="nav-link ${route === "submit" ? "active" : ""}" href="#submit">Submit Complaint</a>
        <a class="nav-link ${route === "track" ? "active" : ""}" href="#track">Track</a>
        <a class="nav-link ${route === "history" ? "active" : ""}" href="#history">History</a>
      </div>
      <div class="site-nav-actions">
        <a class="profile-menu" href="#profile">
          <span class="avatar">${esc(initials(state.citizen.name))}</span>
          <span>${esc(state.citizen.name)}</span>
        </a>
        <button class="btn btn-danger-soft btn-small" data-signout type="button">${icon("logout")} Sign out</button>
      </div>`;
  } else {
    nav.innerHTML = `
      <div class="site-nav-links">
        <a class="nav-link ${route === "home" ? "active" : ""}" href="#home">Home</a>
        <a class="nav-link" href="#home-categories">Categories</a>
        <a class="nav-link" href="#track">Track Complaint</a>
        <a class="nav-link" href="#admin-login">Admin</a>
      </div>
      <div class="site-nav-actions">
        <a class="btn btn-ghost btn-small" href="#login">${icon("login")} Login</a>
        <a class="btn btn-primary btn-small" href="#register">${icon("user")} Register</a>
      </div>`;
  }
  document.querySelectorAll("[data-signout]").forEach(button => button.addEventListener("click", signOut));
  nav.classList.remove("open");
  document.querySelector("#mobile-menu-btn").setAttribute("aria-expanded", "false");
}

function footer() {
  return `
    <footer class="site-footer">
      <div class="container footer-grid">
        <div class="footer-brand">
          <strong>Smart City Grievance Management System</strong>
          <p>A transparent digital platform connecting citizens and municipal teams to build cleaner, safer, and more responsive neighborhoods.</p>
        </div>
        <div class="footer-col">
          <h3>Quick links</h3>
          <a href="#home">Home</a><a href="#submit">Submit complaint</a><a href="#track">Track complaint</a>
        </div>
        <div class="footer-col">
          <h3>Citizen services</h3>
          <a href="#login">Citizen login</a><a href="#register">New registration</a><a href="#admin-login">Admin portal</a>
        </div>
        <div class="footer-col">
          <h3>Support</h3>
          <a href="tel:18001234567">1800-123-4567</a><a href="mailto:help@smartcity.gov">help@smartcity.gov</a><a href="#home-contact">Contact centre</a>
        </div>
      </div>
      <div class="container footer-bottom">
        <span>© 2026 Smart City Municipal Corporation. All rights reserved.</span>
        <span>Accessibility | Privacy Policy | Terms of Service</span>
      </div>
    </footer>`;
}

function homePage() {
  main.innerHTML = `
    <section class="hero">
      <img class="hero-bg" src="/assets/smart-city-hero.png" alt="">
      <div class="container hero-inner">
        <div class="hero-copy">
          <span class="eyebrow">${icon("shield")} Citizen-first municipal services</span>
          <h1>Building a better city, <span>together.</span></h1>
          <p class="hero-description">Report civic issues in minutes, follow progress transparently, and help municipal teams deliver faster resolutions across your neighborhood.</p>
          <div class="hero-actions">
            <a class="btn btn-primary" href="${state.citizen ? "#submit" : "#register"}">${icon("plus")} Submit a complaint</a>
            <a class="btn btn-green" href="#track">${icon("search")} Track complaint</a>
          </div>
          <div class="hero-trust">
            <span>${icon("check")} Quick registration</span>
            <span>${icon("check")} Real-time tracking</span>
            <span>${icon("check")} Transparent resolution</span>
          </div>
        </div>
      </div>
    </section>
    <section class="impact-strip" aria-label="Service statistics">
      <div class="container impact-grid" id="home-stats">
        ${impactItem("file", "12,480+", "Complaints received", "")}
        ${impactItem("resolved", "10,892+", "Issues resolved", "green")}
        ${impactItem("users", "8,424+", "Active citizens", "amber")}
        ${impactItem("building", "24", "Wards covered", "purple")}
      </div>
    </section>
    <section class="section section-white">
      <div class="container">
        <div class="section-heading">
          <span class="section-kicker">Connected citizen services</span>
          <h2>One platform for a more responsive city</h2>
          <p>Simple reporting for residents, actionable insights for teams, and visible progress for everyone.</p>
        </div>
        <div class="feature-grid">
          ${feature("plus", "Report in minutes", "Share the issue, pinpoint the location, add an image, and receive a unique complaint ID instantly.")}
          ${feature("progress", "Track every update", "Follow your complaint through assignment, field work, and resolution with clear status updates.")}
          ${feature("shield", "Accountable action", "Municipal teams get the information they need to prioritize work and close the loop transparently.")}
        </div>
      </div>
    </section>
    <section class="section section-blue" id="home-categories">
      <div class="container">
        <div class="section-heading">
          <span class="section-kicker">Complaint categories</span>
          <h2>Tell us what needs attention</h2>
          <p>Select the most relevant category when you report an issue so the right civic team can respond quickly.</p>
        </div>
        <div class="category-grid">
          ${categories.map(([title, iconName, description], index) => `
            <a class="category-card" href="${state.citizen ? `#submit?category=${encodeURIComponent(title)}` : "#login"}">
              <span class="category-icon ${index % 3 === 1 ? "green" : ""}">${icon(iconName)}</span>
              <h3>${title}</h3><p>${description}</p>
            </a>`).join("")}
        </div>
      </div>
    </section>
    <section class="section section-white">
      <div class="container">
        <div class="section-heading">
          <span class="section-kicker">Simple process</span>
          <h2>How it works</h2>
          <p>From report to resolution, your civic request stays visible and easy to follow.</p>
        </div>
        <div class="steps-grid">
          ${step("01", "Submit your complaint", "Choose a category, explain the issue, and add the exact location to create your request.")}
          ${step("02", "Municipal team takes action", "The concerned department reviews, assigns, and works on your complaint based on priority.")}
          ${step("03", "Get status updates", "Use your complaint ID to see progress and read the municipal team's resolution notes.")}
        </div>
      </div>
    </section>
    <section class="cta-band">
      <div class="container cta-inner">
        <div><h2>See an issue? Help your city act faster.</h2><p>Your report gives municipal teams the context they need to make neighborhoods cleaner, safer, and easier to move through.</p></div>
        <a class="btn" href="${state.citizen ? "#submit" : "#register"}">${icon("arrow")} Report an issue</a>
      </div>
    </section>
    <section class="section section-blue" id="home-contact">
      <div class="container">
        <div class="section-heading">
          <span class="section-kicker">Need assistance?</span>
          <h2>Contact the citizen support centre</h2>
          <p>Our helpdesk is available for urgent civic matters and assistance with online submissions.</p>
        </div>
        <div class="contact-grid">
          ${contact("phone", "Citizen helpline", "1800-123-4567 · Available 24x7")}
          ${contact("mail", "Email support", "help@smartcity.gov")}
          ${contact("map", "Municipal office", "Civic Centre, Main Avenue · Mon-Sat")}
        </div>
      </div>
    </section>
    ${footer()}`;
  loadHomeStats();
}

function impactItem(iconName, value, label, color) {
  return `<div class="impact-item"><span class="impact-icon ${color}">${icon(iconName)}</span><div><strong class="impact-value">${value}</strong><span class="impact-label">${label}</span></div></div>`;
}

function feature(iconName, title, text) {
  return `<article class="feature-card"><span class="feature-icon">${icon(iconName)}</span><h3>${title}</h3><p>${text}</p></article>`;
}

function step(number, title, text) {
  return `<article class="step-card"><span class="step-number">${number}</span><h3>${title}</h3><p>${text}</p></article>`;
}

function contact(iconName, title, text) {
  return `<article class="contact-card"><span class="contact-icon">${icon(iconName)}</span><div><strong>${title}</strong><span>${text}</span></div></article>`;
}

async function loadHomeStats() {
  try {
    const stats = await api("/api/stats");
    document.querySelector("#home-stats").innerHTML = `
      ${impactItem("file", `${stats.totalComplaints.toLocaleString("en-IN")}+`, "Complaints received", "")}
      ${impactItem("resolved", `${stats.resolved.toLocaleString("en-IN")}+`, "Issues resolved", "green")}
      ${impactItem("users", `${stats.activeCitizens.toLocaleString("en-IN")}+`, "Active citizens", "amber")}
      ${impactItem("building", stats.wardsCovered, "Wards covered", "purple")}`;
  } catch {
    // Keep the friendly fallback figures rendered in the initial page.
  }
}

function authIntro(kind) {
  const admin = kind === "admin";
  return `
    <section class="auth-intro">
      <div>
        <span class="section-kicker">${admin ? "Authorized personnel only" : "Citizen services portal"}</span>
        <h1>${admin ? "Municipal operations, clearly managed." : "Your city is listening."}</h1>
        <p>${admin ? "Sign in to review, assign, update, and resolve civic grievances across every ward." : "Create an account or sign in to report issues and follow each request from submission to resolution."}</p>
        <div class="auth-list">
          <span>${icon("check")} Secure and accessible service</span>
          <span>${icon("check")} Transparent complaint tracking</span>
          <span>${icon("check")} Faster municipal response</span>
        </div>
      </div>
      <div class="auth-tip"><strong>${admin ? "Admin demo:" : "Citizen demo:"}</strong><br>${admin ? "admin@smartcity.gov / Admin@123" : "citizen@example.com / Citizen@123"}</div>
    </section>`;
}

function loginPage(admin = false) {
  main.innerHTML = `
    <section class="auth-shell">
      <div class="auth-layout">
        ${authIntro(admin ? "admin" : "citizen")}
        <div class="auth-card">
          <h2>${admin ? "Administrator login" : "Welcome back"}</h2>
          <p>${admin ? "Use your authorized municipal credentials to continue." : "Sign in to manage and track your civic complaints."}</p>
          <form class="form-grid" id="${admin ? "admin-login-form" : "login-form"}">
            <div class="form-group">
              <label for="login-email">${admin ? "Admin username" : "Email address"}</label>
              <input class="input" id="login-email" name="${admin ? "username" : "email"}" type="${admin ? "text" : "email"}" placeholder="${admin ? "admin@smartcity.gov" : "you@example.com"}" required autocomplete="username">
            </div>
            <div class="form-group">
              <label for="login-password">Password</label>
              <input class="input" id="login-password" name="password" type="password" placeholder="Enter your password" required autocomplete="current-password">
            </div>
            <div class="form-row">
              <label class="checkbox"><input type="checkbox" name="remember"> Remember me</label>
              <a href="#forgot-password">Forgot password?</a>
            </div>
            <button class="btn btn-primary btn-wide" type="submit">${icon(admin ? "lock" : "login")} ${admin ? "Secure admin login" : "Login to your account"}</button>
          </form>
          <div class="auth-footer">
            ${admin ? `<a href="#login">Return to citizen login</a>` : `New to the portal? <a href="#register">Create an account</a>`}
          </div>
        </div>
      </div>
    </section>`;
  document.querySelector("form").addEventListener("submit", async event => {
    event.preventDefault();
    const button = event.currentTarget.querySelector("button[type=submit]");
    button.disabled = true;
    try {
      const body = Object.fromEntries(new FormData(event.currentTarget));
      const result = await api(admin ? "/api/auth/admin" : "/api/auth/login", { method: "POST", body: JSON.stringify(body) });
      admin ? setAdmin(result) : setCitizen(result);
      toast(`Welcome back, ${admin ? result.admin.name : result.user.name}.`);
      location.hash = admin ? "admin-dashboard" : "dashboard";
    } catch (error) {
      toast(error.message, "error");
    } finally {
      button.disabled = false;
    }
  });
}

function registerPage() {
  main.innerHTML = `
    <section class="auth-shell">
      <div class="auth-layout">
        ${authIntro("citizen")}
        <div class="auth-card">
          <h2>Create citizen account</h2>
          <p>Register once to report and track civic issues across the city.</p>
          <form class="form-grid two-col" id="register-form">
            <div class="form-group full"><label for="reg-name">Full name</label><input class="input" id="reg-name" name="name" required autocomplete="name" placeholder="Enter your full name"></div>
            <div class="form-group full"><label for="reg-email">Email address</label><input class="input" id="reg-email" name="email" type="email" required autocomplete="email" placeholder="you@example.com"></div>
            <div class="form-group full"><label for="reg-mobile">Mobile number</label><input class="input" id="reg-mobile" name="mobile" inputmode="numeric" pattern="[0-9]{10}" required autocomplete="tel" placeholder="10-digit mobile number"></div>
            <div class="form-group"><label for="reg-password">Password</label><input class="input" id="reg-password" name="password" type="password" required autocomplete="new-password" placeholder="Create password"><span class="form-help">8+ characters with uppercase, lowercase, and number.</span></div>
            <div class="form-group"><label for="reg-confirm">Confirm password</label><input class="input" id="reg-confirm" name="confirmPassword" type="password" required autocomplete="new-password" placeholder="Repeat password"></div>
            <div class="form-group full"><button class="btn btn-primary btn-wide" type="submit">${icon("user")} Register account</button></div>
          </form>
          <div class="auth-footer">Already registered? <a href="#login">Sign in here</a></div>
        </div>
      </div>
    </section>`;
  document.querySelector("#register-form").addEventListener("submit", async event => {
    event.preventDefault();
    const form = event.currentTarget;
    const body = Object.fromEntries(new FormData(form));
    if (body.password !== body.confirmPassword) return toast("Passwords do not match.", "error");
    if (!/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).{8,}$/.test(body.password)) return toast("Please use a stronger password.", "error");
    try {
      await api("/api/auth/register", { method: "POST", body: JSON.stringify(body) });
      toast("Registration successful. Please sign in.");
      location.hash = "login";
    } catch (error) {
      toast(error.message, "error");
    }
  });
}

function forgotPasswordPage() {
  main.innerHTML = `
    <section class="auth-shell">
      <div class="auth-layout">
        ${authIntro("citizen")}
        <div class="auth-card">
          <h2>Reset your password</h2>
          <p>Enter your registered email. We will send password reset instructions if an account is found.</p>
          <form class="form-grid" id="forgot-form">
            <div class="form-group"><label for="forgot-email">Email address</label><input class="input" id="forgot-email" name="email" type="email" required placeholder="you@example.com"></div>
            <button class="btn btn-primary btn-wide" type="submit">${icon("mail")} Send reset instructions</button>
          </form>
          <div class="auth-footer"><a href="#login">Return to login</a></div>
        </div>
      </div>
    </section>`;
  document.querySelector("#forgot-form").addEventListener("submit", event => {
    event.preventDefault();
    toast("If the email is registered, reset instructions have been sent.");
    location.hash = "login";
  });
}

function requireCitizen() {
  if (state.citizen && state.citizenToken) return true;
  toast("Please sign in to access citizen services.", "error");
  location.hash = "login";
  return false;
}

function requireAdmin() {
  if (state.admin && state.adminToken) return true;
  toast("Please sign in with administrator credentials.", "error");
  location.hash = "admin-login";
  return false;
}

function pageHeader(kicker, title, description, action = "") {
  return `<header class="page-heading"><div><span class="section-kicker">${kicker}</span><h1>${title}</h1><p>${description}</p></div>${action}</header>`;
}

function statCard(iconName, value, label, color = "") {
  return `<article class="stat-card"><span class="stat-icon ${color}">${icon(iconName)}</span><div><strong class="stat-value">${value}</strong><span class="stat-label">${label}</span></div></article>`;
}

async function dashboardPage() {
  if (!requireCitizen()) return;
  main.innerHTML = `<section class="page-shell"><div class="container">${pageHeader("Citizen dashboard", `Good day, ${esc(state.citizen.name.split(" ")[0])}`, "View your civic complaints, follow updates, and submit a new request whenever your neighborhood needs attention.", `<a class="btn btn-primary" href="#submit">${icon("plus")} New complaint</a>`)}<div class="panel"><div class="empty-state">${icon("clock")}<h3>Loading dashboard</h3><p>Fetching your latest complaint information.</p></div></div></div></section>`;
  try {
    const complaints = await api("/api/my/complaints", {}, "citizen");
    const counts = {
      pending: complaints.filter(item => item.status === "Pending").length,
      progress: complaints.filter(item => ["Assigned", "In Progress"].includes(item.status)).length,
      resolved: complaints.filter(item => item.status === "Resolved").length
    };
    main.innerHTML = `
      <section class="page-shell"><div class="container">
        ${pageHeader("Citizen dashboard", `Good day, ${esc(state.citizen.name.split(" ")[0])}`, "View your civic complaints, follow updates, and submit a new request whenever your neighborhood needs attention.", `<a class="btn btn-primary" href="#submit">${icon("plus")} New complaint</a>`)}
        <div class="dashboard-grid">
          ${statCard("file", complaints.length, "Total complaints")}
          ${statCard("pending", counts.pending, "Pending", "amber")}
          ${statCard("progress", counts.progress, "In progress", "purple")}
          ${statCard("resolved", counts.resolved, "Resolved", "green")}
        </div>
        <div class="dashboard-content">
          <section class="panel">
            <div class="panel-header"><div><h2>Recent complaints</h2><p>Your latest submitted grievances and current progress.</p></div><a class="btn btn-soft btn-small" href="#history">View all</a></div>
            ${complaintTable(complaints.slice(0, 5), false)}
          </section>
          <aside class="panel">
            <div class="panel-header"><div><h2>Quick actions</h2><p>Get to citizen services quickly.</p></div></div>
            <div class="quick-grid">
              ${quick("plus", "Submit complaint", "Report a new issue", "#submit")}
              ${quick("search", "Track complaint", "Search using your ID", "#track")}
              ${quick("history", "Complaint history", "View all requests", "#history")}
              ${quick("edit", "Edit profile", "Update your details", "#profile")}
            </div>
          </aside>
        </div>
      </div></section>`;
    bindTrackButtons();
  } catch (error) {
    toast(error.message, "error");
    signOut();
  }
}

function bindTrackButtons() {
  document.querySelectorAll("[data-track]").forEach(button => button.addEventListener("click", () => {
    location.hash = `track?id=${encodeURIComponent(button.dataset.track)}`;
  }));
}

function quick(iconName, title, text, href) {
  return `<a class="quick-card" href="${href}"><span class="quick-icon">${icon(iconName)}</span><strong>${title}</strong><span>${text}</span></a>`;
}

function complaintTable(items, admin = false) {
  if (!items.length) return `<div class="empty-state">${icon("file")}<h3>No complaints to show</h3><p>Your complaint list will appear here after submission.</p></div>`;
  return `<div class="table-wrap"><table class="data-table">
    <thead><tr><th>Complaint ID</th>${admin ? "<th>Citizen</th>" : ""}<th>Category</th><th>Date</th><th>Status</th><th>Action</th></tr></thead>
    <tbody>${items.map(item => `<tr>
      <td><strong>${esc(item.complaint_id)}</strong></td>
      ${admin ? `<td>${esc(item.citizen_name)}</td>` : ""}
      <td>${esc(item.category)}</td><td>${formatDate(item.created_at)}</td>
      <td><span class="status ${statusClass(item.status)}">${esc(item.status)}</span></td>
      <td><button class="btn btn-soft btn-small" type="button" data-${admin ? "manage" : "track"}="${esc(item.complaint_id)}">${admin ? "Manage" : "View details"}</button></td>
    </tr>`).join("")}</tbody>
  </table></div>`;
}

function submitPage() {
  if (!requireCitizen()) return;
  const params = new URLSearchParams((location.hash.split("?")[1] || ""));
  const selectedCategory = params.get("category") || "";
  main.innerHTML = `
    <section class="page-shell"><div class="container">
      ${pageHeader("Citizen services", "Submit a new complaint", "Describe the civic issue clearly so the municipal team can route and resolve it efficiently.")}
      <section class="panel">
        <form class="form-grid two-col" id="complaint-form">
          <div class="form-group full"><label for="complaint-title">Complaint title</label><input class="input" id="complaint-title" name="title" required maxlength="180" placeholder="Give the issue a short, clear title"></div>
          <div class="form-group"><label for="complaint-category">Complaint category</label><select class="select" id="complaint-category" name="category" required><option value="">Select category</option>${[...categories.map(item => item[0]), "Other"].map(item => `<option ${selectedCategory === item ? "selected" : ""}>${item}</option>`).join("")}</select></div>
          <div class="form-group"><label for="complaint-priority">Priority level</label><select class="select" id="complaint-priority" name="priority" required><option>Low</option><option selected>Medium</option><option>High</option><option>Urgent</option></select></div>
          <div class="form-group full"><label for="complaint-description">Description</label><textarea class="textarea" id="complaint-description" name="description" required maxlength="1200" placeholder="Explain what you observed and include any useful landmarks or safety concerns"></textarea></div>
          <div class="form-group full"><label for="complaint-location">Location</label><input class="input" id="complaint-location" name="location" required maxlength="255" placeholder="Street, landmark, and ward if known"></div>
          <div class="form-group full"><label for="complaint-image">Upload image <span class="form-help">(optional)</span></label><input class="input" id="complaint-image" name="image" type="file" accept="image/png,image/jpeg,image/webp"><span class="form-help">Upload a clear image to help the field team identify the issue. Max recommended size: 5 MB.</span></div>
          <div class="form-group full"><button class="btn btn-primary" type="submit">${icon("plus")} Submit complaint</button></div>
        </form>
      </section>
    </div></section>`;
  document.querySelector("#complaint-form").addEventListener("submit", async event => {
    event.preventDefault();
    const form = event.currentTarget;
    const body = Object.fromEntries(new FormData(form));
    body.image_path = form.image.files[0] ? form.image.files[0].name : "";
    delete body.image;
    try {
      const result = await api("/api/complaints", { method: "POST", body: JSON.stringify(body) }, "citizen");
      localStorage.setItem("smartcity_last_complaint", result.complaint.complaint_id);
      toast(`Complaint submitted: ${result.complaint.complaint_id}`);
      location.hash = `track?id=${encodeURIComponent(result.complaint.complaint_id)}`;
    } catch (error) {
      toast(error.message, "error");
    }
  });
}

function trackPage() {
  const params = new URLSearchParams((location.hash.split("?")[1] || ""));
  const initialId = params.get("id") || localStorage.getItem("smartcity_last_complaint") || "";
  main.innerHTML = `
    <section class="page-shell"><div class="container">
      ${pageHeader("Transparent progress", "Track your complaint", "Enter your complaint ID to see the assigned team, current status, and resolution notes.")}
      <section class="panel">
        <form class="tracking-search" id="track-form">
          <label class="sr-only" for="track-id">Complaint ID</label>
          <input class="input" id="track-id" name="id" value="${esc(initialId)}" required placeholder="Enter complaint ID, for example SCM-2026-0002">
          <button class="btn btn-primary" type="submit">${icon("search")} Track complaint</button>
        </form>
        <div id="track-result"></div>
      </section>
    </div></section>`;
  const form = document.querySelector("#track-form");
  form.addEventListener("submit", async event => {
    event.preventDefault();
    await loadTrackedComplaint(form.id.value.trim());
  });
  if (initialId) loadTrackedComplaint(initialId);
}

async function loadTrackedComplaint(id) {
  const result = document.querySelector("#track-result");
  result.innerHTML = `<div class="empty-state">${icon("clock")}<h3>Checking complaint status</h3><p>Looking up ${esc(id)}.</p></div>`;
  try {
    const complaint = await api(`/api/complaints/${encodeURIComponent(id)}`);
    localStorage.setItem("smartcity_last_complaint", complaint.complaint_id);
    result.innerHTML = complaintDetail(complaint);
  } catch (error) {
    result.innerHTML = `<div class="empty-state">${icon("search")}<h3>Complaint not found</h3><p>${esc(error.message)} Please check the ID and try again.</p></div>`;
  }
}

function complaintDetail(item) {
  const steps = ["Pending", "Assigned", "In Progress", "Resolved"];
  const current = steps.indexOf(item.status);
  return `<article class="panel detail-card">
    <div class="detail-top"><div><span class="complaint-id">${esc(item.complaint_id)}</span><h2>${esc(item.title)}</h2></div><span class="status ${statusClass(item.status)}">${esc(item.status)}</span></div>
    <div class="progress-track">${steps.map((stepName, index) => `<span class="progress-step ${index < current ? "complete" : index === current ? "active" : ""}">${stepName}</span>`).join("")}</div>
    <div class="detail-grid">
      <div class="detail-item"><strong>Category</strong><span>${esc(item.category)}</span></div>
      <div class="detail-item"><strong>Priority</strong><span class="priority priority-${item.priority.toLowerCase()}">${esc(item.priority)}</span></div>
      <div class="detail-item"><strong>Submitted date</strong><span>${formatDate(item.created_at, true)}</span></div>
      <div class="detail-item"><strong>Assigned team</strong><span>${esc(item.assigned_to || "Unassigned")}</span></div>
    </div>
    <div class="detail-description"><h3>Issue description</h3><p>${esc(item.description)}</p></div>
    <div class="detail-description"><h3>Location</h3><p>${esc(item.location)}</p></div>
    <div class="detail-description"><h3>Resolution notes</h3><p>${esc(item.admin_remarks || "Municipal remarks will appear here as the assigned team reviews your complaint.")}</p></div>
  </article>`;
}

async function historyPage() {
  if (!requireCitizen()) return;
  main.innerHTML = `<section class="page-shell"><div class="container">${pageHeader("Citizen services", "Complaint history", "Review all your past and active civic grievances in one place.", `<a class="btn btn-primary" href="#submit">${icon("plus")} New complaint</a>`)}<section class="panel"><div class="empty-state">${icon("clock")}<h3>Loading complaint history</h3></div></section></div></section>`;
  try {
    const complaints = await api("/api/my/complaints", {}, "citizen");
    main.innerHTML = `<section class="page-shell"><div class="container">${pageHeader("Citizen services", "Complaint history", "Review all your past and active civic grievances in one place.", `<a class="btn btn-primary" href="#submit">${icon("plus")} New complaint</a>`)}<section class="panel"><div class="panel-header"><div><h2>All complaints</h2><p>${complaints.length} grievance${complaints.length === 1 ? "" : "s"} submitted from your account.</p></div></div>${complaintTable(complaints, false)}</section></div></section>`;
    bindTrackButtons();
  } catch (error) {
    toast(error.message, "error");
  }
}

function profilePage() {
  if (!requireCitizen()) return;
  main.innerHTML = `
    <section class="page-shell"><div class="container">
      ${pageHeader("Citizen account", "Edit profile", "Keep your contact details up to date so municipal teams can reach you when more information is needed.")}
      <section class="panel">
        <form class="form-grid two-col" id="profile-form">
          <div class="form-group full"><label for="profile-name">Full name</label><input class="input" id="profile-name" name="name" required value="${esc(state.citizen.name)}"></div>
          <div class="form-group"><label for="profile-email">Email address</label><input class="input" id="profile-email" name="email" type="email" required value="${esc(state.citizen.email)}"></div>
          <div class="form-group"><label for="profile-mobile">Mobile number</label><input class="input" id="profile-mobile" name="mobile" required pattern="[0-9]{10}" value="${esc(state.citizen.mobile)}"></div>
          <div class="form-group full"><button class="btn btn-primary" type="submit">${icon("edit")} Save profile changes</button></div>
        </form>
      </section>
    </div></section>`;
  document.querySelector("#profile-form").addEventListener("submit", async event => {
    event.preventDefault();
    try {
      const body = Object.fromEntries(new FormData(event.currentTarget));
      const result = await api("/api/my/profile", { method: "PATCH", body: JSON.stringify(body) }, "citizen");
      state.citizen = result.user;
      localStorage.setItem("smartcity_citizen", JSON.stringify(result.user));
      toast("Profile updated successfully.");
      renderNav();
    } catch (error) {
      toast(error.message, "error");
    }
  });
}

function barChart(data) {
  const max = Math.max(1, ...data.map(item => item.value));
  return `<div class="bars">${data.map(item => `<div class="bar-row"><span class="bar-label">${esc(item.label)}</span><span class="bar-track"><span class="bar-fill" style="width:${Math.max(6, item.value / max * 100)}%"></span></span><span class="bar-value">${item.value}</span></div>`).join("")}</div>`;
}

function lineChart(data) {
  const width = 540;
  const height = 175;
  const max = Math.max(1, ...data.map(item => item.value));
  const points = data.map((item, index) => {
    const x = 34 + index * ((width - 68) / Math.max(1, data.length - 1));
    const y = height - 36 - (item.value / max) * 105;
    return { ...item, x, y };
  });
  return `<svg class="line-chart" viewBox="0 0 ${width} ${height}" role="img" aria-label="Monthly complaint trend chart">
    <path d="M34 139H506" stroke="var(--line)" stroke-width="1"/>
    <path d="${points.map((item, index) => `${index ? "L" : "M"}${item.x} ${item.y}`).join(" ")}" fill="none" stroke="var(--primary)" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/>
    ${points.map(item => `<circle cx="${item.x}" cy="${item.y}" r="4" fill="var(--surface)" stroke="var(--primary)" stroke-width="3"/><text x="${item.x}" y="160" text-anchor="middle">${esc(item.label)}</text><text x="${item.x}" y="${item.y - 12}" text-anchor="middle">${item.value}</text>`).join("")}
  </svg>`;
}

function donutChart(data) {
  const colors = ["#ea9c22", "#805ac7", "#1682d5", "#0b9d6e"];
  const total = Math.max(1, data.reduce((sum, item) => sum + item.value, 0));
  let progress = 0;
  const stops = data.map((item, index) => {
    const start = progress / total * 360;
    progress += item.value;
    return `${colors[index]} ${start}deg ${progress / total * 360}deg`;
  }).join(",");
  return `<div class="donut-wrap"><div class="donut" data-total="${total}" style="background:conic-gradient(${stops})"></div><div class="chart-legend">${data.map((item, index) => `<span class="legend-item"><span><i class="legend-dot" style="background:${colors[index]}"></i>${esc(item.label)}</span><strong>${item.value}</strong></span>`).join("")}</div></div>`;
}

async function adminDashboardPage() {
  if (!requireAdmin()) return;
  main.innerHTML = `<section class="page-shell"><div class="container">${pageHeader("Municipal control centre", "Administrator dashboard", "Monitor civic issues, understand complaint patterns, and keep resolution work moving.", `<a class="btn btn-primary" href="#admin-complaints">${icon("file")} Manage complaints</a>`)}<section class="panel"><div class="empty-state">${icon("clock")}<h3>Loading municipal analytics</h3></div></section></div></section>`;
  try {
    const [stats, complaints] = await Promise.all([
      api("/api/admin/dashboard", {}, "admin"),
      api("/api/admin/complaints", {}, "admin")
    ]);
    main.innerHTML = `
      <section class="page-shell"><div class="container">
        ${pageHeader("Municipal control centre", "Administrator dashboard", "Monitor civic issues, understand complaint patterns, and keep resolution work moving.", `<a class="btn btn-primary" href="#admin-complaints">${icon("file")} Manage complaints</a>`)}
        <div class="dashboard-grid">
          ${statCard("users", stats.totalUsers, "Total users")}
          ${statCard("file", stats.totalComplaints, "Total complaints", "purple")}
          ${statCard("pending", stats.pending, "Pending", "amber")}
          ${statCard("resolved", stats.resolved, "Resolved", "green")}
        </div>
        <div class="chart-grid">
          <section class="panel"><div class="panel-header"><div><h2>Monthly complaint trends</h2><p>Volume of grievance submissions over time.</p></div></div>${lineChart(stats.monthlyData)}</section>
          <section class="panel"><div class="panel-header"><div><h2>Resolution statistics</h2><p>Complaint workload by current status.</p></div></div>${donutChart(stats.statusData)}</section>
          <section class="panel"><div class="panel-header"><div><h2>Complaints by category</h2><p>Reported issues across municipal departments.</p></div></div>${barChart(stats.categoryData)}</section>
        </div>
        <section class="panel" style="margin-top:18px"><div class="panel-header"><div><h2>Recent complaints</h2><p>Newest citizen grievances requiring municipal attention.</p></div><a class="btn btn-soft btn-small" href="#admin-complaints">View all</a></div>${complaintTable(complaints.slice(0, 5), true)}</section>
      </div></section>`;
    bindManageButtons(complaints);
  } catch (error) {
    toast(error.message, "error");
    signOut();
  }
}

async function adminComplaintsPage() {
  if (!requireAdmin()) return;
  main.innerHTML = `
    <section class="page-shell"><div class="container">
      ${pageHeader("Complaint management", "Manage civic complaints", "Search, filter, assign, and resolve complaints across every municipal service team.")}
      <section class="panel">
        <form class="filters" id="admin-filters">
          <div class="search-input">${icon("search")}<input class="input" name="search" placeholder="Search ID, title, citizen, location"></div>
          <select class="select" name="status"><option value="">All statuses</option><option>Pending</option><option>Assigned</option><option>In Progress</option><option>Resolved</option></select>
          <select class="select" name="category"><option value="">All categories</option>${[...categories.map(item => item[0]), "Other"].map(item => `<option>${item}</option>`).join("")}</select>
          <button class="btn btn-primary" type="submit">${icon("filter")} Apply filters</button>
        </form>
        <div id="admin-complaint-list"><div class="empty-state">${icon("clock")}<h3>Loading complaints</h3></div></div>
      </section>
    </div></section>`;
  const form = document.querySelector("#admin-filters");
  const load = async () => {
    const query = new URLSearchParams(new FormData(form));
    try {
      const complaints = await api(`/api/admin/complaints?${query}`, {}, "admin");
      document.querySelector("#admin-complaint-list").innerHTML = `<div class="panel-header"><div><h2>Complaint registry</h2><p>${complaints.length} complaint${complaints.length === 1 ? "" : "s"} match your current view.</p></div></div>${complaintTable(complaints, true)}`;
      bindManageButtons(complaints);
    } catch (error) {
      toast(error.message, "error");
    }
  };
  form.addEventListener("submit", event => {
    event.preventDefault();
    load();
  });
  load();
}

function bindManageButtons(complaints) {
  document.querySelectorAll("[data-manage]").forEach(button => {
    button.addEventListener("click", () => openManageModal(complaints.find(item => item.complaint_id === button.dataset.manage)));
  });
}

function openManageModal(item) {
  if (!item) return;
  const teams = ["Unassigned", "Road Maintenance", "Solid Waste Management", "Water Supply", "Drainage Operations", "Electrical Services", "Traffic Engineering", "Public Health & Sanitation"];
  modalRoot.innerHTML = `
    <section class="modal" role="dialog" aria-modal="true" aria-labelledby="manage-title">
      <div class="modal-header"><div><span class="complaint-id">${esc(item.complaint_id)}</span><h2 id="manage-title">${esc(item.title)}</h2></div><button class="icon-button" type="button" data-close-modal aria-label="Close dialog">${icon("close")}</button></div>
      <div class="detail-grid">
        <div class="detail-item"><strong>Citizen</strong><span>${esc(item.citizen_name)}</span></div>
        <div class="detail-item"><strong>Category</strong><span>${esc(item.category)}</span></div>
        <div class="detail-item"><strong>Priority</strong><span class="priority priority-${item.priority.toLowerCase()}">${esc(item.priority)}</span></div>
        <div class="detail-item"><strong>Submitted</strong><span>${formatDate(item.created_at)}</span></div>
      </div>
      <div class="detail-description"><h3>Description</h3><p>${esc(item.description)}</p></div>
      <div class="detail-description"><h3>Location</h3><p>${esc(item.location)}</p></div>
      <form class="form-grid two-col" id="manage-form" style="margin-top:20px">
        <div class="form-group"><label for="manage-team">Assign municipal team</label><select class="select" id="manage-team" name="assigned_to">${teams.map(team => `<option ${team === item.assigned_to ? "selected" : ""}>${team}</option>`).join("")}</select></div>
        <div class="form-group"><label for="manage-status">Update status</label><select class="select" id="manage-status" name="status">${["Pending", "Assigned", "In Progress", "Resolved"].map(status => `<option ${status === item.status ? "selected" : ""}>${status}</option>`).join("")}</select></div>
        <div class="form-group full"><label for="manage-remarks">Municipal remarks</label><textarea class="textarea" id="manage-remarks" name="admin_remarks" placeholder="Add clear action or resolution notes for the citizen">${esc(item.admin_remarks || "")}</textarea></div>
        <div class="form-group full"><button class="btn btn-primary" type="submit">${icon("check")} Save complaint update</button></div>
      </form>
    </section>`;
  document.querySelector("[data-close-modal]").addEventListener("click", closeModal);
  document.querySelector("#manage-form").addEventListener("submit", async event => {
    event.preventDefault();
    const body = Object.fromEntries(new FormData(event.currentTarget));
    try {
      await api(`/api/admin/complaints/${encodeURIComponent(item.complaint_id)}`, { method: "PATCH", body: JSON.stringify(body) }, "admin");
      toast(`${item.complaint_id} updated successfully.`);
      closeModal();
      renderRoute();
    } catch (error) {
      toast(error.message, "error");
    }
  });
}

function closeModal() {
  modalRoot.innerHTML = "";
}

function adminReportsPage() {
  if (!requireAdmin()) return;
  const reports = [
    ["daily", "clock", "Daily report", "Complaints received during the last 24 hours."],
    ["weekly", "chart", "Weekly report", "A seven-day view of incoming civic issues."],
    ["monthly", "file", "Monthly report", "Complaints submitted over the previous 30 days."],
    ["category", "filter", "Category-wise report", "A service-category view of complaint records."]
  ];
  main.innerHTML = `
    <section class="page-shell"><div class="container">
      ${pageHeader("Reporting centre", "Municipal reports", "Generate operational complaint reports and export them for review, planning, or official records.")}
      <div class="report-grid">${reports.map(([period, iconName, title, text]) => `
        <article class="panel report-card">
          <span class="feature-icon">${icon(iconName)}</span><h3>${title}</h3><p>${text}</p>
          <div class="report-actions">
            <button class="btn btn-soft btn-small" type="button" data-report="${period}" data-format="pdf">${icon("pdf")} PDF</button>
            <button class="btn btn-soft btn-small" type="button" data-report="${period}" data-format="csv">${icon("download")} Excel</button>
          </div>
        </article>`).join("")}</div>
      <section class="panel" style="margin-top:18px"><div class="panel-header"><div><h2>Export guidance</h2><p>PDF exports are formatted for quick sharing and official records. Excel exports use CSV format for compatibility with spreadsheet applications.</p></div></div></section>
    </div></section>`;
  document.querySelectorAll("[data-report]").forEach(button => button.addEventListener("click", () => downloadReport(button.dataset.report, button.dataset.format)));
}

async function downloadReport(period, format) {
  try {
    const response = await fetch(`/api/reports?period=${encodeURIComponent(period)}&format=${encodeURIComponent(format)}`, { headers: { Authorization: `Bearer ${state.adminToken}` } });
    if (!response.ok) throw new Error("Report export could not be generated.");
    const blob = await response.blob();
    const link = document.createElement("a");
    link.href = URL.createObjectURL(blob);
    link.download = `smart-city-${period}-report.${format === "csv" ? "csv" : "pdf"}`;
    document.body.append(link);
    link.click();
    link.remove();
    URL.revokeObjectURL(link.href);
    toast(`${period[0].toUpperCase() + period.slice(1)} report downloaded.`);
  } catch (error) {
    toast(error.message, "error");
  }
}

function notFoundPage() {
  main.innerHTML = `<section class="page-shell"><div class="container"><section class="panel"><div class="empty-state">${icon("map")}<h3>Page not found</h3><p>The requested page is unavailable.</p><a class="btn btn-primary" style="margin-top:16px" href="#home">Return home</a></div></section></div></section>`;
}

function renderRoute() {
  const route = routeName();
  renderNav();
  window.scrollTo({ top: 0, behavior: "instant" });
  const routes = {
    home: homePage,
    "home-categories": () => {
      homePage();
      requestAnimationFrame(() => document.querySelector("#home-categories").scrollIntoView());
    },
    "home-contact": () => {
      homePage();
      requestAnimationFrame(() => document.querySelector("#home-contact").scrollIntoView());
    },
    login: () => loginPage(false),
    register: registerPage,
    "forgot-password": forgotPasswordPage,
    "admin-login": () => loginPage(true),
    dashboard: dashboardPage,
    submit: submitPage,
    track: trackPage,
    history: historyPage,
    profile: profilePage,
    "admin-dashboard": adminDashboardPage,
    "admin-complaints": adminComplaintsPage,
    "admin-reports": adminReportsPage
  };
  (routes[route] || notFoundPage)();
}

function applyTheme(theme) {
  const dark = theme === "dark";
  document.body.classList.toggle("dark", dark);
  const button = document.querySelector("#theme-toggle");
  button.querySelector("[data-icon]").innerHTML = icon(dark ? "sun" : "moon");
  button.querySelector(".theme-label").textContent = dark ? "Light mode" : "Dark mode";
  button.setAttribute("aria-label", dark ? "Switch to light mode" : "Switch to dark mode");
}

document.querySelector("#theme-toggle").addEventListener("click", () => {
  const theme = document.body.classList.contains("dark") ? "light" : "dark";
  localStorage.setItem("smartcity_theme", theme);
  applyTheme(theme);
});

document.querySelector("#mobile-menu-btn").addEventListener("click", event => {
  nav.classList.toggle("open");
  event.currentTarget.setAttribute("aria-expanded", String(nav.classList.contains("open")));
});

window.addEventListener("hashchange", renderRoute);
window.addEventListener("keydown", event => {
  if (event.key === "Escape" && modalRoot.innerHTML) closeModal();
});

applyTheme(localStorage.getItem("smartcity_theme") || "light");
renderRoute();
