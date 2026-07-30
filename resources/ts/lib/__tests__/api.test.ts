import { beforeEach, describe, expect, it, vi } from "vitest";
import { api, del, get, post, put } from "@/ts/lib/api";

// Mock document.querySelector for CSRF token
beforeEach(() => {
  document.head.innerHTML = '<meta name="csrf-token" content="test-csrf">';
  vi.restoreAllMocks();
});

// ─── api() ───────────────────────────────────────────────
describe("api()", () => {
  it("sends GET request with correct headers", async () => {
    const mockFetch = vi.fn().mockResolvedValue({
      ok: true,
      status: 200,
      json: async () => ({ data: { id: 1 } }),
    });
    vi.stubGlobal("fetch", mockFetch);

    const result = await api("/test", { method: "GET" });
    expect(result.success).toBe(true);
    expect(result.data).toEqual({ id: 1 });

    const [, options] = mockFetch.mock.calls[0];
    expect(options.method).toBe("GET");
    expect(options.headers["X-CSRF-TOKEN"]).toBe("test-csrf");
    expect(options.headers["Content-Type"]).toBe("application/json");
    expect(options.credentials).toBe("same-origin");
  });

  it("sends POST with JSON body", async () => {
    const mockFetch = vi.fn().mockResolvedValue({
      ok: true,
      status: 200,
      json: async () => ({ data: { created: true } }),
    });
    vi.stubGlobal("fetch", mockFetch);

    const body = { name: "Test" };
    const result = await api("/create", { method: "POST", body });
    expect(result.success).toBe(true);

    const [, options] = mockFetch.mock.calls[0];
    expect(options.method).toBe("POST");
    expect(options.body).toBe(JSON.stringify(body));
  });

  it("validates body before sending when validate option provided", async () => {
    const mockFetch = vi.fn();
    vi.stubGlobal("fetch", mockFetch);

    const schema = {
      safeParse: vi
        .fn()
        .mockReturnValue({ success: false, error: { issues: [{ path: ["x"], message: "bad" }] } }),
    };
    const result = await api("/test", {
      method: "POST",
      body: { x: 1 },
      validate: schema as never,
    });
    expect(result.success).toBe(false);
    expect(mockFetch).not.toHaveBeenCalled();
  });

  it("returns error on non-ok response", async () => {
    vi.stubGlobal(
      "fetch",
      vi.fn().mockResolvedValue({
        ok: false,
        status: 422,
        json: async () => ({ messages: { email: ["Taken"] } }),
      }),
    );

    const result = await api("/test");
    expect(result.success).toBe(false);
    expect(result.messages).toEqual({ email: ["Taken"] });
  });

  it("redirects to /login on 401", async () => {
    const assignSpy = vi.fn();
    Object.defineProperty(window, "location", {
      value: {
        set href(v: string) {
          assignSpy(v);
        },
      },
      writable: true,
    });

    vi.stubGlobal(
      "fetch",
      vi.fn().mockResolvedValue({
        ok: false,
        status: 401,
        json: async () => ({}),
      }),
    );

    await api("/test");
    expect(assignSpy).toHaveBeenCalledWith("/login");
  });

  it("returns network error message on fetch failure", async () => {
    vi.stubGlobal("fetch", vi.fn().mockRejectedValue(new Error("Network down")));

    const result = await api("/test");
    expect(result.success).toBe(false);
    expect(result.message).toBe("Network down");
  });

  it("does not send body on GET request", async () => {
    const mockFetch = vi.fn().mockResolvedValue({
      ok: true,
      status: 200,
      json: async () => ({}),
    });
    vi.stubGlobal("fetch", mockFetch);

    await api("/test", { method: "GET", body: { should: "not send" } });
    const [, options] = mockFetch.mock.calls[0];
    expect(options.body).toBeUndefined();
  });
});

// ─── Convenience wrappers ────────────────────────────────
describe("get()", () => {
  it("calls api with GET method", async () => {
    const mockFetch = vi.fn().mockResolvedValue({
      ok: true,
      status: 200,
      json: async () => ({ data: "ok" }),
    });
    vi.stubGlobal("fetch", mockFetch);

    const result = await get("/data");
    expect(result.success).toBe(true);
    expect(mockFetch.mock.calls[0][1].method).toBe("GET");
  });
});

describe("post()", () => {
  it("calls api with POST method and body", async () => {
    const mockFetch = vi.fn().mockResolvedValue({
      ok: true,
      status: 200,
      json: async () => ({ data: "created" }),
    });
    vi.stubGlobal("fetch", mockFetch);

    await post("/create", { name: "test" });
    const [, options] = mockFetch.mock.calls[0];
    expect(options.method).toBe("POST");
    expect(options.body).toBe(JSON.stringify({ name: "test" }));
  });
});

describe("put()", () => {
  it("calls api with PUT method", async () => {
    const mockFetch = vi.fn().mockResolvedValue({
      ok: true,
      status: 200,
      json: async () => ({}),
    });
    vi.stubGlobal("fetch", mockFetch);

    await put("/update/1", { name: "new" });
    const [, options] = mockFetch.mock.calls[0];
    expect(options.method).toBe("PUT");
  });
});

describe("del()", () => {
  it("calls api with DELETE method", async () => {
    const mockFetch = vi.fn().mockResolvedValue({
      ok: true,
      status: 200,
      json: async () => ({}),
    });
    vi.stubGlobal("fetch", mockFetch);

    await del("/delete/1");
    const [, options] = mockFetch.mock.calls[0];
    expect(options.method).toBe("DELETE");
  });
});
