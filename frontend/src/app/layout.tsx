import type { Metadata } from "next";
import "./globals.css";

export const metadata: Metadata = {
  title: "Orderly - Plataforma SaaS de Delivery",
  description: "Sistema completo de gestao para restaurantes e delivery",
};

export default function RootLayout({
  children,
}: Readonly<{
  children: React.ReactNode;
}>) {
  return (
    <html lang="pt-BR" className="dark">
      <body className="min-h-screen bg-background text-foreground antialiased">
        {children}
      </body>
    </html>
  );
}