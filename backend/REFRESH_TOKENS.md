Refresh tokens (server-side)

Overview

- Login (`POST /api/login`) now returns:
  - `token`: short-lived JWT (access token)
  - `refresh_token`: opaque string stored in the database

- Use the refresh endpoint to rotate refresh tokens and obtain a new access token:
  - `POST /api/token/refresh`
  - Body: `{"refresh_token": "<refresh-token-value>"}`
  - Response: new `token`, new `refresh_token`, and `user` info

- Logout (`POST /api/logout`) revokes all refresh tokens for the current user.

Security notes

- Access tokens are short-lived (configured to 5 minutes).
- Refresh tokens are stored server-side and are rotated on use.
- Old refresh tokens are revoked when rotated; revoked or expired refresh tokens are rejected.

Developer tasks / commands

- Generate and run Doctrine migrations (one-time after pulling changes):

```bash
php bin/console doctrine:migrations:diff
php bin/console doctrine:migrations:migrate
```

- Run tests (tests have been updated to exercise the refresh flow):

```bash
php bin/phpunit
```

Client usage

- On login, persist both `token` and `refresh_token` securely on the client.
- Attach `Authorization: Bearer <token>` for API requests.
- When the access token expires, call `/api/token/refresh` with the `refresh_token` to get a new access token and rotated refresh token.
- On logout, call `/api/logout` to revoke refresh tokens.
