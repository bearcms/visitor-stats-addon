var vsjs = typeof vsjs !== "undefined" ? vsjs : (function () {
    var url = originalURL = window.location.href;
    if (url.indexOf('-vssource') !== -1) {
        try {
            url = url.replace(/\?-vssource=.*?&/, '?').replace(/&-vssource=.*?&/, '&').replace(/\?-vssource=.*/, '').replace(/&-vssource=.*/, '');
            history.replaceState({}, "", url);
        } catch (e) {

        }
    }
    return {
        'log': function (action, data) {
            if (typeof navigator.sendBeacon === "undefined") {
                return false;
            }
            if (typeof action === "undefined") {
                action = "";
            }
            if (typeof data === "undefined") {
                data = {};
            }
            var formData = new FormData();
            formData.append('a', action);
            formData.append('d', JSON.stringify(data));
            formData.append('u', typeof navigator.userAgent !== 'undefined' ? navigator.userAgent : '');
            try {
                formData.append('z', Intl.DateTimeFormat().resolvedOptions().timeZone);
            } catch (e) {
            }
            return navigator.sendBeacon('INSERT_URL_HERE', formData);
        },
        'getSource': function () {
            var u = new URL(originalURL);
            if (typeof u.searchParams !== 'undefined') {
                return u.searchParams.get('-vssource');
            }
            return null;
        },
        'getURL': function () {
            return url;
        }
    };
}());