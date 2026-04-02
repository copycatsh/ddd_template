"use client";

import Link from "next/link";
import { motion } from "framer-motion";
import {
  ArrowDown,
  ArrowUp,
  ArrowsLeftRight,
  Wallet,
} from "@phosphor-icons/react";
import { useAuth } from "@/providers/auth-provider";
import { useUserAccounts } from "@/lib/hooks";
import { PageHeader } from "@/components/page-header";
import { Money, CurrencyBadge } from "@/components/money";
import { CardSkeleton } from "@/components/loading";
import { EmptyState } from "@/components/empty-state";
import { StaggerList, StaggerItem } from "@/components/stagger-list";
import { Button } from "@/components/ui/button";

export default function DashboardPage() {
  const { userId, user } = useAuth();
  const { data, isLoading } = useUserAccounts(userId);

  const accounts = data?.accounts || [];

  const totalByUAH = accounts
    .filter((a) => a.currency === "UAH")
    .reduce((sum, a) => sum + parseFloat(a.balance), 0);
  const totalByUSD = accounts
    .filter((a) => a.currency === "USD")
    .reduce((sum, a) => sum + parseFloat(a.balance), 0);

  return (
    <>
      <PageHeader
        title={`Welcome back${user?.email ? `, ${user.email.split("@")[0]}` : ""}`}
        description="Your financial overview"
      />

      {/* Quick actions */}
      <div className="flex items-center gap-2 mb-8">
        {[
          { href: "/deposit", icon: ArrowDown, label: "Deposit" },
          { href: "/withdraw", icon: ArrowUp, label: "Withdraw" },
          { href: "/transfer", icon: ArrowsLeftRight, label: "Transfer" },
        ].map((action) => (
          <Link key={action.href} href={action.href}>
            <Button
              variant="outline"
              size="sm"
              className="gap-1.5 border-white/[0.06] bg-white/[0.02] hover:bg-white/[0.04] active:scale-[0.98] transition-all text-xs"
            >
              <action.icon size={14} />
              {action.label}
            </Button>
          </Link>
        ))}
      </div>

      {/* Totals */}
      {!isLoading && accounts.length > 0 && (
        <motion.div
          initial={{ opacity: 0, y: 12 }}
          animate={{ opacity: 1, y: 0 }}
          transition={{ duration: 0.5, delay: 0.1, ease: [0.32, 0.72, 0, 1] }}
          className="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-8"
        >
          {totalByUAH > 0 && (
            <div className="rounded-2xl border border-white/[0.04] bg-white/[0.02] p-6">
              <p className="text-xs text-muted-foreground mb-1 uppercase tracking-wider">
                Total UAH
              </p>
              <Money
                amount={totalByUAH.toFixed(2)}
                currency="UAH"
                size="lg"
              />
            </div>
          )}
          {totalByUSD > 0 && (
            <div className="rounded-2xl border border-white/[0.04] bg-white/[0.02] p-6">
              <p className="text-xs text-muted-foreground mb-1 uppercase tracking-wider">
                Total USD
              </p>
              <Money
                amount={totalByUSD.toFixed(2)}
                currency="USD"
                size="lg"
              />
            </div>
          )}
        </motion.div>
      )}

      {/* Accounts */}
      <div className="mb-4">
        <h2 className="text-sm font-medium text-muted-foreground uppercase tracking-wider">
          Accounts
        </h2>
      </div>

      {isLoading ? (
        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
          <CardSkeleton />
          <CardSkeleton />
        </div>
      ) : accounts.length === 0 ? (
        <EmptyState
          icon={Wallet}
          title="No accounts yet"
          description="Your accounts will appear here once created"
        />
      ) : (
        <StaggerList>
          <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
            {accounts.map((account) => (
              <StaggerItem key={account.accountId}>
                <Link href={`/accounts/${account.accountId}`}>
                  <div className="group relative rounded-2xl border border-white/[0.04] bg-white/[0.02] p-6 hover:bg-white/[0.04] hover:border-white/[0.08] transition-all duration-300 cursor-pointer">
                    <div className="flex items-start justify-between mb-4">
                      <CurrencyBadge currency={account.currency} />
                      <span className="text-xs text-muted-foreground font-mono">
                        {account.accountId.slice(0, 8)}...
                      </span>
                    </div>
                    <Money
                      amount={account.balance}
                      currency={account.currency}
                      size="lg"
                    />
                    <p className="mt-2 text-xs text-muted-foreground">
                      Opened{" "}
                      {new Date(account.createdAt).toLocaleDateString("en-US", {
                        month: "short",
                        day: "numeric",
                        year: "numeric",
                      })}
                    </p>

                    <div className="absolute top-6 right-6 opacity-0 group-hover:opacity-100 transition-opacity duration-200">
                      <div className="w-6 h-6 rounded-full bg-white/[0.06] flex items-center justify-center">
                        <ArrowsLeftRight
                          size={12}
                          className="text-muted-foreground"
                        />
                      </div>
                    </div>
                  </div>
                </Link>
              </StaggerItem>
            ))}
          </div>
        </StaggerList>
      )}
    </>
  );
}
