const moment = require('moment');

function formatMessage(username, text, bundle) {
    return {
        username,
        text,
        time: moment().format('HH:mm:ss'),
        bundle
    };
}

module.exports = formatMessage;