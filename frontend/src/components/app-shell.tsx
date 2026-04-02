"use client";

import Link from "next/link";
import { usePathname } from "next/navigation";
import { motion } from "framer-motion";
import {
  House,
  ArrowsLeftRight,
  ArrowDown,
  ArrowUp,
  Bell,
  User,
  SignOut,
} from "@phosphor-icons/react";
import { useAuth } from "@/providers/auth-provider";
import { cn } from "@/lib/utils";

const NAV_ITEMS = [
  { href: "/dashboard", label: "Overview", icon: House },
  { href: "/transfer", label: "Transfer", icon: ArrowsLeftRight },
  { href: "/deposit", label: "Deposit", icon: ArrowDown },
  { href: "/withdraw", label: "Withdraw", icon: ArrowUp },
  { href: "/notifications", label: "Alerts", icon: Bell },
  { href: "/profile", label: "Profile", icon: User },
];

export function AppShell({ children }: { children: React.ReactNode }) {
  const pathname = usePathname();
  const { user, logout, isLoading } = useAuth();

  if (isLoading) {
    return (
      <div className="min-h-[100dvh] flex items-center justify-center">
        <div className="w-6 h-6 border-2 border-emerald border-t-transparent rounded-full animate-spin" />
      </div>
    );
  }

  return (
    <div className="min-h-[100dvh] flex flex-col">
      <header className="sticky top-0 z-40 border-b border-border/50 bg-background/80 backdrop-blur-xl">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 h-16 flex items-center justify-between">
          <Link href="/dashboard" className="flex items-center gap-2.5">
            <div className="w-8 h-8 rounded-lg bg-emerald/10 border border-emerald/20 flex items-center justify-center">
              <span className="text-emerald font-mono text-sm font-bold">V</span>
            </div>
            <span className="font-semibold tracking-tight text-foreground/90">
              Vault
            </span>
          </Link>

          <nav className="hidden md:flex items-center gap-1">
            {NAV_ITEMS.map((item) => {
              const isActive = pathname === item.href;
              return (
                <Link
                  key={item.href}
                  href={item.href}
                  className={cn(
                    "relative px-3 py-1.5 text-sm font-medium rounded-lg transition-colors duration-200",
                    isActive
                      ? "text-foreground"
                      : "text-muted-foreground hover:text-foreground/80",
                  )}
                >
                  {isActive && (
                    <motion.div
                      layoutId="nav-pill"
                      className="absolute inset-0 bg-white/[0.04] border border-white/[0.06] rounded-lg"
                      transition={{
                        type: "spring",
                        stiffness: 400,
                        damping: 30,
                      }}
                    />
                  )}
                  <span className="relative flex items-center gap-1.5">
                    <item.icon size={16} weight={isActive ? "fill" : "regular"} />
                    {item.label}
                  </span>
                </Link>
              );
            })}
          </nav>

          <div className="flex items-center gap-3">
            {user && (
              <span className="hidden sm:block text-xs text-muted-foreground font-mono">
                {user.email}
              </span>
            )}
            <button
              onClick={logout}
              className="p-2 text-muted-foreground hover:text-foreground transition-colors rounded-lg hover:bg-white/[0.04]"
            >
              <SignOut size={18} />
            </button>
          </div>
        </div>

        {/* Mobile nav */}
        <nav className="md:hidden flex items-center gap-1 px-4 pb-3 overflow-x-auto no-scrollbar">
          {NAV_ITEMS.map((item) => {
            const isActive = pathname === item.href;
            return (
              <Link
                key={item.href}
                href={item.href}
                className={cn(
                  "flex-shrink-0 px-3 py-1.5 text-xs font-medium rounded-full transition-colors",
                  isActive
                    ? "bg-emerald/10 text-emerald border border-emerald/20"
                    : "text-muted-foreground hover:text-foreground/80",
                )}
              >
                {item.label}
              </Link>
            );
          })}
        </nav>
      </header>

      <main className="flex-1 w-full max-w-7xl mx-auto px-4 sm:px-6 py-8">
        {children}
      </main>
    </div>
  );
}
