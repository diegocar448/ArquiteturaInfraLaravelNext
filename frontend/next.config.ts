import type { NextConfig } from "next";

const nextConfig: NextConfig = {
  // Standalone output para producao (imagem Docker minima)
  output: "standalone",
  // Permitir acesso via 127.0.0.1 e localhost em dev (WSL2)
  allowedDevOrigins: ["127.0.0.1", "localhost"],
};

export default nextConfig;