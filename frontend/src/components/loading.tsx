export function CardSkeleton() {
  return (
    <div className="rounded-2xl border border-white/[0.04] bg-white/[0.02] p-6 animate-pulse">
      <div className="h-3 w-16 bg-white/[0.06] rounded mb-4" />
      <div className="h-8 w-32 bg-white/[0.06] rounded mb-2" />
      <div className="h-3 w-24 bg-white/[0.04] rounded" />
    </div>
  );
}

export function TableSkeleton({ rows = 5 }: { rows?: number }) {
  return (
    <div className="space-y-3">
      {Array.from({ length: rows }).map((_, i) => (
        <div
          key={i}
          className="flex items-center gap-4 animate-pulse"
          style={{ animationDelay: `${i * 80}ms` }}
        >
          <div className="h-4 w-24 bg-white/[0.06] rounded" />
          <div className="h-4 flex-1 bg-white/[0.04] rounded" />
          <div className="h-4 w-20 bg-white/[0.06] rounded" />
        </div>
      ))}
    </div>
  );
}

export function PageSkeleton() {
  return (
    <div className="space-y-6 animate-pulse">
      <div className="h-8 w-48 bg-white/[0.06] rounded" />
      <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
        <CardSkeleton />
        <CardSkeleton />
      </div>
    </div>
  );
}
