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

// Função para executar comandos com shell_exec
function executeCommand($command) {
    $output = shell_exec($command . ' 2>&1');
    return $output;
}

// Função para obter o domínio do GLPI
function getGLPIDomain() {
    $url = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '';
    $domain = preg_replace('/:\d+$/', '', $url);
    
    if (empty($domain)) {
        $domain = 'localhost';
    }
    
    return $domain;
}

// Processar POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
   Session::checkValidSession();
   Session::checkCSRFToken();
   
   $action = '';
   $domain = getGLPIDomain();
   
   if (isset($_POST['install'])) {
      $action = 'install';
   } elseif (isset($_POST['renew'])) {
      $action = 'renew';
   } elseif (isset($_POST['status'])) {
      $action = 'status';
   }
}
?>

<div class="card" style="max-width:900px;margin:40px auto;padding:30px;text-align:center;">
   <h2><?= __('Gerenciamento do Certbot SSL', 'certbotrenew') ?></h2>
   <p><?= __('Escolha uma ação abaixo para instalar, renovar ou verificar o status dos certificados SSL.', 'certbotrenew') ?></p>

   <div class="alert alert-info" style="max-width:600px;margin:0 auto 20px;">
      <i class="fas fa-globe"></i> 
      <strong><?= __('Domínio detectado:', 'certbotrenew') ?></strong> 
      <code><?= htmlspecialchars(getGLPIDomain()) ?></code>
   </div>

   <form method="post" action="<?= $_SERVER['PHP_SELF'] ?>">
      <?php 
      echo Html::hidden('_glpi_csrf_token', [
         'value' => Session::getNewCSRFToken()
      ]); 
      ?>

      <div style="margin-top:25px;">
         <button type="submit" name="install" value="1" class="btn btn-secondary" style="margin-right:10px;">
            <i class="fas fa-download"></i> <?= __('Instalar Certbot', 'certbotrenew') ?>
         </button>

         <button type="submit" name="renew" value="1" class="btn btn-primary" style="margin-right:10px;">
            <i class="fas fa-sync-alt"></i> <?= __('Renovar agora', 'certbotrenew') ?>
         </button>

         <button type="submit" name="status" value="1" class="btn btn-info">
            <i class="fas fa-certificate"></i> <?= __('Ver status dos certificados', 'certbotrenew') ?>
         </button>
      </div>
   </form>

   <?php
   if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($action)) {
      
      echo "<div style='margin-top:30px;'>";
      
      switch ($action) {
         case 'install':
            echo "<h3><i class='fas fa-download'></i> " . __('Instalando Certbot...', 'certbotrenew') . "</h3>";
            
            // Testar comandos sem sudo primeiro
            $output1 = executeCommand('apt --version');
            $output2 = executeCommand('certbot --version');
            
            echo "<div class='alert alert-warning'>";
            echo "<strong>Teste de comandos:</strong><br>";
            echo "apt --version: " . (empty($output1) ? 'Não executou' : nl2br(htmlspecialchars($output1))) . "<br>";
            echo "certbot --version: " . (empty($output2) ? 'Não executou' : nl2br(htmlspecialchars($output2)));
            echo "</div>";
            
            // Tentar instalação
            $output = executeCommand('sudo apt update && sudo apt install -y certbot');
            
            if (!empty($output)) {
               echo "<div class='alert alert-success'><i class='fas fa-check-circle'></i> " . __('Comando executado!', 'certbotrenew') . "</div>";
            } else {
               echo "<div class='alert alert-danger'><i class='fas fa-exclamation-triangle'></i> " . __('Nenhuma saída do comando.', 'certbotrenew') . "</div>";
            }
            break;
            
         case 'renew':
            echo "<h3><i class='fas fa-sync-alt'></i> " . __('Renovando certificados SSL...', 'certbotrenew') . "</h3>";
            
            $output = executeCommand('sudo certbot renew --non-interactive');
            
            if (!empty($output)) {
               echo "<div class='alert alert-success'><i class='fas fa-check-circle'></i> " . __('Comando executado!', 'certbotrenew') . "</div>";
            } else {
               echo "<div class='alert alert-danger'><i class='fas fa-exclamation-triangle'></i> " . __('Nenhuma saída do comando.', 'certbotrenew') . "</div>";
            }
            break;
            
         case 'status':
            echo "<h3><i class='fas fa-search'></i> " . __('Verificando status dos certificados...', 'certbotrenew') . "</h3>";
            
            $output = executeCommand('sudo certbot certificates');
            
            if (!empty($output)) {
               echo "<div class='alert alert-success'><i class='fas fa-check-circle'></i> " . __('Comando executado!', 'certbotrenew') . "</div>";
            } else {
               echo "<div class='alert alert-danger'><i class='fas fa-exclamation-triangle'></i> " . __('Nenhuma saída do comando.', 'certbotrenew') . "</div>";
            }
            break;
      }

      // Exibir output do comando
      if (!empty($output)) {
         echo "<h4>Saída do comando:</h4>";
         echo "<pre style='text-align:left;background:#111;color:#0f0;padding:10px;border-radius:10px;overflow:auto;margin-top:20px;max-height:400px;'>";
         echo htmlspecialchars($output, ENT_QUOTES, 'UTF-8');
         echo "</pre>";
      } else {
         echo "<div class='alert alert-warning'>";
         echo "<strong>Debug info:</strong><br>";
         echo "Função shell_exec disponível: " . (function_exists('shell_exec') ? 'Sim' : 'Não') . "<br>";
         echo "Safe mode: " . (ini_get('safe_mode') ? 'Sim' : 'Não') . "<br>";
         echo "Disabled functions: " . ini_get('disable_functions');
         echo "</div>";
      }
      
      echo "</div>";
   }
   ?>
</div>

<?php
Html::footer();
?>
