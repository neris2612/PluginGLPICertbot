<?php
function plugin_init_certbotrenew() {
   global $PLUGIN_HOOKS;

   $PLUGIN_HOOKS['csrf_compliant']['certbotrenew'] = true;
   $PLUGIN_HOOKS['menu_toadd']['certbotrenew'] = ['tools' => 'PluginCertbotrenewMenu'];
}

function plugin_version_certbotrenew() {
   return [
      'name'           => 'Certbot Renew',
      'version'        => '2.0.0',
      'author'         => 'Matheus Neris',
      'license'        => 'GPLv2+',
      'homepage'       => 'https://github.com/MatheusNeris',
      'minGlpiVersion' => '10.0.0'
   ];
}

function plugin_certbotrenew_check_prerequisites() {
   if (version_compare(GLPI_VERSION, '10.0.0', 'lt')) {
      echo "Este plugin requer o GLPI 10.0.0 ou superior.";
      return false;
   }
   return true;
}

function plugin_certbotrenew_check_config() {
   return true;
}
