"use client";

import { useRouter } from "next/navigation";
import { useClientAuthStore } from "@/stores/client-auth-store";
import { Button } from "@/components/ui/button";
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from "@/components/ui/card";
import { LogOut, User } from "lucide-react";

export default function ClientPedidosPage() {
    const router = useRouter();
    const { client, logout } = useClientAuthStore();

    const handleLogout = async () => {
        await logout();
        router.push("/client/login");
    };

    return (
        <div className="min-h-screen bg-muted/50">
            <header className="border-b bg-background">
                <div className="mx-auto flex max-w-4xl items-center justify-between px-4 py-3">
                    <h1 className="text-lg font-bold">Orderly</h1>
                    <div className="flex items-center gap-3">
                        <span className="flex items-center gap-1.5 text-sm text-muted-foreground">
                            <User className="h-4 w-4" />
                            {client?.name}
                        </span>
                        <Button
                            variant="ghost"
                            size="sm"
                            onClick={handleLogout}
                        >
                            <LogOut className="mr-1.5 h-4 w-4" />
                            Sair
                        </Button>
                    </div>
                </div>
            </header>

            <main className="mx-auto max-w-4xl px-4 py-8">
                <Card>
                    <CardHeader>
                        <CardTitle>Meus Pedidos</CardTitle>
                        <CardDescription>
                            Acompanhe seus pedidos e avalie apos a entrega.
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <p className="text-sm text-muted-foreground">
                            Nenhum pedido encontrado. Em breve voce podera
                            acompanhar seus pedidos por aqui.
                        </p>
                    </CardContent>
                </Card>
            </main>
        </div>
    );
}
