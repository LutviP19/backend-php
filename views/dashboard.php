<?php $this->include('includes.header') ?>
<br><br>
<div class="container">
  <iframe src="<?= assets('iframe/dashboard-frame.html') ?>" style="border: none;position: absolute;top: 0;left: 0;bottom: 0;right: 0;width: 100%;height: 93%;margin-top: 5rem;"></iframe>
</div>
<script type="text/javascript">
document.body.setAttribute("style", "overflow-y: hidden;");
</script>  
<?php //$this->include('includes.footer') ?>