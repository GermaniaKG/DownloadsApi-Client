<?php
namespace Germania\DownloadsApi;

trait AuthenticationTrait
{


    /**
     * Authentication token value to be used as Bearer token.
     *
     * @var string
     */
    protected $auth_token;



    /**
     * @return string API key
     */
    public function getAuthentication( ) : string
    {
        return $this->auth_token;
    }


    /**
     * @param string $key API key
     */
    public function setAuthentication(  ?string $key ) : self
    {
        $this->auth_token = (string) $key;
        return $this;
    }
}
