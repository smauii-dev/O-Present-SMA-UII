import type { z } from "zod";

interface AlpineValidationState {
  errors: Record<string, string>;
  touched: Record<string, boolean>;
}

declare global {
  interface Window {
    AlpineValidationMixin: (schema: z.ZodTypeAny) => Record<string, unknown>;
  }
}

function buildPartialData(
  validation: AlpineValidationState,
  form: Record<string, unknown> | undefined,
  field: string,
  value: unknown,
): Record<string, unknown> {
  const data: Record<string, unknown> = {};
  const keys = [...new Set([...Object.keys(validation.errors), field, ...Object.keys(form || {})])];
  for (const key of keys) {
    if (key === field) {
      data[key] = value;
    } else if (form && key in form) {
      data[key] = form[key];
    }
  }
  return data;
}

export function AlpineValidationMixin(schema: z.ZodType) {
  return {
    _validation: {
      errors: {} as Record<string, string>,
      touched: {} as Record<string, boolean>,
    } as AlpineValidationState,

    init() {
      this._validation = { errors: {}, touched: {} };
    },

    validateField(field: string, value: unknown) {
      this._validation.touched[field] = true;
      const self = this as unknown as { form?: Record<string, unknown> };
      const result = schema.safeParse(buildPartialData(this._validation, self.form, field, value));
      if (result.success) {
        delete this._validation.errors[field];
        return true;
      }
      const issue = result.error.issues.find((i: z.ZodIssue) => i.path.join(".") === field);
      if (issue) {
        this._validation.errors[field] = issue.message;
      }
      return !issue;
    },

    validateAll(data: unknown) {
      const result = schema.safeParse(data);
      if (result.success) {
        this._validation.errors = {};
        return true;
      }
      const errors: Record<string, string> = {};
      for (const issue of result.error.issues) {
        const path = issue.path.join(".");
        if (path && !errors[path]) {
          errors[path] = issue.message;
        }
      }
      this._validation.errors = errors;
      return false;
    },

    hasError(field: string): boolean {
      return this._validation.touched[field] && field in this._validation.errors;
    },

    getError(field: string): string {
      return this._validation.errors[field] || "";
    },

    clearErrors() {
      this._validation.errors = {};
      this._validation.touched = {};
    },

    markTouched(field: string) {
      this._validation.touched[field] = true;
    },
  };
}

export function registerAlpineValidation() {
  if (typeof window !== "undefined") {
    window.AlpineValidationMixin = AlpineValidationMixin;
  }
}
