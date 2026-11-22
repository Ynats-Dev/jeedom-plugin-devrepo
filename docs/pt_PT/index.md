---
layout: default
lang: pt_PT
---

Descrição
===

O DevRepo atualiza os seus plugins **em desenvolvimento** diretamente a partir de um ramo do respetivo repositório Git (**Gogs**, **Gitea** ou **GitHub**), sem git nem acesso SSH à box. É particularmente prático quando o Jeedom corre num contentor Docker.

Para cada plugin seguido, um clique em **Atualizar** transfere o arquivo do ramo, verifica que se trata do plugin certo, substitui os seus ficheiros e depois executa a sua função de atualização (como uma atualização normal a partir do Market).

> Reservado ao desenvolvimento: os ficheiros ausentes do ramo não são eliminados e não é feita nenhuma cópia de segurança antes da atualização.

Configuração
===

![Lista de plugins seguidos](../images/devrepo_screenshot.png)

A lista de plugins seguidos encontra-se na página do plugin: **Plugins → Programação → DevRepo**. Para cada plugin:

- **Plugin**: o seu identificador (nome da sua pasta em `plugins/`), ex. `groupir`. São propostos os plugins instalados. Pode ser indicado um plugin ainda não instalado: o DevRepo cria a sua pasta e falta apenas ativá-lo na gestão de plugins;
- **Repositório**: URL do repositório, ex. `https://servidor/proprietario/repositorio.git` ou `https://github.com/proprietario/repositorio`;
- **Ramo**: ramo a seguir (`beta` por predefinição);
- **Auto**: atualiza o plugin automaticamente a cada novo commit no ramo (ver abaixo);
- **Token**: necessário se o repositório for privado (Gogs / Gitea: *Definições → Aplicações*; GitHub: *Settings → Developer settings → Personal access tokens*). É guardado **cifrado** e nunca mais é apresentado: deixe o campo vazio para o manter, ou use a borracha para o eliminar.

Clique em **Guardar** (a lista também é guardada automaticamente antes de uma atualização).

Utilização
===

- **Verificar**: compara a última atualização de cada plugin com o último commit do seu ramo (*atualizado* ou *novo commit*);
- **Atualizar**: obtém o ramo e substitui os ficheiros do plugin. A coluna *Última atualização* indica a data e o commit instalados (detalhe ao passar o rato).

Atualização automática
===

Para os plugins com a caixa **Auto** assinalada, o DevRepo verifica o ramo **a cada 5 minutos**: assim que é enviado um novo commit, o plugin é atualizado como com o botão *Atualizar*. Um ícone de robô em *Última atualização* assinala uma atualização feita automaticamente.

- A tarefa corresponde à linha **cron5** do painel *Funcionalidades* da configuração do plugin: desativá-la suspende todas as atualizações automáticas;
- se a atualização para um commit falhar, não é repetida em ciclo: o DevRepo aguarda o commit seguinte (ou um clique em *Atualizar*). O erro está no log `devrepo`;
- um repositório temporariamente inacessível é simplesmente verificado de novo na passagem seguinte.

Webhook: atualização imediata
===

Em vez de esperar pela verificação, o repositório pode avisar o Jeedom a cada push. O botão <i class="fas fa-plug"></i> de uma linha mostra o URL do webhook desse plugin (acesso interno e externo do Jeedom):

- no repositório: *Definições → Webhooks* (Gogs, Gitea ou GitHub), cole o URL, evento **push**, tipo de conteúdo **JSON**;
- a atualização só é feita se **Auto** estiver assinalado e o ramo enviado for o seguido;
- o repositório tem de conseguir aceder ao Jeedom: um Gogs / Gitea da rede local usa o acesso interno; o GitHub precisa do acesso externo.

O URL contém a chave API do DevRepo (*Definições → Sistema → Configuração → API*): não a partilhe.

Modo tempo real
===

Se o repositório não conseguir aceder ao Jeedom (GitHub e box não exposta na internet), o botão **Tempo real** verifica os ramos dos plugins **Auto** **a cada 10 segundos durante 1 hora** (por predefinição), o tempo de uma sessão de desenvolvimento. O botão mostra o tempo restante; um clique para-o. Durante este modo, a verificação a cada 5 minutos fica suspensa.

A duração escolhe-se na lista ao lado do botão: de 15 minutos a 8 horas. É aplicada na próxima ativação.

No GitHub sem token (no máximo 60 pedidos por hora), a verificação é espaçada para 90 segundos: introduza um token para aproveitar os 10 segundos.

Notificação
===

Cada atualização automática (webhook, tempo real ou verificação a cada 5 minutos) mostra uma notificação em todas as páginas do Jeedom abertas, com o commit instalado, ou o erro em caso de falha. Se a página do DevRepo estiver aberta, a linha do plugin é atualizada.

O DevRepo também se pode atualizar a si próprio: adicione `devrepo` à lista com o seu repositório.

Em caso de erro, é apresentada uma mensagem explícita (repositório não encontrado, token recusado, arquivo que não contém o plugin certo, permissões de escrita...). O detalhe está no log `devrepo`.
