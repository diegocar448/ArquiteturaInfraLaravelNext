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
    <Alert>
      <ShieldAlert className="h-4 w-4" />
      <AlertTitle>Modo visualizacao</AlertTitle>
      <AlertDescription>
        Voce esta logado como super-admin e pode visualizar {resource} de todos
        os tenants. Para criar ou editar, faca login com um usuario vinculado a
        um tenant (ex: <strong>gerente@demo.com</strong>).
      </AlertDescription>
    </Alert>
  );
}
