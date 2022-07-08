const rooms = [];

// Create room
function createRoom(id, senderIp, senderId, senderUsername, senderSocket, senderOnline, senderAnonim, senderPhpsessid,
    receiverId, receiverUsername, receiverSocket, receiverPhpsessid) {
    const room = {
        id: id, senderIp: senderIp, senderId: senderId, senderUsername: senderUsername, senderSocket: senderSocket, senderOnline: senderOnline, senderAnonim: senderAnonim, senderPhpsessid: senderPhpsessid,
        receiverId: receiverId, receiverUsername: receiverUsername, receiverSocket: receiverSocket, receiverPhpsessid: receiverPhpsessid, receiverAvailable: true
    };
    rooms.push(room);
}

// Get current room
function getCurrentRoom(id) {
    return rooms.find(room => room.id === id);
}

// Get all related rooms
function getAllRooms(id) {
    return rooms.filter(room => room.senderSocket === id || room.receiverSocket === id);
}

// Remove room
function removeRoom(id) {
    const index = rooms.findIndex(room => room.id === id);

    if (index !== -1)
        rooms.splice(index, 1);
}

//Set receiver not available
function setReceiverNotAvailable(id) {
    const index = rooms.findIndex(room => room.id === id);

    rooms[index].receiverAvailable = false;
}

//Swap Anonimity
function swapAnonimity(id, username) {
    const index = rooms.findIndex(room => room.id === id);

    rooms[index].senderAnonim = false;
    rooms[index].senderUsername = username;
}

module.exports = {
  createRoom,
  getCurrentRoom,
  getAllRooms,
  removeRoom,
  setReceiverNotAvailable,
  swapAnonimity
};