<?php
namespace Germania\DownloadsApiClient;


interface ApiClientInterface
{
    public function all() : iterable ;
    public function latest() : iterable;
}
