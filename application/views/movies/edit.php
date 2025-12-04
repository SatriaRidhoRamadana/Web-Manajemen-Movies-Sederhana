<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title><?= html_escape($title); ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
</head>
<body class="bg-light">
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h1 class="h5 mb-0">Edit Film</h1>
                    <a href="<?= site_url('movies'); ?>" class="btn btn-sm btn-outline-light">Kembali</a>
                </div>
                <div class="card-body">
                    <?php if ($this->session->flashdata('error')): ?>
                        <div class="alert alert-danger">
                            <?= $this->session->flashdata('error'); ?>
                        </div>
                    <?php endif; ?>
                    <?= form_open_multipart('movies/update/' . $movie['id']); ?>
                    <div class="mb-3">
                        <label for="title" class="form-label">Judul</label>
                        <input type="text" name="title" id="title" class="form-control" value="<?= html_escape($movie['title']); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="description" class="form-label">Deskripsi</label>
                        <textarea name="description" id="description" rows="4" class="form-control"><?= html_escape($movie['description']); ?></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="duration" class="form-label">Durasi (menit)</label>
                        <input type="number" name="duration" id="duration" class="form-control" min="1" value="<?= (int)$movie['duration']; ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="genre" class="form-label">Genre</label>
                        <input type="text" name="genre" id="genre" class="form-control" value="<?= html_escape($movie['genre']); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="release_date" class="form-label">Tanggal Rilis</label>
                        <input type="date" name="release_date" id="release_date" class="form-control" value="<?= html_escape($movie['release_date']); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Poster Saat Ini</label>
                        <?php if (!empty($movie['poster_url'])): ?>
                            <div class="mb-2">
                                <img src="<?= base_url($movie['poster_url']); ?>" alt="<?= html_escape($movie['title']); ?>" class="img-thumbnail" style="max-width: 150px;">
                            </div>
                        <?php else: ?>
                            <p class="text-muted">Tidak ada poster.</p>
                        <?php endif; ?>
                        <label for="poster" class="form-label">Ubah Poster</label>
                        <input type="file" name="poster" id="poster" class="form-control" accept="image/*">
                        <div class="form-text">Biarkan kosong jika tidak ingin mengganti poster.</div>
                    </div>
                    <div class="d-flex justify-content-end gap-2">
                        <a href="<?= site_url('movies'); ?>" class="btn btn-secondary">Batal</a>
                        <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                    </div>
                    <?= form_close(); ?>
                </div>
            </div>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

