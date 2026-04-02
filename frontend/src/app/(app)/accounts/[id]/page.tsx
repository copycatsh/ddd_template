"use client";

import { use } from "react";
import Link from "next/link";
import { motion } from "framer-motion";
import {
  ArrowDown,
  ArrowUp,
  ArrowsLeftRight,
  Receipt,
  CaretLeft,
} from "@phosphor-icons/react";
import { useAccountBalance, useAccountTransactions } from "@/lib/hooks";
import { PageHeader } from "@/components/page-header";
import { Money, CurrencyBadge } from "@/components/money";
import { CardSkeleton, TableSkeleton } from "@/components/loading";
import { EmptyState } from "@/components/empty-state";
import { StaggerList, StaggerItem } from "@/components/stagger-list";
import { Button } from "@/components/ui/button";
import { Badge } from "@/components/ui/badge";

const TYPE_ICONS: Record<string, typeof ArrowDown> = {
  DEPOSIT: ArrowDown,
  WITHDRAWAL: ArrowUp,
  TRANSFER: ArrowsLeftRight,
};

const STATUS_COLORS: Record<string, string> = {
  COMPLETED: "bg-emerald/10 text-emerald border-emerald/20",
  PENDING: "bg-amber-500/10 text-amber-400 border-amber-500/20",
  FAILED: "bg-destructive/10 text-destructive border-destructive/20",
};

export default function AccountDetailPage({
  params,
}: {
  params: Promise<{ id: string }>;
}) {
  const { id } = use(params);
  const { data: account, isLoading: loadingAccount } = useAccountBalance(id);
  const { data: txData, isLoading: loadingTx } = useAccountTransactions(id);

  const transactions = txData || [];

  return (
    <>
      <div className="mb-6">
        <Link
          href="/dashboard"
          className="inline-flex items-center gap-1 text-xs text-muted-foreground hover:text-foreground transition-colors"
        >
          <CaretLeft size={12} />
          Back to overview
        </Link>
      </div>

      {loadingAccount ? (
        <CardSkeleton />
      ) : account ? (
        <motion.div
          initial={{ opacity: 0, y: 12 }}
          animate={{ opacity: 1, y: 0 }}
          transition={{ duration: 0.4, ease: [0.32, 0.72, 0, 1] }}
          className="rounded-2xl border border-white/[0.04] bg-white/[0.02] p-8 mb-8"
        >
          <div className="flex items-start justify-between mb-6">
            <div>
              <div className="flex items-center gap-2 mb-3">
                <CurrencyBadge currency={account.currency} />
                <span className="text-xs text-muted-foreground font-mono">
                  {account.accountId}
                </span>
              </div>
              <Money
                amount={account.balance}
                currency={account.currency}
                size="xl"
              />
              <p className="mt-2 text-xs text-muted-foreground">
                Last updated{" "}
                {new Date(account.lastUpdated).toLocaleString("en-US", {
                  month: "short",
                  day: "numeric",
                  hour: "2-digit",
                  minute: "2-digit",
                })}
              </p>
            </div>

            <div className="flex gap-2">
              <Link href={`/deposit?account=${id}`}>
                <Button
                  variant="outline"
                  size="sm"
                  className="gap-1.5 border-white/[0.06] bg-white/[0.02] hover:bg-white/[0.04] text-xs"
                >
                  <ArrowDown size={14} />
                  Deposit
                </Button>
              </Link>
              <Link href={`/withdraw?account=${id}`}>
                <Button
                  variant="outline"
                  size="sm"
                  className="gap-1.5 border-white/[0.06] bg-white/[0.02] hover:bg-white/[0.04] text-xs"
                >
                  <ArrowUp size={14} />
                  Withdraw
                </Button>
              </Link>
            </div>
          </div>
        </motion.div>
      ) : null}

      <PageHeader title="Transactions" />

      {loadingTx ? (
        <TableSkeleton rows={5} />
      ) : transactions.length === 0 ? (
        <EmptyState
          icon={Receipt}
          title="No transactions"
          description="Transactions will appear here after deposits, withdrawals, or transfers"
        />
      ) : (
        <StaggerList>
          <div className="space-y-1">
            {transactions.map((tx) => {
              const Icon = TYPE_ICONS[tx.type] || ArrowsLeftRight;
              return (
                <StaggerItem key={tx.id}>
                  <div className="flex items-center gap-4 py-3 px-4 rounded-xl hover:bg-white/[0.02] transition-colors">
                    <div className="w-9 h-9 rounded-xl bg-white/[0.03] border border-white/[0.06] flex items-center justify-center flex-shrink-0">
                      <Icon size={16} className="text-muted-foreground" />
                    </div>
                    <div className="flex-1 min-w-0">
                      <p className="text-sm font-medium capitalize">
                        {tx.type.toLowerCase()}
                      </p>
                      <p className="text-xs text-muted-foreground font-mono truncate">
                        {tx.id.slice(0, 12)}...
                      </p>
                    </div>
                    <Badge
                      variant="outline"
                      className={`text-[10px] ${STATUS_COLORS[tx.status] || ""}`}
                    >
                      {tx.status}
                    </Badge>
                    <Money
                      amount={tx.amount}
                      currency={tx.currency}
                      size="sm"
                      className="text-right min-w-[90px]"
                    />
                    <span className="text-xs text-muted-foreground min-w-[70px] text-right">
                      {new Date(tx.createdAt).toLocaleDateString("en-US", {
                        month: "short",
                        day: "numeric",
                      })}
                    </span>
                  </div>
                </StaggerItem>
              );
            })}
          </div>
        </StaggerList>
      )}
    </>
  );
}
