var MEMBER_REGISTER = {
    storageKey: 'member-register-hidden-columns',
    hiddenColumns: [1, 7, 8],
    saveHiddenColumns: function () {
        if (typeof window.localStorage === 'object') {
            window.localStorage.setItem(this.storageKey, JSON.stringify(this.hiddenColumns));
        }
    },
    readHiddenColumns: function () {
        if (typeof window.localStorage === 'object') {
            var possibleHidden = window.localStorage.getItem(this.storageKey);
            if (possibleHidden !== null) {
                this.hiddenColumns = JSON.parse(possibleHidden);
            }
        }
    },
    hideColumns: function () {
        var $table = jQuery('table').has('th.hideable');
        var $caption = $table.find('caption > p');

        for (var i = 0; i < MEMBER_REGISTER.hiddenColumns.length; ++i) {
            var index = MEMBER_REGISTER.hiddenColumns[i];
            var ths = $table.find('tr th:nth-child(' + index + ')');
            var text = ths.text();
            var tds = $table.find('tr td:nth-child(' + index + ')');

            var showLink = '<a href="#show" title="' + text + '" data-index="' + index +
                '"><i class="dashicons dashicons-download"></i>' + text + '</a>';
            $caption.append(showLink);
            ths.hide();
            tds.hide();
        }
    }
};

jQuery(document).ready(function () {
    MEMBER_REGISTER.readHiddenColumns();
    MEMBER_REGISTER.hideColumns();

    jQuery('table.sorter').stupidtable();

    // Search field
    jQuery('#tablesearch').bind('change keyup', function (event) {
        var $self = jQuery(this);
        var search = $self.val();
        var $trs = $self.parentsUntil('table').parent().find('tbody > tr');
        $trs.each(function (index, item) {
            var $tr = jQuery(item);
            if ($tr.text().indexOf(search) !== -1) {
                if ($tr.is(':hidden')) {
                    $tr.show();
                }
            }
            else {
                $tr.hide();
            }
        });
    });

    jQuery.datepicker.setDefaults({
        showWeek: true,
        changeMonth: true,
        changeYear: true,
        yearRange: '1920:2060',
        numberOfMonths: 1,
        dateFormat: 'yy-mm-dd'
    });
    jQuery('input.pickday').datepicker();
    jQuery('select.chosen').select2({
        allowClear: true
    });
    jQuery('form').validate();

    // Removal button should ask the user: are you sure?
    jQuery('a[rel="remove"]').click(function () {
        var title = jQuery(this).attr('title');
        return confirm(title);
    });

    var hideLink = '<a href="#hide" class="dashicons dashicons-upload" title="<?php echo __('Hide', 'member-register'); ?>">&nbsp;</a>';
    jQuery('th.hideable').append(hideLink);

    // Hide table columns
    jQuery('th.hideable a[href="#hide"]').on('click', function () {
        var $self = jQuery(this);
        var inx = $self.parent().index() + 1;

        var table = $self.parentsUntil('table').parent();
        var text = $self.parent().text();
        //console.log('hide(). inx: ' + inx + ', text: ' + text);

        if (MEMBER_REGISTER.hiddenColumns.indexOf(inx) === -1) {
            MEMBER_REGISTER.hiddenColumns.push(inx);
        }
        var showLink = '<a href="#show" title="' + text + '" data-index="' + inx +
            '"><i class="dashicons dashicons-download"></i>' + text + '</a>';
        table.find('caption > p').append(showLink);

        var ths = table.find('tr th:nth-child(' + inx + ')');
        var tds = table.find('tr td:nth-child(' + inx + ')');

        ths.hide();
        tds.hide();
        MEMBER_REGISTER.saveHiddenColumns();

        return false;
    });

    // Show the column again
    jQuery(document).on('click', 'table caption a[href="#show"]', function () {
        var $self = jQuery(this);
        var text = $self.text();
        var inx = $self.data('index');
        //console.log('show(). inx: ' + inx + ', text: ' + text);

        var table = $self.parentsUntil('table').parent();
        var ths = table.find('tr th:nth-child(' + inx + ')');
        var tds = table.find('tr td:nth-child(' + inx + ')');
        ths.show();
        tds.show();

        var columnIndex = MEMBER_REGISTER.hiddenColumns.indexOf(inx);
        if (columnIndex !== -1) {
            MEMBER_REGISTER.hiddenColumns.splice(columnIndex, 1);
        }

        $self.remove();
        MEMBER_REGISTER.saveHiddenColumns();

        return false;
    });
});
