import { Link, useNavigate } from 'react-router-dom';
import { useAuth } from '../hooks/useAuth';
import StartGameForm from '../components/lobby/StartGameForm';

export default function LobbyPage() {
  const { user, logout } = useAuth();
  const navigate = useNavigate();

  async function handleLogout() {
    await logout();
    navigate('/login');
  }

  return (
    <div className="container mx-auto max-w-lg p-8">
      <h1 className="text-3xl font-bold">
        Welcome{user ? `, ${user.username}` : ''}!
      </h1>
      <p className="text-base-content/60 mt-1 mb-6">
        Ready for a quiz? Start a new game below.
      </p>

      <StartGameForm />

      <div className="flex gap-2 mt-6">
        <Link to="/user" className="btn btn-outline btn-sm">
          View Profile
        </Link>
        <button onClick={handleLogout} className="btn btn-outline btn-sm">
          Logout
        </button>
      </div>

      <div className="divider mt-8" />
      <div className="text-base-content/40 text-sm">
        <p>More features coming soon: team tables, multiplayer lobbies, and leaderboards.</p>
      </div>
    </div>
  );
}
