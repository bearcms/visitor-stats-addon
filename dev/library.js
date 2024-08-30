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
            if (typeof action === "undefined") {
                action = "";
            }
            if (typeof data === "undefined") {
                data = {};
            }

            var xmlhttp = new XMLHttpRequest();
            var params = [];
            params.push('a=' + encodeURIComponent(action));
            params.push('d=' + encodeURIComponent(JSON.stringify(data)));
            params.push('u=' + encodeURIComponent(typeof navigator.userAgent !== 'undefined' ? navigator.userAgent : ''));
            try {
                var timeZone = Intl.DateTimeFormat().resolvedOptions().timeZone;
                params.push('z=' + encodeURIComponent(timeZone));
            } catch (e) {

            }
            params = params.join('&');
            xmlhttp.open('POST', "INSERT_URL_HERE", true);
            xmlhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
            xmlhttp.send(params);
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