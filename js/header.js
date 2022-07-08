var phpsessid = null;

window.onload = function (e) {
    var event = e || window.event, imgs, i;
    if (event.preventDefault) {
        imgs = document.getElementsByTagName('img');

        for (i = 0; i < imgs.length; i++) {
            imgs[i].onmousedown = disableDragging;
        }
    }

    try {
        if (window.top.location.host === "swirlia.com" || host === "wwww.swirlia.com")
            checkHost(window.top.location.host);
    } catch (err) {
        window.addEventListener('message', receiveMessage, false);

        function receiveMessage(event) {
            checkHost(event.data);
        }
    }
};

function checkHost(host) {
    try {
        if (host === "swirlia.com" || host === "www.swirlia.com" || host === "swirlia.net" || host === "www.swirlia.net") {
            //PARENT
            if (host === "swirlia.net" || host === "www.swirlia.net") {
                document.getElementById("header_h3").style.display = "none";
                document.getElementById("header_not_signed_in").style.display = "none";
                document.getElementById("header_signed_in").style.display = "none";
                document.getElementById("logo_div").style = "display:flex; height:60vh; width:100%; align-items:center; align-content:center; justify-content:center;";
                document.getElementById("logo").style.objectPosition = "center";

                document.body.style.display = "flex";
            } else if (parent.document.getElementById("form_button") === null && parent.document.getElementById("block_form") === null) {
                document.getElementById("header_h3").style.display = "none";
                document.getElementById("header_not_signed_in").style.display = "none";
                document.getElementById("header_signed_in").style.display = "none";
                document.getElementById("logo_div").style = "display:flex; height:60vh; width:100%; align-items:center; align-content:center; justify-content:center;";
                document.getElementById("logo").style.objectPosition = "center";

                document.body.style.display = "flex";
            } else {
                var xhr = new XMLHttpRequest();
                xhr.open('POST', 'html/session.php', true);
                xhr.send();

                xhr.onload = function () {
                    phpsessid = this.response;

                    afterSessionSet();
                }

                function afterSessionSet() {
                    document.body.style.display = "flex";

                    //Sound
                    soundManager.createSound("sparkle_sound", "https://swirlia.com/sounds/magic_shime.mp3");

                    /////////////////////////////LOGIN///////////////////////////
                    const forgot = document.getElementById("forgot");
                    const login_button = document.getElementById("login_button");
                    const error_label = document.getElementById("error_label");

                    const login_username_input = document.getElementById("login_username");
                    const login_password_input = document.getElementById("login_password");

                    var wait = false;
                    var forgotOperation = false;
                    var loginOperation = false;
                    var formFocused = false;

                    if (forgot) {
                        forgot.onclick = function (e) {
                            if (!loginOperation && !wait) {
                                wait = true;

                                const x = e.clientX;
                                const y = e.clientY;

                                soundManager.play("sparkle_sound");

                                const glitter_confetti = document.createElement("img");

                                new Promise((resolve, reject) => {
                                    glitter_confetti.src = "https://swirlia.com/images/sparkle.gif";
                                });

                                glitter_confetti.classList.add("glitter_confetti");
                                glitter_confetti.style.left = (x - 30) + "px";
                                glitter_confetti.style.top = (y - 20) + "px";
                                document.getElementById("header_not_signed_in").appendChild(glitter_confetti);

                                var z = setTimeout(function () {
                                    document.getElementById("header_not_signed_in").removeChild(document.getElementById("header_not_signed_in").lastChild)

                                    if (forgotOperation) {
                                        forgotOperation = false;
                                        forgot.innerText = "Şifrenizi mi unuttunuz?";
                                        login_button.innerText = "Giriş Yap";
                                        login_password_input.style.display = "initial";
                                        login_password_input.required = true;
                                    } else {
                                        forgotOperation = true;
                                        forgot.innerText = "Giriş Formuna Dön";
                                        login_button.innerText = "Gönder";
                                        login_password_input.style.display = "none";
                                        login_password_input.required = false;
                                    }

                                    error_label.innerText = "";
                                    error_label.style.display = "none";
                                    login_password_input.style.borderColor = "none;";
                                    login_username_input.style.borderColor = "none;";

                                    wait = false;
                                }, 750);
                            }
                        }

                        login_button.onclick = function () {
                            login();
                        }

                        document.addEventListener('keydown', (e) => {
                            var evtobj = window.event ? event : e;

                            if ((e.code === "Enter" || e.code === "NumpadEnter" || evtobj.keyCode == 13) && formFocused) {
                                e.preventDefault();

                                login();
                            }
                        });

                        function login() {
                            if (!loginOperation) {
                                error_label.innerText = "";
                                error_label.style.display = "none";
                                login_password_input.style.borderColor = "none;";
                                login_username_input.style.borderColor = "none;";

                                if (forgotOperation) {
                                    const login_username = document.getElementById("login_username").value;

                                    //Username Check
                                    let username_check = true;

                                    if (!login_username.includes("@")) {
                                        var allowed_characters = ["A", "B", "C", "D", "E", "F", "G", "H", "I", "J", "K", "L", "M", "N", "O", "P", "Q", "R",
                                            "S", "T", "U", "V", "W", "X", "Y", "Z", "a", "b", "c", "d", "e", "f", "g", "h", "i", "j",
                                            "k", "l", "m", "n", "o", "p", "q", "r", "s", "t", "u", "v", "w", "x", "y", "z", "_", ".",
                                            "-", "0", "1", "2", "3", "4", "5", "6", "7", "8", "9"];
                                        var username_characters = login_username.split("");

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
                                    } else if (login_username.length < 6)
                                        username_check = false;

                                    if (((login_username.length < 4 || login_username.length > 16) && !login_username.includes("@")) || (login_username.includes(".html") && !login_username.includes("@")) || !username_check) {
                                        error_label.innerText = "*Giriş bilgisi hatalı";
                                        error_label.style.display = "flex";
                                        login_password_input.style.borderColor = "#C60000";
                                        login_username_input.style.borderColor = "#C60000";
                                    } else {
                                        loginOperation = true;

                                        document.getElementById('logo').style.pointerEvents = 'none';
                                        document.getElementById("login_username").disabled = true;
                                        document.getElementById("login_username").readOnly = true;
                                        document.getElementById("login_password").disabled = true;
                                        document.getElementById("login_password").readOnly = true;
                                        document.getElementById("forgot").disabled = true;

                                        if (parent.document.getElementById("form_button") !== null) {
                                            parent.document.getElementById("username_signup").disabled = true;
                                            parent.document.getElementById("username_signup").readOnly = true;
                                            parent.document.getElementById("password_signup").disabled = true;
                                            parent.document.getElementById("password_signup").readOnly = true;
                                            parent.document.getElementById("password_again_signup").disabled = true;
                                            parent.document.getElementById("password_again_signup").readOnly = true;
                                            parent.document.getElementById("email_signup").disabled = true;
                                            parent.document.getElementById("email_signup").readOnly = true;
                                            parent.document.getElementById("male_gender").disabled = true;
                                            parent.document.getElementById("female_gender").disabled = true;
                                            parent.document.getElementById("other_gender").disabled = true;
                                            parent.document.getElementById("birthdate").disabled = true;
                                            parent.document.getElementById("birthdate").readOnly = true;
                                        }

                                        var params = "operation=forgotPassword&variable=" + encodeURIComponent(login_username) + "&phpsessid=" + phpsessid;
                                        var xhr = new XMLHttpRequest();
                                        xhr.open('POST', 'https://swirlia.net/php/Index.php', true);
                                        xhr.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');
                                        xhr.withCredentials = true;
                                        xhr.send(params);

                                        xhr.onload = function () {
                                            loginOperation = false;

                                            document.getElementById('logo').style.pointerEvents = 'auto';
                                            document.getElementById("login_username").disabled = false;
                                            document.getElementById("login_username").readOnly = false;
                                            document.getElementById("login_password").disabled = false;
                                            document.getElementById("login_password").readOnly = false;
                                            document.getElementById("forgot").disabled = false;

                                            if (parent.document.getElementById("form_button") !== null) {
                                                parent.document.getElementById("username_signup").disabled = false;
                                                parent.document.getElementById("username_signup").readOnly = false;
                                                parent.document.getElementById("password_signup").disabled = false;
                                                parent.document.getElementById("password_signup").readOnly = false;
                                                parent.document.getElementById("password_again_signup").disabled = false;
                                                parent.document.getElementById("password_again_signup").readOnly = false;
                                                parent.document.getElementById("email_signup").disabled = false;
                                                parent.document.getElementById("email_signup").readOnly = false;
                                                parent.document.getElementById("male_gender").disabled = false;
                                                parent.document.getElementById("female_gender").disabled = false;
                                                parent.document.getElementById("other_gender").disabled = false;
                                                parent.document.getElementById("birthdate").disabled = false;
                                                parent.document.getElementById("birthdate").readOnly = false;
                                            }

                                            if (this.response.length > 0) {
                                                var server_response = JSON.parse(this.response);

                                                if (server_response.message === "User already logged in")
                                                    parent.window.location.href = server_response.username;
                                                else
                                                    alert("Eğer girdiğiniz bilgiye uygun bir kullanıcı bulunursa şifre sıfırlama maili daha önce onaylanmış e-postaya gönderilecek."
                                                        + "\n\nEğer sistemdeki kayıtlı e-posta onaylanmamış ise maalesef bir şifre sıfırlama maili gelmeyecek."
                                                        + "\n\nMailin gelmesi 10 dakikayı bulabilir. Lütfen spam klasörünü de kontrol etmeyi unutmayınız."
                                                        + "\n\nLütfen her bir şifre sıfırlama talebi için bir saat beklemeniz gerektiğini unutmayınız.");
                                            } else
                                                alert("Eğer girdiğiniz bilgiye uygun bir kullanıcı bulunursa şifre sıfırlama maili daha önce onaylanmış e-postaya gönderilecek."
                                                    + "\n\nEğer sistemdeki kayıtlı e-posta onaylanmamış ise maalesef bir şifre sıfırlama maili gelmeyecek."
                                                    + "\n\nMailin gelmesi 10 dakikayı bulabilir. Lütfen spam klasörünü de kontrol etmeyi unutmayınız."
                                                    + "\n\nLütfen her bir şifre sıfırlama talebi için bir saat beklemeniz gerektiğini unutmayınız.");
                                        }
                                    }
                                } else {
                                    const login_username = document.getElementById("login_username").value;
                                    const login_password = document.getElementById("login_password").value;

                                    //Username Check
                                    let username_check = true;

                                    if (!login_username.includes("@")) {
                                        var allowed_characters = ["A", "B", "C", "D", "E", "F", "G", "H", "I", "J", "K", "L", "M", "N", "O", "P", "Q", "R",
                                            "S", "T", "U", "V", "W", "X", "Y", "Z", "a", "b", "c", "d", "e", "f", "g", "h", "i", "j",
                                            "k", "l", "m", "n", "o", "p", "q", "r", "s", "t", "u", "v", "w", "x", "y", "z", "_", ".",
                                            "-", "0", "1", "2", "3", "4", "5", "6", "7", "8", "9"];
                                        var username_characters = login_username.split("");

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
                                    } else if (login_username.length < 6)
                                        username_check = false;

                                    //Password Check
                                    var upper_characters = ["A", "B", "C", "Ç", "D", "E", "F", "G", "Ğ", "H", "I", "İ", "J", "K", "L", "M",
                                        "N", "O", "Ö", "P", "Q", "R", "S", "Ş", "T", "U", "Ü", "V", "W", "X", "Y", "Z"];
                                    var number_characters = ["0", "1", "2", "3", "4", "5", "6", "7", "8", "9"];
                                    var password_characters = login_password.split("");

                                    let upperCheck = false;
                                    for (let i = 0; i < password_characters.length; i++) {
                                        for (let j = 0; j < upper_characters.length; j++) {
                                            if (password_characters[i] === upper_characters[j])
                                                upperCheck = true;
                                        }
                                    }

                                    let numberCheck = false;
                                    for (let i = 0; i < password_characters.length; i++) {
                                        for (let j = 0; j < number_characters.length; j++) {
                                            if (password_characters[i] === number_characters[j])
                                                numberCheck = true;
                                        }
                                    }

                                    if (((login_username.length < 4 || login_username.length > 16) && !login_username.includes("@")) || (login_username.includes(".html") && !login_username.includes("@")) || !username_check ||
                                        login_password.length < 8 || login_password.length > 16 || !upperCheck || !numberCheck) {
                                        error_label.innerText = "*Giriş bilgisi hatalı";
                                        error_label.style.display = "flex";
                                        login_password_input.style.borderColor = "#C60000";
                                        login_username_input.style.borderColor = "#C60000";
                                    } else {
                                        loginOperation = true;

                                        document.getElementById('logo').style.pointerEvents = 'none';
                                        document.getElementById("login_username").disabled = true;
                                        document.getElementById("login_username").readOnly = true;
                                        document.getElementById("login_password").disabled = true;
                                        document.getElementById("login_password").readOnly = true;
                                        document.getElementById("forgot").disabled = true;

                                        if (parent.document.getElementById("form_button") !== null) {
                                            parent.document.getElementById("username_signup").disabled = true;
                                            parent.document.getElementById("username_signup").readOnly = true;
                                            parent.document.getElementById("password_signup").disabled = true;
                                            parent.document.getElementById("password_signup").readOnly = true;
                                            parent.document.getElementById("password_again_signup").disabled = true;
                                            parent.document.getElementById("password_again_signup").readOnly = true;
                                            parent.document.getElementById("email_signup").disabled = true;
                                            parent.document.getElementById("email_signup").readOnly = true;
                                            parent.document.getElementById("male_gender").disabled = true;
                                            parent.document.getElementById("female_gender").disabled = true;
                                            parent.document.getElementById("other_gender").disabled = true;
                                            parent.document.getElementById("birthdate").disabled = true;
                                            parent.document.getElementById("birthdate").readOnly = true;
                                        }

                                        var params = "operation=login&username=" + encodeURIComponent(login_username) + "&password=" + encodeURIComponent(login_password) + "&phpsessid=" + phpsessid;
                                        var xhr = new XMLHttpRequest();
                                        xhr.open('POST', 'https://swirlia.net/php/Index.php', true);
                                        xhr.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');
                                        xhr.withCredentials = true;
                                        xhr.send(params);

                                        xhr.onload = function () {
                                            loginOperation = false;

                                            document.getElementById('logo').style.pointerEvents = 'auto';
                                            document.getElementById("login_username").disabled = false;
                                            document.getElementById("login_username").readOnly = false;
                                            document.getElementById("login_password").disabled = false;
                                            document.getElementById("login_password").readOnly = false;
                                            document.getElementById("forgot").disabled = false;

                                            if (parent.document.getElementById("form_button") !== null) {
                                                parent.document.getElementById("username_signup").disabled = false;
                                                parent.document.getElementById("username_signup").readOnly = false;
                                                parent.document.getElementById("password_signup").disabled = false;
                                                parent.document.getElementById("password_signup").readOnly = false;
                                                parent.document.getElementById("password_again_signup").disabled = false;
                                                parent.document.getElementById("password_again_signup").readOnly = false;
                                                parent.document.getElementById("email_signup").disabled = false;
                                                parent.document.getElementById("email_signup").readOnly = false;
                                                parent.document.getElementById("male_gender").disabled = false;
                                                parent.document.getElementById("female_gender").disabled = false;
                                                parent.document.getElementById("other_gender").disabled = false;
                                                parent.document.getElementById("birthdate").disabled = false;
                                                parent.document.getElementById("birthdate").readOnly = false;
                                            }

                                            var server_response = JSON.parse(this.response);

                                            if (server_response.result === "success")
                                                parent.window.location.href = login_username;
                                            else if (server_response.message === "User already logged in")
                                                parent.window.location.href = server_response.username;
                                            else {
                                                error_label.innerText = "*Giriş bilgisi hatalı";
                                                error_label.style.display = "flex";
                                                login_password_input.style.borderColor = "#C60000";
                                                login_username_input.style.borderColor = "#C60000";
                                            }
                                        }
                                    }
                                }
                            }
                        }

                        /////////// CHANGING THE BORDER COLORS////////////
                        login_username_input.addEventListener("focus", function () {
                            formFocused = true;
                        });
                        login_username_input.addEventListener("blur", function () {
                            formFocused = false;
                        });

                        login_password_input.addEventListener("focus", function () {
                            formFocused = true;
                        });
                        login_password_input.addEventListener("blur", function () {
                            formFocused = false;
                        });
                    }
                    /////////////////////////////LOGIN///////////////////////////
                }
            }
        }
    } catch (err) {
        document.body.innerHTML = "";
        setTimeout(() => {
            alert("Access Denied");
        }, 1000);
    }
}

function disableDragging(e) {
    e.preventDefault();
}