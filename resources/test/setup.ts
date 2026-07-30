// Vitest setup — jsdom environment helpers
import { afterEach } from "vitest";

// Reset DOM between tests
afterEach(() => {
  document.body.innerHTML = "";
  document.head.innerHTML = "";
});
