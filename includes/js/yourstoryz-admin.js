document.addEventListener("DOMContentLoaded", function() {
    var button = document.getElementById("yourstoryz_getstoryz");
    if (button) {
        button.addEventListener("click", function(e) {
            e.preventDefault();
            button.disabled = true;
            button.innerHTML = "Fetching storyz...";
            fetch(ajaxurl + '?action=call_api')
                .then(function(data) {
                    button.disabled = false;
                    button.innerHTML = "Get YourStoryz";
                })
                .catch(function(error) {
                    button.disabled = false;
                    button.innerHTML = "Something went wrong";
                });
        });
    }
});
