<?php
include('../../../inc/includes.php');

Session::checkRight("config", READ);

Html::header(
   __('Certbot Renew', 'certbotrenew'),
   $_SERVER['PHP_SELF'],
   'tools',
   'PluginCertbotrenewMenu'
);

// Função auxiliar para execução segura no servidor
function runCommand($cmd) {
   $output = [];
   exec($cmd . ' 2>&1', $output, $code);
   return ['output' => $output, 'code' => $code];
}

// Se for requisição AJAX (JSON)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SERVER['HTTP_X_REQUESTED_WITH'])) {
   header('Content-Type: application/json; charset=utf-8');

   // Garante sessão válida e token CSRF
   Session::checkValidSession();
   if (isset($_SERVER['HTTP_X_GLPI_CSRF_TOKEN'])) {
      $_POST['_glpi_csrf_token'] = $_SERVER['HTTP_X_GLPI_CSRF_TOKEN'];
   }
   Session::checkCSRFToken();

   $data = json_decode(file_get_contents('php://input'), true);
   $action = $data['action'] ?? '';
   $result = [];

   switch ($action) {
      case 'renew':
         $result = runCommand('sudo /usr/bin/certbot renew');
         break;
      case 'status':
         $result = runCommand('sudo /usr/bin/certbot certificates');
         break;
      case 'log':
         $log_path = '/var/log/letsencrypt/letsencrypt.log';
         if (file_exists($log_path)) {
            $result = ['output' => explode("\n", file_get_contents($log_path)), 'code' => 0];
         } else {
            $result = ['output' => ["Arquivo de log não encontrado em $log_path"], 'code' => 1];
         }
         break;
      default:
         $result = ['output' => ['Ação inválida.'], 'code' => 1];
         break;
   }

   echo json_encode($result);
   exit;
}
?>

<div class="card" style="max-width:900px;margin:40px auto;padding:30px;text-align:center;">
   <h2><?= __('Gerenciamento de Certificados SSL (Certbot)', 'certbotrenew') ?></h2>
   <p><?= __('Escolha uma aÃ§Ã£o para instalar, renovar ou verificar o status dos certificados SSL.', 'certbotrenew') ?></p>

   <div class="alert alert-info" style="max-width:600px;margin:0 auto 20px;">
      <i class="fas fa-globe"></i> 
      <strong><?= __('DomÃ­nio detectado:', 'certbotrenew') ?></strong> 
      <code><?= htmlspecialchars($_SERVER['HTTP_HOST']) ?></code>
   </div>

   <div style="margin-top:25px;">
      <button onclick="runCertbot('renew')" class="btn btn-primary" style="margin-right:10px;">
         <i class="fas fa-sync-alt"></i> <?= __('Renovar agora', 'certbotrenew') ?>
      </button>

      <button onclick="runCertbot('status')" class="btn btn-info" style="margin-right:10px;">
         <i class="fas fa-certificate"></i> <?= __('Ver status', 'certbotrenew') ?>
      </button>

      <button onclick="runCertbot('log')" class="btn btn-secondary">
         <i class="fas fa-file-alt"></i> <?= __('Ver log', 'certbotrenew') ?>
      </button>
   </div>

   <div id="output" style="margin-top:30px;text-align:left;max-height:500px;overflow:auto;background:#111;color:#0f0;padding:10px;border-radius:10px;display:none;">
      <pre id="output-content"></pre>
   </div>
</div>

<script>
const CSRF = "<?= addslashes(Session::getNewCSRFToken()) ?>";

async function runCertbot(action) {
   const outputDiv = document.getElementById("output");
   const pre = document.getElementById("output-content");

   outputDiv.style.display = "block";
   pre.innerHTML = `? Executando aÃ§Ã£o: ${action}...\n`;

   try {
      const response = await fetch(window.location.href, {
         method: "POST",
         headers: {
            "Content-Type": "application/json",
            "X-Requested-With": "XMLHttpRequest",
            "X-GLPI-CSRF-TOKEN": CSRF
         },
         body: JSON.stringify({ action })
      });

      const data = await response.json();

      if (data.output) {
         pre.innerHTML += data.output.join("\n");
         if (data.code === 0) {
            pre.innerHTML += "\n\n? Operação concluída com sucesso!";
         } else {
            pre.innerHTML += "\n\n?? Ocorreu um erro durante a execução.";
         }
      } else {
         pre.innerHTML += "\n?? Nenhuma saída retornada.";
      }
   } catch (err) {
      pre.innerHTML += "\n? Erro de comunicaÃ§Ã£o com o servidor: " + err.message;
   }
}
</script>

<?php
Html::footer();
?>
