<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

use ActiveCollab\Module\Invoicing\InvoicingModule;
use ActiveCollab\Module\System\Utils\QrGenerator\QrGeneratorInterface;
use Angie\Globalization;

class InvoiceTCPDF extends TCPDF
{
    private string $paper_format;
    private string $paper_orientation;
    private IInvoice $invoice;
    private InvoiceTemplate $template;

    public function __construct(IInvoice $invoice)
    {
        $this->invoice = $invoice;
        $this->template = new InvoiceTemplate($invoice->hasCJKCharacters());

        parent::__construct();

        $this->paper_format = $this->template->getPaperSize();
        $this->paper_orientation = Globalization::PAPER_ORIENTATION_PORTRAIT;

        // margins
        $this->SetLeftMargin(15);
        $this->SetRightMargin(15);
        $this->setHeaderMargin(10);
        $this->setFooterMargin(10);

        // meta
        $this->SetTitle($this->invoice->getName());
        $this->SetAuthor('ActiveCollab (https://activecollab.com)');
        $this->SetCreator('ActiveCollab (https://activecollab.com)');
        $this->SetAutoPageBreak(true, 20);

        // font subsetting
        $this->setFontSubsetting(false);

        $this->AddPage($this->paper_orientation, $this->paper_format);
    }

    /**
     * Generate the invoice.
     */
    public function generate()
    {
        $template = AngieApplication::getSmarty()->createTemplate(
            InvoicingModule::PATH . '/resources/invoice_template_body.tpl'
        );

        $logged_user = AngieApplication::authentication()->getLoggedUser();
        $language = $this->invoice->getLanguage();

        if ($this->invoice instanceof Invoice) {
            $additional_variables = [
                'issued_on' => $this->invoice->getIssuedOn()->formatDateForUser($logged_user, 0, $language),
                'due_on' => $this->invoice->getDueOn()->formatForUser($logged_user, 0, $language),
                'canceled_on' => $this->invoice->getClosedOn()
                    ? $this->invoice->getClosedOn()->formatForUser($logged_user, 0, $language)
                    : null,
                'related_projects' => $this->invoice->getRelatedProjects(),
                'qr_uri' => null,
            ];

            if ($this->invoice->getQrNote() && $this->invoice->getQrNote() !== 'none') {
                $data = $this->invoice->getQrNote() === 'custom' ? $this->invoice->getQrNoteContent() : $this->invoice->getPublicUrl();

                $qr_code = AngieApplication::getContainer()->get(QrGeneratorInterface::class)->generate($data);

                $additional_variables['qr_uri'] = '@' . base64_encode($qr_code->writeString());
            }

            $template->assign(
                $this->getTemplateVariables(
                    $additional_variables
                )
            );
        } else {
            if ($this->invoice instanceof Estimate) {
                $template->assign(
                    $this->getTemplateVariables(
                        [
                            'sent_on' => $this->invoice->getSentOn()
                                ? $this->invoice->getSentOn()->formatDateForUser($logged_user, 0, $language)
                                : '',
                        ]
                    )
                );
            }
        }

        $this->writeHTML($template->fetch(), false, false, false, false, '');
    }

    /**
     * Get template variables.
     *
     * @param  array $additional_variables
     * @return array
     */
    public function getTemplateVariables($additional_variables)
    {
        return array_merge(
            [
                'template' => $this->template,
                'invoice' => $this->invoice,
                'columns' => $this->getColumnsWidth(),
            ],
            $additional_variables
        );
    }

    /**
     * Get columns width.
     *
     * @return array
     */
    public function getColumnsWidth()
    {
        $page_width = $this->getUsefulPageWidth();

        // number of numeric columns
        $numeric_columns_count = 3;
        // determine column widths
        $order_width = round(4 * $page_width / 100);
        $numeric_column_width = round(((57 / $numeric_columns_count) > 15 ? 15 : (57 / $numeric_columns_count)) * $page_width / 100);
        $description_width = round($page_width - $order_width - ($numeric_columns_count * $numeric_column_width));
        $totals_label = round($numeric_column_width * 2);
        $totals_empty = round($page_width - $numeric_column_width) - $totals_label;

        return [
            'order' => $order_width,
            'description' => $description_width,
            'numeric' => $numeric_column_width,
            'totals_empty' => $totals_empty,
            'totals_label' => $totals_label,
        ];
    }

    /**
     * Get Usefull Page Width.
     *
     * @return int
     */
    public function getUsefulPageWidth()
    {
        return $this->getPageWidth() - $this->lMargin - $this->rMargin;
    }

    /**
     * Function which renders the header (and page background if needed).
     */
    public function Header()
    {
        $template = AngieApplication::getSmarty()->createTemplate(InvoicingModule::PATH . '/resources/invoice_template_header.tpl');
        $template->assign($this->getTemplateVariables([
            'company_cell' => trim($this->template->getCompanyName() . "\n" . $this->template->getCompanyDetails()),
        ]));
        $this->writeHTML($template->fetch(), false, false, false, false, '');

        $header_height = $this->GetY();

        // header border
        if ($this->template->getPrintHeaderBorder()) {
            $this->SetLineStyle(['color' => $this->convertHTMLColorToDec($this->template->getMainBorderColor())]);
            $this->Line($this->lMargin, $header_height - 2, $this->getPageWidth() - $this->lMargin, $header_height - 2);
        }

        // set the header height
        $this->SetTopMargin($header_height + 16);
    }

    /**
     * Convert HTML color to RGB or CMYK color.
     *
     * @param  string $color
     * @return array
     */
    public function convertHTMLColorToDec($color)
    {
        return TCPDF_COLORS::convertHTMLColorToDec($color, $this->spot_colors);
    }

    /**
     * Function which renders the footer.
     *
     * - Footer is rendered 'the old way' because total_pages functionality does not work when we use writeHtml
     */
    public function Footer()
    {
        // footer font style
        if ($this->template->getPrintFooter()) {
            $font_size = '9';
            $this->SetFont($this->template->getFont(), '', $font_size);

            if ($this->template->getPrintFooterBorder()) {
                $this->SetY(-8 - $font_size);
                $this->SetLineStyle(['color' => $this->convertHTMLColorToDec($this->template->getMainBorderColor())]);
                $this->Line($this->lMargin, $this->GetY() + 2, $this->getPageWidth() - $this->lMargin, $this->GetY() + 2);
            } else {
                $this->SetY(-8 - $font_size);
            }

            $this->SetY($this->GetY() + 5);

            if ($this->template->getFooterLayout()) {
                $this->SetX($this->getPageWidth() - $this->rMargin - 60);
                $this->Cell(60, $this->template->getLineHeight(), $this->invoice->getName(), 0, 0, 'R');
                $this->SetX($this->lMargin);
                $this->Cell(0, $this->template->getLineHeight(), lang('Page :page_no of :total_pages', ['page_no' => $this->PageNo(), 'total_pages' => $this->getAliasNbPages()], true, $this->invoice->getLanguage()), 0, 0, 'L');
            } else {
                $this->SetX($this->getPageWidth() - $this->rMargin - 26.5);
                $this->Cell(40, $this->template->getLineHeight(), lang('Page :page_no of :total_pages', ['page_no' => $this->PageNo(), 'total_pages' => $this->getAliasNbPages()], true, $this->invoice->getLanguage()), 0, 0, 'R');
                $this->SetX($this->lMargin);
                $this->Cell(0, $this->template->getLineHeight(), $this->invoice->getName(), 0, 0, 'L');
            }
        }
    }

    /**
     * Output fonts. Overwritten parent method - cache fonts.
     *
     * @protected
     */
    protected function _putfonts()
    {
        $nf = $this->n;
        foreach ($this->diffs as $diff) {
            //Encodings
            $this->_newobj();
            $this->_out('<< /Type /Encoding /BaseEncoding /WinAnsiEncoding /Differences [' . $diff . '] >>' . "\n" . 'endobj');
        }
        $mqr = TCPDF_STATIC::get_mqr();
        TCPDF_STATIC::set_mqr(false);
        foreach ($this->FontFiles as $file => $info) {
            // search and get font file to embedd
            $fontfile = TCPDF_FONTS::getFontFullPath($file, $info['fontdir']);
            if (!TCPDF_STATIC::empty_string($fontfile)) {
                $font = file_get_contents($fontfile);
                $compressed = (substr($file, -2) == '.z');
                if ((!$compressed) and (isset($info['length2']))) {
                    $header = (ord($font[0]) == 128);
                    if ($header) {
                        // strip first binary header
                        $font = substr($font, 6);
                    }
                    if ($header and (ord($font[$info['length1']]) == 128)) {
                        // strip second binary header
                        $font = substr($font, 0, $info['length1']) . substr($font, ($info['length1'] + 6));
                    }
                } elseif ($info['subset'] and ((!$compressed) or ($compressed and function_exists('gzcompress')))) {
                    if ($compressed) {
                        // uncompress font
                        $font = gzuncompress($font);
                    }
                    // merge subset characters
                    $subsetchars = []; // used chars
                    foreach ($info['fontkeys'] as $fontkey) {
                        $fontinfo = $this->getFontBuffer($fontkey);
                        $subsetchars += $fontinfo['subsetchars'];
                    }
                    // rebuild a font subset
                    //$font = TCPDF_FONTS::_getTrueTypeFontSubset($font, $subsetchars);

                    //Theses lines are different from the parent class - begin

                    // Alcal: $font2cache modification
                    // This modification creates utf-8 fonts only the first time,
                    // after that it uses cache file which dramatically reduces execution time
                    if (!file_exists($fontfile . '.cached')) {
                        // calculate $font first time
                        $subsetchars = array_fill(0, 512, true); // fill subset for all chars 0-512
                        $font = TCPDF_FONTS::_getTrueTypeFontSubset($font, $subsetchars); // this part is actually slow!
                        // and then save $font to file for further use
                        $fp = fopen($fontfile . '.cached', 'w');
                        $flat_array = serialize($font);
                        fwrite($fp, $flat_array);
                        fclose($fp);
                    } else {
                        // cache file exist, load file
                        $fp = fopen($fontfile . '.cached', 'r');
                        $flat_array = fread($fp, filesize($fontfile . '.cached'));
                        fclose($fp);
                        $font = unserialize($flat_array);
                    }
                    //Theses lines are different from the parent class - end

                    // calculate new font length
                    $info['length1'] = strlen($font);
                    if ($compressed) {
                        // recompress font
                        $font = gzcompress($font);
                    }
                }
                $this->_newobj();
                $this->FontFiles[$file]['n'] = $this->n;
                $stream = $this->_getrawstream($font);
                $out = '<< /Length ' . strlen($stream);
                if ($compressed) {
                    $out .= ' /Filter /FlateDecode';
                }
                $out .= ' /Length1 ' . $info['length1'];
                if (isset($info['length2'])) {
                    $out .= ' /Length2 ' . $info['length2'] . ' /Length3 0';
                }
                $out .= ' >>';
                $out .= ' stream' . "\n" . $stream . "\n" . 'endstream';
                $out .= "\n" . 'endobj';
                $this->_out($out);
            }
        }
        TCPDF_STATIC::set_mqr($mqr);
        foreach ($this->fontkeys as $k) {
            //Font objects
            $font = $this->getFontBuffer($k);
            $type = $font['type'];
            $name = $font['name'];
            if ($type == 'core') {
                // standard core font
                $out = $this->_getobj($this->font_obj_ids[$k]) . "\n";
                $out .= '<</Type /Font';
                $out .= ' /Subtype /Type1';
                $out .= ' /BaseFont /' . $name;
                $out .= ' /Name /F' . $font['i'];
                if ((strtolower($name) != 'symbol') and (strtolower($name) != 'zapfdingbats')) {
                    $out .= ' /Encoding /WinAnsiEncoding';
                }
                if ($k == 'helvetica') {
                    // add default font for annotations
                    $this->annotation_fonts[$k] = $font['i'];
                }
                $out .= ' >>';
                $out .= "\n" . 'endobj';
                $this->_out($out);
            } elseif (($type == 'Type1') or ($type == 'TrueType')) {
                // additional Type1 or TrueType font
                $out = $this->_getobj($this->font_obj_ids[$k]) . "\n";
                $out .= '<</Type /Font';
                $out .= ' /Subtype /' . $type;
                $out .= ' /BaseFont /' . $name;
                $out .= ' /Name /F' . $font['i'];
                $out .= ' /FirstChar 32 /LastChar 255';
                $out .= ' /Widths ' . ($this->n + 1) . ' 0 R';
                $out .= ' /FontDescriptor ' . ($this->n + 2) . ' 0 R';
                if ($font['enc']) {
                    if (isset($font['diff'])) {
                        $out .= ' /Encoding ' . ($nf + $font['diff']) . ' 0 R';
                    } else {
                        $out .= ' /Encoding /WinAnsiEncoding';
                    }
                }
                $out .= ' >>';
                $out .= "\n" . 'endobj';
                $this->_out($out);
                // Widths
                $this->_newobj();
                $s = '[';
                for ($i = 32; $i < 256; ++$i) {
                    if (isset($font['cw'][$i])) {
                        $s .= $font['cw'][$i] . ' ';
                    } else {
                        $s .= $font['dw'] . ' ';
                    }
                }
                $s .= ']';
                $s .= "\n" . 'endobj';
                $this->_out($s);
                //Descriptor
                $this->_newobj();
                $s = '<</Type /FontDescriptor /FontName /' . $name;
                foreach ($font['desc'] as $fdk => $fdv) {
                    if (is_float($fdv)) {
                        $fdv = sprintf('%F', $fdv);
                    }
                    $s .= ' /' . $fdk . ' ' . $fdv . '';
                }
                if (!TCPDF_STATIC::empty_string($font['file'])) {
                    $s .= ' /FontFile' . ($type == 'Type1' ? '' : '2') . ' ' . $this->FontFiles[$font['file']]['n'] . ' 0 R';
                }
                $s .= '>>';
                $s .= "\n" . 'endobj';
                $this->_out($s);
            } else {
                // additional types
                $mtd = '_put' . strtolower($type);
                if (!method_exists($this, $mtd)) {
                    $this->Error('Unsupported font type: ' . $type);
                }
                $this->$mtd($font);
            }
        }
    }
}
