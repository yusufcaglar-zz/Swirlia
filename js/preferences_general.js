var phpsessid = null;

try {
    if (window.top.location.host === "swirlia.com" || window.top.location.host === "wwww.swirlia.com") {
        window.onload = function () {
            var xhr = new XMLHttpRequest();
            xhr.open('POST', 'html/session.php', true);
            xhr.send();

            xhr.onload = function () {
                phpsessid = this.response;

                afterSessionSet();
            }

            function afterSessionSet() {
                var params = "operation=getPreferences&phpsessid=" + phpsessid ;
                var xhr = new XMLHttpRequest();
                xhr.open('POST', 'https://swirlia.net/php/Index.php', true);
                xhr.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');
                xhr.withCredentials = true;
                xhr.send(params);
                xhr.onload = function () {
                    var server_response = JSON.parse(this.response);

                    if (server_response.result === "success") {
                        const conversations_count = document.getElementById("conversations_count");
                        const followers_count = document.getElementById("followers_count");
                        const register_date = document.getElementById("register_date");
                        const last_seen_date = document.getElementById("last_seen_date");
                        const sounds_enabled = document.getElementById("sounds_enabled");
                        const private_profile = document.getElementById("private_profile");
                        const registered_access = document.getElementById("registered_access");
                        const registered_message = document.getElementById("registered_message");

                        if (server_response.conversations_count === "1")
                            conversations_count.checked = true;
                        else
                            conversations_count.checked = false;

                        if (server_response.followers_count === "1")
                            followers_count.checked = true;
                        else
                            followers_count.checked = false;

                        if (server_response.register_date === "1")
                            register_date.checked = true;
                        else
                            register_date.checked = false;

                        if (server_response.last_seen_date === "1")
                            last_seen_date.checked = true;
                        else
                            last_seen_date.checked = false;

                        if (server_response.sounds_enabled === "1")
                            sounds_enabled.checked = true;
                        else
                            sounds_enabled.checked = false;

                        if (server_response.private_profile === "1")
                            private_profile.checked = true;
                        else
                            private_profile.checked = false;

                        if (server_response.registered_access === "1")
                            registered_access.checked = true;
                        else
                            registered_access.checked = false;

                        if (server_response.registered_message === "1")
                            registered_message.checked = true;
                        else
                            registered_message.checked = false;

                        conversations_count.disabled = false;
                        followers_count.disabled = false;
                        register_date.disabled = false;
                        last_seen_date.disabled = false;
                        sounds_enabled.disabled = false;
                        private_profile.disabled = false;
                        registered_access.disabled = false;
                        registered_message.disabled = false;

                        //EventListeners
                        conversations_count.onclick = function () {
                            changePreferences("conversations_count", this.checked ? 1 : 0);
                        }

                        followers_count.onclick = function () {
                            changePreferences("followers_count", this.checked ? 1 : 0);
                        }

                        register_date.onclick = function () {
                            changePreferences("register_date", this.checked ? 1 : 0);
                        }

                        last_seen_date.onclick = function () {
                            changePreferences("last_seen_date", this.checked ? 1 : 0);
                        }

                        sounds_enabled.onclick = function () {
                            changePreferences("sounds_enabled", this.checked ? 1 : 0);
                        }

                        private_profile.onclick = function () {
                            changePreferences("private_profile", this.checked ? 1 : 0);
                        }

                        registered_access.onclick = function () {
                            changePreferences("registered_access", this.checked ? 1 : 0);
                        }

                        registered_message.onclick = function () {
                            changePreferences("registered_message", this.checked ? 1 : 0);
                        }

                        //Display Page
                        document.getElementsByClassName("loader")[0].style.display = "none";
                        document.getElementById("container").style.display = "block";
                    } else {
                        if (server_response.message === "User not exists")
                            parent.window.location.href = "";
                        else if (server_response.message === "User not logged in")
                            parent.window.location.href = "";
                    }
                }
            }
        }

        function changePreferences(type, value) {
            conversations_count.disabled = true;
            followers_count.disabled = true;
            register_date.disabled = true;
            last_seen_date.disabled = true;
            sounds_enabled.disabled = true;
            private_profile.disabled = true;
            registered_access.disabled = true;
            registered_message.disabled = true;

            var params = "operation=setPreferences&type=" + type + "&value=" + value + "&phpsessid=" + phpsessid;
            var xhr = new XMLHttpRequest();
            xhr.open('POST', 'https://swirlia.net/php/Index.php', true);
            xhr.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');
            xhr.withCredentials = true;
            xhr.send(params);
            xhr.onload = function () {
                var server_response = JSON.parse(this.response);

                if (server_response.result === "success") {
                    conversations_count.disabled = false;
                    followers_count.disabled = false;
                    register_date.disabled = false;
                    last_seen_date.disabled = false;
                    sounds_enabled.disabled = false;
                    private_profile.disabled = false;
                    registered_access.disabled = false;
                    registered_message.disabled = false;
                } else {
                    if (server_response.message === "User not exists")
                        parent.window.location.href = "";
                    else if (server_response.message === "User not logged in")
                        parent.window.location.href = "";
                }
            }
        }
    }
} catch (err) {
    document.body.innerHTML = "";
    setTimeout(() => {
        alert("Access Denied");
    }, 1000);
}