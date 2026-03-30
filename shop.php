<?php
ob_start();
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once 'config/database.php';
require_once 'config/config.php';
require_once 'includes/functions.php';

// ─── Filter & pagination params ────────────────────────────────────────────
$category_slug = isset($_GET['category']) ? sanitize($_GET['category']) : '';
$search        = isset($_GET['search'])   ? sanitize($_GET['search'])   : '';
$sort          = isset($_GET['sort'])     ? sanitize($_GET['sort'])     : 'newest';
$page          = isset($_GET['page'])     ? max(1, (int)$_GET['page']) : 1;
$per_page      = 12;
$offset        = ($page - 1) * $per_page;

// ─── Resolve category ──────────────────────────────────────────────────────
$category_id   = 0;
$category_name = 'All Products';
if ($category_slug) {
    $cat = getCategoryBySlug($conn, $category_slug);
    if ($cat) {
        $category_id   = (int)$cat['id'];
        $category_name = $cat['name'];
    }
}

// ─── Build dynamic WHERE ───────────────────────────────────────────────────
$where  = "WHERE p.status = 'active'";
$params = [];
$types  = '';

if ($category_id) {
    $where   .= ' AND p.category_id = ?';
    $params[] = $category_id;
    $types   .= 'i';
}
if ($search) {
    $where   .= ' AND (p.name LIKE ? OR p.description LIKE ?)';
    $like     = '%' . $search . '%';
    $params[] = $like;
    $params[] = $like;
    $types   .= 'ss';
}

// ─── Sort order ────────────────────────────────────────────────────────────
$allowed_sorts = ['newest' => 'p.created_at DESC', 'price_asc' => 'COALESCE(p.sale_price, p.price) ASC', 'price_desc' => 'COALESCE(p.sale_price, p.price) DESC', 'name' => 'p.name ASC'];
$order_by = isset($allowed_sorts[$sort]) ? $allowed_sorts[$sort] : 'p.created_at DESC';
$order = "ORDER BY $order_by";

// ─── Count query ───────────────────────────────────────────────────────────
$count_sql  = "SELECT COUNT(*) AS total FROM products p $where";
$count_stmt = $conn->prepare($count_sql);
if ($types) {
    $count_stmt->bind_param($types, ...$params);
}
$count_stmt->execute();
$total_products = (int)$count_stmt->get_result()->fetch_assoc()['total'];
$count_stmt->close();
$total_pages = (int)ceil($total_products / $per_page);

// ─── Products query ────────────────────────────────────────────────────────
$sql = "SELECT p.*, c.name AS category_name, pi.image_path
        FROM products p
        LEFT JOIN categories c  ON p.category_id = c.id
        LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = 1
        $where $order LIMIT ? OFFSET ?";

$product_params = array_merge($params, [$per_page, $offset]);
$product_types  = $types . 'ii';

$stmt = $conn->prepare($sql);
$stmt->bind_param($product_types, ...$product_params);
$stmt->execute();
$products = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$categories = getCategories($conn);

$page_title       = $category_name . ' - Shop';
$meta_description = 'Browse our collection of premium luxury marble products.';
require_once 'includes/header.php';
?>

<!-- ─── Breadcrumb ─────────────────────────────────────────────────────────── -->
<div class="breadcrumb-section bg-cream py-3">
    <div class="container">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="<?php echo SITE_URL; ?>/">Home</a></li>
                <li class="breadcrumb-item <?php echo !$category_slug ? 'active' : ''; ?>">
                    <a href="<?php echo SITE_URL; ?>/shop.php">Shop</a>
                </li>
                <?php if ($category_slug && $category_id): ?>
                <li class="breadcrumb-item active"><?php echo htmlspecialchars($category_name); ?></li>
                <?php endif; ?>
            </ol>
        </nav>
    </div>
</div>

<!-- ─── Main layout ───────────────────────────────────────────────────────── -->
<div class="container my-5">
    <div class="row g-4">

        <!-- ══ LEFT SIDEBAR ══════════════════════════════════════════════════ -->
        <div class="col-lg-3">
            <div class="filter-sidebar card border-0 shadow-sm p-4">
                <h5 class="filter-heading mb-4">
                    <i class="fas fa-filter gold-text me-2"></i>Filter
                    <span class="gold-underline d-block mt-1"></span>
                </h5>

                <!-- Categories -->
                <div class="filter-group mb-4">
                    <h6 class="filter-group-title mb-3">Categories</h6>
                    <ul class="list-unstyled category-filter-list mb-0">
                        <li>
                            <a href="<?php echo SITE_URL; ?>/shop.php<?php echo $search ? '?search=' . urlencode($search) : ''; ?>"
                               class="category-filter-link <?php echo !$category_slug ? 'active' : ''; ?>">
                                <i class="fas fa-chevron-right me-1 small"></i>All Products
                            </a>
                        </li>
                        <?php foreach ($categories as $cat): ?>
                        <li>
                            <a href="<?php echo SITE_URL; ?>/shop.php?category=<?php echo htmlspecialchars($cat['slug']); ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?>"
                               class="category-filter-link <?php echo $category_slug === $cat['slug'] ? 'active' : ''; ?>">
                                <i class="fas fa-chevron-right me-1 small"></i><?php echo htmlspecialchars($cat['name']); ?>
                            </a>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                </div>

                <!-- Price range -->
                <div class="filter-group mb-4">
                    <h6 class="filter-group-title mb-3">Price Range</h6>
                    <div class="price-range-container">
                        <input type="range" class="form-range gold-range" id="priceRange"
                               min="0" max="500000" step="1000"
                               value="<?php echo isset($_GET['max_price']) ? (int)$_GET['max_price'] : 500000; ?>">
                        <div class="d-flex justify-content-between mt-1">
                            <span class="small text-muted">₹0</span>
                            <span class="small gold-text fw-bold" id="priceRangeValue">
                                Up to ₹<span id="priceRangeDisplay"><?php echo isset($_GET['max_price']) ? number_format((int)$_GET['max_price']) : '5,00,000'; ?></span>
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Clear filters -->
                <?php if ($category_slug || $search || (isset($_GET['sort']) && $_GET['sort'] !== 'newest')): ?>
                <a href="<?php echo SITE_URL; ?>/shop.php" class="btn btn-outline-gold btn-sm w-100 mt-2">
                    <i class="fas fa-times me-1"></i>Clear Filters
                </a>
                <?php endif; ?>
            </div>
        </div><!-- /sidebar -->

        <!-- ══ MAIN CONTENT ══════════════════════════════════════════════════ -->
        <div class="col-lg-9">

            <!-- Top bar -->
            <div class="shop-topbar d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
                <p class="mb-0 text-muted">
                    <strong class="text-dark"><?php echo $total_products; ?></strong> product<?php echo $total_products !== 1 ? 's' : ''; ?> found
                    <?php if ($category_slug && $category_id): ?>
                    in <span class="gold-text"><?php echo htmlspecialchars($category_name); ?></span>
                    <?php endif; ?>
                </p>

                <div class="d-flex gap-2 align-items-center flex-wrap">
                    <!-- Search -->
                    <form method="get" action="" class="d-flex gap-1">
                        <?php if ($category_slug): ?>
                        <input type="hidden" name="category" value="<?php echo htmlspecialchars($category_slug); ?>">
                        <?php endif; ?>
                        <input type="text" name="search" class="form-control form-control-sm"
                               placeholder="Search products…"
                               value="<?php echo htmlspecialchars($search); ?>"
                               style="width:180px;">
                        <button type="submit" class="btn btn-gold btn-sm">
                            <i class="fas fa-search"></i>
                        </button>
                    </form>

                    <!-- Sort -->
                    <form method="get" action="" id="sortForm">
                        <?php if ($category_slug): ?>
                        <input type="hidden" name="category" value="<?php echo htmlspecialchars($category_slug); ?>">
                        <?php endif; ?>
                        <?php if ($search): ?>
                        <input type="hidden" name="search" value="<?php echo htmlspecialchars($search); ?>">
                        <?php endif; ?>
                        <select name="sort" class="form-select form-select-sm sort-select" onchange="this.form.submit()">
                            <option value="newest"     <?php echo $sort === 'newest'     ? 'selected' : ''; ?>>Newest First</option>
                            <option value="price_asc"  <?php echo $sort === 'price_asc'  ? 'selected' : ''; ?>>Price: Low → High</option>
                            <option value="price_desc" <?php echo $sort === 'price_desc' ? 'selected' : ''; ?>>Price: High → Low</option>
                            <option value="name"       <?php echo $sort === 'name'       ? 'selected' : ''; ?>>Name: A → Z</option>
                        </select>
                    </form>
                </div>
            </div>

            <!-- Product grid -->
            <?php if (!empty($products)): ?>
            <div class="row g-4">
                <?php foreach ($products as $product): ?>
                <div class="col-md-6 col-lg-4">
                    <?php include __DIR__ . '/includes/product-card.php'; ?>
                </div>
                <?php endforeach; ?>
            </div>

            <?php else: ?>
            <div class="no-products text-center py-5">
                <i class="fas fa-search fa-4x text-muted mb-4"></i>
                <h4 class="text-muted">No products found</h4>
                <p class="text-muted mb-4">Try adjusting your search or filter criteria.</p>
                <a href="<?php echo SITE_URL; ?>/shop.php" class="btn btn-gold">
                    <i class="fas fa-redo me-2"></i>View All Products
                </a>
            </div>
            <?php endif; ?>

            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
            <nav class="mt-5" aria-label="Product pages">
                <ul class="pagination justify-content-center">
                    <!-- Prev -->
                    <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                        <a class="page-link" href="?<?php
                            $q = array_filter(['category' => $category_slug, 'search' => $search, 'sort' => $sort, 'page' => $page - 1]);
                            echo http_build_query($q);
                        ?>"><i class="fas fa-chevron-left"></i></a>
                    </li>

                    <?php
                    $range = 2;
                    $start = max(1, $page - $range);
                    $end   = min($total_pages, $page + $range);
                    if ($start > 1): ?>
                    <li class="page-item">
                        <a class="page-link" href="?<?php echo http_build_query(array_filter(['category' => $category_slug, 'search' => $search, 'sort' => $sort, 'page' => 1])); ?>">1</a>
                    </li>
                    <?php if ($start > 2): ?><li class="page-item disabled"><span class="page-link">…</span></li><?php endif; ?>
                    <?php endif; ?>

                    <?php for ($p = $start; $p <= $end; $p++): ?>
                    <li class="page-item <?php echo $p === $page ? 'active' : ''; ?>">
                        <a class="page-link" href="?<?php echo http_build_query(array_filter(['category' => $category_slug, 'search' => $search, 'sort' => $sort, 'page' => $p])); ?>"><?php echo $p; ?></a>
                    </li>
                    <?php endfor; ?>

                    <?php if ($end < $total_pages):
                        if ($end < $total_pages - 1): ?><li class="page-item disabled"><span class="page-link">…</span></li><?php endif; ?>
                    <li class="page-item">
                        <a class="page-link" href="?<?php echo http_build_query(array_filter(['category' => $category_slug, 'search' => $search, 'sort' => $sort, 'page' => $total_pages])); ?>"><?php echo $total_pages; ?></a>
                    </li>
                    <?php endif; ?>

                    <!-- Next -->
                    <li class="page-item <?php echo $page >= $total_pages ? 'disabled' : ''; ?>">
                        <a class="page-link" href="?<?php
                            $q = array_filter(['category' => $category_slug, 'search' => $search, 'sort' => $sort, 'page' => $page + 1]);
                            echo http_build_query($q);
                        ?>"><i class="fas fa-chevron-right"></i></a>
                    </li>
                </ul>
            </nav>
            <?php endif; ?>

        </div><!-- /main content -->
    </div><!-- /row -->
</div><!-- /container -->

<script>
// Live price-range display
(function () {
    var range   = document.getElementById('priceRange');
    var display = document.getElementById('priceRangeDisplay');
    if (!range || !display) return;
    range.addEventListener('input', function () {
        var val = parseInt(this.value, 10);
        display.textContent = val.toLocaleString('en-IN');
    });
})();
</script>

<?php
require_once 'includes/footer.php';
ob_end_flush();
?>
