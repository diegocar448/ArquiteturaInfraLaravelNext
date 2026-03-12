import { test, expect } from "@playwright/test";

test.describe("Admin Authentication", () => {
    test("should show login page", async ({ page }) => {
        await page.goto("/login");

        await expect(page.getByText("Entrar")).toBeVisible();
        await expect(page.getByLabel("Email")).toBeVisible();
        await expect(page.getByLabel("Senha")).toBeVisible();
    });

    test("should login with valid credentials", async ({ page }) => {
        await page.goto("/login");

        await page.getByLabel("Email").fill("admin@orderly.com");
        await page.getByLabel("Senha").fill("password");
        await page.getByRole("button", { name: "Entrar" }).click();

        // Aguardar o redirect para o dashboard (timeout maior para SSR)
        await expect(page).toHaveURL(/.*dashboard/, { timeout: 15000 });
        // Usar heading para evitar conflito com link da sidebar
        await expect(
            page.getByRole("heading", { name: "Dashboard" }),
        ).toBeVisible();
    });

    test("should show error for invalid credentials", async ({ page }) => {
        await page.goto("/login");

        await page.getByLabel("Email").fill("admin@orderly.com");
        await page.getByLabel("Senha").fill("wrong-password");
        await page.getByRole("button", { name: "Entrar" }).click();

        await expect(
            page.getByText(/credenciais|invalido|erro/i),
        ).toBeVisible({ timeout: 10000 });
    });
});