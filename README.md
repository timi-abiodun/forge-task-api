# Forge Task API

Forge Task API is a Laravel-based backend for managing organisations, projects, tasks, attachments, and invitation-driven onboarding. It is designed as an API-first application with Sanctum authentication, role-aware organisation context, and file handling for task attachments.

## What It Does

- Authenticates users with Laravel Sanctum token auth.
- Lets users create and manage organisations, projects, and tasks.
- Supports task attachments with upload, download, list, and delete flows.
- Sends organisation invitations by email and allows invite acceptance or rejection through public token endpoints.
- Tracks organisation membership roles and invitation lifecycle states with enums.
- Sends task notifications and reminders through queued mail and notification jobs.

## Core Domain Model

- Organisations own projects, tasks, memberships, and invitations.
- Projects use status values such as `planning`, `active`, `on_hold`, `completed`, and `cancelled`.
- Tasks use status values such as `todo`, `in_progress`, `blocked`, `review`, and `completed`.
- Membership roles are enum-based, and organisation admins or owners are used for elevated access.
- Invitations are tracked with statuses such as `pending`, `accepted`, `declined`, `expired`, and `revoked`.
- Accepted invitations record the user in the `accepted_by` field.

## API Highlights

Base route prefix: `/api/v1`

Authentication:

- `POST /register`
- `POST /login`
- `POST /logout`
- `GET /user`

Organisation-scoped routes:

- `GET /organisations/{organisation}/projects`
- `POST /organisations/{organisation}/projects`
- `GET /organisations/{organisation}/projects/{project}`
- `PUT /organisations/{organisation}/projects/{project}`
- `DELETE /organisations/{organisation}/projects/{project}`
- `GET /organisations/{organisation}/tasks`
- `POST /organisations/{organisation}/tasks`
- `GET /organisations/{organisation}/tasks/{task}`
- `PUT /organisations/{organisation}/tasks/{task}`
- `DELETE /organisations/{organisation}/tasks/{task}`
- `GET /organisations/{organisation}/tasks/{task}/attachments`
- `POST /organisations/{organisation}/tasks/{task}/attachments`
- `GET /organisations/{organisation}/tasks/{task}/attachments/{attachment}`
- `GET /organisations/{organisation}/tasks/{task}/attachments/{attachment}/download`
- `DELETE /organisations/{organisation}/tasks/{task}/attachments/{attachment}`
- `POST /organisations/{organisation}/invitations`
- `GET /organisations/{organisation}/invitations`
- `DELETE /organisations/{organisation}/invitations/{invitation}`
- `POST /organisations/{organisation}/invitations/{invitation}/resend`

Public invitation routes:

- `GET /invitations/{token}`
- `POST /invitations/{token}/accept`
- `POST /invitations/{token}/reject`

## Requirements

- PHP 8.3+
- Composer
- Node.js and npm
- A database supported by Laravel

## Getting Started

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
npm install
npm run build
```

If you want the full local developer workflow, use the bundled script:

```bash
composer run dev
```

That starts the Laravel server, queue listener, and Vite dev server together.

## Configuration Notes

- Set your database connection in `.env` before running migrations.
- Configure mail so organisation invitations can be delivered.
- Configure the filesystem disk used for attachments in `config/attachments.php` and the related filesystem settings.
- Sanctum is used for API authentication, so clients should send the issued bearer token on protected requests.

## Testing

```bash
php artisan test
```

## Project Notes

- This repository is API-focused rather than a full web frontend.
- Organisation context is enforced on protected organisation routes.
- Invitation acceptance creates or reuses a user account and then records the membership.
- Task reminders and notifications are handled through Laravel events, listeners, notifications, and queued mail.

## License

This project is licensed under the MIT license.




change all status to user defined. 
review the acepted by field in the invitation table.