import { test, expect } from "@playwright/test";

// Helper: login antes dos testes
async function loginAsAdmin(page: import("@playwright/test").Page) {
    await page.goto("/login");
    await page.getByLabel("Email").fill("admin@orderly.com");
    await page.getByLabel("Senha").fill("password");
    await page.getByRole("button", { name: "Entrar" }).click();
    await expect(page).toHaveURL(/.*dashboard/, { timeout: 15000 });
}

test.describe("Dashboard", () => {
    test.beforeEach(async ({ page }) => {
        await loginAsAdmin(page);
    });

    test("should display metric cards", async ({ page }) => {
        await expect(page.getByText("Pedidos Hoje")).toBeVisible();
        await expect(page.getByText("Faturamento Hoje")).toBeVisible();
        // Usar locator mais especifico para evitar conflito com sidebar
        await expect(
            page.locator("[data-slot='card-title']", { hasText: "Clientes" }),
        ).toBeVisible();
        await expect(
            page.locator("[data-slot='card-title']", { hasText: "Produtos" }),
        ).toBeVisible();
    });

    test("should display orders chart", async ({ page }) => {
        await expect(page.getByText("Pedidos por dia")).toBeVisible();
    });

    test("should display orders by status", async ({ page }) => {
        await expect(page.getByText("Pedidos por status")).toBeVisible();
    });

    test("should display recent evaluations", async ({ page }) => {
        await expect(page.getByText("Avaliacoes recentes")).toBeVisible();
    });
});