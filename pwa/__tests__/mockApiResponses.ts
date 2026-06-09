const LD_JSON = 'application/ld+json'

export const ldJsonHeaders = {
  get: (name: string): string | null =>
    name.toLowerCase() === 'content-type' ? LD_JSON : null,
}

export function mockAdminDashboardFetchPayload(
  email: string,
  roles: string[] = ['ROLE_ADMIN'],
): {
  ok: boolean
  headers: { get: (name: string) => string | null }
  json: () => Promise<Record<string, unknown>>
} {
  return {
    ok: true,
    headers: ldJsonHeaders,
    json: () =>
      Promise.resolve({
        title: 'Admin Dashboard',
        user: { email, roles },
      }),
  }
}
