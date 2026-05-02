Preciso criar um site onde ficará hospedado no site próprio do lovable com as seguintes referências.


Ideias para o sistema de restaurante trabalho do IFSP

Um sistema de gestão de restaurante feito usando PHP/Laravel
O sistema deve ter para os usuários que vão usar o sistema um gerenciador de departamentos e esse departamentos vão ser ter visualização apenas para algumas telas e quem define qual usuário consegue logar e visualizar? -- O sistema utilizará controle de acesso baseado em funções (RBAC), onde o administrador é responsável por criar usuários e vinculá-los a departamentos específicos, determinando assim quais telas e funcionalidades cada usuário pode acessar.
	
-----------------------------------------------------------------------------------------------------------------------------------------------------------------------

Gerenciamento feito pelo admin

- Consegue ver todos os departamentos e todas as telas dos departamentos (Acesso total)
- Deve criar e registrar usuários dentro da plataforma (esses usuários vão ser os funcionários do restaurante)

Gerenciamento da cozinha

- Visualiza o pedido dos pratos
- Deve mudar o status do pedido
- Visualiza todos os históricos de pedido

O projeto é grande deve ter um sistema de tablet para o cliente conseguir fazer o pedido e esse pedido só vai ser registrado para o departamento da cozinha, a cozinha registra o status do pedido e de forma inicial deve estar como "Em execução"

Os pedidos devem aparecer de forma simplificada para quem gerencia o restaurante, deve conter uma UIX simples para apenas mudar o status dos pedidos


Gerenciamento do financeiro

O financeiro é um outro departamento que consegue ver algumas métricas do sistema
- Total de pedido
- Pedidos Em andamento
- Ganhos
- Despesas
--- SE FALTAR MÉTRICA POSSO COLOCAR AQUI

-----------------------------------------------------------------------------------------------------------------------------------------------------------------------

FLUXO DE TELA (PROTÓTIPO)

TELA DE LOGIN
O login só vai ter nome e senha quem cria o login? (O administrador consegue criar o login para o funcionário) -> o admin já deve ter um login pronto


TELA DE INICIO 
Tela de dashboard muda dependendo do departamento do usuário
Cozinha
-- Total de pedidos em preparo
-- Últimos pedidos
-- Tempo médio de preparo

Financeiro
-- Total de pedidos em preparo
-- Últimos pedidos
-- Tempo médio de preparo

Administrador 
Resumo de todos os departamento (dash)

TELA DE PEDIDOS	-> só o departamento cozinha e usuários no departamento consegue ver
Mostra os pedidos registrados pelo tablet na hora de fazer o pedido
Nesta tela a cozinha consegue registrar status dos pedidos e vão ter 2 tipos de visualização PEDIDOS EM PREPARO E HISTÓRICO DE PEDIDOS

Pedidos em preparo vai ser todos os pedidos que estão com o status que estão em preparo, quando essa condição mudar some da página de preparo e parte para a página de histórico dos pedidos

Em histórico de pedido deve ter um filtro dos dias que foram feitos os pedidos, exemplo: data inicio 17/03/2026 -> data final 20/03/2026 deve pegar apenas os pedidos desses dias

Vão ter 3 status importante 
EM PREPARO -> aparece na tela de pedidos em preparo
PRONTO -> aparece no histórico de pedido quando o status do preparo mudar
CANCELADO -> aparece no histórico de pedido quando o status do preparo mudar

IMPORTANTE 
Os pedidos devem conter o número da mesa que fez o pedido -> tablet 1 = mesa 1, tablet 2 = mesa2 (Preciso entender melhor como faz esta divisão)

Cada pedido como um card:
Mesa: 3
Pedido: #102
Itens:
X-Burger
Batata
Observação: “Sem picles”

Botão grande:
[Marcar como pronto]


TELA DE CADASTROS -> apenas o admin consegue cadastrar
os cadastros são:
Cadastro de usuário -> botão para adicionar o usuário -> abre um menu na própria tela para fazer o cadastro do usuário pede o nome, departamento em que ele vai fazer parte, senha para login e o e-mail. A senha deve ser apenas para o primeiro login, quando o usuário faz o primeiro acesso na plataforma a plataforma pede uma redefinição de senha. Precisa de um botão para editar e apagar o usuário da listas de usuário. 

Os usuários são listados 
nome usuário
e-mail
departamento
ações -> editar e apagar


Cadastro de pratos -> Nome do prato, descrição, preço e foto do prato
Os pratos são listados também em uma lista 
Nome do prato
descrição
preço
ações -> editar e apagar
Deve ser categorizado, exemplo: Pratos principais, bebidas, sobremesas

Departamentos -> Os departamentos também já devem ser criados -> Admin, cozinha e financeiro (SEM OPÇÃO PARA EDIÇÃO OU EXCLUSÃO)


GERENCIAMENTO DO FINANCEIRO / CAIXA

O financeiro também atua como caixa do restaurante, sendo responsável por visualizar os pedidos das mesas e realizar o fechamento da conta.

O caixa consegue visualizar todas as mesas que possuem pedidos registrados no sistema e identificar se a conta já foi paga ou não.

Funcionalidades do caixa:
Visualiza todas as mesas com pedidos em aberto
Visualiza o total acumulado por mesa
Identifica o status da mesa (EM ABERTO ou PAGO)
Pode acessar os detalhes dos pedidos de cada mesa
Realiza o fechamento da conta da mesa
VISUALIZAÇÃO DAS MESAS

As mesas devem ser exibidas em formato de lista ou cards contendo:

Número da mesa
Valor total dos pedidos da mesa
Status da mesa:
EM ABERTO
PAGO

Ações disponíveis:
[Ver detalhes]
[Fechar conta]
DETALHES DA MESA

Ao acessar os detalhes da mesa, o caixa consegue visualizar:

Lista de todos os pedidos da mesa
Itens de cada pedido
Preço individual dos itens
Status do pedido (EM PREPARO, PRONTO, CANCELADO)
Observações feitas pelo cliente (ex: sem picles)

Também deve ser exibido:

Valor total da mesa (soma de todos os pedidos não pagos)
TRIAGEM DE PAGAMENTO (IMPORTANTE)

Todos os pedidos devem possuir um controle de pagamento para identificar se já foram pagos ou não.

Pedidos com pagamento pendente:
paid = false
Pedidos já pagos:
paid = true

O caixa deve considerar apenas os pedidos com pagamento pendente para calcular o total da mesa.

FECHAMENTO DA CONTA

Ao clicar em [Fechar conta], o sistema deve:

Marcar todos os pedidos da mesa como pagos (paid = true)
Alterar o status da mesa para PAGO

Após isso:

A mesa não deve mais aparecer como pendente
Os pedidos continuam registrados para histórico e relatórios
FORMA DE PAGAMENTO (OPCIONAL - DIFERENCIAL)

MÉTRICAS DO FINANCEIRO

O departamento financeiro deve visualizar indicadores do sistema, como:

Total de pedidos
Pedidos em andamento
Ganhos totais
Despesas
Faturamento do dia
Número de mesas atendidas
-----------------------------------------------------------------------------------------------------------------------------------------------------------------------
FLUXO DA TELA DE TABLET Subdomínio (PROTÓTIPO)
Cliente registra pedido e pode fazer observação EX: SEM PICLES




Diferenciais
O cliente consegue fazer uma avaliação dentro da página principal do site (ideia inicial) 
Avaliação de prato feita pelo cliente estilo LatterBox
