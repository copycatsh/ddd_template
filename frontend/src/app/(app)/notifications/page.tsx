"use client";

import { motion } from "framer-motion";
import { Bell, EnvelopeSimple } from "@phosphor-icons/react";
import { useAuth } from "@/providers/auth-provider";
import { useNotifications } from "@/lib/hooks";
import { PageHeader } from "@/components/page-header";
import { TableSkeleton } from "@/components/loading";
import { EmptyState } from "@/components/empty-state";
import { StaggerList, StaggerItem } from "@/components/stagger-list";
import { Badge } from "@/components/ui/badge";

const TYPE_LABELS: Record<string, { label: string; color: string }> = {
  transaction_created: {
    label: "Created",
    color: "bg-blue-500/10 text-blue-400 border-blue-500/20",
  },
  transaction_completed: {
    label: "Completed",
    color: "bg-emerald/10 text-emerald border-emerald/20",
  },
  transaction_failed: {
    label: "Failed",
    color: "bg-destructive/10 text-destructive border-destructive/20",
  },
};

export default function NotificationsPage() {
  const { userId } = useAuth();
  const { data, isLoading } = useNotifications(userId);

  const items = data?.items || [];

  return (
    <>
      <PageHeader
        title="Notifications"
        description="Transaction alerts and updates"
      />

      {isLoading ? (
        <TableSkeleton rows={6} />
      ) : items.length === 0 ? (
        <EmptyState
          icon={Bell}
          title="No notifications"
          description="You will receive alerts for transaction events"
        />
      ) : (
        <StaggerList>
          <div className="space-y-1">
            {items.map((item) => {
              const typeInfo = TYPE_LABELS[item.notificationType] || {
                label: item.notificationType,
                color: "",
              };
              return (
                <StaggerItem key={item.id}>
                  <motion.div className="flex items-center gap-4 py-3 px-4 rounded-xl hover:bg-white/[0.02] transition-colors">
                    <div className="w-9 h-9 rounded-xl bg-white/[0.03] border border-white/[0.06] flex items-center justify-center flex-shrink-0">
                      <EnvelopeSimple
                        size={16}
                        className="text-muted-foreground"
                      />
                    </div>
                    <div className="flex-1 min-w-0">
                      <p className="text-sm font-medium">
                        Transaction {typeInfo.label.toLowerCase()}
                      </p>
                      <p className="text-xs text-muted-foreground font-mono truncate">
                        {item.transactionId.slice(0, 16)}...
                      </p>
                    </div>
                    <Badge
                      variant="outline"
                      className={`text-[10px] ${typeInfo.color}`}
                    >
                      {typeInfo.label}
                    </Badge>
                    <span className="text-xs text-muted-foreground min-w-[100px] text-right">
                      {new Date(item.sentAt).toLocaleString("en-US", {
                        month: "short",
                        day: "numeric",
                        hour: "2-digit",
                        minute: "2-digit",
                      })}
                    </span>
                  </motion.div>
                </StaggerItem>
              );
            })}
          </div>
        </StaggerList>
      )}
    </>
  );
}
