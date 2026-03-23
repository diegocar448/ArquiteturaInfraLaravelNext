"use client";

import { useState } from "react";

export default function TestFormPage() {
  const [submitted, setSubmitted] = useState(false);

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    setSubmitted(true);
  };

  return (
    <div style={{ padding: 40 }}>
      <h1>Test Form - Hydration Check</h1>
      <p>If you see this text change after submit, React hydration is working.</p>
      {submitted && <p style={{ color: "green" }}>FORM SUBMITTED VIA REACT!</p>}
      <form onSubmit={handleSubmit}>
        <input type="text" name="test" placeholder="type something" />
        <button type="submit">Submit</button>
      </form>
    </div>
  );
}
