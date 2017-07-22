(function ($) {
    'use strict';

    $('.skautisUserManagementTable').dataTable({
        responsive: true,
        language: {
            url: 'https://cdn.datatables.net/plug-ins/1.10.15/i18n/Czech.json'
        }
    }).on('init.dt', function () {
        $(this).find('th').each(function () {
            $(this).html('<span>' + $(this).html() + '</span>');
        });
    });

    var $modal = $('#TB_ajaxContent');
    $('.thickbox').on('click', function () {
        var $this = $(this);
        var userName = $this.parents('tr').find('.firstName').html() + ' ' + $this.parents('tr').find('.lastName').html(),
            nickName = $this.parents('tr').find('.nickName').html();
        if (nickName) {
            userName += ' (' + nickName + ')';
        }

        $('#connectUserToSkautisModal_username').html(userName);

        var $connectUserToSkautisModal_connectLink = $('#connectUserToSkautisModal_connectLink');
        $connectUserToSkautisModal_connectLink.attr('href', updateQueryStringInUrl('skautisUserId', $this.parents('tr').find('.skautisUserId').html(), $connectUserToSkautisModal_connectLink.attr('href')));

        var $connectUserToSkautisModal_registerLink = $('#connectUserToSkautisModal_registerLink');
        var newHref = updateQueryStringInUrl('skautisUserId', $this.parents('tr').find('.skautisUserId').html(), $connectUserToSkautisModal_registerLink.attr('href'));
        $connectUserToSkautisModal_registerLink.attr('href', newHref);
    });

    $('#connectUserToSkautisModal_select').on('change', function () {
        var $this = $(this),
            $connectUserToSkautisModal_connectLink = $('#connectUserToSkautisModal_connectLink');
        if ($.isNumeric($this.val())) {
            $connectUserToSkautisModal_connectLink.attr('href', updateQueryStringInUrl('wpUserId', $this.val(), $connectUserToSkautisModal_connectLink.attr('href')));
        } else {
            $connectUserToSkautisModal_connectLink.attr('href', updateQueryStringInUrl('wpUserId', '', $connectUserToSkautisModal_connectLink.attr('href')));
        }
    });

    $('#connectUserToSkautisModal_defaultRole').on('change', function () {
        var $this = $(this),
            $connectUserToSkautisModal_registerLink = $('#connectUserToSkautisModal_registerLink');
        $connectUserToSkautisModal_registerLink.attr('href', updateQueryStringInUrl('wpRole', $this.val(), $connectUserToSkautisModal_registerLink.attr('href')));
    }).trigger('change');

})(jQuery);

function updateQueryStringInUrl(key, value, url) {
    var re = new RegExp("([?&])" + key + "=.*?(&|#|$)(.*)", "gi"),
        hash;

    if (re.test(url)) {
        if (typeof value !== 'undefined' && value !== null)
            return url.replace(re, '$1' + key + "=" + value + '$2$3');
        else {
            hash = url.split('#');
            url = hash[0].replace(re, '$1$3').replace(/(&|\?)$/, '');
            if (typeof hash[1] !== 'undefined' && hash[1] !== null)
                url += '#' + hash[1];
            return url;
        }
    }
    else {
        if (typeof value !== 'undefined' && value !== null) {
            var separator = url.indexOf('?') !== -1 ? '&' : '?';
            hash = url.split('#');
            url = hash[0] + separator + key + '=' + value;
            if (typeof hash[1] !== 'undefined' && hash[1] !== null)
                url += '#' + hash[1];
            return url;
        }
        else
            return url;
    }
}