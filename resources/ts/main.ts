import "../css/main.css";
import Alpine from "alpinejs";
import htmx from "htmx.org";
import type { z } from "zod";
import { registerModalComponent } from "./components/Modal";
import { registerAlpineValidation } from "./lib/alpine-validation";
import * as api from "./lib/api";
import {
  changePasswordSchema,
  clockInSchema,
  forgotPasswordSchema,
  jabatanSchema,
  ketidakhadiranSchema,
  loginSchema,
  lokasiSchema,
  pegawaiSchema,
  profileUpdateSchema,
  rekapFilterSchema,
  resetPasswordSchema,
} from "./schemas";

declare global {
  interface Window {
    Alpine: typeof Alpine;
    htmx: typeof htmx;
    api: typeof api;
    showToast: typeof showToast;
    formatTime: typeof formatTime;
    formatDate: typeof formatDate;
    schemas: Record<string, z.ZodTypeAny>;
    validate: {
      form: (
        schemaName: string,
        data: unknown,
      ) => { success: boolean; errors?: Record<string, string>; data?: unknown };
    };
    AlpineValidationMixin: (schema: z.ZodTypeAny) => Record<string, unknown>;
  }
}

window.Alpine = Alpine;
window.htmx = htmx;
window.api = api;

htmx.config.defaultSwapStyle = "innerHTML";
htmx.config.historyEnabled = false;
htmx.config.requestClass = "htmx-request";
htmx.config.indicatorClass = "htmx-indicator";

document.addEventListener("htmx:configRequest", (e: Event) => {
  const detail = (e as CustomEvent).detail;
  const meta = document.querySelector('meta[name="csrf-token"]');
  if (meta) {
    detail.headers["X-CSRF-TOKEN"] = meta.getAttribute("content") || "";
  }
  // CI4 cookie-based CSRF (forms + HTMX partials)
  const match = document.cookie.match(/(?:^|;\s*)csrf_cookie_name=([^;]+)/);
  if (match?.[1]) {
    detail.headers["X-CSRF-TOKEN"] = decodeURIComponent(match[1]);
  }
});

// Auto-disable submit buttons during HTMX form submission to prevent race conditions & double-clicks
document.addEventListener("htmx:configRequest", (e: Event) => {
  const detail = (e as CustomEvent).detail;
  const element = detail.elt as HTMLElement;
  if (element && element.tagName === "FORM") {
    const submitButtons = element.querySelectorAll('button[type="submit"]');
    submitButtons.forEach((btn) => {
      btn.setAttribute("disabled", "true");
      btn.classList.add("opacity-60", "cursor-not-allowed");
    });
  }
});

document.addEventListener("htmx:afterRequest", (e: Event) => {
  const detail = (e as CustomEvent).detail;
  const element = detail.elt as HTMLElement;
  if (element && element.tagName === "FORM") {
    const submitButtons = element.querySelectorAll('button[type="submit"]');
    submitButtons.forEach((btn) => {
      btn.removeAttribute("disabled");
      btn.classList.remove("opacity-60", "cursor-not-allowed");
    });
  }
});

document.addEventListener("htmx:responseError", (e: Event) => {
  const detail = (e as CustomEvent).detail;
  if (detail.xhr.status === 401) {
    window.location.href = "/login";
  }
});

/**
 * HTMX + Alpine: Alpine 3 already MutationObserver-inits new nodes.
 * Do NOT call Alpine.initTree() on every swap — that re-binds x-data and
 * causes double listeners / “dobel teks” on buttons with x-show pairs.
 *
 * Rules of thumb:
 * - Partial/fragment swaps (hx-get/hx-post into a target) → fine
 * - Avoid hx-boost on forms that return full-page redirects
 * - Prefer one element + x-text over two x-show siblings for loading labels
 * - Always x-cloak on elements that should be hidden until Alpine runs
 */

function showToast(message: string, type: "success" | "error" | "info" | "warning" = "info") {
  window.dispatchEvent(new CustomEvent("toast", { detail: { message, type } }));
}

window.showToast = showToast;

function formatTime(dateStr: string): string {
  if (!dateStr) return "-";
  const d = new Date(dateStr);
  return d.toLocaleTimeString("id-ID", { hour: "2-digit", minute: "2-digit" });
}

function formatDate(dateStr: string): string {
  if (!dateStr) return "-";
  const d = new Date(dateStr);
  return d.toLocaleDateString("id-ID", {
    day: "numeric",
    month: "long",
    year: "numeric",
  });
}

window.formatTime = formatTime;
window.formatDate = formatDate;

window.schemas = {
  login: loginSchema,
  forgotPassword: forgotPasswordSchema,
  resetPassword: resetPasswordSchema,
  profileUpdate: profileUpdateSchema,
  changePassword: changePasswordSchema,
  pegawai: pegawaiSchema,
  jabatan: jabatanSchema,
  lokasi: lokasiSchema,
  ketidakhadiran: ketidakhadiranSchema,
  clockIn: clockInSchema,
  rekapFilter: rekapFilterSchema,
} as Record<string, z.ZodTypeAny>;

import { validateWith } from "./lib/validate";

window.validate = {
  form(schemaName: string, data: unknown) {
    const schema = window.schemas[schemaName];
    if (!schema) return { success: false, errors: { _form: "Unknown schema" } };
    return validateWith(schema, data);
  },
};

registerAlpineValidation();

registerModalComponent(Alpine);

// Register validation mixin as Alpine data component
Alpine.data("validation", (schema: unknown) => {
  const mixin = window.AlpineValidationMixin(schema as z.ZodTypeAny);
  return {
    ...mixin,
  };
});

Alpine.start();
