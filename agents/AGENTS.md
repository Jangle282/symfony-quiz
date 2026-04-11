## Project Overview
A pub quiz game application using Open Trivia Database API with user accounts, game management, and score tracking.

## Project Context
- **Backend**: Symfony (PHP) with PostgreSQL, phpunit
- **Frontend**: React with TypeScript, axios, cypress, Daisy UI and tailwind. 
- **Infrastructure**: Docker & Docker Compose
- **API**: Open Trivia Database (https://opentdb.com/) but should be extensible to other API providers

## Backend Architecture & Standards
- Endpoints should be RESTFUL
- Unauthenticated endpoints should be covered by a throttle by IP. Authenticated routes by a throttle by user id. 
- Throttling should be handled by a global handler and not duplicated
- Controllers should be slim, utilising service classes for logic. They should focus on Requests and Responses and orchestrating services.
- Services should avoid requiring other services, unless necessary. To avoid circular references. 
- Use Repository classes for database interactions. 
- Endpoints should be covered with feature tests concerned with authorisation, authentication, request validation and responses. 
- Service classes should be covered with unit tests which also test database layer persistence. There is no need to mock the database.
- Tests should use the same database as the application but utilise transactions, rolling back changes made during the test
- The backend is responsible for returning error messages to be shown in the Front end.
- Create swagger documentation for the API


## Frontend Architecture & Standards
- Build the frontend as a Single Page Application (SPA)
- Use the backend as an API-only service for authentication and quiz data
- Prefer storing JWT tokens outside of cookies and sending them in the `Authorization` header
- Protect the app against XSS by keeping tokens in safe client storage, avoiding insecure script injection, and using secure coding patterns
- There should be a global error handler for catching and showing exceptions
- Create components with basic UI components from Daisy UI. Avoid custom implementations of styling and components. 
- Use local component state (useState) whenever state needs to be shared between components. Do not use Redux.
- For each component add rendering, navigation, form validation, input selection and form submission tests.
- Use Cypress for unit and E2E tests


## Patterns
- Review instructions against this file for conflicts. Request resolution before starting
- Always write tests