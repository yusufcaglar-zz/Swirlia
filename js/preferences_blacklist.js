var phpsessid = null;

try {
    if (window.top.location.host === "swirlia.com" || window.top.location.host === "wwww.swirlia.com") {
        var serverProcessing = true;

        window.onload = function () {
            var xhr = new XMLHttpRequest();
            xhr.open('POST', 'html/session.php', true);
            xhr.send();

            xhr.onload = function () {
                phpsessid = this.response;

                afterSessionSet();
            }

            function afterSessionSet() {
                var params = "operation=getBlacklist&phpsessid=" + phpsessid;
                var xhr = new XMLHttpRequest();
                xhr.open('POST', 'https://swirlia.net/php/Index.php', true);
                xhr.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');
                xhr.withCredentials = true;
                xhr.send(params);
                xhr.onload = function () {
                    var server_response = JSON.parse(this.response);

                    if (server_response.result === "success") {
                        //Append List
                        const ul_profile = document.getElementById("blacklist_profile");
                        ul_profile.style.display = "block";
                        const ul_chat = document.getElementById("blacklist_chat");
                        ul_chat.style.display = "block";

                        for (let i = 0; i < server_response.blacklist.length; i++) {
                            if (!server_response.blacklist[i].type) {
                                var label = document.createElement("label");
                                label.innerText = server_response.blacklist[i].name;

                                var button = document.createElement("button");
                                button.setAttribute('id', server_response.blacklist[i].sno);
                                button.innerText = "Engeli Kaldır";

                                var li = document.createElement("li");
                                li.appendChild(label);
                                li.appendChild(button);
                                ul_profile.appendChild(li);

                                button.onclick = function () {
                                    if (!serverProcessing)
                                        unblock(server_response.blacklist[i].name, server_response.blacklist[i].sno);
                                }
                            } else {
                                var label = document.createElement("label");
                                label.innerText = server_response.blacklist[i].name;

                                var button = document.createElement("button");
                                button.setAttribute('id', server_response.blacklist[i].sno);
                                button.innerText = "Engeli Kaldır";

                                var li = document.createElement("li");
                                li.appendChild(label);
                                li.appendChild(button);
                                ul_chat.appendChild(li);

                                button.onclick = function () {
                                    if (!serverProcessing)
                                        unblockChat(server_response.blacklist[i].name, server_response.blacklist[i].sno);
                                }
                            }
                        }

                        //Searchbox
                        if (ul_profile.childNodes.length !== 0) {
                            const caption_profile = document.getElementById("caption_profile");
                            caption_profile.style.display = "initial";

                            const searchbox_profile = document.getElementById("searchbox_profile");
                            const searchbox_text_profile = document.getElementById("searchbox_text_profile");
                            searchbox_profile.style.display = "flex";

                            searchbox_text_profile.oninput = function () {
                                if (!serverProcessing)
                                    search(searchbox_text_profile.value, ul_profile);
                            }
                        }

                        if (ul_chat.childNodes.length !== 0) {
                            const caption_chat = document.getElementById("caption_chat");
                            caption_chat.style.display = "initial";

                            const searchbox_chat = document.getElementById("searchbox_chat");
                            const searchbox_text_chat = document.getElementById("searchbox_text_chat");
                            searchbox_chat.style.display = "flex";

                            searchbox_text_chat.oninput = function () {
                                if (!serverProcessing)
                                    search(searchbox_text_chat.value, ul_chat);
                            }
                        }
                    } else {
                        if (server_response.message === "User not exists")
                            parent.window.location.href = "";
                        else if (server_response.message === "User not logged in")
                            parent.window.location.href = "";
                        else if (server_response.message === "Blacklist is empty") {
                            const p = document.getElementById("p");
                            p.style.display = "initial";
                        }
                    }

                    //Display Page
                    document.getElementsByClassName("loader")[0].style.display = "none";
                    document.getElementById("container").style.display = "flex";

                    serverProcessing = false;
                }
            }
        }

        function search(filter, ul) {
            for (let i = 0; i < ul.childNodes.length; i++) {
                var label = ul.childNodes[i].childNodes[0];
                var li = ul.childNodes[i];

                if (!(label.innerText.toUpperCase()).includes(filter.toUpperCase()) && filter !== "")
                    li.style.display = "none";
                else
                    li.style.display = "flex";
            }
        }

        function unblock(name, sno) {
            serverProcessing = true;

            var params = "operation=unblock&sno=" + sno + "&phpsessid=" + phpsessid;
            var xhr = new XMLHttpRequest();
            xhr.open('POST', 'https://swirlia.net/php/Index.php', true);
            xhr.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');
            xhr.withCredentials = true;
            xhr.send(params);
            xhr.onload = function () {
                const ul = document.getElementById("blacklist_profile");

                for (let i = 0; i < ul.childNodes.length; i++) {
                    if (ul.childNodes[i].childNodes[0].innerText === name) {
                        ul.removeChild(ul.childNodes[i]);
                        break;
                    }
                }

                if (ul.childNodes.length === 0) {
                    ul.style.display = "none";

                    const caption_profile = document.getElementById("caption_profile");
                    caption_profile.style.display = "none";

                    const searchbox = document.getElementById("searchbox_profile");
                    searchbox.style.display = "none";
                }

                checkBlacklistClear();

                serverProcessing = false;
            }
        }

        function unblockChat(name, sno) {
            serverProcessing = true;

            var params = "operation=unblock&sno=" + sno + "&phpsessid=" + phpsessid;
            var xhr = new XMLHttpRequest();
            xhr.open('POST', 'https://swirlia.net/php/Index.php', true);
            xhr.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');
            xhr.withCredentials = true;
            xhr.send(params);
            xhr.onload = function () {
                const ul = document.getElementById("blacklist_chat");

                for (let i = 0; i < ul.childNodes.length; i++) {
                    if (ul.childNodes[i].childNodes[0].innerText === name) {
                        ul.removeChild(ul.childNodes[i]);
                        break;
                    }
                }

                if (ul.childNodes.length === 0) {
                    ul.style.display = "none";

                    const caption_chat = document.getElementById("caption_chat");
                    caption_chat.style.display = "none";

                    const searchbox = document.getElementById("searchbox_chat");
                    searchbox.style.display = "none";
                }

                checkBlacklistClear();

                serverProcessing = false;
            }
        }

        function checkBlacklistClear() {
            const ul_profile = document.getElementById("blacklist_profile");
            const ul_chat = document.getElementById("blacklist_chat");

            if (ul_profile.childNodes.length === 0 && ul_chat.childNodes.length === 0) {
                const p = document.getElementById("p");
                p.style.display = "initial";
            }
        }
    }
} catch (err) {
    document.body.innerHTML = "";
    setTimeout(() => {
        alert("Access Denied");
    }, 1000);
}