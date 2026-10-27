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
  - Quando desativado, a opção "Nenhum" ficará disponível na lista de grupos de destino.
  - Se o chamado for enviado para uma entidade sem grupo, ele será considerado "Novo".
- **Exigir justificativa para transferência**:
  - Quando ativado, o campo de justificativa será exibido com destaque vermelho.
  - Quando desativado, o campo será exibido com destaque azul, mas continuará disponível para preenchimento.
- **Manter categoria após a transferência**:
  - Quando ativado, a categoria será mantida somente se estiver disponível na entidade de destino; caso contrário, será removida.
  - Quando desativado, a categoria será removida.
  - Também é possível selecionar uma categoria padrão.
  - :warning: Se a categoria for obrigatória na entidade de destino, por meio dos modelos do GLPI, e for removida durante a transferência, será exibido um erro.

## Onde configurar o plugin

Os direitos de acesso ao plugin podem ser configurados em **Administração > Perfis**. Os pré-requisitos da transferência são administrados nas configurações das entidades.

Administradores com permissão para atualizar a configuração também podem utilizar o ícone de configuração do plugin. A tela possui abas laterais para **Entidades**, **Perfis** e **Grupos**, além da aba **Todos**, que exibe as configurações em conjunto:

- **Entidades**: aplica as configurações de transferência a várias entidades simultaneamente. O grupo e a categoria selecionados devem estar disponíveis em todas as entidades escolhidas.
- **Perfis**: aplica os dois direitos do plugin a vários perfis simultaneamente.
- **Grupos**: adiciona ao grupo atribuído padrão os usuários dos grupos selecionados. Usuários que já são membros não são duplicados. O grupo atribuído padrão é definido na aba Entidades.

O carregamento das abas e o salvamento exibem um indicador de progresso.

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

Les paramètres sont conservés et rechargés lors de la réouverture de la page de configuration. Les entités et profils désélectionnés sont automatiquement désactivés lors de l'enregistrement. Le chargement des onglets et l'enregistrement affichent un indicateur de progression.

L'onglet « Groupes » peut être temporairement masqué dans l'interface tout en restant disponible dans le code pour une utilisation future.

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
    - If not, the choice "none" will appear in the target group list and must be selected.
    - If the ticket is sent to an entity without a group, it will be considered "new"
- Justification required to make a transfer :
    - If yes, the input field is displayed with a red highlighting.
    - If no, it will appear with a blue highlighting, but the input field will remain active if required.
- Keep category after transfer :
    - If yes, category's ticket will be keep only if it is available in the target entity, otherwise it will be reset to null.
    - If no, category's ticket will be set to null.
    - If no, it's possible to select a default category.
    - :warning: If category is mandatory in the chosen entity (via GLPI's templates) and it will be equal to null after transfer, an error will occur.

## Where set up the plugin

You can configure access rights to the plugin in profile administration.
The transfer prerequisites are managed in the entity administration.

Administrators with permission to update the configuration can also use the plugin configuration icon. It contains side tabs for **Entities**, **Profiles**, and **Groups**, plus an **All** tab that displays them together:

- **Entities**: applies transfer settings to multiple entities at once. The selected group and category must be available in every selected entity.
- **Profiles**: applies the plugin's two rights to multiple profiles at once.
- **Groups**: adds users from the selected groups to the default assigned group. Existing memberships are not duplicated. Users belonging to deselected groups are removed from the assigned group unless they also belong to a selected group. The default assigned group cannot be selected as a source group and is configured in the Entities tab.

Settings are persisted and restored when the configuration page is reopened. Deselected entities and profiles are automatically disabled when settings are saved. Loading tabs and saving display a progress indicator.

The **Groups** tab may be temporarily hidden in the interface while remaining available in the source code for future use.

## Compatibility

This plugin was developed and tested for GLPI 11.
