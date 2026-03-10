"use client";

import { useEffect, useState } from "react";
import { getProducts } from "@/services/product-service";
import type { Product } from "@/types/catalog";
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from "@/components/ui/table";
import { Button } from "@/components/ui/button";
import { Badge } from "@/components/ui/badge";
import { Skeleton } from "@/components/ui/skeleton";
import { Plus, Pencil, Trash2 } from "lucide-react";
import { ProductFormDialog } from "@/components/products/product-form-dialog";
import { DeleteProductDialog } from "@/components/products/delete-product-dialog";
import { TenantRequiredAlert } from "@/components/tenant-required-alert";

const flagLabels: Record<string, string> = {
  active: "Ativo",
  inactive: "Inativo",
  featured: "Destaque",
};

const flagVariants: Record<string, "default" | "secondary" | "destructive"> = {
  active: "default",
  inactive: "destructive",
  featured: "secondary",
};

export default function ProductsPage() {
  const [products, setProducts] = useState<Product[]>([]);
  const [loading, setLoading] = useState(true);
  const [createOpen, setCreateOpen] = useState(false);
  const [editProduct, setEditProduct] = useState<Product | null>(null);
  const [deleteState, setDeleteState] = useState<Product | null>(null);

  const fetchProducts = async () => {
    try {
      const response = await getProducts();
      setProducts(response.data);
    } catch (error) {
      console.error("Erro ao carregar produtos:", error);
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    fetchProducts();
  }, []);

  const handleSaved = () => {
    setCreateOpen(false);
    setEditProduct(null);
    fetchProducts();
  };

  const handleDeleted = () => {
    setDeleteState(null);
    fetchProducts();
  };

  const formatPrice = (price: string) => {
    return new Intl.NumberFormat("pt-BR", {
      style: "currency",
      currency: "BRL",
    }).format(Number(price));
  };

  return (
    <div className="space-y-4">
      <TenantRequiredAlert resource="produtos" />

      <div className="flex items-center justify-between">
        <h1 className="text-2xl font-bold">Produtos</h1>
        <Button onClick={() => setCreateOpen(true)}>
          <Plus className="mr-2 h-4 w-4" />
          Novo Produto
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
              <TableHead>Titulo</TableHead>
              <TableHead>Preco</TableHead>
              <TableHead>Status</TableHead>
              <TableHead>Descricao</TableHead>
              <TableHead className="w-[100px]">Acoes</TableHead>
            </TableRow>
          </TableHeader>
          <TableBody>
            {products.length === 0 ? (
              <TableRow>
                <TableCell colSpan={5} className="text-center text-muted-foreground">
                  Nenhum produto cadastrado.
                </TableCell>
              </TableRow>
            ) : (
              products.map((product) => (
                <TableRow key={product.id}>
                  <TableCell className="font-medium">{product.title}</TableCell>
                  <TableCell>{formatPrice(product.price)}</TableCell>
                  <TableCell>
                    <Badge variant={flagVariants[product.flag] || "default"}>
                      {flagLabels[product.flag] || product.flag}
                    </Badge>
                  </TableCell>
                  <TableCell className="max-w-[200px] truncate">
                    {product.description || "—"}
                  </TableCell>
                  <TableCell>
                    <div className="flex gap-1">
                      <Button
                        size="icon"
                        variant="ghost"
                        onClick={() => setEditProduct(product)}
                      >
                        <Pencil className="h-4 w-4" />
                      </Button>
                      <Button
                        size="icon"
                        variant="ghost"
                        onClick={() => setDeleteState(product)}
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

      <ProductFormDialog
        open={createOpen}
        onOpenChange={setCreateOpen}
        onSaved={handleSaved}
      />

      {editProduct && (
        <ProductFormDialog
          open={!!editProduct}
          onOpenChange={() => setEditProduct(null)}
          onSaved={handleSaved}
          product={editProduct}
        />
      )}

      <DeleteProductDialog
        product={deleteState}
        onOpenChange={() => setDeleteState(null)}
        onDeleted={handleDeleted}
      />
    </div>
  );
}