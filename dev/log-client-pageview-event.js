(function () {
    var log = function () {
        var data = {};
        data["url"] = window.location.toString();
        var referrer = "";
        try {
            var referrerHost = (new URL(document.referrer)).host;
            referrer = referrerHost !== window.location ? referrerHost : document.referrer;
        } catch (e) {
        }
        data["referrer"] = referrer;
        vsjs.log("pageview", data);
    };
    if (document.readyState === "loading") {
        document.addEventListener("DOMContentLoaded", log);
    } else {
        log();
    }
}());