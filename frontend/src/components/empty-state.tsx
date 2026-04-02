"use client";

import { motion } from "framer-motion";
import type { Icon } from "@phosphor-icons/react";

interface EmptyStateProps {
  icon: Icon;
  title: string;
  description: string;
  action?: React.ReactNode;
}

export function EmptyState({
  icon: IconComponent,
  title,
  description,
  action,
}: EmptyStateProps) {
  return (
    <motion.div
      initial={{ opacity: 0, scale: 0.96 }}
      animate={{ opacity: 1, scale: 1 }}
      transition={{ duration: 0.3, ease: [0.32, 0.72, 0, 1] }}
      className="flex flex-col items-center justify-center py-16 text-center"
    >
      <div className="w-14 h-14 rounded-2xl bg-white/[0.03] border border-white/[0.06] flex items-center justify-center mb-4">
        <IconComponent size={24} className="text-muted-foreground" />
      </div>
      <h3 className="text-sm font-medium text-foreground/80 mb-1">{title}</h3>
      <p className="text-xs text-muted-foreground max-w-[280px]">
        {description}
      </p>
      {action && <div className="mt-4">{action}</div>}
    </motion.div>
  );
}
