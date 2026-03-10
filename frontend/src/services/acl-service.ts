import { apiClient } from "@/lib/api";
import type { Permission, Profile, Role } from "@/types/acl";
import type { PaginatedResponse } from "@/types/plan";

// Permissions
export async function getPermissions(): Promise<{ data: Permission[] }> {
  return apiClient<{ data: Permission[] }>("/v1/permissions");
}

// Profiles
export async function getProfiles(page = 1): Promise<PaginatedResponse<Profile>> {
  return apiClient<PaginatedResponse<Profile>>(`/v1/profiles?page=${page}`);
}

export async function getProfile(id: number): Promise<{ data: Profile }> {
  return apiClient<{ data: Profile }>(`/v1/profiles/${id}`);
}

export async function createProfile(data: {
  name: string;
  description?: string;
}): Promise<{ data: Profile }> {
  return apiClient<{ data: Profile }>("/v1/profiles", {
    method: "POST",
    body: JSON.stringify(data),
  });
}

export async function updateProfile(
  id: number,
  data: { name: string; description?: string }
): Promise<{ data: Profile }> {
  return apiClient<{ data: Profile }>(`/v1/profiles/${id}`, {
    method: "PUT",
    body: JSON.stringify(data),
  });
}

export async function deleteProfile(id: number): Promise<{ message: string }> {
  return apiClient<{ message: string }>(`/v1/profiles/${id}`, {
    method: "DELETE",
  });
}

export async function syncProfilePermissions(
  profileId: number,
  permissions: number[]
): Promise<unknown> {
  return apiClient(`/v1/profiles/${profileId}/permissions`, {
    method: "POST",
    body: JSON.stringify({ permissions }),
  });
}

// Roles
export async function getRoles(page = 1): Promise<PaginatedResponse<Role>> {
  return apiClient<PaginatedResponse<Role>>(`/v1/roles?page=${page}`);
}

export async function getRole(id: number): Promise<{ data: Role }> {
  return apiClient<{ data: Role }>(`/v1/roles/${id}`);
}

export async function createRole(data: {
  name: string;
  description?: string;
}): Promise<{ data: Role }> {
  return apiClient<{ data: Role }>("/v1/roles", {
    method: "POST",
    body: JSON.stringify(data),
  });
}

export async function updateRole(
  id: number,
  data: { name: string; description?: string }
): Promise<{ data: Role }> {
  return apiClient<{ data: Role }>(`/v1/roles/${id}`, {
    method: "PUT",
    body: JSON.stringify(data),
  });
}

export async function deleteRole(id: number): Promise<{ message: string }> {
  return apiClient<{ message: string }>(`/v1/roles/${id}`, {
    method: "DELETE",
  });
}

export async function syncRolePermissions(
  roleId: number,
  permissions: number[]
): Promise<unknown> {
  return apiClient(`/v1/roles/${roleId}/permissions`, {
    method: "POST",
    body: JSON.stringify({ permissions }),
  });
}