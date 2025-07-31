<?php 
include '../../includes/header.php';
require_once '../../config/roles.php';

// Check permission
if (!hasPermission($current_user['role'], 'documents', 'read')) {
    header("Location: ../../dashboard/" . $current_user['role'] . ".php");
    exit();
}

// Get project filter
$project_id = intval($_GET['project_id'] ?? 0);

// Fetch projects for filtering
$projects_query = "SELECT id, name FROM projects ORDER BY name";
$projects_stmt = $db->prepare($projects_query);
$projects_stmt->execute();
$projects = $projects_stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch image documents
$images_query = "SELECT d.*, p.name as project_name, u.name as uploaded_by_name 
                 FROM project_documents d 
                 LEFT JOIN projects p ON d.project_id = p.id 
                 LEFT JOIN users u ON d.uploaded_by = u.id 
                 WHERE d.file_type LIKE 'image/%'";

$params = [];
if ($project_id) {
    $images_query .= " AND d.project_id = ?";
    $params[] = $project_id;
}

$images_query .= " ORDER BY d.created_at DESC";
$images_stmt = $db->prepare($images_query);
$images_stmt->execute($params);
$images = $images_stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-md-3">
            <?php include '../../includes/sidebar.php'; ?>
        </div>
        <div class="col-md-9">
            <div class="main-content p-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2><i class="fas fa-images"></i> Project Gallery</h2>
                    <div class="btn-group">
                        <a href="list.php" class="btn btn-outline-secondary">
                            <i class="fas fa-list"></i> Document List
                        </a>
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#uploadModal">
                            <i class="fas fa-cloud-upload-alt"></i> Upload Images
                        </button>
                    </div>
                </div>

                <!-- Gallery Filters -->
                <div class="card mb-4">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-md-4">
                                <select class="form-control" onchange="filterGallery(this.value)">
                                    <option value="">All Projects</option>
                                    <?php foreach ($projects as $project): ?>
                                    <option value="<?php echo $project['id']; ?>"
                                        <?php echo $project_id == $project['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($project['name']); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <select class="form-control" id="categoryFilter" onchange="filterByCategory()">
                                    <option value="">All Categories</option>
                                    <option value="design">Design Files</option>
                                    <option value="photo">Project Photos</option>
                                    <option value="before">Before Photos</option>
                                    <option value="after">After Photos</option>
                                    <option value="progress">Progress Photos</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <div class="btn-group w-100">
                                    <button class="btn btn-outline-primary active" onclick="setGalleryView('grid')">
                                        <i class="fas fa-th"></i> Grid
                                    </button>
                                    <button class="btn btn-outline-primary" onclick="setGalleryView('masonry')">
                                        <i class="fas fa-th-large"></i> Masonry
                                    </button>
                                    <button class="btn btn-outline-primary" onclick="setGalleryView('carousel')">
                                        <i class="fas fa-images"></i> Slideshow
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Gallery Statistics -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="card bg-primary text-white">
                            <div class="card-body text-center">
                                <h4><?php echo count($images); ?></h4>
                                <p class="mb-0">Total Images</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-success text-white">
                            <div class="card-body text-center">
                                <h4><?php echo count(array_filter($images, fn($i) => $i['category'] == 'design')); ?>
                                </h4>
                                <p class="mb-0">Design Files</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-warning text-white">
                            <div class="card-body text-center">
                                <h4><?php echo count(array_filter($images, fn($i) => $i['category'] == 'photo')); ?>
                                </h4>
                                <p class="mb-0">Project Photos</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-info text-white">
                            <div class="card-body text-center">
                                <h4><?php 
                                $totalSize = array_sum(array_column($images, 'file_size'));
                                echo round($totalSize / (1024 * 1024), 1) . ' MB';
                                ?></h4>
                                <p class="mb-0">Storage Used</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Grid View -->
                <div id="gridView" class="gallery-grid">
                    <?php if (empty($images)): ?>
                    <div class="text-center py-5">
                        <i class="fas fa-images fa-3x text-muted mb-3"></i>
                        <h5 class="text-muted">No images found</h5>
                        <p class="text-muted">Upload some images to get started!</p>
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#uploadModal">
                            <i class="fas fa-plus"></i> Upload Images
                        </button>
                    </div>
                    <?php else: ?>
                    <div class="row" id="imageGrid">
                        <?php foreach ($images as $index => $image): ?>
                        <div class="col-lg-3 col-md-4 col-sm-6 mb-4 gallery-item"
                            data-category="<?php echo $image['category']; ?>"
                            data-project="<?php echo $image['project_id']; ?>">
                            <div class="gallery-card">
                                <div class="image-container">
                                    <img src="../../<?php echo htmlspecialchars($image['file_path']); ?>"
                                        alt="<?php echo htmlspecialchars($image['original_name']); ?>"
                                        class="gallery-image" onclick="openLightbox(<?php echo $index; ?>)">

                                    <div class="image-overlay">
                                        <div class="overlay-content">
                                            <button class="btn btn-light btn-sm"
                                                onclick="viewImage(<?php echo $image['id']; ?>)">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <button class="btn btn-light btn-sm"
                                                onclick="downloadImage(<?php echo $image['id']; ?>)">
                                                <i class="fas fa-download"></i>
                                            </button>
                                            <button class="btn btn-light btn-sm"
                                                onclick="shareImage(<?php echo $image['id']; ?>)">
                                                <i class="fas fa-share"></i>
                                            </button>
                                            <?php if (hasPermission($current_user['role'], 'documents', 'delete')): ?>
                                            <button class="btn btn-danger btn-sm"
                                                onclick="deleteImage(<?php echo $image['id']; ?>)">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>

                                <div class="card-body p-2">
                                    <h6 class="card-title mb-1"
                                        title="<?php echo htmlspecialchars($image['original_name']); ?>">
                                        <?php echo htmlspecialchars(strlen($image['original_name']) > 20 ? substr($image['original_name'], 0, 20) . '...' : $image['original_name']); ?>
                                    </h6>
                                    <small class="text-muted d-block">
                                        <i class="fas fa-project-diagram"></i>
                                        <?php echo htmlspecialchars($image['project_name']); ?>
                                    </small>
                                    <small class="text-muted d-block">
                                        <i class="fas fa-user"></i>
                                        <?php echo htmlspecialchars($image['uploaded_by_name']); ?>
                                    </small>
                                    <small class="text-muted">
                                        <i class="fas fa-calendar"></i>
                                        <?php echo date('M d, Y', strtotime($image['created_at'])); ?>
                                    </small>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Masonry View -->
                <div id="masonryView" class="gallery-masonry" style="display: none;">
                    <div class="masonry-grid">
                        <?php foreach ($images as $index => $image): ?>
                        <div class="masonry-item" data-category="<?php echo $image['category']; ?>"
                            data-project="<?php echo $image['project_id']; ?>">
                            <img src="../../<?php echo htmlspecialchars($image['file_path']); ?>"
                                alt="<?php echo htmlspecialchars($image['original_name']); ?>"
                                onclick="openLightbox(<?php echo $index; ?>)">
                            <div class="masonry-overlay">
                                <h6><?php echo htmlspecialchars($image['original_name']); ?></h6>
                                <p><?php echo htmlspecialchars($image['project_name']); ?></p>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Carousel View -->
                <div id="carouselView" class="gallery-carousel" style="display: none;">
                    <div id="galleryCarousel" class="carousel slide" data-bs-ride="carousel">
                        <div class="carousel-inner">
                            <?php foreach ($images as $index => $image): ?>
                            <div class="carousel-item <?php echo $index === 0 ? 'active' : ''; ?>">
                                <img src="../../<?php echo htmlspecialchars($image['file_path']); ?>"
                                    class="d-block w-100 carousel-image"
                                    alt="<?php echo htmlspecialchars($image['original_name']); ?>">
                                <div class="carousel-caption d-none d-md-block">
                                    <h5><?php echo htmlspecialchars($image['original_name']); ?></h5>
                                    <p><?php echo htmlspecialchars($image['project_name']); ?></p>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>

                        <button class="carousel-control-prev" type="button" data-bs-target="#galleryCarousel"
                            data-bs-slide="prev">
                            <span class="carousel-control-prev-icon"></span>
                        </button>
                        <button class="carousel-control-next" type="button" data-bs-target="#galleryCarousel"
                            data-bs-slide="next">
                            <span class="carousel-control-next-icon"></span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Lightbox Modal -->
<div class="modal fade" id="lightboxModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="lightboxTitle">Image Viewer</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center">
                <img id="lightboxImage" class="img-fluid" style="max-height: 70vh;">
                <div class="mt-3">
                    <button class="btn btn-outline-primary" onclick="previousImage()">
                        <i class="fas fa-chevron-left"></i> Previous
                    </button>
                    <button class="btn btn-outline-primary" onclick="nextImage()">
                        Next <i class="fas fa-chevron-right"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Upload Modal (reuse from list.php) -->
<div class="modal fade" id="uploadModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Upload Images</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="uploadForm" enctype="multipart/form-data">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Project <span class="text-danger">*</span></label>
                                <select class="form-control" name="project_id" required>
                                    <option value="">Select Project</option>
                                    <?php foreach ($projects as $project): ?>
                                    <option value="<?php echo $project['id']; ?>">
                                        <?php echo htmlspecialchars($project['name']); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Category</label>
                                <select class="form-control" name="category">
                                    <option value="photo">Project Photos</option>
                                    <option value="design">Design Files</option>
                                    <option value="before">Before Photos</option>
                                    <option value="after">After Photos</option>
                                    <option value="progress">Progress Photos</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Images <span class="text-danger">*</span></label>
                        <input type="file" class="form-control" name="files[]" multiple required accept="image/*">
                        <div class="form-text">Only image files are allowed in gallery view</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea class="form-control" name="description" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Upload Images</button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
.gallery-card {
    position: relative;
    background: white;
    border-radius: 12px;
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    overflow: hidden;
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.gallery-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
}

.image-container {
    position: relative;
    overflow: hidden;
    height: 200px;
}

.gallery-image {
    width: 100%;
    height: 100%;
    object-fit: cover;
    cursor: pointer;
    transition: transform 0.3s ease;
}

.gallery-image:hover {
    transform: scale(1.05);
}

.image-overlay {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.7);
    display: flex;
    align-items: center;
    justify-content: center;
    opacity: 0;
    transition: opacity 0.3s ease;
}

.gallery-card:hover .image-overlay {
    opacity: 1;
}

.overlay-content {
    display: flex;
    gap: 10px;
}

.masonry-grid {
    column-count: 4;
    column-gap: 15px;
}

.masonry-item {
    break-inside: avoid;
    margin-bottom: 15px;
    position: relative;
    cursor: pointer;
}

.masonry-item img {
    width: 100%;
    border-radius: 8px;
    transition: transform 0.3s ease;
}

.masonry-item:hover img {
    transform: scale(1.02);
}

.masonry-overlay {
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    background: linear-gradient(transparent, rgba(0, 0, 0, 0.8));
    color: white;
    padding: 15px;
    opacity: 0;
    transition: opacity 0.3s ease;
}

.masonry-item:hover .masonry-overlay {
    opacity: 1;
}

.carousel-image {
    height: 500px;
    object-fit: contain;
}

@media (max-width: 768px) {
    .masonry-grid {
        column-count: 2;
    }

    .gallery-card {
        margin-bottom: 15px;
    }
}

@media (max-width: 480px) {
    .masonry-grid {
        column-count: 1;
    }
}
</style>

<script>
let currentImageIndex = 0;
let imageArray = <?php echo json_encode($images); ?>;
let currentView = 'grid';

function filterGallery(projectId) {
    const url = new URL(window.location);
    if (projectId) {
        url.searchParams.set('project_id', projectId);
    } else {
        url.searchParams.delete('project_id');
    }
    window.location = url;
}

function filterByCategory() {
    const category = document.getElementById('categoryFilter').value;
    const items = document.querySelectorAll('.gallery-item, .masonry-item');

    items.forEach(item => {
        if (!category || item.dataset.category === category) {
            item.style.display = '';
        } else {
            item.style.display = 'none';
        }
    });
}

function setGalleryView(view) {
    // Hide all views
    document.getElementById('gridView').style.display = 'none';
    document.getElementById('masonryView').style.display = 'none';
    document.getElementById('carouselView').style.display = 'none';

    // Show selected view
    document.getElementById(view + 'View').style.display = 'block';

    // Update active button
    document.querySelectorAll('.btn-group .btn').forEach(btn => btn.classList.remove('active'));
    event.target.classList.add('active');

    currentView = view;
}

function openLightbox(index) {
    currentImageIndex = index;
    const image = imageArray[index];

    document.getElementById('lightboxTitle').textContent = image.original_name;
    document.getElementById('lightboxImage').src = '../../' + image.file_path;

    const modal = new bootstrap.Modal(document.getElementById('lightboxModal'));
    modal.show();
}

function previousImage() {
    currentImageIndex = currentImageIndex > 0 ? currentImageIndex - 1 : imageArray.length - 1;
    openLightbox(currentImageIndex);
}

function nextImage() {
    currentImageIndex = currentImageIndex < imageArray.length - 1 ? currentImageIndex + 1 : 0;
    openLightbox(currentImageIndex);
}

function viewImage(id) {
    window.open(`view.php?id=${id}`, '_blank');
}

function downloadImage(id) {
    window.open(`download.php?id=${id}`, '_blank');
}

function shareImage(id) {
    const url = window.location.origin + `/modules/documents/view.php?id=${id}`;
    if (navigator.share) {
        navigator.share({
            url: url
        });
    } else {
        navigator.clipboard.writeText(url);
        alert('Image link copied to clipboard!');
    }
}

function deleteImage(id) {
    if (confirm('Are you sure you want to delete this image?')) {
        fetch(`delete.php?id=${id}`, {
                method: 'POST'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert('Delete failed: ' + data.message);
                }
            });
    }
}

// Upload form submission
document.getElementById('uploadForm').addEventListener('submit', function(e) {
    e.preventDefault();

    const formData = new FormData(this);
    const submitBtn = this.querySelector('button[type="submit"]');

    submitBtn.disabled = true;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Uploading...';

    fetch('upload.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                bootstrap.Modal.getInstance(document.getElementById('uploadModal')).hide();
                location.reload();
            } else {
                alert('Upload failed: ' + data.message);
            }
        })
        .finally(() => {
            submitBtn.disabled = false;
            submitBtn.innerHTML = 'Upload Images';
        });
});

// Keyboard navigation for lightbox
document.addEventListener('keydown', function(e) {
    const modal = document.getElementById('lightboxModal');
    if (modal.classList.contains('show')) {
        if (e.key === 'ArrowLeft') previousImage();
        if (e.key === 'ArrowRight') nextImage();
        if (e.key === 'Escape') bootstrap.Modal.getInstance(modal).hide();
    }
});
</script>

<?php include '../../includes/footer.php'; ?>