-- MariaDB dump 10.19  Distrib 10.4.32-MariaDB, for Win64 (AMD64)
--
-- Host: localhost    Database: greenerry
-- ------------------------------------------------------
-- Server version	10.4.32-MariaDB

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Current Database: `greenerry`
--

CREATE DATABASE /*!32312 IF NOT EXISTS*/ `greenerry` /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci */;

USE `greenerry`;

--
-- Table structure for table `admin`
--

DROP TABLE IF EXISTS `admin`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `admin` (
  `idAdmin` int(11) NOT NULL AUTO_INCREMENT,
  `nome` varchar(120) NOT NULL,
  `email` varchar(150) NOT NULL,
  `palavra_passe` varchar(255) NOT NULL,
  `cargo` varchar(80) DEFAULT 'Administrador',
  `ativo` tinyint(1) NOT NULL DEFAULT 1,
  `ultimo_login` datetime DEFAULT NULL,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  `atualizado_em` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`idAdmin`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `admin`
--

LOCK TABLES `admin` WRITE;
/*!40000 ALTER TABLE `admin` DISABLE KEYS */;
INSERT INTO `admin` VALUES (1,'Admin Principal','greenerry333@gmail.com','$2y$10$RzXscm.n8HmnKofQ1i1MJ.uJ6tuTIZQuMOuFWWaLJ4DFHoqp04EKq','Administrador Principal',1,'2026-06-05 13:48:47','2026-05-26 21:40:46','2026-06-05 12:48:47'),(2,'Catálogo Admin ','catalogo.admin@greenerry.com','$2y$10$ETaWdm2KLTAQSCPIjpgRO.XJ13zYxYm5ufCYSxoFlSwN2t0x2.PUS','Produtos',1,'2026-06-04 15:13:04','2026-05-27 20:17:38','2026-06-04 14:13:04'),(3,'Música Admin ','musica.admin@greenerry.com','$2y$10$ETaWdm2KLTAQSCPIjpgRO.XJ13zYxYm5ufCYSxoFlSwN2t0x2.PUS','Musica',1,NULL,'2026-05-27 20:17:38','2026-05-28 00:37:22'),(4,'Suporte Admin','suporte.admin@greenerry.com','$2y$10$ETaWdm2KLTAQSCPIjpgRO.XJ13zYxYm5ufCYSxoFlSwN2t0x2.PUS','Suporte',1,NULL,'2026-05-27 20:17:38','2026-05-28 00:37:33'),(5,'Relatórios Admin ','relatorios.admin@greenerry.com','$2y$10$ETaWdm2KLTAQSCPIjpgRO.XJ13zYxYm5ufCYSxoFlSwN2t0x2.PUS','Relatorios',1,NULL,'2026-05-27 20:17:38','2026-05-28 00:37:46');
/*!40000 ALTER TABLE `admin` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `categoria`
--

DROP TABLE IF EXISTS `categoria`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `categoria` (
  `idCategoria` int(11) NOT NULL AUTO_INCREMENT,
  `nomeCategoria` varchar(100) NOT NULL,
  `descricaoCategoria` text DEFAULT NULL,
  `usa_tamanhos` tinyint(1) NOT NULL DEFAULT 0,
  `estado` enum('ativo','inativo') NOT NULL DEFAULT 'ativo',
  `idAdminCriador` int(11) DEFAULT NULL,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  `atualizado_em` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`idCategoria`),
  UNIQUE KEY `nomeCategoria` (`nomeCategoria`),
  KEY `fk_categoria_admin` (`idAdminCriador`),
  CONSTRAINT `fk_categoria_admin` FOREIGN KEY (`idAdminCriador`) REFERENCES `admin` (`idAdmin`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `categoria`
--

LOCK TABLES `categoria` WRITE;
/*!40000 ALTER TABLE `categoria` DISABLE KEYS */;
INSERT INTO `categoria` VALUES (1,'T-Shirt','T-shirts oficiais dos artistas.',1,'ativo',1,'2026-05-26 21:40:46','2026-05-26 21:40:46'),(2,'Hoodie','Sweatshirts e hoodies de merchandising.',1,'ativo',1,'2026-05-26 21:40:46','2026-05-26 21:40:46'),(3,'Vinil','Edicoes em vinil para colecao.',0,'ativo',1,'2026-05-26 21:40:46','2026-05-26 21:40:46'),(4,'CD','Edicoes fisicas em CD.',0,'ativo',1,'2026-05-26 21:40:46','2026-05-26 21:40:46'),(5,'Poster','Posters e material visual promocional.',1,'ativo',1,'2026-05-26 21:40:46','2026-05-26 21:40:46'),(6,'Acessorio','Acessorios como sacos, pins e outros artigos.',0,'ativo',1,'2026-05-26 21:40:46','2026-05-26 21:40:46');
/*!40000 ALTER TABLE `categoria` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cliente`
--

DROP TABLE IF EXISTS `cliente`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cliente` (
  `idCliente` int(11) NOT NULL AUTO_INCREMENT,
  `nome` varchar(120) NOT NULL,
  `email` varchar(150) NOT NULL,
  `palavra_passe` varchar(255) NOT NULL,
  `telefone` varchar(30) DEFAULT NULL,
  `foto` varchar(255) DEFAULT NULL,
  `banner` varchar(255) DEFAULT NULL,
  `bio` text DEFAULT NULL,
  `slug` varchar(160) DEFAULT NULL,
  `estado` enum('ativo','inativo','bloqueado') NOT NULL DEFAULT 'ativo',
  `ultimo_login` datetime DEFAULT NULL,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  `atualizado_em` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`idCliente`),
  UNIQUE KEY `email` (`email`),
  UNIQUE KEY `slug` (`slug`),
  KEY `idx_cliente_estado` (`estado`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cliente`
--

LOCK TABLES `cliente` WRITE;
/*!40000 ALTER TABLE `cliente` DISABLE KEYS */;
INSERT INTO `cliente` VALUES (2,'Srijan Gautam','ygautam288@gmail.com','$2y$10$gh5tXkJ3BUA5vFlVurgXK.RbiNkDTbLqKiRbypa5jS01SFbufnwZm','926275311','avatar_2_1742c86c41fd5281.jpg','banner_2_1fb62ed61e2a1763.jpg','Tenho o melhor gosto musical.','srijan-gautam','ativo','2026-06-05 12:30:10','2026-05-26 22:26:11','2026-06-05 13:01:17'),(3,'Sneha Gurung','ser00ena@gmail.com','$2y$10$P3xi5uxawx9nFT5UnafDgu2z8/y4duIrU2saNbB5K6jW7Jtlfmd0.','920000000','avatar_3_314658644302f9a2.jpg','banner_3_ddb36b55411f1812.jpg','Aqui para espalhar boas energias.','sneha-gurung','ativo','2026-05-27 11:05:09','2026-05-26 22:26:37','2026-06-05 13:01:17'),(4,'Green','srijangautm5318@gmail.com','$2y$10$H1Rza4l2S8HCmFpJ/Nnb5ONkB/bE1JYWl0Nk.2iyf3IXp/AIC9cqu',NULL,NULL,NULL,NULL,'green','inativo',NULL,'2026-05-26 22:30:37','2026-05-26 22:30:37'),(5,'Green','srijangautam5318@gmail.com','$2y$10$mRsE0D7XWjUJA3zF/VsGbuxGyvlLhPCGxfTTXGCqsc92y84sKYqd6','999999999','avatar_5_20a6005bd19eafe7.jpg','banner_5_a40841a2d6aa1c36.jpg','A relaxar e a criar.','green-2','ativo','2026-06-05 00:40:22','2026-05-26 22:32:35','2026-06-05 13:01:17');
/*!40000 ALTER TABLE `cliente` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `configuracao_site`
--

DROP TABLE IF EXISTS `configuracao_site`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `configuracao_site` (
  `chave_configuracao` varchar(80) NOT NULL,
  `valor_configuracao` text DEFAULT NULL,
  `atualizado_em` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`chave_configuracao`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `configuracao_site`
--

LOCK TABLES `configuracao_site` WRITE;
/*!40000 ALTER TABLE `configuracao_site` DISABLE KEYS */;
INSERT INTO `configuracao_site` VALUES ('commission_percent','5','2026-05-26 21:40:46'),('contact_email','greenerry333@gmail.com','2026-05-26 22:24:47'),('contact_phone','+351 926275311','2026-05-26 22:24:47'),('email_enabled','1','2026-05-26 22:24:47'),('featured_artist_id','3','2026-06-04 14:06:33'),('featured_product_id','6','2026-06-03 17:59:45'),('featured_release_id','25','2026-06-03 17:59:45'),('footer_note','','2026-05-26 21:40:46'),('instagram_url','#','2026-05-26 21:40:46'),('maintenance_pages','','2026-06-04 22:03:13'),('shipping_note','Suporte digital e merch geridos pela equipa Greenerry.','2026-06-05 13:01:17'),('site_name','Greenerry','2026-05-26 21:40:46'),('smtp_host','smtp.gmail.com','2026-05-26 22:24:47'),('smtp_password','','2026-05-26 22:24:47'),('smtp_port','587','2026-05-26 22:24:47'),('smtp_secure','tls','2026-05-26 22:24:47'),('smtp_username','greenerry333@gmail.com','2026-05-26 22:24:47'),('x_url','#','2026-05-26 21:40:46');
/*!40000 ALTER TABLE `configuracao_site` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `encomenda`
--

DROP TABLE IF EXISTS `encomenda`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `encomenda` (
  `idEncomenda` int(11) NOT NULL AUTO_INCREMENT,
  `idCliente` int(11) NOT NULL,
  `subtotal` decimal(10,2) NOT NULL DEFAULT 0.00,
  `iva_total` decimal(10,2) NOT NULL DEFAULT 0.00,
  `comissao_total` decimal(10,2) NOT NULL DEFAULT 0.00,
  `total_final` decimal(10,2) NOT NULL DEFAULT 0.00,
  `estado_encomenda` enum('pendente','em_preparacao','enviada','entregue','cancelada') NOT NULL DEFAULT 'pendente',
  `estado_pagamento` enum('pendente','pago','falhado','reembolsado') NOT NULL DEFAULT 'pendente',
  `metodo_pagamento` enum('cartao','mbway','transferencia') NOT NULL DEFAULT 'cartao',
  `nif` varchar(20) DEFAULT NULL,
  `observacoes` text DEFAULT NULL,
  `transportadora` varchar(120) DEFAULT NULL,
  `tracking_number` varchar(120) DEFAULT NULL,
  `tracking_url` varchar(255) DEFAULT NULL,
  `pago_em` datetime DEFAULT NULL,
  `enviado_em` datetime DEFAULT NULL,
  `entregue_em` datetime DEFAULT NULL,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  `atualizado_em` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`idEncomenda`),
  KEY `idx_encomenda_cliente_estado` (`idCliente`,`estado_encomenda`),
  CONSTRAINT `fk_encomenda_cliente` FOREIGN KEY (`idCliente`) REFERENCES `cliente` (`idCliente`)
) ENGINE=InnoDB AUTO_INCREMENT=50 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `encomenda`
--

LOCK TABLES `encomenda` WRITE;
/*!40000 ALTER TABLE `encomenda` DISABLE KEYS */;
INSERT INTO `encomenda` VALUES (1,2,99.97,22.99,6.15,122.96,'entregue','pago','cartao','245678901','[DEMO_GRAPH_SEED_2026] Demo purchase for PAP dashboard graphs',NULL,NULL,NULL,NULL,NULL,NULL,'2025-12-28 15:52:19','2025-12-28 15:52:19'),(2,2,129.97,29.89,7.99,159.86,'entregue','pago','mbway','245678901','[DEMO_GRAPH_SEED_2026] Demo purchase for PAP dashboard graphs',NULL,NULL,NULL,NULL,NULL,NULL,'2026-01-15 16:52:19','2026-01-15 16:52:19'),(3,2,129.96,29.89,7.99,159.85,'entregue','pago','cartao','245678901','[DEMO_GRAPH_SEED_2026] Demo purchase for PAP dashboard graphs',NULL,NULL,NULL,NULL,NULL,NULL,'2026-01-29 17:52:19','2026-01-29 17:52:19'),(4,2,69.97,16.09,4.30,86.06,'em_preparacao','pago','cartao','245678901','[DEMO_GRAPH_SEED_2026] Demo purchase for PAP dashboard graphs',NULL,NULL,NULL,NULL,NULL,NULL,'2026-02-20 18:52:19','2026-02-20 18:52:19'),(5,2,209.97,48.29,12.91,258.26,'enviada','pago','mbway','245678901','[DEMO_GRAPH_SEED_2026] Demo purchase for PAP dashboard graphs',NULL,NULL,NULL,NULL,NULL,NULL,'2026-03-06 19:52:19','2026-03-06 19:52:19'),(6,2,149.96,34.49,9.22,184.45,'pendente','pendente','transferencia','245678901','[DEMO_GRAPH_SEED_2026] Demo purchase for PAP dashboard graphs',NULL,NULL,NULL,NULL,NULL,NULL,'2026-03-25 20:52:19','2026-03-25 20:52:19'),(7,2,199.97,45.99,12.30,245.96,'cancelada','reembolsado','cartao','245678901','[DEMO_GRAPH_SEED_2026] Demo purchase for PAP dashboard graphs',NULL,NULL,NULL,NULL,NULL,NULL,'2026-04-12 20:52:19','2026-04-12 20:52:19'),(8,2,99.97,22.99,6.15,122.96,'entregue','pago','mbway','245678901','[DEMO_GRAPH_SEED_2026] Demo purchase for PAP dashboard graphs',NULL,NULL,NULL,NULL,NULL,NULL,'2026-04-26 21:52:19','2026-04-26 21:52:19'),(9,2,109.96,25.29,6.76,135.25,'em_preparacao','pago','transferencia','245678901','[DEMO_GRAPH_SEED_2026] Demo purchase for PAP dashboard graphs',NULL,NULL,NULL,NULL,NULL,NULL,'2026-05-09 22:52:19','2026-05-09 22:52:19'),(10,2,149.97,34.49,9.22,184.46,'enviada','pago','cartao','245678901','[DEMO_GRAPH_SEED_2026] Demo purchase for PAP dashboard graphs',NULL,NULL,NULL,NULL,NULL,NULL,'2026-05-20 23:52:19','2026-05-20 23:52:19'),(11,3,79.96,18.39,4.92,98.35,'entregue','pago','mbway','246789012','[DEMO_GRAPH_SEED_2026] Demo purchase for PAP dashboard graphs',NULL,NULL,NULL,NULL,NULL,NULL,'2025-12-28 22:52:19','2025-12-28 22:52:19'),(12,3,159.96,36.79,9.84,196.75,'entregue','pago','cartao','246789012','[DEMO_GRAPH_SEED_2026] Demo purchase for PAP dashboard graphs',NULL,NULL,NULL,NULL,NULL,NULL,'2026-01-15 23:52:19','2026-01-15 23:52:19'),(13,3,69.98,16.10,4.30,86.08,'em_preparacao','pago','cartao','246789012','[DEMO_GRAPH_SEED_2026] Demo purchase for PAP dashboard graphs',NULL,NULL,NULL,NULL,NULL,NULL,'2026-01-30 00:52:19','2026-01-30 00:52:19'),(14,3,99.96,22.99,6.15,122.95,'enviada','pago','mbway','246789012','[DEMO_GRAPH_SEED_2026] Demo purchase for PAP dashboard graphs',NULL,NULL,NULL,NULL,NULL,NULL,'2026-02-21 01:52:19','2026-02-21 01:52:19'),(15,3,299.96,68.99,18.45,368.95,'pendente','pendente','transferencia','246789012','[DEMO_GRAPH_SEED_2026] Demo purchase for PAP dashboard graphs',NULL,NULL,NULL,NULL,NULL,NULL,'2026-03-07 02:52:19','2026-03-07 02:52:19'),(16,3,39.98,9.20,2.46,49.18,'cancelada','reembolsado','cartao','246789012','[DEMO_GRAPH_SEED_2026] Demo purchase for PAP dashboard graphs',NULL,NULL,NULL,NULL,NULL,NULL,'2026-03-26 03:52:19','2026-03-26 03:52:19'),(17,3,159.96,36.79,9.84,196.75,'entregue','pago','mbway','246789012','[DEMO_GRAPH_SEED_2026] Demo purchase for PAP dashboard graphs',NULL,NULL,NULL,NULL,NULL,NULL,'2026-04-13 03:52:19','2026-04-13 03:52:19'),(18,3,129.96,29.89,7.99,159.85,'em_preparacao','pago','transferencia','246789012','[DEMO_GRAPH_SEED_2026] Demo purchase for PAP dashboard graphs',NULL,NULL,NULL,NULL,NULL,NULL,'2026-04-27 04:52:19','2026-04-27 04:52:19'),(19,3,49.98,11.50,3.07,61.48,'enviada','pago','cartao','246789012','[DEMO_GRAPH_SEED_2026] Demo purchase for PAP dashboard graphs',NULL,NULL,NULL,NULL,NULL,NULL,'2026-05-10 05:52:19','2026-05-10 05:52:19'),(20,3,239.96,55.19,14.76,295.15,'entregue','pago','cartao','246789012','[DEMO_GRAPH_SEED_2026] Demo purchase for PAP dashboard graphs',NULL,NULL,NULL,NULL,NULL,NULL,'2026-05-21 06:52:19','2026-05-21 06:52:19'),(21,5,99.95,22.99,6.15,122.94,'entregue','pago','cartao','247890123','[DEMO_GRAPH_SEED_2026] Demo purchase for PAP dashboard graphs',NULL,NULL,NULL,NULL,NULL,NULL,'2025-12-29 05:52:19','2025-12-29 05:52:19'),(22,5,79.98,18.40,4.92,98.38,'em_preparacao','pago','cartao','247890123','[DEMO_GRAPH_SEED_2026] Demo purchase for PAP dashboard graphs',NULL,NULL,NULL,NULL,NULL,NULL,'2026-01-16 06:52:19','2026-01-16 06:52:19'),(23,5,99.97,22.99,6.15,122.96,'enviada','pago','mbway','247890123','[DEMO_GRAPH_SEED_2026] Demo purchase for PAP dashboard graphs',NULL,NULL,NULL,NULL,NULL,NULL,'2026-01-30 07:52:19','2026-01-30 07:52:19'),(24,5,119.95,27.59,7.38,147.54,'cancelada','reembolsado','transferencia','247890123','[DEMO_GRAPH_SEED_2026] Demo purchase for PAP dashboard graphs',NULL,NULL,NULL,NULL,NULL,NULL,'2026-02-21 08:52:19','2026-06-04 23:39:48'),(25,5,69.98,16.10,4.30,86.08,'cancelada','reembolsado','cartao','247890123','[DEMO_GRAPH_SEED_2026] Demo purchase for PAP dashboard graphs',NULL,NULL,NULL,NULL,NULL,NULL,'2026-03-07 09:52:19','2026-03-07 09:52:19'),(26,5,59.97,13.79,3.69,73.76,'entregue','pago','mbway','247890123','[DEMO_GRAPH_SEED_2026] Demo purchase for PAP dashboard graphs',NULL,NULL,NULL,NULL,NULL,NULL,'2026-03-26 10:52:19','2026-03-26 10:52:19'),(27,5,139.95,32.19,8.61,172.14,'em_preparacao','pago','transferencia','247890123','[DEMO_GRAPH_SEED_2026] Demo purchase for PAP dashboard graphs',NULL,NULL,NULL,NULL,NULL,NULL,'2026-04-13 10:52:19','2026-04-13 10:52:19'),(28,5,69.98,16.10,4.30,86.08,'enviada','pago','cartao','247890123','[DEMO_GRAPH_SEED_2026] Demo purchase for PAP dashboard graphs',NULL,NULL,NULL,NULL,NULL,NULL,'2026-04-27 11:52:19','2026-04-27 11:52:19'),(29,5,99.97,22.99,6.15,122.96,'entregue','pago','cartao','247890123','[DEMO_GRAPH_SEED_2026] Demo purchase for PAP dashboard graphs',NULL,NULL,NULL,NULL,NULL,NULL,'2026-05-10 12:52:19','2026-05-10 12:52:19'),(30,5,169.95,39.09,10.45,209.04,'entregue','pago','mbway','247890123','[DEMO_GRAPH_SEED_2026] Demo purchase for PAP dashboard graphs',NULL,NULL,NULL,NULL,NULL,NULL,'2026-05-21 13:52:19','2026-05-21 13:52:19'),(31,3,19.99,4.60,1.00,24.59,'entregue','pago','cartao',NULL,NULL,NULL,NULL,NULL,'2026-05-21 13:43:08','2026-05-21 13:43:08','2026-05-21 13:43:08','2026-05-21 12:43:08','2026-05-21 12:43:08'),(32,3,19.99,4.60,1.00,24.59,'entregue','pago','cartao',NULL,NULL,NULL,NULL,NULL,'2026-05-20 13:43:08','2026-05-20 13:43:08','2026-05-20 13:43:08','2026-05-20 12:43:08','2026-05-20 12:43:08'),(33,3,39.99,9.20,2.00,49.19,'entregue','pago','cartao',NULL,NULL,NULL,NULL,NULL,'2026-05-19 13:43:08','2026-05-19 13:43:08','2026-05-19 13:43:08','2026-05-19 12:43:08','2026-05-19 12:43:08'),(34,3,39.99,9.20,2.00,49.19,'entregue','pago','cartao',NULL,NULL,NULL,NULL,NULL,'2026-05-18 13:43:08','2026-05-18 13:43:08','2026-05-18 13:43:08','2026-05-18 12:43:08','2026-05-18 12:43:08'),(35,3,29.99,6.90,1.50,36.89,'entregue','pago','cartao',NULL,NULL,NULL,NULL,NULL,'2026-05-17 13:43:08','2026-05-17 13:43:08','2026-05-17 13:43:08','2026-05-17 12:43:08','2026-05-17 12:43:08'),(36,3,39.99,9.20,2.00,49.19,'entregue','pago','cartao',NULL,NULL,NULL,NULL,NULL,'2026-05-16 13:43:08','2026-05-16 13:43:08','2026-05-16 13:43:08','2026-05-16 12:43:08','2026-05-16 12:43:08'),(37,3,19.99,4.60,1.00,24.59,'entregue','pago','cartao',NULL,NULL,NULL,NULL,NULL,'2026-05-15 13:43:08','2026-05-15 13:43:08','2026-05-15 13:43:08','2026-05-15 12:43:08','2026-05-15 12:43:08'),(38,3,29.99,6.90,1.50,36.89,'entregue','pago','cartao',NULL,NULL,NULL,NULL,NULL,'2026-05-14 13:43:08','2026-05-14 13:43:08','2026-05-14 13:43:08','2026-05-14 12:43:08','2026-05-14 12:43:08'),(39,2,39.99,9.20,2.00,49.19,'entregue','pago','cartao',NULL,NULL,NULL,NULL,NULL,'2026-05-13 13:43:08','2026-05-13 13:43:08','2026-05-13 13:43:08','2026-05-13 12:43:08','2026-05-13 12:43:08'),(40,2,29.99,6.90,1.50,36.89,'entregue','pago','cartao',NULL,NULL,NULL,NULL,NULL,'2026-05-12 13:43:08','2026-05-12 13:43:08','2026-05-12 13:43:08','2026-05-12 12:43:08','2026-05-12 12:43:08'),(41,2,19.99,4.60,1.00,24.59,'entregue','pago','cartao',NULL,NULL,NULL,NULL,NULL,'2026-05-11 13:43:08','2026-05-11 13:43:08','2026-05-11 13:43:08','2026-05-11 12:43:08','2026-05-11 12:43:08'),(42,2,89.99,20.70,4.50,110.69,'entregue','pago','cartao',NULL,NULL,NULL,NULL,NULL,'2026-05-10 13:43:08','2026-05-10 13:43:08','2026-05-10 13:43:08','2026-05-10 12:43:08','2026-05-10 12:43:08'),(43,2,29.99,6.90,1.50,36.89,'entregue','pago','cartao',NULL,NULL,NULL,NULL,NULL,'2026-05-09 13:43:08','2026-05-09 13:43:08','2026-05-09 13:43:08','2026-05-09 12:43:08','2026-05-09 12:43:08'),(44,2,24.99,5.75,1.25,30.74,'entregue','pago','cartao',NULL,NULL,NULL,NULL,NULL,'2026-05-08 13:43:08','2026-05-08 13:43:08','2026-05-08 13:43:08','2026-05-08 12:43:08','2026-05-08 12:43:08'),(45,2,19.99,4.60,1.00,24.59,'entregue','pago','cartao',NULL,NULL,NULL,NULL,NULL,'2026-05-07 13:43:08','2026-05-07 13:43:08','2026-05-07 13:43:08','2026-05-07 12:43:08','2026-05-07 12:43:08'),(46,2,14.99,3.45,0.75,18.44,'entregue','pago','cartao',NULL,NULL,NULL,NULL,NULL,'2026-05-06 13:43:08','2026-05-06 13:43:08','2026-05-06 13:43:08','2026-05-06 12:43:08','2026-05-06 12:43:08'),(47,2,614.69,141.38,37.80,756.07,'pendente','pago','cartao',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2026-06-04 14:19:41','2026-06-04 14:19:41'),(48,2,59.98,13.80,3.69,73.78,'pendente','pago','cartao','999999999',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2026-06-04 23:13:50','2026-06-04 23:13:50'),(49,2,39.98,9.20,2.46,49.18,'pendente','pago','cartao','999999999',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2026-06-04 23:37:28','2026-06-04 23:37:28');
/*!40000 ALTER TABLE `encomenda` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `encomenda_item`
--

DROP TABLE IF EXISTS `encomenda_item`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `encomenda_item` (
  `idEncomendaItem` int(11) NOT NULL AUTO_INCREMENT,
  `idEncomenda` int(11) NOT NULL,
  `idProduto` int(11) NOT NULL,
  `idArtista` int(11) NOT NULL,
  `idTamanho` int(11) DEFAULT NULL,
  `nome_produto` varchar(150) NOT NULL,
  `categoria_nome` varchar(100) DEFAULT NULL,
  `quantidade` int(11) NOT NULL,
  `preco_unitario` decimal(10,2) NOT NULL,
  `iva_percentual` decimal(5,2) NOT NULL,
  `iva_valor` decimal(10,2) NOT NULL,
  `comissao_percentual` decimal(5,2) NOT NULL,
  `comissao_valor` decimal(10,2) NOT NULL,
  `subtotal_linha` decimal(10,2) NOT NULL,
  `total_linha` decimal(10,2) NOT NULL,
  `valor_artista` decimal(10,2) NOT NULL,
  `estado_item` enum('pendente','em_preparacao','enviado','entregue','cancelado') NOT NULL DEFAULT 'pendente',
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`idEncomendaItem`),
  KEY `fk_encomenda_item_encomenda` (`idEncomenda`),
  KEY `fk_encomenda_item_produto` (`idProduto`),
  KEY `fk_encomenda_item_tamanho` (`idTamanho`),
  KEY `idx_encomenda_item_artista_estado` (`idArtista`,`estado_item`),
  CONSTRAINT `fk_encomenda_item_artista` FOREIGN KEY (`idArtista`) REFERENCES `cliente` (`idCliente`),
  CONSTRAINT `fk_encomenda_item_encomenda` FOREIGN KEY (`idEncomenda`) REFERENCES `encomenda` (`idEncomenda`) ON DELETE CASCADE,
  CONSTRAINT `fk_encomenda_item_produto` FOREIGN KEY (`idProduto`) REFERENCES `produto` (`idProduto`),
  CONSTRAINT `fk_encomenda_item_tamanho` FOREIGN KEY (`idTamanho`) REFERENCES `tamanho` (`idTamanho`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=83 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `encomenda_item`
--

LOCK TABLES `encomenda_item` WRITE;
/*!40000 ALTER TABLE `encomenda_item` DISABLE KEYS */;
INSERT INTO `encomenda_item` VALUES (1,1,9,3,NULL,'Debut','Vinil',1,39.99,23.00,9.20,5.00,2.46,39.99,49.19,46.73,'entregue','2025-12-28 15:52:19'),(2,1,10,3,NULL,'Spike Hat','Acessorio',2,29.99,23.00,13.80,5.00,3.69,59.98,73.78,70.09,'entregue','2025-12-28 15:52:19'),(3,2,11,3,1,'Lamp Tshirt','T-Shirt',2,19.99,23.00,9.20,5.00,2.46,39.98,49.18,46.72,'entregue','2026-01-15 16:52:19'),(4,2,12,5,2,'Vetements Europe','Hoodie',1,89.99,23.00,20.70,5.00,5.53,89.99,110.69,105.15,'entregue','2026-01-15 16:52:19'),(5,3,13,5,2,'Crystal castles Tshirt','T-Shirt',3,29.99,23.00,20.69,5.00,5.53,89.97,110.66,105.13,'entregue','2026-01-29 17:52:19'),(6,3,9,3,NULL,'Debut','Vinil',1,39.99,23.00,9.20,5.00,2.46,39.99,49.19,46.73,'entregue','2026-01-29 17:52:19'),(7,4,10,3,NULL,'Spike Hat','Acessorio',1,29.99,23.00,6.90,5.00,1.84,29.99,36.89,35.04,'em_preparacao','2026-02-20 18:52:19'),(8,4,11,3,1,'Lamp Tshirt','T-Shirt',2,19.99,23.00,9.20,5.00,2.46,39.98,49.18,46.72,'em_preparacao','2026-02-20 18:52:19'),(9,5,12,5,2,'Vetements Europe','Hoodie',2,89.99,23.00,41.40,5.00,11.07,179.98,221.38,210.31,'enviado','2026-03-06 19:52:19'),(10,5,13,5,2,'Crystal castles Tshirt','T-Shirt',1,29.99,23.00,6.90,5.00,1.84,29.99,36.89,35.04,'enviado','2026-03-06 19:52:19'),(11,6,9,3,NULL,'Debut','Vinil',3,39.99,23.00,27.59,5.00,7.38,119.97,147.56,140.18,'pendente','2026-03-25 20:52:19'),(12,6,10,3,NULL,'Spike Hat','Acessorio',1,29.99,23.00,6.90,5.00,1.84,29.99,36.89,35.04,'pendente','2026-03-25 20:52:19'),(13,7,11,3,1,'Lamp Tshirt','T-Shirt',1,19.99,23.00,4.60,5.00,1.23,19.99,24.59,23.36,'cancelado','2026-04-12 20:52:19'),(14,7,12,5,2,'Vetements Europe','Hoodie',2,89.99,23.00,41.40,5.00,11.07,179.98,221.38,210.31,'cancelado','2026-04-12 20:52:19'),(15,8,13,5,2,'Crystal castles Tshirt','T-Shirt',2,29.99,23.00,13.80,5.00,3.69,59.98,73.78,70.09,'entregue','2026-04-26 21:52:19'),(16,8,9,3,NULL,'Debut','Vinil',1,39.99,23.00,9.20,5.00,2.46,39.99,49.19,46.73,'entregue','2026-04-26 21:52:19'),(17,9,10,3,NULL,'Spike Hat','Acessorio',3,29.99,23.00,20.69,5.00,5.53,89.97,110.66,105.13,'em_preparacao','2026-05-09 22:52:19'),(18,9,11,3,1,'Lamp Tshirt','T-Shirt',1,19.99,23.00,4.60,5.00,1.23,19.99,24.59,23.36,'em_preparacao','2026-05-09 22:52:19'),(19,10,12,5,2,'Vetements Europe','Hoodie',1,89.99,23.00,20.70,5.00,5.53,89.99,110.69,105.15,'enviado','2026-05-20 23:52:19'),(20,10,13,5,2,'Crystal castles Tshirt','T-Shirt',2,29.99,23.00,13.80,5.00,3.69,59.98,73.78,70.09,'enviado','2026-05-20 23:52:19'),(21,11,1,2,NULL,'E','CD',2,19.99,23.00,9.20,5.00,2.46,39.98,49.18,46.72,'entregue','2025-12-28 22:52:19'),(22,11,2,2,NULL,'Zushi','CD',2,19.99,23.00,9.20,5.00,2.46,39.98,49.18,46.72,'entregue','2025-12-28 22:52:19'),(23,12,3,2,NULL,'BBF','Vinil',3,39.99,23.00,27.59,5.00,7.38,119.97,147.56,140.18,'entregue','2026-01-15 23:52:19'),(24,12,4,2,NULL,'333','Vinil',1,39.99,23.00,9.20,5.00,2.46,39.99,49.19,46.73,'entregue','2026-01-15 23:52:19'),(25,13,5,2,NULL,'Bladee Stanley','Acessorio',1,29.99,23.00,6.90,5.00,1.84,29.99,36.89,35.04,'em_preparacao','2026-01-30 00:52:19'),(26,13,6,2,NULL,'Man plays the horn','Vinil',1,39.99,23.00,9.20,5.00,2.46,39.99,49.19,46.73,'em_preparacao','2026-01-30 00:52:19'),(27,14,7,2,NULL,'Black pleasure cassette','CD',2,19.99,23.00,9.20,5.00,2.46,39.98,49.18,46.72,'enviado','2026-02-21 01:52:19'),(28,14,8,2,1,'Dean Tshirt','T-Shirt',2,29.99,23.00,13.80,5.00,3.69,59.98,73.78,70.09,'enviado','2026-02-21 01:52:19'),(29,15,12,5,2,'Vetements Europe','Hoodie',3,89.99,23.00,62.09,5.00,16.60,269.97,332.06,315.46,'pendente','2026-03-07 02:52:19'),(30,15,13,5,2,'Crystal castles Tshirt','T-Shirt',1,29.99,23.00,6.90,5.00,1.84,29.99,36.89,35.04,'pendente','2026-03-07 02:52:19'),(31,16,1,2,NULL,'E','CD',1,19.99,23.00,4.60,5.00,1.23,19.99,24.59,23.36,'cancelado','2026-03-26 03:52:19'),(32,16,2,2,NULL,'Zushi','CD',1,19.99,23.00,4.60,5.00,1.23,19.99,24.59,23.36,'cancelado','2026-03-26 03:52:19'),(33,17,3,2,NULL,'BBF','Vinil',2,39.99,23.00,18.40,5.00,4.92,79.98,98.38,93.46,'entregue','2026-04-13 03:52:19'),(34,17,4,2,NULL,'333','Vinil',2,39.99,23.00,18.40,5.00,4.92,79.98,98.38,93.46,'entregue','2026-04-13 03:52:19'),(35,18,5,2,NULL,'Bladee Stanley','Acessorio',3,29.99,23.00,20.69,5.00,5.53,89.97,110.66,105.13,'em_preparacao','2026-04-27 04:52:19'),(36,18,6,2,NULL,'Man plays the horn','Vinil',1,39.99,23.00,9.20,5.00,2.46,39.99,49.19,46.73,'em_preparacao','2026-04-27 04:52:19'),(37,19,7,2,NULL,'Black pleasure cassette','CD',1,19.99,23.00,4.60,5.00,1.23,19.99,24.59,23.36,'enviado','2026-05-10 05:52:19'),(38,19,8,2,1,'Dean Tshirt','T-Shirt',1,29.99,23.00,6.90,5.00,1.84,29.99,36.89,35.04,'enviado','2026-05-10 05:52:19'),(39,20,12,5,2,'Vetements Europe','Hoodie',2,89.99,23.00,41.40,5.00,11.07,179.98,221.38,210.31,'entregue','2026-05-21 06:52:19'),(40,20,13,5,2,'Crystal castles Tshirt','T-Shirt',2,29.99,23.00,13.80,5.00,3.69,59.98,73.78,70.09,'entregue','2026-05-21 06:52:19'),(41,21,1,2,NULL,'E','CD',3,19.99,23.00,13.79,5.00,3.69,59.97,73.76,70.07,'entregue','2025-12-29 05:52:19'),(42,21,2,2,NULL,'Zushi','CD',2,19.99,23.00,9.20,5.00,2.46,39.98,49.18,46.72,'entregue','2025-12-29 05:52:19'),(43,22,3,2,NULL,'BBF','Vinil',1,39.99,23.00,9.20,5.00,2.46,39.99,49.19,46.73,'em_preparacao','2026-01-16 06:52:19'),(44,22,4,2,NULL,'333','Vinil',1,39.99,23.00,9.20,5.00,2.46,39.99,49.19,46.73,'em_preparacao','2026-01-16 06:52:19'),(45,23,5,2,NULL,'Bladee Stanley','Acessorio',2,29.99,23.00,13.80,5.00,3.69,59.98,73.78,70.09,'enviado','2026-01-30 07:52:19'),(46,23,6,2,NULL,'Man plays the horn','Vinil',1,39.99,23.00,9.20,5.00,2.46,39.99,49.19,46.73,'enviado','2026-01-30 07:52:19'),(47,24,7,2,NULL,'Black pleasure cassette','CD',3,19.99,23.00,13.79,5.00,3.69,59.97,73.76,70.07,'cancelado','2026-02-21 08:52:19'),(48,24,8,2,1,'Dean Tshirt','T-Shirt',2,29.99,23.00,13.80,5.00,3.69,59.98,73.78,70.09,'cancelado','2026-02-21 08:52:19'),(49,25,9,3,NULL,'Debut','Vinil',1,39.99,23.00,9.20,5.00,2.46,39.99,49.19,46.73,'cancelado','2026-03-07 09:52:19'),(50,25,10,3,NULL,'Spike Hat','Acessorio',1,29.99,23.00,6.90,5.00,1.84,29.99,36.89,35.04,'cancelado','2026-03-07 09:52:19'),(51,26,11,3,1,'Lamp Tshirt','T-Shirt',2,19.99,23.00,9.20,5.00,2.46,39.98,49.18,46.72,'entregue','2026-03-26 10:52:19'),(52,26,1,2,NULL,'E','CD',1,19.99,23.00,4.60,5.00,1.23,19.99,24.59,23.36,'entregue','2026-03-26 10:52:19'),(53,27,2,2,NULL,'Zushi','CD',3,19.99,23.00,13.79,5.00,3.69,59.97,73.76,70.07,'em_preparacao','2026-04-13 10:52:19'),(54,27,3,2,NULL,'BBF','Vinil',2,39.99,23.00,18.40,5.00,4.92,79.98,98.38,93.46,'em_preparacao','2026-04-13 10:52:19'),(55,28,4,2,NULL,'333','Vinil',1,39.99,23.00,9.20,5.00,2.46,39.99,49.19,46.73,'enviado','2026-04-27 11:52:19'),(56,28,5,2,NULL,'Bladee Stanley','Acessorio',1,29.99,23.00,6.90,5.00,1.84,29.99,36.89,35.04,'enviado','2026-04-27 11:52:19'),(57,29,6,2,NULL,'Man plays the horn','Vinil',2,39.99,23.00,18.40,5.00,4.92,79.98,98.38,93.46,'entregue','2026-05-10 12:52:19'),(58,29,7,2,NULL,'Black pleasure cassette','CD',1,19.99,23.00,4.60,5.00,1.23,19.99,24.59,23.36,'entregue','2026-05-10 12:52:19'),(59,30,8,2,1,'Dean Tshirt','T-Shirt',3,29.99,23.00,20.69,5.00,5.53,89.97,110.66,105.13,'entregue','2026-05-21 13:52:19'),(60,30,9,3,NULL,'Debut','Vinil',2,39.99,23.00,18.40,5.00,4.92,79.98,98.38,93.46,'entregue','2026-05-21 13:52:19'),(61,31,1,2,NULL,'E','CD',1,19.99,23.00,4.60,5.00,1.00,19.99,24.59,23.59,'entregue','2026-05-21 12:43:08'),(62,32,2,2,NULL,'Zushi','CD',1,19.99,23.00,4.60,5.00,1.00,19.99,24.59,23.59,'entregue','2026-05-20 12:43:08'),(63,33,3,2,NULL,'BBF','Vinil',1,39.99,23.00,9.20,5.00,2.00,39.99,49.19,47.19,'entregue','2026-05-19 12:43:08'),(64,34,4,2,NULL,'333','Vinil',1,39.99,23.00,9.20,5.00,2.00,39.99,49.19,47.19,'entregue','2026-05-18 12:43:08'),(65,35,5,2,NULL,'Bladee Stanley','Acessorio',1,29.99,23.00,6.90,5.00,1.50,29.99,36.89,35.39,'entregue','2026-05-17 12:43:08'),(66,36,6,2,NULL,'Man plays the horn','Vinil',1,39.99,23.00,9.20,5.00,2.00,39.99,49.19,47.19,'entregue','2026-05-16 12:43:08'),(67,37,7,2,NULL,'Black pleasure cassette','CD',1,19.99,23.00,4.60,5.00,1.00,19.99,24.59,23.59,'entregue','2026-05-15 12:43:08'),(68,38,8,2,NULL,'Dean Tshirt','T-Shirt',1,29.99,23.00,6.90,5.00,1.50,29.99,36.89,35.39,'entregue','2026-05-14 12:43:08'),(69,39,9,3,NULL,'Debut','Vinil',1,39.99,23.00,9.20,5.00,2.00,39.99,49.19,47.19,'entregue','2026-05-13 12:43:08'),(70,40,10,3,NULL,'Spike Hat','Acessorio',1,29.99,23.00,6.90,5.00,1.50,29.99,36.89,35.39,'entregue','2026-05-12 12:43:08'),(71,41,11,3,NULL,'Lamp Tshirt','T-Shirt',1,19.99,23.00,4.60,5.00,1.00,19.99,24.59,23.59,'entregue','2026-05-11 12:43:08'),(72,42,12,5,NULL,'Vetements Europe','Hoodie',1,89.99,23.00,20.70,5.00,4.50,89.99,110.69,106.19,'entregue','2026-05-10 12:43:08'),(73,43,13,5,NULL,'Crystal castles Tshirt','T-Shirt',1,29.99,23.00,6.90,5.00,1.50,29.99,36.89,35.39,'entregue','2026-05-09 12:43:08'),(74,44,15,5,NULL,'Star ','CD',1,24.99,23.00,5.75,5.00,1.25,24.99,30.74,29.49,'entregue','2026-05-08 12:43:08'),(75,45,16,5,NULL,'Mohawk Hat','Acessorio',1,19.99,23.00,4.60,5.00,1.00,19.99,24.59,23.59,'entregue','2026-05-07 12:43:08'),(76,46,17,5,NULL,'Studded Belt','Acessorio',1,14.99,23.00,3.45,5.00,0.75,14.99,18.44,17.69,'entregue','2026-05-06 12:43:08'),(77,47,16,5,NULL,'Mohawk Hat','Acessorio',30,19.99,23.00,137.93,5.00,36.88,599.70,737.63,700.75,'pendente','2026-06-04 14:19:41'),(78,47,17,5,NULL,'Studded Belt','Acessorio',1,14.99,23.00,3.45,5.00,0.92,14.99,18.44,17.52,'pendente','2026-06-04 14:19:41'),(79,48,11,3,2,'Lamp Tshirt','T-Shirt',1,19.99,23.00,4.60,5.00,1.23,19.99,24.59,23.36,'pendente','2026-06-04 23:13:50'),(80,48,14,5,NULL,'Riviera','Vinil',1,39.99,23.00,9.20,5.00,2.46,39.99,49.19,46.73,'pendente','2026-06-04 23:13:50'),(81,49,15,5,NULL,'Star ','CD',1,24.99,23.00,5.75,5.00,1.54,24.99,30.74,29.20,'pendente','2026-06-04 23:37:28'),(82,49,17,5,NULL,'Studded Belt','Acessorio',1,14.99,23.00,3.45,5.00,0.92,14.99,18.44,17.52,'pendente','2026-06-04 23:37:28');
/*!40000 ALTER TABLE `encomenda_item` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `encomenda_mensagem`
--

DROP TABLE IF EXISTS `encomenda_mensagem`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `encomenda_mensagem` (
  `idMensagemEncomenda` int(11) NOT NULL AUTO_INCREMENT,
  `idEncomenda` int(11) NOT NULL,
  `idProduto` int(11) NOT NULL,
  `idComprador` int(11) NOT NULL,
  `idArtista` int(11) NOT NULL,
  `remetente` enum('comprador','artista') NOT NULL DEFAULT 'comprador',
  `mensagem` text NOT NULL,
  `lida` tinyint(1) NOT NULL DEFAULT 0,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`idMensagemEncomenda`),
  KEY `idx_encomenda_mensagem_thread` (`idEncomenda`,`idProduto`,`criado_em`),
  KEY `idx_encomenda_mensagem_artista` (`idArtista`,`lida`),
  KEY `fk_encomenda_mensagem_produto` (`idProduto`),
  KEY `fk_encomenda_mensagem_comprador` (`idComprador`),
  CONSTRAINT `fk_encomenda_mensagem_artista` FOREIGN KEY (`idArtista`) REFERENCES `cliente` (`idCliente`) ON DELETE CASCADE,
  CONSTRAINT `fk_encomenda_mensagem_comprador` FOREIGN KEY (`idComprador`) REFERENCES `cliente` (`idCliente`) ON DELETE CASCADE,
  CONSTRAINT `fk_encomenda_mensagem_encomenda` FOREIGN KEY (`idEncomenda`) REFERENCES `encomenda` (`idEncomenda`) ON DELETE CASCADE,
  CONSTRAINT `fk_encomenda_mensagem_produto` FOREIGN KEY (`idProduto`) REFERENCES `produto` (`idProduto`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `encomenda_mensagem`
--

LOCK TABLES `encomenda_mensagem` WRITE;
/*!40000 ALTER TABLE `encomenda_mensagem` DISABLE KEYS */;
INSERT INTO `encomenda_mensagem` VALUES (1,10,12,2,5,'comprador','Olá Green, consegues confirmar se esta encomenda é oficial?',1,'2026-06-02 20:19:06'),(2,10,12,2,5,'artista','Sim, não te preocupes. A encomenda está confirmada.',0,'2026-06-03 10:54:20'),(3,10,12,2,5,'comprador','Podes confirmar o envio, por favor?',1,'2026-06-04 13:54:04'),(4,10,12,2,5,'artista','Claro, vou atualizar o estado assim que for enviada.',0,'2026-06-04 13:58:27'),(5,49,15,2,5,'comprador','Olá, quanto tempo deve demorar a entrega?',0,'2026-06-04 23:38:57'),(6,49,17,2,5,'comprador','Por favor envia assim que conseguires.',0,'2026-06-04 23:39:07');
/*!40000 ALTER TABLE `encomenda_mensagem` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `faixa`
--

DROP TABLE IF EXISTS `faixa`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `faixa` (
  `idFaixa` int(11) NOT NULL AUTO_INCREMENT,
  `idRelease` int(11) NOT NULL,
  `numero_faixa` int(11) NOT NULL DEFAULT 1,
  `titulo` varchar(180) NOT NULL,
  `genero` varchar(80) DEFAULT NULL,
  `ficheiro_audio` varchar(255) NOT NULL,
  `duracao_segundos` int(11) DEFAULT NULL,
  `estado` enum('pendente','aprovada','rejeitada','inativa') NOT NULL DEFAULT 'pendente',
  `ativo` tinyint(1) NOT NULL DEFAULT 1,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  `atualizado_em` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`idFaixa`),
  UNIQUE KEY `uq_faixa_release_numero` (`idRelease`,`numero_faixa`),
  KEY `idx_faixa_release_estado` (`idRelease`,`estado`),
  KEY `idx_faixa_genero` (`genero`),
  CONSTRAINT `fk_faixa_release` FOREIGN KEY (`idRelease`) REFERENCES `release_musical` (`idRelease`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=45 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `faixa`
--

LOCK TABLES `faixa` WRITE;
/*!40000 ALTER TABLE `faixa` DISABLE KEYS */;
INSERT INTO `faixa` VALUES (3,3,1,'Nice','Electronic','track_2_1_8eb5bc3e1653c81e.mp3',NULL,'aprovada',1,'2026-05-26 22:51:45','2026-06-02 15:41:50'),(4,4,1,'Rush','Electronic','track_2_1_b98a4ff8ec5ee53e.mp3',NULL,'aprovada',1,'2026-05-26 22:52:18','2026-06-02 15:41:50'),(5,5,1,'Never late again','Electronic','track_2_1_d813fe8801e40f78.mp3',NULL,'aprovada',1,'2026-05-26 23:36:05','2026-06-02 15:41:50'),(6,5,2,'It Makes the Babies Want to Cry','Electronic','track_2_2_1576af3d6b2faffc.mp3',NULL,'aprovada',1,'2026-05-26 23:36:05','2026-06-02 15:41:50'),(7,5,3,'Warmspot','Electronic','track_2_3_3c53e6087a9bd582.mp3',NULL,'aprovada',1,'2026-05-26 23:36:05','2026-06-02 15:41:50'),(8,6,1,'Night , Blooming Jasmine .','Alternative R&B','track_2_1_8d605e9ffe5c2b4a.mp3',NULL,'aprovada',1,'2026-05-26 23:37:32','2026-06-02 15:41:50'),(9,7,1,'I Really Want to Stay At Your House','Synthpop','track_2_1_c11fabc728c8b9e2.mp3',NULL,'aprovada',1,'2026-05-26 23:40:50','2026-06-02 15:41:50'),(10,8,1,'7 Eleven','Alternative R&B','track_2_1_ccadbf8c369da634.mp3',NULL,'aprovada',1,'2026-05-26 23:43:39','2026-06-02 15:41:50'),(11,9,1,'donna air','Alternative','track_2_1_0e1520ab3e026b43.mp3',NULL,'aprovada',1,'2026-05-26 23:49:26','2026-06-02 15:41:50'),(12,9,2,'pj harvey cover','Alternative','track_2_2_f7070b059a074abf.mp3',NULL,'aprovada',1,'2026-05-26 23:49:26','2026-06-02 15:41:50'),(13,9,3,'Rinsed','Alternative','track_2_3_7676e4c0159e114e.mp3',NULL,'aprovada',1,'2026-05-26 23:49:26','2026-06-02 15:41:50'),(14,9,4,'100','Alternative','track_2_4_25d77ebf164f9470.mp3',NULL,'aprovada',1,'2026-05-26 23:49:26','2026-06-02 15:41:50'),(15,10,1,'IDrankUpAllTheClouds','Experimental','track_2_1_58e0c71673e248cb.mp3',NULL,'aprovada',1,'2026-05-26 23:53:11','2026-06-02 15:41:50'),(16,10,2,'ESCORTS','Experimental','track_2_2_0f683a34350c60d1.mp3',NULL,'aprovada',1,'2026-05-26 23:53:11','2026-06-02 15:41:50'),(17,10,3,'Cities Aviv   VISIONS OF US','Experimental Hip Hop','track_2_3_42f7bcb1439f12c0.mp3',NULL,'aprovada',1,'2026-05-26 23:53:11','2026-06-02 15:41:50'),(18,10,4,'SATISFY YOUR SOUL','Experimental','track_2_4_13686fc57f95f9f3.mp3',NULL,'aprovada',1,'2026-05-26 23:53:11','2026-06-02 15:41:50'),(19,11,1,'Peroxide','Alternative Pop','track_2_1_a8a5001ed09893cd.mp3',NULL,'aprovada',1,'2026-05-26 23:57:05','2026-06-03 10:39:44'),(20,12,1,'Unforgettable','R&B','track_3_1_875a37873e3a5748.mp3',NULL,'aprovada',1,'2026-05-27 00:21:05','2026-06-02 15:41:50'),(21,13,1,'Big Time Sensuality','Dance Pop','track_3_1_878146c20ff7ee89.mp3',NULL,'aprovada',1,'2026-05-27 00:22:43','2026-06-02 15:41:50'),(22,14,1,'Break from Toronto','R&B','track_3_1_bb6240c67a83d5db.mp3',NULL,'aprovada',1,'2026-05-27 00:23:50','2026-06-02 15:41:50'),(23,15,1,'Fashion Killa','Hip Hop','track_3_1_dd86d2825f5ae445.mp3',NULL,'aprovada',1,'2026-05-27 00:28:44','2026-06-02 15:41:50'),(24,16,1,'Sure Thing','R&B','track_3_1_054dcf2c5c04fdd7.mp3',NULL,'aprovada',1,'2026-05-27 00:29:47','2026-06-02 15:41:50'),(25,17,1,'Say it','R&B','track_3_1_b0b84abcf1dfaee3.mp3',NULL,'aprovada',1,'2026-05-27 00:30:48','2026-06-02 15:41:50'),(26,18,1,'Ella Megalast Burls Forever','Alternative','track_3_1_5c29b96b4a4e61c3.mp3',NULL,'aprovada',1,'2026-05-27 00:32:16','2026-06-02 15:41:50'),(27,19,1,'3005','Hip Hop','',NULL,'rejeitada',0,'2026-05-27 00:46:22','2026-06-02 15:41:50'),(28,20,1,'What Do You Mean','Pop','',NULL,'rejeitada',0,'2026-05-27 00:49:08','2026-06-02 15:41:50'),(29,21,1,'PILLOWTALK','Pop','',NULL,'rejeitada',0,'2026-05-27 00:52:58','2026-06-02 15:41:50'),(30,22,1,'3005','Hip Hop','track_3_1_69dcaae35c1e4525.mp3',NULL,'aprovada',1,'2026-05-27 00:57:35','2026-06-02 15:41:50'),(31,23,1,'What Do You Mean','Pop','track_3_1_68319689e7d45a72.mp3',NULL,'aprovada',1,'2026-05-27 00:58:13','2026-06-02 15:41:50'),(32,24,1,'PILLOWTALK','Pop','track_3_1_11791c3b2d0d658c.mp3',NULL,'aprovada',1,'2026-05-27 00:58:50','2026-06-02 15:41:50'),(36,25,1,'Die For You','R&B','track_3_1_3b7aacb035427585.mp3',NULL,'aprovada',1,'2026-05-27 10:04:11','2026-06-02 15:41:50'),(37,25,2,'Call Out My Name','R&B','track_3_2_47e7ae29eb8e8cff.mp3',NULL,'aprovada',1,'2026-05-27 10:04:11','2026-06-02 15:41:50'),(38,25,3,'Is There Someone Else?','R&B','track_3_3_8bd83f1dd8e41817.mp3',NULL,'aprovada',1,'2026-05-27 10:04:11','2026-06-02 15:41:50'),(39,26,1,'Loveeeeeee Song','R&B','track_3_1_2d8c158fd8bb6a8e.mp3',NULL,'aprovada',1,'2026-05-27 10:11:39','2026-06-02 15:41:50'),(40,26,2,'Kiss It Better','R&B','track_3_2_adbb8eb116f45ffe.mp3',NULL,'aprovada',1,'2026-05-27 10:11:39','2026-06-02 15:41:50'),(41,26,3,'Only Girl','Pop','track_3_3_dabf98551e028afe.mp3',NULL,'aprovada',1,'2026-05-27 10:11:39','2026-06-02 15:41:50'),(42,27,1,'Travel the world','Indie','track_5_1_041ad36d013d997f.mp3',NULL,'aprovada',1,'2026-05-27 11:26:58','2026-06-02 15:41:50'),(43,28,1,'Sword','Alternative','track_5_1_b13d4aa4133156ef.mp3',NULL,'aprovada',1,'2026-05-27 11:28:39','2026-06-02 15:41:50'),(44,29,1,'Empty','Ambient','track_5_1_2fb8d2d984550031.mp3',NULL,'aprovada',1,'2026-05-27 11:30:02','2026-06-02 15:41:50');
/*!40000 ALTER TABLE `faixa` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `faixa_listen`
--

DROP TABLE IF EXISTS `faixa_listen`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `faixa_listen` (
  `idListen` int(11) NOT NULL AUTO_INCREMENT,
  `idFaixa` int(11) NOT NULL,
  `idCliente` int(11) DEFAULT NULL,
  `idArtista` int(11) NOT NULL,
  `segundos_ouvidos` int(11) NOT NULL DEFAULT 0,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`idListen`),
  KEY `idx_faixa_listen_artista` (`idArtista`,`criado_em`),
  KEY `idx_faixa_listen_faixa` (`idFaixa`,`criado_em`),
  KEY `fk_faixa_listen_cliente` (`idCliente`),
  CONSTRAINT `fk_faixa_listen_artista` FOREIGN KEY (`idArtista`) REFERENCES `cliente` (`idCliente`) ON DELETE CASCADE,
  CONSTRAINT `fk_faixa_listen_cliente` FOREIGN KEY (`idCliente`) REFERENCES `cliente` (`idCliente`) ON DELETE SET NULL,
  CONSTRAINT `fk_faixa_listen_faixa` FOREIGN KEY (`idFaixa`) REFERENCES `faixa` (`idFaixa`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=1658 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `faixa_listen`
--

LOCK TABLES `faixa_listen` WRITE;
/*!40000 ALTER TABLE `faixa_listen` DISABLE KEYS */;
INSERT INTO `faixa_listen` VALUES (1,14,2,2,155,'2026-06-02 09:05:10'),(2,14,2,2,171,'2026-06-02 09:05:27'),(3,44,2,5,20,'2026-06-02 09:06:17'),(4,44,2,5,39,'2026-06-02 09:06:36'),(5,32,2,3,20,'2026-06-02 09:07:06'),(6,32,2,3,74,'2026-06-02 09:08:00'),(7,32,2,3,133,'2026-06-02 09:09:00'),(8,9,2,2,133,'2026-06-02 09:16:04'),(9,9,2,2,136,'2026-06-02 09:16:08'),(10,9,2,2,137,'2026-06-02 09:16:08'),(11,9,2,2,141,'2026-06-02 09:16:13'),(12,9,2,2,188,'2026-06-02 09:17:00'),(13,9,2,2,248,'2026-06-02 09:18:00'),(14,18,2,2,109,'2026-06-02 09:18:52'),(15,18,2,2,109,'2026-06-02 09:18:54'),(16,18,2,2,109,'2026-06-02 09:18:57'),(17,36,2,3,109,'2026-06-02 09:19:08'),(18,36,2,3,109,'2026-06-02 09:19:13'),(19,36,2,3,109,'2026-06-02 09:19:13'),(20,36,2,3,109,'2026-06-02 09:19:14'),(21,36,2,3,109,'2026-06-02 09:19:15'),(22,36,2,3,109,'2026-06-02 09:19:25'),(23,36,2,3,109,'2026-06-02 09:20:59'),(24,36,2,3,109,'2026-06-02 09:21:12'),(25,36,2,3,109,'2026-06-02 09:21:14'),(26,36,2,3,109,'2026-06-02 09:21:15'),(27,36,2,3,109,'2026-06-02 09:21:16'),(28,36,2,3,109,'2026-06-02 09:21:17'),(29,36,2,3,109,'2026-06-02 09:21:18'),(30,36,2,3,109,'2026-06-02 09:21:19'),(31,36,2,3,109,'2026-06-02 09:21:19'),(32,36,2,3,109,'2026-06-02 09:21:20'),(33,36,2,3,109,'2026-06-02 09:21:21'),(34,36,2,3,109,'2026-06-02 09:21:22'),(35,36,2,3,109,'2026-06-02 09:21:24'),(36,36,2,3,109,'2026-06-02 09:21:25'),(37,36,2,3,109,'2026-06-02 09:21:29'),(38,36,2,3,109,'2026-06-02 09:21:41'),(39,36,2,3,109,'2026-06-02 09:21:43'),(40,36,2,3,109,'2026-06-02 09:21:44'),(41,36,2,3,109,'2026-06-02 09:21:46'),(42,36,2,3,109,'2026-06-02 09:21:47'),(43,36,2,3,109,'2026-06-02 09:21:49'),(44,36,2,3,109,'2026-06-02 09:21:58'),(45,36,2,3,109,'2026-06-02 09:22:05'),(46,36,2,3,109,'2026-06-02 09:22:06'),(47,36,2,3,109,'2026-06-02 09:22:06'),(48,36,2,3,109,'2026-06-02 09:22:08'),(49,36,2,3,109,'2026-06-02 09:28:34'),(50,36,2,3,109,'2026-06-02 09:28:36'),(51,36,2,3,109,'2026-06-02 09:28:38'),(52,36,2,3,109,'2026-06-02 09:28:38'),(53,36,2,3,109,'2026-06-02 09:28:39'),(54,36,2,3,109,'2026-06-02 09:32:24'),(55,36,2,3,109,'2026-06-02 09:32:27'),(56,36,2,3,109,'2026-06-02 09:32:28'),(57,36,2,3,109,'2026-06-02 09:32:29'),(58,36,2,3,109,'2026-06-02 09:32:30'),(59,36,2,3,109,'2026-06-02 09:32:37'),(60,36,2,3,109,'2026-06-02 09:32:39'),(61,36,2,3,109,'2026-06-02 09:35:31'),(62,36,2,3,109,'2026-06-02 09:35:43'),(63,36,2,3,109,'2026-06-02 09:38:55'),(64,36,2,3,109,'2026-06-02 09:38:56'),(65,36,2,3,109,'2026-06-02 09:39:00'),(66,36,2,3,109,'2026-06-02 09:39:03'),(67,36,2,3,109,'2026-06-02 09:39:04'),(68,36,2,3,109,'2026-06-02 09:39:05'),(69,36,2,3,109,'2026-06-02 09:39:11'),(70,36,2,3,109,'2026-06-02 09:44:31'),(71,36,2,3,109,'2026-06-02 09:53:22'),(72,36,2,3,147,'2026-06-02 09:54:00'),(73,36,2,3,207,'2026-06-02 09:55:00'),(74,7,2,2,20,'2026-06-02 09:55:46'),(75,7,2,2,33,'2026-06-02 09:56:00'),(76,7,2,2,93,'2026-06-02 09:57:00'),(77,7,2,2,107,'2026-06-02 09:57:14'),(78,7,2,2,117,'2026-06-02 09:57:24'),(79,7,2,2,153,'2026-06-02 09:58:00'),(80,7,2,2,213,'2026-06-02 09:59:00'),(81,31,2,3,20,'2026-06-02 10:00:12'),(82,31,2,3,67,'2026-06-02 10:01:00'),(83,31,2,3,68,'2026-06-02 10:01:02'),(84,31,2,3,127,'2026-06-02 10:02:00'),(85,31,2,3,186,'2026-06-02 10:03:00'),(86,8,2,2,20,'2026-06-02 10:03:39'),(87,8,2,2,41,'2026-06-02 10:04:00'),(88,36,2,3,20,'2026-06-02 10:04:25'),(89,36,2,3,54,'2026-06-02 10:05:00'),(90,36,2,3,114,'2026-06-02 10:06:00'),(91,36,2,3,174,'2026-06-02 10:07:00'),(92,13,2,2,20,'2026-06-02 10:08:18'),(93,13,2,2,47,'2026-06-02 10:08:46'),(94,13,2,2,60,'2026-06-02 10:09:00'),(95,6,2,2,20,'2026-06-02 10:10:06'),(96,42,2,5,20,'2026-06-02 10:11:09'),(97,42,2,5,71,'2026-06-02 10:12:00'),(98,5,2,2,20,'2026-06-02 10:12:44'),(99,5,2,2,27,'2026-06-02 10:12:51'),(100,5,2,2,35,'2026-06-02 10:13:00'),(101,5,2,2,95,'2026-06-02 10:14:00'),(102,5,2,2,155,'2026-06-02 10:15:00'),(103,5,2,2,215,'2026-06-02 10:16:00'),(104,25,2,3,20,'2026-06-02 10:16:56'),(105,25,2,3,23,'2026-06-02 10:17:00'),(106,25,2,3,43,'2026-06-02 10:17:19'),(107,25,2,3,83,'2026-06-02 10:18:00'),(108,25,2,3,143,'2026-06-02 10:19:00'),(109,20,2,3,20,'2026-06-02 10:19:53'),(110,20,2,3,20,'2026-06-02 10:19:53'),(111,20,2,3,27,'2026-06-02 10:20:00'),(112,20,2,3,86,'2026-06-02 10:21:00'),(113,3,2,2,20,'2026-06-02 10:21:56'),(114,3,2,2,24,'2026-06-02 10:22:00'),(115,3,2,2,84,'2026-06-02 10:23:00'),(116,7,2,2,20,'2026-06-02 10:24:18'),(117,7,2,2,61,'2026-06-02 10:25:00'),(118,7,2,2,121,'2026-06-02 10:26:00'),(119,7,2,2,181,'2026-06-02 10:27:00'),(120,9,2,2,20,'2026-06-02 10:27:52'),(121,9,2,2,27,'2026-06-02 10:28:00'),(122,9,2,2,85,'2026-06-02 10:28:58'),(123,9,2,2,87,'2026-06-02 10:29:00'),(124,9,2,2,128,'2026-06-02 10:29:42'),(125,9,2,2,147,'2026-06-02 10:30:00'),(126,9,2,2,206,'2026-06-02 10:31:00'),(127,31,2,3,20,'2026-06-02 10:32:21'),(128,31,2,3,59,'2026-06-02 10:33:00'),(129,31,2,3,119,'2026-06-02 10:34:00'),(130,31,2,3,179,'2026-06-02 10:35:00'),(131,21,2,3,20,'2026-06-02 10:35:46'),(132,21,2,3,33,'2026-06-02 10:36:00'),(133,21,2,3,93,'2026-06-02 10:37:00'),(134,21,2,3,153,'2026-06-02 10:38:00'),(135,21,2,3,213,'2026-06-02 10:39:00'),(136,37,2,3,20,'2026-06-02 10:39:43'),(137,37,2,3,36,'2026-06-02 10:40:00'),(138,37,2,3,96,'2026-06-02 10:41:00'),(139,37,2,3,157,'2026-06-02 10:42:00'),(140,37,2,3,216,'2026-06-02 10:43:00'),(141,10,2,2,20,'2026-06-02 10:43:32'),(142,10,2,2,48,'2026-06-02 10:44:00'),(143,10,2,2,108,'2026-06-02 10:45:00'),(144,9,2,2,20,'2026-06-02 10:46:17'),(145,9,2,2,63,'2026-06-02 10:47:00'),(146,9,2,2,79,'2026-06-02 10:47:17'),(147,9,2,2,122,'2026-06-02 10:48:00'),(148,9,2,2,182,'2026-06-02 10:49:00'),(149,9,2,2,242,'2026-06-02 10:50:00'),(150,17,2,2,20,'2026-06-02 10:50:40'),(151,17,2,2,39,'2026-06-02 10:51:00'),(152,17,2,2,99,'2026-06-02 10:52:00'),(153,17,2,2,159,'2026-06-02 10:53:00'),(154,44,2,5,20,'2026-06-02 10:53:33'),(155,44,2,5,41,'2026-06-02 10:53:55'),(156,44,2,5,45,'2026-06-02 10:54:00'),(157,44,2,5,101,'2026-06-02 10:54:56'),(158,44,2,5,105,'2026-06-02 10:55:00'),(159,13,2,2,20,'2026-06-02 10:55:47'),(160,13,2,2,32,'2026-06-02 10:56:00'),(161,13,2,2,84,'2026-06-02 10:56:52'),(162,13,2,2,20,'2026-06-02 10:57:17'),(163,13,2,2,62,'2026-06-02 10:58:00'),(164,11,2,2,20,'2026-06-02 10:59:04'),(165,37,2,3,20,'2026-06-02 11:00:08'),(166,37,2,3,72,'2026-06-02 11:01:00'),(167,37,2,3,131,'2026-06-02 11:02:00'),(168,37,2,3,191,'2026-06-02 11:03:00'),(169,4,2,2,20,'2026-06-02 11:03:57'),(170,4,2,2,23,'2026-06-02 11:04:00'),(171,4,2,2,83,'2026-06-02 11:05:00'),(172,4,2,2,143,'2026-06-02 11:06:00'),(173,4,2,2,203,'2026-06-02 11:07:00'),(174,39,2,3,20,'2026-06-02 11:07:23'),(175,39,2,3,56,'2026-06-02 11:08:00'),(176,39,2,3,116,'2026-06-02 11:09:00'),(177,39,2,3,176,'2026-06-02 11:10:00'),(178,39,2,3,236,'2026-06-02 11:11:00'),(179,12,2,2,20,'2026-06-02 11:11:40'),(180,12,2,2,40,'2026-06-02 11:12:00'),(181,8,2,2,20,'2026-06-02 11:12:42'),(182,8,2,2,38,'2026-06-02 11:13:00'),(183,8,2,2,98,'2026-06-02 11:14:00'),(184,8,2,2,158,'2026-06-02 11:15:00'),(185,31,2,3,20,'2026-06-02 11:15:41'),(186,31,2,3,39,'2026-06-02 11:16:00'),(187,31,2,3,99,'2026-06-02 11:17:00'),(188,31,2,3,159,'2026-06-02 11:18:00'),(189,25,2,3,20,'2026-06-02 11:19:06'),(190,25,2,3,73,'2026-06-02 11:20:00'),(191,25,2,3,133,'2026-06-02 11:21:00'),(192,25,2,3,193,'2026-06-02 11:22:00'),(193,42,2,5,20,'2026-06-02 11:23:04'),(194,42,2,5,75,'2026-06-02 11:24:00'),(195,6,2,2,20,'2026-06-02 11:24:39'),(196,6,2,2,41,'2026-06-02 11:25:00'),(197,6,2,2,100,'2026-06-02 11:26:00'),(198,6,2,2,161,'2026-06-02 11:27:00'),(199,32,2,3,20,'2026-06-02 11:28:17'),(200,32,2,3,62,'2026-06-02 11:29:00'),(201,32,2,3,122,'2026-06-02 11:30:00'),(202,32,2,3,182,'2026-06-02 11:31:00'),(203,32,2,3,206,'2026-06-02 12:17:52'),(204,40,2,3,20,'2026-06-02 12:18:13'),(205,40,2,3,66,'2026-06-02 12:19:00'),(206,40,2,3,126,'2026-06-02 12:20:00'),(207,40,2,3,169,'2026-06-02 12:20:43'),(208,40,2,3,186,'2026-06-02 12:21:00'),(209,40,2,3,246,'2026-06-02 12:22:00'),(210,37,2,3,20,'2026-06-02 12:22:27'),(211,37,2,3,53,'2026-06-02 12:23:00'),(212,37,2,3,113,'2026-06-02 12:24:00'),(213,37,2,3,173,'2026-06-02 12:25:00'),(214,16,2,2,20,'2026-06-02 12:26:15'),(215,16,2,2,64,'2026-06-02 12:27:00'),(216,16,2,2,124,'2026-06-02 12:28:00'),(217,22,2,3,20,'2026-06-02 12:29:07'),(218,22,2,3,72,'2026-06-02 12:30:00'),(219,24,2,3,20,'2026-06-02 12:30:46'),(220,24,2,3,33,'2026-06-02 12:31:00'),(221,24,2,3,93,'2026-06-02 12:32:00'),(222,24,2,3,153,'2026-06-02 12:33:00'),(223,40,2,3,20,'2026-06-02 12:34:00'),(224,40,2,3,79,'2026-06-02 12:35:00'),(225,32,2,3,20,'2026-06-02 12:35:51'),(226,32,2,3,28,'2026-06-02 12:36:00'),(227,32,2,3,88,'2026-06-02 12:37:00'),(228,32,2,3,148,'2026-06-02 12:38:00'),(229,32,2,3,192,'2026-06-02 12:38:44'),(230,39,2,3,20,'2026-06-02 12:39:18'),(231,39,2,3,50,'2026-06-02 12:39:49'),(232,39,2,3,58,'2026-06-02 12:39:57'),(233,39,2,3,61,'2026-06-02 12:40:00'),(234,39,2,3,120,'2026-06-02 12:41:00'),(235,39,2,3,180,'2026-06-02 12:42:00'),(236,19,2,2,20,'2026-06-02 12:42:53'),(237,19,2,2,26,'2026-06-02 12:43:00'),(238,19,2,2,86,'2026-06-02 12:44:00'),(239,19,2,2,94,'2026-06-02 12:44:08'),(240,19,2,2,146,'2026-06-02 12:45:00'),(241,19,2,2,172,'2026-06-02 12:45:26'),(242,32,2,3,20,'2026-06-02 12:45:51'),(243,32,2,3,28,'2026-06-02 12:46:00'),(244,32,2,3,88,'2026-06-02 12:47:00'),(245,31,2,3,20,'2026-06-02 12:48:10'),(246,31,2,3,36,'2026-06-02 12:48:26'),(247,31,2,3,39,'2026-06-02 12:48:30'),(248,31,2,3,69,'2026-06-02 12:49:00'),(249,38,2,3,20,'2026-06-02 12:50:14'),(250,32,2,3,20,'2026-06-02 12:50:45'),(251,32,2,3,34,'2026-06-02 12:51:00'),(252,32,2,3,20,'2026-06-02 12:52:10'),(253,24,2,3,20,'2026-06-02 12:53:17'),(254,24,2,3,53,'2026-06-02 12:53:51'),(255,24,2,3,62,'2026-06-02 12:54:00'),(256,24,2,3,75,'2026-06-02 12:54:13'),(257,37,2,3,20,'2026-06-02 12:55:11'),(258,37,2,3,68,'2026-06-02 12:56:00'),(259,37,2,3,128,'2026-06-02 12:57:00'),(260,32,2,3,20,'2026-06-02 12:57:26'),(261,32,2,3,51,'2026-06-02 12:57:58'),(262,32,2,3,53,'2026-06-02 12:58:00'),(263,32,2,3,65,'2026-06-02 12:58:11'),(264,32,2,3,107,'2026-06-02 12:58:54'),(265,32,2,3,113,'2026-06-02 12:59:00'),(266,32,2,3,173,'2026-06-02 13:00:00'),(267,8,2,2,20,'2026-06-02 13:00:32'),(268,8,2,2,48,'2026-06-02 13:01:00'),(269,8,2,2,108,'2026-06-02 13:02:00'),(270,8,2,2,168,'2026-06-02 13:03:00'),(271,4,2,2,20,'2026-06-02 13:03:31'),(272,4,2,2,33,'2026-06-02 13:03:44'),(273,40,2,3,20,'2026-06-02 13:04:10'),(274,40,2,3,69,'2026-06-02 13:05:00'),(275,40,2,3,83,'2026-06-02 13:05:14'),(276,40,2,3,101,'2026-06-02 13:05:32'),(277,40,2,3,126,'2026-06-02 13:05:58'),(278,40,2,3,128,'2026-06-02 13:06:00'),(279,37,2,3,20,'2026-06-02 13:07:18'),(280,37,2,3,61,'2026-06-02 13:08:00'),(281,37,2,3,121,'2026-06-02 13:09:00'),(282,25,2,3,20,'2026-06-02 13:09:27'),(283,25,2,3,36,'2026-06-02 13:09:44'),(284,25,2,3,52,'2026-06-02 13:10:00'),(285,25,2,3,94,'2026-06-02 13:10:42'),(286,25,2,3,112,'2026-06-02 13:11:00'),(287,25,2,3,123,'2026-06-02 13:11:11'),(288,25,2,3,133,'2026-06-02 13:11:22'),(289,22,2,3,20,'2026-06-02 13:11:55'),(290,22,2,3,24,'2026-06-02 13:12:00'),(291,22,2,3,33,'2026-06-02 13:12:09'),(292,22,2,3,53,'2026-06-02 13:12:30'),(293,22,2,3,84,'2026-06-02 13:13:00'),(294,40,2,3,20,'2026-06-02 13:13:35'),(295,40,2,3,44,'2026-06-02 13:14:00'),(296,40,2,3,104,'2026-06-02 13:15:00'),(297,40,2,3,164,'2026-06-02 13:16:00'),(298,40,2,3,224,'2026-06-02 13:17:00'),(299,24,2,3,20,'2026-06-02 13:17:48'),(300,24,2,3,31,'2026-06-02 13:18:00'),(301,24,2,3,74,'2026-06-02 13:18:43'),(302,24,2,3,77,'2026-06-02 13:18:46'),(303,24,2,3,77,'2026-06-02 13:18:47'),(304,24,2,3,88,'2026-06-02 13:18:57'),(305,24,2,3,90,'2026-06-02 13:19:00'),(306,24,2,3,98,'2026-06-02 13:19:07'),(307,20,2,3,20,'2026-06-02 13:19:41'),(308,20,2,3,39,'2026-06-02 13:20:00'),(309,20,2,3,73,'2026-06-02 13:20:35'),(310,20,2,3,98,'2026-06-02 13:21:00'),(311,20,2,3,101,'2026-06-02 13:21:02'),(312,31,2,3,20,'2026-06-02 13:21:44'),(313,31,2,3,35,'2026-06-02 13:22:00'),(314,31,2,3,95,'2026-06-02 13:23:00'),(315,31,2,3,123,'2026-06-02 13:23:28'),(316,31,2,3,155,'2026-06-02 13:24:00'),(317,31,2,3,176,'2026-06-02 13:24:21'),(318,6,2,2,20,'2026-06-02 13:25:10'),(319,6,2,2,36,'2026-06-02 13:25:26'),(320,32,2,3,20,'2026-06-02 13:25:59'),(321,32,2,3,20,'2026-06-02 13:26:00'),(322,32,2,3,80,'2026-06-02 13:27:00'),(323,39,2,3,20,'2026-06-02 13:27:52'),(324,39,2,3,27,'2026-06-02 13:28:00'),(325,39,2,3,87,'2026-06-02 13:29:00'),(326,39,2,3,147,'2026-06-02 13:30:00'),(327,39,2,3,162,'2026-06-02 13:30:15'),(328,39,2,3,177,'2026-06-02 13:30:30'),(329,39,2,3,197,'2026-06-02 13:30:50'),(330,39,2,3,207,'2026-06-02 13:31:00'),(331,23,2,3,20,'2026-06-02 13:32:10'),(332,23,2,3,70,'2026-06-02 13:33:00'),(333,23,2,3,130,'2026-06-02 13:34:00'),(334,23,2,3,190,'2026-06-02 13:35:00'),(335,30,2,3,20,'2026-06-02 13:35:41'),(336,30,2,3,38,'2026-06-02 13:36:00'),(337,30,2,3,98,'2026-06-02 13:37:00'),(338,30,2,3,103,'2026-06-02 13:37:05'),(339,30,2,3,105,'2026-06-02 13:37:07'),(340,30,2,3,158,'2026-06-02 13:38:00'),(341,13,2,2,20,'2026-06-02 13:38:46'),(342,13,2,2,31,'2026-06-02 13:38:57'),(343,13,2,2,34,'2026-06-02 13:39:00'),(344,13,2,2,88,'2026-06-02 13:39:55'),(345,13,2,2,93,'2026-06-02 13:40:00'),(346,36,2,3,20,'2026-06-02 13:40:33'),(347,36,2,3,46,'2026-06-02 13:41:00'),(348,36,2,3,47,'2026-06-02 13:41:00'),(349,36,2,3,78,'2026-06-02 13:41:32'),(350,20,2,3,20,'2026-06-02 13:42:00'),(351,20,2,3,79,'2026-06-02 13:43:00'),(352,41,2,3,20,'2026-06-02 13:44:03'),(353,41,2,3,76,'2026-06-02 13:45:00'),(354,36,2,3,20,'2026-06-02 13:45:43'),(355,36,2,3,37,'2026-06-02 13:46:00'),(356,36,2,3,53,'2026-06-02 13:46:16'),(357,36,2,3,20,'2026-06-02 13:47:30'),(358,36,2,3,41,'2026-06-02 13:47:51'),(359,36,2,3,44,'2026-06-02 13:47:54'),(360,36,2,3,45,'2026-06-02 13:47:56'),(361,36,2,3,45,'2026-06-02 13:47:57'),(362,36,2,3,46,'2026-06-02 13:47:57'),(363,36,2,3,47,'2026-06-02 13:47:58'),(364,36,2,3,48,'2026-06-02 13:48:00'),(365,36,2,3,108,'2026-06-02 13:49:00'),(366,36,2,3,168,'2026-06-02 13:50:00'),(367,36,2,3,228,'2026-06-02 13:51:00'),(368,36,2,3,39,'2026-06-02 13:52:00'),(369,36,2,3,99,'2026-06-02 13:53:00'),(370,36,2,3,159,'2026-06-02 13:54:00'),(371,36,2,3,219,'2026-06-02 13:55:00'),(372,37,2,3,20,'2026-06-02 13:55:33'),(373,37,2,3,46,'2026-06-02 13:56:00'),(374,37,2,3,106,'2026-06-02 13:57:00'),(375,37,2,3,166,'2026-06-02 13:58:00'),(376,37,2,3,226,'2026-06-02 13:59:00'),(377,38,2,3,20,'2026-06-02 13:59:22'),(378,38,2,3,57,'2026-06-02 14:00:00'),(379,20,2,3,20,'2026-06-02 14:00:51'),(380,20,2,3,28,'2026-06-02 14:01:00'),(381,20,2,3,88,'2026-06-02 14:02:00'),(382,41,2,3,20,'2026-06-02 14:02:54'),(383,41,2,3,25,'2026-06-02 14:03:00'),(384,41,2,3,85,'2026-06-02 14:04:00'),(385,41,2,3,145,'2026-06-02 14:05:00'),(386,41,2,3,205,'2026-06-02 14:06:00'),(387,4,2,2,20,'2026-06-02 14:06:50'),(388,4,2,2,30,'2026-06-02 14:07:00'),(389,4,2,2,90,'2026-06-02 14:08:00'),(390,4,2,2,150,'2026-06-02 14:09:00'),(391,42,2,5,20,'2026-06-02 14:10:16'),(392,42,2,5,63,'2026-06-02 14:11:00'),(393,25,2,3,20,'2026-06-02 14:11:51'),(394,25,2,3,29,'2026-06-02 14:12:00'),(395,25,2,3,89,'2026-06-02 14:13:00'),(396,25,2,3,149,'2026-06-02 14:14:00'),(397,25,2,3,209,'2026-06-02 14:15:00'),(398,38,2,3,20,'2026-06-02 14:15:49'),(399,38,2,3,31,'2026-06-02 14:16:00'),(400,38,2,3,91,'2026-06-02 14:17:00'),(401,38,2,3,151,'2026-06-02 14:18:00'),(402,7,2,2,20,'2026-06-02 14:19:08'),(403,7,2,2,71,'2026-06-02 14:20:00'),(404,7,2,2,131,'2026-06-02 14:21:00'),(405,7,2,2,191,'2026-06-02 14:22:00'),(406,7,2,2,251,'2026-06-02 14:23:00'),(407,20,2,3,20,'2026-06-02 14:23:34'),(408,20,2,3,46,'2026-06-02 14:24:00'),(409,20,2,3,106,'2026-06-02 14:25:00'),(410,23,2,3,20,'2026-06-02 14:25:36'),(411,23,2,3,43,'2026-06-02 14:26:00'),(412,23,2,3,103,'2026-06-02 14:27:00'),(413,23,2,3,163,'2026-06-02 14:28:00'),(414,23,2,3,223,'2026-06-02 14:29:00'),(415,17,2,2,20,'2026-06-02 14:29:35'),(416,17,2,2,44,'2026-06-02 14:30:00'),(417,17,2,2,105,'2026-06-02 14:31:00'),(418,17,2,2,165,'2026-06-02 14:32:00'),(419,25,2,3,20,'2026-06-02 14:32:28'),(420,25,2,3,51,'2026-06-02 14:33:00'),(421,25,2,3,111,'2026-06-02 14:34:00'),(422,25,2,3,171,'2026-06-02 14:35:00'),(423,25,2,3,231,'2026-06-02 14:36:00'),(424,20,2,3,20,'2026-06-02 14:44:04'),(425,20,2,3,75,'2026-06-02 14:45:00'),(426,44,2,5,20,'2026-06-02 14:46:07'),(427,44,2,5,73,'2026-06-02 14:47:00'),(428,25,2,3,20,'2026-06-02 14:47:26'),(429,25,2,3,53,'2026-06-02 14:48:00'),(430,25,2,3,113,'2026-06-02 14:49:00'),(431,25,2,3,174,'2026-06-02 14:50:00'),(432,25,2,3,234,'2026-06-02 14:51:00'),(433,15,2,2,20,'2026-06-02 14:51:24'),(434,15,2,2,56,'2026-06-02 14:52:00'),(435,39,2,3,20,'2026-06-02 14:52:33'),(436,39,2,3,47,'2026-06-02 14:53:00'),(437,39,2,3,52,'2026-06-02 14:53:05'),(438,39,2,3,59,'2026-06-02 14:53:12'),(439,39,2,3,106,'2026-06-02 14:54:00'),(440,39,2,3,166,'2026-06-02 14:55:00'),(441,9,2,2,20,'2026-06-02 14:55:43'),(442,9,2,2,36,'2026-06-02 14:56:00'),(443,9,2,2,89,'2026-06-02 14:56:53'),(444,39,2,3,20,'2026-06-02 14:57:18'),(445,39,2,3,61,'2026-06-02 14:58:00'),(446,13,2,2,20,'2026-06-02 14:58:49'),(447,13,2,2,30,'2026-06-02 14:59:00'),(448,13,2,2,42,'2026-06-02 14:59:11'),(449,13,2,2,90,'2026-06-02 15:00:00'),(450,13,2,2,43,'2026-06-02 15:01:00'),(451,13,2,2,103,'2026-06-02 15:02:00'),(452,37,2,3,20,'2026-06-02 15:03:01'),(453,37,2,3,64,'2026-06-02 15:03:45'),(454,37,2,3,78,'2026-06-02 15:04:00'),(455,37,2,3,138,'2026-06-02 15:05:00'),(456,37,2,3,198,'2026-06-02 15:06:00'),(457,32,2,3,20,'2026-06-02 15:06:50'),(458,32,2,3,30,'2026-06-02 15:07:00'),(459,20,2,3,20,'2026-06-02 15:08:25'),(460,31,2,3,20,'2026-06-02 15:08:58'),(461,31,2,3,21,'2026-06-02 15:09:00'),(462,31,2,3,62,'2026-06-02 15:09:40'),(463,31,2,3,81,'2026-06-02 15:10:00'),(464,31,2,3,141,'2026-06-02 15:11:00'),(465,31,2,3,201,'2026-06-02 15:12:00'),(466,18,2,2,20,'2026-06-02 15:12:25'),(467,18,2,2,55,'2026-06-02 15:13:00'),(468,18,2,2,93,'2026-06-02 15:13:38'),(469,22,2,3,20,'2026-06-02 15:14:15'),(470,22,2,3,65,'2026-06-02 15:15:00'),(471,17,2,2,20,'2026-06-02 15:15:54'),(472,17,2,2,25,'2026-06-02 15:16:00'),(473,17,2,2,86,'2026-06-02 15:17:00'),(474,17,2,2,145,'2026-06-02 15:18:00'),(475,11,2,2,20,'2026-06-02 15:18:47'),(476,11,2,2,32,'2026-06-02 15:19:00'),(477,37,2,3,20,'2026-06-02 15:19:51'),(478,37,2,3,24,'2026-06-02 15:19:56'),(479,37,2,3,28,'2026-06-02 15:20:00'),(480,37,NULL,3,38,'2026-06-02 15:20:10'),(481,37,NULL,3,40,'2026-06-02 15:20:12'),(482,37,NULL,3,40,'2026-06-02 15:20:12'),(483,37,NULL,3,43,'2026-06-02 15:20:16'),(484,37,NULL,3,47,'2026-06-02 15:20:20'),(485,37,NULL,3,49,'2026-06-02 15:20:21'),(486,37,NULL,3,49,'2026-06-02 15:20:22'),(487,37,NULL,3,87,'2026-06-02 15:21:00'),(488,31,NULL,3,20,'2026-06-02 15:21:46'),(489,31,NULL,3,34,'2026-06-02 15:22:00'),(490,31,NULL,3,49,'2026-06-02 15:22:15'),(491,31,NULL,3,56,'2026-06-02 15:22:22'),(492,31,NULL,3,56,'2026-06-02 15:22:23'),(493,31,NULL,3,57,'2026-06-02 15:22:23'),(494,31,NULL,3,69,'2026-06-02 15:22:36'),(495,31,NULL,3,81,'2026-06-02 15:22:48'),(496,31,NULL,3,93,'2026-06-02 15:23:00'),(497,31,NULL,3,98,'2026-06-02 15:23:05'),(498,31,NULL,3,98,'2026-06-02 15:23:06'),(499,31,NULL,3,106,'2026-06-02 15:23:14'),(500,31,NULL,3,108,'2026-06-02 15:23:15'),(501,31,NULL,3,109,'2026-06-02 15:23:17'),(502,31,NULL,3,110,'2026-06-02 15:23:18'),(503,31,NULL,3,110,'2026-06-02 15:23:18'),(504,31,NULL,3,151,'2026-06-02 15:24:00'),(505,31,NULL,3,169,'2026-06-02 15:24:17'),(506,31,NULL,3,173,'2026-06-02 15:24:22'),(507,31,NULL,3,174,'2026-06-02 15:24:23'),(508,31,NULL,3,180,'2026-06-02 15:24:29'),(509,31,NULL,3,181,'2026-06-02 15:24:30'),(510,31,NULL,3,181,'2026-06-02 15:24:30'),(511,31,NULL,3,182,'2026-06-02 15:24:32'),(512,31,2,3,193,'2026-06-02 15:24:42'),(513,25,2,3,20,'2026-06-02 15:25:15'),(514,25,2,3,64,'2026-06-02 15:26:00'),(515,25,2,3,109,'2026-06-02 15:26:45'),(516,25,2,3,109,'2026-06-02 15:26:45'),(517,25,2,3,110,'2026-06-02 15:26:46'),(518,25,2,3,123,'2026-06-02 15:27:00'),(519,25,2,3,125,'2026-06-02 15:27:01'),(520,25,2,3,126,'2026-06-02 15:27:02'),(521,25,2,3,126,'2026-06-02 15:27:03'),(522,25,2,3,126,'2026-06-02 15:27:03'),(523,25,2,3,127,'2026-06-02 15:27:04'),(524,25,2,3,169,'2026-06-02 15:27:47'),(525,25,2,3,177,'2026-06-02 15:27:54'),(526,25,2,3,182,'2026-06-02 15:28:00'),(527,25,2,3,189,'2026-06-02 15:28:06'),(528,25,2,3,237,'2026-06-02 15:28:55'),(529,25,2,3,237,'2026-06-02 15:28:56'),(530,7,2,2,20,'2026-06-02 15:29:17'),(531,7,2,2,58,'2026-06-02 15:29:56'),(532,7,2,2,62,'2026-06-02 15:30:00'),(533,7,2,2,120,'2026-06-02 15:30:57'),(534,7,2,2,122,'2026-06-02 15:31:00'),(535,7,2,2,161,'2026-06-02 15:31:39'),(536,7,2,2,182,'2026-06-02 15:32:00'),(537,7,2,2,242,'2026-06-02 15:33:00'),(538,11,2,2,20,'2026-06-02 15:33:43'),(539,11,2,2,36,'2026-06-02 15:34:00'),(540,11,2,2,40,'2026-06-02 15:34:04'),(541,11,2,2,44,'2026-06-02 15:34:09'),(542,11,2,2,45,'2026-06-02 15:34:10'),(543,11,2,2,46,'2026-06-02 15:34:10'),(544,32,2,3,20,'2026-06-02 15:34:48'),(545,32,2,3,31,'2026-06-02 15:35:00'),(546,32,2,3,91,'2026-06-02 15:36:00'),(547,32,2,3,151,'2026-06-02 15:37:00'),(548,36,2,3,20,'2026-06-02 15:38:15'),(549,36,2,3,65,'2026-06-02 15:39:00'),(550,20,2,3,20,'2026-06-02 15:39:45'),(551,20,2,3,35,'2026-06-02 15:40:00'),(552,20,2,3,95,'2026-06-02 15:41:00'),(553,20,2,3,106,'2026-06-02 15:41:12'),(554,20,2,3,117,'2026-06-02 15:41:23'),(555,10,2,2,20,'2026-06-02 15:41:48'),(556,10,2,2,31,'2026-06-02 15:42:00'),(557,10,2,2,91,'2026-06-02 15:43:00'),(558,10,2,2,151,'2026-06-02 15:44:00'),(559,12,2,2,20,'2026-06-02 15:44:33'),(560,12,2,2,47,'2026-06-02 15:45:00'),(561,17,2,2,20,'2026-06-02 15:45:34'),(562,17,2,2,45,'2026-06-02 15:46:00'),(563,17,2,2,105,'2026-06-02 15:47:00'),(564,17,2,2,165,'2026-06-02 15:48:00'),(565,14,2,2,20,'2026-06-02 15:48:28'),(566,14,2,2,52,'2026-06-02 15:49:00'),(567,14,2,2,112,'2026-06-02 15:50:00'),(568,14,2,2,171,'2026-06-02 15:51:00'),(569,41,2,3,20,'2026-06-02 15:51:49'),(570,41,2,3,31,'2026-06-02 15:52:00'),(571,41,2,3,91,'2026-06-02 15:53:00'),(572,41,2,3,150,'2026-06-02 15:54:00'),(573,41,2,3,211,'2026-06-02 15:55:00'),(574,36,2,3,20,'2026-06-02 15:55:30'),(575,36,2,3,49,'2026-06-02 15:56:00'),(576,36,2,3,109,'2026-06-02 15:57:00'),(577,36,2,3,169,'2026-06-02 15:58:00'),(578,36,2,3,216,'2026-06-02 15:58:47'),(579,36,2,3,229,'2026-06-02 15:59:00'),(580,22,2,3,20,'2026-06-02 15:59:24'),(581,22,2,3,20,'2026-06-02 15:59:25'),(582,22,2,3,55,'2026-06-02 16:00:00'),(583,22,2,3,64,'2026-06-02 16:00:09'),(584,22,2,3,65,'2026-06-02 16:00:10'),(585,22,2,3,65,'2026-06-02 16:00:10'),(586,22,2,3,65,'2026-06-02 16:00:11'),(587,37,2,3,20,'2026-06-02 16:01:05'),(588,37,2,3,27,'2026-06-02 16:01:12'),(589,37,2,3,27,'2026-06-02 16:01:13'),(590,37,2,3,28,'2026-06-02 16:01:14'),(591,37,2,3,74,'2026-06-02 16:02:00'),(592,37,2,3,108,'2026-06-02 16:02:34'),(593,37,2,3,133,'2026-06-02 16:03:00'),(594,37,2,3,193,'2026-06-02 16:04:00'),(595,37,2,3,33,'2026-06-02 16:05:00'),(596,37,2,3,50,'2026-06-02 16:05:17'),(597,37,2,3,93,'2026-06-02 16:06:00'),(598,37,2,3,150,'2026-06-02 16:06:57'),(599,37,2,3,152,'2026-06-02 16:07:00'),(600,37,2,3,195,'2026-06-02 20:14:04'),(601,32,2,3,20,'2026-06-02 20:14:59'),(602,32,2,3,21,'2026-06-02 20:15:00'),(603,32,2,3,81,'2026-06-02 20:16:00'),(604,32,2,3,141,'2026-06-02 20:17:00'),(605,32,2,3,201,'2026-06-02 20:18:00'),(606,36,2,3,20,'2026-06-02 20:18:25'),(607,36,2,3,54,'2026-06-02 20:19:00'),(608,36,2,3,61,'2026-06-02 20:19:07'),(609,36,NULL,3,92,'2026-06-02 20:19:38'),(610,36,NULL,3,95,'2026-06-02 20:19:41'),(611,36,NULL,3,113,'2026-06-02 20:20:00'),(612,36,NULL,3,146,'2026-06-02 20:20:33'),(613,36,NULL,3,150,'2026-06-02 20:20:37'),(614,36,NULL,3,173,'2026-06-02 20:21:00'),(615,36,NULL,3,182,'2026-06-02 20:21:09'),(616,36,5,3,198,'2026-06-02 20:21:25'),(617,36,5,3,232,'2026-06-02 20:22:00'),(618,37,5,3,20,'2026-06-02 20:22:20'),(619,37,5,3,59,'2026-06-02 20:23:00'),(620,37,5,3,119,'2026-06-02 20:24:00'),(621,37,5,3,179,'2026-06-02 20:25:00'),(622,32,5,3,20,'2026-06-02 20:26:09'),(623,32,5,3,70,'2026-06-02 20:27:00'),(624,32,5,3,118,'2026-06-02 20:27:48'),(625,32,5,3,130,'2026-06-02 20:28:00'),(626,32,5,3,190,'2026-06-02 20:29:00'),(627,36,5,3,20,'2026-06-02 20:29:36'),(628,36,5,3,43,'2026-06-02 20:30:00'),(629,36,5,3,103,'2026-06-02 20:31:00'),(630,36,5,3,163,'2026-06-02 20:32:00'),(631,36,5,3,223,'2026-06-02 20:33:00'),(632,37,5,3,20,'2026-06-02 20:33:29'),(633,37,5,3,50,'2026-06-02 20:34:00'),(634,37,5,3,110,'2026-06-02 20:35:00'),(635,37,5,3,170,'2026-06-02 20:36:00'),(636,37,5,3,219,'2026-06-02 20:36:50'),(637,32,5,3,20,'2026-06-02 20:37:19'),(638,32,5,3,61,'2026-06-02 20:38:00'),(639,32,5,3,121,'2026-06-02 20:39:00'),(640,32,5,3,180,'2026-06-02 20:40:00'),(641,36,5,3,20,'2026-06-02 20:40:46'),(642,36,5,3,34,'2026-06-02 20:41:00'),(643,36,5,3,64,'2026-06-02 20:41:31'),(644,36,5,3,68,'2026-06-02 20:41:34'),(645,36,5,3,73,'2026-06-02 20:41:40'),(646,36,5,3,78,'2026-06-02 20:41:45'),(647,36,5,3,93,'2026-06-02 20:42:00'),(648,36,5,3,153,'2026-06-02 20:43:00'),(649,36,5,3,213,'2026-06-02 20:44:00'),(650,37,5,3,20,'2026-06-02 20:44:40'),(651,37,5,3,39,'2026-06-02 20:45:00'),(652,37,5,3,99,'2026-06-02 20:46:00'),(653,37,5,3,159,'2026-06-02 20:47:00'),(654,37,5,3,184,'2026-06-02 20:47:24'),(655,37,5,3,219,'2026-06-02 20:48:00'),(656,32,5,3,20,'2026-06-02 20:48:29'),(657,32,5,3,50,'2026-06-02 20:49:00'),(658,32,5,3,93,'2026-06-02 20:49:43'),(659,32,5,3,110,'2026-06-02 20:50:00'),(660,32,5,3,170,'2026-06-02 20:51:00'),(661,36,5,3,20,'2026-06-02 20:51:57'),(662,36,5,3,23,'2026-06-02 20:52:00'),(663,36,5,3,83,'2026-06-02 20:53:00'),(664,36,5,3,143,'2026-06-02 20:54:00'),(665,36,5,3,203,'2026-06-02 20:55:00'),(666,37,5,3,20,'2026-06-02 20:55:50'),(667,37,5,3,30,'2026-06-02 20:56:00'),(668,37,5,3,90,'2026-06-02 20:57:00'),(669,37,5,3,150,'2026-06-02 20:58:00'),(670,37,5,3,210,'2026-06-02 20:59:00'),(671,32,5,3,20,'2026-06-02 20:59:38'),(672,32,5,3,41,'2026-06-02 21:00:00'),(673,32,5,3,101,'2026-06-02 21:01:00'),(674,32,5,3,146,'2026-06-02 21:01:45'),(675,32,5,3,160,'2026-06-02 21:02:00'),(676,36,5,3,20,'2026-06-02 21:03:06'),(677,36,5,3,74,'2026-06-02 21:04:00'),(678,36,5,3,134,'2026-06-02 21:05:00'),(679,36,5,3,169,'2026-06-02 21:05:36'),(680,36,5,3,193,'2026-06-02 21:06:00'),(681,37,5,3,20,'2026-06-02 21:06:59'),(682,37,5,3,20,'2026-06-02 21:07:00'),(683,37,5,3,80,'2026-06-02 21:08:00'),(684,37,5,3,140,'2026-06-02 21:09:00'),(685,37,5,3,171,'2026-06-02 21:09:31'),(686,37,5,3,200,'2026-06-02 21:10:00'),(687,32,5,3,20,'2026-06-02 21:10:49'),(688,32,5,3,30,'2026-06-02 21:11:00'),(689,32,5,3,90,'2026-06-02 21:12:00'),(690,32,5,3,150,'2026-06-02 21:13:00'),(691,36,5,3,20,'2026-06-02 21:14:16'),(692,36,5,3,42,'2026-06-02 21:14:38'),(693,36,5,3,54,'2026-06-02 21:14:51'),(694,36,5,3,63,'2026-06-02 21:15:00'),(695,36,5,3,122,'2026-06-02 21:16:00'),(696,36,5,3,183,'2026-06-02 21:17:00'),(697,36,NULL,3,193,'2026-06-02 21:17:10'),(698,36,NULL,3,195,'2026-06-02 21:17:12'),(699,37,2,3,20,'2026-06-02 21:18:11'),(700,37,2,3,35,'2026-06-02 21:18:27'),(701,37,2,3,68,'2026-06-02 21:19:00'),(702,37,2,3,128,'2026-06-02 21:20:00'),(703,37,2,3,188,'2026-06-02 21:21:00'),(704,32,2,3,20,'2026-06-02 21:22:00'),(705,32,2,3,79,'2026-06-02 21:23:00'),(706,32,2,3,139,'2026-06-02 21:24:00'),(707,32,2,3,200,'2026-06-02 21:25:00'),(708,36,2,3,20,'2026-06-02 21:25:27'),(709,36,2,3,53,'2026-06-02 21:26:00'),(710,36,2,3,113,'2026-06-02 21:27:00'),(711,36,2,3,173,'2026-06-02 21:28:00'),(712,37,2,3,20,'2026-06-02 21:29:20'),(713,37,2,3,60,'2026-06-02 21:30:00'),(714,37,2,3,120,'2026-06-02 21:31:00'),(715,37,2,3,170,'2026-06-02 21:31:50'),(716,37,2,3,179,'2026-06-02 21:32:00'),(717,32,2,3,20,'2026-06-02 21:33:09'),(718,32,2,3,70,'2026-06-02 21:34:00'),(719,13,2,2,20,'2026-06-02 23:08:43'),(720,13,2,2,36,'2026-06-02 23:09:00'),(721,13,2,2,97,'2026-06-02 23:10:00'),(722,11,2,2,20,'2026-06-02 23:10:30'),(723,11,2,2,50,'2026-06-02 23:11:00'),(724,32,2,3,20,'2026-06-02 23:11:26'),(725,32,2,3,34,'2026-06-02 23:11:41'),(726,32,2,3,53,'2026-06-02 23:12:00'),(727,32,2,3,113,'2026-06-02 23:13:00'),(728,32,2,3,173,'2026-06-02 23:14:00'),(729,14,2,2,20,'2026-06-02 23:14:53'),(730,14,2,2,26,'2026-06-02 23:15:00'),(731,14,2,2,86,'2026-06-02 23:16:00'),(732,14,2,2,146,'2026-06-02 23:17:00'),(733,18,2,2,20,'2026-06-02 23:18:14'),(734,18,2,2,65,'2026-06-02 23:19:00'),(735,16,2,2,20,'2026-06-02 23:20:03'),(736,16,2,2,76,'2026-06-02 23:21:00'),(737,16,2,2,136,'2026-06-02 23:22:00'),(738,11,2,2,20,'2026-06-02 23:22:56'),(739,11,2,2,24,'2026-06-02 23:23:00'),(740,10,2,2,20,'2026-06-02 23:23:59'),(741,10,2,2,20,'2026-06-02 23:24:00'),(742,10,2,2,80,'2026-06-02 23:25:00'),(743,31,2,3,20,'2026-06-02 23:26:06'),(744,31,2,3,73,'2026-06-02 23:27:00'),(745,31,2,3,133,'2026-06-02 23:28:00'),(746,31,2,3,141,'2026-06-02 23:28:07'),(747,31,2,3,142,'2026-06-02 23:28:08'),(748,31,2,3,193,'2026-06-02 23:29:00'),(749,6,2,2,20,'2026-06-02 23:29:33'),(750,6,2,2,47,'2026-06-02 23:30:00'),(751,6,2,2,107,'2026-06-02 23:31:00'),(752,6,2,2,167,'2026-06-02 23:32:00'),(753,12,2,2,20,'2026-06-02 23:33:11'),(754,43,2,5,20,'2026-06-02 23:34:13'),(755,43,2,5,66,'2026-06-02 23:35:00'),(756,13,2,2,20,'2026-06-02 23:35:47'),(757,13,2,2,32,'2026-06-02 23:36:00'),(758,24,2,3,20,'2026-06-02 23:37:00'),(759,24,2,3,28,'2026-06-02 23:37:08'),(760,24,2,3,37,'2026-06-02 23:37:17'),(761,24,2,3,79,'2026-06-02 23:38:00'),(762,24,2,3,138,'2026-06-02 23:38:59'),(763,24,2,3,138,'2026-06-02 23:39:00'),(764,24,2,3,148,'2026-06-02 23:39:09'),(765,24,2,3,149,'2026-06-02 23:39:11'),(766,37,2,3,20,'2026-06-02 23:40:15'),(767,37,2,3,64,'2026-06-02 23:41:00'),(768,37,2,3,124,'2026-06-02 23:42:00'),(769,37,2,3,125,'2026-06-02 23:42:01'),(770,37,2,3,136,'2026-06-02 23:42:12'),(771,37,2,3,138,'2026-06-02 23:42:14'),(772,37,2,3,139,'2026-06-02 23:42:15'),(773,37,2,3,161,'2026-06-02 23:42:38'),(774,37,2,3,183,'2026-06-02 23:43:00'),(775,18,2,2,20,'2026-06-02 23:44:06'),(776,18,2,2,50,'2026-06-02 23:44:36'),(777,18,2,2,51,'2026-06-02 23:44:37'),(778,18,2,2,73,'2026-06-02 23:45:00'),(779,39,2,3,20,'2026-06-02 23:45:55'),(780,39,2,3,24,'2026-06-02 23:46:00'),(781,39,2,3,84,'2026-06-02 23:47:00'),(782,39,2,3,98,'2026-06-02 23:47:14'),(783,39,2,3,144,'2026-06-02 23:48:00'),(784,39,2,3,204,'2026-06-02 23:49:00'),(785,32,2,3,20,'2026-06-02 23:50:12'),(786,32,2,3,67,'2026-06-02 23:51:00'),(787,42,2,5,20,'2026-06-02 23:51:32'),(788,42,2,5,47,'2026-06-02 23:52:00'),(789,8,2,2,20,'2026-06-02 23:53:07'),(790,8,2,2,72,'2026-06-02 23:54:00'),(791,8,2,2,132,'2026-06-02 23:55:00'),(792,8,2,2,160,'2026-06-02 23:55:28'),(793,8,2,2,176,'2026-06-02 23:55:44'),(794,23,2,3,20,'2026-06-02 23:56:07'),(795,23,2,3,71,'2026-06-02 23:57:05'),(796,23,2,3,126,'2026-06-02 23:58:00'),(797,37,2,3,20,'2026-06-02 23:59:07'),(798,37,2,3,72,'2026-06-03 00:00:00'),(799,37,2,3,132,'2026-06-03 00:01:00'),(800,37,2,3,192,'2026-06-03 00:02:00'),(801,32,2,3,20,'2026-06-03 00:02:56'),(802,32,2,3,23,'2026-06-03 00:03:00'),(803,32,2,3,58,'2026-06-03 00:03:35'),(804,32,2,3,83,'2026-06-03 00:04:00'),(805,32,2,3,93,'2026-06-03 00:04:10'),(806,32,2,3,105,'2026-06-03 00:04:22'),(807,32,2,3,142,'2026-06-03 00:05:00'),(808,32,2,3,202,'2026-06-03 00:06:00'),(809,36,2,3,20,'2026-06-03 00:06:24'),(810,36,2,3,56,'2026-06-03 00:07:00'),(811,36,2,3,115,'2026-06-03 00:08:00'),(812,36,2,3,175,'2026-06-03 00:09:00'),(813,37,2,3,20,'2026-06-03 00:10:17'),(814,37,2,3,62,'2026-06-03 00:11:00'),(815,37,2,3,122,'2026-06-03 00:12:00'),(816,37,2,3,182,'2026-06-03 00:13:00'),(817,32,2,3,20,'2026-06-03 00:14:06'),(818,32,2,3,73,'2026-06-03 00:15:00'),(819,32,2,3,133,'2026-06-03 00:16:00'),(820,32,2,3,193,'2026-06-03 00:17:00'),(821,36,2,3,20,'2026-06-03 00:17:33'),(822,36,2,3,47,'2026-06-03 00:18:00'),(823,36,2,3,48,'2026-06-03 00:18:01'),(824,36,2,3,106,'2026-06-03 00:19:00'),(825,36,2,3,166,'2026-06-03 00:20:00'),(826,36,2,3,186,'2026-06-03 00:20:19'),(827,36,2,3,217,'2026-06-03 00:20:51'),(828,36,2,3,226,'2026-06-03 00:21:00'),(829,37,2,3,20,'2026-06-03 00:21:27'),(830,37,2,3,52,'2026-06-03 00:22:00'),(831,37,2,3,112,'2026-06-03 00:23:00'),(832,37,2,3,172,'2026-06-03 00:24:00'),(833,32,2,3,20,'2026-06-03 00:25:16'),(834,32,2,3,38,'2026-06-03 00:25:35'),(835,32,2,3,63,'2026-06-03 00:26:00'),(836,32,2,3,123,'2026-06-03 00:27:00'),(837,32,2,3,183,'2026-06-03 00:28:00'),(838,36,2,3,20,'2026-06-03 00:28:43'),(839,36,2,3,31,'2026-06-03 00:28:55'),(840,36,2,3,36,'2026-06-03 00:29:00'),(841,36,2,3,96,'2026-06-03 00:30:00'),(842,36,2,3,156,'2026-06-03 00:31:00'),(843,36,2,3,216,'2026-06-03 00:32:00'),(844,37,2,3,20,'2026-06-03 00:32:37'),(845,37,2,3,43,'2026-06-03 00:33:00'),(846,37,2,3,102,'2026-06-03 00:34:00'),(847,37,2,3,162,'2026-06-03 00:35:00'),(848,37,2,3,222,'2026-06-03 00:36:00'),(849,32,2,3,20,'2026-06-03 00:36:26'),(850,32,2,3,54,'2026-06-03 00:37:00'),(851,32,2,3,107,'2026-06-03 00:37:54'),(852,32,2,3,113,'2026-06-03 00:38:00'),(853,32,2,3,158,'2026-06-03 00:38:44'),(854,32,2,3,161,'2026-06-03 00:38:48'),(855,32,2,3,167,'2026-06-03 00:38:54'),(856,32,2,3,173,'2026-06-03 00:39:00'),(857,32,2,3,203,'2026-06-03 00:39:30'),(858,36,2,3,20,'2026-06-03 00:39:54'),(859,36,2,3,26,'2026-06-03 00:40:00'),(860,36,2,3,86,'2026-06-03 00:41:00'),(861,36,2,3,146,'2026-06-03 00:42:00'),(862,36,2,3,206,'2026-06-03 00:43:00'),(863,37,2,3,20,'2026-06-03 00:43:47'),(864,37,2,3,33,'2026-06-03 00:44:00'),(865,37,2,3,93,'2026-06-03 00:45:00'),(866,37,2,3,128,'2026-06-03 00:45:35'),(867,37,2,3,152,'2026-06-03 00:46:00'),(868,37,2,3,212,'2026-06-03 00:47:00'),(869,32,2,3,20,'2026-06-03 00:47:36'),(870,32,2,3,43,'2026-06-03 00:48:00'),(871,32,2,3,103,'2026-06-03 00:49:00'),(872,32,2,3,163,'2026-06-03 00:50:00'),(873,36,2,3,20,'2026-06-03 00:51:03'),(874,36,2,3,45,'2026-06-03 00:51:29'),(875,36,2,3,76,'2026-06-03 00:52:00'),(876,36,2,3,136,'2026-06-03 00:53:00'),(877,36,2,3,153,'2026-06-03 00:53:17'),(878,36,2,3,196,'2026-06-03 00:54:00'),(879,37,2,3,20,'2026-06-03 00:54:58'),(880,37,2,3,22,'2026-06-03 00:55:00'),(881,37,2,3,82,'2026-06-03 00:56:00'),(882,37,2,3,142,'2026-06-03 00:57:00'),(883,37,2,3,202,'2026-06-03 00:58:00'),(884,32,2,3,20,'2026-06-03 00:58:46'),(885,32,2,3,33,'2026-06-03 00:59:01'),(886,32,2,3,93,'2026-06-03 01:00:00'),(887,32,2,3,153,'2026-06-03 01:01:00'),(888,36,2,3,20,'2026-06-03 01:02:13'),(889,36,2,3,66,'2026-06-03 01:03:00'),(890,36,2,3,126,'2026-06-03 01:04:00'),(891,36,2,3,186,'2026-06-03 01:05:00'),(892,37,2,3,20,'2026-06-03 01:06:06'),(893,37,2,3,73,'2026-06-03 01:07:00'),(894,37,2,3,133,'2026-06-03 01:08:00'),(895,37,2,3,193,'2026-06-03 01:09:00'),(896,32,2,3,20,'2026-06-03 01:09:55'),(897,32,2,3,24,'2026-06-03 01:10:00'),(898,32,2,3,84,'2026-06-03 01:11:00'),(899,32,2,3,144,'2026-06-03 01:12:00'),(900,32,2,3,205,'2026-06-03 01:13:00'),(901,36,2,3,20,'2026-06-03 01:13:22'),(902,36,2,3,58,'2026-06-03 01:14:00'),(903,36,2,3,60,'2026-06-03 01:14:02'),(904,36,2,3,74,'2026-06-03 01:14:16'),(905,36,2,3,117,'2026-06-03 01:15:00'),(906,36,2,3,141,'2026-06-03 01:15:23'),(907,36,2,3,177,'2026-06-03 01:16:00'),(908,37,NULL,3,20,'2026-06-03 01:17:17'),(909,37,NULL,3,22,'2026-06-03 01:17:19'),(910,37,NULL,3,27,'2026-06-03 01:17:44'),(911,37,NULL,3,28,'2026-06-03 01:17:46'),(912,37,2,3,38,'2026-06-03 01:17:56'),(913,37,2,3,41,'2026-06-03 01:18:00'),(914,37,2,3,102,'2026-06-03 01:19:00'),(915,37,2,3,161,'2026-06-03 01:20:00'),(916,37,2,3,221,'2026-06-03 01:21:00'),(917,32,2,3,20,'2026-06-03 01:21:27'),(918,32,2,3,52,'2026-06-03 01:22:00'),(919,32,2,3,65,'2026-06-03 01:22:13'),(920,32,2,3,80,'2026-06-03 01:22:28'),(921,32,2,3,112,'2026-06-03 01:23:00'),(922,32,2,3,172,'2026-06-03 01:24:00'),(923,36,2,3,20,'2026-06-03 01:24:54'),(924,36,2,3,25,'2026-06-03 01:25:00'),(925,36,2,3,85,'2026-06-03 01:26:00'),(926,36,2,3,145,'2026-06-03 01:27:00'),(927,36,2,3,206,'2026-06-03 01:28:00'),(928,36,2,3,232,'2026-06-03 01:28:26'),(929,37,2,3,20,'2026-06-03 01:28:49'),(930,37,2,3,31,'2026-06-03 01:29:00'),(931,37,2,3,91,'2026-06-03 01:30:00'),(932,37,2,3,151,'2026-06-03 01:31:00'),(933,37,2,3,211,'2026-06-03 01:32:00'),(934,32,2,3,20,'2026-06-03 01:32:38'),(935,32,2,3,41,'2026-06-03 01:33:00'),(936,32,2,3,102,'2026-06-03 01:34:00'),(937,32,2,3,125,'2026-06-03 01:34:23'),(938,32,2,3,135,'2026-06-03 01:34:33'),(939,32,2,3,136,'2026-06-03 01:34:34'),(940,32,2,3,137,'2026-06-03 01:34:36'),(941,32,2,3,151,'2026-06-03 01:34:50'),(942,32,2,3,161,'2026-06-03 01:35:00'),(943,32,NULL,3,162,'2026-06-03 01:35:02'),(944,32,NULL,3,165,'2026-06-03 01:35:04'),(945,32,NULL,3,170,'2026-06-03 01:35:32'),(946,32,NULL,3,178,'2026-06-03 01:35:40'),(947,32,2,3,185,'2026-06-03 01:35:47'),(948,32,2,3,197,'2026-06-03 01:36:00'),(949,36,2,3,20,'2026-06-03 01:36:29'),(950,36,2,3,51,'2026-06-03 01:37:00'),(951,36,2,3,111,'2026-06-03 01:38:00'),(952,36,2,3,171,'2026-06-03 01:39:00'),(953,36,2,3,231,'2026-06-03 01:40:00'),(954,37,2,3,20,'2026-06-03 01:40:22'),(955,37,2,3,57,'2026-06-03 01:41:00'),(956,37,2,3,118,'2026-06-03 01:42:00'),(957,37,2,3,177,'2026-06-03 01:43:00'),(958,32,2,3,20,'2026-06-03 01:44:11'),(959,32,2,3,69,'2026-06-03 01:45:00'),(960,32,2,3,79,'2026-06-03 01:45:10'),(961,32,2,3,84,'2026-06-03 01:45:16'),(962,32,2,3,85,'2026-06-03 01:45:16'),(963,32,2,3,86,'2026-06-03 01:45:17'),(964,32,2,3,87,'2026-06-03 01:45:19'),(965,32,2,3,94,'2026-06-03 01:45:26'),(966,32,2,3,101,'2026-06-03 01:45:33'),(967,32,2,3,105,'2026-06-03 01:45:37'),(968,32,NULL,3,114,'2026-06-03 01:45:47'),(969,32,NULL,3,117,'2026-06-03 01:45:49'),(970,32,NULL,3,123,'2026-06-03 01:46:49'),(971,32,NULL,3,125,'2026-06-03 01:46:52'),(972,32,2,3,133,'2026-06-03 01:47:00'),(973,32,2,3,133,'2026-06-03 01:47:00'),(974,32,2,3,193,'2026-06-03 01:48:00'),(975,36,2,3,20,'2026-06-03 01:48:34'),(976,36,2,3,46,'2026-06-03 01:49:00'),(977,36,2,3,106,'2026-06-03 01:50:00'),(978,36,2,3,166,'2026-06-03 01:51:00'),(979,36,2,3,226,'2026-06-03 01:52:00'),(980,37,2,3,20,'2026-06-03 01:52:27'),(981,37,2,3,53,'2026-06-03 01:53:00'),(982,37,2,3,55,'2026-06-03 01:53:02'),(983,37,2,3,56,'2026-06-03 01:53:03'),(984,37,2,3,57,'2026-06-03 01:53:04'),(985,37,2,3,112,'2026-06-03 01:54:00'),(986,37,NULL,3,154,'2026-06-03 01:54:41'),(987,37,NULL,3,156,'2026-06-03 01:54:43'),(988,37,NULL,3,162,'2026-06-03 01:55:44'),(989,37,NULL,3,164,'2026-06-03 01:55:46'),(990,37,2,3,170,'2026-06-03 01:55:52'),(991,37,2,3,177,'2026-06-03 01:56:00'),(992,32,2,3,20,'2026-06-03 01:57:11'),(993,32,2,3,68,'2026-06-03 01:58:00'),(994,32,2,3,128,'2026-06-03 01:59:00'),(995,32,2,3,188,'2026-06-03 02:00:00'),(996,36,2,3,20,'2026-06-03 02:00:38'),(997,36,2,3,20,'2026-06-03 02:00:38'),(998,36,2,3,42,'2026-06-03 02:01:00'),(999,36,2,3,50,'2026-06-03 02:01:09'),(1000,36,2,3,54,'2026-06-03 02:01:13'),(1001,36,2,3,101,'2026-06-03 02:02:00'),(1002,36,2,3,161,'2026-06-03 02:03:00'),(1003,36,2,3,221,'2026-06-03 02:04:00'),(1004,37,2,3,20,'2026-06-03 02:04:32'),(1005,37,2,3,48,'2026-06-03 02:05:00'),(1006,37,2,3,107,'2026-06-03 02:06:00'),(1007,37,2,3,167,'2026-06-03 02:07:00'),(1008,37,2,3,228,'2026-06-03 02:08:00'),(1009,32,2,3,20,'2026-06-03 02:08:21'),(1010,32,2,3,59,'2026-06-03 02:09:00'),(1011,32,2,3,119,'2026-06-03 02:10:00'),(1012,32,2,3,174,'2026-06-03 02:10:55'),(1013,32,2,3,178,'2026-06-03 02:11:00'),(1014,36,2,3,20,'2026-06-03 02:11:48'),(1015,36,2,3,21,'2026-06-03 09:08:05'),(1016,36,2,3,64,'2026-06-03 09:25:36'),(1017,36,NULL,3,64,'2026-06-03 09:25:41'),(1018,36,NULL,3,64,'2026-06-03 09:25:43'),(1019,36,2,3,64,'2026-06-03 09:25:52'),(1020,36,2,3,64,'2026-06-03 09:44:45'),(1021,36,2,3,64,'2026-06-03 09:45:52'),(1022,36,2,3,64,'2026-06-03 09:53:21'),(1023,36,2,3,64,'2026-06-03 10:01:13'),(1024,36,2,3,64,'2026-06-03 10:04:38'),(1025,32,2,3,20,'2026-06-03 10:05:22'),(1026,32,2,3,57,'2026-06-03 10:06:00'),(1027,32,2,3,117,'2026-06-03 10:07:00'),(1028,32,2,3,177,'2026-06-03 10:08:00'),(1029,7,2,2,20,'2026-06-03 10:08:49'),(1030,7,2,2,30,'2026-06-03 10:09:00'),(1031,7,2,2,90,'2026-06-03 10:10:00'),(1032,7,2,2,150,'2026-06-03 10:11:00'),(1033,7,2,2,210,'2026-06-03 10:12:00'),(1034,26,2,3,20,'2026-06-03 10:13:15'),(1035,26,2,3,65,'2026-06-03 10:14:00'),(1036,26,2,3,67,'2026-06-03 10:14:02'),(1037,26,2,3,124,'2026-06-03 10:15:00'),(1038,26,2,3,184,'2026-06-03 10:16:00'),(1039,26,2,3,207,'2026-06-03 10:16:23'),(1040,36,2,3,20,'2026-06-03 10:16:55'),(1041,36,2,3,24,'2026-06-03 10:17:00'),(1042,36,2,3,84,'2026-06-03 10:18:00'),(1043,36,2,3,144,'2026-06-03 10:19:00'),(1044,36,2,3,204,'2026-06-03 10:20:00'),(1045,10,2,2,20,'2026-06-03 10:20:48'),(1046,10,2,2,31,'2026-06-03 10:21:00'),(1047,10,2,2,91,'2026-06-03 10:22:00'),(1048,10,2,2,151,'2026-06-03 10:23:00'),(1049,10,2,2,162,'2026-06-03 10:23:11'),(1050,16,2,2,20,'2026-06-03 10:23:34'),(1051,16,2,2,37,'2026-06-03 10:23:52'),(1052,16,2,2,45,'2026-06-03 10:24:00'),(1053,16,2,2,105,'2026-06-03 10:25:00'),(1054,16,2,2,113,'2026-06-03 10:25:09'),(1055,16,2,2,138,'2026-06-03 10:25:33'),(1056,16,2,2,144,'2026-06-03 10:25:40'),(1057,16,2,2,146,'2026-06-03 10:25:42'),(1058,16,2,2,151,'2026-06-03 10:25:47'),(1059,16,2,2,164,'2026-06-03 10:26:00'),(1060,43,2,5,20,'2026-06-03 10:26:29'),(1061,43,2,5,51,'2026-06-03 10:27:00'),(1062,41,2,3,20,'2026-06-03 10:28:03'),(1063,41,2,3,77,'2026-06-03 10:29:00'),(1064,41,2,3,137,'2026-06-03 10:30:00'),(1065,41,2,3,197,'2026-06-03 10:31:00'),(1066,41,2,3,223,'2026-06-03 10:31:26'),(1067,41,2,3,235,'2026-06-03 10:31:39'),(1068,3,2,2,20,'2026-06-03 10:32:00'),(1069,3,2,2,79,'2026-06-03 10:33:00'),(1070,3,2,2,108,'2026-06-03 10:33:29'),(1071,3,2,2,111,'2026-06-03 10:33:31'),(1072,3,2,2,113,'2026-06-03 10:33:34'),(1073,3,2,2,115,'2026-06-03 10:33:36'),(1074,3,2,2,117,'2026-06-03 10:33:38'),(1075,3,2,2,138,'2026-06-03 10:34:00'),(1076,5,2,2,20,'2026-06-03 10:34:25'),(1077,5,2,2,54,'2026-06-03 10:35:00'),(1078,5,2,2,87,'2026-06-03 10:35:33'),(1079,5,2,2,93,'2026-06-03 10:35:39'),(1080,5,2,2,95,'2026-06-03 10:35:41'),(1081,5,2,2,97,'2026-06-03 10:35:43'),(1082,5,2,2,113,'2026-06-03 10:36:00'),(1083,5,2,2,173,'2026-06-03 10:37:00'),(1084,5,2,2,233,'2026-06-03 10:38:00'),(1085,22,2,3,20,'2026-06-03 10:38:39'),(1086,22,2,3,22,'2026-06-03 10:38:41'),(1087,22,2,3,40,'2026-06-03 10:39:00'),(1088,19,2,2,80,'2026-06-03 10:40:06'),(1089,32,2,3,20,'2026-06-03 10:40:28'),(1090,32,2,3,51,'2026-06-03 10:41:00'),(1091,32,NULL,3,52,'2026-06-03 10:41:01'),(1092,32,NULL,3,54,'2026-06-03 10:41:03'),(1093,32,NULL,3,110,'2026-06-03 10:42:00'),(1094,32,NULL,3,171,'2026-06-03 10:43:00'),(1095,32,NULL,3,198,'2026-06-03 10:43:28'),(1096,32,NULL,3,200,'2026-06-03 10:43:29'),(1097,32,NULL,3,201,'2026-06-03 10:43:31'),(1098,32,NULL,3,203,'2026-06-03 10:43:33'),(1099,36,NULL,3,20,'2026-06-03 10:43:56'),(1100,36,NULL,3,21,'2026-06-03 10:43:58'),(1101,36,NULL,3,23,'2026-06-03 10:44:00'),(1102,36,NULL,3,23,'2026-06-03 10:44:00'),(1103,36,NULL,3,27,'2026-06-03 10:44:04'),(1104,36,NULL,3,29,'2026-06-03 10:44:06'),(1105,36,NULL,3,30,'2026-06-03 10:44:07'),(1106,36,5,3,42,'2026-06-03 10:44:19'),(1107,36,5,3,82,'2026-06-03 10:45:00'),(1108,36,5,3,142,'2026-06-03 10:46:00'),(1109,36,5,3,202,'2026-06-03 10:47:00'),(1110,40,5,3,20,'2026-06-03 10:47:50'),(1111,40,5,3,29,'2026-06-03 10:48:00'),(1112,40,5,3,89,'2026-06-03 10:49:00'),(1113,40,5,3,137,'2026-06-03 10:49:48'),(1114,40,5,3,149,'2026-06-03 10:50:00'),(1115,40,5,3,209,'2026-06-03 10:51:00'),(1116,7,5,2,20,'2026-06-03 10:52:04'),(1117,7,5,2,76,'2026-06-03 10:53:00'),(1118,7,5,2,92,'2026-06-03 10:53:16'),(1119,9,5,2,20,'2026-06-03 10:53:40'),(1120,9,5,2,39,'2026-06-03 10:54:00'),(1121,9,5,2,43,'2026-06-03 10:54:21'),(1122,9,5,2,43,'2026-06-03 11:07:29'),(1123,9,5,2,43,'2026-06-03 11:08:12'),(1124,9,5,2,43,'2026-06-03 11:22:51'),(1125,9,5,2,43,'2026-06-03 17:35:10'),(1126,36,5,3,20,'2026-06-03 17:35:37'),(1127,36,5,3,42,'2026-06-03 17:36:00'),(1128,36,5,3,102,'2026-06-03 17:37:00'),(1129,36,5,3,162,'2026-06-03 17:38:00'),(1130,36,5,3,222,'2026-06-03 17:39:00'),(1131,30,5,3,20,'2026-06-03 17:39:30'),(1132,30,5,3,49,'2026-06-03 17:40:00'),(1133,30,5,3,109,'2026-06-03 17:41:00'),(1134,30,5,3,169,'2026-06-03 17:42:00'),(1135,30,5,3,229,'2026-06-03 17:43:00'),(1136,39,5,3,20,'2026-06-03 17:43:46'),(1137,39,5,3,33,'2026-06-03 17:44:00'),(1138,39,NULL,3,72,'2026-06-03 17:44:39'),(1139,39,NULL,3,74,'2026-06-03 17:44:41'),(1140,39,NULL,3,86,'2026-06-03 19:21:46'),(1141,36,NULL,3,20,'2026-06-03 19:22:16'),(1142,36,2,3,20,'2026-06-03 19:22:17'),(1143,36,2,3,63,'2026-06-03 19:23:00'),(1144,36,2,3,124,'2026-06-03 19:24:00'),(1145,36,2,3,184,'2026-06-03 19:25:00'),(1146,22,2,3,20,'2026-06-03 19:26:23'),(1147,22,2,3,56,'2026-06-03 19:27:00'),(1148,20,2,3,20,'2026-06-03 19:28:13'),(1149,20,2,3,66,'2026-06-03 19:29:00'),(1150,20,2,3,20,'2026-06-03 19:30:03'),(1151,20,NULL,3,26,'2026-06-03 19:30:10'),(1152,20,NULL,3,29,'2026-06-03 19:30:13'),(1153,20,NULL,3,37,'2026-06-04 01:02:53'),(1154,20,NULL,3,44,'2026-06-04 01:03:00'),(1155,20,NULL,3,73,'2026-06-04 01:04:30'),(1156,20,NULL,3,76,'2026-06-04 01:07:07'),(1157,22,NULL,3,20,'2026-06-04 01:08:14'),(1158,22,NULL,3,66,'2026-06-04 01:09:00'),(1159,31,NULL,3,20,'2026-06-04 01:09:53'),(1160,31,NULL,3,23,'2026-06-04 01:09:56'),(1161,31,NULL,3,26,'2026-06-04 01:10:00'),(1162,31,NULL,3,86,'2026-06-04 01:11:00'),(1163,31,NULL,3,146,'2026-06-04 01:12:00'),(1164,31,NULL,3,154,'2026-06-04 01:12:07'),(1165,20,2,3,20,'2026-06-04 01:13:20'),(1166,20,2,3,59,'2026-06-04 01:14:00'),(1167,20,2,3,119,'2026-06-04 01:15:00'),(1168,22,2,3,20,'2026-06-04 01:15:23'),(1169,22,2,3,50,'2026-06-04 01:15:53'),(1170,22,2,3,56,'2026-06-04 01:16:00'),(1171,31,2,3,20,'2026-06-04 01:17:02'),(1172,31,2,3,77,'2026-06-04 01:18:00'),(1173,31,2,3,83,'2026-06-04 01:18:06'),(1174,31,2,3,137,'2026-06-04 01:19:00'),(1175,31,2,3,190,'2026-06-04 01:19:53'),(1176,31,2,3,196,'2026-06-04 01:20:00'),(1177,20,2,3,20,'2026-06-04 01:20:28'),(1178,36,2,3,20,'2026-06-04 01:21:19'),(1179,36,2,3,61,'2026-06-04 01:22:00'),(1180,36,2,3,98,'2026-06-04 01:22:37'),(1181,31,2,3,20,'2026-06-04 01:23:15'),(1182,31,2,3,64,'2026-06-04 01:24:00'),(1183,31,2,3,124,'2026-06-04 01:25:00'),(1184,31,2,3,184,'2026-06-04 01:26:00'),(1185,13,2,2,20,'2026-06-04 01:26:41'),(1186,13,2,2,38,'2026-06-04 01:27:00'),(1187,13,2,2,98,'2026-06-04 01:28:00'),(1188,40,2,3,20,'2026-06-04 01:28:28'),(1189,24,2,3,20,'2026-06-04 01:29:16'),(1190,24,2,3,64,'2026-06-04 01:30:00'),(1191,24,2,3,124,'2026-06-04 01:31:00'),(1192,24,2,3,184,'2026-06-04 01:32:00'),(1193,13,2,2,20,'2026-06-04 01:32:29'),(1194,13,2,2,22,'2026-06-04 01:32:33'),(1195,22,2,3,20,'2026-06-04 01:33:27'),(1196,22,2,3,52,'2026-06-04 01:34:00'),(1197,30,2,3,20,'2026-06-04 01:35:07'),(1198,30,2,3,73,'2026-06-04 01:36:00'),(1199,30,2,3,133,'2026-06-04 01:37:00'),(1200,30,2,3,193,'2026-06-04 01:38:00'),(1201,30,2,3,253,'2026-06-04 01:39:00'),(1202,15,2,2,20,'2026-06-04 01:39:22'),(1203,15,2,2,57,'2026-06-04 01:40:00'),(1204,15,2,2,118,'2026-06-04 01:41:00'),(1205,12,2,2,20,'2026-06-04 01:41:39'),(1206,12,2,2,40,'2026-06-04 01:42:00'),(1207,12,2,2,42,'2026-06-04 01:42:02'),(1208,19,2,2,20,'2026-06-04 01:42:41'),(1209,24,2,3,20,'2026-06-04 01:43:07'),(1210,24,2,3,72,'2026-06-04 01:44:00'),(1211,24,2,3,132,'2026-06-04 01:45:00'),(1212,24,2,3,193,'2026-06-04 01:46:00'),(1213,17,2,2,20,'2026-06-04 01:46:21'),(1214,17,2,2,27,'2026-06-04 01:46:29'),(1215,17,2,2,58,'2026-06-04 01:47:00'),(1216,17,2,2,118,'2026-06-04 01:48:00'),(1217,16,2,2,20,'2026-06-04 01:49:15'),(1218,16,2,2,23,'2026-06-04 01:49:19'),(1219,16,2,2,64,'2026-06-04 01:50:00'),(1220,16,2,2,124,'2026-06-04 01:51:00'),(1221,3,2,2,20,'2026-06-04 01:52:07'),(1222,3,2,2,39,'2026-06-04 01:52:26'),(1223,3,2,2,72,'2026-06-04 01:53:00'),(1224,3,2,2,132,'2026-06-04 01:54:00'),(1225,7,2,2,20,'2026-06-04 01:54:30'),(1226,7,2,2,49,'2026-06-04 01:55:00'),(1227,7,2,2,64,'2026-06-04 01:55:15'),(1228,7,2,2,98,'2026-06-04 01:55:49'),(1229,7,2,2,108,'2026-06-04 01:56:00'),(1230,36,2,3,20,'2026-06-04 01:56:34'),(1231,36,2,3,45,'2026-06-04 01:57:00'),(1232,36,2,3,105,'2026-06-04 01:58:00'),(1233,36,2,3,165,'2026-06-04 01:59:00'),(1234,36,2,3,225,'2026-06-04 02:00:00'),(1235,43,2,5,20,'2026-06-04 02:00:27'),(1236,43,2,5,52,'2026-06-04 02:01:00'),(1237,40,2,3,20,'2026-06-04 02:02:01'),(1238,40,2,3,70,'2026-06-04 02:02:51'),(1239,40,2,3,78,'2026-06-04 02:03:00'),(1240,40,2,3,114,'2026-06-04 02:03:36'),(1241,40,2,3,138,'2026-06-04 02:04:00'),(1242,40,2,3,198,'2026-06-04 02:05:00'),(1243,40,2,3,238,'2026-06-04 02:05:40'),(1244,6,2,2,20,'2026-06-04 02:06:16'),(1245,6,2,2,30,'2026-06-04 02:06:27'),(1246,6,2,2,63,'2026-06-04 02:07:00'),(1247,6,2,2,123,'2026-06-04 02:08:00'),(1248,6,2,2,183,'2026-06-04 02:09:00'),(1249,11,2,2,20,'2026-06-04 02:09:55'),(1250,11,2,2,24,'2026-06-04 02:10:00'),(1251,15,2,2,20,'2026-06-04 02:10:59'),(1252,15,2,2,21,'2026-06-04 02:11:00'),(1253,15,2,2,81,'2026-06-04 02:12:00'),(1254,8,2,2,20,'2026-06-04 02:13:16'),(1255,8,2,2,64,'2026-06-04 02:14:00'),(1256,8,2,2,124,'2026-06-04 02:15:00'),(1257,10,2,2,20,'2026-06-04 02:16:16'),(1258,4,2,2,20,'2026-06-04 02:16:45'),(1259,4,2,2,34,'2026-06-04 02:17:00'),(1260,4,2,2,94,'2026-06-04 02:18:00'),(1261,4,2,2,154,'2026-06-04 02:19:00'),(1262,42,2,5,20,'2026-06-04 02:20:12'),(1263,42,2,5,67,'2026-06-04 02:21:00'),(1264,36,2,3,20,'2026-06-04 02:21:47'),(1265,36,2,3,33,'2026-06-04 02:22:00'),(1266,36,2,3,93,'2026-06-04 02:23:00'),(1267,36,2,3,153,'2026-06-04 02:24:00'),(1268,36,2,3,213,'2026-06-04 02:25:00'),(1269,22,2,3,20,'2026-06-04 02:25:40'),(1270,22,2,3,40,'2026-06-04 02:26:00'),(1271,37,2,3,20,'2026-06-04 02:27:19'),(1272,37,2,3,60,'2026-06-04 02:28:00'),(1273,37,2,3,120,'2026-06-04 02:29:00'),(1274,37,2,3,181,'2026-06-04 02:30:00'),(1275,26,2,3,20,'2026-06-04 02:31:08'),(1276,26,2,3,72,'2026-06-04 02:32:00'),(1277,26,2,3,132,'2026-06-04 02:33:00'),(1278,26,2,3,136,'2026-06-04 02:33:04'),(1279,36,2,3,20,'2026-06-04 02:33:28'),(1280,36,2,3,51,'2026-06-04 02:34:00'),(1281,36,2,3,111,'2026-06-04 02:35:00'),(1282,36,2,3,138,'2026-06-04 02:35:27'),(1283,36,2,3,143,'2026-06-04 02:35:32'),(1284,36,2,3,171,'2026-06-04 02:36:00'),(1285,36,2,3,231,'2026-06-04 02:37:00'),(1286,13,2,2,20,'2026-06-04 02:37:22'),(1287,13,2,2,57,'2026-06-04 02:38:00'),(1288,15,2,2,20,'2026-06-04 02:39:09'),(1289,15,2,2,70,'2026-06-04 02:40:00'),(1290,15,2,2,119,'2026-06-04 02:40:49'),(1291,15,2,2,130,'2026-06-04 02:41:00'),(1292,19,2,2,20,'2026-06-04 02:41:26'),(1293,19,2,2,38,'2026-06-04 11:06:07'),(1294,19,NULL,2,57,'2026-06-04 11:08:45'),(1295,19,NULL,2,63,'2026-06-04 11:08:51'),(1296,19,NULL,2,71,'2026-06-04 11:09:00'),(1297,36,NULL,3,20,'2026-06-04 11:09:22'),(1298,36,NULL,3,57,'2026-06-04 11:10:00'),(1299,36,NULL,3,117,'2026-06-04 11:11:00'),(1300,36,NULL,3,165,'2026-06-04 11:11:48'),(1301,36,NULL,3,177,'2026-06-04 11:12:00'),(1302,36,2,3,177,'2026-06-04 11:12:00'),(1303,7,2,2,20,'2026-06-04 11:13:16'),(1304,7,2,2,63,'2026-06-04 11:14:00'),(1305,4,2,2,20,'2026-06-04 11:14:43'),(1306,4,2,2,36,'2026-06-04 11:15:00'),(1307,4,NULL,2,69,'2026-06-04 11:15:33'),(1308,4,NULL,2,71,'2026-06-04 11:15:35'),(1309,4,NULL,2,96,'2026-06-04 11:16:00'),(1310,4,NULL,2,98,'2026-06-04 13:44:00'),(1311,36,NULL,3,20,'2026-06-04 13:46:14'),(1312,36,NULL,3,57,'2026-06-04 13:50:25'),(1313,36,NULL,3,57,'2026-06-04 13:50:27'),(1314,36,NULL,3,57,'2026-06-04 13:50:28'),(1315,36,NULL,3,57,'2026-06-04 13:50:33'),(1316,36,NULL,3,57,'2026-06-04 13:50:36'),(1317,36,2,3,57,'2026-06-04 13:50:48'),(1318,31,2,3,20,'2026-06-04 13:52:35'),(1319,39,2,3,20,'2026-06-04 13:53:24'),(1320,39,2,3,56,'2026-06-04 13:54:00'),(1321,39,2,3,61,'2026-06-04 13:54:05'),(1322,39,2,3,115,'2026-06-04 13:55:00'),(1323,39,2,3,176,'2026-06-04 13:56:00'),(1324,39,2,3,235,'2026-06-04 13:57:00'),(1325,44,2,5,20,'2026-06-04 13:57:40'),(1326,44,NULL,5,34,'2026-06-04 13:57:55'),(1327,44,NULL,5,38,'2026-06-04 13:57:59'),(1328,44,NULL,5,38,'2026-06-04 13:58:00'),(1329,44,5,5,47,'2026-06-04 13:58:09'),(1330,44,5,5,66,'2026-06-04 13:58:28'),(1331,44,5,5,98,'2026-06-04 13:59:00'),(1332,15,5,2,20,'2026-06-04 13:59:55'),(1333,15,5,2,24,'2026-06-04 14:00:00'),(1334,15,5,2,84,'2026-06-04 14:01:00'),(1335,36,5,3,20,'2026-06-04 14:02:12'),(1336,36,5,3,67,'2026-06-04 14:03:00'),(1337,36,5,3,116,'2026-06-04 14:03:49'),(1338,36,5,3,127,'2026-06-04 14:04:00'),(1339,36,NULL,3,163,'2026-06-04 14:04:36'),(1340,36,NULL,3,174,'2026-06-04 14:04:48'),(1341,36,NULL,3,183,'2026-06-04 14:04:56'),(1342,36,NULL,3,186,'2026-06-04 14:05:00'),(1343,4,NULL,2,20,'2026-06-04 14:06:24'),(1344,4,NULL,2,33,'2026-06-04 14:06:37'),(1345,4,NULL,2,55,'2026-06-04 14:07:00'),(1346,4,NULL,2,115,'2026-06-04 14:08:00'),(1347,4,NULL,2,175,'2026-06-04 14:09:00'),(1348,43,NULL,5,20,'2026-06-04 14:09:51'),(1349,43,NULL,5,28,'2026-06-04 14:10:00'),(1350,43,NULL,5,88,'2026-06-04 14:11:00'),(1351,24,NULL,3,20,'2026-06-04 14:11:25'),(1352,24,NULL,3,26,'2026-06-04 14:11:33'),(1353,24,NULL,3,54,'2026-06-04 14:12:00'),(1354,24,NULL,3,88,'2026-06-04 14:12:34'),(1355,24,NULL,3,97,'2026-06-04 14:12:43'),(1356,24,NULL,3,113,'2026-06-04 14:13:00'),(1357,24,NULL,3,140,'2026-06-04 14:13:26'),(1358,24,NULL,3,144,'2026-06-04 14:13:30'),(1359,24,NULL,3,157,'2026-06-04 14:13:44'),(1360,24,NULL,3,167,'2026-06-04 14:13:54'),(1361,24,NULL,3,169,'2026-06-04 14:13:56'),(1362,24,NULL,3,173,'2026-06-04 14:14:00'),(1363,21,NULL,3,48,'2026-06-04 14:15:08'),(1364,21,NULL,3,55,'2026-06-04 14:15:15'),(1365,21,2,3,75,'2026-06-04 14:15:36'),(1366,21,2,3,99,'2026-06-04 14:16:00'),(1367,21,2,3,159,'2026-06-04 14:17:00'),(1368,21,2,3,219,'2026-06-04 14:18:00'),(1369,13,2,2,20,'2026-06-04 14:18:37'),(1370,13,2,2,42,'2026-06-04 14:19:00'),(1371,13,2,2,87,'2026-06-04 14:19:45'),(1372,13,2,2,102,'2026-06-04 14:20:00'),(1373,30,2,3,20,'2026-06-04 14:20:25'),(1374,30,2,3,55,'2026-06-04 14:21:00'),(1375,30,2,3,115,'2026-06-04 14:22:00'),(1376,30,2,3,175,'2026-06-04 14:23:00'),(1377,30,2,3,235,'2026-06-04 14:24:00'),(1378,9,2,2,20,'2026-06-04 14:24:40'),(1379,9,2,2,39,'2026-06-04 14:25:00'),(1380,9,2,2,96,'2026-06-04 14:25:57'),(1381,9,2,2,99,'2026-06-04 14:26:00'),(1382,9,2,2,102,'2026-06-04 14:26:04'),(1383,9,2,2,158,'2026-06-04 14:27:00'),(1384,9,2,2,218,'2026-06-04 14:28:00'),(1385,9,2,2,259,'2026-06-04 14:28:41'),(1386,9,2,2,262,'2026-06-04 14:28:44'),(1387,39,2,3,20,'2026-06-04 14:29:59'),(1388,39,2,3,20,'2026-06-04 14:30:00'),(1389,36,2,3,20,'2026-06-04 14:30:32'),(1390,36,2,3,47,'2026-06-04 14:31:00'),(1391,36,2,3,107,'2026-06-04 14:32:00'),(1392,36,2,3,20,'2026-06-04 14:33:56'),(1393,36,2,3,24,'2026-06-04 14:34:00'),(1394,24,2,3,20,'2026-06-04 14:35:12'),(1395,24,2,3,67,'2026-06-04 14:36:00'),(1396,24,2,3,127,'2026-06-04 14:37:00'),(1397,24,2,3,187,'2026-06-04 14:38:00'),(1398,26,2,3,20,'2026-06-04 14:38:26'),(1399,26,2,3,53,'2026-06-04 14:39:00'),(1400,26,2,3,113,'2026-06-04 14:40:00'),(1401,26,2,3,173,'2026-06-04 14:41:00'),(1402,14,2,2,20,'2026-06-04 14:42:06'),(1403,14,2,2,73,'2026-06-04 14:43:00'),(1404,14,2,2,133,'2026-06-04 14:44:00'),(1405,14,2,2,193,'2026-06-04 14:45:00'),(1406,43,2,5,20,'2026-06-04 14:45:27'),(1407,43,2,5,52,'2026-06-04 14:46:00'),(1408,43,2,5,60,'2026-06-04 21:47:00'),(1409,43,2,5,68,'2026-06-04 21:47:09'),(1410,43,NULL,5,76,'2026-06-04 21:47:17'),(1411,43,NULL,5,83,'2026-06-04 21:47:25'),(1412,15,NULL,2,20,'2026-06-04 21:47:55'),(1413,15,NULL,2,24,'2026-06-04 21:48:00'),(1414,15,NULL,2,84,'2026-06-04 21:49:00'),(1415,15,NULL,2,123,'2026-06-04 21:49:39'),(1416,15,2,2,127,'2026-06-04 21:49:43'),(1417,22,2,3,20,'2026-06-04 21:50:13'),(1418,22,2,3,66,'2026-06-04 21:51:00'),(1419,20,2,3,20,'2026-06-04 21:51:53'),(1420,20,2,3,27,'2026-06-04 21:52:00'),(1421,20,2,3,87,'2026-06-04 21:53:00'),(1422,40,2,3,20,'2026-06-04 21:53:55'),(1423,40,2,3,24,'2026-06-04 21:54:00'),(1424,40,2,3,84,'2026-06-04 21:55:00'),(1425,40,2,3,144,'2026-06-04 21:56:00'),(1426,40,2,3,204,'2026-06-04 21:57:00'),(1427,3,2,2,20,'2026-06-04 21:58:08'),(1428,3,NULL,2,64,'2026-06-04 21:58:53'),(1429,3,NULL,2,65,'2026-06-04 21:58:55'),(1430,3,NULL,2,70,'2026-06-04 21:59:00'),(1431,3,NULL,2,77,'2026-06-04 22:45:54'),(1432,3,NULL,2,83,'2026-06-04 22:46:00'),(1433,3,NULL,2,85,'2026-06-04 22:46:02'),(1434,3,2,2,92,'2026-06-04 22:46:09'),(1435,44,2,5,20,'2026-06-04 22:47:20'),(1436,44,2,5,60,'2026-06-04 22:48:00'),(1437,44,2,5,120,'2026-06-04 22:49:00'),(1438,6,2,2,20,'2026-06-04 22:49:32'),(1439,6,2,2,35,'2026-06-04 22:49:48'),(1440,6,2,2,47,'2026-06-04 22:50:00'),(1441,6,2,2,96,'2026-06-04 22:50:49'),(1442,6,2,2,106,'2026-06-04 22:51:00'),(1443,6,2,2,166,'2026-06-04 22:52:00'),(1444,6,NULL,2,213,'2026-06-04 22:52:46'),(1445,6,NULL,2,215,'2026-06-04 22:52:48'),(1446,15,NULL,2,20,'2026-06-04 22:53:16'),(1447,15,2,2,28,'2026-06-04 22:53:25'),(1448,15,2,2,63,'2026-06-04 22:54:00'),(1449,15,2,2,123,'2026-06-04 22:55:00'),(1450,20,2,3,20,'2026-06-04 22:55:34'),(1451,20,2,3,45,'2026-06-04 22:56:00'),(1452,20,2,3,105,'2026-06-04 22:57:00'),(1453,13,2,2,20,'2026-06-04 22:57:37'),(1454,13,2,2,43,'2026-06-04 22:58:00'),(1455,13,2,2,103,'2026-06-04 22:59:00'),(1456,11,2,2,20,'2026-06-04 22:59:24'),(1457,11,2,2,56,'2026-06-04 23:00:00'),(1458,22,2,3,20,'2026-06-04 23:00:27'),(1459,22,2,3,52,'2026-06-04 23:01:00'),(1460,31,2,3,20,'2026-06-04 23:02:07'),(1461,31,2,3,73,'2026-06-04 23:03:00'),(1462,31,2,3,109,'2026-06-04 23:03:36'),(1463,31,2,3,133,'2026-06-04 23:04:00'),(1464,31,2,3,193,'2026-06-04 23:05:00'),(1465,8,2,2,20,'2026-06-04 23:05:33'),(1466,8,2,2,35,'2026-06-04 23:05:49'),(1467,8,2,2,47,'2026-06-04 23:06:00'),(1468,8,2,2,106,'2026-06-04 23:07:00'),(1469,8,2,2,166,'2026-06-04 23:08:00'),(1470,36,2,3,20,'2026-06-04 23:08:32'),(1471,36,2,3,47,'2026-06-04 23:09:00'),(1472,36,2,3,107,'2026-06-04 23:10:00'),(1473,36,2,3,167,'2026-06-04 23:11:00'),(1474,36,2,3,227,'2026-06-04 23:12:00'),(1475,7,2,2,20,'2026-06-04 23:12:25'),(1476,7,2,2,54,'2026-06-04 23:13:00'),(1477,7,2,2,113,'2026-06-04 23:13:59'),(1478,7,2,2,114,'2026-06-04 23:14:00'),(1479,7,2,2,174,'2026-06-04 23:15:00'),(1480,7,2,2,234,'2026-06-04 23:16:00'),(1481,6,2,2,20,'2026-06-04 23:16:51'),(1482,6,2,2,28,'2026-06-04 23:17:00'),(1483,6,2,2,88,'2026-06-04 23:18:00'),(1484,6,2,2,148,'2026-06-04 23:19:00'),(1485,6,2,2,208,'2026-06-04 23:20:00'),(1486,14,2,2,20,'2026-06-04 23:20:30'),(1487,14,2,2,50,'2026-06-04 23:21:00'),(1488,14,2,2,109,'2026-06-04 23:22:00'),(1489,14,2,2,170,'2026-06-04 23:23:00'),(1490,10,2,2,20,'2026-06-04 23:23:51'),(1491,10,2,2,29,'2026-06-04 23:24:00'),(1492,10,2,2,89,'2026-06-04 23:25:00'),(1493,10,2,2,148,'2026-06-04 23:26:00'),(1494,4,2,2,20,'2026-06-04 23:26:35'),(1495,4,2,2,44,'2026-06-04 23:27:00'),(1496,4,2,2,104,'2026-06-04 23:28:00'),(1497,4,2,2,164,'2026-06-04 23:29:00'),(1498,40,2,3,20,'2026-06-04 23:30:02'),(1499,40,2,3,47,'2026-06-04 23:30:30'),(1500,40,2,3,77,'2026-06-04 23:31:00'),(1501,40,2,3,137,'2026-06-04 23:32:00'),(1502,40,2,3,197,'2026-06-04 23:33:00'),(1503,38,2,3,20,'2026-06-04 23:34:16'),(1504,38,2,3,64,'2026-06-04 23:35:00'),(1505,38,2,3,124,'2026-06-04 23:36:00'),(1506,38,2,3,184,'2026-06-04 23:37:00'),(1507,6,2,2,20,'2026-06-04 23:37:35'),(1508,6,2,2,44,'2026-06-04 23:38:00'),(1509,6,2,2,104,'2026-06-04 23:39:00'),(1510,6,2,2,153,'2026-06-04 23:39:49'),(1511,6,2,2,164,'2026-06-04 23:40:00'),(1512,6,NULL,2,165,'2026-06-04 23:40:01'),(1513,25,5,3,20,'2026-06-04 23:40:28'),(1514,25,5,3,51,'2026-06-04 23:41:00'),(1515,25,5,3,111,'2026-06-04 23:42:00'),(1516,25,NULL,3,167,'2026-06-04 23:42:56'),(1517,25,NULL,3,169,'2026-06-04 23:42:58'),(1518,25,NULL,3,171,'2026-06-04 23:43:00'),(1519,25,NULL,3,175,'2026-06-04 23:44:11'),(1520,25,NULL,3,178,'2026-06-04 23:44:14'),(1521,25,NULL,3,183,'2026-06-04 23:44:19'),(1522,25,2,3,188,'2026-06-04 23:44:25'),(1523,25,2,3,223,'2026-06-04 23:45:00'),(1524,32,2,3,20,'2026-06-04 23:45:34'),(1525,32,2,3,45,'2026-06-04 23:46:00'),(1526,32,2,3,105,'2026-06-04 23:47:00'),(1527,32,2,3,165,'2026-06-04 23:48:00'),(1528,43,2,5,20,'2026-06-04 23:49:01'),(1529,43,2,5,78,'2026-06-04 23:50:00'),(1530,16,2,2,20,'2026-06-04 23:50:35'),(1531,16,2,2,44,'2026-06-04 23:51:00'),(1532,16,2,2,104,'2026-06-04 23:52:00'),(1533,16,2,2,164,'2026-06-04 23:53:00'),(1534,10,2,2,20,'2026-06-04 23:53:27'),(1535,10,2,2,52,'2026-06-04 23:54:00'),(1536,10,2,2,112,'2026-06-04 23:55:00'),(1537,22,2,3,20,'2026-06-04 23:56:12'),(1538,22,2,3,67,'2026-06-04 23:57:00'),(1539,42,2,5,20,'2026-06-04 23:57:42'),(1540,25,2,3,20,'2026-06-04 23:58:25'),(1541,25,2,3,55,'2026-06-04 23:59:00'),(1542,40,2,3,20,'2026-06-05 00:00:08'),(1543,40,2,3,72,'2026-06-05 00:01:00'),(1544,40,NULL,3,128,'2026-06-05 00:01:56'),(1545,40,NULL,3,131,'2026-06-05 00:02:00'),(1546,40,NULL,3,131,'2026-06-05 00:02:00'),(1547,40,NULL,3,139,'2026-06-05 00:07:13'),(1548,40,NULL,3,169,'2026-06-05 00:07:44'),(1549,40,NULL,3,171,'2026-06-05 00:07:46'),(1550,40,NULL,3,172,'2026-06-05 00:07:48'),(1551,40,NULL,3,173,'2026-06-05 00:07:49'),(1552,40,NULL,3,175,'2026-06-05 00:07:51'),(1553,40,NULL,3,181,'2026-06-05 00:07:57'),(1554,40,NULL,3,183,'2026-06-05 00:08:00'),(1555,40,NULL,3,185,'2026-06-05 00:08:02'),(1556,40,NULL,3,193,'2026-06-05 00:08:10'),(1557,40,NULL,3,197,'2026-06-05 00:08:14'),(1558,40,NULL,3,200,'2026-06-05 00:08:18'),(1559,40,2,3,207,'2026-06-05 00:08:25'),(1560,40,2,3,242,'2026-06-05 00:09:00'),(1561,18,2,2,20,'2026-06-05 00:09:32'),(1562,8,2,2,20,'2026-06-05 00:09:54'),(1563,8,2,2,25,'2026-06-05 00:10:00'),(1564,8,2,2,63,'2026-06-05 00:10:38'),(1565,9,2,2,20,'2026-06-05 00:10:56'),(1566,11,2,2,20,'2026-06-05 00:11:20'),(1567,26,2,3,20,'2026-06-05 00:12:16'),(1568,26,2,3,63,'2026-06-05 00:13:00'),(1569,31,2,3,20,'2026-06-05 00:13:48'),(1570,31,2,3,31,'2026-06-05 00:14:00'),(1571,31,2,3,91,'2026-06-05 00:15:00'),(1572,31,NULL,3,95,'2026-06-05 00:15:04'),(1573,31,NULL,3,98,'2026-06-05 00:15:07'),(1574,31,NULL,3,107,'2026-06-05 00:15:16'),(1575,31,NULL,3,112,'2026-06-05 00:15:29'),(1576,31,NULL,3,142,'2026-06-05 00:16:00'),(1577,31,NULL,3,202,'2026-06-05 00:17:00'),(1578,36,NULL,3,20,'2026-06-05 00:17:23'),(1579,36,NULL,3,56,'2026-06-05 00:18:00'),(1580,36,NULL,3,116,'2026-06-05 00:19:00'),(1581,36,NULL,3,176,'2026-06-05 00:20:00'),(1582,36,NULL,3,209,'2026-06-05 00:20:33'),(1583,36,NULL,3,227,'2026-06-05 00:20:51'),(1584,13,NULL,2,20,'2026-06-05 00:21:17'),(1585,13,NULL,2,50,'2026-06-05 11:24:38'),(1586,31,NULL,3,112,'2026-06-05 11:24:42'),(1587,31,NULL,3,114,'2026-06-05 11:24:45'),(1588,31,NULL,3,129,'2026-06-05 11:25:00'),(1589,31,NULL,3,131,'2026-06-05 11:25:02'),(1590,31,NULL,3,137,'2026-06-05 11:25:08'),(1591,31,NULL,3,145,'2026-06-05 11:26:39'),(1592,31,NULL,3,155,'2026-06-05 11:26:50'),(1593,31,NULL,3,156,'2026-06-05 11:26:52'),(1594,31,NULL,3,165,'2026-06-05 11:27:00'),(1595,31,2,3,165,'2026-06-05 11:27:01'),(1596,36,2,3,20,'2026-06-05 11:28:01'),(1597,24,2,3,191,'2026-06-05 11:28:44'),(1598,37,2,3,20,'2026-06-05 11:29:10'),(1599,37,NULL,3,69,'2026-06-05 11:30:00'),(1600,37,NULL,3,72,'2026-06-05 11:30:03'),(1601,37,2,3,79,'2026-06-05 11:30:10'),(1602,37,2,3,110,'2026-06-05 11:30:41'),(1603,37,2,3,129,'2026-06-05 11:31:00'),(1604,37,2,3,189,'2026-06-05 11:32:00'),(1605,40,2,3,20,'2026-06-05 11:33:00'),(1606,40,2,3,79,'2026-06-05 11:34:00'),(1607,40,2,3,139,'2026-06-05 11:35:00'),(1608,40,2,3,199,'2026-06-05 11:36:00'),(1609,31,2,3,20,'2026-06-05 11:37:13'),(1610,31,2,3,66,'2026-06-05 11:38:00'),(1611,31,2,3,126,'2026-06-05 11:39:00'),(1612,31,2,3,186,'2026-06-05 11:40:00'),(1613,20,2,3,20,'2026-06-05 11:40:39'),(1614,20,2,3,40,'2026-06-05 11:41:00'),(1615,20,2,3,100,'2026-06-05 11:42:00'),(1616,23,2,3,20,'2026-06-05 11:42:42'),(1617,23,2,3,38,'2026-06-05 11:43:00'),(1618,23,2,3,98,'2026-06-05 11:44:00'),(1619,23,2,3,158,'2026-06-05 11:45:00'),(1620,23,2,3,218,'2026-06-05 11:46:00'),(1621,8,2,2,20,'2026-06-05 11:46:40'),(1622,8,2,2,39,'2026-06-05 11:47:00'),(1623,8,2,2,99,'2026-06-05 11:48:00'),(1624,8,2,2,159,'2026-06-05 11:49:00'),(1625,6,2,2,20,'2026-06-05 11:49:39'),(1626,6,2,2,40,'2026-06-05 11:50:00'),(1627,6,2,2,100,'2026-06-05 11:51:00'),(1628,6,2,2,160,'2026-06-05 11:52:00'),(1629,37,2,3,20,'2026-06-05 11:53:18'),(1630,37,2,3,23,'2026-06-05 11:53:21'),(1631,37,2,3,61,'2026-06-05 11:54:00'),(1632,37,2,3,121,'2026-06-05 11:55:00'),(1633,37,2,3,181,'2026-06-05 11:56:00'),(1634,41,2,3,20,'2026-06-05 11:57:07'),(1635,41,2,3,72,'2026-06-05 11:58:00'),(1636,41,2,3,132,'2026-06-05 11:59:00'),(1637,41,2,3,192,'2026-06-05 12:00:00'),(1638,39,2,3,20,'2026-06-05 12:01:03'),(1639,39,2,3,77,'2026-06-05 12:02:00'),(1640,39,2,3,137,'2026-06-05 12:03:00'),(1641,39,2,3,197,'2026-06-05 12:04:00'),(1642,4,2,2,20,'2026-06-05 12:05:19'),(1643,4,2,2,60,'2026-06-05 12:06:00'),(1644,4,2,2,120,'2026-06-05 12:07:00'),(1645,4,2,2,180,'2026-06-05 12:08:00'),(1646,26,2,3,20,'2026-06-05 12:08:46'),(1647,26,2,3,34,'2026-06-05 12:09:00'),(1648,26,2,3,94,'2026-06-05 12:10:00'),(1649,26,2,3,154,'2026-06-05 12:11:00'),(1650,26,2,3,214,'2026-06-05 12:12:00'),(1651,10,2,2,20,'2026-06-05 12:12:26'),(1652,10,2,2,54,'2026-06-05 12:13:00'),(1653,10,2,2,108,'2026-06-05 12:13:55'),(1654,10,2,2,113,'2026-06-05 12:14:00'),(1655,10,2,2,143,'2026-06-05 12:30:57'),(1656,10,NULL,2,143,'2026-06-05 12:48:38'),(1657,10,NULL,2,143,'2026-06-05 12:48:40');
/*!40000 ALTER TABLE `faixa_listen` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `favorito_musica`
--

DROP TABLE IF EXISTS `favorito_musica`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `favorito_musica` (
  `idFavorito` int(11) NOT NULL AUTO_INCREMENT,
  `idCliente` int(11) NOT NULL,
  `idFaixa` int(11) NOT NULL,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`idFavorito`),
  UNIQUE KEY `uq_favorito_musica` (`idCliente`,`idFaixa`),
  KEY `fk_favorito_faixa` (`idFaixa`),
  CONSTRAINT `fk_favorito_cliente` FOREIGN KEY (`idCliente`) REFERENCES `cliente` (`idCliente`) ON DELETE CASCADE,
  CONSTRAINT `fk_favorito_faixa` FOREIGN KEY (`idFaixa`) REFERENCES `faixa` (`idFaixa`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `favorito_musica`
--

LOCK TABLES `favorito_musica` WRITE;
/*!40000 ALTER TABLE `favorito_musica` DISABLE KEYS */;
INSERT INTO `favorito_musica` VALUES (1,5,42,'2026-05-27 14:57:53'),(2,5,44,'2026-05-27 14:58:00'),(3,5,43,'2026-05-27 14:58:03'),(4,2,36,'2026-06-02 09:06:43'),(5,2,32,'2026-06-02 09:06:46'),(6,2,37,'2026-06-02 11:03:33'),(8,2,4,'2026-06-04 02:16:46'),(9,5,36,'2026-06-04 14:04:16');
/*!40000 ALTER TABLE `favorito_musica` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `mensagem_admin`
--

DROP TABLE IF EXISTS `mensagem_admin`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `mensagem_admin` (
  `idMensagem` int(11) NOT NULL AUTO_INCREMENT,
  `idCliente` int(11) NOT NULL,
  `assunto` varchar(160) NOT NULL,
  `mensagem` text NOT NULL,
  `resposta_admin` text DEFAULT NULL,
  `estado` enum('aberta','respondida','fechada') NOT NULL DEFAULT 'aberta',
  `idAdminResposta` int(11) DEFAULT NULL,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  `respondido_em` datetime DEFAULT NULL,
  PRIMARY KEY (`idMensagem`),
  KEY `fk_mensagem_cliente` (`idCliente`),
  KEY `fk_mensagem_admin` (`idAdminResposta`),
  KEY `idx_mensagem_admin_estado` (`estado`,`criado_em`),
  CONSTRAINT `fk_mensagem_admin` FOREIGN KEY (`idAdminResposta`) REFERENCES `admin` (`idAdmin`) ON DELETE SET NULL,
  CONSTRAINT `fk_mensagem_cliente` FOREIGN KEY (`idCliente`) REFERENCES `cliente` (`idCliente`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `mensagem_admin`
--

LOCK TABLES `mensagem_admin` WRITE;
/*!40000 ALTER TABLE `mensagem_admin` DISABLE KEYS */;
INSERT INTO `mensagem_admin` VALUES (1,2,'Problema com o meu lançamento','Olá, submeti um lançamento na semana passada mas ainda não recebi resposta. Podem verificar o estado?','eirir','respondida',1,'2026-05-10 20:17:38','2026-06-04 15:09:16'),(2,2,'Dúvida sobre stock do produto','Bom dia, queria saber como posso atualizar o stock de um dos meus produtos. Qual é o processo?','done','respondida',1,'2026-05-05 20:17:38','2026-06-04 23:38:32'),(3,3,'Receitas não estão a atualizar','A minha página de receitas parece estar com dados antigos. Há algum atraso ou algo está errado?','done','respondida',1,'2026-05-26 20:17:38','2026-06-03 19:01:23'),(4,3,'Pergunta sobre colaborações','Seria possível ter dois artistas associados ao mesmo lançamento? Gostava de explorar essa opção.','ugh','respondida',1,'2026-04-28 20:17:38','2026-06-05 01:02:27'),(5,5,'Aprovação de novos produtos','Submeti três novos produtos. É possível acelerar a revisão antes do meu próximo evento?','sure','respondida',1,'2026-05-04 20:17:38','2026-06-04 23:38:37'),(6,5,'Feedback sobre a plataforma','Estou a adorar a plataforma! Uma sugestão: seria ótimo poder ver o número de streams nos meus lançamentos.','mmhm','respondida',1,'2026-04-30 20:17:38','2026-06-04 23:38:48');
/*!40000 ALTER TABLE `mensagem_admin` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `morada_encomenda`
--

DROP TABLE IF EXISTS `morada_encomenda`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `morada_encomenda` (
  `idMoradaEncomenda` int(11) NOT NULL AUTO_INCREMENT,
  `idEncomenda` int(11) NOT NULL,
  `nome_destinatario` varchar(120) NOT NULL,
  `morada` varchar(255) NOT NULL,
  `cidade` varchar(100) NOT NULL,
  `codigo_postal` varchar(20) NOT NULL,
  `pais` varchar(80) NOT NULL DEFAULT 'Portugal',
  `telefone` varchar(30) NOT NULL,
  PRIMARY KEY (`idMoradaEncomenda`),
  UNIQUE KEY `idEncomenda` (`idEncomenda`),
  CONSTRAINT `fk_morada_encomenda` FOREIGN KEY (`idEncomenda`) REFERENCES `encomenda` (`idEncomenda`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=50 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `morada_encomenda`
--

LOCK TABLES `morada_encomenda` WRITE;
/*!40000 ALTER TABLE `morada_encomenda` DISABLE KEYS */;
INSERT INTO `morada_encomenda` VALUES (1,1,'Srijan Gautam','Rua das Flores 22, 2º Esq.','Porto','4000-265','Portugal','+351 912345678'),(2,2,'Srijan Gautam','Rua das Flores 22, 2º Esq.','Porto','4000-265','Portugal','+351 912345678'),(3,3,'Srijan Gautam','Rua das Flores 22, 2º Esq.','Porto','4000-265','Portugal','+351 912345678'),(4,4,'Srijan Gautam','Rua das Flores 22, 2º Esq.','Porto','4000-265','Portugal','+351 912345678'),(5,5,'Srijan Gautam','Rua das Flores 22, 2º Esq.','Porto','4000-265','Portugal','+351 912345678'),(6,6,'Srijan Gautam','Rua das Flores 22, 2º Esq.','Porto','4000-265','Portugal','+351 912345678'),(7,7,'Srijan Gautam','Rua das Flores 22, 2º Esq.','Porto','4000-265','Portugal','+351 912345678'),(8,8,'Srijan Gautam','Rua das Flores 22, 2º Esq.','Porto','4000-265','Portugal','+351 912345678'),(9,9,'Srijan Gautam','Rua das Flores 22, 2º Esq.','Porto','4000-265','Portugal','+351 912345678'),(10,10,'Srijan Gautam','Rua das Flores 22, 2º Esq.','Porto','4000-265','Portugal','+351 912345678'),(11,11,'Sneha Gurung','Avenida da República 104','Lisboa','1050-190','Portugal','+351 913456789'),(12,12,'Sneha Gurung','Avenida da República 104','Lisboa','1050-190','Portugal','+351 913456789'),(13,13,'Sneha Gurung','Avenida da República 104','Lisboa','1050-190','Portugal','+351 913456789'),(14,14,'Sneha Gurung','Avenida da República 104','Lisboa','1050-190','Portugal','+351 913456789'),(15,15,'Sneha Gurung','Avenida da República 104','Lisboa','1050-190','Portugal','+351 913456789'),(16,16,'Sneha Gurung','Avenida da República 104','Lisboa','1050-190','Portugal','+351 913456789'),(17,17,'Sneha Gurung','Avenida da República 104','Lisboa','1050-190','Portugal','+351 913456789'),(18,18,'Sneha Gurung','Avenida da República 104','Lisboa','1050-190','Portugal','+351 913456789'),(19,19,'Sneha Gurung','Avenida da República 104','Lisboa','1050-190','Portugal','+351 913456789'),(20,20,'Sneha Gurung','Avenida da República 104','Lisboa','1050-190','Portugal','+351 913456789'),(21,21,'Green','Rua do Bonjardim 511','Braga','4700-320','Portugal','+351 914567890'),(22,22,'Green','Rua do Bonjardim 511','Braga','4700-320','Portugal','+351 914567890'),(23,23,'Green','Rua do Bonjardim 511','Braga','4700-320','Portugal','+351 914567890'),(24,24,'Green','Rua do Bonjardim 511','Braga','4700-320','Portugal','+351 914567890'),(25,25,'Green','Rua do Bonjardim 511','Braga','4700-320','Portugal','+351 914567890'),(26,26,'Green','Rua do Bonjardim 511','Braga','4700-320','Portugal','+351 914567890'),(27,27,'Green','Rua do Bonjardim 511','Braga','4700-320','Portugal','+351 914567890'),(28,28,'Green','Rua do Bonjardim 511','Braga','4700-320','Portugal','+351 914567890'),(29,29,'Green','Rua do Bonjardim 511','Braga','4700-320','Portugal','+351 914567890'),(30,30,'Green','Rua do Bonjardim 511','Braga','4700-320','Portugal','+351 914567890'),(31,31,'Sneha Gurung','Rua da Musica 12','Lisboa','1000-001','Portugal','910000000'),(32,32,'Sneha Gurung','Rua da Musica 12','Lisboa','1000-001','Portugal','910000000'),(33,33,'Sneha Gurung','Rua da Musica 12','Lisboa','1000-001','Portugal','910000000'),(34,34,'Sneha Gurung','Rua da Musica 12','Lisboa','1000-001','Portugal','910000000'),(35,35,'Sneha Gurung','Rua da Musica 12','Lisboa','1000-001','Portugal','910000000'),(36,36,'Sneha Gurung','Rua da Musica 12','Lisboa','1000-001','Portugal','910000000'),(37,37,'Sneha Gurung','Rua da Musica 12','Lisboa','1000-001','Portugal','910000000'),(38,38,'Sneha Gurung','Rua da Musica 12','Lisboa','1000-001','Portugal','910000000'),(39,39,'Srijan Gautam','Rua da Musica 12','Lisboa','1000-001','Portugal','910000000'),(40,40,'Srijan Gautam','Rua da Musica 12','Lisboa','1000-001','Portugal','910000000'),(41,41,'Srijan Gautam','Rua da Musica 12','Lisboa','1000-001','Portugal','910000000'),(42,42,'Srijan Gautam','Rua da Musica 12','Lisboa','1000-001','Portugal','910000000'),(43,43,'Srijan Gautam','Rua da Musica 12','Lisboa','1000-001','Portugal','910000000'),(44,44,'Srijan Gautam','Rua da Musica 12','Lisboa','1000-001','Portugal','910000000'),(45,45,'Srijan Gautam','Rua da Musica 12','Lisboa','1000-001','Portugal','910000000'),(46,46,'Srijan Gautam','Rua da Musica 12','Lisboa','1000-001','Portugal','910000000'),(47,47,'Srijan Gautam','Rua da fenache','Almada','2825-009','Portugal','926275311'),(48,48,'Srijan Gautam','Rua da fenache','Almada','2825-009','Portugal','926275311'),(49,49,'Srijan Gautam','Rua da fenache','Almada','2825-009','Portugal','926275311');
/*!40000 ALTER TABLE `morada_encomenda` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `notificacao`
--

DROP TABLE IF EXISTS `notificacao`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `notificacao` (
  `idNotificacao` int(11) NOT NULL AUTO_INCREMENT,
  `idCliente` int(11) NOT NULL,
  `titulo` varchar(160) NOT NULL,
  `mensagem` text NOT NULL,
  `tipo` enum('sistema','produto','musica','encomenda','mensagem','password') NOT NULL DEFAULT 'sistema',
  `lida` tinyint(1) NOT NULL DEFAULT 0,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`idNotificacao`),
  KEY `idx_notificacao_cliente_lida` (`idCliente`,`lida`),
  CONSTRAINT `fk_notificacao_cliente` FOREIGN KEY (`idCliente`) REFERENCES `cliente` (`idCliente`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=60 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `notificacao`
--

LOCK TABLES `notificacao` WRITE;
/*!40000 ALTER TABLE `notificacao` DISABLE KEYS */;
INSERT INTO `notificacao` VALUES (1,5,'Lançamento rejeitado','O lançamento \"PILLOWTALK\" foi rejeitado.','musica',0,'2026-05-27 00:55:19'),(2,5,'Lançamento rejeitado','O lançamento \"What do you mean?\" foi rejeitado.','musica',0,'2026-05-27 00:55:26'),(3,5,'Lançamento rejeitado','O lançamento \"3005\" foi rejeitado. Motivo: Não corresponde ao estilo da curadoria.','musica',0,'2026-05-27 00:55:45'),(4,3,'Lançamento aprovado','O lançamento \"What do you mean?\" foi aprovado e já pode aparecer na música.','musica',0,'2026-05-27 09:40:49'),(5,3,'Lançamento aprovado','O lançamento \"Say it\" foi aprovado e já pode aparecer na música.','musica',0,'2026-05-27 09:40:56'),(6,3,'Lançamento aprovado','O lançamento \"Big Time Sensuality\" foi aprovado e já pode aparecer na música.','musica',0,'2026-05-27 09:40:59'),(7,2,'Lançamento aprovado','O lançamento \"Dean Blunt\" foi aprovado e já pode aparecer na música.','musica',1,'2026-05-27 09:41:01'),(8,2,'Lançamento aprovado','O lançamento \"Unforgettable\" foi aprovado e já pode aparecer na música.','musica',1,'2026-05-27 09:41:18'),(9,3,'Lançamento aprovado','O lançamento \"Dawn FM\" foi aprovado e já pode aparecer na música.','musica',0,'2026-05-27 10:02:44'),(10,3,'Produto aprovado','O produto \"Lamp Tshirt\" foi aprovado e já pode aparecer na loja.','produto',0,'2026-05-27 10:13:05'),(11,3,'Produto aprovado','O produto \"Spike Hat\" foi aprovado e já pode aparecer na loja.','produto',0,'2026-05-27 10:13:08'),(12,3,'Produto aprovado','O produto \"Debut\" foi aprovado e já pode aparecer na loja.','produto',0,'2026-05-27 10:13:11'),(13,2,'Produto aprovado','O produto \"Dean Tshirt\" foi aprovado e já pode aparecer na loja.','produto',1,'2026-05-27 10:13:14'),(14,2,'Produto aprovado','O produto \"Black pleasure cassette\" foi aprovado e já pode aparecer na loja.','produto',1,'2026-05-27 10:13:17'),(15,2,'Produto aprovado','O produto \"Man plays the horn\" foi aprovado e já pode aparecer na loja.','produto',1,'2026-05-27 10:13:20'),(16,2,'Produto aprovado','O produto \"Bladee Stanley\" foi aprovado e já pode aparecer na loja.','produto',1,'2026-05-27 10:13:23'),(17,2,'Produto aprovado','O produto \"333\" foi aprovado e já pode aparecer na loja.','produto',1,'2026-05-27 10:13:26'),(18,2,'Produto aprovado','O produto \"BBF\" foi aprovado e já pode aparecer na loja.','produto',1,'2026-05-27 10:13:29'),(19,2,'Produto aprovado','O produto \"Zushi\" foi aprovado e já pode aparecer na loja.','produto',1,'2026-05-27 10:13:33'),(20,2,'Produto aprovado','O produto \"E\" foi aprovado e já pode aparecer na loja.','produto',1,'2026-05-27 10:13:36'),(21,3,'Lançamento aprovado','O lançamento \"LOUD\" foi aprovado e já pode aparecer na música.','musica',0,'2026-05-27 10:13:42'),(22,3,'Lançamento aprovado','O lançamento \"Dawn FM\" foi aprovado e já pode aparecer na música.','musica',0,'2026-05-27 10:13:45'),(23,3,'Lançamento aprovado','O lançamento \"PILLOWTALK\" foi aprovado e já pode aparecer na música.','musica',0,'2026-05-27 10:13:49'),(24,3,'Lançamento aprovado','O lançamento \"3005\" foi aprovado e já pode aparecer na música.','musica',0,'2026-05-27 10:13:52'),(25,3,'Lançamento aprovado','O lançamento \"Ella Megalast Burls Forever\" foi aprovado e já pode aparecer na música.','musica',0,'2026-05-27 10:13:55'),(26,3,'Lançamento aprovado','O lançamento \"Sure Thing\" foi aprovado e já pode aparecer na música.','musica',0,'2026-05-27 10:16:05'),(27,3,'Lançamento aprovado','O lançamento \"Fashion Killa\" foi aprovado e já pode aparecer na música.','musica',0,'2026-05-27 10:16:08'),(28,3,'Lançamento aprovado','O lançamento \"Break from Toronto\" foi aprovado e já pode aparecer na música.','musica',0,'2026-05-27 10:16:11'),(29,3,'Lançamento aprovado','O lançamento \"Unforgettable\" foi aprovado e já pode aparecer na música.','musica',0,'2026-05-27 10:16:17'),(30,2,'Lançamento aprovado','O lançamento \"Peroxide\" foi aprovado e já pode aparecer na música.','musica',1,'2026-05-27 10:35:33'),(31,2,'Lançamento aprovado','O lançamento \"Black Pleasure 2012\" foi aprovado e já pode aparecer na música.','musica',1,'2026-05-27 10:35:39'),(32,2,'Lançamento aprovado','O lançamento \"7 Eleven\" foi aprovado e já pode aparecer na música.','musica',1,'2026-05-27 10:35:44'),(33,2,'Lançamento aprovado','O lançamento \"Cyberpun edgerunners\" foi aprovado e já pode aparecer na música.','musica',1,'2026-05-27 10:35:47'),(34,2,'Lançamento aprovado','O lançamento \"Night , Blooming Jasmine .\" foi aprovado e já pode aparecer na música.','musica',1,'2026-05-27 10:35:51'),(35,2,'Lançamento aprovado','O lançamento \"100% ELECTRONICA\" foi aprovado e já pode aparecer na música.','musica',1,'2026-05-27 10:35:54'),(36,2,'Lançamento aprovado','O lançamento \"Rush\" foi aprovado e já pode aparecer na música.','musica',1,'2026-05-27 10:36:12'),(37,2,'Lançamento aprovado','O lançamento \"Nice\" foi aprovado e já pode aparecer na música.','musica',1,'2026-05-27 10:36:16'),(38,2,'Lançamento aprovado','O lançamento \"Peroxide\" foi aprovado e já pode aparecer na música.','musica',1,'2026-05-27 10:36:19'),(39,3,'Lançamento inativado','O lançamento \"LOUD\" foi pausado pelo admin.','musica',0,'2026-05-27 10:53:19'),(40,3,'Lançamento reativado','O lançamento \"LOUD\" voltou a ficar ativo.','musica',0,'2026-05-27 10:53:23'),(41,5,'Lançamento aprovado','O lançamento \"Empty\" foi aprovado e já pode aparecer na música.','musica',0,'2026-05-27 11:31:05'),(42,5,'Lançamento aprovado','O lançamento \"Sword\" foi aprovado e já pode aparecer na música.','musica',0,'2026-05-27 11:31:10'),(43,5,'Lançamento aprovado','O lançamento \"Travel the world\" foi aprovado e já pode aparecer na música.','musica',0,'2026-05-27 11:31:14'),(44,5,'Produto aprovado','O produto \"Crystal castles Tshirt\" foi aprovado e já pode aparecer na loja.','produto',0,'2026-05-27 15:15:26'),(45,5,'Produto aprovado','O produto \"Vetements Europe\" foi aprovado e já pode aparecer na loja.','produto',0,'2026-05-27 15:15:30'),(46,5,'Mensagem do comprador','Encomenda #10: Olá Green, consegues confirmar se esta encomenda é oficial?','encomenda',0,'2026-06-02 20:19:06'),(47,2,'Resposta do vendedor','Encomenda #10: Sim, não te preocupes. A encomenda está confirmada.','encomenda',1,'2026-06-03 10:54:21'),(48,3,'Resposta do admin','O admin respondeu a tua mensagem: Receitas não estão a atualizar','mensagem',0,'2026-06-03 18:01:23'),(49,5,'Mensagem do comprador','Encomenda #10: Podes confirmar o envio, por favor?','encomenda',0,'2026-06-04 13:54:04'),(50,2,'Resposta do vendedor','Encomenda #10: Claro, vou atualizar o estado assim que for enviada.','encomenda',1,'2026-06-04 13:58:28'),(51,5,'Produto aprovado','O produto \"Riviera\" foi aprovado e já pode aparecer na loja.','produto',0,'2026-06-04 14:07:27'),(52,2,'Resposta do admin','O admin respondeu a tua mensagem: Problema com o meu lançamento','mensagem',1,'2026-06-04 14:09:16'),(53,2,'Resposta do admin','O admin respondeu a tua mensagem: Dúvida sobre stock do produto','mensagem',1,'2026-06-04 22:38:32'),(54,5,'Resposta do admin','O admin respondeu a tua mensagem: Aprovação de novos produtos','mensagem',0,'2026-06-04 22:38:37'),(55,5,'Resposta do admin','O admin respondeu a tua mensagem: Feedback sobre a plataforma','mensagem',0,'2026-06-04 22:38:48'),(56,5,'Mensagem do comprador','Encomenda #49: Olá, quanto tempo deve demorar a entrega?','encomenda',0,'2026-06-04 23:38:57'),(57,5,'Mensagem do comprador','Encomenda #49: Por favor envia assim que conseguires.','encomenda',0,'2026-06-04 23:39:07'),(58,5,'Encomenda atualizada','A encomenda #24 está agora Cancelado.','encomenda',0,'2026-06-04 23:39:48'),(59,3,'Resposta do admin','O admin respondeu a tua mensagem: Pergunta sobre colaborações','mensagem',0,'2026-06-05 00:02:27');
/*!40000 ALTER TABLE `notificacao` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pagamento`
--

DROP TABLE IF EXISTS `pagamento`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pagamento` (
  `idPagamento` int(11) NOT NULL AUTO_INCREMENT,
  `idEncomenda` int(11) NOT NULL,
  `valor` decimal(10,2) NOT NULL,
  `metodo_pagamento` enum('cartao','mbway','transferencia') NOT NULL,
  `estado_pagamento` enum('pendente','pago','falhado','reembolsado') NOT NULL DEFAULT 'pendente',
  `referencia` varchar(120) DEFAULT NULL,
  `data_pagamento` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`idPagamento`),
  KEY `fk_pagamento_encomenda` (`idEncomenda`),
  CONSTRAINT `fk_pagamento_encomenda` FOREIGN KEY (`idEncomenda`) REFERENCES `encomenda` (`idEncomenda`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=34 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pagamento`
--

LOCK TABLES `pagamento` WRITE;
/*!40000 ALTER TABLE `pagamento` DISABLE KEYS */;
INSERT INTO `pagamento` VALUES (1,1,122.96,'cartao','pago','DEMO-000001','2025-12-28 15:52:19'),(2,2,159.86,'mbway','pago','DEMO-000002','2026-01-15 16:52:19'),(3,3,159.85,'cartao','pago','DEMO-000003','2026-01-29 17:52:19'),(4,4,86.06,'cartao','pago','DEMO-000004','2026-02-20 18:52:19'),(5,5,258.26,'mbway','pago','DEMO-000005','2026-03-06 19:52:19'),(6,6,184.45,'transferencia','pendente','DEMO-000006','2026-03-25 20:52:19'),(7,7,245.96,'cartao','reembolsado','DEMO-000007','2026-04-12 21:52:19'),(8,8,122.96,'mbway','pago','DEMO-000008','2026-04-26 22:52:19'),(9,9,135.25,'transferencia','pago','DEMO-000009','2026-05-09 23:52:19'),(10,10,184.46,'cartao','pago','DEMO-000010','2026-05-21 00:52:19'),(11,11,98.35,'mbway','pago','DEMO-000011','2025-12-28 22:52:19'),(12,12,196.75,'cartao','pago','DEMO-000012','2026-01-15 23:52:19'),(13,13,86.08,'cartao','pago','DEMO-000013','2026-01-30 00:52:19'),(14,14,122.95,'mbway','pago','DEMO-000014','2026-02-21 01:52:19'),(15,15,368.95,'transferencia','pendente','DEMO-000015','2026-03-07 02:52:19'),(16,16,49.18,'cartao','reembolsado','DEMO-000016','2026-03-26 03:52:19'),(17,17,196.75,'mbway','pago','DEMO-000017','2026-04-13 04:52:19'),(18,18,159.85,'transferencia','pago','DEMO-000018','2026-04-27 05:52:19'),(19,19,61.48,'cartao','pago','DEMO-000019','2026-05-10 06:52:19'),(20,20,295.15,'cartao','pago','DEMO-000020','2026-05-21 07:52:19'),(21,21,122.94,'cartao','pago','DEMO-000021','2025-12-29 05:52:19'),(22,22,98.38,'cartao','pago','DEMO-000022','2026-01-16 06:52:19'),(23,23,122.96,'mbway','pago','DEMO-000023','2026-01-30 07:52:19'),(24,24,147.54,'transferencia','pendente','DEMO-000024','2026-02-21 08:52:19'),(25,25,86.08,'cartao','reembolsado','DEMO-000025','2026-03-07 09:52:19'),(26,26,73.76,'mbway','pago','DEMO-000026','2026-03-26 10:52:19'),(27,27,172.14,'transferencia','pago','DEMO-000027','2026-04-13 11:52:19'),(28,28,86.08,'cartao','pago','DEMO-000028','2026-04-27 12:52:19'),(29,29,122.96,'cartao','pago','DEMO-000029','2026-05-10 13:52:19'),(30,30,209.04,'mbway','pago','DEMO-000030','2026-05-21 14:52:19'),(31,47,756.07,'cartao','pago',NULL,'2026-06-04 15:19:41'),(32,48,73.78,'cartao','pago',NULL,'2026-06-05 00:13:50'),(33,49,49.18,'cartao','pago',NULL,'2026-06-05 00:37:28');
/*!40000 ALTER TABLE `pagamento` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `playlist`
--

DROP TABLE IF EXISTS `playlist`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `playlist` (
  `idPlaylist` int(11) NOT NULL AUTO_INCREMENT,
  `idCliente` int(11) NOT NULL,
  `nome` varchar(140) NOT NULL,
  `descricao` text DEFAULT NULL,
  `capa` varchar(255) DEFAULT NULL,
  `visibilidade` enum('privada','publica') NOT NULL DEFAULT 'privada',
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  `atualizado_em` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`idPlaylist`),
  KEY `idx_playlist_cliente` (`idCliente`,`criado_em`),
  CONSTRAINT `fk_playlist_cliente` FOREIGN KEY (`idCliente`) REFERENCES `cliente` (`idCliente`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `playlist`
--

LOCK TABLES `playlist` WRITE;
/*!40000 ALTER TABLE `playlist` DISABLE KEYS */;
INSERT INTO `playlist` VALUES (1,2,'Etheral',NULL,'playlist_2_049b8f8b06c2778c.jpg','privada','2026-06-02 10:56:43','2026-06-02 15:08:41');
/*!40000 ALTER TABLE `playlist` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `playlist_faixa`
--

DROP TABLE IF EXISTS `playlist_faixa`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `playlist_faixa` (
  `idPlaylistFaixa` int(11) NOT NULL AUTO_INCREMENT,
  `idPlaylist` int(11) NOT NULL,
  `idFaixa` int(11) NOT NULL,
  `ordem` int(11) NOT NULL DEFAULT 0,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`idPlaylistFaixa`),
  UNIQUE KEY `uq_playlist_faixa` (`idPlaylist`,`idFaixa`),
  KEY `idx_playlist_faixa_faixa` (`idFaixa`),
  CONSTRAINT `fk_playlist_faixa_faixa` FOREIGN KEY (`idFaixa`) REFERENCES `faixa` (`idFaixa`) ON DELETE CASCADE,
  CONSTRAINT `fk_playlist_faixa_playlist` FOREIGN KEY (`idPlaylist`) REFERENCES `playlist` (`idPlaylist`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `playlist_faixa`
--

LOCK TABLES `playlist_faixa` WRITE;
/*!40000 ALTER TABLE `playlist_faixa` DISABLE KEYS */;
INSERT INTO `playlist_faixa` VALUES (5,1,20,3,'2026-06-02 15:08:11'),(6,1,22,4,'2026-06-02 15:08:34'),(7,1,31,5,'2026-06-02 15:08:41');
/*!40000 ALTER TABLE `playlist_faixa` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `produto`
--

DROP TABLE IF EXISTS `produto`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `produto` (
  `idProduto` int(11) NOT NULL AUTO_INCREMENT,
  `idCliente` int(11) NOT NULL,
  `idCategoria` int(11) NOT NULL,
  `nomeProduto` varchar(150) NOT NULL,
  `descricaoProduto` text DEFAULT NULL,
  `marca` varchar(100) DEFAULT NULL,
  `precoAtual` decimal(10,2) NOT NULL,
  `iva_percentual` decimal(5,2) NOT NULL DEFAULT 23.00,
  `comissao_percentual` decimal(5,2) NOT NULL DEFAULT 5.00,
  `stock_total` int(11) NOT NULL DEFAULT 0,
  `usa_tamanhos` tinyint(1) NOT NULL DEFAULT 0,
  `estado` enum('pendente','aprovado','rejeitado','inativo') NOT NULL DEFAULT 'pendente',
  `ativo` tinyint(1) NOT NULL DEFAULT 1,
  `bloqueado_admin` tinyint(1) NOT NULL DEFAULT 0,
  `motivo_rejeicao` text DEFAULT NULL,
  `idAdminAprovacao` int(11) DEFAULT NULL,
  `aprovado_em` datetime DEFAULT NULL,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  `atualizado_em` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`idProduto`),
  KEY `fk_produto_cliente` (`idCliente`),
  KEY `fk_produto_categoria` (`idCategoria`),
  KEY `fk_produto_admin` (`idAdminAprovacao`),
  KEY `idx_produto_estado_cliente` (`estado`,`idCliente`),
  CONSTRAINT `fk_produto_admin` FOREIGN KEY (`idAdminAprovacao`) REFERENCES `admin` (`idAdmin`) ON DELETE SET NULL,
  CONSTRAINT `fk_produto_categoria` FOREIGN KEY (`idCategoria`) REFERENCES `categoria` (`idCategoria`),
  CONSTRAINT `fk_produto_cliente` FOREIGN KEY (`idCliente`) REFERENCES `cliente` (`idCliente`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=18 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `produto`
--

LOCK TABLES `produto` WRITE;
/*!40000 ALTER TABLE `produto` DISABLE KEYS */;
INSERT INTO `produto` VALUES (1,2,4,'E','Álbum de estreia de Ecco2K, lançado em 2019 pela Drain Gang. Uma edição delicada e experimental para coleci...','Ecco2K',19.99,23.00,5.00,8,0,'aprovado',1,0,NULL,1,'2026-05-27 11:13:34','2026-05-26 23:58:12','2026-06-05 13:01:40'),(2,2,4,'Zushi','Projeto a solo de Dean Blunt, lançado em 2020. Uma edição minimalista e crua para fãs do artista.','Dean Blunt',19.99,23.00,5.00,5,0,'aprovado',1,0,NULL,1,'2026-05-27 11:13:31','2026-05-26 23:59:18','2026-06-05 13:01:17'),(3,2,3,'BBF','Edição especial de BBF Hosted by DJ Escrow, ligada ao universo de Dean Blunt e pensada para colecionadores.','Babyfather',39.99,23.00,5.00,2,0,'aprovado',1,0,NULL,1,'2026-05-27 11:13:27','2026-05-27 00:03:45','2026-06-05 13:01:17'),(4,2,3,'333','Edição física de 333, um lançamento marcante de Bladee com estética brilhante e produção etérea.','Bladee',39.99,23.00,5.00,5,0,'aprovado',1,0,NULL,1,'2026-05-27 11:13:24','2026-05-27 00:05:03','2026-06-05 13:01:17'),(5,2,6,'Bladee Stanley','Copo Stanley de edição limitada com arte inspirada em Bladee. Mantém bebidas frias e funciona como peça de...','Bladee',29.99,23.00,5.00,3,0,'aprovado',1,0,NULL,1,'2026-05-27 11:13:21','2026-05-27 00:06:01','2026-06-05 13:01:40'),(6,2,3,'Man plays the horn','Reedição em vinil de Man Plays the Horn, clássico de jazz de 1956. Uma peça intemporal para coleção.','Cities Aviv',39.99,23.00,5.00,14,0,'aprovado',1,0,NULL,1,'2026-05-27 11:13:18','2026-05-27 00:09:58','2026-06-05 13:01:17'),(7,2,4,'Black pleasure cassette','Cassete de Black Pleasure, projeto de 2012 de Cities Aviv que mistura hip-hop, ruído e estética underground.','Cities Aviv',19.99,23.00,5.00,6,0,'aprovado',1,0,NULL,1,'2026-05-27 11:13:15','2026-05-27 00:11:05','2026-06-05 13:01:17'),(8,2,1,'Dean Tshirt','T-shirt vintage associada ao universo de Dean Blunt. Algodão pesado, corte confortável e aspeto de peça rara.','Supreme',29.99,23.00,5.00,14,1,'aprovado',1,0,NULL,1,'2026-05-27 11:13:12','2026-05-27 00:13:42','2026-06-05 13:01:17'),(9,3,3,'Debut','Edição de Debut, álbum icónico de Björk lançado em 1993. Um registo essencial para fãs de pop alternativo.','Björk',39.99,23.00,5.00,1,0,'aprovado',1,0,NULL,1,'2026-05-27 11:13:09','2026-05-27 00:16:28','2026-06-05 13:01:17'),(10,3,6,'Spike Hat','Chapéu com picos feito à mão, produzido por encomenda com tachas metálicas e estrutura firme.','Sneha',29.99,23.00,5.00,2,0,'aprovado',1,0,NULL,1,'2026-05-27 11:13:06','2026-05-27 00:18:02','2026-06-05 13:01:17'),(11,3,1,'Lamp Tshirt','T-shirt oficial da banda japonesa Lamp. Algodão macio com logótipo discreto no peito.','Lamp',19.99,23.00,5.00,6,1,'aprovado',1,0,NULL,1,'2026-05-27 11:13:03','2026-05-27 00:19:21','2026-06-05 13:01:17'),(12,5,2,'Vetements Europe','Hoodie Vetements Europe de edição limitada. Algodão pesado, corte largo e acabamento premium.','Vetements',89.99,23.00,5.00,0,1,'aprovado',1,0,NULL,1,'2026-05-27 16:15:28','2026-05-27 15:10:46','2026-06-05 13:01:17'),(13,5,1,'Crystal castles Tshirt','Merch original inspirado na fase inicial de Crystal Castles. Peça ligada à história da eletrónica underground.','Crystal Castles',29.99,23.00,5.00,0,1,'aprovado',1,0,NULL,1,'2026-05-27 16:15:21','2026-05-27 15:14:25','2026-06-05 13:01:17'),(14,5,3,'Riviera','Produto inspirado no álbum Riviera dos The Hellp, pensado para fãs de estética alternativa.','The Hellp',39.99,23.00,5.00,9,0,'aprovado',1,0,NULL,1,'2026-06-04 15:07:25','2026-05-27 15:33:46','2026-06-05 13:01:17'),(15,5,4,'Star ','Vinil Star de 2hollis, edição limitada que captura a energia crua do projeto.','Green',24.99,23.00,5.00,49,0,'aprovado',1,0,NULL,NULL,NULL,'2026-05-27 20:01:50','2026-06-05 13:01:17'),(16,5,6,'Mohawk Hat','Chapéu estilo mohawk feito em couro vegan com detalhes metálicos. Uma peça forte para se destacar.','Green',19.99,23.00,5.00,0,0,'aprovado',1,0,NULL,NULL,NULL,'2026-05-27 20:01:50','2026-06-05 13:01:17'),(17,5,6,'Studded Belt','Cinto com tachas em acabamento prateado envelhecido sobre tira preta ajustável.','Green',14.99,23.00,5.00,38,0,'aprovado',1,0,NULL,NULL,NULL,'2026-05-27 20:01:50','2026-06-05 13:01:17');
/*!40000 ALTER TABLE `produto` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `produto_imagem`
--

DROP TABLE IF EXISTS `produto_imagem`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `produto_imagem` (
  `idProdutoImagem` int(11) NOT NULL AUTO_INCREMENT,
  `idProduto` int(11) NOT NULL,
  `ficheiro` varchar(255) NOT NULL,
  `ordem` int(11) NOT NULL DEFAULT 0,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`idProdutoImagem`),
  KEY `idx_produto_imagem_produto` (`idProduto`,`ordem`),
  CONSTRAINT `fk_produto_imagem_produto` FOREIGN KEY (`idProduto`) REFERENCES `produto` (`idProduto`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=26 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `produto_imagem`
--

LOCK TABLES `produto_imagem` WRITE;
/*!40000 ALTER TABLE `produto_imagem` DISABLE KEYS */;
INSERT INTO `produto_imagem` VALUES (1,1,'product_2_26ac7ac3248fd9c8.jpg',0,'2026-05-26 23:58:12'),(2,1,'product_2_75dde490c07f4713.jpg',1,'2026-05-26 23:58:12'),(3,2,'product_2_162a9383f67ffa44.png',0,'2026-05-26 23:59:18'),(4,3,'product_2_709632dc6a8385ab.webp',0,'2026-05-27 00:03:45'),(5,3,'product_2_7267d51528c1bb5a.png',1,'2026-05-27 00:03:45'),(6,4,'product_2_a15e1f90a1b805da.png',0,'2026-05-27 00:05:03'),(7,4,'product_2_204bc65129d0a772.webp',1,'2026-05-27 00:05:03'),(8,5,'product_2_94ca209c2bbcd879.png',0,'2026-05-27 00:06:01'),(9,6,'product_2_1932b160820bef2e.png',0,'2026-05-27 00:09:58'),(10,6,'product_2_14bc4dd5dea3ae4d.jpg',1,'2026-05-27 00:09:58'),(11,7,'product_2_64600bacec73bfec.png',0,'2026-05-27 00:11:05'),(12,8,'product_2_363c3820e1fdef82.png',0,'2026-05-27 00:13:42'),(13,8,'product_2_1846b158e12cbecb.png',1,'2026-05-27 00:13:42'),(14,9,'product_3_64a2200251945dda.png',0,'2026-05-27 00:16:28'),(15,9,'product_3_04db7ed0e005edad.png',1,'2026-05-27 00:16:28'),(16,10,'product_3_21f7df1a9a4c3269.jpg',0,'2026-05-27 00:18:02'),(17,11,'product_3_31ded82f65776524.jpg',0,'2026-05-27 00:19:21'),(18,12,'product_5_a193d8ff3a2994a7.png',0,'2026-05-27 15:10:46'),(19,12,'product_5_f117b035b813105e.jpg',1,'2026-05-27 15:10:46'),(20,13,'product_5_39eb80fcd5511e97.jpg',0,'2026-05-27 15:14:25'),(21,14,'product_5_bbddf43f1265c48c.jpg',0,'2026-05-27 15:33:46'),(22,15,'product_5_adcdd99883c8b165.png',1,'2026-05-27 20:01:50'),(23,15,'product_5_831947e7963f786a.png',2,'2026-05-27 20:01:50'),(24,16,'product_5_4a962e571ce472a9.jpg',1,'2026-05-27 20:01:50'),(25,17,'product_5_b391c88e6af75cea.jpg',1,'2026-05-27 20:01:50');
/*!40000 ALTER TABLE `produto_imagem` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `produto_review`
--

DROP TABLE IF EXISTS `produto_review`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `produto_review` (
  `idReview` int(11) NOT NULL AUTO_INCREMENT,
  `idProduto` int(11) NOT NULL,
  `idCliente` int(11) NOT NULL,
  `idEncomenda` int(11) NOT NULL,
  `rating` tinyint(4) NOT NULL,
  `comentario` varchar(180) DEFAULT NULL,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  `atualizado_em` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`idReview`),
  UNIQUE KEY `uq_produto_review_cliente` (`idProduto`,`idCliente`),
  KEY `idx_produto_review_produto` (`idProduto`,`criado_em`),
  KEY `fk_produto_review_cliente` (`idCliente`),
  KEY `fk_produto_review_encomenda` (`idEncomenda`),
  CONSTRAINT `fk_produto_review_cliente` FOREIGN KEY (`idCliente`) REFERENCES `cliente` (`idCliente`) ON DELETE CASCADE,
  CONSTRAINT `fk_produto_review_encomenda` FOREIGN KEY (`idEncomenda`) REFERENCES `encomenda` (`idEncomenda`) ON DELETE CASCADE,
  CONSTRAINT `fk_produto_review_produto` FOREIGN KEY (`idProduto`) REFERENCES `produto` (`idProduto`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `produto_review`
--

LOCK TABLES `produto_review` WRITE;
/*!40000 ALTER TABLE `produto_review` DISABLE KEYS */;
INSERT INTO `produto_review` VALUES (1,1,3,31,4,'Muito bom. Chegou bem embalado.','2026-05-21 12:43:08','2026-05-21 12:43:08'),(2,2,3,32,5,'Qualidade boa e visual fiel ao site.','2026-05-20 12:43:08','2026-05-20 12:43:08'),(3,3,3,33,4,'Produto bonito e bem acabado.','2026-05-19 12:43:08','2026-05-19 12:43:08'),(4,4,3,34,5,'Gostei. O material parece resistente.','2026-05-18 12:43:08','2026-05-18 12:43:08'),(5,5,3,35,4,'Entrega tranquila e produto impecavel.','2026-05-17 12:43:08','2026-05-17 12:43:08'),(6,6,3,36,5,'Boa compra. Voltaria a encomendar.','2026-05-16 12:43:08','2026-05-16 12:43:08'),(7,7,3,37,4,'Cor e tamanho bateram certo.','2026-05-15 12:43:08','2026-05-15 12:43:08'),(8,8,3,38,5,'Simples, bonito e com boa qualidade.','2026-05-14 12:43:08','2026-05-14 12:43:08'),(9,9,2,39,4,'Muito bom. Chegou bem embalado.','2026-05-13 12:43:08','2026-05-13 12:43:08'),(10,10,2,40,5,'Qualidade boa e visual fiel ao site.','2026-05-12 12:43:08','2026-05-12 12:43:08'),(11,11,2,41,4,'Produto bonito e bem acabado.','2026-05-11 12:43:08','2026-05-11 12:43:08'),(12,12,2,42,5,'Gostei. O material parece resistente.','2026-05-10 12:43:08','2026-05-10 12:43:08'),(13,13,2,43,4,'Entrega tranquila e produto impecavel.','2026-05-09 12:43:08','2026-05-09 12:43:08'),(14,15,2,44,5,'Boa compra. Voltaria a encomendar.','2026-05-08 12:43:08','2026-05-08 12:43:08'),(15,16,2,45,4,'Cor e tamanho bateram certo.','2026-05-07 12:43:08','2026-05-07 12:43:08'),(16,17,2,46,5,'Simples, bonito e com boa qualidade.','2026-05-06 12:43:08','2026-05-06 12:43:08');
/*!40000 ALTER TABLE `produto_review` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `produto_tamanho_stock`
--

DROP TABLE IF EXISTS `produto_tamanho_stock`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `produto_tamanho_stock` (
  `idProdutoTamanho` int(11) NOT NULL AUTO_INCREMENT,
  `idProduto` int(11) NOT NULL,
  `idTamanho` int(11) NOT NULL,
  `stock` int(11) NOT NULL DEFAULT 0,
  `ativo` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`idProdutoTamanho`),
  UNIQUE KEY `uq_produto_tamanho` (`idProduto`,`idTamanho`),
  KEY `fk_produto_tamanho_tamanho` (`idTamanho`),
  CONSTRAINT `fk_produto_tamanho_produto` FOREIGN KEY (`idProduto`) REFERENCES `produto` (`idProduto`) ON DELETE CASCADE,
  CONSTRAINT `fk_produto_tamanho_tamanho` FOREIGN KEY (`idTamanho`) REFERENCES `tamanho` (`idTamanho`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `produto_tamanho_stock`
--

LOCK TABLES `produto_tamanho_stock` WRITE;
/*!40000 ALTER TABLE `produto_tamanho_stock` DISABLE KEYS */;
INSERT INTO `produto_tamanho_stock` VALUES (1,8,1,2,1),(2,8,2,10,1),(3,8,3,5,1),(4,11,1,0,1),(5,11,2,9,1),(6,12,2,0,1),(7,13,2,0,1);
/*!40000 ALTER TABLE `produto_tamanho_stock` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `recuperacao_password`
--

DROP TABLE IF EXISTS `recuperacao_password`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `recuperacao_password` (
  `idRecuperacaoPassword` int(11) NOT NULL AUTO_INCREMENT,
  `idCliente` int(11) NOT NULL,
  `hash_token` varchar(255) NOT NULL,
  `hash_codigo` varchar(255) DEFAULT NULL,
  `expira_em` datetime NOT NULL,
  `usado_em` datetime DEFAULT NULL,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`idRecuperacaoPassword`),
  UNIQUE KEY `hash_token` (`hash_token`),
  KEY `idx_recuperacao_password_cliente` (`idCliente`),
  CONSTRAINT `fk_recuperacao_password_cliente` FOREIGN KEY (`idCliente`) REFERENCES `cliente` (`idCliente`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `recuperacao_password`
--

LOCK TABLES `recuperacao_password` WRITE;
/*!40000 ALTER TABLE `recuperacao_password` DISABLE KEYS */;
/*!40000 ALTER TABLE `recuperacao_password` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `release_musical`
--

DROP TABLE IF EXISTS `release_musical`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `release_musical` (
  `idRelease` int(11) NOT NULL AUTO_INCREMENT,
  `idCliente` int(11) NOT NULL,
  `titulo` varchar(180) NOT NULL,
  `tipo` enum('Single','EP','Album') NOT NULL DEFAULT 'Single',
  `idGenero` int(11) DEFAULT NULL,
  `descricao` text DEFAULT NULL,
  `capa` varchar(255) DEFAULT NULL,
  `data_lancamento` date DEFAULT NULL,
  `estado` enum('pendente','aprovado','rejeitado','inativo') NOT NULL DEFAULT 'pendente',
  `ativo` tinyint(1) NOT NULL DEFAULT 1,
  `bloqueado_admin` tinyint(1) NOT NULL DEFAULT 0,
  `motivo_rejeicao` text DEFAULT NULL,
  `idAdminAprovacao` int(11) DEFAULT NULL,
  `aprovado_em` datetime DEFAULT NULL,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  `atualizado_em` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`idRelease`),
  KEY `fk_release_cliente` (`idCliente`),
  KEY `fk_release_admin` (`idAdminAprovacao`),
  KEY `idx_release_estado_cliente` (`estado`,`idCliente`),
  KEY `idx_release_genero` (`idGenero`),
  CONSTRAINT `fk_release_admin` FOREIGN KEY (`idAdminAprovacao`) REFERENCES `admin` (`idAdmin`) ON DELETE SET NULL,
  CONSTRAINT `fk_release_cliente` FOREIGN KEY (`idCliente`) REFERENCES `cliente` (`idCliente`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=30 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `release_musical`
--

LOCK TABLES `release_musical` WRITE;
/*!40000 ALTER TABLE `release_musical` DISABLE KEYS */;
INSERT INTO `release_musical` VALUES (3,2,'Nice','Single',NULL,'Faixa de 2hollis.','release_2_3ffdc4b18fdf881c.jpg','2026-05-26','aprovado',1,0,NULL,1,'2026-05-27 11:36:13','2026-05-26 22:51:45','2026-06-05 13:01:17'),(4,2,'Rush','Single',NULL,'Faixa de natesib.','release_2_0b4aceebca176bf9.jpg','2026-05-26','aprovado',1,0,NULL,1,'2026-05-27 11:36:09','2026-05-26 22:52:18','2026-06-05 13:01:17'),(5,2,'100% ELECTRONICA','EP',NULL,'EP de George Clanton.','release_2_1d529f210467651e.jpg','2026-05-27','aprovado',1,0,NULL,1,'2026-05-27 11:35:52','2026-05-26 23:36:05','2026-06-05 13:01:17'),(6,2,'Night , Blooming Jasmine .','Single',NULL,'Faixa de Fakemink.','release_2_9b4348dacb4b24ab.jpg','2026-05-27','aprovado',1,0,NULL,1,'2026-05-27 11:35:48','2026-05-26 23:37:32','2026-06-05 13:01:17'),(7,2,'Cyberpun edgerunners','Single',NULL,'Faixa inspirada em Cyberpunk Edgerunners.','release_2_af404a7b33348bc2.jpg','2026-05-27','aprovado',1,0,NULL,1,'2026-05-27 11:35:45','2026-05-26 23:40:50','2026-06-05 13:01:17'),(8,2,'7 Eleven','Single',NULL,'Faixa de Bladee.','release_2_ea9c3b7ed59a3305.jpg','2026-05-27','aprovado',1,0,NULL,1,'2026-05-27 11:35:40','2026-05-26 23:43:39','2026-06-05 13:01:17'),(9,2,'Dean Blunt','EP',NULL,'Seleção de faixas de Dean Blunt.','release_2_1e3df069c2c12795.jpg','2026-05-27','aprovado',1,0,NULL,1,'2026-05-27 10:40:59','2026-05-26 23:49:26','2026-06-05 13:01:17'),(10,2,'Black Pleasure 2012','EP',NULL,'Álbum de Cities Aviv.','release_2_43a366a25be88d12.jpg','2026-05-27','aprovado',1,0,NULL,1,'2026-05-27 11:35:36','2026-05-26 23:53:11','2026-06-05 13:01:17'),(11,2,'Peroxide','Single',NULL,'Faixa de Ecco2K.','release_2_6b21fd4808398888.jpg','2026-05-27','aprovado',1,0,NULL,1,'2026-05-27 11:35:30','2026-05-26 23:57:05','2026-06-05 13:01:17'),(12,3,'Unforgettable','Single',NULL,'Remix de Unforgettable por PnB.','release_3_92eb60f8f1d2b370.jpg','2026-05-27','aprovado',1,0,NULL,1,'2026-05-27 11:16:14','2026-05-27 00:21:05','2026-06-05 13:01:17'),(13,3,'Big Time Sensuality','Single',NULL,'Faixa de Björk.','release_3_c38afbb8024d5eb8.jpg','2026-05-27','aprovado',1,0,NULL,1,'2026-05-27 10:40:56','2026-05-27 00:22:43','2026-06-05 13:01:17'),(14,3,'Break from Toronto','Single',NULL,'Faixa de PARTYNEXTDOOR.','release_3_1ea208794c55a4c9.png','2026-05-27','aprovado',1,0,NULL,1,'2026-05-27 11:16:09','2026-05-27 00:23:50','2026-06-05 13:01:17'),(15,3,'Fashion Killa','Single',NULL,'Faixa de A$AP Rocky.','release_3_cdf541046e5302e3.jpg','2026-05-27','aprovado',1,0,NULL,1,'2026-05-27 11:16:06','2026-05-27 00:28:44','2026-06-05 13:01:17'),(16,3,'Sure Thing','Single',NULL,'Faixa de Miguel.','release_3_4b5ad7d4e60a0e13.jpg','2026-05-27','aprovado',1,0,NULL,1,'2026-05-27 11:16:03','2026-05-27 00:29:47','2026-06-05 13:01:17'),(17,3,'Say it','Single',NULL,'Faixa de Tory Lanez.','release_3_ce6da3ff76b2ce4f.jpg','2026-05-27','aprovado',1,0,NULL,1,'2026-05-27 10:40:54','2026-05-27 00:30:48','2026-06-05 13:01:17'),(18,3,'Ella Megalast Burls Forever','Single',NULL,'Faixa de Cocteau Twins.','release_3_01992f3f9eec5e78.jpg','2026-05-27','aprovado',1,0,NULL,1,'2026-05-27 11:13:53','2026-05-27 00:32:16','2026-06-05 13:01:17'),(19,5,'3005','Single',NULL,'Faixa de Childish Gambino.','release_5_37b1ff497bf3dd31.jpg','2026-05-27','rejeitado',0,0,'Não corresponde ao estilo da curadoria.',1,'2026-05-27 01:55:41','2026-05-27 00:46:22','2026-06-05 13:01:17'),(20,5,'What do you mean?','Single',NULL,'Faixa de Justin Bieber.','release_5_54c3d4c4bf844bd4.jpg','2026-05-28','rejeitado',0,0,'',1,'2026-05-27 01:55:23','2026-05-27 00:49:08','2026-06-05 13:01:17'),(21,5,'PILLOWTALK','Single',NULL,'Faixa de Zayn.','release_5_7a1c4e929e7387b2.jpg','2026-05-27','rejeitado',0,0,'',1,'2026-05-27 01:55:17','2026-05-27 00:52:58','2026-06-05 13:01:17'),(22,3,'3005','Single',NULL,'Faixa de Childish Gambino.','release_3_ad30f741db104eed.jpg','2026-05-27','aprovado',1,0,NULL,1,'2026-05-27 11:13:49','2026-05-27 00:57:35','2026-06-05 13:01:17'),(23,3,'What do you mean?','Single',NULL,'Faixa de Justin Bieber.','release_3_8c746d1dcf3ad811.jpg','2026-05-27','aprovado',1,0,NULL,1,'2026-05-27 10:40:45','2026-05-27 00:58:13','2026-06-05 13:01:17'),(24,3,'PILLOWTALK','Single',NULL,'Faixa de Zayn.','release_3_69564da8adbe9b45.jpg','2026-05-27','aprovado',1,0,NULL,1,'2026-05-27 11:13:47','2026-05-27 00:58:50','2026-06-05 13:01:17'),(25,3,'Dawn FM','EP',NULL,'EP de The Weeknd.','release_3_f36f2b939e965ff0.webp','2026-05-27','aprovado',1,0,NULL,1,'2026-05-27 11:13:43','2026-05-27 09:59:45','2026-06-05 13:01:17'),(26,3,'LOUD','EP',NULL,'EP de Rihanna.','release_3_7ccbd51f0bf2d42e.jpg','2026-05-27','aprovado',1,0,NULL,1,'2026-05-27 11:13:40','2026-05-27 10:11:39','2026-06-05 13:01:17'),(27,5,'Travel the world','Single',NULL,'Criado por Green.','release_5_05654622dddfd2d9.jpg','2026-05-27','aprovado',1,0,NULL,1,'2026-05-27 12:31:11','2026-05-27 11:26:58','2026-06-05 13:01:17'),(28,5,'Sword','Single',NULL,'Criado por Green.','release_5_6ecf02ed5523508b.jpg','2026-01-01','aprovado',1,0,NULL,1,'2026-05-27 12:31:06','2026-05-27 11:28:39','2026-06-05 13:01:17'),(29,5,'Empty','Single',NULL,'Criado por Green.','release_5_7186c757985f3b9a.jpg','2026-01-01','aprovado',1,0,NULL,1,'2026-05-27 12:31:02','2026-05-27 11:30:02','2026-06-05 13:01:17');
/*!40000 ALTER TABLE `release_musical` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `seguir_artista`
--

DROP TABLE IF EXISTS `seguir_artista`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `seguir_artista` (
  `idSeguirArtista` int(11) NOT NULL AUTO_INCREMENT,
  `idSeguidor` int(11) NOT NULL,
  `idArtista` int(11) NOT NULL,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`idSeguirArtista`),
  UNIQUE KEY `uq_seguir_artista` (`idSeguidor`,`idArtista`),
  KEY `idx_seguir_artista_seguidor` (`idSeguidor`,`criado_em`),
  KEY `idx_seguir_artista_artista` (`idArtista`,`criado_em`),
  CONSTRAINT `fk_seguir_artista_artista` FOREIGN KEY (`idArtista`) REFERENCES `cliente` (`idCliente`) ON DELETE CASCADE,
  CONSTRAINT `fk_seguir_artista_seguidor` FOREIGN KEY (`idSeguidor`) REFERENCES `cliente` (`idCliente`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `seguir_artista`
--

LOCK TABLES `seguir_artista` WRITE;
/*!40000 ALTER TABLE `seguir_artista` DISABLE KEYS */;
INSERT INTO `seguir_artista` VALUES (1,2,3,'2026-05-27 22:43:54'),(2,2,5,'2026-05-27 22:43:59'),(3,5,3,'2026-05-27 23:03:12');
/*!40000 ALTER TABLE `seguir_artista` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tamanho`
--

DROP TABLE IF EXISTS `tamanho`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tamanho` (
  `idTamanho` int(11) NOT NULL AUTO_INCREMENT,
  `codigo` varchar(20) NOT NULL,
  `etiqueta` varchar(30) NOT NULL,
  `ordem` int(11) NOT NULL DEFAULT 0,
  `ativo` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`idTamanho`),
  UNIQUE KEY `codigo` (`codigo`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tamanho`
--

LOCK TABLES `tamanho` WRITE;
/*!40000 ALTER TABLE `tamanho` DISABLE KEYS */;
INSERT INTO `tamanho` VALUES (1,'S','S',1,1),(2,'M','M',2,1),(3,'L','L',3,1),(4,'XL','XL',4,1);
/*!40000 ALTER TABLE `tamanho` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `verificacao_email`
--

DROP TABLE IF EXISTS `verificacao_email`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `verificacao_email` (
  `idVerificacaoEmail` int(11) NOT NULL AUTO_INCREMENT,
  `idCliente` int(11) NOT NULL,
  `hash_token` varchar(255) NOT NULL,
  `hash_codigo` varchar(255) DEFAULT NULL,
  `expira_em` datetime NOT NULL,
  `usado_em` datetime DEFAULT NULL,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`idVerificacaoEmail`),
  UNIQUE KEY `hash_token` (`hash_token`),
  KEY `idx_verificacao_email_cliente` (`idCliente`),
  CONSTRAINT `fk_verificacao_email_cliente` FOREIGN KEY (`idCliente`) REFERENCES `cliente` (`idCliente`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `verificacao_email`
--

LOCK TABLES `verificacao_email` WRITE;
/*!40000 ALTER TABLE `verificacao_email` DISABLE KEYS */;
INSERT INTO `verificacao_email` VALUES (2,2,'4576a4d9cc5c44e53f65fd9e98c3decca6d2a5f8fccf84cda8ee764264dfacba',NULL,'2026-05-27 23:26:11','2026-05-26 23:31:50','2026-05-26 22:26:11'),(3,3,'b74be9eac29d9b9fed22271ce68e74a3d522a7afa610327b95442ac66a8c3e64',NULL,'2026-05-27 23:26:37','2026-05-26 23:31:02','2026-05-26 22:26:37'),(4,4,'5c7ee44e6d299f75c078ce890e76e531e1adc5f6b78ccf2b54b41b04e345e1c8',NULL,'2026-05-27 23:30:37',NULL,'2026-05-26 22:30:37'),(5,5,'d105711403d5a852c2b567fc63ead760cafab33875167a18e783867dd0ad9320',NULL,'2026-05-27 23:32:35','2026-05-26 23:32:45','2026-05-26 22:32:35');
/*!40000 ALTER TABLE `verificacao_email` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-06-05 14:03:59
