-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jun 03, 2026 at 09:32 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `web_programiranje`
--

-- --------------------------------------------------------

--
-- Table structure for table `filmovi`
--

CREATE TABLE `filmovi` (
  `id` int(11) NOT NULL,
  `naslov` varchar(255) NOT NULL,
  `zanr` varchar(100) NOT NULL,
  `godina` year(4) NOT NULL,
  `trajanje` int(11) NOT NULL COMMENT 'Trajanje u minutama (30-300)',
  `ocjena` decimal(3,1) NOT NULL DEFAULT 0.0,
  `redatelj` varchar(150) NOT NULL,
  `zemlja` varchar(100) NOT NULL,
  `opis` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ;

--
-- Dumping data for table `filmovi`
--

INSERT INTO `filmovi` (`id`, `naslov`, `zanr`, `godina`, `trajanje`, `ocjena`, `redatelj`, `zemlja`, `opis`, `created_at`) VALUES
(1, 'The Shawshank Redemption', 'Drama', '1994', 142, 9.3, 'Frank Darabont', 'USA', NULL, '2026-05-14 19:42:00'),
(2, 'The Godfather', 'Crime, Drama', '1972', 175, 9.2, 'Francis Ford Coppola', 'USA', NULL, '2026-05-14 19:42:00'),
(3, 'The Dark Knight', 'Action, Crime', '2008', 152, 9.0, 'Christopher Nolan', 'UK/USA', NULL, '2026-05-14 19:42:00'),
(4, 'Schindler\'s List', 'Biography, Drama', '1993', 195, 9.0, 'Steven Spielberg', 'USA', NULL, '2026-05-14 19:42:00'),
(5, '12 Angry Men', 'Crime, Drama', '1957', 96, 9.0, 'Sidney Lumet', 'USA', NULL, '2026-05-14 19:42:00'),
(6, 'Pulp Fiction', 'Crime, Drama', '1994', 154, 8.9, 'Quentin Tarantino', 'USA', NULL, '2026-05-14 19:42:00'),
(7, 'The Lord of the Rings: The Return of the King', 'Action, Adventure', '2003', 201, 9.0, 'Peter Jackson', 'NZ/USA', NULL, '2026-05-14 19:42:00'),
(8, 'Il Buono il Brutto il Cattivo', 'Western', '1966', 161, 8.8, 'Sergio Leone', 'Italy', NULL, '2026-05-14 19:42:00'),
(9, 'Fight Club', 'Drama', '1999', 139, 8.8, 'David Fincher', 'USA', NULL, '2026-05-14 19:42:00'),
(10, 'Inception', 'Action, Adventure', '2010', 148, 8.8, 'Christopher Nolan', 'USA/UK', NULL, '2026-05-14 19:42:00'),
(11, 'The Matrix', 'Action, Sci-Fi', '1999', 136, 8.7, 'Lana Wachowski', 'USA', NULL, '2026-05-14 19:42:00'),
(12, 'Goodfellas', 'Biography, Crime', '1990', 145, 8.7, 'Martin Scorsese', 'USA', NULL, '2026-05-14 19:42:00'),
(13, 'One Flew Over the Cuckoo\'s Nest', 'Drama', '1975', 133, 8.7, 'Milos Forman', 'USA', NULL, '2026-05-14 19:42:00'),
(14, 'Seven Samurai', 'Action, Drama', '1954', 207, 8.6, 'Akira Kurosawa', 'Japan', NULL, '2026-05-14 19:42:00'),
(15, 'Se7en', 'Crime, Drama', '1995', 127, 8.6, 'David Fincher', 'USA', NULL, '2026-05-14 19:42:00'),
(16, 'The Silence of the Lambs', 'Crime, Drama', '1991', 118, 8.6, 'Jonathan Demme', 'USA', NULL, '2026-05-14 19:42:00'),
(17, 'City of God', 'Crime, Drama', '2002', 130, 8.6, 'Fernando Meirelles', 'Brazil', NULL, '2026-05-14 19:42:00'),
(18, 'Life Is Beautiful', 'Comedy, Drama', '1997', 116, 8.6, 'Roberto Benigni', 'Italy', NULL, '2026-05-14 19:42:00'),
(19, 'Interstellar', 'Adventure, Drama', '2014', 169, 8.7, 'Christopher Nolan', 'USA/UK', NULL, '2026-05-14 19:42:00'),
(20, 'Saving Private Ryan', 'Drama, War', '1998', 169, 8.6, 'Steven Spielberg', 'USA', NULL, '2026-05-14 19:42:00'),
(21, 'Parasite', 'Drama, Thriller', '2019', 132, 8.5, 'Bong Joon Ho', 'South Korea', NULL, '2026-05-14 19:42:00'),
(22, 'The Green Mile', 'Crime, Drama', '1999', 189, 8.6, 'Frank Darabont', 'USA', NULL, '2026-05-14 19:42:00'),
(23, 'Star Wars: Episode IV - A New Hope', 'Action, Adventure', '1977', 121, 8.6, 'George Lucas', 'USA', NULL, '2026-05-14 19:42:00'),
(24, 'Terminator 2: Judgment Day', 'Action, Sci-Fi', '1991', 137, 8.6, 'James Cameron', 'USA', NULL, '2026-05-14 19:42:00'),
(25, 'Back to the Future', 'Adventure, Comedy', '1985', 116, 8.5, 'Robert Zemeckis', 'USA', NULL, '2026-05-14 19:42:00'),
(26, 'The Pianist', 'Biography, Drama', '2002', 150, 8.5, 'Roman Polanski', 'France/Poland', NULL, '2026-05-14 19:42:00'),
(27, 'Psycho', 'Horror, Mystery', '1960', 109, 8.5, 'Alfred Hitchcock', 'USA', NULL, '2026-05-14 19:42:00'),
(28, 'Gladiator', 'Action, Adventure', '2000', 155, 8.5, 'Ridley Scott', 'USA/UK', NULL, '2026-05-14 19:42:00'),
(29, 'The Lion King', 'Animation, Adventure', '1994', 88, 8.5, 'Roger Allers', 'USA', NULL, '2026-05-14 19:42:00'),
(30, 'The Departed', 'Crime, Drama', '2006', 151, 8.5, 'Martin Scorsese', 'USA', NULL, '2026-05-14 19:42:00');

-- --------------------------------------------------------

--
-- Table structure for table `korisnici`
--

CREATE TABLE `korisnici` (
  `id` int(11) NOT NULL,
  `korisnicko_ime` varchar(50) NOT NULL,
  `lozinka` varchar(255) NOT NULL,
  `email` varchar(100) NOT NULL,
  `uloga` enum('korisnik','admin') DEFAULT 'korisnik',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `korisnici`
--

INSERT INTO `korisnici` (`id`, `korisnicko_ime`, `lozinka`, `email`, `uloga`, `created_at`) VALUES
(1, 'admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin@example.com', 'admin', '2026-05-14 19:42:00'),
(2, 'romana', '$2y$10$fZMCw43B3RvI2DP1N7riOOYxpUcNxw9bKr2/hJfRH2C8azE0hLxi6', 'romanahorvat007@gmail.com', 'korisnik', '2026-05-15 06:47:44');

-- --------------------------------------------------------

--
-- Table structure for table `ocjene_filmova`
--

CREATE TABLE `ocjene_filmova` (
  `id` int(11) NOT NULL,
  `korisnik_id` int(11) NOT NULL,
  `film_id` int(11) NOT NULL,
  `ocjena` tinyint(4) NOT NULL,
  `vrijeme` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ;

-- --------------------------------------------------------

--
-- Table structure for table `ocjene_slike`
--

CREATE TABLE `ocjene_slike` (
  `id` int(11) NOT NULL,
  `korisnik_id` int(11) NOT NULL,
  `slika_id` int(11) NOT NULL,
  `ocjena` tinyint(4) NOT NULL,
  `vrijeme` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ;

--
-- Dumping data for table `ocjene_slike`
--

INSERT INTO `ocjene_slike` (`id`, `korisnik_id`, `slika_id`, `ocjena`, `vrijeme`) VALUES
(1, 2, 6, 5, '2026-05-15 06:47:59'),
(2, 2, 7, 5, '2026-05-15 06:48:00'),
(3, 2, 5, 5, '2026-05-15 06:48:01'),
(4, 2, 4, 5, '2026-05-15 06:48:02'),
(5, 2, 3, 5, '2026-05-15 06:48:03'),
(6, 2, 2, 3, '2026-05-15 06:48:05'),
(7, 1, 7, 3, '2026-05-15 06:48:29'),
(8, 1, 6, 4, '2026-05-15 06:48:30'),
(9, 1, 5, 2, '2026-05-15 06:48:31'),
(10, 1, 4, 1, '2026-05-15 06:48:33'),
(11, 1, 3, 5, '2026-05-15 06:48:34'),
(12, 1, 2, 4, '2026-05-15 06:48:35');

-- --------------------------------------------------------

--
-- Table structure for table `slike`
--

CREATE TABLE `slike` (
  `id` int(11) NOT NULL,
  `naziv` varchar(255) NOT NULL,
  `opis` text DEFAULT NULL,
  `putanja` varchar(500) NOT NULL,
  `izvor` enum('lokalno','api') DEFAULT 'lokalno',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `slike`
--

INSERT INTO `slike` (`id`, `naziv`, `opis`, `putanja`, `izvor`, `created_at`) VALUES
(2, 'Slika 6a06b88c485a2', NULL, 'images/gallery/slika_6a06b88c485a2.jpg', 'lokalno', '2026-05-15 06:44:15'),
(3, 'Photo1', NULL, 'images/gallery/photo1.jpg', 'lokalno', '2026-05-15 06:45:48'),
(4, 'Photo2', NULL, 'images/gallery/photo2.jpg', 'lokalno', '2026-05-15 06:45:48'),
(5, 'Photo3', NULL, 'images/gallery/photo3.jpg', 'lokalno', '2026-05-15 06:45:48'),
(6, 'Photo4', NULL, 'images/gallery/photo4.jpg', 'lokalno', '2026-05-15 06:45:48'),
(7, 'Photo5', NULL, 'images/gallery/photo5.jpg', 'lokalno', '2026-05-15 06:45:48');

-- --------------------------------------------------------

--
-- Table structure for table `zeljeni_filmovi`
--

CREATE TABLE `zeljeni_filmovi` (
  `id` int(11) NOT NULL,
  `korisnik_id` int(11) NOT NULL,
  `film_id` int(11) NOT NULL,
  `dodano` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `filmovi`
--
ALTER TABLE `filmovi`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `korisnici`
--
ALTER TABLE `korisnici`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `korisnicko_ime` (`korisnicko_ime`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `ocjene_filmova`
--
ALTER TABLE `ocjene_filmova`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_ocjena` (`korisnik_id`,`film_id`),
  ADD KEY `film_id` (`film_id`);

--
-- Indexes for table `ocjene_slike`
--
ALTER TABLE `ocjene_slike`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_ocjena_slike` (`korisnik_id`,`slika_id`),
  ADD KEY `slika_id` (`slika_id`);

--
-- Indexes for table `slike`
--
ALTER TABLE `slike`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `zeljeni_filmovi`
--
ALTER TABLE `zeljeni_filmovi`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_korisnik_film` (`korisnik_id`,`film_id`),
  ADD KEY `film_id` (`film_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `filmovi`
--
ALTER TABLE `filmovi`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `korisnici`
--
ALTER TABLE `korisnici`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `ocjene_filmova`
--
ALTER TABLE `ocjene_filmova`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `ocjene_slike`
--
ALTER TABLE `ocjene_slike`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `slike`
--
ALTER TABLE `slike`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `zeljeni_filmovi`
--
ALTER TABLE `zeljeni_filmovi`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `ocjene_filmova`
--
ALTER TABLE `ocjene_filmova`
  ADD CONSTRAINT `ocjene_filmova_ibfk_1` FOREIGN KEY (`korisnik_id`) REFERENCES `korisnici` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `ocjene_filmova_ibfk_2` FOREIGN KEY (`film_id`) REFERENCES `filmovi` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `ocjene_slike`
--
ALTER TABLE `ocjene_slike`
  ADD CONSTRAINT `ocjene_slike_ibfk_1` FOREIGN KEY (`korisnik_id`) REFERENCES `korisnici` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `ocjene_slike_ibfk_2` FOREIGN KEY (`slika_id`) REFERENCES `slike` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `zeljeni_filmovi`
--
ALTER TABLE `zeljeni_filmovi`
  ADD CONSTRAINT `zeljeni_filmovi_ibfk_1` FOREIGN KEY (`korisnik_id`) REFERENCES `korisnici` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `zeljeni_filmovi_ibfk_2` FOREIGN KEY (`film_id`) REFERENCES `filmovi` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
