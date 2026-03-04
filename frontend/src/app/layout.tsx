import type { Metadata } from "next";

export const metadata: Metadata = {
  title: "Orderly - Plataforma SaaS de Delivery",
  description:
    "Plataforma multi-tenant para delivery de comida. Gerencie seu restaurante, cardapio, pedidos e muito mais.",
};

export default function RootLayout({
  children,
}: Readonly<{
  children: React.ReactNode;
}>) {
  return (
    <html lang="pt-BR">
      <body>{children}</body>
    </html>
  );
}