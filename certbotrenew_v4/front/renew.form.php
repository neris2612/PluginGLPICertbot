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
                exec('sudo /usr/local/bin/glpi-certbot-wrapper renew 2>&1', $output, $return_code);
                break;

            case 'status':
                // Método alternativo: executar diretamente com ambiente controlado
                $env = [
                    'CERTBOT_NONINTERACTIVE' => 'true',
                    'DEBIAN_FRONTEND' => 'noninteractive',
                    'PATH' => '/usr/bin:/bin'
                ];
                
                $env_string = '';
                foreach ($env as $key => $value) {
                    $env_string .= $key . '=' . $value . ' ';
                }
                
                $command = $env_string . 'sudo /usr/local/bin/glpi-certbot-wrapper status 2>&1';
                exec($command, $output, $return_code);
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
      <button class="btn btn-warning" onclick="executeAction('renew')" id="renewBtn">
         <i class="fas fa-sync-alt"></i> <span class="btn-text"><?= __('Renovar agora', 'certbotrenew') ?></span>
         <div class="spinner-border spinner-border-sm d-none" role="status">
            <span class="visually-hidden">Carregando...</span>
         </div>
      </button>

      <button class="btn btn-primary" onclick="executeAction('status')" id="statusBtn">
         <i class="fas fa-certificate"></i> <span class="btn-text"><?= __('Ver status', 'certbotrenew') ?></span>
         <div class="spinner-border spinner-border-sm d-none" role="status">
            <span class="visually-hidden">Carregando...</span>
         </div>
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
   "><?= __('Aguardando ação...', 'certbotrenew') ?></pre>
</div>

<style>
.spinner-border {
    width: 1rem;
    height: 1rem;
    border-width: 0.15em;
}

.btn:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

/* Spinner personalizado para tema escuro */
.spinner-border {
    color: #fff;
}

.btn-warning .spinner-border {
    color: #000;
}
</style>

<script>
let csrfToken = '<?= Session::getNewCSRFToken() ?>';
let isProcessing = false;

function showSpinner(buttonId) {
    const btn = document.getElementById(buttonId);
    if (btn) {
        const spinner = btn.querySelector('.spinner-border');
        const btnText = btn.querySelector('.btn-text');
        const icon = btn.querySelector('.fa');
        
        if (spinner) spinner.classList.remove('d-none');
        if (icon) icon.classList.add('d-none');
        if (btnText) {
            if (buttonId === 'renewBtn') {
                btnText.textContent = 'Renovando...';
            } else if (buttonId === 'statusBtn') {
                btnText.textContent = 'Verificando...';
            }
        }
        btn.disabled = true;
    }
}

function hideSpinner(buttonId) {
    const btn = document.getElementById(buttonId);
    if (btn) {
        const spinner = btn.querySelector('.spinner-border');
        const btnText = btn.querySelector('.btn-text');
        const icon = btn.querySelector('.fa');
        
        if (spinner) spinner.classList.add('d-none');
        if (icon) icon.classList.remove('d-none');
        if (btnText) {
            if (buttonId === 'renewBtn') {
                btnText.textContent = 'Renovar agora';
            } else if (buttonId === 'statusBtn') {
                btnText.textContent = 'Ver status';
            }
        }
        btn.disabled = false;
    }
}

async function executeAction(action) {
    if (isProcessing) {
        return;
    }
    
    isProcessing = true;
    const buttonId = action + 'Btn';
    const outputArea = document.getElementById('output');
    
    // Mostra spinner no botão clicado
    showSpinner(buttonId);
    outputArea.textContent = `Executando ação: ${action}...\n`;

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
            outputArea.textContent += `\nErro: ${result.message || 'Falha desconhecida'}\n`;
        } else {
            outputArea.textContent += `\nSaída do comando:\n\n${result.output.join('\n')}\n`;
        }

    } catch (err) {
        outputArea.textContent += `\n❌ Erro: ${err.message}\n`;
        console.error('Erro completo:', err);
        
        if (err.message.includes('JSON') || err.message.includes('token') || err.message.includes('vazia')) {
            outputArea.textContent += `\n🔄 Recarregando a página...\n`;
            setTimeout(() => location.reload(), 2000);
        }
    } finally {
        // Esconde o spinner independente do resultado
        hideSpinner(buttonId);
        isProcessing = false;
    }
}
</script>

<?php
Html::footer();
