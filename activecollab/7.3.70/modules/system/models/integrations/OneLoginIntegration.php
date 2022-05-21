<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

class OneLoginIntegration extends Integration
{
    public function isSingleton(): bool
    {
        return true;
    }

    public function getGroup()
    {
        return 'authentication';
    }

    public function getName()
    {
        return 'OneLogin';
    }

    public function getShortName(): string
    {
        return 'one-login';
    }

    public function getDescription()
    {
        return lang('OneLogin description'); // @TODO: add description
    }

    /**
     * @return string|null
     */
    public function getEntityId()
    {
        return $this->getAdditionalProperty('entity_id');
    }

    /**
     * @param  string $entity_id
     * @return mixed
     */
    public function setEntityId($entity_id)
    {
        return $this->setAdditionalProperty('entity_id', $entity_id);
    }

    /**
     * @return string|null
     */
    public function getSingleSignOnService()
    {
        return $this->getAdditionalProperty('single_sign_on_service');
    }

    /**
     * @param  string $single_sign_on_service
     * @return mixed
     */
    public function setSingleSignOnService($single_sign_on_service)
    {
        return $this->setAdditionalProperty('single_sign_on_service', $single_sign_on_service);
    }

    /**
     * @return string|null
     */
    public function getSingleLogoutService()
    {
        return $this->getAdditionalProperty('single_logout_service');
    }

    /**
     * @param  string $single_logout_service
     * @return mixed
     */
    public function setSingleLogoutService($single_logout_service)
    {
        return $this->setAdditionalProperty('single_logout_service', $single_logout_service);
    }

    /**
     * @return string|null
     */
    public function getX509cert()
    {
        return $this->getAdditionalProperty('x509cert');
    }

    /**
     * @param  string $x509cert
     * @return mixed
     */
    public function setX509cert($x509cert)
    {
        return $this->setAdditionalProperty('x509cert', $x509cert);
    }

    /**
     * @return bool
     */
    public function getIsEnable()
    {
        return $this->getAdditionalProperty('is_enable');
    }

    /**
     * @param  bool  $is_enable
     * @return mixed
     */
    public function setIsEnable($is_enable)
    {
        return $this->setAdditionalProperty('is_enable', (bool) $is_enable);
    }

    public function jsonSerialize(): array
    {
        $array = array_merge(parent::jsonSerialize(), [
            'has_set_params' => $this->hasSetParams(),
            'is_enabled' => $this->getIsEnable(),
        ]);

        if ($this->hasSetParams()) {
            $array = array_merge($array, [
                'entity_id' => $this->getEntityId(),
                'single_sign_on_service' => $this->getSingleSignOnService(),
                'single_logout_service' => $this->getSingleLogoutService(),
            ]);
        }

        return $array;
    }

    /**
     * Return true if credentials are set.
     *
     * @return bool
     */
    private function hasSetParams()
    {
        return $this->getEntityId() && $this->getSingleSignOnService() && $this->getSingleLogoutService() && $this->getX509cert();
    }

    /**
     * Save credentials from xml file.
     *
     * @return $this
     * @throws Exception
     */
    public function setCredentials(UploadedFile $file)
    {
        if ($file instanceof LocalUploadedFile) {
            $file_path = AngieApplication::fileLocationToPath($file->getLocation());
        } else {
            /* @var WarehouseIntegration $warehouse_integration */
            $warehouse_integration = Integrations::findFirstByType(WarehouseIntegration::class);
            $file_path = $warehouse_integration->getFileApi()->getFileInfo($file->getLocation());
        }

        $xml_attribute = function ($object, $attribute) {
            if (isset($object[$attribute])) {
                return (string) $object[$attribute];
            }
        };

        $xml = simplexml_load_file($file_path);

        $entity_id = $xml_attribute($xml, 'entityID');
        $logout_service = $xml_attribute($xml->IDPSSODescriptor->SingleLogoutService, 'Location');

        $login_service = null;
        foreach ($xml->IDPSSODescriptor->SingleSignOnService as $service) {
            $binding = $xml_attribute($service, 'Binding');

            if (strpos($binding, 'HTTP-POST')) {
                $login_service = $xml_attribute($service, 'Location');
                break;
            }
        }

        $certificate = null;
        foreach ($xml->IDPSSODescriptor->KeyDescriptor->xpath('//ds:KeyInfo') as $key) {
            foreach ($key->xpath('//ds:X509Certificate') as $cert) {
                $certificate = $xml_attribute($cert, 0);
            }
        }

        if ($certificate !== null && $entity_id !== null && $login_service !== null && $logout_service !== null) {
            $this->setEntityId($entity_id);
            $this->setSingleSignOnService($login_service);
            $this->setSingleLogoutService($logout_service);
            $this->setX509cert($certificate);
            $this->save();

            return $this;
        } else {
            throw new Exception('Params from XML file not founded');
        }
    }

    /**
     * Enable integration.
     */
    public function enable()
    {
        try {
            // @TODO change this line with UserSessions::delete()
            DB::execute('DELETE FROM api_subscriptions'); // delete all users sessions

            $this->setIsEnable(true);
            $this->save();

            return $this;
        } catch (Exception $e) {
            throw new Exception('Enable Failed');
        }
    }

    /**
     * Disable integration.
     */
    public function disable()
    {
        try {
            // @TODO change this line with UserSessions::delete()
            DB::execute('DELETE FROM api_subscriptions'); // delete all users sessions

            $this->setIsEnable(false);
            $this->save();

            return $this;
        } catch (Exception $e) {
            throw new Exception('Disable Failed');
        }
    }
}
