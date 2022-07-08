//Decleration
const button = document.getElementById("form_button");
const usernameError = document.getElementById("username_error");
const passwordError = document.getElementById("password_error");
const emailError = document.getElementById("email_error");
const dateError = document.getElementById("date_error");
var registerOperation = false;
var formFocused = false;

var phpsessid = null;

//Error Message
let uError = false;
let pError = false;
let eError = false;
let dError = false;

/////////////////////////FORM/////////////////////////
const overlayy = document.getElementById("overlay");

function openForm(form, id) {
    if (form == null)
        return;

    form = document.getElementById(form);
    form.classList.add('active');
    overlayy.classList.add('active');

    if (id === "user_agreement") {
        const user_agreement = document.getElementById("user_agreement");
        user_agreement.style.display = "initial";
    }

    //Close form
    overlayy.onclick = function () {
        if (form.classList.contains("active")) {
            const user_agreement = document.getElementById("user_agreement");
            user_agreement.style.display = "none";

            closeForm(form);
        }
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

try {
    if (window.top.location.host === "swirlia.com" || window.top.location.host === "wwww.swirlia.com") {
        //Update URL
        history.pushState("index", "index", "https://swirlia.com");

        var xhr = new XMLHttpRequest();
        xhr.open('POST', 'html/session.php', true);
        xhr.send();

        xhr.onload = function () {
            phpsessid = this.response;

            afterSessionSet();
        }

        function afterSessionSet() {
            //H3 LABEL
            const iframe = document.getElementById("iframe_header");
            iframe.onload = function () {
                var headerDocument = iframe.contentDocument || iframe.contentWindow.document;
                headerDocument.getElementById("header_h3").innerText = "Giriş yap veya anonim olarak gezin!";
            }

            /////////////SWIRL/////////////////////
            const swirl = document.getElementById("iframe_swirl");
            swirl.onload = function () {
                const swirl = document.getElementById("iframe_swirl");
                const swirlDocument = swirl.contentDocument || swirl.contentWindow.document;

                const tooltip = swirlDocument.querySelectorAll(".tooltip");
                const tooltiptext = swirlDocument.querySelectorAll(".tooltiptext");

                for (let i = 0; i < tooltip.length; i++) {
                    tooltiptext[i].classList.remove("tooltiptext");

                    if (i < 8) {
                        if (i === 0)
                            tooltiptext[i].classList.add("tooltiptext_left");
                        else if (i === 7)
                            tooltiptext[i].classList.add("tooltiptext_right");
                        else
                            tooltiptext[i].classList.add("tooltiptext");
                    } else if (i < 16) {
                        if (i === 8)
                            tooltiptext[i].classList.add("tooltiptext_left");
                        else if (i === 15)
                            tooltiptext[i].classList.add("tooltiptext_right");
                        else
                            tooltiptext[i].classList.add("tooltiptext");
                    } else if (i < 24) {
                        if (i === 16)
                            tooltiptext[i].classList.add("tooltiptext_left");
                        else if (i === 23)
                            tooltiptext[i].classList.add("tooltiptext_right");
                        else
                            tooltiptext[i].classList.add("tooltiptext");
                    } else {
                        if (i === 24)
                            tooltiptext[i].classList.add("tooltiptext_nd_left");
                        else if (i === 31)
                            tooltiptext[i].classList.add("tooltiptext_nd_right");
                        else
                            tooltiptext[i].classList.add("tooltiptext_nd");

                        tooltip[i].style.bottom = "175%";
                    }
                }
            }

            //IS LOGGED IN?
            var params = "operation=isLoggedIn&phpsessid=" + phpsessid;
            var xhr = new XMLHttpRequest();
            xhr.open('POST', 'https://swirlia.net/php/Profile.php', true);
            xhr.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');
            xhr.withCredentials = true;
            xhr.send(params);

            xhr.onload = function () {
                var server_response = JSON.parse(this.response);

                if (server_response.result === "success")
                    location.href = server_response.username;
                else {
                    //Display Page
                    document.getElementsByClassName("loader")[0].style.display = "none";
                    document.getElementById("container").style.display = "flex";
                }
            }

            //SIGNUP
            document.getElementById("username_signup").onfocus = function () {
                formFocused = true;
            };
            document.getElementById("password_signup").onfocus = function () {
                formFocused = true;
            };
            document.getElementById("password_again_signup").onfocus = function () {
                formFocused = true;
            };
            document.getElementById("email_signup").onfocus = function () {
                formFocused = true;
            };
            document.getElementById("birthdate").onfocus = function () {
                formFocused = true;
            };
            document.getElementById("username_signup").onblur = function () {
                formFocused = false;
            };
            document.getElementById("password_signup").onblur = function () {
                formFocused = false;
            };
            document.getElementById("password_again_signup").onblur = function () {
                formFocused = false;
            };
            document.getElementById("email_signup").onblur = function () {
                formFocused = false;
            };
            document.getElementById("birthdate").onfocus = function () {
                formFocused = false;
            };

            document.addEventListener('keydown', (e) => {
                var evtobj = window.event ? event : e;

                if ((e.code === "Enter" || e.code === "NumpadEnter" || evtobj.keyCode == 13) && formFocused) {
                    e.preventDefault();
                    signup();
                }
            });

            //Event Listener
            button.onclick = function () {
                signup();
            }

            function signup() {
                if (!registerOperation) {
                    //Decleration
                    const usernameText = document.getElementById("username_signup").value;
                    const passwordText = document.getElementById("password_signup").value;
                    const passwordAgainText = document.getElementById("password_again_signup").value;
                    const emailText = document.getElementById("email_signup").value;
                    const birthdate = document.getElementById("birthdate").value;

                    //Gender
                    let genderText = "1";
                    if (document.getElementById("female_gender").checked)
                        genderText = "2";
                    else if (document.getElementById("other_gender").checked)
                        genderText = "3";

                    //Error handling
                    var allowed_characters = ["A", "B", "C", "D", "E", "F", "G", "H", "I", "J", "K", "L", "M", "N", "O", "P", "Q", "R",
                        "S", "T", "U", "V", "W", "X", "Y", "Z", "a", "b", "c", "d", "e", "f", "g", "h", "i", "j",
                        "k", "l", "m", "n", "o", "p", "q", "r", "s", "t", "u", "v", "w", "x", "y", "z", "_", ".",
                        "-", "0", "1", "2", "3", "4", "5", "6", "7", "8", "9"];
                    var username_characters = usernameText.split("");

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

                    var upper_characters = ["A", "B", "C", "Ç", "D", "E", "F", "G", "Ğ", "H", "I", "İ", "J", "K", "L", "M",
                        "N", "O", "Ö", "P", "Q", "R", "S", "Ş", "T", "U", "Ü", "V", "W", "X", "Y", "Z"];
                    var number_characters = ["0", "1", "2", "3", "4", "5", "6", "7", "8", "9"];
                    var password_characters = passwordText.split("");

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

                    if (usernameText.length < 4 || usernameText.length > 16) {
                        usernameError.innerText = "**Kullanıcı adı 4 ile 16 karakter arasında olmalıdır.";
                        uError = true;
                    } else if (!username_check) {
                        usernameError.innerText = "**Kullanıcı adı İngilizce alfabeden, rakamlardan ve _ - . karakterlerinden oluşabilir.";
                        uError = true;
                    } else if (usernameText.includes(".html")) {
                        usernameError.innerText = "**Kullanıcı adı .html içermemelidir.";
                        uError = true;
                    } else {
                        usernameError.innerText = "";
                        uError = false;
                    }

                    if (passwordText !== passwordAgainText) {
                        passwordError.innerText = "**Şifreler uyuşmuyor.";
                        pError = true;
                    } else if (passwordText.length < 8 || passwordText.length > 16) {
                        passwordError.innerText = "**Şifre minimum 8, maksimum 16 karakterden oluşabilir.";
                        pError = true;
                    } else if (!upperCheck || !numberCheck) {
                        passwordError.innerText = "**Şifre en azından bir büyük harf ve sayı içermelidir.";
                        pError = true;
                    } else {
                        passwordError.innerText = "";
                        pError = false;
                    }

                    //Layout Resizing
                    const aside_body = document.getElementById("aside_id");

                    if (uError || pError)
                        aside_body.style.height = "540px";
                    else {
                        registerOperation = true;

                        var headerDocument = iframe.contentDocument || iframe.contentWindow.document;
                        headerDocument.getElementById('logo').style.pointerEvents = 'none';

                        headerDocument.getElementById('login_button').style.pointerEvents = 'none';
                        headerDocument.getElementById("login_username").disabled = true;
                        headerDocument.getElementById("login_username").readOnly = true;
                        headerDocument.getElementById("login_password").disabled = true;
                        headerDocument.getElementById("login_password").readOnly = true;
                        headerDocument.getElementById("forgot").disabled = true;

                        document.getElementById("username_signup").disabled = true;
                        document.getElementById("username_signup").readOnly = true;
                        document.getElementById("password_signup").disabled = true;
                        document.getElementById("password_signup").readOnly = true;
                        document.getElementById("password_again_signup").disabled = true;
                        document.getElementById("password_again_signup").readOnly = true;
                        document.getElementById("email_signup").disabled = true;
                        document.getElementById("email_signup").readOnly = true;
                        document.getElementById("male_gender").disabled = true;
                        document.getElementById("female_gender").disabled = true;
                        document.getElementById("other_gender").disabled = true;
                        document.getElementById("birthdate").disabled = true;
                        document.getElementById("birthdate").readOnly = true;

                        emailError.innerText = "";
                        usernameError.innerText = "";
                        passwordError.innerText = "";
                        dateError.innerText = "";
                        aside_body.style.height = "520px";

                        //AJAX
                        //Signup
                        var params = "operation=register&username=" + usernameText + "&password=" + encodeURIComponent(passwordText) + "&email="
                            + encodeURIComponent(emailText) + "&gender=" + genderText + "&birthdate=" + birthdate + "& phpsessid=" + phpsessid;
                        var xhr = new XMLHttpRequest();
                        xhr.open('POST', 'https://swirlia.net/php/Index.php', true);
                        xhr.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');
                        xhr.withCredentials = true;
                        xhr.send(params);

                        //Response
                        xhr.onload = function () {
                            var server_response = JSON.parse(this.response);

                            if (server_response.result === "failure") {
                                if (server_response.message === "This email is already in use") {
                                    emailError.innerText = "**Bu email daha önce kullanılmış";
                                    eError = true;
                                } else if (server_response.message === "This username has taken") {
                                    usernameError.innerText = "**Bu kullanıcı adı daha önce alınmış";
                                    uError = true;
                                } else if (server_response.message === "User's age must be between 14-100" ||
                                    server_response.type === "Invalid date time") {
                                    dateError.innerText = "**Kullanıcı yaşı 14-100 arası olmalıdır";
                                    dError = true;
                                } else if (server_response.message === "User already logged in")
                                    location.href = server_response.username;

                                aside_body.style.height = "540px";

                                registerOperation = false;

                                headerDocument.getElementById('logo').style.pointerEvents = 'auto';

                                headerDocument.getElementById('login_button').style.pointerEvents = 'auto';
                                headerDocument.getElementById("login_username").disabled = false;
                                headerDocument.getElementById("login_username").readOnly = false;
                                headerDocument.getElementById("login_password").disabled = false;
                                headerDocument.getElementById("login_password").readOnly = false;
                                headerDocument.getElementById("forgot").disabled = false;

                                document.getElementById("username_signup").disabled = false;
                                document.getElementById("username_signup").readOnly = false;
                                document.getElementById("password_signup").disabled = false;
                                document.getElementById("password_signup").readOnly = false;
                                document.getElementById("password_again_signup").disabled = false;
                                document.getElementById("password_again_signup").readOnly = false;
                                document.getElementById("email_signup").disabled = false;
                                document.getElementById("email_signup").readOnly = false;
                                document.getElementById("male_gender").disabled = false;
                                document.getElementById("female_gender").disabled = false;
                                document.getElementById("other_gender").disabled = false;
                                document.getElementById("birthdate").disabled = false;
                                document.getElementById("birthdate").readOnly = false;
                            } else {
                                //Login
                                location.href = usernameText;
                            }
                        }
                    }
                }
            }

            const maleLabel = document.getElementById("male_signup");
            const femaleLabel = document.getElementById("female_signup");
            const otherLabel = document.getElementById("other_signup");

            maleLabel.onclick = function () {
                var headerDocument = iframe.contentDocument || iframe.contentWindow.document;
                if (!registerOperation && headerDocument.getElementById('logo').style.pointerEvents !== "none")
                    document.getElementById("male_gender").checked = true;
            };

            femaleLabel.onclick = function () {
                var headerDocument = iframe.contentDocument || iframe.contentWindow.document;
                if (!registerOperation && headerDocument.getElementById('logo').style.pointerEvents !== "none")
                    document.getElementById("female_gender").checked = true;
            };

            otherLabel.onclick = function () {
                var headerDocument = iframe.contentDocument || iframe.contentWindow.document;
                if (!registerOperation && headerDocument.getElementById('logo').style.pointerEvents !== "none")
                    document.getElementById("other_gender").checked = true;
            };

            const passw_icon = document.getElementById("password_icon");
            const passw_again_icon = document.getElementById("password_again_icon");
            const passw_input = document.getElementById("password_signup");
            const passw_again_input = document.getElementById("password_again_signup");

            passw_icon.onclick = function () {
                if (passw_input.type === "password") {
                    passw_input.type = "text";

                    new Promise((resolve, reject) => {
                        passw_icon.src = "images/invisible.png"
                    });
                } else {
                    passw_input.type = "password";

                    new Promise((resolve, reject) => {
                        passw_icon.src = "images/visible.png"
                    });
                }
            }

            passw_again_icon.onclick = function () {
                if (passw_again_input.type === "password") {
                    passw_again_input.type = "text";

                    new Promise((resolve, reject) => {
                        passw_again_icon.src = "images/invisible.png"
                    });
                } else {
                    passw_again_input.type = "password";

                    new Promise((resolve, reject) => {
                        passw_again_icon.src = "images/visible.png"
                    });
                }
            }
        }

        ///DISABLE IMAGE DRAGGING
        window.onload = function (e) {
            //DISABLE IMAGE DRAGGING
            var event = e || window.event, imgs, i;
            if (event.preventDefault) {
                imgs = document.getElementsByTagName('img');

                for (i = 0; i < imgs.length; i++) {
                    imgs[i].onmousedown = disableDragging;
                }
            }
        };

        function disableDragging(e) {
            e.preventDefault();
        }
    }
} catch (err) {
    document.body.innerHTML = "";
    setTimeout(() => {
        alert("Access Denied");
    }, 1000);
}