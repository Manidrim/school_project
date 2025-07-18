import Head from "next/head";
import { useEffect, useState } from "react";
import { useRouter } from "next/router";
import { useAuth } from "../../contexts/AuthContext";

const Admin = () => {
  const [DynamicAdmin, setDynamicAdmin] = useState(<p>Loading...</p>);
  const { user, loading, logout } = useAuth();
  const router = useRouter();

  useEffect(() => {
    if (!loading && !user) {
      router.push("/login");
      return;
    }

    if (user) {
      (async () => {
        const HydraAdmin = (await import("@api-platform/admin")).HydraAdmin;
        setDynamicAdmin(<HydraAdmin entrypoint={window.origin}></HydraAdmin>);
      })();
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
        <title>API Platform Admin</title>
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
          {DynamicAdmin}
        </main>
      </div>
    </>
  );
};

export default Admin;
