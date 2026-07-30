declare module "alpinejs" {
  const Alpine: {
    start(): void;
    data(name: string, callback: (...args: unknown[]) => Record<string, unknown>): void;
    store(name: string, value: unknown): void;
  };
  export default Alpine;
}
