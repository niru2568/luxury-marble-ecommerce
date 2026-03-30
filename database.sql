-- Luxury Marble Ecommerce Database
-- =====================================

CREATE DATABASE IF NOT EXISTS luxury_marble
  DEFAULT CHARACTER SET utf8mb4
  DEFAULT COLLATE utf8mb4_unicode_ci;

USE luxury_marble;

-- ─── TABLES ─────────────────────────────────────────────────────────────────

CREATE TABLE IF NOT EXISTS admin_users (
  id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  username    VARCHAR(100) NOT NULL,
  password    VARCHAR(255) NOT NULL,
  email       VARCHAR(255) NOT NULL,
  created_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_admin_username (username),
  UNIQUE KEY uq_admin_email    (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS categories (
  id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  name        VARCHAR(150) NOT NULL,
  slug        VARCHAR(160) NOT NULL,
  description TEXT         NULL,
  image       VARCHAR(255) NULL,
  status      ENUM('active','inactive') NOT NULL DEFAULT 'active',
  created_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_category_slug (slug),
  INDEX idx_category_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS products (
  id               INT UNSIGNED NOT NULL AUTO_INCREMENT,
  name             VARCHAR(255) NOT NULL,
  slug             VARCHAR(270) NOT NULL,
  category_id      INT UNSIGNED NOT NULL,
  description      LONGTEXT     NULL,
  price            DECIMAL(10,2) NOT NULL,
  sale_price       DECIMAL(10,2) NULL,
  stock            INT          NOT NULL DEFAULT 0,
  featured         TINYINT(1)   NOT NULL DEFAULT 0,
  status           ENUM('active','inactive') NOT NULL DEFAULT 'active',
  meta_title       VARCHAR(255) NULL,
  meta_description VARCHAR(500) NULL,
  created_at       DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at       DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_product_slug (slug),
  INDEX idx_product_status   (status),
  INDEX idx_product_featured (featured),
  CONSTRAINT fk_product_category FOREIGN KEY (category_id)
    REFERENCES categories (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS product_images (
  id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  product_id  INT UNSIGNED NOT NULL,
  image_path  VARCHAR(255) NOT NULL,
  is_primary  TINYINT(1)   NOT NULL DEFAULT 0,
  sort_order  INT          NOT NULL DEFAULT 0,
  PRIMARY KEY (id),
  INDEX idx_pi_product    (product_id),
  INDEX idx_pi_is_primary (is_primary),
  CONSTRAINT fk_pi_product FOREIGN KEY (product_id)
    REFERENCES products (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS slider (
  id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  title       VARCHAR(255) NULL,
  subtitle    VARCHAR(500) NULL,
  image       VARCHAR(255) NOT NULL,
  button_text VARCHAR(100) NULL,
  button_link VARCHAR(500) NULL,
  sort_order  INT          NOT NULL DEFAULT 0,
  status      ENUM('active','inactive') NOT NULL DEFAULT 'active',
  created_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  INDEX idx_slider_status     (status),
  INDEX idx_slider_sort_order (sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS users (
  id              INT UNSIGNED NOT NULL AUTO_INCREMENT,
  name            VARCHAR(150) NOT NULL,
  email           VARCHAR(255) NOT NULL,
  phone           VARCHAR(20)  NULL,
  password        VARCHAR(255) NOT NULL,
  address         TEXT         NULL,
  city            VARCHAR(100) NULL,
  state           VARCHAR(100) NULL,
  pincode         VARCHAR(10)  NULL,
  otp             VARCHAR(6)   NULL,
  otp_expires_at  DATETIME     NULL,
  email_verified  TINYINT(1)   NOT NULL DEFAULT 0,
  reset_token     VARCHAR(64)  NULL,
  reset_expires_at DATETIME    NULL,
  created_at      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_user_email (email),
  INDEX idx_user_reset_token (reset_token)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS orders (
  id                  INT UNSIGNED NOT NULL AUTO_INCREMENT,
  order_number        VARCHAR(32)  NOT NULL,
  user_id             INT UNSIGNED NULL,
  session_id          VARCHAR(128) NULL,
  name                VARCHAR(150) NOT NULL,
  email               VARCHAR(255) NOT NULL,
  phone               VARCHAR(20)  NOT NULL,
  address             TEXT         NOT NULL,
  city                VARCHAR(100) NOT NULL,
  state               VARCHAR(100) NOT NULL,
  pincode             VARCHAR(10)  NOT NULL,
  subtotal            DECIMAL(10,2) NOT NULL,
  shipping            DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  total               DECIMAL(10,2) NOT NULL,
  payment_method      ENUM('razorpay','cod','bank_transfer') NOT NULL DEFAULT 'cod',
  payment_status      ENUM('pending','paid','failed','refunded') NOT NULL DEFAULT 'pending',
  order_status        ENUM('pending','processing','shipped','delivered','cancelled') NOT NULL DEFAULT 'pending',
  razorpay_order_id   VARCHAR(64)  NULL,
  razorpay_payment_id VARCHAR(64)  NULL,
  notes               TEXT         NULL,
  created_at          DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at          DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_order_number (order_number),
  INDEX idx_order_user_id       (user_id),
  INDEX idx_order_payment_status (payment_status),
  INDEX idx_order_order_status   (order_status),
  CONSTRAINT fk_order_user FOREIGN KEY (user_id)
    REFERENCES users (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS order_items (
  id            INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  order_id      INT UNSIGNED  NOT NULL,
  product_id    INT UNSIGNED  NOT NULL,
  product_name  VARCHAR(255)  NOT NULL,
  product_image VARCHAR(255)  NULL,
  price         DECIMAL(10,2) NOT NULL,
  quantity      INT           NOT NULL,
  subtotal      DECIMAL(10,2) NOT NULL,
  PRIMARY KEY (id),
  INDEX idx_oi_order_id   (order_id),
  INDEX idx_oi_product_id (product_id),
  CONSTRAINT fk_oi_order   FOREIGN KEY (order_id)   REFERENCES orders   (id) ON DELETE CASCADE,
  CONSTRAINT fk_oi_product FOREIGN KEY (product_id) REFERENCES products (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS blog (
  id               INT UNSIGNED NOT NULL AUTO_INCREMENT,
  title            VARCHAR(255) NOT NULL,
  slug             VARCHAR(270) NOT NULL,
  content          LONGTEXT     NOT NULL,
  excerpt          TEXT         NULL,
  image            VARCHAR(255) NULL,
  category         VARCHAR(100) NOT NULL DEFAULT 'Uncategorized',
  meta_title       VARCHAR(255) NULL,
  meta_description VARCHAR(500) NULL,
  status           ENUM('active','inactive') NOT NULL DEFAULT 'active',
  created_at       DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at       DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_blog_slug (slug),
  INDEX idx_blog_status   (status),
  INDEX idx_blog_category (category)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS cart (
  id         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id    INT UNSIGNED NULL,
  session_id VARCHAR(128) NULL,
  product_id INT UNSIGNED NOT NULL,
  quantity   INT          NOT NULL DEFAULT 1,
  created_at DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  INDEX idx_cart_user_id    (user_id),
  INDEX idx_cart_session_id (session_id),
  INDEX idx_cart_product_id (product_id),
  CONSTRAINT fk_cart_product FOREIGN KEY (product_id)
    REFERENCES products (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── SAMPLE DATA ─────────────────────────────────────────────────────────────

-- Admin user  (password: Admin@123)
INSERT INTO admin_users (username, password, email) VALUES
('admin', '$2y$10$qD6aDpoSdqMBKhOFObHexuwvxDHtpKm2P5H2u1perZdvN5y7/dtVq', 'admin@luxemarble.com');

-- Categories
INSERT INTO categories (name, slug, description, status) VALUES
('Marble Mandirs',    'marble-mandirs',    'Handcrafted marble temples and mandirs for your home, crafted with precision and devotion.', 'active'),
('Marble Fireplaces', 'marble-fireplaces', 'Elegant marble fireplaces that add timeless warmth and luxury to any living space.',          'active'),
('Wall Panels',       'wall-panels',       'Premium marble wall panels and cladding to transform interiors with natural beauty.',         'active'),
('Marble Flooring',   'marble-flooring',   'Exquisite marble flooring solutions available in multiple finishes and patterns.',            'active'),
('Decorative Pieces', 'decorative-pieces', 'Bespoke marble decorative artifacts and sculptures for homes and offices.',                   'active');

-- Products
INSERT INTO products (name, slug, category_id, description, price, sale_price, stock, featured, status, meta_title, meta_description) VALUES
('Classic White Makrana Mandir',          'classic-white-makrana-mandir',          1, 'Beautifully handcrafted mandir made from premium Makrana white marble. Features intricate carvings, double shikhar design and fine filigree work.',                                                150000.00, 135000.00, 5,  1, 'active', 'Classic White Makrana Mandir | Luxe Marble',     'Buy Classic White Makrana Mandir online. Premium handcrafted marble temple.'),
('Renaissance Marble Fireplace',          'renaissance-marble-fireplace',          2, 'Inspired by European renaissance architecture, this Italian marble fireplace is a statement piece for premium homes and hotels.',                                                                 250000.00, NULL,       3,  1, 'active', 'Renaissance Marble Fireplace | Luxe Marble',     'Luxury Renaissance-style marble fireplace crafted from Italian marble.'),
('Rajasthani Carved Wall Panel',          'rajasthani-carved-wall-panel',          3, 'Intricate Rajasthani-style marble wall panel with traditional jali (lattice) carving. Perfect for feature walls, lobbies and premium interiors.',                                               75000.00,  65000.00, 10, 1, 'active', 'Rajasthani Carved Wall Panel | Luxe Marble',     'Traditional Rajasthani marble wall panel with jali carving.'),
('Statuario Marble Flooring Tile Set',    'statuario-marble-flooring-tile-set',    4, 'Premium Statuario marble flooring tiles with striking grey veining on white background. Sold per sq ft. Minimum order 100 sq ft.',                                                             45000.00,  NULL,       50, 0, 'active', 'Statuario Marble Flooring | Luxe Marble',        'Buy Statuario marble flooring tiles online. Premium Italian marble.'),
('Abstract Marble Sculpture',             'abstract-marble-sculpture',             5, 'Hand-carved abstract sculpture in Carrara marble. Each piece is unique, signed by the artist and comes with a certificate of authenticity.',                                                   85000.00,  75000.00, 8,  1, 'active', 'Abstract Marble Sculpture | Luxe Marble',        'Unique hand-carved abstract Carrara marble sculpture.'),
('Black Marquina Fireplace Surround',     'black-marquina-fireplace-surround',     2, 'Striking Black Marquina marble fireplace surround with gold veining. A bold luxury statement for contemporary interiors.',                                                                     185000.00, NULL,       4,  0, 'active', 'Black Marquina Fireplace | Luxe Marble',         'Black Marquina marble fireplace surround for luxury interiors.'),
('Double Shikhar Puja Mandir',            'double-shikhar-puja-mandir',            1, 'Grand double shikhar puja mandir in pure white marble with gold inlay work. Ideal for large pooja rooms.',                                                                                     220000.00, 199000.00, 3,  1, 'active', 'Double Shikhar Puja Mandir | Luxe Marble',       'Grand double shikhar puja mandir in white marble with gold inlay.'),
('Calacatta Gold Marble Wall Cladding',   'calacatta-gold-marble-wall-cladding',   3, 'Opulent Calacatta Gold marble wall cladding panels. Sold per sq ft. Creates a stunning feature wall or bathroom accent.',                                                                     35000.00,  NULL,       30, 0, 'active', 'Calacatta Gold Wall Cladding | Luxe Marble',     'Premium Calacatta Gold marble wall cladding panels.'),
('Marble Elephant Pair',                  'marble-elephant-pair',                  5, 'A pair of hand-carved white marble elephants, symbols of prosperity and good fortune. Perfect as an entrance or living room décor.',                                                            25000.00,  22000.00, 15, 0, 'active', 'Marble Elephant Pair | Luxe Marble',             'Hand-carved white marble elephant pair for home decor.'),
('Italian Botticino Flooring Collection', 'italian-botticino-flooring-collection', 4, 'Classic Botticino marble from Italy. Warm beige tones with soft veining, available in polished and honed finishes. Sold per sq ft.',                                                         15000.00,  NULL,       80, 0, 'active', 'Botticino Marble Flooring | Luxe Marble',        'Italian Botticino marble flooring in polished and honed finishes.');

-- Product images
INSERT INTO product_images (product_id, image_path, is_primary, sort_order) VALUES
(1,  'placeholder-1.jpg',  1, 1),
(2,  'placeholder-2.jpg',  1, 1),
(3,  'placeholder-3.jpg',  1, 1),
(4,  'placeholder-4.jpg',  1, 1),
(5,  'placeholder-5.jpg',  1, 1),
(6,  'placeholder-6.jpg',  1, 1),
(7,  'placeholder-7.jpg',  1, 1),
(8,  'placeholder-8.jpg',  1, 1),
(9,  'placeholder-9.jpg',  1, 1),
(10, 'placeholder-10.jpg', 1, 1);

-- Slider
INSERT INTO slider (title, subtitle, image, button_text, button_link, sort_order, status) VALUES
('Timeless Marble. Crafted for Royalty.',
 'Discover our exclusive collection of handcrafted marble mandirs, fireplaces, wall panels and décor — bringing heritage craftsmanship into modern luxury spaces.',
 'slider-1.jpg', 'Explore Collection', '/products', 1, 'active'),

('Transform Your Space with Natural Stone.',
 'From floors to feature walls, our premium marble surfaces redefine interiors. Custom sizes, finishes and designs available to order.',
 'slider-2.jpg', 'Shop Now', '/categories', 2, 'active');

-- Blog posts
INSERT INTO blog (title, slug, content, excerpt, category, meta_title, meta_description, status) VALUES

('The Timeless Appeal of Marble in Modern Interiors',
 'timeless-appeal-of-marble-modern-interiors',
 '<p>Marble has been synonymous with luxury, power and artistic achievement for millennia. From the Taj Mahal to the Pantheon in Rome, this natural stone has graced the most magnificent structures in human history. Yet far from being a relic of the past, marble continues to be the material of choice for discerning homeowners and architects in the twenty-first century.</p>
<p>The secret to marble\'s enduring popularity lies in its unique visual character. No two slabs of marble are identical — each piece carries its own pattern of veining, colour variation and mineral inclusions, making every installation a one-of-a-kind work of art. This natural uniqueness is something that no manufactured material can replicate.</p>
<h2>Marble in Contemporary Design</h2>
<p>Today\'s interior designers are finding fresh and exciting ways to incorporate marble into modern spaces. Rather than covering entire rooms in a single marble type, the contemporary approach is to use marble as a dramatic accent — a statement fireplace surround, a kitchen island top or a feature bathroom wall.</p>
<p>The contrast of cool white Carrara or Statuario marble against warm wood tones, matte black fixtures and soft textiles creates an interior that feels both luxurious and liveable. This interplay of textures and materials is at the heart of modern luxury design.</p>
<h2>Caring for Marble</h2>
<p>One of the most common concerns about marble is its maintenance. While it is true that marble requires a little more care than ceramic or porcelain, the effort is well worth the reward. Sealing marble surfaces annually protects against staining, and wiping up spills promptly prevents etching from acidic liquids. With proper care, marble surfaces will remain beautiful for generations.</p>
<p>At Luxe Marble, every piece we deliver is accompanied by detailed care instructions tailored to that specific stone type, ensuring your investment retains its beauty for decades to come.</p>',
 'Marble has graced magnificent structures for millennia. Discover how this timeless stone continues to redefine modern luxury interiors.',
 'Interior Design',
 'Marble in Modern Interiors | Luxe Marble Blog',
 'Explore the timeless appeal of marble in contemporary interior design. Tips, trends and inspiration from Luxe Marble.',
 'active'),

('Choosing the Right Marble for Your Pooja Room',
 'choosing-right-marble-pooja-room',
 '<p>A pooja room is the spiritual heart of an Indian home. It is a space that calls for materials that are not only beautiful but also imbued with a sense of purity and sanctity. Marble has been the traditional choice for mandirs and pooja spaces for hundreds of years, and for good reason — its cool, pristine surface and natural luminosity create an atmosphere of calm reverence.</p>
<h2>White Marble: The Purest Choice</h2>
<p>White marble — particularly Makrana white, which was used to construct the Taj Mahal — is the most popular choice for marble mandirs. Its pure white surface reflects light beautifully and lends the mandir a divine radiance. Makrana marble is also exceptionally durable and resistant to chipping, making it ideal for intricately carved structures.</p>
<h2>Factors to Consider</h2>
<p>When choosing marble for your pooja room, consider the size of the space first. A small pooja alcove may benefit from a compact yet ornate mandir with detailed filigree carving, while a dedicated pooja room can accommodate a grand double shikhar structure with multiple deity platforms.</p>
<p>Finish is another important consideration. A polished finish gives marble a mirror-like shine that is visually spectacular, while a honed (matte) finish is softer and less slippery — often preferred for floor areas around the mandir where family members sit or stand during prayer.</p>
<h2>Custom Mandir Design</h2>
<p>At Luxe Marble, we offer fully customised mandir designs. Our master craftsmen can incorporate your preferred deity, family traditions and architectural style into a bespoke design. We offer free design consultations and 3D previews before fabrication begins, ensuring the final piece exceeds your expectations.</p>',
 'Discover how to choose the perfect marble for your pooja room. Expert guidance on stone types, finishes and mandir styles from Luxe Marble.',
 'Buying Guide',
 'Marble Pooja Room Guide | Luxe Marble Blog',
 'A comprehensive guide to choosing the right marble for your pooja room and mandir. Expert advice from Luxe Marble.',
 'active'),

('Top Marble Trends Dominating Luxury Homes in 2024',
 'top-marble-trends-luxury-homes-2024',
 '<p>The world of luxury interiors is constantly evolving, but certain materials never go out of fashion. Marble is one of them. In 2024, we are seeing exciting new ways in which marble is being incorporated into high-end residential projects across India and the world. Here are the key trends shaping the luxury marble landscape this year.</p>
<h2>1. Bookmatched Marble Feature Walls</h2>
<p>Bookmatching — the technique of mirroring two adjacent marble slabs to create a symmetrical, butterfly-wing pattern — is having a major moment in luxury interiors. When executed with a statement marble like Calacatta Gold or Nero Marquina, a bookmatched wall becomes the focal point of an entire room.</p>
<h2>2. Fluted Marble Surfaces</h2>
<p>Fluting — the carving of vertical channels into a surface — has moved from classical architecture into contemporary kitchens and bathrooms. Fluted marble kitchen islands and bathroom vanities add tactile depth and visual interest while maintaining the luxurious feel of natural stone.</p>
<h2>3. Marble and Brass Combinations</h2>
<p>The pairing of white or grey marble with warm brass hardware and fixtures is a dominant trend in 2024 luxury interiors. The coolness of the stone against the warmth of brass creates a sophisticated balance that feels both timeless and contemporary.</p>
<h2>4. Sustainable Locally Sourced Marble</h2>
<p>There is a growing preference for locally sourced Indian marbles such as Makrana White, Rajnagar Pink and Kota Stone. Architects and designers are recognising the quality and sustainability credentials of Indian stone, reducing the carbon footprint of luxury projects while supporting local craftsmanship.</p>
<h2>5. Marble Outdoor Spaces</h2>
<p>Marble is increasingly finding its way outdoors — on terrace flooring, pool surrounds, exterior cladding and garden sculptures. Weather-resistant sealants and improved stone-treatment technologies have made marble a viable and stunning choice for exterior applications.</p>
<p>At Luxe Marble, we stay ahead of these trends and can guide you in selecting the right marble and design for your project. Contact us for a free consultation with one of our design experts.</p>',
 'Discover the top marble design trends defining luxury homes in 2024 — from bookmatched feature walls to fluted surfaces and sustainable Indian stone.',
 'Trends',
 'Marble Trends 2024 | Luxe Marble Blog',
 'Explore the top marble trends shaping luxury interiors in 2024. Insights and inspiration from Luxe Marble.',
 'active');
