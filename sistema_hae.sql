-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Tempo de geração: 01/06/2026 às 03:25
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
(1, 1, 5, 2026, 'Dia 06/11/2024 reunião do comitê organizador do evento a pedido do Fábio Dias foi reagendado, pois o presidente da ACIG Fábio Raniel não poderá participar.\r\n\r\nDia 11/11/2024 reunião do comitê organizador do evento a pedido do presidente da Acig, Fábio Raniel foi adiado.\r\n\r\nDia 19/11/2024 reunião com Wagner, empresa Fulltime que está apoiando e nos ajudando a trazer a Amazon para o evento. Discutimos possíveis eventos (Deep Racer e Hackathon) a serem inseridos na feira e a questão da Acig.\r\n\r\nDia 26/11/2024 reunião com Wilson e Fernando da Fulltime, onde a Amazon pediu ajustes no site e redes sociais para conseguir a aprovação da participação do evento. Será também agendada reunião com o Mauro que faz parte do conselho deliberativo da Acig para novos alinhamentos, na primeira semana de Dezembro.\r\n\r\nDia 28/11/2024 atualizado o site e as redes sociais as melhorias solicitadas pela Amazon para a aprovação de sua participação no evento.', 'Publicado', '2026-05-05 21:50:44'),
(2, 2, 4, 2026, 'asdtfrytuylhj asrdtfygh asrdtjfgyhl etwayesrudtfygAESRYDTFYI AAAAAAAAAAAAA SDD223', 'Publicado', '2026-05-05 22:27:25'),
(3, 2, 5, 2026, 'SADRDHTRJYKUL', 'Rascunho', '2026-05-05 22:22:22'),
(4, 1, 4, 2026, 'AXZ\\ZCVX CDSA\\AX\\CXVBNXS\\ WFERD DF BGCVXCZSX AAAAAAAAAAAAA', 'Publicado', '2026-05-31 21:45:44'),
(5, 4, 6, 2026, 'testandoooooo   testandoooooo testandoooooo testandoooooo vv testandoooooo', 'Publicado', '2026-06-01 00:44:50'),
(6, 1, 6, 2026, 'eeeeeeeeeeeeeeeeeeee eeeeeeeeeeeeeeee eeeeeeeeeee eeeeeeeeeeee', 'Publicado', '2026-06-01 00:44:37'),
(7, 4, 5, 2026, 'rrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrr', 'Publicado', '2026-06-01 01:22:13');

-- --------------------------------------------------------

--
-- Estrutura para tabela `solicitacoes_hae`
--

CREATE TABLE `solicitacoes_hae` (
  `id` int(11) NOT NULL,
  `professor_id` int(11) NOT NULL,
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
  `parecer_direcao` text DEFAULT NULL,
  `data_criacao` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `solicitacoes_hae`
--

INSERT INTO `solicitacoes_hae` (`id`, `professor_id`, `semestre`, `quantidade_horas`, `horas_aprovadas`, `titulo_projeto`, `projeto_anterior`, `nome_projeto_anterior`, `objetivos_escola`, `horas_aula`, `horas_atividade`, `horas_especificas`, `total_semanal`, `total_mensal`, `categoria`, `justificativa`, `objetivo`, `metodologia`, `envolvidos`, `recursos_necessarios`, `detalhamento_recursos`, `cronograma`, `resultados_esperados`, `status_aprovacao`, `parecer_direcao`, `data_criacao`) VALUES
(1, 1, '12/04/2011', 3, NULL, 'Relacionamentos com empresas de TI.', 0, NULL, 'Realizar o relacionamento junto às empresas de TI, a fim de tornar a Fatec Garça mais conhecida e buscar junto a estas parcerias, apoio para a unidade e fortalecimento dos cursos.', 24, 12, 8, 44, 198, 'Administrativo', 'Buscar junto a empresas de tecnologia da informação uma integração (Faculdade x Empresas) proporcionando ganhos de conhecimentos e troca de experiências para as partes. ', 'Realizar o relacionamento junto às empresas de TI, tornando a Fatec Garça mais conhecida e buscar junto a estas parcerias, apoio para a unidade e fortalecimento dos cursos.', 'Este relacionamento acontecerá através de visitas e reuniões entre as partes, tanto na unidade de ensino como nas empresas.', 'Professor, Direção e Empresas de TI.', 'Físico,Humano', '', 'As atividades irão transcorrer nos meses que compreende o segundo semestre do ano de 2024.', 'Conseguir agregar benefícios para a Fatec Garça e para as empresas de TI.', 'Aprovado', NULL, '2026-05-05 19:45:28'),
(2, 3, '12/04/2026', 4, NULL, 'Relacionamentos com empresas de TI.', 0, NULL, 'wersdtfygukfdbsvca\\', 24, 12, 8, 44, 198, 'Acadêmico', 'AFSGDHTFUKJL,JMGHNFBVZC\\Xz', 'ewgrehtryjuiloç,gjfmhdnsbacfsbfbf fsdgjfyu', 'wshrderty sfjdesrdtfy easrdtfy dzxfcfg', 'wertjy qWAESRYKTU ', 'Físico,Humano', 'ERTTIYOIYLTDSHJYKUIL HRJYKUFIL, ', 'MARCO A JULHO DE 2025', 'SFDJTFRYTOYUPO AGDSZDHXFCJGKHegty', 'Aprovado', NULL, '2026-05-05 22:13:35'),
(3, 4, '2/2026', 5, NULL, 'Relacionamentos com empresas de TI.', 0, NULL, 'aaaaaaaa', 23, 12, 5, 40, 178, '', 'aaaaaaaaaaaa', 'aaaaaaaa', 'aaaaaaaaaaaaaaaa', 'aaaaaaaaaa', 'Financeiro,Físico', 'aaaaaaaaaaaa', 'aaaaaaaaaaa', 'aaaaaaaaaaa', 'Rejeitado', 'não esta bom', '2026-05-31 11:59:24'),
(4, 1, '2/2026', 4, NULL, 'Relacionamentos com empresas de TI.', 0, NULL, 'vvvvvvvvvvvvvvvvvv', 23, 12, 5, 40, 178, 'Visita Técnica', 'vvvvvvvvvvvvvvvvvvvvv', 'vvvvvvvvvvvvvvvvvvvvvv', 'vvvvvvvvvvv', 'vvvvvvvvvvvvvvvvvv', 'Físico,Humano', 'vvvvvvvvvvvvv', 'vvvvvvvvvvvvvvvvv', 'vvvvvvvvvvvvvvvvvvvv', 'Aprovado', 'certo ok', '2026-05-31 12:04:51');

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
(4, 'professor_teste', 'professorteste@gmail.com', '(14) 99837-3207', 'Professor', '2006-11-12', '2014-04-04', 'Indeterminado', 'especialista em TI', 'uploads/assinaturas/4c7bd8b0a262942fec377e0cb3763944.jpg', '1d8bbc4294d306e8ba3ec733b0b06180', 0);

--
-- Índices para tabelas despejadas
--

--
-- Índices de tabela `relatorios_hae`
--
ALTER TABLE `relatorios_hae`
  ADD PRIMARY KEY (`id`),
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
-- AUTO_INCREMENT de tabela `relatorios_hae`
--
ALTER TABLE `relatorios_hae`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT de tabela `solicitacoes_hae`
--
ALTER TABLE `solicitacoes_hae`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de tabela `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

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
