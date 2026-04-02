import Cookies from "js-cookie";
import { api } from "./api";
import type { LoginResponse, UserResource } from "./types";

const TOKEN_KEY = "jwt_token";
const USER_KEY = "user_data";

export function getToken(): string | undefined {
  return Cookies.get(TOKEN_KEY);
}

export function isAuthenticated(): boolean {
  return !!getToken();
}

export async function login(
  email: string,
  password: string,
): Promise<UserResource> {
  const { token } = await api.post<LoginResponse>("/api/login_check", {
    username: email,
    password,
  });

  Cookies.set(TOKEN_KEY, token, { expires: 1, sameSite: "lax" });

  const payload = JSON.parse(atob(token.split(".")[1]));
  const userId = payload.userId || payload.sub;

  if (!userId) {
    const user: UserResource = {
      id: "",
      email,
      role: payload.roles?.[0] || "ROLE_USER",
      createdAt: new Date().toISOString(),
    };
    localStorage.setItem(USER_KEY, JSON.stringify(user));
    return user;
  }

  const user = await api.get<UserResource>(`/api/users/${userId}`);
  localStorage.setItem(USER_KEY, JSON.stringify(user));
  return user;
}

export function logout(): void {
  Cookies.remove(TOKEN_KEY);
  localStorage.removeItem(USER_KEY);
  window.location.href = "/login";
}

export function getCachedUser(): UserResource | null {
  if (typeof window === "undefined") return null;
  const data = localStorage.getItem(USER_KEY);
  return data ? JSON.parse(data) : null;
}
