//Dependencies
const path = require("path");
const express = require("express");
const socketio = require("socket.io");
const axios = require('axios');

const formatMessage = require('/root/messages');
const {
    createRoom,
    getCurrentRoom,
    getAllRooms,
    removeRoom,
    setReceiverNotAvailable,
    swapAnonimity
} = require("/root/rooms");
const {
    createRoom_history,
    getCurrentRoom_history,
    removeRoom_history,
    swapAnonimity_history
} = require("/root/rooms_history");

//Https
const https = require("https");
const fs = require("fs");

const options = {
    key: fs.readFileSync("/etc/pki/tls/private/93d4ef7fd0373e0d.pem"),
    cert: fs.readFileSync("/etc/pki/tls/certs/93d4ef7fd0373e0d.pem")
};

//Port, Server and Socket
const PORT = process.env.PORT || 3000;
const app = express();
const server = https.createServer(options, app);
const io = socketio(server);

//Set static folder
app.use(express.static(path.join(__dirname, "../var/www/html")));

//Listen Port
server.listen(PORT);

//Reset Chats
var data = JSON.stringify({
    operation: "resetChats",
    password: "!Fsc%vA>vtD5qahh"
});
post(data);

//Listen Connections
io.on("connection", socket => {
    //Create Room
    socket.on("createRoom", ({ id, senderIp, senderId, senderUsername, senderSocket, senderOnline, senderAnonim,
        receiverId, receiverUsername, receiverSocket }) => {
        if (senderOnline) {
            var data = JSON.stringify({
                operation: "getSessions",
                receiver: receiverId,
                sender: senderId,
                password: "!Fsc%vA>vtD5qahh"
            });
        } else {
            var data = JSON.stringify({
                operation: "getSessions",
                receiver: receiverId,
                sender: null,
                password: "!Fsc%vA>vtD5qahh"
            });
        }

        const headers = { 'Content-Type': 'application/json' };
        const config = { headers: headers };

        axios.post('https://swirlia.net/php/Chat.php', data, config)
            .then(function (response) {
                const server_response = JSON.parse(response.data.trim());

                if (server_response.result === "success") {
                    createRoom(id, senderIp, senderId, senderUsername, senderSocket, senderOnline, senderAnonim, server_response.sender_phpsessid,
                        receiverId, receiverUsername, receiverSocket, server_response.receiver_phpsessid);

                    createRoom_history(id, senderIp, senderId, senderUsername, senderOnline, receiverId);

                    socket.join(id);
                    io.sockets.connected[receiverSocket].join(id);

                    socket.emit("startConversation", formatMessage(receiverUsername, "Chat started with", { room: id }));
                    socket.broadcast.to(id).emit("startConversation", formatMessage(senderUsername, "Chat started with", { room: id }));
                } else
                    socket.emit("endConversation", formatMessage(receiverUsername, "Server failed", { room: id }));
            })
            .catch(function (error) {
                console.error(error);

                socket.emit("endConversation", formatMessage(receiverUsername, "Server failed", { room: id }));
            });
    });

    //Message
    socket.on("message", ({ id, isMedia, message, message_index, shouldStartConversation }) => {
        const room = getCurrentRoom(id);

        if (room) {
            if (room.senderSocket === socket.id)
                socket.emit("messageCallback", formatMessage(room.receiverUsername, "success", { message_index: message_index, room: room.id }));
            else
                socket.emit("messageCallback", formatMessage(room.senderUsername, "success", { message_index: message_index, room: room.id }));

            var data = JSON.stringify({
                operation: "ensureChat",
                receiverId: room.receiverId,
                receiverPhpsessid: room.receiverPhpsessid,
                senderIp: room.senderOnline ? room.senderId : room.senderIp,
                senderId: room.senderId,
                senderOnline: room.senderOnline,
                senderPhpsessid: room.senderPhpsessid,
                password: "!Fsc%vA>vtD5qahh"
            });

            const headers = { 'Content-Type': 'application/json' };
            const config = { headers: headers };

            axios.post('https://swirlia.net/php/Chat.php', data, config)
                .then(function (response) {
                    const server_response = JSON.parse(response.data.trim());

                    if (server_response.result === "success") {
                        if (room.senderSocket === socket.id) {
                            socket.broadcast.to(room.id).emit("message", formatMessage(room.senderUsername, message, { room: room.id, isMedia: isMedia }));

                            if (shouldStartConversation) {
                                data = JSON.stringify({
                                    operation: "startConversation",
                                    receiver: room.receiverId,
                                    sender: room.senderId,
                                    password: "!Fsc%vA>vtD5qahh"
                                });

                                post(data);
                            }
                        } else
                            socket.broadcast.to(room.id).emit("message", formatMessage(room.receiverUsername, message, { room: room.id, isMedia: isMedia }));
                    } else if (room.senderSocket === socket.id)
                        socket.emit("endConversation", formatMessage(room.receiverUsername, server_response.message, { room: room.id }));
                    else {
                        socket.emit("endConversation", formatMessage(room.senderUsername, server_response.message, { room: room.id }));
                        socket.broadcast.to(room.id).emit("endConversation", formatMessage(room.receiverUsername, server_response.message, { room: room.id }));
                    }
                })
                .catch(function (error) {
                    console.error(error);

                    if (room.senderSocket === socket.id)
                        socket.emit("endConversation", formatMessage(room.receiverUsername, "Server failed", { room: room.id }));
                    else {
                        socket.emit("endConversation", formatMessage(room.senderUsername, "Server failed", { room: room.id }));
                        socket.broadcast.to(room.id).emit("endConversation", formatMessage(room.receiverUsername, "Server failed", { room: room.id }));
                    }
                });
        } else
            socket.emit("messageCallback", formatMessage(socket.id, "failure", { message_index: message_index, room: id }));
    });

    //Block
    socket.on("block", (id) => {
        const room = getCurrentRoom_history(id);

        if (room) {
            data = JSON.stringify({
                operation: "block",
                receiverId: room.receiverId,
                anon_name: room.senderUsername,
                anon_ip: room.senderOnline ? room.senderId : room.senderIp,
                password: "!Fsc%vA>vtD5qahh"
            });

            post(data);

            removeRoom_history(id);
        }
    });

    //Swap Anonimity
    socket.on("swapAnonimity", ({ id, username }) => {
        const room = getCurrentRoom(id);

        if (room) {
            swapAnonimity(id, username);
            swapAnonimity_history(id, username);
            socket.broadcast.to(id).emit("swapAnonimity", formatMessage(username, "", { room: id}));
        }
    });

    //Seen
    socket.on("seen", (id) => {
        const room = getCurrentRoom(id);

        if (room)
            socket.broadcast.to(id).emit("seen", formatMessage("", "", { room: id }));
    });

    //Typing
    socket.on("typing", ({ id, status }) => {
        const room = getCurrentRoom(id);

        if (room)
            socket.broadcast.to(id).emit("typing", formatMessage("", "", { room: id, status: status }));
    });

    //Close Conversation From Both Sides
    socket.on("closeConversation", (id) => {
        const room = getCurrentRoom(id);

        if (room != null) {
            setReceiverNotAvailable(id);
            removeRoom_history(id);
            socket.broadcast.to(room.id).emit("endConversation", formatMessage(room.receiverUsername, "Has closed chat", { room: room.id }));
        }
    });

    //Disconnection, Remove Room
    socket.on("disconnect", () => {
        const rooms = getAllRooms(socket.id);

        if (rooms.length > 0) {
            while (rooms.length > 0) {
                var i = rooms.length - 1;

                if (rooms[i].receiverSocket === socket.id) {
                    socket.broadcast.to(rooms[i].id).emit("endConversation", formatMessage(rooms[i].receiverUsername, "Has disconnected", { room: rooms[i].id }));

                    var data = JSON.stringify({
                        operation: "nullToken",
                        token: rooms[i].receiverSocket,
                        password: "!Fsc%vA>vtD5qahh"
                    });

                    post(data);

                    removeRoom_history(rooms[i].id);
                } else if (rooms[i].receiverAvailable)
                    socket.broadcast.to(rooms[i].id).emit("endConversation", formatMessage(rooms[i].senderUsername, "Has disconnected", { room: rooms[i].id }));

                var data = JSON.stringify({
                    operation: "endConversation",
                    receiver: rooms[i].receiverId,
                    sender: rooms[i].senderId,
                    password: "!Fsc%vA>vtD5qahh"
                });

                post(data);

                removeRoom(rooms[i].id);
                rooms.splice(i, 1);
            }
        } else {
            var data = JSON.stringify({
                operation: "nullToken",
                token: socket.id,
                password: "!Fsc%vA>vtD5qahh"
            });

            post(data);
        }
    });
});

//Server Requests
function post(data) {
    const headers = { 'Content-Type': 'application/json' };
    const config = { headers: headers };

    axios.post('https://swirlia.net/php/Chat.php', data, config)
        .then(function (response) {
            console.log(response.status + " " + JSON.parse(data).operation);
        })
        .catch(function (error) {
            console.error(error);
        });
}

/*
    socket.emit(name, value) -> send message to only related user
    socket.broadcast.emit(name, value) -> send message to everyone except related user
    io.emit(name, value) -> send message to everyone
    */

    // sckt = io.sockets.connected[socketId] -> get socket from its id

    /*
    console.log(Object.keys(io.of('/chat').sockets).length) -> Namespace connectionts
    console.log(Object.keys(io.sockets.sockets).length) -> Non namespace connections
    console.log(io.sockets.adapter.rooms[rommId].length) -> Room connections
    */

    /* -> native https post
        var data = JSON.stringify({
            operation: "ensureChat",
            isOnline: isOnline,
            receiver: room.receiverId,
            sender: room.senderId,
            password: "!Fsc%vA>vtD5qahh"
        });

        const options = {
            hostname: 'swirlia.net',
            port: 443,
            path: '/php/Chat.php',
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            }
        };

        process.env["NODE_TLS_REJECT_UNAUTHORIZED"] = 0; //REMOVE THIS LINE WHEN HAVING REAL SERVER
        const req = https.request(options, res => {
            res.on('data', d => {
                process.stdout.write(d);
            });

            console.log(res.statusCode);
        });

        req.on('error', error => {
            console.error(error);
        });

        req.write(data);
        req.end();

process.env["NODE_TLS_REJECT_UNAUTHORIZED"] = 0; //REMOVE THIS LINE WHEN HAVING REAL SERVER
*/