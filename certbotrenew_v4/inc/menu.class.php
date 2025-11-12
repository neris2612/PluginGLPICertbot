<?php
class PluginCertbotrenewMenu extends CommonGLPI {
   static function getMenuName() {
      return __('Certbot Manager', 'certbotrenew');
   }

   static function getMenuContent() {
      $menu = [];
      $menu['title'] = self::getMenuName();
      $menu['page']  = '/plugins/certbotrenew/front/renew.form.php';
      $menu['icon']  = 'fas fa-lock';
      return $menu;
   }
}
