<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

namespace ActiveCollab\Module\Invoicing\Wires;

use ActiveCollab\Foundation\App\Proxy\ProxyRequestHandler;

class InvoiceLogoProxy extends ProxyRequestHandler
{
    const SCALE = 'scale'; // Proportionally scale down to the given dimensions

    /**
     * Image width (in px).
     *
     * @var int
     */
    protected $width;

    /**
     * Image height (in px).
     *
     * @var int
     */
    protected $height;

    /**
     * Scaling method.
     *
     * @var int
     */
    protected $scale;
    /**
     * @var string
     */
    private $default_invoice_logo_tag = '';

    /**
     * Construct proxy request handler.
     *
     * @param array $params
     */
    public function __construct($params = null)
    {
        $this->width = isset($params['width']) && $params['width'] ? (int) $params['width'] : 0;
        $this->height = isset($params['height']) && $params['height'] ? (int) $params['height'] : 0;
        $this->scale = isset($params['scale']) && $params['scale'] ? $params['scale'] : self::SCALE;
    }

    /**
     * Handle request based on provided data.
     */
    public function execute()
    {
        require_once ANGIE_PATH . '/functions/general.php';
        require_once ANGIE_PATH . '/functions/web.php';
        require_once ANGIE_PATH . '/functions/files.php';

        if ($connection = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME)) {
            $connection->set_charset('utf8mb4');

            if ($result = $connection->query(sprintf("SELECT value FROM config_options WHERE name='%s'", 'invoice_template'))) {
                if ($value = $result->fetch_assoc()['value']) {
                    $invoice_template = unserialize($value);

                    if ($logo_path = array_var($invoice_template, 'logo_path')) {
                        $result = $connection->query(sprintf("SELECT raw_additional_properties FROM integrations WHERE type='%s'", 'WarehouseIntegration'));
                        $warehouse_integrations = $result->fetch_assoc()['raw_additional_properties'];

                        if ($warehouse_integrations !== null) {
                            $warehouse_access_token = unserialize($warehouse_integrations)['access_token'];
                            $logo_md5 = array_var($invoice_template, 'logo_md5');
                            $this->renderInvoiceLogoFromWarehouse($logo_path, $logo_md5, $warehouse_access_token);
                        } else {
                            $tag = md5($logo_path);

                            if ($this->getCachedEtag() == $tag) {
                                $this->invoiceLogoNotChanged($tag);
                            }

                            $source_file = UPLOAD_PATH . '/' . $logo_path;

                            if (is_file($source_file)) {
                                $this->renderInvoiceLogoFromSource($source_file, $tag);
                            }
                        }
                    }
                }
            }
        }

        if ($this->getCachedEtag() === $this->getDefaultInvoiceLogoTag()) {
            $this->invoiceLogoNotChanged($this->getDefaultInvoiceLogoTag());
        } else {
            $this->renderDefaultInvoiceLogo();
        }

        $this->notFound();
    }

    /**
     * Serve not changed invoice logo.
     *
     * @param string $etag
     */
    private function invoiceLogoNotChanged($etag)
    {
        header('Content-Type: image/png');
        header('Content-Disposition: inline; filename=invoice_logo.png');
        header('Cache-Control: public, max-age=315360000');
        header('Pragma: public');
        header('Etag: ' . $etag);

        $this->notModified();
    }

    /**
     * Render avatar from custom source file.
     *
     * @param string $source_file
     * @param string $tag
     */
    private function renderInvoiceLogoFromSource($source_file, $tag)
    {
        $thumb_file = THUMBNAILS_PATH . "/invoice-logo-{$tag}-{$this->width}x{$this->height}-$this->scale";

        if ($this->scale == self::SCALE) {
            scale_and_fit_image($source_file, $thumb_file, $this->width, $this->height, IMAGETYPE_JPEG, 100);
        } else {
            scale_and_crop_image_alt($source_file, $thumb_file, $this->width, $this->height, null, null, IMAGETYPE_JPEG, 100);
        }

        if (is_file($thumb_file)) {
            header('Content-Type: image/png');
            header('Content-Disposition: inline; filename=invoice_logo.png');
            header('Cache-Control: public, max-age=315360000');
            header('Pragma: public');
            header('Etag: ' . $tag);

            $agent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : null;
            if ($agent && strpos($agent, 'MSIE') !== false) {
                header('X-Content-Type-Options: nosniff');
            }

            print file_get_contents($thumb_file);
            exit();
        }
    }

    /**
     * Return default invoice logo tag.
     *
     * @return string
     */
    private function getDefaultInvoiceLogoTag()
    {
        if (empty($this->default_invoice_logo_tag)) {
            $this->default_invoice_logo_tag = md5_file(APPLICATION_PATH . '/modules/invoicing/resources/invoice_default_logo.png');
        }

        return $this->default_invoice_logo_tag;
    }

    /**
     * Render default invoice logo.
     */
    private function renderDefaultInvoiceLogo()
    {
        $this->renderInvoiceLogoFromSource(APPLICATION_PATH . '/modules/invoicing/resources/invoice_default_logo.png', $this->getDefaultInvoiceLogoTag());
    }

    /**
     * Render invoice logo for warehouse.
     *
     * @param string $logo_path
     * @param string $logo_md5
     * @param string $access_token
     */
    private function renderInvoiceLogoFromWarehouse($logo_path, $logo_md5, $access_token)
    {
        $account_id = $_SERVER['ACTIVECOLLAB_ACCOUNT_ID'] ?? 0;
        if ($this->getCachedEtag() == $logo_md5) {
            $this->invoiceLogoNotChanged($logo_md5);
        }

        $logo_path = urlencode($logo_path);

        $source_file = WORK_PATH . '/' . $account_id . "-invoice-logo-{$logo_path}-{$logo_md5}";

        if (!is_file($source_file)) {
            $this->downloadWarehouseFile(
                $logo_path,
                $logo_md5,
                $access_token,
                $source_file
            );
        }

        $this->renderInvoiceLogoFromSource($source_file, $logo_md5);
    }
}
