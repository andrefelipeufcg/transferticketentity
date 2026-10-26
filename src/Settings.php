<?php

namespace GlpiPlugin\Transferticketentity;

use CommonGLPI;
use Config;
use Glpi\Application\View\TemplateRenderer;
use Plugin;
use Profile as GlpiProfile;
use Session;

class Settings extends CommonGLPI
{
    public $taborientation = 'vertical';

    public static function getTypeName($nb = 0)
    {
        return __('Transfer Ticket Entity', 'transferticketentity');
    }

    public static function getIcon(): string
    {
        return 'ti ti-transfer';
    }

    public static function canView(): bool
    {
        return (bool) Session::haveRight('config', UPDATE);
    }

    public static function getFormURL($full = true)
    {
        global $CFG_GLPI;

        $path = Plugin::getPhpDir('transferticketentity', false) . '/front/config.form.php';

        return $full ? $CFG_GLPI['root_doc'] . $path : $path;
    }

    public function defineTabs($options = [])
    {
        $tabs = [];
        $this->addStandardTab(self::class, $tabs, $options);

        return $tabs;
    }

    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
    {
        if (!($item instanceof self) || !self::canView()) {
            return '';
        }

        return [
            1 => self::createTabEntry(__('Entities', 'transferticketentity'), 0, null, 'ti ti-building'),
            2 => self::createTabEntry(__('Profiles', 'transferticketentity'), 0, null, 'ti ti-shield-lock'),
            3 => self::createTabEntry(__('Groups', 'transferticketentity'), 0, null, 'ti ti-users-group'),
        ];
    }

    public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0)
    {
        if (!($item instanceof self) || !self::canView()) {
            return false;
        }

        $renderer = TemplateRenderer::getInstance();
        switch ((int) $tabnum) {
            case 1:
                $last = self::getLastApplied('entities');
                $assignedGroupId = self::getAssignedGroupId();
                $renderer->display('@transferticketentity/settings_entities.html.twig', [
                    'entities' => self::getEntityNames(),
                    'groups' => self::getGroupsByEntity(),
                    'categories' => self::getCategoriesByEntity(),
                    'assigned_group_id' => $assignedGroupId,
                    'last' => [
                        'entities' => array_map('intval', (array) ($last['entities'] ?? [])),
                        'allow_transfer' => (int) ($last['allow_transfer'] ?? 0),
                        'allow_entity_only_transfer' => (int) ($last['allow_entity_only_transfer'] ?? 0),
                        'groups_id' => (int) ($last['groups_id'] ?? 0) ?: $assignedGroupId,
                        'justification_transfer' => (int) ($last['justification_transfer'] ?? 0),
                        'keep_category' => (int) ($last['keep_category'] ?? 0),
                        'itilcategories_id' => (int) ($last['itilcategories_id'] ?? 0),
                    ],
                ]);
                return true;
            case 2:
                $last = self::getLastApplied('profiles');
                $lastRights = [];
                foreach (Profile::getAllRights() as $right) {
                    $lastRights[$right['field']] = (int) ($last['rights'][$right['field']] ?? 0);
                }
                $renderer->display('@transferticketentity/settings_profiles.html.twig', [
                    'profiles' => self::getProfileNames(),
                    'rights' => Profile::getAllRights(),
                    'last' => [
                        'profiles' => array_map('intval', (array) ($last['profiles'] ?? [])),
                        'rights' => $lastRights,
                    ],
                ]);
                return true;
            case 3:
                $groupId = self::getAssignedGroupId();
                $groupNames = self::getGroupsByEntity();
                $assignedGroupName = '';
                foreach ($groupNames as $groups) {
                    if (isset($groups[$groupId])) {
                        $assignedGroupName = $groups[$groupId];
                        break;
                    }
                }
                $renderer->display('@transferticketentity/settings_groups.html.twig', [
                    'groups' => $groupNames,
                    'assigned_group_id' => $groupId,
                    'assigned_group_name' => $assignedGroupName,
                    'last' => [
                        'groups' => array_map('intval', (array) (self::getLastApplied('groups')['groups'] ?? [])),
                    ],
                ]);
                return true;
        }

        return false;
    }

    /**
     * Values used the last time each tab was saved, so that every view of the tab shows them again.
     *
     * @return array<string, mixed>
     */
    public static function getLastApplied(string $kind): array
    {
        $raw = Config::getConfigurationValue('transferticketentity', 'last_applied_' . $kind);
        $data = is_string($raw) ? json_decode($raw, true) : null;

        return is_array($data) ? $data : [];
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function rememberApplied(string $kind, array $data): void
    {
        Config::setConfigurationValues('transferticketentity', [
            'last_applied_' . $kind => json_encode($data),
        ]);
    }

    /**
     * @return array<int, string>
     */
    public static function getEntityNames(): array
    {
        global $DB;

        $names = [];
        foreach ($DB->request([
            'SELECT' => ['id', 'name', 'completename'],
            'FROM' => 'glpi_entities',
            'ORDER' => 'completename',
        ]) as $entity) {
            $names[(int) $entity['id']] = $entity['completename'] ?: $entity['name'];
        }

        return $names;
    }

    /**
     * @return array<int, string>
     */
    private static function getProfileNames(): array
    {
        global $DB;

        $names = [];
        foreach ($DB->request([
            'SELECT' => ['id', 'name'],
            'FROM' => GlpiProfile::getTable(),
            'ORDER' => 'name',
        ]) as $profile) {
            $names[(int) $profile['id']] = $profile['name'];
        }

        return $names;
    }

    /**
     * @return array<string, array<int, string>> groups keyed by the name of their entity
     */
    private static function getGroupsByEntity(): array
    {
        global $DB;

        $entityNames = self::getEntityNames();
        $groups = [];
        foreach ($DB->request([
            'SELECT' => ['id', 'name', 'entities_id'],
            'FROM' => 'glpi_groups',
            'ORDER' => 'name',
        ]) as $group) {
            $label = $entityNames[(int) $group['entities_id']] ?? ('#' . $group['entities_id']);
            $groups[$label][(int) $group['id']] = $group['name'];
        }
        ksort($groups, SORT_NATURAL | SORT_FLAG_CASE);

        return $groups;
    }

    public static function getAssignedGroupId(): int
    {
        global $DB;

        $configured = Config::getConfigurationValue('transferticketentity', 'assigned_group_id');
        if ($configured !== null) {
            return (int) $configured;
        }

        $groupIds = [];
        foreach ($DB->request([
            'SELECT' => ['groups_id'],
            'DISTINCT' => true,
            'FROM' => 'glpi_plugin_transferticketentity_entities_settings',
            'WHERE' => ['groups_id' => ['>', 0]],
        ]) as $row) {
            $groupIds[] = (int) $row['groups_id'];
        }

        return count($groupIds) === 1 ? $groupIds[0] : 0;
    }

    /**
     * @return array<string, array<int, string>> categories keyed by the name of their entity
     */
    private static function getCategoriesByEntity(): array
    {
        global $DB;

        $entityNames = self::getEntityNames();
        $categories = [];
        foreach ($DB->request([
            'SELECT' => ['id', 'name', 'completename', 'entities_id'],
            'FROM' => 'glpi_itilcategories',
            'ORDER' => 'completename',
        ]) as $category) {
            $label = $entityNames[(int) $category['entities_id']] ?? ('#' . $category['entities_id']);
            $categories[$label][(int) $category['id']] = $category['completename'] ?: $category['name'];
        }
        ksort($categories, SORT_NATURAL | SORT_FLAG_CASE);

        return $categories;
    }
}
