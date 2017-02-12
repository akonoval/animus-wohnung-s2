<?php

// src/AppBundle/Entity/Tokens.php
namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="tokens")
 */
class Tokens
{

    /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @ORM\Column(type="integer")
     */
    private $aid;

    /**
     * @ORM\Column(type="string", length=100)
     */
    private $token;


    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Get aid
     *
     * @return integer
     */
    public function getAid()
    {
        return $this->aid;
    }
    
    /**
     * Set token
     *
     * @param string $token
     *
     * @return Tokens
     */
    public function setToken($token)
    {
        $this->token = $token;

        return $this;
    }

    /**
     * Get token
     *
     * @return string
     */
    public function getToken()
    {
        return $this->token;
    }

    /**
     * Set appartment id
     *
     * @param integer $aid
     *
     * @return Tokens
     */
    public function setAid($id)
    {
        $this->aid = $id;

        return $this;
    }
    
    /**
     * Check the id
     *
     * @param integer $id
     *
     * @return boolean
     */
    public function validate($id)
    {
        $aid = $this->getAid();
        
        $res = FALSE;
        if ($aid == $id)
        {
            $res = TRUE;
        }

        return $res;
    }
}
