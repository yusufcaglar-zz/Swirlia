const rooms = [];

// Create room
function createRoom_history(id, senderIp, senderId, senderUsername, senderOnline, receiverId) {
    const room = {
        id: id, senderIp: senderIp, senderId: senderId, senderUsername: senderUsername, senderOnline: senderOnline, receiverId: receiverId
    };

    rooms.push(room);
}

// Get current room
function getCurrentRoom_history(id) {
    return rooms.find(room => room.id === id);
}

// Remove room
function removeRoom_history(id) {
    const index = rooms.findIndex(room => room.id === id);

    if (index !== -1)
        rooms.splice(index, 1);
}

//Swap Anonimity
function swapAnonimity_history(id, username) {
    const index = rooms.findIndex(room => room.id === id);

    rooms[index].senderUsername = username;
}

module.exports = {
    createRoom_history,
    getCurrentRoom_history,
    removeRoom_history,
    swapAnonimity_history
};