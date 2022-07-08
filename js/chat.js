//Declaring
var isSelf = false;
var isError = false;
var isAnonim = true;
var isLoggedIn = false;
var notification_interval = 0;
var typing_timeout = 0;
var kick_timeout = 0;
var warning_timeout = 0;
var controlNewMessage = false;

var requested_username = null;
var anon_id = null;
var token = null;
var phpsessid = null;

const conversations = [];
var currentRoom = null;

const ul = document.getElementById("tabs_ul");
const chat = document.getElementsByClassName("chat")[0];
const block = document.getElementById("block_label");

const message = document.getElementById("message_");
const photo = document.getElementById("photo_");
const photo_clicked = document.getElementById("photo_clicked_");

const anonimityy = document.getElementById("anonimity");

const blank_screen = document.getElementById("blank_screen_");
const receiver_blank = "Henüz aktif konuşmanız yok 😔<br />Fakat bu ekran açık kaldığı sürece mesaj alabilirsiniz 😏";
const sender_blank = "Yukarıdan ilk mesajını yazabilirsin 👆<br />Lütfen nezaket kurallarına uymayı unutma 🙄";
const typing = document.getElementById("typing_");

var sfx_allowed = true;

//Sounds
const start_conversation_sound = document.getElementById("start_conversation_sound");
const end_conversation_sound = document.getElementById("end_conversation_sound");
const message_sound = document.getElementById("message_sound");
const kick_alarming_sound = document.getElementById("kick_alarming_sound");

/////////////////////////FORM/////////////////////////
const overlayy = document.getElementById("overlay");

function openForm(form, id, material_type, material) {
    if (form == null)
        return;

    form.classList.add('active');
    overlayy.classList.add('active');

    if (id === "report") {
        if (material_type)
            report(form, "image", material);
        else
            report(form, "message", material);
    } else {
        const user_img = document.getElementById("user_img");

        new Promise((resolve, reject) => {
            user_img.src = id.src;
        });
    }

    //Close form
    overlayy.onclick = function () {
        if (form.classList.contains("active"))
            closeForm(form);
    }

    document.addEventListener('keyup', (e) => {
        if (e.code === "Escape" && form.classList.contains("active")) {
            closeForm(form);
        }
    });
}

function closeForm(form) {
    form.classList.remove('active');
    overlayy.classList.remove('active');
}

function report(form, material_type, material) {
    form.style.display = "initial";

    if (isLoggedIn) {
        const p = document.getElementById("report_p");
        p.style.display = "none";

        const improper_img = document.getElementById("improper_img");
        const improper_message = document.getElementById("improper_message");
        const other = document.getElementById("other");
        const improper_img_label = document.getElementById("improper_img_label");
        const improper_message_label = document.getElementById("improper_message_label");
        const other_label = document.getElementById("other_label");

        improper_img.checked = false;
        improper_img.disabled = false;
        improper_img_label.style.color = "initial";
        improper_message.checked = false;
        improper_message.disabled = false;
        improper_message_label.style.color = "initial";
        other.checked = false;
        other.disabled = false;
        other_label.style.color = "initial";

        const extra_textarea = document.getElementById("extra_textarea");
        extra_textarea.value = "";

        improper_message_label.onclick = function () {
            if (material_type === "message")
                improper_message.checked = true;
        }

        other_label.onclick = function () {
            if (material_type === "message")
                other.checked = true;
        }

        if (material_type === "image") {
            improper_img.checked = true;
            improper_message.disabled = true;
            improper_message_label.style.color = "grey";
            other.disabled = true;
            other_label.style.color = "grey";
        } else {
            improper_img.disabled = true;
            improper_img_label.style.color = "grey";
        }

        const send_report = document.getElementById("send_report");

        send_report.onclick = function () {
            const error_p = document.getElementById("error_p");

            if (!document.getElementById("improper_img").checked
                && !document.getElementById("improper_message").checked
                && !document.getElementById("other").checked) {
                error_p.style.display = "initial";
                error_p.innerText = "*Lütfen bir şikayet sebebi seçiniz";
            } else {
                error_p.style.display = "none";

                var reason = "Improper image";
                if (document.getElementById("improper_message").checked)
                    reason = "Improper message";
                else if (document.getElementById("other").checked)
                    reason = "Other";

                var extra = document.getElementById("extra_textarea").value;

                const index = conversations.findIndex(user => user.room === currentRoom);

                var is_anonim, anon_name;

                if (isSelf) {
                    if (conversations[index].isAnonim) {
                        is_anonim = "1";

                        const split = conversations[index].username.split("-");
                        anon_name = split[1];
                    } else {
                        is_anonim = "2";
                        anon_name = conversations[index].username;
                    }
                } else {
                    is_anonim = "2";
                    anon_name = requested_username;
                }

                if (material_type === "image")
                    handleReportUpload(form, reason, extra, material_type, material, is_anonim, anon_name);
                else {
                    var params = "operation=report&id=" + "" + "&reason=" + reason
                        + "&message=" + encodeURIComponent(extra) + "&material_type=" + material_type + "&material=" + encodeURIComponent(material)
                        + "&is_anonim=" + is_anonim + "&anon_name=" + anon_name + "&phpsessid=" + phpsessid;
                    var xhr = new XMLHttpRequest();
                    xhr.open('POST', 'https://swirlia.net/php/Profile.php', true);
                    xhr.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');
                    xhr.withCredentials = true;
                    xhr.send(params);
                    xhr.onload = function () {
                        closeForm(form);

                        const snackbar = document.getElementById("snackbar");
                        snackbar.className = "show";

                        setTimeout(function () {
                            snackbar.className = snackbar.className.replace("show", "");
                        }, 3000);
                    }
                }

                clearTimeout(kick_timeout);
                clearTimeout(warning_timeout);
            }

        }
    } else {
        const category_p = document.getElementById("category_p");
        const radio_div = document.getElementById("radio_div");
        const extra_p = document.getElementById("extra_p");
        const extra_textarea = document.getElementById("extra_textarea");
        const send_report = document.getElementById("send_report");

        category_p.style.display = "none";
        radio_div.style.display = "none";
        extra_p.style.display = "none";
        extra_textarea.style.display = "none";
        send_report.style.display = "none";
    }
}

//Notify
const notify = document.getElementById("notify_");
notify.onclick = function () {
    notify.style.display = "none";
    chat.scrollTop = 0;
}

//Page Visibility API
var hidden, visibilityChange, pageVisibilityAPI = true;
if (typeof document.hidden !== "undefined") {
    hidden = "hidden";
    visibilityChange = "visibilitychange";
} else if (typeof document.msHidden !== "undefined") {
    hidden = "msHidden";
    visibilityChange = "msvisibilitychange";
} else if (typeof document.webkitHidden !== "undefined") {
    hidden = "webkitHidden";
    visibilityChange = "webkitvisibilitychange";
}

if (typeof document.addEventListener === "undefined" || hidden === undefined) {
    console.log("This demo requires a browser, such as Google Chrome or Firefox, that supports the Page Visibility API.");
    pageVisibilityAPI = false;
}

window.onload = function (e) {
    //Disable Image Dragging
    var event = e || window.event, imgs, i;
    if (event.preventDefault) {
        imgs = document.getElementsByTagName('img');

        for (i = 0; i < imgs.length; i++) {
            imgs[i].onmousedown = disableDragging;
        }
    }

    //Requested username
    var temp = window.location.href.split("=");
    requested_username = temp[1].split("&")[0];
    if (temp[3] === "false")
        sfx_allowed = false;
    temp = temp[2].split("=");
    phpsessid = temp[0].split("&")[0];

    var allowed_characters = ["A", "B", "C", "D", "E", "F", "G", "H", "I", "J", "K", "L", "M", "N", "O", "P", "Q", "R",
        "S", "T", "U", "V", "W", "X", "Y", "Z", "a", "b", "c", "d", "e", "f", "g", "h", "i", "j",
        "k", "l", "m", "n", "o", "p", "q", "r", "s", "t", "u", "v", "w", "x", "y", "z", "_", ".",
        "-", "0", "1", "2", "3", "4", "5", "6", "7", "8", "9"];
    var username_characters = requested_username.split("");

    let username_check = true;
    for (let i = 0; i < username_characters.length; i++) {
        let check = false;

        for (let j = 0; j < allowed_characters.length; j++) {
            if (username_characters[i] === allowed_characters[j])
                check = true;
        }

        if (!check) {
            username_check = false;
            break;
        }
    }

    if (requested_username.length < 4 || requested_username.length > 16 || !username_check || requested_username.includes(".html")) {
        document.body.style = "background-color: #eee; display:flex; flex-direction:column; align-items:center; justify-content:center; width:100%; height:100vh; padding:0px; margin:1em;";
        document.body.innerHTML = "<img loading='lazy' alt='' src='/images/search_failed.png' style='object-fit:contain; width:100px; height:100px;' /><p style='color:indianred; font-size:x-large; font-weight:bold; text-align:center; font-family:Lucida Console;'>KULLANICI BULUNAMADI</p>";
        isError = true;
    } else {
        var params = "operation=getChat&username=" + requested_username + "&phpsessid=" + phpsessid;
        var xhr = new XMLHttpRequest();
        xhr.open('POST', 'https://swirlia.net/php/Chat.php', true);
        xhr.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');
        xhr.withCredentials = true;
        xhr.send(params);
        xhr.onload = function () {
            var server_response = JSON.parse(this.response);

            if (server_response.result === "success") {
                const socket = io();

                //Page Visibility
                document.addEventListener(visibilityChange, function () {
                    if ((currentRoom != null || !isSelf) && conversations != null) {
                        if (document.visibilityState !== hidden) {
                            var index;
                            if (isSelf)
                                index = conversations.findIndex(user => user.room === currentRoom);
                            else
                                index = 0;

                            if (conversations[index] != null) {
                                if (conversations[index].chat_ul != null) {
                                    if (conversations[index].chat_ul.childNodes.length > 0) {
                                        if (document.getElementsByClassName("chat")[0].scrollTop <= 70) {
                                            notify.style.display = "none";

                                            if (conversations[index].chat_ul.firstChild.hasAttribute("data-seen") && pageVisibilityAPI) {
                                                if (conversations[index].chat_ul.firstChild.getAttribute("data-seen") === "false") {
                                                    conversations[index].chat_ul.firstChild.setAttribute("data-seen", true);

                                                    socket.emit("seen", conversations[index].room);

                                                    control_new_message(socket);
                                                }
                                            } else if (pageVisibilityAPI) {
                                                conversations[index].chat_ul.firstChild.setAttribute("data-seen", true);

                                                control_new_message(socket);
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                });

                //Block
                block.onclick = function () {
                    if (confirm("Bu göndericiden bir daha mesaj almayacaksınız, emin misiniz?"
                        + "\nEngelli listenizi yukarıdaki menüden Tercihler'i tıkladıktan sonra açılan pencerede Kara Liste sekmesinde görebilirsiniz.")) {
                        try {
                            var index = conversations.findIndex(user => user.room === currentRoom);

                            var ul_index;
                            for (let i = 0; i < ul.childNodes.length; i++) {
                                if (ul.childNodes[i].childNodes[0].innerText === currentRoom) {
                                    ul_index = i;
                                    break;
                                }
                            }

                            //Close Conversation From Both Sides
                            socket.emit("block", conversations[index].room);
                            socket.emit("closeConversation", conversations[index].room);

                            conversations.splice(index, 1);
                            ul.removeChild(ul.childNodes[ul_index]);
                            chat.removeChild(chat.childNodes[0]);

                            if (conversations.length === 0 || ul.childNodes.length === 0) {
                                message.value = "";
                                message.disabled = true;
                                photo.disabled = true;
                                currentRoom = null;
                                notify.style.display = "none";
                                block.style.visibility = "hidden";
                                typing.style.display = "none";
                                blank_screen.innerHTML = receiver_blank;
                            } else {
                                if (ul_index === ul.childNodes.length)
                                    --ul_index;

                                index = conversations.findIndex(user => user.room === ul.childNodes[ul_index].childNodes[0].innerText);

                                chat.appendChild(conversations[index].chat_ul);
                                currentRoom = conversations[index].room;

                                if (!conversations[index].active) {
                                    message.value = "";
                                    message.disabled = true;
                                    photo.disabled = true;
                                } else {
                                    message.disabled = false;
                                    photo.disabled = false;
                                }

                                if (conversations[index].typing && conversations[index].active) {
                                    typing.style.display = "initial";
                                    scrollUp(false, socket, false, false, true);
                                } else
                                    typing.style.display = "none";

                                const li = ul.childNodes[ul_index];
                                li.style.backgroundColor = "#e2e2e2";

                                li.onmouseover = function () {
                                    li.style.backgroundColor = "#d8d8d8";
                                }
                                li.onmouseout = function () {
                                    li.style.backgroundColor = "#e2e2e2";
                                }
                            }

                            control_new_message(socket);

                            clearTimeout(kick_timeout);
                            clearTimeout(warning_timeout);
                        } catch (err) {
                            console.log(err);
                        }
                    }
                }

                //Start Conversation
                socket.on("startConversation", (message) => {
                    if (!isSelf) {
                        document.getElementsByClassName("tabs")[0].style.display = "none";
                        document.getElementById("anonimity_div").style.display = "flex";

                        if (isLoggedIn) {
                            anonimityy.onclick = function () {
                                anonimityClicked(socket, server_response.username);
                            }
                        } else {
                            anonimityy.style.pointerEvents = "none";
                            anonimityy.innerText = "Anonimlikten çıkabilmek için hesap oluşturun veya giriş yapın"
                        }

                        afterHandShake(socket, 0);

                        blank_screen.innerHTML = sender_blank;

                        //Chat Sfx
                        if (sfx_allowed) {
                            new Promise((resolve, reject) => {
                                document.getElementsByClassName("sounds_enabled")[0].src = "images/sounds_enabled.png";
                            });
                        } else {
                            new Promise((resolve, reject) => {
                                document.getElementsByClassName("sounds_enabled")[0].src = "images/sounds_disabled.png";
                            });
                        }

                        document.getElementsByClassName("sounds_enabled")[0].onclick = function () {
                            if (!sfx_allowed) {
                                sfx_allowed = true;
                                new Promise((resolve, reject) => {
                                    document.getElementsByClassName("sounds_enabled")[0].src = "images/sounds_enabled.png";
                                });
                            } else {
                                sfx_allowed = false;
                                new Promise((resolve, reject) => {
                                    document.getElementsByClassName("sounds_enabled")[0].src = "images/sounds_disabled.png";
                                });
                            }
                        }

                        //Display Page
                        document.getElementsByClassName("loader")[0].style.display = "none";
                        document.getElementById("container").style.display = "flex";
                    }

                    const chat_ul = document.createElement("div");
                    chat_ul.classList.add("chat_ul");

                    const user = { room: message.bundle.room, username: message.username, pendingMessage: false, isAnonim: true, received_messages: 0, sent_messages: 0, allow_media: false, chat_ul: chat_ul, active: true, typing: false };
                    conversations.push(user);

                    chat.onscroll = function () {
                        var index;
                        if (isSelf)
                            index = conversations.findIndex(user => user.room === currentRoom);
                        else
                            index = 0;

                        if (index !== -1) {
                            if (document.getElementsByClassName("chat")[0].scrollTop <= 70) {
                                notify.style.display = "none";

                                if (conversations[index].chat_ul.firstChild.hasAttribute("data-seen") && pageVisibilityAPI) {
                                    if (conversations[index].chat_ul.firstChild.getAttribute("data-seen") === "false") {
                                        conversations[index].chat_ul.firstChild.setAttribute("data-seen", true);

                                        socket.emit("seen", conversations[index].room);

                                        control_new_message(socket);
                                    }
                                } else if (pageVisibilityAPI) {
                                    conversations[index].chat_ul.firstChild.setAttribute("data-seen", true);

                                    control_new_message(socket);
                                }
                            }
                        }
                    }
                });

                //End Conversation
                socket.on("endConversation", (message) => {
                    if (!isSelf)
                        socket.disconnect();

                    const index = conversations.findIndex(user => user.room === message.bundle.room);

                    const chat_status = document.createElement("label");
                    chat_status.classList.add("chat_status");

                    if (index !== -1) {
                        if (conversations[index].received_messages !== 0 || conversations[index].sent_messages !== 0 || !isSelf) {
                            conversations[index].active = false;

                            typing.style.display = "none";
                            blank_screen.innerHTML = "";

                            if (!isSelf && isAnonim) {
                                anonimityy.innerText = "Anonimlikten çıkmadınız";
                                anonimityy.style.pointerEvents = "none";
                                document.getElementsByClassName("sounds_enabled")[0].style.pointerEvents = "none";
                            }

                            if (sfx_allowed)
                                end_conversation_sound.play();

                            if (message.text === "User doesn't want to take messages from guests") {
                                if (isSelf) {
                                    chat_status.innerText = "KULLANICI KAYITLI OLMADIĞI İÇİN SOHBET KAPATILDI";
                                    conversations[index].chat_ul.insertBefore(chat_status, conversations[index].chat_ul.firstChild);

                                    if (currentRoom === message.bundle.room) {
                                        document.getElementById("message_").value = "";
                                        document.getElementById("message_").disabled = true;
                                        photo.disabled = true;
                                    }
                                } else {
                                    chat_status.innerHTML = "KULLANICI SADECE KAYITLI ÜYELERDEN MESAJ ALMAK İSTİYOR, LÜTFEN GİRİŞ YAPIN VEYA " +
                                        "<a href='https://swirlia.com' target='_blank'>ÜCRETSİZ HESAP OLUŞTURUN</a>";
                                    conversations[index].chat_ul.insertBefore(chat_status, conversations[index].chat_ul.firstChild);

                                    document.getElementById("message_").value = "";
                                    document.getElementById("message_").disabled = true;
                                    photo.disabled = true;
                                }
                                isError = true;
                            } else if (message.text === "User not exists") {
                                chat_status.innerText = "KULLANICI BULUNAMADI";
                                conversations[index].chat_ul.insertBefore(chat_status, conversations[index].chat_ul.firstChild);

                                if (currentRoom === message.bundle.room || !isSelf) {
                                    document.getElementById("message_").value = "";
                                    document.getElementById("message_").disabled = true;
                                    photo.disabled = true;
                                }

                                isError = true;
                            } else if (message.text === "User either not exists or offline") {
                                chat_status.innerText = "KULLANICI BULUNAMADI VEYA ÇEVRİMDIŞI";
                                conversations[index].chat_ul.insertBefore(chat_status, conversations[index].chat_ul.firstChild);

                                if (currentRoom === message.bundle.room || !isSelf) {
                                    document.getElementById("message_").value = "";
                                    document.getElementById("message_").disabled = true;
                                    photo.disabled = true;
                                }

                                isError = true;
                            } else if (message.text === "Has disconnected") {
                                chat_status.innerText = "KULLANICININ BAĞLANTISI KESİLDİ";
                                conversations[index].chat_ul.insertBefore(chat_status, conversations[index].chat_ul.firstChild);

                                if (currentRoom === message.bundle.room || (!isSelf && conversations[index].sent_messages > 0)) {
                                    document.getElementById("message_").value = "";
                                    document.getElementById("message_").disabled = true;
                                    photo.disabled = true;
                                }

                                if (!isSelf && conversations[index].sent_messages === 0) {
                                    document.body.style = "background-color: #eee; display:flex; flex-direction:column; align-items:center; justify-content:center; width:100%; height:100vh; padding:0px; margin:1em;";
                                    document.body.innerHTML = "<img loading='lazy' alt='' src='/images/server_error.png' style='object-fit:contain; width:100px; height:100px;' /><p style='color:indianred; font-size:x-large; font-weight:bold; text-align:center; font-family:Lucida Console;'>KULLANICININ BAĞLANTISI KESİLDİ</p>";
                                }

                                isError = true;
                            } else if (message.text === "User is offline") {
                                chat_status.innerText = "ÇEVRİMDIŞI";
                                conversations[index].chat_ul.insertBefore(chat_status, conversations[index].chat_ul.firstChild);

                                if (currentRoom === message.bundle.room || !isSelf) {
                                    document.getElementById("message_").value = "";
                                    document.getElementById("message_").disabled = true;
                                    photo.disabled = true;
                                }

                                isError = true;
                            } else if (message.text === "Sender online status changed") {
                                chat_status.innerText = "ÇEVRİMDIŞI";
                                conversations[index].chat_ul.insertBefore(chat_status, conversations[index].chat_ul.firstChild);

                                if (currentRoom === message.bundle.room || !isSelf) {
                                    document.getElementById("message_").value = "";
                                    document.getElementById("message_").disabled = true;
                                    photo.disabled = true;
                                }

                                isError = true;
                            } else if (message.text === "Has closed chat") {
                                chat_status.innerText = "KULLANICI KONUŞMAYI SONLANDIRDI";
                                conversations[index].chat_ul.insertBefore(chat_status, conversations[index].chat_ul.firstChild);

                                if (currentRoom === message.bundle.room || !isSelf) {
                                    document.getElementById("message_").value = "";
                                    document.getElementById("message_").disabled = true;
                                    photo.disabled = true;
                                }

                                isError = true;
                            } else if (message.text === "Server failed") {
                                chat_status.innerText = "SUNUCU HATASI, LÜTFEN SAYFAYI YENİLEYİNİZ";
                                conversations[index].chat_ul.insertBefore(chat_status, conversations[index].chat_ul.firstChild);

                                if (currentRoom === message.bundle.room || !isSelf) {
                                    document.getElementById("message_").value = "";
                                    document.getElementById("message_").disabled = true;
                                    photo.disabled = true;
                                }

                                isError = true;
                            } else {
                                chat_status.innerText = "SUNUCU HATASI, LÜTFEN SAYFAYI YENİLEYİNİZ";
                                conversations[index].chat_ul.insertBefore(chat_status, conversations[index].chat_ul.firstChild);

                                if (currentRoom === message.bundle.room || !isSelf) {
                                    document.getElementById("message_").value = "";
                                    document.getElementById("message_").disabled = true;
                                    photo.disabled = true;
                                }

                                isError = true;
                            }
                        }

                        scrollUp(false, null, false, false, false);
                    } else if (!isError) {
                        conversations[index].active = false;

                        chat_status.innerText = "SUNUCU HATASI, LÜTFEN SAYFAYI YENİLEYİNİZ";
                        conversations[index].chat_ul.insertBefore(chat_status, conversations[index].chat_ul.firstChild);

                        if (currentRoom === message.bundle.room || !isSelf) {
                            document.getElementById("message_").value = "";
                            document.getElementById("message_").disabled = true;
                            photo.disabled = true;
                            typing.style.display = "none";
                        }

                        isError = true;

                        scrollUp(false, null, false, false, false);
                    }
                });

                //Message
                socket.on("message", (message) => {
                    const index = conversations.findIndex(user => user.room === message.bundle.room)

                    conversations[index].received_messages++;

                    if (isSelf && conversations[index].received_messages === 1) {
                        if (currentRoom == null) {
                            currentRoom = message.bundle.room;
                            chat.appendChild(conversations[index].chat_ul);
                        }

                        afterHandShake(socket, index);
                        addAnon(conversations[index].username, socket, conversations[index].room);

                        if (sfx_allowed)
                            start_conversation_sound.play();
                    }

                    var ul_index;
                    for (let i = 0; i < ul.childNodes.length; i++) {
                        if (ul.childNodes[i].childNodes[0].innerText === message.bundle.room) {
                            ul_index = i;
                            break;
                        }
                    }

                    addMessage(message, conversations[index].room, index, ul_index, socket);
                });

                //Connected
                socket.on("connect", () => {
                    //Online
                    if (server_response.isOnline === "online") {
                        isLoggedIn = true;

                        //Self
                        if (server_response.username === requested_username) {
                            isSelf = true;

                            params = "operation=createToken&token=" + socket.id + "&phpsessid=" + phpsessid;
                            xhr = new XMLHttpRequest();
                            xhr.open('POST', 'https://swirlia.net/php/Chat.php', true);
                            xhr.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');
                            xhr.withCredentials = true;
                            xhr.send(params);

                            xhr.onload = function () {
                                server_response = JSON.parse(this.response);

                                if (server_response.result === "failure") {
                                    document.body.style = "background-color: #eee; display:flex; flex-direction:column; align-items:center; justify-content:center; width:100%; height:100vh; padding:0px; margin:1em;";
                                    document.body.innerHTML = "<img loading='lazy' alt='' src='/images/server_failure.png' style='object-fit:contain; width:100px; height:100px;' /><p style='color:indianred; font-size:x-large; font-weight:bold; text-align:center; font-family:Lucida Console;'>SUNUCU HATASI, LÜTFEN SAYFAYI YENİLEYİN VE GİRİŞ YAPTIĞINIZDAN EMİN OLUN</p>";
                                    isError = true;
                                    socket.disconnect();
                                } else {
                                    blank_screen.innerHTML = receiver_blank;

                                    //Chat Sfx
                                    if (sfx_allowed) {
                                        new Promise((resolve, reject) => {
                                            document.getElementsByClassName("sounds_enabled")[1].src = "images/sounds_enabled.png";
                                        });
                                    } else {
                                        new Promise((resolve, reject) => {
                                            document.getElementsByClassName("sounds_enabled")[1].src = "images/sounds_disabled.png";
                                        });
                                    }

                                    document.getElementsByClassName("sounds_enabled")[1].onclick = function () {
                                        if (!sfx_allowed) {
                                            sfx_allowed = true;
                                            new Promise((resolve, reject) => {
                                                document.getElementsByClassName("sounds_enabled")[1].src = "images/sounds_enabled.png";
                                            });
                                        } else {
                                            sfx_allowed = false;
                                            new Promise((resolve, reject) => {
                                                document.getElementsByClassName("sounds_enabled")[1].src = "images/sounds_disabled.png";
                                            });
                                        }
                                    }

                                    //Display Page
                                    document.getElementsByClassName("loader")[0].style.display = "none";
                                    document.getElementById("container").style.display = "flex";
                                }
                            }

                            //Not Self
                        } else {
                            anon_id = server_response.anon_id;

                            socket.emit("createRoom", {
                                id: md5("" + token + socket.id),
                                senderIp: server_response.ip, senderId: server_response.id, senderUsername: anon_id, senderSocket: socket.id, senderOnline: true, senderAnonim: true,
                                receiverId: server_response.requested_id, receiverUsername: requested_username, receiverSocket: server_response.token
                            });
                        }

                        //Offline
                    } else {
                        anon_id = server_response.anon_id;

                        socket.emit("createRoom", {
                            id: md5("" + token + socket.id),
                            senderIp: server_response.ip, senderId: server_response.id, senderUsername: anon_id, senderSocket: socket.id, senderOnline: false, senderAnonim: true,
                            receiverId: server_response.requested_id, receiverUsername: requested_username, receiverSocket: server_response.token
                        });
                    }
                });

                //Message Callback
                socket.on("messageCallback", (message) => {
                    const index = conversations.findIndex(user => user.room === message.bundle.room);

                    const div = conversations[index].chat_ul.childNodes[message.bundle.message_index];
                    const sending = div.childNodes[1];

                    if (message.text === "success") {
                        sending.style.display = "none";

                        const chat_div = div.childNodes[0];
                        const time = chat_div.childNodes[2];

                        const time_split = message.time.split(":");
                        time.innerHTML = time_split[0] + ":" + time_split[1] + "<span style='font-size:xx-small; color:#888'>  " + time_split[2] + "</span>";

                        if (sfx_allowed)
                            message_sound.play();

                        scrollUp(false, socket, false, false, false);
                    } else {
                        sending.style.animation = "none";
                        sending.style.webkitAnimation = "none";

                        new Promise((resolve, reject) => {
                            sending.src = "images/sending_failed.png";
                        });

                        sending.style.cursor = "pointer";

                        sending.onclick = function () {
                            sending.style.cursor = "default";

                            new Promise((resolve, reject) => {
                                sending.src = "images/sending.png"
                            });

                            sending.style.animation = "spin 2s linear infinite";
                            sending.style.webkitAnimation = "spin 2s linear infinite";

                            if (isSelf)
                                var index = conversations.findIndex(user => user.room === currentRoom);
                            else
                                var index = conversations.findIndex(user => user.username === requested_username);

                            socket.emit("message", {
                                id: conversations[index].room, isMedia: message.bundle.isMedia, message: message.bundle.text,
                                message_index: message.bundle.message_index, shouldStartConversation: ((conversations[index].sent_messages === 1 && !isSelf) ? true : false)
                            });
                        }
                    }
                });

                //Swap Anonimity
                socket.on("swapAnonimity", (message) => {
                    if (isSelf) {
                        const index = conversations.findIndex(user => user.room === message.bundle.room);

                        var ul_index;
                        for (let i = 0; i < ul.childNodes.length; i++) {
                            if (ul.childNodes[i].childNodes[0].innerText === message.bundle.room) {
                                ul_index = i;
                                break;
                            }
                        }

                        if (conversations[index].received_messages !== 0) {
                            const li = ul.childNodes[ul_index]

                            li.childNodes[1].innerText = message.username;

                            const h4 = document.createElement("h4");
                            h4.innerText = "KULLANICI ANONİMLİKTEN ÇIKTI";
                            checkSeen(index);
                            conversations[index].chat_ul.insertBefore(h4, conversations[index].chat_ul.firstChild);

                            if (message.bundle.room === currentRoom)
                                scrollUp(false, socket, false, false, false);
                            else {
                                li.style.backgroundColor = "#ffff9e";

                                li.onmouseover = function () {
                                    li.style.backgroundColor = "#ffff7a";
                                }

                                li.onmouseout = function () {
                                    li.style.backgroundColor = "#ffff9e";
                                }
                            }
                        } else
                            conversations[index].pendingMessage = true;

                        conversations[index].isAnonim = false;
                        conversations[index].username = message.username;
                    }
                });

                //Seen
                socket.on("seen", (message) => {
                    if (pageVisibilityAPI) {
                        const index = conversations.findIndex(user => user.room === message.bundle.room);

                        const time_split = message.time.split(":");
                        const h6 = document.createElement("h6");

                        h6.setAttribute("data-seen-notify", true);
                        h6.innerHTML = "✔✔&nbsp;&nbsp;&nbsp;" + time_split[0] + ":" + time_split[1] + "<span style='font-size:xx-small;'>  " + time_split[2] + "</span>";

                        conversations[index].chat_ul.insertBefore(h6, conversations[index].chat_ul.childNodes[1]);

                        if (message.bundle.room === currentRoom || !isSelf)
                            scrollUp(false, socket, false, false, false);
                    }
                });

                //Typing
                socket.on("typing", (message) => {
                    const index = conversations.findIndex(user => user.room === message.bundle.room);

                    conversations[index].typing = message.bundle.status;

                    if (message.bundle.room === currentRoom || !isSelf) {
                        if (message.bundle.status) {
                            typing.style.display = "initial";
                            scrollUp(false, socket, false, false, true);
                        } else
                            typing.style.display = "none";
                    }
                });
            } else if (server_response.message === "User doesn't want to take messages from guests") {
                document.body.style = "background-color: #eee; display:flex; flex-direction:column; align-items:center; justify-content:center; width:100%; height:100vh; padding:0px; margin:1em;";
                document.body.innerHTML = "<img loading='lazy' alt='' src='/images/confused.png' style='object-fit:contain; width:100px; height:100px;' /><p style='color:dodgerblue; font-size:x-large; font-weight:bold; text-align:center; font-family:Lucida Console;'>KULLANICI SADECE KAYITLI ÜYELERDEN MESAJ ALMAK İSTİYOR, LÜTFEN GİRİŞ YAPIN VEYA " + "<a href='https://swirlia.com' target='_blank'>ÜCRETSİZ HESAP OLUŞTURUN</a></p>";
                isError = true;
            } else if (server_response.message === "User is offline") {
                document.body.style = "background-color: #eee; display:flex; flex-direction:column; align-items:center; justify-content:center; width:100%; height:100vh; padding:0px; margin:1em;";
                document.body.innerHTML = "<img loading='lazy' alt='' src='/images/offline_user.png' style='object-fit:contain; width:100px; height:100px;' /><p style='color:indianred; font-size:x-large; font-weight:bold; text-align:center; font-family:Lucida Console;'>ÇEVRİMDIŞI</p>";
                isError = true;
            } else if (server_response.message === "A room have been created already") {
                document.body.style = "background-color: #eee; display:flex; flex-direction:column; align-items:center; justify-content:center; width:100%; height:100vh; padding:0px; margin:1em;";
                document.body.innerHTML = "<img loading='lazy' alt='' src='/images/alarm.png' style='object-fit:contain; width:100px; height:100px;' /><p style='color:indianred; font-size:x-large; font-weight:bold; text-align:center; font-family:Lucida Console;'>BAŞKA BİR SEKMEDE SOHBET EKRANI ZATEN AÇIK.<br />EĞER DEĞİL İSE LÜTFEN SAYFAYI YENİLEYİNİZ</p>";
                isError = true;
            } else if (server_response.message === "User either not exists or offline") {
                document.body.style = "background-color: #eee; display:flex; flex-direction:column; align-items:center; justify-content:center; width:100%; height:100vh; padding:0px; margin:1em;";
                document.body.innerHTML = "<img loading='lazy' alt='' src='/images/search_suspicious.png' style='object-fit:contain; width:100px; height:100px;' /><p style='color:indianred; font-size:x-large; font-weight:bold; text-align:center; font-family:Lucida Console;'>KULLANICI BULUNAMADI VEYA ÇEVRİMDIŞI</p>";
                isError = true;
            } else {
                document.body.style = "background-color: #eee; display:flex; flex-direction:column; align-items:center; justify-content:center; width:100%; height:100vh; padding:0px; margin:1em;";
                document.body.innerHTML = "<img loading='lazy' alt='' src='/images/search_failed.png' style='object-fit:contain; width:100px; height:100px;' /><p style='color:indianred; font-size:x-large; font-weight:bold; text-align:center; font-family:Lucida Console;'>KULLANICI BULUNAMADI</p>";
                isError = true;
            }
        }
    }
}

function afterHandShake(socket, index) {
    message.disabled = false;
    photo.disabled = false;

    message.addEventListener('keydown', (e) => {
        var evtobj = window.event ? event : e;

        if ((e.code === "Enter" || e.code === "NumpadEnter" || evtobj.keyCode == 13) && !(evtobj.keyCode == 13 && evtobj.ctrlKey)) {
            e.preventDefault();

            if (message.value.trim().length > 0 && message.value.length <= 1024) {
                sendMessage(false, message.value, socket);

                message.value = "";
                message.focus();
            }
        } else if (evtobj.keyCode == 13 && evtobj.ctrlKey && message.value.length <= 1024)
            message.value = message.value + "\n";
    });

    message.addEventListener('keyup', (e) => {
        var evtobj = window.event ? event : e;

        if ((e.code === "Enter" || e.code === "NumpadEnter" || evtobj.keyCode == 13) && !evtobj.ctrlKey) {
            message.value = "";
            message.focus();
        }        
    });

    message.addEventListener('input', (e) => {
        if (message.value === "") {
            clearTimeout(typing_timeout);
            socket.emit("typing", { id: conversations[index].room, status: false });
        } else {
            if (typing_timeout)
                clearTimeout(typing_timeout);

            const params = { socket: socket, index: index };
            typing_timeout = setTimeout(typing_countdown, 1000, params);
            socket.emit("typing", { id: conversations[index].room, status: true });
        }
    });

    photo.onclick = function () {
        if (!photo.disabled)
            photo_clicked.click();

        photo_clicked.onchange = function () {
            const img_input = document.getElementById("photo_clicked_").files[0];

            if (img_input !== undefined) {
                if (img_input.type === "image/png" || img_input.type === "image/jpeg" || img_input.type === "image/jpg") {
                    if (img_input.size <= 25000000) {
                        document.getElementById("photo_").readOnly = true;
                        document.getElementById("photo_clicked_").readOnly = true;

                        sendMessage(true, img_input, socket);
                    }
                }
            }
        }
    }
}

function handleImageUpload(imageFile, index, message_index, socket) {
    //console.log('originalFile instanceof Blob', imageFile instanceof Blob); // true
    //console.log(`originalFile size ${imageFile.size / 1024 / 1024} MB`);

    var options = {
        maxSizeMB: 1,
        maxWidthOrHeight: 8182,
        useWebWorker: true
    }

    imageCompression(imageFile, options)
        .then(function (compressedFile) {
            //console.log('compressedFile instanceof Blob', compressedFile instanceof Blob); // true
            //console.log(`compressedFile size ${compressedFile.size / 1024 / 1024} MB`); // smaller than maxSizeMB

            return uploadToServer(compressedFile);
        })
        .catch(function (error) {
            console.log(error.message);
        });

    function uploadToServer(compressedFile) {
        var fileReader = new FileReader();
        fileReader.readAsDataURL(compressedFile);

        fileReader.onload = function (fileLoadedEvent) {
            socket.emit("message", {
                id: conversations[index].room, isMedia: true, message: fileLoadedEvent.target.result, message_index: message_index,
                shouldStartConversation: ((conversations[index].sent_messages === 1 && !isSelf) ? true : false)
            });
        }
    }
}

function handleReportUpload(form, reason, extra, material_type, material, is_anonim, anon_name) {
    //console.log('originalFile instanceof Blob', imageFile instanceof Blob); // true
    //console.log(`originalFile size ${imageFile.size / 1024 / 1024} MB`);

    closeForm(form);

    const snackbar = document.getElementById("snackbar");
    snackbar.className = "show";

    setTimeout(function () {
        snackbar.className = snackbar.className.replace("show", "");
    }, 3000);

    var options = {
        maxSizeMB: 0.5,
        maxWidthOrHeight: 8182,
        useWebWorker: true
    }

    const blob = b64toBlob(material);

    imageCompression(blob, options)
        .then(function (compressedFile) {
            //console.log('compressedFile instanceof Blob', compressedFile instanceof Blob); // true
            //console.log(`compressedFile size ${compressedFile.size / 1024 / 1024} MB`); // smaller than maxSizeMB
            return uploadToServer(compressedFile);
        })
        .catch(function (error) {
            console.log(error.message);
        });

    function b64toBlob(dataURI) {

        var byteString = atob(dataURI.split(',')[1]);
        var ab = new ArrayBuffer(byteString.length);
        var ia = new Uint8Array(ab);

        for (var i = 0; i < byteString.length; i++) {
            ia[i] = byteString.charCodeAt(i);
        }

        return new Blob([ab], { type: dataURI.substr(0, dataURI.indexOf(';')).substr(dataURI.indexOf(':') + 1) });
    }

    function uploadToServer(compressedFile) {
        var fileReader = new FileReader();
        fileReader.readAsDataURL(compressedFile);

        fileReader.onload = function (fileLoadedEvent) {
            var params = "operation=report&id=" + "" + "&reason=" + reason
                + "&message=" + encodeURIComponent(extra) + "&material_type=" + material_type + "&material=" + encodeURIComponent(fileLoadedEvent.target.result)
                + "&is_anonim=" + is_anonim + "&anon_name=" + anon_name + "&phpsessid=" + phpsessid;
            var xhr = new XMLHttpRequest();
            xhr.open('POST', 'https://swirlia.net/php/Profile.php', true);
            xhr.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');
            xhr.withCredentials = true;
            xhr.send(params);            
        }
    }
}

function addAnon(name, socket, room) {
    blank_screen.innerHTML = "";

    block.style.visibility = "visible";

    const hidden = document.createElement("label");
    hidden.innerText = room;
    hidden.style.display = "none";

    const label = document.createElement("label");
    label.innerText = name;
    label.classList.add("tabs_label");

    const img = document.createElement("img");

    new Promise((resolve, reject) => {
        img.src = "images/close.png";
    });

    img.classList.add("tabs_img");

    const li = document.createElement("li");
    li.classList.add("tabs_li");

    li.appendChild(hidden);
    li.appendChild(label);
    li.appendChild(img);
    ul.appendChild(li);

    img.onclick = function (e) {
        e.stopPropagation();
        try {
            var index = conversations.findIndex(user => user.room === room);

            var ul_index;
            for (let i = 0; i < ul.childNodes.length; i++) {
                if (ul.childNodes[i].childNodes[0].innerText === room) {
                    ul_index = i;
                    break;
                }
            }

            //Close Conversation From Both Sides
            socket.emit("closeConversation", conversations[index].room);

            conversations.splice(index, 1);
            ul.removeChild(ul.childNodes[ul_index]);

            if (room == currentRoom)
                chat.removeChild(chat.childNodes[0]);

            if (conversations.length === 0 || ul.childNodes.length === 0) {
                message.value = "";
                message.disabled = true;
                photo.disabled = true;
                currentRoom = null;
                notify.style.display = "none";
                block.style.visibility = "hidden";
                typing.style.display = "none";
                blank_screen.innerHTML = receiver_blank;
            } else {
                if (room == currentRoom) {
                    if (ul_index === ul.childNodes.length)
                        --ul_index;

                    index = conversations.findIndex(user => user.room === ul.childNodes[ul_index].childNodes[0].innerText);

                    chat.appendChild(conversations[index].chat_ul);
                    currentRoom = conversations[index].room;

                    if (!conversations[index].active) {
                        message.value = "";
                        message.disabled = true;
                        photo.disabled = true;
                    } else {
                        message.disabled = false;
                        photo.disabled = false;
                    }

                    if (conversations[index].typing && conversations[index].active) {
                        typing.style.display = "initial";
                        scrollUp(false, socket, false, false, true);
                    } else
                        typing.style.display = "none";

                    const li = ul.childNodes[ul_index];
                    li.style.backgroundColor = "#e2e2e2";

                    li.onmouseover = function() {
                        li.style.backgroundColor = "#d8d8d8";
                    }
                    li.onmouseout = function() {
                        li.style.backgroundColor = "#e2e2e2";
                    }
                }
            }

            control_new_message(socket);

            clearTimeout(kick_timeout);
            clearTimeout(warning_timeout);
        } catch (err) {
            console.log(err);
        }
    }

    li.onclick = function () {
        if (currentRoom !== room) {
            var ul_index;
            for (let i = 0; i < ul.childNodes.length; i++) {
                if (ul.childNodes[i].childNodes[0].innerText === currentRoom) {
                    ul_index = i;
                    break;
                }
            }

            var index = conversations.findIndex(user => user.room === currentRoom);

            if (typing_timeout)
                clearTimeout(typing_timeout);
            socket.emit("typing", { id: conversations[index].room, status: false });   

            var all_seen = true;
            if (conversations[index].chat_ul.firstChild.hasAttribute("data-seen")) {
                if (conversations[index].chat_ul.firstChild.getAttribute("data-seen") === "false")
                    all_seen = false;
            }

            chat.removeChild(chat.childNodes[0]);

            var old_li = ul.childNodes[ul_index];

            if (all_seen) {
                old_li.style.backgroundColor = "#f3f3f3";

                old_li.onmouseover = function () {
                    old_li.style.backgroundColor = "#e9e9e9";
                }
                old_li.onmouseout = function () {
                    old_li.style.backgroundColor = "#f3f3f3";
                }
            } else {
                old_li.style.backgroundColor = "#ffff9e";

                old_li.onmouseover = function () {
                    old_li.style.backgroundColor = "#ffff7a";
                }
                old_li.onmouseout = function () {
                    old_li.style.backgroundColor = "#ffff9e";
                }
            }

            currentRoom = room;

            index = conversations.findIndex(user => user.room === currentRoom);
            chat.appendChild(conversations[index].chat_ul);
            notify.style.display = "none";

            if (!conversations[index].active) {
                message.value = "";
                message.disabled = true;
                photo.disabled = true;
            } else {
                message.disabled = false;
                photo.disabled = false;
            }

            if (conversations[index].typing && conversations[index].active) {
                typing.style.display = "initial";
                scrollUp(false, socket, false, false, true);
            } else
                typing.style.display = "none";

            if (li.style.backgroundColor === "rgb(255,255,158)" || li.style.backgroundColor === "rgb(255, 255, 122)")
                scrollUp(true, socket, true, false, false);
            else
                scrollUp(true, socket, false, false, false);

            li.style.backgroundColor = "#e2e2e2";

            li.onmouseover = function () {
                li.style.backgroundColor = "#d8d8d8";
            }
            li.onmouseout = function () {
                li.style.backgroundColor = "#e2e2e2";
            }
        }
    }

    if (room !== currentRoom) {
        li.style.backgroundColor = "#ffff9e";

        li.onmouseover = function () {
            li.style.backgroundColor = "#ffff7a";
        }
        li.onmouseout = function () {
            li.style.backgroundColor = "#ffff9e";
        }
    }
}

function addMessage(message, room, index, ul_index, socket) {
    if (conversations[index].pendingMessage) {
        ul.childNodes[ul_index].childNodes[1].innerText = message.username;

        const h4 = document.createElement("h4");
        h4.innerText = "KULLANICI ANONİMLİKTEN ÇIKTI";
        h4.style.marginTop = "0.5em";
        checkSeen(index);
        conversations[index].chat_ul.insertBefore(h4, conversations[index].chat_ul.firstChild);

        conversations[index].pendingMessage = false;
    }

    const div = document.createElement("div");
    div.setAttribute("data-seen", false);
    div.classList.add("chat_li_left");

    const chat_div = document.createElement("div");
    chat_div.classList.add("chat_div_left");

    const username = document.createElement("label");
    username.innerText = message.username;
    username.classList.add("chat_username_left");

    if (isSelf && !conversations[index].isAnonim) {
        username.style.cursor = "pointer"

        username.onclick = function () {
            window.open("https://swirlia.com/" + message.username);
        }

        username.onmouseover = function () {
            username.style.textDecoration = "underline";
        }
        username.onmouseout = function () {
            username.style.textDecoration = "none";
        }
    }

    var msg;
    if (message.bundle.isMedia) {
        msg = document.createElement("img");
        msg.classList.add("chat_image_left");

        msg.onload = function () {
            if (isSelf) {
                if (room === currentRoom)
                    scrollUp(false, socket, false, false, false);
            } else
                scrollUp(false, socket, false, false, false);
        }

        if (conversations[index].allow_media) {
            msg.setAttribute("data-form-target", "#image_form");

            new Promise((resolve, reject) => {
                msg.src = message.text;
            });
            
            msg.onclick = function () {
                const form = document.querySelector(msg.dataset.formTarget);
                openForm(form, msg, null, null);
            }
        } else {
            new Promise((resolve, reject) => {
                msg.src = "https://swirlia.net/images/show_image.png";
            });

            msg.onclick = function () {
                msg.setAttribute("data-form-target", "#image_form");

                new Promise((resolve, reject) => {
                    msg.src = message.text;
                });

                msg.onclick = function () {
                    const form = document.querySelector(msg.dataset.formTarget);
                    openForm(form, msg, null, null);
                }

                if (!conversations[index].allow_media) {
                    if (confirm("Bu kullanıcıdan gelecek olan yeni fotoğraflar da doğrudan gösterilsin mi?"))
                        conversations[index].allow_media = true;
                }
            }
        }
    } else {
        msg = document.createElement("label");
        msg.innerText = message.text;
        msg.classList.add("chat_message_left");
    }

    const time_split = message.time.split(":");
    const time = document.createElement("label");
    time.innerHTML = time_split[0] + ":" + time_split[1] + "<span style='font-size:xx-small; color:#888'>  " + time_split[2] + "</span>";
    time.classList.add("chat_time_left");

    const report = document.createElement("img");

    new Promise((resolve, reject) => {
        report.src = "images/report.png";
    });

    report.classList.add("chat_report");
    report.draggable = false;

    report.onclick = function () {
        openForm(document.getElementById("report_form"), "report", message.bundle.isMedia, message.text);
    }

    div.onmouseover = function () {
        if (message.bundle.isMedia) {
            if (msg.src !== "https://swirlia.net/images/show_image.png")
                report.style.display = "initial";
        } else
            report.style.display = "initial";
    }
    div.onmouseout = function () {
        report.style.display = "none";
    }

    chat_div.appendChild(username);
    chat_div.appendChild(msg);
    chat_div.appendChild(time);
    div.appendChild(chat_div);
    div.appendChild(report);
    checkSeen(index);
    conversations[index].chat_ul.insertBefore(div, conversations[index].chat_ul.firstChild);

    if (isSelf) {
        if (room === currentRoom)
            scrollUp(false, socket, false, true, false);
        else if (conversations[index].received_messages > 1) {
            var li = ul.childNodes[ul_index];
            li.style.backgroundColor = "#ffff9e";

            li.onmouseover = function () {
                li.style.backgroundColor = "#ffff7a";
            }

            li.onmouseout = function () {
                li.style.backgroundColor = "#ffff9e";
            }
        }
    } else
        scrollUp(false, socket, false, true, false);

    if (conversations[index].received_messages > 1 && sfx_allowed)
        message_sound.play();

    control_new_message(socket);
}

function sendMessage(isMedia, message, socket) {
    blank_screen.innerHTML = "";

    const div = document.createElement("div");
    div.classList.add("chat_li_right");

    const chat_div = document.createElement("div");
    chat_div.classList.add("chat_div_right");

    const username = document.createElement("label");
    if (isSelf)
        username.innerText = requested_username;
    else
        username.innerText = anon_id;
    username.classList.add("chat_username_right");

    var msg;
    if (isMedia) {
        msg = document.createElement("img");
        msg.classList.add("chat_image_right");
        msg.setAttribute("data-form-target", "#image_form");

        msg.onclick = function () {
            const form = document.querySelector(msg.dataset.formTarget);
            openForm(form, msg, null, null);
        }

        msg.onload = function () {
            scrollUp(true, socket, false, false, false);
        }

        var fr = new FileReader();

        fr.onload = function () {
            new Promise((resolve, reject) => {
                msg.src = fr.result;
            });
        }

        fr.readAsDataURL(message);
    } else {
        msg = document.createElement("label");
        msg.innerText = message;
        msg.classList.add("chat_message_right");
    }

    const time = document.createElement("label");
    time.classList.add("chat_time_right");

    const sending = document.createElement("img");

    new Promise((resolve, reject) => {
        sending.src = "images/sending.png";
    });

    sending.classList.add("chat_sending");
    sending.draggable = false;

    chat_div.appendChild(username);
    chat_div.appendChild(msg);
    chat_div.appendChild(time);
    div.appendChild(chat_div);
    div.appendChild(sending);

    //Index
    if (isSelf)
        var index = conversations.findIndex(user => user.room === currentRoom);
    else {
        var index = 0;

        chat.appendChild(conversations[index].chat_ul);
    }

    //Add Message
    checkSeen(index);
    conversations[index].chat_ul.insertBefore(div, conversations[index].chat_ul.firstChild);
    scrollUp(true, socket, false, false, false);

    //Server
    const message_index = Array.prototype.indexOf.call(conversations[index].chat_ul.children, div);

    conversations[index].sent_messages++;

    if (isMedia)
        handleImageUpload(message, index, message_index, socket);
    else
        socket.emit("message", {
            id: conversations[index].room, isMedia: false, message: message, message_index: message_index,
            shouldStartConversation: ((conversations[index].sent_messages === 1 && !isSelf) ? true : false)
        });

    control_new_message(socket);
}

function anonimityClicked(socket, username) {
    if (isAnonim && conversations[0].active) {
        if (confirm("Anonimlikten çıkmak istediğinize emin misiniz? Artık kullanıcı adınız karşıdaki kullanıcı tarafından görülebilir olacak.")) {
            anonimityy.style.color = "green";
            anonimityy.innerText = "Anonimlikten çıktınız";
            anonimityy.style.pointerEvents = "none";
            isAnonim = false;

            anon_id = username;

            socket.emit("swapAnonimity", { id: conversations[0].room, username: username });

            clearTimeout(kick_timeout);
            clearTimeout(warning_timeout);
        }
    }
}

//AUX
function scrollUp(absolute, socket, forced_seen, should_seen, typing) {
    var index;

    if (isSelf)
        index = conversations.findIndex(user => user.room === currentRoom);
    else
        index = 0;
    console.log(document.getElementsByClassName("chat")[0].scrollTop);
    if (index !== -1) {
        if ((document.getElementsByClassName("chat")[0].scrollTop <= 70) || absolute) {
            chat.scrollTop = 0;

            notify.style.display = "none";

            //Seen
            if (((forced_seen && absolute) || should_seen) && pageVisibilityAPI) {
                if (document.visibilityState !== hidden) {
                    conversations[index].chat_ul.firstChild.setAttribute("data-seen", true);

                    socket.emit("seen", conversations[index].room);

                    control_new_message(socket);
                }
            }
        } else if (!typing)
            notify.style.display = "initial";
    }
}

function checkSeen(index) {
    if (conversations[index].chat_ul.firstChild != null) {
        if (conversations[index].chat_ul.firstChild.hasAttribute("data-seen-notify")) {
            conversations[index].chat_ul.removeChild(conversations[index].chat_ul.firstChild);

            for (let i = 0; i < conversations[index].chat_ul.length; i++) {
                if (conversations[index].chat_ul.childNodes[i].hasAttribute("data-seen-notify"))
                    conversations[index].chat_ul.removeChild(conversations[index].chat_ul.childNodes[i]);
            }
        }
    }
}

function control_new_message(socket) {
    controlNewMessage = false;

    for (let i = 0; i < conversations.length; i++) {
        if (conversations[i].chat_ul.childNodes.length > 0) {
            for (let j = 0; j < conversations[i].chat_ul.childNodes.length; j++) {
                if (conversations[i].chat_ul.firstChild.hasAttribute("data-seen")) {
                    if (conversations[i].chat_ul.firstChild.getAttribute("data-seen") === "false")
                        controlNewMessage = true;
                }
            }
        }
    }

    if (controlNewMessage) {
        notification_interval = setInterval(head_title_notifier, 2000);
        kick_timeout = setTimeout(kick_countdown, 1200000, socket);
        warning_timeout = setTimeout(warning_sound, 900000);
    } else {
        clearInterval(notification_interval);
        clearTimeout(kick_timeout);
        clearTimeout(warning_timeout);

        document.title = requested_username;
        window.parent.postMessage(document.title, '*');
    }

    function head_title_notifier() {
        if (controlNewMessage) {
            document.title = (document.title === requested_username ? "Yeni mesaj!" : requested_username);
            window.parent.postMessage(document.title, '*');
        } else {
            document.title = requested_username;
            window.parent.postMessage(document.title, '*');
            clearTimeout(kick_timeout);
            clearTimeout(warning_timeout);
        }
    }
}

function kick_countdown(socket) {
    /*
    if (controlNewMessage) {
        document.body.style = "background-color: #eee; display:flex; flex-direction:column; align-items:center; justify-content:center; width:100%; height:100vh; padding:0px; margin:1em;";
        document.body.innerHTML = "<img loading='lazy' alt='' src='/images/not_available.png' style='object-fit:contain; width:100px; height:100px;' /><p style='color:indianred; font-size:x-large; font-weight:bold; text-align:center; font-family:Lucida Console;'>UZUN SÜRE İNAKTİF KALDIĞINIZ İÇİN SOHBET OTURUMU SONLANDIRILDI</p>";
        isError = true;
        socket.disconnect();

        if (sfx_allowed)
            kick_alarming_sound.play();

        clearInterval(notification_interval);
        var disconnected = setInterval(disconnected_interval, 2000);
    } else {
        clearInterval(notification_interval);
        clearTimeout(kick_timeout);
        clearTimeout(warning_timeout);
    }

    function disconnected_interval() {
        document.title = (document.title === requested_username ? "Bağlantı kesildi!" : requested_username);
        window.parent.postMessage(document.title, '*');
    }*/
}

function warning_sound() {
    if (controlNewMessage) {
        if (sfx_allowed)
            kick_alarming_sound.play();
    } else {
        clearInterval(notification_interval);
        clearTimeout(kick_timeout);
        clearTimeout(warning_timeout);
    }
}

function typing_countdown(params) {
    params.socket.emit("typing", { id: conversations[params.index].room, status: false });
}

///DISABLE IMAGE DRAGGING
function disableDragging(e) {
    e.preventDefault();
}