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

                    $base = [];
                    $this->setDefaultLocalization($base);
                    // $settingsArray = array_merge_recursive($base, $settingsArray);

                    if($users == $inActive){
                        if(!isset($settingsArray['system'])){
                            $settingsArray['system'] = [];
                        }
                        $settingsArray['system']['active'] = 0;
                    }

                    if(isset($settingsArray['system'])){
                        unset($settingsArray['system']['systemPassword']);
                    }

                    $this->setDefaultSettings($settingsArray);
                    return json_encode($settingsArray);
                } catch(\PDOException $e) {
                    $settingsArray = [];
                    $this->setDefaultLocalization($settingsArray);
                    $this->setDefaultSettings($settingsArray);
                    return json_encode($settingsArray);
                }
            }

            public function setDefaultLocalization(&$settingsArray){
                $settingsArray['initial'] = [ 'initial' => 'Openingsbericht', 'initialMessage' => 'Om u zo snel mogelijk te kunnen helpen vragen wij u om uw postcode, klantennummer en pasnummer door te sturen. <br/><br/>Een van onze medewerkers zal uw spoedig helpen.', ];
                $settingsArray['header'] = [ 'chatPlaceholder' => 'Verbinden..' ];
                $settingsArray['welcome'] = [ 'introTitle' => 'Hallo daar!', 'introDescription' => 'Heb je vragen of ben je opzoek naar iets? Stel dan gerust een vraag zodat een van onze werknemers je zo snel mogelijk kan helpen!', 'formName' => 'Naam..', 'formEmail' => 'Email..', 'formPrivacy' => 'Ik ga akkoord met het [url]verwerken[/url] van mijn persoonsgegevens.', 'formSubmit' => 'Versturen' ];
                $settingsArray['chat'] = [ 'chatMessage' => 'Type je bericht hier..', 'chatClose' => 'Chat sluiten', 'chatNotification' => 'Blicon: Nieuw chat bericht', 'chatOverview' => 'Naar chat overzicht', ];
                $settingsArray['connect'] = [ 'connectButton' => 'Verbind met Chat' ];
                $settingsArray['users'] = [ 'userTitle' => 'Chat Overzicht', 'userButton' => 'Claim chat', 'userActive' => 'Open', 'userInactive' => 'Geclaimed', 'userJoin' => 'Open chat', ]; 
                $settingsArray['archive'] = [ 'archiveTitle' => 'Chat Archief', 'archiveButton' => 'Bekijk chat', 'archiveRefresh' => 'Pagina vernieuwen', 'archiveUsers' => 'Chats', 'archiveFilter' => 'Medewerker', 'archiveFilterDefault' => 'Chats door: ', ]; 
                $settingsArray['end'] = [ 'endTitle' => 'De chat is gesloten', 'endDescription' => 'Bedankt, we wensen u nog een fijne dag!',];
                $settingsArray["detail"] = [ "detailMail" => "Stuur een e-mail", "detailConversation" => "Gesprek", "detailRemove" => "Verwijderen gesprek", 'detailArchive' => 'Archief' ];
                $settingsArray["confirmation"] = [ "confirmationMessage" => "Weet je zeker dat je dit wilt doen", "confirmationYes" => "Ja", "confirmationNo" => "Annuleren", ];
                $settingsArray["settings"] = [ "settingsTitle" => "Instellingen", "settingsMenu" => "Instellingen aanpassen" ];
                $settingsArray['system'] = [ 'inactiveDescription' => 'Sorry, op dit moment zijn er geen medewerkers actief op de chat.', 'inactiveButton' => 'Neem contact op', 'inactiveUrl' => '/' , 'activateLabel' => 'Chat actief', 'systemPassword' => 'This is a placeholder value..', 'systemDisabled' => false];
            }

            public function setDefaultSettings(&$settingsArray){
                if(!isset($settingsArray['system'])){
                    $settingsArray['system'] = [];
                } if(!isset($settingsArray['system']['active'])){
                    $settingsArray['system']['active'] = 0;
                } if(!isset($settingsArray['ui'])){
                    $settingsArray['ui'] = ['uiText' => '#ffffff'];
                } if(!isset($settingsArray['main'])){
                    $settingsArray['main'] = ['mainColor' => '#000000', 'primaryShade' => '#000000'];
                } if(!isset($settingsArray['secondary'])){
                    $settingsArray['secondary'] = ['secondaryColor' => '#969696', 'secondaryShade' => '#969696'];
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

    function gdhc_is_system_disabled($data){
        if(isset($_GET['mode']) && $_GET['mode'] == 0){ return false; }
        $data = json_decode($data, true);
        if(!isset($data['system'])){ return true; }
        if(!isset($data['system']['systemDisabled'])){ return true; }
        if($data['system']['systemDisabled'] == 'off'){ return true; }
        return false;
    }
?>
<?php require_once('defaultLocalization.php'); ?>
<?php $data = gdhc_fetch_localization(); ?>
<?php if(gdhc_is_system_disabled($data)){ global $hcDisabledChat; $hcDisabledChat = true; return; } ?>
<script>
    if(typeof window.HelloChat == 'undefined'){
        window.HelloChat = {};
    }
    window.HelloChat.localization = JSON.parse('<?php echo $data; ?>');
    window.HelloChat.active = (window.HelloChat.localization.system.active == 1) ? true : false;
    delete window.HelloChat.localization.system.active;
    delete window.HelloChat.localization.system.systemPassword;
    delete window.HelloChat.localization.system.systemDisabled;
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
        'expandIcon' : '<?php echo HELLO_CHAT_VERSION_URL; ?>HelloChat/resources/img/expand.png',
        'shrinkIcon' : '<?php echo HELLO_CHAT_VERSION_URL; ?>HelloChat/resources/img/shrink.png',
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