<?php 
namespace Goodday\HelloChat;

use DateTime;
use Exception;
use SilverStripe\Control\Controller;
use SilverStripe\Control\Director;
use SilverStripe\Core\ClassInfo;
use SilverStripe\Core\Convert;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\CronTask\CronTaskStatus;
use SilverStripe\CronTask\Interfaces\CronTask;
use SilverStripe\ORM\FieldType\DBDatetime;
use SilverStripe\Security\Permission;
use SilverStripe\Security\Security;

class HelloChatController extends Controller {

	    private static $url_handlers = [
        	'admin/$*' => 'index',
    	];


	    public function init() {
        parent::init();

        // Unless called from the command line, we need ADMIN privileges
        if (!Permission::check('ADMIN')) {
            Security::permissionFailure();
        }
    }

    public function index(HTTPRequest $request) {
        // Show more debug info with ?debug=1
        $isDebug = (bool)$request->getVar('debug');


    }

}