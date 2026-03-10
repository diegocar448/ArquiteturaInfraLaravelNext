"use client";

import { useEffect, useState } from "react";
import { useParams, useRouter } from "next/navigation";
import {
  getProfile,
  getPermissions,
  syncProfilePermissions,
} from "@/services/acl-service";
import type { Profile, Permission } from "@/types/acl";
import { Button } from "@/components/ui/button";
import {
  Card,
  CardContent,
  CardHeader,
  CardTitle,
} from "@/components/ui/card";
import { Checkbox } from "@/components/ui/checkbox";
import { Skeleton } from "@/components/ui/skeleton";
import { ArrowLeft, Save } from "lucide-react";

export default function ProfileDetailPage() {
  const params = useParams();
  const router = useRouter();
  const profileId = Number(params.id);

  const [profile, setProfile] = useState<Profile | null>(null);
  const [allPermissions, setAllPermissions] = useState<Permission[]>([]);
  const [selectedIds, setSelectedIds] = useState<number[]>([]);
  const [loading, setLoading] = useState(true);
  const [saving, setSaving] = useState(false);

  useEffect(() => {
    const fetchData = async () => {
      try {
        const [profileRes, permissionsRes] = await Promise.all([
          getProfile(profileId),
          getPermissions(),
        ]);
        setProfile(profileRes.data);
        setAllPermissions(permissionsRes.data);
        setSelectedIds(
          profileRes.data.permissions?.map((p) => p.id) || []
        );
      } catch (error) {
        console.error("Erro ao carregar perfil:", error);
      } finally {
        setLoading(false);
      }
    };

    fetchData();
  }, [profileId]);

  const handleToggle = (permissionId: number) => {
    setSelectedIds((prev) =>
      prev.includes(permissionId)
        ? prev.filter((id) => id !== permissionId)
        : [...prev, permissionId]
    );
  };

  const handleSave = async () => {
    setSaving(true);
    try {
      await syncProfilePermissions(profileId, selectedIds);
      alert("Permissoes atualizadas com sucesso!");
    } catch (error) {
      console.error("Erro ao salvar permissoes:", error);
    } finally {
      setSaving(false);
    }
  };

  // Agrupar permissoes por recurso (ex: "plans.view" → grupo "plans")
  const groupedPermissions = allPermissions.reduce(
    (acc, permission) => {
      const resource = permission.name.split(".")[0];
      if (!acc[resource]) acc[resource] = [];
      acc[resource].push(permission);
      return acc;
    },
    {} as Record<string, Permission[]>
  );

  if (loading) {
    return (
      <div className="space-y-4">
        <Skeleton className="h-8 w-48" />
        <Skeleton className="h-96 w-full" />
      </div>
    );
  }

  if (!profile) {
    return <p>Perfil nao encontrado.</p>;
  }

  return (
    <div className="space-y-6">
      <div className="flex items-center gap-4">
        <Button
          variant="ghost"
          size="icon"
          onClick={() => router.push("/profiles")}
        >
          <ArrowLeft className="h-4 w-4" />
        </Button>
        <div>
          <h1 className="text-2xl font-bold">{profile.name}</h1>
          <p className="text-muted-foreground">
            {profile.description || "Gerencie as permissoes deste perfil."}
          </p>
        </div>
      </div>

      <Card>
        <CardHeader className="flex flex-row items-center justify-between">
          <CardTitle>Permissoes</CardTitle>
          <Button onClick={handleSave} disabled={saving}>
            <Save className="mr-2 h-4 w-4" />
            {saving ? "Salvando..." : "Salvar"}
          </Button>
        </CardHeader>
        <CardContent>
          <div className="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
            {Object.entries(groupedPermissions).map(
              ([resource, permissions]) => (
                <div key={resource} className="space-y-2">
                  <h3 className="font-semibold capitalize">{resource}</h3>
                  {permissions.map((permission) => (
                    <div
                      key={permission.id}
                      className="flex items-center gap-2"
                    >
                      <Checkbox
                        id={`perm-${permission.id}`}
                        checked={selectedIds.includes(permission.id)}
                        onCheckedChange={() =>
                          handleToggle(permission.id)
                        }
                      />
                      <label
                        htmlFor={`perm-${permission.id}`}
                        className="text-sm cursor-pointer"
                      >
                        {permission.description || permission.name}
                      </label>
                    </div>
                  ))}
                </div>
              )
            )}
          </div>
        </CardContent>
      </Card>
    </div>
  );
}