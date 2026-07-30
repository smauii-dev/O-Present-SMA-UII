import type { z } from "zod";
import { validateWith } from "./validate";

type HttpMethod = "GET" | "POST" | "PUT" | "DELETE";

interface ApiOptions {
  method?: HttpMethod;
  body?: unknown;
  headers?: Record<string, string>;
  validate?: z.ZodType;
}

interface ApiResponse<T = unknown> {
  success: boolean;
  data?: T;
  messages?: Record<string, string[]>;
  message?: string;
}

function getCsrfToken(): string {
  const meta = document.querySelector('meta[name="csrf-token"]');
  return meta?.getAttribute("content") || "";
}

export async function api<T = unknown>(
  url: string,
  options: ApiOptions = {},
): Promise<ApiResponse<T>> {
  const { method = "GET", body, headers = {}, validate } = options;

  if (validate && body) {
    const validation = validateWith(validate, body);
    if (!validation.success) {
      return {
        success: false,
        messages: Object.fromEntries(Object.entries(validation.errors).map(([k, v]) => [k, [v]])),
      };
    }
  }

  const fetchOptions: RequestInit = {
    method,
    headers: {
      "Content-Type": "application/json",
      "X-CSRF-TOKEN": getCsrfToken(),
      ...headers,
    },
    credentials: "same-origin",
  };

  if (body && method !== "GET") {
    fetchOptions.body = JSON.stringify(body);
  }

  try {
    const response = await fetch(url, fetchOptions);

    if (response.status === 401) {
      window.location.href = "/login";
      return { success: false, message: "Sesi berakhir" };
    }

    const data = await response.json().catch(() => null);

    if (!response.ok) {
      return {
        success: false,
        data: data?.data,
        messages: data?.messages,
        message: data?.message || `Error ${response.status}`,
      };
    }

    return {
      success: true,
      data: data?.data ?? data,
      messages: data?.messages,
      message: data?.message,
    };
  } catch (err) {
    return {
      success: false,
      message: err instanceof Error ? err.message : "Terjadi kesalahan jaringan",
    };
  }
}

export function get<T = unknown>(url: string) {
  return api<T>(url, { method: "GET" });
}

export function post<T = unknown>(url: string, body: unknown, schema?: z.ZodType) {
  return api<T>(url, { method: "POST", body, validate: schema });
}

export function put<T = unknown>(url: string, body: unknown, schema?: z.ZodType) {
  return api<T>(url, { method: "PUT", body, validate: schema });
}

export function del<T = unknown>(url: string) {
  return api<T>(url, { method: "DELETE" });
}
