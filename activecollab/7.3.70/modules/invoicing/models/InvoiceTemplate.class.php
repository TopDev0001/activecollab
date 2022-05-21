<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

use ActiveCollab\Module\Invoicing\InvoicingModule;
use ActiveCollab\Module\Invoicing\Wires\InvoiceLogoProxy;
use ActiveCollab\Module\System\Utils\JwtTokenIssuer\JwtTokenIssuerInterface;
use ActiveCollab\Module\System\Utils\OwnerCompanyResolver\OwnerCompanyResolverInterface;
use Angie\Error;
use Angie\Globalization;
use Angie\Inflector;

class InvoiceTemplate implements JsonSerializable
{
    /**
     * Default font.
     *
     * @var string
     */
    const DEFAULT_FONT = 'dejavusans';

    /**
     * Default CJK font.
     */
    const CJK_FONT = 'msungstdlight';

    /**
     * Settings.
     *
     * @var array
     */
    private $attributes;

    /**
     * Should we use cjk font.
     *
     * @var bool
     */
    private $use_cjk_font = false;

    /**
     * Logo uploaded file.
     *
     * @var UploadedFile
     */
    private $logo = null;

    /**
     * Constructor.
     *
     * @param bool $use_cjk_font
     */
    public function __construct($use_cjk_font = false)
    {
        $this->use_cjk_font = $use_cjk_font;
        $this->attributes = ConfigOptions::getValue('invoice_template');

        if (!$this->attributes) {
            $this->attributes = [
                // GENERAL
                'label' => $this->getLabel(),
                'paper_size' => $this->getPaperSize(),
                'logo_path' => $this->getLogoPath(),
                'logo_timestamp' => $this->getLogoTimestamp(),

                // HEADER
                'header_layout' => $this->getHeaderLayout(),
                'company_name' => $this->getCompanyName(),
                'company_details' => $this->getCompanyDetails(),
                'print_header_border' => $this->getPrintHeaderBorder(),

                // BODY
                'body_layout' => $this->getBodyLayout(),

                // FOOTER
                'print_footer' => $this->getPrintFooter(),
                'footer_layout' => $this->getFooterLayout(),
                'print_footer_border' => $this->getPrintFooterBorder(),
            ];
        }
    }

    /**
     * Set attributes.
     */
    public function setAttributes(array $attributes)
    {
        foreach ($attributes as $name => $value) {
            $method_name = Inflector::camelize('set_' . $name);

            if (method_exists(__CLASS__, $method_name)) {
                self::$method_name($value);
            }
        }
    }

    /**
     * Get attributes.
     *
     * @return array
     */
    public function getAttributes()
    {
        return $this->attributes;
    }

    /**
     * Get font.
     *
     * @return string
     */
    public function getFont()
    {
        return $this->use_cjk_font ? self::CJK_FONT : self::DEFAULT_FONT;
    }

    /**
     * Get global font size.
     *
     * @return int
     */
    public function getFontSize()
    {
        return '3.2';
    }

    /**
     * Get global font size.
     *
     * @return int
     */
    public function getLineHeight()
    {
        return '4.8';
    }

    /**
     * Get main border color.
     *
     * @return string
     */
    public function getMainBorderColor()
    {
        return '#cccccc';
    }

    /**
     * Return label.
     */
    public function getLabel()
    {
        return array_var($this->attributes, 'label');
    }

    /**
     * Set label.
     *
     * @param string $label
     */
    public function setLabel($label)
    {
        $this->attributes['label'] = $label;
    }

    /**
     * Return paper size.
     *
     * @return string
     */
    public function getPaperSize()
    {
        return array_var($this->attributes, 'paper_size', Globalization::PAPER_FORMAT_A4);
    }

    /**
     * set paper size.
     *
     * @param string $paper_size
     */
    public function setPaperSize($paper_size)
    {
        $this->attributes['paper_size'] = $paper_size;
    }

    /**
     * Get company name.
     *
     * @return string
     * @throws Error
     */
    public function getCompanyName()
    {
        $company_name = array_var($this->attributes, 'company_name');
        if ($company_name) {
            return $company_name;
        }

        $owner_company = AngieApplication::getContainer()
            ->get(OwnerCompanyResolverInterface::class)
                ->getCompany();

        return $owner_company->getName();
    }

    /**
     * Set company name.
     *
     * @param string $company_name
     */
    public function setCompanyName($company_name)
    {
        $this->attributes['company_name'] = $company_name;
    }

    /**
     * Get company details.
     *
     * @return string
     */
    public function getCompanyDetails()
    {
        $company_details = array_var($this->attributes, 'company_details');
        if ($company_details) {
            return $company_details;
        }

        $owner_company = AngieApplication::getContainer()
            ->get(OwnerCompanyResolverInterface::class)
                ->getCompany();

        $company_details = '';
        if ($owner_company->getAddress()) {
            $company_details .= "\n" . $owner_company->getAddress();
        }
        if ($owner_company->getPhone()) {
            $company_details .= "\n" . $owner_company->getPhone();
        }
        if ($owner_company->getHomepageUrl()) {
            $company_details .= "\n" . $owner_company->getHomepageUrl();
        }

        return $company_details;
    }

    /**
     * Set company details.
     *
     * @param string $company_details
     */
    public function setCompanyDetails($company_details)
    {
        $this->attributes['company_details'] = $company_details;
    }

    /**
     * Uses the background image.
     *
     * @param  string        $path
     * @return bool
     * @throws Error
     * @throws FileCopyError
     * @throws FileDnxError
     */
    public function useBackgroundImage($path)
    {
        if (!is_file($path)) {
            throw new FileDnxError($path);
        }

        $info = getimagesize($path);
        $mime_type = strtolower($info['mime']);

        if (!in_array($mime_type, ['image/png', 'image/jpg', 'image/jpeg'])) {
            throw new Error(lang('Background image should be PNG or JPEG image'));
        }

        if (!@copy($path, $this->getBackgroundImagePath())) {
            throw new FileCopyError($path, $this->getBackgroundImagePath());
        }

        return true;
    }

    /**
     * Remove background image.
     *
     * @return bool
     */
    public function removeBackgroundImage()
    {
        if ($this->hasBackgroundImage() && !@unlink($this->getBackgroundImagePath())) {
            throw new FileDeleteError($this->getBackgroundImagePath());
        }

        return true;
    }

    /**
     * Has background image.
     *
     * @return bool
     */
    public function hasBackgroundImage()
    {
        return is_file($this->getBackgroundImagePath());
    }

    /**
     * Get path to the background image.
     *
     * @return string
     */
    public function getBackgroundImagePath()
    {
        return PUBLIC_PATH . '/brand/invoice-background-image.png';
    }

    /**
     * Get url to the background image.
     *
     * @return string
     */
    public function getBackgroundImageUrl()
    {
        return '#';
    }

    /**
     * Get header layout.
     *
     * @return int
     */
    public function getHeaderLayout()
    {
        return array_var($this->attributes, 'header_layout', 0);
    }

    /**
     * Set header layout.
     *
     * @param int $layout
     */
    public function setHeaderLayout($layout)
    {
        $this->attributes['header_layout'] = $layout;
    }

    /**
     * Get path to the logo image.
     *
     * @return string
     */
    public function getLogoImagePath()
    {
        $logo_path = array_var($this->attributes, 'logo_path');

        if (empty($logo_path)) {
            return $this->getDefaultLogoPath();
        }

        try {
            /** @var WarehouseIntegration $warehouse_integration */
            $warehouse_integration = Integrations::findFirstByType(WarehouseIntegration::class);

            if (!$warehouse_integration->isInUse()) {
                $uploaded_file_location = AngieApplication::fileLocationToPath($logo_path);

                return is_file($uploaded_file_location) ? $uploaded_file_location : $this->getDefaultLogoPath();
            }

            $logo_path = urlencode($logo_path);
            $logo_md5 = array_var($this->attributes, 'logo_md5');

            if ($logo_path && preg_match('/^[a-f0-9]{32}$/i', $logo_md5)) {
                $download_path = $this->prepareLogoImagePath($logo_path, $logo_md5);

                if (is_file($download_path)) {
                    return $download_path;
                }

                return $this->downloadAndPrepareInvoiceLogo(
                    $logo_path,
                    $logo_md5,
                    $download_path,
                    $warehouse_integration
                );
            }

            return $this->getDefaultLogoPath();
        } catch (Throwable $e) {
            return $this->getDefaultLogoPath();
        }
    }

    private function getDefaultLogoPath(): string
    {
        return InvoicingModule::PATH . '/resources/invoice_default_logo.png';
    }

    private function downloadAndPrepareInvoiceLogo(
        string $logo_path,
        string $logo_md5,
        string $download_path,
        WarehouseIntegration $warehouse_integration
    ): string
    {
        $user = AngieApplication::authentication()->getLoggedUser();

        if (!$user instanceof User) {
            $user = new AnonymousUser('Invoice Recipient', 'recipient@example.com');
        }

        $download_token = AngieApplication::getContainer()
            ->get(JwtTokenIssuerInterface::class)
            ->issueDownloadToken($user);

        $file = file_get_contents(
            str_replace(
                '--DOWNLOAD-TOKEN--',
                $download_token,
                $warehouse_integration->prepareFileDownloadUrl(
                    urldecode($logo_path),
                    $logo_md5,
                    false
                )
            )
        );

        if (!$file) {
            throw new \RuntimeException('Failed to download file.');
        }

        file_put_contents($download_path, $file);

        scale_image_and_force_size(
            $download_path,
            $download_path. '-THUMB',
            120,
            80,
            IMAGETYPE_JPEG,
            100
        );

        return $download_path;
    }

    private function prepareLogoImagePath($logo_path, $logo_md5): string
    {
        return sprintf('%s/instance-%d-invoice-logo-%s.%s',
            WORK_PATH,
            AngieApplication::getAccountId(),
            $logo_md5,
            get_file_extension($logo_path)
        );
    }

    /**
     * Get logo image height in pm for CSS.
     *
     * @param  float $unit_divide_value - value which will be divided with image height (ex. px/mm, px/cm ...)
     * @param  float $height_limit
     * @return float
     */
    public function getLogoImageHeight($unit_divide_value, $height_limit)
    {
        $image_size_data = getimagesize($this->getLogoImagePath());
        $image_size = $image_size_data[1] / $unit_divide_value;

        if ($image_size > $height_limit) {
            $image_size = $height_limit;
        }

        return $image_size;
    }

    /**
     * Get url to the background image.
     *
     * @return string
     */
    public function getLogoImageUrl()
    {
        $params = [
            'width' => '--WIDTH--',
            'height' => '--HEIGHT--',
            'scale' => '--SCALE--',
        ];

        if (isset($this->attributes['logo_timestamp']) && $this->attributes['logo_timestamp']) {
            $params['timestamp'] = $this->attributes['logo_timestamp'];
        }

        return AngieApplication::getProxyUrl(
            InvoiceLogoProxy::class,
            InvoicingModule::NAME,
            $params
        );
    }

    /**
     * Get time of uploaded logo.
     *
     * @return mixed
     */
    public function getLogoTimestamp()
    {
        return array_var($this->attributes, 'logo_timestamp', 0);
    }

    /**
     * Set time when logo was uploaded.
     *
     * @param $value
     */
    public function setLogoTimestamp($value)
    {
        $this->attributes['logo_timestamp'] = $value;
    }

    /**
     * Return uploaded file path.
     *
     * @return mixed
     */
    public function getLogoPath()
    {
        return array_var($this->attributes, 'logo_path');
    }

    /**
     * Set uploaded file path.
     *
     * @param $value
     */
    public function setLogoPath($value)
    {
        $this->attributes['logo_path'] = $value;
    }

    /**
     * Return uploaded file md5.
     *
     * @return mixed
     */
    public function getLogoMd5()
    {
        return array_var($this->attributes, 'logo_md5');
    }

    /**
     * Set uploaded file md5.
     *
     * @param $value
     */
    public function setLogoMd5($value)
    {
        $this->attributes['logo_md5'] = $value;
    }

    /**
     * Set logo from uploaded file.
     */
    public function setLogo(UploadedFile $file)
    {
        $this->logo = $file;
    }

    /**
     * Return true if image with the give name and mime type can be used as an invoice logo.
     *
     * @param  string $file_name
     * @param  string $mime_type
     * @param  int    $file_size
     * @return bool
     */
    public function isSupportedLogoImage($file_name, $mime_type, $file_size)
    {
        $file_size = (int) $file_size;

        if ($file_size <= 0 || $file_size >= 3145728) {
            return false; // We need a file with content that is not larger than 3MB
        }

        if (in_array(strtolower($mime_type), ['image/png', 'image/jpeg'])) {
            return true;
        }

        $path_info = pathinfo($file_name);

        if (!empty($path_info['extension']) && in_array(strtolower($path_info['extension']), ['png', 'jpg', 'jpeg'])) {
            return true;
        }

        return false;
    }

    /**
     * Set print header border.
     *
     * @return bool
     */
    public function getPrintHeaderBorder()
    {
        return array_var($this->attributes, 'print_header_border', true);
    }

    /**
     * Set print header border.
     *
     * @param bool $print_border
     */
    public function setPrintHeaderBorder($print_border)
    {
        $this->attributes['print_header_border'] = $print_border;
    }

    /**
     * Get body layout.
     *
     * @return int
     */
    public function getBodyLayout()
    {
        return array_var($this->attributes, 'body_layout', 0);
    }

    /**
     * Set body layout.
     *
     * @param int $layout
     */
    public function setBodyLayout($layout)
    {
        $this->attributes['body_layout'] = $layout;
    }

    /**
     * Set print logo.
     *
     * @return bool
     */
    public function getPrintFooter()
    {
        return array_var($this->attributes, 'print_footer', true);
    }

    /**
     * Set print logo.
     *
     * @param bool $print_footer
     */
    public function setPrintFooter($print_footer)
    {
        $this->attributes['print_footer'] = $print_footer;
    }

    /**
     * Get footer layout.
     *
     * @return int
     */
    public function getFooterLayout()
    {
        return array_var($this->attributes, 'footer_layout', 0);
    }

    /**
     * Set footer layout.
     *
     * @param int $layout
     */
    public function setFooterLayout($layout)
    {
        $this->attributes['footer_layout'] = $layout;
    }

    /**
     * Set print footer border.
     *
     * @return bool
     */
    public function getPrintFooterBorder()
    {
        return array_var($this->attributes, 'print_footer_border', true);
    }

    /**
     * Set print footer border.
     *
     * @param bool $print_border
     */
    public function setPrintFooterBorder($print_border)
    {
        $this->attributes['print_footer_border'] = $print_border;
    }

    /**
     * Get Totals Columns Count.
     */
    public function getTotalColumnsCount()
    {
        return 7;
    }

    /**
     * Save the configuration.
     *
     * @throws Exception
     */
    public function save()
    {
        try {
            DB::beginWork('Begin: save invoice template settings @ ' . __CLASS__);

            if ($this->logo instanceof UploadedFile) {
                $current_logo_location = $this->getLogoPath();
                $warehouse_integration = Integrations::findFirstByType(WarehouseIntegration::class);
                $current_logo_type = !$warehouse_integration->isInUse() ? LocalFile::class : WarehouseFile::class;

                $this->setLogoPath($this->logo->getLocation());
                $this->setLogoMd5($this->logo->getMd5());
                $this->setLogoTimestamp($this->logo->getCreatedOn()->getTimestamp());

                // delete old logo if it exists
                if ($current_logo_location) {
                    AngieApplication::storage()->deleteFile(
                        $current_logo_type,
                        $current_logo_location
                    );
                }

                $this->logo->keepFileOnDelete(true);
                $this->logo->delete();
            }

            ConfigOptions::setValue('invoice_template', $this->attributes);
            Invoices::bulkUpdateOn();
            Estimates::bulkUpdateOn();

            DB::commit('Done: invoice template saved @ ' . __CLASS__);
        } catch (Exception $e) {
            DB::rollback('Rollback: save invoice template settings @ ' . __CLASS__);

            throw $e;
        }
    }

    /**
     * Return array or property => value pairs that describes this object.
     */
    public function jsonSerialize(): array
    {
        $result = $this->attributes;

        if ($this->hasBackgroundImage()) {
            $result['background_image'] = $this->getBackgroundImageUrl();
        }

        if (!isset($result['label'])) {
            $result['label'] = null;
        }

        // don't need to send system logo image path and time of creation to output
        unset($result['logo_path'], $result['logo_timestamp']);

        // instead we give absolute url path to logo image
        $result['logo'] = $this->getLogoImageUrl();

        return $result;
    }

    public function canView(User $user): bool
    {
        return true;
    }

    public function canEdit(User $user): bool
    {
        return $user->isOwner();
    }
}
