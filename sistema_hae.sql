-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Tempo de geração: 10/08/2026 às 01:34
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
(19, 13, 6, 2026, 'Dia 06/11/2024 reunião do comitê organizador do evento a pedido do Fábio Dias foi reagendado, pois o presidente da ACIG Fábio Raniel não poderá participar.\r\n\r\nDia 11/11/2024 reunião do comitê organizador do evento a pedido do presidente da Acig, Fábio Raniel foi adiado.\r\n\r\nDia 19/11/2024 reunião com Wagner, empresa Fulltime que está apoiando e nos ajudando a trazer a Amazon para o evento. Discutimos possíveis eventos (Deep Racer e Hackathon) a serem inseridos na feira e a questão da Acig.\r\n\r\nDia 26/11/2024 reunião com Wilson e Fernando da Fulltime, onde a Amazon pediu ajustes no site e redes sociais para conseguir a aprovação da participação do evento. Será também agendada reunião com o Mauro que faz parte do conselho deliberativo da Acig para novos alinhamentos, na primeira semana de Dezembro.\r\n\r\nDia 28/11/2024 atualizado o site e as redes sociais as melhorias solicitadas pela Amazon para a aprovação de sua participação no evento.\r\n\r\n          Site:  http://fitecgarca.com.br\r\n\r\n          Instagram: https://www.instagram.com/fitec.garca?igsh=MWtvZ21pMG8wajRhbA==', 'Publicado', '2026-06-08 16:56:43'),
(20, 8, 5, 2026, 'aaa', 'Publicado', '2026-07-01 19:41:56');

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

INSERT INTO `solicitacoes_hae` (`id`, `professor_id`, `coordenador_alvo_id`, `coordenador_indicado_id`, `coordenador_id`, `diretor_id`, `parecer_coordenador`, `data_aprovacao_coordenador`, `data_parecer_coordenador`, `semestre`, `quantidade_horas`, `horas_aprovadas`, `titulo_projeto`, `projeto_anterior`, `nome_projeto_anterior`, `objetivos_escola`, `horas_aula`, `horas_atividade`, `horas_especificas`, `total_semanal`, `total_mensal`, `categoria`, `justificativa`, `objetivo`, `metodologia`, `envolvidos`, `recursos_necessarios`, `detalhamento_recursos`, `cronograma`, `resultados_esperados`, `status_aprovacao`, `status_coordenador`, `status_diretor`, `parecer_diretor`, `data_aprovacao_diretor`, `assinatura_coordenador`, `assinatura_diretor`, `data_criacao`) VALUES
(1, 1, NULL, NULL, 7, 2, 'é ok', '2026-06-03', NULL, '12/04/2011', 3, NULL, 'Relacionamentos com empresas de TI.', 0, NULL, 'Realizar o relacionamento junto às empresas de TI, a fim de tornar a Fatec Garça mais conhecida e buscar junto a estas parcerias, apoio para a unidade e fortalecimento dos cursos.', 24, 12, 8, 44, 198, 'Administrativo', 'Buscar junto a empresas de tecnologia da informação uma integração (Faculdade x Empresas) proporcionando ganhos de conhecimentos e troca de experiências para as partes. ', 'Realizar o relacionamento junto às empresas de TI, tornando a Fatec Garça mais conhecida e buscar junto a estas parcerias, apoio para a unidade e fortalecimento dos cursos.', 'Este relacionamento acontecerá através de visitas e reuniões entre as partes, tanto na unidade de ensino como nas empresas.', 'Professor, Direção e Empresas de TI.', 'Físico,Humano', '', 'As atividades irão transcorrer nos meses que compreende o segundo semestre do ano de 2024.', 'Conseguir agregar benefícios para a Fatec Garça e para as empresas de TI.', 'Aprovado', 'Aprovado', 'Aprovado', 'boa', '2026-06-03', NULL, NULL, '2026-05-05 19:45:28'),
(2, 3, NULL, NULL, 7, 2, 'blz', '2026-06-03', NULL, '12/04/2026', 4, NULL, 'Relacionamentos com empresas de TI.', 0, NULL, 'wersdtfygukfdbsvca\\', 24, 12, 8, 44, 198, 'Acadêmico', 'AFSGDHTFUKJL,JMGHNFBVZC\\Xz', 'ewgrehtryjuiloç,gjfmhdnsbacfsbfbf fsdgjfyu', 'wshrderty sfjdesrdtfy easrdtfy dzxfcfg', 'wertjy qWAESRYKTU ', 'Físico,Humano', 'ERTTIYOIYLTDSHJYKUIL HRJYKUFIL, ', 'MARCO A JULHO DE 2025', 'SFDJTFRYTOYUPO AGDSZDHXFCJGKHegty', 'Aprovado', 'Aprovado', 'Aprovado', '', '2026-06-03', NULL, NULL, '2026-05-05 22:13:35'),
(3, 4, NULL, NULL, 7, 2, 'ok certo', '2026-06-03', NULL, '2/2026', 5, NULL, 'Relacionamentos com empresas de TI.', 0, NULL, 'aaaaaaaa', 23, 12, 5, 40, 178, '', 'aaaaaaaaaaaa', 'aaaaaaaa', 'aaaaaaaaaaaaaaaa', 'aaaaaaaaaa', 'Financeiro,Físico', 'aaaaaaaaaaaa', 'aaaaaaaaaaa', 'aaaaaaaaaaa', 'Aprovado', 'Aprovado', 'Aprovado', 'não esta bom', '2026-06-03', NULL, NULL, '2026-05-31 11:59:24'),
(4, 1, NULL, NULL, 7, 2, 'ok', '2026-06-03', NULL, '2/2026', 4, NULL, 'Relacionamentos com empresas de TI.', 0, NULL, 'vvvvvvvvvvvvvvvvvv', 23, 12, 5, 40, 178, 'Visita Técnica', 'vvvvvvvvvvvvvvvvvvvvv', 'vvvvvvvvvvvvvvvvvvvvvv', 'vvvvvvvvvvv', 'vvvvvvvvvvvvvvvvvv', 'Físico,Humano', 'vvvvvvvvvvvvv', 'vvvvvvvvvvvvvvvvv', 'vvvvvvvvvvvvvvvvvvvv', 'Aprovado', 'Aprovado', 'Aprovado', 'certo ok', '2026-06-03', NULL, NULL, '2026-05-31 12:04:51'),
(5, 6, NULL, NULL, 7, 2, 'excelente', '2026-06-03', NULL, '1/2026', 7, NULL, 'Tecnologia Inovação', 0, NULL, 'teste testando teste teste teste tastando aaaah testando', 24, 12, 8, 44, 198, 'academicas', 'teste testando teste teste teste tastando aaaah testando', 'teste testando teste teste teste tastando aaaah testando', 'teste testando teste teste teste tastando aaaah testando', 'teste testando teste teste teste tastando aaaah testando', 'Financeiro,Físico', 'teste testando teste teste teste tastando aaaah testando', 'teste testando teste teste teste tastando aaaah testando', 'teste testando teste teste teste tastando aaaah testando', 'Aprovado', 'Aprovado', 'Aprovado', 'teste testando teste teste teste tastando aaaah testandotgdhyfdxmzfksjnhxvb edddddxcc  bbbbb', '2026-06-03', NULL, NULL, '2026-06-02 00:24:59'),
(6, 6, NULL, NULL, 7, 2, 'otimo', '2026-06-03', NULL, '1/2025', 7, NULL, 'Sistemas da informação', 0, NULL, 'aaaa aaa aaaaaaaa aaaaaaaaaaa aaaaa  aaaaaa aaaa aaaaaaa  aa aaa aaaaaa aaaaaaaaaaaa aaaaaaaaaaaaaa', 24, 12, 8, 44, 198, 'Administrativo', 'aaaa aaa aaaaaaaa aaaaaaaaaaa aaaaa  aaaaaa aaaa aaaaaaa  aa aaa aaaaaa aaaaaaaaaaaa aaaaaaaaaaaaaa', 'aaaa aaa aaaaaaaa aaaaaaaaaaa aaaaa  aaaaaa aaaa aaaaaaa  aa aaa aaaaaa aaaaaaaaaaaa aaaaaaaaaaaaaa', 'aaaa aaa aaaaaaaa aaaaaaaaaaa aaaaa  aaaaaa aaaa aaaaaaa  aa aaa aaaaaa aaaaaaaaaaaa aaaaaaaaaaaaaa', 'aaaa aaa aaaaaaaa aaaaaaaaaaa aaaaa  aaaaaa aaaa aaaaaaa  aa aaa aaaaaa aaaaaaaaaaaa aaaaaaaaaaaaaa', 'Físico,Humano', 'aaaa aaa aaaaaaaa aaaaaaaaaaa aaaaa  aaaaaa aaaa aaaaaaa  aa aaa aaaaaa aaaaaaaaaaaa aaaaaaaaaaaaaa', 'aaaa aaa aaaaaaaa aaaaaaaaaaa aaaaa  aaaaaa aaaa aaaaaaa  aa aaa aaaaaa aaaaaaaaaaaa aaaaaaaaaaaaaa', 'aaaa aaa aaaaaaaa aaaaaaaaaaa aaaaa  aaaaaa aaaa aaaaaaa  aa aaa aaaaaa aaaaaaaaaaaa aaaaaaaaaaaaaa', 'Aprovado', 'Aprovado', 'Aprovado', 'otimo correto', '2026-06-03', NULL, NULL, '2026-06-02 23:26:03'),
(7, 4, NULL, NULL, 7, 2, 'aeeeeee aeeeee', '2026-06-03', '2026-06-03', '1/2026', 7, NULL, 'Projeto testeee', 0, NULL, 'eeeeeeeeeeeeeeeeeeeeee e e eeee eeee e eeeeeeeee', 24, 12, 8, 44, 198, 'Acadêmico', 'eeeeeeeeeeeeeeeeeeeeee e e eeee eeee e eeeeeeeee', 'eeeeeeeeeeeeeeeeeeeeee e e eeee eeee e eeeeeeeee', 'eeeeeeeeeeeeeeeeeeeeee e e eeee eeee e eeeeeeeee', 'eeeeeeeeeeeeeeeeeeeeee e e eeee eeee e eeeeeeeee', 'Financeiro,Físico', 'eeeeeeeeeeeeeeeeeeeeee e e eeee eeee e eeeeeeeee', 'eeeeeeeeeeeeeeeeeeeeee e e eeee eeee e eeeeeeeee', 'eeeeeeeeeeeeeeeeeeeeee e e eeee eeee e eeeeeeeee', 'Aprovado', 'Aprovado', 'Aprovado', 'ok tudo certoo', '2026-06-03', NULL, NULL, '2026-06-03 00:19:26'),
(8, 3, NULL, NULL, 7, 2, 'tudo certinho ok', '2026-06-03', NULL, '2/2026', 8, NULL, 'projeto ADS', 0, NULL, 'o oooooooo ooooo oo  ooooo  oo oooo o poooo', 24, 12, 8, 44, 198, 'Acadêmico', 'o oooooooo ooooo oo  ooooo  oo oooo o poooo', 'o oooooooo ooooo oo  ooooo  oo oooo o poooo', 'o oooooooo ooooo oo  ooooo  oo oooo o poooo', 'o oooooooo ooooo oo  ooooo  oo oooo o poooo', 'Financeiro,Físico', 'o oooooooo ooooo oo  ooooo  oo oooo o poooo', 'o oooooooo ooooo oo  ooooo  oo oooo o poooo', 'o oooooooo ooooo oo  ooooo  oo oooo o poooo', 'Aprovado', 'Aprovado', 'Aprovado', 'opa tudo bem', '2026-06-03', NULL, NULL, '2026-06-03 00:37:50'),
(9, 3, NULL, NULL, 7, 2, 'otimo', '2026-06-03', NULL, '2/2026', 8, NULL, 'Gestao Empresarial', 0, NULL, 'uhulllll uhuulll uhluuu hulllll uhullll uuu hulllll uhullll uuu hulllll uhullll', 24, 12, 8, 44, 198, 'Administrativo', 'uhulllll uhuulll uhluuu hulllll uhullll uuu hulllll uhullll uuu hulllll uhullll', 'uhulllll uhuulll uhluuu hulllll uhullll uuu hulllll uhullll uuu hulllll uhullll', 'uhulllll uhuulll uhluuu hulllll uhullll uuu hulllll uhullll uuu hulllll uhullll', 'uhulllll uhuulll uhluuu hulllll uhullll uuu hulllll uhullll uuu hulllll uhullll', '', 'uhulllll uhuulll uhluuu hulllll uhullll uuu hulllll uhullll uuu hulllll uhullll', 'uhulllll uhuulll uhluuu hulllll uhullll uuu hulllll uhullll uuu hulllll uhullll', 'uhulllll uhuulll uhluuu hulllll uhullll uuu hulllll uhullll uuu hulllll uhullll', 'Aprovado', 'Aprovado', 'Aprovado', 'beeeeeeeeeeeeeeellllllleeeeeeeeeeeeeeezzzzzzaaaaaaaaaaa', '2026-06-03', NULL, NULL, '2026-06-03 00:57:24'),
(10, 8, NULL, NULL, 7, 2, 'otimo esta tudo certo com seu documeto HAE. Aprovado!!', '2026-06-05', NULL, '1/2026', 8, NULL, 'Testando projeto eduarda', 0, NULL, 'aaaaaaaaaa aaaaaaaaaaa aaaaa aaaaa aa a aaaa aaaa  aaaaaa  a a', 24, 12, 8, 44, 198, 'Acadêmico', 'aaaa aaa aaaaaa aaa aaaaaa aaaaaaaaaa aaaaaaaaaaa aaaaa aaaaa aa a aaaa aaaa  aaaaaa  a a', 'aaaaaaaaaa aaaaaaaaaaa aaaaa aaaaa aa a aaaa aaaa  aaaaaa  a a', 'aaaaaaaaaa aaaaaaaaaaa aaaaa aaaaa aa a aaaa aaaa  aaaaaa  a a', 'aaaaaaaaaa aaaaaaaaaaa aaaaa aaaaa aa a aaaa aaaa  aaaaaa  a a', 'Financeiro,Humano', 'aaaaaaaaaa aaaaaaaaaaa aaaaa aaaaa aa a aaaa aaaa  aaaaaa  a a', 'aaaaaaaaaa aaaaaaaaaaa aaaaa aaaaa aa a aaaa aaaa  aaaaaa  a a', 'aaaaaaaaaa aaaaaaaaaaa aaaaa aaaaa aa a aaaa aaaa  aaaaaa  a a', 'Aprovado', 'Aprovado', 'Aprovado', 'otimo esta tudo certo com seu documeto HAE. Aprovado!!!!!', '2026-06-05', NULL, NULL, '2026-06-03 22:50:35'),
(12, 9, NULL, NULL, 7, 2, 'otimo, perfeito', '2026-06-06', NULL, '2/2026', 8, NULL, 'Projeto do professor João', 0, NULL, 'eitaaaaaaaaaaaaaaaaaaaa eitaaaaaaaaaaa eeeeeeeeeeeeiiiiiiiiiiiiitttttttttttttaaaaaaaaaaaaaa', 24, 12, 8, 44, 198, 'Administrativo', 'eitaaaaaaaaaaaaaaaaaaaa eitaaaaaaaaaaa eeeeeeeeeeeeiiiiiiiiiiiiitttttttttttttaaaaaaaaaaaaaa', 'eitaaaaaaaaaaaaaaaaaaaa eitaaaaaaaaaaa eeeeeeeeeeeeiiiiiiiiiiiiitttttttttttttaaaaaaaaaaaaaa', 'eitaaaaaaaaaaaaaaaaaaaa eitaaaaaaaaaaa eeeeeeeeeeeeiiiiiiiiiiiiitttttttttttttaaaaaaaaaaaaaa', 'eitaaaaaaaaaaaaaaaaaaaa eitaaaaaaaaaaa eeeeeeeeeeeeiiiiiiiiiiiiitttttttttttttaaaaaaaaaaaaaa', 'Financeiro', 'eitaaaaaaaaaaaaaaaaaaaa eitaaaaaaaaaaa eeeeeeeeeeeeiiiiiiiiiiiiitttttttttttttaaaaaaaaaaaaaa', 'eitaaaaaaaaaaaaaaaaaaaa eitaaaaaaaaaaa eeeeeeeeeeeeiiiiiiiiiiiiitttttttttttttaaaaaaaaaaaaaa', 'eitaaaaaaaaaaaaaaaaaaaa eitaaaaaaaaaaa eeeeeeeeeeeeiiiiiiiiiiiiitttttttttttttaaaaaaaaaaaaaa', 'Aprovado', 'Aprovado', 'Aprovado', 'Otimo, perfeito', '2026-06-06', NULL, NULL, '2026-06-05 22:31:27'),
(13, 11, NULL, NULL, 7, 2, 'aprovado com louvor', '2026-06-08', NULL, '2/2026', 8, NULL, 'Relacionamentos com empresas de TI.', 0, NULL, 'Realizar o relacionamento junto às empresas de TI, tornando a Fatec Garça mais conhecida e buscar junto a estas parcerias, apoio para a unidade e fortalecimento dos cursos.', 24, 12, 8, 44, 198, 'Administrativo', 'Buscar junto a empresas de tecnologia da informação uma integração (Faculdade x Empresas) proporcionando ganhos de conhecimentos e troca de experiências para as partes. ', 'Realizar o relacionamento junto às empresas de TI, tornando a Fatec Garça mais conhecida e buscar junto a estas parcerias, apoio para a unidade e fortalecimento dos cursos.', 'Este relacionamento acontecerá através de visitas e reuniões entre as partes, tanto na unidade de ensino como nas empresas.', 'Professor, Direção e Empresas de TI.', 'Físico,Humano', '- Físico.....: Sala dos professores ou residência do professor\r\n- Humano: Acordado com as partes interessadas no projeto.\r\n', 'As atividades irão transcorrer nos meses que compreende o segundo semestre do ano de 2026.', 'Conseguir agregar benefícios para a Fatec Garça e para as empresas de TI.', 'Aprovado', 'Aprovado', 'Aprovado', 'ok, aprovado!', '2026-06-08', NULL, NULL, '2026-06-08 16:39:56'),
(14, 11, 13, NULL, NULL, NULL, NULL, NULL, NULL, '1/2026', 8, NULL, 'testando', 0, NULL, 'teste teste teste teste teste teste teste teste teste teste teste teste teste teste teste', 24, 12, 8, 44, 198, 'Acadêmico', 'teste teste teste teste teste teste teste teste teste teste teste teste teste teste teste', 'teste teste teste teste teste teste teste teste teste teste teste teste teste teste teste', 'teste teste teste teste teste teste teste teste teste teste teste teste teste teste teste', 'teste teste teste teste teste teste teste teste teste teste teste teste teste teste teste', 'Físico', 'teste teste teste teste teste teste teste teste teste teste teste teste teste teste teste', 'teste teste teste teste teste teste teste teste teste teste teste teste teste teste teste', 'teste teste teste teste teste teste teste teste teste teste teste teste teste teste teste', 'Pendente', 'Pendente', 'Pendente', NULL, NULL, NULL, NULL, '2026-06-15 19:13:27'),
(15, 9, 7, NULL, 7, 2, 'bbbbbbbbbbb', '2026-07-01', NULL, '1/2026', 8, NULL, 'realizando outro teste', 0, NULL, 'teste teste teste teste teste teste teste teste teste teste teste teste teste teste teste', 24, 12, 8, 44, 198, 'Acadêmico', 'teste teste teste teste teste teste teste teste teste teste teste teste teste teste teste', 'teste teste teste teste teste teste teste teste teste teste teste teste teste teste teste', 'teste teste teste teste teste teste teste teste teste teste teste teste teste teste teste', 'teste teste teste teste teste teste teste teste teste teste teste teste teste teste teste', 'Financeiro', 'teste teste teste teste teste teste teste teste teste teste teste teste teste teste teste', 'teste teste teste teste teste teste teste teste teste teste teste teste teste teste teste', 'teste teste teste teste teste teste teste teste teste teste teste teste teste teste teste', 'Aprovado', 'Aprovado', 'Aprovado', 'aaaaaaaa', '2026-07-01', NULL, NULL, '2026-06-15 19:32:04'),
(16, 14, 7, NULL, 7, 2, 'otimo', '2026-07-01', NULL, '2/2026', 8, NULL, 'realizando teste de hojee', 0, NULL, 'teste de  teste de hoje teste de hojeteste de hojeteste de hojeteste de hojeteste de hojeteste de hojeteste de hojeteste de hojeteste de hojeteste de hojeteste de hojeteste de hojeteste de hoje', 24, 12, 8, 44, 198, 'Acadêmico', 'teste de hojeteste de hojeteste de hojeteste de hoje', 'teste de hojeteste de hojeteste de hojeteste de hoje', 'teste de hojeteste de hojeteste de hojeteste de hojeteste de hoje', 'teste de hojeteste de hojeteste de hojeteste de hojeteste de hojeteste de hojeteste de hoje', 'Financeiro,Físico', 'teste de hojeteste de hojeteste de hojeteste de hojeteste de hoje', 'teste de hojeteste de hojeteste de hojeteste de hojeteste de hoje', 'teste de hojeteste de hojeteste de hojeteste de hojeteste de hojeteste de hoje', 'Aprovado', 'Aprovado', 'Aprovado', 'esta de acordo', '2026-07-01', NULL, NULL, '2026-07-01 17:27:14'),
(17, 8, 7, NULL, 7, 2, NULL, NULL, NULL, '2/2026', 8, NULL, 'realizando outro teste', 0, NULL, 'ssssssssss', 24, 12, 8, 44, 198, 'Administrativo', 'ssssssssssss', 'ssssssssss', 'sssssssss', 'ssssssssss', 'Financeiro,Físico', 'sssssssssss', 'ssssssssssss', 'ssssssssssssa', 'Pendente', 'Pendente', 'Pendente', NULL, NULL, NULL, NULL, '2026-07-01 19:56:14'),
(18, 29, 7, NULL, 7, 2, 'projeto aprovado', '2026-07-29', NULL, '2/2026', 8, NULL, 'realizando teste de hojee 29.07', 0, NULL, 'testando 29.07 testando 29.07testando 29.07testando 29.07testando 29.07testando 29.07testando 29.07testando 29.07testando 29.07', 24, 12, 8, 44, 198, 'Acadêmico', 'testando 29.07testando 29.07testando 29.07', 'testando 29.07testando 29.07testando 29.07', 'testando 29.07testando 29.07testando 29.07', 'testando 29.07testando 29.07testando 29.07', 'Físico,Humano', 'testando 29.07testando 29.07testando 29.07', 'testando 29.07testando 29.07testando 29.07testando 29.07', 'testando 29.07testando 29.07testando 29.07testando 29.07testando 29.07', 'Aprovado', 'Aprovado', 'Aprovado', 'esta de acordo, projeto aprovado !!!', '2026-07-29', NULL, NULL, '2026-07-29 19:20:38'),
(19, 29, 7, NULL, 7, 2, 'perfeito', '2026-07-29', NULL, '1/2026', 8, NULL, 'realizando teste de hojee 29.07 de rejeição', 0, NULL, 'testando rejeição testando rejeição testando rejeição testando rejeição testando rejeição testando rejeição testando rejeição ', 24, 12, 8, 44, 198, 'Administrativo', 'testando rejeição testando rejeição testando rejeição ', 'testando rejeição testando rejeição testando rejeição ', 'testando rejeição testando rejeição testando rejeição ', 'testando rejeição testando rejeição testando rejeição testando rejeição testando rejeição ', 'Financeiro,Físico', 'testando rejeição testando rejeição testando rejeição testando rejeição ', 'testando rejeição testando rejeição testando rejeição ', 'testando rejeição testando rejeição testando rejeição ', 'Aprovado', 'Aprovado', 'Aprovado', 'otimo aprovado !!!', '2026-07-29', NULL, NULL, '2026-07-29 19:32:06'),
(20, 29, 7, NULL, 7, NULL, 'ruim', NULL, NULL, '1/2026', 8, NULL, 'realizando outro teste rejeição!!!!', 0, NULL, 'testando rejeição testando rejeição testando rejeição testando rejeição testando rejeição testando rejeição testando rejeição ', 24, 12, 8, 44, 198, 'Extensão à comunidade', 'testando rejeição testando rejeição testando rejeição testando rejeição testando rejeição ', 'testando rejeição testando rejeição testando rejeição testando rejeição ', 'testando rejeição testando rejeição testando rejeição testando rejeição testando rejeição ', 'testando rejeição testando rejeição testando rejeição testando rejeição testando rejeição ', 'Financeiro,Físico', 'testando rejeição testando rejeição testando rejeição testando rejeição ', 'testando rejeição testando rejeição ', 'testando rejeição testando rejeição testando rejeição ', 'Rejeitado', 'Rejeitado', 'Pendente', NULL, NULL, NULL, NULL, '2026-07-29 19:35:44'),
(21, 8, 7, NULL, NULL, 2, NULL, NULL, NULL, '2/2026', 4, NULL, 'realizando teste de hoje 0308', 0, NULL, 'aaaaaaaa', 10, 5, 4, 19, 86, 'Acadêmico', 'aaaaaaaaaaaaa', 'aaaaaaaaaa', 'aaaaaaaaaa', 'aaaaaaaaaa', 'Humano', 'aaaaaaaaaaaaaaaaa', 'aaaaaaaaaaaaaaa', 'aaaaaaaaaaaaaaaaa', 'Pendente', 'Pendente', 'Aprovado', 's', '2026-08-03', NULL, NULL, '2026-08-03 17:33:12');

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
(3, 'Teste professoraa', 'professora@gmail.com', '(14) 99837-3207', 'Professor', '2006-11-12', '2025-03-01', 'Indeterminado', 'ADS', 'uploads/assinaturas/dd9d1889e20ed1ca009b5a54459f8795.jpg', '1d8bbc4294d306e8ba3ec733b0b06180', 0),
(4, 'professor_teste', 'professorteste@gmail.com', '(14) 99837-3207', 'Professor', '2006-11-12', '2014-04-04', 'Indeterminado', 'especialista em TI', 'uploads/assinaturas/4c7bd8b0a262942fec377e0cb3763944.jpg', '1d8bbc4294d306e8ba3ec733b0b06180', 0),
(6, 'Victor Matheus', 'victor.matheus@cps.sp.gov.br', '(14) 99837-3207', 'Professor', '1999-12-08', '2023-03-02', 'Indeterminado', 'ADS', 'uploads/assinaturas/79f9c768743cb1228a7d8ae3e3129edd.jpg', '1d8bbc4294d306e8ba3ec733b0b06180', 0),
(7, 'Adriano Nakamura', 'nakamura@cps.sp.gov.br', '(14) 99837-3207', 'Coordenador', '1957-10-28', '2009-04-12', 'Indeterminado', 'nakamura@cps.sp.gov.br', 'uploads/assinaturas/5e53c2367047a679b99234e8cd7962a9.avif', '1d8bbc4294d306e8ba3ec733b0b06180', 0),
(8, 'eduarda professora', 'eduardaprofessora@gmail.com', '(14) 99837-3207', 'Professor', '2007-02-03', '2022-05-04', 'Indeterminado', 'eduardaprofessora@gmail.com', 'uploads/assinaturas/23c6560e00f19601811392924978be82.jpg', '1d8bbc4294d306e8ba3ec733b0b06180', 0),
(9, 'joao professor', 'joao@gmail.com', '(14) 99837-3207', 'Professor', '2000-11-12', '2023-03-04', 'Indeterminado', 'joao@gmail.com', 'uploads/assinaturas/9dd5bb59783570ede18df44b647cf7ca.svg', '1d8bbc4294d306e8ba3ec733b0b06180', 0),
(10, 'maria teste', 'maria@gmail.com', '(14) 99837-3207', 'Professor', '2007-11-12', '2007-02-03', 'Indeterminado', 'ADS', 'uploads/assinaturas/6973a39fac579c1874c7102bff592ef9.jpeg', '1d8bbc4294d306e8ba3ec733b0b06180', 0),
(11, 'Fabio Rodrigues Gonçalves', 'fabio@gmail.com', '(14) 98180-0001', 'Professor', '1968-11-02', '2011-03-04', 'Indeterminado', 'fabio@gmail.com', 'uploads/assinaturas/fb5a28125b31ae489040ccb7b4a765a2.png', '1d8bbc4294d306e8ba3ec733b0b06180', 0),
(12, 'maria teste', 'maaaria@gmail.com', '(14) 99837-3207', 'Professor', '2006-11-12', NULL, NULL, NULL, NULL, '01d2960db8e87a1ddd3a140378d87d29', 1),
(13, 'coordenadora teste', 'coordenadorateste@gmail.com', '(14) 99837-3207', 'Coordenador', '2006-11-12', '2020-05-11', 'Indeterminado', 'coordenadorateste@gmail.com', 'uploads/assinaturas/92db37fa8bab306292d530ff07532f47.png', '1d8bbc4294d306e8ba3ec733b0b06180', 0),
(14, 'teste de HOJE', 'testehoje@gmail.com', '(14) 99837-3207', 'Professor', '2006-11-12', '2022-04-03', 'Indeterminado', 'Doutorado Em ADS', 'uploads/assinaturas/6603fa4b7d28409b07923630476615a6.jpg', '1d8bbc4294d306e8ba3ec733b0b06180', 0),
(15, 'aaa@gmail.com', 'aaa@gmail.com', '(14) 99837-3207', 'Professor', '2006-11-12', NULL, NULL, NULL, NULL, '01d2960db8e87a1ddd3a140378d87d29', 1),
(16, 'diiiirrrreeeetooooor', 'diretor1212@gmail.com', '(14) 99837-3207', 'Diretor', '2006-11-12', NULL, NULL, NULL, NULL, '01d2960db8e87a1ddd3a140378d87d29', 1),
(17, 'aaa', 'aa@gmail.com', '(14) 99837-3207', 'Professor', '2006-11-12', NULL, NULL, NULL, NULL, '01d2960db8e87a1ddd3a140378d87d29', 1),
(29, 'Stefani de Oliveira Santos', 'stefanisantos12112006@gmail.com', '(14) 99837-3207', 'Professor', '2006-11-12', '2022-03-03', 'Indeterminado', 'doutarado em ciencia da computação', 'uploads/assinaturas/8edc0288eb90fad646308b0b5faeab04.jpg', '1d8bbc4294d306e8ba3ec733b0b06180', 0);

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
-- Índices de tabela `configuracoes`
--
ALTER TABLE `configuracoes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `chave` (`chave`);

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT de tabela `configuracoes`
--
ALTER TABLE `configuracoes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de tabela `relatorios_hae`
--
ALTER TABLE `relatorios_hae`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT de tabela `solicitacoes_hae`
--
ALTER TABLE `solicitacoes_hae`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT de tabela `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=30;

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
