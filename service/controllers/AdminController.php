<?php

require_once 'BaseController.php';
require_once 'models/SessionModel.php';
require_once 'models/InitiativeModel.php';
require_once 'models/LocationModel.php';
require_once 'models/ActivityModel.php';

Zend_Loader::loadClass('Zend_Auth_Adapter_Ldap');
class AdminController extends BaseController
{
    private $userData = [];

    private $ldapOptions = [];

    public function indexAction()
    {
        //
    }

    public function loginAction()
    {
    }

    public function authenticateAction()
    {
        $validData = true;
        $validRequestData = true;
        $logMsg = "Authentication Attempt:";
        $config = Globals::getConfig();
        $result = "";
        $loginError = false;

        $user = Zend_Filter::filterStatic($this->getRequest()->getParam('username'), 'StripTags');
        $pass = Zend_Filter::filterStatic($this->getRequest()->getParam('password'), 'StripTags');

        if (empty($user) && empty($pass)) {
            $logMsg .= " Failed, ";
            $logMsg .= "no credentials provided.";
            $validRequestData = false;
        } else {
            if (empty($user)) {
                $logMsg .= " Failed, ";
                $logMsg .= "no username provided.";
                $validRequestData = false;
            } elseif (empty($pass)) {
                $logMsg .= " Failed, ";
                $logMsg .= "no password provided.";
                $validRequestData = false;
            }
        }

        if (! $validRequestData) {
            if (! empty($user)) {
                $logMsg .= " User ID: " . $user;
            } else {
                $logMsg .= " User ID: anonymous";
            }

            Globals::getLog()->err($logMsg);
            $this->forward('login');
        }

        $lcUserName = strtolower($user);

        if (! empty($config->sumaserver->sumaAdminsFp)) {
            $sumaAdminsFile = new \SplFileObject($config->sumaserver->sumaAdminsFp);
            $this->buildUserDataArray($sumaAdminsFile);
        } else {
            $logMsg .= " Failed, conf setting sumaAdminsFp is empty. ";
            $logMsg .= "Please set a file path for the sumaAdminsFp configuration setting.";
            Globals::getLog()->err($logMsg);
            $this->forward('login');
        }

        //$result = $auth->authenticate(new My_Auth_Adapter($user, $pass));

        if (! $this->isValidUser($user)) {
            $logMsg .= " Failed, user id is not authorized to access service.";
            $logMsg .= " User ID: " . $user;
            $logMsg .= " Lowercase User ID: " . $lcUserName;
            $validData = false;
            Globals::getLog()->err($logMsg);
            $this->forward('login');
        }

        if ($validData) {
            $auth = Zend_Auth::getInstance();
            $this->buildLDAPOptionsArray($config);

            $adapter = new Zend_Auth_Adapter_Ldap($this->ldapOptions, $user, $pass);

            $result = $auth->authenticate($adapter);

            $codeStr = $this->getResultCodeStr($result);

            if (! $result->isValid()) {
                $logMsg .= " " . $codeStr;
                $logMsg .= " User ID: " . $user;
                $logMsg .= " Lowercase User ID: " . $lcUserName;

                $errorMessages = $result->getMessages();

                if (! empty($errorMessages)) {
                    for ($a = 0; $a < count($errorMessages); $a++) {
                        $logMsg .= " Reason: " . $errorMessages[$a] . "; ";
                    }
                }
                $loginError = true;
                Globals::getLog()->err($logMsg);
            } else {
                $logMsg .= " " . $codeStr;
                $logMsg .= " User ID: " . $user;
                $logMsg .= " Lowercase User ID: " . $lcUserName;
                Globals::getLog()->info($logMsg);
            }
        }

        if (! $loginError) {
            $this->forward("index");
        }
    }

    public function logoutAction()
    {
        Zend_Auth::getInstance()->clearIdentity();
        Zend_Session::destroy();
        $this->forward('login');
    }

    public function errorAction()
    {
    }

    public function initiativesAction()
    {
        try {
            $this->view->initiatives = InitiativeModel::getAll();
            $this->view->roots = LocationModel::getLocTreeRoots();
        } catch (Exception $e) {
            $this->view->error = $e->getMessage();
            Globals::getLog()->err('ADMIN fetch initiatives error: ' . $this->view->error);
            $this->render('error');
            return false;
        }
    }

    public function initiativeloadAction()
    {
        $id = Zend_Filter::filterStatic($this->getRequest()->getParam('id'), 'StripTags');

        if (is_numeric($id)) {
            try {
                $this->view->init = new InitiativeModel($id);
                $this->view->roots = LocationModel::getLocTreeRoots();
            } catch (Exception $e) {
                $this->view->error = $e->getMessage();
                Globals::getLog()->err('ADMIN load initiative error: ' . $this->view->error);
                $this->render('error-xhr');
                return false;
            }
        } else {
            $this->view->error = 'Invalid initiative ID';
            Globals::getLog()->err('ADMIN load initiative error: ' . $this->view->error);
            $this->render('error-xhr');
            return false;
        }
    }

    public function createinitiativeAction()
    {
        $title = Zend_Filter::filterStatic($this->getRequest()->getParam('title'), 'StripTags');
        $description = Zend_Filter::filterStatic($this->getRequest()->getParam('desc'), 'StripTags');
        $locRootID = Zend_Filter::filterStatic($this->getRequest()->getParam('locRootID'), 'StripTags');

        if ((!empty($title) || is_numeric($title)) && is_numeric($locRootID)) {
            $data['title'] = $title;
            $data['description'] = $description;

            try {
                $this->view->id = InitiativeModel::create($data);
                $initModel = new InitiativeModel($this->view->id);
                $initModel->setRoot($locRootID);
            } catch (Exception $e) {
                $this->view->error = $e->getMessage();
                Globals::getLog()->err('ADMIN create initiative error: ' . $this->view->error);
                $this->render('error-xhr');
                return false;
            }
        } else {
            $this->view->error = 'Title must not be empty and location root ID must be numeric';
            Globals::getLog()->err('ADMIN create initiative error: ' . $this->view->error);
            $this->render('error-xhr');
            return false;
        }
    }

    public function updateinitiativeAction()
    {
        $id = Zend_Filter::filterStatic($this->getRequest()->getParam('id'), 'StripTags');
        $title = Zend_Filter::filterStatic($this->getRequest()->getParam('title'), 'StripTags');
        $description = Zend_Filter::filterStatic($this->getRequest()->getParam('desc'), 'StripTags');

        if (is_numeric($id) && (!empty($title) || is_numeric($title))) {
            $data['title'] = $title;
            $data['description'] = $description;

            try {
                $init = new InitiativeModel($id);
                $init->update($data);
            } catch (Exception $e) {
                $this->view->error = $e->getMessage();
                Globals::getLog()->err('ADMIN update initiative error: ' . $this->view->error);
                $this->render('error-xhr');
            }
        }
    }

    public function enableinitiativeAction()
    {
        $id = Zend_Filter::filterStatic($this->getRequest()->getParam('id'), 'StripTags');

        if (is_numeric($id)) {
            try {
                $init = new InitiativeModel($id);
                $init->enable();
            } catch (Exception $e) {
                $this->view->error = $e->getMessage();
                Globals::getLog()->err('ADMIN enable initiative error: ' . $this->view->error);
                $this->render('error-xhr');
            }
        }
    }

    public function disableinitiativeAction()
    {
        $id = Zend_Filter::filterStatic($this->getRequest()->getParam('id'), 'StripTags');

        if (is_numeric($id)) {
            try {
                $init = new InitiativeModel($id);
                $init->disable();
            } catch (Exception $e) {
                $this->view->error = $e->getMessage();
                Globals::getLog()->err('ADMIN disable initiative error: ' . $this->view->error);
                $this->render('error-xhr');
            }
        }
    }

    public function setinitloctreeAction()
    {
        $id = Zend_Filter::filterStatic($this->getRequest()->getParam('id'), 'StripTags');
        $rootId = Zend_Filter::filterStatic($this->getRequest()->getParam('root'), 'StripTags');

        if (is_numeric($id) && is_numeric($rootId)) {
            try {
                $initModel = new InitiativeModel($id);
                $initModel->setRoot($rootId);
            } catch (Exception $e) {
                $this->view->error = $e->getMessage();
                Globals::getLog()->err('ADMIN set initiative location tree error: ' . $this->view->error);
                $this->render('error-xhr');
            }
        }
    }

    public function updateactivitiesAction()
    {
        $activities = json_decode(Zend_Filter::filterStatic($this->getRequest()->getParam('activities'), 'StripTags'), true);
        $initID = Zend_Filter::filterStatic($this->getRequest()->getParam('init'), 'StripTags');

        if (!$activities || !is_numeric($initID)) {
            $this->view->error = 'Problem with activities or init ID';
            Globals::getLog()->err('ADMIN update activities error: ' . $this->view->error);
            $this->render('error-xhr');
            return false;
        }

        try {
            ActivityModel::updateActivitiesArray($activities, $initID);
        } catch (Exception $e) {
            $this->view->error = 'Error updating activities: ' . $e->getMessage();
            Globals::getLog()->err('ADMIN update activities error: ' . $this->view->error);
            $this->render('error-xhr');
            return false;
        }
        return true;
    }

    public function enableactivityAction()
    {
        $id = Zend_Filter::filterStatic($this->getRequest()->getParam('id'), 'StripTags');

        if (is_numeric($id)) {
            try {
                $activity = new ActivityModel($id);
                $activity->enable();
            } catch (Exception $e) {
                $this->view->error = $e->getMessage();
                Globals::getLog()->err('ADMIN enable activity error: ' . $this->view->error);
                $this->render('error-xhr');
            }
        } else {
            $this->view->error = 'Invalid activity ID';
            Globals::getLog()->err('ADMIN enable activity error: ' . $this->view->error);
            $this->render('error-xhr');
            return false;
        }
    }

    public function disableactivityAction()
    {
        $id = Zend_Filter::filterStatic($this->getRequest()->getParam('id'), 'StripTags');

        if (is_numeric($id)) {
            try {
                $activity = new ActivityModel($id);
                $activity->disable();
            } catch (Exception $e) {
                $this->view->error = $e->getMessage();
                Globals::getLog()->err('ADMIN disable activity error: ' . $this->view->error);
                $this->render('error-xhr');
            }
        } else {
            $this->view->error = 'Invalid activity ID';
            Globals::getLog()->err('ADMIN disable activity error: ' . $this->view->error);
            $this->render('error-xhr');
            return false;
        }
    }

    public function locationsAction()
    {
        try {
            $this->view->roots = LocationModel::getLocTreeRoots();
        } catch (Exception $e) {
            $this->render('error');
        }
    }

    public function locationloadAction()
    {
        $id = Zend_Filter::filterStatic($this->getRequest()->getParam('id'), 'StripTags');

        if (is_numeric($id)) {
            try {
                $this->view->location = new LocationModel($id);
            } catch (Exception $e) {
                $this->view->error = $e->getMessage();
                Globals::getLog()->err('ADMIN load location view error: ' . $this->view->error);
                $this->render('error-xhr');
            }
        } else {
            $this->view->error = 'Invalid location ID';
            Globals::getLog()->err('ADMIN load location error: ' . $this->view->error);
            $this->render('error-xhr');
            return false;
        }
    }

    public function createloctreeAction()
    {
        $title = Zend_Filter::filterStatic($this->getRequest()->getParam('title'), 'StripTags');
        $description = Zend_Filter::filterStatic($this->getRequest()->getParam('desc'), 'StripTags');

        if (!empty($title) || is_numeric($title)) {
            $data['title'] = $title;
            $data['description'] = $description;
            try {
                $this->view->id = LocationModel::create($data);
            } catch (Exception $e) {
                $this->view->error = $e->getMessage();
                Globals::getLog()->err('ADMIN create location tree error: ' . $this->view->error);
                $this->render('error-xhr');
                return false;
            }
            // TODO: see if this fails...
            // if so, delete loc and throw an error
        } else {
            $this->view->error = 'Title must not be empty';
            Globals::getLog()->err('ADMIN create location tree error: ' . $this->view->error);
            $this->render('error-xhr');
            return false;
        }
    }

    public function updateloctreeAction()
    {
        $locTree = json_decode(Zend_Filter::filterStatic($this->getRequest()->getParam('loc_tree'), 'StripTags'), true);

        if (!$locTree) {
            $this->view->error = 'Problem parsing tree';
            Globals::getLog()->err('ADMIN update location tree error: ' . $this->view->error);
            $this->render('error-xhr');
            return false;
        }
        try {
            $db = Globals::getDBConn();
            $db->beginTransaction();
            LocationModel::updateLocTree($locTree);
            $db->commit();
        } catch (Exception $e) {
            $db->rollBack();
            $this->view->error = $e->getMessage();
            Globals::getLog()->err('ADMIN update location tree error: ' . $this->view->error);
            $this->render('error-xhr');
            return false;
        }

        return true;
    }

    public function toggleloctreeAction()
    {
        // NOT TO BE IMPLEMENTED YET
    }

    public function sessionsAction()
    {
        $this->view->sessions = SessionModel::getAll(false);
    }

    public function sessionAction()
    {
        $id = Zend_Filter::filterStatic($this->getRequest()->getParam('id'), 'StripTags');

        if (is_numeric($id)) {
            try {
                $session = new SessionModel($id);
                $init = $session->getInitiative();

                $rootLoc = $init->getRootLocation();
                $treeString = LocationModel::walkTree($rootLoc->getMetadata('id'));
                $treeArray = explode(",", $treeString);
                $locations = array();
                foreach ($treeArray as $locId) {
                    $locations[] = new LocationModel($locId);
                }

                $this->view->locations = $locations;
                $this->view->session = $session;
                $this->view->init = $init;
            } catch (Exception $e) {
                $this->view->error = $e->getMessage();
                Globals::getLog()->err('ADMIN sessions error: ' . $this->view->error);
                $this->render('error');
                return false;
            }
        } else {
            $this->view->error = 'Invalid session ID';
            Globals::getLog()->err('ADMIN sessions error: ' . $this->view->error);
            $this->render('error');
            return false;
        }
    }

    public function jsonimportAction()
    {
    }

    public function preDispatch()
    {
        $auth = Zend_Auth::getInstance();

        if (!$auth->hasIdentity()) {
            if (
                !in_array($this->_request->getActionName(), array(
                    'login', 'authenticate'
                ))
            ) {
                $this->redirect('admin/login');
            }
        } /* else {
            //echo $auth->getIdentity();
        } */
    }

    private function getResultCodeStr($result)
    {
        $codeStr = "";

        switch ($result->getCode()) {
            case Zend_Auth_Result::FAILURE:
                $codeStr .= "Failure";
                break;
            case Zend_Auth_Result::FAILURE_CREDENTIAL_INVALID:
                $codeStr .= "Failure: Invalid Credential";
                break;
            case Zend_Auth_Result::FAILURE_IDENTITY_AMBIGUOUS:
                $codeStr .= "Failure: Ambiguous Identity";
                break;
            case Zend_Auth_Result::FAILURE_IDENTITY_NOT_FOUND:
                $codeStr .= "Failure: Identity Not Found";
                break;
            case Zend_Auth_Result::FAILURE_UNCATEGORIZED:
                $codeStr .= "Failture: Uncategorized";
                break;
            case Zend_Auth_Result::SUCCESS:
                $codeStr .= "Success";
                break;
            default:
                break;
        }

        return $codeStr;
    }

    private function buildLDAPOptionsArray($config): void
    {
        $ldapConfsObject = $config->sumaserver->ldap;

        $this->ldapOptions = [
            'default' => [
                'host' => $ldapConfsObject->host,
                'port' => $ldapConfsObject->port,
                'useSsl' => $ldapConfsObject->useSsl,
                'username' => $ldapConfsObject->username,
                'password' => $ldapConfsObject->password,
                'baseDn' => $ldapConfsObject->baseDn,
                'bindRequiresDn' => $ldapConfsObject->bindRequiresDn,
                'accountFilterFormat' => $ldapConfsObject->accountFilterFormat,
                'accountCanonicalForm' => $ldapConfsObject->accountCanonicalForm,
            ],
        ];
    }

    private function buildUserDataArray(\SplFileObject $userDataFile): void
    {
        /* Globals::getLog()->info("userDataFile = " . $userDataFile->getPath() . "/" . $userDataFile->getFilename());
        $i = 0; */
        while (($dataRow = $userDataFile->fgetcsv()) != false) {
            /* Globals::getLog()->info('dataRow[0] is empty = ' . (empty($dataRow[0]) ? 'true' : 'false')); */
            if (! empty($dataRow[0])) {
                $name = $dataRow[0];
                /* $logMsg = "";
                $logMsg .= "name = " . $name . PHP_EOL; */
                array_push($this->userData, $name);

                /* $logMsg .= "Size of userData = " . count($this->userData) . PHP_EOL;
                $logMsg .= 'User [' . $i . ']: ' . $this->userData[$i] . ';  ';
                Globals::getLog()->info($logMsg);
                $i++; */
            }
        }
    }

    private function isValidUser(string $userId = null)
    {
        /* Globals::getLog()->info('userId is null = ' . (empty($userId) ? 'true' : 'false'));
        Globals::getLog()->info('User id = ' . (empty($userId) ? "null" : $userId));
        Globals::getLog()->info('User id ' . $userId . ' is in userData = ' . (
            in_array($userId, $this->userData) ? 'true' : 'false'
        )); */
        return (!empty($this->userData) ? in_array($userId, $this->userData) : false);
    }
}
