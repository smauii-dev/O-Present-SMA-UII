# Fix Toaster & Modal Bugs

## Context
Audit menemukan 3 bug nyata dan beberapa UX gap di sistem toaster + modal HTMX. Rencana ini memperbaiki semua bug dan meningkatkan kualitas toaster tanpa mengubah perilaku existing yang sudah benar.

---

## Bug #1: Duplicate Toast pada 403 Error
**File:** `resources/ts/main.ts:88-96`

**Masalah:** `htmx:responseError` handler fire toast generic "Anda tidak memiliki akses" untuk SEMUA 403 error. Tapi 403 error dari controller sudah punya `HX-Trigger: {toast: {...}}` → HTMX dispatch event → toast spesifik muncul. Akhirnya user lihat **2 toast**.

**Fix:** Hapus branch 403 dari handler. Biarkan controller handle sendiri via `HX-Trigger`.

```ts
// SEBELUM (line 88-96)
document.addEventListener("htmx:responseError", (e: Event) => {
  const detail = (e as CustomEvent).detail;
  const status = detail.xhr.status;
  if (status === 401) {
    window.location.href = "/login";
  } else if (status === 403) {
    showToast("Anda tidak memiliki akses", "error");
  }
});

// SESUDAH
document.addEventListener("htmx:responseError", (e: Event) => {
  const detail = (e as CustomEvent).detail;
  if (detail.xhr.status === 401) {
    window.location.href = "/login";
  }
});
```

---

## Bug #2: Modal Close Tidak Berfungsi untuk Pegawai/Jabatan/Ketidakhadiran
**File:** `app/Views/partials/form_modal.twig:35-45` + 3 controllers

**Masalah:** Handler `@htmx:after-request` di `form_modal.twig` baca `event.detail.xhr.response` — tapi itu **string**, bukan object. Akses `.closePegawaiTable` → `undefined`. Dead code. Modal tidak close setelah save/delete.

**Lokasi** already works karena dispatch `'close-modal' => true` (nama event = `close-modal`, cocok dengan listener `@close-modal.window="hide()"` di `modal.twig:19`).

**Fix (2 langkah):**

### Step A: Ganti nama event di 3 controllers

**Pegawai.php** (2 tempat — save update + save create):
```php
// Ganti 'closePegawaiModal' => true menjadi 'close-modal' => true
// Line ~131-135 dan ~181-185
```

**Jabatan.php** (1 tempat — save):
```php
// Ganti 'closeJabatanModal' => true menjadi 'close-modal' => true
// Line ~65-69
```

**Ketidakhadiran.php** (1 tempat — save):
```php
// Ganti 'closeKetidakhadiranModal' => true menjadi 'close-modal' => true
// Line ~152-156
```

### Step B: Hapus handler yang broken dari `form_modal.twig`

Hapus baris 35-45 (`@htmx:after-request="..."`). Handler ini dead code dan tidak diperlukan karena:
- `HX-Trigger: {reloadXxxTable: true}` di-dispatch HTMX → bubbles ke body → ditangkap oleh `hx-trigger="revealed, reloadXxxTable from:body"`
- `HX-Trigger: {'close-modal': true}` → bubbles ke window → ditangkap oleh `@close-modal.window="hide()"`

---

## Bug #3: Auth Page Tidak Render flashWarning
**File:** `app/Views/layouts/auth.twig`

**Masalah:** Auth page render inline alert untuk `flashError`, `flashSuccess`, `flashInfo` — tapi tidak untuk `flashWarning`. Toast container dihide di auth page (`display: none`). Jika ada warning flash → pesan hilang.

**Fix:** Tambah `{% if flashWarning %}` block setelah flashInfo:

```twig
{% if flashWarning %}
<div class="auth-alert auth-alert--warning" role="alert">
  <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/>
  </svg>
  <span>{{ flashWarning }}</span>
</div>
{% endif %}
```

---

## UX Enhancement: Perbaiki Toaster di base.twig
**File:** `app/Views/layouts/base.twig:12-73`

### Enhancement A: Pause-on-hover
Ganti `setTimeout` fixed 4000ms dengan timer yang bisa di-pause:

```js
Alpine.data('toaster', () => ({
  toasts: [],
  addToast(toast) {
    const id = crypto.randomUUID ? crypto.randomUUID() : Date.now().toString(36) + Math.random().toString(36).slice(2);
    const startTime = Date.now();
    const remaining = 4000;
    this.toasts.push({ id, ...toast, remaining, startTime, paused: false, timer: null });
    this.startTimer(this.toasts[this.toasts.length - 1]);
  },
  startTimer(t) {
    if (t.timer) clearTimeout(t.timer);
    t.timer = setTimeout(() => {
      this.toasts = this.toasts.filter(x => x.id !== id);
    }, t.remaining);
  },
  pauseToast(id) {
    const t = this.toasts.find(x => x.id === id);
    if (t && !t.paused) {
      t.remaining -= (Date.now() - t.startTime);
      t.paused = true;
      if (t.timer) clearTimeout(t.timer);
    }
  },
  resumeToast(id) {
    const t = this.toasts.find(x => x.id === id);
    if (t && t.paused) {
      t.paused = false;
      t.startTime = Date.now();
      this.startTimer(t);
    }
  }
}));
```

### Enhancement B: Max toasts limit (5)
Di `addToast()`, sebelum push:
```js
if (this.toasts.length >= 5) {
  this.toasts.shift();
}
```

### Enhancement C: Exit transition slide-up
Ganti leave transition:
```html
x-transition:leave="transition ease-in duration-200"
x-transition:leave-start="opacity-100 translate-y-0"
x-transition:leave-end="opacity-0 -translate-y-2"
```

### Enhancement D: Tambah event handler di template
Tambah `@mouseenter` dan `@mouseleave` pada toast element:
```html
@mouseenter="pauseToast(toast.id)"
@mouseleave="resumeToast(toast.id)"
```

---

## File yang Diubah

| # | File | Perubahan |
|---|------|-----------|
| 1 | `resources/ts/main.ts` | Hapus 403 branch dari `htmx:responseError` (line 93-95) |
| 2 | `app/Views/partials/form_modal.twig` | Hapus handler `@htmx:after-request` (line 35-45) |
| 3 | `app/Controllers/Web/Pegawai.php` | Rename `closePegawaiModal` → `close-modal` (2 tempat) |
| 4 | `app/Controllers/Web/Jabatan.php` | Rename `closeJabatanModal` → `close-modal` (1 tempat) |
| 5 | `app/Controllers/Web/Ketidakhadiran.php` | Rename `closeKetidakhadiranModal` → `close-modal` (1 tempat) |
| 6 | `app/Views/layouts/auth.twig` | Tambah `flashWarning` inline alert |
| 7 | `app/Views/layouts/base.twig` | Rewrite toaster: pause-on-hover, max 5, slide-up exit, UUID |

---

## Urutan Eksekusi

1. `main.ts` — hapus 403 handler
2. 3 controllers — rename event keys
3. `form_modal.twig` — hapus broken handler
4. `auth.twig` — tambah flashWarning
5. `base.twig` — rewrite toaster component
6. `bun run build` — rebuild frontend
7. Deploy ke produksi

---

## Verification

1. **Build:** `bun run build` — no TypeScript errors
2. **Toast 403:** Trigger a 403 via Presensi clock-in outside location → only 1 toast (the specific one)
3. **Modal close:** Create/update/delete Pegawai/Jabatan/Ketidakhadiran → modal auto-close
4. **Auth flashWarning:** Manually set flash warning, visit auth page → see inline alert
5. **Pause-on-hover:** Trigger toast, hover → timer pauses, unhover → resumes
6. **Max limit:** Trigger 6+ toasts rapidly → only 5 visible
7. **Exit transition:** Wait for auto-dismiss → toast slides up + fades (not just fade)
