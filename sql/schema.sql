-- BINJPIX Photo and Printing Service Request Management System
-- Run this manually if you are not using install.php

CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    display_name VARCHAR(50) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role VARCHAR(20) NOT NULL DEFAULT 'staff',
    created_at DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS customers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(100) NOT NULL,
    phone VARCHAR(30) NOT NULL,
    email VARCHAR(100) NULL,
    created_at DATETIME NOT NULL,
    INDEX idx_customers_phone (phone)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    reference VARCHAR(20) NOT NULL UNIQUE,
    customer_id INT NOT NULL,
    service_type VARCHAR(40) NOT NULL,
    service_mode VARCHAR(10) NOT NULL DEFAULT 'request', -- 'booking' or 'request'
    -- used when service_mode = 'booking' (photo shoots, videography)
    scheduled_date DATE NULL,
    scheduled_time VARCHAR(10) NULL,
    location VARCHAR(255) NULL,
    -- used when service_mode = 'request' (printing, editing)
    quantity INT NULL,
    claim_method VARCHAR(20) NULL,
    delivery_address VARCHAR(255) NULL,
    notes TEXT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'Pending',
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    CONSTRAINT fk_requests_customer FOREIGN KEY (customer_id) REFERENCES customers(id),
    INDEX idx_requests_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS request_files (
    id INT AUTO_INCREMENT PRIMARY KEY,
    request_id INT NOT NULL,
    original_name VARCHAR(255) NOT NULL,
    stored_name VARCHAR(100) NOT NULL,
    size_bytes INT NOT NULL,
    mime_type VARCHAR(100) NOT NULL,
    uploaded_at DATETIME NOT NULL,
    CONSTRAINT fk_files_request FOREIGN KEY (request_id) REFERENCES requests(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    request_id INT NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    payment_date DATE NOT NULL,
    recorded_by INT NULL,
    created_at DATETIME NOT NULL,
    CONSTRAINT fk_payments_request FOREIGN KEY (request_id) REFERENCES requests(id),
    CONSTRAINT fk_payments_user FOREIGN KEY (recorded_by) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
