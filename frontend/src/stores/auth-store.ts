import { create } from "zustand";
import { persist } from "zustand/middleware";
import { apiClient, ApiError } from "@/lib/api";

// Sync token com cookie para o middleware (server-side) conseguir ler
function setTokenCookie(token: string) {
  document.cookie = `token=${token}; path=/; max-age=${60 * 60}; SameSite=Lax`;
}

function removeTokenCookie() {
  document.cookie = "token=; path=/; max-age=0";
}

interface User {
  id: number;
  name: string;
  email: string;
  created_at: string;
}

interface AuthState {
  token: string | null;
  user: User | null;
  isAuthenticated: boolean;
  isLoading: boolean;
  login: (email: string, password: string) => Promise<void>;
  logout: () => Promise<void>;
  fetchUser: () => Promise<void>;
  setToken: (token: string) => void;
  clear: () => void;
}

interface LoginResponse {
  access_token: string;
  token_type: string;
  expires_in: number;
}

interface UserResponse {
  data: User;
}

export const useAuthStore = create<AuthState>()(
  persist(
    (set, get) => ({
      token: null,
      user: null,
      isAuthenticated: false,
      isLoading: false,

      setToken: (token: string) => {
        localStorage.setItem("token", token);
        setTokenCookie(token);
        set({ token, isAuthenticated: true });
      },

      login: async (email: string, password: string) => {
        set({ isLoading: true });
        try {
          const response = await apiClient<LoginResponse>("/v1/auth/login", {
            method: "POST",
            body: JSON.stringify({ email, password }),
          });

          localStorage.setItem("token", response.access_token);
          setTokenCookie(response.access_token);
          set({
            token: response.access_token,
            isAuthenticated: true,
            isLoading: false,
          });

          // Buscar dados do usuario
          await get().fetchUser();
        } catch (error) {
          set({ isLoading: false });
          throw error;
        }
      },

      fetchUser: async () => {
        try {
          const response = await apiClient<UserResponse>("/v1/auth/me");
          set({ user: response.data });
        } catch {
          get().clear();
        }
      },

      logout: async () => {
        try {
          await apiClient("/v1/auth/logout", { method: "POST" });
        } catch {
          // Limpar mesmo se der erro no backend
        }
        get().clear();
      },

      clear: () => {
        localStorage.removeItem("token");
        removeTokenCookie();
        set({ token: null, user: null, isAuthenticated: false });
      },
    }),
    {
      name: "auth-storage",
      partialize: (state) => ({ token: state.token }),
    },
  ),
);