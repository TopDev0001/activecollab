<style>
    .overall {
        font-family: '{$template->getFont()}';
        font-size: {$template->getFontSize()}mm;
        line-height: {$template->getLineHeight()}mm;
    }

    .header_cell {
        width: 50%;
    }

    .company_cell_left {
        text-align: left;
    }

    .company_cell {
        text-align: right;
    }

    .logo_image {
        height: {$template->getLogoImageHeight(3.779,21.166666667)}mm;
    }

    .logo_cell_right {
        text-align: right;
    }
    
    .empty_space {
        width: 30%;
    }
    
    .image_placeholder {
        width: 70%;
    }
    
</style>

<div class="overall"><table >
        <tr>
            {if $template->getHeaderLayout()}<td class="header_cell company_cell_left">{$company_cell|clean|nl2br nofilter}</td>{/if}
            <td class="header_cell {if $template->getHeaderLayout()}logo_cell_right{else}logo_cell{/if}" ><table>
                    <tr>
                        <td class="{if $template->getHeaderLayout()}empty_space{else}image_placeholder{/if}">{if !$template->getHeaderLayout()}<img src="@{base64_encode(file_get_contents($template->getLogoImagePath()))}" class="logo_image"/>{/if}</td>
                        <td class="{if $template->getHeaderLayout()}image_placeholder{else}empty_space{/if}">{if $template->getHeaderLayout()}<img src="@{base64_encode(file_get_contents($template->getLogoImagePath()))}" class="logo_image"/>{/if}</td>
                    </tr>
                </table> 
            </td>
            {if !$template->getHeaderLayout()}<td class="header_cell company_cell">{$company_cell|clean|nl2br nofilter}</td>{/if}
        </tr>
    </table>
</div>
