<?php

namespace GlpiPlugin\Transferticketentity\Controller;

use Entity as GlpiEntity;
use Config;
use Glpi\Controller\AbstractController;
use Glpi\Http\RedirectResponse;
use GlpiPlugin\Transferticketentity\Entity;
use Html;
use Session;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class EntityController extends AbstractController
{
    #[Route("/front/entity.form.php", name: "transferticketentity_entity")]
    public function __invoke(Request $request): Response
    {
        global $DB;

        $entity_id = $request->request->getInt('entity_id', -1);
        $entity = new GlpiEntity();
        $entity->check($entity_id, UPDATE);

        $itilcategories_id = $request->request->getInt('itilcategories_id');

        $allowTransfer = $request->request->getBoolean('allow_transfer');
        $groupRequired = $allowTransfer && $request->request->getBoolean('allow_entity_only_transfer');
        $groups_id = $request->request->getInt('groups_id');

        if ($allowTransfer && $groupRequired) {
            $availableGroupIds = array_map('intval', array_column(Entity::getEntityGroups($entity_id), 'id'));
            if ($groups_id <= 0 || !in_array($groups_id, $availableGroupIds, true)) {
                Session::addMessageAfterRedirect(
                    __('Select a valid assigned group when group assignment is required.', 'transferticketentity'),
                    true,
                    ERROR
                );

                return new RedirectResponse(Html::getBackUrl());
            }
        } else {
            $groups_id = 0;
        }

        $DB->updateOrInsert(
            'glpi_plugin_transferticketentity_entities_settings',
            [
                'allow_entity_only_transfer' => $groupRequired,
                'justification_transfer' => $request->request->getBoolean('justification_transfer'),
                'allow_transfer' => $allowTransfer,
                'keep_category' => $request->request->getBoolean('keep_category'),
                'itilcategories_id' => $itilcategories_id > 0 ? $itilcategories_id : null,
                'groups_id' => $groups_id,
            ],
            [
                'entities_id' => $entity_id,
            ]
        );
        if ($groups_id > 0) {
            Config::setConfigurationValues('transferticketentity', [
                'assigned_group_id' => $groups_id,
            ]);
        }

        Session::addMessageAfterRedirect(
            sprintf(
                __('Item successfully updated: %s', 'transferticketentity'),
                $entity->getName()
            ),
            true,
            INFO
        );

        return new RedirectResponse(Html::getBackUrl());
    }
}
