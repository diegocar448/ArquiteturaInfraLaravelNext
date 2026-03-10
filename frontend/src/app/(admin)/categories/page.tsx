
"use client";

import { useEffect, useState } from "react";
import { getCategories } from "@/services/category-service";
import type { Category } from "@/types/catalog";
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from "@/components/ui/table";
import { Button } from "@/components/ui/button";
import { Skeleton } from "@/components/ui/skeleton";
import { Plus, Pencil, Trash2 } from "lucide-react";
import { CategoryFormDialog } from "@/components/categories/category-form-dialog";
import { DeleteCategoryDialog } from "@/components/categories/delete-category-dialog";
import { TenantRequiredAlert } from "@/components/tenant-required-alert";

export default function CategoriesPage() {
  const [categories, setCategories] = useState<Category[]>([]);
  const [loading, setLoading] = useState(true);
  const [createOpen, setCreateOpen] = useState(false);
  const [editCategory, setEditCategory] = useState<Category | null>(null);
  const [deleteState, setDeleteState] = useState<Category | null>(null);

  const fetchCategories = async () => {
    try {
      const response = await getCategories();
      setCategories(response.data);
    } catch (error) {
      console.error("Erro ao carregar categorias:", error);
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    fetchCategories();
  }, []);

  const handleSaved = () => {
    setCreateOpen(false);
    setEditCategory(null);
    fetchCategories();
  };

  const handleDeleted = () => {
    setDeleteState(null);
    fetchCategories();
  };

  return (
    <div className="space-y-4">
      <TenantRequiredAlert resource="categorias" />

      <div className="flex items-center justify-between">
        <h1 className="text-2xl font-bold">Categorias</h1>
        <Button onClick={() => setCreateOpen(true)}>
          <Plus className="mr-2 h-4 w-4" />
          Nova Categoria
        </Button>
      </div>

      {loading ? (
        <div className="space-y-2">
          {Array.from({ length: 5 }).map((_, i) => (
            <Skeleton key={i} className="h-12 w-full" />
          ))}
        </div>
      ) : (
        <Table>
          <TableHeader>
            <TableRow>
              <TableHead>Nome</TableHead>
              <TableHead>Slug</TableHead>
              <TableHead>Descricao</TableHead>
              <TableHead className="w-[100px]">Acoes</TableHead>
            </TableRow>
          </TableHeader>
          <TableBody>
            {categories.length === 0 ? (
              <TableRow>
                <TableCell colSpan={4} className="text-center text-muted-foreground">
                  Nenhuma categoria cadastrada.
                </TableCell>
              </TableRow>
            ) : (
              categories.map((cat) => (
                <TableRow key={cat.id}>
                  <TableCell className="font-medium">{cat.name}</TableCell>
                  <TableCell className="text-muted-foreground">{cat.url}</TableCell>
                  <TableCell>{cat.description || "—"}</TableCell>
                  <TableCell>
                    <div className="flex gap-1">
                      <Button
                        size="icon"
                        variant="ghost"
                        onClick={() => setEditCategory(cat)}
                      >
                        <Pencil className="h-4 w-4" />
                      </Button>
                      <Button
                        size="icon"
                        variant="ghost"
                        onClick={() => setDeleteState(cat)}
                      >
                        <Trash2 className="h-4 w-4" />
                      </Button>
                    </div>
                  </TableCell>
                </TableRow>
              ))
            )}
          </TableBody>
        </Table>
      )}

      <CategoryFormDialog
        open={createOpen}
        onOpenChange={setCreateOpen}
        onSaved={handleSaved}
      />

      {editCategory && (
        <CategoryFormDialog
          open={!!editCategory}
          onOpenChange={() => setEditCategory(null)}
          onSaved={handleSaved}
          category={editCategory}
        />
      )}

      <DeleteCategoryDialog
        category={deleteState}
        onOpenChange={() => setDeleteState(null)}
        onDeleted={handleDeleted}
      />
    </div>
  );
}