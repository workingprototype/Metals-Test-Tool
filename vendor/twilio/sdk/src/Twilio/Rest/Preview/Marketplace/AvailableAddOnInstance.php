<?php

/**
 * This code was generated by
 * ___ _ _ _ _ _    _ ____    ____ ____ _    ____ ____ _  _ ____ ____ ____ ___ __   __
 *  |  | | | | |    | |  | __ |  | |__| | __ | __ |___ |\ | |___ |__/ |__|  | |  | |__/
 *  |  |_|_| | |___ | |__|    |__| |  | |    |__] |___ | \| |___ |  \ |  |  | |__| |  \
 *
 * Twilio - Preview
 * This is the public Twilio REST API.
 *
 * NOTE: This class is auto generated by OpenAPI Generator.
 * https://openapi-generator.tech
 * Do not edit the class manually.
 */


namespace Twilio\Rest\Preview\Marketplace;

use Twilio\Exceptions\TwilioException;
use Twilio\InstanceResource;
use Twilio\Values;
use Twilio\Version;
use Twilio\Rest\Preview\Marketplace\AvailableAddOn\AvailableAddOnExtensionList;


/**
 * @property string|null $sid
 * @property string|null $friendlyName
 * @property string|null $description
 * @property string|null $pricingType
 * @property array|null $configurationSchema
 * @property string|null $url
 * @property array|null $links
 */
class AvailableAddOnInstance extends InstanceResource
{
    protected $_extensions;

    /**
     * Initialize the AvailableAddOnInstance
     *
     * @param Version $version Version that contains the resource
     * @param mixed[] $payload The response payload
     * @param string $sid The SID of the AvailableAddOn resource to fetch.
     */
    public function __construct(Version $version, array $payload, string $sid = null)
    {
        parent::__construct($version);

        // Marshaled Properties
        $this->properties = [
            'sid' => Values::array_get($payload, 'sid'),
            'friendlyName' => Values::array_get($payload, 'friendly_name'),
            'description' => Values::array_get($payload, 'description'),
            'pricingType' => Values::array_get($payload, 'pricing_type'),
            'configurationSchema' => Values::array_get($payload, 'configuration_schema'),
            'url' => Values::array_get($payload, 'url'),
            'links' => Values::array_get($payload, 'links'),
        ];

        $this->solution = ['sid' => $sid ?: $this->properties['sid'], ];
    }

    /**
     * Generate an instance context for the instance, the context is capable of
     * performing various actions.  All instance actions are proxied to the context
     *
     * @return AvailableAddOnContext Context for this AvailableAddOnInstance
     */
    protected function proxy(): AvailableAddOnContext
    {
        if (!$this->context) {
            $this->context = new AvailableAddOnContext(
                $this->version,
                $this->solution['sid']
            );
        }

        return $this->context;
    }

    /**
     * Fetch the AvailableAddOnInstance
     *
     * @return AvailableAddOnInstance Fetched AvailableAddOnInstance
     * @throws TwilioException When an HTTP error occurs.
     */
    public function fetch(): AvailableAddOnInstance
    {

        return $this->proxy()->fetch();
    }

    /**
     * Access the extensions
     */
    protected function getExtensions(): AvailableAddOnExtensionList
    {
        return $this->proxy()->extensions;
    }

    /**
     * Magic getter to access properties
     *
     * @param string $name Property to access
     * @return mixed The requested property
     * @throws TwilioException For unknown properties
     */
    public function __get(string $name)
    {
        if (\array_key_exists($name, $this->properties)) {
            return $this->properties[$name];
        }

        if (\property_exists($this, '_' . $name)) {
            $method = 'get' . \ucfirst($name);
            return $this->$method();
        }

        throw new TwilioException('Unknown property: ' . $name);
    }

    /**
     * Provide a friendly representation
     *
     * @return string Machine friendly representation
     */
    public function __toString(): string
    {
        $context = [];
        foreach ($this->solution as $key => $value) {
            $context[] = "$key=$value";
        }
        return '[Twilio.Preview.Marketplace.AvailableAddOnInstance ' . \implode(' ', $context) . ']';
    }
}

