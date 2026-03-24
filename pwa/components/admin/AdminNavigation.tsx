import Link from "next/link";

interface IUser {
  email: string;
}

interface IAdminNavigationProps {
  user: IUser;
  onLogout: () => void;
  title: string;
  showBackLink?: boolean;
}

const AdminNavigation = ({ 
  user, 
  onLogout, 
  title, 
  showBackLink = false 
}: IAdminNavigationProps): JSX.Element => (
  <nav className="bg-white shadow-sm border-b border-gray-200">
    <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <div className="flex justify-between h-16">
        <div className="flex items-center space-x-4">
          <h1 className="text-xl font-semibold text-gray-900">{title}</h1>
          {showBackLink && (
            <Link 
              href="/admin" 
              className="text-blue-600 hover:text-blue-800 text-sm"
            >
              ← Back to Dashboard
            </Link>
          )}
        </div>
        <div className="flex items-center space-x-4">
          <span className="text-sm text-gray-500">Welcome, {user.email}</span>
          <button
            onClick={onLogout}
            className="bg-red-500 hover:bg-red-700 text-white font-bold py-2 px-4 rounded text-sm"
          >
            Logout
          </button>
        </div>
      </div>
    </div>
  </nav>
);

export default AdminNavigation;