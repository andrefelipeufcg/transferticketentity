<?php

namespace Glpi\Controller {
    class AbstractController {}
}

namespace Glpi\Http {
    class RedirectResponse extends \Symfony\Component\HttpFoundation\Response
    {
        public function __construct(public string $url) {}
    }
}

namespace Symfony\Component\HttpFoundation {
    class Response {}
    class ParameterBag
    {
        public function __construct(private array $values) {}
        public function getInt(string $key, int $default = 0): int { return (int) ($this->values[$key] ?? $default); }
        public function getString(string $key): string { return (string) ($this->values[$key] ?? ''); }
    }
    class Request
    {
        public ParameterBag $request;
        public function __construct(array $values) { $this->request = new ParameterBag($values); }
        public function getBasePath(): string { return ''; }
    }
}

namespace Glpi {
    class Event
    {
        public static function getTable(): string { return 'glpi_events'; }
    }
}

namespace GlpiPlugin\Transferticketentity {
    class Entity
    {
        public static function getEntityGroups(int $id): array { return [['id' => 7]]; }
        public static function getEntityCategories(int $id): array { return []; }
    }
}

namespace {
    use GlpiPlugin\Transferticketentity\Controller\TicketController;
    use Symfony\Component\HttpFoundation\Request;

    define('ALLSTANDARDRIGHT', 1);
    define('UPDATE', 2);
    define('ERROR', 3);
    define('INFO', 4);

    function __($message, $domain = null) { return $message; }
    function htmlescape($value) { return htmlspecialchars($value, ENT_QUOTES, 'UTF-8'); }

    class Session
    {
        public static array $messages = [];
        public static function checkRight(...$args): void {}
        public static function haveRight(...$args): bool { return true; }
        public static function getLoginUserID(): int { return 12; }
        public static function addMessageAfterRedirect($message, ...$args): void { self::$messages[] = $message; }
    }
    class Html { public static function getBackUrl(): string { return '/back'; } }
    class CommonITILObject { public const ASSIGNED = 2; public const INCOMING = 1; }
    class CommonITILActor { public const ASSIGN = 2; }
    class Planning { public const INFO = 0; }
    class SLM { public const TTO = 1; public const TTR = 2; }

    class FakeDB
    {
        public array $events = [];
        public array $ticketFields;
        public bool $failInsert = false;
        public bool $wrongEntity = false;
        public bool $failUpdate = false;
        public bool $requireGroup = false;
        private array $snapshot;
        public function __construct()
        {
            $this->ticketFields = [
                'id' => 42, 'entities_id' => 1, 'itilcategories_id' => 0,
                'slas_id_tto' => 0, 'slas_id_ttr' => 0, 'time_to_own' => null,
                'time_to_resolve' => null, 'slalevels_id_ttr' => 0, 'sla_waiting_duration' => 0,
                'type' => 1,
            ];
        }
        public function request(array $query): \ArrayIterator
        {
            return new \ArrayIterator([[
                'allow_transfer' => 1, 'allow_entity_only_transfer' => (int) $this->requireGroup,
                'groups_id' => 7, 'justification_transfer' => 0,
                'keep_category' => 1, 'itilcategories_id' => 0, 'keep_sla' => 1,
            ]]);
        }
        public function beginTransaction(): void { $this->snapshot = [$this->ticketFields, $this->events]; }
        public function commit(): void {}
        public function rollBack(): void { [$this->ticketFields, $this->events] = $this->snapshot; }
        public function insert($table, $values): bool
        {
            if ($this->failInsert) { return false; }
            if ($table !== 'glpi_events') { throw new \RuntimeException('Unexpected table'); }
            $this->events[] = $values;
            return true;
        }
    }

    class Entity
    {
        private int $id;
        public function getFromDB($id): bool { $this->id = $id; return in_array($id, [1, 2], true); }
        public function getId(): int { return $this->id; }
        public function getName($options = []): string { return $this->id === 1 ? 'Root > Source' : 'Root > Destination'; }
    }
    class Ticket extends CommonITILObject
    {
        public array $fields;
        public function check($id, $right): void { $this->getFromDB($id); }
        public function getFromDB($id): bool { global $DB; $this->fields = $DB->ticketFields; return true; }
        public function getId(): int { return $this->fields['id']; }
        public function getEntityID(): int { return $this->fields['entities_id']; }
        public function isClosed(): bool { return false; }
        public function update(array $input): bool
        {
            global $DB;
            if ($DB->failUpdate) { return false; }
            $DB->ticketFields['entities_id'] = $DB->wrongEntity ? 1 : $input['entities_id'];
            return true;
        }
        public function getITILTemplateToUse(...$args): object
        {
            return new class { public function isMandatoryField($field): bool { return false; } };
        }
    }
    class Ticket_User
    {
        public function find($criteria): array { return []; }
    }
    class Group
    {
        public function getFromDB($id): bool { return true; }
        public function getName(): string { return 'Default'; }
    }
    class Group_Ticket
    {
        private array $groups = [];
        public function find($criteria): array { return $this->groups; }
        public function add($input): bool { $this->groups[1] = ['groups_id' => $input['groups_id']]; return true; }
    }
    class TicketTask { public function add($input): bool { return true; } }

    require __DIR__ . '/../src/Controller/TicketController.php';

    function expect(bool $condition, string $message): void
    {
        if (!$condition) { throw new \RuntimeException($message); }
    }

    function transfer(array $values = []): void
    {
        (new TicketController())->transferTicket(new Request($values + [
            'ticket_id' => 42, 'entity_choice' => 2, 'category_choice' => 0, 'justification' => 'Necessário <teste>',
        ]));
    }

    $_SESSION = ['glpiname' => 'tech', 'glpi_currenttime' => '2026-11-06 12:00:00'];
    foreach ([false, true] as $requiredGroup) {
        $DB = new FakeDB();
        $DB->requireGroup = $requiredGroup;
        transfer();
        expect($DB->ticketFields['entities_id'] === 2, 'Ticket was not transferred');
        expect(count($DB->events) === 1, 'Expected exactly one event');
        $event = $DB->events[0];
        expect($event['items_id'] === 42 && $event['type'] === 'Ticket' && $event['service'] === 'tracking' && $event['level'] === 4, 'Incorrect event metadata');
        foreach (['tech (user ID 12)', 'ticket 42', 'Root > Source (entity ID 1)', 'Root > Destination (entity ID 2)', 'Necessário <teste>'] as $part) {
            expect(str_contains($event['message'], $part), 'Missing event detail: ' . $part);
        }
    }

    $DB = new FakeDB();
    transfer(['justification' => '']);
    expect(count($DB->events) === 1 && str_ends_with($DB->events[0]['message'], 'Justification: '), 'Empty justification was not logged');
    $baseLength = strlen($DB->events[0]['message']);

    $DB = new FakeDB();
    transfer(['justification' => "  first line\nsecond line  "]);
    expect(str_ends_with($DB->events[0]['message'], "  first line\nsecond line  "), 'Justification was not preserved');

    $DB = new FakeDB();
    transfer(['justification' => str_repeat('x', 65535 - $baseLength)]);
    expect(count($DB->events) === 1 && strlen($DB->events[0]['message']) === 65535, 'Maximum-size event was not accepted');

    $DB = new FakeDB();
    transfer(['justification' => str_repeat('x', 65536 - $baseLength)]);
    expect($DB->events === [] && $DB->ticketFields['entities_id'] === 1, 'Event over the limit was not rejected');

    foreach (['failUpdate', 'wrongEntity'] as $failure) {
        $DB = new FakeDB();
        $DB->$failure = true;
        transfer();
        expect($DB->ticketFields['entities_id'] === 1 && $DB->events === [], "$failure was not rolled back");
    }

    $DB = new FakeDB();
    $DB->failInsert = true;
    try {
        transfer();
        throw new \RuntimeException('Expected event insertion failure');
    } catch (\RuntimeException $e) {
        expect($e->getMessage() === 'Unable to record the ticket transfer event.', 'Unexpected error on event insertion');
    }
    expect($DB->ticketFields['entities_id'] === 1 && $DB->events === [], 'Event insertion failure did not roll back');

    $DB = new FakeDB();
    transfer(['justification' => str_repeat('é', 33000)]);
    expect($DB->ticketFields['entities_id'] === 1 && $DB->events === [], 'Oversized event was not rejected');
    expect(str_contains(end(Session::$messages), 'too long'), 'Oversized event error was not shown');

    echo "Transfer event tests passed.\n";
}
