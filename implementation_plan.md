# Pub Quiz Application - Implementation Plan

## Project Overview
A pub quiz game application using Open Trivia Database API with user accounts, game management, and score tracking.

## Tech Stack
- **Backend**: Symfony (PHP) with PostgreSQL
- **Frontend**: React with TypeScript
- **Infrastructure**: Docker & Docker Compose
- **API**: Open Trivia Database (https://opentdb.com/)

## Backend Architecture
- Unauthenticated endpoints should be covered by a throttle by IP. Authenticated routes by a throttle by user id. 
- Throttling should be handled by a global handler and not duplicated
- Controllers should be slim, utilising service classes for logic. They should focus on Requests and Responses and orchestrating services.
- Services should avoid requiring other services, unless necessary. To avoid circular references. 
- Where necessary Repository layers for models can be created to avoid duplication of database layer logic. 
- Endpoints should be covered with feature tests concerned with authorisation, authentication, request validation and responses. 
- Service classes should be covered with unit tests, although the database does not need to be mocked. 

## Frontend Architecture
- Build the frontend as a Single Page Application (SPA)
- Use the backend as an API-only service for authentication and quiz data
- Prefer storing JWT tokens outside of cookies and sending them in the `Authorization` header
- Protect the app against XSS by keeping tokens in safe client storage, avoiding insecure script injection, and using secure coding patterns

---

## Phase 1: Project Setup & Infrastructure

### 1.1 Docker Configuration
- Create `docker-compose.yml` with services:
  - `nginx` - Web server
  - `php-fpm` - PHP 8.2+ with Symfony
  - `postgres` - PostgreSQL 15+
  - `node` - Node.js for React development
- Create `Dockerfile` for PHP/Symfony service
- Create `Dockerfile` for React frontend
- Configure volume mappings for development
- Set up environment variables in `.env` files
- Create `.dockerignore` files

### 1.2 Symfony Backend Setup
- Initialize Symfony 6.x project
- Install core dependencies:
  - `symfony/orm-pack` (Doctrine ORM)
  - `symfony/security-bundle`
  - `symfony/validator`
  - `symfony/serializer`
  - `lexik/jwt-authentication-bundle`
  - `nelmio/cors-bundle`
  - `symfony/http-client` (for API calls)
- Install dev dependencies:
  - `symfony/maker-bundle`
  - `phpunit/phpunit`
  - `symfony/browser-kit`
  - `symfony/phpunit-bridge`
  - `dama/doctrine-test-bundle`
- Configure database connection in `.env`
- Set up CORS for React frontend, allowing the React origin and the `Authorization` header for bearer tokens
- Configure JWT authentication

### 1.3 React Frontend Setup
- Initialize React app with TypeScript using Vite
- Install dependencies:
  - `react-router-dom`
  - `axios`
  - `@tanstack/react-query` (for API state management)
  - `tailwindcss` or `mui` (UI framework)
- Install dev dependencies:
  - `@testing-library/react`
  - `@testing-library/jest-dom`
  - `vitest`
  - `@types/react`
  - `@types/react-dom`
- Configure TypeScript (`tsconfig.json`)
- Set up API client with axios
- Configure environment variables

---

## Phase 2: Database Schema & Models

### 2.1 Database Design
Create the following entities:

#### User Entity
```
- table name users
- entity name User
Columns
- id (uuid, primary key)
- username (string, unique)
- password (string, hashed)
- created_at (datetime)
- updated_at (datetime)
```

#### Difficulty Entity
```
- table name quiz_difficulty
- entity name Difficulty
Columns
- id (uuid, primary key)
- name (string, not nullable)
```

#### Game Entity
```
- table name quiz_games
- entity name Game
Columns
- id (uuid, primary key)
- name (string, nullable) - optional game name
- difficulty (Foreign key to quiz_difficulty)
- created_by (uuid, foreign key to user) - user who created the game
- total_score (integer) - team score
- started_at (datetime)
- completed_at (datetime, nullable)
- created_at (datetime)
```

#### UserGame Entity (Join table for many-to-many User-Game with additional data)
```
- table name user_game
- entity name UserGame
Columns
- id (uuid, primary key)
- user_id (uuid, foreign key to user)
- game_id (uuid, foreign key to quiz_games)
- joined_at (datetime)
- role (string, enum: 'host', 'participant')
```

#### Round Category
```
- table name quiz_category
- entity name Category
Columns
- id (uuid, primary key)
- name (string, not nullable)
```

#### Round Entity
```
- table name quiz_rounds
- entity name Round
Columns
- id (uuid, primary key)
- game_id (foreign key to quiz_games)
- category_id (foreign key to quiz_category)
- round_number (integer)
- created_at (datetime)
```

#### Question Entity
```
- table name quiz_questions
- entity name Question
Columns
- id (uuid, primary key)
- round_id (foreign key to quiz_rounds)
- question_text (text)
```

#### Answer Entity
```
- table name quiz_answers
- entity name Answer
Columns
- id (uuid, primary key)
- question_id (foreign key to quiz_questions)
- user_selected (boolean)
- is_correct (boolean)
```

### 2.2 Doctrine Entity Implementation
- Create User entity with validation
- Create Game entity with relationships (ManyToMany with User via UserGame)
- Create UserGame entity for game participation
- Create Round entity
- Create Question entity
- Create Answer entity
- Create migrations for all entities
- Set up entity relationships and cascade operations

### 2.3 Seeders and Factories
- Set up a basic Seeder structure creating a user with a game which has 1 round with 5 questions and answers
- Create a basic factory for each model for use in tests and seeders

---

## Phase 3: Backend - Authentication & Authorization

### 3.1 Health endpoint and test set up
- Implement `/api/health` endpoint for readiness/liveness checks
  - The route is unauthenticated
  - returns a success response
  - research the best practice foundations setting up tests for the given programming language and framework in VScode. 
  - ensure tests can be run inside the docker container directly from VScode UI 'run' buttons.
  - create a feature test class for the health endpoint

### 3.2 User Authentication Endpoints
- Implement User registration endpoint (`POST /api/register`)
  - Validate username uniqueness
  - Validate password strength (minimum 10 characters, mix of letters, numbers, symbols)
  - Hash password with bcrypt
  - Return user data (without password)
  - The route is unauthenticated
  - Create dedicated feature test class 
- Implement login endpoint (`POST /api/login`)
  - Validate credentials
  - Generate JWT token and refresh token
  - Return tokens
  - The route is unauthenticated
  - Create dedicated feature test class 
- Implement logout endpoint (`POST /api/logout`)
  - Invalidate token (if using token blacklist)
  - Create dedicated feature test class
- Implement token refresh endpoint (`POST /api/token/refresh`)
  - The route is unauthenticated
  - revokes all refresh tokens
  - Create dedicated feature test class 

### 3.3 Authorization & Security
- Create Voter for Game resource
  - Check user is a participant in the game
- Create Voter for User
  - Check user can only access own data
- Configure security.yaml with:
  - Firewall rules
  - Access control
  - Role hierarchy
- Add authentication middleware
- Implement rate limiting for API endpoints (e.g., registration, authentication, game actions) using Symfony's rate limiter to prevent abuse.
- Protect JWT-based API endpoints with standard authentication and authorization checks rather than CSRF tokens, since the app uses `Authorization` header bearer tokens instead of cookie-based auth.

### 3.4 User Management
- Create get user endpoint (`GET /api/user/{user_id}`)
  - Return user data
  - Return games participated in (via UserGame)
  - Users can only see their own information
  - Create dedicated feature test class 
- Update username endpoint (`PATCH /api/user/{user_id}/username`)
  - Validate uniqueness
  - Update user
  - Users can only update their own username
  - Create dedicated feature test class 
- Update password endpoint (`PATCH /api/user/{user_id}/password`)
  - Validate old password
  - Validate new password strength
  - Hash new password
  - Update user
  - Users can only update their own password
  - Create dedicated feature test class 
- Delete game endpoint (`DELETE /api/games/{id}`)
  - Check user is host of the game
  - Soft delete or hard delete game
  - Users can only delete their own games
  - Create dedicated feature test class 

---

## Phase 4: Backend - Question Service Layer

### 4.1 Question Provider Interface
Create abstraction layer for question sources:

- Create `QuestionProviderInterface` with methods:
  - `createQuestionsAndAnswers(rounds, difficulty): void`

### 4.2 Open Trivia DB Implementation
- Create `OpenTriviaDBProvider` implementing `QuestionProviderInterface`
- Implement HTTP client for API calls
- Implement API authentication and refreshing mechanisms if necessary
- Add response parsing and validation
- Handle API errors and rate limiting
- Decode HTML entities in questions/answers
- CreateQuestionsAndAnswers() should:
  - Map the category of the round to the categories available from the api 
  - Map the difficulty of the game to the difficulties available from the api
  - Fetch 10 questions per round 
  - Map API responses to Question and Answer DTOs ready for storing in the database.
- Create unit tests for OpenTriviaDBProvider which mock the responses from the api.

### 4.3 Question Service
- Create `QuestionService` that uses `QuestionProviderInterface`
- Implement method to fetch and store questions for a round
- Shuffle answer options (mix correct with incorrect)
- Add factory pattern for provider selection
- Add configuration for default provider

---

## Phase 5: Backend - Game Logic

### 5.1 Game Management Endpoints
- GameController endpoints. Using a GameService for business Logic. 
  - Start new game (`POST /api/games`)
    - Create Game entity
    - Create UserGame entity for creator (role: host)
    - Create Round entity (1 round, general knowledge)
    - Fetch 5 questions from provider
    - Store questions and answers in database
    - Return game ID and first question
    - The returned question cannot contain the correct answer
  - Get current game state (`GET /api/games/{id}`)
    - Return game progress
    - Return current question
    - Check user is participant
  - Complete game endpoint (`POST /api/games/{id}/complete`)
    - Mark game as completed
  - Get game results (`GET /api/games/{id}/results`)
    - Return total team score
    - Return question breakdown (question, team answer, correct answer, is_correct)
    - Check user is participant
- UserGameController Using a UserGame Service for logic
  - Join game (`POST /api/games/{id}/join`) - for future multi-user
    - Add user as participant
- QuestionController using QuestionService for logic
  - Get next question (`GET /api/games/{id}/rounds/{id}/questions/{id}/next`)
    - Questions are ordered by id
    - Return next question of the round with which answer was previously selected (if any given)
    - Return null if all answered
    - Do not return which answer is correct in the response
    - Check the User has joined the game
  - Get previous question (`GET /api/games/{id}/rounds/{id}/questions/{id}/previous`)
    - Questions are ordered by id within a round
    - Return previous question in the round with which answer was previously selected (if any given)
    - Return null if there is no previous question
    - Do not return which answer is correct in the response
    - Check the User has joined the game
- AnswerController using an AnswerService for logic
  - Submit answer endpoint (`POST /api/games/{id}/rounds/{id}/questions/{id}/answers/{id}/select`)
    - Validate question belongs to game
    - Validate the answer relates to the question
    - Validate the user is a participant of the game
    - Validate the Game is not already completed
    - update user_selection in Answer entity.
    - removed user_selection from other answers to the same question

---

## Phase 6: Backend - Testing

### 6.1 Unit Tests
- Test User entity validation
- Test password hashing
- Test Question provider implementations
- Test QuestionService logic
- Test answer validation logic
- Test score calculation

### 6.2 Integration Tests
- Test registration flow
- Test login/logout flow
- Test game creation and question fetching
- Test answer submission and scoring
- Test game completion
- Test user management
- Test authorization voters

### 6.3 API Tests
- Test all endpoints with valid data
- Test endpoints with invalid data
- Test authentication requirements
- Test authorization rules
- Mock external API calls

---

## Phase 7: Frontend - Setup & Routing

### 7.1 Project Structure
```
src/
├── components/
│   ├── common/
│   ├── auth/
│   ├── lobby/
│   ├── game/
│   └── profile/
├── pages/
│   ├── LoginPage.tsx
│   ├── RegisterPage.tsx
│   ├── LobbyPage.tsx
│   ├── GamePage.tsx
│   ├── ResultsPage.tsx
│   └── ProfilePage.tsx
├── services/
│   ├── api.ts
│   ├── authService.ts
│   └── gameService.ts
├── context/
│   └── AuthContext.tsx
├── hooks/
├── types/
└── utils/
```

### 7.2 Routing Setup
- Configure React Router with routes:
  - `/login` - Login page
  - `/register` - Registration page
  - `/lobby` - Lobby (protected)
  - `/game/:id` - Game play (protected)
  - `/results/:id` - Results (protected)
  - `/user` - User profile (protected)
- Create ProtectedRoute component
- Implement redirect logic for unauthenticated users

### 7.3 API Service Layer
- Create axios instance with base URL
- Add request interceptor for JWT token to attach bearer auth headers
- Add response interceptor for error handling
- Create authService with:
  - `register(username, password)`
  - `login(username, password)`
  - `logout()`
  - `getCurrentUser()`
- Create gameService with:
  - `startGame()`
  - `getGame(id)`
  - `submitAnswer(gameId, questionId, answer)`
  - `getNextQuestion(gameId)`
  - `completeGame(gameId)`
  - `deleteGame(gameId)`
  - `getResults(gameId)`
- Create userService with:
  - `getUser()`
  - `updateUsername(username)`
  - `updatePassword(oldPassword, newPassword)`
  - `deleteGame(gameId)`

---

## Phase 8: Frontend - Authentication

### 8.1 Auth Context Setup
- Create AuthContext using React Context API
  - `user` state
  - `token` state
  - `isAuthenticated` computed value
  - `login` function
  - `logout` function
  - `setUser` function
- Create AuthProvider component
- Persist token in localStorage
- Auto-load user on app initialization
- Create `useAuth` hook for consuming context

### 8.2 Authentication Components
- Create LoginPage component
  - Form with username and password fields
  - Validation
  - Error handling
  - Redirect to lobby on success
- Create RegisterPage component
  - Form with username and password fields
  - Password confirmation
  - Validation
  - Error handling
  - Redirect to login on success
- Create Logout button component
- Add navigation guards for protected routes

---

## Phase 9: Frontend - Lobby

### 9.1 Lobby Page
- Create LobbyPage component
- Display welcome message with username
- Add "Start Game" button
  - Calls API to create new game
  - Redirects to game page with game ID
- Add "View Profile" button
  - Navigates to profile page
- Add "Logout" button
- Add placeholder for future features (team tables)

---

## Phase 10: Frontend - Game Play

### 10.1 Game State Management
- Use local component state (useState) for:
  - `currentGame`
  - `currentQuestion`
  - `selectedAnswer`
  - `questionIndex`
  - `isSubmitted`
  - `answerFeedback`
- Use React Query for API data fetching and caching

### 10.2 Game Components
- Create GamePage component
  - Load game on mount
  - Display current question
  - Manage local game state
- Create Question component
  - Display question text
  - Display category and difficulty
  - Display question number
- Create AnswerOptions component
  - Display all answer choices
  - Handle answer selection
  - Disable after submission
- Create SubmitAnswer button
  - Submit answer to API
  - Show feedback (correct/incorrect)
  - Disable until next question
- Create NextQuestion button
  - Load next question
  - Show after answer submitted
- Create progress indicator
  - Show current question number / total
- Handle last question:
  - Show "View Results" button instead of "Next"
  - Call complete game API
  - Navigate to results page

### 10.3 Game Flow
- Implement question loading logic
- Implement answer submission flow
- Implement navigation between questions
- Add loading states
- Add error handling

---

## Phase 11: Frontend - Results & Profile

### 11.1 Results Page
- Create ResultsPage component
- Display total score (X out of Y)
- Display percentage
- Create QuestionBreakdown component
  - List all questions
  - Show user's answer
  - Show correct answer
  - Indicate correct/incorrect with styling
- Add "Delete" button
  - Call delete API
  - Navigate to lobby

### 11.2 Profile Page
- Create ProfilePage component
- Display user information
- Create GameHistory component
  - List all completed games (using React Query)
  - Show date, score, and details
  - Add delete button for each game
- Create UpdateUsername form
  - Input field and submit button
  - Validation and error handling
- Create UpdatePassword form
  - Old password and new password fields
  - Validation and error handling
- Add navigation back to lobby

---

## Phase 12: Frontend - Testing

### 12.1 Component Tests
- Test LoginPage rendering and form submission
- Test RegisterPage rendering and validation
- Test LobbyPage navigation
- Test GamePage question display
- Test AnswerOptions selection
- Test ResultsPage score display
- Test ProfilePage data display
- Test AuthContext provider and hook

### 12.2 Integration Tests
- Test authentication flow
- Test game play flow
- Test profile update flow
- Mock API responses

### 12.3 E2E Tests (Optional)
- Test complete user journey
- Test error scenarios

---

## Phase 13: Styling & UX

### 13.1 UI Design
- Create consistent color scheme
- Design responsive layouts
- Add loading spinners
- Add error messages/