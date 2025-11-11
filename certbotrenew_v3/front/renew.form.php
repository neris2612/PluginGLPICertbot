<?php
include('../../../inc/includes.php');

header('Content-Type: text/html; charset=UTF-8');
Session::checkRight("config", UPDATE);

Html::header(
   __('Certbot Renew', 'certbotrenew'),
   $_SERVER['PHP_SELF'],
   'tools',
   'PluginCertbotrenewMenu'
);
?>

<div class="card" style="max-width:700px;margin:40px auto;padding:30px;text-align:center;">
   <h2><?= __('Gerenciamento do Certbot SSL', 'certbotrenew') ?></h2>
   <p><?= __('Escolha uma ação abaixo para instalar, renovar ou verificar o status dos certificados SSL.', 'certbotrenew') ?></p>

   <form method="post" action="">
      

      <div style="margin-top:25px;">
         <button type="submit" name="install" class="btn btn-secondary" style="margin-right:10px;">
            <i class="fas fa-download"></i> <?= __('Instalar Certbot', 'certbotrenew') ?>
         </button>

         <button type="submit" name="renew" class="btn btn-primary" style="margin-right:10px;">
            <i class="fas fa-sync-alt"></i> <?= __('Renovar agora', 'certbotrenew') ?>
         </button>

         <button type="submit" name="status" class="btn btn-info">
            <i class="fas fa-certificate"></i> <?= __('Ver status dos certificados', 'certbotrenew') ?>
         </button>
      </div>
   </form>

   <?php
   if ($_SERVER['REQUEST_METHOD'] === 'POST') {
      Session::checkValidSession();
      Session::checkCSRFToken();

      $output = [];
      $return_code = 0;

      if (isset($_POST['install'])) {
         echo "<h3 style='margin-top:30px;'><i class='fas fa-terminal'></i> Instalando Certbot...</h3>";
         exec('sudo /usr/bin/apt update && sudo /usr/bin/apt install -y certbot 2>&1', $output, $return_code);
      }

      if (isset($_POST['renew'])) {
         echo "<h3 style='margin-top:30px;'><i class='fas fa-terminal'></i> Renovando certificados SSL...</h3>";
         exec('sudo /usr/bin/certbot renew 2>&1', $output, $return_code);
      }

      if (isset($_POST['status'])) {
         echo "<h3 style='margin-top:30px;'><i class='fas fa-search'></i> Verificando status dos certificados...</h3>";
         exec('sudo /usr/bin/certbot certificates 2>&1', $output, $return_code);
      }

      if ($return_code === 0) {
         echo "<div class='alert alert-success' style='margin-top:15px;'><i class='fas fa-check-circle'></i> Operação concluída com sucesso!</div>";
      } else {
         echo "<div class='alert alert-danger' style='margin-top:15px;'><i class='fas fa-exclamation-triangle'></i> Falha durante a execução do comando.</div>";
      }

      echo "<pre style='text-align:left;background:#111;color:#0f0;padding:10px;border-radius:10px;overflow:auto;margin-top:20px;max-height:400px;'>";
      foreach ($output as $line) {
         echo htmlspecialchars($line, ENT_QUOTES, 'UTF-8') . "\n";
      }
      echo "</pre>";
   }
   ?>
</div>

<?php
Html::footer();
?>
