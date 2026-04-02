"use client";

import { useState } from "react";
import { useRouter } from "next/navigation";
import { useForm } from "react-hook-form";
import { zodResolver } from "@hookform/resolvers/zod";
import { z } from "zod/v4";
import { motion } from "framer-motion";
import { ArrowsLeftRight, Spinner, Check } from "@phosphor-icons/react";
import { useAuth } from "@/providers/auth-provider";
import { useUserAccounts, useTransfer } from "@/lib/hooks";
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
  toAccountId: z.string().min(1, "Destination account ID is required"),
  amount: z.string().regex(/^\d+(\.\d{1,2})?$/, "Enter a valid amount"),
});

type FormData = z.infer<typeof schema>;

export default function TransferPage() {
  const { userId } = useAuth();
  const router = useRouter();
  const { data, isLoading } = useUserAccounts(userId);
  const transfer = useTransfer();

  const accounts = data?.accounts || [];
  const [fromId, setFromId] = useState("");
  const [error, setError] = useState<string | null>(null);

  const fromAccount = accounts.find((a) => a.accountId === fromId);

  const {
    register,
    handleSubmit,
    formState: { errors },
    reset,
  } = useForm<FormData>({
    resolver: zodResolver(schema),
  });

  async function onSubmit(data: FormData) {
    if (!fromId || !fromAccount) return;
    setError(null);
    try {
      await transfer.mutateAsync({
        fromAccountId: fromId,
        toAccountId: data.toAccountId,
        amount: data.amount,
        currency: fromAccount.currency,
      });
      toast.success(`Transferred ${fromAccount.currency} ${data.amount}`);
      reset();
      router.push(`/accounts/${fromId}`);
    } catch (err) {
      setError(err instanceof ApiError ? err.message : "Transfer failed");
    }
  }

  if (isLoading) return <PageSkeleton />;

  return (
    <>
      <PageHeader
        title="Transfer"
        description="Move funds between accounts"
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
            value={fromId}
            onChange={setFromId}
            label="From account"
          />

          {fromAccount && (
            <div className="flex items-center justify-between py-2 px-3 rounded-lg bg-white/[0.02] border border-white/[0.04]">
              <span className="text-xs text-muted-foreground">
                Available
              </span>
              <Money
                amount={fromAccount.balance}
                currency={fromAccount.currency}
                size="sm"
              />
            </div>
          )}

          <div className="space-y-2">
            <Label className="text-xs text-muted-foreground">
              To account
            </Label>
            <Input
              placeholder="Enter destination account ID"
              className="h-11 bg-white/[0.03] border-white/[0.06] focus:border-emerald/30 font-mono text-sm"
              {...register("toAccountId")}
            />
            {errors.toAccountId && (
              <p className="text-xs text-destructive">
                {errors.toAccountId.message}
              </p>
            )}
          </div>

          <div className="space-y-2">
            <Label className="text-xs text-muted-foreground">Amount</Label>
            <div className="relative">
              <Input
                placeholder="0.00"
                className="h-11 bg-white/[0.03] border-white/[0.06] focus:border-emerald/30 font-mono text-lg pl-4 pr-16"
                {...register("amount")}
              />
              {fromAccount && (
                <span className="absolute right-3 top-1/2 -translate-y-1/2 text-xs text-muted-foreground font-mono">
                  {fromAccount.currency}
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
            disabled={!fromId || transfer.isPending}
            className="w-full h-11 bg-emerald text-zinc-950 font-medium hover:bg-emerald/90 active:scale-[0.98] transition-all rounded-xl"
          >
            {transfer.isPending ? (
              <Spinner size={18} className="animate-spin" />
            ) : transfer.isSuccess ? (
              <Check size={18} weight="bold" />
            ) : (
              <span className="flex items-center gap-2">
                <ArrowsLeftRight size={16} weight="bold" />
                Transfer
              </span>
            )}
          </Button>
        </form>
      </motion.div>
    </>
  );
}
