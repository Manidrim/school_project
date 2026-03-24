import Head from "next/head";
import { useEffect } from "react";
import { useRouter } from "next/router";
import { useAuth } from "../../contexts/AuthContext";
import AdminCard, { type IAdminCardProps } from "../../components/admin/AdminCard";
import AdminNavigation from "../../components/admin/AdminNavigation";
import LoadingScreen from "../../components/common/LoadingScreen";

const ADMIN_CARDS: IAdminCardProps[] = [
  {
    title: "Articles Management",
    description: "Create, edit and manage blog articles",
    buttonText: "Manage Articles",
    href: "/admin/articles",
    colorScheme: "indigo"
  },
  {
    title: "API Documentation",
    description: "Access the API documentation and test endpoints",
    buttonText: "View API Docs",
    href: "/docs",
    colorScheme: "blue"
  },
  {
    title: "Database Status",
    description: "Monitor database connections and health",
    buttonText: "Check Status",
    onClick: (): void => { /* TODO: implement */ },
    colorScheme: "green"
  },
  {
    title: "System Info",
    description: "View system information and configuration",
    buttonText: "System Info",
    onClick: (): void => { /* TODO: implement */ },
    colorScheme: "purple"
  }
];

const useAdminLogic = (): {
  user: { email: string } | null;
  loading: boolean;
  handleLogout: () => void;
} => {
  const { user, loading, logout } = useAuth();
  const router = useRouter();

  useEffect(() => {
    if (!loading && !user) {
      void router.push("/login");
    }
  }, [user, loading, router]);

  const handleLogout = (): void => {
    void logout();
  };

  return { user, loading, handleLogout };
};

const Admin = (): JSX.Element | null => {
  const { user, loading, handleLogout } = useAdminLogic();

  if (loading) {
    return <LoadingScreen />;
  }

  if (!user) {
    return null;
  }

  return (
    <>
      <Head>
        <title>Admin Dashboard</title>
      </Head>
      <div className="min-h-screen bg-gray-50">
        <AdminNavigation 
          user={user} 
          onLogout={handleLogout} 
          title="Admin Dashboard"
        />
        <main className="py-6">
          <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div className="bg-white shadow rounded-lg p-6">
              <h2 className="text-2xl font-bold text-gray-900 mb-4">Welcome to Admin Panel</h2>
              <p className="text-gray-600 mb-4">
                You are successfully authenticated as an administrator.
              </p>
              
              <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                {ADMIN_CARDS.map((card, index) => (
                  <AdminCard key={index} {...card} />
                ))}
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
      </div>
    </>
  );
};

export default Admin;
