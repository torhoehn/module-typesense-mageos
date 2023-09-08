<?php
declare(strict_types=1);

namespace MageOS\TypeSense\Setup;

use Magento\AdvancedSearch\Model\Client\ClientResolver;
use Magento\Search\Model\SearchEngine\ValidatorInterface;

/**
 * Validate Search engine connection
 */
class Validator implements ValidatorInterface
{
    public function __construct(private ClientResolver $clientResolver)
    {
    }

    public function validate(): array
    {
        $errors = [];
        try {
            $client = $this->clientResolver->create();
            if (!$client->testConnection()) {
                $engine = $this->clientResolver->getCurrentEngine();
                $errors[] = "Could not validate a connection to the Search engine: $engine."
                    . ' Verify that the host and port are configured correctly.';
            }
        } catch (\Exception $e) {
            $errors[] = 'Could not validate a connection to the Typesense. ' . $e->getMessage();
        }
        return $errors;
    }
}
