<?php
/**
 * TEST: Test Discussions on TYMY api
 * 
 */

namespace Test;

use Nette;
use Tester;
use Tester\Assert;

$container = require __DIR__ . '/../bootstrap.php';
Tester\Environment::skip('Temporary skipping');
if (in_array(basename(__FILE__, '.phpt') , $GLOBALS["testedTeam"]["skips"])) {
    Tester\Environment::skip('Test skipped as set in config file.');
}

class APIDiscussionsTest extends ITapiTest {

    /** @var \Tymy\Discussions */
    private $discussions;

    function __construct(Nette\DI\Container $container) {
        $this->container = $container;
    }
    
    public function getTestedObject() {
        return $this->discussions;
    }
    
    protected function setUp() {
        $this->discussions = $this->container->getByType('Tymy\Discussions');
        parent::setUp();
    }
    
    /* TEST GETTERS AND SETTERS */ 
    
    /* TEST TAPI FUNCTIONS */ 
    
    /* TAPI : SELECT */
    
    /**
     * @throws Nette\Application\AbortException
     */
    function testFetchNotLoggedInFails404() {
        $presenterFactory = $this->container->getByType('Nette\Application\IPresenterFactory');
        $mockPresenter = $presenterFactory->createPresenter('Homepage');
        $mockPresenter->autoCanonicalize = FALSE;

        $this->authenticator->setId(38);
        $this->authenticator->setStatus(["TESTROLE", "TESTROLE2"]);
        $this->authenticator->setArr(["tym" => "testteam", "sessionKey" => "dsfbglsdfbg13546"]);

        $mockPresenter->getUser()->setAuthenticator($this->authenticator);
        $mockPresenter->getUser()->login("test", "test");


        $discussionsObj = new \Tymy\Discussions();
        $discussionsObj->setPresenter($mockPresenter)
                ->recId(1)
                ->fetch();
    }
    
    /**
     * @throws Nette\Application\AbortException
     */
    function testFetchNotLoggedInRedirects() {
        $presenterFactory = $this->container->getByType('Nette\Application\IPresenterFactory');
        $mockPresenter = $presenterFactory->createPresenter('Homepage');
        $mockPresenter->autoCanonicalize = FALSE;

        $this->authenticator->setId(38);
        $this->authenticator->setStatus(["TESTROLE", "TESTROLE2"]);
        $this->authenticator->setArr(["sessionKey" => "dsfbglsdfbg13546"]);

        $mockPresenter->getUser()->setAuthenticator($this->authenticator);
        $mockPresenter->getUser()->login("test", "test");


        $discussionsObj = new \Tymy\Discussions();
        $discussionsObj
                ->setPresenter($mockPresenter)
                ->recId(1)
                ->fetch();
    }
    
    function testFetchSuccess() {
        $presenterFactory = $this->container->getByType('Nette\Application\IPresenterFactory');
        $mockPresenter = $presenterFactory->createPresenter('Discussion');
        $mockPresenter->autoCanonicalize = FALSE;

        $this->login();
        $this->authenticator->setId($this->login->id);
        $this->authenticator->setArr(["sessionKey" => $this->loginObj->getResult()->sessionKey]);
        $mockPresenter->getUser()->setAuthenticator($this->authenticator);
        $mockPresenter->getUser()->setExpiration('2 minutes');
        $mockPresenter->getUser()->login($GLOBALS["testedTeam"]["user"], $GLOBALS["testedTeam"]["pass"]);

        $discussionsObj = new \Tymy\Discussions($mockPresenter->tapiAuthenticator, $mockPresenter);
        $discussionsObj->fetch();
        Assert::true(is_object($discussionsObj));
        Assert::true(is_object($discussionsObj->result));
        Assert::type("string",$discussionsObj->result->status);
        Assert::same("OK",$discussionsObj->result->status);
        Assert::type("array",$discussionsObj->result->data);
        
        foreach ($discussionsObj->result->data as $dis) {
            Assert::type("int",$dis->id);
            Assert::type("string",$dis->caption);
            Assert::type("string",$dis->description);
            Assert::type("string",$dis->readRightName);
            Assert::type("string",$dis->writeRightName);
            Assert::type("string",$dis->deleteRightName);
            Assert::type("string",$dis->stickyRightName);
            Assert::type("bool",$dis->publicRead);
            Assert::type("string",$dis->status);
            Assert::same("ACTIVE",$dis->status);
            Assert::type("bool",$dis->editablePosts);
            Assert::type("int",$dis->order);
            Assert::type("bool",$dis->canRead);
            Assert::type("bool",$dis->canWrite);
            Assert::type("bool",$dis->canDelete);
            Assert::type("bool",$dis->canStick);
            Assert::true(!property_exists($dis, "newInfo"));
        }
    }
    
    function testFetchWithNew() {
        $presenterFactory = $this->container->getByType('Nette\Application\IPresenterFactory');
        $mockPresenter = $presenterFactory->createPresenter('Discussion');
        $mockPresenter->autoCanonicalize = FALSE;

        $this->login();
        $this->authenticator->setId($this->login->id);
        $this->authenticator->setArr(["sessionKey" => $this->loginObj->getResult()->sessionKey]);
        $mockPresenter->getUser()->setAuthenticator($this->authenticator);
        $mockPresenter->getUser()->setExpiration('2 minutes');
        $mockPresenter->getUser()->login($GLOBALS["testedTeam"]["user"], $GLOBALS["testedTeam"]["pass"]);

        $discussionsObj = new \Tymy\Discussions($mockPresenter->tapiAuthenticator, $mockPresenter);
        $discussionsObj->setWithNew(TRUE)
                ->fetch();

        Assert::true(is_object($discussionsObj));
        Assert::true(is_object($discussionsObj->result));
        Assert::type("string",$discussionsObj->result->status);
        Assert::same("OK",$discussionsObj->result->status);
        Assert::type("array",$discussionsObj->result->data);
        
        foreach ($discussionsObj->result->data as $dis) {
            Assert::type("int",$dis->id);
            Assert::type("string",$dis->caption);
            Assert::type("string",$dis->description);
            Assert::type("string",$dis->readRightName);
            Assert::type("string",$dis->writeRightName);
            Assert::type("string",$dis->deleteRightName);
            Assert::type("string",$dis->stickyRightName);
            Assert::type("bool",$dis->publicRead);
            Assert::type("string",$dis->status);
            Assert::same("ACTIVE",$dis->status);
            Assert::type("bool",$dis->editablePosts);
            Assert::type("int",$dis->order);
            Assert::type("bool",$dis->canRead);
            Assert::type("bool",$dis->canWrite);
            Assert::type("bool",$dis->canDelete);
            Assert::type("bool",$dis->canStick);
            if ($dis->newPosts > 0) {
                Assert::true(is_object($dis->newInfo));
                Assert::type("int", $dis->newInfo->newsCount);
                Assert::type("int", $dis->newInfo->discussionId);
                Assert::same($dis->id, $dis->newInfo->discussionId);
                Assert::type("string", $dis->newInfo->lastVisit);
                Assert::same(1, preg_match_all($GLOBALS["dateRegex"], $dis->newInfo->lastVisit)); //timezone correction check
            }
        }
    }

}

$test = new APIDiscussionsTest($container);
$test->run();