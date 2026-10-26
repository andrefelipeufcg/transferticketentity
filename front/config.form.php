<?php

use Glpi\Application\View\TemplateRenderer;
use GlpiPlugin\Transferticketentity\Settings;

Session::checkRight('config', UPDATE);

$plugin = new Plugin();
if (!$plugin->isActivated('transferticketentity')) {
    Html::displayNotFoundError();
}

Html::header(
    Settings::getTypeName(),
    '',
    'config',
    'plugins'
);

global $CFG_GLPI;

TemplateRenderer::getInstance()->display('@transferticketentity/settings.html.twig', [
    'ajax_url' => $CFG_GLPI['root_doc'] . Plugin::getPhpDir('transferticketentity', false) . '/front/config.ajax.php',
]);

(new Settings())->display(['id' => 1, 'show_nav_header' => false]);

Html::footer();
