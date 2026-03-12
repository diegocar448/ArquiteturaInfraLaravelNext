import { create } from "zustand";
import { persist } from "zustand/middleware";
import { ApiError } from "@/lib/api";

// Cookie separado para nao conflitar com o admin
function setClientTokenCookie(token: string) {
    document.cookie = `client_token=${token}; path=/; max-age=${60 * 60}; SameSite=Lax`;
}

function removeClientTokenCookie() {
    document.cookie = "client_token=; path=/; max-age=0";
}

interface ClientUser {
    id: number;
    uuid: string;
    name: string;
    email: string;
}

interface ClientAuthState {
    token: string | null;
    client: ClientUser | null;
    isAuthenticated: boolean;
    isLoading: boolean;
    login: (email: string, password: string) => Promise<void>;
    register: (name: string, email: string, password: string) => Promise<void>;
    logout: () => Promise<void>;
    fetchClient: () => Promise<void>;
    clear: () => void;
}

interface ClientLoginResponse {
    access_token: string;
    token_type: string;
    expires_in: number;
    client: ClientUser;
}

interface ClientMeResponse {
    data: ClientUser;
}

const API_URL = process.env.NEXT_PUBLIC_API_URL || "/api";

/** Fetch wrapper que usa o token do cliente (guard client) */
async function clientApi<T>(
    endpoint: string,
    token: string | null,
    options: RequestInit = {},
): Promise<T> {
    const headers: Record<string, string> = {
        "Content-Type": "application/json",
        Accept: "application/json",
        ...(options.headers as Record<string, string>),
    };

    if (token) {
        headers.Authorization = `Bearer ${token}`;
    }

    const response = await fetch(`${API_URL}${endpoint}`, {
        ...options,
        headers,
    });

    if (!response.ok) {
        const data = await response.json().catch(() => null);
        throw new ApiError(
            response.status,
            data?.message || "Erro na requisicao",
            data,
        );
    }

    return response.json();
}

export const useClientAuthStore = create<ClientAuthState>()(
    persist(
        (set, get) => ({
            token: null,
            client: null,
            isAuthenticated: false,
            isLoading: false,

            login: async (email: string, password: string) => {
                set({ isLoading: true });
                try {
                    const response = await clientApi<ClientLoginResponse>(
                        "/v1/client/auth/login",
                        null,
                        {
                            method: "POST",
                            body: JSON.stringify({ email, password }),
                        },
                    );

                    setClientTokenCookie(response.access_token);
                    set({
                        token: response.access_token,
                        client: response.client,
                        isAuthenticated: true,
                        isLoading: false,
                    });
                } catch (error) {
                    set({ isLoading: false });
                    throw error;
                }
            },

            register: async (
                name: string,
                email: string,
                password: string,
            ) => {
                set({ isLoading: true });
                try {
                    const response = await clientApi<ClientLoginResponse>(
                        "/v1/client/auth/register",
                        null,
                        {
                            method: "POST",
                            body: JSON.stringify({ name, email, password }),
                        },
                    );

                    setClientTokenCookie(response.access_token);
                    set({
                        token: response.access_token,
                        client: response.client,
                        isAuthenticated: true,
                        isLoading: false,
                    });
                } catch (error) {
                    set({ isLoading: false });
                    throw error;
                }
            },

            fetchClient: async () => {
                try {
                    const response = await clientApi<ClientMeResponse>(
                        "/v1/client/auth/me",
                        get().token,
                    );
                    set({ client: response.data });
                } catch {
                    get().clear();
                }
            },

            logout: async () => {
                try {
                    await clientApi("/v1/client/auth/logout", get().token, {
                        method: "POST",
                    });
                } catch {
                    // Limpar mesmo se der erro no backend
                }
                get().clear();
            },

            clear: () => {
                removeClientTokenCookie();
                set({
                    token: null,
                    client: null,
                    isAuthenticated: false,
                });
            },
        }),
        {
            name: "client-auth-storage",
            partialize: (state) => ({ token: state.token }),
        },
    ),
);
