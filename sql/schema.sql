CREATE TABLE IF NOT EXISTS settings (
    key VARCHAR(100) PRIMARY KEY,
    value TEXT NOT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS users (
    id SERIAL PRIMARY KEY,
    login VARCHAR(100) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role VARCHAR(20) NOT NULL CHECK (role IN ('admin', 'seller')),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS markup_rules (
    id SERIAL PRIMARY KEY,
    scope VARCHAR(20) NOT NULL CHECK (scope IN ('global', 'category', 'product')),
    target VARCHAR(255) DEFAULT NULL,
    percent DECIMAL(5,2) NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE (scope, target)
);

CREATE TABLE IF NOT EXISTS products (
    id SERIAL PRIMARY KEY,
    external_id VARCHAR(100) NOT NULL,
    brand VARCHAR(100) NOT NULL DEFAULT '',
    category VARCHAR(100) NOT NULL DEFAULT '',
    subcategory VARCHAR(100) NOT NULL DEFAULT '',
    name VARCHAR(255) NOT NULL,
    country VARCHAR(10) NOT NULL DEFAULT '',
    cost DECIMAL(12,2) NOT NULL DEFAULT 0,
    price DECIMAL(12,2) NOT NULL DEFAULT 0,
    synced_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE (external_id, country)
);

CREATE INDEX IF NOT EXISTS idx_products_brand ON products(brand);
CREATE INDEX IF NOT EXISTS idx_products_category ON products(category);
CREATE INDEX IF NOT EXISTS idx_products_subcategory ON products(subcategory);
CREATE INDEX IF NOT EXISTS idx_products_name ON products(name);

CREATE TABLE IF NOT EXISTS sync_log (
    id SERIAL PRIMARY KEY,
    started_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    finished_at TIMESTAMP NULL DEFAULT NULL,
    items_count INT DEFAULT 0,
    status VARCHAR(20) NOT NULL CHECK (status IN ('running', 'success', 'error')),
    error_message TEXT
);

INSERT INTO settings (key, value) VALUES
    ('api_base_url', 'https://api-c.rmgroup.website'),
    ('api_token', 'fa070fd94059505dff35021632a5522e24681c1429dfe92ee63266e4709dfe08'),
    ('default_markup_percent', '15'),
    ('rounding_step', '100'),
    ('sync_interval_minutes', '15'),
    ('last_sync_at', '')
ON CONFLICT (key) DO UPDATE SET value = EXCLUDED.value;
