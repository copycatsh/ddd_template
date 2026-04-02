"use client";

import { useState } from "react";
import { useForm } from "react-hook-form";
import { zodResolver } from "@hookform/resolvers/zod";
import { z } from "zod/v4";
import { motion } from "framer-motion";
import { Spinner, Check, User } from "@phosphor-icons/react";
import { useAuth } from "@/providers/auth-provider";
import { useChangeEmail } from "@/lib/hooks";
import { ApiError } from "@/lib/api";
import { logout } from "@/lib/auth";
import { PageHeader } from "@/components/page-header";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Button } from "@/components/ui/button";
import { toast } from "sonner";

const schema = z.object({
  email: z.email("Enter a valid email"),
});

type FormData = z.infer<typeof schema>;

export default function ProfilePage() {
  const { user, userId, setUser } = useAuth();
  const changeEmail = useChangeEmail();
  const [error, setError] = useState<string | null>(null);

  const {
    register,
    handleSubmit,
    formState: { errors },
  } = useForm<FormData>({
    resolver: zodResolver(schema),
    defaultValues: { email: user?.email || "" },
  });

  async function onSubmit(data: FormData) {
    setError(null);
    try {
      await changeEmail.mutateAsync({
        userId,
        email: data.email,
      });
      toast.success("Email updated — please sign in again");
      setTimeout(() => logout(), 1500);
    } catch (err) {
      setError(err instanceof ApiError ? err.message : "Update failed");
    }
  }

  return (
    <>
      <PageHeader title="Profile" description="Manage your account settings" />

      <div className="grid grid-cols-1 md:grid-cols-3 gap-8">
        {/* User info */}
        <motion.div
          initial={{ opacity: 0, y: 12 }}
          animate={{ opacity: 1, y: 0 }}
          transition={{ duration: 0.4, ease: [0.32, 0.72, 0, 1] }}
          className="rounded-2xl border border-white/[0.04] bg-white/[0.02] p-6"
        >
          <div className="w-14 h-14 rounded-2xl bg-white/[0.03] border border-white/[0.06] flex items-center justify-center mb-4">
            <User size={24} className="text-muted-foreground" />
          </div>
          <dl className="space-y-3">
            <div>
              <dt className="text-[10px] text-muted-foreground uppercase tracking-wider">
                User ID
              </dt>
              <dd className="text-xs font-mono text-foreground/80 mt-0.5 break-all">
                {userId}
              </dd>
            </div>
            <div>
              <dt className="text-[10px] text-muted-foreground uppercase tracking-wider">
                Email
              </dt>
              <dd className="text-sm text-foreground/90 mt-0.5">
                {user?.email}
              </dd>
            </div>
            <div>
              <dt className="text-[10px] text-muted-foreground uppercase tracking-wider">
                Role
              </dt>
              <dd className="text-sm text-foreground/90 mt-0.5">
                {user?.role?.replace("ROLE_", "")}
              </dd>
            </div>
            <div>
              <dt className="text-[10px] text-muted-foreground uppercase tracking-wider">
                Joined
              </dt>
              <dd className="text-sm text-foreground/90 mt-0.5">
                {user?.createdAt
                  ? new Date(user.createdAt).toLocaleDateString("en-US", {
                      month: "long",
                      day: "numeric",
                      year: "numeric",
                    })
                  : "—"}
              </dd>
            </div>
          </dl>
        </motion.div>

        {/* Change email form */}
        <motion.div
          initial={{ opacity: 0, y: 12 }}
          animate={{ opacity: 1, y: 0 }}
          transition={{
            duration: 0.4,
            delay: 0.1,
            ease: [0.32, 0.72, 0, 1],
          }}
          className="md:col-span-2"
        >
          <form
            onSubmit={handleSubmit(onSubmit)}
            className="space-y-5 rounded-2xl border border-white/[0.04] bg-white/[0.02] p-6"
          >
            <h2 className="text-sm font-medium">Change email</h2>

            <div className="space-y-2">
              <Label className="text-xs text-muted-foreground">
                New email address
              </Label>
              <Input
                type="email"
                className="h-11 bg-white/[0.03] border-white/[0.06] focus:border-emerald/30 max-w-sm"
                {...register("email")}
              />
              {errors.email && (
                <p className="text-xs text-destructive">
                  {errors.email.message}
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
              disabled={changeEmail.isPending}
              className="h-10 bg-emerald text-zinc-950 font-medium hover:bg-emerald/90 active:scale-[0.98] transition-all rounded-xl px-6"
            >
              {changeEmail.isPending ? (
                <Spinner size={16} className="animate-spin" />
              ) : changeEmail.isSuccess ? (
                <Check size={16} weight="bold" />
              ) : (
                "Save changes"
              )}
            </Button>
          </form>
        </motion.div>
      </div>
    </>
  );
}
