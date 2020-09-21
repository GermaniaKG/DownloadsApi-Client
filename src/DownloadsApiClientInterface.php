<?php
namespace Germania\DownloadsApiClient;


interface DownloadsApiClientInterface
{
    public function all() : iterable ;
    public function latest() : iterable;
}
