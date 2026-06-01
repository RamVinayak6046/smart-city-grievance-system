const http = require("http");
const fs = require("fs");
const path = require("path");
const crypto = require("crypto");
const { URL } = require("url");

const PORT = Number(process.env.PORT || 4173);
const ROOT = __dirname;
const PUBLIC_DIR = path.join(ROOT, "public");
const DATA_DIR = path.join(ROOT, "data");
const STORE_FILE = path.join(DATA_DIR, "store.json");
const sessions = new Map();

fs.mkdirSync(DATA_DIR, { recursive: true });

function isoDaysAgo(days, hour = 9) {
  const date = new Date();
  date.setDate(date.getDate() - days);
  date.setHours(hour, 30, 0, 0);
  return date.toISOString();
}

function hashPassword(password, salt = crypto.randomBytes(16).toString("hex")) {
  return `${salt}:${crypto.scryptSync(password, salt, 64).toString("hex")}`;
}

function verifyPassword(password, stored) {
  const [salt, hash] = stored.split(":");
  const computed = crypto.scryptSync(password, salt, 64);
  return crypto.timingSafeEqual(Buffer.from(hash, "hex"), computed);
}

function seedStore() {
  return {
    users: [
      {
        user_id: 1,
        name: "Aarav Mehta",
        email: "citizen@example.com",
        mobile: "9876543210",
        password: hashPassword("Citizen@123"),
        created_at: isoDaysAgo(146)
      },
      {
        user_id: 2,
        name: "Meera Nair",
        email: "meera@example.com",
        mobile: "9820016721",
        password: hashPassword("Citizen@123"),
        created_at: isoDaysAgo(97)
      },
      {
        user_id: 3,
        name: "Rohan Iyer",
        email: "rohan@example.com",
        mobile: "9811145632",
        password: hashPassword("Citizen@123"),
        created_at: isoDaysAgo(61)
      }
    ],
    admins: [
      {
        admin_id: 1,
        username: "admin@smartcity.gov",
        name: "Municipal Admin",
        password: hashPassword("Admin@123")
      }
    ],
    complaints: [
      {
        complaint_id: "SCM-2026-0001",
        user_id: 1,
        title: "Streetlight not working near Central Park",
        category: "Street Light",
        description: "Three consecutive streetlights are not functioning on the east side of Central Park Road.",
        location: "Central Park Road, Ward 12",
        image_path: "",
        priority: "Medium",
        status: "Resolved",
        assigned_to: "Electrical Services",
        admin_remarks: "Faulty cable junction replaced and lights tested after sunset.",
        created_at: isoDaysAgo(48),
        updated_at: isoDaysAgo(43)
      },
      {
        complaint_id: "SCM-2026-0002",
        user_id: 1,
        title: "Large pothole causing traffic slowdown",
        category: "Potholes",
        description: "A deep pothole has formed near the school crossing and vehicles are swerving to avoid it.",
        location: "Lakeview Avenue, Ward 8",
        image_path: "",
        priority: "High",
        status: "In Progress",
        assigned_to: "Road Maintenance",
        admin_remarks: "Site inspected. Repair crew scheduled for resurfacing work.",
        created_at: isoDaysAgo(7),
        updated_at: isoDaysAgo(2)
      },
      {
        complaint_id: "SCM-2026-0003",
        user_id: 2,
        title: "Garbage accumulation beside community hall",
        category: "Garbage",
        description: "Waste has not been collected for several days and is blocking the pedestrian path.",
        location: "Community Hall Lane, Ward 4",
        image_path: "",
        priority: "High",
        status: "Assigned",
        assigned_to: "Solid Waste Management",
        admin_remarks: "Assigned to the zonal collection team for same-day clearance.",
        created_at: isoDaysAgo(3),
        updated_at: isoDaysAgo(1)
      },
      {
        complaint_id: "SCM-2026-0004",
        user_id: 3,
        title: "Water leaking from pipeline",
        category: "Water Leakage",
        description: "Continuous clean water leakage is visible near the bus stop and water is flowing onto the road.",
        location: "Metro Station Road, Ward 6",
        image_path: "",
        priority: "Urgent",
        status: "Pending",
        assigned_to: "Unassigned",
        admin_remarks: "",
        created_at: isoDaysAgo(1),
        updated_at: isoDaysAgo(1)
      },
      {
        complaint_id: "SCM-2026-0005",
        user_id: 2,
        title: "Blocked storm drain after rainfall",
        category: "Drainage",
        description: "Storm drain is clogged and standing water remains across the service lane.",
        location: "Green Market Service Lane, Ward 10",
        image_path: "",
        priority: "Medium",
        status: "Resolved",
        assigned_to: "Drainage Operations",
        admin_remarks: "Drain desilted and cover reset. Flow restored.",
        created_at: isoDaysAgo(84),
        updated_at: isoDaysAgo(79)
      },
      {
        complaint_id: "SCM-2026-0006",
        user_id: 1,
        title: "Damaged road surface near flyover",
        category: "Road Damage",
        description: "Road surface is uneven with broken asphalt along the left lane.",
        location: "Civic Centre Flyover, Ward 11",
        image_path: "",
        priority: "Medium",
        status: "Resolved",
        assigned_to: "Road Maintenance",
        admin_remarks: "Patch repair completed and lane reopened.",
        created_at: isoDaysAgo(119),
        updated_at: isoDaysAgo(112)
      },
      {
        complaint_id: "SCM-2026-0007",
        user_id: 3,
        title: "Traffic signal timing issue",
        category: "Traffic Signal",
        description: "Signal remains green for only a few seconds during the morning peak hour.",
        location: "Museum Junction, Ward 3",
        image_path: "",
        priority: "Medium",
        status: "Resolved",
        assigned_to: "Traffic Engineering",
        admin_remarks: "Controller timing updated after traffic study.",
        created_at: isoDaysAgo(151),
        updated_at: isoDaysAgo(144)
      }
    ]
  };
}

function readStore() {
  if (!fs.existsSync(STORE_FILE)) {
    writeStore(seedStore());
  }
  return JSON.parse(fs.readFileSync(STORE_FILE, "utf8"));
}

function writeStore(store) {
  fs.writeFileSync(STORE_FILE, JSON.stringify(store, null, 2));
}

function sendJson(res, status, payload) {
  res.writeHead(status, { "Content-Type": "application/json; charset=utf-8" });
  res.end(JSON.stringify(payload));
}

function sendText(res, status, body, type = "text/plain; charset=utf-8", headers = {}) {
  res.writeHead(status, { "Content-Type": type, ...headers });
  res.end(body);
}

function parseBody(req) {
  return new Promise((resolve, reject) => {
    let body = "";
    req.on("data", chunk => {
      body += chunk;
      if (body.length > 1_000_000) reject(new Error("Request body is too large."));
    });
    req.on("end", () => {
      if (!body) return resolve({});
      try {
        resolve(JSON.parse(body));
      } catch {
        reject(new Error("Invalid JSON request."));
      }
    });
    req.on("error", reject);
  });
}

function authSession(req, role) {
  const token = (req.headers.authorization || "").replace(/^Bearer\s+/i, "");
  const session = sessions.get(token);
  return session && (!role || session.role === role) ? session : null;
}

function withUser(complaint, store) {
  const user = store.users.find(item => item.user_id === complaint.user_id);
  return { ...complaint, citizen_name: user ? user.name : "Unknown Citizen" };
}

function nextComplaintId(complaints) {
  const year = new Date().getFullYear();
  const max = complaints.reduce((current, item) => {
    const value = Number(item.complaint_id.split("-").pop());
    return Math.max(current, Number.isFinite(value) ? value : 0);
  }, 0);
  return `SCM-${year}-${String(max + 1).padStart(4, "0")}`;
}

function getAdminStats(store) {
  const categoryMap = {};
  const statusMap = {};
  const monthlyMap = {};
  const monthBuckets = Array.from({ length: 6 }, (_, index) => {
    const date = new Date();
    date.setMonth(date.getMonth() - (5 - index), 1);
    const key = `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, "0")}`;
    monthlyMap[key] = { label: date.toLocaleDateString("en-US", { month: "short" }), value: 0 };
    return key;
  });
  store.complaints.forEach(item => {
    categoryMap[item.category] = (categoryMap[item.category] || 0) + 1;
    const date = new Date(item.created_at);
    const month = `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, "0")}`;
    if (monthlyMap[month]) monthlyMap[month].value += 1;
    statusMap[item.status] = (statusMap[item.status] || 0) + 1;
  });
  return {
    totalUsers: store.users.length,
    totalComplaints: store.complaints.length,
    pending: store.complaints.filter(item => item.status === "Pending").length,
    resolved: store.complaints.filter(item => item.status === "Resolved").length,
    categoryData: Object.entries(categoryMap).map(([label, value]) => ({ label, value })),
    monthlyData: monthBuckets.map(key => monthlyMap[key]),
    statusData: ["Pending", "Assigned", "In Progress", "Resolved"].map(label => ({ label, value: statusMap[label] || 0 }))
  };
}

function reportRows(store, period) {
  const now = new Date();
  let since = new Date(0);
  if (period === "daily") since = new Date(now.getTime() - 24 * 60 * 60 * 1000);
  if (period === "weekly") since = new Date(now.getTime() - 7 * 24 * 60 * 60 * 1000);
  if (period === "monthly") since = new Date(now.getTime() - 30 * 24 * 60 * 60 * 1000);
  return store.complaints
    .filter(item => period === "category" || new Date(item.created_at) >= since)
    .map(item => withUser(item, store));
}

function csvEscape(value) {
  return `"${String(value ?? "").replaceAll('"', '""')}"`;
}

function makeCsv(rows) {
  const headers = ["Complaint ID", "Citizen", "Category", "Priority", "Status", "Assigned To", "Location", "Created At"];
  const body = rows.map(row => [
    row.complaint_id,
    row.citizen_name,
    row.category,
    row.priority,
    row.status,
    row.assigned_to,
    row.location,
    row.created_at
  ].map(csvEscape).join(","));
  return [headers.map(csvEscape).join(","), ...body].join("\r\n");
}

function pdfEscape(value) {
  return String(value).replaceAll("\\", "\\\\").replaceAll("(", "\\(").replaceAll(")", "\\)");
}

function makeSimplePdf(rows, period) {
  const lines = [
    "SMART CITY GRIEVANCE MANAGEMENT SYSTEM",
    `${period.toUpperCase()} COMPLAINT REPORT`,
    `Generated: ${new Date().toLocaleString("en-IN")}`,
    "",
    `Total complaints: ${rows.length}`,
    "",
    ...rows.slice(0, 22).map(row =>
      `${row.complaint_id} | ${row.category} | ${row.status} | ${row.citizen_name}`
    )
  ];
  const commands = [
    "BT",
    "/F1 11 Tf",
    "50 790 Td",
    ...lines.flatMap((line, index) => [
      index === 0 ? "" : "0 -20 Td",
      `(${pdfEscape(line)}) Tj`
    ]),
    "ET"
  ].filter(Boolean).join("\n");
  const objects = [
    "<< /Type /Catalog /Pages 2 0 R >>",
    "<< /Type /Pages /Kids [3 0 R] /Count 1 >>",
    "<< /Type /Page /Parent 2 0 R /MediaBox [0 0 612 842] /Resources << /Font << /F1 5 0 R >> >> /Contents 4 0 R >>",
    `<< /Length ${Buffer.byteLength(commands)} >>\nstream\n${commands}\nendstream`,
    "<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>"
  ];
  let pdf = "%PDF-1.4\n";
  const offsets = [0];
  objects.forEach((object, index) => {
    offsets.push(Buffer.byteLength(pdf));
    pdf += `${index + 1} 0 obj\n${object}\nendobj\n`;
  });
  const xref = Buffer.byteLength(pdf);
  pdf += `xref\n0 ${objects.length + 1}\n0000000000 65535 f \n`;
  offsets.slice(1).forEach(offset => {
    pdf += `${String(offset).padStart(10, "0")} 00000 n \n`;
  });
  pdf += `trailer\n<< /Size ${objects.length + 1} /Root 1 0 R >>\nstartxref\n${xref}\n%%EOF`;
  return Buffer.from(pdf);
}

function serveStatic(req, res, pathname) {
  const requested = pathname === "/" ? "/index.html" : pathname;
  const filePath = path.normalize(path.join(PUBLIC_DIR, requested));
  if (!filePath.startsWith(PUBLIC_DIR) || !fs.existsSync(filePath) || fs.statSync(filePath).isDirectory()) {
    return sendText(res, 404, "Not Found");
  }
  const extension = path.extname(filePath);
  const mime = {
    ".html": "text/html; charset=utf-8",
    ".css": "text/css; charset=utf-8",
    ".js": "application/javascript; charset=utf-8",
    ".png": "image/png",
    ".svg": "image/svg+xml"
  }[extension] || "application/octet-stream";
  res.writeHead(200, { "Content-Type": mime, "Cache-Control": extension === ".html" ? "no-cache" : "public, max-age=3600" });
  fs.createReadStream(filePath).pipe(res);
}

async function handleApi(req, res, url) {
  const store = readStore();
  const pathname = url.pathname;

  if (req.method === "GET" && pathname === "/api/health") {
    return sendJson(res, 200, { status: "ok", service: "Smart City Grievance API" });
  }

  if (req.method === "GET" && pathname === "/api/stats") {
    const stats = getAdminStats(store);
    return sendJson(res, 200, {
      totalComplaints: stats.totalComplaints + 12473,
      resolved: stats.resolved + 10889,
      activeCitizens: stats.totalUsers + 8421,
      wardsCovered: 24
    });
  }

  if (req.method === "POST" && pathname === "/api/auth/register") {
    const body = await parseBody(req);
    if (!body.name || !body.email || !body.mobile || !body.password) {
      return sendJson(res, 400, { error: "Please complete all required fields." });
    }
    if (!/^\S+@\S+\.\S+$/.test(body.email)) {
      return sendJson(res, 400, { error: "Please enter a valid email address." });
    }
    if (!/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).{8,}$/.test(body.password)) {
      return sendJson(res, 400, { error: "Password must contain 8 characters, uppercase, lowercase, and a number." });
    }
    if (store.users.some(item => item.email.toLowerCase() === body.email.toLowerCase())) {
      return sendJson(res, 409, { error: "An account with this email already exists." });
    }
    const user = {
      user_id: Math.max(0, ...store.users.map(item => item.user_id)) + 1,
      name: body.name.trim(),
      email: body.email.trim().toLowerCase(),
      mobile: body.mobile.trim(),
      password: hashPassword(body.password),
      created_at: new Date().toISOString()
    };
    store.users.push(user);
    writeStore(store);
    return sendJson(res, 201, { message: "Registration successful. You can now sign in." });
  }

  if (req.method === "POST" && pathname === "/api/auth/login") {
    const body = await parseBody(req);
    const user = store.users.find(item => item.email.toLowerCase() === String(body.email || "").toLowerCase());
    if (!user || !verifyPassword(String(body.password || ""), user.password)) {
      return sendJson(res, 401, { error: "Invalid email or password." });
    }
    const token = crypto.randomBytes(24).toString("hex");
    sessions.set(token, { role: "citizen", user_id: user.user_id });
    return sendJson(res, 200, {
      token,
      user: { user_id: user.user_id, name: user.name, email: user.email, mobile: user.mobile }
    });
  }

  if (req.method === "POST" && pathname === "/api/auth/admin") {
    const body = await parseBody(req);
    const admin = store.admins.find(item => item.username.toLowerCase() === String(body.username || "").toLowerCase());
    if (!admin || !verifyPassword(String(body.password || ""), admin.password)) {
      return sendJson(res, 401, { error: "Invalid administrator credentials." });
    }
    const token = crypto.randomBytes(24).toString("hex");
    sessions.set(token, { role: "admin", admin_id: admin.admin_id });
    return sendJson(res, 200, { token, admin: { admin_id: admin.admin_id, name: admin.name, username: admin.username } });
  }

  if (req.method === "GET" && pathname === "/api/my/complaints") {
    const session = authSession(req, "citizen");
    if (!session) return sendJson(res, 401, { error: "Please sign in to continue." });
    return sendJson(res, 200, store.complaints.filter(item => item.user_id === session.user_id).sort((a, b) => b.created_at.localeCompare(a.created_at)));
  }

  if (req.method === "PATCH" && pathname === "/api/my/profile") {
    const session = authSession(req, "citizen");
    if (!session) return sendJson(res, 401, { error: "Please sign in to update your profile." });
    const body = await parseBody(req);
    if (!body.name || !body.email || !body.mobile || !/^\S+@\S+\.\S+$/.test(body.email)) {
      return sendJson(res, 400, { error: "Please enter valid profile details." });
    }
    if (store.users.some(item => item.user_id !== session.user_id && item.email.toLowerCase() === body.email.toLowerCase())) {
      return sendJson(res, 409, { error: "Another account already uses this email address." });
    }
    const user = store.users.find(item => item.user_id === session.user_id);
    user.name = body.name.trim();
    user.email = body.email.trim().toLowerCase();
    user.mobile = body.mobile.trim();
    writeStore(store);
    return sendJson(res, 200, { user: { user_id: user.user_id, name: user.name, email: user.email, mobile: user.mobile } });
  }

  if (req.method === "POST" && pathname === "/api/complaints") {
    const session = authSession(req, "citizen");
    if (!session) return sendJson(res, 401, { error: "Please sign in to submit a complaint." });
    const body = await parseBody(req);
    if (!body.title || !body.category || !body.description || !body.location || !body.priority) {
      return sendJson(res, 400, { error: "Please complete all complaint fields." });
    }
    const now = new Date().toISOString();
    const complaint = {
      complaint_id: nextComplaintId(store.complaints),
      user_id: session.user_id,
      title: body.title.trim(),
      category: body.category,
      description: body.description.trim(),
      location: body.location.trim(),
      image_path: body.image_path || "",
      priority: body.priority,
      status: "Pending",
      assigned_to: "Unassigned",
      admin_remarks: "",
      created_at: now,
      updated_at: now
    };
    store.complaints.push(complaint);
    writeStore(store);
    return sendJson(res, 201, { message: "Complaint submitted successfully.", complaint });
  }

  const publicComplaint = pathname.match(/^\/api\/complaints\/([^/]+)$/);
  if (req.method === "GET" && publicComplaint) {
    const complaint = store.complaints.find(item => item.complaint_id === decodeURIComponent(publicComplaint[1]));
    if (!complaint) return sendJson(res, 404, { error: "No complaint found with that ID." });
    return sendJson(res, 200, withUser(complaint, store));
  }

  if (req.method === "GET" && pathname === "/api/admin/dashboard") {
    if (!authSession(req, "admin")) return sendJson(res, 401, { error: "Administrator access required." });
    return sendJson(res, 200, getAdminStats(store));
  }

  if (req.method === "GET" && pathname === "/api/admin/complaints") {
    if (!authSession(req, "admin")) return sendJson(res, 401, { error: "Administrator access required." });
    const search = (url.searchParams.get("search") || "").toLowerCase();
    const status = url.searchParams.get("status") || "";
    const category = url.searchParams.get("category") || "";
    const rows = store.complaints
      .map(item => withUser(item, store))
      .filter(item => !search || [item.complaint_id, item.title, item.location, item.citizen_name].some(value => String(value).toLowerCase().includes(search)))
      .filter(item => !status || item.status === status)
      .filter(item => !category || item.category === category)
      .sort((a, b) => b.created_at.localeCompare(a.created_at));
    return sendJson(res, 200, rows);
  }

  const adminComplaint = pathname.match(/^\/api\/admin\/complaints\/([^/]+)$/);
  if (req.method === "PATCH" && adminComplaint) {
    if (!authSession(req, "admin")) return sendJson(res, 401, { error: "Administrator access required." });
    const body = await parseBody(req);
    const complaint = store.complaints.find(item => item.complaint_id === decodeURIComponent(adminComplaint[1]));
    if (!complaint) return sendJson(res, 404, { error: "Complaint not found." });
    ["status", "assigned_to", "admin_remarks"].forEach(field => {
      if (typeof body[field] === "string") complaint[field] = body[field].trim();
    });
    complaint.updated_at = new Date().toISOString();
    writeStore(store);
    return sendJson(res, 200, { message: "Complaint updated successfully.", complaint });
  }

  if (req.method === "GET" && pathname === "/api/reports") {
    if (!authSession(req, "admin")) return sendJson(res, 401, { error: "Administrator access required." });
    const period = ["daily", "weekly", "monthly", "category"].includes(url.searchParams.get("period")) ? url.searchParams.get("period") : "monthly";
    const format = url.searchParams.get("format") || "json";
    const rows = reportRows(store, period);
    const filename = `smart-city-${period}-report`;
    if (format === "csv") {
      return sendText(res, 200, makeCsv(rows), "application/vnd.ms-excel; charset=utf-8", {
        "Content-Disposition": `attachment; filename="${filename}.csv"`
      });
    }
    if (format === "pdf") {
      const pdf = makeSimplePdf(rows, period);
      res.writeHead(200, {
        "Content-Type": "application/pdf",
        "Content-Disposition": `attachment; filename="${filename}.pdf"`
      });
      return res.end(pdf);
    }
    return sendJson(res, 200, rows);
  }

  return sendJson(res, 404, { error: "API endpoint not found." });
}

const server = http.createServer(async (req, res) => {
  const url = new URL(req.url, `http://${req.headers.host || "localhost"}`);
  try {
    if (url.pathname.startsWith("/api/")) {
      return await handleApi(req, res, url);
    }
    return serveStatic(req, res, url.pathname);
  } catch (error) {
    console.error(error);
    return sendJson(res, 500, { error: error.message || "Unexpected server error." });
  }
});

server.listen(PORT, () => {
  console.log(`Smart City Grievance Management System running at http://localhost:${PORT}`);
  console.log("Citizen demo: citizen@example.com / Citizen@123");
  console.log("Admin demo: admin@smartcity.gov / Admin@123");
});
