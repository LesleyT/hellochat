SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;


--
-- Beperkingen voor geëxporteerde tabellen
--

--
-- Beperkingen voor tabel `gdhc_conversations`
--
ALTER TABLE `gdhc_conversations`
  ADD CONSTRAINT `sessionuserid1_fk` FOREIGN KEY (`participantOne`) REFERENCES `gdhc_sessions` (`id`),
  ADD CONSTRAINT `sessionuserid2_fk` FOREIGN KEY (`participantTwo`) REFERENCES `gdhc_sessions` (`id`);

--
-- Beperkingen voor tabel `gdhc_messages`
--
ALTER TABLE `gdhc_messages`
  ADD CONSTRAINT `conversationid_fk` FOREIGN KEY (`conversationId`) REFERENCES `gdhc_conversations` (`id`),
  ADD CONSTRAINT `messageuserid_fk` FOREIGN KEY (`userId`) REFERENCES `gdhc_users` (`id`);

--
-- Beperkingen voor tabel `gdhc_sessions`
--
ALTER TABLE `gdhc_sessions`
  ADD CONSTRAINT `sessionuserid_fk` FOREIGN KEY (`userId`) REFERENCES `gdhc_users` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
