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
     * @throws \Germania\DownloadsApi\Exceptions\DownloadsApiExceptionInterface
     */
    public function request(string $path, array $filters = array()) : iterable ;


    /**
     * Returns all documents, optionally filtered by parameters.
     *
     * @return iterable
     *
     * @throws \Germania\DownloadsApi\Exceptions\DownloadsApiExceptionInterface
     */
    public function all(array $filters = array()) : iterable ;


    /**
     * Returns latest documents, optionally filtered by parameters.
     *
     * @return iterable
     *
     * @throws \Germania\DownloadsApi\Exceptions\DownloadsApiExceptionInterface
     */
    public function latest(array $filters = array()) : iterable;



    /**
     * @return string API Key
     */
    public function getAuthentication() : string;


    /**
     * @param string|null $key API Key
     */
    public function setAuthentication(?string $key) : DownloadsApiInterface;
}
