<?php

use GlpiPlugin\Transferticketentity\Entity as TransferEntity;
use GlpiPlugin\Transferticketentity\Profile as TransferProfile;
use GlpiPlugin\Transferticketentity\Settings;

// POST requests are CSRF-checked by GLPI itself (X-Glpi-Csrf-Token header sent by its AJAX layer).
Session::checkRight('config', UPDATE);

header('Content-Type: application/json; charset=UTF-8');
$transactionStarted = false;

$action = $_SERVER['REQUEST_METHOD'] === 'POST'
    ? ($_POST['action'] ?? '')
    : ($_GET['action'] ?? '');

try {
    switch ($action) {
        case 'save_entities':
            tteSaveEntitySettings();
            echo json_encode([
                'message' => __('Entity settings successfully updated.', 'transferticketentity'),
                'assigned_group_id' => Settings::getAssignedGroupId(),
            ]);
            break;
        case 'save_profiles':
            tteSaveProfileSettings();
            echo json_encode(['message' => __('Profile settings successfully updated.', 'transferticketentity')]);
            break;
        case 'save_groups':
            tteSaveGroupMemberships();
            echo json_encode(['message' => __('Group memberships successfully updated.', 'transferticketentity')]);
            break;
        default:
            http_response_code(400);
            echo json_encode(['message' => __('Invalid settings action.', 'transferticketentity')]);
    }
} catch (InvalidArgumentException $e) {
    tteRollBack();
    http_response_code(400);
    echo json_encode(['message' => $e->getMessage()]);
} catch (Throwable $e) {
    tteRollBack();
    error_log('[transferticketentity] ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['message' => __('The operation failed. No changes were applied.', 'transferticketentity')]);
}

function tteRollBack(): void
{
    global $DB, $transactionStarted;

    if (isset($DB) && $transactionStarted) {
        $DB->rollBack();
        $transactionStarted = false;
    }
}

function tteSaveEntitySettings(): void
{
    global $DB, $transactionStarted;

    $entityIds = tteValidatedIds($_POST['entities_id'] ?? [], '', 0, true);
    $allowTransfer = tteReadBoolean('allow_transfer');
    $groupRequired = $allowTransfer && tteReadBoolean('allow_entity_only_transfer');
    $justification = $allowTransfer && tteReadBoolean('justification_transfer');
    $keepCategory = $allowTransfer && tteReadBoolean('keep_category');
    $submittedGroupId = tteReadNonNegativeId($_POST['groups_id'] ?? '0');
    $submittedCategoryId = tteReadNonNegativeId($_POST['itilcategories_id'] ?? '0');
    $groupsId = $groupRequired ? $submittedGroupId : 0;
    $categoryId = $allowTransfer && !$keepCategory ? $submittedCategoryId : 0;

    if ($groupRequired && $groupsId <= 0) {
        throw new InvalidArgumentException(__('Select an assigned group before saving.', 'transferticketentity'));
    }

    $entityNames = Settings::getEntityNames();
    $groupUnavailable = [];
    $categoryUnavailable = [];
    foreach ($entityIds as $entityId) {
        if (!isset($entityNames[$entityId])) {
            throw new InvalidArgumentException(__('One or more selected entities do not exist.', 'transferticketentity'));
        }

        if ($groupRequired) {
            $availableGroups = array_map('intval', array_column(TransferEntity::getEntityGroups($entityId), 'id'));
            if (!in_array($groupsId, $availableGroups, true)) {
                $groupUnavailable[] = $entityNames[$entityId];
            }
        }

        if ($categoryId > 0 && !tteCategoryIsAvailable($entityId, $categoryId)) {
            $categoryUnavailable[] = $entityNames[$entityId];
        }
    }
    if ($groupUnavailable !== []) {
        throw new InvalidArgumentException(sprintf(
            __('The assigned group must be available in every selected entity. Unavailable in: %s', 'transferticketentity'),
            tteListNames($groupUnavailable)
        ));
    }
    if ($categoryUnavailable !== []) {
        throw new InvalidArgumentException(sprintf(
            __('The default category must be available in every selected entity. Unavailable in: %s', 'transferticketentity'),
            tteListNames($categoryUnavailable)
        ));
    }

    $DB->beginTransaction();
    $transactionStarted = true;
    foreach ($entityIds as $entityId) {
        $DB->updateOrInsert(
            'glpi_plugin_transferticketentity_entities_settings',
            [
                'allow_entity_only_transfer' => $groupRequired,
                'justification_transfer' => $justification,
                'allow_transfer' => $allowTransfer,
                'keep_category' => $keepCategory,
                'itilcategories_id' => $categoryId > 0 ? $categoryId : null,
                'groups_id' => $groupsId,
            ],
            ['entities_id' => $entityId]
        );
    }
    // Disable transfer for unchecked entities that have existing settings
    $uncheckedEntities = array_diff(array_keys($entityNames), $entityIds);
    if ($uncheckedEntities !== []) {
        $DB->update(
            'glpi_plugin_transferticketentity_entities_settings',
            [
                'allow_transfer' => 0,
                'allow_entity_only_transfer' => 0,
                'justification_transfer' => 0,
                'keep_category' => 0,
                'itilcategories_id' => null,
                'groups_id' => 0,
            ],
            ['entities_id' => $uncheckedEntities]
        );
    }
    if ($groupsId > 0) {
        Config::setConfigurationValues('transferticketentity', [
            'assigned_group_id' => $groupsId,
        ]);
    }
    Settings::rememberApplied('entities', [
        'entities' => $entityIds,
        'allow_transfer' => (int) $allowTransfer,
        'allow_entity_only_transfer' => (int) $groupRequired,
        'groups_id' => $groupsId,
        'justification_transfer' => (int) $justification,
        'keep_category' => (int) $keepCategory,
        'itilcategories_id' => $categoryId,
    ]);
    $DB->commit();
    $transactionStarted = false;
}

function tteListNames(array $names): string
{
    $shown = array_slice($names, 0, 5);
    $list = implode(', ', $shown);
    if (count($names) > count($shown)) {
        $list .= ' (+' . (count($names) - count($shown)) . ')';
    }

    return $list;
}

function tteCategoryIsAvailable(int $entityId, int $categoryId): bool
{
    foreach (TransferEntity::availableCategories($entityId) as $categories) {
        if (array_key_exists($categoryId, $categories)) {
            return true;
        }
    }
    return false;
}

function tteSaveProfileSettings(): void
{
    global $DB, $transactionStarted;

    $profileIds = tteValidatedIds($_POST['profiles_id'] ?? [], '', 1, true);
    $rights = [];
    foreach (TransferProfile::getAllRights() as $right) {
        $rights[$right['field']] = tteReadBoolean($right['field'])
            ? key($right['rights'])
            : 0;
    }

    if ($profileIds !== []) {
        $existingProfiles = [];
        foreach ($DB->request([
            'SELECT' => ['id'],
            'FROM' => 'glpi_profiles',
            'WHERE' => ['id' => $profileIds],
        ]) as $profile) {
            $existingProfiles[(int) $profile['id']] = true;
        }
        if (count($existingProfiles) !== count($profileIds)) {
            throw new InvalidArgumentException(__('One or more selected profiles do not exist.', 'transferticketentity'));
        }
    }

    $DB->beginTransaction();
    $transactionStarted = true;
    foreach ($profileIds as $profileId) {
        ProfileRight::updateProfileRights($profileId, $rights);
        $storedRights = ProfileRight::getProfileRights($profileId, array_keys($rights));
        foreach ($rights as $name => $value) {
            if ((int) ($storedRights[$name] ?? -1) !== (int) $value) {
                throw new RuntimeException(__('Profile permissions could not be saved.', 'transferticketentity'));
            }
        }
    }
    // Disable permissions for unchecked profiles
    $allProfileIds = array_keys(Settings::getProfileNames());
    $uncheckedProfileIds = array_diff($allProfileIds, $profileIds);
    if ($uncheckedProfileIds !== []) {
        $disabledRights = [];
        foreach (TransferProfile::getAllRights() as $right) {
            $disabledRights[$right['field']] = 0;
        }
        foreach ($uncheckedProfileIds as $pid) {
            ProfileRight::updateProfileRights($pid, $disabledRights);
        }
    }
    Settings::rememberApplied('profiles', [
        'profiles' => $profileIds,
        'rights' => array_map(static fn($value) => (int) ($value > 0), $rights),
    ]);
    $DB->commit();
    $transactionStarted = false;
}

function tteSaveGroupMemberships(): void
{
    global $DB, $transactionStarted;

    $targetGroupId = Settings::getAssignedGroupId();
    if ($targetGroupId <= 0) {
        throw new InvalidArgumentException(__('Choose the assigned group in the Entities tab before saving.', 'transferticketentity'));
    }
    $groupIds = tteValidatedIds($_POST['groups_id'] ?? [], __('Select at least one source group.', 'transferticketentity'));

    $targetGroup = new Group();
    if (!$targetGroup->getFromDB($targetGroupId)) {
        throw new InvalidArgumentException(__('The configured assigned group no longer exists. Update it in the Entities tab.', 'transferticketentity'));
    }
    if (!$targetGroup->can($targetGroupId, UPDATE)) {
        throw new InvalidArgumentException(__('You do not have permission to update the configured assigned group.', 'transferticketentity'));
    }

    $existingSourceGroups = [];
    foreach ($DB->request([
        'SELECT' => ['id'],
        'FROM' => 'glpi_groups',
        'WHERE' => ['id' => $groupIds],
    ]) as $group) {
        $existingSourceGroups[(int) $group['id']] = true;
    }
    if (count($existingSourceGroups) !== count($groupIds)) {
        throw new InvalidArgumentException(__('One or more selected groups do not exist.', 'transferticketentity'));
    }

    $members = [];
    foreach ($DB->request([
        'SELECT' => ['users_id'],
        'FROM' => 'glpi_groups_users',
        'WHERE' => ['groups_id' => $groupIds],
    ]) as $member) {
        $members[(int) $member['users_id']] = true;
    }

    $existingMembers = [];
    foreach ($DB->request([
        'SELECT' => ['users_id'],
        'FROM' => 'glpi_groups_users',
        'WHERE' => ['groups_id' => $targetGroupId],
    ]) as $member) {
        $existingMembers[(int) $member['users_id']] = true;
    }

    $DB->beginTransaction();
    $transactionStarted = true;
    $groupUser = new Group_User();
    foreach (array_keys($members) as $userId) {
        if (isset($existingMembers[$userId])) {
            continue;
        }

        if (!$groupUser->add([
            'groups_id' => $targetGroupId,
            'users_id' => $userId,
        ])) {
            throw new RuntimeException(__('A user could not be added to the configured assigned group.', 'transferticketentity'));
        }
    }
    Settings::rememberApplied('groups', ['groups' => $groupIds]);
    $DB->commit();
    $transactionStarted = false;
}

function tteValidatedIds($input, string $emptyMessage, int $minimum = 1, bool $allowEmpty = false): array
{
    if (!is_array($input) || $input === []) {
        if ($allowEmpty) {
            return [];
        }
        throw new InvalidArgumentException($emptyMessage);
    }

    $ids = [];
    foreach ($input as $value) {
        $id = filter_var($value, FILTER_VALIDATE_INT);
        if ($id === false || $id < $minimum) {
            throw new InvalidArgumentException(__('Invalid selection.', 'transferticketentity'));
        }
        $ids[$id] = $id;
    }

    return array_values($ids);
}

function tteReadBoolean(string $field): bool
{
    $value = $_POST[$field] ?? '0';
    if (!in_array($value, ['0', '1'], true)) {
        throw new InvalidArgumentException(__('Invalid selection.', 'transferticketentity'));
    }
    return $value === '1';
}

function tteReadNonNegativeId($value): int
{
    $id = filter_var($value, FILTER_VALIDATE_INT);
    if ($id === false || $id < 0) {
        throw new InvalidArgumentException(__('Invalid selection.', 'transferticketentity'));
    }
    return $id;
}
