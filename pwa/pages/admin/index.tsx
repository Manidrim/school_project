import Head from "next/head";
import { useEffect } from "react";
import { useRouter } from "next/router";
import { useAuth } from "../../contexts/AuthContext";

interface IUser {
  email: string;
}

interface IAdminCardProps {
  title: string;
  description: string;
  buttonText: string;
  href?: string;
  onClick?: () => void;
  colorScheme: 'blue' | 'green' | 'purple';
}

const createLoadingScreen = (): JSX.Element => (
  <div className="min-h-screen flex items-center justify-center">
    <div className="text-xl">Loading...</div>
  </div>
);

const createNavigation = (user: IUser, onLogout: () => void): JSX.Element => (
  <nav className="bg-white shadow-sm border-b border-gray-200">
    <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <div className="flex justify-between h-16">
        <div className="flex items-center">
          <h1 className="text-xl font-semibold text-gray-900">Admin Dashboard</h1>
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

const createAdminCard = ({ title, description, buttonText, href, onClick, colorScheme }: IAdminCardProps): JSX.Element => {
  const colorClasses = {
    blue: {
      bg: 'bg-blue-50',
      border: 'border-blue-200',
      titleText: 'text-blue-900',
      descText: 'text-blue-700',
      button: 'bg-blue-500 hover:bg-blue-600'
    },
    green: {
      bg: 'bg-green-50',
      border: 'border-green-200',
      titleText: 'text-green-900',
      descText: 'text-green-700',
      button: 'bg-green-500 hover:bg-green-600'
    },
    purple: {
      bg: 'bg-purple-50',
      border: 'border-purple-200',
      titleText: 'text-purple-900',
      descText: 'text-purple-700',
      button: 'bg-purple-500 hover:bg-purple-600'
    }
  };

  const colors = colorClasses[colorScheme];

  return (
    <div className={`${colors.bg} border ${colors.border} rounded-lg p-4`}>
      <h3 className={`text-lg font-medium ${colors.titleText} mb-2`}>{title}</h3>
      <p className={`${colors.descText} text-sm mb-3`}>{description}</p>
      {href !== undefined ? (
        <a
          href={href}
          className={`${colors.button} text-white font-medium py-2 px-4 rounded text-sm`}
        >
          {buttonText}
        </a>
      ) : (
        <button
          onClick={onClick}
          className={`${colors.button} text-white font-medium py-2 px-4 rounded text-sm`}
        >
          {buttonText}
        </button>
      )}
    </div>
  );
};

const useAdminLogic = (): {
  user: IUser | null;
  loading: boolean;
  handleLogout: () => void;
} => {
  const { user, loading, logout } = useAuth();
  const router = useRouter();

  useEffect((): void => {
    if (!loading && !user) {
      void router.push("/login");
      return;
    }
  }, [user, loading, router]);

  const handleLogout = (): void => {
    void logout();
  };

  return { user, loading, handleLogout };
};

const createMainContent = (): JSX.Element => (
  <main className="py-6">
    <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <div className="bg-white shadow rounded-lg p-6">
        <h2 className="text-2xl font-bold text-gray-900 mb-4">Welcome to Admin Panel</h2>
        <p className="text-gray-600 mb-4">
          You are successfully authenticated as an administrator.
        </p>
        
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
          {createAdminCard({
            title: "API Documentation",
            description: "Access the API documentation and test endpoints",
            buttonText: "View API Docs",
            href: "/docs",
            colorScheme: "blue"
          })}
          
          {createAdminCard({
            title: "Database Status",
            description: "Monitor database connections and health",
            buttonText: "Check Status",
            onClick: (): void => { /* TODO: implement */ },
            colorScheme: "green"
          })}
          
          {createAdminCard({
            title: "System Info",
            description: "View system information and configuration",
            buttonText: "System Info",
            onClick: (): void => { /* TODO: implement */ },
            colorScheme: "purple"
          })}
        </div>

        <div className="mt-8 p-4 bg-yellow-50 border border-yellow-200 rounded-lg">
          <p className="text-yellow-800 text-sm">
            <strong>Note:</strong> This is a simplified admin interface. 
            The full API Platform Admin will be available once dependency issues are resolved.
          </p>
        </div>
      </div>
    </div>
  </main>
);

const createAdminDashboard = (user: IUser, handleLogout: () => void): JSX.Element => (
  <>
    <Head>
      <title>Admin Dashboard</title>
    </Head>

    <div className="min-h-screen bg-gray-50">
      {createNavigation(user, handleLogout)}
      {createMainContent()}
    </div>
  </>
);

const Admin = (): JSX.Element | null => {
  const { user, loading, handleLogout } = useAdminLogic();

  if (loading) {
    return createLoadingScreen();
  }

  if (!user) {
    return null;
  }

  return createAdminDashboard(user, handleLogout);
};

export default Admin;
