"use client";

import {
  createContext,
  useContext,
  useState,
  useEffect,
  useCallback,
  type ReactNode,
} from "react";
import { useRouter } from "next/navigation";
import Cookies from "js-cookie";
import { getCachedUser, logout as authLogout } from "@/lib/auth";
import type { UserResource } from "@/lib/types";

interface AuthContextValue {
  user: UserResource | null;
  userId: string;
  isLoading: boolean;
  setUser: (user: UserResource) => void;
  logout: () => void;
}

const AuthContext = createContext<AuthContextValue | null>(null);

export function AuthProvider({ children }: { children: ReactNode }) {
  const [user, setUser] = useState<UserResource | null>(null);
  const [isLoading, setIsLoading] = useState(true);
  const router = useRouter();

  useEffect(() => {
    const token = Cookies.get("jwt_token");
    if (!token) {
      setIsLoading(false);
      router.replace("/login");
      return;
    }
    const cached = getCachedUser();
    if (cached) {
      setUser(cached);
    }
    setIsLoading(false);
  }, [router]);

  const logout = useCallback(() => {
    authLogout();
  }, []);

  return (
    <AuthContext.Provider
      value={{
        user,
        userId: user?.id || "",
        isLoading,
        setUser,
        logout,
      }}
    >
      {children}
    </AuthContext.Provider>
  );
}

export function useAuth() {
  const ctx = useContext(AuthContext);
  if (!ctx) throw new Error("useAuth must be used within AuthProvider");
  return ctx;
}
