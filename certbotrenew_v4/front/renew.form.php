<?php
include('../../../inc/includes.php');

// Define o caminho do log do Certbot
define('CERTBOT_LOG_PATH', '/var/log/letsencrypt/letsencrypt.log');

// --- BACKEND (responde ao AJAX) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax'])) {
    // Verifica permissão para requisições AJAX
    Session::checkLoginUser();
    Session::checkRight("config", READ);
    
    // Limpa qualquer output anterior
    if (ob_get_length()) ob_clean();
    
    header('Content-Type: application/json; charset=UTF-8');
    
    try {
        $action = $_POST['action'] ?? '';
        $output = [];
        $return_code = 0;

        switch ($action) {
            case 'renew':
                exec('sudo /usr/bin/certbot renew 2>&1', $output, $return_code);
                break;

            case 'status':
                exec('sudo /usr/bin/certbot certificates 2>&1', $output, $return_code);
                break;

            case 'log':
                if (file_exists(CERTBOT_LOG_PATH)) {
                    // Verifica se o arquivo é legível
                    if (!is_readable(CERTBOT_LOG_PATH)) {
                        // Tenta ler com sudo
                        exec('sudo cat ' . escapeshellarg(CERTBOT_LOG_PATH) . ' 2>&1', $output, $return_code);
                        if ($return_code !== 0) {
                            throw new Exception("Arquivo de log não pode ser lido. Verifique as permissões: " . CERTBOT_LOG_PATH);
                        }
                    } else {
                        $logContent = file_get_contents(CERTBOT_LOG_PATH);
                        if ($logContent === false) {
                            throw new Exception("Falha ao ler o arquivo de log: " . CERTBOT_LOG_PATH);
                        }
                        $output = explode("\n", $logContent);
                    }
                    
                    // Se o log estiver vazio, mostra uma mensagem
                    if (empty($output) || (count($output) === 1 && empty($output[0]))) {
                        $output = ["Log vazio ou sem conteúdo."];
                    }
                } else {
                    throw new Exception("Arquivo de log não encontrado: " . CERTBOT_LOG_PATH);
                }
                break;

            default:
                throw new Exception("Ação inválida: $action");
        }

        $response = [
            'error' => false,
            'action' => $action,
            'output' => $output,
            'code' => $return_code,
            'new_csrf_token' => Session::getNewCSRFToken()
        ];
        
        echo json_encode($response);
        
    } catch (Throwable $e) {
        $error_response = [
            'error' => true,
            'message' => $e->getMessage(),
            'new_csrf_token' => Session::getNewCSRFToken()
        ];
        echo json_encode($error_response);
    }
    
    // Garante que nada mais será enviado
    exit;
}

// --- FRONTEND (apenas para requisições normais) ---
Session::checkRight("config", READ);

Html::header(
   __('Gerenciamento de Certificados SSL (Certbot)', 'certbotrenew'),
   $_SERVER['PHP_SELF'],
   'tools',
   'PluginCertbotrenewMenu'
);
?>
<div class="center" style="max-width:900px;margin:40px auto;">
   <h2><?= __('Gerenciamento de Certificados SSL (Certbot)', 'certbotrenew') ?></h2>
   <p><?= __('Escolha uma ação para renovar ou verificar o status dos certificados SSL.', 'certbotrenew') ?></p>

   <div class="alert alert-info" style="max-width:600px;margin:0 auto 20px;">
      <i class="fas fa-globe"></i>
      <strong><?= __('Domínio detectado:', 'certbotrenew') ?></strong>
      <code><?= htmlspecialchars(preg_replace('/:\d+$/', '', $_SERVER['HTTP_HOST'])) ?></code>
   </div>

   <div class="d-flex justify-content-center" style="gap:10px;margin-bottom:20px;">
      <button class="btn btn-warning" onclick="executeAction('renew')">
         <i class="fas fa-sync-alt"></i> <?= __('Renovar agora', 'certbotrenew') ?>
      </button>

      <button class="btn btn-primary" onclick="executeAction('status')">
         <i class="fas fa-certificate"></i> <?= __('Ver status', 'certbotrenew') ?>
      </button>

      <button class="btn btn-secondary" onclick="executeAction('log')">
         <i class="fas fa-file-alt"></i> <?= __('Ver log', 'certbotrenew') ?>
      </button>
   </div>

   <pre id="output" style="
      text-align:left;
      background:#111;
      color:#0f0;
      padding:10px;
      border-radius:10px;
      overflow:auto;
      max-height:500px;
      display:block;
   "><?= __('⏳ Aguardando ação...', 'certbotrenew') ?></pre>
</div>

<script>
let csrfToken = '<?= Session::getNewCSRFToken() ?>';

async function executeAction(action) {
   const outputArea = document.getElementById('output');
   outputArea.textContent = `⚙️ Executando ação: ${action}...\n`;

   try {
      const response = await fetch('<?= $_SERVER['PHP_SELF'] ?>', {
         method: 'POST',
         headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
         },
         body: new URLSearchParams({ 
             ajax: '1', 
             action: action,
             _glpi_csrf_token: csrfToken
         })
      });

      // Verifica se a resposta está vazia
      const responseText = await response.text();
      
      if (!responseText) {
         throw new Error('Resposta vazia do servidor');
      }

      // Tenta fazer parse do JSON
      let result;
      try {
         result = JSON.parse(responseText);
      } catch (e) {
         console.error('Resposta do servidor:', responseText);
         throw new Error('Resposta inválida do servidor: ' + e.message);
      }

      if (result.new_csrf_token) {
         csrfToken = result.new_csrf_token;
      }

      if (result.error) {
         outputArea.textContent += `\n❌ Erro: ${result.message || 'Falha desconhecida'}\n`;
      } else {
         outputArea.textContent += `\n✅ Saída do comando:\n\n${result.output.join('\n')}\n`;
      }

   } catch (err) {
      outputArea.textContent += `\n❗ Erro: ${err.message}\n`;
      console.error('Erro completo:', err);
      
      if (err.message.includes('JSON') || err.message.includes('token') || err.message.includes('vazia')) {
         outputArea.textContent += `\n🔄 Recarregando a página...\n`;
         setTimeout(() => location.reload(), 2000);
      }
   }
}
</script>

<?php
Html::footer();
