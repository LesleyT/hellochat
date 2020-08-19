<?php
    namespace API\Controllers;
    function gdhc_fetch_localization(){
        require_once(HELLO_CHAT_VERSION_ROOT.'/API/Controllers/DB.php');
        require_once(HELLO_CHAT_VERSION_ROOT.'/API/Models/Response.php');
        require_once(HELLO_CHAT_VERSION_ROOT.'/API/Controllers/Request.php');
        
        class Localization extends Request{
            public function __construct(){
                parent::__construct();                
                return true;
            }

            public function run(){ 
                try {
                    $query = $this->getReadDB()->prepare('SELECT * FROM gdhc_settings');
                    $query->execute();
                    
                    $rowCount = $query->rowCount();

                    /* BUILD RESPONS */
                    $settingsArray = [];
                    while($row = $query->fetch(\PDO::FETCH_ASSOC)){
                        if(!isset($settingsArray[$row['category']])){
                            $settingsArray[$row['category']] = [];
                        }
                        $settingsArray[$row['category']][$row['name']] = $row['value'];
                    }

                    $query = $this->getReadDB()->prepare('SELECT accesstokenexpiry FROM gdhc_sessions, gdhc_users WHERE gdhc_sessions.userId = gdhc_users.id AND gdhc_users.role = "ADMIN"');
                    $query->execute();
                    $users = 0;
                    $inActive = 0;
                    while($row = $query->fetch(\PDO::FETCH_ASSOC)){
                        $users++;
                        if(strtotime($row['accesstokenexpiry']) < time()){
                            $inActive++;
                        }
                    }

                    if($users == $inActive){
                        if(!isset($settingsArray['system'])){
                            $settingsArray['system'] = [];
                        }
                        $settingsArray['system']['active'] = 0;
                    }

                    if(isset($settingsArray['system'])){
                        unset($settingsArray['system']['systemPassword']);
                    }

                    if(!isset($settingsArray['ui'])){
                        $settingsArray['ui'] = ['uiText' => '#ffffff'];
                    } if(!isset($settingsArray['main'])){
                        $settingsArray['main'] = ['mainColor' => '#000000', 'primaryShade' => '#000000'];
                    } if(!isset($settingsArray['secondary'])){
                        $settingsArray['secondary'] = ['secondaryColor' => '#969696', 'secondaryShade' => '#969696'];
                    }

                    return json_encode($settingsArray);
                } catch(\PDOException $e) {
                    return json_encode(false);
                }
            }
            protected function DELETE(){ }
            protected function PATCH(){ }
            protected function POST(){ }
            protected function GET(){ }
            protected function OPTIONS(){ }
        }
        return (new Localization())->run();
    }
?>
<?php require_once('defaultLocalization.php'); ?>
<?php $data = gdhc_fetch_localization(); ?>
<script>
    if(typeof window.HelloChat == 'undefined'){
        window.HelloChat = {};
    }
    window.HelloChat.localization = JSON.parse('<?php echo $data; ?>');
    window.HelloChat.active = (window.HelloChat.localization.system.active == 1) ? true : false;
    delete window.HelloChat.localization.system.active;
    delete window.HelloChat.localization.system.systemPassword;
    delete window.HelloChat.localization.main;
    delete window.HelloChat.localization.secondary;
    delete window.HelloChat.localization.ui;
    
    if(!window.HelloChat.localization){
        window.HelloChat.localization = window.HelloChat.defaultLocalization;
    }

    window.HelloChat.config = {
        'privacy' : '#',
        'defaultIcon' : '<?php echo HELLO_CHAT_VERSION_URL; ?>HelloChat/resources/img/user.png',
        'settingsIcon' : '<?php echo HELLO_CHAT_VERSION_URL; ?>HelloChat/resources/img/settings.png',
        'notificationIcon' : '<?php echo HELLO_CHAT_VERSION_URL; ?>HelloChat/resources/img/icon.png',
        'api': '<?php echo HELLO_CHAT_VERSION_API; ?>'
    };
</script>

<?php 
    $settings = json_decode($data);
?>
<style>
    :root {
        --main-color: <?php echo $settings->main->mainColor; ?>;
        --main-shade: <?php echo $settings->main->primaryShade; ?>;
        --secondary-color: <?php echo $settings->secondary->secondaryColor; ?>;
        --secondary-shade: <?php echo $settings->secondary->secondaryShade; ?>;
        
        --ui-text: <?php echo $settings->ui->uiText; ?>;
        
        --message-text: #404040;
        --message-subtext: #adadad;
        --message-send: <?php echo $settings->main->mainColor; ?>;
        --message-received: #ececec; 
    }
</style>
<?php unset($settings); ?>