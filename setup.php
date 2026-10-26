<?php

use GlpiPlugin\Transferticketentity\Entity as TransferTicketEntityEntity;
use GlpiPlugin\Transferticketentity\Profile as TransferTicketEntityProfile;
use GlpiPlugin\Transferticketentity\Ticket as TransferTicketEntityTicket;

define('PLUGIN_TRANSFERTICKETENTITY_VERSION', '1.3.1');
define('PLUGIN_TRANSFERTICKETENTITY_MIN_GLPI', '11.0.0');
define('PLUGIN_TRANSFERTICKETENTITY_MAX_GLPI', '12.0.99');

function plugin_init_transferticketentity()
{
    global $PLUGIN_HOOKS;

    // Add a tab for profiles and tickets
    Plugin::registerClass(TransferTicketEntityProfile::class, ['addtabon' => 'Profile']);
    Plugin::registerClass(TransferTicketEntityTicket::class, ['addtabon' => 'Ticket']);
    Plugin::registerClass(TransferTicketEntityEntity::class, ['addtabon' => 'Entity']);

    $PLUGIN_HOOKS['csrf_compliant']['transferticketentity'] = true;
}

function plugin_version_transferticketentity()
{
    return [
        'name'           => 'TransferTicketEntity',
        'version'        => PLUGIN_TRANSFERTICKETENTITY_VERSION,
        'author'         => 'Yannick COMBA',
        'license'        => 'GPLv3+',
        'homepage'       => 'https://github.com/departement-maine-et-loire/',
        'requirements'   => [
            'glpi'   => [
                'min' => PLUGIN_TRANSFERTICKETENTITY_MIN_GLPI,
                'max' => PLUGIN_TRANSFERTICKETENTITY_MAX_GLPI,
            ],
        ],
    ];
}

function plugin_transferticketentity_check_prerequisites()
{
    return true;
}

function plugin_transferticketentity_check_config($verbose = false)
{
    if ($verbose) {
        echo __s('Installed / not configured', 'transferticketentity');
    }
    return true;
}

function plugin_transferticketentity_options()
{
    return [
        Plugin::OPTION_AUTOINSTALL_DISABLED => true,
    ];
}
