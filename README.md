# Symfony Quiz (Pub Quiz App)

A pub quiz game application using Symfony (PHP) backend and React (TypeScript) frontend.

## Quick start

### 1) Start the development stack

```sh
docker compose up --build
```

### 2) Backend

- Symfony API: http://localhost:8080

### 3) Frontend

- React app: http://localhost:5173

## Project Structure

- `backend/` - Symfony API
- `frontend/` - React + Vite frontend

## Backend notes

- Authentication now uses short-lived JWT access tokens and server-stored refresh tokens.
- Login (`POST /api/login`) returns `token` (access JWT) and `refresh_token` (opaque string).
- Refresh tokens: call `POST /api/token/refresh` with JSON body `{"refresh_token":"<token>"}` to obtain a new access token and a rotated refresh token.
- Logout (`POST /api/logout`) revokes refresh tokens for the user.

Run migrations after pulling these changes:
```bash
php bin/console doctrine:migrations:migrate
```

## Notes

- Environment variables are managed via `.env` files.
- Database is PostgreSQL running in Docker.
