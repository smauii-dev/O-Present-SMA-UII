<?= $this->extend('templates/index') ?>

<?= $this->section('pageBody') ?>
<!-- Page body -->
<div class="page-body">
    <div class="container-xl">
        <div class="card mb-3">
            <div class="card-body">
                <div class="row justify-content-between g-3 flex-column-reverse flex-lg-row align-items-lg-end">
                    <div class="col-md-6">
                        <form method="get">
                            <div class="row g-2 align-items-end">
                                <div class="col">
                                    <div class="row g-1">
                                        <div class="col">
                                            <label class="form-label" for="tanggal_dari">Tanggal Mulai</label>
                                            <input type="date" name="tanggal_dari" class="form-control" id="tanggal_dari" value="<?= $tanggal_dari ?>">
                                        </div>
                                        <div class="col">
                                            <label for="tanggal_sampai" class="form-label">Tanggal Akhir</label>
                                            <input type="date" name="tanggal_sampai" class="form-control" id="tanggal_sampai" value="<?= $tanggal_sampai ?>">
                                        </div>
                                    </div>
                                </div>
                                <div class="col-auto">
                                    <button type="submit" class="btn btn-outline-cyan">Filter</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <div class="row row-deck row-cards align-items-start">
            <div class="col-lg-12">
                <div class="card">
                    <div class="card-body">
                        <div class="card-title">Rekap Presensi Tanggal: <strong><?= $data_tanggal; ?></strong></div>
                        <div class="table-responsive">
                            <table class="table table-bordered">
                                <tr class="text-center">
                                    <th>No</th>
                                    <th>Tanggal</th>
                                    <th>Jam Masuk</th>
                                    <th>Foto Masuk</th>
                                    <th>Jam Pulang</th>
                                    <th>Foto Pulang</th>
                                    <th>Total Keterlambatan</th>
                                </tr>
                                <?php if (!empty($data_presensi_pegawai)) : ?>
                                    <?php $nomor = 1 + ($perPage * ($currentPage - 1)); ?>
                                    <?php foreach ($data_presensi_pegawai as $data_presensi) : ?>
                                        <?php
                                        // TOTAL JAM KERJA
                                        $jam_tanggal_masuk = date('Y-m-d H:i:s', strtotime($data_presensi->tanggal_masuk . ' ' . $data_presensi->jam_masuk));
                                        $jam_tanggal_keluar = date('Y-m-d H:i:s', strtotime($data_presensi->tanggal_keluar . ' ' . $data_presensi->jam_keluar));

                                        $timestamp_masuk = strtotime($jam_tanggal_masuk);
                                        $timestamp_keluar = strtotime($jam_tanggal_keluar);

                                        // Selisih dalam format time
                                        $selisih = $timestamp_keluar - $timestamp_masuk;

                                        // Selisih dalam format jam
                                        $total_jam_kerja = floor($selisih / 3600);

                                        // Selisih dalam format menit
                                        $selisih_menit_kerja = floor(($selisih % 3600) / 60);

                                        // Format string
                                        $total_jam_kerja_format = sprintf("%d Jam %d Menit", $total_jam_kerja, $selisih_menit_kerja);

                                        // TOTAL KETERLAMBATAN
                                        $jam_masuk = date('H:i:s', strtotime($data_presensi->jam_masuk));
                                        $timestamp_jam_masuk_real = strtotime($jam_masuk);
                                        $timestamp_jam_masuk_kantor = strtotime($jam_masuk_kantor);

                                        $terlambat = $timestamp_jam_masuk_real - $timestamp_jam_masuk_kantor;
                                        $total_jam_keterlambatan = floor($terlambat / 3600);
                                        $selisih_menit_keterlambatan = floor(($terlambat % 3600) / 60);

                                        $total_jam_keterlambatan_format = sprintf("%d Jam %d Menit", $total_jam_keterlambatan, $selisih_menit_keterlambatan);
                                        ?>

                                        <tr>
                                            <td class="text-center"><?= $nomor++ ?></td>
                                            <td class="text-center"><?= date('d F Y', strtotime($data_presensi->tanggal_masuk)) ?></td>
                                            <td class="text-center"><?= $data_presensi->jam_masuk ?></td>
                                            <td class="text-center"><a href="<?= base_url('assets/img/foto_presensi/masuk/' . $data_presensi->foto_masuk) ?>" target="_blank">Lihat Foto</a></td>
                                            <td class="text-center"><?= $data_presensi->jam_keluar ?></td>
                                            <?php if ($data_presensi->jam_keluar === '00:00:00' || $data_presensi->foto_keluar === '-') : ?>
                                                <td class="text-center">-</td>
                                            <?php else : ?>
                                                <td class="text-center"><a href="<?= base_url('assets/img/foto_presensi/keluar/' . $data_presensi->foto_keluar) ?>" target="_blank">Lihat Foto</a></td>
                                            <?php endif; ?>
                                            <?php if ($total_jam_keterlambatan_format < 0) :  ?>
                                                <td class="text-center"><span class="badge bg-success">On Time</span></td>
                                            <?php else : ?>
                                                <td class="text-center"><?= $total_jam_keterlambatan_format ?></td>
                                            <?php endif; ?>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else : ?>
                                    <tr class="text-center">
                                        <td colspan="8">Belum ada data presensi.</td>
                                    </tr>
                                <?php endif; ?>
                            </table>
                        </div>
                    </div>
                    <div class="card-footer d-flex align-items-center justify-content-between">
                        <p class="m-0 text-muted">Showing <span><?= ($perPage * ($currentPage - 1)) + 1 ?></span> to <span><?= min($perPage * $currentPage, $total) ?></span> of <span><?= $total ?></span> entries</p>
                        <?= $pager; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Export Excel Modals -->
<div class="modal" id="exportModal" tabindex="-1">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Export Rekap Presensi Pegawai</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="<?= base_url('/rekap-presensi/excel') ?>" method="POST">
                <?= csrf_field() ?>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="tanggal_awal">Tanggal Awal</label>
                        <input class="form-control mt-1" type="date" id="tanggal_awal" name="tanggal_awal" value="<?= $tanggal_dari ?>">
                    </div>
                    <div class="mb-3">
                        <label for="tanggal_akhir">Tanggal Akhir</label>
                        <input class="form-control mt-1" type="date" id="tanggal_akhir" name="tanggal_akhir" value="<?= $tanggal_sampai ?>">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn me-auto" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-success" data-bs-dismiss="modal">Export</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?= $this->endSection() ?>