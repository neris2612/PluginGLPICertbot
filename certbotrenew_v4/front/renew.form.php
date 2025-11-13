<?php
include('../../../inc/includes.php');

// Define o caminho do log do Certbot
define('CERTBOT_LOG_PATH', '/var/log/letsencrypt/letsencrypt.log');

// --- BACKEND (responde ao AJAX) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax'])) {
    // Verifica permissão para requisições AJAX
    Session::checkLoginUser();
    Session::checkRight("config", READ);
    
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
                    $output = explode("\n", file_get_contents(CERTBOT_LOG_PATH));
                } else {
                    throw new Exception("Arquivo de log não encontrado: " . CERTBOT_LOG_PATH);
                }
                break;

            default:
                throw new Exception("Ação inválida: $action");
        }

        echo json_encode([
            'error' => false,
            'action' => $action,
            'output' => $output,
            'code' => $return_code
        ]);
    } catch (Throwable $e) {
        http_response_code(500);
        echo json_encode([
            'error' => true,
            'message' => $e->getMessage()
            // Removido 'trace' por segurança em produção
        ]);
    }
    exit;
}

// --- FRONTEND (apenas para requisições normais) ---

// Verifica permissão
Session::checkRight("config", READ);

// Cabeçalho HTML padrão do GLPI
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

   <!-- Exibir domínio detectado -->
   <div class="alert alert-info" style="max-width:600px;margin:0 auto 20px;">
      <i class="fas fa-globe"></i>
      <strong><?= __('Domínio detectado:', 'certbotrenew') ?></strong>
      <code><?= htmlspecialchars(preg_replace('/:\d+$/', '', $_SERVER['HTTP_HOST'])) ?></code>
   </div>

   <!-- Botões AJAX -->
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

   <!-- Área de log -->
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
// Captura o token CSRF do GLPI
const csrfToken = '<?= Session::getNewCSRFToken() ?>';

// Função para executar ações via AJAX
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

      if (!response.ok) {
         throw new Error(`HTTP ${response.status}`);
      }

      const result = await response.json();

      if (result.error) {
         outputArea.textContent += `\n❌ Erro: ${result.message || 'Falha desconhecida'}\n`;
      } else {
         outputArea.textContent += `\n✅ Saída do comando:\n\n${result.output.join('\n')}\n`;
      }

   } catch (err) {
      outputArea.textContent += `\n❗ Erro de comunicação: ${err.message}\n`;
   }
}
</script>

<?php
Html::footer();
?>
