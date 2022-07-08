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
                var params = "operation=getAccount&phpsessid=" + phpsessid;
                var xhr = new XMLHttpRequest();
                xhr.open('POST', 'https://swirlia.net/php/Index.php', true);
                xhr.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');
                xhr.withCredentials = true;
                xhr.send(params);
                xhr.onload = function () {
                    var server_response = JSON.parse(this.response);

                    if (server_response.result === "success") {
                        //Email Verified
                        const emailVerified_label = document.getElementById("emailVerified_label");
                        const emailVerified_submit = document.getElementById("emailVerified_submit");

                        if (server_response.account.email_verified === "1") {
                            document.getElementById("emailVerified_submit").style.display = "none";
                            emailVerified_label.style.color = "green";
                            emailVerified_label.innerText = "e-mail'iniz onaylanmış.";
                        } else
                            emailVerified_submit.onclick = function () {
                                if (!serverProcessing)
                                    sendVerificationToken();
                            }

                        //Email
                        const email = document.getElementById("email");
                        const email_submit = document.getElementById("email_submit");

                        email.value = server_response.account.email;

                        email_submit.onclick = function () {
                            if (!serverProcessing)
                                emailChange(email.value);
                        }

                        //Password
                        const old_password = document.getElementById("old_password");
                        const new_password = document.getElementById("new_password");
                        const new_password_again = document.getElementById("new_password_again");
                        const old_password_show = document.getElementById("old_password_show");
                        const new_password_show = document.getElementById("new_password_show");
                        const new_password_again_show = document.getElementById("new_password_again_show");
                        const password_submit = document.getElementById("password_submit");

                        old_password_show.onclick = function () {
                            if (old_password.type === "password") {
                                old_password.type = "text";

                                new Promise((resolve, reject) => {
                                    old_password_show.src = "images/invisible.png"
                                });
                            } else {
                                old_password.type = "password";

                                new Promise((resolve, reject) => {
                                    old_password_show.src = "images/visible.png"
                                });
                            }
                        }

                        new_password_show.onclick = function () {
                            if (new_password.type === "password") {
                                new_password.type = "text";

                                new Promise((resolve, reject) => {
                                    new_password_show.src = "images/invisible.png"
                                });
                            } else {
                                new_password.type = "password";

                                new Promise((resolve, reject) => {
                                    new_password_show.src = "images/visible.png"
                                });
                            }
                        }

                        new_password_again_show.onclick = function () {
                            if (new_password_again.type === "password") {
                                new_password_again.type = "text";

                                new Promise((resolve, reject) => {
                                    new_password_again_show.src = "images/invisible.png"
                                });
                            } else {
                                new_password_again.type = "password";

                                new Promise((resolve, reject) => {
                                    new_password_again_show.src = "images/visible.png"
                                });
                            }
                        }

                        password_submit.onclick = function () {
                            if (!serverProcessing)
                                passwordChange(old_password.value, new_password.value, new_password_again.value)
                        }

                        //Gender
                        const male = document.getElementById("male");
                        const female = document.getElementById("female");
                        const other = document.getElementById("other");
                        const male_label = document.getElementById("male_label");
                        const female_label = document.getElementById("female_label");
                        const other_label = document.getElementById("other_label");

                        if (server_response.account.gender === "1")
                            male.checked = true;
                        else if (server_response.account.gender === "2")
                            female.checked = true;
                        else
                            other.checked = true;

                        male.onchange = function () {
                            genderChanged("1");
                        }

                        female.onchange = function () {
                            genderChanged("2");
                        }

                        other.onchange = function () {
                            genderChanged("3");
                        }

                        male_label.onclick = function () {
                            if (!serverProcessing && !male.checked) {
                                male.checked = true;
                                genderChanged("1");
                            }
                        }

                        female_label.onclick = function () {
                            if (!serverProcessing && !female.checked) {
                                female.checked = true;
                                genderChanged("2");
                            }
                        }

                        other_label.onclick = function () {
                            if (!serverProcessing && !other.checked) {
                                other.checked = true;
                                genderChanged("3");
                            }
                        }

                        //Birthdate
                        const birthdate = document.getElementById("birthdate");
                        const birthdate_submit = document.getElementById("birthdate_submit");

                        birthdate.value = server_response.account.birthdate;

                        birthdate_submit.onclick = function () {
                            if (!serverProcessing)
                                birthdateChange(birthdate.value);
                        }

                        //Deactivate
                        const deactivate = document.getElementById("deactivate");

                        deactivate.onclick = function () {
                            if (!serverProcessing)
                                deactivateAccount();
                        }

                        //Delete
                        const deleteAccount = document.getElementById("delete");

                        deleteAccount.onclick = function () {
                            if (!serverProcessing)
                                removeAccount();
                        }

                        email.readOnly = false;
                        old_password.readOnly = false;
                        new_password.readOnly = false;
                        new_password_again.readOnly = false;
                        male.disabled = false;
                        female.disabled = false;
                        other.disabled = false;
                        birthdate.readOnly = false;
                        serverProcessing = false;

                        //Display Page
                        document.getElementsByClassName("loader")[0].style.display = "none";
                        document.getElementById("container").style.display = "flex";
                    } else {
                        if (server_response.message === "User not exists")
                            parent.window.location.href = "";
                        else if (server_response.message === "User not logged in")
                            parent.window.location.href = "";
                    }
                }
            }
        }

        function sendVerificationToken() {
            serverProcessing = true;
            email.readOnly = true;
            old_password.readOnly = true;
            new_password.readOnly = true;
            new_password_again.readOnly = true;
            male.disabled = true;
            female.disabled = true;
            other.disabled = true;
            birthdate.readOnly = true;

            var params = "operation=verifyEmail&phpsessid=" + phpsessid;
            var xhr = new XMLHttpRequest();
            xhr.open('POST', 'https://swirlia.net/php/Index.php', true);
            xhr.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');
            xhr.withCredentials = true;
            xhr.send(params);
            xhr.onload = function () {
                var server_response = JSON.parse(this.response);

                const snackbar = document.getElementById("snackbar");

                if (server_response.result === "success") {
                    snackbar.innerText = "Tekrar bir onaylama maili tarafınıza gönderildi.";
                    snackbar.className = "show";

                    setTimeout(function () {
                        snackbar.className = snackbar.className.replace("show", "");
                    }, 5000);

                    document.getElementById("emailVerified_submit").style.display = "none";
                    emailVerified_label.style.color = "orangered";
                    emailVerified_label.innerText = "Tekrar mail talebinde bulunabilmek için 1 saat beklemelisiniz.";
                } else {
                    if (server_response.message === "User not logged in")
                        parent.window.location.href = "";
                    else if (server_response.message === "Server failure") {
                        snackbar.innerText = "Sunucu hatası. Lütfen tekrar deneyiniz..";
                        snackbar.className = "show";

                        setTimeout(function () {
                            snackbar.className = snackbar.className.replace("show", "");
                        }, 5000);
                    } else if (server_response.message === "Email already verified") {
                        snackbar.innerText = "e-mail'iniz zaten onaylanmış.";
                        snackbar.className = "show";

                        setTimeout(function () {
                            snackbar.className = snackbar.className.replace("show", "");
                        }, 5000);

                        document.getElementById("emailVerified_submit").style.display = "none";
                        emailVerified_label.style.color = "green";
                        emailVerified_label.innerText = "e-mail'iniz onaylanmış.";
                    } else {
                        var time = server_response.message.split(": ");
                        time = time[1];

                        var minutes = time / 60;
                        while (minutes >= 60)
                            minutes = minutes - 60;

                        snackbar.innerText = "Son talebiniz üzerinden 1 saat geçmedi. Beklemeniz gereken süre: " + Math.floor(minutes) + " dakikadır.";
                        snackbar.className = "show";

                        setTimeout(function () {
                            snackbar.className = snackbar.className.replace("show", "");
                        }, 10000);
                    }
                }

                email.readOnly = false;
                old_password.readOnly = false;
                new_password.readOnly = false;
                new_password_again.readOnly = false;
                male.disabled = false;
                female.disabled = false;
                other.disabled = false;
                birthdate.readOnly = false;
                serverProcessing = false;
            }
        }

        function emailChange(email_new) {
            const email_error = document.getElementById("email_error");
            email_error.style.color = "red";

            if (email_new.length > 5 && email_new.includes("@") && email_new.includes(".")) {
                serverProcessing = true;
                email.readOnly = true;
                old_password.readOnly = true;
                new_password.readOnly = true;
                new_password_again.readOnly = true;
                male.disabled = true;
                female.disabled = true;
                other.disabled = true;
                birthdate.readOnly = true;

                var params = "operation=changeEmail&email_new=" + encodeURIComponent(email_new) + "&phpsessid=" + phpsessid;
                var xhr = new XMLHttpRequest();
                xhr.open('POST', 'https://swirlia.net/php/Index.php', true);
                xhr.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');
                xhr.withCredentials = true;
                xhr.send(params);
                xhr.onload = function () {
                    var server_response = JSON.parse(this.response);

                    const snackbar = document.getElementById("snackbar");

                    if (server_response.result === "success") {
                        snackbar.innerText = "Mail adresine onaylama maili gönderildi.";
                        snackbar.className = "show";

                        setTimeout(function () {
                            snackbar.className = snackbar.className.replace("show", "");
                        }, 5000);

                        document.getElementById("email_submit").style.display = "none";
                        email_error.style.color = "orangered";
                        email_error.innerText = "Tekrar işlem yapabilmek için 12 saat beklemelisiniz.";
                        email_error.style.display = "flex";
                    } else {
                        if (server_response.message === "User not logged in")
                            parent.window.location.href = "";
                        else if (server_response.message === "Server failure") {
                            email_error.innerText = "Sunucu hatası. Lütfen tekrar deneyiniz..";
                            email_error.style.display = "flex";
                        } else if (server_response.message === "Emails are same") {
                            email_error.innerText = "Eski e-mail ve yeni e-mail aynı olmamalı.";
                            email_error.style.display = "flex";
                        } else if (server_response.message === "Email is already in use") {
                            email_error.innerText = "E-mail başka bir hesap tarafından kullanılmakta.";
                            email_error.style.display = "flex";
                        } else if (server_response.message === "Invalid Parameters") {
                            email_error.innerText = "E-mail adresi hatalı.";
                            email_error.style.display = "flex";
                        } else {
                            var time = server_response.message.split(": ");
                            time = time[1];

                            const hours = time / 3600;
                            var minutes = time / 60;
                            while (minutes >= 60)
                                minutes = minutes - 60;

                            snackbar.innerText = "Son talebinizin üzerinden 12 saat geçmedi. Beklemeniz gereken süre: " + Math.floor(hours) + " saat, " + Math.floor(minutes) + " dakikadır.";
                            snackbar.className = "show";

                            setTimeout(function () {
                                snackbar.className = snackbar.className.replace("show", "");
                            }, 10000);
                        }
                    }

                    email.readOnly = false;
                    old_password.readOnly = false;
                    new_password.readOnly = false;
                    new_password_again.readOnly = false;
                    male.disabled = false;
                    female.disabled = false;
                    other.disabled = false;
                    birthdate.readOnly = false;
                    serverProcessing = false;
                }
            } else {
                email_error.style.display = "flex";
                email_error.innerText = "E-mail adresi hatalı.";
            }
        }

        function passwordChange(oldPassword, newPassword, newPasswordAgain) {
            const password_error = document.getElementById("password_error");
            password_error.style.color = "red";

            //Check Old
            var upper_characters = ["A", "B", "C", "Ç", "D", "E", "F", "G", "Ğ", "H", "I", "İ", "J", "K", "L", "M",
                "N", "O", "Ö", "P", "Q", "R", "S", "Ş", "T", "U", "Ü", "V", "W", "X", "Y", "Z"];
            var number_characters = ["0", "1", "2", "3", "4", "5", "6", "7", "8", "9"];
            var password_characters = oldPassword.split("");

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

            if (oldPassword === newPassword) {
                password_error.style.display = "block";
                password_error.innerText = "Mevcut şifre değiştirilecek olan şifreyle aynı olamaz.";
            } else if (oldPassword.length < 8 || oldPassword.length > 16) {
                password_error.style.display = "block";
                password_error.innerText = "Mevcut şifre yanlış girildi.";
            } else if (!upperCheck || !numberCheck) {
                password_error.style.display = "block";
                password_error.innerText = "Mevcut şifre yanlış girildi.";
            } else {
                //Check New
                var upper_characters = ["A", "B", "C", "Ç", "D", "E", "F", "G", "Ğ", "H", "I", "İ", "J", "K", "L", "M",
                    "N", "O", "Ö", "P", "Q", "R", "S", "Ş", "T", "U", "Ü", "V", "W", "X", "Y", "Z"];
                var number_characters = ["0", "1", "2", "3", "4", "5", "6", "7", "8", "9"];
                var password_characters = newPassword.split("");

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

                if (newPassword !== newPasswordAgain) {
                    password_error.style.display = "block";
                    password_error.innerText = "Şifreler uyuşmuyor.";
                } else if (newPassword.length < 8 || newPassword.length > 16) {
                    password_error.style.display = "block";
                    password_error.innerText = "Yeni şifre minimum 8, maksimum 16 karakterden oluşabilir.";
                } else if (!upperCheck || !numberCheck) {
                    password_error.style.display = "block";
                    password_error.innerText = "Yeni şifre en azından bir büyük harf ve sayı içermelidir.";
                } else {
                    serverProcessing = true;
                    email.readOnly = true;
                    old_password.readOnly = true;
                    new_password.readOnly = true;
                    new_password_again.readOnly = true;
                    male.disabled = true;
                    female.disabled = true;
                    other.disabled = true;
                    birthdate.readOnly = true;

                    //Server
                    var params = "operation=changePassword&oldPassword=" + encodeURIComponent(oldPassword) + "&newPassword=" + encodeURIComponent(newPassword) + "&phpsessid=" + phpsessid;
                    var xhr = new XMLHttpRequest();
                    xhr.open('POST', 'https://swirlia.net/php/Index.php', true);
                    xhr.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');
                    xhr.withCredentials = true;
                    xhr.send(params);
                    xhr.onload = function () {
                        var server_response = JSON.parse(this.response);

                        if (server_response.result === "success") {
                            password_error.style.display = "block";
                            password_error.style.color = "green";
                            password_error.innerText = "Şifre değiştirildi.";

                            email.readOnly = false;
                            old_password.readOnly = false;
                            new_password.readOnly = false;
                            new_password_again.readOnly = false;
                            male.disabled = false;
                            female.disabled = false;
                            other.disabled = false;
                            birthdate.readOnly = false;
                            serverProcessing = false;
                        } else {
                            if (server_response.message === "User not exists")
                                parent.window.location.href = "";
                            else if (server_response.message === "User not logged in")
                                parent.window.location.href = "";
                            else {
                                password_error.style.display = "block";
                                password_error.innerText = "Mevcut şifre yanlış girildi.";

                                email.readOnly = false;
                                old_password.readOnly = false;
                                new_password.readOnly = false;
                                new_password_again.readOnly = false;
                                male.disabled = false;
                                female.disabled = false;
                                other.disabled = false;
                                birthdate.readOnly = false;
                                serverProcessing = false;
                            }
                        }
                    }
                }
            }
        }

        function genderChanged(gender) {
            serverProcessing = true;
            email.readOnly = true;
            old_password.readOnly = true;
            new_password.readOnly = true;
            new_password_again.readOnly = true;
            male.disabled = true;
            female.disabled = true;
            other.disabled = true;
            birthdate.readOnly = true;

            var params = "operation=changeGender&gender=" + gender + "&phpsessid=" + phpsessid;
            var xhr = new XMLHttpRequest();
            xhr.open('POST', 'https://swirlia.net/php/Index.php', true);
            xhr.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');
            xhr.withCredentials = true;
            xhr.send(params);
            xhr.onload = function () {
                var server_response = JSON.parse(this.response);

                if (server_response.result === "failure") {
                    if (server_response.message === "User not exists")
                        parent.window.location.href = "";
                    else if (server_response.message === "User not logged in")
                        parent.window.location.href = "";
                }

                email.readOnly = false;
                old_password.readOnly = false;
                new_password.readOnly = false;
                new_password_again.readOnly = false;
                male.disabled = false;
                female.disabled = false;
                other.disabled = false;
                birthdate.readOnly = false;
                serverProcessing = false;
            }
        }

        function birthdateChange(birthdate) {
            const birthdate_error = document.getElementById("birthdate_error");
            birthdate_error.style.color = "red";

            serverProcessing = true;
            email.readOnly = true;
            old_password.readOnly = true;
            new_password.readOnly = true;
            new_password_again.readOnly = true;
            male.disabled = true;
            female.disabled = true;
            other.disabled = true;
            birthdate.readOnly = true;

            //Server
            var params = "operation=changeBirthdate&birthdate=" + birthdate + "&phpsessid=" + phpsessid;
            var xhr = new XMLHttpRequest();
            xhr.open('POST', 'https://swirlia.net/php/Index.php', true);
            xhr.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');
            xhr.withCredentials = true;
            xhr.send(params);
            xhr.onload = function () {
                var server_response = JSON.parse(this.response);

                if (server_response.result === "success") {
                    birthdate_error.style.display = "block";
                    birthdate_error.style.color = "green";
                    birthdate_error.innerText = "Doğum tarihiniz değiştirildi.";

                    email.readOnly = false;
                    old_password.readOnly = false;
                    new_password.readOnly = false;
                    new_password_again.readOnly = false;
                    male.disabled = false;
                    female.disabled = false;
                    other.disabled = false;
                    birthdate.readOnly = false;
                    serverProcessing = false;
                } else {
                    if (server_response.message === "User not exists")
                        parent.window.location.href = "";
                    else if (server_response.message === "User not logged in")
                        parent.window.location.href = "";
                    else {
                        birthdate_error.style.display = "block";
                        birthdate_error.innerText = "Kullanıcı yaşı 14-100 arası olmalıdır.";

                        email.readOnly = false;
                        old_password.readOnly = false;
                        new_password.readOnly = false;
                        new_password_again.readOnly = false;
                        male.disabled = false;
                        female.disabled = false;
                        other.disabled = false;
                        birthdate.readOnly = false;
                        serverProcessing = false;
                    }
                }
            }
        }

        function deactivateAccount() {
            if (confirm("Hesabınızı dondurmak istediğinize emin misiniz?\n\n"
                + "Hesabınızı tekrar aktif hale getirmek istediğinizde sadece giriş yapmanız yeterlidir.")) {
                serverProcessing = true;
                email.readOnly = true;
                old_password.readOnly = true;
                new_password.readOnly = true;
                new_password_again.readOnly = true;
                male.disabled = true;
                female.disabled = true;
                other.disabled = true;
                birthdate.readOnly = true;

                var params = "operation=deactivate&phpsessid=" + phpsessid;
                var xhr = new XMLHttpRequest();
                xhr.open('POST', 'https://swirlia.net/php/Index.php', true);
                xhr.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');
                xhr.withCredentials = true;
                xhr.send(params);
                xhr.onload = function () {
                    parent.window.location.href = "";
                }
            }
        }

        function removeAccount() {
            if (confirm("Hesabınızı silmek istediğinize emin misiniz?\n\n"
                + "Hesabınız dondurulacak ve 30 gün geri sayım yapılacak. "
                + "Eğer 30 gün içinde bu talebi iptal etmezseniz hesabınız kalıcı olarak silinecek.\n\n"
                + "İptal etmek ve hesabınızı tekrar aktif hale getirmek istediğinizde sadece giriş yapmanız yeterlidir.")) {
                serverProcessing = true;
                email.readOnly = true;
                old_password.readOnly = true;
                new_password.readOnly = true;
                new_password_again.readOnly = true;
                male.disabled = true;
                female.disabled = true;
                other.disabled = true;
                birthdate.readOnly = true;

                var params = "operation=delete&phpsessid=" + phpsessid;
                var xhr = new XMLHttpRequest();
                xhr.open('POST', 'https://swirlia.net/php/Index.php', true);
                xhr.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');
                xhr.withCredentials = true;
                xhr.send(params);
                xhr.onload = function () {
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