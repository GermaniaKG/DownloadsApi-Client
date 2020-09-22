<?php
namespace Germania\DownloadsApiClient;

use Germania\JsonDecoder\JsonDecoder;
use Psr\Http\Message\ResponseInterface;

class JsonDataArrayResponseDecoder
{

    /**
     * @param  ResponseInterface $response
     * @return array
     *
     * @throws ApiClientExceptionInterface
     * @throws ApiClientUnexpectedValueException
     */
    public function __invoke( ResponseInterface $response ) : array
    {
        try {
            $response_body_decoded = (new JsonDecoder)($response, "associative");
        }
        catch (\JsonException $e) {
            throw new ApiClientUnexpectedValueException("Problems decoding JSON", 0, $e);
        }

        if (!isset( $response_body_decoded['data'] )):
            throw new ApiClientUnexpectedValueException("Missing 'data' element");
        endif;

        if (!is_array( $response_body_decoded['data'] )):
            throw new ApiClientUnexpectedValueException("Element 'data' is not array");
        endif;

        // JsonAPI.org standard: Data items have "attributes"
        $result = array_column($response_body_decoded['data'], "attributes");
        return $result;
    }


}
