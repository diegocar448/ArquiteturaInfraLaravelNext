import { describe, it, expect } from "vitest";
import { render, screen } from "@testing-library/react";

// O componente StarRating esta inline no dashboard, vamos extrair para teste
// Se voce quiser testar inline, importe o DashboardPage completo
function StarRating({ stars }: { stars: number }) {
    return (
        <div data-testid="star-rating">
            {Array.from({ length: 5 }).map((_, i) => (
                <span
                    key={i}
                    data-testid={`star-${i}`}
                    className={i < stars ? "filled" : "empty"}
                >
                    ★
                </span>
            ))}
        </div>
    );
}

describe("StarRating", () => {
    it("renders 5 stars", () => {
        render(<StarRating stars={3} />);

        const stars = screen.getAllByTestId(/^star-\d$/);
        expect(stars).toHaveLength(5);
    });

    it("fills correct number of stars", () => {
        render(<StarRating stars={3} />);

        const stars = screen.getAllByTestId(/^star-\d$/);
        const filled = stars.filter((s) => s.className.includes("filled"));
        const empty = stars.filter((s) => s.className.includes("empty"));

        expect(filled).toHaveLength(3);
        expect(empty).toHaveLength(2);
    });

    it("fills all stars for rating 5", () => {
        render(<StarRating stars={5} />);

        const stars = screen.getAllByTestId(/^star-\d$/);
        const filled = stars.filter((s) => s.className.includes("filled"));

        expect(filled).toHaveLength(5);
    });

    it("fills no stars for rating 0", () => {
        render(<StarRating stars={0} />);

        const stars = screen.getAllByTestId(/^star-\d$/);
        const empty = stars.filter((s) => s.className.includes("empty"));

        expect(empty).toHaveLength(5);
    });
});