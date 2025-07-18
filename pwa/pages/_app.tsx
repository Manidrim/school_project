import "../styles/globals.css"
import Layout from "../components/common/Layout"
import { AuthProvider } from "../contexts/AuthContext"
import type { AppProps } from "next/app"
import type { DehydratedState } from "react-query"

function MyApp({ Component, pageProps }: AppProps<{dehydratedState: DehydratedState}>) {
  return (
    <AuthProvider>
      <Layout dehydratedState={pageProps.dehydratedState}>
        <Component {...pageProps} />
      </Layout>
    </AuthProvider>
  )
}

export default MyApp
