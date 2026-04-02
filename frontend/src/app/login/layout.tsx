import { QueryProvider } from "@/providers/query-provider";

export default function LoginLayout({
  children,
}: {
  children: React.ReactNode;
}) {
  return <QueryProvider>{children}</QueryProvider>;
}
