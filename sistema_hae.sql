-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Tempo de geração: 07/06/2026 às 18:12
-- Versão do servidor: 10.4.32-MariaDB
-- Versão do PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Banco de dados: `sistema_hae`
--

-- --------------------------------------------------------

--
-- Estrutura para tabela `categorias_projeto`
--

CREATE TABLE `categorias_projeto` (
  `id` int(11) NOT NULL,
  `nome` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `categorias_projeto`
--

INSERT INTO `categorias_projeto` (`id`, `nome`) VALUES
(1, 'Acadêmico'),
(2, 'Administrativo'),
(3, 'Extensão à comunidade');

-- --------------------------------------------------------

--
-- Estrutura para tabela `relatorios_hae`
--

CREATE TABLE `relatorios_hae` (
  `id` int(11) NOT NULL,
  `solicitacao_id` int(11) NOT NULL,
  `mes_referencia` int(11) NOT NULL,
  `ano_referencia` int(11) NOT NULL,
  `acoes_realizadas` text NOT NULL,
  `status` enum('Rascunho','Publicado') DEFAULT 'Rascunho',
  `data_envio` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `relatorios_hae`
--

INSERT INTO `relatorios_hae` (`id`, `solicitacao_id`, `mes_referencia`, `ano_referencia`, `acoes_realizadas`, `status`, `data_envio`) VALUES
(11, 3, 6, 2026, 'bbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbb bbbbbbbb b bbbb', 'Rascunho', '2026-06-04 01:41:05'),
(14, 4, 6, 2026, 'aaaahh', 'Rascunho', '2026-06-05 00:56:59'),
(15, 4, 5, 2026, 'vixiiii', 'Rascunho', '2026-06-05 01:04:23'),
(16, 10, 5, 2026, 'hahaha', 'Publicado', '2026-06-05 01:06:46'),
(17, 12, 5, 2026, 'hahahahahaha hahaha haha ahahah ahahah ahha bababa babba attatata tatt', 'Publicado', '2026-06-05 22:38:07'),
(18, 12, 6, 2026, 'eiiiitaaaaaaaaaaaaaa hahahahhahahah ahhahahha hahhah hah', 'Publicado', '2026-06-05 22:40:29');

-- --------------------------------------------------------

--
-- Estrutura para tabela `solicitacoes_hae`
--

CREATE TABLE `solicitacoes_hae` (
  `id` int(11) NOT NULL,
  `professor_id` int(11) NOT NULL,
  `coordenador_id` int(11) DEFAULT NULL,
  `diretor_id` int(11) DEFAULT NULL,
  `parecer_coordenador` text DEFAULT NULL,
  `data_aprovacao_coordenador` date DEFAULT NULL,
  `data_parecer_coordenador` date DEFAULT NULL,
  `semestre` varchar(10) NOT NULL,
  `quantidade_horas` int(11) NOT NULL,
  `horas_aprovadas` int(11) DEFAULT NULL,
  `titulo_projeto` varchar(150) NOT NULL,
  `projeto_anterior` tinyint(1) DEFAULT 0,
  `nome_projeto_anterior` varchar(150) DEFAULT NULL,
  `objetivos_escola` text DEFAULT NULL,
  `horas_aula` int(11) NOT NULL,
  `horas_atividade` int(11) NOT NULL,
  `horas_especificas` int(11) NOT NULL,
  `total_semanal` int(11) NOT NULL,
  `total_mensal` int(11) NOT NULL,
  `categoria` varchar(255) NOT NULL,
  `justificativa` text NOT NULL,
  `objetivo` text NOT NULL,
  `metodologia` text NOT NULL,
  `envolvidos` text NOT NULL,
  `recursos_necessarios` set('Financeiro','Físico','Humano') NOT NULL,
  `detalhamento_recursos` text DEFAULT NULL,
  `cronograma` text NOT NULL,
  `resultados_esperados` text NOT NULL,
  `status_aprovacao` enum('Pendente','Aprovado','Rejeitado') DEFAULT 'Pendente',
  `status_coordenador` varchar(20) DEFAULT 'Pendente',
  `status_diretor` varchar(20) DEFAULT 'Pendente',
  `parecer_diretor` text DEFAULT NULL,
  `data_aprovacao_diretor` date DEFAULT NULL,
  `assinatura_coordenador` varchar(255) DEFAULT NULL,
  `assinatura_diretor` varchar(255) DEFAULT NULL,
  `data_criacao` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `solicitacoes_hae`
--

INSERT INTO `solicitacoes_hae` (`id`, `professor_id`, `coordenador_id`, `diretor_id`, `parecer_coordenador`, `data_aprovacao_coordenador`, `data_parecer_coordenador`, `semestre`, `quantidade_horas`, `horas_aprovadas`, `titulo_projeto`, `projeto_anterior`, `nome_projeto_anterior`, `objetivos_escola`, `horas_aula`, `horas_atividade`, `horas_especificas`, `total_semanal`, `total_mensal`, `categoria`, `justificativa`, `objetivo`, `metodologia`, `envolvidos`, `recursos_necessarios`, `detalhamento_recursos`, `cronograma`, `resultados_esperados`, `status_aprovacao`, `status_coordenador`, `status_diretor`, `parecer_diretor`, `data_aprovacao_diretor`, `assinatura_coordenador`, `assinatura_diretor`, `data_criacao`) VALUES
(1, 1, 7, 2, 'é ok', '2026-06-03', NULL, '12/04/2011', 3, NULL, 'Relacionamentos com empresas de TI.', 0, NULL, 'Realizar o relacionamento junto às empresas de TI, a fim de tornar a Fatec Garça mais conhecida e buscar junto a estas parcerias, apoio para a unidade e fortalecimento dos cursos.', 24, 12, 8, 44, 198, 'Administrativo', 'Buscar junto a empresas de tecnologia da informação uma integração (Faculdade x Empresas) proporcionando ganhos de conhecimentos e troca de experiências para as partes. ', 'Realizar o relacionamento junto às empresas de TI, tornando a Fatec Garça mais conhecida e buscar junto a estas parcerias, apoio para a unidade e fortalecimento dos cursos.', 'Este relacionamento acontecerá através de visitas e reuniões entre as partes, tanto na unidade de ensino como nas empresas.', 'Professor, Direção e Empresas de TI.', 'Físico,Humano', '', 'As atividades irão transcorrer nos meses que compreende o segundo semestre do ano de 2024.', 'Conseguir agregar benefícios para a Fatec Garça e para as empresas de TI.', 'Aprovado', 'Aprovado', 'Aprovado', 'boa', '2026-06-03', NULL, NULL, '2026-05-05 19:45:28'),
(2, 3, 7, 2, 'blz', '2026-06-03', NULL, '12/04/2026', 4, NULL, 'Relacionamentos com empresas de TI.', 0, NULL, 'wersdtfygukfdbsvca\\', 24, 12, 8, 44, 198, 'Acadêmico', 'AFSGDHTFUKJL,JMGHNFBVZC\\Xz', 'ewgrehtryjuiloç,gjfmhdnsbacfsbfbf fsdgjfyu', 'wshrderty sfjdesrdtfy easrdtfy dzxfcfg', 'wertjy qWAESRYKTU ', 'Físico,Humano', 'ERTTIYOIYLTDSHJYKUIL HRJYKUFIL, ', 'MARCO A JULHO DE 2025', 'SFDJTFRYTOYUPO AGDSZDHXFCJGKHegty', 'Aprovado', 'Aprovado', 'Aprovado', '', '2026-06-03', NULL, NULL, '2026-05-05 22:13:35'),
(3, 4, 7, 2, 'ok certo', '2026-06-03', NULL, '2/2026', 5, NULL, 'Relacionamentos com empresas de TI.', 0, NULL, 'aaaaaaaa', 23, 12, 5, 40, 178, '', 'aaaaaaaaaaaa', 'aaaaaaaa', 'aaaaaaaaaaaaaaaa', 'aaaaaaaaaa', 'Financeiro,Físico', 'aaaaaaaaaaaa', 'aaaaaaaaaaa', 'aaaaaaaaaaa', 'Aprovado', 'Aprovado', 'Aprovado', 'não esta bom', '2026-06-03', NULL, NULL, '2026-05-31 11:59:24'),
(4, 1, 7, 2, 'ok', '2026-06-03', NULL, '2/2026', 4, NULL, 'Relacionamentos com empresas de TI.', 0, NULL, 'vvvvvvvvvvvvvvvvvv', 23, 12, 5, 40, 178, 'Visita Técnica', 'vvvvvvvvvvvvvvvvvvvvv', 'vvvvvvvvvvvvvvvvvvvvvv', 'vvvvvvvvvvv', 'vvvvvvvvvvvvvvvvvv', 'Físico,Humano', 'vvvvvvvvvvvvv', 'vvvvvvvvvvvvvvvvv', 'vvvvvvvvvvvvvvvvvvvv', 'Aprovado', 'Aprovado', 'Aprovado', 'certo ok', '2026-06-03', NULL, NULL, '2026-05-31 12:04:51'),
(5, 6, 7, 2, 'excelente', '2026-06-03', NULL, '1/2026', 7, NULL, 'Tecnologia Inovação', 0, NULL, 'teste testando teste teste teste tastando aaaah testando', 24, 12, 8, 44, 198, 'academicas', 'teste testando teste teste teste tastando aaaah testando', 'teste testando teste teste teste tastando aaaah testando', 'teste testando teste teste teste tastando aaaah testando', 'teste testando teste teste teste tastando aaaah testando', 'Financeiro,Físico', 'teste testando teste teste teste tastando aaaah testando', 'teste testando teste teste teste tastando aaaah testando', 'teste testando teste teste teste tastando aaaah testando', 'Aprovado', 'Aprovado', 'Aprovado', 'teste testando teste teste teste tastando aaaah testandotgdhyfdxmzfksjnhxvb edddddxcc  bbbbb', '2026-06-03', NULL, NULL, '2026-06-02 00:24:59'),
(6, 6, 7, 2, 'otimo', '2026-06-03', NULL, '1/2025', 7, NULL, 'Sistemas da informação', 0, NULL, 'aaaa aaa aaaaaaaa aaaaaaaaaaa aaaaa  aaaaaa aaaa aaaaaaa  aa aaa aaaaaa aaaaaaaaaaaa aaaaaaaaaaaaaa', 24, 12, 8, 44, 198, 'Administrativo', 'aaaa aaa aaaaaaaa aaaaaaaaaaa aaaaa  aaaaaa aaaa aaaaaaa  aa aaa aaaaaa aaaaaaaaaaaa aaaaaaaaaaaaaa', 'aaaa aaa aaaaaaaa aaaaaaaaaaa aaaaa  aaaaaa aaaa aaaaaaa  aa aaa aaaaaa aaaaaaaaaaaa aaaaaaaaaaaaaa', 'aaaa aaa aaaaaaaa aaaaaaaaaaa aaaaa  aaaaaa aaaa aaaaaaa  aa aaa aaaaaa aaaaaaaaaaaa aaaaaaaaaaaaaa', 'aaaa aaa aaaaaaaa aaaaaaaaaaa aaaaa  aaaaaa aaaa aaaaaaa  aa aaa aaaaaa aaaaaaaaaaaa aaaaaaaaaaaaaa', 'Físico,Humano', 'aaaa aaa aaaaaaaa aaaaaaaaaaa aaaaa  aaaaaa aaaa aaaaaaa  aa aaa aaaaaa aaaaaaaaaaaa aaaaaaaaaaaaaa', 'aaaa aaa aaaaaaaa aaaaaaaaaaa aaaaa  aaaaaa aaaa aaaaaaa  aa aaa aaaaaa aaaaaaaaaaaa aaaaaaaaaaaaaa', 'aaaa aaa aaaaaaaa aaaaaaaaaaa aaaaa  aaaaaa aaaa aaaaaaa  aa aaa aaaaaa aaaaaaaaaaaa aaaaaaaaaaaaaa', 'Aprovado', 'Aprovado', 'Aprovado', 'otimo correto', '2026-06-03', NULL, NULL, '2026-06-02 23:26:03'),
(7, 4, 7, 2, 'aeeeeee aeeeee', '2026-06-03', '2026-06-03', '1/2026', 7, NULL, 'Projeto testeee', 0, NULL, 'eeeeeeeeeeeeeeeeeeeeee e e eeee eeee e eeeeeeeee', 24, 12, 8, 44, 198, 'Acadêmico', 'eeeeeeeeeeeeeeeeeeeeee e e eeee eeee e eeeeeeeee', 'eeeeeeeeeeeeeeeeeeeeee e e eeee eeee e eeeeeeeee', 'eeeeeeeeeeeeeeeeeeeeee e e eeee eeee e eeeeeeeee', 'eeeeeeeeeeeeeeeeeeeeee e e eeee eeee e eeeeeeeee', 'Financeiro,Físico', 'eeeeeeeeeeeeeeeeeeeeee e e eeee eeee e eeeeeeeee', 'eeeeeeeeeeeeeeeeeeeeee e e eeee eeee e eeeeeeeee', 'eeeeeeeeeeeeeeeeeeeeee e e eeee eeee e eeeeeeeee', 'Aprovado', 'Aprovado', 'Aprovado', 'ok tudo certoo', '2026-06-03', NULL, NULL, '2026-06-03 00:19:26'),
(8, 3, 7, 2, 'tudo certinho ok', '2026-06-03', NULL, '2/2026', 8, NULL, 'projeto ADS', 0, NULL, 'o oooooooo ooooo oo  ooooo  oo oooo o poooo', 24, 12, 8, 44, 198, 'Acadêmico', 'o oooooooo ooooo oo  ooooo  oo oooo o poooo', 'o oooooooo ooooo oo  ooooo  oo oooo o poooo', 'o oooooooo ooooo oo  ooooo  oo oooo o poooo', 'o oooooooo ooooo oo  ooooo  oo oooo o poooo', 'Financeiro,Físico', 'o oooooooo ooooo oo  ooooo  oo oooo o poooo', 'o oooooooo ooooo oo  ooooo  oo oooo o poooo', 'o oooooooo ooooo oo  ooooo  oo oooo o poooo', 'Aprovado', 'Aprovado', 'Aprovado', 'opa tudo bem', '2026-06-03', NULL, NULL, '2026-06-03 00:37:50'),
(9, 3, 7, 2, 'otimo', '2026-06-03', NULL, '2/2026', 8, NULL, 'Gestao Empresarial', 0, NULL, 'uhulllll uhuulll uhluuu hulllll uhullll uuu hulllll uhullll uuu hulllll uhullll', 24, 12, 8, 44, 198, 'Administrativo', 'uhulllll uhuulll uhluuu hulllll uhullll uuu hulllll uhullll uuu hulllll uhullll', 'uhulllll uhuulll uhluuu hulllll uhullll uuu hulllll uhullll uuu hulllll uhullll', 'uhulllll uhuulll uhluuu hulllll uhullll uuu hulllll uhullll uuu hulllll uhullll', 'uhulllll uhuulll uhluuu hulllll uhullll uuu hulllll uhullll uuu hulllll uhullll', '', 'uhulllll uhuulll uhluuu hulllll uhullll uuu hulllll uhullll uuu hulllll uhullll', 'uhulllll uhuulll uhluuu hulllll uhullll uuu hulllll uhullll uuu hulllll uhullll', 'uhulllll uhuulll uhluuu hulllll uhullll uuu hulllll uhullll uuu hulllll uhullll', 'Aprovado', 'Aprovado', 'Aprovado', 'beeeeeeeeeeeeeeellllllleeeeeeeeeeeeeeezzzzzzaaaaaaaaaaa', '2026-06-03', NULL, NULL, '2026-06-03 00:57:24'),
(10, 8, 7, 2, 'otimo esta tudo certo com seu documeto HAE. Aprovado!!', '2026-06-05', NULL, '1/2026', 8, NULL, 'Testando projeto eduarda', 0, NULL, 'aaaaaaaaaa aaaaaaaaaaa aaaaa aaaaa aa a aaaa aaaa  aaaaaa  a a', 24, 12, 8, 44, 198, 'Acadêmico', 'aaaa aaa aaaaaa aaa aaaaaa aaaaaaaaaa aaaaaaaaaaa aaaaa aaaaa aa a aaaa aaaa  aaaaaa  a a', 'aaaaaaaaaa aaaaaaaaaaa aaaaa aaaaa aa a aaaa aaaa  aaaaaa  a a', 'aaaaaaaaaa aaaaaaaaaaa aaaaa aaaaa aa a aaaa aaaa  aaaaaa  a a', 'aaaaaaaaaa aaaaaaaaaaa aaaaa aaaaa aa a aaaa aaaa  aaaaaa  a a', 'Financeiro,Humano', 'aaaaaaaaaa aaaaaaaaaaa aaaaa aaaaa aa a aaaa aaaa  aaaaaa  a a', 'aaaaaaaaaa aaaaaaaaaaa aaaaa aaaaa aa a aaaa aaaa  aaaaaa  a a', 'aaaaaaaaaa aaaaaaaaaaa aaaaa aaaaa aa a aaaa aaaa  aaaaaa  a a', 'Aprovado', 'Aprovado', 'Aprovado', 'otimo esta tudo certo com seu documeto HAE. Aprovado!!!!!', '2026-06-05', NULL, NULL, '2026-06-03 22:50:35'),
(11, 8, NULL, 2, NULL, NULL, NULL, '2/2026', 8, NULL, 'projeto de teste novamente haha', 0, NULL, 'mds meu senhor me ajuda por favor ', 24, 12, 8, 44, 198, 'Administrativo', 'mds meu senhor me ajuda por favor ', 'mds meu senhor me ajuda por favor ', 'mds meu senhor me ajuda por favor ', 'mds meu senhor me ajuda por favor ', 'Financeiro', 'mds meu senhor me ajuda por favor ', 'mds meu senhor me ajuda por favor ', 'mds meu senhor me ajuda por favor ', 'Pendente', 'Pendente', 'Aprovado', 'correto haha', '2026-06-06', NULL, NULL, '2026-06-05 21:22:03'),
(12, 9, 7, 2, 'otimo, perfeito', '2026-06-06', NULL, '2/2026', 8, NULL, 'Projeto do professor João', 0, NULL, 'eitaaaaaaaaaaaaaaaaaaaa eitaaaaaaaaaaa eeeeeeeeeeeeiiiiiiiiiiiiitttttttttttttaaaaaaaaaaaaaa', 24, 12, 8, 44, 198, 'Administrativo', 'eitaaaaaaaaaaaaaaaaaaaa eitaaaaaaaaaaa eeeeeeeeeeeeiiiiiiiiiiiiitttttttttttttaaaaaaaaaaaaaa', 'eitaaaaaaaaaaaaaaaaaaaa eitaaaaaaaaaaa eeeeeeeeeeeeiiiiiiiiiiiiitttttttttttttaaaaaaaaaaaaaa', 'eitaaaaaaaaaaaaaaaaaaaa eitaaaaaaaaaaa eeeeeeeeeeeeiiiiiiiiiiiiitttttttttttttaaaaaaaaaaaaaa', 'eitaaaaaaaaaaaaaaaaaaaa eitaaaaaaaaaaa eeeeeeeeeeeeiiiiiiiiiiiiitttttttttttttaaaaaaaaaaaaaa', 'Financeiro', 'eitaaaaaaaaaaaaaaaaaaaa eitaaaaaaaaaaa eeeeeeeeeeeeiiiiiiiiiiiiitttttttttttttaaaaaaaaaaaaaa', 'eitaaaaaaaaaaaaaaaaaaaa eitaaaaaaaaaaa eeeeeeeeeeeeiiiiiiiiiiiiitttttttttttttaaaaaaaaaaaaaa', 'eitaaaaaaaaaaaaaaaaaaaa eitaaaaaaaaaaa eeeeeeeeeeeeiiiiiiiiiiiiitttttttttttttaaaaaaaaaaaaaa', 'Aprovado', 'Aprovado', 'Aprovado', 'Otimo, perfeito', '2026-06-06', NULL, NULL, '2026-06-05 22:31:27');

-- --------------------------------------------------------

--
-- Estrutura para tabela `usuarios`
--

CREATE TABLE `usuarios` (
  `id` int(11) NOT NULL,
  `nome` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `telefone_whatsapp` varchar(20) DEFAULT NULL,
  `funcao` enum('Professor','Coordenador','Diretor') NOT NULL,
  `data_nascimento` date DEFAULT NULL,
  `data_admissao` date DEFAULT NULL,
  `tipo_contrato` enum('Determinado','Indeterminado') DEFAULT NULL,
  `formacao_academica` text DEFAULT NULL,
  `assinatura_path` varchar(255) DEFAULT NULL,
  `senha` varchar(255) NOT NULL,
  `primeiro_acesso` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `usuarios`
--

INSERT INTO `usuarios` (`id`, `nome`, `email`, `telefone_whatsapp`, `funcao`, `data_nascimento`, `data_admissao`, `tipo_contrato`, `formacao_academica`, `assinatura_path`, `senha`, `primeiro_acesso`) VALUES
(1, 'Stefani Santos', 'stefanisantos1212@gmail.com', '(14) 99837-3207', 'Professor', '2006-11-12', '2026-02-02', 'Indeterminado', 'ADS', 'uploads/assinaturas/cb407ab5771005be2cb9e453adf39fb3.jpg', '1d8bbc4294d306e8ba3ec733b0b06180', 0),
(2, 'Diretor Teste', 'diretor@fatec.sp.gov.br', '14999999999', 'Diretor', '1980-03-15', '2010-02-01', 'Indeterminado', 'Doutorado em Educação', 'uploads/assinaturas/4256e35039565917b300e919c260083b.jpg', '1d8bbc4294d306e8ba3ec733b0b06180', 0),
(3, 'Teste professora', 'professora@gmail.com', '(14) 99837-3207', 'Professor', '2006-11-12', '2025-03-01', 'Indeterminado', 'ADS', 'uploads/assinaturas/dd9d1889e20ed1ca009b5a54459f8795.jpg', '1d8bbc4294d306e8ba3ec733b0b06180', 0),
(4, 'professor_teste', 'professorteste@gmail.com', '(14) 99837-3207', 'Professor', '2006-11-12', '2014-04-04', 'Indeterminado', 'especialista em TI', 'uploads/assinaturas/4c7bd8b0a262942fec377e0cb3763944.jpg', '1d8bbc4294d306e8ba3ec733b0b06180', 0),
(6, 'Victor Matheus', 'victor.matheus@cps.sp.gov.br', '(14) 99837-3207', 'Professor', '1999-12-08', '2023-03-02', 'Indeterminado', 'ADS', 'uploads/assinaturas/79f9c768743cb1228a7d8ae3e3129edd.jpg', '1d8bbc4294d306e8ba3ec733b0b06180', 0),
(7, 'Adriano Nakamura', 'nakamura@cps.sp.gov.br', '(14) 99837-3207', 'Coordenador', '1957-10-28', '2009-04-12', 'Indeterminado', 'nakamura@cps.sp.gov.br', 'uploads/assinaturas/5e53c2367047a679b99234e8cd7962a9.avif', '1d8bbc4294d306e8ba3ec733b0b06180', 0),
(8, 'eduarda professora', 'eduardaprofessora@gmail.com', '(14) 99837-3207', 'Professor', '2007-02-03', '2022-05-04', 'Indeterminado', 'eduardaprofessora@gmail.com', 'uploads/assinaturas/23c6560e00f19601811392924978be82.jpg', '1d8bbc4294d306e8ba3ec733b0b06180', 0),
(9, 'joao professor', 'joao@gmail.com', '(14) 99837-3207', 'Professor', '2000-11-12', '2023-03-04', 'Indeterminado', 'joao@gmail.com', 'uploads/assinaturas/9dd5bb59783570ede18df44b647cf7ca.svg', '1d8bbc4294d306e8ba3ec733b0b06180', 0),
(10, 'maria teste', 'maria@gmail.com', '(14) 99837-3207', 'Professor', '2007-11-12', NULL, NULL, NULL, NULL, 'c0d5186fbca9cf93928bff8d214a6676', 1);

--
-- Índices para tabelas despejadas
--

--
-- Índices de tabela `categorias_projeto`
--
ALTER TABLE `categorias_projeto`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nome` (`nome`);

--
-- Índices de tabela `relatorios_hae`
--
ALTER TABLE `relatorios_hae`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_projeto_mes_ano` (`solicitacao_id`,`mes_referencia`,`ano_referencia`),
  ADD KEY `solicitacao_id` (`solicitacao_id`);

--
-- Índices de tabela `solicitacoes_hae`
--
ALTER TABLE `solicitacoes_hae`
  ADD PRIMARY KEY (`id`),
  ADD KEY `professor_id` (`professor_id`);

--
-- Índices de tabela `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT para tabelas despejadas
--

--
-- AUTO_INCREMENT de tabela `categorias_projeto`
--
ALTER TABLE `categorias_projeto`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT de tabela `relatorios_hae`
--
ALTER TABLE `relatorios_hae`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT de tabela `solicitacoes_hae`
--
ALTER TABLE `solicitacoes_hae`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT de tabela `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- Restrições para tabelas despejadas
--

--
-- Restrições para tabelas `relatorios_hae`
--
ALTER TABLE `relatorios_hae`
  ADD CONSTRAINT `relatorios_hae_ibfk_1` FOREIGN KEY (`solicitacao_id`) REFERENCES `solicitacoes_hae` (`id`);

--
-- Restrições para tabelas `solicitacoes_hae`
--
ALTER TABLE `solicitacoes_hae`
  ADD CONSTRAINT `solicitacoes_hae_ibfk_1` FOREIGN KEY (`professor_id`) REFERENCES `usuarios` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
