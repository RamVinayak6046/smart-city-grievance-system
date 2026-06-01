# Smart City Grievance Management System

A responsive full-stack civic grievance portal for citizens and municipal administrators.

## Run

```powershell
npm start
```

Open `http://localhost:4173`.

## Demo accounts

- Citizen: `citizen@example.com` / `Citizen@123`
- Admin: `admin@smartcity.gov` / `Admin@123`

## Storage

The zero-dependency demo persists data in `data/store.json`, generated on first run. `schema.sql` contains the production-ready MySQL database design requested for deployment.

## Features

- Citizen registration, login, grievance submission, tracking, history, and profile editing
- Admin authentication, dashboard analytics, complaint assignment, remarks, and status management
- Daily, weekly, monthly, and category report exports in Excel-compatible CSV and PDF
- Responsive, accessible interface with dark mode
