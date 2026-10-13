-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Tempo de geraÃ§Ã£o: 05/10/2026 Ã s 19:43
-- VersÃ£o do servidor: 10.4.32-MariaDB
-- VersÃ£o do PHP: 8.2.12

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
(1, 'AcadÃªmico'),
(2, 'Administrativo'),
(3, 'ExtensÃ£o Ã  comunidade');

-- --------------------------------------------------------

--
-- Estrutura para tabela `configuracoes`
--

CREATE TABLE `configuracoes` (
  `id` int(11) NOT NULL,
  `chave` varchar(50) NOT NULL,
  `valor` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `configuracoes`
--

INSERT INTO `configuracoes` (`id`, `chave`, `valor`) VALUES
(1, 'logo_institucional', 'uploads/logo_fatec_1785789131.jpeg'),
(2, 'ano_eleitoral', '0'),
(3, 'total_hae_disponivel', '250');

-- --------------------------------------------------------

--
-- Estrutura para tabela `fcm_tokens`
--

CREATE TABLE `fcm_tokens` (
  `id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `token` varchar(255) NOT NULL,
  `data_registro` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `fcm_tokens`
--

-- [INSERT INTO fcm_tokens removido por segurança]

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
(18, 12, 6, 2026, 'eiiiitaaaaaaaaaaaaaa hahahahhahahah ahhahahha hahhah hah', 'Publicado', '2026-06-05 22:40:29'),
(19, 13, 6, 2026, 'Dia 06/11/2024 reuniÃ£o do comitÃª organizador do evento a pedido do FÃ¡bio Dias foi reagendado, pois o presidente da ACIG FÃ¡bio Raniel nÃ£o poderÃ¡ participar.\r\n\r\nDia 11/11/2024 reuniÃ£o do comitÃª organizador do evento a pedido do presidente da Acig, FÃ¡bio Raniel foi adiado.\r\n\r\nDia 19/11/2024 reuniÃ£o com Wagner, empresa Fulltime que estÃ¡ apoiando e nos ajudando a trazer a Amazon para o evento. Discutimos possÃ­veis eventos (Deep Racer e Hackathon) a serem inseridos na feira e a questÃ£o da Acig.\r\n\r\nDia 26/11/2024 reuniÃ£o com Wilson e Fernando da Fulltime, onde a Amazon pediu ajustes no site e redes sociais para conseguir a aprovaÃ§Ã£o da participaÃ§Ã£o do evento. SerÃ¡ tambÃ©m agendada reuniÃ£o com o Mauro que faz parte do conselho deliberativo da Acig para novos alinhamentos, na primeira semana de Dezembro.\r\n\r\nDia 28/11/2024 atualizado o site e as redes sociais as melhorias solicitadas pela Amazon para a aprovaÃ§Ã£o de sua participaÃ§Ã£o no evento.\r\n\r\n          Site:  http://fitecgarca.com.br\r\n\r\n          Instagram: https://www.instagram.com/fitec.garca?igsh=MWtvZ21pMG8wajRhbA==', 'Publicado', '2026-06-08 16:56:43'),
(20, 8, 5, 2026, 'aaa', 'Publicado', '2026-07-01 19:41:56'),
(21, 10, 10, 2026, 'gcfhgfghgyj nkhukgyujyfgijk', 'Rascunho', '2026-10-05 16:45:19'),
(22, 10, 9, 2026, 'dxfgdfgdgdg', 'Publicado', '2026-10-05 16:46:35');

-- --------------------------------------------------------

--
-- Estrutura para tabela `solicitacoes_hae`
--

CREATE TABLE `solicitacoes_hae` (
  `id` int(11) NOT NULL,
  `professor_id` int(11) NOT NULL,
  `coordenador_alvo_id` int(11) DEFAULT NULL,
  `coordenador_indicado_id` int(11) DEFAULT NULL,
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
  `recursos_necessarios` set('Financeiro','FÃ­sico','Humano') NOT NULL,
  `detalhamento_recursos` text DEFAULT NULL,
  `cronograma` text NOT NULL,
  `resultados_esperados` text NOT NULL,
  `status_aprovacao` enum('Pendente','Aprovado','Rejeitado','Devolvido') DEFAULT 'Pendente',
  `status_coordenador` varchar(20) DEFAULT 'Pendente',
  `status_diretor` varchar(20) DEFAULT 'Pendente',
  `parecer_diretor` text DEFAULT NULL,
  `data_aprovacao_diretor` date DEFAULT NULL,
  `assinatura_coordenador` varchar(255) DEFAULT NULL,
  `assinatura_diretor` varchar(255) DEFAULT NULL,
  `data_criacao` timestamp NOT NULL DEFAULT current_timestamp(),
  `data_inicio_relatorios` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `solicitacoes_hae`
--

INSERT INTO `solicitacoes_hae` (`id`, `professor_id`, `coordenador_alvo_id`, `coordenador_indicado_id`, `coordenador_id`, `diretor_id`, `parecer_coordenador`, `data_aprovacao_coordenador`, `data_parecer_coordenador`, `semestre`, `quantidade_horas`, `horas_aprovadas`, `titulo_projeto`, `projeto_anterior`, `nome_projeto_anterior`, `objetivos_escola`, `horas_aula`, `horas_atividade`, `horas_especificas`, `total_semanal`, `total_mensal`, `categoria`, `justificativa`, `objetivo`, `metodologia`, `envolvidos`, `recursos_necessarios`, `detalhamento_recursos`, `cronograma`, `resultados_esperados`, `status_aprovacao`, `status_coordenador`, `status_diretor`, `parecer_diretor`, `data_aprovacao_diretor`, `assinatura_coordenador`, `assinatura_diretor`, `data_criacao`, `data_inicio_relatorios`) VALUES
(1, 1, NULL, NULL, 7, 2, 'Ã© ok', '2026-06-03', NULL, '12/04/2011', 3, NULL, 'Relacionamentos com empresas de TI.', 0, NULL, 'Realizar o relacionamento junto Ã s empresas de TI, a fim de tornar a Fatec GarÃ§a mais conhecida e buscar junto a estas parcerias, apoio para a unidade e fortalecimento dos cursos.', 24, 12, 8, 44, 198, 'Administrativo', 'Buscar junto a empresas de tecnologia da informaÃ§Ã£o uma integraÃ§Ã£o (Faculdade x Empresas) proporcionando ganhos de conhecimentos e troca de experiÃªncias para as partes. ', 'Realizar o relacionamento junto Ã s empresas de TI, tornando a Fatec GarÃ§a mais conhecida e buscar junto a estas parcerias, apoio para a unidade e fortalecimento dos cursos.', 'Este relacionamento acontecerÃ¡ atravÃ©s de visitas e reuniÃµes entre as partes, tanto na unidade de ensino como nas empresas.', 'Professor, DireÃ§Ã£o e Empresas de TI.', 'FÃ­sico,Humano', '', 'As atividades irÃ£o transcorrer nos meses que compreende o segundo semestre do ano de 2024.', 'Conseguir agregar benefÃ­cios para a Fatec GarÃ§a e para as empresas de TI.', 'Aprovado', 'Aprovado', 'Aprovado', 'boa', '2026-06-03', NULL, NULL, '2026-05-05 19:45:28', NULL),
(2, 3, NULL, NULL, 7, 2, 'blz', '2026-06-03', NULL, '12/04/2026', 4, NULL, 'Relacionamentos com empresas de TI.', 0, NULL, 'wersdtfygukfdbsvca\\', 24, 12, 8, 44, 198, 'AcadÃªmico', 'AFSGDHTFUKJL,JMGHNFBVZC\\Xz', 'ewgrehtryjuiloÃ§,gjfmhdnsbacfsbfbf fsdgjfyu', 'wshrderty sfjdesrdtfy easrdtfy dzxfcfg', 'wertjy qWAESRYKTU ', 'FÃ­sico,Humano', 'ERTTIYOIYLTDSHJYKUIL HRJYKUFIL, ', 'MARCO A JULHO DE 2025', 'SFDJTFRYTOYUPO AGDSZDHXFCJGKHegty', 'Aprovado', 'Aprovado', 'Aprovado', '', '2026-06-03', NULL, NULL, '2026-05-05 22:13:35', NULL),
(3, 4, NULL, NULL, 7, 2, 'ok certo', '2026-06-03', NULL, '2/2026', 5, NULL, 'Relacionamentos com empresas de TI.', 0, NULL, 'aaaaaaaa', 23, 12, 5, 40, 178, '', 'aaaaaaaaaaaa', 'aaaaaaaa', 'aaaaaaaaaaaaaaaa', 'aaaaaaaaaa', 'Financeiro,FÃ­sico', 'aaaaaaaaaaaa', 'aaaaaaaaaaa', 'aaaaaaaaaaa', 'Aprovado', 'Aprovado', 'Aprovado', 'nÃ£o esta bom', '2026-06-03', NULL, NULL, '2026-05-31 11:59:24', NULL),
(4, 1, NULL, NULL, 7, 2, 'ok', '2026-06-03', NULL, '2/2026', 4, NULL, 'Relacionamentos com empresas de TI.', 0, NULL, 'vvvvvvvvvvvvvvvvvv', 23, 12, 5, 40, 178, 'Visita TÃ©cnica', 'vvvvvvvvvvvvvvvvvvvvv', 'vvvvvvvvvvvvvvvvvvvvvv', 'vvvvvvvvvvv', 'vvvvvvvvvvvvvvvvvv', 'FÃ­sico,Humano', 'vvvvvvvvvvvvv', 'vvvvvvvvvvvvvvvvv', 'vvvvvvvvvvvvvvvvvvvv', 'Aprovado', 'Aprovado', 'Aprovado', 'certo ok', '2026-06-03', NULL, NULL, '2026-05-31 12:04:51', NULL),
(5, 6, NULL, NULL, 7, 2, 'excelente', '2026-06-03', NULL, '1/2026', 7, NULL, 'Tecnologia InovaÃ§Ã£o', 0, NULL, 'teste testando teste teste teste tastando aaaah testando', 24, 12, 8, 44, 198, 'academicas', 'teste testando teste teste teste tastando aaaah testando', 'teste testando teste teste teste tastando aaaah testando', 'teste testando teste teste teste tastando aaaah testando', 'teste testando teste teste teste tastando aaaah testando', 'Financeiro,FÃ­sico', 'teste testando teste teste teste tastando aaaah testando', 'teste testando teste teste teste tastando aaaah testando', 'teste testando teste teste teste tastando aaaah testando', 'Aprovado', 'Aprovado', 'Aprovado', 'teste testando teste teste teste tastando aaaah testandotgdhyfdxmzfksjnhxvb edddddxcc  bbbbb', '2026-06-03', NULL, NULL, '2026-06-02 00:24:59', NULL),
(6, 6, NULL, NULL, 7, 2, 'otimo', '2026-06-03', NULL, '1/2025', 7, NULL, 'Sistemas da informaÃ§Ã£o', 0, NULL, 'aaaa aaa aaaaaaaa aaaaaaaaaaa aaaaa  aaaaaa aaaa aaaaaaa  aa aaa aaaaaa aaaaaaaaaaaa aaaaaaaaaaaaaa', 24, 12, 8, 44, 198, 'Administrativo', 'aaaa aaa aaaaaaaa aaaaaaaaaaa aaaaa  aaaaaa aaaa aaaaaaa  aa aaa aaaaaa aaaaaaaaaaaa aaaaaaaaaaaaaa', 'aaaa aaa aaaaaaaa aaaaaaaaaaa aaaaa  aaaaaa aaaa aaaaaaa  aa aaa aaaaaa aaaaaaaaaaaa aaaaaaaaaaaaaa', 'aaaa aaa aaaaaaaa aaaaaaaaaaa aaaaa  aaaaaa aaaa aaaaaaa  aa aaa aaaaaa aaaaaaaaaaaa aaaaaaaaaaaaaa', 'aaaa aaa aaaaaaaa aaaaaaaaaaa aaaaa  aaaaaa aaaa aaaaaaa  aa aaa aaaaaa aaaaaaaaaaaa aaaaaaaaaaaaaa', 'FÃ­sico,Humano', 'aaaa aaa aaaaaaaa aaaaaaaaaaa aaaaa  aaaaaa aaaa aaaaaaa  aa aaa aaaaaa aaaaaaaaaaaa aaaaaaaaaaaaaa', 'aaaa aaa aaaaaaaa aaaaaaaaaaa aaaaa  aaaaaa aaaa aaaaaaa  aa aaa aaaaaa aaaaaaaaaaaa aaaaaaaaaaaaaa', 'aaaa aaa aaaaaaaa aaaaaaaaaaa aaaaa  aaaaaa aaaa aaaaaaa  aa aaa aaaaaa aaaaaaaaaaaa aaaaaaaaaaaaaa', 'Aprovado', 'Aprovado', 'Aprovado', 'otimo correto', '2026-06-03', NULL, NULL, '2026-06-02 23:26:03', NULL),
(7, 4, NULL, NULL, 7, 2, 'aeeeeee aeeeee', '2026-06-03', '2026-06-03', '1/2026', 7, NULL, 'Projeto testeee', 0, NULL, 'eeeeeeeeeeeeeeeeeeeeee e e eeee eeee e eeeeeeeee', 24, 12, 8, 44, 198, 'AcadÃªmico', 'eeeeeeeeeeeeeeeeeeeeee e e eeee eeee e eeeeeeeee', 'eeeeeeeeeeeeeeeeeeeeee e e eeee eeee e eeeeeeeee', 'eeeeeeeeeeeeeeeeeeeeee e e eeee eeee e eeeeeeeee', 'eeeeeeeeeeeeeeeeeeeeee e e eeee eeee e eeeeeeeee', 'Financeiro,FÃ­sico', 'eeeeeeeeeeeeeeeeeeeeee e e eeee eeee e eeeeeeeee', 'eeeeeeeeeeeeeeeeeeeeee e e eeee eeee e eeeeeeeee', 'eeeeeeeeeeeeeeeeeeeeee e e eeee eeee e eeeeeeeee', 'Aprovado', 'Aprovado', 'Aprovado', 'ok tudo certoo', '2026-06-03', NULL, NULL, '2026-06-03 00:19:26', NULL),
(8, 3, NULL, NULL, 7, 2, 'tudo certinho ok', '2026-06-03', NULL, '2/2026', 8, NULL, 'projeto ADS', 0, NULL, 'o oooooooo ooooo oo  ooooo  oo oooo o poooo', 24, 12, 8, 44, 198, 'AcadÃªmico', 'o oooooooo ooooo oo  ooooo  oo oooo o poooo', 'o oooooooo ooooo oo  ooooo  oo oooo o poooo', 'o oooooooo ooooo oo  ooooo  oo oooo o poooo', 'o oooooooo ooooo oo  ooooo  oo oooo o poooo', 'Financeiro,FÃ­sico', 'o oooooooo ooooo oo  ooooo  oo oooo o poooo', 'o oooooooo ooooo oo  ooooo  oo oooo o poooo', 'o oooooooo ooooo oo  ooooo  oo oooo o poooo', 'Aprovado', 'Aprovado', 'Aprovado', 'opa tudo bem', '2026-06-03', NULL, NULL, '2026-06-03 00:37:50', NULL),
(9, 3, NULL, NULL, 7, 2, 'otimo', '2026-06-03', NULL, '2/2026', 8, NULL, 'Gestao Empresarial', 0, NULL, 'uhulllll uhuulll uhluuu hulllll uhullll uuu hulllll uhullll uuu hulllll uhullll', 24, 12, 8, 44, 198, 'Administrativo', 'uhulllll uhuulll uhluuu hulllll uhullll uuu hulllll uhullll uuu hulllll uhullll', 'uhulllll uhuulll uhluuu hulllll uhullll uuu hulllll uhullll uuu hulllll uhullll', 'uhulllll uhuulll uhluuu hulllll uhullll uuu hulllll uhullll uuu hulllll uhullll', 'uhulllll uhuulll uhluuu hulllll uhullll uuu hulllll uhullll uuu hulllll uhullll', '', 'uhulllll uhuulll uhluuu hulllll uhullll uuu hulllll uhullll uuu hulllll uhullll', 'uhulllll uhuulll uhluuu hulllll uhullll uuu hulllll uhullll uuu hulllll uhullll', 'uhulllll uhuulll uhluuu hulllll uhullll uuu hulllll uhullll uuu hulllll uhullll', 'Aprovado', 'Aprovado', 'Aprovado', 'beeeeeeeeeeeeeeellllllleeeeeeeeeeeeeeezzzzzzaaaaaaaaaaa', '2026-06-03', NULL, NULL, '2026-06-03 00:57:24', NULL),
(10, 8, NULL, NULL, 7, 2, 'otimo esta tudo certo com seu documeto HAE. Aprovado!!', '2026-06-05', NULL, '1/2026', 8, NULL, 'Testando projeto eduarda', 0, NULL, 'aaaaaaaaaa aaaaaaaaaaa aaaaa aaaaa aa a aaaa aaaa  aaaaaa  a a', 24, 12, 8, 44, 198, 'AcadÃªmico', 'aaaa aaa aaaaaa aaa aaaaaa aaaaaaaaaa aaaaaaaaaaa aaaaa aaaaa aa a aaaa aaaa  aaaaaa  a a', 'aaaaaaaaaa aaaaaaaaaaa aaaaa aaaaa aa a aaaa aaaa  aaaaaa  a a', 'aaaaaaaaaa aaaaaaaaaaa aaaaa aaaaa aa a aaaa aaaa  aaaaaa  a a', 'aaaaaaaaaa aaaaaaaaaaa aaaaa aaaaa aa a aaaa aaaa  aaaaaa  a a', 'Financeiro,Humano', 'aaaaaaaaaa aaaaaaaaaaa aaaaa aaaaa aa a aaaa aaaa  aaaaaa  a a', 'aaaaaaaaaa aaaaaaaaaaa aaaaa aaaaa aa a aaaa aaaa  aaaaaa  a a', 'aaaaaaaaaa aaaaaaaaaaa aaaaa aaaaa aa a aaaa aaaa  aaaaaa  a a', 'Aprovado', 'Aprovado', 'Aprovado', 'otimo esta tudo certo com seu documeto HAE. Aprovado!!!!!', '2026-06-05', NULL, NULL, '2026-06-03 22:50:35', NULL),
(12, 9, NULL, NULL, 7, 2, 'otimo, perfeito', '2026-06-06', NULL, '2/2026', 8, NULL, 'Projeto do professor JoÃ£o', 0, NULL, 'eitaaaaaaaaaaaaaaaaaaaa eitaaaaaaaaaaa eeeeeeeeeeeeiiiiiiiiiiiiitttttttttttttaaaaaaaaaaaaaa', 24, 12, 8, 44, 198, 'Administrativo', 'eitaaaaaaaaaaaaaaaaaaaa eitaaaaaaaaaaa eeeeeeeeeeeeiiiiiiiiiiiiitttttttttttttaaaaaaaaaaaaaa', 'eitaaaaaaaaaaaaaaaaaaaa eitaaaaaaaaaaa eeeeeeeeeeeeiiiiiiiiiiiiitttttttttttttaaaaaaaaaaaaaa', 'eitaaaaaaaaaaaaaaaaaaaa eitaaaaaaaaaaa eeeeeeeeeeeeiiiiiiiiiiiiitttttttttttttaaaaaaaaaaaaaa', 'eitaaaaaaaaaaaaaaaaaaaa eitaaaaaaaaaaa eeeeeeeeeeeeiiiiiiiiiiiiitttttttttttttaaaaaaaaaaaaaa', 'Financeiro', 'eitaaaaaaaaaaaaaaaaaaaa eitaaaaaaaaaaa eeeeeeeeeeeeiiiiiiiiiiiiitttttttttttttaaaaaaaaaaaaaa', 'eitaaaaaaaaaaaaaaaaaaaa eitaaaaaaaaaaa eeeeeeeeeeeeiiiiiiiiiiiiitttttttttttttaaaaaaaaaaaaaa', 'eitaaaaaaaaaaaaaaaaaaaa eitaaaaaaaaaaa eeeeeeeeeeeeiiiiiiiiiiiiitttttttttttttaaaaaaaaaaaaaa', 'Aprovado', 'Aprovado', 'Aprovado', 'Otimo, perfeito', '2026-06-06', NULL, NULL, '2026-06-05 22:31:27', NULL),
(13, 11, NULL, NULL, 7, 2, 'aprovado com louvor', '2026-06-08', NULL, '2/2026', 8, NULL, 'Relacionamentos com empresas de TI.', 0, NULL, 'Realizar o relacionamento junto Ã s empresas de TI, tornando a Fatec GarÃ§a mais conhecida e buscar junto a estas parcerias, apoio para a unidade e fortalecimento dos cursos.', 24, 12, 8, 44, 198, 'Administrativo', 'Buscar junto a empresas de tecnologia da informaÃ§Ã£o uma integraÃ§Ã£o (Faculdade x Empresas) proporcionando ganhos de conhecimentos e troca de experiÃªncias para as partes. ', 'Realizar o relacionamento junto Ã s empresas de TI, tornando a Fatec GarÃ§a mais conhecida e buscar junto a estas parcerias, apoio para a unidade e fortalecimento dos cursos.', 'Este relacionamento acontecerÃ¡ atravÃ©s de visitas e reuniÃµes entre as partes, tanto na unidade de ensino como nas empresas.', 'Professor, DireÃ§Ã£o e Empresas de TI.', 'FÃ­sico,Humano', '- FÃ­sico.....: Sala dos professores ou residÃªncia do professor\r\n- Humano: Acordado com as partes interessadas no projeto.\r\n', 'As atividades irÃ£o transcorrer nos meses que compreende o segundo semestre do ano de 2026.', 'Conseguir agregar benefÃ­cios para a Fatec GarÃ§a e para as empresas de TI.', 'Aprovado', 'Aprovado', 'Aprovado', 'ok, aprovado!', '2026-06-08', NULL, NULL, '2026-06-08 16:39:56', NULL),
(14, 11, 13, NULL, NULL, NULL, NULL, NULL, NULL, '1/2026', 8, NULL, 'testando', 0, NULL, 'teste teste teste teste teste teste teste teste teste teste teste teste teste teste teste', 24, 12, 8, 44, 198, 'AcadÃªmico', 'teste teste teste teste teste teste teste teste teste teste teste teste teste teste teste', 'teste teste teste teste teste teste teste teste teste teste teste teste teste teste teste', 'teste teste teste teste teste teste teste teste teste teste teste teste teste teste teste', 'teste teste teste teste teste teste teste teste teste teste teste teste teste teste teste', 'FÃ­sico', 'teste teste teste teste teste teste teste teste teste teste teste teste teste teste teste', 'teste teste teste teste teste teste teste teste teste teste teste teste teste teste teste', 'teste teste teste teste teste teste teste teste teste teste teste teste teste teste teste', 'Pendente', 'Pendente', 'Pendente', NULL, NULL, NULL, NULL, '2026-06-15 19:13:27', NULL),
(15, 9, 7, NULL, 7, 2, 'bbbbbbbbbbb', '2026-07-01', NULL, '1/2026', 8, NULL, 'realizando outro teste', 0, NULL, 'teste teste teste teste teste teste teste teste teste teste teste teste teste teste teste', 24, 12, 8, 44, 198, 'AcadÃªmico', 'teste teste teste teste teste teste teste teste teste teste teste teste teste teste teste', 'teste teste teste teste teste teste teste teste teste teste teste teste teste teste teste', 'teste teste teste teste teste teste teste teste teste teste teste teste teste teste teste', 'teste teste teste teste teste teste teste teste teste teste teste teste teste teste teste', 'Financeiro', 'teste teste teste teste teste teste teste teste teste teste teste teste teste teste teste', 'teste teste teste teste teste teste teste teste teste teste teste teste teste teste teste', 'teste teste teste teste teste teste teste teste teste teste teste teste teste teste teste', 'Aprovado', 'Aprovado', 'Aprovado', 'aaaaaaaa', '2026-07-01', NULL, NULL, '2026-06-15 19:32:04', NULL),
(16, 14, 7, NULL, 7, 2, 'otimo', '2026-07-01', NULL, '2/2026', 8, NULL, 'realizando teste de hojee', 0, NULL, 'teste de  teste de hoje teste de hojeteste de hojeteste de hojeteste de hojeteste de hojeteste de hojeteste de hojeteste de hojeteste de hojeteste de hojeteste de hojeteste de hojeteste de hoje', 24, 12, 8, 44, 198, 'AcadÃªmico', 'teste de hojeteste de hojeteste de hojeteste de hoje', 'teste de hojeteste de hojeteste de hojeteste de hoje', 'teste de hojeteste de hojeteste de hojeteste de hojeteste de hoje', 'teste de hojeteste de hojeteste de hojeteste de hojeteste de hojeteste de hojeteste de hoje', 'Financeiro,FÃ­sico', 'teste de hojeteste de hojeteste de hojeteste de hojeteste de hoje', 'teste de hojeteste de hojeteste de hojeteste de hojeteste de hoje', 'teste de hojeteste de hojeteste de hojeteste de hojeteste de hojeteste de hoje', 'Aprovado', 'Aprovado', 'Aprovado', 'esta de acordo', '2026-07-01', NULL, NULL, '2026-07-01 17:27:14', NULL),
(17, 8, 7, NULL, 7, 2, NULL, NULL, NULL, '2/2026', 8, NULL, 'realizando outro teste', 0, NULL, 'ssssssssss', 24, 12, 8, 44, 198, 'Administrativo', 'ssssssssssss', 'ssssssssss', 'sssssssss', 'ssssssssss', 'Financeiro,FÃ­sico', 'sssssssssss', 'ssssssssssss', 'ssssssssssssa', 'Pendente', 'Pendente', 'Pendente', NULL, NULL, NULL, NULL, '2026-07-01 19:56:14', NULL),
(18, 29, 7, NULL, 7, 2, 'projeto aprovado', '2026-07-29', NULL, '2/2026', 8, NULL, 'realizando teste de hojee 29.07', 0, NULL, 'testando 29.07 testando 29.07testando 29.07testando 29.07testando 29.07testando 29.07testando 29.07testando 29.07testando 29.07', 24, 12, 8, 44, 198, 'AcadÃªmico', 'testando 29.07testando 29.07testando 29.07', 'testando 29.07testando 29.07testando 29.07', 'testando 29.07testando 29.07testando 29.07', 'testando 29.07testando 29.07testando 29.07', 'FÃ­sico,Humano', 'testando 29.07testando 29.07testando 29.07', 'testando 29.07testando 29.07testando 29.07testando 29.07', 'testando 29.07testando 29.07testando 29.07testando 29.07testando 29.07', 'Aprovado', 'Aprovado', 'Aprovado', 'esta de acordo, projeto aprovado !!!', '2026-07-29', NULL, NULL, '2026-07-29 19:20:38', NULL),
(19, 29, 7, NULL, 7, 2, 'perfeito', '2026-07-29', NULL, '1/2026', 8, NULL, 'realizando teste de hojee 29.07 de rejeiÃ§Ã£o', 0, NULL, 'testando rejeiÃ§Ã£o testando rejeiÃ§Ã£o testando rejeiÃ§Ã£o testando rejeiÃ§Ã£o testando rejeiÃ§Ã£o testando rejeiÃ§Ã£o testando rejeiÃ§Ã£o ', 24, 12, 8, 44, 198, 'Administrativo', 'testando rejeiÃ§Ã£o testando rejeiÃ§Ã£o testando rejeiÃ§Ã£o ', 'testando rejeiÃ§Ã£o testando rejeiÃ§Ã£o testando rejeiÃ§Ã£o ', 'testando rejeiÃ§Ã£o testando rejeiÃ§Ã£o testando rejeiÃ§Ã£o ', 'testando rejeiÃ§Ã£o testando rejeiÃ§Ã£o testando rejeiÃ§Ã£o testando rejeiÃ§Ã£o testando rejeiÃ§Ã£o ', 'Financeiro,FÃ­sico', 'testando rejeiÃ§Ã£o testando rejeiÃ§Ã£o testando rejeiÃ§Ã£o testando rejeiÃ§Ã£o ', 'testando rejeiÃ§Ã£o testando rejeiÃ§Ã£o testando rejeiÃ§Ã£o ', 'testando rejeiÃ§Ã£o testando rejeiÃ§Ã£o testando rejeiÃ§Ã£o ', 'Aprovado', 'Aprovado', 'Aprovado', 'otimo aprovado !!!', '2026-07-29', NULL, NULL, '2026-07-29 19:32:06', NULL),
(20, 29, 7, NULL, 7, NULL, 'ruim', NULL, NULL, '1/2026', 8, NULL, 'realizando outro teste rejeiÃ§Ã£o!!!!', 0, NULL, 'testando rejeiÃ§Ã£o testando rejeiÃ§Ã£o testando rejeiÃ§Ã£o testando rejeiÃ§Ã£o testando rejeiÃ§Ã£o testando rejeiÃ§Ã£o testando rejeiÃ§Ã£o ', 24, 12, 8, 44, 198, 'ExtensÃ£o Ã  comunidade', 'testando rejeiÃ§Ã£o testando rejeiÃ§Ã£o testando rejeiÃ§Ã£o testando rejeiÃ§Ã£o testando rejeiÃ§Ã£o ', 'testando rejeiÃ§Ã£o testando rejeiÃ§Ã£o testando rejeiÃ§Ã£o testando rejeiÃ§Ã£o ', 'testando rejeiÃ§Ã£o testando rejeiÃ§Ã£o testando rejeiÃ§Ã£o testando rejeiÃ§Ã£o testando rejeiÃ§Ã£o ', 'testando rejeiÃ§Ã£o testando rejeiÃ§Ã£o testando rejeiÃ§Ã£o testando rejeiÃ§Ã£o testando rejeiÃ§Ã£o ', 'Financeiro,FÃ­sico', 'testando rejeiÃ§Ã£o testando rejeiÃ§Ã£o testando rejeiÃ§Ã£o testando rejeiÃ§Ã£o ', 'testando rejeiÃ§Ã£o testando rejeiÃ§Ã£o ', 'testando rejeiÃ§Ã£o testando rejeiÃ§Ã£o testando rejeiÃ§Ã£o ', 'Rejeitado', 'Rejeitado', 'Pendente', NULL, NULL, NULL, NULL, '2026-07-29 19:35:44', NULL),
(21, 8, 7, NULL, NULL, 2, NULL, NULL, NULL, '2/2026', 4, NULL, 'realizando teste de hoje 0308', 0, NULL, 'aaaaaaaa', 10, 5, 4, 19, 86, 'AcadÃªmico', 'aaaaaaaaaaaaa', 'aaaaaaaaaa', 'aaaaaaaaaa', 'aaaaaaaaaa', 'Humano', 'aaaaaaaaaaaaaaaaa', 'aaaaaaaaaaaaaaa', 'aaaaaaaaaaaaaaaaa', 'Pendente', 'Pendente', 'Aprovado', 's', '2026-08-03', NULL, NULL, '2026-08-03 17:33:12', NULL),
(23, 1, 7, NULL, 7, NULL, NULL, NULL, NULL, '2/2026', 8, NULL, 'eeeeeeeeeee - v1.3', 0, NULL, 'eeeeeeeeeee', 22, 11, 8, 41, 185, 'AcadÃªmico', 'eeeeeeeeeee', 'eeeeeeee', 'eeeeeeeeeeee', 'eeeeeeeeeee', 'FÃ­sico,Humano', 'eeeeeeeeeee', 'eeeeeeeeeeeeeeeeee', 'eeeeeeeeeeeeeeeeee', 'Pendente', 'Pendente', 'Pendente', NULL, NULL, NULL, NULL, '2026-08-24 19:44:59', NULL),
(25, 1, 7, NULL, 7, 2, 'bom', '2026-08-24', NULL, '2/2026', 8, NULL, 'mmmmmmmmmmmmm - v1.0', 0, NULL, 'mmmmmmmmmmmmmm', 22, 11, 8, 41, 185, 'Administrativo', 'mmmmmmmmmmmmmmmmm', 'mmmmmmmmmmmmmmm', 'mmmmmmmmmm', 'mmmmmmmmmm', 'FÃ­sico,Humano', 'mmmmmmmmmmm', 'mmmmmmmmmmm', 'mmmmmmmmmm', 'Aprovado', 'Aprovado', 'Aprovado', 'otimo', '2026-08-24', NULL, NULL, '2026-08-24 20:18:55', NULL),
(27, 1, 7, NULL, 7, NULL, 'devolvendo outro af\n\n???? PRAZO PARA CORREÃ‡ÃƒO:\nO projeto deverÃ¡ ser ajustado e submetido novamente no portal atÃ© o dia 28/08/2026 Ã s 12:00.', NULL, NULL, '2/2026', 8, NULL, 'bbbbbbbbbbbbbbbbbbbbbb - v1.0', 0, NULL, 'bbbbbbbbbbbbb', 22, 11, 6, 39, 176, 'Administrativo', 'bbbbbbbbb', 'bbbbbbbbb', 'bbbbbbbbbbbbb', 'bbbbbbb', 'FÃ­sico,Humano', 'bbbbbbbbb', 'bbbbbbbbbbbbbb', 'bbbbbbbbb', 'Devolvido', 'Devolvido', 'Pendente', NULL, NULL, NULL, NULL, '2026-08-24 20:42:57', NULL),
(29, 1, 7, NULL, 7, NULL, 'mds testando pega logo\n\n???? PRAZO PARA CORREÃ‡ÃƒO:\nO projeto deverÃ¡ ser ajustado e submetido novamente no portal atÃ© o dia 25/08/2026 Ã s 08:30.', NULL, NULL, '2/2026', 8, NULL, 'vvvvvvv - v1.1', 0, NULL, 'vvvvvvvvv', 22, 11, 8, 41, 185, 'AcadÃªmico', 'vvvvvvvvvvvvv', 'vvvvvvvv', 'vvvvvv', 'vvvvvvvvv', 'FÃ­sico,Humano', 'vvvvvvvvvvvv', 'vvvvvvvvvvvv', 'vvvvvvvvvv', 'Devolvido', 'Devolvido', 'Pendente', NULL, NULL, NULL, NULL, '2026-08-24 23:39:41', NULL),
(30, 1, 7, NULL, 7, 29, 'agora vai teste coord.', '2026-08-25', NULL, '2/2026', 8, NULL, 'cccccccccccccccccee - v1.4', 0, NULL, 'cccccccc', 22, 11, 8, 41, 185, 'Administrativo', 'ccccccccccc', 'ccccccccccc', 'ccccccccccccc', 'cccccccc', 'FÃ­sico,Humano', 'ccccccccc', 'ccccccccccc', 'ccccccccccac', 'Pendente', 'Aprovado', 'Pendente', NULL, NULL, NULL, NULL, '2026-08-24 23:41:08', NULL),
(31, 1, 7, NULL, 7, 29, 'ok', '2026-08-27', NULL, '2/2026', 8, NULL, 'iiiiiiiiiiii - v1.5', 0, NULL, 'iiiiiiiiii', 22, 11, 8, 41, 185, 'AcadÃªmico', 'iiiiiiiiii', 'iiiiiiiiiiiiiiiiiiiiiiii', 'iiiiiiiiiiiiii', 'iiiiiiiiiiiiiiiiiiiiiiiiiiii', 'FÃ­sico,Humano', 'iiiiiiiiiiiiiiiiiiiiiiiiii', 'iiiiiiiiiiii', 'iiiiiiiiiiiiiiiii', 'Rejeitado', 'Aprovado', 'Rejeitado', 'eita', NULL, NULL, NULL, '2026-08-25 00:38:48', NULL);

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

-- [INSERT INTO usuarios removido por segurança]

--
-- Ãndices para tabelas despejadas
--

--
-- Ãndices de tabela `categorias_projeto`
--
ALTER TABLE `categorias_projeto`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nome` (`nome`);

--
-- Ãndices de tabela `configuracoes`
--
ALTER TABLE `configuracoes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `chave` (`chave`);

--
-- Ãndices de tabela `fcm_tokens`
--
ALTER TABLE `fcm_tokens`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `token` (`token`),
  ADD KEY `usuario_id` (`usuario_id`);

--
-- Ãndices de tabela `relatorios_hae`
--
ALTER TABLE `relatorios_hae`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_projeto_mes_ano` (`solicitacao_id`,`mes_referencia`,`ano_referencia`),
  ADD KEY `solicitacao_id` (`solicitacao_id`);

--
-- Ãndices de tabela `solicitacoes_hae`
--
ALTER TABLE `solicitacoes_hae`
  ADD PRIMARY KEY (`id`),
  ADD KEY `professor_id` (`professor_id`);

--
-- Ãndices de tabela `usuarios`
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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT de tabela `configuracoes`
--
ALTER TABLE `configuracoes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de tabela `fcm_tokens`
--
ALTER TABLE `fcm_tokens`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=48;

--
-- AUTO_INCREMENT de tabela `relatorios_hae`
--
ALTER TABLE `relatorios_hae`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT de tabela `solicitacoes_hae`
--
ALTER TABLE `solicitacoes_hae`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=32;

--
-- AUTO_INCREMENT de tabela `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=30;

--
-- RestriÃ§Ãµes para tabelas despejadas
--

--
-- RestriÃ§Ãµes para tabelas `fcm_tokens`
--
ALTER TABLE `fcm_tokens`
  ADD CONSTRAINT `fcm_tokens_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE;

--
-- RestriÃ§Ãµes para tabelas `relatorios_hae`
--
ALTER TABLE `relatorios_hae`
  ADD CONSTRAINT `relatorios_hae_ibfk_1` FOREIGN KEY (`solicitacao_id`) REFERENCES `solicitacoes_hae` (`id`);

--
-- RestriÃ§Ãµes para tabelas `solicitacoes_hae`
--
ALTER TABLE `solicitacoes_hae`
  ADD CONSTRAINT `solicitacoes_hae_ibfk_1` FOREIGN KEY (`professor_id`) REFERENCES `usuarios` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
