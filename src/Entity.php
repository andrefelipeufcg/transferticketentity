<?php

/**
 -------------------------------------------------------------------------
 LICENSE

 This file is part of Transferticketentity plugin for GLPI.

 Transferticketentity is free software: you can redistribute it and/or modify
 it under the terms of the GNU Affero General Public License as published by
 the Free Software Foundation, either version 3 of the License, or
 (at your option) any later version.

 Transferticketentity is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 GNU Affero General Public License for more details.

 You should have received a copy of the GNU Affero General Public License
 along with Reports. If not, see <http://www.gnu.org/licenses/>.

 @category  Ticket
 @package   Transferticketentity
 @author    Yannick Comba <y.comba@maine-et-loire.fr>
 @copyright 2015-2023 Département de Maine et Loire plugin team
 @license   AGPL License 3.0 or (at your option) any later version
            https://www.gnu.org/licenses/gpl-3.0.html
 @link      https://github.com/departement-maine-et-loire/
 --------------------------------------------------------------------------
*/

namespace GlpiPlugin\Transferticketentity;

use CommonDBTM;
use CommonGLPI;
use Dropdown;
use Entity as GlpiEntity;
use Glpi\Application\View\TemplateRenderer;
use Group;
use Html;
use Session;

class Entity extends CommonDBTM
{
    public static function getIcon(): string
    {
        return 'ti ti-transfer';
    }

    /**
     * If the profile is authorised, add an extra tab
     *
     * @param CommonGLPI $item Entity
     * @param int $withtemplate 0
     *
     * @return "Entity ticket transfer"
     */
    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
    {
        if ($item instanceof GlpiEntity
            && $item->getField('id') != NOT_AVAILABLE) {
            return self::createTabEntry(__s("Transfer Ticket Entity", "transferticketentity"));
        }
        return '';
    }

    /**
     * If we are on tickets, an additional tab is displayed
     *
     * @param CommonGLPI $item Ticket
     * @param int $tabnum 1
     * @param int $withtemplate 0
     *
     * @return true
     */
    public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0)
    {
        if ($item instanceof GlpiEntity) {
            return self::showForEntity($item->getID());
        }

        return true;
    }

    public static function checkRights($ID)
    {
        global $DB;

        return $DB->request([
            'FROM' => 'glpi_plugin_transferticketentity_entities_settings',
            'WHERE' => ['entities_id' => $ID],
        ])->current();
    }

    /**
     * If category belong to ancestor, return it
     *
     * @return array
     */
    public static function availableCategories($entity_id): array
    {
        global $DB;
        $allITILCategories = [];
        $entityNames = self::getEntityNames();

        $result = $DB->request([
            'FROM' => 'glpi_entities',
            'WHERE' => ['id' => $entity_id],
        ]);

        $ancestorsEntities = [];

        foreach ($result as $data) {
            if ($data['ancestors_cache']) {
                $ancestorsEntities = $data['ancestors_cache'];
                $ancestorsEntities = json_decode($ancestorsEntities, true);
                $ancestorsEntities[] = $entity_id;
            } else {
                $ancestorsEntities[] = 0;
            }
        }

        foreach ($ancestorsEntities as $ancestorEntity) {
            if ($ancestorEntity == $entity_id) {
                $result = $DB->request([
                    'SELECT' => ['id', 'name', 'completename', 'entities_id'],
                    'FROM' => 'glpi_itilcategories',
                    'WHERE' => ['entities_id' => $ancestorEntity],
                    'ORDER' => 'completename',
                ]);

            } else {
                $result = $DB->request([
                    'SELECT' => ['id', 'name', 'completename', 'entities_id'],
                    'FROM' => 'glpi_itilcategories',
                    'WHERE' => ['entities_id' => $ancestorEntity, 'is_recursive' => 1],
                    'ORDER' => 'completename',
                ]);

            }
            foreach ($result as $data) {
                $entityLabel = $entityNames[(int) $data['entities_id']] ?? ('#' . $data['entities_id']);
                $categoryLabel = $data['completename'] ?: $data['name'];
                $allITILCategories[$entityLabel][$data['id']] = $categoryLabel;
            }
        }

        return $allITILCategories;
    }

    /**
     * Return entity names for grouping dropdown options.
     */
    private static function getEntityNames(): array
    {
        global $DB;

        $entityNames = [];
        foreach ($DB->request([
            'SELECT' => ['id', 'name', 'completename'],
            'FROM' => 'glpi_entities',
        ]) as $entity) {
            $entityNames[(int) $entity['id']] = $entity['completename'] ?: $entity['name'];
        }

        return $entityNames;
    }

    /**
     * Groups available in the entity (own groups and recursive groups of its ancestors).
     *
     * @return array<int, array{id: int, name: string, entities_id: int}>
     */
    public static function getEntityGroups(int $entity_id): array
    {
        global $DB;

        $criteria = [
            'SELECT' => ['id', 'name', 'entities_id'],
            'FROM' => Group::getTable(),
            'ORDER' => 'name',
        ];
        $criteria = array_merge_recursive($criteria, getEntitiesRestrictCriteria(Group::getTable(), '', $entity_id, true));

        return iterator_to_array($DB->request($criteria), false);
    }

    /**
     * Return groups keyed by their owning entity for optgroup dropdowns.
     */
    private static function getEntityGroupsByEntity(int $entity_id): array
    {
        $entityNames = self::getEntityNames();
        $groupsByEntity = [];
        foreach (self::getEntityGroups($entity_id) as $group) {
            $entityLabel = $entityNames[(int) $group['entities_id']] ?? ('#' . $group['entities_id']);
            $groupsByEntity[$entityLabel][(int) $group['id']] = $group['name'];
        }

        ksort($groupsByEntity, SORT_NATURAL | SORT_FLAG_CASE);

        return $groupsByEntity;
    }

    /**
     * Display the ticket transfer form
     *
     * @return true
     */
    public static function showForEntity($entity_id): true
    {
        $checkRights = self::checkRights($entity_id);
        $availableCategories = self::availableCategories($entity_id);
        $availableGroups = self::getEntityGroupsByEntity((int) $entity_id);

        if (empty($checkRights)) {
            $checkRights['allow_entity_only_transfer'] = 0;
            $checkRights['justification_transfer'] = 0;
            $checkRights['allow_transfer'] = 0;
            $checkRights['keep_category'] = 0;
            $checkRights['itilcategories_id'] = null;
            $checkRights['groups_id'] = 0;
        }

        $entity = new GlpiEntity();
        $entity->getFromDB($entity_id);

        TemplateRenderer::getInstance()->display('@transferticketentity/entity.html.twig', [
            'item' => $entity,
            'form_path' => self::getFormURL(),
            'canedit' => $entity->can($entity_id, UPDATE),
            'checkRights' => $checkRights,
            'availableCategories' => $availableCategories,
            'availableGroups' => $availableGroups,
        ]);

        return true;
    }
}
