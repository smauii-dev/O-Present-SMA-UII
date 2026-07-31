/**
 * Global AlpineJS Modal Component
 * Register as: Alpine.data('modal', Modal)
 *
 * Usage in Twig:
 *   x-data="modal({ size: 'md', centered: true, closeOnBackdrop: true, closeOnEscape: true })"
 *   x-show="open"
 *   @close-modal.window="hide()"
 *
 * Notes:
 * - Never call Alpine.data('modal') without the factory — that unregisters the component
 *   and causes "Illegal invocation" on x-show="open" (falls through to window.open).
 * - HTMX partials swapped into #modal-host auto-open on init.
 */

export interface ModalOptions {
  size?: "sm" | "md" | "lg" | "xl" | "full";
  centered?: boolean;
  closeOnBackdrop?: boolean;
  closeOnEscape?: boolean;
  /** When true (default), open immediately if mounted inside #modal-host */
  openOnInit?: boolean;
  onOpen?: () => void;
  onClose?: () => void;
}

type ModalComponent = {
  open: boolean;
  size: NonNullable<ModalOptions["size"]>;
  centered: boolean;
  closeOnBackdrop: boolean;
  closeOnEscape: boolean;
  openOnInit: boolean;
  onOpen?: () => void;
  onClose?: () => void;
  init(): void;
  show(title?: string, newSize?: ModalOptions["size"], newCentered?: boolean): void;
  hide(): void;
  toggle(): void;
  handleKeydown(e: KeyboardEvent): void;
  handleBackdropClick(e: MouseEvent): void;
  focusFirst(): void;
  $el: HTMLElement;
  $refs: { panel?: HTMLElement };
  $nextTick: (fn: () => void) => void;
};

/**
 * Alpine v3 data component factory.
 * Must return a plain object with reactive state + methods (not a class instance).
 */
function Modal(
  this: unknown,
  options: ModalOptions = {},
): Omit<ModalComponent, "$el" | "$refs" | "$nextTick"> {
  const {
    size = "md",
    centered = true,
    closeOnBackdrop = true,
    closeOnEscape = true,
    openOnInit = true,
    onOpen,
    onClose,
  } = options ?? {};

  return {
    // Reactive state — must stay boolean so x-show never hits window.open
    open: false as boolean,
    size,
    centered,
    closeOnBackdrop,
    closeOnEscape,
    openOnInit,
    onOpen,
    onClose,

    init(this: ModalComponent) {
      if (this.openOnInit && this.$el?.closest?.("#modal-host")) {
        this.show();
      }
    },

    show(
      this: ModalComponent,
      _title?: string,
      newSize?: ModalOptions["size"],
      newCentered?: boolean,
    ) {
      if (newSize) this.size = newSize;
      if (newCentered !== undefined) this.centered = newCentered;
      this.open = true;
      document.body.style.overflow = "hidden";
      this.onOpen?.();
      this.$nextTick(() => this.focusFirst());
    },

    hide(this: ModalComponent) {
      if (!this.open) return;
      this.open = false;
      document.body.style.overflow = "";
      this.onClose?.();

      // After leave transition, clear HTMX host so the next open is clean
      const el = this.$el;
      window.setTimeout(() => {
        const host = document.getElementById("modal-host");
        if (host && el && host.contains(el)) {
          host.innerHTML = "";
        }
      }, 200);
    },

    toggle(this: ModalComponent) {
      if (this.open) this.hide();
      else this.show();
    },

    handleKeydown(this: ModalComponent, e: KeyboardEvent) {
      if (!this.closeOnEscape) return;
      if (e.key === "Escape" && this.open) {
        e.preventDefault();
        this.hide();
      }
    },

    handleBackdropClick(this: ModalComponent, e: MouseEvent) {
      if (!this.closeOnBackdrop) return;
      if (e.target === e.currentTarget && this.open) {
        this.hide();
      }
    },

    focusFirst(this: ModalComponent) {
      const panel = this.$refs?.panel;
      if (!panel) return;
      const focusable = panel.querySelector<HTMLElement>(
        'input:not([type="hidden"]), select, textarea, button, [tabindex]:not([tabindex="-1"])',
      );
      focusable?.focus();
    },
  };
}

export default Modal;

/**
 * Register the modal component with Alpine.
 * Call this before Alpine.start().
 *
 * Uses a named wrapper so accidental `Alpine.data('modal')` without args
 * is less likely during debugging, and re-registration is idempotent.
 */
export function registerModalComponent(Alpine: typeof import("alpinejs").default): void {
  const factory = (options: ModalOptions = {}) => Modal(options);
  Alpine.data("modal", factory as (...args: unknown[]) => Record<string, unknown>);
  // Expose for re-register / debugging without wiping the factory
  if (typeof window !== "undefined") {
    (window as Window & { __alpineModalFactory?: typeof factory }).__alpineModalFactory = factory;
  }
  console.log("[Modal] Alpine component registered");
}
