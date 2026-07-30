import { describe, expect, it } from "vitest";
import { z } from "zod";
import {
  createFormValidator,
  useFormValidation,
  validateField,
  validateWith,
} from "@/ts/lib/validate";

const testSchema = z.object({
  name: z.string().min(1, "Name is required"),
  age: z.number().min(18, "Must be 18+"),
  email: z.string().email("Invalid email"),
});

// ─── validateWith ────────────────────────────────────────
describe("validateWith", () => {
  it("returns success with parsed data for valid input", () => {
    const result = validateWith(testSchema, { name: "Budi", age: 25, email: "budi@test.com" });
    expect(result.success).toBe(true);
    if (result.success) {
      expect(result.data.name).toBe("Budi");
      expect(result.data.age).toBe(25);
    }
  });

  it("returns errors for invalid input", () => {
    const result = validateWith(testSchema, { name: "", age: 15, email: "bad" });
    expect(result.success).toBe(false);
    if (!result.success) {
      expect(result.errors).toHaveProperty("name");
      expect(result.errors).toHaveProperty("age");
      expect(result.errors).toHaveProperty("email");
    }
  });

  it("returns first error per field only", () => {
    const schema = z.object({ x: z.string().min(1, "min1").min(5, "min5") });
    const result = validateWith(schema, { x: "" });
    expect(result.success).toBe(false);
    if (!result.success) {
      expect(result.errors.x).toBe("min1");
    }
  });
});

// ─── useFormValidation ───────────────────────────────────
describe("useFormValidation", () => {
  it("validates and clears errors on success", () => {
    const form = useFormValidation(testSchema);
    expect(form.validate({ name: "", age: 10, email: "bad" })).toBe(false);
    expect(form.hasError("name")).toBe(true);

    expect(form.validate({ name: "OK", age: 20, email: "ok@test.com" })).toBe(true);
    expect(form.hasError("name")).toBe(false);
  });

  it("getError returns message for touched field", () => {
    const form = useFormValidation(testSchema);
    form.validate({ name: "", age: 10, email: "bad" });
    expect(form.getError("name")).toBe("Name is required");
  });

  it("clearError removes single field error", () => {
    const form = useFormValidation(testSchema);
    form.validate({ name: "", age: 10, email: "bad" });
    form.clearError("name");
    expect(form.hasError("name")).toBe(false);
    expect(form.hasError("age")).toBe(true);
  });

  it("clearAll removes all errors", () => {
    const form = useFormValidation(testSchema);
    form.validate({ name: "", age: 10, email: "bad" });
    form.clearAll();
    expect(form.hasError("name")).toBe(false);
    expect(form.hasError("age")).toBe(false);
  });
});

// ─── validateField ───────────────────────────────────────
describe("validateField", () => {
  it("returns null for valid field in valid object", () => {
    expect(
      validateField(testSchema, "name", { name: "Budi", age: 25, email: "budi@test.com" }),
    ).toBeNull();
  });

  it("returns error message for invalid field in object", () => {
    expect(validateField(testSchema, "name", { name: "", age: 25, email: "budi@test.com" })).toBe(
      "Name is required",
    );
    expect(
      validateField(testSchema, "age", { name: "Budi", age: 10, email: "budi@test.com" }),
    ).toBe("Must be 18+");
  });

  it("returns null for unknown field", () => {
    expect(
      validateField(testSchema, "unknown", { name: "Budi", age: 25, email: "budi@test.com" }),
    ).toBeNull();
  });
});

// ─── createFormValidator ─────────────────────────────────
describe("createFormValidator", () => {
  it("validates full data", () => {
    const validator = createFormValidator(testSchema);
    const result = validator.validate({ name: "OK", age: 20, email: "ok@test.com" });
    expect(result.success).toBe(true);
  });

  it("validates individual field using full object", () => {
    const validator = createFormValidator(testSchema);
    expect(validator.validateField("age", { name: "OK", age: 10, email: "ok@test.com" })).toBe(
      "Must be 18+",
    );
    expect(
      validator.validateField("age", { name: "OK", age: 25, email: "ok@test.com" }),
    ).toBeNull();
  });
});
