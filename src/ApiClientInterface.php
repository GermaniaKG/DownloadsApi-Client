<?php
namespace Germania\DownloadsApiClient;


interface ApiClientInterface
{
    public function all() : iterable ;
    public function latest() : iterable;

    /**
     * Returns an authentication ID for identification purposes.
     *
     * @return string
     */
    public function getAuthentication() : string;
}
