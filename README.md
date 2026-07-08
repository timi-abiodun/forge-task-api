# Forge Task API

Forge Task API is a Laravel-based backend for managing organisations, projects, tasks, attachments, and invitation-driven onboarding. It is built as an API-first application with Sanctum authentication, organisation-aware access control, and a documented OpenAPI surface for consumers.

## Overview

- Authenticates users with Laravel Sanctum bearer tokens.
- Lets users create and manage organisations, projects, tasks, and attachments.
- Sends organisation invitations by email and supports public accept and reject flows.
- Sends task notifications and reminders through queued mail and notifications.
- Exposes Swagger UI and an OpenAPI document for interactive API exploration.
- Includes a server-rendered Blade demo UI covering auth, projects, tasks, attachments, notifications, and invitations, built as a throwaway layer ahead of a planned React SPA.

## API Docs

- Swagger UI: `/api/docs`
- OpenAPI JSON: `/openapi.json`

The OpenAPI schema documents the public API contract, including authentication, organisation-scoped endpoints, and invitation lifecycle fields such as `accepted_by`.

## Domain Notes

- Organisations own projects, tasks, memberships, and invitations.
- Project, task, and invitation statuses are application-defined enum values rather than free-form strings.
- Membership roles are enum-based, and organisation admins or owners are used for elevated access.
- Accepted invitations record the user in the `accepted_by` field and also store the acceptance timestamp.

## API Surface

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

## Web UI (Demo Layer)

A server-rendered Blade interface sits alongside the API, sharing the same underlying services, policies, and FormRequests — no business logic is duplicated. It covers:

- Auth (login/register) via the web session guard
- Organisation creation and switching (session-based active org, not URL-based)
- Project and task CRUD, with attachment upload/download/delete
- A notifications dropdown (Laravel's built-in database notifications)
- Role-based UI visibility (`@can` gates matching the real policies)
- Full invitation flow: send/list/revoke/resend, plus a public accept/reject page for invitees

### Invitation acceptance and new-account setup

The API's `acceptInvitation()` creates a `User` with an unusable random password for brand-new invitees, with no way for them to ever log in. The web layer adds a **new, additive** service method (`acceptInvitationWithPasswordSetup()`) that instead issues a real password-reset token via Laravel's built-in password broker for new accounts, and routes existing-account invitees straight to login. The original method is untouched, so the API's behavior and tests are unaffected.

### Known limitations

- **Email delivery (Resend):** sandbox mode only delivers to the Resend account's own verified email. Sending to arbitrary invitees requires verifying a sending domain at resend.com/domains.
- The task edit form uses a UI heuristic to disable restricted fields for assignee-only users. Disabled fields are not submitted by the browser, but the server-side `UpdateTaskRequest` is the real enforcement point, so this fails safe even if the UI logic doesn’t perfectly match every role edge case.



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
- The API documentation is served from the public Swagger UI bundle, so keep `public/openapi.json` and `public/swagger-ui/` in sync when the contract changes.
- For local development, `QUEUE_CONNECTION=sync` avoids needing a running queue worker, but note that a failing mail send (e.g. Resend sandbox rejecting a recipient) will then throw synchronously and can interrupt an otherwise-unrelated request (like task creation triggering an assignment notification). `QUEUE_CONNECTION=database` + `php artisan queue:work` isolates notification failures from core actions.

## Testing

```bash
php artisan test
```

## Project Notes

- The API is the primary, permanent interface. A Blade-based web UI exists alongside it as a demo layer for portfolio purposes and is not the intended long-term frontend — a React SPA is planned to replace it.
- Organisation context is enforced on protected organisation routes.
- Invitation acceptance creates or reuses a user account and then records the membership.
- Task reminders and notifications are handled through Laravel events, listeners, notifications, and queued mail.

## License

This project is licensed under the MIT license.
