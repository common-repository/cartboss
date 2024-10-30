document.addEventListener("DOMContentLoaded", function () {
    for (var i = 0; i < cb_helper_data.length; i++) {
        try {
            var value = new URL(window.location.href).searchParams.get(cb_helper_data[i]['query']);
            if (value) {
                document.cookie = cb_helper_data[i]['cookie'] + "=" + value + ";secure=true;sameSite=None;path=/";
            }
        } catch (e) {
        }
    }

    console.log("🔥", "SMS Powered by ->", "www.cartboss.io");
});
