<!doctype html>
<html class="no-js" lang="">

<head>
  <meta charset="utf-8">
  <title></title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="apple-touch-icon" href="icon.png">
  <link rel="stylesheet" href="<?php echo HELLO_CHAT_RESOURCE_URL; ?>css/reset.css">
  <link rel="stylesheet" href="<?php echo HELLO_CHAT_RESOURCE_URL; ?>css/client/main.css">

  <meta name="theme-color" content="#fafafa">
</head>

<body>
  <script>
    window.HelloChatClient = {};
    window.HelloChatClient.url = '<?php echo HELLO_CHAT_VERSION_URL; ?>';
  </script>
  <?php require_once HELLO_CHAT_DIR.'/Config/settings.php'; ?>
  <script src="<?php echo HELLO_CHAT_RESOURCE_URL; ?>js/client/admin.js" type="module"></script>
</body>

</html>