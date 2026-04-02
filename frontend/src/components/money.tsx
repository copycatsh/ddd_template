"use client";

import { cn } from "@/lib/utils";

const CURRENCY_SYMBOLS: Record<string, string> = {
  UAH: "\u20B4",
  USD: "$",
  EUR: "\u20AC",
};

interface MoneyProps {
  amount: string;
  currency: string;
  size?: "sm" | "md" | "lg" | "xl";
  className?: string;
  showSign?: boolean;
}

export function Money({
  amount,
  currency,
  size = "md",
  className,
  showSign,
}: MoneyProps) {
  const symbol = CURRENCY_SYMBOLS[currency] || currency;
  const num = parseFloat(amount);
  const isPositive = num > 0;
  const formatted = Math.abs(num).toLocaleString("en-US", {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  });

  return (
    <span
      className={cn(
        "font-mono tabular-nums tracking-tight",
        size === "sm" && "text-sm",
        size === "md" && "text-base",
        size === "lg" && "text-xl md:text-2xl",
        size === "xl" && "text-3xl md:text-4xl",
        showSign && isPositive && "text-emerald",
        showSign && !isPositive && "text-destructive",
        className,
      )}
    >
      {showSign && isPositive && "+"}
      {showSign && !isPositive && "-"}
      {symbol}
      {formatted}
    </span>
  );
}

export function CurrencyBadge({ currency }: { currency: string }) {
  return (
    <span className="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-mono font-bold uppercase tracking-wider bg-white/[0.04] border border-white/[0.06] text-muted-foreground">
      {currency}
    </span>
  );
}
