"use client";

import { Suspense, useState } from "react";
import { useRouter, useSearchParams } from "next/navigation";
import { useForm } from "react-hook-form";
import { zodResolver } from "@hookform/resolvers/zod";
import { z } from "zod/v4";
import { motion } from "framer-motion";
import { ArrowRight, Spinner } from "@phosphor-icons/react";
import { login } from "@/lib/auth";
import { ApiError } from "@/lib/api";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Button } from "@/components/ui/button";

const schema = z.object({
  email: z.email("Enter a valid email"),
  password: z.string().min(4, "Password too short"),
});

type FormData = z.infer<typeof schema>;

export default function LoginPage() {
  return (
    <Suspense>
      <LoginForm />
    </Suspense>
  );
}

function LoginForm() {
  const router = useRouter();
  const searchParams = useSearchParams();
  const redirect = searchParams.get("redirect") || "/dashboard";
  const [error, setError] = useState<string | null>(null);

  const {
    register,
    handleSubmit,
    formState: { errors, isSubmitting },
  } = useForm<FormData>({
    resolver: zodResolver(schema),
    defaultValues: {
      email: "user@fintech.com",
      password: "user123",
    },
  });

  async function onSubmit(data: FormData) {
    setError(null);
    try {
      await login(data.email, data.password);
      router.replace(redirect);
    } catch (err) {
      if (err instanceof ApiError) {
        setError(err.message);
      } else {
        setError("Authentication failed");
      }
    }
  }

  return (
    <div className="min-h-[100dvh] flex items-center justify-center px-4">
      {/* Background glow */}
      <div className="fixed inset-0 pointer-events-none">
        <div className="absolute top-1/4 left-1/2 -translate-x-1/2 w-[600px] h-[400px] bg-emerald/[0.04] rounded-full blur-[120px]" />
      </div>

      <motion.div
        initial={{ opacity: 0, y: 20 }}
        animate={{ opacity: 1, y: 0 }}
        transition={{ duration: 0.6, ease: [0.32, 0.72, 0, 1] }}
        className="relative w-full max-w-[380px]"
      >
        {/* Logo */}
        <div className="flex items-center gap-2.5 mb-10">
          <div className="w-10 h-10 rounded-xl bg-emerald/10 border border-emerald/20 flex items-center justify-center">
            <span className="text-emerald font-mono text-lg font-bold">V</span>
          </div>
          <span className="text-xl font-semibold tracking-tight">Vault</span>
        </div>

        <div className="mb-8">
          <h1 className="text-2xl font-semibold tracking-tight mb-1">
            Sign in
          </h1>
          <p className="text-sm text-muted-foreground">
            Access your financial dashboard
          </p>
        </div>

        <form onSubmit={handleSubmit(onSubmit)} className="space-y-5">
          <div className="space-y-2">
            <Label htmlFor="email" className="text-xs text-muted-foreground">
              Email
            </Label>
            <Input
              id="email"
              type="email"
              autoFocus
              className="h-11 bg-white/[0.03] border-white/[0.06] focus:border-emerald/30 transition-colors"
              {...register("email")}
            />
            {errors.email && (
              <p className="text-xs text-destructive">{errors.email.message}</p>
            )}
          </div>

          <div className="space-y-2">
            <Label
              htmlFor="password"
              className="text-xs text-muted-foreground"
            >
              Password
            </Label>
            <Input
              id="password"
              type="password"
              className="h-11 bg-white/[0.03] border-white/[0.06] focus:border-emerald/30 transition-colors"
              {...register("password")}
            />
            {errors.password && (
              <p className="text-xs text-destructive">
                {errors.password.message}
              </p>
            )}
          </div>

          {error && (
            <motion.div
              initial={{ opacity: 0, y: -4 }}
              animate={{ opacity: 1, y: 0 }}
              className="p-3 rounded-lg bg-destructive/10 border border-destructive/20 text-sm text-destructive"
            >
              {error}
            </motion.div>
          )}

          <Button
            type="submit"
            disabled={isSubmitting}
            className="w-full h-11 bg-emerald text-zinc-950 font-medium hover:bg-emerald/90 active:scale-[0.98] transition-all duration-200 rounded-xl"
          >
            {isSubmitting ? (
              <Spinner size={18} className="animate-spin" />
            ) : (
              <span className="flex items-center justify-center gap-2">
                Continue
                <ArrowRight size={16} weight="bold" />
              </span>
            )}
          </Button>
        </form>

        <p className="mt-8 text-xs text-center text-muted-foreground/60">
          Demo: user@fintech.com / user123
        </p>
      </motion.div>
    </div>
  );
}
