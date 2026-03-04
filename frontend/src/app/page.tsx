export default function Home() {
  return (
    <main
      style={{
        display: "flex",
        flexDirection: "column",
        alignItems: "center",
        justifyContent: "center",
        minHeight: "100vh",
        fontFamily: "system-ui, sans-serif",
        backgroundColor: "#0a0a0a",
        color: "#ededed",
      }}
    >
      <h1 style={{ fontSize: "3rem", marginBottom: "0.5rem" }}>Orderly</h1>
      <p style={{ fontSize: "1.25rem", color: "#888" }}>
        Plataforma SaaS Multi-Tenant de Delivery
      </p>
      <div
        style={{
          marginTop: "2rem",
          padding: "1rem 2rem",
          border: "1px solid #333",
          borderRadius: "8px",
          fontSize: "0.9rem",
          color: "#666",
        }}
      >
        <p>Stack: Next.js 15 + Laravel 12 + PostgreSQL + Redis + Kafka</p>
        <p style={{ marginTop: "0.5rem" }}>
          Ambiente Docker funcionando com sucesso!
        </p>
      </div>
    </main>
  );
}