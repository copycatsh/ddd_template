export interface LoginResponse {
  token: string;
}

export interface UserResource {
  id: string;
  email: string;
  role: string;
  createdAt: string;
}

export interface AccountSummary {
  accountId: string;
  balance: string;
  currency: string;
  createdAt: string;
}

export interface UserAccountsResponse {
  userId: string;
  accounts: AccountSummary[];
}

export interface AccountBalanceResponse {
  accountId: string;
  balance: string;
  currency: string;
  lastUpdated: string;
}

export interface AccountResource {
  id: string;
  userId: string;
  currency: string;
  balance: string;
  createdAt: string;
  updatedAt: string;
}

export interface TransactionDto {
  id: string;
  type: string;
  fromAccountId: string;
  toAccountId: string | null;
  amount: string;
  currency: string;
  status: string;
  createdAt: string;
  completedAt: string | null;
}

export interface NotificationItem {
  id: number;
  transactionId: string;
  accountId: string;
  userId: string;
  recipientEmail: string;
  notificationType: string;
  sentAt: string;
}

export interface NotificationHistoryResponse {
  userId: string;
  items: NotificationItem[];
  total: number;
  page: number;
  perPage: number;
}

export type Currency = "UAH" | "USD";
