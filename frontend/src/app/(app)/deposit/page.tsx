"use client";

import { Suspense, useState } from "react";
import { useSearchParams, useRouter } from "next/navigation";
import { useForm } from "react-hook-form";
import { zodResolver } from "@hookform/resolvers/zod";
import { z } from "zod/v4";
import { motion } from "framer-motion";
import { ArrowDown, Spinner, Check } from "@phosphor-icons/react";
import { useAuth } from "@/providers/auth-provider";
import { useUserAccounts, useDeposit } from "@/lib/hooks";
import { ApiError } from "@/lib/api";
import { PageHeader } from "@/components/page-header";
import { AccountSelect } from "@/components/account-select";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Button } from "@/components/ui/button";
import { PageSkeleton } from "@/components/loading";
import { toast } from "sonner";

const schema = z.object({
  amount: z.string().regex(/^\d+(\.\d{1,2})?$/, "Enter a valid amount"),
});

type FormData = z.infer<typeof schema>;

export default function DepositPage() {
  return (
    <Suspense>
      <DepositForm />
    </Suspense>
  );
}

function DepositForm() {
  const { userId } = useAuth();
  const searchParams = useSearchParams();
  const router = useRouter();
  const { data, isLoading } = useUserAccounts(userId);
  const deposit = useDeposit();

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
      await deposit.mutateAsync({
        accountId,
        amount: data.amount,
        currency: selectedAccount.currency,
      });
      toast.success(
        `Deposited ${selectedAccount.currency} ${data.amount}`,
      );
      reset();
      router.push(`/accounts/${accountId}`);
    } catch (err) {
      setError(err instanceof ApiError ? err.message : "Deposit failed");
    }
  }

  if (isLoading) return <PageSkeleton />;

  return (
    <>
      <PageHeader
        title="Deposit"
        description="Add funds to your account"
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
            label="To account"
          />

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
            disabled={!accountId || deposit.isPending}
            className="w-full h-11 bg-emerald text-zinc-950 font-medium hover:bg-emerald/90 active:scale-[0.98] transition-all rounded-xl"
          >
            {deposit.isPending ? (
              <Spinner size={18} className="animate-spin" />
            ) : deposit.isSuccess ? (
              <Check size={18} weight="bold" />
            ) : (
              <span className="flex items-center gap-2">
                <ArrowDown size={16} weight="bold" />
                Deposit
              </span>
            )}
          </Button>
        </form>
      </motion.div>
    </>
  );
}
