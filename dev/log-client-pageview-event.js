(function () {
    var log = function () {
        var data = {};
        data["url"] = vsjs.getURL();
        var source = vsjs.getSource();
        if (source !== null) {
            data["source"] = source;
        }
        var referrer = '';
        try {
            var referrerHost = document.referrer !== '' ? (new URL(document.referrer)).host : '';
            referrer = referrerHost !== window.location ? referrerHost : document.referrer;
        } catch (e) {
        }
        if (referrer.length > 0) {
            data["referrer"] = referrer;
        }
        vsjs.log("pageview", data);
    };
    if (document.readyState === "loading") {
        document.addEventListener("DOMContentLoaded", log);
    } else {
        log();
    }
}());