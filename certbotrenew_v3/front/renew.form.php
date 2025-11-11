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

// Função para executar comandos de forma segura
function executeCommand($command) {
    $output = [];
    $return_code = 0;
    
    // Remove sudo para testar - ajuste conforme a permissão do usuário web
    $safe_command = str_replace('sudo ', '', $command);
    
    // Executa o comando
    exec($safe_command . ' 2>&1', $output, $return_code);
    
    return [
        'output' => $output,
        'return_code' => $return_code,
        'command' => $safe_command
    ];
}

// Função para obter o domínio do GLPI automaticamente
function getGLPIDomain() {
    $url = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '';
    
    // Remove porta se existir
    $domain = preg_replace('/:\d+$/', '', $url);
    
    // Se não conseguir detectar, usa um fallback
    if (empty($domain)) {
        $domain = 'localhost'; // Fallback seguro
    }
    
    return $domain;
}

// Função para verificar se o Certbot está instalado
function isCertbotInstalled() {
    $result = executeCommand('which certbot');
    return $result['return_code'] === 0;
}

// Função para obter informações do certificado
function getCertificateInfo($domain) {
    $result = executeCommand('/usr/bin/certbot certificates --domain ' . escapeshellarg($domain));
    return $result;
}

// Processar POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
   Session::checkValidSession();
   Session::checkCSRFToken();
}
?>

<div class="card" style="max-width:900px;margin:40px auto;padding:30px;text-align:center;">
   <h2><?= __('Gerenciamento do Certbot SSL', 'certbotrenew') ?></h2>
   <p><?= __('Escolha uma ação abaixo para instalar, renovar ou verificar o status dos certificados SSL.', 'certbotrenew') ?></p>

   <!-- Informações do domínio detectado -->
   <div class="alert alert-info" style="max-width:600px;margin:0 auto 20px;">
      <i class="fas fa-globe"></i> 
      <strong><?= __('Domínio detectado:', 'certbotrenew') ?></strong> 
      <code><?= htmlspecialchars(getGLPIDomain()) ?></code>
   </div>

   <form method="post" action="<?= $_SERVER['PHP_SELF'] ?>">
      <?php 
      // Gerar token CSRF para o formulário
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
   // Exibir resultados apenas se uma ação foi executada via POST
   if ($_SERVER['REQUEST_METHOD'] === 'POST') {
      
      $action = '';
      $domain = getGLPIDomain();
      
      if (isset($_POST['install'])) {
         $action = 'install';
      } elseif (isset($_POST['renew'])) {
         $action = 'renew';
      } elseif (isset($_POST['status'])) {
         $action = 'status';
      }

      echo "<div style='margin-top:30px;'>";
      
      switch ($action) {
         case 'install':
            echo "<h3><i class='fas fa-download'></i> " . __('Instalando Certbot...', 'certbotrenew') . "</h3>";
            
            if (isCertbotInstalled()) {
               echo "<div class='alert alert-warning'><i class='fas fa-info-circle'></i> " . __('Certbot já está instalado no sistema.', 'certbotrenew') . "</div>";
            } else {
               // Instalar Certbot
               $update_result = executeCommand('/usr/bin/apt update');
               $install_result = executeCommand('/usr/bin/apt install -y certbot');
               
               $output = array_merge($update_result['output'], $install_result['output']);
               $return_code = $install_result['return_code'];
               
               echo "<div class='alert alert-info'><strong>Comando executado:</strong> " . htmlspecialchars($install_result['command']) . "</div>";
               
               if ($return_code === 0) {
                  echo "<div class='alert alert-success'><i class='fas fa-check-circle'></i> " . __('Certbot instalado com sucesso!', 'certbotrenew') . "</div>";
               } else {
                  echo "<div class='alert alert-danger'><i class='fas fa-exclamation-triangle'></i> " . __('Falha na instalação do Certbot.', 'certbotrenew') . "</div>";
               }
            }
            break;
            
         case 'renew':
            echo "<h3><i class='fas fa-sync-alt'></i> " . __('Renovando certificados SSL...', 'certbotrenew') . "</h3>";
            
            if (!isCertbotInstalled()) {
               echo "<div class='alert alert-danger'><i class='fas fa-exclamation-triangle'></i> " . __('Certbot não está instalado. Use o botão "Instalar Certbot" primeiro.', 'certbotrenew') . "</div>";
            } else {
               // Verificar se o certificado existe para o domínio
               $cert_info = getCertificateInfo($domain);
               
               if (!$cert_info['success']) {
                  echo "<div class='alert alert-warning'><i class='fas fa-info-circle'></i> " . __('Certificado não encontrado para este domínio. Será necessário criar um novo certificado.', 'certbotrenew') . "</div>";
                  
                  // Comando para obter certificado (versão simplificada)
                  echo "<p>" . __('Executando comando para obter certificado...', 'certbotrenew') . "</p>";
                  $result = executeCommand('/usr/bin/certbot certonly --standalone -d ' . escapeshellarg($domain) . ' --non-interactive --agree-tos --email admin@' . $domain);
                  $output = $result['output'];
                  $return_code = $result['return_code'];
                  
                  echo "<div class='alert alert-info'><strong>Comando executado:</strong> " . htmlspecialchars($result['command']) . "</div>";
               } else {
                  // Renovar certificados existentes
                  $result = executeCommand('/usr/bin/certbot renew --non-interactive');
                  $output = $result['output'];
                  $return_code = $result['return_code'];
                  
                  echo "<div class='alert alert-info'><strong>Comando executado:</strong> " . htmlspecialchars($result['command']) . "</div>";
               }
               
               if ($return_code === 0) {
                  echo "<div class='alert alert-success'><i class='fas fa-check-circle'></i> " . __('Certificados renovados/obtidos com sucesso!', 'certbotrenew') . "</div>";
               }
            }
            break;
            
         case 'status':
            echo "<h3><i class='fas fa-search'></i> " . __('Verificando status dos certificados...', 'certbotrenew') . "</h3>";
            
            if (!isCertbotInstalled()) {
               echo "<div class='alert alert-danger'><i class='fas fa-exclamation-triangle'></i> " . __('Certbot não está instalado. Use o botão "Instalar Certbot" primeiro.', 'certbotrenew') . "</div>";
            } else {
               // Listar todos os certificados
               $result = executeCommand('/usr/bin/certbot certificates');
               $output = $result['output'];
               $return_code = $result['return_code'];
               
               echo "<div class='alert alert-info'><strong>Comando executado:</strong> " . htmlspecialchars($result['command']) . "</div>";
               
               if ($return_code === 0) {
                  echo "<div class='alert alert-success'><i class='fas fa-check-circle'></i> " . __('Status dos certificados obtido com sucesso!', 'certbotrenew') . "</div>";
               }
            }
            break;
            
         default:
            echo "<div class='alert alert-warning'>Nenhuma ação reconhecida.</div>";
            break;
      }

      // Exibir output sempre que houver
      if (isset($output) && !empty($output)) {
         echo "<h4>" . __('Saída do comando:', 'certbotrenew') . "</h4>";
         echo "<pre style='text-align:left;background:#111;color:#0f0;padding:10px;border-radius:10px;overflow:auto;margin-top:20px;max-height:400px;'>";
         foreach ($output as $line) {
            echo htmlspecialchars($line, ENT_QUOTES, 'UTF-8') . "\n";
         }
         echo "</pre>";
      } elseif (isset($action)) {
         echo "<div class='alert alert-info'>Nenhuma saída foi gerada pelo comando.</div>";
      }
      
      echo "</div>";
   }
   ?>
</div>

<?php
Html::footer();
?>
