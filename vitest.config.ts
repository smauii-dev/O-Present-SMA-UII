import path from "node:path";
import { defineConfig } from "vitest/config";

export default defineConfig({
  resolve: {
    alias: {
      "@": path.resolve(__dirname, "resources"),
      "@/*": path.resolve(__dirname, "resources/*"),
    },
  },
  test: {
    environment: "jsdom",
    globals: true,
    include: ["resources/**/*.{test,spec}.{ts,tsx}"],
    setupFiles: ["resources/test/setup.ts"],
    server: {
      deps: {
        inline: ["zod"],
      },
    },
    coverage: {
      provider: "v8",
      reporter: ["text", "json", "html"],
      include: ["resources/ts/**/*.ts"],
      exclude: ["resources/**/*.d.ts", "resources/test/**", "resources/ts/main.ts"],
    },
  },
});
