<?php

namespace App\Model;

use Nette;

/**
 * Users management.
 */
class TestAuthenticator implements Nette\Security\IAuthenticator {

    private $id;
    private $status;
    private $arr;
    
    /** @var Supplier */
    private $supplier;
    
    public function __construct(Supplier $supplier) {
        $this->supplier = $supplier;
    }
    
    /**
     * Performs an authentication.
     * @return Nette\Security\Identity
     * @throws Nette\Security\AuthenticationException
     */
    public function authenticate(array $credentials) {
        list($username, $password) = $credentials;
        $this->setId(38);
        $this->setStatus(["TESTROLE", "TESTROLE2"]);
        $this->setArr([
            "tym" => "testteam", 
            "sessionKey" => "dsfbglsdfbg13546",
            "hash" => "123hash",
            "tapi_config" => $this->supplier->getTapi_config(),
            "login" => $username
            ]);
        return new Nette\Security\Identity($this->id, $this->status, $this->arr );
    }
    
    public function setId($id){
        $this->id = $id;
    }
    
    public function setStatus($status){
        $this->status = $status;
    }
    
    public function setArr($arr){
        $this->arr = $arr;
    }
}