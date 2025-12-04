<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Movies Management</title>
    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
        integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH"
        crossorigin="anonymous"
    >
    <style>
        body {
            background-color: #f8f9fa;
        }
        .poster-thumb {
            width: 80px;
            height: 120px;
            object-fit: cover;
            border-radius: 4px;
            border: 1px solid #dee2e6;
        }
        .table td {
            vertical-align: middle;
        }
    </style>
</head>
<body>
<div class="container py-5">
    <div class="row">
        <div class="col-12">
            <h1 class="mb-4 text-center">Movies CRUD Dashboard</h1>
        </div>
    </div>

    <?php if (!empty($messages)): ?>
        <div class="row">
            <div class="col-12">
                <div class="alert alert-<?php echo $messages['type'] === 'success' ? 'success' : 'danger'; ?> alert-dismissible fade show" role="alert">
                    <?php echo $messages['content']; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <div class="row">
        <div class="col-12 mb-4">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    Add New Movie
                </div>
                <div class="card-body">
                    <form action="<?php echo site_url('movies/create'); ?>" method="post" enctype="multipart/form-data">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="create-title" class="form-label">Title</label>
                                <input type="text" class="form-control" id="create-title" name="title" required>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label for="create-duration" class="form-label">Duration (minutes)</label>
                                <input type="number" class="form-control" id="create-duration" name="duration" min="1" required>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label for="create-genre" class="form-label">Genre</label>
                                <input type="text" class="form-control" id="create-genre" name="genre" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="create-release-date" class="form-label">Release Date</label>
                                <input type="date" class="form-control" id="create-release-date" name="release_date" required>
                            </div>
                            <div class="col-md-8 mb-3">
                                <label for="create-description" class="form-label">Description</label>
                                <textarea class="form-control" id="create-description" name="description" rows="4"></textarea>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="create-poster" class="form-label">Poster Image</label>
                                <input type="file" class="form-control" id="create-poster" name="poster" accept=".jpg,.jpeg,.png,.gif">
                                <div class="form-text">Supported formats: JPG, PNG, GIF. Max size 2 MB.</div>
                            </div>
                        </div>
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary">Create Movie</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header bg-dark text-white">
                    Movies List
                </div>
                <div class="card-body table-responsive">
                    <table class="table table-striped align-middle">
                        <thead>
                        <tr>
                            <th>#</th>
                            <th>Poster</th>
                            <th>Title</th>
                            <th>Genre</th>
                            <th>Duration</th>
                            <th>Release Date</th>
                            <th>Description</th>
                            <th>Actions</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php if (!empty($movies)): ?>
                            <?php $counter = 1; ?>
                            <?php foreach ($movies as $movie): ?>
                                <tr>
                                    <td><?php echo $counter++; ?></td>
                                    <td>
                                        <?php if (!empty($movie['poster_url'])): ?>
                                            <?php
                                                $posterSrc = $movie['poster_url'];
                                                if (!preg_match('/^https?:\/\//i', $posterSrc)) {
                                                    $posterSrc = base_url($posterSrc);
                                                }
                                            ?>
                                            <img src="<?php echo htmlspecialchars($posterSrc, ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars($movie['title'], ENT_QUOTES, 'UTF-8'); ?>" class="poster-thumb">
                                        <?php else: ?>
                                            <span class="text-muted">No poster</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($movie['title'], ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?php echo htmlspecialchars($movie['genre'], ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?php echo (int) $movie['duration']; ?> min</td>
                                    <td><?php echo htmlspecialchars($movie['release_date'], ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td class="w-25"><?php echo nl2br(htmlspecialchars($movie['description'], ENT_QUOTES, 'UTF-8')); ?></td>
                                    <td>
                                        <div class="d-flex flex-column gap-2">
                                            <button
                                                class="btn btn-sm btn-outline-primary"
                                                type="button"
                                                data-bs-toggle="collapse"
                                                data-bs-target="#edit-form-<?php echo (int) $movie['id']; ?>"
                                                aria-expanded="false"
                                                aria-controls="edit-form-<?php echo (int) $movie['id']; ?>"
                                            >
                                                Edit
                                            </button>
                                                <form action="<?php echo site_url('movies/delete/' . (int) $movie['id']); ?>" method="post" onsubmit="return confirm('Delete this movie?');">
                                                <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                                <tr class="collapse" id="edit-form-<?php echo (int) $movie['id']; ?>">
                                    <td colspan="8">
                                        <div class="card">
                                            <div class="card-header bg-warning text-dark">
                                                <h5 class="mb-0">Edit Movie: <?php echo htmlspecialchars($movie['title'], ENT_QUOTES, 'UTF-8'); ?></h5>
                                            </div>
                                            <div class="card-body">
                                                <form action="<?php echo site_url('movies/edit/' . (int) $movie['id']); ?>" method="post" enctype="multipart/form-data">
                                                    <div class="row g-3">
                                                        <div class="col-md-6">
                                                            <label class="form-label fw-bold" for="title-<?php echo (int) $movie['id']; ?>">Title</label>
                                                            <input type="text" class="form-control" id="title-<?php echo (int) $movie['id']; ?>" name="title" value="<?php echo htmlspecialchars($movie['title'], ENT_QUOTES, 'UTF-8'); ?>" required>
                                                        </div>
                                                        <div class="col-md-3">
                                                            <label class="form-label fw-bold" for="duration-<?php echo (int) $movie['id']; ?>">Duration (min)</label>
                                                            <input type="number" class="form-control" id="duration-<?php echo (int) $movie['id']; ?>" name="duration" min="1" value="<?php echo (int) $movie['duration']; ?>" required>
                                                        </div>
                                                        <div class="col-md-3">
                                                            <label class="form-label fw-bold" for="genre-<?php echo (int) $movie['id']; ?>">Genre</label>
                                                            <input type="text" class="form-control" id="genre-<?php echo (int) $movie['id']; ?>" name="genre" value="<?php echo htmlspecialchars($movie['genre'], ENT_QUOTES, 'UTF-8'); ?>" required>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <label class="form-label fw-bold" for="release-<?php echo (int) $movie['id']; ?>">Release Date</label>
                                                            <input type="date" class="form-control" id="release-<?php echo (int) $movie['id']; ?>" name="release_date" value="<?php echo htmlspecialchars($movie['release_date'], ENT_QUOTES, 'UTF-8'); ?>" required>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <label class="form-label fw-bold" for="description-<?php echo (int) $movie['id']; ?>">Description</label>
                                                            <textarea class="form-control" id="description-<?php echo (int) $movie['id']; ?>" name="description" rows="3"><?php echo htmlspecialchars($movie['description'], ENT_QUOTES, 'UTF-8'); ?></textarea>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <label class="form-label fw-bold" for="poster-<?php echo (int) $movie['id']; ?>">Update Poster</label>
                                                            <input type="file" class="form-control" id="poster-<?php echo (int) $movie['id']; ?>" name="poster" accept=".jpg,.jpeg,.png,.gif">
                                                            <div class="form-text">Leave blank to keep current poster.</div>
                                                        </div>
                                                        <div class="col-md-6 d-flex flex-column align-items-start">
                                                            <label class="form-label fw-bold">Current Poster</label>
                                                            <?php if (!empty($movie['poster_url'])): ?>
                                                                <?php
                                                                    $posterSrc = $movie['poster_url'];
                                                                    if (!preg_match('/^https?:\/\//i', $posterSrc)) {
                                                                        $posterSrc = base_url($posterSrc);
                                                                    }
                                                                ?>
                                                                <img src="<?php echo htmlspecialchars($posterSrc, ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars($movie['title'], ENT_QUOTES, 'UTF-8'); ?>" class="poster-thumb mb-2">
                                                            <?php else: ?>
                                                                <span class="text-muted">No poster</span>
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>
                                                    <div class="mt-4 text-end">
                                                        <button type="submit" class="btn btn-success btn-lg">Update Movie</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" class="text-center text-muted">No movies found. Add your first movie using the form above.</td>
                            </tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
    integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz"
    crossorigin="anonymous"
></script>
</body>
</html>
