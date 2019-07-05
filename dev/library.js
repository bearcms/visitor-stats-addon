var vsjs = typeof vsjs !== "undefined" ? vsjs : (function () {
    return {
        "log": function (action, data) {
            if (typeof action === "undefined") {
                action = "";
            }
            if (typeof data === "undefined") {
                data = {};
            }
            var script = document.createElement("script");
            script.type = "text/javascript";
            script.async = true;
            script.src = "INSERT_URL_HERE?a=" + encodeURIComponent(action) + "&d=" + encodeURIComponent(JSON.stringify(data));
            var element = document.getElementsByTagName("script")[0];
            element.parentNode.insertBefore(script, element);
        }
    };
}());