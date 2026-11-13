-- Hotel Room Booking System — Database Schema (InfinityFree / shared hosting)
--
-- InfinityFree gives you ONE database already created (something like
-- epiz_12345678_hotel) — you can't CREATE DATABASE yourself, so this
-- version skips straight to creating tables. Import it via phpMyAdmin:
--   1. Log in to your InfinityFree control panel → MySQL Databases
--   2. Open phpMyAdmin for your database
--   3. Click "Import", choose this file, and run it
-- Your database is already selected in phpMyAdmin, so no USE statement
-- is needed.

-- ---------------------------------------------------------------
-- users
-- ---------------------------------------------------------------
CREATE TABLE IF NOT EXISTS users (
    id                      INT AUTO_INCREMENT PRIMARY KEY,
    name                    VARCHAR(150)  NOT NULL,
    email                   VARCHAR(150)  NOT NULL UNIQUE,
    password_hash           VARCHAR(255)  NOT NULL,
    phone                   VARCHAR(30)   NOT NULL,
    nationality             VARCHAR(50)   NOT NULL,
    role                    ENUM('guest','admin') NOT NULL DEFAULT 'guest',
    remember_token          VARCHAR(255)  NULL,
    preferred_room_type_id  INT           NULL,
    special_requests        TEXT          NULL,
    created_at              TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ---------------------------------------------------------------
-- room_types
-- ---------------------------------------------------------------
CREATE TABLE IF NOT EXISTS room_types (
    id               INT AUTO_INCREMENT PRIMARY KEY,
    name             VARCHAR(100)   NOT NULL,
    description      TEXT           NULL,
    price_per_night  DECIMAL(10,2)  NOT NULL,
    max_capacity     INT            NOT NULL,
    thumbnail_path   VARCHAR(255)   NULL,
    amenities        VARCHAR(255)   NULL,
    created_at       TIMESTAMP      NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

ALTER TABLE users
    ADD CONSTRAINT fk_users_preferred_room_type
    FOREIGN KEY (preferred_room_type_id) REFERENCES room_types(id)
    ON DELETE SET NULL;

-- ---------------------------------------------------------------
-- rooms  (physical rooms, each tied to a room type)
-- ---------------------------------------------------------------
CREATE TABLE IF NOT EXISTS rooms (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    room_number   VARCHAR(20)  NOT NULL UNIQUE,
    floor         INT          NOT NULL DEFAULT 1,
    room_type_id  INT          NOT NULL,
    status        ENUM('available','maintenance') NOT NULL DEFAULT 'available',
    CONSTRAINT fk_rooms_room_type
        FOREIGN KEY (room_type_id) REFERENCES room_types(id)
        ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------------
-- bookings
-- ---------------------------------------------------------------
CREATE TABLE IF NOT EXISTS bookings (
    id             INT AUTO_INCREMENT PRIMARY KEY,
    user_id        INT            NOT NULL,
    room_id        INT            NOT NULL,
    checkin_date   DATE           NOT NULL,
    checkout_date  DATE           NOT NULL,
    total_price    DECIMAL(10,2)  NOT NULL,
    status         ENUM('Booked','Checked-In','Checked-Out','Cancelled') NOT NULL DEFAULT 'Booked',
    created_at     TIMESTAMP      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_bookings_user
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_bookings_room
        FOREIGN KEY (room_id) REFERENCES rooms(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------------
-- Seed data
-- ---------------------------------------------------------------

-- Default admin account: admin@hotel.com / Admin@123
INSERT INTO users (name, email, password_hash, phone, nationality, role)
VALUES (
    'Hotel Administrator',
    'admin@hotel.com',
    '$2y$10$ELWz0RSH87SFHfDbmmQYB.YbNhREuNFJGe.oJbqOzCCrB76HJL8ra', -- Admin@123
    '01700000000',
    'Bangladeshi',
    'admin'
) ON DUPLICATE KEY UPDATE email = email;

INSERT INTO room_types (name, description, price_per_night, max_capacity, thumbnail_path, amenities) VALUES
('Standard', 'A cozy, well-appointed room perfect for solo travelers or couples.', 3500.00, 2, '', 'WiFi,AC,TV'),
('Deluxe',   'Spacious room with a scenic view and upgraded amenities.',            6500.00, 3, '', 'WiFi,AC,Minibar,Balcony'),
('Suite',    'Our most luxurious accommodation with a separate living area.',      12000.00, 4, '', 'WiFi,AC,Bathtub,Safe,Balcony');

INSERT INTO rooms (room_number, floor, room_type_id, status) VALUES
('101', 1, 1, 'available'),
('102', 1, 1, 'available'),
('103', 1, 1, 'available'),
('201', 2, 2, 'available'),
('202', 2, 2, 'available'),
('203', 2, 2, 'maintenance'),
('301', 3, 3, 'available'),
('302', 3, 3, 'available');
