<?php
/**
 * TEST: Test Events on TYMY api
 * 
 */

namespace Test;

use Nette;
use Tester;
use Tester\Assert;

$container = require __DIR__ . '/bootstrap.php';

if (in_array(basename(__FILE__, '.phpt') , $GLOBALS["testedTeam"]["skips"])) {
    Tester\Environment::skip('Test skipped as set in config file.');
}

class APIEventsTest extends TapiTestCase {

    private $authenticator;
    private $container;

    function __construct(Nette\DI\Container $container) {
        $this->container = $container;
    }
    
    function setUp() {
        parent::setUp();
        $this->initTapiConfiguration($this->container);
        $this->authenticator = new \App\Model\TestAuthenticator();
    }
    
    function tearDown() {
        parent::tearDown();
    }

    /**
     * @throws Nette\Application\AbortException
     */
    function testFetchNotLoggedInFails404() {
        $presenterFactory = $this->container->getByType('Nette\Application\IPresenterFactory');
        $mockPresenter = $presenterFactory->createPresenter('Homepage');
        $mockPresenter->autoCanonicalize = FALSE;

        $this->authenticator->setId(38);
        $this->authenticator->setStatus(["TESTROLE", "TESTROLE2"]);
        $this->authenticator->setArr(["sessionKey" => "dsfbglsdfbg13546"]);

        $mockPresenter->getUser()->setAuthenticator($this->authenticator);
        $mockPresenter->getUser()->login("test", "test");

        $eventsObj = new \Tymy\Events();
        $eventsObj->setSupplier($this->supplier)
                ->setPresenter($mockPresenter)
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


        $eventsObj = new \Tymy\Events();
        $eventsObj->setPresenter($mockPresenter)
                ->fetch();
    }
    
    function testFetchSuccess() {
        $presenterFactory = $this->container->getByType('Nette\Application\IPresenterFactory');
        $mockPresenter = $presenterFactory->createPresenter('Event');
        $mockPresenter->autoCanonicalize = FALSE;

        $this->login();
        $this->authenticator->setId($this->login->id);
        $this->authenticator->setArr(["sessionKey" => $this->loginObj->getResult()->sessionKey]);
        $mockPresenter->getUser()->setAuthenticator($this->authenticator);
        $mockPresenter->getUser()->setExpiration('2 minutes');
        $mockPresenter->getUser()->login($GLOBALS["testedTeam"]["user"], $GLOBALS["testedTeam"]["pass"]);

        $eventsObj = new \Tymy\Events($mockPresenter->tapiAuthenticator, $mockPresenter);
        $eventsObj->fetch();
        
        Assert::same(1, count($eventsObj->getUriParams()));
        
        Assert::true(is_object($eventsObj));
        Assert::true(is_object($eventsObj->result));
        Assert::type("string",$eventsObj->result->status);
        Assert::same("OK",$eventsObj->result->status);
        
        Assert::type("array",$eventsObj->result->data);
        
        foreach ($eventsObj->result->data as $ev) {
            Assert::true(is_object($ev));
            Assert::type("int",$ev->id);
            Assert::type("string",$ev->caption);
            Assert::type("string",$ev->type);
            Assert::type("string",$ev->description);
            Assert::type("string",$ev->closeTime);
            Assert::type("string",$ev->startTime);
            Assert::type("string",$ev->endTime);
            Assert::type("string",$ev->link);
            Assert::type("string",$ev->place);
            Assert::type("bool",$ev->canView);
            Assert::type("bool",$ev->canPlan);
            Assert::type("bool",$ev->canResult);
            Assert::type("bool",$ev->inPast);
            Assert::type("bool",$ev->inFuture);
            Assert::true(!property_exists($ev, "myAttendance"));
        }
    }
    
    function testFetchFilter() {
        $presenterFactory = $this->container->getByType('Nette\Application\IPresenterFactory');
        $mockPresenter = $presenterFactory->createPresenter('Event');
        $mockPresenter->autoCanonicalize = FALSE;

        $this->login();
        $this->authenticator->setId($this->login->id);
        $this->authenticator->setArr(["sessionKey" => $this->loginObj->getResult()->sessionKey]);
        $mockPresenter->getUser()->setAuthenticator($this->authenticator);
        $mockPresenter->getUser()->setExpiration('2 minutes');
        $mockPresenter->getUser()->login($GLOBALS["testedTeam"]["user"], $GLOBALS["testedTeam"]["pass"]);

        $eventsObj = new \Tymy\Events($mockPresenter->tapiAuthenticator, $mockPresenter);
        $eventsObj->fetch();
        
        Assert::same(1, count($eventsObj->getUriParams()));
        Assert::contains("TSID",array_keys($eventsObj->getUriParams()));
        
        $eventsObj = new \Tymy\Events($mockPresenter->tapiAuthenticator, $mockPresenter);
        $eventsObj->from("20160202")
                ->fetch();
        
        Assert::same(2, count($eventsObj->getUriParams()));
        Assert::contains("filter",array_keys($eventsObj->getUriParams()));
        Assert::contains("TSID",array_keys($eventsObj->getUriParams()));
        Assert::contains("startTime>20160202",$eventsObj->getUriParams());
        
        $eventsObj = new \Tymy\Events($mockPresenter->tapiAuthenticator, $mockPresenter);
        $eventsObj->to("20170202")
                ->fetch();
        
        Assert::same(2, count($eventsObj->getUriParams()));
        Assert::contains("filter",array_keys($eventsObj->getUriParams()));
        Assert::contains("TSID",array_keys($eventsObj->getUriParams()));
        Assert::contains("startTime<20170202",$eventsObj->getUriParams());
        
        $eventsObj = new \Tymy\Events($mockPresenter->tapiAuthenticator, $mockPresenter);
        $eventsObj->from("20160202")
                ->to("20170202")
                ->fetch();
        
        Assert::same(2, count($eventsObj->getUriParams()));
        Assert::contains("filter",array_keys($eventsObj->getUriParams()));
        Assert::contains("TSID",array_keys($eventsObj->getUriParams()));
        Assert::contains("startTime>20160202~startTime<20170202",$eventsObj->getUriParams());
        
        $eventsObj = new \Tymy\Events($mockPresenter->tapiAuthenticator, $mockPresenter);
        $eventsObj->from("20160202")
                ->to("20170202")
                ->withMyAttendance(TRUE)
                ->fetch();
        
        Assert::same(2, count($eventsObj->getUriParams()));
        Assert::contains("filter",array_keys($eventsObj->getUriParams()));
        Assert::contains("TSID",array_keys($eventsObj->getUriParams()));
        Assert::contains("startTime>20160202~startTime<20170202",$eventsObj->getUriParams());

        foreach ($eventsObj->result->data as $ev) {
            Assert::true(is_object($ev));
            Assert::true(property_exists($ev, "myAttendance"));
            Assert::type("string",$ev->myAttendance->preStatus);
            Assert::type("string",$ev->myAttendance->preDescription);
            Assert::type("string",$ev->myAttendance->postStatus);
            Assert::type("string",$ev->myAttendance->postDescription);
        }
    }

}

$test = new APIEventsTest($container);
$test->run();
