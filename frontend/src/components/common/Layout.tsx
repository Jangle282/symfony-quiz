import { Link, Outlet, useNavigate } from 'react-router-dom';
import { useAuth } from '../../hooks/useAuth';

export default function Layout() {
  const navigate = useNavigate();
  const { user, logout } = useAuth();

  async function handleLogout() {
    await logout();
    navigate('/login');
  }

  return (
    <div className="min-h-screen flex flex-col">
      <div className="navbar bg-base-200">
        <div className="flex-1">
          <Link to="/lobby" className="btn btn-ghost text-xl">
            Pub Quiz
          </Link>
        </div>
        <div className="flex-none gap-2">
          {user && (
            <span className="text-sm text-base-content/70">
              {user.username}
            </span>
          )}
          <Link to="/lobby" className="btn btn-ghost btn-sm">
            Home
          </Link>
          <Link to="/user" className="btn btn-ghost btn-sm">
            Profile
          </Link>
          <button onClick={handleLogout} className="btn btn-ghost btn-sm">
            Logout
          </button>
        </div>
      </div>
      <main className="flex-1">
        <Outlet />
      </main>
    </div>
  );
}
