<?php
namespace Germania\DownloadsApi;


interface DownloadsApiInterface
{

    /**
     * The DownloadsApi Base Url.
     *
     * Mind the trailing slash!
     *
     * @var string
     */
    const BASE_URL = "https://documents.germania-kg.com/v0/";


    /**
     * @return iterable
     *
     * @throws \Germania\DownloadsApi\DownloadsApiExceptionInterface.
     */
    public function request( string $path ) : iterable ;


    /**
     * Returns all documents, optionally filtered by parameters.
     *
     * @return iterable
     *
     * @throws \Germania\DownloadsApi\DownloadsApiExceptionInterface.
     */
    public function all() : iterable ;


    /**
     * Returns latest documents, optionally filtered by parameters.
     *
     * @return iterable
     *
     * @throws \Germania\DownloadsApi\DownloadsApiExceptionInterface.
     */
    public function latest() : iterable;



    /**
     * @return string Authentication
     */
    public function getAuthentication( ) : string;


    /**
     * @param string $auth_token Authentication token
     */
    public function setAuthentication( string $auth_token ) : DownloadsApiInterface;

}
