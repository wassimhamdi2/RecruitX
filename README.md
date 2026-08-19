# RecruitX — Recruitment & Applicant Tracking System

Full-stack recruitment platform: Laravel 12 REST API + React 19/TypeScript SPA, with a FastAPI microservice for CV parsing.

```
RecruitX/
├── backend/         # Laravel 12 REST API (Sanctum tokens, RBAC, Eloquent)
├── frontend/        # React 19 + TypeScript + Vite SPA (Axios, TanStack Query, Zustand)
├── parser_service/  # FastAPI microservice (PDF/DOCX text extraction + heuristics)
└── README.md
```

## Features

- Authentication & authorization (Laravel Sanctum tokens, Spatie permissions)
- Role-based access: admin, recruiter, candidate
- Job management (companies, skills, job offers, filters)
- Candidate profiles, CV upload, **CV auto-detection & parsing** — upload a CV and the parser automatically detects contact info, skills, education and work experience (English or French), previews the result, then applies it to the profile with one click
- Application status pipeline with history tracking and terminal states (hired / rejected / withdrawn)
- Interview scheduling with multiple interviewers, rescheduling, notifications
- Recruiter dashboard with ApexCharts (application trends, status breakdown, top jobs)
- Notifications, audit logs, REST API

## Getting started

### 1. Backend

```bash
cd backend
cp .env.example .env   # set DB_CONNECTION=mysql, DB_DATABASE=recruitx
composer install
php artisan key:generate
php artisan migrate --seed
php artisan serve       # http://localhost:8000
```

`.env` also expects `CV_PARSER_URL` (default `http://127.0.0.1:8001`). Notification emails are sent via the queue:

```bash
php artisan queue:work
```

### 2. CV parser service

```bash
cd parser_service
python -m venv .venv
.venv\Scripts\pip install -r requirements.txt   # Windows
.venv\Scripts\python -m uvicorn app:app --port 8001 --host 127.0.0.1
```

### 3. Frontend

```bash
cd frontend
npm install
npm run dev   # http://localhost:5173 (proxies /api -> :8000)
```

### Demo accounts (seeded)

`admin@recruitx.test`, `recruiter@recruitx.test`, `candidate@recruitx.test` — all with password `password`.

## CV auto-detection

The parser service automatically detects the following from an uploaded PDF/DOC/DOCX:

- **Contact info** — name, email, and phone number in any international format (e.g. `+216 99 128 589`, `+33 6 12 34 56 78`, `06 12 34 56 78`)
- **Skills** — matched against a built-in skill dictionary (Java, React, Docker, …)
- **Education** — degree and institution, with year ranges
- **Work experience** — position and company, with year ranges

Section detection works for English (`SKILLS`, `EDUCATION`, `ACADEMIC EXPERIENCE`) and French (`COMPÉTENCES`, `ÉDUCATION`, `EXPÉRIENCES ACADÉMIQUES`) CVs, and handles both `2023 - 2025` and `January 2025 – June 2025` date formats. Parsed data is shown for review before being applied to the profile.

## Tests

```bash
cd backend && php artisan test      # 79 tests (232 assertions)
cd frontend && npm run lint && npm run build
```

## Tech stack

Laravel 12 · PHP · MySQL/MariaDB · Sanctum · Spatie Permission · FastAPI · PyMuPDF · React · TypeScript · Vite · Tailwind CSS · ApexCharts · Axios · TanStack Query · React Hook Form · Zod · Zustand · PHPUnit