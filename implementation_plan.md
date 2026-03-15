# Pub Quiz Application - Implementation Plan

## Project Overview
A pub quiz game application using Open Trivia Database API with user accounts, game management, and score tracking.

## Tech Stack
- **Backend**: Symfony (PHP) with PostgreSQL
- **Frontend**: React with TypeScript
- **Infrastructure**: Docker & Docker Compose
- **API**: Open Trivia Database (https://opentdb.com/)

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
- Set up CORS for React frontend
- Configure JWT authentication

### 1.3 React Frontend Setup
- Initialize React app with TypeScript using Vite
- Install dependencies:
  - `react-router-dom`
  - `axios`
  - `@tanstack/react-query` (for API state management)
  - `zustand` or `redux-toolkit` (for global state)
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
- id (uuid, primary key)
- username (string, unique)
- password (string, hashed)
- created_at (datetime)
- updated_at (datetime)
```

#### Game Entity
```
- id (uuid, primary key)
- user_id (foreign key to User)
- total_score (integer)
- total_questions (integer)
- started_at (datetime)
- completed_at (datetime, nullable)
- saved (boolean, default false)
- created_at (datetime)
```

#### Round Entity
```
- id (uuid, primary key)
- game_id (foreign key to Game)
- round_number (integer)
- category (string)
- difficulty (string, nullable)
- created_at (datetime)
```

#### Question Entity
```
- id (uuid, primary key)
- round_id (foreign key to Round)
- question_text (text)
- correct_answer (string)
- incorrect_answers (json)
- category (string)
- difficulty (string)
- question_type (string)
- order_number (integer)
```

#### Answer Entity
```
- id (uuid, primary key)
- question_id (foreign key to Question)
- user_answer (string)
- is_correct (boolean)
- answered_at (datetime)
```

### 2.2 Doctrine Entity Implementation
- Create User entity with validation
- Create Game entity with relationships
- Create Round entity
- Create Question entity
- Create Answer entity
- Create migrations for all entities
- Set up entity relationships and cascade operations

---

## Phase 3: Backend - Authentication & Authorization

### 3.1 User Authentication
- Implement User registration endpoint (`POST /api/register`)
  - Validate username uniqueness
  - Hash password with bcrypt
  - Return user data (without password)
- Implement login endpoint (`POST /api/login`)
  - Validate credentials
  - Generate JWT token
  - Return token and user data
- Implement logout endpoint (`POST /api/logout`)
  - Invalidate token (if using token blacklist)
- Implement token refresh endpoint (`POST /api/token/refresh`)

### 3.2 Authorization & Security
- Create Voter for Game resource
  - Check user owns the game
- Create Voter for User profile
  - Check user can only access own profile
- Configure security.yaml with:
  - Firewall rules
  - Access control
  - Role hierarchy
- Add authentication middleware
- Implement CSRF protection where needed

### 3.3 User Profile Management
- Create profile endpoint (`GET /api/profile`)
  - Return user data
  - Return game history
- Update username endpoint (`PATCH /api/profile/username`)
  - Validate uniqueness
  - Update user
- Update password endpoint (`PATCH /api/profile/password`)
  - Validate old password
  - Hash new password
  - Update user
- Delete game endpoint (`DELETE /api/games/{id}`)
  - Check authorization
  - Soft delete or hard delete game

---

## Phase 4: Backend - Question Service Layer

### 4.1 Question Provider Interface
Create abstraction layer for question sources:

- Create `QuestionProviderInterface` with methods:
  - `fetchQuestions(category, difficulty, amount): Question[]`
  - `getCategories(): Category[]`
  - `validateResponse(response): boolean`
- Create `QuestionDTO` for standardized question format
- Create `CategoryDTO` for category information

### 4.2 Open Trivia DB Implementation
- Create `OpenTriviaDBProvider` implementing `QuestionProviderInterface`
- Implement HTTP client for API calls
- Add response parsing and validation
- Handle API errors and rate limiting
- Decode HTML entities in questions/answers
- Map API response to `QuestionDTO`
- Add caching layer (optional, for categories)

### 4.3 Question Service
- Create `QuestionService` that uses `QuestionProviderInterface`
- Implement method to fetch and store questions for a round
- Shuffle answer options (mix correct with incorrect)
- Add factory pattern for provider selection
- Add configuration for default provider

---

## Phase 5: Backend - Game Logic

### 5.1 Game Management Endpoints
- Start new game (`POST /api/games`)
  - Create Game entity
  - Create Round entity (1 round, general knowledge)
  - Fetch 5 questions from provider
  - Store questions in database
  - Return game ID and first question
- Get current game state (`GET /api/games/{id}`)
  - Return game progress
  - Return current question
  - Check authorization
- Get next question (`GET /api/games/{id}/questions/next`)
  - Return next unanswered question
  - Return null if all answered

### 5.2 Answer Submission
- Submit answer endpoint (`POST /api/games/{id}/answers`)
  - Accept question_id and user_answer
  - Validate question belongs to game
  - Check if already answered
  - Create Answer entity
  - Calculate if correct
  - Update game score
  - Return correct/incorrect feedback

### 5.3 Game Completion
- Complete game endpoint (`POST /api/games/{id}/complete`)
  - Mark game as completed
  - Calculate final score
  - Return results summary
- Save game score (`POST /api/games/{id}/save`)
  - Mark game as saved
  - Return confirmation
- Discard game (`DELETE /api/games/{id}/discard`)
  - Delete unsaved game
  - Check authorization

### 5.4 Results
- Get game results (`GET /api/games/{id}/results`)
  - Return total score
  - Return question breakdown (question, user answer, correct answer, is_correct)
  - Check authorization

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
- Test profile management
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
├── hooks/
├── types/
├── store/
└── utils/
```

### 7.2 Routing Setup
- Configure React Router with routes:
  - `/login` - Login page
  - `/register` - Registration page
  - `/lobby` - Lobby (protected)
  - `/game/:id` - Game play (protected)
  - `/results/:id` - Results (protected)
  - `/profile` - User profile (protected)
- Create ProtectedRoute component
- Implement redirect logic for unauthenticated users

### 7.3 API Service Layer
- Create axios instance with base URL
- Add request interceptor for JWT token
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
  - `saveGame(gameId)`
  - `discardGame(gameId)`
  - `getResults(gameId)`
- Create profileService with:
  - `getProfile()`
  - `updateUsername(username)`
  - `updatePassword(oldPassword, newPassword)`
  - `deleteGame(gameId)`

---

## Phase 8: Frontend - Authentication

### 8.1 State Management
- Create auth store (Zustand/Redux):
  - `user` state
  - `token` state
  - `isAuthenticated` computed
  - `login` action
  - `logout` action
  - `setUser` action
- Persist token in localStorage
- Auto-load user on app initialization

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
- Create game store:
  - `currentGame` state
  - `currentQuestion` state
  - `selectedAnswer` state
  - `questionIndex` state
  - Actions for state updates

### 10.2 Game Components
- Create GamePage component
  - Load game on mount
  - Display current question
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
- Add "Save Score" button
  - Call save API
  - Show confirmation
  - Navigate to lobby or profile
- Add "Discard" button
  - Call discard API
  - Navigate to lobby

### 11.2 Profile Page
- Create ProfilePage component
- Display user information
- Create GameHistory component
  - List all saved games
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
- Add error messages/toasts
- Add success confirmations
- Style forms with validation feedback

### 13.2 Accessibility
- Add proper ARIA labels
- Ensure keyboard navigation
- Add focus indicators
- Test with screen readers