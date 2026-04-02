"use client";

import type { AccountSummary } from "@/lib/types";

const CURRENCY_SYMBOLS: Record<string, string> = {
  UAH: "\u20B4",
  USD: "$",
  EUR: "\u20AC",
};

interface AccountSelectProps {
  accounts: AccountSummary[];
  value: string;
  onChange: (value: string) => void;
  label: string;
  filterCurrency?: string;
  excludeId?: string;
}

export function AccountSelect({
  accounts,
  value,
  onChange,
  label,
  filterCurrency,
  excludeId,
}: AccountSelectProps) {
  const filtered = accounts.filter((a) => {
    if (filterCurrency && a.currency !== filterCurrency) return false;
    if (excludeId && a.accountId === excludeId) return false;
    return true;
  });

  return (
    <div className="space-y-2">
      <label className="text-xs text-muted-foreground">{label}</label>
      <select
        value={value}
        onChange={(e) => onChange(e.target.value)}
        className="w-full h-11 rounded-xl bg-white/[0.03] border border-white/[0.06] text-foreground text-sm px-3 focus:outline-none focus:border-emerald/30 transition-colors appearance-none"
      >
        <option value="" className="bg-[#181a24]">
          Select account
        </option>
        {filtered.map((a) => (
          <option
            key={a.accountId}
            value={a.accountId}
            className="bg-[#181a24]"
          >
            {a.currency} — {CURRENCY_SYMBOLS[a.currency]}
            {parseFloat(a.balance).toLocaleString("en-US", {
              minimumFractionDigits: 2,
            })}{" "}
            ({a.accountId.slice(0, 8)}...)
          </option>
        ))}
      </select>
    </div>
  );
}
