type RequestOptions = RequestInit & {
  isServer?: boolean;
};

// Client-side: vai pelo Nginx (mesma origem)
const PUBLIC_API_URL = process.env.NEXT_PUBLIC_API_URL || "/api";

// Server-side: vai pela rede Docker interna
const INTERNAL_API_URL =
  process.env.INTERNAL_API_URL || "http://nginx:80/api";

export class ApiError extends Error {
  constructor(
    public status: number,
    message: string,
    public data?: unknown,
  ) {
    super(message);
    this.name = "ApiError";
  }
}

export async function apiClient<T>(
  endpoint: string,
  options: RequestOptions = {},
): Promise<T> {
  const { isServer = false, headers: customHeaders, ...fetchOptions } = options;
  const baseUrl = isServer ? INTERNAL_API_URL : PUBLIC_API_URL;

  const headers: Record<string, string> = {
    "Content-Type": "application/json",
    Accept: "application/json",
    ...(customHeaders as Record<string, string>),
  };

  // Adicionar token JWT se disponivel (client-side)
  if (typeof window !== "undefined") {
    let token = localStorage.getItem("token");

    // Fallback: ler do Zustand persist (auth-storage)
    if (!token) {
      try {
        const authStorage = localStorage.getItem("auth-storage");
        if (authStorage) {
          const parsed = JSON.parse(authStorage);
          token = parsed?.state?.token || null;
        }
      } catch {
        // ignore parse errors
      }
    }

    if (token) {
      headers.Authorization = `Bearer ${token}`;
    }
  }

  const response = await fetch(`${baseUrl}${endpoint}`, {
    ...fetchOptions,
    headers,
  });

  if (!response.ok) {
    const data = await response.json().catch(() => null);
    throw new ApiError(response.status, data?.message || "Erro na requisicao", data);
  }

  return response.json();
}