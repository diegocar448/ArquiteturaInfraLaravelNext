import { test, expect } from "@playwright/test";

async function loginAsAdmin(page: import("@playwright/test").Page) {
    await page.goto("/login");
    await page.getByLabel("Email").fill("admin@orderly.com");
    await page.getByLabel("Senha").fill("password");
    await page.getByRole("button", { name: "Entrar" }).click();
    await expect(page).toHaveURL(/.*dashboard/, { timeout: 15000 });
}

test.describe("Order Flow", () => {
    test("should navigate to orders page", async ({ page }) => {
        await loginAsAdmin(page);

        await page.getByRole("link", { name: "Pedidos" }).click();

        await expect(page).toHaveURL(/.*orders/);
        // Usar heading para evitar conflito com link da sidebar
        await expect(
            page.getByRole("heading", { name: "Pedidos" }),
        ).toBeVisible();
    });

    test("should display orders page content", async ({ page }) => {
        await loginAsAdmin(page);
        await page.goto("/orders");

        await expect(page).toHaveURL(/.*orders/);
        await expect(
            page.getByRole("heading", { name: "Pedidos" }),
        ).toBeVisible();
    });
});