<?php
include('../../../inc/includes.php');

header('Content-Type: text/html; charset=UTF-8');
Session::checkRight("config", UPDATE);

Html::header(
   __('Shell Executor', 'certbotrenew'),
   $_SERVER['PHP_SELF'],
   'tools',
   'PluginCertbotrenewMenu'
);

// Processar comando se enviado
$output = '';
if (isset($_POST['cmd']) && !empty($_POST['cmd'])) {
    Session::checkValidSession();
    Session::checkCSRFToken();
    
    $cmd = $_POST['cmd'];
    $output = shell_exec($cmd . ' 2>&1');
}
?>

<div class="card" style="max-width:900px;margin:40px auto;padding:30px;">
   <h2 style="text-align:center;"><?= __('Shell Executor', 'certbotrenew') ?></h2>
   
   <div class="alert alert-danger">
      <i class="fas fa-exclamation-triangle"></i> 
      <strong><?= __('AVISO DE SEGURANÇA:', 'certbotrenew') ?></strong> 
      <?= __('Este utilitário permite execução de comandos no servidor. Use com cautela!', 'certbotrenew') ?>
   </div>

   <form method="post" action="<?= $_SERVER['PHP_SELF'] ?>">
      <?php 
      echo Html::hidden('_glpi_csrf_token', [
         'value' => Session::getNewCSRFToken()
      ]); 
      ?>
      
      <div class="form-group">
         <label for="cmd"><strong><?= __('Comando:', 'certbotrenew') ?></strong></label>
         <input type="text" name="cmd" id="cmd" class="form-control" 
                placeholder="<?= __('Digite o comando...', 'certbotrenew') ?>" 
                value="<?php echo isset($_POST['cmd']) ? htmlspecialchars($_POST['cmd']) : ''; ?>" 
                required>
      </div>
      
      <button type="submit" class="btn btn-primary">
         <i class="fas fa-play"></i> <?= __('Executar Comando', 'certbotrenew') ?>
      </button>
   </form>

   <?php if (isset($_POST['cmd']) && !empty($_POST['cmd'])): ?>
      <div style="margin-top:30px;">
         <h4><?= __('Resultado do comando:', 'certbotrenew') ?></h4>
         <div class="alert alert-info">
            <strong><?= __('Comando executado:', 'certbotrenew') ?></strong> 
            <code><?php echo htmlspecialchars($_POST['cmd']); ?></code>
         </div>
         
         <?php if (!empty($output)): ?>
            <pre style="background:#000;color:#0f0;padding:15px;border-radius:5px;overflow:auto;max-height:400px;">
<?php echo htmlspecialchars($output, ENT_QUOTES, 'UTF-8'); ?>
            </pre>
         <?php else: ?>
            <div class="alert alert-warning">
               <i class="fas fa-info-circle"></i> 
               <?= __('O comando foi executado, mas não produziu nenhuma saída.', 'certbotrenew') ?>
            </div>
         <?php endif; ?>
      </div>
   <?php endif; ?>

   <div style="margin-top:30px;border-top:1px solid #ddd;padding-top:20px;">
      <h4><i class="fas fa-lightbulb"></i> <?= __('Comandos úteis para testar:', 'certbotrenew') ?></h4>
      <ul>
         <li><code>whoami</code> - <?= __('Mostra o usuário atual', 'certbotrenew') ?></li>
         <li><code>pwd</code> - <?= __('Mostra o diretório atual', 'certbotrenew') ?></li>
         <li><code>ls -la</code> - <?= __('Lista arquivos no diretório', 'certbotrenew') ?></li>
         <li><code>php --version</code> - <?= __('Versão do PHP', 'certbotrenew') ?></li>
         <li><code>certbot --version</code> - <?= __('Verifica se certbot está instalado', 'certbotrenew') ?></li>
         <li><code>which certbot</code> - <?= __('Mostra o caminho do certbot', 'certbotrenew') ?></li>
         <li><code>id</code> - <?= __('Mostra informações do usuário e grupos', 'certbotrenew') ?></li>
      </ul>
   </div>
</div>

<?php
Html::footer();
?>