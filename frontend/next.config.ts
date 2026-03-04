import type { NextConfig } from "next";

const nextConfig: NextConfig = {
  // Standalone output para producao (imagem Docker minima)
  output: "standalone",

  // Rewrites para proxy da API em desenvolvimento
  // Em producao, o Nginx faz esse roteamento
  async rewrites() {
    return [
      {
        source: "/api/:path*",
        destination: `${process.env.NEXT_PUBLIC_API_URL || "http://backend:9000"}/:path*`,
      },
    ];
  },
};

export default nextConfig;