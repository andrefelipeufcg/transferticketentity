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

$scriptVersion = hash_file('sha256', dirname(__DIR__) . '/public/js/pluginSettings.js');
if ($scriptVersion === false) {
    throw new \RuntimeException('Unable to read the plugin settings script.');
}

TemplateRenderer::getInstance()->display('@transferticketentity/settings.html.twig', [
    'ajax_url' => $CFG_GLPI['root_doc'] . Plugin::getPhpDir('transferticketentity', false) . '/front/config.ajax.php',
    'pluginSettingsScriptVersion' => $scriptVersion,
]);

(new Settings())->display(['id' => 1, 'show_nav_header' => false]);

Html::footer();
