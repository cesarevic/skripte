-- MySQL dump 10.11
--
-- Host: localhost    Database: pingback
-- ------------------------------------------------------
-- Server version	5.0.77

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `domain`
--

DROP TABLE IF EXISTS `domain`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `domain` (
  `id` int(11) NOT NULL auto_increment,
  `domain` varchar(128) NOT NULL,
  `pingback_count` mediumint(9) NOT NULL default '0',
  `pingback_error_count` tinyint(4) NOT NULL default '0',
  `lock_pid` mediumint(9) NOT NULL default '0',
  `lock_timestamp` int(10) NOT NULL default '0',
  `active` tinyint(4) NOT NULL default '1',
  `parse_error_count` tinyint(4) NOT NULL default '0',
  PRIMARY KEY  (`id`),
  UNIQUE KEY `domain` (`domain`)
) ENGINE=MyISAM AUTO_INCREMENT=4663 DEFAULT CHARSET=utf8;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `keyword`
--

DROP TABLE IF EXISTS `keyword`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `keyword` (
  `id` int(11) NOT NULL auto_increment,
  `keyword` varchar(128) NOT NULL default '',
  PRIMARY KEY  (`id`),
  UNIQUE KEY `keyword` (`keyword`(64))
) ENGINE=MyISAM AUTO_INCREMENT=91 DEFAULT CHARSET=utf8;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `keyword_url`
--

DROP TABLE IF EXISTS `keyword_url`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `keyword_url` (
  `id` int(11) NOT NULL auto_increment,
  `keyword_id` int(11) NOT NULL,
  `url_id` int(11) NOT NULL,
  PRIMARY KEY  (`id`),
  UNIQUE KEY `keyword_url_key` (`keyword_id`,`url_id`)
) ENGINE=MyISAM AUTO_INCREMENT=727 DEFAULT CHARSET=utf8;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `pingback_url`
--

DROP TABLE IF EXISTS `pingback_url`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `pingback_url` (
  `id` int(11) NOT NULL auto_increment,
  `domain_id` int(11) NOT NULL,
  `url` varchar(256) NOT NULL,
  PRIMARY KEY  (`id`),
  UNIQUE KEY `url` (`url`)
) ENGINE=MyISAM AUTO_INCREMENT=541 DEFAULT CHARSET=utf8;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `url`
--

DROP TABLE IF EXISTS `url`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `url` (
  `id` int(11) NOT NULL auto_increment,
  `domain_id` int(11) NOT NULL,
  `url` varchar(256) NOT NULL default '',
  `pingback_url_id` int(11) NOT NULL,
  `pingback_count` mediumint(9) NOT NULL default '0',
  `pingback_error_count` tinyint(4) NOT NULL default '0',
  PRIMARY KEY  (`id`),
  UNIQUE KEY `url` (`url`)
) ENGINE=MyISAM AUTO_INCREMENT=642 DEFAULT CHARSET=utf8;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `xml_data`
--

DROP TABLE IF EXISTS `xml_data`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `xml_data` (
  `id` int(11) NOT NULL auto_increment,
  `url_id` int(11) NOT NULL,
  `xml` blob NOT NULL,
  PRIMARY KEY  (`id`),
  UNIQUE KEY `url_id` (`url_id`)
) ENGINE=MyISAM AUTO_INCREMENT=642 DEFAULT CHARSET=utf8;
SET character_set_client = @saved_cs_client;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2010-10-28  9:27:44
