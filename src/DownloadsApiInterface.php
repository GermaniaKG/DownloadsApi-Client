<?php
namespace Germania\DownloadsApi;


interface DownloadsApiInterface
{

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
     * Returns an authentication ID for identification purposes.
     *
     * @return string
     */
    public function getAuthentication() : string;
}
