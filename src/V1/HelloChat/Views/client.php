<!doctype html>
<html class="no-js" lang="">

<head>
  <meta charset="utf-8">
  <title></title>
  <!-- <meta name="description" content=""> -->
  <meta name="viewport" content="width=device-width, initial-scale=1">

  <!-- <meta property="og:title" content=""> -->
  <!-- <meta property="og:type" content=""> -->
  <!-- <meta property="og:url" content=""> -->
  <!-- <meta property="og:image" content=""> -->

  <!-- <link rel="manifest" href="site.webmanifest"> -->
  <link rel="apple-touch-icon" href="icon.png">
  <!-- Place favicon.ico in the root directory -->

  <link rel="stylesheet" href="<?php echo HELLO_CHAT_RESOURCE_URL; ?>/css/reset.css">
  <link rel="stylesheet" href="<?php echo HELLO_CHAT_RESOURCE_URL; ?>/css/app/main.css">

  <meta name="theme-color" content="#fafafa">
</head>
<body>
    <script>
        window.HelloChat = {};
        
        window.HelloChat.general = { 
            'opening' : {
                'content' : 'Om u zo snel mogelijk te kunnen helpen vragen wij u om uw postcode, klantennummer en pasnummer door te sturen. <br/><br/>Een van onze medewerkers zal uw spoedig helpen.',
                'email' : '#',
                'name' : 'Blicon'
            }
        };
    </script>
    <?php require_once HELLO_CHAT_DIR.'/Config/settings.php'; ?>
  <script src="<?php echo HELLO_CHAT_RESOURCE_URL; ?>/js/app/client.js" type="module"></script>
</body>

</html>