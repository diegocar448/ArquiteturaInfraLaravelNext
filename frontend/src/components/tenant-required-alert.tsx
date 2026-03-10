"use client";

import { useAuthStore } from "@/stores/auth-store";
import { Alert, AlertDescription, AlertTitle } from "@/components/ui/alert";
import { ShieldAlert } from "lucide-react";

interface TenantRequiredAlertProps {
  resource: string;
}

export function TenantRequiredAlert({ resource }: TenantRequiredAlertProps) {
  const user = useAuthStore((s) => s.user);

  if (!user || user.tenant_id) return null;

  return (
    <Alert variant="destructive">
      <ShieldAlert className="h-4 w-4" />
      <AlertTitle>Tenant necessario</AlertTitle>
      <AlertDescription>
        Voce esta logado como super-admin sem tenant vinculado. Para gerenciar{" "}
        {resource}, faca login com um usuario que pertenca a um tenant (ex:{" "}
        <strong>gerente@demo.com</strong>).
      </AlertDescription>
    </Alert>
  );
}
