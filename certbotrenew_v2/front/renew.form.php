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
echo "<h2>" . __('Renovação de Certificados SSL', 'certbotrenew') . "</h2>";
echo "<form method='post'>";
echo "<button type='submit' class='btn btn-primary'>";
echo "<i class='fas fa-sync-alt'></i> " . __('Renovar agora', 'certbotrenew');
echo "</button>";
echo "</form><br>";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
   $output = [];
   exec('sudo /usr/bin/certbot renew 2>&1', $output, $return_code);

   if ($return_code === 0) {
      echo "<div class='alert alert-success'>";
      echo "<i class='fas fa-check-circle'></i> " . __('Renovação concluída com sucesso!', 'certbotrenew');
      echo "</div>";
   } else {
      echo "<div class='alert alert-danger'>";
      echo "<i class='fas fa-exclamation-triangle'></i> " . __('Erro ao renovar o certificado.', 'certbotrenew');
      echo "</div>";
   }

   echo "<pre style='text-align:left;background:#111;color:#0f0;padding:10px;border-radius:10px;overflow:auto;'>";
   echo htmlspecialchars(implode("\\n", $output));
   echo "</pre>";
}
echo "</div>";

Html::footer();
