# RecruitX — Recruitment & Applicant Tracking System

Full-stack recruitment platform: Laravel 12 REST API + React 19/TypeScript SPA.

```
RecruitX/
├── backend/    # Laravel 12 REST API (Sanctum tokens, RBAC, Eloquent)
├── frontend/   # React 19 + TypeScript + Vite SPA (Axios, TanStack Query, Zustand)
└── README.md
```

## Features

- Authentication & authorization (Laravel Sanctum tokens, Spatie permissions)
- Role-based access: admin, recruiter, candidate
- Job management (companies, skills, job offers, filters)
- Candidate profiles, CV uploads, applications
- Application status pipeline with history tracking
- Interview scheduling, participants, candidate evaluations
- Notifications, audit logs, reports, REST API

## Getting started

### Backend

```bash
cd backend
cp .env.example .env   # set DB_CONNECTION=mysql, DB_DATABASE=recruitx
composer install
php artisan key:generate
php artisan migrate --seed
php artisan serve
```

Demo accounts (seeded): `admin@recruitx.test`, `recruiter@recruitx.test`, `candidate@recruitx.test` — all with password `password`.

### Frontend

```bash
cd frontend
npm install
npm run dev   # http://localhost:5173 (proxies /api -> :8000)
```

## Tech stack

Laravel 12 - PHP - MySQL/MariaDB - Sanctum - Spatie Permission - React - TypeScript - Vite - Tailwind CSS - Axios - TanStack Query - React Hook Form - Zod - Zustand - PHPUnit