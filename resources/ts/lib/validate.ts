import type { z } from "zod";

interface ValidationErrors {
  [field: string]: string;
}

export function validateWith<T>(
  schema: z.ZodType<T>,
  data: unknown,
):
  | {
      success: true;
      data: T;
    }
  | {
      success: false;
      errors: ValidationErrors;
    } {
  const result = schema.safeParse(data);
  if (result.success) {
    return { success: true, data: result.data };
  }
  return { success: false, errors: formatZodErrors(result.error) };
}

function formatZodErrors(error: z.ZodError): ValidationErrors {
  const errors: ValidationErrors = {};
  for (const issue of error.issues) {
    const path = issue.path.join(".");
    if (!errors[path]) {
      errors[path] = issue.message;
    }
  }
  return errors;
}

export function useFormValidation(schema: z.ZodType) {
  return {
    errors: {} as ValidationErrors,

    validate(data: unknown): boolean {
      const result = validateWith(schema, data);
      if (result.success) {
        this.errors = {};
        return true;
      }
      this.errors = result.errors;
      return false;
    },

    hasError(field: string): boolean {
      return field in this.errors;
    },

    getError(field: string): string {
      return this.errors[field] || "";
    },

    clearError(field: string): void {
      delete this.errors[field];
    },

    clearAll(): void {
      this.errors = {};
    },
  };
}

export function validateField(schema: z.ZodType, field: string, value: unknown): string | null {
  const result = schema.safeParse(value);
  if (result.success) return null;

  const issue = result.error.issues.find((i) => i.path.join(".") === field);
  return issue ? issue.message : null;
}

export function createFormValidator(schema: z.ZodType) {
  return {
    validate(data: unknown) {
      return validateWith(schema, data);
    },

    validateField(field: string, value: unknown) {
      return validateField(schema, field, value);
    },
  };
}
