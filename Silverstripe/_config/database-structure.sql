SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;


-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `gdhc_conversations`
--

CREATE TABLE IF NOT EXISTS `gdhc_conversations` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT COMMENT 'Conversation ID - Primary Key',
  `participantOne` bigint(20) NOT NULL COMMENT 'Session ID of First Participant in Conversation ',
  `participantTwo` bigint(20) DEFAULT NULL COMMENT 'Session ID of Second Participant in Conversation ',
  `participantOneTyping` enum('Y','N') NOT NULL DEFAULT 'N',
  `participantTwoTyping` enum('Y','N') NOT NULL DEFAULT 'N',
  `done` enum('Y','N') NOT NULL DEFAULT 'N',
  `created` datetime NOT NULL COMMENT 'Time Created',
  PRIMARY KEY (`id`),
  KEY `sessionuserid1_fk` (`participantOne`),
  KEY `sessionuserid2_fk` (`participantTwo`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `gdhc_messages`
--

CREATE TABLE IF NOT EXISTS `gdhc_messages` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT COMMENT 'Message ID - Primary Key',
  `name` varchar(100) DEFAULT NULL COMMENT 'Messenger Name',
  `email` varchar(100) NOT NULL COMMENT 'Messenger Email',
  `content` mediumtext NOT NULL COMMENT 'Message Body',
  `created` datetime NOT NULL COMMENT 'Time Message Created',
  `userId` bigint(20) NOT NULL COMMENT 'User ID Owner of Message',
  `conversationId` bigint(20) NOT NULL COMMENT 'Conversation ID Conversation of Message',
  PRIMARY KEY (`id`),
  KEY `messageuserid_fk` (`userId`),
  KEY `conversationid_fk` (`conversationId`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `gdhc_sessions`
--

CREATE TABLE IF NOT EXISTS `gdhc_sessions` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT COMMENT 'Session ID',
  `userId` bigint(20) NOT NULL COMMENT 'User ID',
  `accesstoken` varchar(100) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL COMMENT 'Access Token',
  `accesstokenexpiry` datetime NOT NULL COMMENT 'Access Token Expiry Date/Time',
  `refreshtoken` varchar(100) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL COMMENT 'Refresh Token',
  `refreshtokenexpiry` datetime NOT NULL COMMENT 'Refresh Token Expiry Date/Time',
  PRIMARY KEY (`id`),
  UNIQUE KEY `accesstoken` (`accesstoken`),
  UNIQUE KEY `refreshtoken` (`refreshtoken`),
  KEY `sessionuserid_fk` (`userId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Acces Tokken';

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `gdhc_settings`
--

CREATE TABLE IF NOT EXISTS `gdhc_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(254) NOT NULL,
  `value` text,
  `category` varchar(254) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `gdhc_users`
--

CREATE TABLE IF NOT EXISTS `gdhc_users` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT COMMENT 'User Id',
  `fullname` varchar(100) NOT NULL COMMENT 'Users Full Name',
  `username` varchar(255) NOT NULL COMMENT 'Users username',
  `password` varchar(255) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL COMMENT 'Users password',
  `useractive` enum('N','Y') NOT NULL DEFAULT 'Y' COMMENT 'Is user active',
  `loginattempts` int(1) NOT NULL,
  `role` enum('GEBRUIKER','ADMIN') NOT NULL DEFAULT 'GEBRUIKER' COMMENT 'User''s Role',
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Users table';


/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
