import { z } from "zod";

export const loginSchema = z.object({
  login: z.string().min(1, "Email atau username wajib diisi"),
  password: z.string().min(1, "Password wajib diisi"),
  remember: z.boolean().optional().default(false),
});

export const forgotPasswordSchema = z.object({
  email: z.string().email("Email tidak valid").min(1, "Email wajib diisi"),
});

export const resetPasswordSchema = z
  .object({
    token: z.string().min(1),
    email: z.string().email(),
    password: z
      .string()
      .min(8, "Password minimal 8 karakter")
      .max(128, "Password maksimal 128 karakter"),
    password_confirm: z.string().min(1, "Konfirmasi password wajib diisi"),
  })
  .refine((data) => data.password === data.password_confirm, {
    message: "Konfirmasi password tidak cocok",
    path: ["password_confirm"],
  });

export const profileUpdateSchema = z.object({
  nama: z.string().min(1, "Nama wajib diisi").max(100),
  no_handphone: z
    .string()
    .regex(/^[\d+\-\s]*$/, "Nomor handphone tidak valid")
    .optional()
    .or(z.literal("")),
  jenis_kelamin: z.enum(["", "Laki-laki", "Perempuan"]).optional(),
  alamat: z.string().max(255).optional().or(z.literal("")),
});

export const changePasswordSchema = z
  .object({
    password_lama: z.string().min(1, "Password lama wajib diisi"),
    password_baru: z
      .string()
      .min(8, "Password baru minimal 8 karakter")
      .max(128, "Password baru maksimal 128 karakter"),
    password_confirm: z.string().min(1, "Konfirmasi password wajib diisi"),
  })
  .refine((data) => data.password_baru === data.password_confirm, {
    message: "Konfirmasi password tidak cocok",
    path: ["password_confirm"],
  })
  .refine((data) => data.password_lama !== data.password_baru, {
    message: "Password baru harus berbeda dari password lama",
    path: ["password_baru"],
  });

export const pegawaiSchema = z.object({
  nama: z.string().min(1, "Nama wajib diisi").max(100),
  email: z.string().email("Email tidak valid").min(1, "Email wajib diisi"),
  jenis_kelamin: z.enum(["", "Laki-laki", "Perempuan"]).optional(),
  no_handphone: z
    .string()
    .regex(/^[\d+\-\s]*$/, "Nomor handphone tidak valid")
    .optional()
    .or(z.literal("")),
  alamat: z.string().max(255).optional().or(z.literal("")),
  id_jabatan: z.string().min(1, "Jabatan wajib dipilih"),
  id_lokasi_presensi: z.string().min(1, "Lokasi wajib dipilih"),
  role: z.string().optional(),
});

export const jabatanSchema = z.object({
  jabatan: z.string().min(1, "Nama jabatan wajib diisi").max(100),
});

export const lokasiSchema = z.object({
  nama_lokasi: z.string().min(1, "Nama lokasi wajib diisi").max(100),
  alamat_lokasi: z.string().max(255).optional().or(z.literal("")),
  tipe_lokasi: z.enum(["Pusat", "Cabang"]).optional().default("Pusat"),
  maps_url: z.string().optional().or(z.literal("")),
  radius: z.coerce
    .number({ error: "Radius harus berupa angka" })
    .min(10, "Radius minimal 10 meter")
    .max(5000, "Radius maksimal 5000 meter")
    .optional()
    .default(100),
  latitude: z.coerce
    .number({ error: "Latitude harus berupa angka" })
    .min(-90, "Latitude minimal -90")
    .max(90, "Latitude maksimal 90"),
  longitude: z.coerce
    .number({ error: "Longitude harus berupa angka" })
    .min(-180, "Longitude minimal -180")
    .max(180, "Longitude maksimal 180"),
  jam_masuk: z.string().min(1, "Jam masuk wajib diisi"),
  jam_pulang: z.string().min(1, "Jam pulang wajib diisi"),
  zona_waktu: z
    .enum(["Asia/Jakarta", "Asia/Makassar", "Asia/Jayapura"])
    .optional()
    .default("Asia/Jakarta"),
});

export const ketidakhadiranSchema = z
  .object({
    tipe_ketidakhadiran: z.enum(["", "SAKIT", "IZIN", "CUTI"], {
      message: "Tipe ketidakhadiran wajib dipilih",
    }),
    tanggal_mulai: z.string().min(1, "Tanggal mulai wajib diisi"),
    tanggal_berakhir: z.string().min(1, "Tanggal berakhir wajib diisi"),
    deskripsi: z.string().max(500).optional().or(z.literal("")),
  })
  .refine(
    (data) => {
      if (data.tanggal_mulai && data.tanggal_berakhir) {
        return data.tanggal_berakhir >= data.tanggal_mulai;
      }
      return true;
    },
    {
      message: "Tanggal berakhir harus setelah atau sama dengan tanggal mulai",
      path: ["tanggal_berakhir"],
    },
  );

export const clockInSchema = z.object({
  latitude: z.coerce.number("Lokasi tidak tersedia").min(-90).max(90),
  longitude: z.coerce.number("Lokasi tidak tersedia").min(-180).max(180),
  foto: z.string().min(1, "Foto wajib diambil"),
});

export const rekapFilterSchema = z.object({
  dari: z.string().optional(),
  sampai: z.string().optional(),
});

export type LoginInput = z.infer<typeof loginSchema>;
export type ForgotPasswordInput = z.infer<typeof forgotPasswordSchema>;
export type ResetPasswordInput = z.infer<typeof resetPasswordSchema>;
export type ProfileUpdateInput = z.infer<typeof profileUpdateSchema>;
export type ChangePasswordInput = z.infer<typeof changePasswordSchema>;
export type PegawaiInput = z.infer<typeof pegawaiSchema>;
export type JabatanInput = z.infer<typeof jabatanSchema>;
export type LokasiInput = z.infer<typeof lokasiSchema>;
export type KetidakhadiranInput = z.infer<typeof ketidakhadiranSchema>;
export type ClockInInput = z.infer<typeof clockInSchema>;
export type RekapFilterInput = z.infer<typeof rekapFilterSchema>;
