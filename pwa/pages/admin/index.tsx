import Head from "next/head";
import { useEffect } from "react";
import { useRouter } from "next/router";
import { useAuth } from "../../contexts/AuthContext";

const Admin = () => {
  const { user, loading, logout } = useAuth();
  const router = useRouter();

  useEffect(() => {
    if (!loading && !user) {
      router.push("/login");
      return;
    }
  }, [user, loading, router]);

  const handleLogout = () => {
    logout();
  };

  if (loading) {
    return (
      <div className="min-h-screen flex items-center justify-center">
        <div className="text-xl">Loading...</div>
      </div>
    );
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
        <nav className="bg-white shadow-sm border-b border-gray-200">
          <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div className="flex justify-between h-16">
              <div className="flex items-center">
                <h1 className="text-xl font-semibold text-gray-900">Admin Dashboard</h1>
              </div>
              <div className="flex items-center space-x-4">
                <span className="text-sm text-gray-500">Welcome, {user.email}</span>
                <button
                  onClick={handleLogout}
                  className="bg-red-500 hover:bg-red-700 text-white font-bold py-2 px-4 rounded text-sm"
                >
                  Logout
                </button>
              </div>
            </div>
          </div>
        </nav>

        <main className="py-6">
          <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div className="bg-white shadow rounded-lg p-6">
              <h2 className="text-2xl font-bold text-gray-900 mb-4">Welcome to Admin Panel</h2>
              <p className="text-gray-600 mb-4">
                You are successfully authenticated as an administrator.
              </p>
              
              <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <div className="bg-blue-50 border border-blue-200 rounded-lg p-4">
                  <h3 className="text-lg font-medium text-blue-900 mb-2">API Documentation</h3>
                  <p className="text-blue-700 text-sm mb-3">Access the API documentation and test endpoints</p>
                  <a
                    href="/docs"
                    className="bg-blue-500 hover:bg-blue-600 text-white font-medium py-2 px-4 rounded text-sm"
                  >
                    View API Docs
                  </a>
                </div>

                <div className="bg-green-50 border border-green-200 rounded-lg p-4">
                  <h3 className="text-lg font-medium text-green-900 mb-2">Database Status</h3>
                  <p className="text-green-700 text-sm mb-3">Monitor database connections and health</p>
                  <button className="bg-green-500 hover:bg-green-600 text-white font-medium py-2 px-4 rounded text-sm">
                    Check Status
                  </button>
                </div>

                <div className="bg-purple-50 border border-purple-200 rounded-lg p-4">
                  <h3 className="text-lg font-medium text-purple-900 mb-2">System Info</h3>
                  <p className="text-purple-700 text-sm mb-3">View system information and configuration</p>
                  <button className="bg-purple-500 hover:bg-purple-600 text-white font-medium py-2 px-4 rounded text-sm">
                    System Info
                  </button>
                </div>
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
