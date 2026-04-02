"use client";

import { Suspense, useState } from "react";
import { useSearchParams, useRouter } from "next/navigation";
import { useForm } from "react-hook-form";
import { zodResolver } from "@hookform/resolvers/zod";
import { z } from "zod/v4";
import { motion } from "framer-motion";
import { ArrowUp, Spinner, Check } from "@phosphor-icons/react";
import { useAuth } from "@/providers/auth-provider";
import { useUserAccounts, useWithdraw } from "@/lib/hooks";
import { ApiError } from "@/lib/api";
import { PageHeader } from "@/components/page-header";
import { AccountSelect } from "@/components/account-select";
import { Money } from "@/components/money";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Button } from "@/components/ui/button";
import { PageSkeleton } from "@/components/loading";
import { toast } from "sonner";

const schema = z.object({
  amount: z.string().regex(/^\d+(\.\d{1,2})?$/, "Enter a valid amount"),
});

type FormData = z.infer<typeof schema>;

export default function WithdrawPage() {
  return (
    <Suspense>
      <WithdrawForm />
    </Suspense>
  );
}

function WithdrawForm() {
  const { userId } = useAuth();
  const searchParams = useSearchParams();
  const router = useRouter();
  const { data, isLoading } = useUserAccounts(userId);
  const withdraw = useWithdraw();

  const accounts = data?.accounts || [];
  const preselected = searchParams.get("account") || "";
  const [accountId, setAccountId] = useState(preselected);
  const [error, setError] = useState<string | null>(null);

  const selectedAccount = accounts.find((a) => a.accountId === accountId);

  const {
    register,
    handleSubmit,
    formState: { errors },
    reset,
  } = useForm<FormData>({
    resolver: zodResolver(schema),
  });

  async function onSubmit(data: FormData) {
    if (!accountId || !selectedAccount) return;
    setError(null);
    try {
      await withdraw.mutateAsync({
        accountId,
        amount: data.amount,
        currency: selectedAccount.currency,
      });
      toast.success(
        `Withdrew ${selectedAccount.currency} ${data.amount}`,
      );
      reset();
      router.push(`/accounts/${accountId}`);
    } catch (err) {
      setError(err instanceof ApiError ? err.message : "Withdrawal failed");
    }
  }

  if (isLoading) return <PageSkeleton />;

  return (
    <>
      <PageHeader
        title="Withdraw"
        description="Withdraw funds from your account"
      />

      <motion.div
        initial={{ opacity: 0, y: 12 }}
        animate={{ opacity: 1, y: 0 }}
        transition={{ duration: 0.4, ease: [0.32, 0.72, 0, 1] }}
        className="max-w-md"
      >
        <form
          onSubmit={handleSubmit(onSubmit)}
          className="space-y-5 rounded-2xl border border-white/[0.04] bg-white/[0.02] p-6"
        >
          <AccountSelect
            accounts={accounts}
            value={accountId}
            onChange={setAccountId}
            label="From account"
          />

          {selectedAccount && (
            <div className="flex items-center justify-between py-2 px-3 rounded-lg bg-white/[0.02] border border-white/[0.04]">
              <span className="text-xs text-muted-foreground">
                Available balance
              </span>
              <Money
                amount={selectedAccount.balance}
                currency={selectedAccount.currency}
                size="sm"
              />
            </div>
          )}

          <div className="space-y-2">
            <Label className="text-xs text-muted-foreground">Amount</Label>
            <div className="relative">
              <Input
                placeholder="0.00"
                className="h-11 bg-white/[0.03] border-white/[0.06] focus:border-emerald/30 font-mono text-lg pl-4 pr-16"
                {...register("amount")}
              />
              {selectedAccount && (
                <span className="absolute right-3 top-1/2 -translate-y-1/2 text-xs text-muted-foreground font-mono">
                  {selectedAccount.currency}
                </span>
              )}
            </div>
            {errors.amount && (
              <p className="text-xs text-destructive">
                {errors.amount.message}
              </p>
            )}
          </div>

          {error && (
            <div className="p-3 rounded-lg bg-destructive/10 border border-destructive/20 text-sm text-destructive">
              {error}
            </div>
          )}

          <Button
            type="submit"
            disabled={!accountId || withdraw.isPending}
            className="w-full h-11 bg-emerald text-zinc-950 font-medium hover:bg-emerald/90 active:scale-[0.98] transition-all rounded-xl"
          >
            {withdraw.isPending ? (
              <Spinner size={18} className="animate-spin" />
            ) : withdraw.isSuccess ? (
              <Check size={18} weight="bold" />
            ) : (
              <span className="flex items-center gap-2">
                <ArrowUp size={16} weight="bold" />
                Withdraw
              </span>
            )}
          </Button>
        </form>
      </motion.div>
    </>
  );
}
