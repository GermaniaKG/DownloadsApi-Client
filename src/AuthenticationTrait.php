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
     * @return string Authentication token
     */
    public function getAuthentication( ) : string
    {
        return $this->auth_token;
    }


    /**
     * @param string $auth_token Authentication token
     */
    public function setAuthentication( string $auth_token ) : self
    {
        $this->auth_token = $auth_token;
        return $this;
    }
}
