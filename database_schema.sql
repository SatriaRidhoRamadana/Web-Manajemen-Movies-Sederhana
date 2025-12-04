-- Database schema for movie ticket booking API

CREATE DATABASE IF NOT EXISTS movie_booking;
USE movie_booking;

-- Users table
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    phone VARCHAR(20),
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Movies table
CREATE TABLE movies (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    duration INT NOT NULL, -- in minutes
    genre VARCHAR(100),
    release_date DATE,
    poster_url VARCHAR(500),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Showtimes table
CREATE TABLE showtimes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    movie_id INT NOT NULL,
    show_date DATE NOT NULL,
    show_time TIME NOT NULL,
    theater VARCHAR(100) NOT NULL,
    total_seats INT NOT NULL DEFAULT 100,
    available_seats INT NOT NULL DEFAULT 100,
    price DECIMAL(10,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (movie_id) REFERENCES movies(id) ON DELETE CASCADE
);

-- Bookings table
CREATE TABLE bookings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    showtime_id INT NOT NULL,
    seats_booked INT NOT NULL,
    total_amount DECIMAL(10,2) NOT NULL,
    booking_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status ENUM('confirmed', 'cancelled') DEFAULT 'confirmed',
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (showtime_id) REFERENCES showtimes(id) ON DELETE CASCADE
);

-- Insert sample data
INSERT INTO users (name, email, phone, password) VALUES
('John Doe', 'john@example.com', '08123456789', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4o1rX2.LbWYuH5hl5YEBG/8h/9y4Gq'),
('Jane Smith', 'jane@example.com', '08198765432', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4o1rX2.LbWYuH5hl5YEBG/8h/9y4Gq'),
('Ahmad Rahman', 'ahmad@example.com', '08134567890', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4o1rX2.LbWYuH5hl5YEBG/8h/9y4Gq'),
('Siti Nurhaliza', 'siti@example.com', '08145678901', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4o1rX2.LbWYuH5hl5YEBG/8h/9y4Gq'),
('Budi Santoso', 'budi@example.com', '08156789012', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4o1rX2.LbWYuH5hl5YEBG/8h/9y4Gq');

INSERT INTO movies (title, description, duration, genre, release_date, poster_url) VALUES
('Avengers: Endgame', 'After the devastating events of Avengers: Infinity War, the universe is in ruins. With the help of remaining allies, the Avengers assemble once more in order to reverse Thanos actions and restore balance to the universe.', 181, 'Action', '2019-04-26', 'https://example.com/avengers.jpg'),
('Spider-Man: No Way Home', 'Peter Parker is unmasked and no longer able to separate his normal life from the high-stakes of being a super-hero. When he asks for help from Doctor Strange the stakes become even more dangerous, forcing him to discover what it truly means to be Spider-Man.', 148, 'Action', '2021-12-17', 'https://example.com/spiderman.jpg'),
('The Batman', 'When a sadistic serial killer begins murdering key political figures in Gotham, Batman is forced to investigate the citys hidden corruption and question his familys involvement.', 176, 'Action', '2022-03-04', 'https://example.com/batman.jpg'),
('Dune', 'Paul Atreides, a brilliant and gifted young man born into a great destiny beyond his understanding, must travel to the most dangerous planet in the universe to ensure the future of his family and his people.', 155, 'Sci-Fi', '2021-10-22', 'https://example.com/dune.jpg'),
('Top Gun: Maverick', 'After more than thirty years of service as one of the Navys top aviators, Pete Mitchell is where he belongs, pushing the envelope as a courageous test pilot and dodging the advancement in rank that would ground him.', 130, 'Action', '2022-05-27', 'https://example.com/topgun.jpg'),
('Black Panther: Wakanda Forever', 'The people of Wakanda fight to protect their home from intervening world powers as they mourn the death of King TChalla.', 161, 'Action', '2022-11-11', 'https://example.com/blackpanther.jpg');

INSERT INTO showtimes (movie_id, show_date, show_time, theater, total_seats, available_seats, price) VALUES
(1, '2024-01-15', '14:00:00', 'Cinema 1', 100, 100, 50000.00),
(1, '2024-01-15', '18:00:00', 'Cinema 1', 100, 100, 55000.00),
(1, '2024-01-16', '10:00:00', 'Cinema 2', 100, 100, 50000.00),
(2, '2024-01-16', '15:00:00', 'Cinema 2', 100, 100, 45000.00),
(2, '2024-01-16', '19:00:00', 'Cinema 1', 100, 100, 50000.00),
(3, '2024-01-17', '20:00:00', 'Cinema 1', 100, 100, 60000.00),
(3, '2024-01-18', '14:00:00', 'Cinema 3', 100, 100, 55000.00),
(4, '2024-01-18', '16:00:00', 'Cinema 2', 100, 100, 45000.00),
(5, '2024-01-19', '13:00:00', 'Cinema 1', 100, 100, 50000.00),
(5, '2024-01-19', '17:00:00', 'Cinema 3', 100, 100, 55000.00),
(6, '2024-01-20', '15:00:00', 'Cinema 2', 100, 100, 50000.00),
(6, '2024-01-20', '19:00:00', 'Cinema 1', 100, 100, 55000.00);

INSERT INTO bookings (user_id, showtime_id, seats_booked, total_amount, status) VALUES
(1, 1, 2, 100000.00, 'confirmed'),
(2, 2, 1, 55000.00, 'confirmed'),
(3, 4, 3, 135000.00, 'confirmed'),
(1, 6, 2, 120000.00, 'confirmed'),
(4, 8, 1, 45000.00, 'confirmed');
    seats_booked INT NOT NULL,

    total_amount DECIMAL(10,2) NOT NULL,

    booking_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    status ENUM('confirmed', 'cancelled') DEFAULT 'confirmed',

    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,

    FOREIGN KEY (showtime_id) REFERENCES showtimes(id) ON DELETE CASCADE

);



-- Insert sample data

INSERT INTO users (name, email, phone) VALUES

('John Doe', 'john@example.com', '08123456789'),

('Jane Smith', 'jane@example.com', '08198765432'),

('Ahmad Rahman', 'ahmad@example.com', '08134567890'),

('Siti Nurhaliza', 'siti@example.com', '08145678901'),

('Budi Santoso', 'budi@example.com', '08156789012');



INSERT INTO movies (title, description, duration, genre, release_date, poster_url) VALUES

('Avengers: Endgame', 'After the devastating events of Avengers: Infinity War, the universe is in ruins. With the help of remaining allies, the Avengers assemble once more in order to reverse Thanos actions and restore balance to the universe.', 181, 'Action', '2019-04-26', 'https://example.com/avengers.jpg'),

('Spider-Man: No Way Home', 'Peter Parker is unmasked and no longer able to separate his normal life from the high-stakes of being a super-hero. When he asks for help from Doctor Strange the stakes become even more dangerous, forcing him to discover what it truly means to be Spider-Man.', 148, 'Action', '2021-12-17', 'https://example.com/spiderman.jpg'),

('The Batman', 'When a sadistic serial killer begins murdering key political figures in Gotham, Batman is forced to investigate the citys hidden corruption and question his familys involvement.', 176, 'Action', '2022-03-04', 'https://example.com/batman.jpg'),

('Dune', 'Paul Atreides, a brilliant and gifted young man born into a great destiny beyond his understanding, must travel to the most dangerous planet in the universe to ensure the future of his family and his people.', 155, 'Sci-Fi', '2021-10-22', 'https://example.com/dune.jpg'),

('Top Gun: Maverick', 'After more than thirty years of service as one of the Navys top aviators, Pete Mitchell is where he belongs, pushing the envelope as a courageous test pilot and dodging the advancement in rank that would ground him.', 130, 'Action', '2022-05-27', 'https://example.com/topgun.jpg'),

('Black Panther: Wakanda Forever', 'The people of Wakanda fight to protect their home from intervening world powers as they mourn the death of King TChalla.', 161, 'Action', '2022-11-11', 'https://example.com/blackpanther.jpg');



INSERT INTO showtimes (movie_id, show_date, show_time, theater, total_seats, available_seats, price) VALUES

(1, '2024-01-15', '14:00:00', 'Cinema 1', 100, 100, 50000.00),

(1, '2024-01-15', '18:00:00', 'Cinema 1', 100, 100, 55000.00),

(1, '2024-01-16', '10:00:00', 'Cinema 2', 100, 100, 50000.00),

(2, '2024-01-16', '15:00:00', 'Cinema 2', 100, 100, 45000.00),

(2, '2024-01-16', '19:00:00', 'Cinema 1', 100, 100, 50000.00),

(3, '2024-01-17', '20:00:00', 'Cinema 1', 100, 100, 60000.00),

(3, '2024-01-18', '14:00:00', 'Cinema 3', 100, 100, 55000.00),

(4, '2024-01-18', '16:00:00', 'Cinema 2', 100, 100, 45000.00),

(5, '2024-01-19', '13:00:00', 'Cinema 1', 100, 100, 50000.00),

(5, '2024-01-19', '17:00:00', 'Cinema 3', 100, 100, 55000.00),

(6, '2024-01-20', '15:00:00', 'Cinema 2', 100, 100, 50000.00),

(6, '2024-01-20', '19:00:00', 'Cinema 1', 100, 100, 55000.00);



INSERT INTO bookings (user_id, showtime_id, seats_booked, total_amount, status) VALUES

(1, 1, 2, 100000.00, 'confirmed'),

(2, 2, 1, 55000.00, 'confirmed'),

(3, 4, 3, 135000.00, 'confirmed'),

(1, 6, 2, 120000.00, 'confirmed'),

(4, 8, 1, 45000.00, 'confirmed');
