import Head from "next/head";
import { useState, useEffect } from "react";
import { useRouter } from "next/router";
import { useAuth } from "../contexts/AuthContext";

interface IErrorDisplayProps {
  error: string;
}

interface IEmailInputProps {
  email: string;
  onChange: (email: string) => void;
}

interface IPasswordInputProps {
  password: string;
  onChange: (password: string) => void;
}

interface ISubmitButtonProps {
  loading: boolean;
}

const createErrorDisplay = ({ error }: IErrorDisplayProps): JSX.Element => (
  <div className="rounded-md bg-red-50 p-4">
    <div className="text-sm text-red-700">{error}</div>
  </div>
);

const createEmailInput = ({ email, onChange }: IEmailInputProps): JSX.Element => (
  <div>
    <label htmlFor="email-address" className="sr-only">
      Email address
    </label>
    <input
      id="email-address"
      name="email"
      type="email"
      autoComplete="email"
      required
      className="appearance-none rounded-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-t-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 focus:z-10 sm:text-sm"
      placeholder="Email address"
      value={email}
      onChange={(e): void => onChange(e.target.value)}
    />
  </div>
);

const createPasswordInput = ({ password, onChange }: IPasswordInputProps): JSX.Element => (
  <div>
    <label htmlFor="password" className="sr-only">
      Password
    </label>
    <input
      id="password"
      name="password"
      type="password"
      autoComplete="current-password"
      required
      className="appearance-none rounded-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-b-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 focus:z-10 sm:text-sm"
      placeholder="Password"
      value={password}
      onChange={(e): void => onChange(e.target.value)}
    />
  </div>
);

const createSubmitButton = ({ loading }: ISubmitButtonProps): JSX.Element => (
  <button
    type="submit"
    disabled={loading}
    className="group relative w-full flex justify-center py-2 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-50"
  >
    {loading ? "Signing in..." : "Sign in"}
  </button>
);

const useLoginLogic = (): {
  email: string;
  setEmail: (email: string) => void;
  password: string;
  setPassword: (password: string) => void;
  error: string;
  loading: boolean;
  handleSubmit: (e: React.FormEvent) => Promise<void>;
} => {
  const [email, setEmail] = useState("");
  const [password, setPassword] = useState("");
  const [error, setError] = useState("");
  const [loading, setLoading] = useState(false);
  const router = useRouter();
  const { login: authLogin, user } = useAuth();

  useEffect((): void => {
    if (user) {
      void router.push("/admin");
    }
  }, [user, router]);

  const handleSubmit = async (e: React.FormEvent): Promise<void> => {
    e.preventDefault();
    setLoading(true);
    setError("");

    const success = await authLogin(email, password);
    
    if (success) {
      void router.push("/admin");
    } else {
      setError("Invalid credentials");
    }
    
    setLoading(false);
  };

  return { email, setEmail, password, setPassword, error, loading, handleSubmit };
};

const createLoginHeader = (): JSX.Element => (
  <div>
    <h2 className="mt-6 text-center text-3xl font-extrabold text-gray-900">
      Sign in to admin panel
    </h2>
  </div>
);

const createLoginForm = (loginData: ReturnType<typeof useLoginLogic>): JSX.Element => {
  const { email, setEmail, password, setPassword, error, loading, handleSubmit } = loginData;
  
  return (
    <form className="mt-8 space-y-6" onSubmit={(e): void => { void handleSubmit(e); }}>
      {error && createErrorDisplay({ error })}
      
      <div className="rounded-md shadow-sm -space-y-px">
        {createEmailInput({ email, onChange: setEmail })}
        {createPasswordInput({ password, onChange: setPassword })}
      </div>

      <div>
        {createSubmitButton({ loading })}
      </div>
    </form>
  );
};

const Login = (): JSX.Element => {
  const loginData = useLoginLogic();

  return (
    <>
      <Head>
        <title>Admin Login</title>
      </Head>

      <div className="min-h-screen flex items-center justify-center bg-gray-50 py-12 px-4 sm:px-6 lg:px-8">
        <div className="max-w-md w-full space-y-8">
          {createLoginHeader()}
          {createLoginForm(loginData)}
        </div>
      </div>
    </>
  );
};

export default Login; 