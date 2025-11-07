<?php
include('../../../inc/includes.php');

Session::checkRight("config", READ);

Html::header(
   __('Certbot Renew', 'certbotrenew'),
   $_SERVER['PHP_SELF'],
   'tools',
   'PluginCertbotrenewMenu'
);

echo "<div class='center'>";
echo "<h2>" . __('Gerenciamento do Certbot SSL', 'certbotrenew') . "</h2>";

echo "<form method='post'>";
echo "<button type='submit' name='install' class='btn btn-secondary' style='margin-right:10px;'>";
echo "<i class='fas fa-download'></i> " . __('Instalar Certbot', 'certbotrenew');
echo "</button>";

echo "<button type='submit' name='renew' class='btn btn-primary'>";
echo "<i class='fas fa-sync-alt'></i> " . __('Renovar agora', 'certbotrenew');
echo "</button>";
echo "</form><br>";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
   $output = [];
   $return_code = 0;

   if (isset($_POST['install'])) {
      echo "<h3><i class='fas fa-terminal'></i> " . __('Instalando Certbot...', 'certbotrenew') . "</h3>";
      exec('sudo apt update && sudo apt install -y certbot 2>&1', $output, $return_code);
   }

   if (isset($_POST['renew'])) {
      echo "<h3><i class='fas fa-terminal'></i> " . __('Renovando certificados SSL...', 'certbotrenew') . "</h3>";
      exec('sudo /usr/bin/certbot renew 2>&1', $output, $return_code);
   }

   if ($return_code === 0) {
      echo "<div class='alert alert-success'>";
      echo "<i class='fas fa-check-circle'></i> " . __('Operação concluída com sucesso!', 'certbotrenew');
      echo "</div>";
   } else {
      echo "<div class='alert alert-danger'>";
      echo "<i class='fas fa-exclamation-triangle'></i> " . __('Falha durante a execução do comando.', 'certbotrenew');
      echo "</div>";
   }

   echo "<pre style='text-align:left;background:#111;color:#0f0;padding:10px;border-radius:10px;overflow:auto;'>";
   foreach ($output as $line) {
      echo htmlspecialchars($line) . "\n";
   }
   echo "</pre>";
}
echo "</div>";

Html::footer();
