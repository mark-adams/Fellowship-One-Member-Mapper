-- Generation Time: Nov 08, 2010 at 05:04 AM
-- Server version: 5.1.39
-- PHP Version: 5.2.14

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";

--
-- Database: `interviewapp`
--

-- --------------------------------------------------------

--
-- Table structure for table `GeocodeCache`
--

CREATE TABLE IF NOT EXISTS `GeocodeCache` (
  `hash` char(40) NOT NULL,
  `lat` decimal(10,7) NOT NULL,
  `lng` decimal(10,7) NOT NULL,
  PRIMARY KEY (`hash`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
