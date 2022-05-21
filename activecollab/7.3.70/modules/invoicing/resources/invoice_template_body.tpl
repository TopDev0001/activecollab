<style>
    .overall {
        font-family: '{$template->getFont()}';
        font-size: {$template->getFontSize()}mm;
        line-height: {$template->getLineHeight()}mm;
    }

    h1 {
        font-size: 5.5mm;
        font-weight: bold;
    }

    .overdue {
        color: #FF0000;
    }

    .invoice_details_right {
        text-align: right;
    }

    .company_details {
        text-align: right;
    }

    .company_details_left {
        text-align: left;
    }

    .item_table {
        padding: 1.3mm 0mm 1.3mm 0mm;
    }

    .item_table_cell {
        line-height: 4.5mm;
        height: 4.5mm;
    }

    .item_table_order {
        text-align: left;
        width: {$columns.order}mm;
    }

    .item_table_description {
        text-align: left;
        width: {$columns.description}mm;
    }

    .item_table_numeric {
        text-align: right;
        width: {$columns.numeric}mm;
    }

    .stronger_border {
        border-bottom: 0.2mm solid #bbbbbb;
    }

    .bold {
        font-weight: bold;
    }

    .lighter_border {
        border-bottom: 0.2mm solid #eeeeee;
    }

    .totals_table_empty {
        width: {$columns.totals_empty}mm;
    }

    .totals_table_label {
        width: {$columns.totals_label}mm;
        text-align: right;
    }

    .totals_table_value {
        width: {$columns.numeric}mm;
        text-align: right;
    }

    .invoice_note {
        line-height: 3.2mm;
    }

    .invoice_qr_code {
        display: block;
        width: 79px;
        height: 79px;
        margin: 10px;
    }
</style>

<div class="overall"><table>
        <tr>
            {if !$template->getBodyLayout()}
                <td class="company_details company_details_left"><b>{$invoice->getCompanyName()}</b><br/>{$invoice->getCompanyAddress()|clean|nl2br nofilter}</td>
            {/if}

            <td class="invoice_details {if !$template->getBodyLayout()}invoice_details_right{/if}"><h1>{if $invoice instanceof Invoice}{if $label = $template->getLabel()}{$label}{else}{lang language=$invoice->getLanguage()}Invoice{/lang}{/if}{/if}{if $invoice instanceof Estimate}{lang language=$invoice->getLanguage()}Estimate for{/lang}{/if} {$invoice->getName()}</h1>

                {if $invoice instanceof Invoice}
                    {if $invoice->getPurchaseOrderNumber()}
                        <br/>
                        {lang po_number=$invoice->getPurchaseOrderNumber() language=$invoice->getLanguage()}Reference: :po_number{/lang}
                    {/if}

                    {if $invoice->getProject() instanceof Project}
                        <br/>
                        {lang project_name=$invoice->getProject()->getName() language=$invoice->getLanguage()}Project: :project_name{/lang}
                    {/if}

                    {if $invoice->getStatus() == 'issued' || $invoice->getStatus() == 'paid'}
                        {if $issued_on}
                            <br/>
                            {lang issued_date=$issued_on language=$invoice->getLanguage()}Issued On: :issued_date{/lang}
                        {/if}

                        {if $due_on}
                            <br/>
                            <span
                                    class="{if due_on && $invoice->isOverdue()}overdue{/if}">{lang due_date=$due_on language=$invoice->getLanguage()}Payment Due On: :due_date{/lang}</span>
                        {/if}
                    {/if}

                    {if $related_projects && (count($related_projects) > 0)}
                        <br/>
                        <span>{lang language=$invoice->getLanguage()}Related project(s){/lang}: {foreach from=$related_projects key=k item=v} {$v->getName()} (#{$k}) {/foreach}</span>
                    {/if}
                    {if $invoice->getIsCanceled()}
                        <br/>
                        <span
                                class="overdue">{lang canceled_date=$canceled_on language=$invoice->getLanguage()}Canceled On: :canceled_date{/lang}</span>
                    {/if}
                {/if}

                {if $invoice instanceof Estimate}
                    {if $sent_on}
                        <br/>
                        {lang sent_date=$sent_on language=$invoice->getLanguage()}Sent On: :sent_date{/lang}
                    {/if}
                {/if}
            </td>

            {if $template->getBodyLayout()}
                <td class="company_details"><b>{$invoice->getCompanyName()}</b><br/>{$invoice->getCompanyAddress()|clean|nl2br nofilter}</td>
            {/if}
        </tr>
    </table>

    <br/><br/><br/>

    <table border="0" class="item_table">
        <tr>
            <th class="item_table_order item_table_cell stronger_border bold">#</th>
            <th class="item_table_description item_table_cell stronger_border bold"
                >{lang language=$invoice->getLanguage()}Description{/lang}</th>
            <th class="item_table_numeric item_table_cell stronger_border bold"
                >{lang language=$invoice->getLanguage()}Qty.{/lang}</th>
            <th class="item_table_numeric item_table_cell stronger_border bold"
                >{lang language=$invoice->getLanguage()}Unit Cost{/lang}</th>
            <th class="item_table_numeric item_table_cell stronger_border bold"
                >{lang language=$invoice->getLanguage()}Amount{/lang}</th>
        </tr>
        {foreach from=$invoice->getItems() item=item name=item_loop}
            {if $smarty.foreach.item_loop.iteration != count($invoice->getItems())}
                {assign var="item_border" value="lighter_border"}
            {else}
                {assign var="item_border" value="stronger_border"}
            {/if}
            <tr>
                <td class="item_table_order item_table_cell {$item_border}">{$smarty.foreach.item_loop.iteration}</td>
                <td class="item_table_description item_table_cell {$item_border}">{$item->getDescription()|clean|nl2br nofilter}</td>
                <td class="item_table_numeric item_table_cell {$item_border}">{$item->getQuantity()}</td>
                <td class="item_table_numeric item_table_cell {$item_border}">{$item->getUnitCost()|money:$invoice->getCurrency():$invoice->getLanguage()}</td>
                <td class="item_table_numeric item_table_cell {$item_border}">{($item->getSubtotal())|money:$invoice->getCurrency():$invoice->getLanguage()}</td>
            </tr>
        {/foreach}
    </table>

    <table border="0" class="item_table">
        <tr>
            <td class="item_table_cell totals_table_empty"></td>
            <td class="item_table_cell totals_table_label lighter_border">{lang language=$invoice->getLanguage()}Subtotal{/lang}
                :
            </td>
            <td class="item_table_cell totals_table_value lighter_border">{($invoice->getSubTotal())|money:$invoice->getCurrency():$invoice->getLanguage()}</td>
        </tr>
        {if $invoice->getDiscount()}
            <tr>
                <td class="item_table_cell totals_table_empty"></td>
                <td class="item_table_cell totals_table_label lighter_border">{lang language=$invoice->getLanguage()}Discount{/lang}
                    :
                </td>
                <td class="item_table_cell totals_table_value lighter_border">
                    -{$invoice->getDiscount()|money:$invoice->getCurrency():$invoice->getLanguage()}</td>
            </tr>
        {/if}

        {foreach from=$invoice->getTaxGroupedByType() item=invoice_tax}
            <tr>
                <td class="item_table_cell totals_table_empty"></td>
                <td class="item_table_cell totals_table_label lighter_border">{$invoice_tax.name}
                    ({$invoice_tax.percentage}%):
                </td>
                <td class="item_table_cell totals_table_value lighter_border">{$invoice_tax.amount|money:$invoice->getCurrency():$invoice->getLanguage()}</td>
            </tr>
        {/foreach}
        {if $invoice->getRoundingDifference()}
            <tr>
                <td class="item_table_cell totals_table_empty"></td>
                <td class="item_table_cell totals_table_label lighter_border">{lang language=$invoice->getLanguage()}Rounding Diff.{/lang}
                    :
                </td>
                <td class="item_table_cell totals_table_value lighter_border">{$invoice->getRoundingDifference()|money:$invoice->getCurrency():$invoice->getLanguage()}</td>
            </tr>
        {/if}
        <tr>
            <td class="item_table_cell totals_table_empty"></td>
            <td class="item_table_cell totals_table_label stronger_border">
                <b>{lang language=$invoice->getLanguage()}Total{/lang} ({$invoice->getCurrency()->getCode()}):</b></td>
            <td class="item_table_cell totals_table_value stronger_border">
                <b>{$invoice->getRoundedTotal()|money:$invoice->getCurrency():$invoice->getLanguage()}</b></td>
        </tr>
        {if !($invoice instanceof Estimate)}
            <tr>
                <td class="item_table_cell totals_table_empty"></td>
                <td class="item_table_cell totals_table_label lighter_border">{lang language=$invoice->getLanguage()}Amount Paid{/lang}
                    :
                </td>
                <td class="item_table_cell totals_table_value lighter_border">{$invoice->getPaidAmount()|money:$invoice->getCurrency():$invoice->getLanguage()}</td>
            </tr>
            <tr>
                <td class="item_table_cell totals_table_empty"></td>
                <td class="item_table_cell totals_table_label stronger_border">
                    <b>{lang language=$invoice->getLanguage()}Balance Due{/lang} ({$invoice->getCurrency()->getCode()}):</b></td>
                <td class="item_table_cell totals_table_value stronger_border">
                    <b>{$invoice->getBalanceDue()|money:$invoice->getCurrency():$invoice->getLanguage()}</b></td>
            </tr>
        {/if}
    </table>


    {if $invoice->getNote()}
        <div>
            <br/>
            <br/>
            <br/>
            <b>{lang language=$invoice->getLanguage()}Note{/lang}:</b>
            <br/>
            <span class="invoice_note">{$invoice->getNote()|clean|nl2br nofilter}</span>
        </div style="di">
    {/if}

    {if !empty($qr_uri)}
        <div>
            <img src="{$qr_uri}" alt="" class="invoice_qr_code" />
        </div>
    {/if}
</div>
