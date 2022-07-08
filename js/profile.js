var phpsessid = null;

//Sounds
const preferences_opening_sound = document.getElementById("preferences_opening_sound");
const preferences_switching_sound = document.getElementById("preferences_switching_sound");
const preferences_closing_sound = document.getElementById("preferences_closing_sound");

try {
    if (window.top.location.host === "swirlia.com" || window.top.location.host === "wwww.swirlia.com") {
        //Declaring
        var isLoggedIn = false;
        var isSelf = false;
        var requested_username = null;
        var username = null;
        var id = null;
        var requested_id = null;

        var uploadImage = false;
        var selfImage = false;
        var editingBio = false;
        var followOperation = false;

        var imagePath;

        /////////////////////////FORM/////////////////////////
        const openFormButtons = document.querySelectorAll('[data-form-target]');
        const closeFormButtons = document.querySelectorAll('[data-close-button]');
        const overlay = document.getElementById("overlay");

        for (let i = 0; i < openFormButtons.length; i++) {
		    openFormButtons[i].onclick = function () {
			    const form = document.querySelector(openFormButtons[i].dataset.formTarget);
                	    openForm(form, openFormButtons[i].id);
		    }
	    }

	    for (let i = 0; i < closeFormButtons.length; i++) {
		    closeFormButtons[i].onclick = function () {
			    const form = closeFormButtons[i].closest(".form");
                	    closeForm(form);
		    }
	    }

        //////////HANDLE LOGIN AND USERNAME////////////////
        const iframe = document.getElementById("iframe_header");
        const headerDocument = iframe.contentDocument || iframe.contentWindow.document;

        if (headerDocument.getElementById("header_signed_in") != null) {
            var xhr = new XMLHttpRequest();
            xhr.open('POST', 'html/session.php', true);
            xhr.send();

            xhr.onload = function () {
                phpsessid = this.response;

                login();
            }
        } else {
            iframe.onload = function () {
                var xhr = new XMLHttpRequest();
                xhr.open('POST', 'html/session.php', true);
                xhr.send();

                xhr.onload = function () {
                    phpsessid = this.response;

                    login();
                }
            }
        }

        function login() {
            var temp = window.location.pathname.split("/");
            requested_username = temp[temp.length - 1];
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
                var params = "operation=isLoggedIn&phpsessid=" + phpsessid;
                var xhr = new XMLHttpRequest();
                xhr.open('POST', 'https://swirlia.net/php/Profile.php', true);
                xhr.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');
                xhr.withCredentials = true;
                xhr.send(params);

                xhr.onload = function () {
                    var server_response = JSON.parse(this.response);

                    if (server_response.result === "failure")
                        location.href = "";
                    else
                        location.href = server_response.username;
                }
            } else {
                var params = "operation=getProfile&username=" + requested_username + "&phpsessid=" + phpsessid;
                var xhr = new XMLHttpRequest();
                xhr.open('POST', 'https://swirlia.net/php/Profile.php', true);
                xhr.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');
                xhr.withCredentials = true;
                xhr.send(params);

                xhr.onload = function () {
                    var server_response = JSON.parse(this.response);

                    if (server_response.user_info == null)
                        document.title = requested_username;
                    else
                        document.title = server_response.user_info.username;

                    //SIGNED IN
                    if (server_response.result === "success") {
                        isLoggedIn = true;

                        //SUPPORT
                        const footer = document.getElementById("iframe_footer");
                        const footerDocument = footer.contentDocument || footer.contentWindow.document;

                        if (footerDocument.getElementById("span_hide") != null) {
                            const footerDocument = footer.contentDocument || footer.contentWindow.document;
                            footerDocument.getElementById("span_hide").style.display = "initial";
                            footerDocument.getElementById("support").style.display = "initial";
                            footerDocument.getElementById("support").onclick = function () {
                                openForm(document.getElementById("support_form"), "support");
                            }
                        } else {
                            footer.onload = function () {
                                const footerDocument = footer.contentDocument || footer.contentWindow.document;
                                footerDocument.getElementById("span_hide").style.display = "initial";
                                footerDocument.getElementById("support").style.display = "initial";
                                footerDocument.getElementById("support").onclick = function () {
                                    openForm(document.getElementById("support_form"), "support");
                                }
                            }
                        }

                        username = server_response.username;
                        id = server_response.id

                        if (username.toLowerCase() === requested_username.toLowerCase()) {
                            isSelf = true;

                            afterCheck(server_response); //Continue
                        } else {
                            if (server_response.message === "User not exists")
                                location.href = server_response.username;
                            else {
                                requested_id = server_response.user_info.id;

                                afterCheck(server_response); //Continue
                            }
                        }

                        //NOT SIGNED IN
                    } else {
                        if (server_response.message === "User not exists")
                            location.href = "";
                        else
                            afterCheck(server_response); // Continue
                    }
                }
            }
        }

        /////////////HANDLE MENU///////////////
        function handleMenu() {
            const iframe = document.getElementById("iframe_header");
            const headerDocument = iframe.contentDocument || iframe.contentWindow.document;

            if (isLoggedIn) {
                headerDocument.getElementById("header_signed_in").style.display = "flex";
                headerDocument.getElementById("header_not_signed_in").style.display = "none";
                headerDocument.body.removeChild(headerDocument.getElementById("header_not_signed_in"));

                //H3 Label
                headerDocument.getElementById("header_h3").innerText = "Hoşgeldin, " + username;

                ///////////MENU/////////////
                const preferences_div = headerDocument.getElementById("menu_preferences");
                const preferences_label = headerDocument.getElementById("preferences_label");
                const preferences_img = headerDocument.getElementById("preferences_img");

                const exit_div = headerDocument.getElementById("menu_exit");
                const exit_label = headerDocument.getElementById("exit_label");
                const exit_img = headerDocument.getElementById("exit_img");

                const followings_div = headerDocument.getElementById("menu_followings");
                const profile_div = headerDocument.getElementById("menu_profile");

                preferences_div.onmouseover = function () {
                    preferences_label.style.filter = "invert(30%)";
                    preferences_img.style.filter = "invert(30%)";
                }

                preferences_div.onmouseout = function () {
                    preferences_label.style.filter = "invert(0%)";
                    preferences_img.style.filter = "invert(0%)";
                }

                exit_div.onmouseover = function () {
                    exit_label.style.filter = "invert(30%)";
                    exit_img.style.filter = "invert(30%)";
                }

                exit_div.onmouseout = function () {
                    exit_label.style.filter = "invert(0%)";
                    exit_img.style.filter = "invert(0%)";
                }

                exit_div.onclick = function () {
                    var params = "operation=exit&phpsessid=" + phpsessid;
                    var xhr = new XMLHttpRequest();
                    xhr.open('POST', 'https://swirlia.net/php/Profile.php', true);
                    xhr.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');
                    xhr.withCredentials = true;
                    xhr.send(params);

                    xhr.onload = function () {
                        location.href = "";
                    }
                }
                ///////////MENU/////////////

                //PREFERENCES AND FOLLOWINGS
                preferences_div.onclick = function () {
                    openForm(document.getElementById("preferences_form"), "preferences");
                }

                followings_div.onclick = function () {
                    openForm(document.getElementById("followings_form"), "followings");
                }

                if (isSelf) {
                    ///////////MENU/////////////
                    const followings_div = headerDocument.getElementById("menu_followings");
                    const followings_label = headerDocument.getElementById("followings_label");
                    const followings_img = headerDocument.getElementById("followings_img");

                    profile_div.style.display = "none";
                    followings_div.style.display = "flex";

                    followings_div.onmouseover = function () {
                        followings_label.style.filter = "invert(30%)";
                        followings_img.style.filter = "invert(30%)";
                    }

                    followings_div.onmouseout = function () {
                        followings_label.style.filter = "invert(0%)";
                        followings_img.style.filter = "invert(0%)";
                    }
                    ///////////MENU/////////////
                } else {
                    ///////////MENU/////////////
                    const profile_div = headerDocument.getElementById("menu_profile");
                    const profile_label = headerDocument.getElementById("profile_label");
                    const profile_img = headerDocument.getElementById("profile_img");

                    followings_div.style.display = "none";
                    profile_div.style.display = "flex";

                    profile_div.onmouseover = function () {
                        profile_label.style.filter = "invert(30%)";
                        profile_img.style.filter = "invert(30%)";
                    }

                    profile_div.onmouseout = function () {
                        profile_label.style.filter = "invert(0%)";
                        profile_img.style.filter = "invert(0%)";
                    }

                    profile_div.onclick = function () {
                        location.href = username;
                    }
                    ///////////MENU/////////////
                }
            } else {
                headerDocument.getElementById("header_signed_in").style.display = "none";
                headerDocument.body.removeChild(headerDocument.getElementById("header_signed_in"));
                headerDocument.getElementById("header_not_signed_in").style.display = "flex";

                //H3 Label
                headerDocument.getElementById("header_h3").innerText = "Giriş yap veya anonim olarak gezin!";
            }
        }

        /////////////SWIRL/////////////////////
        const swirl = document.getElementById("iframe_swirl");
        swirl.onload = function () {
            const swirl = document.getElementById("iframe_swirl");
            const swirlDocument = swirl.contentDocument || swirl.contentWindow.document;

            //Up
            const profile_swirl = swirlDocument.getElementById("swirl");
            const searchbox = swirlDocument.getElementById("searchbox");
            const searchbox_img = swirlDocument.getElementById("searchbox_img");
            const random = swirlDocument.getElementsByClassName("random")[0];
            const auxilliary = swirlDocument.getElementsByClassName("auxilliary")[0];

            profile_swirl.classList.remove("swirl");
            profile_swirl.classList.add("profile_swirl");
            searchbox.style.height = "2vw";
            searchbox_img.style.height = "2vw";
            random.style.height = "2vw";
            random.style.marginTop = "5px";
            auxilliary.style.marginTop = "0.5em";

            //Below
            const images = swirlDocument.getElementById("images");

            images.style.gridTemplateColumns = "auto auto auto auto auto auto auto auto auto auto auto auto auto auto auto";
            images.style.gridTemplateRows = "auto auto";
            images.style.marginBottom = "0px";

            const profile_users = swirlDocument.querySelectorAll(".users");
            const profile_img = swirlDocument.querySelectorAll(".img");
            const profile_username = swirlDocument.querySelectorAll(".username");
            const tooltip = swirlDocument.querySelectorAll(".tooltip");
            const tooltiptext = swirlDocument.querySelectorAll(".tooltiptext");

            for (let i = 0; i < profile_users.length; i++) {
                tooltiptext[i].classList.remove("tooltiptext");
                profile_username[i].classList.remove("username");
                profile_img[i].classList.remove("img");
                profile_users[i].classList.remove("users");

                profile_users[i].classList.add("profile_users");
                profile_img[i].classList.add("profile_img");
                profile_username[i].classList.add("profile_username");

                if (i > 14) {
                    if (i === 15)
                        tooltiptext[i].classList.add("tooltiptext_nd_left");
                    else if (i === 29)
                        tooltiptext[i].classList.add("tooltiptext_nd_right");
                    else
                        tooltiptext[i].classList.add("tooltiptext_nd");

                    tooltip[i].style.bottom = "175%";
                } else {
                    if (i === 0)
                        tooltiptext[i].classList.add("tooltiptext_left");
                    else if (i === 14)
                        tooltiptext[i].classList.add("tooltiptext_right");
                    else
                        tooltiptext[i].classList.add("tooltiptext");
                }
            }
        }

        //////////////CONTINUE////////////////
        function afterCheck(server_response) {
            handleMenu();

            //Profile Sync
            var user_info = server_response.user_info;

            //Profile image
            var user_image = document.getElementById("user_image");
            if (user_info.profile_img !== "php/uploads/user.png")
                selfImage = true;

            new Promise((resolve, reject) => {
                user_image.src = "https://swirlia.net/" + user_info.profile_img;
            });

            imagePath = "https://swirlia.net/" + user_info.profile_img;

            //Username
            document.getElementById("username").innerText = user_info.username;

            //Is Online
            if (user_info.is_online) {
                document.getElementById("online_status_label").innerText = "Çevrimiçi";
                document.getElementById("online_status_label").style.color = "mediumseagreen";

                new Promise((resolve, reject) => {
                    document.getElementById("online_status_img").src = "images/online.png";
                });
            } else {
                document.getElementById("online_status_label").style.color = "indianred";

                if (user_info.last_seen === -1)
                    document.getElementById("online_status_label").innerText = "Çevrimdışı";
                else
                    lastSeenDate(user_info.last_seen);

                new Promise((resolve, reject) => {
                    document.getElementById("online_status_img").src = "images/offline.png";
                });
            }

            //Created at
            if (user_info.created_at !== null) {
                var clipped_createdAt = user_info.created_at.substring(0, 10);
                var split_createdAt = clipped_createdAt.split("-");

                var month_createdAt = "Ocak";
                switch (split_createdAt[1]) {
                    case "02": month_createdAt = "Şubat";
                        break;
                    case "03": month_createdAt = "Mart";
                        break;
                    case "04": month_createdAt = "Nisan";
                        break;
                    case "05": month_createdAt = "Mayıs";
                        break;
                    case "06": month_createdAt = "Haziran";
                        break;
                    case "07": month_createdAt = "Temmuz";
                        break;
                    case "08": month_createdAt = "Ağustos";
                        break;
                    case "09": month_createdAt = "Eylül";
                        break;
                    case "10": month_createdAt = "Ekim";
                        break;
                    case "11": month_createdAt = "Kasım";
                        break;
                    case "12": month_createdAt = "Aralık";
                        break;
                }

                document.getElementById("register_date").innerText =
                    "Kayıt tarihi: " + split_createdAt[2] + " " + month_createdAt + " " + split_createdAt[0];
            }

            if (user_info.bio !== null) {
                //Detect if only whitespace used
                if (!user_info.bio.replace(/\s/g, '').length)
                    document.getElementById("bio").value = user_info.username +
                        " kendisi hakkında bir şey yazmamayı tercih etmiş";
                else
                    document.getElementById("bio").value = user_info.bio;
            } else
                document.getElementById("bio").value = user_info.username +
                    " kendisi hakkında bir şey yazmamayı tercih etmiş";

            if (server_response.conversations !== null)
                document.getElementById("conversations").innerText = server_response.conversations + " Mesaj";
            else
                document.getElementById("conversations_count").style.display = "none";

            if (server_response.followers !== null)
                document.getElementById("followers").innerText = server_response.followers + " Takipçi";
            else
                document.getElementById("followers_count").style.display = "none";

            if (server_response.conversations === null && server_response.followers === null)
                document.getElementById("follow").style.borderBottomLeftRadius = "1vh";
            else if (server_response.conversations === null)
                document.getElementById("followers_count").style.borderBottomLeftRadius = "1vh";

            if (!isLoggedIn)
                document.getElementById("block").style.display = "none";

            if (isSelf) {
                document.getElementById("follow").style.display = "none";
                document.getElementById("block").style.display = "none";
                document.getElementById("report").style.display = "none";
                document.getElementById("followers_count").style.borderBottomRightRadius = "1vh";
                document.getElementById("user_edit_bio").style.display = "initial";

                //Bio
                if (user_info.bio === null) {
                    document.getElementById("bio").value =
                        "Sağdaki yeşil kaleme tıklayarak buraya kendin hakkında bir şeyler yazabilirsin. Soldan ise fotoğrafını değiştirebilirsin.";
                    document.getElementById("bio").style.color = "red";
                } else if (!user_info.bio.replace(/\s/g, '').length) {
                    document.getElementById("bio").value =
                        "Sağdaki yeşil kaleme tıklayarak buraya kendin hakkında bir şeyler yazabilirsin. Soldan ise fotoğrafını değiştirebilirsin.";
                    document.getElementById("bio").style.color = "red";
                }

                //Edit Bio
                var user_edit_bio = document.getElementById("user_edit_bio");
                var user_bio_div = document.getElementById("user_bio_div");
                var user_bio = document.getElementById("bio");

                user_edit_bio.onclick = function () {
                    if (!editingBio) {
                        if (user_bio.readOnly) {
                            user_bio.readOnly = false;
                            user_bio_div.style.backgroundColor = "white";

                            new Promise((resolve, reject) => {
                                user_edit_bio.src = "images/save.png";
                            });

                            if (user_info.bio === null) {
                                user_bio.value = "";
                                user_bio.style.color = "black";
                            } else if (!user_bio.value.replace(/\s/g, '').length) {
                                user_bio.value = "";
                                user_bio.style.color = "black";
                            }

                            user_bio.focus();
                        } else {
                            user_bio.readOnly = true;
                            user_bio_div.style.backgroundColor = "white";

                            new Promise((resolve, reject) => {
                                user_edit_bio.src = "images/edit_bio.png";
                            });

                            var value = null;

                            if (user_bio.value === "") {
                                user_bio.value =
                                    "Sağdaki yeşil kaleme tıklayarak buraya kendin hakkında bir şeyler yazabilirsin. Soldan ise fotoğrafını değiştirebilirsin.";
                                user_bio.style.color = "red";
                            } else {
                                if (!user_bio.value.replace(/\s/g, '').length) {
                                    user_bio.value =
                                        "Sağdaki yeşil kaleme tıklayarak buraya kendin hakkında bir şeyler yazabilirsin. Soldan ise fotoğrafını değiştirebilirsin.";
                                    user_bio.style.color = "red";
                                } else
                                    value = user_bio.value;
                            }

                            if (user_info.bio !== value) {
                                editingBio = true;

                                user_info.bio = value;

                                var isNull = false;
                                if (value === null)
                                    isNull = true;

                                var params = "operation=editBio&bio=" + encodeURIComponent(value) + "&isNull=" + isNull + "&phpsessid=" + phpsessid;
                                var xhr = new XMLHttpRequest();
                                xhr.open('POST', 'https://swirlia.net/php/Profile.php', true);
                                xhr.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');
                                xhr.withCredentials = true;
                                xhr.send(params);
                                xhr.onload = function () {
                                    editingBio = false;
                                }
                            }
                        }
                    }
                }

                //Edit Image
                var edit_image = document.getElementById("user_edit_image");
                user_image.onmouseover = function () {
                    edit_image.style.display = "initial";
                }

                edit_image.onmouseover = function () {
                    edit_image.style.display = "initial";
                    edit_image.style.filter = "invert(15%)";
                }

                edit_image.onmouseout = function () {
                    edit_image.style.filter = "invert(0%)";
                }

                user_image.onmouseout = function () {
                    edit_image.style.display = "none";
                }

                //Edit Image Click
                const edit_photo = document.getElementById("user_img_submit");
                edit_photo.onclick = function () {
                    const img_input = document.getElementById("user_img_change").files[0];

                    const form_error = document.getElementById("form_error");
                    form_error.style.display = "block";

                    if (img_input !== undefined) {
                        if (img_input.type !== "image/png" && img_input.type !== "image/jpeg" && img_input.type !== "image/jpg")
                            form_error.innerText = "Lütfen sadece png, jpg veya jpeg dosyası seçiniz";
                        else {
                            if (img_input.size > 16000000)
                                form_error.innerText = "Fotoğraf boyutu 16Mb yi aşmamalı";
                            else {
                                document.getElementById("user_img_change").disabled = true;
                                document.getElementById("user_img_submit").disabled = true;
                                document.getElementById("user_img_remove").disabled = true;

                                form_error.innerText = "Gönderiliyor...";
                                closeForm(document.getElementById("form"));

                                uploadImage = true;

                                handleImageUpload(img_input);
                            }
                        }
                    } else
                        form_error.innerText = "Lütfen fotoğraf seçiniz";
                }
            } else {
                if (server_response.isFollowing) {
                    new Promise((resolve, reject) => {
                        document.getElementById("follow_img").src = "images/unfollow.png";
                    });

                    document.getElementById("follow_label").innerText = "Takipten Çık";
                }

                //Follow, Block, Report
                const follow_button = document.getElementById("follow");
                const block_button = document.getElementById("block");
                const report_button = document.getElementById("report");

                //Follow
                follow_button.onclick = function () {
                    if (isLoggedIn && !followOperation) {
                        followOperation = true;

                        var params = "operation=follow&id=" + requested_id + "&phpsessid=" + phpsessid;
                        var xhr = new XMLHttpRequest();
                        xhr.open('POST', 'https://swirlia.net/php/Profile.php', true);
                        xhr.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');
                        xhr.withCredentials = true;
                        xhr.send(params);
                        xhr.onload = function () {
                            followOperation = false;

                            var response = JSON.parse(this.response);

                            if (response.result === "success") {
                                if (response.isFollowing === "false") {
                                    new Promise((resolve, reject) => {
                                        document.getElementById("follow_img").src = "images/follow.png";
                                    });

                                    document.getElementById("follow_label").innerText = "Takipt Et";
                                    server_response.isFollowing = "false";
                                } else {
                                    new Promise((resolve, reject) => {
                                        document.getElementById("follow_img").src = "images/unfollow.png";
                                    });

                                    document.getElementById("follow_label").innerText = "Takipten Çık";
                                    server_response.isFollowing = "true";
                                }
                            } else {
                                if (response.message === "User not exists")
                                    location.href = username;
                                else if (response.message === "User not logged in")
                                    location.href = requested_username;
                            }
                        }
                    } else if (!followOperation)
                        openForm(document.getElementById("login_register_form"), "login_register_form");
                }

                //Block
                if (isLoggedIn) {
                    block_button.onclick = function () {
                        openForm(document.getElementById("block_form"), "block");
                    }
                }

                //Report
                report_button.onclick = function () {
                    openForm(document.getElementById("report_form"), "report");
                }
            }

            //Display Page
            document.getElementsByClassName("loader")[0].style.display = "none";
            document.getElementById("container").style.display = "flex";

            //Bio Resize
            var user_bio_div = document.getElementById("user_bio_div");
            var user_bio = document.getElementById("bio");
            var dummy = document.getElementById("dummy");

            function formatDummyText(text) {
                if (!text) {
                    return '&nbsp;';
                }
                return text.replace(/\n$/, '<br>&nbsp;')
                    .replace(/\n/g, '<br>');
            }

            function positionTextarea() {
                var h = user_bio_div.clientHeight;
                var top = Math.max(0, (h - dummy.clientHeight) * 0.5);
                user_bio.style.paddingTop = top + "px";
                user_bio.style.height = (h - top) + "px";
            }

            user_bio.addEventListener("keyup", method);
            user_bio.addEventListener("change", method);
            function method() {
                var html = formatDummyText(user_bio.value);
                dummy.innerText = html;
                positionTextarea();
            }

            var trigger = new Event("change");
            user_bio.dispatchEvent(trigger);

            user_bio.scrollTop = user_bio.scrollHeight;

            //Set image dimensions
            var imageDivWidth = document.getElementById("user_image_div").clientWidth;
            var imageDivHeight = document.getElementById("user_image_div").clientHeight;

            if (imageDivWidth > imageDivHeight) {
                document.getElementById("user_image").style.height = imageDivHeight + "px";
                document.getElementById("user_image").style.width = imageDivHeight + "px";
            } else {
                document.getElementById("user_image").style.height = imageDivWidth + "px";
                document.getElementById("user_image").style.width = imageDivWidth + "px";
            }

            //Edit Image Resize
            if (imageDivWidth > imageDivHeight) {
                document.getElementById("user_edit_image").style.height = (imageDivHeight / 8) + "px";
                document.getElementById("user_edit_image").style.width = (imageDivHeight / 8) + "px";
            } else {
                document.getElementById("user_edit_image").style.height = (imageDivWidth / 8) + "px";
                document.getElementById("user_edit_image").style.width = (imageDivWidth / 8) + "px";
            }

            //Orientation changes
            if (window.innerHeight < window.innerWidth) {
                document.getElementById("iframe_chat").style.width = (document.getElementsByClassName("user_about_div")[0].getBoundingClientRect().width - 10) + "px";
                document.getElementById("iframe_chat").style.marginLeft = (document.getElementsByClassName("user_about_div")[0].getBoundingClientRect().x + 10) + "px";
                document.getElementById("iframe_chat").style.MozMarginStart = (document.getElementsByClassName("user_about_div")[0].getBoundingClientRect().left + 10) + "px";
                document.getElementById("iframe_chat").style.webkitMarginStart = (document.getElementsByClassName("user_about_div")[0].getBoundingClientRect().left + 10) + "px";
            } else {
                document.getElementById("iframe_chat").style.width = (document.getElementById("user_div").getBoundingClientRect().width) + "px";
                document.getElementById("iframe_chat").style.marginLeft = (document.getElementById("user_div").getBoundingClientRect().x) + "px";
                document.getElementById("iframe_chat").style.MozMarginStart = (document.getElementById("user_div").getBoundingClientRect().left) + "px";
                document.getElementById("iframe_chat").style.webkitMarginStart = (document.getElementById("user_div").getBoundingClientRect().left) + "px";

                if (imageDivWidth > imageDivHeight)
                    document.getElementById("user_div").style.height = imageDivHeight + "px";
                else
                    document.getElementById("user_div").style.height = imageDivWidth + "px";
            }

            //User about div Height
            if (imageDivWidth > imageDivHeight)
                document.getElementsByClassName("user_about_div")[0].style.height = imageDivHeight + "px";
            else
                document.getElementsByClassName("user_about_div")[0].style.height = imageDivWidth + "px";

            //Resize Bio
            positionTextarea();
            user_bio.scrollTop = user_bio.scrollHeight;

            window.onresize = function (event) {
                //Bio
                positionTextarea();
                user_bio.scrollTop = user_bio.scrollHeight;

                //Images and Orientation
                imageDivWidth = document.getElementById("user_image_div").clientWidth;
                imageDivHeight = document.getElementById("user_image_div").clientHeight;

                if (imageDivWidth > imageDivHeight) {
                    document.getElementById("user_image").style.height = imageDivHeight + "px";
                    document.getElementById("user_image").style.width = imageDivHeight + "px";

                    if (isSelf) {
                        document.getElementById("user_edit_image").style.height = (imageDivHeight / 8) + "px";
                        document.getElementById("user_edit_image").style.width = (imageDivHeight / 8) + "px";
                    }
                } else {
                    document.getElementById("user_image").style.height = imageDivWidth + "px";
                    document.getElementById("user_image").style.width = imageDivWidth + "px";

                    if (isSelf) {
                        document.getElementById("user_edit_image").style.height = (imageDivWidth / 8) + "px";
                        document.getElementById("user_edit_image").style.width = (imageDivWidth / 8) + "px";
                    }
                }

                if (window.innerHeight < window.innerWidth) {
                    document.getElementById("iframe_chat").style.width = (document.getElementsByClassName("user_about_div")[0].getBoundingClientRect().width - 10) + "px";
                    document.getElementById("iframe_chat").style.marginLeft = (document.getElementsByClassName("user_about_div")[0].getBoundingClientRect().x + 10) + "px";
                    document.getElementById("iframe_chat").style.MozMarginStart = (document.getElementsByClassName("user_about_div")[0].getBoundingClientRect().left + 10) + "px";
                    document.getElementById("iframe_chat").style.webkitMarginStart = (document.getElementsByClassName("user_about_div")[0].getBoundingClientRect().left + 10) + "px";
                } else {
                    document.getElementById("iframe_chat").style.width = (document.getElementById("user_div").getBoundingClientRect().width) + "px";
                    document.getElementById("iframe_chat").style.marginLeft = (document.getElementById("user_div").getBoundingClientRect().x) + "px";
                    document.getElementById("iframe_chat").style.MozMarginStart = (document.getElementById("user_div").getBoundingClientRect().left) + "px";
                    document.getElementById("iframe_chat").style.webkitMarginStart = (document.getElementById("user_div").getBoundingClientRect().left) + "px";

                    if (imageDivWidth > imageDivHeight && document.getElementById("user_div").getBoundingClientRect().height > imageDivHeight)
                        document.getElementById("user_div").style.height = imageDivHeight + "px";
                    else if (document.getElementById("user_div").getBoundingClientRect().height > imageDivWidth)
                        document.getElementById("user_div").style.height = imageDivWidth + "px";
                }

                if (imageDivWidth > imageDivHeight)
                    document.getElementsByClassName("user_about_div")[0].style.height = imageDivHeight + "px";
                else
                    document.getElementsByClassName("user_about_div")[0].style.height = imageDivWidth + "px";
            };

            //Keep alive
            if (isLoggedIn)
                keepAlive();

            //Chat
            const iframe_chat = document.getElementById("iframe_chat");
            iframe_chat.src = "https://swirlia.net:3000/chat.html?username=" + user_info.username + "&phpsessid=" + phpsessid
                + "&sounds_enabled=" + user_info.sounds_enabled;
        }

        function lastSeenDate(last_seen) {
            if (last_seen < 30)
                document.getElementById("online_status_label").innerText = `Son görülme:` + `\xa0\xa0` + `Birkaç saniye önce`;
            else if (last_seen < 60)
                document.getElementById("online_status_label").innerText = `Son görülme:` + `\xa0\xa0` + `Bir dakikadan daha az`;
            else if (last_seen < 60*4)
                document.getElementById("online_status_label").innerText = `Son görülme:` + `\xa0\xa0` + `Birkaç dakika önce`;
            else if (last_seen < 60*8)
                document.getElementById("online_status_label").innerText = `Son görülme:` + `\xa0\xa0` + `5 dakika önce`;
            else if (last_seen < 60*13)
                document.getElementById("online_status_label").innerText = `Son görülme:` + `\xa0\xa0` + `10 dakika önce`;
            else if (last_seen < 60*18)
                document.getElementById("online_status_label").innerText = `Son görülme:` + `\xa0\xa0` + `15 dakika önce`;
            else if (last_seen < 60*23)
                document.getElementById("online_status_label").innerText = `Son görülme:` + `\xa0\xa0` + `20 dakika önce`;
            else if (last_seen < 60*28)
                document.getElementById("online_status_label").innerText = `Son görülme:` + `\xa0\xa0` + `25 dakika önce`;
            else if (last_seen < 60 * 41)
                document.getElementById("online_status_label").innerText = `Son görülme:` + `\xa0\xa0` + `Yarım saat önce`;
            else if (last_seen < 60 * 55)
                document.getElementById("online_status_label").innerText = `Son görülme:` + `\xa0\xa0` + `45 dakika önce`;
            else if (last_seen < 60 * 60 * 2)
                document.getElementById("online_status_label").innerText = `Son görülme:` + `\xa0\xa0` + `1 saat önce`;
            else if (last_seen < 60 * 60 * 4)
                document.getElementById("online_status_label").innerText = `Son görülme:` + `\xa0\xa0` + `Birkaç saat önce`;
            else if (last_seen < 60 * 60 * 6)
                document.getElementById("online_status_label").innerText = `Son görülme:` + `\xa0\xa0` + `5 saat önce`;
            else if (last_seen < 60 * 60 * 8)
                document.getElementById("online_status_label").innerText = `Son görülme:` + `\xa0\xa0` + `5 saatten fazla`;
            else if (last_seen < 60 * 60 * 10)
                document.getElementById("online_status_label").innerText = `Son görülme:` + `\xa0\xa0` + `10 saat önce`;
            else if (last_seen < 60 * 60 * 13)
                document.getElementById("online_status_label").innerText = `Son görülme:` + `\xa0\xa0` + `10 saatten fazla`;
            else if (last_seen < 60 * 60 * 15)
                document.getElementById("online_status_label").innerText = `Son görülme:` + `\xa0\xa0` + `15 saat önce`;
            else if (last_seen < 60 * 60 * 18)
                document.getElementById("online_status_label").innerText = `Son görülme:` + `\xa0\xa0` + `15 saatten fazla`;
            else if (last_seen < 60 * 60 * 20)
                document.getElementById("online_status_label").innerText = `Son görülme:` + `\xa0\xa0` + `20 saat önce`;
            else if (last_seen < 60 * 60 * 23)
                document.getElementById("online_status_label").innerText = `Son görülme:` + `\xa0\xa0` + `20 saatten fazla`;
            else if (last_seen < 60 * 60 * 40)
                document.getElementById("online_status_label").innerText = `Son görülme:` + `\xa0\xa0` + `1 gün önce`;
            else if (last_seen < 60 * 60 * 64)
                document.getElementById("online_status_label").innerText = `Son görülme:` + `\xa0\xa0` + `2 gün önce`;
            else if (last_seen < 60 * 60 * 88)
                document.getElementById("online_status_label").innerText = `Son görülme:` + `\xa0\xa0` + `3 gün önce`;
            else if (last_seen < 60 * 60 * 112)
                document.getElementById("online_status_label").innerText = `Son görülme:` + `\xa0\xa0` + `4 gün önce`;
            else if (last_seen < 60 * 60 * 136)
                document.getElementById("online_status_label").innerText = `Son görülme:` + `\xa0\xa0` + `5 gün önce`;
            else if (last_seen < 60 * 60 * 160)
                document.getElementById("online_status_label").innerText = `Son görülme:` + `\xa0\xa0` + `6 gün önce`;
            else if (last_seen < 60 * 60 * 24 * 8)
                document.getElementById("online_status_label").innerText = `Son görülme:` + `\xa0\xa0` + `1 hafta önce`;
            else if (last_seen < 60 * 60 * 24 * 10)
                document.getElementById("online_status_label").innerText = `Son görülme:` + `\xa0\xa0` + `1 haftadan fazla`;
            else if (last_seen < 60 * 60 * 24 * 14)
                document.getElementById("online_status_label").innerText = `Son görülme:` + `\xa0\xa0` + `2 hafta önce`;
            else if (last_seen < 60 * 60 * 24 * 17)
                document.getElementById("online_status_label").innerText = `Son görülme:` + `\xa0\xa0` + `2 haftadan fazla`;
            else if (last_seen < 60 * 60 * 24 * 21)
                document.getElementById("online_status_label").innerText = `Son görülme:` + `\xa0\xa0` + `3 hafta önce`;
            else if (last_seen < 60 * 60 * 24 * 24)
                document.getElementById("online_status_label").innerText = `Son görülme:` + `\xa0\xa0` + `3 haftadan fazla`;
            else if (last_seen < 60 * 60 * 24 * 28)
                document.getElementById("online_status_label").innerText = `Son görülme:` + `\xa0\xa0` + `4 hafta önce`;
            else if (last_seen < 60 * 60 * 24 * 35)
                document.getElementById("online_status_label").innerText = `Son görülme:` + `\xa0\xa0` + `1 ay önce`;
            else if (last_seen < 60 * 60 * 24 * 55)
                document.getElementById("online_status_label").innerText = `Son görülme:` + `\xa0\xa0` + `1 aydan fazla`;
            else if (last_seen < 60 * 60 * 24 * 65)
                document.getElementById("online_status_label").innerText = `Son görülme:` + `\xa0\xa0` + `2 ay önce`;
            else if (last_seen < 60 * 60 * 24 * 85)
                document.getElementById("online_status_label").innerText = `Son görülme:` + `\xa0\xa0` + `2 aydan fazla`;
            else if (last_seen < 60 * 60 * 24 * 95)
                document.getElementById("online_status_label").innerText = `Son görülme:` + `\xa0\xa0` + `3 ay önce`;
            else if (last_seen < 60 * 60 * 24 * 115)
                document.getElementById("online_status_label").innerText = `Son görülme:` + `\xa0\xa0` + `3 aydan fazla`;
            else if (last_seen < 60 * 60 * 24 * 214)
                document.getElementById("online_status_label").innerText = `Son görülme:` + `\xa0\xa0` + `6 ay önce`;
            else if (last_seen < 60 * 60 * 24 * 304)
                document.getElementById("online_status_label").innerText = `Son görülme:` + `\xa0\xa0` + `6 aydan fazla`;
            else if (last_seen < 60 * 60 * 24 * 669)
                document.getElementById("online_status_label").innerText = `Son görülme:` + `\xa0\xa0` + `1 yıl önce`;
            else if (last_seen < 60 * 60 * 24 * 1034)
                document.getElementById("online_status_label").innerText = `Son görülme:` + `\xa0\xa0` + `2 yıl önce`;
            else if (last_seen < 60 * 60 * 24 * 1399)
                document.getElementById("online_status_label").innerText = `Son görülme:` + `\xa0\xa0` + `3 yıl önce`;
            else if (last_seen < 60 * 60 * 24 * 1764)
                document.getElementById("online_status_label").innerText = `Son görülme:` + `\xa0\xa0` + `4 yıl önce`;
            else if (last_seen < 60 * 60 * 24 * 2129)
                document.getElementById("online_status_label").innerText = `Son görülme:` + `\xa0\xa0` + `5 yıl önce`;
            else if (last_seen < 60 * 60 * 24 * 2494)
                document.getElementById("online_status_label").innerText = `Son görülme:` + `\xa0\xa0` + `6 yıl önce`;
            else if (last_seen < 60 * 60 * 24 * 2859)
                document.getElementById("online_status_label").innerText = `Son görülme:` + `\xa0\xa0` + `7 yıl önce`;
            else if (last_seen < 60 * 60 * 24 * 3224)
                document.getElementById("online_status_label").innerText = `Son görülme:` + `\xa0\xa0` + `8 yıl önce`;
            else if (last_seen < 60 * 60 * 24 * 3589)
                document.getElementById("online_status_label").innerText = `Son görülme:` + `\xa0\xa0` + `9 yıl önce`;
            else if (last_seen < 60 * 60 * 24 * 3954)
                document.getElementById("online_status_label").innerText = `Son görülme:` + `\xa0\xa0` + `10 yıl önce`;
            else
                document.getElementById("online_status_label").innerText = `Son görülme:` + `\xa0\xa0` + `10 yıldan fazla`;
        }

        function keepAlive() {
            //First
            var params = "operation=keepAlive&phpsessid=" + phpsessid;
            var xhr = new XMLHttpRequest();
            xhr.open('POST', 'https://swirlia.net/php/Profile.php', true);
            xhr.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');
            xhr.withCredentials = true;
            xhr.send(params);
            xhr.onload = function () {
                var server_response = JSON.parse(this.response);

                if (server_response.result === "failure")
                    location.href = "";
            }

            var x = setInterval(function () {
                var params = "operation=keepAlive&phpsessid=" + phpsessid;
                var xhr = new XMLHttpRequest();
                xhr.open('POST', 'https://swirlia.net/php/Profile.php', true);
                xhr.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');
                xhr.withCredentials = true;
                xhr.send(params);
                xhr.onload = function () {
                    var server_response = JSON.parse(this.response);

                    if (server_response.result === "failure")
                        location.href = "";
                }
            }, 30000);
        }

        function handleImageUpload(imageFile) {
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
                    var params = "operation=uploadPhoto&type=" + compressedFile.type + "&photo=" + fileLoadedEvent.target.result + "&phpsessid=" + phpsessid;
                    var xhr = new XMLHttpRequest();
                    xhr.open('POST', 'https://swirlia.net/php/Profile.php', true);
                    xhr.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');
                    xhr.withCredentials = true;
                    xhr.send(params);

                    xhr.onload = function () {
                        uploadImage = false;
                        if (document.getElementById("form") != null)
                            closeForm(document.getElementById("form"));

                        var server_response = JSON.parse(this.response);

                        if (server_response.result === "success") {
                            selfImage = true;
                            var timestamp = new Date().getTime();

                            var user_image = document.getElementById("user_image");

                            new Promise((resolve, reject) => {
                                user_image.src = "https://swirlia.net/php/uploads/" + md5(username) + ".png?t=" + timestamp;
                            });

                            imagePath = "https://swirlia.net/php/uploads/" + md5(username) + ".png?t=";
                        }
                    }

                }

                function closeForm(form) {
                    if (form == null)
                        return;

                    form.classList.remove('active');
                    overlay.classList.remove('active');
                    form_error.style.display = "none";
                }
            }
        }

        ///////////////FORM///////////////////
        function openForm(form, id) {
            if (form == null)
                return;

            form.classList.add('active');
            overlay.classList.add('active');

            if (id === "user_edit_image")
                user_edit_image_form();
            else if (id === "user_image")
                user_image_form();
            else if (id === "block")
                block();
            else if (id === "report")
                report();
            else if (id === "preferences")
                preferences();
            else if (id === "followings")
                followings();
            else if (id === "support")
                support();

            //Close form
            overlay.onclick = function () {
                if (form.classList.contains("active")) {
                    if (id === "followings") {
                        var x = setTimeout(function () {
                            const followings_form = document.getElementById("followings_form");
                            const ul = document.getElementById("followings_ul");
                            followings_form.removeChild(ul);
                        }, 200);
                    } else if (id === "preferences")
                        preferences_closing_sound.play();

                    closeForm(form);
                }
            }

            document.addEventListener('keyup', (e) => {
                if (e.code === "Escape" && form.classList.contains("active")) {
                    if (id === "followings") {
                        var x = setTimeout(function () {
                            const followings_form = document.getElementById("followings_form");
                            const ul = document.getElementById("followings_ul");
                            followings_form.removeChild(ul);
                        }, 200);
                    } else if (id === "preferences")
                        preferences_closing_sound.play();

                    closeForm(form);
                }
            });
        }

        function closeForm(form) {
            form.classList.remove('active');
            overlay.classList.remove('active');
            form_error.style.display = "none";
        }

        function user_edit_image_form() {
            //Clear Input
            const img_input = document.getElementById("user_img_change");
            img_input.value = '';

            document.getElementById("user_img_change").disabled = false;
            document.getElementById("user_img_submit").disabled = false;
            document.getElementById("user_img_remove").disabled = false;

            //Checking if uploading
            if (uploadImage) {
                document.getElementById("user_img_change").disabled = true;
                document.getElementById("user_img_submit").disabled = true;
                document.getElementById("user_img_remove").disabled = true;

                const form_error = document.getElementById("form_error");
                form_error.style.display = "block";
                form_error.innerText = "Gönderiliyor..."
            }

            //Remove Image
            const remove_photo = document.getElementById("user_img_remove");
            if (selfImage) {
                remove_photo.style.display = "initial";

                remove_photo.onclick = function () {
                    var params = "operation=removePhoto&phpsessid=" + phpsessid;
                    var xhr = new XMLHttpRequest();
                    xhr.open('POST', 'https://swirlia.net/php/Profile.php', true);
                    xhr.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');
                    xhr.withCredentials = true;
                    xhr.send(params);

                    selfImage = false;
                    remove_photo.style.display = "none";

                    new Promise((resolve, reject) => {
                        user_image.src = "https://swirlia.net/php/uploads/user.png";
                    });

                    imagePath = "https://swirlia.net/php/uploads/user.png";
                    closeForm(form);
                }
            } else
                remove_photo.style.display = "none";
        }

        function user_image_form() {
            const user_img = document.getElementById("user_img");

            new Promise((resolve, reject) => {
                user_img.src = imagePath;
            });
        }

        function block() {
            const yes = document.getElementById("block_yes");
            const no = document.getElementById("block_no");

            yes.onclick = function () {
                var params = "operation=block&id=" + requested_id + "&phpsessid=" + phpsessid;
                var xhr = new XMLHttpRequest();
                xhr.open('POST', 'https://swirlia.net/php/Profile.php', true);
                xhr.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');
                xhr.withCredentials = true;
                xhr.send(params);
                xhr.onload = function () {
                    location.href = username;
                }
            }

            no.onclick = function () {
                closeForm(document.getElementById("block_form"));
            }
        }

        function report() {
            if (isLoggedIn) {
                const p = document.getElementById("report_p");
                p.style.display = "none";

                document.getElementById("improper_img").checked = false;
                document.getElementById("improper_bio").checked = false;
                document.getElementById("fake_acc").checked = false;
                document.getElementById("extra_textarea").value = "";

                const improper_img_label = document.getElementById("improper_img_label");
                const improper_bio_label = document.getElementById("improper_bio_label");
                const face_acc_label = document.getElementById("fake_acc_label");

                improper_img_label.onclick = function () {
                    document.getElementById("improper_img").checked = true;
                }

                improper_bio_label.onclick = function () {
                    document.getElementById("improper_bio").checked = true;
                }

                face_acc_label.onclick = function () {
                    document.getElementById("fake_acc").checked = true;
                }

                const send_report = document.getElementById("send_report");

                send_report.onclick = function () {
                    const error_p = document.getElementById("error_p");

                    if (!document.getElementById("improper_img").checked
                        && !document.getElementById("improper_bio").checked
                        && !document.getElementById("fake_acc").checked) {
                        error_p.style.display = "initial";
                        error_p.innerText = "*Lütfen bir şikayet sebebi seçiniz";
                    } else {
                        error_p.style.display = "none";

                        var reason = "Improper image";
                        if (document.getElementById("improper_bio").checked)
                            reason = "Improper bio";
                        else if (document.getElementById("fake_acc").checked)
                            reason = "Fake account";

                        var extra = document.getElementById("extra_textarea").value;
                        var material_type = "", material = "";

                        var params = "operation=report&id=" + requested_id + "&reason=" + reason
                            + "&message=" + encodeURIComponent(extra) + "&material_type=" + material_type + "&material=" + encodeURIComponent(material)
                            + "&is_anonim=" + "" + "&anon_name=" + "" + "&phpsessid=" + phpsessid;
                        var xhr = new XMLHttpRequest();
                        xhr.open('POST', 'https://swirlia.net/php/Profile.php', true);
                        xhr.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');
                        xhr.withCredentials = true;
                        xhr.send(params);
                        xhr.onload = function () {
                            location.href = username;
                        }
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

        function preferences() {
            preferences_opening_sound.play();

            const iframe = document.getElementById("preferences_iframe");

            const general = document.getElementById("preferences_general");
            general.onclick = function () {
                preferences_switching_sound.play();

                general.classList.add("preferences_menu_active");
                account.classList.remove("preferences_menu_active");
                blacklist.classList.remove("preferences_menu_active");

                iframe.src = "html/preferences_general.html";
            }

            const account = document.getElementById("preferences_account");
            account.onclick = function () {
                preferences_switching_sound.play();

                account.classList.add("preferences_menu_active");
                general.classList.remove("preferences_menu_active");
                blacklist.classList.remove("preferences_menu_active");

                iframe.src = "html/preferences_account.html";
            }

            const blacklist = document.getElementById("preferences_blacklist");
            blacklist.onclick = function () {
                preferences_switching_sound.play();

                blacklist.classList.add("preferences_menu_active");
                general.classList.remove("preferences_menu_active");
                account.classList.remove("preferences_menu_active");

                iframe.src = "html/preferences_blacklist.html";
            }

            iframe.src = "html/preferences_general.html";
            general.classList.add("preferences_menu_active");
            account.classList.remove("preferences_menu_active");
            blacklist.classList.remove("preferences_menu_active");
        }

        function followings() {
            serverProcessing = true;

            const followings_form = document.getElementById("followings_form");
            const ul = document.createElement("ul");

            ul.setAttribute("id", "followings_ul");
            followings_form.appendChild(ul);

            const p = document.getElementById("followings_p");
            p.style.display = "none";

            const header = document.getElementById("followings_header");
            header.style.display = "flex";

            var params = "operation=getFollowings&phpsessid=" + phpsessid;
            var xhr = new XMLHttpRequest();
            xhr.open('POST', 'https://swirlia.net/php/Profile.php', true);
            xhr.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');
            xhr.withCredentials = true;
            xhr.send(params);
            xhr.onload = function () {
                var server_response = JSON.parse(this.response);

                if (server_response.result === "success") {
                    var searchbox = document.getElementById("searchbox");
                    searchbox_text.oninput = function () {
                        if (!serverProcessing)
                            search(searchbox_text.value, ul);
                    }

                    for (let i = 0; i < server_response.followings.length; i++) {
                        var label = document.createElement("label");
                        label.innerText = server_response.followings[i].username;
                        label.classList.add("followings_username");

                        var img = document.createElement("img");
                        
                        new Promise((resolve, reject) => {
                            img.src = "https://swirlia.net/" + server_response.followings[i].profile_img;
                        });

                        img.classList.add("followings_profile");

                        var status = document.createElement("img");
                        if (server_response.followings[i].is_online) {
                            new Promise((resolve, reject) => {
                                status.src = "images/online.png";
                            });
                        } else {
                            new Promise((resolve, reject) => {
                                status.src = "images/offline.png";
                            });
                        }

                        status.classList.add("followings_status");

                        var unfollow = document.createElement("img");

                        new Promise((resolve, reject) => {
                            unfollow.src = "images/unfollow_red.png";
                        });

                        unfollow.classList.add("followings_unfollow");
                        unfollow.onclick = function () {
                            if (!serverProcessing) {
                                serverProcessing = true;

                                var params = "operation=unfollow&id=" + server_response.followings[i].id + "&phpsessid=" + phpsessid;
                                var xhr = new XMLHttpRequest();
                                xhr.open('POST', 'https://swirlia.net/php/Profile.php', true);
                                xhr.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');
                                xhr.withCredentials = true;
                                xhr.send(params);
                                xhr.onload = function () {
                                    for (let j = 0; j < ul.childNodes.length; j++) {
                                        if (ul.childNodes[j].childNodes[0].childNodes[2].innerText === server_response.followings[i].username)
                                            ul.removeChild(ul.childNodes[j]);
                                    }

                                    serverProcessing = false;

                                    search(searchbox_text.value, ul);
                                }
                            }
                        }

                        var a = document.createElement("a");
                        a.href = server_response.followings[i].username;
                        a.target = "_blank";

                        a.appendChild(status);
                        a.appendChild(img);
                        a.appendChild(label);

                        var li = document.createElement("li");
                        li.appendChild(a);
                        li.appendChild(unfollow);

                        ul.appendChild(li);
                    }
                } else {
                    if (server_response.message === "User not exists")
                        location.href = "";
                    else if (server_response.message === "User not logged in")
                        location.href = "";
                    else {
                        ul.style.display = "none";

                        const header = document.getElementById("followings_header");
                        header.style.display = "none";

                        const p = document.getElementById("followings_p");
                        p.style.display = "initial";
                    }
                }

                document.getElementById("followings_form").style.display = "flex";
                serverProcessing = false;
            }

            function search(filter, ul) {
                let counter = 0;

                for (let i = 0; i < ul.childNodes.length; i++) {
                    var label = ul.childNodes[i].childNodes[0].childNodes[2];
                    var li = ul.childNodes[i];

                    if (!(label.innerText.toUpperCase()).includes(filter.toUpperCase()) && filter !== "")
                        li.style.display = "none";
                    else {
                        li.style.display = "flex";
                        counter++;
                    }
                }

                if (counter === 0) {
                    const p = document.getElementById("followings_p");
                    p.innerText = "Aramalarla eşleşen kullanıcı yok.";
                    p.style.display = "initial";
                } else {
                    const p = document.getElementById("followings_p");
                    p.style.display = "none";
                    p.innerText = "Takip edilenler listeniz boş.";
                }
            }
        }

        function support() {
            if (isLoggedIn) {
                scrollToTop(500);

                function scrollToTop(duration) {
                    if (document.scrollingElement.scrollTop === 0)
                        return;

                    const cosParameter = document.scrollingElement.scrollTop / 2;
                    let scrollCount = 0, oldTimestamp = null;

                    function step(newTimestamp) {
                        if (oldTimestamp !== null) {
                            scrollCount += Math.PI * (newTimestamp - oldTimestamp) / duration;

                            if (scrollCount >= Math.PI)
                                return document.scrollingElement.scrollTop = 0;

                            document.scrollingElement.scrollTop = cosParameter + cosParameter * Math.cos(scrollCount);
                        }

                        oldTimestamp = newTimestamp;
                        window.requestAnimationFrame(step);
                    }

                    window.requestAnimationFrame(step);
                }

                const error_p = document.getElementById("error_p_nd");
                const textarea = document.getElementById("message_textarea");
                const support_category = document.getElementById("support_category");
                const elements = support_category.options;

                for (var i = 0; i < elements.length; i++)
                    elements[i].selected = false;

                textarea.value = "";

                error_p.innerText = "";
                error_p.style.display = "none";

                const send_support = document.getElementById("send_support");

                send_support.onclick = function () {
                    if (textarea.length <= 10) {
                        error_p.style.display = "initial";
                        error_p.innerText = "*Lütfen en az 10 karakter olacak şekilde bir destek mesajı giriniz.";
                    } else {
                        error_p.innerText = "";
                        error_p.style.display = "none";

                        const reason = support_category.options[support_category.selectedIndex].value;
                        const message = textarea.value;

                        var params = "operation=support&reason=" + reason + "&message=" + encodeURIComponent(message) + "&phpsessid=" + phpsessid;
                        var xhr = new XMLHttpRequest();
                        xhr.open('POST', 'https://swirlia.net/php/Profile.php', true);
                        xhr.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');
                        xhr.withCredentials = true;
                        xhr.send(params);
                        xhr.onload = function () {
                            closeForm(document.getElementById("support_form"));
                        }
                    }
                }
            } else
                location.href = "";
        }

        //DISABLE IMAGE DRAGGING
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

        //IFRAME MESSAGE
        window.addEventListener('message', receiveMessage, false);

        function receiveMessage(event) {
            document.title = event.data;
        }
    }
} catch (err) {
    document.body.innerHTML = "";
    setTimeout(() => {
        alert("Access Denied");
    }, 1000);
}