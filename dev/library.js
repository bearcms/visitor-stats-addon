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
            var script = document.createElement("script");
            script.type = "text/javascript";
            script.async = true;
            script.src = "INSERT_URL_HERE?a=" + encodeURIComponent(action) + "&d=" + encodeURIComponent(JSON.stringify(data)) + "&u=" + encodeURIComponent(typeof navigator.userAgent !== 'undefined' ? navigator.userAgent : '');
            var element = document.getElementsByTagName("script")[0];
            element.parentNode.insertBefore(script, element);
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