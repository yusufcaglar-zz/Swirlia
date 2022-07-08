var phpsessid = null;

//Sounds
const swirl_1_sound = document.getElementById("swirl_1_sound");
const swirl_2_sound = document.getElementById("swirl_2_sound");
const swirl_3_sound = document.getElementById("swirl_3_sound");
const swirl_4_sound = document.getElementById("swirl_4_sound");

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
        }

        function afterSessionSet() {
            var serverProcessing = true;
            //GET SWIRL
            getSwirl();
            function getSwirl() {
                serverProcessing = true;

                hide_page();

                for (let i = 0; i < 32; i++) {
                    var div = document.getElementById("div_" + (i + 1));
                    div.style.display = "none";
                }

                var params = "operation=getSwirl&phpsessid=" + phpsessid;
                var xhr = new XMLHttpRequest();
                xhr.open('POST', 'https://swirlia.net/php/Index.php', true);
                xhr.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');
                xhr.withCredentials = true;
                xhr.send(params);
                xhr.onload = function () {
                    var server_response = JSON.parse(this.response);

                    if (server_response.result === "success") {
                        const users = document.querySelectorAll(".users");

                        for (let i = 0; i < server_response.swirl.length; i++) {
                            var div = document.getElementById("div_" + (i + 1));
                            div.style.display = "flex";

                            var label = document.getElementById("label_" + (i + 1));
                            label.innerText = server_response.swirl[i].username;

                            var img = document.getElementById("img_" + (i + 1));

                            new Promise((resolve, reject) => {
                                var timestamp = new Date().getTime();
                                img.src = "https://swirlia.net/" + server_response.swirl[i].profile_img + "?t=" + timestamp;
                            });

                            var bio = document.getElementById("span_" + (i + 1));
                            var limited = server_response.swirl[i].bio;
                            if (limited != null) {
                                if (limited.length > 97)
                                    bio.textContent = limited.substring(0, 97) + "...";
                                else
                                    bio.textContent = limited;
                                bio.style.display = "initial";
                            } else {
                                bio.textContent = "";
                                bio.style.display = "none";
                            }

                            div.href = server_response.swirl[i].username;
                            div.target = "_blank";

                            if (users.length == 0 && (i == 30 || i == 31))
                                div.style.visibility = "hidden";
                        }

                        display_page();
                    } else {
                        if (server_response.message === "User not exists")
                            parent.window.location.href = "";
                        else if (server_response.message === "User not logged in")
                            parent.window.location.href = "";
                        else
                            display_page();
                    }

                    serverProcessing = false;
                }
            }

            //SWIRL BUTTON
            let degree = 0;
            const swirl = document.getElementById("swirl");
            swirl.onclick = function () {
                if (!serverProcessing) {
                    const random = Math.random();

                    if (random <= 0.25)
                        swirl_1_sound.play();
                    else if (random <= 0.5)
                        swirl_2_sound.play();
                    else if (random <= 0.75)
                        swirl_3_sound.play();
                    else
                        swirl_4_sound.play();

                    degree += 1450;
                    swirl.style.transform = "rotate(-" + degree.toString() + "deg)";
                    swirl.style.transition = "ease 1s";

                    getSwirl();
                }
            }

            //RANDOM
            const random = document.getElementById("random");
            random.onclick = function () {
                if (!serverProcessing) {
                    serverProcessing = true;

                    var params = "operation=getRandom&phpsessid=" + phpsessid;
                    var xhr = new XMLHttpRequest();
                    xhr.open('POST', 'https://swirlia.net/php/Index.php', true);
                    xhr.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');
                    xhr.send(params);
                    xhr.onload = function () {
                        var server_response = JSON.parse(this.response);

                        if (server_response.result === "success") {
                            if (!(window.open("https://swirlia.com/" + server_response.username, "_blank")))
                                parent.window.location.href = "https://swirlia.com/" + server_response.username;

                        } else {
                            if (server_response.message === "User not exists")
                                parent.window.location.href = "";
                            else if (server_response.message === "User not logged in")
                                parent.window.location.href = "";
                            else
                                display_page();
                        }

                        serverProcessing = false;
                    }
                }
            }

            //SEARCH
            const searchbox_text = document.getElementById("searchbox_text");
            const searchbox_img = document.getElementById("searchbox_img");
            searchbox_text.onfocus = function () {
                document.addEventListener('keyup', (e) => {
                    if (e.code === "Enter" || e.code === "NumpadEnter") {
                        const keyword = searchbox_text.value;
                        search(keyword);
                    }
                });
            }
            searchbox_img.onclick = function () {
                const keyword = searchbox_text.value;
                search(keyword);
            }
            function search(keyword) {
                if (!serverProcessing) {
                    serverProcessing = true;

                    if (keyword.length > 0) {
                        hide_page();

                        for (let i = 0; i < 32; i++) {
                            var div = document.getElementById("div_" + (i + 1));
                            div.style.display = "none";
                        }

                        var params = "operation=search&keyword=" + encodeURIComponent(keyword) + "&phpsessid=" + phpsessid;
                        var xhr = new XMLHttpRequest();
                        xhr.open('POST', 'https://swirlia.net/php/Index.php', true);
                        xhr.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');
                        xhr.send(params);
                        xhr.onload = function () {
                            var server_response = JSON.parse(this.response);

                            if (server_response.result === "success") {
                                const users = document.querySelectorAll(".users");

                                for (let i = 0; i < server_response.swirl.length; i++) {
                                    var div = document.getElementById("div_" + (i + 1));
                                    div.style.display = "flex";

                                    var label = document.getElementById("label_" + (i + 1));
                                    label.innerText = server_response.swirl[i].username;

                                    var img = document.getElementById("img_" + (i + 1));

                                    new Promise((resolve, reject) => {
                                        var timestamp = new Date().getTime();
                                        img.src = "https://swirlia.net/" + server_response.swirl[i].profile_img + "?t=" + timestamp;
                                    });

                                    var bio = document.getElementById("span_" + (i + 1));
                                    var limited = server_response.swirl[i].bio;
                                    if (limited != null) {
                                        if (limited.length > 97)
                                            bio.textContent = limited.substring(0, 97) + "...";
                                        else
                                            bio.textContent = limited;
                                        bio.style.display = "initial";
                                    } else {
                                        bio.textContent = "";
                                        bio.style.display = "none";
                                    }

                                    div.href = server_response.swirl[i].username;
                                    div.target = "_blank";

                                    if (users.length == 0 && (i == 30 || i == 31))
                                        div.style.visibility = "hidden";
                                }

                                display_page();
                            } else {
                                if (server_response.message === "User not exists")
                                    parent.window.location.href = "";
                                else if (server_response.message === "User not logged in")
                                    parent.window.location.href = "";
                                else
                                    display_page();
                            }

                            serverProcessing = false;
                        }
                    } else
                        getSwirl();
                }
            }

            //Display Page
            function display_page() {
                document.getElementsByClassName("loader")[0].style.display = "none";
                document.getElementById("images").style.visibility = "visible";
            }

            function hide_page() {
                document.getElementsByClassName("loader")[0].style.display = "initial";
                document.getElementById("images").style.display = "hidden";
            }
        }
    }
} catch (err) {
    document.body.innerHTML = "";
    setTimeout(() => {
        alert("Access Denied");
    }, 1000);
}