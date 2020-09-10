<link rel="stylesheet" href="<?php echo HELLO_CHAT_RESOURCE_URL; ?>css/reset.css">
<link rel="stylesheet" href="<?php echo HELLO_CHAT_RESOURCE_URL; ?>css/client/main.css">
<script>
  window.HelloChatClient = {};
  window.HelloChatClient.url = '<?php echo HELLO_CHAT_VERSION_URL; ?>';
</script>    
<?php require_once HELLO_CHAT_DIR.'/Config/settings.php'; ?>
<?php  global $hcDisabledChat;
  if($hcDisabledChat == true){ return; } ?>
<script src="<?php echo HELLO_CHAT_RESOURCE_URL; ?>js/client/main.js" type="module"></script>