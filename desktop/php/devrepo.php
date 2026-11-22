<?php
if (!isConnect('admin')) {
  throw new Exception('{{401 - Accès non autorisé}}');
}
?>
<div class="row row-overflow">
  <div class="col-xs-12">
    <legend><i class="fas fa-code-branch"></i> {{DevRepo}}</legend>
    <?php include dirname(__FILE__) . '/devrepo.list.php'; ?>
  </div>
</div>
<?php include_file('desktop', 'devrepo', 'css', 'devrepo'); ?>
<?php include_file('desktop', 'devrepo', 'js', 'devrepo'); ?>
