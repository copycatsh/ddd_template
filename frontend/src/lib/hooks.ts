import { useQuery, useMutation, useQueryClient } from "@tanstack/react-query";
import { api } from "./api";
import type {
  UserResource,
  UserAccountsResponse,
  AccountBalanceResponse,
  AccountResource,
  TransactionDto,
  NotificationHistoryResponse,
} from "./types";

export function useUser(userId: string) {
  return useQuery({
    queryKey: ["user", userId],
    queryFn: () => api.get<UserResource>(`/api/users/${userId}`),
    enabled: !!userId,
  });
}

export function useUserAccounts(userId: string) {
  return useQuery({
    queryKey: ["accounts", userId],
    queryFn: () =>
      api.get<UserAccountsResponse>(`/api/users/${userId}/accounts`),
    enabled: !!userId,
  });
}

export function useAccountBalance(accountId: string) {
  return useQuery({
    queryKey: ["account", accountId],
    queryFn: () =>
      api.get<AccountBalanceResponse>(`/api/accounts/${accountId}`),
    enabled: !!accountId,
  });
}

export function useAccountTransactions(accountId: string) {
  return useQuery({
    queryKey: ["transactions", accountId],
    queryFn: () =>
      api.get<TransactionDto[]>(
        `/api/accounts/${accountId}/transactions`,
      ),
    enabled: !!accountId,
  });
}

export function useNotifications(userId: string) {
  return useQuery({
    queryKey: ["notifications", userId],
    queryFn: () =>
      api.get<NotificationHistoryResponse>(
        `/api/users/${userId}/notifications`,
      ),
    enabled: !!userId,
  });
}

export function useDeposit() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: ({
      accountId,
      amount,
      currency,
    }: {
      accountId: string;
      amount: string;
      currency: string;
    }) =>
      api.put<AccountResource>(`/api/accounts/${accountId}/deposit`, {
        amount,
        currency,
        description: "Deposit via web",
      }),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: ["accounts"] });
      qc.invalidateQueries({ queryKey: ["account"] });
      qc.invalidateQueries({ queryKey: ["transactions"] });
    },
  });
}

export function useWithdraw() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: ({
      accountId,
      amount,
      currency,
    }: {
      accountId: string;
      amount: string;
      currency: string;
    }) =>
      api.put<AccountResource>(`/api/accounts/${accountId}/withdraw`, {
        amount,
        currency,
        description: "Withdrawal via web",
      }),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: ["accounts"] });
      qc.invalidateQueries({ queryKey: ["account"] });
      qc.invalidateQueries({ queryKey: ["transactions"] });
    },
  });
}

export function useTransfer() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: ({
      fromAccountId,
      toAccountId,
      amount,
      currency,
    }: {
      fromAccountId: string;
      toAccountId: string;
      amount: string;
      currency: string;
    }) =>
      api.put<AccountResource>(`/api/accounts/${fromAccountId}/transfer`, {
        toAccountId,
        amount,
        currency,
      }),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: ["accounts"] });
      qc.invalidateQueries({ queryKey: ["account"] });
      qc.invalidateQueries({ queryKey: ["transactions"] });
      qc.invalidateQueries({ queryKey: ["notifications"] });
    },
  });
}

export function useChangeEmail() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: ({ userId, email }: { userId: string; email: string }) =>
      api.put<UserResource>(`/api/users/${userId}/email`, { email }),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: ["user"] });
    },
  });
}
