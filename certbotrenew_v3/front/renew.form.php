<?php
include('../../../inc/includes.php');

// Define o caminho do log do Certbot. Pode ser ajustado se necessário.
// Nota: O caminho pode variar dependendo da instalação do Certbot.
define('CERTBOT_LOG_PATH', '/var/log/letsencrypt/letsencrypt.log');

Session::checkRight("config", READ);

Html::header(
   __('Certbot Renew', 'certbotrenew'),
   $_SERVER['PHP_SELF'],
   'tools',
   'PluginCertbotrenewMenu'
);

echo "<div class='center'>";
echo "<h2>" . __('Gerenciamento de Certificados SSL (Certbot)', 'certbotrenew') . "</h2>";

// --- Formulário de Ações ---
// Usamos um único formulário com botões que enviam o valor da 'action'
echo "<form method='post' class='d-flex justify-content-center gap-3'>";

// Botão 1: Renovar agora
echo "<button type='submit' name='action' value='renew' class='btn btn-primary'>";
echo "<i class='fas fa-sync-alt'></i> " . __('Renovar agora', 'certbotrenew');
echo "</button>";

// Botão 2: Status do Certbot
echo "<button type='submit' name='action' value='status' class='btn btn-info'>";
echo "<i class='fas fa-certificate'></i> " . __('Status do Certbot', 'certbotrenew');
echo "</button>";

// Botão 3: Ver Log de Renovação
echo "<button type='submit' name='action' value='log' class='btn btn-secondary'>";
echo "<i class='fas fa-file-alt'></i> " . __('Ver Log de Renovação', 'certbotrenew');
echo "</button>";

echo "</form><br>";

// --- Lógica de Processamento ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
   $action = $_POST['action'];
   $output = [];
   $return_code = 0;
   $display_output = true;
   $title = '';

   switch ($action) {
      case 'renew':
         $title = __('Executando Renovação do Certbot...', 'certbotrenew');
         // A função exec retorna a saída em $output e o código de retorno em $return_code
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
         break;

      case 'status':
         $title = __('Status dos Certificados Instalados', 'certbotrenew');
         exec('sudo /usr/bin/certbot certificates 2>&1', $output, $return_code);

         if ($return_code !== 0) {
            echo "<div class='alert alert-danger'>";
            echo "<i class='fas fa-exclamation-triangle'></i> " . __('Erro ao obter o status dos certificados.', 'certbotrenew');
            echo "</div>";
         }
         break;

      case 'log':
         $title = __('Conteúdo do Log de Renovação', 'certbotrenew');
         $display_output = false; // Não é um comando, vamos ler o arquivo

         if (file_exists(CERTBOT_LOG_PATH)) {
            // Usamos file_get_contents para ler o arquivo de log
            $log_content = file_get_contents(CERTBOT_LOG_PATH);
            echo "<h3>" . $title . "</h3>";
            echo "<pre style='text-align:left;background:#111;color:#fff;padding:10px;border-radius:10px;overflow:auto;max-height:500px;'>";
            // Usamos htmlspecialchars para garantir que o conteúdo do log seja exibido corretamente
            echo htmlspecialchars($log_content);
            echo "</pre>";
         } else {
            echo "<div class='alert alert-warning'>";
            echo "<i class='fas fa-exclamation-triangle'></i> " . __('Arquivo de log não encontrado em: ', 'certbotrenew') . CERTBOT_LOG_PATH;
            echo "</div>";
         }
         break;

      default:
         // Ação desconhecida
         $display_output = false;
         break;
   }

   // Exibe a saída do comando (para 'renew' e 'status')
   if ($display_output && !empty($output)) {
      echo "<h3>" . $title . "</h3>";
      echo "<pre style='text-align:left;background:#111;color:#0f0;padding:10px;border-radius:10px;overflow:auto;max-height:500px;'>";
      // Usamos implode("\n", $output) para juntar as linhas da saída do comando
      echo htmlspecialchars(implode("\n", $output));
      echo "</pre>";
   }
}
echo "</div>";

Html::footer();
