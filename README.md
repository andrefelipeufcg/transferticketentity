# Plugin GLPI Transfer Ticket Entity (PT-BR)

## Introdução

Este plugin permite que perfis autorizados transfiram chamados de uma entidade para outra, mesmo quando não possuem acesso à entidade de destino. Ele foi desenvolvido para organizações que configuram seus perímetros de atuação no GLPI por entidade, como Recursos Humanos, Contabilidade e Tecnologia da Informação.

O objetivo é permitir a transferência de chamados entre técnicos do GLPI de entidades diferentes, preservando a confidencialidade das informações entre as entidades.

Por exemplo, um perfil de técnico da Contabilidade pode visualizar, por padrão, apenas os chamados da entidade Contabilidade. Se um chamado atribuído a ele estiver relacionado ao departamento de Recursos Humanos, ele poderá transferi-lo com acompanhamento. Após a transferência, o técnico não terá mais visibilidade sobre o chamado.

## Documentação

Para configurar os pré-requisitos da transferência entre entidades:

- Acesse **Administração > Perfis > Seu perfil > Assistência > Chamado** e marque a permissão **Atualizar**.
- **Permitir transferência de chamados**: define se a transferência é permitida para a entidade e os grupos associados.
- **Exigir atribuição a um grupo**:
  - Define se o chamado transferido deve ser obrigatoriamente atribuído a um grupo.
  - Quando ativado, somente o grupo padrão configurado permanece como grupo técnico após a transferência, mesmo que regras adicionem outros grupos durante a atualização.
  - Quando desativado, a opção "Nenhum" ficará disponível na lista de grupos de destino.
  - Se o chamado for enviado para uma entidade sem grupo, ele será considerado "Novo".
- **Exigir justificativa para transferência**:
  - Quando ativado, o campo de justificativa será exibido com destaque vermelho.
  - Quando desativado, o campo será exibido com destaque azul, mas continuará disponível para preenchimento.
- **Manter a SLA após a transferência**:
  - Quando ativado, mantém as SLAs de atendimento e solução do chamado.
  - Quando desativado, remove as SLAs e suas ações de escalonamento pendentes; os prazos existentes e as OLAs não são alterados por essa opção.
  - Nas configurações existentes, a opção inicia ativada.
- **Manter categoria após a transferência**:
  - Quando desativado, é possível configurar uma categoria padrão, que será exibida e aplicada sem permitir edição durante a transferência.
  - Sem uma categoria padrão, a categoria atual é exibida e pode ser alterada para uma categoria disponível na entidade de destino.
  - Se a categoria atual não estiver disponível no destino, é necessário escolher outra antes da transferência.
  - Se a categoria for obrigatória no modelo do GLPI da entidade de destino, não será possível transferir sem categoria.

## Onde configurar o plugin

Os direitos de acesso ao plugin podem ser configurados em **Administração > Perfis**. Os pré-requisitos da transferência são administrados nas configurações das entidades.

Administradores com permissão para atualizar a configuração também podem utilizar o ícone de configuração do plugin. A tela possui abas laterais para **Entidades**, **Perfis** e **Grupos**, além da aba **Todos**, que exibe as configurações em conjunto:

- **Entidades**: aplica as configurações de transferência a várias entidades simultaneamente. O grupo e a categoria selecionados devem estar disponíveis em todas as entidades escolhidas.
- **Perfis**: aplica os dois direitos do plugin a vários perfis simultaneamente.
- **Grupos**: adiciona ao grupo atribuído padrão os usuários dos grupos selecionados. Usuários que já são membros não são duplicados. O grupo atribuído padrão é definido na aba Entidades.

Na aba **Grupos**, a caixa ao lado de cada entidade permite marcar ou desmarcar todos os seus grupos. Ela fica marcada quando todos os grupos disponíveis da entidade estão selecionados.

Use a seta ao lado de cada entidade para expandir ou recolher seus grupos sem alterar as seleções. Ao filtrar, as entidades com resultados são expandidas; ao limpar o filtro, o estado anterior é restaurado.

Use o filtro acima de cada lista para localizar itens sem alterar suas seleções. Os botões de seleção em massa e a caixa de seleção da entidade continuam afetando todos os itens correspondentes, inclusive os ocultos pelo filtro.

O carregamento das abas e o salvamento exibem um indicador de progresso.

## Log de transferências

Após cada transferência concluída, o plugin tenta registrar um evento e uma entrada no arquivo **transferticketentity.log**, consultáveis em **Administração > Logs**, com o usuário, o chamado, as entidades de origem e destino e a justificativa informada. Ambos são registrados independentemente do nível de log ou da opção de logs em arquivo do GLPI. O evento segue a política de retenção dos eventos do GLPI; o arquivo segue a gestão de arquivos de log do GLPI. O acesso aos logs pode revelar justificativas a administradores que não tenham acesso ao chamado.

O evento e o arquivo são gravados após a confirmação da transferência, sem garantia transacional. Falhas são registradas no log de erros do servidor, sem aviso ao usuário e sem desfazer a transferência. Se a mensagem exceder o limite do evento no banco, o evento não é gravado, mas o registro no arquivo continua sendo tentado. O arquivo é criado na primeira gravação bem-sucedida, no diretório de logs do GLPI. O registro direto do evento não dispara o webhook de novo evento do GLPI.

## Compatibilidade

Este plugin foi desenvolvido e testado para uso com o GLPI 11.

---

# GLPi Transfer Ticket Entity Plugin (FR)

## Introduction

Ce plugin permet, pour les profils autorisés, de transférer des tickets d'une entité à une autre sur laquelle ils n'ont pas les accès.
Il s'adresse aux organisations qui ont configurés, dans GLPI, leurs périmètres d'activités par entité (DRH, Comptabilité, SI, ...).

L'intérêt étant de pouvoir transférer un ticket entre techniciens "GLPI" d'entités différentes et de continuer à assurer la confidentialité des données entre entités.

Pour exemple : 
Un profil "technicien comptable" a, par défaut, visibilité que sur les tickets de son entité "Comptabilité".
Dès lors, où un des tickets, qui lui est assigné, concerne la DRH, il aura la possibilité de leur transférer avec suivi.
Une fois le transfert effectué, il n'a plus aucune visibilité sur le ticket.

## Documentation

Pour configurer les prérequis du transfert d'entité :

- Dans Administration > Profils > VotreProfil > Assistance > Ticket : Mettre à jour doit être coché.

- Autoriser la fonction Transfert : définit si le transfert est autorisé vers l'entité et les groupes associés.
- Groupe à assigner obligatoire pour effectuer un transfert :
	- Définit si le ticket transféré doit être obligatoirement assigné à un groupe.
	- Si oui, seul le groupe par défaut configuré reste assigné au ticket après le transfert, même si des règles ajoutent d'autres groupes pendant la mise à jour.
	- Si non, le choix "aucun" apparaîtra dans la liste du groupe cible et sera à sélectionner
	- Si le ticket est envoyé dans une entité sans groupe, il sera considéré comme "nouveau"
- Justification obligatoire pour effectuer un transfert
	- Si oui, le champ de saisi s'affiche avec un encart rouge
	- Si non, ce dernier apparaîtra avec un encart bleu toutefois la zone de saisie restera active si besoin.
- Garder la catégorie après le transfert :
    - Si oui, la catégorie du ticket sera gardé après le transfert seulement si elle est disponible dans l'entité cible, sinon elle sera remise à null.
    - Si non, la catégorie du ticket sera remise à null.
    - Si non, il est possible de choisir une catégorie par défaut.
    - :warning: Si la catégorie est obligatoire dans l'entité de destination (via les templates de GLPI) et qu'elle sera équivalente à null après le transfert, une erreur apparaîtra.

## Où configurer le plugin

Vous pouvez configurer les droits d'accès au plugin dans l'administration des profils.
Les prérequis du transfert se gèrent dans l'administration des entités.

Les administrateurs ayant le droit de mise à jour de la configuration peuvent aussi utiliser l'icône de configuration du plugin. Elle contient trois onglets latéraux, plus l'onglet « Tous » qui les affiche ensemble :

- **Entités** : applique les paramètres de transfert à plusieurs entités à la fois. Le groupe et la catégorie choisis doivent être accessibles dans toutes les entités sélectionnées.
- **Profils** : applique les deux droits du plugin à plusieurs profils à la fois.
- **Groupes** : ajoute au groupe attribué par défaut les utilisateurs des groupes sélectionnés. Les utilisateurs déjà membres ne sont pas dupliqués. Les utilisateurs appartenant à des groupes désélectionnés sont retirés du groupe attribué, sauf s'ils appartiennent également à un groupe sélectionné. Le groupe attribué par défaut ne peut pas être sélectionné comme groupe source et est défini dans l'onglet Entités.

Dans l'onglet **Groupes**, la case à côté de chaque entité permet de sélectionner ou désélectionner tous ses groupes. Elle est cochée lorsque tous les groupes disponibles de l'entité sont sélectionnés.

La flèche à côté de chaque entité permet de déplier ou replier ses groupes sans modifier leur sélection. Le filtre déplie les entités contenant des résultats, puis restaure leur état lorsque le filtre est effacé.

Le filtre au-dessus de chaque liste permet de trouver des éléments sans modifier leur sélection. Les actions de sélection en masse et la case de l'entité s'appliquent aussi aux éléments masqués par le filtre.

Les paramètres sont conservés et rechargés lors de la réouverture de la page de configuration. Les entités et profils désélectionnés sont automatiquement désactivés lors de l'enregistrement. Le chargement des onglets et l'enregistrement affichent un indicateur de progression.

L'onglet « Groupes » peut être temporairement masqué dans l'interface tout en restant disponible dans le code pour une utilisation future.

## Journal des transferts

Après chaque transfert réussi, le plugin tente d'enregistrer un événement et une entrée dans **transferticketentity.log**, consultables dans **Administration > Journaux**, avec l'utilisateur, le ticket, les entités d'origine et de destination et la justification. Ils sont enregistrés quels que soient le niveau de journalisation et l'option de fichiers de log de GLPI. L'événement suit la politique de conservation des événements de GLPI ; le fichier suit la gestion des fichiers de log de GLPI. Les administrateurs ayant accès aux journaux peuvent voir des justifications de tickets auxquels ils n'ont pas accès.

L'événement et le fichier sont enregistrés après confirmation du transfert, sans garantie transactionnelle. Les erreurs sont consignées dans le journal d'erreurs du serveur, sans avertissement à l'utilisateur ni annulation du transfert. Si le message dépasse la limite de stockage des événements, l'événement n'est pas enregistré, mais l'écriture du fichier est toujours tentée. Le fichier est créé dans le répertoire des logs GLPI lors de la première écriture réussie. L'enregistrement direct ne déclenche pas le webhook de nouvel événement de GLPI.

## Compatibilité

Ce plugin a été développé et testé pour GLPI 11.

La compatibilité avec GLPI 10.0.14, mentionnée dans la documentation d'origine, n'a pas été revalidée pour cette version personnalisée.

# GLPi Transfer Ticket Entity Plugin (EN)

## Introduction

This plugin enables authorized profiles to transfer tickets from one entity to another to which they do not have access.
It is designed for organizations that have configured their GLPI activity perimeters by entity (HR, Accounting, IS, etc.).

The aim is to be able to transfer a ticket between "GLPI" technicians from different entities, and to continue to ensure the confidentiality of data between entities.

For example: 
An "accounting technician" profile has, by default, visibility only on the tickets of its "Accounting" entity.
If one of the tickets assigned to him concerns the HR department, he will be able to transfer it with follow-up.
Once the transfer has been made, he no longer has any visibility of the ticket.

## Documentation

To configure entity transfer prerequisites :

- In Administration > Profile > YourProfile > Assistance > Ticket : Update must be checked.

- **Allow ticket transfers**: defines whether transfers are allowed to the entity and its associated groups.
- **Require assignment to a group**:
    - Defines whether the transferred ticket must be assigned to a group.
    - When enabled, only the configured default group remains assigned after transfer, even if rules add other groups during the update.
    - If not, the choice "none" will appear in the target group list and must be selected.
    - If the ticket is sent to an entity without a group, it will be considered "new"
- Justification required to make a transfer :
    - If yes, the input field is displayed with a red highlighting.
    - If no, it will appear with a blue highlighting, but the input field will remain active if required.
- Keep category after transfer :
    - When disabled, a default category can be configured; it is displayed and applied without allowing edits during transfer.
    - Without a default category, the current category is displayed and can be changed to one available in the destination entity.
    - If the current category is unavailable in the destination, another must be selected before transfer.
    - If the destination entity's GLPI template requires a category, the ticket cannot be transferred without one.

## Where set up the plugin

You can configure access rights to the plugin in profile administration.
The transfer prerequisites are managed in the entity administration.

Administrators with permission to update the configuration can also use the plugin configuration icon. It contains side tabs for **Entities**, **Profiles**, and **Groups**, plus an **All** tab that displays them together:

- **Entities**: applies transfer settings to multiple entities at once. The selected group and category must be available in every selected entity.
- **Profiles**: applies the plugin's two rights to multiple profiles at once.
- **Groups**: adds users from the selected groups to the default assigned group. Existing memberships are not duplicated. Users belonging to deselected groups are removed from the assigned group unless they also belong to a selected group. The default assigned group cannot be selected as a source group and is configured in the Entities tab.

In the **Groups** tab, the checkbox next to each entity selects or clears all of its groups. It is checked when all available groups in that entity are selected.

Use the arrow next to an entity to expand or collapse its groups without changing selections. Filtering expands entities with matches; clearing the filter restores their previous state.

Use the filter above each list to find items without changing their selections. Bulk actions and entity checkboxes still affect all corresponding items, including those hidden by the filter.

Settings are persisted and restored when the configuration page is reopened. Deselected entities and profiles are automatically disabled when settings are saved. Loading tabs and saving display a progress indicator.

The **Groups** tab may be temporarily hidden in the interface while remaining available in the source code for future use.

## Transfer event log

After each successful plugin transfer, the plugin attempts to record an event and an entry in **transferticketentity.log**, available under **Administration > Logs**, containing the user, ticket, source and destination entities, and the supplied justification. Both are recorded regardless of GLPI's log level or file logging setting. The event follows GLPI's event retention policy; the file follows GLPI's log file management. Administrators with log access can see justifications for tickets they cannot otherwise access.

The event and file are written after the transfer commits, without transactional guarantees. Failures are recorded in the server error log without notifying the user or undoing the transfer. If the message exceeds the database event limit, the event is skipped but file logging is still attempted. The file is created in GLPI's log directory on the first successful write. Direct event insertion does not trigger GLPI's new-event webhook.

## Compatibility

This plugin was developed and tested for GLPI 11.
