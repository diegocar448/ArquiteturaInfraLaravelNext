import { NextResponse } from "next/server";
import type { NextRequest } from "next/server";

export function middleware(request: NextRequest) {
  const { pathname } = request.nextUrl;

  // --- Rotas de cliente ---
  const isClientLoginPage = pathname === "/client/login";
  const isClientRegisterPage = pathname === "/client/register";
  const isClientProtectedRoute =
    pathname.startsWith("/client/") && !isClientLoginPage && !isClientRegisterPage;

  if (isClientProtectedRoute || isClientLoginPage || isClientRegisterPage) {
    const clientToken = request.cookies.get("client_token")?.value;

    if (isClientProtectedRoute && !clientToken) {
      return NextResponse.redirect(new URL("/client/login", request.url));
    }

    if ((isClientLoginPage || isClientRegisterPage) && clientToken) {
      return NextResponse.redirect(new URL("/client/pedidos", request.url));
    }

    return NextResponse.next();
  }

  // --- Rotas de admin ---
  const token = request.cookies.get("token")?.value;

  const isLoginPage = pathname === "/login";
  const isProtectedRoute = pathname.startsWith("/dashboard") ||
    pathname.startsWith("/plans") ||
    pathname.startsWith("/profiles") ||
    pathname.startsWith("/roles") ||
    pathname.startsWith("/orders") ||
    pathname.startsWith("/products") ||
    pathname.startsWith("/customers") ||
    pathname.startsWith("/tables") ||
    pathname.startsWith("/reviews") ||
    pathname.startsWith("/settings");

  // Redirecionar para login se nao autenticado
  if (isProtectedRoute && !token) {
    return NextResponse.redirect(new URL("/login", request.url));
  }

  // Redirecionar para dashboard se ja autenticado
  if (isLoginPage && token) {
    return NextResponse.redirect(new URL("/dashboard", request.url));
  }

  return NextResponse.next();
}

export const config = {
  matcher: [
    "/dashboard/:path*",
    "/plans/:path*",
    "/profiles/:path*",
    "/roles/:path*",
    "/orders/:path*",
    "/products/:path*",
    "/customers/:path*",
    "/tables/:path*",
    "/reviews/:path*",
    "/settings/:path*",
    "/login",
    "/client/:path*",
  ],
};